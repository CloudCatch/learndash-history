<?php
/**
 * Log class
 *
 * @package SeattleWebCo\LearnDashHistory
 */

namespace SeattleWebCo\LearnDashHistory;

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

/**
 * Logging class file
 */
class Log {

	/**
	 * Log message
	 *
	 * @param string $message Message to log.
	 * @param string $type warning|error|notice.
	 * @param array  $data Additional context data.
	 * @return void
	 */
	public static function write( $message, $type = 'notice', $data = array() ) {
		$upload_dir = wp_upload_dir( null, false );

		$log = new Logger( 'learndash_history' );
		$log->pushHandler( new RotatingFileHandler( $upload_dir['basedir'] . '/learndash-history-logs/learndash-history.log', 14, Logger::DEBUG ) );

		$log->{$type}( $message, $data );
	}

}
