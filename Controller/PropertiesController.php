<?php

namespace Controller;
use \Model\Category;
use \Model\Property;
use \Model\Propertychoice;
use \Model\CategoryProperty;
use \Model\Comment;

class PropertiesController extends Base {

	/**
	 * Route /properties
	 *
	 * @param $app: Application context
	 */
	public function index($app, $collection_id = null) {
		//Get all properties
		$properties = Property::getProperties();

		$app->view()->set('properties', $properties);

		$app->setTitle(__('Properties'));
		$app->render('properties_index.html');
	}

	/**
	 * Route /properties/add/:property_id
	 *
	 * @param $app: Application context
	 */
	public function add($app, $collection_id = null) {
		//Check POST
		$values = $app->request()->post();
		if ($values) {
			//Try to insert to database
			if (($id = Property::insert($values))) {
				$app->flashSuccess(__('Property has been successfully added.'));

				//Add comment
				$app->commentAdd(Comment::MODEL_PROPERTY, $id, $values);
				
				if (isset($values['saveandnew'])) {
					$app->redirect($app->urlFor('properties_add'));
				} else {
					$app->redirect($app->urlFor('properties_list'));
				}
			} else {
				$app->flashErrorNow(__('Problems with creating property!'));
			}
		}
		//Set values to view
		$app->view()->set('values', $values);

		//Get all categories
		$categories = Category::getCategories();

		$app->view()->set('categories', $categories);
		$app->view()->set('action', 'add');

		$app->setTitle(__('Add property'));
		return $app->render('properties_add_edit.html');
	}

	/**
	 * Route /properties/edit/:property_id
	 *
	 * @param $app: Application context
	 * @param $property_id: Property ID to edit
	 */
	public function edit($app, $collection_id = null, $property_id = null) {
		//Get property from DB first
		$record = $app->validate(Property::getProperty($property_id));

		//Check POST
		$values = $app->request()->post();
		if ($values) {
			//Save property
			$values['id'] = $property_id;

			//Check data
			if (!isset($values['category'])) {
				$values['category'] = [];
			}

			//Try to update database
			if (Property::update($property_id, $values)) {
				$app->flashSuccess(__('Property has been successfully updated.'));
				if (isset($values['submit'])) {
					$app->redirect($app->urlFor('properties_list'));
				} else {
					$app->redirect($app->urlFor('properties_edit', ['property_id' => $property_id]));
				}
			} else {
				$app->flashErrorNow(__('Problems with updating property!'));
			}

			$app->view()->set('values', $values);
		} else {
			$values = $record['Property'];
			$values->category = CategoryProperty::getCategoriesIds($property_id);;
		}
		

		//Get all categories available
		$categories = Category::getCategories();

		//Set values
		$app->view()->set('record', $record);
		$app->view()->set('values', $values);
		$app->view()->set('categories', $categories);
		$app->view()->set('action', 'edit');
		$app->commentSetView(Comment::MODEL_PROPERTY, $property_id);

		$app->setTitle(__('Edit property'));
		return $app->render('properties_add_edit.html');
	}

	/**
	 * Route /properties/delete/:property_id
	 *
	 * @param $app: Application context
	 * @param $property_id: Property ID to delete
	 */
	public function delete($app, $collection_id = null, $property_id = null) {
		$status = Property::delete($property_id);

		if ($app->request()->isAjax()) {
		//	return $app->toJSON(['Status' => $status], true, $app->toStatusCode($status));
		} 

		if ($status) {
			$app->flashSuccess(__('Property has been successfully deleted.'));
		} else {
			$app->flashError(__('Problems with deleting property!'));
		}
		$app->redirect($app->urlFor('properties_list'));
	}

	/**
	 * Update property with POST method
	 *
	 * POST /properties/update/:property_id
	 *
	 * @param $app: Application context
	 * @param $id: Property to update
	 */
	public function update($app, $collection_id = null, $id = null) {
		//Not succedded
		$success = false;

		//Get element
		$result = Property::getProperty($id);
		if ($result) {			
			//Update element
			$success = Property::update($id, $app->getPostUpdateFields());
		}

		//Return status
		return $app->toJSON(['Success' => $success], true, $app->toStatusCode($success));
	}

	/**
	 * Update property with POST method
	 *
	 * POST /properties/choices/:property_id
	 *
	 * @param $app: Application context
	 * @param $id: Property to update
	 */
	public function choices($app, $collection_id = null, $property_id = null) {
		$update = $add = $remove = [];

		//Check ajax
		if (!$app->request()->isAjax()) {
			$app->redirect($app->urlFor('properties_list'));
		}

		//Get property with choices
		$property = $app->validate(Property::getProperty($property_id, $collection_id, ['contain' => ['Propertychoice']]));

		$values = $app->request()->post();
		if ($values) {
			$propChoices = $property['Propertychoice'];
			$choices = (array)(isset($values['choices']) ? $values['choices'] : []);

			//Check all received choices
			foreach ($choices as $k => $v) {
				//Received already inserted id
				if (strpos($k, 'id_') === 0) {
					$id = intval(str_replace('id_', '', $k));
					foreach ($propChoices as $c) {
						//We already have choice in database
						//Compare to see if they match
						if ($c->id == $id && $v != $c->choice) {
							$update[$c->id] = $v;
						}
					}
				} else if (!empty(trim($v))) {
					$add[] = $v;
				}
			}

			//Check what we have to remove
			foreach ($propChoices as $c) {
				//Check what we should remove
				if (!isset($choices['id_' . $c->id])) {
					$remove[] = $c->id;
				}
			}

			//Make changes
			if (count($add) > 0) {
				foreach ($add as $value) {
					Propertychoice::insert($property_id, ['choice' => $value]);
				}
			}

			//Check for updates
			if (count($update)) {
				foreach ($update as $id => $value) {
					Propertychoice::update($id, ['choice' => $value]);
				}
			}

			//Check for remove
			if (count($remove) > 0) {
				Propertychoice::delete($remove, $collection_id);
			}

			//Invalidate
			if (count($add) || count($update) || count($remove)) {
				$property = $app->validate(Property::getProperty($property_id, $collection_id, ['contain' => ['Propertychoice']]));
				$values = false;

				$app->flashSuccessNow(__('Choices has been successfully updated.'));
			} else {
				$app->flashSuccessNow(__('No changes were made done to choices for this property.'));
			}
		}

		//Check values
		if (!$values) {
			//Format values
			$values['choices'] = [];
			foreach ($property['Propertychoice'] as $c) {
				$values['choices']['id_' . $c->id] = $c->choice;
			}
		}

		$app->view()->set('property', $property);
		$app->view()->set('values', $values);
		return $app->render('properties_choices.html');
	}
}