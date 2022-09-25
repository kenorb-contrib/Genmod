<?php
/**
 * Class file for mediafiles, supports real and virtual file system
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
 * @subpackage DataModel
 * @version $Id: media_file_class.php,v 1.26 2009/03/06 06:25:40 sjouke Exp $
 */

include_once("mime_type_detect.class.php");
 
class MediaFS {
	
	var $fdetails = array();
	
	function GetMediaDirList($directory="", $all=true, $level=1, $checkwrite=true, $incthumbdir=false, $dbmode=false) {
		global $TBLPREFIX, $MEDIA_DIRECTORY_LEVELS, $MEDIA_DIRECTORY, $MEDIA_IN_DB, $INDEX_DIRECTORY;
// print "Dir in dirlist: ".$directory."<br />";
		$directory = RelativePathFile($directory);
		$dirs = array();
		if (!empty($directory) && $level = 1) $dirs[] = $directory;
		
		if ($dbmode) {
//			print "directory: ".$directory."<br />";
			$l = preg_split("/\//", $directory);
			$thislevel = count($l)-1;
//			print "thislevel: ".$thislevel."<br />";
//			print_r($l);
			$sql = "SELECT mf_path FROM ".$TBLPREFIX."media_files";
			if (!empty($directory)) $sql .= " WHERE mf_path LIKE '".$directory."%'";
			else $sql .= " WHERE mf_link NOT LIKE '%://%'";
			$sql .= " AND mf_file='<DIR>' ORDER BY mf_path";
			$res = NewQuery($sql);
			while ($row = $res->FetchRow()) {
				$folders = preg_split("/\//", $row[0]);
//				print_r($folders);
				$base = "";
				foreach ($folders as $level => $foldername) {
//					print $foldername."<br />";
					if ($level <= $MEDIA_DIRECTORY_LEVELS && !empty($foldername)) {
						$base = $base.$foldername."/";
						if (($all || $level == $thislevel) && !in_array($base, $dirs)) $dirs[] = $base;
					}
				}
			}
		}
		else {
			if (empty($directory)) $directory = $MEDIA_DIRECTORY;
			//print "Getting : ".$directory."<br />";
			$canwrite = true;
			$exclude_dirs = array($INDEX_DIRECTORY, "./languages/", "./fonts/", "./hooks/", "./images/", "./includes", "./languages/", "./modules/", "./places/", "./reports/", "./ufpdf/", "./themes/", "./blocks/", "./install/", "./includes/", "./pgvnuke/");
			if ($level <= $MEDIA_DIRECTORY_LEVELS) {
				$d = @dir($directory);
				if (is_object($d)) {
					while (false !== ($entry = $d->read())) {
						if ($entry != ".." && $entry != "." && $entry != "CVS" && ($incthumbdir || $entry != "thumbs")) {
							$entry = $directory.$entry."/";
							if(is_dir($entry)) {
								if ($checkwrite) $canwrite = DirIsWritable($entry);
								if (!in_array($entry, $exclude_dirs) && $canwrite) {
									if ($all) $dirs = array_merge($dirs, $this->GetMediaDirList($entry, $all, $level+1, $checkwrite, $incthumbdir, $dbmode));
									else $dirs[] = $entry;
								}
							}
						}
					}
					$d->close();
				}
			}
		}
//		print_r($dirs);
		return $dirs;
	}

	function GetFileList($directory, $filter="", $dbmode=false) {
		global $MEDIATYPE, $MEDIA_DIRECTORY, $TBLPREFIX;
		
		$dirfiles = array();
		if (!$dbmode) {
			if ($directory == "external_links") {
				// External links pics don't exist in Genmod.
				// We must check the MM items for links instead of files
				$sql = "SELECT m_gedrec FROM ".$TBLPREFIX."media WHERE m_gedrec LIKE '%://%'";
				$res = NewQuery($sql);
				while($row = $res->FetchRow()) {
					$link = GetGedcomValue("FILE", 1, $row[0]);
					if (!empty($filter)) {
						if (stristr($link, $filter)) {
							$dirfiles[] = $link;
						}
					}
					else $dirfiles[] = $link;
				}
			}
			else {
				$d = @dir($directory);
				$dirfiles = array();
				if (is_object($d)) {
					while (false !== ($entry = $d->read())) {
						if ($entry != ".." && $entry != ".") {
							if (!is_dir($directory.$entry)) {
								// Get the extension
								if ($this->IsValidMedia($entry)) {
									if (!empty($filter)) {
										if (stristr($entry, $filter)) {
											$dirfiles[] = RelativePathFile($directory.$entry);
										}
									}
									else $dirfiles[] = RelativePathFile($directory.$entry);
									// print $directory.$entry;
								}
							}
						}
					}
					$d->close();
				}
			}
		}
		else {
			$directory = RelativePathFile($directory);
			$m = RelativePathFile($MEDIA_DIRECTORY);
			if ($directory == "external_links") {
				$sql = "SELECT mf_link FROM ".$TBLPREFIX."media_files WHERE";
				$sql .= " mf_link NOT LIKE ''";
				// Reset the directory, as the media items have no path recorded.
				$directory = "";
			}
			else $sql = "SELECT mf_file FROM ".$TBLPREFIX."media_files WHERE mf_path LIKE '".$directory."' AND mf_link LIKE ''";
			if (!empty($filter)) $sql .= " AND (mf_file LIKE '%".$filter."%' OR m_titl LIKE '%".$filter."%')"; 
			$sql .= " AND mf_file NOT LIKE '<DIR>' ORDER BY mf_file";
//			print $sql."<br /><br />";
			$res = NewQuery($sql);
			while ($row = $res->FetchRow()) {
				if (!in_array($row[0], $dirfiles)) $dirfiles[] = $row[0];
			}
		}
		return $dirfiles;
	}
		
		
	function GetMediaFilelist($directory, $filter="", $dbmode=false) {
		global $TBLPREFIX, $MEDIA_IN_DB, $MEDIA_DIRECTORY, $MEDIATYPE, $AUTO_GENERATE_THUMBS, $DBCONN;
 //print "Dir in filelist: ".$directory."<br />";
 
		$directory = RelativePathFile($directory);
		if ($directory != "external_links") $m = RelativePathFile($MEDIA_DIRECTORY);
		else $m = "";
		$files = array();
		if ($dbmode) {
			// For DB media
			$sql = "SELECT ".$TBLPREFIX."media.*, ".$TBLPREFIX."media_files.* FROM ".$TBLPREFIX."media_files LEFT JOIN ".$TBLPREFIX."media ON mf_file=CONCAT('".$m."', m_file) WHERE";
			if ($directory == "external_links") {
				$sql .= " mf_link NOT LIKE ''";
				// Reset the directory, as the media items have no path recorded.
//				$directory = "";
			}
			else $sql .= " mf_path LIKE '".$directory."' AND mf_link LIKE ''";
			if (!empty($filter)) $sql .= " AND (mf_file LIKE '%".$filter."%' OR m_titl LIKE '%".$filter."%')"; 
			$sql .= " AND mf_file NOT LIKE '<DIR>' ORDER BY mf_file";
//			print $sql;
			$res = NewQuery($sql);
//			print "found: ".$res->numrows();
			while ($row = $res->FetchAssoc()) {
				if ($directory == "external_links") {
					if (!isset($files[$row["mf_link"]]["filedata"])) $files[$row["mf_link"]]["filedata"] = New MFile($row);
					if (!empty($row["m_media"])) {
						$media = new MediaItem($row);
						$files[$row["mf_link"]]["objects"][] = $media;
					}
				}
				else {
					if (!isset($files[$row["mf_file"]]["filedata"])) $files[$row["mf_file"]]["filedata"] = New MFile($row);
					if (!empty($row["m_media"])) {
						$media = new MediaItem($row);
						$files[$row["mf_file"]]["objects"][] = $media;
					}
				}
			}
			foreach ($files as $filename => $objects) {
				if (isset($objects["objects"])) {
					foreach ($objects["objects"] as $index =>$obj) {
						if (!$obj->disp) unset($files[$filename]);
					}
				}
			}
			return $files;
		}
		else {
			if (empty($directory)) $directory = "./";
			$dirfiles = $this->GetFileList($directory, $filter, $dbmode);
//			print_r($dirfiles);
			if (!count($dirfiles)) return $dirfiles;
			$sql = "SELECT * FROM ".$TBLPREFIX."media WHERE (";
			$first = true;
			foreach ($dirfiles as $key => $dir) {
				$dir = $this->NormalizeLink($dir);
//				print "search ".$dir."<br />";
				if (!$first) $sql .= " OR ";
				else $first = false;
				if (!empty($m)) $dir = preg_replace("~".$m."~", "", $dir); 
				$sql .= "m_file LIKE '%".$DBCONN->EscapeQuery($dir)."'";
			}
			$sql .=")";
			if (!empty($filter)) $sql .= " AND m_file LIKE '%".$DBCONN->EscapeQuery($filter)."%'"; 
			$sql .= " ORDER BY m_file";
//			print $sql;
			$res = NewQuery($sql);
			while ($row = $res->FetchAssoc()) {
//				print "<br />";
//				print_r($row);
//				$mi = new MediaItem($row);
//				$f = $m.RelativePathFile($this->CheckMediaDepth($mi->m_file));
				if ($directory != "external_links") $f = $m.RelativePathFile($this->CheckMediaDepth($row["m_file"]));
				else $f = GetGedcomValue("FILE", "1", $row["m_gedrec"]);
//				print "f: ".$f;
				if (in_array($f, $dirfiles)) {
//					print "added";
					$files[$f]["objects"][] = new MediaItem($row);
				}
			}
			foreach ($files as $filename => $objects) {
				if (isset($objects["objects"])) {
					foreach ($objects["objects"] as $index =>$obj) {
						if (!$obj->disp) unset($files[$filename]);
					}
				}
			}
			// NOW get the other file attributes
			foreach ($dirfiles as $pipo => $file) {
				$files[$file]["filedata"] = new MFile($file);
			}
			return $files;
			print_r($files);
		}
	}
	
