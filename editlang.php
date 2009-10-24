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
 * @version $Id$
 */

class EditLang {
	
	var $name = 'EditLang';
	var $message = '';
	var $output = '';
	var $hide_translated = false;
	var $language1 = 'english';
	var $language2 = '';
	var $langhelp = array();
	var $file_type = 'lang';
	var $execute = '';
	var $debug_lang = false;
	var $query_string = '';
	var $sorted_langs = array();
	var $lang_shortcut = '';
	
	
	function EditLang(&$genmod, &$gm_lang, &$gm_username) {
//		$this->CheckUrl();
		if (!$this->CheckAccess($gm_username)) return false;
		else {
			$this->GetPageValues($genmod);
			$this->SortLanguageTable($genmod, $gm_lang);
			$this->AddHeader($genmod, $gm_lang);
			$this->ShowMenu($genmod, $gm_lang);
			echo '<div id="content">';
			// Inclusion of the language editing functions
			require $genmod['gm_base_directory'] . "includes/functions/functions_editlang.php";
			switch ($genmod['action']) {
				case 'debug':
					if (!empty($this->execute)) {
						if (isset($_REQUEST["DEBUG_LANG"])) $_SESSION["DEBUG_LANG"] = $_REQUEST["DEBUG_LANG"];
						else $_SESSION["DEBUG_LANG"] = "no";
						$this->debug_lang = $_SESSION["DEBUG_LANG"];
					}
					$this->DebugHelptext($gm_lang);
					break;
				case 'edit':
					$this->EditLanguage($genmod, $gm_lang);
					break;
				case 'export':
					$this->ExportLanguage($genmod, $gm_lang);
					break;
				case 'compare':
					$this->CompareLanguage($genmod, $gm_lang);
					break;
				default:
					?>
					<div class="admin_topbottombar">
					<?php
					print "<h3>".$gm_lang["edit_langdiff"]."</h3>";
					?>
					</div>
					<div class="admin_item_box">
						<div class="admin_item_left"><div class="helpicon"><?php print_help_link("help_changelanguage.php", "qm", "enable_disable_lang"); ?></div><div class="description"><a href="changelanguage.php"><?php print $gm_lang["enable_disable_lang"];?></a>
						</div></div>
						<div class="admin_item_right"><div class="helpicon"><?php print_help_link("edit_lang_utility_help", "qm", "edit_lang_utility"); ?></div><div class="description"><a href="editlang.php?action=edit"><?php print $gm_lang["edit_lang_utility"];?></a></div></div>
						<div class="admin_item_left"><div class="helpicon"><?php print_help_link("export_lang_utility_help", "qm", "export_lang_utility"); ?></div><div class="description"><a href="editlang.php?action=export"><?php print $gm_lang["export_lang_utility"];?></a></div></div>
						<div class="admin_item_right"><div class="helpicon"><?php print_help_link("translation_forum_desc", "qm", "translation_forum"); ?></div><div class="description"><a href="http://www.genmod.net/forum/viewforum.php?f=4" target="_blank" ><?php print $gm_lang["translation_forum"];?></a></div></div>
						<div class="admin_item_left"><div class="helpicon"><?php print_help_link("compare_lang_utility_help", "qm", "compare_lang_utility"); ?></div><div class="description"><a href="editlang.php?action=compare"><?php print $gm_lang["compare_lang_utility"];?></a></div></div>
						<div class="admin_item_right"><div class="helpicon"><?php print_help_link("lang_debug_help", "qm", "lang_debug"); ?></div><div class="description"><a href="editlang.php?action=debug"><?php print $gm_lang["lang_debug"];?></a></div></div>
						<div class="admin_item_left"><div class="helpicon"><?php print_help_link("bom_check_help", "qm", "bom_check"); ?></div><div class="description"><a href="editlang.php?action=bom"><?php print $gm_lang["bom_check"];?></a></div></div>
					</div>
					<?php
					if ($genmod['action'] == "bom") {
						echo "<div class=\"shade2 center\">".$gm_lang["bom_check"]."</div>";
						echo "<div class=\"shade1 ltr\">".CheckBom()."</div>";
					}
					break;
			}
			echo '</div></div>';
			$this->AddFooter();
		}
	}
	
