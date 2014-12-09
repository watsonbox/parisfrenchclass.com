<?php
/**
 * @package WassUP
 * @subpackage
 */
/**
 *  settings.php -- a wassup.php include file for changing Wassup default 
 *  	            settings in the Wassup options menu 
 */
?>
	<style type="text/css">
	  h3 { margin-bottom:0px; padding-bottom:5px; color:#333; }
          form p { margin-top:0px; padding-top:0px; padding-left:15px; }
	<?php if (version_compare($wp_version, '2.5', '<')) { ?>
	  #wassup_opt_frag-1, #wassup_opt_frag-2, 
	  #wassup_opt_frag-3, #wassup_opt_frag-4 { 
	  	background-color: #e6eff6;
		border-left: 1px solid #cce; 
		border-right: 1px solid #cce; 
		border-bottom: 1px solid #cce;
		font-size: 85%;
	  }
	  #tabcontainer { list-style:none; }
	  #tabcontainer ul li { min-width:60px; display:inline;}
	  #tabcontainer ul li a span { font-size: 105%; }
	<?php } //end if version_compare ?>
	</style>
<?php
	$adminemail = get_bloginfo('admin_email');

	$alert_msg = "";
	if ($wassup_options->wassup_remind_flag == 2) {
		$alert_msg = '<p style="color:red;font-weight:bold;">'.__('ATTENTION! Your WassUp table have reached the maximum value you set, I disabled the alert, you can re-enable it here.','wassup').'</p>';
		$wassup_options->wassup_remind_flag = 0;
		$wassup_options->saveSettings();
	}
	$alertstyle = 'color:red; background-color:#ffd;';

	if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
		if (!empty($_POST['delete_manual'])) {
			$to_date = wassup_get_time();
			$from_date = @strtotime($_POST['delete_manual'], $to_date);
			$wpdb->query("DELETE FROM $table_name WHERE timestamp<'$from_date'");
			$wpdb->query("OPTIMIZE TABLE $table_name");
		} 
		if (!empty($_POST['wassup_empty'])) {
			$wpdb->query("DELETE FROM $table_name");
			$wpdb->query("OPTIMIZE TABLE $table_name");
		}
		$table_status = $wpdb->get_results("SHOW TABLE STATUS LIKE '$table_name'");
		foreach ($table_status as $fstatus) {
			$data_lenght = $fstatus->Data_length;
			$data_rows = $fstatus->Rows;
			if (isset($fstatus->Engine)) {
				$table_engine = $fstatus->Engine;
			} elseif (isset($fstatus->Type)) {
				$table_engine = $fstatus->Type;
			} else {
				$table_engine = "";
			}
			$table_collation = (isset($fstatus->Collation)? $fstatus->Collation: '');
		}
		$tusage = number_format(($data_lenght/1024/1024), 2, ",", " ");
		$tusage2 = ($data_lenght/1024/1024);
	} else { ?>
		<span style="<?php echo $alertstyle; ?>"><br /><strong><?php echo __('IMPORTANT').': WassUp '.__("table empty or does not exist. Click 'repair' button to rebuild it now ==>","wassup"); ?></strong> [repair]
		</span>
<?php	} ?>
	
	<p style="padding:10px 0 10px 0;"><?php _e('Select the options you want for the WassUp plugin','wassup'); ?></p>
	<?php
	if (!empty($_GET['tab']) && is_numeric($_GET['tab'])) { $tab = $_GET['tab']; }
	else { $tab = "0"; } ?>

	<form action="" method="post">
	<div id="tabcontainer">
	    <ul style="list-style:none;">
                <li><a href="#wassup_opt_frag-1"><span><?php _e("General Setup", "wassup") ?></span></a></li>
		<li<?php if ($tab == "2"  || isset($_POST['submit-options2'])) { echo ' class="ui-tabs-selected"';} ?>><a href="#wassup_opt_frag-2"><span><?php _e("Statistics Recording", "wassup") ?></span></a></li>
		<li<?php if ($tab == "3"  || isset($_POST['submit-options3'])) { echo ' class="ui-tabs-selected"';} ?>><a href="#wassup_opt_frag-3"><span><?php _e("Manage Files & Database", "wassup") ?></span></a></li>
                <li<?php if ($tab == "4"  || isset($_POST['submit-options4'])) { echo ' class="ui-tabs-selected"';} ?>><a href="#wassup_opt_frag-4"><span><?php _e("Uninstall", "wassup") ?></span></a></li>
	    </ul>

	   <div id="wassup_opt_frag-1">
		<h3><?php _e('Your default screen resolution (browser width)','wassup'); ?></h3>
		<p><strong><?php _e('Default screen resolution (in pixels)','wassup'); ?></strong>:
		<select name='wassup_screen_res' style="width:90px;">
		<?php $wassup_options->showFormOptions("wassup_screen_res"); ?>
	        </select>
	        </p>
		<br /><h3><?php _e('Set minimum users level which can view and manage WassUp plugin (default Administrators)','wassup'); ?></h3>
		<p><select name="wassup_userlevel">
		<?php $wassup_options->showFormOptions("wassup_userlevel"); ?>
		</select></p><br />

		<br /><h3><?php _e('Dashboard Settings','wassup'); ?></h3>
		<p><input type="checkbox" name="wassup_dashboard_chart" value="1" <?php if($wassup_options->wassup_dashboard_chart == 1) print "CHECKED"; ?> /> <strong><?php _e('Display small chart in the dashboard','wassup'); ?></strong>
		</p><br />

		<br /><h3><?php _e('GEO IP Map (Spy view)','wassup'); ?></h3>
		
		<p><input type="checkbox" name="wassup_geoip_map" value="1"
		<?php	if (!function_exists('curl_init')) { print "DISABLED"; }
			elseif ($wassup_options->wassup_geoip_map == 1) print "CHECKED"; ?> />
		<strong><?php _e('Display a GEO IP Map in the spy visitors view','wassup'); ?></strong></p>
		<?php	// Test Google Maps Key If test fails deactivate *map option
		if (function_exists('curl_init')) {
			$code_error="";
			if ($wassup_options->wassup_geoip_map == 1) {
		
				$code = geocodeWassUp("Ancona", $wassup_options->wassup_googlemaps_key);
				if ($code[0] != "200") { 
					$code_error = "<script type=\"text/javascript\">jQuery(document).ready(function($){ $(\"#key_error\").fadeIn(2000); });</script><p id='key_error' style='text-align:center;background:#FA8C97;border:1px solid #999;padding:4px;margin:4px;width:40%;display:none;'>--->> <strong>".__("WARNING","wassup")."</strong> ".__("Activation problem >> Error code","wassup").": <a href='http://code.google.com/apis/maps/documentation/reference.html#GGeoStatusCode' target='_BLANK'>".$code."</a> <<---</p>";
					$wassup_options->wassup_geoip_map = 0;
					//$wassup_options->wassup_googlemaps_key = ""; //don't erase key...may need later
					$wassup_options->saveSettings();
					echo "$code_error";
				}
			}
		?>
		<p><strong>Google Maps API <?php _e("key","wassup"); ?>:</strong> <input type="text" name="wassup_googlemaps_key" size="40" value="<?php print $wassup_options->wassup_googlemaps_key; ?>" /> - <a href="http://www.google.com/maps/api_signup?url=<?php echo $wpurl; ?>"><?php _e("signup for your key","wassup"); ?></a></p></br />
		<?php
		} else { 	//no curl ?>
		<p class="small"><?php echo __("Geo IP Map requires","wassup")." PHP <strong>Curl</strong>. ".__("Please install it to be able to activate this feature","wassup")."."; ?></p>
		<?php
		} ?>

		<br /><h3><?php _e('Time format','wassup'); ?></h3>
		<p>12h <input type="radio" name="wassup_time_format" value="12" <?php if($wassup_options->wassup_time_format == 12) print "CHECKED"; ?> /> - 24h <input type="radio" name="wassup_time_format" value="24" <?php if($wassup_options->wassup_time_format == 24) print "CHECKED"; ?> /> <strong><?php _e('Time format 12/24 hour','wassup'); ?></strong>
		</p><br />

		<br /><h3><?php _e('Visitor Detail Settings','wassup'); ?></h3>
		<p><strong><?php _e('Show chart type - How many axes','wassup'); ?></strong>:
		<select name='wassup_chart_type'>
		<?php $wassup_options->showFormOptions("wassup_chart_type"); ?>
		</select>
		</p><br />
		<p>
		<strong><?php echo __('Set how many minutes wait for automatic page refresh','wassup').'</strong> ('.__('Current Visitors Online and Visitors Details','wassup').'):'; ?>
		<input type="text" name="wassup_refresh" size="2" value="<?php print $wassup_options->wassup_refresh; ?>" /> <?php _e('refresh minutes (default 3)','wassup'); ?></p><br />

		<p><strong><?php _e('Show visitor details for','wassup'); ?></strong>:
		<select name='wassup_default_type'>
		<?php $wassup_options->showFormOptions("wassup_default_type"); ?>
		</select>
		</p><br />
		<p><strong><?php _e('Number of items per page','wassup'); ?></strong>:
		<select name='wassup_default_limit'>
		<?php $wassup_options->showFormOptions("wassup_default_limit"); ?>
		</select>
		</p><br />
		<?php
		//TODO: Make Top 10 Customizable with up to 10 choices
		$top_ten = unserialize(html_entity_decode($wassup_options->wassup_top10));
		?>
		<br /><h3><?php _e('Customize Top Ten List','wassup'); ?></h3>
		<p style="margin-top:5px;"> <strong> <?php _e("Choose one or more items for your Top Ten list", "wassup"); ?></strong> (<?php _e("over 5 selections may cause horizontal scrolling","wassup"); ?>):<br />
		<div style="padding-left:25px;padding-top:0;margin-top:0;display:block;clear:left;">
		<div style="display:block; vertical-align:top; float:left; width:225px;">
	        <input type="checkbox" name="topsearch" value="1" <?php if($top_ten['topsearch'] == 1) print "CHECKED"; ?> /><?php _e("Top Searches", "wassup"); ?><br />
	        <input type="checkbox" name="topreferrer" value="1" <?php if($top_ten['topreferrer'] == 1) print "CHECKED"; ?> /><?php _e("Top Referrers", "wassup"); ?><br />
		<input type="checkbox" name="toprequest" value="1" <?php if($top_ten['toprequest'] == 1) print "CHECKED"; ?> /><?php _e("Top Requests", "wassup"); ?><br />
		</div>
		<div style="display:block; vertical-align:top; float:left; width:225px;">
	        <input type="checkbox" name="topbrowser" value="1" <?php if($top_ten['topbrowser'] == 1) print "CHECKED"; ?> /><?php _e("Top Browsers", "wassup"); ?> <br />
		<input type="checkbox" name="topos" value="1" <?php if($top_ten['topos'] == 1) print "CHECKED"; ?> /><?php _e("Top OS", "wassup"); ?> <br />
	        <input type="checkbox" name="toplocale" value="1" <?php if($top_ten['toplocale'] == 1) print "CHECKED"; ?> /><?php _e("Top Locales", "wassup"); ?></span><br />
		</div>
		<div style="vertical-align:top; float:left; width:225px;">
		<input type="checkbox" name="topvisitor" value="1" <?php if($top_ten['topvisitor'] == 1) print "CHECKED"; ?> /><?php _e("Top Visitors", "wassup"); ?><br /><!--  
		//TODO
	        <input type="checkbox" name="topfeed" value="1" DISABLED /><?php _e("Top Feeds", "wassup"); ?><br />
	        <input type="checkbox" name="topcrawler" value="1" DISABLED /><?php _e("Top Crawlers", "wassup"); ?> --><br />
		</div>
		</div>
		</p>
		<p style="margin-top:5px; clear:left;"> <strong><?php _e("Websites/domains to exclude from top referrers", "wassup"); ?></strong> :<br />
		<span style="padding-left:10px;display:block;clear:left;">
		<textarea name="topreferrer_exclude" rows="2" style="width:60%;"><?php echo $top_ten['topreferrer_exclude']; ?></textarea><br />
		<?php  _e("Comma separated value","wassup"); ?> (ex: mydomain2.net, mydomain2.info,...).
		<?php  _e("Don't list your website domain defined in WordPress","wassup"); ?>.</span>
		</p>
		<br /><br />
		<p style="clear:both;padding-left:0;padding-top:15px;"><input type="submit" name="submit-options" value="<?php _e('Save Settings','wassup'); ?>" />&nbsp;<input type="reset" name="reset" value="<?php _e('Reset','wassup'); ?>" /> - <input type="submit" name="reset-to-default" value="<?php _e("Reset to Default Settings", "wassup"); ?>" /></p><br />
	   </div>

	   <div id="wassup_opt_frag-2">
		<h3><?php _e('Statistics Recording Settings','wassup'); ?></h3>
		<p> <input type="checkbox" name="wassup_active" value="1" <?php if($wassup_options->wassup_active == 1) print "CHECKED"; ?> /> <strong><?php _e('Enable/Disable Recording','wassup'); ?></strong></p>
		<p style="margin-top:5px;"> <strong> <?php _e("Checkbox to record statistics for each type of \"visitor\"", "wassup") ?></strong><br />
		<span style="padding-left:25px;padding-top:0;margin-top:0;display:block;clear:left;">
	        <input type="checkbox" name="wassup_loggedin" value="1" <?php if($wassup_options->wassup_loggedin == 1) print "CHECKED"; ?> /> <?php _e("Record logged in users", "wassup") ?><br />
	        <input type="checkbox" name="wassup_admin" value="1" <?php if($wassup_options->wassup_admin == 1) print "CHECKED"; ?> /> <?php _e("Record logged in administrators", "wassup") ?><br />
	        <input type="checkbox" name="wassup_spider" value="1" <?php if($wassup_options->wassup_spider == 1) print "CHECKED"; ?> /> <?php _e("Record spiders and bots", "wassup") ?><br />
	        <input type="checkbox" name="wassup_attack" value="1" <?php if($wassup_options->wassup_attack == 1) print "CHECKED"; ?> /> <?php _e("Record attack/exploit attempts (libwww-perl agent)", "wassup") ?><br />
		</span>
		</p>
		<br /><p><input type="checkbox" name="wassup_spamcheck" value="1" <?php if($wassup_options->wassup_spamcheck == 1 ) print "CHECKED"; ?> /> <strong><?php _e('Enable/Disable Spam Check on Records','wassup'); ?></strong></p>
		<p style="margin-top:5px;"> <strong> <?php _e('Checkbox to record statistics for each type of "spam"','wassup'); ?></strong><br />
		<span style="padding-left:25px;padding-top:0;margin-top:0;display:block;clear:left;">
		<input type="checkbox" name="wassup_spam" value="1" <?php if($wassup_options->wassup_spam == 1) print "CHECKED"; ?> /> <?php _e('Record Akismet comment spam attempts','wassup'); ?> (<?php _e('check if an IP has previous comments as spam','wassup'); ?>)<br />
		<input type="checkbox" name="wassup_refspam" value="1" <?php if($wassup_options->wassup_refspam == 1) print "CHECKED"; ?> /> <?php _e('Record referrer spam attempts','wassup'); ?><br />
	        <input type="checkbox" name="wassup_hack" value="1" <?php if($wassup_options->wassup_hack == 1) print "CHECKED"; ?> /> <?php _e("Record admin break-in/hacker attempts", "wassup") ?><br />
		</span>
		</p>
		<br />
		<h3><?php _e('Statistics Recording Exceptions','wassup'); ?></h3>

		<p><strong><?php echo __("Sites","wassup")."\n<br /> &nbsp; ".__('Enter source IPs to exclude from recording','wassup'); ?></strong>:
		<br /><span style="padding-left:10px;display:block;clear:left;">
	        <textarea name="wassup_exclude" rows="2" style="width:60%;"><?php print $wassup_options->wassup_exclude; ?></textarea></span> &nbsp; <?php _e("comma separated value (ex: 127.0.0.1, 10.0.0.1, etc...)", "wassup") ?></p>
		
		<br /><p><strong><?php echo __("Users","wassup")."\n<br /> &nbsp; ".__('Enter usernames to exclude from recording','wassup'); ?></strong>:
		<br /><span style="padding-left:10px;display:block;clear:left;">
	        <textarea name="wassup_exclude_user" rows="2" style="width:60%;"><?php print $wassup_options->wassup_exclude_user; ?></textarea></span> &nbsp; <?php _e("comma separated value, enter a registered user's login name (ex: bobmarley, enyabrennan, etc.)", "wassup") ?></p>
		<br /><p><strong><?php echo __("Posts/pages","wassup")."\n<br /> &nbsp; ".__('enter requested URLs to exclude from recording','wassup'); ?></strong>:
		<br /><span style="padding-left:10px;display:block;clear:left;">
	        <textarea name="wassup_exclude_url" rows="2" style="width:60%;"><?php print $wassup_options->wassup_exclude_url; ?></textarea></span> &nbsp; <?php _e("comma separated value, don't enter entire url, only the last path or some word to exclude (ex: /category/wordpress, 2007, etc...)", "wassup") ?></p>
		<p style="clear:both;padding-left:0;padding-top:15px;"><input type="submit" name="submit-options2" value="<?php _e('Save Settings','wassup'); ?>" />&nbsp;<input type="reset" name="reset" value="<?php _e('Reset','wassup'); ?>" /> - <input type="submit" name="reset-to-default" value="<?php _e("Reset to Default Settings", "wassup"); ?>" /></p><br />
	   </div>
	
	   <div id="wassup_opt_frag-3">
	   <?php /*
	   <h3><?php _e('Temporary files location folder','wassup'); ?></h3>
		<p><?php echo '<strong>'.__('Current "Save path" directory for storing temporary files used to track visitor activity','wassup').'</strong>:<br />';
		$sessionpath = $wassup_options->wassup_savepath;
		if (empty($sessionpath)) {
			$sessionpath = getSessionpath();
		}
		//$sessionpath = "/fakefolder/temp";	//#debug
		$sessionstyle = '';
		//# check that session_save_path exists and is writable...
		if ($sessionpath == "" || $wassup_options->isWritableFolder($sessionpath) == false) {
			$sessionwarn = '<span style="font-size:95%; padding-left:5px;'.$alertstyle.'"><span style="text-decoration:blink;">'.__('WARNING','wassup').'!</span> '.__('Directory does not exist or is not writable. Please enter a different path above or change "session.save_path" in "php.ini" to point to a valid, writable folder','wassup').'.</span>';
			$sessionstyle = $alertstyle;
		} else {
			$sessionwarn ='<span style="font-size:95%; color:#555; padding-left:5px;">'.__('Note: To adjust, modify the directory shown in the box above or edit "sessions.save_path" in','wassup').' <i>php.ini</i>.</span>';
		} ?>
		<textarea name="wassup_savepath" rows="1" style="width:550px;padding-left:25px;clear:left;<?php echo $sessionstyle; ?>"><?php echo $sessionpath; ?></textarea>
		<br />&nbsp;&nbsp;<?php echo __('Use absolute directory paths only. This value is usually','wassup').' "/tmp".'."\n"; ?>
		<br />&nbsp; <span style="font-size:95%; color:#555;">System default for session.save_path="<?php echo session_save_path(); //debug ?>" from <i>php.ini</i> or from web server configuration.</span>
		<br />&nbsp;<?php echo $sessionwarn."\n"; ?>
		</p><br />
		*/ ?>
	   <?php //TODO ?>
	   <!--
	   <br /><h3><?php _e('Rescan Old Records','wassup'); ?></h3>
		<p><?php _e("Statistical records collected by earlier versions of WassUp may not have the latest spider, search engine, and spam data properly identified.  Click the \"Rescan\" button to retroactively scan and update old records","wassup"); ?>.
		<br /><input type="button" name="rescan" value="<?php _e('Rescan Old Records','wassup'); ?>" /> 
		</p><br />
	   -->
		<h3><?php _e('Select actions for table growth','wassup'); ?></h3>
		<p><?php _e("WassUp table grows very fast (especially if your blog is frequently visited), I recommend you to delete old records sometimes. You can select any option below to reset it, delete old records automatically or manually. (If you haven't database space problems you can leave the table as is)","wassup"); ?></p>
		<p><?php _e('Current WassUp table usage is','wassup'); ?>:
		<strong>
		<?php
		if ( (int)$tusage >= (int)$wassup_options->wassup_remind_mb) { 
			print '<span style="'.$alertstyle.'">'.$tusage.'</span>';
		} else { print $tusage; } ?>
		</strong> Mb (<?php echo $data_rows.' '.__('records','wassup'); ?>)</p>
		<?php print $alert_msg; ?>
		<br /><p><input type="checkbox" name="wassup_remind_flag" value="1" <?php if ($wassup_options->wassup_remind_flag == 1) print "CHECKED"; ?>> 
		<strong><?php _e('Alert me','wassup'); ?></strong> (<?php _e('email to','wassup'); ?>: <strong><?php print $adminemail; ?></strong>) <?php _e('when table reaches','wassup'); ?> <input type="text" name="wassup_remind_mb" size="3" value="<?php print $wassup_options->wassup_remind_mb; ?>"> Mb</p>
		<p><input type="checkbox" name="wassup_empty" value="1"> 
		<strong><?php _e('Empty table','wassup'); ?></strong> (<a href="?<?php echo $_SERVER['QUERY_STRING'].'&export=1&tab=2'; ?>"><?php _e('export table in SQL format','wassup'); ?></a>)
		<!-- 
		<a id="show-backup" href="#"><?php _e("backup table to server file", "wassup"); ?></a> -->	
		</p>
		<br /><p><strong><?php _e("Automatically delete records older than:", "wassup") ?></strong> 
		<select name="delete_auto">
		<?php $wassup_options->showFormOptions("delete_auto"); ?>
		</select></p>
		<br /><p><?php _e("Delete NOW records older than:", "wassup") ?> 
		<select name="delete_manual">
		<option value="never"><?php _e("Action is NOT undoable", "wassup") ?> &nbsp;</option>
		<option value="-1 day"><?php _e("24 hours", "wassup") ?></option>
		<option value="-1 week"><?php _e("1 week", "wassup") ?></option>
		<option value="-1 month"><?php _e("1 month", "wassup") ?></option>
		<option value="-3 months"><?php _e("3 months", "wassup") ?></option>
		<option value="-6 months"><?php _e("6 months", "wassup") ?></option>
		<option value="-1 year"><?php _e("1 year", "wassup") ?></option>
		</select></p><br />

		<br />
		<h3><?php _e("Server Settings and Memory Resources","wassup"); ?></h3>
		<p style="color:#555; margin-top:0; padding-top:0;"><?php echo __('For information only. Some values may be adjustable in startup files', 'wassup').", wp_config.php ".__('and','wassup')." php.ini ".__('or','wassup')." php5.ini"; ?>.</p><p>
		<p style="margin-bottom:0; padding-top:10px;"><strong>WassUp <?php _e('Version'); ?></strong>: <?php echo $wassupversion; ?>
	   	<ul class="varlist">
		<li><strong>WassUp <?php _e('Table name','wassup'); ?></strong>: <?php echo $wassup_options->wassup_table; ?></li>
	   	<li><strong>WassUp <?php _e('Table Charset/collation','wassup'); ?></strong>: <?php 
		if (!empty($table_collation)) {
			echo $table_collation; 
		} else {
			 _e("unknown","wassup");
		}?></li></ul>
		</p>
		<p style="margin-bottom:0; padding-top:10px;"><strong>WordPress <?php _e('Version','wassup'); ?></strong>: <?php echo $wp_version; ?>
	   	<ul class="varlist">
		<li><strong>WordPress <?php _e('Host Server','wassup'); ?></strong>: <?php 
		if (!empty($_SERVER['SERVER_SOFTWARE'])) {
			if (strtolower($_SERVER['SERVER_SOFTWARE']) == "apache" && function_exists('apache_get_version')) {
				echo apache_get_version();
			} else {
				echo $_SERVER['SERVER_SOFTWARE'];
			}
		} elseif (function_exists('apache_get_version')) { 
			echo apache_get_version();
		} else {
			 _e("unknown","wassup");
		} ?></li>
	   	<li><strong>WordPress <?php _e('Time Offset','wassup'); ?></strong>: <?php $offset = get_option("gmt_offset"); echo 'UTC ';
		if ($offset >= 0) { echo '+'.$offset; }
		else { echo $offset; }
		echo ' '.__('hours').' ('.gmdate(get_option('time_format'),current_time('timestamp')).')'; ?></li>
		<li><strong>WordPress <?php _e('Character set','wassup'); ?></strong>: <?php echo get_option('blog_charset'); ?></li>
		<li><strong>WordPress <?php _e('Language','wassup'); ?></strong>: <?php echo get_bloginfo('language'); ?></li>
		</ul></p>
		<p style="margin-bottom:0; padding-top:10px;"><strong>PHP <?php _e('Version'); ?></strong>: <?php echo PHP_VERSION; ?>
	   	<ul class="varlist">
		<li><strong>PHP <?php _e("Safe Mode", "wassup"); ?></strong>: <?php
		if (ini_get("safe_mode")) { 
			_e("on","wassup");
		} else { 
			_e("off","wassup"); ?></li>
		<li><strong>PHP <?php _e("File Open Restrictions", "wassup"); ?></strong> (open_basedir): <?php
			$open_basedir=ini_get('open_basedir');
			if ($open_basedir == "") {
				_e("off","wassup");
			} else { 
				__("on","wassup").'<!-- $open_basedir -->';
			}
		}
		?></li>
	   	<li><strong>PHP <?php _e("Memory Allocation","wassup"); ?></strong>: <?php	
			$memory_use=0;
			if (function_exists('memory_get_usage')) {
				$memory_use=round(memory_get_usage()/1024/1024,2);
			}
			$memory_limit = ini_get('memory_limit');
			if (preg_match('/^(\d+){1,4}(\w?)/',$memory_limit,$matches) > 0) {
				$mem=(int)$matches[1];
				if ( $mem < 12 && $matches[2] == "M") { 
			   		print '<span style="'.$alertstyle.'">'.$memory_limit."</span>";
				} else {
					echo $memory_limit;
				}
			} else { 
				$memory_limit=0; _e("unknown","wassup");
			}
		?></li>
	   	<li><strong>PHP <?php _e("Memory Usage","wassup"); ?></strong>: <?php
			if ($memory_limit >0 && ($memory_limit-$memory_use) < 2) {
				print '<span style="'.$alertstyle.'">'.$memory_use."M</span>";
			} elseif ($memory_use >0) {
			   	echo $memory_use."M";
			} else { 
				_e("unknown","wassup");
			}
		?></li>
	   	<li><strong>PHP <?php _e("Script Timeout Limit","wassup"); ?></strong>: <?php
			$max_execute = ini_get("max_execution_time");
			if (!empty($max_execute)) { echo $max_execute." ".__("seconds","wassup"); }
			else { _e("unknown","wassup"); }
		?></li>
	   	<li><strong>PHP <?php _e("Browser Capabilities File","wassup"); ?></strong> (browscap): <?php	
			$browscap = ini_get("browscap");
			if ( $browscap == "") { _e("not set","wassup"); } 
			else { echo basename($browscap); }
		?></li>
	   	<li><strong>PHP Curl</strong>: <?php	
			if (!function_exists('curl_init')) { echo "<span style='color:red;'>".__("not installed","wassup")."</span>"; } 
			else { echo "<span style='color:green;'>".__("installed","wassup")."</span>"; }
		?></li></ul></p>
	   	<?php
		//###MySQL server settings
		$sql_version = $wpdb->get_var("SELECT version() as version");
		//$sql_version = mysql_get_server_info();
		if (!empty($sql_version) && version_compare($sql_version, '4.1', '>=')) {
			$sql_conf = @$wpdb->get_results("SELECT @@global.time_zone AS tzglobal, @@session.time_zone AS tzsession, @@session.collation_connection AS char_collation, @@session.wait_timeout AS wait_timeout, @@global.connect_timeout AS connect_timeout, @@global.key_buffer_size as index_buffer, @@session.read_buffer_size AS read_buffer, @@global.query_cache_size AS query_cache_size, @@global.query_cache_type AS query_cache_type, @@global.delayed_queue_size AS delayed_queue_size, @@session.storage_engine AS storage_engine");
		}
		if (!empty($sql_conf) && is_array($sql_conf)) { 
			$sql_tzglobal = isset($sql_conf[0]->tzglobal)? $sql_conf[0]->tzglobal : "";
			$sql_timezone = isset($sql_conf[0]->tzsession)? $sql_conf[0]->tzsession : $sql_tzglobal;
			$sql_collation = isset($sql_conf[0]->char_collation)? $sql_conf[0]->char_collation : "";
			$sql_wait_timeout = isset($sql_conf[0]->wait_timeout)? $sql_conf[0]->wait_timeout : "";
			$sql_connect_timeout = isset($sql_conf[0]->connect_timeout)? $sql_conf[0]->connect_timeout : "";
			$sql_indexbuffer = isset($sql_conf[0]->index_buffer)? $sql_conf[0]->index_buffer : "";
			$sql_readbuffer = isset($sql_conf[0]->read_buffer)? $sql_conf[0]->read_buffer : "";
			$sql_query_cache = isset($sql_conf[0]->query_cache_size)? $sql_conf[0]->query_cache_size : "";
			$sql_cache_type = isset($sql_conf[0]->query_cache_type)? $sql_conf[0]->query_cache_type : "";
			$sql_delayed_queue = isset($sql_conf[0]->delayed_queue_size)? $sql_conf[0]->delayed_queue_size : "";
			$sql_engine = isset($sql_conf[0]->storage_engine)? $sql_conf[0]->storage_engine : "";
		} else {
			//for old MySQL versions (pre 4.1)
			$sql_vars = $wpdb->get_results("SHOW VARIABLES");
			foreach ($sql_vars AS $var) {
				if ($var->Variable_name == "timezone") {
					$sql_timezone = $var->Value;
				} elseif ($var->Variable_name == "connect_timeout") {
					$sql_connect_timeout = $var->Value;
				} elseif ($var->Variable_name == "wait_timeout") {
					$sql_wait_timeout = $var->Value;
				} elseif ($var->Variable_name == "key_buffer_size") {
					$sql_indexbuffer = $var->Value;
				} elseif ($var->Variable_name == "read_buffer_size") {
					$sql_readbuffer = $var->Value;
				} elseif ($var->Variable_name == "query_cache_size") {
					$sql_query_cache = $var->Value;
				} elseif ($var->Variable_name == "query_cache_type") {
					$sql_query_cache_type = $var->Value;
				} elseif ($var->Variable_name == "delayed_queue_size") {
					$sql_delayed_queue = $var->Value;
				} elseif ($var->Variable_name == "storage_engine") {
					$sql_engine = $var->Value;
				} elseif (empty($sql_engine) && $var->Variable_name == "table_type") {
					$sql_engine = $var->Value;
				}
			}
			if ($debug_mode) {
				print_r($sql_vars); //debug
			}
		}
		?>
		<p style="margin-bottom:0; padding-top:10px;"><strong>MySQL <?php _e('Version'); ?></strong>: <?php if (!empty($sql_version)) { echo $sql_version; } else { _e("unknown","wassup"); } ?>
	   	<ul class="varlist">
	   	<li><strong>MySQL <?php _e('Engine','wassup'); ?></strong>: <?php
			if (!empty($table_engine)) { 
				echo $table_engine;
			} elseif (!empty($sql_engine)) { 
				echo $sql_engine;
			} else { 
				_e("unknown","wassup");
			} ?></li>
		<li><strong>MySQL <?php _e('Query Cache','wassup'); ?></strong>: <?php 
		if (is_numeric($sql_query_cache)) {
			if ((int)$sql_query_cache >0) {
				echo (round((int)$sql_query_cache/1024/1024)) . "M";
			} else {
				echo $sql_query_cache." (".__("disabled","wassup").")";
			}
		} else { 
			_e("unknown","wassup");
		} ?></li>
	   	<li><strong>MySQL <?php _e('Delayed Insert Queue'); ?></strong>: <?php 
		if (is_numeric($sql_delayed_queue)) {
			if ((int)$sql_delayed_queue >0) {
				echo (int)$sql_delayed_queue ." ".__("rows","wassup");
			} else {
				echo $sql_delayed_queue." (".__("not supported","wassup").")";
			}
		} else { 
			_e("unknown","wassup");
		} ?></li>
		<li><strong>MySQL <?php _e('Index (Key) Buffer','wassup'); ?></strong>: <?php 
		if (is_numeric($sql_indexbuffer)) {
			if ((int)$sql_indexbuffer >0) {
				echo (round((int)$sql_indexbuffer/1024/1024)) . "M";
			} else {
				echo $sql_indexbuffer." (".__("disabled","wassup").")";
			}
		} else { 
			_e("unknown","wassup");
		}
		?></li>
		<li><strong>MySQL <?php _e('Read Buffer','wassup'); ?></strong>: <?php 
		if (is_numeric($sql_readbuffer)) {
			if ((int)$sql_readbuffer >0) {
				echo (round((int)$sql_readbuffer/1024/1024)) . "M";
			} else {
				echo $sql_readbuffer." (".__("disabled","wassup").")";
			}
		} else { 
			_e("unknown","wassup");
		}
		?></li>
		<li><strong>MySQL <?php _e("Wait Timeout","wassup"); ?></strong>: <?php
		if (is_numeric($sql_wait_timeout)) {
			echo $sql_wait_timeout." ".__("seconds","wassup");
		} else { 
			_e("unknown","wassup");
		}
		?></li>
	   	<li><strong>MySQL <?php _e('Timezone'); ?></strong>: 
		<?php if (!empty($sql_timezone)) {
			$sys_timezone = date('T');
			if ($sql_timezone == "SYSTEM" || $sql_timezone == strtoupper($sys_timezone)) {
				$sql_tzoffset = sprintf("%s",(int)date('Z')/(60*60));
				$sql_timezone = $sys_timezone. " (UTC ".$sql_tzoffset.")";
			}
			echo $sql_timezone;
		} else { _e("unknown","wassup"); }
		?> </li>
		<li><strong>MySQL <?php _e('Charset/collation','wassup'); ?></strong>: <?php if (!empty($sql_collation)) {
			echo $sql_collation;
		} else {
			$sql_charset = mysql_client_encoding();
			if (!empty($sql_charset)) { 
				echo $sql_charset;
			} else { _e("unknown","wassup"); }
		}
		?></li>
		</ul></p>
		</p>
		<br /><br />
		<p style="clear:both;padding-left:0;padding-top:15px;"><input type="submit" name="submit-options3" value="<?php _e('Save Settings','wassup'); ?>" />&nbsp;<input type="reset" name="reset" value="<?php _e('Reset','wassup'); ?>" /> - <input type="submit" name="reset-to-default" value="<?php _e("Reset to Default Settings", "wassup"); ?>" /></p><br />
	   </div>
	
	   <div id="wassup_opt_frag-4">
		<h3><?php _e('Want to uninstall WassUp?', 'wassup') ;?></h3>
		<p><?php _e('No problem. Before you deactivate this plugin, check the box below to cleanup any data that was collected by WassUp that could be left behind.', 'wassup') ;?></p><br />
		<p><input type="checkbox" name="wassup_uninstall" value="1" <?php if ($wassup_options->wassup_uninstall == 1 ) print "CHECKED"; ?> /> <strong><?php _e('Permanently remove WassUp data and settings from Wordpress','wassup'); ?></strong></p>
		<?php if ($wassup_options->wassup_uninstall == 1) { ?>
			<span style="font-size:95%;font-weight:bold; margin-left:20px;<?php echo $alertstyle; ?>"><span style="text-decoration:blink;padding-left:5px;"><?php _e("WARNING","wassup"); ?>! </span><?php _e("All WassUp data and settings will be DELETED upon deactivation of this plugin","wassup"); ?>.</span><br />
		<?php } ?>
		<br /><p><?php _e("This action cannot be undone. Before uninstalling WassUp, you should backup your Wordpress database first. WassUp data is stored in the table", "wassup"); ?> <strong>wp_wassup</strong>.</p>

		<br /><p><?php _e("To help improve this plugin, we would appreciate your feedback at","wassup"); ?> <a href="http://www.wpwp.org">www.wpwp.org</a>.</p>
		<br /><br />
		<p style="clear:both;padding-left:0;padding-top:15px;"><input type="submit" name="submit-options4" value="<?php _e('Save Settings','wassup'); ?>" />&nbsp;<input type="reset" name="reset" value="<?php _e('Reset','wassup'); ?>" /> - <input type="submit" name="reset-to-default" value="<?php _e("Reset to Default Settings", "wassup"); ?>" /></p><br />
	   </div>
        </form>
	</div>
	<br />
