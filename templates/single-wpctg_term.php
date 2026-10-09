<?php
/**
 * Single Glossary Term Template.
 *
 * This template can be overridden by copying it to yourtheme/wpcalibrate-term-glossary/single-wpctg_term.php
 * or yourtheme/single-wpctg_term.php.
 *
 * @package WPCalibrate\TermGlossary
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main wpctg-single-term-main">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'wpctg-single-term-entry' ); ?>>
			<header class="entry-header">
				<?php the_title( '<h1 class="entry-title wpctg-single-term-title">', '</h1>' ); ?>
			</header>

			<div class="entry-content wpctg-single-term-content">
				<?php
				the_content();

				wp_link_pages(
					[
						'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'wpcalibrate-term-glossary' ),
						'after'  => '</div>',
					]
				);
				?>
			</div>
		</article>
		<?php
	endwhile;
	?>
</main>

<?php
get_sidebar();
get_footer();
