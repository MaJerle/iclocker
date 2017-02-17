<?php

namespace Controller;

use \Model\Element;
use \Model\Category;
use \Model\Property;
use \Model\Order;
use \Model\CategoryProperty;
use \Model\Collection;
use \Model\Product;
use \Inc\Model;

class ImporterController extends Base {

	/**
	 * Route /import
	 *
	 * @param $app: Application context
	 */
	public function index($app) {
		var_dump('OPA'); exit;
		//Import elements
		$elements = json_decode(file_get_contents('mesicomponents.json'));
		$elements = $elements->Values;

		//Delete everything first
		$coll = Collection::find('first', [
			'conditions' => [
				'Collection.name' => 'MESI collection'
			]
		]);
		$app->collection_id = $coll['Collection']->id;

		//Delete first
		if ($coll) {
			Collection::delete($coll['Collection']->id);
		}
		//Create collection
		$collId = Collection::insert(['name' => 'MESI collection', 'description' => 'MESI Development collection']);
		if (!$collId) {
			print 'CAN NOT CREATE MESI COLLECTION!'; exit;
		}
		$app->collection_id = $collId;

		//Get all available element info
		$properties = [];
		$categories = [];
		$products = [];
		foreach ($elements as $element) {
			//Category
			if (!isset($categories[$element->Category->category_name])) {
				$categories[$element->Category->category_name] = $element->Category->category_name;
			}

			//Properties
			foreach ($element->Property as $p) {
				if (!isset($properties[$p->property_name])) {
					$properties[$p->property_name] = $p->property_name;
				}
			}

			//Products
			foreach ($element->Product as $p) {
				if (!isset($products[$p->product_name])) {
					$products[$p->product_name] = $p->product_name;
				}
			}
		}

		//Insert properties
		foreach ($properties as $prop => $id) {
			$data = [
				'collection_id' => $collId,
				'name' => $prop,
				'description' => 'empty',
				'data_type' => '2',
				'unit' => 'Unknown',
				'category' => [],
				'option' => []
			];
			$properties[$prop] = Property::insert($data);
		}

		//Insert categories
		foreach ($categories as $cat => $id) {
			$data = [
				'collection_id' => $collId,
				'name' => $cat,
				'description' => 'empty',
				'property' => array_values($properties)
			];
			$categories[$cat] = Category::insert($data);
		}

		//Insert products
		foreach ($products as $prod => $id) {
			$data = [
				'collection_id' => $collId,
				'name' => $prod,
				'description' => 'empty'
			];
			$products[$prod] = Product::insert($data);
		}

		//Insert elements
		foreach ($elements as $element) {
			$data = [
				'collection_id' => $collId,
				'name' => $element->Element->element_name,
				'description' => $element->Element->element_comment,
				'quantity' => $element->Element->element_quantity,
				'product' => [],
				'property' => []
			];
			foreach ($element->Product as $p) {
				$data['product'][$products[$p->product_name]] = $p->ElementProduct->element_count;
			}
			foreach ($element->Property as $p) {
				$data['property'][$properties[$p->property_name]] = $p->ElementProperty->property_value;
			}
			Element::insert($categories[$element->Category->category_name], $data);
		}
		
		var_dump($categories, $properties, $products);
		exit;
	}

