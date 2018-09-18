<?php

/**
 * @license PHP Daemon
 * http://www.xarg.org/2016/07/how-to-write-a-php-daemon/
 *
 * Copyright (c) 2016, Robert Eisele (robert@xarg.org)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 **/

define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_HOST', 'localhost');
define('DB_NAME', 'test');

define('DAEMON_NAME', 'daemon');
define('DAEMON_ROOT', '.');

define('DAEMON_UID', 1500);
define('DAEMON_GID', 1500);

define('DAEMON_PID', '/var/run/' . DAEMON_NAME . '.pid');
define('DAEMON_LOG', ini_get('error_log') ? ini_get('error_log') : "/var/log/" . DAEMON_NAME . '.log');

define('DAEMON_FORK',  empty($argv[1]) || 'cli' != $argv[1]);

define('MAX_RESULT', 100);
define('MIN_SLEEP', 0);
define('MAX_SLEEP', 45);

function __autoload($class) {

	require DAEMON_ROOT . '/lib/' . strtolower($class) . '.php';
}

function echos($text, $color="normal") {

	static $colors = array(
		'light_red' => "[1;31m",
		'light_green' => "[1;32m",
		'yellow' => "[1;33m",
		'light_blue' => "[1;34m",
		'magenta' => "[1;35m",
		'light_cyan' => "[1;36m",
		'white' => "[1;37m",
		'normal' => "[0m",
		'black' => "[0;30m",
		'red' => "[0;31m",
		'green' => "[0;32m",
		'brown' => "[0;33m",
		'blue' => "[0;34m",
		'cyan' => "[0;36m",
		'bold' => "[1m",
		'underscore' => "[4m",
		'reverse' => "[7m"
	);

	$str = chr(27) . $colors[$color] . $text . chr(27) . "[0m";

	if (false === DAEMON_FORK) {
		echo $str;
		return;
	}

	if (Main::$screen === null) {
		return;
	}

	if (false === @fwrite(Main::$screen, $str)) {
		Main::$screen = null;
	}
}

