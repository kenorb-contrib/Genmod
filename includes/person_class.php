<?php
/**
 * Class file for a person
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
 * Page does not validate see line number 1109 -> 15 August 2005
 *
 * @package Genmod
 * @subpackage DataModel
 * @version $Id$
 */
require_once 'includes/gedcomrecord.php';
require_once 'includes/family_class.php';
require_once 'includes/functions_charts.php';

class Person extends GedcomRecord {
	var $sex = "U";
	var $disp = true;
	var $dispname = true;
	var $indifacts = array();
	var $indinew = false;
	var $indideleted = false;
	var $sourfacts = array();
	var $notefacts = array();
	var $globalfacts = array();
	var $mediafacts = array();
	var $changednames = array();
	var $facts_parsed = false;
	var $bdate = "";
	var $ddate = "";
	var $brec = "";
	var $newbrec = "";
	var $drec = "";
	var $newdrec = "";
	var $label = "";
	var $newgedrec = "";
	var $show_changes = false;
	var $highlightedimage = null;
	// var $file = "";
	var $sosamax = 7;
	var $fams = null;
	var $famc = null;
	var $spouseFamilies = null;
	var $childFamilies = null;
	var $close_relatives = false;
	var $media_count = null;
	var $actionlist = array();
	
	/**
	 * Constructor for person object
	 * @param string $gedrec	the raw individual gedcom record
	 */
	function Person($gedrec, $changed=false) {
		global $MAX_ALIVE_AGE, $GEDCOM, $controller, $show_changes, $Users, $Actions;
		
		parent::GedcomRecord($gedrec);

		// NOTE: See if we can show changes
		if ((!isset($show_changes) || $show_changes != "no") && $Users->UserCanEditOwn($Users->GetUserName(), $this->xref)) $this->show_changes = true;
		
		// NOTE: Check if person exists
		$this->exist = $this->pid_exist($this->xref);
//print "Person for ".$this->xref."<br />";
		// NOTE: Determine the persons gender
//		print $gedrec;
		$st = preg_match("/1 SEX (.*)/", $gedrec, $smatch);
		if ($st>0) $this->sex = trim($smatch[1]);
		if (empty($this->sex)) $this->sex = "U";
		
		// NOTE: Specify if this record is from a change
		if ($changed) $this->indinew = true;

		// NOTE: Specify if indi is deleted
		if ($this->show_changes && GetChangeData(true, $this->xref, true, "", "") ) {
//			if (GetChangeData(true, $this->xref, true, "", "")) {
				$rec = GetChangeData(false, $this->xref, true, "gedlines", "");
				if (empty($rec[$GEDCOM][$this->xref])) $this->indideleted = true;
				else if (!$changed) $this->newgedrec = $rec[$GEDCOM][$this->xref];
//			}
		}
		
		// NOTE: Get the persons name
		$this->name = PrintReady($this->getName());
		
		// NOTE: Get the additional names
		$this->addname = PrintReady($this->getAddName());

		// NOTE: Get the array of old/new names, for display on indipage
		$this->changednames = GetChangeNames($this->xref);
				
		// NOTE: Can this person details be shown?
		$this->disp = displayDetailsByID($this->xref);
		
		// NOTE: Can this name be displayed?
		$this->dispname = showLivingNameByID($this->xref);
		
		// NOTE: Get the birth and death record
		$this->_parseBirthDeath();
		
	}
	/**
	 * Check if the ID exists
	 * @return string
	 */
	function pid_exist($pid) {
		global $TBLPREFIX, $GEDCOMID, $show_changes, $controller, $indilist;
		
		if (isset($indilist[$pid]) || isset($indilist[JoinKey($pid, $GEDCOMID)])) return true;
		$sql = "SELECT i_id FROM ".$TBLPREFIX."individuals WHERE i_key='".JoinKey($pid,$GEDCOMID)."'";
		$res = NewQuery($sql);
		if (is_array($row = $res->FetchAssoc())) return true;
		else {
			if ($this->show_changes && GetChangeData(true, $pid, true, "", "INDI")) {
				$this->indinew = true;
				return true;
			}
			else {
				if ($this->show_changes && GetChangeData(true, $pid, true, "", "FAMC")) {
					$this->indinew = true;
					return true;
				}
			}
			return false;
		}
	}
	function getActionList() {
		global $Actions;
		
		if (is_object($Actions)) $this->actionlist = $Actions->GetActionListByID($this->xref);
			
	}
		
	/**
	 * get the persons name
	 * @return string
	 */
	function getName() {
		global $gm_lang;
		if (!$this->canDisplayName()) return $gm_lang["private"];
		if (!empty($this->newgedrec)) $name = PrintReady(GetPersonName($this->xref, $this->newgedrec));
		else $name = PrintReady(GetPersonName($this->xref, $this->gedrec));
		if (empty($name)) return $gm_lang["unknown"];
		return $name;
	}
	/**
	 * Check if an additional name exists for this person
	 * @return string
	 */
	function getAddName() {
		if (!$this->canDisplayName()) return "";
		if (!empty($this->newgedrec)) return PrintReady(GetAddPersonName($this->xref, $this->gedrec));
		else return PrintReady(GetAddPersonName($this->xref, $this->newgedrec));
	}
	/**
	 * Check if privacy options allow this record to be displayed
	 * @return boolean
	 */
	function canDisplayDetails() {
		return $this->disp;
	}
	/**
	 * Check if privacy options allow the display of the persons name
	 * @return boolean
	 */
	function canDisplayName() {
		return ($this->disp || $this->dispname);
	}
	/**
	 * get highlighted media
	 * @return array
	 */
	function findHighlightedMedia() {
		if (is_null($this->highlightedimage)) {
			$this->highlightedimage = FindHighlightedObject($this->xref, $this->gedrec);
		}
		return $this->highlightedimage;
	}
	
	/**
	 * get the number of level 1 media items 
	 * @return string
	 */
	function getNumberOfMedia() {
		
		if (!is_null($this->media_count)) return $this->media_count;
		
		if ($this->show_changes) $gedrec = $this->getchangedGedcomRecord();
		else $gedrec = $this->gedrec;
		$i = 1;
		do {
			$rec = getsubrecord(1, "1 OBJE", $gedrec, $i);
			if (!empty($rec)) $this->media_count++;
			$i++;
		}
		while (!empty($rec));
		return $this->media_count;
	}
	
	/**
	 * parse birth and death records
	 */
	function _parseBirthDeath() {
		global $MAX_ALIVE_AGE;
		$this->brec = GetSubRecord(1, "1 BIRT", $this->gedrec);
		if (empty($this->brec) && $this->indinew) $this->brec = RetrieveChangedFact($this->xref, "BIRT", "");
		$this->bdate = GetSubRecord(2, "2 DATE", $this->brec);
		$this->drec = GetSubRecord(1, "1 DEAT", $this->gedrec);
		if (empty($this->drec) && $this->indinew) $this->drec = RetrieveChangedFact($this->xref, "DEAT", "");
		$this->ddate = GetSubRecord(2, "2 DATE", $this->drec);
		if (empty($this->ddate) && !empty($this->bdate)) {
			$pdate=ParseDate(substr($this->bdate,6));
			if ($pdate[0]["year"]>0) $this->ddate = "2 DATE BEF ".($pdate[0]["year"]+$MAX_ALIVE_AGE);
		}
		if (empty($this->bdate) && !empty($this->ddate)) {
			$pdate=ParseDate(substr($this->ddate,6));
			if ($pdate[0]["year"]>0) $this->bdate = "2 DATE AFT ".($pdate[0]["year"]-$MAX_ALIVE_AGE);
		}
		$this->newbrec = RetrieveChangedFact($this->xref, "BIRT", $this->brec);
		$this->newdrec = RetrieveChangedFact($this->xref, "DEAT", $this->drec);
	}
	
	/**
	 * get the person's sex
	 * @return string 	return M, F, or U
	 */
	function getSex() {
		global $GEDCOM;
		if ($this->show_changes) {
			if (GetChangeData(true, $this->xref, true)) {
				$rec = GetChangeData(false, $this->xref, true);
				$gedrec = $rec[$GEDCOM][$this->xref];
				$st = preg_match("/1 SEX (.*)/", $gedrec, $smatch);
				if ($st>0) {
					$smatch[1] = trim($smatch[1]);
					if (empty($smatch[1])) return "U";
					else return trim($smatch[1]);
				}
			}
		}
		return $this->sex;
	}

