<?php
/**
 * UI for online updating of the gedcom config file.
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
 * This Page Is Valid XHTML 1.0 Transitional! > 22 August 2005
 *
 * @author GM Development Team
 * @package Genmod
 * @subpackage Admin
 * @version $Id$
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

if (!$gm_user->userGedcomAdmin()) {
	header("Location: editgedcoms.php");
	exit;
}

// Incoming variables:
//
// $source: Set when loaded from editgedcoms.php
// 		add_form: 		add new gedcom already on server
// 		upload form: 	add new gedcom from upload
// 		add_new_form: 	add new gedcom from scratch
// 		reupload_form: 	re-upload and import an existing gedcom
// 		<no value>: 	edit existing settings
if (!isset($source)) $source="";		

// $GEDCOMPATH: Set from input field for upload. Path and filename. Sets $_FILES['GEDCOMPATH']
//
// $GEDFILENAME: Name of the gedcom file (filename and extension).
if (!isset($GEDFILENAME)) $GEDFILENAME = "";

// $gedid: ID of the gedcom being edited
if (!isset($gedid)) $gedid = false;

// $oldgedid: ID of the gedcom sent with filled in form
if (empty($oldgedid)) $oldgedid = "";
else $gedid = $oldgedid;

// $action: either nothing (edit/add) or "update" for processing the form
if (!isset($action)) $action="";

if (!isset($_POST)) $_POST = $HTTP_POST_VARS;
$error_msg = "";

// Initial
if (empty($action)) {
	
	switch($source){
		
		// Add a gedcom with the file already on the server
		case("add_form"):
			$GEDCOMPATH = "";
			$gedcom_title = "";
			break;
			
		// Add a new gedcom with upload
		case("upload_form"):
			$GEDCOMPATH = "";
			$gedcom_title = "";
			break;
			
		// Add a new gedcom from scratch
		case("add_new_form"):
			$GEDCOMPATH = "";
			$gedcom_title = "";
			break;
			
		// Re-upload a gedcom file for an existing gedcom
		case("reupload_form"):
			SwitchGedcom($gedid);
			$GEDCOMPATH = $GEDCOMS[$gedid]["path"];
			$path = AdminFunctions::CalculateGedcomPath($GEDCOMPATH);
			$GEDFILENAME = $GEDCOMS[$gedid]["gedcom"];
			$gedcom_title = $GEDCOMS[$gedid]["title"];
			$oldgedid = $GEDCOMS[$gedid]["id"];
			break;
			
		// No source, so it must be an edit of an existing gedcom setting
		default:
			SwitchGedcom($gedid);
			$GEDCOMPATH = $GEDCOMS[$gedid]["path"];
			$path = AdminFunctions::CalculateGedcomPath($GEDCOMPATH);
			$GEDFILENAME = $GEDCOMS[$gedid]["gedcom"];
			$gedcom_title = $GEDCOMS[$gedid]["title"];
			$oldgedid = $GEDCOMS[$gedid]["id"];
			break;
	}
}
else {
	switch($source){
		
		// Add a gedcom with the file already on the server
		case("add_form"):
			$path = AdminFunctions::CalculateGedcomPath($GEDCOMPATH);
			$GEDFILENAME = basename($GEDCOMPATH);
			if (!file_exists($path.$GEDFILENAME)) $action = "";
			if (get_id_from_gedcom($GEDFILENAME)) {
				$error_msg = GM_LANG_gedcom_exists;
				$action = "";
			}
			break;
			
		// Add a new gedcom with upload
		case("upload_form"):
			$path = INDEX_DIRECTORY;
			$GEDFILENAME = basename($GEDCOMPATH['name']);
			if (!AdminFunctions::CheckUploadedGedcom($GEDFILENAME)) {
				$action = "";
				$error_msg = GM_LANG_upload_error;
				break;
			} 
			$GEDFILENAME = AdminFunctions::MoveUploadedGedcom($GEDFILENAME, $path);
			break;
			
		// Add a new gedcom from scratch
		case("add_new_form"):
			$path = AdminFunctions::CalculateGedcomPath($GEDCOMPATH);
			$GEDFILENAME = basename($GEDCOMPATH);
			// Check of a gedcom with that name already exists in the DB
			if (get_id_from_gedcom($GEDFILENAME)) {
				$error_msg = GM_LANG_gedcom_exists;
				$action = "";
			}
			// if not, check if a file with the same name already exists on that place
			else {
				if (file_exists($path.$GEDFILENAME)) {
					$error_msg = GM_LANG_file_exists;
					$action = "";
				}
				// if not, check if we can crate the file there
				else {
					if (!MediaFS::DirIsWritable($path, false)) {
						$error_msg = GM_LANG_cannot_create_file;
						$action = "";
					}
				}
			}
			break;
			
		// Re-upload a gedcom file for an existing gedcom
		case("reupload_form"):
			SwitchGedcom($gedid);
			$GEDCOMPATH = $GEDCOMS[$gedid]["path"];
			$path = AdminFunctions::CalculateGedcomPath($GEDCOMPATH);
			$GEDFILENAME = $GEDCOMS[$gedid]["gedcom"];
			if (!AdminFunctions::CheckUploadedGedcom($GEDFILENAME)) {
				$action = "";
				$error_msg = GM_LANG_upload_error;
				break;
			}
			$GEDFILENAME = AdminFunctions::MoveUploadedGedcom($GEDFILENAME, $path);
			break;
			
		// No source, so it must be an edit of an existing gedcom setting
		default:
			SwitchGedcom($gedid);
			$GEDCOMPATH = $GEDCOMS[$gedid]["path"];
			// recalculate the path
			$path = AdminFunctions::CalculateGedcomPath($GEDCOMPATH);
			$GEDFILENAME = $GEDCOMS[$gedid]["gedcom"];
			break;
	}
}
	
/*
// Process (re-)upload or add
if (isset($GEDCOMPATH)) {
	
	// NOTE: Check if we are uploading a file and retrieve the filename
	if (isset($_FILES['GEDCOMPATH'])) {
		if (filesize($_FILES['GEDCOMPATH']['tmp_name'])!= 0) $GEDCOMPATH = $_FILES['GEDCOMPATH']['name'];
	}
	else {
		// TODO: We cannot upload the file. Too big or something else.
	}

	// NOTE: Extract the GEDCOM filename
	$GEDFILENAME = basename($GEDCOMPATH);
	
	// NOTE: Check if it is a zipfile, only extract it if it's not an upload (already there)
	if (strstr(strtolower(trim($GEDFILENAME)), ".zip")==".zip") {
		// Get the filename of the gedcom file. It is extracted in the folder of $GEDCOMPATH (if empty: INDEX_DIRECTORY)
		if ($source == "add_form") $GEDFILENAME = AdminFunctions::GetGedFromZip($GEDCOMPATH);
	}
	
	// Now GEDFILENAME either has it's name extracted from the ZIP, or from the entry in GEDCOMPATH
	// Check if there is an extension, if not: add it.
	if (strtolower(substr(trim($GEDFILENAME), -4)) != ".ged" && strtolower(substr(trim($GEDFILENAME), -4)) != ".zip") $GEDFILENAME .= ".ged";
	
	// NOTE: Check if there is a path in GEDCOMPATH (only the case if the file already is on the server)
	$path = AdminFunctions::CalculateGedcomPath($GEDCOMPATH);
	// At this point, $path represents the path to the gedcom file. 
	
	// If a file is uploaded, place it in the previously determinated $path folder
	$ctupload = count($_FILES);
	if ($ctupload > 0) {
		// NOTE: When uploading a file check if it doesn't exist yet, either in $GEDCOMS or on disk
		if (!isset($GEDCOMS[get_id_from_gedcom($GEDFILENAME)]) || !file_exists($path.$GEDFILENAME)) {
			if (move_uploaded_file($_FILES['GEDCOMPATH']['tmp_name'], $path.$GEDFILENAME)) {
				WriteToLog("EditConfigGedcom-> Gedcom ".$path.$GEDFILENAME." uploaded", "I", "S");
			}
		}
		// NOTE: If the file exists we will make a backup file
		else if (file_exists($path.$GEDFILENAME)) {
			if (file_exists($path.$GEDFILENAME.".old")) unlink($path.$GEDFILENAME.".old");
				copy($path.$GEDFILENAME, $path.$GEDFILENAME.".old");
				unlink($path.$GEDFILENAME);
			move_uploaded_file($_FILES['GEDCOMPATH']['tmp_name'], $path.$GEDFILENAME);
		}
		// A bit odd to have this extracted here, but if it works... 
		// Get the gedcom name from the ZIP 
		if (strstr(strtolower(trim($GEDFILENAME)), ".zip") == ".zip") $GEDFILENAME = AdminFunctions::GetGedFromZip($path.$GEDFILENAME);
	}
	
	$ged = $GEDFILENAME;
	$gedid = get_id_from_gedcom($ged);
}
// $gedid is false when it doesn't exist yet in the system. 
if ($gedid) {
	if (isset($GEDCOMS[$gedid])) {
		$GEDCOMPATH = $GEDCOMS[$gedid]["path"];
		// recalculate the path
		$path = AdminFunctions::CalculateGedcomPath($GEDCOMPATH);
		$GEDFILENAME = $GEDCOMS[$gedid]["gedcom"];
		if (!isset($gedcom_title)) $gedcom_title = $GEDCOMS[$gedid]["title"];
		$gedid = $GEDCOMS[$gedid]["id"];
		$FILE = $GEDFILENAME;
		$oldgedid = $gedid;
	}
	else {
		if (!isset($_POST["GEDCOMPATH"])) {
			$GEDCOMPATH = "";
			$gedcom_title = "";
		}
	}
}
// We have to fill in the form with new values, so some must exist (empty)
else {
	$GEDCOMPATH = "";
	$gedcom_title = "";
}
*/
$USERLANG = $LANGUAGE;
$temp = GedcomConfig::$THEME_DIR;

// If it's a new gedcom, read the default settings (auto calculate and set the new GEDCOMID)
if (!isset($gedid) || !isset($GEDCOMS[$gedid])) {
	GedcomConfig::ReadGedcomConfig(0);
	$gedid = GedcomConfig::$GEDCOMID;
}
else SwitchGedcom($gedid);

if (is_null(GedcomConfig::$GEDCOMLANG)) GedcomConfig::$GEDCOMLANG = $LANGUAGE;
$LANGUAGE = $USERLANG;

