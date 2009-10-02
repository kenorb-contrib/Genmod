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
	public $classname = "Source";			// Name of the class
	public $datatype = "SOUR";				// Type of data collected here
	private static $sourcecache = array();	// Holder of the instances for this class
	
	// Data
	private $name = null;					// Title of the source (both descriptors), privacy applied
	private $descriptor = null;				// Main title of the source, privacy applied
	private $adddescriptor = null;			// Additional title, privacy applied

		
	public static function GetInstance($xref, $gedrec="", $gedcomid="") {
		global $GEDCOMID;
		
		if (empty($gedcomid)) $gedcomid = $GEDCOMID;
		if (!isset(self::$sourcecache[$gedcomid][$xref])) {
			self::$sourcecache[$gedcomid][$xref] = new Source($xref, $gedrec, $gedcomid);
		}
		return self::$sourcecache[$gedcomid][$xref];
	}
	
	/**
	 * Constructor for source object
	 * @param string $gedrec	the raw source gedcom record
	 */
	public function __construct($id, $gedrec="", $gedcomid="") {

		if (is_array($gedrec)) {
			// preset some values
			// extract the construction parameters
			$gedcomid = $gedrec["s_file"];
			$id = $gedrec["s_id"];
			$gedrec = $gedrec["s_gedrec"];
		}
		
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

	public function __set($property, $value) {
		switch ($property) {
			case "addlink":
				if (is_null($this->link_array)) $this->link_array = array();
				$this->link_array[] = $value;
				break;
			default:
				parent::__set($property, $value);
				break;
		}
	}
	
	public function ObjCount() {
		$count = 0;
		foreach(self::$sourcecache as $ged => $source) {
			$count += count($source);
		}
		return $count;
	}	
	
	/**
	 * get the title of this source record
	 * @return string
	 */
	private function getTitle() {
		global $gm_lang;
		
		if (is_null($this->name)) {
			if ($this->DisplayDetails()) {
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
	private function getSourceDescriptor() {
		global $gm_lang;
		
		if (is_null($this->descriptor)) {
			if ($this->DisplayDetails()) {
		
				if ($this->show_changes && $this->ThisChanged()) $gedrec = $this->GetChangedGedRec();
				else $gedrec = $this->gedrec;
		
				if (!empty($gedrec)) {
					$tt = preg_match("/1 TITL (.*)/", $gedrec, $smatch);
					if ($tt>0) {
						$subrec = GetSubRecord(1, "1 TITL", $gedrec);
						if (!PrivacyFunctions::showFact("TITL", $this->xref, "SOUR")) {
							$this->descriptor = $gm_lang["unknown"];
						}
						else if(!PrivacyFunctions::showFactDetails("TITL", $this->xref, "SOUR") || PrivacyFunctions::FactViewRestricted($this->xref, $subrec, 2) || !$this->DisplayDetails()) {
							$this->descriptor = $gm_lang["private"];
						}
						else {
							// This automatically handles CONC/CONT lines below the title record
							$this->descriptor = GetGedcomValue("TITL", 1, $subrec);
						}				
						return $this->descriptor;
					}
					$et = preg_match("/1 ABBR (.*)/", $gedrec, $smatch);
					if ($et>0) {
						if (!PrivacyFunctions::showFact("ABBR", $this->xref, "SOUR")) {
							$this->descriptor = $gm_lang["unknown"];
						}
						else if (!PrivacyFunctions::showFactDetails("ABBR", $this->xref, "SOUR") || !$this->DisplayDetails()) {
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
	private function getAddSourceDescriptor() {
		global $gm_lang;
	
		if (is_null($this->adddescriptor)) {
			if ($this->DisplayDetails()) {
				if ($this->show_changes && $this->ThisChanged()) $gedrec = $this->GetChangedGedRec();
				else $gedrec = $this->gedrec;
				
				if (!empty($gedrec)) {
					$ct = preg_match("/\d ROMN (.*)/", $gedrec, $match);
			 		if ($ct>0) {
						if (!PrivacyFunctions::showFact("ROMN", $this->xref, "SOUR") || !PrivacyFunctions::showFactDetails("ROMN", $this->xref, "SOUR") || !$this->DisplayDetails()) {
							$this->adddescriptor = "";
						}
						else $this->adddescriptor = $match[1];
						return $this->adddescriptor;
			 		}
					$ct = preg_match("/\d _HEB (.*)/", $gedrec, $match);
			 		if ($ct>0) {
						if (!PrivacyFunctions::showFact("_HEB", $this->xref, "SOUR")|| !PrivacyFunctions::showFactDetails("_HEB", $this->xref, "SOUR") || !$this->DisplayDetails()) {
							$this->adddescriptor = "";
						}
						else $this->adddescriptor = $match[1];
						return $this->adddescriptor;
			 		}
			 	}
				$this->adddescriptor = "";
			}
			else $this->adddescriptor = $gm_lang["private"];
		}
		return $this->adddescriptor;
	}
	
	protected function getLinksFromIndis() {

		if (!is_null($this->indilist)) return $this->indilist;
		$this->indilist = array();
		$this->indi_hide = 0;
		
		$sql = "SELECT DISTINCT i_key, i_gedrec, i_isdead, i_id, i_file  FROM ".TBLPREFIX."source_mapping, ".TBLPREFIX."individuals WHERE sm_sid='".$this->xref."' AND sm_file='".$this->gedcomid."' AND sm_type='INDI' AND sm_gid=i_id AND sm_file=i_file";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()){
			$person = null;
			$person =& Person::GetInstance($row["i_id"], $row, $row["i_file"]);
			if ($person->DispName()) {
				$this->indilist[$row["i_key"]] = $person;
			}
			else $this->indi_hide++;
		}
		uasort($this->indilist, "ItemObjSort");
		$this->indi_count=count($this->indilist);
		return $this->indilist;
	}
	
	protected function getLinksFromFams() {

		if (!is_null($this->famlist)) return $this->famlist;
		$this->famlist = array();
		$this->fam_hide = 0;
		
		$sql = "SELECT DISTINCT f_key, f_gedrec, f_id, f_file, f_husb, f_wife  FROM ".TBLPREFIX."source_mapping, ".TBLPREFIX."families WHERE sm_sid='".$this->xref."' AND sm_file='".$this->gedcomid."' AND sm_type='FAM' AND sm_gid=f_id AND sm_file=f_file";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()){
			$family = null;
			$family =& Family::GetInstance($row["f_id"], $row, $row["f_file"]);
			if ($family->DisplayDetails()) {
				$this->famlist[$row["f_key"]] = $family;
			}
			else $this->fam_hide++;
		}
		uasort($this->famlist, "ItemObjSort");
		$this->fam_count = count($this->famlist);
		return $this->famlist;
	}
	
	protected function getLinksFromNotes() {

		if (!is_null($this->notelist)) return $this->notelist;
		$this->notelist = array();
		$this->note_hide = 0;
		
		$sql = "SELECT DISTINCT o_key, o_id, o_gedrec, o_file FROM ".TBLPREFIX."source_mapping, ".TBLPREFIX."other WHERE sm_sid='".$this->xref."' AND sm_file='".$this->gedcomid."' AND sm_type='NOTE' AND sm_gid=o_id AND o_file=sm_file";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()){
			$note = null;
			$note =& Note::GetInstance($row["o_id"], $row, $row["o_file"]);
			if ($note->DisplayDetails()) $this->notelist[$row["o_key"]] = $note;
			else $this->note_hide++;
		}
		uasort($this->notelist, "TitleObjSort");
		$this->note_count=count($this->notelist);
		return $this->notelist;
	}
	
	protected function getLinksFromMedia() {
		
		if (!is_null($this->medialist)) return $this->medialist;
		$this->medialist = array();
		$this->media_hide = 0;
		
		$sql = "SELECT DISTINCT m_media, m_gedrec, m_file, m_ext, m_mfile FROM ".TBLPREFIX."source_mapping, ".TBLPREFIX."media WHERE sm_sid='".$this->xref."' AND sm_file='".$this->gedcomid."' AND sm_type='OBJE' AND sm_gid=m_media AND m_file=sm_file";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()) {
			$mediaitem = null;
			$mediaitem =& MediaItem::GetInstance($row["m_media"], $row, $row["m_file"]);
			if ($mediaitem->DisplayDetails()) $this->medialist[JoinKey($row["m_media"], $row["m_file"])] = $mediaitem;
			else $this->media_hide++;
		}
		uasort($this->medialist, "TitleObjSort");
		$this->media_count=count($this->medialist);
		return $this->medialist;
	}
	
	protected function ReadSourceRecord() {
		
		$sql = "SELECT s_gedrec FROM ".TBLPREFIX."sources WHERE s_key='".JoinKey($this->xref, $this->gedcomid)."'";
		$res = NewQuery($sql);
		if ($res) {
			if ($res->NumRows() != 0) {
				$row = $res->fetchAssoc();
				$this->gedrec = $row["s_gedrec"];
			}
		}
	}
	
	// Type	=	1	: normal title (descriptor and adddescriptor
	// 			2	: descriptor
	//			3	: adddescriptor
	public function PrintListSource($useli=true, $type=1) {

		if (!$this->DisplayDetails()) return false;
		
		if ($useli) {
			if (begRTLText($this->title)) print "\n\t\t\t<li class=\"rtl\" dir=\"rtl\">";
			else print "\n\t\t\t<li class=\"ltr\" dir=\"ltr\">";
		}
		if ($type == 1) print "\n\t\t\t<a href=\"source.php?sid=$this->xref&amp;gedid=".$this->gedcomid."\" class=\"list_item\">".PrintReady($this->GetTitle());
		else if ($type == 2) print "\n\t\t\t<a href=\"source.php?sid=$this->xref&amp;gedid=".$this->gedcomid."\" class=\"list_item\">".PrintReady($this->GetSourceDescriptor());
		else if ($type == 3) print "\n\t\t\t<a href=\"source.php?sid=$this->xref&amp;gedid=".$this->gedcomid."\" class=\"list_item\">".PrintReady($this->GetAddSourceDescriptor());
		print $this->addxref;
		print "</a>\n";
		if ($useli) print "</li>\n";
	}
}
?>