<?php

namespace Controller;

use \Model\Elementorder;
use \Model\Orderelement;
use \Model\Element;
use \Model\Comment;
use \Model\Property;
use \Model\ElementorderProperty;
use \Model\ElementProperty;
use \Inc\Model;

class OrdersController extends Base {
	/**
	 * Route /collection/:collection_id/orders
	 *
	 * @param $app: Application context
	 */
	public function index($app) {
		//Get all orders
		$orders = Elementorder::getOrders();

		$app->view()->set('orders', $orders);

		$app->setTitle(__('Orders'));
		return $app->render('orders_index.html');
	}

	/**
	 * Route /collection/:collection_id/orders/add
	 *
	 * @param $app: Application context
	 */
	public function add($app, $collection_id = null) {
		//Check if request was made
		$values = $app->request()->post();
		if ($values) {
			//Add category to database
			if (($id = Elementorder::insert($values))) {
				//Redirect to orders
				$app->flashSuccess(__('Order has been successfully added.'));		

				//Add comment
				$app->commentAdd(Comment::MODEL_ORDER, $id, $values);
				
				if (isset($values['saveandnew'])) {
					$app->redirect($app->urlFor('orders_add'));
				} else {
					$app->redirect($app->urlFor('orders_list'));
				}
			} else {
				$app->flashErrorNow(__('Problems with creating order!'));
			}
		}

		//Get properties
		$properties = Property::getProperties(null, null, [
			'conditions' => [
				'Property.data_type != ' . Property::TYPE_FILEUPLOAD
			]
		]);
		
		//Set values
		$app->view()->set('values', $values);
		$app->view()->set('properties', $properties);
		$app->view()->set('action', 'add');

		$app->setTitle(__('Add order'));
		return $app->render('orders_add_edit.html');
	}

	/**
	 * Route /collection/:collection_id/orders/edit/:order_id
	 *
	 * @param $app: Application context
	 * @param $order_id: Category ID to update
	 */
	public function edit($app, $collection_id = null, $order_id = null) {
		//Get category from DB first
		$record = $app->validate(Elementorder::getOrder($order_id));

		//Check if request was made
		$values = $app->request()->post();
		if ($values) {
			//Try to update category and relation table
			if (Elementorder::update($order_id, $values)) {
				//Redirect to orders
				$app->flashSuccess(__('Order has been successfully updated.'));
				if (isset($values['submit'])) {
					$app->redirect($app->urlFor('orders_list'));
				} else {
					$app->redirect($app->urlFor('orders_edit', ['elementorder_id' => $order_id]));
				}
			} else {
				$app->flashErrorNow(__('Problems with updating order!'));
			}
		} else {
			$values = $record['Elementorder'];
			$values->datecreated = (new \DateTime($values->datecreated))->format('Y-m-d H:i:s');
			$values->dateordered = (new \DateTime($values->dateordered))->format('Y-m-d H:i:s');

			//Get used properties
			$properties = ElementorderProperty::getProperties($record['Elementorder']->id);

			//Save used property ID
			if (count($properties)) {
				$values->property = [$properties[0]['Property']->id];
			}
		}

		//Get properties
		$properties = Property::getProperties(null, null, [
			'conditions' => [
				'Property.data_type != ' . Property::TYPE_FILEUPLOAD
			]
		]);

		$app->view()->set('record', $record);
		$app->view()->set('values', $values);
		$app->view()->set('properties', $properties);
		$app->view()->set('action', 'edit');

		$app->commentSetView(Comment::MODEL_ORDER, $order_id);

		$app->setTitle(__('Edit order'));
		return $app->render('orders_add_edit.html');
	}

