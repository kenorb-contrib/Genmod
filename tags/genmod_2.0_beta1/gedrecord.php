<?php
/**
 * Parses gedcom file and displays record for given id in raw text
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
 * @subpackage Charts
 * @version $Id$
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

if (!isset($pid)) $pid = "";
if (!isset($changed)) $changed = false;
$pid = CleanInput($pid);
PrintSimpleHeader($pid);

if ((!$gm_user->userCanViewGedlines()) && (!$gm_user->UserCanAccept())) {
	print "<span class=\"error\">".GM_LANG_ged_noshow."</span>\n";
	print "</body></html>";
	exit;
}
$object =& ConstructObject($pid, $type);
if (!$object->isempty && !$object->disp) {
	PrintPrivacyError(GedcomConfig::$CONTACT_EMAIL);
	print "</body></html>";
	exit;
}
if ($changed) {
	print "<table class=\"facts_table\">\r\n";
	print "<tr class=\"topbottombar\"><td>".GM_LANG_old_record."</td><td>".GM_LANG_new_record."</td></tr>\r\n";
	print "<tr class=\"shade1 wrap\"><td>".nl2br($object->oldprivategedrec)."</td><td>".nl2br($object->newprivategedrec)."</td></tr>\r\n";
	print "<tr class=\"topbottombar\"><td colspan=\"2\">&nbsp;</td></tr></table>\r\n";
}
else {
	$indirec = $object->privategedrec;
	print nl2br($indirec);
	print "<br />";
}
print "<div class=\"center\"><a href=\"#\" onclick=\"window.close();\">".GM_LANG_close_window."</a></div>\n";
PrintSimpleFooter();
?>