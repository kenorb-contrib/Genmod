<?php
/**
 * Core Functions that can be used by any page in Genmod
 *
 * The functions in this file are common to all Genmod pages and include date conversion
 * routines and sorting functions.
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2002 to 2009  Genmod Development Team
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
 * @version $Id: functions_sort.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

// ************************************************* START OF SORTING FUNCTIONS ********************************* //
// Helper function to sort facts.
function CompareFactsType($arec, $brec, $type) {
	static $factsort, $facttype;

	if (is_array($arec)) $arec = $arec[1];
	if (is_array($brec)) $brec = $brec[1];

	// Facts from different families stay grouped together
	if (preg_match('/_GMFS @(\w+)@/', $arec, $match1) && preg_match('/_GMFS @(\w+)@/', $brec, $match2) && $match1[1]!=$match2[1]) return 0;
		
	// Extract fact type from record
	if (!preg_match("/1\s+(\w+)/", $arec, $matcha) || !preg_match("/1\s+(\w+)/", $brec, $matchb)) return 0;
	$afact=$matcha[1];
	$bfact=$matchb[1];

	if (($afact=="EVEN" || $afact=="FACT") && preg_match("/2\s+TYPE\s+(\w+)/", $arec, $match) && defined("GM_FACT_".$match[1])) $afact=$match[1];
	if (($bfact=="EVEN" || $bfact=="FACT") && preg_match("/2\s+TYPE\s+(\w+)/", $brec, $match) && defined("GM_FACT_".$match[1])) $bfact=$match[1];

	if (!is_array($factsort)) {
		$facttype = $type;
		if ($facttype == "SOUR") {
			$factsort = array_flip(array(
				"TITL",
				"ABBR",
				"AUTH",
				"PUBL",
				"TEXT",
				"DATA",
				"REFN",
				"RIN",
				"_????_",
				"NOTE",
				"OBJE",
				"REPO",
				"RESN",
				"CHAN",
			));
		}
		elseif ($facttype == "REPO") {
			$factsort = array_flip(array(
				"NAME",
				"ADDR",
				"PHON",
				"EMAIL",
				"_EMAIL",
				"FAX",
				"WWW",
				"REFN",
				"RIN",
				"_????_",
				"NOTE",
				"OBJE",
				"RESN",
				"CHAN",
			));
		}
		elseif ($facttype == "OBJE") {
			$factsort = array_flip(array(
				"TITL",
				"FILE",
				"FORM",
				"REFN",
				"RIN",
				"_PRIM",
				"_THUM",
				"_????_",
				"NOTE",
				"SOUR",
				"RESN",
				"CHAN",
			));
		}
		else {
			$factsort = array_flip(array(
				"BIRT",
				"_HNM",
				"ALIA", "_AKA", "_AKAN",
				"ADOP", "_ADPF", "_ADPF",
				"_BRTM",
				"CHR", "BAPM",
				"FCOM",
				"CONF",
				"BARM", "BASM",
				"SSN",
				"EDUC",
				"GRAD",
				"_DEG",
				"EMIG", "IMMI",
				"NATU",
				"_MILI", "_MILT",
				"ENGA",
				"MARB", "MARC", "MARL", "_MARI", "_MBON",
				"MARR", "MARR_CIVIL", "MARR_RELIGIOUS", "MARR_PARTNERS", "MARR_UNKNOWN", "_COML",
				"_STAT",
				"_SEPR",
				"DIVF",
				"MARS",
				"_BIRT_CHIL",
				"DIV", "ANUL",
				"_BIRT_", "_MARR_", "_DEAT_",
				"CENS",
				"OCCU",
				"RESI",
				"PROP",
				"CHRA",
				"RETI",
				"FACT", "EVEN",
				"_NMR", "_NMAR", "NMR",
				"NCHI",
				"WILL",
				"_HOL",
				"_????_",
				"DEAT", "CAUS",
				"_FNRL", "BURI", "CREM", "_INTE", "CEME",
				"_YART",
				"_NLIV",
				"PROB",
				"TITL",
				"COMM",
				"NATI",
				"CITN",
				"CAST",
				"RELI",
				"IDNO",
				"TEMP",
				"SLGC", "BAPL", "CONL", "ENDL", "SLGS",
				"AFN", "REFN", "_PRMN", "REF", "RIN",
				"ADDR", "PHON", "EMAIL", "_EMAIL", "EMAL", "FAX", "WWW", "URL", "_URL",
				"RESN",
				"CHAN", "_TODO"
			));
		}
	}

	// Events not in the above list get mapped onto one that is.
	if (!isset($factsort[$afact]))
		if (preg_match('/(_(BIRT|MARR|DEAT)_)/', $afact, $match)) $afact=$match[1];
		else $afact="_????_";
	if (!isset($factsort[$bfact]))
		if (preg_match('/(_(BIRT|MARR|DEAT)_)/', $bfact, $match)) $bfact=$match[1];
		else $bfact="_????_";

	$ret = $factsort[$afact]-$factsort[$bfact];
	//-- if the facts are the same, then go ahead and compare them by date
	//-- this will improve the positioning of non-dated elements on the next pass
	if ($ret==0)
		$ret = CompareFactsDate($arec, $brec);
	return $ret;
}

// Helper function to sort facts.
function CompareFactsDate($arec, $brec) {
	static $parsecache;
	
	if (!isset($parsecache)) $parsecache = array();
	
	if (is_array($arec)) $arec = $arec[1];
	if (is_array($brec)) $brec = $brec[1];

	// If either fact is undated, the facts sort equally.
	if (!preg_match("/2 _?DATE (.*)/", $arec, $amatch) || !preg_match("/2 _?DATE (.*)/", $brec, $bmatch)) {
		if (preg_match('/2 _SORT (\d+)/', $arec, $match1) && preg_match('/2 _SORT (\d+)/', $brec, $match2)) {
			return $match1[1]-$match2[1];
		}
		return 0;
	}

	if (isset($parsecache[$amatch[1]]["date"])) $adate = $parsecache[$amatch[1]]["date"];
	else {
		$adate = ParseDate($amatch[1]);
		$parsecache[$amatch[1]]["date"] = $adate;
	}
	if (isset($parsecache[$bmatch[1]]["date"])) $bdate = $parsecache[$bmatch[1]]["date"];
	else {
		$bdate = ParseDate($bmatch[1]);
		$parsecache[$bmatch[1]]["date"] = $bdate;
	}
	// If either date can't be parsed, don't sort.
	if (!$adate[0]["year"] || !$bdate[0]["year"]) {
		if (preg_match('/2 _SORT (\d+)/', $arec, $match1) && preg_match('/2 _SORT (\d+)/', $brec, $match2)) {
			// print "no sort ".$arec." ".$brec."<br />";
			return $match1[1]-$match2[1];
		}
		return 0;
	}

	// Remember that dates can be ranges and overlapping ranges sort equally.
	if (isset($parsecache[$amatch[1]]["min"])) $amin = $parsecache[$amatch[1]]["min"];
	else {
		$amin = adodb_mktime(0, 0, 0, (empty($adate[0]["mon"]) ? 1 : $adate[0]["mon"]), (empty($adate[0]["day"]) ? 1 : $adate[0]["day"]), $adate[0]["year"]);
		$parsecache[$amatch[1]]["min"] = $amin;
	}
	if (isset($parsecache[$bmatch[1]]["min"])) $bmin = $parsecache[$bmatch[1]]["min"];
	else {
		$bmin = adodb_mktime(0, 0, 0, (empty($bdate[0]["mon"]) ? 1 : $bdate[0]["mon"]), (empty($bdate[0]["day"]) ? 1 : $bdate[0]["day"]), $bdate[0]["year"]);
		$parsecache[$bmatch[1]]["min"] = $bmin;
	}
	if (isset($adate[1]["year"])) {
		if (isset($parsecache[$amatch[1]]["max"])) $amax = $parsecache[$amatch[1]]["max"];
		else {
			$amax = adodb_mktime(0, 0, 0, isset($adate[1]["mon"]) ? $adate[1]["mon"] : 12, isset($adate[1]["day"]) ? $adate[1]["day"] : 31, $adate[1]["year"]);
			$parsecache[$amatch[1]]["max"] = $amax;
		}
	}
	else $amax = $amin;
	if (isset($bdate[1]["year"])) {
		if (isset($parsecache[$bmatch[1]]["max"])) $bmax = $parsecache[$bmatch[1]]["max"];
		else {
			$bmax = adodb_mktime(0, 0, 0, isset($bdate[1]["mon"]) ? $bdate[1]["mon"] : 12, isset($bdate[1]["day"]) ? $bdate[1]["day"] : 31, $bdate[1]["year"]);
			$parsecache[$bmatch[1]]["max"] = $bmax;
		}
	}
	else $bmax = $bmin;
/*
print "arec: ".$arec." brec: ".$brec."<br />";
print "amin: ".$amin." amax: ".$amax." bmin: ".$bmin." bmax: ".$bmax."<br />";
print_r($adate);
print "<br />";
print adodb_date("d m Y", $amin)."<br />";
print_r($bdate);
print "<br />";
print adodb_date("d m Y", $bmin)."<br />";
*/
	// BEF/AFT XXX sort as the day before/after XXX
	if ($adate[0]["ext"]=='BEF') {
		$amin=$amin-1;
		$amax=$amin;
	} else
		if ($adate[0]["ext"]=='AFT') {
			$amax=$amax+1;
			$amin=$amax;
		}
	if ($bdate[0]["ext"]=='BEF') {
		$bmin=$bmin-1;
		$bmax=$bmin;
	} else
		if ($bdate[0]["ext"]=='AFT') {
			$bmax=$bmax+1;
			$bmin=$bmax;
		}
	if ($amax < $bmin) {
		// print "A return -1<br />";
		return -1;
	}
	else
		if ($amin > $bmax) {
			// print "B return 1<br />";
			return 1;
		}
		else {
			//-- ranged date... take the type of fact sorting into account
			$factWeight = 0;
			if (preg_match('/2 _SORT (\d+)/', $arec, $match1) && preg_match('/2 _SORT (\d+)/', $brec, $match2)) {
				$factWeight = $match1[1]-$match2[1];
			}
			//-- fact is prefered to come before, so compare using the minimum ranges
			if ($factWeight < 0 && $amin!=$bmin) {
				// print "C return ".($amin-$bmin)."<br />";
				return ($amin-$bmin);
			} else
				if ($factWeight > 0 && $bmax!=$amax) {
					//-- fact is prefered to come after, so compare using the max of the ranges
					// print "D return ".($bmax-$amax)."<br />";
					return ($bmax-$amax);
				} else {
					//-- facts are the same or the ranges don't give enough info, so use the average of the range
					$aavg = ($amin+$amax)/2;
					$bavg = ($bmin+$bmax)/2;
					if ($aavg<$bavg) {
						// print "E return -1<br />";
						return -1;
					}
					else
						if ($aavg>$bavg) {
							// print "F return 1<br />";
							return 1;
						}
						else {
							// print "G return ".$factWeight."<br />";
							return $factWeight;
						}
				}
			// print "H return 0";
			return 0;
		}
}

