<?php
/**
 * Class file for a person
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 Genmod Development Team
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
 * @version $Id: person_class.php,v 1.18 2006/04/09 15:53:28 roland-d Exp $
 */
require_once 'includes/gedcomrecord.php';
require_once 'includes/family_class.php';
require_once 'includes/functions_charts.php';

class Person extends GedcomRecord {
	var $sex = "U";
	var $disp = true;
	var $dispname = true;
	var $indifacts = array();
	var $sourfacts = array();
	var $notefacts = array();
	var $globalfacts = array();
	var $mediafacts = array();
	var $facts_parsed = false;
	var $bdate = "";
	var $ddate = "";
	var $brec = "";
	var $drec = "";
	var $label = "";
	var $highlightedimage = null;
	// var $file = "";
	var $sosamax = 7;
	var $fams = null;
	var $famc = null;
	var $spouseFamilies = null;
	var $childFamilies = null;
	/**
	 * Constructor for person object
	 * @param string $gedrec	the raw individual gedcom record
	 */
	function Person($gedrec, $changed=false) {
		global $MAX_ALIVE_AGE;
		
		parent::GedcomRecord($gedrec);
		// NOTE: Check if person exists
		$this->exist = $this->pid_exist($this->xref);
		
		// NOTE: Determine the persons gender
		$st = preg_match("/1 SEX (.*)/", $gedrec, $smatch);
		if ($st>0) $this->sex = trim($smatch[1]);
		if (empty($this->sex)) $this->sex = "U";
		
		// NOTE: Get the persons name
		$this->name = PrintReady($this->getName());
		
		// NOTE: Get the additional names
		$this->addname = PrintReady($this->getAddName());
		
		// NOTE: Can this person details be shown?
		$this->disp = displayDetailsByID($this->xref);
		
		// NOTE: Can this name be displayed?
		$this->dispname = showLivingNameByID($this->xref);
		
		// NOTE: Get the birth and death record
		$this->_parseBirthDeath();
		
		// NOTE: Specify if this record is from a change
		// $this->changed = true;
	}
	/**
	 * Check if the ID exists
	 * @return string
	 */
	function pid_exist($pid) {
		global $TBLPREFIX, $GEDCOMS, $GEDCOM;
		
		$sql = "SELECT i_id FROM ".$TBLPREFIX."individuals WHERE i_id = '".$pid."' AND i_file = '".$GEDCOMS[$GEDCOM]["id"]."'";
		$res = mysql_query($sql);
		if (is_array($row = mysql_fetch_row($res))) return true;
		else return false;
	}
	
