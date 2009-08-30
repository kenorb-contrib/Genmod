<?php
/**
 * ToDo List
 *
 * Parses gedcom file and displays a list of the repositories in the file.
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
 * @subpackage Lists
 * @version $Id$
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

print_header($gm_lang["actionlist"]);

if (!$gm_user->ShowActionLog()) {
	print "<span class=\"error\">".$gm_lang["access_denied"]."</span>";
	print_footer();
	exit;
}

if (!isset($sort)) $sort = "person"; // Default to sort on person
if (!isset($status)) $status = "0"; // Default to open ToDo's

print "<div class=\"center\">";
print "<h3>".$gm_lang["actionlist"]."</h3>\n\t";

if ($view != "preview") {
	print "\n\t<form name=\"actionlist\" action=\"actionlist.php\" method=\"post\">";
	print "\n\t\t<table class=\"list_table center $TEXT_DIRECTION\">\n\t\t\t<tr>";
	// Upper options block
	print "\n\t\t\t<td class=\"shade3 center\" colspan=\"4\">".$gm_lang["choose"]."</td></tr>";
	// Sort by part 1
	print "<tr><td class=\"shade1\">".$gm_lang["sort_by_person"]."</td>";
	print "<td class=\"shade2\" style=\"vertical-align: middle;\"><input type=\"radio\" name=\"sort\" value=\"person\" onclick=\"submit()\"";
	if ($sort == "person") print " checked=\"checked\" ";
	print " /></td>";
	// Show what status
	print "<td class=\"shade1\" rowspan=\"2\" style=\"vertical-align: middle;\">".$gm_lang["show_status"]."</td>";
	print "<td class=\"shade2\"  rowspan=\"2\" style=\"vertical-align: middle;\">";
	print "<select name=\"status\" onchange=\"submit()\">";
	print "<option value=\"\"";
	if ($status == "") print " selected=\"selected\"";
	print ">".$gm_lang["all"]."</option>";
	print "<option value=\"0\"";
	if ($status == "0") print " selected=\"selected\"";
	print ">".$gm_lang["action0"]."</option>";
	print "<option value=\"1\"";
	if ($status == "1") print " selected=\"selected\"";
	print ">".$gm_lang["action1"]."</option>";
	print "</select></td></tr>";
	// Sort by part 2
	print "<tr><td class=\"shade1\">".$gm_lang["sort_by_repo"]."</td>";
	print "<td class=\"shade2\" style=\"vertical-align: middle;\"><input type=\"radio\" name=\"sort\" value=\"repo\" onclick=\"submit()\"";
	if ($sort == "repo") print " checked=\"checked\" ";
	print " /></td></tr>";
	print "</table></form>";
}
       
// Get the data
$actionlist = ActionController::GetActionList($status, $sort == "repo");
print "<br />";

if (count($actionlist) == 0) {
	print "<span class=\"error\">".$gm_lang["no_action_found"]."</span>";
}
else {
	print "<div class=\"width90 center\"><table class=\"$TEXT_DIRECTION center\">";
	if ($sort == "person") {
		print "<tr><td class=\"shade2\">".$gm_lang["action_for_id"]."</td><td class=\"shade2\">".$gm_lang["repo"]."</td><td class=\"shade2\">".$gm_lang["status"]."</td><td class=\"shade2\">".$gm_lang["description"]."</td></tr>";
		foreach($actionlist as $key => $action) {
			if ($action->disp) {
				print "<tr><td class=\"shade1 wrap\">";
				if (is_object($action->indi_obj)) $action->indi_obj->PrintListPerson(false, true);
				else print "&nbsp;";
				print "</td><td class=\"shade1 wrap\">";
				if (is_object($action->repo_obj)) $action->repo_obj->PrintListRepository(false, false);
				else print "&nbsp;";
				print "</td><td class=\"shade1\">".$gm_lang["action".$action->status]."</td><td class=\"shade1 wrap\">".nl2br(stripslashes($action->text))."</td></tr>";
			}
		}
	}
	if ($sort == "repo") {
		print "<tr><td class=\"shade2\">".$gm_lang["repo"]."</td><td class=\"shade2\">".$gm_lang["action_for_id"]."</td><td class=\"shade2\">".$gm_lang["status"]."</td><td class=\"shade2\">".$gm_lang["description"]."</td></tr>";
		foreach($actionlist as $key => $action) {
			if ($action->disp) {
				print "<tr><td class=\"shade1 wrap\">";
				if (is_object($action->repo_obj)) $action->repo_obj->PrintListRepository(false, false);
				else print "&nbsp;";
				print "</td><td class=\"shade1 wrap\">";
				if (is_object($action->indi_obj)) $action->indi_obj->PrintListPerson(false, true);
				else print "&nbsp;";
				print "</td><td class=\"shade1\">".$gm_lang["action".$action->status]."</td><td class=\"shade1 wrap\">".nl2br(stripslashes($action->text))."</td></tr>";
			}
		}
	}
	print "</table></div>";
}
print "</div>";
print_footer();
?>