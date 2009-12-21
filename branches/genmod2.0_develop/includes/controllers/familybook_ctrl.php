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
 * @version $Id$
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
 
class FamilyBookController extends ChartController {
	
	public $classname = "FamilyBookController";	// Name of this class
	private $num_descent = null;				// Number of time to repeat the family book for a descendant generation (depth)
	private $show_spouse = null;				// Show spouses with persons
	private $dgenerations = null;				// Calculated maximum generations
	private $boxcount = 0;						// To keep track of the number of printed boxes
	
	public function __construct() {
		
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
		
		if (!isset($_REQUEST["show_spouse"])) $this->show_spouse = 0;
		else $this->show_spouse = $_REQUEST["show_spouse"];
		if ($this->show_spouse == "") $this->show_spouse = 0;
		
		if (!isset($_REQUEST["num_descent"]) || $_REQUEST["num_descent"] == "") $this->num_descent = 2;
		else $this->num_descent = $_REQUEST["num_descent"];
		
	}

	public function __get($property) {
		switch($property) {
			case "num_descent":
				return $this->num_descent;
				break;
			case "show_spouse":
				return $this->show_spouse;
				break;
			default:
				return parent::__get($property);
				break;
		}
	}

	protected function GetPageTitle() {
		
		if (is_null($this->pagetitle)) {
			$this->pagetitle = $this->GetRootObject()->name;
			if (GedcomConfig::$SHOW_ID_NUMBERS) $this->pagetitle .= " - ".$this->xref;
			$this->pagetitle .= " - ".GM_LANG_familybook_chart;
		}
		return $this->pagetitle;
	}
	
	
	public function PrintInputDescentSteps() {
	
		print "<tr><td class=\"shade2\" >";
		print_help_link("desc_descent_help", "qm");
		print GM_LANG_descent_steps."&nbsp;</td>";
		print "<td class=\"shade1 vmiddle\">";
		print "<input class=\"pedigree_form\" type=\"text\" size=\"3\" name=\"num_descent\" value=\"".$this->num_descent."\" />";
		print "</td></tr>";
	}
	
