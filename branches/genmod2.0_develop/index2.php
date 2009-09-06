<?php
/**
 * The starting point for all other Genmod pages
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
 * @version $Id$
 */

// Include the configuration file
require('config.php');

// Set a global array
$genmod = array();

// Check if we have any incoming requests
if (isset($_REQUEST['page'])) $genmod['page'] = $_REQUEST['page'];
else $genmod['page'] = 'index';

// Check if there is any action to perform
if (isset($_REQUEST['action'])) $genmod['action'] = $_REQUEST['action'];
else $genmod['action'] = '';

// Check if there is any task to perform
if (isset($_REQUEST['task'])) $genmod['task'] = $_REQUEST['task'];
else $genmod['task'] = '';

// Check if we have a gedcom to work with
$genmod['gedcomid'] = $GEDCOMID;
$genmod['gedcomname'] = $GEDCOM;

// Get some general settings
$genmod['index_directory'] = INDEX_DIRECTORY;
$genmod['text_direction'] = $TEXT_DIRECTION;
$genmod['version'] = GM_VERSION;
$genmod['version_release'] = GM_VERSION_RELEASE;
$genmod['character_set'] = $CHARACTER_SET;
$genmod['gedcoms'] = $GEDCOMS;
$genmod['language'] = $LANGUAGE;
$genmod['gm_base_directory'] = $GM_BASE_DIRECTORY;
$genmod['query_string'] = $QUERY_STRING;
$genmod['gm_language'] = $gm_language;
$genmod['language_settings'] = $language_settings;
$genmod['gm_lang'] = $gm_lang;
$genmod['tblprefix'] = TBLPREFIX;
$genmod['factarray'] = $factarray;
$genmod['gm_images'] = $GM_IMAGES;
$genmod['gm_image_dir'] = $GM_IMAGE_DIR;

// Start collecting output
ob_start();

// Lets call the page we want to vist
include($genmod['page'].'.php');

// End collection of output
$genmod['output'] = ob_get_contents();
// Lets see if any data was received, if not, then we go to the start page
if (empty($genmod['output'])) {
	include('index.php');
	$genmod['output'] = ob_get_contents();
}
ob_end_clean();

// Output the data
echo $genmod['output'];
?>
