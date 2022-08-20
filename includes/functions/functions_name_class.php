<?php
/**
 * Name Specific Functions
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
 * @version $Id: functions_name_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * security check to prevent hackers from directly accessing this file
 */
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

abstract class NameFunctions {
	
	public function GetFamilyDescriptor(&$family, $rev = false, $changes = false, $starred=true) {

		if (is_object($family->husb)) {
			if ($family->husb->disp_name) {
				if ($rev) $hname = $family->husb->revname;
				else $hname = $family->husb->name;
			}
//				$hname = self::GetSortableName($family->husb, false, $rev);
			else $hname = GM_LANG_private;
		}
		else {
			if ($rev) $hname = "@N.N., @P.N.";
			else $hname = "@P.N. @N.N.";
		}
		if (is_object($family->wife)) {
			if ($family->wife->disp_name) {
				if ($rev) $wname = $family->wife->revname;
				else $wname = $family->wife->name;
			}
//				$wname = self::GetSortableName($family->wife, false, $rev);
			else $wname = GM_LANG_private;
		}
		else {
			if ($rev) $wname = "@N.N., @P.N.";
			else $wname = "@P.N. @N.N.";
		}

		if (!empty($hname) && !empty($wname)) return self::CheckNN($hname,$starred)." + ".self::CheckNN($wname,$starred);
		else if (!empty($hname) && empty($wname)) return self::CheckNN($hname,$starred);
		else if (empty($hname) && !empty($wname)) return self::CheckNN($wname,$starred);
	}
	
	public function GetAllFamilyDescriptors(&$family, $rev = false, $starred=true, $letter="", $fletter="") {
		global $NAME_REVERSE;

		$hnamearray = array();
		$wnamearray = array();
		$phnamearray = array();
		$pwnamearray = array();
		$names = array();
		//print "letter: ".$letter." fletter ".$fletter."<br />";
		if (is_object($family->husb)) {
			foreach($family->husb->name_array as $key => $name) {
				// print $letter."-".$name[1]."==".$fletter."-".$name[5]."<br />";
				if (($letter == "" || $letter == $name[1]) && ($fletter == "" || $fletter == $name[5])) {
					if ($NAME_REVERSE) $phnamearray[] = self::SortableNameFromName(self::ReverseName($name[0]), $rev);
					else $phnamearray[] = self::SortableNameFromName($name[0], $rev);
					// print "addh ".self::SortableNameFromName($name[0], $rev)."<br />";
				}
				if ($NAME_REVERSE) $hnamearray[] = self::SortableNameFromName(self::ReverseName($name[0]), $rev);
				else $hnamearray[] = self::SortableNameFromName($name[0], $rev);
			}
		}
		else $hnamearray[] = "@N.N., @P.N.";

		if (is_object($family->wife)) {
			foreach($family->wife->name_array as $key => $name) {
				if (($letter == "" || $letter == $name[1]) && ($fletter == "" || $fletter == $name[5])) {
					if ($NAME_REVERSE) $pwnamearray[] = self::SortableNameFromName(self::ReverseName($name[0]), $rev);
					else $pwnamearray[] = self::SortableNameFromName($name[0], $rev);
					// print "addw ".self::SortableNameFromName($name[0], $rev)." ".$name[1]." ".$name[5]."<br />";
				}
				if ($NAME_REVERSE) $wnamearray[] = self::SortableNameFromName(self::ReverseName($name[0]), $rev);
				else $wnamearray[] = self::SortableNameFromName($name[0], $rev);
			}
		}
		else $wnamearray[] = "@N.N., @P.N.";

		foreach($phnamearray as $key1 => $hname) {
			foreach($wnamearray as $key2 => $wname) {
				$names[] = self::CheckNN($hname,$starred)." + ".self::CheckNN($wname,$starred);
			}
		}
		foreach($pwnamearray as $key1 => $wname) {
			foreach($hnamearray as $key2 => $hname) {
				$names[] = self::CheckNN($wname,$starred)." + ".self::CheckNN($hname,$starred);
			}
		}
		return $names;
	}
	
	public function GetFamilyAddDescriptor($family, $rev = false, $changes = false) {
		
		$hname = "";
		$wname = "";
		$disph = false;
		$dispw = false;
		
		if (is_object($family->husb)) {
			$hname = self::GetSortableAddName($family->husb, $rev, $changes);
			if (!empty($hname) && $family->husb->disp_name) $disph = true;
		}
		if (is_object($family->wife)) {
			$wname = self::GetSortableAddName($family->wife, $rev, $changes);
			if (!empty($wname) && $family->wife->disp_name) $dispw = true;
		}
		
		// One of both addnames must be found, otherwise it's no use to return anything
		if (!$disph && !$dispw) return "";

		// if we cannot display the name, or the person does not exist, fill the name with the appropriate values
		if (is_object($family->wife)) {
			if (!$family->wife->disp_name) $wname = GM_LANG_private;
		}
		else {
			if ($rev) $wname = "@P.N. @N.N.";
			else $wname = "@N.N., @P.N.";
		}
		if (is_object($family->husb)) {
			if (!$family->husb->disp_name) $hname = GM_LANG_private;
		}
		else {
			if ($rev) $hname = "@P.N. @N.N.";
			else $hname = "@N.N., @P.N.";
		}
		
		if (!empty($hname) && !empty($wname)) return self::CheckNN($hname)." + ".self::CheckNN($wname);
		else if (!empty($hname) && empty($wname)) return self::CheckNN($hname)." + ".self::CheckNN(self::GetSortableName($family->wife, false, $rev));
		else if (empty($hname) && !empty($wname)) return self::CheckNN(self::GetSortableName($family->husb, false, $rev))." + ".self::CheckNN($wname);
	}
		
