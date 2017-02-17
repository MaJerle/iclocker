<?php

namespace Model;

use \Inc\Model;

/**
 * A coolection of functions that deal with Blind management
 * @package default
 */
class Propertychoice extends Model {
	//Set table name
	public $tableName = 'propertychoices';

	//Format associations
	public $associations = [
		'belongsTo' => [
			'Property' => [
				'foreignKey' => 'property_id',
				'conditions' => [
					'Property.deleted' => 0
				],
				'counterCache' => [
					'propertychoices_count' => [
						'Propertychoice.deleted' => 0,
						'Propertychoice.revision_id' => 0
					]
				],
			]
		]
	];
	
	//Validation errors
	private static $__validationErrors = [];
	//Columns for SQL operations
	private static $__tableColumns = ['choice', 'collection_id'];

	//Get a list of all choices for hiven property
	public static function getChoices($property_id = null, $choice_id = null, $collection_id = null, $options = []) {
		//Get collection
		$collection_id = parent::collection_id($collection_id);

		//Merge options
		$options = array_merge([
			'type' => 'all',
			'conditions' => [],
			'contain' => [],
			'order' => 'Propertychoice.choice',
			'changes' => false,
			'revision' => false
		], $options);
		if (!$options['revision']) {
			$options['conditions']['Propertychoice.revision_id'] = 0;
		}
		if (!$options['changes']) {
			$options['conditions']['Propertychoice.deleted'] = 0;
		}

		if ($collection_id) {
			$options['conditions']['Propertychoice.collection_id'] = $collection_id;
		}
		if ($property_id) {
			$options['conditions']['Propertychoice.property_id'] = $property_id;
		}
		if ($choice_id) {
			$options['conditions']['Propertychoice.id'] = $choice_id;
		}

		
		return parent::$db->selectEx(new self(), $options);
	}

	//Get a list of all choices for given property
	public static function getChoicesIds($property_id = null, $choice_id = null, $collection_id = null) {
		return parent::getIds(self::getChoices($property_id, $choice_id, $collection_id), 'Propertychoice.choice_id');
	}

	//Get a list of choices
	public static function getChoicesList($property_id = null, $collection_id = null) {
		return parent::$db->getIds(self::getChoices($property_id, null, $collection_id), 'Propertychoice.choice');
	}

	//Get choice by value and property
	public static function getByChoice($property_id, $choice) {
		return self::getChoices($property_id, null, null, [
			'type' => 'first',
			'conditions' => [
				'Propertychoice.property_id' => $property_id,
				'Propertychoice.choice' => $choice
			]
		]);
	}

	//Get choice by id
	public static function getChoice($choice_id, $property_id) {
		return self::getChoices($property_id, null, null, [
			'type' => 'first',
			'conditions' => [
				'Propertychoice.id' => $choice_id
			]
		]);
	}

	//Update properties with new propertyChoices
	public static function updateProperty($property_id, $new, $old = false) {
		$add = $remove = [];

		//Go to array
		if (!is_array($new)) {
			$new = [$new];
		}

		//Get current list if needed
		if (!$old) {
			$old = array();
		} else if ($old === true) {
			$old = self::getChoicesIds($property_id);
		}

		if (!empty($new) || !empty($old)) {
			//Insert data
			foreach ($new as $n) {
				if (!in_array($n, $old)) {
					$add[] = [$property_id, $n];
				}
			}

			//Check what we have to remove
			foreach ($old as $c) {
				if (!in_array($c, $new)) {
					$remove[] = $c;
				}
			}
		}

		//Add choices
		if (!empty($add)) {
			parent::insertData(new self(), ['property_id', 'choice'], $add);
		}

		//Remove choices
		if (!empty($remove)) {
			parent::deleteData(new self(), ['id' => $remove]);
		}
	}

	//Insert new property into database
	public static function insert($property_id, $data) {
		//Validate input
		if (!self::validateInsert($data)) {
			return false;
		}

		//Check if already exists
		$choice = Propertychoice::getByChoice($property_id, $data['choice']);
		if ($choice) {
			return $choice['Propertychoice']->id;
		}

		//Check if already exists first
		return parent::insertData(new self(),
			array_merge(parent::formatColumns(self::$__tableColumns, $data), [
				'property_id' => $property_id,
				'collection_id' => parent::collection_id()
			]));
	}

	//Insert new property into database
	public static function update($choice_id, $data) {
		//Validate input
		if (!self::validateUpdate($data)) {
			return false;
		}

		//Check if already exists first
		return parent::updateData(new self(),
			array_merge(parent::formatColumns(self::$__tableColumns, $data)), [
				'id' => $choice_id,
				'collection_id' => parent::collection_id()
			]);
	}

	//Insert new property into database
	public static function delete($choice_id, $collection_id) {
		//Delete property choice with given ID
		return parent::deleteData(new self(), ['id' => $choice_id, 'collection_id' => self::collection_id()]);
	}

	//Validate for insert operation
	public static function validateInsert($data) {
		$v = parent::getValidationObject($data);

		//Set rules
		$v->rule('required', 'choice')->message(__('Choice value is required!'))
		  ->rule('lengthMin', 'choice', 1)->message(__('Choice value is too small!'));

		//Validate
		return parent::validate($v, self::$__validationErrors);
	}

	//Validate for insert operation
	public static function validateUpdate($data) {
		$v = parent::getValidationObject($data);

		//Set rules
		$v->rule('optional', 'choice')->message(__('Choice value is required!'))
		  ->rule('lengthMin', 'choice', 1)->message(__('Choice value is too small!'));

		//Validate
		return parent::validate($v, self::$__validationErrors);
	}

	//Get validation errors
	public static function getValidationErrors() {
		return self::$__validationErrors;
	}


	///CALLBACKS FOR FAKE UPDATES properties for changes
	//$val = false: insert failed, $val > 0: id of inserted value
	public function afterInsert($val) {
		//Make fake updates to get new modified by for parent element
		if ($val) {
			$el = Propertychoice::find('first', ['conditions' => ['Propertychoice.id' => $val], 'contain' => [], 'createdBy' => false]);

			if ($el) {
				$success = Property::update($el['Propertychoice']->property_id, ['modified_at' => self::getDate()]);
			}
		}
	}

	//$conditions = conditions, used for updating record
	public function afterUpdate($conditions) {
		if ($conditions) {
			$el = Propertychoice::find('first', ['conditions' => $conditions, 'contain' => [], 'createdBy' => false]);

			if ($el) {
				Property::update($el['Propertychoice']->property_id, ['modified_at' => self::getDate()]);
			}
		}
	}

	//$conditions = conditions, used for updating record
	public function afterDelete($conditions) {
		if ($conditions) {
			$el = Propertychoice::find('first', ['conditions' => $conditions, 'contain' => [], 'createdBy' => false]);

			if ($el) {
				Property::update($el['Propertychoice']->property_id, ['modified_at' => self::getDate()]);
			}
		}
	}
}
