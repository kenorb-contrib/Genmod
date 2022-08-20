<?php
/**
 * Date Functions that can be used by any page in GM
 *
 * The functions in this file are common to all GM pages and include date conversion
 * routines and sorting functions.
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
 * @package Genmod
 * @subpackage Dates
 * @version $Id: functions_date.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * security check to prevent hackers from directly accessing this file
 */
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
if (GedcomConfig::$CALENDAR_FORMAT=="hijri" || GedcomConfig::$CALENDAR_FORMAT=="arabic") {
	require_once("includes/functions/functions_date_hijri.php");
}

// Removed Hebrew check because one can have Hebrew disabled but the Gedcom can contain
// Hebrew dates.
// if ((stristr(GedcomConfig::$CALENDAR_FORMAT, "hebrew")!==false) || (stristr(GedcomConfig::$CALENDAR_FORMAT, "jewish")!==false) || GedcomConfig::$USE_RTL_FUNCTIONS) {
	require_once("includes/functions/functions_date_hebrew.php");
// }

/**
 * convert a date to other languages or formats
 *
 * converts and translates a date based on the selected language and calendar format
 * @param string $dstr_beg prepend this string to the converted date
 * @param string $dstr_end append the string to the converted date
 * @param int $day the day of month for the date
 * @param string $month the abbreviated month (ie JAN, FEB, MAR, etc)
 * @param int $year the year (ie 1900, 2004, etc)
 * @return string the new converted date
 */
