<?php
/**
 * Various functions used by the media DB interface
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2002 to 2005 Peter Dyson, John Finlay and Others
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
 * @subpackage MediaDB
 * @version $Id: functions_mediadb.php,v 1.35 2006/04/09 15:53:27 roland-d Exp $
 */

if (strstr($_SERVER["SCRIPT_NAME"],"functions")) {
	print "Now, why would you want to do that.  You're not hacking are you?";
	exit;
}

/*
 *******************************
 * Database Interface functions
 *******************************/

/**
 * Removes a media item from this gedcom.
 *
 * Removes the main media record and any associated links
 * to individuals.
 *
 * @param string $media The gid of the record to be removed in the form Mxxxx.
 * @param string $ged The gedcom file this action is to apply to.
 */
function remove_db_media($media,$ged) {
	global $TBLPREFIX;

	$success = false;
	
	// remove the media record
	$sql = "DELETE FROM ".$TBLPREFIX."media WHERE m_media='$media' AND m_gedfile='$ged'";
	if ($res = dbquery($sql)) $success = true;

	// remove all links to this media item
	$sql = "DELETE FROM ".$TBLPREFIX."media_mapping WHERE mm_media='$media' AND mm_gedfile='$ged'";
	if ($res = dbquery($sql)) $success = true;

	return $success;
}

/**
 * Queries the existence of a link in the db.
 *
 * @param string $media The gid of the record to be removed in the form Mxxxx.
 * @param string $gedrec The gedcom record as a string without the gid.
 * @param string $indi The gid that this media is linked to Ixxx Fxxx ect.
 * @return boolean
 */
function exists_db_link($media, $indi, $ged) {
	global $GEDCOMS, $TBLPREFIX;

	$sql = "SELECT * FROM ".$TBLPREFIX."media_mapping WHERE mm_gedfile='".$GEDCOMS[$ged]["id"]."' AND mm_gid='".addslashes($indi)."' AND mm_media='".addslashes($media)."'";
	$res = dbquery($sql);
	if ($res->numRows()) { return true;} else {return false;}
}


/**
 * Updates any gedcom records associated with the media.
 *
 * Replace the gedrec for the media record.
 *
 * @param string $media The gid of the record to be removed in the form Mxxxx.
 * @param string $gedrec The gedcom record as a string without the gid.
 * @param string $ged The gedcom file this action is to apply to.
 */
function update_db_media($media, $gedrec, $ged) {
	global $GEDCOMS, $TBLPREFIX;

	// replace the gedrec for the media record
	$sql = "UPDATE ".$TBLPREFIX."media SET m_gedrec = '".addslashes($gedrec)."' WHERE (m_id = '".addslashes($media)."' AND m_gedfile = '".$GEDCOMS[$ged]["id"]."')";
	$res = dbquery($sql);

}

/**
 * Updates any gedcom records associated with the link.
 *
 * Replace the gedrec for an existing link record.
 *
 * @param string $media The gid of the record to be updated in the form Mxxxx.
 * @param string $indi The gid that this media is linked to Ixxx Fxxx ect.
 * @param string $gedrec The gedcom record as a string without the gid.
 * @param string $ged The gedcom file this action is to apply to.
 * @param integer $order The order that this record should be displayed on the gid. If not supplied then
 *                       the order is not replaced.
 */
function update_db_link($media, $indi, $gedrec, $ged, $order=-1) {
	global $TBLPREFIX, $GEDCOMS;

	if (exists_db_link($media, $indi, $ged)) {
		// replace the gedrec for the media link record
		$sql = "UPDATE ".$TBLPREFIX."media_mapping SET mm_gedrec = '".addslashes($gedrec)."'";
		if ($order >= 0) $sql .= ", mm_order = $order";
		$sql .= " WHERE (mm_media = '".addslashes($media)."' AND mm_gedfile = '".$GEDCOMS[$ged]["id"]."' AND mm_gid = '".addslashes($indi)."')";
		$res = dbquery($sql);
		if ($res) {
			WriteToLog("Media record: ".$media." updated successfully", "I", "S");
			return true;
		}
		else {
			WriteToLog("There was a problem updating media record: ".$media, "E", "S");
			return false;
		}
	}
	else {
		add_db_link($media, $indi, $gedrec, $ged, $order=-1);
	}

}