if ($action=="update") {
	$errors = false;
	if (!isset($_POST)) $_POST = $HTTP_POST_VARS;
	$FILE = $GEDFILENAME;
	$newgedcom=false;

	$gedarray = array();
	$gedarray["gedcom"] = $FILE;
	if (!empty($gedcom_title)) $gedarray["title"] = $gedcom_title;
	else if (!empty($_POST["gedcom_title"])) $gedarray["title"] = $_POST["gedcom_title"];
	else $gedarray["title"] = str_replace("#GEDCOMFILE#", $GEDFILENAME, GM_LANG_new_gedcom_title);
	$gedarray["path"] = $path.$GEDFILENAME;
	$gedarray["id"] = $gedid;

	// Remove slashes
	if (isset($_POST["NEW_COMMON_NAMES_ADD"])) $_POST["NEW_COMMON_NAMES_ADD"] = stripslashes($_POST["NEW_COMMON_NAMES_ADD"]);
	if (isset($_POST["NEW_COMMON_NAMES_REMOVE"])) $_POST["NEW_COMMON_NAMES_REMOVE"] = stripslashes($_POST["NEW_COMMON_NAMES_REMOVE"]);
	// Check that add/remove common surnames are separated by [,;] blank
	$_POST["NEW_COMMON_NAMES_REMOVE"] = preg_replace("/[,;]\b/", ", ", $_POST["NEW_COMMON_NAMES_REMOVE"]);
	$_POST["NEW_COMMON_NAMES_ADD"] = preg_replace("/[,;]\b/", ", ", $_POST["NEW_COMMON_NAMES_ADD"]);
	GedcomConfig::$COMMON_NAMES_THRESHOLD = $_POST["NEW_COMMON_NAMES_THRESHOLD"];
	GedcomConfig::$COMMON_NAMES_ADD = $_POST["NEW_COMMON_NAMES_ADD"];
	GedcomConfig::$COMMON_NAMES_REMOVE = $_POST["NEW_COMMON_NAMES_REMOVE"];
	$gedarray["commonsurnames"] = "";
	$GEDCOMS[$gedid] = $gedarray;
	AdminFunctions::StoreGedcoms();

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
	
	// Check the nick delimiter values
	if (empty($_POST["NEW_NICK_DELIM0"]) && empty($_POST["NEW_NICK_DELIM0"])) {
		$_POST["NEW_NICK_DELIM0"] = "(";
		$_POST["NEW_NICK_DELIM1"] = ")";
	}
	else if (empty($_POST["NEW_NICK_DELIM0"]) && !empty($_POST["NEW_NICK_DELIM1"])) $_POST["NEW_NICK_DELIM0"] = $_POST["NEW_NICK_DELIM1"];
	else if (empty($_POST["NEW_NICK_DELIM1"]) && !empty($_POST["NEW_NICK_DELIM0"])) $_POST["NEW_NICK_DELIM1"] = $_POST["NEW_NICK_DELIM0"];

	$newconf = array();
	$newconf["gedcom"] = $FILE;
	$newconf["gedcomid"] = get_id_from_gedcom($FILE);
	$newconf["name_from_gedcom"] = $boolarray[GedcomConfig::$NAME_FROM_GEDCOM]; // -- This value is used but defaults to false.
	$newconf["abbreviate_chart_labels"] = $boolarray[$_POST["NEW_ABBREVIATE_CHART_LABELS"]];
	$newconf["allow_edit_gedcom"] = $boolarray[$_POST["NEW_ALLOW_EDIT_GEDCOM"]];
	$newconf["allow_theme_dropdown"] = $boolarray[$_POST["NEW_ALLOW_THEME_DROPDOWN"]];
	$newconf["alpha_index_lists"] = $_POST["NEW_ALPHA_INDEX_LISTS"];
	$newconf["auto_generate_thumbs"] = $boolarray[$_POST["NEW_AUTO_GENERATE_THUMBS"]];
	$newconf["bcc_webmaster"] = $boolarray[$_POST["NEW_BCC_WEBMASTER"]];
	$newconf["calendar_format"] = $_POST["NEW_CALENDAR_FORMAT"];
	$newconf["character_set"] = $_POST["NEW_CHARACTER_SET"];
	$newconf["chart_box_tags"] = $_POST["NEW_CHART_BOX_TAGS"];
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
	$newconf["edit_gedcom_record"] = $_POST["NEW_EDIT_GEDCOM_RECORD"];
	$newconf["enable_multi_language"] = $boolarray[$_POST["NEW_ENABLE_MULTI_LANGUAGE"]];
	$newconf["expand_relatives_events"] = $boolarray[$_POST["NEW_EXPAND_RELATIVES_EVENTS"]];
	$newconf["fam_facts_add"] = $_POST["NEW_FAM_FACTS_ADD"];
	$newconf["fam_facts_unique"] = $_POST["NEW_FAM_FACTS_UNIQUE"];
	$newconf["fam_quick_addfacts"] = $_POST["NEW_FAM_QUICK_ADDFACTS"];
	$newconf["fam_id_prefix"] = $_POST["NEW_FAM_ID_PREFIX"];
	$newconf["favicon"] = $_POST["NEW_FAVICON"];
	$newconf["gedcom_default_tab"] = $_POST["NEW_GEDCOM_DEFAULT_TAB"];
	$newconf["gedcom_id_prefix"] = $_POST["NEW_GEDCOM_ID_PREFIX"];
	$newconf["gedcomlang"] = $_POST["NEW_GEDCOMLANG"];
	$newconf["hide_gedcom_errors"] = $boolarray[$_POST["NEW_HIDE_GEDCOM_ERRORS"]];
	$newconf["home_site_text"] = $_POST["NEW_HOME_SITE_TEXT"];
	$newconf["home_site_url"] = $_POST["NEW_HOME_SITE_URL"];
	$newconf["indi_facts_add"] = $_POST["NEW_INDI_FACTS_ADD"];
	$newconf["indi_facts_unique"] = $_POST["NEW_INDI_FACTS_UNIQUE"];
	$newconf["indi_quick_addfacts"] = $_POST["NEW_INDI_QUICK_ADDFACTS"];
	$newconf["indi_ext_fam_facts"] = $boolarray[$_POST["NEW_INDI_EXT_FAM_FACTS"]];
	$newconf["jewish_ashkenaz_pronunciation"] = $boolarray[$_POST["NEW_JEWISH_ASHKENAZ_PRONUNCIATION"]];
	$newconf["keep_actions"] = $boolarray[$_POST["NEW_KEEP_ACTIONS"]];
	$newconf["link_icons"] = $_POST["NEW_LINK_ICONS"];
	$newconf["max_descendancy_generations"] = $_POST["NEW_MAX_DESCENDANCY_GENERATIONS"];
	$newconf["max_pedigree_generations"] = $_POST["NEW_MAX_PEDIGREE_GENERATIONS"];
	$newconf["media_directory"] = $_POST["NEW_MEDIA_DIRECTORY"];
	$newconf["media_directory_levels"] = $_POST["NEW_MEDIA_DIRECTORY_LEVELS"];
	$newconf["media_external"] = $boolarray[$_POST["NEW_MEDIA_EXTERNAL"]];
	$newconf["media_facts_add"] = $_POST["NEW_MEDIA_FACTS_ADD"];
	$newconf["media_facts_unique"] = $_POST["NEW_MEDIA_FACTS_UNIQUE"];
	$newconf["media_quick_addfacts"] = $_POST["NEW_MEDIA_QUICK_ADDFACTS"];
	$newconf["media_id_prefix"] = $_POST["NEW_MEDIA_ID_PREFIX"];
	$newconf["merge_double_media"] = $_POST["NEW_MERGE_DOUBLE_MEDIA"];
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
	$newconf["nick_delim"] = $_POST["NEW_NICK_DELIM0"].$_POST["NEW_NICK_DELIM1"];
	$newconf["note_facts_add"] = $_POST["NEW_NOTE_FACTS_ADD"];
	$newconf["note_facts_unique"] = $_POST["NEW_NOTE_FACTS_UNIQUE"];
	$newconf["note_quick_addfacts"] = $_POST["NEW_NOTE_QUICK_ADDFACTS"];
	$newconf["note_id_prefix"] = $_POST["NEW_NOTE_ID_PREFIX"];
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
	$newconf["repo_quick_addfacts"] = $_POST["NEW_REPO_QUICK_ADDFACTS"];
	$newconf["repo_id_prefix"] = $_POST["NEW_REPO_ID_PREFIX"];
	$newconf["require_authentication"] = $boolarray[$_POST["NEW_REQUIRE_AUTHENTICATION"]];
	$newconf["rss_format"] = $_POST["NEW_RSS_FORMAT"];
	$newconf["show_context_help"] = $boolarray[$_POST["NEW_SHOW_CONTEXT_HELP"]];
	$newconf["show_counter"] = $boolarray[$_POST["NEW_SHOW_COUNTER"]];
	$newconf["show_empty_boxes"] = $boolarray[$_POST["NEW_SHOW_EMPTY_BOXES"]];
	$newconf["lists_all"] = $boolarray[$_POST["NEW_LISTS_ALL"]];
	$newconf["show_fam_id_numbers"] = $boolarray[$_POST["NEW_SHOW_FAM_ID_NUMBERS"]];
	$newconf["show_gedcom_record"] = $_POST["NEW_SHOW_GEDCOM_RECORD"];
	$newconf["show_highlight_images"] = $boolarray[$_POST["NEW_SHOW_HIGHLIGHT_IMAGES"]];
	$newconf["show_id_numbers"] = $boolarray[$_POST["NEW_SHOW_ID_NUMBERS"]];
	$newconf["show_lds_at_glance"] = $boolarray[$_POST["NEW_SHOW_LDS_AT_GLANCE"]];
	$newconf["show_nick"] = $boolarray[$_POST["NEW_SHOW_NICK"]];
	$newconf["show_married_names"] = $boolarray[$_POST["NEW_SHOW_MARRIED_NAMES"]];
	$newconf["show_parents_age"] = $boolarray[$_POST["NEW_SHOW_PARENTS_AGE"]];
	$newconf["show_pedigree_places"] = $_POST["NEW_SHOW_PEDIGREE_PLACES"];
	$newconf["show_quick_resn"] = $boolarray[$_POST["NEW_SHOW_QUICK_RESN"]];
	$newconf["show_relatives_events"] = $_POST["NEW_SHOW_RELATIVES_EVENTS"];
	$newconf["show_stats"] = $boolarray[$_POST["NEW_SHOW_STATS"]];
	$newconf["sour_facts_add"] = $_POST["NEW_SOUR_FACTS_ADD"];
	$newconf["sour_facts_unique"] = $_POST["NEW_SOUR_FACTS_UNIQUE"];
	$newconf["sour_quick_addfacts"] = $_POST["NEW_SOUR_QUICK_ADDFACTS"];
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
	$newconf["display_pinyin"] = $boolarray[$_POST["NEW_DISPLAY_PINYIN"]];
	if (file_exists($NTHEME_DIR)) {
		$newconf["theme_dir"] = $_POST["NTHEME_DIR"];
	}
	else {
		$errors = true;
	}
	$newconf["time_limit"] = $_POST["NEW_TIME_LIMIT"];
	$newconf["last_change_email"] = $_POST["NEW_LAST_CHANGE_EMAIL"];

	GedcomConfig::SetGedcomConfig($newconf);
	MediaFS::CreateDir(RelativePathFile($newconf["media_directory"]), "");
	// If it's a new gedcom, also save the default privacy settings
	if ($source == "add_form" || $source == "upload_form" || $source == "add_new_form" ) {
		$priv = PrivacyObject::GetInstance(get_id_from_gedcom($FILE));
		$priv->GEDCOM = $FILE;
		$priv->WritePrivacy();
	}
	
	foreach($_POST as $key=>$value) {
		if ($key != "path") {
			$key=preg_replace("/NEW_/", "", $key);
			if (isset(GedcomConfig::$$key)) {
				if ($value=='yes') GedcomConfig::$$key=true;
				else if ($value=='no') GedcomConfig::$$key=false;
				else GedcomConfig::$$key=$value;
			}
		}
	}
	WriteToLog("EditConfigGedcom-> Gedcom configuration for ".$FILE."  updated by >".$gm_user->username."<", "I", "G", get_id_from_gedcom($FILE));
	if (!$errors) {
		$gednews = NewsController::getUserNews(get_id_from_gedcom($FILE));
		if (count($gednews)==0) {
			$news = new News();
			$news->title = "#default_news_title#";
			$news->username = get_id_from_gedcom($FILE);
			$news->text = "#default_news_text#";
			$news->date = time()-$_SESSION["timediff"];
			$news->addNews();
		}
//		if ($source == "upload_form" || $source == "reupload_form") $check = "upload";
//		else if ($source == "add_form") $check = "add";
//		else if ($source == "add_new_form") $check = "add_new";
		//print "source: ".$source." check: ".$check." gedfilename: ".$GEDFILENAME." path: ".$path. " bakfile: ".$bakfile;
		//exit;
		// If the script was only for modifying the settings, return to editgedcoms. If not, go to import.
		if ($source !== "") header("Location: uploadgedcom.php?action=".$source."&gedcomid=".get_id_from_gedcom($FILE)."&verify=verify_gedcom");
		else {
			header("Location: editgedcoms.php");
		}
		exit;
	}
}

//-- output starts here
$temp2 = GedcomConfig::$THEME_DIR;
GedcomConfig::$THEME_DIR = $temp;
PrintHeader(GM_LANG_gedconf_head);
GedcomConfig::$THEME_DIR = $temp2;
if (!isset($NTHEME_DIR)) $NTHEME_DIR = GedcomConfig::$THEME_DIR;
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
    	print "<h3>".GM_LANG_gedconf_head." - ";
		if (isset($gedid) && isset($GEDCOMS[$gedid])) print $GEDCOMS[$gedid]["title"];
		else if ($source == "add_form") print GM_LANG_add_gedcom;
		else if ($source == "upload_form" || $source == "reupload_form") print GM_LANG_upload_gedcom;
		else if ($source == "add_new_form") print GM_LANG_add_new_gedcom;
		print "</h3>";
		print "<a href=\"editgedcoms.php\"><b>";
		print GM_LANG_lang_back_manage_gedcoms;
		print "</b></a><br /><br />";
    	?>
    </td>
  </tr>
</table>

<input type="hidden" name="action" value="update" />
<input type="hidden" name="source" value="<?php print $source; ?>" />
<input type="hidden" name="oldgedid" value="<?php print $oldgedid; ?>" />
<input type="hidden" name="old_DAYS_TO_SHOW_LIMIT" value="<?php print GedcomConfig::$DAYS_TO_SHOW_LIMIT; ?>" />
<input type="hidden" name="NEW_LAST_CHANGE_EMAIL" value="<?php print GedcomConfig::$LAST_CHANGE_EMAIL; ?>" />
<?php
	if (!empty($error_msg)) print "<br /><span class=\"error\">".$error_msg."</span><br />\n";
	$i = 0;
?>

