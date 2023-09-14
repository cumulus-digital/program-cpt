<?php
/**
 * Append child categories and a CPT list to our custom category archives.
 */

namespace CUMULUS\Wordpress\ProgramCPT;

// Exit if accessed directly.
\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

use function CMLS_Base\tax_query_includes_children;

\add_action( 'pre_get_posts', function ( $q ) {
	if (
		! \is_admin()
		&& ! \is_search()
		&& $q->is_main_query()
	) {
		// For our base-level CPT archives, only show posts that aren't in a category
		if ( $q->is_post_type_archive( CPTs::getKeys() ) && ! \is_tax() ) {
			$tax_query = \array_map(
				function ( $cpt ) {
					return array(
						'taxonomy' => "{$cpt}-cat",
						'operator' => 'NOT EXISTS',
					);
				},
				CPTs::getKeys()
			);

			$original_tax_query = $q->get( 'tax_query' );
			if ( \is_array( $original_tax_query ) ) {
				$tax_query = \array_merge( $original_tax_query, $tax_query );
			}

			$q->set( 'tax_query', $tax_query );
		}
	}

	return $q;
} );

// Set appropriate image sizes
\add_filter( 'display-archive-all', function ( $args ) {
	if ( ! CPTs::isOurQuery() || ! \is_archive( CPTs::getKeys() ) || \is_search() ) {
		return $args;
	}

	if ( $args['thumbnail_size'] === 'thumbnail-uncropped' ) {
		$args['thumbnail_attributes']['sizes'] = '(max-width: 600px) 150px, 400px';
	}

	return $args;
} );

// Include sub-categories in archives
\add_action( 'template_include', function ( $template ) {
	if ( ! CPTs::isOurQuery() || ! \is_archive( CPTs::getKeys() ) || \is_search() ) {
		return $template;
	}

	$tag_taxes = \array_map(
		function ( $str ) {
			return $str . '-tag';
		},
		CPTs::getKeys()
	);

	$is_tag_query  = false;
	$tags_searched = array();

	foreach ( $tag_taxes as $tag_tax ) {
		if ( ! empty( $_GET[$tag_tax] ) ) {
			$is_tag_query = true;
			$tax          = \get_term_by( 'slug', $_GET[$tag_tax], $tag_tax );
			if ( $tax ) {
				$tags_searched[] = $tax;
			}
		}
	}

	if ( \count( $tags_searched ) ) {
		\add_action( 'cmls_template-archive-header-after_title', function () use ( $tags_searched ) {
			$args['tags_searched'] = $tags_searched;
			include 'archives/term_view_tags.php';
		} );
	}

	\add_action( 'cmls_template-archive-after_content', function ( $args ) use ( $is_tag_query, $tag_taxes ) {
		$term_children = array();
		if ( \is_array( $args ) && \array_key_exists( 'term_children', $args ) ) {
			$term_children = $args['term_children'];
		} else {
			$taxes = \array_map(
				function ( $str ) {
					return $str . '-cat';
				},
				CPTs::getKeys()
			);

			$this_obj = \get_queried_object();

			$cat_name      = null;
			$term_children = null;

			// NOTE: because PublishPress Permissions may break term cache, we cannot use get_term_children!
			global $wpdb;

			// If it's a post type, get all base-level terms
			if ( \is_a( $this_obj, 'WP_Post_Type' ) && ! \is_search() ) {
				// Get categories for this CPT
				$term_children_ids = $wpdb->get_col( $wpdb->prepare( "SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy=%s AND parent=0", $this_obj->name . '-cat' ) );
				$cat_name          = $this_obj->name . '-cat';
			}
			if ( \is_a( $this_obj, 'WP_Term' ) && \is_taxonomy_hierarchical( $this_obj->taxonomy ) ) {
				// Get all sub-categories for this term
				$term_children_ids = $wpdb->get_col( $wpdb->prepare( "SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy=%s AND parent=%d", $this_obj->taxonomy, $this_obj->term_id ) );
				$cat_name          = $this_obj->taxonomy;
			}

			if ( ! $cat_name ) {
				return;
			}

			if ( \is_array( $term_children_ids ) && \count( $term_children_ids ) ) {
				$term_children = get_terms( array(
					'taxonomy'   => $cat_name,
					'include'    => $term_children_ids,
					'hide_empty' => true,
				) );
			}
		}

		if ( $term_children && ( ! tax_query_includes_children() || $is_tag_query ) ) {
			$args['term_children'] = $term_children;
			$args['tag_taxes']     = $tag_taxes;
			include 'archives/term_children.php';
		}
	} );

	// Append a list of tags in these categories and their children
	\add_action( 'cmls_template-archive-after_content', function () {
		$this_obj = \get_queried_object();
		$base_url = '/';
		$tax_name = '';
		$cat      = (object) array(
			'term_id'  => 0,
			'taxonomy' => 'cat',
		);

		if ( \is_a( $this_obj, 'WP_Post_Type' ) && ! \is_search() ) {
			$base_url      = \get_post_type_archive_link( $this_obj->name );
			$tax_name      = $this_obj->name . '-tag';
			$cat->taxonomy = $this_obj->name . '-cat';
		}
		if ( \is_a( $this_obj, 'WP_Term' ) && \is_taxonomy_hierarchical( $this_obj->taxonomy ) ) {
			$base_url = \get_term_link( $this_obj );
			$tax      = \get_taxonomy( $this_obj->taxonomy );
			$tax_name = $tax->object_type[0] . '-tag';
			$cat      = $this_obj;
		}

		$tax_tags = \CMLS_Base\get_category_tags(
			$cat,
			$tax_name,
			true
		);

		if ( \count( $tax_tags ) ) {
			\usort( $tax_tags, function ( $tag1, $tag2 ) {
				return $tag1->count < $tag2->count ? 1 : -1;
			} );

			$args['tax_tags'] = $tax_tags;
			$args['base_url'] = $base_url;
			include 'archives/term_tags.php';
		}
	}, 999 );

	return $template;
}, \PHP_INT_MAX );