	/**
	 * set a label for this person
	 * The label can be used when building a list of people
	 * to display the relationship between this person
	 * and the person listed on the page
	 * @param string $label
	 */
	function setLabel($label) {
		$this->label = $label;
	}
	
	function setFamLabel($fam, $label) {
		$this->label[$fam] = $label;
	}

	/**
	 * get the label for this person
	 * The label can be used when building a list of people
	 * to display the relationship between this person
	 * and the person listed on the page
	 * @return string
	 */
	function getLabel() {
		return $this->label;
	}
	
	/**
	 * get updated Person
	 * If there is an updated individual record in the gedcom file
	 * return a new person object for it
	 * @return Person
	 */
//	function &getUpdatedPerson() {
//		global $GEDCOM, $gm_username;
//		if ($this->changed) return null;
//		if (userCanEdit($gm_username)&&($this->disp)) {
//			if (GetChangeData(true, $this->xref, true)) {
//				$newrec = GetChangeData(false, $this->xref, true, "gedlines");
//				if (!empty($newrec)) {
//					$new = new Person($newrec);
//					return $new;
//				}
//			}
//		}
//		return null;
//	}
	/**
	 * Parse the facts from the individual record
	 */
	function parseFacts() {
		global $nonfacts, $show_changes, $controller;
		//-- only run this function once
		if ($this->facts_parsed) return;
		//-- don't run this function if privacy does not allow viewing of details
		if (!$this->canDisplayDetails()) return;
		$this->facts_parsed = true;
		// Don't apply privacy here, if only fact details are hidden the fact must be printed later
		$this->allindisubs = GetAllSubrecords($this->gedrec, "", false, false, false);
		$f = 0;	   // -- counter
		$o = 0;
		$g = 0;
		$m = 0;
		$count = array();
		$sexfound = false;
		foreach ($this->allindisubs as $key => $subrecord) {
			$ft = preg_match("/1\s(\w+)(.*)/", $subrecord, $match);
			if ($ft>0) {
				$fact = $match[1];
				$gid = trim(str_replace("@", "", $match[2]));
			}
			else {
				$fact = "";
				$gid = "";
			}
			$fact = trim($fact);
			if (!isset($count[$fact])) $count[$fact] = 1;
			else $count[$fact]++;
			// NOTE: handle special name fact case
			if ($fact=="NAME") {
				 $this->globalfacts[$g] = array($fact, $subrecord, $count[$fact]);
				 if ($this->indinew) $this->globalfacts[$g][] = "new";
				 $g++;
			}
			// NOTE: handle special source fact case
			else if ($fact=="SOUR") {
				 $this->sourfacts[$o] = array($fact, $subrecord, $count[$fact]);
				 if ($this->indinew) $this->sourfacts[$o][] = "new";
				 $o++;
			}
			// NOTE: handle special note fact case
			else if ($fact=="NOTE") {
				 $this->notefacts[$o] = array($fact, $subrecord, $count[$fact]);
				 if ($this->indinew) $this->notefacts[$o][] = "new";
				 $o++;
			}
			// NOTE: handle special media fact case
			else if ($fact=="OBJE") {
				 $this->mediafacts[$m] = array($fact, $subrecord, $count[$fact]);
				 if ($this->indinew) $this->mediafacts[$m][] = "new";
				 $m++;
			}
			// -- handle special sex case
			else if ($fact=="SEX") {
				 $this->globalfacts[$g] = array($fact, $subrecord, $count[$fact]);
				 if ($this->indinew) $this->globalfacts[$g][] = "new";
				 $g++;
				 $sexfound = true;
			}
			else if (!in_array($fact, $nonfacts)) {
				 $this->indifacts[$f]=array($fact, $subrecord, $count[$fact]);
				 if ($this->indinew) $this->indifacts[$f][] = "new";
				 $f++;
			}
		}
		//-- add a new sex fact if one was not found
		if (!$sexfound) {
			$this->globalfacts[$g] = array('new', "1 SEX U");
			$g++;
		}
		if ($this->show_changes && !$this->indinew) {
		$newrecs = RetrieveNewFacts($this->xref);
			foreach($newrecs as $key=> $newrec) {
				$ft = preg_match("/1\s(\w+)(.*)/", $newrec, $match);
				if ($ft>0) {
					$fact = $match[1];
				}
				else {
					$fact = "";
				}
				$fact = trim($fact);
				if (!isset($count[$fact])) $count[$fact] = 1;
				else $count[$fact]++;
				// NOTE: handle special name fact case
				if ($fact=="NAME") {
					$this->globalfacts[$g] = array($fact, $newrec, $count[$fact], 'new');
					$g++;
				}
				// NOTE: handle special source fact case
				else if ($fact=="SOUR") {
					$this->sourfacts[$o] = array($fact, $newrec, $count[$fact], 'new');
					$o++;
				}
				// NOTE: handle special note fact case
				else if ($fact=="NOTE") {
					$this->notefacts[$o] = array($fact, $newrec, $count[$fact], 'new');
					$o++;
				}
				// NOTE: handle special media fact case
				else if ($fact=="OBJE") {
					$this->mediafacts[$m] = array($fact, $newrec, $count[$fact], 'new');
					$m++;
				}
				// -- handle special sex case
				else if ($fact=="SEX") {
					$this->globalfacts[$g] = array($fact, $newrec, $count[$fact], 'new');
					$g++;
					$sexfound = true;
				}
				else if (!in_array($fact, $nonfacts)) {
					$this->indifacts[$f] = array($fact, $newrec, $count[$fact], 'new');
					$f++;
				}
			}
		}
	}

