<?php

namespace CUMULUS\Wordpress\ProgramCPT;

// Exit if accessed directly.
\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

// Tell base theme where our templates are
\add_filter( 'cmls-locate_template_path', function ( $paths ) {
	if ( \is_singular( PREFIX ) ) {
		$paths[] = BASEPATH;
	}

	return $paths;
} );

// Use our archive template for CPT archive
\add_filter( 'template_include', function ( $template ) {
	if ( \is_post_type_archive( PREFIX ) ) {
		return BASEPATH . '/archive-program.php';
	}

	return $template;
} );

\add_action( 'wp_enqueue_scripts', function () {
	if ( \is_singular( PREFIX ) || \is_archive( PREFIX ) ) {
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

// Include our tag prepender
include_once BASEPATH . '/templates/cpt-archive-prepender.php';

// Our category appender
include_once BASEPATH . '/templates/cpt-archive-appender.php';
