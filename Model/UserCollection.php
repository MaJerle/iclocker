<?php

namespace Model;

use \Inc\Model;

class UserCollection extends Model {
	//Set table name
	public $tableName = 'users_collections';

	//Format associations
	public $associations = [
		'belongsTo' => [
			'User' => [
				'foreignKey' => 'user_id',
				'counterCache' => 'collections_count',
				'conditions' => [
					'User.deleted' => 0
				]
			],
			'Collection' => [
				'foreignKey' => 'collection_id',
				'counterCache' => 'users_count',
				'conditions' => [
					'Collection.deleted' => 0
				]
			]
		]
	];
	
	//Check if user can access to collection and all its sub things
	public static function checkCollectionAccess($user_id, $collection_id) {
		//User is object
		if (is_array($user_id)) {
			if (parent::$app->isAdmin()) {
				return true;
			}
			$user_id = $user_id['User']->id;
		} else if (is_object($user_id)) {
			if (parent::$app->isAdmin()) {
				return true;
			}
			$user_id = $user_id->id;	
		}

		//Set options
		$options = [
			'type' => 'all',
			'conditions' => [
				'UserCollection.deleted' => 0,
				'UserCollection.user_id' => $user_id,
				'UserCollection.collection_id' => $collection_id,
				'UserCollection.revision_id' => 0
			]
		];

		return parent::$db->selectEx(new self(), $options);
	}

	//Get all collections for given user id
	public static function getCollections($user_id, $options = []) {
		$options = array_merge([
			'type' => 'all',
			'conditions' => []
		], $options);

		//Remove contain flag, all must be included!
		if (isset($options['contain'])) {
			unset($options['contain']);
		}

		//Conditions
		$options['conditions']['UserCollection.deleted'] = 0;
		$options['conditions']['UserCollection.revision_id'] = 0;
		$options['conditions']['UserCollection.user_id'] = $user_id;

		return parent::$db->selectEx(new self(), $options);
	}

	//Get a list of all collections for given user
	public static function getCollectionsIds($user_id) {
		return parent::$db->getIds(self::getCollections($user_id), 'UserCollection.collection_id');
	}

	//Get all collections for given user id
	public static function getUsers($collection_id, $options = []) {
		$options = array_merge([
			'type' => 'all',
			'conditions' => []
		], $options);

		//Remove contain flag, all must be included!
		if (isset($options['contain'])) {
			unset($options['contain']);
		}

		//Conditions
		$options['conditions']['UserCollection.deleted'] = 0;
		$options['conditions']['UserCollection.collection_id'] = $collection_id;

		return parent::$db->selectEx(new self(), $options);
	}

	//Get a list of all collections for given user
	public static function getUsersIds($collection_id) {
		return parent::$db->getIds(self::getUsers($collection_id), 'UserCollection.user_id');
	}

	//Update user collection relation according to selected user
	public static function updateUser($user_id, $new, $old = false) {
		$obj = new self();
		$add2 = [];

		//Get all records
		$records = self::getCollectionsIds($user_id);

		//Delete all records for this user first
		parent::deleteData($obj, ['user_id' => $user_id]);

		//Add values for new user
		$add = [];
		foreach ($new as $n) {
			$add[] = [$user_id, $n];
			$add2[] = $n;
		}

		//Add values to database
		if (!empty($add)) {
			parent::insertData($obj, ['user_id', 'collection_id'], $add);
		}

		//Update last modified record for syncing
		parent::updateLastModified(new User(), $user_id);
		parent::updateLastModified(new Collection(), $add2, $records);
	}

	//Update user collection relation according to selected collection
	public static function updateCollection($collection_id, $new, $old = false) {
		$add2 = [];
		$delete = [];

		//Get all records
		$records = self::getUsersIds($collection_id);

		//Check what we have to delete
		foreach ($records as $k => $r) {
			if (!in_array($r, $new)) {
				$delete[] = $r;
				unset($records[$k]);
			}
		}

		//Delete records we don't want anymore
		if (!empty($delete)) {
			parent::deleteData(new self(), ['collection_id' => $collection_id, 'user_id' => $delete, 'revision_id' => 0, 'deleted' => 0]);
		}

		//Add values for new collecton
		$add = [];
		foreach ($new as $n) {
			if (!in_array($n, $records)) {
				$add[] = [$collection_id, $n];
				$add2[] = $n;
			}
		}

		//Add values to database
		if (!empty($add)) {
			parent::insertData(new self(), ['collection_id', 'user_id'], $add);
		}

		//Update last modified record for syncing
		parent::updateLastModified(new Collection(), $collection_id);
		parent::updateLastModified(new User(), $add2, $records);
	}

	//Adds new record, matching user and collection
	public static function connectUserCollection($userID, $collectionID) {
		if (!$userID || !$collectionID) {
			return false;
		}

		return parent::insertData(new self(), ['user_id' => $userID, 'collection_id' => $collectionID]);
	}
}