// Sort the facts, using three conflicting rules (family sequence,
// date sequence and fact sequence).
// We sort by fact first (preserving family order where possible) and then
// resort by date (preserving fact order where possible).
// This results in the dates always being in sequence, and the facts
// *mostly* being in sequence.
function SortFacts(&$arr, $type="", $desc=false) {
	
	// Pass one - insertion sort on fact type
	$lastDate = "";
	for ($i=0; $i<count($arr); ++$i) {
		if ($i>0) {
			$tmp=$arr[$i];
			$j=$i;
			while ($j>0 && CompareFactsType($arr[$j-1], $tmp, $type)>0) {
				$arr[$j]=$arr[$j-1];
				--$j;
			}
			$arr[$j]=$tmp;
		}
	}

	//-- add extra codes for the next pass of sorting
	//-- add a fake date for the date sorting based on the previous fact that came before
	$lastDate = "";
	for ($i=0; $i<count($arr); $i++) {
		//-- add a fake date for the date sorting based on the previous fact that came before
		if (is_array($arr[$i])) {
			if (preg_match("/2 DATE (.+)/", $arr[$i][1], $match)==0 && !empty($lastDate))
				$arr[$i][1].="\r\n2 _DATE ".$lastDate."\r\n";
			else
				$lastDate = @$match[1];
			//-- also add a sort field so that we can compare based on how they were sorted by the previous pass when the date does not give enough information
			$arr[$i][1] .= "\r\n2 _SORT ".$i."\r\n";
		} else {
			if (preg_match("/2 DATE (.+)/", $arr[$i], $match)==0 && !empty($lastDate))
				$arr[$i].="\r\n2 _DATE ".$lastDate."\r\n";
			else
				$lastDate = @$match[1];
			$arr[$i].="\r\n2 _SORT ".$i."\r\n";
		}
	}
	
	// Pass two - modified bubble/insertion sort on date
	for ($i=0; $i<count($arr)-1; ++$i)
		for ($j=count($arr)-1; $j>$i; --$j)
			if ((!$desc && CompareFactsDate($arr[$i],$arr[$j])>0) || ($desc && CompareFactsDate($arr[$i],$arr[$j])<0)) {
				$tmp=$arr[$i];
				for ($k=$i; $k<$j; ++$k)
					$arr[$k]=$arr[$k+1];
				$arr[$j]=$tmp;
			}
			
	//-- delete the temporary fields
	for ($i=0; $i<count($arr); $i++) {
		if (is_array($arr[$i])) {
			$arr[$i][1] = trim(preg_replace("/2 _(DATE|SORT) (.+)/", "", $arr[$i][1]));
		} 
		else {
			$arr[$i] = trim(preg_replace("/2 _(DATE|SORT) (.+)/", "", $arr[$i]));
		}
	}
}

