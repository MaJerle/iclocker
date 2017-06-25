<?php

namespace Inc;

use \Slim\Slim;
use \Inc\Cache\Cache;
use \Valitron\Validator;
use \Model\DbEvent;

class Model {
	//Set table name used for database operations
	public $tableName = false;

	//These must not be static!
	public $associations = false;

	//Primary key in database
	public $primaryKey = null;

	//Virtual fields
	public $virtualFields = [];

	//Always include created by object if exists
	public $includeCreatedBy = true;

	//Application object
	public static $app;

	//Database object
	public static $db;

	//Event types
	const EVENT_INSERT = 0;
	const EVENT_UPDATE = 1;
	const EVENT_DELETE = 2;

	//Constructor
	public function __construct($app = false, $db = false) {
		//Save application context
		self::$app = $app;
		if (self::$app === false) {
			self::$app = \Slim\Slim::getInstance('default'); 
		}

		//Database object
		if ($db !== false) {
			self::$db = $db;
		} else {
			self::$db = self::$app->db;
		}

		//Must be array!
		if (!is_array($this->associations)) {
			$this->associations = [];
		}

		//Merge default values
		$this->associations = array_merge([
				'hasOne' => [],
				'belongsTo' => [],
				'manyToMany' => [],
				'hasMany' => []
			], $this->associations);

		//Check if table has created_by field
		if (
			isset(self::$db->tables[$this->tableName]['created_by']) && 
			!isset($this->associations['belongsTo']['CreatedBy'])) {

			//Add createdBy association
			$this->associations['belongsTo']['CreatedBy'] = [
				'modelName' => 'User',
				'foreignKey' => 'created_by',
				'conditions' => [
					//'CreatedBy.deleted' => 0,
				],
				'joinType' => 'LEFT',
				'fields' => [
					'CreatedBy.id',
					'CreatedBy.first_name',
					'CreatedBy.last_name',
					'CreatedBy.username',
					'CreatedBy.image',
					'CreatedBy.deleted'
				]
			];
		}
		//Check if table has modified_by field
		if (
			isset(self::$db->tables[$this->tableName]['modified_by']) && 
			!isset($this->associations['belongsTo']['ModifiedBy'])) {

			//Add ModifiedBy association
			$this->associations['belongsTo']['ModifiedBy'] = [
				'modelName' => 'User',
				'foreignKey' => 'modified_by',
				'conditions' => [
					//'ModifiedBy.deleted' => 0,
				],
				'joinType' => 'LEFT',
				'fields' => [
					'ModifiedBy.id',
					'ModifiedBy.first_name',
					'ModifiedBy.last_name',
					'ModifiedBy.username',
					'ModifiedBy.image',
					'ModifiedBy.deleted'
				]
			];
		}

		//Temporary save
		$assocs = $this->associations;

		//Go through types
		foreach (['hasOne', 'belongsTo', 'hasMany', 'manyToMany'] as $type) {
			//Check for existance
			$models = [];
			foreach ($assocs[$type] as $modelName => $opts) {
				//Check for array
				if (!is_array($opts)) {
					continue;
				}

				//Check if model name exists
				if (!isset($opts['modelName'])) {
					$opts['modelName'] = $modelName;
				}

				//add conditions
				if (!isset($opts['conditions']) || !is_array($opts['conditions'])) {
					$opts['conditions'] = [];
				}

				//add conditions
				if (!isset($opts['joins']) || !is_array($opts['joins'])) {
					$opts['joins'] = [];
				}

				//Check for join type
				if ($type == 'hasOne' || $type == 'belongsTo') {
					if (!isset($opts['joinType'])) {
						$opts['joinType'] = 'LEFT';
					}
				}

				//Check counter cache
				if ($type == 'belongsTo') {
					if (!isset($opts['counterCache'])) {
						$opts['counterCache'] = [];
					}
					if (!is_array($opts['counterCache'])) {
						$opts['counterCache'] = [$opts['counterCache'] => []];
					}
				}

				//Fields for select
				if (!isset($opts['fields'])) {
					$opts['fields'] = [];
				}

				//Only for specific
				if (in_array($type, ['hasMany', 'manyToMany'])) {
					//Sort ordering
					if (!isset($opts['order'])) {
						$opts['order'] = false;
					}
					//Limit number
					if (!isset($opts['limit'])) {
						$opts['limit'] = false;
					}
					//Dependant
					if (!isset($opts['dependant'])) {
						$opts['dependant'] = false;
					}
				}

				//Add to models
				$models[$modelName] = $opts;
			}

			//Create new models
			$assocs[$type] = $models;
		}

		//Save new value
		$this->associations = $assocs;

		//Check for primary key
		if ($this->primaryKey === null && get_class($this) != 'Inc\Model') {
			//Set primary key according to model name and '_id' suffix
			$this->primaryKey = 'id';
			//$this->primaryKey = strtolower(array_pop(explode('\\', get_class($this)))) . '_id';
		}

		//Set model name
		if (!isset($this->modelName)) {
			//Format model name
			$tokens = explode('\\', get_class($this));
			$this->modelName = array_pop($tokens);
		}

		if ($this->modelName == 'Comment') {
			//var_dump($this->associations); exit;
		}
	}

