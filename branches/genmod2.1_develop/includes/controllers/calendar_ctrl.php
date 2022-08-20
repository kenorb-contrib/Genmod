<?php
/**
 * Controller for the calendar
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
 * @package Genmod
 * @subpackage Charts
 * @version $Id: calendar_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class CalendarController extends ChartController {
	
	public $classname = "CalendarController";		// Name of this class
	
	// Inputs
	private $year = null;							// Input value for the year option
	private $month = null;							// Input value for month and day option
	private $day = null;							// Input value for day option
	private $filterof = null;						// Selector for persons (all, living, recent)
	private $filterev = null;						// Selector for the type of event
	private $filtersx = null;						// Selector for the gender: M, F or nothing=all
	// action values are calendar, today and year
	
	// Internal values
	private $hebrewfound = null;					// Check if any dates are in hebrew
	private $datearray = null;						// Array to keep the dates to be converted to Hebrew etc. =>not called from calendar
	private $date = null;							// Array to keep the dates converted to Hebrew etc. =>not called from calendar
	private $hDay = null;							// (First) day of date entered in Hebrew
	private $hMonth = null;							// (First) month of date entered in Hebrew
	private $hYear = null;							// (First) year of date entered in Hebrew
	private $year_text = null;						// Year (range) to print in the titlebar =>not called from calendar
	private $m_days = null;							// Number of days in the selected month
	private $CalYear = null;					
	private $currhDay = null;					
	private $currhMonth = null;					
	private $currhYear = null;					
	private $leap = null;							// True or false a leap year
	private $bartext = null;						// Text with dates to print in the topbar
	
	// for year
	private $startyear = null;						// First year of year range (used for search parameters)
	private $endyear = null;						// Last year of year range (used for search parameters)
	private $hstartyear = null;						// First year of year range in hebrew calendar (used for search parameters)
	private $hendyear = null;						// Last year of year range in hebrew calendar (used for search parameters)
	
	// for calendar
	private $show_no_day = null;					// In which column show the persons with a date in which the day is not set
	private $lastday = null;						// Have to find this out...
	
	private $myfamlist = null;						// Holder for the resulting familylist
	private $myindilist = null;						// Holder for the resulting individuallist
	
	public function __construct() {
		global $nonfacts, $nonfamfacts;
		
		parent::__construct();
		
		// -- array of GEDCOM elements that will be found but should not be displayed
		$nonfacts[] = "FAMS";
		$nonfacts[] = "FAMC";
		$nonfacts[] = "MAY";
		$nonfacts[] = "BLOB";
		$nonfacts[] = "CHIL";
		$nonfacts[] = "HUSB";
		$nonfacts[] = "WIFE";
		$nonfacts[] = "RFN";
		$nonfacts[] = "";
		$nonfamfacts[] = "UID";
		$nonfamfacts[] = "RESN";
		
		
		// Get the day
		if (!isset($_REQUEST["day"]) || $_REQUEST["day"] == "") $this->day = adodb_date("j");
		else $this->day = $_REQUEST["day"];
		
		// Get the month
		if (!isset($_REQUEST["month"]) || $_REQUEST["month"] == "") $this->month = adodb_date("M");
		else $this->month = $_REQUEST["month"];
		
		// Get the yearstring
		if (!isset($_REQUEST["year"]) || $_REQUEST["year"] == "") $this->year = adodb_date("Y");
		else $this->year = $_REQUEST["year"];
		
		// Get the action
		if ($this->action == "") $this->action = "today";
		
		// Get the filter event
		if (!isset($_REQUEST["filterev"]) || $_REQUEST["filterev"] == "") $this->filterev = "bdm";
		else $this->filterev = $_REQUEST["filterev"];
		
		// Get the filter gender
		if (!isset($_REQUEST["filtersx"])) $this->filtersx = "";
		else $this->filtersx = $_REQUEST["filtersx"];
		
		// Get the filter for persons
		if (!isset($_REQUEST["filterof"]) || $_REQUEST["filterof"] == "") $this->filterof = "all";
		else $this->filterof = $_REQUEST["filterof"];
		
		// First parse the input.
		$this->ParseInput();
	}

	public function __get($property) {
		switch($property) {
			case "day":
				return $this->day;
				break;
			case "m_days":
				return $this->m_days;
				break;
			case "month":
				return $this->month;
				break;
			case "year":
				return $this->year;
				break;
			case "filterev":
				return $this->filterev;
				break;
			case "filtersx":
				return $this->filtersx;
				break;
			case "filterof":
				return $this->filterof;
				break;
			case "lastday":
				return $this->lastday;
				break;
			case "show_no_day":
				return $this->show_no_day;
				break;
			case "CalYear":
				return $this->CalYear;
				break;
			case "currhDay":
				return $this->currhDay;
				break;
			case "currhMonth":
				return $this->currhMonth;
				break;
			case "currhYear":
				return $this->currhYear;
				break;
			case "hDay":
				return $this->hDay;
				break;
			case "hYear":
				return $this->hYear;
				break;
			case "leap":
				return $this->leap;
				break;
			case "usehebrew";
				return $this->GetUseHebrew();
				break;
			case "bartext";
				return $this->GetBarText();
				break;
			case "myindilist";
				return $this->myindilist;
				break;
			case "myfamlist";
				return $this->myfamlist;
				break;
			default:
				return parent::__get($property);
				break;
		}
	}

	public function __set($property, $value) {
		switch($property) {
			case "show_no_day":
				$this->show_no_day = $value;
				break;
			case "day":
				$this->day = $value;
				break;
			default:
				parent::__set($property, $value);
				break;
		}
	}
	
	protected function GetPageTitle() {
		
		if (is_null($this->pagetitle)) {
			$this->pagetitle = GM_LANG_anniversary_calendar;
		}
		return $this->pagetitle;
	}

	function GetUseHebrew() {
		
		if (!GedcomConfig::$USE_RTL_FUNCTIONS) return false;
		if (!isset($this->hebrewfound[GedcomConfig::$GEDCOMID])) {
			$sql = "SELECT i_id FROM ".TBLPREFIX."individuals where i_file='".GedcomConfig::$GEDCOMID."' AND i_gedrec like '%@#DHEBREW@%' LIMIT 1";
			$res = NewQuery($sql);
			if ($res->NumRows()>0) $this->hebrewfound[GedcomConfig::$GEDCOMID] = true;
			else $this->hebrewfound[GedcomConfig::$GEDCOMID] = false;
			$res->FreeResult();
		}
		return $this->hebrewfound[GedcomConfig::$GEDCOMID];
	}
	
	private function ParseInput() {
		global $monthtonum;
		
		$this->endyear = 0;
		$this->startyear = 0;
		// Only for the year option we can specify ranges.
		if ($this->action == "year") {
			
			// Check for a year range, split on any not numeric character
			$years = preg_split("/[^0-9]/",$this->year);
			$this->startyear = $years[0];
			if (isset($years[1])) $this->endyear = $years[1];
			else $this->endyear = $this->startyear;
			$this->startyear = $this->CheckYear($this->startyear, adodb_date("Y"));
			$this->endyear = $this->CheckYear($this->endyear, $this->startyear);
			
			$this->year_text = $this->startyear.($this->endyear != $this->startyear ? " - ".$this->endyear : "");
			// Also reset the input
			$this->year = $this->year_text;
			
		}
		else {
			// For actions month and day are only real years allowed
			$this->year = $this->CheckYear($this->year, adodb_date("Y"));
			$this->startyear = $this->year;
			$this->endyear = $this->year;
	 	//print "year final: startyear: ".$this->startyear." endyear: ".$this->endyear." year: ".$this->year." yearquery: ".$this->year_query." year_text: ".$this->year_text."<br />";
		}		
		// calculate leap year
		if (strlen($this->year) < 5 && preg_match("/[\d]{2,4}/", $this->year)) {
			if (checkdate(2,29,$this->year)) $leap = TRUE;
			else $this->leap = FALSE;
		}
		else $this->leap = FALSE;
		
		// Check for invalid days
		$this->m_days = 31;
		$m_name = strtolower($this->month);
		if ($m_name == "feb") {
			if (!$this->leap) {
				$this->m_days = 28;
				if ($this->day >= '28') {
					$this->day = "28";
				}
			}
			else {
				$this->m_days = 29;
				if ($this->day >= '29') {
					$this->day = "29";
				}
			}
		}
		else if ($m_name == "apr" || $m_name == "jun" || $m_name == "sep" || $m_name == "nov") {
			$this->m_days = 30;
			if ($this->day >= '30') {
				$this->day = "30";
			}
		}
		
		if (GedcomConfig::$USE_RTL_FUNCTIONS) {
			
			$this->datearray = array();
			// (First) entered date to Hebrew
		 	$this->datearray[0]["day"]   = $this->day;
		 	$this->datearray[0]["mon"]   = $monthtonum[Str2Lower(trim($this->month))];	
		 	$this->datearray[0]["year"]  = $this->startyear;
		 	$this->datearray[0]["month"] = $this->month;
		 	
		 	// Todays date to Hebrew
		 	$this->datearray[1]["day"]   = adodb_date("j");
		 	$this->datearray[1]["mon"]   = $monthtonum[Str2Lower(trim(adodb_date("M")))];	
		 	$this->datearray[1]["year"]  = adodb_date("Y");
			
		    $this->date   		= GregorianToJewishGedcomDate($this->datearray);
		    $this->hDay   		= $this->date[0]["day"];
		    $this->hMonth 		= $this->date[0]["month"];
		    $this->hYear		= $this->date[0]["year"];
		    $this->CalYear		= $this->hYear;
		    
		    $this->currhDay  	= $this->date[1]["day"];
		    $this->currhMonth   = trim($this->date[1]["month"]);
		    $this->currhYear 	= $this->date[1]["year"];
		    
		  	$this->datearray = array();
			// (First) entered date to Hebrew
		 	$this->datearray[0]["day"]   = $this->day;
		 	$this->datearray[0]["mon"]   = $monthtonum[Str2Lower(trim($this->month))];	
		 	$this->datearray[0]["year"]  = $this->startyear; //
		 	$this->datearray[0]["month"] = $this->month;
		 	// for month
		 	$this->datearray[1]["day"]   = 01;
		 	$this->datearray[1]["mon"]   = $monthtonum[Str2Lower(trim($this->month))];	
		 	$this->datearray[1]["year"]  = $this->startyear;
		 	$this->datearray[2]["day"]   = $this->m_days;
		 	$this->datearray[2]["mon"]   = $monthtonum[Str2Lower(trim($this->month))];	
		 	$this->datearray[2]["year"]  = $this->endyear;
		 	
		 	// for year
			if ($this->action == "year") {
		 		$this->datearray[3]["day"]   = 01;
		 		$this->datearray[3]["mon"]   = 01;	
		 		$this->datearray[3]["year"]  = $this->startyear;
		 		$this->datearray[4]["day"]   = 31;
		 		$this->datearray[4]["mon"]   = 12;	
		 		$this->datearray[4]["year"]  = $this->endyear;
			}
		    $this->date   	= GregorianToJewishGedcomDate($this->datearray);
		    $this->hDay   	= $this->date[0]["day"];
		    $this->hMonth 	= $this->date[0]["month"];
		    $this->CalYear	= $this->date[0]["year"];
		}
	}
	private function GetBarText() {
		
		if (is_null($this->bartext)) {
			$this->bartext = "";
			if ($this->action == "today") {
				//-- the year is needed for alternate calendars
			 	if (GedcomConfig::$CALENDAR_FORMAT != "gregorian") $this->bartext .= GetChangedDate($this->day." ". $this->month." ". $this->startyear);
				else $this->bartext .= GetChangedDate($this->day." ".$this->month);
				if (GedcomConfig::$CALENDAR_FORMAT == "gregorian" && $this->GetUseHebrew()) $this->bartext .= " / ".GetChangedDate("@#DHEBREW@ ".$this->hDay." ".$this->hMonth." ".$this->CalYear); 
			}
			else if ($this->action == "calendar") {
				$this->bartext .= GetChangedDate(" ".$this->month." ".$this->year." ");
				if (GedcomConfig::$CALENDAR_FORMAT=="gregorian" && $this->GetUseHebrew()) {
					$hdd = $this->date[1]["day"];
					$hmm = $this->date[1]["month"];
					$hyy = $this->date[1]["year"];
					$this->bartext .= " /  ".GetChangedDate("@#DHEBREW@ ".$hdd." ".$hmm." ".$hyy);
			        if ($hmm!=$this->date[2]["month"]) {
				            $hdd = $this->date[2]["day"];
			        		$hmm = $this->date[2]["month"];
							$hyy = $this->date[2]["year"];
							$this->bartext .= " -".GetChangedDate("@#DHEBREW@ ".$hdd." ".$hmm." ".$hyy);
					}
			    }
			}
			else if ($this->action == "year") {
				$this->bartext .= GetChangedDate(" ".$this->year_text." ");
				if (GedcomConfig::$CALENDAR_FORMAT == "gregorian" && $this->GetUseHebrew()) {
					$hdd = $this->date[3]["day"];
					$hmm = $this->date[3]["month"];
					$this->hstartyear = $this->date[3]["year"];
					$this->bartext .= " /  ".GetChangedDate("@#DHEBREW@ ".$hdd." ".$hmm." ".$this->hstartyear);
				    $hdd = $this->date[4]["day"];
			        $hmm = $this->date[4]["month"];
					$this->hendyear = $this->date[4]["year"];
					$this->bartext .= " -".GetChangedDate("@#DHEBREW@ ".$hdd." ".$hmm." ".$this->hendyear);
				}
			}
		}
		return $this->bartext;
	}
	
	private function CheckYear($year, $default) {
		
		$year = trim($year, " ");
		if ($year != 0) $year = ltrim($year, "0");
		if (!is_numeric($year)) $year = $default;
		return $year;
	}
	
	public function GetDateFacts($facts) {
		
		$text_fact = "";
		foreach($facts as $index => $factobj) {
			//print "Checking ".$factobj->fact."<br />";
			$text_temp = "";
			$t1 = preg_match("/2 DATE.*DHEBREW.* (\d\d\d\d|\d\d\d)/i", $factobj->factrec, $m1);
			if (GedcomConfig::$USE_RTL_FUNCTIONS && $this->action == "year" && !is_null($this->hendyear) && $this->hendyear > 0 && $t1 > 0) {
				if ($m1[1] == $this->hstartyear || $m1[1] == $this->hendyear) {
					//print "check hebrew".$factobj->factrec."<br />";
					// verify if the date falls within the first or the last range gregorian year @@@@ !!!!
					$fdate = $factobj->datestring;
					if ($fdate != "") {
						$hdate = ParseDate(trim($fdate));
						$gdate = JewishGedcomDateToGregorian($hdate);
                	    $gyear = $gdate[0]["year"]; 

						if ($gyear >= $this->startyear && $gyear <= $this->endyear) $hprocess=true;
						else $hprocess = false;
					}
					else $hprocess = false;
				}
				else if ($m1[1] > $this->hstartyear && $m1[1] < $this->hendyear) $hprocess = true;
				     else $hprocess = false;
				     //if ($hprocess) print "heb_proc";
				if ($hprocess) $text_temp .= FactFunctions::GetCalendarFact($factobj, $this->action, $this->filterof, $this->filterev, $this->year, $this->month, $this->day, $this->CalYear, $this->currhYear);
				else $text_temp .= "filter";
			}
			elseif (GedcomConfig::$USE_RTL_FUNCTIONS && $this->action == "today" && $t1) {
				//print "check hebrew".$factobj->factrec."<br />";
				// verify if the date falls within the first or the last range gregorian year @@@@ !!!!
				$fdate = $factobj->datestring;
				if ($fdate != "") {
					$hdate = ParseDate(trim($fdate));
					if ($hdate[0]["day"] == $this->hDay && Str2Lower($hdate[0]["month"]) == Str2Lower($this->hMonth)) $hprocess=true;
					else $hprocess=false;
				}
				else $hprocess=false;
			     //if ($hprocess) print "heb_proc";
				if ($hprocess) $text_temp .= FactFunctions::GetCalendarFact($factobj, $this->action, $this->filterof, $this->filterev, $this->hYear, $this->hMonth, $this->hDay, $this->CalYear, $this->currhYear);
				else $text_temp .= "filter";
			}
			elseif ($factobj->datestring != "") {
				$dates = ParseDate($factobj->datestring);
				$print = false;
				if ($this->action == "year" && $dates[0]["year"] >= $this->startyear && $dates[0]["year"] <= $this->endyear) $print = true;
				if ($this->action == "today" && $dates[0]["day"] == $this->day && Str2Lower($dates[0]["month"]) == Str2Lower($this->month)) $print = true;
//					print ($print ? "true":"false");
//					print "<br />";
				if ($print) $text_temp .= FactFunctions::GetCalendarFact($factobj, $this->action, $this->filterof, $this->filterev, $this->year, $this->month, $this->day, $this->CalYear, $this->currhYear);
				else $text_temp .= "filter";
			}
							
				if ($text_temp != "filter" && $text_temp != "filterfilter") $text_fact .= $text_temp;
		} // end fact loop
		return $text_fact;
	}
	
	public function GetDayYearLists() {
		
		// Determine what facts to search and where to search
		$this->myindilist = array();
		$this->myfamlist = array();
		$famfacts = array("_COML", "MARR", "DIV", "EVEN");
		$findindis = true;
		$findfams = true;
		if ($this->filterev == "bdm") {
			$selindifacts = "('BIRT', 'DEAT')";
			$selfamfacts = "('MARR')";
		}
		else if ($this->filterev != "all" && $this->filterev != "") {
			if (in_array($this->filterev, $famfacts)) {
				$findindis = false;
				$selfamfacts = "('".$this->filterev."')";
			}
			else {
				$selindifacts = "('".$this->filterev."')";
				$findfams = false;
			}
		}
		else {
			$selindifacts = "";
			$selfamfacts = "";
		}
		
		if ($this->action == "year"){
			if ($findindis) $this->myindilist = SearchFunctions::SearchIndisYearRange($this->startyear,$this->endyear, false, $selindifacts);
			if ($findfams) $this->myfamlist = SearchFunctions::SearchFamsYearRange($this->startyear,$this->endyear, false, $selfamfacts);
			
			if (GedcomConfig::$USE_RTL_FUNCTIONS && !is_null($this->hstartyear) && !is_null($this->hendyear)) {
				if ($findindis) {
					$myindilist1 = SearchFunctions::SearchIndisYearRange($this->hstartyear, $this->hendyear, false, $selindifacts);
					$this->myindilist = GmArrayMerge($this->myindilist, $myindilist1);
				}
				
				if ($findfams) {
					$myfamlist1 = SearchFunctions::SearchFamsYearRange($this->hstartyear, $this->hendyear, false, $selfamfacts);
					$this->myfamlist = GmArrayMerge($this->myfamlist, $myfamlist1);
				}
			}
		}
		if ($this->action == "today") {
			if ($findindis) $this->myindilist = SearchFunctions::SearchIndisDates($this->day, $this->month, "", $selindifacts, ($this->filterof == "recent" ? ($this->startyear-100) : ""));
			if ($findfams) $this->myfamlist = SearchFunctions::SearchFamsDates($this->day, $this->month, "", $selfamfacts, ($this->filterof == "recent" ? ($this->startyear-100) : ""));
			
			if (GedcomConfig::$USE_RTL_FUNCTIONS) {
				if ($findindis) {
					$myindilist1 = SearchFunctions::SearchIndisDates($this->hDay, $this->hMonth, "", $selindifacts);
					$this->myindilist = GmArrayMerge($this->myindilist, $myindilist1);
				}
				
				if ($findfams) {
					$myfamlist1 = SearchFunctions::SearchFamsDates($this->hDay, $this->hMonth, "", $selfamfacts);
					$this->myfamlist = GmArrayMerge($this->myfamlist, $myfamlist1);
				}
			}
		}	
		
		// If filter on gender is chosen, we display the fam facts with the person of that gender.
		// Of course, only if any fam facts were selected
		// So we copy them from fam to indi. This may causes doubles (indi AND fam fact in the same date range) but we will filter them out later
		if ($this->filtersx != "" && $findfams) {
			foreach($this->myfamlist as $key => $fam) {
				if ($fam->husb_id != "" && $fam->husb->sex == $this->filtersx) {
					$this->myindilist[] =& $fam->husb;
					//print "Added: ".$fam->husb->name."<br />";
				}
				if ($fam->wife_id != "" && $fam->wife->sex == $this->filtersx) {
					$this->myindilist[] =& $fam->wife;
					//print "Added: ".$fam->wife->name."<br />";
				}
			}
		}
	/*	
	 print "indicount: ".count($this->myindilist)."<br />";
	 print "famcount: ".count($this->myfamlist)."<br />";
	 print "filterof: ".$this->filterof."<br />";
	 print "filterev: ".$this->filterev."<br />";
	 print "filtersx: ".$this->filtersx."<br />";
	*/
		uasort($this->myindilist, "ItemSort");
		if ($this->filtersx == "") uasort($this->myfamlist, "ItemSort");
	}
	
	public function GetCalendarLists() {
		global $monthtonum, $WEEK_START, $monthstart;
	
		$monthstart 	= adodb_mktime(1,0,0,$monthtonum[strtolower($this->month)],1,$this->year);
		$startday 		= adodb_date("w", $monthstart);
		$endday 		= adodb_dow($this->year,$monthtonum[strtolower($this->month)],adodb_date("t", $monthstart));
		$this->lastday		= adodb_date("t", $monthstart);
		$mmon 			= strtolower(adodb_date("M", $monthstart));
		$monthstart 	= $monthstart-(60*60*24*$startday);
		if ($WEEK_START <= $startday)
			$monthstart += $WEEK_START*(60*60*24);
		else //week start > $startday
			$monthstart -= (7-$WEEK_START)*(60*60*24);
		if (($endday == 6 && $WEEK_START == 0) || ($endday == 0 && $WEEK_START == 1)) $this->show_no_day = 0;
		else $this->show_no_day = 6;
		if ((($startday == 0 && $WEEK_START == 0) || ($startday == 2 && $WEEK_START == 1)) && $this->show_no_day == 0) $this->show_no_day = 6;
		$this->lastday -= 29;   
		if ($this->lastday < 0) $this->lastday = 0;
		$this->myindilist = array();
		$this->myfamlist = array();
	
		$fact = "";	
	
		$famfacts = array("_COML", "MARR", "DIV", "EVEN");
		$findindis = true;
		$findfams = true;
		if ($this->filterev == "bdm") $fact = "('BIRT', 'DEAT', 'MARR')";
		else if ($this->filterev != "all" && !empty($this->filterev)) {
			$fact = "('".$this->filterev."')";
			if (in_array($this->filterev, $famfacts)) $findindis = false;
			else $findfams = false;
		}
		else $fact = "";
		
		if ($findindis) $this->myindilist = SearchFunctions::SearchIndisDates("", $mmon, "", $fact, ($this->filterof == "recent" ? ($this->startyear-100) : ""));
		if ($findfams) $this->myfamlist = SearchFunctions::SearchFamsDates("", $mmon, "", $fact, ($this->filterof == "recent" ? ($this->startyear-100) : ""));
	
		if (GedcomConfig::$USE_RTL_FUNCTIONS) {
			$datearray[0]["day"]   = 01;
	 		$datearray[0]["mon"]   = $monthtonum[Str2Lower($this->month)];	
	 		$datearray[0]["year"]  = $this->year;
	 		$datearray[0]["month"] = $this->month;
	 		$datearray[1]["day"]   = 15;
	 		$datearray[1]["mon"]   = $monthtonum[Str2Lower($this->month)];	
	 		$datearray[1]["year"]  = $this->year;
	 		$datearray[1]["month"] = $this->month;
	 		$datearray[2]["day"]   = adodb_date("t", $monthstart);
	 		$datearray[2]["mon"]   = $monthtonum[Str2Lower($this->month)];	
	 		$datearray[2]["year"]  = $this->year;
	 		$datearray[2]["month"] = $this->month;
	
			$date   = GregorianToJewishGedcomDate($datearray);
			$HBMonth1 = $date[0]["month"];
			$HBYear1  = $date[0]["year"];
			$HBMonth2 = $date[1]["month"];
			$HBMonth3 = $date[2]["month"];
			
			if ($findindis) $myindilist1 = SearchFunctions::SearchIndisDates("", $HBMonth1, "", $fact);
			if ($findfams) $myfamlist1 = SearchFunctions::SearchFamsDates("", $HBMonth1, "", $fact);
				
			if ($findindis) $this->myindilist = GmArrayMerge($this->myindilist, $myindilist1);
			if ($findfams) $this->myfamlist  = GmArrayMerge($this->myfamlist, $myfamlist1);
			
			if ($HBMonth1 != $HBMonth2) {		
				$preghbquery2 = "2 DATE[^\n]*$HBMonth2";
					
				if ($findindis) $myindilist1 = SearchFunctions::SearchIndisDates("", $HBMonth2, "", $fact);
				if ($findfams) $myfamlist1 = SearchFunctions::SearchFamsDates("", $HBMonth2, "", $fact);
				
				if ($findindis) $this->myindilist = GmArrayMerge($this->myindilist, $myindilist1);
				if ($findfams) $this->myfamlist  = GmArrayMerge($this->myfamlist, $myfamlist1);
			}
			
			if ($HBMonth2 != $HBMonth3) {		
				$preghbquery3 = "2 DATE[^\n]*$HBMonth3";
					
				if ($findindis) $myindilist1 = SearchFunctions::SearchIndisDates("", $HBMonth3, "", $fact);
				if ($findfams) $myfamlist1 = SearchFunctions::SearchFamsDates("", $HBMonth3, "", $fact);
				
				if ($findindis) $this->myindilist = GmArrayMerge($this->myindilist, $myindilist1);
				if ($findfams) $this->myfamlist  = GmArrayMerge($this->myfamlist, $myfamlist1);
			}
			
			if (!IsJewishLeapYear($HBYear1) && ($HBMonth1 == "adr" || $HBMonth2 == "adr" || $HBMonth3 == "adr")) {
				$HBMonth4 = "ads"; 
			
				if ($findindis) $myindilist1 = SearchFunctions::SearchIndisDates("", $HBMonth4, "", $fact);
				if ($findfams) $myfamlist1 = SearchFunctions::SearchFamsDates("", $HBMonth4, "", $fact);
				
				if ($findindis) $this->myindilist = GmArrayMerge($this->myindilist, $myindilist1);
				if ($findfams) $this->myfamlist  = GmArrayMerge($this->myfamlist, $myfamlist1);
			}
		}
		/*
		 print "indicount: ".count($this->myindilist)."<br />";
		 print "famcount: ".count($this->myfamlist)."<br />";
		 print "filterof: ".$this->filterof."<br />";
		 print "filterev: ".$this->filterev."<br />";
		 print "filtersx: ".$this->filtersx."<br />";
		*/
		uasort($this->myindilist, "ItemSort");
	}
}
?>