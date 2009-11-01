<?php
/**
 * Controller for the Realationship Page
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
 * @subpackage Charts
 * @version $Id$
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
 
class RelationshipController extends ChartController {
	
	public $classname = "RelationshipController";	// Name of this class
	public $asc = null;								// Option to show oldest on top
	private $pid1 = null;							// ID of the first person
	private $pid2 = null;							// ID of the second person
	private $person1 = null;						// Container for the first person
	private $person2 = null;						// Container for the second person
	public $path_to_find = null;					// # of path to find (starting with 0)

	
	public function __construct() {
		
		parent::__construct();
		
		if (!isset($_REQUEST["pid1"]) || $_REQUEST["pid1"] == "") $this->pid1 = "";
		else $this->pid1 = CleanInput($_REQUEST["pid1"]);
		$this->person1 =& Person::GetInstance($this->pid1, "", $this->gedcomid);
		if ($this->person1->isempty) $this->pid1 = "";

		if (!isset($_REQUEST["pid2"]) || $_REQUEST["pid2"] == "") $this->pid2 = "";
		else $this->pid2 = CleanInput($_REQUEST["pid2"]);
		$this->person2 =& Person::GetInstance($this->pid2, "", $this->gedcomid);
		if ($this->person2->isempty) $this->pid2 = "";
		
		if (!isset($_REQUEST["pretty"]) || $_REQUEST["pretty"] == "") $this->pretty = 0;
		else $this->pretty = $_REQUEST["pretty"];
		
		if (!isset($_REQUEST["asc"]) || $_REQUEST["asc"] == "") $this->asc = 1;
		else $this->asc = $_REQUEST["asc"];
		
		if (!isset($_REQUEST["followspouse"]) || $_REQUEST["followspouse"] == "") $this->pretty = 0;
		else $this->followspouse = $_REQUEST["followspouse"];
		
		if (!isset($_REQUEST["path_to_find"]) || $_REQUEST["path_to_find"] == "") {
			$this->path_to_find = "0";
			$this->pretty = 1;
			unset($_SESSION["relationships"]);
		}
		else $this->path_to_find = $_REQUEST["path_to_find"];
		
		if ($this->path_to_find == -1){
			$this->path_to_find = "0";
			unset($_SESSION["relationships"]);
		}
		
		if ($this->pid1 == "") {
			$this->pretty = 1;
			$this->followspouse = 1;
		}
	}

	public function __get($property) {
		switch($property) {
			case "pid1":
				return $this->pid1;
				break;
			case "pid2":
				return $this->pid2;
				break;
			case "person1":
				return $this->person1;
				break;
			case "person2":
				return $this->person2;
				break;
			default:
				return parent::__get($property);
				break;
		}
	}

	protected function GetPageTitle() {
		global $gm_lang;
		
		if (is_null($this->pagetitle)) {
			$this->pagetitle = "";
			if ($this->pid1 != "") $this->pagetitle .= $this->person1->name.$this->person1->addxref;
			if ($this->pid2 != "") $this->pagetitle .= "/".$this->person2->name.$this->person2->addxref;;
			$this->pagetitle .= " - ".$gm_lang["relationship_chart"];
		}
		return $this->pagetitle;
	}
}
?>