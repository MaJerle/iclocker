<?php
namespace Model;

use \Inc\Model;
use \Model\EmaiLSender;

/**
 * 
 * @package default
 */
class Token extends Model {
	//Set table name
	public $tableName = 'tokens';
	
	//Validation errors
	private static $__validationErrors = [];
	//Columns for insert or update operation
	private static $__tableColumns = ['type', 'code', 'user_id'];

	//List of token types
	const TYPE_CONFIRM_ACCOUNT = 1;
	const TYPE_FORGOT_PASSWORD = 2;

	//Get status by code and include user
	public static function getToken($type, $code) {
		return parent::$db->selectEx(new self(), [
			'type' => 'first',
			'conditions' => [
				'Token.type' => $type,
				'Token.code' => $code,
				'Token.revision_id' => 0
			]
		]);
	}

	//Insert token to database
	public static function insert($type, $userid = null) {
		//Generate new code
		do {
			//Create code
			$code = parent::hash(microtime(true) * rand());
		} while (self::getToken($type, $code));

		//Insert into database and return inserted object
		$id = parent::insertData(new self(), [
			'type' => $type,
			'user_id' => $userid,
			'code' => $code
		]);

		if ($id) {
			return self::getToken($type, $code);
		}
		return $id;
	}

	//Delete token
	public static function delete($type, $code) {
		return parent::deleteData(new self(), ['type' => $type, 'code' => $code]);
	}

	//Validate insert
	private static function __validateInsert($data) {
		return true;
	}

	//Get validation errors
	public static function getValidationErrors() {
		return self::$__validationErrors;
	}
}