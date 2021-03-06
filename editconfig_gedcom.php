<?php
/**
 * UI for online updating of the gedcom config file.
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
 * This Page Is Valid XHTML 1.0 Transitional! > 22 August 2005
 *
 * @author GM Development Team
 * @package Genmod
 * @subpackage Admin
 * @version $Id: editconfig_gedcom.php,v 1.18 2006/04/07 03:21:09 sjouke Exp $
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

if (empty($action)) $action="";
if (empty($source)) $source="";		// Set when loaded from uploadgedcom.php
if (!userGedcomAdmin($gm_username)) {
	header("Location: editgedcoms.php");
	exit;
}

/**
 * Inclusion of the language files
*/
require $GM_BASE_DIRECTORY.$factsfile["english"];
if (file_exists($GM_BASE_DIRECTORY.$factsfile[$LANGUAGE])) require $GM_BASE_DIRECTORY.$factsfile[$LANGUAGE];

if (!isset($_POST)) $_POST = $HTTP_POST_VARS;

// Remove slashes
if (isset($_POST["NEW_COMMON_NAMES_ADD"])) $_POST["NEW_COMMON_NAMES_ADD"] = stripslashes($_POST["NEW_COMMON_NAMES_ADD"]);
if (isset($_POST["NEW_COMMON_NAMES_REMOVE"])) $_POST["NEW_COMMON_NAMES_REMOVE"] = stripslashes($_POST["NEW_COMMON_NAMES_REMOVE"]);
if (empty($oldged)) $oldged = "";
else $ged = $oldged;
if (!isset($path)) $path = "";
if (!isset($GEDFILENAME)) $GEDFILENAME = "";

if (isset($GEDCOMPATH)) {
	// NOTE: Check if we are uploading a file and retrieve the filename
	if (isset($_FILES['GEDCOMPATH'])) {
		if (filesize($_FILES['GEDCOMPATH']['tmp_name'])!= 0) $GEDCOMPATH = $_FILES['GEDCOMPATH']['name'];
	}

	// NOTE: Extract the GEDCOM filename
	if (!empty($path)) $GEDFILENAME = basename($path);
	else $GEDFILENAME = basename($GEDCOMPATH);

	// NOTE: Check if it is a zipfile
	if ($path == "") if (strstr(strtolower(trim($GEDFILENAME)), ".zip")==".zip") {
		if ($source == "add_form") $GEDFILENAME = GetGEDFromZIP($GEDCOMPATH);
	}
	// NOTE: Check if there is an extension
	if (strtolower(substr(trim($GEDFILENAME), -4)) != ".ged" && strtolower(substr(trim($GEDFILENAME), -4)) != ".zip") $GEDFILENAME .= ".ged";
	// NOTE: Check if the input contains a valid path otherwise check if there is one in the GEDCOMPATH
	if (!is_dir($path)) {
		if (!empty($path)) $parts = preg_split("/[\/\\\]/", $path);
		else $parts = preg_split("/[\/\\\]/", $GEDCOMPATH);
		$path = "";
		$ctparts = count($parts)-1;
		if (count($parts) == 1) $path = $INDEX_DIRECTORY;
		else {
			foreach ($parts as $key => $pathpart) {
				if ($key < $ctparts) $path .= $pathpart."/";
			}
		}
	}
	$ctupload = count($_FILES);
	if ($ctupload > 0) {
		// NOTE: When uploading a file check if it doesn't exist yet
		if (!isset($GEDCOMS[$GEDFILENAME]) || !file_exists($path.$GEDFILENAME)) {
			if (move_uploaded_file($_FILES['GEDCOMPATH']['tmp_name'], $path.$GEDFILENAME)) {
				WriteToLog("Gedcom ".$path.$GEDFILENAME." uploaded", "I", "S");
			}
		}
		// NOTE: If the file exists we will make a backup file
		else if (file_exists($path.$GEDFILENAME)) {
			if (file_exists($path.$GEDFILENAME.".old")) unlink($path.$GEDFILENAME.".old");
				copy($path.$GEDFILENAME, $path.$GEDFILENAME.".old");
				unlink($path.$GEDFILENAME);
			move_uploaded_file($_FILES['GEDCOMPATH']['tmp_name'], $path.$GEDFILENAME);
		}
		if (strstr(strtolower(trim($GEDFILENAME)), ".zip")==".zip") $GEDFILENAME = GetGEDFromZIP($path.$GEDFILENAME);
	}
	$ged = $GEDFILENAME;
}
if (isset($ged)) {
	if (isset($GEDCOMS[$ged])) {
		$GEDCOMPATH = $GEDCOMS[$ged]["path"];
		$path = "";
		$parts = preg_split("/[\/\\\]/", $GEDCOMPATH);
		$ctparts = count($parts)-1;
		if (count($parts) == 1) $path = $INDEX_DIRECTORY;
		else {
			foreach ($parts as $key => $pathpart) {
				if ($key < $ctparts) $path .= $pathpart."/";
			}
		}
		$GEDFILENAME = $ged;
		if (!isset($gedcom_title)) $gedcom_title = $GEDCOMS[$ged]["title"];
		$gedcom_config = $GEDCOMS[$ged]["config"];
		$gedcom_privacy = $GEDCOMS[$ged]["privacy"];
		$gedcom_id = $GEDCOMS[$ged]["id"];
		$FILE = $ged;
		$oldged = $ged;
	}
	else {
		if (empty($_POST["GEDCOMPATH"])) {
			$GEDCOMPATH = "";
			$gedcom_title = "";
		}
		$gedcom_config = "config_gedcom.php";
		$gedcom_privacy = "privacy.php";
		$gedcom_id = "";
	}
}
else {
	$GEDCOMPATH = "";
	$gedcom_title = "";
	$gedcom_config = "config_gedcom.php";
	$gedcom_privacy = "privacy.php";
	$gedcom_id = "";
	$path = "";
	$GEDFILENAME = "";
}
$USERLANG = $LANGUAGE;
$temp = $THEME_DIR;

if (!isset($ged) || !isset($GEDCOMS[$ged])) require($gedcom_config);
else ReadGedcomConfig($ged);

if (!isset($_POST["GEDCOMLANG"])) $GEDCOMLANG = $LANGUAGE;
$LANGUAGE = $USERLANG;
$error_msg = "";

