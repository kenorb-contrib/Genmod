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

if (empty($day)) $day = adodb_date("j");
if (empty($month)) $month = adodb_date("M");
if (empty($year)) $year = adodb_date("Y");

if (GedcomConfig::$USE_RTL_FUNCTIONS) {
	//-------> Today's Hebrew Day with Gedcom Month 
	
	$datearray = array();
 	$datearray[0]["day"]   = $day;
 	$datearray[0]["mon"]   = $monthtonum[Str2Lower(trim($month))];	
 	$datearray[0]["year"]  = $year;
 	$datearray[0]["month"] = $month;
 	$datearray[1]["day"]   = adodb_date("j");
 	$datearray[1]["mon"]   = $monthtonum[Str2Lower(trim(adodb_date("M")))];	
 	$datearray[1]["year"]  = adodb_date("Y");
 	// should use $ParseDate
 	
    $date   	= GregorianToJewishGedcomDate($datearray);
    $hDay   	= $date[0]["day"];
    $hMonth 	= $date[0]["month"];
    $hYear		= $date[0]["year"];
    $CalYear	= $hYear;
    
    $currhDay   = $date[1]["day"];
    $currhMon   = trim($date[1]["month"]);
    $currhMonth = $monthtonum[Str2Lower($currhMon)];
    $currhYear 	= $date[1]["year"];
}

if (empty($action)) $action = "today";
if (empty($filterev)) $filterev = "bdm";
if (empty($filterof)) $filterof = "all";
if (empty($filtersx)) $filtersx = "";

$olddates = true;
if ($action == "calendar") {
	$test = @adodb_mktime(1,0,0,1,1,1960);
	if ($test == -1) $olddates = false;
}
$endyear = "0";
if ($action == "year") {
	
	// Check for abbreviations
	$abbr = array(GM_LANG_abt, GM_LANG_aft, GM_LANG_bef, GM_LANG_bet, GM_LANG_cal, GM_LANG_est, GM_LANG_from, GM_LANG_int, GM_LANG_cir, GM_LANG_apx, GM_LANG_and, GM_LANG_to);
	
	// strip heading and trailing spaces and heading zero's
	$year = trim($year);
	for ($i=0;$i<=strlen($year);$i++){
		if (substr($year,0,1) == "0" && substr($year,1,1) != "-") $year = substr($year,1);
	}
	
	// Search for spaces and get the string up to the space
	$pos1 = strpos($year," ");
	if ($pos1 == 0) $pos1=strlen($year);
	if (function_exists("Str2Lower")) $in_year = Str2Lower(substr($year, 0, $pos1));
	else $in_year = substr($year, 0, $pos1);
	
	// If the characters before the space are in the translated prefix array, replace them with the gedcom expressions (ongeveer => abt)
	if (in_array($in_year, $abbr)){
		if (function_exists("Str2Lower")) $year = preg_replace(array("/$abbr[0]/","/$abbr[1]/","/$abbr[2]/","/$abbr[3]/","/$abbr[4]/","/$abbr[5]/","/$abbr[6]/","/$abbr[7]/","/$abbr[8]/","/$abbr[9]/","/ $abbr[10] /","/ $abbr[11] /"), array("abt","aft","bef","bet","cal","est","from","int","cir","apx"," and "," to "), Str2Lower($year));
		else $year = preg_replace(array("/$abbr[0]/","/$abbr[1]/","/$abbr[2]/","/$abbr[3]/","/$abbr[4]/","/$abbr[5]/","/$abbr[6]/","/$abbr[7]/","/$abbr[8]/","/$abbr[9]/"), array("abt","aft","bef","bet","cal","est","from","int","cir","apx"), $year);
	}
	
	
	// replace a question mark with [0-9]
	if (strlen($year) > 1 && preg_match("/\?/", $year)) $year = preg_replace("/\?/", "[0-9]", $year);
	
	// Replace all other invalid characters
	$year = preg_replace(array("/&lt;/", "/&gt;/", "/[?*+|&.,:'%_<>!#?{}=^]/", "/\\$/", "/\\\/",  "/\"/"), "", $year);
	
	// If what remains cannot be a year, set it to the current year
	if (preg_match("/[\D]{1,2}/", $year) && strlen($year) <= 2) $year="";
	if (empty($year)) $year = adodb_date("Y");
	$year = trim($year);
	$year_text = $year;
	$year_query = $year;
//	print $year;

	$startyear="0";
	if ((strpos($year, "-") > 0) && !preg_match("/[\[\]]/", $year)){
		if (substr($year,0,1) > 9){
			while (substr($year,0,1) > 9) $year = trim(substr($year, 1));
		}
		$pos1 = strpos($year, "-");
		if (strlen($year) == $pos1+2){					// endyear n
			$year_query = substr($year, 0, ($pos1-1))."[".substr($year, ($pos1-1), 3)."]";
			$year_text  = substr($year, 0, ($pos1+1)).substr($year, 0, ($pos1-1)).substr($year, ($pos1+1), 1);
		}
		else if (strlen($year)==$pos1+3){				// endyear nn
			$year_text = substr($year, 0, ($pos1-2));
			if ((substr($year, ($pos1-1), 1)=="0")&&(substr($year, ($pos1+2), 1)=="9")){
				$year_query  = $year_text."[".substr($year, ($pos1-2), 1)."-".substr($year, ($pos1+1), 1)."][0-9]";
			}
			else {
				$startyear= substr($year, 0, $pos1);
				$endyear= substr($year, 0, ($pos1-2)).substr($year, ($pos1+1), 2);
			}
			$year_text = substr($year, 0, ($pos1))." - ".($startyear=="0"?"":$year_text).substr($year, ($pos1+1), 2);
		}
		else if ((strlen($year)==$pos1+4)&&($pos1==4)){	// endyear nnn
			$year_text = substr($year, 0, ($pos1-3));
			if ((substr($year, ($pos1-2), 2)=="00")&&(substr($year, ($pos1+2), 2)=="99")){
				$year_query  = $year_text."[".substr($year, ($pos1-3), 1)."-".substr($year, ($pos1+1), 1)."][0-9][0-9]";
			}
			else {
				$startyear= substr($year, 0, $pos1);
				$endyear= substr($year, 0, ($pos1-3)).substr($year, ($pos1+1), 3);
			}
			$year_text = substr($year, 0, ($pos1))." - ".$year_text.substr($year, ($pos1+1), 3);
		}
		else {											// endyear nnn(n)
			$startyear = substr($year, 0, $pos1);
			$endyear   = substr($year, ($pos1+1));
			$year_text = $startyear." - ".$endyear;
		}
		if ($startyear>$endyear){
			$year_text = $startyear;
			$startyear = $endyear;
			$endyear   = $year_text;
			$year = $startyear."-".$endyear;
			$year_text = $startyear." - ".$endyear;
		}
// print "1. met streepjes start: ".$startyear." end: ".$endyear." year: ".$year."yearquery: ".$year_query."<br />";
	}
	if (strpos($year, "[", 1)>"0"){
		$pos1=(strpos($year, "[", 0));
		$year_text=substr($year, 0, $pos1);
		while (($pos1 = strpos($year, "[", $pos1))!==false) {
			$year_text .= substr($year, ($pos1+1), 1);
			$pos1++;
		}
		$pos1=strpos($year, "]", $pos1);
		if (strlen($year)>$pos1 && !strpos($year, "]", $pos1+1)) $year_add=substr($year, $pos1+1, strlen($year));
		$pos1=strpos($year, "]", $pos1+1);
		if (strlen($year)>$pos1 && !strpos($year, "]", $pos1+1)) $year_add=substr($year, $pos1+1, strlen($year));
		if (isset($year_add)) $year_text .= $year_add." ~ ";
		else $year_text .= " - ";
		if (strpos($year, " ", 0)>0) $pos1=(strpos($year, " ", 0)+1);
		else $pos1=0;
		$year_text .= substr($year, $pos1, (strpos($year, "[", 0))-$pos1);
		$pos1=(strpos($year, "[", 0));
		while (($pos1 = strpos($year, "]", $pos1))!==false) {
			$year_text .= substr($year, ($pos1-1), 1);
			$pos1++;
		}
		if (isset($year_add)) $year_text .= $year_add;
		$year_query=$year;
// print "2. met haken start: ".$startyear." end: ".$endyear." year: ".$year."yearquery: ".$year_query."<br />";
	}
	else if (strlen($year)<4 && preg_match("/[\d]{1,3}/", $year)){
		if (substr($year, 0, 2) <= substr(adodb_date("Y"), 0, 2)){
			for ($i=strlen($year); $i<4; $i++) $year_text .="0";
			$startyear = $year_text;
			$year_text .= " - ".$year;
			for ($i=strlen($year); $i<4; $i++) $year_text .="9";
			$endyear=$year;
			for ($i=strlen($year); $i<4; $i++) $endyear .="9";
		}
		else {
			for ($i=strlen($year); $i<3; $i++) $year_text .="0";
			for ($i=strlen($year); $i<3; $i++) $year .= "0";
		}
		$year_query=$year;
// print "3. lengte < 4 start: ".$startyear." end: ".$endyear." year: ".$year."yearquery: ".$year_query."<br />";
	}
if ($startyear == 0) {
	$startyear = $year;
	$endyear = $year;
}
// print "year final: start: ".$startyear." end: ".$endyear." year: ".$year."yearquery: ".$year_query."<br />";
}
else {
	if (strlen($year) < 3) $year = adodb_date("Y");
	if (strlen($year) > 4){
		if (strpos($year, "[", 1) > "0"){
			$pos1 = (strpos($year, "[", 0));
			$year_text = $year;
			$year = substr($yy, 0, ($pos1));
			$year .= substr($yy, ($pos1+1), 1);
			if (strlen($year_text) == $pos1+10) $year .= substr($yy, ($pos1+6), 1);
		}
		else if (strpos($year, "-", 1) > "0") $year = substr($year, 0, (strpos($year, "-", 0)));
			else $year = adodb_date("Y");
	}
	$year = trim($year);
//print "alle andere  year: ".$year."yearquery: ".$year_query."<br />";
}

