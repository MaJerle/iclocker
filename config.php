<?php

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
$config = [
    'apiversion' => [
        'Major' => '0',
        'Minor' => '1',
        'Patch' => '0'
    ],
    'site' => [
        'title' => 'IC Locker'
    ],
    'slim' => [
        'templates.path' => __DIR__ . '/Templates',
        'mode' => 'development',
        'debug' => true,
        'url_prefix' => '/',
        'template_prefix' => '/Templates/',
        'images_prefix' => '/images/',
        'production_server' => 'ic-locker.com', //domain name where production mode is active
        'session_cookie_secret' => sha1('ic-locker.com'),
        'index_prefix' => false,        //When true, links will have domain.com/index.php/.../...
    ],
    'database' => [
        'host' => 'localhost',
        'user' => 'root',
        'password' => '',
        'db' => ''
    ],
    'cookies' => [
        'expires' => '20 minutes',
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'httponly' => false,
        'name' => 'slim_session',
        'secret' => '3425908234kjh234tu890g',
        'cipher' => 'MCRYPT_RIJNDAEL_256',
        'cipher_mode' => 'MCRYPT_MODE_CBC'
    ],
    'mail' => [
        'Host' => 'kron.avant.si',
        'Port' => 465,
        'SMTPAuth' => true,
        'Username' => 'red@mesi.si',
        'Password' => 'MredMine666',
        'SMTPSecure' => 'ssl',
        'From' => [
            'support@ic-locker.com',
            'IC Locker'
        ]
    ],
    'twig' => [
        'debug' => true,
        'cache' => __DIR__ . '/cache'
    ],
    'cache' => [
        'enabled' => true,
        'path' => __DIR__ . '/cache/',
    ],
    'main_path' => __DIR__,
    'images' => [
        'path' => __DIR__ . '/images/'
    ],
    'templates' => [
        'path' => __DIR__ . '/Templates/'
    ],
    'uploads' => [
        'path' => __DIR__ . DS . 'uploads' . DS
    ],
    'languages' => [
        'file_extensions' => ['po', 'pot'],
        'path' => 'languages' . DIRECTORY_SEPARATOR,
        'list' => [
            'en_US' => [
                'title' => 'English',
                'desc' => 'United States of America'
            ],
            'sl_SI' => [
                'title' => 'Slovensko',
                'desc' => 'Slovenija'
            ]
        ]
    ],
    //Browsers on blacklist
    'browsers' => [
        ['Internet Explorer', '6.0'],
        ['Internet Explorer', '7.0'],
        ['Internet Explorer', '8.0'],
    ],
    'strings' => [
        //Days and months
        __('Monday'), __('Tuesday'), __('Wednesday'), __('Thursday'), __('Friday'), __('Saturday'), __('Sunday'), 
        __('January'), __('February'), __('March'), __('April'), __('May'), __('June'),
        __('July'), __('August'), __('September'), __('October'), __('November'), __('December')
    ]
];

//Include site config
$siteConfig = require 'config_site.php';

//Merge database
if (isset($siteConfig['database'])) {
    $config['database'] = array_merge_recursive($config['database'], $siteConfig['database']);
}

//Merge email
if (isset($siteConfig['database'])) {
    $config['database'] = array_merge_recursive($config['mail'], $siteConfig['database']);
}

//Check site title
if (isset($siteConfig['site_title'])) {
    $config['site']['title'] = $siteConfig['site_title'];
}

//Set production server
if (isset($config['production_server'])) {
    $config['slim']['production_server'] = $siteConfig['production_server'];
}

//Set debug
if (isset($config['debug'])) {
    $config['slim']['debug'] = $siteConfig['debug'];
}

//Set template prefix
if (isset($siteConfig['template_prefix'])) {
    $config['slim']['template_prefix'] = $siteConfig['template_prefix'];
}

//Set template prefix
if (isset($siteConfig['images_prefix'])) {
    $config['slim']['images_prefix'] = $siteConfig['images_prefix'];
}

//Set index prefix
if (isset($siteConfig['index_prefix'])) {
    $config['slim']['index_prefix'] = $siteConfig['index_prefix'];
}

return $config;
