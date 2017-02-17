<?php

namespace Model;

use \Inc\Model;

/**
 * A coolection of functions that deal with Blind management
 * @package default
 */
class Subproduct extends Model {
	//Set table name
	public $tableName = 'products';

	//Format associations
	public $associations = [
		'manyToMany' => [
			'Product' => [
				'joinModel' => 'ProductSubproduct',
				'foreignKey' => 'subproduct_id',
				'associationForeignKey' => 'product_id',
				'conditions' => [
					'Subproduct.deleted' => 0
				],
				'changes' => false
			]
		]
	];
}
