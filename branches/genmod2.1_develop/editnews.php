<?php
/**
 * Popup window for Editing news items
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
 * @version $Id: editnews.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

$username = $gm_username;
if (empty($username)) {
	PrintSimpleHeader("");
	print GM_LANG_access_denied;
	PrintSimpleFooter();
	exit;
}

/**
 * Inclusion of the CK Editor
*/
$useCK = file_exists("modules/CKEditor/ckeditor.php");
if($useCK){
	include("modules/CKEditor/ckeditor.php");
}

if (!isset($action)) $action="compose";

PrintSimpleHeader(GM_LANG_edit_news);

if (empty($uname)) $uname = get_gedcom_from_id(GedcomConfig::$GEDCOMID);

if ($action=="compose") {
	?>
	<script language="JavaScript" type="text/javascript">
	<!--
		function checkForm(frm) {
			if (frm.title.value=="") {
				alert('<?php print GM_LANG_enter_title; ?>');
				document.messageform.title.focus();
				return false;
			}
			<?php if (! $useCK) { //will be empty for FCK. FIXME, use FCK API to check for content.
			?>
			if (frm.text.value=="") {
				alert('<?php print GM_LANG_enter_text; ?>');
				document.messageform.text.focus();
				return false;
			}
			<?php } ?>
			return true;
		}
	//-->
	</script>
	<?php
	print "<form name=\"messageform\" method=\"post\" onsubmit=\"return checkForm(this);";
	print "\">\n";
	if (isset($news_id)) {
		$news = NewsController::getNewsItem($news_id);
	}
	else {
		$news_id="";
		$news = new News();
		$news->username = $uname;
		$news->date = time()-$_SESSION["timediff"];
		$news->title = "";
		$news->text = "";
	}
	print "<input type=\"hidden\" name=\"action\" value=\"save\" />\n";
	print "<input type=\"hidden\" name=\"uname\" value=\"".$news->username."\" />\n";
	print "<input type=\"hidden\" name=\"news_id\" value=\"".$news->id."\" />\n";
	print "<input type=\"hidden\" name=\"date\" value=\"".$news->date."\" />\n";
	print "<table class=\"NavBlockTable\">\n";
		print "<tr><td colspan=\"2\" class=\"NavBlockHeader\">".GM_LANG_edit_news."</td>";
		print "<tr><td class=\"NavBlockLabel\">".GM_LANG_title."</td><td class=\"NavBlockField\"><input type=\"text\" name=\"title\" size=\"50\" value=\"".$news->title."\" /><br /></td></tr>\n";
		print "<tr><td class=\"NavBlockLabel\">".GM_LANG_article_text."</td>";
		print "<td class=\"NavBlockField\">";
		if ($useCK) { // use CKeditor module
			$trans = get_html_translation_table(HTML_SPECIALCHARS);
			$trans = array_flip($trans);
			$news->text = strtr($news->text, $trans);
	//		$news->text = nl2br($news->text); This causes extra line breaks in CKEditor!
			
			?><script type="text/javascript" src="modules/CKEditor/ckeditor.js"></script><?php
			$oCKeditor = new CKEditor();
			$oCKeditor->BasePath = 'modules/CKEditor/';
			$oCKeditor->config["height"] = 450;
			$oCKEditor->config["enterMode"] = "br";
			$oCKEditor->config["ShiftEnterMode"] = "p";
			$oCKeditor->config['language'] = $language_settings[$LANGUAGE]["lang_short_cut"];
			$oCKeditor->editor("text", $news->text) ;
		} else { //use standard textarea
			print "<textarea name=\"text\" cols=\"80\" rows=\"10\">".$news->text."</textarea>";
		}
		print "</td></tr>\n";
		print "<tr><td class=\"NavBlockFooter\" colspan=\"2\"><input type=\"submit\"  value=\"".GM_LANG_save."\" /></td></tr>\n";
	print "</table>\n";
	print "</form>\n";
}
else if ($action=="save") {
	$news = NewsController::getNewsItem($news_id);
	if (!is_object($news)) $news = new News();
	$date=time()-$_SESSION["timediff"];
	if (empty($title)) $title="No Title";
	if (empty($text)) $text="No Text";
	$news->username = $uname;
	$news->date=$date;
	$news->title = $title;
	$news->text = $text;
	if ($news->addNews()) {
		print GM_LANG_news_saved;
	}
}
else if ($action=="delete") {
	if (NewsController::DeleteNews($news_id)) print GM_LANG_news_deleted;
}
print "<div class=\"CloseWindow\"><a href=\"#\" onclick=\"if (window.opener.refreshpage) window.opener.refreshpage(); window.close();\">".GM_LANG_close_window."</a></div>";

PrintSimpleFooter();
?>