	function PrintViewLink($file, $thumb=false, $paste=false) {
		global $MEDIA_IN_DB, $SERVER_URL, $USE_GREYBOX, $TEXT_DIRECTION, $gm_lang, $MEDIA_DIRECTORY, $AUTO_GENERATE_THUMBS;

		$fileobj = $file["filedata"];
//		print_r($fileobj);
		$realfile = RelativePathFile($fileobj->f_main_file);
		$thumbfile = $fileobj->f_thumb_file;
		if ($MEDIA_IN_DB) {
			if (!empty($fileobj->f_link)) {
				$realfile = $fileobj->f_link;
			}
			else {
				if (!$fileobj->f_is_image && !empty($fileobj->f_mimetype)) $realfile .= "&amp;header=".$fileobj->f_mimetype;
			}
		}
		else {
			// If not an image, let the function take care of what to display
			// Also, if MM-items are attached, there will probably be thumbs, also let the function handle this.
			// Else use the original file for display
			if (!$fileobj->f_is_image || isset($file["objects"])) $thumbfile = $this->ThumbNailFile($realfile, $MEDIA_IN_DB);
			else $thumbfile = $realfile;
		}

		if ($thumb) {
			print "\n\t\t\t<td class=\"list_value wrap $TEXT_DIRECTION\">";
			$twidth = 50;
			$theight = 50;
			if ($USE_GREYBOX && $fileobj->f_is_image) {
				print "<a href=\"".FilenameEncode($realfile)."\" title=\"".$fileobj->f_main_file."\" rel=\"gb_imageset[]\">";
			}
			else {
				print "<a href=\"#\" onclick=\"return openImage('".$realfile."','".$fileobj->f_width."','".$fileobj->f_height."', '".$fileobj->f_is_image."');\">";
			}
			print "<img src=\"".$thumbfile."\" border=\"0\" align=\"left\" class=\"thumbnail\" alt=\"\" width=\"".$twidth."\" height=\"".$theight."\" />";
			print "</a></td>";
		}
		
		print "<td class=\"list_value wrap $TEXT_DIRECTION\"";
		if (!$thumb) print " colspan=\"2\" ";
		print ">";
		print $gm_lang["filename"]."&nbsp;";
		if (!$paste) print $fileobj->f_file."<br />";
		else {
			$m = RelativePathFile($MEDIA_DIRECTORY);
			if (!empty($m)) $plink = preg_replace("~$m~", "", $fileobj->f_pastelink);
			else $plink = $fileobj->f_pastelink;
			print "<a href=\"#\" onclick=\"pasteid('".preg_replace("/'/", "\'", FilenameEncode($plink))."');\">".basename($fileobj->f_file)."</a><br />";
		}
		$linked = false;
		if (isset($file["objects"])) {
			foreach ($file["objects"] as $index => $media) {
				if (!empty($media->m_media)) {
					if (!$linked) print $gm_lang["used_in"]."&nbsp;";
					if ($media->m_titl != "") $title = "<b>".$media->m_titl."</b> (".$media->m_media.")";
					else $title = "";
					print "<a href=\"mediadetail.php?mid=".$media->m_media."&amp;gedcomid=".$media->m_gedfile."\" target=\"blank\">".PrintReady($title)."</a><br />";
					$linked = true;
				}
			}
		}
		if (!$linked) print "<span class=\"error\">".$gm_lang["media_not_linked"]."</span>";
		print "</td>";
	}
	
