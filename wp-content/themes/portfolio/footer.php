<?php if(!is_home()) { ?></div></div><?php } ?>
<noscript><br /><br /><p class="noscript"><strong>You do not appear to have JavaScript enabled.</strong><br />Some advanced features of this site may not appear as intended.</p><br /><br /></noscript>
</div><!-- end outer -->
<div id="footer" class="clearfix">
	<div class="social-media">
		<?php if (get_option('social_url_twitter')) { ?><a href="<?php echo get_option('social_url_twitter') ? get_option('social_url_twitter') : "#"; ?>"><img src="<?php echo bloginfo('template_directory')."/images/sm-icons/twitter.gif"; ?>" alt="Follow me on Twitter!" /></a><?php } ?>
		<?php if (get_option('social_url_delicious')) { ?><a href="<?php echo get_option('social_url_delicious') ? get_option('social_url_delicious') : "#"; ?>"><img src="<?php echo bloginfo('template_directory')."/images/sm-icons/delicious.gif"; ?>" alt="delicious" /></a><?php } ?>
		<?php if (get_option('social_url_digg')) { ?><a href="<?php echo get_option('social_url_digg') ? get_option('social_url_digg') : "#"; ?>"><img src="<?php echo bloginfo('template_directory')."/images/sm-icons/digg.gif"; ?>" alt="Digg" /></a><?php } ?>
		<?php if (get_option('social_url_facebook')) { ?><a href="<?php echo get_option('social_url_facebook') ? get_option('social_url_facebook') : "#"; ?>"><img src="<?php echo bloginfo('template_directory')."/images/sm-icons/facebook.gif"; ?>" alt="Facebook" /></a><?php } ?>
		<?php if (get_option('social_url_flickr')) { ?><a href="<?php echo get_option('social_url_flickr') ? get_option('social_url_flickr') : "#"; ?>"><img src="<?php echo bloginfo('template_directory')."/images/sm-icons/flickr.gif"; ?>" alt="Flickr" /></a><?php } ?>
		<?php if (get_option('social_url_linkedin')) { ?><a href="<?php echo get_option('social_url_linkedin') ? get_option('social_url_linkedin') : "#"; ?>"><img src="<?php echo bloginfo('template_directory')."/images/sm-icons/linkedin.gif"; ?>" alt="Linkedin" /></a><?php } ?>
		<?php if (get_option('social_url_reddit')) { ?><a href="<?php echo get_option('social_url_reddit') ? get_option('social_url_reddit') : "#"; ?>"><img src="<?php echo bloginfo('template_directory')."/images/sm-icons/reddit.gif"; ?>" alt="Reddit" /></a><?php } ?>
		<?php if (get_option('social_url_youtube')) { ?><a href="<?php echo get_option('social_url_youtube') ? get_option('social_url_youtube') : "#"; ?>"><img src="<?php echo bloginfo('template_directory')."/images/sm-icons/youtube.gif"; ?>" alt="Youtube" /></a><?php } ?>
		
		<?php if (get_option('social_url_extra01') && get_option('social_icon_extra01')) { ?><a href="<?php echo get_option('social_url_extra01') ? get_option('social_url_extra01') : "#"; ?>"><img src="<?php echo get_option('social_icon_extra01'); ?>" alt="Social Media Link" /></a><?php } ?>
		<?php if (get_option('social_url_extra02') && get_option('social_icon_extra02')) { ?><a href="<?php echo get_option('social_url_extra02') ? get_option('social_url_extra02') : "#"; ?>"><img src="<?php echo bloginfo('social_icon_extra02'); ?>" alt="Social Media Link" /></a><?php } ?>
		<?php if (get_option('social_url_extra03') && get_option('social_icon_extra03')) { ?><a href="<?php echo get_option('social_url_extra03') ? get_option('social_url_extra03') : "#"; ?>"><img src="<?php echo bloginfo('social_icon_extra03'); ?>" alt="Social Media Link" /></a><?php } ?>
	</div>
	<p><?php echo get_option('footer_text') ? get_option('footer_text') : "&copy; 2009 Wordpress Smart Portfolio - Created by <a href='http://www.curtziegler.com/' title='Curt Ziegler Web Design'>Curt Ziegler</a> for <a href='http://www.themeforest.net/user/cudazi/portfolio/?ref=cudazi'>ThemeForest.Net</a>" ?></p>

</div>
<?php wp_footer(); ?>
</body>
</html>