/**
 * Adds a new link into the database.
 *
 * Replace the gedrec for an existing link record.
 *
 * @param string $media The gid of the record to be updated in the form Mxxxx.
 * @param string $indi The gid that this media is linked to Ixxx Fxxx ect.
 * @param string $gedrec The gedcom record as a string without the gid.
 * @param string $ged The gedcom file this action is to apply to.
 * @param integer $order The order that this record should be displayed on the gid. If not supplied then
 *                       the order is not replaced.
 */
function add_db_link($media, $indi, $gedrec, $ged, $order=-1) {
	global $TBLPREFIX, $GEDCOMS;


	// if no preference to order find the number of records and add to the end
	if ($order=-1) {
		$sql = "SELECT * FROM ".$TBLPREFIX."media_mapping WHERE mm_gedfile='".$GEDCOMS[$ged]["id"]."' AND mm_gid='".addslashes($indi)."'";
		$res = dbquery($sql);
		$ct = $res->numRows();
		$order = $ct + 1;
	}

	// add the new media link record
	$sql = "INSERT INTO ".$TBLPREFIX."media_mapping VALUES(NULL,'".addslashes($media)."','".addslashes($indi)."','".addslashes($order)."','".$GEDCOMS[$ged]["id"]."','".addslashes($gedrec)."')";
	$res = dbquery($sql);
	if ($res) {
		WriteToLog("New media link added to the database: ".$media, "I", "G", $GEDCOM);
		return true;
	}
	else {
		WriteToLog("There was a problem adding media record: ".$media, "E", "G", $GEDCOM);
		return false;
	}

}



/**
 * Update the media file location.
 *
 * When the user moves a file that is already imported into the db ensure the links are consistent.
 * This is the handler for media db injected items.
 *
 * @param string $oldfile The name of the file before the move.
 * @param string $newfile The new name for the file.
 * @param string $ged The gedcom file this action is to apply to.
 * @return boolean True if handled and record found in DB. False if not in DB so we can drop back to
 *                 media item handling for items not controlled by MEDIA_DB settings.
 */
function move_db_media($oldfile, $newfile, $ged) {
	global $TBLPREFIX, $GEDCOMS;
	// TODO: Update function
	$sql = "SELECT * FROM ".$TBLPREFIX."media WHERE m_gedfile='".$GEDCOMS[$ged]["id"]."' AND m_file='".addslashes($oldfile)."'";
	$res = dbquery($sql);
	if ($res->numRows()) {
		$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
		$m_id = $row["m_media"];
		$sql = "UPDATE ".$TBLPREFIX."media SET m_file = '".addslashes($newfile)."' WHERE (m_gedfile='".$GEDCOMS[$ged]["id"]."' AND m_file='".addslashes($oldfile)."')";
		$res = dbquery($sql);
		// if we in sql mode then update the GM other table with this info
		$sql = "SELECT * FROM ".$TBLPREFIX."other WHERE o_file='".$GEDCOMS[$ged]["id"]."' AND o_id='".$m_id."'";
		$res1 = dbquery($sql);
		$srch = "/".addcslashes($oldfile,'/.')."/";
		$repl = addcslashes($newfile,'/.');
		if ($res1->numRows()) {
			$row1 = $res1->fetchRow(DB_FETCHMODE_ASSOC);
			$gedrec = $row1["o_gedcom"];
			$newrec = stripcslashes(preg_replace($srch, $repl, $gedrec));
			$sql = "UPDATE ".$TBLPREFIX."other SET o_gedcom = '".addslashes($newrec)."' WHERE o_file='".addslashes($ged)."' AND o_id='".$m_id."'";
			$res = dbquery($sql);
		}
		// alter the base gedcom file so that all is kept consistent
		$gedrec = find_gedcom_record($m_id);
		$newrec = stripcslashes(preg_replace($srch, $repl, $gedrec));
		db_replace_gedrec($m_id,$newrec);
		return true;
	} else { return false; }
}

