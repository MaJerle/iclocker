<?php

namespace Controller;
use \Model\Category;
use \Model\User;

class LoginController extends Base {
	public $authorization = false;

	/**
	 * Route /login
	 *
	 * @param $app: Application context
	 */
	public function login($app) {
		//Check if user is logged in
		if ($app->user_logged) {
			$app->redirect($app->urlFor('index'));
		}

		//Check for input request
		$values = $app->request()->post();
		if ($values) {
			//Get user with given username and password
			$user = User::login($values['username'], $values['password']);

			//Check for success
			if ($user) {
				//Save token
				$this->__login($user, $values['remember']);
				$app->redirect($app->urlFor('index'));
			}

			//Show error
			$app->view()->set('errors', __('Username or password incorrect!'));
			$app->flashErrorNow(__('Username or password incorrect!'));
		}

		$app->setTitle(__('Login'));
		return $app->render('login.html');
	}

	/**
	 * Route /register
	 *
	 * @param $app: Application context
	 */
	public function register($app) {
		//Check if user is logged in
		if ($app->user_logged) {
			$app->redirect($app->urlFor('index'));
		}

		//Check POST
		$values = $app->request()->post();
		if ($values) {
			//Insert user
			if (User::register($values)) {
				//Authenticate user
				$user = User::login($values['username'], $values['password']);
				//Try to login user
				if ($user !== false) {
					$this->__login($user);
					$app->flashSuccess(__('Account has been created and you have been automatically logged into system.'));
					$app->redirect($app->urlFor('index'));
				} else {
					$app->flashSuccess(__('Account has been created. You may now login.'));
					$app->redirect($app->urlFor('login'));
				}
			} else {
				$app->flashErrorNow(__('There were problems with registration. Please see errors below.'));
			}
		}

		$app->view->set('values', $values);

		$app->setTitle(__('Register'));
		return $app->render('register.html');
	}

	/**
	 * Forgot password
	 *
	 * Route /forgot_password
	 *
	 * @param $app: Application context
	 */
	public function forgot_password($app) {
		if ($app->user_logged) {
			return $app->redirect($app->urlFor('index'));
		}

		$values = $app->request()->post();
		if ($values) {
			if (User::forgot_password($values)) {
				$app->flashSuccess(__('E-mail with further instructions about password reset has been successfully sent to your email address. Please check email.'));
				$app->redirect($app->urlFor('login'));
			} else {
				$app->flashErrorNow(__('There were problems with your request!'));
			}
		}

		$app->view()->set('values', $values);

		$app->setTitle(__('Forgot password'));
		return $app->render('forgot_password.html');
	}

	/**
	 * Reset password
	 *
	 * Route /reset_password
	 *
	 * @param $app: Application context
	 */
	public function reset_password($app) {
		if ($app->user_logged) {
			return $app->redirect($app->urlFor('index'));
		}

		//Get code
		$code = $app->validate($app->request()->get('code', false));

		$values = $app->request()->post();
		if ($values) {
			$values['code'] = $code;
			if (User::reset_password($values)) {
				$app->flashSuccess(__('Password has been successfully updated. You may now login.'));
				$app->redirect($app->urlFor('login'));
			} else {
				$app->flashErrorNow(__('There were problems with updating your password!'));
			}
		}

		$app->view()->set('values', $values);

		$app->setTitle(__('Reset password'));
		return $app->render('reset_password.html');
	}

	//Process login request
	private function __login($user, $remember = true) {
		//Set cookie
		$time = 0;
		if ($remember) {
			//Set cookie for 1 year
			$time = time() + 24 * 3600 * 365;
		}

		//Login user
		setcookie('user', json_encode(['token' => $user['Usertoken']->token]), $time, '/');
	}
}