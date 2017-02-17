<?php

namespace Model;

use \Inc\Model;

/**
 * A coolection of functions that deal with Blind management
 * @package default
 */
class DbEvent extends Model {
	//Set table name
	public $tableName = 'events';

	//Validation errors
	private static $__validationErrors = [];
	//Columns for SQL operations
	private static $__tableColumns = ['model', 'type', 'foreign_id'];
}