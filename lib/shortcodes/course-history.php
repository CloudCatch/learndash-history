<?php
/**
 * Course history shortcode
 *
 * @package SeattleWebCo\LearnDashHistory
 */

namespace SeattleWebCo\LearnDashHistory\Shortcodes;

use function SeattleWebCo\LearnDashHistory\Functions\certificate_link;
use function SeattleWebCo\LearnDashHistory\Functions\get_user_history;

/**
 * Output a users course history
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function course_history_shortcode( $atts ) {
	global $wpdb;

	\wp_enqueue_style( 'learndash-history', LEARNDASH_HISTORY_URL . 'assets/css/frontend.css', array(), LEARNDASH_HISTORY_VER );

	$atts = \shortcode_atts(
		array(
			'user_id'                  => 0,
			'status'                   => null,
			'orderby'                  => 'course_title',
			'order'                    => 'ASC',
			'include_enrolled_courses' => '1',
		),
		$atts,
		'ld_course_history'
	);

	$user_id = \absint( $atts['user_id'] ?: \get_current_user_id() );
	$history = get_user_history( $user_id, $atts );

	ob_start();
	?>

	<div class="learndash-wrapper">
		<div class="learndash-history-course-history">
			<?php
			foreach ( $history as $item ) {
				$certificate_link = certificate_link( $item );
				?>

					<div class="learndash-history-course-info">
						<span class="learndash-history-course-info-title"><?php echo get_the_title( $item['post_id'] ); ?></span>
						<span class="learndash-history-course-info-date"><?php echo esc_html( gmdate( get_option( 'date_format' ), $item['activity_completed'] ?: $item['activity_started'] ) ); ?></span>
						<span class="learndash-history-course-info-type"><?php echo esc_html( \LearnDash_Custom_Label::get_label( $item['activity_type'] ) ); ?></span>
						<span class="learndash-history-course-info-status">
							<?php
								echo $item['activity_status'] && $item['activity_completed'] ? esc_html__( 'Completed', 'learndash-history' ) : esc_html__( 'In Progress', 'learndash-history' );
							?>
						</span>
						<span class="learndash-history-course-info-certificate">
							<?php
							if ( $certificate_link ) {
								?>

								<span>
									<a href="<?php echo esc_url( $certificate_link ); ?>" target="_blank" title="<?php esc_attr_e( 'View Certificate', 'learndash-history' ); ?>">
										<span class="ldh-icon-award"></span>
									</a>
								</span>

								<?php
							}
							?>
						</span>
					</div>

					<?php if ( ! empty( $item['quizzes'] ) ) { ?>

						<div class="learndash-history-course-quizzes">
							<?php foreach ( $item['quizzes'] as $quiz ) { ?>

								<div class="learndash-history-course-quiz">
									<span class="learndash-history-course-quiz-title">
										<a href="<?php echo get_permalink( $quiz['post_id'] ); ?>"><?php echo esc_html( $quiz['post_title'] ); ?></a>
									</span>
									<span class="learndash-history-course-quiz-score">
										<?php echo sprintf( '%s: %s', esc_html__( 'Score', 'learndash-history' ), esc_html( round( $quiz['percentage'], 2 ) . '%' ) ); ?>
									</span>
									<span class="learndash-history-course-quiz-certificate">
										<?php
										$certificate_link = certificate_link( $quiz );

										if ( $certificate_link ) {
											?>

													<a href="<?php echo esc_url( $certificate_link ); ?>" target="_blank" title="<?php esc_attr_e( 'View Certificate', 'learndash-history' ); ?>">
														<span class="ldh-icon-award"></span>
													</a>

												<?php
										}
										?>
									</span>
								</div>

							<?php } ?>
						</div>

					<?php } ?>

				<?php
			}
			?>
			</div>
		</div>

		<?php
		return ob_get_clean();
}
\add_shortcode( 'ld_course_history', __NAMESPACE__ . '\course_history_shortcode' );
