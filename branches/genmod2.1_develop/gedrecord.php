<?php
/**
 * Parses gedcom file and displays record for given id in raw text
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
 * @subpackage Charts
 * @version $Id: gedrecord.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

if (!isset($pid)) $pid = "";
if (!isset($type)) $type = "";
if (!isset($changed)) $changed = false;
$pid = CleanInput($pid);
PrintSimpleHeader($pid);

if ((!$gm_user->userCanViewGedlines()) && (!$gm_user->UserCanAccept())) {
	EditFunctions::PrintFailMessage(GM_LANG_ged_noshow);
}
$object =& ConstructObject($pid, $type);

if (!is_object($object) || (!$object->isempty && !$object->disp)) {
	PrintPrivacyError(GedcomConfig::$CONTACT_EMAIL);
}
else
{
	print "<table class=\"ListTable\">\r\n";
	if ($changed) {
		print "<tr><td class=\"ListTableHeader\" colspan=\"2\">".GM_LANG_view_gedrec."</td></tr>";
		print "<tr><td class=\"ListTableColumnHeader\">".GM_LANG_old_record."</td><td class=\"ListTableColumnHeader\">".GM_LANG_new_record."</td></tr>\r\n";
		print "<tr><td class=\"ListTableContent\">".nl2br($object->oldprivategedrec)."</td><td class=\"ListTableContent\">".nl2br($object->newprivategedrec)."</td></tr>\r\n";
	}
	else {
		$indirec = $object->privategedrec;
		print "<tr><td class=\"ListTableHeader\">".GM_LANG_view_gedrec."</td></tr>";
		print "<tr><td class=\"ListTableContent\">".nl2br($indirec)."</td></tr>";
	}
	print "</table>";
}	
print "<div class=\"CloseWindow\"><a href=\"#\" onclick=\"window.close();\">".GM_LANG_close_window."</a></div>\n";
PrintSimpleFooter();
?>