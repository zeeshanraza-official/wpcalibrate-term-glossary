<?php

/**
 * Plugin Name:       WPCalibrate Term Glossary
 * Plugin URI:        https://wpcalibrate.com/
 * Description:       Create, manage, display, and automatically link glossary terms and definitions across your WordPress website.
 * Version:           1.0.0
 * Requires at least: 6.8
 * Requires PHP:      8.2
 * Author:            WPCalibrate
 * Author URI:        https://wpcalibrate.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpcalibrate-term-glossary
 * Domain Path:       /languages
 *
 * @package WPCalibrate\TermGlossary
 */

declare(strict_types=1);

namespace WPCalibrate\TermGlossary;

if (! defined('ABSPATH')) {
	exit;
}

// Plugin constants.
define('WPCTG_VERSION', '1.0.0');
define('WPCTG_MIN_PHP', '8.2');
define('WPCTG_MIN_WP', '6.8');
define('WPCTG_FILE', __FILE__);
define('WPCTG_DIR', plugin_dir_path(__FILE__));
define('WPCTG_URL', plugin_dir_url(__FILE__));
define('WPCTG_BASENAME', plugin_basename(__FILE__));

/**
 * Check minimum PHP version.
 */
if (version_compare(PHP_VERSION, WPCTG_MIN_PHP, '<')) {
	add_action(
		'admin_notices',
		static function (): void {
?>
		<div class="notice notice-error">
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: Plugin name, 2: Required PHP version, 3: Current PHP version */
						__('%1$s requires PHP version %2$s or higher. Your site is running PHP %3$s. Please upgrade your PHP version.', 'wpcalibrate-term-glossary'),
						'WPCalibrate Term Glossary',
						WPCTG_MIN_PHP,
						PHP_VERSION
					)
				);
				?>
			</p>
		</div>
<?php
		}
	);
	return;
}

/**
 * PSR-4 Autoloader for WPCalibrate\TermGlossary namespace.
 */
spl_autoload_register(
	static function (string $class_name): void {
		$prefix = 'WPCalibrate\\TermGlossary\\';
		if (! str_starts_with($class_name, $prefix)) {
			return;
		}

		$relative_class = substr($class_name, strlen($prefix));
		$file           = WPCTG_DIR . 'src/' . str_replace('\\', '/', $relative_class) . '.php';

		if (file_exists($file)) {
			require_once $file;
		}
	}
);

// Register activation and deactivation hooks.
register_activation_hook(WPCTG_FILE, [Activation::class, 'activate']);
register_deactivation_hook(WPCTG_FILE, [Deactivation::class, 'deactivate']);

/**
 * Bootstrap the plugin on plugins_loaded.
 */
add_action(
	'plugins_loaded',
	static function (): void {
		Plugin::instance()->boot();
	}
);
