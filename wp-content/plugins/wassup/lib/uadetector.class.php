<?php
/**
 * UADetector.class.php
 * @version 0.8
 * @author Helene Duncker <helene@techfromhel.com>
 * Author URI: http://www.techfromhel.com/
 *
 * @copyright Copyright (c) 2009 Helene Duncker
 * @license http://www.gnu.org/licenses/gpl.txt GNU General Public License
 */
/**
 * Class: UADetector 
 * Description: a PHP4 class for browser & spider identification
 * Usage: include_once(uadetector.class.php); 
 *         $browser_info = new UADetector();
 * Updated: 2009-07-20
 */
class UADetector {
	var $name;
	var $version;
	var $os;
	var $language;
	var $device;		//PC, PDA, Phone, TV, if known
	var $model;		//Manufacturer+model, if known
	var $resolution;	//given by some embedded devices
	var $subscribers;	//feed subscribers, if given in user-agent
	var $agenttype; //B=Browser, F=feed reader, H=harvester bot, 
	//M=monitor/piracy checker bot, R=archiver/search engine robot, 
	//S=Spammer/Script injection bot, V=Validator (Link/CSS/Html)
	var $agent;
	var $browscap;	//private
	var $allbrowserschecked;	//private
	var $allspiderschecked; 	//private

	/* constructor */
	function uaDetector($ua="") {
		global $debug_mode;
		if ($debug_mode && function_exists('profiler_beginSection')) {
			profiler_beginSection('(Subtot)uaDetector');
		}
		if (empty($ua)) { 
			$this->agent = trim($_SERVER['HTTP_USER_AGENT']);
		} else {
			$this->agent = trim($ua);
		}
		$this->name = "";
		$this->version = "";
		$this->os = "";
		$this->device = "";
		$this->model = "";
		$this->language = "";
		$this->resolution = "";
		$this->subscribers = "";
		$this->browscap = array();
		$this->agenttype = "";
		$this->allbrowserschecked = false; //true after isBrowser() is parsed
		$this->allspiderschecked = false; //true after isSpider() is parsed
		if (strlen($this->agent)<5) { 	//nothing to check
			$this->name = $this->agent;
			$this->agenttype = 'R';
		} else {
			if ($this->isTopAgent()===false) {
			if ($this->isBrowser()===false) {
			if ($this->isSpider()===false) {
				$this->isWTF();
			}}}
		}
		if (function_exists('profiler_endSection')) {
			profiler_endSection('(Subtot)uaDetector');
		}
		return;
	} //end function uaDetector

