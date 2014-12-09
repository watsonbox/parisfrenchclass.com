<?php
if (!class_exists('pagination')) { 	//in case another app uses this class...
class pagination{
/*
Script Name: *Digg Style Paginator Class
Script URI: http://www.mis-algoritmos.com/2007/05/27/digg-style-pagination-class/
Description: Class in PHP that allows to use a pagination like a digg or sabrosus style.
Script Version: 0.3.2
Author: Victor De la Rocha
Author URI: http://www.mis-algoritmos.com
*/
	/*Default values*/
        var $total_pages;
        var $limit;
        var $target;
        var $page;
        var $adjacents;
        var $showCounter;
        var $className;
        var $parameterName;
        var $urlF ;

        /*Buttons next and previous*/
        var $nextT;
        var $nextI;
        var $prevT;
        var $prevI;

        /*****/
        var $calculate;
	
	#Total items
	function items($value){$this->total_pages = intval($value);}
	
	#how many items to show per page
	function limit($value){$this->limit = intval($value);}
	
	#Page to sent the page value
	function target($value){$this->target = $value;}
	
	#Current page
	function currentPage($value){$this->page = intval($value);}
	
	#How many adjacent pages should be shown on each side of the current page?
	function adjacents($value){$this->adjacents = intval($value);}
	
	#show counter?
	function showCounter($value=""){$this->showCounter=($value===true)?true:false;}

	#to change the class name of the pagination div
	function changeClass($value=""){$this->className=$value;}

	function nextLabel($value){$this->nextT = $value;}
	function nextIcon($value){$this->nextI = $value;}
	function prevLabel($value){$this->prevT = $value;}
	function prevIcon($value){$this->prevI = $value;}

	#to change the class name of the pagination div
	function parameterName($value=""){$this->parameterName=$value;}

	#to change urlFriendly
	function urlFriendly($value="%"){
			if(eregi('^ *$',$value)){
					$this->urlF=false;
					return false;
				}
			$this->urlF=$value;
		}
	
	var $pagination;

	function pagination(){
                /*Set Default values*/
                $this->total_pages = null;
                $this->limit = null;
                $this->target = "";
                $this->page = 1;
                $this->adjacents = 2;
                $this->showCounter = false;
                $this->className = "pagination";
                $this->parameterName = "pages";
                $this->urlF = false;//urlFriendly

                /*Buttons next and previous*/
                $this->nextT = __("Next","wassup");
                $this->nextI = "&#187;"; //&#9658;
                $this->prevT = __("Previous","wassup");
                $this->prevI = "&#171;"; //&#9668;

                $this->calculate = false;
	}
	function show(){
			if(!$this->calculate)
				if($this->calculate())
					echo "<div class=\"$this->className\">$this->pagination</div>";
		}
	function get_pagenum_link($id){
			if(strpos($this->target,'?')===false)
					if($this->urlF)
							return str_replace($this->urlF,$id,$this->target);
						else
							return "$this->target?$this->parameterName=$id";
				else
					return "$this->target&$this->parameterName=$id";
		}
	
	function calculate(){
			$this->pagination = "";
			$this->calculate == true;
			$error = false;
			if($this->urlF and $this->urlF != '%' and strpos($this->target,$this->urlF)===false){
					//Es necesario especificar el comodin para sustituir
					echo 'Especificaste un wildcard para sustituir, pero no existe en el target<br />';
                                        $error = true;
                                }elseif($this->urlF and $this->urlF == '%' and strpos($this->target,$this->urlF)===false){
                                        echo 'Es necesario especificar en el target el comodin';
                                        $error = true;
                                }
                        if($this->total_pages == null){
                                        echo __("It is necessary to specify the","wassup")." <strong>".__("number of pages","wassup")."</strong> (\$class->items(1000))<br />";
                                        $error = true;
                                }
                        if($this->limit == null){
                                        echo __("It is necessary to specify the","wassup")." <strong>".__("limit of items","wassup")."</strong> ".__("to show per page","wassup")." (\$class->limit(10))<br />";
                                        $error = true;
				}
			if($error)return false;
			
			$n = trim($this->nextT.' '.$this->nextI);
			$p = trim($this->prevI.' '.$this->prevT);
			
			/* Setup vars for query. */
			if($this->page) 
				$start = ($this->page - 1) * $this->limit;             //first item to display on this page
			else
				$start = 0;                                //if no page var is given, set start to 0
		
			/* Setup page vars for display. */
			if ($this->page == 0) $this->page = 1;                    //if no page var is given, default to 1.
			$prev = $this->page - 1;                            //previous page is page - 1
			$next = $this->page + 1;                            //next page is page + 1
			$lastpage = ceil($this->total_pages/$this->limit);        //lastpage is = total pages / items per page, rounded up.
			$lpm1 = $lastpage - 1;                        //last page minus 1
			
			/* 
				Now we apply our rules and draw the pagination object. 
				We're actually saving the code to a variable in case we want to draw it more than once.
			*/
			
			if($lastpage > 1){
					//anterior button
					if($this->page > 1)
							$this->pagination .= "<a href=\"".$this->get_pagenum_link($prev)."\">$p</a>";
						else
							$this->pagination .= "<span class=\"disabled\">$p</span>";
					//pages	
					if ($lastpage < 7 + ($this->adjacents * 2)){//not enough pages to bother breaking it up
							for ($counter = 1; $counter <= $lastpage; $counter++){
									if ($counter == $this->page)
											$this->pagination .= "<span class=\"current\">$counter</span>";
										else
											$this->pagination .= "<a href=\"".$this->get_pagenum_link($counter)."\">$counter</a>";
								}
						}
					elseif($lastpage > 5 + ($this->adjacents * 2)){//enough pages to hide some
							//close to beginning; only hide later pages
							if($this->page < 1 + ($this->adjacents * 2)){
									for ($counter = 1; $counter < 4 + ($this->adjacents * 2); $counter++){
											if ($counter == $this->page)
													$this->pagination .= "<span class=\"current\">$counter</span>";
												else
													$this->pagination .= "<a href=\"".$this->get_pagenum_link($counter)."\">$counter</a>";
										}
									$this->pagination .= "...";
									$this->pagination .= "<a href=\"".$this->get_pagenum_link($lpm1)."\">$lpm1</a>";
									$this->pagination .= "<a href=\"".$this->get_pagenum_link($lastpage)."\">$lastpage</a>";
								}
							//in middle; hide some front and some back
							elseif($lastpage - ($this->adjacents * 2) > $this->page && $this->page > ($this->adjacents * 2)){
									$this->pagination .= "<a href=\"".$this->get_pagenum_link(1)."\">1</a>";
									$this->pagination .= "<a href=\"".$this->get_pagenum_link(2)."\">2</a>";
									$this->pagination .= "...";
									for ($counter = $this->page - $this->adjacents; $counter <= $this->page + $this->adjacents; $counter++)
										if ($counter == $this->page)
												$this->pagination .= "<span class=\"current\">$counter</span>";
											else
												$this->pagination .= "<a href=\"".$this->get_pagenum_link($counter)."\">$counter</a>";
									$this->pagination .= "...";
									$this->pagination .= "<a href=\"".$this->get_pagenum_link($lpm1)."\">$lpm1</a>";
									$this->pagination .= "<a href=\"".$this->get_pagenum_link($lastpage)."\">$lastpage</a>";
								}
							//close to end; only hide early pages
							else{
									$this->pagination .= "<a href=\"".$this->get_pagenum_link(1)."\">1</a>";
									$this->pagination .= "<a href=\"".$this->get_pagenum_link(2)."\">2</a>";
									$this->pagination .= "...";
									for ($counter = $lastpage - (2 + ($this->adjacents * 2)); $counter <= $lastpage; $counter++)
										if ($counter == $this->page)
												$this->pagination .= "<span class=\"current\">$counter</span>";
											else
												$this->pagination .= "<a href=\"".$this->get_pagenum_link($counter)."\">$counter</a>";
								}
						}
					//siguiente button
					if ($this->page < $counter - 1)
							$this->pagination .= "<a href=\"".$this->get_pagenum_link($next)."\">$n</a>";
						else
							$this->pagination .= "<span class=\"disabled\">$n</span>";
						if($this->showCounter)$this->pagination .= "<div class=\"pagination_data\">($this->total_pages ".__("Pages","wassup").")</div>";
				}

			return true;
		}
	} //end class pagination
} //end if !class_exists('pagination')

