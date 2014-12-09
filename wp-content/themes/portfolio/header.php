<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>

	<head profile="http://gmpg.org/xfn/11">
		
		<title>
			<?php if (is_home()) { echo bloginfo('name');
			} elseif (is_404()) {
			echo '404 Not Found';
			} elseif (is_category()) {
			echo 'Category:'; wp_title('');
			} elseif (is_search()) {
			echo 'Search Results';
			} elseif ( is_day() || is_month() || is_year() ) {
			echo 'Archives:'; wp_title('');
			} else {
			echo wp_title('');
			}
			?>
		</title>

	    <meta http-equiv="content-type" content="<?php bloginfo('html_type') ?>; charset=<?php bloginfo('charset') ?>" />
		<meta name="description" content="<?php bloginfo('description') ?>" />
		
		<?php if(is_search()) { ?><meta name="robots" content="noindex, nofollow" /> <?php }?>
	
		<link rel="stylesheet" type="text/css" href="<?php bloginfo('stylesheet_url'); ?>" media="screen" />
		<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> RSS Feed" href="<?php bloginfo('rss2_url'); ?>" />
		<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
		
		<script type="text/javascript" src="<?php bloginfo('template_directory'); ?>/js/general.js"></script>

		<?php wp_head(); ?>

	</head>

	<body>
    
    <div id="outer">
    	<div id="logo"><a href="<?php bloginfo('url'); ?>" title="<?php bloginfo('name'); ?>"><img src="<?php echo get_option('logo') ? get_option('logo') : bloginfo('template_directory') . "/images/logo.gif" ; ?>" alt="Logo for <?php bloginfo('name'); ?>" /></a></div>
		<?php if(is_home()) { ?><h2 class="intro"><?php echo get_option('tagline') ? get_option('tagline') : "Welcome to the online portfolio of Jane B Doe,<br />Ph.D. student, designer in Cityname, State."; ?></h2><?php } ?>
    	<?php if(is_home()) { ?> <div class="bracket"></div><?php } ?>
        <?php create_columns(); ?>
		<?php get_messages(); ?>
		
        <?php if(!is_home()) { ?><div class="contentWidth"><div class="education-expanded expanded clearfix"><?php } ?>