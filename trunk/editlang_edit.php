<?php
/**
 * Edit a language file
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
 * @subpackage EditLang
 * @version $Id: editlang_edit.php,v 1.3 2006/01/22 19:50:24 roland-d Exp $
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

/**
 * Inclusion of the language files
*/
if (file_exists($GM_BASE_DIRECTORY . $confighelpfile[$LANGUAGE])) require $GM_BASE_DIRECTORY . $confighelpfile[$LANGUAGE];

/**
 * Inclusion of the language editing functions
*/
require $GM_BASE_DIRECTORY . "includes/functions_editlang.php";

//-- make sure that they have admin status before they can use this page
//-- otherwise have them login again
$uname = $gm_username;
if (empty($uname)) {
	print "Please close this window and do a Login in the former window first...";
	exit;
}

if (!isset($lang_filename)) $lang_filename = "";
if (!isset($file_type)) $file_type = "";
if (!isset($language2)) $language2 = "";
if (!isset($ls01)) $ls01 = "";
if (!isset($ls02)) $ls02 = "";
if (!isset($lang_filename_orig)) $lang_filename_orig = "";

print_simple_header($gm_lang["editlang"]);

print "<script language=\"JavaScript\" type=\"text/javascript\">";
print "self.focus();";
print "</script>\n";

switch ($file_type) {
	case "lang": 
		$lang_filename = $language2;
  		$lang_filename_orig = "english";
  		break;
	case "facts":
		$lang_filename = $factsfile[$language2];
  		$lang_filename_orig = $factsfile["english"];
  		break;
	case "help_text": 
		$lang_filename = $language2;
  		$lang_filename_orig = "english";
  		break;
}

if ($action != "save") {
	print "<div align=\"center\"><center>";
	print "<table class=\"facts_table\">";
	print "<tr>";
	print "<td class=\"facts_label03\">";
	print_text("editlang_help");
	print "</td>";
	print "</tr>";
	print "<tr>";
	print "<td class=\"facts_value\" style=\"text-align:center; \">"."(" .$lang_filename.")"."</td>";
	print "</tr>";
	print "</table>";
	
	print "<form name=\"Form1\" method=\"post\" action=\"" .$PHP_SELF. "\">";
	print "<input type=\"hidden\" name=\"".session_name()."\" value=\"".session_id()."\" />";
	print "<input type=\"hidden\" name=\"action\" value=\"save\" />";
	print "<input type=\"hidden\" name=\"anchor\" value=\"".$anchor."\" />";
	print "<input type=\"hidden\" name=\"language2\" value=\"" . $language2 . "\" />";
	print "<input type=\"hidden\" name=\"ls01\" value=\"" . $ls01 . "\" />";
	print "<input type=\"hidden\" name=\"ls02\" value=\"" . $ls02 . "\" />";
	print "<input type=\"hidden\" name=\"file_type\" value=\"" . $file_type . "\" />";
	
	print "<table class=\"facts_table\">";
	print "<tr>";
	print "<td class=\"facts_label03\" style=\"color: #0000FF; font-weight: bold; \">";
	print_text("original_message");
	print "</td>";
	print "</tr>";
	print "<tr>";
	print "<td class=\"facts_value\" style=\"text-align:center; color: #0000FF\" >";
	// print "<strong style=\"color: red\">|</strong>" . stripslashes(mask_all(find_in_file($ls01, $lang_filename_orig))) . "<strong style=\"color: red\">|</strong>";
	print "<strong style=\"color: red\">|</strong>" . stripslashes(mask_all(getString($ls01, $lang_filename_orig))) . "<strong style=\"color: red\">|</strong>";
	print "</td>";
	print "</tr>";
	print "</table>";
	print "<br />";
	print "<table class=\"facts_table\">";
	print "<tr>";
	print "<td class=\"facts_label03\" style=\"color: #FF0000; font-weight: bold; \" >";
	print_text("message_to_edit");
	print "</td>";
	print "</tr>";
	print "<tr>";
	print "<td class=\"facts_value\" style=\"text-align:center; \" >";
	print "<textarea rows=\"10\" name=\"new_message\" cols=\"75\" style=\"color: #FF0000\" >";
	// if ($ls02>0) print stripslashes(mask_all(find_in_file($ls02, $lang_filename)));
	if (strlen($ls02) > 0) print stripslashes(mask_all(getString($ls02, $lang_filename)));
	print "</textarea>";
	print "</td>";
	print "</tr>";
	print "</table>";
	print "<br />";
	print "<table class=\"facts_table\">";
	print "<tr>";
	print "<td class=\"facts_value\" style=\"text-align:center; \" >";
	print "<input type=\"submit\" value=\"";
	print_text("lang_save");
	print "\" />";
	print "&nbsp;&nbsp;";
	print "<input type=\"submit\" value=\"";
	print_text("cancel");
	print "\"" . " onclick=\"self.close()\" />";
	print "</td>";
	print "</tr>";
	print "</table>";
	print "</form>";
	print "</center></div>";
}

