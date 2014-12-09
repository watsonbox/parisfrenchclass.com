<?php
/**
 * @package WassUP
 * @subpackage
 */
/**
/**
 * action.php -- perform an (ajax) action for WassUp admin and reports
 */
//immediately block any attempt to hack WordPress via WassUp
//  -Helene D. 2009-04-04
if (preg_match("/=[ \+%#;0-9]*(select|update|alter|drop|create|union|\-1|\-9+)[ \+%&].+/i",$_SERVER['REQUEST_URI'])>0) {
	header("HTTP/1.1 403 Forbidden");
	die('Illegal request - Permission Denied!');
} elseif (preg_match('/(<|&lt;|&#60;|%3C)script[^a-z0-9]/i',$_SERVER['REQUEST_URI'])>0) {
   	header("HTTP/1.1 403 Forbidden");
	die('Illegal request - Permission Denied!');
} 
if (!isset($_GET['whash'])) {	//hash required
	header("HTTP/1.1 403 Forbidden");
	die('Missing or invalid parameter - Permission Denied!');
} 

//#check for required files and include them
if (!function_exists('get_bloginfo')) {
	//IMPORTANT NOTE: As of WordPress 2.6+ "/wp-content/" can be in a
	//  different location from the Wordpress install directory (i.e. 
	//  not a subdirectory). This configuration requires an additional 
	//  GET parameter "wpabspath=ABSPATH" for "action.php" to run.
	//-Helene D. 2009-04-04
	if (!empty($_GET['wpabspath'])) {
		$wpabspath=attribute_escape(base64_decode(urldecode($_GET['wpabspath'])));
	} elseif (defined('ABSPATH')) {
		$wpabspath=ABSPATH;
	} 
	if (empty($wpabspath) || !is_dir($wpabspath)) {
		$file = preg_replace('/\\\\/', '/', __FILE__);
		$wpabspath=substr($file,0,strpos($file, '/wp-content/')+1);
	}
	
	if (file_exists($wpabspath. 'wp-config.php')) {
        	include_once($wpabspath.'wp-config.php');
	} elseif (file_exists($wpabspath. '../wp-config.php')) { //since WP2.6
        	include_once($wpabspath.'../wp-config.php');
	} else {
		//Note: localization functions, _e() and __(), are not used
		//  here because they would not be defined if this error 
		//  occurred
		echo '<span style="color:red;">Action.php ERROR: file not found, '.$wpabspath.'wp-config.php</span>';
		die();
	}
}

//#only logged-in users are allowed to run this script
$logged_user = wp_get_current_user();
$validuser = (!empty($logged_user->user_login)? true: false);
if (!$validuser) {
	header("HTTP/1.1 403 Forbidden");
	die('Login required. Permission Denied!');
}

//#set required variables
$blogurl =  get_bloginfo('home');
$wpurl =  get_bloginfo('wpurl');
$wassup_settings = get_option('wassup_settings');
$table_name = (!empty($wassup_settings['wassup_table']))? $wassup_settings['wassup_table'] : $wpdb->prefix . "wassup";
$table_tmp_name = $table_name . "_tmp";
if (!defined('WASSUPFOLDER')) {
	define('WASSUPFOLDER', dirname(dirname(__FILE__)));
}
if (!defined('WASSUPURL')) {
	if (defined('WP_CONTENT_URL') && defined('WP_CONTENT_DIR') && strpos(WP_CONTENT_DIR,ABSPATH)===FALSE) {
		$wassupurl = rtrim(WP_CONTENT_URL,"/")."/plugins/".WASSUPFOLDER;
	} else {
		$wassupurl = $wpurl."/wp-content/plugins/".WASSUPFOLDER;
	}
	define('WASSUPURL',$wassupurl);
}

$debug_mode=false;	//debug set below
//echo "Debug: Starting action.php from directory ".dirname(__FILE__).".  ABSPATH=".$wpabspath.".<br />\n"; //debug

