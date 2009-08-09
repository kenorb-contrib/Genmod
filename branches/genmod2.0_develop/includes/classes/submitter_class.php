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
 * @version $Id$
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class Submitter extends GedcomRecord {

	// General class information
	public $classname = "Submitter";			// Name of this class
	public $datatype = "SUBM";					// Type of data collected here
	private static $submittercache = array(); 	// Holder of the instances for this class
	
	private $name = null;					// Printable name of the person, after applying privacy (can be unknown of private)

	
	public static function GetInstance($xref, $gedrec="", $gedcomid="") {
		global $GEDCOMID;
		
		if (empty($gedcomid)) $gedcomid = $GEDCOMID;
		if (!isset(self::$submittercache[$gedcomid][$xref])) {
			self::$submittercache[$gedcomid][$xref] = new Submitter($xref, $gedrec, $gedcomid);
		}
		return self::$submittercache[$gedcomid][$xref];
	}
		
	/**
	 * Constructor for submitter object
	 * @param string $gedrec	the raw submitter gedcom record
	 */
	public function __construct($id, $gedrec="", $changed=false) {
		
		parent::__construct($id, $gedrec);

		$this->tracefacts = false;
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
			$ct = preg_match("/1 NAME (.*)/", $this->gedrec, $match);
			if ($ct > 0) $this->name = $match[1];
			else $this->name = "";
		}
		return $this->name;
	}
}
?>