// Sort the facts, using three conflicting rules (family sequence,
// date sequence and fact sequence).
// We sort by fact first (preserving family order where possible) and then
// resort by date (preserving fact order where possible).
// This results in the dates always being in sequence, and the facts
// *mostly* being in sequence.
function SortFactObjs(&$arr, $type="", $desc=false) {
	
	// Pass one - insertion sort on fact type
	$lastDate = "";
	for ($i=0; $i<count($arr); ++$i) {
		if ($i>0) {
			$tmp=$arr[$i];
			$j=$i;
			while ($j>0 && CompareFactsType($arr[$j-1]->factrec, $tmp->factrec, $type)>0) {
				$arr[$j]=$arr[$j-1];
				--$j;
			}
			$arr[$j]=$tmp;
		}
	}

	//-- add extra codes for the next pass of sorting
	//-- add a fake date for the date sorting based on the previous fact that came before
	$lastDate = "";
	for ($i=0; $i<count($arr); $i++) {
		//-- add a fake date for the date sorting based on the previous fact that came before
		if (is_object($arr[$i])) {
			if (preg_match("/2 DATE (.+)/", $arr[$i]->factrec, $match)==0 && !empty($lastDate))
				$arr[$i]->factrec .= "\r\n2 _DATE ".$lastDate."\r\n";
			else
				$lastDate = @$match[1];
			//-- also add a sort field so that we can compare based on how they were sorted by the previous pass when the date does not give enough information
			$arr[$i]->factrec .= "\r\n2 _SORT ".$i."\r\n";
		} else {
			if (preg_match("/2 DATE (.+)/", $arr[$i], $match)==0 && !empty($lastDate))
				$arr[$i].="\r\n2 _DATE ".$lastDate."\r\n";
			else
				$lastDate = @$match[1];
			$arr[$i].="\r\n2 _SORT ".$i."\r\n";
		}
	}
	
	// Pass two - modified bubble/insertion sort on date
	for ($i=0; $i<count($arr)-1; $i++)
		for ($j=count($arr)-1; $j>$i; $j--)
			if ((!$desc && CompareFactsDate($arr[$i]->factrec,$arr[$j]->factrec)>0) || ($desc && CompareFactsDate($arr[$i]->factrec,$arr[$j]->factrec)<0)) {
				$tmp=$arr[$i];
				for ($k=$i; $k<$j; ++$k)
					$arr[$k]=$arr[$k+1];
				$arr[$j]=$tmp;
			}
			
	//-- delete the temporary fields
	for ($i=0; $i<count($arr); $i++) {
		if (is_object($arr[$i])) {
			$arr[$i]->factrec = trim(preg_replace("/2 _(DATE|SORT) (.+)/", "", $arr[$i]->factrec));
		} 
		else {
			$arr[$i] = trim(preg_replace("/2 _(DATE|SORT) (.+)/", "", $arr[$i]));
		}
	}
}