	function CheckUrl() {
		// Do not allow direct access to the script
//		if ($_SERVER['SCRIPT_NAME'] == '/editlang.php') header('Location: editlang.php');
	}
	
	function CheckAccess($gm_user) {
		global $gm_user;
		
		// If no admin, always search in user help
		if (!$gm_user->UserIsAdmin()) return false;
		else return true;
	}
	
	function AddHeader(&$genmod, &$gm_lang) {
		switch ($genmod['action']){
			case "edit": 
				PrintHeader($gm_lang["edit_lang_utility"]);
				break;
			case "export": 
				PrintHeader($gm_lang["export_lang_utility"]);
				break;
			case "compare": 
				PrintHeader($gm_lang["compare_lang_utility"]); 
				break;
			default: 
				PrintHeader($gm_lang["edit_langdiff"]); 
				break;
		}
		
		echo "<script language=\"JavaScript\" type=\"text/javascript\">\n";
		echo "<!--\n";
		echo "var helpWin;\n";
		echo "function helpPopup00(which) {\n";
		echo "if ((!helpWin)||(helpWin.closed)){helpWin = window.open('editlang_edit.php?' + which, '' , 'left=50, top=30, width=700, height=600, resizable=1, scrollbars=1'); helpWin.focus();}\n";
		echo "else helpWin.location = 'editlang_edit.php?' + which;\n";
		echo "return false;\n";
		echo "}\n";
		echo "function showchanges(which2) {\n";
		echo "\twindow.location = '".$_SERVER['SCRIPT_NAME']."?".$this->query_string."'+which2;\n";
		echo "}\n";
		echo "//-->\n";
		echo "</script>\n";
	}
	
	function AddFooter() {
		if ($this->message != "") echo "<div class=\"shade2 center\">".$this->message."</div>";
		if ($this->output != "") echo "<div class=\"shade1 ltr\">".$this->output."</div>";
		PrintFooter();
	}
	
	function GetPageValues(&$genmod) {
		if (isset($_REQUEST['hide_translated'])) $this->hide_translated = $_REQUEST['hide_translated'];
		if (isset($_REQUEST['language1'])) $this->language1 = $_REQUEST['language1'];
		if (isset($_REQUEST['language2'])) $this->language2 = $_REQUEST['language2'];
		else $this->language2 = $genmod['language'];
		if (isset($_REQUEST['file_type'])) $this->file_type = $_REQUEST['file_type'];
		if (isset($_REQUEST['execute'])) $this->execute = $_REQUEST['execute'];
		if (isset($genmod['language_settings'][$this->language2]["lang_short_cut"])) $this->lang_shortcut = $genmod['language_settings'][$this->language2]["lang_short_cut"];
		$this->query_string = preg_replace("/&amp;/", "&", $genmod['query_string']);
		$this->query_string = preg_replace("/&&/", "&", $this->query_string);
		if (strpos($this->query_string,"&dv=")) $this->query_string = substr($this->query_string,0,strpos($this->query_string,"&dv="));
	}
	
	function SortLanguageTable(&$genmod, &$gm_lang) { 
		// Sort the Language table into localized language name order
		// NB we can only work on the active languages, inactive ones are not in the DB!
		foreach ($genmod['gm_language'] as $key => $value){
			if ($genmod['language_settings'][$key]["gm_lang_use"]) {
				$d_LangName = "lang_name_".$key;
				$this->sorted_langs[$key] = $gm_lang[$d_LangName];
			}
		}
		asort($this->sorted_langs);
	}
	
	function ShowMenu(&$genmod, &$gm_lang) {
		?>
		<!-- Setup the left box -->
		<div id="admin_genmod_left">
			<div class="admin_link"><a href="admin.php"><?php print $gm_lang["admin"];?></a></div>
			<?php
			if ($genmod['action'] != "") print "<div class=\"admin_link\"><a href=\"editlang.php\">".$gm_lang["translator_tools"]."</a></div>";
			?>
		</div>
		<?php
	}
	
