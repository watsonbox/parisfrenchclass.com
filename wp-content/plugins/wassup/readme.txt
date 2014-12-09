=== Plugin Name ===
Contributors: michelem, helened
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=michele%40befree%2eit&item_name=WassUp&no_shipping=0&no_note=1&tax=0&currency_code=EUR&lc=IT&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: tracker, tracking, statistics, analyze, web, realtime, stats, ajax, visitors, visits, online users, details, seo, admin, spy, visitors, widgets, widget, sidebar, monitor, webmaster, tool
Requires at least: 2.2
Tested up to: 2.8.3 - 2.7.* - 2.6.* - 2.5.* - 2.3.* - 2.2.3 - 2.2.2
Stable tag: 1.7.2.1

Analyze your visitors traffic with real time statistics, chart, a lot of chronological information and a sidebar Widget.

== Description ==

Disclaimer: Use at your own risk. No warranty expressed or implied is provided.

WassUp is a new Wordpress plugin to track your visitors in real time. It has a very readable and fancy admin console to keep tracks of your blog's users visits.
It has a "current visitors online" view and a more detailed "visitors details" view where you can know almost everything your users are doing on your blog, it's very useful for SEO or statistics maniacs. Now it comes with a new "Spy" view in Ajax like the Digg Spy.
The aim of WassUp is the knowledge of what your visitors do when they surf your blog, it is not intended to show grouped statistics for periods like visitors per day, pageviews per months and so on (there are many others tools to better gain that, like Google Analytics). With WassUp you'll get a cronology of your blog's visits with a lot of details for each single user session.

WassUp works with two anti-spam function to detect and skip (if you want) referers spammers and akismet spammers.

For people with database space problem, WassUp has some options to manage his database table, you can empty it or delete old records to prevent reaching the size limit.

It comes with a nice sidebar Widget which shows current visitors online, last searched terms and last external referers. The widget is fully customizable.

WassUp shows a lot of data from who is visiting your blog like:

 - ip / hostname
 - referer
 - spider
 - search engines used
 - keywords
 - SERP (search engine result page)
 - operating system / language / browser
 - pages viewed (chronologically and per user session)
 - complete user agent
 - name of user logged in
 - name of comment's author
 - top ten charts with aggregate data (top queries, requests, os, browsers)

The admin console is very nice and you can customize it by:

 - records by date range
 - records per page
 - records per entry type (spider, users logged in, comment's authors, search engine, referer)
 - expand/collapse informations (with ajax support)

There are 4 views:

 - Spy view like Digg Spy
 - Current Online view
 - Details view
 - Options to customize WassUp

Many options are customizable:

 - Refreshing minutes
 - details width
 - Users levels required
 - Enable/Disable recording
 - Record or not users logged in
 - Record or not spiders and bots
 - Record or not exploit attempts (libwww-perl user agent)
 - IPs to exclude from recording
 - Alert admin for table growth

== Frequently Asked Questions ==

http://www.wpwp.org

== Screenshots ==

You could find some screenshots at http://www.wpwp.org

== Installation ==

Installation:

- Download the plugin WassUp (Real Time Visitors Tracking)
- uncompress it with your preferred unzip/untar program or use the command line: tar xzvf wassup.tar.gz
- copy the directory wassup in your plugins directory at your wordpress blog (/wp-content/plugins)
- activate the WassUp Wordpress plugin at your Plugins admin page

Upgrading: 

- Disable the WassUp plugin
- Delete totally the directory "wassup" in your plugins dir
- Download and unzip the new WassUp file into the plugins dir
- Enable the WassUp plugin

== Usage ==

Usage:

When you activate (as described in "Installation") the plugin, it works "as is". You don't have anything to do. Wait your visitors hit your blog and start seeing details (click the dashboard and go to WassUp page)

Important Note: WassUp is incompatible with page-based caching plugins such as "Super-Cache". 

== Infos ==


You could find every informations and much more at http://www.wpwp.org - http://trac.wpwp.org - http://www.wpwp.org/forums

Credits to: Jquery ( http://www.jquery.com ) for the amazing Ajax framework, FAMFAMFAM ( http://www.famfamfam.com/ ) for the flags icons and a big thanks to Helene D. ( http://www.techfromhel.com/ ) for her help to improve WassUp!
