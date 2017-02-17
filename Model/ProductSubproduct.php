<?php

namespace Model;

use \Inc\Model;

/**
 * A coolection of functions that deal with Blind management
 * @package default
 */
class ProductSubproduct extends Model {
	//Set table name
	public $tableName = 'products_subproducts';

	//Format associations
	public $associations = [
		'belongsTo' => [
			'Product' => [
				'foreignKey' => 'product_id',
				'conditions' => [
					'Product.deleted' => 0
				]
			],
			'Subproduct' => [
				'foreignKey' => 'subproduct_id',
				'conditions' => [
					'Subproduct.deleted' => 0
				]
			]
		]
	];
	
	//Get all subproducts for desired product in endless depth
	public static function getSubproducts($product_id, $level = -1, $currentLevel = 1) {
		//Check status
		if ($currentLevel > $level && $level >= 0) {
			return [];
		}

		$products = ProductSubproduct::find('all', [
			'contain' => ['Subproduct'],
			'conditions' => [
				'ProductSubproduct.deleted' => 0,
				'ProductSubproduct.revision_id' => 0,
				'ProductSubproduct.product_id' => $product_id
			]
		]);

		foreach ($products as &$product) {
			//Get children
			$product['children'] = self::getSubProducts($product['Subproduct']->id, $level, $currentLevel + 1);
		}

		return $products;
	}

	//Get list of properties IDS currently used on this element
	public static function getProductsIds($element_id) {
		return parent::$db->getIds(self::getSubproducts($element_id, 1), 'ProductSubproduct.subproduct_id');
	}

	//Update relation table for given product
	//$values parameters are in value: ['subproduct_id' => 'subproduct_count', 'subproduct_id2' => 'subproduct_count2', ...]
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
			$usedElements = self::getProductsIds($product_id);
		}

		//Else use value from user
		$update = []; $remove = []; $add = [];
		foreach ($values as $subproduct_id => $subproduct_count) {
			//Check if record already is in DB = update or delete
			if (in_array($subproduct_id, $usedElements)) {
				//Check if empty
				if (empty($subproduct_count)) {
					$remove[] = $subproduct_id;
				} else {
					$update[$subproduct_id] = $subproduct_count;
				}
			} else if (!empty($subproduct_count)) {
				$add[] = [$product_id, $subproduct_id, $subproduct_count];
				$add2[] = $subproduct_id;
			}
		}
		//Remove currently used elements whic are not in new request
		foreach ($usedElements as $e) {
			if (!isset($values[$e])) {
				$remove[] = $e;
			}
		}

		//What we have to add
		if (!empty($add)) {
			//Add elements to database
			parent::insertData(new self(), ['product_id', 'subproduct_id', 'subproduct_count'], $add);
		}

		//What we have to update
		if (!empty($update)) {
			//Update one by one
			foreach ($update as $subproduct_id => $value) {
				parent::updateData(new self(), ['subproduct_count' => $value], ['product_id' => $product_id, 'subproduct_id' => $subproduct_id]);
			}
		}

		//What we have to remove
		if (!empty($remove)) {
			parent::deleteData(new self(), ['subproduct_id' => $remove, 'product_id' => $product_id]);
		}

		//Update last modified
		parent::updateLastModified(new Product(), [$product_id], $add2, array_keys($update), $remove);
	}

	//Validation errors
	private static $__validationErrors = [];
	//Columns for SQL operations
	private static $__tableColumns = ['product_id', 'subproduct_id'];
}