	//Get class name
	public function getName() {
		$tokens = explode('\\', get_class($this));
		return array_pop($tokens);
	}

	//Insert record(s) into database
	public static function insertData($dbName, $fields, $data = false, $options = []) {
		$options = array_merge([
			'events' => true
		], $options);

		$obj = null;
		if (is_object($dbName)) {
			$obj = $dbName;
			$dbName = $dbName->tableName;
		}

		//Get all table columns
		$tablecolumns = self::$db->tables[$dbName];

		//Check for data if "second order" array
		if (!is_array($fields)) {
			$fields = [$fields];
		}
		if ($data != false && !is_array($data)) {
			$data = [$data];
		}

		//No parameters on data
		if ($data == false) {
			$data = array(array_values($fields));
			$fields = array_keys($fields);
		} else {
			//Check for multi insert
			if (!is_array(array_values($data)[0])) {
				$data = array($data);
			}
		}

		//Check if all fields exists in table columns
		$keysToRemove = [];
		foreach ($fields as $k => $field) {
			if (!isset($tablecolumns[$field])) {
				unset($fields[$k]);
				$keysToRemove[] = $k;
			}
		}

		//Get date
		$date = self::getDate();

		//Remove fields if exists in data
		$addDeletedAt = false;
		foreach (['created_by', 'created_at', 'modified_by', 'modified_at', 'deleted_at'] as $f) {
			if (!isset($tablecolumns[$f])) {
				continue;
			}
			//Find field in fields array
			$keyPos = array_search($f, $fields);
			if ($keyPos !== false) {
				if ($f == 'deleted_at') {
					$addDeletedAt = true;
				}

				//Remove field
				unset($fields[$keyPos]);
				//Remove data
				foreach ($data as &$d) {
					unset($d[$keyPos]);
				}
			}
		}

		//@TODO
		//TEMPORARY_FIX!!
		if ($dbName != 'collections' && isset($tablecolumns['collection_id']) && !isset($fields['collection_id']) && !in_array('collection_id', $fields)) {
			//Add collection ID
			$fields[] = 'collection_id';

			//Add value for collection ID also
			foreach ($data as &$d) {
				$d[] = self::collection_id();
			}
		}

		//Add created by option if not already
		if (self::$app->user_logged && isset($tablecolumns['created_by']) && !in_array('created_by', $fields)) {
			//We will save created by
			$fields[] = 'created_by';

			//Add value to all records
			foreach ($data as &$d) {
				$d[] = self::userid();
			}
		}

		//Add created at option if not already
		if (isset($tablecolumns['created_at']) && !in_array('created_at', $fields)) {
			//We will save created by
			$fields[] = 'created_at';

			//Add value to all records
			foreach ($data as &$d) {
				$d[] = $date;
			}
		}

		//Add modified by option if not already
		if (self::$app->user_logged && isset($tablecolumns['modified_by']) && !in_array('modified_by', $fields)) {
			//We will save created by
			$fields[] = 'modified_by';

			//Add value to all records
			foreach ($data as &$d) {
				$d[] = self::userid();
			}
		}

		//Add modified at option if not already
		if (isset($tablecolumns['modified_at']) && !in_array('modified_at', $fields)) {
			//We will save created by
			$fields[] = 'modified_at';

			//Add value to all records
			foreach ($data as &$d) {
				$d[] = $date;
			}
		}

		//Add deleted at option if not already
		if (isset($tablecolumns['deleted_at']) && (!in_array('deleted_at', $fields) || $addDeletedAt)) {
			//We will save created by
			$fields[] = 'deleted_at';

			//Add value to all records
			foreach ($data as &$d) {
				$d[] = $date;
			}
		}

		//Format columns
		$columns = implode(', ', $fields);
		$queries = [];

		//Go through all value and create multiple VALUES brackets
		$matched = false;
		foreach ($data as &$d) {
			$str = [];
			foreach ($d as $k => $v) {
				if (in_array($k, $keysToRemove)) {
					continue;
				}
				if (is_numeric($v)) {
					$str[] = $v;
				} else {
					$str[] = '"' . self::$db->res($v) . '"';
				}
			}

			if (!empty($str)) {
				$queries[] = '(' . implode(', ', $str) . ')';
			}
		}

		//Create query
		$query = 'INSERT INTO ' . $dbName . ' 
			(' . $columns . ')
			VALUES ' . implode(', ', $queries);

		//Insert
		$insID = self::$db->insert($query);
		
		//Update counter cache
		if ($insID) {
			self::$db->updateCounterCache($obj, $insID, 'insert');
		}

		if ($obj !== null) {
			//Call function
			$obj->afterInsert($insID);
		}

		//Return insert ID
		return $insID;
	}

