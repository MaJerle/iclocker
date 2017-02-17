<?php

namespace Controller;

use \Model\Element;
use \Model\Product;
use \Model\ElementProperty;
use \Model\ElementProduct;
use \Model\Collection;
use \Model\Comment;
use \Model\Subproduct;
use \Model\ProductSubproduct;
use \Inc\Model;

class ProductsController extends Base {

	/**
	 * Route /products
	 *
	 * @param $app: Application context
	 */
	public function index($app, $collection_id = null) {
		$products = Product::getProducts();

		$app->view()->set('products', $products);

		$app->setTitle(__('Products'));
		return $app->render('products_index.html');
	}

	/**
	 * Route /products/add
	 *
	 * @param $app: Application context
	 */
	public function add($app, $collection_id = null) {
		$values = $app->request()->post();
		if ($values) {
			//Check for elements in product
			if (isset($values['element_id']) && isset($values['element_value'])) {
				$values['element'] = [];
				foreach ($values['element_id'] as $key => $value) {
					if (!empty($value) && !empty($values['element_value'][$key])) {
						$values['element'][$value] = $values['element_value'][$key];
					}
				}
			}
			//Check for subproducts before save
			if (isset($values['subproduct_id']) && isset($values['subproduct_value'])) {
				$values['subproduct'] = [];
				foreach ($values['subproduct_id'] as $key => $value) {
					if (!empty($value) && !empty($values['subproduct_value'][$key])) {
						$values['subproduct'][$value] = $values['subproduct_value'][$key];
					}
				}
			}

			//Check data
			if (!isset($values['element'])) {
				$values['element'] = [];
			}

			//Try to add product
			if (($id = Product::insert($values))) {
				$app->flashSuccess(__('Product has been successfully created.'));

				//Add comment
				$app->commentAdd(Comment::MODEL_PRODUCT, $id, $values);
				
				if (isset($values['saveandnew'])) {
					$app->redirect($app->urlFor('products_add'));
				} else {
					$app->redirect($app->urlFor('products_list'));
				}
			} else {
				$app->flashErrorNow(__('Problems with creating product!'));
			}

			//Set values to view
			$app->view()->set('values', $values);
		}

		//Get elements
		$elements = Element::getElements(null, null, null, ['contain' => []]);

		//Get products 
		$products = Product::getProducts();

		$app->view->set('elements', $elements);
		$app->view->set('products', $products);
		$app->view()->set('action', 'add');

		$app->setTitle(__('Add product'));
		return $app->render('products_add_edit.html');
	}

	/**
	 * Route /products/edit/:product_id
	 *
	 * @param $app: Application context
	 * @param $product_id: Collection id to edit
	 */
	public function edit($app, $collection_id = null, $product_id = null) {
		$record = $app->validate(Product::getProduct($product_id, null, ['contain' => ['Subproduct']]));

		$values = $app->request()->post();
		if ($values) {
			$values['element'] = [];
			$values['subproduct'] = [];

			//Check for elements in product and format them before save
			if (isset($values['element_id']) && isset($values['element_value'])) {
				foreach ($values['element_id'] as $key => $value) {
					if (!empty($value) && !empty($values['element_value'][$key])) {
						$values['element'][$value] = $values['element_value'][$key];
					}
				}
			}
			//Check for subproducts before save
			if (isset($values['subproduct_id']) && isset($values['subproduct_value'])) {
				foreach ($values['subproduct_id'] as $key => $value) {
					if (!empty($value) && !empty($values['subproduct_value'][$key])) {
						$values['subproduct'][$value] = $values['subproduct_value'][$key];
					}
				}
			}

			//Try to edit collection
			if (Product::update($product_id, $values)) {
				$app->flashSuccess(__('Product has been successfully updated.'));
				if (isset($values['submit'])) {
					$app->redirect($app->urlFor('products_list'));
				} else {
					$app->redirect($app->urlFor('products_edit', ['product_id' => $product_id]));
				}
			} else {
				$app->flashErrorNow(__('Problems with updating product!'));
			}
		} else {
			//Get product
			$values = $record['Product'];

			//Get elements used with product 
			$usedElements = ElementProduct::getElements($product_id);
			$values->element = [];
			if ($usedElements) {
				foreach ($usedElements as $e) {
					$values->element[$e['ElementProduct']->element_id] = $e['ElementProduct']->element_count;
				}
			}

			//Check subproducts, go level 1 only.
			$subProducts = ProductSubproduct::getSubproducts($product_id, 1);
			$values->subproduct = [];
			if ($subProducts) {
				foreach ($subProducts as $p) {
					$values->subproduct[$p['ProductSubproduct']->subproduct_id] = $p['ProductSubproduct']->subproduct_count;
				}
			}
		}

		//Get elements
		$elements = Element::getElements(null, null, null, ['contain' => ['Category']]);

		//Get other products
		$products = Product::getProducts(null, null, [
			'conditions' => [
				'Product.id !=' => $product_id
			]
		]);

		//Set values to view
		$app->view()->set('record', $record);
		$app->view()->set('values', $values);
		$app->view()->set('elements', $elements);
		$app->view()->set('products', $products);

		//Calculate how many products we can create using saved elements
		$productionCount = 0;
		if (isset($usedElements) && $usedElements) {
			$productionCount = [];
			foreach ($usedElements as $element) {
				$productionCount[] = floor($element['Element']->quantity / $element['ElementProduct']->element_count);
			}
			$productionCount = min($productionCount);
			$app->view()->set('usedElements', $usedElements);
		}

		//Set comments for view
		$app->commentSetView(Comment::MODEL_PRODUCT, $product_id);

		$app->view->set('productionCount', $productionCount);
		$app->view()->set('action', 'edit');

		$app->setTitle(__('Edit product'));
		return $app->render('products_add_edit.html');
	}

