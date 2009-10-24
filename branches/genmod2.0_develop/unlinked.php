<?php
/**
 * Individual List
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 - 2008 Genmod Development Team
 *
 * The individual list shows all individuals from a chosen gedcom file. The list is
 * setup in two sections. The alphabet bar and the details.
 *
 * The alphabet bar shows all the available letters users can click. The bar is built
 * up from the lastnames first letter. Added to this bar is the symbol @, which is
 * shown as a translated version of the variable <var>gm_lang["NN"]</var>, and a
 * translated version of the word ALL by means of variable <var>$gm_lang["all"]</var>.
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
 * @version $Id$
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

PrintHeader($gm_lang["unlink_list"]);
print "<div class=\"center\"><h3>".$gm_lang["unlink_list"]."</h3></div>\n";

$indis = GetUnlinked();
print "<div id=\"content\">";
$printlist = array();

uasort($indis, "ItemSort");
if (count($indis) == 0) {
	print "<div class=\"error center\">".$gm_lang["sc_ged_nounlink"]."</div>";
}
else PrintPersonList($indis);
print "</div>";
PrintFooter();

?>