// ************************************************* START OF SORTING FUNCTIONS ********************************* //
/**
 * Function to sort GEDCOM fact tags based on their tanslations
 */
function FactSort($a, $b) {

	return StringSort(trim(strip_tags(constant("GM_FACT_".$a))), trim(strip_tags(constant("GM_FACT_".$b))));
}

/**
 * String sorting function
 * @param string $a
 * @param string $b
 * @return int negative numbers sort $a first, positive sort $b first
 */
function StringSort($aname, $bname) {
	global $LANGUAGE, $alphabet;

	$alphabet = GetAlphabet();

	if (is_array($aname)) debug_print_backtrace();

	//-- split strings into strings and numbers
	$aparts = preg_split("/(\d+)/", $aname, -1, PREG_SPLIT_DELIM_CAPTURE);
	$bparts = preg_split("/(\d+)/", $bname, -1, PREG_SPLIT_DELIM_CAPTURE);

	//-- loop through the arrays of strings and numbers
	for($j=0; ($j<count($aparts) && $j<count($bparts)); $j++) {
		$aname = $aparts[$j];
		$bname = $bparts[$j];

		//-- sort numbers differently
		if (is_numeric($aname) && is_numeric($bname)) {
			if ($aname!=$bname) return $aname-$bname;
		}
		else {
			
	// Handle chinese order, force this check even if pinyin is disabled
	if(NameFunctions::HasChinese($aname, true) && NameFunctions::HasChinese($bname, true)) {
		$aname = NameFunctions::GetPinYin($aname);
		$bname = NameFunctions::GetPinYin($bname);
	}
	
	//-- get the name lengths
	$alen = strlen($aname);
	$blen = strlen($bname);

	
	//-- loop through the characters in the string and if we find one that is different between the strings
	//-- return the difference
	$hungarianex = array("CS","DZ","GY","LY","NY","SZ","TY","ZS","DZS");
	$danishex = array("OE", "AE", "AA");
	for($i=0; ($i<$alen)&&($i<$blen); $i++) {
		if ($LANGUAGE == "hungarian" && $i==0){
			$aletter = substr($aname, $i, 3);
			if (strtoupper($aletter) == "DZS");
			else $aletter = substr($aname, $i, 2);
			if (in_array(strtoupper($aletter), $hungarianex));
			else $aletter = $aname{$i};

			$bletter = substr($bname, $i, 3);
			if (strtoupper($bletter) == "DZS");
			else $bletter = substr($bname, $i, 2);
			if (in_array(strtoupper($bletter), $hungarianex));
			else $bletter = $bname{$i};
		}
		else if (($LANGUAGE == "danish" || $LANGUAGE == "norwegian")){
			$aletter = substr($aname, $i, 2);
			if (in_array(strtoupper($aletter), $danishex)) {
				if (strtoupper($aletter) == "AA") {
					if ($aletter == "aa") $aname=substr_replace($aname, "å", $i, 2);
					else $aname=substr_replace($aname, "Å", $i, 2);
				}
				else if (strtoupper($aletter) == "OE") {
					if ($i==0 || $aletter=="Oe") $aname=substr_replace($aname, "Ø", $i, 2);
				}
				else if (strtoupper($aletter) == "AE") {
					if ($aletter == "ae") $aname=substr_replace($aname, "æ", $i, 2);
					else $aname=substr_replace($aname, "Æ", $i, 2);
				}
			}
			$aletter = substr($aname, $i, 1);

			$bletter = substr($bname, $i, 2);
			if (in_array(strtoupper($bletter), $danishex)) {
				if (strtoupper($bletter) == "AA") {
					if ($bletter == "aa") $bname=substr_replace($bname, "å", $i, 2);
					else $bname=substr_replace($bname, "Å", $i, 2);
				}
				else if (strtoupper($bletter) == "OE") {
					if ($i==0 || $bletter=="Oe") $bname=substr_replace($bname, "Ø", $i, 2);
				}
				else if (strtoupper($bletter) == "AE") {
					if ($bletter == "ae") $bname=substr_replace($bname, "æ", $i, 2);
					else $bname=substr_replace($bname, "Æ", $i, 2);
				}
			}
			$bletter = substr($bname, $i, 1);
		}
		else {
			$aletter = substr($aname, $i, 1);
			$bletter = substr($bname, $i, 1);
		}
		if (GedcomConfig::$CHARACTER_SET=="UTF-8") {
			$ord = ord($aletter);
			if ($ord==92 || $ord==195 || $ord==196 || $ord==197 || $ord==206 || $ord==207 || $ord==208 || $ord==209 || $ord==214 || $ord==215 || $ord==216 || $ord==217 || $ord==218 || $ord==219){
				$aletter = stripslashes(substr($aname, $i, 2));
			}
			else if ($ord==228 || $ord==229 || $ord == 230 || $ord==232 || $ord==233){
				$aletter = substr($aname, $i, 3);
			}
			else if (strlen($aletter) == 1) $aletter = strtoupper($aletter);

			$ord = ord($bletter);
			if ($ord==92 || $ord==195 || $ord==196 || $ord==197 || $ord==206 || $ord==207 || $ord==208 || $ord==209 || $ord==214 || $ord==215 || $ord==216 || $ord==217 || $ord==218 || $ord==219){
				$bletter = stripslashes(substr($bname, $i, 2));
			}
			else if ($ord==228 || $ord==229 || $ord == 230 || $ord==232 || $ord==233){
				$bletter = substr($bname, $i, 3);
			}
			else if (strlen($bletter) == 1) $bletter = strtoupper($bletter);
		}

		if ($aletter!=$bletter) {
			//-- get the position of the letter in the alphabet string
			$apos = strpos($alphabet, $aletter);
			//print $aletter."=".$apos." ";
			$bpos = strpos($alphabet, $bletter);
			//print $bletter."=".$bpos." ";
			if ($LANGUAGE == "hungarian" && $i==0){ // Check for combination of letters not in the alphabet
				if ($apos==0 || $bpos==0){			// (see array hungarianex)
					$lettera=strtoupper($aletter);
					if (in_array($lettera, $hungarianex)) {
						if ($apos==0) $apos = (strpos($alphabet, substr($lettera,0,1))*3)+(strlen($aletter)>2?2:1);
					}
					else $apos = $apos*3;
					$letterb=strtoupper($bletter);
					if (in_array($letterb, $hungarianex)) {
						if ($bpos==0) $bpos = (strpos($alphabet, substr($letterb,0,1))*3)+(strlen($bletter)>2?2:1);
					}
					else $bpos = $bpos*3;
				}
			}
			if (($bpos!==false)&&($apos===false)) return -1;
			if (($bpos===false)&&($apos!==false)) return 1;
			if (($bpos===false)&&($apos===false)) return ord($aletter)-ord($bletter);
			//print ($apos-$bpos)."<br />";
			if ($apos!=$bpos) return ($apos-$bpos);
		}
	}
	}

	//-- if we made it through the loop then check if one name is longer than the
	//-- other, the shorter one should be first
	if ($alen!=$blen) return ($alen-$blen);
	}
	if (count($aparts)!=count($bparts)) return (count($aparts)-count($bparts));

	//-- the strings are exactly the same so return 0
	return 0;
}

