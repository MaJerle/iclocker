<?php

namespace Model;

use \Inc\Model;

/**
 * A coolection of functions that deal with Blind management
 * @package default
 */
class Property extends Model {
	//Set table name
	public $tableName = 'properties';

	//Format associations
	public $associations = [
		'belongsTo' => [
			'Collection' => [
				'foreignKey' => 'collection_id',
				'conditions' => [
					'Collection.deleted' => 0
				],
				'counterCache' => [
					'properties_count' => [
						'Property.deleted' => 0,
						'Property.revision_id' => 0
					]
				],
				'changes' => false
			]
		],
		'hasMany' => [
			'Propertychoice' => [
				'foreignKey' => 'property_id',
				'conditions' => [
					'Propertychoice.deleted' => 0
				]
			],
			'CategoryProperty' => [
				'foreignKey' => 'property_id',
				'changes' => true
			],
			'ElementProperty' => [
				'foreignKey' => 'property_id',
				'changes' => true
			]
		],
		'manyToMany' => [
			'Category' => [
				'joinModel' => 'CategoryProperty',
				'foreignKey' => 'property_id',
				'associationForeignKey' => 'category_id',
				'conditions' => [
					'Category.deleted' => 0
				],
				'changes' => false
			],
			'Element' => [
				'joinModel' => 'ElementProperty',
				'foreignKey' => 'property_id',
				'associationForeignKey' => 'element_id',
				'conditions' => [
					'Element.deleted' => 0
				],
				'changes' => false
			]
		]
	];

	//Data types for property
	const TYPE_STRING = 1;
	const TYPE_NUMBER = 2;
	const TYPE_FILEUPLOAD = 3;
	
	//Validation errors
	private static $__validationErrors = [];
	//Columns for SQL operations
	private static $__tableColumns = ['name', 'description', 'unit', 'data_type', 'collection_id'];

	//List all properties
	public static function getProperties($property_id = null, $collection_id = null, $options = []) {
		$options = array_merge([
			'type' => 'all',
			'conditions' => [],
			'order' => 'Property.name',
			'contain' => [],
			'changes' => false,
			'revision' => false
		], $options);
		if (!$options['revision']) {
			$options['conditions']['Property.revision_id'] = 0;
		}
		if (!$options['changes']) {
			$options['conditions']['Property.deleted'] = 0;
		}

		//Get collection ID
		$collection_id = parent::collection_id($collection_id);

		//Check for conditions
		if ($property_id) {
			$options['conditions']['Property.id'] = $property_id; 
		}
		if ($collection_id) {
			$options['conditions']['Property.collection_id'] = $collection_id;
		}

		//Get properties
		return parent::$db->selectEx(new self(), $options);
	}

	//List all properties with categories
	public static function getPropertiesWithCategories($property_id = null, $collection_id = null, $options = []) {
		return self::getProperties($property_id, $collection_id, array_merge([
			'contain' => ['Category', 'Propertychoice']
		], $options));
	}

	//Get property with given ID
	public static function getProperty($property_id, $collection_id = null, $options = []) {
		return self::getProperties($property_id, $collection_id, array_merge($options, ['type' => 'first']));
	}

	//Get property with given ID including its categories
	public static function getPropertyWithCategories($property_id, $collection_id = null, $options = []) {
		return self::getPropertiesWithCategories($property_id, $collection_id, array_merge($options, ['type' => 'first']));
	}

	//Insert into database
	public static function insert($data) {
		//Check validation
		if (!self::validateInsert($data)) {
			return false;
		}

		//Try to insert property
		$insID = parent::insertData(new self(), parent::formatColumns(self::$__tableColumns, $data));

		//Check for success and insert relation table with categories
		if ($insID && isset($data['category'])) {
			//Update relation table with selected categories
			CategoryProperty::updateProperty($insID, $data['category'], false);
		}

		//Check for success and option values
		if ($insID && isset($data['option'])) {
			//Update relation table with selected property choices (options)
			Propertychoice::updateProperty($insID, $data['option'], false);
		}

		return $insID;
	}

	//Update database
	public static function update($property_id, $data) {
		//Check validation
		if (!self::validateUpdate($data)) {
			return false;
		}

		//Try to update
		$success = parent::updateData(new self(),
			parent::formatColumns(self::$__tableColumns, array_merge($data, ['id' => $property_id])),
			['id' => $property_id, 'collection_id' => parent::collection_id()]
		);

		//Check for success and update relation table with categories
		if ($success && isset($data['category'])) {
			//Update relation table with selected categories
			CategoryProperty::updateProperty($property_id, $data['category'], true);
		}

		//Check for success and option values
		if ($success && isset($data['option'])) {
			//Update relation table with selected property choices (options)
			Propertychoice::updateProperty($property_id, $data['option'], true);
		}

		return $success;
	}

	//Delete property with given ID
	public static function delete($property_id) {
		return parent::deleteData(new self(), ['id' => $property_id, 'collection_id' => self::collection_id()]);
	}

	//Validate for insert operation
	public static function validateInsert($data) {
		$v = parent::getValidationObject($data);

		//Set rules
		$v->rule('required', 'name')->message(__('Name is required!'))
		  ->rule('lengthMin', 'name', 1)->message(__('Name size is too small!'))
		  ->rule('required', 'data_type')->message(__('Data type is required!'))
		  ->rule('lengthMin', 'data_type', 1)->message(__('Data type size is too small!'))
		  ->rule('integer', 'data_type')->message(__('Data type must be integer!'))
		  ->rule('required', 'unit')->message(__('Unit is required!'))
		  ->rule('lengthMin', 'unit', 1)->message(__('Unit size is too small!'))
		  ->rule('relationData', 'category', 'int')->message(__('Incorrect data format for property categories!'))
		  ->rule('relationData', 'option', false)->message(__('Incorrect data format for property options!'));

		//Validate
		return parent::validate($v, self::$__validationErrors);
	}

	//Validate for update operation
	public static function validateUpdate($data) {
		$v = parent::getValidationObject($data);

		//Set rules
		$v->rule('optional', 'name')
		  ->rule('lengthMin', 'name', 1)->message(__('Name size is too small!'))
		  ->rule('optional', 'data_type')
		  ->rule('lengthMin', 'data_type', 1)->message(__('Data type size is too small!'))
		  ->rule('integer', 'data_type')->message(__('Data type must be integer!'))
		  ->rule('optional', 'unit')
		  ->rule('lengthMin', 'unit', 1)->message(__('Unit size is too small!'))
		  ->rule('relationData', 'category', 'int')->message(__('Incorrect data format for property categories!'))
		  ->rule('relationData', 'option', false)->message(__('Incorrect data format for property options!'));

		//Validate
		return parent::validate($v, self::$__validationErrors);
	}

	//Get validation errors
	public static function getValidationErrors() {
		return self::$__validationErrors;
	}
}
