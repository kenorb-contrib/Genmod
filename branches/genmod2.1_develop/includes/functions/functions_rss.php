<?php
/**
 * Various functions used to generate the Genmod RSS feed.
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 - 2008 Genmod Development Team
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
 * @version $Id: functions_rss.php 29 2022-07-17 13:18:20Z Boudewijn $
 * @package Genmod
 * @subpackage RSS
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

require("config.php");

if (isset($_SESSION["timediff"])) $time = time()-$_SESSION["timediff"];
else $time = time();
$day = date("j", $time);
$month = date("M", $time);
$year = date("Y", $time);

$GM_BLOCKS["print_recent_changes"]["config"] = array("days"=>30, "hide_empty"=>"no", "show_indi"=>"yes", "show_fam"=>"yes", "show_sour"=>"yes", "show_repo"=>"yes", "show_obje"=>"yes");
$GM_BLOCKS["print_upcoming_events"]["config"] = array("days"=>30, "filter"=>"all", "onlyBDM"=>"no");


/**
 * Returns an ISO8601 formatted date used for the RSS feed
 *
 * @param $time the time in the UNIX time format (milliseconds since Jan 1, 1970)
 * @return SO8601 formatted date in the format of 2005-07-06T20:52:16+00:00
 */
function iso8601_date($time) {
	$tzd = date('O',$time);
	$tzd = $tzd[0] . str_pad((int) ($tzd / 100), 2, "0", STR_PAD_LEFT) .
				   ':' . str_pad((int) ($tzd % 100), 2, "0", STR_PAD_LEFT);
	$date = date('Y-m-d\TH:i:s', $time) . $tzd;
	return $date;
}

/**
 * Returns the upcoming events array used for the RSS feed
 *
 * @return the array with upcoming events data. the format is $dataArray[0] = title, $dataArray[1] = date,
 * 				$dataArray[2] = data
 * @TODO does not pick up the upcoming events block config and always shows 30 days of data.
 */