	function isTopAgent($agent="") {
		//Check if user agent is a frequent (top) user agent and 
		//  and breakdown agent information into an array of Name,
		//  Version,  Language, Platform, OS, and UA agenttype 
		//  (B=browser, R=bot, F=feed/RSS reader, V=link verifier, 
		//  U=utility...)
		//NOTE: Top agents are based on recent log data from 
		//  "WassUp", a web statistics plugin for WordPress 2.2+ at 
		//   http://www.wpwp.org
		global $debug_mode;

		// User agent parameter or class variable is required.
		// Defaults to $_SERVER[HTTP_USER_AGENT] if not set.
		if (empty($agent)) { 
			if (!empty($this->agent)) { $ua = $this->agent; }
			else { $ua = trim($_SERVER['HTTP_USER_AGENT']); }
		}
		if (empty($ua)) {  //nothing to check
			return false;
		}
		if ($debug_mode && function_exists('profiler_beginSection')) {
			profiler_beginSection('____ua::isTopAgent');
		}

		// Check if $agent is a top (popular) user agent. 
		$os="";
		$top_ua = array('name'=>"",'version'=>"",'os'=>"",'platform'=>"",'language'=>"",'agenttype'=>"");
		// #1 Googlebot 
		if (preg_match("#^Mozilla/\d\.\d\s\(compatible;\sGooglebot/(\d\.\d);[\s\+]+http\://www\.google\.com/bot\.html\)$#i",$ua,$match)>0) {
			$top_ua['name'] = "Googlebot";
			$top_ua['version']=$match[1];
			$top_ua['agenttype']= "R";
		// #2 IE 8/7/6 on Windows 7/2008/Vista/XP/2003/2000
		} elseif (preg_match('#^Mozilla/\d\.\d\s\(compatible;\sMSIE\s(\d+)(?:\.\d+)+;\s(Windows\sNT\s\d\.\d(?:;\sW[inOW]{2}64)?)(?:;\sx64)?;?(?:\sSLCC1;?|\sSV1;?|\sGTB\d;|\sTrident/\d\.\d;|\sFunWebProducts;?|\s\.NET\sCLR\s[0-9.]+;?|\s(Media\sCenter\sPC|Tablet\sPC)\s\d\.\d;?|\sInfoPath\.\d;?)*\)$#',$ua,$match)>0) {
			$top_ua['name'] = 'IE';
			$top_ua['version'] = $match[1];
			$top_ua['platform']='Windows'; 
			$os = $match[2];
			$top_ua['os'] = $this->winOSversion($os);
			$top_ua['agenttype']= 'B';
			if (!empty($match[3])) {
				$top_ua['device'] = $match[3];
			}
		// #3 Firefox or other Mozilla browsers on Windows
		} elseif (preg_match('#^Mozilla/\d\.\d\s\(Windows;\sU;\s(.+);\s([a-z]{2}(?:\-[A-Za-z]{2})?);\srv\:\d(?:.\d+)+\)\sGecko/\d+\s([A-Za-z\-0-9]+)/(\d+(?:.\d+)+)(?:\s\(.*\))?$#',$ua,$match)>0) {
			$top_ua['name'] = $match[3];
			$top_ua['version'] = $match[4];
			$top_ua['language']=$match[2];
			$top_ua['platform']="Windows"; 
			$os = $match[1];
			$top_ua['os'] = $this->winOSversion($os);
			$top_ua['agenttype']= 'B';
		// #4 Yahoo!Slurp
		} elseif (preg_match('#^Mozilla/\d\.\d\s\(compatible;\s(Yahoo\!\s([A-Z]{2})?\s?Slurp)/?(\d\.\d)?;\shttp\://help.yahoo.com/help/us/ysearch/slurp\)$#i',$ua,$match)>0) {
			$top_ua['name'] = $match[1];
			if (!empty($match[3])) { 
				$top_ua['version']=$match[3];
			}
			if (!empty($match[2])) { 
				$top_ua['language']=$match[2];
			}
			$top_ua['agenttype']= 'R';
		// #5 MSNBot, msnbot-media
		} elseif (preg_match('#^(?:msnbot(\-media)?)/(\d.\d)\s\(\+http\://search\.msn\.com/msnbot\.htm\)$#',$ua,$match)>0) {
			$top_ua['name'] = 'MSNBot';
			if (!empty($match[1])) { 
				$top_ua['name']=$top_ua['name'].$match[1];
			}
			if (!empty($match[2])) { 
				$top_ua['version']=$match[2];
			}
			$top_ua['agenttype']= 'R';
		// #6 Twiceler
		} elseif (preg_match('#^Mozilla/\d\.\d\s\(Twiceler\-(\d\.\d)\shttp://www\.cuill?\.com/twiceler/robot.html\)$#',$ua,$match)>0) {
			$top_ua['name'] = 'Twiceler';
			$top_ua['version']=$match[1];
			$top_ua['agenttype']= 'R';
		// #7 FeedBurner
		} elseif (preg_match('#^FeedBurner/(\d\.\d)\s\(http\://www\.FeedBurner\.com\)$#',$ua,$match)>0) {
			$top_ua['name'] = 'FeedBurner';
			$top_ua['version']=$match[1];
			$top_ua['agenttype']= 'F';
		// #8 Wordpress
		} elseif (preg_match('#^WordPress/(?:wordpress(\-mu)\-)?(\d\.\d+)(?:.\d+)*(?:\-[a-z]+)?(?:\;\shttp\://[a-z0-9_\.\:\/]+)?$#',$ua,$match)>0) {
			$top_ua['name'] = 'Wordpress';
			if (!empty($match[1])) { 
				$top_ua['name']=$top_ua['name'].$match[1];
			}
			$top_ua['version']=$match[2];
			$top_ua['agenttype']= 'U';
		// #9 Firefox and Gecko browsers on Mac/*nix/OS/2 etc...
		} elseif (preg_match('#^Mozilla/\d\.\d\s\((Macintosh|X11|OS/2);\sU;\s(.+);\s([a-z]{2}(?:\-[A-Za-z]{2})?)(?:-mac)?;\srv\:\d(?:.\d+)+\)\sGecko/\d+\s([A-Za-z\-0-9]+)/(\d+(?:\.[0-9a-z\-\.]+))+(?:(\s\(.*\))(?:\s([A-Za-z\-0-9]+)/(\d+(?:.\d+)+)))?$#',$ua,$match)>0) {
			$top_ua['name'] = $match[4];
			$top_ua['version'] = $match[5];
			$top_ua['language']=$match[3];
			$top_ua['platform']=$match[1];
			$os = $match[2];
			if (!empty($match[7])) { 
				$top_ua['name'] = $match[7];
				$top_ua['version'] = $match[8];
				$os=$os." ".$match[4]." ".$match[5];
			} elseif (!empty($match[6])) { 
				$os=$os.$match[6];
			}
			list($top_ua['os']) = $this->OSversion($os,$top_ua['platform'],$ua);
			$top_ua['agenttype']= 'B';
		// #10 Safari and Safari-based browsers on all platforms
		} elseif (preg_match('#^Mozilla/\d\.\d\s\(([A-Za-z0-9/.]+);\sU;?\s?(.*);\s?([a-z]{2}(?:\-[A-Za-z]{2})?)?\)\sAppleWebKit/[0-9.]+\+?\s\((?:KHTML,\s)?like\sGecko\)(?:\s([a-zA-Z0-9./]+(?:\sMobile)?)/?[A-Z0-9]*)?\sSafari/([0-9.]+)$#',$ua,$match)>0) {
			$top_ua['name'] = 'Safari';
			if (!empty($match[4])) { $vers = $match[4]; } 
			else { $vers = $match[5]; }
			$browser = $this->safariVersion($vers,$ua);
			if (!empty($browser) && is_array($browser)) {
				$top_ua['name'] = $browser['name'];
				$top_ua['version'] = $browser['version'];
			}
			if (empty($match[2])) {
				$os = $match[1];
			} else {
				$top_ua['platform'] = $match[1];
				$os = $match[2];
			}
			if ($top_ua['platform'] == 'Windows') {
				$top_ua['os'] = $this->winOSversion($os);
			} else {
				list($top_ua['os']) = $this->OSversion($os,$top_ua['platform'],$ua);
			}
			$top_ua['language']=$match[3];
			$top_ua['agenttype']= 'B';
		}
		//set class vars and return array
		if (!empty($top_ua['name'])) {
			//if agent is class var, set other class vars.
			if ($agent == "") {
				$this->setClassVars($top_ua);
			}
		} else {
			$top_ua=false;
		}
		if ($debug_mode) {
			echo '<br />\nuadetector: top_ua->name='.$top_ua['name']; //debug
			if (function_exists('profiler_endSection')) {
				profiler_endSection('____ua::isTopAgent');
			}
		}
		return ($top_ua);
	} //end function isTopUserAgent

