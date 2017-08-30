<?php

/****************************************************************************/
/* Rename config_site_template.php to config_site.php and update your setup */
/****************************************************************************/

return [
    /**
     * Database setup
     */
	'database' => [
        'host' => 'localhost',
        'user' => 'root',
        'password' => '',
        'db' => 'components'
	],
    /**
     * Email setup for PHPMailer library
     */
	'mail' => [
		'Host' => '',                   //Host used for sending emails
        'Port' => 465,                 	//Port for emails
        'SMTPAuth' => true,          	//Authenticate SMTP
        'Username' => 'user@host',     	//SMTP username
        'Password' => 'pass',        	//SMTP password
        'SMTPSecure' => 'ssl',          //Use secure SSL
        'From' => [                     //Set "From" parameter
            'support@ic-locker.com', 'IC Locker'
        ]
	],
	'debug' => true,                    //Enable debug mode
	'production_server' => 'ic-locker.com',    //Domain where production is active
	'site_title' => 'IC Locker',        //Site title
    'index_prefix' => false             //Should all links be prefixed with index.php, "domain.com/index.php/controller/action"
];
