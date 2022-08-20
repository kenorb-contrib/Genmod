<?php
/**
 * Popup window that will allow a user to search for a media
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
 * @author Genmod Development Team
 * @package Genmod
 * @subpackage Display
 * @version $Id: media.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

// Only gedcom admins may do this
if (!$gm_user->userGedcomAdmin()) {
	if (LOGIN_URL == "") header("Location: login.php?".GetQueryString(true));
	else header("Location: ".LOGIN_URL."?url=media.php&amp;".GetQueryString(true));
	exit;
}
if (!isset($sort)) $sort = "name";
if (!isset($directory)) $directory = GedcomConfig::$MEDIA_DIRECTORY;
else $directory = urldecode($directory);
if (!isset($action)) $action = "";
if (!isset($filter)) $filter = "";
if (!isset($thumbs)) $thumbs = false;
if (!isset($autothumbs)) $autothumbs = false;
if (!isset($file)) $file = "";
else $file = urldecode($file);
if (!isset($error)) $error = "";
if (!isset($disp1)) $disp1 = "block";
if (!isset($disp2)) $disp2 = "none";
if ($disp1 == "block") $disp2 = "none";

GedcomConfig::$AUTO_GENERATE_THUMBS = $autothumbs;

PrintHeader(GM_LANG_manage_media." - ".$GEDCOMS[GedcomConfig::$GEDCOMID]['title']);

if ($action == "delete") {
	MediaFS::DeleteFile(basename($file), RelativePathFile($directory));
}
if ($disp1 == "block") {
	$dirs = MediaFS::GetMediaDirList(GedcomConfig::$MEDIA_DIRECTORY, true, 1, false, true);
//	if (!in_array(GedcomConfig::$MEDIA_DIRECTORY, $dirs)) $dirs[] = GedcomConfig::$MEDIA_DIRECTORY;
	sort($dirs);
	$files = MediaFS::GetMediaFileList($directory, $filter);
	if ($sort == "name") uasort($files, "MMNameSort");
	else if ($sort == "size") uasort($files, "MMSizeSort");
	else uasort($files, "MMLinkSort");
}

if ($action == "select_action") {
	$changed = false;
	$errors = false;
	if ($sel_action == "move" && urldecode($move_folder) != $directory) {
		foreach ($files as $filename => $fdetails) {
			$movefrom = "select".preg_replace(array("/\(/","/\)/","/\./","/-/","/ /","/\//","/\+/"), array("_","_","_","_","_","_","_"), $filename); 
			if (isset($$movefrom)) {
				$changed = true;
//				print "found move for ".$filename." - ".$$movefrom." from ".$directory." to ".urldecode($move_folder)."<br />";
				$result = MediaFS::MoveFile($filename, RelativePathFile($directory), RelativePathFile(urldecode($move_folder)));
				if(!$result) $errors = true;
			}
		}
		if (!$changed) $error = GM_LANG_nothing_selected;
		else {
			if (!$errors) $error = GM_LANG_move_ok;
			else $error = GM_LANG_move_fail;
		}
	}
	
	if ($sel_action == "delete") {
		foreach ($files as $filename => $fdetails) {
			$delfile = "select".preg_replace(array("/\(/","/\)/","/\./","/-/","/ /","/\//","/\+/"), array("_","_","_","_","_","_","_"), $filename); 
			if (isset($$delfile)) {
				$changed = true;
				$result = MediaFS::DeleteFile(basename($filename), RelativePathFile($directory));
				if(!$result) $errors = true;
			}
		}
		if (!$changed) $error = GM_LANG_nothing_selected;
		else {
			if (!$errors) $error = GM_LANG_delete_ok;
			else $error = GM_LANG_delete_fail;
		}
	}
	if ($changed) {
		// Get the file list again, it's changed by the mass update
		$files = MediaFS::GetMediaFileList($directory, $filter);
		if ($sort == "name") uasort($files, "MMNameSort");
		else if ($sort == "size") uasort($files, "MMSizeSort");
		else uasort($files, "MMLinkSort");
	}
}
//print "<pre>";
//print_r($files);	
//print "</pre>";
if ($action == "directory_action") {
	if ($dir_action == "create" && isset($new_dir) && !empty($new_dir)) {
		if (MediaFS::CreateDir($new_dir, $parent_dir, SystemConfig::$MEDIA_IN_DB)) $error = GM_LANG_dir_created;
		else $error = GM_LANG_dir_not_created;
	}
	if ($dir_action == "delete") {
		if (!isset($del_dir)) $error = GM_LANG_dirdel_fail;
		else {
			if (MediaFS::DeleteDir(urldecode($del_dir), SystemConfig::$MEDIA_IN_DB)) $error = GM_LANG_dirdel_ok;
			else $error = GM_LANG_dirdel_fail;
		}
	}
	$dirs = MediaFS::GetMediaDirList(GedcomConfig::$MEDIA_DIRECTORY, true, 1, false, true);
//	if (!in_array(GedcomConfig::$MEDIA_DIRECTORY, $dirs)) $dirs[] = GedcomConfig::$MEDIA_DIRECTORY;
	sort($dirs);
}

if ($action == "import_action") {
	// delold = delete file system file after imported
	// delexist = overwrite file in DB if exists
	// linked = import only files that are linked to MM-objects
	if(!isset($linked)) $linked = false;
	if(!isset($delold)) $delold = false;
	if(!isset($delexist)) $delexist = false;
	$count = 0;
	
	// Linked only
	if ($linked) {
		// Read the files into the table
		$sql = "SELECT m_mfile, m_gedrec FROM ".TBLPREFIX."media WHERE m_file='".GedcomConfig::$GEDCOMID."'";
		$res = NewQuery($sql);
		while ($row = $res->Fetchrow()) {
			$filefromged = GetGedcomValue("FILE", 1, $row[1]);
			if (stristr($filefromged, "://")) $file = $filefromged;
			else $file = GedcomConfig::$MEDIA_DIRECTORY.$row[0];
			// dbmode is always true for import
			if (MediaFS::CreateFile($file, $delexist, true, $delold)) $count++;
			else $error = GM_LANG_m_imp_err."<br />";
		}
	}
	else {
		$dirs = MediaFS::GetMediaDirList(GedcomConfig::$MEDIA_DIRECTORY, true, 1, false, false, false);
		foreach ($dirs as $pipo => $dir) {
			$files = MediaFS::GetFileList($dir, "", false);
			foreach ($files as $dikkedeur => $file) {
				// dbmode is always true for import
				if (MediaFS::CreateFile($file, $delexist, true, $delold)) $count++;
				else $error = GM_LANG_m_imp_err."<br />";
			}
		}
	}
	$error .= GM_LANG_mm_inserts." ".$count;
}

if ($action == "export_action") {
	// delold - delete DB file after exported
	// delexist - overwrite file in file system if exists
	// linked - export only files that are linked to MM-objects
	// genthumbs - no, yes; also export thumbnails
	if(!isset($linked)) $linked = false;
	if(!isset($delold)) $delold = false;
	if(!isset($delexist)) $delexist = false;
	$count = 0;

	// Linked only
	if ($linked) {
		$sql = "SELECT m_mfile, m_gedrec FROM ".TBLPREFIX."media WHERE m_file='".GedcomConfig::$GEDCOMID."'";
		$res = NewQuery($sql);
		while ($row = $res->Fetchrow()) {
			$filefromged = GetGedcomValue("FILE", 1, $row[1]);
			if (stristr($filefromged, "://")) $file = $filefromged;
			else $file = GedcomConfig::$MEDIA_DIRECTORY.$row[0];
			if (MediaFS::CreateFile($file, $delexist, false, $delold, $genthumbs)) $count++;
			else $error = GM_LANG_m_imp_err."<br />";
		}
	}
	else {
		$dirs = MediaFS::GetMediaDirList(GedcomConfig::$MEDIA_DIRECTORY, true, 1, false, false, true); 
		$dirs[] = "external_links";
		foreach ($dirs as $pipo => $dir) {
			$files = MediaFS::GetFileList($dir, "", true);
			foreach ($files as $dikkedeur => $file) {
				if (MediaFS::CreateFile($file, $delexist, false, $delold, $genthumbs)) $count++;
				else $error = GM_LANG_m_imp_err."<br />";
			}
		}
	}	
	$error .= GM_LANG_mm_exports." ".$count;
}

if ($action == "upload_action" && (!empty($picture))) {
	// picture - Name of the uploaded file
	// thumbnail - Name of the uploaded thumb
	// folder - Folder to place the new file
	// delupl - Overwrite if exists
	if (!isset($delupl)) $delupl = false;
	$result = MediaFS::UploadFiles($_FILES, $folder, $delupl);
	$filename = $result["filename"];
	if ($result["errno"] != 0) {
		$error = GM_LANG_upload_error."<br />".$result["error"];
	}
	else $error = $result["error"];
}

// Form for all screens
print "<form action=\"media.php\" method=\"post\" name=\"managemedia\" id=\"managemedia\" enctype=\"multipart/form-data\">\n";
print "<input type=\"hidden\" name=\"action\" value=\"\" />\n";
print "<input type=\"hidden\" name=\"directory\" value=\"".urlencode($directory)."\" />\n";

// Sort order
print "<input type=\"hidden\" name=\"sort\" value=\"".$sort."\" />\n";

// Declare the switch variables
print "<input type=\"hidden\" name=\"disp1\" />\n";
print "<input type=\"hidden\" name=\"disp2\" />\n";

// File management part --------------------------------------------------------------------------------------------------------------------------
if ($disp1 == "block") {
	// Setup the left box
	print "<div id=\"AdminColumnLeft\" style=\"display: ".$disp1.";\">\n";
		print "<div class=\"AdminColumnLeftLink\">".GM_LANG_navigation."</div>\n";
		print "<div class=\"BlockContainer ManageMediaBlockContainer\">\n";
		// Print the external media link
			print "<a href=\"javascript:".GM_LANG_show_dir."\" onclick=\"document.managemedia.directory.value='external_links'; document.managemedia.submit(); return false;\">";
			if ($directory == "external_links") print "<span class=\"Current\">";
			print GM_LANG_external_media;
			if ($directory == "external_links") print "</span>";
			print "</a><br />\n";
		
		foreach ($dirs as $key => $dir) {
			$canwrite = MediaFS::DirIsWritable($dir);
			$indent = preg_match_all("/\//", RelativePathFile($dir), $m)*8;
			$d = preg_split("/\//",$dir);
			$d = array_reverse($d);
			if (isset($d[1])) {
				print "<span style=\"padding-left:".$indent."pt;\">";
				print "<a href=\"javascript:".GM_LANG_show_dir."\" onclick=\"document.managemedia.directory.value='".urlencode($dir)."'; document.managemedia.submit(); return false;\">";
				if ($dir == RelativePathFile($directory)) print "<span class=\"Current\">";
				if (!$canwrite) print "<span class=\"ReadOnly\">";
				print $d[1];
				if (!$canwrite) print "</span>";
				if ($dir == RelativePathFile($directory)) print "</span>";
				print PrintReady("&nbsp;(".count(MediaFS::GetFileList($dir, "", SystemConfig::$MEDIA_IN_DB)).")");
				print "</a>";
				print "</span>";
				print "<br />\n";
			}
		}
		print "</div>\n"; // Close container
	print "</div>"; // Close column
	
	// Setup the right box
	print "\n<div id=\"AdminColumnRight\" style=\"display: ".$disp1.";\">";
		print "<div class=\"AdminColumnLeftLink\">".GM_LANG_options."</div>";
		
		// Switch screen option
		// File management
		print "\n<div id=\"ManageMediaSwitchBlock\" class=\"BlockContainer ManageMediaBlockContainer\">";
			print "<div class=\"BlockHeader ManageMediaBlockHeader\">".GM_LANG_switch_functions."</div>";
			print "<div class=\"BlockContent ManageMediaBlockContent\">";
				print "<input type=\"radio\" name=\"disp1\" id=\"dispa\" value=\"block\"";
				print " checked=\"checked\"";
				print " onclick=\"document.managemedia.action.value='switch'; document.managemedia.disp2.value='none'; document.managemedia.submit(); return false;\" />" . GM_LANG_file_management."<br />";
				print "<input type=\"radio\" name=\"disp1\" id=\"dispb\" value=\"none\"";
				print " onclick=\"document.managemedia.action.value='switch'; document.managemedia.disp2.value='block'; document.managemedia.submit(); return false;\" />" . GM_LANG_in_export;
			print "</div>";
		print "</div>";
		
		// Thumbnail options
		// Show thumbs
		print "\n<div id=\"ManageMediaThumbsBlock\" class=\"BlockContainer ManageMediaBlockContainer\">";
			print "<div class=\"BlockHeader ManageMediaBlockHeader\">".GM_LANG_thumb_options."</div>";
				print "<div class=\"BlockContent ManageMediaBlockContent\">";
				print "<input type=\"checkbox\" name=\"thumbs\" id=\"thumbs\" value=\"1\"";
				if ($thumbs) print "\" checked=\"checked\"";
				print " onclick=\"document.managemedia.submit(); return false;\" />" . GM_LANG_show_thumbnail."<br />";
				// Switch auto generate thumbs
				print "<input type=\"checkbox\" name=\"autothumbs\" id=\"autothumbs\" value=\"1\"";
				if ($autothumbs) print " checked=\"checked\"";
				print " />" . GM_LANG_auto_thumbs;
			print "</div>";
		print "</div>";
		
		// Filter
		print "\n<div id=\"ManageMediaFilterBlock\" class=\"BlockContainer ManageMediaBlockContainer\">";
			print "<div class=\"BlockHeader ManageMediaBlockHeader\">".GM_LANG_filter."</div>";
				print "<div class=\"BlockContent ManageMediaBlockContent\">";
				print "<input class=\"ManageMediaBlockInputText\" type=\"text\" name=\"filter\" value=\"".$filter."\" /><br />";
				print "<input type=\"button\" value=\"".GM_LANG_filter."\" onclick=\"document.managemedia.action.value='filter'; document.managemedia.submit(); return false;\" />";
			print "</div>";
		print "</div>";
		
		// Select/deselect all
		print "\n<div id=\"ManageMediaActionBlock\" class=\"BlockContainer ManageMediaBlockContainer\">";
			print "<div class=\"BlockHeader ManageMediaBlockHeader\">".GM_LANG_with_selected."</div>";
				print "<div class=\"BlockContent ManageMediaBlockContent\">";
				if ($directory != "external_links") {
					$link1 = "\n<a href=\"javascript: ".urlencode(GM_LANG_select_all)."\" onclick=\"";
					$link2 = "\n<a href=\"javascript: ".urlencode(GM_LANG_deselect_all)."\" onclick=\"";
					foreach ($files as $filename => $file) {
						$link1 .= "document.managemedia.select".preg_replace(array("/\(/","/\)/","/\./","/-/","/ /","/\//","/\+/"), array("_","_","_","_","_","_","_"), $filename).".checked=true; ";
						$link2 .= "document.managemedia.select".preg_replace(array("/\(/","/\)/","/\./","/-/","/ /","/\//","/\+/"), array("_","_","_","_","_","_","_"), $filename).".checked=false; ";
					}
					$link1 .= "return false;\">";
					$link2 .= "return false;\">";
					print "<div class=\"ManageMediaActionBlockLinks\">";
					print $link1.GM_LANG_select_all."</a>";
					print "&nbsp;/<br />";
					print $link2.GM_LANG_deselect_all."</a></div>";
				}
				// Actions with selected
				if (GedcomConfig::$MEDIA_DIRECTORY_LEVELS > 0) {
					// Move
					print "<input type=\"radio\" name=\"sel_action\" value=\"move\" checked=\"checked\"/>";
					print GM_LANG_sel_location."<br />";
					print "<div class=\"ManageMediaBlockDirList\">";
						print "<select name=\"move_folder\">";
						$d = RelativePathFile($directory);
						foreach($dirs as $key => $dir) {
							if (MediaFS::DirIsWritable($dir)) {
								if ($dir != $d) print "<option value=\"".urlencode($dir)."\">".$dir."</option>";
							}
						}
						print "</select>";
					print "</div>";
				}
				// Delete
				print "<input type=\"radio\" name=\"sel_action\" value=\"delete\" ".(GedcomConfig::$MEDIA_DIRECTORY_LEVELS == 0 ? "checked=\"checked\"" : "")." />".GM_LANG_delete."<br />";
				// The submit button
				print "<div class=\"ManageMediaBlockButton\"><input type=\"button\" value=\"".GM_LANG_go."\" onclick=\"document.managemedia.action.value='select_action'; document.managemedia.submit(); return false;\" /></div>";
			print "\n</div>";
		print "\n</div>";
	
		if (GedcomConfig::$MEDIA_DIRECTORY_LEVELS > 0) {
			// Directory maintenance
			print "\n<div id=\"ManageMediaFolderBlock\" class=\"BlockContainer ManageMediaBlockContainer\">";
				print "<div class=\"BlockHeader ManageMediaBlockHeader\">".GM_LANG_directories."</div>";
				print "<div class=\"BlockContent ManageMediaBlockContent\">";
					// Create
					print "<input type=\"radio\" name=\"dir_action\" value=\"create\" checked=\"checked\"/>";
					print GM_LANG_create_dir."<br /><input type=\"text\" class=\"ManageMediaBlockInputText\" name=\"new_dir\" /><br />";
					print GM_LANG_under."<br />";
					print "<div class=\"ManageMediaBlockDirList\">";
						print "<select name=\"parent_dir\">";
						foreach($dirs as $key => $dir) {
							if (MediaFS::DirIsWritable($dir)) {
								$d = RelativePathFile($dir);
								$l = preg_split("/\//", $d);
								if (count($l)-1 <= GedcomConfig::$MEDIA_DIRECTORY_LEVELS) print "<option value=\"".urlencode($dir)."\">".$dir."</option>";
							}
						}
						print "</select>";
					print "</div>";
					// Delete
					$csel = 0;
					$sel = "<div class=\"ManageMediaBlockDirList\">";
						$sel .= "<select name=\"del_dir\">";
						// To fix: only dirs with no subdirs
						foreach($dirs as $key => $dir) {
							if (MediaFS::DirIsWritable($dir) && MediaFS::DeleteDir($dir, SystemConfig::$MEDIA_IN_DB, true) && $dir != RelativePathFile(GedcomConfig::$MEDIA_DIRECTORY)) {
								$sel .= "<option value=\"".urlencode($dir)."\">".$dir."</option>";
								$csel++;
							}
						}
						$sel .= "</select></div>";
					print "<input type=\"radio\" name=\"dir_action\" value=\"delete\" ";
					if (!$csel) print "disabled=\"disabled\" ";
					print "/>";
					print GM_LANG_delete_directory."<br />";
					if ($csel) print $sel;
					else print GM_LANG_no_empty_dirs;
					// The submit button
					print "<div class=\"ManageMediaBlockButton\"><input type=\"button\" value=\"".GM_LANG_go."\" onclick=\"document.managemedia.action.value='directory_action'; document.managemedia.submit(); return false;\" /></div>";
				print "</div>";
			print "</div>";
		}
	
		print "</div>"; // Close container
	
	// Setup the middle box
	$cols = 1; // 1 or 2. 2 won't fit on screen properly
	if (count($files) < 2) $cols = 1;
	print "\n<div id=\"AdminColumnMiddle\" style=\"display: ".$disp1.";\">";
		print "<table class=\"NavBlockTable AdminNavBlockTable\">";
		print "<tr><td class=\"NavBlockHeader AdminNavBlockHeader\" colspan=\"".($cols*5)."\"><span class=\"AdminNavBlockTitle\">".GM_LANG_manage_media." - ".$GEDCOMS[GedcomConfig::$GEDCOMID]['title']."</span>";
		if (!empty($error)) print "<br /><span class=\"Error\">".$error."</span>";
		print "<br />".GM_LANG_files_found."&nbsp;".count($files);
		print "</td></tr>";
		print "<tr>";
		for ($i=1;$i<=$cols;$i++) {
			print "<td class=\"NavBlockColumnHeader AdminNavBlockColumnHeader\">".GM_LANG_select."</td>\n";
			print "<td class=\"NavBlockColumnHeader AdminNavBlockColumnHeader\"><a href=\"javascript: ".GM_LANG_sort_on_name."\" onclick=\"document.managemedia.sort.value='name'; document.managemedia.submit(); return false;\" title=\"".GM_LANG_sort_on_name."\">".GM_LANG_name."</a></td>\n";
			print "<td class=\"NavBlockColumnHeader AdminNavBlockColumnHeader\"><a href=\"javascript: ".GM_LANG_sort_on_size."\" onclick=\"document.managemedia.sort.value='size'; document.managemedia.submit(); return false;\" title=\"".GM_LANG_sort_on_size."\">".GM_LANG_size."</a></td>\n";
			print "<td class=\"NavBlockColumnHeader AdminNavBlockColumnHeader\"><a href=\"javascript: ".GM_LANG_sort_on_linked."\" onclick=\"document.managemedia.sort.value='linked'; document.managemedia.submit(); return false;\" title=\"".GM_LANG_sort_on_linked."\">".GM_LANG_linked."</a></td>\n";
			print "<td class=\"NavBlockColumnHeader AdminNavBlockColumnHeader\" colspan=\"1\">".GM_LANG_action."</td>\n";
		}
		print "</tr>\n";
		$i=0;
		$canwrite = true;
		
		// Get the type of dir we have here. If it's a thumb dir or the url dir where the thumbs for the urls are stored,
		// we will display the thumb itself instead of it's thumb, which is a placeholder.
		$curdir = RelativePathFile($directory);
		$d = preg_split("/\//",$curdir);
		$d = array_reverse($d);
		if (isset($d[1]) && ($d[1] == "thumbs" || $d[1] == "urls")) $is_thumb_dir = true;
		else $is_thumb_dir = false;
		
		$counter = 0;
		foreach ($files as $filename => $file) {
			$counter++;
			if ($i%2 == 0) print "<tr>";
			$fileobj = $file["filedata"];
			// print select
			print "<td class=\"NavBlockLabel AdminNavBlockLabel NavBlockCheckRadio ManageMediaSelectColumn ";
			if ($i%2 == 1) print "ManageMediaRightColumn";
			print "\">";
			if ($directory != "external_links") print "<input type=\"checkbox\" name=\"select".preg_replace(array("/\(/","/\)/","/\./","/-/","/ /","/\//","/\+/"), array("_","_","_","_","_","_","_"), $filename)."\" value=\"yes\" />";
			else print "&nbsp;";
			print "</td>";
			
			// print filename
			print "<td class=\"NavBlockLabel AdminNavBlockLabel\">";
			if (!SystemConfig::$MEDIA_IN_DB) $canwrite = AdminFunctions::FileIsWriteable($filename);
			if (!$thumbs) $thumbname = "";
			else $thumbname = ($is_thumb_dir ? ($fileobj->f_is_image ? $fileobj->f_main_file : $fileobj->f_thumb_file) : $fileobj->f_thumb_file);
			$close = MediaFS::DispImgLink($fileobj->f_main_file, $thumbname, $fileobj->f_file, "", 100, 100, $fileobj->f_width, $fileobj->f_height, $fileobj->f_is_image, $fileobj->f_file_exists, false);
			if (!$canwrite) print "<span class=\"ReadOnly\">";
			if ($directory == "external_links") print $filename;
			else print basename($filename);
			if (!$canwrite) print "</span>";
			if ($close) print "</a>";
			print "</td>";

			// print size
			print "<td class=\"NavBlockLabel AdminNavBlockLabel ManageMediaSizeColumn\">";
			print GetFileSize($fileobj->f_file_size);
			print "</td>";

			// Linked
			print "<td class=\"NavBlockLabel AdminNavBlockLabel ManageMediaLinkColumn\">";
			if (isset($file["objects"])) {
				// Define the box with links
				print "\n\t\t<div id=\"I".$counter."links\" class=\"ManageMediaPopup\"";
				print " onmouseover=\"keepbox('".$counter."'); return false;\" ";
				print "onmouseout=\"moveout('".$counter."'); return false;\">";
				print "<div class=\"ManageMediaPopupTitle\">".GM_LANG_mm_links."</div>";
				foreach($file["objects"] as $pipo => $media) {
					print "\n\t<a href=\"mediadetail.php?mid=".$media->xref."&amp;gedid=".$media->gedcomid."\" target=\"_blank\">".$media->title."</a><br />";
				}
				print "</div>";
				print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["link"]["other"]."\" class=\"ManageMediaFileLinks\" border=\"0\" alt=\"".GM_LANG_click_mm_links."\" title=\"".GM_LANG_click_mm_links."\" onclick=\"showbox(this, '".$counter."', 'relatives'); return false;\" onmouseout=\"moveout('".$counter."');return false;\"/>";
			}
			else print "&nbsp;";
			print "</td>";

			// Print actions
			// Delete 
			print "<td class=\"NavBlockLabel AdminNavBlockLabel ManageMediaFileLinksColumn\">";
			if ($canwrite) {
				print "<a href=\"media.php?action=delete&amp;file=".urlencode(MediaFS::NormalizeLink($filename))."&amp;directory=".$directory."&amp;thumbs=".$thumbs."&amp;filter=".$filter."\" onclick=\"return confirm('".GM_LANG_del_mm_file1;
				if (isset($file["objects"])) print GM_LANG_del_mm_file2;
				print "');\">";
				print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["delete"]["other"]."\" border=\"0\" alt=\"".GM_LANG_delete_file."\" title=\"".GM_LANG_delete_file."\" /></a>";
			}
			else print "&nbsp;";
//			print "</td>";
			
			// Download
//			print "<td class=\"NavBlockLabel AdminNavBlockLabel\">";
			if (SystemConfig::$MEDIA_IN_DB) {
				if (!empty($fileobj->f_link)) {
					print "<a href=\"showblob.php?link=".urlencode($fileobj->f_link);
				}
				else {
					print "<a href=\"showblob.php?file=".urlencode($filename);
				}	
				if (!empty($fileobj->f_mimetype)) print "&amp;header=".urlencode($fileobj->f_mimetype);
				print "\" target=\"_blank\">";
			}
			else print "<a href=\"downloadbackup.php?fname=".urlencode($filename)."\" target=\"_blank\">";
			print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["download"]["other"]."\" border=\"0\" alt=\"".GM_LANG_download_now."\" title=\"".GM_LANG_download_now."\" />";
			print "</a>";
			
			// Details
				print "\n\t\t<div id=\"I".$counter."D"."links\" class=\"ManageMediaPopup\" ";
				print "onmouseover=\"keepbox('".$counter."D"."'); return false;\" ";
				print "onmouseout=\"moveout('".$counter."D"."'); return false;\">";
				print "<div class=\"ManageMediaPopupTitle\">".GM_LANG_fdetails."</div>";
				if (!empty($fileobj->f_mimetype)) print "<span class=\"FactDetailLabel ManageMediaFactDetailLabel\">".GM_LANG_media_format.":&nbsp;</span><span class=\"FactDetailField ManageMediaFactDetailField\">".$fileobj->f_mimetype."</span><br />";
				if ($fileobj->f_is_image && $fileobj->f_height > 0) print "<span class=\"FactDetailLabel ManageMediaFactDetailLabel\">".GM_LANG_image_size.":&nbsp;</span><span class=\"FactDetailField ManageMediaFactDetailField\">".$fileobj->f_height.($TEXT_DIRECTION =="rtl"?" &rlm;x&rlm; " : " x ").$fileobj->f_width.'</span><br />';
				if ($fileobj->f_file_size > 0) print "<span class=\"FactDetailLabel ManageMediaFactDetailLabel\">".GM_LANG_media_file_size.":&nbsp;</span><span class=\"FactDetailField ManageMediaFactDetailField\">".GetFileSize($fileobj->f_file_size)."</span><br />";
				print "<span class=\"FactDetailLabel ManageMediaFactDetailLabel\">".GM_LANG_file_status.":&nbsp;</span><span class=\"FactDetailField ManageMediaFactDetailField\">";
				if ($canwrite) print GM_LANG_stat_rw;
				else print GM_LANG_stat_ro;
				print "</span>";
				print "</div>";
			
				print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["search"]["small"]."\" height=\"15\" width=\"15\" border=\"0\" alt=\"".GM_LANG_click_details."\" title=\"".GM_LANG_click_details."\" onclick=\"showbox(this, '".$counter."D"."', 'relatives'); return false;\" onmouseout=\"moveout('".$counter."D"."');return false;\" />";
			print "</td>";
			
			$i++;
			if ($cols == 1) $i++;
			if ($i%2 == 0) print "</tr>";
		}
		// Only for cols=2, so not used
		if ($i%2 != 0) {
			print "<td style=\"border-bottom:1px solid #493424;border-left:1px solid #493424;\">&nbsp;</td>";
			for ($j=1;$j<=6;$j++) {
				print "<td style=\"border-bottom:1px solid #493424;\">&nbsp;</td>";
			}
			print "</tr>";
		}
		print "</table>";
	print "</div>"; // Close column

}

if ($disp2 == "block") {
// File up/download, import/export --------------------------------------------------------------------------------------------------------------------

	// Left block
	print "<div id=\"AdminColumnLeft\" style=\"display: ".$disp2.";\">";
		print "<div class=\"NavBlockHeader AdminNavBlockHeader\">".GM_LANG_navigation."</div>";
	print "</div>";
			
	// Setup the right box
	print "\n<div id=\"AdminColumnRight\" style=\"display: ".$disp2.";\">";
		print "<div class=\"NavBlockHeader AdminNavBlockHeader\">".GM_LANG_options."</div>";
		// Switch screen option
		// File management
		print "\n<div id=\"ManageMediaSwitchBlock\" class=\"BlockContainer ManageMediaBlockContainer\">";
			print "<div class=\"BlockHeader ManageMediaBlockHeader\">".GM_LANG_switch_functions."</div>";
			print "<div class=\"BlockContent ManageMediaBlockContent\">";
				print "<input type=\"radio\" name=\"disp2\" id=\"dispa\" value=\"none\"";
				print " onclick=\"document.managemedia.action.value='switch'; document.managemedia.disp1.value='block'; document.managemedia.submit(); return false;\" />" . GM_LANG_file_management."<br />";
				print "<input type=\"radio\" name=\"disp2\" id=\"dispb\" value=\"block\"";
				print " checked=\"checked\"";
				print " onclick=\"document.managemedia.action.value='switch'; document.managemedia.disp1.value='none'; document.managemedia.submit(); return false;\" />" . GM_LANG_in_export;
			print "</div>";
		print "</div>";
	print "</div>";
	
	// Setup the middle box
	print "\n<div id=\"AdminColumnMiddle\" style=\"display: ".$disp2.";\">";
		print "<div class=\"NavBlockHeader AdminNavBlockHeader\"><span class=\"AdminNavBlockTitle\">".GM_LANG_manage_media." - ".$GEDCOMS[GedcomConfig::$GEDCOMID]['title']."</span>";
		if (!empty($error)) print "<br /><span class=\"Error\">".$error."</span>";
		print "</div>";

		// Upload media
		// Print the JS to check for valid characters in the filename
		?><script language="JavaScript" type="text/javascript">
		<!--
		function check( filename ) {
			if( filename.match( /^[a-zA-Z]:[\w- \\\\]+\..*$/ ) ) 
				return true;
			else
				alert( "<?php print GM_LANG_invalid_file; ?>" ) ;
				return false;
		}
		-->
		</script> <?php
		print "\n<div class=\"NavBlockLabel AdminNavBlockLabel ManageMediaUIEBlock\">";
			print "<div class=\"ManageMediaUIEBlockHeader\">";
				if (SystemConfig::$MEDIA_IN_DB) print GM_LANG_upload_db;
				else print GM_LANG_upload_filesys;
				print "<br />";
				if (!$filesize = ini_get('upload_max_filesize')) $filesize = "2M";
				print GM_LANG_max_upload_size.$filesize;
			print "</div>";
			print "<div class=\"ManageMediaUIEBlockContent\">";
				// Box for user to choose to upload file from local computer
				print "<input type=\"file\" name=\"picture\" size=\"30\" />&nbsp;&nbsp;&nbsp;".GM_LANG_upload_file."<br /><br />";
				// Box for user to choose to upload thumb from local computer
//					print "<input type=\"file\" name=\"thumbnail\" size=\"30\" />&nbsp;&nbsp;&nbsp;".GM_LANG_upl_thumb."<br /><br />";
				// Box for user to choose the folder to store the image
				$dirlist = MediaFS::GetMediaDirList(GedcomConfig::$MEDIA_DIRECTORY, true, 1, true, false, SystemConfig::$MEDIA_IN_DB);
//					if (!in_array(GedcomConfig::$MEDIA_DIRECTORY, $dirlist)) $dirlist[] = GedcomConfig::$MEDIA_DIRECTORY;
				sort($dirlist);
				print "<select name=\"folder\">";
				foreach($dirlist as $key => $dir) {
					print "<option value=\"".$dir."\">".$dir."</option>";
				}
				print "</select>";
				print "&nbsp;&nbsp;&nbsp;".GM_LANG_upload_to_folder."<br /><br />";
				print "<input type=\"checkbox\" name=\"delupl\" value=\"1\" />".GM_LANG_del_upl_files."<br />";
			print "</div>";
			print "<div class=\"ManageMediaBlockButton\"><input type=\"button\" value=\"".GM_LANG_go."\" onclick=\"document.managemedia.action.value='upload_action'; if (check(document.managemedia.picture.value)) document.managemedia.submit(); return false;\" /></div>";
		print "\n</div>";
		
		// Export DB->filesys
		print "\n<div class=\"NavBlockLabel AdminNavBlockLabel ManageMediaUIEBlock\">";
			print "<div class=\"ManageMediaUIEBlockHeader\">".GM_LANG_db_to_file."</div>";
			print "<div class=\"ManageMediaUIEBlockContent\">";
				print "<input type=\"checkbox\" name=\"delold\" value=\"1\" />".GM_LANG_del_exp_files."<br />";
				print "<input type=\"checkbox\" name=\"delexist\" value=\"1\" />".GM_LANG_del_exist_files."<br /><br />";
				print "<input type=\"checkbox\" name=\"linked\" value=\"1\" />".GM_LANG_linked_only."<br /><br />";
				print "<input type=\"radio\" name=\"genthumbs\" value=\"no\" checked=\"checked\" />".GM_LANG_exp_no_thum."<br />";
				print "<input type=\"radio\" name=\"genthumbs\" value=\"yes\" />".GM_LANG_exp_thumb."<br />";
			print "</div>";
			print "<div class=\"ManageMediaBlockButton\"><input type=\"button\" value=\"".GM_LANG_go."\" onclick=\"document.managemedia.action.value='export_action'; document.managemedia.submit(); return false;\" /></div>";
		print "\n</div>";
		
		// Import filesys->DB
		print "\n<div class=\"NavBlockLabel AdminNavBlockLabel ManageMediaUIEBlock\">";
			print "<div class=\"ManageMediaUIEBlockHeader\">".GM_LANG_file_to_db."</div>";
			print "<div class=\"ManageMediaUIEBlockContent\">";
				print "<input type=\"checkbox\" name=\"delold\" value=\"1\" />".GM_LANG_del_imp_files."<br />";
				print "<input type=\"checkbox\" name=\"delexist\" value=\"1\" />".GM_LANG_del_exist_db."<br />";
				print "<input type=\"checkbox\" name=\"linked\" value=\"1\" />".GM_LANG_linked_only."<br />";
			print "</div>";
			print "<div class=\"ManageMediaBlockButton\"><input type=\"button\" value=\"".GM_LANG_go."\" onclick=\"document.managemedia.action.value='import_action'; document.managemedia.submit(); return false;\" /></div>";
		print "\n</div>";
	print "</div>";
}
print "</form>";

function MMNameSort($a, $b) {
	
	$aname = (is_null($a["filedata"]->f_link) ? basename($a["filedata"]->f_file) : $a["filedata"]->f_file);
	$bname = (is_null($b["filedata"]->f_link) ? basename($b["filedata"]->f_file) : $b["filedata"]->f_file);
	return StringSort($aname, $bname);
}

function MMSizeSort($a, $b) {
	
	return ($a["filedata"]->f_file_size == $b["filedata"]->f_file_size ? MMNameSort($a, $b) : ($a["filedata"]->f_file_size > $b["filedata"]->f_file_size ? 1 : -1));
}

function MMLinkSort($a, $b) {
	
	if (isset($a["objects"])) $cnta = count($a["objects"]);
	else $cnta = 0;
	
	if (isset($b["objects"])) $cntb = count($b["objects"]);
	else $cntb = 0;
	
	if ($cnta == $cntb) return MMNameSort($a, $b); 
	else return ($cnta  < $cntb);
}
PrintFooter();
?>