/*
 ****************************
 * general functions
 ****************************/



/**
 * Get the list of media from the database
 *
 * Searches the media table of the database for media items that
 * are associated with the currently active GEDCOM.
 *
 * The medialist that is returned contains the following elements:
 * - $media["ID"] the unique id of this media item in the table (Mxxxx)
 * - $media["GEDFILE"] the gedcom file the media item should be added to
 * - $media["FILE"] the filename of the media item
 * - $media["FORM"] the format of the item (ie JPG, PDF, etc)
 * - $media["TITL"] a title for the item, used for list display
 * - $media["NOTE"] gedcom record snippet
 *
 * @return mixed A media list array.
 */
function get_db_media_list() {
	global $GEDCOM, $GEDCOMS;
	global $TBLPREFIX;

	$medialist = array();
	$sql = "SELECT * FROM ".$TBLPREFIX."media WHERE m_gedfile='".$GEDCOMS[$GEDCOM]["id"]."' ORDER BY m_id";
	$res = dbquery($sql);
	$ct = $res->numRows();
	while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
		$media = array();
		$media["ID"] = $row["m_id"];
		$media["XREF"] = stripslashes($row["m_media"]);
		$media["GEDFILE"] = stripslashes($row["m_gedfile"]);
		$media["FILE"] = stripslashes($row["m_file"]);
		$media["FORM"] = stripslashes($row["m_ext"]);
		$media["TITL"] = stripslashes($row["m_titl"]);
		$media["NOTE"] = stripslashes($row["m_gedrec"]);
		$medialist[] = $media;
	}
	return $medialist;

}

/**
 * No change logging version of replace_gedrec.
 *
 * This function is and should only be used during the inject media phase
 * as it breaks all undo functionality.
 *
 * @param string $indi Gid of the individuals record to replace.
 * @param gedrec $indirec New gedcom record.
 */
function db_replace_gedrec($indi, $gedrec) {
	global $fcontents, $INDEX_DIRECTORY, $GEDCOM;

	if (!isset($fcontents)) {
		$fp = fopen($INDEX_DIRECTORY.$GEDCOM, "r");
		$fcontents = fread($fp, filesize($INDEX_DIRECTORY.$GEDCOM));
		fclose($fp);
	}
	
	$pos1 = strpos($fcontents, "0 @$indi@");
	if ($pos1===false) {
		print "ERROR 4: Could not find gedcom record with xref:$indi\n";
		return false;
	}
	if (($gedrec = check_gedcom($gedrec, false))!==false) {
		$pos2 = strpos($fcontents, "0 @", $pos1+1);
		if ($pos2===false) $pos2=strlen($fcontents);
		$fcontents = substr($fcontents, 0,$pos1).trim($gedrec)."\r\n".substr($fcontents, $pos2);
		return true;
	}
	return false;
}

/**
 * Converts a block of text into a gedcom NOTE record.
 *
 * @param integer $level  The indent number for the NOTE record.
 * @param string $txt Block of text to convert.
 * @return gedrec Gedcom NOTE record.
*/
function textblock_to_note($level, $txt) {

	$newnote = $level." NOTE\r\n";
	$indent = $level + 1;
	$newline = $indent." CONC ".$txt;
	$newlines = preg_split("/\r?\n/", $newline);
	for($k=0; $k<count($newlines); $k++) {
		if ($k>0) $newlines[$k] = $indent." CONT ".$newlines[$k];
		if (strlen($newlines[$k])>255) {
			while(strlen($newlines[$k])>255) {
				$newnote .= substr($newlines[$k], 0, 255)."\r\n";
				$newlines[$k] = substr($newlines[$k], 255);
				$newlines[$k] = $indent." CONC ".$newlines[$k];
			}
			$newnote .= trim($newlines[$k])."\r\n";
		}
		else {
			$newnote .= trim($newlines[$k])."\r\n";
		}
	}
	return $newnote;
}

/**
 * Removes /./  /../  from the middle of any given path
 * User function as the php variant will expand leading ./ to full path which is not
 * required and could be security issue.
 *
 * @param string $path Filepath to check.
 * @return string Cleaned up path.
 */