if (!file_exists($path.$GEDFILENAME) && $source != "add_new_form") $action="add";
if ($action=="update") {
	$errors = false;
	if (!isset($_POST)) $_POST = $HTTP_POST_VARS;
	$FILE=$GEDFILENAME;
	$newgedcom=false;
	$gedcom_config="config_gedcom.php";

	$gedarray = array();
	$gedarray["gedcom"] = $FILE;
	$gedarray["config"] = $gedcom_config;
	$gedarray["privacy"] = $gedcom_privacy;
	if (!empty($gedcom_title)) $gedarray["title"] = $gedcom_title;
	else if (!empty($_POST["gedcom_title"])) $gedarray["title"] = $_POST["gedcom_title"];
	else $gedarray["title"] = str_replace("#GEDCOMFILE#", $GEDFILENAME, $gm_lang["new_gedcom_title"]);
	$gedarray["path"] = $path.$GEDFILENAME;
	$gedarray["id"] = $gedcom_id;

	// Check that add/remove common surnames are separated by [,;] blank
	$_POST["NEW_COMMON_NAMES_REMOVE"] = preg_replace("/[,;]\b/", ", ", $_POST["NEW_COMMON_NAMES_REMOVE"]);
	$_POST["NEW_COMMON_NAMES_ADD"] = preg_replace("/[,;]\b/", ", ", $_POST["NEW_COMMON_NAMES_ADD"]);
	$COMMON_NAMES_THRESHOLD = $_POST["NEW_COMMON_NAMES_THRESHOLD"];
	$COMMON_NAMES_ADD = $_POST["NEW_COMMON_NAMES_ADD"];
	$COMMON_NAMES_REMOVE = $_POST["NEW_COMMON_NAMES_REMOVE"];
	$gedarray["commonsurnames"] = "";
	$GEDCOMS[$FILE] = $gedarray;
	store_gedcoms();

	ReadGedcoms();
	$boolarray = array();
	$boolarray["yes"]="1";
	$boolarray["no"]="0";
	$boolarray[false]="0";
	$boolarray[true]="1";

	$_POST["NEW_MEDIA_DIRECTORY"] = preg_replace('/\\\/','/',$_POST["NEW_MEDIA_DIRECTORY"]);
	$ct = preg_match("'/$'", $_POST["NEW_MEDIA_DIRECTORY"]);
	if ($ct==0) $_POST["NEW_MEDIA_DIRECTORY"] .= "/";
	if(preg_match("/.*[a-zA-Z]{1}:.*/",$_POST["NEW_MEDIA_DIRECTORY"])>0) $errors = true;
	if (preg_match("'://'", $_POST["NEW_HOME_SITE_URL"])==0) $_POST["NEW_HOME_SITE_URL"] = "http://".$_POST["NEW_HOME_SITE_URL"];
	$_POST["NEW_PEDIGREE_ROOT_ID"] = trim($_POST["NEW_PEDIGREE_ROOT_ID"]);
	if ($_POST["NEW_DAYS_TO_SHOW_LIMIT"] < 1) $_POST["NEW_DAYS_TO_SHOW_LIMIT"] = 1;
	if ($_POST["NEW_DAYS_TO_SHOW_LIMIT"] > 30) $_POST["NEW_DAYS_TO_SHOW_LIMIT"] = 30;

	$newconf = array();
	$newconf["gedcom"] = $FILE;
	$newconf["name_from_gedcom"] = $boolarray[$NAME_FROM_GEDCOM]; // -- This value is used but defaults to false.
	$newconf["abbreviate_chart_labels"] = $boolarray[$_POST["NEW_ABBREVIATE_CHART_LABELS"]];
	$newconf["allow_edit_gedcom"] = $boolarray[$_POST["NEW_ALLOW_EDIT_GEDCOM"]];
	$newconf["allow_theme_dropdown"] = $boolarray[$_POST["NEW_ALLOW_THEME_DROPDOWN"]];
	$newconf["alpha_index_lists"] = $boolarray[$_POST["NEW_ALPHA_INDEX_LISTS"]];
	$newconf["auto_generate_thumbs"] = $boolarray[$_POST["NEW_AUTO_GENERATE_THUMBS"]];
	$newconf["calendar_format"] = $_POST["NEW_CALENDAR_FORMAT"];
	$newconf["character_set"] = $_POST["NEW_CHARACTER_SET"];
	$newconf["chart_box_tags"] = $_POST["NEW_CHART_BOX_TAGS"];
	$newconf["check_child_dates"] = $boolarray[$_POST["NEW_CHECK_CHILD_DATES"]];
	$newconf["common_names_add"] = $_POST["NEW_COMMON_NAMES_ADD"];
	$newconf["common_names_remove"] = $_POST["NEW_COMMON_NAMES_REMOVE"];
	$newconf["common_names_threshold"] = $_POST["NEW_COMMON_NAMES_THRESHOLD"];
	$newconf["contact_email"] = $_POST["NEW_CONTACT_EMAIL"];
	$newconf["contact_method"] = $_POST["NEW_CONTACT_METHOD"];
	$newconf["days_to_show_limit"] = $_POST["NEW_DAYS_TO_SHOW_LIMIT"];
	$newconf["default_pedigree_generations"] = $_POST["NEW_DEFAULT_PEDIGREE_GENERATIONS"];
	$newconf["display_jewish_gereshayim"] = $boolarray[$_POST["NEW_DISPLAY_JEWISH_GERESHAYIM"]];
	$newconf["display_jewish_thousands"] = $boolarray[$_POST["NEW_DISPLAY_JEWISH_THOUSANDS"]];
	$newconf["edit_autoclose"] = $boolarray[$_POST["NEW_EDIT_AUTOCLOSE"]];
	$newconf["enable_multi_language"] = $boolarray[$_POST["NEW_ENABLE_MULTI_LANGUAGE"]];
	$newconf["expand_relatives_events"] = $boolarray[$_POST["NEW_EXPAND_RELATIVES_EVENTS"]];
	$newconf["fam_facts_add"] = $_POST["NEW_FAM_FACTS_ADD"];
	$newconf["fam_facts_unique"] = $_POST["NEW_FAM_FACTS_UNIQUE"];
	$newconf["fam_id_prefix"] = $_POST["NEW_FAM_ID_PREFIX"];
	$newconf["favicon"] = $_POST["NEW_FAVICON"];
	$newconf["gedcom_default_tab"] = $_POST["NEW_GEDCOM_DEFAULT_TAB"];
	$newconf["gedcom_id_prefix"] = $_POST["NEW_GEDCOM_ID_PREFIX"];
	$newconf["hide_gedcom_errors"] = $boolarray[$_POST["NEW_HIDE_GEDCOM_ERRORS"]];
	$newconf["hide_live_people"] = $boolarray[$_POST["NEW_HIDE_LIVE_PEOPLE"]];
	$newconf["home_site_text"] = $_POST["NEW_HOME_SITE_TEXT"];
	$newconf["home_site_url"] = $_POST["NEW_HOME_SITE_URL"];
	$newconf["indi_facts_add"] = $_POST["NEW_INDI_FACTS_ADD"];
	$newconf["indi_facts_unique"] = $_POST["NEW_INDI_FACTS_UNIQUE"];
	$newconf["jewish_ashkenaz_pronunciation"] = $boolarray[$_POST["NEW_JEWISH_ASHKENAZ_PRONUNCIATION"]];
	$newconf["language"] = $_POST["GEDCOMLANG"];
	$newconf["link_icons"] = $_POST["NEW_LINK_ICONS"];
	$newconf["max_descendancy_generations"] = $_POST["NEW_MAX_DESCENDANCY_GENERATIONS"];
	$newconf["max_pedigree_generations"] = $_POST["NEW_MAX_PEDIGREE_GENERATIONS"];
	$newconf["media_directory"] = $_POST["NEW_MEDIA_DIRECTORY"];
	$newconf["media_directory_levels"] = $_POST["NEW_MEDIA_DIRECTORY_LEVELS"];
	$newconf["media_external"] = $boolarray[$_POST["NEW_MEDIA_EXTERNAL"]];
	$newconf["media_id_prefix"] = $_POST["NEW_MEDIA_ID_PREFIX"];
	$newconf["meta_audience"] = $_POST["NEW_META_AUDIENCE"];
	$newconf["meta_author"] = $_POST["NEW_META_AUTHOR"];
	$newconf["meta_copyright"] = $_POST["NEW_META_COPYRIGHT"];
	$newconf["meta_description"] = $_POST["NEW_META_DESCRIPTION"];
	$newconf["meta_keywords"] = $_POST["NEW_META_KEYWORDS"];
	$newconf["meta_page_topic"] = $_POST["NEW_META_PAGE_TOPIC"];
	$newconf["meta_page_type"] = $_POST["NEW_META_PAGE_TYPE"];
	$newconf["meta_publisher"] = $_POST["NEW_META_PUBLISHER"];
	$newconf["meta_revisit"] = $_POST["NEW_META_REVISIT"];
	$newconf["meta_robots"] = $_POST["NEW_META_ROBOTS"];
	$newconf["meta_surname_keywords"] = $boolarray[$_POST["NEW_META_SURNAME_KEYWORDS"]];
	$newconf["meta_title"] = $_POST["NEW_META_TITLE"];
	$newconf["multi_media"] = $boolarray[$_POST["NEW_MULTI_MEDIA"]];
	$newconf["pedigree_full_details"] = $boolarray[$_POST["NEW_PEDIGREE_FULL_DETAILS"]];
	$newconf["pedigree_layout"] = $boolarray[$_POST["NEW_PEDIGREE_LAYOUT"]];
	$newconf["pedigree_root_id"] = $_POST["NEW_PEDIGREE_ROOT_ID"];
	$newconf["postal_code"] = $boolarray[$_POST["NEW_POSTAL_CODE"]];
	$newconf["quick_add_facts"] = $_POST["NEW_QUICK_ADD_FACTS"];
	$newconf["quick_add_famfacts"] = $_POST["NEW_QUICK_ADD_FAMFACTS"];
	$newconf["quick_required_facts"] = $_POST["NEW_QUICK_REQUIRED_FACTS"];
	$newconf["quick_required_famfacts"] = $_POST["NEW_QUICK_REQUIRED_FAMFACTS"];
	$newconf["repo_facts_add"] = $_POST["NEW_REPO_FACTS_ADD"];
	$newconf["repo_facts_unique"] = $_POST["NEW_REPO_FACTS_UNIQUE"];
	$newconf["repo_id_prefix"] = $_POST["NEW_REPO_ID_PREFIX"];
	$newconf["require_authentication"] = $boolarray[$_POST["NEW_REQUIRE_AUTHENTICATION"]];
	$newconf["rss_format"] = $_POST["NEW_RSS_FORMAT"];
	$newconf["show_context_help"] = $boolarray[$_POST["NEW_SHOW_CONTEXT_HELP"]];
	$newconf["show_counter"] = $boolarray[$_POST["NEW_SHOW_COUNTER"]];
	$newconf["show_empty_boxes"] = $boolarray[$_POST["NEW_SHOW_EMPTY_BOXES"]];
	$newconf["show_fam_id_numbers"] = $boolarray[$_POST["NEW_SHOW_FAM_ID_NUMBERS"]];
	$newconf["show_gedcom_record"] = $boolarray[$_POST["NEW_SHOW_GEDCOM_RECORD"]];
	$newconf["show_highlight_images"] = $boolarray[$_POST["NEW_SHOW_HIGHLIGHT_IMAGES"]];
	$newconf["show_id_numbers"] = $boolarray[$_POST["NEW_SHOW_ID_NUMBERS"]];
	$newconf["show_lds_at_glance"] = $boolarray[$_POST["NEW_SHOW_LDS_AT_GLANCE"]];
	$newconf["show_married_names"] = $boolarray[$_POST["NEW_SHOW_MARRIED_NAMES"]];
	$newconf["show_parents_age"] = $boolarray[$_POST["NEW_SHOW_PARENTS_AGE"]];
	$newconf["show_pedigree_places"] = $_POST["NEW_SHOW_PEDIGREE_PLACES"];
	$newconf["show_quick_resn"] = $boolarray[$_POST["NEW_SHOW_QUICK_RESN"]];
	$newconf["show_relatives_events"] = $_POST["NEW_SHOW_RELATIVES_EVENTS"];
	$newconf["show_stats"] = $boolarray[$_POST["NEW_SHOW_STATS"]];
	$newconf["sour_facts_add"] = $_POST["NEW_SOUR_FACTS_ADD"];
	$newconf["sour_facts_unique"] = $_POST["NEW_SOUR_FACTS_UNIQUE"];
	$newconf["source_id_prefix"] = $_POST["NEW_SOURCE_ID_PREFIX"];
	$newconf["split_places"] = $boolarray[$_POST["NEW_SPLIT_PLACES"]];
	$newconf["support_method"] = $_POST["NEW_SUPPORT_METHOD"];
	$newconf["thumbnail_width"] = $_POST["NEW_THUMBNAIL_WIDTH"];
	$newconf["underline_name_quotes"] = $boolarray[$_POST["NEW_UNDERLINE_NAME_QUOTES"]];
	$newconf["use_quick_update"] = $boolarray[$_POST["NEW_USE_QUICK_UPDATE"]];
	$newconf["use_rin"] = $boolarray[$_POST["NEW_USE_RIN"]];
	$newconf["use_rtl_functions"] = $boolarray[$_POST["NEW_USE_RTL_FUNCTIONS"]];
	$newconf["use_thumbs_main"] = $boolarray[$_POST["NEW_USE_THUMBS_MAIN"]];
	$newconf["webmaster_email"] = $_POST["NEW_WEBMASTER_EMAIL"];
	$newconf["welcome_text_auth_mode"] = $_POST["NEW_WELCOME_TEXT_AUTH_MODE"];
	$newconf["welcome_text_auth_mode_4"] = $_POST["NEW_WELCOME_TEXT_AUTH_MODE_4"];
	$newconf["welcome_text_cust_head"] = $boolarray[$_POST["NEW_WELCOME_TEXT_CUST_HEAD"]];
	$newconf["word_wrapped_notes"] = $boolarray[$_POST["NEW_WORD_WRAPPED_NOTES"]];
	$newconf["zoom_boxes"] = $_POST["NEW_ZOOM_BOXES"];
	if (file_exists($NTHEME_DIR)) {
	$newconf["theme_dir"] = $_POST["NTHEME_DIR"];
	}
	else {
		$errors = true;
	}
	$newconf["time_limit"] = $_POST["NEW_TIME_LIMIT"];
	$newconf["last_change_email"] = $_POST["NEW_LAST_CHANGE_EMAIL"];
	
	StoreGedcomConfig($newconf);
	// Delete Upcoming Events cache
	if ($_POST["old_DAYS_TO_SHOW_LIMIT"] < $_POST["NEW_DAYS_TO_SHOW_LIMIT"]) {
    	if (is_writable($INDEX_DIRECTORY) and file_exists($INDEX_DIRECTORY.$FILE."_upcoming.php")) {
			unlink ($INDEX_DIRECTORY.$FILE."_upcoming.php");
		}
	}
	foreach($_POST as $key=>$value) {
		if ($key != "path") {
			$key=preg_replace("/NEW_/", "", $key);
			if ($value=='yes') $$key=true;
			else if ($value=='no') $$key=false;
			else $$key=$value;
		}
	}
	WriteToLog("Gedcom configuration for ".$FILE."  updated by >".$gm_username."<", "I", "G", $FILE);
	if (!$errors) {
		$gednews = getUserNews($FILE);
		if (count($gednews)==0) {
			$news = array();
			$news["title"] = "#default_news_title#";
			$news["username"] = $FILE;
			$news["text"] = "#default_news_text#";
			$news["date"] = time()-$_SESSION["timediff"];
			addNews($news);
		}
		if ($source == "upload_form") $check = "upload";
		else if ($source == "add_form") $check = "add";
		else if ($source == "add_new_form") $check = "add_new";
		if (!isset($bakfile)) $bakfile = "";
		if ($source !== "") header("Location: uploadgedcom.php?action=$source&check=$check&step=2&GEDFILENAME=$GEDFILENAME&path=$path&verify=verify_gedcom&bakfile=$bakfile");
		else {
			header("Location: editgedcoms.php");
		}
		exit;
	}
}

//-- output starts here
$temp2 = $THEME_DIR;
$THEME_DIR = $temp;
print_header($gm_lang["gedconf_head"]);
$THEME_DIR = $temp2;
// if (isset($FILE) && !check_for_import($FILE)) print "<span class=\"subheaders\">".$gm_lang["step2"]." ".$gm_lang["configure"]." + ".$gm_lang["ged_gedcom"]."</span><br /><br />";
if (!isset($NTHEME_DIR)) $NTHEME_DIR=$THEME_DIR;
if (!isset($themeselect)) $themeselect="";