function ConvertDate($dstr_beg, $dstr_end, $day, $month, $year) {
	global $DATE_FORMAT, $LANGUAGE, $monthtonum, $TEXT_DIRECTION;
	$altDay=30;

	$month = trim($month);
	$day = trim($day);
	$skipday = false;
	$skipmonth = false;
	if (empty($month)||!isset($monthtonum[strtolower($month)])) {
		$dstr_beg .= " ".$month." ";
		$month = "jan";
		$skipmonth=true;
	}
	if (empty($day)) {
		$day = 1;
		if ((!empty($month))&&(isset($monthtonum[$month]))){
			$yy = $year;
			//-- make sure there is always a year
			if (empty($yy)) $yy = date("Y");
			if (function_exists("cal_days_in_month")) $altDay = cal_days_in_month(CAL_GREGORIAN, $monthtonum[$month], $yy);
			else $altDay = 30;
		}
		$skipday = true;
	}
	if (GedcomConfig::$CALENDAR_FORMAT=="jewish" && $LANGUAGE != "hebrew" && !empty($year) && ! (preg_match("/^\d+$/", $year)==0)) {
		$month = $monthtonum[$month];
		$jd = gregoriantojd($month, $day, $year);
		$hebrewDate = jdtojewish($jd);
		list ($hebrewMonth, $hebrewDay, $hebrewYear) = explode ('/', $hebrewDate);
		$altJd = gregoriantojd($month, $altDay, $year);
		$altHebrewDate = jdtojewish($altJd);
		list ($altHebrewMonth, $altHebrewDay, $altHebrewYear) = explode ('/', $altHebrewDate);

		$hebrewMonthName = GetJewishMonthName($hebrewMonth, $hebrewYear);
		if($skipday && !$skipmonth && $altHebrewMonth !=0 && $altHebrewYear !=0 && $hebrewMonth != $altHebrewMonth && $hebrewYear != $altHebrewYear) { //elul tishrai
			$hebrewMonthName .= " ";
			$hebrewMonthName .= $hebrewYear;
			$hebrewYear = " / ";
			$hebrewYear .= GetJewishMonthName($altHebrewMonth, $altHebrewYear);
			$hebrewYear .= " ";
			$hebrewYear .= $altHebrewYear;
		} else if($skipday && !$skipmonth && $altHebrewMonth !=0 && $hebrewMonth != $altHebrewMonth) {
			$hebrewMonthName .= " / ";
			$hebrewMonthName .= GetJewishMonthName($altHebrewMonth, $altHebrewYear);
		} else if($altHebrewYear !=0 && $hebrewYear != $altHebrewYear && $skipday) {
			$hebrewYear .= " / ";
			$hebrewYear .= $altHebrewYear;
		}
		if ($skipday) $hebrewDay = "";
		if ($skipmonth) $hebrewMonthName = "";
		if ($DATE_FORMAT == "D. M Y" && $skipday)
			 $newdate = preg_replace("/D/", $hebrewDay, "D M Y");
		else $newdate = preg_replace("/D/", $hebrewDay, $DATE_FORMAT);
		$newdate = preg_replace("/M/", $hebrewMonthName, $newdate);
		$newdate = preg_replace("/Y/", $hebrewYear, $newdate);
		$datestr = $dstr_beg . $newdate . $dstr_end;
	}
	else if (GedcomConfig::$CALENDAR_FORMAT=="jewish_and_gregorian" && $LANGUAGE != "hebrew" && !empty($year) && ! (preg_match("/^\d+$/", $year)==0)) {
		$monthnum = $monthtonum[$month];
		$jd = gregoriantojd($monthnum, $day, $year);
		$hebrewDate = jdtojewish($jd);
		list ($hebrewMonth, $hebrewDay, $hebrewYear) = explode ('/', $hebrewDate);

		$altJd = gregoriantojd($monthnum, $altDay, $year);
		$altHebrewDate = jdtojewish($altJd);
		list ($altHebrewMonth, $altHebrewDay, $altHebrewYear) = explode ('/', $altHebrewDate);
		$hebrewMonthName = GetJewishMonthName($hebrewMonth, $hebrewYear);

		if($skipday && !$skipmonth && $altHebrewMonth !=0 && $altHebrewYear !=0 && $hebrewMonth != $altHebrewMonth && $hebrewYear != $altHebrewYear ) { //elul tishrai
			$hebrewMonthName .= " ";
			$hebrewMonthName .= $hebrewYear;
			$hebrewYear = " / ";
			$hebrewYear .= GetJewishMonthName($altHebrewMonth, $altHebrewYear);
			$hebrewYear .= " ";
			$hebrewYear .= $altHebrewYear;
		} else if($skipday && !$skipmonth && $altHebrewMonth !=0 && $hebrewMonth != $altHebrewMonth) {
			$hebrewMonthName .= " / ";
			$hebrewMonthName .= GetJewishMonthName($altHebrewMonth, $altHebrewYear);
		} else if($altHebrewYear !=0 && $hebrewYear != $altHebrewYear && $skipday) {
			$hebrewYear .= " / ";
			$hebrewYear .= $altHebrewYear;
		}

		if ($skipday) $hebrewDay = "";
		if ($skipmonth) $hebrewMonthName = "";
		if (!empty($year)) {
		if ($DATE_FORMAT == "D. M Y" && $skipday)
			 $newdate = preg_replace("/D/", $hebrewDay, "D M Y");
		else $newdate = preg_replace("/D/", $hebrewDay, $DATE_FORMAT);
		$newdate = preg_replace("/M/", $hebrewMonthName, $newdate);
		$newdate = preg_replace("/Y/", $hebrewYear, $newdate);
		}
		else $newdate="";
		if ($skipday) $day = "";
		if ($skipmonth) $month = "";
		if ($DATE_FORMAT == "D. M Y" && $skipday)
			 $gdate = preg_replace("/D/", $day, "D M Y");
		else $gdate = preg_replace("/D/", $day, $DATE_FORMAT);
		$gdate = preg_replace("/M/", $month, $gdate);
		$gdate = preg_replace("/Y/", $year, $gdate);
		$gdate = trim($gdate);
		$datestr = $dstr_beg . $newdate . " ($gdate)" . $dstr_end;
	}
	else if ((GedcomConfig::$CALENDAR_FORMAT=="hebrew" || (GedcomConfig::$CALENDAR_FORMAT=="jewish" && $LANGUAGE == "hebrew")) && !empty($year) && ! (preg_match("/^\d+$/", $year)==0)) {

		$month = $monthtonum[$month];
		$jd = gregoriantojd($month, $day, $year);
		$hebrewDate = jdtojewish($jd);
		list ($hebrewMonth, $hebrewDay, $hebrewYear) = split ('/', $hebrewDate);

		$altJd = gregoriantojd($month, $altDay, $year);
		$altHebrewDate = jdtojewish($altJd);
		list ($altHebrewMonth, $altHebrewDay, $altHebrewYear) = split ('/', $altHebrewDate);

		if ($skipday) $hebrewDay = "";
		if ($skipmonth) $hebrewMonth = "";
		$newdate = GetFullHebrewJewishDates($hebrewYear, $hebrewMonth, $hebrewDay, $altHebrewYear, $altHebrewMonth);
		$datestr = $dstr_beg . $newdate . $dstr_end;
	}
	else if ((GedcomConfig::$CALENDAR_FORMAT=="hebrew_and_gregorian" || (GedcomConfig::$CALENDAR_FORMAT=="jewish_and_gregorian" && $LANGUAGE == "hebrew")) && !empty($year) && ! (preg_match("/^\d+$/", $year)==0)) {
		$monthnum = $monthtonum[$month];
		//if (preg_match("/^\d+$/", $year)==0) $year = date("Y");
		$jd = gregoriantojd($monthnum, $day, $year);
		$hebrewDate = jdtojewish($jd);
		list ($hebrewMonth, $hebrewDay, $hebrewYear) = explode ('/', $hebrewDate);

		$altJd = gregoriantojd($monthnum, $altDay, $year);
		$altHebrewDate = jdtojewish($altJd);
		list ($altHebrewMonth, $altHebrewDay, $altHebrewYear) = explode ('/', $altHebrewDate);

		if ($skipday) $hebrewDay = "";
		if ($skipmonth) $hebrewMonth = "";
		if (!empty($year)) $newdate = GetFullHebrewJewishDates($hebrewYear, $hebrewMonth, $hebrewDay, $altHebrewYear, $altHebrewMonth);
		else $newdate = "";
		if ($skipday) $day = "";
		if ($skipmonth) $month = "";
		if ($DATE_FORMAT == "D. M Y" && $skipday)
			 $gdate = preg_replace("/D/", $day, "D M Y");
		else $gdate = preg_replace("/D/", $day, $DATE_FORMAT);
		$gdate = preg_replace("/M/", $month, $gdate);
		$gdate = preg_replace("/Y/", $year, $gdate);
		$gdate = trim($gdate);
		$datestr = $dstr_beg  . " ". $newdate . " ($gdate) ". $dstr_end;
	}
	else if (GedcomConfig::$CALENDAR_FORMAT=="julian") {
		$monthnum = $monthtonum[$month];
		$jd = gregoriantojd($monthnum, $day, $year);
		$jDate = jdtojulian($jd);
		list ($jMonth, $jDay, $jYear) = explode ('/', $jDate);
		$jMonthName = jdmonthname ( $jd, 3);
		if ($skipday) $jDay = "";
		if ($skipmonth) $jMonthName = "";
		$newdate = preg_replace("/D/", $jDay, $DATE_FORMAT);
		$newdate = preg_replace("/M/", $jMonthName, $newdate);
		$newdate = preg_replace("/Y/", $jYear, $newdate);
		$datestr = $dstr_beg . $newdate . $dstr_end;
	}
	else if (GedcomConfig::$CALENDAR_FORMAT=="hijri") {
		$monthnum = $monthtonum[$month];
		$hDate = GetHijri($day, $monthnum, $year);
		list ($hMonthName, $hDay, $hYear) = explode ('/', $hDate);
		if ($skipday) $hDay = "";
		if ($skipmonth) $hMonthName = "";
		$newdate = preg_replace("/D/", $hDay, $DATE_FORMAT);
		$newdate = preg_replace("/M/", $hMonthName, $newdate);
		$newdate = preg_replace("/Y/", $hYear, $newdate);
		$datestr = $dstr_beg . '<span dir="rtl" lang="ar-sa">'.$newdate . '</span>';
		if($TEXT_DIRECTION == "ltr") { //only do this for ltr languages
	  		$datestr .= "&lrm;"; //add entity to return to left to right direction
	  	}
		$datestr .= $dstr_end;
	}
	else if (GedcomConfig::$CALENDAR_FORMAT=="arabic") {
		$monthnum = $monthtonum[$month];
		$aDate = GetArabic($day, $monthnum, $year);
		list ($aMonthName, $aDay, $aYear) = explode ('/', $aDate);
		if ($skipday) $aDay = "";
		if ($skipmonth) $aMonthName = "";
		$newdate = preg_replace("/D/", $aDay, $DATE_FORMAT);
		$newdate = preg_replace("/M/", $aMonthName, $newdate);
		$newdate = preg_replace("/Y/", $aYear, $newdate);
		$datestr = $dstr_beg . '<span dir="rtl" lang="ar-sa">'.$newdate . '</span>';
		if($TEXT_DIRECTION == "ltr") { //only do this for ltr languages
	  		$datestr .= "&lrm;"; //add entity to return to left to right direction
	  	}
		$datestr .= $dstr_end;
	}
	else if (GedcomConfig::$CALENDAR_FORMAT=="french") {
		$monthnum = $monthtonum[$month];
		$jd = gregoriantojd($monthnum, $day, $year);
		$frenchDate = jdtofrench($jd);
		list ($fMonth, $fDay, $fYear) = explode ('/', $frenchDate);
		$fMonthName = jdmonthname ( $jd, 5);
		if ($skipday) $fDay = "";
		if ($skipmonth) $fMonthName = "";
		$newdate = preg_replace("/D/", $fDay, $DATE_FORMAT);
		$newdate = preg_replace("/M/", $fMonthName, $newdate);
		$newdate = preg_replace("/Y/", $fYear, $newdate);
		$datestr = $dstr_beg . $newdate . $dstr_end;
	}
	else {
		$temp_format = "~".$DATE_FORMAT;
		if ($skipday)
		{
		  //-- if the D is before the M the get the substr of everthing after the M
		  //-- if the D is after the M then just replace it
		  //-- @TODO figure out how to replace D. anywhere in the string
		  $pos1 = strpos($temp_format, "M");
		  $pos2 = strpos($temp_format, "D");
		  if ($pos2<$pos1) $temp_format = substr($temp_format, $pos1-1);
		  else $temp_format = preg_replace("/D/", "", $temp_format);
		}
		if ($skipmonth)
		{
		  $month = "";
		  $dpos_d_01 = strpos($temp_format, "M");
		  $dpos_d_00 = $dpos_d_01;
		  $dpos_d_02 = strlen($temp_format);
		  if ($dpos_d_01>0)
		  {
			while (!strpos("DY",$temp_format[$dpos_d_01]))
			{
			  $temp_format01 = substr($temp_format,0,$dpos_d_00);
			  $temp_format02 = substr($temp_format,$dpos_d_01);
			  $temp_format = $temp_format01.$temp_format02;
			  $dpos_d_02 = strlen($temp_format);
			  $dpos_d_01++;
			  if ($dpos_d_01 >= $dpos_d_02) break;
			}
		  }
		}
		$newdate = trim(substr($temp_format,1));

		if ($LANGUAGE=="chinese") {
			$day = ConvertNumber($day);
			$yearStr = "".$year;
			$year="";
			for($i=0; $i<strlen($yearStr); $i++) {
				$year .= ConvertNumber($yearStr{$i});
			}
		}
		$newdate = preg_replace("/D/", $day, $newdate);
		$newdate = preg_replace("/M/", $month, $newdate);
		$newdate = preg_replace("/Y/", $year, $newdate);
		$datestr = $dstr_beg . $newdate . $dstr_end;
	}
	return $datestr;
}