	function EditLanguage(&$genmod, &$gm_lang) {
		?>
		<form name="choose_form" method="get" action="<?php print $_SERVER['SCRIPT_NAME']; ?>">
			<input type="hidden" name="page" value="editlang" />
			<input type="hidden" name="action" value="edit" />
			<input type="hidden" name="execute" value="true" />
			<div class="admin_topbottombar">
				<?php print $gm_lang["edit_lang_utility"]; ?>
			</div>
			<div class="admin_item_box">
				<div class="width30 choice_left">
					<div class="helpicon">
						<?php print_help_link("language_to_edit_help", "qm", "language_to_edit");?>
					</div>
					<div class="description">
						<?php
						print $gm_lang["language_to_edit"]."<br />";
						print "<select name=\"language2\">";
						foreach ($this->sorted_langs as $key => $value){
							print "\n\t\t\t<option value=\"$key\"";
							if ($key == $this->language2) print " selected=\"selected\"";
							print ">".$gm_lang["lang_name_".$key]."</option>";
						}
						?>
						</select>
					</div>
				</div>
				<div class="width30 choice_middle">
					<div class="helpicon">
						<?php print_help_link("file_to_edit_help", "qm", "file_to_edit");?>
					</div>
					<div class="description">
						<?php
						print $gm_lang["file_to_edit"]."<br />";
						print "<select name=\"file_type\">";
						print "\n\t\t\t<option value=\"lang\"";
						if ($this->file_type == "lang") print " selected=\"selected\"";
						print ">".$gm_lang["comparing_main"]."</option>";
						
						print "\n\t\t\t<option value=\"help_text\"";
						if ($this->file_type == "help_text") print " selected=\"selected\"";
						print ">" . $gm_lang["comparing_helptext"] . "</option>";
						
						print "\n\t\t\t<option value=\"facts\"";
						if ($this->file_type == "facts") print " selected=\"selected\"";
						print ">" . $gm_lang["comparing_facts"] . "</option>";
						?>
						</select>
					</div>
				</div>
				<div class="width30 choice_middle">
					<div class="helpicon">
						<?php print_help_link("hide_translated_help", "qm", "hide_translated");?>
					</div>
					<div class="description">
						<?php
						print $gm_lang["hide_translated"]."<br />";
						print "<select name=\"hide_translated\">";
						print "<option";
						if (!$this->hide_translated) print " selected=\"selected\"";
						print " value=\"";
						print "0";
						print "\">";
						print $gm_lang["no"];
						print "</option>";
						print "<option";
						if ($this->hide_translated) print " selected=\"selected\"";
						print " value=\"";
						print "1";
						print "\">";
						print $gm_lang["yes"];
						print "</option>";
						?>
						</select>
					</div>
				</div>
			</div>
			<div class="admin_item_box">
				<div class="center">
					<input  type="submit" value="<?php print $gm_lang["edit"];?>" />
				</div>
			</div>
		</form>
		<?php
		if ($this->execute) {
			?>
			<div class="topbottombar subheaders"><?php print $gm_lang["listing"];?>: "
			<?php
			switch ($this->file_type) {
				case "lang":
					print $gm_lang["lang_name_english"] . "\" ";
					print $gm_lang["and"] . " \"";
					print $gm_lang["lang_name_".$this->language2];
					// read the english lang.en.php file into array
					$english_language_array = array();
					$english_language_array = LoadEnglish(true,false,false);
					// read the chosen lang.xx.php file into array
					$new_language_array = array();
					$new_language_array = LoadLanguage($this->language2, true);
					break;
				case "help_text":
					print $gm_lang["lang_name_english"] . "\" ";
					print $gm_lang["and"] . " \"";
					print $gm_lang["lang_name_".$this->language2];
					// read the english lang.en.php file into array
					$english_language_array = array();
					$english_language_array = LoadEnglish(true, true);
					// read the chosen lang.xx.php file into array
					$new_language_array = array();
					$new_language_array = LoadLanguage($this->language2, true, true);
					break;
				case "facts":
					print $gm_lang["lang_name_english"] . "\" ";
					print $gm_lang["and"] . " \"";
					print $gm_lang["lang_name_".$this->language2];
					// read the english lang.en.php file into array
					$english_language_array = array();
					$english_language_array = LoadEnglishFacts(true);
					// read the chosen lang.xx.php file into array
					$new_language_array = array();
					$new_language_array = LoadFacts($this->language2);
					break;					
			}
			print "\"<br />\n";
			print $gm_lang["contents"] . ":</div>";
			$lastfound = (-1);
			$counter = 0;
			$colorid = 1;
			foreach ($english_language_array as $string => $value) {
				echo '<div class="language_item_box'.$colorid.'">';
				$dummy_output = "";
				$dummy_output .= "<div class=\"original_language\">";
				$dummy_output .= $string;
				$dummy_output .= "</div>\n";
				$dummy_output .= "<div class=\"translated_language\">";
				$dummy_output .= "\n<a name=\"a1_".$counter."\"></a>\n";
				
				$val = stripslashes(mask_all($value));
				if ($val == "") {
					$dummy_output .= "<strong style=\"color: #FF0000\">" . str_replace("#LANGUAGE_FILE#", $genmod['gm_language'][$this->language1], $gm_lang["message_empty_warning"]) . "</strong>";
				}
				else $dummy_output .= "<i>" . $val . "</i>";
				$dummy_output .= '</div>';
				$dummy_output .= "<div class=\"original_language\">";
				$dummy_output .= "&nbsp;";
				$dummy_output .= "</div>";
				$dummy_output .= "<div class=\"translated_language\">";
				$new_counter = 0;
				$found = false;
				// NOTE: Get the translated text
				if (isset($new_language_array[$string])) {
					$dDummy =  $new_language_array[trim($string)];
					$dummy_output .= "<a href=\"#\" onclick=\"return helpPopup00('" . "ls01=" . $string . "&amp;ls02=" . $string . "&amp;language2=" . $this->language2 . "&amp;file_type=" . $this->file_type . "&amp;" . session_name() . "=" . session_id() . "&amp;anchor=a1_" . $counter . "');\">";
					$dum = stripslashes(mask_all($dDummy));
					$dummy_output .= $dum;
					if ($dum == "") {
						$dummy_output .= "<strong style=\"color: #FF0000\">" . str_replace("#LANGUAGE_FILE#", $genmod['gm_language'][$this->language2], $gm_lang["message_empty_warning"]) . "</strong>";
					}
					$dummy_output .= "</a>";
					$lastfound = $new_counter;
					$found = true;
				}
				else {
					$dummy_output .= "<a style=\"color: #FF0000\" href=\"#\" onclick=\"return helpPopup00('" . "ls01=" . $string . "&amp;ls02=" . (0 - intval($lastfound) - 1) . "&amp;language2=" . $this->language2 . "&amp;file_type=" . $this->file_type . "&amp;anchor=a1_" . $counter . "');\">";
					$dummy_output .= "<i>";
					if ($val == "") $dummy_output .= "&nbsp;";
					else $dummy_output .= $val;
					$dummy_output .= "</i></a>";
				}
				$new_counter++;
				
				$dummy_output .= "</div>";
				if (!$this->hide_translated) {
					echo $dummy_output;
					if ($colorid == 2) $colorid = 1;
					else $colorid++;
				}
				// NOTE: Print the untranslated strings
				else if ($this->hide_translated && !$found) {
					echo $dummy_output;
					if ($colorid == 2) $colorid = 1;
					else $colorid++;
				}
				$counter++;
				echo '</div>';
			}
		}
	}
	
