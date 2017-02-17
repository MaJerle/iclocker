<?php

namespace Model;

use \Inc\Model;
use \Model\Orderelement;
use \Model\Element;
use \Model\ElementorderProperty;

/**
 * A coolection of functions that deal with Blind management
 * @package default
 */
class Elementorder extends Model {
	//Constants
	const STATUS_CANCELED = 0;
	const STATUS_OPEN = 1;
	const STATUS_ORDERED = 2;

	//Set table name
	public $tableName = 'elementorders';

	//Format associations
	public $associations = [
		'hasMany' => [
			'Orderelement' => [
				'foreignKey' => 'elementorder_id',
				'conditions' => [
					'Orderelement.deleted' => 0
				]
			]
		],
		'belongsTo' => [
			'Collection' => [
				'foreignKey' => 'collection_id',
				'conditions' => [
					'Collection.deleted' => 0
				],
				'counterCache' => [
					'elementorders_count' => [
						'Elementorder.deleted' => 0,
						'Elementorder.revision_id' => 0
					]
				],
				'changes' => false
			]
		],
		'manyToMany' => [
			'Property' => [
				'joinModel' => 'ElementorderProperty',
				'foreignKey' => 'elementorder_id',
				'associationForeignKey' => 'property_id',
				'conditions' => [
					'Property.deleted' => 0,
					'Property.revision_id' => 0,
					'ElementorderProperty.deleted' => 0,
					'ElementorderProperty.revision_id' => 0
				]
			]
		]
	];
	
	//Virtual fields
	public $virtualFields = [
		//'orderelements_count' => '(SELECT COUNT(*) FROM orderelements AS Orderelement WHERE Orderelement.elementorder_id = Elementorder.id AND Orderelement.deleted = 0)'
	];

	//Validation errors
	private static $__validationErrors = [];
	//Columns for SQL operations
	private static $__tableColumns = ['name', 'datecreated', 'dateordered', 'status', 'synced', 'quantity_type'];

	//Get a list of orders
	public static function getOrders($order_id = null, $collection_id = null, $options = []) {
		$options = array_merge([
			'type' => 'all',
			'conditions' => [],
			'changes' => false,
			'contain' => ['Property'],
			'revision' => false
		], $options);
		if (!$options['revision']) {
			$options['conditions']['Elementorder.revision_id'] = 0;
		}
		if (!$options['changes']) {
			$options['conditions']['Elementorder.deleted'] = 0;
		}

		//Get collection ID
		$collection_id = parent::collection_id($collection_id);
		
		//Conditions
		if ($collection_id) {
			$options['conditions']['Elementorder.collection_id'] = $collection_id;
		}
		if ($order_id) {
			$options['conditions']['Elementorder.id'] = $order_id;
		}

		return parent::$db->selectEx(new self(), $options);
	}

	//Get a list of orders with all elemenets
	public static function getOrdersWithElements($order_id = null, $collection_id = null, $options = []) {
		return self::getOrders($order_id, $collection_id, array_merge([
			'type' => 'all',
			'contain' => [
				'Orderelement',
				'Property'
			]
		], $options));
	}

	//Get order with specific ID
	public static function getOrder($order_id = null, $collection_id = null) {
		return self::getOrders($order_id, $collection_id, [
			'type' => 'first'
		]);
	}

	//Get order with specific ID
	public static function getOrderWithElements($order_id = null, $collection_id = null) {
		return self::getOrdersWithElements($order_id, $collection_id, [
			'type' => 'first'
		]);
	}

	//Insert new order
	public static function insert($data) {
		if (!is_array($data)) {
			$data = [$data];
		}

		//Check for created date if it not exists
		if (!isset($data['datecreated']) || empty($data['datecreated'])) {
			$data['datecreated'] = parent::getDate();
		}
		if (!isset($data['dateordered']) || empty($data['dateordered'])) {
			$data['dateordered'] = parent::getDate();
		}

		//Validate insert
		if (!self::validateInsert($data)) {
			return false;
		}

		//Try to insert
		$insID = parent::insertData(new self(), parent::formatColumns(self::$__tableColumns, $data));

		//Check property values
		if ($insID && isset($data['property'])) {
			//Add properties
			ElementorderProperty::updateOrder($insID, $data['property'], false);
		}

		return $insID;
	}

	//Insert new order
	public static function update($order_id, $data) {
		//Validate insert
		if (!self::validateUpdate($data)) {
			return false;
		}

		//Try to update
		$status = parent::updateData(new self(), 
			parent::formatColumns(self::$__tableColumns, $data), 
			['id' => $order_id, 'collection_id' => parent::collection_id()]
		);

		//Check property values
		if ($status && isset($data['property'])) {
			//Add properties
			ElementorderProperty::updateOrder($order_id, $data['property'], true);
		}

		return $status;
	}

	//Insert new order
	public static function delete($order_id) {
		//Try to delete
		return parent::deleteData(new self(), ['id' => $order_id]);
	}

	//Validate for insert operation
	public static function validateInsert($data) {
		$v = parent::getValidationObject($data);

		//Set rules
		$v->rule('required', 'name')->message(__('Name is required!'))
		  ->rule('lengthMin', 'name', 1)->message(__('Name size is too small!'));

		//Validate
		return parent::validate($v, self::$__validationErrors);
	}

	//Validate for insert operation
	public static function validateUpdate($data) {
		$v = parent::getValidationObject($data);

		//Set rules
		$v->rule('optional', 'name')
		  ->rule('lengthMin', 'name', 1)->message(__('Name size is too small!'));

		//Validate
		return parent::validate($v, self::$__validationErrors);
	}

	//Get validation errors
	public static function getValidationErrors() {
		return self::$__validationErrors;
	}
}