if (!class_exists('Detector')) { 	//in case another app uses this class...
//
// Detector class (c) Mohammad Hafiz bin Ismail 2006
// detect location by ipaddress
// detect browser type and operating system
//
// November 27, 2006
//
// by : Mohammad Hafiz bin Ismail (info@mypapit.net)
// 
// You are allowed to use this work under the terms of 
// Creative Commons Attribution-Share Alike 3.0 License
// 
// Reference : http://creativecommons.org/licenses/by-sa/3.0/
// 

class Detector {

	var $town;
	var $state;
	var $country;
	var $Ctimeformatode;
	var $longitude;
	var $latitude;
	var $ipaddress;
	var $txt;

	var $browser;
	var $browser_version;
	var $os_version;
	var $os;
	var $useragent;

	function Detector($ip="", $ua="")
	{	
		$apiserver="http://showip.fakap.net/txt/";
		if ($ip != "") {	
		if (preg_match('/\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b/',$ip,$matches))
		  {
		    $this->ipaddress=$ip;
		  }

		else { $this->ipaddress = "0.0.0.0"; }

		//uncomment this below if CURL doesnt work		

		$this->txt=file_get_contents($apiserver . "$ip");

		$wtf=$this->txt;
		$this->processTxt($wtf);
		}

		$this->useragent=$ua;
		$this->check_os($ua);
		$this->check_browser($ua);
	}

	function processTxt($wtf)

	{
//	  	$tok = strtok($txt, ',');
	  	$this->town = strtok($wtf,',');
	  	$this->state = strtok(',');
	  	$this->country=strtok(',');
	  	$this->ccode = strtok(',');
	  	$this->latitude=strtok(',');
	  	$this->longitude=strtok(',');
	}

	function check_os($useragent) {

			$os = "N/A"; $version = "";

			if (preg_match("/Windows NT 5.1/",$useragent,$match)) {
				$os = "WinXP"; $version = "";
			} elseif (preg_match("/Windows NT 5.2/",$useragent,$match)) {
				$os = "Win2003"; $version = "";
			} elseif (preg_match("/Windows NT 6.0/",$useragent,$match)) {
				$os = "WinVista"; $version = "";
			} elseif (preg_match("/(?:Windows NT 5.0|Windows 2000)/",$useragent,$match)) {
				$os = "Win2000"; $version = "";
			} elseif (preg_match("/Windows ME/",$useragent,$match)) {
				$os = "WinME"; $version = "";
			} elseif (preg_match("/(?:WinNT|Windows\s?NT)\s?([0-9\.]+)?/",$useragent,$match)) {
				$os = "WinNT"; $version = $match[1];
			} elseif (preg_match("/Mac OS X/",$useragent,$match)) {
				$os = "MacOSX"; $version = "";
			} elseif (preg_match("/(Mac_PowerPC|Macintosh)/",$useragent,$match)) {
				$os = "MacPPC"; $version = "";
			} elseif (preg_match("/(?:Windows95|Windows 95|Win95|Win 95)/",$useragent,$match)) {
				$os = "Win95"; $version = "";
			} elseif (preg_match("/(?:Windows98|Windows 98|Win98|Win 98|Win 9x)/",$useragent,$match)) {
				$os = "Win98"; $version = "";
			} elseif (preg_match("/(?:WindowsCE|Windows CE|WinCE|Win CE)/",$useragent,$match)) {
				$os = "WinCE"; $version = "";
			} elseif (preg_match("/PalmOS/",$useragent,$match)) {
				$os = "PalmOS";
			} elseif (preg_match("/\(PDA(?:.*)\)(.*)Zaurus/",$useragent,$match)) {
				$os = "Sharp Zaurus";
			} elseif (preg_match("/Linux\s*((?:i[0-9]{3})?\s*(?:[0-9]\.[0-9]{1,2}\.[0-9]{1,2})?\s*(?:i[0-9]{3})?)?/",$useragent,$match)) {
				$os = "Linux"; $version = $match[1];
			} elseif (preg_match("/NetBSD\s*((?:i[0-9]{3})?\s*(?:[0-9]\.[0-9]{1,2}\.[0-9]{1,2})?\s*(?:i[0-9]{3})?)?/",$useragent,$match)) {
				$os = "NetBSD"; $version = $match[1];
			} elseif (preg_match("/OpenBSD\s*([0-9\.]+)?/",$useragent,$match)) {
				$os = "OpenBSD"; $version = $match[1];
			} elseif (preg_match("/CYGWIN\s*((?:i[0-9]{3})?\s*(?:[0-9]\.[0-9]{1,2}\.[0-9]{1,2})?\s*(?:i[0-9]{3})?)?/",$useragent,$match)) {
				$os = "CYGWIN"; $version = $match[1];
			} elseif (preg_match("/SunOS\s*([0-9\.]+)?/",$useragent,$match)) {
				$os = "SunOS"; $version = $match[1];
			} elseif (preg_match("/IRIX\s*([0-9\.]+)?/",$useragent,$match)) {
				$os = "SGI IRIX"; $version = $match[1];
			} elseif (preg_match("/FreeBSD\s*((?:i[0-9]{3})?\s*(?:[0-9]\.[0-9]{1,2})?\s*(?:i[0-9]{3})?)?/",$useragent,$match)) {
				$os = "FreeBSD"; $version = $match[1];
			} elseif (preg_match("/SymbianOS\/([0-9.]+)/i",$useragent,$match)) {
				$os = "SymbianOS"; $version = $match[1];
			} elseif (preg_match("/Symbian\/([0-9.]+)/i",$useragent,$match)) {
				$os = "Symbian"; $version = $match[1];
			} elseif (preg_match("/PLAYSTATION 3/",$useragent,$match)) {
				$os = "Playstation"; $version = 3;
			}

			$this->os = $os;
			$this->os_version = $version;
		}

		function check_browser($useragent) {

			$browser = "";

			if (preg_match("/^Mozilla(?:.*)compatible;\sMSIE\s(?:.*)Opera\s([0-9\.]+)/",$useragent,$match)) {
				$browser = "Opera";
			} elseif (preg_match("/^Opera\/([0-9\.]+)/",$useragent,$match)) {
				$browser = "Opera";
			} elseif (preg_match("/^Mozilla(?:.*)compatible;\siCab\s([0-9\.]+)/",$useragent,$match)) {
				$browser = "iCab";
			} elseif (preg_match("/^iCab\/([0-9\.]+)/",$useragent,$match)) {
				$browser = "iCab";
			} elseif (preg_match("/^Mozilla(?:.*)compatible;\sMSIE\s([0-9\.]+)/",$useragent,$match)) {
				$browser = "IE";
			} elseif (preg_match("/^(?:.*)compatible;\sMSIE\s([0-9\.]+)/",$useragent,$match)) {
				$browser = "IE";
			} elseif (preg_match("/^Mozilla(?:.*)(?:.*)Chrome/",$useragent,$match)) {
				$browser = "Google Chrome";
			} elseif (preg_match("/^Mozilla(?:.*)(?:.*)Safari\/([0-9\.]+)/",$useragent,$match)) {
				$browser = "Safari";
			} elseif (preg_match("/^Mozilla(?:.*)\(Macintosh(?:.*)OmniWeb\/v([0-9\.]+)/",$useragent,$match)) {
				$browser = "Omniweb";
			} elseif (preg_match("/^Mozilla(?:.*)\(compatible; Google Desktop/",$useragent,$match)) {
				$browser = "Google Desktop";
			} elseif (preg_match("/^Mozilla(?:.*)\(compatible;\sOmniWeb\/([0-9\.v-]+)/",$useragent,$match)) {
				$browser = "Omniweb";
			} elseif (preg_match("/^Mozilla(?:.*)Gecko(?:.*?)(?:Camino|Chimera)\/([0-9\.]+)/",$useragent,$match)) {
				$browser = "Camino";
			} elseif (preg_match("/^Mozilla(?:.*)Gecko(?:.*?)Netscape\/([0-9\.]+)/",$useragent,$match)) {
				$browser = "Netscape";
			} elseif (preg_match("/^Mozilla(?:.*)Gecko(?:.*?)(?:Fire(?:fox|bird)|Phoenix)\/([0-9\.]+)/",$useragent,$match)) {
				$browser = "Firefox";
			} elseif (preg_match("/^Mozilla(?:.*)Gecko(?:.*?)Minefield\/([0-9\.]+)/",$useragent,$match)) {
				$browser = "Minefield";
			} elseif (preg_match("/^Mozilla(?:.*)Gecko(?:.*?)Epiphany\/([0-9\.]+)/",$useragent,$match)) {
				$browser = "Epiphany";
			} elseif (preg_match("/^Mozilla(?:.*)Galeon\/([0-9\.]+)\s(?:.*)Gecko/",$useragent,$match)) {
				$browser = "Galeon";
			} elseif (preg_match("/^Mozilla(?:.*)Gecko(?:.*?)K-Meleon\/([0-9\.]+)/",$useragent,$match)) {
				$browser = "K-Meleon";
			} elseif (preg_match("/^Mozilla(?:.*)rv:([0-9\.]+)\)\sGecko/",$useragent,$match)) {
				$browser = "Mozilla";
			} elseif (preg_match("/^Mozilla(?:.*)compatible;\sKonqueror\/([0-9\.]+);/",$useragent,$match)) {
				$browser = "Konqueror";
			} elseif (preg_match("/^Mozilla\/(?:[34]\.[0-9]+)(?:.*)AvantGo\s([0-9\.]+)/",$useragent,$match)) {
				$browser = "AvantGo";
			} elseif (preg_match("/^Mozilla(?:.*)NetFront\/([34]\.[0-9]+)/",$useragent,$match)) {
				$browser = "NetFront";
			} elseif (preg_match("/^Mozilla\/([34]\.[0-9]+)/",$useragent,$match)) {
				$browser = "Netscape";
			} elseif (preg_match("/^Liferea\/([0-9\.]+)/",$useragent,$match)) {
				$browser = "Liferea";
			} elseif (preg_match("/^curl\/([0-9\.]+)/",$useragent,$match)) {
				$browser = "curl";
			} elseif (preg_match("/^links\/([0-9\.]+)/i",$useragent,$match)) {
				$browser = "Links";
			} elseif (preg_match("/^links\s?\(([0-9\.]+)/i",$useragent,$match)) {
				$browser = "Links";
			} elseif (preg_match("/^lynx\/([0-9a-z\.]+)/i",$useragent,$match)) {
				$browser = "Lynx";
			} elseif (preg_match("/^Wget\/([0-9\.]+)/i",$useragent,$match)) {
				$browser = "Wget";
			} elseif (preg_match("/^Xiino\/([0-9\.]+)/i",$useragent,$match)) {
				$browser = "Xiino";
			} elseif (preg_match("/^W3C_Validator\/([0-9\.]+)/i",$useragent,$match)) {
				$browser = "W3C Validator";
			} elseif (preg_match("/^Jigsaw(?:.*) W3C_CSS_Validator_(?:[A-Z]+)\/([0-9\.]+)/i",$useragent,$match)) {
				$browser = "W3C CSS Validator";
			} elseif (preg_match("/^Dillo\/([0-9\.]+)/i",$useragent,$match)) {
				$browser = "Dillo";
			} elseif (preg_match("/^amaya\/([0-9\.]+)/i",$useragent,$match)) {
				$browser = "Amaya";
			} elseif (preg_match("/^DocZilla\/([0-9\.]+)/i",$useragent,$match)) {
				$browser = "DocZilla";
			} elseif (preg_match("/^fetch\slibfetch\/([0-9\.]+)/i",$useragent,$match)) {
				$browser = "FreeBSD libfetch";
			} elseif (preg_match("/^Nokia([0-9a-zA-Z\-.]+)\/([0-9\.]+)/i",$useragent,$match)) {
				$browser="Nokia";
			} elseif (preg_match("/^SonyEricsson([0-9a-zA-Z\-.]+)\/([a-zA-Z0-9\.]+)/i",$useragent,$match)) {
				$browser="SonyEricsson";
			}

			//$version = $match[1];
			//restrict version to major and minor version #'s
			preg_match("/^\d+(\.\d+)?/",$match[1],$majorvers);
			$version = $majorvers[0];

			$this->browser = $browser;
			$this->browser_version = $version;
	}
} //end class Detector
} //end if !class_exists('Detector')

