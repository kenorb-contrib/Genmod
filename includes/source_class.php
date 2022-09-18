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
 * @version $Id: source_class.php,v 1.21 2009/03/16 19:51:12 sjouke Exp $
 */
require_once 'includes/gedcomrecord.php';
class Source extends GedcomRecord {
	var $disp = true;
	var $name = "";
	var $sourcefacts = null;
	var $indilist = null;
	var $famlist = null;
	var $medialist = null;
	var $notelist = null;
	var $sournew = false;
	var $sourdeleted = false;
	var $showchanges = false;
	
	/**
	 * Constructor for source object
	 * @param string $gedrec	the raw source gedcom record
	 */
	function Source($gedrec) {
		global $gm_username, $GEDCOM, $Users;
		parent::GedcomRecord($gedrec);
		$this->disp = displayDetailsByID($this->xref, "SOUR", 1, true);
		if ($this->disp) {
			$this->name = GetSourceDescriptor($this->xref);
			$add_descriptor = GetAddSourceDescriptor($this->xref);
			if ($add_descriptor) $this->name .= " - ".$add_descriptor;
			if ((!isset($show_changes) || $show_changes != "no") && $Users->UserCanEdit($gm_username)) $this->showchanges = true;
			if ($this->showchanges && GetChangeData(true, $this->xref, true, "", "")) {
				$rec = GetChangeData(false, $this->xref, true, "gedlines", "");
				if (empty($rec[$GEDCOM][$this->xref])) $this->sourdeleted = true;
			}
		}
		
	}
	
	/**
	 * Check if privacy options allow this record to be displayed
	 * @return boolean
	 */
	function canDisplayDetails() {
		return $this->disp;
	}
	
	/**
	 * get the title of this source record
	 * @return string
	 */
	function getTitle() {
		global $gm_lang;
		
		if (empty($this->name)) return $gm_lang["unknown"];
		return $this->name;
	}
	
	/**
	 * get source facts array
	 * @return array
	 */
	function getSourceFacts() {
		$this->parseFacts();
		return $this->sourcefacts;
	}
	
	/**
	 * Parse the facts from the individual record
	 */
	function parseFacts() {
		if (!is_null($this->sourcefacts)) return;
		$this->sourcefacts = array();
		$this->allsourcesubs = GetAllSubrecords($this->gedrec, "", true, false, false);
		foreach ($this->allsourcesubs as $key => $subrecord) {
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
			if (!empty($fact) ) {
				$this->sourcefacts[] = array($fact, $subrecord, $count[$fact]);
			}
		}
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
			if (!empty($fact) ) {
				$this->sourcefacts[] = array($fact, $newrec, $count[$fact], "new");
			}
		}
		SortFacts($this->sourcefacts, "SOUR");
	}
	/**
	 * Merge the facts from another Source object into this object
	 * for generating a diff view
	 * @param Source $diff	the source to compare facts with
	 */
	function diffMerge(&$diff) {
		if (is_null($diff)) return;
		$this->parseFacts();
		$diff->parseFacts();
		
		//-- update old facts
		foreach($this->sourcefacts as $key=>$fact) {
			$found = false;
			foreach($diff->sourcefacts as $indexval => $newfact) {
				$newfact=preg_replace("/\\\/", "/", $newfact);
				if (trim($newfact[0])==trim($fact[0])) {
					$found = true;
					break;
				}
			}
			if (!$found) {
				$this->sourcefacts[$key][0].="\nGM_OLD\n";
			}
		}
		//-- look for new facts
		foreach($diff->sourcefacts as $key=>$newfact) {
			$found = false;
			foreach($this->sourcefacts as $indexval => $fact) {
				$newfact=preg_replace("/\\\/", "/", $newfact);
				if (trim($newfact[0])==trim($fact[0])) {
					$found = true;
					break;
				}
			}
			if (!$found) {
				$newfact[0].="\nGM_NEW\n";
				$this->sourcefacts[]=$newfact;
			}
		}
	}
	
	/**
	 * get the list of individuals connected to this source
	 * @return array
	 */
	function getSourceIndis() {
		global $REGEXP_DB, $GEDCOMID;
		if (!is_null($this->indilist)) return $this->indilist;
		$links = GetSourceLinks($this->xref, "INDI", true, false);
		if (count($links) > 0) {
			$linkslst = implode("[".$GEDCOMID."]','", $links);
			$linkslst .= "[".$GEDCOMID."]'";
			$linkslst = "'".$linkslst;
			$this->indilist = GetIndiList("", $linkslst, true);
			uasort($this->indilist, "ItemSort");
		}
		else $this->indilist = array();
		return $this->indilist;
	}
	
	/**
	 * get the list of families connected to this source
	 * @return array
	 */
	function getSourceFams() {
		global $REGEXP_DB, $GEDCOMID;
		if (!is_null($this->famlist)) return $this->famlist;
		$links = GetSourceLinks($this->xref, "FAM", true, false);
		if (count($links) > 0) {
			$linkslst = implode("[".$GEDCOMID."]','", $links);
			$linkslst .= "[".$GEDCOMID."]'";
			$linkslst = "'".$linkslst;
			$this->famlist = GetFamList("", $linkslst, true);
			uasort($this->famlist, "ItemSort");
		}
		else $this->famlist = array();
		return $this->famlist;
	}
	
	/**
	 * get the list of media connected to this source
	 * @return array
	 */
	function getSourceMedia() {
		global $TBLPREFIX, $GEDCOMID;
		
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
		return $this->medialist;
	}
	/**
	 * get the list of media connected to this source
	 * @return array
	 */
	function getSourceNotes() {
		global $TBLPREFIX, $GEDCOMID;

		if (!is_null($this->notelist)) return $this->notelist;
		$this->notelist = array();
		
		$links = GetSourceLinks($this->xref, "NOTE", true, false);
		if (is_array($links) && count($links) > 0) {
			$links = implode("','", $links);
			$links .= "'";
			$links = "'".$links;
			require_once("includes/controllers/note_ctrl.php");
			$note_controller->GetNoteList("", $links);
			$this->notelist = $note_controller->notelist;
		}
		return $this->notelist;
	}
}
?>