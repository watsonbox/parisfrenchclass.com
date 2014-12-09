<?php
/*
Plugin Name: WassUp
Plugin URI: http://www.wpwp.org
Description: Wordpress plugin to analyze your visitors traffic with real time stats, chart and a lot of chronological informations. It has sidebar Widget support to show current online visitors and other statistics. For Wordpress 2.2 or higher.
Version: 1.7.2.1
Author: Michele Marcucci, Helene D.
Author URI: http://www.michelem.org/
Disclaimer: Use at your own risk. No warranty expressed or implied is provided.

Copyright (c) 2007-2009 Michele Marcucci
Released under the GNU General Public License (GPL)
http://www.gnu.org/licenses/gpl.txt
*/

//# Stop any attempt to call wassup.php directly.  -Helene D. 1/27/08.
if (preg_match('#'.basename(__FILE__) .'#', $_SERVER['PHP_SELF'])) { 
	die('Permission Denied! You are not allowed to call this page directly.');
}
$wassupversion = "1.7.2.1";
$debug_mode=false;	//turns on debugging (global)
$wassupdir = dirname(__FILE__);
define('WASSUPFOLDER', dirname(plugin_basename(__FILE__)), TRUE);
require_once($wassupdir.'/lib/wassup.class.php');
require_once($wassupdir.'/lib/main.php');
include_once($wassupdir.'/lib/uadetector.class.php');

$wpurl = get_bloginfo('wpurl');	//global
$blogurl = get_bloginfo('home'); //global
//For non-standard "/wp-content/" paths (since WordPress 2.6+) 2009-04-04 -Helene D.
if (defined('WP_CONTENT_URL') && defined('WP_CONTENT_DIR') && strpos(WP_CONTENT_DIR,ABSPATH)===FALSE) {
	$wassupurl = rtrim(WP_CONTENT_URL,"/")."/plugins/".WASSUPFOLDER;
} else {
	$wassupurl = $wpurl."/wp-content/plugins/".WASSUPFOLDER;
}
define('WASSUPURL',$wassupurl);
unset ($wassupurl, $wassupdir);

global $wp_version, $current_user, $wassup_options;

if (is_admin()) {
if (isset($_GET['export'])) {
	export_wassup();
}

//global $wp_version, $current_user; //defined above

//#This works only in WP2.2 or higher
if (version_compare($wp_version, '2.2', '<')) {
	if (function_exists('deactivate_plugins')) {
		deactivate_plugins(__FILE__);
	}
	wp_die( '<strong style="color:#c00;background-color:#dff;padding:5px;">'.__("Sorry, Wassup requires WordPress 2.2 or higher to work","wassup").'.</strong>');
}
}
//#add initial options and create table when Wassup activated 
//  -Helene D. 2/26/08.
function wassup_install() {
	global $wpdb, $wassupversion;

	//### Add/update wassup settings in Wordpress options table
	$wassup_options = new wassupOptions; //#settings initialized here
	$table_name = (!empty($wassup_options->wassup_table))? $wassup_options->wassup_table : $wpdb->prefix . "wassup";
	$table_tmp_name = $table_name."_tmp";

	//# wassup should not be active during install
	$wassup_options->wassup_active = 0;

	//# set hash
	$whash = $wassup_options->get_wp_hash();
	if (!empty($whash)) {
		$wassup_options->whash = $whash;
	}
	//# Add timestamp to optimize table once a day
	$wassup_options->wassup_optimize = wassup_get_time();

        //# clear temporary values, wmark and wip, and wassup_alert_message
        $wassup_options->wmark = 0;     //#no preservation of delete/mark
        $wassup_options->wip = null;
        $wassup_options->wassup_alert_message = "";

	//### For upgrade of Wassup, manually initialize new settings
	//# initialize settings for 'spamcheck', 'refspam', and 'spam'
	if (!isset($wassup_options->wassup_spamcheck)) {
		$wassup_options->wassup_spamcheck = "0";
		//#set wassup_spamcheck=1 if either wassup_refspam=1 or wassup_spam=1
		if (isset($wassup_options->wassup_spam) && isset($wassup_options->wassup_refspam)) {
			//$wassup_options->wassup_spam = "1";
			//$wassup_options->wassup_refspam = "1";
			if ( $wassup_options->wassup_spam == "1" || $wassup_options->wassup_refspam == "1" ) { 
				$wassup_options->wassup_spamcheck = "1";
			}
		//} elseif ( $wassup_options->wassup_spam == "0" && $wassup_options->wassup_refspam == "0" ) { 
	   	//	$wassup_options->wassup_spamcheck = "0";
	   	}
	}
	
	//# display google chart by default for upgrades from 1.4.4
	if (!isset($wassup_options->wassup_chart)) {
		$wassup_options->wassup_chart = 1;
	}
	//# assign top ten items for upgrades from 1.4.9 or less
	if (empty($wassup_options->wassup_top10)) {
		$top_ten = array("topsearch"=>"1",
				 "topreferrer"=>"1",
				 "toprequest"=>"1",
				 "topbrowser"=>"1",
				 "topos"=>"1",
				 "toplocale"=>"0",
				 "topfeed"=>"0",
				 "topcrawler"=>"0",
				 "topvisitor"=>"0",
				 "topreferrer_exclude"=>"");
	} else {
	//new top ten array items for 1.6+ upgrades
		$top_ten = unserialize(html_entity_decode($wassup_options->wassup_top10));
		if (!isset($top_ten['topreferrer_exclude'])) {
			$top_ten['topreferrer_exclude'] = "";
		}
	}
	$wassup_options->wassup_top10 = attribute_escape(serialize($top_ten));
	unset($top_ten);	//because "install" works in global scope in some Wordpress versions

	//#upgrade from 1.6: new options wassup_time_format and wassup_hack
	if (!isset($wassup_options->wassup_time_format)) {
		$wassup_options->wassup_time_format = 24;
	}
	if (!isset($wassup_options->wassup_hack)) {
		$wassup_options->wassup_hack = 1;
	}
	//#upgrade from 1.6.3: new option for default_spy_type
	if (!isset($wassup_options->wassup_default_spy_type)) {
		$wassup_options->wassup_default_spy_type = "everything";
	}

	//#Upgrade from 1.7.1: new options for wassup_dbengine and wassup_table
	$wassup_options->wassup_dbengine = $wassup_options->getMySQLsetting("engine");
	if (empty($wassup_options->wassup_table)) {
		$wassup_options->wassup_table = $table_name;
	}
	$wassup_options->wassup_alert_message = "Wassup install: wassup options set"; //debug
	//# Save settings before table create/upgrade...
	$wassup_options->saveSettings();

	//# TODO:
	//###Detect known incompatible plugins like "wp_cache" and stop 
	//#  install and show warning message...

	//CREATE new table
	if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) { 
		if (wCreateTable($table_name)) {
			//1st attempt
			if ($wpdb->get_var("SHOW TABLES LIKE '$table_tmp_name'") != $table_tmp_name) {
				wCreateTable($table_tmp_name);
			}
			$wassup_options->wassup_active = "1";
		} else {
			//2nd attempt: no character set in table creation
			if (wCreateTable($table_name,false)) {
				if ($wpdb->get_var("SHOW TABLES LIKE '$table_tmp_name'") != $table_tmp_name) {
					wCreateTable($table_tmp_name,false);
				}
				$wassup_options->wassup_active = "1";
			} elseif ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
				//table error but table exists, so record anyway
				$wassup_options->wassup_active = "1";
			} else {
				//table creation error
				$wassup_options->wassup_active = "0";
			}
		}
	} else {
		//UpdateTable(); //<== wassup_tmp is added here, if missing
		//'wUpdateTable' now uses 'wCreateTable' and Wordpress' 'dbdelta' function to upgrade table structure
		if (wUpdateTable()) {
			$wassup_options->wassup_active = "1";	//start recording 
		} elseif ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
			//table error but table exists, so record anyway
			$wassup_options->wassup_active = "1";
		} else {
			//table upgrade error
			$wassup_options->wassup_active = "0";
		}
	}
	
	//# Upgrade from 1.6.5, add version to options - after table update
	$wassup_options->wassup_version = $wassupversion;
	if ($wassup_options->wassup_active == "1") {
		$wassup_options->wassup_alert_message = "Wassup $wassupversion install: database created/upgraded successfully"; //debug
	}

	$wassup_options->saveSettings();
	unset ($table_name, $table_tmp_name);
} //#end function wassup_install

//set global variables that are dependent on Wassup's wp_options values
$wassup_options = new wassupOptions; 
//$wassup_options->loadSettings();	//done automatically 
//$wassup_settings = get_option('wassup_settings'); //temp only..
$whash = $wassup_options->whash;	//global...

//#Completely remove all wassup tables and options from Wordpress when
//# the 'wassup_uninstall' option is set and plugin is deactivated.
//#  -Helene D. 2/26/08
function wassup_uninstall() {
	global $wassup_options, $wpdb;

	//first, stop recording
	if ($wassup_options->wassup_active == "1") {
		$wassup_option->wassup_active = "0";
		$wassup_options->saveSettings();
	}
	//remove wassup tables and options
	if ($wassup_options->wassup_uninstall == "1") {
		//make sure wassup is not recording before purge table
		//if ($wassup_options->wassup_active == "1") {
		//	$wassup_option->wassup_active = "0";
		//	$wassup_options->saveSettings();
		//}
		//remove wassup widgets from wordpress ?
		remove_action("widgets_init", "wassup_widget_init");
		remove_action('wp_dashboard_setup', 'wassup_add_dashboard_widgets' );
		//purge wassup tables- WARNING: this is a permanent erase!!
		$table_name = $wassup_options->wassup_table;
		$table_tmp_name = $table_name."_tmp";
		//$wpdb->query("DROP TABLE IF EXISTS $table_name"); //incorrectly causes an activation error in Wordpress
		//$wpdb->query("DROP TABLE IF EXISTS $table_tmp_name"); //incorrectly causes an activation error in Wordpress
		mysql_query("DROP TABLE IF EXISTS $table_tmp_name");
		mysql_query("DROP TABLE IF EXISTS $table_name");
		//$table_name = $wpdb->prefix . "wassup";
		//$table_tmp_name = $wpdb->prefix . "wassup_tmp";

		$wassup_options->deleteSettings(); 
	}
} //#end function wassup_uninstall

function wassup_meta_info($screen_res="") {
	global $wassup_options, $blogurl, $wassupversion, $current_user;
	//$siteurl = get_bloginfo('siteurl');
	if ($wassup_options->wassup_active == "1") {
		print '<meta name="wassup-version" content="'.$wassupversion.'" />'."\n";
		//add screen resolution javascript to blog header
		if ($screen_res == "" && isset($_COOKIE['wassup_screen_res'])) {
			$screen_res = $_COOKIE['wassup_screen_res'];
			if (trim($screen_res) == "x") $screen_res="";
		}
		if (empty($screen_res) && isset($_ENV['HTTP_UA_PIXELS'])) {
			//resolution in IE/IEMobile header sometimes
			$screen_res = str_replace('X','x',$_ENV['HTTP_UA_PIXELS']);
		}
		if (empty($screen_res) && isset($_COOKIE['wassup'])) {
			$cookie_data = explode('::',attribute_escape(base64_decode(urldecode($_COOKIE['wassup']))));
			$screen_res=(!empty($cookie_data[2]))?$cookie_data[2]:"";
		}
		//if (empty($screen_res) && isset($_GET['wscr'])) {
		//	$screen_res = $_GET['wscr'];
		//}
	//Get visitor's screen resolution using javascript and a cookie.
	// - Added here so javascript code is placed in document <head> 
	//   to store this client-side only variable in a cookie that PHP
	//   can read.  -Helene D. 2009-01-19 ?>
<script type="text/javascript">
//<![CDATA[
	var screen_res = "<?php echo $screen_res; ?>";
<?php 	if (empty($screen_res) && !isset($_COOKIE['wassup_screen_res'])) { ?>
	function writeCookie(name,value,hours) {
		var the_cookie = name+"="+value+"; expires=";
		var expires = "";
		hours=hours+0; //convert to number
		if (hours > 0) { //0==expires on browser close
			var date = new Date();
			date.setTime(date.getTime()+(hours*60*60*1000));
			expires = expires+date.toGMTString();
		} 
	<?php	if (defined('COOKIE_DOMAIN')) {
			$cookiedomain = COOKIE_DOMAIN;
			$cookiepath = "/";
		} else {
			$cookieurl = parse_url(get_option('home'));
			$cookiedomain = preg_replace('/^www\./','',$cookieurl['host']);
			$cookiepath = $cookieurl['path'];
		}
		echo "\t".'document.cookie = the_cookie+expires+"; path='.$cookiepath.'; domain='.$cookiedomain.'";'."\n"; ?>
	}
	screen_res = window.screen.width+"x"+window.screen.height;
	writeCookie("wassup_screen_res",screen_res,"48"); //keep 2 days
<?php 	
	// *DISABLED*  because inflates the number of visitor page views
	// if (!isset($_GET['wscr']) && !isset($_COOKIE['wassup'])) {
	//  In case cookie is rejected, screen resolution is assigned as 
	//  GET parameter, 'wscr', and included on request line. ? >
	/* function resolution_reload() {
		if (screen_res != "") {
			var reload_url="< ? php
			if (empty($_SERVER['QUERY_STRING'])) {
				echo $_SERVER['REQUEST_URI'].'?wscr=';
			} else {
				echo $_SERVER['REQUEST_URI'].'&wscr=';
			} ? >"+screen_res;
			location.replace(reload_url);
		}
	} 
	*/
	//reload page after timeout of 40 seconds with 'wscr' param set (browsers only, no spiders)
	/* if (empty($screen_res) && preg_match('/(?:Opera|Gecko|Webkit|MSIE\s[78])/i',$_SERVER['HTTP_USER_AGENT'])>0 && !is_user_logged_in()) { ? >
	if (screen_res != "") setTimeout("resolution_reload()",40000);
	} */
	//} //end if !isset 'wscr'
	} //end if !isset('wassup_screen_res') ?>
//]]>
</script>
<?php	
	} // end if wassup_active == "1"
} //end function wassup_meta_info

//# Wassup init hook actions performed before headers are sent: 
//#   -Load jquery AJAX library and dependent javascripts for admin menus
//#   -Load language/localization files for admin menus and widget
//#   -Set 'wassup' cookie for new visitor hits when wassup is active
function wassup_init() {
	global $wpurl, $wassup_options;

	//block any obvious sql injection attempts involving WassUp -Helene D. 2009-06-16
	if (stristr($_SERVER['REQUEST_URI'],'wassup') !==FALSE || (isset($_SERVER['HTTP_REFERER']) && stristr($_SERVER['HTTP_REFERER'],'wassup') !==FALSE)) {
		if (preg_match('/[&?].+\=(\-(1|9)+|.*(select|update|delete|alter|drop|union|create)[ %&].*(?:from)?.*wp_\w+)/i',str_replace(array('\\','&#92;','"','%22','&#34;','&quot','&#39;','\'','`','&#96;'),'',$_SERVER['REQUEST_URI']))>0) {
   			header("HTTP/1.1 403 Forbidden");
			wp_die('Illegal request - Permission Denied!');
		} elseif (preg_match('/(<|&lt;|&#60;|%3C)script[^a-z0-9]/i',$_SERVER['REQUEST_URI'])>0) {
   			header("HTTP/1.1 403 Forbidden");
			wp_die('Illegal request - Permission Denied!');
		}
	}

	//### Add wassup scripts to Wassup Admin pages...
	if (!empty($_GET['page']) && stristr($_GET['page'],'wassup') !== FALSE) {
		if ( function_exists('wp_deregister_script')) {
			//removes old jquery vers.
			wp_deregister_script('jquery');	
		}
		// the safe way to load jquery into WP
		wp_register_script('jquery', WASSUPURL.'/js/jquery.js',FALSE,'1.2.6');
		if ($_GET['page'] == "wassup-spy") {
			//the safe way to load a jquery dependent script
			wp_enqueue_script('spy', WASSUPURL.'/js/spy.js', array('jquery'), '1.4');
		} elseif($_GET['page'] == "wassup-options") {
			wp_enqueue_script('ui.base', WASSUPURL.'/js/ui.base.js', array('jquery'), '3');
			wp_enqueue_script('ui.tabs', WASSUPURL.'/js/ui.tabs.js', array('jquery'), '3');
		} else {
			//the safe way to load a jquery dependent script
			wp_enqueue_script('thickbox', WASSUPURL.'/thickbox/thickbox.js', array('jquery'), '3');
		} 
	}

	//Loading language file...
	//Doesn't work if the plugin file has its own directory.
	//Let's make it our way... load_plugin_textdomain() searches only in the wp-content/plugins dir.
	$currentLocale = get_locale();
	if(!empty($currentLocale)) {
		$moFile = dirname(__FILE__) . "/language/" . $currentLocale . ".mo";
		if(@file_exists($moFile) && is_readable($moFile)) load_textdomain('wassup', $moFile);
	}

	//Set Wassup cookie for visitor hits before headers are sent
	if (!empty($wassup_options->wassup_active)) {
		wassupPrepend();
	}
} // end function wassup_init

//Add the wassup stylesheet and other javascripts...
function add_wassup_css() {
	global $wpurl, $wassup_options, $whash, $debug_mode;

	$plugin_page = $_GET['page'];
	if (stristr($plugin_page,'wassup') !== FALSE) { $plugin_page="wassup"; }
	//Add css and javascript to wassup menu pages only...
	if ($plugin_page == "wassup") {
		//assign a value to whash, if none
		if ($whash == "") {
			$whash = $wassup_options->get_wp_hash();
			$wassup_options->whash = $whash;	//save new hash
			$wassup_options->saveSettings();
		}
		//preassign "GET" parameters for "action.php" in "action_param"
		$action_param='&whash='.$wassup_options->whash;
		if ($debug_mode) {
			$action_param .= '&debug_mode=true';
		}
		//Important Note: In WordPress 2.6+ "/wp-content/" can be 
		//  located outside of Wordpress' install directory. In 
		//  this configuration, "action.php" will not run without 
		//  the additional GET parameter, "wpabspath=ABSPATH"
		if (defined('WP_CONTENT_DIR') && strpos(WP_CONTENT_DIR,ABSPATH)===FALSE) {
			//  wpabspath is encoded to hide real directory 
			//  path from users and to improve security
			$action_param .= '&wpabspath='.urlencode(base64_encode(ABSPATH));
		}

		//print the css stylesheet and javascripts
		echo "\n".'<script type="text/javascript">var tb_pathToImage = "'.WASSUPURL.'/thickbox/loadingAnimation.gif";</script>';
		echo "\n".'<link rel="stylesheet" href="'.WASSUPURL.'/thickbox/thickbox.css'.'" type="text/css" />';
		echo "\n".'<link rel="stylesheet" href="'.WASSUPURL.'/ui.tabs.css'.'" type="text/css" />';
		echo "\n".'<link rel="stylesheet" href="'.WASSUPURL.'/wassup.css'.'" type="text/css" />'."\n";

if ($_GET['page'] != "wassup-options" AND $_GET['page'] != "wassup-spy") { ?>
<script type='text/javascript'>
  //<![CDATA[
  var selftimerID = 0;
  function selfRefresh(){
 	location.href='?<?php print $_SERVER['QUERY_STRING']; ?>';
  }
  selftimerID = setTimeout('selfRefresh()', <?php print ($wassup_options->wassup_refresh * 60000)+2000; ?>);
  //]]>
</script>

<script type='text/javascript'>
  //<![CDATA[
  var _countDowncontainer="0";
  var _currentSeconds=0;
  var paused = " *<?php _e('paused','wassup'); ?>* ";
  var tickerID = 0;
  function ActivateCountDown(strContainerID, initialValue) {
  	_countDowncontainer = document.getElementById(strContainerID);
  	SetCountdownText(initialValue);
  	tickerID = window.setInterval("CountDownTick()", 1000);
  }
  function CountDownTick() {
  	if (_currentSeconds > 0) {		//don't tick below zero
    		SetCountdownText(_currentSeconds-1);
  	} else {
		clearInterval(tickerID);	//stop ticker when reach 0
		tickerID = 0;
  	}
  }
  function SetCountdownText(seconds) {
  	//store:
  	_currentSeconds = seconds;
  	//build text:
  	var strText = AddZero(seconds);
  	//apply:
  	if (_countDowncontainer) {	//prevents error in "Options" submenu
  		_countDowncontainer.innerHTML = strText;
  	}
  }
  function AddZero(num) {
  	return ((num >= "0")&&(num < 10))?"0"+num:num+"";
  }
  //]]>
</script>
<script type="text/javascript">
  //<![CDATA[
  window.onload=WindowLoad;
  function WindowLoad(event) {
  	ActivateCountDown("CountDownPanel", <?php print ($wassup_options->wassup_refresh * 60); ?>);
  }
  //]]>
</script>

<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function($){
	$("a.showhide").click(function(){
	   var id = $(this).attr('id');
	   $("div.navi" + id).toggle("slow");
	   return false;
	});
	$("a.toggleagent").click(function(){
	   var id = $(this).attr('id');
	   $("div.naviagent" + id).slideToggle("slow");
	   return false;
	});

	//show larger icons on mouse-over
	$("img.delete-icon").mouseover(function() {
		$(this).attr("src","<?php echo WASSUPURL.'/img/cross2.png'; ?>"); 
	}).mouseout(function() {
		$(this).attr("src","<?php echo WASSUPURL.'/img/cross.png'; ?>");
	}); 
	$("img.table-icon").mouseover(function() {
		$(this).attr("src","<?php echo WASSUPURL.'/img/database_table2.png'; ?>"); 
	}).mouseout(function() {
		$(this).attr("src","<?php echo WASSUPURL.'/img/database_table.png'; ?>");
	});

	$("a.deleteID").click(function(){
		var id = $(this).attr('id');
		//highlight the record being deleted
		$("div.delID" + id).css("background-color","#ffdbbd");
		$.ajax({
		  url: "<?php echo WASSUPURL.'/lib/action.php?action=deleteID'.$action_param; ?>&id=" + id,
		  async: false,
  		  success: function(html){
		  	if (html == "") 
		  		$("div.delID" + id).fadeOut("slow");
			else 
  				$("div.delID" + id).append("<small> &nbsp; &nbsp;Sorry, delete failed. " + html + "</small>");
			},
		  error: function (XMLHttpReq, txtStatus, errThrown) {
			  $("div.delID" + id).append("<small style='color:#404;'> &nbsp; &nbsp;Sorry, delete failed. " + txtStatus + ": " + errThrown + "</small>");
		  	}
		});
		return false;
	});

	$("a.show-search").toggle(function(){
	   $("div.search-ip").slideDown("slow");
	   $("a.show-search").html("<a href='#' class='show-search'><?php _e("Hide Search", "wassup") ?></a>");
	},function() {
	   $("div.search-ip").slideUp("slow");
	   $("a.show-search").html("<a href='#' class='show-search'><?php _e("Search", "wassup") ?></a>");
	   return false;
	});
	$("a.show-topten").toggle(function(){
	   $("div.topten").slideDown("slow");
	   $("a.show-topten").html("<a href='#' class='show-topten'><?php _e("Hide TopTen", "wassup") ?></a>");
	},function() {
	   $("div.topten").slideUp("slow");
	   $("a.show-topten").html("<a href='#' class='show-topten'><?php _e("Show TopTen", "wassup") ?></a>");
	   return false;
	});

	$("a.toggle-all").toggle(function() {
	   $("div.togglenavi").slideDown("slow");
	   $("a.toggle-all").html("<a href='#' class='toggle-all'><?php _e("Collapse All", "wassup") ?></a>");
	},function() {
	   $("div.togglenavi").slideUp("slow");
	   $("a.toggle-all").html("<a href='#' class='toggle-all'><?php _e("Expand All", "wassup") ?></a>");
	   return false;
	});
	$("a.toggle-allcrono").toggle(function() {
	   $("div.togglecrono").slideUp("slow");
	   $("a.toggle-allcrono").html("<a href='#' class='toggle-allcrono'><?php _e("Expand Cronology", "wassup") ?></a>");
 	},function() {
	   $("div.togglecrono").slideDown("slow");
	   $("a.toggle-allcrono").html("<a href='#' class='toggle-allcrono'><?php _e("Collapse Cronology", "wassup") ?></a>");
	   return false;
	});

	$("#CountDownPanel").click(function(){	//Pause|Resume countdown
	   var timeleft = _currentSeconds*1000;
	   if (tickerID != 0) {
	   	clearInterval(tickerID);
	   	clearTimeout(selftimerID);
		tickerID = 0;
		$(this).css('color','#999').html(paused);
	   } else {
	   	if (_currentSeconds < 1) timeleft = 1000;
	   	selftimerID = setTimeout('selfRefresh()', timeleft);
	   	tickerID = window.setInterval("CountDownTick()", 1000);
		$(this).css('color','#555');
	   }
	});
});	//end jQuery(document).ready
//]]>
</script>
<?php } //end if page != wassup-options ?>

<script type='text/javascript'>
  //<![CDATA[
  function go()
  {
  	box = document.forms["0"].navi;
  	destination = box.options[box.selectedindex].value;
  	if (destination) location.href = destination;
  }
  function go2()
  {
  	box2 = document.forms["0"].type;
  	destination2 = box2.options[box2.selectedindex].value;
  	if (destination2) location.href = destination2;
  }
  //]]>
</script>

<?php
if ($_GET['page'] == "wassup-options") {
        //#Current active tabs are indentified after page reload with 
        //#  either $_GET['tab']=N or $_POST['submit-optionsN'] where 
        //#  N=tab number. The tab is then activated directly in 
        //#  "settings.php" with <li class="ui-tabs-selected">
?>
<script type="text/javascript">
  //<![CDATA[
  jQuery(document).ready(function($) {
        $('#tabcontainer > ul').tabs({ fx: { opacity: 'toggle' } });
  });
  //]]>
</script>
<?php
} elseif ($_GET['page'] == "wassup-spy") {
	//## Filter detail lists by visitor type...
	if (isset($_GET['spytype'])) {
		$spytype = attribute_escape($_GET['spytype']);
		$wassup_options->wassup_default_spy_type = $spytype;
		$wassup_options->saveSettings(); //save changes 
	} elseif (isset($wassup_options->wassup_default_spy_type) && $wassup_options->wassup_default_spy_type != '') {
		$spytype = $wassup_options->wassup_default_spy_type;
	} else {
		$spytype="everything";
	}
?>
<script type="text/javascript">
  //<![CDATA[
  jQuery(document).ready(function($){
  	$('#spyContainer > div:gt(4)').fadeEachDown(); // initial fade
  	$('#spyContainer').spy({ 
  		limit: 10, 
  		fadeLast: 5, 
		ajax: '<?php echo WASSUPURL."/lib/action.php?action=spy&spytype=".$spytype.$action_param; ?>',
  		timeout: 5000, 
  		'timestamp': myTimestamp, 
		fadeInSpeed: 1100 });
  });
	
  function myTimestamp() {
  	var d = new Date();
  	var timestamp = d.getFullYear() + '-' + pad(d.getMonth()) + '-' + pad(d.getDate());
  	timestamp += ' ';
  	timestamp += pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
  	return timestamp;
  }

  // pad ensures the date looks like 2006-09-13 rather than 2006-9-13
  function pad(n) {
  	n = n.toString();
  	return (n.length == 1 ? '0' + n : n);
  }

  //]]>
</script>
<?php } //end if page == "wassup-spy"
} //end if plugin_page == "wassup"
} //end function add_wassup_css()

