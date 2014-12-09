<?php
// Theme Name: Smart Portfolio
add_action('admin_menu', 'portfolio_settings'); // Theme Menu "Brightness Settings"
add_action('admin_head', 'portfolio_styles'); // CSS For "Brightness Settings" Page


if ( function_exists('register_sidebar') )
    register_sidebar(array(
        'before_widget' => '<li>',
        'after_widget' => '</li>',
        'before_title' => '<h3 class="widgettitle">',
        'after_title' => '</h3>',
    ));
	
	// create column for menu
	function create_individual_column($id,$count)
	{
		echo "<div class='column'>";
		
		//
		$heading_image = "";
		if(get_option("home_column0".$id."_icon_url"))
		{
			$heading_image = get_option("home_column0".$id."_icon_url");
		}else{
			if(get_option("home_column0".$id."_icon"))
			{
				$heading_image = get_option("home_column0".$id."_icon");
			}
		}
		if($heading_image != "")
		{
			$heading_image = get_bloginfo('template_directory') . "/images/headings/" . $heading_image;
		}else{
			$heading_image = get_bloginfo('template_directory') . "/images/headings/notepad.gif"; // default
		}
		//
		
		
		$column_url = "";
		if(get_option("home_column0". $id ."_url_page_id"))
		{
			$column_url = get_page_link(get_option("home_column0". $id ."_url_page_id"));
		}else{
			$column_url = get_option("home_column0".$id."_url");
		}
		echo "<h3><a href='".$column_url."' style='background:url(" . $heading_image . ") no-repeat;'>" . get_option("home_column0".$id."_title") . "</a></h3>";
		
		
		if (is_home()) 
		{
			echo "<p>" . get_option("home_column0".$id."_summary") . "</p>";
		}
		
		echo "</div><!-- end column -->";
	}
	
	// create columns for the main menu based on the admin settings add-on screen
	function create_columns()
	{
		// get total active columns
		$count = 0;
		if(get_option('home_column01_active')) { $count++; }
		if(get_option('home_column02_active')) { $count++; }
		if(get_option('home_column03_active')) { $count++; }
		if(get_option('home_column04_active')) { $count++; }
		
		echo "<div class='clearfix columns columns-total-".$count."'>";
	
		if(get_option('home_column01_active')) { create_individual_column('1',$count); }
		if(get_option('home_column02_active')) { create_individual_column('2',$count); }
		if(get_option('home_column03_active')) { create_individual_column('3',$count); }
		if(get_option('home_column04_active')) { create_individual_column('4',$count); }
		
		echo "</div><!-- end column container -->";
	}
	
	
	
	
	
	
	
	
