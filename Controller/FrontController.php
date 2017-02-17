<?php

namespace Controller;

class FrontController extends Base {
	//Authorization is not required
	public $authorization = false;

	/**
	 * Route /
	 *
	 * @param $app: Application context
	 */
	public function index($app) {
		return $app->render('index.html');
	}
}