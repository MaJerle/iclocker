<?php

namespace Controller;

use \Model\FileUpload;

class AssetsController extends Base {

	/**
	 * Route /assets/upload_file
	 *
	 * @param $app: Application context
	 */
	public function upload_file($app) {
		if (!$app->request()->isPost()) {
			$app->redirect($app->urlFor('index'));
		}

		//output
		$out = [];

		//Upload file by file
		$files = $_FILES;
		foreach ($files as $file) {
			if (($id = FileUpload::upload($file)) != false) {
				$out[] = $id;
			}
		}
		if (empty($out)) {
			return $app->toJSON([], true, 400);
		}
		if (count($out) == 1) {
			//$out = $out[0];
		}
		print json_encode(FileUpload::getFiles($out));
		exit;
	}

	/**
	 * Route /assets/download_file
	 *
	 * @param $app: Application context
	 */
	public function download_file($app, $file = null) {
		//Download file!
		FileUpload::download($file);
	}
}