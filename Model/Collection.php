<?php

namespace Model;

use \Inc\Model;
use \Model\UserCollection;

/**
 * A coolection of functions that deal with Blind management
 * @package default
 */
class Collection extends Model {
	//Collection nae
	public $tableName = 'collections';
	
	//Model associations
	public $associations = [
		'manyToMany' => [
			'User' => [
				'joinModel' => 'UserCollection',
				'foreignKey' => 'collection_id',
				'associationForeignKey' => 'user_id',
				'conditions' => [
					'User.deleted' => 0
				],
				'changes' => false
			]
		],
		'hasMany' => [
			'Element' => [
				'foreignKey' => 'collection_id',
				'conditions' => [
					'Element.deleted' => 0
				],
				'changes' => false
			],
			'Category' => [
				'foreignKey' => 'collection_id',
				'conditions' => [
					'Category.deleted' => 0
				],
				'changes' => false
			],
			'Property' => [
				'foreignKey' => 'collection_id',
				'conditions' => [
					'Property.deleted' => 0
				],
				'changes' => false
			],
			'Product' => [
				'foreignKey' => 'collection_id',
				'conditions' => [
					'Product.deleted' => 0
				],
				'changes' => false
			],
			'Elementorder' => [
				'foreignKey' => 'collection_id',
				'conditions' => [
					'Elementorder.deleted' => 0
				],
				'changes' => false
			],
			'UserCollection' => [
				'foreignKey' => 'collection_id',
				'changes' => true
			]
		]
	];

	//Virtual fields
	public $virtualFields = [
		'users_count' => '(SELECT COUNT(*) FROM users_collections AS UserCollection LEFT JOIN users AS User ON User.id = UserCollection.user_id WHERE Collection.id = UserCollection.collection_id AND User.deleted = 0 AND User.revision_id = 0)',
		//'categories_count' => '(SELECT COUNT(id) FROM categories AS Category WHERE Collection.id = Category.collection_id AND Category.deleted = 0)',
		//'elements_count' => '(SELECT COUNT(id) FROM elements AS Element WHERE Collection.id = Element.collection_id AND Element.deleted = 0)',
		//'properties_count' => '(SELECT COUNT(id) FROM properties AS Property WHERE Collection.id = Property.collection_id AND Property.deleted = 0)',
		//'products_count' => '(SELECT COUNT(id) FROM products AS Product WHERE Collection.id = Product.collection_id AND Product.deleted = 0)',
		//'elementorders_count' => '(SELECT COUNT(id) FROM elementorders AS Elementorder WHERE Collection.id = Elementorder.collection_id AND Elementorder.deleted = 0)',

		//New in last 7 days
		'users_count_new' => '(SELECT COUNT(*) FROM users_collections AS UserCollection LEFT JOIN users AS User ON User.id = UserCollection.user_id WHERE Collection.id = UserCollection.collection_id AND User.deleted = 0 AND User.revision_id = 0 AND UserCollection.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY))',
		'categories_count_new' => '(SELECT COUNT(id) FROM categories AS Category WHERE Collection.id = Category.collection_id AND Category.deleted = 0 AND Category.revision_id = 0 AND Category.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY))',
		'elements_count_new' => '(SELECT COUNT(Element.id) FROM elements AS Element WHERE Collection.id = Element.collection_id AND Element.deleted = 0 AND Element.revision_id = 0 AND Element.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY))',
		'properties_count_new' => '(SELECT COUNT(Property.id) FROM properties AS Property WHERE Collection.id = Property.collection_id AND Property.deleted = 0 AND Property.revision_id = 0 AND Property.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY))',
		'products_count_new' => '(SELECT COUNT(Product.id) FROM products AS Product WHERE Collection.id = Product.collection_id AND Product.deleted = 0 AND Product.revision_id = 0 AND Product.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY))',
		'elementorders_count_new' => '(SELECT COUNT(Elementorder.id) FROM elementorders AS Elementorder WHERE Collection.id = Elementorder.collection_id AND Elementorder.deleted = 0 AND Elementorder.revision_id = 0 AND Elementorder.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY))',

		//Deleted in last 7 days
		'users_count_deleted' => '(SELECT COUNT(*) FROM users_collections AS UserCollection LEFT JOIN users AS User ON User.id = UserCollection.user_id WHERE Collection.id = UserCollection.collection_id AND User.deleted = 1 AND User.revision_id = 0 AND UserCollection.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY))',
		'categories_count_deleted' => '(SELECT COUNT(*) FROM categories AS Category WHERE Collection.id = Category.collection_id AND Category.deleted = 1 AND Category.revision_id = 0 AND Category.deleted_at > DATE_SUB(NOW(), INTERVAL 7 DAY))',
		'elements_count_deleted' => '(SELECT COUNT(Element.id) FROM elements AS Element WHERE Collection.id = Element.collection_id AND Element.deleted = 1 AND Element.revision_id = 0 AND Element.deleted_at > DATE_SUB(NOW(), INTERVAL 7 DAY))',
		'properties_count_deleted' => '(SELECT COUNT(Property.id) FROM properties AS Property WHERE Collection.id = Property.collection_id AND Property.deleted = 1 AND Property.revision_id = 0 AND Property.deleted_at > DATE_SUB(NOW(), INTERVAL 7 DAY))',
		'products_count_deleted' => '(SELECT COUNT(Product.id) FROM products AS Product WHERE Collection.id = Product.collection_id AND Product.deleted = 1 AND Product.revision_id = 0 AND Product.deleted_at > DATE_SUB(NOW(), INTERVAL 7 DAY))',
		'elementorders_count_deleted' => '(SELECT COUNT(Elementorder.id) FROM elementorders AS Elementorder WHERE Collection.id = Elementorder.collection_id AND Elementorder.deleted = 1 AND Elementorder.revision_id = 0 AND Elementorder.deleted_at > DATE_SUB(NOW(), INTERVAL 7 DAY))',
	];

