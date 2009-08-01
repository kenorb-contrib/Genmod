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

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class Person extends GedcomRecord {

	// General class information
	public $classname = "Person";			// Name of this class
	public $datatype = "INDI";				// Type of data collected here
	private static $personcache = array(); 	// Holder of the instances for this class
	
	private $name = null;					// Printable name of the person, after applying privacy (can be unknown of private)
	private $addname = null;				// Printable addname of the person, after applying privacy (can be blank)
	private $name_array = null;				// Array of names from GetIndiNames
	private $sortable_name = null;			// Sortable name of the person, no privacy applied
	private $sortable_addname = null;		// Sortable addname of the person, no privacy applied
	private $changednames = null;			// Array with old and new values of the names. No privacy applied. Only old names if user cannot edit.
	private $bdate = null;					// The birth date in gedcom 2 DATE xxxxxx format. Privacy is applied.
	private $ddate = null;					// The death date in gedcom 2 DATE xxxxxx format. Privacy is applied.
	private $brec = null;					// The complete birthrecord in gedcom format. Privacy is applied.
	private $drec = null;					// The complete deathrecord in gedcom format. Privacy is applied.
	private $sex = null;					// Gender of the person: M, F, U. Privacy is applied.
	private $age = null;					// Age of the person
	private $highlightedimage = null;		// Array with info on the media item that is primary to show
	
	public $sexdetails = array();	 		// Set by individual controller
	public $label = array();				// Set for relatives relation
	
	private $childfamilies = null;  		// container for array of family objects where this person is child
	private $primaryfamily = null;			// The xref of the parent family that is set as primary for this person
	private $spousefamilies = null;		 	// container for array of family objects where this person is spouse
	private $famc = null;					// container for array of family ID's and relational info where this person is child
	private $fams = null;					// container for array of family ID's where this person is spouse
	
	private $globalfacts = array();			// Array with name and sex facts. Showfact, Factviewrestricted are applied
	
	private $facts_parsed = false;			// True if all facts from this person him/herself are parsed
	private $close_relatives = false;		// True if all labels have been set for display on the individual page; if false the close relatives tab hides
	private $sosamax = 7;					// Maximum sosa number for parents facts
	private $tracefacts = false;			// traces all relatives events that are added to the indifacts array 

	
	public static function GetInstance($xref, $gedrec="", $gedcomid="") {
		if (!isset(self::$personcache[$xref])) {
			self::$personcache[$xref] = new Person($xref, $gedrec, $gedcomid);
		}
		return self::$personcache[$xref];
	}
		
	/**
	 * Constructor for person object
	 * @param string $gedrec	the raw individual gedcom record
	 */
	public function __construct($id, $gedrec="", $changed=false) {
		
		parent::__construct($id, $gedrec);

		$this->tracefacts = false;
		$this->exclude_facts = "";
		
	}
	
	public function __get($property) {
		
		switch ($property) {
			case "name":
				return $this->getName();
				break;
			case "addname":
				return $this->getAddName();
				break;
			case "name_array":
				return $this->GetNameArray();
				break;
			case "sortable_name":
				return $this->getSortableName();
				break;
			case "sortable_addname":
				return $this->getSortableAddName();
				break;
			case "changednames":
				return $this->GetChangedNames();
				break;
			case "bdate":
				return $this->GetBirthDate();
				break;
			case "ddate":
				return $this->GetDeathDate();
				break;
			case "brec":
				return $this->GetBirthRecord();
				break;
			case "drec":
				return $this->GetDeathRecord();
				break;
			case "sex":
				return $this->GetSex();
				break;
			case "highlightedimage":
				return $this->FindHighlightedMedia();
				break;
			case "facts":
				if (!$this->facts_parsed) $this->ParseIndiFacts();
//				SortFacts($this->facts, $this->type);
				return $this->facts;
				break;
			case "globalfacts":
				if (!$this->facts_parsed) $this->ParseIndiFacts();
				return $this->globalfacts;
				break;
			case "sourfacts_count":
				if (!$this->facts_parsed) $this->ParseIndiFacts();
				return $this->sourfacts_count;
				break;
			case "notefacts_count":
				if (!$this->facts_parsed) $this->ParseIndiFacts();
				return $this->notefacts_count;
				break;
			case "mediafacts_count":
				if (!$this->facts_parsed) $this->ParseIndiFacts();
				return $this->mediafacts_count;
				break;
			case "childfamilies":
				return $this->GetChildFamilies();
				break;
			case "primaryfamily":
				return $this->GetPrimaryChildFamily();
				break;
			case "spousefamilies":
				return $this->GetSpouseFamilies();
				break;
			case "famc":
				return $this->GetChildFamilyIds();
				break;
			case "close_relatives":
				return $this->close_relatives;
				break;
			case "title":
				return $this->GetName();
				break;
			default:
				return parent::__get($property);
				break;
		}
	}

	
	
	protected function getActionList() {
		global $Actions;
		
		if (is_null($this->actionlist)) {
			if (is_object($Actions)) $this->actionlist = $Actions->GetActionListByID($this->xref);
		}
		return $this->actionlist;
			
	}
		
	private function getName() {
		global $gm_lang;
		
		if (is_null($this->name)) {
			if (!$this->disp_name) $this->name = $gm_lang["private"];
			else {
				if ($this->show_changes && $this->ThisChanged()) $gedrec = $this->GetChangedGedRec();
				else $gedrec = $this->gedrec;
				$this->name = PrintReady(GetPersonName($this->xref, $gedrec));
			}
			if ($this->name == "") $this->name = $gm_lang["unknown"];
		}
		return $this->name;
	}
	
	private function getAddName() {
		
		if (is_null($this->addname)) {
			if (!$this->disp_name) $this->addname = "";
			else {
				if ($this->show_changes && $this->ThisChanged()) $gedrec = $this->GetChangedGedRec();
				else $gedrec = $this->gedrec;
				$this->addname = PrintReady(GetAddPersonName($this->xref, $gedrec));
			}
		}
		return $this->addname;
	}

	private function getNameArray() {
		
		if (is_null($this->name_array)) {
			if ($this->show_changes && $this->ThisChanged()) $gedrec = $this->GetChangedGedRec();
			else $gedrec = $this->gedrec;
			$this->name_array = GetIndiNames($gedrec);
		}
		return $this->name_array;
	}

	private function GetSortableName() {
		
		if (is_null($this->sortable_name)) {
			if ($this->show_changes && $this->ThisChanged()) $this->sortable_name = GetSortableName($this->xref, "", "", false, false, true);
			else $this->sortable_name = GetSortableName($this->xref);
		}
		return $this->sortable_name;
	}
	private function GetSortableAddName() {
		
		if (is_null($this->sortable_addname)) {
			if ($this->show_changes && $this->ThisChanged()) $gedrec = $this->GetChangedGedRec();
			else $gedrec = $this->gedrec;
			$this->sortable_addname = GetSortableAddName($this->xref, $gedrec);
		}
		return $this->sortable_addname;
	}

	private function GetChangedNames() {
		
		// NOTE: Get the array of old/new names, for display on indipage
		if (is_null($this->changednames)) {
			$this->changednames = GetChangeNames($this->xref);
		}
		return $this->changednames;
	}
	
	/**
	 * get highlighted media
	 * @return array
	 */
	private function findHighlightedMedia() {
		
		if (is_null($this->highlightedimage)) {
			$this->highlightedimage = FindHighlightedObject($this->xref, $this->gedrec);
		}
		return $this->highlightedimage;
	}
	
	/**
	 * parse birth and death records
	 */
	private function parseBirthDeath() {
		global $MAX_ALIVE_AGE; 

		if (is_null($this->bdate) || is_null($this->ddate) || is_null($this->brec) || is_null($this->drec)) {
			if ($this->show_changes && $this->ThisChanged()) $gedrec = $this->GetChangedGedRec();
			else $gedrec = $this->gedrec;
			
			$subrecord = GetSubRecord(1, "1 BIRT", $gedrec);
			if ($this->disp && ShowFact("BIRT", $this->xref, "INDI") && ShowFactDetails("BIRT", $this->xref) && !FactViewRestricted($this->xref, $subrecord, 2)) {
				$this->brec = $subrecord;
 				$this->bdate = GetSubRecord(2, "2 DATE", $this->brec);
			}
			else {
				$this->brec = "";
				$this->bdate = "";
			}
			$subrecord = GetSubRecord(1, "1 DEAT", $gedrec);
			if ($this->disp && ShowFact("DEAT", $this->xref, "INDI") && ShowFactDetails("DEAT", $this->xref) && !FactViewRestricted($this->xref, $subrecord, 2)) {
				$this->drec = $subrecord;
				$this->ddate = GetSubRecord(2, "2 DATE", $this->drec);
			}
			else {
				$this->drec = "";
				$this->ddate = "";
			}
			
			if (empty($this->ddate) && !empty($this->bdate)) {
				$pdate=ParseDate(substr($this->bdate,6));
				if ($pdate[0]["year"]>0) $this->ddate = "2 DATE BEF ".($pdate[0]["year"]+$MAX_ALIVE_AGE);
			}
			if (empty($this->bdate) && !empty($this->ddate)) {
				$pdate=ParseDate(substr($this->ddate,6));
				if ($pdate[0]["year"]>0) $this->bdate = "2 DATE AFT ".($pdate[0]["year"]-$MAX_ALIVE_AGE);
			}
		}
		return;
	}
	
	private function GetBirthDate() {
		
		if (is_null($this->bdate)) $this->ParseBirthDeath();
		return $this->bdate;
	}
	
	private function GetDeathDate() {
		
		if (is_null($this->ddate)) $this->ParseBirthDeath();
		return $this->ddate;
	}
	private function GetBirthRecord() {
		
		if (is_null($this->brec)) $this->ParseBirthDeath();
		return $this->brec;
	}
	
	private function GetDeathRecord() {
		
		if (is_null($this->drec)) $this->ParseBirthDeath();
		return $this->drec;
	}
	
	/**
	 * get the person's sex
	 * @return string 	return M, F, or U
	 */
	private function getSex() {

		if (is_null($this->sex)) {
			// if a person is deleted, get his old gender. Otherwise it will show as unknown.
			if ($this->show_changes && $this->ThisChanged() && !$this->Thisdeleted()) $gedrec = $this->GetChangedGedRec();
			else $gedrec = $this->gedrec;

			if (ShowFact("SEX", $this->xref, "INDI") && ShowFactDetails("SEX", $this->xref)) {
				$st = preg_match("/1 SEX (.*)/", $gedrec, $smatch);
				if ($st>0) {
					$smatch[1] = trim($smatch[1]);
					if (empty($smatch[1])) $this->sex = "U";
					else $this->sex = trim($smatch[1]);
				}
				else $this->sex = "U";
			}
			else $this->sex = "U";
		}
		return $this->sex;
	}
	
	public function setFamLabel($fam, $label) {
		$this->label[$fam] = $label;
	}
	
	/**
	 * Parse the facts from the individual record
	 */
	public function parseIndiFacts() {
		
		//-- only run this function once
		if ($this->facts_parsed) return;
		
		//-- don't run this function if privacy does not allow viewing of details
		if (!$this->disp) return;
		$this->facts_parsed = true;
		
		$sexfound = false;
		$this->ParseFacts();
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

	/**
	 * add facts from the family record
	 */
	public function AddFamilyFacts() {
		global $GEDCOM, $nonfacts, $nonfamfacts;
		
		if (!$this->disp) return;
		$this->GetSpouseFamilies();
		foreach ($this->spousefamilies as $key => $fam) {
			// If the family is (partly) private, we must not show facts
			if ($fam->disp) {
				if ($fam->husb_id == $this->xref) $spperson = "wife";
				else $spperson = "husb";
				$fam->ParseFacts();
				// No need to check privacy. Already done in ParseFacts.
				$count_facts = array(); 
				foreach ($fam->facts as $key => $factobj) {
	//				$fact = $factarr[0];
	//				$subrecord = $factarr[1];
	//				$type = $factarr[3];
					if (!isset($count_facts[$factobj->fact])) $count_facts[$factobj->fact] = 1;
					else $count_facts[$factobj->fact]++;
					// -- handle special source fact case
					if (($factobj->fact!="SOUR") && ($factobj->fact!="OBJE") && ($factobj->fact!="NOTE") && ($factobj->fact!="CHAN") && ($factobj->fact!="_UID") && ($factobj->fact!="RIN")) {
						if ((!in_array($factobj->fact, $nonfacts))&&(!in_array($factobj->fact, $nonfamfacts))) {
//							$oldsub = $factobj->factrec;
							$subrecord = trim($factobj->factrec)."\r\n";
							if (is_object($fam->$spperson)) $subrecord.="1 _GMS @".$fam->$spperson->xref."@\r\n";
							$subrecord.="1 _GMFS @".$fam->xref."@\r\n";
if ($this->tracefacts) print "AddFamilyFacts - Adding for ".$fam->xref.": ".$factobj->fact." ".$subrecord."<br />";
							$this->facts[] = new fact($fam->xref, $factobj->fact, $subrecord, $count_facts[$factobj->fact], $factobj->style);
						}
					}
				}
				// Only add spouses and kidsfacts if the family is not hidden.
				if (is_object($fam->$spperson)) $this->AddSpouseFacts($fam, $spperson);
				$this->AddChildrenFacts($fam);
			}
		}
		$this->AddParentsFacts($this);
		$this->add_historical_facts();
		$this->AddAssoFacts($this->xref);
		SortFactObjs($this->facts, $this->type);

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
	private function AddParentsFacts($person, $sosa=1) {
		global $SHOW_RELATIVES_EVENTS, $gm_username, $GEDCOM;
		
		if (!$SHOW_RELATIVES_EVENTS) return;
		if ($sosa > $this->sosamax) return;
		if (!is_object($person)) return;
		$person->GetChildFamilies();
		foreach ($person->childfamilies as $indexval => $fam) {
			if ($fam->disp) {
				if ($fam->husb_id != "") {
					// add father death
					if ($sosa==1) $fact="_DEAT_FATH"; else $fact="_DEAT_GPAR";
					if (strstr($SHOW_RELATIVES_EVENTS, $fact)) {
						if (CompareFacts($this->GetBirthDate(), $fam->husb->GetDeathDate())<0 && CompareFacts($fam->husb->GetDeathDate(), $this->GetDeathDate())<0) {
							$factrec = "1 ".$fact;
							$factrec .= "\n".trim($fam->husb->GetDeathDate());
							$factrec .= "\n2 ASSO @".$fam->husb->xref."@";
							$factrec .= "\n3 RELA ".(GetSosaName($sosa*2));
	if ($this->tracefacts) print "AddParentsFacts sosa ".$sosa."- Adding for ".$fam->xref.": ".$fact." ".$factrec."<br />";
							$this->facts[] = new Fact($fam->xref, "X$fact", $factrec, 0, "");
						}
					}
					if ($sosa==1) $this->AddStepSiblingsFacts($fam->husb, $fam->xref); // stepsiblings with father
					$this->AddParentsFacts($fam->husb, $sosa*2); // recursive call for father ancestors
				}
				
				if ($fam->wife_id != "") {
					// add mother death
					if ($sosa==1) $fact="_DEAT_MOTH"; else $fact="_DEAT_GPAR";
					if (strstr($SHOW_RELATIVES_EVENTS, $fact)) {
						if (CompareFacts($this->GetBirthDate(), $fam->wife->GetDeathDate())<0 && CompareFacts($fam->wife->GetDeathDate(), $this->GetDeathDate())<0) {
							$factrec = "1 ".$fact;
							$factrec .= "\n".trim($fam->wife->GetDeathDate());
							$factrec .= "\n2 ASSO @".$fam->wife->xref."@";
							$factrec .= "\n3 RELA ".(GetSosaName($sosa*2+1));
	if ($this->tracefacts) print "AddParentsFacts sosa ".$sosa."- Adding for ".$fam->xref.": ".$fact." ".$factrec."<br />";
	//print $this->GetBirthDate()."  ".$fam->wife->GetDeathDate()."  ".$fam->wife->GetDeathDate()."  ".$this->GetDeathDate()."<br />";
							$this->facts[] = new Fact($fam->xref, "X$fact", $factrec, 0, "");
						}
					}
					if ($sosa==1) $this->AddStepSiblingsFacts($fam->wife, $fam->xref); // stepsiblings with mother
					$this->AddParentsFacts($fam->wife, $sosa*2+1); // recursive call for mother ancestors
				}
				
				if ($sosa>3) return;
				// add father/mother marriages
				foreach ($fam->parents as $role => $parent) {
					if ($role == "HUSB") {
						$fact="_MARR_FATH";
						$rela="father";
					}
					else {
						$fact="_MARR_MOTH";
						$rela="mother";
					}
					if (strstr($SHOW_RELATIVES_EVENTS, $fact) && is_object($parent)) {
						$parent->GetSpouseFamilies();
						foreach ($parent->spousefamilies as $indexval => $psfam) {
							if ($psfam->xref == $fam->xref and $rela=="mother") continue; // show current family marriage only for father
							if (CompareFacts($this->bdate, $psfam->marr_date)<0 and CompareFacts($psfam->marr_date, $this->ddate)<0) {
								$factrec = "1 ".$fact;
								$factrec .= "\n".trim($psfam->marr_date);
								$factrec .= "\n2 ASSO @".$parent->xref."@";
								$factrec .= "\n3 RELA ".$rela;
								if ($psfam->husb_id == $parent->xref) $spouse = $psfam->wife_id;
								else $spouse = $psfam->husb_id;
								if ($rela=="father") $rela2="stepmom";
								else $rela2="stepdad";
								if ($psfam->xref == $fam->xref) $rela2="mother";
								if (!empty($spouse)) {
									$factrec .= "\n2 ASSO @".$spouse."@";
									$factrec .= "\n3 RELA ".$rela2;
								}
								$factrec .= "\n2 ASSO @".$psfam->xref."@";
								$factrec .= "\n3 RELA family";
if ($this->tracefacts) print "AddParentsFacts sosa ".$sosa."- Adding for ".$fam->xref.": ".$fact." ".$factrec."<br />";
								$this->facts[] = new Fact($psfam->xref, "X$fact", $factrec, 0, "");
							}
						}
					}
				}
				//-- find siblings
				$this->AddChildrenFacts($fam, $sosa, $person->xref);
			}
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
	private function AddChildrenFacts($fam, $option="", $except="") {
		global $SHOW_RELATIVES_EVENTS, $gm_username, $GEDCOM, $gm_lang;

		if (!$SHOW_RELATIVES_EVENTS) return;
		if (!$fam->disp) return;
		
		foreach($fam->children as $key => $child) {
			// Check privacy here, as GM will insert own fact tags, so the original fact cannot be checked anymore.
			if ($except != $child->xref) {
//				print $option." ".$childrec."<br />";
				// children
				$rela="child";
				if ($child->GetSex() == "F") $rela="daughter";
				if ($child->GetSex() == "M") $rela="son";
				// grandchildren
				if ($option=="grand") {
					$rela="grandchild";
					if ($child->GetSex() == "F") $rela="granddaughter";
					if ($child->GetSex() == "M") $rela="grandson";
				}
				// stepsiblings
				if ($option=="step") {
					$rela="halfsibling";
					if ($child->GetSex() == "F") $rela="halfsister";
					if ($child->GetSex() == "M") $rela="halfbrother";
				}
				// siblings
				if ($option=="1") {
					$rela="sibling";
					if ($child->GetSex() == "F") $rela="sister";
					if ($child->GetSex() == "M") $rela="brother";
				}
				// uncles/aunts
				if ($option=="2" or $option=="3") {
					$rela="uncle/aunt";
					if ($child->GetSex() == "F") $rela="aunt";
					if ($child->GetSex() == "M") $rela="uncle";
				}
				// firstcousins
				if ($option=="first") {
					$rela="firstcousin";
					if ($child->GetSex() == "F") $rela="femalecousin";
					if ($child->GetSex() == "M") $rela="malecousin";
				}
				// add child birth
				$fact = "_BIRT_CHIL";
				if ($option=="grand") $fact = "_BIRT_GCHI";
				if ($option=="step") $fact = "_BIRT_HSIB";
				if ($option=="first") $fact = "_BIRT_COUS";
				if ($option=="1") $fact = "_BIRT_SIBL";
				if ($option=="2") $fact = "_BIRT_FSIB";
				if ($option=="3") $fact = "_BIRT_MSIB";
				if (strstr($SHOW_RELATIVES_EVENTS, $fact) && ShowFact("BIRT", $child->xref, "INDI") && ShowFactDetails("BIRT", $child->xref)) {
					if (CompareFacts($this->GetBirthDate(), $child->GetBirthDate()) < 0 and CompareFacts($child->GetBirthDate(), $this->GetDeathDate()) < 0) {
						$factrec = "1 ".$fact;
						$factrec .= "\n".trim($child->bdate);
						$factrec .= "\n2 ASSO @".$child->xref."@";
						$factrec .= "\n3 RELA ".$rela;
if ($this->tracefacts) print "AddChildrenFacts (".$option.") - Adding for ".$child->xref.": ".$fact." ".$factrec."<br />";

						$this->facts[] = new Fact($child->xref, "X$fact", $factrec, 0, "");
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
				if (strstr($SHOW_RELATIVES_EVENTS, $fact) && ShowFact("DEAT", $child->xref, "INDI") && ShowFactDetails("DEAT", $child->xref)) {
					if (CompareFacts($this->GetBirthDate(), $child->GetDeathDate())<0 && CompareFacts($child->GetDeathDate(), $this->GetDeathDate()) < 0) {
						$factrec = "1 ".$fact;
						$factrec .= "\n".trim($child->ddate);
						$factrec .= "\n2 ASSO @".$child->xref."@";
						$factrec .= "\n3 RELA ".$rela;
if ($this->tracefacts) print "AddChildrenFacts (".$option.") - Adding for ".$child->xref.": ".$fact." ".$factrec."<br />";
						$this->facts[] = new Fact($child->xref, "X$fact", $factrec, 0, "");
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
					$child->GetSpouseFamilies();
					foreach($child->spousefamilies as $key => $childfam) {
						if ($childfam->disp && ShowFact("MARR", $childfam->xref, "FAM") && ShowFactDetails("MARR", $childfam->xref)) {
							if (CompareFacts($this->bdate, $childfam->marr_date)<0 and CompareFacts($childfam->marr_date, $this->ddate)<0) {
								$factrec = "1 ".$fact;
								$factrec .= "\n".trim($childfam->marr_date);
								$factrec .= "\n2 ASSO @".$child->xref."@";
								$factrec .= "\n3 RELA ".$rela;
								if ($childfam->husb_id == $child->xref) $spouse = $childfam->wife_id;
								else $spouse = $childfam->husb_id;
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
								$factrec .= "\n2 ASSO @".$childfam->xref."@";
								$factrec .= "\n3 RELA family";
								$arec = GetSubRecord(2, "2 ASSO @".$child->xref."@", $childfam->marr_fact->factrec);
								if ($arec) $factrec .= "\n".$arec;
if ($this->tracefacts) print "AddChildrenFacts (".$option.") - Adding for ".$child->xref.": ".$fact." ".$factrec."<br />";
								$this->facts[] = new Fact($child->xref, "X$fact", $factrec, 0, "");
							}
						}
					}
				}
				// add grand-children
				if ($option=="") {
					$child->GetSpouseFamilies();
					foreach ($child->spousefamilies as $indexval => $fam) {
						$this->AddChildrenFacts($fam, "grand");
					}
				}
				// first cousins
				if ($option=="2" or $option=="3") {
					foreach ($child->spousefamilies as $indexval => $fam) {
						$this->AddChildrenFacts($fam, "first");
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
	private function AddSpouseFacts($fam, $spperson) {
		global $SHOW_RELATIVES_EVENTS;

		// do not show if divorced
		if (strstr($fam->gedrec, "1 DIV")) return;
		// add spouse death
		$fact = "_DEAT_SPOU";
		if (strstr($SHOW_RELATIVES_EVENTS, $fact)) {
			if (CompareFacts($this->GetBirthDate(), $fam->$spperson->GetDeathDate())<0 and CompareFacts($fam->$spperson->GetDeathDate(), $this->GetDeathDate())<0) {
				$factrec = "1 ".$fact;
				$factrec .= "\n".trim($fam->$spperson->ddate);
				$factrec .= "\n2 ASSO @".$fam->$spperson->xref."@";
				$factrec .= "\n3 RELA spouse";
if ($this->tracefacts) print "AddSpouseFacts - Adding for ".$fam->$spperson->xref.": ".$fact." ".$factrec."<br />";
				$this->facts[] = new Fact($fam->$spperson->xref, "X$fact", $factrec, 0, "");
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
	private function AddStepSiblingsFacts($spouse, $except="") {

		$spouse->GetSpouseFamilies();
		foreach ($spouse->spousefamilies as $index => $fam) {
			// process children from all step families
			if ($fam->xref != $except) $this->AddChildrenFacts($fam, "step");
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
	private function add_historical_facts() {
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
//				$this->facts[]=array(0, $hrec);
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
	private function AddAssoFacts($pid) {
		global $factarray, $gm_lang;
		global $assolist, $GEDCOMID;

		if (!function_exists("GetAssoList")) return;
		GetAssoList("all", $pid);
		$apid = $pid."[".$GEDCOMID."]";
		// associates exist ?
		if (count($assolist) > 0) {
			// if so, print all indi's where the indi is associated to
			foreach($assolist as $indexval => $assos) {
				foreach($assos as $key => $asso) {
					$rid = splitkey($indexval, "id");
					$typ = $asso["type"];
					// search for matching fact
					if (DisplayDetailsByID($rid, strtoupper($typ)) && empty($asso["resn"])) {
						for ($i=1; ; $i++) {
							$srec = GetSubRecord(1, "1 ".$asso["fact"], $asso["gedcom"], $i);
							if (empty($srec)) break;
							$arec = GetSubRecord(2, "2 ASSO @".$pid."@", $srec);
							if ($arec) {
								$fact = trim(substr($srec, 2, 5));
								if (ShowFact($fact, $rid, $typ) && ShowFactDetails($fact, $rid)) {
									$label = strip_tags($factarray[$fact]);
									$sdate = GetSubRecord(2, "2 DATE", $srec);
									// relationship ?
									if (empty($asso["role"])) $rela = "ASSO";
									if (isset($gm_lang[$asso["role"]])) $rela = $gm_lang[$asso["role"]];
									else if (isset($factarray[$asso["role"]])) $rela = $factarray[$asso["role"]];
									// add an event record
									$factrec = "1 EVEN\n2 TYPE ".$label."<br/>[".$rela."]";
									$factrec .= "\n".trim($sdate);
									if (trim($typ) == "FAM") {
										$fam =& Family::GetInstance($rid);
										if ($fam->husb_id != "") $factrec .= "\n2 ASSO @".$fam->husb_id."@"; //\n3 RELA ".$factarray[$fact];
										if ($fam->wife_id != "") $factrec .= "\n2 ASSO @".$fam->wife_id."@"; //\n3 RELA ".$factarray[$fact];
									}
									else $factrec .= "\n2 ASSO @".$rid."@\n3 RELA ".$label;
									//$factrec .= "\n3 NOTE ".$rela;
									$factrec .= "\n2 ASSO @".$pid."@\n3 RELA ".$rela;
									$this->facts[] = new Fact($rid, "X_$fact", $factrec, 0, "");
								}
							}
						}
					}
				}
			}
		}
	}
	
	public function getParentFamily() {
		global $gm_lang, $gm_username, $GEDCOM;
		
		$this->GetChildFamilies();
		if (count($this->childfamilies) > 0) {
			$this->close_relatives = true;
			foreach ($this->childfamilies as $id => $fam) {
				if ($fam->husb != "") {
					$sex = $fam->husb->getSex();
					$label = $this->gender($fam->husb->getSex(), $fam->pedigreetype."parents");
					if ($fam->husb == $this->xref) $label = "<img src=\"images/selected.png\" alt=\"\" />";
					$this->childfamilies[$id]->husb->setFamLabel($fam->xref, $label);
				}
				if ($fam->wife != "") {
					$sex = $fam->wife->getSex();
					$label = $this->gender($fam->wife->getSex(), $fam->pedigreetype."parents");
					if ($fam->wife == $this->xref) $label = "<img src=\"images/selected.png\" alt=\"\" />";
					$this->childfamilies[$id]->wife->setFamLabel($fam->xref, $label);
				}
				// NOTE: Set the family label, also if no parents exist.
				if (isset($gm_lang["as_".$fam->pedigreetype."child"])) $this->childfamilies[$id]->label = $gm_lang["as_".$fam->pedigreetype."child"];
				else $this->childfamilies[$id]->label = $fam->pedigreetype;
				
				// NOTE: Create the children and set their label
				foreach ($fam->children as $cid => $child) {
//					NOTE: we must get the relation of the person to the siblings, not of the siblings to the family!
					if ($fam->pedigreetype == "") {
						$child->GetChildFamilyIds();
						$rela = $child->famc[$id]["relation"];
//						$famlink = GetSubRecord(1, "1 FAMC @".$fam->xref."@", $child->gedrec);
//						$ft = preg_match("/2 PEDI (.*)/", $famlink, $fmatch);
//						if ($ft>0 && $fmatch[1] != "birth") $rela = trim($fmatch[1]);
//						else $rela = "";
					}
					else $rela = $fam->pedigreetype;
					$sex = $child->getSex();
					$label = $child->gender($sex, $rela."parentskids");
					if ($child->xref==$this->xref) $label = "<img src=\"images/selected.png\" alt=\"\" />";
					$this->childfamilies[$id]->children[$cid]->setFamLabel($fam->xref, $label);
				}
			}
		}
	}
	
	public function getSpouseFamily() {
		global $gm_lang, $GEDCOM, $gm_username, $Users;

		if (count($this->spousefamilies) > 0) {
			$this->close_relatives = true;
			foreach ($this->spousefamilies as $id => $fam) {
//				if ($fam->disp) {
					$this->close_relatives = true;
					
					// NOTE: Get the label for the family
					$this->spousefamilies[$id]->label = $gm_lang["family_with"] . " ";
					// Check if the husband is equal to the person we are displaying
					// If so, add the wife to the tag
					if ($this->xref == $this->spousefamilies[$id]->husb_id) {
						if (is_object($this->spousefamilies[$id]->wife)) {
							$name = $this->spousefamilies[$id]->wife->GetName();
							if (HasChinese($name)) $name = PrintReady($name." (".$this->spousefamilies[$id]->wife->GetAddName().")");
							if ($fam->wife->disp_name) $this->spousefamilies[$id]->label .= $name;
							else $this->spousefamilies[$id]->label .= $gm_lang["private"];
						}
						else $this->spousefamilies[$id]->label .= $gm_lang["unknown"];
					}
					else {
						if (is_object($this->spousefamilies[$id]->husb)) {
							$name = $this->spousefamilies[$id]->husb->getName();
							if (HasChinese($name)) $name = PrintReady($name." (".$this->spousefamilies[$id]->husb->getAddName().")");
							if ($fam->husb->disp_name) $this->spousefamilies[$id]->label .= $name;
							else $this->spousefamilies[$id]->label .= $gm_lang["private"];
						}
						else $this->spousefamilies[$id]->label .= $gm_lang["unknown"];
					}
					// NOTE: Create the parents and set their label
					foreach ($this->spousefamilies[$id]->parents as $type => $parent) {
						if (is_object($parent)) {
							$sex = $parent->getSex();
							$divorced = "";
							$married = "";
							$divorced = GetSubRecord(1, "DIV", $this->spousefamilies[$id]->gedrec);
							if ($parent->xref == $this->xref) $label = "<img src=\"images/selected.png\" alt=\"\" />";
							else $label = $parent->gender($sex, "spouseparents", $divorced, (is_object($this->spousefamilies[$id]->marr_fact) ? $this->spousefamilies[$id]->marr_fact->factrec : ""));
							$type = strtolower($type);
							$this->spousefamilies[$id]->$type->setFamLabel($this->spousefamilies[$id]->xref, $label);
						}
					}
					// NOTE: Create the children and set their label
					foreach ($this->spousefamilies[$id]->children as $key => $child) {
						$sex = $child->getSex();
						// Get the type of family relationship
						$child->GetChildFamilyIds();
//						print $child->xref;
//						print_r($child->famc);
						$rela = $child->famc[$id]["relation"];
//						$famlink = GetSubRecord(1, "1 FAMC @".$this->spousefamilies[$id]->xref."@", $child->gedrec);
//						$ft = preg_match("/2 PEDI (.*)/", $famlink, $fmatch);
//						if ($ft>0 && $fmatch[1] != "birth") $rela = trim($fmatch[1]);
//						else $rela = "";
						if ($child->xref == $this->xref) $label = "<img src=\"images/selected.png\" alt=\"\" />";
						else $label = $child->gender($sex, $rela."spousekids");
						$this->spousefamilies[$id]->children[$key]->setFamLabel($fam->xref, $label);
					}
//				}
			}
		}
	}
	
	public function getParentOtherFamily() {
		global $gm_lang, $GEDCOM, $gm_username;
		
		// NOTE: Get the parents other families
		// NOTE: Get the fathers families only if they have kids
		$this->close_relatives = true;
		foreach ($this->childfamilies as $famid => $family) {
			if (is_object($family->husb)) {
				foreach ($family->husb->getspousefamilies() as $key => $sfamily) {
					if ($sfamily->xref != $family->xref) {
						if (count($sfamily->children) > 0) {
							foreach ($sfamily->children as $kidkey => $kid) {
								$sex = $kid->getSex();
								$kid->GetChildFamilyIds();
								// if this persons own relation to the family, AND that of the kid are birth, they are half brothers
								if ($kid->xref == $this->xref) $label = "<img src=\"images/selected.png\" alt=\"\" />";
								else if ($family->pedigreetype == "" && $kid->famc[$key]["relation"] == "") $label = $kid->gender($sex, "halfkids");
								else $label = $gm_lang["no_relation"];
								$family->husb->spousefamilies[$key]->children[$kidkey]->setFamLabel($sfamily->xref, $label);
							}
							// NOTE: Get the label for the family
							$this->childfamilies[$famid]->husb->spousefamilies[$key]->label = $gm_lang["fathers_family_with"] . " ";
							// Check if the husband is equal to the person we are displaying
							// If so, add the wife to the tag
							if ($family->husb->xref == $sfamily->husb->xref) {
								if (is_object($sfamily->wife)) {
									$name = $sfamily->wife->GetName();
									// empty the label, as the wife is not related
									$sfamily->wife->SetFamLabel($sfamily->xref, "");
									if (HasChinese($name)) $name = PrintReady($name." (".$sfamily->wife->GetAddName().")");
								}
								else $name = CheckNN("@P.N./@N.N./");
								$this->childfamilies[$famid]->husb->spousefamilies[$key]->label .= $name;
							}
							// NOTE: We are not viewing the husband, so add the husbands label
							else {
								$name = $sfamily->husb->GetName();
								if (HasChinese($name)) $name = PrintReady($name." (".$sfamily->husb->GetAddName().")");
								$this->childfamilies[$famid]->husb->spousefamilies[$key]->label .= $name;
							}
						}
					}
				}
			}
			
			// NOTE: Get the mothers families only if they have kids
			if (is_object($family->wife)) {
				foreach ($family->wife->getspousefamilies() as $key => $sfamily) {
					if ($sfamily->xref != $family->xref) {
						if (count($sfamily->children) > 0) {
							foreach ($sfamily->children as $kidkey => $kid) {
								$sex = $kid->getSex();
								$kid->GetChildFamilyIds();
								// if this persons own relation to the family, AND that of the kid are birth, they are half brothers
								if ($kid->xref == $this->xref) $label = "<img src=\"images/selected.png\" alt=\"\" />";
								else if ($family->pedigreetype == "" && $kid->famc[$key]["relation"] == "") $label = $kid->gender($sex, "halfkids");
								else $label = $gm_lang["no_relation"];
								$family->wife->spousefamilies[$key]->children[$kidkey]->setFamLabel($sfamily->xref, $label);
							}
							// NOTE: Get the label for the family
							$this->childfamilies[$famid]->wife->spousefamilies[$key]->label = $gm_lang["mothers_family_with"] . " ";
							// Check if the husband is equal to the person we are displaying
							// If so, add the wife to the tag
							if ($family->wife->xref == $sfamily->wife->xref) {
								if (is_object($sfamily->husb)) {
									// empty the label, as the husb is not related
									$sfamily->husb->SetFamLabel($sfamily->xref, "");
									$name = $sfamily->husb->GetName();
									if (HasChinese($name)) $name = PrintReady($name." (".$sfamily->husb->GetAddName().")");
								}
								else $name = CheckNN("@P.N./@N.N./");
								$this->childfamilies[$famid]->wife->spousefamilies[$key]->label .= $name;
							}
							// NOTE: We are not viewing the husband, so add the husbands label
							else {
								$name = $sfamily->wife->GetName();
								if (HasChinese($name)) $name = PrintReady($name." (".$sfamily->wife->GetAddName().")");
								$this->childfamilies[$famid]->wife->spousefamilies[$key]->label .= $name;
							}
						}
					}
				}
			}
		}
	}
	
	public function gender($sex, $type, $divorced="", $married = "") {
		global $gm_lang;
//		print "married: ".$married;
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
	private function getSpouseFamilyIds() {
		
		if (is_null($this->fams)) {
	
			if ($this->show_changes && $this->ThisChanged()) $gedrec = $this->GetChangedGedRec();
			else $gedrec = $this->gedrec;
			
			$this->fams = array();
			$ct = preg_match_all("/1\s+FAMS\s+@(.*)@.*/", $gedrec, $fmatch, PREG_SET_ORDER);
			if ($ct>0) {
				foreach($fmatch as $key => $value) {
					$this->fams[$value[1]] = $value[1];
				}
			}
		}
		return $this->fams;
	}

	private function GetSpouseFamilies() {
		
		if (is_null($this->spousefamilies)) {
		
			$this->spousefamilies = array();
			$fams = $this->getSpouseFamilyIds();
			
			foreach($fams as $key=>$famid) {
				if (!empty($famid)) {
					$fam =& Family::GetInstance($famid);
					$this->spousefamilies[$famid] = $fam;
				}
			}
		}
		return $this->spousefamilies;
	}
		
		
	/**
	 * get the families with parents
	 * @return array	array of Family objects
	 */
	private function getChildFamilies() {
		
		if (is_null($this->childfamilies)) {
			
			$fams = $this->getChildFamilyIds();
			$this->childfamilies = array();
			
			foreach($fams as $key=>$ffamid) {
				$famid = $ffamid["famid"];
				if (!empty($famid)) {
					$family =& Family::GetInstance($famid);
					if ($ffamid["primary"] == "Y") $family->showprimary = true;
					else $family->showprimary = false;
					$family->pedigreetype = $ffamid["relation"];
					$family->status = $ffamid["status"];
					$this->childfamilies[$famid] = $family;
				}
			}
		}
		return $this->childfamilies;
	}

	private function GetPrimaryChildFamily() {
		
		if (is_null($this->primaryfamily)) {
			if (is_null($this->childfamilies)) $this->GetChildFamilies();
			foreach ($this->childfamilies as $id => $family) {
				if (is_null($this->primaryfamily) || $family->showprimary) $this->primaryfamily = $id;
			}
			if (is_null($this->primaryfamily)) $this->primaryfamily = "";
		}
		return $this->primaryfamily;
	}
		
	/**
	 * get family with child ids
	 * @return array	array of the FAMC ids
	 */
	private function getChildFamilyIds() {
		
		if (is_null($this->famc)) {
			
			$gedrecs = array();
			if ($this->show_changes && $this->ThisChanged() && !$this->ThisDeleted()) $gedrecs[] = $this->GetChangedGedRec();
			$gedrecs[] = $this->gedrec;

			$this->famc = array();
			foreach($gedrecs as $key => $gedrec) {
			$ct = preg_match_all("/1\s+FAMC\s+@(.*)@.*/", $gedrec, $fmatch, PREG_SET_ORDER);
			if ($ct>0) {
				$i = 1;
				foreach($fmatch as $key => $value) {
					$famcrec = GetSubRecord(1, "1 FAMC", $gedrec, $i);
					$ct = preg_match("/2\s+_PRIMARY\s(.+)/", $famcrec, $pmatch);
					if ($ct>0) $prim = trim($pmatch[1]);
					else $prim = "";
					$ct = preg_match("/2\s+PEDI\s+(adopted|birth|foster|sealing)/", $famcrec, $pmatch);
					$ped = "";
					if ($ct>0) $ped = trim($pmatch[1]);
					if ($ped == "birth") $ped = "";
					$ct = preg_match("/2\s+STAT\s+(challenged|proven|disproven)/", $famcrec, $pmatch);
					$stat = "";
					if ($ct>0) $stat = trim($pmatch[1]);
					$this->famc[$value[1]] = array("famid"=>$value[1], "primary"=>$prim, "relation"=>$ped, "status"=>$stat);
					$i++;
				}
			}
			}
		}
		return $this->famc;
	}
	
	protected function GetLinksFromActions($status="") {
		global $TBLPREFIX, $Users;

		if(!is_null($this->actionlist)) return $this->actionlist;
		$this->actionlist = array();
		if ($Users->ShowActionLog()) { 
			$sql = "SELECT * FROM ".$TBLPREFIX."actions WHERE a_gedfile='".$this->gedcomid."' AND a_pid='".$this->xref."'";
			if ($status != "") $sql .= " AND a_status='".$status."'";
			else $sql .= " ORDER BY a_status ASC";
			$res = NewQuery($sql);
			while ($row = $res->FetchAssoc()) {
				$action = null;
				$action = new ActionItem($row, $this->xref);
				if ($action->disp) {
					if ($action->status == 1) $this->action_open++;
					else $this->action_closed++;
					$this->actionlist[] = $action;
				}
				else $this->action_hide++;
			}
		}
		$this->action_count = count($this->actionlist);
		return $this->actionlist;
	}
	
	protected function GetLinksFromActionCount() {
		global $TBLPREFIX;

		if(!is_null($this->action_open)) return;

		$this->actionlist = array();
		$sql = "SELECT count(a_status) as count, a_status FROM ".$TBLPREFIX."actions WHERE a_gedfile='".$this->gedcomid."' AND a_pid='".$this->xref."' GROUP BY a_status";
		$res = NewQuery($sql);
		while ($row = $res->FetchAssoc()) {
			if ($row["a_status"] == "1") $this->action_open = $row["count"];
			else if ($row["a_status"] == "0") $this->action_closed = $row["count"];
		}
		if (is_null($this->action_open)) $this->action_open = 0;
		if (is_null($this->action_closed)) $this->action_closed = 0;
		$this->actioncount = $this->action_open + $this->action_closed;
	}

	public function PrintListPerson($useli=true, $break=false) {
		
		if (!$this->disp) return false;
		
		if ($useli) {
			if (begRTLText($this->GetSortableName())) print "<li class=\"rtl\" dir=\"rtl\">";
			else print "<li class=\"ltr\" dir=\"ltr\">";
		}
		
		if (HasChinese($this->name_array[0][0])) $addname = " (".$this->GetSortableAddName().")";
		else $addname = "";
		print "<a href=\"individual.php?pid=".$this->xref."&amp;gedid=".$this->gedcomid."\" class=\"list_item\"><b>";
		print CheckNN($this->GetSortableName()).$addname."</b>".$this->addxref;
		print_first_major_fact($this->xref, $this->gedrec, true, $break);
		print "</a>\n";
		if ($useli) print "</li>";
		

	}
	/**
	 * get the correct label for a family
	 * @param Family $family		the family to get the label for
	 * @return string
	 */
/*	function getChildFamilyLabel(&$family) {
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
*/
}
?>