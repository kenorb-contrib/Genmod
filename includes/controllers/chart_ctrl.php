<?php
/**
 * Controller for the Chart Pages
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
 * @version $Id: chart_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
 
/**
 * Main controller class for the individual page.
 */
abstract class ChartController extends BaseController {
	
	public $classname = "ChartController";	// Name of this class
	protected $root = null;					// Holder for the root person object
	protected $show_full = null;			// Display details in chart boxes
	protected $show_details = null;			// Display details in chart boxes (input variable)
	protected $show_cousins = null;			// Show cousins in ancestry and descendancy charts
	protected $show_spouse = null;			// Show spouses with persons
	protected $box_width = null;			// Width of the person boxes
	protected $chart_style = null;			// Type of chart: booklet = 0, list = 1
	protected $min_generation = null;		// Generate an error that a minimum number of generations must be displayed
	protected $max_generation = null;		// Generate an error that no more than a maximum number of generations can be displayed
	protected $num_generations = null;		// Number of generations to display
	protected $boxcount = 0;				// To keep track of the number of printed boxes
	protected $params = null;				// Page parameters to send to various functions to construct pagelinks
	
	
	public function __construct() {
		
		parent::__construct();
		
		if (isset($_REQUEST["rootid"])) $this->xref = $_REQUEST["rootid"];
		$this->xref = ChartFunctions::CheckRootId(CleanInput($this->xref));
		
		if (isset($_REQUEST["show_details"])) {
			$this->show_details = $_REQUEST["show_details"];
			$this->show_full = ($this->show_details == 1 ? 1 : 0);
		}
		else {
			$this->show_details = (GedcomConfig::$PEDIGREE_FULL_DETAILS == 1 ? 1 : -1);
			$this->show_full = GedcomConfig::$PEDIGREE_FULL_DETAILS;
		}
		
		global $show_full;
		$show_full = $this->show_full;
		
		if (!isset($_REQUEST["chart_style"])) $this->chart_style = 0;
		else $this->chart_style = ($_REQUEST["chart_style"] == "" ? 0 : $_REQUEST["chart_style"]);
		
		if (!isset($_REQUEST["show_cousins"])) $this->show_cousins = 0;
		else $this->show_cousins = $_REQUEST["show_cousins"];
		if ($this->show_cousins == "") $this->show_cousins = 0;
		
		global $show_cousins;
		$show_cousins = $this->show_cousins;

		if (!isset($_REQUEST["show_spouse"])) $this->show_spouse = 0;
		else $this->show_spouse = $_REQUEST["show_spouse"];
		if ($this->show_spouse == "") $this->show_spouse = 0;
		
		if (!isset($_REQUEST["box_width"]) || (int)$_REQUEST["box_width"] == "") $this->box_width = "100";
		else $this->box_width = (int)$_REQUEST["box_width"];
		$this->box_width = max($this->box_width, 50);
		$this->box_width = min($this->box_width, 300);
		
		global $box_width;
		$box_width = $this->box_width;
		
		$this->params = array();
		$this->params["rootid"] = $this->xref;
		$this->params["num_generations"] = $this->num_generations;
		$this->params["box_width"] = $this->box_width;
		$this->params["show_details"] = $this->show_details;
		$this->params["chart_style"] = $this->chart_style;
		$this->params["show_spouse"] = $this->show_spouse;
		$this->params["show_cousins"] = $this->show_cousins;
		
	}

	public function __get($property) {
		switch($property) {
			case "root":
				return $this->GetRootObject();
				break;
			case "show_full":
				return $this->show_full;
				break;
			case "box_width":
				return $this->box_width;
				break;
			case "chart_style":
				return $this->chart_style;
				break;
			case "show_cousins":
				return $this->show_cousins;
				break;
			case "show_spouse":
				return $this->show_spouse;
				break;
			case "show_details":
				return $this->show_details;
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
			case "params":
				return $this->params;
				break;
			default:
				return parent::__get($property);
				break;
		}
	}
	