	function DebugHelptext(&$gm_lang) {
		?>
		<form name="debug_form" method="post" action="editlang.php">
		<input type="hidden" name="page" value="editlang" />
		<input type="hidden" name="action" value="debug" />
		<input type="hidden" name="execute" value="true" />
		<div class="admin_topbottombar">
			<?php print $gm_lang["lang_debug"]; ?>
		</div>
		<div class="admin_item_box">
			<div class="choice_left">
				<input type="checkbox" name="DEBUG_LANG" value="yes" 
				<?php
				if (isset($_SESSION["DEBUG_LANG"])) {
					if (($_SESSION["DEBUG_LANG"]) == "yes") print "checked=\"checked\"";
				}
				?>
				/>
				<?php print $gm_lang["lang_debug_use"]?>&nbsp;&nbsp;
			</div>
			<div class="choice_middle">
				<input  type="submit" value="<?php print $gm_lang["save"];?>" />
			</div>
		</div>
		</form>
		<?php
	}
	
	function ExportLanguage(&$genmod, &$gm_lang) {
		?>
		<form name="export_form" method="get" action="editlang.php">
		<input type="hidden" name="page" value="editlang" />
		<input type="hidden" name="action" value="export" />
		<input type="hidden" name="execute" value="true" />
		<div class="admin_topbottombar">
			<?php print $gm_lang["export_lang_utility"]; ?>
		</div>
		<div class="admin_item_box">
			<div class="width25 choice_left">
				<div class="helpicon">
					<?php print_help_link("language_to_export_help", "qm", "language_to_export"); ?>
				</div>
				<div class="description">
					<?php print $gm_lang["language_to_export"]; ?>
					<br />
					<select name="language2">
					<?php
					foreach ($this->sorted_langs as $key => $value){
						if ($genmod['language_settings'][$key]["gm_lang_use"]) {
							print "\n\t\t\t<option value=\"$key\"";
							if ($key == $this->language2) print " selected=\"selected\"";
							print ">".$gm_lang["lang_name_".$key]."</option>";
						}
					}
					?>
					</select>
				</div>
			</div>
			<div class="width25 center choice_right">
				<input  type="submit" value="<?php print $gm_lang["export"];?>" />
			</div>
		</div>
		</form>
		
		<?php
		if ($this->execute) {
			print "<div class=\"shade2 center\">";
				print $gm_lang["export_results"];
			print "</div>";
			print "<div class=\"shade1 ltr\">";
				$data = "";
				$data_help = "";
				$storeerror = false;
				$storelang = LoadLanguage($this->language2, true) + LoadLanguage($this->language2, true, true);
				ksort($storelang);
				foreach($storelang as $string => $value) {
					if (substr($string, -5) == "_help") $data_help .= "\"".$string."\";\"".$value."\"\r\n";
					else $data .= "\"".$string."\";\"".$value."\"\r\n";
				}
				if (!empty($data)) {
					if (!$handle = fopen("languages/lang.".$this->lang_shortcut.".txt", "w")) {
						print str_replace("#lang_filename#", "languages/lang.".$this->lang_shortcut.".txt", $gm_lang["no_open"])."<br />";
						$storeerror = true;
						WriteToLog("EditLang-> Can't open file languages/lang.".$this->lang_shortcut.".txt", "E", "S");
					}
					else {
						if (fwrite($handle, $data)) {
							WriteToLog("EditLang-> ".$gm_lang["editlang_lang_export_success"], "I", "S");
							print $gm_lang["editlang_lang_export_success"]."<br />";
						}
						else {
							$storeerror = true;
							WriteToLog("EditLang-> ".$gm_lang["editlang_lang_export_no_success"], "E", "S");
							print "<span class=\"error\">".$gm_lang["editlang_lang_export_no_success"]."</span><br />";
						}
						fclose($handle);            
					}
				}
				else print "<span class=\"error\">".$gm_lang["lang_not_stored"]."</span><br />";
				if (!empty($data_help)) {
					if (!$handle_help = fopen("languages/help_text.".$this->lang_shortcut.".txt", "w")) {
						print str_replace("#lang_filename#", "languages/help_text.".$this->lang_shortcut.".txt", $gm_lang["no_open"])."<br />";
						$storeerror = true;
						WriteToLog("EditLang-> Can't open file languages/help_text.".$this->lang_shortcut.".txt", "E", "S");
					}
					else {
						if (fwrite($handle_help, $data_help)) {
							WriteToLog("EditLang-> ".$gm_lang["editlang_help_export_success"], "I", "S");
							print $gm_lang["editlang_help_export_success"]."<br />";
						}
						else {
							$storeerror = true;
							WriteToLog("EditLang-> ".$gm_lang["editlang_help_no_export_success"], "E", "S");
							print "<span class=\"error\">".$gm_lang["editlang_help_no_export_success"]."</span><br />";
						}
						fclose($handle_help);
					}
				}
				else print "<span class=\"error\">".$gm_lang["lang_help_not_stored"]."</span><br />";
				
				$data = "";
				$storefacts = LoadFacts($this->language2);
				ksort($storefacts);
				foreach($storefacts as $string => $value) {
					$data .= "\"".$string."\";\"".$value."\"\r\n";
				}
				if (!empty($data)) {
					if (!$handle = fopen("languages/facts.".$this->lang_shortcut.".txt", "w")) {
						print str_replace("#lang_filename#", "languages/facts.".$this->lang_shortcut.".txt", $gm_lang["no_open"])."<br />";
						$storeerror = true;
						WriteToLog("EditLang-> Can't open file languages/facts.".$this->lang_shortcut.".txt", "E", "S");
					}
					else {
						if (fwrite($handle, $data)) {
							WriteToLog("EditLang-> ".$gm_lang["editlang_facts_export_success"], "I", "S");
							print $gm_lang["editlang_facts_export_success"]."<br />";
						}
						else {
							$storeerror = true;
							WriteToLog("EditLang-> ".$gm_lang["editlang_facts_export_no_success"], "E", "S");
							print "<span class=\"error\">".$gm_lang["editlang_facts_export_no_success"]."</span><br />";
						}
						fclose($handle);            
					}
				}
				else print "<span class=\"error\">".$gm_lang["lang_facts_not_stored"]."</span><br />";
				if (!$storeerror) {
					$sql = "UPDATE ".TBLPREFIX."lang_settings SET ls_translated = '0', ls_md5_lang = '".md5_file("languages/lang.".$genmod['language_settings'][$this->language2]["lang_short_cut"].".txt")."', ls_md5_help = '".md5_file("languages/help_text.".$genmod['language_settings'][$this->language2]["lang_short_cut"].".txt")."', ls_md5_facts = '".md5_file("languages/facts.".$genmod['language_settings'][$this->language2]["lang_short_cut"].".txt")."' WHERE ls_gm_langname='".$this->language2."'";
					$res = NewQuery($sql);
				}
			print "</div>";
		}
	}
	
