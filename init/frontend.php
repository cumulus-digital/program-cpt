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
	if ( \is_singular( CPTs::getKeys() ) || CPTs::isOurQuery() ) {
		/*
		$assets = include BASEPATH . '/build/frontend.asset.php';
		\wp_register_script(
			PREFIX . '_script-frontend',
			BASEURL . '/build/frontend.js',
			$assets['dependencies'],
			$assets['version'],
			true
		);
		\wp_enqueue_script( PREFIX . '_script-frontend' );
		 */

		\wp_register_style(
			PREFIX . '_style',
			BASEURL . '/build/frontend.css',
			array(), // array(PREFIX . '_customizer_vars'),
			null,
			'all'
		);
		\wp_enqueue_style( PREFIX . '_style' );
	}
} );

function alterQuerySettingsForPostType( $query ) {
	if (
		! \is_admin() && CPTs::isOurQuery()
	) {
		// Set query limit for our CPTs to infinite
		$query->set( 'posts_per_archive_page', -1 );

		// Always order by menu_order and title
		// $query->set( 'orderby', 'menu_order title' );
		// $query->set( 'order', 'ASC' );
		$query->set( 'orderby', array(
			'menu_order' => 'asc',
			'title'      => 'asc',
		) );
	}

	return $query;
}
\add_action( 'pre_get_posts', __NAMESPACE__ . '\alterQuerySettingsForPostType', \PHP_INT_MAX );

// Our category appender
include_once BASEPATH . '/templates/cpt-archive-appender.php';
