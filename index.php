<?php

require 'vendor/autoload.php';

session_start();

if (isset($_GET['server'])) {
	pr($_SERVER); exit;
}

if (isset($_GET['phpinfo'])) {
	phpinfo(); exit;
}

if (isset($_GET['session'])) {
	pr($_SESSION); exit;
}

if (isset($_GET['cookie'])) {
	pr($_COOKIE); exit;
}

//Create application context
$app = new Inc\Controller;
date_default_timezone_set ('Europe/Ljubljana');

//Frontend routes

//TRENUTNA ROUTA
/*
$app->any('/', function() use($app) {
	$app->redirect($app->urlFor('dashboard'));
})->name('front_index');
*/
//Original route
$app->any('/', Route('Front', 'index'))->name('front_index');
//Original route is used when template is finished

//Development route for first page
$app->any('/template', Route('Front', 'index'))->name('front_index_template');

//Routes
$app->group('/app', function() use ($app) {
	//First page
	$app->get('/dashboard', Route('Collections', 'index'))->name('dashboard');

	//Users
	$app->get('/users', Route('Users', 'index'))->name('users_list');
	$app->any('/users/add', Route('Users', 'add'))->name('users_add');
	$app->any('/users/edit/:user_id', Route('Users', 'edit'))->name('users_edit');
	$app->get('/users/delete/:user_id', Route('Users', 'delete'))->name('users_delete');
	$app->any('/users/settings/:settings', Route('Users', 'settings'))->name('users_settings');

	//Collections
	$app->get('/', Route('Collections', 'index'))->name('index');
	$app->any('/collections', Route('Collections', 'index'))->name('collections_list');
	$app->any('/collections/add', Route('Collections', 'add'))->name('collections_add');
	$app->any('/collections/edit/:collection_id', Route('Collections', 'edit'))->name('collections_edit');
	$app->get('/collections/delete/:collection_id', Route('Collections', 'delete'))->name('collections_delete');
	//Collections group
	$app->group('/collection/:collection_id', function() use ($app) {
		//Elements
		$app->get('/elements', Route('Elements', 'index'))->name('elements_list');
		$app->get('/elements/data', Route('Elements', 'index_data'))->name('elements_list_data');
		$app->get('/elements/category/:category_id', Route('Elements', 'index'))->name('elements_list_category');
		$app->any('/elements/add(/:category_id)', Route('Elements', 'add'))->name('elements_add');
		$app->any('/elements/edit/:element_id', Route('Elements', 'edit'))->name('elements_edit');
		$app->any('/elements/delete/:element_id', Route('Elements', 'delete'))->name('elements_delete');
		$app->any('/elements/row/:element_id', Route('Elements', 'row'))->name('elements_row');
		$app->get('/elements/quantity/:element_id/:quantity', Route('Elements', 'quantity'))->name('elements_quantity');
		$app->post('/elements/update/:element_id', Route('Elements', 'update'))->name('elements_update');
		$app->get('/elements/duplicate/:element_id', Route('Elements', 'duplicate'))->name('elements_duplicate');
		$app->any('/elements/order/:element_id', Route('Elements', 'order'))->name('elements_order');
		$app->any('/elements/properties/:element_id', Route('Elements', 'properties'))->name('elements_properties');
		$app->any('/elements/import', Route('Elements', 'import'))->name('elements_import');

		//Categories
		$app->get('/categories', Route('Categories', 'index'))->name('categories_list');
		$app->any('/categories/add', Route('Categories', 'add'))->name('categories_add');
		$app->any('/categories/edit/:category_id', Route('Categories', 'edit'))->name('categories_edit');
		$app->any('/categories/delete/:category_id', Route('Categories', 'delete'))->name('categories_delete');
		$app->post('/categories/update/:category_id', Route('Categories', 'update'))->name('categories_update');

		//Properties
		$app->get('/properties', Route('Properties', 'index'))->name('properties_list');
		$app->any('/properties/add', Route('Properties', 'add'))->name('properties_add');
		$app->any('/properties/edit/:property_id', Route('Properties', 'edit'))->name('properties_edit');
		$app->get('/properties/delete/:property_id', Route('Properties', 'delete'))->name('properties_delete');
		$app->post('/properties/update/:property_id', Route('Properties', 'update'))->name('properties_update');
		$app->any('/properties/choices/:property_id', Route('Properties', 'choices'))->name('properties_choices');

		//Products
		$app->get('/products', Route('Products', 'index'))->name('products_list');
		$app->any('/products/add', Route('Products', 'add'))->name('products_add');
		$app->any('/products/edit/:product_id', Route('Products', 'edit'))->name('products_edit');
		$app->any('/products/import/:product_id', Route('Products', 'import_bom'))->name('products_import_bom');
		$app->get('/products/delete/:product_id', Route('Products', 'delete'))->name('products_delete');
		$app->post('/products/update/:product_id', Route('Products', 'update'))->name('products_update');
		$app->any('/products/build/:product_id', Route('Products', 'build'))->name('products_build');

		//Orders
		$app->get('/orders', Route('Orders', 'index'))->name('orders_list');
		$app->any('/orders/add', Route('Orders', 'add'))->name('orders_add');
		$app->any('/orders/edit/:elementorder_id', Route('Orders', 'edit'))->name('orders_edit');
		$app->get('/orders/delete/:elementorder_id', Route('Orders', 'delete'))->name('orders_delete');
		$app->get('/orders/sync/:elementorder_id', Route('Orders', 'sync_elements'))->name('orders_sync');
		$app->post('/orders/update/:elementorder_id', Route('Orders', 'update'))->name('orders_update');
		//Order elements
		$app->group('/order/:elementorder_id', function() use($app) {
			$app->any('/elements', Route('Orders', 'elements'))->name('order_elements_list');
			$app->any('/elements/add', Route('Orders', 'elements_add'))->name('orders_elements_add');
			$app->get('/elements/edit/:orderelement_id', Route('Orders', 'elements'))->name('orders_elements_edit');
			$app->get('/elements/delete/:orderelement_id', Route('Orders', 'elements_delete'))->name('orders_elements_delete');
			$app->any('/elements/update/:orderelement_id', Route('Orders', 'elements_update'))->name('orders_elements_update');
		});

		//Comments
		$app->post('/comments/add', Route('Comments', 'add'))->name('comments_add');
		$app->get('/comments/view/:type/:foreign_id', Route('Comments', 'view'))->name('comments_view');
	});
	
	$app->post('/assets/upload_file', Route('Assets', 'upload_file'))->name('upload_file');
	$app->get('/assets/download_file/:file', Route('Assets', 'download_file'))->name('download_file');
});