	//Validation errors
	private static $__validationErrors = [];
	//Columns for SQL operations
	private static $__tableColumns = ['name', 'description'];

	//List all collections
	public static function getCollections($collections = null, $options = []) {
		//Read
		$options = array_merge([
			'type' => 'all',
			'order' => 'Collection.name ASC',
			'conditions' => [],
			'contain' => [],
			'joins' => [],
			'changes' => false,
			'revision' => false
		], $options);
		if (!$options['revision']) {
			$options['conditions']['Collection.revision_id'] = 0;
		}
		if (!$options['changes']) {
			$options['conditions']['Collection.deleted'] = 0;
		}

		//Add conditions if needed
		if ($collections) {
			$options['conditions']['Collection.id'] = $collections;
		}

		//Check for user
		if (!parent::$app->isAdmin()) {
			//Make a join
			$options['joins'][] = [
				'model' => 'UserCollection',
				'on' => 'UserCollection.collection_id = Collection.id',
				'type' => 'INNER'
			];

			//Additional conditions
			$options['conditions']['UserCollection.user_id'] = parent::userid();
		}

		//Get elements from database
		return parent::$app->db->selectEx(new self(), $options);
	}

	//Get collection by given ID
	public static function getCollection($collection_id) {
		return self::getCollections($collection_id, ['type' => 'first']);
	}

	//Insert new data
	public static function insert($data) {
		if (!self::validateInsert($data)) {
			return false;
		}

		//Try to insert new collection
		$insID = parent::insertData(new self(), parent::formatColumns(self::$__tableColumns, $data));

		//If it was successfull, update users belonging to this collection
		if ($insID && isset($data['user'])) {
			//Update relation
			UserCollection::updateCollection($insID, $data['user'], false);
		}

		//Check for user
		if ($insID && (!isset($data['user']) || !in_array(parent::userid(), $data['user']))) {
			//Add user to collection
			UserCollection::connectUserCollection(parent::userid(), $insID);
		}

		return $insID;
	}

	//Update collection
	public static function update($collection_id, $data) {
		if (!self::validateUpdate($data) || !Collection::findCountByPrimaryKey($collection_id)) {
			return false;
		}
		
		//Try to update collection
		$success = parent::updateData(new self(), parent::formatColumns(self::$__tableColumns, $data), ['id' => $collection_id]);

		//If it was successfull, update users belonging to this collection
		if ($success && isset($data['user'])) {
			//Add yourself
			if (!in_array(parent::userid(), $data['user'])) {
				$data['user'][] = parent::userid();
			}
			
			//Update relation
			UserCollection::updateCollection($collection_id, $data['user'], true);
		}

		return $success;
	}

	//Delete collection with given ID
	public static function delete($collection_id, $link = false) {
		$params = [
			'type' => 'count',
			'conditions' => [
				'Collection.id' => $collection_id
			]
		];

		//Check group
		if (!parent::$app->isAdmin() || $link != false) {
			$params['conditions']['Collection.created_by'] = parent::userid();
		}

		//Get count
		$cnt = parent::$db->selectEx(new self(), $params);


		//User wants to remove link between him and collection
		if ($cnt == 0) {
			//Check if collection exists
			$cnt = parent::$db->selectEx(new UserCollection(), [
				'type' => 'count',
				'conditions' => [
					'UserCollection.user_id' => parent::userid(),
					'UserCollection.collection_id' => $collection_id
				]
			]);

			if ($cnt > 0) {
				//Delete link only
				return parent::deleteData(new UserCollection(), ['collection_id' => $collection_id, 'user_id' => parent::userid()]);
			}

			//Delete was not successful
			return false;
		}

		//Delete collection
		return parent::deleteData(new self(), ['id' => $collection_id]);
	}

	//Validate for insert operation
	public static function validateInsert($data) {
		$v = parent::getValidationObject($data);

		//Set rules
		$v->rule('required', 'name')->message(__('Name is required!'))
		  ->rule('lengthMin', 'name', 1)->message(__('Name cannot be empty!'))
		  ->rule('relationData', 'user', 'int')->message(__('Incorrect data format for collection users!'));

		//Validate
		return parent::validate($v, self::$__validationErrors);
	}

	//Validate for insert operation
	public static function validateUpdate($data) {
		$v = parent::getValidationObject($data);

		//Set rules
		$v->rule('optional', 'name')
		  ->rule('lengthMin', 'name', 1)->message(__('Name cannot be empty!'))
		  ->rule('relationData', 'user', 'int')->message(__('Incorrect data format for collection users!'));

		//Validate
		return parent::validate($v, self::$__validationErrors);
	}

	//Get validation errors
	public static function getValidationErrors() {
		return self::$__validationErrors;
	}
}