/**
 * sort arrays or strings
 *
 * this function is called by the uasort PHP function to compare two items and tell which should be
 * sorted first.  It uses the language alphabets to create a string that will is used to compare the
 * strings.  For each letter in the strings, the letter's position in the alphabet string is found.
 * Whichever letter comes first in the alphabet string should be sorted first.
 * @param array $a first item
 * @param array $b second item
 * @return int negative numbers sort $a first, positive sort $b first
 */
function FirstnameSort($a, $b) {
	if (isset($a["name"])) $aname = NameFunctions::SortableNameFromName($a["name"]);
	else if (isset($a["names"])) $aname = NameFunctions::SortableNameFromName($a["names"][0][0]);
	else if (is_array($a)) $aname = NameFunctions::SortableNameFromName($a[0]);
	else $aname=$a;
	if (isset($b["name"])) $bname = NameFunctions::SortableNameFromName($b["name"]);
	else if (isset($b["names"])) $bname = NameFunctions::SortableNameFromName($b["names"][0][0]);
	else if (is_array($b)) $bname = NameFunctions::SortableNameFromName($b[0]);
	else $bname=$b;
	$aname = NameFunctions::StripPrefix($aname);
	$bname = NameFunctions::StripPrefix($bname);
	$an = explode(", ", $aname);
	if (isset($an[1])) $aname = $an[1].", ".$an[0];
	$bn = explode(", ", $bname);
	if (isset($bn[1])) $bname = $bn[1].", ".$bn[0];
	return StringSort($aname, $bname);
}


/**
 * sort arrays or strings
 *
 * this function is called by the uasort PHP function to compare two items and tell which should be
 * sorted first.  It uses the language alphabets to create a string that will is used to compare the
 * strings.  For each letter in the strings, the letter's position in the alphabet string is found.
 * Whichever letter comes first in the alphabet string should be sorted first.
 * @param array $a first item
 * @param array $b second item
 * @return int negative numbers sort $a first, positive sort $b first
 */
