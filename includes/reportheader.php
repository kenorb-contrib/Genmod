<?php
/**
 * Report Header Parser
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 - 2012 Genmod Development Team
 *
 * used by the SAX parser to generate PDF reports from the XML report file.
 *
 * @package Genmod
 * @subpackage Reports
 * @version $Id$
 */

//-- do not allow direct access to this file
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../intrusion.php";
} 
 
/**
 * element handlers array
 *
 * An array of element handler functions
 * @global array $elementHandler
 */
$elementHandler = array();
$elementHandler["GMReport"]["start"]			= "GMReportSHandler";
$elementHandler["GMRvar"]["start"]			= "GMRvarSHandler";
$elementHandler["GMRTitle"]["start"]			= "GMRTitleSHandler";
$elementHandler["GMRTitle"]["end"]			= "GMRTitleEHandler";
$elementHandler["GMRDescription"]["end"]			= "GMRDescriptionEHandler";
$elementHandler["GMRInput"]["start"]			= "GMRInputSHandler";
$elementHandler["GMRInput"]["end"]			= "GMRInputEHandler";

$text = "";
$report_array = array();

define("AVAIL_PAGE_SIZES", "letter,legal,A4,A3,A5");


/**
 * xml start element handler
 *
 * this function is called whenever a starting element is reached
 * @param resource $parser the resource handler for the xml parser
 * @param string $name the name of the xml element parsed
 * @param array $attrs an array of key value pairs for the attributes
 */
function startElement($parser, $name, $attrs) {
	global $elementHandler, $processIfs, $processGedcoms, $processRepeats;
	
	if (($processIfs==0 || $name=="GMRif")) {
		if (isset($elementHandler[$name]["start"])) call_user_func($elementHandler[$name]["start"], $attrs);
	}
}

/**
 * xml end element handler
 *
 * this function is called whenever an ending element is reached
 * @param resource $parser the resource handler for the xml parser
 * @param string $name the name of the xml element parsed
 */
function endElement($parser, $name) {
	global $elementHandler, $processIfs, $processGedcoms, $processRepeats;
	
	if (($processIfs==0 || $name=="GMRif")) {
		if (isset($elementHandler[$name]["end"])) call_user_func($elementHandler[$name]["end"]);
	}
}

/**
 * xml character data handler
 *
 * this function is called whenever raw character data is reached
 * just print it to the screen
 * @param resource $parser the resource handler for the xml parser
 * @param string $data the name of the xml element parsed
 */
function characterData($parser, $data) {
	global $text;
	$text .= $data;
}

function GMReportSHandler($attrs) {
	global $report_array;
	global $PRIV_PUBLIC, $PRIV_USER, $PRIV_NONE, $PRIV_HIDE;
	
	$access = $PRIV_PUBLIC;
	if (isset($attrs["access"])) {
		if (isset($$attrs["access"])) $access = $$attrs["access"];
	}
	$report_array["access"] = $access;

	if (isset($attrs["icon"])) $report_array["icon"] = $attrs["icon"];
	else $report_array["icon"] = "";

	if (isset($attrs["type"])) $report_array["type"] = $attrs["type"];
	else $report_array["type"] = "general";
}

function GMRvarSHandler($attrs) {
	global $text, $vars, $fact, $desc, $type, $generation;

	$var = $attrs["var"];
	if (!empty($var)) {
		if (!empty($attrs["date"])) {
		}
		if (!empty($vars[$var]['id'])) {
			$var = $vars[$var]['id'];
		}
		else if (substr($var, 0, 8) == "GM_LANG_") {
			$ct = preg_match("/GM_LANG_(.*)/", $var, $match);
			if ($ct > 0) {
				if (substr($match[1], 0, 1) == "$") $match[1] = eval("if(isset($match[1])) print $match[1]; else print '';");
				$var = "GM_LANG_".$match[1];
			}
			if (defined($var)) $var = constant($var);
		}
		else if (substr($var, 0, 8) == "GM_FACT_") {
			$ct = preg_match("/GM_FACT_(.*)/", $var, $match);
			if ($ct > 0) {
				if (substr($match[1], 0, 1) == "$") {
					$match[1] = eval("if(isset($match[1])) print $match[1]; else print '';");
					$var = "GM_FACT_".$match[1];
				}
			}
			if (defined($var)) $var = constant($var);
		}
		else {
			$tfact = $fact;
			if ($fact=="EVEN" || $fact=="FACT") $tfact = $type;
			$var = preg_replace(array("/\[/","/\]/","/@fact/","/@desc/"), array("['","']",$tfact,$desc), $var);
			if (isset($$var)) {
				eval("if (!empty(\$$var)) \$var = \$$var;");
			}
			$ct = preg_match("/factarray\['(.*)'\]/", $var, $match);
			if ($ct>0) {
				if (defined("GM_FACT_".$match[1])) $var = constant("GM_FACT_".$match[1]);
				else $var = $match[1];
			}
		}
		$text .= $var;
	}
}

function GMRTitleSHandler() {
	global $report_array, $text;
	
	$text = "";
}

function GMRTitleEHandler() {
	global $report_array, $text;
	
	$report_array["title"] = $text;
	$text = "";
}

function GMRDescriptionEHandler() {
	global $report_array, $text;
	
	$report_array["description"] = $text;
	$text = "";
}

function GMRInputSHandler($attrs) {
	global $input, $text;
	
	$text ="";
	$input = array();
	$input["name"] = "";
	$input["type"] = "";
	$input["lookup"] = "";
	$input["default"] = "";
	$input["value"] = "";
	$input["options"] = "";
	if (isset($attrs["name"])) $input["name"] = $attrs["name"];
	if (isset($attrs["type"])) $input["type"] = $attrs["type"];
	if (isset($attrs["lookup"])) $input["lookup"] = $attrs["lookup"];
	if (isset($attrs["default"])) {
		switch ($attrs["default"]) {
		case "DEFAULT_PAGE_SIZE":
			$input["default"] = SystemConfig::$DEFAULT_PAGE_SIZE;
			break;
		case "SHOW_ID_NUMBERS":
			$input["default"] = GedcomConfig::$SHOW_ID_NUMBERS;
			break;
		case "SHOW_FAM_ID_NUMBERS":
			$input["default"] = GedcomConfig::$SHOW_FAM_ID_NUMBERS;
			break;
		case "NOW":
			$input["default"] = date("d M Y");
			break;
		default:
			$ct = preg_match("/NOW\s*([+\-])\s*(\d+)/", $attrs['default'], $match);
			if ($ct>0) {
//				$plus = 1;
//				if ($match[1]=="-") $plus = -1;
//				$input["default"] = date("d M Y", time()+$plus*60*60*24*$match[2]);
//print $match[1].$match[2]." days";
				$input["default"] = date("d M Y", strtotime($match[1].$match[2]." days"));
			}
			else {
				$input["default"] = $attrs["default"];
			}
			break;
		}
	}
	if (isset($attrs["options"])) {
		$options = $attrs["options"];
		if ($options == "AVAIL_PAGE_SIZES") {
			$options = AVAIL_PAGE_SIZES;
		}
		$input["options"] = $options;
	}
}

function GMRInputEHandler() {
	global $report_array, $text, $input;
	
	$input["value"] = $text;
	if (!isset($report_array["inputs"])) $report_array["inputs"] = array();
	$report_array["inputs"][] = $input;
	$text = "";
}

?>
