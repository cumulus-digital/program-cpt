<?php
/**
 * Category archives to show posts by sub-category.
 */

namespace CUMULUS\Wordpress\ProgramCPT;

use function CMLS_Base\cmls_get_template_part;
use function CMLS_Base\get_tax_display_args;
use function CMLS_Base\make_post_class;

\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

$display_args = get_tax_display_args();

$tax_tag_query = array();
$tag_search    = array();
foreach( $args['tag_taxes'] as $tag_tax ) {
	if ( ! empty( $_GET[$tag_tax] ) ) {
		$tax_tag_query[] = array(
			'taxonomy' => $tag_tax,
			'field'    => 'slug',
			'terms'    => $_GET[$tag_tax],
		);
		$tag_search[] = \get_taxonomy( $tag_tax );
	}
}

if ( \count( $tag_search ) ) {
}

function get_term_posts( $term, $additional_tax_query ) {
	\wp_reset_postdata();
	$tax_query = \array_merge(
		array(
			'relation' => 'AND',
			array(
				'taxonomy'         => $term->taxonomy,
				'terms'            => $term->term_id,
				'include_children' => false,
			),
		),
		$additional_tax_query
	);
	$child_posts = new \WP_Query( array(
		'ignore_sticky_posts' => true,
		'posts_per_page'      => -1,
		'orderby'             => 'menu_order title',
		'order'               => 'ASC',
		'tax_query'           => $tax_query,
	) );
	\wp_reset_postdata();

	return $child_posts;
}
function resolve_terms( &$terms, $tax_tag_query ) {
	foreach( $terms as &$term ) {
		$term->child_posts = get_term_posts( $term, $tax_tag_query );
		global $wpdb;
		$term_children_ids = $wpdb->get_col( $wpdb->prepare( "SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy=%s AND parent=%d", $term->taxonomy, $term->term_id ) );
		if ( $term_children_ids ) {
			$term_children = get_terms( array(
				'taxonomy'   => $term->taxonomy,
				'include'    => $term_children_ids,
				'hide_empty' => true,
			) );
			$term->child_terms = $term_children;
			resolve_terms( $term->child_terms, $tax_tag_query );
		}
	}

	return $terms;
}

$term_with_posts = resolve_terms( $args['term_children'], $tax_tag_query );

function output_term_row( $term, $display_args, $level = 1 ) {
	if ( ! $term->child_posts->found_posts && ! $term->child_terms ) {
		return;
	}
	?>
		<div class="row level-<?php echo $level; ?>">
			<div class="row-container tax-child-header">
				<h<?php echo $level + 2; ?>>
					<a href="<?php echo \get_term_link( $term ); ?>" title="View all <?php echo \esc_attr( \wp_strip_all_tags( $term->name ) ); ?>">
						<?php echo \wp_kses_post( \apply_filters( 'single_term_title', $term->name ) ); ?>
					</a>
				</h<?php echo $level + 2; ?>>
				<?php $subtitle = get_field( 'field_6136452e5eecb', $term ); ?>
				<?php if ( $subtitle ): ?>
					<h<?php echo $level + 3; ?>>
						<?php echo \wp_kses_post( $subtitle ); ?>
					</h<?php echo $level + 3; ?>>
				<?php endif; ?>
				<?php if ( $term->description ): ?>
					<div class="tax-child-description">
						<?php echo \wp_kses_post( $term->description ); ?>
					</div>
				<?php endif; ?>
			</div>


			<?php if ( $term->child_posts->found_posts ):

				cmls_get_template_part(
					'templates/archives/post_list',
					make_post_class(),
					\array_merge(
						$display_args,
						array(
							'display_format'       => 'cards small',
							'the_posts'            => $term->child_posts,
							'row-class'            => 'tax-child small',
							'thumbnail_size'       => 'medium',
							'thumbnail_attributes' => array(
								'sizes' => '400px',
							),
						)
					)
				);

			endif; ?>

			<?php
			if ( $term->child_terms ) {
				foreach( $term->child_terms as $child_term ) {
					output_term_row( $child_term, $display_args, $level + 1 );
				}
			}
	?>
		</div>
	<?php
}

?>

<!-- Term children injected from CPT plugin -->
<div class="tax-children">

	<?php foreach( $term_with_posts as $term ): ?>

		<?php output_term_row( $term, $display_args ); ?>

	<?php endforeach; ?>

</div>