	/**
	 * Generates the thumbnail filename and path
	 *
	 * The full file path is taken and turned into the location of the thumbnail file.
	 *
	 * @author	Genmod Development Team
	 * @param		string $filename The full filename of the media item
	 * @return 	string the location of the thumbnail
	 */
	function ThumbnailFile($filename, $dbmode=false, $placeholder=true) {
		global $MEDIA_DIRECTORY, $GM_IMAGE_DIR, $GM_IMAGES, $AUTO_GENERATE_THUMBS, $MEDIA_DIRECTORY_LEVELS;
		global $MEDIA_EXTERNAL, $TBLPREFIX, $MEDIA_IN_DB, $INDEX_DIRECTORY;

		if (strlen($filename) == 0) return false;
		// NOTE: Lets get the file details
//		print "file: ".$filename."<br />";
		$parts = pathinfo($filename);
		$dirname = RelativePathFile($parts["dirname"]."/");
//		print "dir: ".$dirname."<br />";
		$file_basename = $parts["basename"];
		if (isset($parts["extension"])) $thumb_extension = $parts["extension"];
		else $thumb_extension = "";
		// We can skip this part for media in DB
		if (!$dbmode && $AUTO_GENERATE_THUMBS) {	
//			if ((stristr($filename, "://") && !$MEDIA_EXTERNAL) || !stristr($filename, "://")) {
				// in case of a link we set the thumb dir to the media directory
				if (stristr($filename, "://")) $dirname = RelativePathFile($MEDIA_DIRECTORY);
				// NOTE: Construct dirname according to media levels to keep
				if (!is_dir($dirname."thumbs/urls/") && $dirname == RelativePathFile($MEDIA_DIRECTORY)) {
					$this->CreateDir("thumbs", $dirname); 
					$this->CreateDir("urls", $dirname."thumbs/"); 
				}
				if ($MEDIA_DIRECTORY_LEVELS == 0) $dirname = RelativePathFile($MEDIA_DIRECTORY);
				if (!is_dir($dirname."thumbs/")&& !stristr($dirname, "thumbs")) {
//					print "dirname: ".$dirname."<br />";
					$this->CreateDir("thumbs", $dirname); 
				}
//			}
			// NOTE: First check if the thumbnail exists by its exact name
			// NOTE: This is needed if people have page1.pdf and image thumb by the same name
		}
		
		// Handle links
		if (stristr($filename, "://")) {
			if ($dbmode) {
				$file_basename = preg_replace(array("/http:\/\//", "/\//"), array("","_"),$filename);
				// See if the thumb exists
				if (!$this->GetDBFileDetails($file_basename)) {
					// Nothing in the DB for this external link, first try to make a thumb
					// If not there, no need to proceed
					//print "generating for ".$filename."<br />";
					if (!$this->GenerateThumbnail($filename, $INDEX_DIRECTORY.$file_basename, $ignoremediadir=true, $ext="")) {
						WriteToLog("MediaFS->ThumbnailFile: Cannot generate thumbnail from ".$filename.".", "E", "S");
						return "";
					}
					
					// Get the file details
					$this->GetFileDetails($file_basename);
					
					// it's a link
					$this->fdetails["link"] = $filename;
					
					// Whatever was taken from the file, we know it's a pic
					$this->fdetails["is_image"] = true;
					// Find an extension 
					$exts = preg_split("/;/", $this->fdetails["extension"]);
					$ext = $exts[0];
					if (!empty($ext)) $ext = substr($ext,1);
					
					// Get the thumb details
					$this->GetThumbDetails($INDEX_DIRECTORY.$file_basename);
					
					// Store the file information
					$this->fdetails["file"] = $file_basename;
					$this->WriteDBFileDetails();
					
					// Store the thumb
					$this->WriteDBThumbFile($this->fdetails["file"], $INDEX_DIRECTORY.$file_basename, true, true);
					
					// return the link
					return "showblob.php?file=".urlencode($this->fdetails["file"])."&amp;type=thumb";
				}
				else {
					// We found the file
					// return the link
					return "showblob.php?file=".urlencode($this->fdetails["file"])."&amp;type=thumb";
				}
			}
			else {
				$file_basename = $this->NormalizeLink($filename);
				if (file_exists(FilenameDecode($MEDIA_DIRECTORY."thumbs/urls/".$file_basename))) {
					return $MEDIA_DIRECTORY."thumbs/urls/".$file_basename;
				}
				else {
					if ($AUTO_GENERATE_THUMBS && is_dir($MEDIA_DIRECTORY."thumbs/urls/")) {
						if ($this->GenerateThumbnail($filename, $MEDIA_DIRECTORY."thumbs/urls/".$file_basename)) {
							return $MEDIA_DIRECTORY."thumbs/urls/".$file_basename;
						}
						else WriteToLog("MediaFS->ThumbnailFile: Cannot generate thumbnail from ".$filename.".", "E", "S");
					}
				}
			}
		}
		else {
			if ($dbmode) {
//				print "do something";
//				return $dirname.$file_basename;
			}
			else {
				// Handle files
//				print "Search thumb :".FilenameDecode($dirname."thumbs/".$file_basename)."<br />";
				if (file_exists(FilenameDecode($dirname."thumbs/".$file_basename))) {
					return $dirname."thumbs/".$file_basename;
				}
				else {
					if ($AUTO_GENERATE_THUMBS && is_dir($dirname."thumbs/")) {				
						if ($this->GenerateThumbnail($dirname.$file_basename, $dirname."thumbs/".$file_basename)) {
							return $dirname."thumbs/".$file_basename;
						}
					}
				}
			}
		}
		
		if ($placeholder) {
			switch ($thumb_extension) {
				case "pdf":
					return $GM_IMAGE_DIR."/".$GM_IMAGES["media"]["pdf"];
					break;
				case "doc":
					return $GM_IMAGE_DIR."/".$GM_IMAGES["media"]["doc"];
					break;
				case "ged":
					return $GM_IMAGE_DIR."/".$GM_IMAGES["media"]["ged"];
					break;
				default :
					return $GM_IMAGE_DIR."/".$GM_IMAGES["media"]["large"];
			}
		}
		else return "";
	}
	
	/**
	 * Validate the media depth
	 *
	 * When the user has a media depth greater than 0, all media needs to be
	 * checked against this to ensure the proper path is in place. This function
	 * takes a filename, split it in parts and then recreates it according to the
	 * chosen media depth
	 *
	 * @author	Genmod Development Team
	 * @param		string	$filename		The filename that needs to be checked for media depth
	 * @return 	string	A filename validated for the media depth
	 */
	function CheckMediaDepth($filename) {
		global $MEDIA_DIRECTORY, $MEDIA_DIRECTORY_LEVELS, $MEDIA_EXTERNAL;
	
		// NOTE: If the media depth is 0, no need to check it
		if (empty($filename) || ($MEDIA_EXTERNAL && stristr($filename, "://"))) return $filename;
		
		// NOTE: Check media depth
		$parts = pathinfo($filename);
		if (isset($parts["dirname"])) $dirname = $parts["dirname"];
		else return $parts["basename"];
		$file_basename = $parts["basename"];
		$dirname = preg_replace("/\.\/|\./", "", $dirname);
		$dir_levels = array_reverse(preg_split("/\//", $dirname));
		$level = 0;
		$path = "";
		$count_dir_levels = count($dir_levels);
		if ($MEDIA_DIRECTORY_LEVELS == 0 || empty($dirname)) return $file_basename;
		else if ($MEDIA_DIRECTORY_LEVELS < $count_dir_levels) {
			for ($ct_level = ($MEDIA_DIRECTORY_LEVELS-1); $ct_level >= 0; $ct_level--) {
				if (strlen(trim($dir_levels[$ct_level])) != 0) {
					$path .= $dir_levels[$ct_level]."/";
				}
			}
		}
		else return $dirname."/".$file_basename;
		return $path.$file_basename;
	}
	