//put WassUp in the top-level admin menu and add submenus....
function wassup_add_pages() {
	global $wassup_options;
	$userlevel = $wassup_options->wassup_userlevel;
	if (empty($userlevel)) { $userlevel = 8; }
	// add the default submenu first (important!)...
	add_submenu_page(WASSUPFOLDER, __('Visitor Details', 'wassup'), __('Visitor Details', 'wassup'), $userlevel, WASSUPFOLDER, 'WassUp'); //<-- WASSUPFOLDER needed here for directory names that include a version number...
	// then add top menu and other submenus...
	add_menu_page('Wassup', 'WassUp', $userlevel, WASSUPFOLDER, 'Wassup');
	add_submenu_page(WASSUPFOLDER, __('Spy Visitors', 'wassup'), __('SPY Visitors', 'wassup'), $userlevel, 'wassup-spy', 'WassUp');
	add_submenu_page(WASSUPFOLDER, __('Current Visitors Online', 'wassup'), __('Current Visitors Online', 'wassup'), $userlevel, 'wassup-online', 'WassUp');
	add_submenu_page(WASSUPFOLDER, __('Options', 'wassup'), __('Options', 'wassup'), $userlevel, 'wassup-options', 'WassUp');
}

function WassUp() {
        global $wpdb, $wp_version, $wassupversion, $wpurl, $wassup_options, $whash, $debug_mode;
	
	// Start getting time of execution to debug SQL query
	$starttime = microtime_float();

	//#debug...
	if ($debug_mode) {
		$mode_reset=ini_get('display_errors');
		//error_reporting(E_ALL | E_STRICT);	//debug, E_STRICT=php5 only
		error_reporting(E_ALL);	//debug
		ini_set('display_errors','On');	//debug
		echo "\n<!-- *WassUp DEBUG On-->\n";
		echo "<!-- *normal setting: display_errors=$mode_reset -->\n";
	}
	if ( !ini_get('safe_mode')) {	//extend php script timeout length
		@set_time_limit(2*60); 	//  ...to 2 minutes
	}
	//for generating page link urls....
	//$wpurl =  get_bloginfo('wpurl');	//global
	//$siteurl =  get_bloginfo('siteurl');	//now blogurl
	$table_name = $wassup_options->wassup_table;
	$table_tmp_name = $table_name."_tmp";

	//"action_param" are preassigned "GET" parameters used for "action.php" external/ajax calls like "top ten" 
	$action_param='&whash='.$wassup_options->whash;
	if ($debug_mode) {
		$action_param .= '&debug_mode=true';
	}
	//wpabspath param required for non-standard wp-content directory location
	if (defined('WP_CONTENT_DIR') && strpos(WP_CONTENT_DIR,ABSPATH)===FALSE) {
		$action_param .= '&wpabspath='.urlencode(base64_encode(ABSPATH));
	}
	$wassup_options->loadSettings();	//needed in case "update_option is run elsewhere in wassup (widget)

	// RUN THE SAVE/RESET OPTIONS
	$admin_message="";
	if (isset($_POST['submit-options']) || 
	    isset($_POST['submit-options2']) || 
	    isset($_POST['submit-options3'])) {
		if (!empty($_POST['wassup_remind_flag'])) {
			$wassup_options->wassup_remind_flag = $_POST['wassup_remind_flag'];
			if (!empty($_POST['wassup_remind_mb']) ) {
				$wassup_options->wassup_remind_mb = $_POST['wassup_remind_mb'];
			} else {
				$wassup_options->wassup_remind_mb = 10;
			}
		}
		$wassup_options->wassup_active = $_POST['wassup_active'];
		$wassup_options->wassup_chart_type = $_POST['wassup_chart_type'];
		if ((int)$_POST['wassup_chart_type'] == 0) {	//no chart
			$wassup_options->wassup_chart = "0";
		}
		$wassup_options->wassup_loggedin = $_POST['wassup_loggedin'];
		$wassup_options->wassup_admin = $_POST['wassup_admin'];
		$wassup_options->wassup_spider = $_POST['wassup_spider'];
		$wassup_options->wassup_attack = $_POST['wassup_attack'];
		$wassup_options->wassup_spamcheck = $_POST['wassup_spamcheck'];
		$wassup_options->wassup_spam = $_POST['wassup_spam'];
		$wassup_options->wassup_refspam = $_POST['wassup_refspam'];
		$wassup_options->wassup_hack = $_POST['wassup_hack'];
		$wassup_options->wassup_exclude = attribute_escape($_POST['wassup_exclude']);
		$wassup_options->wassup_exclude_url = attribute_escape($_POST['wassup_exclude_url']);
		$wassup_options->wassup_exclude_user = attribute_escape($_POST['wassup_exclude_user']);
		$wassup_options->delete_auto = $_POST['delete_auto'];
		if (isset($_POST['delete_auto_size'])) {
			$wassup_options->delete_auto_size = $_POST['delete_auto_size'];
		}
		$wassup_options->wassup_screen_res = $_POST['wassup_screen_res'];
		$wassup_options->wassup_refresh = $_POST['wassup_refresh'];
		$wassup_options->wassup_userlevel = $_POST['wassup_userlevel'];
		$wassup_options->wassup_dashboard_chart = $_POST['wassup_dashboard_chart'];
		$wassup_options->wassup_geoip_map = $_POST['wassup_geoip_map'];
		if (!empty($_POST['wassup_googlemaps_key'])) {	//don't clear geoip key
			$wassup_options->wassup_googlemaps_key = $_POST['wassup_googlemaps_key'];
		}
		$wassup_options->wassup_time_format = $_POST['wassup_time_format'];
		$wassup_options->wassup_default_type = $_POST['wassup_default_type'];
		$wassup_options->wassup_default_limit = $_POST['wassup_default_limit'];
		$top_ten = array("topsearch" => $_POST['topsearch'],
				"topreferrer" => $_POST['topreferrer'],
				"toprequest" => $_POST['toprequest'],
				"topbrowser" => $_POST['topbrowser'],
				"topos" => $_POST['topos'],
				"toplocale" => (isset($_POST['toplocale'])?$_POST['toplocale']:"0"),
				"topvisitor" => (isset($_POST['topvisitor'])?$_POST['topvisitor']:"0"),
				"topfeed" => "0",
				"topcrawler" => "0",
				"topreferrer_exclude" => $_POST['topreferrer_exclude']);
		$wassup_options->wassup_top10 = attribute_escape(serialize($top_ten));
		if ($wassup_options->saveSettings()) {
			$admin_message = __("Wassup options updated successfully","wassup")."." ;
		}
	} elseif (isset($_POST['submit-options4'])) {	//uninstall checkbox
		if (!empty($_POST['wassup_uninstall'])) {
			$wassup_options->wassup_uninstall = $_POST['wassup_uninstall'];
		} else {
			$wassup_options->wassup_uninstall = "0";
		}
		if ($wassup_options->wassup_uninstall == "1") {
			$wassup_options->wassup_active = "0"; //disable recording now
		}
		if ($wassup_options->saveSettings()) {
			$admin_message = __("Wassup uninstall option updated successfully","wassup")."." ;
		}
	} elseif (isset($_POST['submit-spam'])) {
		$wassup_options->wassup_spamcheck = $_POST['wassup_spamcheck'];
		$wassup_options->wassup_spam = $_POST['wassup_spam'];
		$wassup_options->wassup_refspam = $_POST['wassup_refspam'];
		$wassup_options->wassup_hack = $_POST['wassup_hack'];
		if ($wassup_options->saveSettings()) {
			$admin_message = __("Wassup spam options updated successfully","wassup")."." ;
		}
	} elseif (isset($_POST['reset-to-default'])) {
		$wassup_options->loadDefaults();
		if ($wassup_options->saveSettings()) {
			$admin_message = __("Wassup options updated successfully","wassup")."." ;
		}
	}

	//#sets current tab style for Wassup admin submenu?
	if ($_GET['page'] == "wassup-spy") {
		$class_spy="class='current'";
	} elseif ($_GET['page'] == "wassup-options") {
		$class_opt="class='current'";
	} elseif ($_GET['page'] == "wassup-online") {
		$class_ol="class='current'";
	} else {
		$class_sub="class='current'";
	}

	//for stringShortener calculated values and max-width...-Helene D. 11/27/07, 12/6/07
	if (!empty($wassup_options->wassup_screen_res)) {
		$screen_res_size = (int) $wassup_options->wassup_screen_res;
	} else { 
		$screen_res_size = 670;
	}
	$max_char_len = ($screen_res_size)/10;
	$screen_res_size = $screen_res_size+20; //for wrap margins...
	$wrapstyle = "style='margin:0 auto; padding:0 15px; max-width:".$screen_res_size."px;'";

	//for wassup chart size
	$res = (int) $wassup_options->wassup_screen_res;
	if (empty($res)) $res=620;
	elseif ($res < 800) $res=620;
	elseif ($res < 1024) $res=740;
	elseif ($res < 1200) $res=1000;
	else $res=1000;

	//Some Wordpress 2.7-specific style adjustments
	if (version_compare($wp_version, '2.7', '>=')) { 
		//set smaller chart size and screen_res to make room for new sidebar in WP2.7+
		$screen_res_size = $screen_res_size-160;
		$max_char_len = $max_char_len-16;
		$res = $res-120;
		//$wrapstyle = "style='margin:0 auto;padding: 0 30px 0 20px;'";	//use wordpress style/auto width
		$wrapstyle = "style='width:95%;margin:0 auto;padding: 0 30px 0 20px'"; //for ie6

	//Restore horizontal menus in Wordpress 2.7 by adding WassUp 
	//   submenus to context menus- for easier menu navigation (no need
	//   to scroll down to see menus). Tested in ie6, ie7, ff1.5, ff2,
	//   ff3, safari 1-3. -Helene D. 2009-03-09
	?>
	<style type="text/css">
	#wassup-menu { 
		display: inline;
		position: relative;
	}
	#wassup-menu a, #wassup-menu a.link {
		text-decoration: none;
		z-index: 1;
		margin: 0 auto;
		padding: 0 6px 0 6px;
		height: 22px;
		line-height: 22px;
		font-size: 10px;
		background-repeat: no-repeat;
		background-position: right bottom;
	}
	#wassup-menu a:visited { color: #e61; }
	#wassup-menu a:hover { color: #db5; }
	.wassup-menu-link {
		float: right;
		background: transparent url(images/screen-options-left.gif ) no-repeat 0 0;
		font-family: "Lucida Grande", Verdana, Arial, "Bitstream Vera Sans", sans-serif;
		height: 22px;
		padding: 0;
		margin: 0 6px 0 0;
		text-decoration: none;
		text-align:center;
	}
	</style>
	
	<ul id="wassup-menu">
		<li class="wassup-menu-link"><a href="?page=wassup-options">Options</a></li>
		<li class="wassup-menu-link"><a href="?page=wassup-online">Current Visitors Online</a></li>
		<li class="wassup-menu-link"><a href="?page=wassup-spy">SPY Visitors</a></li>
		<li class="wassup-menu-link"><a href="?page=<?php echo WASSUPFOLDER; ?>">Visitor Details</a></li>
	</ul><div style="clear:right;"></div>
<?php	} //end if version_compare(2.7)

	//#display an admin message or an alert. This must be above "wrap"
	//# div, but below wassup menus -Helene D. 2008-2-26, 2009-3-08
	if (!empty($admin_message)) {
		$wassup_options->showMessage($admin_message);
	} elseif (!empty($wassup_options->wassup_alert_message)) {
		$wassup_options->showMessage();
		//#show alert message only once, so remove it here...
		$wassup_options->wassup_alert_message = "";
		$wassup_options->saveSettings();
	} elseif ($wassup_options->wassup_active != "1") {
		//display as a system message when not recording...
	    	$admin_message = __("Warning","wassup").": WassUp ".__("is NOT recording new statistics","wassup").". ".__('To collect data you must check "Enable/Disable recording" in "Options: Statistics Recording" tab','wassup');
		$wassup_options->showMessage($admin_message);
	} ?>
	<div id="wassup-wrap" class="wrap" <?php echo $wrapstyle; ?>>
	<div id="icon-plugins" class="icon32 wassup-icon"></div>
<?php

	// HERE IS THE VISITORS ONLINE VIEW
	if ($_GET['page'] == "wassup-online") { ?>
		<h2>WassUp - <?php _e("Current Visitors Online", "wassup"); ?></h2>
		<p class="legend"><?php echo __("Legend", "wassup").': <span class="box-log">&nbsp;&nbsp;</span> '.__("Logged-in Users", "wassup").' <span class="box-aut">&nbsp;&nbsp;</span> '.__("Comments Authors", "wassup").' <span class="box-spider">&nbsp;&nbsp;</span> '.__("Spiders/bots", "wassup"); ?></p><br />
		<p class="legend"><a href="#" class="toggle-all"><?php _e("Expand All","wassup"); ?></a></p>
		<?php
		$to_date = wassup_get_time();
		$from_date = strtotime('-3 minutes', $to_date);
		$currenttot = $wpdb->get_var("SELECT COUNT(DISTINCT wassup_id) as currenttot FROM $table_tmp_name WHERE `timestamp` BETWEEN $from_date AND $to_date");
		$currenttot = $currenttot+0;	//set to integer
		print "<p class='legend'>".__("Visitors online", "wassup").": <strong>".$currenttot."</strong></p><br />";
		if ($currenttot > 0) {
			$qryC = $wpdb->get_results("SELECT id, wassup_id, max(timestamp) as max_timestamp, ip, hostname, searchengine, urlrequested, agent, referrer, spider, username, comment_author FROM $table_tmp_name WHERE `timestamp` BETWEEN $from_date AND $to_date GROUP BY ip ORDER BY max_timestamp DESC");
		foreach ($qryC as $cv) {
		if ($wassup_options->wassup_time_format == 24) {
			$timed = gmdate("H:i:s", $cv->max_timestamp);
		} else {
			$timed = gmdate("h:i:s a", $cv->max_timestamp);
		}
		$ip_proxy = strpos($cv->ip,",");
		//if proxy, get 2nd ip...
		if ($ip_proxy !== false) {
			$ip = substr($cv->ip,(int)$ip_proxy+1);
		} else { 
			$ip = $cv->ip;
		}
		if ($cv->referrer != '') {
			if ($cv->searchengine != "" || stristr($cv->referrer,$wpurl)!=$cv->referrer) { 
				if ($cv->searchengine == "") {
					$referrer = '<a href="'.wCleanURL($cv->referrer).'" target=_"BLANK"><span style="font-weight: bold;">'.stringShortener("{$cv->referrer}", round($max_char_len*.8,0)).'</span></a>';
				} else {
					$referrer = '<a href="'.wCleanURL($cv->referrer).'" target=_"BLANK">'.stringShortener("{$cv->referrer}", round($max_char_len*.9,0)).'</a>';
				}
			} else { 
				$referrer = __("From your blog", "wassup"); 
			} 
		} else { 
			$referrer = __("Direct hit", "wassup"); 
		} 
		$numurl = (int) $wpdb->get_var("SELECT COUNT(DISTINCT id) as numurl FROM $table_tmp_name WHERE wassup_id='".$cv->wassup_id."'");
?>
			<div class="sum">
			<span class="sum-box"><?php if ($numurl >= 2) { ?><a  href="#" class="showhide" id="<?php echo $cv->id ?>"><?php print $ip; ?></a><?php } else { ?><?php print $ip; ?><?php } ?></span>
			<div class="sum-det"><span class="det1">
			<?php
			if (strstr($cv->urlrequested,'[404]')) {  //no link for 404 page
				print stringShortener($cv->urlrequested, round($max_char_len*.9,0)+5);
			} else {
				print '<a href="'.wAddSiteurl("{$cv->urlrequested}").'" target="_BLANK">';
				print stringShortener("{$cv->urlrequested}", round($max_char_len*.9,0)).'</a>';
			} ?>
			</span><br />
			<span class="det2"><strong><?php print $timed; ?> - </strong><?php print $referrer ?></span></div>
			</div>
<?php 		// User is logged in or is a comment's author
		if ($cv->username != "" OR $cv->comment_author != "") {
				if ($cv->username != "") {
					$Ousername = '<li class="users"><span class="indent-li-agent">'.__("LOGGED IN USER", "wassup").': <strong>'.$cv->username.'</strong></span></li>'; 
					$Ocomment_author = '<li class="users"><span class="indent-li-agent">'.__("COMMENT AUTHOR", "wassup").': <strong>'.$cv->comment_author.'</strong></span></li>'; 
					$unclass = "userslogged";
				} elseif ($cv->comment_author != "") {
					$Ocomment_author = '<li class="users"><span class="indent-li-agent">'.__("COMMENT AUTHOR", "wassup").': <strong>'.$cv->comment_author.'</strong></span></li>'; 
					$unclass = "users";
				}
?>
			<ul class="<?php print $unclass; ?>">
				<?php print $Ousername; ?>
				<?php print $Ocomment_author; ?>
			</ul>
<?php		} 
		if ($numurl >1) { ?>
			<div style="display: none;" class="togglenavi navi<?php echo $cv->id ?>">
			<ul class="url">
	<?php 
			$qryCD = $wpdb->get_results("SELECT `timestamp`, urlrequested FROM $table_tmp_name WHERE wassup_id='".$cv->wassup_id."' ORDER BY `timestamp` ASC");
			$i=1;
			foreach ($qryCD as $cd) {	
			$time2 = gmdate("H:i:s", $cd->timestamp);
			$num = ($i&1);
			$char_len = round($max_char_len*.9,0);
			if ($num == 0) $classodd = "urlodd"; else  $classodd = "url";
			if ($i < $numurl) { ?>
				<li class="<?php print $classodd; ?> navi<?php echo $cv->id ?>"><span class="indent-li"><?php print $time2; ?> - 
<?php
				if (strstr($cd->urlrequested,'[404]')) {  //no link for 404 page
					print stringShortener($cd->urlrequested, $char_len);
				} else {
					print '<a href="'.wAddSiteurl("{$cd->urlrequested}").'" target="_BLANK">';
					print stringShortener("{$cd->urlrequested}", $char_len).'</a>'."\n";
				} ?>
				</span></li>
<?php			}
			$i++;
			} //end foreach qryCD ?>
			</ul>
			</div>
			<p class="sum-footer"></p>
<?php		} //end if numurl
		} //end foreach qryC
		} //end if currenttot ?>
	<br /><p class="legend"><a href="#" class="toggle-all"><?php _e("Expand All", "wassup"); ?></a></p>

<?php	// HERE IS THE SPY MODE VIEW
	} elseif ($_GET['page'] == "wassup-spy") { ?>
		<h2>WassUp - <?php _e("SPY Visitors", "wassup"); ?></h2>
		<p class="legend"><?php echo __("Legend", "wassup").': <span class="box-log">&nbsp;&nbsp;</span> '.__("Logged-in Users", "wassup").' <span class="box-aut">&nbsp;&nbsp;</span> '.__("Comments Authors", "wassup").' <span class="box-spider">&nbsp;&nbsp;</span> '.__("Spiders/bots", "wassup"); ?></p><br />
		<div>
		<a href="#?" onclick="return pauseSpy();"><span id="spy-pause"><?php _e("Pause", "wassup"); ?></span></a>
		<a href="#?" onclick="return playSpy();"><span id="spy-play"><?php _e("Play", "wassup"); ?></span></a>
                - <span style="font-size: 11px;"><?php _e('Spy items by','wassup'); ?>: <select name="navi" style="font-size: 11px;" onChange="window.location.href=this.options[this.selectedIndex].value;">
                <?php
                //## selectable filter by type of record (wassup_default_spy_type)
		if (isset($_GET['spytype'])) {
			$spytype = attribute_escape($_GET['spytype']);
		} elseif ($wassup_options->wassup_default_spy_type != '') {
			$spytype = $wassup_options->wassup_default_spy_type;
		} else {
			$spytype="everything";
		}
                $selected=$spytype;
                $optionargs="?page=wassup-spy&spytype=";
                $wassup_options->showFormOptions("wassup_default_spy_type","$selected","$optionargs");
                ?>
                </select>
                </span>
		<br />&nbsp;<br /></div>

	<?php // GEO IP Map
	if ($wassup_options->wassup_geoip_map == 1 AND $wassup_options->wassup_googlemaps_key != "") { ?>
		<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=<?php echo $wassup_options->wassup_googlemaps_key; ?>" type="text/javascript"></script>
		<div id="map" style="width: <?php echo ($screen_res_size*95/100); ?>px; height: 220px;border:2px solid #999;"></div>
		    <script type="text/javascript">
		    //<![CDATA[
		    if (GBrowserIsCompatible()) { 
		      // Display the map, with some controls and set the initial location 
		      var map = new GMap2(document.getElementById("map"));
		      map.addControl(new GSmallMapControl());
		      map.addControl(new GMapTypeControl());
		      //map.enableScrollWheelZoom();
		      map.setCenter(new GLatLng(0,0),3);
		    }
		    // display a warning if the browser was not compatible
		    else {
		      alert("Sorry, the Google Maps API is not compatible with this browser");
		    }
		    //]]>
		    </script>
		<p>&nbsp;</p>
	<?php } //end if geoip_map 
	?>
		<div id="spyContainer">
		<?php 
		//display the last few hits here. The rest will be added by spy.js
		$to_date = (wassup_get_time()-2);
		$from_date = ($to_date - 12*(60*60)); //display last 10 visits in 12 hours...
		spyview($from_date,$to_date,10,$spytype,$table_name); ?>
		</div><br />

<?php	// HERE IS THE OPTIONS VIEW
	} elseif($_GET['page'] == "wassup-options") { ?>
		<h2>WassUp - <?php _e('Options','wassup'); ?></h2>
		<p><?php _e('You can add a sidebar Widget with some useful statistics information by activating the','wassup'); ?>
		<a href="<?php echo $wpurl.'/wp-admin/widgets.php'; ?>"><?php _e('Wassup Widget in the Widgets menu option','wassup'); ?></a>.</p>
<?php		//#moved content to external include file, "settings.php"
		//#  to make "wassup" code easier to read and modify 
		//#  -Helene D. 1/15/08.
		include(dirname(__FILE__).'/lib/settings.php'); ?>

<?php	// HERE IS THE MAIN/DETAILS VIEW
	} else { ?>
		<h2>WassUp - <?php _e("Latest hits", "wassup"); ?></h2>
		<?php if ($wassup_options->wassup_active != 1) { ?>
			<p style="color:red; font-weight:bold;"><?php _e("WassUp recording is disabled", "wassup"); ?></p>
		<?php }

		//## GET parameters that change options settings
		if (isset($_GET['wchart']) || isset($_GET['wmark'])) { 
			if (isset($_GET['wchart'])) { // [0|1] only
			if ($_GET['wchart'] == 0) {
				$wassup_options->wassup_chart = 0;
			} elseif ($_GET['wchart'] == 1) {
				$wassup_options->wassup_chart = 1;
			}
			}
			if (isset($_GET['wmark'])) { // [0|1] only
			if ($_GET['wmark'] == 0) {
                		$wassup_options->wmark = "0";
				$wassup_options->wip = "";
			} elseif ($_GET['wmark'] == 1 && isset($_GET['wip'])) {
				$wassup_options->wmark = "1";
				$wassup_options->wip = attribute_escape($_GET['wip']);
			}
			}
			$wassup_options->saveSettings();
		}

		//## GET params that filter detail display
		$stickyFilters=""; //filters that remain in effect after page reloads
		//
		//## Filter detail list by date range...
		$to_date = wassup_get_time();
		if (isset($_GET['last']) && is_numeric($_GET['last'])) { 
			$last = $_GET['last'];
			$stickyFilters.='&last='.$last;
		} else {
			$last = 1; 
		}
		if ($last == 0) {
			$from_date = "0";	//all time
		} elseif ($last < 2) {
			$from_date = strtotime('-'.(int)($last*24).' hours', $to_date);
		} elseif ($last < 30) {
			$from_date = strtotime('-'.$last.' days', $to_date);
		} else {
			$from_date = strtotime('-'.(int)($last/30).' months', $to_date);
		}

		//## Filter detail lists by visitor type...
		if (isset($_GET['type'])) {
			$type = attribute_escape($_GET['type']);
			$stickyFilters.='&type='.$type;
		} elseif ($wassup_options->wassup_default_type != '') {
			$type = $wassup_options->wassup_default_type;
		}
		$whereis="";
		if ($type == 'spider') {
			$whereis = " AND spider!=''";
		} elseif ($type == 'nospider') {
			$whereis = " AND spider=''";
		} elseif ($type == 'spam') {
			$whereis = " AND spam>0";
		} elseif ($type == 'nospam') {
			$whereis = " AND spam=0";
		} elseif ($type == 'nospamspider') {
			$whereis = " AND spam=0 AND spider=''";
		} elseif ($type == 'searchengine') {
			$whereis = " AND searchengine!='' AND search!=''";
		} elseif ($type == 'referrer') {
			$whereis = " AND referrer!='' AND referrer NOT LIKE '%$wpurl%' AND searchengine='' AND search=''";
		} elseif ($type == 'comauthor') {
			$whereis = " AND comment_author!=''";
		} elseif ($type == 'loggedin') {
			$whereis = " AND username!=''";
		}

		//## Filter detail lists by a specific page and number
		//#  of items per page...
		$items = 10;	//default
		if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
			//$items = htmlentities(attribute_escape($_GET['limit'])); 
			$items = (int)$_GET['limit']; 
		} elseif ($wassup_options->wassup_default_limit != '') {
			$items = $wassup_options->wassup_default_limit;
		}
		if ((int)$items < 1 ) { $items = 10; }
		//# current page selections
		if (isset($_GET['pages']) && is_numeric($_GET['pages'])) {
			$pages = (int)$_GET['pages'];
		} else {
			$pages = 1;
		}
		if ( $pages > 1 ) {
			$limit = " LIMIT ".(($pages-1)*$items).",$items";
		} else {
			$limit = " LIMIT $items";
		}

		//## Filter detail lists by a searched item
                if (!empty($_GET['search'])) { 
                        $search = attribute_escape(strip_tags(html_entity_decode($_GET['search'])));
                } else {
                        $search = "";
                }

                // DELETE EVERY RECORD MARKED BY IP
		//#  Delete limited to selected date range only. -Helene D. 3/4/08.
		if (!empty($_GET['deleteMARKED']) && $wassup_options->wmark == "1" && !empty($_GET['dip'])) {
                        $del_count = $wpdb->get_var("SELECT COUNT(ip) as deleted FROM $table_name WHERE ip='".attribute_escape($_GET['dip'])."' AND `timestamp` BETWEEN $from_date AND $to_date");
                        if (method_exists($wpdb,'prepare')) {
                                $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE ip='%s' AND `timestamp` BETWEEN %s AND %s", $_GET['dip'], $from_date, $to_date));
                        } else {
                                $wpdb->query("DELETE FROM $table_name WHERE ip='".attribute_escape($_GET['dip'])."' AND `timestamp` BETWEEN $from_date AND $to_date");
                        }
                        $rec_count = $wpdb->get_var("SELECT COUNT(ip) as deleted FROM $table_name WHERE ip='".attribute_escape($_GET['dip'])."' AND `timestamp` BETWEEN $from_date AND $to_date");	//double-check deletions
			$rec_deleted = ($del_count - $rec_count)." ".__('records deleted','wassup');
			$wassup_options->showMessage($rec_deleted);
                        //echo '<p><strong>'.$rec_deleted.' '.__('records deleted','wassup').'</strong></p>';
			//reset wmark/deleteMarked after delete
                        $wassup_options->wmark = "0";
                        $wassup_options->wip = null;
                        $wassup_options->saveSettings();
                } //end if deleteMARKED
		//to prevent browser timeouts, send <!--heartbeat--> output
		echo "<!--heartbeat-->\n";

		// Instantiate class to count items
		$Tot = New MainItems($table_name,$from_date,$to_date,$whereis,$limit);
		$Tot->whereis = $whereis;
		$Tot->Limit = $limit;
		$Tot->WpUrl = $wpurl;
		echo "<!--heartbeat-->\n";

		// MAIN QUERY
		$main = $Tot->calc_tot("main", $search);
		echo "<!--heartbeat-->\n";

		$itemstot = $Tot->calc_tot("count", $search, null, "DISTINCT");
		echo "<!--heartbeat-->\n";
		$pagestot = $Tot->calc_tot("count", $search);
		echo "<!--heartbeat-->\n";
		$spamtot = $Tot->calc_tot("count", $search, "AND spam>0");
		// Check if some records was marked
		if ($wassup_options->wmark == "1") {
			$markedtot = $Tot->calc_tot("count", $search, "AND ip LIKE '%".$wassup_options->wip."%'", "DISTINCT");
		}
		echo "<!--heartbeat-->\n";
		// Check if some records were searched
		if (!empty($search)) {
			$searchtot = $Tot->calc_tot("count", $search, null, "DISTINCT");
		} ?>

		<form><table width="100%">
		<tr>
		<td>
		<p class="legend">
		<?php 
		//#  remove any delete request from $_SERVER['QUERY_STRING'] 
		//#  clear non-sticky filter parameters before applying new filters 
		if (isset($_GET['deleteMARKED']) && isset($_GET['dip'])) {
			$remove_it= array('&deleteMARKED='.$_GET['deleteMARKED'],'&wmark=1','&dip='.$_GET['dip']);
		} else {
			$remove_it = array();
		}
		if (isset($_GET['wchart'])) {
			$remove_it[] = '&wchart='.$_GET['wchart'];
		}
		if (!empty($remove_it)) {
			$URLQuery = str_replace($remove_it,"",$_SERVER['QUERY_STRING']);
			$_SERVER['QUERY_STRING'] = $URLQuery; //in case of auto refresh
		} else {
			$URLQuery = $_SERVER['QUERY_STRING'];
		}
		//chart options
		if ($wassup_options->wassup_chart == "1") {  ?>
			<a href="?<?php echo attribute_escape($URLQuery.'&wchart=0'); ?>" style="text-decoration:none;">
			<img src="<?php echo WASSUPURL.'/img/chart_delete.png" style="padding:0px 6px 0 0;" alt="'.__('hide chart','wassup').'" title="'.__('Hide the chart','wassup'); ?>" /></a>
		<?php } else { ?>
			<a href="?<?php echo attribute_escape($URLQuery.'&wchart=1'); ?>" style="text-decoration:none;">
			<img src="<?php echo WASSUPURL.'/img/chart_add.png" style="padding:0px 6px 0 0;" alt="'.__('show chart','wassup').'" title="'.__('Show the chart','wassup'); ?>" /></a>
		<?php }

		//## Show selectable detail filters...
		//selectable filter by date range
		if (isset($_GET['last'])) {
			$new_last = str_replace("&last=".$_GET['last'],"", $URLQuery);
		} else {
			$new_last = $URLQuery;
		}
		_e('Summary for the last','wassup'); ?>
		<select style="font-size: 11px;" name="last" onChange="window.location.href=this.options[this.selectedIndex].value;">
		<?php 
		$optionargs="?".attribute_escape($new_last."&last=");
		$wassup_options->showFormOptions("wassup_time_range","$last","$optionargs"); ?>
		</select></p>
		</td>
		<td align="right"><p style="font-size: 11px;"><?php _e('Items per page','wassup'); ?>: <select name="navi" style="font-size: 11px;" onChange="window.location.href=this.options[this.selectedIndex].value;">
                <?php
                //selectable filter by number of items on page (default_limit)
		if (isset($_GET['limit'])) {
			$new_limit = attribute_escape(str_replace("&limit=".$_GET['limit'], "", html_entity_decode($URLQuery)));
		} else { $new_limit = $URLQuery; }
                $selected=$items;
                $optionargs="?".$new_limit."&limit=";
                $wassup_options->showFormOptions("wassup_default_limit","$selected","$optionargs");
                ?>
                </select> - <?php _e('Show items by','wassup'); ?>: <select style="font-size: 11px;" name="type" onChange="window.location.href=this.options[this.selectedIndex].value;">
                <?php
                //selectable filter by type of record (wassup_default_type)
                $selected=$type;
		$filter_args=str_replace("&type=$type","",$stickyFilters);
		$optionargs="?page=".WASSUPFOLDER.$filter_args."&type=";
                $wassup_options->showFormOptions("wassup_default_type","$selected","$optionargs");
                ?>
                </select>
                </p>
                </td>
                </tr>
                </table>
                </form>

		<?php // Print Site Usage ?>
        <div class='main-tabs'>
                <div id='usage'>
                        <ul>
                        <li><span style="border-bottom:2px solid #0077CC;"><?php echo $itemstot; ?></span> <small><?php _e('Visits','wassup'); ?></small></li>
                        <li><span style="border-bottom:2px dashed #FF6D06;"><?php echo $pagestot; ?></span> <small><?php _e('Pageviews','wassup'); ?></small></li>
                        <li><span><?php echo @number_format(($pagestot/$itemstot), 2); ?></span> <small><?php _e('Pages/Visits','wassup'); ?></small></li>
                        <?php // Print spam usage only if enabled
			if ($wassup_options->wassup_spamcheck == 1) { ?>
			<li><span><a href="#TB_inline?height=180&width=300&inlineId=hiddenspam" class="thickbox"><?php echo $spamtot; ?></a></span> <span>(<?php echo @number_format(($spamtot*100/$pagestot), 2); ?>%)</span> <small><?php _e('Spams','wassup'); ?></small></li>
			<?php } ?>
			</ul>
		</div>
	</div>
		<?php 
		//if (!isset($_GET['limit']) OR $_GET['limit'] == 10 OR $_GET['limit'] == 20) { 
			$expcol = '
		<table width="100%"><tr>
		<td align="left" class="legend"><a href="#" class="toggle-all">'.__('Expand All','wassup').'</a></td>
		<td align="right" class="legend"><a href="#" class="toggle-allcrono">'.__('Collapse Chronology','wassup').'</a></td>
		</tr></table><br />';
		//}
		
		// Page breakdown
		// paginate only when total records > items per page
		if ($itemstot > $items) {
		$p=new pagination();
		$p->items($itemstot);
		$p->limit($items);
		$p->currentPage($pages);
		$p->target("admin.php?page=".WASSUPFOLDER."&limit=$items&type=$type&last=$last&search=$search");
		echo "<!--heartbeat-->\n";
		$p->calculate();
		$p->adjacents(5);
		}

		// hidden spam options
                ?>
                <div id="hiddenspam" style="display:none;">
        <h2><?php _e('Spam Options','wassup'); ?></h2>
        <form action="" method="post">
	<p><input type="checkbox" name="wassup_spamcheck" value="1" <?php if($wassup_options->wassup_spamcheck == 1 ) print "CHECKED"; ?> /> <strong><?php _e('Enable/Disable Spam Check on Records','wassup'); ?></strong></p>
        <p style="padding-left:30px;"><input type="checkbox" name="wassup_spam" value="1" <?php if($wassup_options->wassup_spam == 1) print "CHECKED"; ?> /> <?php _e('Record Akismet comment spam attempts','wassup'); ?></p>
        <p style="padding-left:30px;"><input type="checkbox" name="wassup_refspam" value="1" <?php if($wassup_options->wassup_refspam == 1) print "CHECKED"; ?> /> <?php _e('Record referrer spam attempts','wassup'); ?></p>
        <p style="padding-left:30px;"><input type="checkbox" name="wassup_hack" value="1" <?php if($wassup_options->wassup_hack == 1) print "CHECKED"; ?> /> <?php _e("Record admin break-in/hacker attempts", "wassup") ?></p>
        <p style="padding-left:0;"><input type="submit" name="submit-spam" value="<?php _e('Save Settings','wassup'); ?>" /></p>
        </form>
                </div>
                <table width="100%">
                <tr>
                <td align="left" class="legend">
                <?php
		// Marked items - Refresh
		if ($wassup_options->wmark == 1) {
			echo '<a href="?'.attribute_escape($URLQuery.'&search='.$wassup_options->wip).'" title="'.__('Filter by marked IP','wassup').'"><strong>'.$markedtot.'</strong> '.__('show marked items','wassup').'</a> - ';
		}
		if (!empty($search)) { 
			print "<strong>$searchtot</strong> ".__('Searched for','wassup').": <strong>$search</strong> - ";
		}
		echo __('Auto refresh in','wassup').'&nbsp;<span id="CountDownPanel"></span>&nbsp;'.__('seconds','wassup');

		 ?>
		</td>
		<td align="right" class="legend"><a href="<?php echo wCleanURL(WASSUPURL.'/lib/action.php?action=topten&from_date='.$from_date.'&to_date='.$to_date.$action_param.'&width='.$res.'&height=400','','url'); ?>" class="thickbox" title="Wassup <?php _e('Top Ten','wassup'); ?>"><?php _e('Show Top Ten','wassup'); ?></a> - <a href="#" class='show-search'><?php _e('Search','wassup'); ?></a></td>
                </tr>
                </table>
