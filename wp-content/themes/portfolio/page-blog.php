<?php
/*
Template Name: Blog
*/
?>
<?php get_header(); ?>
        
		<h1><?php the_title(); ?></h1>
        <hr />
		<div class="column-75-left">
			<?php if(get_option('portfolio_category_id')) { $param = "cat=-".get_option('portfolio_category_id'); }else{ $param = ""; } ?>
			<?php query_posts($param); ?>
		
			<?php global $more; $more = 0; ?>
			<?php while (have_posts()) : the_post(); ?>
				<div class="post">
					<h2 id="post-<?php the_ID(); ?>"><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
					<p class="post-details"><span><?php comments_popup_link('No Comments', '1 Comment', '% Comments'); ?></span>// Posted in <?php the_category(', ') ?> by <?php the_author_posts_link(); ?> on <?php the_time('m.d.y') ?>.</p>
					<?php the_content("<p>Continue reading " . the_title('', '', false) . "</p>"); ?>
					<?php edit_post_link('Edit This', '<p><small>', '</small></p>'); ?>
					<hr />
				</div>
				
			<?php endwhile; ?>
			<p class="pagenav"><?php posts_nav_link('&nbsp;','View Newer Entries','View Older Entries'); ?></p>
			
		</div>
		<div class="column-25-right widgets">
			<?php get_sidebar(); ?>
		</div>
<?php get_footer(); ?>