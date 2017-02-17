<?php

namespace Model;

use \Inc\Model;
use \Model\Propertychoice;

/**
 * A coolection of functions that deal with Blind management
 * @package default
 */
class CategoryProperty extends Model {
	//Set table name
	public $tableName = 'categories_properties';

	//Format associations
	public $associations = [
		'belongsTo' => [
			'Category' => [
				'foreignKey' => 'category_id',
				'counterCache' => [
					'properties_count' => []
				],
				'conditions' => [
					'Category.deleted' => 0,
				]
			],
			'Property' => [
				'foreignKey' => 'property_id',
				'counterCache' => 'categories_count',
				'conditions' => [
					'Property.deleted' => 0,
				]
			]
		]
	];

	//Lists all categories used in given property
	public static function getCategories($property_id, $options = []) {
		$options = array_merge([
			'type' => 'all',
			'conditions' => []
		], $options);

		//Set conditions
		$options['conditions']['CategoryProperty.deleted'] = 0;
		$options['conditions']['CategoryProperty.revision_id'] = 0;
		$options['conditions']['CategoryProperty.property_id'] = $property_id;

		//Select data
		return parent::$db->selectEx(new self(), $options);
	}

	//List all categories IDS used in given property
	public static function getCategoriesIds($property_id) {
		return parent::$db->getIds(self::getCategories($property_id), 'CategoryProperty.category_id');
	}

	//Lists all properties used in given category
	public static function getProperties($category_id, $options = []) {
		$options = array_merge([
			'type' => 'all',
			'conditions' => []
		], $options);

		//Remove contain flag, all must be included!
		if (isset($options['contain'])) {
			unset($options['contain']);
		}

		//Set conditions
		$options['conditions']['CategoryProperty.deleted'] = 0;
		$options['conditions']['CategoryProperty.revision_id'] = 0;
		$options['conditions']['CategoryProperty.category_id'] = $category_id;

		//Select data
		return parent::$db->selectEx(new self(), $options);
	}

	public static function getPropertiesWithChoices($category_id, $options = []) {
		//Get available properties
		$properties = CategoryProperty::getProperties($category_id, array_merge(['contain' => ['Category', 'Property']], $options));

		//Get property IDS
		if ($properties) {
			//Get ID values
			$property_ids = [];
			foreach ($properties as $property) {
				$property_ids[] = $property['Property']->id;
			}

			//Get property choices
			$propertyChoices = Propertychoice::getChoices($property_ids);

			//Go through options
			if ($propertyChoices) {
				foreach ($properties as &$option) {
					$option['Property']->Propertychoice = [];
					foreach ($propertyChoices as $choice) {
						if ($choice['Propertychoice']->property_id == $option['Property']->id) {
							$option['Property']->Propertychoice[] = $choice['Propertychoice'];
						}
					}
				}
			}
		}

		return $properties;
	}

	//Lists all properties IDS used in given category
	public static function getPropertiesIds($category_id) {
		return parent::$db->getIds(self::getProperties($category_id), 'CategoryProperty.property_id');
	}

	//Update relation table for given property
	public static function updateProperty($property_id, $new, $old = false) {
		$add = $add2 = $remove = [];

		//Get current list if needed
		if (!$old) {
			$old = array();
		} else if ($old === true) {
			$old = self::getCategoriesIds($property_id);
		}
		//Check only if there are not both false or empty
		if (!empty($new) || !empty($old)) {
			//Check what we have to add
			foreach ($new as $c) {
				if (!in_array($c, $old)) {
					//$add[] = '("' . parent::$db->res($property_id) . '", "' . parent::$db->res($c) . '")';
					$add[] = [$property_id, $c];
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
			parent::insertData(new self(), ['property_id', 'category_id'], $add);
		}

		//Check for delete entries
		if (!empty($remove)) {
			//Delete data from database
			parent::deleteData(new self(), ['property_id' => $property_id, 'category_id' => $remove]);
		}

		//Update last modified
		parent::updateLastModified(new Property(), $property_id);
		parent::updateLastModified(new Category(), $add2, $remove);
	}

	//Update relation table for given category
	public static function updateCategory($category_id, $new, $old = false) {
		$add = $add2 = $remove = [];

		//Get current list if needed
		if (!$old) {
			$old = array();
		} else if ($old === true) {
			$old = self::getPropertiesIds($category_id);
		}

		//Check only if there are not both false or empty
		if (!empty($new) || !empty($old)) {
			//Check what we have to add
			foreach ($new as $c) {
				if (!in_array($c, $old)) {
					$add[] = [$category_id, $c];
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
			parent::insertData(new self(), ['category_id', 'property_id'], $add);
		}

		//Check for remove entries
		if (!empty($remove)) {
			//Delete data from database
			parent::deleteData(new self(), ['property_id' => $remove, 'category_id' => $category_id, 'revision_id' => 0, 'deleted' => 0]);
		}

		//Update last modified
		parent::updateLastModified(new Category(), $category_id);
		parent::updateLastModified(new Property(), $add2, $remove);
	}
}