	/**
	 * add facts from the family record
	 */
	function add_family_facts() {
		global $GEDCOM, $nonfacts, $nonfamfacts, $controller;
		
		if (!$this->canDisplayDetails()) return;
		$indirec = $this->gedrec;
		if (!empty($this->xref) && $this->show_changes && GetChangeData(true, $this->xref, true, "")) {
			$rec = GetChangeData(false, $this->xref, true, "gedlines");
			$indirec = $rec[$GEDCOM][$this->xref];
		}
		if ($this->show_changes) $newfams = GetNewFams($this->xref);
		else $newfams = array();
		//-- Get the facts from the family with spouse (FAMS)
		$ct = preg_match_all("/1\s+FAMS\s+@(.*)@/", $indirec, $fmatch, PREG_SET_ORDER);
		$count_fams = array();
		for($j=0; $j<$ct; $j++) {
			$famid = $fmatch[$j][1];
			// If the family is (partly) private, we must not show facts
			if (DisplayDetailsByID($famid, "FAM")) {
				if ($this->show_changes && GetChangeData(true, $famid, true, "", "")) {
					$rec = GetChangeData(false, $famid, true, "gedlines", "");
					$famrec = $rec[$GEDCOM][$famid];
				}
				else {
					$famrec = FindFamilyRecord($famid);
				}
				$parents = FindParentsInRecord($famrec);
				if ($parents['HUSB']==$this->xref) $spouse=$parents['WIFE'];
				else $spouse=$parents['HUSB'];
				$this->allfamsubs = GetAllSubrecords($famrec, "", false, false, false);
				$count_facts = array();
				foreach ($this->allfamsubs as $key => $subrecord) {
					$ft = preg_match("/1\s(\w+)(.*)/", $subrecord, $match);
					if ($ft>0) $fact = $match[1];
					else $fact="";
					$fact = trim($fact);
					if (!isset($count_facts[$fact])) $count_facts[$fact] = 1;
					else $count_facts[$fact]++;
					// -- handle special source fact case
					if (($fact!="SOUR") && ($fact!="OBJE") && ($fact!="NOTE") && ($fact!="CHAN") && ($fact!="_UID") && ($fact!="RIN")) {
						if ((!in_array($fact, $nonfacts))&&(!in_array($fact, $nonfamfacts))) {
							$oldsub = $subrecord;
							$subrecord = trim($subrecord)."\r\n";
							$subrecord.="1 _GMS @$spouse@\r\n";
							$subrecord.="1 _GMFS @$famid@\r\n";
							if (in_array($famid, $newfams)) $this->indifacts[]=array($fact, $subrecord, $count_facts[$fact], "new");
							else $this->indifacts[]=array($fact, $subrecord, $count_facts[$fact]);
						}
					}
					else if ($fact=="OBJE") {
						$subrecord = trim($subrecord)."\r\n";
						$subrecord.="1 _GMS @$spouse@\r\n";
						$subrecord.="1 _GMFS @$famid@\r\n";
						// $this->mediafacts[]=array($fact, $subrecord, $count_facts[$fact]);
					}
				}
				if ($this->show_changes) {
					$newfacts = RetrieveNewFacts($famid);
					foreach ($newfacts as $key => $subrecord) {
						$ft = preg_match("/1\s(\w+)(.*)/", $subrecord, $match);
						if ($ft>0) $fact = $match[1];
						else $fact="";
						$fact = trim($fact);
						if (!isset($count_facts[$fact])) $count_facts[$fact] = 1;
						else $count_facts[$fact]++;
						// -- handle special source fact case
						if (($fact!="SOUR") && ($fact!="OBJE") && ($fact!="NOTE") && ($fact!="CHAN") && ($fact!="_UID") && ($fact!="RIN")) {
							if ((!in_array($fact, $nonfacts))&&(!in_array($fact, $nonfamfacts))) {
								$oldsub = $subrecord;
								$subrecord = trim($subrecord)."\r\n";
								$subrecord.="1 _GMS @$spouse@\r\n";
								$subrecord.="1 _GMFS @$famid@\r\n";
								$this->indifacts[]=array($fact, $subrecord, $count_facts[$fact], "new");
							}
						}
						else if ($fact=="OBJE") {
							$subrecord = trim($subrecord)."\r\n";
							$subrecord.="1 _GMS @$spouse@\r\n";
							$subrecord.="1 _GMFS @$famid@\r\n";
							// $this->mediafacts[]=array($fact, $subrecord, $count_facts[$fact], "new");
						}
					}
				}
				$this->add_spouse_facts($spouse, $famrec);
			}
			$this->add_children_facts($famid);
		}
		$this->add_parents_facts($this->xref);
		$this->add_historical_facts();
		$this->add_asso_facts($this->xref);
	}
	/**
	 * add parents events to individual facts array
	 *
	 * sosamax = sosa max for recursive call
	 * bdate = indi birth date record
	 * ddate = indi death date record
	 *
	 * @param string $pid	Gedcom id
	 * @param int $sosa		2=father 3=mother ...
	 * @return records added to indifacts array
	 */
	function add_parents_facts($pid, $sosa=1) {
		global $SHOW_RELATIVES_EVENTS, $gm_username, $GEDCOM, $controller;
		if (!$SHOW_RELATIVES_EVENTS) return;
		if ($sosa>$this->sosamax) return;
		if (empty($pid)) return;
//		print "Add parents facts for ".$pid."<br />";
		//-- find family as child
//		$famids = FindFamilyIds($pid);
//		if ($this->show_changes && (GetChangeData(true, $pid, true, "", ""))) {
//			$rec = GetChangeData(false, $pid, true, "gedlines", "");
//			$childrec = $rec[$GEDCOM][$pid];
//			$addfamids = FindFamilyIds($pid, $childrec);
//			$famids = array_merge($famids, $addfamids);
//		}
		if ($this->show_changes) $famids = FindFamilyIds($pid, "", true);
		else $famids = FindFamilyIds($pid);
		foreach ($famids as $indexval=>$ffamid) {
			$famid = $ffamid["famid"];
//			print $famid;
			$parents = FindParents($famid);
			$spouse = $parents["HUSB"];
			if (DisplayDetailsByID($parents["HUSB"], "INDI")) {
				// add father death
				if (!empty($spouse) && $this->show_changes && GetChangeData(true, $spouse, true)) {
					$rec = GetChangeData(false, $spouse, true, "gedlines");
					$spouserec = $rec[$GEDCOM][$spouse];
				}
				else {
					$spouserec = FindPersonRecord($spouse);
				}
				if ($sosa==1) $fact="_DEAT_FATH"; else $fact="_DEAT_GPAR";
				if ($spouse && strstr($SHOW_RELATIVES_EVENTS, $fact)) {
					$srec = GetSubRecord(1, "1 DEAT", $spouserec);
					$sdate = GetSubRecord(2, "2 DATE", $srec);
					if (CompareFacts($this->bdate, $sdate)<0 && CompareFacts($sdate, $this->ddate)<0) {
						$factrec = "1 ".$fact;
						$factrec .= "\n".trim($sdate);
						$factrec .= "\n2 ASSO @".$spouse."@";
						$factrec .= "\n3 RELA ".(GetSosaName($sosa*2));
						$this->indifacts[]=array("X$fact", $factrec, 0);
					}
				}
			}
			if ($sosa==1) $this->add_stepsiblings_facts($spouse, $famid); // stepsiblings with father
			$this->add_parents_facts($spouse, $sosa*2); // recursive call for father ancestors
			$spouse = $parents["WIFE"];
			if (DisplayDetailsByID($parents["WIFE"], "INDI")) {
				// add mother death
				if (!empty($spouse) && $this->show_changes && GetChangeData(true, $spouse, true, "", "INDI")) {
					$rec = GetChangeData(false, $spouse, true, "gedlines", "INDI");
					$spouserec = $rec[$GEDCOM][$spouse];
				}
				else {
					$spouserec = FindPersonRecord($spouse);
				}
				if ($sosa==1) $fact="_DEAT_MOTH"; else $fact="_DEAT_GPAR";
				if ($spouse and strstr($SHOW_RELATIVES_EVENTS, $fact)) {
					$srec = GetSubRecord(1, "1 DEAT", $spouserec);
					$sdate = GetSubRecord(2, "2 DATE", $srec);
					if (CompareFacts($this->bdate, $sdate)<0 and CompareFacts($sdate, $this->ddate)<0) {
						$factrec = "1 ".$fact;
						$factrec .= "\n".trim($sdate);
						$factrec .= "\n2 ASSO @".$spouse."@";
						$factrec .= "\n3 RELA ".GetSosaName($sosa*2+1);
						$this->indifacts[]=array("X$fact", $factrec, 0);
					}
				}
			}
			if ($sosa==1) $this->add_stepsiblings_facts($spouse, $famid); // stepsiblings with mother
			$this->add_parents_facts($spouse, $sosa*2+1); // recursive call for mother ancestors
			if ($sosa>3) return;
			if (DisplayDetailsByID($famid, "FAM")) {
				// add father/mother marriages
				if (is_array($parents)) {
					foreach ($parents as $indexval=>$spid) {
						if ($spid==$parents["HUSB"]) {
							$fact="_MARR_FATH";
							$rela="father";
						}
						else {
							$fact="_MARR_MOTH";
							$rela="mother";
						}
						if (strstr($SHOW_RELATIVES_EVENTS, $fact)) {
							if ($this->show_changes) $sfamids = FindSfamilyIds($spid, true);
							else $sfamids = FindSfamilyIds($spid);
							foreach ($sfamids as $indexval=>$fsfamid) {
								$sfamid = $fsfamid["famid"];
								if ($sfamid==$famid and $rela=="mother") continue; // show current family marriage only for father
								if ($this->show_changes && GetChangeData(true, $sfamid, true, "", "")) {
									$rec = GetChangeData(false, $sfamid, true, "gedlines", "");
									$childrec = $rec[$GEDCOM][$sfamid];
								}
								else {
									$childrec = FindFamilyRecord($sfamid);
								}
								$srec = GetSubRecord(1, "1 MARR", $childrec);
								$sdate = GetSubRecord(2, "2 DATE", $srec);
								if (CompareFacts($this->bdate, $sdate)<0 and CompareFacts($sdate, $this->ddate)<0) {
									$factrec = "1 ".$fact;
									$factrec .= "\n".trim($sdate);
									$factrec .= "\n2 ASSO @".$spid."@";
									$factrec .= "\n3 RELA ".$rela;
									$sparents = FindParents($sfamid);
									$spouse = $sparents["HUSB"];
									if ($spouse==$spid) $spouse = $sparents["WIFE"];
									if ($rela=="father") $rela2="stepmom";
									else $rela2="stepdad";
									if ($sfamid==$famid) $rela2="mother";
									if (!empty($spouse)) {
										$factrec .= "\n2 ASSO @".$spouse."@";
										$factrec .= "\n3 RELA ".$rela2;
									}
									$factrec .= "\n2 ASSO @".$sfamid."@";
									$factrec .= "\n3 RELA family";
									$this->indifacts[]=array("X$fact", $factrec, 0);
								}
							}
						}
					}
				}
			}
			//-- find siblings
//			print "Find children facts for ".$famid." ".$sosa."<br />";
			$this->add_children_facts($famid,$sosa, $pid);
		}
	}
	/**
	 * add children events to individual facts array
	 *
	 * bdate = indi birth date record
	 * ddate = indi death date record
	 *
	 * @param string $famid	Gedcom family id
	 * @param string $option Family level indicator
	 * @param string $except	Gedcom childid already processed
	 * @return records added to indifacts array
	 */
	function add_children_facts($famid, $option="", $except="") {
		global $SHOW_RELATIVES_EVENTS, $gm_username, $GEDCOM, $controller, $gm_lang;
		if (!$SHOW_RELATIVES_EVENTS) return;
		if (!DisplayDetailsByID($famid, "FAM")) return;
		if ($this->show_changes && (GetChangeData(true, $famid, true, ""))) {
			$rec = GetChangeData(false, $famid, true, "gedlines");
			$famrec = $rec[$GEDCOM][$famid];
		}
		else $famrec = FindFamilyRecord($famid);
//print "Add children facts for ".$famid."option: ".$option."<br />";		
		$num = preg_match_all("/1\s*CHIL\s*@(.*)@/", $famrec, $smatch, PREG_SET_ORDER);
		for($i=0; $i<$num; $i++) {
			$spid = $smatch[$i][1];
			// Check privacy here, as GM will insert own fact tags, so the original fact cannot be checked anymore.
			if ($spid!=$except && DisplayDetailsByID($spid)) {
				if ($this->show_changes && (GetChangeData(true, $spid, true, ""))) {
					$rec = GetChangeData(false, $spid, true, "gedlines");
					$childrec = $rec[$GEDCOM][$spid];
				}
				else $childrec = FindPersonRecord($spid);
//				print $option." ".$childrec."<br />";
				$srec = GetSubRecord(1, "1 SEX", $childrec);
				$sex = trim(substr($srec, 6));
				// children
				$rela="child";
				if ($sex=="F") $rela="daughter";
				if ($sex=="M") $rela="son";
				// grandchildren
				if ($option=="grand") {
					$rela="grandchild";
					if ($sex=="F") $rela="granddaughter";
					if ($sex=="M") $rela="grandson";
				}
				// stepsiblings
				if ($option=="step") {
					$rela="halfsibling";
					if ($sex=="F") $rela="halfsister";
					if ($sex=="M") $rela="halfbrother";
				}
				// siblings
				if ($option=="1") {
					$rela="sibling";
					if ($sex=="F") $rela="sister";
					if ($sex=="M") $rela="brother";
				}
				// uncles/aunts
				if ($option=="2" or $option=="3") {
					$rela="uncle/aunt";
					if ($sex=="F") $rela="aunt";
					if ($sex=="M") $rela="uncle";
				}
				// firstcousins
				if ($option=="first") {
					$rela="firstcousin";
					if ($sex=="F") $rela="femalecousin";
					if ($sex=="M") $rela="malecousin";
				}
				// add child birth
				$fact = "_BIRT_CHIL";
				if ($option=="grand") $fact = "_BIRT_GCHI";
				if ($option=="step") $fact = "_BIRT_HSIB";
				if ($option=="first") $fact = "_BIRT_COUS";
				if ($option=="1") $fact = "_BIRT_SIBL";
				if ($option=="2") $fact = "_BIRT_FSIB";
				if ($option=="3") $fact = "_BIRT_MSIB";
				if (strstr($SHOW_RELATIVES_EVENTS, $fact)) {
					$srec = GetSubRecord(1, "1 BIRT", $childrec);
					$sdate = GetSubRecord(2, "2 DATE", $srec);
				
					$newfact = RetrieveChangedFact($spid, "BIRT", $this->brec);
					if (!empty($newfact)) {
						$ibd = GetSubRecord(1, "1 BIRT", $newfact);
						$ibdate = GetSubRecord(2, "2 DATE", $ibd);
					}
					else $ibdate = $this->bdate;
				
					$newfact = RetrieveChangedFact($this->xref, "DEAT", $this->drec);
					if (!empty($newfact)) {
						$idd = GetSubRecord(1, "1 DEAT", $newfact);
						$iddate = GetSubRecord(2, "2 DATE", $idd);
					}
					else $iddate = $this->ddate;
				
					if (CompareFacts($ibdate, $sdate) < 0 and CompareFacts($sdate, $iddate) < 0) {
						$factrec = "1 ".$fact;
						$factrec .= "\n".trim($sdate);
						$factrec .= "\n2 ASSO @".$spid."@";
						$factrec .= "\n3 RELA ".$rela;
						$this->indifacts[]=array("X$fact", $factrec, 0);
					}
				}
				// add child death
				$fact = "_DEAT_CHIL";
				if ($option=="grand") $fact = "_DEAT_GCHI";
				if ($option=="step") $fact = "_DEAT_HSIB";
				if ($option=="first") $fact = "_DEAT_COUS";
				if ($option=="1") $fact = "_DEAT_SIBL";
				if ($option=="2") $fact = "_DEAT_FSIB";
				if ($option=="3") $fact = "_DEAT_MSIB";
				if (strstr($SHOW_RELATIVES_EVENTS, $fact)) {
					$srec = GetSubRecord(1, "1 DEAT", $childrec);
					$sdate = GetSubRecord(2, "2 DATE", $srec);
				
					$newfact = RetrieveChangedFact($this->xref, "BIRT", $this->brec);
					if (!empty($newfact)) {
						$ib = GetSubRecord(1, "1 BIRT", $newfact);
						$ibdate = GetSubRecord(2, "2 DATE", $ib);
					}						
					else $ibdate = $this->bdate;
				
					$newfact = RetrieveChangedFact($this->xref, "DEAT", $this->drec);
					if (!empty($newfact)) {
						$id = GetSubRecord(1, "1 DEAT", $newfact);
					$iddate = GetSubRecord(2, "2 DATE", $id);
					}
					else $iddate = $this->ddate;
					if (CompareFacts($ibdate, $sdate)<0 && CompareFacts($sdate, $iddate) < 0) {
						$factrec = "1 ".$fact;
						$factrec .= "\n".trim($sdate);
						$factrec .= "\n2 ASSO @".$spid."@";
						$factrec .= "\n3 RELA ".$rela;
						$this->indifacts[]=array("X$fact", $factrec, 0);
					}
				}
				// add child marriage
				$fact = "_MARR_CHIL";
				if ($option=="grand") $fact = "_MARR_GCHI";
				if ($option=="step") $fact = "_MARR_HSIB";
				if ($option=="first") $fact = "_MARR_COUS";
				if ($option=="1") $fact = "_MARR_SIBL";
				if ($option=="2") $fact = "_MARR_FSIB";
				if ($option=="3") $fact = "_MARR_MSIB";
				if (strstr($SHOW_RELATIVES_EVENTS, $fact)) {
					if ($this->show_changes) $sfamids = FindSfamilyIds($spid, true);
					else $sfamids = FindSfamilyIds($spid);
					foreach ($sfamids as $indexval=>$fsfamid) {
						$sfamid = $fsfamid["famid"];
						if (DisplayDetailsByID($sfamid, "FAM")) {
							$childrec = FindFamilyRecord($sfamid);
							if (empty($childrec) && $this->show_changes && (GetChangeData(true, $sfamid, true, ""))) {
								$rec = GetChangeData(false, $sfamid, true, "gedlines");
								$childrec = $rec[$GEDCOM][$sfamid];
							}
							$srec = GetSubRecord(1, "1 MARR", $childrec);
							$sdate = GetSubRecord(2, "2 DATE", $srec);
							if (CompareFacts($this->bdate, $sdate)<0 and CompareFacts($sdate, $this->ddate)<0) {
								$factrec = "1 ".$fact;
								$factrec .= "\n".trim($sdate);
								$factrec .= "\n2 ASSO @".$spid."@";
								$factrec .= "\n3 RELA ".$rela;
								$parents = FindParents($sfamid);
								$spouse = $parents["HUSB"];
								if ($spouse==$spid) $spouse = $parents["WIFE"];
								if ($rela=="son") $rela2="daughter-in-law";
								else if ($rela=="daughter") $rela2="son-in-law";
								else if ($rela=="brother" or $rela=="halfbrother") $rela2="sister-in-law";
								else if ($rela=="sister" or $rela=="halfsister") $rela2="brother-in-law";
								else if ($rela=="uncle") $rela2="aunt";
								else if ($rela=="aunt") $rela2="uncle";
								else if (strstr($rela, "cousin")) $rela2="cousin-in-law";
								else $rela2="spouse";
								if (!empty($spouse)) {
									$factrec .= "\n2 ASSO @".$spouse."@";
									$factrec .= "\n3 RELA ".$rela2;
								}
								$factrec .= "\n2 ASSO @".$sfamid."@";
								$factrec .= "\n3 RELA family";
								$arec = GetSubRecord(2, "2 ASSO @".$spid."@", $srec);
								if ($arec) $factrec .= "\n".$arec;
								$this->indifacts[]=array("X$fact", $factrec, 0);
							}
						}
					}
				}
				// add grand-children
				if ($option=="") {
					if ($this->show_changes) $famids = FindSfamilyIds($spid, true);
					else $famids = FindSfamilyIds($spid);
					foreach ($famids as $indexval=>$famid) {
						$this->add_children_facts($famid["famid"], "grand");
					}
				}
				// first cousins
				if ($option=="2" or $option=="3") {
					if ($this->show_changes) $famids = FindSfamilyIds($spid, true);
					else $famids = FindSfamilyIds($spid);
					foreach ($famids as $indexval=>$famid) {
						$this->add_children_facts($famid["famid"], "first");
					}
				}
			}
		}
	}
	