	/**
	 * get the person's name as surname, given names
	 *
	 * This function will return the given person's name is a format that is good for sorting
	 * Surname, given names
	 * @param string $pid the gedcom xref id for the person
	 * @param string $alpha	only get the name that starts with a certain letter
	 * @param string $surname only get the name that has this surname
	 * @param boolean $allnames true returns all names in an array
	 * @param boolean $rev reverse the name order
	 * @param boolean $changes include unapproved changes
	 * @return string the sortable name
	 */
	public function GetSortableName(&$person, $allnames=false, $rev = false, $count=0) {
		global $NAME_REVERSE;
	
		$mynames = array();
	
		if (!is_object($person)) {
			if ($allnames == false) {
				if ($rev) return "@P.N. @N.N.";
				else return "@N.N., @P.N.";
			}
			else {
				if ($rev) $mynames[] = "@P.N. @N.N.";
				else $mynames[] = "@N.N., @P.N.";
				return $mynames;
			}
		}
	
	
		if ($allnames == true) {
			$names = $person->name_array;
			$mynames = array();
			foreach ($names as $key => $name) {
				if ($NAME_REVERSE) $mynames[] = self::SortableNameFromName(self::ReverseName($name[0]), $rev).(empty($name[3]) || !GedcomConfig::$SHOW_NICK ? "" : " ".substr(GedcomConfig::$NICK_DELIM, 0, 1).$name[3].substr(GedcomConfig::$NICK_DELIM, 1, 1));
				else $mynames[] = self::SortableNameFromName($name[0], $rev).(empty($name[3]) || !GedcomConfig::$SHOW_NICK ? "" : " ".substr(GedcomConfig::$NICK_DELIM, 0, 1).$name[3].substr(GedcomConfig::$NICK_DELIM, 1, 1));
			}
			return $mynames;
		}
		$name = $person->name_array[$count];
		if ($NAME_REVERSE) return self::SortableNameFromName(self::ReverseName($name[0]), $rev).(empty($name[3]) || !GedcomConfig::$SHOW_NICK ? "" : " ".substr(GedcomConfig::$NICK_DELIM, 0, 1).$name[3].substr(GedcomConfig::$NICK_DELIM, 1, 1));
		else return self::SortableNameFromName($name[0], $rev).(empty($name[3]) || !GedcomConfig::$SHOW_NICK ? "" : " ".substr(GedcomConfig::$NICK_DELIM, 0, 1).$name[3].substr(GedcomConfig::$NICK_DELIM, 1, 1));
	}
	
	// -- find and return a given individual's second name in sort format: familyname, firstname
	public function GetSortableAddName($person, $rev = false, $changes = false, $count=0) {
		global $NAME_REVERSE;
	
		//-- get the name from the indexes
		if ($changes) $record = $person->changedgedrec;
		else $record = $person->gedrec;

		$name_record = GetSubRecord(1, "1 NAME", $record, $count+1);

		$name = "";

		// Check for ROMN name
		$romn = preg_match("/(2 ROMN (.*)|2 _HEB (.*))/", $name_record, $romn_match);
		if ($romn == 0) {
			$orgname = self::GetNameInRecord($name_record);
			if (self::HasChinese($orgname)){
				$romn_match[0] = self::GetPinYin($orgname);
				$romn = 1;
			}
			else if (self::HasCyrillic($orgname)) {
				$romn_match[0] = self::GetTransliterate($orgname);
				$romn = 1;
			}
		}
		if ($romn > 0){
	    	$names = preg_split("/\//", $romn_match[count($romn_match)-1]);
			if ($names[0] == "") $names[0] = "@P.N.";	//-- MA
			if (empty($names[1])) $names[1] = "@N.N.";	//-- MA
			if (count($names)>1) {
				$fullname = trim($names[1]).",";
				$fullname .= ",# ".trim($names[0]);
				if (count($names)>2) $fullname .= ",% ".trim($names[2]);
			}
			else $fullname=$romn_match[1];
			if (!$NAME_REVERSE) {
				if ($rev) $name = trim($names[0])." ".trim($names[1]);
				else $name = trim($names[1]).", ".trim($names[0]);
			}
			else {
				if ($rev) $name = trim($names[1])." ".trim($names[0]);
				else $name = trim($names[0])." ,".trim($names[1]);
			}
		}
		return $name;
	}
	/**
	 * Get the name from the raw gedcom record
	 *
	 * @param string $indirec the raw gedcom record to get the name from
	 */
	public function GetNameInRecord($indirec, $import=false) {
		
		$name = "";
		$nt = preg_match("/1 NAME (.*)/", $indirec, $ntmatch);
		if ($nt>0) {
			$name = trim($ntmatch[1]);
			$name = preg_replace(array("/__+/", "/\.\.+/", "/^\?+/"), array("", "", ""), $name);
	
			//-- check for a surname
			$ct = preg_match("~/(.*)/~", $name, $match);
			if ($ct > 0) {
				$surname = trim($match[1]);
				$surname = preg_replace(array("/__+/", "/\.\.+/", "/^\?+/"), array("", "", ""), $surname);
				if (empty($surname)) $name = preg_replace("~/(.*)/~", "/@N.N./", $name);
			}
			else {
				//-- check for the surname SURN tag
				$ct = preg_match("/2 SURN (.*)/", $indirec, $match);
				if ($ct>0) {
					$pt = preg_match("/2 SPFX (.*)/", $indirec, $pmatch);
					if ($pt>0) $name .=" ".trim($pmatch[1]);
					$surname = trim($match[1]);
					$surname = preg_replace(array("/__+/", "/\.\.+/", "/^\?+/"), array("", "", ""), $surname);
					if (empty($surname)) $name .= " /@N.N./";
					else $name .= " /".$surname."/";
				}
				else $name .= " /@N.N./";
			}
			
			$givens = preg_replace("~/.*/~", "", $name);
			if (empty($givens)) $name = "@P.N. ".$name;
		}
		else {
			/*-- this is all extraneous to the 1 NAME tag and according to the gedcom spec
			-- the 1 NAME tag should take preference
			*/
			$name = "";
			//-- check for the given names
			$gt = preg_match("/2 GIVN (.*)/", $indirec, $gmatch);
			if ($gt>0) $name .= trim($gmatch[1]);
			else $name .= "@P.N.";
	
			//-- check for the surname
			$ct = preg_match("/2 SURN (.*)/", $indirec, $match);
			if ($ct>0) {
				$pt = preg_match("/2 SPFX (.*)/", $indirec, $pmatch);
				if ($pt>0) $name .=" ".trim($pmatch[1]);
				$surname = trim($match[1]);
				if (empty($surname)) $name .= " /@N.N./";
				else $name .= " /".$surname."/";
			}
			if (empty($name)) $name = "@P.N. /@N.N./";
	
			$st = preg_match("/2 NSFX (.*)/", $indirec, $smatch);
			if ($st>0) $name.=" ".trim($smatch[1]);
			$pt = preg_match("/2 SPFX (.*)/", $indirec, $pmatch);
			if ($pt>0) $name =strtolower(trim($pmatch[1]))." ".$name;
		}
		// handle PAF extra NPFX [ 961860 ]
		$ct = preg_match("/2 NPFX (.*)/", $indirec, $match);
		if ($ct>0) {
			$npfx = trim($match[1]);
			if (strpos($name, $npfx)===false) $name = $npfx." ".$name;
		}
		// Insert the nickname if the option is set
//		if (GedcomConfig::$SHOW_NICK && !$import) {
//			$n = self::GetNicks($indirec);
//			if (count($n) > 0) {
//				$ct = preg_match("~(.*)/(.*)/(.*)~", $name, $match);
//				$name = $match[1].substr(GedcomConfig::$NICK_DELIM, 0, 1).$n[0].substr(GedcomConfig::$NICK_DELIM, 1, 1)."/".$match[2]."/".$match[3];
//			}
//		}
		return $name;
	}
	
