<?php
/**
 * Prepend a list of tags used in this category
 */

namespace CUMULUS\Wordpress\ProgramCPT;

// Exit if accessed directly.
\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

\add_action( 'cmls_template-archive-after_header', function () {
	$taxes = \array_map(
		function ( $str ) {
			return $str . '-cat';
		},
		CPTs::getKeys()
	);

	if ( ! \is_tax( $taxes ) ) {
		return;
	}

	$current_term = \get_queried_object();
	$tax          = \get_taxonomy( $current_term->taxonomy );
	$tax_tags     = \CMLS_Base\get_category_tags( $current_term, $tax->object_type[0] . '-tag' );

	if ( \count( $tax_tags ) ) {
		?>

		<aside class="row tags">
			<div class="row-container">
				Tags:
				<?php echo \wp_generate_tag_cloud( $tax_tags, [
					'smallest' => 1,
					'largest'  => 1,
					'unit'     => 'em',
					'orderby'  => 'count',
					'order'    => 'DESC',
				] ); ?>
			</div>
		</aside>

		<?php
	}
}, 99 );
