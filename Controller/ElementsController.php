<?php

namespace Controller;

use \Model\Element;
use \Model\Category;
use \Model\Product;
use \Model\ElementProduct;
use \Model\ElementProperty;
use \Model\CategoryProperty;
use \Model\Propertychoice;
use \Model\Elementorder;
use \Model\Orderelement;
use \Model\Comment;
use \Inc\Model;

class ElementsController extends Base {
	
	/**
	 * Route /elements
	 * Route /elements/:category_id
	 *
	 * @param $app: Application context
	 */
	public function index($app, $collection_id = null, $category_id = null) {
		$options = ['conditions' => []];

		//Check for search
		$search = $app->request()->get('search', false);
		if ($search !== false && strlen($search) > 2) {
			$options['conditions'][] = 'Element.name LIKE "%' . $app->db->res($search) . '%"';
		}

		//Check for product id
		$product_id = intval($app->request()->get('product_id', 0));
		$product = false;
		if ($product_id > 0) {
			$product = Product::getProduct($product_id, null, ['contain' => []]);
		}
		if ($product) {
			$options['conditions']['ElementProduct.product_id'] = $product_id;
			$options['conditions']['ElementProduct.revision_id'] = 0;
			$options['joins'][] = [
				'type' => 'INNER',
				'on' => 'ElementProduct.element_id = Element.id',
				'table' => 'elements_products AS ElementProduct'
			];
		}

		//Get elements
		$elements = [];
		if ($product || $app->isModal()) {
			$elements = Element::getElements(null, null, null, $options);
		}

		//Get categories
		$categories = Category::getCategories();

		$app->view()->set('elements', $elements);
		$app->view()->set('categories', $categories);
		$app->view()->set('product', $product);
		$app->view()->set('category_id', $category_id);

		$app->setTitle(__('Elements'));
		return $app->render('elements_index.html');
	}
	
