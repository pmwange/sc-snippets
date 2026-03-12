<?php
function sce_remove_archive_event_template() {
	if ( ! function_exists( 'sugar_calendar' ) || is_admin() ) {
		return;
	}

	$sce = sugar_calendar();

	if ( empty( $sce ) || ! method_exists( $sce, 'get_frontend' ) ) {
		return;
	}
		
	remove_filter( 'the_posts', [ $sce->get_frontend(), 'inject_archive_event_template_content' ] );
}
add_action( 'init', 'sce_remove_archive_event_template' );