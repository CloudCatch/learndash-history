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
				'activity_status'    => 0,
				'activity_started'   => null,
				'activity_completed' => null,
				'score'              => null,
				'count'              => null,
				'pass'               => null,
				'points'             => null,
				'percentage'         => null,
				'statistic_ref_id'   => null,
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
 * Record resource access
 *
 * @param array $args User activity arguments.
 * @return void
 */
function update_activity( $args ) {
	if ( 'access' === $args['activity_type'] ) {
		return;
	}

	$data = array(
		'user_id'             => $args['user_id'],
		'post_id'             => $args['post_id'],
		'course_id'           => $args['course_id'],
		'activity_type'       => $args['activity_type'],
		'activity_status'     => 0,
		'activity_started'    => time(),
	);

	record( $data );
}
// \add_action( 'learndash_update_user_activity', __NAMESPACE__ . '\update_activity' );

/**
 * Expire recorded course history
 *
 * @param int $user_id User ID.
 * @param int $course_id Course ID.
 * @return void
 */
function course_access_expired( $user_id, $course_id ) {
	$data = array(
		'user_id'             => $user_id,
		'post_id'             => $course_id,
		'course_id'           => $course_id,
		'activity_type'       => 'expire',
		'activity_status'     => 0,
		'activity_started'    => time(),
	);

	record( $data );
}
\add_action( 'learndash_user_course_access_expired', __NAMESPACE__ . '\course_access_expired', 10, 2 );

/**
 * Record course access
 *
 * @param int     $user_id User ID.
 * @param int     $course_id Course ID.
 * @param string  $course_access_list A comma-separated list of user IDs used for the course_access_list field.
 * @param boolean $remove Whether to remove course access from the user.
 * @return void
 */
function course_access( $user_id, $course_id, $course_access_list, $remove ) {
	global $wpdb;

	if ( $remove ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->update(
			$wpdb->prefix . 'learndash_history',
			array( 'state' => 2 ),
			array(
				'user_id'   => $user_id,
				'course_id' => $course_id,
			)
		);

		return;
	}

	$data = array(
		'user_id'             => $user_id,
		'post_id'             => $course_id,
		'course_id'           => $course_id,
		'activity_type'       => 'access',
		'activity_started'    => time(),
	);

	record( $data );
}
\add_action( 'learndash_update_course_access', __NAMESPACE__ . '\course_access', 10, 4 );

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
		'activity_completed'  => $course_data['course_completed'] ?? time(),
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
		'statistic_ref_id'    => $quiz_data['statistic_ref_id'],
	);

	record( $data );
}
\add_action( 'learndash_quiz_completed', __NAMESPACE__ . '\quiz_completed', 10, 2 );

/**
 * Record lesson completed
 *
 * @param array $lesson_data Lesson data to record.
 * @return void
 */
function lesson_completed( $lesson_data ) {
	$data = array(
		'user_id'             => is_object( $lesson_data['user'] ) ? $lesson_data['user']->ID : $lesson_data['user'],
		'post_id'             => is_object( $lesson_data['lesson'] ) ? $lesson_data['lesson']->ID : $lesson_data['lesson'],
		'course_id'           => is_object( $lesson_data['course'] ) ? $lesson_data['course']->ID : $lesson_data['course'],
		'activity_type'       => 'lesson',
		'activity_status'     => 1,
		'activity_completed'  => time(),
	);

	record( $data );
}
\add_action( 'learndash_lesson_completed', __NAMESPACE__ . '\lesson_completed' );

/**
 * Record topic completed
 *
 * @param array $topic_data Topic data to record.
 * @return void
 */
function topic_completed( $topic_data ) {
	$data = array(
		'user_id'             => is_object( $topic_data['user'] ) ? $topic_data['user']->ID : $topic_data['user'],
		'post_id'             => is_object( $topic_data['topic'] ) ? $topic_data['topic']->ID : $topic_data['topic'],
		'course_id'           => is_object( $topic_data['course'] ) ? $topic_data['course']->ID : $topic_data['course'],
		'activity_type'       => 'topic',
		'activity_status'     => 1,
		'activity_completed'  => time(),
	);

	record( $data );
}
\add_action( 'learndash_topic_completed', __NAMESPACE__ . '\topic_completed' );