function getUpcomingEvents() {
	global $month, $year, $day, $monthtonum, $HIDE_LIVE_PEOPLE, $command, $TEXT_DIRECTION, $monthstart;
	global $GM_IMAGES, $ASC, $IGNORE_FACTS, $IGNORE_YEAR, $LAST_QUERY, $GM_BLOCKS;
	global $lastcachedate;
	global $CIRCULAR_BASE;

	$dataArray[0] = GM_LANG_upcoming_events;
	if (!isset($lastcachedate)) $lastcachedate = GedcomConfig::GetAllLastCacheDates();

	if (!isset($lastcachedate)) $lastcachedate = GedcomConfig::GetAllLastCacheDates();
	if (is_array($lastcachedate) && $lastcachedate["gc_last_upcoming"] != 0) $dataArray[1] = iso8601_date($lastcachedate["gc_last_upcoming"]);
	else $dataArray[1] = iso8601_date(time());

	if (empty($config)) $config = $GM_BLOCKS["print_upcoming_events"]["config"];
//	if (!isset(GedcomConfig::$DAYS_TO_SHOW_LIMIT)) GedcomConfig::$DAYS_TO_SHOW_LIMIT = 30;
	if (isset($config["days"])) $daysprint = $config["days"];
	else $daysprint = 30;
	if (isset($config["filter"])) $filter = $config["filter"];  // "living" or "all"
	else $filter = "all";
	if (isset($config["onlyBDM"])) $onlyBDM = $config["onlyBDM"];  // "yes" or "no"
	else $onlyBDM = "no";

	if ($daysprint < 1) $daysprint = 1;
	if ($daysprint > GedcomConfig::$DAYS_TO_SHOW_LIMIT) $daysprint = GedcomConfig::$DAYS_TO_SHOW_LIMIT;  // valid: 1 to limit

	$skipfacts = "CHAN,BAPL,SLGC,SLGS,ENDL";	// These are always excluded

	$daytext = "<ul>";
	$action = "upcoming";

	$found_facts = BlockFunctions::GetCachedEvents($action, $daysprint, $filter, "no", $skipfacts);

	$OutputDone = false;
	$PrivateFacts = false;
	$lastgid="";
	// Cache the selected indi's and fams in the indilist and famlist
	$selindi = array();
	$selfam = array();
	foreach($found_facts as $key=>$factarr) {
		if ($factarr[2] == "INDI") $selindi[] = $factarr[0];
		if ($factarr[2] == "FAM") $selfam[] = $factarr[0];
	}
	
	$selindi = implode("[".GedcomConfig::$GEDCOMID."]','", $selindi);
	$selindi .= "[".GedcomConfig::$GEDCOMID."]'";
	$selindi = "'".$selindi;
	ListFunctions::GetIndiList("no", $selindi);
	$selfam = implode("[".GedcomConfig::$GEDCOMID."]','", $selfam);
	$selfam .= "[".GedcomConfig::$GEDCOMID."]'";
	$selfam = "'".$selfam;
	ListFunctions::GetFamList("no", $selfam);
	foreach($found_facts as $key=>$factarr) {
		$datestamp = $factarr[3];
		if ($datestamp >= $monthstart && $datestamp <= ($monthstart+(60*60*24*$daysprint))) {
			if ($factarr[2] == "INDI") {
				$person =& Person::GetInstance($factarr[0], "", GedcomConfig::$GEDCOMID);
				$fact = new Fact($factarr[0], $factarr[2], GedcomConfig::$GEDCOMID, $factarr[6], $factarr[1]);
				$factrec = $factarr[1];
				if ($person->disp && $fact->disp) {
					$text = FactFunctions::GetCalendarFact($fact, $action, $filter);
					if ($text!="filter") {
						if ($lastgid != $person->xref) {
							$daytext .= "<li><a href=\"".SERVER_URL ."individual.php?pid=".$person->xref."&amp;gedid=".GedcomConfig::$GEDCOMID."\"><b>".PrintReady($person->revname.($person->revaddname == "" ? "" : " (".$person->revaddname.")"))."</b>".$person->addxref;
							$daytext .=  "</a>\n";
							$lastgid = $person->xref;
						}
						$daytext .=  $text. "</li>";
						$OutputDone = true;
					}
				}
				else $PrivateFacts = true;
			}

			if ($factarr[2] == "FAM") {
				$family =& Family::GetInstance($factarr[0], "", GedcomConfig::$GEDCOMID);
				$fact = new Fact($factarr[0], $factarr[2], GedcomConfig::$GEDCOMID, $factarr[6], $factarr[1]);
				if ($family->disp && $fact->disp) {
					$text = FactFunctions::GetCalendarFact($fact, $action, $filter);
					if ($text!="filter") {
						if ($lastgid != $family->xref) {
							$daytext .=  "<li><a href=\"".SERVER_URL ."family.php?famid=".$family->xref."&amp;gedid=".GedcomConfig::$GEDCOMID."\"><b>".PrintReady($family->sortable_name.($family->sortable_addname == "" ? "" : "(".$family->sortable_addname.")"))."</b>".$family->addxref;
							$daytext .=  "</a>\n";
							$lastgid = $family->xref;
						}
						$daytext .=  $text . "</li>";
						$OutputDone = true;
					}
				}
				else $PrivateFacts = true;
			}
		}
	}
	$daytext .= "</ul>";

	if ($PrivateFacts) {    // Facts were found but not printed for some reason
			define("GM_LANG_global_num4", $daysprint);
			$Advisory = "no_events_privacy";
			if ($OutputDone) $Advisory = "more_events_privacy";
			if ($daysprint==1) $Advisory .= "1";
			$daytext .= PrintText($Advisory, 0, 1);
		} else if (!$OutputDone) {    // No Facts were found
			define("GM_LANG_global_num4", $daysprint);
			$Advisory = "no_events_" . $config["filter"];
			if ($daysprint==1) $Advisory .= "1";
			$daytext .= PrintText($Advisory, 0, 1);
	}

	$daytext = preg_replace("/<br \/>/", " ", $daytext);
	$daytext = strip_tags($daytext, '<a><ul><li><b>');
	if($daytext == "<ul></ul>") {
		$daytext = "";
	}
	$dataArray[2]  = $daytext;
	return $dataArray;
}

/**
 * Returns the today's events array used for the RSS feed
 *
 * @return the array with todays events data. the format is $dataArray[0] = title, $dataArray[1] = date,
 * 				$dataArray[2] = data
 * @TODO does not display the privacy message displayed by the upcoming events feed.
 */
