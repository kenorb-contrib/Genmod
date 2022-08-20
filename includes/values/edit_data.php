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
 * @version $Id: edit_data.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
global $NPFX_accept;
$NPFX_accept = array("Adm", "Amb", "Brig", "Can", "Capt", "Chan", "Chapln", "Cmdr", "Col", "Cpl", "Cpt", "Dr", "Gen", "Gov", "Hon", "Lady", "Lt", "Mr", "Mrs", "Ms", "Msgr", "Pfc", "Pres", "Prof", "Pvt", "Rep", "Rev", "Sen", "Sgt", "Sir", "Sr", "Sra", "Srta", "Ven");

global $SPFX_accept;
$SPFX_accept = array("al", "da", "de", "den", "dem", "der", "di", "du", "el", "la", "van", "von");

global $NSFX_accept;
$NSFX_accept = array("Jr", "Sr", "I", "II", "III", "IV", "MD", "PhD");

global $FILE_FORM_accept;
$FILE_FORM_accept = array("avi", "bmp", "gif", "jpeg", "mp3", "ole", "pcx", "tiff", "wav");

// Removed EVEN from empty facts
global $emptyfacts;
$emptyfacts = array("BIRT","CHR","DEAT","BURI","CREM","ADOP","BAPM","BARM","BASM","BLES","CHRA","CONF","FCOM","ORDN","NATU","EMIG","IMMI","CENS","PROB","WILL","GRAD","RETI","BAPL","CONL","ENDL","SLGC","MARR","MARR_CIVIL","MARR_RELIGIOUS","MARR_PARTNERS","MARR_UNKNOWN","SLGS","MARL","ANUL","CENS","DIV","DIVF","ENGA","MARB","MARC","MARS","CHAN","_SEPR","RESI", "DATA", "MAP");
//$separatorfacts = array("BIRT","CHR","DEAT","BURI","CREM","ADOP","BAPM","BARM","BASM","BLES","CHRA","CONF","FCOM","ORDN","NATU","EMIG","IMMI","CENS","PROB","WILL","GRAD","RETI","BAPL","CONL","ENDL","SLGC","EVEN","MARR","SLGS","MARL","ANUL","CENS","DIV","DIVF","ENGA","MARB","MARC","MARS","CHAN","_SEPR","RESI","MAP","SOUR","REPO","OBJE","SEX","NAME","ASSO","NOTE","EMAIL","TITL");

global $separatorfacts;
$separatorfacts = array("SOUR","REPO","OBJE","ASSO","NOTE","RESN","GNOTE");

global $templefacts;
$templefacts = array("SLGC","SLGS","BAPL","ENDL","CONL");

global $nonplacfacts;
$nonplacfacts = array("SLGC","SLGS","ENDL","ASSO","RESN");

global $nondatefacts;
$nondatefacts = array("ABBR","ADDR","ASSO","AFN","AUTH","EMAIL","FAX","NAME","NOTE","GNOTE","OBJE","PHON","PUBL","REPO","SEX","SOUR","TEXT","TITL","WWW","_EMAIL","EMAIL","REFN","NCHI","NMR","RIN","FILE","FORM","_PRIM","_SSHOW","_TYPE","_SCBK","RESN");

global $timefacts;
$timefacts = array("BIRT", "DEAT", "MARR");

global $nonassolayerfacts;
$nonassolayerfacts = array("ASSO","OBJE","REPO","SOUR","NOTE","GNOTE");

global $nonsourlayerfacts;
$nonsourlayerfacts = array("SOUR","OBJE","REPO","GNOTE");

global $nonobjelayerfacts;
$nonobjelayerfacts = array("OBJE","REPO","NOTE","GNOTE");

global $nonnotelayerfacts;
$nonnotelayerfacts = array("NOTE","GNOTE");

global $typefacts;
$typefacts = array();	//-- special facts that go on 2 TYPE lines

global $canhavey_facts;
$canhavey_facts = array("MARR","DIV","BIRT","DEAT","CHR","BURI","CREM"); 
?>