	/**
	 * Route /elements_list_data
	 *
	 * @param $app: Application context
	 */
	public function index_data($app, $collection_id = null, $category_id = null) {
		//Get important input values
		$draw = $app->request()->get('draw', 1);
		$limit = $app->request()->get('length', 30);
		$start = $app->request()->get('start', 0);
		$search = $app->request()->get('search', false);
		$columns = $app->request()->get('columns', false);
		$order = $app->request()->get('order', false);
		$category_id = $app->request()->get('category_id', null);

		//Check for search
		$regex = false;
		if (is_array($search)) {
			$regex = $search['regex'];
			$search = $search['value'];
		}

		//Calculate page
		$page = (int)($start / $limit) + 1;

		//Check for ordering
		$outputOrder = '';
		if ($order && $columns) {
			foreach ($order as $o) {
				$i = intval($o['column']);
				$dir = strtoupper($o['dir']) == 'ASC' ? 'ASC' : 'DESC';
				if (isset($columns[$i])) {
					$data = $columns[$i]['data'];
					switch ($data) {
						case 'id': $data = 'Element.id'; break;
						case 'name': $data = 'Element.name'; break;
						case 'quan': $data = 'Element.quantity'; break;
						case 'warn_quan': $data = 'Element.warning_quantity'; break;
						case 'desc': $data = 'Element.description'; break;
						case 'created': $data = 'Element.created_at'; break;
						case 'cat': $data = 'Category.name'; break;
						default:
							$data = '';
					}
					if (!empty($data)) {
						$outputOrder = trim($outputOrder . ' ' . $data . ' ' . $dir);
					}
				}
			}
		}
		if (empty($outputOrder)) {
			$outputOrder = 'Element.name ASC';
		}

		//Format search options
		$options = [
			'conditions' => [],
			'limit' => $limit,
			'page' => $page,
			'order' => $outputOrder
		];

		//Set up category ID
		if ($category_id != null && is_numeric($category_id)) {
			$options['conditions']['Element.category_id'] = $category_id;
		}

		//Check for search
		if ($search !== false && strlen($search) > 1) {
			$search = $app->db->res($search);
			$tokens = preg_split('/(,)/', $search);

			//Fill search criteria
			$mainSearch = $search;
			$search = [];
			foreach ($tokens as $token) {
				$token = trim($token);
				if (strlen($token) > 1) {
					if (!in_array($token, $search)) {
						$search[] = $token;
					}
				}
			}

			//Format statement
			$group = [];
			foreach ($search as $s) {
				$group[] = 'ElementProperty.property_value LIKE \'%' . $s . '%\'';
			}

			//Get property values for elements with given search first
			$propertyValues = ElementProperty::find('all', [
				'contain' => [],
				'conditions' => [
					'ElementProperty.deleted' => 0,
					'ElementProperty.revision_id' => 0,
					'ElementProperty.collection_id' => $app->collection_id,
					'AND' => $group,
				]
			]);
			$elementIds = [];
			foreach ($propertyValues as $v) {
				$elementIds[] = $v['ElementProperty']->element_id;
			}
			$or = ['Element.id' => $elementIds];

			//Format element scan
			$out = [];
			foreach ($search as $s) {
				//Group of conditions
				$group = [
					'Element.id LIKE \'%' . $s . '%\'',
					'Element.name LIKE \'%' . $s . '%\'',
					'Element.description LIKE \'%' . $s . '%\'',
					'Element.quantity LIKE \'%' . $s . '%\'',
					'Element.warning_quantity LIKE \'%' . $s . '%\'',
					'Category.name LIKE \'%' . $s . '%\''
				];
				$out[] = ['OR' => $group];
			}
			$options['conditions'] = [
				'Element.revision_id' => 0,
				'OR' => [
					'Element.id' => $elementIds,
					'AND' => $out
				]
			];
		}

		$options['debug'] = false;

		//Get elements
		$elements = Element::getElements(null, $category_id, null, $options);

		$out = [];
		$i = $start + 1;
		foreach ($elements as $element) {
			$arr = [];

			//Element count
			$arr['count'] = $i++;

			//Set ID
			if ($app->isAdmin()) {
				$arr['id'] = $element['Element']->id;
			}

			//Set name
			$arr['name'] = '<div class="element_name inline_edit" data-what="name" data-url-func="elements_GetUpdateUrl" data-id="' . $element['Element']->id . '">' . $element['Element']->name . '</div>';

			//Set quantity
			$arr['quan'] = '<div style="display: flex;">
<div class="pull-left"><a class="element_quantity_set" id="element_quantity_set_' . $element['Element']->id . '" href="' . $app->urlFor('elements_quantity', ['element_id' => $element['Element']->id, 'quantity' => -1]) . '" data-element="' . $element['Element']->id . '"><i class="caret"></i></a></div>
<div class="pull-left" style="flex: 1;"><div class="element_quantity inline_edit" data-what="quantity" data-url-func="elements_GetUpdateUrl" data-id="' . $element['Element']->id . '" id="element_quantity_' . $element['Element']->id . '">' . $element['Element']->quantity . '</div></div></div>';

			//Show warning quantity
			if ($app->get_user_setting('show_element_warningquantity')) {
				$arr['war_quan'] = $element['Element']->warning_quantity;
			}

			//Show element category
			if ($app->get_user_setting('show_element_category')) {
				$arr['cat'] = '<div class="inline_edit" data-what="category_id" data-url-func="elements_GetUpdateUrl" data-inputtype="select" data-options-func="elements_GetOptions" data-id="' . $element['Element']->id . '" data-options-selected="' . $element['Element']->category_id . '">' . $element['Category']->name . '</div>';
			}

			//Show description
			if ($app->get_user_setting('show_element_description')) {
				$arr['desc'] = '<div class="inline_edit" data-url-func="elements_GetUpdateUrl" data-inputtype="textarea" data-what="description" data-id="' . $element['Element']->id . '">' . $element['Element']->description . '</div>';
			}

			//Show properties
			if ($app->get_user_setting('show_element_properties')) {
				$arr['prop'] = 'properties';
			}

			//Show element created at
			if ($app->isAdmin() || $app->get_user_setting('show_element_created')) {
				$arr['created'] = $app->get_event_user_datetime($element['CreatedBy'], $element['Element']->created_at);
			}

			//Actions
			$arr['actions'] = '<a href="' . $app->urlFor('comments_view', ['type' => $app->get_comments_type('element'), 'foreign_id' => $element['Element']->id]) . '" data-modal="modal_main" data-ajax-target="modal_main" class="ajax" data-changebrowserlink="no" title="' . __('View comments') . '">' . $app->fa('comments') . '</a>';

			$arr['actions'] .= ' <a href="' . $app->urlFor('elements_properties', ['element_id' => $element['Element']->id]) . '" data-modal="modal_main" data-ajax-target="modal_main" class="ajax" data-changebrowserlink="no" title="' . __('Manage element properties') . '">' . $app->fa('tags') . '</a>';
			$arr['actions'] .= ' <a href="' . $app->urlFor('elements_duplicate', ['element_id' => $element['Element']->id]) . '" title="' . __('Duplicate element') . '" data-confirm="' . __('Are you sure you wanna duplicate this element?') . '">' . $app->fa('copy') . '</a>';
			$arr['actions'] .= ' <a href="' . $app->urlFor('elements_order', ['element_id' => $element['Element']->id]) . '" data-modal="modal_main" data-ajax-target="modal_main" class="ajax" data-changebrowserlink="no" title="' . __('Add element to open order') . '">' . $app->fa('plus') . '</a>';
			$arr['actions'] .= ' <a href="' . $app->urlFor('elements_edit', ['element_id' => $element['Element']->id]) . '" title="' . __('Edit element') . '" class="ajax">' . $app->fa('pencil') . '</a>';
			$arr['actions'] .= ' <a href="' . $app->urlFor('elements_delete', ['element_id' => $element['Element']->id]) . '" title="' . __('Delete element and all dependencies') . '" data-confirm="' . sprintf(__('Are you sure you wanna delete element %s?'), $element['Element']->name) .'" class="delete">' . $app->fa('trash') . '</a>';



			//Additional, unused fields
			$arr['quantity_number'] = $element['Element']->quantity;
			$arr['quantity_warning_number'] = $element['Element']->quantity;

			//Add to output array
			$out[] = $arr;
		}

		//All the records
		$recordsTotal = Element::getElements(null, $category_id, null, ['type' => 'count']);

		//All the records with filter
		$recordsFiltered = Element::getElements(null, $category_id, null, array_merge($options, ['type' => 'count']));

		//Output data
		print json_encode([
			'draw' => $draw,
			'recordsTotal' => $recordsTotal,
			'recordsFiltered' => $recordsFiltered,
			'data' => $out,
			'iclocker' => [
				'page' => $page,
			],
			'order' => $outputOrder,
			'_get' => $_GET
		]);
	}

