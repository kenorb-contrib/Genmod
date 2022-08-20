<?php
/**
 * RTL Functions
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
 * @version $Id: functions_rtl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * security check to prevent hackers from directly accessing this file
 */
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

$SpecialChar = array(' ','.',',','"','\'','/','\\','|',':',';','+','&','#','@','-','=','*','%','!','?','$','<','>',"\n");
$SpecialPar = array('(',')','[',']','{','}');
$SpecialNum  = array('0','1','2','3','4','5','6','7','8','9');

$RTLOrd = array(215,216,217,218,219);

/**
 * $HNN and $ANN are used in
 * RTLUndefined, CheckNN, GetCommonSurnames, print_block_name_top10 
 *
 */  

$HNN = "\x28\xd7\x9c\xd7\x90\x20\xd7\x99\xd7\x93\xd7\x95\xd7\xa2\x29";  
$ANN = "\x28\xd8\xba\xd9\x8a\xd8\xb1\x20\xd9\x85\xd8\xb9\xd8\xb1\xd9\x88\xd9\x81\x29"; 
 
//$HNN = "(לא ידוע)";
//$ANN = "(غير معروف)";

/**
 * Force a string in ltr direction
 *
 * This function returns a string in left-to-right direction.
 * To be used for non HTML string output (e.g. GD ImageTtfText function).
 *
 * @author opus27
 * @param string $name input string
 * @return string ltr string
 * @todo more hebrew controls (check numbers or &rlm; tags)
 * @todo other rtl languages
 */
function ltr_string($name) {
	global $RTLOrd;
	
	if(!GedcomConfig::$USE_RTL_FUNCTIONS) {
		return $name;
	} 
	else {
		// hebrew string => reverse
		$found = false;
		foreach($RTLOrd as $indexval => $ord) {
	   		if (strpos($name, chr($ord)) !== false) $found=true;
		}
		if ($found) {
		 	$ltrname = "";
			$i=0;
			while ($i<strlen($name)) {
	 			if (in_array(ord(substr($name,$i,1)),$RTLOrd)) {
					$ltrname = substr($name, $i, 2) . $ltrname;
					$i+=2;
				}
				else {
					if ($name{$i}==' ') $ltrname = " " . $ltrname;
					else if ($name{$i}=='(') $ltrname = ")" . $ltrname;
					else if ($name{$i}==')') $ltrname = "(" . $ltrname;
					else if ($name{$i}=='[') $ltrname = "]" . $ltrname;
					else if ($name{$i}==']') $ltrname = "[" . $ltrname;
					else if ($name{$i}=='{') $ltrname = "}" . $ltrname;
					else if ($name{$i}=='}') $ltrname = "{" . $ltrname;
					else $ltrname = $name{$i} . $ltrname;   //--- ???
					$i++;
				}
			}
			$ltrname=str_replace(";mrl&", "", $ltrname);
			$ltrname=str_replace(";mlr&", "", $ltrname);
			return $ltrname;
		}
		// other rtl languages => (to be completed)
		// else
	$ltrname=$name;
	$ltrname=str_replace("&lrm;", "", $ltrname);
	$ltrname=str_replace("&rlm;", "", $ltrname);
	return $ltrname;
	}
}

/**
 * process a string according to bidirectional rules
 *
 * this function will take a text string and reverse it for RTL languages
 * according to bidi rules.
 * @param string $text	String to change
 * @return string	the new bidi string
 * @todo add other RTL langauges
 */
