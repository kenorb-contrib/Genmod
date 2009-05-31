<?php
/**
 * Interface to review/accept/reject changes made by editing online.
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 Genmod Development Team
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
 * @version $Id: edit_changes.php,v 1.15 2006/04/09 15:53:27 roland-d Exp $
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

/**
 * Inclusion of the editing functions
*/
require "includes/functions_edit.php";

/**
 * Inclusion of the language files
*/
require($GM_BASE_DIRECTORY.$factsfile["english"]);
if (file_exists($GM_BASE_DIRECTORY . $factsfile[$LANGUAGE])) require $GM_BASE_DIRECTORY . $factsfile[$LANGUAGE];

if (!userCanAccept($gm_username)) {
	header("Location: login.php?url=edit_changes.php");
	exit;
}

if (empty($action)) $action="";

print_simple_header($gm_lang["review_changes"]);
?>
<script language="JavaScript" type="text/javascript">
<!--
	function show_gedcom_record(xref) {
		var recwin = window.open("gedrecord.php?changed=1&pid="+xref, "", "top=50,left=50,width=800,height=400,scrollbars=1,scrollable=1,resizable=1");
	}
	function showchanges() {
	   window.location = '<?php print $SCRIPT_NAME; ?>';
   }

	function show_diff(diffurl) {
		window.opener.location = diffurl;
		return false;
	}
//-->
</script>
<?php
print "<div class=\"center\">\n";
print "<span class=\"subheaders\">";
print_help_link("accept_gedcom", "qm", "review_changes");
print $gm_lang["review_changes"];
print "</span><br /><br />\n";

// NOTE: User wants to reject the change
if ($action=="reject") {
	if (reject_change($cid, $gedfile)) {
		print "<br /><br /><b>";
		print $gm_lang["reject_successful"];
		print "</b><br /><br />";
	}
}
// NOTE: User rejects all changes
if ($action=="rejectall") {
	if (reject_change("", $gedfile, true)) {
		print "<br /><br /><b>";
		print $gm_lang["reject_successful"];
		print "</b><br /><br />";
	}
}
// NOTE: User has accepted the change
if ($action=="accept") {
	if (accept_change($cid, $gedfile)) {
		print "<br /><br /><b>";
		print $gm_lang["accept_successful"];
		print "</b><br /><br />";
	}
}
// NOTE: User accepted all changes
if ($action=="acceptall") {
	if (accept_change("", $gedfile, true)) {
		print "<br /><br /><b>";
		print $gm_lang["accept_successful"];
		print "</b><br /><br />";
	}
}

