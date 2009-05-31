<?php
/**
 * Display a diff between two language files to help in translating.
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
 * @subpackage Languages
 * @version $Id: changelanguage.php,v 1.9 2006/02/19 18:40:23 roland-d Exp $
 */
 
/**
 * Inclusion of the configuration file
*/
require "config.php";

/**
 * Inclusion of the language files
*/
// require $GM_BASE_DIRECTORY . $confighelpfile["english"];
// if (file_exists($GM_BASE_DIRECTORY . $confighelpfile[$LANGUAGE])) require $GM_BASE_DIRECTORY . $confighelpfile[$LANGUAGE];

//-- make sure that they have admin status before they can use this page
//-- otherwise have them login again
$uname = $gm_username;
if (empty($uname)) {
	header("Location: login.php?url=changelanguage.php");
	exit;
}

if (!isset($action) or $action=="") $action="editold";

switch ($action) {
	case "addnew" :
		$helpindex = "add_new_language_help";
		print_header($gm_lang["add_new_language"]); 
		break;

	case "editold" :
	default :
		print_header($gm_lang["edit_lang_utility"]);
}

print "<script language=\"JavaScript\" type=\"text/javascript\">\n";
print "<!--\n";
print "var helpWin;\n";
print "function showchanges() {\n";
print "\twindow.location = '$PHP_SELF';\n";
print "}\n";
print "function helpPopup03(which) {\n";
// print "alert(which);";
// print "if ((!helpWin)||(helpWin.closed)){helpWin = window.open('editlang_edit_settings.php?' + which + '&new_shortcut=' + document.new_lang_form.new_shortcut.value, '' , 'left=50, top=30, width=700, height=600, resizable=1, scrollbars=1'); helpWin.focus();}\n";
// print "else helpWin.location = 'editlang_edit_settings.php?' + which + '&new_shortcut=' + document.new_lang_form.new_shortcut.value;\n";
print "location.href = 'editlang_edit_settings.php?' + which + '&new_shortcut=' + document.new_lang_form.new_shortcut.value;\n";
print "return false;\n";
print "}\n";
print "//-->\n";
print "</script>\n";

// Create array with configured languages in gedcoms and users
$configuredlanguages = array();

// Read GEDCOMS configuration and collect language data
$gedsave = $GEDCOM;
foreach ($GEDCOMS as $key => $value) {
	ReadGedcomConfig($value["gedcom"]);
	if (!isset($configuredlanguages["gedcom"][$LANGUAGE][$key])) $configuredlanguages["gedcom"][$LANGUAGE][$key] = TRUE;
}
// Restore the current settings
$GEDCOM = $gedsave;
ReadGedcomConfig($GEDCOMS[$GEDCOM]["gedcom"]);

// Read user configuration and collect language data
$users = getUsers("username","asc");
foreach($users as $username=>$user) {
	if (!isset($configuredlanguages["users"][$user["language"]][$username])) $configuredlanguages["users"][$user["language"]][$username] = TRUE;
}

// Sort the Language table into localized language name order
foreach ($gm_language as $key => $value){
	$d_LangName = "lang_name_".$key;
	$Sorted_Langs[$key] = $gm_lang[$d_LangName];
}
asort($Sorted_Langs);

// Split defined languages into active and inactive
$split_langs_active = array();
$split_langs_inactive = array();
foreach ($Sorted_Langs as $key => $value){
	if ($gm_lang_use["$key"]) {
		$split_langs_active[count($split_langs_active)+1] = $key; 
	}
	else {
		$split_langs_inactive[count($split_langs_inactive)+1] = $key;
	}
}

$active = count($split_langs_active);
$inactive = count($split_langs_inactive);
$maxlines = max($active, $inactive);

/* Language File Settings Mask */

print "<div class=\"center\">";

//-- Choose the language you want to edit the settings of
print "<table class=\"center $TEXT_DIRECTION\">";