function wassup_get_time() {
	$timeright = gmdate("U");
	$offset = (get_option("gmt_offset")*60*60);
	$timeright = ($timeright + $offset) ;
	return $timeright;
}

/*
# PHP Calendar (version 2.3), written by Keith Devens
# http://keithdevens.com/software/php_calendar
#  see example at http://keithdevens.com/weblog
# License: http://keithdevens.com/software/license
*/
//
// Currently not used in WassUp it's a next implementation idea
//
function generate_calendar($year, $month, $days = array(), $day_name_length = 3, $month_href = NULL, $first_day = 0, $pn = array()){
	$first_of_month = gmmktime(0,0,0,$month,1,$year);
	#remember that mktime will automatically correct if invalid dates are entered
	# for instance, mktime(0,0,0,12,32,1997) will be the date for Jan 1, 1998
	# this provides a built in "rounding" feature to generate_calendar()

	$day_names = array(); #generate all the day names according to the current locale
	for($n=0,$t=(3+$first_day)*86400; $n<7; $n++,$t+=86400) #January 4, 1970 was a Sunday
		$day_names[$n] = ucfirst(gmstrftime('%A',$t)); #%A means full textual day name

	list($month, $year, $month_name, $weekday) = explode(',',gmstrftime('%m,%Y,%B,%w',$first_of_month));
	$weekday = ($weekday + 7 - $first_day) % 7; #adjust for $first_day
	$title   = htmlentities(ucfirst($month_name)).'&nbsp;'.$year;  #note that some locales don't capitalize month and day names

	#Begin calendar. Uses a real <caption>. See http://diveintomark.org/archives/2002/07/03
	@list($p, $pl) = each($pn); @list($n, $nl) = each($pn); #previous and next links, if applicable
	if($p) $p = '<span class="calendar-prev">'.($pl ? '<a href="'.htmlspecialchars($pl).'">'.$p.'</a>' : $p).'</span>&nbsp;';
	if($n) $n = '&nbsp;<span class="calendar-next">'.($nl ? '<a href="'.htmlspecialchars($nl).'">'.$n.'</a>' : $n).'</span>';
	$calendar = '<table class="calendar">'."\n".
		'<caption class="calendar-month">'.$p.($month_href ? '<a href="'.htmlspecialchars($month_href).'">'.$title.'</a>' : $title).$n."</caption>\n<tr>";

	if($day_name_length){ #if the day names should be shown ($day_name_length > 0)
		#if day_name_length is >3, the full name of the day will be printed
		foreach($day_names as $d)
			$calendar .= '<th abbr="'.htmlentities($d).'">'.htmlentities($day_name_length < 4 ? substr($d,0,$day_name_length) : $d).'</th>';
		$calendar .= "</tr>\n<tr>";
	}

	if($weekday > 0) $calendar .= '<td colspan="'.$weekday.'">&nbsp;</td>'; #initial 'empty' days
	for($day=1,$days_in_month=gmdate('t',$first_of_month); $day<=$days_in_month; $day++,$weekday++){
		if($weekday == 7){
			$weekday   = 0; #start a new week
			$calendar .= "</tr>\n<tr>";
		}
		if(isset($days[$day]) and is_array($days[$day])){
			@list($link, $classes, $content) = $days[$day];
			if(is_null($content))  $content  = $day;
			$calendar .= '<td'.($classes ? ' class="'.htmlspecialchars($classes).'">' : '>').
				($link ? '<a href="'.htmlspecialchars($link).'">'.$content.'</a>' : $content).'</td>';
		}
		else $calendar .= "<td>$day</td>";
	}
	if($weekday != 7) $calendar .= '<td colspan="'.(7-$weekday).'">&nbsp;</td>'; #remaining "empty" days

	return $calendar."</tr>\n</table>\n";
}