	/**
	 * Function to generate a thumbnail image
	 *
	 * This function takes two arguments, the $filename, which is the orginal file
	 * and $thumbnail which is the name of the thumbnail image to be generated.
	 *
	 * @param string $filename	Name/URL of the picture
	 * @param string $thumbnail	Name of the thumbnail that will be generated
	 * @return	boolean	true|false
	 */
	function GenerateThumbnail($filename, $thumbnail, $ignoremediadir=false, $ext="") {
		global $MEDIA_DIRECTORY, $THUMBNAIL_WIDTH, $AUTO_GENERATE_THUMBS, $MEDIA_IN_DB;
//						print "Generating thumb for: ".$filename."<br />";

//		print "filename: ".$filename."<br />";
//		print "thumbnail: ".$thumbnail."<br />";
		$parts = pathinfo($thumbnail);
		$dirname = RelativePathFile($parts["dirname"]."/");
//		print "dirname: ".$dirname;
		if (!$AUTO_GENERATE_THUMBS) return false;
		if (!$MEDIA_IN_DB && file_exists($thumbnail)) return false;
		
		if (!$ignoremediadir && !DirIsWritable($dirname)) return false;
	
		if (!$ignoremediadir && strstr($filename, "://") && !is_dir($MEDIA_DIRECTORY."thumbs/urls")) {
			$this->CreateDir($MEDIA_DIRECTORY, "thumbs");
			$this->CreateDir($MEDIA_DIRECTORY."thums/", "urls");
			WriteToLog("MediaFS->GenerateThumbnail: Folder ".$MEDIA_DIRECTORY."thumbs/urls/ created.", "I", "S");
		}
		if (!strstr($filename, "://")) {
			if (!file_exists($filename)) {
				WriteToLog("MediaFS->GenerateThumbnail: Cannot create thumb from non-existent file: ".$filename, "E");
				return false;
			}
			if (!@fopen($filename, "r")) {
				WriteToLog("MediaFS->GenerateThumbnail: Cannot open file for creating thumb: ".$filename.". Check permissions.", "E");
				return false;
			}
			$imgsize = @getimagesize($filename);
			// Check if a size has been determined
			if (!$imgsize) return false;
	
			//-- check if file is small enough to be its own thumbnail
			if (($imgsize[0]<150)&&($imgsize[1]<150)) {
				@copy($filename, $thumbnail);
				return true;
			}
		}
		else {
			if (!$ignoremediadir && !DirIsWritable($MEDIA_DIRECTORY."thumbs/urls")) return false;
			$filename = preg_replace("/ /", "%20", $filename);
			if ($fp = @fopen($filename, "rb")) {
				if ($fp===false) return false;
				$conts = "";
				while(!feof($fp)) {
					$conts .= fread($fp, 4098);
				}
				fclose($fp);
				$fp = @fopen($thumbnail, "wb");
				if (!$fp) return false;
				else {
					if (!fwrite($fp, $conts)) return false;
					fclose($fp);
					$thumbnail = preg_replace("/%20/", " ", $thumbnail);
					if (!stristr("://", $filename)) $imgsize = getimagesize($filename);
					else $imgsize = getimagesize($thumbnail);
					if ($imgsize===false) return false;
					if (($imgsize[0]<150)&&($imgsize[1]<150)) return true;
				}
			}
			else return false;
		}
		$width = $THUMBNAIL_WIDTH;
		$height = round($imgsize[1] * ($width/$imgsize[0]));
		$ct = preg_match("/\.([^\.]+)$/", $filename, $match);
		if ($ct>0) {
			if (empty($ext)) $ext = strtolower(trim($match[1]));
			if ($ext=="gif") {
				if (function_exists("imagecreatefromgif") && function_exists("imagegif")) {
					WriteToLog("MediaFS->GenerateThumbnail: Create thumb attempt for ".$filename, "I", "S");
					$im = @imagecreatefromgif($filename);
					if (empty($im)) {
						WriteToLog("MediaFS->GenerateThumbnail: There were problems creating thumb for ".$filename, "W", "S");
						return false;
					}
					$new = imagecreatetruecolor($width, $height);
					imagecopyresampled($new, $im, 0, 0, 0, 0, $width, $height, $imgsize[0], $imgsize[1]);
					imagegif($new, $thumbnail);
					imagedestroy($im);
					imagedestroy($new);
					WriteToLog("MediaFS->GenerateThumbnail: Created thumb for ".$filename, "I", "S");
					return true;
				}
			}
			else if ($ext=="jpg" || $ext=="jpeg") {
				if (function_exists("imagecreatefromjpeg") && function_exists("imagejpeg")) {
					WriteToLog("MediaFS->GenerateThumbnail: Create thumb attempt for ".$filename, "I", "S");
					$im = @imagecreatefromjpeg($filename);
					if (empty($im)) {
						WriteToLog("MediaFS->GenerateThumbnail: There were problems creating thumb for ".$filename, "W", "S");
						return false;
					}
					$new = imagecreatetruecolor($width, $height);
					imagecopyresampled($new, $im, 0, 0, 0, 0, $width, $height, $imgsize[0], $imgsize[1]);
					imagejpeg($new, $thumbnail);
					imagedestroy($im);
					imagedestroy($new);
					WriteToLog("MediaFS->GenerateThumbnail: Created thumb for ".$filename, "I", "S");
					return true;
				}
			}
			else if ($ext=="png") {
				if (function_exists("imagecreatefrompng") && function_exists("imagepng")) {
					WriteToLog("MediaFS->GenerateThumbnail: Create thumb attempt for ".$filename, "I", "S");
					$im = @imagecreatefrompng($filename);
					if (empty($im)) {
						WriteToLog("MediaFS->GenerateThumbnail: There were problems creating thumb for ".$filename, "W", "S");
						return false;
					}
					$new = imagecreatetruecolor($width, $height);
					imagecopyresampled($new, $im, 0, 0, 0, 0, $width, $height, $imgsize[0], $imgsize[1]);
					imagepng($new, $thumbnail);
					imagedestroy($im);
					imagedestroy($new);
					WriteToLog("MediaFS->GenerateThumbnail: Created thumb for ".$filename, "I", "S");
					return true;
				}
			}
		}
	
		return false;
	}
	
	
	function UploadFiles($files, $path, $overwrite=false) {
		global $MEDIA_DIRECTORY, $MEDIA_IN_DB, $gm_lang, $INDEX_DIRECTORY, $TBLPREFIX, $DBCONN, $MEDIATYPE, $AUTO_GENERATE_THUMBS, $error;
		
		$error = "";
		$result = array("filename"=>"", "error"=>"", "errno"=>"0");
		// Error numbers:
		// 0 - no error
		// 1 - File too big
		// 2 - File too big
		// 3 - Partially uploaded
		// 4 - File missing
		// 5 - Illegal filetype
		// 6 - File already exists
		// 7 - Nothing uploaded
		
		if (count($files)>0) {
			$upload_errors = array($gm_lang["file_success"], $gm_lang["file_too_big"], $gm_lang["file_too_big"],$gm_lang["file_partial"], $gm_lang["file_missing"]);
			if (!empty($path)) {
				$path = RelativePathFile($this->CheckMediaDepth($path));
				if (!empty($path) && substr($path,-1,1) != "/") $path .= "/";
			}
			foreach($files as $type =>$upload) {
//				print $type."<br />";
//				print_r($upload);
//				print "<br /><br />";
				if (!empty($upload["name"])) {
					
					// first get the details of the uploaded file
					$fname = basename($upload["name"]);
					$file = $path.$fname;
					$et = preg_match("/(\.\w+)$/", $fname, $ematch);
					if ($et>0) $ext = substr(trim($ematch[1]),1);
					else $ext = "";
					
					// Is such a file allowed?
					if (!in_array(strtolower($ext), $MEDIATYPE)) {
						WriteToLog("MediaFS->UploadFiles: Illegal upload attempt. File: ".$file, "W", "S");
						$result["error"] = $gm_lang["ext_not_allowed"];
						$result["errno"] = 5;
						unlink($upload["tmp_name"]);
					}
					else {
						if (file_exists($upload["tmp_name"]) && $upload["size"] > 0) {
							
							if ($MEDIA_IN_DB) {
							
								// check if already exists
								$exists = $this->GetDBFileDetails($file);
								if (!$exists || $overwrite) {
								
									// Get the new details
									
									// Try to create a thumb
									$thumb = $INDEX_DIRECTORY.basename($upload["name"]);
									$AUTO_GENERATE_THUMBS = true;
									$hasthumb = $this->GenerateThumbNail($upload["tmp_name"], $thumb, true, $ext);
							
									// and get the details
									if ($hasthumb) $this->GetThumbDetails($thumb);
							
									// Add the file attributes to the virtual directory
									$this->GetFileDetails($upload["tmp_name"]);
									// assign the name if new. Also when old, because GetFileDetails of the upload file 
									// returns path of the PHP upload directory
									$this->fdetails["file"] = $file;
									$this->fdetails["path"] = $path;
									$this->fdetails["fname"] = $fname;
									$this->WriteDBFileDetails($exists);
							
									// Add the file to the DB
									$this->WriteDBDatafile($upload["tmp_name"], $exists);
							
									// Add the thumb to the DB
									if ($hasthumb) $this->WriteDBThumbfile($this->fdetails["file"], $thumb, $exists, true);
								}
								else {
									$result["error"] = $gm_lang["file_exists"];
									$result["errno"] = 6;
								}
								
							}
							else {	
								// Real file system mode
								$exists = $this->GetFileDetails($file);
								if (!$exists || $overwrite) {
									if ($exists) $this->DeleteFile($fname, $path, $MEDIA_IN_DB);
									if (!empty($upload['tmp_name'])) {
										if (!move_uploaded_file($upload['tmp_name'], $file)) {
											$result["error"] = "<br />".$gm_lang["upload_error"]."<br />".$upload_errors[$upload['error']];
											$result["errno"] = $upload['error'];
										}
										else {
											$thumbnail = $this->ThumbnailFile($file, $MEDIA_IN_DB);
										}
									}
								}
								else {
									$result["error"] = $gm_lang["file_exists"];
									$result["errno"] = 6;
								}
							}
						}
						else {
							$result["error"] = $upload_errors[4];
							$result["errno"] = 4;
						}
					}
					if (!empty($result["error"])) {
//						print "<span class=\"error\">".$error."</span><br />";
						return $result;
					}
					$result["error"] = $gm_lang["upload_successful"];
					$result["filename"] = $file;
					return $result;
				}
			}
			// Nothing uploaded but no errors
			$result["errno"] = 0;
			return $result;
		}
	}
	