function ItemSort($a, $b) {

	if (is_array($a)) {
		if (isset($a["name"])) $aname = NameFunctions::SortableNameFromName($a["name"]);
		else if (isset($a["names"])) $aname = NameFunctions::SortableNameFromName($a["names"][0][0]);
		else if (isset($a[0])) $aname = NameFunctions::SortableNameFromName($a[0]);
	}
	else if (is_object($a)) {
		$aname = $a->sortable_name;
	}
	else $aname = $a;
	
	if (is_array($b)) {
		if (isset($b["name"])) $bname = NameFunctions::SortableNameFromName($b["name"]);
		else if (isset($b["names"])) $bname = NameFunctions::SortableNameFromName($b["names"][0][0]);
		else if (isset($b[0])) $bname = NameFunctions::SortableNameFromName($b[0]);
	}
	else if (is_object($b)) {
		$bname = $b->sortable_name;
	}
	else $bname = $b;

	$aname = preg_replace("/([^ ]+)\*/", "$1", NameFunctions::StripPrefix($aname));
	$bname = preg_replace("/([^ ]+)\*/", "$1", NameFunctions::StripPrefix($bname));
	return StringSort($aname, $bname);
}

/**
 * sort a list by the gedcom xref id
 * @param array $a	the first $indi array to sort on
 * @param array $b	the second $indi array to sort on
 * @return int negative numbers sort $a first, positive sort $b first
 */
function IdSort($a, $b) {
	if (is_object($a) && is_object($b)) return StringSort($a->xref, $b->xref);
	if (isset($a["gedcom"])) {
		$ct = preg_match("/0 @(.*)@/", $a["gedcom"], $match);
		if ($ct>0) $aid = $match[1];
	}
	if (isset($b["gedcom"])) {
		$ct = preg_match("/0 @(.*)@/", $b["gedcom"], $match);
		if ($ct>0) $bid = $match[1];
	}
	if (empty($aid) || empty($bid)) return ItemSort($a, $b);
	else return StringSort($aid, $bid);
}

//-- comparison function for usort
function LetterSort($a, $b) {
	return StringSort($a["letter"], $b["letter"]);
}

//-- comparison for sourcelist sort
function SourceSort($a, $b) {
	return StringSort($a["name"], $b["name"]);
}

//-- comparison for sourcelist sort
function SourceDescrSort($a, $b) {
	return StringSort($a->descriptor, $b->descriptor);
}
//-- comparison for sourcelist sort
function SourceAddDescrSort($a, $b) {
	return StringSort($a->adddescriptor, $b->adddescriptor);
}


/**
 * compare two fact records by date
 *
 * Compare facts function is used by the usort PHP function to sort fact baseds on date
 * it parses out the year and if the year is the same, it creates a timestamp based on
 * the current year and the month and day information of the fact
 *
 * @param mixed $a an array with the fact record at index 1 or just a string with the factrecord
 * @param mixed $b an array with the fact record at index 1 or just a string with the factrecord
 * @return int -1 if $a should be sorted first, 0 if they are the same, 1 if $b should be sorted first
 */
