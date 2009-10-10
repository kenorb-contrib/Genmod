<?php
/**
 * Controller for the Placelist Page
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
 *
 * @package Genmod
 * @subpackage Lists
 * @version $Id$
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
 
/**
 * Main controller class for the individual page.
 */
class PlaceListController extends ListController {
	
	public $classname = "PlaceListController";	// Name of this class
	private $display = null;
	private $select = null;
	private $indi_total = array();
	private $indi_hide = array();
	private $sour_total = array();
	private $sour_hide = array();
	private $fam_hide = array();
	private $fam_total = array();
	
	public function __construct() {
		
		parent::__construct();
		if ($this->action = "") $this->action = "find";
		
		if (isset($_REQUEST["display"])) $this->display = $_REQUEST["display"];
		if (is_null($this->display)) $this->display = "hierarchy";
		
		if (isset($_REQUEST["select"])) $this->select = $_REQUEST["select"];
		if (is_null($this->select)) $this->select = "all";
		
	}

	public function __get($property) {
		switch($property) {
			case "select":
				return $this->select;
				break;
			case "display":
				return $this->display;
				break;
			case "indi_total":
				return $this->indi_total;
				break;
			case "indi_hide":
				return $this->indi_hide;
				break;
			case "fam_total":
				return $this->fam_total;
				break;
			case "fam_hide":
				return $this->fam_hide;
				break;
			case "sour_total":
				return $this->sour_total;
				break;
			case "sour_hide":
				return $this->sour_hide;
				break;
			default:
				return parent::__get($property);
				break;
		}
	}

	protected function GetPageTitle() {
		global $gm_lang;
		
		if (is_null($this->pagetitle)) {
			if ($this->display == "hierarchy") $this->pagetitle = $gm_lang["place_list"];
			else $this->pagetitle = $gm_lang["place_list2"];
		}
		return $this->pagetitle;
	}
		
	/**
	 * Find all of the places in the hierarchy
	 *
	 * The $parent array holds the parent hierarchy of the places
	 * we want to get.  The level holds the level in the hierarchy that
	 * we are at.
	 *
	 * @package Genmod
	 * @subpackage Places
	 */
	public function GetPlaceList($parent, $level) {
		global $GEDCOMID;
	
		$placelist = array();
		// --- find all of the place in the file
		if ($level==0) $sql = "SELECT p_place FROM ".TBLPREFIX."places WHERE p_level=0 AND p_file='".$GEDCOMID."' ORDER BY p_place";
		else {
			$parent_id = $this->GetPlaceParentId($parent, $level);
			$sql = "SELECT p_place FROM ".TBLPREFIX."places WHERE p_level=$level AND p_parent_id=$parent_id AND p_file='".$GEDCOMID."' ORDER BY p_place";
		}
		$res = NewQuery($sql);
		while ($row = $res->fetchAssoc()) {
			$placelist[] = $row["p_place"];
		}
		$res->FreeResult();
		if (count($placelist == 0)) $this->action = "show";
		uasort($placelist, "stringsort");
		return $placelist;
	}
	