	function isBrowser($agent="") {
		global $debug_mode;

		if (empty($agent)) { 
			if (!empty($this->agent)) { $ua = $this->agent; }
			else { $ua = trim($_SERVER['HTTP_USER_AGENT']); }
		}
		if (empty($ua)) {  //nothing to check
			return false;
		}
		//##detect browsers
		$browser = array('name'=>"",'version'=>"",'os'=>"",'platform'=>"",'language'=>"",'agenttype'=>"B",'resolution'=>"",'device'=>"");
		$wap = false;

		//spiders are not detected here, so exclude user agents that are likely spiders (ie. contains an email or URL, or spider-like keywords)
		if (!$this->allspiderschecked && preg_match('#(robot|bot[\s\-_\/]|bot$|checker|crawl|feed|fetcher|libwww|link\s?|parser|reader|spider|href|https?\://|.+(?:\@|\s?at\s?)[a-z0-9_\-]+(?:\.|\s?dot\s?)|www[0-9]?\.[a-z0-9_\-]+\..+|\/.+\.(html?|aspx?|php5?|cgi))#i',$ua)>0) {
			//not spider if embedded browser or is a browser add-on such as spyware or translator
			if (preg_match('#(embedded\s?(WB|Web\sbrowser)|dynaweb|bsalsa\.com|muuk\.co|translat[eo]r?)#i',$ua)==0) {
				return false;
			}
		}
		if ($debug_mode && function_exists('profiler_beginSection')) {
			profiler_beginSection('_____ua::isBrowser');
		}
		//MSIE browsers
		if (preg_match('/compatible(?:;|\s)+MSIE\s(\d+)(\.\d+)+(.*)/',$ua,$pcs)>0) {
			$browser['name'] = 'IE';
			$browser['version'] = $pcs[1];
			$iestring = $pcs[3];
			//differentiate IE from IE-based/IE-masked browsers or spiders
			if (preg_match('/\s(AOL|America\sOnline\sBrowser)\s(\d+)(\.\d+)*/',$iestring,$pcs)>0) {
				$browser['name'] = 'AOL';
				$browser['version'] = $pcs[2];

			} elseif (preg_match('#\s(Opera|Netscape)/?\s?(\d+)(\.\d+)*#',$iestring,$pcs)>0) {
				$browser['name'] = $pcs[1];
				$browser['version'] = $pcs[2];

			} elseif (preg_match('/\s(Avant|Orca)\sBrowser;/',$iestring,$pcs)>0) {
				$browser['name'] = $pcs[1];
				$browser['version'] = "";

			} elseif (preg_match('/Windows\sCE;\s?IEMobile\s(\d+)(\.\d+)*\)/i',$iestring,$pcs)>0) {
				$browser['name'] = 'IEMobile';
				$browser['version'] = $pcs[1];
				$browser['os'] = 'WinCE';
				$browser['platform'] = 'WAP';
			} elseif (preg_match('#\s(\d+x\d+)?\;?\s?(?:WebTV|MSNTV)(?:/|\s)([0-9\.]+)*#i',$iestring,$pcs)>0) {
				$browser['name'] = "MSNTV";
				$browser['version'] = $pcs[2];
				$browser['platform'] = 'Embedded';
				$browser['device'] = 'TV';
				if (!empty($pcs[1])) {
					$browser['resolution'] = $pcs[1];
				}
			}

		//Opera browsers
		} elseif (preg_match('#Opera\sMini[/ ]([0-9\.]+)#',$ua,$pcs)>0) {
				$browser['name'] = 'Opera Mini';
				$browser['version'] = $pcs[1];
		} elseif (preg_match('#Opera[/ ]([0-9\.]+)#',$ua,$pcs)>0) {
				$browser['name'] = 'Opera';
				$browser['version'] = $pcs[1];

		//Firefox-based browsers (Camino, Flock) (find before FF)
		} elseif (preg_match('#[^a-z](Camino|Flock|Orca)/(\d+[\.0-9a-z]*)#',$ua,$pcs)>0) {
				$browser['name'] = $pcs[1];
				$browser['version'] = $pcs[2];
		//other Gecko-type browsers (incl. Firefox)
		} elseif (preg_match('#Gecko/\d+\s([a-z0-9_\- ]+)/(\d+[\.0-9a-z]*)(?:$|;?\s([a-z0-9_\- ]+)/(\d+[\.0-9a-z]*))#i',$ua,$pcs)>0) {
				$browser['name'] = $pcs[1];
				$browser['version'] = $pcs[2];
				if (!empty($pcs[3]) && stristr($pcs[3],"Firefox")!==false) {
					$browser['name'] = 'Firefox';
					$browser['version'] = $pcs[4];
				} elseif (stristr($pcs[1],'Firefox')!==false) {
					$browser['name'] = 'Firefox';
				}
		//Firefox browser
		} elseif (preg_match('#[^a-z]Firefox/?(\d+[\.0-9a-z]*)?#',$ua,$pcs)>0) {
				$browser['name'] = 'Firefox';
				if (!empty($pcs[1])) {
					$browser['version'] = $pcs[1];
				}
		//Mozilla browser (like FF, but user agent ends with Gecko)
		} elseif (preg_match('#(?:rv\:(\d[\.0-9a-z]+))\)?[^a-z]Gecko/\d+$#',$ua,$pcs)>0) {
				$browser['name'] = 'Mozilla';
				if (!empty($pcs[1])) {
					$browser['version'] = $pcs[1];
				}

		//NetFront and other mobile/embedded browsers
		} elseif (preg_match("#(NetFront|NF\-Browser)/([0-9\.]+)#i",$ua,$pcs)) {
				$browser['name'] = "NetFront";
				$browser['version'] = $pcs[2];
				$browser['platform'] = "WAP";	//mobile device
		} elseif (preg_match("#[^a-z0-9](Novarra\-Vision|Polaris|Iris)/([0-9\.]+)#i",$ua,$pcs)) {
				$browser['name'] = $pcs[1];
				$browser['version'] = $pcs[2];
				$browser['platform'] = "WAP";	//mobile device
		} elseif (preg_match("#(UP\.browser|SMIT\-Browser)/([0-9\.]+)#i",$ua,$pcs)) {
				$browser['name'] = $pcs[1];
				$browser['version'] = $pcs[2];
				$browser['platform'] = "WAP";	//mobile device
		} elseif (preg_match("#\((jig\sbrowser).*\s([0-9\.]+)[^a-z0-9]#i",$ua,$pcs)) {
				$browser['name'] = $pcs[1];
				$browser['version'] = $pcs[2];
				$browser['platform'] = "WAP";	//mobile device
		//Any browser that use the word "browser" in agent
		} elseif (preg_match("#([a-z0-9]+)[\- _\.]Browser[/ v\.]*([0-9\.]+)?#i",$ua,$pcs)) {
			$browser['name'] = $pcs[1];
			if (!empty($pcs[2])) {
				$browser['version'] = $pcs[2];
			} else {
				$browser['version'] = "";
			}
		}
		
		//##detect mobile/embedded devices
		//known mobile devices...
		if (preg_match('#(amoi|blackberry|htc|ipaq|kindle|kwc|lge|mobilephone|motorola|nokia|PDA|Palm|Samsung|sanyo|smartphone|SonyEricsson|vodafone|zte)[/-_ ]?([a-z]*\d+[a-z]*)*#i',$ua,$pcs)>0) {
			$browser['platform'] = "WAP";
			$browser['device'] = $pcs[1];
			if (!empty($pcs[2])) { 
				$browser['model'] = $pcs[2];
			}
			if ($pcs[1] == "KWC") { 
				$browser['device'] == "Kyocera phone";
				$browser['model'] == $pcs[0];
			}
			if (empty($browser['name'])) {
				$browser['name'] = $pcs[1];
			}

		//check if user-agent has mobile profile
		} elseif (preg_match('#(J2ME/MIDP|Profile/MIDP|Danger\sHiptop|\sOpenWeb\s\d)#i',$ua)>0) {
			$browser['platform'] = "WAP";

		}
		//check if browser HTTP header has a mobile profile
		if ($ua == $_SERVER['HTTP_USER_AGENT']) {
			if (!$wap) {
				$header_profile =array('X_WAP_PROFILE','PROFILE','13_PROFILE','56_PROFILE');
  				foreach ($header_profile AS $wap_profile) {
    				if (!empty($_ENV["HTTP_{$wap_profile}"])) {
					//has a user-agent profile header, so it's probably a mobile device
					$wap = true;
      					break 1;
				}
				}
			}
			if ($wap && empty($browser['platform'])) { //is mobile device
				$browser['platform'] = "WAP";
			}

			//read header HTTP_*PIXELS for mobile device screen resolution
    			if (!empty($_ENV['HTTP_UA_PIXELS'])) {
				$browser['resolution'] = str_replace('X','x',$_ENV['HTTP_UA_PIXELS']);
    			} elseif (!empty($_ENV['HTTP_X_UP_DEVCAP_SCREENPIXELS'])) {
				$browser['resolution'] = str_replace(',','x',$_ENV['HTTP_X_UP_DEVCAP_SCREENPIXELS']);
			}
		}

		//simple alphanumeric strings are usually a crawler
		if (empty($browser['name']) && preg_match("#^([a-z]+[\s_]?[a-z]*)[\-/]?([0-9\.]+)*$#",$ua,$pcs)>0) {
			$browser['name']=trim($pcs[1]);
			if (!empty($pcs[2])) { 
				$browser['version']=$pcs[2];
			}
			if (empty($browser['os']) && $browser['platform'] != "WAP" && stristr($pcs[1],'mozilla')===false) {
				$browser['agenttype']= "R";
			}
		}

		//get operating systems
		if (empty($browser['os']) && !empty($browser['name']) && $browser['agenttype']=="B") {
			list($browser['os'],$platform) = $this->OSversion($browser['os'],$browser['platform'],$ua);
			if (!empty($platform) && empty($browser['platform'])) {
				$browser['platform'] = $platform;
			}
			//if (empty($browser['os']) && empty($browser['platform']) {
			//	$browser['os'] = "unknown";
			//}
		}

		//some user-agents may contain screen resolution 
		if (empty($browser['resolution'])) {
			if (preg_match("#screen(?:res)?[ -/](\d{3,4}[x*]\d{3,4})#",$ua,$pcs)>0) {
				$browser['resolution'] = str_replace('*','x',$pcs[1]);
			} elseif (($browser['platform']=="WAP" || $browser['os']=="WAP") && preg_match("#[ ;](\d{3,4}x\d{3,4})([;)x ]|$)#",$ua,$pcs)>0) {
				$browser['resolution'] = $pcs[1];
			}
		}

		if ( $browser['agenttype'] == "B" ) {
			$browser['language'] = $this->detectLanguage($ua);
		}
		if (empty($browser['name'])) {
			$browser=false;
		} else {
			$this->setClassVars($browser);
		}
		$this->allbrowserschecked=true;
		if (function_exists('profiler_endSection')) {
			profiler_endSection('_____ua::isBrowser');
		}
		return $browser;
	} //end function isBrowser

