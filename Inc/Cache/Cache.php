<?php

namespace Inc\Cache;

/**
 * File based cache
 */
class Cache {

	/**
	 * Config default settings
	 *
	 * @var Array
	 * @access private
	 */
	private static $__configDefaults = [
		'duration' => '+2 minutes',
		'prefix' => '',
		'suffix' => ''
	];

	/**
	 * Configs for cache
	 *
	 * @var Array
	 * @access private
	 */
	private static $__configs = [
		'default' => [
			'duration' => '+2 minutes',
			'prefix' => 'default_',
			'suffix' => '.cache'
		]
	];

	/**
	 * Reads data from cache file.
	 *
	 * @param Object $app: Application context
	 * @param string $name: Cache file name to read
	 * @param string $config: Config name for cache to use
	 * @return mixed result: Result from cache or boolean false if there is no cache or has expired
	 */
	public static function read($app, $name, $config = 'default') {
		//Check if cache enabled
		if (!$app->config['cache']['enabled'] || !isset(self::$__configs[$config])) {
			return false;
		}

		//Set filepath
		$filepath = $app->config['cache']['path'] . self::$__configs[$config]['prefix'] . $name . self::$__configs[$config]['suffix'];

		/* Check if file exists */
		if (file_exists($filepath)) {
			//Read content
			list($timestamp, $content) = explode("\n", file_get_contents($filepath), 2);

			//Check if valid
			if ($timestamp >= time()) {
				return unserialize($content);
			}

			//Unlink file
			unlink($filepath);
		}

		/* No file */
		return false;
	}

	/**
	 * Writes data to cache file.
	 *
	 * @param Object $app: Application context
	 * @param string $name: Cache file name to write
	 * @param string $config: Config name for cache to use
	 * @return None
	 */
	public static function write($app, $name, $content, $config = 'default') {
		//Check if cache enabled
		if (!$app->config['cache']['enabled'] || !isset(self::$__configs[$config])) {
			return false;
		}

		//Set filepath
		$filepath = $app->config['cache']['path'] . self::$__configs[$config]['prefix'] . $name . self::$__configs[$config]['suffix'];

		/* Get timestamp */
		$timestamp = strtotime(self::$__configs[$config]['duration']);

		/* Format content */
		$content = $timestamp . "\n" . serialize($content);

		/* Write to file */
		file_put_contents($filepath, $content);

		//Written OK
		return true;
	}

	/**
	 * Sets config for future use with cache
	 *
	 * @param Object $app: Application context
	 * @param string $name: Cache config name
	 * @param string $config: Config for cache to use
	 * @return None
	 */
	public static function setConfig($app, $configName, $config) {
		//Merge configs
		$config = array_merge(self::$__configDefaults, $config);

		//Add to config
		self::$__configs[$configName] = $config;
	}
}
