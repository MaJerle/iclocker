<?php

namespace Controller;

use \Model\Category;
use \Model\Element;
use \Model\User;
use \Model\Property;
use \Model\Propertychoice;
use \Model\Collection;
use \Model\Product;
use \Model\Elementorder;
use \Model\Orderelement;

class RestController extends Base {
	const CODE_OK = 200;
	const CODE_CREATED = 201;
	const CODE_BADREQUEST = 400;
	const CODE_NOTFOUND = 404;
	const CODE_AUTHORIZATION = 401;
	const CODE_SERVERERROR = 500;

	//Called before controller's function
	public function beforeControllerFunction($app) {
		parent::beforeControllerFunction($app);

		//Parse incoming data
		$this->__parseIncomingData($app);
	}

	//Return data with key if ANDROID
	public function toJSON($data, $key) {
		return $this->app->toJSON($data);
	}

	/**
	 * Login
	 *
	 * @param $app: Application context
	 */
	public function login($app) {
		//Check values
		if (isset($this->parameters['username']) && isset($this->parameters['password'])) {
			//Get user with given username and password
			$user = User::login($this->parameters['username'], $this->parameters['password']);
			
			//Check for user
			if ($user) {
				//Remove password part from response before sent to user
				unset($user['User']->password);

				//Return TOKEN, we are successfully logged in
				return $app->toJSON($user);
			}

			//User was not found, invalid credentials
			return $app->toJSON(['Error' => 'Invalid username or password!'], true, self::CODE_BADREQUEST);
		}

		return $app->toJSON(['Error' => 'Invalid data!'], true, self::CODE_BADREQUEST);
	}

	/**
	 * Gets current user data
	 */
	public function current_user($app) {
		$user = User::getCurrentUser();

		//Remove password field
		if ($user) {
			unset($user['User']->password);
		}

		return $app->toJSON($user);
	}

	/**
	 * Updates current user
	 */
	public function current_user_edit($app) {
		//Update current user
		if (!User::updateCurrentUser($this->parameters)) {
			return $app->toJSON(['Error' => User::getValidationErrors()], true, self::CODE_BADREQUEST);
		}

		$user = User::getCurrentUser();

		//Remove password field
		if ($user) {
			unset($user['User']->password);
		}

		return $app->toJSON($user);
	}

	/**
	 * Get user image data
	 *
	 * @param $app: Application context
	 * @param $user_id: User id to get image
	 */
	public function users_image($app, $user_id = null) {
		//Currently get for current user only!
		$file = $app->config['images']['path'] . 'icon_default.png';

		if (file_exists($file)) {
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="' . basename($file) . '"');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($file));
			readfile($file);
			exit;
		}

		return $this->NotFound();
	}

	/**
	 * List all collections
	 *
	 * @param $app: Application context
	 */
	public function collections($app) {
		//Get all collections for current user
		$collections = Collection::getCollections();

		return $app->toJSON($collections);
	}

	/**
	 * List of all elements
	 *
	 * @param $app: Application context
	 */
	public function elements($app, $collection_id = null) {
		//Get all elements in specific collection
		$elements = Element::getElements();

		return $app->toJSON($elements);
	}

	/**
	 * Values for specific element ID
	 *
	 * @param $app: Application context
	 */
	public function elements_view($app, $collection_id = null, $element_id = null) {
		//Get element with given ID
		$element = Element::getElement($element_id);

		return $app->toJSON($element, true, $element ? self::CODE_OK : self::CODE_NOTFOUND);
	}

	/**
	 * Add element
	 *
	 * @param $app: Application context
	 */
	public function elements_add($app, $collection_id = null, $category_id = null) {
		//Insert element
		$insID = Element::insert($category_id, $this->parameters);

		//If successful, read element back
		if ($insID) {
			$element = Element::getElement($insID);
			return $app->toJSON($element, true, self::CODE_CREATED);
		}

		return $app->toJSON(['Error' => Element::getValidationErrors()], true, self::CODE_BADREQUEST);
	}

	/**
	 * Edit element with specific ID
	 *
	 * @param $app: Application context
	 */
	public function elements_edit($app, $collection_id = null, $element_id = null) {
		//Update element
		$success = Element::update($element_id, $this->parameters);

		//If successful, read element back
		if ($success) {
			$element = Element::getElement($element_id);
			return $app->toJSON($element, true, $element ? self::CODE_OK : self::CODE_NOTFOUND);
		}

		return $app->toJSON(['Error' => Element::getValidationErrors()], true, self::CODE_BADREQUEST);
	}

