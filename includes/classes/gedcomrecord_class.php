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
 
abstract class GedcomRecord {
	
	// General class information
	public $classname = "GedcomRecord";	// Name of this class
	
	// Data
	protected $gedrec = null;			// Gedcom record of the object
	protected $gedcomid = null;			// Gedcom id in which this object exists
	protected $changedgedrec = null;	// The gedcom record after changes would be applied
	protected $xref = null;				// ID of the object
	protected $type = null;				// Type of the object: INDI, FAM, SOUR, REPO, NOTE or OBJE
	protected $facts = null;			// Array of fact objects for this object
	protected $lastchanged = null;		// Date and time from the CHAN fact
	
	// Arrays of records that link to this record
	// and counters
	protected $indilist = null;			// Array of person objects that link to this object
	protected $indi_count = null;		// Count of the above
	protected $indi_hide = null;		// Number of person objects that are omitted because privacy doesn't allow it
	
	protected $famlist = null;			// Array of family objects that link to this object
	protected $fam_count = null;		// Count of the above
	protected $fam_hide = null;			// Number of family objects that are omitted because privacy doesn't allow it
	
	protected $medialist = null;		// Array of media objects that link to this object
	protected $media_count = null;		// Count of the above
	protected $media_hide = null;		// Number of media objects that are omitted because privacy doesn't allow it
	
	protected $sourcelist = null;		// Array of source objects that link to this object
	protected $sour_count = null;		// Count of the above
	protected $sour_hide = null;		// Number of source objects that are omitted because privacy doesn't allow it
	
	protected $repolist = null;			// Array of repository objects that link to this object
	protected $repo_count = null;		// Count of the above
	protected $repo_hide = null;		// Number of repository objects that are omitted because privacy doesn't allow it
	
	protected $notelist = null;			// Array of note objects that link to this object
	protected $note_count = null;		// Count of the above
	protected $note_hide = null;		// Number of note objects that are omitted because privacy doesn't allow it
	
	protected $actionlist = null;		// Array of action objects that link to this object
	protected $action_count = null;		// Count of the above
	protected $action_hide = null;		// Number of action objects that are omitted because privacy doesn't allow it
	protected $action_open = null;		// Number of action objects with status open
	protected $action_closed = null;	// Number of action objects with status close

	protected $sourfacts_count = null;	// Count of level 1 source facts. Showfact, Factviewrestricted are applied, also link privacy.
	protected $notefacts_count = null;	// Count of level 1 note facts. Showfact, Factviewrestricted are applied, also link privacy.
	protected $mediafacts_count = null;	// Count of level 1 media facts. Showfact, Factviewrestricted are applied, also link privacy.
		
	// Informational
	protected $disp = null;				// If privacy allows display of this object
	protected $disp_name = null;		// Person only: if privacy allows display of the name
	protected $show_changes = null;		// If changes must be shown
	protected $canedit = null;			// If this record can be edited (facts must be checked separately)
	protected $ischanged = null;		// If this record is changed (added, deleted, changed)
	protected $isdeleted = null;		// If this record is deleted (unapproved)
	protected $isnew = null;			// If this record is new (unapproved)
	protected $isempty = null;			// If this record exists
	protected $israwedited = null;		// If this record is changed using raw editing
	protected $exclude_facts = "";		// Facts that should be excluded while parsing the facts
	protected $view = null;				// If preview mode is on
	
