<?php

namespace Controller;

use \Model\Element;
use \Model\Category;
use \Model\ElementProperty;
use \Model\UserCollection;
use \Model\Collection;
use \Model\User;
use \Inc\Model;
use \Model\UserFriend;
use \Model\Comment;

class CollectionsController extends Base {
	//Before controller function is called
	public function beforeControllerFunction($app) {
		//Parent call
		parent::beforeControllerFunction($app);

		//Check access
		/*
		if ($app->User['User']->user_group != 1) {
			if ($app->Method != 'index') {
				$app->flashError(__('Permission denied!'));
				$app->redirect($app->urlFor('collections_list'));
			}
		}
		*/
	}

	/**
	 * Route /
	 * Route /collections
	 *
	 * @param $app: Application context
	 */
	public function index($app, $collection_id = null) {
		//Get all collections
		$collections = Collection::getCollections();

		$app->view()->set('collections', $collections);
		
		$app->setTitle(__('Collections'));
		return $app->render('collections_index.html');
	}

	/**
	 * Route /collections/add
	 *
	 * @param $app: Application context
	 */
	public function add($app) {
		if ($app->request->post()) {
			//Get values
			$values = $app->request()->post();

			//Try to add collection
			if (($id = Collection::insert($values))) {
				$app->flashSuccess(__('Collection has been successfully added.'));

				//Add comment
				$app->commentAdd(Comment::MODEL_COLLECTION, $id, $values);
				
				if (isset($values['saveandnew'])) {
					$app->redirect($app->urlFor('collections_add'));
				} else {
					$app->redirect($app->urlFor('collections_list'));
				}
			} else {
				$app->flashErrorNow(__('Problems with creating collection!'));
			}

			//Set values to view
			$app->view()->set('values', $values);
		}

		//List all users
		$users = User::getUsers(null, [
			'contain' => [],
			'conditions' => [
				'User.id !=' => $app->User['User']->id
			]
		]);
		$app->view()->set('users', $users);
		$app->view()->set('action', 'add');

		$app->setTitle(__('Add collection'));
		return $app->render('collections_add_edit.html');
	}

	/**
	 * Route /collections/edit/:collection_id
	 *
	 * @param $app: Application context
	 * @param $collection_id: Collection id to edit
	 */
	public function edit($app, $collection_id = null) {
		$record = $app->validate(Collection::getCollection($collection_id));

		//Check access
		if ($record['Collection']->created_by != $app->userid() && !$app->isAdmin()) {
			$app->flashError(__('You are not permitted for this operation!'));
			$app->redirect($app->urlFor('collections_list'));
		}

		$values = $app->request()->post();
		if ($values) {
			//Get values
			$values = $app->request()->post();

			//Check data
			if (!isset($values['user'])) {
				$values['user'] = [];
			}

			//Try to edit collection
			if (Collection::update($collection_id, $values)) {
				$app->flashSuccess(__('Collection has been successfully updated.'));
				if (isset($values['submit'])) {
					$app->redirect($app->urlFor('collections_list'));
				} else {
					$app->redirect($app->urlFor('collections_edit', array('collection_id' => $collection_id)));
				}
			} else {
				$app->flashErrorNow(__('Problems with updating collection!'));
			}
		} else {
			//Get collection 
			$values = $record['Collection'];

			//Get all users which belongs to this collection
			$values->user = UserCollection::getUsersIds($collection_id);
		}

		//List all users
		$users = User::getUsers(null, [
			'contain' => [],
			'conditions' => [
				'User.id !=' => $app->User['User']->id
			]
		]);
		$app->view()->set('users', $users);

		//Set values to view
		$app->view()->set('record', $record);
		$app->view()->set('values', $values);
		$app->view()->set('action', 'edit');
		$app->commentSetView(Comment::MODEL_COLLECTION, $collection_id);

		$app->setTitle(__('Edit collection'));
		return $app->render('collections_add_edit.html');
	}

	/**
	 * Route /collections/delete/:collection_id
	 *
	 * @param $app: Application context
	 * @param $collection_id: Collection id to delete
	 */
	public function delete($app, $collection_id = null) {
		$status = Collection::delete($collection_id, $app->request->get('link', 0));

		if ($app->request()->isAjax()) {
		//	return $app->toJSON(['Status' => $status], true, $app->toStatusCode($status));
		} 

		if ($status) {
			$app->flashSuccess(__('Collection has been successfully deleted.'));
		} else {
			$app->flashError(__('Problems with deleting collection!'));
		}
		$app->redirect($app->urlFor('collections_list'));
	}
}
