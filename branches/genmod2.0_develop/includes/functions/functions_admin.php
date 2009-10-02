<?php
/**
 * Functions used in admin pages
 *
 * $Id$
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
 * @package Genmod
 * @subpackage Tools
 * @see validategedcom.php
 */
 
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

/**
 * Check if a gedcom file is downloadable over the internet
 *
 * @author opus27
 * @param string $gedfile gedcom file
 * @return mixed 	$url if file is downloadable, false if not
 */
function CheckGedcomDownloadable($gedfile) {
	global $gm_lang;

	$url = "http://localhost/";
	if (substr($url,-1,1)!="/") $url .= "/";
	$url .= preg_replace("/ /", "%20", $gedfile);
	@ini_set('user_agent','MSIE 4\.0b2;'); // force a HTTP/1.0 request
	@ini_set('default_socket_timeout', '10'); // timeout
	$handle = @fopen ($url, "r");
	if ($handle==false) return false;
	// open successfull : now make sure this is a GEDCOM file
	$txt = fread ($handle, 80);
	fclose($handle);
	if (strpos($txt, " HEAD")==false) return false;
	return $url;
}

/* This function returns a list of directories
*
*/
function GetDirList($dirs, $recursive=true) {
	$dirlist = array();
	if (!is_array($dirs)) $dirlist[] = $dirs;
	else $dirlist = $dirs;
	foreach ($dirs as $key=>$dir) {
		$d = @dir($dir);
		if (is_object($d)) {
			while (false !== ($entry = $d->read())) {
				if ($entry != ".." && $entry != ".") {
					$entry = $dir.$entry."/";
					if(is_dir($entry)) {
						if ($recursive) $dirlist = array_merge($dirlist, GetDirList(array($entry)));
						else $dirlist[] = $entry;
					}
				}
			}
			$d->close();
		}
	}
	return $dirlist;
}

?>