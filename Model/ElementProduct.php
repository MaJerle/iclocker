<?php

namespace Model;

use \Inc\Model;

/**
 * A coolection of functions that deal with Blind management
 * @package default
 */
class ElementProduct extends Model {
	//Set table name
	public $tableName = 'elements_products';

	//Format associations
	public $associations = [
		'belongsTo' => [
			'Element' => [
				'foreignKey' => 'element_id',
				'conditions' => [
					'Element.deleted' => 0
				]
			],
			'Product' => [
				'foreignKey' => 'product_id',
				'conditions' => [
					'Product.deleted' => 0
				]
			]
		]
	];

	//Get list of elements currently used on product with given ID
	public static function getElements($product_id, $options = []) {
		$options = array_merge([
			'type' => 'all',
			'conditions' => []
		], $options);

		//Remove contain flag, all must be included!
		if (isset($options['contain'])) {
			unset($options['contain']);
		}

		//Set conditions
		$options['conditions']['ElementProduct.deleted'] = 0;
		$options['conditions']['ElementProduct.revision_id'] = 0;
		$options['conditions']['ElementProduct.product_id'] = $product_id;

		//Select data
		return parent::$db->selectEx(new self(), $options);
	}

	//Get list of elements IDS currently used on product with given ID
	public static function getElementsIds($product_id) {
		return parent::$db->getIds(self::getElements($product_id), 'ElementProduct.element_id');
	}

	//Get list of p currently used on this element
	public static function getProducts($element_id, $options = []) {
		$options = array_merge([
			'type' => 'all',
			'conditions' => []
		], $options);

		//Remove contain flag, all must be included!
		if (isset($options['contain'])) {
			unset($options['contain']);
		}

		//Set conditions
		$options['conditions']['ElementProduct.deleted'] = 0;
		$options['conditions']['ElementProduct.revision_id'] = 0;
		$options['conditions']['ElementProduct.element_id'] = $element_id;

		//Select data
		return parent::$db->selectEx(new self(), $options);
	}

	//Get list of properties IDS currently used on this element
	public static function getProductsIds($element_id) {
		return parent::$db->getIds(self::getProducts($element_id), 'ElementProduct.product_id');
	}

	//Update relation table for given element
	//$values parameters are in value: ['element_id' => 'element_count', 'element_id2' => 'element_count', ...]
	public static function updateProduct($product_id, $values, $usedElements = false) {
		$add = $add2 = [];
		if (!is_array($values)) {
			return;
		}

		//Get current list if needed
		if (!$usedElements) {
			//Useful when first INSERT is used for product
			$usedElements = [];
		} else if ($usedElements == true) {
			//Lets check which properties are currently set for element
			$data = self::getElements($product_id);
			$usedElements = [];
			foreach ($data as $d) {
				$usedElements[$d['ElementProduct']->element_id] = $d['ElementProduct']->element_count;
			}
		}
		//Else use value from user

		$update = []; $remove = []; $add = [];
		foreach ($values as $element_id => $element_count) {
			//Check if record already is in DB = update or delete
			if (isset($usedElements[$element_id])) {
				//Check if empty
				if (empty($element_count) || !$element_count) {
					$remove[] = $element_id;
				} else {
					if ($element_count != $usedElements[$element_id]) {
						$update[$element_id] = $element_count;
					}
				}
			} else if (!empty($element_count)) {
				$add[] = [$product_id, $element_id, $element_count];
				$add2[] = $element_id;
			}
		}

		//Remove currently used elements which are not in new request
		foreach ($usedElements as $k => $e) {
			if (!isset($values[$k])) {
				$remove[] = $k;
			}
		}

		//What we have to add
		if (!empty($add)) {
			//Add elements to database
			parent::insertData(new self(), ['product_id', 'element_id', 'element_count'], $add);
		}

		//What we have to update
		if (!empty($update)) {
			//Update one by one
			foreach ($update as $element_id => $value) {
				parent::updateData(new self(), ['element_count' => $value], ['element_id' => $element_id, 'product_id' => $product_id]);
			}
		}

		//What we have to remove
		if (!empty($remove)) {
			parent::deleteData(new self(), ['element_id' => $remove, 'product_id' => $product_id]);
		}

		//Update last modified
		parent::updateLastModified(new Product(), $product_id);
		parent::updateLastModified(new Element(), $add2, array_keys($update), $remove);
	}

	//Update relation table for given element
	//$values parameters are in value: ['element_id' => 'element_count', 'element_id2' => 'element_count', ...]
	public static function updateElement($element_id, $values, $used = false) {
		$add = $add2 = [];
		if (!is_array($values)) {
			return;
		}

		//Get current list if needed
		if (!$used) {
			//Useful when first INSERT is used for element
			$used = array();
		} else if ($used == true) {
			//Lets check which elements are currently set for element
			$data = self::getProducts($element_id);
			$used = [];
			foreach ($data as $d) {
				$used[$d['ElementProduct']->product_id] = $d['ElementProduct']->element_count;
			}
		}
		//Else use value from user

		$update = []; $remove = []; $add = [];
		foreach ($values as $product_id => $element_count) {
			//Check if record already is in DB = update or delete
			if (isset($used[$product_id])) {
				//Check if empty
				if (empty($element_count)) {
					$remove[] = $product_id;
				} else {
					if ($used[$product_id] != $element_count) {
						$update[$product_id] = $element_count;
					}
				}
			} else if (!empty($element_count)) {
				$add[] = [$element_id, $product_id, $element_count];
				$add2[] = $product_id;
			}
		}
		//Remove currently used elements whic are not in new request
		foreach ($used as $k => $e) {
			if (!isset($values[$k])) {
				$remove[] = $k;
			}
		}

		//What we have to add
		if (!empty($add)) {
			//Add elements to database
			parent::insertData(new self(), ['element_id', 'product_id', 'element_count'], $add);
		}

		//What we have to update
		if (!empty($update)) {
			//Update one by one
			foreach ($update as $product_id => $value) {
				parent::updateData(new self(), ['element_count' => $value], ['product_id' => $product_id, 'element_id' => $element_id, 'revision_id' => 0, 'deleted' => 0]);
			}
		}

		//What we have to remove
		if (!empty($remove)) {
			parent::deleteData(new self(), ['element_id' => $element_id, 'product_id' => $remove, 'revision_id' => 0, 'deleted' => 0]);
		}

		//Update last modified
		parent::updateLastModified(new Element(), $element_id);
		parent::updateLastModified(new Product(), $add2, array_keys($update), $remove);
	}
}