//Login
$app->any('/login', Route('Login', 'login'))->name('login');
$app->any('/register', Route('Login', 'register'))->name('register');
$app->any('/forgot_password', Route('Login', 'forgot_password'))->name('forgot_password');
$app->any('/reset_password', Route('Login', 'reset_password'))->name('reset_password');

//Importer
$app->get('/import', Route('Importer', 'index'))->name('import');
$app->get('/import/lpvo', Route('Importer', 'lpvo'))->name('import_lpvo');
$app->get('/import/update', Route('Importer', 'update'))->name('import_update');

//API
//API group
$app->group('/api', function () use ($app) {
	$app->any('/', function() use ($app) {
		printf("IC-Locker API v%d.%d.%d", $app->config['apiversion']['Major'], $app->config['apiversion']['Minor'], $app->config['apiversion']['Patch']);
	});
	$app->any('/version', function() use ($app) {
		print json_encode(['ApiVersion' => $app->config['apiversion']]);
	});

	//Login
	$app->any('/login', Route('Rest', 'login'));

	//Current user
	$app->get('/current_user', Route('Rest', 'current_user'));
	$app->post('/current_user', Route('Rest', 'current_user_edit'));

	//Users
	$app->get('/users/:user_id/image', Route('Rest', 'users_image'));

	//Collections
	$app->get('/collections', Route('Rest', 'collections'));
	$app->get('/collections/changes', Route('Rest', 'collections_changes'));

	//Categories
	$app->get('/collection/:collection_id/categories', Route('Rest', 'categories'));
	$app->get('/collection/:collection_id/categories/changes', Route('Rest', 'categories_changes'));
	$app->get('/collection/:collection_id/categories/view/:category_id', Route('Rest', 'categories_view'));
	$app->post('/collection/:collection_id/categories/add', Route('Rest', 'categories_add'));
	$app->post('/collection/:collection_id/categories/edit/:category_id', Route('Rest', 'categories_edit'));
	$app->post('/collection/:collection_id/categories/delete/:category_id', Route('Rest', 'categories_delete'));

	//Elements
	$app->get('/collection/:collection_id/elements', Route('Rest', 'elements'));
	$app->get('/collection/:collection_id/elements/changes', Route('Rest', 'elements_changes'));
	$app->get('/collection/:collection_id/elements/add', Route('Rest', 'element_add'));
	$app->get('/collection/:collection_id/elements/view/:element_id', Route('Rest', 'elements_view'));
	$app->post('/collection/:collection_id/elements/edit/:element_id', Route('Rest', 'elements_edit'));
	$app->post('/collection/:collection_id/elements/add/:category_id', Route('Rest', 'elements_add'));
	$app->post('/collection/:collection_id/elements/delete/:element_id', Route('Rest', 'elements_delete'));
	$app->get('/collection/:collection_id/elements/category/:element_id', Route('Rest', 'elements_category'));
	$app->post('/collection/:collection_id/elements/quantity/:element_id', Route('Rest', 'elements_quantity'));

	//Products
	$app->get('/collection/:collection_id/products', Route('Rest', 'products'));
	$app->get('/collection/:collection_id/products/changes', Route('Rest', 'products_changes'));
	$app->get('/collection/:collection_id/products/view/:product_id', Route('Rest', 'products_view'));
	$app->post('/collection/:collection_id/products/add', Route('Rest', 'products_add'));
	$app->post('/collection/:collection_id/products/edit/:product_id', Route('Rest', 'products_edit'));
	$app->post('/collection/:collection_id/products/delete/:product_id', Route('Rest', 'products_delete'));

	//Properties
	$app->get('/collection/:collection_id/properties', Route('Rest', 'properties'));
	$app->get('/collection/:collection_id/properties/changes', Route('Rest', 'properties_changes'));
	$app->get('/collection/:collection_id/properties/view/:property_id', Route('Rest', 'properties_view'));
	$app->post('/collection/:collection_id/properties/add', Route('Rest', 'properties_add'));
	$app->post('/collection/:collection_id/properties/edit/:property_id', Route('Rest', 'properties_edit'));
	$app->post('/collection/:collection_id/properties/delete/:property_id', Route('Rest', 'properties_delete'));

	//Property choices
	$app->get('/collection/:collection_id/properties/:property_id/choices', Route('Rest', 'propertychoices_list'));
	$app->post('/collection/:collection_id/properties/:property_id/choices/add', Route('Rest', 'propertychoices_add'));
	$app->get('/collection/:collection_id/properties/:property_id/choices/edit/:choice_id', Route('Rest', 'propertychoices_edit'));
	$app->get('/collection/:collection_id/properties/:property_id/choices/delete/:choice_id', Route('Rest', 'propertychoices_delete'));

	//Orders
	$app->get('/collection/:collection_id/orders', Route('Rest', 'orders'));
	$app->get('/collection/:collection_id/orders/changes', Route('Rest', 'orders_changes'));
	$app->get('/collection/:collection_id/orders/view/:elementorder_id', Route('Rest', 'orders_view'));
	$app->post('/collection/:collection_id/orders/add', Route('Rest', 'orders_add'));
	$app->post('/collection/:collection_id/orders/edit/:elementorder_id', Route('Rest', 'orders_edit'));
	$app->post('/collection/:collection_id/orders/delete/:elementorder_id', Route('Rest', 'orders_delete'));

	//Orderelements
	$app->post('/collection/:collection_id/orders/:elementorder_id/elements/add', Route('Rest', 'orderelements_add'));
	$app->post('/collection/:collection_id/orders/:elementorder_id/elements/delete/:orderelement_id', Route('Rest', 'orderelements_edit'));
});

