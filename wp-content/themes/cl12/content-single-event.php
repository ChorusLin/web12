<?php
/**
 * The template for displaying content in the single.php template
 *
 * @package WordPress
 * @subpackage Twenty_Eleven
 * @since Twenty Eleven 1.0
 */



function cl12_the_date( $meta_key ) {
	global $post;
	echo date( 'j/n', intval( get_post_meta( $post->ID, $meta_key, true ) ) );
}

function cl12_the_time( $meta_key ) {
	global $post;
	echo date( 'G:i', intval( get_post_meta( $post->ID, $meta_key, true ) ) );
}
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<h1 class="entry-title"><?php the_title(); ?></h1>
		<p>Time: <?php cl12_the_date('_start_timestamp'); ?> kl <?php cl12_the_time('_start_timestamp'); ?></p>
		<p>Location: <?php echo get_post_meta( $post->ID, '_location', true ) ?></p>

	</header><!-- .entry-header -->

	<div class="entry-content">
		<?php the_content(); ?>
		<?php wp_link_pages( array( 'before' => '<div class="page-link"><span>' . __( 'Pages:', 'twentyeleven' ) . '</span>', 'after' => '</div>' ) ); ?>
	</div><!-- .entry-content -->

</article><!-- #post-<?php the_ID(); ?> -->