//Truncate $input string to a length of $max
function stringShortener($input, $max=0, $separator="(...)", $exceedFromEnd=0){
	if(!$input || !is_string($input)){return false;};
	
	//Replace all %-hex chars with literals and trim the input string 
	//  of whitespaces ...because it's shorter and more legible. 
	//  -Helene D. 11/18/07
	$instring = trim(stripslashes(rawurldecode(html_entity_decode($input)))," +\t");	//insecure

	$inputlen=strlen($instring);
	$max=(is_numeric($max))?(integer)$max:$inputlen;
	//if($max>=$inputlen){return $input;};	//caused security loophole ...only $outstring should be returned
	if ($max < $inputlen) {
		$separator=($separator)?$separator:"(...)";
		$modulus=(($max%2));
		$halfMax=floor($max/2);
		$begin="";
		if(!$modulus){$begin=substr($instring, 0, $halfMax);}
		else{$begin=(!$exceedFromEnd)? substr($instring, 0, $halfMax+1) : substr($instring, 0, $halfMax);}
		$end="";
		if(!$modulus){$end=substr($instring,$inputlen-$halfMax);}
		else{$end=($exceedFromEnd)? substr($instring,$inputlen-$halfMax-1) :substr($instring,$inputlen-$halfMax);}
		$extracted=substr($instring, strpos($instring,$begin)+strlen($begin), $inputlen-$max );
		$outstring = $begin.$separator.$end;
		if (strlen($outstring) >= $inputlen) {  //Because "Fir(...)fox" is longer than "Firefox"
			$outstring = $instring;
		}
		//# use WordPress 2.x function attribute_escape and 1.2.x 
		//  function wp_specialchars to make malicious code 
		//  harmless when echoed to the screen
		$outstring=attribute_escape(wp_specialchars($outstring,ENT_QUOTES));
	} else {
		$outstring = attribute_escape(wp_specialchars($instring,ENT_QUOTES));
	}
	return $outstring;
} //end function stringShortener

//# Return a value of true if url argument is a root url and false when
//#  url constains a subdirectory path or query parameters...
//#  - Helene D. 2007
function url_rootcheck($urltocheck) {
	$isroot = false;
	//url must begin with 'http://'
	if (strncasecmp($urltocheck,'http://',7) == 0) {
		$isroot = true;
		$urlparts=parse_url($urltocheck);
		if (!empty($urlparts['path']) && $urlparts['path'] != "/") {
			$isroot=false;
		} elseif (!empty($urlparts['query'])) {
			$isroot=false;
		}
	}
	return $isroot;
}

//#from a page/post url input, output a url with "$blogurl" prepended for 
//#  blogs that have wordpress installed in a separate folder
//#  -Helene D. 1/22/08
function wAddSiteurl($inputurl) {
	$wpurl = rtrim(get_bloginfo('wpurl'),"/");
	$blogurl = rtrim(get_bloginfo('home'),"/");
	if (strcasecmp($blogurl, $wpurl) == 0) {
		$outputurl=$inputurl;
	} elseif (stristr($inputurl,$blogurl) === FALSE && url_rootcheck($blogurl))  {
		$outputurl=$blogurl."/".ltrim($inputurl,"/");
	} else {
		$outputurl=$inputurl;
	}
	$outputurl = rawurldecode(html_entity_decode($outputurl)); //dangerous
	$outputurl = wCleanURL($outputurl);	//safe
	return $outputurl;
}

//sanitize url of potentially dangerous code before display
function wCleanURL($url="") { 
	if (empty($url)) { 
		return;
	}
	//$urlstring = stripslashes($url);
	if (function_exists('esc_url')) {	//#WP 2.8+
		$cleaned_url = esc_url(stripslashes($url));
	} else {
		$cleaned_url = clean_url(stripslashes($url));
	}
	if (empty($cleaned_url)) {	//oops, clean_url chomp
		$cleaned_url = attribute_escape(stripslashes($url));
	}
	return $cleaned_url;
} //end function