	/**
	 * Route /elements/add/:category_id
	 *
	 * @param $app: Application context
	 * @param $app: Category ID to add element to
	 */
	public function add($app, $collection_id = null, $category_id = null) {
		$inModal = $app->isModal();

		//Get categories
		$categories = Category::getCategories();

		//Set to view
		$app->view()->set('categories', $categories);
		$app->view()->set('in_modal', $inModal);

		//Check if category selected
		if (!$category_id) {
			if (count($categories) > 0) {
				$category_id = $categories[0]['Category']->id;
			}
			if (!$category_id) {
				return $app->render('elements_add_choose_category.html');
			}
		}

		//Find category
		$found = false;
		foreach ($categories as $c) {
			if ($c['Category']->id == $category_id) {
				$found = true;
				break;
			}
		}
		$app->validate($found);

		//Check if post is active
		$fromForm = ['category_id' => $category_id];
		$values = $app->request()->post();
		if ($values) {
			//Filter values
			if (isset($values['product_id']) && isset($values['product_value'])) {
				$values['product'] = [];
				foreach ($values['product_id'] as $key => $id) {
					if (!empty($values['product_value'][$key])) {
						$values['product'][$id] = $values['product_value'][$key];	
					}
				}
			}

			//Check category ID
			if ((isset($values['category_id']) && $values['category_id'] != $category_id) || isset($values['update_category'])) {
				$fromForm = $values;
				$values = false;
			}

			if ($values) {
				//Check if category id exists
				$found = false;
				foreach ($categories as $c) {
					if ($c['Category']->id == $category_id) {
						$found = true;
						break;
					}
				}

				if (!$found) {
					$app->flashError(__('Bad idea!'));
					$app->redirect($app->urlFor('elements_list'));
				}

				//Check data
				if (!isset($values['property'])) {
					$values['property'] = [];
				}
				if (!isset($values['product'])) {
					$values['product'] = [];
				}

				//Try to insert element
				if (($id = Element::insert($category_id, $values))) {
					//Try to add comment
					$app->commentAdd(Comment::MODEL_ELEMENT, $id, $values);
					
					if (!$inModal) {
						$app->flashSuccess(__('Element has been successfully added.'));
						if (isset($values['saveandnew'])) {
							$app->redirect($app->urlFor('elements_add', ['category_id' => $category_id]));
						} else {
							$app->redirect($app->urlFor('elements_list'));
						}
					} else {
						$app->flashSuccessNow(__('Element has been successfully added.'));
						$values = false;
					}
				} else {
					$app->flashErrorNow(__('Problems with creating element!'));
				}
			}
		}

		if (!$values) {
			$values = $fromForm;
		}

		//Get properties for category and include choices for property
		$properties = CategoryProperty::getPropertiesWithChoices($category_id);

		//Get all available products available
		$products = Product::getProducts(null, null, ['contain' => []]);

		//Set data to view
		$app->view()->set('properties', $properties);
		$app->view()->set('products', $products);
		$app->view()->set('values', $values);
		$app->view()->set('action', 'add');

		$app->setTitle(__('Add element'));
		return $app->render('elements_add_edit.html');
	}