function getTodaysEvents() {
	global $month, $year, $day, $monthtonum, $HIDE_LIVE_PEOPLE, $command, $TEXT_DIRECTION;
	global $GM_IMAGES, $ASC, $IGNORE_FACTS, $IGNORE_YEAR, $lastcachedate;

	if ($command=="user") $filter = "living";
	else $filter = "all";

	$skipfacts = "CHAN,BAPL,SLGC,SLGS,ENDL";

	$dataArray = array();
	$daytext = "<ul>";
	$action = "today";
	$dataArray[0] = GM_LANG_on_this_day;
	if (!isset($lastcachedate)) $lastcachedate = GedcomConfig::GetAllLastCacheDates();
	if (is_array($lastcachedate) && $lastcachedate["gc_last_today"] != 0) $dataArray[1] = iso8601_date($lastcachedate["gc_last_today"]);
	else $dataArray[1] = iso8601_date(time());

	$found_facts = BlockFunctions::GetCachedEvents($action, 1, $filter, "no", $skipfacts);

	$lastgid="";
	// Cache the selected indi's and fams in the indilist and famlist
	$selindi = array();
	$selfam = array();
	foreach($found_facts as $key=>$factarr) {
		if ($factarr[2] == "INDI") $selindi[] = $factarr[0];
		if ($factarr[2] == "FAM") $selfam[] = $factarr[0];
	}
	$selindi = implode("[".GedcomConfig::$GEDCOMID."]','", $selindi);
	$selindi .= "[".GedcomConfig::$GEDCOMID."]'";
	$selindi = "'".$selindi;
	ListFunctions::GetIndiList("no", $selindi);
	$selfam = implode("[".GedcomConfig::$GEDCOMID."]','", $selfam);
	$selfam .= "[".GedcomConfig::$GEDCOMID."]'";
	$selfam = "'".$selfam;
	ListFunctions::GetFamList("no", $selfam);
	foreach($found_facts as $index=>$factarr) {
		if ($factarr[2] == "INDI") {
			$person =& Person::GetInstance($factarr[0], "", GedcomConfig::$GEDCOMID);
			$fact = new Fact($factarr[0], $factarr[2], GedcomConfig::$GEDCOMID, $factarr[6], $factarr[1]);
			$factrec = $factarr[1];
			if ($person->disp && $fact->disp) {
				$text = FactFunctions::GetCalendarFact($fact, $action, $filter, "all", GetCurrentYear(), GetCurrentMonth(), GetCurrentDay());
				if ($text != "filter") {
					if ($lastgid != $person->xref) {
						$daytext .= "<li><a href=\"".SERVER_URL ."individual.php?pid=".$person->xref."&amp;gedid=".GedcomConfig::$GEDCOMID."\"><b>".						PrintReady($person->revname.($person->revaddname == "" ? "" : " (".$person->revaddname.")"))."</b>".$person->addxref;
						$daytext .= "</a>\n";
						$lastgid = $person->xref;
					}
				$daytext .= $text . "</li>";
				}
			}
		}

		if ($factarr[2] == "FAM") {
			$family =& Family::GetInstance($factarr[0], "", GedcomConfig::$GEDCOMID);
			$fact = new Fact($factarr[0], $factarr[2], GedcomConfig::$GEDCOMID, $factarr[6], $factarr[1]);
			if ($family->disp && $fact->disp) {
				$text = FactFunctions::GetCalendarFact($fact, $action, $filter, "all", GetCurrentYear(), GetCurrentMonth(), GetCurrentDay());
				if ($text!="filter") {
					if ($lastgid != $family->xref) {
							$daytext .=  "<li><a href=\"".SERVER_URL ."family.php?famid=".$family->xref."&amp;gedid=".GedcomConfig::$GEDCOMID."\"><b>".PrintReady($family->sortable_name.($family->sortable_addname == "" ? "" : "(".$family->sortable_addname.")"))."</b>".$family->addxref;
						$daytext .=  "</a>\n";
						$lastgid = $family->xref;
						$daytext .=  $text . "</li>";
					}
				}
			}
		}
	}
	$daytext .= "</ul>";
	$daytext = preg_replace("/<br \/>/", " ", $daytext);
	$daytext = strip_tags($daytext, '<a><ul><li><b>');
	if($daytext == "<ul></ul>"){
		$daytext = "";
	}
	$dataArray[2]  = $daytext;
	return $dataArray;
}

