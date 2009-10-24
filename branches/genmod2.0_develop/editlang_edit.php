<?php
/**
 * Edit a language file
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
 * @package Genmod
 * @subpackage EditLang
 * @version $Id$
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

/**
 * Inclusion of the language editing functions
*/
require $GM_BASE_DIRECTORY . "includes/functions/functions_editlang.php";

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
if (!isset($anchor)) $anchor = "";
if (!isset($realtime)) $realtime = false;

PrintSimpleHeader($gm_lang["editlang"]);

print "<script language=\"JavaScript\" type=\"text/javascript\">";
print "<!--\nself.focus();\n//-->";
print "</script>\n";

switch ($file_type) {
	case "lang": 
		$lang_filename = $language2;
  		$lang_filename_orig = "english";
  		break;
	case "facts":
		$lang_filename = $language2;
  		$lang_filename_orig = "english";
  		break;
	case "help_text": 
		$lang_filename = $language2;
  		$lang_filename_orig = "english";
  		break;
}

if ($action != "save") {
	print "<form name=\"Form1\" method=\"post\" action=\"" .$_SERVER["SCRIPT_NAME"]. "\">";
	print "<input type=\"hidden\" name=\"".session_name()."\" value=\"".session_id()."\" />";
	print "<input type=\"hidden\" name=\"action\" value=\"save\" />";
	print "<input type=\"hidden\" name=\"anchor\" value=\"".$anchor."\" />";
	print "<input type=\"hidden\" name=\"language2\" value=\"" . $language2 . "\" />";
	print "<input type=\"hidden\" name=\"ls01\" value=\"" . $ls01 . "\" />";
	print "<input type=\"hidden\" name=\"ls02\" value=\"" . $ls02 . "\" />";
	print "<input type=\"hidden\" name=\"file_type\" value=\"" . $file_type . "\" />";
	print "<input type=\"hidden\" name=\"realtime\" value=\"" . $realtime . "\" />";
	
	print '<div id="editlang_edit_title" class="center">';
	print_text("editlang_help");
	if (!empty($lang_filename)) print ' '.$language_settings[$lang_filename]['gm_lang'];
	print '</div>';
	
	print "<div id=\"toplinks\" name=\"toplinks\" class=\"center\">";
	print "<input type=\"submit\" value=\"";
	print_text("lang_save");
	if ($realtime) print "\" onclick=\"window.opener.location.reload()\" />";
	else print "\" />";
	print "&nbsp;&nbsp;";
	print "<input  type=\"submit\" value=\"";
	print_text("cancel");
	print "\"" . " onclick=\"self.close()\" />";
	print "</div>";
	print "<hr />";
	
	print '<div id="original_text">';
	print_text("original_message");
	print "<textarea id=\"old_message\" readonly rows=\"10\" name=\"old_message\" cols=\"75\" >";
	print stripslashes(mask_all(GetString($ls01, $lang_filename_orig, $file_type)));
	print "</textarea>";
	print '</div>';
	
	print '<div id="translated_text">';
	print_text("message_to_edit");
	print "<textarea rows=\"10\" id=\"new_message\" name=\"new_message\" cols=\"75\" style=\"color: #FF0000\" >";
	if (strlen($ls02) > 0) print stripslashes(mask_all(GetString($ls02, $lang_filename, $file_type)));
	print "</textarea>";
	print '</div>';
	print "</form>";
	?>
	<script language="JavaScript" type="text/javascript">
	<!--
		document.Form1.new_message.focus();
	//-->
	</script>
	<?php
}
if ($action == "save") {
	if (!isset($_POST)) $_POST = $HTTP_POST_VARS;
	
	// Post-parameters
	// $new_message is the edited message
	// $language2 is the name of the language to edit
	// $ls01 is the number of the message in english language file
	// $ls02 is the number of the message in the edited language file
	// $file_type defines which language file
	$new_message = preg_replace(array("/&amp;/","/&lt;/","/&gt;/"), array("&","<",">"), $new_message);
	$Write_Ok = WriteString($new_message, $ls01, $language2, $file_type);
	
	print "<div align=\"center\"><center>";
	
	print "<table class=\"facts_table\">";
	print "<tr>";
	print "<td class=\"facts_label03\">";
	print_text("savelang_help");
	print "</td>";
	print "</tr>";
	print "<tr>";
	print "<td class=\"facts_value\" style=\"text-align:center; \">"."(".$language_settings[$lang_filename]['gm_lang'].")"."</td>";
	print "</tr>";
	print "</table>";
	
	print "<form name=\"Form2\" method=\"post\" action=\"" .$_SERVER["SCRIPT_NAME"]. "\">";
	print "<input type=\"hidden\" name=\"lang_filename_orig\" value=\"".$lang_filename_orig."\" />";
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
		print "<td class=\"facts_value wrap\" style=\"text-align:center; color: #0000FF\" >";
		print "<strong style=\"color: red\">|</strong>".stripslashes(mask_all(GetString($ls01, $lang_filename_orig, $file_type)))."<strong style=\"color: red\">|</strong>";
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
		print "<td class=\"facts_value wrap\" style=\"text-align:center; color: #0000FF\" >";
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
	if (!$realtime) print "<input type=\"submit\" value=\"" . $gm_lang["close_window"] . "\"" . " onclick=\"window.opener.showchanges('&dv=".rand()."#".$anchor."'); self.close();\" />";
	else print "<input type=\"submit\" value=\"" . $gm_lang["close_window"] . "\"" . " onclick=\"self.close();\" />";
	print "</td>";
	print "</tr>";
	print "</table>";
	
	print "</form>";
	print "</center></div>";
}

PrintSimpleFooter();

?>