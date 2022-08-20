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
 * @version $Id: mediafs_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
 
 
abstract class MediaFS {
	
	public $classname 		= "MediaFS";	// Name of the class
	public static $fdetails = array();		// Holder for the file details
	public static $MEDIATYPE = array("a11","acb","adc","adf","afm","ai","aiff","aif","amg","anm","ans","apd","asf","au","avi","awm","bga","bmp","bob","bpt","bw","cal","cel","cdr","cgm","cmp","cmv","cmx","cpi","cur","cut","cvs","cwk","dcs","dib","dmf","dng","doc","dsm","dxf","dwg","emf","enc","eps","fac","fax","fit","fla","flc","fli","fpx","ftk","ged","gif","gmf","hdf","iax","ica","icb","ico","idw","iff","img","jbg","jbig","jfif","jpe","jpeg","jp2","jpg","jtf","jtp","lwf","mac","mid","midi","miff","mki","mmm",".mod","mov","mp2","mp3","mpg","mpt","msk","msp","mus","mvi","nap","ogg","pal","pbm","pcc","pcd","pcf","pct","pcx","pdd","pdf","pfr","pgm","pic","pict","pk","pm3","pm4","pm5","png","ppm","ppt","ps","psd","psp","pxr","qt","qxd","ras","rgb","rgba","rif","rip","rla","rle","rpf","rtf","scr","sdc","sdd","sdw","sgi","sid","sng","swf","tga","tiff","tif","txt","text","tub","ul","vda","vis","vob","vpg","vst","wav","wdb","win","wk1","wks","wmf","wmv","wpd","wxf","wp4","wp5","wp6","wpg","wpp","xbm","xls","xpm","xwd","yuv","zgm"); 	// Valid mediatypes
	
	public function GetMediaDirList($directory="", $all=true, $level=1, $checkwrite=true, $incthumbdir=false, $dbmode="unset") {
		
		if ($dbmode == "unset") $dbmode = SystemConfig::$MEDIA_IN_DB;
		$directory = RelativePathFile($directory);
		$dirs = array();
		if (!empty($directory) && $level = 1) $dirs[] = $directory;
		
		if ($dbmode) {
			$l = preg_split("/\//", $directory);
			$thislevel = count($l)-1;
			$sql = "SELECT mf_path FROM ".TBLPREFIX."media_files";
			if (!empty($directory)) $sql .= " WHERE mf_path LIKE '".$directory."%'";
			else $sql .= " WHERE mf_link NOT LIKE '%://%'";
			$sql .= " AND mf_file='<DIR>' ORDER BY mf_path";
			$res = NewQuery($sql);
			while ($row = $res->FetchRow()) {
				$folders = preg_split("/\//", $row[0]);
				$base = "";
				foreach ($folders as $level => $foldername) {
					if ($level <= GedcomConfig::$MEDIA_DIRECTORY_LEVELS && !empty($foldername)) {
						$base = $base.$foldername."/";
						if (($all || $level == $thislevel) && !in_array($base, $dirs)) $dirs[] = $base;
					}
				}
			}
		}
		else {
			if (empty($directory)) $directory = GedcomConfig::$MEDIA_DIRECTORY;
			//print "Getting : ".$directory."<br />";
			$canwrite = true;
			$exclude_dirs = array(INDEX_DIRECTORY, "./languages/", "./fonts/", "./hooks/", "./images/", "./includes", "./languages/", "./modules/", "./places/", "./reports/", "./ufpdf/", "./themes/", "./blocks/", "./install/", "./includes/", "./pgvnuke/");
			$exclude_mediadirs = explode(",",str_replace(", ",",",GedcomConfig::$MEDIA_DIRECTORY_HIDE));
			if ($level <= GedcomConfig::$MEDIA_DIRECTORY_LEVELS) {
				$d = @dir($directory);
				if (is_object($d)) {
					while (false !== ($entry = $d->read())) {
						if (!in_array($entry, $exclude_mediadirs) && ($incthumbdir || $entry != "thumbs")) {
							$entry = $directory.$entry;
							if(is_dir($entry)) {
								$entry .= "/";
								if ($checkwrite) $canwrite = self::DirIsWritable($entry, false);
								if (!in_array($entry, $exclude_dirs) && $canwrite) {
									if ($all) $dirs = array_merge($dirs, self::GetMediaDirList($entry, $all, $level+1, $checkwrite, $incthumbdir, $dbmode));
									else $dirs[] = $entry;
								}
							}
						}
					}
					$d->close();
				}
			}
		}
		sort($dirs);
		return $dirs;
	}

