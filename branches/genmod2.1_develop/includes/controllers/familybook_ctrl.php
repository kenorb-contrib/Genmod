<?php
/**
 * Controller for the Familybook Page
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
 * @version $Id: familybook_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
 
class FamilyBookController extends ChartController {
	
	public $classname = "FamilyBookController";	// Name of this class
	protected $num_descent = null;				// Number of time to repeat the family book for a descendant generation (depth)
	private $dgenerations = null;				// Calculated maximum generations
	private $page = null;						// Page this controller is called from
	
	public function __construct() {
		
		$this->page = substr(SCRIPT_NAME, 1);
		
		parent::__construct();

		if (!isset($_REQUEST["num_generations"]) || $_REQUEST["num_generations"] == "") $this->num_generations = GedcomConfig::$DEFAULT_PEDIGREE_GENERATIONS;
		else $this->num_generations = $_REQUEST["num_generations"];
		
		if ($this->num_generations > GedcomConfig::$MAX_PEDIGREE_GENERATIONS) {
			$this->num_generations = GedcomConfig::$MAX_PEDIGREE_GENERATIONS;
			$this->max_generation = true;
		}
		
		if ($this->num_generations < 2) {
			$this->num_generations = 2;
			$this->min_generation = true;
		}
		
		if ($this->page == "familybook.php") {
			if (!isset($_REQUEST["num_descent"]) || $_REQUEST["num_descent"] == "") $this->num_descent = 2;
			else $this->num_descent = $_REQUEST["num_descent"];
		}
		else $this->num_descent = 1;
		
		$this->params["num_descent"] = $this->num_descent;
	}

	public function __get($property) {
		switch($property) {
			case "num_descent":
				return $this->num_descent;
				break;
			default:
				return parent::__get($property);
				break;
		}
	}

	protected function GetPageTitle() {
		
		if (is_null($this->pagetitle)) {
			$this->pagetitle = $this->GetRootObject()->name.$this->GetRootObject()->addxref;
			if ($this->page == "familybook.php") $this->pagetitle .= " - ".GM_LANG_familybook_chart;
			else if ($this->page == "hourglass.php") $this->pagetitle .= " - ".GM_LANG_hourglass_chart;
		}
		return $this->pagetitle;
	}
	
	
	public function PrintInputDescentSteps() {
	
		if ($this->page == "familybook.php") {
			print "<tr><td class=\"NavBlockLabel\" >";
			PrintHelpLink("desc_descent_help", "qm");
			print GM_LANG_descent_steps."&nbsp;</td>";
			print "<td class=\"NavBlockField\">";
			print "<input class=\"PidInputField\" type=\"text\" size=\"3\" name=\"num_descent\" value=\"".$this->num_descent."\" />";
			print "</td></tr>";
		}
	}
	
	public function PrintInputShowSpouse() {
		
		print "<tr><td class=\"NavBlockLabel\" >";
		PrintHelpLink("show_spouse_help", "qm");
		print GM_LANG_show_spouses."&nbsp;</td>";
		print "<td class=\"NavBlockField\">";
		print "<input type=\"checkbox\" value=\"1\" name=\"show_spouse\"";
		if ($this->show_spouse) print " checked=\"checked\"";
		print " />";
		print "</td></tr>";
	}
	
	public function PrintFamilyBook($person, $descent) {
	    global $firstrun;
	    
		if ($descent == 0) return;
		
		if (count($person->spousefamilies) > 0 || empty($firstrun)) {
			$firstrun = true;
			
		if ($this->page == "familybook.php") print "\n\t<div class=\"FamilyBookFamilyHeader\">".GM_LANG_family_of.": ".PrintReady($person->name)."</div>";
			print "<table class=\"FamilyBookContentTable\">";
				print "<tr>\n";
	        
					//-- descendancy
					print "<td>\n";
						$this->dgenerations = $this->num_generations;
						$this->dgenerations = $this->MaxDescendancyGenerations($person, 0);
						$this->PrintDescendancy($person, 0);
					print "</td>\n";
					//-- pedigree
					print "<td>\n";
						$this->PrintPersonPedigree($person, 0);
					print "</td>\n";
				print "</tr>";
			print "</table>\n";
			if ($this->page == "familybook.php") print "<hr class=\"FamilyBookFamilyDivider\" />\n";

			foreach($person->spousefamilies as $indexval => $fam) {
				foreach($fam->children as $key => $child) {
					if ($child->disp) $this->PrintFamilyBook($child, $descent-1);
				}
			}
		}
	}
	
	public function MaxDescendancyGenerations($person, $depth) {
		
		if ($depth >= $this->num_generations) return $depth;
		$maxdc = $depth;
		foreach($person->spousefamilies as $indexval => $fam) {
			foreach($fam->children as $key => $child) {
				$dc = $this->MaxDescendancyGenerations($child, $depth+1);
				if ($dc >= $this->num_generations) return $dc;
				if ($dc > $maxdc) $maxdc = $dc;
			}
		}
		if ($maxdc==0) $maxdc++;
		return $maxdc;
	}
	
	public function PrintDescendancy($person, $count) {
		global $bwidth, $bheight, $TEXT_DIRECTION, $GM_IMAGES;

		if ($count >= $this->dgenerations) return 0;
		// Table level 1
		print "<table class=\"FamilyBookContentTable\">\n";
			print "<tr>";
				print "<td width=\"".$bwidth."\">\n";
				$numkids = 0;
				$firstkids = 0;
				// Table level 2-0
				foreach($person->spousefamilies as $indexval => $fam) {
					// Table level 2-1
					if ($fam->children_count > 0) {
						print "<table class=\"FamilyBookContentTableShowEmpty\">\n";
						$i = 0;
						foreach($fam->children as $key =>$child) {
							$rowspan = 2;
							if (($i > 0) && ($i < $fam->children_count - 1)) $rowspan=1;
							print "<tr>";
								print "<td rowspan=\"".$rowspan."\" width=\"".$bwidth."\" style=\"padding-top: 2px;\">\n";
									if ($count+1 < $this->dgenerations) {
										$kids = $this->PrintDescendancy($child, $count+1);
										if ($i == 0) $firstkids = $kids;
										$numkids += $kids;
									}
									else {
										PersonFunctions::PrintPedigreePerson($child, 1, $this->show_full, $this->boxcount, 1, $this->view, $this->params);
										$this->boxcount++;
										$numkids++;
									}
								print "</td>\n";
								$twidth = 6;
								if ($fam->children_count == 1) $twidth += 6; // adjusted here
								print "<td rowspan=\"".$rowspan."\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" width=\"".$twidth."\" height=\"3\" alt=\"\" /></td>\n";
									if ($fam->children_count > 1) {
										// First
										if ($i == 0) {
											print "<td height=\"50%\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" /></td></tr>\n";
											print "<tr><td height=\"50%\" style=\"background: url('".GM_IMAGE_DIR."/".$GM_IMAGES["vline"]["other"]."');\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" />\n";
										}
										// Last
										else if ($i == $fam->children_count - 1) {
											print "<td height=\"50%\" style=\"background: url('".GM_IMAGE_DIR."/".$GM_IMAGES["vline"]["other"]."');\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" /></td></tr>\n";
											print "<tr><td height=\"50%\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" />\n";
										}
										// Other cases
										else {
											print "<td style=\"background: url('".GM_IMAGE_DIR."/".$GM_IMAGES["vline"]["other"]."');\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" />\n";
										}
									}
								print "</td>";
							print "</tr>\n";
							$i++;
						}
						// Close Table level 2-1
						print "</table>\n";
					}
				}
				print "</td>\n";
				print "<td width=\"".$bwidth."\" style=\"vertical-align:middle;\">\n";

				// NOTE: If statement OK
				if ($numkids == 0) {
					$numkids = 1;
					$tbwidth = $bwidth + 22; // adjusted here
					if ($count+1 < $this->dgenerations) $tbwidth += 3;
					for($j = $count; $j < $this->dgenerations; $j++) {
						print "<div style=\"width: ".($tbwidth)."px;\">&nbsp;<br /></div>\n</td>\n<td width=\"".$bwidth."\" style=\"vertical-align:middle;\">\n";
						$tbwidth += 3;
					}
				}
				//-- add offset divs to make things line up better. Should be fixed later asd this takes a lot of unnecessary space
				if ($this->show_spouse) {
					foreach($person->spousefamilies as $indexval => $fam) {
						if (is_object($fam->marr_fact)) {
							print "<br />";
						}
						print "<div style=\"height: ".$bheight."px; width: ".$bwidth."px;\"><br /></div>\n";
					}
				}
				PersonFunctions::PrintPedigreePerson($person, 1, $this->show_full, $this->boxcount, 1, $this->view, $this->params);
				$this->boxcount++;
				// NOTE: If statement OK
				if ($this->show_spouse) {
					foreach($person->spousefamilies as $indexval => $fam) {
						if (is_object($fam->marr_fact)) {
							if ($fam->disp && $fam->marr_date != "") print "<span class=\"Date\">".NameFunctions::GetFirstLetter($fam->marr_fact->descr).": ".GetChangedDate($fam->marr_fact->datestring)."</span>";
						}
						if ($fam->husb_id != $person->xref) PersonFunctions::PrintPedigreePerson($fam->husb, 1, $this->show_full, $this->boxcount, 1, $this->view, $this->params);
						else PersonFunctions::PrintPedigreePerson($fam->wife, 1, $this->show_full, $this->boxcount, 1, $this->view, $this->params);
						$this->boxcount++;
					}
				}
				// NOTE: If statement OK
				if ($count == 0) {
					// NOTE: If statement OK
					$this->PrintFamArrow("d", $person);
				}
				print "</td>";
			print "</tr>\n";
		// Close table level 1
		print "</table>\n";
		return $numkids;
	}
	
	public function PrintPersonPedigree($person, $count) {
		global $GM_IMAGES, $bheight;
		
		if ($count >= $this->num_generations || !is_object($person)) return;
		
		foreach ($person->childfamilies as $key => $family) {
			print "<table class=\"FamilyBookContentTableShowEmpty\">\n";
				$height="100%";
				print "<tr>";
					if ($count < $this->num_generations - 1) print "<td height=\"50%\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" /></td>\n";
					if ($count < $this->num_generations - 1) print "<td rowspan=\"2\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" width=\"7\" height=\"3\" alt=\"\" /></td>\n";
					print "<td rowspan=\"2\">\n";
						PersonFunctions::PrintPedigreePerson($family->husb, 1, $this->show_full, $this->boxcount, 1, $this->view, $this->params);
						$this->boxcount++;
					print "</td>\n";
					print "<td rowspan=\"2\">\n";
						$this->PrintPersonPedigree($family->husb, $count+1);
					print "</td>\n";
				print "</tr>\n";
				print "<tr>\n";
					print "<td height=\"50%\"";
				if ($count < $this->num_generations - 1) print " style=\"background: url('".GM_IMAGE_DIR."/".$GM_IMAGES["vline"]["other"]."');\" ";
				print "><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" /></td></tr>\n<tr>\n";
				if ($count < $this->num_generations - 1) print "<td height=\"50%\" style=\"background: url('".GM_IMAGE_DIR."/".$GM_IMAGES["vline"]["other"]."'); \"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" /></td>";
				if ($count < $this->num_generations - 1) print "<td rowspan=\"2\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" width=\"7\" height=\"3\" alt=\"\" /></td>\n";
				print "<td rowspan=\"2\">\n";
					PersonFunctions::PrintPedigreePerson($family->wife, 1, $this->show_full, $this->boxcount, 1, $this->view, $this->params);
					$this->boxcount++;
					print "</td>\n";
				print "<td rowspan=\"2\">\n";
					$this->PrintPersonPedigree($family->wife, $count+1);
				print "</td>\n";
			print "</tr>\n";
			if ($count < $this->num_generations - 1) print "<tr>\n<td height=\"50%\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" /></td></tr>\n";
			print "</table>\n";
		}
	}
}
?>