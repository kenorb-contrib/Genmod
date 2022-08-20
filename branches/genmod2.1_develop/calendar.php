<?php
/**
 * Display Events on a Calendar
 *
 * Displays events on a daily, monthly, or yearly calendar.
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
 *
 * $Id: calendar.php 29 2022-07-17 13:18:20Z Boudewijn $
 * @package Genmod
 * @subpackage Calendar
 */

/**
 * load the configuration and create the context
 */
require("config.php");

$calendar_controller = new CalendarController();

PrintHeader($calendar_controller->pagetitle);

//Print page top title
print "<div id=\"CalendarPage\">\n";
	print "<div id=\"CalendarPageTitle\">\n";
	print "<span class=\"PageTitleName\">".($calendar_controller->action == "today" ? GM_LANG_on_this_day : ($calendar_controller->action == "calendar" ? GM_LANG_in_this_month : GM_LANG_in_this_year))."</span>";
print "</div>";

if ($view!="preview") {

	print "<div class=\"CalendarOptionBlock\">";
	print "<form name=\"dateform\" method=\"get\" action=\"calendar.php\">";
	print "<input type=\"hidden\" name=\"action\" value=\"".$calendar_controller->action."\" />";
	print "<table class=\"NavBlockTable CalendarNavBlockTable\">";

	// Print the title bar
	print "<tr><td class=\"NavBlockHeader\" colspan=\"8\">";
	print $calendar_controller->bartext;
	print "</td></tr>";

	// Print calender form
		print "<tr><td class=\"NavBlockLabel\"><div class=\"HelpIconContainer\">";
		PrintHelpLink("annivers_date_select_help", "qm", "day");
		print "</div>".GM_LANG_day."</td>\n";
		print "<td colspan=\"7\" class=\"NavBlockField\">";
		for($i = 1; $i < ($calendar_controller->m_days+1); $i++) {
			print "<a href=\"calendar.php?link=10&amp;day=".$i."&amp;month=".strtolower($calendar_controller->month)."&amp;year=".$calendar_controller->year."&amp;filterev=".$calendar_controller->filterev."&amp;filterof=".$calendar_controller->filterof."&amp;filtersx=".$calendar_controller->filtersx."&amp;action=today\">";
			if ($i == $calendar_controller->day) print "<span class=\"CalendarNavBlockSelDate\">".$i."</span>";
			else print $i;
			print "</a> | ";
		}
		$Dd = adodb_date("j");
		$Mm = adodb_date("M");
		$Yy = adodb_date("Y");
	//	print "<a href=\"calendar.php?filterev=$calendar_controller->filterev&amp;filterof=$calendar_controller->filterof&amp;filtersx=$calendar_controller->filtersx\"><b>".GetChangedDate("$Dd $Mm $Yy")."</b></a> | ";
		//-- for alternate calendars the year is needed
	  	if (GedcomConfig::$CALENDAR_FORMAT!="gregorian" || $calendar_controller->usehebrew) $datestr = "$Dd $Mm $Yy";
	// 	if ($CALENDAR_FORMAT!="gregorian") $datestr = "$Dd $Mm $Yy"; // MA @@@
		else $datestr = "$Dd $Mm";
		print "<a href=\"calendar.php?link=13&amp;filterev=".$calendar_controller->filterev."&amp;filterof=".$calendar_controller->filterof."&amp;filtersx=".$calendar_controller->filtersx."&amp;year=".$calendar_controller->year."\"><span class=\"CalendarNavBlockCurDate\">".GetChangedDate($datestr);
		if ($calendar_controller->usehebrew) {
			$hdatestr = "@#DHEBREW@ ".$calendar_controller->currhDay." ".$calendar_controller->currhMonth." ".$calendar_controller->currhYear;
			print " / ".GetChangedDate($hdatestr, $calendar_controller->CalYear);
		}
		print "</span></a> | ";
		print "</td>\n";
	
		print "</tr><tr>";
		print "<td class=\"NavBlockLabel\"><div class=\"HelpIconContainer\">";
		PrintHelpLink("annivers_month_select_help", "qm", "month");
		print "</div>".GM_LANG_month."</td>\n";
		print "<td colspan=\"7\" class=\"NavBlockField\">";
		foreach($monthtonum as $mon=>$num) {
			if (defined("GM_LANG_".$mon)) {
				if (empty($mm)) $mm=strtolower($calendar_controller->month);
				print "<a href=\"calendar.php?link=1&amp;day=".$calendar_controller->day."&amp;month=".$mon."&amp;year=".$calendar_controller->year."&amp;filterev=".$calendar_controller->filterev."&amp;filterof=".$calendar_controller->filterof."&amp;filtersx=".$calendar_controller->filtersx."&amp;action=".($calendar_controller->action == "year" ? "calendar" : $calendar_controller->action)."\">";
				$monthstr = constant("GM_LANG_".$mon);
				if ($mon==$mm) print "<span class=\"CalendarNavBlockSelDate\">".$monthstr."</span>";
				else print $monthstr;
				print "</a> | ";
			}
		}
	
		print "<a href=\"calendar.php?link=14&amp;month=".strtolower(adodb_date("M"))."&amp;action=calendar&amp;filterev=".$calendar_controller->filterev."&amp;filterof=".$calendar_controller->filterof."&amp;filtersx=".$calendar_controller->filtersx."\"><span class=\"CalendarNavBlockCurDate\">".constant("GM_LANG_".strtolower(adodb_date("M")))." ".strtolower(adodb_date("Y"))."</span></a> | ";
		print "</td>\n";
		print "</tr><tr>";
		print "<td class=\"NavBlockLabel\"><div class=\"HelpIconContainer\">";
		PrintHelpLink("annivers_year_select_help", "qm", "year");
		print "</div>".GM_LANG_year."</td>\n";
		print "<td class=\"NavBlockField\">";
		if (strlen($calendar_controller->year) < 5){
			if ($calendar_controller->year<"AA") print " <a href=\"calendar.php?link=2&amp;day=".$calendar_controller->day."&amp;month=".$calendar_controller->month."&amp;year=".($calendar_controller->year-1)."&amp;action=".($calendar_controller->action == "calendar" ? "calendar" : "year")."&amp;filterev=".$calendar_controller->filterev."&amp;filterof=".$calendar_controller->filterof."&amp;filtersx=".$calendar_controller->filtersx."\" title=\"".($calendar_controller->year-1)."\" >-1</a> ";
		}
		print "<input type=\"text\" name=\"year\" value=\"".$calendar_controller->year."\" size=\"7\" />";
		if (strlen($calendar_controller->year) < 5){
			if ($calendar_controller->year<(adodb_date("Y"))) print " <a href=\"calendar.php?link=3&amp;day=".$calendar_controller->day."&amp;month=".$calendar_controller->month."&amp;year=".($calendar_controller->year+1)."&amp;action=".($calendar_controller->action == "calendar" ? "calendar" : "year")."&amp;filterev=".$calendar_controller->filterev."&amp;filterof=".$calendar_controller->filterof."&amp;filtersx=".$calendar_controller->filtersx."\" title=\"".($calendar_controller->year+1)."\" >+1</a> |";
			else if ($calendar_controller->year < "AA") print " +1 |";
		}
		print " <a href=\"calendar.php?link=4&amp;day=".$calendar_controller->day."&amp;month=".$calendar_controller->month."&amp;year=".adodb_date("Y")."&amp;action=".($calendar_controller->action == "calendar" ? "calendar" : "year")."&amp;filterev=".$calendar_controller->filterev."&amp;filterof=".$calendar_controller->filterof."&amp;filtersx=".$calendar_controller->filtersx."\"><span class=\"CalendarNavBlockCurDate\">".strtolower(adodb_date("Y"))."</span></a> | ";
	
		print "</td>\n ";
		if ($HIDE_LIVE_PEOPLE >= $gm_user->GetUserAccessLevel()) {
			print "<td class=\"NavBlockLabel\"><div class=\"HelpIconContainer\">";
			PrintHelpLink("annivers_show_help", "qm", "show");
			print "</div>".GM_LANG_show.":&nbsp;</td>\n";
			print "<td class=\"NavBlockField\">";
	
			print "<input type=\"hidden\" name=\"filterof\" value=\"".$calendar_controller->filterof."\" />";
			print "<select name=\"filterof\" onchange=\"document.dateform.submit();\">\n";
			print "<option value=\"all\"";
			if ($calendar_controller->filterof == "all") print " selected=\"selected\"";
			print ">".htmlentities(GM_LANG_all_people)."</option>\n";
			print "<option value=\"living\"";
			if ($calendar_controller->filterof == "living") print " selected=\"selected\"";
			print ">".htmlentities(GM_LANG_living_only)."</option>\n";
			print "<option value=\"recent\"";
			if ($calendar_controller->filterof == "recent") print " selected=\"selected\"";
			print ">".htmlentities(GM_LANG_recent_events)."</option>\n";
			print "</select>\n";
		}
		else {
			print "<td class=\"NavBlockLabel\">".GM_LANG_showcal."</td>\n";
			print "<td colspan=\"5\" class=\"NavBlockField\">";
			if ($calendar_controller->filterof == "all") print "<span class=\"Error\">".GM_LANG_all_people. "</span> | ";
			else {
				$filt="all";
				print "<a href=\"calendar.php?link=5&amp;day=".$calendar_controller->day."&amp;month=".$calendar_controller->month."&amp;year=".$calendar_controller->year."&amp;filterof=".$filt."&amp;filtersx=".$calendar_controller->filtersx."&amp;action=".$calendar_controller->action."\">".htmlentities(GM_LANG_all_people)."</a>"." | ";
			}
			if ($calendar_controller->filterof == "recent") print "<span class=\"Error\">".htmlentities(GM_LANG_recent_events). "</span> | ";
			else {
				$filt = "recent";
				print "<a href=\"calendar.php?link=6&amp;day=".$calendar_controller->day."&amp;month=".$calendar_controller->month."&amp;year=".$calendar_controller->year."&amp;filterof=".$filt."&amp;filtersx=".$calendar_controller->filtersx."&amp;action=".$calendar_controller->action."\">".htmlentities(GM_LANG_recent_events)."</a>"." | ";
			}
		}
		
	
		if ($HIDE_LIVE_PEOPLE >= $gm_user->GetUserAccessLevel()) {
			print "</td>\n ";
			print "<td class=\"NavBlockLabel\"><div class=\"HelpIconContainer\">";
			PrintHelpLink("annivers_sex_help", "qm", "sex");
			print "</div>".GM_LANG_sex.":&nbsp;</td>\n";
			print "<td class=\"NavBlockField\">";
			if ($calendar_controller->filtersx == ""){
				print " <img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sex"]["small"]."\" title=\"".GM_LANG_all."\" alt=\"".GM_LANG_all."\" class=\"GenderImageLarge\" align=\"middle\" />";
				print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sexf"]["small"]."\" title=\"".GM_LANG_all."\" alt=\"".GM_LANG_all."\" class=\"GenderImageLarge\" align=\"middle\" />";
				print " | ";
			}
			else {
				$fs="";
				print " <a href=\"calendar.php?link=7&amp;day=".$calendar_controller->day."&amp;month=".$calendar_controller->month."&amp;year=".$calendar_controller->year."&amp;filterev=".$calendar_controller->filterev."&amp;filterof=".$calendar_controller->filterof."&amp;filtersx=".$fs."&amp;action=".$calendar_controller->action."\">";
				print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sex"]["small"]."\" title=\"".GM_LANG_all."\" alt=\"".GM_LANG_all."\" class=\"GenderImage\" align=\"middle\" />";
				print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sexf"]["small"]."\" title=\"".GM_LANG_all."\" alt=\"".GM_LANG_all."\" class=\"GenderImage\" border=\"0\" align=\"middle\" />";
				print "</a>"." | ";
			}
			if ($calendar_controller->filtersx == "M"){
				print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sex"]["small"]."\" title=\"".GM_LANG_male."\" alt=\"".GM_LANG_male."\" class=\"GenderImageLarge\" align=\"middle\" />";
				print " | ";
			}
			else {
				$fs = "M";
				print "<a href=\"calendar.php?link=8&amp;day=".$calendar_controller->day."&amp;month=".$calendar_controller->month."&amp;year=".$calendar_controller->year."&amp;filterev=".$calendar_controller->filterev."&amp;filterof=".$calendar_controller->filterof."&amp;filtersx=".$fs."&amp;action=".$calendar_controller->action."\">";
				print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sex"]["small"]."\" title=\"".GM_LANG_male."\" alt=\"".GM_LANG_male."\" class=\"GenderImage\" align=\"middle\" />";
				print "</a>"." | ";
			}
			if ($calendar_controller->filtersx == "F"){
				print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sexf"]["small"]."\" title=\"".GM_LANG_female."\" alt=\"".GM_LANG_female."\" class=\"GenderImageLarge\" align=\"middle\" />";
				print " | ";
			}
			else {
				$fs = "F";
				print "<a href=\"calendar.php?link=9&amp;day=".$calendar_controller->day."&amp;month=".$calendar_controller->month."&amp;year=".$calendar_controller->year."&amp;filterev=".$calendar_controller->filterev."&amp;filterof=".$calendar_controller->filterof."&amp;filtersx=".$fs."&amp;action=".$calendar_controller->action."\">";
				print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sexf"]["small"]."\" title=\"".GM_LANG_female."\" alt=\"".GM_LANG_female."\" class=\"GenderImage\" align=\"middle\" />";
				print "</a>"." | ";
			}
			
		}
	
		if ($HIDE_LIVE_PEOPLE >= $gm_user->GetUserAccessLevel()) {
			print "</td>\n ";
			print "<td class=\"NavBlockLabel\"><div class=\"HelpIconContainer\">";
			PrintHelpLink("annivers_event_help", "qm", "showcal");
			print "</div>".GM_LANG_showcal."&nbsp;</td>\n";
			print "<td class=\"NavBlockField\"";
			if ($HIDE_LIVE_PEOPLE >= $gm_user->GetUserAccessLevel()) print ">";
			else print " colspan=\"3\">";
			print "<select name=\"filterev\" onchange=\"document.dateform.submit();\">\n";
			
			print "<option value=\"bdm\"";
			if ($calendar_controller->filterev == "bdm") print " selected=\"selected\"";
			print ">".GM_LANG_bdm."</option>\n";
			
			PrintFilterEvent($calendar_controller->filterev);
		}
	
		print "</td>\n";
		print "</tr>";
		print "<tr><td class=\"NavBlockFooter\" colspan=\"8\">";
		PrintHelpLink("day_month_help", "qm");
		print "<input type=\"hidden\" name=\"day\" value=\"".$calendar_controller->day."\" />";
		print "<input type=\"hidden\" name=\"month\" value=\"".$mm."\" />";
		print "<input type=\"hidden\" name=\"filtersx\" value=\"".$calendar_controller->filtersx."\" />";
		print "<input type=\"submit\"  value=\"".GM_LANG_viewday."\" onclick=\"document.dateform.elements['action'].value='today';\" />\n";
		print "<input type=\"submit\"  value=\"".GM_LANG_viewmonth."\" onclick=\"document.dateform.elements['action'].value='calendar';\" />\n";
		print "<input type=\"submit\"  value=\"".GM_LANG_viewyear."\" onclick=\"document.dateform.elements['action'].value='year';\" />\n";
		print "</td></tr></table>";
		print "</form>\n";
	print "</div>\n";
}

