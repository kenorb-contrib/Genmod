<?php
/**
 * Class file for a fact object
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
 * @package Genmod
 * @subpackage DataModel
 * @version $Id: fact_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
 
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class Fact {
	
	// General class information
	public $classname = "Fact";		// Name of this class
	public $datatype = "sub";		// Datataype os the data here
	
	// Data
	private $fact = null;			// Fact/event
	private $factref = null;		// Either the fact, or if fact is EVEN, the event type
	private $factrec = null;		// The complete record
	private $datestring = null;		// Date in raw format, without gedcom level/tag.
	private $simpledate = null;		// Date in gedcom format
	private $timestring = null;		// Time in time (!) format (not in gedcom format)
	private $simpletype = null;		// Type in gedcom format
	private $simpleplace = null;	// Place in gedcom format
	private $linktype = null;		// If this fact links to another objest, the type is set here
	private $linkxref = null;		// Xref of the linked object
	private $resnvalue = null;		// The value for the 2 RESN tag
	private $gedcomid = null;		// Gedcom ID in which the fact exists
	
	// Other attributes
	private $owner = null;			// Xref of the owner of this fact
	private $owner_type = null;		// Datatype of the owner
	private $disp = null;			// Result of ShowFactDetails
	private $show = null;			// Result of ShowFact. Facts in the factarray of other objects are already checked.
	private $canedit = null;		// If privacy (resn) or the owner prevents editing
	private $count = null;			// N-th fact of this type for the owner
	private $style = null;			// Style to print this fact with
	private $descr = null;			// Fact description
	private $rela = null;			// If this is a relafact, the person's ID for which the fact is displayed.
	private $isdeleted = null;		// If the fact is deleted (skipped for further editing)
		
	public function __construct($parent, $parent_type, $gedcomid, $fact, $factrec, $count=1, $style = "", $rela = "") {
		
		$this->fact = trim($fact);
		$this->factrec = $factrec;
		$this->gedcomid = $gedcomid;
		$this->owner = $parent;
		$this->owner_type = $parent_type;
		$this->count = $count;
		$this->style = $style;
		$ct = preg_match("/2 TYPE (.*)/", $this->factrec, $match);
		if ($ct>0) $this->factref = trim($match[1]);
		else $this->factref = $this->fact;
		if (!empty($rela)) $this->rela = $rela;

	}

	public function __get($property) {
		
		switch ($property) {
			case "fact":
				return $this->fact;
				break;
			case "factref":
				return $this->factref;
				break;
			case "factrec":
				return $this->factrec;
				break;
			case "simpledate":
				return $this->getSimpleDate();
				break;
			case "datestring":
				return $this->getDateString();
				break;
			case "timestring":
				return $this->getTimeString();
				break;
			case "simpletype":
				return $this->getSimpleType();
				break;
			case "simpleplace":
				return $this->getSimplePlace();
				break;
			case "linktype":
				return $this->getLinkType();
				break;
			case "linkxref":
				return $this->getLinkXref();
				break;
			case "resnvalue":
				return $this->getResnValue();
				break;
			case "gedcomid":
				return $this->gedcomid;
				break;
			case "owner":
				return $this->getOwner();
				break;
			case "owner_type":
				return $this->owner_type;
				break;
			case "show":
				return $this->Show();
				break;
			case "disp":
				return $this->ShowDetails();
				break;
			case "canedit":
				return $this->canEdit();
				break;
			case "count":
				return $this->count;
				break;
			case "style":
				return $this->style;
				break;
			case "descr":
				return $this->getFactDescription();
				break;
			case "isdeleted":
				return $this->isDeleted();
				break;
			default:
				PrintGetSetError($property, get_class($this), "get");
				break;
		}
	}
		
	public function __set($property, $value) {
		
		switch ($property) {
			case "style":
				$this->style = $value;
				break;
			case "factrec":
				$this->factrec = $value;
				break;
			default:
				PrintGetSetError($property, get_class($this), "set");
				break;
		}
	}
	
	private function getSimpleDate() {
		
		if (is_null($this->simpledate)) {
			if ($this->ShowDetails()) $this->simpledate = GetSubRecord(2, "2 DATE", $this->factrec);
			else $this->simpledate = "";
		}
		return $this->simpledate;
	}
	
	private function getDateString() {
		
		if (is_null($this->datestring)) {
			if ($this->ShowDetails()) $this->datestring = GetGedcomValue("DATE", "2", $this->factrec, "", false);
			else $this->datestring = "";
		}
		return $this->datestring;
	}
	
	private function getTimeString() {
		
		if (is_null($this->timestring)) {
			if ($this->ShowDetails()) $this->timestring = GetGedcomValue("DATE:TIME", "2", $this->factrec);
			else $this->timestring = "";
		}
		return $this->timestring;
	}
	
	private function getSimpleType() {
		
		if (is_null($this->simpletype)) {
			if ($this->ShowDetails()) $this->simpletype = GetSubRecord(2, "2 TYPE", $this->factrec);
			else $this->simpletype = "";
		}
		return $this->simpletype;
	}
	
	private function getSimplePlace() {
		
		if (is_null($this->simpleplace)) {
			if ($this->ShowDetails()) $this->simpleplace = GetSubRecord(2, "2 PLAC", $this->factrec);
			else $this->simpleplace = "";
		}
		return $this->simpleplace;
	}

	private function getLinkType() {
		
		if (is_null($this->linktype)) {
			$ct = preg_match("/^1 (\w+) @.+@/", $this->factrec, $match);
			if ($ct > 0) {
				$types = array("INDI"=>"Person", "CHIL"=>"Person", "HUSB"=>"Person", "WIFE"=>"Person", "FAMS"=>"Family", "FAMC"=>"Family", "SOUR"=>"Source", "REPO"=>"Repository", "OBJE"=>"MediaItem", "NOTE"=>"Note");
				$this->linktype = $types[$match[1]];
				//if ($this->linktype == "Person") print "linktype set to person for: ".$this->factrec."<br />";
			}
			else $this->linktype = "";
		}
		return $this->linktype;
	}
				
	private function getLinkXref() {
		
		if (is_null($this->linkxref)) {
			$ct = preg_match("/1 \w+ @(.+)@/", $this->factrec, $match);
			if ($ct > 0) $this->linkxref = $match[1];
			else $this->linkxref = "";
		}
		return $this->linkxref;
	}

	private function getResnValue() {
		
		if (is_null($this->resnvalue)) {
	 		$resn_tag = preg_match("/2 RESN (.*)/", $this->factrec, $match);
	 		if ($resn_tag > 0) $this->resnvalue = strtolower(trim($match[1]));
			else $this->resnvalue = "";
 		}
 		return $this->resnvalue;
	}
	
	private function getFactDescription() {
		
		if (is_null($this->descr)) {
			$fact = preg_replace("/^X_/", "_", $this->factref);
			if (defined("GM_FACT_".$fact)) $this->descr = constant("GM_FACT_".$fact);
			else $this->descr = $fact;
		}
		if (GedcomConfig::$ABBREVIATE_CHART_LABELS) return NameFunctions::GetFirstLetter($this->descr);
		else return $this->descr;
	}
	
	private function ShowDetails() {
		global $global_facts, $person_facts, $gm_user;
		
		if (is_null($this->disp)) {
			$facts = array();
			// Close relatives facts are already checked against their own xref while adding them
			$f = substr($this->fact, 1, 6);
			if ($f == "_BIRT_" || $f == "_DEAT_" && $f == "_MARR_") {
				$facts[] = substr($this->fact, 2, 4);
				
			}
			else $facts[] = $this->fact;
			
			//-- check for EVEN type facts
			$ct = preg_match("/2 TYPE (.*)/", $this->factrec, $match);
			if ($ct>0) $facts[] = trim($match[1]);
			
			$this->disp = true;
			foreach($facts as $key => $fact) {
			
				//-- if $PRIV_HIDE even admin users won't see everything
				if (isset($global_facts[$fact])) {
					//-- first check the global facts array
					if (isset($global_facts[$fact]["details"])) {
						if ($global_facts[$fact]["details"] < $gm_user->getUserAccessLevel()) $this->disp = false;
					}
				}
				
				//-- check the person facts array
				if (isset($person_facts[$this->owner][$fact]["details"])) {
					if ($person_facts[$this->owner][$fact]["details"] < $gm_user->getUserAccessLevel()) $this->disp = false;
				}
			}
		}
		return $this->disp;
	}
	
	private function Show() {
		global $global_facts, $person_facts, $gm_user;
		
		// print "Checking ".$fact." for ".$pid. " type ".$type." show_sources: ".$SHOW_SOURCES." userlevel: ".$ulevel."<br />";
		
		//-- first check the global facts array
		if (isset($global_facts[$this->fact]["show"])) {
			if ($global_facts[$this->fact]["show"] < $gm_user->getUserAccessLevel()) return false;
		}
		//-- check the person facts array
		if (isset($person_facts[$this->GetOwner()->xref][$this->fact]["show"])) {
			if ($person_facts[$this->GetOwner()->xref][$this->fact]["show"] < $gm_user->getUserAccessLevel()) return false;
		}
		return true;
	}

	private function getOwner() {
		
		if ($this->owner != "") {
			if (!is_object($this->owner)) return ConstructObject($this->owner, $this->owner_type, $this->gedcomid);
			else return $this->owner;
		}
	}
	
	private function canEdit() {
		
		if (is_null($this->canedit)) {
			// We cannot edit CHAN records and records that are added as relatives events.
			if ($this->fact == "CHAN" || substr($this->fact, 0, 2) == "X_") $this->canedit = false;
			else {
				$this->canedit = $this->getOwner()->canedit;
				if ($this->canedit == true) $this->canedit = !PrivacyFunctions::FactEditRestricted($this->getOwner()->xref, $this->factrec);
			}
		}
		return $this->canedit;
	}
	
	/**
	 * print fact DATE TIME
	 *
	 * @param string $factrec	gedcom fact record
	 * @param boolean $anchor	option to print a link to calendar
	 * @param boolean $time		option to print TIME value
	 * @param boolean $print_age option to print age
	 * @param string $fact		optional fact name (to print age)
	 * @param string $pid		optional person ID (to print age)
	 */
	public function PrintFactDate($anchor=false, $time=false, $print_parents_age=false, $print_own_age=false, $prt=true) {
	
		$prtstr = "";
		$ct = preg_match("/2 DATE (.+)/", $this->factrec, $match);
		if ($ct>0) {
			$prtstr .= " ";
			// link to calendar
			if ($anchor) $prtstr .= GetDateUrl($match[1]);
			// simple date
			else $prtstr .= GetChangedDate(trim($match[1]));
			// time
			if ($time) {
				$timerec = GetSubRecord(2, "2 TIME", $this->factrec);
				if (empty($timerec)) $timerec = GetSubRecord(2, "2 DATE", $this->getSimpleDate());
				$tt = preg_match("/[2-3] TIME (.*)/", $timerec, $tmatch);
				if ($tt>0) $prtstr .= " - <span class=\"Date\">".$tmatch[1]."</span>";
			}
			if ($print_parents_age && $this->owner_type == "INDI") {
				// age of parents at child birth
				if ($this->fact == "BIRT") $this->GetOwner()->PrintParentsAge($match[1]);
			}
			if ($print_own_age && ($this->owner_type == "INDI" || !is_null($this->rela))) {
				// age at event
				if ($this->fact != "CHAN") {
					// do not print age after death
					$deatrec = GetSubRecord(1, "1 DEAT", $this->GetOwner()->gedrec);
					if (!is_null($this->rela) || CompareFacts($this->factrec, $this->GetOwner()->drec) != 1 || strstr($this->factrec, "1 DEAT")) {
						if (!is_null($this->rela)) {
							$rela =& Person::GetInstance($this->rela);
							$prtstr .= $rela->GetAge($match[1]);
						}
						else $prtstr .= $this->GetOwner()->GetAge($match[1]);
					}
				}
			}
			$prtstr .= " ";
		}
		else {
			// 1 DEAT Y with no DATE => print YES
			// 1 DEAT N is not allowed
			// It is not proper GEDCOM form to use a N(o) value with an event tag to infer that it did not happen.
			if (preg_match("/^1\s(BIRT|DEAT|MARR|DIV|CHR|CREM|BURI)\sY/", $this->factrec) && !preg_match("/\n2\s(DATE|PLAC)/", $this->factrec)) $prtstr .= GM_LANG_yes."&nbsp;";
		}
		
		// gedcom indi age
		$ages=array();
		$agerec = GetSubRecord(2, "2 AGE", $this->factrec);
		if (empty($agerec)) {
			$daterec = GetSubRecord(2, "2 DATE", $this->factrec);
			$agerec = GetSubRecord(3, "3 AGE", $daterec);
		}
		$ages[0] = $agerec;
		
		// gedcom husband age
		$husbrec = GetSubRecord(2, "2 HUSB", $this->factrec);
		if (!empty($husbrec)) $agerec = GetSubRecord(3, "3 AGE", $husbrec);
		else $agerec = "";
		$ages[1] = $agerec;
		
		// gedcom wife age
		$wiferec = GetSubRecord(2, "2 WIFE", $this->factrec);
		if (!empty($wiferec)) $agerec = GetSubRecord(3, "3 AGE", $wiferec);
		else $agerec = "";
		$ages[2] = $agerec;
		
		// print gedcom ages
		foreach ($ages as $indexval=>$agerec) {
			if (!empty($agerec)) {
				$prtstr .= "<span class=\"FactDetailLabel\">";
				if ($indexval == 1) $prtstr .= GM_LANG_husband;
				else if ($indexval == 2) $prtstr .= GM_LANG_wife;
				else $prtstr .= GM_FACT_AGE;
				$prtstr .= "</span>: ";
				$age = FactFunctions::GetAgeAtEvent(substr($agerec,5));
				$prtstr .= PrintReady($age);
				$prtstr .= " ";
			}
		}
		if ($prt) {
			print $prtstr;
			if (!empty($prtstr)) return true;
			else return false;
		}
		else return $prtstr;
	}
	
	/**
	 * print fact PLACe TEMPle STATus
	 *
	 * @param string $factrec	gedcom fact record
	 * @param boolean $anchor	option to print a link to placelist
	 * @param boolean $sub		option to print place subrecords
	 * @param boolean $lds		option to print LDS TEMPle and STATus
	 */
	public function PrintFactPlace($anchor=false, $sub=false, $lds=false, $prt=true) {
		global $TEMPLE_CODES;
	
		$printed = false;
		$out = false;
		$prtstr = "";
		$ct = preg_match("/2 PLAC (.*)/", $this->factrec, $match);
		if ($ct>0) {
			$printed = true;
			$prtstr .= "&nbsp;";
			// Split on chinese comma 239 188 140
			$match[1] = preg_replace("/".chr(239).chr(188).chr(140)."/", ",", $match[1]);
			$levels = preg_split("/,/", $match[1]);
			if ($anchor) {
				$place = trim($match[1]);
				$place = preg_replace("/\,(\w+)/",", $1", $place);
				// reverse the array so that we get the top level first
				$levels = array_reverse($levels);
				$prtstr .= "<a href=\"placelist.php?action=show&amp;";
				foreach($levels as $pindex=>$ppart) {
					 // routine for replacing ampersands
					 $ppart = preg_replace("/amp\%3B/", "", trim($ppart));
	//				 print "parent[$pindex]=".htmlentities($ppart)."&amp;";
					 $prtstr .= "parent[$pindex]=".urlencode($ppart)."&amp;";			}
				$prtstr .= "level=".count($levels);
				$prtstr .= "\"> ";
				if (NameFunctions::HasChinese($place)) $prtstr .= PrintReady($place."&nbsp;(".NameFunctions::GetPinYin($place).")");
				else if (NameFunctions::HasCyrillic($place)) $prtstr .= PrintReady($place."&nbsp;(".NameFunctions::GetTransliterate($place).")");
				else $prtstr .= PrintReady($place);
				$prtstr .= "</a>";
			}
			else {
				$prtstr .= " -- ";
				for ($level=0; $level < GedcomConfig::$SHOW_PEDIGREE_PLACES; $level++) {
					if (!empty($levels[$level])) {
						if ($level>0) $prtstr .= ", ";
						$prtstr .= PrintReady($levels[$level]);
					}
				}
				if (NameFunctions::HasChinese($match[1]) || NameFunctions::HasCyrillic($match[1])) {
					$ptext = "(";
					for ($level=0; $level < GedcomConfig::$SHOW_PEDIGREE_PLACES; $level++) {
						if (!empty($levels[$level])) {
							if ($level>0) $ptext .= ", ";
							if (NameFunctions::HasChinese($match[1])) $ptext .= NameFunctions::GetPinYin(trim($levels[$level]));
							else $ptext .= NameFunctions::GetTransliterate(trim($levels[$level]));
						}
					}
					$ptext .= ")";
					$prtstr .= " ".PrintReady($ptext);
				}
			}
		}
		$ctn=0;
		if ($sub) {
			$placerec = GetSubRecord(2, "2 PLAC", $this->factrec);
			if (!empty($placerec)) {
				$rorec = GetSubRecord(3, "3 ROMN", $placerec);
				if (!empty($rorec)) {
					$roplac = GetGedcomValue("ROMN", 3, $rorec);
					if (!empty($roplac)) {
						if ($ct>0) $prtstr .= " - ";
						$prtstr .= " ".PrintReady($roplac);
						$rotype = GetGedcomValue("TYPE", 4, $rorec);
						if (!empty($rotype)) {
							$prtstr .= " ".PrintReady("(".$rotype.")");
						}
					}
				}
				$cts = preg_match("/\d _HEB (.*)/", $placerec, $match);
				if ($cts>0) {
	//				if ($ct>0) print "<br />\n";
					if ($ct>0) $prtstr .= " - ";
					$prtstr .= " ".PrintReady($match[1]);
				}
				$map_lati="";
				$cts = preg_match("/\d LATI (.*)/", $placerec, $match);
				if ($cts>0) {
					$map_lati = trim($match[1]);
					$prtstr .= "<br /><span class=\"FactDetailLabel\">".GM_FACT_LATI.": </span><span class=\"FactDetailField\">".$match[1]."</span>";
				}
				$map_long="";
				$cts = preg_match("/\d LONG (.*)/", $placerec, $match);
				if ($cts>0) {
					$map_long = trim($match[1]);
					$prtstr .= " <span class=\"FactDetailLabel\">".GM_FACT_LONG.": </span><span class=\"FactDetailField\">".$match[1]."</span>";
				}
				if (!empty($map_lati) and !empty($map_long)) {
					$prtstr .= " <a target=\"_BLANK\" href=\"http://www.mapquest.com/maps/map.adp?searchtype=address&formtype=latlong&latlongtype=decimal&latitude=".$map_lati."&longitude=".$map_long."\"><img src=\"images/mapq.gif\" border=\"0\" alt=\"Mapquest &copy;\" title=\"Mapquest &copy;\" /></a>";
					if (is_numeric($map_lati) && is_numeric($map_long)) {
						$prtstr .= " <a target=\"_BLANK\" href=\"http://maps.google.com/maps?spn=.2,.2&ll=".$map_lati.",".$map_long."\"><img src=\"images/bubble.gif\" border=\"0\" alt=\"Google Maps &copy;\" title=\"Google Maps &copy;\" /></a>";
					}
					else $prtstr .= " <a target=\"_BLANK\" href=\"http://maps.google.com/maps?q=".$map_lati.",".$map_long."\"><img src=\"images/bubble.gif\" border=\"0\" alt=\"Google Maps &copy;\" title=\"Google Maps &copy;\" /></a>";
					$prtstr .= " <a target=\"_BLANK\" href=\"http://www.multimap.com/map/browse.cgi?lat=".$map_lati."&lon=".$map_long."&scale=icon=x\"><img src=\"images/multim.gif\" border=\"0\" alt=\"Multimap &copy;\" title=\"Multimap &copy;\" /></a>";
					$prtstr .= " <a target=\"_BLANK\" href=\"http://www.terraserver.com/imagery/image_gx.asp?cpx=".$map_long."&cpy=".$map_lati."&res=30&provider_id=340\"><img src=\"images/terrasrv.gif\" border=\"0\" alt=\"TerraServer &copy;\" title=\"TerraServer &copy;\" /></a>";
				}
				$ctn = preg_match("/\d NOTE (.*)/", $placerec, $match);
				if ($ctn>0) {
					// To be done: part of returnstring of this function
					FactFunctions::PrintFactNotes($placerec, 3);
					$out = true;
				}
			}
		}
		if ($lds) {
			$ct = preg_match("/2 TEMP (.*)/", $this->factrec, $match);
			if ($ct>0) {
				$tcode = trim($match[1]);
				if (array_key_exists($tcode, $TEMPLE_CODES)) {
					$prtstr .= "<br />".GM_LANG_temple.": ".$TEMPLE_CODES[$tcode];
				}
				else {
					$prtstr .= "<br />".GM_LANG_temple_code.$tcode;
				}
			}
			$ct = preg_match("/2 STAT (.*)/", $this->factrec, $match);
			if ($ct>0) {
				$prtstr .= "<br />".GM_LANG_status.": ";
				$prtstr .= trim($match[1]);
			}
		}
		if ($prt) {
			print $prtstr;
			return $printed;
		}
		else return $prtstr;
	}
	private function isDeleted() {
		
		if (is_null($this->isdeleted)) {
			$changedrec = ChangeFunctions::RetrieveChangedFact($this->owner, $this->fact, $this->factrec, true);
			if (empty($changedrec)) {
				$this->isdeleted = true;
			}
			else $this->isdeleted = false;
		}
		return $this->isdeleted;
	}
}
?>