?>
<script language="JavaScript" type="text/javascript">
<!--
	var helpWin;
	function helpPopup(which) {
		if ((!helpWin)||(helpWin.closed)) helpWin = window.open('editconfig_help.php?help='+which,'','left=50,top=50,width=500,height=320,resizable=1,scrollbars=1');
		else helpWin.location = 'editconfig_help.php?help='+which;
		return false;
	}
	function getHelp(which) {
		if ((helpWin)&&(!helpWin.closed)) helpWin.location='editconfig_help.php?help='+which;
	}
	function closeHelp() {
		if (helpWin) helpWin.close();
	}
	function show_jewish(dbselect, sid) {
		var sbox = document.getElementById(sid);
		var sbox_style = sbox.style;

		if ((dbselect.options[dbselect.selectedIndex].value=='jewish')
			||(dbselect.options[dbselect.selectedIndex].value=='hebrew')
			||(dbselect.options[dbselect.selectedIndex].value=='jewish_and_gregorian')
			||(dbselect.options[dbselect.selectedIndex].value=='hebrew_and_gregorian')) {
			sbox_style.display='block';
		}
		else {
			sbox_style.display='none';
		}
	}
	var pastefield;
	function paste_id(value) {
		pastefield.value=value;
	}
//-->
</script>

<form enctype="multipart/form-data" method="post" name="configform" action="editconfig_gedcom.php">

<table class="facts_table <?php print $TEXT_DIRECTION ?>">
  <tr>
    <td colspan="2" class="shade3 facts_label center"><?php
    		print "<h2>".$gm_lang["gedconf_head"]." - ";
		if (isset($ged) && isset($GEDCOMS[$ged])) print $GEDCOMS[$ged]["title"];
		else if ($source == "add_form") print $gm_lang["add_gedcom"];
		else if ($source == "upload_form") print $gm_lang["upload_gedcom"];
		else if ($source == "add_new_form") print $gm_lang["add_new_gedcom"];
		print "</h2>";
		print "<a href=\"editgedcoms.php\"><b>";
		print $gm_lang["lang_back_manage_gedcoms"];
		print "</b></a><br /><br />";
    	?>
    </td>
  </tr>
</table>

<input type="hidden" name="action" value="update" />
<input type="hidden" name="source" value="<?php print $source; ?>" />
<input type="hidden" name="oldged" value="<?php print $oldged; ?>" />
<input type="hidden" name="old_DAYS_TO_SHOW_LIMIT" value="<?php print $DAYS_TO_SHOW_LIMIT; ?>" />
<input type="hidden" name="NEW_LAST_CHANGE_EMAIL" value="<?php print $LAST_CHANGE_EMAIL; ?>" />
<?php
	if (!empty($error_msg)) print "<br /><span class=\"error\">".$error_msg."</span><br />\n";
	$i = 0;
?>