function real_path($path)
{
   if ($path == "") { return false; }

   $path = trim(preg_replace("/\\\\/", "/", (string)$path));

   if (!preg_match("/(\.\w{1,4})$/", $path)  &&
       !preg_match("/\?[^\\/]+$/", $path)  &&
       !preg_match("/\\/$/", $path))
   {
       $path .= '/';
   }

   $pattern = "/^(\\/|\w:\\/|https?:\\/\\/[^\\/]+\\/)?(.*)$/i";

   preg_match_all($pattern, $path, $matches, PREG_SET_ORDER);

   $path_tok_1 = $matches[0][1];
   $path_tok_2 = $matches[0][2];

   $path_tok_2 = preg_replace(
                   array("/^\\/+/", "/\\/+/"),
                   array("", "/"),
                   $path_tok_2);

   $path_parts = explode("/", $path_tok_2);
   $real_path_parts = array();

   for ($i = 0, $real_path_parts = array(); $i < count($path_parts); $i++)
   {
       if ($path_parts[$i] == '.')
       {
           continue;
       }
       else if ($path_parts[$i] == '..')
       {
           if (  (isset($real_path_parts[0])  &&  $real_path_parts[0] != '..')
               || ($path_tok_1 != "")  )
           {
               array_pop($real_path_parts);
               continue;
           }
       }
       array_push($real_path_parts, $path_parts[$i]);
   }

   return $path_tok_1 . implode('/', $real_path_parts);
}

/**
 * 
 * Sanity check for the media folder. We need to check if the media and the thumbs folder
 * exist. If they don't exist we will try to create them otherwise we can't continue.
 *
 * @return boolean Specify whether we succeeded to create the media and thumbnail folder
 */
function check_media_structure() {
	global $MEDIA_DIRECTORY;
	
	// Check if the media directory is not a .
	// If so, do not try to create it since it does exist
	if (substr($MEDIA_DIRECTORY,0,1) != ".") {
		// Check first if the $MEDIA_DIRECTORY exists
		if (!is_dir($MEDIA_DIRECTORY)) {
			if (!mkdir($MEDIA_DIRECTORY)) return false;
			if (!file_exists($MEDIA_DIRECTORY."index.php")) {
				$inddata = html_entity_decode("<?php\nheader(\"Location: ../medialist.php\");\nexit;\n?>");
				$fp = @fopen($MEDIA_DIRECTORY."index.php","w+");
				if (!$fp) print "<div class=\"error\">".$gm_lang["security_no_create"].$MEDIA_DIRECTORY."thumbs</div>";
				else {
					// Write the index.php for the media folder
					fputs($fp,$inddata);
					fclose($fp);
				}			
			}
		}
	}
	// Check if the thumbs folder exists
	if (!is_dir($MEDIA_DIRECTORY."thumbs")) {
		print $MEDIA_DIRECTORY."thumbs";
		if (!mkdir($MEDIA_DIRECTORY."thumbs")) return false;
		if (file_exists($MEDIA_DIRECTORY."index.php")) {
			$inddata = file_get_contents($MEDIA_DIRECTORY."index.php");
			$inddatathumb = str_replace(": ../",": ../../",$inddata);
			$fpthumb = @fopen($MEDIA_DIRECTORY."thumbs/index.php","w+");
			if (!$fpthumb) print "<div class=\"error\">".$gm_lang["security_no_create"].$MEDIA_DIRECTORY."thumbs</div>";
			else {
				// Write the index.php for the thumbs media folder
				fputs($fpthumb,$inddatathumb);
				fclose($fpthumb);
			}
		}		
	}
	return true;
}

