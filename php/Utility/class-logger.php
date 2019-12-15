<?php
/**
 * Multisite Search Logger.
 *
 * @package   MultisiteSearch
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 */

namespace MultisiteSearch\Utility;

/**
 * Logger.
 */
class Logger {

	/**
	 * Are we using WP_CLI?
	 *
	 * @return bool
	 */
	public static function using_cli() {
		return defined( 'WP_CLI' ) && WP_CLI;
	}

	/**
	 * Log message.
	 *
	 * @param string $message Message to output.
	 */
	private static function debug_log( $message ) {
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		}
	}

	/**
	 * Log message.
	 *
	 * @param string $message Message to output.
	 */
	public static function log( $message ) {
		if ( self::using_cli() ) {
			\WP_CLI::log( $message );
		} else {
			static::debug_log( $message );
		}
	}

	/**
	 * Log success message.
	 *
	 * @param string $message Message to output.
	 */
	public static function success( $message ) {
		if ( self::using_cli() ) {
			\WP_CLI::success( $message );
		} else {
			static::debug_log( 'SUCCESS: ' . $message );
		}
	}

	/**
	 * Log error message.
	 *
	 * @param string $message Message to output.
	 */
	public static function error( $message ) {
		if ( self::using_cli() ) {
			\WP_CLI::error( $message );
		} else {
			static::debug_log( 'ERROR: ' . $message );
		}
	}

}
