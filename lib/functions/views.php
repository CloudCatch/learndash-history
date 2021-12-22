<?php
/**
 * Admin views
 *
 * @package SeattleWebCo\LearnDashHistory
 */

namespace SeattleWebCo\LearnDashHistory\Functions;

use SeattleWebCo\LearnDashHistory\ActivityHistory;

/**
 * Enqueue admin assets
 *
 * @return void
 */
function view_scripts() {
	\wp_register_style( 'learndash-history-admin', LEARNDASH_HISTORY_URL . 'assets/css/admin.css', array(), LEARNDASH_HISTORY_VER );
	\wp_register_script( 'learndash-history-admin', LEARNDASH_HISTORY_URL . 'assets/js/admin.js', array( 'jquery' ), LEARNDASH_HISTORY_VER, true );

	\wp_localize_script(
		'learndash-history-admin',
		'learndashhistory',
		array(
			'nonce'   => \wp_create_nonce( 'wp_rest' ),
			'restUrl' => \get_rest_url(),
		)
	);

	if ( ! isset( $_GET['page'] ) || 'learndash-activity-history' !== $_GET['page'] ) {
		return;
	}

	\wp_enqueue_style( 'learndash-history-admin' );
	\wp_enqueue_script( 'learndash-history-admin' );
	\wp_set_script_translations( 'learndash-history-admin', 'learndash-history', LEARNDASH_HISTORY_PATH . 'languages' );
}
\add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\view_scripts' );

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

		<div id="learndash-history-migrator"></div>

		<form action="" method="GET">
			<?php $table->views(); ?>
			<?php $table->search_box( \esc_attr__( 'Search', 'learndash-history' ), 's' ); ?>
			<?php $table->display(); ?>
			<input type="hidden" name="page" value="<?php echo \esc_attr( \sanitize_key( $_GET['page'] ?? '' ) ); ?>" />
		</form>
	</div>

	<?php
}