function CompareFacts($a, $b) {
	global $ASC, $IGNORE_YEAR, $IGNORE_FACTS, $CIRCULAR_BASE;
	if (!isset($ASC)) $ASC = 0;
	if (!isset($IGNORE_YEAR)) $IGNORE_YEAR = 0;
	if (!isset($IGNORE_FACTS)) $IGNORE_FACTS = 0;
	
	$adate=0;
	$bdate=0;
	
	$bef = -1;
	$aft = 1;
	if ($ASC) {
		$bef = 1;
		$aft = -1;
	}
	
	if (is_array($a)) $arec = $a[1];
	else $arec = $a;
	if (is_array($b)) $brec = $b[1];
	else $brec = $b;
	
	if (!$IGNORE_FACTS) {
		$ft = preg_match("/1\s(\w+)(.*)/", $arec, $match);
		if ($ft>0) $afact = $match[1];
		else $afact="";
		$afact = trim($afact);
	
		$ft = preg_match("/1\s(\w+)(.*)/", $brec, $match);
		if ($ft>0) $bfact = $match[1];
		else $bfact="";
		$bfact = trim($bfact);
		//-- make sure CHAN facts are displayed at the end of the list
		if ($afact=="CHAN" && $bfact!="CHAN") return $aft;
		if ($afact!="CHAN" && $bfact=="CHAN") return $bef;
		
		//-- RESN facts just before CHAN facts
		if ($afact=="RESN" && $bfact!="RESN") return $aft;
		if ($afact!="RESN" && $bfact=="RESN") return $bef;
	
		//-- BIRT at the top of the list
		if ($afact=="BIRT" && $bfact!="BIRT") return $bef;
		if ($afact!="BIRT" && $bfact=="BIRT") return $aft;
	
		//-- DEAT before BURI
		if ($afact=="DEAT" && $bfact=="BURI") return $bef;
		if ($afact=="BURI" && $bfact=="DEAT") return $aft;
	
		//-- DEAT before CREM
		if ($afact=="DEAT" && $bfact=="CREM") return $bef;
		if ($afact=="CREM" && $bfact=="DEAT") return $aft;
	
		//-- group address related data together
		$addr_group = array("ADDR"=>1,"PHON"=>2,"EMAIL"=>3,"FAX"=>4,"WWW"=>5);
		if (isset($addr_group[$afact]) && isset($addr_group[$bfact])) {
			return $addr_group[$afact]-$addr_group[$bfact];
		}
		if (isset($addr_group[$afact]) && !isset($addr_group[$bfact])) {
			return $aft;
		}
		if (!isset($addr_group[$afact]) && isset($addr_group[$bfact])) {
			return $bef;
		}
	}
	
	$cta = preg_match("/2 DATE (.*)/", $arec, $match);
	if ($cta>0) $adate = ParseDate(trim($match[1]));
	$ctb = preg_match("/2 DATE (.*)/", $brec, $match);
	if ($ctb>0) $bdate = ParseDate(trim($match[1]));
	//-- DEAT after any other fact if one date is missing
	// -- Added: if dates are the same then sort by fact
//LERMAN - original does not quite sort right if only one date is missing
// the original "if" does not match the "Added" comment. Plus,
// below has some other cases where both are 0. Do which?
//print "---a=";print_r($adate);print " . . . b=";print_r($bdate);print "<BR>"; 
//	if ($cta==0 && $ctb==0) {
	if ($cta==0 || $ctb==0) {
		if (isset($afact)) {
			if ($afact=="BURI") return $aft;
			if ($afact=="DEAT") return $aft;
			if ($afact=="SLGC") return $aft;
			if ($afact=="SLGS") return $aft;
			if ($afact=="BAPL") return $aft;
			if ($afact=="ENDL") return $aft;
		}
		if (isset($bfact)) {
			if ($bfact=="BURI") return $bef;
			if ($bfact=="DEAT") return $bef;
			if ($bfact=="SLGC") return $bef;
			if ($bfact=="SLGS") return $bef;
			if ($bfact=="BAPL") return $bef;
			if ($bfact=="ENDL") return $bef;
		}
	}
	
	//-- check if both had a date
	if($cta<$ctb) return $aft;
	if($cta>$ctb) return $bef;
	//-- neither had a date so sort by fact name
	if ($cta == 0 && $ctb == 0) {
		if (isset($afact)) {
			if ($afact=="EVEN" || $afact=="FACT") {
				$ft = preg_match("/2 TYPE (.*)/", $arec, $match);
				if ($ft>0) $afact = trim($match[1]);
			}
		}
		else $afact = "";
		if (isset($bfact)) {
			if ($bfact=="EVEN" || $bfact=="FACT") {
				$ft = preg_match("/2 TYPE (.*)/", $brec, $match);
				if ($ft>0) $bfact = trim($match[1]);
			}
		}
		else $bfact = "";
		if (defined("GM_FACT_".$afact)) $afact = constant("GM_FACT_".$afact);
		else if (defined("GM_LANG_".$afact)) $afact = constant("GM_LANG_".$afact);
		if (defined("GM_FACT_".$bfact)) $bfact = constant("GM_FACT_".$bfact);
		else if (defined("GM_LANG_".$bfact)) $bfact = constant("GM_LANG_".$bfact);
		return StringSort($afact, $bfact);
	}
	if ($IGNORE_YEAR) {
	// Calculate Current year Gregorian date for Hebrew date
	   if (GedcomConfig::$USE_RTL_FUNCTIONS && isset($adate[0]["ext"]) && strstr($adate[0]["ext"], "#DHEBREW")!==false) $adate = JewishGedcomDateToCurrentGregorian($adate);
		if (GedcomConfig::$USE_RTL_FUNCTIONS && isset($bdate[0]["ext"]) && strstr($bdate[0]["ext"], "#DHEBREW")!==false) $bdate = JewishGedcomDateToCurrentGregorian($bdate);
	}
	else {
	// Calculate Original year Gregorian date for Hebrew date
	if (GedcomConfig::$USE_RTL_FUNCTIONS && isset($adate[0]["ext"]) && strstr($adate[0]["ext"], "#DHEBREW")!==false) $adate = JewishGedcomDateToGregorian($adate);
	if (GedcomConfig::$USE_RTL_FUNCTIONS && isset($bdate[0]["ext"]) && strstr($bdate[0]["ext"], "#DHEBREW")!==false) $bdate = JewishGedcomDateToGregorian($bdate);
	}

	if ($adate[0]["year"]==$bdate[0]["year"] || $IGNORE_YEAR) {
		// Check month
		$montha = $adate[0]["mon"];
		$monthb = $bdate[0]["mon"];

		if ($montha == $monthb) {
		// Check day
			$newa = $adate[0]["day"]." ".$adate[0]["month"]." ".date("Y");
			$newb = $bdate[0]["day"]." ".$bdate[0]["month"]." ".date("Y");
			$astamp = strtotime($newa);
			$bstamp = strtotime($newb);
			if ($astamp==$bstamp) {
				if ($IGNORE_YEAR && ($adate[0]["year"]!=$bdate[0]["year"])) return ($adate[0]["year"] < $bdate[0]["year"]) ? $aft : $bef;
				$cta = preg_match("/[2-3] TIME (.*)/", $arec, $amatch);
				$ctb = preg_match("/[2-3] TIME (.*)/", $brec, $bmatch);
				//-- check if both had a time
				if($cta<$ctb) return $aft;
				if($cta>$ctb) return $bef;
				//-- neither had a time
				if(($cta==0)&&($ctb==0)) {
					// BIRT before DEAT on same date
					if (isset($afact)) {
						if (strstr($afact, "BIRT_")) return $bef;
					}
					else if (preg_match("/1\sBIRT/", $arec)) return $bef;
					if (isset($bfact)) {
						if (strstr($bfact, "BIRT_")) return $aft;
					}
					else if (preg_match("/1\sBIRT/", $brec)) return $aft;
					return 0;
				}
				$atime = trim($amatch[1]);
				$btime = trim($bmatch[1]);
				$astamp = strtotime($newa." ".$atime);
				$bstamp = strtotime($newb." ".$btime);
				if ($astamp==$bstamp) return 0;
			}
			return ($astamp < $bstamp) ? $bef : $aft;
		}
		else {
			if (isset($CIRCULAR_BASE)) {
				if ($montha < $CIRCULAR_BASE) $montha += 12;
				if ($monthb < $CIRCULAR_BASE) $monthb += 12;
			}
			return ($montha < $monthb) ? $bef : $aft;
		}
	}
	return ($adate[0]["year"] < $bdate[0]["year"]) ? $bef : $aft;
}