function my_attachment_image($postid=0, $size='thumbnail', $attributes='') {
	$count = 0;
	if ($postid < 1 ) $postid = get_the_ID();
	if ($images = get_children(array(
		'post_parent' => $postid,
		'post_type' => 'attachment',
		'numberposts' => 1,
		'post_mime_type' => 'image',)))
		foreach($images as $image) {
			$attachment=wp_get_attachment_image_src($image->ID, $size);
				echo "<img src='" . $attachment[0] . "'" . $attributes . " class='attachment-image' />";
				$count++;
		}
		if($count == 0)
		{
			echo "<p>No image uploaded - image required in the portfolio category...</p>";
		}
}

	

	
function portfolio_settings_form(){ 
    if(isset($_POST['submit-updates']) && $_POST['submit-updates'] == "yes"){
		
		$tagline = stripslashes($_POST['tagline']);
		$logo = stripslashes($_POST['logo']);
		
		$social_url_delicious = stripslashes($_POST['social_url_delicious']);
		$social_url_twitter = stripslashes($_POST['social_url_twitter']);
		$social_url_digg = stripslashes($_POST['social_url_digg']);
		$social_url_facebook = stripslashes($_POST['social_url_facebook']);
		$social_url_flickr = stripslashes($_POST['social_url_flickr']);
		$social_url_linkedin = stripslashes($_POST['social_url_linkedin']);
		$social_url_reddit = stripslashes($_POST['social_url_reddit']);
		$social_url_youtube = stripslashes($_POST['social_url_youtube']);
		$social_url_extra01 = stripslashes($_POST['social_url_extra01']);
		$social_icon_extra01 = stripslashes($_POST['social_icon_extra01']);
		$social_url_extra02 = stripslashes($_POST['social_url_extra02']);
		$social_icon_extra02 = stripslashes($_POST['social_icon_extra02']);
		$social_url_extra03 = stripslashes($_POST['social_url_extra03']);
		$social_icon_extra03 = stripslashes($_POST['social_icon_extra03']);
		
		$home_column01_title = stripslashes($_POST['home_column01_title']);
		$home_column01_summary = stripslashes($_POST['home_column01_summary']);
		$home_column01_icon = stripslashes($_POST['home_column01_icon']);
		$home_column01_icon_url = stripslashes($_POST['home_column01_icon_url']);
		$home_column01_url = stripslashes($_POST['home_column01_url']);
		$home_column01_active = stripslashes($_POST['home_column01_active']);
		$home_column02_title = stripslashes($_POST['home_column02_title']);
		$home_column02_summary = stripslashes($_POST['home_column02_summary']);
		$home_column02_icon = stripslashes($_POST['home_column02_icon']);
		$home_column02_icon_url = stripslashes($_POST['home_column02_icon_url']);
		$home_column02_url = stripslashes($_POST['home_column02_url']);
		$home_column02_active = stripslashes($_POST['home_column02_active']);
		$home_column03_title = stripslashes($_POST['home_column03_title']);
		$home_column03_summary = stripslashes($_POST['home_column03_summary']);
		$home_column03_icon = stripslashes($_POST['home_column03_icon']);
		$home_column03_icon_url = stripslashes($_POST['home_column03_icon_url']);
		$home_column03_url = stripslashes($_POST['home_column03_url']);
		$home_column03_active = stripslashes($_POST['home_column03_active']);
		$home_column04_title = stripslashes($_POST['home_column04_title']);
		$home_column04_summary = stripslashes($_POST['home_column04_summary']);
		$home_column04_icon = stripslashes($_POST['home_column04_icon']);
		$home_column04_icon_url = stripslashes($_POST['home_column04_icon_url']);
		$home_column04_url = stripslashes($_POST['home_column04_url']);
		$home_column04_active = stripslashes($_POST['home_column04_active']);
		
		$home_column01_url_page_id = stripslashes($_POST['home_column01_url_page_id']);
		$home_column02_url_page_id = stripslashes($_POST['home_column02_url_page_id']);
		$home_column03_url_page_id = stripslashes($_POST['home_column03_url_page_id']);
		$home_column04_url_page_id = stripslashes($_POST['home_column04_url_page_id']);
		
		
		$cf_email = stripslashes($_POST['cf_email']);
		$portfolio_category_id = stripslashes($_POST['portfolio_category_id']);
		
		
		$footer_text = stripslashes($_POST['footer_text']);
		//

		update_option("tagline", $tagline);
		update_option("logo", $logo);
		
		update_option("social_url_delicious", $social_url_delicious);
		update_option("social_url_twitter", $social_url_twitter);
		update_option("social_url_digg", $social_url_digg);
		update_option("social_url_facebook", $social_url_facebook);
		update_option("social_url_flickr", $social_url_flickr);
		update_option("social_url_linkedin", $social_url_linkedin);
		update_option("social_url_reddit", $social_url_reddit);
		update_option("social_url_youtube", $social_url_youtube);
		update_option("social_url_extra01", $social_url_extra01);
		update_option("social_icon_extra01", $social_icon_extra01);
		update_option("social_url_extra02", $social_url_extra02);
		update_option("social_icon_extra02", $social_icon_extra02);
		update_option("social_url_extra03", $social_url_extra03);
		update_option("social_icon_extra03", $social_icon_extra03);
		
		update_option("home_column01_title", $home_column01_title);
		update_option("home_column01_summary", $home_column01_summary);
		update_option("home_column01_icon", $home_column01_icon);
		update_option("home_column01_icon_url", $home_column01_icon_url);
		update_option("home_column01_url", $home_column01_url);
		update_option("home_column01_active", $home_column01_active);
		update_option("home_column02_title", $home_column02_title);
		update_option("home_column02_summary", $home_column02_summary);
		update_option("home_column02_icon", $home_column02_icon);
		update_option("home_column02_icon_url", $home_column02_icon_url);
		update_option("home_column02_url", $home_column02_url);
		update_option("home_column02_active", $home_column02_active);
		update_option("home_column03_title", $home_column03_title);
		update_option("home_column03_summary", $home_column03_summary);
		update_option("home_column03_icon", $home_column03_icon);
		update_option("home_column03_icon_url", $home_column03_icon_url);
		update_option("home_column03_url", $home_column03_url);
		update_option("home_column03_active", $home_column03_active);
		update_option("home_column04_title", $home_column04_title);
		update_option("home_column04_summary", $home_column04_summary);
		update_option("home_column04_icon", $home_column04_icon);
		update_option("home_column04_icon_url", $home_column04_icon_url);
		update_option("home_column04_url", $home_column04_url);
		update_option("home_column04_active", $home_column04_active);
		
		update_option("home_column01_url_page_id", $home_column01_url_page_id);
		update_option("home_column02_url_page_id", $home_column02_url_page_id);
		update_option("home_column03_url_page_id", $home_column03_url_page_id);
		update_option("home_column04_url_page_id", $home_column04_url_page_id);
		
		
		
		
		update_option("cf_email", $cf_email);
		update_option("portfolio_category_id", $portfolio_category_id);
		
		update_option("footer_text", $footer_text);
		//update_option("", $);
		//

		
		
		// display confirmation message 
        echo "<div id=\"message\" class=\"updated fade\"><p><strong>Saved Settings!</strong></p></div>";
    }
	
	function category_ddl()
	{
		$categories=  get_categories('hierarchical=0&hide_empty=0'); 
		foreach ($categories as $cat) {
			$selected = "";
			if(get_option('portfolio_category_id') == $cat->cat_ID) { $selected = " selected='selected' "; }
			$option = "<option value='".$cat->cat_ID."' ".$selected.">";
			$option .= $cat->cat_name;
			$option .= ' ('.$cat->cat_ID.')';
			$option .= '</option>';
			echo $option;
		}
	}
	
	function page_ddl($column_number)
	{
	  $pages = get_pages(); 
	  foreach ($pages as $pagg) {
	  $selected = '';
	  if(get_option("home_column0".$column_number."_url_page_id") == $pagg->ID) { $selected = " selected='selected' "; }
		  $option = "<option value=\"".$pagg->ID."\" ".$selected.">";
			$option .= $pagg->post_title . " (id:" . $pagg->ID . ")";
			$option .= '</option>';
		echo $option;
	  }
	}
	
	function list_thumbnails($id)
	{
		echo "<option value=''>None</option>";
		if ($handle = opendir(TEMPLATEPATH . "/images/headings")) {
			while (false !== ($file = readdir($handle))) {
				if (preg_match("/^.*\.(jpg|jpeg|png|gif)$/i", $file)) {
					if(get_option($id) == $file)
					{
						echo "<option selected='selected'>";
					}else{
						echo "<option>";
					}
					echo "$file</option>";
				}
			}
			closedir($handle);
		}
	}
	
	
	
?>

<div class="wrap">
	<form method="post" name="brightness" target="_self" class="adminoptions">
		<h1>Theme Settings</h1>
		<p>Leave any option blank and hit save to revert back to the defaults.</p>
		<input type="submit" name="Submit" value="Save Settings" />
		<h2>General</h2>
		<div class="field"><label>Tagline:</label><small>The tagline appears under the logo. Use &lt;br /&gt; tags for line breaks.</small><textarea cols="2" rows="2" class="textarea-large" name="tagline"><?php echo get_option('tagline'); ?></textarea></div>
		<div class="field"><label>Logo URL:</label><small>Use this to override the theme default.</small><input class="textbox-medium" type="text" name="logo" value="<?php echo get_option('logo'); ?>" /></div>
		<div class="field"><label>Footer Text:</label><textarea cols="2" rows="2" class="textarea-small" name="footer_text"><?php echo get_option('footer_text'); ?></textarea></div>
		
		<h2>Portfolio Page</h2>
		<p>After creating a <strong>new page</strong> using the Portfolio template, please assign the post category you want to display on this page.</p>
		<p>The blog template will then display all posts except this below.</p>
		<div class="field"><label>Portfolio Category:</label><select name="portfolio_category_id"><?php category_ddl(); ?></select></div>
		
		<h2>Contact Form</h2>
		<div class="field"><label>Deliver email to:</label><small>youremail@domain.com<br />Please update this before testing the contact form!</small><input class="textbox-medium" type="text" name="cf_email" value="<?php echo get_option('cf_email'); ?>" /></div>
		
		<h2>Home Page Columns</h2>
		<p>You can have 1-4 Columns on the home page, it will calculate them based on your settings below.</p>
		<p>Mark the <strong>set active</strong> box to show column on the home page.</p>
		<div class="inset">
			<p><strong>Column 01</strong></p>
			<div class="field"><label>Title:</label><input name="home_column01_title" class="textbox-medium" type="text" value="<?php echo get_option('home_column01_title'); ?>" /></div>
			<div class="field"><label>Summary</label><textarea name="home_column01_summary" class="textarea-medium"><?php echo get_option('home_column01_summary'); ?></textarea></div>
			<div class="field"><label>Icon</label><small>Select an icon from the list or enter your own below.<br />You may add/edit the images in this theme's images folder:<br />wp-content/themes/{THIS THEME FOLDER}/images/<strong>headings</strong></small><select name="home_column01_icon"><?php list_thumbnails('home_column01_icon'); ?></select></div>
			<div class="field"><label>Alternate Icon:</label><small>Using this <strong>will override</strong> the setting above.</small><input name="home_column01_icon_url" class="textbox-medium" type="text" value="<?php echo get_option('home_column01_icon_url'); ?>" /></div>
			<div class="field"><label>Page URL</label><small>This can be internal, external, etc...</small><input name="home_column01_url" class="textbox-medium" type="text" value="<?php echo get_option('home_column01_url'); ?>" /></div>
            <div class="field"><label><em>OR</em> use an exising page:</div><small>This <strong>will override</strong> the URL above if used.</small><select name="home_column01_url_page_id"><option value="">None - Use URL Above</option><?php page_ddl(1); ?></select></label>
			<div class="field"><label><input type="checkbox" <?php if(get_option('home_column01_active')) { echo "checked='checked'"; } ?> name="home_column01_active" /> Set Active</label><small>Check the box to show on the home page.</small></div>
		</div>
		
		<div class="inset">
			<p><strong>Column 02</strong></p>
			<div class="field"><label>Title:</label><input name="home_column02_title" class="textbox-medium" type="text" value="<?php echo get_option('home_column02_title'); ?>" /></div>
			<div class="field"><label>Summary</label><textarea name="home_column02_summary" class="textarea-medium"><?php echo get_option('home_column02_summary'); ?></textarea></div>
			<div class="field"><label>Icon</label><small>Select an icon from the list or enter your own below.<br />You may add/edit the images in this theme's images folder:<br />wp-content/themes/{THIS THEME FOLDER}/images/<strong>headings</strong></small><select name="home_column02_icon"><?php list_thumbnails('home_column02_icon'); ?></select></div>
			<div class="field"><label>Alternate Icon:</label><small>Using this <strong>will override</strong> the setting above.</small><input name="home_column02_icon_url" class="textbox-medium" type="text" value="<?php echo get_option('home_column02_icon_url'); ?>" /></div>
			<div class="field"><label>Page URL</label><small>This can be internal, external, etc...</small><input name="home_column02_url" class="textbox-medium" type="text" value="<?php echo get_option('home_column02_url'); ?>" /></div>
			<div class="field"><label><em>OR</em> use an exising page:</div><small>This <strong>will override</strong> the URL above if used.</small><select name="home_column02_url_page_id"><option value="">None - Use URL Above</option><?php page_ddl(2); ?></select></label>
            <div class="field"><label><input type="checkbox" <?php if(get_option('home_column02_active')) { echo "checked='checked'"; } ?> name="home_column02_active" /> Set Active</label><small>Check the box to show on the home page.</small></div>
		</div>
		
		<div class="inset">
			<p><strong>Column 03</strong></p>
			<div class="field"><label>Title:</label><input name="home_column03_title" class="textbox-medium" type="text" value="<?php echo get_option('home_column03_title'); ?>" /></div>
			<div class="field"><label>Summary</label><textarea name="home_column03_summary" class="textarea-medium"><?php echo get_option('home_column03_summary'); ?></textarea></div>
			<div class="field"><label>Icon</label><small>Select an icon from the list or enter your own below.<br />You may add/edit the images in this theme's images folder:<br />wp-content/themes/{THIS THEME FOLDER}/images/<strong>headings</strong></small><select name="home_column03_icon"><?php list_thumbnails('home_column03_icon'); ?></select></div>
			<div class="field"><label>Alternate Icon:</label><small>Using this <strong>will override</strong> the setting above.</small><input name="home_column03_icon_url" class="textbox-medium" type="text" value="<?php echo get_option('home_column03_icon_url'); ?>" /></div>
			<div class="field"><label>Page URL</label><small>This can be internal, external, etc...</small><input name="home_column03_url" class="textbox-medium" type="text" value="<?php echo get_option('home_column03_url'); ?>" /></div>
			<div class="field"><label><em>OR</em> use an exising page:</div><small>This <strong>will override</strong> the URL above if used.</small><select name="home_column03_url_page_id"><option value="">None - Use URL Above</option><?php page_ddl(3); ?></select></label>
            <div class="field"><label><input type="checkbox" <?php if(get_option('home_column03_active')) { echo "checked='checked'"; } ?> name="home_column03_active" /> Set Active</label><small>Check the box to show on the home page.</small></div>
		</div>
		
		<div class="inset">
			<p><strong>Column 04</strong></p>
			<div class="field"><label>Title:</label><input name="home_column04_title" class="textbox-medium" type="text" value="<?php echo get_option('home_column04_title'); ?>" /></div>
			<div class="field"><label>Summary</label><textarea name="home_column04_summary" class="textarea-medium"><?php echo get_option('home_column04_summary'); ?></textarea></div>
			<div class="field"><label>Icon</label><small>Select an icon from the list or enter your own below.<br />You may add/edit the images in this theme's images folder:<br />wp-content/themes/{THIS THEME FOLDER}/images/<strong>headings</strong></small><select name="home_column04_icon"><?php list_thumbnails('home_column04_icon'); ?></select></div>
			<div class="field"><label>Alternate Icon:</label><small>Using this <strong>will override</strong> the setting above.</small><input name="home_column04_icon_url" class="textbox-medium" type="text" value="<?php echo get_option('home_column04_icon_url'); ?>" /></div>
			<div class="field"><label>Page URL</label><small>This can be internal, external, etc...</small><input name="home_column04_url" class="textbox-medium" type="text" value="<?php echo get_option('home_column04_url'); ?>" /></div>
			<div class="field"><label><em>OR</em> use an exising page:</div><small>This <strong>will override</strong> the URL above if used.</small><select name="home_column04_url_page_id"><option value="">None - Use URL Above</option><?php page_ddl(4); ?></select></label>
            <div class="field"><label><input type="checkbox" <?php if(get_option('home_column04_active')) { echo "checked='checked'"; } ?> name="home_column04_active" /> Set Active</label><small>Check the box to show on the home page.</small></div>
		</div>
		
		
		<h2>Social Media Footer Links</h2>
		<p>Leave URL empty to hide link in footer.<br />Please use the FULL URL: http://www.twitter.com/yourname</small>
		<div class="field"><label>Twitter</label><input class="textbox-medium" type="text" name="social_url_twitter" value="<?php echo get_option('social_url_twitter'); ?>" /></div>
		<div class="field"><label>del.icio.us</label><input class="textbox-medium" type="text" name="social_url_delicious" value="<?php echo get_option('social_url_delicious'); ?>" /></div>
		<div class="field"><label>Digg</label><input class="textbox-medium" type="text" name="social_url_digg" value="<?php echo get_option('social_url_digg'); ?>" /></div>
		<div class="field"><label>Facebook</label><input class="textbox-medium" type="text" name="social_url_facebook" value="<?php echo get_option('social_url_facebook'); ?>" /></div>
		<div class="field"><label>Flickr</label><input class="textbox-medium" type="text" name="social_url_flickr" value="<?php echo get_option('social_url_flickr'); ?>" /></div>
		<div class="field"><label>Linkedin</label><input class="textbox-medium" type="text" name="social_url_linkedin" value="<?php echo get_option('social_url_linkedin'); ?>" /></div>
		<div class="field"><label>Reddit</label><input class="textbox-medium" type="text" name="social_url_reddit" value="<?php echo get_option('social_url_reddit'); ?>" /></div>
		<div class="field"><label>Youtube</label><input class="textbox-medium" type="text" name="social_url_youtube" value="<?php echo get_option('social_url_youtube'); ?>" /></div>
		
		<p><strong>Extra Icons</strong><small>If URL and LOGO URL are filled out, they will appear in the website footer.</small></p>
		<div class="field">
			Extra 01 URL: <input class="textbox-medium" type="text" name="social_url_extra01" value="<?php echo get_option('social_url_extra01'); ?>" />
			Extra 01 ICON URL: <input class="textbox-medium" type="text" name="social_icon_extra01" value="<?php echo get_option('social_icon_extra01'); ?>" />
		</div>
		<div class="field">
			Extra 02 URL: <input class="textbox-medium" type="text" name="social_url_extra02" value="<?php echo get_option('social_url_extra02'); ?>" />
			Extra 02 ICON URL: <input class="textbox-medium" type="text" name="social_icon_extra02" value="<?php echo get_option('social_icon_extra02'); ?>" />
		</div>
		<div class="field">
			Extra 03 URL: <input class="textbox-medium" type="text" name="social_url_extra03" value="<?php echo get_option('social_url_extra03'); ?>" />
			Extra 03 ICON URL: <input class="textbox-medium" type="text" name="social_icon_extra03" value="<?php echo get_option('social_icon_extra03'); ?>" />
		</div>
		
		
		<input type="submit" name="Submit" value="Save Settings" />
		<input name="submit-updates" type="hidden" value="yes" />
	</form>
</div>

<?php 
}

