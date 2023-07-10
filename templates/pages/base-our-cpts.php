<?php
/**
 * Program page template
 */

namespace CUMULUS\Wordpress\ProgramCPT;

use function CMLS_Base\cmls_get_template_part;

\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

$req = \get_queried_object();

$display_args = (array) \get_field( 'field_612d7a1f9df36' );
$show_header  = \get_field( 'field_612ec3547ecb5' );
$categories   = \get_the_terms( \get_the_ID(), $req->post_type . '-cat' );
$tags         = \get_the_terms( \get_the_ID(), $req->post_type . '-tag' );
$post_class   = [
	$cpt,
	'cmls-program-cpt',
	( $show_header ) ? 'display-header' : 'hide-header',
	( $display_args['background-color'] || $display_args['background-image'] )
		? 'has-header-background' : 'no-header-background',
	\has_post_thumbnail() ? 'has-featured-image' : 'no-featured-image',
];
?>

<!-- Template from CPT plugin -->
<article
	id="post-<?php \the_ID(); ?>"
	<?php \post_class( $post_class ); ?>
>
	<?php if ( $show_header ): ?>
	<style>
		<?php if ( $display_args ): ?>
		article#post-<?php \the_ID(); ?> {
			--progam-header-background-color: <?php echo $display_args['background-color']; ?>;

			<?php if ( \array_key_exists( 'background_image', $display_args ) && \array_key_exists( 'url', $display_args['background-image'] ) ): ?>
				--progam-header-background-image: url('<?php echo $display_args['background-image']['url']; ?>');
			<?php endif; ?>

			--progam-header-background-position: <?php echo $display_args['background-position']; ?>;
			--progam-header-background-repeat: <?php echo $display_args['background-repeat']; ?>;
			--progam-header-background-size: <?php echo $display_args['background-size']; ?>;
			--progam-header-title-color: <?php echo $display_args['title-color']; ?>;
			--progam-header-title-shadow-opacity: <?php echo $display_args['title-shadow-opacity']; ?>;
		}
		<?php endif; ?>
	</style>
	<header class="full-width">
		<div class="row-container">

			<?php cmls_get_template_part( 'templates/pages/featured_image', null, ['force_featured_image' => true, 'disable_lazyload' => true] ); ?>

			<div class="title">

				<?php if ( $categories ): ?>
					<div class="categories">
					<?php foreach ( $categories as $category ): ?>
						<div class="category">
							<?php
echo \untrailingslashit( \get_term_parents_list(
	$category->term_id,
	$category->taxonomy,
	[
		'inclusive' => true,
		'separator' => null,
	]
) );
						?>
						</div>
					<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<h1><?php \the_title(); ?></h1>
			</div>

		</div>
	</header>
	<?php endif; ?>

	<?php cmls_get_template_part( 'templates/pages/body' ); ?>

	<?php if ( $tags ): ?>
		<aside class="tags">
			<h5>Tags:</h5>
			<?php
				\the_terms(
					\get_the_ID(),
					$req->post_type . '-tag',
					null,
					null,
					null
				);
		?>
		</aside>
	<?php endif; ?>

</article>
