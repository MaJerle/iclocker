<?php

namespace Model;

use \Inc\Model;

/**
 * A coolection of functions that deal with Blind management
 * @package default
 */
class Comment extends Model {
	const MODEL_COLLECTION = 1;
	const MODEL_CATEGORY = 2;
	const MODEL_ELEMENT = 3;
	const MODEL_PROPERTY = 4;
	const MODEL_PRODUCT = 5;
	const MODEL_ORDER = 6;
	const MODEL_USER = 7;

	//Set table name
	public $tableName = 'comments';

	//Validation errors
	private static $__validationErrors = [];
	//Columns for SQL operations
	private static $__tableColumns = ['comment', 'foreign_id'];

	//Get comments
	public static function getComments($comment_id = null, $model = null, $foreign_id = null, $collection_id = null, $options = []) {
		$collection_id = parent::collection_id($collection_id);
		$options = array_merge([
			'type' => 'thread',
			'conditions' => [],
			'revision' => false
		], $options);
		if (!$options['revision']) {
			$options['conditions']['Comment.revision_id'] = 0;
		}

		if ($collection_id != null) {
			$options['conditions']['Comment.collection_id'] = $collection_id;
		}
		if ($model != null) {
			$options['conditions']['Comment.model'] = $model;
		}
		if ($foreign_id != null) {
			$options['conditions']['Comment.foreign_id'] = $foreign_id;
		}
		if ($comment_id != null) {
			$options['conditions']['Comment.id'] = $comment_id;
		}

		return parent::$db->selectEx(new self(), $options);
	}

	//Get comments
	public static function getComment($comment_id = null, $model = null, $foreign_id = null, $collection_id = null, $options = []) {
		return self::getComments($comment_id, $model, $foreign_id, $collection_id, array_merge($options, ['type' => 'first']));
	}

	//Insert new comment to database
	public static function insert($values) {
		if (!self::validateInsert($values)) {
			return false;
		}

		//Check for collection
		if ($values['model'] == self::MODEL_COLLECTION) {
			$values['collection_id'] = $values['foreign_id'];
		}

		//Add to database
		return parent::insertData(new self(), $values);
	}

	//Delete category
	public static function delete($comment_id) {
		return parent::deleteData(new self(), ['id' => $comment_id, 'collection_id' => parent::collection_id()]);
	}

	//Validate for insert operation
	public static function validateInsert($data) {
		$v = parent::getValidationObject($data);

		//Set rules
		$v->rule('required', 'comment')->message(__('Comment is required!'))
		  ->rule('lengthMin', 'comment', 1)->message(__('Comment size is too small!'))
		  ->rule('required', 'model')->message(__('Model is required!'))
		  ->rule('numeric', 'model')->message(__('Model must be numerical value!'))
		  ->rule('required', 'foreign_id')->message(__('Foreign id is required!'))
		  ->rule('numeric', 'foreign_id')->message(__('Foreign id must be numerical value!'));

		//Validate
		return parent::validate($v, self::$__validationErrors);
	}

	//Validate for insert operation
	public static function validateUpdate($data) {
		$v = parent::getValidationObject($data);

		//Set rules
		$v->rule('optional', 'name')
		  ->rule('lengthMin', 'name', 1)->message(__('Name size is too small!'))
		  ->rule('relationData', 'property', 'int')->message(__('Incorrect data format for category properties!'));

		//Validate
		return parent::validate($v, self::$__validationErrors);
	}

	//Get validation errors
	public static function getValidationErrors() {
		return self::$__validationErrors;
	}
}