	/**
	 * add spouse events to individual facts array
	 *
	 * bdate = indi birth date record
	 * ddate = indi death date record
	 *
	 * @param string $spouse	Gedcom id
	 * @param string $famrec	family Gedcom record
	 * @param int $count The number of the fact in the record
	 * @return records added to indifacts array
	 */
	function add_spouse_facts($spouse, $famrec="") {
		global $SHOW_RELATIVES_EVENTS, $controller;
		// do not show if divorced
		if (strstr($famrec, "1 DIV")) return;
		// add spouse death
		$fact = "_DEAT_SPOU";
		if ($spouse && strstr($SHOW_RELATIVES_EVENTS, $fact)) {
			$srec=GetSubRecord(1, "1 DEAT", FindPersonRecord($spouse));
			$sdate=GetSubRecord(2, "2 DATE", $srec);
			if (CompareFacts($this->bdate, $sdate)<0 and CompareFacts($sdate, $this->ddate)<0) {
				$factrec = "1 ".$fact;
				$factrec .= "\n".trim($sdate);
				$factrec .= "\n2 ASSO @".$spouse."@";
				$factrec .= "\n3 RELA spouse";
			$this->indifacts[]=array("X$fact", $factrec, 0);
			}
		}
	}
	/**
	 * add step-siblings events to individual facts array
	 *
	 * @param string $spouse	Father or mother Gedcom id
	 * @param string $except	Gedcom famid already processed
	 * @return records added to indifacts array
	 */
	function add_stepsiblings_facts($spouse, $except="") {
		global $controller;
		if ($this->show_changes) $famids = FindSfamilyIds($spouse, true);
		else $famids = FindSfamilyIds($spouse);
		foreach ($famids as $indexval=>$famid) {
			// process children from all step families
			if ($famid["famid"]!=$except) $this->add_children_facts($famid["famid"], "step");
		}
	}
	/**
	 * add historical events to individual facts array
	 *
	 * @return records added to indifacts array
	 *
	 * Historical facts are imported from optional language file : histo.xx.php
	 * where xx is language code
	 * This file should contain records similar to :
	 *
	 *	$histo[]="1 EVEN\n2 TYPE History\n2 DATE 11 NOV 1918\n2 NOTE WW1 Armistice";
	 *	$histo[]="1 EVEN\n2 TYPE History\n2 DATE 8 MAY 1945\n2 NOTE WW2 Armistice";
	 * etc...
	 *
	 */
	function add_historical_facts() {
		global $GM_BASE_DIRECTORY, $LANGUAGE, $lang_short_cut;
		global $SHOW_RELATIVES_EVENTS;
		if (!$SHOW_RELATIVES_EVENTS) return;
		if (empty($this->bdate)) return;
		$histo=array();
		if (file_exists($GM_BASE_DIRECTORY."languages/histo.".$lang_short_cut[$LANGUAGE].".php")) {
			@include($GM_BASE_DIRECTORY."languages/histo.".$lang_short_cut[$LANGUAGE].".php");
		}
		foreach ($histo as $indexval=>$hrec) {
			$sdate = GetSubRecord(2, "2 DATE", $hrec);
			if (CompareFacts($this->bdate, $sdate)<0 && CompareFacts($sdate, $this->ddate)<0) {
				$this->indifacts[]=array(0, $hrec);
			}
		}
	}
	/**
	 * add events where pid is an ASSOciate
	 *
	 * @param string $pid	Gedcom id
	 * @return records added to indifacts array
	 *
	 */
	function add_asso_facts($pid) {
		global $factarray, $gm_lang, $controller;
		global $assolist, $GEDCOMID;
		if (!function_exists("GetAssoList")) return;
		GetAssoList("all", $pid);
		$apid = $pid."[".$GEDCOMID."]";
		// associates exist ?
		if (isset($assolist[$apid])) {
			// if so, print all indi's where the indi is associated to
			foreach($assolist[$apid] as $indexval => $asso) {
				$ct = preg_match("/0 @(.*)@ (\w+)/", $asso["gedcom"], $match);
				$rid = $match[1];
				$typ = $match[2];
				// search for matching fact
				if (DisplayDetailsByID($rid, $typ)) {
					for ($i=1; ; $i++) {
						$srec = GetSubRecord(1, "1 ", $asso["gedcom"], $i);
						if (empty($srec)) break;
						$arec = GetSubRecord(2, "2 ASSO @".$pid."@", $srec);
						if ($arec) {
							$fact = trim(substr($srec, 2, 5));
							$label = strip_tags($factarray[$fact]);
							$sdate = GetSubRecord(2, "2 DATE", $srec);
							// relationship ?
							$rrec = GetSubRecord(3, "3 RELA", $arec);
							$rela = trim(substr($rrec, 7));
							if (empty($rela)) $rela = "ASSO";
							if (isset($gm_lang[$rela])) $rela = $gm_lang[$rela];
							else if (isset($factarray[$rela])) $rela = $factarray[$rela];
							// add an event record
							$factrec = "1 EVEN\n2 TYPE ".$label."<br/>[".$rela."]";
							$factrec .= "\n".trim($sdate);
							if (trim($typ) == "FAM") {
								$famrec = FindFamilyRecord($rid);
								if ($famrec) {
									$parents = FindParentsInRecord($famrec);
									if ($parents["HUSB"]) $factrec .= "\n2 ASSO @".$parents["HUSB"]."@"; //\n3 RELA ".$factarray[$fact];
									if ($parents["WIFE"]) $factrec .= "\n2 ASSO @".$parents["WIFE"]."@"; //\n3 RELA ".$factarray[$fact];
								}
							}
							else $factrec .= "\n2 ASSO @".$rid."@\n3 RELA ".$label;
							//$factrec .= "\n3 NOTE ".$rela;
							$factrec .= "\n2 ASSO @".$pid."@\n3 RELA ".$rela;
							$this->indifacts[] = array("X_$fact", $factrec, 0);
						}
					}
				}
			}
		}
	}
	
