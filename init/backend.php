<?php

namespace CUMULUS\Wordpress\ProgramCPT;

use DOMDocument;
use DOMXPath;
use Exception;

// Exit if accessed directly.
\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

\add_action( 'enqueue_block_assets', function () {
	global $pagenow;

	if ( ! \is_admin() || 'widgets.php' === $pagenow ) {
		return;
	}

	$screen = \get_current_screen();

	if (
		$screen->is_block_editor
		&& \in_array( $screen->post_type, CPTs::getKeys() )
	) {
		\wp_enqueue_style( 'cmls-program-cpt-block-style', BASEURL . '/build/backend.css' );

		$assets = include BASEPATH . '/build/backend.asset.php';
		\wp_enqueue_script(
			'cmls-program-cpt-backend-script',
			BASEURL . '/build/backend.js',
			$assets['dependencies'],
			$assets['version'],
			true
		);
	}
} );

// Intercept excerpt building so that it comes from the main content column.
\add_filter( 'get_the_excerpt', function ( $excerpt, $post ) {
	if (
		$post->post_excerpt
		|| ! \class_exists( __NAMESPACE__ . '\\CPTs' )
		|| \count( CPTs::getKeys() ) < 1
		|| ! \in_array( $post->post_type, CPTs::getKeys() )
	) {
		return $excerpt;
	}

	if ( ! $post->post_content ) {
		return $excerpt;
	}

	// Pull a new excerpt out of the .main-content div
	try {
		$dom = new DOMDocument();
		$dom->loadHTML( $post->post_content );
		$xpath   = new DOMXPath( $dom );
		$content = $xpath->query( "//div[contains(@class,'program-content')][1]/div[contains(@class,'main-content')][1]" );

		if ( ! $content->length ) {
			return $excerpt;
		}

		$excerpt_length = \apply_filters( 'excerpt_length', 55 );
		$excerpt_more   = \apply_filters( 'excerpt_more', ' ' . '[&hellip;]' );
		$text           = \wp_trim_words( $content->item( 0 )->textContent, $excerpt_length, $excerpt_more );

		// Attempt to truncate to the nearest sentence
		$puncs  = ['. ', '! ', '? '];
		$maxPos = 0;

		foreach ( $puncs as $punc ) {
			$pos = \mb_strrpos( $text, $punc );

			if ( $pos && $pos > $maxPos ) {
				$maxPos = $pos;
			}
		}

		if ( $maxPos ) {
			$text = \mb_substr( $text, 0, $maxPos + 1 );
		}

		// Update the post with this generated excerpt so we don't have to keep doing this...
		//$post->post_excerpt = $text;
		//\wp_update_post( $post );

		return $text;
	} catch ( Exception $e ) {
		return $excerpt;
	}
}, 10, 2 );

// If helper exists, disable reordering on our metaboxes
\add_action( 'admin_init', function () {
	if ( \function_exists( 'CMLS_Base\acfResetMetaboxesForCPT' ) ) {
		\CMLS_Base\acfResetMetaboxesForCPT( 'program', 'group_61240bc1afe31' );
	}
} );