	function CompareLanguage(&$genmod, &$gm_lang) { ?>
		<form name="langdiff_form" method="get" action="editlang.php">
		<input type="hidden" name="page" value="editlang" />
		<input type="hidden" name="action" value="compare" />
		<input type="hidden" name="execute" value="true" />
		<div class="admin_topbottombar">
			<?php print $gm_lang["compare_lang_utility"]; ?>
		</div>
		<div class="admin_item_box">
			<div class="width25 choice_left">
				<div class="helpicon">
					<?php print_help_link("new_language_help", "qm", "new_language");?>
				</div>
				<div class="description">
					<?php
					print $gm_lang["new_language"]."<br />";
					print "<select name=\"language1\">";
					foreach ($this->sorted_langs as $key => $value) {
						if ($genmod['language_settings'][$key]["gm_lang_use"]) {
							print "\n\t\t\t<option value=\"$key\"";
							if ($key == $this->language1) print " selected=\"selected\"";
							print ">".$gm_lang["lang_name_".$key]."</option>";
						}
					}
					?>
					</select>
				</div>
			</div>
			<div class="width25 choice_middle">
				<div class="helpicon">
					<?php print_help_link("old_language_help", "qm", "old_language");?>
				</div>
				<div class="description">
					<?php
					print $gm_lang["old_language"]."<br />";
					print "<select name=\"language2\">";
					foreach ($this->sorted_langs as $key => $value) {
						if ($genmod['language_settings'][$key]["gm_lang_use"]) {
							print "\n\t\t\t<option value=\"$key\"";
							if ($key == $this->language2) print " selected=\"selected\"";
							print ">".$gm_lang["lang_name_".$key]."</option>";
						}
					}
					?>
					</select>
				</div>
			</div>
			<div class="width25 center choice_right">
				<input  type="submit" value="<?php print $gm_lang["compare"]; ?>" />
			</div>
		</div>
		</form>
		<?php
		if ($this->execute) {
			?>
			<div class="topbottombar subheaders">
				<?php echo $gm_lang["comparing_main"];?>
			</div>
			<?php
			$this->ShowLanguageCompare($genmod, LoadLanguage($this->language1, true), LoadLanguage($this->language2, true))
			?>
			<img src="<?php print $genmod['gm_image_dir']."/".$genmod['gm_images']["hline"]["other"];?>" width="100%" height="6" alt="" /><br />
			<div class="topbottombar subheaders">
				<?php print $gm_lang["comparing_facts"];?>
			</div>
			<?php
			$this->ShowLanguageCompare($genmod, LoadFacts($this->language1), LoadFacts($this->language2), true);
				?>
			<img src="<?php print $genmod['gm_image_dir']."/".$genmod['gm_images']["hline"]["other"];?>" width="100%" height="6" alt="" /><br />
			<div class="topbottombar subheaders">
				<?php print $gm_lang["comparing_helptext"];?>
			</div>
			<?php
			$this->langhelp = LoadEnglish(true, true, true);
			$this->ShowLanguageCompare($genmod, LoadLanguage($this->language1, true, true), LoadLanguage($this->language2, true, true), false, true);
			$this->langhelp = array();
		}
	}
	