	/**
	 * Get the sortable name from the gedcom name
	 *
	 * @param string $name 	the name from the 1 NAME gedcom line including the /
	 * @return string 	The new name in the form Surname, Given Names
	 */
	public function SortableNameFromName($name, $rev = false) {
		global $NAME_REVERSE;

		// NOTE: Remove any unwanted characters from the name
		if (preg_match("/^\.(\.*)$|^\?(\?*)$|^_(_*)$|^,(,*)$/", $name)) $name = preg_replace(array("/,/","/\./","/_/","/\?/"), array("","","",""), $name);
	
		$ct = preg_match("~(.*)/(.*)/(.*)~", $name, $match);
		if ($ct>0) {
			$surname = trim($match[2]);
			if (empty($surname)) $surname = "@N.N.";
			$givenname = trim($match[1]);
			$othername = trim($match[3]);
			if (self::HasChinese($name, true) || self::HasCyrillic($name, true)) $add = "";
			else $add = " ";
			if (empty($givenname)&&!empty($othername)) {
				$givenname = $othername;
				$othername = "";
			}
			if ($rev) {
				if (empty($givenname)) $givenname = "@P.N.";
				if ($NAME_REVERSE || self::HasChinese($name, true)) $name = $surname.$add.$givenname;
				else $name = $givenname.$add.$surname;
				if (!empty($othername)) $name .= $add.$othername;
			}
			else {
				if (empty($givenname)) $givenname = "@P.N.";
				$name = $surname;
				if (!empty($othername)) $name .= $add.$othername;
				if ($NAME_REVERSE || self::HasChinese($name, true)) $name .= $add.$givenname;
				else $name .= ", ".$givenname;
			}
		}
		if (!empty($name)) return $name;
		else {
			if ($rev) return "@P.N. @N.N.";
			else return "@N.N., @P.N.";
		}
	}
	
	/**
	 * reverse a name
	 * this function will reverse a name for languages that
	 * prefer last name first such as hungarian and chinese
	 * @param string $name	the name to reverse, must be gedcom encoded as if from the 1 NAME line
	 * @return string		the reversed name
	 */
	public function ReverseName($name) {
		$ct = preg_match("~(.*)/(.*)/(.*)~", $name, $match);
		if ($ct>0) {
			if (self::HasChinese($name, true)) $add = "";
			else $add = " ";
			$surname = trim($match[2]);
			if (empty($surname)) $surname = "@N.N.";
			$givenname = trim($match[1]);
			$othername = trim($match[3]);
			if (empty($givenname)&&!empty($othername)) {
				$givenname = $othername;
				$othername = "";
			}
			if (empty($givenname)) $givenname = "@P.N.";
			$name = $surname;
			$name .= $add.$givenname;
			if (!empty($othername)) $name .= $add.$othername;
		}
		
		return $name;
	}
	
	public function GetNicks($namerec) {
		
		$nicks = array();
		if (!empty($namerec)) {
			$i = 1;
			while($n = GetSubrecord(2, "2 NICK", $namerec, $i)) {
				$nicks[] = GetGedcomValue("NICK", 2, $n);
				$i++;
			}
		}
		return $nicks;
	}
	
	/**
	 * get an array of names from an indivdual record
	 * @param string $indirec	The raw individual gedcom record
	 * @return array	The array of individual names
	 */
	public function GetIndiNames($indirec, $import=false, $marr_names=true) {
		$names = array();
		//-- get all names
		$namerec = GetSubRecord(1, "1 NAME", $indirec, 1);
		if (empty($namerec)) $names[] = array("@P.N. /@N.N./", "@", "@N.N.", "", "A", "@");
		else {
			$j = 1;
			while(!empty($namerec)) {
				$name = self::GetNameInRecord($namerec, $import);
				$nick = GetGedcomValue("NAME:NICK", 1, $namerec);
				$surname = self::ExtractSurname($name);
				if (empty($surname)) $surname = "@N.N.";
				$lname = preg_replace("/^[a-z0-9 \.]+/", "", $surname);
				if (empty($lname)) $lname = $surname;
				$letter = self::GetFirstLetter($lname, $import);
				$letter = Str2Upper($letter);
				$fletter = self::GetFirstLetter($name, $import);
				$fletter = Str2Upper($fletter);
				if ($fletter == "/") $fletter = "@";
				if (empty($letter)) $letter = "@";
				if (preg_match("~/~", $name)==0) $name .= " /@N.N./";
				if ($j == 1) $names[] = array($name, $letter, $surname, $nick, "P", $fletter);
				else $names[] = array($name, $letter, $surname, $nick, "A", $fletter);
	
				//-- check for _HEB or ROMN name sub tags
				$addname = self::GetAddPersonNameInRecord($namerec, true, $import);
				if (!empty($addname)) {
					$surname = self::ExtractSurname($addname);
					if (empty($surname)) $surname = "@N.N.";
					$lname = preg_replace("/^[a-z0-9 \.]+/", "", $surname);
					if (empty($lname)) $lname = $surname;
					$letter = self::GetFirstLetter($lname, $import);
					$letter = Str2Upper($letter);
					$fletter = self::GetFirstLetter($addname, $import);
					$fletter = Str2Upper($fletter);
					if ($fletter == "/") $fletter = "@";
					if (empty($letter)) $letter = "@";
					if (preg_match("~/~", $addname)==0) $addname .= " /@N.N./";
					$names[] = array($addname, $letter, $surname, "", "A", $fletter);
				}
				//-- check for _MARNM name subtags
				if ($marr_names) {
					$ct = preg_match_all("/\d _MARNM (.*)/", $namerec, $match, PREG_SET_ORDER);
					for($i=0; $i<$ct; $i++) {
						$marriedname = trim($match[$i][1]);
						$surname = self::ExtractSurname($marriedname);
						if (empty($surname)) $surname = "@N.N.";
						$lname = preg_replace("/^[a-z0-9 \.]+/", "", $surname);
						if (empty($lname)) $lname = $surname;
						$letter = self::GetFirstLetter($lname, $import);
						$letter = Str2Upper($letter);
						$fletter = self::GetFirstLetter($marriedname, $import);
						$fletter = Str2Upper($fletter);
						if ($fletter == "/") $fletter = "@";
						if (empty($letter)) $letter = "@";
						if (preg_match("~/~", $marriedname)==0) $marriedname .= " /@N.N./";
						$names[] = array($marriedname, $letter, $surname, "", "C", $fletter);
					}
				}
				//-- check for _AKA name subtags
				$ct = preg_match_all("/\d _AKA (.*)/", $namerec, $match, PREG_SET_ORDER);
				for($i=0; $i<$ct; $i++) {
					$akaname = trim($match[$i][1]);
					$surname = self::ExtractSurname($akaname, false);
					if (empty($surname)) $surname = "@N.N.";
					$lname = preg_replace("/^[a-z0-9 \.]+/", "", $surname);
					if (empty($lname)) $lname = $surname;
					$letter = self::GetFirstLetter($lname, $import);
					$letter = Str2Upper($letter);
					$fletter = self::GetFirstLetter($akaname, $import);
					$fletter = Str2Upper($fletter);
					if ($fletter == "/") $fletter = "@";
					if (empty($letter)) $letter = "@";
					if (preg_match("~/~", $akaname)==0) $akaname .= " /@N.N./";
					$names[] = array($akaname, $letter, $surname, "", "A", $fletter);
				}
				$j++;
				$namerec = GetSubRecord(1, "1 NAME", $indirec, $j);
			}
		}
		return $names;
	}
	