//-- end of Jewish date functions

//-- functions to take a date and display it in Finnish.
//-- provided by: KurtNorgaz
//-- updated by Meliza
function GetFinnishDate($datestr, $day) {

	//-- the Finnish text of the value for one date is shown at the end of the date
	//-- the Finnish values of two dates are replaced by a -
	$array_short = array("aft", "bet", "from", "to");
	foreach($array_short as $indexval => $value) {

	  $oldDateStr = $datestr;
	  $newdatestr = preg_replace("/$value([^a-zA-Z])/i", "" . "\$1", $datestr);
	  if ($newdatestr != $datestr) {

		$datestr = $newdatestr;

		switch ($value) {
		  case "from" : $datestr = trim($datestr);
						$temp_date = strtolower($datestr);
						$pos_of_to = strpos(" ".$temp_date, "to");
						$newdatestr = preg_replace("/to/", "", $temp_date);
						if ($newdatestr != $temp_date) {
							$datestr_01 = trim(substr($datestr, 0, $pos_of_to - 2));
							$datestr_02 = substr($datestr, $pos_of_to + 1);
							$datestr = $datestr_01." - ".$datestr_02." ";
						}
						else $datestr = $datestr." ".constant("GM_LANG_".$value);
						break;
		  case "bet"  : $datestr = trim($datestr);
						$temp_date = strtolower($datestr);
						$pos_of_and = strpos(" ".$temp_date, "and");
						$datestr_01 = trim(substr($datestr, 0, $pos_of_and - 2));
						$datestr_02 = substr($datestr, $pos_of_and + 2);
						if (strlen($datestr_01) > 0 && strlen($datestr_02) > 0)
							$datestr = $datestr_01." - ".$datestr_02." ";
						break;
		  case "to"   : $datestr = $newdatestr." ".constant("GM_LANG_".$value); break;
		  case "aft"  : $datestr = $newdatestr." ".constant("GM_LANG_".$value); break;
		  default	  : $datestr = $oldDateStr; break;
		}
	  }
	}
	//-- the Finnish text of the value is shown bau before the date
	$array_short = array("abt", "apx", "bef", "cal", "est", "int", "cir");
	foreach($array_short as $indexval => $value) {
		$datestr = preg_replace("/^$value([^a-zA-Z])/i", constant("GM_LANG_".$value)."\$1", $datestr);
		$datestr = preg_replace("/(\W)$value([^a-zA-Z])/i", "\$1".constant("GM_LANG_".$value)."\$2", $datestr);
	}
	//-- Constant 'ta' is appended to the Finnish month values, if a day value exists (for the last date)
	$array_short = array("jan", "feb", "mar", "apr", "may", "jun", "jul", "aug", "sep", "oct", "nov", "dec");
	foreach($array_short as $indexval => $value) {

	if ($day > 0) {
		 $datestr = preg_replace("/(\W)$value([^a-zA-Z])/i", "\$1".constant("GM_LANG_".$value)."ta"."\$2", $datestr);
		 $datestr = preg_replace("/^$value([^a-zA-Z])/i", constant("GM_LANG_".$value)."ta"."\$1", $datestr);
	  }
	else {
		 $datestr = preg_replace("/(\W)$value([^a-zA-Z])/i", "\$1".constant("GM_LANG_".$value)."\$2", $datestr);
		 $datestr = preg_replace("/^$value([^a-zA-Z])/i", constant("GM_LANG_".$value)."\$1", $datestr);
	  }
	}
	return $datestr;
}

//-- end of Finnish date functions

