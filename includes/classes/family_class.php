<?php
/**
 * Class file for a Family
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 - 2009 Genmod Development Team
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
	public $classname = "Family";			// Name of this class
	public $datatype = "FAM";				// Type of data collected here
	private static $familycache = array(); // Holder of the instances for this class

	// data
	private $sortable_name = null;			// Printable and sortable name of the family, after applying privacy (can be unknown of private)
	private $sortable_addname = null;		// Printable and sortable addname of the family, after applying privacy (can be blank)
	public $label = null;					// Label set in the person class as a specific label for this family of the person
	private $title = null;					// Printable name for the family in normal order
	private $descriptor = null;				// Same as title
	private $adddescriptor = null;			// Printable addname, names in order firstname lastname
		
	// Family members
	private $husb = null;					// Holder for the husband object (or new, if showing changes)
	private $husb_id = null;				// Id for the husband object (or new, if showing changes)
	private $husbold = null;				// Holder for the deleted husband object (or none if not showing changes)
	private $husbold_id = null;				// Id for the deleted husband object (or none if not showing changes)
	private $husb_status = null;			// Status af the husband in this family: "deleted", "new", "changed", "" (=unchanged)
	private $wife = null;					// Holder for the wifes object (or new, if showing changes)
	private $wife_id = null;				// Id for the wifes object (or new, if showing changes)
	private $wifeold = null;				// Holder for the deleted wife object (or none if not showing changes)
	private $wifeold_id = null;				// Id for the deleted wife object (or none if not showing changes)
	private $wife_status = null;			// Status af the wife in this family: "deleted", "new", "changed", "" (=unchanged)
	private $children = null;				// Array of children the new/remaining in order, the deleted added at the end
	private $children_ids = null;			// Id's of the children in the array
	private $children_count = null;			// Number of children in the old (not changed) situation
	public $child_status = null;			// Status af the child in this family: "deleted", "new", "" (=unchanged)
	
	// Marriage events
	private $marr_fact = null;				// Fact object of marriage record (after showfact, showfactdetails and factviewrestricted)
	private $marr_date = null;				// Marriage date (after showfact, showfactdetails and factviewrestricted)
	private $marr_type = null;				// Marriage type (after showfact, showfactdetails and factviewrestricted)
	private $marr_plac = null;				// Marriage place (after showfact, showfactdetails and factviewrestricted)
	
	// Relations from the FAMC link
	public $showprimary = null;				// Show this family as primary from the childs perspective (set in person class)
	public $pedigreetype = null;			// Pedigree type of this family from the childs perspective (set in person class)
	public $status = null;					// Status of this family from the childs perspective (set in person class)
	
	
	public static function GetInstance($xref, $gedrec="", $gedcomid="") {
		global $GEDCOMID;
		
		if (empty($gedcomid)) $gedcomid = $GEDCOMID;
		if (!isset(self::$familycache[$gedcomid][$xref])) {
			self::$familycache[$gedcomid][$xref] = new Family($xref, $gedrec, $gedcomid);
		}
		return self::$familycache[$gedcomid][$xref];
	}
	
	/**
	 * constructor
	 * @param string $gedrec	the gedcom record
	 */
	public function __construct($id, $gedrec="", $gedcomid="") {
		
		if (is_array($gedrec)) {
			// extract the construction parameters
			$gedcomid = $gedrec["f_file"];
			$id = $gedrec["f_id"];
			$gedrec = $gedrec["f_gedrec"];
		}
		
		parent::__construct($id, $gedrec, $gedcomid);
		$this->exclude_facts = "";
	
	}

	public function __get($property) {

		switch($property) {
			case "husb":
				return $this->GetHusband();
				break;
			case "husb_id":
				return $this->GetHusbID();
				break;
			case "husbold":
				return $this->GetOldHusb();
				break;
			case "husbold_id":
				return $this->GetOldHusbID();
				break;
			case "husb_status":
				if (is_null($this->husb_status)) $this->GetHusband();
				return $this->husb_status;
				break;
			case "wife":
				return $this->GetWife();
				break;
			case "wife_id":
				return $this->GetWifeID();
				break;
			case "wifeold":
				return $this->GetOldWife();
				break;
			case "wifeold_id":
				return $this->GetOldWifeID();
				break;
			case "wife_status":
				if (is_null($this->wife_status)) $this->GetWife();
				return $this->wife_status;
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
			case "children_ids":
				return $this->GetChildrenIds();
				break;
			case "sortable_name":
				return $this->GetFamilyDescriptor();
				break;
			case "sortable_addname":
				return $this->GetFamilyAddDescriptor();
				break;
			case "marr_fact":
				return $this->getMarriageFact();
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
			case "title":
				return $this->GetTitle();
				break;
			case "descriptor":
				return $this->GetTitle();
				break;
			case "adddescriptor":
				return $this->GetAddTitle();
				break;
			default:
				return parent::__get($property);
				break;
		}
	}

	public function ObjCount() {
		$count = 0;
		foreach(self::$familycache as $ged => $family) {
			$count += count($family);
		}
		return $count;
	}	
	
	/**
	 * get the children
	 * @return array 	array of children Persons
	 */
	private function GetChildren() {

		if (is_null($this->children)) {
		
			$this->children = array();
			$this->child_status = array();
			if ($this->show_changes && $this->ThisChanged()) {
				$changed = true;
				$gedrec = $this->GetChangedGedrec();
				$num = preg_match_all("/1\s*CHIL\s*@(.*)@/", $gedrec, $smatch, PREG_SET_ORDER);
				$this->children_count = $num;
				for($i=0; $i<$num; $i++) {
					//-- get the childs ids
					$chil = trim($smatch[$i][1]);
					$this->children[$chil] =& Person::GetInstance($chil);
					$this->child_status[$chil] = "new";
				}
			}
			else $changed = false;
			$gedrec = $this->gedrec;
			$num = preg_match_all("/1\s*CHIL\s*@(.*)@/", $gedrec, $smatch, PREG_SET_ORDER);
			if (is_null($this->children_count)) $this->children_count = $num;
			for($i=0; $i<$num; $i++) {
				//-- get the childs ids
				$chil = trim($smatch[$i][1]);
				if (!isset($this->children[$chil])) {
					$this->children[$chil] =& Person::GetInstance($chil);
					if ($changed) $this->child_status[$chil] = "deleted";
					else $this->child_status[$chil] = "";
				}
				else $this->child_status[$chil] = "";
			}
		}
		return $this->children;
	}

	public function GetChildStatus($chil) {
		
		if (is_null($this->child_status)) $this->GetChildren();
		
		return $this->child_status[$chil];
	}
			
	
	private function GetFamilyDescriptor() {
		
		if (is_null($this->sortable_name)) $this->sortable_name = NameFunctions::GetFamilyDescriptor($this, false);
		return $this->sortable_name;
	}
	
	private function GetFamilyAddDescriptor() {
		global $DISPLAY_PINYIN, $LANGUAGE;
		
		if (is_null($this->sortable_addname)) {
			$this->sortable_addname = NameFunctions::GetFamilyAddDescriptor($this, false);
		}
		return $this->sortable_addname;
	}

	private function GetTitle() {
		
		if (is_null($this->title)) {
			$this->title = NameFunctions::GetFamilyDescriptor($this, true);
		}
		return $this->title;
	}
	
	private function GetAddTitle() {
		
		if (is_null($this->adddescriptor)) {
			$this->title = NameFunctions::GetFamilyAddDescriptor($this, true);
		}
		return $this->adddescriptor;
	}
	/**
	 * get the husbands ID
	 * @return string
	 */
	private function getHusbId() {
		
		if (is_null($this->husb_id)) {
			if (is_null($this->husb)) $this->getHusband();
			if ($this->husb != "") $this->husb_id = $this->husb->xref;
			else $this->husb_id = "";
		}		
		return $this->husb_id;
	}
	
	private function getOldHusbId() {
		
		if (is_null($this->husbold_id)) {
			if (is_null($this->husbold)) $this->getOldHusb();
			if (is_object($this->husbold)) $this->husbold_id = $this->husbold->xref;
			else $this->husbold_id = "";
		}		
		return $this->husbold_id;
	}
	
	/**
	 * get the husbands ID
	 * @return string
	 */
	private function getHusband() {
		
		if (is_null($this->husb)) {
			$husbold = "";
			$ct = preg_match("/1 HUSB @(.+)@/", $this->gedrec, $match);
			if ($ct == 0) $husb = "";
			else $husb = $match[1];
			$this->husb_status = "";
			if ($this->show_changes && $this->ThisChanged()) {
				$gedrec = $this->GetChangedGedrec();
				$ct = preg_match("/1 HUSB @(.+)@/", $gedrec, $match);
				if ($ct == 0) $husbnew = "";
				else $husbnew = $match[1];
				if ($husb == "" && $husbnew != "") {
					$this->husb_status = "new";
					$husb = $husbnew;
					$husbold = "";
				}
				elseif ($husb != "" && $husbnew == "") {
					$this->husb_status = "deleted";
					$husbold = $husb;
					$husb = "";
				}
				elseif ($husb != $husbnew) {
					$husbold = $husb;
					$husb = $husbnew;
					$this->husb_status = "changed";
				}
				else $husb = $husbnew;
			}
			if (!empty($husb)) $this->husb =& Person::GetInstance($husb, "", $this->gedcomid);
			else $this->husb = "";
			if (!empty($husbold)) $this->husbold =& Person::GetInstance($husbold, "", $this->gedcomid);
			else $this->husbold = "";
		}
		return $this->husb;
	}
	
	private function GetOldHusb() {
		
		if (is_null($this->husbold)) $this->GetHusband();
		return $this->husbold;
	}
	
	/**
	 * get the wife ID
	 * @return string
	 */
	private function getWifeId() {
		
		if (is_null($this->wife_id)) {
			if (is_null($this->wife)) $this->getWife();
			if ($this->wife != "") $this->wife_id = $this->wife->xref;
			else $this->wife_id = "";
		}		
		return $this->wife_id;
	}

	private function getOldWifeId() {
		
		if (is_null($this->wifeold_id)) {
			if (is_null($this->wifeold)) $this->getOldWife();
			if (is_object($this->wifeold)) $this->wifeold_id = $this->wifeold->xref;
			else $this->wifeold_id = "";
		}		
		return $this->wifeold_id;
	}
	
	private function getWife() {
		
		if (is_null($this->wife)) {
			$wifeold = "";
			$ct = preg_match("/1 WIFE @(.+)@/", $this->gedrec, $match);
			if ($ct == 0) $wife = "";
			else $wife = $match[1];
			$this->wife_status = "";
			if ($this->show_changes && $this->ThisChanged()) {
				$gedrec = $this->GetChangedGedrec();
				$ct = preg_match("/1 WIFE @(.+)@/", $gedrec, $match);
				if ($ct == 0) $wifenew = "";
				else $wifenew = $match[1];
				if ($wife == "" && $wifenew != "") {
					$this->wife_status = "new";
					$wife = $wifenew;
					$wifeold = "";
				}
				elseif ($wife != "" && $wifenew == "") {
					$this->wife_status = "deleted";
					$wifeold = $wife;
					$wife = "";
				}
				elseif ($wife != $wifenew) {
					$wifeold = $wife;
					$wife = $wifenew;
					$this->wife_status = "changed";
				}
				else $wife = $wifenew;
			}
			if (!empty($wife)) $this->wife =& Person::GetInstance($wife, "", $this->gedcomid);
			else $this->wife = "";
			if (!empty($wifeold)) $this->wifeold =& Person::GetInstance($wifeold, "", $this->gedcomid);
			else $this->wifeold = "";
		}
		return $this->wife;
	}

	private function GetOldWife() {
		
		if (is_null($this->wifeold)) $this->GetWife();
		return $this->wifeold;
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
		
		if(is_null($this->children_count)) $this->GetChildren();
		return $this->children_count;
	}
	  
	/**
	 * parse marriage record
	 */
	private function parseMarriageFact() {
		
		if ($this->show_changes && $this->ThisChanged()) $gedrec = $this->GetChangedGedRec();
		else $gedrec = $this->gedrec;

		$subrecord = GetSubRecord(1, "1 MARR", $this->gedrec);
		if (!empty($subrecord) && PrivacyFunctions::showFact("MARR", $this->xref, "FAM") && !PrivacyFunctions::FactViewRestricted($this->xref, $subrecord, 2)) {
			$this->marr_fact = new Fact($this->xref, "MARR", $subrecord);
			$this->marr_date = $this->marr_fact->simpledate;
			$this->marr_type = $this->marr_fact->simpletype;
			$this->marr_plac = $this->marr_fact->simpleplace;
		}
		else {
			$this->marr_fact = "";
			$this->marr_date = "";
			$this->marr_type = "";
			$this->marr_plac = "";
		}
	}
	
	/**
	 * get marriage record
	 * @return string
	 */
	private function getMarriageFact() {
		
		if (is_null($this->marr_fact)) $this->parseMarriageFact();
		return $this->marr_fact;
	}
	
	/**
	 * get marriage date
	 * @return string
	 */
	private function getMarriageDate() {
		
		if (is_null($this->marr_date)) $this->parseMarriageFact();
		return $this->marr_date;
	}
	
	/**
	 * get the type for this marriage
	 * @return string
	 */
	private function getMarriageType() {
		
		if (is_null($this->marr_type)) $this->parseMarriageFact();
		return $this->marr_type;
	}
	
	/**
	 * get the place for this marriage
	 * @return string
	 */
	private function getMarriagePlace() {
		
		if (is_null($this->marr_plac)) $this->parseMarriageFact();
		return $this->marr_plac;
	}
	
	protected function GetLinksFromActions($status="") {

		if(!is_null($this->actionlist)) return $this->actionlist;
		$this->actionlist = array();
		$search = ActionController::GetSelectActionList("", $this->xref, $this->gedcomid, $status);
		$this->actionlist = $search[0];
		$this->action_open = $search[1];
		$this->action_closed = $search[2];
		$this->action_hide = $search[3];
		$this->action_count = count($this->actionlist);
		return $this->actionlist;
	}
	
	protected function ReadFamilyRecord() {
		
		$sql = "SELECT f_gedrec FROM ".TBLPREFIX."families WHERE f_key='".JoinKey($this->xref, $this->gedcomid)."'";
		$res = NewQuery($sql);
		if ($res) {
			if ($res->NumRows() != 0) {
				$row = $res->fetchAssoc();
				$this->gedrec = $row["f_gedrec"];
			}
		}
	}
	
	public function PrintListFamily($useli=true) {
		global $gm_lang;
		
		if (!$this->DisplayDetails()) return false;
		
		if ($useli) {
			if (begRTLText($this->GetFamilyDescriptor())) print "\n\t\t\t<li class=\"rtl\" dir=\"rtl\">";
			else print "\n\t\t\t<li class=\"ltr\" dir=\"ltr\">";
		}
		print "\n\t\t\t<a href=\"family.php?famid=".$this->xref."&amp;gedid=".$this->gedcomid."\" class=\"list_item\"><b>".$this->GetFamilyDescriptor();
		if ($this->GetFamilyAddDescriptor() != "") print "&nbsp;(".$this->GetFamilyAddDescriptor().")";
		print "</b>";
		print $this->addxref;
		if ($this->GetMarriageFact() != "") {
			print " -- <i>".$gm_lang["marriage"]." ";
			$this->marr_fact->PrintFactDate(false, false, false, $this->xref);
			$this->marr_fact->PrintFactPlace();
			print "</i>";
		}
		print "</a>\n";
		if ($useli) print "</li>\n";
		
	}
}
?>