	function ShowLanguageCompare(&$genmod, $lang1, $lang2, $facts = false, $help = false) {
		echo '<span class="subheaders">'.$genmod['gm_lang']["additions"].':</span>';
		$count=0;
		$colorid = 1;
		foreach($lang1 as $key=>$value) {
			echo '<div class="language_item_box'.$colorid.'">';
			if (!array_key_exists($key, $lang2)) {
				echo '<div class="original_language width40">';
//				if (!$facts && !$help) echo $genmod['gm_lang'][$key];
//				else if ($facts) echo $genmod['factarray'][$key];
//				else if ($help) echo $this->langhelp[$key];
				echo $key;
				echo '</div>';
				echo '<div class="translated_language">'.$value.'</div>';
				$count++;
				if ($colorid == 2) $colorid = 1;
				else $colorid++;
			}
			echo '</div>';
		}
		if ($count==0) echo '<div class="shade1">'.$genmod['gm_lang']["no_additions"].'</div>';
		echo '<span class="subheaders">'.$genmod['gm_lang']["subtractions"].':</span>';
		$count = 0;
		$colorid = 1;
		foreach($lang2 as $key=>$value) {
			echo '<div class="language_item_box'.$colorid.'">';
			if (!array_key_exists($key, $lang1)) {
				echo '<div class="original_language">';
				if (!$facts && !$help) echo $genmod['gm_lang'][$key];
				else if ($facts) echo $genmod['factarray'][$key];
				else if ($help) echo $this->langhelp[$key];
				echo '</div>';
				echo '<div class="translated_language">'.$value.'</div>';
				$count++;
				if ($colorid == 2) $colorid = 1;
				else $colorid++;
			}
			echo '</div>';
		}
		if ($count==0) echo '<div class="shade1">'.$genmod['gm_lang']["no_subtractions"].'</div>';
	}
}
require "config.php";
require "includes/setgenmod.php";
$editlang = new EditLang($genmod, $gm_lang, $gm_username);

// NOTE: make sure that they have admin status before they can use this page
// NOTE: otherwise have them login again
$uname = $gm_username;
if (empty($uname)) {
	if (LOGIN_URL == "") header("Location: login.php?url=editlang.php");
	else header("Location: ".LOGIN_URL."?url=editlang.php");
	exit;
}

?>