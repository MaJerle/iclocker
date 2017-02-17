<?php

namespace Model;

use \Inc\Model;

/**
 * A coolection of functions that deal with Blind management
 * @package default
 */
class Orderelement extends Model {
	//Set table name
	public $tableName = 'orderelements';

	//Format associations
	public $associations = [
		'belongsTo' => [
			'Elementorder' => [
				'foreignKey' => 'elementorder_id',
				'conditions' => [
					'Elementorder.deleted' => 0
				],
				'counterCache' => [
					'orderelements_count' => [
						'Orderelement.deleted' => 0,
						'Orderelement.revision_id' => 0
					]
				],
			]
		]
	];
	
	//Validation errors
	private static $__validationErrors = [];
	//Valid edit/insert columns
	private static $__tableColumns = [
		'elementorder_id',
		'number',
		'desiredquantity',
		'minquantity',
		'ordered',
		'purpose',
		'description',
		'element_id'
	];

	//Gets a list of all elements ever ordered
	public static function getElements($order_id = null, $element_id = null, $options = []) {
		$options = array_merge([
			'type' => 'all',
			'conditions' => [],
			'contain' => [],
			'changes' => false,
			'revision' => false
		], $options);
		if (!$options['revision']) {
			$options['conditions']['Orderelement.revision_id'] = 0;
		}
		if (!$options['changes']) {
			$options['conditions']['Orderelement.deleted'] = 0;
		}

		if ($order_id) {
			$options['conditions']['Orderelement.elementorder_id'] = $order_id;
		}
		if ($element_id) {
			$options['conditions']['Orderelement.id'] = $element_id;
		}

		//Get and return all elements for order
		return parent::$db->selectEx(new self(), $options);
	}

	//Gets a list of all elements ever ordered
	public static function getElement($order_id = null, $element_id = null, $options = []) {
		return self::getElements($order_id, $element_id, array_merge($options, ['type' => 'first']));
	}

	//Gets a list of all elements ever ordered
	public static function getElementWithOrder($order_id = null, $element_id = null, $options = []) {
		return self::getElements($order_id, $element_id, array_merge($options, [
			'type' => 'first',
			'contain' => [
				'Elementorder'
			]
		]));
	}

	//Get element on order by order number
	public static function getElementByNumber($order_id, $order_number, $options = []) {
		$options = array_merge([
			'type' => 'first',
			'conditions' => [
				'Orderelement.deleted' => 0,
				'Orderelement.elementorder_id' => $order_id,
				'Orderelement.number' => $order_number,
			],
			'contain' => []
		], $options);

		//Get and return all elements for order
		return parent::$db->selectEx(new self(), $options);
	}
	
	//Insert new element to order
	public static function insert($order_id, $data, $ifexists = 'add') {
		if (!is_array($data)) {
			return false;
		}

		//Get order
		$order = Elementorder::getOrder($order_id, null, [
			'conditions' => [
				'Elementorder.deleted' => 0,
				'Elementorder.status' => '1'
			]
		]);

		//Check order
		if (!$order) {
			return false;
		}

		//Save order id
		$data['elementorder_id'] = $order_id;

		//Check if element ID exists and check in database
		if (isset($data['element_id']) && !empty($data['element_id'])) {
			//Get element from database
			$element = Element::getElement($data['element_id'], null, null, [
				'contain' => ['Property']
			]);

			//Check if exists
			if ($element) {
				if ($order) {
					$match = false;
					foreach ($order['Property'] as $op) {					
						foreach ($element['Property'] as $p) {
							//Check if element ID matches property ID
							if ($op->id == $p->id) {
								//Get property value from element_property relation
								$data['number'] = $p->ElementProperty->property_value;
								break;
							}
						}
					}
				}

				//Fill settings from element
				if (!isset($data['description']) || !$data['description']) {
					$data['description'] = $element['Element']->description;
				}
			} else {
				//Remove element ID
				unset($data['element_id']);
			}
		}

		//Validate object
		if (!self::validateInsert($data)) {
			return false;
		}

		//Get element already on that order
		$element = self::getElementByNumber($order_id, $data['number']);

		if ($element) {
			$ifexists = strtolower($ifexists);

			//Update element with new values
			if ($ifexists == 'add') {
				//Element already exists with that order number on that order
				$min = 'minquantity = minquantity + ' . $data['minquantity'];
				$desired = 'desiredquantity = desiredquantity + ' . $data['desiredquantity'];

				//Remove from data array
				unset($data['minquantity']);
				unset($data['desiredquantity']);

				//Filter columns first
				$columns = parent::formatColumns(self::$__tableColumns, $data);

				//Add to columns
				$columns[] = $min;
				$columns[] = $desired;
			} else {
				//Use fields as already on data array
				$columns = parent::formatColumns(self::$__tableColumns, $data);
			}

			//Update elements only!
			$success = parent::updateData(new self(), $columns, ['id' => $element['Orderelement']->id]);

			//Check status
			if ($success) {
				return $element['Orderelement']->id;
			}
			return $success;
		}

		//Not ordered yet
		$data['ordered'] = 0;

		//Check if ID of element is set
		return parent::insertData(new self(), parent::formatColumns(self::$__tableColumns, array_merge($data, ['id' => $order_id])));
	}

