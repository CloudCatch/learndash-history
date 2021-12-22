<?php
/**
 * REST API functions
 *
 * @package SeattleWebCo\LearnDashHistory
 */

namespace SeattleWebCo\LearnDashHistory\Functions;

use WP_Error;

/**
 * Register REST endpoints
 *
 * @return void
 */
function rest_api_init() {
	\register_rest_route(
		'learndashhistory/v1',
		'/migrate',
		array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => __NAMESPACE__ . '\migrate_history',
			'permission_callback' => __NAMESPACE__ . '\migrate_history_permissions_check',
		)
	);

	\register_rest_route(
		'learndashhistory/v1',
		'/migrate/count',
		array(
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => __NAMESPACE__ . '\migrate_history_count',
			'permission_callback' => __NAMESPACE__ . '\migrate_history_permissions_check',
		)
	);
}
\add_action( 'rest_api_init', __NAMESPACE__ . '\rest_api_init' );

/**
 * Check if the current user can perform the migration
 *
 * @param WP_REST_Request $request Full data about the request.
 * @return bool
 */
function migrate_history( $request ) {
	global $wpdb;

	$params = $request->get_json_params();

	if ( ! isset( $params['offset'] ) ) {
		return new \WP_Error( 'invalid-request', esc_html__( 'Missing parameter `offset`', 'learndash-history' ), array( 'status' => 400 ) );
	}

	if ( ! isset( $params['limit'] ) ) {
		return new \WP_Error( 'invalid-request', esc_html__( 'Missing parameter `limit`', 'learndash-history' ), array( 'status' => 400 ) );
	}

	if ( ! isset( $params['start'] ) ) {
		return new \WP_Error( 'invalid-request', esc_html__( 'Missing parameter `start`', 'learndash-history' ), array( 'status' => 400 ) );
	}

	$migrated_rows = $wpdb->query(
		$wpdb->prepare(
			"
        INSERT INTO {$wpdb->prefix}learndash_history
            (
                user_id,
                post_id,
                course_id,
                activity_type,
                activity_status,
                activity_started,
                activity_completed,
                score,
                count,
                pass,
                points,
                percentage,
				statistic_ref_id,
                migrated
            )
        SELECT 
            a.user_id,
            a.post_id,
            a.course_id,
            a.activity_type,
            a.activity_status,
            a.activity_started,
            a.activity_completed,
            am1.activity_meta_value AS score,
            am2.activity_meta_value AS count,
            am3.activity_meta_value AS pass,
            am4.activity_meta_value AS points,
            am5.activity_meta_value AS percentage,
			am6.activity_meta_value AS statistic_ref_id,
            1
        FROM   {$wpdb->prefix}learndash_user_activity a
        LEFT JOIN {$wpdb->prefix}learndash_user_activity_meta am1
            ON a.activity_id = am1.activity_id
                AND am1.activity_meta_key = 'score'
        LEFT JOIN {$wpdb->prefix}learndash_user_activity_meta am2
            ON a.activity_id = am2.activity_id
                AND am2.activity_meta_key = 'count'
        LEFT JOIN {$wpdb->prefix}learndash_user_activity_meta am3
            ON a.activity_id = am3.activity_id
                AND am3.activity_meta_key = 'pass'
        LEFT JOIN {$wpdb->prefix}learndash_user_activity_meta am4
            ON a.activity_id = am4.activity_id
                AND am4.activity_meta_key = 'points'
        LEFT JOIN {$wpdb->prefix}learndash_user_activity_meta am5
            ON a.activity_id = am5.activity_id
                AND am5.activity_meta_key = 'percentage'
		LEFT JOIN {$wpdb->prefix}learndash_user_activity_meta am6
            ON a.activity_id = am6.activity_id
                AND am6.activity_meta_key = 'statistic_ref_id'
        WHERE a.activity_type IN( 'course', 'quiz', 'lesson', 'topic' ) 
            AND a.activity_id >= %d
        ORDER BY a.activity_id ASC
        LIMIT %d
    ",
			\absint( $params['start'] ),
			\absint( $params['limit'] )
		)
	);

	$last_activity_id = $wpdb->get_var(
		$wpdb->prepare(
			"
                SELECT activity_id
                FROM {$wpdb->prefix}learndash_user_activity
                WHERE activity_type IN( 'course', 'quiz', 'lesson', 'topic' ) 
                    AND activity_id >= %d
                ORDER BY activity_id ASC
                LIMIT %d, 1
            ",
			\absint( $params['start'] ),
			\absint( $params['limit'] )
		)
	);

	if ( ! $last_activity_id ) {
		$migrated_rows = 0;
	}

	if ( false === $migrated_rows ) {
		return new \WP_Error( 'migration-error', \esc_html__( 'An error occurred during migration. Lower the number of rows migrated per run and try again.', 'learndash-history' ), array( 'status' => 500 ) );
	} elseif ( 0 === $migrated_rows ) {
		// Migration complete.
		\update_option( 'learndash_migrated_rows', time() );

		return \rest_ensure_response(
			array(
				'migrated' => $migrated_rows,
				'start'    => \absint( $last_activity_id ),
				'complete' => true,
			)
		);
	}

	return \rest_ensure_response(
		array(
			'migrated' => $migrated_rows,
			'start'    => \absint( $last_activity_id ),
		)
	);
}

/**
 * Count number of activities to migrate
 *
 * @param WP_REST_Request $request Full data about the request.
 * @return bool
 */
function migrate_history_count( $request ) {
	global $wpdb;

	$count_rows = $wpdb->get_var(
		"
            SELECT 
                COUNT(a.activity_id)
            FROM   {$wpdb->prefix}learndash_user_activity a
            WHERE a.activity_type IN( 'course', 'quiz', 'lesson', 'topic' ) 
        "
	);

	return \rest_ensure_response( \absint( $count_rows ) );
}

/**
 * Check if the current user can perform the migration
 *
 * @param WP_REST_Request $request Full data about the request.
 * @return bool|WP_Error
 */
function migrate_history_permissions_check( $request ) {
	$capability = defined( 'LEARNDASH_ADMIN_CAPABILITY_CHECK' ) ? constant( 'LEARNDASH_ADMIN_CAPABILITY_CHECK' ) : 'manage_options';

	return true;

	return current_user_can( $capability );
}