	/**
	 * Route /collection/:collection_id/orders/delete/:elementorder_id
	 *
	 * @param $app: Application context
	 * @param $order_id: Order id to delete
	 */
	public function delete($app, $collection_id = null, $order_id = null) {
		$status = Elementorder::delete($order_id);

		if ($app->request()->isAjax()) {
		//	return $app->toJSON(['Status' => $status], true, $app->toStatusCode($status));
		} 

		if ($status) {
			$app->flashSuccess(__('Order has been successfully deleted.'));
		} else {
			$app->flashError(__('Problems with deleting order!'));
		}
		$app->redirect($app->urlFor('orders_list'));
	}


	/**
	 * Route /collection/:collection_id/order/:elementorder_id/elements
	 *
	 * @param $app: Application context
	 * @param $order_id: Order id
	 */
	public function elements($app, $collection_id = null, $order_id = null) {
		//Get order
		$order = Elementorder::getOrderWithElements($order_id);

		//Get order
		if (!$order) {
			$app->redirect($app->urlFor('orders_list'));
		}

		//Get element ids
		$elementids = [];
		foreach ($order['Orderelement'] as $o) {
			$elementids[] = $o->id;
		}

		//Get other orders
		$orders = Elementorder::getOrders();

		//Check for POST
		$values = $app->request()->post();
		if ($values) {
			//Get IDS of selected elements
			$ids = isset($values['orderelement']) ? $values['orderelement'] : [];

			$success = true;
			if (!empty($ids)) {
				//Check if they exists in array
				foreach ($ids as $k => $id) {
					if (!in_array($id, $elementids)) {
						unset($ids[$k]);
					}
				}

				//Check option
				$option = strtolower($values['option']);

				//Check actions
				if ($option == 'order' || $option == 'order_cancel') {
					$success = Orderelement::update($ids, ['ordered' => '1']);
				} else if ($option == 'cancel') {
					$success = Orderelement::update($ids, ['ordered' => '0']);
				} 

				//Set others as not ordered
				if ($success && $option == 'order_cancel') {
					$othids = [];
					foreach ($elementids as $id) {
						if (!in_array($id, $ids)) {
							$othids[] = $id;
						}
					}

					$success = Orderelement::update($othids, ['ordered' => '0']);
				}
			}

			//Check for success
			if ($success) {
				$app->flashSuccess(__('Bulk action successfully completed.'));
			} else {
				$app->flashError(__('Bulk action failed!'));
			}

			//Redirect back
			$app->redirect($app->urlFor('order_elements_list', ['elementorder_id' => $order_id]));
		}

		//Output data
		$app->view()->set('orders', $orders);
		$app->view()->set('order', $order);

		$app->setTitle(__('Order elements'));
		return $app->render('orders_elements.html');
	}

	/**
	 * Route /collection/:collection_id/order/:elementorder_id/elements/add
	 *
	 * @param $app: Application context
	 * @param $order_id: Order id
	 */
	public function elements_add($app, $collection_id = null, $order_id = null) {
		//Get order
		$order = $app->validate(Elementorder::getOrder($order_id));

		//Check for errors
		if ($order['Elementorder']->status != Elementorder::STATUS_OPEN) {
			$app->flashError(__('You can\'t add elements to order which is not open!'));
			$app->redirect($app->urlFor('orders_list'));
		}

		//Check values
		$values = $app->request()->post();
		if ($values && !isset($values['button_reload'])) {
			if (Orderelement::insert($order_id, $values)) {
				$app->flashSuccess(__('Element has been successfully added to order.'));
				if (isset($values['saveandnew'])) {
					$app->redirect($app->urlFor('orders_elements_add', ['elementorder_id' => $order_id]));
				} else {
					$app->redirect($app->urlFor('order_elements_list', ['elementorder_id' => $order_id]));
				}
			} else {
				$app->flashErrorNow(__('There were problems with adding new element to order!'));
			}
		}

		//Get order name
		$orderName = $order['Elementorder']->name;

		//Get elements suitable for this order
		$elements = [];
		if (isset($order['Property'])) {
			$elements = ElementProperty::getElements(Model::getIds($order['Property'], 'id'), [
				'order' => 'Element.name ASC'
			]);
		}

		//Output data
		$app->view()->set('order', $order);
		$app->view()->set('elements', $elements);
		$app->view()->set('values', $values);

		$app->setTitle(__('Add order element'));
		return $app->render('orders_elements_add.html');
	}

