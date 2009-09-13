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
// if (!isset($_SERVER["HTTP_REFERER"])) {
//	header("Location: mergegedcoms.php");
//	exit;
//}
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
//print_r($_POST);
if (!$gm_user->userGedcomAdmin()) {
	if (LOGIN_URL == "") header("Location: login.php?url=mergegedcom.php");
	else header("Location: ".LOGIN_URL."?url=mergegedcom.php");
	exit;
}

// Fix by Thomas for compression problems on Apache
if(function_exists('apache_setenv')) { 
	// apparently @ isn't enough to make php ignore this failing
	@apache_setenv('no-gzip', '1');
}

if (empty($action)) $action = "merge_form";
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

// In merge gedcom we don't show the choice for merging double media. We take the default from the gedcom settings

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
else if ($check == "cancel_upload") {
	if ($exists) {
//		if ($action == "merge_form") @unlink($GEDFILENAME); cannot do this, no idea how the file got there
	}
	// NOTE: Cleanup everything no longer needed
	if (isset($bakfile) && file_exists($bakfile)) unlink($bakfile);
	if ($verify) $verify = "";
	if ($GEDFILENAME) unset($GEDFILENAME);
	if ($startimport) $startimport="";
	if ($import) $import = false;
	if ($cleanup_needed) $cleanup_needed = false;
	$noupload = true;
	header("Location: mergegedcoms.php");
}
if ($cleanup_needed == "cleanup_needed" && $continue == $gm_lang["del_proceed"]) {
	
	$filechanged=false;
	$inpfile = $GEDFILENAME;
	if (FileIsWriteable($inpfile) && (file_exists($inpfile))) {
		$l_headcleanup = false;
		$l_macfilecleanup = false;
		$l_lineendingscleanup = false;
		$l_placecleanup = false;
		$l_datecleanup=false;
		$l_isansi = false;
		$fp = fopen($inpfile, "rb");
		$fw = fopen($inpfile.".bak", "wb");
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
			
			// Remove heading spaces and other garbage from the gedlines
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
		copy($inpfile.".bak", $inpfile);
		$cleanup_needed = false;
		$import = "true";
	}
	else {
		$error = str_replace("#GEDCOM#", $GEDFILENAME, $gm_lang["error_header_write"]);
	}
}
if (isset($UPFILE) && !empty($UPFILE["name"])) {
	// NOTE: Check if we are uploading a file and retrieve the filename
	if (isset($_FILES['UPFILE'])) {
		if (filesize($_FILES['UPFILE']['tmp_name'])!= 0) $UPFILE = $_FILES['UPFILE']['name'];
	}
//print_r($_FILES);
	// NOTE: Extract the GEDCOM filename
	if (!empty($path)) $GEDFILENAME = basename($path);
	else $GEDFILENAME = basename($UPFILE);

	// NOTE: Check if it is a zipfile
	if ($path == "") if (strstr(strtolower(trim($GEDFILENAME)), ".zip")==".zip") {
		$GEDFILENAME = GetGedFromZip($UPFILE);
	}
	// NOTE: Check if there is an extension
	if (strtolower(substr(trim($GEDFILENAME), -4)) != ".ged" && strtolower(substr(trim($GEDFILENAME), -4)) != ".zip") $GEDFILENAME .= ".ged";
	// NOTE: Check if the input contains a valid path otherwise check if there is one in the GEDCOMPATH
	if (!is_dir($path)) {
		if (!empty($path)) $parts = preg_split("/[\/\\\]/", $path);
		else $parts = preg_split("/[\/\\\]/", $UPFILE);
		$path = "";
		$ctparts = count($parts)-1;
		if (count($parts) == 1) $path = INDEX_DIRECTORY;
		else {
			foreach ($parts as $key => $pathpart) {
				if ($key < $ctparts) $path .= $pathpart."/";
			}
		}
	}
	$ctupload = count($_FILES);
	if ($ctupload > 0) {
		// NOTE: When uploading a file check if it doesn't exist yet
		if (!isset($GEDCOMS[get_id_from_gedcom($GEDFILENAME)]) || !file_exists($path.$GEDFILENAME)) {
			if (move_uploaded_file($_FILES['UPFILE']['tmp_name'], $path.$GEDFILENAME)) {
				WriteToLog("MergeGedcom-> Gedcom ".$path.$GEDFILENAME." uploaded", "I", "S");
			}
		}
		// NOTE: If the file exists we will make a backup file
		else if (file_exists($path.$GEDFILENAME)) {
			if (file_exists($path.$GEDFILENAME.".old")) unlink($path.$GEDFILENAME.".old");
				copy($path.$GEDFILENAME, $path.$GEDFILENAME.".old");
				unlink($path.$GEDFILENAME);
			move_uploaded_file($_FILES['UPFILE']['tmp_name'], $path.$GEDFILENAME);
		}
		if (strstr(strtolower(trim($GEDFILENAME)), ".zip")==".zip") $GEDFILENAME = GetGedFromZip($path.$GEDFILENAME);
	}
//	$ged = $GEDFILENAME;
}

