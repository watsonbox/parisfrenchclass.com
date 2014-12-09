<?php
/*
Template Name: Contact Page
*/
?>
<?php get_header(); ?>
	<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <div class="post" id="post-<?php the_ID(); ?>">
            <h1><?php the_title(); ?></h1>
            <hr />
            <div class="clearfix">
                <div class="column50">
                <?php mail_form(); ?>
                </div>
                <div class="column50">
                    <?php the_content(); ?>
                </div>
            </div>
        </div>
        <?php endwhile; endif; ?>
   <?php edit_post_link('Edit This', '<p><small>', '</small></p>'); ?>
<?php get_footer(); ?>