<table class="facts_table"><tr><td class="topbottombar <?php print $TEXT_DIRECTION;?>">
<?php
print "<a href=\"javascript: ".GM_LANG_gedcom_conf."\" onclick=\"expand_layer('file-options'); return false;\"><img id=\"file-options_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["minus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
print "&nbsp;<a href=\"javascript: ".GM_LANG_gedcom_conf."\" onclick=\"expand_layer('file-options'); return false;\">".GM_LANG_gedcom_conf."</a>";
?></td></tr></table>
<div id="file-options" style="display: block">
<table class="facts_table">
	<tr>
		<td class="shade2 wrap width20">
		<?php
		if ($source == "upload_form" || $source == "reupload_form") {
			print "<div class=\"helpicon\">";
			PrintHelpLink("upload_path_help", "qm", "upload_path"); print "</div><div class=\"description\">"; print GM_LANG_upload_path;
			print "</div></td><td class=\"shade1\">";
			print "<input name=\"GEDCOMPATH\" type=\"file\" size=\"60\" />";
			if (!$filesize = ini_get('upload_max_filesize')) $filesize = "2M";
			print " ( ".GM_LANG_max_upload_size." $filesize )";
		}
		else {
			print "<div class=\"helpicon\">";
			PrintHelpLink("gedcom_path_help", "qm", "gedcom_path"); print "</div><div class=\"description\">"; print GM_LANG_gedcom_path;
			print "</div></td><td class=\"shade1\">";
				?>
			<input type="text" name="GEDCOMPATH" value="<?php print preg_replace('/\\*/', '\\', $GEDCOMPATH);?>" size="40" dir ="ltr" tabindex="<?php $i++; print $i?>" <?php if (empty($source)) print " disabled=\"disabled\"";?>/>
			<?php
		}
		if ($source != "add_new_form" && ($GEDCOMPATH != "" || $GEDFILENAME != "")) {
			if (!file_exists($path.$GEDFILENAME) && !empty($GEDCOMPATH)) {
				if (strtolower(substr(trim($path.$GEDFILENAME), -4)) != ".ged") $GEDFILENAME .= ".ged";
			}
			if (!strstr($GEDCOMPATH, "://") && !file_exists($path.$GEDFILENAME)) {
				print "<br /><span class=\"error\">".str_replace("#GEDCOM#", $GEDCOMPATH, GM_LANG_error_header)."</span>\n";
			}
		}
		?>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("gedcom_title_help", "qm", "gedcom_title", true); print "</div><div class=\"description\">";print PrintText("gedcom_title",0,0,false);?></div></td>
		<td class="shade1"><input type="text" name="gedcom_title" dir="ltr" value="<?php print preg_replace("/\"/", "&quot;", PrintReady($gedcom_title)); ?>" size="40" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap">
		<div class="helpicon"><?php PrintHelpLink("LANGUAGE_help", "qm", "LANGUAGE"); print "</div><div class=\"description\">";print GM_LANG_LANGUAGE;?></div></td>
		<td class="shade1"><input type="hidden" name="changelanguage" value="yes" />
		<select name="NEW_GEDCOMLANG" dir="ltr" tabindex="<?php $i++; print $i?>" >
		<?php
			foreach ($gm_language as $key=>$value) {
				if ($language_settings[$key]["gm_lang_use"]) {
					print "\n\t\t\t<option value=\"$key\"";
					if (GedcomConfig::$GEDCOMLANG == $key) print " selected=\"selected\"";
					print ">".constant("GM_LANG_lang_name_".$key)."</option>";
				}
			}
			print "</select>";
		?>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap width20">
		<div class="helpicon"><?php PrintHelpLink("DISPLAY_PINYIN_help", "qm", "DISPLAY_PINYIN"); print "</div><div class=\"description\">"; print GM_LANG_DISPLAY_PINYIN;?></div></td>
		<td class="shade1"><select name="NEW_DISPLAY_PINYIN" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$DISPLAY_PINYIN) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$DISPLAY_PINYIN) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap width20">
		<div class="helpicon"><?php PrintHelpLink("CHARACTER_SET_help", "qm", "CHARACTER_SET"); print "</div><div class=\"description\">"; print GM_LANG_CHARACTER_SET;?></div></td>
		<td class="shade1"><input type="text" name="NEW_CHARACTER_SET" dir="ltr" value="<?php print GedcomConfig::$CHARACTER_SET;?>" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap">
		<div class="helpicon"><?php PrintHelpLink("PEDIGREE_ROOT_ID_help", "qm", "PEDIGREE_ROOT_ID"); print "</div><div class=\"description\">"; print GM_LANG_PEDIGREE_ROOT_ID;?></div></td>

		<?php
		$indirec = "";
		if ((!empty($GEDCOMPATH))&&(file_exists($path.$GEDFILENAME))&&(!empty(GedcomConfig::$PEDIGREE_ROOT_ID))) {
			//-- the following section of code was modified from the find_record_in_file function of functions.php
			$fpged = fopen($path.$GEDFILENAME, "r");
			if ($fpged) {
				$gid = GedcomConfig::$PEDIGREE_ROOT_ID;
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
							GedcomConfig::$PEDIGREE_ROOT_ID = $gid;
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
		else {
			// Maybe the DB is already loaded. We try to get the record from there.
		}
		$person = Person::GetInstance(GedcomConfig::$PEDIGREE_ROOT_ID, $indirec, get_id_from_gedcom($GEDFILENAME));
	?>
	<td class="shade1"><input type="text" name="NEW_PEDIGREE_ROOT_ID" id="NEW_PEDIGREE_ROOT_ID" value="<?php print GedcomConfig::$PEDIGREE_ROOT_ID?>" size="5" tabindex="<?php $i++; print $i?>" />
			<?php
			if ($source == "") {
				if (!$person->isempty) {
					if ($source == "") {
						print "\n<span class=\"list_item\">".$person->name;
						PersonFunctions::PrintFirstMajorFact($person);
						print "</span>\n";
					}
			    }
			    else {
					print "<span class=\"error\">";
					print GM_LANG_unable_to_find_indi;
					print "</span>";
				}
				LinkFunctions::PrintFindIndiLink("NEW_PEDIGREE_ROOT_ID","");
			}
		?>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap">
		<div class="helpicon"><?php PrintHelpLink("CALENDAR_FORMAT_help", "qm", "CALENDAR_FORMAT"); print "</div><div class=\"description\">"; print GM_LANG_CALENDAR_FORMAT;?></div></td>
		<td class="shade1"><select name="NEW_CALENDAR_FORMAT" tabindex="<?php $i++; print $i?>"  onchange="show_jewish(this, 'hebrew-cal');">
				<option value="gregorian" <?php if (GedcomConfig::$CALENDAR_FORMAT=='gregorian') print "selected=\"selected\""; ?>><?php print GM_LANG_gregorian;?></option>
				<option value="julian" <?php if (GedcomConfig::$CALENDAR_FORMAT=='julian') print "selected=\"selected\""; ?>><?php print GM_LANG_julian;?></option>
				<option value="french" <?php if (GedcomConfig::$CALENDAR_FORMAT=='french') print "selected=\"selected\""; ?>><?php print GM_LANG_config_french;?></option>
				<option value="jewish" <?php if (GedcomConfig::$CALENDAR_FORMAT=='jewish') print "selected=\"selected\""; ?>><?php print GM_LANG_jewish;?></option>
				<option value="jewish_and_gregorian" <?php if (GedcomConfig::$CALENDAR_FORMAT=='jewish_and_gregorian') print "selected=\"selected\""; ?>><?php print GM_LANG_jewish_and_gregorian;?></option>
				<option value="hebrew" <?php if (GedcomConfig::$CALENDAR_FORMAT=='hebrew') print "selected=\"selected\""; ?>><?php print GM_LANG_config_hebrew;?></option>
				<option value="hebrew_and_gregorian" <?php if (GedcomConfig::$CALENDAR_FORMAT=='hebrew_and_gregorian') print "selected=\"selected\""; ?>><?php print GM_LANG_hebrew_and_gregorian;?></option>
				<option value="arabic" <?php if (GedcomConfig::$CALENDAR_FORMAT=='arabic') print "selected=\"selected\""; ?>><?php print GM_LANG_arabic_cal;?></option>
				<option value="hijri" <?php if (GedcomConfig::$CALENDAR_FORMAT=='hijri') print "selected=\"selected\""; ?>><?php print GM_LANG_hijri;?></option>
			</select>
		</td>
	</tr>
	</table>
	<div id="hebrew-cal" style="display: <?php if ((GedcomConfig::$CALENDAR_FORMAT=='jewish')||(GedcomConfig::$CALENDAR_FORMAT=='jewish_and_gregorian')||(GedcomConfig::$CALENDAR_FORMAT=='hebrew')||(GedcomConfig::$CALENDAR_FORMAT=='hebrew_and_gregorian')) print 'block'; else print 'none';?>;">
	<table class="facts_table">
	<tr>
		<td class="shade2 wrap width20">
		<div class="helpicon"><?php PrintHelpLink("DISPLAY_JEWISH_THOUSANDS_help", "qm", "DISPLAY_JEWISH_THOUSANDS"); print "</div><div class=\"description\">"; print GM_LANG_DISPLAY_JEWISH_THOUSANDS;?></div></td>
		<td class="shade1"><select name="NEW_DISPLAY_JEWISH_THOUSANDS">
				<option value="yes" <?php if (GedcomConfig::$DISPLAY_JEWISH_THOUSANDS) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$DISPLAY_JEWISH_THOUSANDS) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap">
		<div class="helpicon"><?php PrintHelpLink("DISPLAY_JEWISH_GERESHAYIM_help", "qm", "DISPLAY_JEWISH_GERESHAYIM"); print "</div><div class=\"description\">"; print GM_LANG_DISPLAY_JEWISH_GERESHAYIM;?></div></td>
		<td class="shade1"><select name="NEW_DISPLAY_JEWISH_GERESHAYIM">
				<option value="yes" <?php if (GedcomConfig::$DISPLAY_JEWISH_GERESHAYIM) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$DISPLAY_JEWISH_GERESHAYIM) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap">
		<div class="helpicon"><?php PrintHelpLink("JEWISH_ASHKENAZ_PRONUNCIATION_help", "qm", "JEWISH_ASHKENAZ_PRONUNCIATION"); print "</div><div class=\"description\">"; print GM_LANG_JEWISH_ASHKENAZ_PRONUNCIATION;?></div></td>
		<td class="shade1"><select name="NEW_JEWISH_ASHKENAZ_PRONUNCIATION">
				<option value="yes" <?php if (GedcomConfig::$JEWISH_ASHKENAZ_PRONUNCIATION) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$JEWISH_ASHKENAZ_PRONUNCIATION) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	</table>
	</div>
	<table class="facts_table">
	<tr>
		<td class="shade2 wrap width20">
		<div class="helpicon"><?php PrintHelpLink("USE_RTL_FUNCTIONS_help", "qm", "USE_RTL_FUNCTIONS"); print "</div><div class=\"description\">"; print GM_LANG_USE_RTL_FUNCTIONS;?></div></td>
		<td class="shade1"><select name="NEW_USE_RTL_FUNCTIONS" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$USE_RTL_FUNCTIONS) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$USE_RTL_FUNCTIONS) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap">
		<div class="helpicon"><?php PrintHelpLink("USE_RIN_help", "qm", "USE_RIN"); print "</div><div class=\"description\">"; print GM_LANG_USE_RIN;?></div></td>
		<td class="shade1"><select name="NEW_USE_RIN" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$USE_RIN) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$USE_RIN) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap">
		<div class="helpicon"><?php PrintHelpLink("GEDCOM_ID_PREFIX_help", "qm", "GEDCOM_ID_PREFIX"); print "</div><div class=\"description\">"; print GM_LANG_GEDCOM_ID_PREFIX;?></div></td>
		<td class="shade1"><input type="text" name="NEW_GEDCOM_ID_PREFIX" dir="ltr" value="<?php print GedcomConfig::$GEDCOM_ID_PREFIX?>" size="5" tabindex="<?php $i++; print $i?>" />
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap">
		<div class="helpicon"><?php PrintHelpLink("FAM_ID_PREFIX_help", "qm", "FAM_ID_PREFIX"); print "</div><div class=\"description\">"; print GM_LANG_FAM_ID_PREFIX;?></div></td>
		<td class="shade1"><input type="text" name="NEW_FAM_ID_PREFIX" dir="ltr" value="<?php print GedcomConfig::$FAM_ID_PREFIX?>" size="5" tabindex="<?php $i++; print $i?>" />
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("SOURCE_ID_PREFIX_help", "qm", "SOURCE_ID_PREFIX"); print "</div><div class=\"description\">"; print GM_LANG_SOURCE_ID_PREFIX;?></div></td>
		<td class="shade1"><input type="text" name="NEW_SOURCE_ID_PREFIX" dir="ltr" value="<?php print GedcomConfig::$SOURCE_ID_PREFIX?>" size="5" tabindex="<?php $i++; print $i?>" />
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("REPO_ID_PREFIX_help", "qm", "REPO_ID_PREFIX"); print "</div><div class=\"description\">"; print GM_LANG_REPO_ID_PREFIX;?></div></td>
		<td class="shade1"><input type="text" name="NEW_REPO_ID_PREFIX" dir="ltr" value="<?php print GedcomConfig::$REPO_ID_PREFIX?>" size="5" tabindex="<?php $i++; print $i?>" />
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("MEDIA_ID_PREFIX_help", "qm", "MEDIA_ID_PREFIX"); print "</div><div class=\"description\">";print GM_LANG_MEDIA_ID_PREFIX;?></div></td>
		<td class="shade1"><input type="text" name="NEW_MEDIA_ID_PREFIX" dir="ltr" value="<?php print GedcomConfig::$MEDIA_ID_PREFIX?>" size="5" tabindex="<?php $i++; print $i?>" />
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("NOTE_ID_PREFIX_help", "qm", "NOTE_ID_PREFIX"); print "</div><div class=\"description\">";print GM_LANG_NOTE_ID_PREFIX;?></div></td>
		<td class="shade1"><input type="text" name="NEW_NOTE_ID_PREFIX" dir="ltr" value="<?php print GedcomConfig::$NOTE_ID_PREFIX?>" size="5" tabindex="<?php $i++; print $i?>" />
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap width20">
		<div class="helpicon"><?php PrintHelpLink("KEEP_ACTIONS_help", "qm", "KEEP_ACTIONS"); print "</div><div class=\"description\">"; print GM_LANG_KEEP_ACTIONS;?></div></td>
		<td class="shade1"><select name="NEW_KEEP_ACTIONS" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$KEEP_ACTIONS) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$KEEP_ACTIONS) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("time_limit_help", "qm", "PHP_TIME_LIMIT"); print "</div><div class=\"description\">"; print GM_LANG_PHP_TIME_LIMIT;?></div></td>
		<td class="shade1"><input type="text" name="NEW_TIME_LIMIT" value="<?php print GedcomConfig::$TIME_LIMIT?>" size="5" tabindex="<?php $i++; print $i?>"/><br />
		<?php if ($SystemConfig->max_execution_time == 0) print GM_LANG_maxtime_not_set;
		else print GM_LANG_maxtime_is."&nbsp;".$SystemConfig->max_execution_time;
		print "<br />".GM_LANG_maxtime_measure;
		?></td>
	</tr>