	/**
	 * constructor for this class
	 */
	protected function __construct($id, $gedrec="", $gedcomid="") {
		global $show_changes, $GEDCOMID, $gm_username, $gm_user, $ALLOW_EDIT_GEDCOM;
		
		// The class might be called with an ID or with a gedcom record.
		// The gedcom record might be empty, in which case it's a new or non existent record.
		$this->gedrec = trim($gedrec);
		$this->show_changes = $show_changes;
		if (empty($gedcomid)) $this->gedcomid = $GEDCOMID;
		else $this->gedcomid = $gedcomid;
	
		if ($this->gedrec == "") {
			$this->xref = $id;
			$this->type = $this->datatype;
			// Get the gedcom record
			switch ($this->type) {
				case "INDI": $this->gedrec = FindPersonRecord($this->xref); break;
				case "FAM": $this->gedrec = FindFamilyRecord($this->xref); break;
				case "SOUR": $this->gedrec = FindSourceRecord($this->xref); break;
				case "REPO": $this->gedrec = FindOtherRecord($this->xref); break;
				case "OBJE": $this->gedrec = FindMediaRecord($this->xref); break;
				case "NOTE": $this->gedrec = FindOtherRecord($this->xref, "", false, "NOTE"); break;
				case "SUBM": $this->gedrec = FindOtherRecord($this->xref, "", false, "SUBM"); break;
			}
			if (empty($this->gedrec)) {
				if (!$this->show_changes) $this->isempty = true;
				else {
					$this->GetChangedGedrec();
					if (empty($this->changedgedrec)) $this->isempty = true;
					else $this->isempty = false;
				}
			}
			else $this->isempty = false;
				
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
		
		$this->disp = PrivacyFunctions::displayDetailsByID($this->xref, $this->type, 1, true);
		$this->disp_name = $this->disp;
		
		if (!$this->disp && $this->datatype == "INDI") $this->disp_name = PrivacyFunctions::showLivingNameByID($this->xref, "INDI", $this->gedrec);
		
		if ($this->disp && $ALLOW_EDIT_GEDCOM && $gm_user->userCanEdit() && !PrivacyFunctions::FactEditRestricted($this->xref, $this->gedrec, 1)) $this->canedit = true;
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
			case "disp_name":
				return $this->disp_name;
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
				if ($SHOW_ID_NUMBERS && $this->disp_name) {
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
			case "sourfacts_count":
				if (is_null($this->facts)) $this->ParseFacts();
				return $this->sourfacts_count;
				break;
			case "notefacts_count":
				if (is_null($this->facts)) $this->ParseFacts();
				return $this->notefacts_count;
				break;
			case "mediafacts_count":
				if (is_null($this->facts)) $this->ParseFacts();
				return $this->mediafacts_count;
				break;
			case "view":
				return $this->IsPreview();
				break;
			default:
				print "<span class=\"error\">Invalid property ".$property." for __get in gedcomrecord class</span><br />";
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
		
		if (is_null($this->israwedited) && $this->show_changes) {
			$sql = "SELECT COUNT(ch_id) FROM ".TBLPREFIX."changes WHERE ch_gedfile='".$this->gedcomid."' AND ch_type='edit_raw' AND ch_gid='".$this->xref."'";
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
	 * Parse the facts from the record
	 */
	protected function parseFacts() {
		
		if (!is_null($this->facts)) return $this->facts;
		
		$this->facts = array();
		if (!$this->disp) return $this->facts;
		
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
					if ($ct>0) $typeshow = PrivacyFunctions::showFact(trim($match[1]), $this->xref, $this->type);
				}
				if (!empty($fact) && PrivacyFunctions::showFact($fact, $this->xref, $this->type) && $typeshow && !PrivacyFunctions::FactViewRestricted($this->xref, $subrecord, 2)) {
					if (empty($gid) || PrivacyFunctions::DisplayDetailsByID($gid, $fact, 1, true)) {
						$this->facts[] = new Fact($this->xref, $fact, $subrecord, $count[$fact], "");
//						$this->facts[] = array($fact, $subrecord, $count[$fact], "");
						if ($fact == "SOUR") $this->sourfacts_count++;
						elseif ($fact == "NOTE") $this->notefacts_count++;
						elseif ($fact == "OBJE") $this->mediafacts_count++;
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
				if (!empty($fact) && !in_array($fact, array($this->exclude_facts)) && PrivacyFunctions::showFact($fact, $this->xref, $this->type) && !PrivacyFunctions::FactViewRestricted($this->xref, $newrec, 2)) {
//					$this->facts[] = array($fact, $newrec, $count[$fact], "change_new");
					$this->facts[] = new Fact($this->xref, $fact, $newrec, $count[$fact], "change_new");
				}
			}
			// After sorting, add the changed facts at the appropriate places
			SortFactObjs($this->facts, $this->type);
			$newfacts = array();
			foreach($this->facts as $key => $factobj) {
				// For a new fact, always show
				if ($factobj->style == "change_new") {
					// but if the record is deleted, show everything as old
					if ($this->isdeleted) $factobj->style = "change_old";
					$newfacts[] = $factobj;
				}
				else {
					// if anything changed but is not new........
					if ($factobj->style != "change_new" && ($this->isdeleted || IsChangedFact($this->xref, $factobj->factrec))) {
						// if the record is changed, also show the new value
						$cfact = RetrieveChangedFact($this->xref, $factobj->fact, $factobj->factrec);
						// if only a fact is changed/deleted.....
						if (!$this->isdeleted) {
							// Add the old fact.
							$factobj->style = "change_old";
							$newfacts[] = $factobj;
							// an empty record indicates deletion, so only add the new record if not empty
							if (!empty($cfact)) {
								$newfact = new Fact($this->xref, $factobj->fact, $cfact, $factobj->count, "change_new");
								// add the new fact
								$newfacts[] = $newfact;
							}
						}
						// The record is deleted. Show the latest visible value of the fact
						else {
							if (!empty($cfact)) $factobj->factrec = $cfact;
							$factobj->style = "change_old";
							// add the new fact
							$newfacts[] = $factobj;
						}
					}
					else {
						// Nothing changed for this fact. Just add it
						$newfacts[] = $factobj;
					}
				}
			}
			$this->facts = $newfacts;
		}
		else SortFactObjs($this->facts, $this->type);
		if ($this->type == "INDI") {
			$sexfound = false;
			foreach($this->facts as $key => $factobj) {
				if ($factobj->fact == "NAME") $this->globalfacts[] = $factobj;
				elseif ($factobj->fact == "SEX") {
					$this->globalfacts[] = $factobj;
					$sexfound = true;
				}
			}
	
			//-- add a new sex fact if one was not found
			if (!$sexfound) {
				$this->globalfacts[] = array('new', "1 SEX U");
			}
		}
		return $this->facts;
	}
	
	public function	SelectFacts($factarr) {
		
		if (is_null($this->facts)) $this->ParseFacts();
		
		$facts = array();
		// We must retain the order of the fact array
		foreach ($factarr as $key => $fact) {
			foreach ($this->facts as $key => $factobj) {
				if ($factobj->fact == $fact) {
					$facts[] = $factobj;
				}
			}
		}
		return $facts;
	}
	
	
	protected function GetLastChangeDate() {
		
		if (!is_null($this->lastchanged)) return $this->lastchanged;
		$this->lastchanged = GetChangedDate(GetGedcomValue("CHAN:DATE", 1, $this->gedrec));
		$add = GetGedcomValue("CHAN:DATE:TIME", 1, $this->gedrec);
		if ($add) $this->lastchanged .= " - ".$add;
		return $this->lastchanged;
	}
	
	protected function IsPreview() {
		global $view;
		
		if (is_null($this->view)) {
			if (isset($view) && $view == "preview") $this->view = true;
			else $this->view = false;
		}
		return $this->view;
	}
}
?>