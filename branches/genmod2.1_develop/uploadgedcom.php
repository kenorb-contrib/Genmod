<?php
/**
 * Allow admin users to upload a new gedcom using a web interface.
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
 * This Page Is Valid XHTML 1.0 Transitional! > 12 September 2005
 * 
 * @author Genmod Development Team
 * @package Genmod
 * @subpackage Admin
 * @version $Id: uploadgedcom.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
require "config.php";

if (!$gm_user->userGedcomAdmin()) {
	if (LOGIN_URL == "") header("Location: login.php?url=uploadgedcom.php");
	else header("Location: ".LOGIN_URL."?url=uploadgedcom.php");
	exit;
}

// Fix by Thomas for compression problems on Apache
if(function_exists('apache_setenv')) { 
	// apparently @ isn't enough to make php ignore this failing
	@apache_setenv('no-gzip', '1');
}

 // TODO: Progress bars don't show until </table> or </div>
 // TODO: Upload ZIP support alternative path and name

 
 // Editconfig.gedcom will have gedcom settings ready and a gedcom file in place. This script will
 // import the file in the DB, with some options along the way.
 // Exception is creation of a new, empty gedcom, where only the settings are in place but the gedccom must be created here.
 // On only one occasion this script is called directly: for import from the editgedcoms.php page,
 // which is logical because settings and gedcom file are already there.
 //
 // IINCOMING VARIABLES -------------------------------------------------------------------
 // $action = Which title and first block we should present
 // Possible values:
 // 	add_form		: add new gedcom already on server
 // 	upload form		: add new gedcom from upload
 // 	add_new_form	: add new gedcom from scratch
 // 	reupload_form	: re-upload and import an existing gedcom
 //		merge_form		: merge a file with an existing gedcom
	if (empty($action)) $action = "upload_form";

 // $gedcomid: Which gedcom to process
 	if (!isset($gedcomid) || !isset($GEDCOMS[$gedcomid])) $gedcomid = GedcomConfig::$GEDCOMID;
	
 // $import_existing = See if we are just importing an existing GEDCOM: the script is called from the import option of the gedcom in editgedcoms.
	if (!isset($import_existing)) $import_existing = false;
	
 // Important variables dring the process:
 
 // $step: determines where the upload is in the process
 	if (!isset($step)) $step = 1;
 
 	// Check if we uploaded a file for merge
 	if (isset($uploadfile) && !empty($uploadfile["name"])) {
	 	if (!AdminFunctions::CheckUploadedGedcom($uploadfile['name'], "uploadfile", false)) {
			$error_msg = GM_LANG_upload_error;
		}
		else {
			$mergefile = INDEX_DIRECTORY.AdminFunctions::MoveUploadedGedcom($uploadfile['name'], INDEX_DIRECTORY, "uploadfile");
		}
	}
 // $path: The path to the GEDCOM file. 
 // In case of upload, name/path are already stored in $GEDCOMS. Calculated here.
 // In case of merge, it's the name/path of the file being merged, so it is input from step 1.
 // Gedfilename is the name of the file + extension, path is only the path
 	if ($action == "merge_form") {
	 	if (!isset($mergefile)) $mergefile = "";
		$path = AdminFunctions::CalculateGedcomPath($mergefile);
		$gedfilename = basename($mergefile);
 	}
 	else {
		$path = AdminFunctions::CalculateGedcomPath($GEDCOMS[$gedcomid]["path"]);
		$gedfilename = $GEDCOMS[$gedcomid]["gedcom"];
	}

 // NOTE: $contine = When the user decided to move on to the next step
	
 
 // $override: 	yes or no to remove the existing gedcom from the DB and replace it with the new info. Also used for cancelling the import.
	if ((isset($override) && $override == "no") || (isset($cancel_import) && $cancel_import == "yes")) {
		unset($_SESSION["import"]);
		header("Location: editgedcoms.php");
	}
	
 // $timelimit: The time limit for the import process. At first it's set to the default value, which the user can change during this process.
	if (!isset($timelimit)) $timelimit = GedcomConfig::$TIME_LIMIT;
	
	if (!isset($merge_media)) $merge_media = GedcomConfig::$MERGE_DOUBLE_MEDIA;
	else GedcomConfig::$MERGE_DOUBLE_MEDIA = $merge_media;


// NOTE: Change header depending on action
if ($action == "upload_form" || $action == "reupload_form") $headertext = GM_LANG_upload_gedcom;
else if ($action == "add_form") $headertext = GM_LANG_add_gedcom;
else if ($action == "add_new_form") $headertext = GM_LANG_add_new_gedcom;
else if ($action == "merge_form") $headertext = GM_LANG_merge_gedcom;
else $headertext = GM_LANG_ged_import;
PrintHeader($headertext);
?>
<!-- Setup the left box -->
<div id="AdminColumnLeft">
	<?php AdminFunctions::AdminLink("admin.php", GM_LANG_admin); ?>
	<?php AdminFunctions::AdminLink("editgedcoms.php", GM_LANG_manage_gedcoms); ?>
</div>

<!-- Setup the middle box -->
<div id="AdminColumnMiddle">
<?php
// NOTE: Print form header
print "\n<form enctype=\"multipart/form-data\" method=\"post\" name=\"configform\" action=\"uploadgedcom.php\">";
print "\n<input type=\"hidden\" name=\"action\" value=\"".$action."\" />";
print "\n<input type=\"hidden\" name=\"gedcomid\" value=\"".$gedcomid."\" />";
print "\n<input type=\"hidden\" name=\"import_existing\" value=\"".(isset($import_existing) && $import_existing == true ? 1 : 0)."\" />";
print "\n<input type=\"hidden\" name=\"override\" value=\"".(isset($override) && $override == "yes" ? "yes" : "")."\" />";
print "\n<div class=\"NavBlockHeader AdminNavBlockHeader UploadGedcomNavBlockHeader\"><span class=\"AdminNavBlockTitle\">".$headertext."</span></div>";

// Step 1
// Add a new empty gedcom if requested
// Display the file section and check nothing.
// On merge, let the user select the gedcomid and the path/name of the file.

if ($step >= 1) {
	if ($action == "add_new_form") {
		if ($path != "") $fp = fopen($path.$gedfilename, "wb");
		else $fp = fopen(INDEX_DIRECTORY.$gedfilename, "wb");
		if ($fp) {
			$newgedcom = "0 HEAD
1 SOUR Genmod
2 VERS ".GM_VERSION." ".GM_VERSION_RELEASE."
1 DEST ANSTFILE
1 GEDC
2 VERS 5.5
2 FORM Lineage-Linked
1 CHAR UTF-8
0 @I1@ INDI
1 NAME Given Names /Surname/
2 GIVN Given Names
2 SURN Surname
1 SEX M
1 BIRT
2 DATE 01 JAN 1850
2 PLAC Click edit and change me
0 TRLR";
			fwrite($fp, $newgedcom);
			fclose($fp);
			$verify = "validate_form";
			// NOTE: Go straight to import, no other settings needed
			$marr_names = "no";
			$xreftype = "NA";
			$utf8convert = "no";
			$ged = $gedfilename;
			$step = 5;
			$auto_continue = "yes";
		}
	}
	
	// Print the first block: name of the gedcom file to import
		
	// Print the +/- for the admin box and the content for all actions except merge
	if ($action == "add_form") {
		if (!isset($import_existing) || $import_existing != true) $v1 = GM_LANG_add_gedcom;
		else $v1 = GM_LANG_ged_import;
		$layer = "add-form";
		$img = "add-form_img";
		$link1 = "add_gedcom_help";
		$link2 = "add_gedcom";
	}
	else if ($action == "upload_form" || $action == "reupload_form") {
		$v1 = GM_LANG_upload_gedcom;
		$layer = "upload_gedcom";
		$img = "upload_gedcom_img";
		$link1 = "upload_gedcom_help";
		$link2 = "upload_gedcom";
	}
	else if ($action == "add_new_form") {
		$v1 = GM_LANG_add_new_gedcom;
		$layer = "add-form";
		$img = "add_new_gedcom_img";
		$link1 = "add_gedcom_instructions";
		$link2 = "add_new_gedcom";
	}
	else if ($action == "merge_form") {
		$v1 = GM_LANG_merge_what;
		$layer = "add-form";
		$img = "add-form_img";
		$link1 = "merge_gedcom_help";
		$link2 = "merge_gedcom";
	}
	print "\n\n<div class=\"NavBlockHeader UploadGedcomSubHeader\">";
		print "<a href=\"javascript: ";
		print $v1."\" onclick=\"expand_layer('".$layer."');return false\"><img id=\"".$img."\" src=\"".GM_IMAGE_DIR."/";
		if ($step > 3) print $GM_IMAGES["plus"]["other"];
		else print $GM_IMAGES["minus"]["other"];
		print "\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
		PrintHelpLink($link1, "qm", $link2);
		print "&nbsp;<a href=\"javascript: ";
		print $v1;
		print "\" onclick=\"expand_layer('".$layer."');return false;\">";
		print $v1;
		print "</a>";
	print "\n</div>";
		
	$i = 0;
	if ($action != "merge_form") {
		// Now print the content of this section: filename
		print "\n<div id=\"".$layer."\" class=\"NavBlockLabel UploadGedcomNavBlockLabel\" style=\"display: ";
			if ($step > 3) print "none ";
			else print "block ";
			print "\">";
			PrintHelpLink("gedcom_path_help", "qm","gedcom_path");
			print GM_LANG_gedcom_file;
			// Actually this is dummy. The path and gedfilename are calculated from the gedcomid and not from this variable.
			print "&nbsp;\n<input type=\"text\" name=\"gedfilename\" value=\"".$path.$gedfilename."\" size=\"60\" dir=\"ltr\" tabindex=\"".$i."\" disabled=\"disabled\" />";
		print "\n<!-- Close file block //--></div>";
	
		// if we have nothing to do here, we can proceed
		if ($step == 1) $step = 2;
	}
	else {
		if ($step == 1 && !empty($gedfilename)) $step = 3;
		// Print the inputs for the mergefile
		// 1. File on the server
		print "<div class=\"NavBlockLabel UploadGedcomNavBlockLabel\" id=\"".$layer."\" style=\"display: ";
			if ($step > 3) print "none ";
			else print "block ";
			print "\">";
			$ferror = "";
			if (!empty($gedfilename) && !file_exists($path.$gedfilename)) {
				$ferror = "<span class=\"Error\">".GM_LANG_file_not_found."&nbsp;".$path.$gedfilename."</span>\n";
				$step = 1;
				$gedfilename = "";
			}
			PrintHelpLink("gedcom_path_help", "qm","gedcom_path");
			print GM_LANG_gedcom_file."&nbsp;&nbsp;";
			print "<input type=\"text\" name=\"mergefile\" value=\"".(isset($gedfilename) && strlen($gedfilename) > 4 ? $path.$gedfilename : "")."\" size=\"60\" dir=\"ltr\" tabindex=\"".$i."\"" .($step > 1 ? "disabled=\"disabled\" " : "")." />&nbsp;&nbsp;" . $ferror;
			if ($step > 1) print "\n<input type=\"hidden\" name=\"mergefile\" value=\"".$mergefile."\" />";
		print "</div>";
		// 2. Upload file. Show only if no gedfilename is know.
		if (empty($gedfilename)) {
			// Print the bar
			print "<div class=\"NavBlockHeader UploadGedcomSubHeader\">";
				print "<a href=\"javascript: ".GM_LANG_merge_what_upload."\" onclick=\"expand_layer('upload_gedcom'); return false;\"><img id=\"upload_gedcom_img\" src=\"".GM_IMAGE_DIR."/";
				if ($step > 2) print $GM_IMAGES["plus"]["other"];
				else print $GM_IMAGES["minus"]["other"];
				print "\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
				PrintHelpLink("upload_gedcom_help", "qm", "upload_gedcom");
				print "&nbsp;<a href=\"javascript: ".GM_LANG_merge_what_upload."\" onclick=\"expand_layer('upload_gedcom');return false\">".GM_LANG_merge_what_upload."</a>";
			print "</div>";
			// Print the content
			print "<div class=\"NavBlockLabel UploadGedcomNavBlockLabel\" id=\"upload_gedcom\" style=\"display: ";
				if ($step > 2) print "none ";
				else print "block ";
				print "\">";
				print GM_LANG_gedcom_file."&nbsp;&nbsp;";
				print "<input name=\"uploadfile\" type=\"file\" size=\"60\" />";
				if (!$filesize = ini_get('upload_max_filesize')) $filesize = "2M";
				print " (".GM_LANG_max_upload_size." ".$filesize.")".(isset($error_msg) ? "&nbsp;<span class=\"Error\">".$error_msg."</span>" : "");
				if (isset($error_msg)) $step = 1;
			print "</div>";
		}
		// 3. File to merge with
		print "<div class=\"NavBlockHeader UploadGedcomSubHeader\">";
			print "<a href=\"javascript: ".GM_LANG_merge_with."\" onclick=\"expand_layer('merge_gedcom'); return false;\"><img id=\"merge_gedcom_img\" src=\"".GM_IMAGE_DIR."/";
			if ($step > 3) print $GM_IMAGES["plus"]["other"];
			else print $GM_IMAGES["minus"]["other"];
			print "\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
			PrintHelpLink("upload_gedcom_help", "qm", "upload_gedcom");
			print "&nbsp;<a href=\"javascript: ".GM_LANG_merge_with."\" onclick=\"expand_layer('merge_gedcom');return false\">".GM_LANG_merge_with."</a>";
		print "</div>";
		print "<div class=\"NavBlockLabel UploadGedcomNavBlockLabel\" id=\"merge_gedcom\" style=\"display: ";
			if ($step > 3) print "none ";
			else print "block ";
			print "\">";
			print GM_LANG_merge_with."&nbsp;&nbsp;";
			print "<select name=\"gedcomid\"";
			if ($step > 1) print "disabled=\"disabled\" ";
			print ">";
			foreach($GEDCOMS as $gedc=>$gedarray) {
				print "<option value=\"".$gedc."\"";
				if (isset($gedcomid)) {
					if ($gedcomid == $gedc) print " selected=\"selected\"";
				}
				else if (GedcomConfig::$GEDCOMID == $gedc) print " selected=\"selected\"";
				print ">".PrintReady($gedarray["title"])."</option>";
			}
			print "</select>";
		print "</div>";
	}
}

// Step 2: Check if the file already exists in the DB. If so, the user must choose what to do.
// If the user chooses no, the script will redirect to editgedcoms.php
// If the user chooses yes, the script will continue to step 3.
if ($step == 2) {
	// Check if GEDCOM has been imported into DB
	$imported = CheckForImport($gedfilename);
	if ($imported && (!isset($override) || $override != "yes")) {
		// print the second block
		print "\n\n<div class=\"NavBlockHeader UploadGedcomSubHeader\">";
			print "\n<a href=\"javascript: ".GM_LANG_verify_gedcom."\" onclick=\"expand_layer('verify_gedcom');return false\"><img id=\"verify_gedcom_img\" src=\"".GM_IMAGE_DIR."/";
			print $GM_IMAGES["minus"]["other"];
			print "\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
			PrintHelpLink("verify_gedcom_help", "qm", "verify_gedcom");
			print "&nbsp;<a href=\"javascript: ".GM_LANG_verify_gedcom."\" onclick=\"expand_layer('verify_gedcom');return false\">".GM_LANG_verify_gedcom."</a>";
		print "\n</div>";
		print "\n<div id=\"verify_gedcom\" class=\"NavBlockLabel UploadGedcomNavBlockLabel\" style=\"display: block;\">";
			print "<span class=\"Error\">".GM_LANG_dataset_exists."</span><br /><br />";
		// TODO: Check for existing changes
			print "\n<div>". GM_LANG_empty_dataset."&nbsp;";
				print "<select name=\"override\">";
				print "<option value=\"no\">".GM_LANG_no."</option>";
				print "<option value=\"yes\">".GM_LANG_yes."</option>";
				print "</select>";
			print "\n</div>";
		print "\n<!-- Close empty DB block //--></div>";
		print "<input type=\"hidden\" name=\"step\" value=\"2\" />";
	}
	else $step = 3;
}

// Step 3: Check if cleanup is needed. If so, the user van choose what to do.
if ($step >= 3) {
	
	// If we execute step 3 for the second time, cleanup was needed and the user accepted, perform the cleanup first.
	if ($step == 3 && isset($cleanup_needed) && $cleanup_needed == "yes" && isset($continue) && $continue == GM_LANG_del_proceed) {
		$filechanged=false;
		if (AdminFunctions::FileIsWriteable($path.$gedfilename) && file_exists($path.$gedfilename)) {
			$l_headcleanup = false;
			$l_macfilecleanup = false;
			$l_lineendingscleanup = false;
			$l_placecleanup = false;
			$l_datecleanup=false;
			$l_isansi = false;
			$fp = fopen($path.$gedfilename, "rb");
			$fw = fopen(INDEX_DIRECTORY."/".$gedfilename.".bak", "wb");
			//-- read the gedcom and test it in 8KB chunks
			while(!feof($fp)) {
				$fcontents = fread($fp, 1024*8);
				$lineend = "\n";
				if (ImportFunctions::NeedMacfileCleanup($fcontents)) {
					$l_macfilecleanup=true;
					$lineend = "\r";
				}
				
				//-- read ahead until the next line break
				$byte = "";
				while((!feof($fp)) && ($byte!=$lineend)) {
					$byte = fread($fp, 1);
					$fcontents .= $byte;
				}
				
				// Remove heading spaces from the gedlines
				$fcontents = preg_replace("/\n\W+/", "\n", $fcontents);
				
				if (!$l_headcleanup && ImportFunctions::NeedHeadCleanup($fcontents)) {
					ImportFunctions::HeadCleanup($fcontents);
					$l_headcleanup = true;
				}
		
				if ($l_macfilecleanup) {
					ImportFunctions::MacfileCleanup();
				}
		
				if (isset($_POST["cleanup_places"]) && $_POST["cleanup_places"]=="yes") {
					if(($sample = ImportFunctions::NeedPlaceCleanup($fcontents)) !== false) {
						$l_placecleanup=true;
						ImportFunctions::PlaceCleanup();
					}
				}
		
				if (ImportFunctions::LineEndingsCleanup()) {
					$filechanged = true;
				}
		
				if(isset($_POST["datetype"])) {
					$filechanged=true;
					//month first
					ImportFunctions::DateCleanup($_POST["datetype"]);
				}
				if (isset($_POST["utf8convert"])=="yes") {
					$filechanged=true;
					ImportFunctions::ConvertAnsiUtf8();
				}
				fwrite($fw, $fcontents);
			}
			fclose($fp);
			fclose($fw);
			copy(INDEX_DIRECTORY."/".$gedfilename.".bak", $path.$gedfilename);
			$cleaned = "yes";
			$step = 4;
		}
		else {
			$error = str_replace("#GEDCOM#", $gedfilename, GM_LANG_error_header_write);
		}
	}
	// If the gedcom is not checked for cleanup, do it now. 
	// After checking, the appriopriate form will display.
	print "\n\n<div class=\"NavBlockHeader UploadGedcomSubHeader\">";
		print "\n<a href=\"javascript: ".GM_LANG_validate_gedcom."\" onclick=\"expand_layer('validate_gedcom');return false\"><img id=\"validate_gedcom_img\" src=\"".GM_IMAGE_DIR."/";
		if ($step > 3) print $GM_IMAGES["plus"]["other"];
		else print $GM_IMAGES["minus"]["other"];
		print "\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
		PrintHelpLink("validate_gedcom_help", "qm","validate_gedcom");
		print "&nbsp;\n<a href=\"javascript: ".GM_LANG_validate_gedcom."\" onclick=\"expand_layer('validate_gedcom');return false\">".GM_LANG_validate_gedcom."</a>";
	print "\n</div>";
	print "\n<div id=\"validate_gedcom\" class=\"NavBlockLabel UploadGedcomNavBlockLabel\" style=\"display: ";
		if ($step > 3) print "none ";
		else print "block ";
		print "\">";
		print GM_LANG_performing_validation."<br />";
		if (isset($error) && !empty($error)) print "<span class=\"Error\">".$error."</span>\n";
		// Check for cleanup, skip not clicked
		if ($step == 3 && (!isset($skip_cleanup) || $skip_cleanup != GM_LANG_skip_cleanup)) {
			$l_headcleanup = false;
			$l_macfilecleanup = false;
			$l_lineendingscleanup = false;
			$l_placecleanup = false;
			$l_datecleanup=false;
			$l_isansi = false;
			$fp = fopen($path.$gedfilename, "r");
			if (!$fp) {
				print "error opening file ".$path.$gedfilename;
				exit;
			}
			//-- read the gedcom and test it in 8KB chunks
			while(!feof($fp)) {
				$fcontents = fread($fp, 1024*8);
				$fcontents = preg_replace("/\n\W+/", "\n", $fcontents);
				
				if (!$l_headcleanup && ImportFunctions::NeedHeadCleanup($fcontents)) $l_headcleanup = true;
				if (!$l_macfilecleanup && ImportFunctions::NeedMacfileCleanup($fcontents)) $l_macfilecleanup = true;
				if (!$l_lineendingscleanup && ImportFunctions::NeedLineEndingsCleanup($fcontents)) $l_lineendingscleanup = true;
				if (!$l_placecleanup && ($placesample = ImportFunctions::NeedPlaceCleanup($fcontents)) !== false) $l_placecleanup = true;
				if (!$l_datecleanup && ($datesample = ImportFunctions::NeedDateCleanup($fcontents)) !== false) $l_datecleanup = true;
				if (!$l_isansi && ImportFunctions::IsAnsi($fcontents)) $l_isansi = true;
			}
			fclose($fp);
			
			if (!$l_datecleanup && !$l_isansi  && !$l_headcleanup && !$l_macfilecleanup &&!$l_placecleanup && !$l_lineendingscleanup) {
				print GM_LANG_valid_gedcom;
				$step = 4;
			}
			else {
				$cleanup_needed = "yes";
				print "<input type=\"hidden\" name=\"cleanup_needed\" value=\"yes\">";
				if (!AdminFunctions::FileIsWriteable($path.$gedfilename) && (file_exists($path.$gedfilename))) {
					print "<div class=\"NavBlockField UploadGedcomErrorBox\">";
					print "<span class=\"Error\">".str_replace("#GEDCOM#", get_gedcom_from_id(GedcomConfig::$GEDCOMID), GM_LANG_error_header_write)."</span>\n";
					print "</div>";
				}
				// NOTE: Check for head cleanu
				if ($l_headcleanup) {
					print "<div class=\"NavBlockField UploadGedcomErrorBox\">";
						PrintHelpLink("invalid_header_help", "qm", "invalid_header");
						print "<span class=\"Error\">".GM_LANG_invalid_header."</span>\n";
					print "</div>";
				}
				// NOTE: Check for mac file cleanup
				if ($l_macfilecleanup) {
					print "<div class=\"NavBlockField UploadGedcomErrorBox\">";
						PrintHelpLink("macfile_detected_help", "qm", "macfile_detected");
						print "<span class=\"Error\">".GM_LANG_macfile_detected."</span>\n";
					print "</div>";
				}
				// NOTE: Check for line endings cleanup
				if ($l_lineendingscleanup) {
					print "<div class=\"NavBlockField UploadGedcomErrorBox\">";
						PrintHelpLink("empty_lines_detected_help", "qm", "empty_lines_detected");
						print "<span class=\"Error\">".GM_LANG_empty_lines_detected."</span>\n";
					print "</div>";
				}
				// NOTE: Check for place cleanup
				if ($l_placecleanup) {
					print "<div class=\"NavBlockField UploadGedcomErrorBox\">";
					PrintHelpLink("cleanup_places_help", "qm", "cleanup_places");
					print "<span class=\"Error\">".GM_LANG_place_cleanup_detected."</span>\n";
					print "<br /><br />";
					print GM_LANG_example_place."<br />".PrintReady(nl2br($placesample[0]))."\n";
						print GM_LANG_cleanup_places."&nbsp;&nbsp;";
						print "<select name=\"cleanup_places\">\n";
						print "<option value=\"yes\" selected=\"selected\">".GM_LANG_yes."</option>\n<option value=\"no\">".GM_LANG_no."</option>\n</select><br />";
					print "</div>";
				}
				// NOTE: Check for date cleanup
				if ($l_datecleanup) {
					print "<div class=\"NavBlockField UploadGedcomErrorBox\">";
						PrintHelpLink("detected_date_help", "qm");
						print "<span class=\"Error\">".GM_LANG_invalid_dates."</span>\n";
						print "<br /><br />";
						print GM_LANG_example_date."<br />".$datesample[0]."<br />";
						if (isset($datesample["choose"])){
							print GM_LANG_date_format."&nbsp;&nbsp;";
							print "<select name=\"datetype\">\n";
							print "<option value=\"1\">".GM_LANG_day_before_month."</option>\n<option value=\"2\">".GM_LANG_month_before_day."</option>\n</select>";
						}
						else print "<input type=\"hidden\" name=\"datetype\" value=\"3\" />";
					print "</div>";
				}
				// NOTE: Check for ansi encoding
				if ($l_isansi) {
					print "<div class=\"NavBlockField UploadGedcomErrorBox\">";
						PrintHelpLink("detected_ansi2utf_help", "qm", "ansi_to_utf8");
						print "<span class=\"Error\">".GM_LANG_ansi_encoding_detected."</span>\n";
						print "<br /><br />";
						print GM_LANG_ansi_to_utf8."&nbsp;&nbsp;";
						print "<select name=\"utf8convert\">\n";
						print "<option value=\"yes\" selected=\"selected\">".GM_LANG_yes."</option>\n";
						print "<option value=\"no\">".GM_LANG_no."</option>\n</select>";
					print "</div>";
				}
			}
		}
		// Not step 3 or skip cleanup
		if ((!isset($cleanup_needed) || $cleanup_needed != "yes") || (isset($skip_cleanup) && $skip_cleanup == GM_LANG_skip_cleanup)) {
			print GM_LANG_valid_gedcom;
			if ($step == 3) $step = 4;
		}
		else print "<input type=\"hidden\" name=\"step\" value=\"3\" />";
		if (isset($cleaned)) {
			print GM_LANG_cleanup_success;
			print "<input type=\"hidden\" name=\"cleaned\" value=\"yes\" />"; // next time also display this message
		}
	print "<!-- Close validation block //--></div>";
}


if ($step >= 4) {
	// NOTE: Additional import options
	print "\n\n<div class=\"NavBlockHeader UploadGedcomSubHeader\">";
		print "<a href=\"javascript: ".GM_LANG_import_options."\" onclick=\"expand_layer('import_options');return false\"><img id=\"import_options_img\" src=\"".GM_IMAGE_DIR."/";
		if ($step > 4) print $GM_IMAGES["plus"]["other"];
		else print $GM_IMAGES["minus"]["other"];
		print "\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
		PrintHelpLink("import_options_help", "qm", "import_options");
		print "&nbsp;<a href=\"javascript: ".GM_LANG_import_options."\" onclick=\"expand_layer('import_options');return false\">".GM_LANG_import_options."</a>";
	print "</div>";
	print "\n<div id=\"import_options\" style=\"display: ";
		if ($step > 4) print "none ";
		else print "block ";
		print "\">";
		print "<table class=\"NavBlockTable UploadGedcomNavBlockTable\">";
	
		// NOTE: Time limit for import
		// TODO: Write help text
		print "<tr><td class=\"NavBlockLabel UploadGedcomNavBlockLabel\">";
			PrintHelpLink("time_limit_help", "qm", "time_limit");
			print GM_LANG_time_limit;
			print "</td><td class=\"NavBlockField UploadGedcomNavBlockField\">";
			if ($step > 4)  {
				print $timelimit;
				print "<input type=\"hidden\" name=\"timelimit\" value=\"".$timelimit."\" />";
			}
			else print "<input type=\"text\" name=\"timelimit\" value=\"".$timelimit."\" size=\"5\" />\n";
		print "</td></tr>";
		
		// NOTE: Auto-click "Continue" button
		print "<tr><td class=\"NavBlockLabel UploadGedcomNavBlockLabel\">";
		PrintHelpLink("auto_ontinue_help", "qm", "auto_continue");
		print GM_LANG_auto_continue;
		print "</td><td class=\"NavBlockField UploadGedcomNavBlockField\">";
		if ($step > 4)  {
			print constant("GM_LANG_".$auto_continue);
			print "<input type=\"hidden\" name=\"auto_continue\" value=\"".$auto_continue."\" />";
		}
		else {
			print "<select name=\"auto_continue\"";
			print ">\n";
			print "<option value=\"yes\">".GM_LANG_yes."</option>\n";
			print "<option value=\"no\">".GM_LANG_no."</option>\n</select>";
		}
		print "</td></tr>";

		// NOTE: Import married names
		print "<tr><td class=\"NavBlockLabel UploadGedcomNavBlockLabel\">";
		PrintHelpLink("import_marr_names_help", "qm", "import_marr_names");
		print GM_LANG_import_marr_names.":";
		print "</td><td class=\"NavBlockField UploadGedcomNavBlockField\">";
		if ($step > 4) {
			print constant("GM_LANG_".$marr_names);
			print "<input type=\"hidden\" name=\"marr_names\" value=\"".$marr_names."\" />";
		}
		else {
			print "<select name=\"marr_names\">\n";
			print "<option value=\"yes\"";
			if (isset($marr_names) && $marr_names == "yes") print " selected=\"selected\"";
			print ">".GM_LANG_yes."</option>\n";
			print "<option value=\"no\"";
			if (!isset($marr_names) || $marr_names == "no") print " selected=\"selected\"";
			print ">".GM_LANG_no."</option>\n</select>";
		}
		print "</td></tr>";
		
		// NOTE: change XREF to RIN, REFN, or Don't change
		print "<tr><td class=\"NavBlockLabel UploadGedcomNavBlockLabel\">";
		PrintHelpLink("change_indi2id_help", "qm", "change_id");
		print GM_LANG_change_id;
		print "</td><td class=\"NavBlockField UploadGedcomNavBlockField\">";
		if ($step > 4) {
			if ($xreftype == "NA") print GM_LANG_do_not_change;
			else print $xreftype;
			print "<input type=\"hidden\" name=\"xreftype\" value=\"".$xreftype."\" />";
		}
		else {
			print "<select name=\"xreftype\">\n";
			print "<option value=\"NA\">".GM_LANG_do_not_change."</option>\n<option value=\"RIN\">RIN</option>\n";
			print "<option value=\"REFN\">REFN</option>\n</select>";
		}
		print "</td></tr>";
		
		// NOTE: option to convert to utf8
		print "<tr><td class=\"NavBlockLabel UploadGedcomNavBlockLabel\">";
		PrintHelpLink("convert_ansi2utf_help", "qm", "ansi_to_utf8");
		print GM_LANG_ansi_to_utf8;
		print "</td><td class=\"NavBlockField UploadGedcomNavBlockField\">";
		if ($step > 4) {
			print constant("GM_LANG_".strtolower($utf8convert));
			print "<input type=\"hidden\" name=\"utf8convert\" value=\"".$utf8convert."\" />";
		}
		else {
			print "<select name=\"utf8convert\">\n";
			print "<option value=\"yes\">".GM_LANG_yes."</option>\n";
			print "<option value=\"no\" selected=\"selected\">".GM_LANG_no."</option>\n</select>";
		}
		print "</td></tr>";

		// NOTE: option to merge double embedded MM items
		print "<tr><td class=\"NavBlockLabel UploadGedcomNavBlockLabel\">";
		PrintHelpLink("MERGE_DOUBLE_MEDIA_help", "qm", "MERGE_DOUBLE_MEDIA");
		print GM_LANG_MERGE_DOUBLE_MEDIA;
		print "</td><td class=\"NavBlockField UploadGedcomNavBlockField\">";
		if ($step > 4) {
			print constant("GM_LANG_merge_dm_".$merge_media);
			print "<input type=\"hidden\" name=\"merge_media\" value=\"".$merge_media."\" />";
		}
		else {
			print "<select name=\"merge_media\">\n";
			print "<option value=\"0\" ";
			if (GedcomConfig::$MERGE_DOUBLE_MEDIA == "0") print "selected=\"selected\"";
			print ">".GM_LANG_merge_dm_0."</option>";
			print "<option value=\"1\" ";
			if (GedcomConfig::$MERGE_DOUBLE_MEDIA == "1" || empty($MERGE_DOUBLE_MEDIA)) print "selected=\"selected\"";
			print ">".GM_LANG_merge_dm_1."</option>";
			print "<option value=\"2\" ";
			if (GedcomConfig::$MERGE_DOUBLE_MEDIA == "2") print "selected=\"selected\"";
			print ">".GM_LANG_merge_dm_2."</option>\n</select>";
		}
		print "</td></tr></table>";
						
		if ($step == 4) print "<input type=\"hidden\" name=\"step\" value=\"5\" />";
	print "<!-- Close option block //--></div>";
}

if ($step == 5 || $step == 6) {
	$temp = GedcomConfig::$THEME_DIR;
	SwitchGedcom($gedcomid);
	if ($LANGUAGE <> $_SESSION["CLANGUAGE"]) $LANGUAGE = $_SESSION["CLANGUAGE"];
	GedcomConfig::$THEME_DIR = $temp;
	$marr_importtime = 0;
}

if ($step == 5) {
	$oldtime = time();
	
	if (!isset($stage)) $stage = 0;
	
	if ($stage == 0) {
		$_SESSION["import"]["resumed"] = 0;
		if ($action != "merge_form") ImportFunctions::EmptyDatabase($gedcomid);
		$stage=1;
		if ($action == "merge_form") {
			if (!AdminFunctions::MakeTransTab($path.$gedfilename, $gedcomid)) exit;
		}
	}
	flush();
	@ob_flush();

	if ($stage==1) {
		//$timelimit = 20;
		// NOTE: Running in safe mode set_time_limit does not work
		if (!ini_get('safe_mode')) @set_time_limit($timelimit);
		
		$FILE_SIZE = filesize($path.$gedfilename);
		print "\n<div class=\"NavBlockHeader UploadGedcomSubHeader\">";
		print GM_LANG_reading_file." ".$path.$gedfilename;
		print "</div>";
		print "<div class=\"NavBlockLabel UploadGedcomNavBlockLabel\">";
		ImportFunctions::SetupProgressBar($FILE_SIZE);
		flush();
		@ob_flush();
		// ------------------------------------------------------ Begin importing data
		$i=0;
		if ($action == "merge_form") {
			$ft = fopen(INDEX_DIRECTORY."transtab.txt", "rb");
			$ftt = fread($ft, filesize(INDEX_DIRECTORY."transtab.txt"));
			fclose($ft);
			$transtab = unserialize($ftt);
			reset($transtab);
			unset($ftt);
			$skiprecs = array("HEAD", "SUBM", "TRLR");
			$reftype = array("HUSB"=>"INDI", "WIFE"=>"INDI", "CHIL"=>"INDI", "ALIA"=>"INDI", "ASSO"=>"INDI", "FAMC"=>"FAM", "FAMS"=>"FAM", "SOUR"=>"SOUR", "REPO"=>"REPO", "OBJE"=>"OBJE", "NOTE"=>"NOTE");
		}
		$fpged = fopen($path.$gedfilename, "rb");
		$BLOCK_SIZE = 1024*4;	//-- 4k bytes per read
		$fcontents = ""; //ok
		$TOTAL_BYTES = 0; //ok
		$place_count = 0; //ok
		$date_count = 0; //ok
		$media_count = 0; //ok
		$listtype= array(); //ok
		$importtime = 0; // Total import time
		// NOTE: Resume a halted import from the session
		if (!empty($_SESSION["import"]["resumed"])) {
				$place_count	= $_SESSION["import"]["place_count"]; //ok
				$date_count		= $_SESSION["import"]["date_count"]; //ok
				$found_ids		= $_SESSION["import"]["found_ids"]; // For UpdateMedia
				$media_count	= $_SESSION["import"]["media_count"]; // For UpdateMedia
				$TOTAL_BYTES	= $_SESSION["import"]["TOTAL_BYTES"]; //ok
				$fcontents		= $_SESSION["import"]["fcontents"]; //ok
				$listtype		= $_SESSION["import"]["listtype"]; //ok
				if (isset($_SESSION["import"]["lastgid"])) $lastgid = $_SESSION["import"]["lastgid"];
				if (isset($_SESSION["import"]["importtime"])) $importtime = $_SESSION["import"]["importtime"];
				fseek($fpged, $TOTAL_BYTES);
		}
		else $_SESSION["import"]["resumed"] = 0;
		if (!isset($lastgid)) {
			ImportFunctions::LockTables();
			NameFunctions::DMSoundex("", "opencache");
			$end = false;
			$pos1 = 0;
			while(!$end) {
				$pos2 = 0;
				// NOTE: find the start of the next record
				if (strlen($fcontents) > $pos1+1) $pos2 = strpos($fcontents, "\n0", $pos1+1);
					
				while((!$pos2)&&(!feof($fpged))) {
					$fcontents .= fread($fpged, $BLOCK_SIZE);
					$TOTAL_BYTES += $BLOCK_SIZE;
					$pos2 = strpos($fcontents, "\n0", $pos1+1);
				}
					
				//-- pull the next record out of the file
				if ($pos2) $indirec = substr($fcontents, $pos1, $pos2-$pos1);
				else $indirec = substr($fcontents, $pos1);
					
				//-- remove any extra slashes
				$indirec = preg_replace("/\\\/", "/", $indirec);
					
				// NOTE: Clean up the indi record
				$clean_indirec = preg_split("/(\r\n|\r|\n)/", $indirec);
				$indirec = "";
				foreach ($clean_indirec as $key => $line) {
					$indirec .= trim($line)."\r\n";
				}
				$indirec = trim($indirec);
					
				//-- Rename PGVU references to GMU
				$indirec = str_replace("2 _PGVU", "2 _GMU", $indirec);
				
				if ($action == "merge_form") $ct = preg_match("/0\s+@(.*)@\s+(\w*)/", $indirec, $match);
				
				// On normal import, import all records
				// On merge, only recordtypes not in the array skiprecs must be imported
				if ($action != "merge_form" || ($ct && !in_array(trim($match[2]), $skiprecs))) {
					// Only renumber on merge
					if ($action == "merge_form") {
						if (array_key_exists(trim($match[2]), $transtab) && array_key_exists($match[1], $transtab[trim($match[2])])) $indirec = preg_replace("/@$match[1]@/", "@".$transtab[trim($match[2])][$match[1]]."@", $indirec);
						else print "Problem with: ".$indirec."<br />This record cannot be renumbered, as the recordtype could not be determined earlier. Key: ".$match[2]."<br />";
						$indisubrecs = preg_split("/\r\n/", $indirec);
						$indiline = "";
						foreach ($indisubrecs as $key => $subrec) {
							$ct2 = preg_match_all("/\n\d\s(\w+)\s@(\w+)@/", "\n".$subrec, $match2);
							if ($ct2 && !in_array(trim($match[2]), $skiprecs)) {
								for ($j=0;$j<$ct2;$j++) {
									$match2[1][$j] = trim($match2[1][$j]);
									$match2[2][$j] = trim($match2[2][$j]);
//if (!isset($reftype[$match2[1][$j]])) print $indirec;
										// NOTE exclude SUBM, SUB1
//print "replace: ".$match2[1][$j]." ".$match2[2][$j]." with ".$transtab[$reftype[$match2[1][$j]]][$match2[2][$j]];
									if (array_key_exists($reftype[$match2[1][$j]], $transtab) && isset($transtab[$reftype[$match2[1][$j]]][$match2[2][$j]])) {
//print "Found!";
										$subrec = str_replace("@".$match2[2][$j]."@", "@".$transtab[$reftype[$match2[1][$j]]][$match2[2][$j]]."@", $subrec);
									}
									else {
										print "Problem with: ".$indirec."<br />A reference in this record cannot be renumbered, as is points to a non existent level O record.<br />Reference type: ".$match2[1][$j]."<br />Reference: ".$match2[2][$j]."<br />You must repair this later manually.<br /><br />";
									}
								}
//print "<br />";
//print $match2[1][$j]." ".$match2[2][$j];
//pb();
							}
							$indiline .= "\r\n".$subrec;
						}
						$indirec = $indiline;
	//print $indirec."<br />";
					} // End renumbering

					//-- import anything that is not a blob
					if (preg_match("/\n\d BLOB/", $indirec)==0) {
						$gid = ImportFunctions::ImportRecord($indirec, false, $gedcomid);
						$type = GetRecType($indirec);
						$place_count += ImportFunctions::UpdatePlaces($gid, $type, $indirec, false, $gedcomid);
						$date_count += ImportFunctions::UpdateDates($gid, $indirec, $gedcomid);
					}
					else WriteToLog("UploadGedcom -&gt; Import skipped a aecord with a BLOB tag: ".$indirec, "E", "G", get_gedcom_from_id($gedcomid));
						
					//-- calculate some statistics
					if (!isset($show_type)){
						$show_type = $type;
						$i_start = 1;
						$exectime_start = 0;
						$type_BYTES = $TOTAL_BYTES;
					}
					$i++;
					if ($show_type != $type) {
						$newtime = time();
						$exectime = $newtime - $oldtime;
						$show_exectime = $exectime - $exectime_start;
						$show_i = $i - $i_start;
						$type_BYTES = $TOTAL_BYTES - $type_BYTES;
						if (!isset($listtype[$show_type]["type"])) {
							$listtype[$show_type]["exectime"]	= $show_exectime;
							$listtype[$show_type]["bytes"]		= $type_BYTES;
							$listtype[$show_type]["i"]			= $show_i;
							$listtype[$show_type]["type"]		= $show_type;
						}
						else {
							$listtype[$show_type]["exectime"]	+= $show_exectime;
							$listtype[$show_type]["bytes"]		+= $type_BYTES;
							$listtype[$show_type]["i"]			+= $show_i;
						}
						$show_type = $type;
						$i_start = $i;
						$exectime_start = $exectime;
						$type_BYTES=$TOTAL_BYTES;
					}
					//-- update the progress bars at every 10 records
					if ($i%10==0) {
						$newtime = time();
						$exectime = $newtime - $oldtime;
						print "\n<script type=\"text/javascript\"><!--\nupdate_progress(".$TOTAL_BYTES.", ".$exectime.");\n//-->\n</script>\n";
						flush();
						@ob_flush();
					}
					$show_gid=$gid;
				
					//-- check if we are getting close to timing out
					if ($i%10 == 0) {
						$newtime = time();
						$exectime = $newtime - $oldtime;
						if (($timelimit != 0) && ($timelimit - $exectime) < 10) {
							$sql = "UNLOCK TABLES";
							$res = NewQuery($sql);
							// Update all counters before restarting
							$show_exectime = $exectime - $exectime_start;
							$show_i = $i - $i_start + 1;
							$type_BYTES = $TOTAL_BYTES - $type_BYTES;
							if (!isset($listtype[$show_type]["type"])) {
								$listtype[$show_type]["exectime"]	= $show_exectime;
								$listtype[$show_type]["bytes"]		= $type_BYTES;
								$listtype[$show_type]["i"]			= $show_i;
								$listtype[$show_type]["type"]		= $show_type;
							}
							else {
								$listtype[$show_type]["exectime"]	+= $show_exectime;
								$listtype[$show_type]["bytes"] 		+= $type_BYTES;
								$listtype[$show_type]["i"]			+= $show_i;
							}
							$importtime = $importtime + $exectime;
							$fcontents = substr($fcontents, $pos2);
							//-- store the resume information in the session
							$_SESSION["import"]["fcontents"]	= $fcontents;
							$_SESSION["import"]["place_count"]	= $place_count;
							$_SESSION["import"]["date_count"]	= $date_count;
							$_SESSION["import"]["media_count"]	= $media_count; // For UpdateMedia
							$_SESSION["import"]["found_ids"]	= $found_ids; // Saved for UpdateMedia!
							$_SESSION["import"]["TOTAL_BYTES"]	= $TOTAL_BYTES;
							$_SESSION["import"]["listtype"]		= $listtype;
							$_SESSION["import"]["importtime"]	= $importtime;
							
							//-- close the file connection
							fclose($fpged);
							$_SESSION["import"]["resumed"]++;
							print "</div>";
							print "<div class=\"NavBlockFooter\">";
							print GM_LANG_import_time_exceeded."<br /><br />";
							print "<input type=\"hidden\" name=\"stage\" value=\"1\" />";
							print "<input type=\"hidden\" name=\"step\" value=\"5\" />";
							// This is the (auto)continue button
							print "<input type=\"submit\" name=\"continue\" value=\"".GM_LANG_del_proceed."\" />";
							print "</div></div>";
							NameFunctions::DMSoundex("", "closecache");
							//-- We write the session data and close it. Fix for intermittend logoff.
							session_write_close();
							if ($auto_continue == "yes") { ?>
								<script type="text/javascript">
									<!--
									(function (fn) {
										if (window.addEventListener) window.addEventListener('load', fn, false);
										else window.attachEvent('onload', fn);
									})
									(function() {
										document.forms['configform'].elements['continue'].click();
									});
									//-->
								</script>
							<?php 
							}
							SwitchGedcom();
							PrintFooter();
							exit;
						}
					}
				}
				// loop merge until here
				$pos1 = 0;
				$fcontents = substr($fcontents, $pos2);
				if ($pos2 == 0 && feof($fpged)) $end = true;
			}
			fclose($fpged);
			NameFunctions::DMSoundex("", "closecache");
			$newtime = time();
			$exectime = $newtime - $oldtime;
			$importtime = $importtime + $exectime;
			flush();
			@ob_flush();
			if ($marr_names == "yes") {
				$_SESSION["import"]["place_count"] 		= $place_count;
				$_SESSION["import"]["date_count"]		= $date_count;
				$_SESSION["import"]["media_count"]		= $media_count; // For UpdateMedia
				$_SESSION["import"]["TOTAL_BYTES"]		= $TOTAL_BYTES;
				$_SESSION["import"]["listtype"]			= $listtype;
				$_SESSION["import"]["exectime_start"]	= $exectime_start;
				$_SESSION["import"]["importtime"]		= $importtime;
				$step = 6;
			}
		}
		
		$sql = "UNLOCK TABLES";
		$res = NewQuery($sql);
	}
}
if ($step == 6) {
	$k = 0;
	print "<input type=\"hidden\" name=\"step\" value=\"6\" />";
	$flist = ImportFunctions::GetFemalesWithFAMS($gedcomid);
	$famlist = ImportFunctions::GetFamListWithMARR($gedcomid);
//	print GM_LANG_calc_marr_names;
	
	if (isset($exectime)){
		$oldtime = time()-$exectime;
	}
	else $oldtime = time();
	
	if (isset($_SESSION["import"]["marr_resumed"])) {
		$lastgid 			= $_SESSION["import"]["lastgid"];
		$marr_importtime 	= $_SESSION["import"]["marr_importtime"];
		$names_added 		= $_SESSION["import"]["names_added"];
		$listtype 			= $_SESSION["import"]["listtype"]; 		// For printing later
		$TOTAL_BYTES 		= $_SESSION["import"]["TOTAL_BYTES"]; 	// For printing later
//		$k 					= $_SESSION["import"]["k"]; 			// For printing later
				
		print "\n<div class=\"NavBlockHeader UploadGedcomSubHeader\">";
		print GM_LANG_reading_file." ".$path.$gedfilename;
		print "</div>";
		print "<div class=\"NavBlockLabel UploadGedcomNavBlockLabel\">";
		ImportFunctions::SetupProgressBar(count($flist));
		$newtime = time();
		$exectime = $newtime - $oldtime;
		$exectime_start = $exectime;
	}
	else {
		?>
		<script type="text/javascript">
		<!--
		var FILE_SIZE = <?php print count($flist); ?>;
		//-->
		</script>
		<?php
		unset($lastgid);
		$names_added = 0;
	}
	$exectime_start = $_SESSION["import"]["exectime_start"]; //ok
	$importtime = $_SESSION["import"]["importtime"];
	
	NameFunctions::DMSoundex("", "opencache");
	if (!isset($lastgid)) $skip = false;
	else $skip = true;
	foreach($flist as $gid => $indi) {
		if ($skip == false) {
			$lastgid = $gid;
			// Get the family ID's
			$ct = preg_match_all("/1\s*FAMS\s*@(.*)@/", $indi->gedrec, $match, PREG_SET_ORDER);
			if ($ct>0){
				for($j=0; $j<$ct; $j++) {
					// Process if the family is in the array (thus has a marriage)
					if (isset($famlist[$match[$j][1]])) {
						// Get the other spouse's name from the family
						$fam = $famlist[$match[$j][1]];
						if ($fam->husb_id != $gid) $spouse = $fam->husb;
						else $spouse = $fam->wife;
						$spnames = $spouse->name_array;
						$surname = $spnames[0][2];
						$letter = $spnames[0][1];
						
						// Add the spouses name to the individual
						$indi_names = $indi->name_array;
						//-- uncomment the next line to put the maiden name in the given name area
						//$newname = preg_replace("~/(.*)/~", " $1 /".$surname."/", $indi_names[0][0]);
						$newname = preg_replace("~/(.*)/~", "/".$surname."/", $indi_names[0][0]);
						// But only if both are the same for chinese
						if (NameFunctions::HasChinese($surname, true) == NameFunctions::HasChinese($indi_names[0][0], true)) {
							// and only if it doesn't exist yet
							if (strpos($indi->gedrec, "_MARNM $newname")===false) {
								$gedrec = $indi->gedrec;
								$pos1 = strpos($gedrec, "1 NAME");
								if ($pos1!==false) {
									$pos1 = strpos($gedrec, "\n1", $pos1+1);
									if ($pos1!==false) $gedrec = substr($gedrec, 0, $pos1)."\n2 _MARNM ".$newname."\r\n".substr($gedrec, $pos1+1);
									else $gedrec = trim($gedrec)."\r\n2 _MARNM ".$newname."\r\n";
									$gedrec = EditFunctions::CheckGedcom($gedrec, false);
									$fletter = $indi_names[0][5];
									ImportFunctions::AddNewName($indi, $newname, $letter, $fletter, $surname, $gedrec);
									$names_added++;
								}
							}
						}
					}
				}
			}
		}
		if ($lastgid == $gid) $skip = false;
		$k++;
		if ($k%10 == 0) {
			$newtime = time();
			$exectime = $newtime - $oldtime;
			print "\n<script type=\"text/javascript\"><!--\nupdate_progress(".$k.", ".$exectime.");\n//-->\n</script>\n";
			flush();
			@ob_flush();

			//-- check if we are getting close to timing out
			$newtime = time();
			$exectime = $newtime - $oldtime;
			if (($timelimit != 0) && ($timelimit - $exectime) < 10) {
				$marr_importtime = $marr_importtime + $exectime;
				//-- store the resume information in the session
				$_SESSION["import"]["exectime_start"]	= $exectime_start; //ok
				$_SESSION["import"]["marr_importtime"]	= $marr_importtime;
				$_SESSION["import"]["names_added"]		= $names_added;
				$_SESSION["import"]["lastgid"]			= $lastgid; // Last gid processed
				$_SESSION["import"]["marr_resumed"]		= 1;
//				$_SESSION["import"]["k"]				= $k;
				?>
				</div>
				<div class="NavBlockFooter">
				<?php print GM_LANG_import_time_exceeded; ?><br /><br />
				<?php
				// This is the (auto)continue button
				print "<input type=\"submit\" name=\"continue\" value=\"".GM_LANG_del_proceed."\" /></div>";
				NameFunctions::DMSoundex("", "closecache");
				//-- We write the session data and close it. Fix for intermittend logoff.
				session_write_close();
				if ($auto_continue == "yes") { ?>
					<script type="text/javascript">
						<!--
						(function (fn) {
							if (window.addEventListener) window.addEventListener('load', fn, false);
							else window.attachEvent('onload', fn);
						})
						(function() {
							document.forms['configform'].elements['continue'].click();
						});
						//-->
					</script>
					<?php 
				}
				SwitchGedcom();
				PrintFooter();
				exit;
			}
		}
	}
	NameFunctions::DMSoundex("", "closecache");
	$show_table_marr = "<table class=\"NavBlockTable\"><tr>";
	$show_table_marr .= "<tr><td class=\"NavBlockHeader\" colspan=\"3\">".GM_LANG_import_marr_names."</td></tr>";
	$show_table_marr .= "<td class=\"NavBlockColumnHeader\">".GM_LANG_exec_time."</td>";
	$show_table_marr .= "<td class=\"NavBlockColumnHeader\">".GM_LANG_found_record."</td>";
	$show_table_marr .= "<td class=\"NavBlockColumnHeader\">".GM_LANG_type."</td></tr>\n";
	$newtime = time();
	$exectime = $newtime - $oldtime;
	$marr_importtime = $exectime + $marr_importtime;
	$show_table_marr .= "<tr><td class=\"NavBlockField\">".$marr_importtime." ".GM_LANG_sec."</td>\n";
	$show_table_marr .= "<td class=\"NavBlockField\">".$names_added."<script type=\"text/javascript\"><!--\nupdate_progress(".$k.", ".($exectime + $marr_importtime).");//-->\n</script></td>";
	$show_table_marr .= "<td class=\"NavBlockField\">&nbsp;INDI&nbsp;</td></tr>\n";
	$show_table_marr .= "</table><br />\n";
	$stage=10;
	$record_count=0;
	flush();
	@ob_flush();
}
		
if ($step == 5 || $step == 6) {
	print "<script type=\"text/javascript\"><!--\ncomplete_progress(".($importtime + $marr_importtime).", '".GM_LANG_exec_time."', '".GM_LANG_click_here_to_go_to_pedigree_tree."', '".GM_LANG_welcome_page."');\n//-->\n</script>";
	
	// TODO: Layout for Hebrew
	$show_table1 = "<table class=\"NavBlockTable\">";
	$show_table1 .= "<tr><td class=\"NavBlockHeader\" colspan=\"4\">".GM_LANG_import_statistics."</td></tr>";
	$show_table1 .= "<tr><td class=\"NavBlockColumnHeader\">".GM_LANG_exec_time."</td>";
	$show_table1 .= "<td class=\"NavBlockColumnHeader\">".GM_LANG_bytes_read."</td>\n";
	$show_table1 .= "<td class=\"NavBlockColumnHeader\">".GM_LANG_found_record."</td>";
	$show_table1 .= "<td class=\"NavBlockColumnHeader\">".GM_LANG_type."</td></tr>\n";
	$total = 0;
	foreach($listtype as $indexval => $type) {
		$show_table1 .= "<tr><td class=\"NavBlockField\">".$type["exectime"]." ".GM_LANG_sec."</td>";
		$show_table1 .= "<td class=\"NavBlockField\">".($type["bytes"]=="0"?"++":$type["bytes"])."</td>\n";
		$show_table1 .= "<td class=\"NavBlockField\">".$type["i"]."</td>";
		$total += $type["i"];
		$show_table1 .= "<td class=\"NavBlockField\">".$type["type"]."</td></tr>\n";
	}
	$show_table1 .= "<tr><td class=\"NavBlockField\">".$importtime." ".GM_LANG_sec."</td>";
	$show_table1 .= "<td class=\"NavBlockField\">".$TOTAL_BYTES."<script type=\"text/javascript\"><!--\nupdate_progress(".$TOTAL_BYTES.", ".$exectime.");\n//-->\n</script></td>\n";
	$show_table1 .= "<td class=\"NavBlockField\">".$total."</td>";
	$show_table1 .= "<td class=\"NavBlockField\">&nbsp;</td></tr>\n";
	$show_table1 .= "</table>\n";
	print "\n<div class=\"UploadGedcomImportStats\">";
		if (!isset($skip_table)) {
			print $show_table1;
			if ($marr_names == "yes") print $show_table_marr;
		}
	print "</div>";
	
	print "</div>"; // Close the container for progress and stats
	
	// NOTE: Finished Links
	$record_count=0;
	unset($_SESSION["import"]);
	if (!ini_get('safe_mode')) @set_time_limit(GedcomConfig::$TIME_LIMIT);
}
$NBfooter = false;
// Don't show the continue botton if the import process is finished!
if ($step < 5) {
	print "<div class=\"NavBlockFooter\"><input type=\"submit\" name=\"continue\" value=\"".GM_LANG_del_proceed."\" />&nbsp;";
	$NBfooter = true;
}

if ($step == 3 && (isset($cleanup_needed) && $cleanup_needed == "yes") && (!isset($skip_cleanup) || $skip_cleanup != GM_LANG_skip_cleanup)) {
	if (!$NBfooter) print "<div class=\"NavBlockFooter\">";
	PrintHelpLink("skip_cleanup_help", "qm", "skip_cleanup");
	print "<input type=\"submit\" name=\"skip_cleanup\" value=\"".GM_LANG_skip_cleanup."\" />&nbsp;\n";
	$NBfooter = true;
}

// Option to cancel the import
if ($step < 5) {
	if (!$NBfooter) print "<div class=\"NavBlockFooter\">";
	print "<input type=\"hidden\" name=\"cancel_import\" value=\"\" />";
	print "<input type=\"button\" name=\"cancel\" value=\"".GM_LANG_cancel."\" onclick=\"document.configform.cancel_import.value='yes'; document.configform.submit(); \" />";
	$NBfooter = true;
}
if ($NBfooter) print "<!-- Close block footer //--></div>";
print "</form>";
print "<!-- Close middle section //--></div>"; 
SwitchGedcom();
PrintFooter();
?>