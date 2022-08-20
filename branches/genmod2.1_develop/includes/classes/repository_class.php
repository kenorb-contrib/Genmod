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
 * @version $Id: repository_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
 
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class Repository extends GedcomRecord {
	
	// General class information
	public $classname = "Repository";		// Name of this class
	public $datatype = "REPO";				// Type of data
	private static $cache = array();		// Holder of the instances for this class
	
	// Data
	private $name = null;					// Full title of the repository, including all descriptors
	private $descriptor = null;				// Name of the repository
	private $adddescriptor = null;			// Additional names of this repository
		
	public static function GetInstance($xref, $gedrec="", $gedcomid="") {
		
		if (empty($gedcomid)) $gedcomid = GedcomConfig::$GEDCOMID;
		if (!isset(self::$cache[$gedcomid][$xref])) {
			self::$cache[$gedcomid][$xref] = new Repository($xref, $gedrec, $gedcomid);
		}
		return self::$cache[$gedcomid][$xref];
	}
	
	public static function NewInstance($xref, $gedrec="", $gedcomid="") {
		
		if (empty($gedcomid)) $gedcomid = GedcomConfig::$GEDCOMID;
		self::$cache[$gedcomid][$xref] = new Repository($xref, $gedrec, $gedcomid);
		return self::$cache[$gedcomid][$xref];
	}
	
	public static function IsInstance($xref, $gedcomid="") {
		
		if (empty($gedcomid)) $gedcomid = GedcomConfig::$GEDCOMID;
		if (!isset(self::$cache[$gedcomid][$xref])) return false;
		else return true;
	}
	
	public function __construct($id, $gedrec="", $gedcomid="") {
		
		if (is_array($gedrec)) {
			// preset some values
			// extract the construction parameters
			$gedcomid = $gedrec["o_file"];
			$id = $gedrec["o_id"];
			$gedrec = $gedrec["o_gedrec"];
		}
		
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
			case "name":
				return $this->GetTitle();
				break;
			default:
				return parent::__get($property);
				break;
		}
	}
		
	public function ObjCount() {
		$count = 0;
		foreach(self::$cache as $ged => $repo) {
			$count += count($repo);
		}
		return $count;
	}	
	
	/**
	 * get the title of this repository record
	 * Titles consist of the name, the additional name.
	 * @return string
	 */
	private function getTitle() {
		
		if (is_null($this->name)) {
			$this->name = $this->GetRepoDescriptor();
			if ($this->DisplayDetails()) {
				$add_descriptor = $this->GetAddRepoDescriptor();
				if ($add_descriptor) {
					if ($this->name) $this->name .= " - ".$add_descriptor;
					else $this->name = $add_descriptor;
				}
			}
			else $this->name = GM_LANG_private;
		}
		if (!$this->name) return GM_LANG_unknown;
		return $this->name;
	}
	
	/**
	 * get the descriptive title of the repository
	 *
	 * @param string $sid the gedcom xref id for the source to find
	 * @return string the title of the source
	 */
	private function GetRepoDescriptor() {
		
		if (!is_null($this->descriptor)) return $this->descriptor;
		
		if ($this->show_changes && $this->ThisChanged()) $gedrec = $this->GetChangedGedRec();
		else $gedrec = $this->gedrec;
		
		if (!empty($gedrec)) {
			$tt = preg_match("/1 NAME (.*)/", $gedrec, $smatch);
			if ($tt>0) {
				if (!PrivacyFunctions::showFact("NAME", $this->xref, "REPO") || !PrivacyFunctions::showFactDetails("NAME", $this->xref, "REPO")) return GM_LANG_private;
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
				if (!PrivacyFunctions::showFact("ROMN", $this->xref, "SOUR") || !PrivacyFunctions::showFactDetails("ROMN", $this->xref, "REPO")) return false;
				$this->adddescriptor = $match[1];
				return $this->adddescriptor;
	 		}
			$ct = preg_match("/\d _HEB (.*)/", $gedrec, $match);
	 		if ($ct>0) {
				if (!PrivacyFunctions::showFact("_HEB", $this->xref, "SOUR")|| !PrivacyFunctions::showFactDetails("_HEB", $this->xref, "REPO")) return false;
				$this->adddescriptor = $match[1];
				return $this->adddescriptor;
	 		}
	 	}
		$this->adddescriptor = "";
		return $this->adddescriptor;
	}
	
	/**
	 * get the list of sources connected to a record
	 * @return array
	 */
	protected function GetLinksFromSources() {

		if(!is_null($this->sourcelist)) return $this->sourcelist;
		$this->sourcelist = array();
		$this->sour_hide = 0;
		
		$sql = 	"SELECT DISTINCT s_key, s_id, s_gedrec, s_file FROM ".TBLPREFIX."other_mapping, ".TBLPREFIX."sources WHERE om_oid='".$this->xref."' AND om_file='".$this->gedcomid."' AND om_type='SOUR' AND s_file=om_file AND s_id=om_gid";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()){
			$source = null;
			$source =& Source::GetInstance($row["s_id"], $row, $row["s_file"]);
			if ($source->DisplayDetails()) $this->sourcelist[$row["s_key"]] = $source;
			else $this->sour_hide++;
		}
		uasort($this->sourcelist, "GedcomObjSort");
		$this->sour_count = count($this->sourcelist);
		return $this->sourcelist;
	}
	
	protected function GetLinksFromActions($status="") {
		
		if(!is_null($this->actionlist)) return $this->actionlist;
		$this->actionlist = array();
		$search = ActionController::GetSelectActionList($this->xref, "", $this->gedcomid, $status);
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
		$search = ActionController::GetSelectActionList($this->xref, "", $this->gedcomid, "", true);
		$this->action_open = $search[1];
		$this->action_closed = $search[2];
		$this->action_count = $this->action_open + $this->action_closed;
	}

	protected function ReadRepositoryRecord() {
		
		$sql = "SELECT o_gedrec FROM ".TBLPREFIX."other WHERE o_key='".DbLayer::EscapeQuery(JoinKey($this->xref, $this->gedcomid))."' AND o_type='REPO'";
		$res = NewQuery($sql);
		if ($res) {
			if ($res->NumRows() != 0) {
				$row = $res->fetchAssoc();
				$this->gedrec = $row["o_gedrec"];
			}
		}
	}
		
	// Type	=	1	: normal title (descriptor and adddescriptor
	// 			2	: descriptor
	//			3	: adddescriptor
	public function PrintListRepository($useli=true, $type=1, $prtact=true, $fact="", $paste=false) {
		global $TEXT_DIRECTION;

		if (!$this->DisplayDetails()) return false;
		
		if ($useli) {
			if (begRTLText($this->title)) print "\n\t\t\t<li class=\"rtl\" dir=\"rtl\">";
			else print "\n\t\t\t<li class=\"ltr\" dir=\"ltr\">";
		}

		if ($paste) print "<a href=\"#\" onclick=\"sndReq(document.getElementById('dummy'), 'lastused', false, 'type', '".$this->datatype."', 'id', '".$this->key."'); pasteid('".$this->xref."'); return false;\" class=\"ListItem\">";
		else print "<a href=\"repo.php?rid=".$this->xref."&amp;gedid=".$this->gedcomid."\" class=\"ListItem\">";
		if ($type == 1) print PrintReady($this->GetTitle());
		else if ($type == 2) print PrintReady($this->GetRepoDescriptor());
		else if ($type == 3) print PrintReady($this->GetAddRepoDescriptor());
		print $this->addxref;
		
		if ($prtact) {
			$this->GetLinksFromActionCount();
			if ($this->action_closed > 0) {
				if ($TEXT_DIRECTION=="ltr") print "<span class=\"ClosedAction\"> &lrm;(".$this->action_closed.")&lrm;</span>";
				else print "<span class=\"ClosedAction\"> &rlm;(".$this->action_closed.")&rlm;</span>";
			}
			if ($this->action_open > 0) {
				if ($TEXT_DIRECTION=="ltr") print "<span class=\"OpenAction\"> &lrm;(".$this->action_open.")&lrm;</span>";
				else print "<span class=\"OpenAction\"> &rlm;(".$this->action_open.")&rlm;</span>";
			}
		}			
		if (!empty($fact)) {
			print " <i>(";
			if (defined("GM_FACT_".$fact)) print constant("GM_FACT_".$fact);
			else print $fact;
			print ")</i>";
		}
		print "</a>\n";
		if ($useli) print "</li>\n";
		return true;
	}
}
?>