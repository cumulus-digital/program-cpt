<?php

namespace CUMULUS\Wordpress\ProgramCPT;

// Exit if accessed directly.
\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

\add_action( 'init', function () {
	$options = \get_option( TXTDOMAIN );

	if ( \is_array( $options ) && \count( $options ) ) {
		\register_taxonomy(
			PREFIX . '-cat',
			PREFIX,
			[
				'public'       => true,
				'hierarchical' => true,
				'show_in_rest' => true,
				'query_var'    => true,
				'label'        => $options['cat-plural'],
				'rewrite'      => [
					'slug'         => $options['cpt-slug'] . '/' . $options['cat-slug'],
					'hierarchical' => true,
					'with_front'   => false,
				],
				/*
				'capabilities' => [
					'manage_terms' => 'manage_' . PREFIX . '-cat',
					'edit_terms'   => 'manage_' . PREFIX . '-cat',
					'delete_terms' => 'manage_' . PREFIX . '-cat',
					'assign_terms' => 'edit_' . PREFIX,
				],
				*/
			]
		);
		/*
		\register_extended_taxonomy(
			PREFIX . '-cat',
			PREFIX,
			[
				'allow_hierarchy'  => true,
				'exclusive'        => true,
				'meta_box'         => 'dropdown',
				'dashboard_glance' => true,
			],
			[
				'singular' => $options['cat-singular'],
				'plural'   => $options['cat-plural'],
				'slug'     => $options['cpt-slug'] . '/' . $options['cat-slug'],
			]
		);
		*/
		// Extended-CPTs doesn't really seem to behave well for
		// tag-like taxonomies, so we'll use the regular builder.
		\register_taxonomy(
			PREFIX . '-tag',
			PREFIX,
			[
				'public'       => true,
				'hierarchical' => false,
				'show_in_rest' => true,
				'query_var'    => true,
				'label'        => $options['tag-plural'],
				'rewrite'      => [
					'slug'       => $options['cpt-slug'] . '/' . $options['tag-slug'],
					'with_front' => false,
				],
				/*
				'capabilities' => [
					'manage_terms' => 'manage_' . PREFIX . '-tag',
					'edit_terms'   => 'manage_' . PREFIX . '-tag',
					'delete_terms' => 'manage_' . PREFIX . '-tag',
					'assign_terms' => 'edit_' . PREFIX,
				],
				*/
			]
		);

		// Filter displays of our tax
		\add_action( 'pre_get_posts', function ( $query ) {
			$taxonomy_slugs = [PREFIX . '-cat'];

			if ( \is_admin() || ! $query->is_main_query() ) {
				return;
			}

			if ( ! \is_tax( $taxonomy_slugs ) ) {
				return;
			}

			if ( \is_singular() ) {
				return;
			}

			// Display all posts in these taxes
			$query->set( 'posts_per_page', -1 );

			// Order them by title
			$query->set( 'orderby', [ 'title' => 'ASC' ] );

			// Exclude children from archives for this taxonomy
			$tax_query = $query->tax_query->queries;
			$tax_query[0]['include_children'] = false;
			$query->set( 'tax_query', $tax_query );
		}, 1, 1 );
	}
} );

// Handle redirect for base category

// Handle redirects for deeply nested categories without /category/
\add_action( 'template_redirect', function () {
	global $wp_query;

	function is_term_query() {
		global $wp_query;
		$tax = $wp_query->get( 'taxonomy' );

		return $tax === PREFIX . '-cat' || $tax === PREFIX . '-tag';
	}
	$post_type = $wp_query->get( 'post_type' );

	if (
		! (
			$post_type === PREFIX
			|| is_term_query()
		)
		|| ! \is_404()
		|| \is_admin()
	) {
		return;
	}

	// Trick to get the full URL
	$url = \array_filter( \explode( '/', \add_query_arg( '', '' ) ) );

	if ( \is_array( $url ) && \count( $url ) ) {
		$test_slug = \array_pop( $url );

		// Handle links to terms without term-slug base
		if ( $post_type === PREFIX ) {
			$taxq = $wp_query->get( 'taxonomy' );
			$term = PREFIX . '-cat';

			if ( $taxq === PREFIX . '-tag' ) {
				$term = PREFIX . '-tag';
			}

			$test_term = \get_term_by( 'slug', $test_slug, $term );

			if ( $test_term ) {
				return \wp_safe_redirect( \get_term_link( $test_term ), 302 );
			}
		}

		// Handle links to cat slug base
		if ( is_term_query() ) {
			$options = \get_option( TXTDOMAIN );

			if (
				$test_slug === $options['cat-slug']
				|| $test_slug === $options['tag-slug']
			) {
				return \wp_safe_redirect( \get_post_type_archive_link( PREFIX ), 301 );
			}
		}
	}
} );