<table class="facts_table"><tr><td class="topbottombar <?php print $TEXT_DIRECTION;?>">
<?php
print "<a href=\"javascript: ".$gm_lang["gedcom_conf"]."\" onclick=\"expand_layer('file-options'); return false;\"><img id=\"file-options_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["minus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
print "&nbsp;<a href=\"javascript: ".$gm_lang["gedcom_conf"]."\" onclick=\"expand_layer('file-options'); return false;\">".$gm_lang["gedcom_conf"]."</a>";
?></td></tr></table>
<div id="file-options" style="display: block">
<table class="facts_table">
	<tr>
		<td class="shade2 wrap width20">
		<?php
		if ($source == "upload_form") {
			print "<div class=\"helpicon\">";
			print_help_link("upload_path_help", "qm", "upload_path"); print "</div><div class=\"description\">"; print $gm_lang["upload_path"];
			print "</div></td><td class=\"shade1\">";
			print "<input name=\"GEDCOMPATH\" type=\"file\" size=\"60\" />";
			if (!$filesize = ini_get('upload_max_filesize')) $filesize = "2M";
			print " ( ".$gm_lang["max_upload_size"]." $filesize )";
		}
		else {
			print "<div class=\"helpicon\">";
			print_help_link("gedcom_path_help", "qm", "gedcom_path"); print "</div><div class=\"description\">"; print $gm_lang["gedcom_path"];
			print "</div></td><td class=\"shade1\">";
			?>
		<input type="text" name="GEDCOMPATH" value="<?php print preg_replace('/\\*/', '\\', $GEDCOMPATH);?>" size="40" dir ="ltr" tabindex="<?php $i++; print $i?>" />
		<?php
		}
			if ($GEDCOMPATH != "" || $GEDFILENAME != "") {
				if (!file_exists($path.$GEDFILENAME) && !empty($GEDCOMPATH)) {
					if (strtolower(substr(trim($path.$GEDFILENAME), -4)) != ".ged") $GEDFILENAME .= ".ged";
				}
				if ((!strstr($GEDCOMPATH, "://")) &&(!file_exists($path.$GEDFILENAME))) {
					print "<br /><span class=\"error\">".str_replace("#GEDCOM#", $GEDCOMPATH, $gm_lang["error_header"])."</span>\n";
				}
			}
		?>
		</td>
	</tr>
	<?php if ($source == "upload_form") {?>
	<tr>
		<td class="shade2 wrap width20">
		<div class="helpicon">
		<?php print_help_link("gedcom_path_help", "qm", "gedcom_path"); print "</div><div class=\"description\">"; print $gm_lang["gedcom_path"];?></div></td>
		<td class="shade1">
		<input type="text" name="path" value="<?php print preg_replace('/\\*/', '\\', $path);?>" size="40" dir ="ltr" tabindex="<?php $i++; print $i?>" />
		</td>
	</tr>
	<?php } ?>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("gedcom_title_help", "qm", "gedcom_title", true); print "</div><div class=\"description\">";print print_text("gedcom_title",0,0,false);?></div></td>
		<td class="shade1"><input type="text" name="gedcom_title" dir="ltr" value="<?php print preg_replace("/\"/", "&quot;", PrintReady($gedcom_title)); ?>" size="40" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap">
		<div class="helpicon"><?php print_help_link("LANGUAGE_help", "qm", "LANGUAGE"); print "</div><div class=\"description\">";print $gm_lang["LANGUAGE"];?></div></td>
		<td class="shade1"><input type="hidden" name="changelanguage" value="yes" />
		<select name="GEDCOMLANG" dir="ltr" tabindex="<?php $i++; print $i?>">
		<?php
			foreach ($gm_language as $key=>$value) {
			if ($language_settings[$key]["gm_lang_use"]) {
					print "\n\t\t\t<option value=\"$key\"";
					if ($GEDCOMLANG==$key) print " selected=\"selected\"";
					print ">".$gm_lang[$key]."</option>";
				}
			}
			print "</select>";
			if (!file_exists($INDEX_DIRECTORY . "lang_settings.php")) {
				print "<br /><span class=\"error\">";
				print $gm_lang["LANGUAGE_DEFAULT"];
				print "</span>";
			}
		?>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap">
		<div class="helpicon"><?php print_help_link("CHARACTER_SET_help", "qm", "CHARACTER_SET"); print "</div><div class=\"description\">"; print $gm_lang["CHARACTER_SET"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_CHARACTER_SET" dir="ltr" value="<?php print $CHARACTER_SET?>" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap">
		<div class="helpicon"><?php print_help_link("PEDIGREE_ROOT_ID_help", "qm", "PEDIGREE_ROOT_ID"); print "</div><div class=\"description\">"; print $gm_lang["PEDIGREE_ROOT_ID"];?></div></td>

		<?php
		if ((!empty($GEDCOMPATH))&&(file_exists($path.$GEDFILENAME))&&(!empty($PEDIGREE_ROOT_ID))) {
			//-- the following section of code was modified from the find_record_in_file function of functions.php
			$fpged = fopen($path.$GEDFILENAME, "r");
			if ($fpged) {
				$gid = $PEDIGREE_ROOT_ID;
				$prefix = "";
				$suffix = $gid;
				$ct = preg_match("/^([a-zA-Z]+)/", $gid, $match);
				if ($ct>0) $prefix = $match[1];
				$ct = preg_match("/([\d\.]+)$/", $gid, $match);
				if ($ct>0) $suffix = $match[1];
				//print "prefix:$prefix suffix:$suffix";
				$BLOCK_SIZE = 1024*4;	//-- 4k bytes per read
				$fcontents = "";
				while(!feof($fpged)) {
					$fcontents = fread($fpged, $BLOCK_SIZE);
					//-- convert mac line endings
					$fcontents = preg_replace("/\r(\d)/", "\n$1", $fcontents);
					$ct = preg_match("/0 @(".$prefix."0*".$suffix.")@ INDI/", $fcontents, $match);
					if ($ct>0) {
						$gid = $match[1];
						$pos1 = strpos($fcontents, "0 @$gid@", 0);
						if ($pos1===false) $fcontents = "";
						else {
							$PEDIGREE_ROOT_ID = $gid;
							$pos2 = strpos($fcontents, "\n0", $pos1+1);
							while((!$pos2)&&(!feof($fpged))) {
								$fcontents .= fread($fpged, $BLOCK_SIZE);
								$pos2 = strpos($fcontents, "\n0", $pos1+1);
							}
							if ($pos2) $indirec = substr($fcontents, $pos1, $pos2-$pos1);
							else $indirec = substr($fcontents, $pos1);
							break;
						}
					}
					else $fcontents = "";
				}
				fclose($fpged);
			}
		}
	?>
	<td class="shade1"><input type="text" name="NEW_PEDIGREE_ROOT_ID" id="NEW_PEDIGREE_ROOT_ID" value="<?php print $PEDIGREE_ROOT_ID?>" size="5" tabindex="<?php $i++; print $i?>" />
			<?php
			if ($source == "") {
				if (!empty($indirec)) {
					if ($source == "") {
						$indilist[$PEDIGREE_ROOT_ID]["gedcom"] = $indirec;
						$indilist[$PEDIGREE_ROOT_ID]["names"] = get_indi_names($indirec);
						$indilist[$PEDIGREE_ROOT_ID]["isdead"] = 1;
						$indilist[$PEDIGREE_ROOT_ID]["gedfile"] = $GEDCOM;
						print "\n<span class=\"list_item\">".get_person_name($PEDIGREE_ROOT_ID);
						print_first_major_fact($PEDIGREE_ROOT_ID);
						print "</span>\n";
					}
			    }
			    else {
					print "<span class=\"error\">";
					print $gm_lang["unable_to_find_indi"];
					print "</span>";
				}
				print_findindi_link("NEW_PEDIGREE_ROOT_ID","");
			}
		?>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap">
		<div class="helpicon"><?php print_help_link("CALENDAR_FORMAT_help", "qm", "CALENDAR_FORMAT"); print "</div><div class=\"description\">"; print $gm_lang["CALENDAR_FORMAT"];?></div></td>
		<td class="shade1"><select name="NEW_CALENDAR_FORMAT" tabindex="<?php $i++; print $i?>"  onchange="show_jewish(this, 'hebrew-cal');">
				<option value="gregorian" <?php if ($CALENDAR_FORMAT=='gregorian') print "selected=\"selected\""; ?>><?php print $gm_lang["gregorian"];?></option>
				<option value="julian" <?php if ($CALENDAR_FORMAT=='julian') print "selected=\"selected\""; ?>><?php print $gm_lang["julian"];?></option>
				<option value="french" <?php if ($CALENDAR_FORMAT=='french') print "selected=\"selected\""; ?>><?php print $gm_lang["config_french"];?></option>
				<option value="jewish" <?php if ($CALENDAR_FORMAT=='jewish') print "selected=\"selected\""; ?>><?php print $gm_lang["jewish"];?></option>
				<option value="jewish_and_gregorian" <?php if ($CALENDAR_FORMAT=='jewish_and_gregorian') print "selected=\"selected\""; ?>><?php print $gm_lang["jewish_and_gregorian"];?></option>
				<option value="hebrew" <?php if ($CALENDAR_FORMAT=='hebrew') print "selected=\"selected\""; ?>><?php print $gm_lang["config_hebrew"];?></option>
				<option value="hebrew_and_gregorian" <?php if ($CALENDAR_FORMAT=='hebrew_and_gregorian') print "selected=\"selected\""; ?>><?php print $gm_lang["hebrew_and_gregorian"];?></option>
				<option value="arabic" <?php if ($CALENDAR_FORMAT=='arabic') print "selected=\"selected\""; ?>><?php print $gm_lang["arabic_cal"];?></option>
				<option value="hijri" <?php if ($CALENDAR_FORMAT=='hijri') print "selected=\"selected\""; ?>><?php print $gm_lang["hijri"];?></option>
			</select>
		</td>
	</tr>
	</table>
	<div id="hebrew-cal" style="display: <?php if (($CALENDAR_FORMAT=='jewish')||($CALENDAR_FORMAT=='jewish_and_gregorian')||($CALENDAR_FORMAT=='hebrew')||($CALENDAR_FORMAT=='hebrew_and_gregorian')) print 'block'; else print 'none';?>;">
	<table class="facts_table">
	<tr>
		<td class="shade2 wrap width20">
		<div class="helpicon"><?php print_help_link("DISPLAY_JEWISH_THOUSANDS_help", "qm", "DISPLAY_JEWISH_THOUSANDS"); print "</div><div class=\"description\">"; print $gm_lang["DISPLAY_JEWISH_THOUSANDS"];?></div></td>
		<td class="shade1"><select name="NEW_DISPLAY_JEWISH_THOUSANDS">
				<option value="yes" <?php if ($DISPLAY_JEWISH_THOUSANDS) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$DISPLAY_JEWISH_THOUSANDS) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap">
		<div class="helpicon"><?php print_help_link("DISPLAY_JEWISH_GERESHAYIM_help", "qm", "DISPLAY_JEWISH_GERESHAYIM"); print "</div><div class=\"description\">"; print $gm_lang["DISPLAY_JEWISH_GERESHAYIM"];?></div></td>
		<td class="shade1"><select name="NEW_DISPLAY_JEWISH_GERESHAYIM">
				<option value="yes" <?php if ($DISPLAY_JEWISH_GERESHAYIM) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$DISPLAY_JEWISH_GERESHAYIM) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap">
		<div class="helpicon"><?php print_help_link("JEWISH_ASHKENAZ_PRONUNCIATION_help", "qm", "JEWISH_ASHKENAZ_PRONUNCIATION"); print "</div><div class=\"description\">"; print $gm_lang["JEWISH_ASHKENAZ_PRONUNCIATION"];?></div></td>
		<td class="shade1"><select name="NEW_JEWISH_ASHKENAZ_PRONUNCIATION">
				<option value="yes" <?php if ($JEWISH_ASHKENAZ_PRONUNCIATION) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$JEWISH_ASHKENAZ_PRONUNCIATION) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	</table>
	</div>
	<table class="facts_table">
	<tr>
		<td class="shade2 wrap width20">
		<div class="helpicon"><?php print_help_link("USE_RTL_FUNCTIONS_help", "qm", "USE_RTL_FUNCTIONS"); print "</div><div class=\"description\">"; print $gm_lang["USE_RTL_FUNCTIONS"];?></div></td>
		<td class="shade1"><select name="NEW_USE_RTL_FUNCTIONS" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($USE_RTL_FUNCTIONS) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$USE_RTL_FUNCTIONS) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap">
		<div class="helpicon"><?php print_help_link("USE_RIN_help", "qm", "USE_RIN"); print "</div><div class=\"description\">"; print $gm_lang["USE_RIN"];?></div></td>
		<td class="shade1"><select name="NEW_USE_RIN" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($USE_RIN) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$USE_RIN) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap">
		<div class="helpicon"><?php print_help_link("GEDCOM_ID_PREFIX_help", "qm", "GEDCOM_ID_PREFIX"); print "</div><div class=\"description\">"; print $gm_lang["GEDCOM_ID_PREFIX"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_GEDCOM_ID_PREFIX" dir="ltr" value="<?php print $GEDCOM_ID_PREFIX?>" size="5" tabindex="<?php $i++; print $i?>" />
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap">
		<div class="helpicon"><?php print_help_link("FAM_ID_PREFIX_help", "qm", "FAM_ID_PREFIX"); print "</div><div class=\"description\">"; print $gm_lang["FAM_ID_PREFIX"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_FAM_ID_PREFIX" dir="ltr" value="<?php print $FAM_ID_PREFIX?>" size="5" tabindex="<?php $i++; print $i?>" />
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("SOURCE_ID_PREFIX_help", "qm", "SOURCE_ID_PREFIX"); print "</div><div class=\"description\">"; print $gm_lang["SOURCE_ID_PREFIX"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_SOURCE_ID_PREFIX" dir="ltr" value="<?php print $SOURCE_ID_PREFIX?>" size="5" tabindex="<?php $i++; print $i?>" />
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("REPO_ID_PREFIX_help", "qm", "REPO_ID_PREFIX"); print "</div><div class=\"description\">"; print $gm_lang["REPO_ID_PREFIX"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_REPO_ID_PREFIX" dir="ltr" value="<?php print $REPO_ID_PREFIX?>" size="5" tabindex="<?php $i++; print $i?>" />
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("MEDIA_ID_PREFIX_help", "qm", "MEDIA_ID_PREFIX"); print "</div><div class=\"description\">";print $gm_lang["MEDIA_ID_PREFIX"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_MEDIA_ID_PREFIX" dir="ltr" value="<?php print $MEDIA_ID_PREFIX?>" size="5" tabindex="<?php $i++; print $i?>" />
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("time_limit_help", "qm", "PHP_TIME_LIMIT"); print "</div><div class=\"description\">"; print $gm_lang["PHP_TIME_LIMIT"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_TIME_LIMIT" value="<?php print $TIME_LIMIT?>" size="5" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
</table>
</div>

<table class="facts_table"><tr><td class="topbottombar <?php print $TEXT_DIRECTION;?>">
<?php
print "<a href=\"javascript: ".$gm_lang["media_conf"]."\" onclick=\"expand_layer('config-media');return false;\"><img id=\"config-media_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
print "&nbsp;<a href=\"javascript: ".$gm_lang["media_conf"]."\" onclick=\"expand_layer('config-media');return false;\">".$gm_lang["media_conf"]."</a>";
?></td></tr></table>
<div id="config-media" style="display: none">
<table class="facts_table">
	<tr>
		<td class="shade2 wrap width20"><div class="helpicon"><?php print_help_link("MULTI_MEDIA_help", "qm", "MULTI_MEDIA"); print "</div><div class=\"description\">"; print $gm_lang["MULTI_MEDIA"];?></div></td>
		<td class="shade1"><select name="NEW_MULTI_MEDIA" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($MULTI_MEDIA) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$MULTI_MEDIA) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("MEDIA_EXTERNAL_help", "qm", "MEDIA_EXTERNAL"); print "</div><div class=\"description\">"; print $gm_lang["MEDIA_EXTERNAL"];?></div></td>
		<td class="shade1"><select name="NEW_MEDIA_EXTERNAL" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($MEDIA_EXTERNAL) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$MEDIA_EXTERNAL) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("MEDIA_DIRECTORY_help", "qm", "MEDIA_DIRECTORY"); print "</div><div class=\"description\">"; print $gm_lang["MEDIA_DIRECTORY"];?></div></td>
		<td class="shade1"><input type="text" size="50" name="NEW_MEDIA_DIRECTORY" value="<?php print $MEDIA_DIRECTORY?>" dir="ltr" tabindex="<?php $i++; print $i?>" />
		<?php
		if(preg_match("/.*[a-zA-Z]{1}:.*/",$MEDIA_DIRECTORY)>0) print "<span class=\"error\">".$gm_lang["media_drive_letter"]."</span>\n";
		?>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("MEDIA_DIRECTORY_LEVELS_help", "qm", "MEDIA_DIRECTORY_LEVELS"); print "</div><div class=\"description\">"; print $gm_lang["MEDIA_DIRECTORY_LEVELS"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_MEDIA_DIRECTORY_LEVELS" value="<?php print $MEDIA_DIRECTORY_LEVELS?>" size="5" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("THUMBNAIL_WIDTH_help", "qm", "THUMBNAIL_WIDTH"); print "</div><div class=\"description\">"; print $gm_lang["THUMBNAIL_WIDTH"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_THUMBNAIL_WIDTH" value="<?php print $THUMBNAIL_WIDTH?>" size="5" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("AUTO_GENERATE_THUMBS_help", "qm", "AUTO_GENERATE_THUMBS"); print "</div><div class=\"description\">"; print $gm_lang["AUTO_GENERATE_THUMBS"];?></div></td>
		<td class="shade1"><select name="NEW_AUTO_GENERATE_THUMBS" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($AUTO_GENERATE_THUMBS) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$AUTO_GENERATE_THUMBS) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("SHOW_HIGHLIGHT_IMAGES_help", "qm", "SHOW_HIGHLIGHT_IMAGES"); print "</div><div class=\"description\">"; print $gm_lang["SHOW_HIGHLIGHT_IMAGES"];?></div></td>
		<td class="shade1"><select name="NEW_SHOW_HIGHLIGHT_IMAGES" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($SHOW_HIGHLIGHT_IMAGES) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$SHOW_HIGHLIGHT_IMAGES) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("USE_THUMBS_MAIN_help", "qm", "USE_THUMBS_MAIN"); print "</div><div class=\"description\">"; print $gm_lang["USE_THUMBS_MAIN"];?></div></td>
		<td class="shade1"><select name="NEW_USE_THUMBS_MAIN" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($USE_THUMBS_MAIN) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$USE_THUMBS_MAIN) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
</table>
</div>

<table class="facts_table"><tr><td class="topbottombar <?php print $TEXT_DIRECTION;?>">
<?php
print "<a href=\"javascript: ".$gm_lang["accpriv_conf"]."\" onclick=\"expand_layer('access-options');return false;\"><img id=\"access-options_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
print "&nbsp;<a href=\"javascript: ".$gm_lang["accpriv_conf"]."\" onclick=\"expand_layer('access-options');return false;\">".$gm_lang["accpriv_conf"]."</a>";
?></td></tr></table>
<div id="access-options" style="display: none">
<table class="facts_table">
	<tr>
		<td class="shade2 wrap width20"><div class="helpicon"><?php print_help_link("HIDE_LIVE_PEOPLE_help", "qm", "HIDE_LIVE_PEOPLE"); print "</div><div class=\"description\">"; print $gm_lang["HIDE_LIVE_PEOPLE"];?></div></td>
		<td class="shade1"><select name="NEW_HIDE_LIVE_PEOPLE" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($HIDE_LIVE_PEOPLE) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$HIDE_LIVE_PEOPLE) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("REQUIRE_AUTHENTICATION_help", "qm", "REQUIRE_AUTHENTICATION"); print "</div><div class=\"description\">"; print $gm_lang["REQUIRE_AUTHENTICATION"];?></div></td>
		<td class="shade1"><select name="NEW_REQUIRE_AUTHENTICATION" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($REQUIRE_AUTHENTICATION) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$REQUIRE_AUTHENTICATION) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("WELCOME_TEXT_AUTH_MODE_help", "qm", "WELCOME_TEXT_AUTH_MODE"); print "</div><div class=\"description\">"; print $gm_lang["WELCOME_TEXT_AUTH_MODE"];?></div></td>
		<td class="shade1"><select name="NEW_WELCOME_TEXT_AUTH_MODE" tabindex="<?php $i++; print $i?>">
				<option value="1" <?php if ($WELCOME_TEXT_AUTH_MODE=='1') print "selected=\"selected\""; ?>><?php print $gm_lang["WELCOME_TEXT_AUTH_MODE_OPT1"];?></option>
				<option value="2" <?php if ($WELCOME_TEXT_AUTH_MODE=='2') print "selected=\"selected\""; ?>><?php print $gm_lang["WELCOME_TEXT_AUTH_MODE_OPT2"];?></option>
				<option value="3" <?php if ($WELCOME_TEXT_AUTH_MODE=='3') print "selected=\"selected\""; ?>><?php print $gm_lang["WELCOME_TEXT_AUTH_MODE_OPT3"];?></option>
				<option value="4" <?php if ($WELCOME_TEXT_AUTH_MODE=='4') print "selected=\"selected\""; ?>><?php print $gm_lang["WELCOME_TEXT_AUTH_MODE_OPT4"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("WELCOME_TEXT_AUTH_MODE_CUST_HEAD_help", "qm", "WELCOME_TEXT_AUTH_MODE_CUST_HEAD"); print "</div><div class=\"description\">"; print $gm_lang["WELCOME_TEXT_AUTH_MODE_CUST_HEAD"];?></div></td>
		<td class="shade1"><select name="NEW_WELCOME_TEXT_CUST_HEAD" tabindex="<?php $i++; print $i?>" >
				<option value="yes" <?php if ($WELCOME_TEXT_CUST_HEAD) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$WELCOME_TEXT_CUST_HEAD) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("WELCOME_TEXT_AUTH_MODE_CUST_help", "qm", "WELCOME_TEXT_AUTH_MODE_CUST"); print "</div><div class=\"description\">"; print $gm_lang["WELCOME_TEXT_AUTH_MODE_CUST"];?></div></td>
		<td class="shade1"><textarea name="NEW_WELCOME_TEXT_AUTH_MODE_4" rows="5" cols="60" dir="ltr" tabindex="<?php $i++; print $i?>"><?php print  $WELCOME_TEXT_AUTH_MODE_4 ?></textarea>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("CHECK_CHILD_DATES_help", "qm", "CHECK_CHILD_DATES"); print "</div><div class=\"description\">"; print $gm_lang["CHECK_CHILD_DATES"];?></div></td>
		<td class="shade1"><select name="NEW_CHECK_CHILD_DATES" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($CHECK_CHILD_DATES) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$CHECK_CHILD_DATES) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
