<?php

namespace Model;

use \Inc\Model;

/**
 * A coolection of functions that deal with Blind management
 * @package default
 */
class ElementorderProperty extends Model {
	//Set table name
	public $tableName = 'elementorders_properties';
	public $associations = [
		'belongsTo' => [
			'Elementorder' => [
				'foreignKey' => 'elementorder_id',
				'conditions' => [
					'Elementorder.deleted' => 0,
				]
			],
			'Property' => [
				'foreignKey' => 'property_id',
				'conditions' => [
					'Property.deleted' => 0,
				]
			]
		]
	];

	//Get list of p currently used on this element
	public static function getProperties($order_id, $options = []) {
		$options = array_merge([
			'type' => 'all',
			'conditions' => []
		], $options);

		//Remove contain flag, all must be included!
		if (isset($options['contain'])) {
			unset($options['contain']);
		}

		//Set conditions
		$options['conditions']['ElementorderProperty.deleted'] = 0;
		$options['conditions']['ElementorderProperty.revision_id'] = 0;
		$options['conditions']['ElementorderProperty.elementorder_id'] = $order_id;

		//Select data
		return parent::$db->selectEx(new self(), $options);
	}

	//Get list of properties IDS currently used on this order
	public static function getPropertiesIds($order_id) {
		return parent::$db->getIds(self::getProperties($order_id), 'ElementorderProperty.property_id');
	}

	//Update relation table for given order
	//$values parameters are in value: [id1, id2, id3]
	public static function updateOrder($order_id, $new, $old = false) {
		$add = $add2 = $remove = [];

		//Get current list if needed
		if (!$old) {
			$old = array();
		} else if ($old === true) {
			$old = self::getPropertiesIds($order_id);
		}
		//Check only if there are not both false or empty
		if (!empty($new) || !empty($old)) {
			//Check what we have to add
			foreach ($new as $c) {
				if (!in_array($c, $old)) {
					$add[] = [$order_id, $c];
					$add2[] = $c;
				}
			}

			//Check what we have to remove
			foreach ($old as $c) {
				if (!in_array($c, $new)) {
					$remove[] = $c;
				}
			}
		}

		//We have to add something?
		if (!empty($add)) {
			//Insert data to database
			parent::insertData(new self(), ['elementorder_id', 'property_id'], $add);
		}

		//Check for delete entries
		if (!empty($remove)) {
			//Delete data from database
			parent::deleteData(new self(), ['elementorder_id' => $order_id, 'property_id' => $remove]);
		}

		//Update last modified
		parent::updateLastModified(new Elementorder(), $order_id);
		parent::updateLastModified(new Property(), $add2, $remove);
	}
}