<div class="search-ip" style="display: none;">
	<table border=0 width="100%">
		<tr valign="top">
		<td align="right">
		<form action="" method="get">
		<input type="hidden" name="page" value="<?php echo WASSUPFOLDER; ?>" />
<?php
		$filterargs=str_replace('&type='.$type,'',$stickyFilters);
		if (!empty($filterargs)) {
			$filters=explode('&',$filterargs);
			foreach ($filters AS $filter) {
				$filterval=explode('=',$filter);
				if (!empty($filterval[0])) {
					echo '<input type="hidden" name="'.$filterval[0].'" value="'.$filterval[1].'" />'."\n";
				}
			}
		} ?>
		<input type="text" size="25" name="search" value="<?php if ($search != "") print attribute_escape($search); ?>" /><input type="submit" name="submit-search" value="search" />
		</form>
		</td>
		</tr>
	</table>
</div>
<?php
	//# Detailed List of Wassup Records...
	print $expcol;
        //# Show Page numbers/Links...
        if ($itemstot > $items) {
                print "\n".'<div id="pag" align="center">'.$p->show().'</div><br />'."\n";
        }
	if ($itemstot > 0) {
	foreach ($main as $rk) {
		$timestampF = $rk->max_timestamp;
		$dateF = gmdate("d M Y", $timestampF);
		if ($wassup_options->wassup_time_format == 24) {
			$datetimeF = gmdate('Y-m-d H:i:s', $timestampF);
			$timeF = gmdate("H:i:s", $timestampF);
		} else {
			$datetimeF = gmdate('Y-m-d h:i:s a', $timestampF);
			$timeF = gmdate("h:i:s a", $timestampF);
		}
		//$ip = @explode(",", $rk->ip);
		$ip_proxy = strpos($rk->ip,",");
		//if proxy, get 2nd ip...
		if ($ip_proxy !== false) {
			$ip = trim(substr($rk->ip,(int)$ip_proxy+1));
			if (empty($ip) || $ip == "unknown" || $ip == "127.0.0.1") {
				//if bad 2nd ip, use proxy ip
				$ip = substr($rk->ip,0,$ip_proxy-1);
			}
		} else { 
			$ip = $rk->ip;
		}
		if ($rk->hostname != "") $hostname = $rk->hostname; 
		else $hostname = "unknown";
		//$numurl = $wpdb->get_var("SELECT COUNT(DISTINCT id) as numurl FROM $table_name WHERE wassup_id='".$rk->wassup_id."'");
		$numurl = (int) $rk->page_hits;

		// Visitor Record - raw data (hidden)
		$raw_div="raw-".substr($rk->wassup_id,0,25).rand(0,99);
		echo "\n"; ?>
                <div id="<?php echo $raw_div; ?>" style="display:none; padding-top:7px;" >
                <h2><?php _e("Raw data","wassup"); ?>:</h2>
                <style type="text/css">.raw { color: #542; padding-left:5px; }</style>
                <ul style="list-style-type:none;padding:20px 0 0 30px;">
		<li><?php echo __("Visit type","wassup").': <span class="raw">';
                if ($rk->username != "") { 
			echo __("Logged-in user","wassup").' - '.$rk->username;
		} elseif ($rk->spam == "3") { 
                	_e("Spammer/Hacker","wassup");
		} elseif (!empty($rk->spam)) { 
                	_e("Spammer","wassup");
		} elseif ($rk->comment_author != "") { 
                	echo __("Comment author","wassup").' - '.$rk->comment_author;
		} elseif ($rk->feed != "") { 
                	echo __("Feed","wassup").' - '.$rk->feed;
		} elseif ($rk->spider != "") { 
			echo __("Spider","wassup").' - '.$rk->spider;
		} else {
			 _e("Regular visitor","wassup");
		}
		echo '</span>'; ?></li>
		<li><?php echo __("IP","wassup").': <span class="raw">'.$rk->ip.'</span>'; ?></li>
		<li><?php echo __("Hostname","wassup").': <span class="raw">'.$hostname.'</span>'; ?></li>
		<li><?php echo __("Url Requested","wassup").': <span class="raw">'.attribute_escape(htmlspecialchars(html_entity_decode($rk->urlrequested))).'</span>'; ?></li>
		<li><?php echo __("User Agent","wassup").': <span class="raw">'.attribute_escape(htmlspecialchars(html_entity_decode($rk->agent))).'</span>'; ?></li>
		<li><?php echo __("Referrer","wassup").': <span class="raw">'.attribute_escape(urldecode($rk->referrer)).'</span>'; ?></li>
		<?php if ($rk->search != "") { ?>
		<li><?php echo __("Search Engine","wassup").': <span class="raw">'.$rk->searchengine.'</span> &nbsp; &nbsp; ';
		echo __("Search","wassup").': <span class="raw">'.$rk->search.'</span> &nbsp; &nbsp; '; 
		echo __("Page","wassup").': <span class="raw">'.$rk->searchpage.'</span>';?></li>
		<?php }
		if ($rk->os != "") { ?>
		<li><?php echo __("OS","wassup").': <span class="raw">'.$rk->os.'</span>'; ?></li>
		<?php }
		if ($rk->browser != "") { ?>
		<li><?php echo __("Browser","wassup").': <span class="raw">'.$rk->browser.'</span>'; ?></li>
		<?php }
		if ($rk->language != "") { ?>
		<li><?php echo __("Locale/Language","wassup").': <span class="raw">'.$rk->language.'</span>'; ?></li>
		<?php }
		if ($rk->screen_res != "") { ?>
                <li><?php echo __("Screen Resolution","wassup").': <span class="raw">'.$rk->screen_res.'</span>'; ?></li>
                <?php } ?>
		<li><?php echo 'Wassup ID'.': <span class="raw">'.$rk->wassup_id.'</span>'; ?></li>
		<li><?php echo __("End timestamp","wassup").': <span class="raw">'.$datetimeF.' ( '.$rk->max_timestamp.' )</span>'; ?></li>
		</ul>
		</div> <!-- raw-wassup_id -->

		<?php //Visitor Record - detail listing
		if ($rk->referrer != '') {
			if ($rk->searchengine != "" || stristr($rk->referrer,$wpurl)!=$rk->referrer) { 
				if ($rk->searchengine == "") {
				$referrer = '<a href="'.wCleanURL($rk->referrer).'" target="_BLANK"><span style="font-weight: bold;">'.stringShortener($rk->referrer, round($max_char_len*.8,0)).'</span></a>';
				} else {
				$referrer = '<a href="'.wCleanURL($rk->referrer).'" target="_BLANK">'.stringShortener($rk->referrer, round($max_char_len*.9,0)).'</a>';
				}
			} else { 
                        $referrer = __('From your blog','wassup');
                        }
                } else { 
                        $referrer = __('Direct hit','wassup');
		} ?>
		<div class="delID<?php echo $rk->wassup_id; ?>">
                <div class="<?php if ($wassup_options->wmark == 1 AND $wassup_options->wip == $ip) echo "sum-nav-mark"; else echo "sum-nav"; ?>">

                <p class="delbut">
                <?php // Mark/Unmark IP
                if ($wassup_options->wmark == 1 AND $wassup_options->wip ==  $ip) { ?>
			<a href="?<?php echo attribute_escape($URLQuery.'&deleteMARKED=1&dip='.$ip); ?>" style="text-decoration:none;" class="deleteIP">
                        <img class="delete-icon" src="<?php echo WASSUPURL.'/img/cross.png" alt="'.__('delete','wassup').'" title="'.__('Delete ALL marked records with this IP','wassup'); ?>" /></a>
			<a href="?<?php echo attribute_escape($URLQuery.'&wmark=0'); ?>" style="text-decoration:none;">
                        <img class="unmark-icon" src="<?php echo WASSUPURL.'/img/error_delete.png" alt="'.__('unmark','wassup').'" title="'.__('UnMark IP','wassup'); ?>" /></a>
                <?php } else { ?>
                        <a href="#" class="deleteID" id="<?php echo $rk->wassup_id ?>" style="text-decoration:none;">
                        <img class="delete-icon" src="<?php echo WASSUPURL.'/img/cross.png" alt="'.__('delete','wassup').'" title="'.__('Delete this record','wassup'); ?>" /></a>
			<a href="?<?php echo attribute_escape($URLQuery.'&wmark=1&wip='.$ip); ?>" style="text-decoration:none;">
                        <img class="mark-icon" src="<?php echo WASSUPURL.'/img/error_add.png" alt="'.__('mark','wassup').'" title="'.__('Mark IP','wassup'); ?>" /></a>
                <?php } ?>
                <a href="#TB_inline?height=400&width=<?php echo $res.'&inlineId='.$raw_div; ?>" class="thickbox">
                <img class="table-icon" src="<?php echo WASSUPURL.'/img/database_table.png" alt="'.__('show raw table','wassup').'" title="'.__('Show the items as raw table','wassup'); ?>" /></a>
                </p>

			<span class="sum-box"><?php if ($numurl > 1) { ?><a  href="#" class="showhide" id="<?php echo $rk->id ?>"><?php print $ip; ?></a><?php } else { ?><?php print $ip; ?><?php } ?></span>
			<span class="sum-date"><?php print $datetimeF; ?></span>
			<div class="sum-det"><span class="det1">
<?php
			if (strstr($rk->urlrequested,'[404]')) {  //no link for 404 page
				print stringShortener($rk->urlrequested, round($max_char_len*.8,0)+5);
			} else {
				print '<a href="'.wAddSiteurl($rk->urlrequested).'" target="_BLANK">';
				print stringShortener($rk->urlrequested, round($max_char_len*.8,0)).'</a>';
			} ?>
                        </span><br />
                        <span class="det2"><strong><?php _e('Referrer','wassup'); ?>: </strong><?php print $referrer; ?><br /><strong><?php _e('Hostname','wassup'); ?>:</strong> <a  href="#" class="toggleagent" id="<?php echo $rk->id ?>"><?php print $hostname; ?></a></span></div>
                        </div>
			<div style="margin-left: auto; margin-right: auto;">
			<div style="display: none;" class="togglenavi naviagent<?php echo $rk->id ?>">
			<ul class="useragent">
				<li class="useragent"><span class="indent-li-agent"><?php _e('User Agent','wassup'); ?>: <strong><?php print $rk->agent; ?></strong></span></li>
			</ul>
			</div>
			<?php // Referer is search engine
			if ($rk->searchengine != "") {
				if (stristr($rk->searchengine,"images")!==FALSE) {
					$bg = 'style="background: #e5e3ec;"';
					$page = (number_format(($rk->searchpage / 19), 0) * 18); 
					$Apagenum = explode(".", number_format(($rk->searchpage / 19), 1));
					$pagenum = ($Apagenum[0] + 1);
					$url = parse_url($rk->referrer); 
					$ref = $url['scheme']."://".$url['host']."/images?q=".str_replace(' ', '+', $rk->search)."&start=".$page;
				} else {
					$bg = 'style="background: #e4ecf4;"';
					$pagenum = $rk->searchpage;
					$ref = $rk->referrer;
				}
			?>
			<ul class="searcheng" <?php print $bg; ?>>
                                <li class="searcheng"><span class="indent-li-agent"><?php _e('SEARCH ENGINE','wassup'); ?>: <strong><?php print $rk->searchengine." (".__("page","wassup").": $pagenum)"; ?></strong></span></li>
                                <li class="searcheng"><?php _e("KEYWORDS","wassup"); ?>: <strong><a href="<?php print wCleanURL($ref);  ?>" target="_BLANK"><?php print stringShortener($rk->search, round($max_char_len*.52,0)); ?></a></strong></li>
			</ul>
			<?php 
			}
			$Ocomment_author = "";
			$unclass = "users";
			// User is logged in, is an administrator, and/or is a comment author
			if ($rk->username != "") {
				$utype = __("LOGGED IN USER","wassup");
				$unclass = "userslogged";
				//check for administrator
				$udata=get_userdatabylogin($rk->username);
				//if ($debug_mode) {
				//	echo "\n<!--\n*User data:\n"; //debug
				//	print_r($udata); //debug
				//	echo "\n-->"; //debug
				//}
				if (!empty($udata->user_level) && $udata->user_level > 7) {
					$utype = __("ADMINISTRATOR","wassup");
					$unclass .= " adminlogged";
				}
				$Ocomment_author = '<li class="users"><span class="indent-li-agent">'.$utype.': <strong>'.$rk->username.'</strong></span></li>';
			}
			if ($rk->comment_author != "") {
				$Ocomment_author .= '<li class="users"><span class="indent-li-agent">'.__("COMMENT AUTHOR","wassup").': <strong>'.$rk->comment_author.'</strong></span></li>';
			}
			if (!empty($Ocomment_author)) { ?>
			<ul class="<?php print $unclass; ?>">
				<?php print $Ocomment_author; ?>
			</ul>
			<?php
			}
			// Referer is a Spider or Bot
			if ($rk->spider != "") {
			if ($rk->feed != "") { ?>
			<ul style="background:#fdeec8;" class="spider">
                                <li class="feed"><span class="indent-li-agent"><?php _e('FEEDREADER','wassup'); ?>: <strong><?php print $rk->spider; ?></strong></span></li>
				<?php if (is_numeric($rk->feed)) { ?>
                                <li class="feed"><span class="indent-li-agent"><?php _e('SUBSCRIBER(S)','wassup'); ?>: <strong><?php print (int)$rk->feed; ?></strong></span></li>
				<?php  } ?>
                        </ul>
                        <?php  } else { ?>
                        <ul class="spider">
                                <li class="spider"><span class="indent-li-agent"><?php _e('SPIDER','wassup'); ?>: <strong><?php print $rk->spider; ?></strong></span></li>
			</ul>
			<?php  }
			} ?>
                        <?php // Referer is a SPAM
                        if ($rk->spam > 0 && $rk->spam < 3) { ?>
                        <ul class="spam">
			<li class="spam"><span class="indent-li-agent">
				<?php _e("Probably SPAM!","wassup"); 
				if ($rk->spam==2) { echo '('.__("Referer Spam","wassup").')'; }
				else { echo '(Akismet '.__("Spam","wassup").')'; }  ?>
				</span></li>
                        </ul>
                        <?php  } elseif ($rk->spam == 3) { ?>
                        <ul class="spam">
			<li class="spam"><span class="indent-li-agent">
				<?php _e("Probably hack attempt!","wassup"); ?>
                        </li></ul>
                        <?php  } ?>
			<?php // User os/browser/language
			if ($rk->spider == "" AND ($rk->os != "" OR $rk->browser != "")) { ?>
			<ul class="agent">
			<li class="agent"><span class="indent-li-agent">
				<?php if ($rk->language != "") { ?>
				<img src="<?php echo WASSUPURL.'/img/flags/'.strtolower($rk->language).'.png'.'" alt="'.strtolower($rk->language).'" title="'.__("Language","wassup").': '.strtolower($rk->language); ?>" />
				<?php }
				_e("OS","wassup"); ?>: <strong><?php print $rk->os; ?></strong></span></li>
			<li class="agent"><?php _e("BROWSER","wassup"); ?>: <strong><?php print $rk->browser; ?></strong></li>
			<?php if ($rk->screen_res != "") { ?>
				<li class="agent"><?php _e("RESOLUTION","wassup"); ?>: <strong><?php print $rk->screen_res; ?></strong></li>
			<?php } ?>
			</ul>
			<?php  } ?>
			
			<div style="display: visible;" class="togglecrono navi<?php echo $rk->id ?>">
			<ul class="url">
		<?php 
		if ($numurl > 1) {
			//Important Note: list of urls visited is affected by browsers like Safari 4 which hits a page from both the user window and from it's "top sites" page, creating multiple duplicate records with distinct id's...
			//$qryCD = $wpdb->get_results("SELECT `timestamp`, urlrequested FROM $table_name WHERE wassup_id='".$rk->wassup_id."' ORDER BY `timestamp`");	//duplicates possible
			$qryCD = $wpdb->get_results("SELECT DISTINCT `timestamp`, urlrequested, agent FROM $table_name WHERE wassup_id='".$rk->wassup_id."' ORDER BY `timestamp`");	//no duplication, unless agent is differnt
			//$qryCD = $wpdb->get_results("SELECT `id`, `timestamp`, urlrequested FROM $table_name WHERE wassup_id='".$rk->wassup_id."' ORDER BY `id`");	//id is sequential, so sort order == visit order...UPDATE: may not be in visit order because 'insert delayed' could make `id` out of sync with `timestamp`
			$i=1;
			$char_len = round($max_char_len*.92,0);
			foreach ($qryCD as $cd) {	
				$time2 = gmdate("H:i:s", $cd->timestamp);
				$num = ($i&1);
				if ($num == 0) $classodd = "urlodd"; 
				else  $classodd = "url";
				if ($i < $numurl || $rk->urlrequested != $cd->urlrequested) {
				print '<li class="'.$classodd.' navi'.$rk->id.'"><span class="indent-li-nav">'.$time2.' ->';
				if (strstr($cd->urlrequested,'[404]')) {  //no link for 404 page
					print stringShortener($cd->urlrequested, $char_len);
				} else {
					print '<a href="'.wAddSiteurl($cd->urlrequested).'" target="_BLANK">';
					print stringShortener($cd->urlrequested, $char_len).'</a>'."\n";
				}
				print '</span></li>'."\n";
				}
				$i++;
			} //end foreach qryCD
		} ?>
			</ul>
			</div>
			<p class="sum-footer"></p>
		</div>
	</div>
<?php	} //end foreach qry

	} //end if itemstot > 0
	if ($itemstot > $items) {
		print "\n<br />".'<div align="center">'.$p->show().'</div><br />'."\n";
	}
	print $expcol;
		//print '<br />';
		//if ($itemstot >= 10) $p->show();
		//print '<br />';
		//if (!isset($_GET['limit']) OR $_GET['limit'] == 10 OR $_GET['limit'] == 20) {
		//        print $expcol;
		//}
	// Print Google chart last to speed up detail display
	// TODO? move chart function into action.php and call it as AJAX
	//    request so it does not impact visitor detail page load
	if ($wassup_options->wassup_chart == 1) {
		$chart_type = ($wassup_options->wassup_chart_type >0)? $wassup_options->wassup_chart_type: "2";
		echo "\n"; ?>
	<!-- show Google!Charts image -->
	<script type="text/javascript">
	//<![CDATA[
		var html='<div id="placeholder" align="center">';
		<?php if ($pagestot > 20) {
			$chart_url = $Tot->TheChart($last, $res, "125", $search, $chart_type, "bg,s,ffffff");
			echo "html+='<img src=\"".$chart_url."\" alt=\"".__("Graph of visitor hits","wassup")."\"/>';\n";
		} else {
			echo "html+='<p style=\"padding-top:50px;\">".__("Too few records to print chart","wassup")."...</p>';\n";
		} ?>
		html+='</div>';
		jQuery('div#usage').append(html);
	//]]>
	</script>
<?php	} //end if wassup_chart==1

	} //end MAIN/DETAILS VIEW 

	// End calculating execution time of script
	//$mtime = microtime();
	//$mtime = explode(" ",$mtime);
	$totaltime = sprintf("%8.8s",(microtime_float() - $starttime));
