<?php
/**
 * Family Tree Clippings Cart
 *
 * Uses the $_SESSION["cart"] to store the ids of clippings to download
 * @TODO print a message if people are not included due to privacy
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
 * @subpackage Charts
 * @version $Id$
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

$clippings_controller = new ClippingsController();

// -- print html header information
PrintHeader($clippings_controller->pagetitle);
print "\r\n\t<h3>".$clippings_controller->title."</h3>";

if ($clippings_controller->action == 'add') {
	if ($clippings_controller->type == 'fam') {
		print "\r\n<form action=\"clippings.php\" method=\"get\">\r\n".GM_LANG_which_links."<br />";
		print "\r\n\t<input type=\"hidden\" name=\"id\" value=\"".$clippings_controller->id."\" />";
		print "\r\n\t<input type=\"hidden\" name=\"type\" value=\"".$clippings_controller->type."\" />";
		print "\r\n\t<input type=\"hidden\" name=\"action\" value=\"add1\" />";
		print "\r\n\t<input type=\"radio\" name=\"others\" value=\"none\" />".GM_LANG_just_family."<br />";
		print "\r\n\t<input type=\"radio\" name=\"others\" value=\"parents\" />".GM_LANG_parents_and_family."<br />";
		print "\r\n\t<input type=\"radio\" name=\"others\" selected value=\"members\" />".GM_LANG_parents_and_child."<br />";
		print "\r\n\t<input type=\"radio\" name=\"others\" value=\"descendants\" />".GM_LANG_parents_desc."<br /><br />";
		print "\r\n\t<input type=\"submit\"  value=\"".GM_LANG_continue."\" /><br />\r\n\t</form>";
	}
	else if ($clippings_controller->type == 'indi') {
		print "\r\n<form action=\"clippings.php\" method=\"get\">\r\n".GM_LANG_which_p_links."<br />";
		print "\r\n\t<input type=\"hidden\" name=\"id\" value=\"".$clippings_controller->id."\" />";
		print "\r\n\t<input type=\"hidden\" name=\"type\" value=\"".$clippings_controller->type."\" />";
		print "\r\n\t<input type=\"hidden\" name=\"action\" value=\"add1\" />";
		print "\r\n\t<input type=\"radio\" name=\"others\" value=\"none\" />".GM_LANG_just_person."<br />";
		print "\r\n\t<input type=\"radio\" name=\"others\" value=\"parents\" />".GM_LANG_person_parents_sibs."<br />";
		print "\r\n\t<input type=\"radio\" name=\"others\" value=\"ancestors\" />".GM_LANG_person_ancestors."<br />";
		print "\r\n\t<input type=\"radio\" name=\"others\" value=\"ancestorsfamilies\" />".GM_LANG_person_ancestor_fams."<br />";
		print "\r\n\t<input type=\"radio\" name=\"others\" selected value=\"members\" />".GM_LANG_person_spouse."<br />";
		print "\r\n\t<input type=\"radio\" name=\"others\" value=\"descendants\" />".GM_LANG_person_desc."<br /><br />";
		print "\r\n\t<input type=\"submit\"  value=\"".GM_LANG_continue."\" /><br />\r\n\t</form>";
	}
	else $clippings_controller->action = 'add1';
}

$clippings_controller->PerformAction();

if($clippings_controller->action == 'download') {
	print "\r\n\t<br /><br />".GM_LANG_download."<br /><br />".GM_LANG_gedcom_file."<ul><li><a href=\"clippings_download.php\">clipping.ged</a></li></ul><br />";
	if ($clippings_controller->mediacount > 0) {
		// -- create zipped media file====> is a todo
		print GM_LANG_media_files."<ul>";
		for($m=0; $m < $clippings_controller->mediacount; $m++) {
			print "<li><a href=\"".GedcomConfig::$MEDIA_DIRECTORY.$clippings_controller->media[$m]."\">".substr($clippings_controller->media[$m], strrpos($clippings_controller->media[$m], "/"))."</a></li>";
		}
		print "</ul>";
	}
	print "<br /><br />";
}
if (!isset($clippings_controller->cart[$GEDCOMID]) || count($clippings_controller->cart[$GEDCOMID]) == 0) {

	// NOTE: display helptext when cart is empty
	if ($clippings_controller->action != 'add') PrintText("help_clippings.php");
	
	// -- end new lines
	print "\r\n\t\t<br /><br />".GM_LANG_cart_is_empty."<br /><br />";
}
else {
	$ct = count($clippings_controller->cart[$GEDCOMID]);
	print "\r\n\t<table class=\"list_table\">\r\n\t\t<tr>\r\n\t\t\t<td class=\"list_label\">".GM_LANG_type."</td><td class=\"list_label\">".GM_LANG_id."</td><td class=\"list_label\">".GM_LANG_name_description."</td><td class=\"list_label\">".GM_LANG_remove."</td>\r\n\t\t</tr>";
	for($i=0; $i<$ct; $i++) {
		print "\r\n\t\t<tr>\r\n\t\t<td class=\"list_value\">";
		$clipping = $clippings_controller->cart[$GEDCOMID][$i];
		if($clipping['type']=='indi') print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]."\" border=\"0\" alt=\"".GM_LANG_individual."\" />";
		else if($clipping['type']=='fam') print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sfamily"]["small"]."\" border=\"0\" alt=\"".GM_LANG_family."\" />";
		else if($clipping['type']=='sour') print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["source"]["small"]."\" border=\"0\" alt=\"".GM_LANG_source."\" />";
		else if($clipping['type']=='repo') print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["repository"]["small"]."\" border=\"0\" alt=\"".GM_LANG_repo."\" />";
		else if($clipping['type']=='obje') print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["media"]["small"]."\" border=\"0\" alt=\"".GM_LANG_media."\" />";
		else if($clipping['type']=='note') print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["note"]["other"]."\" border=\"0\" alt=\"".GM_LANG_note."\" />";
		print "</td><td class=\"list_value\">".$clipping['id']."</td><td class=\"list_value\">";

		$id_ok = true;
		$object =& ConstructObject($clipping['id'], $clipping['type']);
		//print "Constructed: ".$object->datatype." ".$clipping['type']." ".$clipping['id']."<br />";
		if ($clipping['type'] == 'indi') {
			$object->PrintListPerson(false);
		}
		else if($clipping['type'] == 'fam') {
			$object->PrintListFamily(false);
		} 
		else if($clipping['type'] == 'sour') {
			$object->PrintListSource(false);
		}
		else if($clipping['type'] == 'note') {
			$object->PrintListNote(60, false);
	   	}
	  	else if($clipping['type'] == 'repo') {
		  	$object->PrintListRepository(false);
		}
		else if($clipping['type'] == 'obje') {
			$object->PrintListMedia(false);
		}
		print "</td><td class=\"list_value\"><a href=\"clippings.php?action=remove&amp;item=$i\">".GM_LANG_remove."</a></td>\r\n\t\t</tr>";
	}
	print "\r\n\t</table>";
	if ($clippings_controller->action != 'download') {
		print "<form method=\"post\" action=\"clippings.php\">\n<input type=\"hidden\" name=\"action\" value=\"download\" />\n";
		?>
		<table>
		<tr><td><input type="checkbox" name="convert" value="yes" /></td><td><?php print GM_LANG_utf8_to_ansi; PrintHelpLink("utf8_ansi_help", "qm"); ?></td></tr>
		<tr><td><input type="checkbox" name="remove" value="yes" checked="checked" /></td><td><?php print GM_LANG_remove_custom_tags; PrintHelpLink("remove_tags_help", "qm"); ?></td></tr>
		</table>
		<input type="submit"  value="<?php print GM_LANG_download_now; ?>" />
		<?php
		PrintHelpLink("clip_download_help", "qm");
		print "<br /></form>";
	}
	print "\r\n\t<br /><a href=\"clippings.php?action=empty\">".GM_LANG_empty_cart."  "."</a>";
	PrintHelpLink("empty_cart_help", "qm");
}
if (isset($_SESSION["cart"])) $_SESSION["cart"] = $clippings_controller->cart;
PrintFooter();
?>