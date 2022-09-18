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
 * @version $Id: setgenmod.php,v 1.2 2008/01/06 11:16:38 roland-d Exp $
 */

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
$genmod['index_directory'] = $INDEX_DIRECTORY;
$genmod['text_direction'] = $TEXT_DIRECTION;
$genmod['version'] = $VERSION;
$genmod['version_release'] = $VERSION_RELEASE;
$genmod['character_set'] = $CHARACTER_SET;
$genmod['gedcoms'] = $GEDCOMS;
$genmod['language'] = $LANGUAGE;
$genmod['gm_base_directory'] = $GM_BASE_DIRECTORY;
$genmod['query_string'] = $QUERY_STRING;
$genmod['gm_language'] = $gm_language;
$genmod['language_settings'] = $language_settings;
$genmod['gm_lang'] = $gm_lang;
$genmod['tblprefix'] = $TBLPREFIX;
$genmod['factarray'] = $factarray;
$genmod['gm_images'] = $GM_IMAGES;
$genmod['gm_image_dir'] = $GM_IMAGE_DIR;
?>