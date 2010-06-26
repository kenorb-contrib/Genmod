<?php
/**
 * Build your own webpages
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
 * @version $Id$
 */
 
require "config.php";

$custompage = new CustomPageController();

PrintHeader($custompage->pagetitle);

if ($custompage->action == "edit") {
	print "<div id=\"content\">";
		print "<div id=\"mainpage\">";
			print "<div class=\"topbottombar\">".GM_LANG_my_pages."</div>";
			if ($custompage->task != GM_LANG_edit) {
				print "<table class=\"width100\">";
				print "<tr class=\"shade3\"><td class=\"width10\">".GM_LANG_options."</td><td>".GM_LANG_title."</td></tr>";
				print "<tr><td class=\"shade2\"><a style=\"text-decoration: none;\" href=\"custompage.php?action=".$custompage->action."&amp;task=".GM_LANG_edit."&amp;page_id=newpage\"><img class=\"noborder\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["edit"]["button"]."\" alt=\"".GM_LANG_edit."\" /></a>";
				print "&nbsp;</td>";
				print "<td class=\"shade1\">".GM_LANG_new."</td></tr>";
				foreach ($custompage->pages as $ct => $page) {
					print "<tr><td class=\"shade2\"><a style=\"text-decoration: none;\" href=\"custompage.php?action=".$custompage->action."&amp;task=".GM_LANG_edit."&amp;page_id=".$page->id."\"><img class=\"noborder\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["edit"]["button"]."\" alt=\"".GM_LANG_edit."\" /></a>&nbsp;";
					print "<a href=\"custompage.php?action=".$custompage->action."&amp;task=".GM_LANG_delete."&amp;page_id=".$page->id."\" onclick=\"return confirm('".GM_LANG_confirm_page_delete."'); \"><img class=\"noborder\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["delete"]["button"]."\" alt=\"".GM_LANG_delete."\" /></a></td>";
					print "<td class=\"shade1\">".$page->title."</td></tr>"';
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
				print "<input type=\"hidden\" name=\"action\" value=\"".$custompage->action."\">";
			
				if ($custompage->page_id == "newpage") print "<input type=\"hidden\" name=\"page_id\" value=\"newpage\">";
				else print "<input type=\"hidden\" name=\"page_id\" value=\"".$custompage->page_id."\">";
				
				print GM_LANG_title.":<br /><input type=\"text\" name=\"title\" value=\"";
				if ($custompage->page_id != "newpage") print $custompage->page->title;
				print "\" /><br />";
				
				print GM_LANG_content.":<br />";
				if ($useCK) { // use CKeditor module
					
					?><script type="text/javascript" src="modules/CKEditor/ckeditor.js"></script><?php
					$oCKEditor = new CKEditor();
					$oCKEditor->BasePath = "modules/CKEditor/";
					$oCKEditor->config["height"] = 450;
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
				print "<br />";
				print "<input type=\"submit\" name=\"task\" value=\"".GM_LANG_save."\">";
				print "<input type=\"submit\" name=\"task\" value=\"".GM_LANG_cancel."\">";
				print "</form>";
			}
			print "</div>";
		print "</div>";
	print "</div>";
}
else {
	print html_entity_decode($custompage->page->content);
}
print "<div class=\"error center\">".$custompage->message."</div>";
PrintFooter();
?>
