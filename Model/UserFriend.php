<?php

namespace Model;

use \Inc\Model;

/**
 * A coolection of functions that deal with Blind management
 * @package default
 */
class UserFriend extends Model {
	//Set table name
	public $tableName = 'users_friends';
	public $associations = [
		'belongsTo' => [
			'User1' => [
				'modelName' => 'User',
				'foreignKey' => 'user1',
				'conditions' => [
					'User1.deleted' => 0,
				]
			],
			'User2' => [
				'modelName' => 'User',
				'foreignKey' => 'user2',
				'conditions' => [
					'User2.deleted' => 0,
				]
			]
		]
	];

	//Get friends
	public static function getFriends($user = null) {
		if ($user === null || !$user) {
			$user = parent::$app->User['User']->user_id;
		}

		//Format options
		$options = [
			'conditions' => [
				'OR' => [
					'UserFriend.user1' => $user,
					'UserFriend.user2' => $user,
				]
			]
		];

		//Get a list of all friends
		return parent::$app->db->selectEx(new UserFriend(), $options);
	}
}
