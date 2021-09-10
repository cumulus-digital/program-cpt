<?php

namespace CUMULUS\Wordpress\ProgramCPT;

// Exit if accessed directly.
\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

/*
 * Extended-CPTs does not handle hierarchical taxonomy links in
 * permastruct, so we'll replace its post_type_link filter with
 * one which does.
 */
function make_post_link_hierarchical( Extended_CPT $cpt ) {
	\remove_filter( 'post_type_link', [$cpt, 'post_type_link'], 1 );

	\add_filter(
		'post_type_link',
		__NAMESPACE__ . '\_extended_cpts_hierarchical_post_link',
		1,
		4
	);
}

function _extended_cpts_hierarchical_post_link( string $post_link, \WP_Post $post, bool $leavename, bool $sample ): string {
	// If it's not our post type, bail out:
	if ( $post->post_type !== PREFIX ) {
		return $post_link;
	}

	$date         = \explode( ' ', \mysql2date( 'Y m d H i s', $post->post_date ) );
	$replacements = ['%year%' => $date[0], '%monthnum%' => $date[1], '%day%' => $date[2], '%hour%' => $date[3], '%minute%' => $date[4], '%second%' => $date[5], '%post_id%' => $post->ID];

	if ( false !== \mb_strpos( $post_link, '%author%' ) ) {
		$replacements['%author%'] = \get_userdata( (int) $post->post_author )->user_nicename;
	}

	foreach ( \get_object_taxonomies( $post ) as $tax ) {
		if ( false === \mb_strpos( $post_link, "%{$tax}%" ) ) {
			continue;
		}
		$terms = \get_the_terms( $post, $tax );

		if ( $terms ) {
			$current_term    = \reset( $terms );
			$taxonomy_object = \get_taxonomy( $tax );

			// Hierarchical rewrite
			if ( isset( $taxonomy_object->rewrite['hierarchical'] ) && $taxonomy_object->rewrite['hierarchical'] ) {
				// https://github.com/WordPress/WordPress/blob/4.9.5/wp-includes/taxonomy.php#L3957-L3965
				$hierarchical_slugs = [];
				$ancestors          = \get_ancestors( $current_term->term_id, $tax, 'taxonomy' );

				foreach ( (array) $ancestors as $ancestor ) {
					$ancestor_term        = \get_term( $ancestor, $tax );
					$hierarchical_slugs[] = $ancestor_term->slug;
				}
				$hierarchical_slugs   = \array_reverse( $hierarchical_slugs );
				$hierarchical_slugs[] = $current_term->slug;
				$term                 = \implode( '/', $hierarchical_slugs );
			} else {
				/**
				 * Filter the term that gets used in the `$tax` permalink token.
				 *
				 * @TODO make this more betterer ^
				 *
				 * @param WP_Term   $term  the `$tax` term to use in the permalink
				 * @param WP_Term[] $terms array of all `$tax` terms associated with the post
				 * @param WP_Post   $post  the post in question
				 */
				$term_object = \apply_filters( "post_link_{$tax}", \reset( $terms ), $terms, $post );
				$term        = \get_term( $term_object, $tax )->slug;
			}
		} else {
			$term = $post->post_type;
			/**
			 * Filter the default term name that gets used in the `$tax` permalink token.
			 *
			 * @TODO make this more betterer ^
			 *
			 * @param string  $term the `$tax` term name to use in the permalink
			 * @param WP_Post $post the post in question
			 */
			$default_term_name = \apply_filters( "default_{$tax}", \get_option( "default_{$tax}", '' ), $post );

			if ( $default_term_name ) {
				$default_term = \get_term( $default_term_name, $tax );

				if ( ! \is_wp_error( $default_term ) ) {
					$term = $default_term->slug;
				}
			}
		}
		$replacements["%{$tax}%"] = $term;
	}
	$post_link = \str_replace( \array_keys( $replacements ), $replacements, $post_link );

	return $post_link;
}
