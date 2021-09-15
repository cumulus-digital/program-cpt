<?php
/**
 * CPT Archive page, should only show the base category terms
 */

namespace CUMULUS\Wordpress\ProgramCPT;

use function CMLS_Base\cmls_get_template_part;
use function CMLS_Base\make_post_class;

$this_term     = \get_queried_object();
$term_children = null;

if ( \is_a( $this_term, 'WP_Post_Type' ) ) {
	//$term_children_ids = \get_term_children( 0, $this_term->name . '-cat' );
	// Necessary to go back to the DB because PublishPress Permissions may break the cache...
	global $wpdb;
	$term_children_ids = $wpdb->get_col( $wpdb->prepare( "SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy=%s AND parent=0", $this_term->name . '-cat' ) );

	if ( \is_array( $term_children_ids ) && \count( $term_children_ids ) ) {
		$term_children = \get_terms( [
			'taxonomy'   => $this_term->name . '-cat',
			'include'    => $term_children_ids,
			'hide_empty' => true,
		] );
	}

	global $wp_query;
	$wp_query->posts      = null;
	$wp_query->post_count = 0;
}

\CMLS_Base\BodyClasses::add( 'disable_bottom_padding' );

cmls_get_template_part( 'archive', make_post_class(), [ 'term_children' => $term_children] );
