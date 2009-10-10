<?php
/**
 * Allow admin users to upload a new gedcom using a web interface.
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
 * This Page Is Valid XHTML 1.0 Transitional! > 12 September 2005
 * 
 * @author Genmod Development Team
 * @package Genmod
 * @subpackage Admin
 * @version $Id$
 */
 // NOTE: No direct access to this script
 //-- Be careful this may not be a good idea... 
 //-- the browser is not necessarily required to send a HTTP_REFERER 
 if (!isset($_SERVER["HTTP_REFERER"])) {
	header("Location: editgedcoms.php");
	exit;
}
 // TODO: Progress bars don't show until </table> or </div>
 // TODO: Upload ZIP support alternative path and name
 
 // NOTE: $GEDFILENAME = The filename of the uploaded GEDCOM
 // NOTE: $action = Which form we should present
 // NOTE: $check = Which check to be performed
 // NOTE: $timelimit = The time limit for the import process
 // NOTE: $cleanup = If set to yes, the GEDCOM contains invalid tags
 // NOTE: $no_upload = When the user cancelled, we want to restore the original settings
 // NOTE: $path = The path to the GEDCOM file
 // NOTE: $contine = When the user decided to move on to the next step
 // NOTE: $import_existing = See if we are just importing an existing GEDCOM
 // NOTE: $replace_gedcom = When uploading a GEDCOM, user will be asked to replace an existing one. If yes, overwrite
 // NOTE: $bakfile = Name and path of the backupfile, this file is created if a file with the same name exists
 
 /**
 * Inclusion of the configuration file
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

if (empty($action)) $action = "upload_form";
if (!isset($path)) $path = "";
if (!isset($check)) $check = "";
if (!isset($error)) $error = "";
if (!isset($verify)) $verify = "";
if (!isset($import)) $import = false;
if (!isset($bakfile)) $bakfile = "";
if (!isset($cleanup_needed)) $cleanup_needed = false;
if (!isset($ok)) $ok = false;
if (!isset($startimport)) $startimport = false;
if (!isset($timelimit)) $timelimit = $TIME_LIMIT;
if (!isset($importtime)) $importtime = 0;
if (!isset($no_upload)) $no_upload = false;
if (!isset($override)) $override = false;
if ($no_upload == "cancel_upload" || $override == "no")  $check = "cancel_upload";
if (!isset($exists)) $exists = false;
if (!isset($config_gedcom)) $config_gedcom = "";
if (!isset($continue)) $continue = false;
if (!isset($import_existing)) $import_existing = false;
if (!isset($skip_cleanup)) $skip_cleanup = false;
if (!isset($merge_media)) $merge_media = $MERGE_DOUBLE_MEDIA;
if (!isset($GEDFILENAME) && isset($FILEID)) $GEDFILENAME = get_gedcom_from_id($FILEID);
if (!isset($gedid)) $gedid = get_id_from_gedcom($GEDFILENAME);

// Override the gedcom default for the import process
$MERGE_DOUBLE_MEDIA = $merge_media;

// NOTE: GEDCOM was uploaded
if ($check == "upload") {
	$verify = "verify_gedcom";
	$ok = true;
}
// NOTE: GEDCOM was added
else if ($check == "add") {
	$verify = "verify_gedcom";
	$ok = true;
}
else if ($check == "add_new") {
	if (((!file_exists(INDEX_DIRECTORY.$GEDFILENAME)) && !file_exists($path.$GEDFILENAME))  || $override == "yes") {
		if ($path != "") $fp = fopen($path.$GEDFILENAME, "wb");
		else	$fp = fopen(INDEX_DIRECTORY.$GEDFILENAME, "wb");
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
			$exists = true;
			// NOTE: Go straight to import, no other settings needed
			$marr_names = "no";
			$xreftype = "NA";
			$utf8convert = "no";
			$ged = $GEDFILENAME;
			$startimport = "true";
		}
	}
	else {
		if ($path != "") $fp = fopen($path.$GEDFILENAME.".bak", "wb");
		else	$fp = fopen(INDEX_DIRECTORY.$GEDFILENAME.".bak", "wb");
		if ($fp) {
			$newgedcom = '0 HEAD
1 SOUR Genmod
2 VERS '.GM_VERSION.' '.GM_VERSION_RELEASE.'
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
0 TRLR';
			fwrite($fp, $newgedcom);
			fclose($fp);
			if ($path != "") $bakfile = $path.$GEDFILENAME.".bak";
			else	$bakfile = INDEX_DIRECTORY.$GEDFILENAME.".bak";
			$ok = false;
			$verify = "verify_gedcom";
			$exists = true;
		}
	}
}
else if ($check == "cancel_upload") {
	if ($exists) {
		unset($GEDCOMS[get_id_from_gedcom($GEDFILENAME)]);
		StoreGedcoms();
		if ($action == "add_new_form") @unlink(INDEX_DIRECTORY.$GEDFILENAME);
	}
	// NOTE: Cleanup everything no longer needed
	if (isset($bakfile) && file_exists($bakfile)) unlink($bakfile);
	if ($verify) $verify = "";
	if ($GEDFILENAME) unset($GEDFILENAME);
	if ($startimport) $startimport="";
	if ($import) $import = false;
	if ($cleanup_needed) $cleanup_needed = false;
	$noupload = true;
	header("Location: editgedcoms.php");
}
if ($cleanup_needed == "cleanup_needed" && $continue == $gm_lang["del_proceed"]) {
	
	$filechanged=false;
	if (FileIsWriteable($GEDCOMS[$gedid]["path"]) && (file_exists($GEDCOMS[$gedid]["path"]))) {
		$l_headcleanup = false;
		$l_macfilecleanup = false;
		$l_lineendingscleanup = false;
		$l_placecleanup = false;
		$l_datecleanup=false;
		$l_isansi = false;
		$fp = fopen($GEDCOMS[$gedid]["path"], "rb");
		$fw = fopen(INDEX_DIRECTORY."/".$GEDFILENAME.".bak", "wb");
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
	
			if (isset($_POST["cleanup_places"]) && $_POST["cleanup_places"]=="YES") {
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
			/**
			if($_POST["xreftype"]!="NA") {
				$filechanged=true;
				ImportFunctions::XrefChange($_POST["xreftype"]);
			}
			**/
			if (isset($_POST["utf8convert"])=="YES") {
				$filechanged=true;
				ImportFunctions::ConvertAnsiUtf8();
			}
			fwrite($fw, $fcontents);
		}
		fclose($fp);
		fclose($fw);
		copy(INDEX_DIRECTORY."/".$GEDFILENAME.".bak", $GEDCOMS[$gedid]["path"]);
		$cleanup_needed = false;
		$import = "true";
	}
	else {
		$error = str_replace("#GEDCOM#", $GEDFILENAME, $gm_lang["error_header_write"]);
	}
}

