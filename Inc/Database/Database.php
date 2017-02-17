<?php

namespace Inc\Database;
use \Inc\Cache\Cache;

class StorageDatabase extends \MySQLi {
	public $queries_log = [];
	public $app, $tables;

	private $__queryDefault = [
		'type' => 'all',
		'model' => null,
		'table' => false,
		'conditions' => [],
		'joins' => [],
		'fields' => [],
		'order' => false,
		'limit' => false,
		'page' => false,
		'recursive' => 1,
		'contain' => true,
		'createdBy' => true,
		'changes' => false,
		'revision' => false,
		'virtualFields' => true,
		'debug' => false
	];

	//Constructur
	public function __construct($app, $host, $user, $password, $dbname) {
		$this->app = $app;

		//Connect to database
		parent::__construct($host, $user, $password);

		//Select database
		$this->select_db($dbname);

		//Get tables information
		$this->tables = $this->getTablesColumns();
	}

	//Select
	public function select($query) {
		$args = func_get_args();
		$res = call_user_func_array(array($this, 'query'), $args);

		//Check for error
		$this->checkErrors($query);

		//Add to query list
		$this->queries_log[] = [
			'query' => $query,
			'num_rows' => $res->num_rows,
			'file' => '',
			'line' => '',
			'class' => ''
		];

		//Information schema return value
		if (stripos($query, 'information_schema') !== false && is_object($res) && $res->num_rows != 0) {
			$ret = [];
			while ($result = $res->fetch_object()) {
				$ret[] = $result;
			}
			return $ret;
		}

		//Check for data
		if (is_object($res) && $res->num_rows != 0) {
			//Get fields from query
			$result = $res->fetch_fields();
			$fields = [];
			foreach ($result as $f) {
				$name = '';
				if ($f->table && in_array($f->name, ['created_by', 'created_at', 'modified_by', 'modified_at'])) {
					//Prefix starts with table name
					$prefix = strtolower($f->table);

					//Check for type
					if (substr($prefix, -3) == 'ies') {
						$prefix = substr($prefix, 0, strlen($prefix) - 3) . 'y';
					} else {
						$prefix = substr($prefix, 0, strlen($prefix) - 1);
					}

					//Check for prefix
					$name .= $prefix . '_' . $f->name;
				} else {
					$name = $f->name;
				}

				$fields[] = $name;
			}

			//Get results
			$ret = [];
			while ($result = $res->fetch_row()) {
				$r = new \StdClass;

				//Fill results
				for ($i = 0; $i < count($fields); $i++) {
					$r->{$fields[$i]} = stripslashes($result[$i]);
				}

				//Add to output array
				$ret[] = $r;
			}
			return $ret;
		}

		return false;
	}

	//Insert
	public function insert($query) {
		$args = func_get_args();
		$res = call_user_func_array(array($this, 'query'), $args);

		//Check for error
		$this->checkErrors($query);
		
		if ($res === true) {
			return $this->insert_id;
		}

		return $res;
	}

	//Update
	public function update($query) {
		$args = func_get_args();
		$res = call_user_func_array(array($this, 'query'), $args);

		//Check for error
		$this->checkErrors($query);

		return $res;
	}

	//Update
	public function delete($query) {
		$args = func_get_args();
		$res = call_user_func_array(array($this, 'query'), $args);

		//Check for error
		$this->checkErrors($query);

		return $res;
	}

	//Alias for real_escape_string
	public function res($val) {
		return $this->real_escape_string($val);
	}

