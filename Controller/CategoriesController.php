<?php

namespace Controller;

use \Model\Category;
use \Model\CategoryProperty;
use \Model\Property;
use \Model\Comment;
use \Model\Element;

class CategoriesController extends Base {

	/**
	 * Route /collection/:collection_id/categories
	 *
	 * @param $app: Application context
	 */
	public function index($app) {
		//Get all categories
		$categories = Category::getCategories();

		$app->view()->set('categories', $categories);

		$app->setTitle(__('Categories'));
		return $app->render('categories_index.html');
	}

	/**
	 * Route /collection/:collection_id/categories/add
	 *
	 * @param $app: Application context
	 */
	public function add($app, $collection_id = null) {
		$inModal = $app->isModal();

		//Check if request was made
		$values = $app->request()->post();
		if ($values) {
			//Add category to database
			if (($id = Category::insert($values))) {				
				//Add comment
				$app->commentAdd(Comment::MODEL_CATEGORY, $id, $values);

				if (!$inModal) {
					//Redirect to categories
					$app->flashSuccess(__('Category has been successfully added.'));
					if (isset($values['saveandnew'])) {
						$app->redirect($app->urlFor('categories_add'));
					} else {
						$app->redirect($app->urlFor('categories_list'));
					}
				} else {
					$values = [];
					$app->flashSuccessNow(__('Category has been successfully added.'));
				}
			} else {
				$app->flashErrorNow(__('Problems with creating category!'));
			}
		}

		//Set to view
		$app->view()->set('values', $values);

		//Get all properties for category
		$properties = Property::getProperties();

		$app->view()->set('properties', $properties);
		$app->view()->set('in_modal', $inModal);
		$app->view()->set('action', 'add');

		$app->setTitle(__('Add category'));
		return $app->render('categories_add_edit.html');
	}

	/**
	 * Route /collection/:collection_id/categories/edit/:category_id
	 *
	 * @param $app: Application context
	 * @param $category_id: Category ID to update
	 */
	public function edit($app, $collection_id = null, $category_id = null) {
		$record = $app->validate(Category::getCategory($category_id));
		$inModal = $app->isModal();

		//Check if request was made
		$values = $app->request()->post();
		if ($values) {
			//Check data
			if (!isset($values['property'])) {
				$values['property'] = [];
			}

			//Try to update category and relation table
			if (Category::update($category_id, $values)) {
				if (!$inModal) {
					//Redirect to categories
					$app->flashSuccess(__('Category has been successfully updated.'));
					if (isset($values['submit'])) {
						$app->redirect($app->urlFor('categories_list'));
					} else {
						$app->redirect($app->urlFor('categories_edit', ['category_id' => $category_id]));
					}
				} else {
					$app->flashSuccessNow(__('Category has been successfully updated.'));
				}
			} else {
				$app->flashErrorNow(__('Problems with updating category!'));
			}
		} else {
			//Get values
			$values = $record['Category'];

			//Get list of all property ID values where this category is used
			$values->property = CategoryProperty::getPropertiesIds($category_id);
		}

		//Get all available properties
		$properties = Property::getProperties();

		$app->view()->set('record', $record);
		$app->view()->set('properties', $properties);
		$app->view()->set('in_modal', $inModal);
		$app->view()->set('values', $values);
		$app->view()->set('action', 'edit');
		$app->commentSetView(Comment::MODEL_CATEGORY, $category_id);

		$app->setTitle(__('Edit category'));
		return $app->render('categories_add_edit.html');
	}

	/**
	 * Route /collection/:collection_id/categories/delete/:category_id
	 *
	 * @param $app: Application context
	 * @param $category_id: Category id to delete
	 */
	public function delete($app, $collection_id = null, $category_id = null) {
		$delete = false;
		$values = $app->request()->post();
	
		if ($values) {
			//Reset category
			if (isset($values['category_new'])) {
				Element::changeCategory($category_id, $values['category_new']);
			}

			//Delete category
			$status = Category::delete($category_id);
			if ($status) {
				$app->flashSuccess(__('Category has been successfully deleted.'));
			} else {
				$app->flashError(__('Problems with deleting category!'));
			}
			$app->redirect($app->urlFor('categories_list'));
		}

		if ($app->request()->isAjax()) {
		//	return $app->toJSON(['Status' => $status], true, $app->toStatusCode($status));
		}

		//Get categories
		$categories = Category::getCategories();
		foreach ($categories as $k => $c) {
			if ($c['Category']->id == $category_id) {
				unset($categories[$k]);
			}
		}

		//Get elements in category
		$elements = Element::getElements(null, $category_id);

		$app->view()->set('category_id', $category_id);
		$app->view()->set('categories', $categories);
		$app->view()->set('elements', $elements);

		return $app->render('categories_delete.html');
	}

	/**
	 * Update category with POST method
	 *
	 * POST /categories/update/:category_id
	 *
	 * @param $app: Application context
	 * @param $id: Property to update
	 */
	public function update($app, $collection_id = null, $id = null) {
		//Not succedded
		$success = false;

		//Get element
		$result = Category::getCategory($id);
		if ($result) {			
			//Update element
			$success = Category::update($id, $app->getPostUpdateFields());
		}

		//Return status
		return $app->toJSON(['Success' => $success], true, $app->toStatusCode($success));
	}
}
