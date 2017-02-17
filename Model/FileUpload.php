<?php

namespace Model;

use \Inc\Model;

/**
 * A coolection of functions that deal with Blind management
 * @package default
 */
class FileUpload extends Model {
	//Set table name
	public $tableName = 'files';

	//Validation errors
	private static $__validationErrors = [];

	//Columns for SQL operations
	private static $__tableColumns = ['name', 'servername', 'mime', 'size'];

	//Allowed file types for upload
	private static $__allowedTypes = [
		'txt' => 'text/plain',
		'htm' => 'text/html',
		'html' => 'text/html',
		//'php' => 'text/html',
		'css' => 'text/css',
		//'js' => 'application/javascript',
		'json' => 'application/json',
		'xml' => 'application/xml',
		'swf' => 'application/x-shockwave-flash',
		'flv' => 'video/x-flv',

		// image
		'png' => 'image/png',
		'jpe' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'jpg' => 'image/jpeg',
		'gif' => 'image/gif',
		'bmp' => 'image/bmp',
		'ico' => 'image/vnd.microsoft.icon',
		'tiff' => 'image/tiff',
		'tif' => 'image/tiff',
		'svg' => 'image/svg+xml',
		'svgz' => 'image/svg+xml',

		// archives
		'zip' => 'application/zip',
		'rar' => 'application/x-rar-compressed',
		//'exe' => 'application/x-msdownload',
		//'msi' => 'application/x-msdownload',
		//'cab' => 'application/vnd.ms-cab-compressed',

		// audio/video
		'mp3' => 'audio/mpeg',
		'qt' => 'video/quicktime',
		'mov' => 'video/quicktime',

		// adobe
		'pdf' => 'application/pdf',
		'psd' => 'image/vnd.adobe.photoshop',
		'ai' => 'application/postscript',
		'eps' => 'application/postscript',
		'ps' => 'application/postscript',

		// ms office
		'doc' => 'application/msword',
		'rtf' => 'application/rtf',
		'xls' => 'application/vnd.ms-excel',
		'ppt' => 'application/vnd.ms-powerpoint',

		// open office
		'odt' => 'application/vnd.oasis.opendocument.text',
		'ods' => 'application/vnd.oasis.opendocument.spreadsheet'
	];

	//Returns a list of files
	public static function getFiles($file_id = null, $options = []) {
		$options = array_merge([
			'type' => 'all',
			'conditions' => [],
			'changes' => false,
			'revision' => false
		], $options);
		if (!$options['revision']) {
			$options['conditions']['FileUpload.revision_id'] = 0;
		}
		if (!$options['changes']) {
			$options['conditions']['FileUpload.deleted'] = 0;
		}
		if ($file_id != null) {
			$options['conditions']['FileUpload.id'] = $file_id;
		}

		return parent::$db->selectEx(new self(), $options);
	}

	//Get file
	public static function getFile($file_id = null, $options = []) {
		return self::getFiles($file_id, array_merge(['type' => 'first'], $options));
	}

	//Upload file
	public static function upload($file) {
		if (
			!isset($file['name']) || !isset($file['tmp_name']) ||
			!isset($file['size']) || !isset($file['type']) ||
			!isset($file['error'])
		) {
			return false;
		}
		if ($file['error'] != 0) {
			return false;
		}
		//Check mime type
		if (!in_array(strtolower($file['type']), self::$__allowedTypes)) {
			return false;
		}

		//Check if file exists
		if (!file_exists($file['tmp_name'])) {
			return false;
		}

		//Calculate MD5 hash
		$file['md5hash'] = md5_file($file['tmp_name']);

		//Check if file exists
		$fileDb = self::getFile(null, [
			'conditions' => [
				'FileUpload.name' => $file['name'],
				'FileUpload.size' => $file['size'],
				'FileUpload.mime' => $file['type'],
				'FileUpload.md5hash' => $file['md5hash']
			]
		]);

		//Check if file is in DB
		if ($fileDb) {
			//Check if file exists on server
			if (file_exists(parent::$app->config['uploads']['path'] . $fileDb['FileUpload']->servername)) {
				//Remove uploaded file
				try {
					unlink($file['tmp_name']);
				} catch (\Exception $e) {

				}

				//Return ID
				return $fileDb['FileUpload']->id;
			}
		}

		//Create file name
		$fileName = md5(microtime(true) . rand(0, 1000000) . rand(0, 1000000));

		//Get file extension
		$ext = '';
		if (substr($file['name'], -7) == '.tar.gz') {
			$ext = 'tar.gz';
		} else {
			$tokens = explode('.', $file['name']);
			if (count($tokens) > 1) {
				$ext = array_pop($tokens);
			} 
		}
		$file['servername'] = $fileName;
		if (substr($file['name'], -1) != '.') {
			$file['servername'] .= '.';
		}
		$file['servername'] .= $ext;

		//Move file to uploads dir
		if (move_uploaded_file($file['tmp_name'], parent::$app->config['uploads']['path'] . $file['servername'])) {
			unset($file['tmp_name']);

			//Insert to database
			$data = [
				'servername' => $file['servername'],
				'name' => $file['name'],
				'size' => $file['size'],
				'mime' => $file['type'],
				'md5hash' => $file['md5hash']
			];

			//Insert to database
			return parent::insertData(new self(), $data);
		}
		return false;
	}

	//Download file
	public static function download($file_id) {
		//Get file
		$file = self::getFile($file_id);
		if (!$file) {
			return false;
		}

		$filePath = parent::$app->config['uploads']['path'] . $file['FileUpload']->servername;
		if (file_exists($filePath)) {
		    header('Content-Description: File Transfer');
		    header('Content-Type: application/octet-stream');
		    header('Content-Disposition: attachment; filename="' . basename($file['FileUpload']->name) . '"');
		    header('Expires: 0');
		    header('Cache-Control: must-revalidate');
		    header('Pragma: public');
		    header('Content-Length: ' . filesize($filePath));
		    readfile($filePath);
		    exit;
		}

		return false;
	}
}