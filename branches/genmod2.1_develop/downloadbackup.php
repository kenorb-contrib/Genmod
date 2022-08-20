<?php
/**
 * Allow an admin user to download the backup file.
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
 * @subpackage Admin
 * @version $Id: downloadbackup.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

// Check if the extension is legal and if the user has rights to download this
$fname = urldecode($fname);
$legal = array_merge(array("zip", "ged"), MediaFS::$MEDIATYPE);
$et = preg_match("/(\.\w+)$/", $fname, $ematch);
if ($et>0) $ext = substr(trim($ematch[1]),1);
else $ext = "";
if (!in_array(strtolower($ext), $legal) || !$gm_user->userGedcomAdmin() || empty($fname)) {
	WriteToLog("DownloadBackup-&gt; Illegal download attempt. File: ".$fname, "W", "S");
	header("HTTP/1.1 403 Forbidden");
	exit;
}


if(ini_get('zlib.output_compression')) @ini_set('zlib.output_compression', 'Off');

header("Pragma: private"); // required
header("Expires: 0");
header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private",false); // required for certain browsers 
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"".basename($fname)."\"");
$s = @filesize($fname);
if ($s) header("Content-length: ".$s);
header("Content-Transfer-Encoding: binary\n");
readfile($fname);
exit;
?>