	function GetDBFileDetails($filename) {
		global $TBLPREFIX;

		$this->fdetails = array();
		$this->fdetails["file"] = "";
		$this->fdetails["path"] = "";
		$this->fdetails["fname"] = "";
		$this->fdetails["is_image"] = false;
		$this->fdetails["width"] = "0";
		$this->fdetails["height"] = "0";
		$this->fdetails["size"] = "0";
		$this->fdetails["twidth"] = "0";
		$this->fdetails["theight"] = "0";
		$this->fdetails["tsize"] = "0";
		$this->fdetails["mimetype"] = "";
		$this->fdetails["mimedesc"] = "";
		$this->fdetails["link"] = "";
				
		$sql = "SELECT * FROM ".$TBLPREFIX."media_files WHERE mf_file='".$filename."'";
		$res = NewQuery($sql);
		if ($res->NumRows() > 0) {
			$row = $res->FetchAssoc();
			foreach ($this->fdetails as $index => $value) {
				$this->fdetails[$index] = $row["mf_".$index];
			}
			return $this->fdetails;
		}
		return false;
	}
	
	function GetFileDetails($filename) {
		
		// Initialise
		$this->fdetails["width"] = 0;
		$this->fdetails["height"] = 0;
		$this->fdetails["size"] = 0;
		$this->fdetails["mimetype"] = "";
		$this->fdetails["mimedesc"] = "";
		$this->fdetails["extension"] = "";
		$this->fdetails["is_image"] = false;
		$this->fdetails["link"] = false;
		
		// and get the details
		if (file_exists($filename)) {
			if (stristr($filename, "://")) $this->fdetails["link"] = true;
			else $this->fdetails["link"] = false;
			$path = pathinfo($filename);
			$this->fdetails["path"] = $path["dirname"];
			if (!empty($this->fdetails["path"]) && substr($this->fdetails["path"],-1) != "/") $this->fdetails["path"] = $this->fdetails["path"]."/";
			$this->fdetails["fname"] = $path["basename"];
			$this->fdetails["size"] = @filesize($filename);
			if ($file_details = @getimagesize($filename)) {
				$this->fdetails["width"] = $file_details[0];
				$this->fdetails["height"] = $file_details[1];
				$this->fdetails["is_image"] = true;
			}
			$mimetypedetect = New MimeTypeDetect();
			$mime = $mimetypedetect->FindMimeType($filename);
			$this->fdetails["mimetype"] = $mime["mime_type"];
			$this->fdetails["mimedesc"] = $mime["description"];
			$exts = preg_split("/;/", $mime["extension"]);
			$ext = $exts[0];
			if (!empty($ext)) $ext = substr($ext,1);
			$this->fdetails["extension"] = $ext;
			return $this->fdetails;
		}
		else return false;
	}
	
	function GetThumbDetails($filename) {

		// Initialise
		$this->fdetails["twidth"] = 0;
		$this->fdetails["theight"] = 0;
		$this->fdetails["tsize"] = 0;
		
		// and get the details
		$this->fdetails["tsize"] = @filesize($filename);
		if ($file_details = @getimagesize($filename)) {
			$this->fdetails["twidth"] = $file_details[0];
			$this->fdetails["theight"] = $file_details[1];
		}
		return $this->fdetails;
	}
	
