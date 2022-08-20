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
 * $Id: debugcollector_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 * @package Genmod
 * @subpackage Controllers
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

abstract class DebugCollector {
	
	public $classname = "DebugCollector"; 																	// Name of this class
	public static $show = false;																			// Switch on/off the collector
	private static $debugoutput = array("output" => array(), "query" => array(), "autoload" => array());	// Collector for the debug output
	
	private function PrintRecord($record) {
		self::OutputCollector(print_r($record, true));
	}
	
	private function debugoutputselect($type="output") {
		if ($type == "output") return self::$debugoutput["output"];
		else if ($type == "queries") return self::$debugoutput["query"];
		else if ($type == "autoload") return self::$debugoutput["autoload"];
	}
	
	public function OutputCollector($output, $type="output") {
		if ($type == "output") self::$debugoutput["output"][] = $output;
		else if ($type == "query") self::$debugoutput["query"][] = $output;
		else if ($type == "autoload") self::$debugoutput["autoload"][] = $output;
	}
	
	private function PrintRHtml($arr, $style = "display: none; margin-left: 10px;") {
		if (!self::$show) return false;
		else {
			static $i = 0; $i++;
			echo "\n<div id=\"array_tree_$i\" class=\"array_tree row\">\n";
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
							echo self::PrintRHtml($val);
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
							echo "<code><pre><b>".htmlspecialchars($key)."</b> => ".htmlspecialchars($val)."</pre></code>";
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
	
	/**
	 * Creates a list of div ids to hide
	 *
	 * A list of divs to hide for a menu.
	 *
	 * @author	Genmod Development Team
	 * @param		array	$pages	The array with pages to hide
	 * @param		string	$show	The name of the page to show
	 */
	private function HideDivs($pages, $show) {
		foreach ($pages as $id => $page) {
			if ($page == $show) {
				echo "expand_layer('".$page."', true); ";
				echo "ChangeClass('".$page."_tab', 'current'); ";
			}
			else {
				echo "expand_layer('".$page."', false); ";
				echo "ChangeClass('".$page."_tab', ''); ";
			}
		}
	}
	
	/**
	 * Shows debug information
	 *
	 * Creates a menu with different sections to show debug info
	 * It shows info on output (defined by developer), database queries,
	 * _session, _post and _get variable
	 *
	 * @author	Genmod Development Team
	 * @param		array	$pages	The array with pages to hide
	 * @param		string	$show	The name of the page to show
	 */
	public function PrintDebug() {
		
		// If we don't show the debug, return empty
		if (!self::$show) return false;
		else {
			$pages = array("output", "autoload", "queries", "session", "post", "get");
			?>
			<div id="DebugOutput">
			<ul>
				<li id="output_tab" class="current" ><a href="#" onclick="<?php self::HideDivs($pages, 'output');  ?> return false;">Output</a></li>
				<li id="autoload_tab"><a href="#" onclick="<?php self::HideDivs($pages, 'autoload');  ?> return false;">Autoload</a></li>
				<li id="queries_tab"><a href="#" onclick="<?php self::HideDivs($pages, 'queries');  ?> return false;">Queries</a></li>
				<li id="session_tab"><a href="#" onclick="<?php self::HideDivs($pages, 'session');  ?> return false;">SESSION</a></li>
				<li id="post_tab"><a href="#" onclick="<?php self::HideDivs($pages, 'post');  ?> return false;">POST</a></li>
				<li id="get_tab"><a href="#" onclick="<?php self::HideDivs($pages, 'get');  ?> return false;">GET</a></li>
			</ul>
			</div>
			<?php
			echo '<div id="output" style="display: show;">';
			echo self::PrintRHtml(self::debugoutputselect("output"));
			echo '</div>';
			echo '<div id="autoload" style="display: none;">';
			echo self::PrintRHtml(self::debugoutputselect("autoload"));
			echo '</div>';
			echo '<div id="queries" style="display: none;">';
			echo self::PrintRHtml(self::debugoutputselect("queries"));
			echo '</div>';
			echo '<div id="session" style="display: none;">';
			echo self::PrintRHtml($_SESSION);
			echo '</div>';
			echo '<div id="post" style="display: none;">';
			echo self::PrintRHtml($_POST);
			echo '</div>';
			echo '<div id="get" style="display: none;">';
			echo self::PrintRHtml($_GET);
			echo '</div>';
		}
	}
}
?>
