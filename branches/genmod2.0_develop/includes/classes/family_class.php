<?php
/**
 * Class file for a Family
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
 * @version $Id: family_class.php,v 1.27 2008/02/08 22:47:32 sjouke Exp $
 */
 
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class Family extends GedcomRecord {
	var $husb = null;
	var $wife = null;
	var $children = array();
	var $disp = true;
	var $famdeleted = false;
	var $famnew = false;
	var $marr_rec = null;
	var $marr_date = null;
	var $marr_type = null;
	var $show_changes = false;
	var $show_primary = false;
	var $media_count = null;
	
	/**
	 * constructor
	 * @param string $gedrec	the gedcom record
	 */
	public function __construct($gedrec, $changed=false) {
		global $GEDCOM, $show_changes, $Users;
		
		parent::__construct($gedrec);
		$this->disp = displayDetailsById($this->xref, "FAM");

		if ((!isset($show_changes) || $show_changes != "no") && $Users->UserCanEdit($Users->GetUserName())) $this->show_changes = true;

		if ($changed) $this->famnew = true;

		if ($this->show_changes && GetChangeData(true, $this->xref, true, "", "")) {
			$rec = GetChangeData(false, $this->xref, true, "gedlines", "");
			if (empty($rec[$GEDCOM][$this->xref])) $this->famdeleted = true;
		}
		$husbrec = GetSubRecord(1, "1 HUSB", $gedrec);
		if (!empty($husbrec)) {
			//-- get the husbands ids
			$husb = GetGedcomValue("HUSB", 1, $husbrec);
			if ($this->show_changes && GetChangeData(true, $husb, true, "", "INDI")) {
				$rec = GetChangeData(false, $husb, true, "gedlines");
				$indirec = $rec[$GEDCOM][$husb];
			}
			else $indirec = FindPersonRecord($husb);
			$this->husb = new Person($indirec);
		}
		$wiferec = GetSubRecord(1, "1 WIFE", $gedrec);
		if (!empty($wiferec)) {
			//-- get the wifes ids
			$wife = GetGedcomValue("WIFE", 1, $wiferec);
			if ($this->show_changes && GetChangeData(true, $wife, true, "", "INDI")) {
				$rec = GetChangeData(false, $wife, true, "gedlines");
				$indirec = $rec[$GEDCOM][$wife];
			}
			else $indirec = FindPersonRecord($wife);
			$this->wife = new Person($indirec);
		}

		if ($this->show_changes && GetChangeData(true, $this->xref, true, "", "CHIL")) {
			$rec = GetChangeData(false, $this->xref, true, "gedlines", "CHIL");
			$gedrec = $rec[$GEDCOM][$this->xref];
		}
		$num = preg_match_all("/1\s*CHIL\s*@(.*)@/", $gedrec, $smatch, PREG_SET_ORDER);
		for($i=0; $i<$num; $i++) {
			//-- get the childs ids
			$chil = trim($smatch[$i][1]);
			// NOTE: Check if the indi has a change
			if ($this->show_changes && GetChangeData(true, $chil, true, "", "INDI")) {
				$rec = GetChangeData(false, $chil, true, "gedlines", "INDI");
				$indirec = $rec[$GEDCOM][$chil];
				$this->children[] = new Person($indirec, true);
			}
			else {
				$indirec = FindPersonRecord($chil);
				$this->children[] = new Person($indirec);
			}
		}
	}
	
	/**
	 * get the husbands ID
	 * @return string
	 */
	function getHusbId() {
		if (!is_null($this->husb)) return $this->husb->getXref();
		else return "";
	}
	
	/**
	 * get the wife ID
	 * @return string
	 */
	function getWifeId() {
		if (!is_null($this->wife)) return $this->wife->getXref();
		else return "";
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
	 * get the husband's person object
	 * @return Person
	 */
	function &getHusband() {
		return $this->husb;
	}
	/**
	 * get the wife's person object
	 * @return Person
	 */
	function &getWife() {
		return $this->wife;
	}
	/**
	 * get the children
	 * @return array 	array of children Persons
	 */
	function getChildren() {
		return $this->children;
	}
	/**
	 * get the children IDs
	 * @return array 	array of children Ids
	 */
	function getChildrenIds() {
		$children = array();
		foreach ($this->children as $id => $child) {
			$children[$id] = $child->getXref();
		}
		return $children;
	}
	
	/**
	 * get the number of children in this family
	 * @return int 	the number of children
	 */
	function getNumberOfChildren() {
		return count($this->children);
	}
	  
	/**
	 * get updated Family
	 * If there is an updated family record in the gedcom file
	 * return a new family object for it
	 */
	function getUpdatedFamily() {
		global $GEDCOM, $gm_username;
		if ($this->changed) return null;
//		if (userCanEdit($gm_username)&&($this->disp)) {
//			if (GetChangeData(true, $this->xref, true, false)) {
//				$rec = GetChangeData(false, $this->xref, true, "gedlines");
//				$newrec = $rec[$GEDCOM][$this->xref];
//				if (!empty($newrec)) {
//					$newfamily = new Family($newrec, true);
//					return $newfamily;
//				}
//			}
//		}
		return null;
	}
	/**
	 * check if this family has the given person
	 * as a parent in the family
	 * @param Person $person
	 */
	function hasParent(&$person) {
		if (is_null($person)) return false;
		if ($person->equals($this->husb)) return true;
		if ($person->equals($this->wife)) return true;
		return false;
	}
	/**
	 * check if this family has the given person
	 * as a child in the family
	 * @param Person $person
	 */
	function hasChild(&$person) {
		if (is_null($person)) return false;
		foreach($this->children as $key=>$child) {
			if ($person->equals($child)) return true;
		}
		return false;
	}
	
	/**
	 * parse marriage record
	 */
	function _parseMarriageRecord() {
		$this->marr_rec = GetSubRecord(1, "1 MARR", $this->gedrec);
		$this->marr_date = GetSubRecord(2, "2 DATE", $this->marr_rec);
		$this->marr_type = GetSubRecord(2, "2 TYPE", $this->marr_rec);
	}
	
	/**
	 * get marriage record
	 * @return string
	 */
	function getMarriageRecord() {
		if (is_null($this->marr_rec)) $this->_parseMarriageRecord();
		return $this->marr_rec;
	}
	
	/**
	 * get marriage date
	 * @return string
	 */
	function getMarriageDate() {
		if (is_null($this->marr_date)) $this->_parseMarriageRecord();
		return $this->marr_date;
	}
	
	/**
	 * get the type for this marriage
	 * @return string
	 */
	function getMarriageType() {
		if (is_null($this->marr_type)) $this->_parseMarriageRecord();
		return $this->marr_type;
	}
}
?>