if ($action == "save") {
	if (!isset($_POST)) $_POST = $HTTP_POST_VARS;
	
	// Post-parameters
	// $new_message is the edited message
	// $language2 is the name of the language to edit
	// $ls01 is the number of the message in english language file
	// $ls02 is the number of the message in the edited language file
	// $file_type defines which language file
	
	switch ($file_type) {
		case "lang": // read the english lang.en.php file into array
			$english_language_array = array();
			$english_language_array = loadEnglish(true);
			// read the chosen lang.xx.php file into array
			$new_language_array = array();
			// $new_language_file = $GM_BASE_DIRECTORY . $gm_language[$language2];
			$new_language_array = loadLanguage($language2, true);
			break;
		case "facts": // read the english lang.en.php file into array
			$english_language_array = array();
			$english_language_array = read_complete_file_into_array($factsfile["english"], "factarray[");
			// read the chosen lang.xx.php file into array
			$new_language_array = array();
			$new_language_file = $GM_BASE_DIRECTORY . $factsfile[$language2];
			$new_language_array = read_complete_file_into_array($new_language_file, "factarray[");
			break;
		case "help_text": // read the english lang.en.php file into array
			$english_language_array = array();
			$english_language_array = loadEnglish(true);
			// read the chosen lang.xx.php file into array
			$new_language_array = array();
			// $new_language_file = $GM_BASE_DIRECTORY . $gm_language[$language2];
			$new_language_array = loadLanguage($language2, true);
			break;
	}
	
	// $new_message = add_backslash_before_dollarsign($new_message);
	$new_message = preg_replace(array("/&amp;/","/&lt;/","/&gt;/"), array("&","<",">"), $new_message);
	// $new_message_line = (-1);
	// if (isset($new_language_array[$ls02])) $dummyArray = $new_language_array[$ls02];
	// else $dummyArray = array();
	// 
	// if (strlen($ls02) < 1) {
		// $dummyArray = $english_language_array[$ls01];
		// $new_message_line = abs($ls02);
	// }
	// if (($new_message_line == 0)||($new_message_line>sizeof($new_language_array))) {
		// $new_message_line = sizeof($new_language_array) - 2;
	// }
	// 
	// $new_message = crlf_lf_to_br($new_message);
	// $dummyArray[1] = $new_message;
	// $dummyArray[3] = substr($dummyArray[3], 0, $dummyArray[2]) . $new_message . "\";";
	// 
	// if (strlen($ls02) > 0) $new_language_array[$ls02] = $dummyArray;
	// 
	// if (strlen($ls02) == 0) {
		// # $new_language_array[$ls02] = $dummyArray;
		// $ls02 = $new_message_line;
	// }
	// 
	// @copy($new_language_file, $new_language_file . ".old");
	// $Write_Ok = write_array_into_file($new_language_file, $new_language_array, $new_message_line, $dummyArray[3]);
	$Write_Ok = writeString($new_message, $ls01, $language2);
	
	print "<div align=\"center\"><center>";
	
	print "<table class=\"facts_table\">";
	print "<tr>";
	print "<td class=\"facts_label03\">";
	print_text("savelang_help");
	print "</td>";
	print "</tr>";
	print "<tr>";
	print "<td class=\"facts_value\" style=\"text-align:center; \">"."(".$lang_filename.")"."</td>";
	print "</tr>";
	print "</table>";
	
	print "<form name=\"Form2\" method=\"post\" action=\"" .$PHP_SELF. "\">";
	print "<table class=\"facts_table\">";
	print "<tr>";
	if ($Write_Ok) print "<td class=\"facts_label03\" style=\"color: #0000FF; font-weight: bold; \">".print_text("original_message",0,1);
	else {
		print "<td class=\"warning\" >";
		print str_replace("#lang_filename#", $lang_filename, $gm_lang["lang_file_write_error"]) . "<br /><br />";
	}
	print "</td>";
	print "</tr>";
	if ($Write_Ok) {
		print "<tr>";
		print "<td class=\"facts_value\" style=\"text-align:center; color: #0000FF\" >";
		print "<strong style=\"color: red\">|</strong>".stripslashes(mask_all(getString($ls01, $lang_filename_orig)))."<strong style=\"color: red\">|</strong>";
		print "</td>";
		print "</tr>";
	}
	print "</table>";
	
	if ($Write_Ok) {
		print "<br />";
		print "<table class=\"facts_table\">";
		print "<tr>";
		print "<td class=\"facts_label03\" style=\"color: #0000FF; font-weight: bold; \">";
		print_text("changed_message");
		print "</td>";
		print "</tr>";
		
		print "<tr>";
		print "<td class=\"facts_value\" style=\"text-align:center; color: #0000FF\" >";
		print "<strong style=\"color: red; \">|</strong>" . stripslashes(mask_all($new_message)) . "<strong style=\"color: red\">|</strong>";
		print "</td>";
		print "</tr>";
		print "</table>";
		
		print "<br />";
	}
	
	print "<table class=\"facts_table\">";
	print "<tr>";
	print "<td class=\"facts_value\" style=\"text-align:center; \" >";
	srand((double)microtime()*1000000);
	print "<input type=\"submit\" value=\"" . $gm_lang["close_window"] . "\"" . " onclick=\"window.opener.showchanges('&dv=".rand()."#".$anchor."'); self.close();\" />";
	print "</td>";
	print "</tr>";
	print "</table>";
	
	print "</form>";
	print "</center></div>";
}

print_simple_footer();

?>