// calculate leap year
if (strlen($year)<5 && preg_match("/[\d]{2,4}/", $year)) {
	if (checkdate(2,29,$year)) $leap = TRUE;
	else $leap = FALSE;
}
else $leap = FALSE;

// Check for invalid days
$m_days = 31;
$m_name = strtolower($month);
if ($m_name == "feb") {
	if (!$leap) {
		$m_days = 28;
		if ($day >= '28') {
			$day = "28";
			$pregquery = "2 DATE[^\n]*2[8|9] $month";
			$query = "2 DATE[^\n]*2[8|9] $month";
		}
	}
	else {
		$m_days = 29;
		if ($day >= '29') {
			$day = "29";
			$pregquery = "2 DATE[^\n]*29 $month";
			$query = "2 DATE[^\n]*29 $month";
		}
	}
}
else if ($m_name == "apr" || $m_name == "jun" || $m_name == "sep" || $m_name == "nov") {
	$m_days = 30;
	if ($day >= '30') {
		$day = "30";
		$pregquery = "2 DATE[^\n]*30 $month";
		$query = "2 DATE[^\n]*30 $month";
	}
}

if (!isset($query)) {
	if ($day<10) {
		$pregquery = "2 DATE[^\n]*[ |0]$day $month";
		$query = "2 DATE[^\n]*[ |0]$day $month";
	}
	else {
		$pregquery = "2 DATE[^\n]*$day $month";
		$query = "2 DATE[^\n]*$day $month";
	}
}

