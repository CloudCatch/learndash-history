<?php
/**
 * Certificate functions
 *
 * @package SeattleWebCo\LearnDashHistory
 */

namespace SeattleWebCo\LearnDashHistory\Functions;

function certificate_link( $history ) {
	$certificate_link = '';

	switch ( $history['activity_type'] ) {
		case 'course':
			$certificate_link = learndash_get_course_certificate_link( $history['course_id'], $history['user_id'] );
			break;

		case 'quiz':
			$certificate_details = $history['pass'] ? learndash_certificate_details( $history['post_id'], $history['user_id'] ) : '';
			$certificate_link    = $certificate_details['certificateLink'] ?? '';
			break;
	}

		$certificate_link = $certificate_link ? add_query_arg( array( 'hid' => $history['id'] ), $certificate_link ) : '';

	return $certificate_link;
}

function courseinfo( $value, $shortcode_atts ) {
	global $wpdb;

	if ( isset( $_REQUEST['hid'] ) ) {

		$history = $wpdb->get_row(
			$wpdb->prepare(
				"
			SELECT *
			FROM   {$wpdb->prefix}learndash_history
			WHERE  id = %d
			LIMIT  1
		",
				absint( $_REQUEST['hid'] )
			),
			ARRAY_A
		);

		if ( $history ) {
			switch ( $shortcode_atts['show'] ) {
				case 'completed_on':
				case 'timestamp':
					$value = learndash_adjust_date_time_display( $history['activity_completed'], $shortcode_atts['format'] );
					break;

				case 'enrolled_on':
					$value = learndash_adjust_date_time_display( $history['activity_started'], $shortcode_atts['format'] );
					break;
			}
		}
	}

	return $value;
}
\add_filter( 'learndash_courseinfo', __NAMESPACE__ . '\courseinfo', 10, 2 );
\add_filter( 'learndash_quizinfo', __NAMESPACE__ . '\courseinfo', 10, 2 );
