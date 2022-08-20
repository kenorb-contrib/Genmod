<?php
/**
 * Class file for a submitter
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
 * @version $Id: submitter_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class Submitter extends GedcomRecord {

	// General class information
	public $classname = "Submitter";	// Name of this class
	public $datatype = "SUBM";			// Type of data collected here
	private static $cache = array(); 	// Holder of the instances for this class
	
	private $name = null;				// Printable name of the person, after applying privacy (can be unknown or private)

	
	public static function GetInstance($xref, $gedrec="", $gedcomid="") {
		
		if (empty($gedcomid)) $gedcomid = GedcomConfig::$GEDCOMID;
		if (!isset(self::$cache[$gedcomid][$xref])) {
			self::$cache[$gedcomid][$xref] = new Submitter($xref, $gedrec, $gedcomid);
		}
		return self::$cache[$gedcomid][$xref];
	}
		
	public static function NewInstance($xref, $gedrec="", $gedcomid="") {
		
		if (empty($gedcomid)) $gedcomid = GedcomConfig::$GEDCOMID;
		self::$cache[$gedcomid][$xref] = new Submitter($xref, $gedrec, $gedcomid);
		return self::$cache[$gedcomid][$xref];
	}
	
	public static function IsInstance($xref, $gedcomid="") {
		
		if (empty($gedcomid)) $gedcomid = GedcomConfig::$GEDCOMID;
		if (!isset(self::$cache[$gedcomid][$xref])) return false;
		else return true;
	}
	
	/**
	 * Constructor for submitter object
	 * @param string $gedrec	the raw submitter gedcom record
	 */
	public function __construct($id, $gedrec="", $gedcomid) {
		
		if (is_array($gedrec)) {
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
			case "name":
				return $this->getName();
				break;
			default:
				return parent::__get($property);
				break;
		}
	}
	
	private function getName() {
		
		if (is_null($this->name)) {
			if ($this->show_changes && $this->ThisChanged()) $gedrec = $this->GetChangedGedRec();
			else $gedrec = $this->gedrec;
			$ct = preg_match("/1 NAME (.*)/", $gedrec, $match);
			if ($ct > 0) $this->name = $match[1];
			else $this->name = "";
		}
		return $this->name;
	}
	
	protected function ReadSubmitterRecord() {
		
		$sql = "SELECT o_gedrec FROM ".TBLPREFIX."other WHERE o_key='".DbLayer::EscapeQuery(JoinKey($this->xref, $this->gedcomid))."'";
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