<?php
/**
 * Class file for Associates
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
 * @version $Id: asso_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class Asso {
	
	// General class information
	public $classname = "Asso";	// The name of this class
	
	// Data
	private $key1 = null;		// Combikey ID of the family or person the associate relates to (and has the ASSO record)
	private $xref1 = null;		// ID of the family or person the associate relates to (and has the ASSO record)
	private $associated = null;	// Holder for the family or person object for $xref1
	private $key2 = null;		// Combikey ID of the family or person the associate relates to (and has the ASSO record)
	private $xref2 = null;		// ID of the family or person the associate relates to (and has the ASSO record)
	private $assoperson = null;	// Holder for the person object for $xref2
	private $type = null;		// Type of ID for $xref1
	private $gedcomid = null;	// Gedcom ID where the association exists in
	private $fact = null;		// Fact of $xref1 that the asso relates to, empty if relating to the FAM/INDI on level 1
	private $disp = null;		// We can display this asso relation if:
								//	1. We can display $xref1
								//	2. We can display the fact for $xref1
								//  3. We can display the name of $xref2
	private $resn = null;		// Resn value for the asso fact or person/family
	private $role = null;		// Role of xref2
	
	private $resnvalues = array(""=>"", "n"=>"none", "l"=>"locked", "p"=>"privacy", "c"=>"confidential");
	private $typevalues = array("I"=>"INDI", "F"=>"FAM");
	
	public function __construct($values="") {
		
		if (is_array($values)) {
			$this->key1 = $values["as_key"];
			$this->key2 = $values["as_pid"];
			$this->xref1 = SplitKey($this->key1, "id");
			$this->xref2 = SplitKey($this->key2, "id");
			$this->type = $this->typevalues[$values["as_type"]];
			$this->gedcomid = $values["as_file"];
			$this->fact = $values["as_fact"];
			$this->resn = $this->resnvalues[$values["as_resn"]];
			$this->role = $values["as_rela"];
		}
		else return false;
	}

	public function __get($property) {
		switch ($property) {
			case "xref1":
				return $this->xref1;
				break;
			case "key1":
				return $this->key1;
				break;
			case "associated":
				return $this->GetAssociated();;
				break;
			case "xref2":
				return $this->xref2;
				break;
			case "key2":
				return $this->key2;
				break;
			case "assoperson":
				return $this->GetAssoPerson();
				break;
			case "type":
				return $this->type;
				break;
			case "gedcomid":
				return $this->gedcomid;
				break;
			case "fact":
				return $this->fact;
				break;
			case "disp":
				return $this->CanDisplay();
				break;
			case "resn":
				return $this->resn;
				break;
			case "role":
				return $this->role;
				break;
			default:
				PrintGetSetError($property, get_class($this), "get");
				break;
		}
	}
	
	public function __set($property, $value) {
		switch ($property) {
			default:
				PrintGetSetError($property, get_class($this), "set");
				break;
		}
	}
	
	private function GetAssoPerson() {
		
		if (!is_object($this->assoperson)) {
			$this->assoperson = Person::GetInstance($this->xref2, "", $this->gedcomid);
		}
		return $this->assoperson;
	}
	
	private function GetAssociated() {
		
		if (!is_object($this->associated)) {
			if ($this->type == "INDI") $this->associated = Person::GetInstance($this->xref1, "", $this->gedcomid);
			else $this->associated = Family::GetInstance(SplitKey($this->xref1, "id"), "", $this->gedcomid);
		}
		return $this->associated;
	}
	
	private function CanDisplay() {
		
		if (is_null($this->disp)) {
			// The associated must be visible WITH his details, as ASSO is fact related!
			// The asso can just have his name displayed
			if (!$this->GetAssociated()->disp) $this->disp = false;
			else if (!$this->GetAssoPerson()->disp_name) $this->disp = false;
			if (is_null($this->disp)) {
				if ($this->fact != "" && !PrivacyFunctions::showFact($this->fact, $this->xref1)) $this->disp = false;
				else {
					if ($this->fact != "") {
						$rec = "1 ".$this->fact."\r\n2 ASSO @".$this->xref2."@\r\n2 RESN ".$this->resn."r\n";
						$disp = PrivacyFunctions::FactViewRestricted($this->xref1, $rec, 2);
					}
					else {
						$rec = "1 ASSO @".SplitKey($this->xref2, "id")."@\r\n2 RESN ".$this->resn."\r\n";
						$disp = PrivacyFunctions::FactViewRestricted($this->xref1, $rec, 2);
					}
					if (!$disp) $this->disp = false;
				}
			}
			if (is_null($this->disp)) $this->disp = true;
		}
		return $this->disp;
	}
}
?>