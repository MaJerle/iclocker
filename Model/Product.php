<?php

namespace Model;

use \Inc\Model;
use \Model\ProductSubproduct;

/**
 * A coolection of functions that deal with Blind management
 * @package default
 */
class Product extends Model {
	//Set table name
	public $tableName = 'products';

	//Format associations
	public $associations = [
		'belongsTo' => [
			'Collection' => [
				'foreignKey' => 'collection_id',
				'conditions' => [
					'Collection.deleted' => 0
				],
				'counterCache' => [
					'products_count' => [
						'Product.deleted' => 0,
						'Product.revision_id' => 0
					]
				],
			]
		],
		'manyToMany' => [
			'Element' => [
				'joinModel' => 'ElementProduct',
				'foreignKey' => 'product_id',
				'associationForeignKey' => 'element_id',
				'conditions' => [
					'Element.deleted' => 0
				],
				'changes' => false
			],
			'Subproduct' => [
				'joinModel' => 'ProductSubproduct',
				'foreignKey' => 'product_id',
				'associationForeignKey' => 'subproduct_id',
				'conditions' => [
					'Subproduct.deleted' => 0
				],
				'changes' => false
			]
		],
		'hasMany' => [
			'ElementProduct' => [
				'foreignKey' => 'product_id',
				'changes' => true
			]
		]
	];

