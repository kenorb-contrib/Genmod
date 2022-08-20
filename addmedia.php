<?php
/**
 * Add media to gedcom file
 *
 * This file allows the user to maintain a seperate table
 * of media files and associate them with individuals in the gedcom
 * and then add these records later.
 * Requires SQL mode.
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
 * @subpackage MediaDB
 * @version $Id: addmedia.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

PrintSimpleHeader(GM_LANG_add_media_tool);

//-- only allow users with edit privileges to access script.
if (!$gm_user->userGedcomAdmin()) {
	print GM_LANG_access_denied;
	PrintSimpleFooter();
	exit;
}

if ($_SESSION["cookie_login"]) {
	if (LOGIN_URL == "") header("Location: login.php?gedid=".GedcomConfig::$GEDCOMID."&url=addmedia.php");
	else header("Location: ".LOGIN_URL."?gedid=".GedcomConfig::$GEDCOMID."&url=addmedia.php");
	exit;
}

?>
<script language="JavaScript" type="text/javascript">
<!--
	var language_filter, magnify;
	var pastefield;
	language_filter = "";
	magnify = "";
	function openerpasteid(id) {
		window.opener.paste_id(id);
		window.close();
	}
	
	function paste_id(value) {
		pastefield.value = value;
	}
	
	function paste_char(value,lang,mag) {
		pastefield.value += value;
		language_filter = lang;
		magnify = mag;
	}
//-->
</script>

<?php
if (empty($action)) $action="showmediaform";
if (!isset($text)) $text = array();
if (!isset($title)) $title = "";

if (!isset($m_ext)) $m_ext="";
if (!isset($m_titl)) $m_titl="";
if (!isset($m_mfile)) $m_mfile="";
if (!isset($paste)) $paste = false;
$can_auto_accept = true;
if (!isset($aa_attempt)) $aa_attempt = false;

// NOTE: Store the entered data
// NOTE: Move this to edit_interface.php
if ($action=="newentry") {
	
	// NOTE: Get a change id
	$change_id = EditFunctions::GetNewXref("CHANGE");
	
	// NOTE: Setting the pid
	if (isset($gid)) $pid = $gid;
	
	// NOTE: Check for file upload
	$result = MediaFS::UploadFiles($_FILES, $folder);
	
	if ($result["errno"] != 0) {
		print "<span class=\"Error\">".GM_LANG_upload_error."<br />".$result["error"]."</span><br />";
	}
	else {
		$filename = $result["filename"];
		// Based on the gedcom settings, we first check if double media is allowed and exists
		if (isset($filename) && !empty($filename) && $filename != "1") {
			$m = RelativePathFile(GedcomConfig::$MEDIA_DIRECTORY);
			if (!empty($m)) $filename = preg_replace("~$m~", "", $filename);
		}
//print "filename:".$filename;
		// Fix the FILE record: if a file is uploaded the 1 FILE is still empty
		$j = count($text)-1;
		for($i = 0; $i<= $j; $i++) {
			if ($tag[$i] == "FILE") {
				if (!isset($text[$i]) || strlen($text[$i]) == 0) $text[$i] = $filename;
				$_POST["text"][$i] = $filename;
//print_r($_POST);
				$newfile = $text[$i];
//print " newfile:".$newfile;
			}
			if ($tag[$i] == "FORM") {
				if (!isset($text[$i]) || $text[$i] == "") $text[$i] = pathinfo($filename, PATHINFO_EXTENSION);
			}
			if ($tag[$i] == "TITL") {
				$title = $text[$i];
			}
		}
		$dm = EditFunctions::CheckDoubleMedia($newfile, $title, GedcomConfig::$GEDCOMID);
		if (!$dm) {
			// NOTE: Build the gedcom record
			// NOTE: Level 0
			$media_id = EditFunctions::GetNewXref("OBJE");
			$newged = "0 @".$media_id."@ OBJE\r\n";
			
			$newged = EditFunctions::HandleUpdates($newged);
			$xref = EditFunctions::AppendGedrec($newged, "OBJE", $change_id, $change_type);
		
			if ($can_auto_accept && (($gm_user->UserCanAccept() && $aa_attempt) || $gm_user->userAutoAccept())) {
				ChangeFunctions::AcceptChange($change_id, GedcomConfig::$GEDCOMID);
			}
			
			print GM_LANG_update_successful;
		}
		else {
			$xref = $dm;
			print "<br /><br /><span class=\"Error\">".GM_LANG_no_double_media."</span>";
		}
		if ($paste) {
			if (GedcomConfig::$EDIT_AUTOCLOSE) print "\n<script type=\"text/javascript\">\n<!--\nopenerpasteid('$xref');\n//-->\n</script>";
			else print "<br /><br /><a href=\"javascript:// OBJE $xref\" onclick=\"openerpasteid('$xref'); return false;\">".GM_LANG_paste_mm_id_into_field." <b>$xref</b></a>\n";
		}
		if (GedcomConfig::$EDIT_AUTOCLOSE) print "\n<script type=\"text/javascript\">\n<!--\nwindow.close();\n//-->\n</script>";
	}
}

print "<br />";
print "<div class=\"CloseWindow\"><a href=\"#\" onclick=\"if (window.opener.showchanges) window.opener.showchanges(); window.close();\">".GM_LANG_close_window."</a></div>\n";
print "<br />";
PrintSimpleFooter();
?>