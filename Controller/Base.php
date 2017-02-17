<?php
namespace Controller;

use \Model\UserCollection;

/**
 * Just a helper class. All of the controllers extend this
 * 
 * If we want any global settings or functions this is the place...
 * 
 * I moved most of the database related stuff to /Model/...
 */
class Base {
	
	//Application context
	public $app;

	//Default authorization
	public $authorization = true;

	//Constructor
	public function __construct($app) {
		$this->app = $app;
	}

	//Called before controller function is called
	public function beforeControllerFunction($app) {
		//Check authorization
		if ($this->authorization && !$app->user_logged && ($app->Controller != 'Rest' || $app->Method != 'login')) {
			//Only for REST controller
			if ($app->isAPI()) {
				$app->toJSON(['Error' => __('Authentication has been denied for this request!')], true, 401);
			}

			//Save redirect to session
			$_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'];

			//Redirect user to login page
            return $app->redirect($app->urlFor('login'));
		}

		//Set collection as global if exists
		$this->app->collection_id = 0;
		if (isset($this->app->RouteParams['collection_id'])) {
			//Save collection ID
			$this->app->collection_id = $this->app->RouteParams['collection_id'];

			//Check if collection is allowed to user
			if (!UserCollection::checkCollectionAccess($this->app->User, $this->app->collection_id)) {
				//Check a call from 
				if ($app->isAPI()) {
					$app->toJSON(['Error' => __('Permission denied!')], true, 401);
				} else {
					$app->flashError(__('Permission denied!'));
					$app->redirect($app->urlFor('index'));
				}
			}
		}
	}
}
