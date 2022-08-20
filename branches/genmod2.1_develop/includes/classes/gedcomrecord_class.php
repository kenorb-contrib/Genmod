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
 * @version $Id: gedcomrecord_class.php 29 2022-07-17 13:18:20Z Boudewijn $
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
	protected $privategedrec = null;	// The gedcom record after all is privatized
	protected $oldprivategedrec = null;	// The gedcom record after all is privatized
	protected $newprivategedrec = null;	// The gedcom record after all is privatized
	protected $xref = null;				// ID of the object
	protected $key = null;				// ID joined with the gedcomid
	protected $type = null;				// Type of the object: INDI, FAM, SOUR, REPO, NOTE or OBJE
	protected $facts = null;			// Array of fact objects for this object
	protected $lastchanged = null;		// Date and time from the CHAN fact
	
	// Arrays of records that link to this record
	// and counters
	protected $link_array = null;		// Array of links to this object. Format: id, type, gedcomid.
	
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
	protected $disp_as_link = null;		// Sources, Media, Notes, Repo's, privacy check without link privacy
	protected $show_changes = null;		// If changes must be shown
	protected $canedit = null;			// If this record can be edited (facts must be checked separately)
	protected $ischanged = null;		// If this record is changed (added, deleted, changed)
	protected $isdeleted = null;		// If this record is deleted (unapproved)
	protected $isnew = null;			// If this record is new (unapproved)
	protected $isempty = null;			// If this record exists
	protected $israwedited = null;		// If this record is changed using raw editing
	protected $exclude_facts = "";		// Facts that should be excluded while parsing the facts
	protected $hiddenfacts = null;		// False or true that 1 or more facts are hidden
	protected $view = null;				// If preview mode is on
	protected $isuserfav = null;		// If this object is a favorite of the current user
	
	/**
	 * constructor for this class
	 */
	protected function __construct($id, $gedrec="", $gedcomid="") {
		global $show_changes;

		// The class might be called with an ID or with a gedcom record.
		// The gedcom record might be empty, in which case it's a new or non existent record.
		$this->gedrec = trim($gedrec);
		$this->show_changes = $show_changes;
		if (empty($gedcomid)) $this->gedcomid = GedcomConfig::$GEDCOMID;
		else $this->gedcomid = $gedcomid;
	
		if ($this->gedrec == "") {
			$this->xref = strtoupper($id);
			$this->type = $this->datatype;
			// Get the gedcom record
			switch ($this->type) {
				case "INDI": $this->ReadPersonRecord(); break;
				case "FAM": $this->ReadFamilyRecord(); break;
				case "SOUR": $this->ReadSourceRecord(); break;
				case "REPO": $this->ReadRepositoryRecord(); break;
				case "OBJE": $this->ReadMediaRecord(); break;
				case "NOTE": $this->ReadNoteRecord(); break;
				case "SUBM": $this->ReadSubmitterRecord(); break;
				case "HEAD": $this->ReadHeaderRecord(); break;
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
			if (!empty($this->gedrec)) $this->gedrec = trim($this->gedrec)."\r\n";
				
		}
		else {
			$this->isempty = false;
			$this->gedrec = trim($this->gedrec)."\r\n";

			$ct = preg_match("/0 @(.*)@ (\w+)/", $this->gedrec, $match);
			if ($ct>0) {
				$this->xref = trim($match[1]);
				$this->type = trim($match[2]);
			}
		}
		$this->key = JoinKey($this->xref, $this->gedcomid);
	}
	

	public function __get($property) {
		global $TEXT_DIRECTION;
	
		switch ($property) {
			case "gedrec":
				return $this->gedrec;
				break;
			case "privategedrec":
				return $this->GetPrivateGedrec(0);
				break;
			case "oldprivategedrec":
				return $this->GetPrivateGedrec(-1);
				break;
			case "newprivategedrec":
				return $this->GetPrivateGedrec(1);
				break;
			case "gedcomid":
				return $this->gedcomid;
				break;
			case "disp":
				return $this->DisplayDetails();
				break;
			case "disp_name":
				return $this->DispName();
				break;
			case "disp_as_link":
				return $this->DisplayDetails(false);
				break;
			case "isempty":
				return $this->isempty;
				break;
			case "show_changes":
				return $this->show_changes;
				break;
			case "canedit":
				return $this->CanEdit();
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
			case "key":
				return $this->key;
				break;
			case "xref":
				return $this->xref;
				break;
			case "type":
				return $this->type;
				break;
			case "addxref":
				if (GedcomConfig::$SHOW_ID_NUMBERS && $this->DispName()) {
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
			case "hiddenfacts":
				if (is_null($this->facts)) $this->ParseFacts();
				return $this->hiddenfacts;
				break;
			case "view":
				return $this->IsPreview();
				break;
			case "isuserfav":
				return $this->IsUserFav();
				break;
			default:
				PrintGetSetError($property, get_class($this), "get");
				break;
		}
	}

	public function __set($property, $value) {
		switch ($property) {
			default:
				PrintGetSetError($property, get_class($this), "set");
				break;
		}
	}

	protected function DisplayDetails($links=true) {
		
		if ($links) {
			if (is_null($this->disp)) {
				$this->disp = $this->CheckAccess(1, $links);
			}
			return $this->disp;
		}
		else {
			if (is_null($this->disp_as_link)) {
				$this->disp_as_link = $this->CheckAccess(1, $links);
			}
			return $this->disp_as_link;
		}
	}
	
	protected function DispName() {
		
		if (is_null($this->disp_name)) {
			$this->disp_name = $this->DisplayDetails();
			if (!$this->disp) {
				if ($this->datatype == "INDI") $this->disp_name = $this->showLivingName();
				else if ($this->datatype == "FAM") {
					$this->disp_name = (($this->husb_id == "" ? true : $this->husb->DispName()) && ($this->wife_id == "" ? true : $this->wife->DispName()));
				}
			}
		}
		return $this->disp_name;
	}
		
	protected function CanEdit() {
		global $gm_user;
		
		if (is_null($this->canedit)) {
			if ($this->DisplayDetails() && GedcomConfig::$ALLOW_EDIT_GEDCOM && $gm_user->userCanEdit() && !PrivacyFunctions::FactEditRestricted($this->xref, $this->gedrec, 1)) $this->canedit = true;
			else $this->canedit = false;
		}
		return $this->canedit;
	}
	
	
	protected function ThisDeleted() {
		
		if (is_null($this->isdeleted)) {
			if (!$this->ThisChanged()) $this->isdeleted = false;
			else {
				$ch = trim($this->GetChangedGedRec());
				if (empty($ch)) $this->isdeleted = true;
				else $this->isdeleted = false;
			}
		}
		return $this->isdeleted;
	}
		
	protected function ThisChanged() {
		
		if (is_null($this->ischanged)) {
			if (!empty($this->xref)) {
				if (ChangeFunctions::GetChangeData(true, $this, true, "", "")) $this->ischanged = true;
				else $this->ischanged = false;
			}
			else $this->ischanged = false;
		}
		return $this->ischanged;
	}
	
	protected function ThisNew() {
		if (is_null($this->isnew)) {
			if (empty($this->gedrec) && ChangeFunctions::GetChangeData(true, $this, true, "", "")) {
				$this->isnew = true;
				$this->ischanged = true;
			}
			else $this->isnew = false;
		}
		return $this->isnew;
	}
		
	protected function ThisRawEdited() {
		
		if (is_null($this->israwedited) && $this->show_changes) {
			$sql = "SELECT COUNT(ch_id) FROM ".TBLPREFIX."changes WHERE ch_file='".$this->gedcomid."' AND ch_type='edit_raw' AND ch_gid='".$this->xref."'";
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
		
		if (!$this->ThisChanged()) return "";
		if (is_null($this->changedgedrec)) {
			$rec = ChangeFunctions::GetChangeData(false, $this, true, "gedlines", "");
			$this->changedgedrec = (isset($rec[$this->gedcomid][$this->xref]) ? $rec[$this->gedcomid][$this->xref] : "");
		}
		return $this->changedgedrec;
	}			

	/**
	 * Parse the facts from the record
	 */
	protected function parseFacts($selection="", $exclude_links=false) {

		if (!is_null($this->facts) && !is_array($selection)) return $this->facts;
		$facts = array();
		if (!$this->DisplayDetails()) {
			$this->hiddenfacts = true;
			return $facts;
		}

		// Get the subrecords/facts. Don't apply privacy here, as it will disturb
		// the fact numbering which is needed for editing (get the right fact number)
		$allsubs = GetAllSubrecords($this->gedrec, $this->exclude_facts, false, false, false);
		$added = false;
		if (is_array($allsubs)) {
			foreach ($allsubs as $key => $subrecord) {
				$added = false;
				$ft = preg_match("/1\s(\w+)(.*)/", $subrecord, $match);
				if ($ft>0) {
					$fact = $match[1];
					if (strstr($match[2], "@") && in_array($fact, array("INDI", "CHIL", "HUSB", "WIFE", "FAMC", "FAMS", "SOUR", "OBJE", "NOTE", "ASSO"))) {
						if ($exclude_links) continue;
						$gid = trim(str_replace("@", "", $match[2]));
					}
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
				if (!empty($fact) && (!is_array($selection) || in_array($fact, $selection))&& $typeshow && !PrivacyFunctions::FactViewRestricted($this->xref, $subrecord, 2)) {
					$factobj = new Fact($this->xref, $this->datatype, $this->gedcomid, $fact, $subrecord, $count[$fact], "");
					if ($factobj->isdeleted) {
						$count[$fact]--;
					}
					$dispobj = $factobj->show;
					if (!empty($gid) && $dispobj) {
						$object =& ConstructObject($gid, $fact, $this->gedcomid, "");
						$disp = $object->DisplayDetails();
					}
					if ($dispobj && (empty($gid) || $disp)) {
						$facts[] = $factobj;
						$added = true;
						if (!is_array($selection)) {
							if ($fact == "SOUR") $this->sourfacts_count++;
							elseif ($fact == "NOTE") $this->notefacts_count++;
							elseif ($fact == "OBJE") $this->mediafacts_count++;
						}
					}
				}
				if (!$added) $this->hiddenfacts = true;
			}
		}
		// if we don't show changes, don't parse the facts
		if ($this->show_changes) {
			// Set the deleted status
			$this->ThisDeleted();
			// Add the new facts first
			$newrecs = ChangeFunctions::RetrieveNewFacts($this->xref);
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
					if (!is_array($selection) || in_array($fact, $selection)) {
						$added = true;
						$facts[] = new Fact($this->xref, $this->datatype, $this->gedcomid, $fact, $newrec, $count[$fact], "ChangeNew");
						// We also must raise the counters for new links, otherwise the tab on the detail pages won't display
						if (!is_array($selection)) {
							if ($fact == "SOUR") $this->sourfacts_count++;
							elseif ($fact == "NOTE") $this->notefacts_count++;
							elseif ($fact == "OBJE") $this->mediafacts_count++;
						}
					}
				}
				if (!$added) $this->hiddenfacts = true;
			}
			// After sorting, add the changed facts at the appropriate places
			SortFactObjs($facts, $this->type);
			
			$newfacts = array();
			foreach($facts as $key => $factobj) {
				// For a new fact, always show
				if ($factobj->style == "ChangeNew") {
					// but if the record is deleted, show everything as old
					if ($this->isdeleted) $factobj->style = "ChangeOld";
					$newfacts[] = $factobj;
				}
				else {
					// if anything changed but is not new........
					if ($factobj->style != "ChangeNew" && ($this->isdeleted || ChangeFunctions::IsChangedFact($this->xref, $factobj->factrec))) {
						// if the record is changed, also show the new value
						$cfact = ChangeFunctions::RetrieveChangedFact($this->xref, $factobj->fact, $factobj->factrec);
//						if ($factobj->fact == "OBJE") print "cfact: ".$cfact;
						// if only a fact is changed/deleted.....
						if (!$this->isdeleted) {
							// Add the old fact.
							$factobj->style = "ChangeOld";
							$newfacts[] = $factobj;
							// an empty record indicates deletion, so only add the new record if not empty
							if (!empty($cfact)) {
								$newfact = new Fact($this->xref, $this->datatype, $this->gedcomid, $factobj->fact, $cfact, $factobj->count, "ChangeNew");
								// add the new fact
//								print "<br />added:";
								$newfacts[] = $newfact;
							}
						}
						// The record is deleted. Show the latest visible value of the fact
						else {
							if (!empty($cfact)) $factobj->factrec = $cfact;
							$factobj->style = "ChangeOld";
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
			$facts = $newfacts;
		}
		else SortFactObjs($facts, $this->type);
		if ($this->type == "INDI") {
//			$sexfound = false;
			foreach($facts as $key => $factobj) {
				if ($factobj->fact == "NAME" || $factobj->fact == "SEX") $this->globalfacts[] = $factobj;
//				elseif ($factobj->fact == "SEX") {
//					$this->globalfacts[] = $factobj;
//					$sexfound = true;
//				}
			}
	
			//-- add a new sex fact if one was not found
//			if (!$sexfound) {
//				$this->globalfacts[] = array('new', "1 SEX U");
//				$this->globalfacts[] = new Fact($this->xref, $this->datatype, $this->gedcomid, "SEX", "1 SEX U", 1, "");
//			}
		}
		if (!is_array($selection)) $this->facts = $facts;

		return $facts;
	}
	
	public function	SelectFacts($factarr, $exclude_links=false) {
		
		//print "select ";
		//print_r($factarr);
		//print " present: ".count($this->facts)."<br />";
		// if the facts are already parsed, we taken them from the fact array
		if (!is_null($this->facts)) {
			$facts = array();
			// We must retain the order of the fact array
			if (is_array($factarr)) {
				foreach ($factarr as $key => $fact) {
					foreach ($this->facts as $key => $factobj) {
						if ($factobj->fact == $fact && ($exclude_links == false || $factobj->linktype == "")) {
//							print "<br />yes: ".$factobj->fact." ".$factobj->style." ";
							$facts[] = $factobj;
						}
//						else print "<br />not: ".$factobj->fact;
					}
				}
			}
			else if ($factarr == "" && $exclude_links) {
				foreach ($this->facts as $key => $factobj) {
					if ($factobj->linktype == "") {
						//print "Added: ".$factobj->fact." ";
						$facts[] = $factobj;
					}
					//else print "rejected: ".$factobj->fact." ".$factobj->linktype."<br />";
				}
			}
			else return $this->facts;
			//print "returning ".count($facts)." facts<br />";
			return $facts;
		}
		// If not, we just select the facts that we need, to prevent unnecessary privacy checking 
		// This causes load if link privacy is enabled
		else {
			return $this->ParseFacts($factarr, $exclude_links);
		}
	}
	
	
	protected function GetLastChangeDate() {
		
		if (is_null($this->lastchanged)) {
			$this->lastchanged = GetGedcomValue("CHAN:DATE", 1, $this->gedrec, "", false);
			$add = GetGedcomValue("CHAN:DATE:TIME", 1, $this->gedrec, "", false);
			if ($add) $this->lastchanged .= " ".$add;
			$this->lastchanged = strtotime($this->lastchanged);
		}
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
	
	private function CheckAccess($recursive=1, $checklinks=false) {
		global $USE_RELATIONSHIP_PRIVACY, $CHECK_MARRIAGE_RELATIONS, $MAX_RELATION_PATH_LENGTH;
		global $global_facts, $person_privacy, $user_privacy, $HIDE_LIVE_PEOPLE, $SHOW_DEAD_PEOPLE, $MAX_ALIVE_AGE, $PRIVACY_BY_YEAR;
		global $PRIVACY_CHECKS, $PRIVACY_BY_RESN, $SHOW_SOURCES, $SHOW_LIVING_NAMES, $LINK_PRIVACY, $gm_user;
		static $hit;
	
					
		if ($this->isempty) return true;
	
		// Check the gedcom context
		$oldgedid = GedcomConfig::$GEDCOMID;
		if (GedcomConfig::$GEDCOMID != $this->gedcomid) SwitchGedcom($this->gedcomid);
		
		$ulevel = $gm_user->getUserAccessLevel();
		if (!isset($hit)) $hit = array();

		if (DEBUG && isset($hit[$this->key][$checklinks])) print "ERROR: getting checked twice: ".$this->key."<br />";
		else $hit[$this->key][$checklinks] = 1;
		
		if (!isset($PRIVACY_CHECKS)) $PRIVACY_CHECKS = 1;
		else $PRIVACY_CHECKS++;
		// print "checking privacy for pid: $this->xref, type: $this->datatype<br />";
	
		//-- look for an Ancestral File level 1 RESN (restriction) tag. This overrules all other settings if it prevents showing data.
		if (isset($PRIVACY_BY_RESN) && ($PRIVACY_BY_RESN==true)) {
			if (PrivacyFunctions::FactViewRestricted($this->xref, $this->gedrec, 1)) {
				SwitchGedcom($oldgedid);
				return false;
			}
		}
	
		// If a user is logged on, check for user related privacy first---------------------------------------------------------------
		if ($gm_user->username != "") {
			// Check user privacy for all users (hide/show)
			if (isset($user_privacy["all"][$this->xref])) {
				if ($user_privacy["all"][$this->xref] == 1) {
					SwitchGedcom($oldgedid);
					return true;
				}
				else {
					SwitchGedcom($oldgedid);
					return false;
				}
			}
			// Check user privacy for this user (hide/show)
			if (isset($user_privacy[$gm_user->username][$this->xref])) {
				if ($user_privacy[$gm_user->username][$this->xref] == 1) {
					SwitchGedcom($oldgedid);
					return true;
				}
				else {
					SwitchGedcom($oldgedid);
					return false;
				}
			}
			// Check person privacy (access level)
			if (isset($person_privacy[$this->xref])) {
				if ($person_privacy[$this->xref] >= $ulevel) {
					SwitchGedcom($oldgedid);
					return true;
				}
				else {
					SwitchGedcom($oldgedid);
					return false;
				}
			}
			
			// Check privacy by isdead status
			if ($this->datatype == "INDI") {
				$isdead = $this->GetDeathStatus();
				// The person is still hidden at this point and cannot be shown, either dead or alive.
				// Check the relation privacy. If within the range, people can be shown. 
				if ($USE_RELATIONSHIP_PRIVACY) {
					// If we don't know the user's gedcom ID, we cannot determine the relationship so no reason to show
					if ($gm_user->gedcomid[GedcomConfig::$GEDCOMID] == "") {
						SwitchGedcom($oldgedid);
						return false;
					}
						
					// If it's the user himself, we can show him
					if ($gm_user->gedcomid[GedcomConfig::$GEDCOMID] == $this->xref) {
						SwitchGedcom($oldgedid);
						return true;
					}
					
					// Determine if the person is within range
					$path_length = $MAX_RELATION_PATH_LENGTH;
					// print "get relation ".$gm_user->gedcomid[$GEDCOMID]." with ".$this->xref;
					$user_indi =& Person::GetInstance($gm_user->gedcomid[GedcomConfig::$GEDCOMID]);
					$relationship = GetRelationship($user_indi, $this, $CHECK_MARRIAGE_RELATIONS, $path_length);

					// Only limit access to live people!
					if ($relationship == false && !$this->GetDeathStatus()) {
						SwitchGedcom($oldgedid);
						return false;
					}
					else {
						// A relation is found. Do not return anything, as general rules will apply in this case.
					}
				}
				
				// First check if the person is dead. If so, it can be shown, depending on the setting for dead people.
				if ($this->GetDeathStatus() && $SHOW_DEAD_PEOPLE >= $ulevel) {
					SwitchGedcom($oldgedid);
					return true;
				}
				
				// Alive people. If the user is allowed to see the person, show it.
				if (!$this->GetDeathStatus() && $HIDE_LIVE_PEOPLE >= $ulevel) {
					SwitchGedcom($oldgedid);
					return true;
				}
				
				// No options left to show the person. Return false.
				SwitchGedcom($oldgedid);
				return false;
			}
		}
		
		// This is the part that handles visitors. ---------------------------------------------------------------
		// No need to check user privacy
		//-- check the person privacy array for an exception (access level)
		// NOTE: This checks all record types! So no need to check later with fams, sources, etc.
		if (isset($person_privacy[$this->xref])) {
			if ($person_privacy[$this->xref] >= $ulevel) {
				SwitchGedcom($oldgedid);
				return true;
			}
			else {
				SwitchGedcom($oldgedid);
				return false;
			}
		}
		if ($this->datatype=="INDI") {
	//		 && $HIDE_LIVE_PEOPLE<getUserAccessLevel($username)) 
			//-- option to keep person living if they haven't been dead very long
			// This option assumes a person as living, if (max_alive_age = 120):
			// - Died within the last 95 years
			// - Married within the last 105 years
			// - Born within the last 120 years
			$dead = $this->GetDeathStatus();
			if ($PRIVACY_BY_YEAR) {
				$cyear = date("Y");
				//-- check death record
				$deatrec = GetSubRecord(1, "1 BIRT", $this->gedrec);
				$ct = preg_match("/2 DATE .*(\d\d\d|\d\d\d\d).*/", $deatrec, $match);
				if ($ct>0) {
					$dyear = $match[1];
					if (($cyear-$dyear) <= $MAX_ALIVE_AGE-25) $dead = true;
				}
				//-- check marriage records
				foreach($this->getSpouseFamilyIds() as $indexval => $fam) {
					$family =& Family::GetInstance($fam, "", GedcomConfig::$GEDCOMID);
					//-- check marriage record
					$marrrec = GetSubRecord(1, "1 MARR", $family->gedrec);
					$ct = preg_match("/2 DATE .*(\d\d\d|\d\d\d\d).*/", $marrrec, $match);
					if ($ct>0) {
						$myear = $match[1];
						if (($cyear-$myear) <= $MAX_ALIVE_AGE-15) $dead = true;
					}
				}
	
				//-- check birth record
				$birtrec = GetSubRecord(1, "1 BIRT", $this->gedrec);
				$ct = preg_match("/2 DATE .*(\d\d\d|\d\d\d\d).*/", $birtrec, $match);
				if ($ct>0) {
					$byear = $match[1];
					if (($cyear-$byear) <= $MAX_ALIVE_AGE) $dead = true;
				}
			}
			if (!$dead) {
				// The person is alive, let's see if we can show him
				if ($HIDE_LIVE_PEOPLE >= $ulevel) {
					SwitchGedcom($oldgedid);
					return true;
				}
				else {
					SwitchGedcom($oldgedid);
					return false;
				}
			}
			else {
				// The person is dead, let's see if we can show him
				if ($SHOW_DEAD_PEOPLE >= $ulevel) {
					SwitchGedcom($oldgedid);
					return true;
				}
				else {
					SwitchGedcom($oldgedid);
					return false;
				}
			}
		}
		
		// Now check the fams, for visitors AND for other users. Previously only INDI's are handled for users, not fams and other record types.
	    if ($this->datatype == "FAM") {
		    $type = "AND";
		    //-- check if we can display both parents. If not, the family will be hidden.	
		    $ct1 = preg_match("/1 HUSB @(.*)@/", $this->gedrec, $match);
			if ($ct1 > 0) {
				$husb =& Person::GetInstance($match[1], "", GedcomConfig::$GEDCOMID);
				if ($type == "AND" && !$husb->DisplayDetails()) {
					SwitchGedcom($oldgedid);
					return false;
				}
			}
		    $ct2 = preg_match("/1 WIFE @(.*)@/", $this->gedrec, $match);
			if ($ct2 > 0) {
				$wife =& Person::GetInstance($match[1], "", GedcomConfig::$GEDCOMID);
				if ($type == "AND" && !$wife->DisplayDetails()) {
					SwitchGedcom($oldgedid);
					return false;
				}
			}
			if ($type == "OR" && ($ct1 && !$husb->DisplayDetails()) && ($ct2 && !$wife->DisplayDetails())) {
				SwitchGedcom($oldgedid);
				return false;
			}
			SwitchGedcom($oldgedid);
			return true;
	    }
	    
	    // Check the sources. First check the general setting
	    if ($this->datatype == "SOUR") {
		    if ($SHOW_SOURCES >= $ulevel) {
			    $disp = true;
			    
			    // If we can show the source, see if any links to hidden records must prevent this.
			    // Only hide if a linked RECORD is hidden. Don't hide if a LINK is hidden
			    if ($LINK_PRIVACY && $checklinks) {
				    // This will prevent loops if MM points to SOUR vice versa. We only go one level deep.
				    $recursive--;
				    if ($recursive >= 0) {
					    $disp = $this->CheckSourceLinks();
				    }
				    $recursive++;
			    }
			    // We can show the source, and there are no links that prevent this
				SwitchGedcom($oldgedid);
			    if ($disp) {
					return true;
				}
				// The links prevent displaying the source
				else {
					return false;
				}
			}
			// The sources setting prevents display, so hide!
			else {
				SwitchGedcom($oldgedid);
				return false;
			}
	    }
	    
	    // Check the repositories
	    if ($this->datatype == "REPO") {
		    if ($SHOW_SOURCES >= $ulevel) {
			    $disp = true;
			    if ($LINK_PRIVACY && $checklinks) {
				    // This will prevent loops if MM points to SOUR vice versa. We only go one level deep.
				    $recursive--;
				    if ($recursive >=0) {
					    $disp = $this->CheckRepoLinks();
				    }
				    $recursive++;
			    }
			    // We can show the repo, and there are no links that prevent this
				SwitchGedcom($oldgedid);
			    if ($disp) {
					return true;
				}
				// The links prevent displaying the source
				else {
					return false;
				}
		    }
			else {
				SwitchGedcom($oldgedid);
				return false;
			}
	    }
	    
	    // Check the MM objects
	    if ($this->datatype == "OBJE") {
		    // Check if OBJE details are hidden by global or specific facts settings
		    if (PrivacyFunctions::ShowFactDetails("OBJE", $this->xref)) {
			    $disp = true;
			    // Check links to the MM record. Only hide if a linked RECORD is hidden. Don't hide if a LINK is hidden
			    if ($LINK_PRIVACY) {
				    $recursive--;
				    if ($recursive >=0) {
					    $disp = $this->CheckMediaLinks();
			    	}
				    $recursive++;
			    }
				SwitchGedcom($oldgedid);
			    if ($disp) {
				    return true;
			    }
			    else {
				    // we cannot show it because of hidden links
					return false;
				}
			}
			// we cannot show the MM details
			else {
				SwitchGedcom($oldgedid);
				return false;
			}
	    }
	    // Check the Note objects
	    if ($this->datatype == "NOTE") {
		    // Check if NOTE details are hidden by global or specific facts settings
		    if (PrivacyFunctions::ShowFactDetails("NOTE", $this->xref)) {
			    $disp = true;
			    // Check links to the note record. Only hide if a linked RECORD is hidden. Don't hide if a LINK is hidden
			    if ($LINK_PRIVACY) {
				    $recursive--;
				    if ($recursive >=0) {
					    $disp = $this->CheckNoteLinks();
			    	}
				    $recursive++;
			    }
				SwitchGedcom($oldgedid);
			    if ($disp) {
				    return true;
			    }
			    else {
				    // we cannot show it because of hidden links
					return false;
				}
			}
			// we cannot show the Note details
			else {
				SwitchGedcom($oldgedid);
				return false;
			}
	    }
		SwitchGedcom($oldgedid);
	    return true;
	}
	
	private function CheckSourceLinks() {
		
		if (!is_array($this->link_array)) {
			$sql = "SELECT DISTINCT n_id, i_id, i_key, i_isdead, i_file, i_gedrec, n_name, n_surname, n_nick, n_letter, n_fletter, n_type FROM ".TBLPREFIX."source_mapping, ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE sm_key='".$this->key."' AND sm_file='".$this->gedcomid."' AND sm_type='INDI' AND sm_gid=i_id AND sm_file=i_file AND i_key=n_key ORDER BY i_key, n_id";
			$res = NewQuery($sql);
			$key = "";
			$ok = true;
			while ($row = $res->FetchAssoc()) {
				if ($key != $row["i_key"]) {
					if ($key != "") $person->names_read = true;
					if (!$ok) {
						$res->FreeResult();
						return false;
					}
					$person = null;
					$key = $row["i_key"];
					$person = Person::GetInstance($row["i_id"], $row, $row["i_file"]);
					if (!$person->DisplayDetails()) {
						$ok = false;
						// Don't return here, first add all names to the person
					}
				}
				if ($person->disp_name) $person->addname = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_nick"], $row["n_type"], $row["n_fletter"]);
			}
			if ($key != "") $person->names_read = true;
			if (!$ok) return false;
			
			$indiarr = array();
			$famarr = array();
			$sql = "SELECT DISTINCT sm_gid, f_id, f_file, f_gedrec, f_husb, f_wife FROM ".TBLPREFIX."source_mapping, ".TBLPREFIX."families WHERE sm_key='".$this->key."' AND sm_file='".$this->gedcomid."' AND sm_type='FAM' AND sm_gid=f_id AND sm_file=f_file";
			$res = NewQuery($sql);
			while ($row = $res->FetchAssoc()) {
				$family = null;
				$family = Family::GetInstance($row["f_id"], $row, $row["f_file"]);
				$famarr[] = $family;
				if ($row["f_husb"] != "") $indiarr[] = $row["f_husb"];
				if ($row["f_wife"] != "") $indiarr[] = $row["f_wife"];
			}
			
			$indiarr = array_flip(array_flip($indiarr));
			
			return $this->IndiFamPrivCheck($indiarr, $famarr);
		}
		// else we presume that the linked objects are already cached somewhere
		else {
			foreach($this->link_array as $key => $link) {
				$obj = null;
				if ($link[1] == "INDI") $obj =& Person::GetInstance($link[0], "", $link[2]);
				else if ($link[1] == "FAM") $obj =& Family::GetInstance($link[0], "", $link[2]);
				if (is_object($obj) && !$obj->DispName()) return false;
			}
		}
		return true;
	}
		
	private function CheckMediaLinks() {

		if (!is_array($this->link_array)) {
			// Check links from sources
			$sql = "SELECT DISTINCT s_key, s_id, s_file, s_gedrec FROM ".TBLPREFIX."sources INNER JOIN ".TBLPREFIX."media_mapping ON mm_gid=s_id AND mm_file=s_file WHERE mm_media='".$this->xref."' AND mm_file='".$this->gedcomid."' AND mm_type='SOUR'";
			$res = NewQuery($sql);
			while ($row = $res->FetchAssoc()) {
				$source =& Source::GetInstance($row["s_id"], $row, $row["s_file"]);
				if (!$source->DisplayDetails(false)) {
					$res->FreeResult();
					return false;
				}
			}
			
			// Check links from individuals
			$sql = "SELECT DISTINCT n_id, i_id, i_key, i_isdead, i_file, i_gedrec, n_name, n_surname, n_nick, n_letter, n_fletter, n_type FROM ".TBLPREFIX."media_mapping, ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE mm_media='".$this->xref."' AND mm_file='".$this->gedcomid."' AND mm_type='INDI' AND mm_gid=i_id AND mm_file=i_file and i_key=n_key ORDER BY i_key, n_id";
			$res = NewQuery($sql);
			$key = "";
			$ok = true;
			while ($row = $res->FetchAssoc()) {
				if ($key != $row["i_key"]) {
					if ($key != "") $person->names_read = true;
					if (!$ok) {
						$res->FreeResult();
						return false;
					}
					$key = $row["i_key"];
					$person = null;
					$person =& Person::GetInstance($row["i_id"], $row, $row["i_file"]);
					if (!$person->DisplayDetails()) {
						$ok = false;
						// Don't return here, first add all names to the person
					}
				}
				if ($person->disp_name) $person->addname = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_nick"], $row["n_type"], $row["n_fletter"]);
			}
			if ($key != "") $person->names_read = true;
			if (!$ok) return false;
			
			// Check links from families
			$indiarr = array();
			$famarr = array();
			$sql = "SELECT DISTINCT mm_gid, f_id, f_file, f_gedrec, f_husb, f_wife FROM ".TBLPREFIX."media_mapping, ".TBLPREFIX."families WHERE mm_media='".$this->xref."' AND mm_file='".$this->gedcomid."' AND mm_type='FAM' AND mm_gid=f_id AND mm_file=f_file";
			$res = NewQuery($sql);
			while ($row = $res->FetchAssoc()) {
				$family = null;
				$family =& Family::GetInstance($row["f_id"], $row, $row["f_file"]);
				$famarr[] = $family;
				if (!empty($row["f_husb"])) $indiarr[] = $row["f_husb"];
				if (!empty($row["f_wife"])) $indiarr[] = $row["f_wife"];
			}
			
			$indiarr = array_flip(array_flip($indiarr));
			return $this->IndiFamPrivCheck($indiarr, $famarr);
		}
		// else we presume that the linked objects are already cached somewhere
		else {
			foreach($this->link_array as $key => $link) {
				$obj = null;
				if ($link[1] == "INDI") $obj =& Person::GetInstance($link[0], "", $link[2]);
				else if ($link[1] == "FAM") $obj =& Family::GetInstance($link[0], "", $link[2]);
				else if ($link[1] == "SOUR") {
					$obj =& Source::GetInstance($link[0], "", $link[2]);
					if (is_object($obj) && !$obj->DisplayDetails(false)) return false;
				}
				if (is_object($obj) && !$obj->DispName()) return false;
			}
		}
		return true;
	}
		
	private function CheckNoteLinks() {
		
		$sql = "SELECT DISTINCT n_id, i_id, i_key, i_isdead, i_file, i_gedrec, n_name, n_surname, n_nick, n_letter, n_fletter, n_type FROM ".TBLPREFIX."other, ".TBLPREFIX."other_mapping, ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE o_id='".$this->xref."' AND o_file='".$this->gedcomid."' AND o_id = om_oid AND o_file = om_file AND om_type='INDI' AND i_id=om_gid AND om_file=i_file AND i_key=n_key ORDER BY i_key, n_id";
		$res = NewQuery($sql);
		$key = "";
		$ok = true;
		while ($row = $res->FetchAssoc()) {
			if ($key != $row["i_key"]) {
				if ($key != "") $person->names_read = true;
				if (!$ok) {
					$res->FreeResult();
					return false;
				}
				$key = $row["i_key"];
				$person = null;
				$person =& Person::GetInstance($row["i_id"], $row, $row["i_file"]);
				if (!$person->DisplayDetails()) {
					$ok = false;
					// Don't return here, first add all names to the person
				}
			}
			if ($person->disp_name) $person->addname = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_nick"], $row["n_type"], $row["n_fletter"]);
		}
		if ($key != "") $person->names_read = true;
		if (!$ok) return false;
		
		$indiarr = array();
		$famarr = array();
		$sql = "SELECT DISTINCT om_gid, f_id, f_file, f_gedrec, f_husb, f_wife FROM ".TBLPREFIX."other, ".TBLPREFIX."other_mapping, ".TBLPREFIX."families WHERE o_id='".$this->xref."' AND o_file='".$this->gedcomid."' AND o_id = om_oid AND o_file = om_file AND om_type='FAM' AND f_id=om_gid AND om_file=f_file";
		$res = NewQuery($sql);
		while ($row = $res->FetchAssoc()) {
			$family = null;
			$family =& Family::GetInstance($row["f_id"], $row, $row["f_file"]);
			$famarr[] = $family;
			if (!empty($row["f_husb"])) $indiarr[] = $row["f_husb"];
			if (!empty($row["f_wife"])) $indiarr[] = $row["f_wife"];
		}
		
		$indiarr = array_flip(array_flip($indiarr));
		return $this->IndiFamPrivCheck($indiarr, $famarr);
	}

	private function CheckRepoLinks() {
		
		$sql = "SELECT s_id, s_file, s_gedrec FROM ".TBLPREFIX."other_mapping, ".TBLPREFIX."sources WHERE om_oid='".$this->xref."' AND om_file='".$this->gedcomid."' AND s_file=om_file AND s_id=om_gid";
		$res = Newquery($sql);
		while ($row = $res->FetchAssoc()) {
			$source = null;
			$source =& Source::GetInstance($row["s_id"], $row, $row["s_file"]);
			if (!$source->DisplayDetails(false)) {
				$res->FreeResult();
				return false;
			}
		}
		return true;
	}
		
	private function IndiFamPrivCheck($indiarr, $famarr) {
		
		if (count($indiarr) != 0) {
			$key = "";
			$sql = "SELECT DISTINCT n_id, i_key, i_id, i_isdead, i_file, i_gedrec, n_name, n_surname, n_nick, n_letter, n_fletter, n_type FROM ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE n_key=i_key AND i_key IN ('".implode("','", $indiarr)."') ORDER BY i_key, n_id";
			$res = NewQuery($sql);
			while ($row = $res->FetchAssoc()) {
				if ($key != $row["i_key"]) {
					if ($key != "") $person->names_read = true;
					$key = $row["i_key"];
					$person = null;
					$person =& Person::GetInstance($row["i_id"], $row, $row["i_file"]);
				}
				if ($person->disp_name) $person->addname = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_nick"], $row["n_type"], $row["n_fletter"]);
			}
			if ($key != "") $person->names_read = true;
		}
		foreach($famarr as $key => $family) {
			if (!$family->DisplayDetails()) return false;
		}
		return true;
		
	}
	
	// Type is used to indicate what gedrec to privatize:
	// -1: old gedrec
	//  0: gedrec that is used depending on the user rights to see changes
	//  1: new gedrec 
	// Otherwise it will depend on the access level to determine if old/new is used.
	private function GetPrivateGedrec($type=-1) {
		global $gm_user;
	
		if ($type == 0) {
			$gedrec = $this->gedrec;
			if ($gm_user->userCanEdit()) {
				if ($this->ThisChanged()) $gedrec = trim($this->GetChangedGedrec());
			}
		}
		else if ($type == 1) {
			if ($gm_user->userCanEdit()) {
				if ($this->ThisChanged()) $gedrec = trim($this->GetChangedGedrec());
				else $gedrec = $this->gedrec;
			}
			else $gedrec = "";
		}
		else $gedrec = $this->gedrec;
		if (trim($gedrec) == "") return "";

		//-- check if the whole record is private
		if (!$this->DisplayDetails()) {
			//-- check if name should be private
			if ($this->type == "INDI") {
				$newrec = "0 @".$this->xref."@ INDI\r\n";
				if (!$this->DispName()) {
					$newrec .= "1 NAME " . GM_LANG_private . " /" . GM_LANG_private . "/" . "\r\n";
					$newrec .= "2 SURN " . GM_LANG_private . "\r\n";
					$newrec .= "2 GIVN " . GM_LANG_private . "\r\n";
				}
				else {
					// Add the names
					$cnt=1;
					do {
						$namerec = GetSubRecord(1, "1 NAME", $gedrec, $cnt);
						if (!empty($namerec)) $newrec .= $namerec;
						$cnt++;
					} while (!empty($namerec));
				}
				// Always add famlinks
				$ct = preg_match_all("/1\s+FAMS\s+@(.*)@.*/", $gedrec, $fmatch, PREG_SET_ORDER);
				if ($ct>0) {
					foreach($fmatch as $key => $value) {
						$newrec .= "1 FAMS @".$value[1]."@\r\n";
					}
				}
				$ct = preg_match_all("/1\s+FAMC\s+@(.*)@.*/", $gedrec, $fmatch, PREG_SET_ORDER);
				if ($ct>0) {
					foreach($fmatch as $key => $value) {
						$newrec .= "1 FAMC @".$value[1]."@\r\n";
					}
				}
			}
			else if ($this->type == "SOUR") {
				$newrec = "0 @".$this->xref."@ SOUR\r\n";
				$newrec .= "1 TITL ".GM_LANG_private."\r\n";
			}
			else if ($this->type == "OBJE") {
				$newrec = "0 @".$this->xref."@ OBJE\r\n";
				$newrec .= "1 FILE\r\n2 TITL ".GM_LANG_private."\r\n";
			}
			else if ($this->type == "REPO") {
				$newrec = "0 @".$this->xref."@ REPO\r\n";
				$newrec .= "1 NAME ".GM_LANG_private."\r\n";
			}
			else if ($this->type == "NOTE") {
				$newrec = "0 @".$this->xref."@ NOTE ".GM_LANG_private."\r\n";
			}
			else if ($this->type == "FAM") {
				$newrec = "0 @".$this->xref."@ FAM\r\n";
				// Always add fam links
			    $ct1 = preg_match("/1 HUSB @(.*)@/", $gedrec, $match);
				if ($ct1 > 0) $newrec .= "1 HUSB @".$match[1]."@\r\n";
			    $ct1 = preg_match("/1 WIFE @(.*)@/", $gedrec, $match);
				if ($ct1 > 0) $newrec .= "1 WIFE @".$match[1]."@\r\n";
				$ct = preg_match_all("/1 CHIL @(.*)@/", $gedrec, $match, PREG_SET_ORDER);
				for($i=0; $i<$ct; $i++) {
					$newrec .= "1 CHIL @".$match[$i][1]."@\r\n";
				}
			}
			else {
				// remain all other types plus indi's that can show their name
				$newrec = "0 @".$this->xref."@ ".$this->type."\r\n";
			}
			if ($this->type != "NOTE") $newrec .= "1 NOTE ".trim(GM_LANG_person_private)."\r\n";
			else $newrec .= "1 CONT ".trim(GM_LANG_person_private)."\r\n";
			return $newrec;
		}
		else {
			if ($this->type == "NOTE") {
				$newrec = MakeCont("0 @".$this->xref."@ NOTE", preg_replace("/\<br \/\>/", "\r\n", $this->GetNoteText(false)),false);
			}
			else $newrec = "0 @".$this->xref."@ ".$this->type."\r\n";

			$allsubs = GetAllSubrecords($gedrec, "", false, false, false);
			if (is_array($allsubs)) {
				foreach ($allsubs as $key => $subrecord) {
					$ft = preg_match("/1\s(\w+)(.*)/", $subrecord, $match);
					if ($ft>0) $fact = $match[1];
					if (!($this->type == "NOTE" && ($fact == "CONC" || $fact == "CONT"))) {
						$fact = new Fact($this->xref, $this->type, $this->gedcomid, $fact, $subrecord);
						if ($fact->disp) $newrec .= $fact->factrec;
					}
				}
			}
			return $newrec;
		}
	}
	
	private function IsUserFav() {
		global $gm_user;
		
		if (is_null($this->isuserfav)) {
			if ($gm_user->username == "") $this->isuserfav = false;
			else $this->isuserfav = FavoritesController::IsUserFav($this->xref, $this->type, $this->gedcomid);
		}
		return $this->isuserfav;
	}
}
?>