?>
	<p><small>WassUp ver: <?php echo $wassupversion.' - '.__("Check the official","wassup").' <a href="http://www.wpwp.org" target="_BLANK">WassUp</a> '.__("page for updates, bug reports and your hints to improve it","wassup").' - <a href="http://trac.wpwp.org/wiki/Documentation" title="Wassup '.__("User Guide documentation","wassup").'">Wassup '.__("User Guide documentation","wassup").'</a>'; ?>
	<nobr>- <?php echo __('Exec time','wassup').": $totaltime"; ?></nobr></small></p>
	<?php 
	if ($debug_mode) {
		//display MySQL errors/warnings in admin menus - for debug
		$wpdb->print_error();	//debug

		//restore normal mode
		ini_set('display_errors',$mode_reset);	//turn off debug
	}
	?>
	</div>	<!-- end wrap --> 
<?php 
} //end function Wassup

function wCreateTable($table_name="",$withcharset=true) {
	global $wpdb, $wassupversion, $current_user;

	$wassup_table = (!empty($wassup_options->wassup_table))? $wassup_options->wassup_table: $wpdb->prefix . "wassup";

	if (empty($table_name)) {
		$table_name = $wassup_table;
	}
	$table_tmp_name = $wassup_table."_tmp";
	
	//...Set default character set and collation (on new table)
	$charset_collate = '';
	//#don't do charset/collation when < MySQL 4.1 or when DB_CHARSET is undefined
	//Note: it is possible that table default charset !== WP database charset on preexisting MySQL database and tables (from WP2.3 or less) because old charsets persist after upgrades
	if ($withcharset && version_compare(mysql_get_server_info(),'4.1.0','>') && defined('DB_CHARSET') && !empty($wpdb->charset)) {
		$charset_collate = 'DEFAULT CHARACTER SET '.$wpdb->charset;
		//add collate only when charset is specified
		if (!empty($wpdb->collate)) {
			$charset_collate .= ' COLLATE '.$wpdb->collate;
		}
	}

	//wassup table structure
	$sql_createtable = "CREATE TABLE `$table_name` (
  `id` mediumint(9) unsigned NOT NULL auto_increment,
  `wassup_id` varchar(60) NOT NULL,
  `timestamp` varchar(20) NOT NULL,
  `ip` varchar(35) default NULL,
  `hostname` varchar(150) default NULL,
  `urlrequested` text,
  `agent` varchar(255) default NULL,
  `referrer` text,
  `search` varchar(255) default NULL,
  `searchpage` int(11) unsigned default '0',
  `os` varchar(15) default NULL,
  `browser` varchar(50) default NULL,
  `language` varchar(5) default NULL,
  `screen_res` varchar(15) default NULL,
  `searchengine` varchar(25) default NULL,
  `spider` varchar(50) default NULL,
  `feed` varchar(50) default NULL,
  `username` varchar(50) default NULL,
  `comment_author` varchar(50) default NULL,
  `spam` varchar(5) default '0',
  `url_wpid` varchar(50) default NULL,
  UNIQUE KEY `id` (`id`),
  KEY `idx_wassup` (`wassup_id`(32),`timestamp`),
  INDEX (`os`),
  INDEX (`browser`),
  INDEX `timestamp` (`timestamp`)) {$charset_collate};";
	//  Note: index (username,ip) has been removed because of problems
	//    with non-romanic language display

	//...Include a first record if new table (not temp table)
	$sql_firstrecord = '';
	if ($table_name != $table_tmp_name && $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		if (!class_exists('UADetector'))
			include_once (dirname(__FILE__) . '/lib/uadetector.class.php');
		$ua = new UADetector;
		if (empty($current_user->user_login)) get_currentuserinfo();
		$logged_user = (!empty($current_user->user_login)? $current_user->user_login: "");
		$sql_firstrecord = sprintf("INSERT INTO $table_name (`wassup_id`, `timestamp`, `ip`, `hostname`, `urlrequested`, `agent`, `referrer`, `search`, `searchpage`, `os`, `browser`, `language`, `screen_res`, `searchengine`, `spider`, `feed`, `username`, `comment_author`, `spam`) VALUES ('%032s','%s','%s','%s','%s','%s','%s','','','%s','%s','','','','','','%s','','0')",
			1, time()+(get_option(gmt_offset)*3600), 
			'127.0.0.1', 'localhost', 
			'[404] '.__('Welcome to WassUP','wassup'), 
			$ua->agent . ' WassUp/'.$wassupversion.' (http://www.wpwp.org)', 
			'http://www.wpwp.org', $ua->os, 
			trim($ua->name .' '. $ua->majorVersion($ua->version)),
			$logged_user);
	}

	if (!function_exists('dbDelta')) {
	if (file_exists(ABSPATH . 'wp-admin/includes/upgrade.php')) {
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	} else {	//deprecated since 2.5
        	require_once( ABSPATH.'wp-admin/upgrade-functions.php');
	}
	}
	//...create/upgrade wassup table
	if (!empty($sql_firstrecord)) {
		$result = dbDelta(array($sql_createtable,$sql_firstrecord));
	} else {
		$result = dbDelta($sql_createtable);
	} 

	//...return 'true' if table created successfully, false otherwise
	$retvalue=true;
	// if (!empty($result)) {	//empty result is success
	//	if ($table_name != $table_tmp_name) {
	//		$wassup_options->wassup_alert_message = 'Table ERROR: '."{$result}";
	//	}
	//}
	if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {

		$retvalue=false;
	} else {
		if ($table_name != $table_tmp_name && version_compare(mysql_get_server_info(),'4.1.0','>')) {
			//'CREATE TABLE LIKE' syntax not supported in MySQL 4.1 or less
			$upgrade = dbDelta("CREATE TABLE $table_tmp_name LIKE {$table_name}");
		}
	}
	return $retvalue;
	//#TODO: 
	// 1. create stored procedure that selects records by timestamp
	// 2. create table views, 'wassup_hourly', 'wassup_weekly', 'wassup_monthly' as subsets of 'wp_wassup' for hourly hits, daily hits, weekly hits...
	//    Note: views are only available in MySQL 5.0.1+
} //end function wCreateTable

//UPGRADE old table: Drop and rebuild all indices except 'id', use
//  'dbDelta' function (in "wCreateTable") to upgrade individual columns,
//  and drop and re-create 'wassup_tmp' table.
function wUpdateTable() {
	global $wpdb, $wassup_options, $wassupversion;

	$table_name = (!empty($wassup_options->wassup_table))? $wassup_options->wassup_table: $wpdb->prefix . "wassup";
	$table_tmp_name = $table_name."_tmp";

	//NOTE: All column updates replaced by single call to 'wcreateTable' function which uses 'dbDelta' to update table structure
	//upgrades for versions 1.7 or less
	//if (empty($wassup_options->wassup_version) OR version_compare($wassup_options->wassup_version,"1.7","<")) {
	//	// Upgrade from version < 1.3.9 - add 'spam' column to wassup table
	//	if ($wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'spam'") == "") {
	//		$wpdb->query("ALTER TABLE {$table_name} ADD COLUMN spam VARCHAR(5) DEFAULT '0'");
	//	}

	//	// Upgrade from version <= 1.5.1 - increase wassup_id size
	//	$wassup_col = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'wassup_id'");
	//	foreach ($wassup_col as $wID) {
	//		if ($wID->Type != "varchar(80)") {
	//			$wpdb->query("ALTER TABLE {$table_name} CHANGE wassup_id wassup_id varchar(80) NULL");
	//		}
	//	}
	//	// - increase size of searchengine and spider columns
	//	$col_size = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'searchengine'");
	//	foreach ($col_size as $wCol) {
	//		if ($wCol->Type != "varchar(25)") {
	//			$wpdb->query("ALTER TABLE {$table_name} CHANGE searchengine searchengine varchar(25) NULL");
	//		}
	//	}
	//	$col_size = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'spider'");
	//	foreach ($col_size as $wCol) {
	//		if ($wCol->Type != "varchar(25)") {
	//			$wpdb->query("ALTER TABLE {$table_name} CHANGE spider spider varchar(25) NULL");
	//		}
	//	}
	//} //end if wassup_version

	//## For all upgrades
	// ...try disable MYSQL warnings to prevent activation error in WP 2.7+
	//$results = $wpdb->query("SET max_error_count = 0");
	//#$idx_cols = $wpdb->get_col("SHOW INDEX FROM $table_name","Column_name"); //doesn't work
	// Drop and re-create all indices except 'id'
	//# get list of all wassup indices
	$qryresult = mysql_query("SHOW INDEX FROM {$table_name}");
	if ($qryresult) { 
		$row_count = mysql_num_rows($qryresult); 
	} else {
		$row_count = 0;
	}
	//# get the names of all indices
	$idx_names = array();
	$prev_key = "";	//names listed multiples times per columns in key
	if ($row_count > 1) {
		while ($row = mysql_fetch_array($qryresult,MYSQL_ASSOC)) {
			if ($row["Column_name"] != "id" && $row["Key_name"] != $prev_key) {
				$idx_names[] = $row["Key_name"];
			}
			$prev_key = $row["Key_name"];
		} //end while
	} //end if row_count
	mysql_free_result($qryresult);
	//# drop all the indices in $idx_names and drop temp table...
	//drop indices
	foreach ($idx_names AS $idx_drop) {
		mysql_query("DROP INDEX $idx_drop ON {$table_name}");
	}

	//a single call to 'wCreateTable' rebuilds indices using wordpress' 'dbdelta' function...
	//this also optimizes
	//...could take a long time, so run in background if window times out
	ignore_user_abort(1);
	//	$wpdb->query("ALTER TABLE {$table_name} ADD INDEX idx_wassup (wassup_id(32),timestamp)");
	//	echo "\n<!-- heartbeat -->";	//ineffective because, no output to browser during install
	//	$wpdb->query("ALTER TABLE {$table_name} ADD INDEX idx_w_os (os)");
	//	$wpdb->query("ALTER TABLE {$table_name} ADD INDEX idx_w_browser (browser)");
	//	echo "\n<!-- heartbeat -->";
	//	$wpdb->query("ALTER TABLE {$table_name} ADD INDEX idx_w_timestamp (timestamp)");
	//	echo "\n<!-- heartbeat -->";
	//	// Since version 1.7 - new index on username and ip - removed because of charset problems
	//	$wpdb->query("ALTER TABLE {$table_name} ADD INDEX w_visitor_idx (username(20),ip)");

	// - drop and recreate table "wp_wassup_tmp" and optimize "wp_wassup"
	//$wpdb->query("DROP TABLE IF EXISTS $table_tmp_name"); //incorrectly causes an activation error in Wordpress
	mysql_query("DROP TABLE IF EXISTS $table_tmp_name"); 

	//call 'wCreateTable' to update table structure and rebuild indices using wordpress' 'dbdelta' function...
	if (wCreateTable($table_name,false)) {
		if ($wpdb->get_var("SHOW TABLES LIKE '$table_tmp_name'") != $table_tmp_name) { 
			wCreateTable($table_tmp_name);
		}
		return true;
	} elseif ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) { 
		//table upgrade warning error
		return true;
	} else {
		//table upgrade fatal error
		return false;
	}

	//lastly, optimize wp_wassup - unnecessary because of index rebuild
	//$wpdb->query("OPTIMIZE TABLE {$table_name}");
} //end function wUpdateTable

//Set Wassup_id and cookie (before headers sent)
function wassupPrepend() {
	global $wassup_options, $current_user, $user_level, $debug_mode;

	if ($wassup_options->wassup_active == 0) {	//do nothing
		return;
	}
	$wassup_id = "";
	$session_timeout = 1;
	$screen_res = "";
	$cookieIP = "";
	$cookieHost = "";
	$cookie_value="";
	if (empty($current_user->user_login)) { 
		get_currentuserinfo();	//sets $current_user, $user_xx 
	}
	$logged_user = (!empty($current_user->user_login)? $current_user->user_login: "");

	//First exclusion control is for admin user
	if ($wassup_options->wassup_admin == "1" || $user_level < 8) {
	//exclude valid wordpress admin page visits
	if (!is_admin() || empty($logged_user)) {

	//### Check if this is an ongoing visit or a new hit...
	//#visitor tracking with "cookie"...
	if (isset($_COOKIE['wassup'])) {
		$cookie_value = $_COOKIE['wassup'];
		$cookie_data = explode('::',attribute_escape(base64_decode(urldecode($_COOKIE['wassup']))));
		$wassup_id = $cookie_data[0];
		if (!empty($cookie_data[1])) { 
			$wassup_timer = $cookie_data[1];
			$session_timeout = ((int)$wassup_timer - (int)time());
		}
		if (!empty($cookie_data[2])) { 
			$screen_res = $cookie_data[2];
		}
		if (!empty($cookie_data[3])) {
			$cookieIP = $cookie_data[3];
			if (!empty($cookie_data[4])) {
				$cookieHost = $cookie_data[4];
			}
		}
	}
	//set screen resolution value from cookie or browser header data, if any
	if (empty($screen_res)) {
		if (isset($_COOKIE['wassup_screen_res'])) {
			$screen_res = $_COOKIE['wassup_screen_res'];
			if (trim($screen_res) == "x") $screen_res="";
		} 
		if (empty($screen_res) && isset($_ENV['HTTP_UA_PIXELS'])) {
			//resolution in IE/IEMobile header sometimes
			$screen_res = str_replace('X','x',$_ENV['HTTP_UA_PIXELS']);
		}
		//if (empty($screen_res) && isset($_GET['wscr'])) {
		//	$screen_res = $_GET['wscr'];
		//} 
	}
	//write wassup cookie for new visits, visit timeout (45 mins) or empty screen_res
	if (empty($wassup_id) || $session_timeout < 1 || (empty($cookie_data[2]) && !empty($screen_res))) {
		$ipAddress = "";
		$hostname = "";
		//#### Get the visitor's details from http header...
		if (isset($_SERVER["REMOTE_ADDR"])) {
			$ipAddress = $_SERVER["REMOTE_ADDR"];
			$IP = "";
			if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
				$proxy = $ipAddress;
				//in case of multiple forwarding
				$IP = validIP($_SERVER["HTTP_X_FORWARDED_FOR"]);
				if ($IP) { 
				if ($cookieIP == $IP) {
					$hostname = $cookieHost;
				} else {
					$hostname = @gethostbyaddr($IP);
					//exclude dummy addresses...
					if (!empty($hostname) && $hostname != "unknown" && $hostname != "localhost.localdomain") {
					if (!validIP($proxy)) {
						$ipAddress = $IP;
					} else {
						$ipAddress = $proxy.",".$IP;
					}
					} else {
						$hostname = "";
					}
				}
				} //end if IP
			}
			if (empty($IP) || empty($hostname)) {
				$IP = $_SERVER["REMOTE_ADDR"];
				if (validIP($IP)) {
					if ($cookieIP == $IP) {
						$hostname = $cookieHost;
					} else {
						$hostname = @gethostbyaddr($IP);
					}
				} else {
					$hostname = "unknown";
				}
			}
		} //end if REMOTE_ADDR

		if (empty($IP)) { $IP = $ipAddress; }
		if (empty($hostname)) { $hostname = "unknown"; }
		$userAgent = (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
		//# Create a new id for this visit from a combination
		//#  of date/hour/min/ip/loggeduser/useragent/hostname.
		//#  It is not unique so that multiple visits from the 
		//#  same ip/userAgent within a 30 minute-period, can be 
		//#  tracked, even when session/cookies is disabled. 
		$temp_id = sprintf("%-060.60s", date('YmdH').str_replace(array(' ','http://','www.','/','.','\'','"',"\\",'$','-','&','+','_',';',',','>','<',':','#','*','%','!','@',')','(',), '', intval(date('i')/30).$IP.strrev($logged_user).strrev($userAgent).strrev($hostname).intval(date('i')/30)).date('HdmY').$hostname.rand());

		//#assign new wassup id from "temp_id" 
		$wassup_id = md5($temp_id);
		$wassup_timer=((int)time() + 2700); //use 45 minutes timer

		//put the cookie in the oven and set the timer...
		//this must be done before headers sent
		if (defined('COOKIE_DOMAIN')) {
			$cookiedomain = COOKIE_DOMAIN;
			$cookiepath = "/";
		} else {
			$cookieurl = parse_url(get_option('home'));
			$cookiedomain = preg_replace('/^www\./i','',$cookieurl['host']);
			$cookiepath = $cookieurl['path'];
		}
		$expire = time()+3000;	//expire based on unix time, not on Wordpress time
		$cookie_data = implode('::',array("$wassup_id","$wassup_timer","$screen_res","$IP","$hostname"));
		$cookie_value = urlencode(base64_encode($cookie_data));
		setcookie("wassup", "$cookie_value", $expire, $cookiepath, $cookiedomain);
	} //end if empty(wassup_id)

	//place wassup tag and javascript in document head and footer
	add_action('wp_head', 'wassup_meta_info', 10, "$screen_res");

	//record visit after page is displayed to keep page load fast
	if ($debug_mode) {
		//show wassupAppend debug output in footer when in debug mode
		//...15-priority so runs after other wp_footer actions
		add_action('wp_footer', 'wassupAppend', 15, "$cookie_value");
	} else {
		//add visit record after page is displayed to keep page load fast
		//...1-priority so runs before other 'shutdown' actions such as cache flush
		add_action('shutdown', 'wassupAppend', 1, "$cookie_value");
		//Warning Note: since Wordpress 2.7.1, 'shutdown' hook causes sporadic inclusion of non-visited posts in wassup table for some wordpress themes
	}

	} //end if !is_admin
	} //end if wassup_admin
} //end function wassupPrepend

