<?php
/**
 * Admin views
 *
 * @package SeattleWebCo\LearnDashHistory
 */

namespace SeattleWebCo\LearnDashHistory\Functions;

use SeattleWebCo\LearnDashHistory\ActivityHistory;

/**
 * Register LearnDash Activity History submenu page
 *
 * @return void
 */
function view_history() {
	\add_submenu_page(
		'learndash-lms',
		\esc_html__( 'Activity History', 'learndash-history' ),
		\esc_html__( 'Activity History', 'learndash-history' ),
		defined( 'LEARNDASH_ADMIN_CAPABILITY_CHECK' ) ? constant( 'LEARNDASH_ADMIN_CAPABILITY_CHECK' ) : 'manage_options',
		'learndash-activity-history',
		__NAMESPACE__ . '\view_renderer'
	);
}
\add_action( 'admin_menu', __NAMESPACE__ . '\view_history' );

/**
 * Callback for LearnDash Activity History submenu page
 *
 * @return void
 */
function view_renderer() {
	$table = new ActivityHistory();
	$table->prepare_items();
	?>

	<div class="wrap">
		<h1><?php esc_html_e( 'Activity History', 'learndash-history' ); ?></h1>
		<form action="" method="GET">
			<?php $table->views(); ?>
			<?php $table->search_box( esc_attr__( 'Search', 'learndash-history' ), 's' ); ?>
			<?php $table->display(); ?>
			<input type="hidden" name="page" value="<?php echo esc_attr( sanitize_key( $_GET['page'] ?? '' ) ); ?>" />
		</form>
	</div>

	<?php
}
