<?php
/**
 * Build your own webpages
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
 * @package Genmod
 * @version $Id: custompage.php,v 1.1 2006/03/14 21:55:32 roland-d Exp $
 */
require("config.php");

if (!isset($action)) $action = "";
if (!isset($id)) $id = 0;

// print "<div id=\"content
if ($id > 0) {
	// Retrieve the page to be shown
	$sql = "SELECT * FROM ".$TBLPREFIX."pages WHERE `pag_id` = '".$id."'";
	$result = mysql_query($sql);
	if (!$result) {
		$message  = 'Invalid query: ' . mysql_error() . "\n";
		$message .= 'Whole query: ' . $query;
		die($message);
	}
	else {
		$pages = array();
		while ($row = mysql_fetch_assoc($result)) {
			$page = array();
			$page["id"] = $row["pag_id"];
			$page["html"] = $row["pag_content"];
			$page["title"] = $row["pag_title"];
		}
	}
	print_header($page["title"]);
	print html_entity_decode($page["html"]);
}
else if (userIsAdmin(getUserName())) {
	print_header($gm_lang["my_pages"]);
	if ($action == $gm_lang["save"]) {
		if (isset($storenew)) $sql = "INSERT INTO ".$TBLPREFIX."pages (`pag_content`, `pag_title`) VALUES ('".mysql_real_escape_string($html)."', '".mysql_real_escape_string($title)."')";
		else $sql = "UPDATE ".$TBLPREFIX."pages SET `pag_content` = '".mysql_real_escape_string($html)."', `pag_title` = '".mysql_real_escape_string($title)."' WHERE `pag_id` = '".$pageid."'";
		$result = mysql_query($sql);
		if (!$result) {
		   $message  = 'Invalid query: ' . mysql_error() . "\n";
		   $message .= 'Whole query: ' . $query;
		   die($message);
		}
	}
	if ($action == $gm_lang["delete"]) {
		$sql = "DELETE FROM ".$TBLPREFIX."pages WHERE `pag_id` = '".$page_id."'";
		$result = mysql_query($sql);
		if (!$result) {
		   $message  = 'Invalid query: ' . mysql_error() . "\n";
		   $message .= 'Whole query: ' . $query;
		   die($message);
		}
		// TODO: Add appropiate text
		else print $gm_lang["gedrec_deleted"];
	}
	// Retrieve the current pages stored in the DB
	$sql = "SELECT * FROM ".$TBLPREFIX."pages";
	$result = mysql_query($sql);
	if (!$result) {
		$message  = 'Invalid query: ' . mysql_error() . "\n";
		$message .= 'Whole query: ' . $query;
		die($message);
	}
	else {
		$pages = array();
		while ($row = mysql_fetch_assoc($result)) {
			$page = array();
			$page["id"] = $row["pag_id"];
			$page["html"] = $row["pag_content"];
			$page["title"] = $row["pag_title"];
			$pages[$row["pag_id"]] = $page;
		}
	}
	print "<div id=\"content\">";
	print "<div id=\"mainpage\">";
	print "<div class=\"topbottombar\">".$gm_lang["my_pages"]."</div>";
	if ($action == $gm_lang["edit"]) {
		?>
		<form name="htmlpage" method="post" action="custompage.php">
		<?php
		if ($page_id == "newpage") print "<input type=\"hidden\" name=\"storenew\" value=\"newpage\">";
		else print "<input type=\"hidden\" name=\"pageid\" value=\"".$page_id."\">";
		print $gm_lang["title"].":<br /><input type=\"text\" name=\"title\" value=\"";
		if ($page_id != "newpage") print $pages[$page_id]["title"]; 
		print "\" /><br />";
		print $gm_lang["content"].":<br />";
		print "<textarea name=\"html\" rows=\"15\" cols=\"110\">";
		if ($page_id != "newpage") print $pages[$page_id]["html"];
		?></textarea>
		<br />
		<input type="submit" name="action" value="<?php print $gm_lang["save"]; ?>">
		<input type="submit" name="action" value="<?php print $gm_lang["cancel"]; ?>">
		</form>
		<?
	}
	else {
		// Form with pages to edit
		// TODO: Add appropiate text
		print "<table class=\"width95\">";
		print "<tr class=\"shade3\"><td class=\"width10\">".$gm_lang["options"]."</td><td>".$gm_lang["title"]."</td></tr>";
		print "<tr><td class=\"shade2\"><a style=\"text-decoration: none;\" href=\"custompage.php?action=".$gm_lang["edit"]."&amp;page_id=newpage\"><img class=\"noborder\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["edit"]["button"]."\" alt=\"".$gm_lang["edit"]."\"/></a>";
		print "&nbsp;</td>";
		print "<td class=\"shade1\">".$gm_lang["new"]."</td></tr>";
		foreach ($pages as $ct => $page) {
			print "<tr><td class=\"shade2\"><a style=\"text-decoration: none;\" href=\"custompage.php?action=".$gm_lang["edit"]."&amp;page_id=".$page["id"]."\"><img class=\"noborder\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["edit"]["button"]."\" alt=\"".$gm_lang["edit"]."\"/></a>&nbsp;";
			print "<a href=\"custompage.php?action=".$gm_lang["delete"]."&amp;page_id=".$page["id"]."\" onclick=\"return confirm('".$gm_lang["confirm_gedcom_delete"]."?');\"><img class=\"noborder\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["delete"]["button"]."\" alt=\"".$gm_lang["delete"]."\"/></a></td>";
			print "<td class=\"shade1\">".$page["title"]."</td></tr>";
		}
		print "</table>";
		
	}
	print "</div>";
}
else {
	print_header($gm_lang["edit"]);
	print $gm_lang["access_denied"];
}
print_footer();
?>