//Track visitors and save record in wassup table, after page is displayed
function wassupAppend($cookie_value="") {
	global $wpdb, $wpurl, $blogurl, $wp_query, $wassup_options, $current_user, $user_level, $wassupversion, $debug_mode;

	if ($wassup_options->wassup_active == 0) {	//do nothing
		return;
	}
	ignore_user_abort(1); // run script in background if visitor aborts

	if ($debug_mode) {	//#debug...
		$mode_reset=ini_get('display_errors');
		$debug_reset=$debug_mode;
		error_reporting(E_ALL);	//debug, E_STRICT=php5 only
		ini_set('display_errors','On');	//debug
		//Debug: Output open comment tag to hide PHP errors from visitors
		echo "\n<!-- *WassUp DEBUG On\n";   //hide errors
	}

	//$siteurl =  get_bloginfo('siteurl');	//now blogurl
	$table_name = $wassup_options->wassup_table;
	$table_tmp_name = $table_name . "_tmp";
	$wassup_rec = "";
	//$current_user = wp_get_current_user();  //$current_user is global
	if (empty($current_user->user_login)) { 
		get_currentuserinfo();	//sets $current_user, $user_xx 
	}
	$logged_user = (!empty($current_user->user_login)? $current_user->user_login: "");
	$urlRequested = $_SERVER['REQUEST_URI'];

	$hackercheck = false;
	if (empty($logged_user) && $wassup_options->wassup_hack == "1") {
		//no hack checks on css or image requests
		if (preg_match('/\.(css|jpe?g|gif|png)$/i',$_SERVER['REQUEST_URI'])==0) {
			$hackercheck = true;
		}
	}
	//First exclusion control is for admin user
	if ($wassup_options->wassup_admin == "1" || $user_level < 8) {

	//Record non-admin page visits and or hack attempts
	if ((!is_admin() && stristr($urlRequested,"/wp-admin/")===FALSE && stristr($urlRequested,"/wp-includes/")===FALSE) || $hackercheck) {

		//TODO: store wordpress post-id/category-id/tag-id for page

		//## Exclude users and urls on exclusion list... 
		$exclude_visit = false;
		if (!empty($wassup_options->wassup_exclude_user) && !empty($logged_user)) {
			$exclude_list = explode(",", $wassup_options->wassup_exclude_user);
			foreach ($exclude_list as $exclude_user) {
				if ($exclude_user == $logged_user) {
					$exclude_visit = true;
					break 1;
				}
			}
		}
		//TODO: exclude page requests by post_id
		if (!empty($wassup_options->wassup_exclude_url) && !$exclude_visit) {
			$exclude_list = explode(",", $wassup_options->wassup_exclude_url);
			$pagerequest=strtolower(remove_query_arg('wscr',$urlRequested));
			foreach ($exclude_list as $exclude_url) {
				$exclude_page = strtolower($exclude_url);
				if ($pagerequest == $exclude_page) {
					$exclude_visit = true;
					break 1;
				} elseif ("$pagerequest" == "{$blogurl}$exclude_page") {
					$exclude_visit = true;
					break 1;
				} elseif ("{$blogurl}$pagerequest" == "$exclude_page") {
					$exclude_visit = true;
					break 1;
				}
			}
		} //end if wassup_exclude_url

	//exclusion control by specific username/url
	if (!$exclude_visit) {
		$wassup_id = "";
		$screen_res = "";
		$cookieIP = "";
		$cookieHost = "";
		//check for wassup cookie and read contents
		if (empty($cookie_value) && isset($_COOKIE['wassup'])) {
			$cookie_value = $_COOKIE['wassup'];
		}
		if (!empty($cookie_value)) {
			$cookie_data = attribute_escape(base64_decode(urldecode($cookie_value)));
			$wassup_cookie = explode('::',$cookie_data);
			$wassup_id = $wassup_cookie[0];
			if (!empty($wassup_cookie[2])) { 
				$screen_res = $wassup_cookie[2];
			}
			if (!empty($wassup_cookie[3])) {
				$cookieIP = $wassup_cookie[3];
				if (!empty($wassup_cookie[4])) {
					$cookieHost = $wassup_cookie[4];
				}
			}
		}
		//### set screen resolution value from cookie or browser header data, if any
		if (empty($screen_res)) {
			if (isset($_COOKIE['wassup_screen_res'])) {
				$screen_res = attribute_escape($_COOKIE['wassup_screen_res']);
			} 
			if (empty($screen_res) && isset($_ENV['HTTP_UA_PIXELS'])) {
				//resolution in IE/IEMobile header sometimes
				$screen_res = str_replace('X','x',attribute_escape($_ENV['HTTP_UA_PIXELS']));
			}
			//if (empty($screen_res) && isset($_GET['wscr'])) {
			//	$screen_res = attribute_escape($_GET['wscr']);
			//} 
		}
		//#### Get the visitor's details from http header...
		$ipAddress = "";
		$hostname = "";
		if (isset($_SERVER["REMOTE_ADDR"])) {
			$ipAddress = $_SERVER["REMOTE_ADDR"];
			$IP = "";
			if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
				$proxy = $ipAddress;
				//in case of multiple forwarding
				$IP = validIP($_SERVER["HTTP_X_FORWARDED_FOR"]);
				if ($IP) {
				if ($cookieIP == $IP) {
					$hostname = $cookieHost;
				} else {
					$hostname = @gethostbyaddr($IP);
					//exclude dummy addresses...
					if (!empty($hostname) && $hostname != "unknown" && $hostname != "localhost.localdomain") {
					if (!validIP($proxy)) {
						$ipAddress = $IP;
					} else {
						$ipAddress = $proxy.",".$IP;
					}
					} else {
						$hostname = "";
					}
				}
				} //end if IP
			}
			if (empty($IP) || empty($hostname)) {
				$IP = $_SERVER["REMOTE_ADDR"];
				if (validIP($IP)) {
					if ($cookieIP == $IP) {
						$hostname = $cookieHost;
					} else {
						$hostname = @gethostbyaddr($IP);
					}
				} else {
					$hostname = "unknown";
				}
			}
		} //end if REMOTE_ADDR
		if (empty($IP)) { $IP = $ipAddress; }
		if (empty($hostname)) { $hostname = "unknown"; }

		//'referrer' must be cleaned when added to table, or used 
		// in a query on database, or when displayed to screen...
		// NOT before...otherwise tests for search engines and 
		//  search phrase will fail.
		$referrer = (isset($_SERVER['HTTP_REFERER'])? $_SERVER['HTTP_REFERER']: '');
    		$userAgent = (isset($_SERVER['HTTP_USER_AGENT']) ? trim($_SERVER['HTTP_USER_AGENT']) : '');
		if (strlen($userAgent) > 255) {
			$userAgent=substr(str_replace(array("  ","%20%20","++"),array(" ","%20","+"),$userAgent),0,255);
		}
    		$language = (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? attribute_escape($_SERVER['HTTP_ACCEPT_LANGUAGE']) : '');
    		$comment_user = (isset($_COOKIE['comment_author_'.COOKIEHASH]) ? utf8_encode($_COOKIE['comment_author_'.COOKIEHASH]) : '');

		$timestamp  = wassup_get_time(); //Add a timestamp to visit... 

	//#####Start recording visit....
	//## Exclude IPs on exclusion list... 
	if (empty($wassup_options->wassup_exclude) || empty($IP) ||
	     strstr($wassup_options->wassup_exclude,$IP) == FALSE) {
	
	//### Exclude requests for themes, plugins, and favicon from recordings
	if (stristr($urlRequested,"favicon.ico") === FALSE) {		//moved
	if (stristr($urlRequested,"/wp-content/plugins") === FALSE || stristr($urlRequested,"forum") !== FALSE || $hackercheck) {	//moved and modified to allow forum requests
	if (stristr($urlRequested,"/wp-content/themes") === FALSE || stristr($urlRequested,"comment") !== FALSE) {	//moved and modified to allow comment requests
		
	//# More recording exclusion controls
	if ($wassup_options->wassup_loggedin == 1 || !is_user_logged_in()) {
	if ($wassup_options->wassup_attack == 1 || stristr($userAgent,"libwww-perl") === FALSE ) {
	if (!is_404() || ($hackercheck && (stristr($urlRequested,"/wp-")!==FALSE || preg_match('/\.(php|ini|aspx?|dll|cgi)/i',$urlRequested)>0))) {	//don't record 404 pages...
		if (is_404()) {	//identify hackers
			$spam = 3;
			$urlRequested = '[404] '.$_SERVER['REQUEST_URI'];
		} else { 
			$spam = 0;
		}

	//#===================================================
	//####Start recording visit....
	$browser = "";
	$os = "";
	$spider = "";
	$feed = "";
	//Work-around for cookie rejection:
	//#assign a new wassup id and use it in dup check
	if (empty($wassup_id)) {
		//# Create a new id for this visit from a combination
		//#  of date/hour/min/ip/loggeduser/useragent/hostname.
		//#  It is not unique so that multiple visits from the 
		//#  same ip/userAgent within a 30 minute-period, can be 
		//#  tracked, even when session/cookies are disabled. 
		$temp_id = sprintf("%-060.60s", date('YmdH').str_replace(array(' ','http://','www.','/','.','\'','"',"\\",'$','-','&','+','_',';',',','>','<',':','#','*','%','!','@',')','(',), '', intval(date('i')/30).$IP.strrev($logged_user).strrev($userAgent).strrev($hostname).intval(date('i')/30)).date('HdmY').$hostname.rand());

		$wassup_id = md5($temp_id);
		//echo "temp_id=$temp_id\n";	//debug
	}

	//### Check for duplicates, previous spam check, and screen res.
	//# and gather previous settings to prevent redundant checks on
	//# same visitor.
	//# Dup: same wassup_id, timestamp <180 secs old, same URL
	$dup_urlrequest=0;
	$wpdb->query("SET wait_timeout = 7"); //don't wait for slow responses
	$recent = $wpdb->get_results("SELECT wassup_id, urlrequested, spam, screen_res, `timestamp`, browser, spider, os, feed, agent FROM ".$table_tmp_name." WHERE wassup_id='".$wassup_id."' AND `timestamp` >".($timestamp-180)." ORDER BY `timestamp` DESC");
	if (!empty($recent)) {
		//check 1st record only
		//record is dup if same url (with 'wscr' removed) and same user-agent
		if ($recent[0]->urlrequested == $urlRequested || $recent[0]->urlrequested == remove_query_arg('wscr',$urlRequested)) {
			if ($recent[0]->agent == $userAgent || empty($recent[0]->agent)) {
				$dup_urlrequest=1;
			}
		}
		//retrieve previous spam check results
		$spamresult = $recent[0]->spam;

		// check for screen resolution and update, if not previously recorded
		if (empty($recent[0]->screen_res) && !empty($screen_res)) {
			$wpdb->query("UPDATE $table_name SET screen_res = '$screen_res' WHERE wassup_id = '$wassup_id' AND screen_res = ''");
		}
		//get previously recorded settings for this visitor to
		//  avoid redundant tests
		if ($dup_urlrequest == 0) {
			if (empty($screen_res) && !empty($recent[0]->screen_res)) {
				$screen_res = $recent[0]->screen_res;
			}
			if ($spam == 0 && (int)$spamresult >0 ) {
				$spam = $spamresult;
			}
			if ($recent[0]->agent == $userAgent || empty($userAgent)) {
				$browser = $recent[0]->browser;
				$spider = $recent[0]->spider;
				$os = $recent[0]->os;
				//feed reader only if this page is feed
				if (!empty($recent[0]->feed) && is_feed()) {
					$feed = $recent[0]->feed;
				}
			}
		}
	}
	if ($debug_mode) {	//debug
		if (!empty($recent)) {
			echo "<br />\nRecent visit data found in wassup_tmp:\n"; //debug
			print_r($recent); //debug
			echo "\n";
			if ($dup_urlrequest == 1) {
				echo "\nDuplicate record!\n";
			}
			if ($recent[0]->agent != $userAgent) {
				echo "\nUser Agents NOT Identical:";
				echo "\n\tCurrent user agent: ".$userAgent;
				echo "\n\tPrevious user agent: $recent[0]->agent\n";
			}
		} else {
			echo "<br />\nNo Recent visit data found in wassup_tmp.\n"; //debug
		}
	}
	$wpdb->query("SET wait_timeout = 60");
	//
	//##### Extract useful visit information from http header..
	//# Exclude duplicates and redundant checks on multi-page visitors
	if ($dup_urlrequest == 0) {
		//#Identify user-agent...
		if (empty($browser) && empty($spider)) {
			$ua = new UADetector();
			if (!empty($ua->name)) {
			if ($ua->agenttype == "B") {
				$browser = $ua->name;
				if (!empty($ua->version)) { 
					$browser .= " ".wMajorVersion($ua->version);
					if (strstr($ua->version,"Mobile")!==false) {
						$browser .= " Mobile";
					}
				}
			} else {
				$spider = $ua->name;
				if ($ua->agenttype == "F") {
					if (!empty($ua->subscribers)) {
						$feed = $ua->subscribers;
					} else {
						$feed = $spider;
					}
				} elseif ($ua->agenttype == "H" || $ua->agenttype == "S") {	//it's a script injection bot|spammer
					if ($spam == "0") { $spam = 3; }
				}
			}
			$os = $ua->os;
			if (!empty($ua->resolution)) {
				//TODO?: Write 'wassup_screen_res' cookie, if none
				$screen_res = $ua->resolution;
			}
			} //end if $ua->name
		}
		//#Identify browsers...
		if (empty($browser) && empty($spider)) {
			list($browser,$os) = wGetBrowser($userAgent);
		}
		//#Identify spiders and feed readers...
		if (empty($spider) || stristr($spider,"unknown") || strlen($spider)<5 || (empty($ua) && !empty($feed))) {
			//# Some spiders, such as Yahoo and MSN, don't 
			//  always have a unique useragent. Do test against
			//  a list of known hostnames/IP to identify these 
			//  spiders. -Helene D.
			$spider_hosts='/^(65\.55\.\d{3}.\d{1,3}|.*\.crawl\.yahoo\.net|msnbot.*\.search\.msn\.com)$/';
			if (empty($browser) || strstr($browser,'N/A') || empty($os) || preg_match($spider_hosts,$hostname)>0 || preg_match("#\s?([a-z]+(?:bot|crawler|spider|reader))[^a-z]#i",$userAgent)>0 || $urlRequested == "robots.txt" || is_feed()) {
				list($spider,$spidertype,$feed) = @wGetSpider($userAgent,$hostname,$browser);
				//it's a browser
				if ($spidertype == "B" && !strstr($urlRequested,"robots.txt") ) { 
					if (empty($browser)) {
						$browser = $spider;
					}
					$spider = "";
					$feed = "";
				} elseif ($spidertype == "H" || $spidertype == "S") {	//it's a script injection bot|spammer
					if ($spam == "0") { $spam = 3; }
				}
			} //end if empty(browser)
		} //end if empty(spider)

	//spider exclusion control
	//# Spider exclusion control moved to avoid unneeded tests
	if ($wassup_options->wassup_spider == 1 || $spider == '') {

        //do spam exclusion controls, unless disabled in wassup_spamcheck
	if ($wassup_options->wassup_spamcheck == 1 && $spam == 0) {
		//### 1st Check for referrer spam...faster, if positive
		if ( $wassup_options->wassup_refspam == 1 && !empty($referrer) ) {
			//#...skip if referrer is own blog
			if (stristr($referrer,$wpurl) === FALSE && stristr($referrer,$blogurl) === FALSE) {
			// Do a control if it is Referrer Spam
			if (wGetSpamRef($referrer) == true) {
				$spam = 2;
				//$spamresult = $spam;
			}
			}
		}
        	
		//### Check for comment spammers...
		if ( $spam == 0 ) {
			//# some valid spiders to exclude from spam check
			$goodbot = false;
			if ($hostname!="" && !empty($spider)) {
			if (preg_match('/^(googlebot|msnbot|yahoo\!\ slurp|technorati)/i',$spider)>0 && preg_match('/\.(googlebot|live|msn|yahoo|technorati)\.(com|net)$/i',$hostname)>0){
				$goodbot = true;
			}
			}

		//# No duplicate spam testing in same session unless there 
		//#  is a forum page request or comment...
		if (isset($spamresult) && stristr($urlRequested,"comment") === FALSE && stristr($urlRequested,"forum") === FALSE && empty($comment_user) && empty($_POST['comment'])) {
			$spam = $spamresult;

		//# No spam check on known bots (google, yahoo,...) unless
		//#  there is a comment or forum page request...
		} elseif (empty($spider) || !$goodbot || stristr($urlRequested,"comment") !== FALSE || stristr($urlRequested,"forum") !== FALSE  || !empty($comment_user) ) { 

			// search for previous spammer detected by anti-spam plugin
			$spammerIP = 0;
			if (!empty($ipAddress)) {
				$checkauthor = New CheckComment;
				$checkauthor->tablePrefix = $wpdb->prefix;
				$spammerIP = $checkauthor->isSpammer($ipAddress);
				if ($spammerIP > 0) {	//is previous comment spam
					$spam = 1;
					//$spamresult = $spam;
					//update previous visits as spam, in case late detection
					if (!empty($recent) && $spamresult==0) {
						$wpdb->query("UPDATE $table_name SET spam='".$spam."' WHERE wassup_id='".$wassup_id."' AND spam='0'");
					}
				}

			}
			// search for spammer by hostname from a list of known spammer hosts
			if ($spam == 0 && !empty($hostname) && $hostname != "unknown") {
				if (wGetSpamRef($hostname) == true) {
					$spam = 1;
					//$spamresult = $spam;
				}
			}

			//#lastly check for comment spammers using Akismet API
			//#  Note: this may cause "header already sent" errors in some Wordpress configurations
			if ($wassup_options->wassup_spam == 1 && stristr($urlRequested,"comment") !== FALSE && $spam == 0) {
				$akismet_key = get_option('wordpress_api_key');
				$akismet_class = dirname(__FILE__).'/lib/akismet.class.php';
			if (!empty($akismet_key) && file_exists($akismet_class)) {
				// load array with comment data 
				$comment_user_email = (!empty($_COOKIE['comment_author_email_'.COOKIEHASH])? utf8_encode($_COOKIE['comment_author_email_'.COOKIEHASH]):"");
				$comment_user_url = (!empty($_COOKIE['comment_author_url_'.COOKIEHASH])? utf8_encode($_COOKIE['comment_author_url_'.COOKIEHASH]):"");
				//$comment_user_url = utf8_encode($_COOKIE['comment_author_url_'.COOKIEHASH]);
				$Acomment = array(
					'author' => $comment_user,
					'email' => $comment_user_email,
					'website' => $comment_user_url,
					'body' => (isset($_POST["comment"])? $_POST["comment"]:""),
					'permalink' => $urlRequested,
					'user_ip' => $ipAddress,
					'user_agent' => $userAgent);

				// instantiate an instance of the class 
				if (!class_exists('Akismet')) {
					include_once($akismet_class);
				}
				$akismet = new Akismet($wpurl, $akismet_key, $Acomment);
				// Check if it's spam
				if ( $akismet->isSpam() ) {
					$spam = 1;
					//retroactively update visitor's recent hits as spam
					//Note: this may cause spam to be recorded when spam recording is disabled because visitor was not identified as spammer until comment attempt
					if(!$akismet->errorsExist()) {
					if (!empty($recent) && $spamresult==0) {
						$wpdb->query("UPDATE $table_name SET spam='".$spam."' WHERE wassup_id='".$wassup_id."' AND spam='0'");
					}
					}
				}
			} //end if !empty(akismet_key)
			} //end if comment
 		} //end else empty($spider)

		} //end if wassup_spam == 1
	} //end if wassup_spamcheck == 1

	//identify hacker/bad activity attempts and assign spam=3
	if ($spam == 0 && $hackercheck) {
		if (is_admin() || stristr($urlRequested,"/wp-admin/")!== FALSE || stristr($urlRequested,"/wp-include/")!==FALSE) {
			//should not happen, if does Wordpress compromised
			$spam=3;
		}
	}

	//## Final exclusion control is spam...
	if ($spam == 0 OR ($wassup_options->wassup_spam == 1 AND $spam == 1) OR ($wassup_options->wassup_refspam == 1 AND $spam == 2) OR ($wassup_options->wassup_hack == 1 AND $spam == 3)) {
	if (stristr($urlRequested,"/wp-content/plugins/")===FALSE OR $spam == 3) {
		//###More user/referrer details for recording
		//#get language/locale info from hostname or referrer data
		$language = wGetLocale($language,$hostname,$referrer);

		//# get search engine and search string data from referrer 
		$searchengine="";
		$search_phrase="";
		$searchpage="";
		//don't check own blog for search engine data
		if ($debug_mode) {	//debug
			echo '<br />\n$Referrer="'.$referrer."<br/>\n".'$blog="'.$blogurl.'".'."\n"; //debug
			echo "test result=".stristr($referrer,$blogurl)."\n";
		}
		if (!empty($referrer) && stristr($referrer,$blogurl)!=$referrer) {
			if ($debug_mode) {	//debug
				echo '<br />\n$Referrer="'.$referrer.'" is NOT own blog. Checking for search engine data...'."\n"; //debug
			}
		list($searchengine,$search_phrase,$searchpage,$searchlang,$searchcountry)=explode("|",wGetSE($referrer));
		if ($search_phrase == '') {
			$se=seReferer($referrer);
			if (!empty($se['Query']))  {
				$search_phrase = $se['Query'];
				$searchpage = $se['Pos'];
				$searchdomain = $se['Se'];
			} else {
				$searchengine = "";
			}
		} else {
			$sedomain = parse_url($referrer);
			$searchdomain = $sedomain['host'];
		}
		if ($search_phrase != '')  {
			if (stristr($searchengine,"images")===FALSE) {
	   		// ATTENTION Position retrieved by referer in Google Images is 
	   		// the Position number of image NOT the number of items in the page like web search
	   			$searchpage=(int)($searchpage/10)+1;
	   		}
			if (!empty($searchengine)) {
				if (empty($searchcountry) && preg_match('/([a-z]\.)+/i',$searchdomain)) {
					$secountry = explode(".", $searchdomain);
	   				if (!empty($secountry[4])) {
						$clength=strlen($secountry[4]);
						if ($clength == 2) {
	   				    		$searchcountry = $secountry[4];
						} elseif (strlen($secountry[0])==2 && $clength >2) {
	   				    		$searchcountry = $secountry[0];
						}
	   				} elseif (!empty($secountry[3])) {
						$clength=strlen($secountry[3]);
						if ($clength == 2) {
	   				    		$searchcountry = $secountry[3];
						} elseif (strlen($secountry[0])==2 && $clength >2) {
	   				    		$searchcountry = $secountry[0];
						}
					} elseif ($searchcountry[2] != '' && strlen($secountry[2]) == 2) {
	   					$searchcountry = $secountry[2];
					}
				}
				if (!empty($searchcountry)) {
					$searchengine .= " ".strtoupper($searchcountry);
				}
			} else {
				$searchengine = $searchdomain;
			}
		} //end if search_phrase
		} //end if (!empty($referrer)
		if ($searchpage == "") {
			$searchpage = 0;
		}

		// #Record visit in wassup tables...
		// #create record to add to wassup tables...	
		$wassup_rec = array('wassup_id'=>$wassup_id, 
				'timestamp'=>$timestamp, 
				'ip'=>$ipAddress, 
				'hostname'=>$hostname, 
				'urlrequested'=>$urlRequested, 
				'agent'=>$userAgent,
				'referrer'=>$referrer, 
				'search'=>$search_phrase,
				'searchpage'=>$searchpage,
				'searchengine'=>$searchengine,
				'os'=>$os, 
				'browser'=>$browser, 
				'language'=>$language, 
				'screen_res'=>$screen_res, 
				'spider'=>$spider, 
				'feed'=>$feed, 
				'username'=>$logged_user, 
				'comment_author'=>$comment_user, 
				'spam'=>$spam);

		// Insert the record into the db
		insert_into_wp($table_name, $wassup_rec);
		// Insert the record into the wassup_tmp table too
		insert_into_wp($table_tmp_name, $wassup_rec);
		// Delete records older then 3 minutes
		if (((int)$timestamp)%17 == 0 ) {
			$wpdb->query("DELETE FROM $table_tmp_name WHERE `timestamp`<'".strtotime("-3 minutes", $timestamp)."'");
		}

        } //end if !wp-content/plugins
        } //end if $spam == 0

        } //end if wassup_spider
	} //end if dup_urlrequest == 0

        } //end if !is_404
        } //end if wassup_attack
        } //end if wassup_loggedin

        } //end if !themes
        } //end if !plugins
	} //end if !favicon

	//### Purge old records from wassup table
	//automatic database cleanup of old records...
	if ($wassup_options->delete_auto != "") {
	   // do purge every few visits to keep wassup fast...
	   if ( ((int)$timestamp)%119 == 0 ) {
	   	//use visit timestamp instead of current time for
	   	//  delete parameter
	   	//$to_date = wassup_get_time();
		$from_date = strtotime($wassup_options->delete_auto, $timestamp);
		//#check before doing delete as it could lock the table...
		if ((int)$wpdb->get_var("SELECT COUNT(id) FROM $table_name WHERE `timestamp`<'$from_date'") > 0) {
			$wpdb->query("DELETE FROM $table_name WHERE `timestamp`<'$from_date'");
		}
		// Optimize table once a day
		if ($timestamp > strtotime("24 hours", $wassup_options->wassup_optimize)) {
			//optimizing can be slow on large tables, so extend
			// script execution time
			set_time_limit(60*60*30); // run script up to 0.5 hour
			$wpdb->query("OPTIMIZE TABLE $table_name");
			$wassup_options->wassup_optimize = wassup_get_time();
                        $wassup_options->saveSettings();
		}
	   }
	} //end if delete_auto

	} //end if wassup_exclude
	} //end if !exclude_visit
	} //end if !is_admin
	} //end if loggeduser_level
	
	//### Notify admin if alert is set and wassup table > alert
	if ($wassup_options->wassup_remind_flag == 1) {
	   // check database size ~every 5 minutes to keep wassup fast...
	   if ( (time())%299 == 0 ) {
		$table_status = $wpdb->get_results("SHOW TABLE STATUS LIKE '$table_name'");
		foreach ($table_status as $fstatus) {
			$data_lenght = $fstatus->Data_length;
		}
		$tusage = ($data_lenght/1024/1024);
		if ($tusage > $wassup_options->wassup_remind_mb) {
			$recipient = get_bloginfo('admin_email');
			$sender = get_bloginfo('name').' <wassup_noreply@'.parse_url($blogurl,PHP_URL_HOST).'>';
                        $subject = "[ALERT]".__('WassUp Plugin table has reached maximum size!','wassup');
                        $message = __('Hi','wassup').",\n".__('you have received this email because your WassUp Database table at your Wordpress blog','wassup')." ($wpurl) ".__('has reached the maximum value you set in the options menu','wassup')." (".$wassup_options->wassup_remind_mb." Mb).\n\n";
                        $message .= __('This is only a reminder, please take the actions you want in the WassUp options menu','wassup')." (".get_bloginfo('url')."/wp-admin/admin.php?page=wassup-options).\n\n".__('This alert now will be removed and you will be able to set a new one','wassup').".\n\n";
                        $message .= __('Thank you for using WassUp plugin. Check if there is a new version available here:','wassup')." http://wordpress.org/extend/plugins/wassup/\n\n".__('Have a nice day!','wassup')."\n";
                        mail($recipient, $subject, $message, "From: $sender");
                        $wassup_options->wassup_remind_flag = 2;
                        $wassup_options->saveSettings();
		}
	   }
	} //if wassup_remind_flag
	if ($debug_mode) {
		if (!empty($wassup_rec)) {
			echo "<br />\nWassUp record data:\n";
			print_r($wassup_rec); //debug
			echo "<br />\n*** Visit recorded ***\n"; //debug
		} else {
			echo "<br />\n*** Visit was NOT recorded! ***\n"; //debug
		}
		echo "<br />\n--> \n";	//close comment tag to hide debug data from visitors 
		//restore normal mode
		ini_set('display_errors',$mode_reset);
	}
} //end function wassupAppend()

// Function to insert the item into the db
function insert_into_wp($wTable, $wassup_rec) {
	global $wpdb, $wassup_options;

	$wassup_table = $wassup_options->wassup_table;
	$wassup_tmp_table = $wassup_table . "_tmp";
	$delayed="";	//for delayed insert

	//check that wassup_rec is valid associative array
	if (is_array($wassup_rec) && !empty($wassup_rec['wassup_id'])) {
		if ($wTable == $wassup_table && (empty($wassup_options->wassup_dbengine) || stristr($wassup_options->wassup_dbengine,"isam"))) {
			$delayed="DELAYED";	//for delayed insert
		}
		//double-check that table exists to avoid errors displaying on blog page
        	if ($wpdb->get_var("SHOW TABLES LIKE '$wTable'") == $wTable) {

		//sanitize mySQL insert statement to prevent SQL injection
		if (method_exists($wpdb,'prepare')) {
			$insert = $wpdb->prepare("INSERT $delayed INTO $wTable (wassup_id, `timestamp`, ip, hostname, urlrequested, agent, referrer, search, searchpage, os, browser, language, screen_res, searchengine, spider, feed, username, comment_author, spam) VALUES ( %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s )",
	   			$wassup_rec['wassup_id'],
		   		$wassup_rec['timestamp'],
		   		$wassup_rec['ip'],
	   			$wassup_rec['hostname'],
		   		$wassup_rec['urlrequested'], 
		   		$wassup_rec['agent'],
	   			$wassup_rec['referrer'],
		   		$wassup_rec['search'],
		   		$wassup_rec['searchpage'], 
	   			$wassup_rec['os'],
		   		$wassup_rec['browser'], 
		   		$wassup_rec['language'], 
	   			$wassup_rec['screen_res'], 
		   		$wassup_rec['searchengine'],
		   		$wassup_rec['spider'], 
	   			$wassup_rec['feed'], 
		   		$wassup_rec['username'], 
		   		$wassup_rec['comment_author'], 
	   			$wassup_rec['spam']);
                } else { 
			//$clean_data = array_map('wSanitizeData',$wassup_rec);
			//use "insert delayed" instead of "insert" because of possibility of lock-outs due daily optimization and purges.
			$insert = sprintf("INSERT $delayed INTO %s (wassup_id, `timestamp`, ip, hostname, urlrequested, agent, referrer, search, searchpage, os, browser, language, screen_res, searchengine, spider, feed, username, comment_author, spam) VALUES ( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )",
				$wTable,
	   			wSanitizeData($wassup_rec['wassup_id']),
		   		wSanitizeData($wassup_rec['timestamp']),
		   		wSanitizeData($wassup_rec['ip']),
	   			wSanitizeData($wassup_rec['hostname']),
		   		wSanitizeData($wassup_rec['urlrequested']), 
		   		wSanitizeData($wassup_rec['agent']),
	   			wSanitizeData($wassup_rec['referrer']),
		   		wSanitizeData($wassup_rec['search']),
		   		wSanitizeData($wassup_rec['searchpage']), 
	   			wSanitizeData($wassup_rec['os']),
		   		wSanitizeData($wassup_rec['browser']), 
		   		wSanitizeData($wassup_rec['language']), 
	   			wSanitizeData($wassup_rec['screen_res']), 
		   		wSanitizeData($wassup_rec['searchengine']),
		   		wSanitizeData($wassup_rec['spider']), 
	   			wSanitizeData($wassup_rec['feed']), 
		   		wSanitizeData($wassup_rec['username']), 
		   		wSanitizeData($wassup_rec['comment_author']), 
	   			wSanitizeData($wassup_rec['spam']));
		} 
		$wpdb->query($insert);
		} //end if $wpdb->get_var
	} //end if is_array
} //end function insert_into_wp

//clean up data for insertion into mySQL to prevent SQL injection attacks
// Use as alternative to "wpdb::prepare" (Wordpress <2.3)
function wSanitizeData($var, $quotes=false) {
	global $wpdb;
	if (is_string($var)) {	//clean strings
		//sanitize urls with "clean_url" wordpress function
		$varstr = stripslashes($var);
		if (strstr($varstr, '://')) {
			$varstr = clean_url($var,'','db');
			if (empty($varstr)) {	//oops, clean_url chomp
				$varstr = attribute_escape(stripslashes($var));
			}
		} else {
			$varstr = attribute_escape($varstr);
		}
 		if ($quotes) {
			$var = "'". $varstr ."'";
		} else {
			$var=$varstr;
		}
	} elseif (is_bool($var) && $quotes) {   //convert boolean variables to binary boolean
		$var = ($var) ? 1 : 0;
	} elseif (is_null($var) && $quotes) {   //convert null variables to SQL NULL
		$var = "NULL";
	}
	//note numeric values do not need to be sanitized
	return $var;
} //end wSanitizeData