	protected function GetRootObject() {
		
		if (is_null($this->root)) {
			$this->root =& Person::GetInstance($this->xref, "", $this->gedcomid);
		}
		return $this->root;
	}
	
	public function PrintInputRootId() {
		
		print "<tr><td class=\"NavBlockLabel\">";
		PrintHelpLink("rootid_help", "qm");
		print GM_LANG_root_person."</td>";
		print "<td class=\"NavBlockField\">";
		print "<input class=\"PidInputField\" type=\"text\" name=\"rootid\" id=\"rootid\" size=\"3\" value=\"".$this->xref."\" />";
		LinkFunctions::PrintFindIndiLink("rootid","");
		print "</td></tr>";
	}
	
	public function PrintInputBoxWidth($tr=true) {

		if ($tr) print "<tr>";
		print "<td class=\"NavBlockLabel\">";
		PrintHelpLink((SCRIPT_NAME == "/fanchart.php" ? "fan_width_help" : "box_width_help"), "qm");
		print (SCRIPT_NAME == "/fanchart.php" ? GM_LANG_fan_width : GM_LANG_box_width) . "&nbsp;</td>";
		print "<td class=\"NavBlockField\">";
		print "<input type=\"text\" size=\"3\" id=\"box_width\" name=\"box_width\" value=\"".$this->box_width."\" />&nbsp;<b>%</b>";
		print "<a href=\"javascript:\" onclick=\"return SetBoxSize(-10);\">&nbsp;&nbsp;-&nbsp;/</a>";
		print "<a href=\"javascript:\" onclick=\"return SetBoxSize(10);\">&nbsp;+&nbsp;</a>";
		print "</td>";
		if ($tr) print "</tr>";
	}

	public function PrintInputHeader($tr=true) {
		
		if ($tr) print "<tr>";
		print "<td colspan=\"2\" class=\"NavBlockHeader\">";
		print GM_LANG_options."</td>";
		if ($tr) print "</tr>";
	}
	
	public function PrintInputGenerations($gens, $help) {	
		
		print "<tr><td class=\"NavBlockLabel\">";
		PrintHelpLink($help, "qm");
		print GM_LANG_generations . "&nbsp;</td>";
	
		print "<td class=\"NavBlockField\">";
		print "<select name=\"num_generations\">";
		for ($i=2; $i <= $gens; $i++) {
			print "<option value=\"".$i."\"" ;
			if ($i == $this->num_generations) print " selected=\"selected\"";
			print ">".$i."</option>";
		}
		print "</select>";
		print "</td></tr>";
	}
	
	public function PrintInputSubmit($tr=true) {
		
		if ($tr) print "<tr>";
		print "<td class=\"NavBlockFooter\" colspan=\"2\">";
		print "\n\t\t<input type=\"submit\" value=\"".GM_LANG_view."\" />";
		print "</td>";
		if ($tr) print "</tr>";
	}
	
	public function PrintInputShowCousins() {
		
		print "<tr><td class=\"NavBlockLabel\">";
		print "<input type=\"hidden\" name=\"show_cousins\" value=\"".$this->show_cousins."\" />";
		PrintHelpLink("show_cousins_help", "qm");
		print GM_LANG_show_cousins."</td>";
		print "<td class=\"NavBlockField\"><input ";
		if ($this->chart_style == "0") print "disabled=\"disabled\" ";
		print "id=\"cousins\" type=\"checkbox\" value=\"";
		if ($this->show_cousins) print "1\" checked=\"checked\" onclick=\"document.people.show_cousins.value='0';\"";
		else print "0\" onclick=\"document.people.show_cousins.value='1';\"";
		print " />";
		print "</td></tr>";
	}
	
