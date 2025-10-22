<?php
/**
 * Plugin Name: LearnDash LMS - FluentCart Integration
 * Plugin URI: https://www.learndash.com
 * Description: Integrates LearnDash LMS with FluentCart to allow automatic course and group enrollment upon product purchase.
 * Version: 1.0.0
 * Author: Wbcom Designs
 * Author URI: https://wbcomdesigns.com
 * Text Domain: learndash-fluentcart
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 6.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LEARNDASH_FLUENTCART_VERSION', '1.0.0' );
define( 'LEARNDASH_FLUENTCART_FILE', __FILE__ );
define( 'LEARNDASH_FLUENTCART_PATH', plugin_dir_path( __FILE__ ) );
define( 'LEARNDASH_FLUENTCART_URL', plugin_dir_url( __FILE__ ) );

/**
 * Check if dependencies are met
 *
 * @return bool
 */
function learndash_fluentcart_check_dependencies() {
	$missing_dependencies = array();

	// Check for LearnDash
	if ( ! defined( 'LEARNDASH_VERSION' ) || ! class_exists( 'SFWD_LMS' ) ) {
		$missing_dependencies[] = '<a href="https://learndash.com">LearnDash LMS</a> (version 4.7.0 or higher)';
	} elseif ( version_compare( LEARNDASH_VERSION, '4.7.0', '<' ) ) {
		$missing_dependencies[] = '<a href="https://learndash.com">LearnDash LMS</a> (version 4.7.0 or higher) - current version: ' . LEARNDASH_VERSION;
	}

	// Check for FluentCart
	if ( ! defined( 'FLUENT_CART_VERSION' ) && ! class_exists( 'FluentCart\Framework\Foundation\Application' ) ) {
		$missing_dependencies[] = '<a href="https://fluentcart.com">FluentCart</a>';
	}

	if ( ! empty( $missing_dependencies ) ) {
		add_action(
			'admin_notices',
			function () use ( $missing_dependencies ) {
				?>
				<div class="notice notice-error">
					<p>
						<strong><?php esc_html_e( 'LearnDash FluentCart Integration:', 'learndash-fluentcart' ); ?></strong>
						<?php esc_html_e( 'The following plugin(s) must be installed and activated:', 'learndash-fluentcart' ); ?>
					</p>
					<ul style="list-style: disc; padding-left: 20px;">
						<?php foreach ( $missing_dependencies as $dependency ) : ?>
							<li><?php echo wp_kses_post( $dependency ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php
			}
		);
		return false;
	}

	return true;
}

/**
 * Initialize the plugin
 */
function learndash_fluentcart_init() {
	if ( ! learndash_fluentcart_check_dependencies() ) {
		return;
	}

	// Load the main integration class
	require_once LEARNDASH_FLUENTCART_PATH . 'includes/class-learndash-fluentcart.php';

	// Initialize using singleton
	LearnDash_FluentCart_Integration::get_instance();
}

add_action( 'plugins_loaded', 'learndash_fluentcart_init', 20 );

/**
 * Load plugin textdomain for translations
 */
function learndash_fluentcart_load_textdomain() {
	load_plugin_textdomain( 'learndash-fluentcart', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

add_action( 'init', 'learndash_fluentcart_load_textdomain' );