	//Add new event to database
	private static function addEvent($modelName, $foreignId, $type) {
		if (!is_array($foreignId)) {
			$foreignId = [$foreignId];
		}

		foreach ($foreignId as $id) {
			self::insertData(new DbEvent(), [
				'type' => $type, 
				'model' => $modelName,
				'foreign_id' => $id
			]);
		}
	}

	//Update record
	public static function updateData($dbName, $fields, $conditions, $options = []) {
		$options = array_merge([
			'revision' => true
		], $options);

		$obj = null;
		if (is_object($dbName)) {
			$obj = $dbName;
			$dbName = $dbName->tableName;
		}

		//Set fields
		if (empty($fields)) {
			$fields = ['modified_at' => self::getDate()];
		}

		//Get all table columns
		$tablecolumns = self::$db->tables[$dbName];

		//Force additional conditions
		if (isset($tablecolumns['deleted'])) {
			$conditions['deleted'] = 0;
		}
		if (isset($tablecolumns['revision_id'])) {
			$conditions['revision_id'] = 0;
		}

		//Check for number of records with condiitons
		$ids = [];
		$records = [];
		$modelName = $obj ? $obj->getName() : null;
		if (is_object($obj)) {
			$records = self::$db->selectEx($obj, [
				'type' => 'all',
				'conditions' => $conditions,
				'contain' => [],
				'virtualFields' => false,
				'createdBy' => false
			]);
			
			//There is no records for update
			if (!$records || count($records) == 0) {
				return false;
			}

			//Get ID values of records to edit
			foreach ($records as $c) {
				$ids[] = $c[$modelName]->{$obj->primaryKey};
			}
		}

		//Check if only last modified field should be updated for sync procedure
		$lastModifiedOnly = false;
		if (count($fields) == 1 && isset($fields['modified_at'])) {
			$lastModifiedOnly = true;
		}

		//Check options
		$options = array_merge([
			'foreignkey_update' => false
		], $options);

		//Format string from conditions array
		$where = self::$db->formatConditions([], $conditions);

		//Add modified by option if not already
		if (self::$app->user_logged && isset($tablecolumns['modified_by'])) {
			//We will save created by
			$fields['modified_by'] = self::userid();
		}

		//Add modified at option if not already
		if (isset($tablecolumns['modified_at'])) {
			//We will save created by
			$fields['modified_at'] = self::getDate();
		}

		//Check for revision reason
		$revisionReason = false;
		if (isset($fields['revision_reason'])) {
			$revisionReason = $fields['revision_reason'];
		} else {
			if (isset($tablecolumns['revision_reason'])) {
				$fields['revision_reason'] = '';
			}
		}

		$strings = array();
		foreach ($fields as $field => $value) {
			$value = self::$db->res($value);
			if (is_int($field)) {
				$strings[] = $value;
			} else {
				if ($value === "NOW()") {	
					$strings[] = $field .' = NOW()';
				} else if (is_null($value)) {
					$strings[] = $field .' = NULL';
				} else {
					$strings[] = $field . self::formatWhereClause($value);
				}
			}
		}

		if (empty($strings)) {
			return false;
		}
		$set = implode(', ', $strings);

		//Format query
		$query = 'UPDATE ' . $dbName . ' SET ' . $set . ' WHERE ' . $where;

		//Update element
		if (!self::$db->update($query)) {
			return false;
		}

		//Add revisions		
		if ($obj && $options['revision'] && !$lastModifiedOnly) {
			if (isset($tablecolumns['revision_id'])) {
				foreach ($records as $r) {
					$r[$modelName]->revision_id = $r[$modelName]->{$obj->primaryKey};
					unset($r[$modelName]->{$obj->primaryKey});
					if ($revisionReason) {
						if (isset($tablecolumns['revision_reason'])) {
							$r[$modelName]->revision_reason = $revisionReason;
						}
					} else {
						if (isset($r[$modelName]->revision_reason)) {
							unset($r[$modelName]->revision_reason);
						}
					}
					self::insertData($obj, self::$app->obj2array($r[$modelName]), false, ['events' => false]);
				}
			}
		}

		//Add event to database
		if (!$lastModifiedOnly) {
			self::addEvent($obj ? $obj->getName() : $dbName, $ids, self::EVENT_UPDATE);
		
			//Update counter cache
			self::$db->updateCounterCache($obj, $conditions, 'update');

			//Check options
			if ($options['foreignkey_update']) {
				//If any main foreign keys changed!
				self::$db->updateCounterCache($obj, $fields, 'update');
			}
		}

		if ($obj !== null) {
			//Call after function
			$obj->afterUpdate($conditions);
		}

		//We are OK
		return true;
	}