	function UpdateDBThumbDetails($file, $size, $width, $height) {
		global $TBLPREFIX;

		$sql = "UPDATE ".$TBLPREFIX."media_files SET mf_twidth='".$width."', mf_theight='".$height."', mf_tsize='".$size."' WHERE mf_file='".$file."'";
		$res = NewQuery($sql);
		if ($res) return true;
		else return false;
	}
				
	
	function WriteDBFileDetails($delete=false) {
		global $TBLPREFIX;

		// delete old data
		if ($delete) {
			$sql = "DELETE FROM ".$TBLPREFIX."media_files WHERE mf_file='".$this->fdetails["file"]."'";
			$ires = NewQuery($sql);
		}
		
		// Insert the file details
		$sql = "INSERT INTO ".$TBLPREFIX."media_files (mf_id, mf_file, mf_path, mf_fname, mf_is_image, mf_width, mf_height, mf_size, mf_twidth, mf_theight, mf_tsize, mf_mimetype, mf_mimedesc, mf_link) VALUES ('0', '".$this->fdetails["file"]."', '".$this->fdetails["path"]."', '".$this->fdetails["fname"]."', '".$this->fdetails["is_image"]."', '".$this->fdetails["width"]."', '".$this->fdetails["height"]."', '".$this->fdetails["size"]."', '".$this->fdetails["twidth"]."', '".$this->fdetails["theight"]."', '".$this->fdetails["tsize"]."', '".$this->fdetails["mimetype"]."', '".$this->fdetails["mimedesc"]."', '".$this->fdetails["link"]."')";
		$ires = NewQuery($sql);
		if ($ires) return true;
		else return false;
	}
	
	function WriteDBDatafile($data, $delete=false, $unlink=false) {
		global $TBLPREFIX, $DBCONN;
		
		$fp = fopen(FileNameEncode($data), "rb");
		if ($fp) {
			if ($delete) {
				// Clean previous versions
				$sql = "DELETE FROM ".$TBLPREFIX."media_datafiles WHERE mdf_file='".$this->fdetails["file"]."'";
				$ires = NewQuery($sql);
				$sql = "DELETE FROM ".$TBLPREFIX."media_thumbfiles WHERE mtf_file='".$this->fdetails["file"]."'";
				$ires = NewQuery($sql);
			}
			// Insert the new one
			while (!feof($fp)) {
				// Make the data mysql insert safe
				$binarydata = $DBCONN->EscapeQuery(fread($fp, 65535));

				$sql = "INSERT INTO ".$TBLPREFIX."media_datafiles (mdf_id, mdf_file, mdf_data) VALUES (";
				$sql .= "'0', '".$this->fdetails["file"]."', '".$binarydata."')";
				$ires = NewQuery($sql);
				if (!$ires) return false;
			}
			fclose($fp);
		}
		else return false;
		
		if ($unlink) return @unlink($data);
		else return true;
	}
	
	function WriteDBThumbfile($file, $thumb, $delete=false, $unlink=false) {
		global $TBLPREFIX, $DBCONN;

		if (empty($thumb)) return false;
						
		$fp = fopen(FileNameEncode($thumb), "rb");
		if ($fp) {
			if ($delete) {
				// Clean previous versions
				$sql = "DELETE FROM ".$TBLPREFIX."media_thumbfiles WHERE mtf_file='".$file."'";
				$ires = NewQuery($sql);
			}
			// Insert the new one
			while (!feof($fp)) {
				// Make the data mysql insert safe
				$binarydata = $DBCONN->EscapeQuery(fread($fp, 65535));

				$sql = "insert into ".$TBLPREFIX."media_thumbfiles (mtf_id, mtf_file, mtf_data) values (";
				$sql .= "'0', '".$file."', '".$binarydata."')";
				$ires = NewQuery($sql);
				if (!$ires) return false;
			}
			fclose($fp);
		}
		else return false;
		
		if ($unlink) return unlink($thumb);
		else return true;
	}

	function CreateFile($filename, $delete=false, $dbmode=false, $delonimport=false, $exportthum="no") {
		global $MEDIA_IN_DB, $TBLPREFIX, $gm_lang, $MEDIA_DIRECTORY;

		// File system to DB
		if ($dbmode) {

			if (!$delete &&	$this->GetDBFileDetails($filename)) {
				WriteToLog("MediaFS->CreateFile: File already exists in DB: ".$filename, "W", "S");
				return false;
			}
			
//			$realfile = RelativePathFile($MEDIA_DIRECTORY.$this->CheckMediaDepth($filename));
//			$this->fdetails["file"] = $realfile;
			$this->fdetails["file"] = $filename;
			if (!$this->GetFileDetails($filename)) {
				WriteToLog("MediaFS->CreateFile: Error getting file details of ".$filename, "W", "S");
				return false;
			}
			$this->CreateDir($this->fdetails["path"], "", true);
			
			if ($this->fdetails["is_image"]) {
				$thumb = $this->ThumbNailFile($filename, false, false);
				$this->GetThumbDetails($thumb);
			}
			else $this->GetThumbDetails("");
		
			$success = $this->WriteDBFileDetails($delete);
			if (!$success) {
				WriteToLog("MediaFS->CreateFile: Error writing media file details for ".$filename, "W", "S");
				return false;
			}
			else $success = $this->WriteDBDatafile($filename, $delete, $delonimport);
			if (!$success) {
				WriteToLog("MediaFS->CreateFile: Error writing media file data for ".$filename, "W", "S");
				return false;
			}
			else if ($this->fdetails["is_image"] && !empty($thumb)) {
				$success = $this->WriteDBThumbfile($this->fdetails["file"], $thumb, $delete, $delonimport);
				if (!$success) {
					WriteToLog("MediaFS->CreateFile: Error writing media thumb data for ".$filename, "W", "S");
					return false;
				}
			}
			return true;
		}
		else {
			// Get the details of the FS-file
			if ($this->GetFileDetails($filename)) {
				// If present and we don't overwrite, return error
				if (!$delete) {
					WriteToLog("MediaFS->CreateFile: File already exists in filesys: ".$filename, "W", "S");
					return false;
				}
				else {
					// Else delete the FS-file
					if (!$this->DeleteFile($this->fdetails["fname"], $this->fdetails["path"], false)) {
						WriteToLog("MediaFS->CreateFile: Cannot delete from filesys: ".$filename, "W", "S");
						return false;
					}
				}
			}
			// Get the details of the files in the DB
			$islink = stristr($filename, "://");
			
			if ($islink) {
				$realfile = $this->NormalizeLink($filename);
			}
			else {
//				$realfile = RelativePathFile($MEDIA_DIRECTORY.$this->CheckMediaDepth($filename));
				$realfile = $filename;
			}
//			print $realfile."<br />";
			$this->fdetails["file"] = $realfile;
			if (!$this->GetDBFileDetails($realfile)) {
				WriteToLog("MediaFS->CreateFile: Error getting DB file details of ".$filename, "W", "S");
				return false;
			}
			// Create the directory in file mode
			if (empty($this->fdetails["path"])) $this->fdetails["path"] = $MEDIA_DIRECTORY;
			// 1. main directory
			if (!$this->CreateDir($this->fdetails["path"], "", false)) {
				WriteToLog("MediaFS->CreateFile: Cannot create folder ".$this->fdetails["path"], "W", "S");
				return false;
			}
			// 2. Thumb directory
			if (!$this->CreateDir("thumbs", $this->fdetails["path"], false)) {
				WriteToLog("MediaFS->CreateFile: Cannot create folder ".$this->fdetails["path"]."thumbs", "W", "S");
				return false;
			}
			// 3. If llink, url directory
			if ($islink && !$this->CreateDir("urls", $this->fdetails["path"]."thumbs/", false)) {
				WriteToLog("MediaFS->CreateFile: Cannot create folder ".$this->fdetails["path"]."thumbs/urls", "W", "S");
				return false;
			}
			if (!DirIsWritable($this->fdetails["path"])) {
				WriteToLog("MediaFS->CreateFile: Folder not writable: ".$this->fdetails["path"],"W", "S");
				return false;
			}
			if ($exportthum != "no" && !DirIsWritable($this->fdetails["path"]."thumbs")) {
				WriteToLog("MediaFS->CreateFile: Folder not writable: ".$this->fdetails["path"]."thumbs", "W", "S");
				return false;
			}
			if ($islink && $exportthum != "no" && !DirIsWritable($MEDIA_DIRECTORY."thumbs/urls")) {
				WriteToLog("MediaFS->CreateFile: Folder not writable: ".$MEDIA_DIRECTORY."thumbs/urls", "W", "S");
				return false;
			}
			
			// copy the file from DB to filesys
			if (!$islink) {
				$sql = "SELECT mdf_data FROM ".$TBLPREFIX."media_datafiles WHERE mdf_file='".$realfile."' ORDER BY mdf_id ASC";
				$res = NewQuery($sql);
				if ($res) {
					$fp = @fopen($realfile, "wb");
					while ($row = $res->FetchRow()) {
						fwrite($fp, $row[0]);
					}
					fclose($fp);
				}
			}
			if ($exportthum == "yes" && $this->fdetails["is_image"]) {
				$sql = "SELECT mtf_data FROM ".$TBLPREFIX."media_thumbfiles WHERE mtf_file='".$realfile."' ORDER BY mtf_id ASC";
				$res = NewQuery($sql);
				if ($res && $res->NumRows() > 0) {
					if ($islink) $fp = @fopen($MEDIA_DIRECTORY."thumbs/urls/".$this->fdetails["file"], "wb");
					else $fp = @fopen($this->fdetails["path"]."thumbs/".$this->fdetails["fname"], "wb");
					while ($row = $res->FetchRow()) {
						fwrite($fp, $row[0]);
					}
					fclose($fp);
				}
			}
			
			if ($delonimport) $this->DeleteFile($realfile, "", true);
			
			return true;
		}
	}
	
