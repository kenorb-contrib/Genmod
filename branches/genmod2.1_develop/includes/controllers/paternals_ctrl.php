<?php
/**
 * Controller for the paternal lines Page
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
 * @version $Id: paternals_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
 
class PaternalsController extends ChartController {
	
	public $classname = "PaternalsController";	// Name of this class
	protected $split = null;					// Where to start the single line of ancestors: me (1), parents (2), grandparents (3)
	protected $line = "paternal";				// Type of ancestry for the lines: maternal, paternal, long
		
	private $rootfams = null;					// Array of fams for the lines
	private $pagewidth = null;					// Minimum width of the page in pix
	
	public function __construct() {
		
		parent::__construct();

		if (!isset($_REQUEST["split"]) || empty($_REQUEST["split"])) $this->split = 1;
		else $this->split = $_REQUEST["split"];
		
		if (!isset($_REQUEST["line"]) || empty($_REQUEST["line"])) $this->line = "paternal";
		else $this->line = $_REQUEST["line"];
		
		global $bwidth;
		$this->pagewidth = floor(pow(2, ($this->split-1)) * ($bwidth * 1.1) * ($this->box_width / 100) + 0.5);

		$this->params["split"] = $this->split;
		$this->params["line"] = $this->line;
	}

	public function __get($property) {
		switch($property) {
			case "pagewidth":
				return $this->pagewidth;
				break;
			default:
				return parent::__get($property);
				break;
		}
	}

	protected function GetPageTitle() {
		
		if (is_null($this->pagetitle)) {
			$this->pagetitle = $this->GetRootObject()->name.$this->GetRootObject()->addxref;
			$this->pagetitle .= " - ".GM_LANG_paternal_chart;
		}
		return $this->pagetitle;
	}
	
	
	public function PrintInputLine() {
	
		print "<tr><td class=\"NavBlockLabel\">";
		PrintHelpLink("paternals_help", "qm");
		print GM_LANG_paternal_line_type . "&nbsp;</td>";
	
		print "<td class=\"NavBlockField\">";
		print "<select name=\"line\">";
		print "<option value=\"paternal\"".($this->line == "paternal" ?  " selected=\"selected\"" : "").">".GM_LANG_paternal_paternal."</option>";
		print "<option value=\"maternal\"".($this->line == "maternal" ?  " selected=\"selected\"" : "").">".GM_LANG_paternal_maternal."</option>";
		print "<option value=\"long\"".($this->line == "long" ?  " selected=\"selected\"" : "").">".GM_LANG_paternal_longest."</option>";
		print "</select>";
		print "</td></tr>";
	}
	
	public function PrintInputStart() {
	
		print "<tr><td class=\"NavBlockLabel\">";
		PrintHelpLink("paternals_help", "qm");
		print GM_LANG_paternal_lines_from . "&nbsp;</td>";
	
		print "<td class=\"NavBlockField\">";
		print "<select name=\"split\">";
		print "<option value=\"1\"".($this->split == 1 ?  " selected=\"selected\"" : "").">".GM_LANG_paternal_self."</option>";
		print "<option value=\"2\"".($this->split == 2 ?  " selected=\"selected\"" : "").">".GM_LANG_paternal_parents."</option>";
		print "<option value=\"3\"".($this->split == 3 ?  " selected=\"selected\"" : "").">".GM_LANG_paternal_grandparents."</option>";
		print "</select>";
		print "</td></tr>";
	}

	public function PrintPaternalLines() {
		global $bwidth, $bheight, $TEXT_DIRECTION, $GM_IMAGES;
	
		print "<br style=\"clear:both;\" />";
		// Print the root person
		// Set the width of the containing div to the calculated value.
		// Minimum if full screen width to keep it centered
		print "<div class=\"PaternalsContent\" style=\"width:".$this->pagewidth."px;\">";
			print "<div class=\"PaternalsUpArrow\">";
				$this->PrintFamArrow("u", $this->GetRootObject());
			print "</div>";
			print "<br /><br />";
			PersonFunctions::PrintPedigreePerson($this->GetRootObject(), 1, $this->show_full, $this->boxcount, 1, $this->view, $this->params);
			$this->boxcount++;
			$this->rootfams = array($this->GetRootObject()->primaryfamily);
			for ($i = 2; $i <= $this->split; $i++) {
				$this->rootfams = $this->PrintParents($this->rootfams, $i);
			}
			// Only go on if the persons had ancestral families
			if (count($this->rootfams) > 0) {
				if ($this->line == "long") $this->PrintLongestLines();
				else $this->PrintAncestors();
			}
		print "</div>";
	}

	private function PrintAncestors() {

		$perc = floor(100 / pow(2, $this->split-1)) - 0.1;
		$width = floor($this->pagewidth / pow(2, $this->split-1));
		$found = true;
		while ($found) {
			$found = false;
			$persons = array();
			foreach($this->rootfams as $key => $fam) {
				$family =& Family::GetInstance($fam);
				if ($this->line == "maternal") $person = $family->wife;
				else if ($this->line == "paternal") $person = $family->husb;
				else $person = ""; // safely end this option (should not occur)
				$this->rootfams[$key] = (is_object($person) ? $person->primaryfamily : "");
				if (is_object($person)) $found = true;
				$persons[] = $person;
			}
			if (!$found) break;
			for ($i = 0; $i < count($this->rootfams); $i++) {
				print "<div style=\"width:".$width."px; min-width:".$perc."%; float:left;\" align=\"center\">";
				print (is_object($persons[$i]) || GedcomConfig::$SHOW_EMPTY_BOXES ? $this->PrintVLine() : "&nbsp");
				print "</div>";
			}
			// Break must be here to force single persons on the next line
			print "<br style=\"clear:both;\" />";
			foreach($persons as $key2 => $person) {
				print "<div style=\"width:".$width."px; min-width:".$perc."%; float:left;\">";
				if (is_object($person) || GedcomConfig::$SHOW_EMPTY_BOXES) PersonFunctions::PrintPedigreePerson($person, 1, $this->show_full, $this->boxcount, 1, $this->view, $this->params);
				else print "&nbsp;";
				$this->boxcount++;
				print "</div>";
			}
			print "<br style=\"clear:both;\" />";
		}
	}
	
	private function PrintParents($fams) {
		global $GM_IMAGES;
		
		$cnt = count($fams);
		$width = floor($this->pagewidth / pow(2, $cnt));
		$perc = floor(100 / pow(2, $cnt)) - 0.1;
		$newfams = array();
		$this->PrintLines($cnt, $width, $perc);
		foreach ($fams as $key =>$famid) {
			$family =& Family::GetInstance($famid);
			print "<div style=\"width:".$width."px; min-width:".$perc."%; float:left;\">";
			PersonFunctions::PrintPedigreePerson($family->husb, 1, $this->show_full, $this->boxcount, 1, $this->view, $this->params);
			$this->boxcount++;
			$newfams[] = (is_object($family->husb) ? $family->husb->primaryfamily : "");
			print "</div>";
			print "<div style=\"width:".$width."px; min-width:".$perc."%; float:left;\">";
			PersonFunctions::PrintPedigreePerson($family->wife, 1, $this->show_full, $this->boxcount, 1, $this->view, $this->params);
			$this->boxcount++;
			$newfams[] = (is_object($family->wife) ? $family->wife->primaryfamily : "");
			print "</div>";
		}
		print "<br style=\"clear:both;\" />";
		return $newfams;
	}
	
	private function PrintLines($cnt, $width, $perc) {
		global $GM_IMAGES;
		
		// Print all the lines
		for ($i = 1; $i <= $cnt;$i++) {
			print "<div style=\"width:".($width*2)."px; min-width:".($perc*2)."%; float:left;\" align=\"center\">";
			$this->PrintVLine();
			print "<div style=\"width:49.9%;\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" width=\"100%\" height=\"3\" alt=\"\" /></div>\n";
			print "<div class=\"PaternalsVlineContainer\" align=\"center\">";
			$this->PrintVLine();
			print "</div>";
			print "<div class=\"PaternalsVlineContainer\" align=\"center\">";
			$this->PrintVLine();
			print "</div>";
			print "</div>";
		}
		print "<br style=\"clear:both;\" />";
	}
	
	private function PrintVLine() {
		global $GM_IMAGES;
		
		print "<div class=\"PaternalsVline\" style=\"background: url('".GM_IMAGE_DIR."/".$GM_IMAGES["vline"]["other"]."');\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" /></div>";
	}
	
	private function PrintLongestLines() {

		foreach($this->rootfams as $key => $fam) {
			$personsarray[] = $this->GetLine($fam);
		}
		$perc = floor(100 / pow(2, $this->split-1)) - 0.1;
		$width = floor($this->pagewidth / pow(2, $this->split-1));
		$cols = array();
		foreach($personsarray as $key => $last) {
			// Here we have 1-4 arrays
			foreach($last as $key2 => $persons) {
				// Here we have 1 or more arrays of ancestors. ID key2 is the last one per array
				// We take the first one....
				$cols[$key] = $persons; // use $key: if the first array is empty, the 2nd will appear as first
				break;
			}
		}
		$found = true;
		$cnt = 0;
		while ($found) {
			$found = false;
			for ($i = 0; $i < count($this->rootfams); $i++) {
				if (isset($cols[$i][$cnt])) {
					$found = true;
					break;
				}
			}
			if (!$found) return;
			for ($i = 0; $i < count($this->rootfams); $i++) {
				print "<div style=\"width:".$perc."%; float:left;\" align=\"center\">";
				if (isset($cols[$i][$cnt]) || GedcomConfig::$SHOW_EMPTY_BOXES) $this->PrintVLine();
				print "</div>";
			}
			print "<br style=\"clear:both;\" />";
			for ($i = 0; $i < count($this->rootfams); $i++) {
				print "<div style=\"width:".$perc."%; float:left;\" align=\"center\">";
				if (isset($cols[$i][$cnt])) {
					$person = Person::GetInstance(SplitKey($cols[$i][$cnt], "id"));
				}
				else $person = "";
				PersonFunctions::PrintPedigreePerson($person, 1, $this->show_full, $this->boxcount, 1, $this->view, $this->params);
				$this->boxcount++;
				print "</div>";
			}
			$cnt++;
			print "<br style=\"clear:both;\" />";
		}
	}
	
	private function GetLine($fam) {
		
		$newlines = array();
		$lines = array();
		$family =& Family::GetInstance($fam);
		if ($family->husb_id != "") $newlines[JoinKey($family->husb_id, $family->gedcomid)] = array(JoinKey($family->husb_id, $family->gedcomid));
		if ($family->wife_id != "") $newlines[JoinKey($family->wife_id, $family->gedcomid)] = array(JoinKey($family->wife_id, $family->gedcomid));
		$found = count($newlines);
		while ($found) {
			$found = false;
			$lines = $newlines;
			$newlines = array();
			$tosearch = array();
			foreach($lines as $last => $persons) {
				$tosearch[] = $last;
			}
			if (count($tosearch) == 0) return;
			$string = "('".implode("', '", $tosearch)."')";
			$sql = "SELECT dest.if_pkey as dest_pid, org.if_pkey as org_pid FROM ".TBLPREFIX."individual_family as org INNER JOIN ".TBLPREFIX."individual_family as dest ON org.if_fkey=dest.if_fkey WHERE org.if_pkey IN ".$string." AND org.if_role='C' AND dest.if_role='S' ORDER BY dest.if_prim ASC, dest.if_pedi DESC";
			$res = NewQuery($sql);
			while ($row = $res->FetchAssoc()) {
				$found = true;
				$newlines[$row["dest_pid"]] = $lines[$row["org_pid"]];
				$newlines[$row["dest_pid"]][] = $row["dest_pid"];
			}
		}
		return $lines;
	}
}
?>