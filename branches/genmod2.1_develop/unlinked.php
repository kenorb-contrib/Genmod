<?php
/**
 * Individual List
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 - 2012 Genmod Development Team
 *
 * The individual list shows all individuals from a chosen gedcom file. The list is
 * setup in two sections. The alphabet bar and the details.
 *
 * The alphabet bar shows all the available letters users can click. The bar is built
 * up from the lastnames first letter. Added to this bar is the symbol @, which is
 * shown as a translated version of the variable <var>gm_lang["NN"]</var>, and a
 * translated version of the word ALL by means of variable <var>GM_LANG_allGM_LANG_</var>.
 *
 * The details can be shown in two ways, with surnames or without surnames. By default
 * the user first sees a list of surnames of the chosen letter and by clicking on a
 * surname a list with names of people with that chosen surname is displayed.
 *
 * Beneath the details list is the option to skip the surname list or show it.
 * Depending on the current status of the list.
 *
 * @package Genmod
 * @subpackage Lists
 * @version $Id: unlinked.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

$unlinkedlist_controller = new IndilistController();

PrintHeader($unlinkedlist_controller->pagetitle);
print "<div id=\"UnlinkedContent\">";
print "<div class=\"PageTitleName\">".GM_LANG_unlink_list."</div>\n";
$indis = ListFunctions::GetIndiList("no", "unlinked");

if (count($indis) == 0) {
	print "<div class=\"Error\">".GM_LANG_sc_ged_nounlink."</div>";
}
else $unlinkedlist_controller->PrintPersonList($indis);
print "</div>";
PrintFooter();
?>