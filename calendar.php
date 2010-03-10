<?php
/**
 * Display Events on a Calendar
 *
 * Displays events on a daily, monthly, or yearly calendar.
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
 *
 * $Id$
 * @package Genmod
 * @subpackage Calendar
 */

/**
 * load the configuration and create the context
 */
require("config.php");
$calendar_controller = new CalendarController();
 
if (GedcomConfig::$USE_RTL_FUNCTIONS) {
    if ($calendar_controller->action != "year") {   //---- ?????? does not work - see I90 in 1042 @@@@@
    	if ($calendar_controller->hDay < 10) {
			$preghbquery = "2 DATE[^\n]*[ |0]".$calendar_controller->hDay." ".$calendar_controller->hMonth;
			$queryhb = "2 DATE[^\n]*[ |0]".$calendar_controller->hDay." ".$calendar_controller->hMonth;
		}
		else {
			$preghbquery = "2 DATE[^\n]*".$calendar_controller->hDay." ".$calendar_controller->hMonth;
			$queryhb = "2 DATE[^\n]*".$calendar_controller->hDay." ".$calendar_controller->hMonth;
		}
	}
}
PrintHeader(GM_LANG_anniversary_calendar);
print "<div style=\" text-align: center;\" id=\"calendar_page\">\n";

	//-- moved here from session.php, should probably be moved somewhere else still
	$sql = "SELECT i_id FROM ".TBLPREFIX."individuals where i_file='".GedcomConfig::$GEDCOMID."' AND i_gedrec like '%@#DHEBREW@%' LIMIT 1";
	$res = NewQuery($sql);
	if ($res->NumRows()>0) $HEBREWFOUND[GedcomConfig::$GEDCOMID] = true;
	else $HEBREWFOUND[GedcomConfig::$GEDCOMID] = false;
	$res->FreeResult();

	// Print top text
	?>
	<table class="facts_table <?php print $TEXT_DIRECTION ?> width100">
	  <tr><td class="facts_label"><h3>
<?php
if ($calendar_controller->action == "today") {
	print GM_LANG_on_this_day."</h3></td></tr>\n";
	print "<tr><td class=\"topbottombar\">";
	//-- the year is needed for alternate calendars
 	if (GedcomConfig::$CALENDAR_FORMAT != "gregorian") print GetChangedDate($calendar_controller->day." ". $calendar_controller->month." ". $calendar_controller->startyear);
	else print GetChangedDate($calendar_controller->day." ".$calendar_controller->month);
	if (GedcomConfig::$CALENDAR_FORMAT == "gregorian" && GedcomConfig::$USE_RTL_FUNCTIONS && $HEBREWFOUND[GedcomConfig::$GEDCOMID] == true) print " / ".GetChangedDate("@#DHEBREW@ ".$calendar_controller->hDay." ".$calendar_controller->hMonth." ".$calendar_controller->CalYear); 
}
else if ($calendar_controller->action == "calendar") {
	print GM_LANG_in_this_month."</h3></td></tr>\n";
	print "<tr><td class=\"topbottombar\">";
	print GetChangedDate(" ".$calendar_controller->month." ".$calendar_controller->year." ");
	if (GedcomConfig::$CALENDAR_FORMAT=="gregorian" && GedcomConfig::$USE_RTL_FUNCTIONS && $HEBREWFOUND[GedcomConfig::$GEDCOMID] == true) {
		$hdd = $calendar_controller->date[1]["day"];
		$hmm = $calendar_controller->date[1]["month"];
		$hyy = $calendar_controller->date[1]["year"];
		print " /  ".GetChangedDate("@#DHEBREW@ ".$hdd." ".$hmm." ".$hyy);
        if ($hmm!=$calendar_controller->date[2]["month"]) {
	            $hdd = $calendar_controller->date[2]["day"];
        		$hmm = $calendar_controller->date[2]["month"];
				$hyy = $calendar_controller->date[2]["year"];
				print " -".GetChangedDate("@#DHEBREW@ ".$hdd." ".$hmm." ".$hyy);
		}
    }
}
else if ($calendar_controller->action == "year") {
	print GM_LANG_in_this_year."</h3></td></tr>\n";
	print "<tr><td class=\"topbottombar\">";
	print GetChangedDate(" ".$calendar_controller->year_text." ");
	if (GedcomConfig::$CALENDAR_FORMAT == "gregorian" && GedcomConfig::$USE_RTL_FUNCTIONS && $HEBREWFOUND[GedcomConfig::$GEDCOMID] == true) {
		$hdd = $calendar_controller->date[3]["day"];
		$hmm = $calendar_controller->date[3]["month"];
		$hstartyear = $calendar_controller->date[3]["year"];
		print " /  ".GetChangedDate("@#DHEBREW@ ".$hdd." ".$hmm." ".$hstartyear);
	    $hdd = $calendar_controller->date[4]["day"];
        $hmm = $calendar_controller->date[4]["month"];
		$hendyear = $calendar_controller->date[4]["year"];
		print " -".GetChangedDate("@#DHEBREW@ ".$hdd." ".$hmm." ".$hendyear);
	}
}
	?>
    </td>
  </tr>
