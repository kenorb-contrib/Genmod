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
	
	// General class information
	public $classname = "GedcomRecord";
	
	// Data
	protected $gedrec = null;
	protected $gedcomid = null;
	protected $changedgedrec = null;
	protected $xref = null;
	protected $type = null;
	protected $facts = null;
	protected $lastchanged = null;
	
	// Arrays of records that link to this record
	// and counters
	protected $indilist = null;
	protected $indi_count = null;
	protected $indi_hide = null;
	
	protected $famlist = null;
	protected $fam_count = null;
	protected $fam_hide = null;
	
	protected $medialist = null;
	protected $media_count = null;
	protected $media_hide = null;
	
	protected $sourcelist = null;
	protected $sour_count = null;
	protected $sour_hide = null;
	
	protected $repolist = null;
	protected $repo_count = null;
	protected $repo_hide = null;
	
	protected $notelist = null;
	protected $note_count = null;
	protected $note_hide = null;
	
	protected $actionlist = null;
	protected $action_count = null;
	protected $action_hide = null;
	protected $action_open = null;
	protected $action_closed = null;
	
	// Informational
	protected $disp = null;
	protected $disp_name = null;
	protected $show_changes = null;
	protected $canedit = null;
	protected $ischanged = null;
	protected $isdeleted = null;
	protected $isnew = null;
	protected $isempty = null;
	protected $israwedited = null;
	protected $exclude_facts = "";
	
	/**
	 * constructor for this class
	 */
	protected function __construct($id, $gedrec="", $gedcomid="") {
		global $show_changes, $GEDCOMID, $Users, $gm_username, $ALLOW_EDIT_GEDCOM;
		
		// The class might be called with an ID or with a gedcom record.
		// The gedcom record might be empty, in which case it's a new or non existent record.
		$this->gedrec = trim($gedrec);
		$this->show_changes = $show_changes;
		if (empty($gedcomid)) $this->gedcomid = $GEDCOMID;
		else $this->gedcomid = $gedcomid;
		
		if (empty($this->gedrec)) {
			$this->xref = $id;
			$this->type = $this->datatype;
			// Get the gedcom record
			switch ($this->type) {
				case "INDI": $this->gedrec = FindPersonRecord($this->xref); break;
				case "FAM": $this->gedrec = FindFamilyRecord($this->xref); break;
				case "SOUR": $this->gedrec = FindSourceRecord($this->xref); break;
				case "REPO": $this->gedrec = FindOtherRecord($this->xref); break;
				case "OBJE": $this->gedrec = FindMediaRecord($this->xref); break;
				case "NOTE": $this->gedrec = FindOtherRecord($this->xref); break;
			}
			if (empty($this->gedrec)) $this->isempty = true;
		}
		else {
			$this->isempty = false;

			$ct = preg_match("/0 @(.*)@ (\w+)/", $this->gedrec, $match);
			if ($ct>0) {
				$this->xref = trim($match[1]);
				$this->type = trim($match[2]);
			}
		}
		
		if ($GEDCOMID != $this->gedcomid) SwitchGedcom($this->gedcomid);
		
		$this->disp = displayDetailsByID($this->xref, $this->type, 1, true);
		
		if (!$this->disp && $this->datatype == "INDI") $this->disp_name = ShowLivingNameByID($this->xref, "INDI");
		else $this->disp_name = $this->disp;
		
		if ($this->disp && $ALLOW_EDIT_GEDCOM && $Users->userCanEdit($gm_username) && !FactEditRestricted($this->xref, $this->gedrec, 1)) $this->canedit = true;
		else $this->canedit = false;
		SwitchGedcom();
	}
	

	public function __get($property) {
		global $TEXT_DIRECTION, $SHOW_ID_NUMBERS;
	
		switch ($property) {
			case "gedrec":
				return $this->gedrec;
				break;
			case "gedcomid":
				return $this->gedcomid;
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
			case "canedit":
				return $this->canedit;
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
			case "israwedited":
				return $this->ThisRawEdited();
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
				return $this->parseFacts();
				break;
			// Media info
			case "indilist":
				return $this->GetLinksFromIndis();
				break;
			case "indi_count":
				if (is_null($this->indi_count)) $this->GetLinksFromIndis();
				return $this->indi_count;
				break;
			case "indi_hide":
				if (is_null($this->indi_hide)) $this->GetLinksFromIndis();
				return $this->indi_hide;
			// fam info
			case "famlist":
				return $this->GetLinksFromFams();
				break;
			case "fam_count":
				if (is_null($this->fam_count)) $this->GetLinksFromFams();
				return $this->fam_count;
				break;
			case "fam_hide":
				if (is_null($this->fam_hide)) $this->GetLinksFromFams();
				return $this->fam_hide;
			// Media info
			case "medialist":
				return $this->GetLinksFromMedia();
				break;
			case "media_count":
				if (is_null($this->media_count)) $this->GetLinksFromMedia();
				return $this->media_count;
				break;
			case "media_hide":
				if (is_null($this->media_hide)) $this->GetLinksFromMedia();
				return $this->media_hide;
			// Source info
			case "sourcelist":
				return $this->GetLinksFromSources();
				break;
			case "sour_count":
				if (is_null($this->sour_count)) $this->GetLinksFromSources();
				return $this->sour_count;
			case "sour_hide":
				if (is_null($this->sour_hide)) $this->GetLinksFromSources();
				return $this->sour_hide;
			// Repo info
			case "repolist":
				return $this->GetLinksFromRepos();
				break;
			case "repo_count":
				if (is_null($this->repo_count)) $this->GetLinksFromRepos();
				return $this->repo_count;
			case "repo_hide":
				if (is_null($this->repo_hide)) $this->GetLinksFromRepos();
				return $this->repo_hide;
			// Note info
			case "notelist":
				return $this->GetLinksFromNotes();
				break;
			case "note_count":
				if (is_null($this->note_count)) $this->GetLinksFromNotes();
				return $this->note_count;
			case "note_hide":
				if (is_null($this->note_hide)) $this->GetLinksFromNotes();
				return $this->note_hide;
			// Actions info
			case "actionlist":
				return $this->GetLinksFromActions();
				break;
			case "action_count":
			if (is_null($this->action_count)) $this->GetLinksFromActions();
				return $this->action_count;
				break;
			case "action_hide":
			if (is_null($this->action_hide)) $this->GetLinksFromActions();
				return $this->action_hide;
				break;
			case "action_open":
			if (is_null($this->action_open)) $this->GetLinksFromActionCount();
				return $this->action_open;
				break;
			case "action_closed":
			if (is_null($this->action_closed)) $this->GetLinksFromActionCount();
				return $this->action_closed;
				break;
		}
	}

	public function __set($property, $value) {
	}
		
	
	protected function ThisDeleted() {
		
		if (is_null($this->isdeleted)) {
			if (!$this->ThisChanged()) $this->deleted = false;
			else {
				$ch = $this->GetChangedGedRec();
				if (empty($ch)) $this->isdeleted = true;
				else $this->isdeleted = false;
			}
		}
		return $this->isdeleted;
	}
		
	protected function ThisChanged() {
		
		if (is_null($this->ischanged)) {
			if (GetChangeData(true, $this->xref, true, "", "")) $this->ischanged = true;
			else $this->ischanged = false;
		}
		return $this->ischanged;
	}
	
	protected function ThisNew() {
		
		if (is_null($this->isnew)) {
			if (empty($this->gedrec) && GetChangeData(true, $this->xref, true, "", "")) {
				$this->isnew = true;
				$this->ischanged = true;
			}
			else $this->isnew = false;
		}
		return $this->isnew;
	}
		
	protected function ThisRawEdited() {
		global $TBLPREFIX;
		
		if (is_null($this->israwedited) && $this->show_changes) {
			$sql = "SELECT COUNT(ch_id) FROM ".$TBLPREFIX."changes WHERE ch_gedfile='".$this->gedcomid."' AND ch_type='edit_raw' AND ch_gid='".$this->xref."'";
			if ($res = NewQuery($sql)) {
				$row = $res->FetchRow();
				$res->FreeResult();
				if ($row[0]>0) {
					$this->israwedited = true;
					$this->ischanged = true;
				}
				else $this->israwedited = false;
			}
		}
		return $this->israwedited;
	}
	
	protected function GetChangedGedRec() {
		global $GEDCOM;
		
		if (!$this->ThisChanged()) return "";
		if (is_null($this->changedgedrec)) {
			$rec = GetChangeData(false, $this->xref, true, "gedlines", "");
			$this->changedgedrec = $rec[$GEDCOM][$this->xref];
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
		
		if (!is_null($this->facts)) return $this->facts;
		
		$this->facts = array();
		// Get the subrecords/facts. Don't apply privacy here, as it will disturb
		// the fact numbering which is needed for editing (get the right fact number)
		$allsubs = GetAllSubrecords($this->gedrec, $this->exclude_facts, false, false, false);

		if (is_array($allsubs)) {
			foreach ($allsubs as $key => $subrecord) {
				$ft = preg_match("/1\s(\w+)(.*)/", $subrecord, $match);
				if ($ft>0) {
					$fact = $match[1];
					if (strstr($match[2], "@") && in_array($fact, array("INDI", "FAM", "SOUR", "OBJE", "NOTE", "ASSO"))) $gid = trim(str_replace("@", "", $match[2]));
					else $gid = "";
				}
				else {
					$fact = "";
					$gid = "";
				}
				$fact = trim($fact);
				if (!isset($count[$fact])) $count[$fact] = 1;
				else $count[$fact]++;
				$typeshow = true;
				if ($fact == "EVEN") {
					$ct = preg_match("/2 TYPE (.*)/", $subrecord, $match);
					if ($ct>0) $typeshow = ShowFact(trim($match[1]), $this->xref, $this->type);
				}
				if (!empty($fact) && ShowFact($fact, $this->xref, $this->type) && $typeshow && !FactViewRestricted($this->xref, $subrecord, 2)) {
					if (empty($gid) || DisplayDetailsByID($gid, $fact, 1, true)) {
						$this->facts[] = array($fact, $subrecord, $count[$fact], "");
					}
				}
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
				if (!empty($fact) && !in_array($fact, array($this->exclude_facts)) && ShowFact($fact, $this->xref, $this->type) && !FactViewRestricted($this->xref, $newrec, 2)) {
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
		return $this->facts;
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