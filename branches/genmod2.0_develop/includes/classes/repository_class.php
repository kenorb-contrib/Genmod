<?php
/**
 * Class file for a Repository (REPO) object
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

class Repository extends GedcomRecord {
	
	public $classname = "Repository";
	private $name = null;
	private $descriptor = null;
	private $adddescriptor = null;
	
	private $sourcelist = null;
	private $actionlist = null;
	
	/**
	 * Constructor for source object
	 * @param string $gedrec	the raw source gedcom record
	 */
	public function __construct($id, $gedrec="") {
		global $gm_username, $GEDCOM, $GEDCOMID, $Users, $otherlist;
		
		if (empty($gedrec)) {
			if (isset($otherlist[$id])) $gedrec = $otherlist[$id]["gedcom"];
			else $gedrec = FindOtherRecord($id, $GEDCOM, false, "REPO");
		}
		parent::__construct($id, $gedrec);
	}

	public function __get($property) {
		$result = NULL;
		switch ($property) {
			case "descriptor":
				return $this->GetRepoDescriptor();
				break;
			case "adddescriptor":
				return $this->GetAddRepoDescriptor();
				break;
			case "title":
				return $this->GetTitle();
				break;
			case "sourcelist":
				return $this->sourcelist;
				
				break;
			case "actionlist":
				return $this->actionlist;
				break;
			case "facts":
				$this->parseFacts();
				return $this->facts;
				break;
			default:
				return parent::__get($property);
				break;
		}
	}
	
	
	/**
	 * get the title of this repository record
	 * Titles consist of the name, the additional name.
	 * @return string
	 */
	private function getTitle() {
		global $gm_lang;
		
		if (is_null($this->name)) {
			$this->name = $this->GetRepoDescriptor();
			if ($this->disp) {
				$add_descriptor = $this->GetAddRepoDescriptor();
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
	 * get the descriptive title of the repository
	 *
	 * @param string $sid the gedcom xref id for the source to find
	 * @return string the title of the source
	 */
	private function GetRepoDescriptor() {
		global $gm_lang;
		
		if (!is_null($this->descriptor)) return $this->descriptor;
		
		if ($this->show_changes) $gedrec = $this->GetChangedGedRec();
		else $gedrec = $this->gedrec;
		
		if (!empty($gedrec)) {
			$tt = preg_match("/1 NAME (.*)/", $gedrec, $smatch);
			if ($tt>0) {
			if (!ShowFact("NAME", $this->xref, "REPO") || !ShowFactDetails("NAME", $this->xref, "REPO")) return $gm_lang["private"];
			$subrec = GetSubRecord(1, "1 NAME", $gedrec);
			// This automatically handles CONC/CONT lines below the title record
			$this->descriptor = GetGedcomValue("NAME", 1, $subrec);
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
}
?>