<?php
/**
 * Various functions used by the language editor of Genmod
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
 * @subpackage Admin
 * @version $Id$
 */

if (strstr($_SERVER["SCRIPT_NAME"],"functions_editlang.php")) {
	require "../intrusion.php";
}

//-----------------------------------------------------------------
function mask_lt($dstring)
{
  $dummy = str_replace("<", "&lt;", $dstring);
  return $dummy;
}

//-----------------------------------------------------------------
function mask_gt($dstring)
{
  $dummy = str_replace(">", "&gt;", $dstring);
  return $dummy;
}

//-----------------------------------------------------------------
function mask_quot($dstring)
{
  $dummy = str_replace("\"", "&quot;", $dstring);
  return $dummy;
}

//-----------------------------------------------------------------
function mask_amp($dstring)
{
  $dummy = str_replace("&", "&amp;", $dstring);
  return $dummy;
}

//-----------------------------------------------------------------
function mask_all($dstring)
{
  $dummy = mask_lt(mask_gt(mask_quot(mask_amp($dstring))));
  return $dummy;
}

//-----------------------------------------------------------------*/
function CheckBom(){
	global $language_settings, $gm_lang;
	$check = false;
	$output = "";
	foreach ($language_settings as $key => $language) {
		// Check if language is active
		if ($language["gm_lang_use"] == true) {
			// Check language file
			if (file_exists($language["gm_language"])) {
				$str = file_get_contents($language["gm_language"]);
				if (ord($str{0}) == 239 && ord($str{1}) == 187 && ord($str{2}) == 191) {
					$check = true;
					$output .= "<span class=\"warning\">".$gm_lang["bom_found"].substr($language["gm_language"], 10).".</span>";
					$output .= "<br />";
					$writetext = htmlentities(substr($str,3, strlen($str)));
					if (!$handle = @fopen($language["gm_language"], "w")){
						$output .= "<span class=\"warning\">";
						$output .= str_replace("#lang_filename#", substr($language["gm_language"], 10), $gm_lang["no_open"]) . "<br /><br />";
						$output .= "</span>";
					}
					if (@fwrite($handle,html_entity_decode($writetext)) === FALSE) {
						$output .= "<span class=\"warning\">";
						$output .= str_replace("#lang_filename#", substr($language["gm_language"], 10), $gm_lang["lang_file_write_error"]) . "<br /><br />";
						$output .= "</span>";
					}
				}
			}
			else {
				$output .= "<span class=\"warning\">";
				$output .= str_replace("#lang_filename#", substr($language["gm_language"], 10), $gm_lang["no_open"]) . "<br /><br />";
				$output .= "</span>";
			}
			
			// Check help file
			if (file_exists($language["helptextfile"])) {
				if (filesize($language['helptextfile']) > 0) {
					$str = file_get_contents($language["helptextfile"]);
					if (ord($str{0}) == 239 && ord($str{1}) == 187 && ord($str{2}) == 191) {
						$check = true;
						$output .= "<span class=\"warning\">".$gm_lang["bom_found"].substr($language["helptextfile"], 10).".</span>";
						$output .= "<br />";
						$writetext = htmlentities(substr($str,3, strlen($str)));
						if (!$handle = @fopen($language["helptextfile"], "w")){
							$output .= "<span class=\"warning\">";
							$output .= str_replace("#lang_filename#", substr($language["helptextfile"], 10), $gm_lang["no_open"]) . "<br /><br />";
							$output .= "</span>";
						}
						if (@fwrite($handle,html_entity_decode($writetext)) === FALSE) {
							$output .= "<span class=\"warning\">";
							$output .= str_replace("#lang_filename#", substr($language["helptextfile"], 10), $gm_lang["lang_file_write_error"]) . "<br /><br />";
							$output .= "</span>";
						}
					}
				}
			}
			else {
				$output .= "<span class=\"warning\">";
				$output .= str_replace("#lang_filename#", substr($language["helptextfile"], 10), $gm_lang["no_open"]) . "<br /><br />";
				$output .= "</span>";
			}
			// Check facts file
			if (file_exists($language["factsfile"])) {
				$str = file_get_contents($language["factsfile"]);
				if (ord($str{0}) == 239 && ord($str{1}) == 187 && ord($str{2}) == 191) {
					$check = true;
					$output .= "<span class=\"warning\">".$gm_lang["bom_found"].substr($language["factsfile"], 10).".</span>";
					$output .= "<br />";
					$writetext = htmlentities(substr($str,3, strlen($str)));
					if (!$handle = @fopen($language["factsfile"], "w")){
						$output .= "<span class=\"warning\">";
						$output .= str_replace("#lang_filename#", substr($language["factsfile"], 10), $gm_lang["no_open"]) . "<br /><br />";
						$output .= "</span>";
					}
					if (@fwrite($handle,html_entity_decode($writetext)) === FALSE) {
						$output .= "<span class=\"warning\">";
						$output .= str_replace("#lang_filename#", substr($language["factsfile"], 10), $gm_lang["lang_file_write_error"]) . "<br /><br />";
						$output .= "</span>";
					}
				}
			}
			else {
				$output .= "<span class=\"warning\">";
				$output .= str_replace("#lang_filename#", substr($language["factsfile"], 10), $gm_lang["no_open"]) . "<br /><br />";
				$output .= "</span>";
			}
		}
	}
	if ($check == false) $output .= $gm_lang["bom_not_found"];
	return $output;
}
?>