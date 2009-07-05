<?php
/**
 * Class file for a Source (SOUR) object
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
 * @version $Id$
 */
 
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class Source extends GedcomRecord {
	
	// General class information
	public $classname = "Source";
	public $datatype = "SOUR";
	
	// Data
	private $name = null;
	private $descriptor = null;
	private $adddescriptor = null;
	
	/**
	 * Constructor for source object
	 * @param string $gedrec	the raw source gedcom record
	 */
	public function __construct($id, $gedrec="", $gedcomid="") {
		
		parent::__construct($id, $gedrec, $gedcomid);
		$this->exclude_facts = "";
	}

	public function __get($property) {
		
		switch ($property) {
			case "descriptor":
				return $this->GetSourceDescriptor();
				break;
			case "adddescriptor":
				return $this->GetAddSourceDescriptor();
				break;
			case "title":
				return $this->GetTitle();
				break;
			default:
				return parent::__get($property);
				break;
		}
	}

	/**
	 * get the title of this source record
	 * @return string
	 */
	private function getTitle() {
		global $gm_lang;
		
		if (is_null($this->name)) {
			if ($this->disp) {
				$this->name = $this->GetSourceDescriptor();
				$add_descriptor = $this->GetAddSourceDescriptor();
				if ($add_descriptor) {
					if ($this->name) $this->name .= " - ".$add_descriptor;
					else $this->name = $add_descriptor;
				}
			}
			else $this->name = $gm_lang["private"];
		}
		return $this->name;
	}
	
	/**
	 * get the descriptive title of the source
	 *
	 * @param string $sid the gedcom xref id for the source to find
	 * @return string the title of the source
	 */
	private function GetSourceDescriptor() {
		global $gm_lang;
		
		if (is_null($this->descriptor)) {
			if ($this->disp) {
		
				if ($this->show_changes && $this->ThisChanged()) $gedrec = $this->GetChangedGedRec();
				else $gedrec = $this->gedrec;
		
				if (!empty($gedrec)) {
					$tt = preg_match("/1 TITL (.*)/", $gedrec, $smatch);
					if ($tt>0) {
						if (!ShowFact("TITL", $this->xref, "SOUR")) {
							$this->descriptor = $gm_lang["unknown"];
						}
						else if(!ShowFactDetails("TITL", $this->xref, "SOUR") || !$this->disp) {
							$this->descriptor = $gm_lang["private"];
						}
						else {
							$subrec = GetSubRecord(1, "1 TITL", $gedrec);
							// This automatically handles CONC/CONT lines below the title record
							$this->descriptor = GetGedcomValue("TITL", 1, $subrec);
						}				
						return $this->descriptor;
					}
					$et = preg_match("/1 ABBR (.*)/", $gedrec, $smatch);
					if ($et>0) {
						if (!ShowFact("ABBR", $this->xref, "SOUR")) {
							$this->descriptor = $gm_lang["unknown"];
						}
						else if (!ShowFactDetails("ABBR", $this->xref, "SOUR") || !$this->disp) {
							$this->descriptor =  $gm_lang["private"];
						}
						else $this->descriptor = $smatch[1];
						return $this->descriptor;
					}
				}
				else $this->descriptor = $gm_lang["unknown"];
			}
			else $this->descriptor = $gm_lang["private"];
		}
		return $this->descriptor;
	}	

	/**
	 * get the additional descriptive title of the source
	 *
	 * @param string $sid the gedcom xref id for the source to find
	 * @return string the additional title of the source
	 */
	private function GetAddSourceDescriptor() {
	
		if (is_null($this->adddescriptor)) {
			if ($this->disp) {
				if ($this->show_changes && $this->ThisChanged()) $gedrec = $this->GetChangedGedRec();
				else $gedrec = $this->gedrec;
				
				if (!empty($gedrec)) {
					$ct = preg_match("/\d ROMN (.*)/", $gedrec, $match);
			 		if ($ct>0) {
						if (!ShowFact("ROMN", $this->xref, "SOUR") || !ShowFactDetails("ROMN", $this->xref, "SOUR") || !$this->disp) {
							$this->adddescriptor = "";
						}
						else $this->adddescriptor = $smatch[1];
						return $this->adddescriptor;
			 		}
					$ct = preg_match("/\d _HEB (.*)/", $gedrec, $match);
			 		if ($ct>0) {
						if (!ShowFact("_HEB", $this->xref, "SOUR")|| !ShowFactDetails("_HEB", $this->xref, "SOUR") || !$this->disp) {
							$this->adddescriptor = "";
						}
						else $this->adddescriptor = $smatch[1];
						return $this->adddescriptor;
			 		}
			 	}
				$this->adddescriptor = "";
			}
			else $this->adddescriptor = $gm_lang["private"];
		}
		return $this->adddescriptor;
	}
	
	protected function GetLinksFromIndis() {
		global $TBLPREFIX, $indilist;

		if (!is_null($this->indilist)) return $this->indilist;
		$this->indilist = array();
		$this->indi_hide = 0;
		if (!isset($indilist)) $indilist = array();
		
		$sql = "SELECT DISTINCT i_key, i_gedcom, i_isdead, i_id, i_file  FROM ".$TBLPREFIX."source_mapping, ".$TBLPREFIX."individuals WHERE sm_sid='".$this->xref."' AND sm_gedfile='".$this->gedcomid."' AND sm_type='INDI' AND sm_gid=i_id AND sm_gedfile=i_file";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()){
			$indi = array();
			$indi["gedcom"] = $row["i_gedcom"];
			$indi["isdead"] = $row["i_isdead"];
			$indi["gedfile"] = $row["i_file"];
			$person = null;
			$person = new Person($row["i_id"], $row["i_gedcom"]);
			if ($person->disp_name) {
				$indi["names"] = $person->name_array;
				$this->indilist[$row["i_key"]] = $person;
				$indilist[$row["i_id"]] = $indi;
			}
			else $this->indi_hide++;
		}
		uasort($this->indilist, "ItemObjSort");
		$this->indi_count=count($this->indilist);
		return $this->indilist;
	}
	
	protected function GetLinksFromFams() {
		global $TBLPREFIX, $famlist;

		if (!is_null($this->famlist)) return $this->famlist;
		$this->famlist = array();
		$this->fam_hide = 0;
		if (!isset($famlist)) $famlist = array();
		
		$sql = "SELECT DISTINCT f_key, f_gedcom, f_id, f_file, f_husb, f_wife  FROM ".$TBLPREFIX."source_mapping, ".$TBLPREFIX."families WHERE sm_sid='".$this->xref."' AND sm_gedfile='".$this->gedcomid."' AND sm_type='FAM' AND sm_gid=f_id AND sm_gedfile=f_file";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()){
			$fam = array();
			$fam["gedcom"] = $row["f_gedcom"];
			$fam["gedfile"] = $row["f_file"];
			$fam["HUSB"] = SplitKey($row["f_husb"], "id");
			$fam["WIFE"] = SplitKey($row["f_wife"], "id");
			$family = null;
			$family = new Family($row["f_id"], $row["f_gedcom"]);
			if ($family->disp) {
				$this->famlist[$row["f_key"]] = $family;
				$famlist[$row["f_id"]] = $fam;
			}
			else $this->fam_hide++;
		}
		uasort($this->famlist, "ItemObjSort");
		$this->fam_count = count($this->famlist);
		return $this->famlist;
	}
	
	protected function GetLinksFromNotes() {
		global $TBLPREFIX, $otherlist;

		if (!is_null($this->notelist)) return $this->notelist;
		if (!isset($otherlist)) $otherlist = array();
		$this->notelist = array();
		$this->note_hide = 0;
		
		$sql = "SELECT DISTINCT o_key, o_id, o_gedcom, o_file FROM ".$TBLPREFIX."source_mapping, ".$TBLPREFIX."other WHERE sm_sid='".$this->xref."' AND sm_gedfile='".$this->gedcomid."' AND sm_type='NOTE' AND sm_gid=o_id AND o_file=sm_gedfile";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()){
			$note = null;
			$otherlist[$row["o_id"]]["gedcom"] = $row["o_gedcom"];
			$otherlist[$row["o_id"]]["gedfile"] = $row["o_file"];
			$note = new Note($row["o_id"], $row["o_gedcom"]);
			if ($note->disp) $this->notelist[$row["o_key"]] = $note;
			else $this->note_hide++;
		}
		uasort($this->notelist, "NotelistObjSort");
		$this->note_count=count($this->notelist);
		return $this->notelist;
	}
	
	protected function GetLinksFromMedia() {
		global $TBLPREFIX, $medialist;
		
		if (!is_null($this->medialist)) return $this->medialist;
		if (!isset($medialist)) $medialist = array();
		$this->medialist = array();
		$this->media_hide = 0;
		
		$sql = "SELECT DISTINCT m_media, m_gedrec, m_gedfile FROM ".$TBLPREFIX."source_mapping, ".$TBLPREFIX."media WHERE sm_sid='".$this->xref."' AND sm_gedfile='".$this->gedcomid."' AND sm_type='OBJE' AND sm_gid=m_media AND m_gedfile=sm_gedfile";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()) {
			$mediaitem = null;
			$medialist[$row["m_media"]]["gedcom"] = $row["m_gedrec"];
			$medialist[$row["m_media"]]["gedfile"] = $row["m_gedfile"];
			$mediaitem = new MediaItem($row["m_media"], $row["m_gedrec"], $row["m_gedfile"]);
			if ($mediaitem->disp) $this->medialist[JoinKey($row["m_media"], $row["m_gedfile"])] = $mediaitem;
			else $this->media_hide++;
		}
		uasort($this->medialist, "ItemObjSort");
		$this->media_count=count($this->medialist);
		return $this->medialist;
	}
	
	public function PrintListSource() {
		
		if (!$this->disp) return false;
		
		if (begRTLText($this->title)) print "\n\t\t\t<li class=\"rtl\" dir=\"rtl\">";
		else print "\n\t\t\t<li class=\"ltr\" dir=\"ltr\">";
		print "\n\t\t\t<a href=\"source.php?sid=$this->xref&amp;gedid=".$this->gedcomid."\" class=\"list_item\">".PrintReady($this->GetTitle());
		print $this->addxref;
		print "</a>\n";
		print "</li>\n";
	}
}
?>