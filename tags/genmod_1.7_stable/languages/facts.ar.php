<?php
/*=================================================
   charset=utf-8
   Projekt: Genmod
   Datei: facts.ar.php
   Autor: John Finlay
   Translation:	 
   Comments:	Arabic Language Facts file for Genmod.
   Change Log:	See LANG_CHANGELOG.txt
		2004-11-29 - File Created
   2005.02.19 "Genmod" and "GEDCOM" made consistent across all language files  G.Kroll (canajun2eh)
===================================================*/
# $Id: facts.ar.php,v 1.1 2005/10/23 21:54:42 roland-d Exp $
 
if (preg_match("/facts\...\.php$/", $_SERVER["SCRIPT_NAME"])>0) {
        print "You cannot access a language file directly.";
        exit;
}

$factarray["AGE"]	= "عمر";
$factarray["BIRT"]	= "الوِدة";
$factarray["DEAT"]	= "الموت";
$factarray["GIVN"]	= "اِسم الخاص";
$factarray["MARR"]	= "زواج";
$factarray["SURN"]	= "إسم العائلة";

if (file_exists($GM_BASE_DIRECTORY . "languages/facts.ar.extra.php")) require $GM_BASE_DIRECTORY . "languages/facts.ar.extra.php";

?>