	/**
	 * This function replaces @N.N. and @P.N. with the language specific translations
	 * @param mixed $names	$names could be an array of name parts or it could be a string of the name
	 * @return string
	 */
	public function CheckNN($names, $starred=true) {
		global $HNN, $ANN;
	
		$fullname = "";
		$NN = GM_LANG_NN;
	 	$PN = GM_LANG_PN;
	
		if (!is_array($names)){
			if (hasRTLText($names)) {
				$NN = RTLUndefined($names);
				$PN = $NN;
			}
			$names = stripslashes($names);
			if (self::HasChinese($names, true)) $names = preg_replace(array("~ /~","~/,~","~/~"), array("", ",", ""), $names);
			else $names = preg_replace(array("~ /~","~/,~","~/~"), array(" ", ",", " "), $names);
			$names = preg_replace(array("/@N.N.?/","/@P.N.?/"), array($NN,$PN), trim($names));
	
			if (GedcomConfig::$UNDERLINE_NAME_QUOTES) {
				if ($starred) $names = preg_replace("/\"(.+)\"/", "<span class=\"StarredName\">$1</span>", $names);
				else $names = preg_replace("/\"(.+)\"/", "$1", $names);
			}
			//-- underline names with a * at the end
			//-- see this forum thread http://sourceforge.net/forum/forum.php?thread_id=1223099&forum_id=185165
			if ($starred) $names = preg_replace("/([^ ]+)\*/", "<span class=\"StarredName\">$1</span>", $names);
			else $names = preg_replace("/([^ ]+)\*/", "$1", $names);
			return $names;
		}
		if (count($names) == 2 && stristr($names[0], "@N.N") && stristr($names[1], "@N.N")){
			$fullname = GM_LANG_NN. " + ". GM_LANG_NN;
		}
		else {
			for($i=0; $i<count($names); $i++) {
				if (hasRTLText($names[$i])) {
					$NN = RTLUndefined($names[$i]);
					$PN = $NN;
				}
				
				for($i=0; $i<count($names); $i++) {
					if (GedcomConfig::$UNDERLINE_NAME_QUOTES) {
						if ($starred) $names[$i] = preg_replace("/\"(.+)\"/", "<span class=\"StarredName\">$1</span>", $names[$i]);
						else $names[$i] = preg_replace("/\"(.+)\"/", "$1", $names[$i]);
					}
					//-- underline names with a * at the end
					//-- see this forum thread http://sourceforge.net/forum/forum.php?thread_id=1223099&forum_id=185165
					if ($starred) $names[$i] = preg_replace("/([^ ]+)\*/", "<span class=\"StarredName\">$1</span>", $names[$i]);
					else $names[$i] = preg_replace("/([^ ]+)\*/", "$1", $names[$i]);
	
					if (stristr($names[$i], "@N.N")) $names[$i] = preg_replace("/@N.N.?/", $NN, trim($names[$i]));
	                if (stristr($names[$i], "@P.N")) $names[$i] = $PN;
					if (substr(trim($names[$i]), 0, 5) == "@P.N." && strlen(trim($names[$i])) > 5) {
						$names[$i] = substr(trim($names[$i]), 5, (strlen($names[$i])-5));
					}
	 				if ($i==1 && (stristr($names[0], GM_LANG_NN) || stristr($names[0],$HNN) || stristr($names[0],$ANN)) && count($names) == 3) $fullname .= ", ";
	 				else if ($i==2 && (stristr($names[2], GM_LANG_NN)||stristr($names[2],$HNN)||stristr($names[2],$ANN)) && count($names) == 3) $fullname .= " + ";
					else if ($i==2 && stristr($names[2], "Individual ") && count($names) == 3) $fullname .= " + ";
					else if ($i==2 && count($names) > 3) $fullname .= " + ";
					else $fullname .= ", ";
					$fullname .= trim($names[$i]);
				}
			}
		}
		if (empty($fullname)) return GM_LANG_NN;
		if (substr(trim($fullname),-1) === ",") $fullname = substr($fullname,0,strlen(trim($fullname))-1);
		if (substr(trim($fullname),0,2) === ", ") $fullname = substr($fullname,2,strlen(trim($fullname)));

		return $fullname;
	}
	
	public function AbbreviateName($name, $length) {
		
		// For now, we can only abbreviate 1 byte character strings
		if (!utf8_isASCII($name)) return $name;
		
		// If the string length is ok, just return the name
		
		// Check for nicknames and remove them
		if (GedcomConfig::$SHOW_NICK) {
			$start = strpos($name, " ".substr(GedcomConfig::$NICK_DELIM, 0, 1));
			if ($start) {
				$part1 = substr($name, 0, $start);
				$pos2 = strpos($name, substr(GedcomConfig::$NICK_DELIM, 1, 1));
				$part2 = substr($name, $pos2 + 1);
				$name = $part1.$part2;
				if (strlen($name) <= $length) return $name;
			}
		}
		
		// Continue with normal checking
		$prevabbrev = false;
		$words = preg_split("/ /", $name);
		$name = $words[count($words)-1];
		for($i=count($words)-2; $i>=0; $i--) {
			$len = strlen($name);
			for($j=$i; $j>=0; $j--) {
				// Added: count the space
				$len += strlen($words[$j]) + 1;
			}
			if ($len>$length) {
				// Added: only convert upper case first letter nameparts to first letters. This prevents a surname as "de Haan" to convert to "D. Haan"
				if (str2lower($words[$i]) == $words[$i]) {
					$name = $words[$i]." ".$name;
				}
				else {
					if ($prevabbrev) $name = self::GetFirstLetter($words[$i]).".".$name;
					else {
						$name = self::GetFirstLetter($words[$i]).". ".$name;
						$prevabbrev = true;
					}
				}
			}
			else $name = $words[$i]." ".$name;
		}
		return $name;
	}
	