	function isSpider($agent="") {
		global $debug_mode;

		if (empty($agent)) { 
			if (!empty($this->agent)) { $ua = $this->agent; }
			else { $ua = trim($_SERVER['HTTP_USER_AGENT']); }
		}
		if (empty($ua)) {  //nothing to check
			return false;
		}
		if ($debug_mode && function_exists('profiler_beginSection')) {
			profiler_beginSection('_____ua::isSpider');
		}
		//##detect spiders
		$spider = array('name'=>"",'version'=>"",'os'=>"",'platform'=>"",'language'=>"",'agenttype'=>"R",'subscribers'=>"");
		// #11 FriendFeedBot
		if (preg_match('#^Mozilla/\d\.\d\s\(compatible;\sFriendFeedBot/([0-9.]+);\s\+Http\://friendfeed\.com/about/bot\)$#',$ua,$match)>0) {
			$spider['name'] = 'FriendFeedBot';
			$spider['version']=$match[1];
			$spider['agenttype']= 'F';
		
		// #12 FeedFetcher Google
		} elseif (preg_match('#^Feedfetcher\-Google[;\s\(\+]+http://www.google.com/feedfetcher\.html[;\)\s]+(?:(\d)\ssubscriber)?#',$ua,$match)>0) {
			$spider['name'] = 'FeedFetcher-Google';
			if (!empty($match[1])) {
				$spider['subscribers']= $match[1];
			}
			$spider['agenttype']= 'F';

		//Twitterfeed
		} elseif (preg_match('/[^a-z]twitterfeed/i',$ua,$match)>0) {
			$spider['name'] = 'Twitterfeed';
			$spider['agenttype']= 'F';

		//Spiders with bot, spider, or crawler in name plus version#
		} elseif (preg_match('#(\w+[\s\-_]?(?:bot|spider|crawler))(?:[\/\s\-\:_])?v?([0-9\.]+)#i',$ua,$match)>0) {
			$spider['name'] = $match[1];
			$spider['version']=$match[2];
		
		// Nutch spiders
		} elseif (preg_match('#^([a-z]+)?/?nutch\-([0-9\.]+)#i',$ua,$match)>0) {
			if (!empty($match[1])) {
				$spider['name']= $match[1];
			} else {
				$spider['name']= 'Nutch';
			}
			$spider['version']=$match[2];

		// Larbin spiders
		} elseif (preg_match('#^larbin[\-_\s\/]?(v?[0-9\.]+)?#i',$ua,$match)>0) {
			$spider['name']= 'Larbin';
			if (!empty($match[1])) {
				$spider['version']=$match[1];
			}
		} elseif (preg_match('#^([a-z]+)[\-_\s\/]?(v?[0-9\.]+)?[^a-z]+larbin([0-9\.]+)\@#i',$ua,$match)>0) {
			$spider['name']= $match[1];
			if (!empty($match[2])) {
				$spider['version']=$match[2];
			}

		// #Assume bot if user-agent includes a url (http|www)
		// #Daumoa spider, et al...
		} elseif (preg_match('/^(?:Mozilla\/.*compatible[^a-z]*).*(?:http|www)[^a-z].*\.(?:com|net|org|html?|aspx?|[a-z]{2})[^a-z0-9]+([a-z0-9\-_]+)[\/|\s|v]+([\d\.]+)/i',$ua,$match)>0) {
			$spider['name'] = $match[1];
			if (!empty($match[2])) {
				$spider['version'] = $match[2];
			}
		// #Assume bot if user-agent includes a url (http|www) with a name repeated
		} elseif (preg_match('/^(?:Mozilla\/.*compatible[^a-z]*)?(([a-z]{3,})[\-\s_]?(?:bot|crawl|robot|spider|parser|reader)?[a-z]*)[^a-z^0-9]+v?\s?([0-9\.]+)?.*[^a-z]+(?:http|www).*[^a-z]+(?:\2|\3)\/?(?:\.?[a-z]+)?\.(?:com|net|org|html?|aspx?|[a-z]{2})/i',$ua,$match)>0) {
			$spider['name'] = $match[1];
			if (!empty($match[3])) {
				$spider['version'] = $match[3];
			}
		// #Assume bot if user-agent 1st word and a contact domain are the same name, ex: Feedburner-feedburner.com, CazoodleBot, 
		} elseif (preg_match('/([a-z\_\s\.]+)[\s\/\-_]?(v?[0-9\.]+)?.*(?:http\:\/\/|www\.)(\1)\.[a-z0-9_\-]+/i',$ua,$match)>0) {
			$spider['name'] = $match[1];
			if (!empty($match[2])) {
				$spider['version'] = $match[2];
			}
		// #Assume bot if one-word user-agent+http address
		} elseif (preg_match('/^([a-z\_\.]+)[\s\/\-_]?(v?[0-9\.]+)?[\s\(\+]*(?:http\:\/\/|www\.)[a-z0-9_\-]+\.[a-z0-9_\-.]+\)?/i',$ua,$match)>0) {
			$spider['name'] = $match[1];
			if (!empty($match[2])) {
				$spider['version'] = $match[2];
			}
		// #Assume bot if name+http//name...
		} elseif (preg_match('/([a-z]+[a-z0-9]{2,})[\s\/\-]?([0-9\.]+)?[^a-z]+[^0-9]*http\:.*\/(\1)[^a-z]/i',$ua,$match)>0) {
			$spider['name'] = $match[1];
			if (!empty($match[2])) {
				$spider['version'] = $match[2];
			}

		// #Assume bot if user-agent includes contact email
		} elseif (preg_match('/^(([a-z]+)\s?(bot|crawler|robot|spider|\s[a-z]+)?)[\/\-\s_](v?[0-9\.]+)?.*[^a-z]+(?:\1|\2|\3)(?:\@|\s?at\s?)[a-z\-_]+(?:\.|\s?dot\s)(?:com|net|org|[a-z]{2})/i',$ua,$match)>0) {
			$spider['name'] = $match[1];
			if (!empty($match[4])) {
				$spider['version'] = $match[4];
			}
		} elseif (preg_match('/^(([a-z]+)\s?(bot|crawler|robot|spider|\s[a-z]+)?)[\/\-\s_](v?[0-9\.]+)?.*[^a-z]+[a-z\-_]+(?:\@|\s?at\s?)(?:\1|\2|\3)(?:\.|\s?dot\s)(?:com|net|org|[a-z]{2})/i',$ua,$match)>0) {
			$spider['name'] = $match[1];
			if (!empty($match[4])) {
				$spider['version'] = $match[4];
			}
		} elseif (preg_match('/^([a-z]+)[\/\-\s_](v?[0-9\.]+)?.*[a-z0-9_\.]+(?:\@|\sat\s)[a-z0-9\-_]+(?:\.|\s?dot\s)(?:com|net|org|[a-z]{2})[^a-z]/i',$ua,$match)>0) {
			$spider['name'] = $match[1];
			if (!empty($match[2])) {
				$spider['version'] = $match[2];
			}

		// #Assume bot if one-word user-agent. ex:
		} elseif (preg_match('/^([a-z\_\.]+)[\s\/\-_]?(v?[0-9\.]+)?$/i',$ua,$match)>0) {
			$spider['name'] = $match[1];
			if (!empty($match[2])) {
				$spider['version'] = $match[2];
			}

		/* // #Yahoo spiders
		} elseif (preg_match('#$#',$ua,$match)>0) {
		*/

		// Libwww spiders

		} else {	//check for script injection bots
			if ($this->isSpambot($ua) !== false) {
				$spider['name'] = "Script Injection Bot";
				$spider['agenttype']= "S";
			} elseif (preg_match("#(robot|bot[\s\-_\/]|bot$|crawl|spider|feed[\s\-_\/]|feed$|fetcher|parser|reader|href|link[\s\-_\/]|linkcheck|checker|http\:\/\/|[^a-z]www[0-9]?\.[a-z0-9_\-]+\.[a-z]{2,3}[^a-z])#i",$ua)>0) {
				if (function_exists('__')) {
					$spider['name'] = __('Unknown Spider');
				} else {
					$spider['name'] = 'Unknown Spider';
				}
				$spider['agenttype']= "R";
			} elseif (preg_match("#([a-z0-9_]+(?:\@|\sat\s)[a-z0-9_\-]+(?:\.|\sdot\s)|\/.+\.(?:html?|aspx?|php5?|cgi))#i",$ua)>0) {
				if (function_exists('__')) {
					$spider['name'] = __('Unknown Spider');
				} else {
					$spider['name'] = 'Unknown Spider';
				}
				$spider['agenttype']= 'R';
			}
		}
		if (!empty($spider['name'])) { 
			//distinguish feed readers from other spiders
			if (empty($spider['subscribers'])  && preg_match("/([0-9]{1,10})\s?subscriber/i",$ua,$subscriber) > 0) {
				// It's a feedreader with some subscribers
				$spider['subscribers'] = $subscriber[1];
				$spider['agenttype']= "F";
			}
			if ($spider['agenttype'] != "F" && preg_match("/(feed|rss|atom|xml)/i",$ua)>0) {
				$spider['agenttype']= "F";
			}

			//add the OS, if given
			if (empty($spider['os'])) {
			}

			$this->setClassVars($spider);
		} else { 
			$spider=false;
		}
		$this->allspiderschecked = true;
		if (function_exists('profiler_endSection')) {
			profiler_endSection('_____ua::isSpider');
		}
		return $spider;
	} //end function isSpider