//-- functions to take a date and display it in Turkish.
//-- provided by: KurtNorgaz
function GetTurkishDate($datestr) {

	$array_short = array("jan", "feb", "mar", "apr", "may", "jun", "jul", "aug", "sep", "oct", "nov", "dec", "est");
	foreach($array_short as $indexval => $value)
	{
	  $datestr = preg_replace("/$value([^a-zA-Z])/i", constant("GM_LANG_".$value) . "\$1", $datestr);
	}

	$array_short = array("abt", "aft", "and", "bef", "bet", "cal", "from", "int", "to", "cir");

	foreach($array_short as $indexval => $value)
	{
	  $oldDateStr = $datestr;
	  $newdatestr = preg_replace("/$value([^a-zA-Z])/i", "" . "\$1", $datestr);

	  if ($newdatestr != $datestr)
	  {
		$pos_of_value = strpos(" " . $datestr, $value);
		$datestr = $newdatestr;

		switch ($value)
		{
		  case "from"	: $datestr = trim($datestr);
					  $pos_of_to = strpos(" " . $datestr, "to");
					  $datestr_01 = trim(substr($datestr, 0, $pos_of_to - 1));
					  $datestr_02 = substr($datestr, $pos_of_to - 2);

					  if (strlen($datestr_01) > 0)
					  {
						$last_char = $datestr[strlen($datestr_01)-1];
					  }
					  else $last_char = "";
					  switch ($last_char)
					  {
						case "0" : if (strlen($datestr_01) > 1)
								   {
									 $last_two_char = substr($datestr_01,-2);
								   }
								   else $last_two_char = "";
								   switch ($last_two_char)
								   {
									 case "00" : $extension = "den"; break;
									 case "20" : $extension = "den"; break;
									 case "50" : $extension = "den"; break;
									 case "70" : $extension = "den"; break;
									 case "80" : $extension = "den"; break;
									 default   : $extension = "dan"; break;
								   }
								   break;
						case "6" : $extension = "dan"; break;
						case "9" : $extension = "dan"; break;
						default  : $extension = "den"; break;
					  }
					  $datestr_01 .= stripslashes(constant("GM_LANG_".$value));
					  $datestr_01 = str_replace("#EXT#", $extension, $datestr_01);

					  $datestr = $datestr_01 . $datestr_02;
					  break;

		  case "to" 	: $datestr = trim($datestr);
					  if (strlen($datestr) > 0)
					  {
						$last_char = $datestr[strlen($datestr)-1];
					  }
					  else $last_char = "";
					  switch ($last_char)
					  {
						case "0" : $extension = "a"; break;
						case "9" : $extension = "a"; break;
						case "2" : $extension = "ye"; break;
						case "7" : $extension = "ye"; break;
						case "6" : $extension = "ya"; break;
						default  : $extension = "e"; break;
					  }
					  $datestr .= stripslashes(constant("GM_LANG_".$value));
					  $datestr = str_replace("#EXT#", $extension, $datestr);
					  break;

		  case "bef"	: $datestr = trim($datestr);
					  if (strlen($datestr) > 0)
					  {
						$last_char = $datestr[strlen($datestr)-1];
					  }
					  else $last_char = "";
					  switch ($last_char)
					  {
						case "0" : if (strlen($datestr) > 1)
								   {
									 $last_two_char = substr($datestr,-2);
								   }
								   else $last_two_char = "";
								   switch ($last_two_char)
								   {
									 case "00" : $extension = "den"; break;
									 case "20" : $extension = "den"; break;
									 case "50" : $extension = "den"; break;
									 case "70" : $extension = "den"; break;
									 case "80" : $extension = "den"; break;
									 default   : $extension = "dan"; break;
								   }
								   break;
						case "6" : $extension = "dan"; break;
						case "9" : $extension = "dan"; break;
						default  : $extension = "den"; break;
					  }
					  $datestr .= stripslashes(constant("GM_LANG_".$value));
					  $datestr = str_replace("#EXT#", $extension, $datestr);
					  break;

		  case "cir"	: $datestr .= stripslashes(constant("GM_LANG_".$value));
					  break;

		  default		: $datestr = $oldDateStr;
					  break;
		}
	  }
	}

	return $datestr;
}

//-- end of Turkish date functions


/**
 * parse a gedcom date
 *
 * this function will parse a gedcom date and convert it to the form defined by the language file
 * by calling the ConvertDate function
 * @param string $datestr the date string (ie everything after the DATE tag)
 * @return string the new date string
 */