</table>
</div>

<table class="facts_table"><tr><td class="topbottombar <?php print $TEXT_DIRECTION;?>">
<?php
print "<a href=\"javascript: ".GM_LANG_media_conf."\" onclick=\"expand_layer('config-media');return false;\"><img id=\"config-media_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
print "&nbsp;<a href=\"javascript: ".GM_LANG_media_conf."\" onclick=\"expand_layer('config-media');return false;\">".GM_LANG_media_conf."</a>";
?></td></tr></table>
<div id="config-media" style="display: none">
<table class="facts_table">
	<tr>
		<td class="shade2 wrap width20"><div class="helpicon"><?php PrintHelpLink("MEDIA_EXTERNAL_help", "qm", "MEDIA_EXTERNAL"); print "</div><div class=\"description\">"; print GM_LANG_MEDIA_EXTERNAL;?></div></td>
		<td class="shade1"><select name="NEW_MEDIA_EXTERNAL" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$MEDIA_EXTERNAL) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$MEDIA_EXTERNAL) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("MEDIA_DIRECTORY_help", "qm", "MEDIA_DIRECTORY"); print "</div><div class=\"description\">"; print GM_LANG_MEDIA_DIRECTORY;?></div></td>
		<td class="shade1"><input type="text" size="50" name="NEW_MEDIA_DIRECTORY" value="<?php print GedcomConfig::$MEDIA_DIRECTORY?>" dir="ltr" tabindex="<?php $i++; print $i?>" />
		<?php
		if(preg_match("/.*[a-zA-Z]{1}:.*/",GedcomConfig::$MEDIA_DIRECTORY)>0) print "<span class=\"error\">".GM_LANG_media_drive_letter."</span>\n";
		?>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("MEDIA_DIRECTORY_LEVELS_help", "qm", "MEDIA_DIRECTORY_LEVELS"); print "</div><div class=\"description\">"; print GM_LANG_MEDIA_DIRECTORY_LEVELS;?></div></td>
		<td class="shade1"><input type="text" name="NEW_MEDIA_DIRECTORY_LEVELS" value="<?php print GedcomConfig::$MEDIA_DIRECTORY_LEVELS?>" size="5" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("THUMBNAIL_WIDTH_help", "qm", "THUMBNAIL_WIDTH"); print "</div><div class=\"description\">"; print GM_LANG_THUMBNAIL_WIDTH;?></div></td>
		<td class="shade1"><input type="text" name="NEW_THUMBNAIL_WIDTH" value="<?php print GedcomConfig::$THUMBNAIL_WIDTH?>" size="5" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("AUTO_GENERATE_THUMBS_help", "qm", "AUTO_GENERATE_THUMBS"); print "</div><div class=\"description\">"; print GM_LANG_AUTO_GENERATE_THUMBS;?></div></td>
		<td class="shade1"><select name="NEW_AUTO_GENERATE_THUMBS" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$AUTO_GENERATE_THUMBS) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$AUTO_GENERATE_THUMBS) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("SHOW_HIGHLIGHT_IMAGES_help", "qm", "SHOW_HIGHLIGHT_IMAGES"); print "</div><div class=\"description\">"; print GM_LANG_SHOW_HIGHLIGHT_IMAGES;?></div></td>
		<td class="shade1"><select name="NEW_SHOW_HIGHLIGHT_IMAGES" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$SHOW_HIGHLIGHT_IMAGES) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$SHOW_HIGHLIGHT_IMAGES) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("USE_THUMBS_MAIN_help", "qm", "USE_THUMBS_MAIN"); print "</div><div class=\"description\">"; print GM_LANG_USE_THUMBS_MAIN;?></div></td>
		<td class="shade1"><select name="NEW_USE_THUMBS_MAIN" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$USE_THUMBS_MAIN) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$USE_THUMBS_MAIN) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("MERGE_DOUBLE_MEDIA_help", "qm", "MERGE_DOUBLE_MEDIA"); print "</div><div class=\"description\">"; print GM_LANG_MERGE_DOUBLE_MEDIA;?></div></td>
		<td class="shade1"><select name="NEW_MERGE_DOUBLE_MEDIA" tabindex="<?php $i++; print $i?>">
				<option value="0" <?php if (GedcomConfig::$MERGE_DOUBLE_MEDIA == "0") print "selected=\"selected\""; ?>><?php print GM_LANG_merge_dm_0;?></option>
				<option value="1" <?php if (GedcomConfig::$MERGE_DOUBLE_MEDIA == "1" || empty($MERGE_DOUBLE_MEDIA)) print "selected=\"selected\""; ?>><?php print GM_LANG_merge_dm_1;?></option>
				<option value="2" <?php if (GedcomConfig::$MERGE_DOUBLE_MEDIA == "2") print "selected=\"selected\""; ?>><?php print GM_LANG_merge_dm_2;?></option>
			</select>
		</td>
	</tr>
</table>
</div>

<table class="facts_table"><tr><td class="topbottombar <?php print $TEXT_DIRECTION;?>">
<?php
print "<a href=\"javascript: ".GM_LANG_accpriv_conf."\" onclick=\"expand_layer('access-options');return false;\"><img id=\"access-options_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
print "&nbsp;<a href=\"javascript: ".GM_LANG_accpriv_conf."\" onclick=\"expand_layer('access-options');return false;\">".GM_LANG_accpriv_conf."</a>";
?></td></tr></table>
<div id="access-options" style="display: none">
<table class="facts_table">
	<tr>
		<td class="shade2 wrap width20"><div class="helpicon"><?php PrintHelpLink("REQUIRE_AUTHENTICATION_help", "qm", "REQUIRE_AUTHENTICATION"); print "</div><div class=\"description\">"; print GM_LANG_REQUIRE_AUTHENTICATION;?></div></td>
		<td class="shade1"><select name="NEW_REQUIRE_AUTHENTICATION" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$REQUIRE_AUTHENTICATION) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$REQUIRE_AUTHENTICATION) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("WELCOME_TEXT_AUTH_MODE_help", "qm", "WELCOME_TEXT_AUTH_MODE"); print "</div><div class=\"description\">"; print GM_LANG_WELCOME_TEXT_AUTH_MODE;?></div></td>
		<td class="shade1"><select name="NEW_WELCOME_TEXT_AUTH_MODE" tabindex="<?php $i++; print $i?>">
				<option value="1" <?php if (GedcomConfig::$WELCOME_TEXT_AUTH_MODE=='1') print "selected=\"selected\""; ?>><?php print GM_LANG_WELCOME_TEXT_AUTH_MODE_OPT1;?></option>
				<option value="2" <?php if (GedcomConfig::$WELCOME_TEXT_AUTH_MODE=='2') print "selected=\"selected\""; ?>><?php print GM_LANG_WELCOME_TEXT_AUTH_MODE_OPT2;?></option>
				<option value="3" <?php if (GedcomConfig::$WELCOME_TEXT_AUTH_MODE=='3') print "selected=\"selected\""; ?>><?php print GM_LANG_WELCOME_TEXT_AUTH_MODE_OPT3;?></option>
				<option value="4" <?php if (GedcomConfig::$WELCOME_TEXT_AUTH_MODE=='4') print "selected=\"selected\""; ?>><?php print GM_LANG_WELCOME_TEXT_AUTH_MODE_OPT4;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("WELCOME_TEXT_AUTH_MODE_CUST_HEAD_help", "qm", "WELCOME_TEXT_AUTH_MODE_CUST_HEAD"); print "</div><div class=\"description\">"; print GM_LANG_WELCOME_TEXT_AUTH_MODE_CUST_HEAD;?></div></td>
		<td class="shade1"><select name="NEW_WELCOME_TEXT_CUST_HEAD" tabindex="<?php $i++; print $i?>" >
				<option value="yes" <?php if (GedcomConfig::$WELCOME_TEXT_CUST_HEAD) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$WELCOME_TEXT_CUST_HEAD) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("WELCOME_TEXT_AUTH_MODE_CUST_help", "qm", "WELCOME_TEXT_AUTH_MODE_CUST"); print "</div><div class=\"description\">"; print GM_LANG_WELCOME_TEXT_AUTH_MODE_CUST;?></div></td>
		<td class="shade1"><textarea name="NEW_WELCOME_TEXT_AUTH_MODE_4" rows="5" cols="60" dir="ltr" tabindex="<?php $i++; print $i?>"><?php print  GedcomConfig::$WELCOME_TEXT_AUTH_MODE_4 ?></textarea>
		</td>
	</tr>
</table>
</div>

<table class="facts_table"><tr><td class="topbottombar <?php print $TEXT_DIRECTION;?>">
<?php
print "<a href=\"javascript: ".GM_LANG_displ_conf."\" onclick=\"expand_layer('layout-options');return false;\"><img id=\"layout-options_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
print "&nbsp;<a href=\"javascript: ".GM_LANG_displ_conf."\" onclick=\"expand_layer('layout-options');return false;\">".GM_LANG_displ_conf."</a>";
?></td></tr></table>
<div id="layout-options" style="display: none">

