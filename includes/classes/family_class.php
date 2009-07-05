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
 * @version $Id$
 */
 
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class Family extends GedcomRecord {
	
	// General class information
	public $classname = "Family";
	public $datatype = "FAM";

	// data
	private $sortable_name = null;
	private $sortable_addname = null;
	public $label = null;
		
	// Family members
	private $husb = null;
	private $husb_id = null;
	private $wife = null;
	private $wife_id = null;
//	private $parents = null;
	private $children = null;
	private $children_ids = null;
	private $children_count = null;
	
	// Marriage events
	private $marr_rec = null;
	private $marr_date = null;
	private $marr_type = null;
	private $marr_plac = null;
	
	// Relations from the FAMC link
	public $showprimary = null;
	public $pedigreetype = null;
	public $status = null;
	/**
	 * constructor
	 * @param string $gedrec	the gedcom record
	 */
	public function __construct($id, $gedrec="", $gedcomid="", $changed=false) {
		global $GEDCOM, $show_changes, $Users;
		
		parent::__construct($id, $gedrec, $gedcomid);
		$this->exclude_facts = "";
	
		// for now, initialize
//		$this->GetFamilyDescriptor();
//		$this->GetFamilyAddDescriptor();
	}

	public function __get($property) {

		switch($property) {
			case "husb":
				return $this->GetHusband();
				break;
			case "wife":
				return $this->GetWife();
				break;
			case "husb_id":
				return $this->GetHusbID();
				break;
			case "wife_id":
				return $this->GetWifeID();
				break;
			case "parents":
				return array("HUSB" => $this->GetHusband(), "WIFE" => $this->GetWife());
				break;
			case "children":
				return $this->GetChildren();
				break;
			case "children_count":
				return $this->GetNumberOfChildren();
				break;
			case "sortable_name":
				return $this->GetFamilyDescriptor();
				break;
			case "sortable_addname":
				return $this->GetFamilyAddDescriptor();
				break;
			case "marr_rec":
				return $this->getMarriageRecord();
				break;
			case "marr_date":
				return $this->getMarriageDate();
				break;
			case "marr_type":
				return $this->getMarriageType();
				break;
			case "marr_plac":
				return $this->getMarriagePlace();
				break;
			case "media_count":
				return $this->GetNumberOfMedia();
				break;
			case "label":
				return $this->label;
				break;
			default:
				return parent::__get($property);
				break;
		}
	}

	
	/**
	 * get the children
	 * @return array 	array of children Persons
	 */
	private function GetChildren() {
		global $GEDCOM;

		if (is_null($this->children)) {
		
			if ($this->show_changes && $this->ThisChanged()) $gedrec = $this->GetChangedGedrec();
			else $gedrec = $this->gedrec;
			
			$this->children = array();
				
			$num = preg_match_all("/1\s*CHIL\s*@(.*)@/", $gedrec, $smatch, PREG_SET_ORDER);
			for($i=0; $i<$num; $i++) {
				//-- get the childs ids
				$chil = trim($smatch[$i][1]);
				$this->children[] = new Person($chil);
			}
		}
		return $this->children;
	}

	private function GetFamilyDescriptor() {
		
		if (is_null($this->sortable_name)) $this->sortable_name = GetFamilyDescriptor($this->xref, false, $this->gedrec);
		return $this->sortable_name;
	}
	
	private function GetFamilyAddDescriptor() {
		
		if (is_null($this->sortable_addname)) $this->sortable_addname = GetFamilyAddDescriptor($this->xref, false, $this->gedrec);
		return $this->sortable_addname;
	}

	/**
	 * get the husbands ID
	 * @return string
	 */
	private function getHusbId() {
		if (is_null($this->husb)) {
			$this->getHusband();
			if ($this->husb != "") $this->husb_id = $this->husb->xref;
			else $this->husb_id = "";
		}		
		return $this->husb_id;
	}
	
	/**
	 * get the husbands ID
	 * @return string
	 */
	private function getHusband() {
		global $GEDCOM;
		
		if (is_null($this->husb)) {
			if ($this->show_changes && $this->ThisChanged()) $gedrec = $this->GetChangedGedrec();
			else $gedrec = $this->gedrec;
			$husb = GetGedcomValue("HUSB", 1, $gedrec);
			if (!empty($husb)) $this->husb = new Person($husb);
			else $this->husb = "";
		}
		return $this->husb;
	}
	/**
	 * get the wife ID
	 * @return string
	 */
	private function getWifeId() {
		if (is_null($this->wife)) {
			$this->getWife();
			if ($this->wife != "") $this->wife_id = $this->wife->xref;
			else $this->wife_id = "";
		}		
		return $this->wife_id;
	}

	private function getWife() {
		global $GEDCOM;
		
		if (is_null($this->wife)) {
			if ($this->show_changes && $this->ThisChanged()) $gedrec = $this->GetChangedGedrec();
			else $gedrec = $this->gedrec;
			$wife = GetGedcomValue("WIFE", 1, $gedrec);
			if (!empty($wife)) $this->wife = new Person($wife);
			else $this->wife = "";
		}
		return $this->wife;
	}
	
	/**
	 * get the number of level 1 media items 
	 * @return string
	 */
	private function getNumberOfMedia() {
		
		if (!is_null($this->media_count)) return $this->media_count;
		
		if ($this->show_changes && $this->ThisChanged()) $gedrec = $this->GetChangedGedRec();
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
	 * get the children IDs
	 * @return array 	array of children Ids
	 */
	private function getChildrenIds() {

		if (!is_null($this->children_ids)) return $this->children_ids;
		if (is_null($this->children)) $this->GetChildren();

		$this->children_ids = array();
		foreach ($this->children as $id => $child) {
			$this->children_ids[$id] = $child->xref;
		}
		return $this->children_ids;
	}
	
	/**
	 * get the number of children in this family
	 * @return int 	the number of children
	 */
	private function getNumberOfChildren() {
		
		if(is_null($this->children_count)) {
			$this->GetChildren();
			$this->children_count = count($this->children);
		}
		return $this->children_count;
	}
	  
	/**
	 * parse marriage record
	 */
	private function _parseMarriageRecord() {
		
		if ($this->show_changes && $this->ThisChanged()) $gedrec = $this->GetChangedGedRec();
		else $gedrec = $this->gedrec;

		$this->marr_rec = GetSubRecord(1, "1 MARR", $this->gedrec);
		$this->marr_date = GetSubRecord(2, "2 DATE", $this->marr_rec);
		$this->marr_type = GetSubRecord(2, "2 TYPE", $this->marr_rec);
		$this->marr_plac = GetSubRecord(2, "2 PLAC", $this->marr_rec);
	}
	
	/**
	 * get marriage record
	 * @return string
	 */
	private function getMarriageRecord() {
		if (is_null($this->marr_rec)) $this->_parseMarriageRecord();
		return $this->marr_rec;
	}
	
	/**
	 * get marriage date
	 * @return string
	 */
	private function getMarriageDate() {
		if (is_null($this->marr_date)) $this->_parseMarriageRecord();
		return $this->marr_date;
	}
	
	/**
	 * get the type for this marriage
	 * @return string
	 */
	private function getMarriageType() {
		if (is_null($this->marr_type)) $this->_parseMarriageRecord();
		return $this->marr_type;
	}
	
	/**
	 * get the type for this marriage
	 * @return string
	 */
	private function getMarriagePlace() {
		if (is_null($this->marr_plac)) $this->_parseMarriageRecord();
		return $this->marr_plac;
	}
}
?>