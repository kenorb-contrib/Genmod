<?php
/**
 * File to edit the language settings of Genmod
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 - 2012 Genmod Development Team
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
 * @subpackage Admin
 * @version $Id: editlang_edit_settings.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

if (!isset($ln)) $ln = "";
if (!isset($action)) $action = "";
if ($action == "" and $ln == "") {
  header("Location: admin.php");
  exit;
}  
  
// NOTE: make sure that they have admin status before they can use this page
// NOTE: otherwise have them login again
if (!$gm_user->UserIsAdmin()) {
  print "Please close this window and do a Login in the former window first...";
  exit;
}

// Determine whether this language's Active status should be protected
if (AdminFunctions::LanguageInUse($ln)) $protectActive = true;
else$protectActive = false;

$d_LangName = "lang_name_" . $ln;
$sentHeader = false;    // Indicates whether HTML headers have been sent
if ($action !="save" and $action != "toggleActive") {
	PrintSimpleHeader(GM_LANG_config_lang_utility);
	$sentHeader = true;
	print "<div id=\"EditLangContent\">";
}

if ($action == "new_lang") {
	require(SystemConfig::$GM_BASE_DIRECTORY . "includes/values/lang_codes_std.php");
	$ln = strtolower($lng_codes[$new_shortcut][0]);
	
	$d_LangName      = "lang_name_" . $ln;
	$languages[$ln]     = $ln;
	$gm_lang_use[$ln]    = true;
	define("GM_LANG_".$ln, $lng_codes[$new_shortcut][0]);
//	$gm_lang[$ln]    = $lng_codes[$new_shortcut][0];
	$lang_short_cut[$ln]    = $new_shortcut;
	$lang_langcode[$ln]    = $new_shortcut . ";";
	if (array_key_exists($new_shortcut, $lng_synonyms)) $lang_langcode[$ln] .= $lng_synonyms[$new_shortcut];
	$gm_language[$ln]    = "languages/lang.".$new_shortcut.".txt";
	$confighelpfile[$ln]  = "";
	$helptextfile[$ln]    = "languages/help_text.".$new_shortcut.".txt";
	
	// Suggest a suitable flag file
	$temp = strtolower($lng_codes[$new_shortcut][1]).".gif";
	if (file_exists("images/flags/".$temp)) $flag = $temp;						// long name takes precedence
	else if (file_exists("images/flags/".$new_shortcut.".gif")) $flag = $new_shortcut.".gif";		// use short name if long name doesn't exist
	else $flag = "new.gif";				// default if neither a long nor a short name exist
	$flagsfile[$ln] = "images/flags/" . $flag;
	
	$factsfile[$ln]    = "languages/facts.".$new_shortcut.".txt";
	$DATE_FORMAT_array[$ln]  = "D M Y";
	$TIME_FORMAT_array[$ln]  = "g:i:sa";
	$WEEK_START_array[$ln]  = "0";
	$TEXT_DIRECTION_array[$ln]  = "ltr";
	$NAME_REVERSE_array[$ln]  = false;
	$ALPHABET_upper[$ln]    = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$ALPHABET_lower[$ln]    = "abcdefghijklmnopqrstuvwxyz";
	$MON_SHORT[$ln]			= "";
	
//	$gm_lang[$d_LangName]  = $lng_codes[$new_shortcut][0];
	define("GM_LANG_".$d_LangName,$lng_codes[$new_shortcut][0]);
}
else if(!isset($v_flagsfile) && isset($flagsfile[$ln])) $v_flagsfile=$flagsfile[$ln];
else if(!isset($v_flagsfile)) $v_flagsfile = "";

if ($action != "save" && $action != "toggleActive") { ?>
	<script language="JavaScript" type="text/javascript">
	<!--
		function CheckFileSelect() {
			if (document.Form1.v_u_lang_filename.value != ""){
				document.Form1.v_lang_filename.value = document.Form1.v_u_lang_filename.value;
			}
		}
		
		function CloseWindow() {
			document.Form1.action.value = "";
			self.close();
		}
	//-->
	</script>
	
	<form name="Form1" method="post" action="editlang_edit_settings.php">
		<input type="hidden" name="action" value="save" />
		<input type="hidden" name="ln" value="<?php print $ln;?>" />
		<?php
		if ($action == "new_lang") print "<input type=\"hidden\" name=\"new_old\" value=\"new\" />";
		else print "<input type=\"hidden\" name=\"new_old\" value=\"old\" />"; 
		?>
	<table class="NavBlockTable AdminNavBlockTable">
		<tr>
			<td class="NavBlockHeader AdminNavBlockHeader" colspan="2">
				<?php print "<div class=\"AdminNavBlockTitle\">";
				if ($action == "new_lang") print GM_LANG_add_new_language;
				else print GM_LANG_config_lang_utility;
				?>
				</div>
				<?php print constant("GM_LANG_".$d_LangName); ?>
			</td>
		</tr>
		<?php                          
		if ($action != "new_lang") {
			if ($protectActive) $v_lang_use = true;
			if (!isset($v_lang_use)) $v_lang_use = $gm_lang_use[$ln];
			?>
			<tr>
				<td class="NavBlockLabel AdimNavBlockLabel">
					<div class="HelpIconContainer"><?php PrintHelpLink("active_help", "qm"); ?></div>
					<div class="AdminNavBlockOptionText"><?php print GM_LANG_active;?></div>
				</td>
				<td class="NavBlockField AdimNavBlockField">
					<?php
					if ($v_lang_use) {
						print "<input";
						if ($protectActive) print " disabled";
						print " type=\"checkbox\" name=\"v_lang_use\" value=\"true\" checked=\"checked\" />";
					}
					else print "<input type=\"checkbox\" name=\"v_lang_use\" value=\"true\" />";
					?>
				</td>
			</tr>
			<?php
		}
		else print "<input type=\"hidden\" name=\"v_lang_use\" value=\"".$gm_lang_use[$ln]."\" />";
		?>
		<tr>
			<td class="NavBlockLabel AdimNavBlockLabel">
				<?php if (!isset($v_original_lang_name)) $v_original_lang_name = constant("GM_LANG_".$ln);?>
				<div class="HelpIconContainer"><?php PrintHelpLink("original_lang_name_help", "qm"); ?></div>
				<div class="AdminNavBlockOptionText"><?php print str_replace("#D_LANGNAME#", constant("GM_LANG_".$d_LangName), GM_LANG_original_lang_name);?></div>
			</td>
			<td class="NavBlockField AdimNavBlockField">
				<input type="text" name="v_original_lang_name" size="30" value="<?php print $v_original_lang_name;?>" />
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdimNavBlockLabel">
				<?php if (!isset($v_lang_shortcut)) $v_lang_shortcut = $lang_short_cut[$ln];?>
				<div class="HelpIconContainer"><?php PrintHelpLink("lang_shortcut_help", "qm"); ?></div>
				<div class="AdminNavBlockOptionText"><?php print GM_LANG_lang_shortcut;?></div>
			</td>
			<td class="NavBlockField AdimNavBlockField">
				<input type="text" name="v_lang_shortcut" size="2" value="<?php print $v_lang_shortcut;?>" onchange="document.Form1.action.value=''; submit();" />
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdimNavBlockLabel">
				<?php if (!isset($v_lang_langcode)) $v_lang_langcode = $lang_langcode[$ln];?>
				<div class="HelpIconContainer"><?php PrintHelpLink("lang_langcode_help", "qm"); ?></div>
				<div class="AdminNavBlockOptionText"><?php print GM_LANG_lang_langcode;?></div>
			</td>
			<td class="NavBlockField AdimNavBlockField">
				<input type="text" name="v_lang_langcode" size="40" value="<?php print $v_lang_langcode;?>" />
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdimNavBlockLabel">
				<?php if (!isset($v_flagsfile)) $v_flagsfile = $flagsfile[$ln];?>
				<div class="HelpIconContainer"><?php PrintHelpLink("flagsfile_help", "qm"); ?></div>
				<div class="AdminNavBlockOptionText"><?php print GM_LANG_flagsfile;?></div>
			</td>
			<td class="NavBlockField AdimNavBlockField">
				<?php
				$dire = "images/flags";
				if ($handle = opendir($dire)) {
					$flagfiles = array();
					$sortedflags = array();
					$cf=0;
					print $dire."/";
					while (false !== ($file = readdir($handle))) {
						$pos1 = strpos($file, "gif");
						if ($file != "." && $file != ".." && $pos1) {
							$filelang = substr($file, 0, $pos1-1);
							$fileflag = $dire."/".$filelang.".gif";
							$flagfiles["file"][$cf]=$file;
							$flagfiles["path"][$cf]=$fileflag;
							$sortedflags[$file]=$cf;
							$cf++;
						}
					}
					closedir($handle);
					$sortedflags = array_flip($sortedflags);
					asort($sortedflags);
					$sortedflags = array_flip($sortedflags);
					reset($sortedflags);
					if ($action != "new_lang") {
						print "&nbsp;&nbsp;&nbsp;<select name=\"v_flagsfile\" onchange=\"document.Form1.action.value=''; submit();\">\n";
						foreach ($sortedflags as $key=>$value) {
							$i = $sortedflags[$key];
							print "<option value=\"".$flagfiles["path"][$i]."\" ";
							if ($v_flagsfile == $flagfiles["path"][$i]){
								print "selected=\"selected\" ";
								$flag_i = $i;
							}
							print ">".$flagfiles["file"][$i]."</option>\n";
						}
						print "</select>\n";
					} 
					else {
						foreach ($sortedflags as $key=>$value) {
							$i = $sortedflags[$key];
							if ($v_flagsfile == $flagfiles["path"][$i]){
								$flag_i = $i;
								break;
							}
						}
						print $flagfiles["file"][$i];
					}
				}
				if (isset($flag_i) && isset($flagfiles["path"][$flag_i])){
					print "<div id=\"Flag\">";
					print "<img src=\"".$flagfiles["path"][$flag_i]."\" alt=\"\" class=\"BrightFlag\" /></div>\n";
				}
				?>
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdimNavBlockLabel">
				<?php if (!isset($v_date_format)) $v_date_format = $DATE_FORMAT_array[$ln];?>
				<div class="HelpIconContainer"><?php PrintHelpLink("date_format_help", "qm", "date_format"); ?></div>
				<div class="AdminNavBlockOptionText"><?php print GM_LANG_date_format;?></div>
			</td>
			<td class="NavBlockField AdimNavBlockField">
				<input type="text" name="v_date_format" size="30" value="<?php print $v_date_format;?>" />
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdimNavBlockLabel">
				<?php if (!isset($v_time_format)) $v_time_format = $TIME_FORMAT_array[$ln];?>
				<div class="HelpIconContainer"><?php PrintHelpLink("time_format_help", "qm", "time_format"); ?></div>
				<div class="AdminNavBlockOptionText"><?php print GM_LANG_time_format;?></div>
			</td>
			<td class="NavBlockField AdimNavBlockField">
				<input type="text" name="v_time_format" size="30" value="<?php print $v_time_format;?>" />
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdimNavBlockLabel">
				<?php if (!isset($v_week_start)) $v_week_start = $WEEK_START_array[$ln];?>
				<div class="HelpIconContainer"><?php PrintHelpLink("week_start_help", "qm","week_start"); ?></div>
				<div class="AdminNavBlockOptionText"><?php print GM_LANG_week_start;?></div>
			</td>
			<td class="NavBlockField AdimNavBlockField">
				<select size="1" name="v_week_start">
				<?php
				$dayArray = array(GM_LANG_sunday,GM_LANG_monday,GM_LANG_tuesday,GM_LANG_wednesday,GM_LANG_thursday,GM_LANG_friday,GM_LANG_saturday);
				for ($x = 0; $x <= 6; $x++)  {
					print "<option";
					if ($v_week_start == $x) print " selected=\"selected\"";
					print " value=\"";
					print $x;
					print "\">";
					print $dayArray[$x];
					print "</option>";
				}
				?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdimNavBlockLabel">
				<?php if (!isset($v_text_direction)) $v_text_direction = $TEXT_DIRECTION_array[$ln];?>
				<div class="HelpIconContainer"><?php PrintHelpLink("text_direction_help", "qm", "text_direction"); ?></div>
				<div class="AdminNavBlockOptionText"><?php print GM_LANG_text_direction;?></div>
			</td>
			<td class="NavBlockField AdimNavBlockField">
				<select size="1" name="v_text_direction">
				<option
				<?php if ($v_text_direction == "ltr") print " selected=\"selected\""; ?>
				value="0">
				<?php print GM_LANG_ltr;?>
				</option>
				<option
				<?php if ($v_text_direction == "rtl") print " selected=\"selected\""; ?>
				value="1">
				<?php print GM_LANG_rtl;?>
				</option>
				</select>
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdimNavBlockLabel">
				<?php if (!isset($v_name_reverse)) $v_name_reverse = $NAME_REVERSE_array[$ln];?>
				<div class="HelpIconContainer"><?php PrintHelpLink("name_reverse_help", "qm", "name_reverse"); ?></div>
				<div class="AdminNavBlockOptionText"><?php print GM_LANG_name_reverse;?></div>
			</td>
			<td class="NavBlockField AdimNavBlockField">
				<select size="1" name="v_name_reverse">
				<option
				<?php if (!$v_name_reverse) print " selected=\"selected\""; ?>
				value="0">
				<?php print GM_LANG_no;?>
				</option>
				<option
				<?php if ($v_name_reverse) print " selected=\"selected\"";?>
				value="1">
				<?php print GM_LANG_yes;?>
				</option>
				</select>
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdimNavBlockLabel">
				<?php if (!isset($v_alphabet_upper)) $v_alphabet_upper = $ALPHABET_upper[$ln];?>
				<div class="HelpIconContainer"><?php PrintHelpLink("alphabet_upper_help", "qm", "alphabet_upper"); ?></div>
				<div class="AdminNavBlockOptionText"><?php print GM_LANG_alphabet_upper;?></div>
			</td>
			<td class="NavBlockField AdimNavBlockField">
				<input type="text" name="v_alphabet_upper" size="50" value="<?php print $v_alphabet_upper;?>" />
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdimNavBlockLabel">
				<?php if (!isset($v_alphabet_lower)) $v_alphabet_lower = $ALPHABET_lower[$ln];?>
				<div class="HelpIconContainer"><?php PrintHelpLink("alphabet_lower_help", "qm", "alphabet_lower"); ?></div>
				<div class="AdminNavBlockOptionText"><?php print GM_LANG_alphabet_lower;?></div>
			</td>
			<td class="NavBlockField AdimNavBlockField">
				<input type="text" name="v_alphabet_lower" size="50" value="<?php print $v_alphabet_lower;?>" />
			</td>
		</tr>
		<tr>
			<td class="NavBlockLabel AdimNavBlockLabel">
				<?php if (!isset($v_mon_short)) $v_mon_short = $MON_SHORT_array[$ln];?>
				<div class="HelpIconContainer"><?php PrintHelpLink("mon_short_help", "qm", "mon_short"); ?></div>
				<div class="AdminNavBlockOptionText"><?php print GM_LANG_mon_short;?></div>
			</td>
			<td class="NavBlockField AdimNavBlockField">
				<input type="text" name="v_mon_short" size="50" value="<?php print $v_mon_short;?>" />
			</td>
		</tr>
		<?php
		if (!isset($v_lang_filename)) $v_lang_filename = "languages/lang.".$v_lang_shortcut.".txt";
		if (!isset($v_factsfile)) $v_factsfile = "languages/facts.".$v_lang_shortcut.".txt";
		if (!isset($v_helpfile)) $v_helpfile = "languages/help_text.".$v_lang_shortcut.".txt";
		if (!isset($v_mon_short)) $v_mon_short = "";
		if ($action != "new_lang"){
			?>
			<tr>
				<td class="NavBlockLabel AdimNavBlockLabel">
					<div class="HelpIconContainer"><?php PrintHelpLink("lang_filenames_help", "qm", "lang_filenames"); ?></div>
					<div class="AdminNavBlockOptionText"><?php print GM_LANG_lang_filenames;?></div>
				</td>
				<td class="NavBlockField AdimNavBlockField">
					<?php
						print $v_factsfile;
						if (!file_exists($v_factsfile)) print "&nbsp;&nbsp;<span class=\"Error\">" . GM_LANG_file_does_not_exist . "</span>";
						print "<br />";
						
						print $v_helpfile;
						if (!file_exists($v_helpfile)) print "&nbsp;&nbsp;<span class=\"Error\">" . GM_LANG_file_does_not_exist . "</span>";
						print "<br />";
						
						print $v_lang_filename;
						if (!file_exists($v_lang_filename)) print "&nbsp;&nbsp;<span class=\"Error\">" . GM_LANG_file_does_not_exist . "</span>";
					?>
				</td>
			</tr>
			<?php
		}
		?>
		<tr>
			<td class="NavBlockFooter" colspan="2">
				<input type="submit" value="<?php print GM_LANG_lang_save;?>" />
				&nbsp;&nbsp;
				<input type="submit" value="<?php print GM_LANG_cancel;?>" onclick="CloseWindow();" />
			</td>
		</tr>
		</table>
	</form>
	<?php
}

if ($action == "toggleActive") {
	if ($language_settings[$ln]["gm_lang_use"] == true) {
		$gm_lang_use[$ln] = false;
		AdminFunctions::RemoveLanguage($ln);
		AdminFunctions::DeactivateLanguage($ln);
	}
	else {
		$gm_lang_use[$ln] = true;
		AdminFunctions::StoreLanguage($ln);
		AdminFunctions::ActivateLanguage($ln);
	}
}

if ($action == "save") {
	if (!isset($_POST)) $_POST = $HTTP_POST_VARS;
	if ($protectActive) $_POST["v_lang_use"] = true;
	if (!isset($_POST["v_lang_use"])) $_POST["v_lang_use"] = false;
	if ($_POST["new_old"] == "new") {
		$lang = array();
		$d_LangName      = "lang_name_".$ln;
//		$gm_lang[$d_LangName]  = $v_original_lang_name;
//		define("GM_LANG_".$d_LangName, $v_original_lang_name);
//		$gm_lang[$ln]    = $ln;
//		define("GM_LANG_".$ln, $ln);
		$gm_language[$ln]    = "languages/lang.".$v_lang_shortcut.".txt";
		$confighelpfile[$ln]  = "";
		$helptextfile[$ln]    = "languages/help_text.".$v_lang_shortcut.".txt";
		$factsfile[$ln]    = "languages/facts.".$v_lang_shortcut.".txt";
		$language_settings[$ln]  = $lang;
		$languages[$ln]    = $ln;
	}
	
	$flagsfile[$ln]    = $v_flagsfile;
	// define("GM_LANG_".$ln, $_POST["v_original_lang_name"]); already defined
	if ($gm_lang_use[$ln] == "1" && $_POST["v_lang_use"] == false) AdminFunctions::RemoveLanguage($ln);
	if ($gm_lang_use[$ln] == "0" && $_POST["v_lang_use"] == true) AdminFunctions::StoreLanguage($ln);
	$gm_lang_use[$ln]  = $_POST["v_lang_use"];
	$lang_short_cut[$ln]  = $_POST["v_lang_shortcut"];
	$lang_langcode[$ln]  = $_POST["v_lang_langcode"];
	
	if (substr($lang_langcode[$ln],strlen($lang_langcode[$ln])-1,1) != ";") $lang_langcode[$ln] .= ";";
	
	$ALPHABET_upper[$ln]  = $_POST["v_alphabet_upper"];
	$ALPHABET_lower[$ln]  = $_POST["v_alphabet_lower"];
	$DATE_FORMAT_array[$ln]  = $_POST["v_date_format"];
	$TIME_FORMAT_array[$ln]  = $_POST["v_time_format"];
	$WEEK_START_array[$ln]  = $_POST["v_week_start"];
	if ($_POST["v_text_direction"] == "0") $TEXT_DIRECTION_array[$ln] = "ltr"; else $TEXT_DIRECTION_array[$ln] = "rtl";
	$NAME_REVERSE_array[$ln]  = $_POST["v_name_reverse"];
	$MON_SHORT_array[$ln]  = $_POST["v_mon_short"];
	
	
	$newvars["gm_langname"] 	= $languages[$ln];
	if ($gm_lang_use[$ln] == true) $newvars["gm_lang_use"] = "1";
	else $newvars["gm_lang_use"] = "0";
	$newvars["gm_lang"] 		= $v_original_lang_name;
	$newvars["lang_short_cut"] 	= $lang_short_cut[$ln];
	$newvars["langcode"] 		= $lang_langcode[$ln];
	$newvars["gm_language"] 	= $gm_language[$ln];
	$newvars["confighelpfile"] 	= "";
	$newvars["helptextfile"] 	= $helptextfile[$ln];
	$newvars["flagsfile"]		= $flagsfile[$ln];
	$newvars["factsfile"]		= $factsfile[$ln];
	$newvars["DATE_FORMAT"]		= $DATE_FORMAT_array[$ln];
	$newvars["TIME_FORMAT"]		= $TIME_FORMAT_array[$ln];
	$newvars["WEEK_START"]		= $WEEK_START_array[$ln];
	$newvars["TEXT_DIRECTION"]	= $TEXT_DIRECTION_array[$ln];
	if ($NAME_REVERSE_array[$ln] == true) $newvars["NAME_REVERSE"] = "1";
	else $newvars["NAME_REVERSE"] = "0";
	$newvars["ALPHABET_upper"]	= $ALPHABET_upper[$ln];
	$newvars["ALPHABET_lower"]	= $ALPHABET_lower[$ln];
	$newvars["MON_SHORT"]		= $MON_SHORT_array[$ln];
	if (!AdminFunctions::StoreLangVars($newvars)) $error = "lang_config_write_error";
	else $error = "";

	if ($error != "") {
		if (!$sentHeader) {
			PrintSimpleHeader(GM_LANG_config_lang_utility);
			$sentHeader = true;
		}
	    print "<form name=\"Form2\" method=\"post\" action=\"" .SCRIPT_NAME. "\">";
	    print "<table class=\"NavBlockTable AdminNavBlockTable\">";
	    print "<tr>";
	    print "<td class=\"NavBlockHeader\">";
	    print "<span class=\"Error\">" . constant("GM_LANG_".$error) . "</span><br /><br />";
	    srand((double)microtime()*1000000);
	    print "<input type=\"submit\" value=\"" . GM_LANG_close_window . "\"" . " onclick=\"self.close(); return false;\" />";
	    print "</td>";
	    print "</tr>";
	    print "</table>";
	    print "</form>";
	}
}
if ($sentHeader) {
	print "<!-- Close EditLangContent //--></div>"; // Close EditLangContent
	PrintSimpleFooter();
}
else if ($action == "toggleActive") {
	header("Location: changelanguage.php");
	exit;
}
else { ?>
	<script language="JavaScript" type="text/javascript">
	<!--
		opener.location.reload();
		self.close();
	//-->
	</script>
	<?php
}
?>