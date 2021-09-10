<?php
/**
 * CPT Archive page, should only show the base category terms
 */

namespace CUMULUS\Wordpress\ProgramCPT;

use function CMLS_Base\cmls_get_template_part;

$this_term = \get_queried_object();

if ( \is_a( $this_term, 'WP_Post_Type' ) ) {
	$term_children_ids = \get_term_children( 0, PREFIX . '-cat' );
	$term_children     = \get_terms( [
		'taxonomy'   => PREFIX . '-cat',
		'include'    => $term_children_ids,
		'parent'     => 0,
		'hide_empty' => true,
	] );
	global $wp_query;
	$wp_query->posts      = null;
	$wp_query->post_count = 0;
}

\CMLS_Base\BodyClasses::add( 'disable_bottom_padding' );

cmls_get_template_part( 'archive', null, [ 'term_children' => $term_children] );
