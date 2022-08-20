<?php
/**
 * View logfiles 
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
 * @subpackage Admin
 * @version $Id: viewlog.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

// Check for logtype
if (!isset($cat)) exit;
if (!isset($_GET["max"])) $max=20;
else $max = $_GET["max"];
if (!isset($type)) $type = "";
if($type == "All") $type = "";
if (!isset($gedid)) $gedid = GedcomConfig::$GEDCOMID;
if (!isset($cleanup)) $cleanup = "";
if (!isset($action)) $action = "";
if ($action != "download") PrintSimpleHeader("Print logfile");

//-- make sure that they have admin status before they can use this page
$auth = false;
if (($cat == "S") && ($gm_user->userIsAdmin())) $auth = true;
if ((($cat == "G") || ($cat == "F"))  && ($gm_user->userGedcomAdmin($gedid))) $auth = true;

if ($auth) {
	if (!empty($action)) {
		if ($action == "download") {
			$loglines = AdminFunctions::ReadLog($cat, "0", "", $gedid);
			if (count($loglines) > 1) {
				if ($cat == "S") $expname = "syslog.csv";
				if ($cat == "G") $expname = "gedlog.csv";
				if ($cat == "F") $expname = "searchlog.csv";
				header("Content-Type: text/plain; charset=".GedcomConfig::$CHARACTER_SET);
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
			print GM_LANG_cleanup_older."&nbsp;".date("d.m.Y H:i:s", $timestamp)."<br />";
			$sql = 	"DELETE FROM ".TBLPREFIX."log WHERE (l_category='".$cat."' AND l_timestamp<'".$timestamp."'";
			if ($cat != "S") $sql .= " AND l_file='".$gedid."'";
			$sql .= ")";
			$res = NewQuery($sql);
			if ($res) {
				print GM_LANG_cleanup_success;
				if ($cat == "F") {
					$g = array();
					$g[] = $gedid;
				}
				else $g = $gedid;
				WriteToLog("ViewLog-&gt; Cleanup up logfile older than ".date("d.m.Y H:i:s", $timestamp), "I", $cat, $g);
			}
			else print GM_LANG_cleanup_failed;
		}
	}
	// Set the notifications to off
	AdminFunctions::HaveReadNewLogrecs($cat, $gedid);
	
	// Retrieve number of loglines
	$logcount = AdminFunctions::ReadLog($cat, $max, $type, $gedid, false, true);
	
	// Start form
	print "<form action=\"viewlog.php\" method=\"get\">\n";
	
	// -- Print the top line
	print "<table class=\"NavBlockTable AdminNavBlockTable\">";
	print "<tr><td colspan=\"4\" class=\"NavBlockHeader\">";
	print GM_LANG_logfile_content." - ";
	if ($cat == "S") print GM_LANG_syslog;
	else print $GEDCOMS[$gedid]["title"];
	print " - ".GM_LANG_recs_present." ".$logcount;
	print "</td></tr>\n";

	// -- Print the options title
	print "<tr><td colspan=\"2\" class=\"NavBlockColumnHeader\">".GM_LANG_select_an_option."</td>\n";
	// -- Print the administration title
	print "<td colspan=\"2\" class=\"NavBlockColumnHeader\">".GM_LANG_administration."</td></tr>";
		// -- Print the options line
		print "<tr><td class=\"NavBlockLabel\">".GM_LANG_show_last."</td>\n";
		print "<td class=\"NavBlockField\"><select id=\"viewlogoption\" name=\"viewlogoption\" onchange=\"document.location=options[selectedIndex].value;\">";
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
		print " >".GM_LANG_all."</option>";
		print "</select>\n";
		print "</td>";
		// -- Calculate the number of months that can be deleted
		$loglines = AdminFunctions::ReadLog($cat, "1", $type, $gedid, true);
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
			
			print "<td class=\"NavBlockLabel\">".GM_LANG_cleanup_older."</td>";
			print "<td class=\"NavBlockField\"><select name=\"cleanup\">\n";
			for ($i=0; $i<=$months; $i++) {
				print "<option value=\"".$i."M\"";
				if ($i == $months) print " selected=\"selected\"";
				print ">".$i."&nbsp;".GM_LANG_months."</option>\n";
			}
			print "</select>\n";
			// -- Print the administration options line
			print "<input type=\"hidden\" name=\"cat\" value=\"$cat\" />";
			print "<input type=\"hidden\" name=\"max\" value=\"$max\" />";
			print "<input type=\"hidden\" name=\"type\" value=\"$type\" />";
			print "<input type=\"hidden\" name=\"gedid\" value=\"$gedid\" />";
			print "<input type=\"submit\" id=\"cleanup\" name=\"action\" value=\"".GM_LANG_cleanup."\" />";
			print "</td></tr>";
		}
		else print "<td class=\"NavBlockLabel\" colspan=\"2\">&nbsp;</td></tr>";
		print "<tr><td class=\"NavBlockLabel\">".GM_LANG_show_events."</td>";
		print "<td class=\"NavBlockField\"><input type=\"radio\" name=\"type\" id=\"type\" value=\"All\" onclick=\"document.location='viewlog.php?cat=$cat&amp;max=$max&amp;gedid=$gedid'\"";
		if ($type == "") print " checked=\"checked\"";
		print " />".GM_LANG_all."&nbsp;";
		print "<input type=\"radio\" name=\"type\" value=\"I\" onclick=\"document.location='viewlog.php?type=I&amp;cat=$cat&amp;max=$max&amp;gedid=$gedid'\"";
		if ($type == "I") print " checked=\"checked\"";
		print " /><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["log"]["information"]."\" alt=\"".GM_LANG_information."\" title=\"".GM_LANG_information."\" />&nbsp;";
		print "<input type=\"radio\" name=\"type\" value=\"W\" onclick=\"document.location='viewlog.php?type=W&amp;cat=$cat&amp;max=$max&amp;gedid=$gedid'\"";
		if ($type == "W") print " checked=\"checked\"";
		print " /><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["log"]["warning"]."\" alt=\"".GM_LANG_warning."\" title=\"".GM_LANG_warning."\" />&nbsp;";
		print "<input type=\"radio\" name=\"type\" value=\"E\" onclick=\"document.location='viewlog.php?type=E&amp;cat=$cat&amp;max=$max&amp;gedid=$gedid'\"";
		if ($type == "E") print " checked=\"checked\"";
		print " /><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["log"]["error"]."\" alt=\"".GM_LANG_error."\" title=\"".GM_LANG_error."\" />&nbsp;";
		print "</td>\n";

		print "<td class=\"NavBlockLabel\">".GM_LANG_export."</td>";
		print "<td class=\"NavBlockField\"><input type=\"button\" id=\"export\" value=\"".GM_LANG_export_log."\" onclick=\"document.location='viewlog.php?type=$type&amp;cat=$cat&amp;max=$max&amp;gedid=$gedid&amp;action=download'; \" /></td>";
		
		print "</tr>";
		// -- Print the buttons bar
		print"<tr><td colspan=\"4\" id=\"toplinks\" class=\"NavBlockFooter\"><input  type=\"button\" value=\"".GM_LANG_back."\" onclick='self.close();' />&nbsp;<input  type=\"button\" value=\"".GM_LANG_refresh."\" onclick=\"document.location='viewlog.php?type=$type&amp;cat=$cat&amp;max=$max&amp;gedid=$gedid'; \" /></td></tr>\n";
		print "</table>";
	
	// Perform the query
	$loglines = AdminFunctions::ReadLog($cat, $max, $type, $gedid);
	
	// Print the loglines
	print "<table class=\"NavBlockTable AdminNavBlockTable\">";
	print "<tr><td colspan=\"5\" class=\"NavBlockRowSpacer\">&nbsp;</td></tr>";
	// -- Print the title bar and content
	if (($cat == "S") || ($cat == "G")) {
		print "<tr><td class=\"NavBlockColumnHeader\">".GM_LANG_type."</td><td class=\"NavBlockColumnHeader\">".GM_LANG_date_time."</td><td class=\"NavBlockColumnHeader\">".GM_LANG_ip_address."</td><td class=\"NavBlockColumnHeader\">".GM_LANG_user."</td><td class=\"NavBlockColumnHeader\">".GM_LANG_message."</td></tr>";
		foreach ($loglines as $key => $logline) {
			print "<tr>\n";
			print "<td class=\"NavBlockLabel AdminNavBlockLabel\">";
			if ($logline["type"] == "I") print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["log"]["information"]."\" alt=\"".GM_LANG_information."\" title=\"".GM_LANG_information."\" />";
			if ($logline["type"] == "W") print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["log"]["warning"]."\" alt=\"".GM_LANG_warning."\" title=\"".GM_LANG_warning."\" />";
			if ($logline["type"] == "E") print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["log"]["error"]."\" alt=\"".GM_LANG_error."\" title=\"".GM_LANG_error."\" />";
			print "</td>\n";
			print "<td class=\"NavBlockLabel AdminNavBlockLabel\">".date("d.m.Y H:i:s", $logline["time"])."</td>\n";
			print "<td class=\"NavBlockLabel AdminNavBlockLabel\">".$logline["ip"]."</td>\n";
			print "<td class=\"NavBlockLabel AdminNavBlockLabel\">";
			if (!empty($logline["user"])) print $logline["user"]."</td>\n";
			else print "&nbsp;</td>\n";
			print "<td class=\"NavBlockLabel AdminNavBlockLabel\">".$logline["text"]."</td>\n";
			print "</tr>\n";
		}
	}

	if ($cat == "F") {
		print "<tr><td class=\"NavBlockColumnHeader\">".GM_LANG_type."</td><td class=\"NavBlockColumnHeader\">".GM_LANG_date_time."</td><td class=\"NavBlockColumnHeader\">".GM_LANG_ip_address."</td><td class=\"NavBlockColumnHeader\">".GM_LANG_user."</td><td class=\"NavBlockColumnHeader\">".GM_LANG_searchtype."</td><td class=\"NavBlockColumnHeader\">".GM_LANG_query."</td></tr>\n";
		foreach ($loglines as $key => $logline) {
			print "<tr>\n";
			print "<td class=\"NavBlockLabel AdminNavBlockLabel\">";
			if ($logline["type"] == "I") print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["log"]["information"]."\" alt=\"".GM_LANG_information."\" title=\"".GM_LANG_information."\" />";
			if ($logline["type"] == "W") print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["log"]["warning"]."\" alt=\"".GM_LANG_warning."\" title=\"".GM_LANG_warning."\" />";
			if ($logline["type"] == "E") print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["log"]["error"]."\" alt=\"".GM_LANG_error."\" title=\"".GM_LANG_error."\" />";
			print "</td>\n";
			print "<td class=\"NavBlockLabel AdminNavBlockLabel\">".date("d.m.Y H:i:s", $logline["time"])."</td>\n";
			print "<td class=\"NavBlockLabel AdminNavBlockLabel\">".$logline["ip"]."</td>\n";
			print "<td class=\"NavBlockLabel AdminNavBlockLabel\">";
			if (!empty($logline["user"])) print $logline["user"]."</td>\n";
			else print "&nbsp;</td>\n";
			$msg = preg_split("/,/", $logline["text"]);
			if (empty($msg[0])) $msg[0] = "&nbsp;";
			if (!isset($msg[1])) $msg[1] = "&nbsp;";
			print "<td class=\"NavBlockLabel AdminNavBlockLabel\">".stripslashes($msg[0])."</td>\n";
			print "<td class=\"NavBlockLabel AdminNavBlockLabel\">".stripslashes($msg[1])."</td>\n";
			print "</tr>\n";
		}
	}
	print "</table>";
	
	// NOTE: Close form
	print "</form>";
}
else {
	print "Not authorized!<br /><br />";
	print "<input type=\"submit\" value=\"".GM_LANG_back."\" onclick='self.close();' /><br /><br />";
}

PrintSimpleFooter();
?>