	//Virtual fields
	public $virtualFields = [
		'elements_count' => '(SELECT COUNT(*) AS elements_count FROM elements_products as ElementProduct
								JOIN elements AS Element
								ON Element.id = ElementProduct.element_id
								WHERE ElementProduct.product_id = Product.id AND Element.deleted = 0 AND Element.revision_id = 0 AND ElementProduct.revision_id = 0 AND ElementProduct.deleted = 0)',
		'elements_count_all' => '(SELECT SUM(ElementProduct.element_count) AS elements_count FROM elements_products AS ElementProduct 
								JOIN elements AS Element
								ON Element.id = ElementProduct.element_id
								WHERE ElementProduct.product_id = Product.id AND Element.deleted = 0 AND Element.revision_id = 0 AND ElementProduct.revision_id = 0 AND ElementProduct.deleted = 0)'
	];

	//Validation errors
	private static $__validationErrors = [];
	//Columns for SQL operations
	private static $__tableColumns = ['name', 'description', 'quantity'];

	//List all products
	public static function getProducts($product_id = null, $collection_id = null, $options = []) {
		$options = array_merge([
			'type' => 'all',
			'order' => 'Product.name',
			'conditions' => [],
			'contain' => [],
			'changes' => false,
			'revision' => false
		], $options);
		if (!$options['revision']) {
			$options['conditions']['Product.revision_id'] = 0;
		}
		if (!$options['changes']) {
			$options['conditions']['Product.deleted'] = 0;
		}

		//Get collection ID
		$collection_id = parent::collection_id($collection_id);
		
		//Check conditions
		if ($product_id) {
			$options['conditions']['Product.id'] = $product_id; 
		}
		if ($collection_id) {
			$options['conditions']['Product.collection_id'] = $collection_id;
		}

		//Get products
		return parent::$db->selectEx(new self(), $options);
	}

	//Get product by given ID
	public static function getProduct($product_id, $collection_id = null, $options = []) {
		return self::getProducts($product_id, $collection_id, array_merge($options, ['type' => 'first']));
	}

	//List all products
	public static function getProductsWithElements($product_id = null, $collection_id = null, $options = []) {
		$options = array_merge([
			'type' => 'all',
			'order' => 'Product.name',
			'conditions' => [
				'Product.deleted' => 0
			],
			'contain' => ['Element'],
			'revision' => false
		], $options);
		if (!$options['revision']) {
			$options['conditions']['Product.revision_id'] = 0;
		}

		//Get collection ID
		$collection_id = parent::collection_id($collection_id);
		
		//Check conditions
		if ($product_id) {
			$options['conditions']['Product.id'] = $product_id; 
		}
		if ($collection_id) {
			$options['conditions']['Product.collection_id'] = $collection_id;
		}

		//Get products
		return parent::$db->selectEx(new self(), $options);
	}

	//Get product by given ID
	public static function getProductWithElements($product_id, $collection_id = null) {
		return self::getProductsWithElements($product_id, $collection_id, ['type' => 'first']);
	}

	//Insert new data to product table and relation table if exists
	public static function insert($data) {
		//Check validation
		if (!self::validateInsert($data)) {
			return false;
		}

		//Try to insert
		$insID = parent::insertData(new self(), parent::formatColumns(self::$__tableColumns, $data));

		//Update elements
		if ($insID && isset($data['element'])) {
			//Update relation table between products and elements
			ElementProduct::updateProduct($insID, $data['element'], false);
		}

		//Update subproducts
		if ($insID && isset($data['subproduct'])) {
			//Update relation table between products and subproducts
			ProductSubproduct::updateProduct($insID, $data['subproduct'], false);
		}

		return $insID;
	}

	//Update product
	public static function update($product_id, $data) {
		//Check validation
		if (!self::validateUpdate($data)) {
			return false;
		}

		//Try to update
		$success = parent::updateData(new self(), parent::formatColumns(self::$__tableColumns, $data), ['id' => $product_id, 'collection_id' => parent::collection_id()]);

		//Update elements
		if ($success && isset($data['element'])) {
			//Update relation table between products and elements, set parameter to true so DB will check for old relations first
			ElementProduct::updateProduct($product_id, $data['element'], true);
		}

		//Update subproducts
		if ($success && isset($data['subproduct'])) {
			//Update relation table between products and subproducts
			ProductSubproduct::updateProduct($product_id, $data['subproduct'], true);
		}

		return $success;
	}

	//Delete product with given ID
	public static function delete($product_id) {
		return parent::deleteData(new self(), ['id' => $product_id]);
	}

	//Build product = decrease elements
	public static function build($product_id, $product_count, $elements) {
		if ($product_count < 1 || !is_array($elements)) {
			return false;
		}

		//Get product
		$product = self::getProduct($product_id, null, ['contain' => ['Element']]);
		if (!$product) {
			return false;
		}

		//Merge together what is required
		foreach ($product['Element'] as $e) {
			if (!isset($elements[$e->id])) {
				$elements[$e->id] = $e->ElementProduct->element_count;
			}
		}

		//Check data first
		foreach ($elements as $k => $v) {
			$found = false;
			foreach ($product['Element'] as $e) {
				if ($e->id == $k) {
					$found = true;
					break;
				}
			}
			if (!$found) {
				unset($elements[$k]);
				continue;
			}
			$elements[$k] = intval($v) * intval($product_count);
		}

		//Set new values for elements
		foreach ($elements as $k => $v) {
			Element::increaseQuantity($k, -$v, sprintf(__('Product %s has been made.'), $product['Product']->name));
		}

		return true;
	}

	//Validate for insert operation
	public static function validateInsert($data) {
		$v = parent::getValidationObject($data);

		//Set rules
		$v->rule('required', 'name')->message(__('Name is required!'))
		  ->rule('lengthMin', 'name', 1)->message(__('Name size is too small!'))
		  ->rule('relationData', 'element', 'int')->message(__('Incorrect data format for product elements!'))
		  ->rule('relationData', 'subproduct', 'int')->message(__('Incorrect data format for product subproducts!'));

		//Validate
		return parent::validate($v, self::$__validationErrors);
	}

	//Validate for insert operation
	public static function validateUpdate($data) {
		$v = parent::getValidationObject($data);

		//Set rules
		$v->rule('optional', 'name')
		  ->rule('lengthMin', 'name', 1)->message(__('Name size is too small!'))
		  ->rule('relationData', 'element', 'int')->message(__('Incorrect data format for product elements!'))
		  ->rule('relationData', 'subproduct', 'int')->message(__('Incorrect data format for product subproducts!'));

		//Validate
		return parent::validate($v, self::$__validationErrors);
	}

	//Get validation errors
	public static function getValidationErrors() {
		return self::$__validationErrors;
	}
}
