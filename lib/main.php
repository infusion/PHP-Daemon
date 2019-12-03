<?php

/**
 * @license PHP Daemon
 * http://www.xarg.org/2016/07/how-to-write-a-php-daemon/
 *
 * Copyright (c) 2016, Robert Eisele (robert@xarg.org)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 **/
final class Main {

	// Flag if daemon is still running
	private static $run = true;

	// The screen terminal connected
	public static $screen = null;

	/**
	The actual loop which handles the process execution and sleep cycles
	*/
	public static function loop() {

		// Initialize extern ressources
		$db = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		
		do {
			echos("BEGINNING\n", "green");

			$load = Worker::run($db);

			if (DAEMON_FORK) {

				$sleep = MAX_SLEEP + $load * (MIN_SLEEP - MAX_SLEEP);
				
				cli_set_process_title(DAEMON_NAME . ': ' . round(100 * $load, 1) . '%');
				echos("Sleep for "); echos($sleep, "magenta"); echos(" seconds\n\n");
				sleep($sleep);
				
			} else if (0 == $load) {
				break;
			}
			
		} while (self::$run);

		// Close extern ressources
		mysqli_close($db);
	}

	/**
	Registering the environment
	*/
	public static function registerEnv() {
		file_put_contents(DAEMON_PID, getmypid());

		posix_setuid(DAEMON_UID);
		posix_setgid(DAEMON_GID);
		
		self::_openConsole(posix_ttyname(STDOUT));

		fclose(STDIN);
		fclose(STDOUT);
		fclose(STDERR);
	}

	/**
	Opens the console
	*/
	private static function _openConsole($screen) {

		if (!empty($screen) && false !== ($fd = fopen($screen, "c"))) {
			self::$screen = $fd;
		}
	}

	/**
	The signal handler function
	*/
	public static function _handleSignal($signo) {

		switch ($signo) {
			/*
			 * Attention: The sigterm is only recognized outside a mysqlnd poll()
			 */
			case SIGTERM:
				self::log(E_NOTICE, 'Received SIGTERM, dying...');
				self::$run = false;
				return;
			case SIGHUP:
				self::log(E_NOTICE, 'Received SIGHUP, rotate...');
				Worker::rotate();
				return;
			case SIGUSR1:

				if (null !== self::$screen) {
					@fclose(self::$screen);
				}

				self::$screen = null;

				if (preg_match('|pts/([0-9]+)|', `who`, $out) && !empty($out[1])) {
					self::_openConsole('/dev/pts/' . $out[1]);
				}
		}
	}

	/**
	Sets up the signal handlers
	*/
	public static function registerSignal() {

		pcntl_signal(SIGTERM, 'Main::_handleSignal');
		pcntl_signal(SIGHUP,  'Main::_handleSignal');
		pcntl_signal(SIGUSR1, 'Main::_handleSignal');
	}

	/**
	The error handler for PHP
	*/
	public static function handleError($errno, $errstr, $errfile, $errline, $errctx) {

		if (error_reporting() == 0) {
			return;
		}

		Main::log($errno, $errstr . " on line " . $errline . "(" . $errfile . ") -> " . var_export($errctx, true));

		/* Don't execute PHP's internal error handler */
		return true;
	}

	/**
	The system log function
	*/
	public static function log($code, $msg, $var = null) {

		static $codeMap = array(
			E_ERROR   => "Error",
			E_WARNING => "Warning",
			E_NOTICE  => "Notice"
		);

		$msg = date('[d-M-Y H:i:s] ') . $codeMap[$code] . ': ' . $msg;

		if (null !== $var) {

			$msg.= "\n";
			$msg.= var_export($var, true);
			$msg.= "\n";
			$msg.="\n";
		}
		file_put_contents(DAEMON_LOG, $msg . "\n", FILE_APPEND);
	}
}

