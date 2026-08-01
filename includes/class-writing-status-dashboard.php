<?php
/**
 * Writing Status Dashboard Widget
 *
 * Registers and renders the Draft Writing Status dashboard widget,
 * including the priority-based orderby filter for widget queries.
 *
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WritingStatusDashboard extends WritingStatusRenderer {

	public function __construct() {
		add_action( 'wp_dashboard_setup', array( $this, 'addDashboardWidget' ) );
	}

	public function addDashboardWidget() {
		// skipcq: PHP-W1020
		wp_add_dashboard_widget(
			'writing_status_widget',
			__( 'Draft Writing Status', 'writing-status' ),
			array( $this, 'renderDashboardWidget' )
		);
	}

	public function renderDashboardWidget() {
		$cached = get_transient( 'writing_status_dashboard_html' );
		if ( false !== $cached ) {
			echo $cached; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already-escaped HTML captured from this same method below before caching.
			return;
		}

		ob_start();
		list($incomplete_query, $complete_query) = $this->getDashboardQueries();
		?>
		<div class="writing-status-widget">
			<?php $this->renderDashboardIncompletePosts( $incomplete_query ); ?>
			<?php $this->renderDashboardCompletePosts( $complete_query ); ?>

			<?php if ( ! $incomplete_query->have_posts() && ! $complete_query->have_posts() ) : ?>
				<output><?php esc_html_e( 'No drafts found. Start writing!', 'writing-status' ); ?></output>
			<?php endif; ?>

			<p class="writing-status-link">
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_status=draft&post_type=post' ) ); ?>" aria-label="<?php esc_attr_e( 'View all draft posts in the posts list', 'writing-status' ); ?>">
					<?php esc_html_e( 'View All Drafts →', 'writing-status' ); ?>
				</a>
			</p>
		</div>
		<?php
		wp_reset_postdata();

		$html = ob_get_clean();
		set_transient( 'writing_status_dashboard_html', $html, HOUR_IN_SECONDS );
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- same reason as above.
	}
}
