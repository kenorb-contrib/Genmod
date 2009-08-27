<?php
/**
 * Base class for all gedcom records
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
class GedcomRecord {
	var $gedrec = "";
	var $xref = "";
	var $type = "";
	var $changed = false;
	var $rfn = null;
	
	/**
	 * constructor for this class
	 */
	function GedcomRecord($gedrec) {

		if (empty($gedrec)) return;

		//-- set the gedcom record a privatized version
		// Better not do this. All privacy is checked again in the classes
//		$this->gedrec = privatize_gedcom($gedrec);
		$this->gedrec = $gedrec;
		$ct = preg_match("/0 @(.*)@ (\w+)/", $this->gedrec, $match);
		if ($ct>0) {
			$this->xref = trim($match[1]);
			$this->type = trim($match[2]);
		}
		if (GetChangeData(true, $this->xref, true, "", "")) $this->changed = true;
	}
	/**
	 * get the xref
	 */
	function getXref() {
		return $this->xref;
	}
	/**
	 * get the object type
	 */
	function getType() {
		return $this->type;
	}
	/**
	 * get gedcom record
	 */
	function getGedcomRecord() {
		return $this->gedrec;
	}
	// get changed gedcom record
	function getchangedGedcomRecord() {
		global $GEDCOM;
		if (GetChangeData(true, $this->xref, true, "", "")) {
			$rec = GetChangeData(false, $this->xref, true, "gedlines", "");
			return $rec[$GEDCOM][$this->xref];
		}					
		else return $this->gedrec;
	}
	
	/**
	 * check if this object is equal to the given object
	 * basically just checks if the IDs are the same
	 * @param GedcomRecord $obj
	 */
	function equals(&$obj) {
		if (is_null($obj)) return false;
		if ($this->xref==$obj->getXref()) return true;
		return false;
	}
}
?>