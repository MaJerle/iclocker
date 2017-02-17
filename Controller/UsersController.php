<?php

namespace Controller;
use \Model\Collection;
use \Model\User;
use \Model\UserToken;
use \Model\Usersetting;
use \Model\UserCollection;
use \Model\Comment;

class UsersController extends Base {
	//Check for access
	public function beforeControllerFunction($app) {
		parent::beforeControllerFunction($app);
		//Allow access only to edit
		if ($app->Method == 'edit') {
			if (
				$app->userid() != $app->RouteParams['user_id'] &&
				!$app->isAdmin()
			) {
				$app->redirect($app->urlFor('index'));
			}
		} else if (!$app->isAdmin() && $app->Method != 'settings') {
			$app->redirect($app->urlFor('index'));
		}
	}

	/**
	 * Route /users
	 *
	 * @param $app: Application context
	 */
	public function index($app) {
		//Get all users
		$users = User::getUsers(null, ['contain' => []]);

		$app->view()->set('users', $users);

		$app->setTitle(__('Users'));
		$app->render('users_index.html');
	}

	/**
	 * Route /users/add/:user_id
	 *
	 * @param $app: Application context
	 */
	public function add($app) {
		//Check POST
		$values = $app->request()->post();
		if ($values) {
			//Try to insert to database
			if (($id = User::insert($values)) != false) {
				$app->flashSuccess(__('User has been successfully added.'));
				
				//Add comment
				$app->commentAdd(Comment::MODEL_USER, $id, $values);

				if (isset($values['saveandnew'])) {
					$app->redirect($app->urlFor('users_add'));
				} else {
					$app->redirect($app->urlFor('users_list'));
				}
			} else {
				$app->flashErrorNow(__('Problems with creating user!'));
			}

		}
		
		$app->view()->set('values', $values);

		//Get list of all collections
		$collections = Collection::getCollections(null, [
			'contain' => []
		]);
		$app->view()->set('collections', $collections);
		$app->view()->set('action', 'add');

		$app->setTitle(__('Add user'));
		return $app->render('users_add_edit.html');
	}

	/**
	 * Route /users/edit/:user_id
	 *
	 * @param $app: Application context
	 * @param $user_id: user ID to edit
	 */
	public function edit($app, $user_id = null) {
		$record = $app->validate(User::getUser($user_id, ['contain' => []]));

		//Check for admin model
		$adminMode = $app->request()->get('adminmode', null);
		if (!is_null($adminMode)) {
			$adminMode = (bool)$adminMode;
			if ($user_id == $app->User['User']->id) {
				if ($app->User['User']->access_group == 1) {
					if ($adminMode) {
						$_SESSION['admin_' . $app->User['User']->id] = true;
						$app->flashSuccess(__('Admin mode enabled.'));
					} else {
						if (isset($_SESSION['admin_' . $app->User['User']->id])) {
							unset($_SESSION['admin_' . $app->User['User']->id]);
							$app->flashSuccess(__('Admin mode disabled.'));
						}
					}
				}
			}
			$app->redirect($app->urlFor('dashboard'));
		}

		//Check POST
		$values = $app->request()->post();
		if ($values) {
			//Check data
			if (!isset($values['collection'])) {
				$values['collection'] = [];
			}

			//Try to update database
			if (User::update($user_id, $values)) {
				$app->flashSuccess(__('User has been successfully updated.'));
				if (isset($values['submit'])) {
					$app->redirect($app->urlFor('users_list'));
				} else {
					$app->redirect($app->urlFor('users_edit', ['user_id' => $user_id]));
				}
			} else {
				$app->flashErrorNow(__('Problems with updating user!'));
			}
		} else {
			$values = $record['User'];

			//If we are admin, we can edit user collections too
			if ($app->isAdmin()) {
				//Currently used collections for user
				$values->collections = UserCollection::getCollectionsIds($user_id);
			}
		}

		//If admin
		if ($app->isAdmin()) {
			//Get collections
			$collections = Collection::getCollections(null, [
				'contain' => []
			]);
			$app->view()->set('collections', $collections);

			//Get last user tokens
			$tokens = Usertoken::getTokens($user_id, [
				'limit' => 50,
				'order' => 'Usertoken.created_at DESC'
			]);
			$app->view()->set('tokens', $tokens);
		}

		$app->view()->set('record', $record);
		$app->view()->set('values', $values);
		$app->view()->set('action', 'edit');
		$app->commentSetView(Comment::MODEL_USER, $user_id);

		$app->setTitle(__('Edit profile'));
		return $app->render('users_add_edit.html');
	}

	/**
	 * Route /users/delete/:user_id
	 *
	 * @param $app: Application context
	 * @param $user_id: user ID to delete
	 */
	public function delete($app, $user_id = null) {
		$status = User::delete($user_id);

		if ($app->request()->isAjax()) {
		//	return $app->toJSON(['Status' => $status], true, $app->toStatusCode($status));
		} 

		if ($status) {
			$app->flashSuccess(__('User has been successfully deleted.'));
		} else {
			$app->flashError(__('Problems with deleting user!'));
		}
		$app->redirect($app->urlFor('users_list'));
	}

	/**
	 * Manages settings for user
	 *
	 * @param $app: Application context
	 * @param $settings: Settings group to edit
	 */
	public function settings($app, $settings_type = null) {
		$app->validate($app->request()->isAjax());

		//Check values
		$values = $app->request()->post();
		if ($values) {
			$success = false;
			if (isset($values['settings'])) {
				$success = Usersetting::writeSettings($app->User['User']->id, $values['settings']);
			}

			if ($success) {
				$app->flashSuccessNow(__('Settings have been successfully saved.'));
			} else {
				$app->flashErrorNow(__('Problems trying to save settings!'));
			}
		}

		$app->view()->set('settings_type', $settings_type);

		return $app->render('users_settings.html');
	}
}