	public function GetAddPersonNameInRecord($name_record, $keep_slash=false, $import=false) {
		global $NAME_REVERSE;
	
		// Check for ROMN name
		$romn = preg_match("/(2 ROMN (.*)|2 _HEB (.*))/", $name_record, $romn_match);
		if ($romn > 0){
			if ($keep_slash) return trim($romn_match[count($romn_match)-1]);
			$names = preg_split("/\//", $romn_match[count($romn_match)-1]);
			if (count($names)>1) {
				if ($NAME_REVERSE) {
					$name = trim($names[1])." ".trim($names[0]);
				}
				else {
					$name = trim($names[0])." ".trim($names[1]);
				}
			}
		    else $name = trim($names[0]);
		}
		// If not found, and chinese, generate the PinYin equivalent of the name
		else {
			$orgname = self::GetNameInRecord($name_record, $import);
			if (self::HasChinese($orgname, $import) || self::HasCyrillic($orgname, $import)) {
				if (self::HasChinese($orgname, $import)) {
					$name = self::GetPinYin($orgname, $import);
				}
				else $name = self::GetTransliterate($orgname, $import);
				if ($keep_slash) return trim($name);
				$names = preg_split("/\//", $name);
				if (count($names)>1) {
					if ($NAME_REVERSE) {
						$name = trim($names[1])." ".trim($names[0]);
					}
					else {
						$name = trim($names[0])." ".trim($names[1]);
					}
				}
			    else $name = trim($names[0]);
			}
			else $name = "";
		}
		if ($NAME_REVERSE) $name = self::ReverseName($name);
		return $name;
	}
	
	/**
	 * Extract the surname from a name
	 *
	 * This function will extract the surname from an individual name in the form
	 * Surname, Given Name
	 * All surnames are stored in the global $surnames array
	 * It will only get the surnames that start with the letter $alpha
	 * For names like van den Burg, it will only return the "Burg"
	 * It will work if the surname is all lowercase
	 * @param string $indiname	the name to extract the surname from
	 */
	public function ExtractSurname($indiname) {
		global $surnames, $alpha, $surname, $show_all, $i, $testname;
	
		if (!isset($testname)) $testname="";
	
		$nsurname = "";
		//-- get surname from a standard name
		if (preg_match("~/([^/]*)/~", $indiname, $match)>0) {
			$nsurname = trim($match[1]);
		}
		//-- get surname from a sortable name
		else {
			$names = preg_split("/,/", $indiname);
			if (count($names)==1) $nsurname = "@N.N.";
			else $nsurname = trim($names[0]);
			$nsurname = preg_replace(array("/ [jJsS][rR]\.?/", "/ I+/"), array("",""), $nsurname);
			
		}
		return $nsurname;
	}
	
	/**
	 * Get array of common surnames from index
	 *
	 * This function returns a simple array of the most common surnames
	 * found in the individuals list.
	 * @param int $min the number of times a surname must occur before it is added to the array
	 */
	public function GetCommonSurnamesIndex($gedid) {
		global $GEDCOMS;
	
		if (empty($GEDCOMS[$gedid]["commonsurnames"])) {
			SwitchGedcom($gedid);
			$surnames = self::GetCommonSurnames(GedcomConfig::$COMMON_NAMES_THRESHOLD);
			if (count($surnames) != 0) {
				$sns = "";
				foreach($surnames as $indexval => $surname) {
					$sns .= $surname["name"].", ";
				}
				$sql = "UPDATE ".TBLPREFIX."gedcoms SET g_commonsurnames='".DbLayer::EscapeQuery($sns)."' WHERE g_file='".$gedid."'";
				$res = NewQuery($sql);
				$GEDCOMS[$gedid]["commonsurnames"] = $sns;
			}
			SwitchGedcom();
		}
		$surnames = array();
		if (empty($GEDCOMS[$gedid]["commonsurnames"]) || ($GEDCOMS[$gedid]["commonsurnames"]==",")) return $surnames;
		$names = preg_split("/[,;]/", $GEDCOMS[$gedid]["commonsurnames"]);
		foreach($names as $indexval => $name) {
			$name = trim($name);
			if (!empty($name)) $surnames[$name]["name"] = stripslashes($name);
		}
		return $surnames;
	}
	
	/**
	 * Get array of common surnames
	 *
	 * This function returns a simple array of the most common surnames
	 * found in the individuals list.
	 * @param int $min the number of times a surname must occur before it is added to the array
	 */
	public function GetCommonSurnames($min) {
		global $indilist, $GEDCOMS, $HNN, $ANN;
	
		$surnames = array();
		if (!CONFIGURED || !UserController::AdminUserExists() || (count($GEDCOMS)==0) || (!CheckForImport(GedcomConfig::$GEDCOMID))) return $surnames;
		//-- this line causes a bug where the common surnames list is not properly updated
		// if ((!isset($indilist))||(!is_array($indilist))) return $surnames;
		$surnames = BlockFunctions::GetTopSurnames(100);
		arsort($surnames);
		$topsurns = array();
		$i=0;
		foreach($surnames as $indexval => $surname) {
			$surname["name"] = trim($surname["name"]);
			if (!empty($surname["name"]) 
					&& stristr($surname["name"], "@N.N")===false
					&& stristr($surname["name"], $HNN)===false
					&& stristr($surname["name"], $ANN.",")===false
					&& stristr(GedcomConfig::$COMMON_NAMES_REMOVE, $surname["name"])===false ) {
				if ($surname["match"]>=$min) {
					$topsurns[Str2Upper($surname["name"])] = $surname;
				}
				$i++;
			}
		}
		$addnames = preg_split("/[,;] /", GedcomConfig::$COMMON_NAMES_ADD);
		if ((count($addnames)==0) && (!empty(GedcomConfig::$COMMON_NAMES_ADD))) $addnames[] = GedcomConfig::$COMMON_NAMES_ADD;
		foreach($addnames as $indexval => $name) {
			if (!empty($name)) {
				$topsurns[$name]["name"] = $name;
				$topsurns[$name]["match"] = $min;
			}
		}
		$delnames = preg_split("/[,;] /", GedcomConfig::$COMMON_NAMES_REMOVE);
		if ((count($delnames)==0) && (!empty(GedcomConfig::$COMMON_NAMES_REMOVE))) $delnames[] = GedcomConfig::$COMMON_NAMES_REMOVE;
		foreach($delnames as $indexval => $name) {
			if (!empty($name)) {
				unset($topsurns[$name]);
			}
		}
	
		uasort($topsurns, "ItemSort");
		return $topsurns;
	}

