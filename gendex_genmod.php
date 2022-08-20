<?php
/* Version 2.0
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 - 2010 Genmod Development Team
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
 * @subpackage export
 * @version $Id: gendex_genmod.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/* Additional ifo
 *
 * This can be used to export gendex files from a Genmod file
 * to make it searchable at FamilyTreeSeeker.com
 *
 * USE:
 * You can submit this gendex file at FamilyTreeSeeker.com with the following address (example):
 * 'http://<www.yourdomain.com>/<Genmod folder>/gendex_genmod.php?g=<gedcom id>'
 * For the standard gedcom you can exclude the part '?g=<gedcom_id>'.
 * Every time this address is visited a gendex file is exported dynamicly,
 * based on the visitor privacy settings for the gedcom in Genmod.
 *
 * For extra safety you can add a password here (between " and "):
 */
$password = "";
/*
 * If you add a password, you have to submit your gendex file as:
 * 'http://<www.yourdomain.com>/<Genmod folder>/gendex_genmod.php?p=password'
*/

ini_set('max_execution_time', 0);
header ('Content-type: text/plain; charset=iso-8859-1');

if ($password && (!isset($_GET['p']) || $password != $_GET['p'])) { 
	exit; 
}

require_once('config.php');

if (!isset($_GET['g'])) {
	$file = $DEFAULT_GEDCOMID;
	if (!$file) {
		foreach ($GEDCOMS as $gedcomid => $array) {
			$file = $gedcomid;
			break;
		}
	}
}
else {
	$file = $_GET['g'];
}
SwitchGedcom($file);

$indis = ListFunctions::GetIndiList("no");

foreach ($indis as $key => $indi) {
	$gdline = $indi->xref."&gedid=".$indi->gedcomid;
	$gdline .= "|";
	$gdline .= $indi->name_array[0][2];
	$gdline .= "|";
	$gdline .= $indi->name_array[0][0];
	$gdline .= "|";
	$bd = ParseDate($indi->bdate);
	$gdline .= $bd[0]["year"];
	$gdline .= "|";
	$gdline .= GetGedcomValue("PLAC", 2, $indi->bplac);
	$gdline .= "|";
	$dd	= ParseDate($indi->ddate);
	$gdline .= $dd[0]["year"];
	$gdline .= "|";
	$gdline .= GetGedcomValue("PLAC", 2, $indi->dplac);
	$gdline .= "|\r\n";
	print($gdline);
}
?>
