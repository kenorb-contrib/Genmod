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
 * @version $Id$
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
 
/**
 * Main controller class for the individual page.
 */
class ChartController extends BaseController {
	
	public $classname = "ChartController";	// Name of this class
	protected $root = null;					// Holder for the root person object
	protected $show_full = null;			// Display details in chart boxes
	protected $show_details = null;			// Display details in chart boxes (input variable)
	protected $show_cousins = null;
	protected $box_width = null;
	protected $chart_style = null;
	protected $min_generation = null;		// Generate an error that a minimum number of generations must be displayed
	protected $max_generation = null;		// Generate an error that no more than a maximum number of generations can be displayed
	protected $num_generations = null;		// Number of generations to display
	
	
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

		if (!isset($_REQUEST["box_width"]) || $_REQUEST["box_width"] == "") $this->box_width = "100";
		else $this->box_width = $_REQUEST["box_width"];
		$this->box_width = max($this->box_width, 50);
		$this->box_width = min($this->box_width, 300);
		
		global $box_width;
		$box_width = $this->box_width;
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
		
		print "<tr><td class=\"shade2\">";
		print_help_link("rootid_help", "qm");
		print GM_LANG_root_person."&nbsp;</td>";
		print "<td class=\"shade1 vmiddle\">";
		print "<input class=\"pedigree_form\" type=\"text\" name=\"rootid\" id=\"rootid\" size=\"3\" value=\"".$this->xref."\" />";
		LinkFunctions::PrintFindIndiLink("rootid","");
		print "</td></tr>";
	}
	
	public function PrintInputBoxWidth($tr=true) {
		
		if ($tr) print "<tr>";
		print "<td class=\"shade2\">";
		print_help_link("box_width_help", "qm");
		print GM_LANG_box_width . "&nbsp;</td>";
		print "<td class=\"shade1 vmiddle\"><input type=\"text\" size=\"3\" name=\"box_width\" value=\"".$this->box_width."\" /> <b>%</b>";
		print "</td>";
		if ($tr) print "</tr>";
	}

	public function PrintInputHeader($tr=true) {
		
		if ($tr) print "<tr>";
		print "<td colspan=\"2\" class=\"topbottombar\" style=\"text-align:center; \">";
		print GM_LANG_options."</td>";
		if ($tr) print "</tr>";
	}
	
	public function PrintInputGenerations($gens, $help) {	
		
		print "<tr><td class=\"shade2\">";
		print_help_link($help, "qm");
		print GM_LANG_generations . "&nbsp;</td>";
	
		print "<td class=\"shade1 vmiddle\">";
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
		print "<td class=\"center\" colspan=\"2\">";
		print "\n\t\t<input type=\"submit\" value=\"".GM_LANG_view."\" />";
		print "</td>";
		if ($tr) print "</tr>";
	}
	
	public function PrintInputShowCousins() {
		
		print "<tr><td class=\"shade2\">";
		print "<input type=\"hidden\" name=\"show_cousins\" value=\"".$this->show_cousins."\" />";
		print_help_link("show_cousins_help", "qm");
		print GM_LANG_show_cousins."</td>";
		print "<td class=\"shade1 vmiddle\"><input ";
		if ($this->chart_style == "0") print "disabled=\"disabled\" ";
		print "id=\"cousins\" type=\"checkbox\" value=\"";
		if ($this->show_cousins) print "1\" checked=\"checked\" onclick=\"document.people.show_cousins.value='0';\"";
		else print "0\" onclick=\"document.people.show_cousins.value='1';\"";
		print " />";
		print "</td></tr>";
	}
	
	public function PrintInputChartStyle() {
				
		print "<tr><td class=\"shade2\">";
		print_help_link("chart_style_help", "qm");
		print GM_LANG_displ_layout_conf;
		print "</td>";
		print "<td class=\"shade1 vmiddle\">";
		print "<input type=\"radio\" name=\"chart_style\" value=\"0\" ";
		if ($this->chart_style == "0") print "checked=\"checked\" ";
		print "onclick=\"toggleStatus('cousins');";
		if ($this->chart_style != "1") print " document.people.chart_style.value='1';";
		print "\" />".GM_LANG_chart_list;
		print "<br /><input type=\"radio\" name=\"chart_style\" value=\"1\" ";
		if ($this->chart_style == "1") print "checked=\"checked\" ";
		print "onclick=\"toggleStatus('cousins');";
		if ($this->chart_style != "1") print " document.people.chart_style.value='0';";
		print "\" />".GM_LANG_chart_booklet;
		print "</td></tr>";
	}
	
	public function PrintInputShowFull($tr=true) {
		
		if ($tr) print "<tr>";
		print "<td class=\"shade2\">";
		print "<input type=\"hidden\" name=\"show_details\" value=\"".$this->show_details."\" />";
		print_help_link("show_full_help", "qm");
		print GM_LANG_show_details;
		print "</td>";
		print "<td class=\"shade1 vmiddle\">";
		print "<input type=\"checkbox\" value=\"";
		if ($this->show_full) print "1\" checked=\"checked\" onclick=\"document.people.show_details.value='-1';\"";
		else print "-1\" onclick=\"document.people.show_details.value='1';\"";
		print " />";
		print "</td>";
		if ($tr) print "</tr>";
	}
}
?>
