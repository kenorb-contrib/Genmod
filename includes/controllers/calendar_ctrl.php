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
 * @version $Id$
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
	public $datearray = null;						// Array to keep the dates to be converted to Hebrew etc.
	public $date = null;							// Array to keep the dates converted to Hebrew etc.
	private $hDay = null;							// (First) day of date entered in Hebrew
	private $hMonth = null;							// (First) month of date entered in Hebrew
	private $hYear = null;							// (First) year of date entered in Hebrew
	private $year_text = null;						// Year (range) to print in the title
	private $startyear = null;						// First year of year range
	private $endyear = null;						// Last year of year range
	private $m_days = null;							// Number of days in the selected month
	private $year_query = null;						// Query to match fact dates after retrieval of indi's/fams
	private $query = null;							// Query to match fact dates after retrieval of indi's/fams
	private $pregquery = null;						// Query to match fact dates after retrieval of indi's/fams
	private $CalYear = null;
	private $currhDay = null;
	private $currhMonth = null;
	private $currhYear = null;
	private $leap = null;							// True or false a leap year
	
	public function __construct() {
		
		parent::__construct();
		
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
			case "year_query":
				return $this->year_query;
				break;
			case "year_text":
				return $this->year_text;
				break;
			case "startyear":
				return $this->startyear;
				break;
			case "endyear":
				return $this->endyear;
				break;
			case "pregquery":
				return $this->pregquery;
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
			case "hMonth":
				return $this->hMonth;
				break;
			case "hYear":
				return $this->hYear;
				break;
			case "leap":
				return $this->leap;
				break;
			default:
				return parent::__get($property);
				break;
		}
	}

	public function __set($property, $value) {
		switch($property) {
			case "year_query":
				$this->year_query = $value;
				break;
			case "year_text":
				$this->year_text = $value;
				break;
			case "pregquery":
				$this->pregquery = $value;
				break;
			case "CalYear":
				$this->CalYear = $value;
				break;
			case "currhYear":
				$this->currhYear = $value;
				break;
			case "currhMonth":
				$this->currhMonth = $value;
				break;
			case "currhDay":
				$this->currhDay = $value;
				break;
			case "hYear":
				$this->hYear = $value;
				break;
			case "hMonth":
				$this->hMonth = $value;
				break;
			case "hDay":
				$this->hDay = $value;
				break;
			case "day":
				$this->day = $value;
				break;
			case "month":
				$this->month = $value;
				break;
			case "year":
				$this->year = $value;
				break;
			default:
				parent::__set($property, $value);
				break;
		}
	}
	
	protected function GetPageTitle() {
		
		if (is_null($this->pagetitle)) {
			$this->pagetitle = "";
			if ($this->pid1 != "") $this->pagetitle .= $this->person1->name.$this->person1->addxref;
			if ($this->pid2 != "") $this->pagetitle .= "/".$this->person2->name.$this->person2->addxref;;
			$this->pagetitle .= " - ".GM_LANG_relationship_chart;
		}
		return $this->pagetitle;
	}
	
	private function ParseInput() {
		global $monthtonum;
		
		$this->endyear = 0;
		$this->startyear = 0;
		// Only for the year option we can specify ranges.
		if ($this->action == "year") {
			
			// Check for abbreviations
			$abbr = array(GM_LANG_abt, GM_LANG_aft, GM_LANG_bef, GM_LANG_bet, GM_LANG_cal, GM_LANG_est, GM_LANG_from, GM_LANG_int, GM_LANG_cir, GM_LANG_apx, GM_LANG_and, GM_LANG_to);
			
			// strip heading and trailing spaces and heading zero's
			$this->year = trim($this->year);
			for ($i = 0; $i <= strlen($this->year); $i++) {
				if (substr($this->year,0,1) == "0" && substr($this->year,1,1) != "-") $this->year = substr($this->year,1);
			}
			
			// Search for spaces and get the string up to the space
			$pos1 = strpos($this->year," ");
			if ($pos1 == 0) $pos1 = strlen($this->year);
			if (function_exists("Str2Lower")) $in_year = Str2Lower(substr($this->year, 0, $pos1));
			else $in_year = substr($this->year, 0, $pos1);
			
			// If the characters before the space are in the translated prefix array, replace them with the gedcom expressions (ongeveer => abt)
			if (in_array($in_year, $abbr)){
				if (function_exists("Str2Lower")) $this->year = preg_replace(array("/$abbr[0]/","/$abbr[1]/","/$abbr[2]/","/$abbr[3]/","/$abbr[4]/","/$abbr[5]/","/$abbr[6]/","/$abbr[7]/","/$abbr[8]/","/$abbr[9]/","/ $abbr[10] /","/ $abbr[11] /"), array("abt","aft","bef","bet","cal","est","from","int","cir","apx"," and "," to "), Str2Lower($this->year));
				else $this->year = preg_replace(array("/$abbr[0]/","/$abbr[1]/","/$abbr[2]/","/$abbr[3]/","/$abbr[4]/","/$abbr[5]/","/$abbr[6]/","/$abbr[7]/","/$abbr[8]/","/$abbr[9]/"), array("abt","aft","bef","bet","cal","est","from","int","cir","apx"), $this->year);
			}
			
			
			// replace a question mark with [0-9]
			if (strlen($this->year) > 1 && preg_match("/\?/", $this->year)) $this->year = preg_replace("/\?/", "[0-9]", $this->year);
			
			// Replace all other invalid characters
			$this->year = preg_replace(array("/&lt;/", "/&gt;/", "/[?*+|&.,:'%_<>!#?{}=^]/", "/\\$/", "/\\\/",  "/\"/"), "", $this->year);
			
			// If what remains cannot be a year, set it to the current year
			if (preg_match("/[\D]{1,2}/", $this->year) && strlen($this->year) <= 2) $this->year="";
			if (empty($this->year)) $this->year = adodb_date("Y");
			$this->year = trim($this->year);
			$this->year_text = $this->year;
			$this->year_query = $this->year;
		//	print $this->year;
		
			if ((strpos($this->year, "-") > 0) && !preg_match("/[\[\]]/", $this->year)){
				if (substr($this->year,0,1) > 9){
					while (substr($this->year,0,1) > 9) $this->year = trim(substr($this->year, 1));
				}
				$pos1 = strpos($this->year, "-");
				if (strlen($this->year) == $pos1 + 2){					// endyear n
					$this->year_query = substr($this->year, 0, ($pos1-1))."[".substr($this->year, ($pos1-1), 3)."]";
					$this->year_text  = substr($this->year, 0, ($pos1+1)).substr($this->year, 0, ($pos1-1)).substr($this->year, ($pos1+1), 1);
				}
				else if (strlen($this->year) == $pos1+3){				// endyear nn
					$this->year_text = substr($this->year, 0, ($pos1-2));
					if ((substr($this->year, ($pos1-1), 1)=="0") && (substr($this->year, ($pos1+2), 1)=="9")){
						$this->year_query  = $this->year_text."[".substr($this->year, ($pos1-2), 1)."-".substr($this->year, ($pos1+1), 1)."][0-9]";
					}
					else {
						$this->startyear= substr($this->year, 0, $pos1);
						$this->endyear= substr($this->year, 0, ($pos1-2)).substr($this->year, ($pos1+1), 2);
					}
					$this->year_text = substr($this->year, 0, ($pos1))." - ".($this->startyear=="0"?"":$this->year_text).substr($this->year, ($pos1+1), 2);
				}
				else if ((strlen($this->year) == $pos1+4) && ($pos1==4)){	// endyear nnn
					$this->year_text = substr($this->year, 0, ($pos1-3));
					if ((substr($this->year, ($pos1-2), 2)=="00")&&(substr($this->year, ($pos1+2), 2)=="99")){
						$this->year_query  = $this->year_text."[".substr($this->year, ($pos1-3), 1)."-".substr($this->year, ($pos1+1), 1)."][0-9][0-9]";
					}
					else {
						$this->startyear= substr($this->year, 0, $pos1);
						$this->endyear= substr($this->year, 0, ($pos1-3)).substr($this->year, ($pos1+1), 3);
					}
					$this->year_text = substr($this->year, 0, ($pos1))." - ".$this->year_text.substr($this->year, ($pos1+1), 3);
				}
				else {											// endyear nnn(n)
					$this->startyear = substr($this->year, 0, $pos1);
					$this->endyear   = substr($this->year, ($pos1+1));
					$this->year_text = $this->startyear." - ".$this->endyear;
				}
				if ($this->startyear>$this->endyear){
					$this->year_text = $this->startyear;
					$this->startyear = $this->endyear;
					$this->endyear   = $this->year_text;
					$this->year = $this->startyear."-".$this->endyear;
					$this->year_text = $this->startyear." - ".$this->endyear;
				}
		// print "1. met streepjes start: ".$this->startyear." end: ".$this->endyear." year: ".$this->year."yearquery: ".$this->year_query."<br />";
			}
			if (strpos($this->year, "[", 1)>"0") {
				$pos1 = strpos($this->year, "[", 0);
				$this->year_text=substr($this->year, 0, $pos1);
				while (($pos1 = strpos($this->year, "[", $pos1)) !== false) {
					$this->year_text .= substr($this->year, ($pos1+1), 1);
					$pos1++;
				}
				$pos1 = strpos($this->year, "]", $pos1);
				if (strlen($this->year) > $pos1 && !strpos($this->year, "]", $pos1+1)) $year_add=substr($this->year, $pos1+1, strlen($this->year));
				$pos1=strpos($this->year, "]", $pos1+1);
				if (strlen($this->year) > $pos1 && !strpos($this->year, "]", $pos1+1)) $year_add=substr($this->year, $pos1+1, strlen($this->year));
				if (isset($year_add)) $this->year_text .= $year_add." ~ ";
				else $this->year_text .= " - ";
				if (strpos($this->year, " ", 0)>0) $pos1=(strpos($this->year, " ", 0)+1);
				else $pos1=0;
				$this->year_text .= substr($this->year, $pos1, (strpos($this->year, "[", 0))-$pos1);
				$pos1=(strpos($this->year, "[", 0));
				while (($pos1 = strpos($this->year, "]", $pos1))!==false) {
					$this->year_text .= substr($this->year, ($pos1-1), 1);
					$pos1++;
				}
				if (isset($year_add)) $this->year_text .= $year_add;
				$this->year_query=$this->year;
		// print "2. met haken start: ".$this->startyear." end: ".$this->endyear." year: ".$this->year."yearquery: ".$this->year_query."<br />";
			}
			else if (strlen($this->year) < 4 && preg_match("/[\d]{1,3}/", $this->year)) {
				if (substr($this->year, 0, 2) <= substr(adodb_date("Y"), 0, 2)){
					for ($i=strlen($this->year); $i<4; $i++) $this->year_text .="0";
					$this->startyear = $this->year_text;
					$this->year_text .= " - ".$this->year;
					for ($i=strlen($this->year); $i<4; $i++) $this->year_text .="9";
					$this->endyear=$this->year;
					for ($i=strlen($this->year); $i<4; $i++) $this->endyear .="9";
				}
				else {
					for ($i=strlen($this->year); $i<3; $i++) $this->year_text .="0";
					for ($i=strlen($this->year); $i<3; $i++) $this->year .= "0";
				}
				$this->year_query = $this->year;
		// print "3. lengte < 4 start: ".$this->startyear." end: ".$this->endyear." year: ".$this->year."yearquery: ".$this->year_query."<br />";
			}
		}
		if ($this->startyear == 0) {
			$this->startyear = $this->year;
			$this->endyear = $this->year;
		}
	 	//print "year final: startyear: ".$this->startyear." endyear: ".$this->endyear." year: ".$this->year." yearquery: ".$this->year_query." year_text: ".$this->year_text."<br />";
		// For actions month and day are only real years allowed
		if ($this->action != "year") {
			if (strlen($this->year) < 3) $this->year = adodb_date("Y");
			if (strlen($this->year) > 4){
				if (strpos($this->year, "[", 1) > "0"){
					$pos1 = (strpos($this->year, "[", 0));
					$this->year_text = $this->year;
					$yy = $this->year;
					$this->year = substr($yy, 0, ($pos1));
					$this->year .= substr($yy, ($pos1+1), 1);
					if (strlen($this->year_text) == $pos1+10) $this->year .= substr($yy, ($pos1+6), 1);
				}
				else if (strpos($this->year, "-", 1) > "0") $this->year = substr($this->year, 0, (strpos($this->year, "-", 0)));
					else $this->year = adodb_date("Y");
			}
			$this->year = trim($this->year);
		//print "alle andere  year: ".$year."yearquery: ".$year_query."<br />";
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
					$this->pregquery = "2 DATE[^\n]*2[8|9] ".$this->month;
					$this->query = "2 DATE[^\n]*2[8|9] ".$this->month;
				}
			}
			else {
				$this->m_days = 29;
				if ($this->day >= '29') {
					$this->day = "29";
					$this->pregquery = "2 DATE[^\n]*29 ".$this->month;
					$this->query = "2 DATE[^\n]*29 ".$this->month;
				}
			}
		}
		else if ($m_name == "apr" || $m_name == "jun" || $m_name == "sep" || $m_name == "nov") {
			$this->m_days = 30;
			if ($this->day >= '30') {
				$this->day = "30";
				$this->pregquery = "2 DATE[^\n]*30 ".$this->month;
				$this->query = "2 DATE[^\n]*30 ".$this->month;
			}
		}
		
		if (!isset($this->query)) {
			if ($this->day < 10) {
				$this->pregquery = "2 DATE[^\n]*[ |0]".$this->day." ".$this->month;
				$this->query = "2 DATE[^\n]*[ |0]".$this->day." ".$this->month;
			}
			else {
				$this->pregquery = "2 DATE[^\n]*".$this->day." ".$this->month;
				$this->query = "2 DATE[^\n]*".$this->day." ".$this->month;
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
		 	$this->datearray[0]["year"]  = $this->startyear;
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
				$pattern = "[ - |-|and|bet|from|to|abt|bef|aft|cal|cir|est|apx|int]";
				$a = preg_split($pattern, $this->year_text);
				if ($a[0] != "") $gstartyear = $a[0]; 
				if (isset($a[1])) {
					if ($a[0] != "") $gendyear = $a[1];
					else {
						$gstartyear = $a[1];
						if (isset($a[2])) $gendyear = $a[2];
						else $gendyear = $a[1];
					}
				}
				else $gendyear = $a[0];
				
		 		$this->datearray[3]["day"]   = 01;
		 		$this->datearray[3]["mon"]   = 01;	
		 		$this->datearray[3]["year"]  = $gstartyear;
		 		$this->datearray[4]["day"]   = 31;
		 		$this->datearray[4]["mon"]   = 12;	
		 		$this->datearray[4]["year"]  = $gendyear;
		// print "gstart: ".$gstartyear." ".$gendyear; 	
			}
		
		    $this->date   	= GregorianToJewishGedcomDate($this->datearray);
		    $this->hDay   	= $this->date[0]["day"];
		    $this->hMonth 	= $this->date[0]["month"];
		    $this->CalYear	= $this->date[0]["year"];
		}
	}
		
}
?>