	//Delete from table
	public static function deleteData($dbName, $conditions = 1) {
		$obj = null;
		if (is_object($dbName)) {
			$obj = $dbName;
			$dbName = $dbName->tableName;
		}

		//Check conditions to delete first
		$ids = [];
		if (is_object($obj)) {
			$check = self::$db->selectEx($obj, [
				'type' => 'all',
				'conditions' => $conditions,
				'contain' => [],
				'createdBy' => false
			]);
			
			//There is no records for update
			if (!$check || count($check) == 0) {
				return false;
			}

			$modelName = $obj->getName();
			foreach ($check as $c) {
				$ids[] = $c[$modelName]->{$obj->primaryKey};
			}
		}

		//Get table columns
		$columns = self::$db->tables[$dbName];

		//Delete or update as deleted?
		$delete = true;
		if ($obj !== null) {
			if (isset($columns['deleted'])) {
				$delete = false;
			}
		}

		//Add special condition
		if (!$delete) {
			$conditions['deleted'] = 0;
		}

		//Check conditions
		$where = $conditions;
		if (is_array($conditions)) {
			//Check for deleted and revision_id
			if (isset($columns['deleted'])) {
				$conditions['deleted'] = 0;
			}
			if (isset($columns['revision_id'])) {
				$conditions['revision_id'] = 0;
			}

			$where = [];
			foreach ($conditions as $key => $val) {
				$partkeys = explode('.', $key);

				//We have table and column name
				if (count($partkeys) == 2) {
					$where[] = $key . self::formatWhereClause($val);
				} else {
					$where[] = $dbName . '.' . $key . self::formatWhereClause($val);
				}
			}
			$where = implode(' AND ', $where);
		}

		//Check for where to delete
		if (!$where || empty($where)) {
			$where = '1';
		}

		//Check for dependencies
		$dependant = [];
		if (is_object($obj)) {
			foreach ($obj->associations['hasMany'] as $k => $a) {
				if ($a['dependant']) {
					$dependant[$k] = $a;
				}
			}
		}

		$ids = [];
		//We have dependant elements
		if (!empty($dependant)) {
			//Get all records with this conditions for deleting
			$obj->includeCreatedBy = false;
			$records = $this->selectEx($obj, ['conditions' => $conditions, 'contain' => []]);

			//Get primary keys for all that objects
			$ids = self::$db->getIds($records, $obj->modelName . '.' . $obj->primaryKey);
		}

		$success = true;
		//Do we have to delete records from db?
		if ($delete) {
			//Delete first
			if (!self::$db->delete('DELETE FROM ' . $dbName . ' WHERE ' . $where)) {
				$success = false;
			}
		} else {
			$modified = '';
			if (isset($columns['modified_at'])) {
				$modified .= ', modified_at = "' . self::getDate() . '"';
			}
			if (isset($columns['modified_by'])) {
				$modified .= ', modified_by = "' . self::userid() . '"';
			}
			if (isset($columns['deleted_at'])) {
				$modified .= ', deleted_at = "' . self::getDate() . '"';
			}
			if (isset($columns['deleted_by'])) {
				$modified .= ', deleted_by = "' . self::userid() . '"';
			}
			if (!self::$db->update('UPDATE ' . $dbName . ' SET deleted = 1' . $modified . ' WHERE ' . $where)) {
				$success = false;
			}
		}

		//Check status
		if ($success != false) {
			//Delete dependant rows!
			if (!empty($ids) && !empty($dependant)) {
				//Delete all dependant parameters
				foreach ($dependant as $d) {
					try {
						$modelName = '\Model\\' . $d['modelName'];
						$o = new $modelName;
						$c = [$d['foreignKey'] => $ids];
						$this->deleteData($o, $c);
					} catch (\Exception $e) {

					}
				}
			}
	
			//Update counter cache
			self::$db->updateCounterCache($obj, $conditions, 'delete');
		}

		if ($success) {
			//Add delete event
			self::addEvent($obj ? $obj->getName() : $dbName, $ids, self::EVENT_DELETE);

			//Callback
			$obj->afterDelete($conditions);
		}

		return $success;
	}