	function getParentFamily($pid) {
		global $gm_lang, $controller, $gm_username, $show_changes, $GEDCOM;
		// $child_families = find_families_in_record($this->gedrec, "FAMC");
		if ($this->show_changes) $child_families = FindFamilyIds($pid, "", true);
		else $child_families = FindFamilyIds($pid);
		if (count($child_families) > 0) {
			$this->close_relatives = true;
			if ($this->show_changes && GetChangeData(true, $pid, true, "", "")) {
				$rec = GetChangeData(false, $pid, true, "gedlines", "");
				$childrec = $rec[$GEDCOM][$pid];
			}
			else $childrec = $this->gedrec;
			foreach ($child_families as $id => $ffamid) {
				$famid = $ffamid["famid"];
				// NOTE: Retrieve the family record
				if ($this->show_changes && (GetChangeData(true, $famid, true, "", ""))) {
					$rec = GetChangeData(false, $famid, true, "gedlines", "");
					$famrec = $rec[$GEDCOM][$famid];
				}
				else $famrec = FindFamilyRecord($famid);
				// Get the type of family relationship
				$famlink = GetSubRecord(1, "1 FAMC @".$famid."@", $childrec);
				$ft = preg_match("/2 PEDI (.*)/", $famlink, $fmatch);
				if ($ft>0 && $fmatch[1] != "birth") $rela = trim($fmatch[1]);
				else $rela = "";
				$prela = $rela;

				// NOTE: Get an array of the parents ID's
				$this->parents[$famid] = FindParentsInRecord($famrec);
				// NOTE: Create parents and set their label
				foreach ($this->parents[$famid] as $type => $parentid) {
					if (!empty($parentid)) {
						if ($this->show_changes && (GetChangeData(true, $parentid, true, "", "INDI"))) {
							$rec = GetChangeData(false, $parentid, true, "gedlines", "INDI");
							$parentrec = $rec[$GEDCOM][$parentid];
							$this->parents[$famid][$type] = new Person($parentrec, true);
						}
						else $this->parents[$famid][$type] = new Person(FindGedcomRecord($parentid));
						// NOTE: Family with Parents
						$sex = $this->parents[$famid][$type]->getSex();
						$label = $this->parents[$famid][$type]->gender($sex, $rela."parents");
						if ($this->parents[$famid][$type]->getxref()==$this->xref) $label = "<img src=\"images/selected.png\" alt=\"\" />";
						$this->parents[$famid][$type]->setLabel($label);
					}
				}
				
				// NOTE: Set the family label, also if no parents exist.
				if (isset($gm_lang["as_".$rela."child"])) $this->parents[$famid]["label"] = $gm_lang["as_".$rela."child"];
				else $this->parents[$famid]["label"] = $rela;
				
				// NOTE: Set the family ID
				$this->parents[$famid]["FAMID"] = $famid;
				// NOTE: Get an array of the children ID's
				$siblings = FindChildrenInRecord($famrec);
				if ($this->show_changes && (GetChangeData(true, $famid, true, "", "CHIL"))) {
					$rec = GetChangeData(false, $famid, true, "gedlines", "CHIL");
					$addsibl = $rec[$GEDCOM][$famid];
					$addsiblings = FindChildrenInRecord($addsibl);
					$siblings = array_merge($siblings, $addsiblings);
				}
				// NOTE: Create the children and set their label
				foreach ($siblings as $id => $sibid) {
					if ($this->show_changes && (GetChangeData(true, $sibid, true, "", ""))) {
						$rec = GetChangeData(false, $sibid, true, "gedlines", "");
						$sibrec = $rec[$GEDCOM][$sibid];
						if(!isset($this->siblings[$sibid])) $this->siblings[$sibid] = new Person($sibrec, true);
					}
					else if (!isset($this->siblings[$sibid])) $this->siblings[$sibid] = new Person(FindGedcomRecord($sibid));
//					NOTE: we must get the relation of the person to the siblings, not of the siblings to the family!
					if ($prela == "") {
						$famlink = GetSubRecord(1, "1 FAMC @".$famid."@", $this->siblings[$sibid]->gedrec);
						$ft = preg_match("/2 PEDI (.*)/", $famlink, $fmatch);
						if ($ft>0 && $fmatch[1] != "birth") $rela = trim($fmatch[1]);
						else $rela = "";
					}
					$sex = $this->siblings[$sibid]->getSex();
					$label = $this->siblings[$sibid]->gender($sex, $rela."parentskids");
					if ($this->siblings[$sibid]->getxref()==$this->xref) $label = "<img src=\"images/selected.png\" alt=\"\" />";
					$this->siblings[$sibid]->setFamLabel($famid, $label);
				}
			}
		}
	}
	