<table class="facts_table"><tr><td class="subbar">
<?php
print "<a href=\"javascript: ".GM_LANG_displ_names_conf."\" onclick=\"expand_layer('layout-options2');return false;\"><img id=\"layout-options2_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
print "&nbsp;<a href=\"javascript: ".GM_LANG_displ_names_conf."\" onclick=\"expand_layer('layout-options2');return false;\">".GM_LANG_displ_names_conf."</a>";
?></td></tr></table>
<div id="layout-options2" style="display: none">
<table class="facts_table">
	<tr>
		<td class="shade2 wrap width20"><div class="helpicon"><?php PrintHelpLink("PEDIGREE_FULL_DETAILS_help", "qm", "PEDIGREE_FULL_DETAILS"); print "</div><div class=\"description\">"; print GM_LANG_PEDIGREE_FULL_DETAILS;?></div></td>
		<td class="shade1"><select name="NEW_PEDIGREE_FULL_DETAILS" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$PEDIGREE_FULL_DETAILS) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$PEDIGREE_FULL_DETAILS) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("ABBREVIATE_CHART_LABELS_help", "qm", "ABBREVIATE_CHART_LABELS"); print "</div><div class=\"description\">"; print GM_LANG_ABBREVIATE_CHART_LABELS;?></div></td>
		<td class="shade1"><select name="NEW_ABBREVIATE_CHART_LABELS" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$ABBREVIATE_CHART_LABELS) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$ABBREVIATE_CHART_LABELS) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("SHOW_PARENTS_AGE_help", "qm", "SHOW_PARENTS_AGE"); print "</div><div class=\"description\">"; print GM_LANG_SHOW_PARENTS_AGE;?></div></td>
		<td class="shade1"><select name="NEW_SHOW_PARENTS_AGE" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$SHOW_PARENTS_AGE) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$SHOW_PARENTS_AGE) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("SHOW_LDS_AT_GLANCE_help", "qm", "SHOW_LDS_AT_GLANCE"); print "</div><div class=\"description\">"; print GM_LANG_SHOW_LDS_AT_GLANCE;?></div></td>
		<td class="shade1"><select name="NEW_SHOW_LDS_AT_GLANCE" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$SHOW_LDS_AT_GLANCE) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$SHOW_LDS_AT_GLANCE) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("SHOW_NICK_help", "qm", "SHOW_NICK"); print "</div><div class=\"description\">"; print GM_LANG_SHOW_NICK;?></div></td>
		<td class="shade1"><select name="NEW_SHOW_NICK" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$SHOW_NICK) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$SHOW_NICK) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("NICK_DELIM_help", "qm", "NICK_DELIM"); print "</div><div class=\"description\">"; print GM_LANG_NICK_DELIM;?></div></td>
		<td class="shade1">
			<input type="text" size="1" maxlength="1" name="NEW_NICK_DELIM0" value="<?php if (empty(GedcomConfig::$NICK_DELIM)) print "("; else print htmlentities(substr(GedcomConfig::$NICK_DELIM, 0, 1), ENT_QUOTES);?>" dir="ltr" tabindex="<?php $i++; print $i?>" />
			<input type="text" size="1" maxlength="1" name="NEW_NICK_DELIM1" value="<?php if (empty(GedcomConfig::$NICK_DELIM)) print ")"; else print htmlentities(substr(GedcomConfig::$NICK_DELIM, 1, 1), ENT_QUOTES);?>" dir="ltr" tabindex="<?php $i++; print $i?>" />
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("CHART_BOX_TAGS_help", "qm", "CHART_BOX_TAGS"); print "</div><div class=\"description\">"; print GM_LANG_CHART_BOX_TAGS;?></div></td>
		<td class="shade1">
			<input type="text" size="50" name="NEW_CHART_BOX_TAGS" value="<?php print GedcomConfig::$CHART_BOX_TAGS?>" dir="ltr" tabindex="<?php $i++; print $i?>" />
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("SHOW_MARRIED_NAMES_help", "qm", "SHOW_MARRIED_NAMES"); print "</div><div class=\"description\">"; print GM_LANG_SHOW_MARRIED_NAMES;?></div></td>
		<td class="shade1"><select name="NEW_SHOW_MARRIED_NAMES" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$SHOW_MARRIED_NAMES) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$SHOW_MARRIED_NAMES) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("UNDERLINE_NAME_QUOTES_help", "qm", "UNDERLINE_NAME_QUOTES"); print "</div><div class=\"description\">"; print GM_LANG_UNDERLINE_NAME_QUOTES;?></div></td>
		<td class="shade1"><select name="NEW_UNDERLINE_NAME_QUOTES" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$UNDERLINE_NAME_QUOTES) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$UNDERLINE_NAME_QUOTES) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("SHOW_ID_NUMBERS_help", "qm", "SHOW_ID_NUMBERS"); print "</div><div class=\"description\">"; print GM_LANG_SHOW_ID_NUMBERS;?></div></td>
		<td class="shade1"><select name="NEW_SHOW_ID_NUMBERS" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$SHOW_ID_NUMBERS) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$SHOW_ID_NUMBERS) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("SHOW_FAM_ID_NUMBERS_help", "qm", "SHOW_FAM_ID_NUMBERS"); print "</div><div class=\"description\">"; print GM_LANG_SHOW_FAM_ID_NUMBERS;?></div></td>
        <td class="shade1"><select name="NEW_SHOW_FAM_ID_NUMBERS" tabindex="<?php $i++; print $i?>">
			<option value="yes" <?php if (GedcomConfig::$SHOW_FAM_ID_NUMBERS) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
            <option value="no" <?php if (!GedcomConfig::$SHOW_FAM_ID_NUMBERS) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
            </select>
        </td>
    </tr>
</table>
</div>

<table class="facts_table"><tr><td class="subbar">
<?php
print "<a href=\"javascript: ".GM_LANG_displ_comsurn_conf."\" onclick=\"expand_layer('layout-options3');return false;\"><img id=\"layout-options3_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
print "&nbsp;<a href=\"javascript: ".GM_LANG_displ_comsurn_conf."\" onclick=\"expand_layer('layout-options3');return false;\">".GM_LANG_displ_comsurn_conf."</a>";
?></td></tr></table>
<div id="layout-options3" style="display: none">
<table class="facts_table">
	<tr>
		<td class="shade2 wrap width20"><div class="helpicon"><?php PrintHelpLink("COMMON_NAMES_THRESHOLD_help", "qm", "COMMON_NAMES_THRESHOLD"); print "</div><div class=\"description\">"; print GM_LANG_COMMON_NAMES_THRESHOLD;?></div></td>
		<td class="shade1"><input type="text" name="NEW_COMMON_NAMES_THRESHOLD" value="<?php print GedcomConfig::$COMMON_NAMES_THRESHOLD?>" size="5" tabindex="<?php $i++; print $i?>" /></td>
	</tr>

	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("COMMON_NAMES_ADD_help", "qm", "COMMON_NAMES_ADD"); print "</div><div class=\"description\">"; print GM_LANG_COMMON_NAMES_ADD;?></div></td>
		<td class="shade1"><input type="text" name="NEW_COMMON_NAMES_ADD" dir="ltr" value="<?php print GedcomConfig::$COMMON_NAMES_ADD?>" size="50" tabindex="<?php $i++; print $i?>" /></td>
	</tr>

	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("COMMON_NAMES_REMOVE_help", "qm", "COMMON_NAMES_REMOVE"); print "</div><div class=\"description\">"; print GM_LANG_COMMON_NAMES_REMOVE;?></div></td>
		<td class="shade1"><input type="text" name="NEW_COMMON_NAMES_REMOVE" dir="ltr" value="<?php print GedcomConfig::$COMMON_NAMES_REMOVE?>" size="50" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
</table>
</div>

<?php // Display and Layout
?>
<table class="facts_table"><tr><td class="subbar <?php print $TEXT_DIRECTION;?>">
<?php
print "<a href=\"javascript: ".GM_LANG_displ_layout_conf."\" onclick=\"expand_layer('layout-options4');return false;\"><img id=\"layout-options4_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
print "&nbsp;<a href=\"javascript: ".GM_LANG_displ_layout_conf."\" onclick=\"expand_layer('layout-options4');return false;\">".GM_LANG_displ_layout_conf."</a>";
?></td></tr></table>
<div id="layout-options4" style="display: none">
<table class="facts_table">
	<tr>
		<td class="shade2 wrap width20"><div class="helpicon"><?php PrintHelpLink("DEFAULT_PEDIGREE_GENERATIONS_help", "qm", "DEFAULT_PEDIGREE_GENERATIONS"); print "</div><div class=\"description\">"; print GM_LANG_DEFAULT_PEDIGREE_GENERATIONS;?></div></td>
		<td class="shade1"><input type="text" name="NEW_DEFAULT_PEDIGREE_GENERATIONS" value="<?php print GedcomConfig::$DEFAULT_PEDIGREE_GENERATIONS?>" size="5" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("MAX_PEDIGREE_GENERATIONS_help", "qm", "MAX_PEDIGREE_GENERATIONS"); print "</div><div class=\"description\">"; print GM_LANG_MAX_PEDIGREE_GENERATIONS;?></div></td>
		<td class="shade1"><input type="text" name="NEW_MAX_PEDIGREE_GENERATIONS" value="<?php print GedcomConfig::$MAX_PEDIGREE_GENERATIONS?>" size="5" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("MAX_DESCENDANCY_GENERATIONS_help", "qm", "MAX_DESCENDANCY_GENERATIONS"); print "</div><div class=\"description\">"; print GM_LANG_MAX_DESCENDANCY_GENERATIONS;?></div></td>
		<td class="shade1"><input type="text" name="NEW_MAX_DESCENDANCY_GENERATIONS" value="<?php print GedcomConfig::$MAX_DESCENDANCY_GENERATIONS?>" size="5" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("PEDIGREE_LAYOUT_help", "qm", "PEDIGREE_LAYOUT"); print "</div><div class=\"description\">"; print GM_LANG_PEDIGREE_LAYOUT;?></div></td>
		<td class="shade1"><select name="NEW_PEDIGREE_LAYOUT" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$PEDIGREE_LAYOUT) print "selected=\"selected\""; ?>><?php print GM_LANG_landscape;?></option>
				<option value="no" <?php if (!GedcomConfig::$PEDIGREE_LAYOUT) print "selected=\"selected\""; ?>><?php print GM_LANG_portrait;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("SHOW_PEDIGREE_PLACES_help", "qm", "SHOW_PEDIGREE_PLACES"); print "</div><div class=\"description\">"; print GM_LANG_SHOW_PEDIGREE_PLACES;?></div></td>
		<td class="shade1"><input type="text" size="5" name="NEW_SHOW_PEDIGREE_PLACES" value="<?php print GedcomConfig::$SHOW_PEDIGREE_PLACES; ?>" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("ZOOM_BOXES_help", "qm", "ZOOM_BOXES"); print "</div><div class=\"description\">"; print GM_LANG_ZOOM_BOXES;?></div></td>
		<td class="shade1"><select name="NEW_ZOOM_BOXES" tabindex="<?php $i++; print $i?>">
				<option value="disabled" <?php if (GedcomConfig::$ZOOM_BOXES=='disabled') print "selected=\"selected\""; ?>><?php print GM_LANG_disabled;?></option>
				<option value="mouseover" <?php if (GedcomConfig::$ZOOM_BOXES=='mouseover') print "selected=\"selected\""; ?>><?php print GM_LANG_mouseover;?></option>
				<option value="mousedown" <?php if (GedcomConfig::$ZOOM_BOXES=='mousedown') print "selected=\"selected\""; ?>><?php print GM_LANG_mousedown;?></option>
				<option value="click" <?php if (GedcomConfig::$ZOOM_BOXES=='click') print "selected=\"selected\""; ?>><?php print GM_LANG_click;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("LINK_ICONS_help", "qm", "LINK_ICONS"); print "</div><div class=\"description\">"; print GM_LANG_LINK_ICONS;?></div></td>
		<td class="shade1"><select name="NEW_LINK_ICONS" tabindex="<?php $i++; print $i?>">
				<option value="disabled" <?php if (GedcomConfig::$LINK_ICONS=='disabled') print "selected=\"selected\""; ?>><?php print GM_LANG_disabled;?></option>
				<option value="mouseover" <?php if (GedcomConfig::$LINK_ICONS=='mouseover') print "selected=\"selected\""; ?>><?php print GM_LANG_mouseover;?></option>
				<option value="click" <?php if (GedcomConfig::$LINK_ICONS=='click') print "selected=\"selected\""; ?>><?php print GM_LANG_click;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("GEDCOM_DEFAULT_TAB_help", "qm", "GEDCOM_DEFAULT_TAB"); print "</div><div class=\"description\">"; print GM_LANG_GEDCOM_DEFAULT_TAB;?></div></td>
		<td class="shade1"><select name="NEW_GEDCOM_DEFAULT_TAB" tabindex="<?php $i++; print $i?>">
				<option value="0" <?php if (GedcomConfig::$GEDCOM_DEFAULT_TAB==0) print "selected=\"selected\""; ?>><?php print GM_LANG_personal_facts;?></option>
				<option value="1" <?php if (GedcomConfig::$GEDCOM_DEFAULT_TAB==1) print "selected=\"selected\""; ?>><?php print GM_LANG_notes;?></option>
				<option value="2" <?php if (GedcomConfig::$GEDCOM_DEFAULT_TAB==2) print "selected=\"selected\""; ?>><?php print GM_LANG_ssourcess;?></option>
				<option value="3" <?php if (GedcomConfig::$GEDCOM_DEFAULT_TAB==3) print "selected=\"selected\""; ?>><?php print GM_LANG_media;?></option>
				<option value="4" <?php if (GedcomConfig::$GEDCOM_DEFAULT_TAB==4) print "selected=\"selected\""; ?>><?php print GM_LANG_relatives;?></option>
				<option value="6" <?php if (GedcomConfig::$GEDCOM_DEFAULT_TAB==6) print "selected=\"selected\""; ?>><?php print GM_LANG_all;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("SHOW_RELATIVES_EVENTS_help", "qm", "SHOW_RELATIVES_EVENTS"); print "</div><div class=\"description\">"; print GM_LANG_SHOW_RELATIVES_EVENTS;?></div></td>
		<td class="shade1">
			<input type="hidden" name="NEW_SHOW_RELATIVES_EVENTS" value="<?php echo GedcomConfig::$SHOW_RELATIVES_EVENTS?>" />
<?php

$previous = "_DEAT_";
print "<table>";

