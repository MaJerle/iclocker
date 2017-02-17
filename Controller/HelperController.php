<?php

namespace Controller;

use \Inc\Model;

class HelperController extends Base {

	/**
	 * Sets counter cache for all elements (for upgrade only)
	 * 
	 * Route /helper/counter_cache
	 *
	 * @param $app: Application context
	 */
	public function set_counter_cache($app) {
		$classes = [
			'Category',
		//	'CategoryProperty',
			'Collection',
			'Comment',
			'Element',
			'Elementorder',
		//	'ElementorderProperty',
		//	'ElementProduct',
		//	'ElementProperty',
			'Orderelement',
			'Product',
			'Property',
			'Propertychoice',
			'Token',
			'User',
		//	'UserCollection',
		//	'Usertoken',
		];

		foreach ($classes as $className) {
			$objectName = '\\Model\\' . $className;
			$model = new $objectName();

			//Get all records for database
			$records = Model::find('all', [
				'contain' => []
			], $model);

			//Check records
			if (!$records || count($records) == 0) {
				print 'NO RECORDS: ' . $className . '<br />';
				continue;
			}

			//Format primary keys for each tablet
			$ids = $app->db->getIds($records, $className . '.' . $model->primaryKey);

			//Update counter cache for model
			$app->db->updateCounterCache($model, [$model->primaryKey => $ids], 'update');

			//Print message
			print 'DONE: ' . $className . '<br />';
		}
		exit;
	}
}