	/**
	 * Increases element quantity by one
	 *
	 * @param $app: Application context
	 */
	public function elements_quantity($app, $collection_id = null, $element_id = null) {
		//return $app->toJSON(['Error' => Element::getValidationErrors()], true, self::CODE_BADREQUEST);
		if (isset($this->parameters['quantity'])) {
			//Update element, increase quantity by 1
			$success = Element::increaseQuantity($element_id, $this->parameters['quantity']);

			//If successful, read element back
			if ($success) {
				$element = Element::getElement($element_id);
				return $app->toJSON($element, true, $element ? self::CODE_OK : self::CODE_NOTFOUND);
			}
		}

		return $app->toJSON(['Error' => Element::getValidationErrors()], true, self::CODE_BADREQUEST);
	}

	/**
	 * Deletes element with specific ID
	 *
	 * @param $app: Application context
	 */
	public function elements_delete($app, $collection_id = null, $element_id = null) {
		//Delete element
		$success = Element::delete($element_id);

		return $app->toJSON(['status' => $success]);
	}

	/**
	 * List of all elements in specific category ID
	 *
	 * @param $app: Application context
	 */
	public function elements_category($app, $collection_id = null, $category_id = null) {
		//Get all elements in specific category
		$elements = Element::getElements($category_id);

		return $app->toJSON($elements);
	}

	/**
	 * Gets list of all categories in specific collection
	 *
	 * @param $app: Application context
	 */
	public function categories($app, $collection_id = null) {
		//Get all categories
		$categories = Category::getCategoriesWithProperties();

		return $app->toJSON($categories);
	}

	/**
	 * View category with specific ID
	 *
	 * @param $app: Application context
	 */
	public function categories_view($app, $collection_id = null, $category_id = null) {
		//View category
		$category = Category::getCategoryWithProperties($category_id);

		return $app->toJSON($category, true, $category ? self::CODE_OK : self::CODE_NOTFOUND);
	}

	/**
	 * Add new category
	 *
	 * @param $app: Application context
	 */
	public function categories_add($app, $collection_id = null) {
		//Insert category
		$insID = Category::insert($this->parameters);

		//If successful, read back
		if ($insID) {
			$element = Category::getCategoryWithProperties($insID);
			return $app->toJSON($element, true, self::CODE_CREATED);
		}

		return $app->toJSON(['Error' => Category::getValidationErrors()], true, self::CODE_BADREQUEST);
	}

	/**
	 * Edit category
	 *
	 * @param $app: Application context
	 */
	public function categories_edit($app, $collection_id = null, $category_id = null) {
		//Edit category
		$success = Category::update($category_id, $this->parameters);

		//If successful, read back
		if ($success) {
			$element = Category::getCategoryWithProperties($category_id);
			return $app->toJSON($element, true, $element ? self::CODE_OK : self::CODE_NOTFOUND);
		}
		return $app->toJSON(['Error' => Category::getValidationErrors()], true, self::CODE_BADREQUEST);
	}

	/**
	 * Deletes category with specific ID
	 *
	 * @param $app: Application context
	 */
	public function categories_delete($app, $collection_id = null, $category_id = null) {
		//Delete category
		$success = Category::delete($category_id);

		return $app->toJSON(['status' => $success]);
	}

	/**
	 * Gets a list of all properties for specific collection
	 *
	 * @param $app: Application context
	 */
	public function properties($app, $collection_id = null) {
		//Get all properties
		$properties = Property::getPropertiesWithCategories();

		return $app->toJSON($properties);
	}

	/**
	 * View property with specific ID
	 *
	 * @param $app: Application context
	 */
	public function properties_view($app, $collection_id = null, $property_id = null) {
		//Update property
		$property = Property::getPropertyWithCategories($property_id);

		return $app->toJSON($property, true, $property ? self::CODE_OK : self::CODE_NOTFOUND);
	}

	/**
	 * Add new property
	 *
	 * @param $app: Application context
	 */
	public function properties_add($app, $collection_id = null) {
		//Insert new property
		$insID = Property::insert($this->parameters);

		//If successful, read property back
		if ($insID) {
			$element = Property::getPropertyWithCategories($insID);
			return $app->toJSON($element, true, self::CODE_CREATED);
		}

		return $app->toJSON(['Error' => Property::getValidationErrors()], true, self::CODE_BADREQUEST);
	}

	/**
	 * Edit property
	 *
	 * @param $app: Application context
	 */
	public function properties_edit($app, $collection_id = null, $property_id = null) {
		//Edit property
		$success = Property::update($property_id, $this->parameters);

		//If successful, read property back
		if ($success) {
			$element = Property::getPropertyWithCategories($property_id);
			return $app->toJSON($element, true, $element ? self::CODE_OK : self::CODE_NOTFOUND);
		}

		return $app->toJSON(['Error' => Property::getValidationErrors()], true, self::CODE_BADREQUEST);
	}

	/**
	 * Deletes property with specific ID
	 *
	 * @param $app: Application context
	 */
	public function properties_delete($app, $collection_id = null, $property_id = null) {
		//Delete property
		$success = Property::delete($property_id);

		return $app->toJSON(['status' => $success]);
	}

