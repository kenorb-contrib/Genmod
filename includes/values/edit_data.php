<?php
/**
 * Various values used by the Edit interface
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
 * @subpackage Edit
 * @see edit_data.php
 * @version $Id$
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

$NPFX_accept = array("Adm", "Amb", "Brig", "Can", "Capt", "Chan", "Chapln", "Cmdr", "Col", "Cpl", "Cpt", "Dr", "Gen", "Gov", "Hon", "Lady", "Lt", "Mr", "Mrs", "Ms", "Msgr", "Pfc", "Pres", "Prof", "Pvt", "Rep", "Rev", "Sen", "Sgt", "Sir", "Sr", "Sra", "Srta", "Ven");
$SPFX_accept = array("al", "da", "de", "den", "dem", "der", "di", "du", "el", "la", "van", "von");
$NSFX_accept = array("Jr", "Sr", "I", "II", "III", "IV", "MD", "PhD");
$FILE_FORM_accept = array("avi", "bmp", "gif", "jpeg", "mp3", "ole", "pcx", "tiff", "wav");
// Removed EVEN from empty facts
$emptyfacts = array("BIRT","CHR","DEAT","BURI","CREM","ADOP","BAPM","BARM","BASM","BLES","CHRA","CONF","FCOM","ORDN","NATU","EMIG","IMMI","CENS","PROB","WILL","GRAD","RETI","BAPL","CONL","ENDL","SLGC","MARR","MARR_CIVIL","MARR_RELIGIOUS","MARR_PARTNERS","MARR_UNKNOWN","SLGS","MARL","ANUL","CENS","DIV","DIVF","ENGA","MARB","MARC","MARS","CHAN","_SEPR","RESI", "DATA", "MAP");
//$separatorfacts = array("BIRT","CHR","DEAT","BURI","CREM","ADOP","BAPM","BARM","BASM","BLES","CHRA","CONF","FCOM","ORDN","NATU","EMIG","IMMI","CENS","PROB","WILL","GRAD","RETI","BAPL","CONL","ENDL","SLGC","EVEN","MARR","SLGS","MARL","ANUL","CENS","DIV","DIVF","ENGA","MARB","MARC","MARS","CHAN","_SEPR","RESI","MAP","SOUR","REPO","OBJE","SEX","NAME","ASSO","NOTE","EMAIL","TITL");
$separatorfacts = array("SOUR","REPO","OBJE","ASSO","NOTE","RESN","GNOTE");
$templefacts = array("SLGC","SLGS","BAPL","ENDL","CONL");
$nonplacfacts = array("SLGC","SLGS","ENDL","ASSO","RESN");
$nondatefacts = array("ABBR","ADDR","ASSO","AFN","AUTH","EMAIL","FAX","NAME","NOTE","GNOTE","OBJE","PHON","PUBL","REPO","SEX","SOUR","TEXT","TITL","WWW","_EMAIL","EMAIL","REFN","NCHI","RIN","FILE","FORM","_PRIM","_SSHOW","_TYPE","_SCBK","RESN");
$typefacts = array();	//-- special facts that go on 2 TYPE lines
$canhavey_facts = array("MARR","DIV","BIRT","DEAT","CHR","BURI","CREM"); 
?>