//Output wassup records in Digg spy style...
function spyview ($from_date="",$to_date="",$rows="999",$spytype="",$spy_datasource="") {
	global $wpdb, $wp_version, $debug_mode;

	$whereis="";
	if ($spytype == 'spider') {
		$whereis = " AND spider!=''";
	} elseif ($spytype == 'nospider') {
		$whereis = " AND spider=''";
	} elseif ($spytype == 'spam') {
		$whereis = " AND spam>0";
	} elseif ($spytype == 'nospam') {
		$whereis = " AND spam=0";
	} elseif ($spytype == 'nospamspider') {
		$whereis = " AND spam=0 AND spider=''";
	} elseif ($spytype == 'searchengine') {
		$whereis = " AND searchengine!='' AND search!=''";
	} elseif ($spytype == 'referrer') {
		$whereis = " AND referrer!='' AND referrer NOT LIKE '%$wpurl%' AND searchengine='' AND search=''";
	} elseif ($spytype == 'comauthor') {
		$whereis = " AND comment_author!=''";
	} elseif ($spytype == 'loggedin') {
		$whereis = " AND username!=''";
	}
	//check for arguments...
	if(empty($to_date)) $to_date = wassup_get_time();
	if (empty($from_date)) $from_date = ($to_date - 5);
	if (empty($spy_datasource)) {
		//temp table is default data source unless not exists
		$spy_datasource = $wpdb->prefix . "wassup_tmp";
		if ($wpdb->get_var("SHOW TABLES LIKE '$spy_datasource'") != $spy_datasource) { 
			$spy_datasource = $wpdb->prefix . "wassup";
		}
	}

	if (function_exists('get_option')) {
		$wassup_settings = get_option('wassup_settings');
	}
	if (!empty($wassup_settings['wassup_screen_res'])) {
		$screen_res_size = (int) $wassup_settings['wassup_screen_res'];
	} else { 
		$screen_res_size = 670;
	}
	$max_char_len = ($screen_res_size)/10;
	//set smaller screen_res_size to make room for sidebar in WP2.7+
	if (version_compare($wp_version, '2.7', '>=')) { 
		$screen_res_size = $screen_res_size-160;
		$max_char_len = $max_char_len-16;
	}
	$wpurl = get_bloginfo('wpurl');
	$blogurl = get_bloginfo('home');
	$unclass = "sum-box";

	$qryC = $wpdb->get_results("SELECT id, wassup_id, `timestamp`, ip, hostname, searchengine, urlrequested, agent, referrer, spider, username, comment_author FROM $spy_datasource WHERE `timestamp` BETWEEN $from_date AND $to_date $whereis ORDER BY `timestamp` DESC");
	if (!empty($qryC)) {
		//restrict # of rows to display when needed...
		$row_count = 0;
	//display the rows...
	foreach ($qryC as $cv) {
		$unclass = "sum-box";
		if ( $row_count < (int)$rows ) {
		   $timestamp = $cv->timestamp;
		   $ip = @explode(",", $cv->ip);
		   if ($cv->referrer != '') {
		   	if ($cv->searchengine != "" || stristr($cv->referrer,$wpurl)!=$cv->referrer) { 
		   	if ($cv->searchengine == "") {
				$referrer = '<a href="'.wCleanURL($cv->referrer).'" target=_"BLANK"><span style="font-weight: bold;">'.stringShortener("{$cv->referrer}", round($max_char_len*.8,0)).'</span></a>';
		   	} else {
				 $referrer = '<a href="'.wCleanURL($cv->referrer).'" target=_"BLANK">'.stringShortener("{$cv->referrer}", round($max_char_len*.9,0)).'</a>';
		   	}
		   	} else { 
		   		$referrer = __('From your blog','wassup');
		   	}
		   } else {
		   	$referrer = __('Direct hit','wassup');
		   } 
		   // User is logged in or is a comment's author
		   if ($cv->username != "") {
		   	$unclass .= "-log";
		   	$map_icon = "marker_loggedin.png";
		   } elseif ($cv->comment_author != "") {
		   	$unclass .= "-aut";
			$map_icon = "marker_author.png";
		   } elseif ($cv->spider != "") {
		   	$unclass .= "-spider";
		   	$map_icon = "marker_bot.png";
		   } else {
		   	$map_icon = "marker_user.png";
		   }

		// Start getting GEOIP info
		   $location="";
		   $lat = 0;
		   $lon = 0;
		   if (function_exists('curl_init')) {
			//TODO: save geo data in 'wassup_tmp_geoloc' table
			//      so multi-page visits from save ip don't do
			//      redundant curl lookups
		   	$geo_url = "http://api.hostip.info/get_html.php?ip=".$ip[0]."&position=true";
		   	$ci = curl_init();

		   	curl_setopt($ci, CURLOPT_URL, $geo_url);
		   	curl_setopt($ci, CURLOPT_HEADER,0);
		   	curl_setopt($ci, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		   	@curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 0);
		   	curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);

		   	$data = curl_exec($ci);
		   	curl_close($ci);

	 	   	$data = explode("\n",$data);
		   	$loc_country = "";
			$loc_city = "";
			if (stristr("unknown", $data[0]) === FALSE) {
		   		$loc_country = preg_replace('/country: /i', "", $data[0]);
		   	}
		   	if (!empty($data[1]) && stristr("unknown", $data[1]) === FALSE) {
		   		$loc_city = preg_replace('/city: /i', '', $data[1]);
		   	}
			$geoloc = trim($loc_country." ".$loc_city);
			if ($debug_mode) {
				echo "<!--\n Curl data: \n"; //debug
				echo var_dump($data);	//debug
				echo "\n geoloc=$geoloc \n";	//debug
				echo "-->\n"; //debug
			}

		   	if ($wassup_settings['wassup_geoip_map'] == 1) {
		   		$gkey = $wassup_settings['wassup_googlemaps_key'];
		   	if ($geoloc != "") {
		   		$geocode = geocodeWassUp($geoloc, $gkey);
		   		if($geocode[0] != 200) {
		   			$lat = explode(":", $data[2]);
		   			$lat = $lat[1];
		      			$lon = explode(":", $data[3]);
		   			$lon = $lon[1];
		   		} else { 
		   			$lat = $geocode[2];
		   			$lon = $geocode[3];
		   		}
		   	}
		   	}
		   	$location = $data[0];
		   	if (!empty($data[1])) { 
				$location .= " - ".$data[1];
			}
		   	echo "<!-- heartbeat -->\n";
		   } //end if curl_init

	// Print the JS code to add marker on the map
	if ($wassup_settings['wassup_geoip_map'] == 1) {
		if ($lat!=0 && $lon!=0) {
		$item_id = $cv->id;
		$img_dir = WASSUPURL.'/img';
                echo "
                <script type=\"text/javascript\">
                var icon$item_id = new GIcon();
                icon$item_id.image = '".$img_dir."/".$map_icon."';
                icon$item_id.shadow = '$img_dir/shadow.png';
                icon$item_id.iconSize = new GSize(20.0, 34.0);
                icon$item_id.shadowSize = new GSize(38.0, 34.0);
                icon$item_id.iconAnchor = new GPoint(10.0, 17.0);
                icon$item_id.infoWindowAnchor = new GPoint(10.0, 17.0);
                var point = new GLatLng($lat,$lon);
                var marker$item_id = new GMarker(point, icon$item_id);
                map.addOverlay(marker$item_id);
                GEvent.addListener(marker$item_id, 'click', function() {
                marker$item_id.openInfoWindowHtml('<div style=\"white-space:nowrap\"><div class=\"bubble\">Ip: ".
			$ip[0]
			."<br />Hour: ".
			gmdate('H:i:s', $timestamp)
			."<br />Request: <a href=".
			wAddSiteurl($cv->urlrequested)
			." target=\"_BLANK\">".
			stringShortener($cv->urlrequested, round($max_char_len*.9,0))
			."</a><br />".
			"</div></div>');
                });
                map.panTo(new GLatLng($lat,$lon),3);
                </script>";
                } //end if $lat!=0
	} // end if wassup_geoip_map
?>
		<div class="sum-spy">
		<span class="<?php print $unclass; ?>">
		   	<?php print $ip[0]; ?></span>
		<div class="sum-det-spy"><span class="det1">
		<?php
			print '<a href="'.wAddSiteurl("{$cv->urlrequested}").'" target="_BLANK">';
			print stringShortener("{$cv->urlrequested}", round($max_char_len*.9,0)); ?>
		</a></span><br />
		<span class="det2"><strong><?php print gmdate("H:i:s", $timestamp); ?> - </strong>
		<?php 
		print $referrer;
		if (!empty($location)) {
			print "<br />".$location; 
		} ?>
		</span>
		</div>
		</div>
<?php
		} //end if row_count
		$row_count=$row_count+1;
		} //end foreach
	} else {
		//display "no activity" periodically so we know spy is running...
		if ((int)$to_date%59 == 0 ) {
			echo '<div class="sum-spy"><span class="det3">'.gmdate("H:i:s",$to_date).' - '.__("No visitor activity","wassup").' &nbsp; &nbsp; :-( &nbsp; </span></div>';
		}
	} //end if !empty($qryC)
} //end function spyview

