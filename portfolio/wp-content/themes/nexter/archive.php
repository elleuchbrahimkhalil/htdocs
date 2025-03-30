<?php
/**
 * The template for displaying archive pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Nexter
 * @since	1.0.0
 */

get_header(); ?>
<?php 	
	$get_sidebar = nexter_site_sidebar_layout();
	$content_column = 'nxt-col-md-12';
	
	if(!empty($get_sidebar) && ($get_sidebar['layout'] == 'left-sidebar' || $get_sidebar['layout'] == 'right-sidebar') ){
		$content_column = ' nxt-col-md-8 nxt-col-sm-12';		
	}
?>

<div id="primary" class="content-area">
	<main id="main" class="site-main">

	<?php if ( have_posts() ) :
		do_action('nexter_archive_content_part');
	else :
		get_template_part( 'template-parts/content', 'none' );
	endif;
	?>
	</main><!-- #main -->
</div><!-- #primary -->
<?php
get_footer();