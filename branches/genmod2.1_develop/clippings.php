<?php
/**
 * Family Tree Clippings Cart
 *
 * Uses the $_SESSION["cart"] to store the ids of clippings to download
 * @TODO print a message if people are not included due to privacy
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
 * @subpackage Charts
 * @version $Id: clippings.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

$clippings_controller = new ClippingsController();

// -- print html header information
PrintHeader($clippings_controller->pagetitle);
print "\r\n\t<div class=\"PageTitleName ClippingsPageTitle\">".$clippings_controller->title."</div>";
if ($clippings_controller->action == 'add') {
	if ($clippings_controller->type == 'fam') {
		print "\r\n<form action=\"clippings.php\" method=\"get\">\r\n";
		print "\r\n\t<input type=\"hidden\" name=\"id\" value=\"".$clippings_controller->id."\" />";
		print "\r\n\t<input type=\"hidden\" name=\"type\" value=\"".$clippings_controller->type."\" />";
		print "\r\n\t<input type=\"hidden\" name=\"action\" value=\"add1\" />";
		print "<table class=\"NavBlockTable\">";
		print "<tr><td class=\"NavBlockHeader\" colspan=\"2\">".GM_LANG_which_links."</td></tr>";
		print "<tr><td class=\"NavBlockLabel\">".GM_LANG_just_family."</td><td class=\"NavBlockField\"><input type=\"radio\" name=\"others\" value=\"none\" /></td></tr>";
		print "<tr><td class=\"NavBlockLabel\">".GM_LANG_parents_and_family."</td><td class=\"NavBlockField\"><input type=\"radio\" name=\"others\" value=\"parents\" /></td></tr>";
		print "<tr><td class=\"NavBlockLabel\">".GM_LANG_parents_and_child."</td><td class=\"NavBlockField\"><input type=\"radio\" name=\"others\" selected value=\"members\" /></td></tr>";
		print "<tr><td class=\"NavBlockLabel\">".GM_LANG_parents_desc."</td><td class=\"NavBlockField\"><input type=\"radio\" name=\"others\" value=\"descendants\" /></td></tr>";
		print "<tr><td class=\"NavBlockFooter\" colspan=\"2\"><input type=\"submit\"  value=\"".GM_LANG_continue."\" /></td></tr>";
		print "</table></form><br /><br />";
	}
	else if ($clippings_controller->type == 'indi') {
		print "\r\n<form action=\"clippings.php\" method=\"get\">\r\n";
		print "\r\n\t<input type=\"hidden\" name=\"id\" value=\"".$clippings_controller->id."\" />";
		print "\r\n\t<input type=\"hidden\" name=\"type\" value=\"".$clippings_controller->type."\" />";
		print "\r\n\t<input type=\"hidden\" name=\"action\" value=\"add1\" />";
		print "<table class=\"NavBlockTable\">";
		print "<tr><td class=\"NavBlockHeader\" colspan=\"2\">".GM_LANG_which_p_links."</td></tr>";
		print "<tr><td class=\"NavBlockLabel\">".GM_LANG_just_person."</td><td class=\"NavBlockField\"><input type=\"radio\" name=\"others\" value=\"none\" /></td></tr>";
		print "<tr><td class=\"NavBlockLabel\">".GM_LANG_person_parents_sibs."</td><td class=\"NavBlockField\"><input type=\"radio\" name=\"others\" value=\"parents\" /></td></tr>";
		print "<tr><td class=\"NavBlockLabel\">".GM_LANG_person_ancestors."</td><td class=\"NavBlockField\"><input type=\"radio\" name=\"others\" value=\"ancestors\" /></td></tr>";
		print "<tr><td class=\"NavBlockLabel\">".GM_LANG_person_ancestor_fams."</td><td class=\"NavBlockField\"><input type=\"radio\" name=\"others\" value=\"ancestorsfamilies\" /></td></tr>";
		print "<tr><td class=\"NavBlockLabel\">".GM_LANG_person_spouse."</td><td class=\"NavBlockField\"><input type=\"radio\" name=\"others\" selected value=\"members\" /></td></tr>";
		print "<tr><td class=\"NavBlockLabel\">".GM_LANG_person_desc."</td><td class=\"NavBlockField\"><input type=\"radio\" name=\"others\" value=\"descendants\" /></td></tr>";
		print "<tr><td class=\"NavBlockFooter\" colspan=\"2\"><input type=\"submit\"  value=\"".GM_LANG_continue."\" /></td></tr>";
		print "</table></form><br /><br />";
	}
	else $clippings_controller->action = 'add1';
}

$clippings_controller->PerformAction();

if($clippings_controller->action == 'download') {
	print "<div class=\"ClippingsDownloadTable\">";
	print "<table class=\"NavBlockTable\">";
	print "<tr><td class=\"NavBlockHeader\" colspan=\"2\">".GM_LANG_clippings_download."</td></tr>";
	print "<tr><td class=\"NavBlockLabel\" colspan=\"2\">".GM_LANG_download."</td></tr>";
	print "<tr><td class=\"NavBlockLabel\">".GM_LANG_gedcom_file."</td><td class=\"NavBlockField\"><a href=\"clippings_download.php\">clipping.ged</a></td></tr>";
	if ($clippings_controller->mediacount > 0) {
		// -- create zipped media file====> is a todo
		print "<tr><td class=\"NavBlockLabel\" rowspan=\"".$clippings_controller->mediacount."\">".GM_LANG_media_files."</td>";
		for($m=0; $m < $clippings_controller->mediacount; $m++) {
			$fileobj = new MFile(trim(GedcomConfig::$MEDIA_DIRECTORY.$clippings_controller->media[$m]));
			print "<td class=\"NavBlockField\">";
			print "<a href=\"".SERVER_URL.$fileobj->f_main_file."\">".substr($clippings_controller->media[$m], strrpos($clippings_controller->media[$m], "/")+1)."</a></td>";
			if ($m <> $clippings_controller->mediacount-1) print "</tr><tr>";
		}
		print "</tr>";
	}
	print "</table></div>";
}
if (!isset($clippings_controller->cart[GedcomConfig::$GEDCOMID]) || count($clippings_controller->cart[GedcomConfig::$GEDCOMID]) == 0) {
	print "<div class=\"ClippingsMessage\">";
	// NOTE: display helptext when cart is empty
	if ($clippings_controller->action != 'add') {
		PrintText("help_clippings.php");
		print "<br /><br />";
	}
	
	// -- end new lines
	print "\r\n\t\t".GM_LANG_cart_is_empty."";
	print "</div>";
}
else {
	if ($clippings_controller->action != 'download') {
		print "<form method=\"post\" action=\"clippings.php\">\n<input type=\"hidden\" name=\"action\" value=\"download\" />\n";
		?>
		<table class="NavBlockTable">
		<tr><td class="NavBlockHeader" colspan="2"><?php print GM_LANG_choose; ?></td></tr>
		<tr><td class="NavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("utf8_ansi_help", "qm"); print "</div>".GM_LANG_utf8_to_ansi;?></td><td class="NavBlockField"><input type="checkbox" name="convert" value="yes" /></td></tr>
		<tr><td class="NavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("remove_tags_help", "qm"); print "</div>".GM_LANG_remove_custom_tags; ?></td><td class="NavBlockField"><input type="checkbox" name="remove" value="yes" checked="checked" /></td></tr>
		<tr><td class="NavBlockFooter" colspan="2"><input type="submit"  value="<?php print GM_LANG_download_now; ?>" />
		<?php
		PrintHelpLink("clip_download_help", "qm"); ?>
		<input type="button" value="<?php print GM_LANG_empty_cart; ?>" onclick="this.form.action.value='empty'; this.form.submit();" />
		<?php 
		PrintHelpLink("empty_cart_help", "qm");
		print "</td></tr></table></form><br /><br />";
	}		
	$ct = count($clippings_controller->cart[GedcomConfig::$GEDCOMID]);
	print "\r\n\t<table class=\"ListTable\">\r\n\t\t<tr><td class=\"ListTableHeader\" colspan=\"4\">".GM_LANG_clip_cart."</td></tr>\r\n\t\t<tr>\r\n\t\t\t<td class=\"ListTableColumnHeader\">".GM_LANG_type."</td><td class=\"ListTableColumnHeader\">".GM_LANG_id."</td><td class=\"ListTableColumnHeader\">".GM_LANG_name_description."</td><td class=\"ListTableColumnHeader\">".GM_LANG_remove."</td>\r\n\t\t</tr>";
	for($i=0; $i<$ct; $i++) {
		print "\r\n\t\t<tr>\r\n\t\t<td class=\"ListTableContent\">";
		$clipping = $clippings_controller->cart[GedcomConfig::$GEDCOMID][$i];
		if($clipping['type']=='indi') print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]."\" border=\"0\" alt=\"".GM_LANG_individual."\" title=\"".GM_LANG_individual."\" />";
		else if($clipping['type']=='fam') print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sfamily"]["small"]."\" border=\"0\" alt=\"".GM_LANG_family."\" title=\"".GM_LANG_family."\" />";
		else if($clipping['type']=='sour') print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["source"]["small"]."\" border=\"0\" alt=\"".GM_LANG_source."\" title=\"".GM_LANG_source."\" />";
		else if($clipping['type']=='repo') print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["repository"]["small"]."\" border=\"0\" alt=\"".GM_LANG_repo."\" title=\"".GM_LANG_repo."\" />";
		else if($clipping['type']=='obje') print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["media"]["small"]."\" border=\"0\" alt=\"".GM_LANG_media."\" title=\"".GM_LANG_media."\" />";
		else if($clipping['type']=='note') print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["note"]["other"]."\" border=\"0\" alt=\"".GM_LANG_note."\" title=\"".GM_LANG_note."\" />";
		print "</td><td class=\"ListTableContent\">".$clipping['id']."</td><td class=\"ListTableContent\">";

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
		print "</td><td class=\"ListTableContent\"><a href=\"clippings.php?action=remove&amp;item=$i\">".GM_LANG_remove."</a></td>\r\n\t\t</tr>";
	}
	print "\r\n\t</table>";
}
if (isset($_SESSION["cart"])) $_SESSION["cart"] = $clippings_controller->cart;
PrintFooter();
?>