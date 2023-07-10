<?php
/**
 * Category archives to show posts by sub-category.
 */

namespace CUMULUS\Wordpress\ProgramCPT;

\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

$tax_tags = $args['tax_tags'];
$base_url = $args['base_url'];
?>
<aside class="row tags">
	<div class="row-container">
		Tags:
		<ul>
			<?php foreach ( $tax_tags as $tag ): ?>
				<li>
					<a
						href="<?php echo \esc_url( "{$base_url}?{$tag->taxonomy}={$tag->slug}" ); ?>"
					>
						<?php echo \esc_html( $tag->name ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</aside>