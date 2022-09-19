<?php
/**
 * History functions
 *
 * @package SeattleWebCo\LearnDashHistory
 */

namespace SeattleWebCo\LearnDashHistory\Functions;

/**
 * Retrieve LearnDash history for a given user, grouped by course
 *
 * @param null|int $user_id User ID.
 * @param array    $args {
 *     An array, object, or WP_User object of user data arguments.
 *
 *     @type int    $ID                   User ID. If supplied, the user will be updated.
 *     @type string $user_pass            The plain-text user password.
 *     @type string $user_login           The user's login username.
 *     @type string $user_nicename        The URL-friendly user name.
 *     @type string $user_url             The user URL.
 *     @type string $user_email           The user email address.
 *     @type string $display_name         The user's display name.
 *                                        Default is the user's username.
 *     @type string $nickname             The user's nickname.
 *                                        Default is the user's username.
 *     @type string $first_name           The user's first name. For new users, will be used
 *                                        to build the first part of the user's display name
 *                                        if `$display_name` is not specified.
 *     @type string $last_name            The user's last name. For new users, will be used
 *                                        to build the second part of the user's display name
 *                                        if `$display_name` is not specified.
 *     @type string $description          The user's biographical description.
 *     @type string $rich_editing         Whether to enable the rich-editor for the user.
 *                                        Accepts 'true' or 'false' as a string literal,
 *                                        not boolean. Default 'true'.
 *     @type string $syntax_highlighting  Whether to enable the rich code editor for the user.
 *                                        Accepts 'true' or 'false' as a string literal,
 *                                        not boolean. Default 'true'.
 *     @type string $comment_shortcuts    Whether to enable comment moderation keyboard
 *                                        shortcuts for the user. Accepts 'true' or 'false'
 *                                        as a string literal, not boolean. Default 'false'.
 *     @type string $admin_color          Admin color scheme for the user. Default 'fresh'.
 *     @type bool   $use_ssl              Whether the user should always access the admin over
 *                                        https. Default false.
 *     @type string $user_registered      Date the user registered. Format is 'Y-m-d H:i:s'.
 *     @type string $user_activation_key  Password reset key. Default empty.
 *     @type bool   $spam                 Multisite only. Whether the user is marked as spam.
 *                                        Default false.
 *     @type string $show_admin_bar_front Whether to display the Admin Bar for the user
 *                                        on the site's front end. Accepts 'true' or 'false'
 *                                        as a string literal, not boolean. Default 'true'.
 *     @type string $role                 User's role.
 *     @type string $locale               User's locale. Default empty.
 * }
 * @return array
 */
function get_user_history( $user_id = null, $args = array() ) {
	global $wpdb;

	$args = \wp_parse_args(
		$args,
		array(
			'status'                   => null,
			'include_enrolled_courses' => 1,
			'types'					   => array( 'course', 'quiz', 'access' )
		)
	);

	$history = $args['include_enrolled_courses'] ? array_map(
		function( $value ) {
			return array();
		},
		array_flip( learndash_user_get_enrolled_courses( \absint( $user_id ) ) )
	) : array();

	$where = sprintf( 'WHERE 1=1 AND `user`.ID = %d ', \absint( $user_id ) );

	if ( $args['status'] ) {
		$where .= sprintf( ' AND `history`.activity_status = %d ', \absint( $args['status'] ) );
	}

	if ( $args['types'] ) {
		$where .= "AND `history`.activity_type IN ('" . implode( "', '", $args['types'] ) . "')";
	} else {
		$where .= "AND `history`.activity_type IN ('course', 'quiz')";
	}

    // phpcs:ignore
    $history = $wpdb->get_results( 
		"
			SELECT SQL_CALC_FOUND_ROWS `history`.*, `user`.*, `course`.post_title course_title, `post`.post_title post_title
			FROM   `{$wpdb->prefix}learndash_history` history
				LEFT JOIN `{$wpdb->users}` user
					ON `history`.user_id = `user`.ID
				JOIN   `{$wpdb->posts}` course
					ON `history`.course_id = `course`.ID
				JOIN   `{$wpdb->posts}` post
					ON `history`.post_id = `post`.ID
			{$where}
			ORDER BY `history`.id DESC",
		ARRAY_A
	);

	return $history;
}