	/**
	 * strip name prefixes
	 *
	 * this function strips the prefixes of lastnames
	 * get rid of jr. Jr. Sr. sr. II, III and van, van der, de lowercase surname prefixes
	 * a . and space must be behind a-z to ensure shortened prefixes and multiple prefixes are removed
	 * @param string $lastname	The name to strip
	 * @return string	The updated name
	 */
	public function StripPrefix($lastname){
	
		$name = preg_replace(array("/ [jJsS][rR]\.?,/", "/ I+,/", "/^[a-z. ]*/"), array(",",",",""), $lastname);
		$name = trim($name);
		return $name;
	}

	/**
	 * Get first letter
	 *
	 * Get the first letter of a UTF-8 string
	 *
	 * @author Genmod Development Team
	 * @param string $text	the text to get the first letter from
	 * @return string 	the first letter UTF-8 encoded
	 */
	public function GetFirstLetter($text, $import=false) {
	
		$text = trim(Str2Upper($text));
	
	//	if ($import == true) {
	//		$hungarianex = array("CS", "DZ" ,"GY", "LY", "NY", "SZ", "TY", "ZS", "DZS");
	//		$danishex = array("OE", "AE", "AA");
	//		if (substr($text, 0, 3)=="DZS") $letter = substr($text, 0, 3);
	//		else if (in_array(substr($text, 0, 2), $hungarianex) || in_array(substr($text, 0, 2), $danishex)) $letter = substr($text, 0, 2);
	//		else $letter = substr($text, 0, 1);
	//	}
	//	else {
			if (GedcomConfig::$GEDCOMLANG == "hungarian"){
				$hungarianex = array("CS", "DZ" ,"GY", "LY", "NY", "SZ", "TY", "ZS", "DZS");
				if (substr($text, 0, 3)=="DZS") $letter = substr($text, 0, 3);
				else if (in_array(substr($text, 0, 2), $hungarianex)) $letter = substr($text, 0, 2);
				else $letter = substr($text, 0, 1);
			}
			if (GedcomConfig::$GEDCOMLANG == "danish" || GedcomConfig::$GEDCOMLANG == "norwegian"){
				$danishex = array("OE", "AE", "AA");
				$letter = Str2Upper(substr($text, 0, 2));
				if (in_array($letter, $danishex)) {
					if ($letter == "AA") $text = "Å";
					else if ($letter == "OE") $text = "Ø";
					else if ($letter == "AE") $text = "Æ";
				}
				$letter = substr($text, 0, 1);
			}
	
	//	}
		if (MB_FUNCTIONS) {
			if (isset($letter) && mb_detect_encoding(mb_substr($text, 0, 1)) == "ASCII") return $letter;
			else return mb_substr($text, 0, 1);
		}
		//-- if the letter is an other character then A-Z or a-z
		//-- define values of characters to look for
		$ord_value2 = array(92, 195, 196, 197, 206, 207, 208, 209, 214, 215, 216, 217, 218, 219);
		$ord_value3 = array(228, 229, 230, 231, 232, 233);
		$ord = ord(substr($text, 0, 1));
		if (in_array($ord, $ord_value2)) $letter = stripslashes(substr($text, 0, 2));
		else if (in_array($ord, $ord_value3)) $letter = stripslashes(substr($text, 0, 3));
	
		return $letter;
	}

	/**
	 * determine the Daitch-Mokotoff Soundex code for a name
	 * @param string $name	The name
	 * @return array		The array of codes
	 */
	