	/* determine operating system and platform from string, $os or $ua
	 * and return values for operating system and platform
	 */
	function OSversion($os,$platform="",$ua="") {
		if (empty($ua) && !empty($this->agent)) { $ua = $this->agent; }
		if (empty($os)) { $os=$ua; }
		//some browsers (IEMobile) show os in HTTP header, use when available
		if ($ua == $_SERVER['HTTP_USER_AGENT']) {
			if (!empty($_ENV['HTTP_UA_OS'])) {
				$os = $_ENV['HTTP_UA_OS'];
			}
		}
		if (empty($os)) { return null; }
		$os_type = "";
		if (preg_match('/(Windows|Win|NT)[0-9; \)\/]/',$os)>0) {
			$os_type = $this->winOSversion($os);
			$platform = "Windows";
		} elseif (strpos($os,'Intel Mac OS X')!==FALSE || strpos($os,'PPC Mac OS X')!==FALSE) {
			$platform = 'Macintosh';
			$os_type = 'MacOSX';
		} elseif (strpos($os,' Mac OS X')!==FALSE) {
			if (!empty($platform)) {
				$os_type = "{$platform}";
			} else {
				$os_type = 'MacOSX';
				$platform = 'Macintosh';
			}
		} elseif (preg_match('/[^a-z0-9]iPhone\sOS\s(\d+)?(?:_\d)*/i',$os,$match)>0) {
			//iPhone OS similar to OSX, so must be tested before
			//  OS X to be identified separately
			if (strpos($os,'iPod')!==FALSE) {
				$os_type = 'iPhone';
				$platform = 'iPod';
			} else {
				$os_type = 'iPhone';
				$platform = 'iPhone';
			}
			if (!empty($match[1])) {
				$os_type .= " $match[1]";
			}
		} elseif (strpos($os,'Mac OS X')!==FALSE) {
				$os_type = 'MacOSX';
				$platform = 'Macintosh';
		} elseif (preg_match('/Android\s?([0-9.]+)?/',$os)>0) {
				$os_type = 'Android';	//Google Android
				if (!empty($match[1])) $version = $match[1];
				$platform = 'WAP';	//Linux
		} elseif (preg_match('/[^a-z0-9]BeOS[^a-z0-9]/',$os)>0) {
				$os_type = 'BeOS';
		} elseif (preg_match('/((?:Free|Open|Net)BSD)\s?(?:[ix]?[386]+)?\s?([0-9\.]+)?/',$os,$match)>0) {
				$os_type = $match[1];
				if (!empty($match[2])) $version = $match[2];
				
		//distinguish between Linux PPC, Linux i686==Linux x86_64
		} elseif (preg_match('/Linux\s*((?:i[0-9]{3})?\s*(?:[0-9]\.[0-9]{1,2}\.[0-9]{1,2})?\s*(?:i[0-9]{3})?)?/',$os,$match)>0) {
				$os_type = 'Linux';
				$version = $match[1];
				$platform = 'X11';
		} elseif (preg_match('/(Mac_PowerPC|Macintosh)/',$os)>0) {
				$os_type = 'MacPPC';
				$platform = 'Macintosh';
		} elseif (stristr($os,'Nintendo Wii')!==false) {
				$os_type = 'Nintendo Wii';
		} elseif (strpos($os,'PalmOS')!==FALSE) {
				$os_type = 'PalmOS';
		} elseif (preg_match('/PLAYSTATION\s(\d)/i',$os,$match)>0) {
				$os_type = 'Playstation';
				$version = $match[1];
		} elseif (preg_match('/IRIX\s*([0-9\.]+)?/i',$os,$match)>0) {
				$os_type = 'SGI Irix';
				if (!empty($match[1])) {
					$version = $match[1];
				}
		} elseif (preg_match('/Solaris\s?([0-9\.]+)?/i',$os,$match)>0) {
				$os_type = 'Solaris';
				if (!empty($match[1])) {
					$version = $match[1];
				}
		} elseif (preg_match('/SunOS\s?(i?[0-9\.]+)?/i',$os,$match)>0) {
				$os_type = 'SunOS';
				if (!empty($match[1])) {
					$version = $match[1];
				}
		} elseif (preg_match('/SymbianOS\/([0-9.]+)/i',$os,$match)>0) {
				$os_type = 'SymbianOS';
				$version = $match[1];
		} elseif (preg_match('#Ubuntu[ /-](\d+[\.0-9a-z]*)?#i',$os)>0) {
				$os_type = 'Ubuntu';
				$platform = 'X11';
		} elseif (preg_match('/\(PDA(?:.*)\)(.*)Zaurus/',$os)>0) {
				$os_type = 'Zaurus';	//Sharp Zaurus
		} elseif (preg_match('#^Mozilla/\d.\d\s\((\w+);\sU;#',$os,$match)>0) {
				$os_type = $match[1];
		} elseif (!empty($platform)) {
				$os_type = $platform;
		}
		return (array($os_type,$platform));
	}  //end function OSversion

