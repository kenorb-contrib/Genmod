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
	
	public $classname = "Source";
	private $name = null;
	private $descriptor = null;
	private $adddescriptor = null;
	
	private $indilist = null;
	private $indi_count = null;
	private $famlist = null;
	private $fam_count = null;
	private $medialist = null;
	private $media_count = null;
	private $notelist = null;
	private $note_count = null;
	private $exclude_facts = "";
	
	/**
	 * Constructor for source object
	 * @param string $gedrec	the raw source gedcom record
	 */
	public function __construct($id, $gedrec="") {
		global $sourcelist;
		
		if (empty($gedrec)) {
			if (isset($sourcelist[$id])) $gedrec = $sourcelist[$id]["gedcom"];
			else $gedrec = FindSourceRecord($id);
		}
		if (empty($gedrec)) $gedrec = "0 @".$id."@ SOUR\r\n";
		else $sourcelist[$id]["gedcom"] = $gedrec;
		
		parent::__construct($id, $gedrec);
	}

	public function __get($property) {
		$result = NULL;
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
			case "indilist":
				return $this->GetSourceIndis();
				break;
			case "indi_count":
				if (is_null($this->indi_count)) $this->GetSourceIndis();
				return $this->indi_count;
				break;
			case "famlist":
				return $this->GetSourceFams();
				break;
			case "fam_count":
				if (is_null($this->fam_count)) $this->GetSourceFams();
				return $this->fam_count;
				break;
			case "medialist":
				return $this->GetSourceMedia();
				break;
			case "media_count":
				if (is_null($this->media_count)) $this->GetSourceMedia();
				return $this->media_count;
				break;
			case "notelist":
				return $this->GetSourceNotes();
				break;
			case "note_count":
				if (is_null($this->note_count)) $this->GetSourceNotes();
				return $this->note_count;
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
			$this->name = $this->GetSourceDescriptor();
			if ($this->disp) {
				$add_descriptor = $this->GetAddSourceDescriptor();
				if ($add_descriptor) {
					if ($this->name) $this->name .= " - ".$add_descriptor;
					else $this->name = $add_descriptor;
				}
			}
			else $this->name = $gm_lang["private"];
		}
		if (!$this->name) return $gm_lang["unknown"];
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
		
		if (!is_null($this->descriptor)) return $this->descriptor;
		
		if ($this->show_changes) $gedrec = $this->GetChangedGedRec();
		else $gedrec = $this->gedrec;
		
		if (!empty($gedrec)) {
			$tt = preg_match("/1 TITL (.*)/", $gedrec, $smatch);
			if ($tt>0) {
				if (!ShowFact("TITL", $this->xref, "SOUR") || !ShowFactDetails("TITL", $this->xref, "SOUR")) {
					$this->descriptor = false;
					return $gm_lang["private"];
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
				if (!ShowFact("ABBR", $this->xref, "SOUR") || !ShowFactDetails("ABBR", $this->xref, "SOUR")) return $gm_lang["private"];
				$this->descriptor = $smatch[1];
				return $this->descriptor;
			}
		}
		$this->descriptor = false;
		return false;
	}	

	/**
	 * get the additional descriptive title of the source
	 *
	 * @param string $sid the gedcom xref id for the source to find
	 * @return string the additional title of the source
	 */
	private function GetAddSourceDescriptor() {
		global $GEDCOM;
	
		if (!is_null($this->adddescriptor)) return $this->adddescriptor;
		
		if ($this->show_changes) $gedrec = $this->GetChangedGedRec();
		else $gedrec = $this->gedrec;
		
		if (!empty($gedrec)) {
			$ct = preg_match("/\d ROMN (.*)/", $gedrec, $match);
	 		if ($ct>0) {
				if (!ShowFact("ROMN", $this->xref, "SOUR") || !ShowFactDetails("ROMN", $this->xref, "SOUR")) return false;
				$this->adddescriptor = $smatch[1];
				return $this->adddescriptor;
	 		}
			$ct = preg_match("/\d _HEB (.*)/", $gedrec, $match);
	 		if ($ct>0) {
				if (!ShowFact("_HEB", $this->xref, "SOUR")|| !ShowFactDetails("_HEB", $this->xref, "SOUR")) return false;
				$this->adddescriptor = $smatch[1];
				return $this->adddescriptor;
	 		}
	 	}
		$this->adddescriptor = false;
		return $this->adddescriptor;
	}
	
	private function GetSourceIndis() {
		global $GEDCOMID;

		if (!is_null($this->indilist)) return $this->indilist;
		$this->indilist = array();
		
		$links = GetSourceLinks($this->xref, "INDI", true, false);
		if (count($links) > 0) {
			$linkslst = implode("[".$GEDCOMID."]','", $links);
			$linkslst .= "[".$GEDCOMID."]'";
			$linkslst = "'".$linkslst;
			$this->indilist = GetIndiList("", $linkslst, true);
			uasort($this->indilist, "ItemSort");
		}
		$this->indi_count=count($this->indilist);
		return $this->indilist;
	}
	
	private function GetSourceFams() {
		global $GEDCOMID;

		if (!is_null($this->famlist)) return $this->famlist;
		$this->famlist = array();
		
		$links = GetSourceLinks($this->xref, "FAM", true, false);
		if (count($links) > 0) {
			$linkslst = implode("[".$GEDCOMID."]','", $links);
			$linkslst .= "[".$GEDCOMID."]'";
			$linkslst = "'".$linkslst;
			$this->famlist = GetFamList("", $linkslst, true);
			uasort($this->famlist, "ItemSort");
		}
		$this->fam_count=count($this->famlist);
		return $this->famlist;
	}
	
	private function GetSourceMedia() {
		global $GEDCOMID;
		
		if (!is_null($this->medialist)) return $this->medialist;
		$this->medialist = array();
		
		$links = GetSourceLinks($this->xref, "OBJE", true, false);
		foreach ($links as $key => $link) {
			$media = array();
			$media["gedfile"] = $GEDCOMID;
			$media["name"] = GetMediaDescriptor($link);
			$this->medialist[$link] = $media;
		}
		uasort($this->medialist, "ItemSort");
		$this->media_count=count($this->medialist);
		return $this->medialist;
	}
	
	private function GetSourceNotes() {
		global $GEDCOMID;

		if (!is_null($this->notelist)) return $this->notelist;
		$this->notelist = array();
		
		$links = GetSourceLinks($this->xref, "NOTE", true, false);
		if (is_array($links) && count($links) > 0) {
			$links = implode("','", $links);
			$links .= "'";
			$links = "'".$links;
			$note_controller = new NoteController();
			$note_controller->GetNoteList("", $links);
			$this->notelist = $note_controller->notelist;
		}
		$this->note_count=count($this->notelist);
		return $this->notelist;
	}
}
?>