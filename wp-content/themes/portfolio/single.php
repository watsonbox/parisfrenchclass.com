<?php get_header(); ?>


<?php
	// remove sidebar / columns on portfolio single to allow the full size images
	$portfoliocat = get_option('portfolio_category_id');
	$category = get_the_category();
	$currentcat = $category[0]->cat_ID;
	if($currentcat == $portfoliocat)
	{
		$portfolio = true;
	}
?>

<h2><?php the_title(); ?></h2>
<hr />

<?php if(!$portfolio) { ?><div class="column-75-left"><?php } ?>
	<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

			<p class="post-details"><span><?php comments_popup_link('No Comments', '1 Comment', '% Comments'); ?></span>// Posted in <?php the_category(', ') ?> by <?php the_author_posts_link(); ?> on <?php the_time('m.d.y') ?>.</p>
			<?php the_content('<p>Read the rest of this entry &raquo;</p>'); ?>
			<?php wp_link_pages(array('before' => '<p><strong>Pages:</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>
			
			<?php if($portfolio) { ?><p class="pagenav"><?php previous_post_link('<span>%link</span>','Previous',TRUE) ?><?php next_post_link('<span>%link</span>','Next',TRUE) ?></p><?php } ?>
			
			<hr />
			<p>

				<?php if (('open' == $post-> comment_status) && ('open' == $post->ping_status)) {
					// Both Comments and Pings are open ?>
					You can <a href="#respond">leave a response</a>, or <a href="<?php trackback_url(); ?>" rel="trackback">trackback</a> from your own site.

				<?php } elseif (!('open' == $post-> comment_status) && ('open' == $post->ping_status)) {
					// Only Pings are Open ?>
					Responses are currently closed, but you can <a href="<?php trackback_url(); ?> " rel="trackback">trackback</a> from your own site.

				<?php } elseif (('open' == $post-> comment_status) && !('open' == $post->ping_status)) {
					// Comments are open, Pings are not ?>
					You can skip to the end and leave a response. Pinging is currently not allowed.

				<?php } elseif (!('open' == $post-> comment_status) && !('open' == $post->ping_status)) {
					// Neither Comments, nor Pings are open ?>
					Both comments and pings are currently closed.

				<?php } edit_post_link('Edit This', '<p><small>', '</small></p>'); ?>
			</p>
			
			
	<?php comments_template(); ?>

	<?php endwhile; else: ?>


		<p>Sorry, no posts matched your criteria.</p>

<?php endif; ?>

<?php if(!$portfolio) { ?>
	</div>
	<div class="column-25-right widgets">
		<?php get_sidebar(); ?>
	</div>
<?php } ?>

<?php get_footer(); ?>