	/**
	 * Imports BOM file of elements and links them to product if available
	 *
	 * Route /products/import/:product_id
	 *
	 * @param $app: Application context
	 * @param $product_id: Product id to delete
	 */
	public function import_bom($app, $collection_id = null, $product_id = null) {
		$file = __DIR__ . '/../DigitalECG.csv';
		$lines = explode("\n", file_get_contents($file));
		
		$csv = [];
		foreach ($lines as $line) {
			$csv[] = str_getcsv($line);
		}

		//Get header
		$header = array_shift($csv);
		$headerCount = count($header);

		//Find name and quantity keys
		$nameKey = false;
		foreach ($header as $k => $v) {
			if ($v == 'Name' || $v == 'Comment') {
				$nameKey = $k;
				break;
			}
		}
		$quantityKey = false;
		foreach ($header as $k => $v) {
			if ($v == 'Quantity') {
				$quantityKey = $k;
				break;
			}
		}

		//Check empty lines and remove them
		foreach ($csv as $k => $l) {
			if (count($l) != $headerCount) {
				unset($csv[$k]);
			}
		}

		//Get all elements with properties
		$elements = Element::getElements(null, null, null, [
			'contain' => ['Property', 'Category'],
			'order' => 'Element.name ASC'
		]);

		$keys = [];
		if ($nameKey !== false) {
			$keys[] = $nameKey;
		}
		if ($quantityKey !== false) {
			//$keys[] = $quantityKey;
		}

		$out = [];
		foreach ($csv as $k => $c) {
			$found = false;
			foreach ($c as $val) {
				/*if (!isset($c[$v])) {
					continue;
				}

				$val = $c[$v];
				*/

				if (empty($val)) {
					continue;
				}

				foreach ($elements as $element) {
					//First check properties
					foreach ($element['Property'] as $p) {
						if (strcmp(strtoupper($p->ElementProperty->property_value), strtoupper($val)) == 0) {
							$found = $element['Element'];
							break;
						}
					}
					if ($found) {
						break;
					}

					//Check name and description
					if (strcmp(strtoupper($element['Element']->name), strtoupper($val)) == 0) {
						$found = $element['Element'];
						break;
					}
					if (!is_numeric($val) && strpos(strtoupper($element['Element']->description), strtoupper($val)) !== false) {
						$found = $element['Element'];
						break;
					}
				}
			}

			$out[$k - 1] = [
				'name' => $c[$nameKey],
				'quantity' => $c[$quantityKey],
				'element' => $found
			];
		}

		$app->view()->set('values', $out);
		$app->view()->set('elements', $elements);

		return $app->render('products_import_bom.html');
	}

	/**
	 * Route /products/delete/:product_id
	 *
	 * @param $app: Application context
	 * @param $product_id: Product id to delete
	 */
	public function delete($app, $collection_id = null, $product_id = null) {
		$status = Product::delete($product_id);

		if ($app->request()->isAjax()) {
		//	return $app->toJSON(['Status' => $status], true, $app->toStatusCode($status));
		}

		if ($status) {
			$app->flashSuccess(__('Product has been successfully deleted.'));
		} else {
			$app->flashError(__('Problems with deleting product!'));
		}
		$app->redirect($app->urlFor('products_list'));
	}

	/**
	 * Update product with POST method
	 *
	 * Route /products/update/:product_id
	 *
	 * @param $app: Application context
	 * @param $id: Property to update
	 */
	public function update($app, $collection_id = null, $id = null) {
		//Not succedded
		$success = false;

		//Get element
		$result = Product::getProduct($id);
		if ($result) {			
			//Update element
			$success = Product::update($id, $app->getPostUpdateFields());
		}

		//Return status
		return $app->toJSON(['Success' => $success], true, $app->toStatusCode($success));
	}

	/**
	 * An update of elements to build products
	 *
	 * Route /products/build/:product_id
	 *
	 * @param $app: Application context
	 * @param $id: Property to update
	 */
	public function build($app, $collection_id = null, $id = null) {
		$app->validate($app->request()->isAjax());

		//Check success
		$values = $app->request()->post();
		if ($values) {
			if (Product::build($id, intval(isset($values['product_count']) ? $values['product_count'] : 0), isset($values['element']) ? $values['element'] : [])) {
				$app->flashSuccessNow(__('Elements has been decreased in database for number of selected products.'));
				$values = false;
			} else {
				$app->flashErrorNow(__('Problems trying to decrease number of elements in database!'));
			}
		}

		//Get product with elements
		$product = $app->validate(Product::getProduct($id, null, ['contain' => ['Element']]));

		if (!$values) {
			$values = [
				'product_count' => 0,
				'element' => []
			];
			foreach ($product['Element'] as $e) {
				$values['element'][$e->id] = 0; //$e->ElementProduct->element_count;
			}
		}

		//Push data to view
		$app->view()->set('product', $product);
		$app->view()->set('values', $values);

		return $app->render('products_build.html');
	}


	private function xml2array($xmlObject, $out = []) {
		$xmlArray = json_decode(json_encode((array)$xmlObject), TRUE);
		return $xmlArray;
		
		foreach ($xmlArray as $k => $v) {

		}

		return $out;
	}
}
