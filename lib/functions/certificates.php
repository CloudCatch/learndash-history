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
			$certificate_id = learndash_get_setting( $history['course_id'], 'certificate' );

			if ( empty( $certificate_id ) ) {
				break;
			}

			if ( ( learndash_get_post_type_slug( 'certificate' ) !== get_post_type( $certificate_id ) ) ) {
				break;
			}

			if ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) {
				$view_user_id = get_current_user_id();
			} else {
				$view_user_id = $history['user_id'];
			}

			$cert_query_args = array(
				'course_id' => $history['course_id'],
			);

			if ( ( $history['user_id'] != $view_user_id ) && ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) ) {
				$cert_query_args['user'] = $history['user_id'];
			}

			$cert_query_args['cert-nonce'] = wp_create_nonce( $history['course_id'] . $history['user_id'] . $view_user_id );

			$certificate_link = apply_filters( 'learndash_course_certificate_link', add_query_arg( $cert_query_args, get_permalink( $certificate_id ) ), $history['course_id'], $history['user_id'] );

			break;

		case 'quiz':
			$certificate_details = $history['pass'] ? learndash_certificate_details( $history['post_id'], $history['user_id'] ) : '';
			$certificate_link    = $certificate_details['certificateLink'] ?? '';
			break;
	}

	$certificate_link = $certificate_link ? add_query_arg( array( 'hid' => $history['id'] ), $certificate_link ) : '';

	return $certificate_link;
}

function certificate_display() {
	global $wpdb;

	if ( ! function_exists( 'learndash_get_post_type_slug' ) ) {
		return;
	}

	if ( ! isset( $_GET['hid'] ) ) {
		return;
	}

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

	if ( ! $history ) {
		return;
	}

	if ( is_singular( learndash_get_post_type_slug( 'certificate' ) ) ) {
		if ( ( isset( $_GET['cert-nonce'] ) ) && ( ! empty( $_GET['cert-nonce'] ) ) ) {
			$certificate_post = get_post( get_the_ID() );

			// The viewing user ID.
			$view_user_id = get_current_user_id();

			/**
			 * Then determined for whom the certificate if for. A
			 * Group Leader or admin user can view other users.
			 */
			if ( ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) && ( ( isset( $_GET['user'] ) ) && ( ! empty( $_GET['user'] ) ) ) ) {
				$cert_user_id = absint( $_GET['user'] );
			} else {
				$cert_user_id = get_current_user_id();
			}

			if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
				$course_id = absint( $_GET['course_id'] );

				if ( wp_verify_nonce( esc_attr( $_GET['cert-nonce'] ), $course_id . $cert_user_id . $view_user_id ) ) {
					$course_post = get_post( $course_id );
					if ( ( $course_post ) && ( is_a( $course_post, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'course' ) === $course_post->post_type ) ) {
						$course_certificate_post_id = learndash_get_setting( $course_post->ID, 'certificate' );
						if ( absint( $course_certificate_post_id ) === absint( $certificate_post->ID ) ) {
							if ( ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) && ( intval( $cert_user_id ) !== intval( $view_user_id ) ) ) {
								wp_set_current_user( $cert_user_id );
							}

							/** This filter is documented in includes/class-ld-cpt-instance.php */
							if ( has_action( 'learndash_tcpdf_init' ) ) {
								do_action(
									'learndash_tcpdf_init',
									array(
										'cert_id' => $certificate_post->ID,
										'user_id' => $cert_user_id,
										'post_id' => $course_id,
									)
								);
							} else {
								require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/ld-convert-post-pdf.php';
								learndash_certificate_post_shortcode(
									array(
										'cert_id' => $certificate_post->ID,
										'user_id' => $cert_user_id,
										'post_id' => $course_id,
									)
								);
							}
							die();
						}
					}
				}
			} elseif ( ( isset( $_GET['quiz'] ) ) && ( ! empty( $_GET['quiz'] ) ) ) {
				$quiz_id = intval( $_GET['quiz'] );
				if ( wp_verify_nonce( $_GET['cert-nonce'], $quiz_id . $cert_user_id . $view_user_id ) ) {

					$quiz_post = get_post( $quiz_id );
					if ( ( $quiz_post ) && ( is_a( $quiz_post, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'quiz' ) === $quiz_post->post_type ) ) {
						$quiz_certificate_post_id = learndash_get_setting( $quiz_post->ID, 'certificate' );
						if ( absint( $quiz_certificate_post_id ) === absint( $certificate_post->ID ) ) {
							if ( $history['pass'] ) {
								if ( ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) && ( $cert_user_id !== $view_user_id ) ) {
									wp_set_current_user( $cert_user_id );
								}

								if ( has_action( 'learndash_tcpdf_init' ) ) {
									/** This filter is documented in includes/class-ld-cpt-instance.php */
									do_action(
										'learndash_tcpdf_init',
										array(
											'cert_id' => $certificate_post->ID,
											'user_id' => $cert_user_id,
											'post_id' => $quiz_id,
										)
									);
								} else {
									/**
									 * Include library to generate PDF
									 */
									require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/ld-convert-post-pdf.php';
									learndash_certificate_post_shortcode(
										array(
											'cert_id' => $certificate_post->ID,
											'user_id' => $cert_user_id,
											'post_id' => $quiz_id,
										)
									);
								}
								die();
							}
						}
					}
				}
			}
		}

		/**
		 * Action to allow custom handling of when a user cannot view a certificate.
		 *
		 * @since 3.2.3
		 */
		do_action( 'learndash_certificate_disallowed' );

		// If here we display the error and exit.
		esc_html_e( 'Access to certificate page is disallowed.', 'learndash' );
		die();
	}
}
\add_action( 'template_redirect', __NAMESPACE__ . '\certificate_display', 0 );

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

				case 'percentage':
					$value = absint( $history['percentage'] );
					break;
			}
		}
	}

	return $value;
}
\add_filter( 'learndash_courseinfo', __NAMESPACE__ . '\courseinfo', PHP_INT_MAX, 2 );
\add_filter( 'learndash_quizinfo', __NAMESPACE__ . '\courseinfo', PHP_INT_MAX, 2 );
