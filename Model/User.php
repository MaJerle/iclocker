<?php

namespace Model;

use \Inc\Model;
use \Model\Usertoken;
use \Model\Usersetting;
use \Model\Token;
use \User\Setting;

/**
 * A coolection of functions that deal with Blind management
 * @package default
 */
class User extends Model {
	//Set table name
	public $tableName = 'users';

	//Format associations
	public $associations = [
		'manyToMany' => [
			'Collection' => [
				'joinModel' => 'UserCollection',
				'foreignKey' => 'user_id',
				'associationForeignKey' => 'collection_id',
				'conditions' => [
					'Collection.deleted' => 0
				],
				'changes' => false
			]
		],
		'hasMany' => [
			'Usertoken' => [
				'foreignKey' => 'user_id',
				'limit' => 1,
				'order' => 'Usertoken.id DESC',
				'conditions' => [
					'Usertoken.deleted' => 0
				]
			],
			'UserCollection' => [
				'foreignKey' => 'user_id',
				'changes' => true
			]
		]
	];

	//Virtual fields for query
	public $virtualFields = [
		'full_name' => 'CONCAT(User.first_name, \' \', User.last_name)'
	];
	
	private static $__userID;
	//Validation errors
	private static $__validationErrors = [];
	//Columns for insert or update operation
	private static $__tableColumns = ['first_name', 'username', 'last_name', 'password', 'access_group', 'last_login'];

	//List all users
	public static function getUsers($users = null, $options = []) {
		$options = array_merge([
			'type' => 'all',
			'conditions' => [],
			'contain' => [],
			'order' => 'User.username ASC',
			'changes' => false,
			'revision' => false
		], $options);
		if (!$options['revision']) {
			$options['conditions']['User.revision_id'] = 0;
		}
		if (!$options['changes']) {
			$options['conditions']['User.deleted'] = 0;
		}

		//User ID
		if (!empty($users)) {
			$options['conditions']['User.id'] = $users;
		}

		return parent::$db->selectEx(new self(), $options);
	}

	//Get collection by given ID
	public static function getUser($user_id, $options = []) {
		//Make query
		$options = array_merge([
			'type' => 'first',
			'conditions' => [],
			'settings' => 1
		], $options);

		//User ID
		if (!empty($user_id)) {
			$options['conditions']['User.id'] = $user_id;
		}

		$user = parent::$db->selectEx(new self(), $options);

		if ($user && $options['settings']) {
			$user['Usersetting'] = Usersetting::getSettings($user['User']->id);
		}

		return $user;
	}

	//Get current user
	public static function getCurrentUser() {
		return self::getUser(parent::userid(), ['contain' => false, 'createdBy' => false]);
	}

	//Returns user with username and password.
	//User must not be deleted!
	public static function getUserByUsernameAndPassword($username, $password) {
		//Make query
		$query = [
			'type' => 'first',
			'conditions' => [
				'User.username' => $username,
				'User.password' => parent::hash($password),
				'User.deleted' => 0
			],
			'contain' => [
				'Usertoken'
			]
		];

		//Get user
		$user = parent::$db->selectEx(new self(), $query);

		//Go to first position
		if (!empty($user['Usertoken'])) {
			$user['Usertoken'] = $user['Usertoken'][0];
		}

		//Return user object
		return $user;
	}

	//Returns status with given username if exists
	public static function getUserByUsername($username) {
		//Make query
		$query = [
			'type' => 'first',
			'conditions' => [
				'User.username' => $username,
				'User.deleted' => 0
			],
			'contain' => []
		];

		return parent::$db->selectEx(new self(), $query);
	}

	//Login user
	public static function login($username, $password) {
		//Get user by username and password
		$user = self::getUserByUsernameAndPassword($username, $password);

		//Check for user
		if (!$user) {
			return false;
		}

		//Update last login value in user database
		self::update($user['User']->id, ['last_login' => parent::getDate()]);

		//If user was found in database, create new token for user
		$token = Usertoken::createToken($user['User']);		

		//We have user logged IN, add it to main application
		parent::$app->User = [
			'User' => $user['User'],
			'Usertoken' => $token['Usertoken'],
			'Usersetting' => Usersetting::getSettings($user['User']->id)
		];
		parent::$app->user_logged = true;

		//If token was not created
		if (!$token) {
			return false;
		}

		//Return token object including user
		return $token;
	}