//#do a hash check
$hashfail = true;
if (isset($_GET['whash'])) {
	if ($_GET['whash'] == $wassup_settings['whash'] || $_GET['whash'] == attribute_escape($wassup_settings['whash'])) {
		$hashfail = false;
	}
}
//#perform an "action" and display the results, if any
if (!$hashfail) {
	//force browser to disable caching so action.php works as an ajax request
	/* header("Expires: Fri, 22 Jun 2007 05:00:00 GMT"); // Date in the past
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
	// HTTP/1.1
	header('Cache-Control: no-store, no-cache, must-revalidate');
	*/

	// ### Separate "delete" action because it has no output
	// ACTION: DELETE ON THE FLY FROM VISITOR DETAILS VIEW
	if ($_GET['action'] == "deleteID") {
		if (!empty($_GET['id'])) {
			if (method_exists($wpdb,'prepare')) {
				$wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE wassup_id='%s'", $_GET['id']));
			} else {
				$wpdb->query("DELETE FROM $table_name WHERE wassup_id='".attribute_escape($_GET['id'])."'");
			}
		} else {
			echo "Error: Missing wassup_id parameter";
		}
		exit();
	} //end if action==deleteID

	// ### Begin actions that have output...
	//#debug mode is GET parameter, read before include "main.php" but
	//#  after DELETE action
	if (!empty($_GET['debug_mode'])) {
		$debug_mode=true;
		$mode_reset=ini_get('display_errors');
		error_reporting(E_ALL);	//debug, E_STRICT=php5 only
		ini_set('display_errors','On');	//debug
		echo "\n<!-- *WassUp DEBUG On-->\n";
		echo "<!-- *normal setting: display_errors=$mode_reset -->\n";
		if (function_exists('profiler_beginSection')) {
			profiler_beginSection('(Tot)Action.php');
		}
	}
	#load wassup core functions
	if (!function_exists('stringShortener')) {
		if (file_exists(dirname(__FILE__). '/main.php')) {
			include_once(dirname(__FILE__). '/main.php');
		} else {
			echo '<span style="font-color:red;">Action.php '.__("ERROR: file not found","wassup").', '.dirname(__FILE__).'/main.php</span>';
			exit();
		}
	}
	//#retrieve command-line arguments
	if (isset($_GET['to_date'])) $to_date = (int)urlencode(attribute_escape($_GET['to_date']));
	else $to_date = wassup_get_time();
	if (isset($_GET['from_date'])) $from_date = (int)urlencode(attribute_escape($_GET['from_date']));
	else $from_date = ($to_date - 3);

	if (isset($_GET['width'])) {
		if (is_numeric($_GET['width'])) $max_char_len = (int)($_GET['width'])/10;
	}
	if (isset($_GET['rows'])) {
		if (is_numeric($_GET['rows'])) $rows = (int)$_GET['rows'];
	}
	//#check that $to_date is a number
	if (!is_numeric($to_date)) { //bad date sent
		echo '<span style="color:red;">Action.php '.__("ERROR: bad date","wassup").', '.$to_date.'</span>';
		exit();
	}

	//#perform action and display output
?>
<html>
<head>
	<link rel="stylesheet" href="<?php echo WASSUPURL; ?>/wassup.css" type="text/css" />
</head>
<body>
<?php
	// ACTION: RUN SPY VIEW
	if ($_GET['action'] == "spy") {
		if (empty($rows)) { $rows = 999; }
		spyview($from_date,$to_date,$rows,$wassup_settings['wassup_default_spy_type']);

	// ACTION: SUMMARY PIE CHART
	} elseif ($_GET['action'] == "piechart") {
		// Prepare Pie Chart
		$Tot = New MainItems($table_name,$from_date,$to_date);
		$items_pie[] = $Tot->calc_tot("count", $search, "AND spam>0", "DISTINCT");
		$items_pie[] = $Tot->calc_tot("count", $search, "AND searchengine!='' AND spam=0", "DISTINCT");
		$items_pie[] = $Tot->calc_tot("count", $search, "AND searchengine='' AND referrer NOT LIKE '%".$this->WpUrl."%' AND referrer!='' AND spam=0", "DISTINCT");
		$items_pie[] = $Tot->calc_tot("count", $search, "AND searchengine='' AND (referrer LIKE '%".$this->WpUrl."%' OR referrer='') AND spam=0", "DISTINCT"); ?>
		<div style="text-align: center"><img src="http://chart.apis.google.com/chart?cht=p3&amp;chco=0000ff&amp;chs=600x300&amp;chl=Spam|Search%20Engine|Referrer|Direct&amp;chd=<?php chart_data($items_pie, null, null, null, 'pie'); ?>" /></div>


	<?php
	// ACTION: DISPLAY RAW RECORDS - no longer used (deprecated)
	} elseif ($_GET['action'] == "displayraw") {
		$raw_table = $wpdb->get_results("SELECT ip, hostname, agent, referrer, search, searchpage, os, browser, language FROM $table_name WHERE wassup_id='".urlencode(attribute_escape($_GET['wassup_id']))."' ORDER BY timestamp ASC LIMIT 1"); ?>
		<div><h2><?php _e("Raw data","wassup"); ?>:</h2>
		<ul style="list-style-type:none;padding:20px 0 0 30px;">
		<?php foreach ($raw_table as $rt) { ?>
			<li><?php echo __("IP","wassup").": ".$rt->ip; ?></li>
			<li><?php echo __("Hostname","wassup").": ".$rt->hostname; ?></li>
			<li><?php echo __("User Agent","wassup").": ".$rt->agent; ?></li>
			<li><?php echo __("Referrer","wassup").": ".urldecode($rt->referrer); ?></li>
			<?php if ($rt->search != "") { ?>
			<li><?php echo __("Search","wassup").": ".$rt->search; ?></li>
			<?php }
			if ($rt->os != "") { ?> 
			<li><?php echo __("OS","wassup").": ".$rt->os; ?></li>
			<?php }
			if ($rt->browser != "") { ?> 
			<li><?php echo __("Browser","wassup").": ".$rt->browser; ?></li>
			<?php }
			if ($rt->language != "") { ?> 
			<li><?php echo __("Language","wassup").": ".$rt->language; ?></li>
			<?php }
		} //end foreach ?>
		</ul>
		</div>

	<?php
	// ACTION: RUN TOP TEN
	} elseif ($_GET['action'] == "topten") {
		$top_ten = unserialize(html_entity_decode($wassup_settings['wassup_top10']));
		$url = parse_url($blogurl);
		$sitedomain = preg_replace('/^www\./i','',$url['host']);

		//extend php script timeout length for large tables
		if (!ini_get('safe_mode')) {
			$php_timeout = @ini_get("max_execution_time");
			if (is_numeric($php_timeout) && (int)$php_timeout < 120) {
				@set_time_limit(2*60); 	//  ...to 2 minutes
			}
		}

		if (empty($max_char_len)) {
			$max_char_len = ($wassup_settings['wassup_screen_res'])/10;
			//make room for WordPress 2.7 sidebar
			if (version_compare($wp_version, '2.7', '>=')) { 
				$max_char_len = $max_char_len-16;
			}
		}
		//#add an extra width offset when columns count < 5
		$col_count = 0;
		foreach ($top_ten as $topitem) {
			if ($topitem == 1) { $col_count = $col_count+1; }
		}
		if ($col_count > 0 && $col_count < 5 ) {
			$widthoffset = (($max_char_len*(5 - $col_count))/$col_count)*.4; //just a guess
		} else { 
			$widthoffset = 0;
		}
		//extend page width to make room for more than 5 columns
		$pagewidth = $wassup_settings['wassup_screen_res'];
		if ($col_count > 5) {
			$pagewidth = $pagewidth+17*($col_count-5);
		}

		//mysql conditional query...
		$top_condition = '`timestamp` BETWEEN '.$from_date.' AND '.$to_date;
		//mysql conditional: exclude spam if it is being recorded
		if ($wassup_settings['wassup_spamcheck'] == 1) {
			$spamselect = "AND spam=0";
		} else {
			$spamselect = "";
		} 
		$top_limit = 10;
	?>
	<div id="toptenchart" style="width:<?php echo $pagewidth; ?>px;">
		<table width="100%" border=0>
		<tr valign="top">
		<?php
		//#output top 10 searches
		if ($top_ten['topsearch'] == 1) {
			//$top_results =  $wpdb->get_results("SELECT count(search) as top_search, search, referrer FROM $table_name WHERE timestamp BETWEEN $from_date AND $to_date AND search!='' $spamselect GROUP BY search ORDER BY top_search DESC LIMIT 10");
			$top_results = wGetStats("searches",$top_limit,$top_condition);
			$char_len = round(($max_char_len*.30)+$widthoffset,0); ?>
		<td style="min-width:<?php echo ($char_len-5); ?>px;">
		<ul class="charts">
			<li class="chartsT"><?php _e("TOP QUERY", "wassup"); ?></li>
		<?php 
		$i=0;
		if (count($top_results) >0) {
			foreach ($top_results as $top10) { 
				echo "\n"; ?>
			<li class="charts"><?php echo $top10->top_count.': <a href="'.$top10->top_link.'" target="_BLANK" title="'.substr($top10->top_item,0,$wassup_settings['wassup_screen_res']-100).'">'.stringShortener(preg_replace('/'.preg_quote($blogurl,'/').'/i', '', $top10->top_item),$char_len).'</a>'; ?></li>
			<?php
			$i++;
			}
		}
		//finish list with empty <li> for styling consistency 
		if ($i < $top_limit) {
			for ($i; $i<$top_limit; $i++) { 
				echo '<li class="charts">&nbsp; &nbsp;</li>'."\n";
			}
		} ?>
		</ul>
		</td>
		<?php
		} // end if topsearch

		//#output top 10 referrers
		if ($top_ten['topreferrer'] == 1) {
			//to prevent browser timeouts, send <!--heartbeat--> output
			echo "<!--heartbeat-->\n";
			$top_results = wGetStats("referrers",$top_limit,$top_condition);
			$char_len = round(($max_char_len*.22)+$widthoffset,0); ?>
		<td style="min-width:<?php echo ($char_len-5); ?>px;">
		<ul class="charts">
                        <li class="chartsT"><?php _e("TOP REFERRER", "wassup"); ?></li>
<?php 		$i=0;
		if (count($top_results) >0) {
			foreach ($top_results as $top10) { ?>
			<li class="charts"><?php echo $top10->top_count.': ';
			print '<a href="'.clean_url($top10->top_link,'','url').'" title="'.attribute_escape($top10->top_link).'" target="_BLANK">';
			//#cut http:// from displayed url, then truncate
			//#   instead of using stringShortener...
			print substr(str_replace("http://", "", attribute_escape($top10->top_item)),0,$char_len);
			if (strlen($top10->top_item) > ($char_len + 7)) { 
			   	print '...';
			}
			print '</a>'; ?></li>
<?php			$i++;
			}
		}
		//finish list with empty <li> for styling consistency 
		if ($i < $top_limit) {
			for ($i; $i<$top_limit; $i++) { 
				echo '<li class="charts">&nbsp; &nbsp;</li>'."\n";
			}
		} ?>
                </ul>
                </td>
		<?php
		} //end if topreferrer

		//#output top 10 url requests
		if ($top_ten['toprequest'] == 1) {
			echo "<!--heartbeat-->\n";
			//$top_results = $wpdb->get_results("SELECT count(urlrequested) as top_urlrequested, urlrequested FROM $table_name WHERE timestamp BETWEEN $from_date AND $to_date AND urlrequested!='' $spamselect GROUP BY REPLACE(urlrequested, '/', '') ORDER BY top_urlrequested DESC LIMIT 10");
			$top_results = wGetStats("urlrequested",$top_limit,$top_condition);
			$char_len = round(($max_char_len*.28)+$widthoffset,0); ?>
		<td style="min-width:<?php echo ($char_len-5); ?>px;">
		<ul class="charts">
			<li class="chartsT"><?php _e("TOP REQUEST", "wassup"); ?></li>
<?php		$i=0;
		if (count($top_results) >0) {
		foreach ($top_results as $top10) { ?>
			<li class="charts"><?php echo $top10->top_count.': ';
			if (strstr($top10->top_item,'[404]')) { //no link for 404 pages
				echo '<span class="top10" title="'.substr($top10->top_item,0,$wassup_settings['wassup_screen_res']-100).'">'.stringShortener(preg_replace('/'.preg_quote($blogurl,'/').'/i', '', $top10->top_item),$char_len).'</span>';
			} else {
				echo '<a href="'.wAddSiteurl($top10->top_link).'" target="_BLANK" title="'.substr($top10->top_item,0,$wassup_settings['wassup_screen_res']-100).'">'.stringShortener(preg_replace('/'.preg_quote($blogurl,'/').'/i', '', $top10->top_item),$char_len).'</a>';
			} ?></li>
<?php 		$i++;
		}
		}
		//finish list with empty <li> for styling consistency 
		if ($i < $top_limit) {
			for ($i; $i<$top_limit; $i++) { 
				echo '<li class="charts">&nbsp; &nbsp;</li>'."\n";
			}
		} ?>
		</ul>
		</td>
		<?php 
		} //end if toprequest

		//#get top 10 browsers...
		if ($top_ten['topbrowser'] == 1) {
			echo "<!--heartbeat-->\n";
			//$top_results = $wpdb->get_results("SELECT count(browser) as top_browser, browser FROM $table_name WHERE timestamp BETWEEN $from_date AND $to_date AND browser!='' AND browser NOT LIKE 'N/A%' AND spider='' $spamselect GROUP BY browser ORDER BY top_browser DESC LIMIT 10");
			$top_results = wGetStats("browser",$top_limit,$top_condition);
			$char_len = round(($max_char_len*.17)+$widthoffset,0); ?>
		<td style="min-width:<?php echo ($char_len-5); ?>px;">
		<ul class="charts">
			<li class="chartsT"><?php _e("TOP BROWSER", "wassup") ?></li>
<?php		$i=0;
		if (count($top_results) >0) {
			foreach ($top_results as $top10) { ?>
			<li class="charts"><?php echo $top10->top_count.': ';
			echo '<span class="top10" title="'.$top10->top_item.'">'.stringShortener($top10->top_item, $char_len).'</span>'; ?>
			</li>
<?php			$i++;
			}
		}
		//finish list with empty <li> for styling consistency 
		if ($i < $top_limit) {
			for ($i; $i<$top_limit; $i++) { 
				echo '<li class="charts">&nbsp; &nbsp;</li>'."\n";
			}
		} ?>
		</ul>
		</td>
		<?php }  //end if topbrowser

		//#output top 10 operating systems...
		if ($top_ten['topos'] == 1) { 
			echo "<!--heartbeat-->\n";
			//$top_results = $wpdb->get_results("SELECT count(os) as top_os, os FROM $table_name WHERE timestamp BETWEEN $from_date AND $to_date AND os!='' AND os NOT LIKE '%N/A%' AND spider='' $spamselect GROUP BY os ORDER BY top_os DESC LIMIT 10");
			$top_results = wGetStats("os",$top_limit,$top_condition);
			$char_len = round(($max_char_len*.15)+$widthoffset,0); ?>
		<td style="min-width:<?php echo ($char_len-5); ?>px;">
		<ul class="charts">
			<li class="chartsT"><?php _e("TOP OS", "wassup") ?></li>
<?php 		$i=0;
		if (count($top_results) >0) {
			foreach ($top_results as $top10) { ?>
			<li class="charts"><?php print $top10->top_count.': '; ?>
				<span class="top10" title="<?php echo $top10->top_item; ?>"><?php echo stringShortener($top10->top_item, $char_len); ?></span>
			</li>
<?php			$i++;
			}
		}
		//finish list with empty <li> for styling consistency 
		if ($i < $top_limit) {
			for ($i; $i<$top_limit; $i++) { 
				echo '<li class="charts">&nbsp; &nbsp;</li>'."\n";
			}
		} ?>
		</ul>
		</td>
		<?php } // end if topos
		
		//#output top 10 locales/geographic regions...
		if ($top_ten['toplocale'] == 1) {
			echo "<!--heartbeat-->\n";
			//$top_results = $wpdb->get_results("SELECT count(LOWER(language)) as top_locale, LOWER(language) as locale FROM $table_name WHERE timestamp BETWEEN $from_date AND $to_date AND language!='' AND spider='' $spamselect GROUP BY locale ORDER BY top_locale DESC LIMIT 10");
			$top_results = wGetStats("language",$top_limit,$top_condition);
			$char_len = round(($max_char_len*.15)+$widthoffset,0); ?>
		<td style="min-width:<?php echo ($char_len-5); ?>px;">
		<ul class="charts">
			<li class="chartsT"><?php _e("TOP LOCALE", "wassup"); ?></li>
<?php 		$i=0;
		if (count($top_results) >0) {
			foreach ($top_results as $top10) { ?>
			<li class="charts"><?php echo $top10->top_count.': ';
			echo '<img src="'.WASSUPURL.'/img/flags/'.strtolower($top10->top_item).'.png" alt="" />'; ?>
			<span class="top10" title="<?php echo $top10->top_item; ?>"><?php echo $top10->top_item; ?></span>
			</li>
<?php 			$i++;
			}
		}
		//finish list with empty <li> for styling consistency 
		if ($i < $top_limit) {
			for ($i; $i<$top_limit; $i++) { 
				echo '<li class="charts">&nbsp; &nbsp;</li>'."\n";
			}
		} ?>
		</ul>
		</td>
		<?php } // end if toplocale
		
		//#output top visitors
		if ($top_ten['topvisitor'] == 1) {
			echo "<!--heartbeat-->\n";
			$result = false;
			$char_len = round(($max_char_len*.17)+$widthoffset,0);
			$tmptable = "top_visitor".rand(0,999);
			if (mysql_query ("CREATE TEMPORARY TABLE {$tmptable} SELECT username as visitor, '1loggedin_user' as visitor_type, `timestamp` as visit_timestamp FROM $table_name WHERE `timestamp` BETWEEN $from_date AND $to_date AND username!='' $spamselect UNION SELECT comment_author as visitor, '2comment_author' as visitor_type, `timestamp` as visit_timestamp FROM wp_wassup WHERE `timestamp` BETWEEN $from_date AND $to_date AND username='' AND comment_author!='' $spamselect UNION SELECT hostname as visitor, '3hostname' as visitor_type, `timestamp` as visit_timestamp FROM wp_wassup WHERE `timestamp` BETWEEN $from_date AND $to_date AND username='' AND comment_author='' AND spider=''")) {
    				$numRows = mysql_affected_rows();
    				if ($numRows > 0) {
					$result = mysql_query ("SELECT count(visitor) as top_visitor, visitor, visitor_type FROM {$tmptable} WHERE visitor!='' GROUP BY visitor ORDER BY 1 DESC, visitor_type, visitor LIMIT $top_limit");
				}
			} //end if mysql_query
		 ?>
		<td style="min-width:<?php echo ($char_len-5); ?>px;">
		<ul class="charts">
			<li class="chartsT"><?php _e("TOP VISITOR", "wassup"); ?></li>
<?php 		$i=0;
		if ($result) { 
		while ($top10 = mysql_fetch_array($result,MYSQL_ASSOC)) { ?>
			<li class="charts"><?php echo $top10['top_visitor'].': '; ?>
			<span class="top10" title="<?php echo $top10['visitor']; ?>"><?php echo stringShortener($top10['visitor'], $char_len); ?></span>
			</li>
<?php 			$i++;
		}
		mysql_free_result($result);
		} //end if result
		mysql_query("DROP TABLE IF EXISTS {$tmptable}");
		//finish list with empty <li> for styling consistency 
		if ($i < $top_limit) {
			for ($i; $i<$top_limit; $i++) { 
				echo '<li class="charts">&nbsp; &nbsp;</li>'."\n";
			}
		} ?>
		</ul>
		</td>
		<?php } // end if topvisitor
		?>
		</tr>
		</table>
		<?php if ($wassup_settings['wassup_spamcheck'] == 1) { 
			echo "\n<br/>"; ?>
		<span style="font-size:6pt;">* <?php _e("This top ten doesn't include Spam records","wassup"); ?></span>
		<?php } ?>
	</div>
	<?php 
	} else {
		echo '<span style="color:red;">Action.php '.__("ERROR: Missing or unknown parameters","wassup").', action='.attribute_escape($_GET["action"]).'</span>';
	}  
	if ($debug_mode) {
		if (function_exists('profiler_endSection')) {
			profiler_endSection('(Tot)Action.php');
			profiler_printResults();
		}
		//$wpdb->print_error();	//debug
		ini_set('display_errors',$mode_reset);	//turn off debug
	}?>
</body></html>	
	<?php
} else {
	echo '<span style="color:red;">Action.php '.__("ERROR: Nothing to do here","wassup").'</span>';
} //end if !$hashfail
?>
