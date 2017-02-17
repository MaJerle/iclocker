<?php

namespace Model;

use \Inc\Model;

/**
 * A coolection of functions that deal with Blind management
 * @package default
 */
class Category extends Model {
	//Set table name
	public $tableName = 'categories';

	//Format associations
	public $associations = [
		'belongsTo' => [
			'Collection' => [
				'foreignKey' => 'collection_id',
				'conditions' => [
					'Collection.deleted' => 0
				],
				'counterCache' => [
					'categories_count' => [
						'Category.deleted' => 0,
						'Category.revision_id' => 0
					]
				],
				'changes' => false
			]
		],
		'hasMany' => [
			'Element' => [
				'foreignKey' => 'category_id',
				'conditions' => [
					'Element.deleted' => 0
				],
				'changes' => false,
				'dependant' => true,
			],
			'CategoryProperty' => [
				'foreignKey' => 'category_id',
				'changes' => true
			]
		],
		'manyToMany' => [
			'Property' => [
				'joinModel' => 'CategoryProperty',
				'foreignKey' => 'category_id',
				'associationForeignKey' => 'property_id',
				'conditions' => [],
				'changes' => false
			]
		]
	];

	//Virtual fields
	public $virtualFields = [
		//'elements_count' => '(SELECT COUNT(*) FROM elements AS Element WHERE Element.category_id = Category.id AND Element.deleted = 0)'
	];

	//Validation errors
	private static $__validationErrors = [];
	//Columns for SQL operations
	private static $__tableColumns = ['name', 'description', 'collection_id'];

	//Get a list of all categories and number of elements in each category
	public static function getCategories($category_id = null, $collection_id = null, $options = []) {
		$options = array_merge([
			'type' => 'all',
			'conditions' => [],
			'order' => 'Category.name',
			'contain' => [],
			'changes' => false,
			'revision' => false
		], $options);
		if (!$options['revision']) {
			$options['conditions']['Category.revision_id'] = 0;
		}
		if (!$options['changes']) {
			$options['conditions']['Category.deleted'] = 0;
		}

		//Get collection ID
		$collection_id = parent::collection_id($collection_id);

		//Check for conditions
		if ($category_id) {
			$options['conditions']['Category.id'] = $category_id;
		}
		if ($collection_id) {
			$options['conditions']['Category.collection_id'] = $collection_id;
		}

		//Get categores
		return parent::$db->selectEx(new self(), $options);
	}

	//Get a list of all categories, number of elements and all properties for each category
	public static function getCategoriesWithProperties($category_id = null, $collection_id = null, $options = []) {
		return self::getCategories($category_id, $collection_id, array_merge([
			'contain' => ['Property', 'Propertychoice']
		], $options));
	}

	//Gets data for single category ID
	public static function getCategory($category_id) {
		return parent::getFirst(self::getCategories($category_id));
	}

	//Gets data for single category ID
	public static function getCategoryWithProperties($category_id) {
		return parent::getFirst(self::getCategoriesWithProperties($category_id));
	}

	//Insert new category to database
	public static function insert($data = array()) {
		//Set collection ID
		$data = array_merge($data, ['collection_id' => parent::collection_id()]);

		//validate for insert
		if (!self::validateInsert($data)) {
			return false;
		}

		//Try to insert
		$insID = parent::insertData(new self(), parent::formatColumns(self::$__tableColumns, $data));

		//Try to update relation table
		if ($insID && isset($data['property'])) {
			//Update relation table
			CategoryProperty::updateCategory($insID, $data['property'], false);
		}

		return $insID;
	}

	//Update single record in database
	public static function update($category_id, $data) {
		//Validate object
		if (!self::validateUpdate($data)) {
			return false;
		}

		//Try to update
		$success = parent::updateData(new self(), parent::formatColumns(self::$__tableColumns, array_merge($data, ['collection_id' => parent::collection_id()])), ['id' => $category_id, 'collection_id' => parent::collection_id()]);

		//Try to update relation table
		if ($success && isset($data['property'])) {
			//Update relation table
			CategoryProperty::updateCategory($category_id, $data['property'], true);
		}

		return $success;
	}

	//Delete category
	public static function delete($category_id) {
		return parent::deleteData(new self(), ['id' => $category_id, 'collection_id' => parent::collection_id()]);
	}

	//Validate for insert operation
	public static function validateInsert($data) {
		$v = parent::getValidationObject($data);

		//Set rules
		$v->rule('required', 'name')->message(__('Name is required!'))
		  ->rule('lengthMin', 'name', 1)->message(__('Name size is too small!'))
		  ->rule('relationData', 'property', 'int')->message(__('Incorrect data format for category properties!'));

		//Validate
		return parent::validate($v, self::$__validationErrors);
	}

	//Validate for insert operation
	public static function validateUpdate($data) {
		$v = parent::getValidationObject($data);

		//Set rules
		$v->rule('optional', 'name')
		  ->rule('lengthMin', 'name', 1)->message(__('Name size is too small!'))
		  ->rule('relationData', 'property', 'int')->message(__('Incorrect data format for category properties!'));

		//Validate
		return parent::validate($v, self::$__validationErrors);
	}

	//Get validation errors
	public static function getValidationErrors() {
		return self::$__validationErrors;
	}
}