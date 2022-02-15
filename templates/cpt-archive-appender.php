<?php
/**
 * Append child categories and a CPT list to our custom category archives
 */

namespace CUMULUS\Wordpress\ProgramCPT;

// Exit if accessed directly.
\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

use function CMLS_Base\cmls_get_template_part;
use function CMLS_Base\get_tax_display_args;
use function CMLS_Base\make_post_class;
use function CMLS_Base\tax_query_includes_children;
use WP_Query;

\add_action( 'cmls_template-archive-after_content', function () {
	$taxes = \array_map(
		function ( $str ) {
			return $str . '-cat';
		},
		CPTs::getKeys()
	);

	if ( ! \is_tax( $taxes ) ) {
		return;
	}

	// Defaults
	$display_args = get_tax_display_args();

	// This term
	$this_term = \get_queried_object();
	$term_children = null;

	if ( \property_exists( $this_term, 'taxonomy' ) && \is_taxonomy_hierarchical( $this_term->taxonomy ) ) {
		// Necessary to go back to the DB because PublishPress Permissions may break the cache...
		//$term_children_ids = \get_term_children( $this_term->term_id, $this_term->taxonomy );
		global $wpdb;
		$term_children_ids = $wpdb->get_col( $wpdb->prepare( "SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy=%s AND parent=%d", $this_term->taxonomy, $this_term->term_id ) );

		if ( \is_array( $term_children_ids ) && \count( $term_children_ids ) ) {
			$term_children = \get_terms( [
				'taxonomy'   => $this_term->taxonomy,
				'include'    => $term_children_ids,
				'hide_empty' => true,
			] );
		}
	} ?>

	<?php if ( $term_children && ! tax_query_includes_children() ): ?>

		<!-- Term children injected from CPT plugin -->
		<div class="tax-children">

		<?php foreach ( $term_children as $child_term ): ?>

			<div class="row">
				<div class="row-container tax-child-header">
					<h3>
						<a href="<?php echo \get_term_link( $child_term ); ?>" title="View all <?php echo \esc_attr( \wp_strip_all_tags( $child_term->name ) ); ?>">
							<?php echo \wp_kses( $child_term->name, ['br'] ); ?>
						</a>
					</h3>
				</div>

<?php
	\wp_reset_postdata();

	$child_posts = new WP_Query( [
		'ignore_sticky_posts' => true,
		'posts_per_page'      => -1,
		'tax_query'           => [
			[
				'taxonomy' => $child_term->taxonomy,
				//'field'            => 'term_id',
				'terms'            => $child_term->term_id,
				'include_children' => false,
			],
		],
	] );

	cmls_get_template_part(
		'templates/archives/post_list',
		make_post_class(),
		\array_merge(
			$display_args,
			[
				'display_format' => 'cards small',
				'the_posts'      => $child_posts,
				'row-class'      => 'tax-child small',
			]
		)
	);
	\wp_reset_postdata(); ?>

			</div>

		<?php endforeach; ?>

		</div>

	<?php endif; ?>

	<?php
}, 99 );