	public function PrintInputShowSpouse() {
		
		print "<tr><td class=\"shade2\" >";
		print_help_link("show_spouse_help", "qm");
		print GM_LANG_show_spouses."&nbsp;</td>";
		print "<td class=\"shade1 vmiddle\">";
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
	        
	        print "\n\t<h3 style=\"text-align: center\">"."Family of".": ".PrintReady($person->name)."</h3>";
	        print "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"vertical-align:middle;\"><tr>\n";
	        
	        //-- descendancy
	        print "<td style=\"vertical-align:middle;\">\n";
	        $this->dgenerations = $this->num_generations;
	        $this->dgenerations = $this->MaxDescendancyGenerations($person, 0);
	       	$this->PrintDescendancy($person, 0);
	        print "</td>\n";
	        //-- pedigree
	        print "<td style=\"vertical-align:middle;\">\n";
	        $this->PrintPersonPedigree($person, 0);
	        print "</td>\n";
	        print "</tr></table>\n";
	        print "<br /><br />\n";
	        print "<hr />\n";
	        print "<br /><br />\n";
	        
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
		print "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"vertical-align:middle;\">\n";
		print "<tr>";
		print "<td width=\"".$bwidth."\" style=\"vertical-align:middle;\">\n";
		$numkids = 0;
		$firstkids = 0;
		// Table level 2-0
		foreach($person->spousefamilies as $indexval => $fam) {
			// Table level 2-1
			if ($fam->children_count > 0) {
				print "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"vertical-align:middle; empty-cells: show;\">\n";
				$i = 0;
				foreach($fam->children as $key =>$child) {
					$rowspan = 2;
					if (($i > 0) && ($i < $fam->children_count - 1)) $rowspan=1;
					print "<tr><td rowspan=\"".$rowspan."\" width=\"".$bwidth."\" style=\"vertical-align:middle; padding-top: 2px;\">\n";
					if ($count+1 < $this->dgenerations) {
						$kids = $this->PrintDescendancy($child, $count+1);
						if ($i == 0) $firstkids = $kids;
						$numkids += $kids;
					}
					else {
						PersonFunctions::PrintPedigreePerson($child, 1, true, $this->boxcount);
						$this->boxcount++;
						$numkids++;
					}
					print "</td>\n";
					$twidth = 6;
					if ($fam->children_count == 1) $twidth += 6; // adjusted here
					print "<td rowspan=\"".$rowspan."\" style=\"vertical-align:middle;\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" width=\"".$twidth."\" height=\"3\" alt=\"\" /></td>\n";
					if ($fam->children_count > 1) {
						// First
						if ($i == 0) {
							print "<td height=\"50%\" style=\"vertical-align:middle;\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" /></td></tr>\n";
							print "<tr><td height=\"50%\" style=\"vertical-align:middle; background: url('".GM_IMAGE_DIR."/".$GM_IMAGES["vline"]["other"]."');\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" /></td>\n";
						}
						// Last
						else if ($i == $fam->children_count - 1) {
							print "<td height=\"50%\" style=\"vertical-align:middle; background: url('".GM_IMAGE_DIR."/".$GM_IMAGES["vline"]["other"]."');\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" /></td></tr>\n";
							print "<tr><td height=\"50%\" style=\"vertical-align:middle;\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" /></td>\n";
						}
						// Other cases
						else {
							print "<td style=\"background: url('".GM_IMAGE_DIR."/".$GM_IMAGES["vline"]["other"]."'); vertical-align:middle;\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" /></td>\n";
						}
					}
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
		PersonFunctions::PrintPedigreePerson($person, 1, true, $this->boxcount);
		$this->boxcount++;
		// NOTE: If statement OK
		if ($this->show_spouse) {
			foreach($person->spousefamilies as $indexval => $fam) {
				if (is_object($fam->marr_fact)) {
//					print "<br />";
					if ($fam->disp && $fam->marr_date != "") print "<span class=\"date\">".GetFirstLetter($fam->marr_fact->descr).": ".$fam->marr_fact->datestring."</span>";
//					FactFunctions::PrintSimpleFact($fam->marr_fact, false, false); 
				}
				if ($fam->husb_id != $person->xref) PersonFunctions::PrintPedigreePerson($fam->husb, 1, true, $this->boxcount);
				else PersonFunctions::PrintPedigreePerson($fam->wife, 1, true, $this->boxcount);
				$this->boxcount++;
			}
		}
		// NOTE: If statement OK
		if ($count == 0) {
			// NOTE: If statement OK
			if ($person->disp_name) {
				// -- print left arrow for decendants so that we can move down the tree
				//-- make sure there is more than 1 child in the family with parents
				$num=0;
				// NOTE: For statement OK
				foreach($person->childfamilies as $key => $fam) {
					$num += $fam->children_count;
				}
				// NOTE: If statement OK
				if (count($person->spousefamilies) > 0 || $num > 1) {
					// Open div childarrow_boxcount
					print "\n\t\t<div id=\"childarrow".$this->boxcount."\" dir=\"";
					if ($TEXT_DIRECTION=="rtl") print "rtl\" style=\"position:absolute; ";
					else print "ltr\" style=\"position:absolute; ";
					print "width:10px; height:10px;\">";
					if ($this->view != "preview") {
						print "<a href=\"javascript: ".GM_LANG_show."\" onclick=\"return togglechildrenbox(".$this->boxcount.");\" onmouseover=\"swap_image('larrow".$this->boxcount."',3);\" onmouseout=\"swap_image('larrow".$this->boxcount."',3);\">";
						print "<img id=\"larrow".$this->boxcount."\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["darrow"]["other"]."\" border=\"0\" alt=\"\" />";
						print "</a>";
					}
					// Open div childbox_boxcount
					print "\n\t\t<div id=\"childbox".$this->boxcount."\" dir=\"";
					if ($TEXT_DIRECTION=="rtl") print "rtl\" style=\"position:absolute; right: 20px; ";
					else print "ltr\" style=\"position:absolute; left: 20px;";
					print " width:".$bwidth."px; height:".$bheight."px; visibility: hidden;\">";
					// Table level 2-2
					print "\n\t\t\t<table class=\"person_box\"><tr><td>";
					foreach($person->spousefamilies as $key => $sfamily) {
						if($person->xref != $sfamily->husb_id) $spouse = $sfamily->husb;
						else $spouse = $sfamily->wife;
						if (is_object($spouse)) {
							print "\n\t\t\t\t<a href=\"familybook.php?rootid=".$spouse->xref."&amp;show_spouse=".$this->show_spouse."&amp;show_details=".$this->show_details."&amp;num_generations=".$this->num_generations."&amp;box_width=".$this->box_width."&amp;num_descent=".$this->num_descent."\"><span ";
							if ($spouse->disp_name) {
								if (hasRTLText($spouse->name))
								     print "class=\"name2\">";
				   				else print "class=\"name1\">";
								print $spouse->name;
							}
							else print GM_LANG_private;
							print "<br /></span></a>";
						}
						foreach($sfamily->children as $ckey => $child) {
							print "\n\t\t\t\t&nbsp;&nbsp;<a href=\"familybook.php?rootid=".$child->xref."&amp;show_spouse=".$this->show_spouse."&amp;show_details=".$this->show_details."&amp;num_generations=".$this->num_generations."&amp;box_width=".$this->box_width."&amp;num_descent=".$this->num_descent."\"><span ";
							if ($child->disp_name) {
								if (hasRTLText($child->name))
								     print "class=\"name2\">&lt; ";
					   			else print "class=\"name1\">&lt; ";
								print $child->name;
							}
							else print ">" . GM_LANG_private;
							print "<br /></span></a>";
						}
					}
					//-- print the siblings
					foreach($person->childfamilies as $key => $cfamily) {
						if($cfamily->husb_id != "" || $cfamily->wife_id != "") {
							print "<span class=\"name1\"><br />".GM_LANG_parents."<br /></span>";
							if ($cfamily->husb_id != "") {
								print "\n\t\t\t\t&nbsp;&nbsp;<a href=\"familybook.php?rootid=".$cfamily->husb_id."&amp;show_spouse=".$this->show_spouse."&amp;show_details=".$this->show_details."&amp;num_generations=".$this->num_generations."&amp;box_width=".$this->box_width."&amp;num_descent=".$this->num_descent."\"><span ";
								if ($cfamily->husb->disp_name) {
									if (hasRTLText($cfamily->husb->name))
									     print "class=\"name2\">";
					   				else print "class=\"name1\">";
									print $cfamily->husb->name;
								}
								else print GM_LANG_private;
								print "<br /></span></a>";
							}
							if ($cfamily->wife_id != "") {
								print "\n\t\t\t\t&nbsp;&nbsp;<a href=\"familybook.php?rootid=".$cfamily->wife_id."&amp;show_spouse=".$this->show_spouse."&amp;show_details=".$this->show_details."&amp;num_generations=".$this->num_generations."&amp;box_width=".$this->box_width."&amp;num_descent=".$this->num_descent."\"><span ";
								if ($cfamily->wife->disp_name) {
									if (hasRTLText($cfamily->wife->name))
									     print "class=\"name2\">";
					   				else print "class=\"name1\">";
									print $cfamily->wife->name;
								}
								else print GM_LANG_private;
								print "<br /></span></a>";
							}
						}
						if ($cfamily->children_count > 1) {
							print "<span class=\"name1\"><br />".GM_LANG_siblings."<br /></span>";
							foreach($cfamily->children as $key2 => $child) {
								if ($child->xref != $person->xref) {
									print "\n\t\t\t\t&nbsp;&nbsp;<a href=\"familybook.php?rootid=".$child->xref."&amp;show_spouse=".$this->show_spouse."&amp;show_details=".$this->show_details."&amp;num_generations=".$this->num_generations."&amp;box_width=".$this->box_width."&amp;num_descent=".$this->num_descent."\"><span ";
									if ($child->disp_name) {
										if (hasRTLText($child->name))
										print "class=\"name2\"> ";
						   				else print "class=\"name1\"> ";
										print $child->name;
									}
									else print ">". GM_LANG_private;
									print "<br /></span></a>";
								}
							}
						}
					}
					// Close table level 2-2
					print "\n\t\t\t</td></tr></table>";
					// Close div childbox_boxcount
					print "\n\t\t</div>";
					// Close div childarrow_boxcount
					print "\n\t\t</div>";
				}
			}
		}
		print "</td></tr>\n";
		// Close table level 1
		print "</table>\n";
		return $numkids;
	}
	
	public function PrintPersonPedigree($person, $count) {
		global $GM_IMAGES, $bheight;
		
		if ($count >= $this->num_generations || !is_object($person)) return;
		
		foreach ($person->childfamilies as $key => $family) {
			print "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"empty-cells: show; vertical-align:middle;\">\n";
			$height="100%";
			print "<tr>";
			if ($count < $this->num_generations - 1) print "<td height=\"50%\" style=\"vertical-align:middle;\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" /></td>\n";
			if ($count < $this->num_generations - 1) print "<td rowspan=\"2\" style=\"vertical-align:middle;\" ><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" width=\"7\" height=\"3\" alt=\"\" /></td>\n";
			print "<td rowspan=\"2\" style=\"vertical-align:middle;\">\n";
			PersonFunctions::PrintPedigreePerson($family->husb, 1, true, $this->boxcount);
			$this->boxcount++;
			print "</td>\n";
			print "<td rowspan=\"2\" style=\"vertical-align:middle;\">\n";
			$this->PrintPersonPedigree($family->husb, $count+1);
			print "</td>\n";
			print "</tr>\n<tr>\n<td height=\"50%\"";
			if ($count < $this->num_generations - 1) print " style=\"vertical-align:middle; background: url('".GM_IMAGE_DIR."/".$GM_IMAGES["vline"]["other"]."');\" ";
			print "><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" /></td></tr>\n<tr>\n";
			if ($count < $this->num_generations - 1) print "<td height=\"50%\" style=\"vertical-align:middle; background: url('".GM_IMAGE_DIR."/".$GM_IMAGES["vline"]["other"]."'); \"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" /></td>";
			if ($count < $this->num_generations - 1) print "<td rowspan=\"2\" style=\"vertical-align:middle;\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" width=\"7\" height=\"3\" alt=\"\" /></td>\n";
			print "<td rowspan=\"2\" style=\"vertical-align:middle;\">\n";
			PersonFunctions::PrintPedigreePerson($family->wife, 1, true, $this->boxcount);
			$this->boxcount++;
			print "</td>\n";
			print "<td rowspan=\"2\" style=\"vertical-align:middle;\">\n";
			$this->PrintPersonPedigree($family->wife, $count+1);
			print "</td>\n";
			print "</tr>\n";
			if ($count < $this->num_generations - 1) print "<tr>\n<td height=\"50%\" style=\"vertical-align:middle;\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["spacer"]["other"]."\" width=\"3\" alt=\"\" /></td></tr>\n";
			print "</table>\n";
		}
	}
	
}
?>