<?php

namespace CUMULUS\Wordpress\ProgramCPT;

// Exit if accessed directly.
\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

\add_action( 'enqueue_block_assets', function () {
	if ( ! \is_admin() ) {
		return;
	}

	$screen = \get_current_screen();

	if ( $screen->post_type === PREFIX && $screen->is_block_editor ) {
		\wp_enqueue_style( 'cmls-program-cpt-block-style', BASEURL . '/build/backend.css' );
	}
} );
