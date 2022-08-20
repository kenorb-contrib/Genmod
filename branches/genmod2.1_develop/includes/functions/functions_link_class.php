<?php
/**
 * Class Function for finding/linking
 *
 * Various printing functions used by all scripts and included by the functions.php file.
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
 * @subpackage Display
 * @version $Id: functions_link_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
abstract class LinkFunctions {

	/**
	 * Print the find individual link
	 *
	 * A link is printed which will give the user a popup
	 * window to select an individual ID from the find screen.
	 *
	 * @author	Genmod Development Team
	 * @param		$element_id	The ID of the form field
	 */
	public function PrintFindIndiLink($element_id, $gedid) {
		global $GM_IMAGES;
	
		$text = GM_LANG_find_id;
		if (isset($GM_IMAGES["indi"]["button"])) $Link = "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["indi"]["button"]."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
		else $Link = $text;
		if (empty($gedid)) $gedid = GedcomConfig::$GEDCOMID;
		print " <a href=\"javascript: ".$text."\" onclick=\"findIndi(document.getElementById('".$element_id."'), '".$gedid."'); return false;\">";
		print $Link;
		print "</a>";
	}
	/**
	 * Print the find place link
	 *
	 * A link is printed which will give the user a popup
	 * window to select an place from the find screen.
	 *
	 * @author	Genmod Development Team
	 * @param		$element_id	The ID of the form field
	 */
	 
	public function PrintFindPlaceLink($element_id) {
		global $GM_IMAGES;
	
		$text = GM_LANG_find_place;
		if (isset($GM_IMAGES["place"]["button"])) $Link = "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["place"]["button"]."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
		else $Link = $text;
		print " <a href=\"javascript: ".$text."\" onclick=\"findPlace(document.getElementById('".$element_id."')); return false;\">";
		print $Link;
		print "</a>";
	}

	/**
	 * Print the find family link
	 *
	 * A link is printed which will give the user a popup
	 * window to select a family ID from the find screen.
	 *
	 * @author	Genmod Development Team
	 * @param		$element_id	The ID of the form field
	 */
	public function PrintFindFamilyLink($element_id) {
		global $GM_IMAGES;
	
		$text = GM_LANG_find_family;
		if (isset($GM_IMAGES["family"]["button"])) $Link = "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["family"]["button"]."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
		else $Link = $text;
		print " <a href=\"javascript: ".$text."\" onclick=\"findFamily(document.getElementById('".$element_id."')); return false;\">";
		print $Link;
		print "</a>";
	}

	/**
	 * Print the find source link
	 *
	 * A link is printed which will give the user a popup
	 * window to select a source ID from the find screen.
	 *
	 * @author	Genmod Development Team
	 * @param		$element_id	The ID of the form field
	 */
	public function PrintFindSourceLink($element_id) {
		global $GM_IMAGES;
	
		$text = GM_LANG_find_sourceid;
		if (isset($GM_IMAGES["source"]["button"])) $Link = "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["source"]["button"]."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
		else $Link = $text;
		print " <a href=\"javascript: ".$text."\" onclick=\"findSource(document.getElementById('".$element_id."')); return false;\">";
		print $Link;
		print "</a>";
	}
	
	/**
	 * Print the find repository link
	 *
	 * A link is printed which will give the user a popup
	 * window to select a repository ID from the find screen.
	 *
	 * @author	Genmod Development Team
	 * @param		$element_id	The ID of the form field
	 */
	public function PrintFindRepositoryLink($element_id) {
		global $GM_IMAGES;
	
		$text = GM_LANG_find_repository;
		if (isset($GM_IMAGES["repository"]["button"])) $Link = "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["repository"]["button"]."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
		else $Link = $text;
		print " <a href=\"javascript: ".$text."\" onclick=\"findRepository(document.getElementById('".$element_id."')); return false;\">";
		print $Link;
		print "</a>";
	}
	
	/**
	 * Print the find media link
	 *
	 * A link is printed which will give the user a popup
	 * window to select a media name from the find screen.
	 *
	 * @author	Genmod Development Team
	 * @param		$element_id	The ID of the form field
	 */
	public function PrintFindMediaFileLink($element_id) {
		global $GM_IMAGES;
	
		$text = GM_LANG_find_mfile;
		if (isset($GM_IMAGES["media"]["button"])) $Link = "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["media"]["button"]."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
		else $Link = $text;
		print " <a href=\"javascript: ".$text."\" onclick=\"findMFile(document.getElementById('".$element_id."')); return false;\">";
		print $Link;
		print "</a>";
	}
	
	/**
	 * Print the find object link
	 *
	 * A link is printed which will give the user a popup
	 * window to select an object ID from the find screen.
	 *
	 * @author	Genmod Development Team
	 * @param		$element_id	The ID of the form field
	 */
	public function PrintFindMediaLink($element_id) {
		global $GM_IMAGES;
	
		$text = GM_LANG_find_media;
		if (isset($GM_IMAGES["media"]["button"])) $Link = "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["media"]["button"]."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
		else $Link = $text;
		print " <a href=\"javascript: ".$text."\" onclick=\"findMedia(document.getElementById('".$element_id."')); return false;\">";
		print $Link;
		print "</a>";
	}

	/**
	 * Print the find note link
	 *
	 * A link is printed which will give the user a popup
	 * window to select a note ID from the find screen.
	 *
	 * @author	Genmod Development Team
	 * @param		$element_id	The ID of the form field
	 */
	public function PrintFindNoteLink($element_id) {
		global $GM_IMAGES;
	
		$text = GM_LANG_find_noteid;
		if (isset($GM_IMAGES["note"]["button"])) $Link = "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["note"]["button"]."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
		else $Link = $text;
		print " <a href=\"javascript: ".$text."\" onclick=\"findNote(document.getElementById('".$element_id."')); return false;\">";
		print $Link;
		print "</a>";
	}

	/**
	 * Print the add source link
	 *
	 * A link is printed which will give the user a popup
	 * window with an editing form to create a new source
	 *
	 * @author	Genmod Development Team
	 * @param		$element_id	The ID of the form field
	 */
	public function PrintAddNewSourceLink($element_id) {
		global $GM_IMAGES;
	
		$text = GM_LANG_create_source;
		if (isset($GM_IMAGES["addsource"]["button"])) $Link = "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["addsource"]["button"]."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
		else $Link = $text;
		print "&nbsp;&nbsp;&nbsp;<a href=\"javascript: ".$text."\" onclick=\"addnewsource(document.getElementById('".$element_id."'), 'create_source'); return false;\">";
		print $Link;
		print "</a>";
	}
	
	/**
	 * Print the add repository link
	 *
	 * A link is printed which will give the user a popup
	 * window with an editing form to create a new repository
	 *
	 * @author	Genmod Development Team
	 * @param		$element_id	The ID of the form field
	 */
	public function PrintAddNewRepositoryLink($element_id) {
		global $GM_IMAGES;
	
		$text = GM_LANG_create_repository;
		if (isset($GM_IMAGES["addrepository"]["button"])) $Link = "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["addrepository"]["button"]."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
		else $Link = $text;
		print "&nbsp;&nbsp;&nbsp;<a href=\"javascript: ".$text."\" onclick=\"addnewrepository(document.getElementById('".$element_id."'), 'create_repository'); return false;\">";
		print $Link;
		print "</a>";
	}

	/**
	 * Print the add media link
	 *
	 * A link is printed which will give the user a popup
	 * window with an editing form to create a new media object
	 *
	 * @author	Genmod Development Team
	 * @param		$element_id	The ID of the form field
	 */
	public function PrintAddNewObjectLink($element_id) {
		global $GM_IMAGES;
	
		$text = GM_LANG_add_media;
		if (isset($GM_IMAGES["addmedia"]["button"])) $Link = "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["addmedia"]["button"]."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
		else $Link = $text;
		print "&nbsp;&nbsp;&nbsp;<a href=\"javascript: ".$text."\" onclick=\"addnewmedia(document.getElementById('".$element_id."'), 'create_media'); return false;\">";
		print $Link;
		print "</a>";
	}
	
	/**
	 * Print the add general note link
	 *
	 * A link is printed which will give the user a popup
	 * window with an editing form to create a new repository
	 *
	 * @author	Genmod Development Team
	 * @param		$element_id	The ID of the form field
	 */
	public function PrintAddNewGNoteLink($element_id) {
		global $GM_IMAGES;
	
		$text = GM_LANG_create_general_note;
		if (isset($GM_IMAGES["addnote"]["button"])) $Link = "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["addnote"]["button"]."\" alt=\"".$text."\" title=\"".$text."\" border=\"0\" align=\"middle\" />";
		else $Link = $text;
		print "&nbsp;&nbsp;&nbsp;<a href=\"javascript: ".$text."\" onclick=\"addnewgnote(document.getElementById('".$element_id."'), 'create_general_note'); return false;\">";
		print $Link;
		print "</a>";
	}
	
	/**
	 * Print the find special character link
	 *
	 * A link is printed which will give the user a popup
	 * window with a list of special characters that can be
	 * inserted into the editing field.
	 *
	 * @author	Genmod Development Team
	 * @param		$element_id	The ID of the form field
	 */
	public function PrintSpecialCharLink($element_id) {
		global $GM_IMAGES;
	
		$text = GM_LANG_find_specialchar;
		if (isset($GM_IMAGES["keyboard"]["button"])) $Link = "<img id=\"".$element_id."_spec\" name=\"".$element_id."_spec\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["keyboard"]["button"]."\"  alt=\"".$text."\"  title=\"".$text."\" border=\"0\" align=\"middle\" />";
		else $Link = $text;
		print " <a href=\"javascript: ".$text."\" onclick=\"findSpecialChar(document.getElementById('$element_id')); return false;\">";
		print $Link;
		print "</a>";
	}

	public function PrintAutoPasteLink($element_id, $choices, $concat=1, $updatename=true) {
	
		print "<small>";
		foreach ($choices as $indexval => $choice) {
			print " &nbsp;<a href=\"javascript: ".GM_LANG_copy."\" onclick=\"document.getElementById('".$element_id."').value ";
			if ($concat) print "+=' "; else print "='";
			print $choice."'; ";
			if ($updatename) print "updatewholename(); ";
			print "return false;\">".$choice."</a>";
		}
		print "</small>";
	}
}
?>