// Geocoding location with Google Maps
function geocodeWassUp($location, $key) {
	//Three parts to the querystring: q is address, output is the format (
	$address = urlencode($location);
	$url = "http://maps.google.com/maps/geo?q=".$address."&output=csv&key=".$key;

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER,0);
	curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$data = curl_exec($ch);
	curl_close($ch);

	$data = explode(",",$data);
	if ($data[0] == 200) {
		return $data;

	} else {
		$error = $data[0];
		return $error;
	}
}

/* wGetStats- Return an associative array containing the top statistics 
 *  numbers of "stat_type" from wassup table. Associative array fields are 
 *  'top_count', 'top_item', and optionally, 'top_link', when data is url.
 *  Results are sorted in descending count order and known spam is 
 *  automatically excluded when spam check is enabled in 'Wassup Options'.
 * Input parameters are 'stat_type'=[name of any column in wassup table],
 * and 2 optional parameters: 
 *  stat_limit=N-- limits results to the top N values. Default=10.
 *  stat_condition='mysql where clause'-- usually a date range clause on 
 *  `timestamp`.  Defaults to 24 hours.
 * Used by action.php TopTen and wassup_widget to retrieve statistics data.
 * - Helene D. 2009-03-04
 */
function wGetStats($stat_type, $stat_limit=10, $stat_condition="") {
	global $wpdb, $debug_mode;

	$wassup_settings = get_option('wassup_settings');
	$top_ten = unserialize(html_entity_decode($wassup_settings['wassup_top10']));
	$wpurl =  get_bloginfo('wpurl');
	$blogurl =  get_bloginfo('home');
	$table_name = (!empty($wassup_settings['wassup_table']))? $wassup_settings['wassup_table'] : $wpdb->prefix . "wassup";
	$table_tmp_name = $table_name . "_tmp";

	if (empty($stat_limit) || !(is_numeric($stat_limit))) {
		$stat_limit=10;
	}
	if (empty($stat_condition)) {
		$to_date = wassup_get_time();
		$from_date = ((int)$to_date - 24*(60*60)); //24 hours
		$stat_condition = " `timestamp` >= $from_date ";
	}
	//exclude spam if it is being recorded
	if ($wassup_settings['wassup_spamcheck'] == 1) {
		$spam_condition = " AND spam=0";
	} else {
		$spam_condition = "";
	}
	$stat_condition .= $spam_condition; 

	//get the stats data
	//top search phrases...
	if ($stat_type == "searches") {
		$stat_results = $wpdb->get_results("SELECT count(search) AS top_count, search AS top_item, referrer AS top_link FROM $table_name WHERE $stat_condition AND search!='' AND spider='' GROUP BY search ORDER BY top_count DESC LIMIT $stat_limit");

	//top external referrers...
	} elseif ($stat_type == "referrers") {
		//exclude internal referrals
		$url = parse_url($blogurl);
		$sitedomain = $url['host'];
		$exclude_list = $sitedomain;
		if ($wpurl != $blogurl) {
			$url = parse_url($wpurl);
			$wpdomain = $url['host'];
			$exclude_list .= ",".$wpdomain;
		}
		//exclude external referrers
		if (!empty($top_ten['topreferrer_exclude'])) {
			$exclude_list .= ",".$top_ten['topreferrer_exclude'];
		}
		//create mysql conditional statement to exclude referrers
		$exclude_referrers = "";
		$exclude_array = array_unique(explode(",", $exclude_list));
		foreach ($exclude_array as $exclude_domain) {
			$exclude_domain = trim($exclude_domain);
			if ($exclude_domain != "" ) {
				$exclude_referrers .= " AND referrer NOT LIKE 'http://".$exclude_domain."%' AND referrer NOT LIKE 'http://www.".$exclude_domain."%'";
			}
		}
		$stat_results = $wpdb->get_results("SELECT count(*) AS top_count, LOWER(referrer) AS top_item, referrer AS top_link FROM $table_name WHERE $stat_condition AND referrer!='' AND search='' AND spider='' $exclude_referrers GROUP BY top_item ORDER BY top_count DESC LIMIT $stat_limit");

	//top url requests...
	} elseif ($stat_type == "urlrequested") {
		$stat_results = $wpdb->get_results("SELECT count(*) AS top_count, LOWER(REPLACE(urlrequested, '/', '')) AS top_group, LOWER(urlrequested) AS top_item, urlrequested AS top_link FROM $table_name WHERE $stat_condition GROUP BY top_group ORDER BY top_count DESC LIMIT $stat_limit");

	//top browser...
	} elseif ($stat_type == "browser") {
		$stat_results = $wpdb->get_results("SELECT count(*) AS top_count, SUBSTRING_INDEX(SUBSTRING_INDEX(browser, ' 0.', 1), '.', 1) AS top_item FROM $table_name WHERE $stat_condition AND `browser`!='' AND `browser` NOT LIKE 'N/A%' AND `spider`='' GROUP BY top_item ORDER BY top_count DESC LIMIT $stat_limit");

	//top os...
	} elseif ($stat_type == "os") {
		$stat_results = $wpdb->get_results("SELECT count(os) as top_count, `os` AS top_item FROM $table_name WHERE $stat_condition AND `os`!='' AND `os` NOT LIKE 'N/A%' AND spider='' GROUP BY top_item ORDER BY top_count DESC LIMIT $stat_limit");

	//top language/locale..
	} elseif ($stat_type == "language" || $stat_type == "locale") {
		$stat_results = $wpdb->get_results("SELECT count(LOWER(language)) as top_count, LOWER(language) as top_item FROM $table_name WHERE $stat_condition AND language!='' AND spider='' GROUP BY top_item ORDER BY top_count DESC LIMIT $stat_limit");

	} else {
		//TODO: check that wp_wassup.$stat_type column exist and is char
		if (!empty($stat_type)) {
			$stat_results = $wpdb->get_results("SELECT count($stat_type) AS top_count, `$stat_type` AS top_item FROM $table_name WHERE $stat_condition AND `$stat_type`!='' AND `$stat_type` NOT LIKE 'N/A%' GROUP BY `$stat_type` ORDER BY top_count DESC LIMIT $stat_limit");
		}
	}

	if (!empty($stat_results[0]->top_count)) {
		return $stat_results;
	} else { 
		return array();
	}
} //end function wGetStats