	/**
	 * Returns list of all property choices for given property
	 *
	 * @param $app: Application context
	 */
	public function propertychoices_list($app, $collection_id = null, $property_id = null) {
		//Get a list of choices
		$choices = Propertychoice::getChoices($property_id);

		return $app->toJSON($choices);
	}

	/**
	 * Adds new choice to property available choices
	 *
	 * @param $app: Application context
	 */
	public function propertychoices_add($app, $collection_id = null, $property_id = null) {
		//Insert new choice
		$insID = Propertychoice::insert($property_id, $this->parameters);

		//If successful, read choice back
		if ($insID) {
			$element = Propertychoice::getChoice($insID, $property_id);
			return $app->toJSON($element, true, self::CODE_CREATED);
		}

		return $app->toJSON(['Error' => Propertychoice::getValidationErrors()], true, self::CODE_BADREQUEST);
	}

	/**
	 * Gets a list of all products for specific collection
	 *
	 * @param $app: Application context
	 */
	public function products($app, $collection_id = null) {
		//Get all products
		$products = Product::getProducts();

		return $app->toJSON($products);
	}

	/**
	 * View product with specific ID
	 *
	 * @param $app: Application context
	 */
	public function products_view($app, $collection_id = null, $product_id = null) {
		//View product
		$product = Product::getProductWithElements($product_id);

		return $app->toJSON($product, true, $product ? self::CODE_OK : self::CODE_NOTFOUND);
	}

	/**
	 * Add new product
	 *
	 * @param $app: Application context
	 */
	public function products_add($app, $collection_id = null) {
		//Insert product
		$insID = Product::insert($this->parameters);

		//If successful, read category back
		if ($insID) {
			$element = Product::getProductWithElements($insID);
			return $app->toJSON($element, true, self::CODE_CREATED);
		}

		return $app->toJSON(['Error' => Product::getValidationErrors()], true, self::CODE_BADREQUEST);
	}

	/**
	 * Edit category
	 *
	 * @param $app: Application context
	 */
	public function products_edit($app, $collection_id = null, $product_id = null) {
		//Get all products
		$success = Product::update($product_id, $this->parameters);

		//If successful, read category back
		if ($success) {
			$element = Product::getProductWithElements($product_id);
			return $app->toJSON($element, true, $element ? self::CODE_OK : self::CODE_NOTFOUND);
		}

		return $app->toJSON(['Error' => Product::getValidationErrors()], true, self::CODE_BADREQUEST);
	}

	/**
	 * Deletes category with specific ID
	 *
	 * @param $app: Application context
	 */
	public function products_delete($app, $collection_id = null, $product_id = null) {
		//Delete property
		$success = Product::delete($product_id);

		return $app->toJSON(['status' => $success]);
	}



	/**
	 * Gets a list of all orders for specific collection
	 *
	 * @param $app: Application context
	 */
	public function orders($app, $collection_id = null) {
		//Get all orders
		$orders = Elementorder::getOrdersWithElements();

		return $app->toJSON($orders);
	}

	/**
	 * View order with specific ID
	 *
	 * @param $app: Application context
	 */
	public function orders_view($app, $collection_id = null, $order_id = null) {
		//View order
		$order = Elementorder::getOrderWithElements($order_id);

		return $app->toJSON($order, true, $order ? self::CODE_OK : self::CODE_NOTFOUND);
	}

	/**
	 * Add new order
	 *
	 * @param $app: Application context
	 */
	public function orders_add($app, $collection_id = null) {
		//Get all orders
		$insID = Elementorder::insert($this->parameters);

		//If successful, read back
		if ($insID) {
			$order = Elementorder::getOrderWithElements($insID);
			return $app->toJSON($order, true, self::CODE_CREATED);
		}

		return $app->toJSON(['Error' => Elementorder::getValidationErrors()], true, self::CODE_BADREQUEST);
	}

	/**
	 * Edit category
	 *
	 * @param $app: Application context
	 */
	public function orders_edit($app, $collection_id = null, $order_id = null) {
		//Update order
		$success = Elementorder::update($order_id, $this->parameters);

		//If successful, read back
		if ($success) {
			$element = Elementorder::getOrderWithElements($order_id);
			return $app->toJSON($element, true, $element ? self::CODE_OK : self::CODE_NOTFOUND);
		}

		return $app->toJSON(['Error' => Elementorder::getValidationErrors()], true, self::CODE_BADREQUEST);
	}

	/**
	 * Deletes order with specific ID
	 *
	 * @param $app: Application context
	 */
	public function orders_delete($app, $collection_id = null, $order_id = null) {
		//Delete order
		$success = Elementorder::delete($order_id);

		return $app->toJSON(['status' => $success]);
	}