	/**
	 * get all of the place connections
	 * @param array $parent
	 * @param int $level
	 * @return array
	 */
	public function GetPlacePositions($parent, $level, $factsel) {
		global $GEDCOMID;
		
		$positions = array();
		$indisel = array();
		$famsel = array();
		$soursel = array();
		
		$p_id = $this->GetPlaceParentId($parent, $level);
		$sql = "SELECT DISTINCT pl_gid, pl_type FROM ".TBLPREFIX."placelinks WHERE pl_p_id='".$p_id."' AND pl_file='".$GEDCOMID."'";
		$res = NewQuery($sql);
		while ($row = $res->fetchAssoc()) {
			// We want to build a cache for all the attached indi's, fams and sources
			if ($row["pl_type"] == "INDI") $indisel[] = JoinKey($row["pl_gid"], $GEDCOMID);
			elseif ($row["pl_type"] == "FAM") $famsel[] = JoinKey($row["pl_gid"], $GEDCOMID);
			elseif ($row["pl_type"] == "SOUR") $soursel[] = JoinKey($row["pl_gid"], $GEDCOMID);
		}
		$positions["INDI"] = array();
		if (count($indisel) > 0) {
			$select = "'".implode("','", $indisel)."'";
			$indilist =& ListFunctions::GetIndiList("no", $select, false);
			usort($indilist, "ItemObjSort");
			foreach($indilist as $key => $indi) {
				if ($factsel != "all") {
					$add = false;
					$facts =& $indi->SelectFacts(array($factsel));
					foreach($facts as $key => $fact) {
						$prec = GetGedcomValue("PLAC", 2, $fact->factrec);
						$add = $this->ComparePlace($parent, $prec);
						if ($add) break;
					}
					
				}
				else $add = true;
				if ($add) {
					$this->indi_total[JoinKey($indi->xref, $GEDCOMID)] = 1;
					if ($indi->disp_name) $positions["INDI"][] = $indi;
					else $this->indi_hide[JoinKey($indi->xref, $GEDCOMID)] = 1;
				}
			}
		}
		
		$positions["FAM"] = array();
		if (count($famsel) > 0) {
			$famlist =& ListFunctions::GetfamList("no", "'".implode("','", $famsel)."'", false);
			usort($famlist, "ItemObjSort");
			foreach($famlist as $key => $fam) {
				if ($factsel != "all") {
					$add = false;
					$facts =& $fam->SelectFacts(array($factsel));
					foreach($facts as $key => $fact) {
						$prec = GetGedcomValue("PLAC", 2, $fact->factrec);
						$add = $this->ComparePlace($parent, $prec);
						if ($add) break;
					}
				}
				else $add = true;
				if ($add) {
					$this->fam_total[JoinKey($fam->xref, $GEDCOMID)] = 1;
					if ($fam->disp) $positions["FAM"][] = $fam;
					else $this->fam_hide[JoinKey($fam->xref, $GEDCOMID)] = 1;
				}
			}
		}
		
		$positions["SOUR"] = array();
		if (count($soursel) > 0) {
			$sourcelist =& ListFunctions::GetSourceList("'".implode("','", $soursel)."'", false);
			usort($sourcelist, "SourceDescrSort");
			foreach($sourcelist as $key => $source) {
				if ($factsel != "all") {
					$add = false;
					$facts =& $source->SelectFacts(array($factsel));
					foreach($facts as $key => $fact) {
						$prec = GetGedcomValue("PLAC", 3, $fact->factrec);
						$add = $this->ComparePlace($parent, $prec);
						if ($add) break;
					}
				}
				else $add = true;
				if ($add) {
					$this->sour_total[JoinKey($source->xref, $GEDCOMID)] = 1;
					if ($source->disp) $positions["SOUR"][] = $source;
					else $this->sour_hide[JoinKey($source->xref, $GEDCOMID)] = 1;
				}
			}
		}
		return $positions;
	}
	
	/**
	 * get place parent ID
	 * @param array $parent
	 * @param int $level
	 * @return int
	 */
	private function GetPlaceParentId($parent, $level) {
		global $GEDCOMID;
	
		$parent_id=0;
		for($i=0; $i<$level; $i++) {
			$escparent = preg_replace("/\?/","\\\\\\?", DbLayer::EscapeQuery($parent[$i]));
			$psql = "SELECT p_id FROM ".TBLPREFIX."places WHERE p_level='".$i."' AND p_parent_id='".$parent_id."' AND p_place LIKE '".$escparent."' AND p_file='".$GEDCOMID."' ORDER BY p_place";
			$res = NewQuery($psql);
			$row = $res->fetchAssoc();
			$res->FreeResult();
			if (empty($row["p_id"])) break;
			$parent_id = $row["p_id"];
		}
		return $parent_id;
		
	}
	
	private function ComparePlace($parray, $location) {
		
		$location = preg_replace("/".chr(239).chr(188).chr(140)."/", ",", $location);
		$places = preg_split("/,/", $location);
		$secalp = array_reverse($places);
		foreach($parray as $key => $part) {
			if (!isset($secalp[$key]) || $part != trim($secalp[$key])) return false;
		}
		return true;
	}
}
?>