/**
 * Returns the gedcom stats
 *
 * @return the array with recent changes data. the format is $dataArray[0] = title, $dataArray[1] = date,
 * 				$dataArray[2] = data
 * @TODO does not pick up the GEDCOM stats block config and always shows most common names.
 */
function getGedcomStats() {
	global $day, $month, $year, $GEDCOMS;
	global $command, $RTLOrd;
	global $lastcachedate;

	$data = "";
	$dataArray[0] = GM_LANG_gedcom_stats . " - " . $GEDCOMS[GedcomConfig::$GEDCOMID]["title"];
	if (!isset($lastcachedate)) $lastcachedate = GedcomConfig::GetAllLastCacheDates();
	if (is_array($lastcachedate) && $lastcachedate["gc_last_stats"] != 0) $dataArray[1] = iso8601_date($lastcachedate["gc_last_stats"]);
	else $dataArray[1] = iso8601_date(time());

	$header =& Header::GetInstance("HEAD");
	$head = $header->gedrec;
	$ct=preg_match("/1 SOUR (.*)/", $head, $match);
	if ($ct>0) {
		$softrec = GetSubRecord(1, "1 SOUR", $head);
		$tt= preg_match("/2 NAME (.*)/", $softrec, $tmatch);
		if ($tt>0) $title = trim($tmatch[1]);
		else $title = trim($match[1]);
		if (!empty($title)) {
			$text = strip_tags(str_replace("#SOFTWARE#", $title, GM_LANG_gedcom_created_using));
			$tt = preg_match("/2 VERS (.*)/", $softrec, $tmatch);
			if ($tt>0) $version = trim($tmatch[1]);
			else $version="";
			$text = strip_tags(str_replace("#VERSION#", $version, $text));
			$data .= $text;
		}
	}
	$ct=preg_match("/1 DATE (.*)/", $head, $match);
	if ($ct>0) {
		$date = trim($match[1]);
		$ct2 = preg_match("/2 TIME (.*)/", $head, $match);
		if ($ct2 > 0) $time = trim($match[1]);
		else $time = "";	

		if (empty($title)) {
			$text = str_replace("#DATE#", GetChangedDate($date), GM_LANG_gedcom_created_on);
			$text = str_replace("#TIME#", $time, $text);
		}
		else {
			$text = str_replace("#DATE#", GetChangedDate($date), GM_LANG_gedcom_created_on2);
			$text = str_replace("#TIME#", $time, $text);
		}
		$data .= $text;
	}

	$data .= " <br />\n";
	$data .= GetListSize("indilist"). " - " .GM_LANG_stat_individuals."<br />";
	$data .= GetListSize("famlist"). " - ".GM_LANG_stat_families."<br />";
	$data .= GetListSize("sourcelist")." - ".GM_LANG_stat_sources."<br /> ";
	$data .= GetListSize("otherlist")." - ".GM_LANG_stat_other."<br />";



	// NOTE: Get earliest birth year
	$sql = "select min(d_year) as lowyear from ".TBLPREFIX."dates where d_file = '".GedcomConfig::$GEDCOMID."' and d_fact = 'BIRT' and d_year != '0' and d_type is null";
	$res = NewQuery($sql);
	$row = $res->FetchAssoc();
	$data .= GM_LANG_stat_earliest_birth." - ".$row["lowyear"]."<br />\n";

	// NOTE: Get the latest birth year
	$sql = "select max(d_year) as highyear from ".TBLPREFIX."dates where d_file = '".GedcomConfig::$GEDCOMID."' and d_fact = 'BIRT' and d_type is null";
	$res = NewQuery($sql);
	$row = $res->FetchAssoc();
	$data .= GM_LANG_stat_latest_birth." - " .$row["highyear"]."<br />\n";



	$surnames = NameFunctions::GetCommonSurnamesIndex(GedcomConfig::$GEDCOMID);
	if (count($surnames)>0) {
		$data .="<b>" . GM_LANG_common_surnames."</b><br />";
		$i=0;
		foreach($surnames as $indexval => $surname) {
			if ($i>0) $data .= ", ";
			if (in_array(ord(substr($surname["name"], 0, 2)),$RTLOrd)) {
				//if (ord(substr($surname["name"], 0, 2),$RTLOrd)){}
				$data .= "<a href=\"".SERVER_URL ."indilist.php?surname=".urlencode($surname["name"])."&amp;gedid=".GedcomConfig::$GEDCOMID."\">".$surname["name"]."</a>";
			}
			else $data .= "<a href=\"".SERVER_URL ."indilist.php?surname=".$surname["name"]."&amp;gedid=".GedcomConfig::$GEDCOMID."\">".$surname["name"]."</a>";
			$i++;
		}
	}

	$data = strip_tags($data, '<a><br><b>');

	$dataArray[2] = $data;
	return $dataArray;
}

