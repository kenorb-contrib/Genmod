<?php
/**
 * Privacy Functions
 *
 * See http://www.Genmod.net/privacy.php for more information on privacy in Genmod
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
 * @version $Id$
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
				$mardate = $family->marr_date;
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
/*			//-- check greatgrandchildren for dates
			$ggchildren = array();
			foreach($gchildren as $indexval => $gchild) {
				// For each family in which this person is a spouse...
				$numfams = preg_match_all("/1\s*FAMS\s*@(.*)@/", $gchild, $fmatch, PREG_SET_ORDER);
				for($j=0; $j<$numfams; $j++) {
					// Get the family record
					$famrec = FindFamilyRecord($fmatch[$j][1]);
	
					// Get the set of greatgrandchildren
					$ct = preg_match_all("/1 CHIL @(.*)@/", $famrec, $match, PREG_SET_ORDER);
					for($i=0; $i<$ct; $i++) {
						// Get each child's record
						$childrec = FindPersonRecord($match[$i][1]);
						$ggchildren[] = $childrec;
	
						// Check each greatgrandchild's dates
						$bt = preg_match_all("/\d DATE.*\s(\d{3,4})\s/", $childrec, $bmatch, PREG_SET_ORDER);
						for($h=0; $h<$bt; $h++) {
							$byear = $bmatch[$h][1];
							// if any greatgrandchild was born more than MAX_ALIVE_AGE-50 years ago assume the grandparent has died
							if (($cyear-$byear) > ($MAX_ALIVE_AGE-50)) {
								//print "greatgrandchild older than $MAX_ALIVE_AGE-50 (".$bmatch[$h][0].") year is $byear\n";
								return true;
							}
						}
					}
				}
			}
			//-- check greatgreatgrandchildren for dates
			foreach($ggchildren as $indexval => $ggchild) {
				// For each family in which this person is a spouse...
				$numfams = preg_match_all("/1\s*FAMS\s*@(.*)@/", $ggchild, $fmatch, PREG_SET_ORDER);
				for($j=0; $j<$numfams; $j++) {
					// Get the family record
					$famrec = FindFamilyRecord($fmatch[$j][1]);
	
					// Get the set of greatgreatgrandchildren
					$ct = preg_match_all("/1 CHIL @(.*)@/", $famrec, $match, PREG_SET_ORDER);
					for($i=0; $i<$ct; $i++) {
						// Get each child's record
						$childrec = FindPersonRecord($match[$i][1]);
	
						// Check each greatgreatgrandchild's dates
						$bt = preg_match_all("/\d DATE.*\s(\d{3,4})\s/", $childrec, $bmatch, PREG_SET_ORDER);
						for($h=0; $h<$bt; $h++) {
							$byear = $bmatch[$h][1];
							// if any greatgreatgrandchild was born more than MAX_ALIVE_AGE-70 years ago assume the grandparent has died
							if (($cyear-$byear) > ($MAX_ALIVE_AGE-70)) {
								//print "greatgreatgrandchild older than $MAX_ALIVE_AGE-70 (".$bmatch[$h][0].") year is $byear\n";
								return true;
							}
						}
					}
				}
			}
	*/			
			
		}
	//		global $ctu;
	//		if (!isset($ctu)) $ctu = 0;
	//		$ctu++;
	//		print $ctu." Undetermined: ".$pid."<br />";
		return false;
	}
	
	/**
	 * Check if details for a GEDCOM XRef ID should be shown
	 *
	 * This function uses the settings in the global variables above to determine if the current user
	 * has sufficient privileges to access the GEDCOM resource.
	 *
	 * @author	Genmod team
	 * @param	string $pid the GEDCOM XRef ID for the entity to check privacy settings for
	 * @param	string $type the GEDCOM type represented by the $pid.  This setting is used so that
	 *			different gedcom types can be handled slightly different. (ie. a source cannot be dead)
	 *			The possible values of $type are:
	 *			- "INDI" record is an individual
	 *			- "FAM" record is a family
	 *			- "SOUR" record is a source
	 *          - "REPO" record is a repository
	 * @return	boolean return true to show the persons details, return false to keep them private
	 */
	public static function displayDetailsByID($pid, $type = "INDI", $recursive=1, $checklinks=false) {
		global $USE_RELATIONSHIP_PRIVACY, $CHECK_MARRIAGE_RELATIONS, $MAX_RELATION_PATH_LENGTH, $GEDCOMID;
		global $global_facts, $person_privacy, $user_privacy, $HIDE_LIVE_PEOPLE, $SHOW_DEAD_PEOPLE, $MAX_ALIVE_AGE, $PRIVACY_BY_YEAR;
		global $PRIVACY_CHECKS, $PRIVACY_BY_RESN, $SHOW_SOURCES, $SHOW_LIVING_NAMES, $LINK_PRIVACY, $gm_user;
	
		//print "Check ".$pid." type ".$type." recursive ".$recursive." checklinks ".$checklinks."<br />";
		// Return the value from the cache, if set
		if (is_null(self::$pcache)) self::$pcache = array();
		if (isset(self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid])) {
			// print "Cache hit for pid: $pid, type: $type<br />";
			return self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid];
		}
		
		$ulevel = $gm_user->getUserAccessLevel();
	
		if (empty($pid)) return true;
		if (empty($type)) $type = "INDI";
	
		if (!isset($PRIVACY_CHECKS)) $PRIVACY_CHECKS = 1;
		else $PRIVACY_CHECKS++;
		// print "checking privacy for pid: $pid, type: $type<br />";
	
		//-- look for an Ancestral File level 1 RESN (restriction) tag. This overrules all other settings if it prevents showing data.
		if (isset($PRIVACY_BY_RESN) && ($PRIVACY_BY_RESN==true)) {
			if ($type == "INDI") $gedrec = FindPersonRecord($pid);
			else if ($type == "FAM") $gedrec = FindFamilyRecord($pid);
			else if ($type == "SOUR") $gedrec = FindSourceRecord($pid);
			else if ($type == "REPO") $gedrec = FindRepoRecord($pid);
			else if ($type == "OBJE") $gedrec = FindMediaRecord($pid);
			else if ($type == "NOTE") $gedrec = FindOtherRecord($pid, "", false, "NOTE");
			else $gedrec = FindGedcomRecord($pid);
			if (self::FactViewRestricted($pid, $gedrec, 1)) {
				self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = false;
				return false;
			}
		}
	
		// If a user is logged on, check for user related privacy first---------------------------------------------------------------
		if ($gm_user->username != "") {
			// Check user privacy for all users (hide/show)
			if (isset($user_privacy["all"][$pid])) {
				if ($user_privacy["all"][$pid] == 1) {
					self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = true;
					return true;
				}
				else {
					self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = false;
					return false;
				}
			}
			// Check user privacy for this user (hide/show)
			if (isset($user_privacy[$gm_user->username][$pid])) {
				if ($user_privacy[$gm_user->username][$pid] == 1) return true;
				else return false;
			}
			// Check person privacy (access level)
			if (isset($person_privacy[$pid])) {
				if ($person_privacy[$pid] >= $ulevel) {
					self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = true;
					return true;
				}
				else {
					self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = false;
					return false;
				}
			}
			
			// Check privacy by isdead status
			if ($type == "INDI") {
				$isdead = IsDeadId($pid);
				// The person is still hidden at this point and cannot be shown, either dead or alive.
				// Check the relation privacy. If within the range, people can be shown. 
				if ($USE_RELATIONSHIP_PRIVACY) {
					// If we don't know the user's gedcom ID, we cannot determine the relationship so no reason to show
					if ($gm_user->gedcomid[$GEDCOMID] == "") {
						self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = false;
						return false;
					}
						
					// If it's the user himself, we can show him
					if ($gm_user->gedcomid[$GEDCOMID]==$pid) {
						self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = true;
						return true;
					}
					
					// Determine if the person is within range
					$path_length = $MAX_RELATION_PATH_LENGTH;
					// print "get relation ".$gm_user->gedcomid[$GEDCOMID]." with ".$pid;
					$relationship = GetRelationship($gm_user->gedcomid[$GEDCOMID], $pid, $CHECK_MARRIAGE_RELATIONS, $path_length);
					// Only limit access to live people!
					if ($relationship == false && !$isdead) {
						self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = false;
						return false;
					}
					else {
						// A relation is found. Do not return anything, as general rules will apply in this case.
					}
				}
				
				// First check if the person is dead. If so, it can be shown, depending on the setting for dead people.
				if ($isdead && $SHOW_DEAD_PEOPLE >= $ulevel) {
					self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = true;
					return true;
				}
				
				// Alive people. If the user is allowed to see the person, show it.
				if (!$isdead && $HIDE_LIVE_PEOPLE >= $ulevel) {
					self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = true;
					return true;
				}
				
				// No options left to show the person. Return false.
				self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = false;
				return false;
			}
		}
		
		// This is the part that handles visitors. ---------------------------------------------------------------
		// No need to check user privacy
		//-- check the person privacy array for an exception (access level)
		// NOTE: This checks all record types! So no need to check later with fams, sources, etc.
		if (isset($person_privacy[$pid])) {
			if ($person_privacy[$pid] >= $ulevel) {
				self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = true;
				return true;
			}
			else {
				self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = false;
				return false;
			}
		}
		if ($type=="INDI") {
	//		 && $HIDE_LIVE_PEOPLE<getUserAccessLevel($username)) 
			//-- option to keep person living if they haven't been dead very long
			// This option assumes a person as living, if (max_alive_age = 120):
			// - Died within the last 95 years
			// - Married within the last 105 years
			// - Born within the last 120 years
			$dead = IsDeadId($pid);
			if ($PRIVACY_BY_YEAR) {
				$cyear = date("Y");
				$indirec = FindPersonRecord($pid);
				//-- check death record
				$deatrec = GetSubRecord(1, "1 DEAT", $indirec);
				$ct = preg_match("/2 DATE .*(\d\d\d\d).*/", $deatrec, $match);
				if ($ct>0) {
					$dyear = $match[1];
					if (($cyear-$dyear) <= $MAX_ALIVE_AGE-25) $dead = true;
				}
				//-- check marriage records
				$famids = FindSfamilyIds($pid);
				foreach($famids as $indexval => $famid) {
					$famrec = FindFamilyRecord($famid["famid"]);
					//-- check death record
					$marrrec = GetSubRecord(1, "1 MARR", $indirec);
					$ct = preg_match("/2 DATE .*(\d\d\d\d).*/", $marrrec, $match);
					if ($ct>0) {
						$myear = $match[1];
						if (($cyear-$myear) <= $MAX_ALIVE_AGE-15) $dead = true;
					}
				}
	
				//-- check birth record
				$birtrec = GetSubRecord(1, "1 BIRT", $indirec);
				$ct = preg_match("/2 DATE .*(\d\d\d\d).*/", $birtrec, $match);
				if ($ct>0) {
					$byear = $match[1];
					if (($cyear-$byear) <= $MAX_ALIVE_AGE) $dead = true;
				}
			}
			if (!$dead) {
				// The person is alive, let's see if we can show him
				if ($HIDE_LIVE_PEOPLE >= $ulevel) {
					self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = true;
					return true;
				}
				else {
					self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = false;
					return false;
				}
			}
			else {
				// The person is dead, let's see if we can show him
	//				print "DDbyID showlivingn: ".$SHOW_LIVING_NAMES."    useracc: ".getUserAccessLevel($username)."    ".$pid."<br />";
	//				if ($SHOW_LIVING_NAMES>getUserAccessLevel($username)) return true;
	//				else return false;
				if ($SHOW_DEAD_PEOPLE >= $ulevel) {
					self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = true;
					return true;
				}
				else {
					self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = false;
					return false;
				}
			}
		}
		
		// Now check the fams, for visitors AND for other users. Previously only INDI's are handled for users, not fams and other record types.
	    if ($type=="FAM") {
		    //-- check if we can display both parents. If not, the family will be hidden.
			$parents = FindParents($pid);
			if (!self::displayDetailsByID($parents["HUSB"])) {
				self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = false;
				return false;
			}
			if (!self::displayDetailsByID($parents["WIFE"])) {
				self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = false;
				return false;
			}
			self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = true;
			return true;
	    }
	    
	    // Check the sources. First check the general setting
	    if ($type=="SOUR") {
		    if ($SHOW_SOURCES >= $ulevel) {
			    $disp = true;
			    
			    // If we can show the source, see if any links to hidden records must prevent this.
			    // Only hide if a linked RECORD is hidden. Don't hide if a LINK is hidden
			    if ($LINK_PRIVACY && $checklinks) {
				    // This will prevent loops if MM points to SOUR vice versa. We only go one level deep.
				    $recursive--;
				    if ($recursive >=0) {
			    		$links = GetSourceLinks($pid, "", false);
			    		// print "Count of source links found: ".count($links)."<br />";
					    foreach($links as $key => $link) {
						    $disp = $disp && self::DisplayDetailsByID($link, IdType($link), $recursive, true);
						    if (!$disp) break;
					    }
				    }
				    $recursive++;
			    }
			    // We can show the source, and there are no links that prevent this
			    if ($disp) {
						self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = true;
					return true;
				}
				// The links prevent displaying the source
				else {
					self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = false;
					return false;
				}
			}
			// The sources setting prevents display, so hide!
			else {
				self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = false;
				return false;
			}
	    }
	    
	    // Check the repositories
	    if ($type=="REPO") {
		    // To do: see if any hidden sources exist that prevent the repository to be shown.
		    if ($SHOW_SOURCES >= $ulevel) return true;
			else return false;
	    }
	    
	    // Check the MM objects
	    if ($type=="OBJE") {
		    // Check if OBJE details are hidden by global or specific facts settings
		    if (self::ShowFactDetails("OBJE", $pid)) {
			    $disp = true;
			    // Check links to the MM record. Only hide if a linked RECORD is hidden. Don't hide if a LINK is hidden
			    if ($LINK_PRIVACY) {
				    $recursive--;
				    if ($recursive >=0) {
				    	$links = GetMediaLinks($pid);
					    foreach($links as $key => $link) {
						    $disp = self::DisplayDetailsByID($link, IdType($link), $recursive, true) && $disp;
						    if (!$disp) break;
				    	}
			    	}
				    $recursive++;
			    }
			    if ($disp) {
					self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = true;
				    return true;
			    }
			    else {
				    // we cannot show it because of hidden links
	   				self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = false;
					return false;
				}
			}
			// we cannot show the MM details
			else {
				self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = false;
				return false;
			}
	    }
	    // Check the Note objects
	    if ($type=="NOTE") {
		    // Check if NOTE details are hidden by global or specific facts settings
		    if (self::ShowFactDetails("NOTE", $pid)) {
			    $disp = true;
			    // Check links to the note record. Only hide if a linked RECORD is hidden. Don't hide if a LINK is hidden
			    if ($LINK_PRIVACY) {
				    $recursive--;
				    if ($recursive >=0) {
				    	$links = GetNoteLinks($pid, "", false);
					    foreach($links as $key => $link) {
						    $disp = $disp && self::DisplayDetailsByID($link[0], $link[1], $recursive, true);
						    if (!$disp) break;
				    	}
			    	}
				    $recursive++;
			    }
			    if ($disp) {
					self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = true;
				    return true;
			    }
			    else {
				    // we cannot show it because of hidden links
	   				self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = false;
					return false;
				}
			}
			// we cannot show the Note details
			else {
				self::$pcache[$GEDCOMID][$type][$recursive][$checklinks][$pid] = false;
				return false;
			}
	    }
	    return true;
	}
	
	/**
	 * Check if the name for a GEDCOM XRef ID should be shown
	 *
	 * This function uses the settings in the global variables above to determine if the current user
	 * has sufficient privileges to access the GEDCOM resource.  It first checks the
	 * <var>$SHOW_LIVING_NAMES</var> variable to see if names are shown to the public.  If they are
	 * then this function will always return true.  If the name is hidden then all relationships
	 * connected with the individual are also hidden such that arriving at this record results in a dead
	 * end.
	 *
	 * @param		string 	$pid 	the GEDCOM XRef ID for the entity to check privacy settings for
	 * @return	boolean 	return true to show the person's name, return false to keep it private
	 */
	public function showLivingNameByID($pid, $type="INDI", $gedrec = "") {
		global $SHOW_LIVING_NAMES, $person_privacy, $user_privacy, $gm_user, $USE_RELATIONSHIP_PRIVACY, $CHECK_MARRIAGE_RELATIONS, $GEDCOMID, $MAX_RELATION_PATH_LENGTH;
		
		// If we can show the details, we can also show the name
		if (self::displayDetailsByID($pid, $type)) return true;
		
		// If a pid is hidden or shown due to user privacy, the name is hidden or shown also
		if (!empty($gm_user->username)) {
			if (isset($user_privacy["all"][$pid])) {
				if ($user_privacy["all"][$pid] == 1) return true;
				else return false;
			}
			if (isset($user_privacy[$gm_user->username][$pid])) {
				if ($user_privacy[$gm_user->username][$pid] == 1) return true;
				else return false;
			}
		}
		
		
		// If a pid is hidden or shown due to person privacy, the name also is
		if (isset($person_privacy[$pid])) {
			if ($person_privacy[$pid] >= $gm_user->getUserAccessLevel()) return true;
			else return false;
		}
		
		// If RESN privacy on level 1 prevents the pid to be displayed, we also cannot show the name
		if (empty($gedrec)) $gedrec = FindGedcomRecord($pid); 
		if (self::FactViewRestricted($pid, $gedrec, 1)) return false;
		
		// Now split dead and alive people
		$isdead = IsDeadId($pid);
		// If dead, we follow DisplayDetailsByID
		// If alive, we check if the general rule allows displaying the name. If not, return false.
		if ($isdead) return false;
		else if ($SHOW_LIVING_NAMES < $gm_user->getUserAccessLevel()) return false;
		
		// Now we check if we must further narrow what can be seen
		// At this point we have a pid that cannot be displayed by detail and is alive,
		// and without relationship privacy the name would be shown.
		if ($USE_RELATIONSHIP_PRIVACY) {
			
			// If we don't know the user's gedcom ID, we cannot determine the relationship,
			// so we cannot further narrow what the user sees.
			// The same applies if we know the user, and he is viewing himself
			if (empty($gm_user->gedcomid[$GEDCOMID]) || $gm_user->gedcomid[$GEDCOMID]==$pid) return true;
			
			// Determine if the person is within range
			$path_length = $MAX_RELATION_PATH_LENGTH;
	//			if (isset($user->max_relation_path[$GEDCOMID]) && $user->max_relation_path[$GEDCOMID] > 0) $path_length = $user->max_relation_path[$GEDCOMID];
			$relationship = GetRelationship($gm_user->gedcomid[$GEDCOMID], $pid, $CHECK_MARRIAGE_RELATIONS, $path_length);
			// If we have a relation in range, we can display the name
			// if not in range, we can display the name of dead people
			if ($relationship != false) return true;
			else return false;
		}
		return true;
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
	
//		if ($fact=="SOUR") {
//			if ($SHOW_SOURCES >= $ulevel) return true;
//			else return false;
//	    }
	    
	//	if ($fact!="NAME") {
	//		$gedrec = FindGedcomRecord($pid);
	//		$disp = self::displayDetailsByID($pid, $type);
	//		return $disp;
	//	}
	//	else {
//		if ($fact == "NAME") {
//			if (!self::displayDetailsByID($pid, $type)) return self::showLivingNameById($pid);
//		}
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
	 * remove all private information from a gedcom record
	 *
	 * this function will analyze and gedcom record and privatize it by removing all private
	 * information that should be hidden from the user trying to access it.
	 * @param string $gedrec the raw gedcom record to privatize
	 * @return string the privatized gedcom record
	 */
	public function PrivatizeGedcom($gedrec) {
		global $gm_user;
		
		$gt = preg_match("/0 @(.+)@ (\w+)/", $gedrec, $gmatch);
		if ($gt > 0) {
			$gid = trim($gmatch[1]);
			$type = trim($gmatch[2]);
			$object = ConstructObject($gid, $type, "", $gedrec);
			$allsubs = GetAllSubrecords($gedrec, "", false, false, false);
			//-- check if the whole record is private
			if (!$object->disp) {
				//-- check if name should be private
				if ($type == "INDI") {
					if (!$object->disp_name) {
						$newrec = "0 @".$gid."@ INDI\r\n";
						$newrec .= "1 NAME " . GM_LANG_private . " /" . GM_LANG_private . "/" . "\r\n";
						$newrec .= "2 SURN " . GM_LANG_private . "\r\n";
						$newrec .= "2 GIVN " . GM_LANG_private . "\r\n";
					}
				}
				else if ($type == "SOUR") {
					$newrec = "0 @".$gid."@ SOUR\r\n";
					$newrec .= "1 TITL ".GM_LANG_private."\r\n";
				}
				else {
					$newrec = "0 @".$gid."@ $type\r\n";
					if ($type == "INDI") {
						$chil = GetSubRecord(1, "1 NAME", $gedrec);
						if (!empty($chil)) $newrec .= trim($chil)."\r\n";
						$chil = GetSubRecord(1, "1 FAMC", $gedrec);
						$i=1;
						while (!empty($chil)) {
							$newrec .= trim($chil)."\r\n";
							$i++;
							$chil = GetSubRecord(1, "1 FAMC", $gedrec, $i);
						}
						$chil = GetSubRecord(1, "1 FAMS", $gedrec);
						$i=1;
						while (!empty($chil)) {
							$newrec .= trim($chil)."\r\n";
							$i++;
							$chil = GetSubRecord(1, "1 FAMS", $gedrec, $i);
						}
					}
					else if ($type == "SOUR") {
						$chil = GetSubRecord(1, "1 ABBR", $gedrec);
						if (!empty($chil)) $newrec .= trim($chil)."\r\n";
						$chil = GetSubRecord(1, "1 TITL", $gedrec);
						if (!empty($chil)) $newrec .= trim($chil)."\r\n";
					}
					else if ($type == "FAM") {
						$chil = GetSubRecord(1, "1 HUSB", $gedrec);
						if (!empty($chil)) $newrec .= trim($chil)."\r\n";
						$chil = GetSubRecord(1, "1 WIFE", $gedrec);
						if (!empty($chil)) $newrec .= trim($chil)."\r\n";
						$chil = GetSubRecord(1, "1 CHIL", $gedrec);
						$i=1;
						while (!empty($chil)) {
							$newrec .= trim($chil)."\r\n";
							$i++;
							$chil = GetSubRecord(1, "1 CHIL", $gedrec, $i);
						}
					}
				}
				if ($type != "NOTE") $newrec .= "1 NOTE ".trim(GM_LANG_person_private)."\r\n";
				else $newrec .= "1 CONT ".trim(GM_LANG_person_private)."\r\n";
				return $newrec;
			}
			else {
				if ($type == "NOTE") {
					// Get the text on level 0
					$nt = preg_match("/0 @.+@ NOTE (.+)(\r\n|\n|\r)*/", $gedrec, $n1match);
					if ($nt>0) $text = $n1match[1];
					// And the additional conc/cont lines
					$text .= preg_replace("/<br \/>/", "", GetCont(1, $gedrec));
					$newrec = MakeCont("0 @".$gid."@ NOTE", $text);
				}
				else $newrec = "0 @".$gid."@ $type\r\n";
				//-- check all of the sub facts for access. As ->facts also contains changed factrecs, compare them with the original indirec.
				foreach($object->facts as $indexval => $fact) {
					if ($fact->disp && in_array($fact->factrec."\r\n", $allsubs)) $newrec .= $fact->factrec."\r\n";
				}
				return $newrec;
			}
		}
		else {
			//-- not a valid gedcom record
			return $gedrec;
		}
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
		global $GEDCOMID, $PRIVACY_BY_RESN, $gm_user;
		
		$ct = preg_match("/$level RESN (.*)/", $factrec, $match);
		if ($ct == 0) return false;
		if ($level == 1 && !$PRIVACY_BY_RESN) return false;
		$myindi = "";
		if (isset($gm_user->gedcomid[$GEDCOMID])) trim($myindi = $gm_user->gedcomid[$GEDCOMID]);
		if ($ct > 0) {
			$match[1] = strtolower(trim($match[1]));
			if ($match[1] == "none") return false;
			if ((($match[1] == "confidential") || ($match[1] == "locked")) && (($gm_user->userIsAdmin()) || ($gm_user->userGedcomAdmin()))) return false;
			if (($match[1] == "privacy") && (($gm_user->userIsAdmin()) || ($myindi == $pid) || ($gm_user->userGedcomAdmin()))) return false;
			if (IDType($pid) == "FAM"){
				$famrec = FindFamilyRecord($pid);
				$parents = FindParentsInRecord($famrec);
				if (($match[1] == "privacy") && (($gm_user->userIsAdmin()) || ($myindi == $parents["HUSB"]) || ($gm_user->userGedcomAdmin()))) return false;
				if (($match[1] == "privacy") && (($gm_user->userIsAdmin()) || ($myindi == $parents["WIFE"]) || ($gm_user->userGedcomAdmin()))) return false;
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
		global $GEDCOMID, $PRIVACY_BY_RESN, $gm_user;
		
		$ct = preg_match("/$level RESN (.*)/", $factrec, $match);
		if ($ct == 0) return false;
		if ($level == 1 && !$PRIVACY_BY_RESN) return false;
		$myindi = "";
		if (isset($gm_user->gedcomid[$GEDCOMID])) $myindi = trim($gm_user->gedcomid[$GEDCOMID]);
		$pid = trim($pid);
		if ($ct > 0) {
			$match[1] = strtolower(trim($match[1]));
			if ($match[1] == "none") return false;
			if ($match[1] == "locked") return false;
			if (($match[1] == "confidential") && (($gm_user->userIsAdmin()) || ($gm_user->userGedcomAdmin()))) return false;
			if (($match[1] == "privacy") && (($gm_user->userIsAdmin()) || ($myindi == $pid) || ($gm_user->userGedcomAdmin()))) return false;
			if (IDType($pid) == "FAM"){
				$family =& Family::GetInstance($pid); 
				if (($match[1] == "privacy") && (($gm_user->userIsAdmin()) || ($myindi == $family->wifeid) || ($gm_user->userGedcomAdmin()))) return false;
				if (($match[1] == "privacy") && (($gm_user->userIsAdmin()) || ($myindi == $family->husbid) || ($gm_user->userGedcomAdmin()))) return false;
			}
		}
		return true;
	}
	// used in old print_fact
	public function ShowRelaFact($factrec) {
		
		$fact = substr($factrec, 3, 4);
		$ct = preg_match_all("/\d ASSO @(.*)@/", $factrec, $match);
		if ($fact == "MARR") {
			foreach($match[1] as $key => $id) {
				if (IDType($id) == "FAM") return self::ShowFact("MARR", $id);
			}
		}
		else {
			$id = $match[1][0];
			return self::ShowFact($fact, $id);
		}
	}
	// used in old print_fact
	public function ShowRelaFactDetails($factrec) {
		
		$fact = substr($factrec, 3, 4);
		$ct = preg_match_all("/\d ASSO @(.*)@/", $factrec, $match);
		if ($fact == "MARR") {
			foreach($match[1] as $key => $id) {
				if (IDType($id) == "FAM") return self::ShowFactDetails("MARR", $id);
			}
		}
		else {
			$id = $match[1][0];
			return self::ShowFactDetails($fact, $id);
		}
	}

	public function ShowSourceFromAnyGed() {
		global $SHOW_SOURCES, $gm_user;
		global $PRIV_PUBLIC, $PRIV_USER, $PRIV_NONE, $PRIV_HIDE, $SHOW_SOURCES;
		
		$acclevel = $gm_user->getUserAccessLevel();
		$sql = "SELECT p_show_sources FROM ".TBLPREFIX."privacy";
		$res = NewQuery($sql);
		while($row = $res->FetchRow()) {
			// Fix for first time storage of privacy values (numeric instead of char)
			if (!is_numeric($row[0])) $row[0] = $$row[0];
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