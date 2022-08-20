<?php
/**
 * Outputs an RSS feed of information, mostly based on the information available
 * in the index page.
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 - 2012 Genmod Development Team
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @package Genmod
 * @subpackage RSS
 * @version $Id: rss.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (isset($_SESSION["CLANGUAGE"])) $oldlang = $_SESSION["CLANGUAGE"];
else $oldlang = "english";
if (!empty($lang)) {
	$changelanguage = "yes";
	$NEWLANGUAGE = $lang;
}

/**
 * Inclusion of the RSS functions
*/
require("includes/functions/functions_rss.php");

/**
 * Inclusion of the configuration file
*/
require("config.php");
if (empty($rssStyle)){
	$rssStyle = GedcomConfig::$RSS_FORMAT;
}
if (empty($rssStyle) || ($rssStyle != "HTML" && $rssStyle != "JS" && $rssStyle != "MBOX")) {
	header("Content-Type: application/xml; charset=utf-8");
} else {
	header('Content-Type: text/html; charset=utf-8');
}

if (!isset($_SERVER['QUERY_STRING'])) $_SERVER['QUERY_STRING'] = "lang=".$LANGUAGE;

$user =& User::GetInstance(GedcomConfig::$CONTACT_EMAIL);
$author =$user->firstname." ".$user->lastname;

$rss = new UniversalFeedCreator();
$rss->title = $GEDCOMS[GedcomConfig::$GEDCOMID]["title"];
$rss->description = str_replace("#GEDCOM_TITLE#", $GEDCOMS[GedcomConfig::$GEDCOMID]["title"], GM_LANG_rss_descr);

//optional
$rss->descriptionTruncSize = 500;
$rss->descriptionHtmlSyndicated = true;
$rss->cssStyleSheet="";

$rss->link = SERVER_URL;
$syndURL = SERVER_URL."rss.php?".$_SERVER['QUERY_STRING'];
$syndURL = preg_replace("/&/", "&amp;", $syndURL);
$rss->syndicationURL = $syndURL;

$image = new FeedImage();
$image->title = GM_LANG_rss_logo_descr;
$image->url = SERVER_URL."images/gedcom.gif";
$image->link = "https://www.sourceforge.net/projects/genmod";
$image->description = GM_LANG_rss_logo_descr;

//optional
$image->descriptionTruncSize = 500;
$image->descriptionHtmlSyndicated = true;

$rss->image = $image;

// determine if to show parts of feed based on their exsistance in the blocks on index.php
$printTodays = false;
$printUpcoming = false;
$printGedcomStats = false;
$printGedcomNews = false;
$printTop10Surnames = false;
$printRecentChanges = false;

// First try to retrieve the block config from the database
$bconfig = "";
$blocks = new Blocks("gedcom");
foreach ($blocks->main as $order => $blockdata) {
	if ($blockdata[0] == "print_RSS_block") {
		$bconfig = $blockdata[1];
		break;
	}
}
if (empty($bconfig)) {
	foreach ($blocks->right as $order => $blockdata) {
		if ($blockdata[0] == "print_RSS_block") {
			$bconfig = $blockdata[1];
			break;
		}
	}
}

// If empty, get it from the RSS block
if (empty($bconfig) || count($bconfig) == 0) {
	require("blocks/rss_block.php");
	$bconfig = $GM_BLOCKS["print_RSS_block"]["config"];
}	

if((empty($module) || $module == "print_gedcom_news") && (isset($bconfig["print_gedcom_news"]) && $bconfig["print_gedcom_news"] == "yes")) {
	$gedcomNews = getGedcomNews();

	$numElements = count($gedcomNews); //number of news items
	for($i=0; $i < $numElements; $i++) {
		$newsItem = $gedcomNews[$i];
		if (! empty($newsItem[1])) {
			$item = new FeedItem();
			$item->title = $newsItem[0];
			//$item->link = SERVER_URL . "index.php?command=gedcom#" . $newsItem[0];
			$item->link = "index.php?gedid=".GedcomConfig::$GEDCOMID."&amp;command=gedcom#" . $newsItem[3];
			$item->description = $newsItem[2];

			//optional
			$item->descriptionTruncSize = 500;
			$item->descriptionHtmlSyndicated = true;

			$item->date = $newsItem[1];
			$item->source = SERVER_URL ;
			$item->author = $author;
			$rss->addItem($item);
		}
	}
}

