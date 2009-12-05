<?php
/**
 * Class file for a gedcom header
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

class Header extends GedcomRecord {

	// General class information
	public $classname = "Header";			// Name of this class
	public $datatype = "HEAD";				// Type of data collected here
	private static $headercache = array(); 	// Holder of the instances for this class
	private $placeformat = null;			// Order of place hierarchy
	
	
	public static function GetInstance($xref, $gedrec="", $gedcomid="") {
		global $GEDCOMID;
		
		if (empty($gedcomid)) $gedcomid = $GEDCOMID;
		if (!isset(self::$headercache[$gedcomid][$xref])) {
			self::$headercache[$gedcomid][$xref] = new Header($xref, $gedrec, $gedcomid);
		}
		return self::$headercache[$gedcomid][$xref];
	}
		
	/**
	 * Constructor for submitter object
	 * @param string $gedrec	the raw submitter gedcom record
	 */
	public function __construct($id, $gedrec="", $gedcomid) {
		
		parent::__construct($id, $gedrec, $gedcomid);

		$this->exclude_facts = "";
		
	}
	
	public function __get($property) {
		
		switch ($property) {
			case "lastchanged":
				return $this->HeaderLastChanged();
				break;
			case "placeformat":
				return $this->GetPlaceFormat();
				break;
			default:
				return parent::__get($property);
				break;
		}
	}
	
	private function HeaderLastChanged() {
		if (is_null($this->lastchanged)) {
			$this->lastchanged = GetGedcomValue("DATE", 1, $this->gedrec, "", false);
			$add = GetGedcomValue("DATE:TIME", 1, $this->gedrec);
			if ($add) $this->lastchanged .= " ".$add;
			$this->lastchanged = strtotime($this->lastchanged);
		}
		return $this->lastchanged;
	}

	private function GetPlaceFormat() {
		
		if (is_null($this->placeformat)) {
			$this->placeformat = GetGedcomValue("PLAC:FORM", 1, $this->gedrec, "", false);
		}
		return $this->placeformat;
	}
		
	protected function ReadHeaderRecord() {
		
		$sql = "SELECT o_gedrec FROM ".TBLPREFIX."other WHERE o_key='".JoinKey($this->xref,	$this->gedcomid)."'";
		$res = NewQuery($sql);
		if ($res) {
			if ($res->NumRows() != 0) {
				$row = $res->fetchAssoc();
				$this->gedrec = $row["o_gedrec"];
			}
		}
	}
	
}
?>