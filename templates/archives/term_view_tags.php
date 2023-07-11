<?php
/**
 * Displays currently filtered tags in the archive header.
 */

namespace CUMULUS\Wordpress\ProgramCPT;

\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

$tags_searched = $args['tags_searched'];
?>
<aside class="row row-container tags">
	Viewing tag:
	<ul>
	<?php foreach( $tags_searched as $t ): ?>
		<li>
			<a href="<?php
				echo \esc_attr( \esc_url( \remove_query_arg( $t->taxonomy ) ) );
		?>">
				<?php echo \wp_kses_post( $t->name ); ?>
				<span>
				✕
				</span>
			</a>
		</li>
	<?php endforeach; ?>
	</ul>
</aside>