	/* determine Microsoft operating system from string, $os */
	function winOSversion($os,$platform="",$ua="") {
		if (empty($os)) { return false; }
		if (strstr($os,'Windows NT 6.2')) {
			$winos = 'Win7';
		} elseif (strstr($os,'Windows NT 6.1')) {
			$winos = 'Win2008';
		} elseif (strstr($os,'Windows NT 6.0')) {
			$winos = 'WinVista';
		} elseif (strstr($os,'Windows NT 5.2')) {
			$winos = 'Win2003';
		} elseif (strstr($os,'Windows NT 5.1')) {
			$winos = 'WinXP';
		} elseif (strstr($os,'Windows NT 5.0') || strstr($os,'Windows 2000')) {
			$winos = 'Win2000';
		} elseif (strstr($os,'Windows ME')) {
			$winos = 'WinME';
		} elseif (preg_match('/Win(?:dows\s)?NT\s?([0-9\.]+)?/',$os,$match)>0) {
			$winos = 'WinNT';
			if (!empty($match[1])) { $winos .= " ".$match[1]; }
		} elseif (preg_match('/(?:Windows95|Windows 95|Win95|Win 95)/',$os)>0) {
			$winos = 'Win95';
		} elseif (preg_match('/(?:Windows98|Windows 98|Win98|Win 98|Win 9x)/',$os)>0) {
			$winos = 'Win98';
		} elseif (preg_match('/(?:WindowsCE|Windows CE|WinCE|Win CE)[^a-z0-9]+(?:.*Version\s([0-9\.]+))?/i',$os)>0) {
			$winos = 'WinCE';
			if (!empty($match[1])) { $winos .= " ".$match[1]; }
		} elseif (preg_match('/(Windows|Win)\s?3\.\d[; )\/]/',$os)>0) {
			$winos = 'Win3.x';
		} elseif (preg_match('/(Windows|Win)[0-9; )\/]/',$os)>0) {
			$winos = 'Windows';
		}
		if (strstr($os,'WOW64') || strstr($os,'Win64') || strstr($os,'x64')) {
			$winos = $winos.' x64';
		}
		return ($winos);
	} //end function winOSversion