	/**
	 * Route /collection/:collection_id/order/:order_id/elements/delete/:element_id
	 *
	 * @param $app: Application context
	 * @param $order_id: Order id
	 */
	public function elements_delete($app, $collection_id = null, $order_id = null, $element_id = null) {
		if (Orderelement::delete($order_id, $element_id)) {
			$app->flashSuccess(__('Element deleted successfully from order.'));
		} else {
			$app->flashError(__('Element was not deleted from order!'));
		}
		$app->redirect($app->urlFor('order_elements_list', ['elementorder_id' => $order_id]));
	}

	/**
	 * Route /collection/:collection_id/order/:order_id/elements/add
	 *
	 * @param $app: Application context
	 * @param $order_id: Order id
	 */
	public function elements_update($app, $collection_id = null, $order_id = null, $orderelement_id = null) {
		//Not succedded
		$success = false;

		//Get element
		$result = Orderelement::getElementWithOrder($order_id, $orderelement_id);
		if ($result && $result['Elementorder']->status == Elementorder::STATUS_OPEN) {		
			//Update element
			$success = Orderelement::update($orderelement_id, $app->getPostUpdateFields());
		}

		//Return status
		return $app->toJSON(['Success' => $success], true, $app->toStatusCode($success));
	}

	/**
	 * Route /collection/:collection_id/order/sync/:order_id
	 *
	 * @param $app: Application context
	 * @param $order_id: Order id
	 */
	public function sync_elements($app, $collection_id = null, $order_id = null) {
		//Get order with all elements
		$order = $app->validate(Elementorder::getOrderWithElements($order_id));

		//Check if order actually ordered
		if ($order['Elementorder']->status != '2') {
			$app->flashError(__('This orders has has not been ordered!'));
			$app->redirect($app->urlFor('orders_list'));
		}

		//Check if already synced
		if ($order['Elementorder']->synced != '0') {
			$app->flashError(__('This orders has already been synced!'));
			$app->redirect($app->urlFor('orders_list'));
		}

		//Get element ids
		$orderelementsids = [];
		foreach ($order['Orderelement'] as $e) {
			//Check if element is valid and element on order was ordered
			if (!empty($e->element_id) && $e->ordered) {
				//Get quantity from order
				if ($order['Elementorder']->quantity_type == '0') {
					$quantity = $e->minquantity;
				} else {
					$quantity = $e->desiredquantity;
				}

				//Update element in database
				Element::increaseQuantity($e->element_id, $quantity, sprintf(__('Quantity increased from %s order.'), $order['Elementorder']->name));
			}
		}

		//Update order and set synced OK
		$success = Elementorder::update($order_id, ['synced' => '1']);

		//Check for ajax
		if ($app->request()->isAjax()) {
		//	return $app->toJSON(['Success' => $success], true, $app->toStatusCode($success));
		}

		//Redirect
		$app->flashSuccess(__('Elements were successfully synchronized with database.'));
		$app->redirect($app->urlFor('orders_list') . '?status=' . intval($success));
	}

	/**
	 * Update order with POST method
	 *
	 * POST /orders/update/:elementorder_id
	 *
	 * @param $app: Application context
	 * @param $id: Order to update
	 */
	public function update($app, $collection_id = null, $id = null) {
		//Not succedded
		$success = false;

		//Get element
		$result = Elementorder::getOrder($id);
		if ($result) {			
			//Update element
			$success = Elementorder::update($id, $app->getPostUpdateFields());
		}

		//Return status
		return $app->toJSON(['Success' => $success], true, $app->toStatusCode($success));
	}
}