function GetChangedDate($datestr, $CalYear="") {
	global $DATE_FORMAT, $LANGUAGE, $monthtonum, $dHebrew;

	$checked_dates = array();

	$datestr = trim($datestr);

	// INFANT CHILD STILLBORN DEAD DECEASED Y AUG ...
	if (preg_match("/\d/", $datestr)==0) 	{
		if (defined("GM_LANG_".$datestr)) return constant("GM_LANG_".$datestr);
		if (defined("GM_LANG_".Str2Upper($datestr))) return constant("GM_LANG_".Str2Upper($datestr));
		if (defined("GM_LANG_".Str2Lower($datestr))) return constant("GM_LANG_".Str2Lower($datestr));

	    if (stristr($datestr, "#DHEBREW")) {
			$datestr = preg_replace("/@([#A-Z]+)@/", "", $datestr);
			$pdate = ParseDate($datestr);
			if (defined("GM_LANG_".$pdate[0]["ext"]))				$tmp = constant("GM_LANG_".$pdate[0]["ext"])." ";
			else if (defined("GM_LANG_".Str2Upper($pdate[0]["ext"]))) $tmp = constant("GM_LANG_".Str2Upper($pdate[0]["ext"]))." ";
			else if (defined("GM_LANG_".Str2Lower($pdate[0]["ext"]))) $tmp = constant("GM_LANG_".Str2Lower($pdate[0]["ext"]))." ";
			else if ($pdate[0]["ext"]=="") $tmp = "";
	   	 	else return $datestr;
	   		if (isset($pdate[0]["mon"]))
		   		if ($LANGUAGE=="hebrew") $tmp .= GetHebrewJewishMonth($pdate[0]["mon"], $CalYear);
		   		else                     $tmp .= GetJewishMonthName($pdate[0]["mon"], $CalYear);
	    	else return $datestr;
        	return $tmp;
		}
		// abt Aug
		else {
		$pdate = ParseDate($datestr);
		if (defined("GM_LANG_".$pdate[0]["ext"])) 				$tmp = constant("GM_LANG_".$pdate[0]["ext"]);
		else if (defined("GM_LANG_".Str2Upper($pdate[0]["ext"]))) $tmp = constant("GM_LANG_".Str2Upper($pdate[0]["ext"]));
		else if (defined("GM_LANG_".Str2Lower($pdate[0]["ext"]))) $tmp = constant("GM_LANG_".Str2Lower($pdate[0]["ext"]));
		else return $datestr;
		if (defined("GM_LANG_".$pdate[0]["month"])) 				  $tmp .= " ".constant("GM_LANG_".$pdate[0]["month"]);
		else if (defined("GM_LANG_".Str2Upper($pdate[0]["month"]))) $tmp .= " ".constant("GM_LANG_".Str2Upper($pdate[0]["month"]));
		else if (defined("GM_LANG_".Str2Lower($pdate[0]["month"]))) $tmp .= " ".constant("GM_LANG_".Str2Lower($pdate[0]["month"]));
		else return $datestr;
        return $tmp;
        // 2 DATE from aug to sep or 2 DATE bet aug and sep still printed as are
		}
	}

	// need day of the week ?
	if (!strpos($datestr, "#") && (strpos($DATE_FORMAT, "F") or strpos($DATE_FORMAT, "d") or strpos($DATE_FORMAT, "j"))) {
		$dateged = "";
		$pdate = ParseDate($datestr);
		$i=0;
		while (!empty($pdate[$i]["year"])) {
			$day = @$pdate[$i]["day"];
			$mon = @$pdate[$i]["mon"];
			$year = $pdate[$i]["year"];
			if (!empty($day)) {
				$fmt = $DATE_FORMAT; // D j F Y
				$fmt = str_replace("R", "", $fmt); // R = french Revolution date
				$adate = adodb_date($fmt, adodb_mktime(0, 0, 0, $mon, $day, $year));
			}
			else if (!empty($mon)) $adate = constant("GM_LANG_".strtolower($pdate[$i]["month"]))." ".$year;
			else $adate=$year;
			// already in english !
			if ($LANGUAGE!="english") {
				foreach (array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December") as $indexval => $item) {
					// February => F�vrier
					$translated = constant("GM_LANG_".substr(strtolower($item),0,3));
					$adate = str_replace($item, $translated, $adate);
					// Feb => F�v
					$item = substr($item, 0, 3);
					$translated = substr($translated, 0, 3);
					$adate = str_replace($item, $translated, $adate);
				}
				foreach (array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday") as $indexval => $item) {
					// Friday => Vendredi
					$translated = constant("GM_LANG_".strtolower($item));
					$adate = str_replace($item, $translated, $adate);
					// Fri => Ven
					$item = substr($item, 0, 3);
					$translated = substr($translated, 0, 3);
					$adate = str_replace($item, $translated, $adate);
				}
			}
			// french first day of month
			if ($LANGUAGE=="french") $adate = str_replace(" 1 ", " 1er ",$adate);
			// french calendar from 22 SEP 1792 to 31 DEC 1805
			if (!empty($day) and strpos($DATE_FORMAT, "R") and function_exists("gregoriantojd")) {
				if ( (1792<$year and $year<1806) or ($year==1792 and ($mon>9 or ($mon==9 and $day>21)))) {
					$jd = gregoriantojd($mon, $day, $year);
					$frenchDate = jdtofrench($jd);
					list ($fMonth, $fDay, $fYear) = explode ('/', $frenchDate);
					$fMonthName = jdmonthname ($jd, 5);
					$adate .= " <u>$fDay $fMonthName An $fYear</u> ";
				}
			}
			if (isset($pdate[$i]["ext"])) {
				$txt = strtolower($pdate[$i]["ext"]);
				if (defined("GM_LANG_".$txt)) $txt = constant("GM_LANG_".$txt);
				else $txt = $pdate[$i]["ext"];
				$adate = $txt. " ". $adate . " ";
			}
			$dateged .= $adate;
			$i++;
		}
		if (!empty($dateged)) return trim($dateged);
	}

	//-- Is the date a Hebrew date
	if (stristr($datestr, "#DHEBREW")) {
		 $dHebrew=1;
		 $datestr = preg_replace("/@([#A-Z]+)@/", "", $datestr);

	}
	else $dHebrew=0;
	//-- check for DAY MONTH YEAR dates

	$Dt = "";
	$ct = preg_match_all("/(\d{1,2}\s)?([a-zA-Z]{3})?\s?(\d{4})?/", $datestr, $match, PREG_SET_ORDER);
	for($i=0; $i<$ct; $i++) {
		$match[$i][0] = trim($match[$i][0]);
		if ((!empty($match[$i][0]))&&(!in_array($match[$i][0], $checked_dates))) {
			if (!empty($match[$i][1])) $day = trim($match[$i][1]);
			else $day = "";
			if (!empty($match[$i][2])) $month = strtolower($match[$i][2]);
			else $month = "";

			if (isset($monthtonum[$month])&&(preg_match("/".$month."[a-z]/i", $datestr)==0)) {
								$checked_dates[] = $match[$i][0];
				if (!empty($match[$i][3])) $year = $match[$i][3];
				else $year = "";
				$pos1 = strpos($datestr, $match[$i][0]);
				$pos2 = $pos1 + strlen($match[$i][0]);
				$dstr_beg = substr($datestr, 0, $pos1);
				$dstr_end = substr($datestr, $pos2);
				//-- sometimes with partial dates a space char is found in the match and not added to the dstr_beg string
				//-- the following while loop will check for spaces at the start of the match and add them to the dstr_beg
				$j=0;
				while(($j<strlen($match[$i][0]))&&($match[$i][0]{$j}==" ")) {
					$dstr_beg.=" ";
					$j++;
				}
				//<-- Day zero-suppress
				if ($day > 0 && $day < 10) $day = preg_replace("/0/", ""."\$1", $day);
				if (!$dHebrew) {
					$datestr = ConvertDate($dstr_beg, $dstr_end, $day, $month, $year);
					if ($day != "") $Dt = $day;
				}
				else {
					if (!function_exists("ConvertHdate")) require_once("includes/functions/functions_date_hebrew.php");
					$datestr = ConvertHdate($dstr_beg, $dstr_end, $day, $month, $year);

					$Dt = "";
				}
			}
			else $month="";
		}
	}
	if (!isset($month)) $month="";
	//-- search for just years because the above code will only allow dates with a valid month to pass
	//-- this will make sure years get converted for non romanic alphabets such as hebrew
	$ct = preg_match_all("/.?(\d\d\d\d)/", $datestr, $match, PREG_SET_ORDER);

	if ((stristr(GedcomConfig::$CALENDAR_FORMAT, "hebrew")!==false) || (stristr(GedcomConfig::$CALENDAR_FORMAT, "jewish")!==false) || ($dHebrew)) { // check if contain hebrew dates then heared also with hebrew date!!!!
//	if ((stristr(GedcomConfig::$CALENDAR_FORMAT, "hebrew")!==false) || (stristr(GedcomConfig::$CALENDAR_FORMAT, "jewish")!==false) || $dHebrew || GedcomConfig::$USE_RTL_FUNCTIONS) {

		$checked_dates_str = implode(",", $checked_dates);
		for($i=0; $i<$ct; $i++) {
			$match[$i][0] = trim($match[$i][0]);
			if ((!empty($match[$i][0]))&&(stristr($checked_dates_str, $match[$i][0])===false)&&(strstr($match[$i][0], "#")===false)) {
				$checked_dates_str .= ", ".$match[$i][0];
				$day = "";
				$month = "";
				$year = $match[$i][1];

				if ($year<4000 || $dHebrew) {
					$pos1 = strpos($datestr, $match[$i][0]);
					$pos2 = $pos1 + strlen($match[$i][0]);
					$dstr_beg = substr($datestr, 0, $pos1);
					$dstr_end = substr($datestr, $pos2);
					if (!$dHebrew) $datestr = ConvertDate($dstr_beg, $dstr_end, $day, $month, $year);
					else $datestr = ConvertHdate($dstr_beg, $dstr_end, $day, $month, $year);
				}
			}
		}
	}
	else if (GedcomConfig::$CALENDAR_FORMAT=="hijri") {
		$checked_dates_str = implode(",", $checked_dates);
		for($i=0; $i<$ct; $i++) {
			$match[$i][0] = trim($match[$i][0]);
			if ((!empty($match[$i][0]))&&(stristr($checked_dates_str, $match[$i][0])===false)&&(strstr($match[$i][0], "#")===false)&&(stristr($datestr, $match[$i][0]."</span>")===false)) {
				$checked_dates_str .= ", ".$match[$i][0];
				$day = "";
				$month = "";
				$year = $match[$i][1];
				//if ($year<4000) {
					$pos1 = strpos($datestr, $match[$i][0]);
					$pos2 = $pos1 + strlen($match[$i][0]);
					$dstr_beg = substr($datestr, 0, $pos1);
					$dstr_end = substr($datestr, $pos2);
					$datestr = ConvertDate($dstr_beg, $dstr_end, $day, $month, $year);
				//}
			}
		}
	}

	if ($LANGUAGE == "turkish") $datestr = GetTurkishDate($datestr);
	else {
	if ($LANGUAGE == "finnish") $datestr = GetFinnishDate($datestr, $Dt);
	else {
		$array_short = array("jan", "feb", "mar", "apr", "may", "jun", "jul", "aug", "sep", "oct", "nov", "dec", "abt", "aft", "and", "bef", "bet", "cal", "est", "from", "int", "to", "cir", "apx");
		foreach($array_short as $indexval => $value){
			$datestr = preg_replace("/(\W)$value([^a-zA-Z])/i", "\$1".constant("GM_LANG_".$value)."\$2", $datestr);
			$datestr = preg_replace("/^$value([^a-zA-Z])/i", constant("GM_LANG_".$value)."\$1", $datestr);
		}
	  }
	}
	return $datestr;
}

