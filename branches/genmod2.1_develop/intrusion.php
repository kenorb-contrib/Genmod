<?php
/**
 * Handle intrusions made to files in subfolders.
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
 * @version $Id: intrusion.php 13 2016-04-27 09:26:01Z Boudewijn $
 * @package Genmod
 * @subpackage Admin
 */

if (!isset($INTRUSION_DETECTED)) { 
	$INTRUSION_DETECTED = true;
	@ini_set('include_path',ini_get('include_path').';..;../..');
	require "config.php";
	exit;
}
?>