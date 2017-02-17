<?php

namespace Model;
use \Inc\Model;

class Usersetting extends Model {
	//Set table name
	public $tableName = 'usersettings';

	//Format associations
	public $associations = [
		/*'belongsTo' => [
			'User' => [
				'foreignKey' => 'user_id',
				'conditions' => [
					'User.deleted' => 0
				]
			]
		]*/
	];
	
	//Validation errors
	private static $__validationErrors = [];
	//Columns for insert or update operation
	private static $__tableColumns = ['user_id', 'key', 'value'];

	//Default setting
	private static $__defaultSettings = [
		//Element based settings
		'show_element_warningquantity' => '1',
		'show_element_description' => '1',
		'show_element_category' => '1',
		'show_element_properties' => '0',
		'show_element_created' => '0'
	];

	//Gets list of settings for specific user
	public static function getSettings($user_id, $options = []) {
		$options = array_merge([
			'conditions' => [
				'Usersetting.user_id' => $user_id,
				'Usersetting.revision_id' => 0
			]
		], $options);

		//Get settings
		$results = parent::$db->selectEx(new self(), $options);

		//Format settings
		$settings = [];
		foreach ($results as $r) {
			$settings[$r['Usersetting']->setting] = $r['Usersetting']->value;
		}

		//Merge with default settings
		$settings = array_merge(self::$__defaultSettings, $settings);

		return $settings;
	}

	//Write setting for user
	public static function write($user_id, $key, $value) {
		$opts = [
			'type' => 'first',
			'conditions' => [
				'Usersetting.user_id' => $user_id,
				'Usersetting.setting' => $key
			]
		];
		$result = parent::$db->selectEx(new self(), $opts);
		if ($result) {
			//Check match
			if (strcmp($result['Usersetting']->value, $value) == 0) {
				return true;
			}

			//Update with new value
			$success = parent::updateData(new self(),
				['value' => $value],
				['user_id' => $user_id, 'setting' => $key]
			);

			if ($success) {
				//Create new record with old values and revision system
				$result['Usersetting'] = self::$app->obj2array($result['Usersetting']);
				$result['Usersetting']['revision_id'] = $result['Usersetting']['id'];
				unset($result['Usersetting']['id']);
				parent::insertData(new self(), $result['Usersetting']);
			}

			return $success;
		}

		//Insert new result
		return parent::insertData(new self(),
			['user_id' => $user_id, 'setting' => $key, 'value' => $value]
		);
	}

	//Write multiple settings
	public static function writeSettings($user_id, $settings) {
		if (is_array($settings)) {
			foreach ($settings as $k => $v) {
				self::write($user_id, $k, $v);
			}

			//Merge new settings with current user
			$user = parent::$app->User;
			$user['Usersetting'] = self::getSettings($user['User']->id);
			parent::$app->User = $user;

			return true;
		}

		return false;
	}
}