/**
 * Create an anchor url to the calendar for a date
 *
 * Create an anchor url to the calendar for a date and parses the date using the get changed date
 * function
 *
 * @todo Do not create a url link for dates as stillborn, infant, child, dead, deceased, Y
 * @todo Do not create a today url link for dates from the monthly Day not set box, inform editors about the error
 * @author Roland (botak)
 * @param string $datestr the date string (ie everything after the DATE tag)
 * @return string a converted date with anchor html tags around it <a href="">date</a>
 */
function GetDateUrl($datestr, $CalYear=""){
	global $monthtonum;
	
    if (!stristr($datestr, "#DHEBREW") || GedcomConfig::$USE_RTL_FUNCTIONS) {

//		Commented out 2 lines as I don't know why they are here
//		$datestrip = preg_replace("/[a-zA-Z]/", "", $datestr);
//		$datestrip = trim($datestrip);

		$checked_dates = array();
		$tmpdatelink = "";
		//-- added trim to datestr to fix broken links produced by matches to a single space char
		$ct = preg_match_all("/(\d{1,2}\s)?([a-zA-Z]{3})?\s?(\d{3,4})?/", trim($datestr), $match, PREG_SET_ORDER);

		for($i=0; $i<$ct; $i++) {
			$tmp = strtolower(trim($datestr));
			if (substr($tmp,0,3)=="bet" || (substr($tmp,0,4)=="from" && substr(stristr($tmp, "to"),0,3)=="to ")) {
			// Checks if date is bet(ween)..and or from..to
				$cb = preg_match_all("/ (\d\d\d\d|\d\d\d)/", trim($datestr), $match_bet, PREG_SET_ORDER);

                    if (stristr($datestr, "#DHEBREW") && GedcomConfig::$USE_RTL_FUNCTIONS) {

                    	$cm = preg_match_all("/([a-zA-Z]{2,4})?\s?([a-zA-Z]{7})?\s?(\d{1,2}\s)?([a-zA-Z]{3})?\s?(\d{3,4})?/", trim($datestr), $match_bet, PREG_SET_ORDER);
						$dateheb = array();

						    //from date
						    if (isset($match_bet[5][3]) && $match_bet[5][3]!="") $date[0]["day"]   = $match_bet[5][3];
						    else if (trim($match_bet[5][0])==trim($match_bet[5][4]) && trim($match_bet[11][0])==trim($match_bet[11][4]))
						    							$date[0]["day"]   = '30';
						    else               			$date[0]["day"]   = '01';
						    if ($match_bet[5][4]!="")   $date[0]["mon"]   = $monthtonum[Str2Lower($match_bet[5][4])];
 							else               			$date[0]["mon"]   = '01';
 							if (isset($match_bet[5][5]) && $match_bet[5][5]!="") $date[0]["year"]  = $match_bet[5][5];
 							$date[0]["month"] = "";

						    if (isset($match_bet[12][3]) && $match_bet[12][3]!="")
						         $date[1]["day"]   = $match_bet[12][3];
						    else if (isset($match_bet[11][3]) && $match_bet[11][3]!="")
						         $date[1]["day"]   = $match_bet[11][3];
						    else $date[1]["day"]   = '30';
						    if (isset($match_bet[12][4]) && $match_bet[12][4]!="rew" && $match_bet[12][4]!="")
						         $date[1]["mon"]   = $monthtonum[Str2Lower($match_bet[12][4])];
						    else if (isset($match_bet[11][4]) && $match_bet[11][4]!="rew" && $match_bet[11][4]!="")
						         $date[1]["mon"]   = $monthtonum[Str2Lower($match_bet[11][4])];
 							else $date[1]["mon"]   = '13';
 							if (isset($match_bet[12][5]) && $match_bet[12][5]!="")
 							     $date[1]["year"]  = $match_bet[12][5];
 							else if (isset($match_bet[11][5]) && $match_bet[11][5]!="")
 							     $date[1]["year"]  = $match_bet[11][5];
 							$date[1]["month"] = "";
 							if (isset($date[1]["year"]) && $date[1]["year"] !="" && !isset($date[0]["year"]))
 							     $date[0]["year"] = $date[1]["year"];
 							if (isset($date[0]["year"]) && $date[0]["year"] !="" && !isset($date[1]["year"]))
 							     $date[1]["year"] = $date[0]["year"];

 							if ((isset($match_bet[5][5]) && isset($match_bet[12][5]) && $match_bet[5][5]>$match_bet[12][5]) ||
 							    (isset($match_bet[5][5]) && isset($match_bet[11][5]) && $match_bet[5][5]>$match_bet[11][5])) {
	 							$date[2] = $date[0];
	 							$date[0] = $date[1];
	 							$date[1] = $date[2];
 							}

 							if (!empty($date[0]["year"]) && !empty($date[1]["year"])) {
                                                		$dateheb = JewishGedcomDateToGregorian($date);
                                                		$action = "year";
                            }
                            else {
                            							$dateheb = JewishGedcomDateToCurrentGregorian($date);
                                                		$action = "today";
                                 }
                            if (trim($match_bet[5][0])==trim($match_bet[5][4]) && trim($match_bet[11][0])==trim($match_bet[11][4])) {
 									  $action = "calendar";
							}

    						if (!empty($dateheb[0]["day"]))
    													$start_day 		= $dateheb[0]["day"];
    						else                        $start_day     	= "";
    						if (!empty($dateheb[0]["month"]))
    													$start_month   	= $dateheb[0]["month"];
    						else                        $start_month   	= "";
    						if (!empty($dateheb[0]["year"]))
    													$start_year    	= $dateheb[0]["year"];
    						else                        $start_year    	= "";

    						if (!empty($dateheb[1]["day"]))
    													$end_day 		= $dateheb[1]["day"];
    						else                        $end_day     	= "";
    						if (!empty($dateheb[1]["month"]))
    													$end_month   	= $dateheb[1]["month"];
    						else                        $end_month   	= "";
    						if (!empty($dateheb[1]["year"]))
    													$end_year    	= $dateheb[1]["year"];
    						else                        $end_year    	= "";
                    }
                    else {

						if (!empty($match_bet[0][0])) $start_year = trim($match_bet[0][0]);
						else $start_year = "";
						if (!empty($match_bet[1][0])) $end_year = trim($match_bet[1][0]);
						else $end_year = "";
						if ($start_year>$end_year){
							$datelink = $start_year;
							$start_year = $end_year;
							$end_year = $datelink;
						}
						if ($start_year !="" || $end_year != "")
							$action = "year";
						else {

                    	$cm = preg_match_all("/([a-zA-Z]{2,4})?\s?(\d{1,2}\s)?([a-zA-Z]{3})?\s?(\d{3,4})?/", trim($datestr), $match_bet, PREG_SET_ORDER);

							if (empty($match_bet[0][2]) && empty($match_bet[1][2]))
								 $action = "calendar";
							else $action = "today";

						    if (!empty($match_bet[0][2])) $start_day = trim($match_bet[0][2]);
						    else $start_day = "";
						    if (!empty($match_bet[0][3])) $start_month = trim($match_bet[0][3]);
						    else $start_month = "";
						    if (!empty($match_bet[1][2])) $end_day = trim($match_bet[1][2]);
						    else $end_day = "";
						    if (!empty($match_bet[1][3])) $end_month = trim($match_bet[1][3]);
						    else $end_month = "";
						}
					}

					$datelink = '<a class="FactDate" href="calendar.php?link=11&amp;gedid='.GedcomConfig::$GEDCOMID.'&amp;';
					If ($action == "year" && ((isset($start_year) && strlen($start_year)>0) ||
					    (isset($end_year) && strlen($end_year)>0))) {
						if (isset($start_year) && strlen($start_year)>0) $datelink .= "year=".$start_year;
						if (isset($end_year) && strlen($end_year)>0 && isset($start_year) && strlen($start_year)>0 && $start_year!=$end_year)
						   $datelink .= "-";
				    	if (!isset($start_year) || strlen($start_year)==0 && isset($end_year) && strlen($end_year) > 0) $datelink .= "year=";
						if (isset($end_year) && strlen($end_year) > 0 && $start_year!=$end_year) $datelink .= $end_year;
					}
					else if ($action == "today" || $action == "calendar") {
					  if (isset($start_day) && strlen($start_day) > 0 && isset($start_month) && strlen($start_month) > 0) {
						if (isset($start_year) && strlen($start_year) > 0) $datelink .= "year=".$start_year;
						else if (isset($end_year) && strlen($end_year) > 0) $datelink .= "year=".$end_year;
						if ($action == "today") $datelink .= "&amp;day=".$start_day;
					    $datelink .= "&amp;month=".$start_month;
					  }
					  else if (isset($end_day) && strlen($end_day) > 0 && isset($end_month) && strlen($end_month) > 0) {
						if (isset($end_year) && strlen($end_year) > 0) $datelink .= "year=".$end_year;
						if ($action == "today") $datelink .= "&amp;day=".$end_day;
					    $datelink .= "&month=".$end_month;
				      }
				      else if (isset($start_month) && strlen($start_month) > 0) $datelink .= "&month=".$start_month;
				    }
					$datelink .= '&amp;filterof=all&amp;action='.$action.'">';
                    if (isset($match_bet[5][4])	&& isset($match_bet[11][4])) {
						if (trim($match_bet[5][0])==trim($match_bet[5][4]) && trim($match_bet[11][0])==trim($match_bet[11][4])) {
							$tmp       = GetChangedDate($match_bet[0][1]." @#DHEBREW@ ".$match_bet[5][0]);
							$tmp      .= " ".GetChangedDate($match_bet[6][1]." @#DHEBREW@ ".$match_bet[11][0]);
							$datelink .= $tmp."</a>";
						}
			        }

			        else //---- MA
 			        if ($action == "calendar" && $match_bet[0][2]=="" && isset($match_bet[0][3]) && $match_bet[1][2]=="" && isset($match_bet[1][3]))
 			        {
 				        	$tmp       = GetChangedDate($match_bet[0][0]);
 							$tmp      .= " ".GetChangedDate($match_bet[1][0]);
 							$datelink .= $tmp."</a>";
			        }
 			        else $datelink .= GetChangedDate($datestr)."</a>";
			}
			else {
				$match[$i][0] = trim($match[$i][0]);

				if ((!empty($match[$i][0]))&&(!in_array($match[$i][0], $checked_dates))) {
					$checked_dates[] = $match[$i][0];
					if (!empty($match[$i][1])) $day = trim($match[$i][1]);
					else $day = "";

					if (isset($match[$i][2])) $tmpmnth = strtolower($match[$i][2]);
					if ((isset($tmpmnth) && isset($monthtonum[$tmpmnth])) || $tmpdatelink=="")
						if (!empty($tmpmnth)) $month = $tmpmnth;
						else $month = "";
					if (!isset($monthtonum[$month])) $month=""; // abt and ust (of august) are not a month !

					if (!empty($match[$i][3])) $year = $match[$i][3];
					else $year = "";

					if ($tmpdatelink=="") {
						if (!empty($day) && !empty($month)) 	$tmpdatelink  = "today";
						else if (!empty($year))   				$tmpdatelink  = "year";
						     else if (!empty($month))   		$tmpdatelink  = "calendar";
						          else 							$tmpdatelink  = "";
						$tmplink = '">';
					}

					if (stristr($datestr, "#DHEBREW") && GedcomConfig::$USE_RTL_FUNCTIONS) {
						    $dateheb = array();
						    if ($day!="")      			$date[0]["day"]   = $day;
						    else if ($tmpdatelink=="calendar") $date[0]["day"]   = '30';
						         else                   $date[0]["day"]   = '01';
						    if ($month>0 && $month<14)  $date[0]["mon"]   = $month;
						    else if ($month!="")    	$date[0]["mon"]   = $monthtonum[Str2Lower($month)];
 							     else               	$date[0]["mon"]   = '01';
 							if (!empty($year)) 		    $date[0]["year"]  = $year;
 							else if (!empty($CalYear) && $tmpdatelink=="today") $date[0]["year"] = $CalYear;
 							if ($tmpdatelink=="year" && $day=="" && !empty($year)) {
	 							                        $date[1]["day"]   = '30';
	 							    	                $date[1]["mon"]   = '13';
 														$date[1]["year"]  = $year;
						    }

                            if (!empty($date[0]["year"]))
                                                		$dateheb = JewishGedcomDateToGregorian($date);
                            else                  		$dateheb = JewishGedcomDateToCurrentGregorian($date);

    						if (!empty($dateheb[0]["day"]))
    													$day 	= $dateheb[0]["day"];
    						else                        $day     = "";
    						if (!empty($dateheb[0]["month"]))
    													$month   = $dateheb[0]["month"];
    						else                        $month   = "";
    						if (!empty($dateheb[0]["year"]))
    													$year    = $dateheb[0]["year"];
    						else                        $year    = "";
					}
					$datelink = '<a class="FactDate" href="calendar.php?link=12&amp;gedid='.GedcomConfig::$GEDCOMID.'&amp;';
					if (isset($day) && strlen($day) > 0) 	 $datelink 	.= "day=".$day."&amp;";
					if (isset($month) && strlen($month) > 0) $datelink 	.= "month=".$month."&amp;";
					if (isset($year) && strlen($year) > 0)   $datelink 	.= "year=".$year;
					if (isset($dateheb[1]["year"]) && $year!=$dateheb[1]["year"] && $tmpdatelink=="year")
															 $datelink .= "-".$dateheb[1]["year"]."&amp;";
					else 									 $datelink .= "&amp;";
					$datelink .= "filterof=all&amp;action=";
					$datelink .= $tmpdatelink.$tmplink;
					$datelink .= GetChangedDate($datestr)."</a>";
				}
			}
		}
		if (!isset($datelink)) $datelink="";
		return $datelink;
	}
	else {
		$datelink = GetChangedDate($datestr);
		return $datelink;
	}
}


