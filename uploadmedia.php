<?php
/**
 * Allow admin users to upload media files using a web interface.
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
 * @subpackage Media
 * @version $Id: uploadmedia.php,v 1.8 2008/01/06 11:03:28 roland-d Exp $
 */
 
/**
 * Inclusion of the configuration file
*/
require "config.php";

if (!userCanEdit($gm_username)) {
	if (empty($LOGIN_URL)) header("Location: login.php?url=uploadmedia.php");
	else header("Location: ".$LOGIN_URL."?url=uploadmedia.php");
	exit;
}

if (isset($_SESSION["cookie_login"]) && $_SESSION["cookie_login"]==true) {
	if (empty($LOGIN_URL)) header("Location: login.php?ged=$GEDCOM&url=uploadmedia.php");
	else header("Location: ".$LOGIN_URL."?ged=$GEDCOM&url=uploadmedia.php");
	exit;
}

print_header($gm_lang["upload_media"]);
$upload_errors = array($gm_lang["file_success"], $gm_lang["file_too_big"], $gm_lang["file_too_big"],$gm_lang["file_partial"], $gm_lang["file_missing"]);
?>
<center>
<?php
	print "<span class=\"subheaders\">".Str2Upper($gm_lang["upload_media"])."</span><br /><br />\n";
	if ((isset($action)) && ($action=="upload")) {
		for($i=1; $i<6; $i++) {
			if (substr($_POST["folder".$i],0,1) == "/") $_POST["folder".$i] = substr($_POST["folder".$i],1);
			if (substr($_POST["folder".$i],-1,1) != "/") $_POST["folder".$i] .= "/";
			$error="";
			if (!empty($_FILES['mediafile'.$i]["name"])) {
				WriteToLog("Media file ".$MEDIA_DIRECTORY.$_POST["folder".$i].basename($_FILES['mediafile'.$i]['name'])." uploaded by >".$gm_username."<", "I", "S");
				$thumbgenned = false;
				if (!move_uploaded_file($_FILES['mediafile'.$i]['tmp_name'], $MEDIA_DIRECTORY.$_POST["folder".$i].basename($_FILES['mediafile'.$i]['name']))) {
					$error .= $gm_lang["upload_error"]."<br />".$upload_errors[$_FILES['mediafile'.$i]['error']]."<br />";
				}
				else {
					//-- automatically generate thumbnail
					if (!empty($_POST['genthumb'.$i]) && ($_POST['genthumb'.$i]=="yes")) {
						$filename = $MEDIA_DIRECTORY.$_POST["folder".$i].basename($_FILES['mediafile'.$i]['name']);
						if (!is_dir($MEDIA_DIRECTORY.$_POST["folder".$i]."thumbs")) mkdir($MEDIA_DIRECTORY.$_POST["folder".$i]."thumbs");
						$thumbnail = $MEDIA_DIRECTORY.$_POST["folder".$i]."thumbs/".basename($_FILES['mediafile'.$i]['name']);
						$thumbgenned = GenerateThumbnail($filename, $thumbnail);
						if (!$thumbgenned) $error .= $gm_lang["thumbgen_error"].$filename."<br />";
						else print $thumbnail." ".$gm_lang["thumb_genned"]."<br />";
					}
				}
				WriteToLog("Media thumbnail ".$MEDIA_DIRECTORY.$_POST["folder".$i]."thumbs/".basename($_FILES['thumbnail'.$i]['name'])." uploaded by >".$gm_username."<", "I", "S");
				if (!$thumbgenned) {
					if (!is_dir($MEDIA_DIRECTORY.$_POST["folder".$i]."thumbs")) mkdir($MEDIA_DIRECTORY.$_POST["folder".$i]."thumbs");
					if (!move_uploaded_file($_FILES['thumbnail'.$i]['tmp_name'], $MEDIA_DIRECTORY.$_POST["folder".$i]."thumbs/".basename($_FILES['thumbnail'.$i]['name']))) {
						$error .= $gm_lang["upload_error"]."<br />".$upload_errors[$_FILES['thumbnail'.$i]['error']]."<br />";
					}
				}
				if (!empty($error)) print "<span class=\"error\">".$error."</span><br />\n";
				else {
					print $gm_lang["upload_successful"]."<br /><br />";
					$imgsize = getimagesize($MEDIA_DIRECTORY.$_POST["folder".$i].$_FILES['mediafile'.$i]['name']);
					$imgwidth = $imgsize[0]+50;
					$imgheight = $imgsize[1]+50;
					print "<a href=\"#\" onclick=\"return openImage('".urlencode($MEDIA_DIRECTORY.$_POST["folder".$i].$_FILES['mediafile'.$i]['name'])."',$imgwidth, $imgheight);\">".$_FILES['mediafile'.$i]['name']."</a>";
					print"<br /><br />";
				}
			}
		}
	}
	
	if (!DirIsWritable($MEDIA_DIRECTORY) || !$MULTI_MEDIA) {
		print "<span class=\"error\"><b>";
		print $gm_lang["no_upload"];
		print "</b></span>";
	} else {
		print "<table width=\"70%\" class=\"$TEXT_DIRECTION\"><tr><td>";
		print_text("upload_media_help");
		if (!$filesize = ini_get('upload_max_filesize')) {
			$filesize = "2M";
		}
		print "<br />".$gm_lang["max_upload_size"];
		print " $filesize<br /><br />";
		print "</td></tr></table>";
		
		print "<form enctype=\"multipart/form-data\" method=\"post\" action=\"uploadmedia.php\">";
		print "<input type=\"hidden\" name=\"action\" value=\"upload\" />";
		print "<table border=0 cellpadding=0 cellspacing=0>";
		for($i=1; $i<6; $i++) {
			print "<tr>";
				print "<td ";
				write_align_with_textdir_check("right");
				print ">";
					print $gm_lang["folder"];
					print "&nbsp;";
				print "</td>";
				print "<td>";
					print "<input type=\"text\" name=\"folder".$i."\" size=60 />";
				print "</td>";
			print "</tr>";			
			print "<tr>";
				print "<td ";
				write_align_with_textdir_check("right");
				print ">";
					print $gm_lang["media_file"];
					print "&nbsp;";
				print "</td>";
				print "<td>";
					print "<input name=\"mediafile".$i."\" type=\"file\" size=60 />";
				print "</td>";
			print "</tr>";
			print "<tr>";
				print "<td ";
				write_align_with_textdir_check("right");
				print ">";
					print $gm_lang["thumbnail"];
					print "&nbsp;";
				print "</td>";
				print "<td>";
					print "<input name=\"thumbnail".$i."\" type=\"file\" size=60 />";
				print "</td>";
			print "</tr>";

			$ThumbSupport = "";
			if (function_exists("imagecreatefromjpeg") and function_exists("imagejpeg")) $ThumbSupport .= ", JPG";
			if (function_exists("imagecreatefromgif") and function_exists("imagegif")) $ThumbSupport .= ", GIF";
			if (function_exists("imagecreatefrompng") and function_exists("imagepng")) $ThumbSupport .= ", PNG";
			if (!$AUTO_GENERATE_THUMBS) $ThumbSupport = "";
			
			if ($ThumbSupport != "") {
				$ThumbSupport = substr($ThumbSupport, 2);	// Trim off first ", "
				print "<tr>";
					print "<td colspan=\"2\" class=\"center\">";
						print "<input type=\"checkbox\" name=\"genthumb".$i."\" value=\"yes\" /> ";
						print $gm_lang["generate_thumbnail"];
						print $ThumbSupport;
						print_help_link("generate_thumb_help", "qm");
					print "</td>";
				print "</tr>";
			}			
			print "<tr><td><br /><br /></td></tr>";
		}
		print "</table>";
		print "<br />";
		print "<input type=\"submit\" value=\"";
		print $gm_lang["upload"];
		print "\" />";
		print "</form>";
		print "<br />";
		print "</center>";
	}
	print_footer();
?>