$factarr = get_defined_constants(true);
foreach ($factarr["user"] as $factkey=>$factlabel) {
	$fcheck = substr($factkey, 0, 8);
	if ($fcheck == "GM_FACT_") {
		$f6=substr($factkey,8,6);
		$factkey = substr($factkey, 8);
		if ($f6=="_BIRT_" or $f6=="_MARR_" or $f6=="_DEAT_") {
			if ($f6=="_BIRT_") print "<tr>";
			if ($f6=="_MARR_" and $previous!="_BIRT_") print "<tr><td>&nbsp;</td>";
			if ($f6=="_DEAT_" and $previous=="_DEAT_") print "<tr><td>&nbsp;</td>";
			if ($f6=="_DEAT_" and $previous!="_MARR_") print "<td>&nbsp;</td>";
			print "\n<td><input type=\"checkbox\" name=\"SHOW_RELATIVES_EVENTS_checkbox\" value=\"".$factkey."\"";
			if (strstr(GedcomConfig::$SHOW_RELATIVES_EVENTS,$factkey)) print " checked=\"checked\"";
			print " onchange=\"var old=document.configform.NEW_SHOW_RELATIVES_EVENTS.value; if (this.checked) old+=','+this.value; else old=old.replace(/".$factkey."/g,''); old=old.replace(/[,]+/gi,','); old=old.replace(/^[,]/gi,''); old=old.replace(/[,]$/gi,''); document.configform.NEW_SHOW_RELATIVES_EVENTS.value=old\" ";
			print " /> ".$factlabel."</td>";
			if ($f6=="_DEAT_") print "</tr>";
			$previous=$f6;
		}
	}
}
print "</table>"; 
?>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("EXPAND_RELATIVES_EVENTS_help", "qm", "EXPAND_RELATIVES_EVENTS"); print "</div><div class=\"description\">"; print GM_LANG_EXPAND_RELATIVES_EVENTS;?></div></td>
		<td class="shade1">
			<select name="NEW_EXPAND_RELATIVES_EVENTS" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$EXPAND_RELATIVES_EVENTS) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$EXPAND_RELATIVES_EVENTS) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>

		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("INDI_EXT_FAM_FACTS_help", "qm", "INDI_EXT_FAM_FACTS"); print "</div><div class=\"description\">"; print GM_LANG_INDI_EXT_FAM_FACTS;?></div></td>
		<td class="shade1">
			<select name="NEW_INDI_EXT_FAM_FACTS" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$INDI_EXT_FAM_FACTS) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$INDI_EXT_FAM_FACTS) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("POSTAL_CODE_help", "qm", "POSTAL_CODE"); print "</div><div class=\"description\">"; print GM_LANG_POSTAL_CODE;?></div></td>
		<td class="shade1"><select name="NEW_POSTAL_CODE" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$POSTAL_CODE) print "selected=\"selected\""; ?>><?php print ucfirst(GM_LANG_after);?></option>
				<option value="no" <?php if (!GedcomConfig::$POSTAL_CODE) print "selected=\"selected\""; ?>><?php print ucfirst(GM_LANG_before);?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("ALPHA_INDEX_LISTS_help", "qm", "ALPHA_INDEX_LISTS"); print "</div><div class=\"description\">"; print GM_LANG_ALPHA_INDEX_LISTS;?></div></td>
		<td class="shade1"><input name="NEW_ALPHA_INDEX_LISTS" tabindex="<?php $i++; print $i?>" type="text" size="5" maxlength="4" value="<?php print GedcomConfig::$ALPHA_INDEX_LISTS; ?>" />
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("LISTS_ALL_help", "qm", "LISTS_ALL"); print "</div><div class=\"description\">"; print GM_LANG_LISTS_ALL;?></div></td>
			<td class="shade1">			
				<select name="NEW_LISTS_ALL" tabindex="<?php $i++; print $i?>">
					<option value="yes" <?php if (GedcomConfig::$LISTS_ALL) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
					<option value="no" <?php if (!GedcomConfig::$LISTS_ALL) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
				</select>
			</select>
		</td>
	</tr>
</table>
</div>


<table class="facts_table"><tr><td class="subbar <?php print $TEXT_DIRECTION;?>">
<?php
print "<a href=\"javascript: ".GM_LANG_displ_hide_conf."\" onclick=\"expand_layer('layout-options5');return false;\"><img id=\"layout-options5_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
print "&nbsp;<a href=\"javascript: ".GM_LANG_displ_hide_conf."\" onclick=\"expand_layer('layout-options5');return false;\">".GM_LANG_displ_hide_conf."</a>";
?></td></tr></table>
<div id="layout-options5" style="display: none">
<table class="facts_table">
	<tr>
		<td class="shade2 wrap width20"><div class="helpicon"><?php PrintHelpLink("DAYS_TO_SHOW_LIMIT_help", "qm", "DAYS_TO_SHOW_LIMIT"); print "</div><div class=\"description\">"; print GM_LANG_DAYS_TO_SHOW_LIMIT;?></div></td>
		<td class="shade1"><input type="text" name="NEW_DAYS_TO_SHOW_LIMIT" value="<?php print GedcomConfig::$DAYS_TO_SHOW_LIMIT?>" size="2" tabindex="<?php $i++; print $i?>" /></td>
	</tr>

	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("SHOW_EMPTY_BOXES_help", "qm", "SHOW_EMPTY_BOXES"); print "</div><div class=\"description\">"; print GM_LANG_SHOW_EMPTY_BOXES;?></div></td>
		<td class="shade1"><select name="NEW_SHOW_EMPTY_BOXES" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$SHOW_EMPTY_BOXES) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$SHOW_EMPTY_BOXES) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("SHOW_GEDCOM_RECORD_help", "qm", "SHOW_GEDCOM_RECORD"); print "</div><div class=\"description\">"; print GM_LANG_SHOW_GEDCOM_RECORD;?></div></td>
		<td class="shade1"><select name="NEW_SHOW_GEDCOM_RECORD" tabindex="<?php $i++; print $i?>">
				<option value="-1" <?php if (GedcomConfig::$SHOW_GEDCOM_RECORD == "-1") print "selected=\"selected\""; ?>><?php print constant("GM_LANG_show_gedrec_-1");?></option>
				<option value="0" <?php if (GedcomConfig::$SHOW_GEDCOM_RECORD == "0") print "selected=\"selected\""; ?>><?php print GM_LANG_show_gedrec_0;?></option>
				<option value="1" <?php if (GedcomConfig::$SHOW_GEDCOM_RECORD == "1") print "selected=\"selected\""; ?>><?php print GM_LANG_show_gedrec_1;?></option>
				<option value="2" <?php if (GedcomConfig::$SHOW_GEDCOM_RECORD == "2") print "selected=\"selected\""; ?>><?php print GM_LANG_show_gedrec_2;?></option>
				<option value="3" <?php if (GedcomConfig::$SHOW_GEDCOM_RECORD == "3") print "selected=\"selected\""; ?>><?php print GM_LANG_show_gedrec_3;?></option>
				<option value="4" <?php if (GedcomConfig::$SHOW_GEDCOM_RECORD == "4") print "selected=\"selected\""; ?>><?php print GM_LANG_show_gedrec_4;?></option>
				<option value="5" <?php if (GedcomConfig::$SHOW_GEDCOM_RECORD == "5") print "selected=\"selected\""; ?>><?php print GM_LANG_show_gedrec_5;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("HIDE_GEDCOM_ERRORS_help", "qm", "HIDE_GEDCOM_ERRORS"); print "</div><div class=\"description\">"; print GM_LANG_HIDE_GEDCOM_ERRORS;?></div></td>
		<td class="shade1"><select name="NEW_HIDE_GEDCOM_ERRORS" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$HIDE_GEDCOM_ERRORS) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$HIDE_GEDCOM_ERRORS) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("WORD_WRAPPED_NOTES_help", "qm", "WORD_WRAPPED_NOTES"); print "</div><div class=\"description\">"; print GM_LANG_WORD_WRAPPED_NOTES;?></div></td>
		<td class="shade1"><select name="NEW_WORD_WRAPPED_NOTES" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$WORD_WRAPPED_NOTES) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$WORD_WRAPPED_NOTES) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("FAVICON_help", "qm", "FAVICON"); print "</div><div class=\"description\">"; print GM_LANG_FAVICON;?></div></td>
		<td class="shade1"><input type="text" name="NEW_FAVICON" value="<?php print GedcomConfig::$FAVICON?>" size="40" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("SHOW_COUNTER_help", "qm", "SHOW_COUNTER"); print "</div><div class=\"description\">"; print GM_LANG_SHOW_COUNTER;?></div></td>
		<td class="shade1"><select name="NEW_SHOW_COUNTER" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$SHOW_COUNTER) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$SHOW_COUNTER) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("SHOW_STATS_help", "qm", "SHOW_STATS"); print "</div><div class=\"description\">"; print GM_LANG_SHOW_STATS;?></div></td>
		<td class="shade1"><select name="NEW_SHOW_STATS" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$SHOW_STATS) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$SHOW_STATS) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
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
print "<a href=\"javascript: ".GM_LANG_editopt_conf."\" onclick=\"expand_layer('edit-options');return false;\"><img id=\"edit-options_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
print "&nbsp;<a href=\"javascript: ".GM_LANG_editopt_conf."\" onclick=\"expand_layer('edit-options');return false;\">".GM_LANG_editopt_conf."</a>";
?></td></tr></table>
<div id="edit-options" style="display: none">
<table class="facts_table">
	<tr>
		<td class="shade2 wrap width20"><div class="helpicon"><?php PrintHelpLink("ALLOW_EDIT_GEDCOM_help", "qm", "ALLOW_EDIT_GEDCOM"); print "</div><div class=\"description\">"; print GM_LANG_ALLOW_EDIT_GEDCOM;?></div></td>
		<td class="shade1"><select name="NEW_ALLOW_EDIT_GEDCOM" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$ALLOW_EDIT_GEDCOM) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$ALLOW_EDIT_GEDCOM) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("EDIT_GEDCOM_RECORD_help", "qm", "EDIT_GEDCOM_RECORD"); print "</div><div class=\"description\">"; print GM_LANG_EDIT_GEDCOM_RECORD;?></div></td>
		<td class="shade1"><select name="NEW_EDIT_GEDCOM_RECORD" tabindex="<?php $i++; print $i?>">
				<option value="-1" <?php if (GedcomConfig::$EDIT_GEDCOM_RECORD == "-1") print "selected=\"selected\""; ?>><?php print constant("GM_LANG_show_gedrec_-1");?></option>
				<option value="2" <?php if (GedcomConfig::$EDIT_GEDCOM_RECORD == "2") print "selected=\"selected\""; ?>><?php print GM_LANG_show_gedrec_2;?></option>
				<option value="3" <?php if (GedcomConfig::$EDIT_GEDCOM_RECORD == "3") print "selected=\"selected\""; ?>><?php print GM_LANG_show_gedrec_3;?></option>
				<option value="4" <?php if (GedcomConfig::$EDIT_GEDCOM_RECORD == "4") print "selected=\"selected\""; ?>><?php print GM_LANG_show_gedrec_4;?></option>
				<option value="5" <?php if (GedcomConfig::$EDIT_GEDCOM_RECORD == "5") print "selected=\"selected\""; ?>><?php print GM_LANG_show_gedrec_5;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("INDI_FACTS_ADD_help", "qm", "INDI_FACTS_ADD"); print "</div><div class=\"description\">"; print GM_LANG_INDI_FACTS_ADD;?></div></td>
		<td class="shade1"><input type="text" name="NEW_INDI_FACTS_ADD" value="<?php print GedcomConfig::$INDI_FACTS_ADD; ?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("INDI_FACTS_UNIQUE_help", "qm", "INDI_FACTS_UNIQUE"); print "</div><div class=\"description\">"; print GM_LANG_INDI_FACTS_UNIQUE;?></div></td>
		<td class="shade1"><input type="text" name="NEW_INDI_FACTS_UNIQUE" value="<?php print GedcomConfig::$INDI_FACTS_UNIQUE; ?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("INDI_QUICK_ADDFACTS_help", "qm", "INDI_QUICK_ADDFACTS"); print "</div><div class=\"description\">"; print GM_LANG_INDI_QUICK_ADDFACTS;?></div></td>
		<td class="shade1"><input type="text" name="NEW_INDI_QUICK_ADDFACTS" value="<?php print GedcomConfig::$INDI_QUICK_ADDFACTS; ?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("FAM_FACTS_ADD_help", "qm", "FAM_FACTS_ADD"); print "</div><div class=\"description\">"; print GM_LANG_FAM_FACTS_ADD;?></div></td>
		<td class="shade1"><input type="text" name="NEW_FAM_FACTS_ADD" value="<?php print GedcomConfig::$FAM_FACTS_ADD; ?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("FAM_FACTS_UNIQUE_help", "qm", "FAM_FACTS_UNIQUE"); print "</div><div class=\"description\">"; print GM_LANG_FAM_FACTS_UNIQUE;?></div></td>
		<td class="shade1"><input type="text" name="NEW_FAM_FACTS_UNIQUE" value="<?php print GedcomConfig::$FAM_FACTS_UNIQUE; ?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("FAM_QUICK_ADDFACTS_help", "qm", "FAM_QUICK_ADDFACTS"); print "</div><div class=\"description\">"; print GM_LANG_FAM_QUICK_ADDFACTS;?></div></td>
		<td class="shade1"><input type="text" name="NEW_FAM_QUICK_ADDFACTS" value="<?php print GedcomConfig::$FAM_QUICK_ADDFACTS; ?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("SOUR_FACTS_ADD_help", "qm", "SOUR_FACTS_ADD"); print "</div><div class=\"description\">"; print GM_LANG_SOUR_FACTS_ADD;?></div></td>
		<td class="shade1"><input type="text" name="NEW_SOUR_FACTS_ADD" value="<?php print GedcomConfig::$SOUR_FACTS_ADD; ?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("SOUR_FACTS_UNIQUE_help", "qm", "SOUR_FACTS_UNIQUE"); print "</div><div class=\"description\">"; print GM_LANG_SOUR_FACTS_UNIQUE;?></div></td>
		<td class="shade1"><input type="text" name="NEW_SOUR_FACTS_UNIQUE" value="<?php print GedcomConfig::$SOUR_FACTS_UNIQUE; ?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("SOUR_QUICK_ADDFACTS_help", "qm", "SOUR_QUICK_ADDFACTS"); print "</div><div class=\"description\">"; print GM_LANG_SOUR_QUICK_ADDFACTS;?></div></td>
		<td class="shade1"><input type="text" name="NEW_SOUR_QUICK_ADDFACTS" value="<?php print GedcomConfig::$SOUR_QUICK_ADDFACTS; ?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("REPO_FACTS_ADD_help", "qm", "REPO_FACTS_ADD"); print "</div><div class=\"description\">"; print GM_LANG_REPO_FACTS_ADD;?></div></td>
		<td class="shade1"><input type="text" name="NEW_REPO_FACTS_ADD" value="<?php print GedcomConfig::$REPO_FACTS_ADD; ?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("REPO_FACTS_UNIQUE_help", "qm", "REPO_FACTS_UNIQUE"); print "</div><div class=\"description\">"; print GM_LANG_REPO_FACTS_UNIQUE;?></div></td>
		<td class="shade1"><input type="text" name="NEW_REPO_FACTS_UNIQUE" value="<?php print GedcomConfig::$REPO_FACTS_UNIQUE; ?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("REPO_QUICK_ADDFACTS_help", "qm", "REPO_QUICK_ADDFACTS"); print "</div><div class=\"description\">"; print GM_LANG_REPO_QUICK_ADDFACTS;?></div></td>
		<td class="shade1"><input type="text" name="NEW_REPO_QUICK_ADDFACTS" value="<?php print GedcomConfig::$REPO_QUICK_ADDFACTS; ?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("MEDIA_FACTS_ADD_help", "qm", "MEDIA_FACTS_ADD"); print "</div><div class=\"description\">"; print GM_LANG_MEDIA_FACTS_ADD;?></div></td>
		<td class="shade1"><input type="text" name="NEW_MEDIA_FACTS_ADD" value="<?php print GedcomConfig::$MEDIA_FACTS_ADD; ?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("MEDIA_FACTS_UNIQUE_help", "qm", "MEDIA_FACTS_UNIQUE"); print "</div><div class=\"description\">"; print GM_LANG_MEDIA_FACTS_UNIQUE;?></div></td>
		<td class="shade1"><input type="text" name="NEW_MEDIA_FACTS_UNIQUE" value="<?php print GedcomConfig::$MEDIA_FACTS_UNIQUE; ?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("MEDIA_QUICK_ADDFACTS_help", "qm", "MEDIA_QUICK_ADDFACTS"); print "</div><div class=\"description\">"; print GM_LANG_MEDIA_QUICK_ADDFACTS;?></div></td>
		<td class="shade1"><input type="text" name="NEW_MEDIA_QUICK_ADDFACTS" value="<?php print GedcomConfig::$MEDIA_QUICK_ADDFACTS; ?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("NOTE_FACTS_ADD_help", "qm", "NOTE_FACTS_ADD"); print "</div><div class=\"description\">"; print GM_LANG_NOTE_FACTS_ADD;?></div></td>
		<td class="shade1"><input type="text" name="NEW_NOTE_FACTS_ADD" value="<?php print GedcomConfig::$NOTE_FACTS_ADD; ?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("NOTE_FACTS_UNIQUE_help", "qm", "NOTE_FACTS_UNIQUE"); print "</div><div class=\"description\">"; print GM_LANG_NOTE_FACTS_UNIQUE;?></div></td>
		<td class="shade1"><input type="text" name="NEW_NOTE_FACTS_UNIQUE" value="<?php print GedcomConfig::$NOTE_FACTS_UNIQUE; ?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("NOTE_QUICK_ADDFACTS_help", "qm", "NOTE_QUICK_ADDFACTS"); print "</div><div class=\"description\">"; print GM_LANG_NOTE_QUICK_ADDFACTS;?></div></td>
		<td class="shade1"><input type="text" name="NEW_NOTE_QUICK_ADDFACTS" value="<?php print GedcomConfig::$NOTE_QUICK_ADDFACTS; ?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("EDIT_AUTOCLOSE_help", "qm", "EDIT_AUTOCLOSE"); print "</div><div class=\"description\">"; print GM_LANG_EDIT_AUTOCLOSE;?></div></td>
		<td class="shade1"><select name="NEW_EDIT_AUTOCLOSE" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$EDIT_AUTOCLOSE) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$EDIT_AUTOCLOSE) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("SPLIT_PLACES_help", "qm", "SPLIT_PLACES"); print "</div><div class=\"description\">"; print GM_LANG_SPLIT_PLACES;?></div></td>
		<td class="shade1"><select name="NEW_SPLIT_PLACES" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$SPLIT_PLACES) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$SPLIT_PLACES) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("USE_QUICK_UPDATE_help", "qm", "USE_QUICK_UPDATE", true); print "</div><div class=\"description\">"; print PrintText("USE_QUICK_UPDATE",0,0,false);?></div></td>
		<td class="shade1"><select name="NEW_USE_QUICK_UPDATE" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$USE_QUICK_UPDATE) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$USE_QUICK_UPDATE) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("SHOW_QUICK_RESN_help", "qm", "SHOW_QUICK_RESN", true); print "</div><div class=\"description\">"; print PrintText("SHOW_QUICK_RESN",0,0,false);?></div></td>
		<td class="shade1"><select name="NEW_SHOW_QUICK_RESN" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$SHOW_QUICK_RESN) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$SHOW_QUICK_RESN) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("QUICK_ADD_FACTS_help", "qm", "QUICK_ADD_FACTS"); print "</div><div class=\"description\">"; print GM_LANG_QUICK_ADD_FACTS;?></div></td>
		<td class="shade1"><input type="text" name="NEW_QUICK_ADD_FACTS" value="<?php print GedcomConfig::$QUICK_ADD_FACTS?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("QUICK_REQUIRED_FACTS_help", "qm", "QUICK_REQUIRED_FACTS"); print "</div><div class=\"description\">"; print GM_LANG_QUICK_REQUIRED_FACTS;?></div></td>
		<td class="shade1"><input type="text" name="NEW_QUICK_REQUIRED_FACTS" value="<?php print GedcomConfig::$QUICK_REQUIRED_FACTS?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("QUICK_ADD_FAMFACTS_help", "qm", "QUICK_ADD_FAMFACTS"); print "</div><div class=\"description\">"; print GM_LANG_QUICK_ADD_FAMFACTS;?></div></td>
		<td class="shade1"><input type="text" name="NEW_QUICK_ADD_FAMFACTS" value="<?php print GedcomConfig::$QUICK_ADD_FAMFACTS?>" size="80" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("QUICK_REQUIRED_FAMFACTS_help", "qm", "QUICK_REQUIRED_FAMFACTS"); print "</div><div class=\"description\">"; print GM_LANG_QUICK_REQUIRED_FAMFACTS;?></div></td>
		<td class="shade1"><input type="text" name="NEW_QUICK_REQUIRED_FAMFACTS" value="<?php print GedcomConfig::$QUICK_REQUIRED_FAMFACTS?>" size="40" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