	//Formats useful data for given columns matching input data
	public static function formatColumns($columns, $data) {
		$data = self::$app->obj2array($data);
		$out = [];

		//Allow by default
		if (!in_array('revision_reason', $columns)) {
			$columns[] = 'revision_reason';
		}

		foreach ($columns as $key => $col) {
			if (isset($data[$col])) {
				$out[$col] = $data[$col];
			}
		}

		return $out;
	}

	public static function formatWhereClause($elements) {
		return self::$db->formatWhereClause($elements);
	}

	public static function collection_id($collection_id = false) {
		if ($collection_id) {
			return $collection_id;
		}

		return self::$app->collection_id;
	}

	public static function toArray($data) {
		return json_decode(json_encode($data), true);
	}

	//Validates object
	public static function validate($v, &$errors) {
		//Check status
		$status = $v->validate();

		//Set errors to view
		$errors = $v->errors();
		$errors['validate_success'] = false;
		$errors['message'] = '';

		//Set errors to view
		self::$app->view()->set('errors', $errors);
		self::$app->validationErrors = $errors;

		//Return status 
		return $status;
	}

	//Filters single column from data and returns as single array
	public static function filterColumn($data, $column) {
		$ret = [];
		if (!$data) {
			return $ret;
		}

		foreach ($data as $d) {
			if (isset($d->$column)) {
				$ret[] = $d->$column;
			}
		}

		return $ret;
	}

	//Returns first element of array
	public static function getFirst($data) {
		if (!is_array($data)) {
			return $data;
		}
		
		if (isset($data[0])) {
			return $data[0];
		}

		return $data;
	}

	//Returns new object for validation
	public static function getValidationObject($dataToValidate) {
		//Create validation object
		$v = new Validator($dataToValidate);

		//Bind relation data validator
		$v->addRule('relationData', array('\Inc\Model', 'validate_relation_data'));
		$v->addRule('optional', function() {
			return true;
		});

		return $v;
	}

