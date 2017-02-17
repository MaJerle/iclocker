<?php

namespace Controller;

use \Model\Category;
use \Model\CategoryProperty;
use \Model\Property;
use \Model\Comment;

class CommentsController extends Base {

	/**
	 * Route /collection/:collection_id/comments/add
	 *
	 * @param $app: Application context
	 */
	public function add($app, $collection_id = null) {
		//Check request
		$app->validate($app->request()->isAjax());

		//Insert comment
		$id = Comment::insert($app->request()->post());
		$success = $comment = false;

		//Check response
		if ($id) {
			$success = true;
			$comment = Comment::getComment($id);
		}

		//Return result
		return $app->toJSON(['Comment' => $comment, 'Success' => $success, 'Errors' => Comment::getValidationErrors()], true, $app->toStatusCode($success));
	}

	/**
	 * View comments for specific model
	 * 
	 * Route /collection/:collection_id/comments/view/:model/:foreign_id
	 */
	public function view($app, $collection_id = null, $model = null, $foreign_id = null) {
		if (!$app->request()->isAjax()) {
			$app->redirect($app->urlFor('dashboard'));
		}

		$app->commentSetView($model, $foreign_id);
		$app->view()->set('in_modal_comments', true);

		return $app->render('comments.html');
	}
}