	/* determine Safari/Webkit-based browser and version from string, $webkit_string */
	function safariVersion($webkit="",$ua="") {
		global $debug_mode;
		$browser = "Safari";
		$vers = "";
		if ($debug_mode) {
			echo "<br />webkit=".$webkit."\n"; //debug
		}
		if (empty($webkit)) { 
			return false;
		} elseif (preg_match("#^([a-zA-Z]+)/([0-9](?:[A-Za-z.0-9]+))(\sMobile)?#",$webkit,$match)>0) {
			if ($match[1] != "Version") { //Chrome, Iron, Shiira
				$browser = $match[1];
			}
			$vers = $match[2];
			if ($vers == "0") { $vers = ""; }
			if (!empty($match[3])) { //Mobile browser
				$vers .= $match[3];
			}
		} elseif (is_numeric($webkit)) {
			$webkit_num = (int)($webkit-0.5);
			if ($debug_mode) {
				echo "<br /><!-- webkit_num = ".$webkit_num." -->\n"; //debug
			}
			if ($webkit_num > 525) { $vers = "4"; }
			elseif ($webkit_num > 419) { $vers = "3"; }
			elseif ($webkit_num > 312) { $vers = "2"; }
			elseif ($webkit_num > 99) { $vers = "1"; }
			else { $vers = ""; } //beta version, 0.x
		} //end else !empty($webkit)
		return array('name'=>$browser,'version'=>$vers);
	}  //end function safariVersion

