<?php
/**
 * Completion triggers
 *
 * @package SeattleWebCo\LearnDashHistory
 */

namespace SeattleWebCo\LearnDashHistory\Functions;

use SeattleWebCo\LearnDashHistory\Log;

/**
 * Record activity to database
 *
 * @param array $data Activity to record.
 * @return int|bool The number of rows inserted, or false on error.
 */
function record( $data ) {
	global $wpdb;

	$data = \apply_filters(
		'learndash_history_record_data',
		\wp_parse_args(
			$data,
			array(
				'user_id'            => \get_current_user_id(),
				'post_id'            => 0,
				'course_id'          => 0,
				'activity_type'      => null,
				'activity_status'    => null,
				'activity_started'   => null,
				'activity_completed' => null,
				'score'              => null,
				'count'              => null,
				'pass'               => null,
				'points'             => null,
				'percentage'         => null,
			)
		)
	);

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$record = $wpdb->insert( $wpdb->prefix . 'learndash_history', $data );

	if ( false === $record ) {
		Log::write( \esc_html__( 'Unable to record LearnDash history', 'learndash-history' ), 'error', $data );
	}

	return $record;
}

/**
 * Record course completed
 *
 * @param array $course_data Course data to record.
 * @return void
 */
function course_completed( $course_data ) {
	$data = array(
		'user_id'             => $course_data['user']->ID,
		'post_id'             => $course_data['course']->ID,
		'course_id'           => $course_data['course']->ID,
		'activity_type'       => 'course',
		'activity_status'     => 1,
		'activity_started'    => \ld_course_access_from( $course_data['course']->ID, $course_data['user']->ID ),
		'activity_completed'  => $course_data['course_completed'],
	);

	record( $data );
}
\add_action( 'learndash_course_completed', __NAMESPACE__ . '\course_completed' );


/**
 * Record quiz completed
 *
 * @param array   $quiz_data Quiz data to record.
 * @param WP_User $user User that completed quiz.
 * @return void
 */
function quiz_completed( $quiz_data, $user ) {
	Log::write( \esc_html__( 'quiz data', 'learndash-history' ), 'error', $quiz_data );

	$data = array(
		'user_id'             => $user->ID,
		'post_id'             => is_object( $quiz_data['quiz'] ) ? $quiz_data['quiz']->ID : $quiz_data['quiz'],
		'course_id'           => is_object( $quiz_data['course'] ) ? $quiz_data['course']->ID : $quiz_data['course'],
		'activity_type'       => 'quiz',
		'activity_status'     => \absint( $quiz_data['pass'] ),
		'activity_started'    => $quiz_data['started'],
		'activity_completed'  => $quiz_data['completed'],
		'score'               => $quiz_data['score'],
		'count'               => $quiz_data['count'],
		'pass'                => $quiz_data['pass'],
		'points'              => $quiz_data['points'],
		'percentage'          => $quiz_data['percentage'],
	);

	record( $data );
}
\add_action( 'learndash_quiz_completed', __NAMESPACE__ . '\quiz_completed', 10, 2 );