	//Update
	public static function update($orderelement_id, $data) {
		if (!self::validateUpdate($data)) {
			return false;
		}

		//Try to update
		return parent::updateData(new self(), parent::formatColumns(self::$__tableColumns, $data), ['id' => $orderelement_id, 'collection_id' => parent::collection_id()]);
	}
	
	//Insert new element to order
	public static function delete($elementorder_id, $orderelement_id) {
		//Check if ID of element is set
		return parent::deleteData(new self(), ['elementorder_id' => $elementorder_id, 'id' => $orderelement_id]);
	}

	//Validate for insert operation
	public static function validateInsert($data) {
		$v = parent::getValidationObject($data);

		//Set rules
		$v->rule('required', 'number')->message(__('Order element number is required!'))
		  ->rule('lengthMin', 'number', 1)->message(__('Order element number is too small!'))
		  ->rule('required', 'minquantity')->message(__('Order element minimal quantity is required!'))
		  ->rule('integer', 'minquantity')->message(__('Order element minimal quantity must be an integer!'))
		  ->rule('required', 'desiredquantity')->message(__('Order element desired quantity is required!'))
		  ->rule('integer', 'desiredquantity')->message(__('Order element desired quantity must be an integer!'))
		  ->rule('required', 'purpose')->message(__('Order element purpose is required!'))
		  ->rule('lengthMin', 'purpose', 1)->message(__('Order element purpose is too small!'));

		//Validate
		return parent::validate($v, self::$__validationErrors);
	}

	//Validate for insert operation
	public static function validateUpdate($data) {
		$v = parent::getValidationObject($data);

		//Set rules
		$v->rule('optional', 'number')
		  ->rule('lengthMin', 'number', 1)->message(__('Order element number is too small!'))
		  ->rule('optional', 'minquantity')
		  ->rule('integer', 'minquantity')->message(__('Order element minimal quantity must be an integer!'))
		  ->rule('optional', 'desiredquantity')
		  ->rule('integer', 'desiredquantity')->message(__('Order element desired quantity must be an integer!'))
		  ->rule('optional', 'purpose')
		  ->rule('lengthMin', 'purpose', 1)->message(__('Order element purpose is too small!'));

		//Validate
		return parent::validate($v, self::$__validationErrors);
	}

	//Get validation errors
	public static function getValidationErrors() {
		return self::$__validationErrors;
	}


	//$val = false: insert failed, $val > 0: id of inserted value
	public function afterInsert($val) {
		//Make fake updates to get new modified by for parent element
		if ($val) {
			$el = Orderelement::find('first', ['conditions' => ['Orderelement.id' => $val], 'contain' => [], 'createdBy' => false]);

			if ($el) {
				Elementorder::update($el['Orderelement']->elementorder_id, ['modified_at' => self::getDate()]);
			}
		}
	}

	//$conditions = conditions, used for updating record
	public function afterUpdate($conditions) {
		if ($conditions) {
			$el = Orderelement::find('first', ['conditions' => $conditions, 'contain' => [], 'createdBy' => false]);

			if ($el) {
				//parent::updateData(new Elementorder(), ['modified_at' => self::getDate()], ['id' => $el['Orderelement']->elementorder_id]);
				Elementorder::update($el['Orderelement']->elementorder_id, ['modified_at' => self::getDate()]);
			}
		}
	}

	//$conditions = conditions, used for updating record
	public function afterDelete($conditions) {
		if ($conditions) {
			$el = Orderelement::find('first', ['conditions' => $conditions, 'contain' => [], 'createdBy' => false]);

			if ($el) {
				Elementorder::update($el['Orderelement']->elementorder_id, ['modified_at' => self::getDate()]);
			}
		}
	}
}
