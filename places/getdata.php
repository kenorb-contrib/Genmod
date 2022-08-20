<?php
/**
 * GET data from a server file to populate a contextual place list
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
 * @subpackage Edit
 * @version $Id: getdata.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

$field=@$HTTP_GET_VARS["field"];
//print $field."|";
$ctry=@$HTTP_GET_VARS["ctry"];
$stae=@$HTTP_GET_VARS["stae"];
$cnty=@$HTTP_GET_VARS["cnty"];
$city=@$HTTP_GET_VARS["city"];

if (empty($ctry)) return;
$filename="";
if ($field=="PLAC_STAE") $filename=$ctry."/".$ctry;
if ($field=="PLAC_CNTY") $filename=$ctry."/".$ctry."_".$stae;
if ($field=="PLAC_CITY") $filename=$ctry."/".$ctry."_".$stae."_".$cnty;
if (empty($filename)) return;
$filename.=".txt";
//print $filename."|";

/** examples:
list of USA states => USA/USA.txt
list of Rhode Island counties => USA/USA_Rhode-Island.txt
**/

if (!file_exists($filename)) return;

$data = file_get_contents($filename);
$data = str_replace("\r", "",$data);
$data = preg_replace("/<!--.*?-->\n/is", "", $data); 
$data = str_replace("\n", "|",$data);
$data = trim($data,"|");
print $data;
?>