	public function DMSoundex($name, $option = "") {
		global $dmsoundexlist, $dmcoding, $maxchar, $cachecount, $cachename;
		
	//	Check for empty string
		if (empty($name)) return array();
		
		// If the code tables are not loaded, reload! Keep them global!
		if (!isset($dmcoding)) {
			$fname = SystemConfig::$GM_BASE_DIRECTORY."includes/values/dmarray.full.utf-8.php";
			require($fname);
		}
	
		// Load the previously saved cachefile and return. Keep the cache global!
		if ($option == "opencache") {
			$cachename = INDEX_DIRECTORY."DM".date("mdHis", filemtime(SystemConfig::$GM_BASE_DIRECTORY."includes/values/dmarray.full.utf-8.php")).".dat";
			if (file_exists($cachename) && filesize($cachename) != 0) {
	//			print "Opening cache file<br />";
				$fp = fopen($cachename, "rb");
				$fcontents = fread($fp, filesize($cachename));
				fclose($fp);
				$dmsoundexlist = unserialize($fcontents);
				reset($dmsoundexlist);
				unset($fcontents);
				$cachecount = count($dmsoundexlist);
	//			print $cachecount." items read.<br />";
				return;
			}
			else {
	//			print "Cache file is length 0 or does not exist.<br />";
				$dmsoundexlist = array();
				$cachecount = 0;
				// clean up old cache
				$handle = opendir(INDEX_DIRECTORY);
				while (($file = readdir ($handle)) != false) {
					if ((substr($file, 0, 2) == "DM") && (substr($file, -4) == ".dat")) unlink(INDEX_DIRECTORY.$file);
				}
				closedir($handle);
				return;
			}
		}
		
		// Write the cache to disk after use. If nothing is added, just return.
		if ($option == "closecache") {
	//		print "Closing cache file<br />";
			if (count($dmsoundexlist) == $cachecount) return;
	//		print "Writing cache file, ".count($dmsoundexlist)." items, to ".$cachename."<br />";
			$fp = fopen($cachename, "wb");
			if ($fp) {
				fwrite($fp, serialize($dmsoundexlist));
				fclose($fp);
				return;
			}
		}
	
		// Hey, we don't want any CJK here!
		$o = ord($name[0]);
		if ($o >= 224 && $o <= 235) {
			return array();
		}
	
		// Check if in cache
		$name = Str2Upper($name);
		$name = trim($name);
		if (isset($dmsoundexlist[$name])) return $dmsoundexlist[$name];
		// Define the result array and set the first (empty) result
		$result = array();
		$result[0][0] = "";
		$rescount = 1;
		$nlen = strlen($name);
		$npos = 0;
		
		// Loop here through the characters of the name
		while($npos < $nlen) { 
			// Check, per length of characterstring, if it exists in the array.
			// Start from max to length of 1 character
			$code = array();
			for ($i=$maxchar; $i>=0; $i--) {
				// Only check if not read past the last character in the name
				if (($npos + $i) <= $nlen) {
					// See if the substring exists in the coding array
					$element = substr($name,$npos,$i);
					// If found, add the sets of results to the code array for the letterstring
					if (isset($dmcoding[$element])) {
						$dmcount = count($dmcoding[$element]);
						// Loop here through the codesets
						// first letter? Then store the first digit.
						if ($npos == 0) {
							// Loop through the sets of 3
							for ($k=0; $k<$dmcount/3; $k++) {
								$c = $dmcoding[$element][$k*3];
								// store all results, cleanup later
								$code[] = $c;
							}
							break;
						}
						// before a vowel? Then store the second digit
						// Check if the code for the next letter exists
						if ((isset($dmcoding[substr($name, $npos + $i + 1)]))) {
							// See if it's a vowel
							if ($dmcoding[substr($name, $npos + $i + 1)] == 0) {
								// Loop through the sets of 3
								for ($k=0; $k<$dmcount/3; $k++) {
									$c = $dmcoding[$element][$k*3+1];
									// store all results, cleanup later
									$code[] = $c;
								}
								break;
							}
						}
						// Do this in all other situations
						for ($k=0; $k<$dmcount/3; $k++) {
							$c = $dmcoding[$element][$k*3+2];
							// store all results, cleanup later
							$code[] = $c;
						}
						break;
					}
				}
			}
			// Store the results and multiply if more found
			if (isset($dmcoding[$element])) {
				// Add code to existing results
	
				// Extend the results array if more than one code is found
				for ($j=1; $j<count($code); $j++) {
					$rcnt = count($result);
					// Duplicate the array
					for ($k=0; $k<$rcnt; $k++) {
						$result[] = $result[$k];
					}
				}
	
				// Add the code to the existing strings
				// Repeat for every code...
				for ($j=0; $j<count($code); $j++) {
					// and add it to the appropriate block of array elements
					for ($k=0; $k<$rescount; $k++) {
						$result[$j * $rescount + $k][] = $code[$j];
					}
				}
				$rescount=count($result);
				$npos = $npos + strlen($element);
			}
			else {
				// The code was not found. Ignore it and continue.
				$npos = $npos + 1;
			}
		}
	
		// Kill the doubles and zero's in each result
		// Do this for every result
		for ($i=0, $max=count($result); $i<$max; $i++) {
			$j=1;
			$res = $result[$i][0];
			// and check every code in the result.
			// codes are stored separately in array elements, to keep
			// distinction between 6 and 66.
			while($j<count($result[$i])) {
				if (($result[$i][$j-1] != $result[$i][$j]) && ($result[$i][$j] != -1)) {
					$res .= $result[$i][$j];
				}
				$j++;
			}
			// Fill up to 6 digits and store back in the array
			$result[$i] = substr($res."000000", 0, 6);
		}
				
		// Kill the double results in the array
		if (count($result)>1) {
			sort($result);
			for ($i=0; $i<count($result)-1; $i++) {
				while ((isset($result[$i+1])) && ($result[$i] == $result[$i+1])) {
					unset($result[$i+1]);
					sort($result);
				}
			}
				
		}
	
		// Store in cache and return
		$dmsoundexlist[$name] = $result;
		return $result;			
	}
	
	// ToDo: test for #bytes in character and determine language. 
	// CJK = 3 bytes, 228 <= ord <= 233
	// Hebrew = 2 bytes, 214 <= ord <= 215
	public function HasChinese($name, $import = false) {
		global $LANGUAGE;
		
		if ((!GedcomConfig::$DISPLAY_PINYIN || $LANGUAGE == "chinese") && !$import) return false;
		$l = strlen($name);
		if ($l <3) return false;
		$max = $l - 3;
		for($i=0; $i <= $max; $i++) {
			$o = ord($name[$i]);
			if (($o >= 228 && $o <= 233) && ord($name[$i+1]) >= 128 && ord($name[$i+2]) >= 128) {
	//			print "Found chinese: ".ord($name[0])." ".ord($name[1])." ".ord($name[2])." ".$name."<br />";
				return true;
			}
		}
		return false;
	}
	
	public function HasCyrillic($name, $import=false) {
		global $LANGUAGE;
		
		if ((!GedcomConfig::$DISPLAY_TRANSLITERATE || $LANGUAGE == "russian") && !$import) return false;
		return (bool) preg_match('/[\p{Cyrillic}]/u', $name);
	}

	public function GetTransliterate($name, $import = false) {
	
		return transliterator_transliterate("Latin", $name);
	}
	
	public function GetPinYin($name, $import = false) {
		global $pinyin;
	
		if (!isset($pinyin) && $import) require_once(SystemConfig::$GM_BASE_DIRECTORY."includes/values/pinyin.php");
		
		$pyname = "";
		$pos1 = 0;
		$pos2 = 2;
		while ($pos2 < strlen($name)) {
			$char = substr($name, $pos1, $pos2 - $pos1 + 1);
			if (self::HasChinese($char, $import)) {
				$pyname .= (isset($pinyin[$char]) ? $pinyin[$char] : $char);
				$pos1 = $pos1 + 3;
				$pos2 = $pos2 + 3;
			}
			else {
				$pyname .= $char[0];
				$pos1 = $pos1 + 1;
				$pos2 = $pos2 + 1;
			}
		}
		if ($pos1 <= strlen($name)-1) $pyname .= substr($name,$pos1);
		$pyname = preg_replace("/".chr(239).chr(188).chr(140)."/", ",", $pyname);
		return $pyname;
	}	
	
