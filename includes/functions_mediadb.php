<?php
/**
 * Various functions used by the media DB interface
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
 * @subpackage MediaDB
 * @version $Id: functions_mediadb.php,v 1.62 2008/08/27 12:58:09 sjouke Exp $
 */

if (strstr($_SERVER["SCRIPT_NAME"],"functions")) {
	require "../intrusion.php";
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
	if ($res = NewQuery($sql)) $success = true;

	// remove all links to this media item
	$sql = "DELETE FROM ".$TBLPREFIX."media_mapping WHERE mm_media='$media' AND mm_gedfile='$ged'";
	if ($res = NewQuery($sql)) $success = true;

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
	$res = NewQuery($sql);
	if ($res->NumRows()) { return true;} else {return false;}
}


/**
 * Updates any gedcom records associated with the media.
 *
 * Replace the gedrec for the media record.
 *
 * @param string $media The gid of the record to be removed in the form Mxxxx.
 * @param string $gedrec The gedcom record as a string without the gid.
 * @param string $ged The gedcom file this action is to apply to.
 *//*
function update_db_media($media, $gedrec, $ged) {
	global $GEDCOMS, $TBLPREFIX;

	// replace the gedrec for the media record
	$sql = "UPDATE ".$TBLPREFIX."media SET m_gedrec = '".addslashes($gedrec)."' WHERE (m_id = '".addslashes($media)."' AND m_gedfile = '".$GEDCOMS[$ged]["id"]."')";
	$res = NewQuery($sql);

}
*/
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
function update_db_link($media, $indi, $gedrec, $ged, $order=-1, $type) {
	global $TBLPREFIX, $GEDCOMS;

	if (exists_db_link($media, $indi, $ged)) {
		// replace the gedrec for the media link record
		$sql = "UPDATE ".$TBLPREFIX."media_mapping SET mm_gedrec = '".addslashes($gedrec)."'";
		if ($order >= 0) $sql .= ", mm_order = $order";
		$sql .= " WHERE (mm_media = '".addslashes($media)."' AND mm_gedfile = '".$GEDCOMS[$ged]["id"]."' AND mm_gid = '".addslashes($indi)."')";
		$res = NewQuery($sql);
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
		add_db_link($media, $indi, $gedrec, $ged, $order=-1, $type);
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
 
 // This function is used in ImportRecord
 
function add_db_link($media, $indi, $gedrec, $ged, $order=-1, $rectype) {
	global $TBLPREFIX, $GEDCOMS, $GEDCOM;


	// if no preference to order find the number of records and add to the end
	if ($order=-1) {
		$sql = "SELECT * FROM ".$TBLPREFIX."media_mapping WHERE mm_gedfile='".$GEDCOMS[$ged]["id"]."' AND mm_gid='".addslashes($indi)."'";
		$res = NewQuery($sql);
		$ct = $res->NumRows();
		$order = $ct + 1;
	}

	// add the new media link record
	$sql = "INSERT INTO ".$TBLPREFIX."media_mapping VALUES(NULL,'".addslashes($media)."','".addslashes($indi)."','".addslashes($order)."','".$GEDCOMS[$ged]["id"]."','".addslashes($gedrec)."', '".$rectype."')";
	$res = NewQuery($sql);
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
	$res = NewQuery($sql);
	if ($res->NumRows()) {
		$row = $res->FetchAssoc();
		$m_id = $row["m_media"];
		$sql = "UPDATE ".$TBLPREFIX."media SET m_file = '".addslashes($newfile)."' WHERE (m_gedfile='".$GEDCOMS[$ged]["id"]."' AND m_file='".addslashes($oldfile)."')";
		$res = NewQuery($sql);
		// if we in sql mode then update the GM other table with this info
		$sql = "SELECT * FROM ".$TBLPREFIX."other WHERE o_file='".$GEDCOMS[$ged]["id"]."' AND o_id='".$m_id."'";
		$res1 = NewQuery($sql);
		$srch = "/".addcslashes($oldfile,'/.')."/";
		$repl = addcslashes($newfile,'/.');
		if ($res1->numRows()) {
			$row1 = $res1->FetchAssoc();
			$gedrec = $row1["o_gedcom"];
			$newrec = stripcslashes(preg_replace($srch, $repl, $gedrec));
			$sql = "UPDATE ".$TBLPREFIX."other SET o_gedcom = '".addslashes($newrec)."' WHERE o_file='".addslashes($ged)."' AND o_id='".$m_id."'";
			$res = NewQuery($sql);
		}
		// alter the base gedcom file so that all is kept consistent
		$gedrec = FindGedcomRecord($m_id);
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
	global $GEDCOM, $GEDCOMS, $GEDCOMID;
	global $TBLPREFIX;

	$medialist = array();
	$sql = "SELECT * FROM ".$TBLPREFIX."media WHERE m_gedfile='".$GEDCOMID."' ORDER BY m_id";
	$res = NewQuery($sql);
	$ct = $res->NumRows();
	while($row = $res->FetchAssoc()){
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
 * No change logging version of ReplaceGedrec.
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
	if (($gedrec = CheckGedcom($gedrec, false))!==false) {
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
 
function search_media_pids($query, $allgeds=false, $ANDOR="AND") {
	global $TBLPREFIX, $GEDCOM, $indilist, $DBCONN, $REGEXP_DB, $GEDCOMS, $GEDCOMID;
	$myindilist = array();
	if ($REGEXP_DB) $term = "REGEXP";
	else $term = "LIKE";
	if (!is_array($query)) $sql = "SELECT m_media as m_media FROM ".$TBLPREFIX."media WHERE (m_gedrec $term '".$DBCONN->EscapeQuery(strtoupper($query))."' OR m_gedrec $term '".$DBCONN->EscapeQuery(Str2Upper($query))."' OR m_gedrec $term '".$DBCONN->EscapeQuery(Str2Lower($query))."')";
	else {
		$sql = "SELECT m_media FROM ".$TBLPREFIX."media WHERE (";
		$i=0;
		foreach($query as $indexval => $q) {
			if ($i>0) $sql .= " $ANDOR ";
			$sql .= "(m_gedrec $term '".$DBCONN->EscapeQuery(Str2Upper($q))."' OR m_gedrec $term '".$DBCONN->EscapeQuery(Str2Lower($q))."')";
			$i++;
		}
		$sql .= ")";
	}
	if (!$allgeds) $sql .= " AND m_gedfile='".$GEDCOMID."'";

	if ((is_array($allgeds)) && (count($allgeds) != 0)) {
		$sql .= " AND (";
		for ($i=0; $i<count($allgeds); $i++) {
			$sql .= "m_gedfile='".$DBCONN->EscapeQuery($allgeds[$i])."'";
			if ($i < count($allgeds)-1) $sql .= " OR ";
		}
		$sql .= ")";
	}
	$res = NewQuery($sql);
//	if (!$res) {
		while($row = $res->FetchRow()){
			$row = db_cleanup($row);
			$sqlmm = "select mm_gid as mm_gid from ".$TBLPREFIX."media_mapping where mm_media = '".$row[0]."' and mm_gedfile = '".$GEDCOMID."'";
			$resmm = NewQuery($sqlmm);
			while ($rowmm = $resmm->FetchRow()) {
				$myindilist[$row[0]."_".$rowmm[0]] = $rowmm[0];
			}
		}
		$res->FreeResult();
//	}
	return $myindilist;
}
*/


// Only used in backup
function GetMediaFiles($gedid="") {
	global $TBLPREFIX, $MEDIA_IN_DB;
	
	$mlist = array();
	$sql = "SELECT m_file from ".$TBLPREFIX."media WHERE m_file NOT LIKE '%://%'";
	if (!empty($gedid)) $sql .= " AND m_gedfile='".$gedid."'";
	$sql .= "ORDER BY m_file ASC";
	$res = NewQuery($sql);
	while($row = $res->FetchRow()){
		$row = db_cleanup($row);
		if (($MEDIA_IN_DB || file_exists($row[0])) && !in_array($row[0], $mlist)) $mlist[] = $row[0];
	}
	return $mlist;
}
// used in privacy.php
function GetMediaLinks($pid, $type="", $applypriv=true) {
	global $TBLPREFIX, $GEDCOMID;
	global $allmlinks, $indilist, $famlist, $LINK_PRIVACY;
	
	if (empty($pid)) return false;
	
	if (!isset($allmlinks)) $allmlinks = array();
	if (isset($allmlinks[$pid][$type][$applypriv])) return $allmlinks[$pid][$type][$applypriv];

	$links = array();	
	$indisel = array();
	$famsel = array();	
	$sql = "SELECT mm_gid, mm_type FROM ".$TBLPREFIX."media_mapping WHERE mm_media='".$pid."'";
	if (!empty($type)) $sql .= " AND mm_type='".$type."'";
	$sql .= " AND mm_gedfile='".$GEDCOMID."'";
	$res = NewQuery($sql);
	while($row = $res->FetchRow()){
		$added = false;
		if (!$applypriv) {
			$links[] = $row[0];
			$added = true;
		}
		else {
			if (ShowFact("OBJE", $row[0], $type)) {
				$links[] = $row[0];
				$added = true;
			}
		}
		if ($LINK_PRIVACY && $added) {
			if ($row[1] == "INDI") {
				if (!isset($indilist[$row[0]])) $indisel[] = $row[0];
			}
			else {
				if ($row[1] == "FAM") {
					if (!isset($famlist[$row[0]])) $famsel[] = $row[0];
				}
			}
		}
	}
	if ($LINK_PRIVACY) {
		$indisel = "'".implode("[".$GEDCOMID."]','", $indisel)."[".$GEDCOMID."]'";
		$famsel = "'".implode ("[".$GEDCOMID."]','", $famsel)."[".$GEDCOMID."]'";
		GetIndiList("no", $indisel, false);
		GetFamList("no", $famsel);
	}
	$allmlinks[$pid][$type][$applypriv] = $links;
	return $links;
}

/* Strips the trailing dot and slash from the filename
*/
function RelativePathFile($file) {
	$s = substr($file,0,1);
	if ($s == ".") $file = substr($file, 1);
	$s = substr($file,0,1);
	if ($s == "/") $file = substr($file, 1);
	return $file;
}
?>