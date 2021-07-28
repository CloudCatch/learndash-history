<?php
/**
 * Plugin Name:     LearnDash LMS - History
 * Description:     Store all users LearnDash course, quiz, and certificate history
 * Version:         0.0.0-development
 * Author:          Seattle Web Co.
 * Author URI:      https://seattlewebco.com
 * Text Domain:     learndash-history
 * Domain Path:     /languages/
 * Contributors:    seattlewebco, dkjensen
 * Requires PHP:    7.0.0
 *
 * @package SeattleWebCo\LearnDashHistory
 */

namespace SeattleWebCo\LearnDashHistory;

/**
 * Setup plugin
 *
 * @return void
 */
function initialize() {
	if ( defined( '\\LEARNDASH_VERSION' ) ) {
		/**
		 * Load functions and dependencies
		 */
		require_once plugin_dir_path( __FILE__ ) . '/vendor/autoload.php';
		require_once plugin_dir_path( __FILE__ ) . '/lib/functions/triggers.php';
		require_once plugin_dir_path( __FILE__ ) . '/lib/functions/views.php';
		require_once plugin_dir_path( __FILE__ ) . '/lib/functions/certificates.php';

		/**
		 * Init localization files
		 */
		load_plugin_textdomain( 'learndash-history', false, plugin_dir_path( __FILE__ ) . '/languages' );
	}
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\initialize' );

/**
 * Install table
 *
 * @return void
 */
function install() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	$table = "CREATE TABLE `{$wpdb->prefix}learndash_history`
    (
        `id`                 BIGINT(20) UNSIGNED NOT NULL auto_increment,
        `user_id`            BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        `post_id`            BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        `course_id`          BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        `activity_type`      VARCHAR(50) NULL DEFAULT NULL,
        `activity_status`    TINYINT(1) UNSIGNED NULL DEFAULT NULL,
        `activity_started`   INT(11) UNSIGNED NULL DEFAULT NULL,
        `activity_completed` INT(11) UNSIGNED NULL DEFAULT NULL,
        `score`              INT(11) UNSIGNED NULL DEFAULT NULL,
        `count`              INT(11) UNSIGNED NULL DEFAULT NULL,
        `pass`               INT(11) UNSIGNED NULL DEFAULT NULL,
        `points`             INT(11) UNSIGNED NULL DEFAULT NULL,
        `percentage`         INT(11) UNSIGNED NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        INDEX `user_id` (`user_id`),
        INDEX `post_id` (`post_id`),
        INDEX `course_id` (`course_id`),
        INDEX `activity_status` (`activity_status`),
        INDEX `activity_type` (`activity_type`),
        INDEX `activity_started` (`activity_started`),
        INDEX `activity_completed` (`activity_completed`),
        INDEX `score` (`score`),
        INDEX `count` (`count`),
        INDEX `pass` (`pass`),
        INDEX `points` (`points`),
        INDEX `percentage` (`percentage`)
    ) {$charset_collate}";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $table );
}
\register_activation_hook( __FILE__, __NAMESPACE__ . '\install' );