</table>
</div>

<table class="facts_table"><tr><td class="topbottombar <?php print $TEXT_DIRECTION;?>">
<?php
print "<a href=\"javascript: ".$gm_lang["displ_conf"]."\" onclick=\"expand_layer('layout-options');return false;\"><img id=\"layout-options_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
print "&nbsp;<a href=\"javascript: ".$gm_lang["displ_conf"]."\" onclick=\"expand_layer('layout-options');return false;\">".$gm_lang["displ_conf"]."</a>";
?></td></tr></table>
<div id="layout-options" style="display: none">

<table class="facts_table"><tr><td class="subbar">
<?php
print "<a href=\"javascript: ".$gm_lang["displ_names_conf"]."\" onclick=\"expand_layer('layout-options2');return false;\"><img id=\"layout-options2_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
print "&nbsp;<a href=\"javascript: ".$gm_lang["displ_names_conf"]."\" onclick=\"expand_layer('layout-options2');return false;\">".$gm_lang["displ_names_conf"]."</a>";
?></td></tr></table>
<div id="layout-options2" style="display: none">
<table class="facts_table">
	<tr>
		<td class="shade2 wrap width20"><div class="helpicon"><?php print_help_link("PEDIGREE_FULL_DETAILS_help", "qm", "PEDIGREE_FULL_DETAILS"); print "</div><div class=\"description\">"; print $gm_lang["PEDIGREE_FULL_DETAILS"];?></div></td>
		<td class="shade1"><select name="NEW_PEDIGREE_FULL_DETAILS" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($PEDIGREE_FULL_DETAILS) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$PEDIGREE_FULL_DETAILS) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("ABBREVIATE_CHART_LABELS_help", "qm", "ABBREVIATE_CHART_LABELS"); print "</div><div class=\"description\">"; print $gm_lang["ABBREVIATE_CHART_LABELS"];?></div></td>
		<td class="shade1"><select name="NEW_ABBREVIATE_CHART_LABELS" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($ABBREVIATE_CHART_LABELS) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$ABBREVIATE_CHART_LABELS) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("SHOW_PARENTS_AGE_help", "qm", "SHOW_PARENTS_AGE"); print "</div><div class=\"description\">"; print $gm_lang["SHOW_PARENTS_AGE"];?></div></td>
		<td class="shade1"><select name="NEW_SHOW_PARENTS_AGE" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($SHOW_PARENTS_AGE) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$SHOW_PARENTS_AGE) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("SHOW_LDS_AT_GLANCE_help", "qm", "SHOW_LDS_AT_GLANCE"); print "</div><div class=\"description\">"; print $gm_lang["SHOW_LDS_AT_GLANCE"];?></div></td>
		<td class="shade1"><select name="NEW_SHOW_LDS_AT_GLANCE" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($SHOW_LDS_AT_GLANCE) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$SHOW_LDS_AT_GLANCE) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("CHART_BOX_TAGS_help", "qm", "CHART_BOX_TAGS"); print "</div><div class=\"description\">"; print $gm_lang["CHART_BOX_TAGS"];?></div></td>
		<td class="shade1">
			<input type="text" size="50" name="NEW_CHART_BOX_TAGS" value="<?php print $CHART_BOX_TAGS?>" dir="ltr" tabindex="<?php $i++; print $i?>" />
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("SHOW_MARRIED_NAMES_help", "qm", "SHOW_MARRIED_NAMES"); print "</div><div class=\"description\">"; print $gm_lang["SHOW_MARRIED_NAMES"];?></div></td>
		<td class="shade1"><select name="NEW_SHOW_MARRIED_NAMES" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($SHOW_MARRIED_NAMES) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$SHOW_MARRIED_NAMES) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("UNDERLINE_NAME_QUOTES_help", "qm", "UNDERLINE_NAME_QUOTES"); print "</div><div class=\"description\">"; print $gm_lang["UNDERLINE_NAME_QUOTES"];?></div></td>
		<td class="shade1"><select name="NEW_UNDERLINE_NAME_QUOTES" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($UNDERLINE_NAME_QUOTES) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$UNDERLINE_NAME_QUOTES) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("SHOW_ID_NUMBERS_help", "qm", "SHOW_ID_NUMBERS"); print "</div><div class=\"description\">"; print $gm_lang["SHOW_ID_NUMBERS"];?></div></td>
		<td class="shade1"><select name="NEW_SHOW_ID_NUMBERS" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($SHOW_ID_NUMBERS) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$SHOW_ID_NUMBERS) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("SHOW_FAM_ID_NUMBERS_help", "qm", "SHOW_FAM_ID_NUMBERS"); print "</div><div class=\"description\">"; print $gm_lang["SHOW_FAM_ID_NUMBERS"];?></div></td>
        <td class="shade1"><select name="NEW_SHOW_FAM_ID_NUMBERS" tabindex="<?php $i++; print $i?>">
			<option value="yes" <?php if ($SHOW_FAM_ID_NUMBERS) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
            <option value="no" <?php if (!$SHOW_FAM_ID_NUMBERS) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
            </select>
        </td>
    </tr>
</table>
</div>

