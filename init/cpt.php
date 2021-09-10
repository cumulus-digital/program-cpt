<?php

namespace CUMULUS\Wordpress\ProgramCPT;

// Exit if accessed directly.
\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

/**
 * Output hierarchical term links in admin columns for this CPT
 */
function outputAdminColsForHierarchicalTerms() {
	// Output hierarchical term links
	global $post;
	$tax   = PREFIX . '-cat';
	$terms = \get_the_terms( $post, $tax );
	$taxes = [];

	if ( $terms ) {
		$current_term    = \reset( $terms );
		$taxonomy_object = \get_taxonomy( $tax );

		if ( isset( $taxonomy_object->rewrite['hierarchical'] ) && $taxonomy_object->rewrite['hierarchical'] ) {
			// https://github.com/WordPress/WordPress/blob/4.9.5/wp-includes/taxonomy.php#L3957-L3965
			$hierarchical = [];
			$ancestors    = \get_ancestors( $current_term->term_id, $tax, 'taxonomy' );

			foreach ( (array) $ancestors as $ancestor ) {
				$ancestor_term  = \get_term( $ancestor, $tax );
				$hierarchical[] = $ancestor_term;
			}
			$hierarchical   = \array_reverse( $hierarchical );
			$hierarchical[] = $current_term;
			$taxes          = $hierarchical;
		} else {
			$term_object = \apply_filters( "post_link_{$tax}", \reset( $terms ), $terms, $post );
			$taxes       = [\get_term( $term_object, $tax )];
		}
	} else {
		$default_term_name = \apply_filters( "default_{$tax}", \get_option( "default_{$tax}", '' ), $post );

		if ( $default_term_name ) {
			$default_term = \get_term( $default_term_name, $tax );

			if ( ! \is_wp_error( $default_term ) ) {
				$taxes = [$default_term];
			}
		}
	}
	$out = [];

	foreach ( $taxes as $tax ) {
		$out[] = \sprintf(
			'<a href="%s">%s</a>',
			\esc_url( \add_query_arg(
				[
					'post_type' => PREFIX,
					'taxonomy'  => PREFIX . '-cat',
					'term'      => $tax->slug,
				],
				'edit.php'
			) ),
			\esc_html(
				\sanitize_term_field(
					'name',
					$tax->name,
					$tax->term_id,
					PREFIX . '-cat',
					'display'
				)
			)
		);
	}
	$top = \array_pop( $out );

	if ( \count( $out ) ) {
		echo '<small>' . \join( ' / ', $out ) . '</small><br>';
	}
	echo $top;
}

/*
 * Create our CPT
 */
\add_action( 'init', function () {
	$options = \get_option( TXTDOMAIN );

	if ( \is_array( $options ) && \count( $options ) ) {
		$cpt = \register_extended_post_type(
			PREFIX,
			[
				'hierarchical'       => false,
				'show_in_feed'       => true,
				'show_in_rest'       => true,
				'dashboard_activity' => true,
				'dashboard_glance'   => true,
				'supports'           => [
					'title',
					'editor',
					'revisions',
					'excerpt',
					'thumbnail',
					'custom-fields',
				],
				'capability_type' => 'page',
				'map_meta_cap'    => true,
				'template_lock'   => 'insert',
				'template'        => [
					[
						'core/columns',
						[
							'templateLock' => 'all',
							'className'    => 'program-content',
							'alignWide'    => false,
							'columns'      => 2,
						],
						[
							[
								'core/column',
								[
									'templateLock'      => false,
									'className'         => 'sidebar',
									'verticalAlignment' => 'top',
									'align'             => 'right',
									'width'             => '350px',
									'spacing'           => false,
								],
								[
									/*
									[ 'core/group', [
										'templateLock' => false,
										'align'        => 'right',
										'layout'       => [ 'inherit' => true ],
									] ],
									*/
									//[ 'core/paragraph', [ 'placeholder' => '(Sidebar content…)']  ],
								],
							],
							[
								'core/column',
								[
									'templateLock'      => false,
									'className'         => 'main-content',
									'verticalAlignment' => 'top',
									'spacing'           => false,
								],
								[
									[ 'core/paragraph', [ 'placeholder' => 'Begin main content here.']  ],
								],
							],
						],
					],
				],
				'menu_icon' => $options['cpt-icon'],
				'rewrite'   => [
					'permastruct'  => '/' . $options['cpt-slug'] . '/' . $options['cpt-permastruct'],
					'hierarchical' => true,
				],
				'admin_cols' => [
					$options['cat-plural'] => [
						'taxonomy' => PREFIX . '-cat',
						'function' => __NAMESPACE__ . '\\outputAdminColsForHierarchicalTerms',
					],
					'date',
				],
				'admin_filters' => [
					$options['cat-plural'] => [
						'taxonomy' => PREFIX . '-cat',
					],
					$options['tag-plural'] => [
						'taxonomy' => PREFIX . '-tag',
					],
				],
			],
			[
				'singular' => $options['cpt-singular'],
				'plural'   => $options['cpt-plural'],
				'slug'     => $options['cpt-slug'],
			]
		);

		make_post_link_hierarchical( $cpt );
	}
} );

// Allow Jetpack copy posts for this CPT
\add_filter( 'jetpack_copy_post_post_types', function ( $post_types ) {
	$post_types[] = PREFIX;

	return $post_types;
} );

// Include CPT in search results
\add_action( 'pre_get_posts', function ( $query ) {
	if ( ! \is_admin() && $query->is_main_query() && $query->is_search() ) {
		$current = (array) $query->get( 'post_type' );
		$current[] = PREFIX;
		$query->set( 'post_type', $current );
	}

	return $query;
} );

// Display filters
function is_our_query() {
	$q = \get_queried_object();

	if (
		(
			\is_object( $q )
			&& \property_exists( $q, 'taxonomy' )
			&& \in_array( $q->taxonomy, [PREFIX . '-tag', PREFIX . '-cat'] )
		)
		|| \is_post_type_archive( PREFIX )
	) {
		return true;
	}

	return false;
}
function return_if_ours( $ours = true, $else = false, $search = null ) {
	if ( ! \is_null( $search ) && \is_search() ) {
		return $else;
	}

	return is_our_query() ? $ours : $else;
}
\add_filter( 'display-archive-display_format', function ( $current ) {
	return return_if_ours( 'cards', $current, true );
}, 10 );
\add_filter( 'display-archive-show_title', function ( $current ) {
	return return_if_ours( false, $current, true );
}, 10 );
\add_filter( 'display-archive-show_date', function ( $current ) {
	return return_if_ours( false, $current );
}, 10 );
\add_filter( 'display-archive-show_author', function ( $current ) {
	return return_if_ours( false, $current );
}, 10 );
\add_filter( 'display-archive-show_category', function ( $current ) {
	return return_if_ours( false, $current, true );
}, 10 );
\add_filter( 'display-archive-show_source', function ( $current ) {
	return return_if_ours( false, $current, true );
}, 10 );
\add_filter( 'display-archive-show_excerpt', function ( $current ) {
	return return_if_ours( false, $current, true );
}, 10 );