</table>
</div>


<?php // User Options
?>
<table class="facts_table"><tr><td class="topbottombar <?php print $TEXT_DIRECTION;?>">
<?php
print "<a href=\"javascript: ".GM_LANG_useropt_conf."\" onclick=\"expand_layer('user-options');return false;\"><img id=\"user-options_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
print "&nbsp;<a href=\"javascript: ".GM_LANG_useropt_conf."\" onclick=\"expand_layer('user-options');return false;\">".GM_LANG_useropt_conf."</a>";
?></td></tr></table>
<div id="user-options" style="display: none">
<table class="facts_table">
	<tr>
		<td class="shade2 wrap width20"><div class="helpicon"><?php PrintHelpLink("ENABLE_MULTI_LANGUAGE_help", "qm", "ENABLE_MULTI_LANGUAGE"); print "</div><div class=\"description\">"; print GM_LANG_ENABLE_MULTI_LANGUAGE;?></div></td>
		<td class="shade1"><select name="NEW_ENABLE_MULTI_LANGUAGE" tabindex="<?php $i++; print $i?>" >
				<option value="yes" <?php if (GedcomConfig::$ENABLE_MULTI_LANGUAGE) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$ENABLE_MULTI_LANGUAGE) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("show_context_help_help", "qm", "show_contexthelp"); print "</div><div class=\"description\">"; print GM_LANG_show_contexthelp;?></div></td>
		<td class="shade1"><select name="NEW_SHOW_CONTEXT_HELP" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$SHOW_CONTEXT_HELP) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$SHOW_CONTEXT_HELP) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("THEME_DIR_help", "qm", "THEME_DIR"); print "</div><div class=\"description\">"; print GM_LANG_THEME_DIR;?></div></td>
		<td class="shade1">
			<select name="themeselect" dir="ltr" tabindex="<?php $i++; print $i?>"  onchange="document.configform.NTHEME_DIR.value=document.configform.themeselect.options[document.configform.themeselect.selectedIndex].value;">
				<?php
					$themes = GetThemeNames();
					foreach($themes as $indexval => $themedir) {
						print "<option value=\"".$themedir["dir"]."\"";
						if ($themedir["dir"] == $NTHEME_DIR) print " selected=\"selected\"";
						print ">".$themedir["name"]."</option>\n";
					}
				?>
				<option value="themes/" <?php if($themeselect=="themes//") print "selected=\"selected\""; ?>><?php print GM_LANG_other_theme; ?></option>
			</select>
			<input type="text" name="NTHEME_DIR" value="<?php print $NTHEME_DIR?>" size="40" dir="ltr" tabindex="<?php $i++; print $i?>" />
	<?php
	if (!file_exists($NTHEME_DIR)) {
		print "<span class=\"error\">$NTHEME_DIR ";
		print GM_LANG_does_not_exist;
		print "</span>\n";
		$NTHEME_DIR = GedcomConfig::$THEME_DIR;
	}
	?>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("ALLOW_THEME_DROPDOWN_help", "qm", "ALLOW_THEME_DROPDOWN"); print "</div><div class=\"description\">"; print GM_LANG_ALLOW_THEME_DROPDOWN;?></div></td>
		<td class="shade1"><select name="NEW_ALLOW_THEME_DROPDOWN" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$ALLOW_THEME_DROPDOWN) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$ALLOW_THEME_DROPDOWN) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
</table>
</div>



