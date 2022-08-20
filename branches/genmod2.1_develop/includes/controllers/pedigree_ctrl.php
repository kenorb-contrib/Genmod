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
 * @version $Id: pedigree_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
 
class PedigreeController extends ChartController {
	
	public $classname = "PedigreeController";	// Name of this class
	private $talloffset = null;					// Orientation of chart

	
	public function __construct() {
		
		parent::__construct();
		if ($this->action = "") $this->action = "find";
		
		if (isset($_REQUEST["talloffset"])) $this->talloffset = $_REQUEST["talloffset"];
		else $this->talloffset = GedcomConfig::$PEDIGREE_LAYOUT;
		if ($this->talloffset == "") $this->talloffset = 0;
		
		if (!isset($_REQUEST["num_generations"]) || $_REQUEST["num_generations"] == "") $this->num_generations = GedcomConfig::$DEFAULT_PEDIGREE_GENERATIONS;
		else $this->num_generations = $_REQUEST["num_generations"];
		
		if ($this->num_generations > GedcomConfig::$MAX_PEDIGREE_GENERATIONS) {
			$this->num_generations = GedcomConfig::$MAX_PEDIGREE_GENERATIONS;
			$this->max_generation = true;
		}
		
		if ($this->num_generations < 3) {
			$this->num_generations = 3;
			$this->min_generation = true;
		}
		
		$this->params["talloffset"] = $this->talloffset;
	}

	public function __get($property) {
		switch($property) {
			case "talloffset":
				return $this->talloffset;
				break;
			default:
				return parent::__get($property);
				break;
		}
	}

	
	protected function GetPageTitle() {
		
		if (is_null($this->pagetitle)) {
			$this->pagetitle = $this->GetRootObject()->name.$this->GetRootObject()->addxref;
			$this->pagetitle .= " - ".GM_LANG_index_header;
		}
		return $this->pagetitle;
	}
	
	public function AdjustSubtree($index, $diff) {
		global $offsetarray, $treeid, $boxspacing, $mdiff;
		
		$f = ($index*2)+1; //-- father index
		$m = $f+1; //-- mother index
	
		if (!GedcomConfig::$SHOW_EMPTY_BOXES && empty($treeid[$index])) return;
		if (empty($offsetarray[$index])) return;
		$offsetarray[$index]["y"] += $diff;
		if ($f<count($treeid)) $this->AdjustSubtree($f, $diff);
		if ($m<count($treeid)) $this->AdjustSubtree($m, $diff);
	}
	
	public function CollapseTree($index, $curgen, $diff) {
		global $offsetarray, $treeid, $boxspacing, $mdiff, $minyoffset;
	
		//print "$index:$curgen:$diff<br />\n";
		$f = ($index*2)+1; //-- father index
		$m = $f+1; //-- mother index
		if (empty($treeid[$index])) {
			$pgen=$curgen;
			$genoffset=0;
			while($pgen <= $this->num_generations) {
				$genoffset += pow(2, ($this->num_generations-$pgen));
				$pgen++;
			}
			if ($this->talloffset==1) $diff+=.5*$genoffset;
			else $diff+=$genoffset;
			if (isset($offsetarray[$index]["y"])) $offsetarray[$index]["y"]-=($boxspacing*$diff)/2;
			return $diff;
		}
		if ($curgen == $this->num_generations) {
			$offsetarray[$index]["y"] -= $boxspacing*$diff;
			//print "UP $index BY $diff<br />\n";
			return $diff;
		}
		$odiff=$diff;
		$fdiff = $this->CollapseTree($f, $curgen+1, $diff);
		if (($curgen < ($this->num_generations-1)) || ($index%2 == 1)) $diff=$fdiff;
		if (isset($offsetarray[$index]["y"])) $offsetarray[$index]["y"] -= $boxspacing*$diff;
		//print "UP $index BY $diff<br />\n";
		$mdiff = $this->CollapseTree($m, $curgen+1, $diff);
		$zdiff = $mdiff - $fdiff;
		if ($zdiff > 0 && $curgen < ($this->num_generations - 2)) {
			$offsetarray[$index]["y"] -= $boxspacing*$zdiff/2;
			//print "UP $index BY ".($zdiff/2)."<br />\n";
			if ((empty($treeid[$m]))&&(!empty($treeid[$f]))) $this->AdjustSubtree($f, -1*($boxspacing*$zdiff/4));
			$diff+=($zdiff/2);
		}
		return $diff;
	}
}
?>