	//check PHP browscap data for browser and platform (last resort)
	function getBrowscap($ua) {
		if (empty($ua)) $ua = $this->agent;
		$browsercap = array();
		$browser = "";
		$os = "";
		$version = "";
		if (ini_get("browscap") != "" && !empty($ua)) {
			if ($debug_mode && function_exists('profiler_beginSection')) {
				profiler_beginSection('__ua::getBrowscap');
			}
			$browsercap = get_browser($ua,true);
			$this->browscap = $browsercap;
			if (!empty($browsercap['platform']) && stristr($browsercap['platform'],"unknown") === false) {
				$os = $browsercap['platform'];
				if (!empty($browsercap['browser'])) {
					$browser = $browsercap['browser'];
				} else {
					$browser = $browsercap['parent'];
				}
			} else {
				$browser = $browsercap['browser'];
			}
			if (!empty($browsercap['version']) && $browscap['spider'] == FALSE) {
				$browser = trim($browser)." ".$browsercap['version'];
			}
			if (function_exists('profiler_endSection')) {
				profiler_endSection('__ua::getBrowscap');
			}
		} else {
			return false;
		}
	} //end function getBrowscap

	function detectLanguage($ua) {
		$language="";
		if (empty($ua)) $ua = $this->agent;
		if (preg_match("/(?:\s|;|\[)(([a-z]{2})(?:\-([a-zA-Z]{2}))?)(?:;|\]|\))/",$ua,$match)>0) {
			$language = $match[1];
		}
		return $language;
	}

	//return true and #of subscriber if feed, false if not feed
	function isFeed($feed_name,$ua="") {
		if (empty($ua)) {
			if (!empty($feed_name)) { 
				$ua=$feed_name;
				$feed_name="";
			} else {
				$ua = $this->agent;
			}
		}
		//distinguish feed readers from other spiders
		if (preg_match("/([0-9]{1,10})\s?subscriber/i",$ua,$subscriber) > 0) {
			// It's a feedreader with some subscribers
			$feed['subscribers'] = $subscriber[1];
			$feed['agenttype']= "F";
		} elseif (preg_match("/(feed|rss)/i",$ua)>0) {
			$feed['agenttype']= "F";
		}
		if (!empty($feed['agenttype'])) {
			if (!empty($feed_name)) {
				$feed['name'] = $feed_name;
			}
			return $feed;
		} else {
			return false;
		}
	} //end function isFeed

	/* Find obvious spam bots and hackers */
	function isSpambot($ua="") {
		if (empty($ua)) { $ua = $this->agent; }
		$crawlertype="";
		$crawler="";
		//## Find obvious script injection bots 
		if (stristr($ua,'location.href')!==FALSE) {
			$crawlertype = "S";
			$crawler = "Script Injection bot";
		} elseif (preg_match('/(<|&lt;|&#60;|%3C)script/i',$ua)>0) {
			$crawlertype = "S";
			$crawler = "Script Injection bot";
		} elseif (preg_match('/(<|&lt;|&#60;|%3C)a(\s|%20|&#32;|\+)+href/i',$ua)>0) {
			$crawlertype = "S";
			$crawler = "Script Injection bot";
		} elseif (preg_match('/(select|update).*( |%20|%#32;|\+)from( |%20|%#32;|\+)/i',$ua)>0) {
			$crawlertype = "S";
			$crawler = "Script Injection bot";
		} elseif (preg_match('/(drop|alter)(?:\s|%20|%#32;|\+)table/i',$ua)>0) {
			$crawlertype = "S";
			$crawler = "Script Injection bot";
		}

		if (!empty($crawler)) {
			return array($crawler,$crawlertype);
		} else {
			return false;
		}
	} //end function isSpamBot

	function isWTF($ua="") {
		//recheck browsers and then do a PHP 'get_browser' call 
		if (!$this->allbrowserschecked) {
			return $this->isBrowser($ua);
		} else {
			return false;
			//$this->getBrowscap($ua);
		}
	}

	function setClassVars($assocArray) {
		foreach($assocArray as $key => $value) {
			$this->$key = $value;
		}
	}

	//return a major version # from a version string argument
	function majorVersion($versionstring) {
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
	} //end majorVersion
} //end class UADetector
?>