	/**
	 * Route /elements/edit/:element_id
	 *
	 * @param $app: Application context
	 * @param $element_id: Element id to edit
	 */
	public function edit($app, $collection_id = null, $element_id = null) {
		$inModal = $app->isModal();

		//Get record
		$record = $app->validate(Element::getElement($element_id, null, null, ['contain' => ['Category']]));

		//Get categories
		$categories = Category::getCategories();

		//Check for POST
		$values = $app->request()->post();
		if ($values) {
			//Check for category ID
			if (isset($values['category_id'])) {
				//Check if category id exists
				$found = false;
				foreach ($categories as $c) {
					if ($c['Category']->id == $values['category_id']) {
						$found = true;
						break;
					}
				}

				if (!$found) {
					unset($values['category_id']);
				}
			}

			//Filter
			if (isset($values['product_id']) && isset($values['product_value'])) {
				foreach ($values['product_id'] as $key => $id) {
					if (!empty($values['product_value'][$key])) {
						if (!isset($values['product'][$id])) {
							$values['product'][$id] = 0;
						}
						$values['product'][$id] += (int)$values['product_value'][$key];
					}
				}
			}

			//Check data
			if (!isset($values['property'])) {
				$values['property'] = [];
			}
			if (!isset($values['product'])) {
				$values['product'] = [];
			}

			//Try to update
			if (Element::update($element_id, $values)) {
				if (!$inModal) {
					$app->flashSuccess(__('Element has been successfully updated.'));
					if (isset($values['submit'])) {
						$app->redirect($app->urlFor('elements_list'));
					} else {
						$app->redirect($app->urlFor('elements_edit', ['element_id' => $element_id]));
					}
				} else {
					$app->flashSuccessNow(__('Element has been successfully updated.'));
				}
			} else {
				$app->flashErrorNow(__('Problems with updating element!'));
			}
		} else {
			//Get from session
			$values = $app->getDataFromSession('elements_edit');

			if (!$values) {
				//Get element from database
				$values = $record['Element'];

				//Get used products with this element
				$usedProducts = ElementProduct::getProducts($element_id);

				//Format products
				if ($usedProducts) {
					$values->product = [];
					foreach ($usedProducts as $p) {
						$values->product[$p['ElementProduct']->product_id] = $p['ElementProduct']->element_count;
					}
				}

				//Get used properties with this element
				$usedProperties = ElementProperty::getProperties($element_id);

				if ($usedProperties) {
					$values->property = [];
					foreach ($usedProperties as $v) {
						$values->property[$v['ElementProperty']->property_id] = $v['ElementProperty']->property_value;
					}
				}	
			}
		}

		//Check category ID
		$category_id = (is_object($values) && isset($values->category_id)) ? $values->category_id : ((is_array($values) && isset($values['category_id'])) ? $values['category_id'] : 0);
		if ($category_id == 0) {
			$category_id = $record['Element']->category_id;
		}

		//Get properties for category and include choices for property
		$properties = CategoryProperty::getPropertiesWithChoices($category_id);

		//Get all available products available
		$products = Product::getProducts(null, null, ['contain' => []]);

		//Get element history log
		$changelog = Element::getElements(null, null, null, [
			'revision' => true,
			'conditions' => [
				'Element.revision_id' => $record['Element']->id
			],
			'contain' => ['CreatedBy'],
			'order' => 'Element.id DESC',
			'debug' => false
		]);
		array_unshift($changelog, $record);

		//Set data to view
		$app->view()->set('record', $record);
		$app->view()->set('properties', $properties);
		$app->view()->set('products', $products);
		$app->view()->set('changelog', $changelog);
		$app->view()->set('changelog_type', 'element');
		$app->view()->set('values', $values);
		$app->view()->set('in_modal', $inModal);
		$app->view()->set('categories', $categories);
		$app->view()->set('action', 'edit');

		$app->commentSetView(Comment::MODEL_ELEMENT, $element_id);

		$app->setTitle(__('Edit element'));
		return $app->render('elements_add_edit.html');
	}