/**
 * Returns the gedcom news for the RSS feed
 *
 * @return array of GEDCOM news arrays. Each GEDCOM news array contains $itemArray[0] = title, $itemArray[1] = date,
 * 				$itemArray[2] = data, $itemArray[3] = anchor (so that the link will load the proper part of the GM page)
 * @TODO prepend relative URL's in news items with SERVER_URL
 */
function getGedcomNews() {
	global $GM_IMAGES, $TEXT_DIRECTION, $command, $TIME_FORMAT, $lastcachedate;

	$usernews = NewsController::getUserNews(GedcomConfig::$GEDCOMID);

	$dataArray = array();
	foreach($usernews as $key=>$news) {

		$day = date("j", $news->date);
		$mon = date("M", $news->date);
		$year = date("Y", $news->date);
		$data = "";
		$news->title = ReplaceEmbedText($news->title);
		$itemArray[0] = $news->title;

		$itemArray[1] = iso8601_date($news->date);
		$news->text = ReplaceEmbedText($news->text);
		$trans = get_html_translation_table(HTML_SPECIALCHARS);
		$trans = array_flip($trans);
		$news->text = strtr($news->text, $trans);
//		$news->text = nl2br($news->text);
		$data .= $news->text;
		$itemArray[2] = $data;
		$itemArray[3] = $news->anchor;
		$dataArray[] = $itemArray;

	}
	return $dataArray;
}

/**
 * Returns the top 10 surnames
 *
 * @return the array with the top 10 surname data. the format is $dataArray[0] = title, $dataArray[1] = date,
 * 				$dataArray[2] = data
 * @TODO does not pick up the the top 10 surname block config and always uses 10 names.
 * @TODO Possibly turn list into a <ul> list
 */
function getTop10Surnames() {
	global $GM_BLOCKS, $command, $GM_IMAGES;

	$data = "";
	$dataArray = array();


	function top_surname_sort($a, $b) {
		return $b["match"] - $a["match"];
	}

	$GM_BLOCKS["print_block_name_top10"]["config"] = array("num"=>10);

	if (empty($config)) $config = $GM_BLOCKS["print_block_name_top10"]["config"];

	$dataArray[0] = str_replace("10", $config["num"], GM_LANG_block_top10_title);
	if (!isset($lastcachedate)) $lastcachedate = GedcomConfig::GetAllLastCacheDates();
	if (is_array($lastcachedate) && $lastcachedate["gc_last_stats"] != 0) $dataArray[1] = iso8601_date($lastcachedate["gc_last_stats"]);
	else $dataArray[1] = iso8601_date(time());

	//-- cache the result in the session so that subsequent calls do not have to
	//-- perform the calculation all over again.
	if (isset($_SESSION["top10"][GedcomConfig::$GEDCOMID]["names"])) {
		$surnames = $_SESSION["top10"][GedcomConfig::$GEDCOMID]["names"];
		$dataArray[1] = $_SESSION["top10"][GedcomConfig::$GEDCOMID]["time"];
	}
	else {
		$surnames = BlockFunctions::GetTopSurnames($config["num"]);

		// Insert from the "Add Names" list if not already in there
		if (GedcomConfig::$COMMON_NAMES_ADD != "") {
			$addnames = preg_split("/[,;] /", GedcomConfig::$COMMON_NAMES_ADD);
			if (count($addnames)==0) $addnames[] = GedcomConfig::$COMMON_NAMES_ADD;
			foreach($addnames as $indexval => $name) {
				//$surname = Str2Upper($name);
				$surname = $name;
				if (!isset($surnames[$surname])) {
					$surnames[$surname]["name"] = $name;
					$surnames[$surname]["match"] = GedcomConfig::$COMMON_NAMES_THRESHOLD;
				}
			}
		}

		// Remove names found in the "Remove Names" list
		if (GedcomConfig::$COMMON_NAMES_REMOVE != "") {
			$delnames = preg_split("/[,;] /", GedcomConfig::$COMMON_NAMES_REMOVE);
			if (count($delnames)==0) $delnames[] = GedcomConfig::$COMMON_NAMES_REMOVE;
			foreach($delnames as $indexval => $name) {
				//$surname = Str2Upper($name);
				$surname = $name;
				unset($surnames[$surname]);
			}
		}

		// Sort the list and save for future reference
		uasort($surnames, "top_surname_sort");
		$_SESSION["top10"][GedcomConfig::$GEDCOMID]["names"] = $surnames;
		$_SESSION["top10"][GedcomConfig::$GEDCOMID]["time"] = $dataArray[1];
	}
	if (count($surnames)>0) {
		$i=0;
		foreach($surnames as $indexval => $surname) {
			if (stristr($surname["name"], "@N.N")===false) {
				$data .= "<a href=\"".SERVER_URL ."indilist.php?surname=".urlencode($surname["name"])."&amp;gedid=".GedcomConfig::$GEDCOMID."\">".PrintReady($surname["name"])."</a> [".$surname["match"]."] <br />";
				$i++;
				if ($i>=$config["num"]) break;
			}
		}
	}
	$dataArray[2] = $data;
	return $dataArray;
}

