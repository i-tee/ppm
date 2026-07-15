<?php

require_once '../tee/iplist.php';

#var_dump($_SERVER['REMOTE_ADDR']);
#var_dump($AllowIp);
#var_dump(array_search($_SERVER['REMOTE_ADDR'],$AllowIp));

if (array_search($_SERVER['REMOTE_ADDR'], $AllowIp) == FALSE) {
	header("Location: https://" . $_SERVER['SERVER_NAME']);
	die();
}

define('JOOMLA_MINIMUM_PHP', '5.3.10');

if (version_compare(PHP_VERSION, JOOMLA_MINIMUM_PHP, '<')) {
	die('Your host needs to use PHP ' . JOOMLA_MINIMUM_PHP . ' or higher to run this version of Joomla!');
}

// Saves the start time and memory usage.
$startTime = microtime(1);
$startMem  = memory_get_usage();

/**
 * Constant that is checked in included files to prevent direct access.
 * define() is used in the installation folder rather than "const" to not error for PHP 5.2 and lower
 */
define('_JEXEC', 1);

if (file_exists(__DIR__ . '/defines.php')) {
	include_once __DIR__ . '/defines.php';
}

if (!defined('_JDEFINES')) {
	define('JPATH_BASE', __DIR__);
	require_once JPATH_BASE . '/includes/defines.php';
}

require_once JPATH_BASE . '/includes/framework.php';
require_once JPATH_BASE . '/includes/helper.php';
require_once JPATH_BASE . '/includes/subtoolbar.php';

// Set profiler start time and memory usage and mark afterLoad in the profiler.
JDEBUG ? JProfiler::getInstance('Application')->setStart($startTime, $startMem)->mark('afterLoad') : null;

// Instantiate the application.
$app = JFactory::getApplication('administrator');

// Execute the application.
$app->execute();