<table class="facts_table"><tr><td class="topbottombar <?php print $TEXT_DIRECTION;?>">
<?php
print "<a href=\"javascript: ".GM_LANG_contact_conf."\" onclick=\"expand_layer('contact-options');return false;\"><img id=\"contact-options_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
print "&nbsp;<a href=\"javascript: ".GM_LANG_contact_conf."\" onclick=\"expand_layer('contact-options');return false;\">".GM_LANG_contact_conf."</a>";
?></td></tr></table>
<div id="contact-options" style="display: none">
<table class="facts_table">
	<tr>
		<td class="shade2 wrap width20"><div class="helpicon"><?php PrintHelpLink("CONTACT_EMAIL_help", "qm", "CONTACT_EMAIL"); print "</div><div class=\"description\">"; print GM_LANG_CONTACT_EMAIL;?></div></td>
		<td class="shade1"><select name="NEW_CONTACT_EMAIL" tabindex="<?php $i++; print $i?>">
		<?php
			if (GedcomConfig::$CONTACT_EMAIL=="you@yourdomain.com") GedcomConfig::$CONTACT_EMAIL = $gm_user->username;
			$users = UserController::GetUsers("lastname", "asc", "firstname");
			foreach($users as $indexval => $user) {
				if ($user->verified_by_admin == "Y") {
					print "<option value=\"".$user->username."\"";
					if (GedcomConfig::$CONTACT_EMAIL == $user->username) print " selected=\"selected\"";
					print ">".$user->lastname.", ".$user->firstname." - ".$user->username."</option>\n";
				}
			}
		?>
		</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("CONTACT_METHOD_help", "qm", "CONTACT_METHOD"); print "</div><div class=\"description\">"; print GM_LANG_CONTACT_METHOD;?></div></td>
		<td class="shade1"><select name="NEW_CONTACT_METHOD" tabindex="<?php $i++; print $i?>">
		<?php if ($GM_STORE_MESSAGES) { ?>
				<option value="messaging" <?php if (GedcomConfig::$CONTACT_METHOD=='messaging') print "selected=\"selected\""; ?>><?php print GM_LANG_messaging;?></option>
				<option value="messaging2" <?php if (GedcomConfig::$CONTACT_METHOD=='messaging2') print "selected=\"selected\""; ?>><?php print GM_LANG_messaging2;?></option>
		<?php } else { ?>
				<option value="messaging3" <?php if (GedcomConfig::$CONTACT_METHOD=='messaging3') print "selected=\"selected\""; ?>><?php print GM_LANG_messaging3;?></option>
		<?php } ?>
				<option value="mailto" <?php if (GedcomConfig::$CONTACT_METHOD=='mailto') print "selected=\"selected\""; ?>><?php print GM_LANG_mailto;?></option>
				<option value="none" <?php if (GedcomConfig::$CONTACT_METHOD=='none') print "selected=\"selected\""; ?>><?php print GM_LANG_no_messaging;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("WEBMASTER_EMAIL_help", "qm", "WEBMASTER_EMAIL"); print "</div><div class=\"description\">"; print GM_LANG_WEBMASTER_EMAIL;?></div></td>
		<td class="shade1"><select name="NEW_WEBMASTER_EMAIL" tabindex="<?php $i++; print $i?>">
		<?php
			$users = UserController::GetUsers("lastname", "asc", "firstname");
			if (GedcomConfig::$WEBMASTER_EMAIL=="webmaster@yourdomain.com") GedcomConfig::$WEBMASTER_EMAIL = $gm_user->username;
			foreach($users as $indexval => $user) {
				if ($user->userIsAdmin()) {
					print "<option value=\"".$user->username."\"";
					if (GedcomConfig::$WEBMASTER_EMAIL==$user->username) print " selected=\"selected\"";
					print ">".$user->lastname.", ".$user->firstname." - ".$user->username."</option>\n";
				}
			}
		?>
		</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("SUPPORT_METHOD_help", "qm", "SUPPORT_METHOD"); print "</div><div class=\"description\">"; print GM_LANG_SUPPORT_METHOD;?></div></td>
		<td class="shade1"><select name="NEW_SUPPORT_METHOD" tabindex="<?php $i++; print $i?>">
		<?php if ($GM_STORE_MESSAGES) { ?>
				<option value="messaging" <?php if (GedcomConfig::$SUPPORT_METHOD=='messaging') print "selected=\"selected\""; ?>><?php print GM_LANG_messaging;?></option>
				<option value="messaging2" <?php if (GedcomConfig::$SUPPORT_METHOD=='messaging2') print "selected=\"selected\""; ?>><?php print GM_LANG_messaging2;?></option>
		<?php } else { ?>
				<option value="messaging3" <?php if (GedcomConfig::$SUPPORT_METHOD=='messaging3') print "selected=\"selected\""; ?>><?php print GM_LANG_messaging3;?></option>
		<?php } ?>
				<option value="mailto" <?php if (GedcomConfig::$SUPPORT_METHOD=='mailto') print "selected=\"selected\""; ?>><?php print GM_LANG_mailto;?></option>
				<option value="none" <?php if (GedcomConfig::$SUPPORT_METHOD=='none') print "selected=\"selected\""; ?>><?php print GM_LANG_no_messaging;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("BCC_WEBMASTER_help", "qm", "BCC_WEBMASTER"); print "</div><div class=\"description\">"; print GM_LANG_BCC_WEBMASTER;?></div></td>
		<td class="shade1"><select name="NEW_BCC_WEBMASTER" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$BCC_WEBMASTER) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$BCC_WEBMASTER) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
</table>
</div>
<table class="facts_table"><tr><td class="topbottombar <?php print $TEXT_DIRECTION;?>">
<?php
print "<a href=\"javascript: ".GM_LANG_meta_conf."\" onclick=\"expand_layer('config-meta');return false;\"><img id=\"config-meta_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
print "&nbsp;<a href=\"javascript: ".GM_LANG_meta_conf."\" onclick=\"expand_layer('config-meta');return false;\">".GM_LANG_meta_conf."</a>";
?></td></tr></table>
<div id="config-meta" style="display: none">
<table class="facts_table">
	<tr>
		<td class="shade2 wrap width20"><div class="helpicon"><?php PrintHelpLink("HOME_SITE_URL_help", "qm", "HOME_SITE_URL"); print "</div><div class=\"description\">"; print GM_LANG_HOME_SITE_URL;?></div></td>
		<td class="shade1"><input type="text" name="NEW_HOME_SITE_URL" value="<?php print GedcomConfig::$HOME_SITE_URL?>" size="50" dir="ltr" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("HOME_SITE_TEXT_help", "qm", "HOME_SITE_TEXT"); print "</div><div class=\"description\">"; print GM_LANG_HOME_SITE_TEXT;?></div></td>
		<td class="shade1"><input type="text" dir="ltr" name="NEW_HOME_SITE_TEXT" value="<?php print htmlspecialchars(GedcomConfig::$HOME_SITE_TEXT);?>" size="50" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("META_AUTHOR_help", "qm", "META_AUTHOR"); print "</div><div class=\"description\">"; print GM_LANG_META_AUTHOR;?></div></td>
		<td class="shade1"><input type="text" dir="ltr" name="NEW_META_AUTHOR" value="<?php print GedcomConfig::$META_AUTHOR?>" tabindex="<?php $i++; print $i?>" /><br />
		<?php print PrintText("META_AUTHOR_descr",0,0,false); ?></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("META_PUBLISHER_help", "qm", "META_PUBLISHER"); print "</div><div class=\"description\">"; print GM_LANG_META_PUBLISHER;?></div></td>
		<td class="shade1"><input type="text" dir="ltr" name="NEW_META_PUBLISHER" value="<?php print GedcomConfig::$META_PUBLISHER?>" tabindex="<?php $i++; print $i?>" /><br />
		<?php print PrintText("META_PUBLISHER_descr",0,0,false); ?></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("META_COPYRIGHT_help", "qm", "META_COPYRIGHT"); print "</div><div class=\"description\">"; print GM_LANG_META_COPYRIGHT;?></div></td>
		<td class="shade1"><input type="text" dir="ltr" name="NEW_META_COPYRIGHT" value="<?php print GedcomConfig::$META_COPYRIGHT?>" tabindex="<?php $i++; print $i?>" /><br />
		<?php print PrintText("META_COPYRIGHT_descr",0,0,false); ?></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("META_DESCRIPTION_help", "qm", "META_DESCRIPTION"); print "</div><div class=\"description\">"; print GM_LANG_META_DESCRIPTION;?></div></td>
		<td class="shade1"><input type="text" dir="ltr" name="NEW_META_DESCRIPTION" value="<?php print GedcomConfig::$META_DESCRIPTION?>" tabindex="<?php $i++; print $i?>" /><br />
		<?php print GM_LANG_META_DESCRIPTION_descr; ?></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("META_PAGE_TOPIC_help", "qm", "META_PAGE_TOPIC"); print "</div><div class=\"description\">"; print GM_LANG_META_PAGE_TOPIC;?></div></td>
		<td class="shade1"><input type="text" dir="ltr" name="NEW_META_PAGE_TOPIC" value="<?php print GedcomConfig::$META_PAGE_TOPIC?>" tabindex="<?php $i++; print $i?>" /><br />
		<?php print GM_LANG_META_PAGE_TOPIC_descr; ?></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("META_AUDIENCE_help", "qm", "META_AUDIENCE"); print "</div><div class=\"description\">"; print GM_LANG_META_AUDIENCE;?></div></td>
		<td class="shade1"><input type="text" dir="ltr" name="NEW_META_AUDIENCE" value="<?php print GedcomConfig::$META_AUDIENCE?>" tabindex="<?php $i++; print $i?>" /><br />
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("META_PAGE_TYPE_help", "qm", "META_PAGE_TYPE"); print "</div><div class=\"description\">"; print GM_LANG_META_PAGE_TYPE;?></div></td>
		<td class="shade1"><input type="text" dir="ltr" name="NEW_META_PAGE_TYPE" value="<?php print GedcomConfig::$META_PAGE_TYPE?>" tabindex="<?php $i++; print $i?>" /><br />
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("META_ROBOTS_help", "qm", "META_ROBOTS"); print "</div><div class=\"description\">"; print GM_LANG_META_ROBOTS;?></div></td>
		<td class="shade1"><input type="text" dir="ltr" name="NEW_META_ROBOTS" value="<?php print GedcomConfig::$META_ROBOTS?>" tabindex="<?php $i++; print $i?>" /><br />
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("META_REVISIT_help", "qm", "META_REVISIT"); print "</div><div class=\"description\">"; print GM_LANG_META_REVISIT;?></div></td>
		<td class="shade1"><input type="text" dir="ltr" name="NEW_META_REVISIT" value="<?php print GedcomConfig::$META_REVISIT?>" tabindex="<?php $i++; print $i?>" /><br />
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("META_KEYWORDS_help", "qm", "META_KEYWORDS"); print "</div><div class=\"description\">"; print GM_LANG_META_KEYWORDS;?></div></td>
		<td class="shade1"><input type="text" dir="ltr" name="NEW_META_KEYWORDS" value="<?php print GedcomConfig::$META_KEYWORDS?>" tabindex="<?php $i++; print $i?>" size="75" /><br />
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("META_SURNAME_KEYWORDS_help", "qm", "META_SURNAME_KEYWORDS"); print "</div><div class=\"description\">"; print GM_LANG_META_SURNAME_KEYWORDS;?></div></td>
		<td class="shade1"><select name="NEW_META_SURNAME_KEYWORDS" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if (GedcomConfig::$META_SURNAME_KEYWORDS) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!GedcomConfig::$META_SURNAME_KEYWORDS) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("META_TITLE_help", "qm", "META_TITLE"); print "</div><div class=\"description\">"; print GM_LANG_META_TITLE;?></div></td>
		<td class="shade1"><input type="text" dir="ltr" name="NEW_META_TITLE" value="<?php print GedcomConfig::$META_TITLE?>" tabindex="<?php $i++; print $i?>" size="75" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><div class="helpicon"><?php PrintHelpLink("RSS_FORMAT_help", "qm", "RSS_FORMAT"); print "</div><div class=\"description\">"; print GM_LANG_RSS_FORMAT;?></div></td>
		<td class="shade1"><select name="NEW_RSS_FORMAT" dir="ltr" tabindex="<?php $i++; print $i?>">
				<option value="RSS0.91" <?php if (GedcomConfig::$RSS_FORMAT=="RSS0.91") print "selected=\"selected\""; ?>>RSS 0.91</option>
				<option value="RSS1.0" <?php if (GedcomConfig::$RSS_FORMAT=="RSS1.0") print "selected=\"selected\""; ?>>RSS 1.0</option>
				<option value="RSS2.0" <?php if (GedcomConfig::$RSS_FORMAT=="RSS2.0") print "selected=\"selected\""; ?>>RSS 2.0</option>
				<option value="ATOM" <?php if (GedcomConfig::$RSS_FORMAT=="ATOM") print "selected=\"selected\""; ?>>ATOM</option>
				<option value="ATOM0.3" <?php if (GedcomConfig::$RSS_FORMAT=='messaging') print "selected=\"selected\""; ?>>ATOM 0.3</option>
			</select>
		</td>
	</tr>
</table>
</div>
<table class="facts_table" border="0">
<tr><td class="center">
<input type="submit" tabindex="<?php $i++; print $i?>" value="<?php print GM_LANG_save_config?>" onclick="closeHelp();" />
&nbsp;&nbsp;
<input type="reset" tabindex="<?php $i++; print $i?>" value="<?php print GM_LANG_reset?>" /><br />
</td></tr>
</table>
</form>
<br />
<?php if (count($GEDCOMS)==0) { ?>
<script language="JavaScript" type="text/javascript">
<!--
	helpPopup('welcome_new_help');
//-->
</script>
<?php
}
// NOTE: Put the focus on the GEDCOM title field since the GEDCOM path actually
// NOTE: needs no changing
?>
<script language="JavaScript" type="text/javascript">
<!--
	<?php if ($source == "") print "document.configform.gedcom_title.focus();";
	else print "document.configform.GEDCOMPATH.focus();";?>
//-->
</script>
<?php
SwitchGedcom();
PrintFooter();
?>