	function NormalizeLink($link) {
		if (stristr($link, "://")) return preg_replace(array("/http:\/\//", "/\//"), array("","_"),$link);
		else return $link;
	}

	function CreateDir($dir, $parent, $dbmode=false, $recurse=false) {
		global $MEDIA_IN_DB, $TBLPREFIX, $gm_lang;
		static $dirlist;
	
		//Cleanup the dir
		if (!empty($dir)) $dir = trim($dir,"/.")."/";
		$parent = urldecode($parent);

		if (!isset($dirlist)) {
			$dirlist = array();
			$dirlist[0] = array();
			$dirlist[1] = array();
		}
		if (in_array($parent.$dir, $dirlist[$dbmode])) return true;
		
		if (!$dbmode && is_dir($parent.$dir)) return true;

		// Create the tree, if this is not the subordinate of an existing dir
		if (!$recurse) {
			$dirlevels = $dir_levels = preg_split("/\//", $parent.$dir);
			$basedir = "";
			foreach($dirlevels as $pipo =>$directory) {
				if (!empty($directory)) {
					$directory .= "/";
					if (!$this->CreateDir($directory, $basedir, $dbmode, true)) {
						WriteToLog("MediaFS->CreateDir: Cannot create folder ".$basedir.$directory." for tree ".$parent.$dir, "W", "S");
						return false;
					}
					$basedir .= $directory;
				}
			}
		}
		if ($dbmode) {
			$parent = RelativePathFile($parent);
			$full = $parent.$dir;
			if (empty($full)) return true;
			$sql = "SELECT * FROM ".$TBLPREFIX."media_files WHERE mf_path='".$parent.$dir."' AND mf_file='<DIR>'";
			$res = NewQuery($sql);
			if ($res->NumRows() > 0 ) {
				$dirlist[$dbmode][] = $parent.$dir;
				return true;
			}
			$sql = "INSERT INTO ".$TBLPREFIX."media_files (mf_file, mf_path) VALUES('<DIR>', '".$parent.$dir."')";
			$res = NewQuery($sql);
			if ($res) {
				$dirlist[$dbmode][] = $parent.$dir;
				return true;
			}
			return false;
		}
		else {
			if (!is_dir($parent.$dir)) {
				if (!@mkdir($parent.$dir, 0777)) {
					return false;
				}
			}
			else return true;
			
			if (!file_exists($parent.$dir."/index.php")) {
				$inddata = html_entity_decode('<?php\nheader("Location: ../index.php");\nexit;\n?>');
				$fp = @fopen($parent.$dir."/index.php","w+");
				if (!$fp) return false;
				else {
					// Write the index.php for the media folder
					fputs($fp,$inddata);
					fclose($fp);
				}			
			}
			$dirlist[$dbmode][] = $parent.$dir;
			return true;
		}
	}
	