// function to print out a chart's preview in the dashboard for WP < 2.7
function wassupDashChart() {
	global $wpdb, $wassup_options;
	$table_name = $wassup_options->wassup_table;
	if ($wassup_options->wassup_dashboard_chart == 1) {
		$chart_type = ($wassup_options->wassup_chart_type >0)? $wassup_options->wassup_chart_type: "2";
		$to_date = wassup_get_time();
		$Chart = New MainItems($table_name,"",$to_date);
        	$chart_url = $Chart->TheChart(1, "400", "125", "", $chart_type, "bg,s,00000000", "dashboard"); ?>
	<h3>WassUp <?php _e('Stats','wassup'); ?> <cite><a href="admin.php?page=<?php echo WASSUPFOLDER; ?>"><?php _e('More','wassup'); ?> &raquo;</a></cite></h3>
	<div class="placeholder" align="left">
		<img src="<?php echo $chart_url; ?>" alt="WassUp <?php _e('visitor stats chart','wassup'); ?>"/>
	</div>
<?php	}
} //end function wassupDashChart

function wGetQueryPairs($url){
	$parsed_url = parse_url($url);
	$tab=parse_url($url);
	$host = $tab['host'];
	if(key_exists("query",$tab)){
	 $query=$tab["query"];
	 return explode("&",$query);
	} else {
	 return null;
	}
}

function array_search_extended($file,$str_search) {
	foreach($file as $key => $line) {
		if (strpos($line, $str_search)!== FALSE) {
			return $key;
		}
	}
	return false;
}

function seReferer($ref = false){
	$SeReferer = (is_string($ref) ? $ref : mb_convert_encoding(strip_tags($_SERVER['HTTP_REFERER']), "HTML-ENTITIES", "auto"));
	if ($SeReferer == "") { return false; }

	//Check against Google, Yahoo, MSN, Ask and others
	if(preg_match("#^https?://([^/]+).*[&\?](prev|q|p|s|search|searchfor|as_q|as_epq|query|keywords|term|encquery)=([^&]+)#i",$SeReferer,$pcs) > 0){
		$SeDomain = trim(strtolower($pcs[1]));
		if ($pcs[2] == "encquery") { 
			$SeQuery = " *".__("encrypted search","wassup")."* ";
		} else { 
			$SeQuery = $pcs[3];
		}

	//Check for search engines that show search results in a url-path "/" structure (ex: Dogpile.com)
	} elseif (preg_match("#^https?://([^/]+).*/(?:results|search)/web/([^/]+)/(\d+)?#i", $SeReferer,$pcs)>0){
		$SeDomain = trim(strtolower($pcs[1]));
		$SeQuery = $pcs[2];
		if (!empty($pcs[3])) {
			$sePos = (int)$pcs[3];
		}
	}
	unset ($pcs);

	//-- We have a query
	if(isset($SeQuery)){ 
		// The Multiple URLDecode Trick to fix DogPile %XXXX Encodes
		if (strstr($SeQuery,'%')) {
			$OldQ=$SeQuery;
			$SeQuery=urldecode($SeQuery);
			while($SeQuery != $OldQ){
				$OldQ=$SeQuery;
				$SeQuery=urldecode($SeQuery);
			}
		}
		if (!isset($SePos)) { 
			//if(preg_match("/[&\?](start|startpage|b|first|stq)=([0-9]*)/i",$SeReferer,$pcs)){
			if (preg_match("#[&\?](start|startpage|b|first|stq|pi|page)[=/](\d+)#i",$SeReferer,$pcs)) {
				$SePos = $pcs[2];
			} else {
				$SePos = 1;
			}
			unset ($pcs);
		}
    		$searchdata=array("Se"=>$SeDomain,
        			  "Query"=>$SeQuery,
				  "Pos"=>$SePos,
				  "Referer"=>$SeReferer);
	} else {
		$searchdata=false;
	}
	return $searchdata;
}

function wGetSE($referrer = null){
	$key = null;
	$search_phrase="";
	$searchpage="";
	$searchengine="";
	$searchlang="";
	$selocale="";
	//list of well known search engines. 
	//  Structure: "SE Name|SE Domain(partial+unique)|query_key|page_key|language_key|locale|"
	$lines = array("Google|www.google.|q|start|hl||",
		"Google|www.google.|as_q|start|hl||",	//advanced query
		"Google Images|images.google.|prev|start|hl||", 
		"Bing|.bing.com|q|first|||", 
		"MSN|search.msn.|q|first|||", 
		"Windows Live|search.live.com|q|first|||",
		"Yahoo|search.yahoo.|p||||", 
		"Yahoo|answers.yahoo.com|p||||", 
		"Aol|.aol.|query||||",
		"Aol|aolrecherches.aol.fr|query|||fr|",
		"100Links|100links.supereva.it|q|||it|",
		"2020Search|.2020search.com|st||||",
		"abcsearch.com|abcsearch.com|terms||||",
		"ABC Sok|verden.abcsok.no|q|||no|",
		"Alice|search.alice.it|qs|||it|", 
		"Altavista|.altavista.com|q||||",
		"Altavista|.altavista.com|aqa||||",	//advanced query
		"Alexa|alexa.com|q||||","Alltheweb|alltheweb.com|q||||",
		"Arianna|arianna.libero.it|query|||it|",
		"Ask|ask.com|ask||||","Ask|ask.com|q||||",
		"Atlas|search.atlas.cz|q|||cz|",
		"Beedly INT|beedly.us|q||||",
		"bluewin|bluewin.ch|query|||ch|", 
		"Centrum|search.centrum.cz|q|||cz|",
		"Clarence|search.clarence.com|q||||",
		"Conduit|search.conduit.com|q||||",
		"DMOZ|search.dmoz.org|search||||", 
		"Dogpile|dogpile.com|q||||",
		"earthlink.net|earthlink.net|q||||",
		"Excite|excite.|q||||",
		"Gazzetta|search.gazzetta.it|q|||it|",
		"Godago|.godago.com|keywords||||",
		"Good Search|goodsearch.com|Keywords||||", 
		"Google Blog|blogsearch.google.|as_q||||", 
		"Google Blog|blogsearch.google.|q||||",
		"Google Groups|groups.google.|q||||", 
		"Google Translate|translate.google.|prev||||",
		"HotBot|hotbot.|query||||",
		"ICQ Search|.icq.com|q||||",
		"Il Trovatore|.iltrovatore.it|q|||it|",
		"Incredimail|.incredimail.com|q||||",
		"ItaliaPuntoNet|italiapuntonet.net|search||||",
		"ixquick|ixquick.com|query||||", 
		"Jyxo|jyxo.1188.cz|q|||cz|",
		"Jumpy|.mediaset.it|searchWord|||it|",
		"Kataweb|kataweb.it|q|||it|", 
		"Kvasir|kvasir.no|searchExpr|||no|", 
		"Lycos|.lycos.it|query|||it|",
		"Lycos|lycos.|q||||",
		"My Search|mysearch.com|searchfor||||",
		"My Way|mysearch.myway.com|searchfor||||",
		"Metacrawler|metacrawler.|q||||", 
		"Metager|metager.de|eingabe|||de|",
		"Netscape Search|search.netscape.com|query||||",
		"Overture|overture.com|Keywords||||",
		"OpenDir|.opendir.cz|cohledas|||cz|",
		"PagineGialle|paginegialle.it|qs|||it|",
		"Picsearch|.picsearch.com|q||||",
		"Search|.search.com|q||||", 
		"Search|.search.it|srctxt|||it|",
		"Seznam|.seznam.cz|q|||cz|", 
		"Start.no|start.no|q||||", 
		"StartNow|search.startnow.|q||||",
		"Supereva|supereva.it|q|||it|",
		"Teoma|teoma.com|q||||",
		"T-Online|suche.t-online.de|q|||de|",
		"Tiscali|search-dyn.tiscali.|key||||",
		"Tiscali|.tiscali.|query||||",
		"Virgilio|.virgilio.it|qs|||it|",
		"Voil|voila.fr|kw|||fr|",
		"Web|.web.de|su|||de|", 
		"Yandex|yandex.ru|text|||ru|", 
		"Zoohoo|.zoohoo.cz|q|||cz|", 
		"Zoznam|.zoznam.sk|s|||sk|",
		"|...|q||||",	//dummy record to prevent "SK" being appended to search domains not on this list
	);
	foreach($lines as $line_num => $se) {
		list($nome,$domain,$key,$page,$lang,$selocale)=explode("|",$se);
		//match on both domain and key..
		$domain_regex='/^https?\:\/\/.*'.preg_quote($domain).'.*[&?]'.$key.'=([^&]+)/i';
		if (preg_match($domain_regex,$referrer,$match)>0) {
			// found it!
			$search_phrase=attribute_escape(urldecode($match[1]));
			$searchengine = $nome;
			$variables = wGetQueryPairs($referrer);
			// The SE is Google Images
			if ($nome == "Google Images" || $nome == "Google Translate") {
				$rightkey = array_search_extended($variables, 'prev=');
				$variables = attribute_escape(preg_replace('#prev=/\w+\?q=#i', '', urldecode($variables[$rightkey])));
				$variables = explode("&",$variables);
				$search_phrase=attribute_escape(urldecode($variables[0]));
			} 
			$i = count($variables);
			while($i--){
				$tab=explode("=",$variables[$i]);
				if($tab[0] == $key && empty($search_phrase)){
					$search_phrase=attribute_escape(urldecode($tab[1]));
				} else {
					if (!empty($page) && $page == $tab[0]) {
						$searchpage = $tab[1];
					}
					if (!empty($lang) && $lang == $tab[0]) {
						$searchlang = $tab[1];
					}
				}
			} //end while
			//if (!empty($search_phrase)) {
			//	return ($nome."|".$search_phrase."|".$searchpage."|".$searchlang."|".$selocale."|");
			//}
			break 1;
		} elseif (@strpos($referrer,$domain)!==FALSE) {
			$searchengine = $nome;
		} //end preg_match
	} //end foreach
	//search engine or key is not in list, so check for general search phrase instead
	if (empty($search_phrase)) {
		//unset($nome,$domain,$key,$page,$lang);

	//Check for general search phrases
	if (preg_match("#^https?://([^/]+).*[&?](q|s|search|searchfor|as_q|as_epq|query|keywords?|term|encquery)=([^&]+)#i",$referrer,$pcs) > 0){
		if (empty($searchengine)) {
			$searchengine = trim(strtolower($pcs[1]));
		}
		if ($pcs[2] == "encquery") { 
			$search_phrase = " *".__("encrypted search","wassup")."* ";
		} else { 
			$search_phrase = $pcs[3];
		}

	//Check separately for queries that use nonstandard search variable
	// names to avoid retrieving values like "p=parameter" when "q=query" exists
	} elseif(preg_match("#^https?://([^/]+).*(?:results|search|query).*[&?](aq|as|p|su|kw|k|qo|qp|qs|string)=([^&]+)#i",$referrer,$pcs) > 0) {
		if (empty($searchengine)) {
			$searchengine = trim(strtolower($pcs[1]));
		}
		$search_phrase = $pcs[3];
	}
	} //end if empty(search_phrase)
	if (!empty($search_phrase)) {
		if (empty($searchpage) && preg_match("#[&\?](start|startpage|b|first|stq|pi|page)[=/](\d+)#i",$referrer,$pcs)>0) {
			$searchpage = $pcs[2];
		}
	}
	return ($searchengine."|".$search_phrase."|".$searchpage."|".$searchlang."|".$selocale."|");
}

//extract browser and platform info from a user agent string and
// return the values in an array: 0->browser 1->os. -Helene D. 6/7/08.
function wGetBrowser($agent="") {
	if (empty($agent)) { $agent = $_SERVER['HTTP_USER_AGENT']; }
	$browsercap = array();
	$browscapbrowser = "";
	$browser = "";
	$os = "";
	//check PHP browscap data for browser and platform, when available
	if (ini_get("browscap") != "" ) {
		$browsercap = get_browser($agent,true);
		if (!empty($browsercap['platform'])) {
		if (stristr($browsercap['platform'],"unknown") === false) {
			$os = $browsercap['platform'];
			if (!empty($browsercap['browser'])) {
				$browser = $browsercap['browser'];
			} else {
				$browser = $browsercap['parent'];
			}
			if (!empty($browsercap['version'])) {
				$browser = $browser." ".wMajorVersion($browsercap['version']);
			}
		}
		}
		//reject generic browscap browsers (ex: mozilla, default)
		if (preg_match('/^(mozilla|default|unknown)/i',$browser) > 0) {
			$browscapbrowser = "$browser";	//save just in case
			$browser = "";
		}
	}
	$os = trim($os); 
	$browser = trim($browser);

	//use Detector class when browscap is missing or browser is unknown
	if ( $os == "" || $browser == "") {
		$dip = &new Detector("", $agent);
		$browser =  trim($dip->browser." ".wMajorVersion($dip->browser_version));
		if ($dip->os != "" && $dip->os != "N/A") {
			$os = trim($dip->os." ".$dip->os_version);
		}

		//use saved browscap info, if Detector had no browser result
		if (!empty($browscapbrowser) && ($browser == "" || $browser == "N/A")) {
			$browser = $browscapbrowser;
		}
	}
	return array($browser,$os);
} //end function wGetBrowser

//return a major version # from a version string argument
function wMajorVersion($versionstring) {
	$version=0;
	if (!empty($versionstring)) {
		$n = strpos($versionstring,'.');
		if ($n >0) {
			$version= (int) substr($versionstring,0,$n);
		}
		if ($n == 0 || $version == 0) {
			$p = strpos($versionstring,'.',$n+1);
			if ($p) $version= substr($versionstring,0,$p);
		}
	}
	if ($version > 0) {
		return $version;
	} else {
		return $versionstring;
	}
}

//extract spider information from a user agent string and return an array
// with values: (name, type=[R|B|F|H|L|S|V], feed subscribers)
// All spider types:  R=robot,  B=browser/downloader,  F=feed reader, 
//	H=hacker/spoofer/injection bot,  L=Link checker/sitemap generator, 
//	S=Spammer/email harvester,  V=css/html Validator
function wGetSpider($agent="",$hostname="", $browser=""){
	if (empty($agent)) { $agent = $_SERVER['HTTP_USER_AGENT']; }
	$ua = trim($agent);
	if (empty($ua)) {	//nothing to do...
		return false;
	} 
	$spiderdata=false;
	$crawler = "";
	$feed = "";
	$os = "";
	//## Identify obvious script injection bots 
	if (stristr('location.href',$ua)!==FALSE) {
		$crawlertype = "H";
		$crawler = "Script Injection bot";
	} elseif (preg_match('/(<|&lt;|&#60;)a(\s|%20|&#32;|\+)href/i',$ua)>0) {
		$crawlertype = "H";
		$crawler = "Script Injection bot";
	} elseif (preg_match('/(<|&lt;|&#60;)script/i',$ua)>0) {
		$crawlertype = "H";
		$crawler = "Script Injection bot";
	} elseif (preg_match('/select.*(\s|%20|\+|%#32;)from(\s|%20|\+|%#32;)wp_/i',$ua)>0) {
		$crawlertype = "H";
		$crawler = "Script Injection bot";
	}
	if (!empty($crawler)) {
		$spiderdata=array($crawler,$crawlertype,$feed);
	} 
	//## check for crawlers that mis-identify themselves as a browser
	//#  but come from a known crawler domain - the most common of
	//#  these are MSN (ie6,win2k3), and Yahoo!
	if ($spiderdata===false && !empty($hostname)) {
		if (substr($hostname,-16) == ".crawl.yahoo.net") {
			if (stristr($ua,"Slurp")) {
				$crawler = "Yahoo!Slurp";
				$crawlertype="R";
			} else {
				$crawler = "Yahoo!";
				$crawlertype="R";
			}
		} elseif (substr($hostname,0,6) == "65.55.") {
			$crawler = "MSNBot";
			$crawlertype="R";
		} elseif (substr($hostname,-7) == "msn.com" && strpos($hostname,"msnbot")!== FALSE) {
			$crawler = "MSNBot";
			$crawlertype="R";
		}
	}
	if (!empty($crawler)) {
		$spiderdata=array($crawler,$crawlertype,$feed);
	}
	//## check for crawlers that identify themselves clearly
	//#  in their user agent string
	if ($spiderdata===false) {
	if (!empty($hostname)) {
		//Check user agent for the words bot, spider, and crawler
		if (preg_match("#(\w+[ \-_]?(bot|spider|crawler|reader|seeker))[0-9/ -:_.;\)]#",$ua,$matches) > 0) {
			$crawler = $matches[1];
		}
	}
	unset ($matches);
	//## check browscap data for crawler info., when available
	if (empty($crawler) && ini_get("browscap") != "" ) {
		$browsercap = get_browser($ua,true);
		//if no platform(os), assume crawler...
		if (!empty($browsercap['platform'])) {
			if ( $browsercap['platform'] != "unknown") {
				$os = $browsercap['platform'];
			}
		}
		if (!empty($browsercap['crawler']) || !empty($browsercap['stripper']) || $os == "") {
			if (!empty($browsercap['browser'])) {
				$crawler = $browsercap['browser'];
			} else {
				$crawler = $browsercap['parent'];
			}
			if (!empty($browsercap['version'])) {
				$crawler = $crawler." ".$browsercap['version'];
			}
		}
		//reject unknown browscap crawlers (ex: default)
		if (preg_match('/^(default|unknown)/i',$crawler) > 0) {
			$crawler = "";
		}
	}

	//get crawler info. from a known list of bots and feedreaders that
	// don't list their names first in UA string.
	//Note: spaces are removed from UA string for the bot comparison
	$crawler = trim($crawler);
	if (empty($crawler)) {
		$agent=str_replace(" ","",$ua);
		$key = null;
		// array format: "Spider Name|UserAgent keywords (no spaces)| Spider type (R=robot, B=Browser/downloader, F=feedreader, H=hacker, L=Link checker, M=siteMap generator, S=Spammer/email harvester, V=CSS/Html validator)
		$lines = array("Googlebot|Googlebot/|R|", 
			"Yahoo!|Yahoo!Slurp|",
			"FeedBurner|FeedBurner|F|",
			"AboutUsBot|AboutUsBot/|R|", 
			"80bot|80legs.com|R|", 
			"Aggrevator|Aggrevator/|F|", 
			"AlestiFeedBot|AlestiFeedBot||", 
			"Alexa|ia_archiver|R|", "AltaVista|Scooter-|R|", 
			"AltaVista|Scooter/|R|", "AltaVista|Scooter_|R|", 
			"AMZNKAssocBot|AMZNKAssocBot/|R|",
			"AppleSyndication|AppleSyndication/|F|",
			"Apple-PubSub|Apple-PubSub/|F|",
			"Ask.com/Teoma|AskJeeves/Teoma)|R|",
			"Ask Jeeves/Teoma|ask.com|R|",
			"AskJeeves|AskJeeves|R|", 
			"Baiduspider|www.baidu.com/search/spider|R|",
			"BlogBot|BlogBot/|F|", "Bloglines|Bloglines/|F|",
			"Blogslive|Blogslive|F|",
			"BlogsNowBot|BlogsNowBot|F|",
			"BlogPulseLive|BlogPulseLive|F|",
			"IceRocket BlogSearch|icerocket.com|F|",
			"Charlotte|Charlotte/|R|", 
			"Xyleme|cosmos/0.|R|", "cURL|curl/|R|",
			"Daumoa|Daumoa-feedfetcher|F|",
			"Daumoa|DAUMOA|R|",
			"Daumoa|.daum.net|R|",
			"Die|die-kraehe.de|R|", 
			"Diggit!|Digger/|R|", 
			"disco/Nutch|disco/Nutch|R|",
			"DotBot|DotBot/|R|",
			"Emacs-w3|Emacs-w3/v|", 
			"ananzi|EMC|", 
			"EnaBot|EnaBot|", 
			"esculapio|esculapio/|", "Esther|esther|", 
			"everyfeed-spider|everyfeed-spider|F|", 
			"Evliya|Evliya|", "nzexplorer|explorersearch|", 
			"eZ publish Validator|eZpublishLinkValidator|",
			"FacebookExternalHit|facebook.com/externalhit|R|",
			"FastCrawler|FastCrawler|R|", 
			"FDSE|FDSErobot|R|", 
			"Feed::Find|Feed::Find|",
			"FeedDemon|FeedDemon/|F|",
			"FeedHub FeedFetcher|FeedHub|F|", 
			"Feedreader|Feedreader|F|", 
			"Feedshow|Feedshow|F|", 
			"Feedster|Feedster|F|",
			"FeedTools|feedtools|F|",
			"Feedfetcher-Google|Feedfetcher-google|F|", 
			"Felix|FelixIDE/|", 
			"FetchRover|ESIRover|", 
			"fido|fido/|", 
			"Fish|Fish-Search-Robot|", "Fouineur|Fouineur|", 
			"Freecrawl|Freecrawl|R|", 
			"FriendFeedBot|FriendFeedBot/|F|",
			"FunnelWeb|FunnelWeb-|", 
			"gammaSpider|gammaSpider|", "gazz|gazz/|", 
			"GCreep|gcreep/|", 
			"GetRight|GetRight|R|", 
			"GetURL|GetURL.re|", "Golem|Golem/|", 
			"Google Images|Googlebot-Image|R|",
			"Google AdSense|Mediapartners-Google|R|", 
			"Google Desktop|GoogleDesktop|F|", 
			"GreatNews|GreatNews|F|", 
			"Gregarius|Gregarius/|F|",
			"Gromit|Gromit/|", 
			"gsinfobot|gsinfobot|", 
			"Gulliver|Gulliver/|", "Gulper|Gulper|", 
			"GurujiBot|GurujiBot|", 
			"havIndex|havIndex/|",
			"heritrix|heritrix/|", "HI|AITCSRobot/|",
			"HKU|HKU|", "Hometown|Hometown|", 
			"HostTracker|host-tracker.com/|R|",
			"ht://Dig|htdig/|R|", "HTMLgobble|HTMLgobble|", 
			"Hyper-Decontextualizer|Hyper|", 
			"iajaBot|iajaBot/|", 
			"IBM_Planetwide|IBM_Planetwide,|", 
			"ichiro|ichiro|", 
			"Popular|gestaltIconoclast/|", 
			"Ingrid|INGRID/|", "Imagelock|Imagelock|", "IncyWincy|IncyWincy/1.|", "Informant|Informant|", 
			"InfoSeek|InfoSeek|", 
			"InfoSpiders|InfoSpiders/|", "Inspector|inspectorwww/1.|", "IntelliAgent|'IAGENT/'|", 
			"ISC Systems iRc Search|ISCSystemsiRcSearch|", 
			"Israeli-search|IsraeliSearch/|", 
			"IRLIRLbot/|IRLIRLbot|",
			"Italian Blog Rankings|blogbabel|F|", 
			"Jakarta|Jakarta|", "Java|Java/|", "JBot|JBot|", 
			"JCrawler|JCrawler/|", 
			"JoBo|JoBo|", "Jobot|Jobot/|", "JoeBot|JoeBot/|",
			"JumpStation|jumpstation|", 
			"image.kapsi.net|image.kapsi.net/|R|", 
			"kalooga/kalooga|kalooga/kalooga|", 
			"Katipo|Katipo/|", "KDD-Explorer|KDD-Explorer/|", 
			"KIT-Fireball|KIT-Fireball/|", 
			"KindOpener|KindOpener|", "kinjabot|kinjabot|", 
			"KO_Yappo_Robot|yappo.com/info/robot.html|", 
			"Krugle|Krugle|", 
			"LabelGrabber|LabelGrab/|",
			"Larbin|larbin_|",
			"libwww-perl|libwww-perl|", 
			"lilina|Lilina|", 
			"Link|Linkidator/|", "LinkWalker|LinkWalker|L|", 
			"LiteFinder|LiteFinder|", 
			"logo.gif|logo.gif|", "LookSmart|grub-client|",
			"Lsearch/sondeur|Lsearch/sondeur|", 
			"Lycos|Lycos/|", 
			"Magpie|Magpie/|", "MagpieRSS|MagpieRSS|", 
			"Mail.ru|Mail.ru|", 
			"marvin/infoseek|marvin/infoseek|", 
			"Mattie|M/3.|", "MediaFox|MediaFox/|", 
			"Megite2.0|Megite.com|", 
			"NEC-MeshExplorer|NEC-MeshExplorer|", 
			"MindCrawler|MindCrawler|", 
			"Missigua Locator|Missigua Locator|", 
			"MJ12bot|MJ12bot|", "mnoGoSearch|UdmSearch|", 
			"MOMspider|MOMspider/|", "Monster|Monster/v|", 
			"Moreover|Moreoverbot|", "Motor|Motor/|", 
			"MSNBot|MSNBOT/|R|", "MSN|msnbot.|R|",
			"MSRBOT|MSRBOT|R|", "Muninn|Muninn/|", 
			"Muscat|MuscatFerret/|", 
			"Mwd.Search|MwdSearch/|", 
			"MyBlogLog|Yahoo!MyBlogLogAPIClient|F|",
			"Naver|NaverBot|","Naver|Cowbot|",
			"NDSpider|NDSpider/|", 
			"Nederland.zoek|Nederland.zoek|", 
			"NetCarta|NetCarta|", "NetMechanic|NetMechanic|", 
			"NetScoop|NetScoop/|", 
			"NetNewsWire|NetNewsWire|", 
			"NewsAlloy|NewsAlloy|",
			"newscan-online|newscan-online/|", 
			"NewsGatorOnline|NewsGatorOnline|", 
			"Exalead NG|NG/|R|", 
			"NHSE|NHSEWalker/|", "Nomad|Nomad-V|", 
			"Nutch/Nutch|Nutch/Nutch|", 
			"ObjectsSearch|ObjectsSearch/|", 
			"Occam|Occam/|", 
			"Openfind|Openfind|", 
			"OpiDig|OpiDig|", 
			"Orb|Orbsearch/|", 
			"OSSE Scanner|OSSEScanner|", 
			"OWPBot|OWPBot|", 
			"Pack|PackRat/|", "ParaSite|ParaSite/|", 
			"Patric|Patric/|", 
			"PECL::HTTP|PECL::HTTP|", 
			"PerlCrawler|PerlCrawler/|", 
			"Phantom|Duppies|", "PhpDig|phpdig/|", 
			"PiltdownMan|PiltdownMan/|", 
			"Pimptrain.com's|Pimptrain|", "Pioneer|Pioneer|", 
			"Portal|PortalJuice.com/|", "PGP|PGP-KA/|", 
			"PlumtreeWebAccessor|PlumtreeWebAccessor/|", 
			"Poppi|Poppi/|", "PortalB|PortalBSpider/|", 
			"psbot|psbot/|", 
			"R6_CommentReade|R6_CommentReade|", 
			"R6_FeedFetcher|R6_FeedFetcher|", 
			"radianrss|RadianRSS|", 
			"Raven|Raven-v|", 
			"relevantNOISE|relevantnoise.com|",
			"Resume|Resume|", "RoadHouse|RHCS/|", 
			"RixBot|RixBot|",
			"Robbie|Robbie/|", "RoboCrawl|RoboCrawl|", 
			"RoboFox|Robofox|",
			"Robozilla|Robozilla/|", 
			"Rojo|rojo1|F|", 
			"Roverbot|Roverbot|", 
			"RssBandit|RssBandit|", 
			"RSSMicro|RSSMicro.com|F|",
			"Ruby|Rfeedfinder|", 
			"RuLeS|RuLeS/|", 
			"Runnk RSS aggregator|Runnk|", 
			"SafetyNet|SafetyNet|", 
			"Sage|(Sage)|F|",
			"SBIder|sitesell.com|R|", 
			"Scooter|Scooter/|", 
			"ScoutJet|ScoutJet|",
			"SearchProcess|searchprocess/|", 
			"Seekbot|seekbot.net|R|", 
			"SimplePie|SimplePie/|F|", 
			"Sitemap Generator|SitemapGenerator|", 
			"Senrigan|Senrigan/|", 
			"SeznamBot|SeznamBot/|R|",
			"SeznamScreenshotator|SeznamScreenshotator/|R|",
			"SG-Scout|SG-Scout|", "Shai'Hulud|Shai'Hulud|", 
			"Simmany|SimBot/|", 
			"SiteTech-Rover|SiteTech-Rover|", 
			"shelob|shelob|", 
			"Sleek|Sleek|", 
			"Slurp|.inktomi.com/slurp.html|R|",
			"Snapbot|.snap.com|R|", 
			"SnapPreviewBot|SnapPreviewBot|R|",
			"Smart|ESISmartSpider/|", 
			"Snooper|Snooper/b97_01|", "Solbot|Solbot/|", 
			"Sphere Scout|SphereScout|",
			"Sphere|sphere.com|R|",
			"spider_monkey|mouse.house/|",
			"SpiderBot|SpiderBot/|", "Spiderline|spiderline/|",
			"SpiderView(tm)|SpiderView|", 
			"SragentRssCrawler|SragentRssCrawler|F|",
			"Site|ssearcher100|",
			"StackRambler|StackRambler|", 
			"Strategic Board Bot|StrategicBoardBot|", 
			"Suke|suke/|", 
			"SummizeFeedReader|SummizeFeedReader|F|", 
			"suntek|suntek/|", 
			"SurveyBot|SurveyBot|", 
			"Sygol|.sygol.com|", 
			"Syndic8|Syndic8|F|", 
			"TACH|TACH|", "Tarantula|Tarantula/|",
			"tarspider|tarspider|", "Tcl|dlw3robot/|", 
			"TechBOT|TechBOT|", "Technorati|Technoratibot|",
			"Teemer|Teemer|", "Templeton|Templeton/|",
			"TitIn|TitIn/|", "TITAN|TITAN/|", 
			"Twiceler|.cuil.com/twiceler/|R|",
			"Twiceler|.cuill.com/twiceler/|R|",
			"Twingly|twingly.com|R|",
			"UCSD|UCSD-Crawler|", "UdmSearch|UdmSearch/",
			"UniversalFeedParser|UniversalFeedParser|", 
			"UptimeBot|uptimebot|", 
			"URL_Spider|URL_Spider_Pro/|R|", 
			"VadixBot|VadixBot|", "Valkyrie|Valkyrie/", 
			"Verticrawl|Verticrawlbot|", 
			"Victoria|Victoria/", 
			"vision-search|vision-search/|", 
			"void-bot|void-bot/", "Voila|VoilaBot|",
			"Voyager|.kosmix.com/html/crawler|R|",
			"VWbot|VWbot_K/|", 
			"W3C_Validator|W3C_Validator/|V|",
			"w3m|w3m/|B|", "W3M2|W3M2/|", "w3mir|w3mir/|", 
			"w@pSpider|w@pSpider/|", 
			"WallPaper|CrawlPaper/|",
			"WebCatcher|WebCatcher/|", 
			"webCollage|webcollage/|R|", 
			"webCollage|collage.cgi/|R|", 
			"WebCopier|WebCopierv|R|",
			"WebFetch|WebFetch|R|", "WebFetch|webfetch/|R|", 
			"WebMirror|webmirror/|", 
			"webLyzard|webLyzard|", "Weblog|wlm-|", 
			"WebReaper|webreaper.net|R|", 
			"WebVac|webvac/|", "webwalk|webwalk|", 
			"WebWalker|WebWalker/|", "WebWatch|WebWatch|", 
			"WebStolperer|WOLP/|", 
			"Wells Search II|WellsSearchII|", 
			"Wget|Wget/|",
			"whatUseek|whatUseek_winona/|", 
			"whiteiexpres/Nutch|whiteiexpres/Nutch|",
			"wikioblogs|wikioblogs|", 
			"WikioFeedBot|WikioFeedBot|", 
			"WikioPxyFeedBo|WikioPxyFeedBo|","Wild|Hazel's|", 
			"Wired|wired-digital-newsbot/|", 
			"Wordpress Pingback/Trackback|Wordpress|", 
			"WWWC|WWWC/|", 
			"XGET|XGET/|", 
			"yacybot|yacybot|",
			"Yahoo FeedSeeker|YahooFeedSeeker|",
			"Yahoo MMAudVid|Yahoo-MMAudVid/|R|",
			"Yahoo MMCrawler|Yahoo-MMCrawler/|R|",
			"Yahoo!SearchMonkey|Yahoo!SearchMonkey|R|",
			"YahooSeeker|YahooSeeker/|R|",
			"YoudaoBot|YoudaoBot|R|", 
			"Tailrank|spinn3r.com/robot|R|",
			"Tailrank|tailrank.com/robot|R|",
			"Yandex|Yandex|",
			"Yesup|yesup|",
			"Internet|User-Agent:|",
			"Robot|Robot|", "Spider|spider|");
		foreach($lines as $line_num => $spider) {
			list($nome,$key,$crawlertype)=explode("|",$spider);
			if ($key != "") {
				if(strstr($agent,$key)===FALSE) { 
					continue; 
				} else { 
					$crawler = trim($nome);
					if (!empty($crawlertype) && $crawlertype == "F") {
						$feed = $crawler;
					}
					break 1;
				}
			}
		}
	} // end if crawler

	//If crawler not on list, use first word in useragent for crawler name
	if (empty($crawler)) { 
		//Assume first word in useragent is crawler name
		if (preg_match("/^(\w+)[\/ \-\:_\.;]/",$ua,$matches) > 0) {
			if (strlen($matches[1])>1 && $matches[1]!="Mozilla") { 
				$crawler = $matches[1];
			}
		}
		/* //Use browser name for crawler as last resort
		if (empty($crawler) && !empty($browser)) { 
			$crawler = $browser;
		} */
	}
	//#do a feed check and get feed subcribers, if available
	if (preg_match("/([0-9]{1,10})\s?subscriber/i",$ua,$subscriber) > 0) {
		// It's a feedreader with some subscribers
		$feed = $subscriber[1];
		if (empty($crawler) && empty($browser)) {
			$crawler = "Feed Reader";
			$crawlertype="F";
		}
	} elseif (empty($feed) && (is_feed() || preg_match("/(feed|rss)/i",$ua)>0)) {
		if (!empty($crawler)) { 
			$feed = $crawler;
		} elseif (empty($browser)) {
			$crawler = "Feed Reader";
			$feed = "feed reader";
		}
		$crawlertype="F";
	} //end else preg_match subscriber

	//check for spoofers of Google/Yahoo crawlers...
	if ($hostname!="") {
		if (preg_match('/^(googlebot|yahoo\!\ slurp)/i',$crawler)>0 && preg_match('/\.(googlebot|yahoo)\./i',$hostname)==0){
			$crawler = "Spoofer bot";
			$crawlertype = "H";
		}
	} //end if hostname
	$spiderdata=array($crawler,$crawlertype,trim($feed));
	} //end if (empty($spiderdata)) {

	return $spiderdata;
} //end function wGetSpider