function backup_wassup($savefile="") {	//untested
	global $wpdb, $wassup_options;
	//# save to server file, $savefile - alternate method to export wassup table.
	//#   Useful when browser keeps timing out during export. -Helene D. 2009-03-16
	if (!empty($savefile)) {
		$savedir = dirname($savefile);
	} else {
		$savefile="wassup_backup.php";
		$savedir = "";
	}
	//use web root or home directory for backup, if full path not specified
	if ($savedir == "" || preg_match('#^/#',$savefile)==0) { 
		if (!empty($_ENV['DOCUMENT_ROOT'])) {
			$savedir = $_ENV['DOCUMENT_ROOT'];
			$savefile = rtrim($_ENV['DOCUMENT_ROOT'],'/').'/'.$savefile;
		} elseif (!empty($_ENV['HOME'])) {
			$savedir = $_ENV['HOME'];
			$savefile = rtrim($_ENV['HOME'],'/').'/'.$savefile;
		}
	}
	if (!$wassup_options->isWritableFolder($savedir)) {
		//unable to save to file
		$wassup_options->wassup_alert_message = "ERROR: Unable to Save file to $savedir";
		$wassup_option->saveOptions();
		return false;
	} else {
		$records = $wpdb->get_results("SELECT * FROM $table_name");
		$wassup_rec = array_map('stripslashes',$records);
		$fb = @fopen($savefile,'w');
		if (!$fb) { 
			$wassup_options->wassup_alert_message = "ERROR: Unable to backup to file, $savefile";
			$wassup_option->saveOptions();
			return false;
		} else { 
			$bytes=0;
			foreach ($records as $record) {
				$wassup_data=sprintf("INSERT INTO `wp_wassup` (`wassup_id`, `timestamp`, `ip`, `hostname`, `urlrequested`, `agent`, `referrer`, `search`, `searchpage`, `os`, `browser`, `language`, `screen_res`, `searchengine`, `spider`, `feed`, `username`, `comment_author`, `spam`) VALUES '%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s'\n", mysql_real_escape_string($record->wassup_id),
			mysql_real_escape_string($record->timestamp),
			mysql_real_escape_string($record->ip),
			mysql_real_escape_string($record->hostname),
			mysql_real_escape_string($record->urlrequested),
			mysql_real_escape_string($record->agent),
			mysql_real_escape_string($record->referrer),
			mysql_real_escape_string($record->search),
			mysql_real_escape_string($record->searchpage),
			mysql_real_escape_string($record->os),
			mysql_real_escape_string($record->browser),
			mysql_real_escape_string($record->language),
			mysql_real_escape_string($record->screen_res),
			mysql_real_escape_string($record->searchengine),
			mysql_real_escape_string($record->spider),
			mysql_real_escape_string($record->feed),
			mysql_real_escape_string($record->username),
			mysql_real_escape_string($record->comment_author),
			mysql_real_escape_string($record->spam));

				$bytes += fwrite($fb, $wassup_data);
			} //end foreach
			fclose($fb);
			$wassup_options->wassup_alert_message = "$bytes Wassup data bytes saved file to $savefile";
			$wassup_option->saveOptions();
			return $bytes;
		} //end else fb
	} //end else wassup_options
} //end function

// How many digits have an integer
function digit_count($n, $base=10) {

  if($n == 0) return 1;

  if($base == 10) {
    # using the built-in log10(x)
    # might be more accurate than log(x)/log(10).
    return 1 + floor(log10(abs($n)));
  }else{
    # here  logB(x) = log(x)/log(B) will have to do.
   return 1 + floor(log(abs($n))/ log($base));
  }
}

//Round the integer to the next near 10
function roundup($value) {
	$dg = digit_count($value);
	if ($dg <= 2) {
		$dg = 1;
	} else {
		$dg = ($dg-2);
	}
	return (ceil(intval($value)/pow(10, $dg))*pow(10, $dg)+pow(10, $dg));
}

function chart_data($Wvisits, $pages=null, $atime=null, $type, $charttype=null, $axes=null, $chart_type=null) {
	global $debug_mode;
	$chartAPIdata = false;
// Port of JavaScript from http://code.google.com/apis/chart/
// http://james.cridland.net/code
   // First, find the maximum value from the values given
   if ($axes == 1) {
	$maxValue = roundup(max(array_merge($Wvisits, $pages)));
	//$maxValue = roundup(max($Wvisits));
	$halfValue = ($maxValue/2); 
	$maxPage = $maxValue;
   } else {
	$maxValue = roundup(max($Wvisits));
	$halfValue = ($maxValue/2);
	$maxPage = roundup(max($pages));
	$halfPage = ($maxPage/2);
   }

   // A list of encoding characters to help later, as per Google's example
   $simpleEncoding = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

   $chartData = "s:";

	// Chart type has two datasets
	if ($charttype == "main") {
		$label_time = "";
		for ($i = 0; $i < count($Wvisits); $i++) {
			$currentValue = $Wvisits[$i];
			$currentTime = $atime[$i];
			if ($chart_type == "dashboard") {
				$label_time="|";
			} else {
				$label_time.=str_replace(' ', '+', $currentTime)."|";
			}
     
			if ($currentValue > -1) {
				$chartData.=substr($simpleEncoding,61*($currentValue/$maxValue),1);
			} else {
				$chartData.='_';
			}
		} 
		// Add pageviews line to the chart
		if (count($pages) != 0) {
			$chartData.=",";
			for ($i = 0; $i < count($pages); $i++) {
				$currentPage = $pages[$i];
				$currentTime = $atime[$i];
     
				if ($currentPage > -1) {
					$chartData.=substr($simpleEncoding,61*($currentPage/$maxPage),1);
				} else {
					$chartData.='_';
				}
			}
		}
		// Return the chart data - and let the Y axis to show the maximum value
   		if ($axes == 1) {
			$chartAPIdata=$chartData."&chxt=x,y&chxl=0:|".$label_time."1:|0|".$halfValue."|".$maxValue."&chxs=0,6b6b6b,9";
		} else {
			$chartAPIdata=$chartData."&chxt=x,y,r&chxl=0:|".$label_time."1:|0|".$halfValue."|".$maxValue."|2:|0|".$halfPage."|".$maxPage."&chxs=0,6b6b6b,9";
		}
	
	// Chart type has one one dataset
	// It's unused now
	} else {
		for ($i = 0; $i < count($Wvisits); $i++) {
			$currentValue = $Wvisits[$i];
			$currentTime = $atime[$i];
			$label_time.=str_replace(' ', '+', $currentTime)."|";

			if ($currentValue > -1) {
				$chartData.=substr($simpleEncoding,61*($currentValue/$maxValue),1);
			} else {
				$chartData.='_';
			}
		}
		$chartAPIdata=$chartData."&chxt=x,y&chxl=0:|".$label_time."|1:|0|".$halfValue."|".$maxValue."&chxs=0,6b6b6b,9";
	}
	return $chartAPIdata;

} //end function

// Used to show main visitors details query, to count items and to extract data for main chart
class MainItems {
	// declare variables
        var $tableName;
        var $from_date;
        var $to_date;
        var $searchString;
        var $whereis;
        var $ItemsType;
        var $Limit;
        var $Last;
	var $WpUrl;

	/* Constructor */
	function mainitems($table_name,$date_from,$date_to,$whereis=null,$limit=null) {
		global $wpdb;
		$this->tableName = $table_name;
		$this->from_date = $date_from;
		$this->to_date = $date_to;
		$this->whereis = $whereis;
		$this->limit = $limit;
	}
	/* Methods */
	// Function to show main query and count items
        function calc_tot($Type, $Search="", $specific_where_clause=null, $distinct_type=null) {
		global $wpdb, $wassup_options, $debug_mode;

                $this->ItemsType = $Type;
		$this->searchString = $Search;
		$ss = "";
		if (!empty($Search) || !empty($specific_where_clause)) {
			$ss = $this->buildSearch($Search,$specific_where_clause);
		}

		// Switch by every (global) items type (visits, pageviews, spams, etc...)
                switch ($Type) {
                        // This is the MAIN query to show the chronology
		case "main":
			//## Extend mysql wait timeout to 2.5 minutes and extend
			//#  php script timeout to 3 minutes to prevent script
			//#  hangs with large tables on slow server.
			if (!ini_get('safe_mode')) @set_time_limit(3*60);
			$results = $wpdb->query("SET wait_timeout = 160");

			//TODO: use a subquery for MySQL 5+
			//main query
			//  - retrieve one row per wassup_id with timestamp = max(timestamp) (ie. latest record)
			// "sql_buffer_result" select option helps in cases where it takes a long time to retrieve results.  -Helene D. 2/29/09
			$qry = sprintf("SELECT SQL_BUFFER_RESULT *, max(`timestamp`) as max_timestamp, count(wassup_id) as page_hits FROM %s WHERE `timestamp` >= %s %s %s GROUP BY wassup_id ORDER BY max_timestamp DESC %s",
				$this->tableName,
				$this->from_date, 
				$ss,
				$this->whereis,
				$this->Limit);
			$results = $wpdb->get_results($qry);
			//return $results;
			break;
		case "count":
			// These are the queries to count the items hits/pages/spam
			$qry = sprintf("SELECT COUNT(%s `wassup_id`) AS itemstot FROM %s WHERE `timestamp` >= %s %s %s",
					$distinct_type,
					$this->tableName,
					$this->from_date,
					$ss,
					$this->whereis);
			$results = $wpdb->get_var($qry);
			//$itemstot = $wpdb->get_var($qry);
			//return $itemstot;
			break;
		} //end switch
		if (!empty($results)) {
			return $results;
		} else {
			return false;
		}
	} //end function calc_tot

