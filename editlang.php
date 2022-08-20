<?php
/**
 * Display a diff between two language files to help in translating.
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 = 2007 Genmod Development Team
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
 * @version $Id: editlang.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

require "config.php";

$el_controller = new EditLangController();

PrintHeader($el_controller->pagetitle);

// Print the JS for the pages
print "<script language=\"JavaScript\" type=\"text/javascript\">\n";
print "<!--\n";
print "var helpWin;\n";
print "function helpPopup00(which) {\n";
print "if ((!helpWin)||(helpWin.closed)){helpWin = window.open('editlang_edit.php?' + which, '' , 'left=50, top=30, width=700, height=600, resizable=1, scrollbars=1'); helpWin.focus();}\n";
print "else helpWin.location = 'editlang_edit.php?' + which;\n";
print "return false;\n";
print "}\n";
print "function showchanges(which2) {\n";
print "\twindow.location = '".$_SERVER['SCRIPT_NAME']."?".$el_controller->query_string."'+which2;\n";
print "}\n";
print "//-->\n";
print "</script>\n";

?>
<!-- Setup the left box -->
<div id="AdminColumnLeft">
	<?php AdminFunctions::AdminLink("admin.php", GM_LANG_admin); ?>
	<?php
	if ($el_controller->action != "") AdminFunctions::AdminLink("editlang.php", GM_LANG_translator_tools);
	?>
</div>
<div id="AdminColumnMiddle">
<?php
switch ($el_controller->action) {
	case 'debug':
		?>
		<form name="debug_form" method="post" action="editlang.php">
		<input type="hidden" name="page" value="editlang" />
		<input type="hidden" name="action" value="debug" />
		<input type="hidden" name="execute" value="true" />
		<table class="NavBlockTable AdminNavBlockTable">
		<tr>
			<td colspan="2" class="NavBlockHeader AdminNavBlockHeader">
				<div class="AdminNavBlockTitle">
				<?php print GM_LANG_lang_debug; ?>
				</div>
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdminNavBlockOption">
				<?php print GM_LANG_lang_debug_use; ?>
			</td>
			<td class="NavBlockField">
				<input type="checkbox" name="DEBUG_LANG" value="yes" 
				<?php
				if (isset($_SESSION["DEBUG_LANG"])) {
					if (($_SESSION["DEBUG_LANG"]) == "yes") print "checked=\"checked\"";
				}
				?>
				/>
			</td>
		</tr>
		<tr>
			<td class="NavBlockFooter" colspan="2">
				<input  type="submit" value="<?php print GM_LANG_save;?>" />
			</td>
		</tr>
		</table>
		</form>
		<?php
		break;
	case 'edit':
		?>
		<form name="choose_form" method="get" action="<?php print $_SERVER['SCRIPT_NAME']; ?>">
			<table class="NavBlockTable AdminNavBlockTable">
			<input type="hidden" name="page" value="editlang" />
			<input type="hidden" name="action" value="edit" />
			<input type="hidden" name="execute" value="true" />
			<tr>
				<td colspan="3" class="NavBlockHeader AdminNavBlockHeader">
					<div class="AdminNavBlockTitle">
					<?php print GM_LANG_edit_lang_utility; ?>
					</div>
				</td>
			</tr>
			<tr>
				<td class="NavBlockColumnHeader">
					<div class="HelpIconContainer">
						<?php PrintHelpLink("language_to_edit_help", "qm", "language_to_edit");?>
					</div>
					<div class="AdminNavBlockOptionText">
						<?php print GM_LANG_language_to_edit; ?>
					</div>
				</td>
				<td class="NavBlockColumnHeader">
					<div class="HelpIconContainer">
						<?php PrintHelpLink("file_to_edit_help", "qm", "file_to_edit");?>
					</div>
					<div class="AdminNavBlockOptionText">
						<?php print GM_LANG_file_to_edit; ?>
					</div>
				</td>
				<td class="NavBlockColumnHeader">
					<div class="HelpIconContainer">
						<?php PrintHelpLink("hide_translated_help", "qm", "hide_translated");?>
					</div>
					<div class="AdminNavBlockOptionText">
						<?php print GM_LANG_hide_translated; ?>
					</div>
				</td>
			</tr>
			<tr>
				<td class="NavBlockField"> <?php
					print "<select name=\"language2\">";
					foreach ($el_controller->sorted_langs as $key => $value){
						print "\n\t\t\t<option value=\"$key\"";
						if ($key == $el_controller->language2) print " selected=\"selected\"";
						print ">".constant("GM_LANG_lang_name_".$key)."</option>";
					}
					?>
					</select>
				</td>
				<td class="NavBlockField"> <?php
					print "<select name=\"file_type\">";
						print "\n\t\t\t<option value=\"lang\"";
						if ($el_controller->file_type == "lang") print " selected=\"selected\"";
						print ">".GM_LANG_comparing_main."</option>";
						
						print "\n\t\t\t<option value=\"help_text\"";
						if ($el_controller->file_type == "help_text") print " selected=\"selected\"";
						print ">" . GM_LANG_comparing_helptext . "</option>";
	
						print "\n\t\t\t<option value=\"facts\"";
						if ($el_controller->file_type == "facts") print " selected=\"selected\"";
						print ">" . GM_LANG_comparing_facts . "</option>";
						?>
					</select>
				</td>
				<td class="NavBlockField"> <?php
					print "<select name=\"hide_translated\">";
						print "<option";
						if (!$el_controller->hide_translated) print " selected=\"selected\"";
						print " value=\"";
						print "0";
						print "\">";
						print GM_LANG_no;
						print "</option>";
						print "<option";
						if ($el_controller->hide_translated) print " selected=\"selected\"";
						print " value=\"";
						print "1";
						print "\">";
						print GM_LANG_yes;
						print "</option>";
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="NavBlockFooter" colspan="3">
					<input  type="submit" value="<?php print GM_LANG_edit;?>" />
				</td>
			</tr>
		<?php
		if ($el_controller->execute) {
			?>
			<tr><td colspan="3" class="NavBlockRowSpacer">&nbsp;</td></tr>
			<tr><td class="NavBlockHeader" colspan="3"><?php print GM_LANG_listing;?>: "
			<?php
			switch ($el_controller->file_type) {
				case "lang":
					print constant("GM_LANG_lang_name_english") . "\" ";
					print GM_LANG_and . " \"";
					print constant("GM_LANG_lang_name_".$el_controller->language2);
					// read the english lang.en.txt file into array
					$english_language_array = array();
					$english_language_array = LanguageFunctions::LoadEnglish(true,false,false);
					// read the chosen lang.xx.txt file into array
					$new_language_array = array();
					$new_language_array = LanguageFunctions::LoadLanguage($el_controller->language2, true);
					break;
				case "help_text":
					print constant("GM_LANG_lang_name_english") . "\" ";
					print GM_LANG_and . " \"";
					print constant("GM_LANG_lang_name_".$el_controller->language2);
					// read the english lang.en.txt file into array
					$english_language_array = array();
					$english_language_array = LanguageFunctions::LoadEnglish(true, true);
					// read the chosen lang.xx.txt file into array
					$new_language_array = array();
					$new_language_array = LanguageFunctions::LoadLanguage($el_controller->language2, true, true);
					break;
				case "facts":
					print constant("GM_LANG_lang_name_english") . "\" ";
					print GM_LANG_and . " \"";
					print constant("GM_LANG_lang_name_".$el_controller->language2);
					// read the english lang.en.txt file into array
					$english_language_array = array();
					$english_language_array = LanguageFunctions::LoadEnglishFacts(true);
					// read the chosen lang.xx.txt file into array
					$new_language_array = array();
					$new_language_array = AdminFunctions::LoadFacts($el_controller->language2);
					break;					
			}
			print "\"<br />\n";
			print GM_LANG_contents . ":</td></tr>";
			$lastfound = (-1);
			$counter = 0;
			foreach ($english_language_array as $string => $value) {
				$dummy_output = "<tr><td class=\"NavBlockLabel AdminNavBlockLabel\">";
				$dummy_output .= $string;
				$dummy_output .= "</td><td colspan=\"2\" class=\"NavBlockField\">";
				$dummy_output .= "\n<a name=\"a1_".$counter."\"></a>\n";
				
				$val = stripslashes(AdminFunctions::Mask_all($value));
				if ($val == "") {
					$dummy_output .= "<span class=\"EditLangNoDestText\">" . str_replace("#LANGUAGE_FILE#", $gm_language[$el_controller->language1], GM_LANG_message_empty_warning) . "</span>";
				}
				else $dummy_output .= "<div class=\"EditLangTranOrgText\">" . $val . "</div>";
				
				$dummy_output .= "<div class=\"NavBlockRowSpacer\">&nbsp;</div>";
				$dummy_output .= "<div class=\"EditLangTranDestText\">";
				$new_counter = 0;
				$found = false;
				// NOTE: Get the translated text
				if (isset($new_language_array[$string])) {
					$dDummy =  $new_language_array[trim($string)];
					$dummy_output .= "<a href=\"#\" onclick=\"return helpPopup00('" . "ls01=" . $string . "&amp;ls02=" . $string . "&amp;language2=" . $el_controller->language2 . "&amp;file_type=" . $el_controller->file_type . "&amp;" . session_name() . "=" . session_id() . "&amp;anchor=a1_" . $counter . "');\">";
					$dum = stripslashes(AdminFunctions::Mask_all($dDummy));
					$dummy_output .= $dum;
					if ($dum == "") {
						$dummy_output .= "<span class=\"EditLangNoDestText\">" . str_replace("#LANGUAGE_FILE#", $gm_language[$el_controller->language2], GM_LANG_message_empty_warning) . "</span>";
					}
					$dummy_output .= "</a>";
					$lastfound = $new_counter;
					$found = true;
				}
				else {
					$dummy_output .= "<a class=\"EditLangNoTranDestText\" href=\"#\" onclick=\"return helpPopup00('" . "ls01=" . $string . "&amp;ls02=" . (0 - intval($lastfound) - 1) . "&amp;language2=" . $el_controller->language2 . "&amp;file_type=" . $el_controller->file_type . "&amp;anchor=a1_" . $counter . "');\">";
					$dummy_output .= "<span class=\"EditLangNoTranDestText\">";
					if ($val == "") $dummy_output .= "&nbsp;";
					else $dummy_output .= $val;
					$dummy_output .= "</span></a>";
				}
				$new_counter++;
				
				$dummy_output .= "</td></tr>";
				if (!$el_controller->hide_translated) {
					print $dummy_output;
				}
				// NOTE: Print the untranslated strings
				else if ($el_controller->hide_translated && !$found) {
					print $dummy_output;
				}
				$counter++;
//				print '</div>';
			}
		} ?>
		</table>
		</form>
		<?php
		break;
	case 'export':
		?>
		<form name="export_form" method="get" action="editlang.php">
		<input type="hidden" name="page" value="editlang" />
		<input type="hidden" name="action" value="export" />
		<input type="hidden" name="execute" value="true" />
		<table class="NavBlockTable AdminNavBlockTable">
		<tr>
			<td colspan="2" class="NavBlockHeader AdminNavBlockHeader">
				<div class="AdminNavBlockTitle">
				<?php print GM_LANG_export_lang_utility; ?>
				</div>
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdminNavBlockOption">
				<div class="HelpIconContainer">
					<?php PrintHelpLink("language_to_export_help", "qm", "language_to_export"); ?>
				</div>
				<div class="AdminNavBlockOptionText">
					<?php print GM_LANG_language_to_export; ?>
				</div>
			</td>
			<td class="NavBlockField">
				<select name="language2">
				<?php
				foreach ($el_controller->sorted_langs as $key => $value){
					if ($language_settings[$key]["gm_lang_use"]) {
						print "\n\t\t\t<option value=\"$key\"";
						if ($key == $el_controller->language2) print " selected=\"selected\"";
						print ">".constant("GM_LANG_lang_name_".$key)."</option>";
					}
				}
				?>
				</select>
			</td>
		</tr>
		
		<?php
		if ($el_controller->execute) {
			print "<tr><td colspan=\"2\" class=\"NavBlockColumnHeader AdminNavBlockColumnHeader\">";
				print GM_LANG_export_results;
			print "</td></tr>";
			print "<tr><td class=\"NavBlockLabel AdminNavBlockOption\" colspan=\"2\">";
				$data = "";
				$data_help = "";
				$storeerror = false;
				$storelang = LanguageFunctions::LoadLanguage($el_controller->language2, true) + LanguageFunctions::LoadLanguage($el_controller->language2, true, true);
				ksort($storelang);
				foreach($storelang as $string => $value) {
					if (substr($string, -5) == "_help") $data_help .= "\"".$string."\";\"".$value."\"\r\n";
					else $data .= "\"".$string."\";\"".$value."\"\r\n";
				}
				if (!empty($data)) {
					if (!$handle = fopen("languages/lang.".$el_controller->lang_shortcut.".txt", "w")) {
						print str_replace("#lang_filename#", "languages/lang.".$el_controller->lang_shortcut.".txt", GM_LANG_no_open)."<br />";
						$storeerror = true;
						WriteToLog("EditLang-&gt; Can't open file languages/lang.".$el_controller->lang_shortcut.".txt", "E", "S");
					}
					else {
						if (fwrite($handle, $data)) {
							WriteToLog("EditLang-&gt; ".GM_LANG_editlang_lang_export_success, "I", "S");
							print GM_LANG_editlang_lang_export_success."<br />";
						}
						else {
							$storeerror = true;
							WriteToLog("EditLang-&gt; ".GM_LANG_editlang_lang_export_no_success, "E", "S");
							print "<span class=\"Error\">".GM_LANG_editlang_lang_export_no_success."</span><br />";
						}
						fclose($handle);            
					}
				}
				else print "<span class=\"Error\">".GM_LANG_lang_not_stored."</span><br />";
				if (!empty($data_help)) {
					if (!$handle_help = fopen("languages/help_text.".$el_controller->lang_shortcut.".txt", "w")) {
						print str_replace("#lang_filename#", "languages/help_text.".$el_controller->lang_shortcut.".txt", GM_LANG_no_open)."<br />";
						$storeerror = true;
						WriteToLog("EditLang-&gt; Can't open file languages/help_text.".$el_controller->lang_shortcut.".txt", "E", "S");
					}
					else {
						if (fwrite($handle_help, $data_help)) {
							WriteToLog("EditLang-&gt; ".GM_LANG_editlang_help_export_success, "I", "S");
							print GM_LANG_editlang_help_export_success."<br />";
						}
						else {
							$storeerror = true;
							WriteToLog("EditLang-&gt; ".GM_LANG_editlang_help_no_export_success, "E", "S");
							print "<span class=\"Error\">".GM_LANG_editlang_help_no_export_success."</span><br />";
						}
						fclose($handle_help);
					}
				}
				else print "<span class=\"Error\">".GM_LANG_lang_help_not_stored."</span><br />";
				
				$data = "";
				$storefacts = AdminFunctions::LoadFacts($el_controller->language2);
				ksort($storefacts);
				foreach($storefacts as $string => $value) {
					$data .= "\"".$string."\";\"".$value."\"\r\n";
				}
				if (!empty($data)) {
					if (!$handle = fopen("languages/facts.".$el_controller->lang_shortcut.".txt", "w")) {
						print str_replace("#lang_filename#", "languages/facts.".$el_controller->lang_shortcut.".txt", GM_LANG_no_open)."<br />";
						$storeerror = true;
						WriteToLog("EditLang-&gt; Can't open file languages/facts.".$el_controller->lang_shortcut.".txt", "E", "S");
					}
					else {
						if (fwrite($handle, $data)) {
							WriteToLog("EditLang-&gt; ".GM_LANG_editlang_facts_export_success, "I", "S");
							print GM_LANG_editlang_facts_export_success."<br />";
						}
						else {
							$storeerror = true;
							WriteToLog("EditLang-&gt; ".GM_LANG_editlang_facts_export_no_success, "E", "S");
							print "<span class=\"Error\">".GM_LANG_editlang_facts_export_no_success."</span><br />";
						}
						fclose($handle);            
					}
				}
				else print "<span class=\"Error\">".GM_LANG_lang_facts_not_stored."</span><br />";
				if (!$storeerror) {
					$sql = "UPDATE ".TBLPREFIX."lang_settings SET ls_translated = '0', ls_md5_lang = '".md5_file("languages/lang.".$language_settings[$el_controller->language2]["lang_short_cut"].".txt")."', ls_md5_help = '".md5_file("languages/help_text.".$language_settings[$el_controller->language2]["lang_short_cut"].".txt")."', ls_md5_facts = '".md5_file("languages/facts.".$language_settings[$el_controller->language2]["lang_short_cut"].".txt")."' WHERE ls_gm_langname='".$el_controller->language2."'";
					$res = NewQuery($sql);
				}
			print "</td></tr>";
		}
		?>
		<tr>
			<td class="NavBlockFooter" colspan="2">
				<input  type="submit" value="<?php print GM_LANG_export;?>" />
			</td>
		</tr>
		</table>
		</form>
		<?php
		break;
	case 'compare':
		?>
		<form name="langdiff_form" method="get" action="editlang.php">
		<input type="hidden" name="page" value="editlang" />
		<input type="hidden" name="action" value="compare" />
		<input type="hidden" name="execute" value="true" />
		<table class="NavBlockTable AdminNavBlockTable">
		<tr>
			<td colspan="2" class="NavBlockHeader AdminNavBlockHeader">
				<div class="AdminNavBlockTitle">
				<?php print GM_LANG_compare_lang_utility; ?>
				</div>
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdminNavBlockOption">
				<div class="HelpIconContainer">
					<?php PrintHelpLink("new_language_help", "qm", "new_language");?>
				</div>
				<div class="AdminNavBlockOptionText">
					<?php
					print GM_LANG_new_language;
					?>
				</div>
			</td>
			<td class="NavBlockField"> <?php
				print "<select name=\"language1\">";
				foreach ($el_controller->sorted_langs as $key => $value) {
					if ($language_settings[$key]["gm_lang_use"]) {
						print "\n\t\t\t<option value=\"$key\"";
						if ($key == $el_controller->language1) print " selected=\"selected\"";
						print ">".constant("GM_LANG_lang_name_".$key)."</option>";
					}
				}
				?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdminNavBlockOption">
				<div class="HelpIconContainer">
					<?php PrintHelpLink("old_language_help", "qm", "old_language");?>
				</div>
				<div class="AdminNavBlockOptionText">
					<?php
					print GM_LANG_old_language;
					?>
				</div>
			</td>
			<td class="NavBlockField"> <?php
				print "<select name=\"language2\">";
				foreach ($el_controller->sorted_langs as $key => $value) {
					if ($language_settings[$key]["gm_lang_use"]) {
						print "\n\t\t\t<option value=\"$key\"";
						if ($key == $el_controller->language2) print " selected=\"selected\"";
						print ">".constant("GM_LANG_lang_name_".$key)."</option>";
					}
				}
				?>
				</select>
			</td>
		</tr>
		<?php
		if ($el_controller->execute) {
			?>
			<tr>
				<td class="NavBlockRowSpacer">
					&nbsp;
				</td>
			</tr>
			<tr>
				<td colspan="2" class="NavBlockHeader AdminNavBlockHeader">
					<?php print GM_LANG_comparing_main; ?>
				</td>
			</tr>
			<?php $el_controller->ShowLanguageCompare(LanguageFunctions::LoadLanguage($el_controller->language1, true), LanguageFunctions::LoadLanguage($el_controller->language2, true)); ?>
			<tr>
				<td class="NavBlockRowSpacer">
					&nbsp;
				</td>
			</tr>
			<tr>
				<td colspan="2" class="NavBlockHeader AdminNavBlockHeader">
					<?php print GM_LANG_comparing_facts;?>
				</td>
			</tr>
			<?php $el_controller->ShowLanguageCompare(AdminFunctions::LoadFacts($el_controller->language1), AdminFunctions::LoadFacts($el_controller->language2), true); ?>
			<tr>
				<td class="NavBlockRowSpacer">
					&nbsp;
				</td>
			</tr>
			<tr>
				<td colspan="2" class="NavBlockHeader AdminNavBlockHeader">
					<?php print GM_LANG_comparing_helptext;?>
				</td>
			</tr>
			<?php
			$el_controller->langhelp = LanguageFunctions::LoadEnglish(true, true, true);
			$el_controller->ShowLanguageCompare(LanguageFunctions::LoadLanguage($el_controller->language1, true, true), LanguageFunctions::LoadLanguage($el_controller->language2, true, true), false, true);
			$el_controller->langhelp = array();
			?>
			<?php
		}
		?>
		<tr>
			<td class="NavBlockFooter" colspan="2">
				<input  type="submit" value="<?php print GM_LANG_compare; ?>" />
			</td>
		</tr>
		</table>
		</form>
		<?php
		break;
	default:
		?>
		<table class="NavBlockTable AdminNavBlockTable">
		<?php
		$menu = new AdminMenu();
		$menu->SetBarText(GM_LANG_edit_langdiff);
		$menu->SetBarStyle("AdminNavBlockHeader");
		$menu->AddItem("help_changelanguage.php", "qm", "enable_disable_lang", "changelanguage.php", GM_LANG_enable_disable_lang, "left");
		$menu->AddItem("edit_lang_utility_help", "qm", "edit_lang_utility", "editlang.php?action=edit", GM_LANG_edit_lang_utility, "right");
		$menu->AddItem("export_lang_utility_help", "qm", "export_lang_utility", "editlang.php?action=export", GM_LANG_export_lang_utility, "left");
		$menu->AddItem("translation_forum_desc", "qm", "translation_forum", "https://www.sourceforge.net/projects/genmod\" target=\"_blank", GM_LANG_translation_forum, "right");
		$menu->AddItem("compare_lang_utility_help", "qm", "compare_lang_utility", "editlang.php?action=compare", GM_LANG_compare_lang_utility, "left");
		$menu->AddItem("lang_debug_help", "qm", "lang_debug", "editlang.php?action=debug", GM_LANG_lang_debug, "right");
		$menu->AddItem("bom_check_help", "qm", "bom_check", "editlang.php?action=bom", GM_LANG_bom_check, "left");
		$menu->PrintItems();
		if ($el_controller->action == "bom") {
			print "<tr><td  colspan=\"2\" class=\"NavBlockRowSpacer\">&nbsp;</td></tr>";
			print "<tr><td colspan=\"2\" class=\"NavBlockColumnHeader AdminNavBlockColumnHeader\">".GM_LANG_bom_check."</td></tr>";
			print "<tr><td colspan=\"2\" class=\"NavBlockLabel AdminNavBlockLabel\">".AdminFunctions::CheckBom()."</td></tr>";
		}
		?>
		</table>
		<?php
		break;
}
print "</div>";
PrintFooter();
?>