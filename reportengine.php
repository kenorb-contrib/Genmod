<?php
/**
 * Report Engine
 *
 * Processes GM XML Reports and generates a report
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 Genmod Development Team
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
 * @package Genmod
 * @subpackage Reports
 * @version $Id: reportengine.php,v 1.4 2006/02/19 18:40:23 roland-d Exp $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

/**
 * Inclusion of the chart functions
*/
require("includes/functions_charts.php");

/**
 * Inclusion of the language files
*/
require($GM_BASE_DIRECTORY.$factsfile["english"]);
if (file_exists($GM_BASE_DIRECTORY.$factsfile[$LANGUAGE])) require($GM_BASE_DIRECTORY.$factsfile[$LANGUAGE]);

@set_time_limit($TIME_LIMIT*2);
function get_tag_values($tag) {
	global $tags, $values;

	$indexes = $tags[$tag];
	$vals = array();
	foreach($indexes as $indexval => $i) {
		$vals[] = $values[$i];
	}
	return $vals;
}

if (empty($action)) $action = "choose";
if (!isset($report)) $report = "";
if (!isset($output)) $output = "PDF";
if (!isset($vars)) $vars = array();
if (!isset($varnames)) $varnames = array();
if (!isset($type)) $type = array();

$newvars = array();
foreach($vars as $name=>$var) {
	$var = clean_input($var);
	$newvars[$name]["id"] = $var;
	if (!empty($type[$name]) && (($type[$name]=="INDI")||($type[$name]=="FAM")||($type[$name]=="SOUR"))) {
		$gedcom = find_gedcom_record($var);
		if (empty($gedcom)) $action="setup";
		if ($type[$name]=="FAM") {
			if (preg_match("/0 @.*@ INDI/", $gedcom)>0) {
				$fams = find_sfamily_ids($var);
				if (!empty($fams[0])) {
					$gedcom = find_family_record($fams[0]);
					if (!empty($gedcom)) $vars[$name] = $fams[0];
					else $action="setup";
				}
			}
		}
		$newvars[$name]["gedcom"] = $gedcom;
	}
}
$vars = $newvars;

foreach($varnames as $indexval => $name) {
	if (!isset($vars[$name])) {
		$vars[$name]["id"] = "";
	}
}

$reports = get_report_list();
if (!empty($report)) {
	$r = basename($report);
	if (!isset($reports[$r]["access"])) $action = "choose";
	else if ($reports[$r]["access"]<getUserAccessLevel($gm_username)) $action = "choose";
}

//-- choose a report to run
if ($action=="choose") {
	$reports = get_report_list(true);
	print_header($gm_lang["choose_report"]);

	print "<br /><br />\n";
	print "<form name=\"choosereport\" method=\"get\" action=\"reportengine.php\">\n";
	print "<input type=\"hidden\" name=\"action\" value=\"setup\" />\n";
	print "<input type=\"hidden\" name=\"output\" value=\"$output\" />\n";
	print "<table class=\"facts_table center $TEXT_DIRECTION\">";
	print "<tr><td class=\"topbottombar\" colspan=\"2\">".$gm_lang["choose_report"]."</td></tr>";
	print "<tr><td class=\"shade2 wrap width20 vmiddle\">".$gm_lang["select_report"]."</td>";
	print "<td class=\"shade1\">";
	print "<select name=\"report\">\n";
	foreach($reports as $file=>$report) {
		print "<option value=\"".$report["file"]."\">".$report["title"][$LANGUAGE]."</option>\n";
	}
	print "</select></td></tr>\n";
	print "<tr><td class=\"topbottombar\" colspan=\"2\"><input type=\"submit\" value=\"".$gm_lang["click_here"]."\" /></td></tr>";
	print "</table>";
	print "</form>\n";
	print "<br /><br />\n";

	print_footer();
}