/**
 * parse a gedcom date into an array
 *
 * parses a gedcom date IE 1 JAN 2002 into an array of month day and year values
 * @param string $datestr		The date to parse
 * @return array		returns an array with indexes "day"=1 "month"=JAN "mon"=1 "year"=2002 "ext" = abt
 */
function ParseDate($datestr) {
	global $monthtonum, $MON_SHORT;
	static $monthshort, $ests;

	if (!isset($ests)) $ests = array("abt", "aft", "and", "bef", "bet", "cal", "from", "int", "to", "cir", "est", "apx");
	
	// Setup the locale translation
	if (!empty($MON_SHORT)) {
		if (!isset($monthshort)) {
			$array_short = array("JAN", "FEB", "MAR", "APR", "MAY", "JUN", "JUL", "AUG", "SEP", "OCT", "NOV", "DEC");
			$ms = preg_split("/,/", $MON_SHORT);
			if (count($ms) == count($array_short)) {
				$monthshort = array();
				foreach ($array_short as $key => $value) {
					$monthshort[Str2Upper($ms[$key])] = Str2Upper($value);
				}
			}
		}
	}

	$datestr = trim($datestr);
	$dates = array();
	$dates[0]["day"] = ""; //1;
	$dates[0]["month"] = ""; //"JAN";
	$dates[0]["mon"] = ""; //1;
	$dates[0]["year"] = 0;
	$dates[0]["ext"] = "";
	$strs = preg_split("/[\s\.,\-\\/\(\)\[\]\+'<>\&]+/", $datestr, -1, PREG_SPLIT_NO_EMPTY);
//	print "<br /><br />strs: ";
//	print_r($strs);
//	print "<br />";
	$index = 0;
//	$longmonth = array("january"=>"jan", "february"=>"feb", "march"=>"mar", "april"=>"apr", "may"=>"may", "june"=>"jun", "july"=>"jul", "august"=>"aug", "september"=>"sep", "october"=>"oct", "november"=>"nov", "december"=>"dec");
	// Removed may, as this causes updates on every May, MAY, etc.
	$longmonth = array("january"=>"jan", "february"=>"feb", "march"=>"mar", "april"=>"apr", "june"=>"jun", "july"=>"jul", "august"=>"aug", "september"=>"sep", "october"=>"oct", "november"=>"nov", "december"=>"dec");
	$loclongmonth = array(GM_LANG_jan=>"jan", GM_LANG_feb=>"feb", GM_LANG_mar=>"mar", GM_LANG_apr=>"apr", GM_LANG_may=>"may", GM_LANG_jun=>"jun", GM_LANG_jul=>"jul", GM_LANG_aug=>"aug", GM_LANG_sep=>"sep", GM_LANG_oct=>"oct", GM_LANG_nov=>"nov", GM_LANG_dec=>"dec");


	// This converts the current language long month names to short ones (Mei -> MAY)
	// If not found, it tries to convert the english month name to a short one (january -> jan)
	for($i=0; $i<count($strs); $i++) {
		// Check for locale long months
		if (isset($loclongmonth[strtolower($strs[$i])])) {
			$strs[$i] = $loclongmonth[strtolower($strs[$i])];
		}
		// Check for English long months
		elseif (isset($longmonth[strtolower($strs[$i])])) {
			$strs[$i] = $longmonth[strtolower($strs[$i])];
		}
		// Check for locale short month names
		else if (isset($monthshort[Str2Upper($strs[$i])])) {
			$strs[$i] = $monthshort[Str2Upper($strs[$i])];
		}
	}
	//-- this section will convert a date like 2005.10.10 to 10 oct 2005
	if (count($strs)==3) {
		if ($strs[0]>31) {
			$strs[1] = array_search($strs[1], $monthtonum);
			$strs = array_reverse($strs);
		}
	}

	
	for($i=0; $i<count($strs); $i++) {
		//print "Processing ".$strs[$i]."<br />";
		$ct = preg_match("/^\d+$/", $strs[$i]);
		if ($ct>0) {
			// If it's the first or second element AND the value <32, we have day or month.
			// Otherwise it's only a year. The last element always is year.
			if (isset($strs[$i+1]) && ($strs[$i]<32)) {
				// If day is not yet set, fill day
				if (empty($dates[$index]["day"])) $dates[$index]["day"] = $strs[$i];
				// Otherwise, it's month
				else if (empty($dates[$index]["mon"])) {
					$dates[$index]["mon"] = $strs[$i];
					$dates[$index]["month"] = array_search($strs[$i], $monthtonum);
				}
			}
			else {
				$dates[$index]["year"] = (int)$strs[$i];
				$index++;
			}
		}
		else {
			// it's not a number, so we try to recognize a month name
			if (isset($monthtonum[strtolower($strs[$i])])) {
				$dates[$index]["month"] = $strs[$i];
				$dates[$index]["mon"] = $monthtonum[strtolower($strs[$i])];
			}
			else {
				// If no month name found, we presume it's a prefix
				if (!isset($dates[$index]["ext"])) $dates[$index]["ext"] = "";
				// Check if the user typed the prefix in his language
				$abbr = array(GM_LANG_abt, GM_LANG_aft, GM_LANG_bef, GM_LANG_bet, GM_LANG_cal, GM_LANG_est, GM_LANG_from, GM_LANG_int, GM_LANG_cir, GM_LANG_apx, GM_LANG_and, GM_LANG_to);
				$pref = strtolower(array_search($strs[$i], $abbr));
				if ($pref && in_array($pref, $ests)) $strs[$i] = strtoupper($pref);
				$dates[$index]["ext"] .= strtoupper($strs[$i]);
			}
		}
	}
	return $dates;
}
	
function GetCurrentYear() {
	static $year;
	if (!isset($year)) {
		if (isset($_SESSION["timediff"])) $time = time()-$_SESSION["timediff"];
		else $time = time();
		$year = date("Y", $time);
	}
	return $year;
}
function GetCurrentMonth() {
	static $month;
	if (!isset($month)) {
		if (isset($_SESSION["timediff"])) $time = time()-$_SESSION["timediff"];
		else $time = time();
		$month = date("M", $time);
	}
	return $month;
}
function GetCurrentDay() {
	static $day;
	if (!isset($day)) {
		if (isset($_SESSION["timediff"])) $time = time()-$_SESSION["timediff"];
		else $time = time();
		$day = date("j", $time);
	}
	return $day;
}
?>
