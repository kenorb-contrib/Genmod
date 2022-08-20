<?php
/**
 * Privacy Functions
 *
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
 * @version $Id: functions_privacy_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 * @package Genmod
 * @subpackage Privacy
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
abstract class PrivacyFunctions {
	/**
	 * store relationship paths in a cache
	 *
	 * the <var>$NODE_CACHE</var> is an array of nodes that have been previously checked
	 * by the relationship calculator.  This cache greatly speed up the relationship privacy
	 * checking on charts as many relationships on charts are in the same relationship path.
	 *
	 * See the documentation for the GetRelationship() function in the functions.php file.
	 */
	public static $NODE_CACHE = array();
	private static $pcache = null;
	
	/**
	 * check if a person is dead
	 *
	 * this function will read a person's gedcom record and try to determine whether the person is
	 * dead or not.  It checks several parameters to determine death status in the following order:
	 * 1. a DEAT record returns dead
	 * 2. a BIRT record less than <var>$MAX_ALIVE_AGE</var> returns alive
	 * 3. Any date in the record that would make them older than <var>$MAX_ALIVE_AGE</var>
	 * 4. A date in the parents record that makes the parents older than <var>$MAX_ALIVE_AGE</var>+40
	 * 5. A marriage record with a date greater than <var>$MAX_ALIVE_AGE</var>-10
	 * 6. A date in the spouse record greater than <var>$MAX_ALIVE_AGE</var>
	 * 7. A date in the children's record that is greater than <var>$MAX_ALIVE_AGE</var>-10
	 * 8. A date in the grand childnren's record that is greater than <var>$MAX_ALIVE_AGE</var>-30
	 *
	 * This function should only be called once per individual.  In index mode this is called during
	 * the Gedcom import.  In MySQL mode this is called the first time the individual is accessed
	 * and then the database table is updated.
	 * @author Genmod Development Team
	 * @param string $indirec the raw gedcom record
	 * @return bool true if dead false if alive
	 */
	public function IsDead($indirec, $cyear="") {
		global $CHECK_CHILD_DATES, $MAX_ALIVE_AGE;
	
		$ct = preg_match("/0 @(.*)@ INDI/", $indirec, $match);
		if ($ct>0) {
			$pid = trim($match[1]);
		}
		else return false;
		
		if (empty($cyear)) $cyear = date("Y");
		
		// -- check for a death record
		$drec = GetSubRecord(1, "1 DEAT", $indirec);
		$deathrec = $drec;
		if (empty($deathrec)) {
			$brec = GetSubRecord(1, "1 BURI", $indirec);
			$deathrec = $brec;
		}
		if (empty($deathrec)) {
			$crec = GetSubRecord(1, "1 CREM", $indirec);
			$deathrec = $crec;
		 }
		if (!empty($deathrec)) {
			// any record is ok to determine the status
			if ($cyear==date("Y")) {
				$lines = preg_split("/\n/", $deathrec);
				if (count($lines)>1) return true;
				if (preg_match("/1 DEAT Y/", $deathrec)>0) return true;
			}
			else {
				// but we want a record with a date to check the year
				$ct = 0;
				if (!empty($drec)) $ct = preg_match("/\d DATE.*\s(\d{3,4})\s/", $drec, $match);
				if ($ct == 0 && !empty($brec)) $ct = preg_match("/\d DATE.*\s(\d{3,4})\s/", $brec, $match);
				if ($ct == 0 && !empty($crec)) $ct = preg_match("/\d DATE.*\s(\d{3,4})\s/", $crec, $match);
				if ($ct>0) {
					$dyear = $match[1];
					if ($dyear<$cyear) return true;
					else return false;
				}
			}
		}
	
		//-- if birthdate less than $MAX_ALIVE_AGE return false
		$birthrec = GetSubRecord(1, "1 BIRT", $indirec);
		if (!empty($birthrec)) {
			$ct = preg_match("/\d DATE.*\s(\d{3,4})\s/", $birthrec, $match);
			if ($ct>0) {
				$byear = $match[1];
				if (($cyear-$byear) < $MAX_ALIVE_AGE) {
					//print "found birth record less that $MAX_ALIVE_AGE\n";
					return false;
				}
			}
		}
	
		// If no death record than check all dates; the oldest one is the DOB
		$ct = preg_match_all("/\d DATE.*\s(\d{3,4})\s/", $indirec, $match, PREG_SET_ORDER);
		for($i=0; $i<$ct; $i++) {
			if (strstr($match[$i][0], "@#DHEBREW@")===false) {
				$byear = $match[$i][1];
				// If any date is prior to than MAX_ALIVE_AGE years ago assume they are dead
				if (($cyear-$byear) > $MAX_ALIVE_AGE) {
					//print "older than $MAX_ALIVE_AGE (".$match[$i][0].") year is $byear\n";
					return true;
				}
			}
		}
	
		// If we found no dates then check the dates of close relatives.
		if($CHECK_CHILD_DATES) {
			//-- check the parents for dates
			$numfams = preg_match_all("/1\s*FAMC\s*@(.*)@/", $indirec, $fmatch, PREG_SET_ORDER);
			for($j=0; $j<$numfams; $j++) {
				$family =& Family::GetInstance($fmatch[$j][1]);
				$parents = $family->parents;
				if (is_object($parents["HUSB"])) {
					$ct = preg_match_all("/\d DATE.*\s(\d{3,4})\s/", $parents["HUSB"]->gedrec, $match, PREG_SET_ORDER);
					for($i=0; $i<$ct; $i++) {
						$byear = $match[$i][1];
						// If any date is prior to than MAX_ALIVE_AGE years ago assume they are dead
						if (($cyear-$byear) > $MAX_ALIVE_AGE+40) {
							//print "father older than $MAX_ALIVE_AGE+40 (".$match[$i][0].") year is $byear\n";
							return true;
						}
					}
				}
				if (is_object($parents["WIFE"])) {
					$ct = preg_match_all("/\d DATE.*\s(\d{3,4})\s/", $parents["WIFE"]->gedrec, $match, PREG_SET_ORDER);
					for($i=0; $i<$ct; $i++) {
						$byear = $match[$i][1];
						// If any date is prior to than MAX_ALIVE_AGE years ago assume they are dead
						if (($cyear-$byear) > $MAX_ALIVE_AGE+40) {
							//print "mother older than $MAX_ALIVE_AGE+40 (".$match[$i][0].") year is $byear\n";
							return true;
						}
					}
				}
			}
			// For each family in which this person is a spouse...
			$numfams = preg_match_all("/1\s*FAMS\s*@(.*)@/", $indirec, $fmatch, PREG_SET_ORDER);
			for($j=0; $j<$numfams; $j++) {
				// Get the family record
				$family =& Family::GetInstance($fmatch[$j][1]);
				//-- check for marriage date
				$marrec = GetSubRecord(1, "1 MARR", $family->gedrec);
				$mardate = GetSubRecord(2, "2 DATE", $marrec);
				if ($mardate != "") {
					$bt = preg_match_all("/\d DATE.*\s(\d{3,4})\s/", $mardate, $bmatch, PREG_SET_ORDER);
					for($h=0; $h<$bt; $h++) {
						$byear = $bmatch[$h][1];
						// if marriage was more than MAX_ALIVE_AGE-10 years ago assume the person has died
						if (($cyear-$byear) > ($MAX_ALIVE_AGE-10)) {
							//print "marriage older than $MAX_ALIVE_AGE-10 (".$bmatch[$h][0].") year is $byear\n";
							return true;
						}
					}
				}
				//-- check spouse record for dates
				if ($family->husb_id != $pid) $spouse = $family->husb;
				else $spouse = $family->wife;
				if (is_object($spouse)) $spouserec = $spouse->gedrec;
				else $spouserec = "";
				// Check dates
				$bt = preg_match_all("/\d DATE.*\s(\d{3,4})\s/", $spouserec, $bmatch, PREG_SET_ORDER);
				for($h=0; $h<$bt; $h++) {
					$byear = $bmatch[$h][1];
					// if the spouse is > $MAX_ALIVE_AGE assume the individual is dead
					if (($cyear-$byear) > $MAX_ALIVE_AGE) {
						//print "spouse older than $MAX_ALIVE_AGE (".$bmatch[$h][0].") year is $byear\n";
						return true;
					}
				}
				// Get the set of children
				foreach ($family->children as $key => $child) {
					// Check each child's dates
					$bt = preg_match_all("/\d DATE.*\s(\d{3,4})\s/", $child->gedrec, $bmatch, PREG_SET_ORDER);
					for($h=0; $h<$bt; $h++) {
						$byear = $bmatch[$h][1];
						// if any child was born more than MAX_ALIVE_AGE-10 years ago assume the parent has died
						if (($cyear-$byear) > ($MAX_ALIVE_AGE-10)) {
							//print "child older than $MAX_ALIVE_AGE-10 (".$bmatch[$h][0].") year is $byear\n";
							return true;
						}
					}
				}
			}
			//-- check grandchildren for dates
			$numfams = preg_match_all("/1\s*FAMS\s*@(.*)@/", $indirec, $fmatch, PREG_SET_ORDER);
			for($j=0; $j<$numfams; $j++) {
				// Get the family record
				$family =& Family::GetInstance($fmatch[$j][1]);
				foreach($family->children as $indexval => $child) {
					// For each family in which this person is a spouse...
					$numfams = preg_match_all("/1\s*FAMS\s*@(.*)@/", $child->gedrec, $fmatch, PREG_SET_ORDER);
					for($j=0; $j<$numfams; $j++) {
						// Get the family record
						$childfam =& Family::GetInstance($fmatch[$j][1]);
						// Get the set of children
						foreach ($childfam->children as $key2 => $grandchild) {
							// Check each grandchild's dates
							$bt = preg_match_all("/\d DATE.*\s(\d{3,4})\s/", $grandchild->gedrec, $bmatch, PREG_SET_ORDER);
							for($h=0; $h<$bt; $h++) {
								$byear = $bmatch[$h][1];
								// if any grandchild was born more than MAX_ALIVE_AGE-30 years ago assume the grandparent has died
								if (($cyear-$byear) > ($MAX_ALIVE_AGE-30)) {
									//print "grandchild older than $MAX_ALIVE_AGE-30 (".$bmatch[$h][0].") year is $byear\n";
									return true;
								}
							}
						}
					}
				}
			}
		}
	//		global $ctu;
	//		if (!isset($ctu)) $ctu = 0;
	//		$ctu++;
	//		print $ctu." Undetermined: ".$pid."<br />";
		return false;
	}
	
	/**
	 * check if the given GEDCOM fact for the given individual, family, or source XRef ID should be shown
	 *
	 * This function uses the settings in the global variables above to determine if the current user
	 * has sufficient privileges to access the GEDCOM resource.  It first checks the $global_facts array
	 * for admin override settings for the fact.
	 *
	 * @param	string $fact the GEDCOM fact tag to check the privacy settings
	 * @param	string $pid the GEDCOM XRef ID for the entity to check privacy settings
	 * @return	boolean return true to show the fact, return false to keep it private
	 */
	public function showFact($fact, $pid, $type="") {
		global $LINK_PRIVACY;
		global $global_facts, $person_facts, $SHOW_SOURCES, $gm_user;
		static $ulevel;
		
		// print "Checking ".$fact." for ".$pid. " type ".$type." show_sources: ".$SHOW_SOURCES." userlevel: ".$ulevel."<br />";
		
		if (!isset($ulevel)) {
			$ulevel = $gm_user->getUserAccessLevel();
		}
	
		//-- first check the global facts array
		if (isset($global_facts[$fact]["show"])) {
			if ($global_facts[$fact]["show"] < $ulevel) return false;
		}
		//-- check the person facts array
		if (isset($person_facts[$pid][$fact]["show"])) {
			if ($person_facts[$pid][$fact]["show"] < $ulevel) return false;
		}
	
		return true;
	}
	
	/**
	 * check if the details of given GEDCOM fact for the given individual, family, or source XRef ID should be shown
	 *
	 * This function uses the settings in the global variables above to determine if the current user
	 * has sufficient privileges to access the GEDCOM resource.  It first checks the $global_facts array
	 * for admin override settings for the fact.
	 *
	 * @param	string $fact the GEDCOM fact tag to check the privacy settings
	 * @param	string $pid the GEDCOM XRef ID for the entity to check privacy settings
	 * @return	boolean return true to show the fact details, return false to keep it private
	 */
	public function showFactDetails($fact, $pid) {
		global $global_facts, $person_facts, $gm_user;
	
	
		// Handle the close relatives facts just as if they were normal facts
		$f = substr($fact, 0, 6);
		if ($f == "_BIRT_" || $f == "_DEAT_" && $f == "_MARR_") {
			$fact = substr($fact, 1, 4);
		}
		
		//-- if $PRIV_HIDE even admin users won't see everything
		if (isset($global_facts[$fact])) {
			//-- first check the global facts array
			if (isset($global_facts[$fact]["details"])) {
				if ($global_facts[$fact]["details"] < $gm_user->getUserAccessLevel()) return false;
			}
		}
			
		//-- check the person facts array
		if (isset($person_facts[$pid][$fact]["details"])) {
				if ($person_facts[$pid][$fact]["details"] < $gm_user->getUserAccessLevel()) return false;
		}
		return true;
	}
	
	/**
	 * Check fact record for editing restrictions
	 *
	 * Checks if the user is allowed to change fact information,
	 * based on the existence of the RESN tag in the fact record.
	 *
	 * @return int		Allowed or not allowed
	 */
	public function FactEditRestricted($pid, $factrec, $level=2) {
		global $PRIVACY_BY_RESN, $gm_user;
		
		$ct = preg_match("/$level RESN (.*)/", $factrec, $match);
		if ($ct == 0) return false;
		if ($level == 1 && !$PRIVACY_BY_RESN) return false;
		$myindi = "";
		if (isset($gm_user->gedcomid[GedcomConfig::$GEDCOMID])) $myindi = trim($gm_user->gedcomid[GedcomConfig::$GEDCOMID]);
		if ($ct > 0) {
			$match[1] = strtolower(trim($match[1]));
			if ($match[1] == "none") return false;
			if ((($match[1] == "confidential") || ($match[1] == "locked")) && (($gm_user->userIsAdmin()) || ($gm_user->userGedcomAdmin()))) return false;
			if (($match[1] == "privacy") && (($gm_user->userIsAdmin()) || ($myindi == $pid) || ($gm_user->userGedcomAdmin()))) return false;
			if (IDType($pid) == "FAM"){
				$family =& Family::GetInstance($pid); 
				if (($match[1] == "privacy") && (($gm_user->userIsAdmin()) || ($myindi == $family->wife_id) || ($gm_user->userGedcomAdmin()))) return false;
				if (($match[1] == "privacy") && (($gm_user->userIsAdmin()) || ($myindi == $family->husb_id) || ($gm_user->userGedcomAdmin()))) return false;
			}
		}
		return true;
	}
	
	/**
	 * Check fact record for viewing restrictions
	 *
	 * Checks if the user is allowed to view fact information,
	 * based on the existence of the RESN tag in the fact record.
	 *
	 * @return int		Allowed or not allowed
	 */
	public function FactViewRestricted($pid, $factrec, $level=2) {
		global $PRIVACY_BY_RESN, $gm_user;
		
		$ct = preg_match("/$level RESN (.*)/", $factrec, $match);
		if ($ct == 0) return false;
		if ($level == 1 && !$PRIVACY_BY_RESN) return false;
		
		$pid = trim($pid);
		$type = IDType($pid);
		$myindi = "";
		// Only for indi's and fams we restrict privacy to the user himself.
		// For other record types it's no use, so authenticated users (users with access priviledge) can see it.
		if ($type == "INDI" || $type == "FAM") {
			if (isset($gm_user->gedcomid[GedcomConfig::$GEDCOMID])) $myindi = trim($gm_user->gedcomid[GedcomConfig::$GEDCOMID]);
		}
		else if ($gm_user->userPrivAccess()) $myindi = $pid;
		if ($ct > 0) {
			$match[1] = strtolower(trim($match[1]));
			if ($match[1] == "none") return false;
			if ($match[1] == "locked") return false;
			if (($match[1] == "confidential") && ($gm_user->userIsAdmin() || $gm_user->userGedcomAdmin())) return false;
			if (($match[1] == "privacy") && (($gm_user->userIsAdmin()) || ($myindi == $pid) || ($gm_user->userGedcomAdmin()))) return false;
			if (IDType($pid) == "FAM"){
				$family =& Family::GetInstance($pid); 
				if (($match[1] == "privacy") && (($gm_user->userIsAdmin()) || ($myindi == $family->wife_id) || ($gm_user->userGedcomAdmin()))) return false;
				if (($match[1] == "privacy") && (($gm_user->userIsAdmin()) || ($myindi == $family->husb_id) || ($gm_user->userGedcomAdmin()))) return false;
			}
		}
		return true;
	}

	// Used in search to determine if the source search must be shown
	public function ShowSourceFromAnyGed() {
		global $SHOW_SOURCES, $gm_user;
		global $PRIV_PUBLIC, $PRIV_USER, $PRIV_NONE, $PRIV_HIDE, $SHOW_SOURCES;
		
		$acclevel = $gm_user->getUserAccessLevel();
		$sql = "SELECT p_show_sources FROM ".TBLPREFIX."privacy";
		$res = NewQuery($sql);
		while($row = $res->FetchRow()) {
			// Fix for first time storage of privacy values (numeric instead of char)
			if (!is_numeric($row[0])) $row[0] = ${$row[0]};
			if ($row["0"] >= $acclevel) {
				$res->FreeResult();
				return true;
			}
		}
		// also check the current setting, as it may not be in the database
		if ($SHOW_SOURCES >= $acclevel) return true;
		return false;
	}
	
	public function UpdateIsDead($indi) {
		
		$isdead = 0;
		$isdead = self::IsDead($indi->gedrec);
		if (empty($isdead)) $isdead = 0;
		$sql = "UPDATE ".TBLPREFIX."individuals SET i_isdead=".$isdead." WHERE i_id LIKE '".DbLayer::EscapeQuery($indi->xref)."' AND i_file='".DbLayer::EscapeQuery($indi->gedcomid)."'";
		$res = NewQuery($sql);
		return $isdead;
	}

}
?>