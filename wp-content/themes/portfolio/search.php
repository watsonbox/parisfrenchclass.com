<?php get_header(); ?>
<h2>Search Results</h2>
		<hr />
<div class="column-75-left">
	<?php if (have_posts()) : ?>

		
		<?php global $more; $more = 0; ?>
			<?php while (have_posts()) : the_post(); ?>
				<div class="post">
					<h2 id="post-<?php the_ID(); ?>"><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
					<p class="post-details"><span><?php comments_popup_link('No Comments', '1 Comment', '% Comments'); ?></span>// Posted in <?php the_category(', ') ?> by <?php the_author_posts_link(); ?> on <?php the_time('m.d.y') ?>.</p>
					<?php the_excerpt(); ?>
					<hr />
				</div>
			<?php endwhile; ?>
			<p class="pagenav"><?php posts_nav_link('&nbsp;','View Newer Entries','View Older Entries'); ?></p>

	<?php else : ?>

		<h2>No posts found. Try a different search?</h2>
		<?php include (TEMPLATEPATH . '/searchform.php'); ?>

	<?php endif; ?>
</div>
<div class="column-25-right widgets">
	<?php get_sidebar(); ?>
</div>
<?php get_footer(); ?>