// NOTE: Change header depending on action
print_header($gm_lang["merge_gedcom"]);
print "<div id=\"import_content\">";
// NOTE: Print form header
print "<form enctype=\"multipart/form-data\" method=\"post\" name=\"configform\" action=\"mergegedcom.php\">";

	// NOTE: Add GEDCOM form
	if ($action == "merge_form") {
		print "<div class=\"topbottombar $TEXT_DIRECTION\">";
			print "<a href=\"javascript: ";
			print $gm_lang["merge_what"];
			print "\" onclick=\"expand_layer('add-form');return false\"><img id=\"add-form_img\" src=\"".$GM_IMAGE_DIR."/";
			if ($startimport != "true") print $GM_IMAGES["minus"]["other"];
			else print $GM_IMAGES["plus"]["other"];
			print "\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
			print_help_link("merge_gedcom_help", "qm","merge_gedcom");
			print "&nbsp;<a href=\"javascript: ";
			$gm_lang["merge_what"];
			print "\" onclick=\"expand_layer('add-form');return false\">";
			print $gm_lang["merge_what"];
			print "</a>";
		print "</div>";
		print "<div class=\"shade1\" style=\"padding-top:5px;\">";
			print "<div id=\"add-form\" style=\"display: ";
			if ($startimport != "true") print "block ";
			else print "none ";
			print "\">";
				?>
				<input type="hidden" name="check" value="add" />
				<?php if ($action == "merge_form") print "<input type=\"hidden\" name=\"merge\" value=\"true\" />"; ?>
				<input type="hidden" name="action" value="<?php print $action; ?>" />
				<input type="hidden" name="import_existing" value="<?php print $import_existing; ?>" />
					<?php
					$i = 0;
					if (!empty($GEDFILENAME)) {
						if (!file_exists($GEDFILENAME) && file_exists(INDEX_DIRECTORY.$GEDFILENAME)) $GEDFILENAME = INDEX_DIRECTORY.$GEDFILENAME;
						if (!file_exists($GEDFILENAME)) $error = $gm_lang["file_not_found"];
					}
					if (!empty($error)) {
						print "<div class=\"shade1 wrap\">";
						print "<span class=\"error\">".$error."</span>\n";
						print "</div>";
					}
					?>
					<?php print_help_link("gedcom_path_help", "qm","gedcom_path");?>
					<span style="vertical-align: 25%"><?php print $gm_lang["gedcom_file"]; ?></span>
					<input type="text" name="GEDFILENAME" value="<?php if (isset($GEDFILENAME) && strlen($GEDFILENAME) > 4) print $GEDFILENAME; ?>" 
					size="60" dir ="ltr" tabindex="<?php $i++; print $i?>"	<?php if ((!$no_upload && isset($GEDFILENAME)) && (empty($error))) print "disabled=\"disabled\" "; ?> />
				<?php
			print "</div>";
		print "</div>";
	// NOTE: Upload GEDCOM form
		if (!isset($GEDFILENAME)) {
			print "<div class=\"topbottombar $TEXT_DIRECTION\">";
			print "<a href=\"javascript: ".$gm_lang["merge_what_upload"]."\" onclick=\"expand_layer('upload_gedcom'); return false;\"><img id=\"upload_gedcom_img\" src=\"".$GM_IMAGE_DIR."/";
			if ($startimport != "true") print $GM_IMAGES["minus"]["other"];
			else print $GM_IMAGES["plus"]["other"];
			print "\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
			print_help_link("upload_gedcom_help", "qm", "upload_gedcom");
			print "&nbsp;<a href=\"javascript: ".$gm_lang["merge_what_upload"]."\" onclick=\"expand_layer('upload_gedcom');return false\">".$gm_lang["merge_what_upload"]."</a>";
		print "</div>";
		print "<div class=\"shade1\" style=\"padding-top:5px;\">";
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
					<div class="shade1 wrap"><span style="vertical-align: 25%">
					<?php print $gm_lang["gedcom_file"];?></span>
					<?php
					if (isset($GEDFILENAME)) print $path.$GEDFILENAME;
					else if (isset($UPFILE)) print $UPFILE["name"];
					else {
						print "<input name=\"UPFILE\" type=\"file\" size=\"60\" /><span style=\"vertical-align: 25%\">";
						if (!$filesize = ini_get('upload_max_filesize')) $filesize = "2M";
						print " ( ".$gm_lang["max_upload_size"]." $filesize )</span>";
					}
					?>
					</div>
				</div>
				<?php
			print "</div>";
		print "</div>";
		}
			print "<div class=\"topbottombar $TEXT_DIRECTION\">";
			print "<a href=\"javascript: ".$gm_lang["merge_with"]."\" onclick=\"expand_layer('merge_gedcom'); return false;\"><img id=\"merge_gedcom_img\" src=\"".$GM_IMAGE_DIR."/";
			if ($startimport != "true") print $GM_IMAGES["minus"]["other"];
			else print $GM_IMAGES["plus"]["other"];
			print "\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
			print_help_link("upload_gedcom_help", "qm", "upload_gedcom");
			print "&nbsp;<a href=\"javascript: ".$gm_lang["merge_with"]."\" onclick=\"expand_layer('merge_gedcom');return false\">".$gm_lang["merge_with"]."</a>";
		print "</div>";
		print "<div class=\"shade1\" style=\"padding-top:5px;\">";
			print "<div id=\"merge_gedcom\" style=\"display: ";
			if ($startimport != "true") print "block ";
			else print "none ";
			print "\">";
			?>
				<div>
					<input type="hidden" name="action" value="<?php print $action; ?>" />
					<input type="hidden" name="check" value="upload" />
					<div class="shade1 wrap"><span style="vertical-align: 25%">
					<?php print $gm_lang["merge_with"];?>
					<?php
					print "</span><select name=\"merge_ged\"";
					if ((!$no_upload && isset($GEDFILENAME)) && (empty($error))) print "disabled=\"disabled\" ";
					print ">";
					foreach($GEDCOMS as $gedc=>$gedarray) {
						print "<option value=\"".$gedc."\"";
						if (isset($merge_ged)) {
							if ($merge_ged==$gedc) print " selected=\"selected\"";
						}
						else if ($GEDCOMID == $gedc) print " selected=\"selected\"";
						print ">".PrintReady($gedarray["title"])."</option>";
					}
					print "</select>";
					?>
					</div>
				</div>
				<?php
			print "</div>";
		print "</div>";
	}
	if ($verify=="verify_gedcom" && empty($error)) {
		// NOTE: Check if GEDCOM has been imported into DB
//		$imported = CheckForImport($GEDFILENAME);
		if ($bakfile != "") {
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
					
					if ($bakfile != "") { 
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
							copy($bakfile, $GEDFILENAME);
							if (file_exists($bakfile)) unlink($bakfile);
							$bakfile = false;
						}
						$l_headcleanup = false;
						$l_macfilecleanup = false;
						$l_lineendingscleanup = false;
						$l_placecleanup = false;
						$l_datecleanup=false;
						$l_isansi = false;
						$fp = fopen($GEDFILENAME, "r");
						//-- read the gedcom and test it in 8KB chunks
						SwitchGedcom($merge_ged);
						$lastindi = GetNewXref("INDI");
						$lastindi = substr($lastindi,(strlen($GEDCOM_ID_PREFIX)));
						$lastfam = GetNewXref("FAM");
						$lastfam = substr($lastfam,(strlen($FAM_ID_PREFIX)));
						$lastobje = GetNewXref("OBJE");
						$lastobje = substr($lastobje,(strlen($MEDIA_ID_PREFIX)));
						$lastsour = GetNewXref("SOUR");
						$lastsour = substr($lastsour,(strlen($SOURCE_ID_PREFIX)));
						$lastrepo = GetNewXref("REPO");
						$lastrepo = substr($lastrepo,(strlen($REPO_ID_PREFIX)));
						$lastnote = GetNewXref("NOTE");
						$lastnote = substr($lastnote,(strlen($NOTE_ID_PREFIX)));
						$inditab = array();
						$famtab = array();
						$objetab = array();
						$sourtab = array();
						$repotab = array();
						$notetab = array();
						$fcontents = "";
						$end = false;
						$pos1 = 0;
						while(!$end) {
							$pos2 = 0;
							// NOTE: find the start of the next record
							if (strlen($fcontents) > $pos1+1) $pos2 = strpos($fcontents, "\n0", $pos1+1);
							while((!$pos2)&&(!feof($fp))) {
								$fcontents .= fread($fp, 1024*8);
								$pos2 = strpos($fcontents, "\n0", $pos1+1);
							}
					
							//-- pull the next record out of the file
							if ($pos2) $indirec = substr($fcontents, $pos1, $pos2-$pos1);
							else $indirec = substr($fcontents, $pos1);

							// Do some preliminary cleanup
							//-- remove double @ signs
							$indirec = preg_replace("/@+/", "@", $indirec);

							// remove heading spaces and other stuff before the gedrec
							$indirec = preg_replace("/\n\W+/", "\n", $indirec);
							
							if (!$l_headcleanup && ImportFunctions::NeedHeadCleanup($indirec)) $l_headcleanup = true;
							if (!$l_macfilecleanup && ImportFunctions::NeedMacfileCleanup($indirec)) $l_macfilecleanup = true;
							if (!$l_lineendingscleanup && ImportFunctions::NeedLineEndingsCleanup($indirec)) $l_lineendingscleanup = true;
							if (!$l_placecleanup && ($placesample = ImportFunctions::NeedPlaceCleanup($indirec)) !== false) $l_placecleanup = true;
							if (!$l_datecleanup && ($datesample = ImportFunctions::NeedDateCleanup($indirec)) !== false) $l_datecleanup = true;
							if (!$l_isansi && ImportFunctions::IsAnsi($indirec)) $l_isansi = true;
							
							// Get all xrefs
							$ct = preg_match_all("/\d\s+@(.*)@\s(\w*)/", $indirec, $match);
							if ($ct>0) {
								for ($i=0;$i<$ct;$i++) {
									$match[2][$i] = trim($match[2][$i]);
									// print "Handling ".$match[1][$i]." at ".$pos1."<br />";
									switch ($match[2][$i]) {
										case "INDI": 
											$inditab[$match[1][$i]] = $GEDCOM_ID_PREFIX.$lastindi;
											$lastindi++;
											break;
										case "FAM":
											$famtab[$match[1][$i]] = $FAM_ID_PREFIX.$lastfam;
											$lastfam++;
											break;
										case "OBJE":
											$objetab[$match[1][$i]] = $MEDIA_ID_PREFIX.$lastobje;
											$lastobje++;
											break;
										case "SOUR":
											$sourtab[$match[1][$i]] = $SOURCE_ID_PREFIX.$lastsour;
											$lastsour++;
											break;
										case "REPO":
											$repotab[$match[1][$i]] = $REPO_ID_PREFIX.$lastrepo;
											$lastrepo++;
											break;
										case "NOTE":
											$notetab[$match[1][$i]] = $NOTE_ID_PREFIX.$lastnote;
											$lastnote++;
											break;
										default:
											// print "Not found type: ".$match[2][$i]." ID: ".$match[1][$i]." <br /><br />";
											// ignored
											break;
									}
								}
							}
							//-- move the cursor to the start of the next record
							$pos1 = 0;
							$fcontents = substr($fcontents, $pos2);
							if ($pos2 == 0 && feof($fp)) $end = true;
						}
						fclose($fp);
						$transtab = array();
						$transtab["INDI"] = $inditab;
						$transtab["FAM"] = $famtab;
						$transtab["OBJE"] = $objetab;
						$transtab["SOUR"] = $sourtab;
						$transtab["REPO"] = $repotab;
						$transtab["NOTE"] = $notetab;
//						print "<pre>";
//						print_r($transtab);
//						print "</pre>";
//						exit;
						@unlink(INDEX_DIRECTORY."transtab.txt");
						$fp = fopen(INDEX_DIRECTORY."transtab.txt", "wb");
						if ($fp) {
							fwrite($fp, serialize($transtab));
							fclose($fp);
						}
						SwitchGedcom();
						if (!isset($cleanup_needed)) $cleanup_needed = false;
						if (!$l_datecleanup && !$l_isansi  && !$l_headcleanup && !$l_macfilecleanup &&!$l_placecleanup && !$l_lineendingscleanup) {
							print $gm_lang["valid_gedcom"];
							$import = true;
						}
						else {
							$cleanup_needed = true;
							print "<input type=\"hidden\" name=\"cleanup_needed\" value=\"cleanup_needed\">";
							if (!FileIsWriteable($GEDFILENAME) && (file_exists($GEDFILENAME))) {
								print "<span class=\"error\">".str_replace("#GEDCOM#", get_gedcom_from_id($GEDCOMID), $gm_lang["error_header_write"])."</span>\n";
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
				<input type = "hidden" name="merge_ged" value="<?php if (isset($merge_ged)) print $merge_ged; ?>" />
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
		print "<div class=\"shade1\" style=\"padding-top:5px;\">";
			print "<div id=\"import_options\" style=\"display: ";
			if ($startimport != "true") print "block ";
			else print "none ";
			print "\">";
				// NOTE: Time limit for import
				// TODO: Write help text
				print "<div class=\"shade2 wrap\">";
					print_help_link("time_limit_help", "qm", "time_limit");
					print "<span style=\"vertical-align: 25%\">".$gm_lang["time_limit"]."</span>&nbsp;";
					print "<input type=\"text\" name=\"timelimit\" value=\"".$timelimit."\" size=\"5\"";
					if ($startimport == "true")  print " disabled=\"disabled\" ";
					print "/>\n";
				print "</div>";
				
				// NOTE: Import married names
				print "<div class=\"shade2 wrap\">";
				print_help_link("import_marr_names_help", "qm", "import_marr_names");
				print "<span style=\"vertical-align: 25%\">".$gm_lang["import_marr_names"].":</span>&nbsp;";
				if ($startimport == "true") print "<span style=\"vertical-align: 25%\">".$gm_lang[$marr_names]."</span>";
				else {
					print "<select name=\"marr_names\">\n";
					print "<option value=\"yes\">".$gm_lang["yes"]."</option>\n";
					print "<option value=\"no\" selected=\"selected\">".$gm_lang["no"]."</option>\n</select>";
				}
				print "</div>";
				
				// NOTE: change XREF to RIN, REFN, or Don't change
				print "<div class=\"shade2 wrap\">";
				print_help_link("change_indi2id_help", "qm", "change_id");
				print "<span style=\"vertical-align: 25%\">".$gm_lang["change_id"]."</span>&nbsp;";
				if ($startimport == "true") {
					if ($xreftype == "NA") print "<span style=\"vertical-align: 25%\">".$gm_lang["do_not_change"]."</span>";
					else print $xreftype;
				}
				else {
					print "<select name=\"xreftype\">\n";
					print "<option value=\"NA\">".$gm_lang["do_not_change"]."</option>\n<option value=\"RIN\">RIN</option>\n";
					print "<option value=\"REFN\">REFN</option>\n</select>";
				}
				print "</div>\n";
				
				// NOTE: option to convert to utf8
				print "<div class=\"shade2 wrap\">";
				print_help_link("convert_ansi2utf_help", "qm", "ansi_to_utf8");
				print "<span style=\"vertical-align: 25%\">".$gm_lang["ansi_to_utf8"]."</span>&nbsp;";
				if ($startimport == "true") print "<span style=\"vertical-align: 25%\">".$gm_lang[strtolower($utf8convert)]."</span>";
				else {
					print "<select name=\"utf8convert\">\n";
					print "<option value=\"YES\">".$gm_lang["yes"]."</option>\n";
					print "<option value=\"NO\" selected=\"selected\">".$gm_lang["no"]."</option>\n</select>";
				}
				print "</div>";
				
				print "<input type=\"hidden\" name=\"startimport\" value=\"true\" />";
				print "<input type=\"hidden\" name=\"merge_ged\" value=\"".$merge_ged."\" />";
//				print "<input type=\"hidden\" name=\"ged\" value=\"";
//				if (isset($GEDFILENAME)) print $GEDFILENAME;
//				print "\" />";
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
		
		/**
		 * function that sets up the html required to run the progress bar
		 * @param long $FILE_SIZE	the size of the file
		 */
		function setup_progress_bar($FILE_SIZE) {
			global $gm_lang, $GEDCOMID, $timelimit;
			?>
			<script type="text/javascript">
			<!--
			function complete_progress(time, exectext, go_pedi, go_welc) {
				progress = document.getElementById("progress_header");
				if (progress) progress.innerHTML = '<?php print "<span class=\"error\"><b>".$gm_lang["import_complete"]."</b></span><br />";?>'+exectext+' '+time+' '+"<?php print $gm_lang["sec"]; ?>";
				progress = document.getElementById("link1");
				if (progress) progress.innerHTML = '<a href="pedigree.php?gedid=<?php print $GEDCOMID; ?>">'+go_pedi+'</a>';
				progress = document.getElementById("link2");
				if (progress) progress.innerHTML = '<a href="index.php?command=gedcom&gedid=<?php print $GEDCOMID; ?>">'+go_welc+'</a>';
				progress = document.getElementById("link3");
				if (progress) progress.innerHTML = '<a href="editgedcoms.php">'+"<?php print $gm_lang["manage_gedcoms"]."</a>"; ?>";
			}
			function wait_progress() {
				progress = document.getElementById("progress_header");
				if (progress) progress.innerHTML = '<?php print $gm_lang["please_be_patient"]; ?>';
			}
			
			var FILE_SIZE = <?php print $FILE_SIZE; ?>;
			var TIME_LIMIT = <?php print $timelimit; ?>;
			function update_progress(bytes, time) {
				perc = Math.round(100*(bytes / FILE_SIZE));
				if (perc>100) perc = 100;
				progress = document.getElementById("progress_div");
				if (progress) {
					progress.style.width = perc+"%";
					progress.innerHTML = perc+"%";
				}
				perc = Math.round(100*(time / TIME_LIMIT));
				if (perc>100) perc = 100;
				progress = document.getElementById("time_div");
				if (progress) {
					progress.style.width = perc+"%";
					progress.innerHTML = perc+"%";
				}
			}
			//-->
			</script>
			<?php
			// NOTE: Print the progress bar for the GEDCOM file
			print "<div>";
				print "<div id=\"progress_header\" class=\"progress_box\" style=\"float: left;\">\n";
					print "<b>".$gm_lang["import_progress"]."</b>";
					print "<div class=\"inner_progress_bar\">\n";
						print "<div id=\"progress_div\" class=\"progress_bar\">";
						if (isset($_SESSION["TOTAL_BYTES"])) {
							print "\n<script type=\"text/javascript\"><!--\nupdate_progress(".$_SESSION["TOTAL_BYTES"].",".$_SESSION["exectime_start"].");\n//--></script>\n";
						}
						else print "1%";
						print "</div>\n";
					print "</div>\n";
				print "</div>\n";
				
				// NOTE: Print the links after import
				print "<div class=\"progress_links\">";
					print "<div id=\"link1\">&nbsp;</div>";
					print "<div id=\"link2\">&nbsp;</div>";
					print "<div id=\"link3\">&nbsp;</div>";
				print "</div>";
				
				// NOTE: Print the progress bar for the time
				print "<div id=\"progress_header\" class=\"progress_box\">\n";
					if ($timelimit == 0) print "<b>".$gm_lang["time_limit"]." ".$gm_lang["none"]."</b>";
					else print "<b>".$gm_lang["time_limit"]." ".$timelimit." ".$gm_lang["sec"]."</b>";
					print "<div class=\"inner_progress_bar\">\n";
						print "<div id=\"time_div\" class=\"progress_bar\">1%</div>\n";
					print "</div>\n";
				print "</div>\n";
			print "</div>";
			flush();
			@ob_flush();
		}
		//-- end of setup_progress_bar function
		
		if (!isset($stage)) $stage = 0;
		$temp = $THEME_DIR;
		$GEDCOM_FILE = $GEDFILENAME;
		$FILEID = $merge_ged;
		$FILE = get_gedcom_from_id($FILEID);
		$TITLE = $GEDCOMS[$FILEID]["title"];
		SwitchGedcom($FILEID);
		if ($LANGUAGE <> $_SESSION["CLANGUAGE"]) $LANGUAGE = $_SESSION["CLANGUAGE"];
		GedcomConfig::ResetCaches(get_id_from_gedcom($merge_ged));
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
			setup_progress_bar($FILE_SIZE);
			flush();
			@ob_flush();
			// ------------------------------------------------------ Begin importing data
			// -- array of names
//			if (!isset($indilist)) $indilist = array();
//			if (!isset($famlist)) $famlist = array();
//			$sourcelist = array();
//			$otherlist = array();
			$i=0;
			$ft = fopen(INDEX_DIRECTORY."transtab.txt", "rb");
			$ftt = fread($ft, filesize(INDEX_DIRECTORY."transtab.txt"));
			fclose($ft);
			$transtab = unserialize($ftt);
			reset($transtab);
//			print_r($transtab);
			unset($ftt);
			$skiprecs = array("HEAD", "SUBM", "TRLR");
			$reftype = array("HUSB"=>"INDI", "WIFE"=>"INDI", "CHIL"=>"INDI", "ALIA"=>"INDI", "ASSO"=>"INDI", "FAMC"=>"FAM", "FAMS"=>"FAM", "SOUR"=>"SOUR", "REPO"=>"REPO", "OBJE"=>"OBJE", "NOTE"=>"NOTE");
			$fpged = fopen($GEDCOM_FILE, "rb");
			$BLOCK_SIZE = 1024*8;	//-- 8k bytes per read
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
					$media_count = $_SESSION["media_count"];
					$found_ids = $_SESSION["found_ids"];
					$TOTAL_BYTES = $_SESSION["TOTAL_BYTES"];
					$fcontents = $_SESSION["fcontents"];
					$listtype = $_SESSION["listtype"];
					$exectime_start = $_SESSION["exectime_start"];
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
						
					// Do some preliminary cleanup
					//-- remove double @ signs
					$indirec = preg_replace("/@+/", "@", $indirec);
					
					// remove heading spaces
					$indirec = preg_replace("/\n(\s*)/", "\n", $indirec);
					
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
					
					//-- renumber all references
					$ct = preg_match("/0\s+@(.*)@\s+(\w*)/", $indirec, $match);
					if ($ct && !in_array(trim($match[2]), $skiprecs)) {
						if (array_key_exists(trim($match[2]), $transtab) && array_key_exists($match[1], $transtab[trim($match[2])])) $indirec = preg_replace("/@$match[1]@/", "@".$transtab[trim($match[2])][$match[1]]."@", $indirec);
						else print "Problem with: ".$indirec."<br />This record cannot be renumbered, as the recordtype could not be determined earlier. Key: ".$match[2]."<br />";
						$indisubrecs = preg_split("/\r\n/", $indirec);
						$indiline = "";
						foreach ($indisubrecs as $key => $subrec) {
							$ct2 = preg_match_all("/\d\s(\w+)\s@(\w+)@/", $subrec, $match2);
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
						
												
						//-- import anything that is not a blob
						if (preg_match("/\n1 BLOB/", $indirec)==0) {
							$gid = ImportFunctions::ImportRecord($indirec);
							$place_count += ImportFunctions::UpdatePlaces($gid, $indirec);
							$date_count += ImportFunctions::UpdateDates($gid, $indirec);
						}
						else WriteToLog("MergeGedcom -> Import skipped a aecord with a BLOB tag: ".$indirec, "E", "G", get_gedcomid_from_file($FILE));
						
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
//					$show_gid=$gid;
					
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
							
								//-- close the file connection
//								if (is_resource($fpged)) fclose($fpged);
								fclose($fpged);
								$_SESSION["resumed"]++;
								print "\n<table class=\"facts_table\">";
								
								?>
								<?php print $gm_lang["import_time_exceeded"]; ?>
								<input type="hidden" name="ged" value="<?php print $ged; ?>" />
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
								<input type="submit" name="continue" value="<?php print $gm_lang["del_proceed"]; ?>" />
								<?php
								DMSoundex("", "closecache");
								//-- We write the session data and close it. Fix for intermittend logoff.
								session_write_close();
								print_footer();
								exit;
							}
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
				print "<script type=\"text/javascript\"><!--complete_progress(".$importtime.", '".$exec_text."', '".$go_pedi."', '".$go_welc."');\n//-->\n</script>";
				flush();
				@ob_flush();
			}
			
			if ($marr_names == "yes") {
				$GEDCOM = $FILE;
				$flist = GetFemalesWithFAMS();
				$famlist = GetFamListWithMARR();
				print $gm_lang["calc_marr_names"];
				setup_progress_bar(count($flist));
			
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
								<input type="hidden" name="ged" value="<?php print $ged; ?>" />
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
				$show_table_marr .= "<td class=\"shade1 indent_rtl rtl\">$names_added<script type=\"text/javascript\"><!--\nupdate_progress($i, $exectime);\n//-->\n</script></td>";
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
			@unlink(INDEX_DIRECTORY."transtab.txt");
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