<?php
/**
 * View logfiles 
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
 * @subpackage Admin
 * @version $Id$
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

// Check for logtype
if (!isset($cat)) exit;
if (!isset($max)) $max=20;
if (!isset($type)) $type = "";
if($type == "All") $type = "";
if (!isset($gedid)) $gedid = $GEDCOMID;
if (!isset($cleanup)) $cleanup = "";
if (!isset($action)) $action = "";
if ($action != "download") print_simple_header("Print logfile");

//-- make sure that they have admin status before they can use this page
$auth = false;
if (($cat == "S") && ($gm_user->userIsAdmin())) $auth = true;
if ((($cat == "G") || ($cat == "F"))  && ($gm_user->userGedcomAdmin($gedid))) $auth = true;

if ($auth) {
	if (!empty($action)) {
		if ($action == "download") {
			$loglines = ReadLog($cat, "0", "", $gedid);
			if (count($loglines) > 1) {
				if ($cat == "S") $expname = "syslog.csv";
				if ($cat == "G") $expname = "gedlog.csv";
				if ($cat == "F") $expname = "searchlog.csv";
				header("Content-Type: text/plain; charset=$CHARACTER_SET");
				header("Content-Disposition: attachment; filename=$expname");
				print '"Type","Date/time","User",';
				if ($cat != "S") print '"Gedcom",';
				print '"Message"';
				print "\r\n";
				foreach($loglines as $key => $logline) {
					print '"'.$logline["type"].'","'.date("d.m.Y H:i:s", $logline["time"]).'","'.$logline["user"].'","';
					if ($cat != "S") print $logline["gedcomid"].'","';
					$text = preg_replace(array("~[\r\n]~", "~<br />~"), array("", "; "), $logline["text"]);
					print $text.'"';
					print "\r\n";
				}
				exit;
			}
		}
		if (!empty($cleanup)) {
			// -- Calculate the timestamp for the earliest message to be deleted
			$str = "-".substr($cleanup, 0, -1)." month";
			$timestamp = strtotime($str);
			print $gm_lang["cleanup_older"]."&nbsp;".date("d.m.Y H:i:s", $timestamp)."<br />";
			$sql = 	"DELETE FROM ".TBLPREFIX."log WHERE (l_category='".$cat."' AND l_timestamp<'".$timestamp."'";
			if ($cat != "S") $sql .= " AND l_file='".$gedid."'";
			$sql .= ")";
			$res = NewQuery($sql);
			if ($res) {
				print $gm_lang["cleanup_success"];
				if ($cat == "F") {
					$g = array();
					$g[] = $gedid;
				}
				else $g = $gedid;
				WriteToLog("ViewLog-> Cleanup up logfile older than ".date("d.m.Y H:i:s", $timestamp), "I", $cat, $g);
			}
			else print $gm_lang["cleanup_failed"];
		}
	}
	// Set the notifications to off
	HaveReadNewLogrecs($cat, $gedid);
	
	// Retrieve number of loglines
	$logcount = ReadLog($cat, $max, $type, $gedid, false, true);
	
	// Start form
	print "<form action=\"viewlog.php\" method=\"get\">";
	
	// -- Print the top line
	print "<div class=\"topbottombar center\">";
	print $gm_lang["logfile_content"]." - ";
	if ($cat == "S") print $gm_lang["syslog"];
	else print $GEDCOMS[$gedid]["title"];
	print " - ".$gm_lang["recs_present"]." ".$logcount;
	print "</div>";

	// -- Print the buttons bar
	print"<div id=\"toplinks\" name=\"toplinks\" class=\"center\"><input  type=\"button\" value=\"".$gm_lang["back"]."\" onclick='self.close();' />&nbsp;<input  type=\"button\" value=\"".$gm_lang["refresh"]."\" onclick=\"document.location='viewlog.php?type=$type&amp;cat=$cat&amp;max=$max&amp;gedid=$gedid'; \" /></div>";
	print "<hr />";
	// -- Print the options title
	print "<div id=\"viewlog_option\">".$gm_lang["select_an_option"]."<br />";
		print "<hr />";
		// -- Print the options line
		print "<label for=\"viewlogoption\">".$gm_lang["show_last"]."</label>";
		print "<select id=\"viewlogoption\" name=\"viewlogoption\" onchange=\"document.location=options[selectedIndex].value;\">";
		print "<option value=\"viewlog.php?cat=".$cat."&amp;type=".$type."&amp;max=20&amp;gedid=$gedid&amp;cat=$cat\"";
		if ($max == "20") print " selected=\"selected\"";
		print " >"."20"."</option>";
		print "<option value=\"viewlog.php?cat=".$cat."&amp;type=".$type."&amp;max=50&amp;gedid=$gedid&amp;cat=$cat\"";
		if ($max == "50") print " selected=\"selected\"";
		print " >"."50"."</option>";
		print "<option value=\"viewlog.php?cat=".$cat."&amp;type=".$type."&amp;max=100&amp;gedid=$gedid&amp;cat=$cat\"";
		if ($max == "100") print " selected=\"selected\"";
		print " >"."100"."</option>";
		print "<option value=\"viewlog.php?cat=".$cat."&amp;type=".$type."&amp;max=0&amp;gedid=$gedid&amp;cat=$cat\"";
		if ($max == "0") print " selected=\"selected\"";
		print " >".$gm_lang["all"]."</option>";
		print "</select>";
		print "<br />";
		
		print "<label for=\"type\">".$gm_lang["show_events"]."</label>";
		print "<input type=\"radio\" name=\"type\" value=\"All\" onclick=\"document.location='viewlog.php?cat=$cat&amp;max=$max&amp;gedid=$gedid'\"";
		if ($type == "") print " checked=\"checked\"";
		print " />".$gm_lang["all"]."&nbsp;";
		print "<input type=\"radio\" name=\"type\" value=\"I\" onclick=\"document.location='viewlog.php?type=I&amp;cat=$cat&amp;max=$max&amp;gedid=$gedid'\"";
		if ($type == "I") print " checked=\"checked\"";
		print " /><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["log"]["information"]."\" alt=\"".$gm_lang["information"]."\" />&nbsp;";
		print "<input type=\"radio\" name=\"type\" value=\"W\" onclick=\"document.location='viewlog.php?type=W&amp;cat=$cat&amp;max=$max&amp;gedid=$gedid'\"";
		if ($type == "W") print " checked=\"checked\"";
		print " /><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["log"]["warning"]."\" alt=\"".$gm_lang["warning"]."\" />&nbsp;";
		print "<input type=\"radio\" name=\"type\" value=\"E\" onclick=\"document.location='viewlog.php?type=E&amp;cat=$cat&amp;max=$max&amp;gedid=$gedid'\"";
		if ($type == "E") print " checked=\"checked\"";
		print " /><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["log"]["error"]."\" alt=\"".$gm_lang["error"]."\" />&nbsp;";
	print "</div>";
	
	print "<div id=\"viewlog_admin\">";
	// -- Print the administration title
	print $gm_lang["administration"]."<br />";
	print "<hr />";

	// -- Print the administration options line
	// -- Calculate the number of months that can be deleted
	$loglines = ReadLog($cat, "1", $type, $gedid, true);
	// if no loglines are present, do not display the cleanup option
	if (count($loglines) > 0) {
		$logline = $loglines[0];
		$months = date("Y", mktime()) - date("Y", $logline["time"]);
		$months = 12 * $months;
		$months = $months + date("m", mktime()) - date("m", $logline["time"]);
		if (date("d", mktime()) < date("d", $logline["time"])) $months--;
		else if (date("d", mktime()) == date("d", $logline["time"])) {
			$mins = 60 * date("H", $logline["time"]) + date("i", $logline["time"]);
			$minsnow = 60 * date("H", mktime()) + date("i", mktime());
			if ($minsnow < $mins) $months--;
		}
		
		print "<label for=\"cleanup\">".$gm_lang["cleanup_older"]."</label>";
		print "<select id=\"cleanup\" name=\"cleanup\">";
		for ($i=0; $i<=$months; $i++) {
			print "<option value=\"".$i."M\"";
			if ($i == $months) print "selected=\"selected\" ";
			print ">".$i."&nbsp;".$gm_lang["months"]."</option>";
		}
		print "</select>";
		print "<input type=\"hidden\" name=\"cat\" value=\"$cat\" />";
		print "<input type=\"hidden\" name=\"max\" value=\"$max\" />";
		print "<input type=\"hidden\" name=\"type\" value=\"$type\" />";
		print "<input type=\"hidden\" name=\"gedid\" value=\"$gedid\" />";
		print "<input  type=\"submit\" name=\"action\" value=\"".$gm_lang["cleanup"]."\" />";
	}
	print "<br />";
	print "<label for=\"cleanup\">".$gm_lang["export"]."</label>";
	print "<input  type=\"button\" value=\"".$gm_lang["export_log"]."\" onclick=\"document.location='viewlog.php?type=$type&amp;cat=$cat&amp;max=$max&amp;gedid=$gedid&amp;action=download'; \" /></td></tr>";
	
	print "</div>";
	print "<br clear=\"all\" />";
	
	// Perform the query
	$loglines = ReadLog($cat, $max, $type, $gedid);
	
	// Print the loglines
//	print "<div id=\"logdetails\">";
	print "<table width=\"100%\">";
	// -- Print the title bar and content
	if (($cat == "S") || ($cat == "G")) {
		print "<tr class=\"admin_item_box shade2\"><td>".$gm_lang["type"]."</td><td>".$gm_lang["date_time"]."</td><td>".$gm_lang["ip_address"]."</td><td>".$gm_lang["user"]."</td><td>".$gm_lang["message"]."</td></tr>";
		foreach ($loglines as $key => $logline) {
			print "<tr class=\"admin_item_box shade1\">";
			print "<td>";
			if ($logline["type"] == "I") print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["log"]["information"]."\" alt=\"".$gm_lang["information"]."\" />";
			if ($logline["type"] == "W") print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["log"]["warning"]."\" alt=\"".$gm_lang["warning"]."\" />";
			if ($logline["type"] == "E") print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["log"]["error"]."\" alt=\"".$gm_lang["error"]."\" />";
			print "</td>";
			print "<td>".date("d.m.Y H:i:s", $logline["time"])."</td>";
			print "<td>".$logline["ip"]."</td>";
			print "<td>";
			if (!empty($logline["user"])) print $logline["user"]."</td>";
			else print "&nbsp;</td>";
			print "<td class=\"wrap\">".$logline["text"]."</td>";
			print "</tr>";
		}
		print "<tr>";
	}

	if ($cat == "F") {
		print "<tr class=\"admin_item_box shade2\"><td>".$gm_lang["type"]."</td><td>".$gm_lang["date_time"]."</td><td>".$gm_lang["ip_address"]."</td><td>".$gm_lang["user"]."</td><td>".$gm_lang["searchtype"]."</td><td>".$gm_lang["query"]."</td></tr>";
		foreach ($loglines as $key => $logline) {
			print "<tr class=\"admin_item_box shade1\">";
			print "<td>";
			if ($logline["type"] == "I") print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["log"]["information"]."\" alt=\"".$gm_lang["information"]."\" />";
			if ($logline["type"] == "W") print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["log"]["warning"]."\" alt=\"".$gm_lang["warning"]."\" />";
			if ($logline["type"] == "E") print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["log"]["error"]."\" alt=\"".$gm_lang["error"]."\" />";
			print "</td>";
			print "<td>".date("d.m.Y H:i:s", $logline["time"])."</td>";
			print "<td>".$logline["ip"]."</td>";
			print "<td>";
			if (!empty($logline["user"])) print $logline["user"]."</td>";
			else print "&nbsp;</td>";
			$msg = preg_split("/,/", $logline["text"]);
			if (empty($msg[0])) $msg[0] = "&nbsp;";
			if (!isset($msg[1])) $msg[1] = "&nbsp;";
			print "<td class=\"wrap\">".stripslashes($msg[0])."</td>";
			print "<td class=\"wrap\">".stripslashes($msg[1])."</td>";
			print "</tr>";
		}
		print "<tr>";
	}
	print "</table>";
//	print "</div>";
	
	// NOTE: Close form
	print "</form>";
	print "<br /><br />";
}
else {
	print "Not authorized!<br /><br />";
	print "<input type=\"submit\" value=\"".$gm_lang["back"]."\" onclick='self.close();' /><br /><br />";
}

print_simple_footer();
?>
