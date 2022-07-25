<?php
/**
 * The template used for displaying slider
 *
 * @package PhotoFocus
 */

$quantity     = get_theme_mod( 'photofocus_slider_number', 4 );
$no_of_post   = 0; // for number of posts
$post_list    = array(); // list of valid post/page ids

$args = array(
	'ignore_sticky_posts' => 1, // ignore sticky posts
);
//Get valid number of posts

for ( $i = 1; $i <= $quantity; $i++ ) {
	$photofocus_post_id = '';

	$photofocus_post_id = get_theme_mod( 'photofocus_slider_page_' . $i );
	

	if ( $photofocus_post_id && '' !== $photofocus_post_id ) {
		$post_list = array_merge( $post_list, array( $photofocus_post_id ) );

		$no_of_post++;
	}
}

$args['post__in'] = $post_list;
$args['post_type'] = 'page';
$args['orderby'] = 'post__in';

if ( ! $no_of_post ) {
	return;
}

$args['posts_per_page'] = $no_of_post;

$loop = new WP_Query( $args );
while ( $loop->have_posts() ) :
	$loop->the_post();

	$classes = 'post post-' . get_the_ID() . ' hentry slides';
	
	$thumbnail = 'post-thumbnail';


	?>
	<article class="<?php echo esc_attr( $classes ); ?>">
		<div class="hentry-inner">
			<?php photofocus_post_thumbnail( $thumbnail, 'html', true, true ); ?>
			
			<div class="entry-container">
				<header class="entry-header">
					<h2 class="entry-title">
						<a title="<?php the_title_attribute(); ?>" href="<?php the_permalink(); ?>">
							<?php the_title(); ?>
						</a>
					</h2>
				</header> 
			</div><!-- .entry-container -->
		</div><!-- .hentry-inner -->
	</article><!-- .slides -->
<?php
endwhile;

wp_reset_postdata();
