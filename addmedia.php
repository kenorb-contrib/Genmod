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
 * @package Genmod
 * @subpackage MediaDB
 * @version $Id: addmedia.php,v 1.28 2009/02/18 09:16:50 sjouke Exp $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

/**
 * Inclusion of the edit functions
*/
require("includes/functions_edit.php");

if (empty($ged)) $ged = $GEDCOM;
$GEDCOM = $ged;

print_simple_header($gm_lang["add_media_tool"]);

//-- only allow users with edit privileges to access script.
if (!$Users->userGedcomAdmin($gm_username)) {
	print $gm_lang["access_denied"];
	print_simple_footer();
	exit;
}

if ($_SESSION["cookie_login"]) {
	if (empty($LOGIN_URL)) header("Location: login.php?ged=$GEDCOM&url=addmedia.php");
	else header("Location: ".$LOGIN_URL."?ged=$GEDCOM&url=addmedia.php");
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

if (!isset($m_ext)) $m_ext="";
if (!isset($m_titl)) $m_titl="";
if (!isset($m_file)) $m_file="";
if (!isset($paste)) $paste = false;
$can_auto_accept = true;
if (!isset($aa_attempt)) $aa_attempt = false;

// NOTE: Store the entered data
// NOTE: Move this to edit_interface.php
if ($action=="newentry") {
	
	// NOTE: Get a change id
	$change_id = GetNewXref("CHANGE");
	
	// NOTE: Setting the pid
	if (isset($gid)) $pid = $gid;
	
	// NOTE: Check for file upload
	$result = $MediaFS->UploadFiles($_FILES, $folder);
	
	if ($result["errno"] != 0) {
		print "<span class=\"error\">".$gm_lang["upload_error"]."<br />".$result["error"]."</span><br />";
	}
	else {
		$filename = $result["filename"];
		// Based on the gedcom settings, we first check if double media is allowed and exists
		if (isset($filename) && !empty($filename) && $filename != "1") {
			$m = RelativePathFile($MEDIA_DIRECTORY);
			if (!empty($m)) $filename = preg_replace("~$m~", "", $filename);
		}

		// Fix the FILE record: if a file is uploaded the 1 FILE is still empty
		$j = count($text)-1;
		for($i = 0; $i<= $j; $i++) {
			if ($tag[$i] == "FILE") {
				if ($text[$i] == "") $text[$i] = $filename;
				$newfile = $text[$i];
			}
			if ($tag[$i] == "FORM") {
				if ($text[$i] == "") $text[$i] = pathinfo($filename, PATHINFO_EXTENSION);
			}
			if ($tag[$i] == "TITL") {
				$title = $text[$i];
			}
		}
		$dm = CheckDoubleMedia($newfile, $title, $GEDCOMID);
		if (!$dm) {
			// NOTE: Build the gedcom record
			// NOTE: Level 0
			$media_id = GetNewXref("OBJE");
			$newged = "0 @".$media_id."@ OBJE\r\n";
			
			$newged = HandleUpdates($newged);
			$xref = AppendGedrec($newged, "OBJE", $change_id, $change_type);
		
			if ($can_auto_accept && (($Users->UserCanAccept($gm_username) && $aa_attempt) || $Users->userAutoAccept())) {
				AcceptChange($change_id, $GEDCOMID);
			}
			
			print $gm_lang["update_successful"];
		}
		else {
			$xref = $dm;
			print "<br /><br /><span class=\"error\">".$gm_lang["no_double_media"]."</span>";
		}
		if ($paste) {
			if ($EDIT_AUTOCLOSE) print "\n<script type=\"text/javascript\">\n<!--\nopenerpasteid('$xref');\n//-->\n</script>";
			else print "<br /><br /><a href=\"javascript:// OBJE $xref\" onclick=\"openerpasteid('$xref'); return false;\">".$gm_lang["paste_mm_id_into_field"]." <b>$xref</b></a>\n";
		}
		if ($EDIT_AUTOCLOSE) print "\n<script type=\"text/javascript\">\n<!--\nwindow.close();\n//-->\n</script>";
	}
}


if ($action=="delete") {
	remove_db_media($m_id, $m_gedfile);
	$action = "showmedia";
}

if ($action=="showmedia") {
	$medialist = get_db_media_list();
	if (count($medialist)>0) {
		print "<table class=\"list_table\">\n";
		print "<tr><td class=\"list_label\">".$gm_lang["delete"]."</td><td class=\"list_label\">".$gm_lang["title"]."</td><td class=\"list_label\">".$gm_lang["gedcomid"]."</td>\n";
		print "<td class=\"list_label\">".$factarray["FILE"]."</td><td class=\"list_label\">".$gm_lang["highlighted"]."</td><td class=\"list_label\">order</td><td class=\"list_label\">gedcom</td></tr>\n";
		foreach($medialist as $indexval => $media) {
			print "<tr>";
			print "<td class=\"list_value\"><a href=\"addmedia.php?action=delete&m_id=".$media["ID"]."\">delete</a></td>";
			print "<td class=\"list_value\"><a href=\"addmedia.php?action=edit&m_id=".$media["ID"]."\">edit</a></td>";
			print "<td class=\"list_value\">".$media["TITL"]."</td>";
//			print "<td class=\"list_value\">";
//			print_list_person($media["INDI"], array(GetPersonName($media["INDI"]), $GEDCOM));
//			print "</td>";
			print "<td class=\"list_value\">".$media["FILE"]."</td>";
//			print "<td class=\"list_value\">".$media["_PRIM"]."</td>";
//			print "<td class=\"list_value\">".$media["ORDER"]."</td>";
			print "<td class=\"list_value\">".$media["GEDFILE"]."</td>";
			print "</tr>\n";
		}
		print "</table>\n";
	}
}


if ($action=="showmediaform") {
 	if (!isset($pid)) $pid = "";
	ShowMediaForm($pid);
}

print "<br />";
print "<div class=\"center\"><a href=\"#\" onclick=\"if (window.opener.showchanges) window.opener.showchanges(); window.close();\">".$gm_lang["close_window"]."</a></div>\n";
print "<br />";
print_simple_footer();
?>