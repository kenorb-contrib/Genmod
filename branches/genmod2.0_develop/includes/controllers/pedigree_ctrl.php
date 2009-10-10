<?php
/**
 * Controller for the Pedigree Page
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
 
class PedigreeController extends ChartController {
	
	public $classname = "PedigreeController";	// Name of this class
	private $root = null;						// Holder for the root person object
	private $show_full = null;					// Display details in chart boxes
	private $talloffset = null;					// Orientation of chart
	private $min_generation = null;				// Generate an error that a minimum number of generations must be displayed
	private $max_generation = null;				// Generate an error that no more than a maximum number of generations can be displayed
	private $num_generations = null;			// Number of generations to display

	
	public function __construct() {
		global $PEDIGREE_FULL_DETAILS, $PEDIGREE_LAYOUT;
		global $DEFAULT_PEDIGREE_GENERATIONS, $MAX_PEDIGREE_GENERATIONS;
		
		parent::__construct();
		if ($this->action = "") $this->action = "find";
		
		if (isset($_REQUEST["rootid"])) $this->xref = $_REQUEST["rootid"];
		$this->xref = ChartFunctions::CheckRootId(CleanInput($this->xref));
		
		if (isset($_REQUEST["show_full"])) $this->show_full = $_REQUEST["show_full"];
		else $this->show_full = $PEDIGREE_FULL_DETAILS;
		if ($this->show_full == "") $this->show_full = 0;
		
		if (isset($_REQUEST["talloffset"])) $this->talloffset = $_REQUEST["talloffset"];
		else $this->talloffset = $PEDIGREE_LAYOUT;
		if ($this->talloffset == "") $this->talloffset = 0;
		
		if (!isset($_REQUEST["num_generations"]) || $_REQUEST["num_generations"] == "") $this->num_generations = $DEFAULT_PEDIGREE_GENERATIONS;
		else $this->num_generations = $_REQUEST["num_generations"];
		
		if ($this->num_generations > $MAX_PEDIGREE_GENERATIONS) {
			$this->num_generations = $MAX_PEDIGREE_GENERATIONS;
			$this->max_generation = true;
		}
		
		if ($this->num_generations < 3) {
			$this->num_generations = 3;
			$this->min_generation = true;
		}
	}

	public function __get($property) {
		switch($property) {
			case "root":
				return $this->GetRootObject();
				break;
			case "show_full":
				return $this->show_full;
				break;
			case "talloffset":
				return $this->talloffset;
				break;
			case "min_generation":
				return $this->min_generation;
				break;
			case "max_generation":
				return $this->max_generation;
				break;
			case "num_generations":
				return $this->num_generations;
				break;
			case "show_full":
				return $this->show_full;
				break;
			default:
				return parent::__get($property);
				break;
		}
	}

	private function GetRootObject() {
		
		if (is_null($this->root)) {
			$this->root =& Person::GetInstance($this->xref, "", $this->gedcomid);
		}
		return $this->root;
	}
	
	protected function GetPageTitle() {
		global $gm_lang, $SHOW_ID_NUMBERS;
		
		if (is_null($this->pagetitle)) {
			$this->pagetitle = $this->GetRootObject()->name;
			if ($SHOW_ID_NUMBERS) $this->pagetitle .= " - ".$this->xref;
			$this->pagetitle .= " - ".$gm_lang["index_header"];
		}
		return $this->pagetitle;
	}
		
}
?>