/**
 * Get the list of media from the database
 *
 * Searches the media table of the database for media items that
 * are associated with the currently active GEDCOM.
 *
 * The medialist that is returned contains the following elements:
 * - $media["ID"] the unique id of this media item in the table (Mxxxx)
 * - $media["GEDFILE"] the gedcom file the media item should be added to
 * - $media["FILE"] the filename and path of the media item checked for media depth
 * - $media["THUMB"] the filename and path of the thumbnail checked for media depth
 * - $media["EXISTS"] if the filename exists
 * - $media["WIDTH"] the width of the media item
 * - $media["HEIGHT"] the height of the media item
 * - $media["FORM"] the format of the item (ie JPG, PDF, etc)
 * - $media["TITL"] a title for the item, used for list display
 * - $media["NOTE"] gedcom record snippet
 * - $media["LINKED"] Flag for front end to indicate this is linked
 * - $media["INDIS"] Array of gedcom ids that this is linked to
 *
 * @author	Genmod Development Team
 * @param		boolean	$currentdir	Should the media be read from the current folder
 * @param		string	$directory	Name of the folder that should be read
 * @return 	array 	A media list array
 */

function get_medialist($currentdir=false, $directory="", $random="0") {
	global $MEDIA_DIRECTORY_LEVELS, $badmedia, $thumbdir, $TBLPREFIX, $MEDIATYPE;
	global $level, $dirs, $ALLOW_CHANGE_GEDCOM, $GEDCOM, $GEDCOMS, $MEDIA_DIRECTORY, $medialist;
	
	// Retrieve the gedcoms to search in
	$sgeds = array();
	if (($ALLOW_CHANGE_GEDCOM) && (count($GEDCOMS) > 1)) {
		foreach ($GEDCOMS as $key=>$ged) {
			$str = preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $key);
			if (isset($$str)) $sgeds[] = $key;
		}
	}
	else $sgeds[] = $GEDCOM;
	
	// Create the medialist array of media in the DB and on disk
	$dirs = array();
	$images = array();
	$foundindis = array();
	$foundfams = array();
	$foundsources = array();	
	$ict=0;
	$exist = false;
	// NOTE: Get the media in the DB
	$medialist = array();
	$folders = preg_split("/\//", $directory);
	$media_folders = preg_split("/\//", $MEDIA_DIRECTORY);
	$dirpath = "";
	for($key = 1; $key < count($folders); $key++) {
		if (!isset($media_folders[$key]) && !empty($folders[$key])) $dirpath .= $folders[$key]."/";
		else if ((!empty($folders[$key])) && $folders[$key] !== $media_folders[$key]) $dirpath .= $folders[$key]."/";
	}
	$sql = "SELECT * FROM ".$TBLPREFIX."media WHERE m_gedfile='".$GEDCOMS[$GEDCOM]["id"]."'";
	if ($random != "0") $sql = "SELECT *, RAND() AS r FROM ".$TBLPREFIX."media WHERE m_gedfile='".$GEDCOMS[$GEDCOM]["id"]."' ORDER BY r LIMIT ".$random;
	else if ($directory != "" && $dirpath != "") $sql .= " AND (m_file REGEXP '".$dirpath."' || m_file REGEXP '://') ORDER BY m_id desc";
	else if ($directory != "") $sql .= " AND m_file REGEXP '://' ORDER BY m_id desc";
	$res = dbquery($sql);
	$ct = $res->numRows();
	while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
		if ($row) {
			$media = array();
			$media["ID"] = $row["m_id"];
			$media["XREF"] = stripslashes($row["m_media"]);
			$media["GEDFILE"] = $row["m_gedfile"];
			$media["FILE"] = check_media_depth(stripslashes($row["m_file"]));
			$media["THUMB"] = thumbnail_file(stripslashes($row["m_file"]));
			if (file_exists(stripslashes($media["FILE"]))) {
				$media["EXISTS"] = true;
				$imgsize = getimagesize($media["FILE"]);
				$media["WIDTH"] = $imgsize[0];
				$media["HEIGHT"] = $imgsize[1];
			}
			else {
				$media["EXISTS"] = false;
				$media["WIDTH"] = 0;
				$media["HEIGHT"] = 1;
			}
			$media["FORM"] = stripslashes($row["m_ext"]);
			$media["TITL"] = stripslashes($row["m_titl"]);
			$media["GEDCOM"] = stripslashes($row["m_gedrec"]);
			$gedrec = trim($row["m_gedrec"]);
			$media["LEVEL"] = $gedrec{0};
			$media["LINKED"] = false;
			$media["LINKS"] = array();
			$medialist[$media["XREF"]."_".$media["GEDFILE"]] = $media;
		}
	}
	$ct = count($medialist);
	if ($ct > 0) {
		if ($directory == "" && $random == "0") {
			$sql = "SELECT * FROM ".$TBLPREFIX."media_mapping WHERE mm_gedfile='".$GEDCOMS[$GEDCOM]["id"]."'";
		}
		else {
			$sql = "SELECT * FROM ".$TBLPREFIX."media_mapping WHERE mm_gedfile='".$GEDCOMS[$GEDCOM]["id"]."' && (";
			$i = 0;
			foreach($medialist as $key => $media) {
				$i++;
				$sql .= "mm_media='".$media["XREF"]."'";
				if ($i < $ct) $sql .= " || ";
			}
			$sql .= ")";
		}
		$res = dbquery($sql);
		// $ct = $res->numRows();
		while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
			$medialist[stripslashes($row["mm_media"])."_".$row["mm_gedfile"]]["LINKS"][stripslashes($row["mm_gid"])] = id_type(stripslashes($row["mm_gid"]));
			$medialist[stripslashes($row["mm_media"])."_".$row["mm_gedfile"]]["LINKED"] = true;
		}
	}
	ksort($medialist);
	// NOTE: Get the media on disk only if the folder is specified
	if ($directory != "") {
		print "directory: ".$directory;
		print "<br />";
		$d = dir($directory);
		while (false !== ($media = $d->read())) {
			$exts = preg_split("/\./", $media);
			$ct = count($exts);
			$ext = strtolower($exts[$ct-1]);
			if (!in_array($media, $badmedia) && (in_array($ext, $MEDIATYPE) || $ct == 1)) {
				$mediafile = $directory.$media;
				if (is_dir($mediafile)) {
					// do not allow the web interface to go to lower levels than configured
					if ($level < $MEDIA_DIRECTORY_LEVELS ) $dirs[] = $media;
				}
				else {
					// Check if the file is already in th DB
					foreach ($medialist as $key => $item) {
						if ($item["FILE"] == $directory.$media) {
							$exist = true;
							break;
						}
					}
					if ($exist == true) {
						if ($currentdir == true && count(preg_split("/\//", $directory.$media)) == count(preg_split("/\//", $directory))) {
							$filename = preg_replace(array("/\+/","/\(/","/\)/"),array("\\\+","\\\(","\\)"),$directory.$media);
							$founditems = search_media_pids($filename, $sgeds, "OR");
						}
						else {
							$filename = preg_replace(array("/\+/","/\(/","/\)/"),array("\\\+","\\\(","\\)"),$directory.$media);
							$founditems = search_media_pids($filename, $sgeds, "OR");
						}
						if (count($founditems) > 0) {
							$medialist[$key]["LINKED"] = true;
							$medialist[$key]["LINKS"] = gm_array_merge($medialist[$key]["LINKS"], $founditems);
						}
						$exist = false;
					}
					else {
						$images[$ict."_unknown"]["ID"] = "";
						$images[$ict."_unknown"]["XREF"] = "";
						$images[$ict."_unknown"]["GEDFILE"] = "";
						$images[$ict."_unknown"]["FILE"] = $directory.$media;
						$images[$ict."_unknown"]["THUMB"] = thumbnail_file($directory.$media);
						$images[$ict."_unknown"]["EXISTS"] = true;
						$imgsize = getimagesize($directory.$media);
						$images[$ict."_unknown"]["WIDTH"] = $imgsize[0];
						$images[$ict."_unknown"]["HEIGHT"] = $imgsize[1];
						$images[$ict."_unknown"]["FORM"] = substr($media,-3,3);
						$images[$ict."_unknown"]["TITL"] = "";
						$images[$ict."_unknown"]["GEDCOM"] = "";
						if ($currentdir == true && count(preg_split("/\//", $directory.$media)) == count(preg_split("/\//", $directory))) {
							$filename = preg_replace(array("/\+/","/\(/","/\)/"),array("\\\+","\\\(","\\)"),$directory.$media);
							$founditems = search_media_pids($filename, $sgeds, "OR");
						}
						else {
							$filename = preg_replace(array("/\+/","/\(/","/\)/"),array("\\\+","\\\(","\\)"),$directory.$media);
							$founditems = search_media_pids($filename, $sgeds, "OR");
						}
						if (count($founditems) > 0) {
							$images[$ict."_unknown"]["LINKED"] = true;
							$images[$ict."_unknown"]["LINKS"] = $founditems;
						}
						else {
							$images[$ict."_unknown"]["LINKED"] = false;
							$images[$ict."_unknown"]["LINKS"] = array();
						}
						$ict++;
					}
				}
			}
		}
		$d->close();
		$medialist = $medialist + $images;
	}
	return $medialist;
}