// NOTE: Change header depending on action
if ($action == "upload_form" || $action == "reupload_form") print_header($gm_lang["upload_gedcom"]);
else if ($action == "add_form") print_header($gm_lang["add_gedcom"]);
else if ($action == "add_new_form") print_header($gm_lang["add_new_gedcom"]);
else print_header($gm_lang["ged_import"]);
print "<div id=\"import_content\">";
// NOTE: Print form header
print "<form enctype=\"multipart/form-data\" method=\"post\" name=\"configform\" action=\"uploadgedcom.php\">";

	// NOTE: Add GEDCOM form
	if ($action == "add_form") {
		print "<div class=\"topbottombar $TEXT_DIRECTION\">";
			print "<a href=\"javascript: ";
			if ($import_existing) print $gm_lang["ged_import"];
			else print $gm_lang["add_gedcom"];
			print "\" onclick=\"expand_layer('add-form');return false\"><img id=\"add-form_img\" src=\"".$GM_IMAGE_DIR."/";
			if ($startimport != "true") print $GM_IMAGES["minus"]["other"];
			else print $GM_IMAGES["plus"]["other"];
			print "\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
			print_help_link("add_gedcom_help", "qm","add_gedcom");
			print "&nbsp;<a href=\"javascript: ";
			if ($import_existing) print $gm_lang["ged_import"];
			else print $gm_lang["add_gedcom"];
			print "\" onclick=\"expand_layer('add-form');return false\">";
			if ($import_existing) print $gm_lang["ged_import"];
			else print $gm_lang["add_gedcom"];
			print "</a>";
		print "</div>";
		print "<div class=\"shade1\" style=\"padding-top:5px;\">";
			print "<div id=\"add-form\" style=\"display: ";
			if ($startimport != "true") print "block ";
			else print "none ";
			print "\">";
				?>
				<input type="hidden" name="check" value="add" />
				<input type="hidden" name="action" value="<?php print $action; ?>" />
				<input type="hidden" name="import_existing" value="<?php print $import_existing; ?>" />
					<?php
					$i = 0;
					if (!empty($error)) {
						print "<div class=\"shade1 wrap\">";
						print "<span class=\"error\">".$error."</span>\n";
						print "</div>";
					}
					?>
					<?php print_help_link("gedcom_path_help", "qm","gedcom_path");?>
					<span style="vertical-align: 25%"><?php print $gm_lang["gedcom_file"]; ?></span>
					<input type="text" name="GEDFILENAME" value="<?php if (isset($GEDFILENAME) && strlen($GEDFILENAME) > 4) print $GEDCOMS[get_id_from_gedcom($GEDFILENAME)]["path"]; ?>" 
					size="60" dir ="ltr" tabindex="<?php $i++; print $i?>"	<?php if ((!$no_upload && isset($GEDFILENAME)) && (empty($error))) print "disabled=\"disabled\" "; ?> />
				<?php
			print "</div>";
		print "</div>";
	}
	// NOTE: Upload GEDCOM form
	if ($action == "upload_form" || $action == "reupload_form") {
		print "<div class=\"topbottombar $TEXT_DIRECTION\">";
			print "<a href=\"javascript: ".$gm_lang["upload_gedcom"]."\" onclick=\"expand_layer('upload_gedcom'); return false;\"><img id=\"upload_gedcom_img\" src=\"".$GM_IMAGE_DIR."/";
			if ($startimport != "true") print $GM_IMAGES["minus"]["other"];
			else print $GM_IMAGES["plus"]["other"];
			print "\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
			print_help_link("upload_gedcom_help", "qm", "upload_gedcom");
			print "&nbsp;<a href=\"javascript: ".$gm_lang["upload_gedcom"]."\" onclick=\"expand_layer('upload_gedcom');return false\">".$gm_lang["upload_gedcom"]."</a>";
		print "</div>";
		print "<div class=\"shade1 wrap\">";
			print "<div id=\"upload_gedcom\" style=\"display: ";
			if ($startimport != "true") print "block ";
			else print "none ";
			print "\">";
			?>
				<div>
					<input type="hidden" name="action" value="<?php print $action; ?>" />
					<input type="hidden" name="check" value="upload" />
					<?php
					if (!empty($error)) {
						print "<span class=\"error\">".$error."</span><br />\n";
						print_text("common_upload_errors");
						print "<br />\n";
					}
					
					?>
					<div class="shade2 width20 wrap">
					<?php print $gm_lang["gedcom_file"];?>
					</div>
					<?php
					if (isset($GEDFILENAME)) print $path.$GEDFILENAME;
					else if (isset($UPFILE)) print $UPFILE["name"];
					else {
						print "<input name=\"UPFILE\" type=\"file\" size=\"60\" />";
						if (!$filesize = ini_get('upload_max_filesize')) $filesize = "2M";
						print " ( ".$gm_lang["max_upload_size"]." $filesize )";
					}
					?>
				</div>
				<?php
			print "</div>";
		print "</div>";
	}
	// NOTE: Add new GEDCOM form
	else if ($action == "add_new_form") {
		print "<div class=\"topbottombar $TEXT_DIRECTION\">";
			print "<a href=\"javascript: ".$gm_lang["add_new_gedcom"]."\" onclick=\"expand_layer('add-form');return false;\"><img id=\"add_new_gedcom_img\" src=\"".$GM_IMAGE_DIR."/";
			if ($startimport != "true") print $GM_IMAGES["minus"]["other"];
			else print $GM_IMAGES["plus"]["other"];
			print "\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
			print_help_link("add_gedcom_instructions", "qm","add_new_gedcom");
			print "&nbsp;<a href=\"javascript: ".$gm_lang["add_new_gedcom"]."\" onclick=\"expand_layer('add-form');return false;\">".$gm_lang["add_new_gedcom"]."</a>";
		print "</div>";
		print "<div class=\"shade1\">";
			print "<div id=\"add-form\" style=\"display: ";
			if ($startimport != "true") print "block ";
			else print "none ";
			print "\">";
				?>
				<div>
					<input type="hidden" name="action" value="<?php print $action; ?>" />
					<input type="hidden" name="check" value="add_new" />
					<?php
					if (!empty($error)) {
							print "<div class=\"shade1 wrap\" colspan=\"2\">";
							print "<span class=\"error\">".$error."</span>\n";
							print "</div>";
					}
					?>
					<div class="shade2 width20 wrap">
						<?php print $gm_lang["gedcom_file"];?>
						<input name="GEDFILENAME" type="text" value="<?php if (isset($GEDFILENAME)) print $path.$GEDFILENAME; ?>" size="60" <?php if (isset($GEDFILENAME) && !$no_upload) print "disabled=\"disabled\""; ?> />
					</div>
				</div>
				<?php
		print "</div>";
	}
	if ($verify=="verify_gedcom") {
		// NOTE: Check if GEDCOM has been imported into DB
		$imported = CheckForImport($GEDFILENAME);
		if ($imported || $bakfile != "") {
			// NOTE: If GEDCOM exists show warning
			print "<div class=\"topbottombar $TEXT_DIRECTION\" style=\"margin-top: 5px;\">";
				print "<a href=\"javascript: ".$gm_lang["verify_gedcom"]."\" onclick=\"expand_layer('verify_gedcom');return false\"><img id=\"verify_gedcom_img\" src=\"".$GM_IMAGE_DIR."/";
				if ($startimport != "true") print $GM_IMAGES["minus"]["other"];
				else print $GM_IMAGES["plus"]["other"];
				print "\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
				print_help_link("verify_gedcom_help", "qm", "verify_gedcom");
				print "&nbsp;<a href=\"javascript: ".$gm_lang["verify_gedcom"]."\" onclick=\"expand_layer('verify_gedcom');return false\">".$gm_lang["verify_gedcom"]."</a>";
			print "</div>";
			print "<div class=\"shade1 wrap\" style=\"padding-top:5px;\">";
				print "<div id=\"verify_gedcom\" style=\"display: ";
				if ($startimport != "true") print "block ";
				else print "none ";
				print "\">";
					?>
					<input type="hidden" name="no_upload" value="" />
					<input type="hidden" name="check" value="" />
					<input type="hidden" name="verify" value="validate_form" />
					<input type="hidden" name="GEDFILENAME" value="<?php if (isset($GEDFILENAME)) print $GEDFILENAME; ?>" />
					<input type="hidden" name="bakfile" value="<?php if (isset($bakfile)) print $bakfile; ?>" />
					<input type="hidden" name="path" value="<?php if (isset($path)) print $path; ?>" />
					
					<?php
					if ($imported) print "<span class=\"error\">".$gm_lang["dataset_exists"]."</span><br /><br />";
					if ($bakfile != "") print $gm_lang["verify_upload_instructions"]."</td></tr>";
					// TODO: Check for existing changes
					
					if ($imported || $bakfile != "") { 
						print "<div class=\"shade2 wrap\">". $gm_lang["empty_dataset"]."&nbsp;";
							print "<select name=\"override\">";
							print "<option value=\"yes\" ";
							if ($override == "yes") print "selected=\"selected\"";
							print ">".$gm_lang["yes"]."</option>";
							print "<option value=\"no\" ";
							if ($override != "yes") print "selected=\"selected\"";
							print ">".$gm_lang["no"]."</option>";
							print "</select>";
						print "</div>";
					}
					print "</div>";
				print "</div>";
			print "</div>";
		}
		else $verify = "validate_form";
	}
	if ($verify == "validate_form") {
		print "<div class=\"topbottombar $TEXT_DIRECTION\">";
			print "<a href=\"javascript: ".$gm_lang["validate_gedcom"]."\" onclick=\"expand_layer('validate_gedcom');return false\"><img id=\"validate_gedcom_img\" src=\"".$GM_IMAGE_DIR."/";
			if ($startimport != "true") print $GM_IMAGES["minus"]["other"];
			else print $GM_IMAGES["plus"]["other"];
			print "\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
			print_help_link("validate_gedcom_help", "qm","validate_gedcom");
			print "&nbsp;<a href=\"javascript: ".$gm_lang["validate_gedcom"]."\" onclick=\"expand_layer('validate_gedcom');return false\">".$gm_lang["validate_gedcom"]."</a>";
		print "</div>";
		print "<div class=\"shade1\" style=\"padding-top:5px;\">";
			print "<div id=\"validate_gedcom\" style=\"display: ";
			if ($startimport != "true") print "block ";
			else print "none ";
			print "\">";
				print "<div class=\"shade1\">";
					print $gm_lang["performing_validation"]."<br />";
					if (!empty($error)) print "<span class=\"error\">".$error."</span>\n";
					
					if ($import != true && $skip_cleanup != $gm_lang["skip_cleanup"]) {
						if ($override == "yes") {
							@copy($bakfile, $GEDCOMS[$gedid]["path"]);
							if (file_exists($bakfile)) unlink($bakfile);
							$bakfile = false;
						}
						$l_headcleanup = false;
						$l_macfilecleanup = false;
						$l_lineendingscleanup = false;
						$l_placecleanup = false;
						$l_datecleanup=false;
						$l_isansi = false;
						$fp = fopen($GEDCOMS[$gedid]["path"], "r");
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
						
						if (!isset($cleanup_needed)) $cleanup_needed = false;
						if (!$l_datecleanup && !$l_isansi  && !$l_headcleanup && !$l_macfilecleanup &&!$l_placecleanup && !$l_lineendingscleanup) {
							print $gm_lang["valid_gedcom"];
							$import = true;
						}
						else {
							$cleanup_needed = true;
							print "<input type=\"hidden\" name=\"cleanup_needed\" value=\"cleanup_needed\">";
							if (!FileIsWriteable($GEDCOMS[$gedid]["path"]) && (file_exists($GEDCOMS[$gedid]["path"]))) {
								print "<span class=\"error\">".str_replace("#GEDCOM#", $GEDCOM, $gm_lang["error_header_write"])."</span>\n";
							}
							// NOTE: Check for head cleanu
							if ($l_headcleanup) {
								print "<div class=\"shade1 wrap\">";
									print_help_link("invalid_header_help", "qm", "invalid_header");
									print "<span class=\"error\">".$gm_lang["invalid_header"]."</span>\n";
								print "</div>";
							}
							// NOTE: Check for mac file cleanup
							if ($l_macfilecleanup) {
								print "<div class=\"shade1 wrap\">";
									print_help_link("macfile_detected_help", "qm", "macfile_detected");
									print "<span class=\"error\">".$gm_lang["macfile_detected"]."</span>\n";
								print "</div>";
							}
							// NOTE: Check for line endings cleanup
							if ($l_lineendingscleanup) {
								print "<div class=\"shade1 wrap\">";
									print_help_link("empty_lines_detected_help", "qm", "empty_lines_detected");
									print "<span class=\"error\">".$gm_lang["empty_lines_detected"]."</span>\n";
								print "</div>";
							}
							// NOTE: Check for place cleanup
							if ($l_placecleanup) {
								print "<div class=\"shade1 wrap\">";
									print "<span class=\"error\">".$gm_lang["place_cleanup_detected"]."</span>\n";
								print "</div>";
								print "<div class=\"shade2 wrap\">";
									print_help_link("cleanup_places_help", "qm", "cleanup_places");
									print $gm_lang["cleanup_places"];
									print "<select name=\"cleanup_places\">\n";
									print "<option value=\"YES\" selected=\"selected\">".$gm_lang["yes"]."</option>\n<option value=\"NO\">".$gm_lang["no"]."</option>\n</select>";
								print "</div>";
								print "<div class=\"shade1\">".$gm_lang["example_place"]."<br />".PrintReady(nl2br($placesample[0]))."</div>\n";
							}
							// NOTE: Check for date cleanup
							if ($l_datecleanup) {
								print "<div class=\"shade1 wrap\">";
									print "<span class=\"error\">".$gm_lang["invalid_dates"]."</span>\n";
								print "</div>";
								print "<div class=\"shade2\">";
									print_help_link("detected_date_help", "qm");
									print $gm_lang["date_format"];
									if (isset($datesample["choose"])){
										print "<select name=\"datetype\">\n";
										print "<option value=\"1\">".$gm_lang["day_before_month"]."</option>\n<option value=\"2\">".$gm_lang["month_before_day"]."</option>\n</select>";
									}
									else print "<input type=\"hidden\" name=\"datetype\" value=\"3\" />";
								print "</div>";
								print "<div class=\"shade1\">".$gm_lang["example_date"]."<br />".$datesample[0]."</div>";
							}
							// NOTE: Check for ansi encoding
							if ($l_isansi) {
								print "<div class=\"shade1\">";
									print "<span class=\"error\">".$gm_lang["ansi_encoding_detected"]."</span>\n";
								print "</div>";
								print "<div class=\"shade2 wrap\">";
									print_help_link("detected_ansi2utf_help", "qm", "ansi_to_utf8");
									print $gm_lang["ansi_to_utf8"];
									print "<select name=\"utf8convert\">\n";
									print "<option value=\"YES\" selected=\"selected\">".$gm_lang["yes"]."</option>\n";
									print "<option value=\"NO\">".$gm_lang["no"]."</option>\n</select>";
								print "</div>";
							}
						}
					}
					else if (!$cleanup_needed) {
						print $gm_lang["valid_gedcom"];
						$import = true;
					}
					else $import = true;
				print "</div>";
				?>
				<input type = "hidden" name="GEDFILENAME" value="<?php if (isset($GEDFILENAME)) print $GEDFILENAME; ?>" />
				<input type = "hidden" name="verify" value="validate_form" />
				<input type = "hidden" name="bakfile" value="<?php if (isset($bakfile)) print $bakfile; ?>" />
				<input type = "hidden" name="path" value="<?php if (isset($path)) print $path; ?>" />
				<input type = "hidden" name="no_upload" value="<?php if (isset($no_upload)) print $no_upload; ?>" />
				<input type = "hidden" name="override" value="<?php if (isset($override)) print $override; ?>" />
				<input type = "hidden" name="ok" value="<?php if (isset($ok)) print $ok; ?>" />
				<?php
			print "</div>";
		print "</div>";
	
	}
	if ($import == true) {
		// NOTE: Additional import options
		print "<div class=\"topbottombar $TEXT_DIRECTION\">";
			print "<a href=\"javascript: ".$gm_lang["import_options"]."\" onclick=\"expand_layer('import_options');return false\"><img id=\"import_options_img\" src=\"".$GM_IMAGE_DIR."/";
			if ($startimport != "true") print $GM_IMAGES["minus"]["other"];
			else print $GM_IMAGES["plus"]["other"];
			print "\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
			print_help_link("import_options_help", "qm", "import_options");
			print "&nbsp;<a href=\"javascript: ".$gm_lang["import_options"]."\" onclick=\"expand_layer('import_options');return false\">".$gm_lang["import_options"]."</a>";
		print "</div>";
		print "<div class=\"shade1 width100\" style=\"padding-top:5px;\">";
			print "<div id=\"import_options\" style=\"display: ";
			if ($startimport != "true") print "block ";
			else print "none ";
			print "\"><table>";
				// NOTE: Time limit for import
				// TODO: Write help text
				print "<tr><td class=\"shade2 wrap width20\">";
					print_help_link("time_limit_help", "qm", "time_limit");
					print $gm_lang["time_limit"];
					print "</td><td class=\"shade2 wrap width20\">";
					print "<input type=\"text\" name=\"timelimit\" value=\"".$timelimit."\" size=\"5\"";
					if ($startimport == "true")  print " disabled=\"disabled\" ";
					print "/>\n";
				print "</td></tr>";
				
				// NOTE: Auto-click "Continue" button
				print "<tr><td class=\"shade2 wrap width20\">";
				print_help_link("auto_ontinue_help", "qm", "auto_continue");
				print $gm_lang["auto_continue"];
				print "</td><td class=\"shade2 width20\">";
				print "<select name=\"auto_continue\"";
				if ($startimport == "true")  print " disabled=\"disabled\" ";
				print ">\n";
				print "<option value=\"YES\" selected=\"selected\">".$gm_lang["yes"]."</option>\n";
				print "<option value=\"NO\">".$gm_lang["no"]."</option>\n</select>";
				print "</td></tr>";
	
				// NOTE: Import married names
				print "<tr><td class=\"shade2 wrap width20\">";
				print_help_link("import_marr_names_help", "qm", "import_marr_names");
				print $gm_lang["import_marr_names"].":";
				print "</td><td class=\"shade2 wrap width20\">";
				if ($startimport == "true") print $gm_lang[$marr_names];
				else {
					print "<select name=\"marr_names\">\n";
					print "<option value=\"yes\">".$gm_lang["yes"]."</option>\n";
					print "<option value=\"no\" selected=\"selected\">".$gm_lang["no"]."</option>\n</select>";
				}
				print "</td></tr>";
				
				// NOTE: change XREF to RIN, REFN, or Don't change
				print "<tr><td class=\"shade2 wrap width20\">";
				print_help_link("change_indi2id_help", "qm", "change_id");
				print $gm_lang["change_id"];
				print "</td><td class=\"shade2 wrap width20\">";
				if ($startimport == "true") {
					if ($xreftype == "NA") print $gm_lang["do_not_change"];
					else print $xreftype;
				}
				else {
					print "<select name=\"xreftype\">\n";
					print "<option value=\"NA\">".$gm_lang["do_not_change"]."</option>\n<option value=\"RIN\">RIN</option>\n";
					print "<option value=\"REFN\">REFN</option>\n</select>";
				}
				print "</td></tr>";
				
				// NOTE: option to convert to utf8
				print "<tr><td class=\"shade2 wrap width20\">";
				print_help_link("convert_ansi2utf_help", "qm", "ansi_to_utf8");
				print $gm_lang["ansi_to_utf8"];
				print "</td><td class=\"shade2 wrap width20\">";
				if ($startimport == "true") print $gm_lang[strtolower($utf8convert)];
				else {
					print "<select name=\"utf8convert\">\n";
					print "<option value=\"YES\">".$gm_lang["yes"]."</option>\n";
					print "<option value=\"NO\" selected=\"selected\">".$gm_lang["no"]."</option>\n</select>";
				}
				print "</td></tr>";

				// NOTE: option to merge double embedded MM items
				print "<tr><td class=\"shade2 wrap width20\">";
				print_help_link("MERGE_DOUBLE_MEDIA_help", "qm", "MERGE_DOUBLE_MEDIA");
				print $gm_lang["MERGE_DOUBLE_MEDIA"];
				print "</td><td class=\"shade2 wrap width20\">";
				if ($startimport == "true") print $gm_lang["merge_dm_".$merge_media];
				else {
					print "<select name=\"merge_media\">\n";
					print "<option value=\"0\" ";
					if ($MERGE_DOUBLE_MEDIA == "0") print "selected=\"selected\"";
					print ">".$gm_lang["merge_dm_0"]."</option>";
					print "<option value=\"1\" ";
					if ($MERGE_DOUBLE_MEDIA == "1" || empty($MERGE_DOUBLE_MEDIA)) print "selected=\"selected\"";
					print ">".$gm_lang["merge_dm_1"]."</option>";
					print "<option value=\"2\" ";
					if ($MERGE_DOUBLE_MEDIA == "2") print "selected=\"selected\"";
					print ">".$gm_lang["merge_dm_2"]."</option>";
				}
				print "</td></tr></table>";
								
				print "<input type=\"hidden\" name=\"startimport\" value=\"true\" />";
				print "<input type=\"hidden\" name=\"ged\" value=\"";
				if (isset($GEDFILENAME)) print $GEDFILENAME;
				print "\" />";
				print "<input type=\"hidden\" name=\"GEDFILENAME\" value=\"";
				if (isset($GEDFILENAME)) print $GEDFILENAME;
				print "\" />";
				print "<input type=\"hidden\" name=\"exists\" value=\"";
				if (isset($exists)) print $exists;
				print "\" />";
				print "<input type=\"hidden\" name=\"ok\" value=\"".$ok."\" />";
				print "<input type=\"hidden\" name=\"import\" value=\"".$import."\" />";
				print "<input type=\"hidden\" name=\"l_isansi\" value=\"";
				if (isset($l_isansi)) print $l_isansi;
				print "\" />";
				print "<input type=\"hidden\" name=\"check\" value=\"\" />";
			print "</div>";
		print "</div>";
	}
	if ($startimport == "true") {
		if (isset($exectime)){
			$oldtime=time()-$exectime;
			$skip_table=0;
		}
		else $oldtime=time();
		
		if (!isset($stage)) $stage = 0;
		if ((empty($gedid))||(!isset($GEDCOMS[$gedid]))) $gedid = $GEDCOMID;
		$temp = $THEME_DIR;
		$GEDCOM_FILE = $GEDCOMS[$gedid]["path"];
		$FILE = $GEDCOMS[$gedid]["gedcom"];
		$FILEID = $gedid;
		$TITLE = $GEDCOMS[$gedid]["title"];
		SwitchGedcom($gedid);
		if ($LANGUAGE <> $_SESSION["CLANGUAGE"]) $LANGUAGE = $_SESSION["CLANGUAGE"];
		
		$temp2 = $THEME_DIR;
		$THEME_DIR = $temp;
		$THEME_DIR = $temp2;
	
		if (isset($GEDCOM_FILE)) {
			if ((!strstr($GEDCOM_FILE, "://"))&&(!file_exists($GEDCOM_FILE))) {
				print "<span class=\"error\" style=\"font-weight: bold\">Could not locate gedcom file at ".$GEDCOM_FILE."</span><br />\n";
				unset($GEDCOM_FILE);
			}
		}
		
		if ($stage==0) {
			$_SESSION["resumed"] = 0;
			ImportFunctions::EmptyDatabase($FILEID);
			$stage=1;
		}
		
		flush();
		@ob_flush();
	
		if ($stage==1) {
			//$timelimit = 20;
			// NOTE: Running in safe mode set_time_limit does not work
			if (!ini_get('safe_mode')) @set_time_limit($timelimit);
			
			$FILE_SIZE = filesize($GEDCOM_FILE);
			print "<div class=\"topbottombar $TEXT_DIRECTION\">";
			print $gm_lang["reading_file"]." ".$GEDCOM_FILE;
			print "</div>";
			ImportFunctions::SetupProgressBar($FILE_SIZE);
			flush();
			@ob_flush();
			// ------------------------------------------------------ Begin importing data
			// -- array of names
//			if (!isset($indilist)) $indilist = array();
//			if (!isset($famlist)) $famlist = array();
//			$sourcelist = array();
//			$otherlist = array();
			$i=0;
		
			$fpged = fopen($GEDCOM_FILE, "rb");
			$BLOCK_SIZE = 1024*4;	//-- 4k bytes per read
			$fcontents = "";
			$TOTAL_BYTES = 0;
			$place_count = 0;
			$date_count = 0;
			$media_count = 0;
			$listtype= array();
			$streamdata = array();
			// NOTE: Resume a halted import from the session
			if (!empty($_SESSION["resumed"])) {
					$place_count = $_SESSION["place_count"];
					$date_count = $_SESSION["date_count"];
					$found_ids = $_SESSION["found_ids"];
					$TOTAL_BYTES = $_SESSION["TOTAL_BYTES"];
					$fcontents = $_SESSION["fcontents"];
					$listtype = $_SESSION["listtype"];
					$exectime_start = $_SESSION["exectime_start"];
					$auto_continue = $_SESSION["auto_continue"];
					$i = $_SESSION["i"];
					if (isset($_SESSION["lastgid"])) $lastgid = $_SESSION["lastgid"];
					if (isset($_SESSION["names_added"])) $names_added = $_SESSION["names_added"];
					fseek($fpged, $TOTAL_BYTES);
			}
			else $_SESSION["resumed"] = 0;
			if (!isset($lastgid)) {
				DMSoundex("", "opencache");
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
					
					//-- import anything that is not a blob
					if (preg_match("/\n\d BLOB/", $indirec)==0) {
						$gid = ImportFunctions::ImportRecord($indirec);
						$type = GetRecType($indirec);
						$place_count += ImportFunctions::UpdatePlaces($gid, $type, $indirec);
						$date_count += ImportFunctions::UpdateDates($gid, $indirec);
					}
					else WriteToLog("UploadGedcom -> Import skipped a aecord with a BLOB tag: ".$indirec, "E", "G", get_gedcom_from_id($FILE));
						
					//-- calculate some statistics
					if (!isset($show_type)){
						$show_type=$type;
						$i_start=1;
						$exectime_start=0;
						$type_BYTES=0;
					}
					$i++;
					if ($show_type!=$type) {
						$newtime = time();
						$exectime = $newtime - $oldtime;
						$show_exectime = $exectime - $exectime_start;
						$show_i=$i-$i_start;
						$type_BYTES=$TOTAL_BYTES-$type_BYTES;
						if (!isset($listtype[$show_type]["type"])) {
							$listtype[$show_type]["exectime"]=$show_exectime;
							$listtype[$show_type]["bytes"]=$type_BYTES;
							$listtype[$show_type]["i"]=$show_i;
							$listtype[$show_type]["type"]=$show_type;
						}
						else {
							$listtype[$show_type]["exectime"]+=$show_exectime;
							$listtype[$show_type]["bytes"]+=$type_BYTES;
							$listtype[$show_type]["i"]+=$show_i;
						}
						$show_type=$type;
						$i_start=$i;
						$exectime_start=$exectime;
						$type_BYTES=$TOTAL_BYTES;
					}
					//-- update the progress bars at every 10 records
					if ($i%10==0) {
						$newtime = time();
						$exectime = $newtime - $oldtime;
						print "\n<script type=\"text/javascript\"><!--\nupdate_progress($TOTAL_BYTES, $exectime);\n//-->\n</script>\n";
						flush();
						@ob_flush();
					}
					$show_gid=$gid;
				
					//-- check if we are getting close to timing out
					if ($i%10==0) {
						$newtime = time();
						$exectime = $newtime - $oldtime;
						if (($timelimit != 0) && ($timelimit - $exectime) < 10) {
							$importtime = $importtime + $exectime;
							$fcontents = substr($fcontents, $pos2);
							//-- store the resume information in the session
							$_SESSION["place_count"] = $place_count;
							$_SESSION["date_count"] = $date_count;
							$_SESSION["media_count"] = $media_count;
							$_SESSION["found_ids"] = $found_ids;
							$_SESSION["TOTAL_BYTES"] = $TOTAL_BYTES;
							$_SESSION["fcontents"] = $fcontents;
							$_SESSION["listtype"] = $listtype;
							$_SESSION["exectime_start"] = $exectime_start;
							$_SESSION["importtime"] = $importtime;
							$_SESSION["i"] = $i;
							$_SESSION["auto_continue"] = $auto_continue;
							
							//-- close the file connection
							fclose($fpged);
							$_SESSION["resumed"]++;
							print "\n<table class=\"facts_table\">";
								
							?>
							<?php print $gm_lang["import_time_exceeded"]; ?>
							<input type="hidden" name="gedid" value="<?php print $gedid; ?>" />
							<input type="hidden" name="stage" value="1" />
							<input type="hidden" name="timelimit" value="<?php print $timelimit; ?>" />
							<input type="hidden" name="importtime" value="<?php print $importtime; ?>" />
							<input type="hidden" name="marr_names" value="<?php print $marr_names; ?>" />
							<input type="hidden" name="xreftype" value="<?php print $xreftype; ?>" />
							<input type="hidden" name="utf8convert" value="<?php print $utf8convert; ?>" />
							<input type="hidden" name="verify" value="<?php print $verify; ?>" />
							<input type="hidden" name="startimport" value="<?php print $startimport; ?>" />
							<input type="hidden" name="import" value="<?php print $import; ?>" />
							<input type="hidden" name="FILE" value="<?php print $FILE; ?>" />
							<input type="hidden" name="merge_media" value="<?php print $merge_media; ?>" />
							<input type="submit" name="continue" value="<?php print $gm_lang["del_proceed"]; ?>" />
							<?php
							DMSoundex("", "closecache");
							//-- We write the session data and close it. Fix for intermittend logoff.
							session_write_close();
							if ($auto_continue=="YES") { ?>
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
							print_footer();
							exit;
						}
					}
					$pos1 = 0;
					$fcontents = substr($fcontents, $pos2);
					if ($pos2 == 0 && feof($fpged)) $end = true;
				}
				fclose($fpged);
				DMSoundex("", "closecache");
				$newtime = time();
				$exectime = $newtime - $oldtime;
				$importtime = $importtime + $exectime;
				$exec_text = $gm_lang["exec_time"];
				$go_pedi = $gm_lang["click_here_to_go_to_pedigree_tree"];
				$go_welc = $gm_lang["welcome_page"];
				print "<script type=\"text/javascript\"><!--\ncomplete_progress(".$importtime.", '".$exec_text."', '".$go_pedi."', '".$go_welc."');\n//-->\n</script>";
				flush();
				@ob_flush();
			}
			
			if ($marr_names == "yes") {
				$GEDCOM = $FILE;
				$flist = GetFemalesWithFAMS();
				$famlist = GetFamListWithMARR();
				print $gm_lang["calc_marr_names"];
				ImportFunctions::SetupProgressBar(count($flist));
			
				$i=0;
				$newtime = time();
				$exectime = $newtime - $oldtime;
				$exectime_start = $exectime;
				if (!isset($names_added)) $names_added = 0;
				include_once("includes/functions/functions_edit.php"); // for checkgedcom
				$manual_save = true;
				DMSoundex("", "opencache");
				if (!isset($lastgid)) $skip = false;
				else $skip = true;
				foreach($flist as $gid=>$indi) {
					if ($skip == false) {
						$lastgid = $gid;
						$ct = preg_match_all("/1\s*FAMS\s*@(.*)@/", $indi["gedcom"], $match, PREG_SET_ORDER);
						if ($ct>0){
							for($j=0; $j<$ct; $j++) {
								if (isset($famlist[$match[$j][1]])) {
									$marrrec = GetSubRecord(1, "1 MARR", $famlist[$match[$j][1]]["gedcom"]);
									if ($marrrec) {
										$parents = FindParentsInRecord($famlist[$match[$j][1]]["gedcom"]);
										if ($parents["HUSB"]!=$gid) $spid = $parents["HUSB"];
										else $spid = $parents["WIFE"];
										$sprec = FindPersonRecord($spid, "", false, true);
										if ($sprec) {
											$spnames = GetIndiNames($sprec, true);
											$surname = $spnames[0][2];
											$letter = $spnames[0][1];
											$indi["names"] = GetIndiNames($indi["gedcom"], true);
											//-- uncomment the next line to put the maiden name in the given name area
											//$newname = preg_replace("~/(.*)/~", " $1 /".$surname."/", $indi["names"][0][0]);
											$newname = preg_replace("~/(.*)/~", "/".$surname."/", $indi["names"][0][0]);
											if (strpos($indi["gedcom"], "_MARNM $newname")===false) {
												$pos1 = strpos($indi["gedcom"], "1 NAME");
												if ($pos1!==false) {
													$pos1 = strpos($indi["gedcom"], "\n1", $pos1+1);
													if ($pos1!==false) $indi["gedcom"] = substr($indi["gedcom"], 0, $pos1)."\n2 _MARNM $newname\r\n".substr($indi["gedcom"], $pos1+1);
													else $indi["gedcom"]= trim($indi["gedcom"])."\r\n2 _MARNM $newname\r\n";
													$indi["gedcom"] = CheckGedcom($indi["gedcom"], false);
													AddNewName($gid, $newname, $letter, $surname, $indi["gedcom"]);
													$names_added++;
												}
											}
										}
									}
								}
							}
						}
					}
					if ($lastgid == $gid) $skip == false;
					$i++;
					if ($i%10==0) {
						$newtime = time();
						$exectime = $newtime - $oldtime;
						print "\n<script type=\"text/javascript\"><!--\nupdate_progress($i, $exectime);\n//-->\n</script>\n";
						flush();
						@ob_flush();
	
						//-- check if we are getting close to timing out
						$newtime = time();
						$exectime = $newtime - $oldtime;
						if (($timelimit != 0) && ($timelimit - $exectime) < 10) {
							$importtime = $importtime + $exectime;
							$fcontents = substr($fcontents, $pos2);
							//-- store the resume information in the session
							$_SESSION["place_count"] = $place_count;
							$_SESSION["date_count"] = $date_count;
							$_SESSION["media_count"] = $media_count;
							$_SESSION["TOTAL_BYTES"] = $TOTAL_BYTES;
							$_SESSION["fcontents"] = $fcontents;
							$_SESSION["listtype"] = $listtype;
							$_SESSION["exectime_start"] = $exectime_start;
							$_SESSION["importtime"] = $importtime;
							$_SESSION["names_added"] = $names_added;
							$_SESSION["i"] = $i;
							$_SESSION["lastgid"] = $lastgid;
							
							//-- close the file connection
							$_SESSION["resumed"]++;
							?>
							<div class="shade2"><?php print $gm_lang["import_time_exceeded"]; ?></div>
							<div class="topbottombar">
								<input type="hidden" name="gedid" value="<?php print $gedid; ?>" />
								<input type="hidden" name="stage" value="1" />
								<input type="hidden" name="timelimit" value="<?php print $timelimit; ?>" />
								<input type="hidden" name="importtime" value="<?php print $importtime; ?>" />
								<input type="hidden" name="marr_names" value="<?php print $marr_names; ?>" />
								<input type="hidden" name="xreftype" value="<?php print $xreftype; ?>" />
								<input type="hidden" name="utf8convert" value="<?php print $utf8convert; ?>" />
								<input type="hidden" name="verify" value="<?php print $verify; ?>" />
								<input type="hidden" name="startimport" value="<?php print $startimport; ?>" />
								<input type="hidden" name="import" value="<?php print $import; ?>" />
								<input type="hidden" name="FILE" value="<?php print $FILE; ?>" />
								<input type="hidden" name="merge_media" value="<?php print $merge_media; ?>" />
								<input type="submit" name="continue" value="<?php print $gm_lang["del_proceed"]; ?>" />
							</div>
							<?php
							print_footer();
							exit;
						}
					}
				}
				DMSoundex("", "closecache");
				$show_table_marr = "<table class=\"list_table\"><tr>";
				$show_table_marr .= "<tr><td class=\"topbottombar\" colspan=\"3\">".$gm_lang["import_marr_names"]."</td></tr>";
				$show_table_marr .= "<td class=\"shade2\">&nbsp;".$gm_lang["exec_time"]."&nbsp;</td>";
				$show_table_marr .= "<td class=\"shade2\">&nbsp;".$gm_lang["found_record"]."&nbsp;</td>";
				$show_table_marr .= "<td class=\"shade2\">&nbsp;".$gm_lang["type"]."&nbsp;</td></tr>\n";
				$newtime = time();
				$exectime = $newtime - $oldtime;
				$show_exectime = $exectime - $exectime_start;
				$show_table_marr .= "<tr><td class=\"shade1 indent_rtl rtl\">$show_exectime ".$gm_lang["sec"]."</td>\n";
				$show_table_marr .= "<td class=\"shade1 indent_rtl rtl\">$names_added<script type=\"text/javascript\"><!--\nupdate_progress($i, $exectime);//-->\n</script></td>";
				$show_table_marr .= "<td class=\"shade1\">&nbsp;INDI&nbsp;</td></tr>\n";
				$show_table_marr .= "</table>\n";
				$stage=10;
				$record_count=0;
				flush();
				@ob_flush();
			}
			// TODO: Layout for Hebrew
			$show_table1 = "<table class=\"list_table center\">";
			$show_table1 .= "<tr><td class=\"topbottombar\" colspan=\"4\">".$gm_lang["ged_import"]."</td></tr>";
			$show_table1 .= "<tr><td class=\"shade2\">&nbsp;".$gm_lang["exec_time"]."&nbsp;</td>";
			$show_table1 .= "<td class=\"shade2\">&nbsp;".$gm_lang["bytes_read"]."&nbsp;</td>\n";
			$show_table1 .= "<td class=\"shade2\">&nbsp;".$gm_lang["found_record"]."&nbsp;</td>";
			$show_table1 .= "<td class=\"shade2\">&nbsp;".$gm_lang["type"]."&nbsp;</td></tr>\n";
			foreach($listtype as $indexval => $type) {
				$show_table1 .= "<tr><td class=\"shade1 indent_rtl rtl \">".$type["exectime"]." ".$gm_lang["sec"]."</td>";
				$show_table1 .= "<td class=\"shade1 indent_rtl rtl \">".($type["bytes"]=="0"?"++":$type["bytes"])."</td>\n";
				$show_table1 .= "<td class=\"shade1 indent_rtl rtl \">".$type["i"]."</td>";
				$show_table1 .= "<td class=\"shade1 rtl\">&nbsp;".$type["type"]."&nbsp;</td></tr>\n";
			}
			$show_table1 .= "<tr><td class=\"shade1 indent_rtl rtl \">$importtime ".$gm_lang["sec"]."</td>";
			$show_table1 .= "<td class=\"shade1 indent_rtl rtl \">$TOTAL_BYTES<script type=\"text/javascript\"><!--\nupdate_progress($TOTAL_BYTES, $exectime);\n//-->\n</script></td>\n";
			$show_table1 .= "<td class=\"shade1 indent_rtl rtl \">".($i-1)."</td>";
			$show_table1 .= "<td class=\"shade1\">&nbsp;</td></tr>\n";
			$show_table1 .= "</table>\n";
			print "<div class=\"import_statistics center\">";
				print "<br /><br />".$gm_lang["import_statistics"]."<br />";
				if (isset($skip_table)) print "<br />...";
				else {
					print $show_table1;
					if ($marr_names == "yes") print $show_table_marr;
				}
			print "</div>";
			
			// NOTE: Finished Links
			$record_count=0;
			$_SESSION["resumed"] = 0;
			unset($_SESSION["place_count"]);
			unset($_SESSION["date_count"]);
			unset($_SESSION["TOTAL_BYTES"]);
			unset($_SESSION["fcontents"]);
			unset($_SESSION["listtype"]);
			unset($_SESSION["exectime_start"]);
			unset($_SESSION["i"]);
			if (!ini_get('safe_mode')) @set_time_limit($TIME_LIMIT);
		}
	}
	print "<div class=\"center\" style=\"margin-top: 5px;\">";
	if ($startimport != "true") print "<input type=\"submit\" name=\"continue\" value=\"".$gm_lang["del_proceed"]."\" />&nbsp;";
		if ($cleanup_needed && $skip_cleanup != $gm_lang["skip_cleanup"]) {
			print_help_link("skip_cleanup_help", "qm", "skip_cleanup");
			print "<input type=\"submit\" name=\"skip_cleanup\" value=\"".$gm_lang["skip_cleanup"]."\" />&nbsp;\n";
		}
		if ($verify && $startimport != "true") print "<input type=\"button\" name=\"cancel\" value=\"".$gm_lang["cancel"]."\" onclick=\"document.configform.override.value='no'; document.configform.no_upload.value='cancel_upload'; document.configform.submit(); \" />";
	print "</div>";
	?>
</form>
<?php
print_footer();
?>