</table>
<?php
if ($view!="preview") {
// Print calender form
	print "<form name=\"dateform\" method=\"get\" action=\"calendar.php\">";
	print "<input type=\"hidden\" name=\"action\" value=\"".$calendar_controller->action."\" />";
	print "\n\t\t<table class=\"facts_table $TEXT_DIRECTION width100\">\n\t\t<tr>";
	print "<td class=\"shade2 vmiddle\">";
	PrintHelpLink("annivers_date_select_help", "qm", "day");
	print GM_LANG_day."</td>\n";
	print "<td colspan=\"7\" class=\"shade1\">";
	for($i=1; $i<($calendar_controller->m_days+1); $i++) {
		if (empty($dd)) $dd = $calendar_controller->day;
		print "<a href=\"calendar.php?day=".$i."&amp;month=".strtolower($calendar_controller->month)."&amp;year=".$calendar_controller->year."&amp;filterev=".$calendar_controller->filterev."&amp;filterof=".$calendar_controller->filterof."&amp;filtersx=".$calendar_controller->filtersx."&amp;action=today\">";
		if ($i==$dd) print "<span class=\"error\">$i</span>";
		else print $i;
		print "</a> | ";
	}
	$Dd = adodb_date("j");
	$Mm = adodb_date("M");
	$Yy = adodb_date("Y");
//	print "<a href=\"calendar.php?filterev=$calendar_controller->filterev&amp;filterof=$calendar_controller->filterof&amp;filtersx=$calendar_controller->filtersx\"><b>".GetChangedDate("$Dd $Mm $Yy")."</b></a> | ";
	//-- for alternate calendars the year is needed
  	if (GedcomConfig::$CALENDAR_FORMAT!="gregorian" || (GedcomConfig::$USE_RTL_FUNCTIONS && $HEBREWFOUND[GedcomConfig::$GEDCOMID] == true)) $datestr = "$Dd $Mm $Yy";
// 	if ($CALENDAR_FORMAT!="gregorian") $datestr = "$Dd $Mm $Yy"; // MA @@@
	else $datestr = "$Dd $Mm";
	print "<a href=\"calendar.php?filterev=".$calendar_controller->filterev."&amp;filterof=".$calendar_controller->filterof."&amp;filtersx=".$calendar_controller->filtersx."&amp;year=".$calendar_controller->year."\"><b>".GetChangedDate($datestr);
	if (GedcomConfig::$USE_RTL_FUNCTIONS && $HEBREWFOUND[GedcomConfig::$GEDCOMID] == true) {
		$hdatestr = "@#DHEBREW@ ".$calendar_controller->currhDay." ".$calendar_controller->currhMonth." ".$calendar_controller->currhYear;
		print " / ".GetChangedDate($hdatestr);
	}
	print "</b></a> | ";
	print "</td>\n";

	print "</tr><tr>";
	print "<td class=\"shade2 vmiddle\">";
	PrintHelpLink("annivers_month_select_help", "qm", "month");
	print GM_LANG_month."</td>\n";
	print "<td colspan=\"7\" class=\"shade1\">";
	foreach($monthtonum as $mon=>$num) {
		if (defined("GM_LANG_".$mon)) {
			if (empty($mm)) $mm=strtolower($calendar_controller->month);
			print "<a href=\"calendar.php?day=".$dd."&amp;month=".$mon."&amp;year=".$calendar_controller->year."&amp;filterev=".$calendar_controller->filterev."&amp;filterof=".$calendar_controller->filterof."&amp;filtersx=".$calendar_controller->filtersx."&amp;action=".($calendar_controller->action == "year" ? "calendar" : $calendar_controller->action)."\">";
			$monthstr = constant("GM_LANG_".$mon);
			if ($mon==$mm) print "<span class=\"error\">".$monthstr."</span>";
			else print $monthstr;
			print "</a> | ";
		}
	}

	print "<a href=\"calendar.php?month=".strtolower(adodb_date("M"))."&amp;action=calendar&amp;filterev=".$calendar_controller->filterev."&amp;filterof=".$calendar_controller->filterof."&amp;filtersx=".$calendar_controller->filtersx."\"><b>".constant("GM_LANG_".strtolower(adodb_date("M")))." ".strtolower(adodb_date("Y"))."</b></a> | ";
	print "</td>\n";
	print "</tr><tr>";
	print "<td class=\"shade2 vmiddle\">";
	PrintHelpLink("annivers_year_select_help", "qm", "year");
	print GM_LANG_year."</td>\n";
	print "<td class=\"shade1 vmiddle\">";
	if (strlen($calendar_controller->year)<5){
		if ($calendar_controller->year<"AA") print " <a href=\"calendar.php?day=".$calendar_controller->day."&amp;month=".$calendar_controller->month."&amp;year=".($calendar_controller->year-1)."&amp;action=".($calendar_controller->action == "calendar" ? "calendar" : "year")."&amp;filterev=".$calendar_controller->filterev."&amp;filterof=".$calendar_controller->filterof."&amp;filtersx=".$calendar_controller->filtersx."\" title=\"".($calendar_controller->year-1)."\" >-1</a> ";
	}
	print "<input type=\"text\" name=\"year\" value=\"".$calendar_controller->year."\" size=\"7\" />";
	if (strlen($calendar_controller->year) < 5){
		if ($calendar_controller->year<(adodb_date("Y"))) print " <a href=\"calendar.php?day=".$calendar_controller->day."&amp;month=".$calendar_controller->month."&amp;year=".($calendar_controller->year+1)."&amp;action=".($calendar_controller->action == "calendar" ? "calendar" : "year")."&amp;filterev=".$calendar_controller->filterev."&amp;filterof=".$calendar_controller->filterof."&amp;filtersx=".$calendar_controller->filtersx."\" title=\"".($calendar_controller->year+1)."\" >+1</a> |";
		else if ($calendar_controller->year < "AA") print " +1 |";
	}
	print " <a href=\"calendar.php?day=".$calendar_controller->day."&amp;month=".$calendar_controller->month."&amp;year=".adodb_date("Y")."&amp;action=".($calendar_controller->action == "calendar" ? "calendar" : "year")."&amp;filterev=".$calendar_controller->filterev."&amp;filterof=".$calendar_controller->filterof."&amp;filtersx=".$calendar_controller->filtersx."\"><b>".strtolower(adodb_date("Y"))."</b></a> | ";

	print "</td>\n ";
	if ($HIDE_LIVE_PEOPLE >= $gm_user->GetUserAccessLevel()) {
		print "<td class=\"shade2 vmiddle\">";
		PrintHelpLink("annivers_show_help", "qm", "show");
		print GM_LANG_show.":&nbsp;</td>\n";
		print "<td class=\"shade1 vmiddle\">";

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
		print "<td class=\"shade2 vmiddle\">".GM_LANG_showcal."</td>\n";
		print "<td colspan=\"5\" class=\"shade1 vmiddle\">";
		if ($calendar_controller->filterof=="all") print "<span class=\"error\">".GM_LANG_all_people. "</span> | ";
		else {
			$filt="all";
			print "<a href=\"calendar.php?day=$dd&amp;month=".$calendar_controller->month."&amp;year=".$calendar_controller->year."&amp;filterof=".$filt."&amp;filtersx=".$calendar_controller->filtersx."&amp;action=".$calendar_controller->action."\">".htmlentities(GM_LANG_all_people)."</a>"." | ";
		}
		if ($calendar_controller->filterof == "recent") print "<span class=\"error\">".htmlentities(GM_LANG_recent_events). "</span> | ";
		else {
			$filt = "recent";
			print "<a href=\"calendar.php?day=".$dd."&amp;month=".$calendar_controller->month."&amp;year=".$calendar_controller->year."&amp;filterof=".$filt."&amp;filtersx=".$calendar_controller->filtersx."&amp;action=".$calendar_controller->action."\">".htmlentities(GM_LANG_recent_events)."</a>"." | ";
		}
	}
	

	if ($HIDE_LIVE_PEOPLE >= $gm_user->GetUserAccessLevel()) {
		print "</td>\n ";
		print "<td class=\"shade2 vmiddle\">";
		PrintHelpLink("annivers_sex_help", "qm", "sex");
		print GM_LANG_sex.":&nbsp;</td>\n";
		print "<td class=\"shade1 vmiddle\">";
		if ($calendar_controller->filtersx==""){
			print " <img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sex"]["small"]."\" title=\"".GM_LANG_all."\" alt=\"".GM_LANG_all."\" width=\"15\" height=\"15\" border=\"0\" align=\"middle\" />";
			print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sexf"]["small"]."\" title=\"".GM_LANG_all."\" alt=\"".GM_LANG_all."\" width=\"15\" height=\"15\" border=\"0\" align=\"middle\" />";
			print " | ";
		}
		else {
			$fs="";
			print " <a href=\"calendar.php?day=".$dd."&amp;month=".$calendar_controller->month."&amp;year=".$calendar_controller->year."&amp;filterev=".$calendar_controller->filterev."&amp;filterof=".$calendar_controller->filterof."&amp;filtersx=".$fs."&amp;action=".$calendar_controller->action."\">";
			print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sex"]["small"]."\" title=\"".GM_LANG_all."\" alt=\"".GM_LANG_all."\" width=\"9\" height=\"9\" border=\"0\" align=\"middle\" />";
			print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sexf"]["small"]."\" title=\"".GM_LANG_all."\" alt=\"".GM_LANG_all."\" width=\"9\" height=\"9\" border=\"0\" align=\"middle\" />";
			print "</a>"." | ";
		}
		if ($calendar_controller->filtersx=="M"){
			print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sex"]["small"]."\" title=\"".GM_LANG_male."\" alt=\"".GM_LANG_male."\" width=\"15\" height=\"15\" border=\"0\" align=\"middle\" />";
			print " | ";
		}
		else {
			$fs="M";
			print "<a href=\"calendar.php?day=".$dd."&amp;month=".$calendar_controller->month."&amp;year=".$calendar_controller->year."&amp;filterev=".$calendar_controller->filterev."&amp;filterof=".$calendar_controller->filterof."&amp;filtersx=".$fs."&amp;action=".$calendar_controller->action."\">";
			print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sex"]["small"]."\" title=\"".GM_LANG_male."\" alt=\"".GM_LANG_male."\" width=\"9\" height=\"9\" border=\"0\" align=\"middle\" />";
			print "</a>"." | ";
		}
		if ($calendar_controller->filtersx=="F"){
			print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sexf"]["small"]."\" title=\"".GM_LANG_female."\" alt=\"".GM_LANG_female."\" width=\"15\" height=\"15\" border=\"0\" align=\"middle\" />";
			print " | ";
		}
		else {
			$fs="F";
			print "<a href=\"calendar.php?day=".$dd."&amp;month=".$calendar_controller->month."&amp;year=".$calendar_controller->year."&amp;filterev=".$calendar_controller->filterev."&amp;filterof=".$calendar_controller->filterof."&amp;filtersx=".$fs."&amp;action=".$calendar_controller->action."\">";
			print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sexf"]["small"]."\" title=\"".GM_LANG_female."\" alt=\"".GM_LANG_female."\" width=\"9\" height=\"9\" border=\"0\" align=\"middle\" />";
			print "</a>"." | ";
		}
		
	}

	if ($HIDE_LIVE_PEOPLE >= $gm_user->GetUserAccessLevel()) {
		print "</td>\n ";
		print "<td class=\"shade2 vmiddle\">";
		PrintHelpLink("annivers_event_help", "qm", "showcal");
		print GM_LANG_showcal."&nbsp;</td>\n";
		print "<td class=\"shade1\"";
		if ($HIDE_LIVE_PEOPLE >= $gm_user->GetUserAccessLevel()) print ">";
		else print " colspan=\"3\">";
		print "<input type=\"hidden\" name=\"filterev\" value=\"".$calendar_controller->filterev."\" />";
		print "<select name=\"filterev\" onchange=\"document.dateform.submit();\">\n";
		
		print "<option value=\"bdm\"";
		if ($calendar_controller->filterev == "bdm") print " selected=\"selected\"";
		print ">".GM_LANG_bdm."</option>\n";
		
		PrintFilterEvent($calendar_controller->filterev);
	}

	print "</td>\n";
	print "</tr>";
	print "<tr><td class=\"topbottombar\" colspan=\"8\">";
	PrintHelpLink("day_month_help", "qm");
	print "<input type=\"hidden\" name=\"day\" value=\"".$dd."\" />";
	print "<input type=\"hidden\" name=\"month\" value=\"".$mm."\" />";
	print "<input type=\"hidden\" name=\"filtersx\" value=\"".$calendar_controller->filtersx."\" />";
	print "<input type=\"submit\"  value=\"".GM_LANG_viewday."\" onclick=\"document.dateform.elements['action'].value='today';\" />\n";
	print "<input type=\"submit\"  value=\"".GM_LANG_viewmonth."\" onclick=\"document.dateform.elements['action'].value='calendar';\" />\n";
	print "<input type=\"submit\"  value=\"".GM_LANG_viewyear."\" onclick=\"document.dateform.elements['action'].value='year';\" />\n";
	print "</td></tr></table><br />";
	print "</form>\n";
	
	
}
if ($calendar_controller->action == "today" || $calendar_controller->action == "year") {
	$myindilist = array();
	$myfamlist = array();
	$famfacts = array("_COML", "MARR", "DIV", "EVEN");
	$findindis = true;
	$findfams = true;
	if ($calendar_controller->filterev == "bdm") {
		$selindifacts = "('BIRT', 'DEAT')";
		$selfamfacts = "('MARR')";
	}
	else if ($calendar_controller->filterev != "all" && $calendar_controller->filterev != "") {
		$selfacts = $calendar_controller->filterev;
		if (in_array($calendar_controller->filterev, $famfacts)) {
			$findindis = false;
			$selfamfacts = "('".$calendar_controller->filterev."')";
		}
		else {
			$selindifacts = "('".$calendar_controller->filterev."')";
			$findfams = false;
		}
	}
	else {
		$selindifacts = "";
		$selfamfacts = "";
	}
	
	if ($calendar_controller->action == "year"){
		if ($calendar_controller->year_query != null) $calendar_controller->year = $calendar_controller->year_query;
		$calendar_controller->pregquery = "2 DATE[^\n]*(bet|".$calendar_controller->year.")";
		$query = "2 DATE[^\n]*(bet|".$calendar_controller->year.")";
		$pregquery1 = "2 DATE[^\n]*".$calendar_controller->year;
		$query1 = "2 DATE[^\n]*".$calendar_controller->year;
		
		if ($findindis) $myindilist = SearchFunctions::SearchIndisYearRange($calendar_controller->startyear,$calendar_controller->endyear, false, $selindifacts);
		if ($findfams) $myfamlist = SearchFunctions::SearchFamsYearRange($calendar_controller->startyear,$calendar_controller->endyear, false, $selfamfacts);
		if (GedcomConfig::$USE_RTL_FUNCTIONS && isset($hstartyear) && isset($hendyear)) {
			
			if ($findindis) {
				$myindilist1 = SearchFunctions::SearchIndisYearRange($hstartyear, $hendyear, false, $selindifacts);
				$myindilist = GmArrayMerge($myindilist, $myindilist1);
			}
			
			if ($findfams) {
				$myfamlist1 = SearchFunctions::SearchFamsYearRange($hstartyear, $hendyear, false, $selfamfacts);
				$myfamlist = GmArrayMerge($myfamlist, $myfamlist1);
			}
		}
	}
	if ($calendar_controller->action == "today") {
		if (GedcomConfig::$USE_RTL_FUNCTIONS) {
			if ($findindis) {
				$myindilist1 = SearchFunctions::SearchIndisDates($dd, $calendar_controller->month, "", $selindifacts);
				$myindilist = GmArrayMerge($myindilist, $myindilist1);
			}
		
			if ($findfams) {
				$myfamlist1 = SearchFunctions::SearchFamsDates($dd, $calendar_controller->month, "", $selfamfacts);
				$myfamlist = GmArrayMerge($myfamlist, $myfamlist1);
			}
		}
		else {
			if ($findindis) $myindilist = SearchFunctions::SearchIndisDates($dd, $calendar_controller->month, "", $selindifacts);
			if ($findfams) $myfamlist = SearchFunctions::SearchFamsDates($dd, $calendar_controller->month, "", $selfamfacts);
        }

		if (GedcomConfig::$USE_RTL_FUNCTIONS && isset($queryhb) && $calendar_controller->action!="year") {
			if ($findindis) {
				$myindilist1 = SearchFunctions::SearchIndis($queryhb);
				$myindilist = GmArrayMerge($myindilist, $myindilist1);
			}
			
			if ($findfams) {
				$myfamlist1 = SearchFunctions::SearchFams($queryhb);
				$myfamlist = GmArrayMerge($myfamlist, $myfamlist1);
			}
		}	
	}
	if (isset($query1)) {
		$query = $query1;
		$calendar_controller->pregquery = $pregquery1;
	}
	
 //print "indicount: ".count($myindilist)."<br />";
 //print "famcount: ".count($myfamlist)."<br />";

	uasort($myindilist, "ItemSort");
	if ($calendar_controller->filtersx == "") uasort($myfamlist, "ItemSort");
	$count_private_indi=array();
	$count_indi=0;
	$count_male=0;
	$count_female=0;
	$count_unknown=0;
	$text_indi="";
	$sx=1;
	if ($calendar_controller->filterev == "bdm") $select = array("BIRT", "DEAT");
	else if ($calendar_controller->filterev == "all") $select = "";
	else $select = array($calendar_controller->filterev);
	foreach($myindilist as $gid=>$indi) {
		if ((($calendar_controller->filterof == "living" && !$indi->isdead) || $calendar_controller->filterof != "living") && (($calendar_controller->filtersx != "") && $indi->sex == $calendar_controller->filtersx) || $calendar_controller->filtersx == "") {
			$filterout=false;
			$text_fact = "";
			$indifacts = $indi->SelectFacts($select, true);
//			print "check ".$indi->xref." ".$indi->name."<br >";
			foreach($indifacts as $index => $factobj) {
//				print "check ".$factobj->fact."<br />";
				$text_temp = "";
				
				$t1 = preg_match("/2 DATE.*DHEBREW.* (\d\d\d\d)/i", $factobj->factrec, $m1);
				if (GedcomConfig::$USE_RTL_FUNCTIONS && $calendar_controller->action == "year" && isset($hendyear) && $hendyear>0 && $t1>0) {
					if ($m1[1]==$hstartyear || $m1[1]==$hendyear) {
						// verify if the date falls within the first or the last range gregorian year @@@@ !!!!
						$fdate = $factobj->datestring;
						if ($fdate != "") {
							$hdate = ParseDate(trim($fdate));
							$gdate = JewishGedcomDateToGregorian($hdate);

                    	    $gyear=$gdate[0]["year"]; 

							if ($gyear>=$gstartyear && $gyear<=$gendyear) $hprocess=true;
							else $hprocess=false;
						}
						else $hprocess=false;
					}
					else if ($m1[1]>$hstartyear && $m1[1]<$hendyear) $hprocess = true;
					     else $hprocess=false;
					if ($hprocess) $text_temp .= FactFunctions::GetCalendarFact($factobj, $calendar_controller->action, $calendar_controller->filterof, $calendar_controller->filterev, $calendar_controller->year, $calendar_controller->month, $calendar_controller->day, $calendar_controller->CalYear, $calendar_controller->currhYear);
					else $text_temp .="filter";
				}
				else 
				if ($calendar_controller->endyear > 0) {
					$t1 = preg_match("/2 DATE.* (\d\d\d\d)/i", $factobj->factrec, $m1);
					if (($t1 > 0) && ($m1[1] >= $calendar_controller->startyear) && ($m1[1] <= $calendar_controller->endyear)){
						$text_temp .= FactFunctions::GetCalendarFact($factobj, $calendar_controller->action, $calendar_controller->filterof, $calendar_controller->filterev, $calendar_controller->year, $calendar_controller->month, $calendar_controller->day, $calendar_controller->CalYear, $calendar_controller->currhYear);
					}
					else {  
						$t2 = preg_match("/2 DATE.* (\d\d\d)/i", $factobj->factrec, $m2);
						if (($t2 > 0) && ($m2[1] >= $calendar_controller->startyear) && ($m2[1] <= $calendar_controller->endyear)){									
							$text_temp .= FactFunctions::GetCalendarFact($factobj, $calendar_controller->action, $calendar_controller->filterof, $calendar_controller->filterev, $calendar_controller->year, $calendar_controller->month, $calendar_controller->day, $calendar_controller->CalYear, $calendar_controller->currhYear);
						}
					}
				}
				else {
					$ct = preg_match("/$calendar_controller->pregquery/i", $factobj->factrec, $match);
					if ($calendar_controller->action == "year"){
						if ($ct == 0){
							$cb = preg_match("/2 DATE[^\n]*(bet)/i", $factobj->factrec, $m1);
							if ($cb>0) {
								$cy = preg_match("/DATE.* [\d]{3,4}/i", $factobj->factrec, $m2);
								if ($cy>0) {
									$numbers = preg_split("/[^\d]{4,9}/i", $m2[0]);
									$years = array();
									if (count($numbers) > 2){
										$y=0;
										foreach($numbers as $key => $value) {
											if (!($value > 0 && $value < 32) && $value != ""){
												$years[$y] = $value;
												$y++;
											}
										}
									}
									if (!isset($years[0])) $years[0] = 0;
									if (!isset($years[1])) $years[1] = 0;
									if ($years[0] < $calendar_controller->year && $years[1] > $calendar_controller->year) $ct = 1;
									else $text_temp .= "filter";
								}
								else $text_temp .= "filter";
							}
						}
					}
					if ($ct < 1 && GedcomConfig::$USE_RTL_FUNCTIONS && isset($preghbquery)) $ct = preg_match("/$preghbquery/i", $factobj->factrec, $match);
					if ($calendar_controller->action == "year"){
						if ($ct == 0){
							$cb = preg_match("/2 DATE[^\n]*(bet)/i", $factobj->factrec, $m1);
							if ($cb>0) {
								$cy = preg_match("/DATE.* [\d]{3,4}/i", $factobj->factrec, $m2);
								if ($cy>0) {											
									$numbers = preg_split("/[^\d]{4,9}/i", $m2[0]);
									$years = array();
									if (count($numbers) > 2){
										$y = 0;
										foreach($numbers as $key => $value) {
											if (!($value > 0 && $value < 32) && $value != ""){
												$years[$y]=$value;
												$y++;
											}
										}
									}
									if (!isset($years[0])) $years[0] = 0;
									if (!isset($years[1])) $years[1] = 0;
									if ($years[0] < $calendar_controller->year && $years[1] > $calendar_controller->year) $ct = 1;
									else $text_temp .= "filter";
								}
								else $text_temp .= "filter";
									
							}
						}
					}
					if ($ct>0) $text_temp .= FactFunctions::GetCalendarFact($factobj, $calendar_controller->action, $calendar_controller->filterof, $calendar_controller->filterev, $calendar_controller->year, $calendar_controller->month, $calendar_controller->day, $calendar_controller->CalYear, $calendar_controller->currhYear);
                }						
 				if ($text_temp == "filter") $filterout = true; 
 				else if ($text_temp == "filterfilter") $filterout = true;
				else $text_fact .= $text_temp;
			} // end fact loop
//print "owner: ".$factobj->owner->xref." fact: ".$factobj->factrec." ".$text_temp."<br />";
			if (!empty($text_fact)) {
				
				// $text_indi .= "<tr><td>";
				$text_indi .= "<li><a href=\"individual.php?pid=".$indi->xref."&amp;gedid=".$indi->gedcomid."\"><b>";
				$text_indi .= $indi->revname;
				if ($indi->addname != "") $text_indi .= "&nbsp;(".$indi->revaddname.")";
				$text_indi .= "</b><img id=\"box-".$indi->xref.".".$index."-sex\" src=\"".GM_IMAGE_DIR."/";
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
				$text_indi .= "\" class=\"sex_image\" />";
				$text_indi .= $indi->addxref;
				$text_indi .= "</a><br />\n\t\t";
				$text_indi .= "<div class=\"indent";
				if($TEXT_DIRECTION == "rtl") $text_indi .= "_rtl";
				$text_indi .= "\">";
				$text_indi .= $text_fact;
				$text_indi .= "<br /></div></li>\n\t\t";
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
		foreach($myfamlist as $gid=>$fam) {
			$display=true;
			if ($calendar_controller->filterof=="living") {
				if (($fam->husb_id != "" && !$fam->husb->isdead) && ($fam->wife_id != "" && !$fam->wife->isdead)) $display = true;
				else $display = false;
			}
  			if ($display) {
				$filterout=false;
				$text_fact = "";
				$famfacts = $fam->SelectFacts($select, true);
				foreach ($famfacts as $index => $factobj) {
					$text_temp = "";
					$t1 = preg_match("/2 DATE.*DHEBREW.* (\d\d\d\d)/i", $factobj->factrec, $m1);
					if (GedcomConfig::$USE_RTL_FUNCTIONS && $calendar_controller->action=="year" && isset($hendyear) && $hendyear>0 && $t1>0) {
						if ($m1[1]==$hstartyear || $m1[1]==$hendyear) {
						// verify if the date falls within the first or the last range gregorian year @@@@ !!!!
						// find gregorian year of the fact hebrew date
							$fdate = $factobj->datestring;
							if ($fdate != "") {
								$hdate = ParseDate(trim($fdate));
								$gdate = JewishGedcomDateToGregorian($hdate);

                        	    $gyear=$gdate[0]["year"]; 

								if ($gyear>=$gstartyear && $gyear<=$gendyear) $hprocess=true;
								else $hprocess=false;
							}
							else $hprocess=false;
						}
						else if ($m1[1]>$hstartyear && $m1[1]<$hendyear) $hprocess = true;
						     else $hprocess=false;
						if ($hprocess) $text_temp .= FactFunctions::GetCalendarFact($factobj, $calendar_controller->action, $calendar_controller->filterof, $calendar_controller->filterev, $calendar_controller->year, $calendar_controller->month, $calendar_controller->day, $calendar_controller->CalYear, $calendar_controller->currhYear);
                        else $text_temp = "filter";
					}
				    else 
					if ($calendar_controller->endyear>0){
						$t1 = preg_match("/2 DATE.* (\d\d\d\d)/i", $factobj->factrec, $m1);
						if (($t1 > 0) && ($m1[1] >= $calendar_controller->startyear) && ($m1[1] <= $calendar_controller->endyear)){
							$text_temp .= FactFunctions::GetCalendarFact($factobj, $calendar_controller->action, $calendar_controller->filterof, $calendar_controller->filterev, $calendar_controller->year, $calendar_controller->month, $calendar_controller->day, $calendar_controller->CalYear, $calendar_controller->currhYear);
						}
						else {
							$t2 = preg_match("/2 DATE.* (\d\d\d)/i", $factobj->factrec, $m2);
							if (($t2 > 0) && ($m2[1] >= $calendar_controller->startyear) && ($m2[1] <= $calendar_controller->endyear)){
								$text_temp .= FactFunctions::GetCalendarFact($factobj, $calendar_controller->action, $calendar_controller->filterof, $calendar_controller->filterev, $calendar_controller->year, $calendar_controller->month, $calendar_controller->day, $calendar_controller->CalYear, $calendar_controller->currhYear);
							}
						}
					}
					else {
						$ct = preg_match("/$calendar_controller->pregquery/i", $factobj->factrec, $match);
						if ($calendar_controller->action == "year"){
							if ($ct == 0){
								$cb = preg_match("/2 DATE[^\n]*(bet)/i", $factobj->factrec, $m1);
								if ($cb > 0) {
									$cy = preg_match("/DATE.* [\d]{3,4}/i", $factobj->factrec, $m2);
									if ($cy > 0) {
										$numbers = preg_split("/[^\d]{4,9}/i", $m2[0]);
										$years= array();
										if (count($numbers) > 2){
											$y=0;
											foreach($numbers as $key => $value) {
												if (!($value > 0 && $value < 32) && $value != ""){
													$years[$y] = $value;
													$y++;
												}
											}
										}
										if (!isset($years[0])) $years[0] = 0;
										if (!isset($years[1])) $years[1] = 0;
										if ($years[0] < $calendar_controller->year && $years[1] > $calendar_controller->year) $ct = 1;
										else $text_temp="filter";
									}
								}
							}
						}
						if ($ct < 1 && GedcomConfig::$USE_RTL_FUNCTIONS && isset($preghbquery)) $ct = preg_match("/$preghbquery/i", $factobj->factrec, $match);
						if ($calendar_controller->action == "year"){
							if ($ct == 0){
								$cb = preg_match("/2 DATE[^\n]*(bet)/i", $factobj->factrec, $m1);
								if ($cb > 0) {
									$cy = preg_match("/DATE.* [\d]{3,4}/i", $factobj->factrec, $m2);
									if ($cy > 0) {
										$numbers = preg_split("/[^\d]{4,9}/i", $m2[0]);
										$years = array();
										if (count($numbers) > 2){
											$y = 0;
											foreach($numbers as $key => $value) {
												if (!($value > 0 && $value < 32) && $value != ""){
													$years[$y] = $value;
													$y++;
												}
											}
										}
										if (!isset($years[0])) $years[0] = 0;
										if (!isset($years[1])) $years[1] = 0;
										if ($years[0] < $calendar_controller->year && $years[1] > $calendar_controller->year) $ct = 1;
										else $text_temp = "filter";
									}
								}
							}
						}
						if ($ct>0) $text_temp .= FactFunctions::GetCalendarFact($factobj, $calendar_controller->action, $calendar_controller->filterof, $calendar_controller->filterev, $calendar_controller->year, $calendar_controller->month, $calendar_controller->day, $calendar_controller->CalYear, $calendar_controller->currhYear);
					}
					if ($text_temp == "filter") $filterout = true;
					else $text_fact .= $text_temp;
				} // end loop famfacts
				if (!empty($text_fact) && $fam->disp) {
					$text_fam .= "<li><a href=\"family.php?famid=".$fam->xref."&amp;gedid=".$fam->gedcomid."\"><b>";
					$text_fam .= $fam->sortable_name;
					if ($fam->sortable_addname != "") $text_fam .= "&nbsp(".$fam->sortable_addname.")";
					$text_fam .= "</b>".$fam->addxref;
					$text_fam .= "</a><br />\n\t\t";
					$text_fam .= "<div class=\"indent";
					if ($TEXT_DIRECTION == "rtl") $text_fam .= "_rtl";
					$text_fam .= "\">";
					$text_fam .= $text_fact;
					$text_fam .= "<br /></div></li>\n\t\t";
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
		print "\n\t\t<table class=\"center $TEXT_DIRECTION\">\n\t\t<tr>";
		if (!empty($text_indi) || ($count_private_indi>0)) {
			print "<td class=\"shade2 center\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]."\" border=\"0\" title=\"".GM_LANG_individuals."\" alt=\"".GM_LANG_individuals."\" /> ".GM_LANG_individuals."</td>";
		}
		if (!empty($text_fam) || ($count_private_fam)) {
			print "<td class=\"shade2 center\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sfamily"]["small"]."\" border=\"0\" title=\"".GM_LANG_families."\" alt=\"".GM_LANG_families."\" /> ".GM_LANG_families."</td>";
		}
		print "</tr><tr>\n\t\t";
		if (!empty($text_indi) || ($count_private_indi)) {
			print "<td class=\"shade1\"><ul>\n\t\t";
			if (!empty($text_indi)) print $text_indi;
			if ($count_private_indi>0){
				PrintHelpLink("privacy_error_help", "qm", "private");
				print "<li><b>";
				print GM_LANG_private;
				print "</b> (".$count_private_indi.") ";
				print "</li>\n\t\t";
			}
			print "</ul></td>";
		}
		if (!empty($text_fam) || ($count_private_fam)) {
			print "<td class=\"shade1\"><ul>\n\t\t";
			if (!empty($text_fam)) print $text_fam;
			if ($count_private_fam>0){
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
			print "<td class=\"shade2\">\n";
			if (($count_male+$count_female+$count_unknown+$count_private_indi)>0)
			print GM_LANG_total_indis." ".($count_male+$count_female+$count_unknown+$count_private_indi)."<br />&nbsp;&nbsp;";
			if ($count_male>0){
				print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sex"]["small"]."\" ";
				print "title=\"".GM_LANG_male."\" alt=\"".GM_LANG_male."\" class=\"sex_image\" />&nbsp;";
				print $count_male;
			}
			if (($count_male>0)&&($count_female>0)) print "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			if ($count_female>0) {
				print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sexf"]["small"]."\" ";
				print "title=\"".GM_LANG_female."\" alt=\"".GM_LANG_female."\" class=\"sex_image\"  />&nbsp;";
				print $count_female;
			}
			if ($count_unknown>0) {
				print "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
				print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sexn"]["small"]."\" ";
				print "title=\"".GM_LANG_unknown."\" alt=\"".GM_LANG_unknown."\" class=\"sex_image\" />&nbsp;";
				print $count_unknown;
			}
			print "</td>";
		}
		if (($count_fam>0)||($count_private_fam>0)) {
			print "<td class=\"shade2\">\n";
			if (($count_fam>0)||($count_private_fam>0)) print GM_LANG_total_fams;
			print "&nbsp;&nbsp;".($count_fam+$count_private_fam);
			print "</td>";
		}
		print "</tr>";
	}
	else {
		print "\n\t<table class=\"list_table center $TEXT_DIRECTION\">\n\t\t<tr>";
		print "<td class=\"shade1 center\">&nbsp;";
		print GM_LANG_individuals."  /  ".GM_LANG_families;
		print "&nbsp;</td></tr><tr><td class=\"warning center\"><i>";
		print GM_LANG_no_results;
		print "</i><br />\n\t\t</td></tr>";
	}
	if ($view=="preview") print "<tr><td>";
}
else if ($calendar_controller->action == "calendar") {
	if(GedcomConfig::$CALENDAR_FORMAT == "jewish" || GedcomConfig::$CALENDAR_FORMAT == "hebrew" || GedcomConfig::$CALENDAR_FORMAT == "hijri") { //since calendar is based on gregorian it doesn't make sense to not display the gregorian caption
		print "<span class=\"subheaders\">".constant("GM_LANG_".strtolower($calendar_controller->month))." ".$calendar_controller->year."</span> &#160; \n";
	}
	if (empty($WEEK_START)) $WEEK_START="0";                //-- if the starting day for a week was not defined in the language file, then make it Sunday
	print "<table class=\"list_table center $TEXT_DIRECTION\">\n";
	print "\t<tr>\n";
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
		print "\t\t<td class=\"shade2\">".constant("GM_LANG_".$days[$j])."</td>\n";
		$j++;
		if ($j>6) $j=0;
	}
	print "\t</tr>\n";
	$monthstart 	= adodb_mktime(1,0,0,$monthtonum[strtolower($calendar_controller->month)],1,$calendar_controller->year);
	$startday 		= adodb_date("w", $monthstart);
	$endday 		= adodb_dow($calendar_controller->year,$monthtonum[strtolower($calendar_controller->month)],adodb_date("t", $monthstart));
	$lastday		= adodb_date("t", $monthstart);
	$mmon 			= strtolower(adodb_date("M", $monthstart));
	$monthstart 	= $monthstart-(60*60*24*$startday);
	if($WEEK_START<=$startday)
		$monthstart += $WEEK_START*(60*60*24);
	else //week start > $startday
		$monthstart -= (7-$WEEK_START)*(60*60*24);
	if (($endday == 6 && $WEEK_START == 0) || ($endday == 0 && $WEEK_START == 1)) $show_no_day = 0;
	else $show_no_day=6;
	if ((($startday==0 && $WEEK_START == 0) || ($startday == 2 && $WEEK_START == 1)) && $show_no_day == 0) $show_no_day = 6;
	$show_not_set = false;
	$lastday -= 29;   
	if ($lastday < 0) $lastday = 0;
	$myindilist = array();
	$myfamlist = array();
	$calendar_controller->pregquery = "2 DATE[^\n]*".$mmon;
	$query = "2 DATE[^\n]*".$mmon;

	$fact = "";	

	$famfacts = array("_COML", "MARR", "DIV", "EVEN");
	$findindis = true;
	$findfams = true;
	if ($calendar_controller->filterev == "bdm") $fact = "('BIRT', 'DEAT', 'MARR')";
	else if ($calendar_controller->filterev != "all" && !empty($calendar_controller->filterev)) {
		$fact = "('".$calendar_controller->filterev."')";
		if (in_array($calendar_controller->filterev, $famfacts)) $findindis = false;
		else $findfams = false;
	}
	else $fact = "";
	
	if ($findindis) $myindilist = SearchFunctions::SearchIndisDates("", $mmon, "", $fact);
	if ($findfams) $myfamlist = SearchFunctions::SearchFamsDates("", $mmon, "", $fact);

	if (GedcomConfig::$USE_RTL_FUNCTIONS) {
		$datearray[0]["day"]   = 01;
 		$datearray[0]["mon"]   = $monthtonum[Str2Lower($calendar_controller->month)];	
 		$datearray[0]["year"]  = $calendar_controller->year;
 		$datearray[0]["month"] = $calendar_controller->month;
 		$datearray[1]["day"]   = 15;
 		$datearray[1]["mon"]   = $monthtonum[Str2Lower($calendar_controller->month)];	
 		$datearray[1]["year"]  = $calendar_controller->year;
 		$datearray[1]["month"] = $calendar_controller->month;
 		$datearray[2]["day"]   = adodb_date("t", $monthstart);
 		$datearray[2]["mon"]   = $monthtonum[Str2Lower($calendar_controller->month)];	
 		$datearray[2]["year"]  = $calendar_controller->year;
 		$datearray[2]["month"] = $calendar_controller->month;

		$date   = GregorianToJewishGedcomDate($datearray);
		$HBMonth1 = $date[0]["month"];
		$HBYear1  = $date[0]["year"];
		$HBMonth2 = $date[1]["month"];
		$HBMonth3 = $date[2]["month"];
		
		$preghbquery1 = "2 DATE[^\n]*$HBMonth1";
		$query1 = "2 DATE[^\n]*$HBMonth1";
		
		if ($findindis) $myindilist1 = SearchFunctions::SearchIndisDates("", $HBMonth1, "", $fact);
		if ($findfams) $myfamlist1 = SearchFunctions::SearchFamsDates("", $HBMonth1, "", $fact);
			
		if ($findindis) $myindilist = GmArrayMerge($myindilist, $myindilist1);
		if ($findfams) $myfamlist  = GmArrayMerge($myfamlist, $myfamlist1);
		
		if ($HBMonth1 != $HBMonth2) {		
			$preghbquery2 = "2 DATE[^\n]*$HBMonth2";
				
			if ($findindis) $myindilist1 = SearchFunctions::SearchIndisDates("", $HBMonth2, "", $fact);
			if ($findfams) $myfamlist1 = SearchFunctions::SearchFamsDates("", $HBMonth2, "", $fact);
			
			if ($findindis) $myindilist = GmArrayMerge($myindilist, $myindilist1);
			if ($findfams) $myfamlist  = GmArrayMerge($myfamlist, $myfamlist1);
		}
		
		if ($HBMonth2 != $HBMonth3) {		
			$preghbquery3 = "2 DATE[^\n]*$HBMonth3";
				
			if ($findindis) $myindilist1 = SearchFunctions::SearchIndisDates("", $HBMonth3, "", $fact);
			if ($findfams) $myfamlist1 = SearchFunctions::SearchFamsDates("", $HBMonth3, "", $fact);
			
			if ($findindis) $myindilist = GmArrayMerge($myindilist, $myindilist1);
			if ($findfams) $myfamlist  = GmArrayMerge($myfamlist, $myfamlist1);
		}
		
		if (!IsJewishLeapYear($HBYear1) && ($HBMonth1 == "adr" || $HBMonth2 == "adr" || $HBMonth3 == "adr")) {
			$HBMonth4 = "ads"; 
		
			if ($findindis) $myindilist1 = SearchFunctions::SearchIndisDates("", $HBMonth4, "", $fact);
			if ($findfams) $myfamlist1 = SearchFunctions::SearchFamsDates("", $HBMonth4, "", $fact);
			
			if ($findindis) $myindilist = GmArrayMerge($myindilist, $myindilist1);
			if ($findfams) $myfamlist  = GmArrayMerge($myfamlist, $myfamlist1);
		}
	}
	
	uasort($myindilist, "ItemSort");
	for($k=0; $k<6; $k++) {
		print "\t<tr>\n";
		for($j=0; $j<7; $j++) {
			$mday = adodb_date("j", $monthstart);
			$mmon = strtolower(adodb_date("M", $monthstart));

			print "\t\t<td class=\"shade1 wrap\">\n";
			if ($show_no_day == 0 && $j == 0 && $k == 0) $show_not_set = true;
			else if ($show_no_day == $k && $j == 6) $show_not_set = true;
			if ($mmon == strtolower($calendar_controller->month) || $show_not_set) {
				if ($show_not_set) {
					$calendar_controller->pregquery = "2 DATE(|[^\n]*[^\d]+|[^\n]*([ |0]0)|[^\n]*3[$lastday-9]|[^\n]*[4-9][0-9]) $calendar_controller->month";
					
					// I see April 1973 in 2004 both correctly in April and in March with another event

					// Include here Hebrew dates that do not convert to a Gregorian date (same into blocks) - like 31 NSN 5724
					
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
					print "<span class=\"cal_day". ($currentDay?" current_day":"") ."\">".$mday."</span>";
					if (GedcomConfig::$CALENDAR_FORMAT == "hebrew_and_gregorian" || GedcomConfig::$CALENDAR_FORMAT == "hebrew" ||
						((GedcomConfig::$CALENDAR_FORMAT == "jewish_and_gregorian" || GedcomConfig::$CALENDAR_FORMAT == "jewish" || (GedcomConfig::$USE_RTL_FUNCTIONS &&  $HEBREWFOUND[GedcomConfig::$GEDCOMID] == true)) && $LANGUAGE == "hebrew")) {
						$monthTemp = $monthtonum[strtolower($calendar_controller->month)];
						$jd = gregoriantojd($monthTemp, $mday, $calendar_controller->year);
						$hebrewDate = jdtojewish($jd);
						// if (GedcomConfig::$USE_RTL_FUNCTIONS &&  $HEBREWFOUND[GedcomConfig::$GEDCOMID] == true) {
							list ($hebrewMonth, $hebrewDay, $hebrewYear) = split ('/', $hebrewDate);
							print "<span class=\"rtl_cal_day". ($currentDay?" current_day":"") ."\">";
							print GetHebrewJewishDay($hebrewDay) . " " .GetHebrewJewishMonth($hebrewMonth, $hebrewYear) . "</span>";
						// }
					}
					else if(GedcomConfig::$CALENDAR_FORMAT == "jewish_and_gregorian" || GedcomConfig::$CALENDAR_FORMAT == "jewish" || (GedcomConfig::$USE_RTL_FUNCTIONS && $HEBREWFOUND[GedcomConfig::$GEDCOMID] == true)) {
						// else if(GedcomConfig::$CALENDAR_FORMAT=="jewish_and_gregorian" || GedcomConfig::$CALENDAR_FORMAT=="jewish" || GedcomConfig::$USE_RTL_FUNCTIONS) {
						$monthTemp = $monthtonum[strtolower($calendar_controller->month)];
						$jd = gregoriantojd($monthTemp, $mday, $calendar_controller->year);
						$hebrewDate = jdtojewish($jd);
						// if (GedcomConfig::$USE_RTL_FUNCTIONS &&  $HEBREWFOUND[GedcomConfig::$GEDCOMID] == true) {
							list ($hebrewMonth, $hebrewDay, $hebrewYear) = split ('/', $hebrewDate);
							print "<span class=\"rtl_cal_day". ($currentDay?" current_day":"") ."\">";
							print $hebrewDay . " " . GetJewishMonthName($hebrewMonth, $hebrewYear) . "</span>";
						// }
					}
					else if(GedcomConfig::$CALENDAR_FORMAT == "hijri") {
						$monthTemp = $monthtonum[strtolower($calendar_controller->month)];
						$hDate = GetHijri($mday, $monthTemp, $calendar_controller->year);
						list ($hMonthName, $calendar_controller->hDay, $calendar_controller->hYear) = split ('/', $hDate);
						print "<span class=\"rtl_cal_day". ($currentDay?" current_day":"") ."\">";
						print $calendar_controller->hDay . " " . $hMonthName . "</span>";
					}
					print "<br style=\"clear: both\" />";
					$dayindilist = array();

					if ($mday<10) $calendar_controller->pregquery = "2 DATE[^\n]*[ |0]$mday $mmon";
					else if (!$calendar_controller->leap && $mmon == "feb" && $mday == '28') $calendar_controller->pregquery = "2 DATE[^\n]*2[8|9] $mmon";
					else $calendar_controller->pregquery = "2 DATE[^\n]*$mday $mmon";
				
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
				foreach($myindilist as $gid=>$indi) {
			
					if ((($calendar_controller->filterof == "living" && !$indi->isdead) || $calendar_controller->filterof != "living") && (($calendar_controller->filtersx != "" && $indi->sex == $calendar_controller->filtersx) || $calendar_controller->filtersx == "")) {
						
						if (preg_match("/$calendar_controller->pregquery/i", $indi->gedrec) > 0 || (GedcomConfig::$USE_RTL_FUNCTIONS && (preg_match("/$preghbquery/i", $indi->gedrec) > 0 || ($preghbquery1!="" && preg_match("/$preghbquery1/i", $indi->gedrec) > 0) || ($preghbquery2!="" && preg_match("/$preghbquery2/i", $indi->gedrec) > 0) || ($preghbquery3 != "" && preg_match("/$preghbquery3/i", $indi->gedrec) > 0)))) {
							$filterout=false;
							$indifacts = $indi->SelectFacts($select, true);
							$text_fact = "";
							foreach($indifacts as $index => $factobj) {
								$text_temp = "";
								$ct = preg_match("/$calendar_controller->pregquery/i", $factobj->factrec, $match);
								if ($ct < 1 && GedcomConfig::$USE_RTL_FUNCTIONS) $ct = preg_match("/$preghbquery/i", $factobj->factrec, $match);
								if ($ct < 1 && GedcomConfig::$USE_RTL_FUNCTIONS && $preghbquery1!="") $ct = preg_match("/$preghbquery1/i", $factobj->factrec, $match);
								if ($ct < 1 && GedcomConfig::$USE_RTL_FUNCTIONS && $preghbquery2!="") $ct = preg_match("/$preghbquery2/i", $factobj->factrec, $match);
								if ($ct < 1 && GedcomConfig::$USE_RTL_FUNCTIONS && $preghbquery3!="") $ct = preg_match("/$preghbquery3/i", $factobj->factrec, $match);
								if ($ct>0) {
									$text_temp .= FactFunctions::GetCalendarFact($factobj, $calendar_controller->action, $calendar_controller->filterof, $calendar_controller->filterev, $calendar_controller->year, $calendar_controller->month, $calendar_controller->day, $calendar_controller->CalYear, $calendar_controller->currhYear);
								}
								if ($text_temp=="filter") $filterout=true;
								else $text_fact .= $text_temp;
							}
							if (!empty($text_fact) && $indi->disp) {
								$text_day .= "<a href=\"individual.php?pid=".$indi->xref."&amp;gedid=".$indi->gedcomid."\"><b>";
								$text_day .= $indi->revname;
								if ($indi->addname != "") $text_day .= "&nbsp;(".$indi->revaddname.")";
								$text_day .= "</b>".$indi->addxref;
								$text_day .= "</a><br />\n";
								$text_day .= "<div class=\"indent";
								if($TEXT_DIRECTION == "rtl") $text_day .= "_rtl";
								$text_day .= " $TEXT_DIRECTION\">";
								$text_day .= $text_fact;
								$text_day .= "</div><br />\n";
							}
//							else if (!$filterout) $count_private++;
						}
					}
				}
				$dayfamlist = array();
				reset($myfamlist);
				$count_private_fam = 0;
				if ($calendar_controller->filterev == "bdm") $select = array("MARR");
				else if ($calendar_controller->filterev == "all") $select = "";
				else $select = array($calendar_controller->filterev);
				if ($calendar_controller->filtersx == ""){
					foreach($myfamlist as $gid=>$fam) {
						$display=true;
						if ($calendar_controller->filterof == "living"){
							if (($fam->husb_id != "" && $fam->husb->isdead) || ($fam->wife_id != "" && $fam->wife->isdead)) $display=false;
						}
						if ($display) {
			    			if (preg_match("/$calendar_controller->pregquery/i", $fam->gedrec) > 0 || (GedcomConfig::$USE_RTL_FUNCTIONS && (preg_match("/$preghbquery/i", $fam->gedrec) > 0 || ($preghbquery1!="" && preg_match("/$preghbquery1/i", $fam->gedrec) > 0) || ($preghbquery2!="" && preg_match("/$preghbquery2/i", $fam->gedrec) > 0) || ($preghbquery3!="" && preg_match("/$preghbquery3/i", $fam->gedrec) > 0)))) {
								
								$filterout = false;
								$famfacts = $fam->SelectFacts($select, true);
								$text_fact = "";
								foreach($famfacts as $index => $factobj) {
									$text_temp = "";
									$ct = preg_match("/$calendar_controller->pregquery/i", $factobj->factrec, $match);
									if ($ct < 1 && GedcomConfig::$USE_RTL_FUNCTIONS) $ct = preg_match("/$preghbquery/i", $factobj->factrec, $match);
									if ($ct < 1 && GedcomConfig::$USE_RTL_FUNCTIONS && $preghbquery1!="") $ct = preg_match("/$preghbquery1/i", $factobj->factrec, $match);
									if ($ct < 1 && GedcomConfig::$USE_RTL_FUNCTIONS && $preghbquery2!="") $ct = preg_match("/$preghbquery2/i", $factobj->factrec, $match);
									if ($ct < 1 && GedcomConfig::$USE_RTL_FUNCTIONS && $preghbquery3!="") $ct = preg_match("/$preghbquery3/i", $factobj->factrec, $match);
									if ($ct>0) {
										$text_temp .= FactFunctions::GetCalendarFact($factobj, $calendar_controller->action, $calendar_controller->filterof, $calendar_controller->filterev, $calendar_controller->year, $calendar_controller->month, $calendar_controller->day, $calendar_controller->CalYear, $calendar_controller->currhYear);
									}
									if ($text_temp=="filter") $filterout=true;
									else $text_fact .= $text_temp;
								}
								if (!empty($text_fact) && $fam->disp) {
									$text_day .= "<a href=\"family.php?famid=".$fam->xref."&amp;gedid=".$fam->gedcomid."\"><b>";
									$text_day .= $fam->sortable_name;
									if ($fam->sortable_addname != "") $text_day .= "&nbsp(".$fam->sortable_addname.")";
									$text_day .= "</b>".$fam->addxref;
									$text_day .= "</a><br />\n";
									$text_day .= "<div class=\"indent";
									if ($TEXT_DIRECTION == "rtl") $text_day .= "_rtl";
									$text_day .= "\">";
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
						print "<span class=\"cal_day\"";
						if ($TEXT_DIRECTION == "rtl") print	" style=\"float: right;\"";
						print " >".GM_LANG_day_not_set."</span>";
						print "<br style=\"clear: both\" />";
					}
					$show_not_set=false;
					$show_no_day=-1;
				}
				print "<div id=\"day$k-$j\" class=\"details1\" style=\"width: 120px; height: ";
				if ($view=="preview") print "100%;";
				else print "150px; overflow: auto;";
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
				if ($show_no_day==6){
					$show_no_day=$k;
					if ($j==6) $show_no_day++;
				}
				else if ($show_no_day<0) $k=6;
			}
		} //-- end day for loop
		print "\t</tr>\n";
	} //-- end week for loop
	if ($view=="preview") print "<tr><td colspan=\"7\">";
}
if ($view=="preview"){
	if (isset($gid)) {
		if (isset($myindilist[$gid]["gedfile"])) $showfile=$myindilist[$gid]["gedfile"];
		else $showfile=$myfamlist[$gid]["gedfile"];
	}
	else $showfile = GedcomConfig::$GEDCOMID;
	$showfilter="";
	if ($calendar_controller->filterof != "all") $showfilter = htmlentities($calendar_controller->filterof == "living"? GM_LANG_living_only : GM_LANG_recent_events);
	if ($calendar_controller->filtersx != ""){
		if (!empty($showfilter)) $showfilter .= " - ";
		$showfilter .= ($calendar_controller->filtersx=="M"?GM_LANG_male : GM_LANG_female);
	}
	if ($calendar_controller->filterev != "all"){
		if (!empty($showfilter)) $showfilter .= " - ";
		if (defined("GM_FACT_".$calendar_controller->filterev)) $showfilter .= constant("GM_FACT_".$calendar_controller->filterev);
		else if (defined("GM_LANG_".$calendar_controller->filterev)) $showfilter .= constant("GM_LANG_".$calendar_controller->filterev);
	}
	print "<br />".$showfile." (".GM_LANG_filter.": ";
	if (!empty($showfilter)) print $showfilter.")\n";
	else print GM_LANG_all.")\n";
	print "</td></tr>";
}
print "</table>\n";
print "</div><br />\n";
PrintFooter();
?>