	/**
	 * Route /import/lpvo
	 *
	 * @param $app: Application context
	 */
	public function lpvo($app) {
		var_dump('NOPE!'); exit;
		//Import elements
		$elements = json_decode(file_get_contents('lpvocomponents.json'));

		//Delete everything first
		$coll = Collection::find('first', [
			'conditions' => [
				'Collection.name' => 'LPVO',
				'Collection.deleted' => 0,
				'Collection.revision_id' => 0
			],
			'contain' => []
		]);
		if (!$coll) {
			var_dump('NOT FOUND! EXIT!'); exit;
		}
		$app->collection_id = $coll['Collection']->id;
		$collId = $coll['Collection']->id;
		
		//Create properties
		$properties = ['SKU' => 0, 'Distributer' => 0, 'Proizvajalec' => 0, 'Koda proizvajalca' => 0];
		foreach ($properties as $k => $v) {
			$p = Property::getProperty(null, null, [
				'contain' => [],
				'conditions' => [
					'Property.name' => $k
				]
			]);
			if ($p) {
				$properties[$k] = $p['Property']->id;
			} else {
				$properties[$k] = Property::insert([
					'collection_id' => $collId,
					'name' => $k,
					'description' => 'empty',
					'data_type' => '1',
					'unit' => 'Unknown',
					'category' => [],
					'option' => []
				]);
			}
		}

		//Create category 
		$category = Category::getCategory(null, null, [
			'contain' => [],
			'conditions' => [
				'Category.name' => 'Uncategorized'
			]
		]);
		if ($category) {
			$category = $category['Category']->id;
		} else {
			$category = Category::insert([
				'collection_id' => $collId,
				'name' => 'Uncategorized',
				'description' => 'empty',
				'property' => array_values($properties)
			]);
		}

		$start = 900;
		ob_start();
		for ($i = $start; $i < count($elements) && ($i - $start) < 300; $i++) {
			$element = $elements[$i];
			$data = [
				'collection_id' => $collId,
				'name' => (string)$element->{'Koda proizvajalca'},
				'description' => $element->Opis,
				'quantity' => $element->Kolicina,
				'warning_quantity' => 0,
				'product' => [],
				'property' => []
			];

			foreach ($properties as $k => $v) {
				$data['property'][$v] = (string)$element->{$k};
			}
			$id = Element::insert($category, $data);
			var_dump($id);
			ob_flush();
		}
	}

	/**
	 * Update database
	 *
	 */
	public function update($app) {
		//var_dump('OPA'); exit;

		//Add columns and ignore response
		$app->db->query("ALTER TABLE categories_properties ADD COLUMN collection_id INT NOT NULL DEFAULT '0' AFTER revision_id;");
		$app->db->query("ALTER TABLE elementorders_properties ADD COLUMN collection_id INT NOT NULL DEFAULT '0' AFTER revision_id;");
		$app->db->query("ALTER TABLE elements_products ADD COLUMN collection_id INT NOT NULL DEFAULT '0' AFTER revision_id;");
		$app->db->query("ALTER TABLE elements_properties ADD COLUMN collection_id INT NOT NULL DEFAULT '0' AFTER revision_id;");
		$app->db->query("ALTER TABLE products_subproducts ADD COLUMN collection_id INT NOT NULL DEFAULT '0' AFTER revision_id;");

		$tables = [
			'categories_properties' => 'categories',
			'elementorders_properties' => 'properties',
			'elements_products' => 'elements',
			'elements_properties' => 'elements',
			'products_subproducts' => 'products'
		];

		foreach ($tables as $p => $s) {
			switch ($s) {
				case 'categories': $f = 'category_id'; break;
				case 'properties': $f = 'property_id'; break;
				case 'elements': $f = 'element_id'; break;
				case 'products': $f = 'product_id'; break;
			}

			//Go from table to table and scan values
			$data = $app->db->select('SELECT ' . $p . '.*, ' . $s . '.collection_id as ' . $s . '_collection_id FROM ' . $p . '
				LEFT JOIN ' . $s . ' ON
					' . $p . '.' . $f . ' = ' . $s . '.id
				WHERE ' . $p . '.collection_id = 0');

			if (!is_array($data)) {
				continue;
			}
			
			foreach ($data as $d) {
				if ($d->{$s . '_collection_id'}) {
					$app->db->update('UPDATE ' . $p . ' SET collection_id = "' . $d->{$s . '_collection_id'} . '" WHERE id = ' . $d->id);
				} else {
					$app->db->update('DELETE FROM ' . $p . ' WHERE id = ' . $d->id);
				} 
			}
		}

		var_dump('DONE!');
	}
}