//-- search through the gedcom records for individuals
/**
 * Search the database for individuals that match the query
 *
 * uses a regular expression to search the gedcom records of all individuals and returns an
 * array list of the matching individuals
 *
 * @author Genmod Development Team
 * @param	string $query a regular expression query to search for
 * @param	boolean $allgeds setting if all gedcoms should be searched, default is false
 * @return	array $myindilist array with all individuals that matched the query
 */
function search_media_pids($query, $allgeds=false, $ANDOR="AND") {
	global $TBLPREFIX, $GEDCOM, $indilist, $DBCONN, $REGEXP_DB, $GEDCOMS;
	$myindilist = array();
	if ($REGEXP_DB) $term = "REGEXP";
	else $term = "LIKE";
	if (!is_array($query)) $sql = "SELECT m_media as m_media FROM ".$TBLPREFIX."media WHERE (m_gedrec $term '".$DBCONN->escapeSimple(strtoupper($query))."' OR m_gedrec $term '".$DBCONN->escapeSimple(str2upper($query))."' OR m_gedrec $term '".$DBCONN->escapeSimple(str2lower($query))."')";
	else {
		$sql = "SELECT m_media FROM ".$TBLPREFIX."media WHERE (";
		$i=0;
		foreach($query as $indexval => $q) {
			if ($i>0) $sql .= " $ANDOR ";
			$sql .= "(m_gedrec $term '".$DBCONN->escapeSimple(str2upper($q))."' OR m_gedrec $term '".$DBCONN->escapeSimple(str2lower($q))."')";
			$i++;
		}
		$sql .= ")";
	}
	if (!$allgeds) $sql .= " AND m_gedfile='".$DBCONN->escapeSimple($GEDCOMS[$GEDCOM]["id"])."'";

	if ((is_array($allgeds)) && (count($allgeds) != 0)) {
		$sql .= " AND (";
		for ($i=0; $i<count($allgeds); $i++) {
			$sql .= "m_gedfile='".$DBCONN->escapeSimple($allgeds[$i])."'";
			if ($i < count($allgeds)-1) $sql .= " OR ";
		}
		$sql .= ")";
	}
	$res = dbquery($sql);
	if (!DB::isError($res)) {
		while($row = $res->fetchRow()){
			$row = db_cleanup($row);
			$sqlmm = "select mm_gid as mm_gid from ".$TBLPREFIX."media_mapping where mm_media = '".$row[0]."' and mm_gedfile = '".$GEDCOMS[$GEDCOM]["id"]."'";
			$resmm = dbquery($sqlmm);
			while ($rowmm = $resmm->fetchRow()) {
				$myindilist[$row[0]."_".$rowmm[0]] = $rowmm[0];
			}
		}
		$res->free();
	}
	return $myindilist;
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
function thumbnail_file($filename) {
	global $MEDIA_DIRECTORY, $GM_IMAGE_DIR, $GM_IMAGES, $AUTO_GENERATE_THUMBS, $MEDIA_DIRECTORY_LEVELS;
	global $MEDIA_EXTERNAL;
	
	if (strlen($filename) == 0) return false;
	
	// NOTE: Lets get the file details
	$parts = pathinfo($filename);
	$dirname = $parts["dirname"];
	$file_basename = $parts["basename"];
	$thumb_extension = $parts["extension"];
	
	if ((stristr($filename, "://") && !$MEDIA_EXTERNAL) || !stristr($filename, "://")) {
		// NOTE: Construct dirname according to media levels to keep
		if ($MEDIA_DIRECTORY_LEVELS == 0) $dirname = "";
		else {
			$dir_levels = array_reverse(preg_split("/\//", $dirname));
			$level = 0;
			$dirname = "";
			$count_dir_levels = count($dir_levels);
			if ($MEDIA_DIRECTORY_LEVELS <= $count_dir_levels) {
				for ($ct_level = ($MEDIA_DIRECTORY_LEVELS-1); $ct_level >= 0; $ct_level--) {
					if (trim($dir_levels[$ct_level]) != "." && strlen(trim($dir_levels[$ct_level])) != 0) {
						$dirname .= $dir_levels[$ct_level]."/";
						if (!is_dir($MEDIA_DIRECTORY."thumbs/".$dirname)) {
							if (mkdir($MEDIA_DIRECTORY."thumbs/".$dirname, 0777)) {
								WriteToLog("Folder ".$MEDIA_DIRECTORY."thumbs/".$dirname." created.", "I", "S");
							}
							else WriteToLog("Folder ".$MEDIA_DIRECTORY."thumbs/".$dirname." could not be created.", "E", "S");
						}
					}
				}
			}
			// else return $MEDIA_DIRECTORY."thumbs/".$filename;
		}
	}
	// NOTE: First check if the thumbnail exists by its exact name
	// NOTE: This is needed if people have page1.pdf and image thumb by the same name
	if (stristr($filename, "://")) {
		$file_basename = preg_replace(array("/http:\/\//", "/\//"), array("","_"),$filename);
		if (file_exists(filename_decode($MEDIA_DIRECTORY."thumbs/urls/".$file_basename))) return $MEDIA_DIRECTORY."thumbs/urls/".$file_basename;
		else if ($AUTO_GENERATE_THUMBS) if (generate_thumbnail($filename, $MEDIA_DIRECTORY."thumbs/urls/".$file_basename)) return $MEDIA_DIRECTORY."thumbs/urls/".$file_basename;
	}
	else {
		if (file_exists(filename_decode($MEDIA_DIRECTORY."thumbs/".$dirname.$file_basename))) return $MEDIA_DIRECTORY."thumbs/".$dirname.$file_basename;
		else {
			if ($AUTO_GENERATE_THUMBS) {
				if (generate_thumbnail($MEDIA_DIRECTORY.$dirname.$file_basename, $MEDIA_DIRECTORY."thumbs/".$dirname.$file_basename)) {
					return $MEDIA_DIRECTORY."thumbs/".$dirname.$file_basename;
				}
			}
		}
	}
	
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
function check_media_depth($filename) {
	global $MEDIA_DIRECTORY, $MEDIA_DIRECTORY_LEVELS, $MEDIA_EXTERNAL;
	
	// NOTE: If the media depth is 0, no need to check it
	if (empty($filename) || ($MEDIA_EXTERNAL && stristr($filename, "://"))) return $filename;
	
	// NOTE: Check media depth
	$parts = pathinfo($filename);
	if (isset($parts["dirname"])) $dirname = $parts["dirname"];
	else return $MEDIA_DIRECTORY.$parts["basename"];
	$file_basename = $parts["basename"];
	$dirname = preg_replace("/\.\/|\./", "", $dirname);
	$dir_levels = array_reverse(preg_split("/\//", $dirname));
	$level = 0;
	$path = "";
	$count_dir_levels = count($dir_levels);
	if ($MEDIA_DIRECTORY_LEVELS == 0 || empty($dirname)) return $MEDIA_DIRECTORY.$file_basename;
	else if ($MEDIA_DIRECTORY_LEVELS <= $count_dir_levels) {
		for ($ct_level = ($MEDIA_DIRECTORY_LEVELS-1); $ct_level >= 0; $ct_level--) {
			if (strlen(trim($dir_levels[$ct_level])) != 0) {
				$path .= $dir_levels[$ct_level]."/";
			}
		}
	}
	else return $MEDIA_DIRECTORY.$dirname."/".$file_basename;
	return $MEDIA_DIRECTORY.$path.$file_basename;
}
?>