If (!isset($datearray[4]["year"]) && GedcomConfig::$USE_RTL_FUNCTIONS) {
	if ($action != "year") {
		 $year1 = $year;
		 $year2 = $year;
	}
	$datearray = array();
 	$datearray[0]["day"]   = $day;
 	$datearray[0]["mon"]   = $monthtonum[Str2Lower(trim($month))];	
 	$datearray[0]["year"]  = $year;
 	$datearray[0]["month"] = $month;
 	// for month
 	$datearray[1]["day"]   = 01;
 	$datearray[1]["mon"]   = $monthtonum[Str2Lower(trim($month))];	
 	$datearray[1]["year"]  = $year;
 	$datearray[2]["day"]   = $m_days;
 	$datearray[2]["mon"]   = $monthtonum[Str2Lower(trim($month))];	
 	$datearray[2]["year"]  = $year;
 	
 	// for year
	if ($action == "year") {
		$pattern = "[ - |-|and|bet|from|to|abt|bef|aft|cal|cir|est|apx|int]";
		$a = preg_split($pattern, $year_text);
		if ($a[0] != "") $gstartyear = $a[0]; 
		if (isset($a[1]))
			if ($a[0] != "") $gendyear = $a[1];
			else {
				$gstartyear = $a[1];
				if (isset($a[2])) $gendyear = $a[2];
				else $gendyear = $a[1];
			}
		else $gendyear = $a[0];
 		
 		$datearray[3]["day"]   = 01;
 		$datearray[3]["mon"]   = 01;	
 		$datearray[3]["year"]  = $gstartyear;
 		$datearray[4]["day"]   = 31;
 		$datearray[4]["mon"]   = 12;	
 		$datearray[4]["year"]  = $gendyear;
	}

    $date   	= GregorianToJewishGedcomDate($datearray);
    $hDay   	= $date[0]["day"];
    $hMonth 	= $date[0]["month"];
    $CalYear	= $date[0]["year"];
    	
    if (!isset($queryhb) && $action!="year") {   //---- ?????? does not work - see I90 in 1042 @@@@@
    	if ($hDay<10) {
			$preghbquery = "2 DATE[^\n]*[ |0]$hDay $hMonth";
			$queryhb = "2 DATE[^\n]*[ |0]$hDay $hMonth";
		}
		else {
			$preghbquery = "2 DATE[^\n]*$hDay $hMonth";
			$queryhb = "2 DATE[^\n]*$hDay $hMonth";
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
if ($action=="today") {
	print GM_LANG_on_this_day."</h3></td></tr>\n";
	print "<tr><td class=\"topbottombar\">";
	//-- the year is needed for alternate calendars
 	if (GedcomConfig::$CALENDAR_FORMAT!="gregorian") print GetChangedDate("$day $month $year");
	else print GetChangedDate("$day $month");
	if (GedcomConfig::$CALENDAR_FORMAT=="gregorian" && GedcomConfig::$USE_RTL_FUNCTIONS && $HEBREWFOUND[GedcomConfig::$GEDCOMID] == true) print " / ".GetChangedDate("@#DHEBREW@ $hDay $hMonth $CalYear"); 
}
else if ($action=="calendar") {
	print GM_LANG_in_this_month."</h3></td></tr>\n";
	print "<tr><td class=\"topbottombar\">";
	print GetChangedDate(" $month $year ");
	if (GedcomConfig::$CALENDAR_FORMAT=="gregorian" && GedcomConfig::$USE_RTL_FUNCTIONS && $HEBREWFOUND[GedcomConfig::$GEDCOMID] == true) {
		$hdd = $date[1]["day"];
		$hmm = $date[1]["month"];
		$hyy = $date[1]["year"];
		print " /  ".GetChangedDate("@#DHEBREW@ $hdd $hmm $hyy");
        if ($hmm!=$date[2]["month"]) {
	            $hdd = $date[2]["day"];
        		$hmm = $date[2]["month"];
				$hyy = $date[2]["year"];
				print " -".GetChangedDate("@#DHEBREW@ $hdd $hmm $hyy");
		}
    }
}
else if ($action=="year") {
	print GM_LANG_in_this_year."</h3></td></tr>\n";
	print "<tr><td class=\"topbottombar\">";
	print GetChangedDate(" $year_text ");
	if (GedcomConfig::$CALENDAR_FORMAT=="gregorian" && GedcomConfig::$USE_RTL_FUNCTIONS && $HEBREWFOUND[GedcomConfig::$GEDCOMID] == true) {
		$hdd = $date[3]["day"];
		$hmm = $date[3]["month"];
		$hstartyear = $date[3]["year"];
		print " /  ".GetChangedDate("@#DHEBREW@ $hdd $hmm $hstartyear");
	    $hdd = $date[4]["day"];
        $hmm = $date[4]["month"];
		$hendyear = $date[4]["year"];
		print " -".GetChangedDate("@#DHEBREW@ $hdd $hmm $hendyear");
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
	print "<input type=\"hidden\" name=\"action\" value=\"$action\" />";
	print "\n\t\t<table class=\"facts_table $TEXT_DIRECTION width100\">\n\t\t<tr>";
	print "<td class=\"shade2 vmiddle\">";
	PrintHelpLink("annivers_date_select_help", "qm", "day");
	print GM_LANG_day."</td>\n";
	print "<td colspan=\"7\" class=\"shade1\">";
	for($i=1; $i<($m_days+1); $i++) {
		if (empty($dd)) $dd = $day;
		print "<a href=\"calendar.php?day=$i&amp;month=".strtolower($month)."&amp;year=$year&amp;filterev=$filterev&amp;filterof=$filterof&amp;filtersx=$filtersx&amp;action=today\">";
		if ($i==$dd) print "<span class=\"error\">$i</span>";
		else print $i;
		print "</a> | ";
	}
	$Dd = adodb_date("j");
	$Mm = adodb_date("M");
	$Yy = adodb_date("Y");
//	print "<a href=\"calendar.php?filterev=$filterev&amp;filterof=$filterof&amp;filtersx=$filtersx\"><b>".GetChangedDate("$Dd $Mm $Yy")."</b></a> | ";
	//-- for alternate calendars the year is needed
  	if (GedcomConfig::$CALENDAR_FORMAT!="gregorian" || (GedcomConfig::$USE_RTL_FUNCTIONS && $HEBREWFOUND[GedcomConfig::$GEDCOMID] == true)) $datestr = "$Dd $Mm $Yy";
// 	if ($CALENDAR_FORMAT!="gregorian") $datestr = "$Dd $Mm $Yy"; // MA @@@
	else $datestr = "$Dd $Mm";
	print "<a href=\"calendar.php?filterev=$filterev&amp;filterof=$filterof&amp;filtersx=$filtersx&amp;year=$year\"><b>".GetChangedDate($datestr);
	if (GedcomConfig::$USE_RTL_FUNCTIONS && $HEBREWFOUND[GedcomConfig::$GEDCOMID] == true) {
		$hdatestr = "@#DHEBREW@ $currhDay $currhMon $currhYear";
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
			if (empty($mm)) $mm=strtolower($month);
			print "<a href=\"calendar.php?day=$dd&amp;month=$mon&amp;year=$year&amp;filterev=$filterev&amp;filterof=$filterof&amp;filtersx=$filtersx&amp;action=".($action=="year"?"calendar":"$action")."\">";
			$monthstr = constant("GM_LANG_".$mon);
			if ($mon==$mm) print "<span class=\"error\">".$monthstr."</span>";
			else print $monthstr;
			print "</a> | ";
		}
	}

	print "<a href=\"calendar.php?month=".strtolower(adodb_date("M"))."&amp;action=calendar&amp;filterev=$filterev&amp;filterof=$filterof&amp;filtersx=$filtersx\"><b>".constant("GM_LANG_".strtolower(adodb_date("M")))." ".strtolower(adodb_date("Y"))."</b></a> | ";
	print "</td>\n";
	print "</tr><tr>";
	print "<td class=\"shade2 vmiddle\">";
	PrintHelpLink("annivers_year_select_help", "qm", "year");
	print GM_LANG_year."</td>\n";
	print "<td class=\"shade1 vmiddle\">";
	if (strlen($year)<5){
		if ($year<"AA") print " <a href=\"calendar.php?day=$day&amp;month=$month&amp;year=".($year-1)."&amp;action=".($action=="calendar"?"calendar":"year")."&amp;filterev=$filterev&amp;filterof=$filterof&amp;filtersx=$filtersx\" title=\"".($year-1)."\" >-1</a> ";
	}
	print "<input type=\"text\" name=\"year\" value=\"$year\" size=\"7\" />";
	if (strlen($year)<5){
		if ($year<(adodb_date("Y"))) print " <a href=\"calendar.php?day=$day&amp;month=$month&amp;year=".($year+1)."&amp;action=".($action=="calendar"?"calendar":"year")."&amp;filterev=$filterev&amp;filterof=$filterof&amp;filtersx=$filtersx\" title=\"".($year+1)."\" >+1</a> |";
		else if ($year<"AA") print " +1 |";
	}
	print " <a href=\"calendar.php?day=$day&amp;month=$month&amp;year=".adodb_date("Y")."&amp;action=".($action=="calendar"?"calendar":"year")."&amp;filterev=$filterev&amp;filterof=$filterof&amp;filtersx=$filtersx\"><b>".strtolower(adodb_date("Y"))."</b></a> | ";

	print "</td>\n ";
	if ($HIDE_LIVE_PEOPLE >= $gm_user->GetUserAccessLevel()) {
		print "<td class=\"shade2 vmiddle\">";
		PrintHelpLink("annivers_show_help", "qm", "show");
		print GM_LANG_show.":&nbsp;</td>\n";
		print "<td class=\"shade1 vmiddle\">";

		print "<input type=\"hidden\" name=\"filterof\" value=\"$filterof\" />";
		print "<select name=\"filterof\" onchange=\"document.dateform.submit();\">\n";
		print "<option value=\"all\"";
		if ($filterof == "all") print " selected=\"selected\"";
		print ">".GM_LANG_all_people."</option>\n";
		print "<option value=\"living\"";
		if ($filterof == "living") print " selected=\"selected\"";
		print ">".GM_LANG_living_only."</option>\n";
		print "<option value=\"recent\"";
		if ($filterof == "recent") print " selected=\"selected\"";
		print ">".GM_LANG_recent_events."</option>\n";
		print "</select>\n";
	}
	else {
		print "<td class=\"shade2 vmiddle\">".GM_LANG_showcal."</td>\n";
		print "<td colspan=\"5\" class=\"shade1 vmiddle\">";
		if ($filterof=="all") print "<span class=\"error\">".GM_LANG_all_people. "</span> | ";
		else {
			$filt="all";
			print "<a href=\"calendar.php?day=$dd&amp;month=$month&amp;year=$year&amp;filterof=$filt&amp;filtersx=$filtersx&amp;action=$action\">".GM_LANG_all_people."</a>"." | ";
		}
		if ($filterof=="recent") print "<span class=\"error\">".GM_LANG_recent_events. "</span> | ";
		else {
			$filt="recent";
			print "<a href=\"calendar.php?day=$dd&amp;month=$month&amp;year=$year&amp;filterof=$filt&amp;filtersx=$filtersx&amp;action=$action\">".GM_LANG_recent_events."</a>"." | ";
		}
	}
	

	if ($HIDE_LIVE_PEOPLE >= $gm_user->GetUserAccessLevel()) {
		print "</td>\n ";
		print "<td class=\"shade2 vmiddle\">";
		PrintHelpLink("annivers_sex_help", "qm", "sex");
		print GM_LANG_sex.":&nbsp;</td>\n";
		print "<td class=\"shade1 vmiddle\">";
		if ($filtersx==""){
			print " <img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sex"]["small"]."\" title=\"".GM_LANG_all."\" alt=\"".GM_LANG_all."\" width=\"15\" height=\"15\" border=\"0\" align=\"middle\" />";
			print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sexf"]["small"]."\" title=\"".GM_LANG_all."\" alt=\"".GM_LANG_all."\" width=\"15\" height=\"15\" border=\"0\" align=\"middle\" />";
			print " | ";
		}
		else {
			$fs="";
			print " <a href=\"calendar.php?day=$dd&amp;month=$month&amp;year=$year&&amp;filterev=$filterev&amp;filterof=$filterof&amp;filtersx=$fs&amp;action=$action\">";
			print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sex"]["small"]."\" title=\"".GM_LANG_all."\" alt=\"".GM_LANG_all."\" width=\"9\" height=\"9\" border=\"0\" align=\"middle\" />";
			print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sexf"]["small"]."\" title=\"".GM_LANG_all."\" alt=\"".GM_LANG_all."\" width=\"9\" height=\"9\" border=\"0\" align=\"middle\" />";
			print "</a>"." | ";
		}
		if ($filtersx=="M"){
			print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sex"]["small"]."\" title=\"".GM_LANG_male."\" alt=\"".GM_LANG_male."\" width=\"15\" height=\"15\" border=\"0\" align=\"middle\" />";
			print " | ";
		}
		else {
			$fs="M";
			print "<a href=\"calendar.php?day=$dd&amp;month=$month&amp;year=$year&amp;filterev=$filterev&amp;filterof=$filterof&amp;filtersx=$fs&amp;action=$action\">";
			print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sex"]["small"]."\" title=\"".GM_LANG_male."\" alt=\"".GM_LANG_male."\" width=\"9\" height=\"9\" border=\"0\" align=\"middle\" />";
			print "</a>"." | ";
		}
		if ($filtersx=="F"){
			print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sexf"]["small"]."\" title=\"".GM_LANG_female."\" alt=\"".GM_LANG_female."\" width=\"15\" height=\"15\" border=\"0\" align=\"middle\" />";
			print " | ";
		}
		else {
			$fs="F";
			print "<a href=\"calendar.php?day=$dd&amp;month=$month&amp;year=$year&amp;filterev=$filterev&amp;filterof=$filterof&amp;filtersx=$fs&amp;action=$action\">";
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
		print "<input type=\"hidden\" name=\"filterev\" value=\"$filterev\" />";
		print "<select name=\"filterev\" onchange=\"document.dateform.submit();\">\n";
		
		print "<option value=\"bdm\"";
		if ($filterev == "bdm") print " selected=\"selected\"";
		print ">".GM_LANG_bdm."</option>\n";
		
		PrintFilterEvent($filterev);
	}

	print "</td>\n";
	print "</tr>";
	print "<tr><td class=\"topbottombar\" colspan=\"8\">";
	PrintHelpLink("day_month_help", "qm");
	print "<input type=\"hidden\" name=\"day\" value=\"$dd\" />";
	print "<input type=\"hidden\" name=\"month\" value=\"$mm\" />";
	print "<input type=\"hidden\" name=\"filtersx\" value=\"$filtersx\" />";
	print "<input type=\"submit\"  value=\"".GM_LANG_viewday."\" onclick=\"document.dateform.elements['action'].value='today';\" />\n";
	print "<input type=\"submit\"  value=\"".GM_LANG_viewmonth."\" onclick=\"document.dateform.elements['action'].value='calendar';\" />\n";
	print "<input type=\"submit\"  value=\"".GM_LANG_viewyear."\" onclick=\"document.dateform.elements['action'].value='year';\" />\n";
	print "</td></tr></table><br />";
	print "</form>\n";
	
	
}
if (($action=="today") || ($action=="year")) {
	$myindilist = array();
	$myfamlist = array();
	$famfacts = array("_COML", "MARR", "DIV", "EVEN");
	$findindis = true;
	$findfams = true;
	if ($filterev == "bdm") {
		$selindifacts = "('BIRT', 'DEAT')";
		$selfamfacts = "('MARR')";
	}
	else if ($filterev != "all" && !empty($filterev)) {
		$selfacts = $filterev;
		if (in_array($filterev, $famfacts)) {
			$findindis = false;
			$selfamfacts = $filterev;
		}
		else {
			$selindifacts = $filterev;
			$findfams = false;
		}
	}
	else {
		$selindifacts = "";
		$selfamfacts = "";
	}
	
	if ($action=="year"){
		if (isset($year_query)) $year=$year_query;
		$pregquery = "2 DATE[^\n]*(bet|$year)";
		$query = "2 DATE[^\n]*(bet|$year)";
		$pregquery1 = "2 DATE[^\n]*$year";
		$query1 = "2 DATE[^\n]*$year";
		
		if ($findindis) $myindilist = SearchFunctions::SearchIndisYearRange($startyear,$endyear, false, $selindifacts);
		if ($findfams) $myfamlist = SearchFunctions::SearchFamsYearRange($startyear,$endyear, false, $selfamfacts);
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
	if ($action == "today") {
		if (GedcomConfig::$USE_RTL_FUNCTIONS) {
//			$myindilist1 = SearchFunctions::SearchIndis($query);
			if ($findindis) {
				$myindilist1 = SearchFunctions::SearchIndis($year, false, "AND", $selindifacts);
				$myindilist = GmArrayMerge($myindilist, $myindilist1);
			}
		
//			$myfamlist1 = SearchFunctions::SearchFams($query);
			if ($findfams) {
				$myfamlist1 = SearchFunctions::SearchFams($year, false, "AND", $selfamfacts);
				$myfamlist = GmArrayMerge($myfamlist, $myfamlist1);
			}
		}
		else {
//			$myindilist = SearchFunctions::SearchIndis($query);
			if ($findindis) $myindilist = SearchFunctions::SearchIndis($year, false, "AND", $selindifacts);
//			$myfamlist = SearchFunctions::SearchFams($query);
			if ($findfams) $myfamlist = SearchFunctions::SearchFams($year, false, "AND", $selfamfacts);
        }

		if (GedcomConfig::$USE_RTL_FUNCTIONS && isset($queryhb) && $action!="year") {
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
		$query=$query1;
		$pregquery = $pregquery1;
	}
	
// print "indicount: ".count($myindilist)."<br />";
// print "famcount: ".count($myfamlist)."<br />";

	uasort($myindilist, "ItemSort");
	if (empty($filtersx)) uasort($myfamlist, "ItemSort");
	$count_private_indi=array();
	$count_indi=0;
	$count_male=0;
	$count_female=0;
	$count_unknown=0;
	$text_indi="";
	$sx=1;
	if ($filterev == "bdm") $select = array("BIRT", "DEAT");
	else if ($filterev == "all") $select = "";
	else $select = array($filterev);
	foreach($myindilist as $gid=>$indi) {
		if ((($filterof == "living" && !$indi->isdead) || $filterof != "living") && ((!empty($filtersx) && $indi->sex == $filtersx) || empty($filtersx))) {
			$filterout=false;
			$text_fact = "";
			$indifacts = $indi->SelectFacts($select, true);
//			print "check ".$indi->xref." ".$indi->name."<br >";
			foreach($indifacts as $index => $factobj) {
//				print "check ".$factobj->fact."<br />";
				$text_temp = "";
				
				$t1 = preg_match("/2 DATE.*DHEBREW.* (\d\d\d\d)/i", $factobj->factrec, $m1);
				if (GedcomConfig::$USE_RTL_FUNCTIONS && $action=="year" && isset($hendyear) && $hendyear>0 && $t1>0) {
					$j = $hstartyear;   //-- why MA @@@ ??
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
						if ($hprocess) $text_temp .= FactFunctions::GetCalendarFact($factobj, $action, $filterof, $filterev);
						else $text_temp .="filter";
				}
				else 
				if ($endyear>0) {
					$j = $startyear;   //----- why??? MA @@@@
					$t1 = preg_match("/2 DATE.* (\d\d\d\d)/i", $factobj->factrec, $m1);
					if (($t1 > 0) && ($m1[1] >= $startyear) && ($m1[1] <= $endyear)){
						$text_temp .= FactFunctions::GetCalendarFact($factobj, $action, $filterof, $filterev);
					}
					else {  
						$t2 = preg_match("/2 DATE.* (\d\d\d)/i", $factobj->factrec, $m2);
						if (($t2 > 0) && ($m2[1] >= $startyear) && ($m2[1] <= $endyear)){									
							$text_temp .= FactFunctions::GetCalendarFact($factobj, $action, $filterof, $filterev);
						}
					}
				}
				else {
					$ct = preg_match("/$pregquery/i", $factobj->factrec, $match);
					if ($action == "year"){
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
									if ($years[0] < $year && $years[1] > $year) $ct = 1;
									else $text_temp .= "filter";
								}
								else $text_temp .= "filter";
							}
						}
					}
					if ($ct < 1 && GedcomConfig::$USE_RTL_FUNCTIONS && isset($preghbquery)) $ct = preg_match("/$preghbquery/i", $factobj->factrec, $match);
					if ($action == "year"){
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
									if ($years[0] < $year && $years[1] > $year) $ct = 1;
									else $text_temp .= "filter";
								}
								else $text_temp .= "filter";
									
							}
						}
					}
					if ($ct>0) $text_temp .= FactFunctions::GetCalendarFact($factobj, $action, $filterof, $filterev);
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
	if ($filterev == "bdm") $select = array("MARR");
	else if ($filterev == "all") $select = "";
	else $select = array($filterev);
	if ($filtersx == "") {
		foreach($myfamlist as $gid=>$fam) {
			$display=true;
			if ($filterof=="living") {
				if (($fam->husb_id != "" && !$fam->husb->isdead) && ($fam->wifeid != "" && !$fam->wife->isdead)) {
					$display = false;
				}
			}
  			if ($display) {
				$filterout=false;
				$text_fact = "";
				$famfacts = $fam->SelectFacts($select, true);
				foreach ($famfacts as $index => $factobj) {
					$text_temp = "";
					$t1 = preg_match("/2 DATE.*DHEBREW.* (\d\d\d\d)/i", $factobj->factrec, $m1);
					if (GedcomConfig::$USE_RTL_FUNCTIONS && $action=="year" && isset($hendyear) && $hendyear>0 && $t1>0) {
						$j = $hstartyear;   //----- why??? MA @@@@
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
						if ($hprocess) $text_temp .= FactFunctions::GetCalendarFact($factobj, $action, $filterof, $filterev);
                        else $text_temp = "filter";
					}
				    else 
					if ($endyear>0){
						$j = $startyear;
						$t1 = preg_match("/2 DATE.* (\d\d\d\d)/i", $factobj->factrec, $m1);
						if (($t1 > 0) && ($m1[1] >= $startyear) && ($m1[1] <= $endyear)){
							$text_temp .= FactFunctions::GetCalendarFact($factobj, $action, $filterof, $filterev);
						}
						else {
							$t2 = preg_match("/2 DATE.* (\d\d\d)/i", $factobj->factrec, $m2);
							if (($t2 > 0) && ($m2[1] >= $startyear) && ($m2[1] <= $endyear)){
								$text_temp .= FactFunctions::GetCalendarFact($factobj, $action, $filterof, $filterev);
							}
						}
					}
					else {
						$ct = preg_match("/$pregquery/i", $factobj->factrec, $match);
						if ($action == "year"){
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
										if ($years[0] < $year && $years[1] > $year) $ct = 1;
										else $text_temp="filter";
									}
								}
							}
						}
						if ($ct < 1 && GedcomConfig::$USE_RTL_FUNCTIONS && isset($preghbquery)) $ct = preg_match("/$preghbquery/i", $factobj->factrec, $match);
						if ($action == "year"){
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
										if ($years[0] < $year && $years[1] > $year) $ct = 1;
										else $text_temp = "filter";
									}
								}
							}
						}
						if ($ct>0) $text_temp .= FactFunctions::GetCalendarFact($factobj, $action, $filterof, $filterev);
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
else if ($action=="calendar") {
	if(GedcomConfig::$CALENDAR_FORMAT=="jewish" || GedcomConfig::$CALENDAR_FORMAT=="hebrew" || GedcomConfig::$CALENDAR_FORMAT=="hijri") { //since calendar is based on gregorian it doesn't make sense to not display the gregorian caption
		print "<span class=\"subheaders\">".constant("GM_LANG_".strtolower($month))." $year</span> &#160; \n";
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
	$monthstart = adodb_mktime(1,0,0,$monthtonum[strtolower($month)],1,$year);
	$startday = adodb_date("w", $monthstart);
	$endday = adodb_dow($year,$monthtonum[strtolower($month)],adodb_date("t", $monthstart));
	$lastday=adodb_date("t", $monthstart);
	$mmon = strtolower(adodb_date("M", $monthstart));
	$monthstart = $monthstart-(60*60*24*$startday);
	if($WEEK_START<=$startday)
		$monthstart += $WEEK_START*(60*60*24);
	else //week start > $startday
		$monthstart -= (7-$WEEK_START)*(60*60*24);
	if (($endday==6 && $WEEK_START==0) || ($endday==0 && $WEEK_START==1)) $show_no_day=0;
	else $show_no_day=6;
	if ((($startday==0 && $WEEK_START==0) || ($startday==2 && $WEEK_START==1)) && $show_no_day==0) $show_no_day=6;
	$show_not_set=false;
	$lastday-=29;   
	if ($lastday<0) $lastday=0;
	$myindilist = array();
	$myfamlist = array();
	$pregquery = "2 DATE[^\n]*$mmon";
	$query = "2 DATE[^\n]*$mmon";

	$fact = "";	
//	if ($filterev == "bdm") $fact = "('BIRT','DEAT','MARR')";
//	else if ($filterev=="all") $fact = "";
//	else $fact = $filterev;

	$famfacts = array("_COML", "MARR", "DIV", "EVEN");
	$findindis = true;
	$findfams = true;
	if ($filterev == "bdm") $fact = "('BIRT', 'DEAT', 'MARR')";
	else if ($filterev != "all" && !empty($filterev)) {
		$fact = $filterev;
		if (in_array($filterev, $famfacts)) $findindis = false;
		else $findfams = false;
	}
	else $fact = "";
	
	
	
	if ($findindis) $myindilist = SearchFunctions::SearchIndisDates("", $mmon, "", $fact);
	if ($findfams) $myfamlist = SearchFunctions::SearchFamsDates("", $mmon, "", $fact);

	if (GedcomConfig::$USE_RTL_FUNCTIONS) {
		$datearray[0]["day"]   = 01;
 		$datearray[0]["mon"]   = $monthtonum[Str2Lower($month)];	
 		$datearray[0]["year"]  = $year;
 		$datearray[0]["month"] = $month;
 		$datearray[1]["day"]   = 15;
 		$datearray[1]["mon"]   = $monthtonum[Str2Lower($month)];	
 		$datearray[1]["year"]  = $year;
 		$datearray[1]["month"] = $month;
 		$datearray[2]["day"]   = adodb_date("t", $monthstart);
 		$datearray[2]["mon"]   = $monthtonum[Str2Lower($month)];	
 		$datearray[2]["year"]  = $year;
 		$datearray[2]["month"] = $month;

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
			$query2 = "2 DATE[^\n]*$HBMonth2";
				
			if ($findindis) $myindilist1 = SearchFunctions::SearchIndisDates("", $HBMonth2, "", $fact);
			if ($findfams) $myfamlist1 = SearchFunctions::SearchFamsDates("", $HBMonth2, "", $fact);
			
			if ($findindis) $myindilist = GmArrayMerge($myindilist, $myindilist1);
			if ($findfams) $myfamlist  = GmArrayMerge($myfamlist, $myfamlist1);
		}
		
		if ($HBMonth2 != $HBMonth3) {		
			$preghbquery3 = "2 DATE[^\n]*$HBMonth3";
			$query3 = "2 DATE[^\n]*$HBMonth3";
				
			if ($findindis) $myindilist1 = SearchFunctions::SearchIndisDates("", $HBMonth3, "", $fact);
			if ($findfams) $myfamlist1 = SearchFunctions::SearchFamsDates("", $HBMonth3, "", $fact);
			
			if ($findindis) $myindilist = GmArrayMerge($myindilist, $myindilist1);
			if ($findfams) $myfamlist  = GmArrayMerge($myfamlist, $myfamlist1);
		}
		
		if (!IsJewishLeapYear($HBYear1) && ($HBMonth1 == "adr" || $HBMonth2 == "adr" || $HBMonth3 == "adr")) {
			$HBMonth4 = "ads"; 
			$preghbquery4 = "2 DATE[^\n]*$HBMonth4";
			$query4 = "2 DATE[^\n]*$HBMonth4";
		
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
			if ($show_no_day==0 && $j==0 && $k==0) $show_not_set=true;
			else if ($show_no_day==$k && $j==6) $show_not_set=true;
			if ($mmon==strtolower($month)||($show_not_set)) {
				if ($show_not_set) {
					$pregquery = "2 DATE(|[^\n]*[^\d]+|[^\n]*([ |0]0)|[^\n]*3[$lastday-9]|[^\n]*[4-9][0-9]) $month";
					
					// I see April 1973 in 2004 both correctly in April and in March with another event

					// Include here Hebrew dates that do not convert to a Gregorian date (same into blocks) - like 31 NSN 5724
					
					if (GedcomConfig::$USE_RTL_FUNCTIONS) {
						    $preghbquery1 = "";
						    $preghbquery2 = "";
						    $preghbquery3 = "";
						    						
						 	$datearray[0]["day"]   = 01;
 							$datearray[0]["mon"]   = $monthtonum[Str2Lower($month)];	
 							$datearray[0]["year"]  = $year;
 							$datearray[0]["month"] = $month;
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
					$day = $mday;
					$currentDay = false;
					if(($year == adodb_date("Y")) && (strtolower($month) == strtolower(adodb_date("M"))) && ($mday == adodb_date("j"))) //current day
						$currentDay = true;
					print "<span class=\"cal_day". ($currentDay?" current_day":"") ."\">".$mday."</span>";
					if (GedcomConfig::$CALENDAR_FORMAT=="hebrew_and_gregorian" || GedcomConfig::$CALENDAR_FORMAT=="hebrew" ||
						((GedcomConfig::$CALENDAR_FORMAT=="jewish_and_gregorian" || GedcomConfig::$CALENDAR_FORMAT=="jewish" || (GedcomConfig::$USE_RTL_FUNCTIONS &&  $HEBREWFOUND[GedcomConfig::$GEDCOMID] == true)) && $LANGUAGE == "hebrew")) {
						$monthTemp = $monthtonum[strtolower($month)];
						$jd = gregoriantojd($monthTemp, $mday, $year);
						$hebrewDate = jdtojewish($jd);
						// if (GedcomConfig::$USE_RTL_FUNCTIONS &&  $HEBREWFOUND[GedcomConfig::$GEDCOMID] == true) {
							list ($hebrewMonth, $hebrewDay, $hebrewYear) = split ('/', $hebrewDate);
							print "<span class=\"rtl_cal_day". ($currentDay?" current_day":"") ."\">";
							print GetHebrewJewishDay($hebrewDay) . " " .GetHebrewJewishMonth($hebrewMonth, $hebrewYear) . "</span>";
						// }
					}
					else if(GedcomConfig::$CALENDAR_FORMAT=="jewish_and_gregorian" || GedcomConfig::$CALENDAR_FORMAT=="jewish" || (GedcomConfig::$USE_RTL_FUNCTIONS && $HEBREWFOUND[GedcomConfig::$GEDCOMID] == true)) {
						// else if(GedcomConfig::$CALENDAR_FORMAT=="jewish_and_gregorian" || GedcomConfig::$CALENDAR_FORMAT=="jewish" || GedcomConfig::$USE_RTL_FUNCTIONS) {
						$monthTemp = $monthtonum[strtolower($month)];
						$jd = gregoriantojd($monthTemp, $mday, $year);
						$hebrewDate = jdtojewish($jd);
						// if (GedcomConfig::$USE_RTL_FUNCTIONS &&  $HEBREWFOUND[GedcomConfig::$GEDCOMID] == true) {
							list ($hebrewMonth, $hebrewDay, $hebrewYear) = split ('/', $hebrewDate);
							print "<span class=\"rtl_cal_day". ($currentDay?" current_day":"") ."\">";
							print $hebrewDay . " " . GetJewishMonthName($hebrewMonth, $hebrewYear) . "</span>";
						// }
					}
					else if(GedcomConfig::$CALENDAR_FORMAT=="hijri") {
						$monthTemp = $monthtonum[strtolower($month)];
						$hDate = GetHijri($mday, $monthTemp, $year);
						list ($hMonthName, $hDay, $hYear) = split ('/', $hDate);
						print "<span class=\"rtl_cal_day". ($currentDay?" current_day":"") ."\">";
						print $hDay . " " . $hMonthName . "</span>";
					}
					print "<br style=\"clear: both\" />";
					$dayindilist = array();

					if ($mday<10) $pregquery = "2 DATE[^\n]*[ |0]$mday $mmon";
					else if (!$leap && $mmon == "feb" && $mday == '28') $pregquery = "2 DATE[^\n]*2[8|9] $mmon";
					else $pregquery = "2 DATE[^\n]*$mday $mmon";
				
					if (GedcomConfig::$USE_RTL_FUNCTIONS) {
						    $preghbquery1 = "";
						    $preghbquery2 = "";
						    $preghbquery3 = "";
						 	$datearray[0]["day"]   = $mday;
 							if (isset($monthTemp)) $datearray[0]["mon"]   = $monthTemp;
 							else $monthTemp = "";
 							$datearray[0]["year"]  = $year;
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
				if ($filterev == "bdm") $select = array("BIRT", "DEAT");
				else if ($filterev == "all") $select = "";
				else $select = array($filterev);
				foreach($myindilist as $gid=>$indi) {
			
					if ((($filterof == "living" && !$indi->isdead) || $filterof != "living") && ((!empty($filtersx) && $indi->sex == $filtersx) || empty($filtersx))) {
						
						if (preg_match("/$pregquery/i", $indi->gedrec) > 0 || (GedcomConfig::$USE_RTL_FUNCTIONS && (preg_match("/$preghbquery/i", $indi->gedrec) > 0 || ($preghbquery1!="" && preg_match("/$preghbquery1/i", $indi->gedrec) > 0) || ($preghbquery2!="" && preg_match("/$preghbquery2/i", $indi->gedrec) > 0) || ($preghbquery3 != "" && preg_match("/$preghbquery3/i", $indi->gedrec) > 0)))) {
							$filterout=false;
							$indifacts = $indi->SelectFacts($select, true);
							$text_fact = "";
							foreach($indifacts as $index => $factobj) {
								$text_temp = "";
								$ct = preg_match("/$pregquery/i", $factobj->factrec, $match);
								if ($ct < 1 && GedcomConfig::$USE_RTL_FUNCTIONS) $ct = preg_match("/$preghbquery/i", $factobj->factrec, $match);
								if ($ct < 1 && GedcomConfig::$USE_RTL_FUNCTIONS && $preghbquery1!="") $ct = preg_match("/$preghbquery1/i", $factobj->factrec, $match);
								if ($ct < 1 && GedcomConfig::$USE_RTL_FUNCTIONS && $preghbquery2!="") $ct = preg_match("/$preghbquery2/i", $factobj->factrec, $match);
								if ($ct < 1 && GedcomConfig::$USE_RTL_FUNCTIONS && $preghbquery3!="") $ct = preg_match("/$preghbquery3/i", $factobj->factrec, $match);
								if ($ct>0) {
									$text_temp .= FactFunctions::GetCalendarFact($factobj, $action, $filterof, $filterev);
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
				if ($filterev == "bdm") $select = array("MARR");
				else if ($filterev == "all") $select = "";
				else $select = array($filterev);
				if ($filtersx==""){
					foreach($myfamlist as $gid=>$fam) {
						$display=true;
						if ($filterof=="living"){
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
									if ($ct < 1 && GedcomConfig::$USE_RTL_FUNCTIONS) $ct = preg_match("/$preghbquery/i", $factobj->factrec, $match);
									if ($ct < 1 && GedcomConfig::$USE_RTL_FUNCTIONS && $preghbquery1!="") $ct = preg_match("/$preghbquery1/i", $factobj->factrec, $match);
									if ($ct < 1 && GedcomConfig::$USE_RTL_FUNCTIONS && $preghbquery2!="") $ct = preg_match("/$preghbquery2/i", $factobj->factrec, $match);
									if ($ct < 1 && GedcomConfig::$USE_RTL_FUNCTIONS && $preghbquery3!="") $ct = preg_match("/$preghbquery3/i", $factobj->factrec, $match);
									if ($ct>0) {
										$text_temp .= FactFunctions::GetCalendarFact($factobj, $action, $filterof, $filterev);
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
			if (($mmon!=strtolower($month)) && ($k>2)) {
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
	if ($filterof!="all") $showfilter = ($filterof=="living"? GM_LANG_living_only : GM_LANG_recent_events);
	if (!empty($filtersx)){
		if (!empty($showfilter)) $showfilter .= " - ";
		$showfilter .= ($filtersx=="M"?GM_LANG_male : GM_LANG_female);
	}
	if ($filterev != "all"){
		if (!empty($showfilter)) $showfilter .= " - ";
		if (defined("GM_FACT_".$filterev)) $showfilter .= constant("GM_FACT_".$filterev);
		else if (defined("GM_LANG_".$filterev)) $showfilter .= constant("GM_LANG_".$filterev);
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