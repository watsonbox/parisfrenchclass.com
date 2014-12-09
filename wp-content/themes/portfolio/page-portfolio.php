<?php
/*
Template Name: Portfolio Page
*/
?>
<?php get_header(); ?>
        <h1><?php the_title(); ?></h1>
        <hr />
        <?php if(get_option('portfolio_category_id')) { $param = "&showposts=-1&cat=".get_option('portfolio_category_id'); }else{ $param = ""; } ?>
		<?php query_posts($param); ?>
		<?php $post_count = 1; ?>
		<ul class="portfolio clearfix">
			<?php while (have_posts()) : the_post(); ?>
				<?php if($post_count % 2) { $class = 'thumb-left'; }else{ $class='thumb-right'; } ?>
				<li class="<?php echo $class; ?>">
					<p class="thumbnail"><a href='<?php the_permalink() ?>' title="View Details for <?php the_title(); ?>"><?php my_attachment_image(0, 'medium', 'alt="' . $post->post_title . '"'); ?></a></p>
					<p class="details"><a href='<?php the_permalink() ?>' title="View Details for <?php the_title(); ?>"><?php the_title(); ?></a></p>
				</li>
				<?php $post_count++; ?>
			<?php endwhile; ?>
		</ul>
		
    <?php edit_post_link('Edit This', '<p><small>', '</small></p>'); ?>
<?php get_footer(); ?>
