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
	private $revname = null;					// Printable name, in reversed order
	private $addname = null;				// Printable addname of the person, after applying privacy (can be blank)
	private $revaddname = null;				// Printable addname, in reversed order
	private $name_array = null;				// Array of names from GetIndiNames
	private $sortable_name = null;			// Sortable name of the person, no privacy applied
	private $sortable_addname = null;		// Sortable addname of the person, no privacy applied
	private $changednames = null;			// Array with old and new values of the names. No privacy applied. Only old names if user cannot edit.
	private $bdate = null;					// The birth date in gedcom 2 DATE xxxxxx format. Privacy is applied. 
											// N.B.: If unknown, it is estimated.
	private $ddate = null;					// The death date in gedcom 2 DATE xxxxxx format. Privacy is applied.
											// N.B.: If unknown, it is estimated.
	private $brec = null;					// The complete birthrecord in gedcom format. Privacy is applied. For now used in timeline.
	private $drec = null;					// The complete deathrecord in gedcom format. Privacy is applied.
	private $sex = null;					// Gender of the person: M, F, U. Privacy is applied.
	private $age = null;					// Age of the person
	private $isdead = null;					// If the person is dead, -1 if not known
	private $highlightedimage = null;		// Array with info on the media item that is primary to show
	
	public $sexdetails = array();	 		// Set by individual controller
	public $label = array();				// Set for relatives relation
	private $names_read = null;				// Set to indicate that this object has read it's names. None can be added.
	
	private $childfamilies = null;  		// container for array of family objects where this person is child
	private $primaryfamily = null;			// The xref of the parent family that is set as primary for this person
	private $spousefamilies = null;		 	// container for array of family objects where this person is spouse
	private $famc = null;					// container for array of family ID's and relational info where this person is child
	private $fams = null;					// container for array of family ID's where this person is spouse
	
	protected $globalfacts = array();			// Array with name and sex facts. Showfact, Factviewrestricted are applied
	
	private $close_relatives = false;		// True if all labels have been set for display on the individual page; if false the close relatives tab hides
	private $sosamax = 7;					// Maximum sosa number for parents facts
	private $tracefacts = false;			// traces all relatives events that are added to the indifacts array
	
	public static function GetInstance($xref, $gedrec="", $gedcomid="") {
		global $GEDCOMID;
		
		if (empty($gedcomid)) $gedcomid = $GEDCOMID;
		if (!isset(self::$personcache[$gedcomid][$xref])) {
			self::$personcache[$gedcomid][$xref] = new Person($xref, $gedrec, $gedcomid);
		}
		return self::$personcache[$gedcomid][$xref];
	}
		
	public static function IsInstance($xref, $gedcomid="") {
		global $GEDCOMID;
		
		if (empty($gedcomid)) $gedcomid = $GEDCOMID;
		if (!isset(self::$personcache[$gedcomid][$xref])) return false;
		else return true;
	}
		
	/**
	 * Constructor for person object
	 * @param string $gedrec	the raw individual gedcom record
	 */
	public function __construct($id, $gedrec="", $gedcomid) {
		
		if (is_array($gedrec)) {
			// preset some values
			if (isset($gedrec["names"])) $this->name_array = $gedrec["names"];
			$this->isdead = $gedrec["i_isdead"];
			// extract the construction parameters
			$gedcomid = $gedrec["i_file"];
			$id = $gedrec["i_id"];
			$gedrec = $gedrec["i_gedrec"];
		}
			
		parent::__construct($id, $gedrec, $gedcomid);

		$this->tracefacts = false;
		$this->exclude_facts = "";
		
	}
	
	public function __get($property) {
		
		switch ($property) {
			case "name":
				return $this->getName();
				break;
			case "revname":
				return $this->getRevName();
				break;
			case "addname":
				return $this->getAddName();
				break;
			case "revaddname":
				return $this->getRevAddName();
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
			case "isdead":
				return $this->GetDeathStatus();
				break;
			case "highlightedimage":
				return $this->FindHighlightedMedia();
				break;
			case "globalfacts":
				if (is_null($this->facts)) $this->ParseFacts();
				return $this->globalfacts;
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
			case "fams":
				return $this->GetSpouseFamilyIds();
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

	public function __set($property, $value) {
		switch ($property) {
			case "isdead":
				$this->isdead = $value;
				break;
			case "addname":
				if ($this->names_read != true) {
					$this->name_array[] = $value;
				}
				break;
			case "names_read":
				if ($this->names_read != true) $this->names_read = $value;
				break;
			default:
				parent::__set($property, $value);
				break;
		}
	}
	
	private function GetDeathStatus() {
		
		if ($this->isdead != "0" && $this->isdead != "1") {
			$this->isdead = PrivacyFunctions::UpdateIsDead($this);
		}
		return $this->isdead;
	}
	
	public function ObjCount() {
		$count = 0;
		foreach(self::$personcache as $ged => $person) {
			$count += count($person);
		}
		return $count;
	}	
	
	private function getName() {
		global $gm_lang;
		
		if (is_null($this->name)) {
			if (!$this->DispName()) $this->name = $gm_lang["private"];
			else {
				if ($this->show_changes && $this->ThisChanged()) $gedrec = $this->GetChangedGedRec();
				else $gedrec = $this->gedrec;
				$this->name = PrintReady(GetPersonName($this->xref, $gedrec));
			}
			if ($this->name == "") $this->name = $gm_lang["unknown"];
		}
		return $this->name;
	}

	private function getRevName() {
		
		if (is_null($this->revname)) {
			$this->revname = $this->GetSortableName();
			$this->revname = CheckNN($this->revname);
		}
		return $this->revname;
	}
	
	private function getRevAddName() {
		
		if (is_null($this->revaddname)) {
			$this->revaddname = $this->GetSortableAddName();
			$this->revaddname = CheckNN($this->revaddname);
		}
		return $this->revaddname;
	}
	
	private function getAddName() {
		
		if (is_null($this->addname)) {
			if (!$this->DispName()) $this->addname = "";
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
			if ($this->show_changes && $this->ThisChanged()) $this->sortable_name = NameFunctions::GetSortableName($this, "", "", false, false, true);
			else $this->sortable_name = NameFunctions::GetSortableName($this);
		}
		return $this->sortable_name;
	}
	private function GetSortableAddName() {
		
		if (is_null($this->sortable_addname)) {
			if ($this->show_changes && $this->ThisChanged()) $this->sortable_addname = NameFunctions::GetSortableAddName($this, false, true);
			else $this->sortable_addname = NameFunctions::GetSortableAddName($this, false, false);
		}
		return $this->sortable_addname;
	}

	private function GetChangedNames() {
		
		// NOTE: Get the array of old/new names, for display on indipage
		if (is_null($this->changednames)) {
			$this->changednames = PersonFunctions::GetChangeNames($this);
		}
		return $this->changednames;
	}
	
	/**
	 * get highlighted media
	 * @return array
	 */
	private function findHighlightedMedia() {
		
		if (is_null($this->highlightedimage)) {
			$this->highlightedimage = FindHighlightedObject($this->xref);
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
			if ($this->DisplayDetails() && PrivacyFunctions::showFact("BIRT", $this->xref, "INDI") && PrivacyFunctions::showFactDetails("BIRT", $this->xref) && !PrivacyFunctions::FactViewRestricted($this->xref, $subrecord, 2)) {
				$this->brec = $subrecord;
 				$this->bdate = GetSubRecord(2, "2 DATE", $this->brec);
			}
			else {
				$this->brec = "";
				$this->bdate = "";
			}
			$subrecord = GetSubRecord(1, "1 DEAT", $gedrec);
			if ($this->DisplayDetails() && PrivacyFunctions::showFact("DEAT", $this->xref, "INDI") && PrivacyFunctions::showFactDetails("DEAT", $this->xref) && !PrivacyFunctions::FactViewRestricted($this->xref, $subrecord, 2)) {
				$this->drec = $subrecord;
				$this->ddate = GetSubRecord(2, "2 DATE", $this->drec);
			}
			else {
				$this->drec = "";
				$this->ddate = "";
			}
			// If either of the dates is unknown, we will make an assumption.
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

			if (PrivacyFunctions::showFact("SEX", $this->xref, "INDI") && PrivacyFunctions::showFactDetails("SEX", $this->xref)) {
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
	 * add facts from the family record
	 */
	public function AddFamilyFacts($continue=true) {
		global $nonfacts, $nonfamfacts;
		
		if (!$this->DisplayDetails()) return;
		if (is_null($this->facts)) $this->ParseFacts();
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
					if (!isset($count_facts[$factobj->fact])) $count_facts[$factobj->fact] = 1;
					else $count_facts[$factobj->fact]++;
					// -- handle special source fact case
					if (($factobj->fact!="SOUR") && ($factobj->fact!="OBJE") && ($factobj->fact!="NOTE") && ($factobj->fact!="CHAN") && ($factobj->fact!="_UID") && ($factobj->fact!="RIN")) {
						if ((!in_array($factobj->fact, $nonfacts))&&(!in_array($factobj->fact, $nonfamfacts))) {
							$subrecord = trim($factobj->factrec)."\r\n";
							if (is_object($fam->$spperson)) $subrecord.="1 _GMS @".$fam->$spperson->xref."@\r\n";
							$subrecord.="1 _GMFS @".$fam->xref."@\r\n";
if ($this->tracefacts) print "AddFamilyFacts - Adding for ".$fam->xref.": ".$factobj->fact." ".$subrecord."<br />";
							$this->facts[] = new fact($this->xref, $factobj->fact, $subrecord, $count_facts[$factobj->fact], $factobj->style);
						}
					}
				}
				// Only add spouses and kidsfacts if the family is not hidden.
				if ($continue && is_object($fam->$spperson)) $this->AddSpouseFacts($fam, $spperson);
				if ($continue) $this->AddChildrenFacts($fam);
			}
		}
		if ($continue) {
			$this->AddParentsFacts($this);
			$this->AddHistoricalFacts();
			$this->AddAssoFacts($this->xref);
		}
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
		
		if (!GedcomConfig::$SHOW_RELATIVES_EVENTS) return;
		if ($sosa > $this->sosamax) return;
		if (!is_object($person)) return;
		$person->GetChildFamilies();
		foreach ($person->childfamilies as $indexval => $fam) {
			if ($fam->disp) {
				if ($fam->husb_id != "") {
					// add father death
					if ($sosa==1) $fact="_DEAT_FATH"; else $fact="_DEAT_GPAR";
					if (strstr(GedcomConfig::$SHOW_RELATIVES_EVENTS, $fact) && PrivacyFunctions::showFact("DEAT", $fam->husb->xref, "INDI") && PrivacyFunctions::showFactDetails("DEAT", $fam->husb->xref)) {
						if (CompareFacts($this->GetBirthDate(), $fam->husb->GetDeathDate())<0 && CompareFacts($fam->husb->GetDeathDate(), $this->GetDeathDate())<0) {
							$factrec = "1 ".$fact;
							$factrec .= "\n".trim($fam->husb->GetDeathDate());
							$factrec .= "\n2 ASSO @".$fam->husb->xref."@";
							$factrec .= "\n3 RELA ".(GetSosaName($sosa*2));
	if ($this->tracefacts) print "AddParentsFacts sosa ".$sosa."- Adding for ".$fam->xref.": ".$fact." ".$factrec."<br />";
							$this->facts[] = new Fact($this->xref, "X$fact", $factrec, 0, "");
						}
					}
					if ($sosa==1) $this->AddStepSiblingsFacts($fam->husb, $fam->xref); // stepsiblings with father
					$this->AddParentsFacts($fam->husb, $sosa*2); // recursive call for father ancestors
				}
				
				if ($fam->wife_id != "") {
					// add mother death
					if ($sosa==1) $fact="_DEAT_MOTH"; else $fact="_DEAT_GPAR";
					if (strstr(GedcomConfig::$SHOW_RELATIVES_EVENTS, $fact) && PrivacyFunctions::showFact("DEAT", $fam->wife->xref, "INDI") && PrivacyFunctions::showFactDetails("DEAT", $fam->wife->xref)) {
						if (CompareFacts($this->GetBirthDate(), $fam->wife->GetDeathDate())<0 && CompareFacts($fam->wife->GetDeathDate(), $this->GetDeathDate())<0) {
							$factrec = "1 ".$fact;
							$factrec .= "\n".trim($fam->wife->GetDeathDate());
							$factrec .= "\n2 ASSO @".$fam->wife->xref."@";
							$factrec .= "\n3 RELA ".(GetSosaName($sosa*2+1));
	if ($this->tracefacts) print "AddParentsFacts sosa ".$sosa."- Adding for ".$fam->xref.": ".$fact." ".$factrec."<br />";
	//print $this->GetBirthDate()."  ".$fam->wife->GetDeathDate()."  ".$fam->wife->GetDeathDate()."  ".$this->GetDeathDate()."<br />";
							$this->facts[] = new Fact($this->xref, "X$fact", $factrec, 0, "");
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
					if (strstr(GedcomConfig::$SHOW_RELATIVES_EVENTS, $fact) && is_object($parent) && PrivacyFunctions::showFact("MARR", $fam->xref, "FAM") && PrivacyFunctions::showFactDetails("MARR", $fam->xref)) {
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
								$this->facts[] = new Fact($this->xref, "X$fact", $factrec, 0, "");
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
		global $gm_lang;

		if (!GedcomConfig::$SHOW_RELATIVES_EVENTS) return;
		if (!$fam->DisplayDetails()) return;
		
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
				if (strstr(GedcomConfig::$SHOW_RELATIVES_EVENTS, $fact) && PrivacyFunctions::showFact("BIRT", $child->xref, "INDI") && PrivacyFunctions::showFactDetails("BIRT", $child->xref)) {
					if (CompareFacts($this->GetBirthDate(), $child->GetBirthDate()) < 0 && CompareFacts($child->GetBirthDate(), $this->GetDeathDate()) < 0) {
						$factrec = "1 ".$fact;
						$factrec .= "\n".trim($child->bdate);
						$factrec .= "\n2 ASSO @".$child->xref."@";
						$factrec .= "\n3 RELA ".$rela;
if ($this->tracefacts) print "AddChildrenFacts (".$option.") - Adding for ".$child->xref.": ".$fact." ".$factrec."<br />";

						$this->facts[] = new Fact($this->xref, "X$fact", $factrec, 0, "");
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
				if (strstr(GedcomConfig::$SHOW_RELATIVES_EVENTS, $fact) && PrivacyFunctions::showFact("DEAT", $child->xref, "INDI") && PrivacyFunctions::showFactDetails("DEAT", $child->xref)) {
					if (CompareFacts($this->GetBirthDate(), $child->GetDeathDate())<0 && CompareFacts($child->GetDeathDate(), $this->GetDeathDate()) < 0) {
						$factrec = "1 ".$fact;
						$factrec .= "\n".trim($child->ddate);
						$factrec .= "\n2 ASSO @".$child->xref."@";
						$factrec .= "\n3 RELA ".$rela;
if ($this->tracefacts) print "AddChildrenFacts (".$option.") - Adding for ".$child->xref.": ".$fact." ".$factrec."<br />";
						$this->facts[] = new Fact($this->xref, "X$fact", $factrec, 0, "");
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
				
				if (strstr(GedcomConfig::$SHOW_RELATIVES_EVENTS, $fact)) {
					$child->GetSpouseFamilies();
					foreach($child->spousefamilies as $key => $childfam) {
						if ($childfam->disp && PrivacyFunctions::showFact("MARR", $childfam->xref, "FAM") && PrivacyFunctions::showFactDetails("MARR", $childfam->xref)) {
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
								$this->facts[] = new Fact($this->xref, "X$fact", $factrec, 0, "");
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
					$child->GetSpouseFamilies();
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

		// do not show if divorced
		if (strstr($fam->gedrec, "1 DIV")) return;
		// add spouse death
		$fact = "_DEAT_SPOU";
		if (strstr(GedcomConfig::$SHOW_RELATIVES_EVENTS, $fact) && PrivacyFunctions::showFact("DEAT", $fam->$spperson->xref, "INDI") && PrivacyFunctions::showFactDetails("DEAT", $fam->$spperson->xref)) {
			if (CompareFacts($this->GetBirthDate(), $fam->$spperson->GetDeathDate())<0 and CompareFacts($fam->$spperson->GetDeathDate(), $this->GetDeathDate())<0) {
				$factrec = "1 ".$fact;
				$factrec .= "\n".trim($fam->$spperson->ddate);
				$factrec .= "\n2 ASSO @".$fam->$spperson->xref."@";
				$factrec .= "\n3 RELA spouse";
if ($this->tracefacts) print "AddSpouseFacts - Adding for ".$fam->$spperson->xref.": ".$fact." ".$factrec."<br />";
				$this->facts[] = new Fact($this->xref, "X$fact", $factrec, 0, "");
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
	 *	$histo[]="1 EVEN\n2 TYPE _HIST\n2 DATE 11 NOV 1918\n2 NOTE WW1 Armistice";
	 *	$histo[]="1 EVEN\n2 TYPE _HIST\n2 DATE 8 MAY 1945\n2 NOTE WW2 Armistice";
	 * etc...
	 *
	 */
	private function AddHistoricalFacts() {
		global $GM_BASE_DIRECTORY, $LANGUAGE, $lang_short_cut;
		
		if (!GedcomConfig::$SHOW_RELATIVES_EVENTS) return;
		if (empty($this->bdate)) return;
		
		$histo=array();
		if (file_exists($GM_BASE_DIRECTORY."languages/histo.".$lang_short_cut[$LANGUAGE].".php")) {
			@include($GM_BASE_DIRECTORY."languages/histo.".$lang_short_cut[$LANGUAGE].".php");
		}
		foreach ($histo as $indexval=>$hrec) {
			if (!isset($count)) $count = 1;
			else $count++;
			$sdate = GetSubRecord(2, "2 DATE", $hrec);
			if (CompareFacts($this->bdate, $sdate)<0 && CompareFactsDate($sdate, $this->ddate)<0) {
				$this->facts[] = new Fact($this->xref, "EVEN", $hrec, $count, "");
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
		global $gm_lang;
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
					$typ = strtoupper($asso["type"]);
					// search for matching fact
					if (empty($asso["resn"])) {
						$object =& ConstructObject($rid, $typ, $this->gedcomid);
						$assodisp = $object->DisplayDetails();
						if ($assodisp) {
							for ($i=1; ; $i++) {
								$srec = GetSubRecord(1, "1 ".$asso["fact"], $asso["gedcom"], $i);
								if (empty($srec)) break;
								$arec = GetSubRecord(2, "2 ASSO @".$pid."@", $srec);
								if ($arec) {
									$fact = trim(substr($srec, 2, 5));
									if (PrivacyFunctions::showFact($fact, $rid, $typ) && PrivacyFunctions::showFactDetails($fact, $rid)) {
										$label = strip_tags(constant("GM_FACT_".$fact));
										$sdate = GetSubRecord(2, "2 DATE", $srec);
										// relationship ?
										if (empty($asso["role"])) $rela = "ASSO";
										if (isset($gm_lang[$asso["role"]])) $rela = $gm_lang[$asso["role"]];
										else if (defined("GM_FACT_".$asso["role"])) $rela = constant("GM_FACT_".$asso["role"]);
										// add an event record
										$factrec = "1 EVEN\n2 TYPE ".$label."<br/>[".$rela."]";
										$factrec .= "\n".trim($sdate);
										if (trim($typ) == "FAM") {
											$fam =& Family::GetInstance($rid);
											if ($fam->husb_id != "") $factrec .= "\n2 ASSO @".$fam->husb_id."@"; 
											if ($fam->wife_id != "") $factrec .= "\n2 ASSO @".$fam->wife_id."@"; 
										}
										else $factrec .= "\n2 ASSO @".$rid."@\n3 RELA ".$label;
										//$factrec .= "\n3 NOTE ".$rela;
										$factrec .= "\n2 ASSO @".$pid."@\n3 RELA ".$rela;
										$this->facts[] = new Fact($this->xref, "X_$fact", $factrec, 0, "");
									}
								}
							}
						}
					}
				}
			}
		}
	}
	
	public function getParentFamily() {
		global $gm_lang;
		
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
		global $gm_lang;

		if (count($this->spousefamilies) > 0) {
			$this->close_relatives = true;
			foreach ($this->spousefamilies as $id => $fam) {
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
					$rela = $child->famc[$id]["relation"];
					if ($child->xref == $this->xref) $label = "<img src=\"images/selected.png\" alt=\"\" />";
					else $label = $child->gender($sex, $rela."spousekids");
					$this->spousefamilies[$id]->children[$key]->setFamLabel($fam->xref, $label);
				}
			}
		}
	}
	
	public function getParentOtherFamily() {
		global $gm_lang;
		
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
	protected function getSpouseFamilyIds() {
		
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
					$fam =& Family::GetInstance($famid, "", $this->gedcomid);
					if ($fam->DisplayDetails()) $this->spousefamilies[$famid] = $fam;
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
					if ($family->DisplayDetails()) {
						if ($ffamid["primary"] == "Y") $family->showprimary = true;
						else $family->showprimary = false;
						$family->pedigreetype = $ffamid["relation"];
						$family->status = $ffamid["status"];
						$this->childfamilies[$famid] = $family;
					}
				}
			}
		}
		return $this->childfamilies;
	}

	private function GetPrimaryChildFamily() {
		
		if (is_null($this->primaryfamily)) {
			if (is_null($this->childfamilies)) $this->GetChildFamilies();
     		$priority = array();
			foreach ($this->childfamilies as $id => $family) {
				if (!isset($priority["first"])) $priority["first"] = $id;
				$priority["last"]=$id;
				if ($family->showprimary) {
					if (!isset($priority["primary"])) $priority["primary"] = $id;
				}
				$relation = $family->pedigreetype;
				switch ($relation) {
					case "adopted":
					case "foster": // Sometimes called "guardian"
					case "sealing":
                		// nothing to do
						break;
					default: // Should be "". Sometimes called "birth","biological","challenged","disproved"
						$relation = "birth";
						break;
            	}
				// in the future, we could use $ffamid["stat"]
				// to further prioritize the family relation:
				// "challenged", "disproven", ""/"proven"

				// only store the first occurance of this type of family
            	if (!isset($priority[$relation])) $priority[$relation] = $id;
			}

			// get the actual family array according to the following priority
			// at least one of these will get some results.
			if (isset($priority["primary"])) $this->primaryfamily = $priority["primary"];
			else if (isset($priority["birth"])) $this->primaryfamily = $priority["birth"];
			else if (isset($priority["adopted"])) $this->primaryfamily = $priority["adopted"];
			else if (isset($priority["foster"])) $this->primaryfamily = $priority["foster"];
			else if (isset($priority["sealing"])) $this->primaryfamily = $priority["sealing"];
			else if (isset($priority["first"])) $this->primaryfamily = $priority["first"];
			else if (isset($priority["last"])) $this->primaryfamily = $priority["last"];
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
	
	/**
	 * get an individuals age at the given date
	 *
	 * get an individuals age at the given date
	 * @param string $indirec the individual record so that we can get the birth date
	 * @param string $datestr the date string (everything after DATE) to calculate the age for
	 * @param string $style optional style (default 1=HTML style)
	 * @return string the age in a string
	 */
	public function GetAge($datestr, $style=1) {
		global $gm_lang, $monthtonum;
		
		$estimates = array("abt","aft","bef","est","cir");
		$realbirthdt="";
		$bdatestr = "";
		
		//-- get birth date for age calculations
		$birthrec = $this->GetBirthRecord();
		if ($birthrec == "") return "";
		
		$hct = preg_match("/2 DATE.*(@#DHEBREW@)/", $birthrec, $match);
		if ($hct>0) {
			$dct = preg_match("/2 DATE (.+)/", $birthrec, $match);
			$hebrew_birthdate = ParseDate(trim($match[1]));
			if (GedcomConfig::$USE_RTL_FUNCTIONS && $index==1) $birthdate = JewishGedcomDateToGregorian($hebrew_birthdate);
		}
		else {
			$dct = preg_match("/2 DATE (.+)/", $birthrec, $match);
			if ($dct>0) $birthdate = ParseDate(trim($match[1]));
		}
	
		$convert_hebrew = false;
		//-- check if it is a hebrew date
		$hct = preg_match("/@#DHEBREW@/", $datestr, $match);
		if (GedcomConfig::$USE_RTL_FUNCTIONS && $hct>0) {
			if (isset($hebrew_birthdate)) $birthdate = $hebrew_birthdate;
			else $convert_hebrew = true;
		}
		if ((strtoupper(trim($datestr))!="UNKNOWN")&&(!empty($birthdate[0]["year"]))) {
			$bt = preg_match("/(\d\d\d\d).*(\d\d\d\d)/", $datestr, $bmatch);
			if ($bt>0) {
				$date = ParseDate($datestr);
				if ($convert_hebrew) $date = JewishGedcomDateToGregorian($date);
				$age1 = $date[0]["year"]-$birthdate[0]["year"];
				$age2 = $date[1]["year"]-$birthdate[0]["year"];
				if ($style) $realbirthdt = " <span class=\"age\">(".$gm_lang["age"]." ";
				$age1n = ConvertNumber($age1);
				$age2n = ConvertNumber($age2);
				$realbirthdt .= $gm_lang["apx"]." ".$age1n;
				if ($age2n > $age1n) $realbirthdt .= "-".$age2n;
				if ($style) $realbirthdt .= ")</span>";
			}
			else {
				$date = ParseDate($datestr);
				if ($convert_hebrew) $date = JewishGedcomDateToGregorian($date);
				if (!empty($date[0]["year"])) {
					$age = $date[0]["year"]-$birthdate[0]["year"];
					if (!empty($birthdate[0]["mon"])) {
						if (!empty($date[0]["mon"])) {
							if ($date[0]["mon"]<$birthdate[0]["mon"]) $age--;
							else if (($date[0]["mon"]==$birthdate[0]["mon"])&&(!empty($birthdate[0]["day"]))) {
								if (!empty($date[0]["day"])) {
									if ($date[0]["day"]<$birthdate[0]["day"]) $age--;
								}
							}
						}
					}
					if ($style) $realbirthdt = " <span class=\"age\">(".$gm_lang["age"];
					$at = preg_match("/([a-zA-Z]{3})\.?/", $birthdate[0]["ext"], $amatch);
					if ($at==0) $at = preg_match("/([a-zA-Z]{3})\.?/", $datestr, $amatch);
					if ($at>0) {
						if (in_array(strtolower($amatch[1]), $estimates)) {
							$realbirthdt .= " ".$gm_lang["apx"];
						}
					}
					// age in months if < 2 years
					if ($age<2) {
						$y1 = $birthdate[0]["year"];
						$y2 = $date[0]["year"];
						$m1 = $birthdate[0]["mon"];
						$m2 = $date[0]["mon"];
						$d1 = $birthdate[0]["day"];
						$d2 = $date[0]["day"];
						$apx = (empty($m2) or empty($m1) or empty($d2) or empty($d1)); // approx
						if ($apx) $realbirthdt .= " ".$gm_lang["apx"];
						if (empty($m2)) $m2=$m1;
						if (empty($m1)) $m1=$m2;
						if (empty($d2)) $d2=$d1;
						if (empty($d1)) $d1=$d2;
						if ($y2>$y1) $m2 +=($y2-$y1)*12;
						$age = $m2-$m1;
						if ($d2<$d1) $age--;
						// age in days if < 1 month
						if ($age<1) {
							if ($m2>$m1) {
								if ($m1==2) $d2+=28;
								else if ($m1==4 or $m1==6 or $m1==9 or $m1==11) $d2+=30;
								else $d2+=31;
							}
							$age = $d2-$d1;
							$realbirthdt .= " ".$age." ";
							if ($age < 2) $realbirthdt .= $gm_lang["day1"];
							else $realbirthdt .= $gm_lang["days"];
						} else if ($age==12 and $apx) {
							$realbirthdt .= " 1 ".$gm_lang["year1"]; // approx 1 year
						} else {
							$realbirthdt .= " ".$age." ";
							if ($age < 2) $realbirthdt .= $gm_lang["month1"];
							else $realbirthdt .= $gm_lang["months"];
						}
					}
					else $realbirthdt .= " ".ConvertNumber($age);
					if ($style) $realbirthdt .= ")</span>";
					if ($age == 0) $realbirthdt = ""; // empty age
				}
			}
		}
		if ($style) return $realbirthdt;
		else return trim($realbirthdt);
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
	
	protected function GetLinksFromActionCount() {

		if(!is_null($this->action_open)) return;

		$this->actionlist = array();
		$search = ActionController::GetSelectActionList("", $this->xref, $this->gedcomid, "", true);
		$this->action_open = $search[1];
		$this->action_closed = $search[2];
		$this->action_count = $this->action_open + $this->action_closed;
	}

	public function PrintListPerson($useli=true, $break=false) {
		
		if (!$this->DisplayDetails()) return false;
		
		if ($useli) {
			if (begRTLText($this->GetSortableName())) print "<li class=\"rtl\" dir=\"rtl\">";
			else print "<li class=\"ltr\" dir=\"ltr\">";
		}
		if (HasChinese($this->name_array[0][0])) $addname = "&nbsp;(".$this->GetSortableAddName().")";
		else $addname = "";
		print "<a href=\"individual.php?pid=".$this->xref."&amp;gedid=".$this->gedcomid."\" class=\"list_item\"><b>";
		print CheckNN($this->GetSortableName()).$addname."</b>".$this->addxref;
		PersonFunctions::PrintFirstMajorFact($this, true, $break);
		print "</a>\n";
		if ($useli) print "</li>";
		

	}
	
	/**
	 * Print age of parents
	 *
	 * @param string $pid	child ID
	 * @param string $bdate	child birthdate
	 */
	public function PrintParentsAge($date) {
		global $gm_lang, $GM_IMAGES;
		
		if (GedcomConfig::$SHOW_PARENTS_AGE) {
			$childfam =& Family::GetInstance($this->GetPrimaryChildFamily());
			if (is_object($childfam)) {
				$father_text = "";
				$mother_text = "";
				// father
				if (is_object($childfam->husb)) {
					$age = ConvertNumber($childfam->husb->GetAge($date, false));
					if (10<$age && $age<80) $father_text = "<img src=\"".GM_IMAGE_DIR."/" . $GM_IMAGES["sex"]["small"] . "\" title=\"" . $gm_lang["father"] . "\" alt=\"" . $gm_lang["father"] . "\" class=\"sex_image\" />".$age;
				}
				// mother
				if (is_object($childfam->wife)) {
					$age = ConvertNumber($childfam->wife->GetAge($date, false));
					if (10<$age && $age<80) $mother_text = "<img src=\"".GM_IMAGE_DIR."/" . $GM_IMAGES["sexf"]["small"] . "\" title=\"" . $gm_lang["mother"] . "\" alt=\"" . $gm_lang["mother"] . "\" class=\"sex_image\" />".$age;
				}
				if ((!empty($father_text)) || (!empty($mother_text))) print "<span class=\"age\">".$father_text.$mother_text."</span>";
			}
		}
	}

	protected function ReadPersonRecord() {
		
		$sql = "SELECT i_key, i_gedrec, i_isdead, i_file, n_name, n_surname, n_letter, n_type FROM ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE i_key='".DbLayer::EscapeQuery(JoinKey($this->xref, $this->gedcomid))."' AND i_key=n_key ORDER BY n_id";
		$res = NewQuery($sql);
		if ($res) {
			if ($res->NumRows() != 0) {
				$row = $res->fetchAssoc();
				$this->gedrec = $row["i_gedrec"];
				$this->name_array[] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
				$this->isdead = $row["i_isdead"];
				while ($row = $res->FetchAssoc()) {
					$this->name_array[] = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_type"]);
				}
			}
		}
		$this->names_read = true;
	}
	
	protected function showLivingName() {
		global $SHOW_LIVING_NAMES, $person_privacy, $user_privacy, $gm_user, $USE_RELATIONSHIP_PRIVACY, $CHECK_MARRIAGE_RELATIONS, $GEDCOMID, $MAX_RELATION_PATH_LENGTH;
		
		// If we can show the details, we can also show the name
		if ($this->disp) return true;
		
		// Check the gedcom context
		$oldgedid = $GEDCOMID;
		if ($GEDCOMID != $this->gedcomid) SwitchGedcom($this->gedcomid);
		
		// If a pid is hidden or shown due to user privacy, the name is hidden or shown also
		if (!empty($gm_user->username)) {
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
		}
		
		
		// If a pid is hidden or shown due to person privacy, the name also is
		if (isset($person_privacy[$this->xref])) {
			if ($person_privacy[$this->xref] >= $gm_user->getUserAccessLevel()) {
				SwitchGedcom($oldgedid);
				return true;
			}
			else {
				SwitchGedcom($oldgedid);
				return false;
			}
		}
		
		// If RESN privacy on level 1 prevents the pid to be displayed, we also cannot show the name
		if (PrivacyFunctions::FactViewRestricted($this->xref, $this->gedrec, 1)) {
			SwitchGedcom($oldgedid);
			return false;
		}
		
		// Now split dead and alive people
		// If dead, we follow DisplayDetailsByID
		// If alive, we check if the general rule allows displaying the name. If not, return false.
		if ($this->isdead) {
			SwitchGedcom($oldgedid);
			return false;
		}
		else if ($SHOW_LIVING_NAMES < $gm_user->getUserAccessLevel()) {
			SwitchGedcom($oldgedid);
			return false;
		}
		
		// Now we check if we must further narrow what can be seen
		// At this point we have a pid that cannot be displayed by detail and is alive,
		// and without relationship privacy the name would be shown.
		if ($USE_RELATIONSHIP_PRIVACY) {
			
			// If we don't know the user's gedcom ID, we cannot determine the relationship,
			// so we cannot further narrow what the user sees.
			// The same applies if we know the user, and he is viewing himself
			if (empty($gm_user->gedcomid[$this->gedcomid]) || $gm_user->gedcomid[$this->gedcomid]==$this->xref) {
				SwitchGedcom($oldgedid);
				return true;
			}
			
			// Determine if the person is within range
			$user_indi =& Person::GetInstance($gm_user->gedcomid[$this->gedcomid]);
			$relationship = GetRelationship($user_indi, $this, $CHECK_MARRIAGE_RELATIONS, $MAX_RELATION_PATH_LENGTH);
			// If we have a relation in range, we can display the name
			// if not in range, we can display the name of dead people
			if ($relationship != false) {
				SwitchGedcom($oldgedid);
				return true;
			}
			else {
				SwitchGedcom($oldgedid);
				return false;
			}
		}
		SwitchGedcom($oldgedid);
		return true;
	}	
}
?>