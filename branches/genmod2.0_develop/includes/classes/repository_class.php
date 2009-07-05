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
	
	// General class information
	public $classname = "Repository";
	public $datatype = "REPO";
	
	// Data
	private $name = null;
	private $descriptor = null;
	private $adddescriptor = null;
		
	public function __construct($id, $gedrec="", $gedcomid="") {
		
		parent::__construct($id, $gedrec, $gedcomid);
		$this->exclude_facts = "";
	}

	public function __get($property) {
		
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
		
		if ($this->show_changes && $this->ThisChanged()) $gedrec = $this->GetChangedGedRec();
		else $gedrec = $this->gedrec;
		
		if (!empty($gedrec)) {
			$tt = preg_match("/1 NAME (.*)/", $gedrec, $smatch);
			if ($tt>0) {
				if (!ShowFact("NAME", $this->xref, "REPO") || !ShowFactDetails("NAME", $this->xref, "REPO")) return $gm_lang["private"];
				$subrec = GetSubRecord(1, "1 NAME", $gedrec);
				// This automatically handles CONC/CONT lines below the title record
				$this->descriptor = GetGedcomValue("NAME", 1, $subrec);
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
	private function GetAddRepoDescriptor() {
	
		if (!is_null($this->adddescriptor)) return $this->adddescriptor;
		
		if ($this->show_changes && $this->ThisChanged()) $gedrec = $this->GetChangedGedRec();
		else $gedrec = $this->gedrec;
		
		if (!empty($gedrec)) {
			$ct = preg_match("/\d ROMN (.*)/", $gedrec, $match);
	 		if ($ct>0) {
				if (!ShowFact("ROMN", $this->xref, "SOUR") || !ShowFactDetails("ROMN", $this->xref, "REPO")) return false;
				$this->adddescriptor = $smatch[1];
				return $this->adddescriptor;
	 		}
			$ct = preg_match("/\d _HEB (.*)/", $gedrec, $match);
	 		if ($ct>0) {
				if (!ShowFact("_HEB", $this->xref, "SOUR")|| !ShowFactDetails("_HEB", $this->xref, "REPO")) return false;
				$this->adddescriptor = $smatch[1];
				return $this->adddescriptor;
	 		}
	 	}
		$this->adddescriptor = false;
		return $this->adddescriptor;
	}
	
	/**
	 * get the list of sources connected to a record
	 * @return array
	 */
	protected function GetLinksFromSources() {
		global $TBLPREFIX, $sourcelist;

		if(!is_null($this->sourcelist)) return $this->sourcelist;
		if (!isset($sourcelist)) $sourcelist = array();
		$this->sourcelist = array();
		$this->sour_hide = 0;
		
		$sql = 	"SELECT DISTINCT s_key, s_id, s_gedcom, s_file FROM ".$TBLPREFIX."other_mapping, ".$TBLPREFIX."sources WHERE om_oid='".$this->xref."' AND om_gedfile='".$this->gedcomid."' AND om_type='SOUR' AND s_file=om_gedfile AND s_id=om_gid";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()){
			$source = null;
			$sourcelist[$row["s_id"]]["gedcom"] = $row["s_gedcom"];
			$sourcelist[$row["s_id"]]["gedfile"] = $row["s_file"];
			$source = new Source($row["s_id"], $row["s_gedcom"]);
			if ($source->disp) $this->sourcelist[$row["s_key"]] = $source;
			else $this->sour_hide++;
		}
		uasort($this->sourcelist, "GedcomObjSort");
		$this->sour_count = count($this->sourcelist);
		return $this->sourcelist;
	}
	
	protected function GetLinksFromActions($status="") {
		global $TBLPREFIX, $Users;

		if(!is_null($this->actionlist)) return $this->actionlist;
		$this->actionlist = array();
		if ($Users->ShowActionLog()) { 
			$sql = "SELECT * FROM ".$TBLPREFIX."actions WHERE a_gedfile='".$this->gedcomid."' AND a_repo='".$this->xref."'";
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
		$sql = "SELECT count(a_status) as count, a_status FROM ".$TBLPREFIX."actions WHERE a_gedfile='".$this->gedcomid."' AND a_repo='".$this->xref."' GROUP BY a_status";
		$res = NewQuery($sql);
		while ($row = $res->FetchAssoc()) {
			if ($row["a_status"] == "1") $this->action_open = $row["count"];
			else if ($row["a_status"] == "0") $this->action_closed = $row["count"];
		}
		if (is_null($this->action_open)) $this->action_open = 0;
		if (is_null($this->action_closed)) $this->action_closed = 0;
		$this->actioncount = $this->action_open + $this->action_closed;
	}
	
	public function PrintListRepository() {
		global $TEXT_DIRECTION;

		if (begRTLText($this->title)) print "\n\t\t\t<li class=\"rtl\" dir=\"rtl\">";
		else print "\n\t\t\t<li class=\"ltr\" dir=\"ltr\">";

		print "<a href=\"repo.php?rid=".$this->xref."&amp;gedid=".$this->gedcomid."\" class=\"list_item\">";
		print PrintReady($this->title);
		print $this->addxref;
		
		if ($this->action_closed > 0) {
			if ($TEXT_DIRECTION=="ltr") print "<span class=\"error\"> &lrm;(".$this->action_closed.")&lrm;</span>";
			else print "<span class=\"error\"> &rlm;(".$this->action_closed.")&rlm;</span>";
		}
		if ($this->action_open > 0) {
			if ($TEXT_DIRECTION=="ltr") print "<span class=\"okay\"> &lrm;(".$this->action_open.")&lrm;</span>";
			else print "<span class=\"okay\"> &rlm;(".$this->action_open.")&rlm;</span>";
		}
			
		print "</a></li>\n";
	}
}
?>