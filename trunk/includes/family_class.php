<?php
/**
 * Class file for a Family
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
 * @version $Id: family_class.php,v 1.10 2006/04/17 20:01:52 roland-d Exp $
 */
/**
 * Inclusion of the base file for a gedcom record
*/
require_once 'includes/gedcomrecord.php';

/**
 * Inclusion of the person class file
*/
require_once 'includes/person_class.php';

class Family extends GedcomRecord {
	var $husb = null;
	var $wife = null;
	var $children = array();
	var $disp = true;
	var $marr_rec = null;
	var $marr_date = null;
	var $marr_type = null;
	
	/**
	 * constructor
	 * @param string $gedrec	the gedcom record
	 */
	function Family($gedrec, $changed=false) {
		global $gm_changes, $GEDCOM;
		
		parent::GedcomRecord($gedrec);
		$this->disp = displayDetailsById($this->xref, "FAM");
		$husbrec = get_sub_record(1, "1 HUSB", $gedrec);
		if (!empty($husbrec)) {
			//-- get the husbands ids
			$husb = get_gedcom_value("HUSB", 1, $husbrec);
			if ($changed) $indirec = change_present($husb);
			else $indirec = find_person_record($husb);
			$this->husb = new Person($indirec);
		}
		$wiferec = get_sub_record(1, "1 WIFE", $gedrec);
		if (!empty($wiferec)) {
			//-- get the wifes ids
			$wife = get_gedcom_value("WIFE", 1, $wiferec);
			if ($changed) $indirec = change_present($wife);
			else $indirec = find_person_record($wife);
			$this->wife = new Person($indirec);
		}
		$num = preg_match_all("/1\s*CHIL\s*@(.*)@/", $gedrec, $smatch, PREG_SET_ORDER);
		for($i=0; $i<$num; $i++) {
			//-- get the childs ids
			$chil = trim($smatch[$i][1]);
			
			// NOTE: Check if the indi has a change
			if (change_present($chil, true, false)) {
				$indirec = change_present($chil);
				$this->children[] = new Person($indirec, true);
			}
			else {
				$indirec = find_person_record($chil);
				$this->children[] = new Person($indirec);
			}
		}
		/**
		// // NOTE: Get any changes to this family
		// if (change_present($this->xref, true, false)) {
			// $alloldsubs = get_all_subrecords($gedrec);
			// $famrecnew = change_present($this->xref);
			// $diffsubs = array_diff(get_all_subrecords($famrecnew), $alloldsubs);
			// foreach ($diffsubs as $key => $value) {
				// ?><pre><?php
				// print_r($value);
				// ?></pre><?php
				// // $indirec = change_present($chil);
				// // $this->children[] = new Person($indirec, true);
			// }
		// }
		*/
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
		global $GEDCOM, $gm_changes, $gm_username;
		if ($this->changed) return null;
		if (userCanEdit($gm_username)&&($this->disp)) {
			if (change_present($this->xref, true, false)) {
				$newrec = change_present($this->xref);
				if (!empty($newrec)) {
					$newfamily = new Family($newrec, true);
					return $newfamily;
				}
			}
		}
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
		$this->marr_rec = get_sub_record(1, "1 MARR", $this->gedrec);
		$this->marr_date = get_sub_record(2, "2 DATE", $this->marr_rec);
		$this->marr_type = get_sub_record(2, "2 TYPE", $this->marr_rec);
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