	/**
	 * Add new orderelement to order
	 *
	 * @param $app: Application context
	 */
	public function orderelements_add($app, $collection_id = null, $order_id = null) {
		//Insert element
		$insID = Orderelement::insert($order_id, $this->parameters);

		//If successful
		if ($insID) {
			$element = Orderelement::getElement($order_id, $insID);
			return $app->toJSON($element, true, self::CODE_CREATED);
		}

		return $app->toJSON(['Error' => Orderelement::getValidationErrors()], true, self::CODE_BADREQUEST);
	}

	/**
	 * Deletes orderelement with specific ID
	 *
	 * @param $app: Application context
	 */
	public function orderelements_delete($app, $collection_id = null, $order_id = null, $orderelement_id = null) {
		//Delete order
		$success = Orderelement::delete($orderelement_id);

		return $app->toJSON(['status' => $success]);
	}

	/**
	 * CHANGES
	 */  
	public function collections_changes($app, $collection_id = null) { return $this->__getChanges($app, $collection_id, 'Collection'); }
	public function elements_changes($app, $collection_id = null) { return $this->__getChanges($app, $collection_id, 'Element'); }
	public function categories_changes($app, $collection_id = null) { return $this->__getChanges($app, $collection_id, 'Category'); }
	public function properties_changes($app, $collection_id = null) { return $this->__getChanges($app, $collection_id, 'Property'); }
	public function products_changes($app, $collection_id = null) { return $this->__getChanges($app, $collection_id, 'Product'); }
	public function orders_changes($app, $collection_id = null) { return $this->__getChanges($app, $collection_id, 'Elementorder'); }

	private function __getChanges($app, $collection_id, $modelName) {
		$maxcnt = 100;
		$options = [
			'conditions' => [],
			'limit' => $maxcnt,
			'order' => $modelName . '.modified_at ASC',
			//'contain' => [],
			'changes' => true
		];
		if ($modelName != 'Collection') {
			$options['conditions'][$modelName . '.collection_id'] = $collection_id;
		}
		$options['conditions'][$modelName . '.revision_id'] = 0;

		//Get token for identify date from where we want changes
		$token = $app->request()->get('token', null);
		if ($token !== null) {
			$tokenValues = json_decode(base64_decode('ey' . $token . '='), true);
			if (isset($tokenValues['Valid']) && isset($tokenValues['Date'])) {
				$options['conditions'][$modelName . '.modified_at > '] = $tokenValues['Date'];
			}
		}

		//Get data
		if ($modelName != 'Collection') {
			//Get values and count
			$values = \Inc\Model::find('all', $options, '\Model\\' . $modelName);
			$cnt = \Inc\Model::find('count', $options, '\Model\\' . $modelName);
		} else {
			$values = Collection::getCollections(null, $options);
			$cnt = Collection::getCollections(null, array_merge($options, ['type' => 'count']));
		}
		
		//Save next token
		$hasMoreChanges = false;
		if ($values) {
			//Check for more changes
			$model = '\Model\\' . $modelName;
			if ($cnt > $maxcnt) {
				$hasMoreChanges = true;
			}

			//Create new token for new operations
			$arr = [
				'Date' => $values[count($values) - 1][$modelName]->modified_at,
				'Valid' => true
			];
			$token = base64_encode(json_encode($arr));
			$token = substr($token, 2, strlen($token) - 3);
		}

		//Changes array
		$values['Changes'] = [
			'More' => $hasMoreChanges,
			'NextToken' => $token
		];

		return $app->toJSON($values);
	}

	//Parses incoming data from request
	private function __parseIncomingData($app) {
		$parameters = array();
		$format = '';

		//Check GET 
		$parameters = array_merge($parameters, $_GET, $_POST);

		//Get POST/PUT from PHP input
		$body = file_get_contents('php://input');

		//Check for content type
		$content_type = false;
		if (isset($_SERVER['CONTENT_TYPE'])) {
			$content_type = array_shift(explode(';', $_SERVER['CONTENT_TYPE']));
		}

		switch ($content_type) {
			case 'application/json':
				//Decode data
				$body_params = json_decode($body, true);

				//Add to parameters
				if ($body_params) {
					foreach($body_params as $param_name => $param_value) {
						$parameters[$param_name] = $param_value;
					}
				}
				$format = 'json';
				break;
			case 'application/x-www-form-urlencoded':
				//Parse variables as query string
				parse_str($body, $postvars);

				//Add to parameters
				foreach ($postvars as $f => $v) {
					$parameters[$f] = $v;
				}
				$format = 'html';
				break;
			default:
				break;
		}

		//Save parameters and format
		$this->parameters = array_merge($app->RouteParams, $parameters);
		$this->format = $format;
	}


	//Return error functions
	private function NotFound() {
		return $this->app->toJSON(false, true, self::CODE_NOTFOUND);
	}
}