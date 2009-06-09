<?php
/**
 * Base class for all gedcom records
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
 
class GedcomRecord {
	
	public $classname = "GedcomRecord";
	protected $gedrec = "";
	protected $gedcomid = null;
	protected $changedgedrec = NULL;
	protected $xref = NULL;
	protected $type = NULL;
	protected $disp = NULL;
	protected $show_changes = NULL;
	protected $ischanged = NULL;
	protected $isdeleted = NULL;
	protected $isnew = null;
	protected $isempty = NULL;
	protected $rfn = null;
	protected $facts = NULL;
	protected $lastchanged = null;
	private $allsubs = array();
	
	/**
	 * constructor for this class
	 */
	protected function __construct($id, $gedrec="") {
		global $show_changes;
		
		// The class might be called with an ID or with a gedcom record.
		// The gedcom record might be empty, in which case it's a new or non existent record.
		$this->gedrec = $gedrec;
		$this->show_changes = $show_changes;
		
		if (empty($this->gedrec)) {
			$this->isempty = true;
		}
		else {
			$this->isempty = false;

			$ct = preg_match("/0 @(.*)@ (\w+)/", $this->gedrec, $match);
			if ($ct>0) {
				$this->xref = trim($match[1]);
				$this->type = trim($match[2]);
				$this->disp = displayDetailsByID($this->xref, $this->type, 1, true);
			}
		}
	}
	

	public function __get($property) {
		global $TEXT_DIRECTION, $SHOW_ID_NUMBERS;
//		print "getting ".$property."<br />";
			$result = NULL;
		switch ($property) {
			case "gedrec":
				return $this->gedrec;
				break;
			case "disp":
				return $this->disp;
				break;
			case "isempty":
				return $this->isempty;
				break;
			case "show_changes":
				return $this->show_changes;
				break;
			case "ischanged":
				return $this->ThisChanged();
				break;
			case "isnew":
				return $this->ThisNew();
				break;
			case "isdeleted":
				return $this->ThisDeleted();
				break;
			case "changedgedrec":
				return $this->GetChangedGedRec();
				break;
			case "xref":
				return $this->xref;
				break;
			case "type":
				return $this->type;
				break;
			case "addxref":
				if ($SHOW_ID_NUMBERS) {
					if ($TEXT_DIRECTION=="ltr")	return " &lrm;(".$this->xref.")&lrm;";
					else return " &rlm;(".$this->xref.")&rlm;";
				}
				else return "";
				break;
			case "lastchanged":
				return $this->GetLastChangeDate();
				break;
			case "facts":
				$this->parseFacts();
				return $this->facts;
				break;
			default:
//				WriteToLog("GedcomRecord->__get-> Invalid property for ".$this-xref, "E", "S");
			break;
		}
		return $result;
	}

	public function __set($property, $value) {
		switch($property) {
			case "gedcomid":
				$this->gedcomid = $value;
				break;
		}
	}
		
	
	protected function ThisDeleted() {
		if (is_null($this->isdeleted)) {
			$ch = $this->GetChangedGedRec();
			if (empty($ch)) $this->isdeleted = true;
			else $this->isdeleted = false;
		}
		return $this->isdeleted;
	}
		
	protected function ThisChanged() {
		if (is_null($this->ischanged)) {
			if (GetChangeData(true, $this->xref, true, "", "")) $this->ischanged = true;
			else $this->ischanged = false;
			return $this->ischanged;
		}
	}
	
	protected function ThisNew() {
		if (is_null($this->isnew)) {
			if (empty($this->gedrec) && GetChangeData(true, $this->xref, true, "", "")) $this->isnew = true;
			else $this->isnew = false;
			return $this->isnew;
		}
	}
		
	protected function GetChangedGedRec() {
		global $GEDCOM;
		
		if (!$this->ThisChanged()) return $this->gedrec;
		if (is_null($this->changedgedrec)) {
			if (GetChangeData(true, $this->xref, true, "", "")) {
				$rec = GetChangeData(false, $this->xref, true, "gedlines", "");
				$this->changedgedrec = $rec[$GEDCOM][$this->xref];
			}
		}					
		return $this->changedgedrec;
	}			
	/**
	 * check if this object is equal to the given object
	 * basically just checks if the IDs are the same
	 * @param GedcomRecord $obj
	 */
	protected function equals(&$obj) {
		if (is_null($obj)) return false;
		if ($this->xref==$obj->xref) return true;
		return false;
	}
	
	/**
	 * Parse the facts from the record
	 */
	protected function parseFacts() {
		
		if (!is_null($this->facts)) return;

		$this->facts = array();
		// Get the subrecords/facts. Don't apply privacy here, as it will disturb
		// the fact numbering which is needed for editing (get the right fact number)
		$this->allsubs = GetAllSubrecords($this->gedrec, $this->exclude_facts, false, false, false);

		foreach ($this->allsubs as $key => $subrecord) {
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
			if (!empty($fact) && ShowFact($fact, $this->xref, $this->type) && !FactViewRestricted($this->xref, $subrecord, 2)) {
				$this->facts[] = array($fact, $subrecord, $count[$fact], "");
			}
		}
		// if we don't show changes, don't parse the facts
		if ($this->show_changes) {
			// Set the deleted status
			$this->ThisDeleted();
			// Add the new facts first
			$newrecs = RetrieveNewFacts($this->xref);
			foreach($newrecs as $key=> $newrec) {
				$ft = preg_match("/1\s(\w+)(.*)/", $newrec, $match);
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
				if (!empty($fact) && !in_array($fact, array($this->exclude_facts)) && ShowFact($fact, $this->xref, $this->type) && !FactViewRestricted($this->xref, $subrecord, 2)) {
					$this->facts[] = array($fact, $newrec, $count[$fact], "change_new");
				}
			}
			// After sorting, add the changed facts at the appropriate places
			SortFacts($this->facts, $this->type);
			$newfacts = array();
			foreach($this->facts as $key => $factarray) {
				// For a new fact, always show
				if ($factarray[3] == "change_new") {
					// but if the record is deleted, show everything as old
					if ($this->isdeleted) $newfacts[] = array($factarray[0], $factarray[1], $factarray[2], "change_old");
					else $newfacts[] = $factarray;
				}
				else {
					// if anything changed but is not new........
					if ($factarray[3] != "change_new" && ($this->isdeleted || IsChangedFact($this->xref, $factarray[1]))) {
						// if the record is changed, also show the new value
						$cfact = RetrieveChangedFact($this->xref, $factarray[0], $factarray[1]);
						// if only a fact is changed/deleted.....
						if (!$this->isdeleted) {
							// Add the old fact.
							$factarray[3] = "change_old";
							$newfacts[] = $factarray;
							// an empty record indicates deletion, so only add the new record if not empty
							if (!empty($cfact)) {
								$factarray[1] = $cfact;
								$factarray[3] = "change_new";
								// add the new fact
								$newfacts[] = $factarray;
							}
						}
						// The record is deleted. Show the latest visible value of the fact
						else {
							if (!empty($cfact)) $factarray[1] = $cfact;
							$factarray[3] = "change_old";
							// add the new fact
							$newfacts[] = $factarray;
						}
					}
					else {
						// Nothing changed for this fact. Just add it
						$newfacts[] = $factarray;
					}
				}
			}
			$this->facts = $newfacts;
		}
		else SortFacts($this->facts, $this->type);
	}
	
	protected function GetLastChangeDate() {
		
		if (!is_null($this->lastchanged)) return $this->lastchanged;

		$this->lastchanged = GetChangedDate(GetGedcomValue("CHAN:DATE", 1, $this->gedrec));
		$add = GetGedcomValue("CHAN:DATE:TIME", 1, $this->gedrec);
		if ($add) $this->lastchanged .= " - ".$add;
		return $this->lastchanged;
	}
}
?>