	// $Ctype = chart's type by time
	// $Res = resolution
	// $Search = string to add to where clause
        function TheChart($Ctype, $Res, $chart_height, $Search="", $axes_type, $chart_bg, $chart_type=null) {
		global $wpdb, $debug_mode;

		$mysqlversion=substr(mysql_get_server_info(),0,3);
		$this->searchString = $Search;
		$this->Last = $Ctype;

		//# MySql 'FROM_UNIXTIME' converts a UTC timestamp to a
		//#  local datetime based on the server host timezone(TZ).
		//#  Wassup's 'timestamp' is neither UTC nor server host
		//#  time. It is Wordpress time and must be converted to
		//#  UTC minus the TZ difference between the server host
		//#  and Wordpress time offset to get an accurate output 
		//#  from 'FROM_UNIXTIME'
		$WPoffset = (int)(get_option("gmt_offset")*60*60);
		$UTCoffset = $WPoffset + ((int)date('Z') - $WPoffset);
		//
		//#for US/Euro date display: USA Timezone=USA date format.
		//TODO: Use date format in Wordpress to determine x-axis format
		if (in_array(date('T'), array("ADT","AST","AKDT","AKST","CDT","CST","EDT","EST","HADT","HAST","MDT","MST","PDT","PST"))) { 
			$USAdate = true;
		} else {
			$USAdate = false;
		}
		// Options by chart type
		switch ($Ctype) {
		case .4:
			$label = __("Last 6 Hours", "wassup");
			$strto = "6 hours";
			$Ctimeformat = "%H";
			$x_axes_label = "%H:00";
			break;
		case 7:
			$label = __("Last 7 Days", "wassup");
			$strto = "7 days";
			$Ctimeformat = "%d";
			if ($USAdate) { $x_axes_label = "%a %b %d"; }
			else { $x_axes_label = "%a %d %b"; }
			break;
		case 30:
			$label = __("Last Month", "wassup");
			$strto = "30 days";
			$Ctimeformat = "%d";
			if ($USAdate) { $x_axes_label = " %b %d"; }
			else { $x_axes_label = "%d %b"; }
			break;
		case 90:
			$label = __("Last 3 Months", "wassup");
			$strto = "3 months";
			$Ctimeformat = "%u";
			$x_axes_label = "%b, wk-%u";
			break;
		case 180:
			$label = __("Last 6 Months", "wassup");
			$strto = "6 months";
			$Ctimeformat = "%m";
			$x_axes_label = " %b %Y";
			break;
		case 365:
			$label = __("Last Year", "wassup");
			$strto = "12 months";
			$Ctimeformat = "%m";
			$x_axes_label = "%b %Y";
			break;
		case 0:
			$label = __("All Time", "wassup");
			$strto = "";
			$Ctimeformat = "%m";
			$x_axes_label = "%b %Y";
			break;
		case 1:
		default:
			$label = __("Last 24 Hours", "wassup");
			$strto = "24 hours";
			$Ctimeformat = "%H";
			$x_axes_label = "%H:00";
		}

		// Add Search variable to WHERE clause
		$ss = $this->buildSearch($Search);

                $hour_todate = $this->to_date;
		if ($strto != "") {
                	$hour_fromdate = strtotime("-".$strto, $hour_todate);
		} else {
                	$hour_fromdate = 0;
		}
		//if ($hour_fromdate == "") $hour_fromdate = strtotime("-24 hours", $hour_todate);

		$qry = sprintf("SELECT COUNT(DISTINCT `wassup_id`) as items, COUNT(`wassup_id`) as pages, DATE_FORMAT(FROM_UNIXTIME(CAST((`timestamp` - %s) AS UNSIGNED)), '%s') as thedate FROM %s WHERE `timestamp` > %s %s %s GROUP BY DATE_FORMAT(FROM_UNIXTIME(CAST((`timestamp` - %s) AS UNSIGNED)), '%s') ORDER BY `timestamp`",
			$UTCoffset,
			$x_axes_label,
			$this->tableName,
			$hour_fromdate, 
			$this->whereis,
			$ss, 
			$UTCoffset,
			$Ctimeformat);
                $aitems = $wpdb->get_results($qry,ARRAY_A);
		// Extract arrays for Visits, Pages and X_Axis_Label
		if (count($aitems) > 0) {
			foreach ($aitems as $bhits) {
                		$ahits[] = $bhits['items'];
	                	$apages[] = $bhits['pages'];
				$atime[] = $bhits['thedate'];
                	}
			// return the url to the google chart image 
			$chart_url ="http://chart.apis.google.com/chart?chf=".$chart_bg."&chtt=".urlencode($label)."&chls=4,1,0|2,6,2&chco=2683ae,FF6D06&chm=B,2683ae30,0,0,0&chg=10,20,2,5&cht=lc&chs=".$Res."x".$chart_height."&chd=".chart_data($ahits, $apages, $atime, $Ctimeformat, "main", $axes_type, $chart_type);
		}
		if (!empty($chart_url)) {
			return $chart_url;
		} else {
			return false;
		}
	} //end function theChart

	//  buildSearch() added to protect against Sql injection code 
	//  in user-input parameter: "Search".  -Helene D. 2/27/09
	function buildSearch($Search,$specific_where_clause=null) {
		global $wpdb;
		$ss="";
		//create the Search portion of a MySql WHERE clause 
		if (!empty($Search)) {
			//escape chars that have special meaning in mysql 'like' [%\]
			if (function_exists('like_escape')) {	//WP 2.5+ function
                		$searchString = like_escape(trim($Search));
			} else {
				$searchString = str_replace(array("%", "_"), array("\\%", "\\_"), trim($Search));
			}
			$searchParam = mysql_real_escape_string($searchString);

			// Create the Search portion of MySQL WHERE clause
			$ss = sprintf(" AND (`ip` LIKE '%%%s%%' OR `hostname` LIKE '%%%s%%' OR `urlrequested` LIKE '%%%s%%' OR `agent` LIKE '%%%s%%' OR `referrer` LIKE '%%%s%%' OR `username` LIKE '%s%%' OR `comment_author` LIKE '%s%%')",
				$searchParam,
				$searchParam,
				$searchParam,
				$searchParam,
				$searchParam,
				$searchParam,
				$searchParam);
		} //if $Search
		if (!empty($specific_where_clause)) {
			$ss .= " ".trim($specific_where_clause);
		}
		return $ss;
	} //end function buildSearch

} //end class mainItems

// Class to check if a previous comment with a specific IP was detected as SPAM by Akismet default plugin
class CheckComment {
        var $tablePrefix;

	function isSpammer ($authorIP) {
		global $wpdb;
	        $spam_comment = $wpdb->get_var("SELECT COUNT(comment_ID) AS spam_comment FROM ".$this->tablePrefix."comments WHERE comment_author_IP='$authorIP' AND comment_approved='spam'");
		return $spam_comment;
	}
}