//#get the visitor locale/language
function wGetLocale($language="",$hostname="",$referrer="") {
	//#use country code for language, if it exists in hostname
	if (!empty($hostname) && preg_match("/\.[a-zA-Z]{2}$/", $hostname) > 0) {
		$country = strtolower(substr($hostname,-2));
		if ($country == "uk") { $country = "gb"; } //change UK to GB for consistent language codes
		$language = $country;
	} elseif (strlen($language) >2) {
	   	$langarray = @explode("-", $language);
	   	$langarray = @explode(",", $langarray[1]);
	   	list($language) = @explode(";", strtolower($langarray[0]));
	}
	//#check referrer search string for language/locale code, if any
	if ((empty($language) || $language=="us" || $language=="en") && !empty($referrer)) {
		$country = $language;
		// google referrer syntax: google.com[.country],hl=language
		if (preg_match('/\.google(\.com)?\.(com|([a-z]{2}))?\/.*[&?]hl\=(\w{2})\-?(\w{2})?/',$referrer,$matches)>0) {
			if (!empty($matches[5])) {
				$country = strtolower($matches[5]);
			} elseif (!empty($matches[3])) {
				$country = strtolower($matches[3]);
			} elseif (!empty($matches[4])) {
				$country = strtolower($matches[4]);
			}
		}
		unset ($matches);
		$language = $country;
	}
	if (!empty($language) && preg_match("/^[a-z]{2}$/",$language)>0) {
		//ignore locales that are also used for media domains
		if ($language == "tv" || $language == "fm") {
			$language = "";
		} else {
		//Make language code consistent with country code
			if ($language == "en") {//default to "US" if "en"
				$language = "us";
			} elseif ($language == "uk") {	//change UK to GB
				$language = "gb";
			} elseif ($language == "ja") {	//change JA to JP
				$language = "jp";
			} elseif ($language == "ko") {	//change KO to KR
				$language = "kr";
			} elseif ($language == "da") {	//change DA to DK
				$language = "dk";
			} elseif ($language == "ur") {	//Urdu 
				$language = "in";	//could be India or Pakistan
			} elseif ($language == "he" || $language == "iw") {	//Hebrew (iso) 
				$language = "il";	//Israel
			} 
		}
	} else {
		$language = "";
	}
	return $language;
} //end function wGetLocale

//# Check input, $referrer against a list of known spammers and 
//#   return "1" if match found. 
//#   All comparisons are case-insensistive and uses the faster string 
//#   functions (stristr) instead of "regular expression" functions.
function wGetSpamRef($referrer) {
	$referrer=attribute_escape(strip_tags(str_replace(" ","",html_entity_decode($referrer))));
	if (empty($referrer)) { return null; }	//nothing to check...
	$badhost=false;
	$badhostfile= dirname(__FILE__).'/badhosts.txt';
        $key = null;

	//#Assume any referrer name similar to "viagra/zanax/.."
	//#  is spam and mark as such...
	$lines = array("cialis","viagra","zanax","phentermine");
	foreach ($lines as $badreferrer) {
		if (stristr($referrer, $badreferrer) !== FALSE) { 
			$badhost=true;
			break 1;
		}
	}
	if (!$badhost) {
		$lines = array("1clickholdem.com", "1ps.biz", "24h.to", "4all-credit.com", "4all-prescription.com", "4u-money.com", "4u.net", "4u.com", "6q.org", "88.to", 
	"allinternal.com", "always-casino.com", "always-credit.com", "andipink.com", "andrewsaluk.com", "antiquemarketplace.net", "artmedia.com.ru", "asstraffic.com", "at.cx", "available-casino.com", "available-credit.com", "available-prescription.com", 
	"base-poker.com", "bayfronthomes.net", "bitlocker.net", "black-poker.com", "blest-money.com", "brutalblowjobs.com", "budgethawaii.net", "buy-2005.com", "bwdow.com",
	"ca-america.com", "cafexml.com", "cameralover.net", "capillarychromatogr.org", "cash-2u.com", "casino-500.com", "casino-bu.com", "casinos4spain.com", "chat-nett.com", "cheat-elite.com", "cialis-gl-pills.com", 
	"clan.ws", "collegefuckfest.com", "cool-extreme.com", "computerxchange.com", "conjuratia.com", "coresat.com", "crescentarian.net", "credit-4me.com", "credit-dreams.com", "cups.cs.cmu.edu", "cxa.de", 
	"dating-s.net", "de.tc", "dietfacts.com", "doctor-here.com", "doctor-test.com", "doobu.com", "drugstore.info",
        "eu.cx", "exitq.com", 
	"fickenfetzt.com", "fidelityfunding.net", "finance-4all.com", "finestrealty.net", "findwebhostingnow.com", "flowersdeliveredquick.com", "fortexasholdem.com", "freakycheats.com", "freewarechannel.de", 
	"gb.com", "golfshoot.com", "great-finance.com", "great-money.com", "guide.info", 
	"health-livening.com", "here.ws", "hu.tc", 
	"iepills.com", "ihomebroker.com", "including-poker.com", "internettexashold.com", "isacommie.com", "isdrin.de", "iwebtool.com", 
	"jaja-jak-globusy.com", "jmhic.com", "jobruler.com", "jpe.com", "js4.de", "just-pharmacy.com", 
	"kredite-kredit", 
	"learnhowtoplay.com", "livemarket.com.ua", 
        "mature-lessons.com", "mine-betting.com", "musicbox1.com", 
	"new-doctor.com", "nonews.ru", "now-cash.com", 
	"online-pills.us", "online.cx", "only-casino.com", "ourtexasholdem.com", "owned.com",
	"p.cx", "partyshopcentral.com", "pervertedtaboo.com", "petsellers.net", "pharmacy.info", "pharmacy-here.com", "pills-only.com", "plenty-cash.com", "poker-check.com", "poker-online.com", "poker-spanish.com", "pressemitteilung.ws", "progressiveupdate.net", "psxtreme.com", 
	"quality-poker.com", 
	"reale-amateure.com", "realtorx2.com", "ro7kalbe.com", "roody.com", "royalfreehost.com",  "rulen.de", 
	"shop.tc", "sml338.org", "smsportali.net", "sp.st", "spanish-casino-4u.com", "spellsbook.com", "standard-poker.com", "start.bg", "sysrem03.com", 
	"take-mortgage.com", "terashells.com", "texasholdfun.com", "texas-holdem", "texas-va-loan.com", "the-discount-store.com", "trackerom.com", 
	"ua-princeton.com", "udcorp.com", "unique-pills.com", "unixlover.com", "us.tc", "useful-pills.com", 
	"vadoptions.com", "vcats.com", "vegas-hair.com", "vinsider.com", "vjackpot.com", "vmousetrap.com", "vplaymate.com", "vpshs.com", "vp888.net", "vrajitor.com", "vselling.com", "vsymphony.com", "vthought.com", 
	"walnuttownfireco.org", "white-pills.com", "whvc.net", "wildpass.com", "wkelleylucas.com", "wrongsideoftown.com", 
	"yelucie.com", "yourpsychic.net", "yx-colorweaving.com",
	"zoomgirls.net");
	foreach($lines as $line_num => $spammer) {
                if(stristr($referrer,$spammer) !== FALSE) {
                        // found it!
			$badhost=true;
			break 1;
                }
	}
	}
	//#check for a customized spammer list...
	if (!$badhost && file_exists($badhostfile)) {
		$lines = file($badhostfile,FILE_IGNORE_NEW_LINES);
		foreach($lines as $line_num => $spammer) {
			if(stristr($referrer,trim($spammer)) !== FALSE) {
                        	// found it!
				$badhost=true;
				break 1;
			}
		}
	}
	return $badhost;
} //end function wGetSpamRef()

//return 1st valid IP address in a comma separated list of IP addresses
//  -Helene D. 2009-03-01
function validIP($multiIP) {
	//in case of multiple forwarding
	$ips = explode(",",$multiIP);
	$goodIP = "";
	//look through forwarded list for a good IP
	foreach ($ips as $IP) {
		//exclude dummy IPv4 addresses...
		$ipaddr = trim($IP);
		if (!empty($ipaddr) && $ipaddr != "unknown" && $ipaddr != "0.0.0.0" && $ipaddr != "127.0.0.1" && substr($ipaddr,0,8) != "192.168." && substr($ipaddr,0,3) != "10." && substr($ipaddr,0,4) != "172.") {
			$goodIP = $ipaddr;
			break 1;
		}
	}
	if (!empty($goodIP)) { return $goodIP; }
	else { return false; }
} //end function validIP

function export_wassup() {
	global $wpdb, $wassup_options;

	if (empty($wassup_options->wassup_table)) {
		$table_name = $wpdb->prefix . "wassup";
	} else {
		$table_name = $wassup_options->wassup_table;
	}
	$filename = 'wassup.' . gmdate('Y-m-d') . '.sql';

	//# check for records before exporting...
	$numrecords = $wpdb->get_var("SELECT COUNT(wassup_id) FROM $table_name");
	if ( $numrecords > 0 ) {
		if ($numrecords > 10000) {
		//...could take a long time, so run in background in case browser times out
			ignore_user_abort(1);
		}
		$exportdata=backup_table("$table_name");

	if ($exportdata) {
	//TODO: use compressed file transfer when zlib available...
	do_action('export_wassup');
	header('Content-Description: File Transfer');
	header("Content-Disposition: attachment; filename=$filename");
	header('Content-Type: text/plain charset=' . get_option('blog_charset'), true);

	// Function is below
	//backup_table($table_name);
	echo $exportdata;

	die(); 	//sends output and flushes buffer
	}
} //end if numrecords > 0
} //end function export_wassup()

/**
* Taken partially from wp-db-backup plugin
* Alain Wolf, Zurich - Switzerland
* Website: http://www.ilfilosofo.com/blog/wp-db-backup/
* @param string $table
* @param string $segment
* @return void
*/
function backup_table($table, $segment = 'none') {
	global $wpdb, $wassup_options;
	define('ROWS_PER_SEGMENT', 100);

	$table_structure = $wpdb->get_results("DESCRIBE $table");
	if (! $table_structure) {
		$wassup_options->wassup_alert_message = __('Error getting table details','wassup') . ": $table";
		$wassup_options->saveSettings();
		return FALSE;
	}

	if(($segment == 'none') || ($segment == 0)) {
		// Add SQL statement to drop existing table
		$sql .= "\n\n";
		$sql .= "#\n";
		$sql .= "# " . sprintf(__('Delete any existing table %s','wassup'),$table) . "\n";
		$sql .= "#\n";
		$sql .= "\n";
		$sql .= "#\n";
		$sql .= "# Uncomment if you need\n";
		$sql .= "#DROP TABLE IF EXISTS " . $table . ";\n";
		
		// Table structure
		// Comment in SQL-file
		$sql .= "\n\n";
		$sql .= "#\n";
		$sql .= "# " . sprintf(__('Table structure of table %s','wassup'),$table) . "\n";
		$sql .= "#\n";
		$sql .= "\n";
		$sql .= "#\n";
		$sql .= "# Uncomment if you need\n";
		
		$create_table = $wpdb->get_results("SHOW CREATE TABLE $table", ARRAY_N);
		if (FALSE === $create_table) {
			$err_msg = sprintf(__('Error with SHOW CREATE TABLE for %s.','wassup'), $table);
			$wassup_options->wassup_alert_message = $err_msg;
			$wassup_options->saveSettings();
			$sql .= "#\n# $err_msg\n#\n";
		}
		$sql .= $create_table[0][1] . ' ;';
		
		if (FALSE === $table_structure) {
			$err_msg = sprintf(__('Error getting table structure of %s','wassup'), $table);
			$wassup_options->wassup_alert_message = $err_msg;
			$wassup_options->saveSettings();
			$sql .= "#\n# $err_msg\n#\n";
		}
	
		// Comment in SQL-file
		$sql .= "\n\n";
		$sql .= "#\n";
		$sql .= '# ' . sprintf(__('Data contents of table %s','wassup'),$table) . "\n";
		$sql .= "#\n";
	}
	
	if(($segment == 'none') || ($segment >= 0)) {
		$defs = array();
		$ints = array();
		foreach ($table_structure as $struct) {
			if ( (0 === strpos($struct->Type, 'tinyint')) ||
				(0 === strpos(strtolower($struct->Type), 'smallint')) ||
				(0 === strpos(strtolower($struct->Type), 'mediumint')) ||
				(0 === strpos(strtolower($struct->Type), 'int')) ||
				(0 === strpos(strtolower($struct->Type), 'bigint')) ||
				(0 === strpos(strtolower($struct->Type), 'timestamp')) ) {
					$defs[strtolower($struct->Field)] = $struct->Default;
					$ints[strtolower($struct->Field)] = "1";
			}
		}
		
		// Batch by $row_inc
		
		if($segment == 'none') {
			$row_start = 0;
			$row_inc = ROWS_PER_SEGMENT;
		} else {
			$row_start = $segment * ROWS_PER_SEGMENT;
			$row_inc = ROWS_PER_SEGMENT;
		}
		do {	
			//Extend php and mysql wait timeout to 15 minutes
			$timeout=15*60;
			if ( !ini_get('safe_mode')) @set_time_limit($timeout);
			$wpdb->query("SET wait_timeout = $timeout");
			$table_data = $wpdb->get_results("SELECT * FROM $table LIMIT {$row_start}, {$row_inc}", ARRAY_A);

			$entries = 'INSERT INTO ' . $table . ' VALUES (';	
			//    \x08\\x09, not required
			$search = array("\x00", "\x0a", "\x0d", "\x1a");
			$replace = array('\0', '\n', '\r', '\Z');
			if($table_data) {
				foreach ($table_data as $row) {
					$values = array();
					foreach ($row as $key => $value) {
						if ($ints[strtolower($key)]) {
							// make sure there are no blank spots in the insert syntax,
							// yet try to avoid quotation marks around integers
							$value = ( '' === $value) ? $defs[strtolower($key)] : $value;
							$values[] = ( '' === $value ) ? "''" : $value;
						} else {
							$values[] = "'" . str_replace($search, $replace, addslashes($value)) . "'";
						}
					}
					$sql .= " \n" . $entries . implode(', ', $values) . ') ;';
				}
				$row_start += $row_inc;
			}
		} while((count($table_data) > 0) and ($segment=='none'));
		//reset mysql wait timeout to 1 minute
		$wpdb->query("SET wait_timeout = 60");
	}
	
	if(($segment == 'none') || ($segment < 0)) {
		// Create footer/closing comment in SQL-file
		$sql .= "\n";
		$sql .= "#\n";
		$sql .= "# " . sprintf(__('End of data contents of table %s','wp-db-backup'),$table) . "\n";
		$sql .= "# --------------------------------------------------------\n";
		$sql .= "\n";
	}
	return $sql;
} // end backup_table()


//Put a Wassup timestamp in page footer to check if page is cached
function wassup_foot() {
	global $wassup_options, $wassupversion, $debug_mode;
	if ($wassup_options->wassup_active == "1") {
		//Output a comment with a current timestamp to verify that page is not cached (i.e. visit is being recorded).
		echo "<!--\n<p class=\"small\"> WassUp $wassupversion ".__("timestamp","wassup").": ".date('Y-m-d h:i:sA T')." (".gmdate('h:iA',time()+(get_option('gmt_offset')*3600)).")<br />\n";
		echo __("If above timestamp is not current time, this page is cached","wassup").".</p> -->\n";
	}
}