	function getSpouseFamily($pid) {
		global $gm_lang, $controller, $show_changes, $GEDCOM, $gm_username, $Users;
		// NOTE: Get an array of the spouse ID's
		if ((!isset($show_changes) ||$show_changes != "no") && $Users->UserCanEdit($Users->GetUserName())) $spouse_families = FindSfamilyIds($pid, true);
		else $spouse_families = FindSfamilyIds($pid);
		// NOTE: Create the spouses and set their label
		foreach ($spouse_families as $id => $ffamid) {
			$famid = $ffamid["famid"];
			if (DisplayDetailsByID($famid, "FAM")) {
				$this->close_relatives = true;
				// NOTE: Retrieve the family record
				$famrec = FindFamilyRecord($famid);
				if ($this->show_changes && GetChangeData(true, $famid, true)) {
					$rec = GetChangeData(false, $famid, true, "gedlines", "");
					if (isset($rec[$GEDCOM][$famid])) $famrec = $rec[$GEDCOM][$famid];
				}
				// NOTE: Retrieve the parents
				$this->spouses[$famid]["parents"] = FindParentsInRecord($famrec);
				
				// NOTE: Get the label for the family
				$this->spouses[$famid]["label"] = $gm_lang["family_with"] . " ";
				// Check if the husband is equal to the person we are displaying
				// If so, add the wife to the tag
				if ($this->xref == $this->spouses[$famid]["parents"]["HUSB"]) {
					$name = GetPersonName($this->spouses[$famid]["parents"]["WIFE"]);
	//				if (empty($name) && $this->show_changes && GetChangeData(true, $this->spouses[$famid]["parents"]["WIFE"], true, "", "")) 
					if ($this->show_changes && GetChangeData(true, $this->spouses[$famid]["parents"]["WIFE"], true, "", "")) {
						$rec = GetChangeData(false, $this->spouses[$famid]["parents"]["WIFE"], true, "gedlines", "");
						if (isset($rec[$GEDCOM][$this->spouses[$famid]["parents"]["WIFE"]])) $name = GetPersonName($this->spouses[$famid]["parents"]["WIFE"], $rec[$GEDCOM][$this->spouses[$famid]["parents"]["WIFE"]]);
					}
					if (HasChinese($name)) $name = PrintReady($name." (".GetAddPersonName($this->spouses[$famid]["parents"]["WIFE"]).")");
					if (ShowLivingNameByID($this->spouses[$famid]["parents"]["WIFE"])) $this->spouses[$famid]["label"] .= $name;
					else $this->spouses[$famid]["label"] .= $gm_lang["private"];
				}
				// NOTE: We are not viewing the husband, so add the husbands label
				else {
					$name = GetPersonName($this->spouses[$famid]["parents"]["HUSB"]);
	//				if (empty($name) && $this->show_changes && GetChangeData(true, $this->spouses[$famid]["parents"]["HUSB"], true, "", "")) 
					if ($this->show_changes && GetChangeData(true, $this->spouses[$famid]["parents"]["HUSB"], true, "", "")) {
						$rec = GetChangeData(false, $this->spouses[$famid]["parents"]["HUSB"], true, "gedlines", "");
						if (isset($rec[$GEDCOM][$this->spouses[$famid]["parents"]["HUSB"]])) $name = GetPersonName($this->spouses[$famid]["parents"]["HUSB"], $rec[$GEDCOM][$this->spouses[$famid]["parents"]["HUSB"]]);
					}
					if (HasChinese($name)) $name = PrintReady($name." (".GetAddPersonName($this->spouses[$famid]["parents"]["HUSB"]).")");
					if (ShowLivingNameByID($this->spouses[$famid]["parents"]["HUSB"])) $this->spouses[$famid]["label"] .= $name;
					else $this->spouses[$famid]["label"] .= $gm_lang["private"];
				}
				
				// NOTE: Create the parents and set their label
				foreach ($this->spouses[$famid]["parents"] as $type => $parentid) {
					$parentged = FindGedcomRecord($parentid);
					if (empty($parentged) && $this->show_changes && GetChangeData(true, $parentid, true)) {
						$rec = GetChangeData(false, $parentid, true, "gedlines", "");
						if (isset($rec[$GEDCOM][$parentid])) $parentged = $rec[$GEDCOM][$parentid];
					}
					if (!empty($parentged)) {
						$this->spouses[$famid]["parents"][$type] = new Person($parentged);
						$sex = $this->spouses[$famid]["parents"][$type]->getSex();
						$divorced = "";
						$married = "";
						$married = GetSubrecord(1, "MARR", $famrec);
						if ($married) {
							$this->spouses[$famid]["marrdate"] = GetGedcomValue("DATE", 2, $married, "", false);
							$this->spouses[$famid]["marrplac"] = GetGedcomValue("PLAC", 2, $married);
						}
						$divorced = GetSubRecord(1, "DIV", $famrec);
						$label = $this->spouses[$famid]["parents"][$type]->gender($sex, "spouseparents", $divorced, $married);
						if ($this->spouses[$famid]["parents"][$type]->getxref()==$this->xref) $label = "<img src=\"images/selected.png\" alt=\"\" />";
						$this->spouses[$famid]["parents"][$type]->setLabel($label);
					}
				}
				// NOTE: Create the children and set their label
				$kids = FindChildrenInRecord($famrec, $this->xref);
				if ($this->show_changes && GetChangeData(true, $famid, true)) {
					$rec = GetChangeData(false, $famid, true, "gedlines", "CHIL");
						if (isset($rec[$GEDCOM][$famid])) {
						$newkids = $rec[$GEDCOM][$famid];
						$kidsn = FindChildrenInRecord($newkids, $this->xref);
						$kids = array_merge($kids, $kidsn);
					}
				}
				foreach ($kids as $type => $kidid) {
					if ($this->show_changes && GetChangeData(true, $kidid, true, "", "")) {
						$rec = GetChangeData(false, $kidid, "gedlines", "");
						$kidrec = $rec[$GEDCOM][$kidid];
						$this->spouses[$famid]["kids"][$kidid] = new Person($kidrec, true);
					}
					else $this->spouses[$famid]["kids"][$kidid] = new Person(FindGedcomRecord($kidid));
					$sex = $this->spouses[$famid]["kids"][$kidid]->getSex();
					// Get the type of family relationship
					$famlink = GetSubRecord(1, "1 FAMC @".$famid."@", $this->spouses[$famid]["kids"][$kidid]->gedrec);
					$ft = preg_match("/2 PEDI (.*)/", $famlink, $fmatch);
					if ($ft>0 && $fmatch[1] != "birth") $rela = trim($fmatch[1]);
					else $rela = "";
					$label = $this->spouses[$famid]["kids"][$kidid]->gender($sex, $rela."spousekids");
					if ($this->spouses[$famid]["kids"][$kidid]->getxref()==$this->xref) $label = "<img src=\"images/selected.png\" alt=\"\" />";
					$this->spouses[$famid]["kids"][$kidid]->setLabel($label);
				}
			}
		}
	}
	
