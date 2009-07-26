<?php
/**
 * Class file for a fact object
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

class Fact {
	
	// General class information
	public $classname = "Fact";	// Name of this class
	public $datatype = "sub";	// Datataype os the data here
	
	// Data
	private $owner = null;		// Xref of the owner of this fact
	private $fact = null;		// Fact/event
	private $factrec = null;	// The complete record
	private $disp = null;		// Result of ShowFactDetails
	private $count = null;		// N-th fact of this type for the owner
	private $style = null;		// Style to print this fact with
	private $descr = null;		// Fact description
		
	public function __construct($parent, $fact, $factrec, $count, $style = "") {
		
		$this->fact = trim($fact);
		$this->factrec = $factrec;
		$this->owner = $parent;
		$this->count = $count;
		$this->style = $style;
	}

	public function __get($property) {
		
		switch ($property) {
			case "fact":
				return $this->fact;
				break;
			case "factrec":
				return $this->factrec;
				break;
			case "disp":
				return $this->ShowDetails();
				break;
			case "count":
				return $this->count;
				break;
			case "style":
				return $this->style;
				break;
			case "descr":
				return $this->getFactDescription();
				break;
			default:
				print "<span class=\"error\">Invalid property ".$property." for __get in fact class</span><br />";
				break;
		}
	}
		
	public function __set($property, $value) {
		
		switch ($property) {
			case "style":
				$this->style = $value;
				break;
			case "factrec":
				$this->factrec = $value;
				break;
			default:
				print "<span class=\"error\">Invalid property ".$property." for __set in fact class</span><br />";
				break;
		}
	}
	
	private function getFactDescription() {
		global $factarray;
		
		if (is_null($this->descr)) {
			if (substr($this->fact, 0, 1) == "X") $fact = substr($this->fact, 1);
			else $fact = $this->fact;
			$this->descr = $factarray[$fact];
		}
		return $this->descr;
	}
	
	
	/**
	 * get the title of this repository record
	 * Titles consist of the name, the additional name.
	 * @return string
	 */
	private function ShowDetails() {
		global $global_facts, $person_facts, $gm_username, $Users;
		
		if ($this->disp == null) {
	
			$facts = array();
			// Handle the close relatives facts just as if they were normal facts
			$f = substr($this->fact, 1, 6);
			if ($f == "_BIRT_" || $f == "_DEAT_" && $f == "_MARR_") {
				$facts[] = substr($this->fact, 2, 4);
				
			}
			else $facts[] = $this->fact;
			
			//-- check for EVEN type facts
			$ct = preg_match("/2 TYPE (.*)/", $this->factrec, $match);
			if ($ct>0) $facts[] = trim($match[1]);
			
			$this->disp = true;
			foreach($facts as $key => $fact) {
			
				//-- if $PRIV_HIDE even admin users won't see everything
				if (isset($global_facts[$fact])) {
					//-- first check the global facts array
					if (isset($global_facts[$fact]["details"])) {
						if ($global_facts[$fact]["details"] < $Users->getUserAccessLevel($gm_username)) $this->disp = false;
					}
				}
				
				//-- check the person facts array
				if (isset($person_facts[$this->owner][$fact]["details"])) {
					if ($person_facts[$this->owner][$fact]["details"] < $Users->getUserAccessLevel($gm_username)) $this->disp = false;
				}
			}
		}
		return $this->disp;
	}
}
?>