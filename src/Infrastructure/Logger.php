<?php
/**
 * Plugin Logger.
 *
 * @package WPCalibrate\TermGlossary
 */

declare( strict_types=1 );

namespace WPCalibrate\TermGlossary\Infrastructure;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Logger
 */
class Logger {

	/**
	 * Log a message if debug logging is enabled.
	 *
	 * @param string               $message Actionable log message.
	 * @param array<string, mixed> $context Optional context data.
	 * @return void
	 */
	public static function log( string $message, array $context = [] ): void {
		if ( ! Settings::get( 'enable_debug_log' ) ) {
			return;
		}

		$sanitized_context = self::sanitize_context( $context );
		$context_str       = ! empty( $sanitized_context ) ? ' ' . wp_json_encode( $sanitized_context ) : '';

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( sprintf( '[WPCalibrate Term Glossary] %s%s', $message, $context_str ) );
	}

	/**
	 * Sanitize context to scrub sensitive data.
	 *
	 * @param array<string, mixed> $context Context.
	 * @return array<string, mixed>
	 */
	private static function sanitize_context( array $context ): array {
		$sensitive_keys = [
			'password',
			'nonce',
			'_wpnonce',
			'cookie',
			'authorization',
			'token',
			'secret',
			'csv_raw',
			'file_content',
		];

		$clean = [];
		foreach ( $context as $key => $value ) {
			if ( in_array( strtolower( (string) $key ), $sensitive_keys, true ) ) {
				$clean[ $key ] = '***REDACTED***';
				continue;
			}

			if ( is_array( $value ) ) {
				$clean[ $key ] = self::sanitize_context( $value );
			} elseif ( is_scalar( $value ) || null === $value ) {
				$clean[ $key ] = $value;
			} else {
				$clean[ $key ] = gettype( $value );
			}
		}

		return $clean;
	}
}