/**
 * Returns the recent changes list for the RSS feed
 *
 * @return the array with recent changes data. the format is $dataArray[0] = title, $dataArray[1] = date,
 * 				$dataArray[2] = data
 * @TODO does not pick up the recent changes block config and always uses 30 days.
 * @TODO use date of most recent change instead of curent time
 * @todo Find out why TOTAL_QUERIES is here???
 */
function getRecentChanges() {
	global $month, $year, $day, $monthtonum, $HIDE_LIVE_PEOPLE, $command, $TEXT_DIRECTION;
	global $GM_IMAGES, $ASC, $IGNORE_FACTS, $IGNORE_YEAR, $TOTAL_QUERIES, $LAST_QUERY, $GM_BLOCKS, $SHOW_SOURCES;

	if ($command=="user") $filter = "living";
	else $filter = "all";

	$config = $GM_BLOCKS["print_recent_changes"]["config"];
	if ($config["days"]<1 or $config["days"]>30) $config["days"] = 30;
	if (isset($config["hide_empty"])) $HideEmpty = $config["hide_empty"];
	else $HideEmpty = "no";

	$dataArray[0] = GM_LANG_recent_changes;
	$dataArray[1] = time();//FIXED: get overwritten later, if any found.

	$recentText = "";

	$found_facts = BlockFunctions::GetRecentChangeFacts(GetCurrentDay(), GetCurrentMonth(), GetCurrentYear(), $config["days"], $config);

// Start output
	if (count($found_facts)==0 and $HideEmpty=="yes") return false;


//		Print block content
	define("GM_LANG_global_num3", $config["days"]);		// Make this visible
	if (count($found_facts)==0) {
		$recentText .= PrintText("recent_changes_none", 0, 1);
	} else {
		$recentText .= PrintText("recent_changes_some", 0, 1) . "<br />\n";
		$ASC = 1;
		$IGNORE_FACTS = 1;
		$IGNORE_YEAR = 0;
		SortFacts($found_facts, "", true);
		$lastgid="";
		$first = true;
		foreach($found_facts as $index=>$factarr) {
			// Get the first record with the most recent change date
			if ($first) {
				$ct = preg_match("/\d DATE (.*)/", $factarr[1], $match);
				if ($ct>0) {
					$date = trim($match[1]);
					$ct2 = preg_match("/\d TIME (.*)/", $factarr[1], $match);
					if ($ct2 > 0) $time = trim($match[1]);
					else $time = "";	
					$dataArray[1] = strtotime($date." ".$time);
					$first = false;
				}
			}
			if ($factarr[2] == "INDI") {
				$person =& Person::GetInstance($factarr[0]);
				$fact = New Fact($person->xref, "INDI", GedcomConfig::$GEDCOMID, $factarr[3], $factarr[1]);
				if ($lastgid != $person->xref && $person->disp) {
					$recentText .= "<a href=\"".SERVER_URL ."individual.php?pid=".$person->xref."&amp;gedid=".GedcomConfig::$GEDCOMID."\"><b>".PrintReady($person->revname.($person->revaddname == "" ? "" : " (".$person->revaddname.")"))."</b>".$person->addxref;
					$recentText .= "</a><br />\n";
					$lastgid = $person->xref;
					if ($fact->disp) { 
						$recentText .= GM_FACT_CHAN;
						if ($fact->datestring != "") {
							$recentText .= " - ".$fact->datestring."&nbsp;".$fact->timestring;
						}
						$recentText .= "<br />";
					}
				}
			}

			if ($factarr[2] == "FAM") {
				$family =& Family::GetInstance($factarr[0]);
				$fact = New Fact($family->xref, "FAM", GedcomConfig::$GEDCOMID, $factarr[3], $factarr[1]);
				if ($lastgid != $family->xref && $family->disp) {
					$recentText .=  "<li><a href=\"".SERVER_URL ."family.php?famid=".$family->xref."&amp;gedid=".GedcomConfig::$GEDCOMID."\"><b>".PrintReady($family->sortable_name.($family->sortable_addname == "" ? "" : "(".$family->sortable_addname.")"))."</b>".$family->addxref;
					$recentText .= "</a><br />\n";
					$lastgid = $family->xref;
					if ($fact->disp) { 
						$recentText .= GM_FACT_CHAN;
						if ($fact->datestring != "") {
							$recentText .= " - ".$fact->datestring."&nbsp;".$fact->timestring;
						}
						$recentText .= "<br />";
					}
				}
			}

			if ($factarr[2]=="SOUR") {
				$source =& Source::GetInstance($factarr[0]);
				$fact = New Fact($source->xref, "SOUR", GedcomConfig::$GEDCOMID, $factarr[3], $factarr[1]);
				if ($lastgid != $source->xref && $source->disp) {
					$recentText .= "<a href=\"".SERVER_URL ."source.php?sid=".$source->xref."&amp;gedid=".GedcomConfig::$GEDCOMID."\"><b>".PrintReady($source->name)."</b>".$source->addxref;
					$recentText .= "</a><br />\n";
					$lastgid = $source->xref;
					if ($fact->disp) { 
						$recentText .= GM_FACT_CHAN;
						if ($fact->datestring != "") {
							$recentText .= " - ".$fact->datestring."&nbsp;".$fact->timestring;
						}
						$recentText .= "<br />";
					}
				}
			}

			if ($factarr[2]=="REPO") {
				$repo =& Repository::GetInstance($factarr[0]);
				$fact = New Fact($repo->xref, "REPO", GedcomConfig::$GEDCOMID, $factarr[3], $factarr[1]);
				if ($lastgid != $repo->xref) {
					$recentText .= "<a href=\"".SERVER_URL ."repo.php?rid=".$repo->xref."&amp;gedid=".GedcomConfig::$GEDCOMID."\"><b>".PrintReady($repo->name)."</b>".$repo->addxref;
					$recentText .= "</a><br />\n";
					$lastgid = $repo->xref;
					if ($fact->disp) { 
						$recentText .= GM_FACT_CHAN;
						if ($fact->datestring != "") {
							$recentText .= " - ".$fact->datestring."&nbsp;".$fact->timestring;
						}
						$recentText .= "<br />";
					}
				}
			}
			if ($factarr[2]=="OBJE") {
				$media =& MediaItem::GetInstance($factarr[0]);
				$fact = New Fact($media->xref, "OBJE", GedcomConfig::$GEDCOMID, $factarr[3], $factarr[1]);
				if ($lastgid != $media->xref) {
					$recentText .= "<a href=\"".SERVER_URL ."mediadetail.php?mid=".$media->xref."&amp;gedid=".GedcomConfig::$GEDCOMID."\"><b>".PrintReady($media->name)."</b>".$media->addxref;
					$recentText .= "</a><br />\n";
					$lastgid = $media->xref;
					if ($fact->disp) { 
						$recentText .= GM_FACT_CHAN;
						if ($fact->datestring != "") {
							$recentText .= " - ".$fact->datestring."&nbsp;".$fact->timestring;
						}
						$recentText .= "<br />";
					}
				}
			}
		}

	}

	$recentText = strip_tags($recentText, '<a><br><b>');
	$dataArray[2] = $recentText;
	return $dataArray;
}

?>