	/**
	 * get the persons name
	 * @return string
	 */
	function getName() {
		global $gm_lang;
		if (!$this->canDisplayName()) return $gm_lang["private"];
		$name = PrintReady(get_person_name($this->xref, $this->gedrec));
		if (empty($name)) return $gm_lang["unknown"];
		return $name;
	}
	/**
	 * Check if an additional name exists for this person
	 * @return string
	 */
	function getAddName() {
		if (!$this->canDisplayName()) return "";
		return PrintReady(get_add_person_name($this->xref));
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
			$this->highlightedimage = find_highlighted_object($this->xref, $this->gedrec);
		}
		return $this->highlightedimage;
	}
	/**
	 * parse birth and death records
	 */
	function _parseBirthDeath() {
		global $MAX_ALIVE_AGE;
		$this->brec = get_sub_record(1, "1 BIRT", $this->gedrec);
		$this->bdate = get_sub_record(2, "2 DATE", $this->brec);
		$this->drec = get_sub_record(1, "1 DEAT", $this->gedrec);
		$this->ddate = get_sub_record(2, "2 DATE", $this->drec);
		if (empty($this->ddate) && !empty($this->bdate)) {
			$pdate=parse_date(substr($this->bdate,6));
			if ($pdate[0]["year"]>0) $this->ddate = "2 DATE BEF ".($pdate[0]["year"]+$MAX_ALIVE_AGE);
		}
		if (empty($this->bdate) && !empty($this->ddate)) {
			$pdate=parse_date(substr($this->ddate,6));
			if ($pdate[0]["year"]>0) $this->bdate = "2 DATE AFT ".($pdate[0]["year"]-$MAX_ALIVE_AGE);
		}
	}
	
	/**
	 * get the person's sex
	 * @return string 	return M, F, or U
	 */
	function getSex() {
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
	function &getUpdatedPerson() {
		global $GEDCOM, $gm_changes, $gm_username;
		if ($this->changed) return null;
		if (userCanEdit($gm_username)&&($this->disp)) {
			if (isset($gm_changes[$this->xref."_".$GEDCOM])) {
				$newrec = find_gedcom_record($this->xref);
				if (!empty($newrec)) {
					$new = new Person($newrec);
					return $new;
				}
			}
		}
		return null;
	}
	/**
	 * Parse the facts from the individual record
	 */
	function parseFacts() {
		global $nonfacts;
		//-- only run this function once
		if ($this->facts_parsed) return;
		//-- don't run this function if privacy does not allow viewing of details
		if (!$this->canDisplayDetails()) return;
		$this->facts_parsed = true;
		$this->allindisubs = get_all_subrecords($this->gedrec, "", false, false);
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
				 $g++;
			}
			// NOTE: handle special source fact case
			else if ($fact=="SOUR") {
				 $this->sourfacts[$o] = array($fact, $subrecord, $count[$fact]);
				 $o++;
			}
			// NOTE: handle special note fact case
			else if ($fact=="NOTE") {
				 $this->notefacts[$o] = array($fact, $subrecord, $count[$fact]);
				 $o++;
			}
			// NOTE: handle special media fact case
			else if ($fact=="OBJE") {
				 $this->mediafacts[$m] = array($fact, $subrecord, $count[$fact]);
				 $m++;
			}
			// -- handle special sex case
			else if ($fact=="SEX") {
				 $this->globalfacts[$g] = array($fact, $subrecord, $count[$fact]);
				 $g++;
				 $sexfound = true;
			}
			else if (!in_array($fact, $nonfacts)) {
				 $this->indifacts[$f]=array($fact, $subrecord, $count[$fact]);
				 $f++;
			}
		}
		//-- add a new sex fact if one was not found
		if (!$sexfound) {
			$this->globalfacts[$g] = array('new', "1 SEX U");
			$g++;
		}
	}
	/**
	 * add facts from the family record
	 */
	function add_family_facts() {
		global $GEDCOM, $nonfacts, $nonfamfacts;

		if (!$this->canDisplayDetails()) return;
		
		//-- Get the facts from the family with spouse (FAMS)
		$ct = preg_match_all("/1\s+FAMS\s+@(.*)@/", $this->gedrec, $fmatch, PREG_SET_ORDER);
		$count_fams = array();
		for($j=0; $j<$ct; $j++) {
			$famid = $fmatch[$j][1];
			$famrec = find_family_record($famid);
			$parents = find_parents_in_record($famrec);
			
			if ($parents['HUSB']==$this->xref) $spouse=$parents['WIFE'];
			else $spouse=$parents['HUSB'];
			
			$this->allfamsubs = get_all_subrecords($famrec);
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
						$subrecord = trim($subrecord)."\r\n";
						$subrecord.="1 _GMS @$spouse@\r\n";
						$subrecord.="1 _GMFS @$famid@\r\n";
						$this->indifacts[]=array($fact, $subrecord, $count_facts[$fact]);
					}
				}
				else if ($fact=="OBJE") {
					$subrecord = trim($subrecord)."\r\n";
					$subrecord.="1 _GMS @$spouse@\r\n";
					$subrecord.="1 _GMFS @$famid@\r\n";
					// $this->mediafacts[]=array($fact, $subrecord, $count_facts[$fact]);
				}
			}
			$this->add_spouse_facts($spouse, $famrec);
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
		global $SHOW_RELATIVES_EVENTS;
		if (!$SHOW_RELATIVES_EVENTS) return;
		if ($sosa>$this->sosamax) return;
		//-- find family as child
		$famids = find_family_ids($pid);
		foreach ($famids as $indexval=>$famid) {
			$parents = find_parents($famid);
			// add father death
			$spouse = $parents["HUSB"];
			if ($sosa==1) $fact="_DEAT_FATH"; else $fact="_DEAT_GPAR";
			if ($spouse && strstr($SHOW_RELATIVES_EVENTS, $fact)) {
				$srec = get_sub_record(1, "1 DEAT", find_person_record($spouse));
				$sdate = get_sub_record(2, "2 DATE", $srec);
				if (compare_facts($this->bdate, $sdate)<0 && compare_facts($sdate, $this->ddate)<0) {
					$factrec = "1 ".$fact;
					$factrec .= "\n".trim($sdate);
					if (!showFact("DEAT", $spouse)) $factrec .= "\n2 RESN privacy";
					$factrec .= "\n2 ASSO @".$spouse."@";
					$factrec .= "\n3 RELA ".(get_sosa_name($sosa*2));
					$this->indifacts[]=array("X$fact", $factrec, 0);
				}
			}
			if ($sosa==1) $this->add_stepsiblings_facts($spouse, $famid); // stepsiblings with father
			$this->add_parents_facts($spouse, $sosa*2); // recursive call for father ancestors
			// add mother death
			$spouse = $parents["WIFE"];
			if ($sosa==1) $fact="_DEAT_MOTH"; else $fact="_DEAT_GPAR";
			if ($spouse and strstr($SHOW_RELATIVES_EVENTS, $fact)) {
				$srec = get_sub_record(1, "1 DEAT", find_person_record($spouse));
				$sdate = get_sub_record(2, "2 DATE", $srec);
				if (compare_facts($this->bdate, $sdate)<0 and compare_facts($sdate, $this->ddate)<0) {
					$factrec = "1 ".$fact;
					$factrec .= "\n".trim($sdate);
					if (!showFact("DEAT", $spouse)) $factrec .= "\n2 RESN privacy";
					$factrec .= "\n2 ASSO @".$spouse."@";
					$factrec .= "\n3 RELA sosa_".($sosa*2+1);
					$this->indifacts[]=array("X$fact", $factrec, 0);
				}
			}
			if ($sosa==1) $this->add_stepsiblings_facts($spouse, $famid); // stepsiblings with mother
			$this->add_parents_facts($spouse, $sosa*2+1); // recursive call for mother ancestors
			if ($sosa>3) return;
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
						$sfamids = find_sfamily_ids($spid);
						foreach ($sfamids as $indexval=>$sfamid) {
							if ($sfamid==$famid and $rela=="mother") continue; // show current family marriage only for father
							$childrec = find_family_record($sfamid);
							$srec = get_sub_record(1, "1 MARR", $childrec);
							$sdate = get_sub_record(2, "2 DATE", $srec);
							if (compare_facts($this->bdate, $sdate)<0 and compare_facts($sdate, $this->ddate)<0) {
								$factrec = "1 ".$fact;
								$factrec .= "\n".trim($sdate);
								if (!showFact("MARR", $sfamid)) $factrec .= "\n2 RESN privacy";
								$factrec .= "\n2 ASSO @".$spid."@";
								$factrec .= "\n3 RELA ".$rela;
								$sparents = find_parents($sfamid);
								$spouse = $sparents["HUSB"];
								if ($spouse==$spid) $spouse = $sparents["WIFE"];
								if ($rela=="father") $rela2="stepmom";
								else $rela2="stepdad";
								if ($sfamid==$famid) $rela2="mother";
								$factrec .= "\n2 ASSO @".$spouse."@";
								$factrec .= "\n3 RELA ".$rela2;
								$factrec .= "\n2 ASSO @".$sfamid."@";
								$factrec .= "\n3 RELA family";
								$this->indifacts[]=array("X$fact", $factrec, 0);
							}
						}
					}
				}
			}
			//-- find siblings
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
		global $SHOW_RELATIVES_EVENTS;
		if (!$SHOW_RELATIVES_EVENTS) return;
		$famrec = find_family_record($famid);
		$num = preg_match_all("/1\s*CHIL\s*@(.*)@/", $famrec, $smatch, PREG_SET_ORDER);
		for($i=0; $i<$num; $i++) {
			$spid = $smatch[$i][1];
			if ($spid!=$except) {
				$childrec = find_person_record($spid);
				$srec = get_sub_record(1, "1 SEX", $childrec);
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
					$srec = get_sub_record(1, "1 BIRT", $childrec);
					$sdate = get_sub_record(2, "2 DATE", $srec);
					if (compare_facts($this->bdate, $sdate)<0 and compare_facts($sdate, $this->ddate)<0) {
						$factrec = "1 ".$fact;
						$factrec .= "\n".trim($sdate);
						if (!showFact("BIRT", $spid)) $factrec .= "\n2 RESN privacy";
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
					$srec = get_sub_record(1, "1 DEAT", $childrec);
					$sdate = get_sub_record(2, "2 DATE", $srec);
					if (compare_facts($this->bdate, $sdate)<0 and compare_facts($sdate, $this->ddate)<0) {
						$factrec = "1 ".$fact;
						$factrec .= "\n".trim($sdate);
						if (!showFact("DEAT", $spid)) $factrec .= "\n2 RESN privacy";
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
					$sfamids = find_sfamily_ids($spid);
					foreach ($sfamids as $indexval=>$sfamid) {
						$childrec = find_family_record($sfamid);
						$srec = get_sub_record(1, "1 MARR", $childrec);
						$sdate = get_sub_record(2, "2 DATE", $srec);
						if (compare_facts($this->bdate, $sdate)<0 and compare_facts($sdate, $this->ddate)<0) {
							$factrec = "1 ".$fact;
							$factrec .= "\n".trim($sdate);
							if (!showFact("MARR", $sfamid)) $factrec .= "\n2 RESN privacy";
							$factrec .= "\n2 ASSO @".$spid."@";
							$factrec .= "\n3 RELA ".$rela;
							$parents = find_parents($sfamid);
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
							$factrec .= "\n2 ASSO @".$spouse."@";
							$factrec .= "\n3 RELA ".$rela2;
							$factrec .= "\n2 ASSO @".$sfamid."@";
							$factrec .= "\n3 RELA family";
							$arec = get_sub_record(2, "2 ASSO @".$spid."@", $srec);
							if ($arec) $factrec .= "\n".$arec;
							$this->indifacts[]=array("X$fact", $factrec, 0);
						}
					}
				}
				// add grand-children
				if ($option=="") {
					$famids = find_sfamily_ids($spid);
					foreach ($famids as $indexval=>$famid) {
						$this->add_children_facts($famid, "grand");
					}
				}
				// first cousins
				if ($option=="2" or $option=="3") {
					$famids = find_sfamily_ids($spid);
					foreach ($famids as $indexval=>$famid) {
						$this->add_children_facts($famid, "first");
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
		global $SHOW_RELATIVES_EVENTS;
		// do not show if divorced
		if (strstr($famrec, "1 DIV")) return;
		// add spouse death
		$fact = "_DEAT_SPOU";
		if ($spouse && strstr($SHOW_RELATIVES_EVENTS, $fact)) {
			$srec=get_sub_record(1, "1 DEAT", find_person_record($spouse));
			$sdate=get_sub_record(2, "2 DATE", $srec);
			if (compare_facts($this->bdate, $sdate)<0 and compare_facts($sdate, $this->ddate)<0) {
				$factrec = "1 ".$fact;
				$factrec .= "\n".trim($sdate);
				if (!showFact("DEAT", $spouse)) $factrec .= "\n2 RESN privacy";
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
		$famids = find_sfamily_ids($spouse);
		foreach ($famids as $indexval=>$famid) {
			// process children from all step families
			if ($famid!=$except) $this->add_children_facts($famid, "step");
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
			$sdate = get_sub_record(2, "2 DATE", $hrec);
			if (compare_facts($this->bdate, $sdate)<0 && compare_facts($sdate, $this->ddate)<0) {
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
		global $factarray, $gm_lang;
		global $assolist, $GEDCOM, $GEDCOMS;
		if (!function_exists("get_asso_list")) return;
		get_asso_list();
		$apid = $pid."[".$GEDCOMS[$GEDCOM]["id"]."]";
		// associates exist ?
		if (isset($assolist[$apid])) {
			// if so, print all indi's where the indi is associated to
			foreach($assolist[$apid] as $indexval => $asso) {
				$ct = preg_match("/0 @(.*)@ (.*)/", $asso["gedcom"], $match);
				$rid = $match[1];
				$typ = $match[2];
				// search for matching fact
				for ($i=1; ; $i++) {
					$srec = get_sub_record(1, "1 ", $asso["gedcom"], $i);
					if (empty($srec)) break;
					$arec = get_sub_record(2, "2 ASSO @".$pid."@", $srec);
					if ($arec) {
						$fact = trim(substr($srec, 2, 5));
						$label = strip_tags($factarray[$fact]);
						$sdate = get_sub_record(2, "2 DATE", $srec);
						// relationship ?
						$rrec = get_sub_record(3, "3 RELA", $arec);
						$rela = trim(substr($rrec, 7));
						if (empty($rela)) $rela = "ASSO";
						if (isset($gm_lang[$rela])) $rela = $gm_lang[$rela];
						else if (isset($factarray[$rela])) $rela = $factarray[$rela];
						// add an event record
						$factrec = "1 EVEN\n2 TYPE ".$label."<br/>[".$rela."]";
						$factrec .= "\n".trim($sdate);
						if (!showFact($fact, $rid)) $factrec .= "\n2 RESN privacy";
						$famrec = find_family_record($rid);
						if ($famrec) {
							$parents = find_parents_in_record($famrec);
							if ($parents["HUSB"]) $factrec .= "\n2 ASSO @".$parents["HUSB"]."@"; //\n3 RELA ".$factarray[$fact];
							if ($parents["WIFE"]) $factrec .= "\n2 ASSO @".$parents["WIFE"]."@"; //\n3 RELA ".$factarray[$fact];
						}
						else $factrec .= "\n2 ASSO @".$rid."@\n3 RELA ".$label;
						//$factrec .= "\n3 NOTE ".$rela;
						$factrec .= "\n2 ASSO @".$pid."@\n3 RELA ".$rela;

						$this->indifacts[] = array("X$fact", $factrec, 0);
					}
				}
			}
		}
	}
	
	function getParentFamily($pid) {
		global $gm_lang, $controller;
		$child_families = find_families_in_record($this->gedrec, "FAMC");
		if (count($child_families) > 0) {
			$controller->close_relatives = true;
			foreach ($child_families as $id => $famid) {
				// NOTE: Retrieve the family record
				$famrec = find_family_record($famid);
				
				// NOTE: Get an array of the parents ID's
				$this->parents[$famid] = find_parents_in_record($famrec);
				
				// NOTE: Create parents and set their label
				foreach ($this->parents[$famid] as $type => $parentid) {
					$this->parents[$famid][$type] = new Person(find_gedcom_record($parentid));
					$sex = $this->parents[$famid][$type]->getSex();
					$label = $this->parents[$famid][$type]->gender($sex, "parents");
					if ($this->parents[$famid][$type]->getXref()==$controller->pid) $label = "<img src=\"images/selected.png\" alt=\"\" />";
					$this->parents[$famid][$type]->setLabel($label);
				}
				// NOTE: Set the family ID
				$this->parents[$famid]["FAMID"] = $famid;
				
				// NOTE: Get an array of the children ID's
				$siblings = find_children_in_record($famrec);
				
				// NOTE: Create the children and set their label
				foreach ($siblings as $id => $sibid) {
					$this->siblings[$sibid] = new Person(find_gedcom_record($sibid));
					$sex = $this->siblings[$sibid]->getSex();
					$label = $this->siblings[$sibid]->gender($sex, "parentskids");
					if ($this->siblings[$sibid]->getXref()==$controller->pid) $label = "<img src=\"images/selected.png\" alt=\"\" />";
					$this->siblings[$sibid]->setLabel($label);
				}
			}
		}
	}
	
	function getSpouseFamily($pid) {
		global $gm_lang, $controller;
		// NOTE: Get an array of the spouse ID's
		$spouse_families = find_sfamily_ids($pid);
		
		// NOTE: Create the spouses and set their label
		foreach ($spouse_families as $id => $famid) {
			$controller->close_relatives = true;
			// NOTE: Retrieve the family record
			$famrec = find_family_record($famid);
			
			// NOTE: Retrieve the parents
			$this->spouses[$famid]["parents"] = find_parents_in_record($famrec);
			
			// NOTE: Get the label for the family
			$this->spouses[$famid]["label"] = $gm_lang["family_with"] . " ";
			// Check if the husband is equal to the person we are displaying
			// If so, add the wife to the tag
			if ($controller->pid == $this->spouses[$famid]["parents"]["HUSB"]) {
				$this->spouses[$famid]["label"] .= get_person_name($this->spouses[$famid]["parents"]["WIFE"]);
			}
			// NOTE: We are not viewing the husband, so add the husbands label
			else $this->spouses[$famid]["label"] .= get_person_name($this->spouses[$famid]["parents"]["HUSB"]);
			
			// NOTE: Create the parents and set their label
			foreach ($this->spouses[$famid]["parents"] as $type => $parentid) {
				$this->spouses[$famid]["parents"][$type] = new Person(find_gedcom_record($parentid));
				$sex = $this->spouses[$famid]["parents"][$type]->getSex();
				$divorced = "";
				$divorced = get_sub_record(1, "DIV", $famrec);
				$label = $this->spouses[$famid]["parents"][$type]->gender($sex, "spouseparents", $divorced);
				if ($this->spouses[$famid]["parents"][$type]->getXref()==$controller->pid) $label = "<img src=\"images/selected.png\" alt=\"\" />";
				$this->spouses[$famid]["parents"][$type]->setLabel($label);
			}
			
			// NOTE: Create the children and set their label
			$kids = find_children_in_record($famrec, $this->xref);
			foreach ($kids as $type => $kidid) {
				$this->spouses[$famid]["kids"][$kidid] = new Person(find_gedcom_record($kidid));
				$sex = $this->spouses[$famid]["kids"][$kidid]->getSex();
				$label = $this->spouses[$famid]["kids"][$kidid]->gender($sex, "spousekids");
				if ($this->spouses[$famid]["kids"][$kidid]->getXref()==$controller->pid) $label = "<img src=\"images/selected.png\" alt=\"\" />";
				$this->spouses[$famid]["kids"][$kidid]->setLabel($label);
			}
			
			// NOTE: Get the parents other families
			// NOTE: Get the fathers families only if they have kids
			if (isset($this->parents)) {
				foreach ($this->parents as $famid => $family) {
					$father_families = find_families_in_record($family["HUSB"]->gedrec, "FAMS");
					$newkids = array();
					foreach ($father_families as $key => $father_famid) {
						if ($father_famid != $famid) {
							$famrec = find_family_record($father_famid);
							$kids = find_children_in_record($famrec, $father_famid);
							if (count($kids) > 0) {
								foreach ($kids as $kidkey => $kidid) {
									$newkids[$kidid] = new Person(find_gedcom_record($kidid));
									$sex = $newkids[$kidid]->getSex();
									$label = $newkids[$kidid]->gender($sex, "halfkids");
									if ($newkids[$kidid]->getXref()==$controller->pid) $label = "<img src=\"images/selected.png\" alt=\"\" />";
									$newkids[$kidid]->setLabel($label);
								}
								$this->father_family[$father_famid]["kids"] = $newkids;
								$parents = find_parents_in_record($famrec);
								foreach ($parents as $type => $parentid) {
									$this->father_family[$father_famid][$type] = new Person(find_gedcom_record($parentid));
								}
								// NOTE: Get the label for the family
								$this->father_family[$father_famid]["label"] = $gm_lang["fathers_family_with"] . " ";
								// Check if the husband is equal to the person we are displaying
								// If so, add the wife to the tag
								if ($family["HUSB"]->xref == $this->father_family[$father_famid]["HUSB"]->xref) {
									$this->father_family[$father_famid]["label"] .= $this->father_family[$father_famid]["WIFE"]->name;
								}
								// NOTE: We are not viewing the husband, so add the husbands label
								else $this->father_family[$father_famid]["label"] .= $this->father_family[$father_famid]["HUSB"]->name;
							}
						}
					}
				}
				
				// NOTE: Get the mothers families only if they have kids
				foreach ($this->parents as $famid => $family) {
					$mother_families = find_families_in_record($family["WIFE"]->gedrec, "FAMS");
					$newkids = array();
					foreach ($mother_families as $key => $mother_famid) {
						if ($mother_famid != $famid) {
							$famrec = find_family_record($mother_famid);
							$kids = find_children_in_record($famrec, $mother_famid);
							if (count($kids) > 0) {
								foreach ($kids as $kidkey => $kidid) {
									$newkids[$kidid] = new Person(find_gedcom_record($kidid));
									$sex = $newkids[$kidid]->getSex();
									$label = $newkids[$kidid]->gender($sex, "halfkids");
									if ($newkids[$kidid]->getXref()==$controller->pid) $label = "<img src=\"images/selected.png\" alt=\"\" />";
									$newkids[$kidid]->setLabel($label);
								}
								$this->mother_family[$mother_famid]["kids"] = $newkids;
								$parents = find_parents_in_record($famrec);
								foreach ($parents as $type => $parentid) {
									$this->mother_family[$mother_famid][$type] = new Person(find_gedcom_record($parentid));
								}
								// NOTE: Get the label for the family
								$this->mother_family[$mother_famid]["label"] = $gm_lang["mothers_family_with"] . " ";
								// Check if the wife is equal to the person we are displaying
								// If so, add the husband to the tag
								if ($family["WIFE"]->xref == $this->mother_family[$mother_famid]["WIFE"]->xref) {
									$this->mother_family[$mother_famid]["label"] .= $this->mother_family[$mother_famid]["HUSB"]->name;
								}
								// NOTE: We are not viewing the husband, so add the husbands label
								else $this->mother_family[$mother_famid]["label"] .= $this->mother_family[$mother_famid]["WIFE"]->name;
							}
						}
					}
				}
			}
		}
	}
	
	function gender($sex, $type, $divorced="") {
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
			case "spouseparents" :
				// NOTE: Get the label for the wife/ex-wife
				if ($sex=="F" && strlen($divorced) == 0) {
					$label = $gm_lang["wife"];
				}
				else if ($sex=="F") $label = $gm_lang["exwife"];
				
				// NOTE: Get the label for the husband/ex-husband
				if ($sex=="M" && strlen($divorced) == 0) {
					$label = $gm_lang["husband"];
				}
				else if ($sex=="M") $label = $gm_lang["exhusband"];
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
		if (!is_null($this->fams)) return $this->fams;
		$this->fams = find_families_in_record($this->gedrec, "FAMS");
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
		foreach($fams as $key=>$famid) {
			if (!empty($famid)) {
				$famrec = find_family_record($famid);
				if (empty($famrec)) $famrec = "0 @".$famid."@ FAM\r\n1 CHIL ".$this->xref;
				// $alloldsubs = get_all_subrecords($famrec);
				// if (change_present($famid, true, false)) {
					// $famrecnew = change_present($famid);
					// $diffsubs = array_diff(get_all_subrecords($famrecnew), $alloldsubs);
					// $famrec = trim($famrec)."\r\n";
					// foreach ($diffsubs as $key => $value) {
						// $famrec .= trim($value)."\r\n2 _GMC\r\n";
					// }
					// // NOTE: Create a family object with the new record
					// $family = new Family($famrec, true);
				// }
				$family = new Family($famrec);
				$family->childFamily = $this->getChildFamilyLabel($family);
				$families[$famid] = $family;
			}
		}
		return $families;
	}
	/**
	 * get family with child ids
	 * @return array	array of the FAMC ids
	 */
	function getChildFamilyIds() {
		if (!is_null($this->famc)) return $this->famc;
		$this->famc = find_families_in_record($this->gedrec, "FAMC");
		return $this->famc;
	}
	/**
	 * get the correct label for a family
	 * @param Family $family		the family to get the label for
	 * @return string
	 */
	function getChildFamilyLabel(&$family) {
		global $gm_lang;
		$famlink = get_sub_record(1, "1 FAMC @".$family->xref."@", $this->gedrec);
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