	//Validate relation data for many-many relations
	public static function validate_relation_data($field, $data, $params) {
		//Check array
		if (!is_array($data)) {
			return false;
		}

		//Check for parameters
		$type = false;
		if (is_array($params) && !empty($params)) {
			$type = $params[0];
		}

		//Get keys and values
		$keys = array_keys($data);
		$values = array_values($data);

		//Keys must be integers
		foreach ($keys as $k) {
			if (!is_numeric($k)) {
				return false;
			}
		}

		//Check fields
		switch ($field) {
			//Used on properties to specify which category belongs to property
			//Data: ['category_id', 'category_id2', 'category_id3']
			case 'category':
			//Used on categories to specify which property belongs to category
			//Data: ['property_id', 'property_id2', 'property_id3']
			case 'property':
			//Used on collections to specify which user belongs to collection
			//Data: ['user_id', 'user_id2', 'user_id3']
			case 'user':
			//Used on elements to specify which products and how many elements on this product are used
			//Data: ['product_id_1' => 'number_of_elements', 'product_id_2': 'number_of_elements']
			case 'product':
			//Used on products to specify which subproducts and how many subproducts on this product are used
			//Data: ['subproduct_id_1' => 'number_of_subproducts', 'subproduct_id_2': 'number_of_subproducts']
			case 'subproduct':
			//Used on products to specify which elements and how many of them on the product are used
			//Data: ['element_id_1' => 'number_of_elements', 'element_id_2': 'number_of_elements']
			case 'element':
			//Used on users to specify which collection is connected with user
			//Data: [collection_id1, collection_id2]
			case 'collection':
				if ($field != 'property') {
					foreach ($values as $v) {
						if (empty($v) || !(int)$v) {
							return false;
						}
					}
				}
				break;
			//Used on properties to specify which options belongs to property
			//Data: ['options_value1', 'options_value2', 'options_value3']
			case 'option':
				foreach ($values as $v) {
					if (!$v || empty($v)) {
						return false;
					}
				}
				break;
			default:
				return false;
		}

		//Validation has passed
		return true;
	}

	//Returns datetime format for mysql
	public static function getDate($timestamp = false) {
		if ($timestamp) {
			return date('Y-m-d H:i:s', $timestamp);
		}
		return (new \DateTime(date('c')))->format('Y-m-d H:i:s');
	}

	//Find function for all models
	public static function find($type = 'all', $options = [], $className = false) {
		//Get classname
		if (!$className) {
			$className = get_called_class();
		}

		//Make advanced search with class
		return self::$db->selectEx(new $className, array_merge($options, ['type' => $type]));
	}

	//Find function for all models
	public static function findCountByPrimaryKey($id, $className = false) {
		//Get classname
		if (!$className) {
			$className = get_called_class();
		}

		//Get className
		$className = get_called_class();
		$obj = new $className;

		//Get count
		$options = [
			'type' => 'count',
			'conditions' => [
				$obj->modelName . '.' . $obj->primaryKey => $id
			]
		];

		//Make advanced search with class
		return self::$db->selectEx($obj, $options);
	}

	//Update last modified value
	public static function updateLastModified($obj, $id) {
		$ids = array_merge([], (array)$id);

		$args = func_get_args();
		array_shift($args);
		array_shift($args);

		foreach ($args as $v) {
			if (is_array($v)) {
				foreach ($v as $a) {
					$ids[] = $a;
				}
			} else {
				$ids[] = $v;
			}
		}

		//Update
		if (!empty($ids)) {
			return self::updateData($obj, ['modified_at' => self::getDate()], [$obj->primaryKey => $ids], ['revision' => false]);
		}
		return false;
	}

	//Gets ids from array path
	public static function getIds($data, $path, $unique = true) {
		return self::$db->getIds($data, $path, $unique);
	}

	//Returns current user ID
	public static function userid() {
		return self::$app->User['User']->id;
	}

	//Returns current access group
	public static function usergroup() {
		return self::$app->User['User']->access_group;
	}

	/////////////////////////////////
	/////       CALLBACKS       /////
	/////////////////////////////////

	//$val = false: insert failed, $val > 0: id of inserted value
	public function afterInsert($val) {
		
	}

	//$val = false: update failed, $conds: list of conditions
	public function afterUpdate($conds) {
		
	}

	//$conds = false: insert failed, $conds: list of conditions
	public function afterDelete($conds) {
		
	}

	//Hash string
	public static function hash($input) {
		return self::$app->hash($input);
	}
}