	public function PrintInputChartStyle() {
				
		print "<tr><td class=\"NavBlockLabel\">";
		PrintHelpLink("chart_style_help", "qm");
		print GM_LANG_displ_layout_conf;
		print "</td>";
		print "<td class=\"NavBlockField\">";
		print "<input type=\"radio\" name=\"chart_style\" value=\"0\" ";
		if ($this->chart_style == "0") print "checked=\"checked\" ";
		print "onclick=\"toggleStatus('cousins');";
//		if ($this->chart_style != "1") print " document.people.chart_style.value='1';";
		print "\" />".GM_LANG_chart_list;
		print "<br /><input type=\"radio\" name=\"chart_style\" value=\"1\" ";
		if ($this->chart_style == "1") print "checked=\"checked\" ";
		print "onclick=\"toggleStatus('cousins');";
//		if ($this->chart_style != "1") print " document.people.chart_style.value='0';";
		print "\" />".GM_LANG_chart_booklet;
		print "</td></tr>";
	}
	
	public function PrintInputShowFull($tr=true) {
		
		if ($tr) print "<tr>";
		print "<td class=\"NavBlockLabel\">";
		print "<input type=\"hidden\" name=\"show_details\" value=\"".$this->show_details."\" />";
		PrintHelpLink("show_full_help", "qm");
		print GM_LANG_show_details;
		print "</td>";
		print "<td class=\"NavBlockField\">";
		print "<input type=\"checkbox\" value=\"";
		if ($this->show_full) print "1\" checked=\"checked\" onclick=\"document.people.show_details.value='-1';\"";
		else print "-1\" onclick=\"document.people.show_details.value='1';\"";
		print " />";
		print "</td>";
		if ($tr) print "</tr>";
	}