switch ($action) {
	case "addnew" :
		print "<tr><td class=\"topbottombar\" colspan=\"2\">";
		print $gm_lang["add_new_language"];
		print "</td></tr>";

		require($GM_BASE_DIRECTORY . "includes/lang_codes_std.php");
		print "<form name=\"new_lang_form\" method=\"get\" action=\"$SCRIPT_NAME\">";
      	print "<input type=\"hidden\" name=\"" . session_name() . "\" value=\"" . session_id() . "\" />";
      	print "<input type=\"hidden\" name=\"action\" value=\"new_lang\" />";
		print "<input type=\"hidden\" name=\"execute\" value=\"true\" />";
		print "<tr><td class=\"shade1\"><select name=\"new_shortcut\">\n";

		asort($lng_codes);		// Sort the language codes table into language name order

		foreach ($lng_codes as $key => $value) {
			$showLang = true;
			foreach ($lang_short_cut as $key02=>$value) {
				if ($value == $key) {		// This language is already in GM
					$showLang = false;
					break;
				}
			}
			if ($showLang) {
				print "<option value=\"$key\"";
			  	print ">".$lng_codes[$key][0]."</option>\n";
			}
		}
		print "</select>\n\n";
		print_help_link("add_new_language_help", "qm");
		print "</td>";
		print "<td class=\"shade1\"><input type=\"submit\" value=\"" . $gm_lang["add_new_lang_button"] . "\" onclick=\"return helpPopup03('" . "action=new_lang" . "&amp;" . session_name() . "=" . session_id() . "'); \" /></td></tr>";
	    print "</td></tr>";
	   	print "<tr><td class=\"shade1 center\" colspan=\"2\"><a href=\"editlang.php\"><b>";
	    print $gm_lang["lang_back"];
	    print "</b></a></td></tr>";
		print "</table><br /></form>";
		$USERLANG = $LANGUAGE;
		break;

	case "editold" :
	default :
		print "<tr><td class=\"topbottombar\" colspan=\"7\">";
		print $gm_lang["config_lang_utility"];
		print_help_link("config_lang_utility_help", "qm");
		print "</td></tr>";

		print "<form name=\"lang_config_form\" method=\"get\" action=\"$PHP_SELF\">";
		print "<input type=\"hidden\" name=\"" . session_name() . "\" value=\"" . session_id() . "\" />";
		print "<input type=\"hidden\" name=\"action\" value=\"config_lang\" />";
		print "<tr>";


		// Column headings, left set
		print "<td class=\"shade1 center\">";
		print $gm_lang["lang_language"];
		print "</td>";
		
		print "<td class=\"shade1 center\">";
		print $gm_lang["active"];
		print "</td>";
		
		print "<td class=\"shade1 center\">";
		print $gm_lang["edit_settings"];
		print "</td>";
		
		// Separator
		print "<td class=\"shade1 center\">" . "&nbsp;" . "</td>";
		
		// Column headings, right set
		print "<td class=\"shade1 center\">";
		print $gm_lang["lang_language"];
		print "</td>";
		
		print "<td class=\"shade1 center\">";
		print $gm_lang["active"];
		print "</td>";
		
		print "<td class=\"shade1 center\">";
		print $gm_lang["edit_settings"];
		print "</td>";
		
		// End of row
		print "</tr>\n";

		// Print the Language table in sorted name order
		for ($i=1; $i<=$maxlines; $i++) {
			print "<form name=\"activelanguage\">";
			print "<tr>";
			// Left 3 columns: Active language
			$value = "";
			if ($i <= $active) $value = $split_langs_active[$i];
		
			if ($value == "") {
				print "<td class=\"shade1\">&nbsp;</td><td class=\"shade1\">&nbsp;</td><td class=\"shade1\">&nbsp;</td>";
			} else { 
				$d_LangName = "lang_name_" . $value;
				print "<td class=\"shade1 center\">";
					print $gm_lang[$d_LangName];
				print "</td>";
				print "<td class=\"shade1 center\">";
					print "<input";
					if (array_key_exists($value, $configuredlanguages["gedcom"]) or array_key_exists($value, $configuredlanguages["users"])) print " disabled";
					print " type=\"checkbox\" value=\"$value\" checked=\"checked\" onclick=\"enabledisablelanguage('$value');\" />";
				print "</td>";
				print "<td class=\"shade1 center\">";
					print "<a href=\"editlang_edit_settings.php?ln=" . $value . "\">";
					print $gm_lang["lang_edit"] . "</a>";
				print "</td>";
			}
			
			// Middle column: Separator
			print "<td class=\"shade1\">" . "&nbsp;" . "</td>";
			
			// Right 3 columns: Inactive language
			$value = "";
			if ($i <= $inactive) $value = $split_langs_inactive[$i];
		
			if ($value == "") {
				print "<td class=\"shade1\">&nbsp;</td><td class=\"shade1\">&nbsp;</td><td class=\"shade1\">&nbsp;</td>";
			} else { 
				$d_LangName = "lang_name_" . $value;
				print "<td class=\"shade1 center\">";
					print $gm_lang[$d_LangName];
				print "</td>";
				print "<td class=\"shade1 center\">";
					print "<input type=\"checkbox\" value=\"$value\" onclick=\"enabledisablelanguage('$value');\"/>"; 
				print "</td>";
				print "<td class=\"shade1 center\">";
					print "<a href=\"editlang_edit_settings.php?ln=" . $value . "\">";
					print $gm_lang["lang_edit"] . "</a>";
				print "</td>";
			}
			print "</tr>";
			print "</form>";
		}
		print "</form>";
		$USERLANG = $LANGUAGE;
		print "<tr>";
		print "<td class=\"topbottombar\" colspan=\"7\">";
			print $gm_lang["configured_languages"];
		print "</td>";
		print "</tr>";
		print "<tr>";
		print "<td class=\"shade1\" colspan=\"3\" rowspan=\"".count($configuredlanguages["gedcom"])."\" valign=\"top\">";
			print $gm_lang["current_gedcoms"];
		print "</td>";
		foreach ($configuredlanguages["gedcom"] as $key => $value) {
			if (!isset($currentkey)) $currentkey = $key;
			if ($currentkey != $key) {
				print "</td></tr><tr><td class=\"shade1\" colspan=\"2\" valign=\"top\">";
				$currentkey = $key;
			}
			else print "<td class=\"shade1\" colspan=\"2\" valign=\"top\">";
			
			// Print gedcom names
			foreach($value as $gedcomname => $used) {
				print "<a href=\"editconfig_gedcom.php?ged=".urlencode($gedcomname)."\" target=\"blank\">".$gedcomname."</a><br />";
			}
			print "</td><td class=\"shade1\" colspan=\"2\" valign=\"top\">";
			// Print language name and flag
			print "<img src=\"".$language_settings[$key]["flagsfile"]."\" class=\"brightflag\" alt=\"".$gm_lang["lang_name_".$key]."\" title=\"".$gm_lang["lang_name_".$key]."\" />&nbsp;".$gm_lang["lang_name_".$key]."<br />";
		}
		print "<tr><td  class=\"shade1\" colspan=\"5\" valign=\"top\" colspan=\"2\">".$gm_lang["users_langs"]."</td><td class=\"shade1\" colspan=\"2\">";
		foreach ($configuredlanguages["users"] as $key => $value) {
			print "<img src=\"".$language_settings[$key]["flagsfile"]."\" class=\"brightflag\" alt=\"".$gm_lang["lang_name_".$key]."\" title=\"".$gm_lang["lang_name_".$key]."\" />&nbsp;<a href=\"useradmin.php?action=listusers&amp;filter=language&amp;usrlang=".$key."\">".$gm_lang["lang_name_".$key]."</a><br />";
		}
}
print "</td></tr>";
print "</table><br />";
$LANGUAGE = $USERLANG;
print "</div>";

print_footer();
?>