<?php

namespace Model;

use \Inc\Model;

/**
 * A coolection of functions that deal with Blind management
 * @package default
 */
class ElementProperty extends Model {
	//Set table name
	public $tableName = 'elements_properties';
	public $associations = [
		'belongsTo' => [
			'Element' => [
				'foreignKey' => 'element_id',
				'conditions' => [
					'Element.deleted' => 0,
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
	public static function getProperties($element_id, $options = []) {
		$options = array_merge([
			'type' => 'all',
			'conditions' => []
		], $options);

		//Remove contain flag, all must be included!
		if (isset($options['contain'])) {
			unset($options['contain']);
		}

		//Set conditions
		$options['conditions']['ElementProperty.deleted'] = 0;
		$options['conditions']['ElementProperty.revision_id'] = 0;
		$options['conditions']['ElementProperty.element_id'] = $element_id;

		//Select data
		return parent::$db->selectEx(new self(), $options);
	}

	//Get list of properties IDS currently used on this element
	public static function getPropertiesIds($element_id) {
		return parent::$db->getIds(self::getProperties($element_id), 'ElementProperty.property_id');
	}

	//Get list of p currently used on this property
	public static function getElements($property_id, $options = []) {
		$options = array_merge([
			'type' => 'all',
			'conditions' => []
		], $options);

		//Remove contain flag, all must be included!
		if (isset($options['contain'])) {
			unset($options['contain']);
		}

		//Set conditions
		$options['conditions']['ElementProperty.deleted'] = 0;
		$options['conditions']['ElementProperty.revision_id'] = 0;
		$options['conditions']['ElementProperty.property_id'] = $property_id;

		//Select data
		return parent::$db->selectEx(new self(), $options);
	}

	//Get list of elements IDS currently used on this property
	public static function getElementsIds($property_id) {
		return parent::$db->getIds(self::getElements($property_id), 'ElementProperty.element_id');
	}

	//Update relation table for given element
	//$values parameters are in value: ['property_id' => 'property_value', 'property_id2' => 'property_value2', ...]
	public static function updateElement($element_id, $values, $used = false) {
		$add = $addProperty = [];
		if (!is_array($values)) {
			return;
		}

		//Get current list if needed
		if (!$used) {
			$used = array();
		} else if ($used == true) {
			//Lets check which properties are currently set for element
			$data = self::getProperties($element_id);
			$used = [];
			foreach ($data as $d) {
				$used[$d['ElementProperty']->property_id] = $d['ElementProperty']->property_value;
			}
		}
		//Else use value from user

		$update = []; $remove = []; $add = [];
		foreach ($values as $property_id => $property_value) {
			//Check if record already is in DB = update or delete
			if (isset($used[$property_id])) {
				//Check if empty
				if (empty($property_value)) {
					$remove[] = $property_id;
				} else {
					if ($used[$property_id] != $property_value) {
						$update[$property_id] = $property_value;
					}
				}
			} else if (!empty($property_value)) {
				$add[] = [$element_id, $property_id, $property_value];
				$addProperty[] = $property_id;
			}
		}

		//Remove previously used whic are not in array now
		foreach ($used as $k => $p) {
			if (!isset($values[$k])) {
				$remove[] = $k;
			}
		}

		//What we have to add
		if (!empty($add)) {
			//Add elements to database
			parent::insertData(new self(), ['element_id', 'property_id', 'property_value'], $add);
		}

		//What we have to update
		if (!empty($update)) {
			//Update one by one
			foreach ($update as $property_id => $value) {
				parent::updateData(new self(), ['property_value' => $value], ['property_id' => $property_id, 'element_id' => $element_id]);
			}
		}

		//What we have to remove
		if (!empty($remove)) {
			parent::deleteData(new self(), ['element_id' => $element_id, 'property_id' => $remove]);
		}

		//Update parent values and set last modified values
		parent::updateLastModified(new Element(), $element_id);
		parent::updateLastModified(new Property(), (array)$addProperty, array_keys((array)$update), (array)$remove);

		//We are OK
		return true;
	}
}