//-- setup report to run
else if ($action=="setup") {
	print_header($gm_lang["enter_report_values"]);
	//-- make sure the report exists
	if (!file_exists($report)) {
		print "<span class=\"error\">The specified report cannot be found</span>\n";
	}
	else {
		require_once("includes/reportheader.php");
		$report_array = array();
		//-- start the sax parser
		$xml_parser = xml_parser_create();
		//-- make sure everything is case sensitive
		xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
		//-- set the main element handler functions
		xml_set_element_handler($xml_parser, "startElement", "endElement");
		//-- set the character data handler
		xml_set_character_data_handler($xml_parser, "characterData");

		//-- open the file
		if (!($fp = fopen($report, "r"))) {
		   die("could not open XML input");
		}
		//-- read the file and parse it 4kb at a time
		while ($data = fread($fp, 4096)) {
			if (!xml_parse($xml_parser, $data, feof($fp))) {
				die(sprintf($data."\nXML error: %s at line %d", xml_error_string(xml_get_error_code($xml_parser)), xml_get_current_line_number($xml_parser)));
			}
		}
		xml_parser_free($xml_parser);

		?>
<script type="text/javascript">
<!--
var pastefield;
function paste_id(value) {
	pastefield.value=value;
}
//-->
</script>
		<?php
		init_calendar_popup();
		print "<form name=\"setupreport\" method=\"get\" action=\"reportengine.php\">\n";
		print "<input type=\"hidden\" name=\"action\" value=\"run\" />\n";
		print "<input type=\"hidden\" name=\"report\" value=\"$report\" />\n";
		print "<input type=\"hidden\" name=\"download\" value=\"\" />\n";
		print "<input type=\"hidden\" name=\"output\" value=\"PDF\" />\n";
		/* -- this will allow user to select future output formats
		print "<select name=\"output\">\n";
		print "<option value=\"HTML\">HTML</option>\n";
		print "<option value=\"PDF\">PDF</option>\n";
		print "</select><br />\n";
		*/
		print "<table class=\"facts_table width50 center $TEXT_DIRECTION\">";
		print "<tr><td class=\"topbottombar\" colspan=\"2\">".$gm_lang["enter_report_values"]."</td></tr>";
		print "<tr><td class=\"shade2 width30 wrap\">".$gm_lang["selected_report"]."</td><td class=\"shade1\">".$report_array["title"]."</td></tr>\n";
		
		$firstrun = 0;
		if (!isset($report_array["inputs"])) $report_array["inputs"] = array();
		foreach($report_array["inputs"] as $indexval => $input) {
			if ((($input["name"] == "sources") && ($SHOW_SOURCES>=getUserAccessLevel($gm_username))) || ($input["name"] != "sources")) {
				if (($input["name"] != "photos") || ($MULTI_MEDIA)) {
					print "<tr><td class=\"shade2 wrap\">\n";
					print "<input type=\"hidden\" name=\"varnames[]\" value=\"".$input["name"]."\" />\n";
					print $input["value"]."</td><td class=\"shade1\">";
					if (!isset($input["type"])) $input["type"] = "text";
					if (!isset($input["default"])) $input["default"] = "";
					if (isset($input["lookup"])) {
						if ($input["lookup"]=="INDI") {
							if (!empty($pid)) $input["default"] = clean_input($pid);
							else $input["default"] = check_rootid($input["default"]);
						}
						if ($input["lookup"]=="FAM") {
							if (!empty($famid)) $input["default"] = clean_input($famid);
						}
						if ($input["lookup"]=="SOUR") {
							if (!empty($sid)) $input["default"] = clean_input($sid);
						}
					}
					if ($input["type"]=="text") {
						print "<input type=\"text\" name=\"vars[".$input["name"]."]\" id=\"".$input["name"]."\" ";
						print "value=\"".$input["default"]."\" ";
						print " style=\"direction: ltr;\" ";
						print "/>";
					}
					if ($firstrun == 0) {
						?>
						<script language="JavaScript" type="text/javascript">
							document.getElementById('<?php print $input["name"]; ?>').focus();
						</script>
						<?php
						$firstrun++;
					}
					if ($input["type"]=="checkbox") {
						print "<input type=\"checkbox\" name=\"vars[".$input["name"]."]\" id=\"".$input["name"]."\" value=\"1\"";
						if ($input["default"]=="1") print "checked=\"checked\"";
						print " />";
					}
					if ($input["type"]=="select") {
						print "<select name=\"vars[".$input["name"]."]\" id=\"".$input["name"]." var\">\n";
						$options = preg_split("/[, ]+/", $input["options"]);
						foreach($options as $indexval => $option) {
							print "\t<option value=\"$option\">";
							if (isset($gm_lang[$option])) print $gm_lang[$option];
							else if (isset($factarray[$option])) print $factarray[$option];
							else print $option;
							print "</option>\n";
						}
						print "</select>\n";
					}		
					if (isset($input["lookup"])) {
						print "<input type=\"hidden\" name=\"type[".$input["name"]."]\" value=\"".$input["lookup"]."\" />";
						if ($input["lookup"]=="FAM") print_findfamily_link("famid");
						if ($input["lookup"]=="INDI") print_findindi_link("pid","");
						if ($input["lookup"]=="PLAC") print_findplace_link("birthplace");
						if ($input["lookup"]=="DATE") {
							$text = $gm_lang["select_date"];
							if (isset($GM_IMAGES["calendar"]["button"])) $Link = "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["calendar"]["button"]."\" name=\"a_".$input["name"]."\" id=\"a_".$input["name"]."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
							else $Link = $text;

							?>
							<a href="javascript: <?php print $input["name"]; ?>" onclick="cal_toggleDate('div_<?php print $input["name"]; ?>', '<?php print $input["name"]; ?>'); return false;">
							<?php print $Link;?>
							</a>
							<div id="div_<?php print $input["name"]; ?>" style="position:absolute;visibility:hidden;background-color:white;layer-background-color:white;"></div>
							<?php
						}
					}
					print "</td></tr>\n";
				}
			}
		}
		print "<tr><td class=\"topbottombar\" colspan=\"2\"><input type=\"submit\" value=\"".$gm_lang["download_report"]."\" onclick=\"document.setupreport.elements['download'].value='1';\"/></td></tr>\n";
		print "</table>\n";
		print "</form>\n";
		print "<br /><br />\n";
	}
	print_footer();
}
//-- run the report
else if ($action=="run") {
	//-- load the report generator
	if ($output=="HTML") require("includes/reporthtml.php");
	else if ($output=="PDF") require("includes/reportpdf.php");

	//-- start the sax parser
	$xml_parser = xml_parser_create();
	//-- make sure everything is case sensitive
	xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
	//-- set the main element handler functions
	xml_set_element_handler($xml_parser, "startElement", "endElement");
	//-- set the character data handler
	xml_set_character_data_handler($xml_parser, "characterData");

	//-- open the file
	if (!($fp = fopen($report, "r"))) {
	   die("could not open XML input");
	}
	//-- read the file and parse it 4kb at a time
	while ($data = fread($fp, 4096)) {
		if (!xml_parse($xml_parser, $data, feof($fp))) {
			die(sprintf($data."\nXML error: %s at line %d", xml_error_string(xml_get_error_code($xml_parser)), xml_get_current_line_number($xml_parser)));
		}
	}
	xml_parser_free($xml_parser);

}

?>