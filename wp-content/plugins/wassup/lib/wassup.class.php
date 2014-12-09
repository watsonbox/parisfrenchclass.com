<?php
/**
 * @package WassUP
 * @subpackage
 */
/* #######
 * ##  wassupOptions - A PHP 4 Class for Wassup plugin option settings
 * ##    for Wassup version 1.7.2.
 * ##    Contains variables and functions used to set or change wassup 
 * ##    settings in Wordpress' wp_options table and to output those
 * ##    values for use in forms. 
 * ##  Author: Helene D. 2/24/08, 6/21/09
 */
//global $wpdb;
if (!class_exists('wassupOptions')) {
class wassupOptions {
	/* general/detail settings */
	var $wassup_debug_mode = "0";	
	var $wassup_refresh = "3";	
	var $wassup_userlevel = "8";
	var $wassup_screen_res = "800";	
	var $wassup_default_type = "";	
	var $wassup_default_spy_type = "";
	var $wassup_default_limit = "10";
	var $wassup_top10 ;	//array containg top ten preferences
	var $wassup_dashboard_chart;	
	var $wassup_geoip_map;	
	var $wassup_googlemaps_key;
	var $wassup_time_format;

	/* recording settings */
	var $wassup_active = "1";	
	var $wassup_loggedin = "1";
	var $wassup_admin = "1";	//since 1.7 - record administrators
	var $wassup_spider = "1";
	var $wassup_attack = "1";
	var $wassup_hack = "1";	
	var $wassup_exclude;	
	var $wassup_exclude_url;
	var $wassup_exclude_user;	//since 1.7 - for exclusion by username

	/* spam settings */
	var $wassup_spamcheck;
        var $wassup_spam;
        var $wassup_refspam;

	/* table/file management settings */
	var $wassup_savepath;
	var $delete_auto;
        var $delete_auto_size;
	var $wassup_remind_mb;
	var $wassup_remind_flag;
	var $wassup_uninstall;	//for complete uninstall of wassup
	var $wassup_optimize;	//for optimize table once a day
	var $wassup_version;	//since 1.7 - for wassup table updates by revision# 
	var $wassup_table;	//new in 1.7.2 - WassUp table name
	var $wassup_dbengine;	//new in 1.7.2 - MySQL table engine type

	/* chart display settings */
	var $wassup_chart;
	var $wassup_chart_type;

	/* widget settings */
	var $wassup_widget_title;
	var $wassup_widget_ulclass;
	var $wassup_widget_loggedin;
	var $wassup_widget_comauth;
	var $wassup_widget_search;
	var $wassup_widget_searchlimit;
	var $wassup_widget_ref;
	var $wassup_widget_reflimit;
	var $wassup_widget_topbr;
	var $wassup_widget_topbrlimit;
	var $wassup_widget_topos;
	var $wassup_widget_toposlimit;
	var $wassup_widget_chars;

	/* temporary action settings */
	var $wassup_alert_message;	//used to display alerts
	var $wmark;
	var $wip;
	var $whash = "";	//wp_hash value used by action.php

	/* Constructor */
	function wassupoptions() {
		//# initialize class variables with current options 
		//# or with defaults if none
		$this->loadSettings();
	}

	/* Methods */
	function loadDefaults() {
		global $version, $wpdb;
		$this->wassup_active = "1";
		$this->wassup_loggedin = "1";
		$this->wassup_admin = "1";	//new, record administrators
		$this->wassup_spider = "1";
		$this->wassup_attack = "1";
		$this->wassup_hack = "1";
		$this->wassup_spamcheck = "1";
        	$this->wassup_spam = "1";
        	$this->wassup_refspam = "1";
		$this->wassup_exclude = "";
		$this->wassup_exclude_url = "";
		$this->wassup_exclude_user = ""; //new, exclude username
		$this->wassup_savepath = null;
		$this->wassup_chart = "1";
		$this->wassup_chart_type = "2";	//2-axes chart
		$this->delete_auto = "never";
        	$this->delete_auto_size = "0";
		$this->wassup_remind_mb = "0";
		$this->wassup_remind_flag = "0";
		$this->wassup_refresh = "3";
		$this->wassup_userlevel = "8";
		$this->wassup_screen_res = "800";
		$this->wassup_default_type = "everything";
		$this->wassup_default_spy_type = "everything";
		$this->wassup_default_limit = "10";
		$this->wassup_dashboard_chart = "0";
		$this->wassup_geoip_map = "0";
		if (!isset($this->wassup_googlemaps_key)) {
			//don't discard key even with "reset-to-default"
			$this->wassup_googlemaps_key= "";
		}
		$this->wassup_time_format = "24";
		$this->wassup_widget_title = "Visitors Online";
		$this->wassup_widget_ulclass = "links";
		$this->wassup_widget_loggedin = "1";
		$this->wassup_widget_comauth = "1";
		$this->wassup_widget_search = "1";
		$this->wassup_widget_searchlimit = "5";
		$this->wassup_widget_ref = "1";
		$this->wassup_widget_reflimit = "5";
		$this->wassup_widget_topbr = "1";
		$this->wassup_widget_topbrlimit = "5";
		$this->wassup_widget_topos = "1";
		$this->wassup_widget_toposlimit = "5";
		$this->wassup_widget_chars = "18";
		$this->wassup_alert_message = "";
		$this->wassup_uninstall = "0";
		$this->wassup_optimize = wassup_get_time();
		$this->wassup_top10 = attribute_escape(serialize(array(
			"topsearch"=>"1",
			"topreferrer"=>"1",
			"toprequest"=>"1",
			"topbrowser"=>"1",
			"topos"=>"1",
			"toplocale"=>"0",
			"topvisitor"=>"0",
			"topfeed"=>"0",
			"topcrawler"=>"0",
			"topreferrer_exclude"=>"")));
		$this->whash = $this->get_wp_hash();
		$this->wassup_version = "";	//set in wassup_install only
		$this->wassup_table = $wpdb->prefix . "wassup";
		$this->wassup_dbengine = $this->getMySQLsetting("engine");
	}

	//#Load class variables with current options or with defaults 
	function loadSettings() {
		//# load class variables with current options or load
		//#   default settings if no options set.
		$this->loadDefaults();
		$options_array = get_option('wassup_settings');
		if (!empty($options_array)) {
			foreach ($options_array as $optionkey => $optionvalue) {
				if (isset($this->$optionkey)) { //returns false for null values
					$this->$optionkey = $optionvalue;
				} elseif (array_key_exists($optionkey,$this)) {
					$this->$optionkey = $optionvalue;
				}
			}
		}
		return true;
	}

	//#Save class variables to the Wordpress options table
	function saveSettings() {
		//#  convert class variables into an array and save using
		//#  Wordpress functions, "update_option" or "add_option"
		//#convert class into array...
		$settings_array = array();
		foreach (array_keys(get_class_vars(get_class($this))) as $k) {
			$settings_array[$k] = "{$this->$k}";
		}
		//#save array to options table...
		$options_check = get_option('wassup_settings');
		if (!empty($options_check)) {
			update_option('wassup_settings', $settings_array);
		} else {
			add_option('wassup_settings', $settings_array, 'Options for WassUp');
		}
		return true;
	}

	function deleteSettings() {
		//#delete the contents of the options table...
		delete_option('wassup_settings');
	}

	//#Return an array containing all possible values of $key, a class 
	//#  variable name or the name of an input field. For use in form 
	//#  validation, etc.
	function getKeyOptions($key,$meta="") {
		$key_options = array();
		$key_options_meta = array();
		$key_default = "";	//default value
		switch ($key) {
		case "wassup_screen_res":
			$key_options = array("640","800","1024","1200");
			$key_options_meta = array("&nbsp;640",
				"&nbsp;800",
				"1024",
				"1200");
			$key_default = "800";
			break;
		case "wassup_userlevel":
			$key_options = array("8","6","2");
			$key_options_meta = array(
				__("Administrator"),
				'&nbsp;'.__("Editor"),
				'&nbsp;'.__("Author"));
			break;
		case "wassup_chart_type":
			$key_options = array("0","1","2");
			$key_options_meta = array(
				__("None - don't show chart","wassup"),
				__("One - 2 lines chart 1 axis","wassup"),
				__("Two - 2 lines chart 2 axes","wassup"));
			$key_default = "2";
			break;
		case "wassup_default_type":
		case "wassup_default_spy_type":
			$key_options = array("everything", 
					"spider", "nospider",
					"spam", "nospam",
					"nospamspider",
					"loggedin",
					"comauthor",
					"searchengine",
					"referrer");
			$key_options_meta = array(
				__("Everything","wassup"),
				__("Spider","wassup"),
				__("No spider","wassup"),
				__("Spam","wassup"),
				__("No Spam","wassup"),
				__("No Spam, No Spider","wassup"),
				__("Users logged in","wassup"),
				__("Comment authors","wassup"),
				__("Referrer from search engine","wassup"),
				__("Referrer from ext link","wassup"));
			break;
		case "wassup_default_limit":
			$key_options = array("10","20","50","100");
			$key_options_meta = array("&nbsp;10",
				"&nbsp;20",
				"&nbsp;50",
				"100");
			break;
		case "delete_auto":
			$key_options = array("never", 
					"-1 day",
					"-1 week", 
					"-1 month", 
					"-3 months",
					"-6 months",
					"-1 year");
			$key_options_meta = array(
				__("Don't delete anything","wassup"),
				__("24 hours","wassup"),
				__("1 week","wassup"),
				__("1 month","wassup"),
				__("3 months","wassup"),
				__("6 months","wassup"),
				__("1 year","wassup"));
			break;
		case "wassup_time_range":
			$key_options = array(".4","1","7","30","90","180","365","0");
			$key_options_meta = array(__("6 hours"),
				__("24 hours"),
				__("7 days"),
				__("1 month"),
				__("3 months"),
				__("6 months"),
				__("1 year"),
				__("all time"));
			$key_default = "1";
			break;
		default: 	//enable/disable is default
			$key_options =  array("1","0");
			$key_options_meta =  array("Enable","Disable");
		} //end switch
		if ($key_default == "") {
			$key_default = $key_options[0];
		}
		if ($meta == "meta") {
			return $key_options_meta;
		} elseif ($meta == "default") {
			return $key_default;
		} else {
			return $key_options;
		}
	} //end getKeyOptions

	//#generates <options> tags for use in a <select> form.  
	//#   $itemkey must a class variable name or a "key" name from 
	//#   'getKeyOptions' above.
	function showFormOptions ($itemkey,$selected="",$optionargs="") {
		$form_items =$this->getKeyOptions($itemkey);
		if (count($form_items) > 0) {
			$form_items_meta = $this->getKeyOptions($itemkey,"meta");
			if ($selected == "") { 
				if (isset($this->$itemkey)) {
					$selected = $this->$itemkey;
				} else { 
					$selected = $form_items[0];
				}
			}
			foreach ($form_items as $k => $option_item) {
	        		echo "\n\t\t".'<option value="'.$optionargs.$option_item.'"';
	        		if ($selected == $option_item) { echo ' SELECTED>'; }
				else { echo '>'; }
				echo $form_items_meta[$k].'&nbsp;&nbsp;</option>';
			}
		}
	} //end showFormOptions


	//#Sets the class variable, wassup_savepath, with the given 
	//#  value $savepath
	function setSavepath($savepath="") {
		$savepath = rtrim($savepath,"/");
		$blogurl = rtrim(get_bloginfo('home'),"/");
		if (!empty($savepath)) {
			//remove site URL from path in case user entered it
			if (strpos($savepath, $blogurl) === 0) {
				$tmppath=substr($savepath,strlen($blogurl)+1);
			} elseif (strpos($savepath,'/') === 0 && !$this->isWritableFolder($savepath)) {
				$tmppath=substr($savepath,1);
			} elseif (strpos($savepath,'./') === 0 ) {
				$tmppath=substr($savepath,2);
			} else { 
				$tmppath = $savepath;
			}
			//append website root or home directory to relative paths...
			if (preg_match('/^[a-zA-Z]/',$tmppath) > 0 || strpos($tmppath,'../') === 0) {
				if (!empty($_ENV['DOCUMENT_ROOT'])) {
					$tmppath = rtrim($_ENV['DOCUMENT_ROOT'],'/').'/'.$tmppath;
				} elseif (!empty($_ENV['HOME'])) {
					$tmppath = rtrim($_ENV['HOME'],'/').'/'.$tmppath;
				}
				if ($this->isWritableFolder($tmppath)) {
					$savepath = $tmppath;
				}
			} 
		}
		$this->wassup_savepath = $savepath;
	}

	//#Return true if the given directory path exists and is writable
	function isWritableFolder($folderpath="") {
		$folderpath=trim($folderpath);	//remove white spaces
		if (!empty($folderpath) && strpos($folderpath,'http://') !== 0 ) {
			if (file_exists($folderpath)) { 
				$testfile = rtrim($folderpath,"/")."/temp".time().'.txt';
				//#check that the directory is writable...
				if (@touch($testfile)) { unlink($testfile); }
				else { return false; }
			} else {
				return false;
			}
		} else {
			return false;
		}
		return true;
	}

	//#Return a MySQL system variable value or '' if variable is not set
	//# Currently only 'engine' option supported.
	//#  Added in revision 1.7.2
	function getMySQLsetting($mysql_var) {
		global $wpdb;
		$mysql_value = "";
		//default mysql_var request is "engine"
		if (empty($mysql_var) || $mysql_var == "engine") {
			$table_status = $wpdb->get_results("SHOW TABLE STATUS LIKE '{$this->wassup_table}'");
			foreach ($table_status as $fstatus) {
				if (isset($fstatus->Engine)) {
					$mysql_value = $fstatus->Engine;
					break 1;
				} elseif (isset($fstatus->Type)) {
					$mysql_value = $fstatus->Type;
					break 1;
				}
			}
		} else {
			$sql_vars = $wpdb->get_results("SHOW VARIABLES LIKE '{$mysql_var}'");
		}
		return $mysql_value;
	}

	//#Set a wp_hash value and return it
	function get_wp_hash($hashkey="") {
		$wassuphash = "";
		if (function_exists('wp_hash')) { 
			if (empty($hashkey)) {
				if (defined('SECRET_KEY')) { 
					$hashkey = SECRET_KEY;
				} else { 
					$hashkey = "wassup";
				}
			}
			$wassuphash = wp_hash($hashkey);
		}
		return $wassuphash;
	} //end function get_wp_hash

	//#show a system message in Wassup Admin menus
	function showMessage($message="") {
		if (empty($message) && !empty($this->wassup_alert_message)) {
			$message = $this->wassup_alert_message;
		}
		//#check for error message/notice message
		if (stristr($message,"error") !== FALSE || stristr($message,"problem") !== FALSE) {
			echo '<div id="wassup-error" class="fade error" style="color:#d00;padding:10px;">'.$message;
			//print_r($this); // #debug
			echo '</div>'."\n";
		} else {
			echo '<div id="wassup-message" class="fade updated" style="color:#040;padding:10px;">'.$message;
			//print_r($this); // #debug
			echo '</div>'."\n";
		}
	} //end showMessage

	function showError($message="") {
		$this->showMessage($message);
	}
} //end class wassupOptions
}
?>