// Add option link to Dashboard
function portfolio_settings() { 
	add_menu_page('Smart Portfolio Settings', 'Smart Portfolio Settings', 'edit_themes', __FILE__, 'portfolio_settings_form');
}

// Add Dashboard Head CSS
function portfolio_styles() { 
	echo "<style type=\"text/css\"> 
	.adminoptions label { display: block; font-weight:bold; } 
	.adminoptions .field { padding:5px 0; } 
	.adminoptions small { display:block; } 
	.adminoptions .textbox-small { width:100px; } 
	.adminoptions .textbox-medium { width:250px; } 
	.adminoptions .textbox-large { width:350px; } 
	.adminoptions .textarea-small { width:350px; height:50px; } 
	.adminoptions .textarea-medium { width:450px; height:50px; } 
	.adminoptions .textarea-large { width:500px; height:100px; } 
	.adminoptions .inset { padding-left:15px; margin-top:35px;  border-left:2px dotted #ccc; } 
	</style>";
}

function get_messages()
{
	if($_GET['sent'] == 'yes')
	{
		echo "<h2>Thank you! - Your message has been sent.</h2>";
	}
}

function mail_form()
{
	// if send is set but fake e-mail is not...
	// spam robots often fill in all form fields, so if the hidden e-mail 
	// has a value...it's probably spam.
	if(!empty($_POST['send_mail']) && empty($_POST['e-mail']))
	{
		$to = get_option('cf_email') ? get_option('cf_email') : "curt@curtziegler.com";
		$subject = "Paris French Classes Inquiry";
		
		$message = "Message from your website:\n\n";
		$message .= "From: " . stripslashes($_POST["cfname"]) . "\n";
		$message .= "Email: " . $_POST["cfemail"] . "\n";
		$message .= "Phone: " . $_POST["cfphone"] . "\n";
		$message .= "Message: " . stripslashes($_POST["cfmessage"]) . "\n\n";
		$message .= "IP Address: " . $_SERVER["REMOTE_ADDR"] . "\n\n";
		$message .= "Sent from: " . $_SERVER['HTTP_HOST'] . "\n\n";
		
		$from = $_POST["cfemail"];
		$headers = "From: blanche@parisfrenchclass.com\r\nReply-To: ".stripslashes($_POST["cfname"])." <".$_POST["cfemail"].">";
		//$headers = "From: blanche@parisfrenchclass.com";
		//$headers = "From: ".$_POST["cfemail"];
		
		if(wp_mail($to,$subject,$message,$headers))
		{
			$sent = true;
		}else{
			$sent = false;
		}
	}

	
?>
	
	<?php if($sent == true) { ?>
		<h2>Thank You!</h2>
		<p><strong>Your message has been sent.</strong></p>
	<?php }else{ ?>
		<h2>Contact Form</h2>
	<?php } ?>
	<p id="message" class="hidden"></p>
	<div class="contactForm">
		<form method="post" target="_self" action="" onsubmit="javascript:return validate(this);">
			<div class="field"><label>Your Name</label><input type="text" name="cfname" class="textbox" /></div>
			<div class="field"><label>Your Email</label><input type="text" name="cfemail" class="textbox" /></div>
			<div class="field"><label>Your Phone</label><input type="text" name="cfphone" class="textbox" /></div>
			<div class="field"><label>Message</label><textarea cols="5" rows="5" class="textarea" name="cfmessage" tabindex="4">Enter your message...</textarea></div>
			<div class="field"><input type="submit" value="SEND MESSAGE" class="submit" name="send_mail" class="button" /></div>
			<input type="hidden" name="e-mail" />
		</form>
	</div>
<?php
}
?>