<table class="facts_table"><tr><td class="subbar">
<?php
print "<a href=\"javascript: ".$gm_lang["displ_comsurn_conf"]."\" onclick=\"expand_layer('layout-options3');return false;\"><img id=\"layout-options3_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
print "&nbsp;<a href=\"javascript: ".$gm_lang["displ_comsurn_conf"]."\" onclick=\"expand_layer('layout-options3');return false;\">".$gm_lang["displ_comsurn_conf"]."</a>";
?></td></tr></table>
<div id="layout-options3" style="display: none">
<table class="facts_table">
	<tr>
		<td class="shade2 wrap width20"><div class="helpicon"><?php print_help_link("COMMON_NAMES_THRESHOLD_help", "qm", "COMMON_NAMES_THRESHOLD"); print "</div><div class=\"description\">"; print $gm_lang["COMMON_NAMES_THRESHOLD"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_COMMON_NAMES_THRESHOLD" value="<?php print $COMMON_NAMES_THRESHOLD?>" size="5" tabindex="<?php $i++; print $i?>" /></td>
	</tr>

	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("COMMON_NAMES_ADD_help", "qm", "COMMON_NAMES_ADD"); print "</div><div class=\"description\">"; print $gm_lang["COMMON_NAMES_ADD"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_COMMON_NAMES_ADD" dir="ltr" value="<?php print $COMMON_NAMES_ADD?>" size="50" tabindex="<?php $i++; print $i?>" /></td>
	</tr>

	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("COMMON_NAMES_REMOVE_help", "qm", "COMMON_NAMES_REMOVE"); print "</div><div class=\"description\">"; print $gm_lang["COMMON_NAMES_REMOVE"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_COMMON_NAMES_REMOVE" dir="ltr" value="<?php print $COMMON_NAMES_REMOVE?>" size="50" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
</table>
</div>

<?php // Display and Layout
?>
<table class="facts_table"><tr><td class="subbar <?php print $TEXT_DIRECTION;?>">
<?php
print "<a href=\"javascript: ".$gm_lang["displ_layout_conf"]."\" onclick=\"expand_layer('layout-options4');return false;\"><img id=\"layout-options4_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
print "&nbsp;<a href=\"javascript: ".$gm_lang["displ_layout_conf"]."\" onclick=\"expand_layer('layout-options4');return false;\">".$gm_lang["displ_layout_conf"]."</a>";
?></td></tr></table>
<div id="layout-options4" style="display: none">
<table class="facts_table">
	<tr>
		<td class="shade2 wrap width20"><div class="helpicon"><?php print_help_link("DEFAULT_PEDIGREE_GENERATIONS_help", "qm", "DEFAULT_PEDIGREE_GENERATIONS"); print "</div><div class=\"description\">"; print $gm_lang["DEFAULT_PEDIGREE_GENERATIONS"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_DEFAULT_PEDIGREE_GENERATIONS" value="<?php print $DEFAULT_PEDIGREE_GENERATIONS?>" size="5" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("MAX_PEDIGREE_GENERATIONS_help", "qm", "MAX_PEDIGREE_GENERATIONS"); print "</div><div class=\"description\">"; print $gm_lang["MAX_PEDIGREE_GENERATIONS"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_MAX_PEDIGREE_GENERATIONS" value="<?php print $MAX_PEDIGREE_GENERATIONS?>" size="5" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("MAX_DESCENDANCY_GENERATIONS_help", "qm", "MAX_DESCENDANCY_GENERATIONS"); print "</div><div class=\"description\">"; print $gm_lang["MAX_DESCENDANCY_GENERATIONS"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_MAX_DESCENDANCY_GENERATIONS" value="<?php print $MAX_DESCENDANCY_GENERATIONS?>" size="5" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("PEDIGREE_LAYOUT_help", "qm", "PEDIGREE_LAYOUT"); print "</div><div class=\"description\">"; print $gm_lang["PEDIGREE_LAYOUT"];?></div></td>
		<td class="shade1"><select name="NEW_PEDIGREE_LAYOUT" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($PEDIGREE_LAYOUT) print "selected=\"selected\""; ?>><?php print $gm_lang["landscape"];?></option>
				<option value="no" <?php if (!$PEDIGREE_LAYOUT) print "selected=\"selected\""; ?>><?php print $gm_lang["portrait"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("SHOW_PEDIGREE_PLACES_help", "qm", "SHOW_PEDIGREE_PLACES"); print "</div><div class=\"description\">"; print $gm_lang["SHOW_PEDIGREE_PLACES"];?></div></td>
		<td class="shade1"><input type="text" size="5" name="NEW_SHOW_PEDIGREE_PLACES" value="<?php print $SHOW_PEDIGREE_PLACES; ?>" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("ZOOM_BOXES_help", "qm", "ZOOM_BOXES"); print "</div><div class=\"description\">"; print $gm_lang["ZOOM_BOXES"];?></div></td>
		<td class="shade1"><select name="NEW_ZOOM_BOXES" tabindex="<?php $i++; print $i?>">
				<option value="disabled" <?php if ($ZOOM_BOXES=='disabled') print "selected=\"selected\""; ?>><?php print $gm_lang["disabled"];?></option>
				<option value="mouseover" <?php if ($ZOOM_BOXES=='mouseover') print "selected=\"selected\""; ?>><?php print $gm_lang["mouseover"];?></option>
				<option value="mousedown" <?php if ($ZOOM_BOXES=='mousedown') print "selected=\"selected\""; ?>><?php print $gm_lang["mousedown"];?></option>
				<option value="click" <?php if ($ZOOM_BOXES=='click') print "selected=\"selected\""; ?>><?php print $gm_lang["click"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("LINK_ICONS_help", "qm", "LINK_ICONS"); print "</div><div class=\"description\">"; print $gm_lang["LINK_ICONS"];?></div></td>
		<td class="shade1"><select name="NEW_LINK_ICONS" tabindex="<?php $i++; print $i?>">
				<option value="disabled" <?php if ($LINK_ICONS=='disabled') print "selected=\"selected\""; ?>><?php print $gm_lang["disabled"];?></option>
				<option value="mouseover" <?php if ($LINK_ICONS=='mouseover') print "selected=\"selected\""; ?>><?php print $gm_lang["mouseover"];?></option>
				<option value="click" <?php if ($LINK_ICONS=='click') print "selected=\"selected\""; ?>><?php print $gm_lang["click"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("GEDCOM_DEFAULT_TAB_help", "qm", "GEDCOM_DEFAULT_TAB"); print "</div><div class=\"description\">"; print $gm_lang["GEDCOM_DEFAULT_TAB"];?></div></td>
		<td class="shade1"><select name="NEW_GEDCOM_DEFAULT_TAB" tabindex="<?php $i++; print $i?>">
				<option value="0" <?php if ($GEDCOM_DEFAULT_TAB==0) print "selected=\"selected\""; ?>><?php print $gm_lang["personal_facts"];?></option>
				<option value="1" <?php if ($GEDCOM_DEFAULT_TAB==1) print "selected=\"selected\""; ?>><?php print $gm_lang["notes"];?></option>
				<option value="2" <?php if ($GEDCOM_DEFAULT_TAB==2) print "selected=\"selected\""; ?>><?php print $gm_lang["ssourcess"];?></option>
				<option value="3" <?php if ($GEDCOM_DEFAULT_TAB==3) print "selected=\"selected\""; ?>><?php print $gm_lang["media"];?></option>
				<option value="4" <?php if ($GEDCOM_DEFAULT_TAB==4) print "selected=\"selected\""; ?>><?php print $gm_lang["relatives"];?></option>
				<option value="5" <?php if ($GEDCOM_DEFAULT_TAB==5) print "selected=\"selected\""; ?>><?php print $gm_lang["all"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("SHOW_RELATIVES_EVENTS_help", "qm", "SHOW_RELATIVES_EVENTS"); print "</div><div class=\"description\">"; print $gm_lang["SHOW_RELATIVES_EVENTS"];?></div></td>
		<td class="shade1">
			<input type="hidden" name="NEW_SHOW_RELATIVES_EVENTS" value="<?php echo $SHOW_RELATIVES_EVENTS?>" />
<?php
$previous="_DEAT_";
print "<table>";
foreach ($factarray as $factkey=>$factlabel) {
	$f6=substr($factkey,0,6);
	if ($f6=="_BIRT_" or $f6=="_MARR_" or $f6=="_DEAT_") {
		if ($f6=="_BIRT_") print "<tr>";
		if ($f6=="_MARR_" and $previous!="_BIRT_") print "<tr><td>&nbsp;</td>";
		if ($f6=="_DEAT_" and $previous=="_DEAT_") print "<tr><td>&nbsp;</td>";
		if ($f6=="_DEAT_" and $previous!="_MARR_") print "<td>&nbsp;</td>";
		print "\n<td><input type=\"checkbox\" name=\"SHOW_RELATIVES_EVENTS_checkbox\" value=\"".$factkey."\"";
		if (strstr($SHOW_RELATIVES_EVENTS,$factkey)) print " checked=\"checked\"";
		print " onchange=\"var old=document.configform.NEW_SHOW_RELATIVES_EVENTS.value; if (this.checked) old+=','+this.value; else old=old.replace(/".$factkey."/g,''); old=old.replace(/[,]+/gi,','); old=old.replace(/^[,]/gi,''); old=old.replace(/[,]$/gi,''); document.configform.NEW_SHOW_RELATIVES_EVENTS.value=old\" ";
		print " /> ".$factlabel."</td>";
		if ($f6=="_DEAT_") print "</tr>";
		$previous=$f6;
	}
}
print "</table>";
print "<table><tr>";
?>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("EXPAND_RELATIVES_EVENTS_help", "qm", "EXPAND_RELATIVES_EVENTS"); print "</div><div class=\"description\">"; print $gm_lang["EXPAND_RELATIVES_EVENTS"];?></div></td>
		<td class="shade1">
			<select name="NEW_EXPAND_RELATIVES_EVENTS" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($EXPAND_RELATIVES_EVENTS) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$EXPAND_RELATIVES_EVENTS) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
<?php
print "</tr></table>";
?>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("POSTAL_CODE_help", "qm", "POSTAL_CODE"); print "</div><div class=\"description\">"; print $gm_lang["POSTAL_CODE"];?></div></td>
		<td class="shade1"><select name="NEW_POSTAL_CODE" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($POSTAL_CODE) print "selected=\"selected\""; ?>><?php print ucfirst($gm_lang["after"]);?></option>
				<option value="no" <?php if (!$POSTAL_CODE) print "selected=\"selected\""; ?>><?php print ucfirst($gm_lang["before"]);?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("ALPHA_INDEX_LISTS_help", "qm", "ALPHA_INDEX_LISTS"); print "</div><div class=\"description\">"; print $gm_lang["ALPHA_INDEX_LISTS"];?></div></td>
		<td class="shade1"><select name="NEW_ALPHA_INDEX_LISTS" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($ALPHA_INDEX_LISTS) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$ALPHA_INDEX_LISTS) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
</table>
</div>


<table class="facts_table"><tr><td class="subbar <?php print $TEXT_DIRECTION;?>">
<?php
print "<a href=\"javascript: ".$gm_lang["displ_hide_conf"]."\" onclick=\"expand_layer('layout-options5');return false;\"><img id=\"layout-options5_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
print "&nbsp;<a href=\"javascript: ".$gm_lang["displ_hide_conf"]."\" onclick=\"expand_layer('layout-options5');return false;\">".$gm_lang["displ_hide_conf"]."</a>";
?></td></tr></table>
<div id="layout-options5" style="display: none">
<table class="facts_table">
	<tr>
		<td class="shade2 wrap width20"><div class="helpicon"><?php print_help_link("DAYS_TO_SHOW_LIMIT_help", "qm", "DAYS_TO_SHOW_LIMIT"); print "</div><div class=\"description\">"; print $gm_lang["DAYS_TO_SHOW_LIMIT"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_DAYS_TO_SHOW_LIMIT" value="<?php print $DAYS_TO_SHOW_LIMIT?>" size="2" tabindex="<?php $i++; print $i?>" /></td>
	</tr>

	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("SHOW_EMPTY_BOXES_help", "qm", "SHOW_EMPTY_BOXES"); print "</div><div class=\"description\">"; print $gm_lang["SHOW_EMPTY_BOXES"];?></div></td>
		<td class="shade1"><select name="NEW_SHOW_EMPTY_BOXES" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($SHOW_EMPTY_BOXES) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$SHOW_EMPTY_BOXES) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("SHOW_GEDCOM_RECORD_help", "qm", "SHOW_GEDCOM_RECORD"); print "</div><div class=\"description\">"; print $gm_lang["SHOW_GEDCOM_RECORD"];?></div></td>
		<td class="shade1"><select name="NEW_SHOW_GEDCOM_RECORD" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($SHOW_GEDCOM_RECORD) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$SHOW_GEDCOM_RECORD) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("HIDE_GEDCOM_ERRORS_help", "qm", "HIDE_GEDCOM_ERRORS"); print "</div><div class=\"description\">"; print $gm_lang["HIDE_GEDCOM_ERRORS"];?></div></td>
		<td class="shade1"><select name="NEW_HIDE_GEDCOM_ERRORS" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($HIDE_GEDCOM_ERRORS) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$HIDE_GEDCOM_ERRORS) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("WORD_WRAPPED_NOTES_help", "qm", "WORD_WRAPPED_NOTES"); print "</div><div class=\"description\">"; print $gm_lang["WORD_WRAPPED_NOTES"];?></div></td>
		<td class="shade1"><select name="NEW_WORD_WRAPPED_NOTES" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($WORD_WRAPPED_NOTES) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$WORD_WRAPPED_NOTES) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("FAVICON_help", "qm", "FAVICON"); print "</div><div class=\"description\">"; print $gm_lang["FAVICON"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_FAVICON" value="<?php print $FAVICON?>" size="40" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("SHOW_COUNTER_help", "qm", "SHOW_COUNTER"); print "</div><div class=\"description\">"; print $gm_lang["SHOW_COUNTER"];?></div></td>
		<td class="shade1"><select name="NEW_SHOW_COUNTER" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($SHOW_COUNTER) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$SHOW_COUNTER) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("SHOW_STATS_help", "qm", "SHOW_STATS"); print "</div><div class=\"description\">"; print $gm_lang["SHOW_STATS"];?></div></td>
		<td class="shade1"><select name="NEW_SHOW_STATS" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($SHOW_STATS) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$SHOW_STATS) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
</table>
</div>
</div>


<?php // Edit Options
?>
<table class="facts_table"><tr><td class="topbottombar <?php print $TEXT_DIRECTION;?>">
<?php
print "<a href=\"javascript: ".$gm_lang["editopt_conf"]."\" onclick=\"expand_layer('edit-options');return false;\"><img id=\"edit-options_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
print "&nbsp;<a href=\"javascript: ".$gm_lang["editopt_conf"]."\" onclick=\"expand_layer('edit-options');return false;\">".$gm_lang["editopt_conf"]."</a>";
?></td></tr></table>
<div id="edit-options" style="display: none">
<table class="facts_table">
	<tr>
		<td class="shade2 wrap width20"><div class="helpicon"><?php print_help_link("ALLOW_EDIT_GEDCOM_help", "qm", "ALLOW_EDIT_GEDCOM"); print "</div><div class=\"description\">"; print $gm_lang["ALLOW_EDIT_GEDCOM"];?></div></td>
		<td class="shade1"><select name="NEW_ALLOW_EDIT_GEDCOM" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($ALLOW_EDIT_GEDCOM) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$ALLOW_EDIT_GEDCOM) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("INDI_FACTS_ADD_help", "qm", "INDI_FACTS_ADD"); print "</div><div class=\"description\">"; print $gm_lang["INDI_FACTS_ADD"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_INDI_FACTS_ADD" value="<?php print $INDI_FACTS_ADD; ?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("INDI_FACTS_UNIQUE_help", "qm", "INDI_FACTS_UNIQUE"); print "</div><div class=\"description\">"; print $gm_lang["INDI_FACTS_UNIQUE"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_INDI_FACTS_UNIQUE" value="<?php print $INDI_FACTS_UNIQUE; ?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("FAM_FACTS_ADD_help", "qm", "FAM_FACTS_ADD"); print "</div><div class=\"description\">"; print $gm_lang["FAM_FACTS_ADD"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_FAM_FACTS_ADD" value="<?php print $FAM_FACTS_ADD; ?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("FAM_FACTS_UNIQUE_help", "qm", "FAM_FACTS_UNIQUE"); print "</div><div class=\"description\">"; print $gm_lang["FAM_FACTS_UNIQUE"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_FAM_FACTS_UNIQUE" value="<?php print $FAM_FACTS_UNIQUE; ?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("SOUR_FACTS_ADD_help", "qm", "SOUR_FACTS_ADD"); print "</div><div class=\"description\">"; print $gm_lang["SOUR_FACTS_ADD"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_SOUR_FACTS_ADD" value="<?php print $SOUR_FACTS_ADD; ?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("SOUR_FACTS_UNIQUE_help", "qm", "SOUR_FACTS_UNIQUE"); print "</div><div class=\"description\">"; print $gm_lang["SOUR_FACTS_UNIQUE"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_SOUR_FACTS_UNIQUE" value="<?php print $SOUR_FACTS_UNIQUE; ?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("REPO_FACTS_ADD_help", "qm", "REPO_FACTS_ADD"); print "</div><div class=\"description\">"; print $gm_lang["REPO_FACTS_ADD"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_REPO_FACTS_ADD" value="<?php print $REPO_FACTS_ADD; ?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("REPO_FACTS_UNIQUE_help", "qm", "REPO_FACTS_UNIQUE"); print "</div><div class=\"description\">"; print $gm_lang["REPO_FACTS_UNIQUE"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_REPO_FACTS_UNIQUE" value="<?php print $REPO_FACTS_UNIQUE; ?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("EDIT_AUTOCLOSE_help", "qm", "EDIT_AUTOCLOSE"); print "</div><div class=\"description\">"; print $gm_lang["EDIT_AUTOCLOSE"];?></div></td>
		<td class="shade1"><select name="NEW_EDIT_AUTOCLOSE" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($EDIT_AUTOCLOSE) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$EDIT_AUTOCLOSE) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("SPLIT_PLACES_help", "qm", "SPLIT_PLACES"); print "</div><div class=\"description\">"; print $gm_lang["SPLIT_PLACES"];?></div></td>
		<td class="shade1"><select name="NEW_SPLIT_PLACES" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($SPLIT_PLACES) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$SPLIT_PLACES) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("USE_QUICK_UPDATE_help", "qm", "USE_QUICK_UPDATE", true); print "</div><div class=\"description\">"; print print_text("USE_QUICK_UPDATE",0,0,false);?></div></td>
		<td class="shade1"><select name="NEW_USE_QUICK_UPDATE" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($USE_QUICK_UPDATE) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$USE_QUICK_UPDATE) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("SHOW_QUICK_RESN_help", "qm", "SHOW_QUICK_RESN", true); print "</div><div class=\"description\">"; print print_text("SHOW_QUICK_RESN",0,0,false);?></div></td>
		<td class="shade1"><select name="NEW_SHOW_QUICK_RESN" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($SHOW_QUICK_RESN) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$SHOW_QUICK_RESN) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("QUICK_ADD_FACTS_help", "qm", "QUICK_ADD_FACTS"); print "</div><div class=\"description\">"; print $gm_lang["QUICK_ADD_FACTS"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_QUICK_ADD_FACTS" value="<?php print $QUICK_ADD_FACTS?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("QUICK_REQUIRED_FACTS_help", "qm", "QUICK_REQUIRED_FACTS"); print "</div><div class=\"description\">"; print $gm_lang["QUICK_REQUIRED_FACTS"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_QUICK_REQUIRED_FACTS" value="<?php print $QUICK_REQUIRED_FACTS?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("QUICK_ADD_FAMFACTS_help", "qm", "QUICK_ADD_FAMFACTS"); print "</div><div class=\"description\">"; print $gm_lang["QUICK_ADD_FAMFACTS"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_QUICK_ADD_FAMFACTS" value="<?php print $QUICK_ADD_FAMFACTS?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("QUICK_REQUIRED_FAMFACTS_help", "qm", "QUICK_REQUIRED_FAMFACTS"); print "</div><div class=\"description\">"; print $gm_lang["QUICK_REQUIRED_FAMFACTS"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_QUICK_REQUIRED_FAMFACTS" value="<?php print $QUICK_REQUIRED_FAMFACTS?>" size="40" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
</table>
</div>


<?php // User Options
?>
<table class="facts_table"><tr><td class="topbottombar <?php print $TEXT_DIRECTION;?>">
<?php
print "<a href=\"javascript: ".$gm_lang["useropt_conf"]."\" onclick=\"expand_layer('user-options');return false;\"><img id=\"user-options_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
print "&nbsp;<a href=\"javascript: ".$gm_lang["useropt_conf"]."\" onclick=\"expand_layer('user-options');return false;\">".$gm_lang["useropt_conf"]."</a>";
?></td></tr></table>
<div id="user-options" style="display: none">
<table class="facts_table">
	<tr>
		<td class="shade2 wrap width20"><div class="helpicon"><?php print_help_link("ENABLE_MULTI_LANGUAGE_help", "qm", "ENABLE_MULTI_LANGUAGE"); print "</div><div class=\"description\">"; print $gm_lang["ENABLE_MULTI_LANGUAGE"];?></div></td>
		<td class="shade1"><select name="NEW_ENABLE_MULTI_LANGUAGE" tabindex="<?php $i++; print $i?>" >
				<option value="yes" <?php if ($ENABLE_MULTI_LANGUAGE) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$ENABLE_MULTI_LANGUAGE) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
			<?php
	        if (!file_exists($INDEX_DIRECTORY . "lang_settings.php")) {
	        	print "<br /><span class=\"error\">";
	        	print $gm_lang["LANGUAGE_DEFAULT"];
	        	print "</span>";
         	}
		    ?>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("show_context_help_help", "qm", "show_contexthelp"); print "</div><div class=\"description\">"; print $gm_lang["show_contexthelp"];?></div></td>
		<td class="shade1"><select name="NEW_SHOW_CONTEXT_HELP" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($SHOW_CONTEXT_HELP) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$SHOW_CONTEXT_HELP) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("THEME_DIR_help", "qm", "THEME_DIR"); print "</div><div class=\"description\">"; print $gm_lang["THEME_DIR"];?></div></td>
		<td class="shade1">
			<select name="themeselect" dir="ltr" tabindex="<?php $i++; print $i?>"  onchange="document.configform.NTHEME_DIR.value=document.configform.themeselect.options[document.configform.themeselect.selectedIndex].value;">
				<?php
					$themes = get_theme_names();
					foreach($themes as $indexval => $themedir) {
						print "<option value=\"".$themedir["dir"]."\"";
						if ($themedir["dir"] == $NTHEME_DIR) print " selected=\"selected\"";
						print ">".$themedir["name"]."</option>\n";
					}
				?>
				<option value="themes/" <?php if($themeselect=="themes//") print "selected=\"selected\""; ?>><?php print $gm_lang["other_theme"]; ?></option>
			</select>
			<input type="text" name="NTHEME_DIR" value="<?php print $NTHEME_DIR?>" size="40" dir="ltr" tabindex="<?php $i++; print $i?>" />
	<?php
	if (!file_exists($NTHEME_DIR)) {
		print "<span class=\"error\">$NTHEME_DIR ";
		print $gm_lang["does_not_exist"];
		print "</span>\n";
		$NTHEME_DIR=$THEME_DIR;
	}
	?>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("ALLOW_THEME_DROPDOWN_help", "qm", "ALLOW_THEME_DROPDOWN"); print "</div><div class=\"description\">"; print $gm_lang["ALLOW_THEME_DROPDOWN"];?></div></td>
		<td class="shade1"><select name="NEW_ALLOW_THEME_DROPDOWN" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($ALLOW_THEME_DROPDOWN) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$ALLOW_THEME_DROPDOWN) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
</table>
</div>



<table class="facts_table"><tr><td class="topbottombar <?php print $TEXT_DIRECTION;?>">
<?php
print "<a href=\"javascript: ".$gm_lang["contact_conf"]."\" onclick=\"expand_layer('contact-options');return false;\"><img id=\"contact-options_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
print "&nbsp;<a href=\"javascript: ".$gm_lang["contact_conf"]."\" onclick=\"expand_layer('contact-options');return false;\">".$gm_lang["contact_conf"]."</a>";
?></td></tr></table>
<div id="contact-options" style="display: none">
<table class="facts_table">
	<tr>
		<td class="shade2 wrap width20"><div class="helpicon"><?php print_help_link("CONTACT_EMAIL_help", "qm", "CONTACT_EMAIL"); print "</div><div class=\"description\">"; print $gm_lang["CONTACT_EMAIL"];?></div></td>
		<td class="shade1"><select name="NEW_CONTACT_EMAIL" tabindex="<?php $i++; print $i?>">
		<?php
			if ($CONTACT_EMAIL=="you@yourdomain.com") $CONTACT_EMAIL = $gm_username;
			$users = getUsers();
			foreach($users as $indexval => $user) {
				if ($user["verified_by_admin"]=="yes") {
					print "<option value=\"".$user["username"]."\"";
					if ($CONTACT_EMAIL==$user["username"]) print " selected=\"selected\"";
					print ">".$user["lastname"].", ".$user["firstname"]." - ".$user["username"]."</option>\n";
				}
			}
		?>
		</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("CONTACT_METHOD_help", "qm", "CONTACT_METHOD"); print "</div><div class=\"description\">"; print $gm_lang["CONTACT_METHOD"];?></div></td>
		<td class="shade1"><select name="NEW_CONTACT_METHOD" tabindex="<?php $i++; print $i?>">
		<?php if ($GM_STORE_MESSAGES) { ?>
				<option value="messaging" <?php if ($CONTACT_METHOD=='messaging') print "selected=\"selected\""; ?>><?php print $gm_lang["messaging"];?></option>
				<option value="messaging2" <?php if ($CONTACT_METHOD=='messaging2') print "selected=\"selected\""; ?>><?php print $gm_lang["messaging2"];?></option>
		<?php } else { ?>
				<option value="messaging3" <?php if ($CONTACT_METHOD=='messaging3') print "selected=\"selected\""; ?>><?php print $gm_lang["messaging3"];?></option>
		<?php } ?>
				<option value="mailto" <?php if ($CONTACT_METHOD=='mailto') print "selected=\"selected\""; ?>><?php print $gm_lang["mailto"];?></option>
				<option value="none" <?php if ($CONTACT_METHOD=='none') print "selected=\"selected\""; ?>><?php print $gm_lang["no_messaging"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("WEBMASTER_EMAIL_help", "qm", "WEBMASTER_EMAIL"); print "</div><div class=\"description\">"; print $gm_lang["WEBMASTER_EMAIL"];?></div></td>
		<td class="shade1"><select name="NEW_WEBMASTER_EMAIL" tabindex="<?php $i++; print $i?>">
		<?php
			$users = getUsers();
			if ($WEBMASTER_EMAIL=="webmaster@yourdomain.com") $WEBMASTER_EMAIL = $gm_username;
			uasort($users, "usersort");
			foreach($users as $indexval => $user) {
				if (userIsAdmin($user["username"])) {
					print "<option value=\"".$user["username"]."\"";
					if ($WEBMASTER_EMAIL==$user["username"]) print " selected=\"selected\"";
					print ">".$user["lastname"].", ".$user["firstname"]." - ".$user["username"]."</option>\n";
				}
			}
		?>
		</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("SUPPORT_METHOD_help", "qm", "SUPPORT_METHOD"); print "</div><div class=\"description\">"; print $gm_lang["SUPPORT_METHOD"];?></div></td>
		<td class="shade1"><select name="NEW_SUPPORT_METHOD" tabindex="<?php $i++; print $i?>">
		<?php if ($GM_STORE_MESSAGES) { ?>
				<option value="messaging" <?php if ($SUPPORT_METHOD=='messaging') print "selected=\"selected\""; ?>><?php print $gm_lang["messaging"];?></option>
				<option value="messaging2" <?php if ($SUPPORT_METHOD=='messaging2') print "selected=\"selected\""; ?>><?php print $gm_lang["messaging2"];?></option>
		<?php } else { ?>
				<option value="messaging3" <?php if ($SUPPORT_METHOD=='messaging3') print "selected=\"selected\""; ?>><?php print $gm_lang["messaging3"];?></option>
		<?php } ?>
				<option value="mailto" <?php if ($SUPPORT_METHOD=='mailto') print "selected=\"selected\""; ?>><?php print $gm_lang["mailto"];?></option>
				<option value="none" <?php if ($SUPPORT_METHOD=='none') print "selected=\"selected\""; ?>><?php print $gm_lang["no_messaging"];?></option>
			</select>
		</td>
	</tr>
</table>
</div>
<table class="facts_table"><tr><td class="topbottombar <?php print $TEXT_DIRECTION;?>">
<?php
print "<a href=\"javascript: ".$gm_lang["meta_conf"]."\" onclick=\"expand_layer('config-meta');return false;\"><img id=\"config-meta_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
print "&nbsp;<a href=\"javascript: ".$gm_lang["meta_conf"]."\" onclick=\"expand_layer('config-meta');return false;\">".$gm_lang["meta_conf"]."</a>";
?></td></tr></table>
<div id="config-meta" style="display: none">
<table class="facts_table">
	<tr>
		<td class="shade2 wrap width20"><div class="helpicon"><?php print_help_link("HOME_SITE_URL_help", "qm", "HOME_SITE_URL"); print "</div><div class=\"description\">"; print $gm_lang["HOME_SITE_URL"];?></div></td>
		<td class="shade1"><input type="text" name="NEW_HOME_SITE_URL" value="<?php print $HOME_SITE_URL?>" size="50" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("HOME_SITE_TEXT_help", "qm", "HOME_SITE_TEXT"); print "</div><div class=\"description\">"; print $gm_lang["HOME_SITE_TEXT"];?></div></td>
		<td class="shade1"><input type="text" dir="ltr" name="NEW_HOME_SITE_TEXT" value="<?php print htmlspecialchars($HOME_SITE_TEXT);?>" size="50" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("META_AUTHOR_help", "qm", "META_AUTHOR"); print "</div><div class=\"description\">"; print $gm_lang["META_AUTHOR"];?></div></td>
		<td class="shade1"><input type="text" dir="ltr" name="NEW_META_AUTHOR" value="<?php print $META_AUTHOR?>" tabindex="<?php $i++; print $i?>" /><br />
		<?php print print_text("META_AUTHOR_descr",0,0,false); ?></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("META_PUBLISHER_help", "qm", "META_PUBLISHER"); print "</div><div class=\"description\">"; print $gm_lang["META_PUBLISHER"];?></div></td>
		<td class="shade1"><input type="text" dir="ltr" name="NEW_META_PUBLISHER" value="<?php print $META_PUBLISHER?>" tabindex="<?php $i++; print $i?>" /><br />
		<?php print print_text("META_PUBLISHER_descr",0,0,false); ?></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("META_COPYRIGHT_help", "qm", "META_COPYRIGHT"); print "</div><div class=\"description\">"; print $gm_lang["META_COPYRIGHT"];?></div></td>
		<td class="shade1"><input type="text" dir="ltr" name="NEW_META_COPYRIGHT" value="<?php print $META_COPYRIGHT?>" tabindex="<?php $i++; print $i?>" /><br />
		<?php print print_text("META_COPYRIGHT_descr",0,0,false); ?></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("META_DESCRIPTION_help", "qm", "META_DESCRIPTION"); print "</div><div class=\"description\">"; print $gm_lang["META_DESCRIPTION"];?></div></td>
		<td class="shade1"><input type="text" dir="ltr" name="NEW_META_DESCRIPTION" value="<?php print $META_DESCRIPTION?>" tabindex="<?php $i++; print $i?>" /><br />
		<?php print $gm_lang["META_DESCRIPTION_descr"]; ?></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("META_PAGE_TOPIC_help", "qm", "META_PAGE_TOPIC"); print "</div><div class=\"description\">"; print $gm_lang["META_PAGE_TOPIC"];?></div></td>
		<td class="shade1"><input type="text" dir="ltr" name="NEW_META_PAGE_TOPIC" value="<?php print $META_PAGE_TOPIC?>" tabindex="<?php $i++; print $i?>" /><br />
		<?php print $gm_lang["META_PAGE_TOPIC_descr"]; ?></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("META_AUDIENCE_help", "qm", "META_AUDIENCE"); print "</div><div class=\"description\">"; print $gm_lang["META_AUDIENCE"];?></div></td>
		<td class="shade1"><input type="text" dir="ltr" name="NEW_META_AUDIENCE" value="<?php print $META_AUDIENCE?>" tabindex="<?php $i++; print $i?>" /><br />
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("META_PAGE_TYPE_help", "qm", "META_PAGE_TYPE"); print "</div><div class=\"description\">"; print $gm_lang["META_PAGE_TYPE"];?></div></td>
		<td class="shade1"><input type="text" dir="ltr" name="NEW_META_PAGE_TYPE" value="<?php print $META_PAGE_TYPE?>" tabindex="<?php $i++; print $i?>" /><br />
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("META_ROBOTS_help", "qm", "META_ROBOTS"); print "</div><div class=\"description\">"; print $gm_lang["META_ROBOTS"];?></div></td>
		<td class="shade1"><input type="text" dir="ltr" name="NEW_META_ROBOTS" value="<?php print $META_ROBOTS?>" tabindex="<?php $i++; print $i?>" /><br />
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("META_REVISIT_help", "qm", "META_REVISIT"); print "</div><div class=\"description\">"; print $gm_lang["META_REVISIT"];?></div></td>
		<td class="shade1"><input type="text" dir="ltr" name="NEW_META_REVISIT" value="<?php print $META_REVISIT?>" tabindex="<?php $i++; print $i?>" /><br />
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("META_KEYWORDS_help", "qm", "META_KEYWORDS"); print "</div><div class=\"description\">"; print $gm_lang["META_KEYWORDS"];?></div></td>
		<td class="shade1"><input type="text" dir="ltr" name="NEW_META_KEYWORDS" value="<?php print $META_KEYWORDS?>" tabindex="<?php $i++; print $i?>" size="75" /><br />
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("META_SURNAME_KEYWORDS_help", "qm", "META_SURNAME_KEYWORDS"); print "</div><div class=\"description\">"; print $gm_lang["META_SURNAME_KEYWORDS"];?></div></td>
		<td class="shade1"><select name="NEW_META_SURNAME_KEYWORDS" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($META_SURNAME_KEYWORDS) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$META_SURNAME_KEYWORDS) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("META_TITLE_help", "qm", "META_TITLE"); print "</div><div class=\"description\">"; print $gm_lang["META_TITLE"];?></div></td>
		<td class="shade1"><input type="text" dir="ltr" name="NEW_META_TITLE" value="<?php print $META_TITLE?>" tabindex="<?php $i++; print $i?>" size="75" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php print_help_link("RSS_FORMAT_help", "qm", "RSS_FORMAT"); print "</div><div class=\"description\">"; print $gm_lang["RSS_FORMAT"];?></div></td>
		<td class="shade1"><select name="NEW_RSS_FORMAT" dir="ltr" tabindex="<?php $i++; print $i?>">
				<option value="RSS0.91" <?php if ($RSS_FORMAT=="RSS0.91") print "selected=\"selected\""; ?>>RSS 0.91</option>
				<option value="RSS1.0" <?php if ($RSS_FORMAT=="RSS1.0") print "selected=\"selected\""; ?>>RSS 1.0</option>
				<option value="RSS2.0" <?php if ($RSS_FORMAT=="RSS2.0") print "selected=\"selected\""; ?>>RSS 2.0</option>
				<option value="ATOM" <?php if ($RSS_FORMAT=="ATOM") print "selected=\"selected\""; ?>>ATOM</option>
				<option value="ATOM0.3" <?php if ($RSS_FORMAT=='messaging') print "selected=\"selected\""; ?>>ATOM 0.3</option>
			</select>
		</td>
	</tr>
</table>
</div>
<table class="facts_table" border="0">
<tr><td class="center">
<input type="submit" tabindex="<?php $i++; print $i?>" value="<?php print $gm_lang["save_config"]?>" onclick="closeHelp();" />
&nbsp;&nbsp;
<input type="reset" tabindex="<?php $i++; print $i?>" value="<?php print $gm_lang["reset"]?>" /><br />
</td></tr>
</table>
</form>
<br /><!--<?php if (isset($FILE) && !check_for_import($FILE)) print_text("return_editconfig_gedcom"); ?><br />-->
<?php if (count($GEDCOMS)==0) { ?>
<script language="JavaScript" type="text/javascript">
	helpPopup('welcome_new_help');
</script>
<?php
}
// NOTE: Put the focus on the GEDCOM title field since the GEDCOM path actually
// NOTE: needs no changing
?>
<script language="JavaScript" type="text/javascript">
	<?php if ($source == "") print "document.configform.gedcom_title.focus();";
	else print "document.configform.GEDCOMPATH.focus();";?>
</script>
<?php
print_footer();
?>
