<?php get_header(); ?>

		<?php if (have_posts()) : ?>

		<?php $post = $posts[0]; // Hack. Set $post so that the_date() works. ?>
		<?php /* If this is a category archive */ if (is_category()) { ?>
		<h2>Archive for the &#8216;<?php single_cat_title(); ?>&#8217; Category</h2>
		<?php /* If this is a tag archive */ } elseif( is_tag() ) { ?>
		<h2>Posts Tagged &#8216;<?php single_tag_title(); ?>&#8217;</h2>
		<?php /* If this is a daily archive */ } elseif (is_day()) { ?>
		<h2>Archive for <?php the_time('F jS, Y'); ?></h2>
		<?php /* If this is a monthly archive */ } elseif (is_month()) { ?>
		<h2>Archive for <?php the_time('F, Y'); ?></h2>
		<?php /* If this is a yearly archive */ } elseif (is_year()) { ?>
		<h2>Archive for <?php the_time('Y'); ?></h2>
		<?php /* If this is an author archive */ } elseif (is_author()) { ?>
		<h2>Author Archive</h2>
		<?php /* If this is a paged archive */ } elseif (isset($_GET['paged']) && !empty($_GET['paged'])) { ?>
		<h2>Blog Archives</h2>
		<?php } ?>
		<hr />
		<div class="column-75-left">
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
	<div class="column-75-left">
		<h2>Not Found</h2>
		<?php include (TEMPLATEPATH . '/searchform.php'); ?>

	<?php endif; ?>
</div>
<div class="column-25-right widgets">
	<?php get_sidebar(); ?>
</div>
<?php get_footer(); ?>