	protected function PrintFamArrow($arrow, $person) {
		global $bwidth, $bheight, $TEXT_DIRECTION, $GM_IMAGES;
		
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
					print "<a href=\"javascript: ".GM_LANG_show."\" onclick=\"return togglechildrenbox(".$this->boxcount.");\" onmouseover=\"swap_image('larrow".$this->boxcount."',".($arrow == "u" ? 2 : 3).");\" onmouseout=\"swap_image('larrow".$this->boxcount."',".($arrow == "u" ? 2 : 3).");\">";
					print "<img id=\"larrow".$this->boxcount."\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES[$arrow."arrow"]["other"]."\" border=\"0\" alt=\"\" />";
					print "</a>";
				}
				// Open div childbox_boxcount
				print "\n\t\t<div id=\"childbox".$this->boxcount."\" dir=\"";
				if ($TEXT_DIRECTION=="rtl") print "rtl\" style=\"position:absolute; right: 20px; ";
				else print "ltr\" style=\"position:absolute; left: 20px;";
				print " width:".$bwidth."px; height:".$bheight."px; visibility: hidden;\">";
				// Table level 2-2
				print "\n\t\t\t<table class=\"PersonBox\"><tr><td align=\"left\">";
				foreach($person->spousefamilies as $key => $sfamily) {
					if($person->xref != $sfamily->husb_id) $spouse = $sfamily->husb;
					else $spouse = $sfamily->wife;
					if (is_object($spouse)) {
						print "\n\t\t\t\t<a href=\"".SCRIPT_NAME."?rootid=".$spouse->xref.(isset($this->line) ? "&amp;line=".$this->line : "").(isset($this->split) ? "&amp;split=".$this->split : "").(isset($this->show_spouse) ? "&amp;show_spouse=".$this->show_spouse : "")."&amp;show_details=".$this->show_details."&amp;num_generations=".$this->num_generations."&amp;box_width=".$this->box_width.(isset($this->num_descent) ? "&amp;num_descent=".$this->num_descent : "")."\"><span ";
						if ($spouse->disp_name) {
							if (hasRTLText($spouse->name))
							     print "class=\"PersonNameBold\">";
			   				else print "class=\"PersonName1\">";
							print $spouse->name;
						}
						else print GM_LANG_private;
						print "<br /></span></a>";
					}
					foreach($sfamily->children as $ckey => $child) {
						print "\n\t\t\t\t&nbsp;&nbsp;<a href=\"".SCRIPT_NAME."?rootid=".$child->xref.(isset($this->line) ? "&amp;line=".$this->line : "").(isset($this->split) ? "&amp;split=".$this->split : "").(isset($this->show_spouse) ? "&amp;show_spouse=".$this->show_spouse : "")."&amp;show_details=".$this->show_details."&amp;num_generations=".$this->num_generations."&amp;box_width=".$this->box_width.(isset($this->num_descent) ? "&amp;num_descent=".$this->num_descent : "")."\"><span ";
						if ($child->disp_name) {
							if (hasRTLText($child->name))
							     print "class=\"PersonNameBold\">&lt; ";
				   			else print "class=\"PersonName1\">&lt; ";
							print $child->name;
						}
						else print ">" . GM_LANG_private;
						print "<br /></span></a>";
					}
				}
				//-- print the siblings
				foreach($person->childfamilies as $key => $cfamily) {
					if($cfamily->husb_id != "" || $cfamily->wife_id != "") {
						print "<span class=\"PersonName1\"><br />".GM_LANG_parents."<br /></span>";
						if ($cfamily->husb_id != "") {
							print "\n\t\t\t\t&nbsp;&nbsp;<a href=\"".SCRIPT_NAME."?rootid=".$cfamily->husb_id.(isset($this->line) ? "&amp;line=".$this->line : "").(isset($this->split) ? "&amp;split=".$this->split : "").(isset($this->show_spouse) ? "&amp;show_spouse=".$this->show_spouse : "")."&amp;show_details=".$this->show_details."&amp;num_generations=".$this->num_generations."&amp;box_width=".$this->box_width.(isset($this->num_descent) ? "&amp;num_descent=".$this->num_descent : "")."\"><span ";
							if ($cfamily->husb->disp_name) {
								if (hasRTLText($cfamily->husb->name))
								     print "class=\"PersonNameBold\">";
				   				else print "class=\"PersonName1\">";
								print $cfamily->husb->name;
							}
							else print GM_LANG_private;
							print "<br /></span></a>";
						}
						if ($cfamily->wife_id != "") {
							print "\n\t\t\t\t&nbsp;&nbsp;<a href=\"".SCRIPT_NAME."?rootid=".$cfamily->wife_id.(isset($this->line) ? "&amp;line=".$this->line : "").(isset($this->split) ? "&amp;split=".$this->split : "").(isset($this->show_spouse) ? "&amp;show_spouse=".$this->show_spouse : "")."&amp;show_details=".$this->show_details."&amp;num_generations=".$this->num_generations."&amp;box_width=".$this->box_width.(isset($this->num_descent) ? "&amp;num_descent=".$this->num_descent : "")."\"><span ";
							if ($cfamily->wife->disp_name) {
								if (hasRTLText($cfamily->wife->name))
								     print "class=\"PersonNameBold\">";
				   				else print "class=\"PersonName1\">";
								print $cfamily->wife->name;
							}
							else print GM_LANG_private;
							print "<br /></span></a>";
						}
					}
					if ($cfamily->children_count > 1) {
						print "<span class=\"PersonName1\"><br />".GM_LANG_siblings."<br /></span>";
						foreach($cfamily->children as $key2 => $child) {
							if ($child->xref != $person->xref) {
								print "\n\t\t\t\t&nbsp;&nbsp;<a href=\"".SCRIPT_NAME."?rootid=".$child->xref.(isset($this->line) ? "&amp;line=".$this->line : "").(isset($this->split) ? "&amp;split=".$this->split : "").(isset($this->show_spouse) ? "&amp;show_spouse=".$this->show_spouse : "")."&amp;show_details=".$this->show_details."&amp;num_generations=".$this->num_generations."&amp;box_width=".$this->box_width.(isset($this->num_descent) ? "&amp;num_descent=".$this->num_descent : "")."\"><span ";
								if ($child->disp_name) {
									if (hasRTLText($child->name))
									print "class=\"PersonNameBold\"> ";
					   				else print "class=\"PersonName1\"> ";
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
}
?>
