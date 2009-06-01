<?php
/**
 * Controls all the debug information
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
 * $Id$
 * @package Genmod
 * @subpackage Controllers
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class DebugCollector {
	
	var $classname = "DebugCollector";
	var $show = false;
	
	function DebugCollector() {
		$this->debugoutput["output"] = array();
		$this->debugoutput["query"] = array();
	}
	
	function PrintRecord($record) {
		$this->OutputCollector(print_r($record, true));
	}
	
	function debugoutputselect($type="output") {
		if ($type == "output") return $this->debugoutput["output"];
		else if ($type == "queries") return $this->debugoutput["query"];
	}
	
	function OutputCollector($output, $type="output") {
		if ($type == "output") $this->debugoutput["output"][] = $output;
		else if ($type == "query") $this->debugoutput["query"][] = $output;
	}
	
	function PrintRHtml($arr, $style = "display: none; margin-left: 10px;") {
		if (!$this->show) return false;
		else {
			static $i = 0; $i++;
			echo "\n<div id=\"array_tree_$i\" class=\"array_tree\">\n";
			if (count($arr) == 0) {
				echo 'No data to show.';
			}
			else {
				foreach($arr as $key => $val) {
					switch (gettype($val)) { 
						case "array":
							echo "<a onclick=\"document.getElementById('";
							echo "array_tree_element_".$i."').style.display = ";
							echo "document.getElementById('array_tree_element_$i";
							echo "').style.display == 'block' ?";
							echo "'none' : 'block';\"\n";
							echo "name=\"array_tree_link_$i\" href=\"#array_tree_link_$i\">".htmlspecialchars($key)."</a><br />\n";
							echo "<div class=\"array_tree_element_\" id=\"array_tree_element_$i\" style=\"$style\">";
							echo $this->PrintRHtml($val);
							echo "</div>";
							break;
						case "integer":
							echo "<b>".htmlspecialchars($key)."</b> => <i>".htmlspecialchars($val)."</i><br />";
							break;
						case "double":
							echo "<b>".htmlspecialchars($key)."</b> => <i>".htmlspecialchars($val)."</i><br />";
							break;
						case "boolean":
							echo "<b>".htmlspecialchars($key)."</b> => ";
							if ($val) { 
								echo "true"; 
							}
							else { 
								echo "false"; 
							}
							echo  "<br />\n";
							break;
						case "string":
							echo "<b>".htmlspecialchars($key)."</b> => <code><pre>".htmlspecialchars($val)."</pre></code><br />";
							break;
						default:
							echo "<b>".htmlspecialchars($key)."</b> => ".gettype($val)."<br />";
							break; 
					}
					echo "\n"; 
				}
			}
			echo "</div>\n";
		}
	}
}
?>