	/**
	 * builds and returns sosa relationship name in the active language
	 *
	 * @param string $sosa sosa number
	 */
	public function GetSosaName($sosa) {
		global $LANGUAGE;
	
		if ($sosa<2) return "";
		$sosaname = "";
		$sosanr = floor($sosa/2);
		$gen = floor( log($sosanr) / log(2) );
	
		if ($LANGUAGE == "danish" || $LANGUAGE == "norwegian" || $LANGUAGE == "swedish") {
			$addname = "";
			$father = strtolower(GM_LANG_father);
			$mother = strtolower(GM_LANG_mother);
			$grand = "be".($LANGUAGE == "danish"?"dste":"ste");
			$great = "olde";
			$tip = "tip".($LANGUAGE == "danish"?"-":"p-");
			for($i = $gen; $i > 2; $i--) {
				$sosaname .= $tip;
			}
			if ($gen >= 2) $sosaname .= $great;
			if ($gen == 1) $sosaname .= $grand;
	
			for ($i=$gen; $i>0; $i--){
				if (!(floor($sosa/(pow(2,$i)))%2)) $addname .= $father;
				else $addname .= $mother;
				if (($gen%2 && !($i%2)) || (!($gen%2) && $i%2)) $addname .= "s ";
			}
			if ($LANGUAGE == "swedish") $sosaname = $addname;
			if (!($sosa%2)){
				$sosaname .= $father;
				if ($gen>0) $addname .= $father;
			}
			else {
				$sosaname .= $mother;
				if ($gen>0) $addname .= $mother;
			}
			$sosaname = Str2Upper(substr($sosaname, 0,1)).substr($sosaname,1);
			if ($LANGUAGE != "swedish") if (!empty($addname)) $sosaname .= ($gen>5?"<br />&nbsp;&nbsp;&nbsp;&nbsp;":"")." <small>(".$addname.")</small>";
		}
		if ($LANGUAGE == "dutch") {
			if ($gen & 256) $sosaname .= GM_LANG_sosa_11;
			if ($gen & 128) $sosaname .= GM_LANG_sosa_10;
			if ($gen & 64) $sosaname .= GM_LANG_sosa_9;
			if ($gen & 32) $sosaname .= GM_LANG_sosa_8;
			if ($gen & 16) $sosaname .= GM_LANG_sosa_7;
			if ($gen & 8) $sosaname .= GM_LANG_sosa_6;
			if ($gen & 4) $sosaname .= GM_LANG_sosa_5;
			$gen = $gen - floor($gen / 4)*4;
			if ($gen == 3) $sosaname .= GM_LANG_sosa_4.GM_LANG_sosa_3.GM_LANG_sosa_2;
			if ($gen == 2) $sosaname .= GM_LANG_sosa_3.GM_LANG_sosa_2;
			if ($gen == 1) $sosaname .= GM_LANG_sosa_2;
			if ($sosa%2) $sosaname .= strtolower(GM_LANG_mother);
			else $sosaname .= strtolower(GM_LANG_father);
			$sosaname = Str2Upper(substr($sosaname, 0,1)).substr($sosaname,1);
			return $sosaname;
		}
		if ($LANGUAGE == "english") {
			for($i = $gen; $i > 1; $i--) {
				$sosaname .= "Great-";
			}
			if ($gen >= 1) $sosaname .= "Grand";
			if (!($sosa%2)) $sosaname .= strtolower(GM_LANG_father);
			else $sosaname .= strtolower(GM_LANG_mother);
			$sosaname = Str2Upper(substr($sosaname, 0,1)).substr($sosaname,1);
		}
		if ($LANGUAGE == "finnish") {
			$father = Str2Lower(GM_LANG_father);
			$mother = Str2Lower(GM_LANG_mother);
	//		$father = "isä";
	//		$mother = "äiti";
	//		GM_LANG_sosa_2= "äidin";	//Grand (mother)
			for ($i=$gen; $i>0; $i--){
				if (!(floor($sosa/(pow(2,$i)))%2)) $sosaname .= $father."n";
	//			else $sosaname .= GM_LANG_sosa_2;
				else $sosaname .= substr($mother, 0,3)."din";
			}
			if (!($sosa%2)) $sosaname .= $father;
			else $sosaname .= $mother;
			if (substr($sosaname, 0,1)=="i") $sosaname = Str2Upper(substr($sosaname, 0,1)).substr($sosaname,1);
			else $sosaname = Str2Upper(substr($mother, 0,2)).substr($sosaname,2);
		}
		if ($LANGUAGE == "french") {
			if ($gen>4) $sosaname = "Arrière(x". ($gen-1) . ")-";
			else for($i = $gen; $i > 1; $i--) {
				$sosaname .= "Arrière-";
			}
			if ($gen >= 1) $sosaname .= "Grand-";
			if (!($sosa%2)) $sosaname .= GM_LANG_father;
			else $sosaname .= GM_LANG_mother;
			if ($gen == 1){
				if ($sosa<6) $sosaname .= " Pater";
				else $sosaname .= " Mater";
				$sosaname .= "nel";
				if ($sosa%2) $sosaname .= "le";
			}
		}
		if ($LANGUAGE == "german") {
			for($i = $gen; $i > 1; $i--) {
				$sosaname .= "Ur-";
			}
			if ($gen >= 1) $sosaname .= "Groß";
			if (!($sosa%2)) $sosaname .= strtolower(GM_LANG_father);
			else $sosaname .= strtolower(GM_LANG_mother);
			$sosaname = Str2Upper(substr($sosaname, 0,1)).substr($sosaname,1);
		}
		if ($LANGUAGE == "hebrew") {
			$addname = "";
			$father = GM_LANG_father;
			$mother = GM_LANG_mother;
			$greatf = GM_LANG_sosa_22;
			$greatm = GM_LANG_sosa_21;
			$of = GM_LANG_sosa_23;
			$grandfather = GM_LANG_sosa_4;
			$grandmother = GM_LANG_sosa_5;
	//		$father = "Aba";
	//		$mother = "Ima";
	//		$grandfather = "Saba";
	//		$grandmother = "Savta";
	//		$greatf = " raba";
	//		$greatm = " rabta";
	//		$of = " shel ";
			for ($i=$gen; $i>=0; $i--){
				if ($i==0){
					if (!($sosa%2)) $addname .= "f";
					else $addname .= "m";
				}
				else if (!(floor($sosa/(pow(2,$i)))%2)) $addname .= "f";
				else $addname .= "m";
				if ($i==0 || strlen($addname)==3){
					if (strlen($addname)==3){
						if (substr($addname, 2,1)=="f") $addname = $grandfather.$greatf;
						else $addname = $grandmother.$greatm;
					}
					else if (strlen($addname)==2){
						if (substr($addname, 1,1)=="f") $addname = $grandfather;
						else $addname = $grandmother;
					}
					else {
						if ($addname=="f") $addname = $father;
						else $addname = $mother;
					}
					$sosaname = $addname.($i<$gen-2?$of:"").$sosaname;
					$addname="";
				}
			}
		}
		if (!empty($sosaname)) return "$sosaname<!-- sosa=$sosa nr=$sosanr gen=$gen -->";
	
		if (defined("GM_LANG_sosa_".$sosa)) return constant("GM_LANG_sosa_".$sosa);
		else return (($sosa%2) ? GM_LANG_mother : GM_LANG_father) . " " . floor($sosa/2);
	}
	
}
?>