print "<div class=\"CalendarResultContainer\">";
if ($calendar_controller->action == "today" || $calendar_controller->action == "year") {
	
	$calendar_controller->GetDayYearLists();
	
	$count_private_indi = array();
	$indi_printed = array();
	$count_indi = 0;
	$count_male = 0;
	$count_female = 0;
	$count_unknown = 0;
	$text_indi = "";
	$sx = 1;
	$index = 0;
	if ($calendar_controller->filterev == "bdm") $select = array("BIRT", "DEAT", "MARR");
	else if ($calendar_controller->filterev == "all") $select = "";
	else $select = array($calendar_controller->filterev);
	//print "selectfacts: ".$select."<br />";
	foreach($calendar_controller->myindilist as $gid=>$indi) {
		//print $indi->name.$indi->addxref."<br />";
		if ((($calendar_controller->filterof == "living" && !$indi->isdead) || $calendar_controller->filterof != "living") && (($calendar_controller->filtersx != "" && $indi->sex == $calendar_controller->filtersx) || $calendar_controller->filtersx == "") && !isset($indi_printed[$indi->key])) {
			$indi_printed[$indi->key] = true;
			$text_fact = "";
			if ($calendar_controller->filtersx != "" && !is_null($calendar_controller->myfamlist)) $indi->AddFamilyFacts(false);
			$indifacts = $indi->SelectFacts($select, true);
			//print "check ".$indi->xref." ".$indi->name."<br >";
			$text_fact = $calendar_controller->GetDateFacts($indifacts);
			if (!empty($text_fact)) {
				$index++;
				$text_indi .= "<li><a href=\"individual.php?pid=".$indi->xref."&amp;gedid=".$indi->gedcomid."\"><span class=\"CalendarDayFactName\">";
				$text_indi .= $indi->revname;
				if ($indi->addname != "") $text_indi .= "&nbsp;".PrintReady("(".$indi->revaddname.")");
				$text_indi .= "</span><img id=\"box-".$indi->xref.".".$index."-sex\" src=\"".GM_IMAGE_DIR."/";
				if ($indi->sex == "M"){
					$count_male++;
					$text_indi .= $GM_IMAGES["sex"]["small"]."\" title=\"".GM_LANG_male."\" alt=\"".GM_LANG_male;
				}
				else if ($indi->sex == "F"){
					$count_female++;
					$text_indi .= $GM_IMAGES["sexf"]["small"]."\" title=\"".GM_LANG_female."\" alt=\"".GM_LANG_female;
				}
				else {
					$count_unknown++;
					$text_indi .= $GM_IMAGES["sexn"]["small"]."\" title=\"".GM_LANG_unknown."\" alt=\"".GM_LANG_unknown;
				}
				$text_indi .= "\" class=\"GenderImage\" />";
				$text_indi .= $indi->addxref;
				$text_indi .= "</a>\n\t\t";
				$text_indi .= "<div class=\"CalendarFactIndent\">";
				$text_indi .= $text_fact;
				$text_indi .= "</div></li>\n\t\t";
				$count_indi++;
			}
			// else if (!$filterout && $text_fact!="") $count__url_indi++; //?? admin sees as private in year also indis w/o a year in their fact 
			else {
//				if (!$filterout && $indi->disp) $count_private_indi[$gid] = 1;
			}
		}
	}

	$count_private_fam = array();
	$count_fam=0;
	$text_fam="";
	if ($calendar_controller->filterev == "bdm") $select = array("MARR");
	else if ($calendar_controller->filterev == "all") $select = "";
	else $select = array($calendar_controller->filterev);
	if ($calendar_controller->filtersx == "") {
		foreach($calendar_controller->myfamlist as $gid => $fam) {
			if ($calendar_controller->filterof != "living" || (($fam->husb_id == "" || !$fam->husb->isdead) && ($fam->wife_id == "" || !$fam->wife->isdead))) {
				$filterout=false;
				$text_fact = "";
				$famfacts = $fam->SelectFacts($select, true);
				$text_fact = $calendar_controller->GetDateFacts($famfacts);
				if (!empty($text_fact) && $fam->disp) {
					$text_fam .= "<li><a href=\"family.php?famid=".$fam->xref."&amp;gedid=".$fam->gedcomid."\"><span class=\"CalendarDayFactName\">";
					$text_fam .= $fam->sortable_name;
					if ($fam->sortable_addname != "") $text_fam .= "&nbsp(".$fam->sortable_addname.")";
					$text_fam .= "</span>".$fam->addxref;
					$text_fam .= "</a>\n\t\t";
					$text_fam .= "<div class=\"CalendarFactIndent";
					if ($TEXT_DIRECTION == "rtl") $text_fam .= "_rtl";
					$text_fam .= "\">";
					$text_fam .= $text_fact;
					$text_fam .= "</div></li>\n\t\t";
					$count_fam++;
				}
//				else if (!$filterout) $count_private_fam[$gid] = 1;
			}
		}
	}
//	print_r(SearchFunctions::$indi_hide);
	$count_private_fam = count($count_private_fam);
//	$count_private_indi = count(array_merge($count_private_indi, SearchFunctions::$indi_hide));
	$count_private_indi = count($count_private_indi);
	// Print the day/year list(s)
	if (!empty($text_indi) || !empty($text_fam) || $count_private_indi>0 || $count_private_fam>0) {
		print "\n\t\t<table class=\"ListTable\">\n\t\t<tr>";
		if ($view == "preview") {
			print "<td class=\"ListTableHeader\" colspan=\"2\">".$calendar_controller->bartext."</td></tr><tr>";
		}
		if (!empty($text_indi) || ($count_private_indi>0)) {
			print "<td class=\"ListTableColumnHeader\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]."\" border=\"0\" title=\"".GM_LANG_individuals."\" alt=\"".GM_LANG_individuals."\" /> ".GM_LANG_individuals."</td>";
		}
		if (!empty($text_fam) || ($count_private_fam)) {
			print "<td class=\"ListTableColumnHeader\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sfamily"]["small"]."\" border=\"0\" title=\"".GM_LANG_families."\" alt=\"".GM_LANG_families."\" /> ".GM_LANG_families."</td>";
		}
		print "</tr><tr>\n\t\t";
		if (!empty($text_indi) || ($count_private_indi)) {
			print "<td class=\"ListTableContent CalendarDYListTableContent\"><ul>\n\t\t";
			if (!empty($text_indi)) print $text_indi;
			if ($count_private_indi > 0){
				PrintHelpLink("privacy_error_help", "qm", "private");
				print "<li><b>";
				print GM_LANG_private;
				print "</b> (".$count_private_indi.") ";
				print "</li>\n\t\t";
			}
			print "</ul></td>";
		}
		if (!empty($text_fam) || ($count_private_fam)) {
			print "<td class=\"ListTableContent CalendarDYListTableContent\"><ul>\n\t\t";
			if (!empty($text_fam)) print $text_fam;
			if ($count_private_fam > 0){
				PrintHelpLink("privacy_error_help", "qm", "private");
				print "<li><b>";
				print GM_LANG_private;
				print "</b> (".$count_private_fam.") ";
				print "</li>\n\t\t";
			}
			print "</ul></td>";
		}
		print "</tr><tr>";
		if ($count_indi > 0 || $count_private_indi > 0){
			print "<td class=\"ListTableColumnFooter\">\n";
			if (($count_male + $count_female + $count_unknown + $count_private_indi) > 0)
			print GM_LANG_total_indis." ".($count_male+$count_female+$count_unknown+$count_private_indi)."<br />&nbsp;&nbsp;";
			if ($count_male > 0){
				print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sex"]["small"]."\" ";
				print "title=\"".GM_LANG_male."\" alt=\"".GM_LANG_male."\" class=\"GenderImage\" />&nbsp;";
				print $count_male;
			}
			if ($count_male > 0 && $count_female > 0) print "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			if ($count_female > 0) {
				print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sexf"]["small"]."\" ";
				print "title=\"".GM_LANG_female."\" alt=\"".GM_LANG_female."\" class=\"GenderImage\"  />&nbsp;";
				print $count_female;
			}
			if ($count_unknown > 0) {
				print "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
				print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sexn"]["small"]."\" ";
				print "title=\"".GM_LANG_unknown."\" alt=\"".GM_LANG_unknown."\" class=\"GenderImage\" />&nbsp;";
				print $count_unknown;
			}
			print "</td>";
		}
		if ($count_fam > 0 || $count_private_fam > 0) {
			print "<td class=\"ListTableColumnFooter\">\n";
			if ($count_fam > 0 || $count_private_fam > 0) print GM_LANG_total_fams;
			print "&nbsp;&nbsp;".($count_fam+$count_private_fam);
			print "</td>";
		}
		print "</tr>";
		if ($view=="preview") print "<tr><td class=\"ListTableFooter\" colspan=\"2\">";
		else print "</table>";
	}
	else {
		print "\n\t<div class=\"Warning CalendarWarning\">";
		print GM_LANG_no_results;
		print "</div>";
	}
}
else if ($calendar_controller->action == "calendar") {
	if(GedcomConfig::$CALENDAR_FORMAT == "jewish" || GedcomConfig::$CALENDAR_FORMAT == "hebrew" || GedcomConfig::$CALENDAR_FORMAT == "hijri") { //since calendar is based on gregorian it doesn't make sense to not display the gregorian caption
		print "<span class=\"SubHeader\">".constant("GM_LANG_".strtolower($calendar_controller->month))." ".$calendar_controller->year."</span> &#160; \n";
	}
	if (empty($WEEK_START)) $WEEK_START="0";                //-- if the starting day for a week was not defined in the language file, then make it Sunday
	print "<table class=\"ListTable CalendarDayBoxTable\">\n";
	print "\t<tr>\n";
	if ($view == "preview") {
		print "<td class=\"ListTableHeader\" colspan=\"7\">".$calendar_controller->bartext."</td></tr><tr>";
	}
	$days = array();
	$days[0] = "sunday";
	$days[1] = "monday";
	$days[2] = "tuesday";
	$days[3] = "wednesday";
	$days[4] = "thursday";
	$days[5] = "friday";
	$days[6] = "saturday";
	$j = $WEEK_START;
	for($i=0; $i<7; $i++) {
		print "\t\t<td class=\"ListTableColumnHeader\">".constant("GM_LANG_".$days[$j])."</td>\n";
		$j++;
		if ($j>6) $j=0;
	}
	print "\t</tr>\n";
	$calendar_controller->GetCalendarLists();
	$show_not_set = false;
	for($k=0; $k<6; $k++) {
		print "\t<tr>\n";
		for($j=0; $j<7; $j++) {
			$mday = adodb_date("j", $monthstart);
			$mmon = strtolower(adodb_date("M", $monthstart));

			print "\t\t<td class=\"ListTableContent CalendarDayListTableContent\">\n";
			if ($calendar_controller->show_no_day == 0 && $j == 0 && $k == 0) $show_not_set = true;
			else if ($calendar_controller->show_no_day == $k && $j == 6) $show_not_set = true;
			if ($mmon == strtolower($calendar_controller->month) || $show_not_set) {
				if ($show_not_set) {
					$pregquery = "2 DATE(|[^\n]*[^\d]+|[^\n]*([ |0]0)|[^\n]*3[$calendar_controller->lastday-9]|[^\n]*[4-9][0-9]) $calendar_controller->month";
					if (GedcomConfig::$USE_RTL_FUNCTIONS) {
						    $preghbquery1 = "";
						    $preghbquery2 = "";
						    $preghbquery3 = "";
						    						
						 	$datearray[0]["day"]   = 01;
 							$datearray[0]["mon"]   = $monthtonum[Str2Lower($calendar_controller->month)];	
 							$datearray[0]["year"]  = $calendar_controller->year;
 							$datearray[0]["month"] = $calendar_controller->month;
 							// should use $ParseDate

    						$date    = GregorianToJewishGedcomDate($datearray);
 							$HBMonth = $date[0]["month"];
 							$HBYear  = $date[0]["year"];
					        
					        if (!IsJewishLeapYear($HBYear) && ($HBMonth == "adr")) {
								$HBMonth1 = "ads"; 
                                $preghbquery  = "2 DATE(|[^\n]*[^\d]+|[^\n]*([ |0]0)|[^\n]*[3][1-9]|[^\n]*[4-9][0-9]) [$HBMonth|$HBMonth1]";
					        }
				        	else {
				        		$preghbquery  = "2 DATE(|[^\n]*[^\d]+|[^\n]*([ |0]0)|[^\n]*[3][1-9]|[^\n]*[4-9][0-9]) $HBMonth";
			        	    }
				    }
				}
				else {
					$calendar_controller->day = $mday;
					$currentDay = false;
					if(($calendar_controller->year == adodb_date("Y")) && (strtolower($calendar_controller->month) == strtolower(adodb_date("M"))) && ($mday == adodb_date("j"))) //current day
						$currentDay = true;
					print "<div class=\"CalendarDay". ($currentDay?" CalendarDayCurrentDay":"") ."\">".$mday."</div>";
					if (GedcomConfig::$CALENDAR_FORMAT == "hebrew_and_gregorian" || GedcomConfig::$CALENDAR_FORMAT == "hebrew" ||
						((GedcomConfig::$CALENDAR_FORMAT == "jewish_and_gregorian" || GedcomConfig::$CALENDAR_FORMAT == "jewish" || ($calendar_controller->usehebrew)) && $LANGUAGE == "hebrew")) {
						$monthTemp = $monthtonum[strtolower($calendar_controller->month)];
						$jd = gregoriantojd($monthTemp, $mday, $calendar_controller->year);
						$hebrewDate = jdtojewish($jd);
						// if ($calendar_controller->usehebrew) {
							list ($hebrewMonth, $hebrewDay, $hebrewYear) = explode ('/', $hebrewDate);
							print "<span class=\"CalendarDayRtl". ($currentDay?" CalendarDayCurrentDay":"") ."\">";
							print GetHebrewJewishDay($hebrewDay) . " " .GetHebrewJewishMonth($hebrewMonth, $hebrewYear) . "</span>";
						// }
					}
					else if(GedcomConfig::$CALENDAR_FORMAT == "jewish_and_gregorian" || GedcomConfig::$CALENDAR_FORMAT == "jewish" || ($calendar_controller->usehebrew)) {
						// else if(GedcomConfig::$CALENDAR_FORMAT=="jewish_and_gregorian" || GedcomConfig::$CALENDAR_FORMAT=="jewish" || GedcomConfig::$USE_RTL_FUNCTIONS) {
						$monthTemp = $monthtonum[strtolower($calendar_controller->month)];
						$jd = gregoriantojd($monthTemp, $mday, $calendar_controller->year);
						$hebrewDate = jdtojewish($jd);
						// if ($calendar_controller->usehebrew) {
							list ($hebrewMonth, $hebrewDay, $hebrewYear) = explode ('/', $hebrewDate);
							print "<span class=\"CalendarDayRtl". ($currentDay?" CalendarDayCurrentDay":"") ."\">";
							print $hebrewDay . " " . GetJewishMonthName($hebrewMonth, $hebrewYear) . "</span>";
						// }
					}
					else if(GedcomConfig::$CALENDAR_FORMAT == "hijri") {
						$monthTemp = $monthtonum[strtolower($calendar_controller->month)];
						$hDate = GetHijri($mday, $monthTemp, $calendar_controller->year);
						list ($hMonthName, $calendar_controller->hDay, $calendar_controller->hYear) = explode ('/', $hDate);
						print "<span class=\"CalendarDayRtl". ($currentDay?" CalendarDayCurrentDay":"") ."\">";
						print $calendar_controller->hDay . " " . $hMonthName . "</span>";
					}
					$dayindilist = array();

					if ($mday<10) $pregquery = "2 DATE[^\n]*[ |0]$mday $mmon";
					else if (!$calendar_controller->leap && $mmon == "feb" && $mday == '28') $pregquery = "2 DATE[^\n]*2[8|9] $mmon";
					else $pregquery = "2 DATE[^\n]*$mday $mmon";
				
					if (GedcomConfig::$USE_RTL_FUNCTIONS) {
						    $preghbquery1 = "";
						    $preghbquery2 = "";
						    $preghbquery3 = "";
						 	$datearray[0]["day"]   = $mday;
 							if (isset($monthTemp)) $datearray[0]["mon"]   = $monthTemp;
 							else $monthTemp = "";
 							$datearray[0]["year"]  = $calendar_controller->year;
 							$datearray[0]["month"] = $mmon;
 							// should use $ParseDate

    						$date    = GregorianToJewishGedcomDate($datearray);
 							$HBDay   = $date[0]["day"];
							$HBMonth = $date[0]["month"];
							$HBYear  = $date[0]["year"];

	// is there a better way to add 1 day to the $datearray than changing the day and using JewishGedcomDateToGregorian 
	// for Yartzeit 
	// KJ definitions - need parameters
	//     what to do in ADR
	//     if ADR 30 does not occur show on SHV 30 or NSN 01 (?) ...
	//     if CSH 30 does not occur show on KSL 01 or CHS 29     ...
	//     if KSL 30 does not occur show on TVT 01 or KSL 29     ...
						       
							if ($HBDay == '29' and ($HBMonth=='adr' || $HBMonth=='csh' || $HBMonth=='ksl')) {
								// handle day 30 in day 29 for ADR, CSH and KSL ???
								//                   2003       30   30      30 
								//					 2004	    29   29      29  No ADR
								//					 2005       30   29      30
								$date[0]["day"]='30';
								$datearray     = JewishGedcomDateToGregorian($date);
								$date          = GregorianToJewishGedcomDate($datearray);
					        }
					        
					        $HBDay1   = 30; 
					        
							if (!IsJewishLeapYear($HBYear) && $HBMonth == "adr") {
								$HBMonth1 = "ads";
								if ($HBDay<10) {
									$preghbquery  = "2 DATE[^\n]*[ |0]$HBDay $HBMonth"; 
									$preghbquery1 = "2 DATE[^\n]*[ |0]$HBDay $HBMonth1"; 
								}								
								else {
									$preghbquery  = "2 DATE[^\n]*$HBDay $HBMonth";
									$preghbquery1 = "2 DATE[^\n]*$HBDay $HBMonth1";

									if ($HBMonth != $date[0]["month"]) 
									   $preghbquery2 = "2 DATE[^\n]*$HBDay1 $HBMonth";
									   $preghbquery3 = "2 DATE[^\n]*$HBDay1 $HBMonth1";
								}									
							}
							else
							    if ($HBDay<10) $preghbquery = "2 DATE[^\n]*[ |0]$HBDay $HBMonth"; 
							    else {
								    $preghbquery = "2 DATE[^\n]*$HBDay $HBMonth"; 							   
							        if ($HBMonth != $date[0]["month"]) 
					                   $preghbquery2 = "2 DATE[^\n]*$HBDay1 $HBMonth"; 
						        }
				    }
				}
				$text_day = "";
				$count_private = 0;
				$sx=1;
				if ($calendar_controller->filterev == "bdm") $select = array("BIRT", "DEAT");
				else if ($calendar_controller->filterev == "all") $select = "";
				else $select = array($calendar_controller->filterev);
				foreach($calendar_controller->myindilist as $gid=>$indi) {
			
					if ((($calendar_controller->filterof == "living" && !$indi->isdead) || $calendar_controller->filterof != "living") && (($calendar_controller->filtersx != "" && $indi->sex == $calendar_controller->filtersx) || $calendar_controller->filtersx == "")) {
						
						if (preg_match("/$pregquery/i", $indi->gedrec) > 0 || (GedcomConfig::$USE_RTL_FUNCTIONS && (preg_match("/$preghbquery/i", $indi->gedrec) > 0 || ($preghbquery1!="" && preg_match("/$preghbquery1/i", $indi->gedrec) > 0) || ($preghbquery2!="" && preg_match("/$preghbquery2/i", $indi->gedrec) > 0) || ($preghbquery3 != "" && preg_match("/$preghbquery3/i", $indi->gedrec) > 0)))) {
							$filterout=false;
							$indifacts = $indi->SelectFacts($select, true);
							$text_fact = "";
							foreach($indifacts as $index => $factobj) {
								$text_temp = "";
								$ct = preg_match("/$pregquery/i", $factobj->factrec, $match);
								if (GedcomConfig::$USE_RTL_FUNCTIONS) {
									if ($ct < 1) $ct = preg_match("/$preghbquery/i", $factobj->factrec, $match);
									if ($ct < 1 && $preghbquery1 != "") $ct = preg_match("/$preghbquery1/i", $factobj->factrec, $match);
									if ($ct < 1 && $preghbquery2 != "") $ct = preg_match("/$preghbquery2/i", $factobj->factrec, $match);
									if ($ct < 1 && $preghbquery3 != "") $ct = preg_match("/$preghbquery3/i", $factobj->factrec, $match);
								}
								if ($ct>0) {
									$text_temp .= FactFunctions::GetCalendarFact($factobj, $calendar_controller->action, $calendar_controller->filterof, $calendar_controller->filterev, $calendar_controller->year, $calendar_controller->month, $calendar_controller->day, $calendar_controller->CalYear, $calendar_controller->currhYear);
								}
								if ($text_temp == "filter") $filterout = true;
								else $text_fact .= $text_temp;
							}
							if (!empty($text_fact) && $indi->disp) {
								$text_day .= "<a href=\"individual.php?pid=".$indi->xref."&amp;gedid=".$indi->gedcomid."\"><span class=\"CalendarDayFactName\">";
								$text_day .= $indi->revname;
								if ($indi->addname != "") $text_day .= "&nbsp;".PrintReady("(".$indi->revaddname.")");
								$text_day .= "</span>".$indi->addxref;
								$text_day .= "</a><br />\n";
								$text_day .= "<div class=\"CalendarFactIndent\">";
								$text_day .= $text_fact;
								$text_day .= "</div><br />\n";
							}
//							else if (!$filterout) $count_private++;
						}
					}
				}
				$dayfamlist = array();
//				reset($calendar_controller->myfamlist);
				$count_private_fam = 0;
				if ($calendar_controller->filterev == "bdm") $select = array("MARR");
				else if ($calendar_controller->filterev == "all") $select = "";
				else $select = array($calendar_controller->filterev);
				if ($calendar_controller->filtersx == ""){
					foreach($calendar_controller->myfamlist as $gid=>$fam) {
						$display = true;
						if ($calendar_controller->filterof == "living"){
							if (($fam->husb_id != "" && $fam->husb->isdead) || ($fam->wife_id != "" && $fam->wife->isdead)) $display=false;
						}
						if ($display) {
			    			if (preg_match("/$pregquery/i", $fam->gedrec) > 0 || (GedcomConfig::$USE_RTL_FUNCTIONS && (preg_match("/$preghbquery/i", $fam->gedrec) > 0 || ($preghbquery1!="" && preg_match("/$preghbquery1/i", $fam->gedrec) > 0) || ($preghbquery2!="" && preg_match("/$preghbquery2/i", $fam->gedrec) > 0) || ($preghbquery3!="" && preg_match("/$preghbquery3/i", $fam->gedrec) > 0)))) {
								
								$filterout = false;
								$famfacts = $fam->SelectFacts($select, true);
								$text_fact = "";
								foreach($famfacts as $index => $factobj) {
									$text_temp = "";
									$ct = preg_match("/$pregquery/i", $factobj->factrec, $match);
									if (GedcomConfig::$USE_RTL_FUNCTIONS) {
										if ($ct < 1) $ct = preg_match("/$preghbquery/i", $factobj->factrec, $match);
										if ($ct < 1 && $preghbquery1 != "") $ct = preg_match("/$preghbquery1/i", $factobj->factrec, $match);
										if ($ct < 1 && $preghbquery2 != "") $ct = preg_match("/$preghbquery2/i", $factobj->factrec, $match);
										if ($ct < 1 && $preghbquery3 != "") $ct = preg_match("/$preghbquery3/i", $factobj->factrec, $match);
									}
									if ($ct>0) {
										$text_temp .= FactFunctions::GetCalendarFact($factobj, $calendar_controller->action, $calendar_controller->filterof, $calendar_controller->filterev, $calendar_controller->year, $calendar_controller->month, $calendar_controller->day, $calendar_controller->CalYear, $calendar_controller->currhYear);
									}
									if ($text_temp == "filter") $filterout = true;
									else $text_fact .= $text_temp;
								}
								if (!empty($text_fact) && $fam->disp) {
									$text_day .= "<a href=\"family.php?famid=".$fam->xref."&amp;gedid=".$fam->gedcomid."\"><span class=\"CalendarDayFactName\">";
									$text_day .= $fam->sortable_name;
									if ($fam->sortable_addname != "") $text_day .= "&nbsp".PrintReady("(".$fam->sortable_addname.")");
									$text_day .= "</span>".$fam->addxref;
									$text_day .= "</a><br />\n";
									$text_day .= "<div class=\"CalendarFactIndent\">";
									$text_day .= $text_fact;
									$text_day .= "</div><br />\n";
								}
//								else if (!$filterout) $count_private++;
							}
						}
					}
				}
				// Print the calendar day list
				if ($show_not_set){
					if ($text_day!=""){
						print "<div class=\"CalendarDay\"";
						if ($TEXT_DIRECTION == "rtl") print	" style=\"float: right;\"";
						print " >".GM_LANG_day_not_set."</div>";
					}
					$show_not_set = false;
					$calendar_controller->show_no_day = -1;
				}
				print "<div id=\"day$k-$j\" class=\"PersonDetails1 CalendarDayBoxDetails ";
				if ($view=="preview") print "CalendarDayBoxDetailsPreview";
				else print "CalendarDayBoxDetailsHeight";
				print "\">\n";
				print $text_day;
				if ($count_private>0){
					PrintHelpLink("privacy_error_help", "qm", "private");
					print "<a name=\"p\">".GM_LANG_private;
					print "</a> (".$count_private.") ";
					print "<br />\n";
				}
				if ($view=="preview"){
					print "<br />";
					if (empty($text_day)) print "<br /><br /><br />";
				}
				print "</div>\n";
			}
			else print "<br />\n";
			print "\t\t</td>\n";
			$monthstart+=(60*60*24);
			$mmon = strtolower(adodb_date("M", $monthstart));
			if (($mmon!=strtolower($calendar_controller->month)) && ($k>2)) {
				if ($calendar_controller->show_no_day == 6){
					$calendar_controller->show_no_day = $k;
					if ($j==6) $calendar_controller->show_no_day++;
				}
				else if ($calendar_controller->show_no_day < 0) $k=6;
			}
		} //-- end day for loop
		print "\t</tr>\n";
	} //-- end week for loop
	if ($view=="preview") print "<tr><td class=\"ListTableFooter\" colspan=\"7\">";
	else print "</table>";

}
if ($view=="preview"){
	if (isset($gid)) {
		if (isset($calendar_controller->myindilist[$gid])) $showfile = $calendar_controller->myindilist[$gid]->gedcomid;
		else $showfile = $calendar_controller->myfamlist[$gid]->gedcomid;
	}
	else $showfile = GedcomConfig::$GEDCOMID;
	$showfilter="";
	if ($calendar_controller->filterof != "all") $showfilter = htmlentities($calendar_controller->filterof == "living"? GM_LANG_living_only : GM_LANG_recent_events);
	if ($calendar_controller->filtersx != ""){
		if (!empty($showfilter)) $showfilter .= " - ";
		$showfilter .= ($calendar_controller->filtersx == "M" ? GM_LANG_male : GM_LANG_female);
	}
	if ($calendar_controller->filterev != "all"){
		if (!empty($showfilter)) $showfilter .= " - ";
		if (defined("GM_FACT_".$calendar_controller->filterev)) $showfilter .= constant("GM_FACT_".$calendar_controller->filterev);
		else if (defined("GM_LANG_".$calendar_controller->filterev)) $showfilter .= constant("GM_LANG_".$calendar_controller->filterev);
	}
	print $GEDCOMS[$showfile]["title"]." (".GM_LANG_filter.": ";
	if (!empty($showfilter)) print $showfilter.")\n";
	else print GM_LANG_all.")\n";
	print "</td></tr>";
	print "</table>";
}
print "</div>";
print "</div>\n";
PrintFooter();
?>