//Helper class routes
//$app->any('/helper/counter_cache', Route('Helper', 'set_counter_cache'));

/* 404 Error handler */
$app->notFound(function() use ($app) {
	print '404 NOT FOUND!';
});

/* Error */
$app->error(function (\Exception $e) use ($app) {
	//Check if API is used when error occurs
	if ($app->isAPI()) {
		$app->toJSON(['Error' => $e->getMessage(), 'Trace' => explode("\n", $e->getTraceAsString())], 500);
	}
	$app->view()->set('message', $e->getMessage());
	$app->view()->set('trace', $e->getTraceAsString());
	exit;
	//$app->render('error.html');
});

//Remove E_STRICT reporting
error_reporting(E_ALL ^ E_STRICT);

/* Run application */
$app->run();

//For language support
function __($text) {
	global $app;

	if (isset($app->TranslateEntries[$text])) {
		$out = implode('', $app->TranslateEntries[$text]['msgstr']);
		if (!empty($out)) {
			return $out;
		}
	}

	return $text;
}

//Print_r with nice format
function pr($obj) {
	$args = func_get_args();
	foreach ($args as $a) {
		print '<pre>' . print_r($a, true) . '</pre>';
	}
}

//Router function
function Route($controller, $method = 'index') {
	global $app;
	if (!$method) {
		$method = 'index';
	}
	$callback = function() use ($app, $controller, $method) {
		//Save current controller and action
		$app->ControllerName = '\Controller\\' . $controller . 'Controller';
		$app->Controller = $controller;
		$app->Method = $method;

		//Save route parameters
		$app->RouteParams = $app->router()->getCurrentRoute()->getParams();
		$app->RouteName = $app->router()->getCurrentRoute()->getName();

		//Create controller instance	
		$class = $app->ControllerName;
		$class = new $class($app);

		//Call function before action
		$class->beforeControllerFunction($app);

		//Get function arguments
		$args = func_get_args();

		//add $app to array
		array_unshift($args, $app);
		return call_user_func_array(array($class, $method), $args);
	};
	return $callback;
}
