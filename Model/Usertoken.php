<?php

namespace Model;

use \Inc\Model;

/**
 * A coolection of functions that deal with Blind management
 * @package default
 */
class Usertoken extends Model {
	//Set table name
	public $tableName = 'usertokens';

	//Format associations
	public $associations = [
		'belongsTo' => [
			'User' => [
				'foreignKey' => 'user_id',
				'conditions' => [
					'User.deleted' => 0
				]
			]
		]
	];
	
	//Validation errors
	private static $__validationErrors = [];
	//Columns for insert or update operation
	private static $__tableColumns = [];

	//Create token from user object
	public static function createToken($user) {
		//Check valid user
		if (!is_object($user)) {
			return false;
		}

		//Create new token value
		do {
			//Create random token value
			$value = parent::hash(rand() * microtime(true));
		} while (self::getByToken($value));

		//Get dynamic token
		$dynamic = parent::hash(rand() * microtime(true));

		//Insert new token
		$insID = parent::insertData(new Usertoken(), [
			'user_id' => $user->id,
			'token' => $value,
			'dynamic_token' => $dynamic,
			'validto' => parent::getDate(time() + 86400),
			'ip' => parent::$app->request()->getIp()
		]);

		//Return token object
		return self::getById($insID);
	}

	//Updates dynamic token to database and returns new value
	public static function updateDynamicToken() {
		if (!parent::$app->user_logged) {
			return;
		}

		//Get usertoken and update it
		$dynamic = parent::hash(rand() * microtime(true));

		//Check success
		if (parent::updateData(new Usertoken(), ['dynamic_token' => $dynamic], ['id' => parent::$app->User['Usertoken']->id])) {
			return $dynamic;
		}

		//Return old one
		return parent::$app->User['Usertoken']->dynamic_token;
	}

	//Returns tokens
	public static function getTokens($user_id = null, $options = []) {
		$options = array_merge([
			'type' => 'all',
			'conditions' => [
				'Usertoken.deleted' => 0
			],
			'contain' => [],
			'revision' => false
		], $options);
		if (!$options['revision']) {
			$options['conditions']['Usertoken.revision_id'] = 0;
		}

		//Check conditions
		if ($user_id != null) {
			$options['conditions']['Usertoken.user_id'] = $user_id;
		}

		//Find
		return parent::$db->selectEx(new Usertoken(), $options);
	}

	//Get record by token value
	public static function getByToken($token) {
		return parent::$db->selectEx(new Usertoken(), [
			'type' => 'first',
			'conditions' => [
				'Usertoken.token' => $token
			]
		]);
	}

	//Get record by token value
	public static function getById($id) {
		return parent::$db->selectEx(new Usertoken(), [
			'type' => 'first',
			'conditions' => [
				'Usertoken.deleted' => 0,
				'Usertoken.id' => $id
			]
		]);
	}

	//Returns token and user selected by token value
	public static function getWithUserByToken($tokenValue) {
		return parent::$db->selectEx(new Usertoken(), [
			'type' => 'first',
			'conditions' => [
				'Usertoken.deleted' => 0,
				'Usertoken.validto > NOW()',
				'Usertoken.token' => $tokenValue,
				'Usertoken.ip' => parent::$app->request()->getIp()
			]
		]);
	}
}
