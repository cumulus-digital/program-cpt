<?php

namespace CUMULUS\Wordpress\ProgramCPT;

// Exit if accessed directly.
\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

// Tell base theme where our templates are
\add_filter( 'cmls-locate_template_path', function ( $paths ) {
	if ( \is_singular( CPTs::getKeys() ) ) {
		$paths[] = BASEPATH;
	}

	return $paths;
} );

// Use our archive template for CPT archive
\add_filter( 'template_include', function ( $template ) {
	if ( \is_post_type_archive( CPTs::getKeys() ) ) {
		return BASEPATH . '/post_type_archive.php';
	}

	return $template;
} );

\add_action( 'wp_enqueue_scripts', function () {
	if ( \is_singular( CPTs::getKeys() ) || \is_archive( CPTs::getKeys() ) ) {
		$assets = include BASEPATH . '/build/frontend.asset.php';
		\wp_register_script(
			PREFIX . '_script-frontend',
			BASEURL . '/build/frontend.js',
			$assets['dependencies'],
			$assets['version'],
			true
		);
		\wp_enqueue_script( PREFIX . '_script-frontend' );

		\wp_register_style(
			PREFIX . '_style',
			BASEURL . '/build/frontend.css',
			[], //array(PREFIX . '_customizer_vars'),
			null,
			'all'
		);
		\wp_enqueue_style( PREFIX . '_style' );
	}
} );

// Set query limit for our CPTs to infinite
function setQueryLimitToInfinite( $query ) {
	if (
		! \is_admin() && $query->is_main_query() && \is_post_type_archive( CPTs::getKeys() )
	) {
		$query->set( 'posts_per_page', -1 );
	}

	return $query;
}
\add_action( 'pre_get_posts', __NAMESPACE__ . '\setQueryLimitToInfinite', 999 );

// Include our tag prepender
include_once BASEPATH . '/templates/cpt-archive-prepender.php';

// Our category appender
include_once BASEPATH . '/templates/cpt-archive-appender.php';
