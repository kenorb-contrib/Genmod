<?php
/**
 * Class file for media objects
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
 * @version $Id: mfile_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
 
class MFile {
	
	public $classname = "MFile";	// Name of this class
	
	private $f_id = null;				// ID of the file in the database (if media in DB) - class internal
	private $f_file = null;				// Complete path and filename/ext including the media directory OR URL to the external file
	private $f_fname = null;			// filename and extension of the file (not set if external file) - class internal
	private $f_path = null;				// Psysical pathname to the file (including the media directory) (not set if external file) - class internal
	private $f_ext = null;				// Extension of the file
	
	private $f_main_file = null;		// Physical location (including mediadir) of the file, or showblob with correct params (if media in DB) OR URL to the external file
	private $f_width = 0;				// Width of the file (not set if external file)
	private $f_height = 0;				// Height of the file (not set if external file)
	private $f_bits = null;				// Bit rate of the file (not set if external file)
	private $f_channels = null;			// Color channels of the file (not set if external file)
	private $f_file_size = 0;			// Size of the file in bytes (not set if external file)
	
	private $f_thumb_file = null;		// Physical location (including mediadir) of the thumnailfile, or showblob with correct params (if media in DB)
	private $f_twidth = 0;				// Width of the thumbfile
	private $f_theight = 0;				// Height of the thumbfile
	private $f_tfile_size = 0;			// Size of the thumbfile in bytes - class internal
	
	private $f_mimetype = null;			// Mimetype of the file (not set if external file)
	private $f_mimedescr = null;		// Descriptive mimetype of the file (not set if external file)
	private $f_is_image = false;		// Wether the file is an image and can be displayed
	private $f_file_exists = false;		// Wether the file exists or not
	private $f_link = null;				// URL of external file (not set for internal file)
	private $f_pastelink = null;		// path/file for pasting the filename. Without the media directory! OR URL to external file

		
	public function __construct($file) {
		
		if (SystemConfig::$MEDIA_IN_DB) {
			if (!is_array($file)) {
				if (stristr($file, "://")) {
					// If link, normalize it so we know the DB filename
					$linkfile = $file;
					$file = MediaFS::NormalizeLink($file);
				}
				else {
					// Strip heading stuff
					$file = RelativePathFile($file);
				}
				$sql = "SELECT * FROM ".TBLPREFIX."media_files WHERE mf_file='".$file."'";
				$res = NewQuery($sql);
				// We find nothing if 
				// - link, and no thumb exists
				// - no file uploaded yet
				// If link, we try to create a thumbnail and try again.
				if ($res->NumRows() == 0) {
					if (isset($linkfile)) {
						$thumb = MediaFS::ThumbNailFile($linkfile);
					}
					if (!empty($thumb)) {
						$sql = "SELECT * FROM ".TBLPREFIX."media_files WHERE mf_file='".$file."'";
						$res = NewQuery($sql);
					}
				}
				if ($res->NumRows() > 0) {
					$file = $res->FetchAssoc();
				}
				$res->FreeResult();
			}
			if (is_array($file)) {
				$this->f_id = $file["mf_id"];
				$this->f_file = $file["mf_file"];
				// Get the extension
				$et = preg_match("/(\.\w+)$/", $this->f_file, $ematch);
				if ($et>0) $this->f_ext = substr(trim($ematch[1]),1);
				$this->f_is_image = $file["mf_is_image"];
				$this->f_path = $file["mf_path"];
				$this->f_fname = $file["mf_fname"];
				$this->f_tfile_size = $file["mf_tsize"];
				$this->f_bits = $file["mf_bits"];
				$this->f_channels = $file["mf_channels"];
				// If the thumbimage exists, set the link
				// If not, and it's an image, generate it
				// If not an image, link the placeholder
//				print_r($this);
				if ($this->f_is_image && $this->f_tfile_size != 0) $this->f_thumb_file = "showblob.php?file=".urlencode($this->f_file)."&amp;type=thumb";
				else if ($this->f_is_image) {
//					print "Is image with no thumb: ".$this->f_file."<br />";
					$thumb = INDEX_DIRECTORY.$this->f_fname;
					// copy the file from DB to filesys
					$sql = "SELECT mdf_data FROM ".TBLPREFIX."media_datafiles WHERE mdf_file='".$this->f_file."' ORDER BY mdf_id ASC";
					$res = NewQuery($sql);
					if ($res) {
						$fp = @fopen($thumb, "wb");
						while ($row = $res->FetchRow()) {
							fwrite($fp, $row[0]);
						}
						fclose($fp);
					}
					$hasthumb = MediaFS::GenerateThumbNail($thumb, $thumb."_t", true);
					@unlink($thumb);
					if ($hasthumb) {
//						print "Making thumb for: ".$this->f_file."<br />";
						$details = MediaFS::GetThumbDetails($thumb."_t");
						MediaFS::WriteDBThumbfile($this->f_file, $thumb."_t", true, true);
						MediaFS::UpdateDBThumbDetails($this->f_file, $details["tsize"], $details["twidth"], $details["theight"]);
						$this->f_thumb_file = "showblob.php?file=".urlencode($this->f_file)."&amp;type=thumb";
						$sql = "SELECT * FROM ".TBLPREFIX."media_files WHERE mf_file='".$file."'";
						$res = NewQuery($sql);
						if ($res->NumRows() > 0) {
							$file = $res->FetchAssoc();
						}
						$res->FreeResult();
					}
					else $this->f_thumb_file = MediaFS::ThumbNailFile($this->f_file);
				}
				else $this->f_thumb_file = MediaFS::ThumbNailFile($this->f_file);
				//print $this->f_thumb_file;
				$this->f_link = $file["mf_link"];
				if (!empty($this->f_link)) $this->f_file = $this->f_link;
				$this->f_height = $file["mf_height"];
				$this->f_width = $file["mf_width"];
				$this->f_bits = $file["mf_bits"];
				$this->f_channels = $file["mf_channels"];
				$this->f_file_size = $file["mf_size"];
				$this->f_theight = $file["mf_theight"];
				$this->f_twidth = $file["mf_twidth"];
				$this->f_tfile_size = $file["mf_tsize"];
				$this->f_mimetype = $file["mf_mimetype"];
				$this->f_mimedescr = $file["mf_mimedesc"];
				$this->f_pastelink = MediaFS::CheckMediaDepth($this->f_file);
				if ($this->f_link) {
					$this->f_main_file = $this->f_file;
				}
				else {
//					if (!$this->f_is_image) {
						$this->f_main_file = "showblob.php?file=".urlencode($this->f_file);
						if (!empty($this->f_mimetype)) $this->f_main_file .= "&amp;header=".urlencode($this->f_mimetype);
//					}
//					else {
//						$this->f_main_file = urlencode($this->f_file);
//						if (!empty($this->f_mimetype)) $this->f_main_file .= "&amp;header=".urlencode($this->f_mimetype);
//					}
				}
				$this->f_file_exists = true;
//				if (empty($this->f_thumb_file)) {
//					$this->f_thumb_file = MediaFS::ThumbNailFile($this->f_file);
//					if (!empty($this->f_thumb_file)) {
//						$this->f_is_image=true;
//						$this->f_file_exists = true;
//					}
//				}
			}
			
		}
		else if ((file_exists($file) && !is_dir($file)) || stristr($file, "://")) {
			$this->f_file = $file;
			// print "getting details for ".$this->f_file."<br />";
			$this->f_main_file = $file;
			// check this!
			$this->f_pastelink = MediaFS::CheckMediaDepth($this->f_file);
			$this->f_thumb_file = MediaFS::ThumbnailFile($this->f_file);
			
			$this->f_file_exists = true;

			// Don't get the details of the remote file everytime, SLOW!
			$mimetypedetect = New MimeTypeDetect;
			if (stristr($file, "://")) {
				$this->f_link = $file;
				$this->f_tfile_size = filesize($this->f_thumb_file);
				$mimetype = $mimetypedetect->FindMimeType($this->f_thumb_file);
				if ($file_details = getimagesize($this->f_thumb_file)) {
					$this->f_twidth = $file_details[0];
					$this->f_theight = $file_details[1];
					$this->f_is_image = true;
					$this->f_bits = $file_details["bits"];
					if (isset($file_details["channels"])) $this->f_channels = $file_details["channels"];
				}
				else $this->f_is_image = false;
			}
			else {
				$this->f_file_size = filesize($this->f_file);
				$mimetype = $mimetypedetect->FindMimeType($this->f_file);
				if ($file_details = @getimagesize($this->f_file)) {
					$this->f_width = $file_details[0];
					$this->f_height = $file_details[1];
					$this->f_bits = $file_details["bits"];
					if (isset($file_details["channels"])) $this->f_channels = $file_details["channels"];
					$this->f_is_image = true;
				}
				else $this->f_is_image = false;
				
				if ($this->f_is_image) {
					$this->f_tfile_size = filesize($this->f_thumb_file);
					if ($file_details = @getimagesize($this->f_thumb_file)) {
						$this->f_twidth = $file_details[0];
						$this->f_theight = $file_details[1];
					}
				}
			}
			if ($mimetype) {
				$this->f_mimetype = $mimetype["mime_type"];
				$this->f_mimedescr = $mimetype["description"];
			}
			
			// Get the extension
			$et = preg_match("/(\.\w+)$/", $this->f_file, $ematch);
			if ($et>0) $this->f_ext = substr(trim($ematch[1]),1);
		}
		else {
			// The file does not exist, but we must have a placeholder for the media to display
			$this->f_file_exists = false;
			$this->f_thumb_file = MediaFS::ThumbnailFile("");
		}
		
//		print "<pre>";
//		print_r($this);
//		print "</pre>";
	}
	
	public function __get($property) {
		switch ($property) {
			case "f_file":
				return $this->f_file;
				break;
			case "f_ext":
				return $this->f_ext;
				break;
			case "f_main_file":
				return $this->f_main_file;
				break;
			case "f_width":
				return $this->f_width;
				break;
			case "f_height":
				return $this->f_height;
				break;
			case "f_bits":
				return $this->f_bits;
				break;
			case "f_channels":
				return $this->f_channels;
				break;
			case "f_file_size":
				return $this->f_file_size;
				break;
			case "f_thumb_file":
				return $this->f_thumb_file;
				break;
			case "f_twidth":
				return $this->f_twidth;
				break;
			case "f_theight":
				return $this->f_theight;
				break;
			case "f_mimetype":
				return $this->f_mimetype;
				break;
			case "f_mimedescr":
				return $this->f_mimedescr;
				break;
			case "f_is_image":
				return $this->f_is_image;
				break;
			case "f_file_exists":
				return $this->f_file_exists;
				break;
			case "f_link":
				return $this->f_link;
				break;
			case "f_pastelink":
				return $this->f_pastelink;
				break;
			case "f_content_main":
				return $this->GetMainContent();
				break;
			default:
				PrintGetSetError($property, get_class($this), "get");
				break;
		}
	}
	private function GetMainContent() {
		
		if (!$this->f_file_exists) return "";
		$data = "";
		if (SystemConfig::$MEDIA_IN_DB) {
			$sql = "SELECT mdf_data FROM ".TBLPREFIX."media_datafiles WHERE mdf_file='".$this->f_file."' ORDER BY mdf_id ASC";
			$res = NewQuery($sql);
			while ($row = $res->FetchRow()) {
				$data .= $row[0];
			}
		}
		else {
			$f = fopen($this->f_file,'rb');
			while(!feof($f)) {
				$data .= fread($f,4096);
			}
			fclose($f);
		}
		return $data;
	}
			
		

		
}
?>