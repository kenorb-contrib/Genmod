<?php
/**
 * Allow admin users to upload a new gedcom using a web interface.
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
 * This Page Is Valid XHTML 1.0 Transitional! > 12 September 2005
 * 
 * @author Genmod Development Team
 * @package Genmod
 * @subpackage Admin
 * @version $Id: uploadgedcom.php,v 1.18 2006/04/30 18:44:15 roland-d Exp $
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

/**
 * Inclusion of the language files
*/
// require $GM_BASE_DIRECTORY.$confighelpfile["english"];
// if (file_exists($GM_BASE_DIRECTORY.$confighelpfile[$LANGUAGE])) require $GM_BASE_DIRECTORY.$confighelpfile[$LANGUAGE];

if (!userGedcomAdmin($gm_username)) {
	header("Location: login.php?url=uploadgedcom.php");
	exit;
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
	if (((!file_exists($INDEX_DIRECTORY.$GEDFILENAME)) && !file_exists($path.$GEDFILENAME))  || $override == "yes") {
		if ($path != "") $fp = fopen($path.$GEDFILENAME, "wb");
		else	$fp = fopen($INDEX_DIRECTORY.$GEDFILENAME, "wb");
		if ($fp) {
			$newgedcom = '0 HEAD
1 SOUR Genmod
2 VERS '.$VERSION.' '.$VERSION_RELEASE.'
1 DEST ANSTFILE
1 GEDC
2 VERS 5.5
2 FORM Lineage-Linked
1 CHAR UTF-8
0 @I1@ INDI
1 NAME Given Names /Surname/
1 SEX M
1 BIRT
2 DATE 01 JAN 1850
2 PLAC Click edit and change me
0 TRLR';
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
		else	$fp = fopen($INDEX_DIRECTORY.$GEDFILENAME.".bak", "wb");
		if ($fp) {
			$newgedcom = '0 HEAD
1 SOUR Genmod
2 VERS '.$VERSION.' '.$VERSION_RELEASE.'
1 DEST ANSTFILE
1 GEDC
2 VERS 5.5
2 FORM Lineage-Linked
1 CHAR UTF-8
0 @I1@ INDI
1 NAME Given Names /Surname/
1 SEX M
1 BIRT
2 DATE 01 JAN 1850
2 PLAC Click edit and change me
0 TRLR';
			fwrite($fp, $newgedcom);
			fclose($fp);
			if ($path != "") $bakfile = $path.$GEDFILENAME.".bak";
			else	$bakfile = $INDEX_DIRECTORY.$GEDFILENAME.".bak";
			$ok = false;
			$verify = "verify_gedcom";
			$exists = true;
		}
	}
}
else if ($check == "cancel_upload") {
	if ($exists) {
		unset($GEDCOMS[$GEDFILENAME]);
		store_gedcoms();
		if ($action == "add_new_form") @unlink($INDEX_DIRECTORY.$GEDFILENAME);
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
	require_once("includes/functions_tools.php");
	
	$filechanged=false;
	if (file_is_writeable($GEDCOMS[$GEDFILENAME]["path"]) && (file_exists($GEDCOMS[$GEDFILENAME]["path"]))) {
		$l_headcleanup = false;
		$l_macfilecleanup = false;
		$l_lineendingscleanup = false;
		$l_placecleanup = false;
		$l_datecleanup=false;
		$l_isansi = false;
		$fp = fopen($GEDCOMS[$GEDFILENAME]["path"], "rb");
		$fw = fopen($INDEX_DIRECTORY."/".$GEDFILENAME.".bak", "wb");
		//-- read the gedcom and test it in 8KB chunks
		while(!feof($fp)) {
			$fcontents = fread($fp, 1024*8);
			$lineend = "\n";
			if (need_macfile_cleanup()) {
				$l_macfilecleanup=true;
				$lineend = "\r";
			}
			
			//-- read ahead until the next line break
			$byte = "";
			while((!feof($fp)) && ($byte!=$lineend)) {
				$byte = fread($fp, 1);
				$fcontents .= $byte;
			}
			
			if (!$l_headcleanup && need_head_cleanup()) {
				head_cleanup();
				$l_headcleanup = true;
			}
	
			if ($l_macfilecleanup) {
				macfile_cleanup();
			}
	
			if (isset($_POST["cleanup_places"]) && $_POST["cleanup_places"]=="YES") {
				if(($sample = need_place_cleanup()) !== false) {
					$l_placecleanup=true;
					place_cleanup();
				}
			}
	
			if (line_endings_cleanup()) {
				$filechanged = true;
			}
	
			if(isset($_POST["datetype"])) {
				$filechanged=true;
				//month first
				date_cleanup($_POST["datetype"]);
			}
			/**
			if($_POST["xreftype"]!="NA") {
				$filechanged=true;
				xref_change($_POST["xreftype"]);
			}
			**/
			if (isset($_POST["utf8convert"])=="YES") {
				$filechanged=true;
				convert_ansi_utf8();
			}
			fwrite($fw, $fcontents);
		}
		fclose($fp);
		fclose($fw);
		copy($INDEX_DIRECTORY."/".$GEDFILENAME.".bak", $GEDCOMS[$GEDFILENAME]["path"]);
		$cleanup_needed = false;
		$import = "true";
	}
	else {
		$error = str_replace("#GEDCOM#", $GEDFILENAME, $gm_lang["error_header_write"]);
	}
}

// NOTE: Change header depending on action
if ($action == "upload_form") print_header($gm_lang["upload_gedcom"]);
else if ($action == "add_form") print_header($gm_lang["add_gedcom"]);
else if ($action == "add_new_form") print_header($gm_lang["add_new_gedcom"]);
else print_header($gm_lang["ged_import"]);

// NOTE: Print form header
print "<form enctype=\"multipart/form-data\" method=\"post\" name=\"configform\" action=\"uploadgedcom.php\">";

// NOTE: Print table header
print "<table class=\"facts_table center $TEXT_DIRECTION\">";

// NOTE: Add GEDCOM form
if ($action == "add_form") {
	print "<tr><td class=\"topbottombar $TEXT_DIRECTION\" colspan=\"2\">";
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
	print "</td></tr>";
	print "<tr><td class=\"shade1\">";
	print "<div id=\"add-form\" style=\"display: ";
	if ($startimport != "true") print "block ";
	else print "none ";
	print "\">";
		?>
		<input type="hidden" name="check" value="add" />
		<input type="hidden" name="action" value="<?php print $action; ?>" />
		<input type="hidden" name="import_existing" value="<?php print $import_existing; ?>" />
		<table class="facts_table">
			<?php
			$i = 0;
			if (!empty($error)) {
				print "<tr><td class=\"shade1 wrap\" colspan=\"2\">";
				print "<span class=\"error\">".$error."</span>\n";
				print "</td></tr>";
			}
			?>
			<tr>
				<td class="shade2 width20 wrap">
				<?php print_help_link("gedcom_path_help", "qm","gedcom_path");?>
				<?php print $gm_lang["gedcom_file"]; ?></td>
				<td class="shade1"><input type="text" name="GEDFILENAME" value="<?php if (isset($GEDFILENAME) && strlen($GEDFILENAME) > 4) print $GEDCOMS[$GEDFILENAME]["path"]; ?>" 
				size="60" dir ="ltr" tabindex="<?php $i++; print $i?>"	<?php if ((!$no_upload && isset($GEDFILENAME)) && (empty($error))) print "disabled "; ?> />
				</td>
			</tr>
		</table>
		<?php
	print "</div>";
	print "</td></tr>";
}
// NOTE: Upload GEDCOM form
else if ($action == "upload_form") {
	print "<tr><td class=\"topbottombar $TEXT_DIRECTION\" colspan=\"2\">";
	print "<a href=\"javascript: ".$gm_lang["upload_gedcom"]."\" onclick=\"expand_layer('upload_gedcom'); return false;\"><img id=\"upload_gedcom_img\" src=\"".$GM_IMAGE_DIR."/";
	if ($startimport != "true") print $GM_IMAGES["minus"]["other"];
	else print $GM_IMAGES["plus"]["other"];
	print "\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
	print_help_link("upload_gedcom_help", "qm", "upload_gedcom");
	print "&nbsp;<a href=\"javascript: ".$gm_lang["upload_gedcom"]."\" onclick=\"expand_layer('upload_gedcom');return false\">".$gm_lang["upload_gedcom"]."</a>";
	print "</td></tr>";
	print "<tr><td class=\"shade1 wrap\">";
	print "<div id=\"upload_gedcom\" style=\"display: ";
	if ($startimport != "true") print "block ";
	else print "none ";
	print "\">";
	?>
		<input type="hidden" name="action" value="<?php print $action; ?>" />
		<input type="hidden" name="check" value="upload" />
		<table class="facts_table">
		<?php
		if (!empty($error)) {
			print "<span class=\"error\">".$error."</span><br />\n";
			print_text("common_upload_errors");
			print "<br />\n";
		}
		
		?>
		<tr>
			<td class="shade2 width20 wrap">
			<?php print $gm_lang["gedcom_file"];?></td>
			<td class="shade1">
				<?php
				if (isset($GEDFILENAME)) print $path.$GEDFILENAME;
				else if (isset($UPFILE)) print $UPFILE["name"];
				else {
					print "<input name=\"UPFILE\" type=\"file\" size=\"60\" />";
					if (!$filesize = ini_get('upload_max_filesize')) $filesize = "2M";
					print " ( ".$gm_lang["max_upload_size"]." $filesize )";
				}
				?>
			</td>
		</tr>
		</table>
		<?php
	print "</div>";
	print "</td></tr>";
}
// NOTE: Add new GEDCOM form
else if ($action == "add_new_form") {
	print "<tr><td class=\"topbottombar $TEXT_DIRECTION\" colspan=\"2\">";
	print "<a href=\"javascript: ".$gm_lang["add_new_gedcom"]."\" onclick=\"expand_layer('add_new_gedcom');return false\"><img id=\"add_new_gedcom_img\" src=\"".$GM_IMAGE_DIR."/";
	if ($startimport != "true") print $GM_IMAGES["minus"]["other"];
	else print $GM_IMAGES["plus"]["other"];
	print "\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
	print_help_link("add_gedcom_instructions", "qm","add_new_gedcom");
	print "&nbsp;<a href=\"javascript: ".$gm_lang["add_new_gedcom"]."\" onclick=\"expand_layer('add_new_gedcom');return false\">".$gm_lang["add_new_gedcom"]."</a>";
	print "</td></tr>";
	print "<tr><td class=\"shade1\">";
	print "<div id=\"add-form\" style=\"display: ";
	if ($startimport != "true") print "block ";
	else print "none ";
	print "\">";
		?>
		<input type="hidden" name="action" value="<?php print $action; ?>" />
		<input type="hidden" name="check" value="add_new" />
		<table class="facts_table">
		<?php
		if (!empty($error)) {
				print "<tr><td class=\"shade1 wrap\" colspan=\"2\">";
				print "<span class=\"error\">".$error."</span>\n";
				print "</td></tr>";
		}
		?>
		<tr>
			<td class="shade2 width20 wrap">
			<?php print $gm_lang["gedcom_file"];?>
			</td>
			<td class="shade1"><input name="GEDFILENAME" type="text" value="<?php if (isset($GEDFILENAME)) print $path.$GEDFILENAME; ?>" size="60" <?php if (isset($GEDFILENAME) && !$no_upload) print "disabled"; ?> /></td>
		</tr>
		</table>
		<?php
	print "</div>";
	print "</td></tr>";
}
if ($verify=="verify_gedcom") {
	// NOTE: Check if GEDCOM has been imported into DB
	$imported = check_for_import($GEDFILENAME);
	if ($imported || $bakfile != "") {
		// NOTE: If GEDCOM exists show warning
		print "<tr><td class=\"topbottombar $TEXT_DIRECTION\" colspan=\"2\">";
		print "<a href=\"javascript: ".$gm_lang["verify_gedcom"]."\" onclick=\"expand_layer('verify_gedcom');return false\"><img id=\"verify_gedcom_img\" src=\"".$GM_IMAGE_DIR."/";
		if ($startimport != "true") print $GM_IMAGES["minus"]["other"];
		else print $GM_IMAGES["plus"]["other"];
		print "\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
		print_help_link("verify_gedcom_help", "qm", "verify_gedcom");
		print "&nbsp;<a href=\"javascript: ".$gm_lang["verify_gedcom"]."\" onclick=\"expand_layer('verify_gedcom');return false\">".$gm_lang["verify_gedcom"]."</a>";
		print "</td></tr>";
		print "<tr><td class=\"shade1\" colspan=\"2\">";
		print "<div id=\"verify_gedcom\" style=\"display: ";
		if ($startimport != "true") print "block ";
		else print "none ";
		print "\">";
		print "<table class=\"facts_table\">";
		print "<tr><td class=\"shade2 width20 wrap\" colspan=\"2\">";
		?>
		<input type="hidden" name="no_upload" value="" />
		<input type="hidden" name="check" value="" />
		<input type="hidden" name="verify" value="validate_form" />
		<input type="hidden" name="GEDFILENAME" value="<?php if (isset($GEDFILENAME)) print $GEDFILENAME; ?>" />
		<input type="hidden" name="bakfile" value="<?php if (isset($bakfile)) print $bakfile; ?>" />
		<input type="hidden" name="path" value="<?php if (isset($path)) print $path; ?>" />
		
		<?php
		if ($imported) print "<span class=error>".$gm_lang["dataset_exists"]."</span><br /><br />";
		if ($bakfile != "") print $gm_lang["verify_upload_instructions"]."</td></tr>";
		// TODO: Check for existing changes
		
		if ($imported || $bakfile != "") { 
			print "<tr><td class=\"shade2 width20 wrap\">". $gm_lang["empty_dataset"]."</td><td class=\"shade1 vmiddle\">\n";
			print "<select name=\"override\">";
			print "<option value=\"yes\" ";
			if ($override == "yes") print "selected=\"selected\"";
			print ">".$gm_lang["yes"]."</option>";
			print "<option value=\"no\" ";
			if ($override != "yes") print "selected=\"selected\"";
			print ">".$gm_lang["no"]."</option>";
			print "</select></td></tr><tr><td class=\"shade1 wrap\" colspan=\"2\">";
		}
		print "</td></tr>";
		print "</table>";
	}
	else $verify = "validate_form";
}
if ($verify == "validate_form") {
	print "<tr><td class=\"topbottombar $TEXT_DIRECTION\" colspan=\"2\">";
	print "<a href=\"javascript: ".$gm_lang["validate_gedcom"]."\" onclick=\"expand_layer('validate_gedcom');return false\"><img id=\"validate_gedcom_img\" src=\"".$GM_IMAGE_DIR."/";
	if ($startimport != "true") print $GM_IMAGES["minus"]["other"];
	else print $GM_IMAGES["plus"]["other"];
	print "\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
	print_help_link("validate_gedcom_help", "qm","validate_gedcom");
	print "&nbsp;<a href=\"javascript: ".$gm_lang["validate_gedcom"]."\" onclick=\"expand_layer('validate_gedcom');return false\">".$gm_lang["validate_gedcom"]."</a>";
	print "</td></tr>";
	print "<tr><td class=\"shade1\">";
	print "<div id=\"validate_gedcom\" style=\"display: ";
	if ($startimport != "true") print "block ";
	else print "none ";
	print "\">";
		print "<table class=\"facts_table\">";
		print "<tr><td class=\"shade2\" colspan=\"2\">".$gm_lang["performing_validation"]."<br />";
		if (!empty($error)) print "<span class=\"error\">$error</span>\n";
		
		if ($import != true && $skip_cleanup != $gm_lang["skip_cleanup"]) {
			require_once("includes/functions_tools.php");
			if ($override == "yes") {
				copy($bakfile, $GEDCOMS[$GEDFILENAME]["path"]);
				if (file_exists($bakfile)) unlink($bakfile);
				$bakfile = false;
			}
			$l_headcleanup = false;
			$l_macfilecleanup = false;
			$l_lineendingscleanup = false;
			$l_placecleanup = false;
			$l_datecleanup=false;
			$l_isansi = false;
			$fp = fopen($GEDCOMS[$GEDFILENAME]["path"], "r");
			//-- read the gedcom and test it in 8KB chunks
			while(!feof($fp)) {
				$fcontents = fread($fp, 1024*8);
				if (!$l_headcleanup && need_head_cleanup()) $l_headcleanup = true;
				if (!$l_macfilecleanup && need_macfile_cleanup()) $l_macfilecleanup = true;
				if (!$l_lineendingscleanup && need_line_endings_cleanup()) $l_lineendingscleanup = true;
				if (!$l_placecleanup && ($placesample = need_place_cleanup()) !== false) $l_placecleanup = true;
				if (!$l_datecleanup && ($datesample = need_date_cleanup()) !== false) $l_datecleanup = true;
				if (!$l_isansi && is_ansi()) $l_isansi = true;
			}
			fclose($fp);
			
			if (!isset($cleanup_needed)) $cleanup_needed = false;
			if (!$l_datecleanup && !$l_isansi  && !$l_headcleanup && !$l_macfilecleanup &&!$l_placecleanup && !$l_lineendingscleanup) {
				print $gm_lang["valid_gedcom"];
				print "</td></tr>";
				$import = true;
			}
			else {
				$cleanup_needed = true;
				print "<input type=\"hidden\" name=\"cleanup_needed\" value=\"cleanup_needed\">";
				if (!file_is_writeable($GEDCOMS[$GEDFILENAME]["path"]) && (file_exists($GEDCOMS[$GEDFILENAME]["path"]))) {
					print "<span class=\"error\">".str_replace("#GEDCOM#", $GEDCOM, $gm_lang["error_header_write"])."</span>\n";
					print "</td></tr>";
				}
				// NOTE: Check for head cleanu
				if ($l_headcleanup) {
					print "<tr><td class=\"shade1 wrap\" colspan=\"2\">";
					print_help_link("invalid_header_help", "qm", "invalid_header");
					print "<span class=\"error\">".$gm_lang["invalid_header"]."</span>\n";
					print "</td></tr>";
				}
				// NOTE: Check for mac file cleanup
				if ($l_macfilecleanup) {
					print "<tr><td class=\"shade1 wrap\" colspan=\"2\">";
					print_help_link("macfile_detected_help", "qm", "macfile_detected");
					print "<span class=\"error\">".$gm_lang["macfile_detected"]."</span>\n";
					print "</td></tr>";
				}
				// NOTE: Check for line endings cleanup
				if ($l_lineendingscleanup) {
					print "<tr><td class=\"shade1 wrap\" colspan=\"2\">";
					print_help_link("empty_lines_detected_help", "qm", "empty_lines_detected");
					print "<span class=\"error\">".$gm_lang["empty_lines_detected"]."</span>\n";
					print "</td></tr>";
				}
				// NOTE: Check for place cleanup
				if ($l_placecleanup) {
					print "<tr><td class=\"shade1 wrap\" colspan=\"2\">";
					print "<table class=\"facts_table\">";
					print "<tr><td class=\"shade1 wrap\" colspan=\"2\">";
					print "<span class=\"error\">".$gm_lang["place_cleanup_detected"]."</span>\n";
					print "</td></tr>";
					print "<tr><td class=\"shade2 wrap width20\">";
					print_help_link("cleanup_places_help", "qm", "cleanup_places");
					print $gm_lang["cleanup_places"];
					print "</td><td class=\"shade1\" colspan=\"2\"><select name=\"cleanup_places\">\n";
					print "<option value=\"YES\" selected=\"selected\">".$gm_lang["yes"]."</option>\n<option value=\"NO\">".$gm_lang["no"]."</option>\n</select>";
					print "</td></tr>";
					print "</td></tr><tr><td class=\"shade1\" colspan=\"2\">".$gm_lang["example_place"]."<br />".PrintReady(nl2br($placesample[0]));
					print "</table>\n";
					print "</td></tr>";
				}
				// NOTE: Check for date cleanup
				if ($l_datecleanup) {
					print "<tr><td class=\"shade1 wrap\" colspan=\"2\">";
					print "<span class=\"error\">".$gm_lang["invalid_dates"]."</span>\n";
					print "<table class=\"facts_table\">";
					print "<tr><td class=\"shade2 width20\">";
					print_help_link("detected_date_help", "qm");
					print $gm_lang["date_format"];
					
					print "</td><td class=\"shade1\" colspan=\"2\">";
					if (isset($datesample["choose"])){
						print "<select name=\"datetype\">\n";
						print "<option value=\"1\">".$gm_lang["day_before_month"]."</option>\n<option value=\"2\">".$gm_lang["month_before_day"]."</option>\n</select>";
					}
					else print "<input type=\"hidden\" name=\"datetype\" value=\"3\" />";
					print "</td></tr><tr><td class=\"shade1\" colspan=\"2\">".$gm_lang["example_date"]."<br />".$datesample[0];
					print "</td></tr>";
					print "</table>\n";
					print "</td></tr>";
				}
				// NOTE: Check for ansi encoding
				if ($l_isansi) {
					print "<tr><td class=\"shade1\" colspan=\"2\">";
					print "<span class=\"error\">".$gm_lang["ansi_encoding_detected"]."</span>\n";
					print "<table class=\"facts_table\">";
					print "<tr><td class=\"shade2 wrap width20\">";
					print_help_link("detected_ansi2utf_help", "qm", "ansi_to_utf8");
					print $gm_lang["ansi_to_utf8"];
					print "</td><td class=\"shade1\"><select name=\"utf8convert\">\n";
					print "<option value=\"YES\" selected=\"selected\">".$gm_lang["yes"]."</option>\n";
					print "<option value=\"NO\">".$gm_lang["no"]."</option>\n</select>";
					print "</td></tr>";
					print "</table>\n";
				}
			}
		}
		else if (!$cleanup_needed) {
			print $gm_lang["valid_gedcom"];
			$import = true;
		}
		else $import = true;
		?>
		<input type = "hidden" name="GEDFILENAME" value="<?php if (isset($GEDFILENAME)) print $GEDFILENAME; ?>" />
		<input type = "hidden" name="verify" value="validate_form" />
		<input type = "hidden" name="bakfile" value="<?php if (isset($bakfile)) print $bakfile; ?>" />
		<input type = "hidden" name="path" value="<?php if (isset($path)) print $path; ?>" />
		<input type = "hidden" name="no_upload" value="<?php if (isset($no_upload)) print $no_upload; ?>" />
		<input type = "hidden" name="override" value="<?php if (isset($override)) print $override; ?>" />
		<input type = "hidden" name="ok" value="<?php if (isset($ok)) print $ok; ?>" />
		<?php
	print "</table>";
	print "</div>";
	print "</td></tr>";

}
if ($import == true) {
	// NOTE: Additional import options
	print "<tr><td class=\"topbottombar $TEXT_DIRECTION\" colspan=\"2\">";
	print "<a href=\"javascript: ".$gm_lang["import_options"]."\" onclick=\"expand_layer('import_options');return false\"><img id=\"import_options_img\" src=\"".$GM_IMAGE_DIR."/";
	if ($startimport != "true") print $GM_IMAGES["minus"]["other"];
	else print $GM_IMAGES["plus"]["other"];
	print "\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
	print_help_link("import_options_help", "qm", "import_options");
	print "&nbsp;<a href=\"javascript: ".$gm_lang["import_options"]."\" onclick=\"expand_layer('import_options');return false\">".$gm_lang["import_options"]."</a>";
	print "</td></tr>";
	print "<tr><td class=\"shade1\" colspan=\"2\">";
	print "<div id=\"import_options\" style=\"display: ";
	if ($startimport != "true") print "block ";
	else print "none ";
	print "\">";
	print "<table class=\"facts_table\">";
	
	// NOTE: Time limit for import
	// TODO: Write help text
	print "<tr><td class=\"shade2 width20 wrap\">";
	print_help_link("time_limit_help", "qm", "time_limit");
	print $gm_lang["time_limit"];
	print "</td><td class=\"shade1\"><input type=\"text\" name=\"timelimit\" value=\"".$timelimit."\" size=\"5\"";
	if ($startimport == "true")  print " disabled ";
	print "/>\n";
	print "</td></tr>";
	
	// NOTE: Import married names
	print "<tr><td class=\"shade2 width20 wrap\">";
	print_help_link("import_marr_names_help", "qm", "import_marr_names");
	print $gm_lang["import_marr_names"].":";
	print "</td><td class=\"shade1\">";
	if ($startimport == "true") print $gm_lang[$marr_names];
	else {
		print "<select name=\"marr_names\">\n";
		print "<option value=\"yes\">".$gm_lang["yes"]."</option>\n";
		print "<option value=\"no\" selected=\"selected\">".$gm_lang["no"]."</option>\n</select>";
	}
	print "</td></tr>";
	
	// NOTE: change XREF to RIN, REFN, or Don't change
	print "<tr><td class=\"shade2 wrap\">";
	print_help_link("change_indi2id_help", "qm", "change_id");
	print $gm_lang["change_id"];
	print "</td><td class=\"shade1\">";
	if ($startimport == "true") {
		if ($xreftype == "NA") print $gm_lang["do_not_change"];
		else print $xreftype;
	}
	else {
		print "<select name=\"xreftype\">\n";
		print "<option value=\"NA\">".$gm_lang["do_not_change"]."</option>\n<option value=\"RIN\">RIN</option>\n";
		print "<option value=\"REFN\">REFN</option>\n</select>";
	}
	print "</td></tr>\n";
	
	// NOTE: option to convert to utf8
	print "<tr><td class=\"shade2 wrap\">";
	print_help_link("convert_ansi2utf_help", "qm", "ansi_to_utf8");
	print $gm_lang["ansi_to_utf8"];
	print "</td><td class=\"shade1\">";
	if ($startimport == "true") print $gm_lang[strtolower($utf8convert)];
	else {
		print "<select name=\"utf8convert\">\n";
		print "<option value=\"YES\">".$gm_lang["yes"]."</option>\n";
		print "<option value=\"NO\" selected=\"selected\">".$gm_lang["no"]."</option>\n</select>";
	}
	print "</td></tr>";
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
	print "</table></div>";
	print "</td></tr>";
}
if ($startimport == "true") {
	//-- set the building index flag to tell the rest of the program that we are importing and so shouldn't
	//-- perform some of the same checks
	$BUILDING_INDEX = true;
	
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
		global $gm_lang, $ged, $timelimit;
		?>
	<script type="text/javascript">
	<!--
	function complete_progress(time, exectext, go_pedi, go_welc) {
		progress = document.getElementById("progress_header");
		if (progress) progress.innerHTML = '<?php print "<span class=\"error\"><b>".$gm_lang["import_complete"]."</b></span><br />";?>'+exectext+' '+time+' '+"<?php print $gm_lang["sec"]; ?>";
		progress = document.getElementById("link1");
		if (progress) progress.innerHTML = '<a href="pedigree.php?ged=<?php print preg_replace("/'/", "\'", $ged); ?>">'+go_pedi+'</a>';
		progress = document.getElementById("link2");
		if (progress) progress.innerHTML = '<a href="index.php?command=gedcom&ged=<?php print preg_replace("/'/", "\'", $ged); ?>">'+go_welc+'</a>';
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
		print "<table style=\"width: 800px;\"><tr><td>";
		print "<div id=\"progress_header\" class=\"person_box\" style=\"width: 350px; margin: 10px; text-align: center;\">\n";
		print "<b>".$gm_lang["import_progress"]."</b>";
		print "<div style=\"left: 10px; right: 10px; width: 300px; height: 20px; border: inset #CCCCCC 3px; background-color: #000000;\">\n";
		print "<div id=\"progress_div\" class=\"person_box\" style=\"width: 1%; height: 18px; text-align: center; overflow: hidden;\">1%</div>\n";
		print "</div>\n";
		print "</div>\n";
		print "</td><td style=\"text-align: center;\"><div id=\"link1\">&nbsp;</div>";
		print "<div id=\"link2\">&nbsp;</div><div id=\"link3\">&nbsp;</div>";
		print "</td></tr></table>";
		print "<table style=\"width: 800px;\"><tr><td>";
		print "<div id=\"progress_header\" class=\"person_box\" style=\"width: 350px; margin: 10px; text-align: center;\">\n";
		if ($timelimit == 0) print "<b>".$gm_lang["time_limit"]." ".$gm_lang["none"]."</b>";
		else print "<b>".$gm_lang["time_limit"]." ".$timelimit." ".$gm_lang["sec"]."</b>";
		print "<div style=\"left: 10px; right: 10px; width: 300px; height: 20px; border: inset #CCCCCC 3px; background-color: #000000;\">\n";
		print "<div id=\"time_div\" class=\"person_box\" style=\"width: 1%; height: 18px; text-align: center; overflow: hidden;\">1%</div>\n";
		print "</div>\n";
		print "</div>\n";
		print "</td><td style=\"text-align: center;\"><div id=\"link1\">&nbsp;</div>";
		print "<div id=\"link2\">&nbsp;</div><div id=\"link3\">&nbsp;</div>";
		print "</td></tr></table>";
		flush();
	}
	//-- end of setup_progress_bar function
	
	if (!isset($stage)) $stage = 0;
	if ((empty($ged))||(!isset($GEDCOMS[$ged]))) $ged = $GEDCOM;
	
	$temp = $THEME_DIR;
	$GEDCOM_FILE = $GEDCOMS[$ged]["path"];
	$FILE = $ged;
	$TITLE = $GEDCOMS[$ged]["title"];
	ReadGedcomConfig($GEDCOMS[$ged]["gedcom"]);
	if ($LANGUAGE <> $_SESSION["CLANGUAGE"]) $LANGUAGE = $_SESSION["CLANGUAGE"];
	
	$temp2 = $THEME_DIR;
	$THEME_DIR = $temp;
	$THEME_DIR = $temp2;

	if (isset($GEDCOM_FILE)) {
		if ((!strstr($GEDCOM_FILE, "://"))&&(!file_exists($GEDCOM_FILE))) {
			print "<span class=\"error\"><b>Could not locate gedcom file at $GEDCOM_FILE<br /></b></span>\n";
			unset($GEDCOM_FILE);
		}
	}
	
	if ($stage==0) {
		$_SESSION["resumed"] = 0;
		empty_database($FILE);
		$stage=1;
	}
	
	flush();


	if ($stage==1) {
		@set_time_limit($timelimit);
		$FILE_SIZE = filesize($GEDCOM_FILE);
		print "<tr><td class=\"topbottombar $TEXT_DIRECTION\" colspan=\"2\">";
		print $gm_lang["reading_file"]." ".$GEDCOM_FILE;
		print "</td></tr>";
		print "<tr><td class=\"shade1\">";
		setup_progress_bar($FILE_SIZE);
		print "</td></tr>";
		flush();
		// ------------------------------------------------------ Begin importing data
		// -- array of names
		if (!isset($indilist)) $indilist = array();
		if (!isset($famlist)) $famlist = array();
		$sourcelist = array();
		$otherlist = array();
		$i=0;
	
		$fpged = fopen($GEDCOM_FILE, "r");
		$BLOCK_SIZE = 1024*4;	//-- 4k bytes per read
		$fcontents = "";
		$TOTAL_BYTES = 0;
		$place_count = 0;
		$date_count = 0;
		$media_count = 0;
		$listtype= array();
		//-- resume a halted import from the session
		if (!empty($_SESSION["resumed"])) {
				$place_count = $_SESSION["place_count"];
				$date_count = $_SESSION["date_count"];
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
			while(!feof($fpged)) {
				$fcontents .= fread($fpged, $BLOCK_SIZE);
				$TOTAL_BYTES += $BLOCK_SIZE;
				$pos1 = 0;
				while($pos1!==false) {
					//-- find the start of the next record
					$pos2 = strpos($fcontents, "\n0", $pos1+1);
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
				
					//-- import anything that is not a blob
					if (preg_match("/\n1 BLOB/", $indirec)==0) {
						$gid = import_record($indirec);
						$place_count += update_places($gid, $indirec);
						$date_count += update_dates($gid, $indirec);
					}
					
					//-- move the cursor to the start of the next record
					$pos1 = $pos2;
				
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
						print "\n<script type=\"text/javascript\">update_progress($TOTAL_BYTES, $exectime);</script>\n";
						flush();
					}
					else print " ";
					$show_gid=$gid;
				
					//-- check if we are getting close to timing out
					if ($i%10==0) {
						$newtime = time();
						$exectime = $newtime - $oldtime;
						if (($timelimit != 0) && ($timelimit - $exectime) < 2) {
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
							$_SESSION["i"] = $i;
							
							//-- close the file connection
							fclose($fpged);
							$_SESSION["resumed"]++;
							?>
							<tr><td class="shade2"><?php print $gm_lang["import_time_exceeded"]; ?></td></tr>
							<tr><td class="topbottombar">
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
							</td></tr></table>
							<?php
							DMSoundex("", "closecache");
							print_footer();
							exit;
						}
					}
				}
				$fcontents = substr($fcontents, $pos2);
			}
			fclose($fpged);
			DMSoundex("", "closecache");
			$newtime = time();
			$exectime = $newtime - $oldtime;
			$importtime = $importtime + $exectime;
			$exec_text = $gm_lang["exec_time"];
			$go_pedi = $gm_lang["click_here_to_go_to_pedigree_tree"];
			$go_welc = $gm_lang["welcome_page"];
			if ($LANGUAGE=="french" || $LANGUAGE=="italian"){
				print "<script type=\"text/javascript\">complete_progress($importtime, \"$exec_text\", \"$go_pedi\", \"$go_welc\");</script>";
			}
			else print "<script type=\"text/javascript\">complete_progress($importtime, '$exec_text', '$go_pedi', '$go_welc');</script>";
			flush();
		}
		
		if ($marr_names == "yes") {
			$GEDCOM = $FILE;
			GetIndiList();
			get_fam_list();
			print "<tr><td class=\"topbottombar $TEXT_DIRECTION\" colspan=\"2\">";
			print $gm_lang["calc_marr_names"];
			print "</td></tr>";
			print "<tr><td class=\"shade1\">";
			setup_progress_bar(count($indilist));
			print "</td></tr>";
		
			$i=0;
			$newtime = time();
			$exectime = $newtime - $oldtime;
			$exectime_start = $exectime;
			if (!isset($names_added)) $names_added = 0;
			include_once("includes/functions_edit.php");
			$manual_save = true;
			DMSoundex("", "opencache");
			if (!isset($lastgid)) $skip = false;
			else $skip = true;
			foreach($indilist as $gid=>$indi) {
				if ($skip == false) {
					$lastgid = $gid;
					if (preg_match("/1 SEX F/", $indi["gedcom"])>0) {
						$ct = preg_match_all("/1\s*FAMS\s*@(.*)@/", $indi["gedcom"], $match, PREG_SET_ORDER);
						if ($ct>0){
							for($j=0; $j<$ct; $j++) {
								if (isset($famlist[$match[$j][1]])) {
									$marrrec = get_sub_record(1, "1 MARR", $famlist[$match[$j][1]]["gedcom"]);
									if ($marrrec) {
										$parents = find_parents_in_record($famlist[$match[$j][1]]["gedcom"]);
										if ($parents["HUSB"]!=$gid) $spid = $parents["HUSB"];
										else $spid = $parents["WIFE"];
										if (isset($indilist[$spid])) {
											$surname = $indilist[$spid]["names"][0][2];
											$letter = $indilist[$spid]["names"][0][1];
											//-- uncomment the next line to put the maiden name in the given name area
											//$newname = preg_replace("~/(.*)/~", " $1 /".$surname."/", $indi["names"][0][0]);
											$newname = preg_replace("~/(.*)/~", "/".$surname."/", $indi["names"][0][0]);
											if (strpos($indi["gedcom"], "_MARNM $newname")===false) {
												$pos1 = strpos($indi["gedcom"], "1 NAME");
												if ($pos1!==false) {
													$pos1 = strpos($indi["gedcom"], "\n1", $pos1+1);
													if ($pos1!==false) $indi["gedcom"] = substr($indi["gedcom"], 0, $pos1)."\n2 _MARNM $newname\r\n".substr($indi["gedcom"], $pos1+1);
													else $indi["gedcom"]= trim($indi["gedcom"])."\r\n2 _MARNM $newname\r\n";
													$indi["gedcom"] = check_gedcom($indi["gedcom"], false);
													add_new_name($gid, $newname, $letter, $surname, $indi["gedcom"]);
													$names_added++;
												}
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
					print "\n<script type=\"text/javascript\">update_progress($i, $exectime);</script>\n";
					flush();

					//-- check if we are getting close to timing out
					$newtime = time();
					$exectime = $newtime - $oldtime;
					if (($timelimit != 0) && ($timelimit - $exectime) < 2) {
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
						<tr><td class="shade2"><?php print $gm_lang["import_time_exceeded"]; ?></td></tr>
						<tr><td class="topbottombar">
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
			$show_table_marr .= "<td class=\"shade1 indent_rtl rtl\">$names_added<script type=\"text/javascript\">update_progress($i, $exectime);</script></td>";
			$show_table_marr .= "<td class=\"shade1\">&nbsp;INDI&nbsp;</td></tr>\n";
			$show_table_marr .= "</table>\n";
			$stage=10;
			$record_count=0;
			flush();
		}
		// TODO: Layout for Hebrew
		$show_table1 = "<table class=\"list_table\"><tr>";
		$show_table1 .= "<tr><td class=\"topbottombar\" colspan=\"4\">".$gm_lang["ged_import"]."</td></tr>";
		$show_table1 .= "<td class=\"shade2\">&nbsp;".$gm_lang["exec_time"]."&nbsp;</td>";
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
		$show_table1 .= "<td class=\"shade1 indent_rtl rtl \">$TOTAL_BYTES<script type=\"text/javascript\">update_progress($TOTAL_BYTES, $exectime);</script></td>\n";
		$show_table1 .= "<td class=\"shade1 indent_rtl rtl \">".($i-1)."</td>";
		$show_table1 .= "<td class=\"shade1\">&nbsp;</td></tr>\n";
		$show_table1 .= "</table>\n";
		print "<tr><td class=\"topbottombar $TEXT_DIRECTION\">";
		print $gm_lang["import_statistics"];
		print "</td></tr>";
		print "<tr><td class=\"shade1\">";
		print "<table cellspacing=\"20px\"><tr><td class=\"shade1\" style=\"vertical-align: top;\">";
		if (isset($skip_table)) print "<br />...";
		else {
			print $show_table1;
			if ($marr_names == "yes") print "</td><td class=\"shade1\">".$show_table_marr;
		}
		print "</td></tr></table>\n";
		// NOTE: Finished Links
		cleanup_database();
		print "</td></tr>";
		
		$record_count=0;
		$_SESSION["resumed"] = 0;
		unset($_SESSION["place_count"]);
		unset($_SESSION["date_count"]);
		unset($_SESSION["TOTAL_BYTES"]);
		unset($_SESSION["fcontents"]);
		unset($_SESSION["listtype"]);
		unset($_SESSION["exectime_start"]);
		unset($_SESSION["i"]);
		@set_time_limit($TIME_LIMIT);
	}
}
?>
<tr><td class="center" colspan="2">
<?php
	if ($startimport != "true") print "<input type=\"submit\" name=\"continue\" value=\"".$gm_lang["del_proceed"]."\" />&nbsp;";
		if ($cleanup_needed && $skip_cleanup != $gm_lang["skip_cleanup"]) {
			print_help_link("skip_cleanup_help", "qm", "skip_cleanup");
			print "<input type=\"submit\" name=\"skip_cleanup\" value=\"".$gm_lang["skip_cleanup"]."\" />&nbsp;\n";
		}
		if ($verify && $startimport != "true") print "<input type=\"button\" name=\"cancel\" value=\"".$gm_lang["cancel"]."\" onclick=\"document.configform.override.value='no'; document.configform.no_upload.value='cancel_upload'; document.configform.submit(); \" />";
	?>
</td></tr>
</table></form>
<?php

print_footer();
?>