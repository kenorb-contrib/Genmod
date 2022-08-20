<?php
/**
 * Interface to review/accept/reject changes made by editing online.
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
 * @subpackage Edit
 * @version $Id: edit_changes.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

if (!$gm_user->userCanAccept()) {
	if (LOGIN_URL == "") header("Location: login.php?url=edit_changes.php");
	else header("Location: ".LOGIN_URL."?url=edit_changes.php");
	exit;
}
// Force to show changes
$show_changes = true;

if (empty($action)) $action="";

PrintSimpleHeader(GM_LANG_review_changes);
?>
<script language="JavaScript" type="text/javascript">
<!--
	function show_gedcom_record(xref, type) {
		var recwin = window.open("gedrecord.php?changed=1&pid="+xref+"&type="+type , "", "top=50,left=50,width=600,height=400,scrollbars=1,scrollable=1,resizable=1");
	}
	function showchanges() {
	   window.location = '<?php print SCRIPT_NAME; ?>';
   }

	function show_diff(diffurl) {
		window.opener.location = diffurl;
		return false;
	}
//-->
</script>
<?php
print "<div class=\"NavBlockHeader EditHeader\">\n";
PrintHelpLink("accept_gedcom", "qm", "review_changes");
print GM_LANG_review_changes;

// NOTE: User wants to reject the change
if ($action=="reject") {
	if (ChangeFunctions::RejectChange($cid, $gedfile)) {
		print "<br /><br />";
		print GM_LANG_reject_successful;
	}
}
// NOTE: User rejects all changes
if ($action=="rejectall") {
	if (ChangeFunctions::RejectChange("", $gedfile, true)) {
		print "<br /><br />";
		print GM_LANG_reject_successful;
	}
}
// NOTE: User has accepted the change
if ($action=="accept") {
	if (ChangeFunctions::AcceptChange($cid, $gedfile)) {
		print "<br /><br />";
		print GM_LANG_accept_successful;
	}
}
// NOTE: User accepted all changes
if ($action=="acceptall") {
	if (ChangeFunctions::AcceptChange("", $gedfile, true)) {
		print "<br /><br />";
		print GM_LANG_accept_successful;
	}
}
print "</div>";

if (ChangeFunctions::GetChangeData(true, "", true)==0) {
	EditFunctions::PrintFailMessage(GM_LANG_no_changes);
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
	$sql = "SELECT DISTINCT ch_cid AS cid FROM ".TBLPREFIX."changes WHERE ch_file = '".GedcomConfig::$GEDCOMID."' ORDER BY ch_cid ASC, ch_fact ASC, ch_time DESC";
	$res = NewQuery($sql);
	while($row = $res->FetchAssoc()){
		$sqlcid = "SELECT * FROM ".TBLPREFIX."changes WHERE ch_cid = '".$row["cid"]."' AND ch_file = '".GedcomConfig::$GEDCOMID."' ORDER BY ch_id ASC";
		$rescid = NewQuery($sqlcid);
		$change_row = 0;
		while($rowcid = $rescid->FetchAssoc()){
			if ($trace) print "<br />1. ch_cid: ".$row["cid"]." ch_id: ".$rowcid["ch_id"]."<br />";
			// First we handle the level 0 changes.
			// ADD ID
			// If the ID is new, it is not dependent
			if (preg_match("/0 @.*@ /", $rowcid["ch_new"]) > 0 && empty($rowcid["ch_old"])) {
				if ($trace) print "2. Found add ".$rowcid["ch_gid"]." ";
				$found0ids[$rowcid["ch_gid"]] = $row["cid"];
				// But we check if it's really new: there must be no DB record AND no previous changes on this ID
				// If not, there is a problem and we can only reject.
				$object =& ConstructObject($rowcid["ch_gid"], $rowcid["ch_gid_type"]);
				if ($object->isnew && !in_array($rowcid["ch_gid"], $foundids)) {
					$rowcid["canaccept"] = true;
					$rowcid["canreject"] = true;
				}
				else {
					$rowcid["canaccept"] = false;
					$rowcid["canreject"] = true;
				}
				if ($trace) print "3. a: ".$rowcid["canaccept"]." r: ".$rowcid["canreject"]."<br />";
			}
			// DELETE ID
			// If it's a delete, we check if the DB gedcom rec is the same. If so, there are no dependencies
			// If not, we have previous changes, OR we have a problem.
			else if (preg_match("/0 @.*@ /", $rowcid["ch_old"]) > 0 && empty($rowcid["ch_new"])) {
				if ($trace) print "4. Found a deletion ".$rowcid["ch_gid"]." ";
				$found0ids[$rowcid["ch_gid"]] = $row["cid"];
				$object =& ConstructObject($rowcid["ch_gid"], $rowcid["ch_gid_type"]);
				if (trim($object->gedrec) != trim($rowcid["ch_old"])) {
					// Not the same, check if we really have previous changes on this ID
					if (in_array($rowcid["ch_gid"], $foundids)) {
						// Yes, found one
						$rowcid["canaccept"] = false;
						$rowcid["canreject"] = false;
					}
					else {
						// If the ch_old was constructed from the original gedrec AND factlevel changes, 
						// the CHAN subrecord is changed meanwhile and does not have the value mentioned in ch_old anymore
						// Therefore we compare the records again. If they match, it's ok.
//						$chanrec1 = GetSubrecord(1, "1 CHAN", $object->gedrec);
//						$chanrec2 = GetSubrecord(1, "1 CHAN", $rowcid["ch_old"]);
//						if (!empty($chanrec1)) $rec1 = preg_replace("/$chanrec1/", "", $object->gedrec);
//						else $rec1 = $object->gedrec;
//						if (!empty($chanrec2)) $rec2 = preg_replace("/$chanrec2/", "", $rowcid["ch_old"]);
//						else $rec2 = $rowcid["ch_old"];
//						if ($rec1 != $rec2) {
							// No, we have a problem and must reject
							$rowcid["canaccept"] = false;
							$rowcid["canreject"] = true;
//						}
//						else {
//							$rowcid["canaccept"] = true;
//							$rowcid["canreject"] = true;
//						}
					}
				}
				else {
					// ok, it's the first and correct change
					$rowcid["canaccept"] = true;
					$rowcid["canreject"] = true;
				}
				if ($trace) print "5. a: ".$rowcid["canaccept"]." r: ".$rowcid["canreject"]."<br />";
			}
			// EDIT ID
			// Here the record is edited. If based on the DB, we can accept it because there are no dependencies
			// If not, we either have previous changes OR a problem. Same code as DELETE
			else if (preg_match("/0 @.*@ /", $rowcid["ch_old"]) > 0 && preg_match("/0 @.*@ /", $rowcid["ch_new"]) > 0) {
				if ($trace) print "6. Found edit ".$rowcid["ch_gid"]."<br />";
				$found0ids[$rowcid["ch_gid"]] = $row["cid"];
				$object =& ConstructObject($rowcid["ch_gid"], $rowcid["ch_gid_type"]);
				// Oops we cannot compare the current gedrec with ch_old, because the latter is without CHAN tag even if the same
				if ($rowcid["ch_old"] != substr($object->gedrec, 0, strlen($rowcid["ch_old"]))) {
					//print "<br />".strlen($rowcid["ch_old"])."<br />".strlen($object->gedrec)."<br />";
					//print "<br />".$rowcid["ch_old"]."<br />".$object->gedrec."<br />";
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
				if ($trace) print "7. a: ".$rowcid["canaccept"]." r: ".$rowcid["canreject"]."<br />";
			}
			else {
				// FACTS
				// Now we check the fact level changes
				// If no previous changes on this ID, we check if the old value is in the DB
				if (!in_array($rowcid["ch_gid"], $foundids)) {
					if ($trace) print "8. Fact, no previous changes ".$rowcid["ch_gid"]."<br />";
					$object =& ReConstructObject($rowcid["ch_gid"], $rowcid["ch_gid_type"]);
					// Don't check when the old value is empty, Just see if the ID exists.
					if (empty($rowcid["ch_old"])) {
						if (!$object->isempty) {
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
						$oldsub = GetSubRecord(1, trim($rowcid["ch_old"]), $object->gedrec);
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
				if ($trace) print "9. a: ".$rowcid["canaccept"]." r: ".$rowcid["canreject"]."<br />";
				}
				else {
					if ($trace) print "10. Fact, previous changes ".$rowcid["ch_gid"]."<br />";
					// There are previous changes.
					// If the previous changes are level 0, we cannot be sure about what happens to this fact, so do nothing
					// One exeption: if the previous change is in the same group, it will be accepted together, so we can accept it.
					if ($trace) print "11. Check for ".$rowcid["ch_gid"]." in ";
					if ($trace) print_r($found0ids);
					if ($trace) print"<br />";
					if (array_key_exists($rowcid["ch_gid"], $found0ids)) {
						if ($trace) print "12. check same group";
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
											// If reorder, there may be two changes eachothers opposite
											// In that case, gid and groupid must be the same AND change type must be reorder_* for both
											if (!($groupid == $rowcid["ch_cid"] && substr($change["type"], 0, 8) == "reorder_" && $change["type"] == $rowcid["ch_type"])) {
												$foundearlier = true;
												break 2;
											}
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
								$object =& ConstructObject($rowcid["ch_gid"], $rowcid["ch_gid_type"]);
								$oldsub = GetSubRecord(1, trim($rowcid["ch_old"]), $object->gedrec);
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
					if ($trace) print "13. a: ".$rowcid["canaccept"]." r: ".$rowcid["canreject"]."<br />";
				}
			}
					
			$changegroup[$rowcid["ch_cid"]][$change_row]["gid"] = $rowcid["ch_gid"];
			$changegroup[$rowcid["ch_cid"]][$change_row]["gid_type"] = trim($rowcid["ch_gid_type"]);
			$changegroup[$rowcid["ch_cid"]][$change_row]["file"] = $rowcid["ch_file"];
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
	print "<table class=\"NavBlockTable EditTable\">";
	foreach ($changegroup as $groupid => $changes) {
		print "<tr><td class=\"NavBlockRowSpacer\">&nbsp;</td></tr>";
		print "<tr><td class=\"NavBlockColumnHeader EditChangesColumnHeader\" colspan=\"2\">".GM_LANG_change_type.": ";
		if (defined("GM_LANG_".$changegroup[$groupid][0]["type"])) print constant("GM_LANG_".$changegroup[$groupid][0]["type"]);
		else print $changegroup[$groupid][0]["type"];
		if (defined("GM_FACT_".$changegroup[$groupid][0]["fact"])) print ": ".constant("GM_FACT_".$changegroup[$groupid][0]["fact"]);
		print "</td><td class=\"NavBlockColumnHeader EditChangesColumnHeader\">";
		if ($changegroup[$groupid]["canaccept"]) print "<a href=\"edit_changes.php?action=accept&amp;cid=$groupid&amp;gedfile=".$changegroup[$groupid][0]["file"]."\">".GM_LANG_accept."</a>";
		if ($changegroup[$groupid]["canaccept"] && $changegroup[$groupid]["canreject"]) print " | ";
		if ($changegroup[$groupid]["canreject"]) print "<a href=\"edit_changes.php?action=reject&amp;cid=$groupid&amp;gedfile=".$changegroup[$groupid][0]["file"]."\">".GM_LANG_reject."</a>";
		print "</td></tr>";
		print "<tr><td class=\"NavBlockLabel EditChangesLabel\">".GM_LANG_name."</td><td class=\"NavBlockLabel EditChangesLabel\">".GM_LANG_username."</td><td class=\"NavBlockLabel EditChangesLabel\">".GM_LANG_date."</td></tr>";
		foreach ($changes as $key => $change) {
			// $change also contains the canaccept and canreject values. Only process if it's an array
			if (is_array($change)) {
				print "<tr><td class=\"NavBlockField\">";
				$object = ConstructObject($change["gid"], $change["gid_type"]);
				switch ($change["gid_type"]) {
					case "INDI":
						$printname = "<span class=\"ListItemName\">".$object->name."</span><span class=\"ListItemXref\">".$object->addxref."</span>";
						$changegids["individuals"][$change["gid"]] = $printname;
						break;
					case "FAM":
						$printname = "<span class=\"ListItemName\">".$object->name."</span><span class=\"ListItemXref\">".$object->addxref."</span>";
						$changegids["families"][$change["gid"]] = $printname;
						break;
					case "SOUR":
						$printname = "<span class=\"ListItemName\">".$object->name."</span><span class=\"ListItemXref\">".$object->addxref."</span>";
						$changegids["source"][$change["gid"]] = $printname;
						break;
					case "REPO":
						$printname = "<span class=\"ListItemName\">".$object->name."</span><span class=\"ListItemXref\">".$object->addxref."</span>";
						$changegids["repo"][$change["gid"]] = $printname;
						break;
					case "OBJE":
						$printname = "<span class=\"ListItemName\">".$object->title."</span><span class=\"ListItemXref\">".$object->addxref."</span>";
						$changegids["media"][$change["gid"]] = $printname;
						break;
					case "SUBM":
						$printname = "<span class=\"ListItemName\">".$object->name."</span><span class=\"ListItemXref\">".$object->addxref."</span>";
						$changegids["subm"][$change["gid"]] = $printname;
						break;
					case "NOTE":
						$printname = "<span class=\"ListItemName\">".$object->title."</span><span class=\"ListItemXref\">".$object->addxref."</span>";
						$changegids["note"][$change["gid"]] = $printname;
						break;
					case "HEAD":
						$printname = "<span class=\"ListItemName\">".$object->classname."</span><span class=\"ListItemXref\">".$object->addxref."</span>";
						$changegids["head"][$change["gid"]] = $printname;
						break;
					default:
						print "change type not found: ".$change["gid_type"];
						break;
				}
				print $printname;
				if ($trace) print "14. a".$changegroup[$groupid]["canaccept"]."r".$changegroup[$groupid]["canreject"];
				print "</td>";
				print "<td class=\"NavBlockField\">";
				$cuser =& User::GetInstance($change["user"]);
				if (!$cuser->is_empty) print PrintReady($cuser->firstname." ".$cuser->lastname);
				print "</td>";
				// NOTE: Use European time format if none is specified.
				if (empty($TIME_FORMAT)) $TIME_FORMAT = "H:m:s";
				print "<td class=\"NavBlockField\">".GetChangedDate(date("j M Y",$change["time"]))." ".date($TIME_FORMAT, $change["time"])."</td></tr>";
			
			}
		}
	}
	//-- accept and reject all
	print "<tr><td class=\"NavBlockRowSpacer\">&nbsp;</td></tr>";
	print "<tr><td class=\"NavBlockFooter EditHeader\" colspan=\"3\">";
	print "<div class=\"EditChangesLinkAccept\"><a href=\"edit_changes.php?action=acceptall&amp;gedfile=".GedcomConfig::$GEDCOMID."\" onclick=\"return confirm('".GM_LANG_accept_all_confirm."');\">".GM_LANG_accept_all."</a></div>\n";
	print "<div class=\"EditChangesLinkReject\"><a href=\"edit_changes.php?action=rejectall&amp;gedfile=".GedcomConfig::$GEDCOMID."\" onclick=\"return confirm('".GM_LANG_reject_all_confirm."');\">".GM_LANG_reject_all."</a></div>\n";
	print "</td></tr>";
	
	print "</table>";
	
	print "<table class=\"NavBlockTable EditChangesLinkTable\">\r\n";
	print "<tr><td class=\"NavBlockHeader\">";
	PrintHelpLink("view_gedcom_help", "qm", "view_gedcom");
	print GM_LANG_view_gedcom."</td></tr>";
	$rectypes = array("note"=>"NOTE", "subm"=>"SUBM", "media"=>"OBJE", "repo"=>"REPO", "source"=>"SOUR", "families"=>"FAM", "individuals"=>"INDI", "head"=>"HEAD");
	foreach ($changegids as $type => $gids) {
		$rectype = $rectypes[$type];
		print "<tr><td class=\"NavBlockColumnHeader\">".constant("GM_LANG_".$type)."</td></tr>";
		foreach ($gids as $gid => $name) {
			print "<tr><td class=\"NavBlockField\"><a href=\"javascript:show_gedcom_record('".$gid."','".$rectype."');\">".$name."</a></td></tr>";
		}
	}
	print "</table>";
}
print "<div class=\"CloseWindow\"><a href=\"#\" onclick=\"window.opener.location.reload(); window.close();\">".GM_LANG_close_window."</a></div>\n";
PrintSimpleFooter();
?>