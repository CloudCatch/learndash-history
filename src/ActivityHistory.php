<?php
/**
 * ActivityHistory class
 *
 * @package SeattleWebCo\LearnDashHistory
 */

namespace SeattleWebCo\LearnDashHistory;

use function SeattleWebCo\LearnDashHistory\Functions\certificate_link;

/**
 * ActivityHistory class file
 */
class ActivityHistory extends \WP_List_Table {

	/**
	 * Prepare the search query
	 *
	 * @return string
	 */
	public function prepare_search() {
		$query = trim( esc_sql( sanitize_key( $_GET['s'] ?? '' ) ) );
		$where = 'WHERE 1 = 1 ';

		$search_fields = apply_filters(
			'learndash_history_search_fields',
			array(
				'user_login',
				'user_email',
				'user_nicename',
				'display_name',
			)
		);

		if ( ! empty( $query ) && ! empty( $search_fields ) ) {
			$where .= 'AND (';

			$i = 0;
			foreach ( $search_fields as $field ) {
				$where .= $i ? " OR {$field} LIKE '%{$query}%' " : " {$field} LIKE '%{$query}%' ";

				$i++;
			}

			$where .= "OR `course`.post_title LIKE '%{$query}%'";
			$where .= "OR `post`.post_title LIKE '%{$query}%'";

			$where .= ')';
		}

		if ( $this->get_activity_type() ) {
			$where .= " AND activity_type = '{$this->get_activity_type()}' ";
		}

		return $where;
	}

	/**
	 * Prepare the items for the table to process
	 *
	 * @return void
	 */
	public function prepare_items() {
		global $wpdb;

		$columns      = $this->get_columns();
		$hidden       = $this->get_hidden_columns();
		$sortable     = $this->get_sortable_columns();
		$current_page = $this->get_pagenum();
		$search       = $this->prepare_search();

		$where = apply_filters( 'learndash_history_prepare_where', $search );

		$per_page = 20;
		$offset   = $current_page <= 1 ? 0 : ( $current_page - 1 ) * $per_page;

		// phpcs:ignore
		$data = $wpdb->get_results(
			"
            SELECT SQL_CALC_FOUND_ROWS `history`.*, `user`.*, `course`.post_title course_title, `post`.post_title post_title
            FROM   `{$wpdb->prefix}learndash_history` history
                JOIN `{$wpdb->users}` user
                    ON `history`.user_id = `user`.ID
                JOIN   `{$wpdb->posts}` course
                    ON `history`.course_id = `course`.ID
                JOIN   `{$wpdb->posts}` post
                    ON `history`.post_id = `post`.ID
			{$where}
			ORDER BY `history`.id DESC
            LIMIT {$offset}, {$per_page}
        ",
			ARRAY_A
		);

		usort( $data, array( &$this, 'sort_data' ) );

		// phpcs:ignore
		$total = $wpdb->get_var( 'SELECT FOUND_ROWS();' );

		$this->set_pagination_args(
			array(
				'total_items' => $total,
				'per_page'    => $per_page,
			)
		);

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $data;
	}

	/**
	 * Override the parent columns method. Defines the columns to use in your listing table
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = apply_filters(
			'learndash_history_table_columns',
			array(
				'user'               => esc_html__( 'User', 'learndash-history' ),
				'course_title'       => esc_html__( 'Course', 'learndash-history' ),
				'activity_type'      => esc_html__( 'Type', 'learndash-history' ),
				'activity_completed' => esc_html__( 'Completed', 'learndash-history' ),
				'certificate'        => esc_html__( 'Certificate', 'learndash-history' ),
				'pass'               => esc_html__( 'Passed', 'learndash-history' ),
			)
		);

		return $columns;
	}

	/**
	 * Define which columns are hidden
	 *
	 * @return array
	 */
	public function get_hidden_columns() {
		return array();
	}