if (!function_exists('microtime_float')) {
function microtime_float() {	//replicates microtime(true) from PHP5
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
}

// START initializing Widget
function wassup_widget_init() {

        if ( !function_exists('register_sidebar_widget') )
                return;

function wassup_widget($wargs) {
	global $wpdb, $wpurl, $blogurl;
	extract($wargs);
	$wassup_settings = get_option('wassup_settings');
	//$wpurl =  get_bloginfo('wpurl');	//global
	//$siteurl =  get_bloginfo('siteurl');	//now blogurl
	$table_name = $wassup_settings['wassup_table'];
	$table_tmp_name = $table_name . "_tmp";
	if ($wassup_settings['wassup_widget_title'] != "") $title = $wassup_settings['wassup_widget_title']; else $title = "Visitors Online";
	if ($wassup_settings['wassup_widget_ulclass'] != "") $ulclass = $wassup_settings['wassup_widget_ulclass']; else $ulclass = "links";
	if ($wassup_settings['wassup_widget_chars'] != "") $chars = $wassup_settings['wassup_widget_chars']; else $chars = "18";
	if ($wassup_settings['wassup_widget_searchlimit'] != "") $searchlimit = $wassup_settings['wassup_widget_searchlimit']; else $searchlimit = "5";
	if ($wassup_settings['wassup_widget_reflimit'] != "") $reflimit = $wassup_settings['wassup_widget_reflimit']; else $reflimit = "5";
	if ($wassup_settings['wassup_widget_topbrlimit'] != "") $topbrlimit = $wassup_settings['wassup_widget_topbrlimit']; else $topbrlimit = "5";
	if ($wassup_settings['wassup_widget_toposlimit'] != "") $toposlimit = $wassup_settings['wassup_widget_toposlimit']; else $toposlimit = "5";
	
	print $before_widget;

	//show stats only when WassUp is active
	if (empty($wassup_settings['wassup_active'])) {
		print $before_title . $title . $after_title;
		print "<ul class='$ulclass'><li>".__("No Data","wassup")."</li>\n";
		print "<span style='font-size:6pt; text-align:center;'>".__("powered by", "wassup")." <a href='http://www.wpwp.org/' title='WassUp - ".__("Real Time Visitors Tracking","wassup")."'>WassUp</a></span></ul>";

	} else {	//Wassup is recording (active)
		$to_date = wassup_get_time();
		$from_date = strtotime('-3 minutes', $to_date);

	// Widget Latest Searches
	if ($wassup_settings['wassup_widget_search'] == 1) {
	$query_det = $wpdb->get_results("SELECT search, referrer FROM $table_tmp_name WHERE search!='' GROUP BY search ORDER BY `timestamp` DESC LIMIT ".attribute_escape($searchlimit)."");
	if (count($query_det) > 0) {
		print "$before_title ".__('Last searched terms','wassup')." $after_title";
		print "<ul class='$ulclass'>";
		foreach ($query_det as $sref) {
			print "<li>- <a href='".wCleanURL($sref->referrer)."' target='_blank' rel='nofollow'>".stringShortener($sref->search, $chars)."</a></li>";
		}
		print "</ul>";
	}
	}

	// Widget Latest Referers
	if ($wassup_settings['wassup_widget_ref'] == 1) {
	$query_ref = $wpdb->get_results("SELECT referrer FROM $table_tmp_name WHERE searchengine='' AND referrer!='' AND referrer NOT LIKE '$wpurl%' GROUP BY referrer ORDER BY `timestamp` DESC LIMIT ".attribute_escape($reflimit)."");
	if (count($query_ref) > 0) {
		print "$before_title ".__('Last referers','wassup')." $after_title";
		print "<ul class='$ulclass'>";
		foreach ($query_ref as $eref) {
			print "<li>- <a href='".wCleanURL($eref->referrer)."' target='_blank' rel='nofollow'>".stringShortener(preg_replace('#https?://#i','',$eref->referrer), $chars)."</a></li>";
		}
		print "</ul>";
	}
	}

	// Widget TOP Browsers
	if ($wassup_settings['wassup_widget_topbr'] == 1) {
		$time_range = '`timestamp` > 0';	//all time
		$top_limit = attribute_escape($topbrlimit);
		$top_results =  wGetStats("browser",$top_limit,$time_range);
		if (count($top_results) > 0) {
			print "$before_title ".__('Top Browsers','wassup')." $after_title";
			print "<ul class='$ulclass'>";
			foreach ($top_results as $wtop) {
				print "<li>- ".stringShortener($wtop->top_item, $chars)."</li>";
			}
			print "</ul>";
		}
	}

	// Widget TOP OSes
	if ($wassup_settings['wassup_widget_topos'] == 1) {
		$time_range = '`timestamp` > 0';	//all time
		$top_limit = attribute_escape($toposlimit);
		$top_results =  wGetStats("os",$top_limit,$time_range);
		if (count($top_results) > 0) {
			print "$before_title ".__('Top OS','wassup')." $after_title";
			print "<ul class='$ulclass'>";
			foreach ($top_results as $wtop) {
				print "<li>- ".stringShortener($wtop->top_item, $chars)."</li>";
			}
			print "</ul>";
		}
	}

	// Widget Visitors Online
	$TotWid = New MainItems($table_tmp_name,$from_date,$to_date);

	$currenttot = $TotWid->calc_tot("count", null, null, "DISTINCT");
	$currentlogged = $TotWid->calc_tot("count", null, "AND  username!=''", "DISTINCT");
	$currentauth = $TotWid->calc_tot("count", null, "AND  comment_author!='' AND username=''", "DISTINCT");

        print $before_title . $title . $after_title;
        print "<ul class='$ulclass'>";
        if ((int)$currenttot < 10) $currenttot = "0".$currenttot;
        print "<li><strong style='padding:0 4px 0 4px;background:#ddd;color:#777'>".$currenttot."</strong> ".__('visitor(s) online','wassup')."</li>";
        if ((int)$currentlogged > 0 AND $wassup_settings['wassup_widget_loggedin'] == 1) {
        if ((int)$currentlogged < 10) $currentlogged = "0".$currentlogged;
                print "<li><strong style='padding:0 4px 0 4px;background:#e7f1c8;color:#777'>".$currentlogged."</strong> ".__('logged-in user(s)','wassup')."</li>";
        }
        if ((int)$currentauth > 0 AND $wassup_settings['wassup_widget_comauth'] == 1) {
        if ((int)$currentauth < 10) $currentauth = "0".$currentauth;
                print "<li><strong style='padding:0 4px 0 4px;background:#fbf9d3;color:#777'>".$currentauth."</strong> ".__('comment author(s)','wassup')."</li>";
	}
	print "<li style='font-size:6pt; color:#bbb;'>".__("powered by", "wassup")." <a style='color:#777;' href='http://www.wpwp.org' title='WassUp - ".__("Real Time Visitors Tracking","wassup")."'>WassUp</a></li>";
	print "</ul>";

	} //end if empty(wassup_active)

	print $after_widget;
} //end function wassup_widget

	//User-selectable widget options
	function wassup_widget_control() {
		global $wpdb;
		$wassup_settings = get_option('wassup_settings');

		//save widget form input
		if (isset($_POST['wassup-submit'])) {
			$wassup_settings['wassup_widget_title'] = attribute_escape($_POST['widget_title']);
			$wassup_settings['wassup_widget_ulclass'] = attribute_escape($_POST['widget_ulclass']);
			if (is_numeric($_POST['widget_chars'])) {
				$wassup_settings['wassup_widget_chars'] = $_POST['widget_chars'];
			}
			$wassup_settings['wassup_widget_loggedin'] = $_POST['widget_loggedin'];
			$wassup_settings['wassup_widget_comauth'] = $_POST['widget_comauth'];
			$wassup_settings['wassup_widget_search'] = $_POST['widget_search'];
			if ((int)$_POST['widget_searchlimit']>0) {
				$wassup_settings['wassup_widget_searchlimit'] = (int)$_POST['widget_searchlimit'];
			} elseif (empty($wassup_settings['wassup_widget_searchlimit'])) {
				$wassup_settings['wassup_widget_searchlimit'] = 5;
			}
			$wassup_settings['wassup_widget_ref'] = $_POST['widget_ref'];
			if ((int)$_POST['widget_reflimit']>0) {
				$wassup_settings['wassup_widget_reflimit'] = (int)$_POST['widget_reflimit'];
			} elseif (empty($wassup_settings['wassup_widget_reflimit'])) {
				$wassup_settings['wassup_widget_reflimit'] = 5;
			}
			$wassup_settings['wassup_widget_topbr'] = $_POST['widget_topbr'];
			if ((int)$_POST['widget_topbrlimit']>0) {
				$wassup_settings['wassup_widget_topbrlimit'] = (int)$_POST['widget_topbrlimit'];
			}
			$wassup_settings['wassup_widget_topos'] = $_POST['widget_topos'];
			if ((int)$_POST['widget_toposlimit']>0) {
				$wassup_settings['wassup_widget_toposlimit'] = (int)$_POST['widget_toposlimit'];
			}

			if (empty($wassup_settings['wassup_userlevel'])) {
				$wassup_settings['wassup_userlevel'] = 8;
			}
			if (empty($wassup_settings['wassup_refresh'])) {
				$wassup_settings['wassup_refresh'] = 3;
			}
			//save the new widget selections
			update_option('wassup_settings', $wassup_settings); 
		} //end if _POST[submit]

		//widget selection form 
		$title = (isset($wassup_settings['wassup_widget_title']))? attribute_escape($wassup_settings['wassup_widget_title']): "Visitors Online";
		$ulclass = (isset($wassup_settings['wassup_widget_ulclass']))? attribute_escape($wassup_settings['wassup_widget_ulclass']): "links";
		$chars = (!empty($wassup_settings['wassup_widget_chars'])) ? (int) $wassup_settings['wassup_widget_chars']: 18;
		$searchlimit = (!empty($wassup_settings['wassup_widget_searchlimit'])) ? (int)$wassup_settings['wassup_widget_searchlimit']: 5;
		$reflimit = (!empty($wassup_settings['wassup_widget_reflimit'])) ? (int)$wassup_settings['wassup_widget_reflimit']: 5;
		$topbrlimit = (!empty($wassup_settings['wassup_widget_topbrlimit'])) ? (int)$wassup_settings['wassup_widget_topbrlimit']: 5;
		$toposlimit = (!empty($wassup_settings['wassup_widget_toposlimit'])) ? (int)$wassup_settings['wassup_widget_toposlimit']: 5;
		?>
		<div>
		<p style="align:left;">
		<label for="widget_title"><nobr><?php _e("Title","wassup"); ?>: 
			<input style="width:200px;background-color:#ddd;" type="text" name="widget_title" id="widget_title" value="<?php echo $title; ?>" /></nobr>
			<nobr> &nbsp; <small>(<?php _e("default \"Visitors Online\"", "wassup") ?>)</small></nobr>
		</label></p>
		<p style="align:left;">
		<label for="widget_ulclass"><nobr><?php _e("Stylesheet class for &lt;ul&gt; attribute","wassup"); ?>:
			<input style="width:100px;background-color:#ddd;" type="text" name="widget_ulclass" id="widget_ulclass" value="<?php echo $ulclass; ?>" /></nobr>
		<nobr> &nbsp; <small>(<?php _e("default \"links\"","wassup"); ?>)</small></nobr>
		</label></p>
		<p style="align:left;">
		<label for="widget_chars"><nobr><?php _e("Number of characters to display from left","wassup"); ?>? 
			<input style="width:50px;background-color:#ddd;" type="text" name="widget_chars" id="widget_chars" value="<?php echo $chars; ?>" /></nobr>
			<br/><nobr> &nbsp; <small>(<?php _e("For template compatibility - default 18", "wassup"); ?>)</small></nobr>
		</label></p>
		<p style="align:left;">
		<label for="widget_loggedin">
			<nobr><input type="checkbox" name="widget_loggedin" id="widget_loggedin" value="1" <?php if (!empty($wassup_settings['wassup_widget_loggedin'])) { echo "CHECKED"; } ?> />
			<?php _e("Show number of logged-in users online","wassup"); ?></nobr> 
			<br/><span style="padding-left:25px;"><nobr><small>(<?php _e("Stats recording must be enabled in WassUp options", "wassup"); ?>)</small></nobr></span>
			<!-- (<?php _e("default Yes", "wassup"); ?>) -->
		</label></p>
		<p style="align:left;">
		<label for="widget_comauth">
			<nobr><input type="checkbox" name="widget_comauth" id="widget_comauth" value="1" <?php if (!empty($wassup_settings['wassup_widget_comauth'])) { echo "CHECKED"; } ?> />
			<?php _e("Show number of comment authors online", "wassup"); ?></nobr>
			<!-- (<?php _e("default Yes", "wassup"); ?>) -->
		</label></p>
		<p style="align:left;">
		<label for="widget_search">
			<nobr><input type="checkbox" name="widget_search" id="widget_search" value="1" <?php if (!empty($wassup_settings['wassup_widget_search'])) { echo "CHECKED"; } ?> />
			<?php _e("Show latest searches","wassup"); ?></nobr>
			<!-- (<?php _e("default Yes", "wassup"); ?>) -->
		</label>
		<label for="widget_searchlimit"><span style="padding-left:25px;line-height:1.1em;display:block;">
			<nobr><?php _e("How many searches?","wassup"); ?>
			<input style="width:40px;background-color:#ddd;" name="widget_searchlimit" id="widget_searchlimit" value="<?php echo $searchlimit; ?>" /></nobr> 
			<small>(<?php _e("default 5", "wassup"); ?>)</small></span>
		</label>
		</p>
		<p style="align:left;">
		<label for="widget_ref">
			<nobr><input type="checkbox" name="widget_ref" id="widget_ref" value="1" <?php if (!empty($wassup_settings['wassup_widget_ref'])) { echo "CHECKED"; } ?> />
			<?php _e("Show latest external referrers", "wassup"); ?></nobr>
			<!-- (<?php _e("default Yes", "wassup"); ?>) -->
		</label>
		<label for="widget_reflimit"><span style="padding-left:25px;line-height:1.1em;display:block;">
			<nobr><?php _e("How many referrers?","wassup"); ?>
			<input style="width:40px;background-color:#ddd;" name="widget_reflimit" id="widget_reflimit" value="<?php echo $reflimit; ?>" /></nobr>
			<small>(<?php _e("default 5", "wassup"); ?>)</small></span>
		</label>
		</p>
		<p style="align:left;">
		<label for="widget_topbr">
			<nobr><input type="checkbox" name="widget_topbr" id="widget_topbr" value="1" <?php if (!empty($wassup_settings['wassup_widget_topbr'])) { echo "CHECKED"; } ?> />
			<?php _e("Show top browsers","wassup"); ?> <small>(<?php _e("default No","wassup"); ?>)</small></nobr>
			 <span style="padding-left:25px;"><small><nobr>(<?php _e("Enabling it could slow down blog)", "wassup"); ?></nobr></small>
		</label>
		<label for="widget_topbrlimit"><span style="padding-left:25px;line-height:1.1em;display:block;">
			<nobr><?php _e("How many browsers?","wassup"); ?>
			<input style="width:40px;background-color:#ddd;" name="widget_topbrlimit" id="widget_topbrlimit" value="<?php echo $topbrlimit; ?>" /></nobr>
			<small>(<?php _e("default 5", "wassup"); ?>)</small></span>
		</label>
		</p>
		<p style="align:left;">
		<label for="widget_topos">
			<nobr><input type="checkbox" name="widget_topos" id="widget_topos" value="1" <?php if (!empty($wassup_settings['wassup_widget_topos'])) { echo "CHECKED"; } ?> />
			<?php _e("Show top operating systems","wassup"); ?> <small>(<?php _e("default No","wassup"); ?>)</small></nobr>
			 <span style="padding-left:25px;"><small><nobr>(<?php _e("Enabling it could slow down blog)", "wassup"); ?></nobr></small>
		</label>
		<label for="widget_toposlimit"><span style="padding-left:25px;line-height:1.1em;display:block;">
			<nobr><?php _e("How many operating systems?","wassup"); ?>
			<input style="width:40px;background-color:#ddd;" name="widget_toposlimit" id="widget_toposlimit" value="<?php echo $toposlimit; ?>" /></nobr>
			<small>(<?php _e("default 5", "wassup"); ?>)</small></span>
		</label>
		</p>
		<p style="text-align:left;"><input type="hidden" name="wassup-submit" id="wassup-submit" value="1" /></p>
		</div>
	<?php
	} //end function wassup_widget_control

	if(function_exists('register_sidebar_widget')) {
		register_sidebar_widget(__('Wassup Widget'), 'wassup_widget'); 
		register_widget_control(array('Wassup Widget', 'widgets'), 'wassup_widget_control', 500, 440);
	}
} //end function wassup_widgit_init

function wassup_sidebar($before_widget='', $after_widget='', $before_title='', $after_title='', $wtitle='', $wulclass='', $wchars='', $wsearch='', $wsearchlimit='', $wref='', $wreflimit='', $wtopbr='', $wtopbrlimit='', $wtopos='', $wtoposlimit='') {
	global $wpdb, $wpurl, $blogurl;
	//$wpurl =  get_bloginfo('wpurl');	//global
	//$siteurl =  get_bloginfo('siteurl');	//now blogurl
	$wassup_settings = get_option('wassup_settings');
	$table_name = $wassup_settings['wassup_table'];
	$table_tmp_name = $table_name . "_tmp";
	if ($wtitle != "") $title = $wtitle; else $title = "Visitors Online";
	if ($wulclass != "") $ulclass = $wulclass; else $ulclass = "links";
	if ($wchars != "") $chars = $wchars; else $chars = "18";
	if ($wsearchlimit != "") $searchlimit = $wsearchlimit; else $searchlimit = "5";
	if ($wreflimit != "") $reflimit = $wreflimit; else $reflimit = "5";
	if ($wtopbrlimit != "") $topbrlimit = $wtopbrlimit; else $topbrlimit = "5";
	if ($wtoposlimit != "") $toposlimit = $wtoposlimit; else $toposlimit = "5";
	//$table_name = $wpdb->prefix . "wassup";
	//$table_tmp_name = $wpdb->prefix . "wassup_tmp";
	//$wassup_settings = get_option('wassup_settings');
	$to_date = wassup_get_time();
	$from_date = strtotime('-3 minutes', $to_date);

	print $before_widget;

	//show stats only when WassUp is active
	if (empty($wassup_settings['wassup_active'])) {
		print $before_title . $title . $after_title;
		print "<ul class='$ulclass'><li>".__("No Data","wassup")."</li>\n";
		print "<span style='font-size:6pt; text-align:center;'>".__("powered by", "wassup")." <a href='http://www.wpwp.org/' title='WassUp - ".__("Real Time Visitors Tracking","wassup")."'>WassUp</a></span></ul>";

	} else {	//Wassup is recording (active)


	if ($wsearch == 1) {
	$query_det = $wpdb->get_results("SELECT search, referrer FROM $table_tmp_name WHERE search!='' GROUP BY search ORDER BY `timestamp` DESC LIMIT $searchlimit");
	if (count($query_det) > 0) {
		print "$before_title Last searched terms $after_title";
		print "<ul class='$ulclass'>";
		foreach ($query_det as $sref) {
			print "<li>- <a href='".attribute_escape($sref->referrer)."' target='_blank' rel='nofollow'>".stringShortener(attribute_escape($sref->search), $chars)."</a></li>";
		}
		print "</ul>";
	}
	}

	if ($wref == 1) {
	$query_ref = $wpdb->get_results("SELECT referrer FROM $table_tmp_name WHERE searchengine='' AND referrer!='' AND referrer NOT LIKE '$wpurl%' GROUP BY referrer ORDER BY `timestamp` DESC LIMIT $reflimit");
	if (count($query_ref) > 0) {
		print "$before_title Last referers $after_title";
		print "<ul class='$ulclass'>";
		foreach ($query_ref as $eref) {
			print "<li>- <a href='".attribute_escape($eref->referrer)."' target='_blank' rel='nofollow'>".stringShortener(preg_replace("#https?://#", "", attribute_escape($eref->referrer)), $chars)."</a></li>";
		}
		print "</ul>";
	}
	}

	if ($wtopbr == 1) {
		$time_range = '`timestamp` > 0';	//all time
		$top_limit = attribute_escape($topbrlimit);
		$top_results =  wGetStats("browser",$top_limit,$time_range);
		if (count($top_results) > 0) {
			print "$before_title ".__('Top Browsers','wassup')." $after_title";
			print "<ul class='$ulclass'>";
			foreach ($top_results as $wtop) {
				print "<li>- ".stringShortener($wtop->top_item, $chars)."</li>";
			}
			print "</ul>";
		}
	}

	if ($wtopos == 1) {
		$time_range = '`timestamp` > 0';	//all time
		$top_limit = attribute_escape($toposlimit);
		$top_results =  wGetStats("os",$top_limit,$time_range);
		if (count($top_results) > 0) {
			print "$before_title ".__('Top OS','wassup')." $after_title";
			print "<ul class='$ulclass'>";
			foreach ($top_results as $wtop) {
				print "<li>- ".stringShortener($wtop->top_item, $chars)."</li>";
			}
			print "</ul>";
		}
	}

	// Visitors Online
	$TotWid = New MainItems($table_tmp_name,$from_date,$to_date);

	$currenttot = $TotWid->calc_tot("count", null, null, "DISTINCT");
	$currentlogged = $TotWid->calc_tot("count", null, "AND  username!=''", "DISTINCT");
	$currentauth = $TotWid->calc_tot("count", null, "AND  comment_author!=''' AND username=''", "DISTINCT");

	print $before_title . $title . $after_title;
	print "<ul class='$ulclass'>";
	if ((int)$currenttot < 10) $currenttot = "0".$currenttot;
	print "<li><strong style='padding:0 4px 0 4px;background:#ddd;color:#777'>".$currenttot."</strong> visitor(s) online</li>";
	if ((int)$currentlogged > 0 AND $wassup_settings['wassup_widget_loggedin'] == 1) {
	if ((int)$currentlogged < 10) $currentlogged = "0".$currentlogged;
		print "<li><strong style='padding:0 4px 0 4px;background:#e7f1c8;color:#777'>".$currentlogged."</strong> logged-in user(s)</li>";
	}
	if ((int)$currentauth > 0 AND $wassup_settings['wassup_widget_comauth'] == 1) {

	if ((int)$currentauth < 10) $currentauth = "0".$currentauth;
		print "<li><strong style='padding:0 4px 0 4px;background:#fbf9d3;color:#777'>".$currentauth."</strong> comment author(s)</li>";
	}
	print "<li style='font-size:6pt; color:#bbb;'>".__("powered by", "wassup")." <a style='color:#777;' href='http://www.wpwp.org/' title='WassUp - Real Time Visitors Tracking'>WassUp</a></li>";
	print "</ul>";

	} //end if !empty(wassup_active)

	print $after_widget;
} //end function wassup_sidebar

// Create functions to output the contents of Dashboard Widget in WP 2.7+
if (version_compare($wp_version, '2.7', '>=')) {
	function wassup_dashboard_widget_function() {
		global $wpdb, $wassup_options, $wpurl;
			$table_name = $wassup_options->wassup_table;
			$table_tmp_name = $table_name."_tmp";
			$to_date = wassup_get_time();
			$chart_type = ($wassup_options->wassup_chart_type >0)? $wassup_options->wassup_chart_type: "2";
			$res = ((int)$wassup_options->wassup_screen_res-160)/2;
			$Chart = New MainItems($table_name,"",$to_date);
		        $chart_url = $Chart->TheChart(1, $res, "180", "", $chart_type, "bg,s,00000000", "dashboard"); 
			$max_char_len= 40;
			?>

			<div class="placeholder" style="margin:0;">
				<p style="text-align:center"><img src="<?php echo $chart_url; ?>" alt="WassUp <?php _e('visitor stats chart','wassup'); ?>"/></p>
				<p><cite><a href="admin.php?page=<?php echo WASSUPFOLDER; ?>"><?php _e('More Stats','wassup'); ?> &raquo;</a></cite></p>
				<style>
					#wassup_dashboard_widget .wassup_dash_box {
						margin: 0px auto 10px auto;
						padding: 10px;
						width:90%;
						font-size:11px;
					}
					#wassup_dashboard_widget .wassup_dash_box p {
						margin: 4px 0 8px 0;
						font-weight: normal;
						font-size:11px;
						border-bottom: 1px solid #dfdfdf;
						padding: 0px 0 8px 0;
					}
					#wassup_dashboard_widget h5 {
						border-top: 3px solid #dfdfdf;
						width:90%;
						margin: 10px auto 0 auto;
						padding: 20px 10px 10px 10px;
						font-size:12px;
					}
					#wassup_dashboard_widget h5 strong {
						font-size:24px;
						margin: 0 10px 0 0;
						padding:2px 10px 2px 10px;
						background:#BBD8E7;
						border:1px solid #dfdfdf;
					}
				</style>
				<?php
				$from_date = strtotime('-3 minutes', $to_date);
				$currenttot = $wpdb->get_var("SELECT COUNT(DISTINCT wassup_id) as currenttot FROM $table_tmp_name WHERE `timestamp` BETWEEN $from_date AND $to_date");
				$currenttot = $currenttot+0;	//set to integer
				if ($currenttot > 0) {
					$qryC = $wpdb->get_results("SELECT id, wassup_id, max(timestamp) as max_timestamp, ip, hostname, searchengine, urlrequested, agent, referrer, spider, username, comment_author FROM $table_tmp_name WHERE `timestamp` BETWEEN $from_date AND $to_date GROUP BY ip ORDER BY max_timestamp DESC");
				print "<h5><strong>".$currenttot."</strong>".__("Visitors online", "wassup")."</h5>";
				print "<div class='wassup_dash_box'>";
				foreach ($qryC as $cv) {
					if ($wassup_options->wassup_time_format == 24) {
						$timed = gmdate("H:i:s", $cv->max_timestamp);
					} else {
						$timed = gmdate("h:i:s a", $cv->max_timestamp);
					}
					$ip_proxy = strpos($cv->ip,",");
					//if proxy, get 2nd ip...
					if ($ip_proxy !== false) {
						$ip = substr($cv->ip,(int)$ip_proxy+1);
					} else { 
						$ip = $cv->ip;
					}
					if ($cv->referrer != '') {
						if ($cv->searchengine != "" || stristr($cv->referrer,$wpurl)!=$cv->referrer) { 
							if ($cv->searchengine == "") {
								$referrer = '<a href="'.wCleanURL($cv->referrer).'" target=_"BLANK"><span style="font-weight: bold;">'.stringShortener("{$cv->referrer}", round($max_char_len*.8,0)).'</span></a>';
							} else {
								$referrer = '<a href="'.wCleanURL($cv->referrer).'" target=_"BLANK">'.stringShortener("{$cv->referrer}", round($max_char_len*.9,0)).'</a>';
							}
						} else { 
							$referrer = __("From your blog", "wassup"); 
						} 
					} else { 
						$referrer = __("Direct hit", "wassup"); 
					} 
		 		// User is logged in or is a comment's author
				if ($cv->username != "" OR $cv->comment_author != "") {
						if ($cv->username != "") {
							$Ousername[] = $cv->username; 
							$Ocomment_author[] = $cv->comment_author; 
						} elseif ($cv->comment_author != "") {
							$Ocomment_author[] = $cv->comment_author; 
						}
				}
					?>

					<?php
					if (strstr($cv->urlrequested,"[404]")) {  //no link for 404 page
						$requrl = stringShortener($cv->urlrequested, round($max_char_len*.9,0)+5);
					} else {
						$requrl = '<a href="'.wAddSiteurl("{$cv->urlrequested}").'" target="_BLANK">';
						$requrl .= stringShortener("{$cv->urlrequested}", round($max_char_len*.9,0)).'</a>';
					} 
					?>
					<p><strong><?php print $timed; ?></strong> - <?php echo $ip; ?> - <?php print $requrl ?><br /><?php echo __("Referrer", "wassup"); ?>: <?php echo $referrer; ?></p>
				<?php		 
				} //end foreach qryC ?>
				</div>
				<?php
				if (count($Ousername) > 0) {
					echo '<div class="wassup_dash_box">';
					echo '<p>'.__('Registered users','wassup').': '.implode(',', $Ousername).'</p>';
					echo '</div>';
				} elseif (count($Ocomment_author) > 0) {
					echo '<div class="wassup_dash_box">';
				 	echo '<p>'.__('Comment authors','wassup').': '.implode(",", $Ocomment_author).'</p>';
					echo '</div>';
				}
		} //end if currenttot
		?>
		</div>
<?php	} //end wassup_dashboard_widget_function

	// Create the function use in the action hook
	function wassup_add_dashboard_widgets() {
		wp_add_dashboard_widget('wassup_dashboard_widget', 'WassUp Summary', 'wassup_dashboard_widget_function');	
	}
}  //end if version_compare >= 2.7

//##Load Wassup functions into document head, contents, and admin menus using Wordpress hooks
function wassup_loader() {
	global $wp_version, $wassup_options;

	//## Wassup Admin hooks and filters
	if (is_admin()) {
		//register_activation_hook(__FILE__, 'wassup_install');
		register_deactivation_hook(__FILE__, 'wassup_uninstall');

		//add hooks for wassup admin header functions
		add_action('admin_head', 'add_wassup_css');
		add_action('admin_menu', 'wassup_add_pages');

		// add dashboard widget hook when WassUp is active
		if (!empty($wassup_options->wassup_dashboard_chart) && !empty($wassup_options->wassup_active)) {
		if (version_compare($wp_version, '2.7', '<')) {
			add_action('activity_box_end', 'wassupDashChart');
		} else {
			// Hook into the 'wp_dashboard_setup' action to register our other functions
			add_action('wp_dashboard_setup', 'wassup_add_dashboard_widgets' );
		}
		}
	} //end if is_admin

	//## non-admin and visitor tracking hooks
	if (!empty($wassup_options->wassup_active)) {
		add_action("widgets_init", "wassup_widget_init");
		//add_action('wp_head', 'wassup_meta_info'); //now in wassupPrepend
		add_action('wp_footer', 'wassup_foot');

	} //end if wassup_active

} //end function wassup_loader

//### Add hooks after functions have been defined
//## General hooks
register_activation_hook(__FILE__, 'wassup_install');
//'init' hook for actions required before http headers are sent 
add_action('init', 'wassup_init');	//<==wassupAppend hook added here 
//hooks for actions after headers are sent (output-related)
add_action('plugins_loaded', 'wassup_loader'); 
?>