if (change_present()==0) {
	print "<br /><br /><b>";
	print $gm_lang["no_changes"];
	print "</b>";
}
else {
	$showchanges = array();
	$sql = "select ch_cid as cid from ".$TBLPREFIX."changes where ch_gedfile = '".$GEDCOMS[$GEDCOM]["id"]."' order by ch_cid ASC";
	$res = dbquery($sql);
	while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)){
		$sqlcid = "select * from ".$TBLPREFIX."changes where ch_cid = '".$row["cid"]."' AND ch_gedfile = '".$GEDCOMS[$GEDCOM]["id"]."' ";
		$rescid = dbquery($sqlcid);
		$change_row = 0;
		while($rowcid = $rescid->fetchRow(DB_FETCHMODE_ASSOC)){
			$showchanges[$rowcid["ch_cid"]][$change_row]["gid"] = $rowcid["ch_gid"];
			$showchanges[$rowcid["ch_cid"]][$change_row]["gedfile"] = $rowcid["ch_gedfile"];
			$showchanges[$rowcid["ch_cid"]][$change_row]["type"] = $rowcid["ch_type"];
			$showchanges[$rowcid["ch_cid"]][$change_row]["user"] = $rowcid["ch_user"];
			$showchanges[$rowcid["ch_cid"]][$change_row]["time"] = $rowcid["ch_time"];
			$showchanges[$rowcid["ch_cid"]][$change_row]["old"] = $rowcid["ch_old"];
			$showchanges[$rowcid["ch_cid"]][$change_row]["new"] = $rowcid["ch_new"];
			$showchanges[$rowcid["ch_cid"]][$change_row]["locked"] = $rowcid["ch_delete"];
			$change_row++;
		}
	}
	print "<table class=\"list_value center\">";
	$changegids = array();
	foreach ($showchanges as $cid => $showchange) {
		print "<tr class=\"topbottombar $TEXT_DIRECTION\"><td colspan=\"2\">".$gm_lang["change_type"].": ".$gm_lang[$showchanges[$cid][0]["type"]]."</td><td>";
		if (!$showchanges[$cid][0]["locked"]) print "<a href=\"edit_changes.php?action=accept&amp;cid=$cid&amp;gedfile=".$showchanges[$cid][0]["gedfile"]."\">".$gm_lang["accept"]."</a> | <a href=\"edit_changes.php?action=reject&amp;cid=$cid&amp;gedfile=".$showchanges[$cid][0]["gedfile"]."\">".$gm_lang["reject"]."</a>";
		print "</td>";
		print "</td></tr>";
		print "<tr class=\"shade2\"><td>".$gm_lang["name"]."</td><td>".$gm_lang["username"]."</td><td>".$gm_lang["date"]."</td></tr><tr>";
		if ($showchanges[$cid][0]["locked"]) print "<tr class=\"locked\"><td colspan=\"4\">".strtoupper($gm_lang["locked"])."</td></tr>";
		foreach ($showchange as $key => $change) {
			print "<tr class=\"shade1\"><td>";
			$gedrec = find_gedcom_record($change["gid"]);
			$type = id_type($change["gid"]);
			switch ($type) {
				case "INDI":
					if (empty($gedrec)) $names = get_indi_names(retrieve_changed_fact($change["gid"], "INDI"));
					else $names = get_indi_names($gedrec);
					$printname = "<b>".PrintReady(check_NN($names[0][0]))."</b> &lrm;(".$change["gid"].")&lrm;<br />\n";
					print $printname;
					$changegids["individuals"][$change["gid"]] = $printname;
					break;
				case "FAM":
					$printname = "<b>".PrintReady(get_family_descriptor($change["gid"]))."</b> &lrm;(".$change["gid"].")&lrm;<br />\n";;
					print $printname;
					$changegids["families"][$change["gid"]] = $printname;
					break;
				case "SOUR":
					$name = get_gedcom_value("ABBR", 1, $gedrec);
					if (empty($name)) $name = get_gedcom_value("TITL", 1, $gedrec);
					$printname = "<b>".PrintReady($name)."</b> &lrm;(".$change["gid"].")&lrm;<br />\n";
					print $printname;
					$changegids["source"][$change["gid"]] = $printname;
					break;
				case "REPO":
					$name = get_gedcom_value("NAME", 1, $gedrec);
					if (empty($name)) $name = get_gedcom_value("NAME", 1, retrieve_changed_fact($change["gid"], "REPO"));
					$printname = "<b>".PrintReady($name)."</b> &lrm;(".$change["gid"].")&lrm;<br />\n";
					print $printname;
					$changegids["repo"][$change["gid"]] = $printname;
					break;
				case "OBJE":
					$printname = "<b>".$gm_lang["media"]."</b> &lrm;(".$change["gid"].")&lrm;<br />\n";;
					print $printname;
					$changegids["media"][$change["gid"]] = $printname;
					break;
			}
			print "</td>";
			print "<td>";
			$cuser = getUser($change["user"]);
			if ($cuser) print PrintReady($cuser["firstname"]." ".$cuser["lastname"]);
			print "</td>";
			print "<td>".get_changed_date(date("j M Y",$change["time"]))." ".date($TIME_FORMAT, $change["time"])."</td></tr>";
			
		}
	}
	print "</table>";
	/**
	print "<br /><br /><table class=\"list_table\">";
	print "<tr><td class=\"list_value $TEXT_DIRECTION\">";
	$changedgedcoms = array();
	foreach($gm_changes as $cid=>$changes) {
		for($i=0; $i<count($changes); $i++) {
			$change = $changes[$i];
			if ($i==0) {
				$changedgedcoms[$change["gedcom"]] = true;
				if ($GEDCOM != $change["gedcom"]) {
					$GEDCOM = $change["gedcom"];
				}
				// find_record_in_file obsolete
				// get the id from the DB
				$gedrec = find_gedcom_record($change["gid"]);
				if (empty($gedrec)) $gedrec = $change["undo"];
				$ct = preg_match("/0 @(.*)@(.*)/", $gedrec, $match);
				if ($ct>0) $type = trim($match[2]);
				else $type = "INDI";
				if ($type=="INDI") {
					$names = get_indi_names($gedrec);
					print "<b>".PrintReady(check_NN($names[0][0]))."</b> &lrm;(".$change["gid"].")&lrm;<br />\n";
				}
				else if ($type=="FAM") print "<b>".PrintReady(get_family_descriptor($change["gid"]))."</b> &lrm;(".$change["gid"].")&lrm;<br />\n";
				else if ($type=="SOUR") {
					$name = get_gedcom_value("ABBR", 1, $gedrec);
					if (empty($name)) $name = get_gedcom_value("TITL", 1, $gedrec); 
					print "<b>".PrintReady($name)."</b> &lrm;(".$change["gid"].")&lrm;<br />\n";
				}
				else print "<b>".$factarray[$type]."</b> &lrm;(".$change["gid"].")&lrm;<br />\n";
				if ($type=="INDI") print "<a href=\"#\" onclick=\"return show_diff('individual.php?pid=".$change["gid"]."&amp;ged=".$change["gedcom"]."&amp;show_changes=yes');\">".$gm_lang["view_change_diff"]."</a> | \n";
				if ($type=="FAM") print "<a href=\"#\" onclick=\"return show_diff('family.php?famid=".$change["gid"]."&amp;ged=".$change["gedcom"]."&amp;show_changes=yes');\">".$gm_lang["view_change_diff"]."</a> | \n";
				if ($type=="SOUR") print "<a href=\"#\" onclick=\"return show_diff('source.php?sid=".$change["gid"]."&amp;ged=".$change["gedcom"]."&amp;show_changes=yes');\">".$gm_lang["view_change_diff"]."</a> | \n";
				print "<a href=\"javascript:show_gedcom_record('".$change["gid"]."');\">".$gm_lang["view_gedcom"]."</a> | ";
				print "<a href=\"#\" onclick=\"return edit_raw('".$change["gid"]."');\">".$gm_lang["edit_raw"]."</a><br />";
				print "<div class=\"indent\">\n";
				print $gm_lang["changes_occurred"]."<br />\n";
				print "<table class=\"list_table\">\n";
				print "<tr><td class=\"list_label\">".$gm_lang["undo"]."</td>";
				print "<td class=\"list_label\">".$gm_lang["accept"]."</td>";
				print "<td class=\"list_label\">".$gm_lang["type"]."</td><td class=\"list_label\">".$gm_lang["username"]."</td><td class=\"list_label\">".$gm_lang["date"]."</td><td class=\"list_label\">GEDCOM</td></tr>\n";
			}
			if ($i==count($changes)-1) {
				print "<td class=\"list_value $TEXT_DIRECTION\"><a href=\"edit_changes.php?action=undo&amp;cid=$cid&amp;index=$i\">".$gm_lang["undo"]."</a></td>";
				print "<td class=\"list_value $TEXT_DIRECTION\"><a href=\"edit_changes.php?action=accept&amp;cid=$cid\">".$gm_lang["accept"]."</a></td>\n";
			}
			else {
				print "<td class=\"list_value $TEXT_DIRECTION\"><br /></td>";
				print "<td class=\"list_value $TEXT_DIRECTION\"><br /></td>";
			}
			print "<td class=\"list_value $TEXT_DIRECTION\"><b>".$gm_lang[$change["type"]]."</b></td>\n";
			print "<td class=\"list_value $TEXT_DIRECTION\"><a href=\"#\" onclick=\"return reply('".$change["user"]."','".$gm_lang["review_changes"]."')\" alt=\"".$gm_lang["message"]."\">";
			$cuser = getUser($change["user"]);
			if ($cuser) {
				print PrintReady($cuser["firstname"]." ".$cuser["lastname"]);
			}
 			print PrintReady("&nbsp;(".$change["user"].")")."</a></td>\n";
 			print "<td class=\"list_value $TEXT_DIRECTION\">".get_changed_date(date("j M Y",$change["time"]))." ".date($TIME_FORMAT, $change["time"])."</td>\n";
			print "<td class=\"list_value $TEXT_DIRECTION\">".$change["gedcom"]."</td>\n";
			print "</tr>\n";
			if ($i==count($changes)-1) {
				print "</table>\n";
				print "</div><br />";
			}
		}
	}
	print "</td></tr></table>";
	*/
	print "<br /><br /><table class=\"list_table\">\r\n";
	print "<tr><td class=\"topbottombar\" colspan=\"2\">";
	print_help_link("view_gedcom_help", "qm", "view_gedcom");
	print $gm_lang["view_gedcom"]."</td></tr>";
	foreach ($changegids as $type => $gids) {
		print "<tr><td class=\"shade2\">".$gm_lang[$type]."</td></tr>";
		foreach ($gids as $gid => $name) {
			print "<tr><td class=\"shade1 $TEXT_DIRECTION\"><a href=\"javascript:show_gedcom_record('".$gid."');\">".$name."</a></td></tr>";
		}
	}
	print "</table>";
	//-- accept and reject all
	print "<br /><br /><table class=\"list_table\"><tr><td class=\"list_label\">".$gm_lang["accept_all"]."</td>";
	print "<td class=\"list_label\">".$gm_lang["reject_all"]."</td></tr>";
	print "<tr><td class=\"list_value\">";
	print "<a href=\"edit_changes.php?action=acceptall&amp;gedfile=".$GEDCOMS[$GEDCOM]["id"]."\">".$gm_lang["accept_all"]."</a>\n";
	print "</td>";
	print "<td class=\"list_value\">";
	print "<a href=\"edit_changes.php?action=rejectall&amp;gedfile=".$GEDCOMS[$GEDCOM]["id"]."\" onclick=\"return confirm('".$gm_lang["reject_all_confirm"]."');\">".$gm_lang["reject_all"]."</a>\n";
	print "</td></tr></table>";
}
print "<br /><br />\n</center></div>\n";
print "<center><a href=\"#\" onclick=\"if (window.opener.showchanges) window.opener.showchanges(); window.close();\">".$gm_lang["close_window"]."</a><br /></center>\n";
print_simple_footer();
?>