	function getParentOtherFamily($pid) {
		global $gm_lang, $controller, $show_changes, $GEDCOM, $gm_username;
			// NOTE: Get the parents other families
			// NOTE: Get the fathers families only if they have kids
			if (isset($this->parents)) {
				foreach ($this->parents as $famid => $family) {
					if (isset($family["HUSB"]->xref)) {
						if ($this->show_changes) $father_families = FindSfamilyIds($family["HUSB"]->xref, true);
						else $father_families = FindSfamilyIds($family["HUSB"]->xref);
						$newkids = array();
						foreach ($father_families as $key => $ffather_famid) {
							$father_famid = $ffather_famid["famid"];
							if ($father_famid != $famid) {
								$famrec = FindFamilyRecord($father_famid);
								$kids = FindChildrenInRecord($famrec, $father_famid);
								if (count($kids) > 0) {
									foreach ($kids as $kidkey => $kidid) {
										$newkids[$kidid] = new Person(FindGedcomRecord($kidid));
										$sex = $newkids[$kidid]->getSex();
										$label = $newkids[$kidid]->gender($sex, "halfkids");
										if ($newkids[$kidid]->getxref()==$this->xref) $label = "<img src=\"images/selected.png\" alt=\"\" />";
										$newkids[$kidid]->setLabel($label);
									}
									$this->father_family[$father_famid]["kids"] = $newkids;
									$parents = FindParentsInRecord($famrec);
									foreach ($parents as $type => $parentid) {
										$this->father_family[$father_famid][$type] = new Person(FindGedcomRecord($parentid));
									}
									// NOTE: Get the label for the family
									$this->father_family[$father_famid]["label"] = $gm_lang["fathers_family_with"] . " ";
									// Check if the husband is equal to the person we are displaying
									// If so, add the wife to the tag
									if ($family["HUSB"]->xref == $this->father_family[$father_famid]["HUSB"]->xref) {
										$name = $this->father_family[$father_famid]["WIFE"]->name;
										if (HasChinese($name)) $name = PrintReady($name." (".GetAddPersonName($this->father_family[$father_famid]["WIFE"]->xref).")");
										$this->father_family[$father_famid]["label"] .= $name;
									}
									// NOTE: We are not viewing the husband, so add the husbands label
									else {
										$name = $this->father_family[$father_famid]["HUSB"]->name;
										if (HasChinese($name)) $name = PrintReady($name." (".GetAddPersonName($this->father_family[$father_famid]["HUSB"]->xref).")");
										$this->father_family[$father_famid]["label"] .= $name;
									}
								}
							}
						}
					}
				}
				
				// NOTE: Get the mothers families only if they have kids
				foreach ($this->parents as $famid => $family) {
					if (isset($family["WIFE"]->xref)) {
						if ($this->show_changes) $mother_families = FindSfamilyIds($family["WIFE"]->xref, true);
						else $mother_families = FindSfamilyIds($family["WIFE"]->xref);
						$newkids = array();
						foreach ($mother_families as $key => $mmother_famid) {
							$mother_famid = $mmother_famid["famid"];
							if ($mother_famid != $famid) {
								$famrec = FindFamilyRecord($mother_famid);
								$kids = FindChildrenInRecord($famrec, $mother_famid);
								if (count($kids) > 0) {
									foreach ($kids as $kidkey => $kidid) {
										$newkids[$kidid] = new Person(FindGedcomRecord($kidid));
										$sex = $newkids[$kidid]->getSex();
										$label = $newkids[$kidid]->gender($sex, "halfkids");
										if ($newkids[$kidid]->getxref()==$this->xref) $label = "<img src=\"images/selected.png\" alt=\"\" />";
										$newkids[$kidid]->setLabel($label);
									}
									$this->mother_family[$mother_famid]["kids"] = $newkids;
									$parents = FindParentsInRecord($famrec);
									foreach ($parents as $type => $parentid) {
										$this->mother_family[$mother_famid][$type] = new Person(FindGedcomRecord($parentid));
									}
									// NOTE: Get the label for the family
									$this->mother_family[$mother_famid]["label"] = $gm_lang["mothers_family_with"] . " ";
									// Check if the wife is equal to the person we are displaying
									// If so, add the husband to the tag
									if ($family["WIFE"]->xref == $this->mother_family[$mother_famid]["WIFE"]->xref) {
										$name = $this->mother_family[$mother_famid]["HUSB"]->name;
										if (HasChinese($name)) $name = PrintReady($name." (".GetAddPersonName($this->mother_family[$mother_famid]["HUSB"]->xref).")");
										$this->mother_family[$mother_famid]["label"] .= $name;
									}
									// NOTE: We are not viewing the husband, so add the husbands label
									else {
										$name = $this->mother_family[$mother_famid]["WIFE"]->name;
										if (HasChinese($name)) $name = PrintReady($name." (".GetAddPersonName($this->mother_family[$mother_famid]["WIFE"]->xref).")");
										$this->mother_family[$mother_famid]["label"] .= $name;
									}
								}
							}
						}
					}
				}
			}
		}
	