	public function GetFileList($directory, $filter="", $dbmode=false) {
		
		$dirfiles = array();
		if (!$dbmode) {
			if ($directory == "external_links") {
				// External links pics don't exist in Genmod.
				// We must check the MM items for links instead of files
				$sql = "SELECT m_gedrec FROM ".TBLPREFIX."media WHERE m_gedrec LIKE '%://%'";
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
							if (!is_dir($directory."/".$entry)) {
								// Get the extension
								if (self::IsValidMedia($entry)) {
									if (!empty($filter)) {
										if (stristr($entry, $filter)) {
											$dirfiles[] = RelativePathFile($directory.$entry);
										}
									}
									else $dirfiles[] = RelativePathFile($directory.$entry);
									// print $directory."/".$entry;
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
			$m = RelativePathFile(GedcomConfig::$MEDIA_DIRECTORY);
			if ($directory == "external_links") {
				$sql = "SELECT mf_link FROM ".TBLPREFIX."media_files WHERE";
				$sql .= " mf_link NOT LIKE ''";
				// Reset the directory, as the media items have no path recorded.
				$directory = "";
			}
			else $sql = "SELECT mf_file FROM ".TBLPREFIX."media_files WHERE mf_path LIKE '".$directory."' AND mf_link LIKE ''";
			if (!empty($filter)) $sql .= " AND (mf_file LIKE '%".$filter."%' OR m_titl LIKE '%".$filter."%')"; 
			$sql .= " AND mf_file NOT LIKE '<DIR>' ORDER BY mf_file";
			$res = NewQuery($sql);
			while ($row = $res->FetchRow()) {
				if (!in_array($row[0], $dirfiles)) $dirfiles[] = $row[0];
			}
		}
		return $dirfiles;
	}
		
		
	public function GetMediaFilelist($directory, $filter="", $dbmode="unset") {

 		if ($dbmode == "unset") $dbmode = SystemConfig::$MEDIA_IN_DB;
		$directory = RelativePathFile($directory);
		if ($directory != "external_links") $m = RelativePathFile(GedcomConfig::$MEDIA_DIRECTORY);
		else $m = "";
		$files = array();
		if ($dbmode) {
			// For DB media
			$sql = "SELECT ".TBLPREFIX."media.*, ".TBLPREFIX."media_files.* FROM ".TBLPREFIX."media_files LEFT JOIN ".TBLPREFIX."media ON mf_file=CONCAT('".$m."', m_mfile) WHERE";
			if ($directory == "external_links") {
				$sql .= " mf_link NOT LIKE ''";
			}
			else $sql .= " mf_path LIKE '".$directory."' AND mf_link LIKE ''";
			if (!empty($filter)) $sql .= " AND (mf_file LIKE '%".$filter."%' OR m_titl LIKE '%".$filter."%')"; 
			$sql .= " AND mf_file NOT LIKE '<DIR>' ORDER BY mf_file";
			
			$res = NewQuery($sql);
			while ($row = $res->FetchAssoc()) {
				if ($directory == "external_links") {
					if (!isset($files[$row["mf_link"]]["filedata"])) $files[$row["mf_link"]]["filedata"] = New MFile($row);
					if (!empty($row["m_media"])) {
						$media =& MediaItem::GetInstance($row["m_media"], $row);
						$files[$row["mf_link"]]["objects"][] = $media;
					}
				}
				else {
					if (!isset($files[$row["mf_file"]]["filedata"])) $files[$row["mf_file"]]["filedata"] = New MFile($row);
					if (!empty($row["m_media"])) {
						$media =& MediaItem::GetInstance($row["m_media"], $row);
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
			$dirfiles = self::GetFileList($directory, $filter, $dbmode);
			if (!count($dirfiles)) return $dirfiles;
			$sql = "SELECT * FROM ".TBLPREFIX."media WHERE (";
			$first = true;
			foreach ($dirfiles as $key => $dir) {
				$dir = self::NormalizeLink($dir);
				if (!$first) $sql .= " OR ";
				else $first = false;
				if (!empty($m)) $dir = preg_replace("~".$m."~", "", $dir); 
				$sql .= "m_mfile LIKE '%".DbLayer::EscapeQuery($dir)."'";
			}
			$sql .=")";
			if (!empty($filter)) $sql .= " AND m_mfile LIKE '%".DbLayer::EscapeQuery($filter)."%'"; 
			$sql .= " ORDER BY m_mfile";
			$res = NewQuery($sql);
			
			while ($row = $res->FetchAssoc()) {
				if ($directory != "external_links") $f = $m.RelativePathFile(self::CheckMediaDepth($row["m_mfile"]));
				else $f = GetGedcomValue("FILE", "1", $row["m_gedrec"]);
				if (in_array($f, $dirfiles)) {
					$files[$f]["objects"][] =& MediaItem::GetInstance($row["m_media"], $row);
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
		}
	}
	
	public function PrintViewLink($file, $thumb=false, $paste=false) {
		global $TEXT_DIRECTION;
		
		$fileobj = $file["filedata"];
		$realfile = RelativePathFile($fileobj->f_main_file);
		$thumbfile = $fileobj->f_thumb_file;
		if (SystemConfig::$MEDIA_IN_DB) {
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
			if (!$fileobj->f_is_image || isset($file["objects"])) $thumbfile = self::ThumbNailFile($realfile, SystemConfig::$MEDIA_IN_DB);
			else $thumbfile = $realfile;
		}

		if ($thumb) {
			print "\n\t\t\t<td class=\"ListTableContent\">";
			$twidth = 50;
			$theight = 50;
			self::DispImgLink($realfile, $thumbfile, $fileobj->f_main_file, "", $twidth, $theight, $fileobj->f_width, $fileobj->f_height, $fileobj->f_is_image, $fileobj->f_file_exists);
			print "</td>";
		}
		
		print "<td class=\"ListTableContent\"";
		if (!$thumb) print " colspan=\"2\" ";
		print ">";
		print "<span class=\"FindMediaFileLabel\">".GM_LANG_filename."</span>&nbsp;";
		if (!$paste) print "<span class=\"ListItemName\">".$fileobj->f_file."</span><br />";
		else {
			$m = RelativePathFile(GedcomConfig::$MEDIA_DIRECTORY);
			if (!empty($m)) $plink = preg_replace("~$m~", "", $fileobj->f_pastelink);
			else $plink = $fileobj->f_pastelink;
			print "<a href=\"#\" onclick=\"pasteid('".preg_replace("/'/", "\'", FilenameEncode($plink))."');\"><span class=\"ListItemName FindMediaFileName\">".basename($fileobj->f_file)."</span></a><br />";
		}
		$linked = false;
		if (isset($file["objects"])) {
			foreach ($file["objects"] as $index => $media) {
				if ($media->xref != "") {
					if (!$linked) print "<span class=\"FindMediaFileLabel\">".GM_LANG_used_in."</span><br />";
					if ($media->title != "") $title = "<span class=\"ListItemName FindMediaFileName\">".$media->title."</span> <span class=\"ListItemXref FindMediaFileXref\">(".$media->xref.")</span>";
					else $title = "";
					print "<a href=\"mediadetail.php?mid=".$media->xref."&amp;gedid=".$media->gedcomid."\" target=\"blank\">".PrintReady($title)."</a><br />";
					$linked = true;
				}
			}
		}
		if (!$linked) print "<span class=\"Error\">".GM_LANG_media_not_linked."</span>";
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
	public function ThumbnailFile($filename, $dbmode="unset", $placeholder=true) {
		global $GM_IMAGES;

		if ($dbmode == "unset") $dbmode = SystemConfig::$MEDIA_IN_DB;
		// Removed this line: an empty filename should return a placeholder (if selected)
//		if (strlen($filename) == 0) return false;
		if ($filename == GedcomConfig::$MEDIA_DIRECTORY) $filename = "";
		
		// NOTE: Lets get the file details
		$parts = pathinfo($filename);
		if (isset($parts["dirname"])) $dirname = RelativePathFile($parts["dirname"]."/");
		else $dirname = RelativePathFile(GedcomConfig::$MEDIA_DIRECTORY);
		if (isset($parts["basename"])) $file_basename = $parts["basename"];
		else $file_basename = "";
		if (isset($parts["extension"])) $thumb_extension = $parts["extension"];
		else $thumb_extension = "";
		// We can skip this part for media in DB
		if (!$dbmode && GedcomConfig::$AUTO_GENERATE_THUMBS) {
//			if ((stristr($filename, "://") && !GedcomConfig::$MEDIA_EXTERNAL) || !stristr($filename, "://")) {
				// in case of a link we set the thumb dir to the media directory
				if (stristr($filename, "://")) $dirname = RelativePathFile(GedcomConfig::$MEDIA_DIRECTORY);
				// NOTE: Construct dirname according to media levels to keep
				if (!is_dir($dirname."thumbs/urls/") && $dirname == RelativePathFile(GedcomConfig::$MEDIA_DIRECTORY)) {
					self::CreateDir("thumbs", $dirname); 
					self::CreateDir("urls", $dirname."thumbs/"); 
				}
				if (GedcomConfig::$MEDIA_DIRECTORY_LEVELS == 0) $dirname = RelativePathFile(GedcomConfig::$MEDIA_DIRECTORY);
				if (!is_dir($dirname."thumbs/")&& !stristr($dirname, "thumbs")) {
					// print "dirname: ".$dirname."<br />";
					self::CreateDir("thumbs", $dirname); 
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
				if (!self::GetFileDetails($file_basename, true)) {
					// Nothing in the DB for this external link, first try to make a thumb
					// If not there, no need to proceed
					//print "generating for ".$filename."<br />";
					if (!self::GenerateThumbnail($filename, INDEX_DIRECTORY.$file_basename, $ignoremediadir=true, $ext="")) {
						WriteToLog("MediaFS-&gt;ThumbnailFile&gt; Cannot generate thumbnail from ".$filename.".", "E", "S");
						return "";
					}
					
					// Get the file details
					self::GetFileDetails($file_basename, false);
					
					// it's a link
					self::$fdetails["link"] = $filename;
					self::$fdetails["fname"] = $file_basename;
					
					// Whatever was taken from the file, we know it's a pic
					self::$fdetails["is_image"] = true;
					// Find an extension 
					$exts = preg_split("/;/", self::$fdetails["extension"]);
					$ext = $exts[0];
					if (!empty($ext)) $ext = substr($ext,1);
					
					// Get the thumb details
					self::GetThumbDetails(INDEX_DIRECTORY.$file_basename);
					
					// Store the file information
					self::$fdetails["file"] = $file_basename;
					self::WriteDBFileDetails();
					
					// Store the thumb
					self::WriteDBThumbFile(self::$fdetails["file"], INDEX_DIRECTORY.$file_basename, true, true);
					
					// return the link
					return "showblob.php?file=".urlencode(self::$fdetails["file"])."&amp;type=thumb";
				}
				else {
					// We found the file
					// return the link
					return "showblob.php?file=".urlencode(self::$fdetails["file"])."&amp;type=thumb";
				}
			}
			else {
				$file_basename = self::NormalizeLink($filename);
				if (file_exists(FilenameDecode(GedcomConfig::$MEDIA_DIRECTORY."thumbs/urls/".$file_basename))) {
					return GedcomConfig::$MEDIA_DIRECTORY."thumbs/urls/".$file_basename;
				}
				else {
					if (GedcomConfig::$AUTO_GENERATE_THUMBS && is_dir(GedcomConfig::$MEDIA_DIRECTORY."thumbs/urls/")) {
						if (self::GenerateThumbnail($filename, GedcomConfig::$MEDIA_DIRECTORY."thumbs/urls/".$file_basename)) {
							return GedcomConfig::$MEDIA_DIRECTORY."thumbs/urls/".$file_basename;
						}
						else WriteToLog("MediaFS-&gt;ThumbnailFile&gt; Cannot generate thumbnail from ".$filename.".", "E", "S");
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
				if (file_exists(FilenameDecode($dirname."thumbs/".$file_basename)) && !empty($file_basename)) {
					return $dirname."thumbs/".$file_basename;
				}
				else {
					if (GedcomConfig::$AUTO_GENERATE_THUMBS && is_dir($dirname."thumbs/")) {				
						if (self::GenerateThumbnail($dirname.$file_basename, $dirname."thumbs/".$file_basename)) {
							return $dirname."thumbs/".$file_basename;
						}
					}
				}
			}
		}
		
		if ($placeholder) {
			switch ($thumb_extension) {
				case "pdf":
					return GM_IMAGE_DIR."/".$GM_IMAGES["media"]["pdf"];
					break;
				case "doc":
					return GM_IMAGE_DIR."/".$GM_IMAGES["media"]["doc"];
					break;
				case "ged":
					return GM_IMAGE_DIR."/".$GM_IMAGES["media"]["ged"];
					break;
				default :
					return GM_IMAGE_DIR."/".$GM_IMAGES["media"]["large"];
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
	public function CheckMediaDepth($filename) {
	
		// NOTE: If the media depth is 0, no need to check it
		if (empty($filename) || (GedcomConfig::$MEDIA_EXTERNAL && stristr($filename, "://"))) return $filename;
		
		// remove heading driveletters
		$filename = preg_replace("/^\w:/", "", $filename);
		
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
		if (GedcomConfig::$MEDIA_DIRECTORY_LEVELS == 0 || empty($dirname)) return $file_basename;
		else if (GedcomConfig::$MEDIA_DIRECTORY_LEVELS < $count_dir_levels) {
			for ($ct_level = (GedcomConfig::$MEDIA_DIRECTORY_LEVELS-1); $ct_level >= 0; $ct_level--) {
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
	public function GenerateThumbnail($filename, $thumbnail, $ignoremediadir=false, $ext="") {

		$parts = pathinfo($thumbnail);
		$dirname = RelativePathFile($parts["dirname"]."/");
		if (!GedcomConfig::$AUTO_GENERATE_THUMBS) return false;
		if (!SystemConfig::$MEDIA_IN_DB && file_exists($thumbnail)) return false;
		
		if (!$ignoremediadir && !self::DirIsWritable($dirname, false)) return false;
	
		if (!$ignoremediadir && strstr($filename, "://") && !is_dir(GedcomConfig::$MEDIA_DIRECTORY."thumbs/urls")) {
			self::CreateDir(GedcomConfig::$MEDIA_DIRECTORY, "thumbs");
			self::CreateDir(GedcomConfig::$MEDIA_DIRECTORY."thums/", "urls");
			WriteToLog("MediaFS-&gt;GenerateThumbnail-&gt; Folder ".GedcomConfig::$MEDIA_DIRECTORY."thumbs/urls/ created.", "I", "S");
		}
		if (!strstr($filename, "://")) {
			if (!file_exists($filename)) {
				WriteToLog("MediaFS-&gt;GenerateThumbnail-&gt; Cannot create thumb from non-existent file: ".$filename, "E");
				return false;
			}
			if (!@fopen($filename, "r")) {
				WriteToLog("MediaFS-&gt;GenerateThumbnail-&gt; Cannot open file for creating thumb: ".$filename.". Check permissions.", "E");
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
			if (!$ignoremediadir && !self::DirIsWritable(GedcomConfig::$MEDIA_DIRECTORY."thumbs/urls", false)) return false;
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
		$width = GedcomConfig::$THUMBNAIL_WIDTH;
		$height = round($imgsize[1] * ($width/$imgsize[0]));
		$ct = preg_match("/\.([^\.]+)$/", $filename, $match);
		if ($ct>0) {
			if (empty($ext)) $ext = strtolower(trim($match[1]));
			if ($ext=="gif") {
				if (function_exists("imagecreatefromgif") && function_exists("imagegif")) {
					WriteToLog("MediaFS-&gt;GenerateThumbnail-&gt; Create thumb attempt for ".$filename, "I", "S");
					$im = @imagecreatefromgif($filename);
					if (empty($im)) {
						WriteToLog("MediaFS-&gt;GenerateThumbnail-&gt; There were problems creating thumb for ".$filename, "W", "S");
						return false;
					}
					$new = imagecreatetruecolor($width, $height);
					imagecopyresampled($new, $im, 0, 0, 0, 0, $width, $height, $imgsize[0], $imgsize[1]);
					imagegif($new, $thumbnail);
					imagedestroy($im);
					imagedestroy($new);
					WriteToLog("MediaFS-&gt;GenerateThumbnail-&gt; Created thumb for ".$filename, "I", "S");
					return true;
				}
			}
			else if ($ext=="jpg" || $ext=="jpeg") {
				if (function_exists("imagecreatefromjpeg") && function_exists("imagejpeg")) {
					WriteToLog("MediaFS->GenerateThumbnail: Create thumb attempt for ".$filename, "I", "S");
					$im = @imagecreatefromjpeg($filename);
					if (empty($im)) {
						WriteToLog("MediaFS-&gt;GenerateThumbnail-&gt; There were problems creating thumb for ".$filename, "W", "S");
						return false;
					}
					$new = imagecreatetruecolor($width, $height);
					imagecopyresampled($new, $im, 0, 0, 0, 0, $width, $height, $imgsize[0], $imgsize[1]);
					imagejpeg($new, $thumbnail);
					imagedestroy($im);
					imagedestroy($new);
					WriteToLog("MediaFS-&gt;GenerateThumbnail-&gt; Created thumb for ".$filename, "I", "S");
					return true;
				}
			}
			else if ($ext=="png") {
				if (function_exists("imagecreatefrompng") && function_exists("imagepng")) {
					WriteToLog("MediaFS-&gt;GenerateThumbnail-&gt; Create thumb attempt for ".$filename, "I", "S");
					$im = @imagecreatefrompng($filename);
					if (empty($im)) {
						WriteToLog("MediaFS-&gt;GenerateThumbnail-&gt; There were problems creating thumb for ".$filename, "W", "S");
						return false;
					}
					$new = imagecreatetruecolor($width, $height);
					imagecopyresampled($new, $im, 0, 0, 0, 0, $width, $height, $imgsize[0], $imgsize[1]);
					imagepng($new, $thumbnail);
					imagedestroy($im);
					imagedestroy($new);
					WriteToLog("MediaFS-&gt;GenerateThumbnail-&gt; Created thumb for ".$filename, "I", "S");
					return true;
				}
			}
		}
	
		return false;
	}
	
	
	public function UploadFiles($files, $path, $overwrite=false) {
		global $error;
		
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
		
		if (count($files) > 0) {
			$upload_errors = array(GM_LANG_file_success, GM_LANG_file_too_big, GM_LANG_file_too_big,GM_LANG_file_partial, GM_LANG_file_missing);
			if (!empty($path)) {
				$path = RelativePathFile(self::CheckMediaDepth($path));
				if (!empty($path) && substr($path,-1,1) != "/") $path .= "/";
			}
			foreach($files as $type =>$upload) {
				// print $type."<br />";
				// print_r($upload);
				// print "<br /><br />";
				if (!empty($upload["name"])) {
					
					// first get the details of the uploaded file
					$fname = basename($upload["name"]);
					$file = $path.$fname;
					$et = preg_match("/(\.\w+)$/", $fname, $ematch);
					if ($et>0) $ext = substr(trim($ematch[1]),1);
					else $ext = "";
					
					// Is such a file allowed?
					if (!self::IsValidMedia($file)) {
						WriteToLog("MediaFS-&gt;UploadFiles-&gt; Illegal upload attempt. File: ".$file, "W", "S");
						$result["error"] = GM_LANG_ext_not_allowed;
						$result["errno"] = 5;
						unlink($upload["tmp_name"]);
					}
					else {
						if (file_exists($upload["tmp_name"]) && $upload["size"] > 0) {
							
							if (SystemConfig::$MEDIA_IN_DB) {
							
								// check if already exists
								$exists = self::GetFileDetails($file, true);
								if (!$exists || $overwrite) {
								
									// Get the new details
									
									// Try to create a thumb
									$thumb = INDEX_DIRECTORY.basename($upload["name"]);
									GedcomConfig::$AUTO_GENERATE_THUMBS = true;
									$hasthumb = self::GenerateThumbNail($upload["tmp_name"], $thumb, true, $ext);
							
									// and get the details
									if ($hasthumb) self::GetThumbDetails($thumb);
							
									// Add the file attributes to the virtual directory
									self::GetFileDetails($upload["tmp_name"], false);
									// assign the name if new. Also when old, because GetFileDetails of the upload file 
									// returns path of the PHP upload directory
									self::$fdetails["file"] = $file;
									self::$fdetails["path"] = $path;
									self::$fdetails["fname"] = $fname;
									self::WriteDBFileDetails($exists);
							
									// Add the file to the DB
									self::WriteDBDatafile($upload["tmp_name"], $exists);
							
									// Add the thumb to the DB
									if ($hasthumb) self::WriteDBThumbfile(self::$fdetails["file"], $thumb, $exists, true);
								}
								else {
									$result["error"] = GM_LANG_file_exists;
									$result["errno"] = 6;
								}
								
							}
							else {	
								// Real file system mode
								$exists = self::GetFileDetails($file, false);
								if (!$exists || $overwrite) {
									if ($exists) self::DeleteFile($fname, $path, SystemConfig::$MEDIA_IN_DB);
									if (!empty($upload['tmp_name'])) {
										if (!move_uploaded_file($upload['tmp_name'], $file)) {
											$result["error"] = "<br />".GM_LANG_upload_error."<br />".$upload_errors[$upload['error']];
											$result["errno"] = $upload['error'];
										}
										else {
											$thumbnail = self::ThumbnailFile($file);
										}
									}
								}
								else {
									$result["error"] = GM_LANG_file_exists;
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
//						print "<span class=\"Error\">".$error."</span><br />";
						return $result;
					}
					$result["error"] = GM_LANG_upload_successful;
					$result["filename"] = $file;
					return $result;
				}
			}
		}
		// Nothing uploaded but no errors
		$result["errno"] = 0;
		return $result;
	}
	
	public function GetFileDetails($filename, $dbmode="unset") {
		
		if ($dbmode == "unset") $dbmode = SystemConfig::$MEDIA_IN_DB;

		if ($dbmode) {
			self::$fdetails = array();
			self::$fdetails["file"] = "";
			self::$fdetails["path"] = "";
			self::$fdetails["fname"] = "";
			self::$fdetails["is_image"] = false;
			self::$fdetails["width"] = "0";
			self::$fdetails["height"] = "0";
			self::$fdetails["bits"] = 0;
			self::$fdetails["channels"] = 0;
			self::$fdetails["size"] = "0";
			self::$fdetails["twidth"] = "0";
			self::$fdetails["theight"] = "0";
			self::$fdetails["tsize"] = "0";
			self::$fdetails["mimetype"] = "";
			self::$fdetails["mimedesc"] = "";
			self::$fdetails["link"] = "";
					
			$sql = "SELECT * FROM ".TBLPREFIX."media_files WHERE mf_file='".$filename."'";
			$res = NewQuery($sql);
			if ($res->NumRows() > 0) {
				$row = $res->FetchAssoc();
				foreach (self::$fdetails as $index => $value) {
					self::$fdetails[$index] = $row["mf_".$index];
				}
				return self::$fdetails;
			}
			return false;
		}
		else {		
			// Initialise
			self::$fdetails["width"] = 0;
			self::$fdetails["height"] = 0;
			self::$fdetails["size"] = 0;
			self::$fdetails["bits"] = 0;
			self::$fdetails["channels"] = 0;
			self::$fdetails["mimetype"] = "";
			self::$fdetails["mimedesc"] = "";
			self::$fdetails["extension"] = "";
			self::$fdetails["is_image"] = false;
			self::$fdetails["link"] = false;
			
			// and get the details
			if (file_exists($filename)) {
				if (stristr($filename, "://")) self::$fdetails["link"] = true;
				else self::$fdetails["link"] = false;
				$path = pathinfo($filename);
				self::$fdetails["path"] = $path["dirname"];
				if (!empty(self::$fdetails["path"]) && substr(self::$fdetails["path"],-1) != "/") self::$fdetails["path"] = self::$fdetails["path"]."/";
				self::$fdetails["fname"] = $path["basename"];
				self::$fdetails["size"] = @filesize($filename);
				if ($file_details = @getimagesize($filename)) {
					self::$fdetails["width"] = $file_details[0];
					self::$fdetails["height"] = $file_details[1];
					self::$fdetails["bits"] = $file_details["bits"];
					if (isset($file_details["channels"])) self::$fdetails["channels"] = $file_details["channels"];
					self::$fdetails["is_image"] = true;
				}
				$mimetypedetect = New MimeTypeDetect();
				$mime = $mimetypedetect->FindMimeType($filename);
				if (is_array($mime)) {
					self::$fdetails["mimetype"] = $mime["mime_type"];
					self::$fdetails["mimedesc"] = $mime["description"];
					$exts = preg_split("/;/", $mime["extension"]);
					$ext = $exts[0];
					if (!empty($ext)) $ext = substr($ext,1);
					self::$fdetails["extension"] = $ext;
				}
				return self::$fdetails;
			}
			else return false;
		}
	}
	
	public function GetThumbDetails($filename) {

		// Initialise
		self::$fdetails["twidth"] = 0;
		self::$fdetails["theight"] = 0;
		self::$fdetails["tsize"] = 0;
		
		// and get the details
		self::$fdetails["tsize"] = @filesize($filename);
		if ($file_details = @getimagesize($filename)) {
			self::$fdetails["twidth"] = $file_details[0];
			self::$fdetails["theight"] = $file_details[1];
		}
		return self::$fdetails;
	}
	
	public function UpdateDBThumbDetails($file, $size, $width, $height) {

		$sql = "UPDATE ".TBLPREFIX."media_files SET mf_twidth='".$width."', mf_theight='".$height."', mf_tsize='".$size."' WHERE mf_file='".$file."'";
		$res = NewQuery($sql);
		if ($res) return true;
		else return false;
	}
				
	
	public function WriteDBFileDetails($delete=false) {

		// delete old data
		if ($delete) {
			$sql = "DELETE FROM ".TBLPREFIX."media_files WHERE mf_file='".self::$fdetails["file"]."'";
			$ires = NewQuery($sql);
		}
		
		// Insert the file details
		$sql = "INSERT INTO ".TBLPREFIX."media_files (mf_id, mf_file, mf_path, mf_fname, mf_is_image, mf_width, mf_height, mf_bits, mf_channels, mf_size, mf_twidth, mf_theight, mf_tsize, mf_mimetype, mf_mimedesc, mf_link) VALUES ('0', '".self::$fdetails["file"]."', '".self::$fdetails["path"]."', '".self::$fdetails["fname"]."', '".self::$fdetails["is_image"]."', '".self::$fdetails["width"]."', '".self::$fdetails["height"]."', '".self::$fdetails["bits"]."', '".self::$fdetails["channels"]."', '".self::$fdetails["size"]."', '".self::$fdetails["twidth"]."', '".self::$fdetails["theight"]."', '".self::$fdetails["tsize"]."', '".self::$fdetails["mimetype"]."', '".self::$fdetails["mimedesc"]."', '".self::$fdetails["link"]."')";
		$ires = NewQuery($sql);
		if ($ires) return true;
		else return false;
	}
	
	public function WriteDBDatafile($data, $delete=false, $unlink=false) {
		
		$fp = fopen(FileNameEncode($data), "rb");
		if ($fp) {
			if ($delete) {
				// Clean previous versions
				$sql = "DELETE FROM ".TBLPREFIX."media_datafiles WHERE mdf_file='".self::$fdetails["file"]."'";
				$ires = NewQuery($sql);
				$sql = "DELETE FROM ".TBLPREFIX."media_thumbfiles WHERE mtf_file='".self::$fdetails["file"]."'";
				$ires = NewQuery($sql);
			}
			// Insert the new one
			while (!feof($fp)) {
				// Make the data mysql insert safe
				$binarydata = DbLayer::EscapeQuery(fread($fp, 65535));

				$sql = "INSERT INTO ".TBLPREFIX."media_datafiles (mdf_id, mdf_file, mdf_data) VALUES (";
				$sql .= "'0', '".self::$fdetails["file"]."', '".$binarydata."')";
				$ires = NewQuery($sql);
				if (!$ires) return false;
			}
			fclose($fp);
		}
		else return false;
		
		if ($unlink) return @unlink($data);
		else return true;
	}
	
	public function WriteDBThumbfile($file, $thumb, $delete=false, $unlink=false) {

		if (empty($thumb)) return false;
						
		$fp = fopen(FileNameEncode($thumb), "rb");
		if ($fp) {
			if ($delete) {
				// Clean previous versions
				$sql = "DELETE FROM ".TBLPREFIX."media_thumbfiles WHERE mtf_file='".$file."'";
				$ires = NewQuery($sql);
			}
			// Insert the new one
			while (!feof($fp)) {
				// Make the data mysql insert safe
				$binarydata = DbLayer::EscapeQuery(fread($fp, 65535));

				$sql = "insert into ".TBLPREFIX."media_thumbfiles (mtf_id, mtf_file, mtf_data) values (";
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

	public function CreateFile($filename, $delete=false, $dbmode=false, $delonimport=false, $exportthum="no") {

		// File system to DB
		if ($dbmode) {

			if (!$delete &&	self::GetFileDetails($filename, true)) {
				WriteToLog("MediaFS-&gt;CreateFile-&gt; File already exists in DB: ".$filename, "W", "S");
				return false;
			}
			
//			$realfile = RelativePathFile(GedcomConfig::$MEDIA_DIRECTORY.self::CheckMediaDepth($filename));
//			self::$fdetails["file"] = $realfile;
			self::$fdetails["file"] = $filename;
			if (!self::GetFileDetails($filename, false)) {
				WriteToLog("MediaFS-&gt;CreateFile-&gt; Error getting file details of ".$filename, "W", "S");
				return false;
			}
			self::CreateDir(self::$fdetails["path"], "", true);
			
			if (self::$fdetails["is_image"]) {
				$thumb = self::ThumbNailFile($filename, false, false);
				self::GetThumbDetails($thumb);
			}
			else self::GetThumbDetails("");
		
			$success = self::WriteDBFileDetails($delete);
			if (!$success) {
				WriteToLog("MediaFS-&gt;CreateFile-&gt; Error writing media file details for ".$filename, "W", "S");
				return false;
			}
			else $success = self::WriteDBDatafile($filename, $delete, $delonimport);
			if (!$success) {
				WriteToLog("MediaFS-&gt;CreateFile-&gt; Error writing media file data for ".$filename, "W", "S");
				return false;
			}
			else if (self::$fdetails["is_image"] && !empty($thumb)) {
				$success = self::WriteDBThumbfile(self::$fdetails["file"], $thumb, $delete, $delonimport);
				if (!$success) {
					WriteToLog("MediaFS-&gt;CreateFile-&gt; Error writing media thumb data for ".$filename, "W", "S");
					return false;
				}
			}
			return true;
		}
		else {
			// Get the details of the FS-file
			if (self::GetFileDetails($filename, false)) {
				// If present and we don't overwrite, return error
				if (!$delete) {
					WriteToLog("MediaFS->CreateFile-&gt; File already exists in filesys: ".$filename, "W", "S");
					return false;
				}
				else {
					// Else delete the FS-file
					if (!self::DeleteFile(self::$fdetails["fname"], self::$fdetails["path"], false)) {
						WriteToLog("MediaFS-&gt;CreateFile-&gt; Cannot delete from filesys: ".$filename, "W", "S");
						return false;
					}
				}
			}
			// Get the details of the files in the DB
			$islink = stristr($filename, "://");
			
			if ($islink) {
				$realfile = self::NormalizeLink($filename);
			}
			else {
//				$realfile = RelativePathFile(GedcomConfig::$MEDIA_DIRECTORY.self::CheckMediaDepth($filename));
				$realfile = $filename;
			}
//			print $realfile."<br />";
			self::$fdetails["file"] = $realfile;
			if (!self::GetFileDetails($realfile, true)) {
				WriteToLog("MediaFS-&gt;CreateFile-&gt; Error getting DB file details of ".$filename, "W", "S");
				return false;
			}
			// Create the directory in file mode
			if (empty(self::$fdetails["path"])) self::$fdetails["path"] = GedcomConfig::$MEDIA_DIRECTORY;
			// 1. main directory
			if (!self::CreateDir(self::$fdetails["path"], "", false)) {
				WriteToLog("MediaFS-&gt;CreateFile-&gt; Cannot create folder ".self::$fdetails["path"], "W", "S");
				return false;
			}
			// 2. Thumb directory
			if (!self::CreateDir("thumbs", self::$fdetails["path"], false)) {
				WriteToLog("MediaFS-&gt;CreateFile-&gt; Cannot create folder ".self::$fdetails["path"]."thumbs", "W", "S");
				return false;
			}
			// 3. If llink, url directory
			if ($islink && !self::CreateDir("urls", self::$fdetails["path"]."thumbs/", false)) {
				WriteToLog("MediaFS-&gt;CreateFile-&gt; Cannot create folder ".self::$fdetails["path"]."thumbs/urls", "W", "S");
				return false;
			}
			if (!self::DirIsWritable(self::$fdetails["path"], false)) {
				WriteToLog("MediaFS-&gt;CreateFile-&gt; Folder not writable: ".self::$fdetails["path"],"W", "S");
				return false;
			}
			if ($exportthum != "no" && !self::DirIsWritable(self::$fdetails["path"]."thumbs", false)) {
				WriteToLog("MediaFS-&gt;CreateFile-&gt; Folder not writable: ".self::$fdetails["path"]."thumbs", "W", "S");
				return false;
			}
			if ($islink && $exportthum != "no" && !self::DirIsWritable(GedcomConfig::$MEDIA_DIRECTORY."thumbs/urls", false)) {
				WriteToLog("MediaFS-&gt;CreateFile-&gt; Folder not writable: ".GedcomConfig::$MEDIA_DIRECTORY."thumbs/urls", "W", "S");
				return false;
			}
			
			// copy the file from DB to filesys
			if (!$islink) {
				$sql = "SELECT mdf_data FROM ".TBLPREFIX."media_datafiles WHERE mdf_file='".$realfile."' ORDER BY mdf_id ASC";
				$res = NewQuery($sql);
				if ($res) {
					$fp = @fopen($realfile, "wb");
					while ($row = $res->FetchRow()) {
						fwrite($fp, $row[0]);
					}
					fclose($fp);
				}
			}
			if ($exportthum == "yes" && self::$fdetails["is_image"]) {
				$sql = "SELECT mtf_data FROM ".TBLPREFIX."media_thumbfiles WHERE mtf_file='".$realfile."' ORDER BY mtf_id ASC";
				$res = NewQuery($sql);
				if ($res && $res->NumRows() > 0) {
					if ($islink) $fp = @fopen(GedcomConfig::$MEDIA_DIRECTORY."thumbs/urls/".self::$fdetails["file"], "wb");
					else $fp = @fopen(self::$fdetails["path"]."thumbs/".self::$fdetails["fname"], "wb");
					while ($row = $res->FetchRow()) {
						fwrite($fp, $row[0]);
					}
					fclose($fp);
				}
			}
			
			if ($delonimport) self::DeleteFile($realfile, "", true);
			
			return true;
		}
	}
	
	public function NormalizeLink($link) {
		if (stristr($link, "://")) return preg_replace(array("/http:\/\//", "/\//"), array("","_"),$link);
		else return $link;
	}

	public function CreateDir($dir, $parent, $dbmode=false, $recurse=false) {
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
					if (!self::CreateDir($directory, $basedir, $dbmode, true)) {
						WriteToLog("MediaFS-&gt;CreateDir-&gt; Cannot create folder ".$basedir.$directory." for tree ".$parent.$dir, "W", "S");
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
			$sql = "SELECT * FROM ".TBLPREFIX."media_files WHERE mf_path='".$parent.$dir."' AND mf_file='<DIR>'";
			$res = NewQuery($sql);
			if ($res->NumRows() > 0 ) {
				$dirlist[$dbmode][] = $parent.$dir;
				return true;
			}
			$sql = "INSERT INTO ".TBLPREFIX."media_files (mf_file, mf_path) VALUES('<DIR>', '".$parent.$dir."')";
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
				else WriteToLog("MediaFS-&gt;CreateDir-&gt; Created folder ".$parent.$dir, "I", "S");

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
	
	public function MoveFile($file, $from, $to) {
		static $change_id;
		
		$file = basename($file);
		// We must have 2 paths: 
		// 1. for the physical file, including the media directory path
		// 2. for the path stored with the media item, excluding the media directory path
		// The incoming parameters are 1., so we must calculate 2.
		$m = RelativePathFile(GedcomConfig::$MEDIA_DIRECTORY);
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
		if (self::FileExists($to.$file)) return false;
		
		// Check is any changes for this file are pending. If so, we cannot move the stuff.
		$sql = "SELECT count(ch_id) FROM ".TBLPREFIX."changes WHERE ch_new LIKE '%1 FILE ".$mfrom.$file."%'";
		$res = NewQuery($sql);
		$cnt = $res->FetchRow();
		if ($cnt[0] > 0) return false;
		
		if (SystemConfig::$MEDIA_IN_DB || (self::DirIsWritable($from, false) && self::DirIsWritable($to, false) && AdminFunctions::FileIsWriteable($from.$file))) {
			// Retrieve the media in which this file is used
			$sql = "SELECT m_media, m_gedrec FROM ".TBLPREFIX."media WHERE m_mfile LIKE '".DbLayer::EscapeQuery($mfrom.$file)."%' AND m_file LIKE '".GedcomConfig::$GEDCOMID."'";
//			print $sql;
			$res = NewQuery ($sql);
			if ($res->NumRows() > 0) {
				if (!isset($change_id)) $change_id = EditFunctions::GetNewXref("CHANGE");
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
						EditFunctions::ReplaceGedRec($row["m_media"], $old, $new, "FILE", $change_id, "edit_fact", GedcomConfig::$GEDCOMID, "OBJE");
					}
					else return false;
				}
			}
			if (!SystemConfig::$MEDIA_IN_DB) {
				// The gedcom records are fixed. Now move the file.
				$success = @copy($from.$file, $to.$file);
				// Dont forget the thumb! No problem if it cannot be moved, it will be deleted now and created later.
				@copy($from."thumbs/".$file, $to."thumbs/".$file);
				if (!$success) {
					WriteToLog("MediaFS-&gt;MoveFile-&gt; Cannot copy file to its new destination: <br />File: ".$file." From: ".$from." To ".$to, "W", "S");
					return false;
				}
				return self::DeleteFile($file, $from, false);
			}
			$sql = "UPDATE ".TBLPREFIX."media_thumbfiles SET mtf_file='".DbLayer::EscapeQuery($to.$file)."' WHERE mtf_file LIKE '".DbLayer::EscapeQuery($from.$file)."'";
			$res = NewQuery($sql);
			$sql = "UPDATE ".TBLPREFIX."media_datafiles SET mdf_file='".DbLayer::EscapeQuery($to.$file)."' WHERE mdf_file LIKE '".DbLayer::EscapeQuery($from.$file)."'";
			$res = NewQuery($sql);
			$sql = "UPDATE ".TBLPREFIX."media_files SET mf_file='".DbLayer::EscapeQuery($to.$file)."', mf_path='".DbLayer::EscapeQuery($to)."' WHERE mf_file LIKE '".DbLayer::EscapeQuery($from.$file)."'";
			$res = NewQuery($sql);
			return true;
		}
	}
	
	public function DeleteFile($file, $folder, $fromdb="unset") {
	
		if ($fromdb == "unset") $fromdb = SystemConfig::$MEDIA_IN_DB;
		if ($fromdb) {
			if ($folder == "external_links") $folder = "";
			$sql = "DELETE FROM ".TBLPREFIX."media_thumbfiles WHERE mtf_file LIKE '".DbLayer::EscapeQuery($folder.$file)."'";
			$res = NewQuery($sql);
			$sql = "DELETE FROM ".TBLPREFIX."media_datafiles WHERE mdf_file LIKE '".DbLayer::EscapeQuery($folder.$file)."'";
			$res = NewQuery($sql);
			$sql = "DELETE FROM ".TBLPREFIX."media_files WHERE mf_file LIKE '".DbLayer::EscapeQuery($folder.$file)."'";
			$res = NewQuery($sql);
			return true;
		}
		else {
			if ($folder == "external_links") $folder = GedcomConfig::$MEDIA_DIRECTORY."/thumbs/urls/";
			$success = @unlink($folder.$file);
			@unlink($folder."thumbs/".$file);
			return $success;
		}
	}
	
	public function DeleteDir($folder, $dbmode=false, $fake=false) {
	
		if ($dbmode) {
			if (!$fake) $sql = "SELECT count(mf_path) as count FROM ".TBLPREFIX."media_files WHERE mf_path='".DbLayer::EscapeQuery($folder)."' GROUP BY mf_path";
			else $sql = "SELECT count(mf_path) as count FROM ".TBLPREFIX."media_files WHERE mf_path LIKE '".DbLayer::EscapeQuery($folder)."%' GROUP BY mf_path";
			$res = NewQuery($sql);
			// If there are more DIR entries starting with this name, it's not the lowest level. We cannot delete.
			if ($res->NumRows() > 1) return false;
			// If there are more entries for this dir, there are still files in it. We cannot delete it.
			$row = $res->FetchRow();
			if ($row[0] > 1) {
				return false;
			}
			if ($fake) return true;
			$sql = "DELETE FROM ".TBLPREFIX."media_files WHERE mf_path LIKE '".DbLayer::EscapeQuery($folder)."' AND mf_file LIKE '<DIR>'";
			$res = NewQuery($sql);
			return true;
		}
		else {
			$d = @dir($folder);
			if (is_object($d)) {
				$candelete = true;
				while (false !== ($entry = $d->read())) {
					if ($entry != ".." && $entry != "." && $entry != "CVS" && $entry != ".svn" && $entry != "_svn" &&  $entry != "index.php") {
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
	
	public function IsValidMedia($filename) {
		
		$et = preg_match("/(\.\w+)$/", $filename, $ematch);
		if ($et>0) $ext = substr(trim($ematch[1]),1);
		else $ext = "";
		return in_array(strtolower($ext), self::$MEDIATYPE);
	}
	
	public function FileExists($filename) {
		
		if (SystemConfig::$MEDIA_IN_DB) {
			$res = NewQuery("SELECT mf_file FROM ".TBLPREFIX."media_files WHERE mf_file LIKE '".DbLayer::EscapeQuery($filename)."'");
			return $res->NumRows();
		}
		else {
			return file_exists($filename);
		}
	}
	
	public function GetTotalMediaSize() {
		
		if (SystemConfig::$MEDIA_IN_DB) {
			$res = NewQuery("SELECT sum(mf_size) FROM ".TBLPREFIX."media_files");
			$row = $res->FetchRow();
			return $row[0];
		}
		else {
			return -1;
		}
	}
	
	public function GetStorageType() {
		
		if (SystemConfig::$MEDIA_IN_DB) return " VFS";
		else return "";
	}

	// This functions checks if an existing directory is physically writeable
	// The standard PHP function only checks for the R/O attribute and doesn't
	// detect authorisation by ACL.
	public function DirIsWritable($dir, $dbmode="unset") {
		
		if ($dbmode == "unset") $dbmode = SystemConfig::$MEDIA_IN_DB;
		
		if (!$dbmode) {
			if (substr($dir,-1) !="/") $dir .="/";
			$err_write = false;
			$handle = @fopen($dir."foo.txt","w+");
			if	($handle) {
				$i = fclose($handle);
				@unlink($dir."foo.txt");
				$err_write = true;
			}
			return($err_write);
		}
		else return true;
	}
	
	public function DispImgLink($filename, $thumbname, $title, $setname, $width, $height, $fullwidth, $fullheight, $is_image, $file_exists, $closelink=true, $center=false) {
		global $TEXT_DIRECTION;
		
		$close = false;
		if ($file_exists) {
			if (USE_GREYBOX && $is_image) {
				print "<a href=\"".FilenameEncode($filename)."\" title=\"".$title."\" rel=\"gb_imageset[".$setname."]\">";
				$close = true;
			}
			elseif (!USE_GREYBOX) {
				print "<a href=\"#\" onclick=\"return openImage('".$filename."','".$fullwidth."','".$fullheight."', '".$is_image."');\">";
				$close = true;
			}
		}
		if (!empty($thumbname)) print "<img src=\"".FilenameEncode($thumbname)."\" border=\"0\" ".($width > 0 ? "width=\"".$width."\" " : "").($height > 0 ? "height=\"".$height."\" " : "")." class=\"Thumbnail\" ".($center ? "" : "align=\"".($TEXT_DIRECTION == "rtl" ? "right" : "left")."\"")." alt=\"\" />";
		if ($close) {
			if ($closelink) print "</a>\n";
			return true;
		}
		else return false;
	}
}

?>