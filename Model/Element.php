<?php

namespace Model;

use \Inc\Model;
use \Model\Comment;

/**
 * A coolection of functions that deal with Blind management
 * @package default
 */
class Element extends Model {
	//MUST NOT BE STATIC!
	//Table name
	public $tableName = 'elements';

	//Format associations
	public $associations = [
		'belongsTo' => [
			'Category' => [
				'foreignKey' => 'category_id',
				'conditions' => [
					'Category.deleted' => 0
				],
				'counterCache' => [
					'elements_count' => [
						'Element.deleted' => 0,
						'Element.revision_id' => 0
					]
				]
			],
			'Collection' => [
				'foreignKey' => 'collection_id',
				'conditions' => [
					'Category.deleted' => 0
				],
				'counterCache' => [
					'elements_count' => [
						'Element.deleted' => 0,
						'Element.revision_id' => 0
					]
				]
			]
		],
		'manyToMany' => [
			'Property' => [
				'joinModel' => 'ElementProperty',
				'foreignKey' => 'element_id',
				'associationForeignKey' => 'property_id',
				'conditions' => [
					'Property.deleted' => 0
				],
				'changes' => false
			],
			'Product' => [
				'joinModel' => 'ElementProduct',
				'foreignKey' => 'element_id',
				'associationForeignKey' => 'product_id',
				'conditions' => [
					'Product.deleted' => 0
				],
				'changes' => false
			]
		],
		'hasMany' => [
			'ElementProperty' => [
				'foreignKey' => 'element_id',
				'changes' => true
			],
			'ElementProduct' => [
				'foreignKey' => 'element_id',
				'changes' => true
			],
			'Comment' => [
				'foreignKey' => 'foreign_id',
				'conditions' => [
					'Comment.deleted' => 0,
					'Comment.model' => Comment::MODEL_ELEMENT
				]
			]
		]
	];

	//Validation errors
	private static $__validationErrors = [];
	//Columns for SQL operations
	private static $__tableColumns = ['name', 'quantity', 'warning_quantity', 'description', 'category_id', 'collection_id'];

	//Lists all elements
	public static function getElements($element_id = null, $category_id = null, $collection_id = null, $options = []) {
		$options = array_merge([
			'type' => 'all',
			'conditions' => [],
			'joins' => [],
			'recursive' => 2,
			'changes' => false,
			'order' => 'Element.name ASC',
			'revision' => false
		], $options);
		if (!$options['revision']) {
			$options['conditions']['Element.revision_id'] = 0;
		}
		if (!$options['changes']) {
			$options['conditions']['Element.deleted'] = 0;
		}

		//Get collection ID
		$collection_id = parent::collection_id($collection_id);

		//What to retrieve
		if (!isset($options['contain'])) {
			$options['contain'] = [
				'Property',
				'Product',
				'Category'
			];
		}

		//Check conditions
		if ($element_id) {
			$options['conditions']['Element.id'] = $element_id;
		}
		if ($category_id) {
			$options['conditions']['Element.category_id'] = $category_id;
		}
		if ($collection_id) {
			$options['conditions']['Element.collection_id'] = $collection_id;
		}

		//Check for property filter
		$pID = parent::$db->res(parent::$app->request()->get('property_id', false));
		$pVAL = parent::$db->res(parent::$app->request()->get('property_value', false));
		$PropertyFilter = false;
		if ($pID && $pVAL) {
			$PropertyFilter = true;

			//Set conditions
			$options['conditions']['ElementProperty.property_id'] = $pID;
			$options['conditions']['ElementProperty.property_value'] = $pVAL;

			//Set joins
			$options['joins'][] = [
				'type' => 'INNER',
				'on' => 'Element.id = ElementProperty.element_id',
				'model' => 'ElementProperty'
			];
			$options['joins'][] = [
				'type' => 'INNER',
				'on' => 'Property.id = ElementProperty.property_id',
				'model' => 'Property',
				'conditions' => [
					'Property.deleted' => 0
				]
			];

			//Contain new
			$options['contain'][] = 'ElementProperty';
		}
 
		//Make a search
		return parent::$db->selectEx(new self(), $options);
	}

	//Get element by specific ID
	public static function getElement($element_id, $category_id = null, $collection_id = null, $options = []) {
		return self::getElements($element_id, null, null, array_merge(['type' => 'first', 'contain' => ['Category']], $options));
	}

	//Get element by specific ID
	public static function getElementsIds($element_id = null, $category_id = null, $collection_id = null, $options = []) {
		$options = array_merge($options, ['contain' => []]);
		$elements = self::getElements($element_id, null, null, $options);

		$ret = [];
		if ($elements) {
			foreach ($elements as $e) {
				$ret[] = $e['Element']->element_id;
			}
		}

		return $ret;
	}

