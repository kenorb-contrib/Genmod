<?php
/**
 * Prints media objects from the DB to the browser
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
 * @version $Id: showblob.php,v 1.9 2008/06/27 04:01:28 sjouke Exp $
 */
require("config.php");

// If it's a link, just slam it out
if (isset($link)) {
	if (isset($header)) header("Content-Type: ".urldecode($header));
	header("Content-Disposition: attachment; filename=".basename($link));
	readfile($link); 
	exit;
}
// Get the file
if (!isset($file)) exit;

$file=urldecode($file);

// Check if the extension is legal
$et = preg_match("/(\.\w+)$/", $file, $ematch);
if ($et>0) $ext = substr(trim($ematch[1]),1);
else $ext = "";
if (!$MediaFS->IsValidMedia($file)) {
	WriteToLog("ShowBlob-> Illegal display attempt. File: ".$file, "W", "S");
	exit;
}

// We always do a privacy check
$m = RelativePathFile($MEDIA_DIRECTORY);
if (!empty($m)) $mfile = preg_replace("~^$m~", "", $file);
else $mfile = $file;
$sql = "SELECT m_media FROM ".$TBLPREFIX."media WHERE m_file='".$mfile."' AND m_gedfile='".$GEDCOMID."'";
$res = NewQuery($sql);

// Check the privacy settings
while ($row = $res->FetchRow()) {
	if (!DisplayDetailsByID($row[0], "OBJE", 2, true)) {
		WriteToLog("ShowBlob-> Unauthorised access to media: ".$file, "W", "S");
		header("HTTP/1.1 403 Forbidden");
		exit;
	}
}
// Unlinked files are not checked for privacy. We can block them, but then we cannot display unlinked files anymore!

if (!isset($type)) $type = "";
if ($type != "thumb") $sql = "SELECT mdf_data FROM ".$TBLPREFIX."media_datafiles WHERE mdf_file='".$file."' ORDER BY mdf_id ASC";
else $sql = "SELECT mtf_data FROM ".$TBLPREFIX."media_thumbfiles WHERE mtf_file='".$file."' ORDER BY mtf_id ASC";
$res = NewQuery($sql);

if ($res->NumRows() == 0) {
	print "Nothing to show.....";
	exit;
}

// And display
if (isset($header)) {
	header("Content-Type: ".urldecode($header));
	header("Content-Disposition: attachment; filename=".basename($file));
} 
while ($row = $res->FetchRow()) {
	print $row[0];
}
?>