	function MoveFile($file, $from, $to) {
		global $MEDIA_IN_DB, $TBLPREFIX, $GEDCOMID, $DBCONN, $MEDIA_DIRECTORY;
		static $change_id;
		require_once("includes/functions_edit.php");
		
		$file = basename($file);
		// We must have 2 paths: 
		// 1. for the physical file, including the media directory path
		// 2. for the path stored with the media item, excluding the media directory path
		// The incoming parameters are 1., so we must calculate 2.
		$m = RelativePathFile($MEDIA_DIRECTORY);
		if (!empty($m)) {
			$mfrom = preg_replace("~$m~", "", $from);
			$mto = preg_replace("~$m~", "", $to);
		}
		else {
			$mfrom = $from;
			$mto = $to;
		}
//		print "file: ".$file."<br />";
//		print "from: ".$from."<br />";
//		print "search media for: ".$mfrom."<br />";
//		print "to: ".$to."<br />";

		// Cannot copy onto itself
		if ($from == $to) return false;
		
		// Cannot overwrite if target already exists
		if ($this->FileExists($to.$file)) return false;
		
		// Check is any changes for this file are pending. If so, we cannot move the stuff.
		$sql = "SELECT count(ch_id) FROM ".$TBLPREFIX."changes WHERE ch_new LIKE '%1 FILE ".$mfrom.$file."%'";
		$res = NewQuery($sql);
		$cnt = $res->FetchRow();
		if ($cnt[0] > 0) return false;
		
		if ($MEDIA_IN_DB || (DirIsWritable($from) && DirIsWritable($to) && FileIsWriteable($from.$file))) {
			// Retrieve the media in which this file is used
			$sql = "SELECT m_media, m_gedrec FROM ".$TBLPREFIX."media WHERE m_file LIKE '".$DBCONN->EscapeQuery($mfrom.$file)."%' AND m_gedfile LIKE '".$GEDCOMID."'";
//			print $sql;
			$res = NewQuery ($sql);
			if ($res->NumRows() > 0) {
				if (!isset($change_id)) $change_id = GetNewXref("CHANGE");
				while ($row = $res->FetchAssoc()) {
					$old = GetSubRecord(1, "1 FILE", $row["m_gedrec"]);
					$oldf = GetGedcomValue("FILE", "1", $old);
					// If a file is moved upwards in the tree, the path will shorten, thus bringing new
					// path elements in the mediadepth scope. This means that if a file is moved, we
					// cannot change the original location in the 1 FILE rec, but must replace it with the
					// Genmod style path.
					// E.g.:
					// 1 FILE /files/pics/sources/I1.jpg with media level 1 and media dir media/:
					// physical file at: media/sources/I1.jpg
					// Moved upwards to media/I1.jpg, the 1 FILE becomes: 1 FILE /files/pics/I1.jpg
					// On import this becomes:
					// media/pics/I1.jpg instead of media/I1.jpg!!!!!!!!
					$new = preg_replace("~".$oldf."~", $mto.$file, $old);
					if (!empty($old) && !empty($new)) {
						ReplaceGedRec($row["m_media"], $old, $new, "FILE", $change_id, $GEDCOMID);
					}
					else return false;
				}
			}
			if (!$MEDIA_IN_DB) {
				// The gedcom records are fixed. Now move the file.
				$success = @copy($from.$file, $to.$file);
				// Dont forget the thumb! No problem if it cannot be moved, it will be deleted now and created later.
				@copy($from."thumbs/".$file, $to."thumbs/".$file);
				if (!$success) {
					WriteToLog("MediaFS->MoveFile: Cannot copy file to its new destination: <br />File: ".$file." From: ".$from." To ".$to, "W", "S");
					return false;
				}
				return $this->DeleteFile($file, $from, false);
			}
			$sql = "UPDATE ".$TBLPREFIX."media_thumbfiles SET mtf_file='".$DBCONN->EscapeQuery($to.$file)."' WHERE mtf_file LIKE '".$DBCONN->EscapeQuery($from.$file)."'";
			$res = NewQuery($sql);
			$sql = "UPDATE ".$TBLPREFIX."media_datafiles SET mdf_file='".$DBCONN->EscapeQuery($to.$file)."' WHERE mdf_file LIKE '".$DBCONN->EscapeQuery($from.$file)."'";
			$res = NewQuery($sql);
			$sql = "UPDATE ".$TBLPREFIX."media_files SET mf_file='".$DBCONN->EscapeQuery($to.$file)."', mf_path='".$DBCONN->EscapeQuery($to)."' WHERE mf_file LIKE '".$DBCONN->EscapeQuery($from.$file)."'";
			$res = NewQuery($sql);
			return true;
		}
	}
	
	function DeleteFile($file, $folder, $fromdb=false) {
		global $MEDIA_IN_DB, $TBLPREFIX, $DBCONN, $MEDIA_DIRECTORY;
	
		if ($fromdb) {
			if ($folder == "external_links") $folder = "";
			$sql = "DELETE FROM ".$TBLPREFIX."media_thumbfiles WHERE mtf_file LIKE '".$DBCONN->EscapeQuery($folder.$file)."'";
			$res = NewQuery($sql);
			$sql = "DELETE FROM ".$TBLPREFIX."media_datafiles WHERE mdf_file LIKE '".$DBCONN->EscapeQuery($folder.$file)."'";
			$res = NewQuery($sql);
			$sql = "DELETE FROM ".$TBLPREFIX."media_files WHERE mf_file LIKE '".$DBCONN->EscapeQuery($folder.$file)."'";
			$res = NewQuery($sql);
			return true;
		}
		else {
			if ($folder == "external_links") $folder = $MEDIA_DIRECTORY."/thumbs/urls/";
			$success = @unlink($folder.$file);
			@unlink($folder."thumbs/".$file);
			return $success;
		}
	}
	
	function DeleteDir($folder, $dbmode=false, $fake=false) {
		global $MEDIA_IN_DB, $TBLPREFIX, $DBCONN;
	
		if ($dbmode) {
			if (!$fake) $sql = "SELECT count(mf_path) as count FROM ".$TBLPREFIX."media_files WHERE mf_path='".$DBCONN->EscapeQuery($folder)."' GROUP BY mf_path";
			else $sql = "SELECT count(mf_path) as count FROM ".$TBLPREFIX."media_files WHERE mf_path LIKE '".$DBCONN->EscapeQuery($folder)."%' GROUP BY mf_path";
			$res = NewQuery($sql);
			// If there are more DIR entries starting with this name, it's not the lowest level. We cannot delete.
			if ($res->NumRows() > 1) return false;
			// If there are more entries for this dir, there are still files in it. We cannot delete it.
			$row = $res->FetchRow();
			if ($row[0] > 1) {
				return false;
			}
			if ($fake) return true;
			$sql = "DELETE FROM ".$TBLPREFIX."media_files WHERE mf_path LIKE '".$DBCONN->EscapeQuery($folder)."' AND mf_file LIKE '<DIR>'";
			$res = NewQuery($sql);
			return true;
		}
		else {
			$d = @dir($folder);
			if (is_object($d)) {
				$candelete = true;
				while (false !== ($entry = $d->read())) {
					if ($entry != "." && $entry != ".." && $entry != "CVS" && $entry != "index.php") {
						$candelete = false;
						break;
					}
				}
				$d->close();
			}
			if (!$candelete) return false;
			if ($fake) return true;
			@unlink($folder."CVS");
			@unlink($folder."index.php");
			$success = @rmdir($folder);
			return $success;
		}
	}
	
	function IsValidMedia($filename) {
		global $MEDIATYPE;
		
		$et = preg_match("/(\.\w+)$/", $filename, $ematch);
		if ($et>0) $ext = substr(trim($ematch[1]),1);
		else $ext = "";
		return in_array(strtolower($ext), $MEDIATYPE);
	}
	
	function FileExists($filename) {
		global $TBLPREFIX, $MEDIA_IN_DB, $DBCONN;
		
		if ($MEDIA_IN_DB) {
			$res = NewQuery("SELECT mf_file FROM ".$TBLPREFIX."media_files WHERE mf_file LIKE '".$DBCONN->EscapeQuery($filename)."'");
			return $res->NumRows();
		}
		else {
			return file_exists($filename);
		}
	}
	
	function GetTotalMediaSize() {
		global $TBLPREFIX, $MEDIA_IN_DB, $DBCONN;
		
		if ($MEDIA_IN_DB) {
			$res = NewQuery("SELECT sum(mf_size) FROM ".$TBLPREFIX."media_files");
			$row = $res->FetchRow();
			return $row[0];
		}
		else {
			return -1;
		}
	}
		
	
}
?>