/**
 * fact date sort
 *
 * compare individuals by a fact date
 */
function CompareDate($a, $b) {
	global $sortby, $selevent;

	$tag = "BIRT";
	if (!empty($sortby)) {
		if ($sortby == "DATE") $tag = $selevent;
		else $tag = $sortby;
	}
	if (is_object($a)) {
		if ($a->ischanged) $arec = $a->changedgedrec;
		else $arec = $a->gedrec;
		if ($b->ischanged) $brec = $b->changedgedrec;
		else $brec = $b->gedrec;
	}
	else if (is_array($a)) {
		$arec = $a["gedcom"];
		$brec = $b["gedcom"];
	}
	else {
		$arec = $a;
		$brec = $b;
	}
	$abirt = GetSubRecord(1, "1 $tag", $arec);
	if (empty($abirt) && $tag == "BIRT") {
		$abirt = GetSubRecord(1, "1 CHR", $arec);
		if (empty($abirt)) $abirt = GetSubRecord(1, "1 BAPM", $arec);
	}
	$bbirt = GetSubRecord(1, "1 $tag", $brec);
	if (empty($bbirt) && $tag == "BIRT") {
		$bbirt = GetSubRecord(1, "1 CHR", $brec);
		if (empty($bbirt)) $bbirt = GetSubRecord(1, "1 BAPM", $brec);
	}
	$c = CompareFactsDate($abirt, $bbirt);
	if ($c == 0) {
		$atime = GetSubRecord(3, "3 TIME", $abirt);
		$btime = GetSubRecord(3, "3 TIME", $bbirt);
		return StringSort($atime, $btime);
	}
	else return $c;
}
function CompareDateDescending($a, $b) {
	$result = CompareDate($a, $b);
	return (0 - $result);
}

function GedcomSort($a, $b) {
	$aname = Str2Upper($a["title"]);
	$bname = Str2Upper($b["title"]);

	return StringSort($aname, $bname);
}

function GedcomObjSort($a, $b) {
	$aname = Str2Upper($a->title);
	$bname = Str2Upper($b->title);

	return StringSort($aname, $bname);
}

function TitleObjSort($a, $b) {
	if ($a->title != $b->title) return StringSort(ltrim($a->title), ltrim($b->title));
	else {
		$anum = preg_replace("/\D*/", "", $a->xref);
		$bnum = preg_replace("/\D*/", "", $b->xref);
		return $anum > $bnum;
	}
}

function ItemObjSort($a, $b) {
	
	if (is_object($a)) {
		if (!is_null($a->sortable_name)) $aname = $a->sortable_name;
		else if (!is_null($a->name_array)) $aname = NameFunctions::SortableNameFromName($a->name_array[0][0]);
	}
	else if (is_array($a)) $aname = NameFunctions::SortableNameFromName($a[0]);
	else if (is_string($a)) $aname = $a;

	if (is_object($b)) {
		if (!is_null($b->sortable_name)) $bname = $b->sortable_name;
		else if (!is_null($b->name_array)) $bname = NameFunctions::SortableNameFromName($b->name_array[0][0]);
	}
	else if (is_array($b)) $bname = NameFunctions::SortableNameFromName($b[0]);
	else if (is_string($b)) $aname = $b;

	$aname = preg_replace("/([^ ]+)\*/", "$1", NameFunctions::StripPrefix($aname));
	$bname = preg_replace("/([^ ]+)\*/", "$1", NameFunctions::StripPrefix($bname));
	return StringSort($aname, $bname);
}

function SameGroup($a, $b) {
	static $carray;
	
	// order: indi, fam, obje, note, sour, repo
	if (!isset($carray)) $carray = array_flip(array("indi", "fam", "obje", "note", "sour", "repo"));
	if ($a['type']==$b['type']) return strnatcasecmp($a['id'], $b['id']);
	else return ($carray[$a['type']] > $carray[$b['type']]);
}

function IndiBirthSort($a, $b) {
	return CompareFacts($a->brec, $b->brec);
}

function IndiDeathSort($a, $b) {
	return CompareFacts($a->drec, $b->drec);
}
function AssoSort($a, $b) {
	return ItemObjSort($a->assoperson, $b->assoperson);
}

function AssoOSort($a, $b) {
	if ($a->xref1 != $b->xref1) return ItemObjSort($a->associated, $b->associated);
	else return CompareFacts("1 ".$a->fact."\r\n", "1 ".$b->fact."\r\n");
}
?>