	//Insert new element with properties/products to database
	public static function insert($category_id, $data, $onlyValidate = false) {
		//Validate input data
		if (!self::validateInsert($data)) {
			return false;
		}
		//Check for category
		if (!Category::getCategory($category_id)) {
			return false;
		}
		if ($onlyValidate) {
			return true;
		}

		//Format useful data for insert from DATA
		$insert = array_merge(parent::formatColumns(self::$__tableColumns, $data), ['category_id' => $category_id, 'collection_id' => parent::collection_id()]);

		//Check quantity
		if (isset($insert['warning_quantity']) && intval($insert['warning_quantity']) == 0) {
			$insert['warning_quantity'] = 0;
		}

		//Add element into database
		$insID = parent::insertData(new self(), $insert);

		//Check property values
		if ($insID && isset($data['property'])) {
			//Add property to database
			ElementProperty::updateElement($insID, $data['property'], false);
		}

		//Check product values
		if ($insID && isset($data['product'])) {
			//Add products
			ElementProduct::updateElement($insID, $data['product'], false);
		}

		return $insID;
	}

	//Insert new element with properties to database
	public static function update($element_id, $data) {
		//Validate input data
		if (!self::validateUpdate($data)) {
			return false;
		}

		//Get elements
		$element = self::getElement($element_id, null, null, [
			'contain' => []
		]);

		//Try to update
		$success = parent::updateData(new self(),
			parent::formatColumns(self::$__tableColumns, $data),
			['id' => $element_id, 'collection_id' => parent::collection_id(), 'category_id' => $element['Element']->category_id],
			['foreignkey_update' => true]
		);

		//Check property values
		if ($success && isset($data['property'])) {
			//Add parameters to database
			ElementProperty::updateElement($element_id, $data['property'], true);
		}

		//Check product values
		if ($success && isset($data['product'])) {
			//Update element with products
			ElementProduct::updateElement($element_id, $data['product'], true);
		}

		return $success;
	}

	//Duplicate element
	public static function duplicate($element_id) {
		//Get element first
		$element = self::getElement($element_id, null, null, ['contain' => ['Property']]);
		if (!$element) {
			return false;
		}

		//Format data for insert
		$data = parent::formatColumns(array_merge(self::$__tableColumns, ['collection_id', 'category_id']), $element['Element']);
		$data['name'] .= '_COPY';
		$data['copy_id'] = $element_id;

		//Insert to database
		if (!($id = parent::insertData(new self(), $data))) {
			return false;
		}

		//Merge properties too
		$data = [];
		foreach ($element['Property'] as $p) {
			$data[$p->id] = $p->ElementProperty->property_value;
		}
		if (count($data)) {
			ElementProperty::updateElement($id, $data, true);
		}

		return true;
	}

	//Updates element quantity by factor of new offset value
	public static function increaseQuantity($element_id, $new_offset_value = 1, $reason = '') {
		$element = self::getElement($element_id, null, null, []);
		if (!$element) {
			return false;
		}
		if (($element['Element']->quantity + $new_offset_value) < 0) {
			$new_offset_value = - $element['Element']->quantity;
		}
		
		return parent::updateData(new self(),
			[
				'quantity = quantity ' . ($new_offset_value < 0 ? '-' : '+') . ' ' . abs($new_offset_value),
				'revision_reason' => $reason
			],
			['id' => $element_id, 'collection_id' => parent::collection_id()]
		);
	}

	//Deletes single element from database
	public static function delete($element_id) {
		return parent::deleteData(new self(), ['id' => $element_id, 'collection_id' => parent::collection_id()]);
	}

	//Change category ID
	public static function changeCategory($oldCat, $newCat) {
		if (!$oldCat || !$newCat) {
			return false;
		}

		//Get categories
		$oldCat = Category::getCategory($oldCat);
		$newCat = Category::getCategory($newCat);

		if (!$oldCat || !$newCat) {
			return false;
		}
		return parent::updateData(new self(),
			['category_id' => $newCat['Category']->id],
			['category_id' => $oldCat['Category']->id, 'collection_id' => parent::collection_id()],
			['foreignkey_update' => true]
		);
	}

	//Validate for insert operation
	public static function validateInsert($data) {
		$v = parent::getValidationObject($data);

		//Set rules
		$v->rule('required', 'name')->message(__('Name is required!'))
		  ->rule('lengthMin', 'name', 1)->message(__('Name cannot be empty!'))
		  ->rule('required', 'quantity')->message(__('Quantity is required!'))
		  ->rule('integer', 'quantity')->message(__('Quantity must be integer!'))
		  ->rule('relationData', 'property', false)->message(__('Incorrect data format for element properties!'))
		  ->rule('relationData', 'product', 'int')->message(__('Incorrect data format for element products!'));

		//Validate
		return parent::validate($v, self::$__validationErrors);
	}

	//Validate for insert operation
	public static function validateUpdate($data) {
		$v = parent::getValidationObject($data);

		//Set rules
		$v->rule('optional', 'name')
		  ->rule('lengthMin', 'name', 1)->message(__('Name cannot be empty!'))
		  ->rule('optional', 'quantity')
		  ->rule('integer', 'quantity')->message(__('Quantity must be integer!'))
		  ->rule('relationData', 'property', false)->message(__('Incorrect data format for element properties!'))
		  ->rule('relationData', 'product', 'int')->message(__('Incorrect data format for element products!'));

		//Validate
		return parent::validate($v, self::$__validationErrors);
	}

	//Get validation errors
	public static function getValidationErrors() {
		return self::$__validationErrors;
	}
}