	//Forgot password token
	public static function forgot_password($values) {
		if (!self::validateForgotPassword($values)) {
			return false;
		}

		//Get user
		$user = self::getUserByUsername($values['username']);

		//Check again
		if (!$user) {
			return false;
		}

		//Create token
		$token = Token::insert(Token::TYPE_FORGOT_PASSWORD, $user['User']->id);

		//Send email
		if ($token) {
			$content = __('
We have received request for password reset on your profile.
<br />
If you were trying to reset your password, please use url below to change your password.
<br /><br />
__URL__
<br /><br />
If this was not you, you can clearly discard this message.<br />
');
			$url = parent::$app->getHostUri() . parent::$app->urlFor('reset_password') . '?code=' . $token['Token']->code;
			$content = str_replace('__URL__', $url, $content);
			return EmailSender::sendEmail($user['User']->username, __('Password change request'), $content);
		}

		return false;
	}

	//Forgot password token
	public static function reset_password($values) {
		if (!self::validateResetPassword($values)) {
			return false;
		}

		//Get user
		$user = self::getUserByUsername($values['username']);
		if (!$user) {
			return false;
		}

		//Create new password
		$password = parent::hash($values['password']);

		//Update password
		$success = parent::updateData(new self(), ['password' => $password], ['id' => $user['User']->id]);

		//Delete token
		if ($success) {
			Token::delete(Token::TYPE_FORGOT_PASSWORD, $values['code']);
		}

		return $success;
	}

	//Insert new data
	public static function insert($data) {
		//Check insert validation
		if (!self::validateInsert($data)) {
			return false;
		}
		//Check password validation
		if (!self::validatePassword($data)) {
			return false;
		}

		//Check for username
		if (!isset($data['username'])) {
			return false;
		}

		//Check if user already exists with that email
		$emailUser = self::getUserByUsername($data['username']);

		//User already exists with that email?
		if ($emailUser) {
			return false;
		}

		//Hash password
		$data['password'] = parent::hash($data['password']);

		//Check for user group
		if (!parent::$app->isAdmin() && isset($data['access_group'])) {
			unset($data['access_group']);
		}

		//Insert to database
		$insID = parent::insertData(new self(), parent::formatColumns(self::$__tableColumns, $data));

		//Check for relation table between collections, access only for admin
		if ($insID && isset($data['collection']) && parent::$app->isAdmin()) {
			//Update collection
			UserCollection::updateUser($insID, $data['collection'], false);
		}

		return $insID;
	}

	//Update collection
	public static function update($user_id, $data) {
		//save current user ID
		self::$__userID = $user_id;

		//Check validation
		if (!self::validateUpdate($data)) {
			return false;
		}

		//Check password
		if ((isset($data['password']) && !empty($data['password'])) || isset($data['password2']) && !empty($data['password2'])) {
			//Validate password
			if (!self::validatePassword($data)) {
				return false;
			}
		} else {
			unset($data['password']);
		}

		//Hash if exists
		if (isset($data['password'])) {
			//Hash password
			$data['password'] = parent::hash($data['password']);
		}

		//If username requested
		if (isset($data['username'])) {
			//Check if user already exists with that email
			$emailUser = self::getUserByUsername($data['username']);

			//User already exists?
			if ($emailUser && $emailUser['User']->id != $user_id) {
				return false;
			}
		}

		//Check for user group
		if (!parent::$app->user_logged || (!parent::$app->isAdmin() && isset($data['access_group']))) {
			unset($data['user_group']);
		}

		//Try to update
		$success = parent::updateData(new self(), parent::formatColumns(self::$__tableColumns, $data), ['id' => $user_id]);

		//Check for relation table between collections, access only for admin
		if ($success && isset($data['collection']) && parent::$app->isAdmin()) {
			//Update collection relation
			UserCollection::updateUser($user_id, $data['collection'], true);
		}

		return $success;
	}

	//Delete collection with given ID
	public static function delete($user_id) {
		return parent::deleteData(new self(), ['id' => $user_id]);
	}

	//Update user
	public static function updateCurrentUser($data) {
		if (!is_array($data)) {
			self::$__validationErrors[] = __('Invalid data!');
			return false;
		}

		//Remove access group
		if (!parent::$app->isAdmin()) {
			if (isset($data['access_group'])) {
				unset($data['access_group']);
			} 
			if (isset($data['username'])) {
				unset($data['username']);
			}
		}
		if (isset($data['collection'])) {
			unset($data['collection']);
		}

		if (empty($data)) {
			self::$__validationErrors[] = __('Empty data!');
			return false;
		}

		//Update user
		return self::update(parent::userid(), $data);
	}

	//Register account
	public static function register($data) {
		//Check insert validation
		if (!self::validateRegister($data)) {
			return false;
		}

		//Check if we are first registered user
		$cnt = parent::find('count', ['contain' => []]);

		//We are first user, set as admin
		if ($cnt == 0) {
			$data['access_group'] = 1;
		} else {
			$data['access_group'] = 0;
		}

		//Hash password
		$data['password'] = parent::hash($data['password']);

		//Insert to database
		$insID = parent::insertData(new self(), parent::formatColumns(self::$__tableColumns, $data));

		//Set created by field
		if ($insID) {
			parent::updateData(new self(), [
				'created_by' => $insID,
				'modified_by' => $insID
			], ['id' => $insID]);
		}

		return $insID;
	}

	//Validate for insert operation
	public static function validateInsert($data) {
		$v = parent::getValidationObject($data);
		$v->addRule('emailUnique', array('\Model\User', 'emailUnique'));

		//Set rules
		$v->rule('required', 'username')->message(__('Username is required!'))
		  ->rule('email', 'username')->message(__('Username must be a valid e-mail address!'))
		  ->rule('emailUnique', 'username')->message(__('User with that e-mail already exists!'))
		  ->rule('required', 'first_name')->message(__('First name is required!'))
		  ->rule('required', 'last_name')->message(__('Last name is required!'))
		  ->rule('relationData', 'collection', 'int')->message(__('Incorrect data format for user collections!'));

		//Validate
		return parent::validate($v, self::$__validationErrors);
	}

	//Validate for update operation
	public static function validateUpdate($data) {
		$v = parent::getValidationObject($data);
		$v->addRule('emailUnique', array('\Model\User', 'emailUnique'));

		//Set rules
		$v->rule('optional', 'username')
		  ->rule('email', 'username')->message(__('Username must be a valid e-mail address!'))
		  ->rule('emailUnique', 'username')->message(__('User with that e-mail already exists!'))
		  ->rule('optional', 'first_name')
		  ->rule('lengthMin', 'first_name', 1)->message(__('User name is too small!'))
		  ->rule('optional', 'last_name')
		  ->rule('lengthMin', 'last_name', 1)->message(__('User surname is too small!'));

		//Validate
		return parent::validate($v, self::$__validationErrors);
	}

	//validate password input
	public static function validatePassword($data, $v = false, $validate = true) {
		if (!$v) {
			$v = parent::getValidationObject($data);
		}

		//Set rules
		$v->rule('required', 'password')->message(__('Password is required!'))
		  ->rule('lengthMin', 'password', 6)->message(__('Password length must be at least 6!'))
		  ->rule('required', 'password2')->message(__('Password is required!'))
		  ->rule('equals', 'password2', 'password')->message(__('Passwords do not match!'));

		//Validate
		if ($validate) {
			return parent::validate($v, self::$__validationErrors);
		}
	}

	//Validate for insert operation
	public static function validateRegister($data) {
		$v = parent::getValidationObject($data);
		$v->addRule('emailUnique', array('\Model\User', 'emailUnique'));

		//Set rules
		$v->rule('required', 'username')->message(__('Username is required!'))
		  ->rule('email', 'username')->message(__('Username must be a valid e-mail address!'))
		  ->rule('emailUnique', 'username')->message(__('User with that e-mail already exists!'))
		  ->rule('required', 'first_name')->message(__('First name is required!'))
		  ->rule('required', 'last_name')->message(__('Last name is required!'))
		  ->rule('relationData', 'collection', 'int')->message(__('Incorrect data format for user collections!'));

		//Merge password
		self::validatePassword($data, $v, false);

		//Validate
		return parent::validate($v, self::$__validationErrors);
	}

	//Validate for insert operation
	public static function validateForgotPassword($data) {
		$v = parent::getValidationObject($data);
		$v->addRule('emailExists', array('\Model\User', 'emailExists'));

		//Set rules
		$v->rule('required', 'username')->message(__('Username is required!'))
		  ->rule('email', 'username')->message(__('Username must be a valid e-mail address!'))
		  ->rule('emailExists', 'username')->message(__('User with this username does not exists!'));

		//Validate
		return parent::validate($v, self::$__validationErrors);
	}

	//Validate for insert operation
	public static function validateResetPassword($data) {
		$v = parent::getValidationObject($data);
		$v->addRule('emailExists', array('\Model\User', 'emailExists'));
		$v->addRule('emailMatchesCodeResetPassword', array('\Model\User', 'emailMatchesCodeResetPassword'));

		//Set rules
		$v->rule('required', 'code')->message(__('Code is required!'))
		  ->rule('required', 'username')->message(__('Username is required!'))
		  ->rule('email', 'username')->message(__('Username must be a valid e-mail address!'))
		  ->rule('emailExists', 'username')->message(__('User with this username does not exists!'))
		  ->rule('emailMatchesCodeResetPassword', 'username', 'code')->message(__('E-mail is invalid!'));

		//Merge password
		self::validatePassword($data, $v, false);

		//Validate
		return parent::validate($v, self::$__validationErrors);
	}

	//Get validation errors
	public static function getValidationErrors() {
		return self::$__validationErrors;
	}

	//Callback for validation
	public static function emailUnique($fieldName, $fieldValue) {
		//Check if exists
		$emailUser = self::getUserByUsername($fieldValue);

		//Rule failed?
		if ($emailUser && $emailUser['User']->id != self::$__userID) {
			return false;
		}

		//Rule succedded
		return true;
	}

	//Callback for validation
	public static function emailExists($fieldName, $fieldValue) {
		return self::getUserByUsername($fieldValue);
	}


	//Code matches with user email and id
	public static function emailMatchesCodeResetPassword($fieldName, $fieldValue, $customParams = [], $values = []) {
		$compareField = $customParams[0];
		if (!isset($values[$fieldName]) || !isset($values[$compareField])) {
			return false;
		}

		//Get user by email
		$user = User::getUserByUsername($fieldValue);
		if (!$user) {
			return false;
		}

		//Get token by code
		$token = Token::getToken(Token::TYPE_FORGOT_PASSWORD, $values[$compareField]);
		if (!$token) {
			return false;
		}

		//Must match
		return $token['Token']->user_id == $user['User']->id;
	}
}
