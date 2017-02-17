<?php

namespace Model;

use \Inc\Model;
require 'vendor' . DIRECTORY_SEPARATOR . 'phpmailer' . DIRECTORY_SEPARATOR . 'phpmailer' . DIRECTORY_SEPARATOR . 'PHPMailerAutoload.php';

/**
 * Email sender object
 *
 * @package default
 */
class EmailSender extends Model {

	//Sends email
	public static function sendEmail($addresses, $subject, $body, $attachments = []) {
		$mail = new \PHPMailer;

		//Set charset first
		$mail->CharSet = 'UTF-8';

		//Set SMTP
		$mail->isSMTP();
		$mail->Host = parent::$app->config['mail']['Host'];
		$mail->Port = parent::$app->config['mail']['Port'];
		$mail->SMTPAuth = parent::$app->config['mail']['SMTPAuth'];
		$mail->Username = parent::$app->config['mail']['Username'];
		$mail->Password = parent::$app->config['mail']['Password'];
		$mail->SMTPSecure = parent::$app->config['mail']['SMTPSecure'];

		//Set from address
		$mail->setFrom(parent::$app->config['mail']['From'][0], parent::$app->config['mail']['From'][1]);

		//Set addresses to send email to
		if (!is_array($addresses)) {
			$addresses = [$addresses];
		}
		foreach ($addresses as $k => $v) {
			if (!is_numeric($k)) {
				//Add email and name
				$mail->addAddress($k, $v);
			} else {
				//Only email
				$mail->addAddress($v);
			}
		}
		
		//Add attachments if any
		foreach ($attachments as $k => $v) {
			if (!is_numeric($k)) {
				//Add email and name
				$mail->addAttachment($k, $v);
			} else {
				//Only email
				$mail->addAttachment($v);
			}
		}

		//Set email info
		$mail->isHTML(true);

		//Set subject and body
		$mail->Subject = $subject;
		$mail->Body = $body;
		$mail->AltBody = '';

		if (!$mail->send()) {
		    return false;
		}
		return true;
	}
}