	/**
	 * Route /elements/delete/:element_id
	 *
	 * @param $app: Application context
	 * @param $element_id: Element id to delete
	 */
	public function delete($app, $collection_id = null, $element_id = null) {
		$status = Element::delete($element_id);

		if ($app->request()->isAjax()) {
		//	return $app->toJSON(['Status' => $status], true, $app->toStatusCode($status));
		} 

		if ($status) {
			$app->flashSuccess(__('Element has been successfully deleted.'));
		} else {
			$app->flashError(__('Problems with deleting element!'));
		}
		$app->redirect($app->urlFor('elements_list'));
	}

	/**
	 * Route /elements/import
	 *
	 * @param $app: Application context
	 * @param $element_id: Element id to delete
	 */
	public function import($app, $collection_id = null) {
		$collection_id = $app->validate($collection_id);

		$values = $app->request()->post();
		if ($values) {
			$csvString = $values['CsvString'];
			$lines = explode(PHP_EOL, $csvString);
			$data = [];
			foreach ($lines as $l) {
				$data[] = str_getcsv($l, "\t");
			}
			$out = [];
			if (count($data)) {
				$topLine = $data[0];
				foreach ($topLine as $k => $v) {
					switch (strtolower($v)) {
						case 'name':
							$topLine[$k] = 'name'; break;
						case 'desc':
						case 'description':
							$topLine[$k] = 'description'; break;
						case 'quant':
						case 'quantity':
							$topLine[$k] = 'quantity'; break;
						case 'quantity!':
						case 'quant!':
						case 'warning_quantity':
							$topLine[$k] = 'warning_quantity'; break;
						case 'category':
							$topLine[$k] = 'category'; break;
					}
				}
				unset($data[0]);
				$categories = [];
				foreach ($data as $entry) {
					$min = min(count($entry), count($topLine));
					$tmp = array_combine(array_slice($topLine, 0, $min), array_slice($entry, 0, $min));
					if (isset($tmp['category'])) {
						$tmp['category'] = strtolower($tmp['category']);
						if (!isset($categories[$tmp['category']])) {
							$categories[$tmp['category']] = 0;
						}
					}
					$out[] = $tmp;
				}

				//Start by looking for categories listed for elements
				$categoriesDb = Category::getCategories(null, null, [
					'conditions' => [
						'Category.name' => array_keys($categories)
					],
					'contain' => []
				]);
				//Set IDs to known categories from DB
				foreach ($categoriesDb as $cat) {
					$categories[strtolower($cat['Category']->name)] = $cat['Category']->id;
				}
				//Create categories from input and which are not existing in DB
				foreach ($categories as $catName => $catId) {
					if (!$catId) {
						$categories[$catName] = Category::insert(['name' => $catName]);
					}
				}

				//Get now all categories again first and format them to user successful value
				$categories = Category::getCategories(null, null, [
					'conditions' => [
						'Category.name' => array_keys($categories)
					],
					'contain' => ['Property']
				]);

				//Format them correctly
				$cats = [];
				foreach ($categories as $k => $v) {
					unset($v['CreatedBy'], $v['ModifiedBy']);
					$cats[strtolower($v['Category']->name)] = $v;
				}

				$entriesSuccess = [];
				$entriesFail = [];

				//Start by writing entries to DB
				$i = 1;
				foreach ($out as $entry) {
					$insertId = false;
					if (isset($cats[$entry['category']])) {
						$cat = $cats[$entry['category']];

						//Save entry data first
						$forEntry = array_merge($entry, ['property' => []]);

						//Check element properties, ignore basic element values
						foreach (['name', 'quantity', 'warning_quantity', 'category', 'description'] as $k) {
							if (isset($entry[$k])) {
								unset($entry[$k]);
							}
						}
						
						//Process remaining properties for element if anything exists
						foreach ($entry as $propertyName => $propertyValue) {
							//Check category properties
							foreach ($cat['Property'] as $p) {
								//Check if category property and entry property match
								if (strtolower($p->name) == strtolower($propertyName)) {
									//We have match between property and category here, merge them together and add to database
									$forEntry['property'][$p->id] = $propertyValue;
								}
							}
						}
						$insertId = Element::insert($cat['Category']->id, $forEntry);
					}
					if ($insertId) {
						$entriesSuccess[] = $i;
					} else {
						$entriesFail[] = $i;
					}
					$i++;
				}

				if (count($entriesSuccess) == count($out)) {
					$app->flashSuccess(__('All entries successfully inserted!'));
					$app->redirect($app->urlFor('elements_list'));
				} else {
					$app->flashNoteNow(sprintf(__('Number of entries inserted: %d/%d. Some entries (line(s): %s) were not inserted due to error.'), 
						count($entriesSuccess), count($out), implode(', ', $entriesFail)
					));
				}
			}
		}

		$app->view()->set('values', $values);

		$app->setTitle(__('Import elements'));
		return $app->render('elements_import.html');
	}