	function gender($sex, $type, $divorced="", $married = "") {
		global $gm_lang;
		$label = $gm_lang["unknown"];
		switch ($type) {
			case "spousekids" :
				switch ($sex) {
					case "F" :
						// NOTE: Get the label for the daughter
						$label = $gm_lang["daughter"];
						break;
					case "M" :
						// NOTE: Get the label for the son
						$label = $gm_lang["son"];
						break;
				}
				return $label;
				break;
			case "fosterspousekids" :
				switch ($sex) {
					case "F" :
						// NOTE: Get the label for the daughter
						$label = $gm_lang["foster_daughter"];
						break;
					case "M" :
						// NOTE: Get the label for the son
						$label = $gm_lang["foster_son"];
						break;
				}
				return $label;
				break;
			case "adoptedspousekids" :
				switch ($sex) {
					case "F" :
						// NOTE: Get the label for the daughter
						$label = $gm_lang["adopted_daughter"];
						break;
					case "M" :
						// NOTE: Get the label for the son
						$label = $gm_lang["adopted_son"];
						break;
				}
				return $label;
				break;
			case "sealingspousekids" :
				switch ($sex) {
					case "F" :
						// NOTE: Get the label for the daughter
						$label = $gm_lang["sealing_daughter"];
						break;
					case "M" :
						// NOTE: Get the label for the son
						$label = $gm_lang["sealing_son"];
						break;
				}
				return $label;
				break;
			case "spouseparents" :
				// NOTE: Get the label for the wife/ex-wife/husband/ex-husband/partner/ex-partner
				if (strlen($married) == 0) {
					$label = $gm_lang["partner"];
				}
				else {
					if ($sex=="F") {
						if (strlen($divorced) == 0) $label = $gm_lang["wife"];
						else $label = $gm_lang["exwife"];
					}
					else {
						if ($sex=="M") {
							if (strlen($divorced) == 0) $label = $gm_lang["husband"];
							else $label = $gm_lang["exhusband"];
						}
					}
				}
				return $label;
				break;
			case "parentskids" :
				switch ($sex) {
					case "F" :
						// NOTE: Get the label for the sister
						$label = $gm_lang["sister"];
						break;
					case "M" :
						// NOTE: Get the label for the brother
						$label = $gm_lang["brother"];
						break;
				}
				return $label;
				break;
			case "fosterparentskids" :
				switch ($sex) {
					case "F" :
						// NOTE: Get the label for the sister
						$label = $gm_lang["foster_sister"];
						break;
					case "M" :
						// NOTE: Get the label for the brother
						$label = $gm_lang["foster_brother"];
						break;
				}
				return $label;
				break;
			case "adoptedparentskids" :
				switch ($sex) {
					case "F" :
						// NOTE: Get the label for the sister
						$label = $gm_lang["adopted_sister"];
						break;
					case "M" :
						// NOTE: Get the label for the brother
						$label = $gm_lang["adopted_brother"];
						break;
				}
				return $label;
				break;
			case "sealingparentskids" :
				switch ($sex) {
					case "F" :
						// NOTE: Get the label for the sister
						$label = $gm_lang["sealing_sister"];
						break;
					case "M" :
						// NOTE: Get the label for the brother
						$label = $gm_lang["sealing_brother"];
						break;
				}
				return $label;
				break;
			case "parents" :
				switch ($sex) {
					case "F" :
						// Note: Get the label for the father
						$label = $gm_lang["mother"];
						break;
					case "M" :
						// Note: Get the label for the mother
						$label = $gm_lang["father"];
						break;
				}
				return $label;
				break;
			case "fosterparents" :
				switch ($sex) {
					case "F" :
						// Note: Get the label for the father
						$label = $gm_lang["foster_mother"];
						break;
					case "M" :
						// Note: Get the label for the mother
						$label = $gm_lang["foster_father"];
						break;
				}
				return $label;
				break;
			case "adoptedparents" :
				switch ($sex) {
					case "F" :
						// Note: Get the label for the father
						$label = $gm_lang["adopted_mother"];
						break;
					case "M" :
						// Note: Get the label for the mother
						$label = $gm_lang["adopted_father"];
						break;
				}
				return $label;
				break;
			case "sealingparents" :
				switch ($sex) {
					case "F" :
						// Note: Get the label for the father
						$label = $gm_lang["sealed_mother"];
						break;
					case "M" :
						// Note: Get the label for the mother
						$label = $gm_lang["sealed_father"];
						break;
				}
				return $label;
				break;
			case "halfkids" :
				switch ($sex) {
					case "F" :
						// NOTE: Get the label for the sister
						$label = $gm_lang["halfsister"];
						break;
					case "M" :
						// NOTE: Get the label for the brother
						$label = $gm_lang["halfbrother"];
						break;
				}
				return $label;
				break;
		}
	}
	/**
	 * get family with spouse ids
	 * @return array	array of the FAMS ids
	 */
	function getSpouseFamilyIds() {
		global $controller;
		if (!is_null($this->fams)) return $this->fams;
		// $this->fams = find_families_in_record($this->gedrec, "FAMS");
		if ($this->show_changes) $this->fams = FindSfamilyIds($this->xref, true);
		else $this->fams = FindSfamilyIds($this->xref);
		return $this->fams;
	}

	/**
	 * get the families with parents
	 * @return array	array of Family objects
	 */
	function getChildFamilies() {
		if (!is_null($this->childFamilies)) return $this->childFamilies;
		$fams = $this->getChildFamilyIds();
		$families = array();
		foreach($fams as $key=>$ffamid) {
			$famid = $ffamid["famid"];
			if (!empty($famid)) {
				$famrec = FindFamilyRecord($famid);
				if (empty($famrec)) $famrec = "0 @".$famid."@ FAM\r\n1 CHIL ".$this->xref;
				// $alloldsubs = GetAllSubrecords($famrec);
				// if (ChangePresent($famid, true, false)) {
					// $famrecnew = ChangePresent($famid);
					// $diffsubs = array_diff(GetAllSubrecords($famrecnew), $alloldsubs);
					// $famrec = trim($famrec)."\r\n";
					// foreach ($diffsubs as $key => $value) {
						// $famrec .= trim($value)."\r\n2 _GMC\r\n";
					// }
					// // NOTE: Create a family object with the new record
					// $family = new Family($famrec, true);
				// }
				$family = new Family($famrec);
				$family->childFamily = $this->getChildFamilyLabel($family);
				if ($ffamid["primary"] == "Y") $family->ShowPrimary = true;
				else $family->ShowPrimary = false;
				$family->PedigreeType = $ffamid["relation"];
				$families[$famid] = $family;
			}
		}
		$this->childFamilies = $families;
		return $families;
	}
	/**
	 * get family with child ids
	 * @return array	array of the FAMC ids
	 */
	function getChildFamilyIds() {
		if (!is_null($this->famc)) return $this->famc;
		// $this->famc = find_families_in_record($this->gedrec, "FAMC");
		$this->famc = FindFamilyIds($this->xref, "", $this->show_changes);
		return $this->famc;
	}
	/**
	 * get the correct label for a family
	 * @param Family $family		the family to get the label for
	 * @return string
	 */
	function getChildFamilyLabel(&$family) {
		global $gm_lang;
		$famlink = GetSubRecord(1, "1 FAMC @".$family->xref."@", $this->gedrec);
		$ft = preg_match("/2 PEDI (.*)/", $famlink, $fmatch);
		if ($ft>0) {
			$temp = trim($fmatch[1]);
			if (isset($gm_lang[$temp])) return $gm_lang[$temp]." ";
		}
		// NOTE: Family with Parents
		return $gm_lang["as_child"];
	}
}
?>