	//Gets all tables and its columns from database
	public function getTablesColumns() {
		//Check cache first
		$tables = Cache::read($this->app, 'database_tables');
		if ($tables) {
			return $tables;
		}

		$results = $this->select('SELECT * FROM information_schema.columns
			WHERE table_schema = "' . $this->app->config['database']['db'] . '"
			ORDER BY table_name, ordinal_position');

		$tables = [];
		if ($results) {
			foreach ($results as $result) {
				if (!isset($tables[$result->TABLE_NAME])) {
					$tables[$result->TABLE_NAME] = [];
				}

				$tables[$result->TABLE_NAME][$result->COLUMN_NAME] = $result;
			}
		}

		//Write cache for next time
		Cache::write($this->app, 'database_tables', $tables);

		//Return object
		return $tables;
	}

	public function checkErrors($query) {
		if ($this->errno) {
			pr('MYSQL_ERROR: ' . $this->errno . '; ' . $this->error);
			pr($query);

			//Get call stack
			$trace = debug_backtrace();

			foreach ($trace as $t) {
				print $t['line'] . ': ' . $t['file'] . '<br />';
			}

			exit;
		}
	}

	//Format WHERE clause
	public function formatWhereClause($elements, $key = false, $params = []) {
		if (is_numeric($key)) {
			if (!is_array($elements)) {
				return $elements;
			}
		}

		//Set quotes by default
		$quotes = '"';

		//Check if all elements are numeric
		if (is_array($elements)) {
			$allNumeric = true;
			$anyArray = false;
			foreach ($elements as $k => $v) {
				if (!is_numeric($k)) {
					$allNumeric = false;
				}
				if (is_array($v)) {
					$anyArray = true;
				}
			}
			if (!$allNumeric || $anyArray) {
				return $this->formatConditions($params, $elements, $key);;
			}
			$quotes = '';
			foreach ($elements as $k => $e) {
				if (!is_numeric($e)) {
					$quotes = '"';
					break;
				}
			} 
		} else {
			if (is_numeric($elements)) {
				$quotes = '';
			}
		}

		//Check for array of data
		if (is_array($elements)) {
			return ' IN (' . $quotes . implode($quotes . ', ' . $quotes, $elements) . $quotes . ')';
		}

		//Check for string in key to compare
		if (is_string($key)) {
			//If we have a space which is not on the beginning of key
			//Then we assume, user has own operator like >, <, <=, etc, etc..
			if (stripos(trim($key), ' ') > 0) {
				return ' ' . $quotes . $this->res($elements) . $quotes; 
			}
		}

		//Single element
		return ' = ' . $quotes . $this->res($elements) . $quotes; 
	}

	//Update counter cache if exists
	public function updateCounterCache($obj, $fields, $insert = false) {
		if (!is_object($obj)) {
			return false;
		}

		//Check for object
		if (!isset($obj) || empty($obj->associations['belongsTo'])) {
			return true;
		}

		//Check belongs to associations
		$associations = $obj->associations['belongsTo'];

		//In case insert was made, grab element from database
		//$fields has insert ID
		if ($insert == 'insert') {
			$options = [
				'type' => 'first',
				'conditions' => [
					$obj->modelName . '.' . $obj->primaryKey => $fields
				],
				'contain' => []
			];
			
			//Get element
			$element = $this->selectEx($obj, $options);

			//Check values
			if (!$element || !isset($element[$obj->modelName])) {
				return false;
			}

			//Format fields, convert to array if not already
			$fields = json_decode(json_encode($element[$obj->modelName]), true);
		}

		//Remove revision
		$fields[$obj->getName() . '.revision_id'] = 0;

		//Go through belongsTo associations
		foreach ($associations as $modelName => $opts) {
			//Check for data in update array
			if (isset($opts['counterCache'])) {
				//Check for record fields
				if (!isset($fields[$opts['foreignKey']])) {
					$options = [
						'type' => 'all',
						'conditions' => $fields,
						'contain' => []
					];
					
					//Get element
					$elements = $this->selectEx($obj, $options);

					//Check values
					if (!$elements || !isset($elements[0][$obj->modelName])) {
						continue;
					}

					//Format fields, convert to array if not already
					$fields = [];

					//Format fields
					foreach ($elements as $element) {
						foreach ($element[$obj->modelName] as $k => $v) {
							if (!isset($fields[$k])) {
								$fields[$k] = [];
							}
							if (!in_array($v, $fields[$k])) {
								$fields[$k][] = $v;
							}
						}
					}
				}
				if (!isset($fields[$opts['foreignKey']])) {
					continue;
				}

				//Create association object
				$className = '\\Model\\' . $modelName;
				if (!class_exists($className)) {
					continue;
				}
				$assocObj = new $className;

				//Go through all counter caches
				foreach ($opts['counterCache'] as $cacheFieldName => $conditions) {
					//Check if field for counter cache exists
					if (!isset($this->tables[$assocObj->tableName][$cacheFieldName])) {
						//Check if field exists
						continue;
					}

					//Get foreign key field name
					$fieldName = $opts['foreignKey'];

					//Create array if needed
					if (!is_array($fields)) {
						$fields = [$fieldName => $fields];
					}

					//Format where clause
					foreach ((array)$fields[$fieldName] as $fieldValue) {
						//Print options
						$options = [
							'type' => 'count', 
							'conditions' => array_merge((array)$conditions, [
								$obj->modelName . '.' . $fieldName => $fieldValue
							])
						];

						//Get count values
						$result = $this->selectEx($obj, $options);

						//Check if table exists
						if (isset($this->tables[$assocObj->tableName])) {
							//Lets update associations
							$query = 'UPDATE ' . $assocObj->tableName . ' SET ' . $cacheFieldName . ' = ' . $result . ' WHERE ' . $assocObj->primaryKey . ' = ' . $fieldValue;

							//Issue update command
							$this->update($query);
						}
					}
				}
			}
		}

		//OK
		return true;
	}

//////////////////////////////////////////// NEW TEST ////////////////////////////////////////////

	//Advanced search
	public function selectEx($model, $query, $recursive = 0) {
		//Check for array
		if (!is_array($query)) {
			return false;
		}

		//Table
		$tokens = explode('\\', get_class($model));
		$currentModelName = array_pop($tokens);

		//Make query parameters
		$params = array_merge($this->__queryDefault, $query);

		//Check params
		if ($recursive > $params['recursive']) {
			return false;
		}

		//Set table
		if (!$params['table']) {
			$params['table'] = $model->tableName . ' AS ' . $currentModelName;
			$params['model'] = $currentModelName;
		}
		$params['type'] = strtolower($params['type']);

		//Check for type
		if ($params['type'] == 'first') {
			$params['limit'] = 1;
			$params['page'] = false;
		}

		//Check for virtual fields
		if (is_array($model->virtualFields) && $params['type'] != 'count' && $params['virtualFields']) {
			foreach ($model->virtualFields as $result => $virtual) {
				$params['fields'][] = $virtual . ' AS ' . $result;
			}
		}

		//Check for CreatedBy object
		if ($model->includeCreatedBy && ($params['contain'] != 1)) {
			if (!is_array($params['contain'])) {
				$params['contain'] = [];
			}
			$params['contain'][] = 'CreatedBy';
			$params['contain'][] = 'ModifiedBy';
		}

		//Format joins
		foreach ($model->associations as $type => $models) {
			//Add hasOne and belongsTo associations to query string
			if ($type === 'hasOne' || $type === 'belongsTo' && is_array($models)) {
				foreach ($models as $modelName => $opts) {
					if (isset($opts['changes']) && $opts['changes'] != $params['changes']) {
						continue;
					}
					//Check for created by model
					if ($modelName == 'CreatedBy' && $params['createdBy'] !== true) {
						continue;
					}

					//Check if model is used for data retrieve
					if (is_array($params['contain']) && !in_array($modelName, $params['contain'])) {
						continue;
					}

					$clsName = '\Model\\' . $opts['modelName'];
					$obj = new $clsName($this->app);
					$j = [
						'table' => $obj->tableName . ' AS ' . $modelName,
						'on' => $currentModelName . '.' . strtolower($opts['foreignKey']) . ' = ' . $modelName . '.' . $obj->primaryKey,
						'type' => $opts['joinType'],
						'fields' => $opts['fields']
					];
					$add = true;
					foreach ($params['joins'] as $join) {
						if (
							(isset($join['table']) && $join['table'] == $j['table']) ||
							(isset($join['model']) && $join['model'] == $modelName)
						) {
							$add = false;
							break;
						}
					}
					if ($add) {
						$params['joins'][] = $j;
					}
					$params['conditions'] = array_merge($params['conditions'], $opts['conditions']);
				}
			}
		}

		//In case of count search
		if (strtolower($params['type']) == 'count') {
			return $this->__findCount($model, $params);
		}

		//Execute data
		$records = $this->__executeSelect($params);

		//Check values
		if (!$records) {
			if ($params['type'] == 'all') {
				return [];
			}

			return false;
		}

		//Grab ID values from main data to get others
		$ids = $this->getIds($records, $currentModelName . '.' . $model->primaryKey);

		//Check if we have manyToMany relations
		if (isset($model->associations['manyToMany']) && !empty($model->associations['manyToMany'])) {
			//Get associations
			$manyToMany = $model->associations['manyToMany'];

			//Go through entire many to many relation
			foreach ($manyToMany as $modelName => $opts) {
				if (isset($opts['changes']) && $opts['changes'] != $params['changes']) {
					continue;
				}
				//Check if model is used for data retrieve
				if (is_array($params['contain']) && !in_array($modelName, $params['contain'])) {
					continue;
				}

				//Create association class instance
				$className = '\Model\\' . $opts['modelName'];
				$obj = new $className;

				//Create relation class instance
				$className = '\Model\\' . $opts['joinModel']; 
				$relObj = new $className;

				//Create conditions
				$conditions = array_merge($opts['conditions'], [
					$opts['joinModel'] . '.' . $opts['foreignKey'] => $ids
				]);

				//If not retrieving changes
				if (!$params['changes']) {
					$conditions[$modelName . '.deleted'] = 0;
					$conditions[$opts['joinModel'] . '.deleted'] = 0;
				}

				//Check for revision select
				if (!$params['revision']) {
					//Add other important stuff
					$conditions[$modelName . '.revision_id'] = 0;
					$conditions[$opts['joinModel'] . '.revision_id'] = 0;
				}

				//Create query
				$assocQuery = array_merge($this->__queryDefault, [
					'type' => 'all',
					'table' => $obj->tableName . ' AS ' . $modelName, //$relObj->tableName . ' AS ' . $opts['joinModel'],
					'conditions' => $conditions,
					'joins' => [[
						'type' => 'INNER',
						'table' => $relObj->tableName . ' AS ' . $opts['joinModel'],
						'on' => $opts['joinModel'] . '.' . $opts['associationForeignKey'] . ' = ' . $modelName . '.' . $obj->primaryKey
					]],
					'recursive' => $params['recursive'],
					'contain' => $params['contain'],
					'changes' => $params['changes']
				]);

				//Get values
				//$values = $this->__executeSelect($assocQuery);
				$values = $this->selectEx($obj, $assocQuery, $recursive + 1);

				//Add values
				foreach ($records as &$record) {
					//Add association
					$record[$modelName] = [];
					
					//Check for values
					if ($values) {
						//Check all values
						foreach ($values as $v) {
							//First ID is from association model
							$firstID = $v[$opts['joinModel']]->{$opts['foreignKey']};

							//Second ID is from record main model
							$secondID = $record[$currentModelName]->{$model->primaryKey};

							//If values matches
							if ($firstID == $secondID) {
								//Add to output
								$r = $v[$modelName];
								$r->{$opts['joinModel']} = $v[$opts['joinModel']];
								$record[$modelName][] = $r;
							}
						}
					}
				}
			}
		}

		//Check for hasMany relations
		if (isset($model->associations['hasMany']) && !empty($model->associations['hasMany'])) {
			//Get associations
			$hasMany = $model->associations['hasMany'];

			//Go through relations
			foreach ($hasMany as $modelName => $opts) {
				if (isset($opts['changes']) && $opts['changes'] != $params['changes']) {
					continue;
				}
				//Check if model is used for data retrieve
				if (is_array($params['contain']) && !in_array($modelName, $params['contain'])) {
					continue;
				}
				
				//Create association class instance
				$className = '\Model\\' . $opts['modelName'];
				$obj = new $className;

				$conditions = array_merge($opts['conditions'], [$modelName . '.' . $opts['foreignKey'] => $ids]);
				if (!$params['changes']) {
					$conditions[$modelName . '.deleted'] = 0;
				}
				if (!$params['revision']) {
					$conditions[$modelName . '.revision_id'] = 0;
				}

				//Create query
				$query = array_merge($this->__queryDefault, [
					'table' => $obj->tableName . ' AS ' . $modelName,
					'conditions' => $conditions,
					'limit' => $opts['limit'],
					'order' => $opts['order'],
					'recursive' => $params['recursive'],
					'contain' => $params['contain'],
					'changes' => $params['changes']
				]);

				//Get values
				$values = $this->selectEx($obj, $query, $recursive + 1);

				//Link values
				foreach ($records as &$record) {
					//Add association
					$record[$modelName] = [];

					//Check for values
					if ($values) {
						//Check all values
						foreach ($values as $v) {
							//First ID is from record
							$firstID = $record[$currentModelName]->{$model->primaryKey};

							//Second ID is from association
							$secondID = $v[$modelName]->{$opts['foreignKey']};

							//Check if the same
							if ($firstID == $secondID) {
								if ($recursive == 0) {
									$record[$modelName][] = $v[$modelName];
								} else {
									$record[$currentModelName]->{$modelName}[] = $v[$modelName];
								}
							}
						}
					}
				}
			}
		}

		//Return first value		
		if (is_array($records) && strtolower($params['type']) == 'first') {
			$r = array_shift($records);
			if ($this->app->request()->get('debug', false) !== false) {
				var_dump($r);
			}
			return $r;
		}
		//Threaded response
		if (is_array($records) && strtolower($params['type']) == 'thread') {
			$records = $this->__buildTree($model, $params, $records);
			return $records;
		}

		//Output records
		if ($this->app->request()->get('debug', false) !== false) {
			var_dump($records);
		}

		return $records;
	}

	//Format conditions for select		
	public function formatConditions($params, $conditions, $mid = 'AND', $nesting = 0) {
		//Check input
		if (!$conditions || !is_array($conditions) || !count($conditions)) {
			return '1 = 1';
		}

		//Output data
		$out = [];

		//Go through conditions
		foreach ($conditions as $key => $value) {
			//Check for OR or AND statements
			if (in_array(strtoupper($key), ['AND', 'OR', 'NOT'])) {
				$out[] = implode(' ' . strtoupper(trim($key)) . ' ', [$this->formatConditions($params, $value, strtoupper($key), $nesting + 1)]);
			} else {
				if (is_array($value) && count($value) == 0) {
					continue;
				}
				if (!is_numeric($key) && strpos($key, '.') === false) {
					$exp = explode('.', $key);
					if (count($exp) == 1 && isset($params['model'])) {
						$key = $params['model'] . '.' . $key;
					}
				}
				$out[] = trim(is_numeric($key) ? '' : $key) . $this->formatWhereClause($value, trim($key), $params);
			}
		}

		//If output is only one field in array
		if (count($out) == 1) {
			return $out[0];
		}

		//If we are in first nesting state
		if ($nesting == 0) {
			return implode(' ' . $mid . ' ', $out);
		}

		//Other possibilities
		return '(' . implode(' ' . $mid . ' ', $out) . ')';
	}

	//Format order for select
	private function __formatOrder($order) {
		//Check input
		if (!$order || is_array($order)) {
			return '';
		}

		//Order
		return 'ORDER BY ' . $order;
	}

	//Format order for select
	private function __formatLimit($limit, $page = false) {
		//Check input
		if (!$limit || is_array($limit) || is_array($page)) {
			return '';
		}

		//Order
		if ($page !== false) {
			return 'LIMIT ' . ($limit * ($page - 1)) . ', ' . $limit;
		}
		return 'LIMIT ' . $limit;
	}

	//Executes query and returns data
	private function __executeSelect($query, $fields = []) {
		//Check for input
		if (is_array($query)) {
			$params = $query;
			$query = $this->__formatQuery($query);
		}

		//Make query get results
		$res = parent::query(trim($query));

		//Check query for errors
		$this->checkErrors($query);

		//Debug backtrace
		$trace = debug_backtrace();

		//Add to query list
		$this->queries_log[] = [
			'query' => $query,
			'num_rows' => $res->num_rows,
			'file' => str_replace($this->app->config['main_path'], '', $trace[2]['file']),
			'line' => $trace[2]['line'],
			'class' => $trace[2]['class']
		];

		//Information schema return value
		if (stripos($query, 'information_schema') !== false && is_object($res) && $res->num_rows != 0) {
			$ret = [];
			while ($result = $res->fetch_object()) {
				$ret[] = $result;
			}
			return $ret;
		}

		//Check for data
		if (is_object($res) && $res->num_rows != 0) {
			//Get fields from query
			$result = $res->fetch_fields();

			$fields = [];
			$i = 0;
			foreach ($result as $f) {
				//If virtual field, add to default table
				if (empty($f->table)) {
					$tokens = explode(' AS ', $params['table']);
					$f->table = array_pop($tokens);
				}

				//Check for field if exists
				if (!isset($fields[$f->table])) {
					$fields[$f->table] = [];
				}

				$fields[$f->table][$f->name] = $f->type;
			}

			//Get results
			$ret = [];
			while ($result = $res->fetch_row()) {
				$r = [];

				//Go through tables
				$k = 0;
				foreach ($fields as $table => $f) {
					if (!isset($r[$table])) {
						//Create object from table
						$r[$table] = new \StdClass;

						//Fill data
						foreach ($f as $j => $type) {

							$val = $result[$k++];
							switch ($type) {
								case 0:
								case 1:
								case 2:
								case 3:
									$val = intval($val); break;
								case 4:
									$val = floatval($val); break;
								default: 
									//String
									$val = stripslashes(str_replace('\r\n', PHP_EOL, $val));
								break;
							}
							$r[$table]->{$j} = $val;
						}
					}
				}

				//Add to output array
				$ret[] = $r;
			}

			//Check for return data
			if (!empty($ret)) {
				//Return output
				return $ret;
			}
		}

		return false;
	}

	//Returns a list of ID values for specific data and path
	public function getIds($data, $path, $unique = true) {
		$ret = [];

		//Check input values
		if (!is_array($data)) {
			return [];
		}

		//Get path
		$path = explode('.', $path);

		//Go through data
		foreach ($data as $d) {
			//Set starting point
			$tmp = $d;

			//Check path
			for ($i = 0; $i < count($path); $i++) {
				$p = $path[$i];
				$isLast = $i == count($path) - 1;

				//Check input
				if (is_array($tmp) && isset($tmp[$p])) {
					if ($isLast) {
						if ($unique) {
							if (!in_array($tmp[$p], $ret)) {
								$ret[] = $tmp[$p];
							}
						} else {
							$ret[] = $tmp[$p];
						}
					}
					$tmp = $tmp[$p];
				} else if (is_object($tmp) && isset($tmp->$p)) {
					if ($isLast) {
						if ($unique) {
							if (!in_array($tmp->$p, $ret)) {
								$ret[] = $tmp->$p;
							}
						} else {
							$ret[] = $tmp->$p;
						}
					}
					$tmp = $tmp->$p;
				} else {
					break;
				}
			}
		}

		//Return values
		return $ret;
	}

	//Returns query string from parameters
	private function __formatQuery($params) {
		//Check if is count type to search
		$isCount = $params['type'] == 'count';

		//Get fields
		if (!$isCount) {
			$fields = array_merge($params['fields'], $this->getFields($params['table']));
		} else {
			$fields = ['COUNT(*) AS count'];
		}

		//Make query parameters
		$params = array_merge($this->__queryDefault, $params);

		//Check for changes
		if ($params['changes']) {
			foreach ($params['conditions'] as $key => $value) {
				if (stripos($key, 'deleted') !== false) {
					unset($params['conditions'][$key]);
				}
			}
		}

		//Format query
		if ($isCount) {
			$query = 'SELECT {FIELDS} FROM {TABLE} {JOINS} {WHERE}';
		} else {
			$query = 'SELECT {FIELDS} FROM {TABLE} {JOINS} {WHERE} {ORDER} {LIMIT}';
		}

		//Format table
		$query = str_replace('{TABLE}', $params['table'], $query);

		//Joins
		if (is_array($params['joins']) && !empty($params['joins'])) {
			$joins = [];
			foreach ($params['joins'] as $j) {
				//Check for table
				if (!isset($j['table']) && isset($j['model'])) {
					if (isset($j['className'])) {
						$class = $j['className'];
					} else {
						$class = $j['model'];
					}
					//Create object
					$className = '\Model\\' . $class;
					$obj = new $className;
					$j['table'] = $obj->tableName . ' AS ';
					if (isset($j['modelAlias'])) {
						$j['table'] .= $j['modelAlias'];
					} else {
						$j['table'] .= $j['model'];
					}
				}

				//Add conditions
				if (isset($j['conditions'])) {
					$params['conditions'] = array_merge($params['conditions'], $j['conditions']);
				}

				//Make join
				$joins[] = $j['type'] . ' JOIN ' . $j['table'] . ' ON ' . $j['on'];

				//Get fields from JOIN
				if (!$isCount) {
					if (!isset($j['fields']) || !$j['fields'] || empty($j['fields'])) {
						//Include all fields
						$fields = array_merge($fields, $this->getFields($j['table']));
					} else {
						//Only selected fields
						$fields = array_merge($fields, $j['fields']);
					}
				}
			}

			//Replace
			$query = str_replace('{JOINS}', implode(' ', $joins), $query);
		} else {
			$query = str_replace('{JOINS}', '', $query);
		}

		//Check params
		if (is_array($params['conditions'])) {
			//$params['conditions'] = $this->__checkConditions($params, $params['conditions']);
		}

		//Format where clause
		$query = str_replace('{WHERE}', ' WHERE ' . $this->formatConditions($params, $params['conditions']), $query);

		//Format order by
		$query = str_replace('{ORDER}', $this->__formatOrder($params['order']), $query);

		//Format order by
		$query = str_replace('{LIMIT}', $this->__formatLimit($params['limit'], $params['page']), $query);

		//Remove unnecessary spaces
		$query = str_replace(['    ', '   ', '  '], ' ', trim($query));

		//Format fields
		$query = str_replace('{FIELDS}', implode(', ', $fields), $query);

		if ($params['debug']) {
			pr($query); exit;
		}

		//Return query format
		return $query;
	}

	//Find count
	private function __findCount($model, $params) {
		//Make search for COUNT
		$resp = $this->__executeSelect($params, []);

		//Return first element
		$tokens = explode(' AS ', $params['table']);
		return $resp[0][array_pop($tokens)]->count;
	}

	//Create tree from response
	private function __buildTree($model, $params, $elements, $parentId = 0) {
	    $branch = [];

	    foreach ($elements as $element) {
	        if ($element[$params['model']]->parent_id == $parentId) {
	            $element['children'] = $this->__buildTree($model, $params, $elements, $element[$params['model']]->id);
	            $branch[] = $element;
	        }
	    }

	    return $branch;
	}

	//Returns array of fields for table with alias if exists
	//$table input can be "tableName AS TableAlias" or "tableName"
	public function getFields($table) {
		$fields = [];

		//Get table name
		$table = explode(' AS ', $table);

		//Table name is always first value
		$tableName = $table[0];
		
		//Alias is always LAST value
		$alias = array_pop($table);

		return [$alias . '.*'];

		//Add to array
		foreach ($this->tables[$tableName] as $fieldName => $fieldOptions) {
			$fields[] = $alias . '.' . $fieldName;
		}

		//Return value
		return $fields;
	}
}