if((empty($module) || $module == "print_gedcom_stats") && (isset($bconfig["print_gedcom_stats"]) && $bconfig["print_gedcom_stats"] == "yes")) {
	$gedcomStats = getGedcomStats();
	if (! empty($gedcomStats[2])) {
		$item = new FeedItem();
		$item->title = $gedcomStats[0];
		//$item->link = SERVER_URL. "index.php?command=gedcom";
		$item->link = "index.php?gedid=".GedcomConfig::$GEDCOMID."&amp;command=gedcom#gedcom_stats";
		$item->description = $gedcomStats[2];

		//optional
		$item->descriptionTruncSize = 500;
		$item->descriptionHtmlSyndicated = true;

		if (! empty($gedcomStats[1])) {
		$item->date = $gedcomStats[1];
		}
		$item->source = SERVER_URL;
		$item->author = $author;

		$rss->addItem($item);
	}
}

if((empty($module) || $module == "print_todays_events") && (isset($bconfig["print_todays_events"]) && $bconfig["print_todays_events"] == "yes")) {
	$todaysEvents = getTodaysEvents();
	if (! empty($todaysEvents[2])) {
		$item = new FeedItem();
		$item->title = $todaysEvents[0];
		$item->link = "calendar.php?link=15&amp;gedid=".GedcomConfig::$GEDCOMID."&amp;action=today";
		$item->description = $todaysEvents[2];

		//optional
		$item->descriptionTruncSize = 500;
		$item->descriptionHtmlSyndicated = true;

		$item->date = $todaysEvents[1];
		$item->source = SERVER_URL;
		$item->author = $author;
		$rss->addItem($item);
	}
}

if((empty($module) || $module == "print_upcoming_events") && (isset($bconfig["print_upcoming_events"]) && $bconfig["print_upcoming_events"] == "yes")) {
	$upcomingEvent = getUpcomingEvents();
	if (! empty($upcomingEvent[2])) {
		$item = new FeedItem();
		$item->title = $upcomingEvent[0];
		$item->link = "calendar.php?link=16&amp;gedid=".GedcomConfig::$GEDCOMID."&amp;action=calendar";
		$item->description = $upcomingEvent[2];

		//optional
		$item->descriptionTruncSize = 500;
		$item->descriptionHtmlSyndicated = true;

		$item->date = $upcomingEvent[1];
		$item->source = SERVER_URL;
		$item->author = $author;

		$rss->addItem($item);
	}
}

if((empty($module) || $module == "print_block_name_top10") && (isset($bconfig["print_block_name_top10"]) && $bconfig["print_block_name_top10"] == "yes")) {
	$top10 = getTop10Surnames();
	if (! empty($top10[2])) {
		$item = new FeedItem();
		$item->title = $top10[0];
		$item->link = "indilist.php?show_all=yes&amp;surname_sublist=yes&amp;gedid=".GedcomConfig::$GEDCOMID;
		$item->description = $top10[2];

		//optional
		$item->descriptionTruncSize = 500;
		$item->descriptionHtmlSyndicated = true;

		if (! empty($top10[1])) {
			$item->date = $top10[1];
		}
		$item->source = SERVER_URL;
		$item->author = $author;

		$rss->addItem($item);
	}
}

if((empty($module) || $module == "print_recent_changes") && (isset($bconfig["print_recent_changes"]) && $bconfig["print_recent_changes"] == "yes")) {
	$recentChanges = getRecentChanges();
	if (! empty($recentChanges[2])) {
		$item = new FeedItem();
		$item->title = $recentChanges[0];
		$item->link = "indilist.php?gedid=".GedcomConfig::$GEDCOMID;
		$item->description = $recentChanges[2];

		//optional
		$item->descriptionTruncSize = 500;
		$item->descriptionHtmlSyndicated = true;

		if (! empty($recentChanges[1])) {
			$item->date = $recentChanges[1];
		}
		$item->source = SERVER_URL;
		$item->author = $author;

		$rss->addItem($item);
	}
}


// valid format strings are: RSS0.91, RSS1.0, RSS2.0, MBOX, OPML, ATOM, ATOM0.3, HTML, JS
if (empty($rssStyle)) $rssStyle = "RSS1.0"; //default to RDF - rss 1.0

echo $rss->createFeed($rssStyle);

 //-- preserve the old language by storing it back in the session
$_SESSION['CLANGUAGE'] = $oldlang;
@session_destroy();
?>