	/**
	 * Route /elements/duplicate/:element_id
	 *
	 * @param $app: Application context
	 * @param $element_id: Element id to duplicate
	 */
	public function duplicate($app, $collection_id = null, $element_id = null) {
		$status = Element::duplicate($element_id);

		if ($status) {
			$app->flashSuccess(__('Element has been successfully duplicated.'));
		} else {
			$app->flashError(__('Problems with duplicating element!'));
		}
		$app->redirect($app->urlFor('elements_list'));
	}

	/**
	 * Set quantity to element
	 *
	 * @param $app: Application context
	 * @param $element_id: ELement to set quantity
	 * @param $quantity: Quantity value to set
	 */
	public function quantity($app, $collection_id = null, $element_id = null, $quantity = null) {
		//Get element

		//Check success
		if (Element::increaseQuantity($element_id, intval($quantity), __('Quantity changed from elements page'))) {
			if ($app->request()->isAjax()) {
				print Element::getElement($element_id, null, null, ['contain' => []])['Element']->quantity;
				exit;
			} else {
				$app->flashSuccess(__('Element quantity has been changed.'));
			}
		} else {
			if ($app->request()->isAjax()) {
				print 'ERROR';
				exit;
			} else {
				$app->flashError(__('Element quantity has not been changed.'));
			}
		}

		$app->redirect($app->urlFor('elements_list'));
	}

	/**
	 * Update element with POST method
	 *
	 * POST /elements/update/:element_id
	 *
	 * @param $app: Application context
	 * @param $element_id: Element to update
	 */
	public function update($app, $collection_id = null, $id = null) {
		//Not succedded
		$success = false;

		//Get element
		$result = Element::getElement($id);
		if ($result) {			
			//Update element
			$success = Element::update($id, $app->getPostUpdateFields());
		}

		//Return status
		return $app->toJSON(['Success' => $success], true, $app->toStatusCode($success));
	}