function bidi_text($text) {
	global $RTLOrd;
	
	if(GedcomConfig::$USE_RTL_FUNCTIONS) {
		return $text;
	} 
	else {
		
		// דו"ח אישי
		//קראטוןםפ שדגכעיחלךף זסבה� מצתץ עברי איתה מאיה (אתקה) שם משפחה ‎
		//מספר מזהה (SSN)
		
		$found = false;
		foreach($RTLOrd as $indexval => $ord) {
	    	if (strpos($text, chr($ord))!==false) $found=true;
		}
		if (!$found) return $text;
	
		$special_chars = array(' ','"','\'','(',')','[',']',':',"\n");
		$newtext = "";
		$parts = array();
		$temp = "";
		$state = 0;
		$p = 0;
		for($i=0; $i<strlen($text); $i++) {
			$letter = $text{$i};
			//print $letter.ord($letter).",";
			//-- handle Hebrew chars
			if (in_array(ord($letter),$RTLOrd)) {
				if (!empty($temp)) {
					//-- just in case the $temp is a Hebrew char push it onto the stack
					if (in_array(ord($temp{0}),$RTLOrd));
					//-- if the $temp starts with a char in the special_chars array then remove the space and push it onto the stack seperately
					else if (in_array($temp{strlen($temp)-1}, $special_chars)) {
						$char = substr($temp, strlen($temp)-1);
						$temp = substr($temp, 0, strlen($temp)-1);
						if ($char=="[") $char = "]";
						else if ($char=="(") $char = ")";
						array_push($parts, $temp);
						array_push($parts, $char);
					}
					//-- otherwise push it onto the begining of the stack
					else array_unshift($parts, $temp);
				}
				$temp = $letter . $text{$i+1};
				$i++;
				if ($i < strlen($text)-1) {
					$l = $text{$i+1};
					if (in_array($l, $special_chars)) {
						if ($l=="]") $l = "[";
						else if ($l==")") $l = "(";
						$temp = $l . $temp;
						$i++;
					}
				}
				array_push($parts, $temp);
				$temp = "";
			}
			else if (ord($letter)==226) {
				if ($i < strlen($text)-2) {
					$l = $letter.$text{$i+1}.$text{$i+2};
					$i += 2;
					if (($l=="\xe2\x80\x8f")||($l=="\xe2\x80\x8e")) {	
						if (!empty($temp)) {
							$last = array_pop($parts);
							if ($temp{0}==")") $last = '(' . $last;
							else if ($temp{0}=="(") $last = ')' . $last;
							else if ($temp{0}=="]") $last = '[' . $last;
							else if ($temp{0}=="[") $last = ']' . $last;
							array_push($parts, $last);
							$temp = "";
						}
					}
				}
			}
			else $temp .= $letter;
		}
		if (!empty($temp)) {
			if (in_array(ord($temp{0}),$RTLOrd)) array_push($parts, $temp);
			else array_push($parts, $temp);
		}
		
		//-- loop through and check if parenthesis are correct... if parenthesis were broken by 
		//-- rtl text then they need to be reversed
		for($i=0; $i<count($parts); $i++) {
			$bef = "";
			$aft = "";
			$wt = preg_match("/^(\s*).*(\s*)$/", $parts[$i], $match);
			if ($wt>0) {
				$bef = $match[1];
				$aft = $match[2];
			}
			$temp = trim($parts[$i]);
			if (!empty($temp)) {
				if ($temp{0}=="(" && $temp{strlen($temp)-1}!=")") $parts[$i] = $bef.substr($temp, 1).")".$aft;
				if ($temp{0}=="[" && $temp{strlen($temp)-1}!="]") $parts[$i] = $bef.substr($temp, 1)."]".$aft;
				if ($temp{0}!="(" && $temp{strlen($temp)-1}==")") $parts[$i] = $bef."(".substr($temp, 0, strlen($temp)-1).$aft;
				if ($temp{0}!="[" && $temp{strlen($temp)-1}=="]") $parts[$i] = $bef."[".substr($temp, 0, strlen($temp)-1).$aft;
			}
		}
		$parts = array_reverse($parts);
		$newtext = implode("", $parts);
		return $newtext;
	}
}

/**
 * Verify if text is a RtL character
 *
 * This will verify if text is a RtL character
 * @param string $text to verify
 */
function oneRTLText($text) {
	global $RTLOrd;
	
	//--- What if gedcom in ANSI?
	if(!GedcomConfig::$USE_RTL_FUNCTIONS) return false;
	else return (strlen($text)==2 && in_array(ord($text),$RTLOrd));
}

/**
 * Verify if text starts by a RtL character
 *
 * This will verify if text starts by a RtL character
 * @param string $text to verify
 */
function begRTLText($text) { 
	global $RTLOrd;
	
	if(!GedcomConfig::$USE_RTL_FUNCTIONS) return false;
	else return (in_array(ord(substr(trim($text),0,2)),$RTLOrd) || in_array(ord(substr(trim($text),1,2)),$RTLOrd));
}

/**
 * Verify if text ends by a RtL character
 *
 * This will verify if text ends by a RtL character
 * @param string $text to verify
 */
function endRTLText($text) { 
	global $RTLOrd;
	
	if(!GedcomConfig::$USE_RTL_FUNCTIONS) return false;
	else return (in_array(ord(substr(trim($text),strlen(trim($text))-2,2)),$RTLOrd) || in_array(ord(substr(trim($text),strlen(trim($text))-3,2)),$RTLOrd));
}

/**
 * Verify if text is RTL
 *
 * This will verify if text has RTL characters
 *
 * @author 	Genmod Development Team
 * @param		string 	$text	Text to verify
 * @return	boolean	True if  RTL characters are present, false if not
 */
function hasRTLText($text) {
	global $RTLOrd;
	
	foreach ($RTLOrd as $key => $ord) {
		if (!(strpos($text, chr($ord)) === false)) return true;
	}
	return false;
} 

/**
 * Verify if text is LTR
 *
 * This will verify if text has LTR characters that are not special characters
 *
 * @author 	Genmod Development Team
 * @param		string 	$text	Text to verify
 * @return	boolean	True if LTR characters are present, false if not
 */
function hasLTRText($text) { 
	//--- Should have one fullspecial characters array in GM - 
	global $SpecialChar, $SpecialPar, $SpecialNum, $RTLOrd;
	
	for ($i=0; $i<strlen($text); $i++) {
		if (in_array(ord(substr(trim($text),$i,2)),$RTLOrd) || in_array(ord(substr(trim($text),$i-1,2)),$RTLOrd)) $i++;
		else {
			$byte = substr(trim($text),$i,1);
		    if (!in_array($byte,$SpecialChar) && !in_array($byte,$SpecialPar) && !in_array($byte,$SpecialNum)) return true;
	    }
	}
	return false;
}

/**
 * Use Hebrew/Arabic undefined for names with Hebrew/Arabic characters on any GM pages
 *  
 * @param string $text to verify
 */
function RTLUndefined($text) {   
	global $HNN, $ANN;
	   
	if (strpos($text, 215) !== false) $NN = $HNN;
	//-- else Arabic - letter ord is 216,217,218,219
	else $NN = $ANN;
	
	return ($NN);
}  
?>