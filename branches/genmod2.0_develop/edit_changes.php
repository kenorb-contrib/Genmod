<?php
/**
 * Interface to review/accept/reject changes made by editing online.
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
 * @version $Id$
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

/**
 * Inclusion of the editing functions
*/
require "includes/functions/functions_edit.php";

if (!$gm_user->userCanAccept()) {
	if (empty($LOGIN_URL)) header("Location: login.php?url=edit_changes.php");
	else header("Location: ".$LOGIN_URL."?url=edit_changes.php");
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
print "<div id=\"content_popup\" class=\"center\">\n";
print "<span class=\"subheaders\">";
print_help_link("accept_gedcom", "qm", "review_changes");
print $gm_lang["review_changes"];
print "</span><br /><br />\n";

// NOTE: User wants to reject the change
if ($action=="reject") {
	if (RejectChange($cid, $gedfile)) {
		print "<br /><br /><b>";
		print $gm_lang["reject_successful"];
		print "</b><br /><br />";
	}
}
// NOTE: User rejects all changes
if ($action=="rejectall") {
	if (RejectChange("", $gedfile, true)) {
		print "<br /><br /><b>";
		print $gm_lang["reject_successful"];
		print "</b><br /><br />";
	}
}
// NOTE: User has accepted the change
if ($action=="accept") {
	if (AcceptChange($cid, $gedfile)) {
		print "<br /><br /><b>";
		print $gm_lang["accept_successful"];
		print "</b><br /><br />";
	}
}
// NOTE: User accepted all changes
if ($action=="acceptall") {
	if (AcceptChange("", $gedfile, true)) {
		print "<br /><br /><b>";
		print $gm_lang["accept_successful"];
		print "</b><br /><br />";
	}
}

if (GetChangeData(true, "", true)==0) {
	print "<br /><br /><b>";
	print $gm_lang["no_changes"];
	print "</b>";
}
else {
	
	// Trace on/off
	$trace = false;
	
	// Array to store all changes groupwise
	$changegroup = array();
	
	// List of ID's in previous changes
	$foundids = array();
	
	// List of previous level 0 ID changes
	$found0ids = array();
	
	// List of names that have changes, for printing purposes only
	$changegids = array();
	
	// First read all changes
	$sql = "SELECT DISTINCT ch_cid AS cid FROM ".$TBLPREFIX."changes WHERE ch_gedfile = '".$GEDCOMID."' ORDER BY ch_cid ASC, ch_fact ASC, ch_time DESC";
	$res = NewQuery($sql);
	while($row = $res->FetchAssoc()){
		$sqlcid = "SELECT * FROM ".$TBLPREFIX."changes WHERE ch_cid = '".$row["cid"]."' AND ch_gedfile = '".$GEDCOMID."' ORDER BY ch_id ASC";
		$rescid = NewQuery($sqlcid);
		$change_row = 0;
		while($rowcid = $rescid->FetchAssoc()){
			if ($trace) print "ch_id: ".$rowcid["ch_id"];
			// First we handle the level 0 changes.
			// ADD ID
			// If the ID is new, it is not dependent
			if (preg_match("/0 @.*@ /", $rowcid["ch_new"]) > 0 && empty($rowcid["ch_old"])) {
				if ($trace) print "Found add ".$rowcid["ch_gid"]." ";
				$found0ids[$rowcid["ch_gid"]] = $row["cid"];
				// But we check if it's really new: there must be no DB record AND no previous changes on this ID
				// If not, there is a problem and we can only reject.
				$oldrec = FindGedcomRecord($rowcid["ch_gid"], "", true);
				if (empty($oldrec) && !in_array($rowcid["ch_gid"], $foundids)) {
					$rowcid["canaccept"] = true;
					$rowcid["canreject"] = true;
				}
				else {
					$rowcid["canaccept"] = false;
					$rowcid["canreject"] = true;
				}
				if ($trace) print "a: ".$rowcid["canaccept"]." r: ".$rowcid["canreject"]."<br />";
			}
			// DELETE ID
			// If it's a delete, we check if the DB gedcom rec is the same. If so, there are no dependencies
			// If not, we have previous changes, OR we have a problem.
			else if (preg_match("/0 @.*@ /", $rowcid["ch_old"]) > 0 && empty($rowcid["ch_new"])) {
				if ($trace) print "Found a deletion ".$rowcid["ch_gid"]." ";
				$found0ids[$rowcid["ch_gid"]] = $row["cid"];
				$oldrec = FindGedcomRecord($rowcid["ch_gid"], "", true);
				if ($oldrec != $rowcid["ch_old"]) {
					// Not the same, check if we really have previous changes on this ID
					if (in_array($rowcid["ch_gid"], $foundids)) {
						// Yes, found one
						$rowcid["canaccept"] = false;
						$rowcid["canreject"] = false;
					}
					else {
						// No, we have a problem and must reject
						$rowcid["canaccept"] = false;
						$rowcid["canreject"] = true;
					}
				}
				else {
					// ok, it's the first and correct change
					$rowcid["canaccept"] = true;
					$rowcid["canreject"] = true;
				}
				if ($trace) print "a: ".$rowcid["canaccept"]." r: ".$rowcid["canreject"]."<br />";
			}
			// EDIT ID
			// Here the record is edited. If based on the DB, we can accept it because there are no dependencies
			// If not, we either have previous changes OR a problem. Same code as DELETE
			else if (preg_match("/0 @.*@ /", $rowcid["ch_old"]) > 0 && preg_match("/0 @.*@ /", $rowcid["ch_new"]) > 0) {
				if ($trace) print "Found edit ".$rowcid["ch_gid"]." ";
				$found0ids[$rowcid["ch_gid"]] = $row["cid"];
				$oldrec = FindGedcomRecord($rowcid["ch_gid"], "", true);
				if ($oldrec != $rowcid["ch_old"]) {
					// Not the same, check if we really have previous changes on this ID
					if (in_array($rowcid["ch_gid"], $foundids)) {
						// Yes, found one
						$rowcid["canaccept"] = false;
						$rowcid["canreject"] = false;
					}
					else {
						// No, we have a problem and must reject
						$rowcid["canaccept"] = false;
						$rowcid["canreject"] = true;
					}
				}
				else {
					// ok, it's the first and correct change
					$rowcid["canaccept"] = true;
					$rowcid["canreject"] = true;
				}
				if ($trace) print "a: ".$rowcid["canaccept"]." r: ".$rowcid["canreject"]."<br />";
			}
			else {
				// FACTS
				// Now we check the fact level changes
				// If no previous changes on this ID, we check if the old value is in the DB
				if (!in_array($rowcid["ch_gid"], $foundids)) {
					if ($trace) print "Fact, no previous changes".$rowcid["ch_gid"];
					$oldrec = FindGedcomRecord($rowcid["ch_gid"], "", true);
					// Don't check when the old value is empty, Just see if the ID exists.
					if (empty($rowcid["ch_old"])) {
						if (!empty($oldrec)) {
							$rowcid["canaccept"] = true;
							$rowcid["canreject"] = true;
						}
						else {
							// We want to add something to a non existent ID!!!!!! Reject!
							$rowcid["canaccept"] = false;
							$rowcid["canreject"] = true;
						}
					}
					else {
						// here we handle fact changes and deletes.
						$oldsub = GetSubRecord(1, trim($rowcid["ch_old"]), $oldrec);
						if (!empty($oldsub)) {
							// It's in the DB, so a valid change
							$rowcid["canaccept"] = true;
							$rowcid["canreject"] = true;
						}
						else {
							// We have a problem, reject only!
							$rowcid["canaccept"] = false;
							$rowcid["canreject"] = true;
						}
					}
				if ($trace) print "a: ".$rowcid["canaccept"]." r: ".$rowcid["canreject"]."<br />";
				}
				else {
					if ($trace) print "Fact, previous changes".$rowcid["ch_gid"];
					// There are previous changes.
					// If the previous changes are level 0, we cannot be sure about what happens to this fact, so do nothing
					// One exeption: if the previous change is in the same group, it will be accepted together, so we can accept it.
					if ($trace) print "Check for ".$rowcid["ch_gid"]." in ";
					if ($trace) print_r($found0ids);
					if ($trace) print"<br />";
					if (array_key_exists($rowcid["ch_gid"], $found0ids)) {
						if ($trace) print "check same group";
						if ($found0ids[$rowcid["ch_gid"]] == $row["cid"]) {
							// Same change group
							$rowcid["canaccept"] = true;
							$rowcid["canreject"] = true;
						}
						else {
							// Different change group
							$rowcid["canaccept"] = false;
							$rowcid["canreject"] = false;
						}
					}
					else {
						// ok, no previous level 0 changes, but there are previous fact level changes.
						// We will loop through the previous changes and see if we can find the old value somewhere.
						// We will only do this if there was an old value, because add's have no fact level dependencies
						$foundearlier = false;
						if (!empty($rowcid["ch_old"])) {
							foreach ($changegroup as $groupid => $changes) {
								foreach ($changes as $nr => $change) {
									// Make sure we talk about the same gid here
									if ($change["gid"] == $rowcid["ch_gid"]) {
										if (trim($change["new"]) == trim($rowcid["ch_old"])) {
											$foundearlier = true;
											break 2;
										}
									}
								}
							}
							if ($foundearlier) {
								// It's a change on a change, do nothing!
								$rowcid["canaccept"] = false;
								$rowcid["canreject"] = false;
							}
							else {
								// It must be a change on the DB. Check that!
								$oldrec = FindGedcomRecord($rowcid["ch_gid"], "", true);
								$oldsub = GetSubRecord(1, trim($rowcid["ch_old"]), $oldrec);
								if (!empty($oldsub)) {
									// It's in the DB, so a valid change
									$rowcid["canaccept"] = true;
									$rowcid["canreject"] = true;
								}
								else {
									// We have a problem, reject only!
									$rowcid["canaccept"] = false;
									$rowcid["canreject"] = true;
								}
							}
						}
						// it's an add fact, not depending on level 0 changes, so we don't loop and can do all
						else {
							$rowcid["canaccept"] = true;
							$rowcid["canreject"] = true;
						}
					}
					if ($trace) print "a: ".$rowcid["canaccept"]." r: ".$rowcid["canreject"]."<br />";
				}
			}
					
			$changegroup[$rowcid["ch_cid"]][$change_row]["gid"] = $rowcid["ch_gid"];
			$changegroup[$rowcid["ch_cid"]][$change_row]["gedfile"] = $rowcid["ch_gedfile"];
			$changegroup[$rowcid["ch_cid"]][$change_row]["type"] = $rowcid["ch_type"];
			$changegroup[$rowcid["ch_cid"]][$change_row]["user"] = $rowcid["ch_user"];
			$changegroup[$rowcid["ch_cid"]][$change_row]["time"] = $rowcid["ch_time"];
			$changegroup[$rowcid["ch_cid"]][$change_row]["old"] = $rowcid["ch_old"];
			$changegroup[$rowcid["ch_cid"]][$change_row]["new"] = $rowcid["ch_new"];
			$changegroup[$rowcid["ch_cid"]][$change_row]["locked"] = $rowcid["ch_delete"];
			$changegroup[$rowcid["ch_cid"]][$change_row]["fact"] = $rowcid["ch_fact"];
			$changegroup[$rowcid["ch_cid"]][$change_row]["canaccept"] = $rowcid["canaccept"];
			$changegroup[$rowcid["ch_cid"]][$change_row]["canreject"] = $rowcid["canreject"];
			$foundids[] = $rowcid["ch_gid"];
			$change_row++;
		}
	}

	// We have all the changes and found out the change dependencies.
	// Now we set the group dependencies:
	// if all changes in a group can be accepted, the group can be accepted.
	// if all changes in the group can be rejected, the group can be rejected.
	foreach ($changegroup as $groupid => $changes) {
		$rejectgroup = true;
		$acceptgroup = true;
		foreach ($changes as $nr => $change) {
			if ($change["canaccept"] == false && $acceptgroup == true) $acceptgroup = false;
			if ($change["canreject"] == false && $rejectgroup == true) $rejectgroup = false;
		}
		$changegroup[$groupid]["canaccept"] = $acceptgroup;
		$changegroup[$groupid]["canreject"] = $rejectgroup;
	}
	// We have all downwards dependencies now. 
	// However, if a change is the last change for a specific pid, it can be rejected.
	// For this to be true, all pids in the change group must not have been found later.
	// We sort the array in reverse groupid order, then walk through.
	krsort($changegroup);
	$laterids = array();
	foreach ($changegroup as $groupid => $changes) {
		$foundlater = false;
		// We check if the pids are found in later changes. We cannot update the laterids array now, because there can be 2 changes to the same pid 
		// in one group, which would cause that the group cannot be rejected.
		// We skip groups that can already be rejected.
		if (!$changegroup[$groupid]["canreject"]) {
			foreach ($changes as $nr => $change) {
				// A changegroup also contains accept and reject attributes. We must skip these, and only get the changes which are arrays.
				if (is_array($change)) {
					if (array_key_exists($change["gid"], $laterids)) $foundlater = true;
				}
			}
			if (!$foundlater) $changegroup[$groupid]["canreject"] = true;
			// now we add the pids of this group to the found array
			foreach ($changes as $nr => $change) {
				if (is_array($change)) $laterids[$change["gid"]] = true;
			}
		}
	}
	// Sort the array back to its original order
	ksort($changegroup);

	// Now, at last, we can start printing!
	print "<table class=\"shade1\">";
	foreach ($changegroup as $groupid => $changes) {
		print "<tr class=\"topbottombar shade2 $TEXT_DIRECTION\"><td colspan=\"2\">".$gm_lang["change_type"].": ";
		if (isset($gm_lang[$changegroup[$groupid][0]["type"]])) print $gm_lang[$changegroup[$groupid][0]["type"]];
		else print $changegroup[$groupid][0]["type"];
		if (defined("GM_FACT_".$changegroup[$groupid][0]["fact"])) print ": ".constant("GM_FACT_".$changegroup[$groupid][0]["fact"]);
		print "</td><td>";
		if ($changegroup[$groupid]["canaccept"]) print "<a href=\"edit_changes.php?action=accept&amp;cid=$groupid&amp;gedfile=".$changegroup[$groupid][0]["gedfile"]."\">".$gm_lang["accept"]."</a>";
		if ($changegroup[$groupid]["canaccept"] && $changegroup[$groupid]["canreject"]) print " | ";
		if ($changegroup[$groupid]["canreject"]) print "<a href=\"edit_changes.php?action=reject&amp;cid=$groupid&amp;gedfile=".$changegroup[$groupid][0]["gedfile"]."\">".$gm_lang["reject"]."</a>";
		print "</td></tr>";
		print "<tr><td>".$gm_lang["name"]."</td><td>".$gm_lang["username"]."</td><td>".$gm_lang["date"]."</td></tr><tr>";
		foreach ($changes as $key => $change) {
			// $change also contains the canaccept and canreject values. Only process if it's an array
			if (is_array($change)) {
				print "<tr class=\"shade1\"><td>";
				$gedrec = FindGedcomRecord($change["gid"], "", true);
				if (empty($gedrec)) {
					if (GetChangeData(true, $change["gid"], true)) {
						$rec = GetChangeData(false, $change["gid"], true, "gedlines");
						$gedrec = $rec[$GEDCOM][$change["gid"]];
					}
				}
				$type = IdType($change["gid"]);
				switch ($type) {
					case "INDI":
						if (empty($gedrec)) $gedrec = RetrieveChangedFact($change["gid"], "INDI", "");
						if (empty($gedrec)) $gedrec = RetrieveChangedFact($change["gid"], "FAMC", "");
						$names = GetIndiNames($gedrec);
						$printname = "<b>".PrintReady(CheckNN($names[0][0]))."</b> &lrm;(".$change["gid"].")&lrm;<br />\n";
						print $printname;
						$changegids["individuals"][$change["gid"]] = $printname;
						break;
					case "FAM":
						$printname = "<b>".PrintReady(GetFamilyDescriptor($change["gid"], "", $gedrec, true))."</b> &lrm;(".$change["gid"].")&lrm;<br />\n";;
						print $printname;
						$changegids["families"][$change["gid"]] = $printname;
						break;
					case "SOUR":
						$name = GetGedcomValue("ABBR", 1, $gedrec);
						if (empty($name)) $name = GetGedcomValue("TITL", 1, $gedrec);
						if (empty($name)) $name = GetGedcomValue("ABBR", 1, RetrieveChangedFact($change["gid"], "SOUR", $gedrec));
						if (empty($name)) $name = GetGedcomValue("TITL", 1, RetrieveChangedFact($change["gid"], "SOUR", $gedrec));
						$printname = "<b>".PrintReady($name)."</b> &lrm;(".$change["gid"].")&lrm;<br />\n";
						print $printname;
						$changegids["source"][$change["gid"]] = $printname;
						break;
					case "REPO":
						$name = GetGedcomValue("NAME", 1, $gedrec);
						if (empty($name)) $name = GetGedcomValue("NAME", 1, RetrieveChangedFact($change["gid"], "REPO", $gedrec));
						$printname = "<b>".PrintReady($name)."</b> &lrm;(".$change["gid"].")&lrm;<br />\n";
						print $printname;
						$changegids["repo"][$change["gid"]] = $printname;
						break;
					case "OBJE":
						$printname = "<b>".$gm_lang["media"]."</b> &lrm;(".$change["gid"].")&lrm;<br />\n";;
						print $printname;
						$changegids["media"][$change["gid"]] = $printname;
						break;
					case "SUBM":
						$printname = "<b>".$gm_lang["submitter_record"]."</b> &lrm;(".$change["gid"].")&lrm;<br />\n";;
						print $printname;
						$changegids["subm"][$change["gid"]] = $printname;
						break;
					case "NOTE":
						$printname = "<b>".$gm_lang["note"]."</b> &lrm;(".$change["gid"].")&lrm;<br />\n";;
						print $printname;
						$changegids["note"][$change["gid"]] = $printname;
						break;
				}
				if ($trace) print "a".$changegroup[$groupid]["canaccept"]."r".$changegroup[$groupid]["canreject"];
				print "</td>";
				print "<td>";
				$cuser =& User::GetInstance($change["user"]);
				if (!$cuser->is_empty) print PrintReady($cuser->firstname." ".$cuser->lastname);
				print "</td>";
				// NOTE: Use European time format if none is specified.
				if (empty($TIME_FORMAT)) $TIME_FORMAT = "H:m:s";
				print "<td>".GetChangedDate(date("j M Y",$change["time"]))." ".date($TIME_FORMAT, $change["time"])."</td></tr>";
			
			}
		}
	}
	print "</table>";
	print "<br /><br /><table class=\"list_table center\">\r\n";
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
	print "<br /><br /><table class=\"list_table center\">";
	print "<tr><td class=\"shade2\">";
	print "<a href=\"edit_changes.php?action=acceptall&amp;gedfile=".$GEDCOMID."\" onclick=\"return confirm('".$gm_lang["accept_all_confirm"]."');\">".$gm_lang["accept_all"]."</a>\n";
	print "</td>";
	print "<td class=\"shade2\">";
	print "<a href=\"edit_changes.php?action=rejectall&amp;gedfile=".$GEDCOMID."\" onclick=\"return confirm('".$gm_lang["reject_all_confirm"]."');\">".$gm_lang["reject_all"]."</a>\n";
	print "</td></tr></table>";
}
print "<br /><br />\n</center></div>\n";
print "<center><a href=\"#\" onclick=\"window.opener.reload(); window.close();\">".$gm_lang["close_window"]."</a><br /></center>\n";
print_simple_footer();
?>