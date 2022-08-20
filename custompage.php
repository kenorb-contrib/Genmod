<?php
/**
 * Build your own webpages
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
 * @version $Id: custompage.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
 
require "config.php";

$custompage = new CustomPageController();

PrintHeader($custompage->pagetitle);

print "<div id=\"ContentCustomPage\">";
if ($custompage->action == "edit") {
	if ($custompage->task != GM_LANG_edit) {
		print "<table class=\"NavBlockTable CustomPageNavTable\">";
		print "<tr><td class=\"NavBlockHeader\" colspan=\"2\">".GM_LANG_my_pages."</td></tr>";
		print "<tr><td class=\"NavBlockColumnHeader CustomPageTableHeader\">".GM_LANG_options."</td><td class=\"NavBlockColumnHeader CustomPageTableHeader\">".GM_LANG_title."</td></tr>";
		print "<tr><td class=\"NavBlockLabel CustomPageTableOptionLabel\"><a href=\"custompage.php?action=".$custompage->action."&amp;task=".GM_LANG_edit."&amp;page_id=newpage\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["edit"]["button"]."\" alt=\"".GM_LANG_add."\" title=\"".GM_LANG_add."\" /></a>";
		print "&nbsp;</td>";
		print "<td class=\"NavBlockField\">".GM_LANG_new."</td></tr>";
		foreach ($custompage->pages as $ct => $page) {
			print "<tr><td class=\"NavBlockLabel CustomPageTableOptionLabel\"><a href=\"custompage.php?action=".$custompage->action."&amp;task=".GM_LANG_edit."&amp;page_id=".$page->id."\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["edit"]["button"]."\" alt=\"".GM_LANG_edit."\" title=\"".GM_LANG_edit."\" /></a>&nbsp;";
			print "<a href=\"custompage.php?action=".$custompage->action."&amp;task=".GM_LANG_delete."&amp;page_id=".$page->id."\" onclick=\"return confirm('".GM_LANG_confirm_page_delete."'); \"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["delete"]["button"]."\" alt=\"".GM_LANG_delete."\" title=\"".GM_LANG_delete."\" /></a></td>";
			print "<td class=\"NavBlockField\">".$page->title."</td></tr>";
		}
		print "</table>";
	}
	else {
		/**
		 * Inclusion of the CK Editor
		*/
		$useCK = file_exists("modules/CKEditor/ckeditor.php");
		if($useCK){
			include("modules/CKEditor/ckeditor.php");
		}
		print "<form name=\"htmlpage\" method=\"post\" action=\"custompage.php\">";
		print "<input type=\"hidden\" name=\"action\" value=\"".$custompage->action."\" />";
	
		if ($custompage->page_id == "newpage") print "<input type=\"hidden\" name=\"page_id\" value=\"newpage\" />";
		else print "<input type=\"hidden\" name=\"page_id\" value=\"".$custompage->page_id."\" />";
		print "<table class=\"NavBlockTable CustomPageNavTable\">";
		print "<tr><td colspan=\"2\" class=\"NavBlockHeader\">".($custompage->page_id == "newpage" ? GM_LANG_custom_page_add : GM_LANG_custom_page_edit)."</td></tr>";
		print "<tr><td class=\"NavBlockLabel CustomPageTitleLabel\">".GM_LANG_title."</td><td class=\"NavBlockField\"><input type=\"text\" name=\"title\" value=\"";
		if ($custompage->page_id != "newpage") print $custompage->page->title;
		print "\" /></td></tr>";
		print "<tr><td class=\"NavBlockLabel\">".GM_LANG_content."</td><td class=\"NavBlockField\">";
		if ($useCK) { // use CKeditor module
			
			?><script type="text/javascript" src="modules/CKEditor/ckeditor.js"></script><?php
			$oCKEditor = new CKEditor();
			$oCKEditor->BasePath = "modules/CKEditor/";
			$oCKEditor->config["height"] = 400;
			$oCKEditor->config["enterMode"] = "br";
			$oCKEditor->config["ShiftEnterMode"] = "p";
			$oCKeditor->config["language"] = $language_settings[$LANGUAGE]["lang_short_cut"];
			$oCKEditor->editor("html", ($custompage->page_id != "newpage" ? $custompage->page->content : ""));
		} 
		else { //use standard textarea
			print "<textarea name=\"html\" rows=\"15\" cols=\"80\">";
			if ($custompage->page_id != "newpage") print $custompage->page->content;
			print "</textarea>";
		}
		print "</td></tr>";
		print "<tr><td colspan=\"2\" class=\"NavBlockFooter\">";
		print "<input type=\"submit\" name=\"task\" value=\"".GM_LANG_save."\" />";
		print "<input type=\"submit\" name=\"task\" value=\"".GM_LANG_cancel."\" />";
		print "</td></tr></table>";
		print "</form>";
	}
}
else {
	print html_entity_decode($custompage->page->content);
}
print "<div class=\"Error\">".$custompage->message."</div>";
print "</div>";
PrintFooter();
?>