	/**
	 * Update element with POST method
	 *
	 * POST /elements/update/:element_id
	 *
	 * @param $app: Application context
	 * @param $element_id: Element to update
	 */
	public function order($app, $collection_id = null, $element_id = null) {
		//Get element
		$element = $app->validate(Element::getElement($element_id, null, null, [
			'contain' => ['Property']
		]));

		//Get all orders
		$allorders = Elementorder::getOrders(null, null, [
			'conditions' => [
				'Elementorder.status' => Elementorder::STATUS_OPEN
			]
		]);

		//Filter orders by properties
		$orders = [];
		$ordersIds = [];
		$success = [];
		if ($allorders) {
			foreach ($allorders as $o) {
				foreach ($element['Property'] as $p) {
					foreach ($o['Property'] as $op) {
						if ($p->id == $op->ElementorderProperty->property_id) {
							$orders[] = $o;
							$ordersIds[] = $o['Elementorder']->id;
						}
					}
				}
			}
		}

		//Check for post method
		$status = [];
		$values = $app->request()->post();
		if ($values) {
			//Add elements
			foreach ($values as $order_id => $data) {
				if (
					(isset($data['minquantity']) && isset($data['desiredquantity']) && isset($data['purpose'])) && //They must exists
					(!empty($data['minquantity']) || !empty($data['desiredquantity']) || !empty($data['purpose']))
				) {
					$d = array_merge($app->obj2array($data), ['element_id' => $element_id]);

					//Add data array
					$d['element_id'] = $element_id;//$element_id;

					//Insert, or update if needed, if element already exists, update quantity with new values
					$success[$order_id] = Orderelement::insert($order_id, $d, 'update');
				}
				
			}
		}

		//Get all order elements from order
		$elements = Orderelement::getElements($ordersIds, null, [
			'conditions' => [
				'Orderelement.element_id' => $element_id
			]
		]);

		//Format values
		foreach ($elements as $e) {
			if (!isset($values[$e['Orderelement']->elementorder_id])) {
				$values[$e['Orderelement']->elementorder_id] = $app->obj2array($e['Orderelement']);
			}
		}

		//Check
		$app->view()->set('success', $success);
		$app->view()->set('values', $values);
		$app->view()->set('element', $element);
		$app->view()->set('orders', $orders);

		return $app->render('elements_order.html');
	}

	/**
	 * Update element properties
	 *
	 * POST /elements/properties/:element_id
	 *
	 * @param $app: Application context
	 * @param $element_id: Element to update
	 */
	public function properties($app, $collection_id = null, $element_id = null) {
		//Get element
		$record = $app->validate(Element::getElement($element_id, null, null, [
			'contain' => ['Property']
		]));

		$values = $app->request()->post();
		if ($values) {
			//Save here
			if (ElementProperty::updateElement($element_id, isset($values['property']) ? $values['property'] : [], true)) {
				$app->flashSuccessNow(__('Properties has been successfully updated.'));
			} else {
				$app->flashErrorNow(__('There were problem with updating properties!'));
			}
		} else {
			//Get element from database
			$values = $record['Element'];

			//Get used properties with this element
			$usedProperties = ElementProperty::getProperties($element_id);

			if ($usedProperties) {
				$values->property = [];
				foreach ($usedProperties as $v) {
					$values->property[$v['ElementProperty']->property_id] = $v['ElementProperty']->property_value;
				}
			}
		}

		//Get properties for category and include choices for property
		$properties = CategoryProperty::getPropertiesWithChoices($record['Element']->category_id);

		//Set data to view
		$app->view()->set('record', $record);
		$app->view()->set('properties', $properties);
		$app->view()->set('values', $values);

		return $app->render('elements_properties.html');
	}

	/**
	 * Show element row
	 *
	 * POST /elements/properties/:element_id
	 *
	 * @param $app: Application context
	 * @param $element_id: Element to update
	 */
	public function row($app, $collection_id = null, $element_id = null) {
		$app->validate($app->request()->isAjax());
		
		//Get element
		$record = $app->validate(Element::getElement($element_id, null, null, [
			'contain' => ['Property']
		]));

		//Set data to view
		$app->view()->set('element', $record);

		return $app->render('elements_index_row.html');
	}
}
