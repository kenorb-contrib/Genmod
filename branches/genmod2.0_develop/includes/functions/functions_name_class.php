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
 * @version $Id$
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
			if ($rev) $hname = "@P.N. @N.N.";
			else $hname = "@N.N., @P.N.";
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
			if ($rev) $wname = "@P.N. @N.N.";
			else $wname = "@N.N., @P.N.";
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
		
		if (is_object($family->husb)) {
			if ($family->husb->disp_name) {
				$hname = self::GetSortableAddName($family->husb, $rev, $changes);
			}
			else $hname = GM_LANG_private;
		}
		else {
			if ($rev) $hname = "@P.N. @N.N.";
			else $hname = "@N.N., @P.N.";
		}
		if (is_object($family->wife)) {
			if ($family->wife->disp_name) {
				$wname = self::GetSortableAddName($family->wife, $rev, $changes);
			}
			else $wname = GM_LANG_private;
		}
		else {
			if ($rev) $wname = "@P.N. @N.N.";
			else $wname = "@N.N., @P.N.";
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
			if (HasChinese($orgname)){
				$romn_match[0] = GetPinYin($orgname);
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
			if (HasChinese($name, true)) $add = "";
			else $add = " ";
			if (empty($givenname)&&!empty($othername)) {
				$givenname = $othername;
				$othername = "";
			}
			if ($rev) {
				if (empty($givenname)) $givenname = "@P.N.";
				if ($NAME_REVERSE || HasChinese($name, true)) $name = $surname.$add.$givenname;
				else $name = $givenname.$add.$surname;
				if (!empty($othername)) $name .= $add.$othername;
			}
			else {
				if (empty($givenname)) $givenname = "@P.N.";
				$name = $surname;
				if (!empty($othername)) $name .= $add.$othername;
				if ($NAME_REVERSE || HasChinese($name, true)) $name .= $add.$givenname;
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
			if (HasChinese($name, true)) $add = "";
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
				$letter = GetFirstLetter($lname, $import);
				$letter = Str2Upper($letter);
				$fletter = GetFirstLetter($name, $import);
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
					$letter = GetFirstLetter($lname, $import);
					$letter = Str2Upper($letter);
					$fletter = GetFirstLetter($addname, $import);
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
						$letter = GetFirstLetter($lname, $import);
						$letter = Str2Upper($letter);
						$fletter = GetFirstLetter($marriedname, $import);
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
					$letter = GetFirstLetter($lname, $import);
					$letter = Str2Upper($letter);
					$fletter = GetFirstLetter($akaname, $import);
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
			if (HasChinese($names, true)) $names = preg_replace(array("~ /~","~/,~","~/~"), array("", ",", ""), $names);
			else $names = preg_replace(array("~ /~","~/,~","~/~"), array(" ", ",", " "), $names);
			$names = preg_replace(array("/@N.N.?/","/@P.N.?/"), array($NN,$PN), trim($names));
	
			if (GedcomConfig::$UNDERLINE_NAME_QUOTES) {
				if ($starred) $names = preg_replace("/\"(.+)\"/", "<span class=\"starredname\">$1</span>", $names);
				else $names = preg_replace("/\"(.+)\"/", "$1", $names);
			}
			//-- underline names with a * at the end
			//-- see this forum thread http://sourceforge.net/forum/forum.php?thread_id=1223099&forum_id=185165
			if ($starred) $names = preg_replace("/([^ ]+)\*/", "<span class=\"starredname\">$1</span>", $names);
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
						if ($starred) $names[$i] = preg_replace("/\"(.+)\"/", "<span class=\"starredname\">$1</span>", $names[$i]);
						else $names[$i] = preg_replace("/\"(.+)\"/", "$1", $names[$i]);
					}
					//-- underline names with a * at the end
					//-- see this forum thread http://sourceforge.net/forum/forum.php?thread_id=1223099&forum_id=185165
					if ($starred) $names[$i] = preg_replace("/([^ ]+)\*/", "<span class=\"starredname\">$1</span>", $names[$i]);
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
			$part = strstr($name, " ".substr(GedcomConfig::$NICK_DELIM, 0, 1));
			if ($part) {
				$name = str_replace($part, "", $name);
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
					if ($prevabbrev) $name = GetFirstLetter($words[$i]).".".$name;
					else {
						$name = GetFirstLetter($words[$i]).". ".$name;
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
			if (HasChinese($orgname, $import)){
				$name = GetPinYin($orgname, $import);
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
	
	
}
?>