	/**
	 * Define the sortable columns
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'activity_completed' => array( 'activity_completed', false ),
			'activity_started'   => array( 'activity_started', false ),
			'activity_type'      => array( 'activity_type', false ),
		);
	}

	/**
	 * Define what data to show on each column of the table
	 *
	 * @param array  $item Current row.
	 * @param string $column_name Column name.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'user':
				$name = __( 'Unknown', 'learndash-history' );

				if ( trim( $item['display_name'] ) ) {
					$name = $item['display_name'];
				} else {
					$userdata = get_userdata( $item['user_id'] );

					if ( $userdata->first_name && $userdata->last_name ) {
						$name = "$userdata->first_name $userdata->last_name";
					} elseif ( $userdata->first_name ) {
						$name = $userdata->first_name;
					} elseif ( $userdata->last_name ) {
						$name = $userdata->last_name;
					}
				}

				$value = sprintf( '<a href="%s" target="_blank">%s</a>', get_edit_user_link( $item['user_id'] ), esc_html( trim( $name ) ) );
				break;

			case 'activity_started':
			case 'activity_completed':
				$value = \gmdate( 'Y-m-d H:i:s', $item[ $column_name ] );
				break;

			case 'post_title':
				$value = $item['course_id'] !== $item['post_id'] ? $item[ $column_name ] : '';
				break;

			case 'activity_type':
				$value = \ucfirst( $item[ $column_name ] );
				break;

			case 'pass':
				$value = 'quiz' !== $item['activity_type'] || 1 === absint( $item['pass'] ) ? esc_html__( 'Yes', 'learndash-history' ) : esc_html__( 'No', 'learndash-history' );
				break;

			case 'certificate':
				$certificate_link = certificate_link( $item );

				$value = $certificate_link ? sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $certificate_link ), esc_html__( 'View Certificate', 'learndash-history' ) ) : '';

				break;

			default:
				$value = $item[ $column_name ] ?? '';
		}

		return \apply_filters( "learndash_history_{$column_name}_value", $value, $item );
	}

	/**
	 * Get current activity type
	 *
	 * @return string
	 */
	protected function get_activity_type() {
		if ( isset( $_GET['activity_type'] ) ) {
			if ( in_array( $_GET['activity_type'], array( 'course', 'lesson', 'topic', 'quiz' ) ) ) {
				return sanitize_key( $_GET['activity_type'] );
			}
		}

		return '';
	}

	/**
	 * Views
	 *
	 * @return array
	 */
	protected function get_views() {
		$views     = array();
		$base_link = admin_url( 'admin.php?page=learndash-activity-history' );

		$types = array(
			''        => esc_html__( 'All', 'learndash-history' ),
			'course'  => \LearnDash_Custom_Label::get_label( 'courses' ),
			'lesson'  => \LearnDash_Custom_Label::get_label( 'lessons' ),
			'topic'   => \LearnDash_Custom_Label::get_label( 'topics' ),
			'quiz'    => \LearnDash_Custom_Label::get_label( 'quizzes' ),
		);

		foreach ( $types as $key => $type ) {
			$views[ $key ] = sprintf(
				'<a href="%s" class="%s">%s</a>',
				$key ? add_query_arg( array( 'activity_type' => $key ), $base_link ) : $base_link,
				$key === $this->get_activity_type() ? 'current' : '',
				esc_html( $type )
			);
		}

		return $views;
	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'No history found.', 'learndash-history' );
	}

	/**
	 * Allows you to sort the data by the variables set in the $_GET
	 *
	 * @param string $a Value to compare.
	 * @param string $b Value to compare to.
	 * @return int
	 */
	private function sort_data( $a, $b ) {
		// Set defaults.
		$orderby = 'activity_completed';
		$order   = 'desc';

		// If orderby is set, use this as the sort column.
		if ( ! empty( $_GET['orderby'] ) ) {
			$orderby = sanitize_key( $_GET['orderby'] );
		}

		// If order is set use this as the order.
		if ( ! empty( $_GET['order'] ) ) {
			$order = sanitize_key( $_GET['order'] );
		}

		$result = strcmp( $a[ $orderby ], $b[ $orderby ] );

		if ( $order === 'asc' ) {
			return $result;
		}

		return -$result;
	}
}
