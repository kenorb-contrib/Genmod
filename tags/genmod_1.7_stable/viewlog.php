<?php
/**
 * View logfiles 
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
 * @subpackage Admin
 * @version $Id: viewlog.php,v 1.16 2006/04/09 15:53:27 roland-d Exp $
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

/**
 * Inclusion of the language files
*/
/*
require $GM_BASE_DIRECTORY . $confighelpfile["english"];
if (file_exists($GM_BASE_DIRECTORY . $confighelpfile[$LANGUAGE])) require $GM_BASE_DIRECTORY . $confighelpfile[$LANGUAGE];
*/

// Check for logtype
if (!isset($cat)) exit;
if (!isset($max)) $max=20;
if (!isset($type)) $type = "";
if($type == "All") $type = "";
if (!isset($ged)) $ged = "";
if (!isset($cleanup)) $cleanup = "";
if (!isset($action)) $action = "";
if ($action != "download") print_simple_header("Print logfile");

//-- make sure that they have admin status before they can use this page
// $uname = $gm_username;
$auth = false;
if (($cat == "S") && (userIsAdmin($gm_username))) $auth = true;
if ((($cat == "G") || ($cat == "F"))  && (userGedcomAdmin($gm_username, $ged))) $auth = true;

if ($auth) {
	if (!empty($action)) {
		if ($action == "download") {
			$loglines = ReadLog($cat, "0", "", $ged);
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
					if ($cat != "S") print $logline["gedcom"].'","';
					$text = str_replace("<br />", "; ", $logline["text"]);
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
			$sql = 	"DELETE FROM ".$TBLPREFIX."log WHERE (l_category='".$cat."' AND l_timestamp<'".$timestamp."'";
			if ($cat != "S") $sql .= " AND l_gedcom='".$ged."'";
			$sql .= ")";
			$res = dbquery($sql);
			if ($res == "1") {
				print $gm_lang["cleanup_success"];
				$g = array();
				$g[] = $ged;
				WriteToLog(",Cleanup up logfile older than ".date("d.m.Y H:i:s", $timestamp), "I", $cat, $g);
			}
			else print $gm_lang["cleanup_failed"];
		}
	}
	// Perform the query
	$loglines = ReadLog($cat, $max, $type, $ged);
	$logcount = ReadLog($cat, $max, $type, $ged, false, true);
	// Start form
	print "<form action=\"viewlog.php\" method=\"get\">";
	
	// Print the loglines
	print "<table class=\"facts_table ".$TEXT_DIRECTION."\">";
	
	// -- Print the top line
	if ($cat == "F") print "<tr><td colspan=\"6\" class=\"topbottombar\">";
	else print "<tr><td colspan=\"5\" class=\"topbottombar\">";
	print $gm_lang["logfile_content"]." - ";
	if ($cat == "S") print $gm_lang["syslog"];
	else print $GEDCOMS[$ged]["title"];
	print " - ".$gm_lang["recs_present"]." ".$logcount;
	print "</td></tr>";

	// -- Print the options title
	if ($cat == "F") print "<tr><td colspan=\"6\" class=\"shade2 center\">";
	else print "<tr><td colspan=\"5\" class=\"shade2 center\">";
	print $gm_lang["select_an_option"]."</td></tr>";

	// -- Print the options line
	print "<tr><td colspan=\"2\" class=\"shade2\">".$gm_lang["show_last"]."</td>";
	print "<td class=\"shade1\" width10>";
	print "<select onchange=\"document.location=options[selectedIndex].value;\">";
	print "<option value=\"viewlog.php?cat=".$cat."&amp;type=".$type."&amp;max=20&amp;ged=$ged&amp;cat=$cat\"";
	if ($max == "20") print " selected=\"selected\"";
	print " >"."20"."</option>";
	print "<option value=\"viewlog.php?cat=".$cat."&amp;type=".$type."&amp;max=50&amp;ged=$ged&amp;cat=$cat\"";
	if ($max == "50") print " selected=\"selected\"";
	print " >"."50"."</option>";
	print "<option value=\"viewlog.php?cat=".$cat."&amp;type=".$type."&amp;max=100&amp;ged=$ged&amp;cat=$cat\"";
	if ($max == "100") print " selected=\"selected\"";
	print " >"."100"."</option>";
	print "<option value=\"viewlog.php?cat=".$cat."&amp;type=".$type."&amp;max=0&amp;ged=$ged&amp;cat=$cat\"";
	if ($max == "0") print " selected=\"selected\"";
	print " >".$gm_lang["all"]."</option>";
	print "</select></td>";
	print "<td class=\"shade2\">".$gm_lang["show_events"]."</td>";
	print "<td class=\"shade1 width10\"";
	if ($cat == "F") print " colspan=\"2\""; 
	print ">";
	print "<input type=\"radio\" name=\"type\" value=\"All\" onclick=\"document.location='viewlog.php?cat=$cat&amp;max=$max&amp;ged=$ged'\"";
	if ($type == "") print " checked=\"checked\"";
	print " />".$gm_lang["all"]."&nbsp;";
	print "<input type=\"radio\" name=\"type\" value=\"I\" onclick=\"document.location='viewlog.php?type=I&amp;cat=$cat&amp;max=$max&amp;ged=$ged'\"";
	if ($type == "I") print " checked=\"checked\"";
	print " /><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["log"]["information"]."\" alt=\"".$gm_lang["information"]."\" />&nbsp;";
	print "<input type=\"radio\" name=\"type\" value=\"W\" onclick=\"document.location='viewlog.php?type=W&amp;cat=$cat&amp;max=$max&amp;ged=$ged'\"";
	if ($type == "W") print " checked=\"checked\"";
	print " /><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["log"]["warning"]."\" alt=\"".$gm_lang["warning"]."\" />&nbsp;";
	print "<input type=\"radio\" name=\"type\" value=\"E\" onclick=\"document.location='viewlog.php?type=E&amp;cat=$cat&amp;max=$max&amp;ged=$ged'\"";
	if ($type == "E") print " checked=\"checked\"";
	print " /><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["log"]["error"]."\" alt=\"".$gm_lang["error"]."\" />&nbsp;";
	print "</td>";
	print "</tr>";
	
	// -- Print the buttons bar
	if ($cat == "F") print "<tr><td colspan=\"6\" class=\"topbottombar\">";
	else print "<tr><td colspan=\"5\" class=\"topbottombar\">";
	print"<input type=\"button\" value=\"".$gm_lang["back"]."\" onclick='self.close();' />&nbsp;<input type=\"button\" value=\"".$gm_lang["refresh"]."\" onclick=\"document.location='viewlog.php?type=$type&amp;cat=$cat&amp;max=$max&amp;ged=$ged'; \" /></td></tr>";

		// -- Print the title bar and content
	if (($cat == "S") || ($cat == "G")) {
		print "<tr><td class=\"list_label\">".$gm_lang["type"]."</td><td class=\"list_label\">".$gm_lang["date_time"]."</td><td class=\"list_label\">".$gm_lang["ip_address"]."</td><td class=\"list_label\">".$gm_lang["user"]."</td><td class=\"list_label\">".$gm_lang["message"]."</td></tr>";
		foreach ($loglines as $key => $logline) {
			print "<tr>";
			print "<td class=\"shade1\">";
			if ($logline["type"] == "I") print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["log"]["information"]."\" alt=\"".$gm_lang["information"]."\" />";
			if ($logline["type"] == "W") print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["log"]["warning"]."\" alt=\"".$gm_lang["warning"]."\" />";
			if ($logline["type"] == "E") print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["log"]["error"]."\" alt=\"".$gm_lang["error"]."\" />";
			print "</td>";
			print "<td class=\"shade1\">".date("d.m.Y H:i:s", $logline["time"])."</td>";
			print "<td class=\"shade1\">".$logline["ip"]."</td>";
			print "<td class=\"shade1\">";
			if (!empty($logline["user"])) print $logline["user"]."</td>";
			else print "&nbsp;</td>";
			print "<td class=\"shade1 wrap\">".$logline["text"]."</td>";
			print "</tr>";
		}
		print "<tr><td colspan=\"5\" class=\"topbottombar\">";
	}

	if ($cat == "F") {
		print "<tr><td class=\"list_label\">".$gm_lang["type"]."</td><td class=\"list_label\">".$gm_lang["date_time"]."</td><td class=\"list_label\">".$gm_lang["ip_address"]."</td><td class=\"list_label\">".$gm_lang["user"]."</td><td class=\"list_label\">".$gm_lang["searchtype"]."</td><td class=\"list_label\">".$gm_lang["query"]."</td></tr>";
		foreach ($loglines as $key => $logline) {
			print "<tr>";
			print "<td class=\"shade1\">";
			if ($logline["type"] == "I") print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["log"]["information"]."\" alt=\"".$gm_lang["information"]."\" />";
			if ($logline["type"] == "W") print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["log"]["warning"]."\" alt=\"".$gm_lang["warning"]."\" />";
			if ($logline["type"] == "E") print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["log"]["error"]."\" alt=\"".$gm_lang["error"]."\" />";
			print "</td>";
			print "<td class=\"shade1\">".date("d.m.Y H:i:s", $logline["time"])."</td>";
			print "<td class=\"shade1\">".$logline["ip"]."</td>";
			print "<td class=\"shade1\">";
			if (!empty($logline["user"])) print $logline["user"]."</td>";
			else print "&nbsp;</td>";
			$msg = preg_split("/,/", $logline["text"]);
			if (empty($msg[0])) $msg[0] = "&nbsp;";
			if (!isset($msg[1])) $msg[1] = "&nbsp;";
			print "<td class=\"shade1 wrap\">".$msg[0]."</td>";
			print "<td class=\"shade1 wrap\">".$msg[1]."</td>";
			print "</tr>";
		}
		print "<tr><td colspan=\"6\" class=\"topbottombar\">";
	}
	
	// -- Print the bottom button bar
	print"<input type=\"button\" value=\"".$gm_lang["back"]."\" onclick='self.close();' />&nbsp;<input type=\"button\" value=\"".$gm_lang["refresh"]."\" onclick=\"document.location='viewlog.php?type=$type&amp;cat=$cat&amp;max=$max&amp;ged=$ged'; \" /></td></tr>";
	
	// -- Print the administration title
	print "<tr><td>&nbsp;</td></tr>";
	if ($cat == "F") print "<tr><td colspan=\"6\" class=\"topbottombar center\">";
	else print "<tr><td colspan=\"5\" class=\"topbottombar center\">";
	print $gm_lang["administration"]."</td></tr>";

	// -- Print the administration options line
	// -- Calculate the number of months that can be deleted
	$loglines = ReadLog($cat, "1", $type, $ged, true);
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
		
		print "<tr><td colspan=\"2\" class=\"shade2\">".$gm_lang["cleanup_older"]."</td>";
		print "<td class=\"shade1 vmiddle\">";
		print "<select name=\"cleanup\">";
		for ($i=0; $i<=$months; $i++) {
			print "<option value=\"".$i."M\"";
			if ($i == $months) print "selected=\"selected\" ";
			print ">".$i."&nbsp;".$gm_lang["months"]."</option>";
		}
		print "</select>";
		print "</td>";
		print "<td class=\"shade2\">";
		print "<input type=\"hidden\" name=\"cat\" value=\"$cat\" />";
		print "<input type=\"hidden\" name=\"max\" value=\"$max\" />";
		print "<input type=\"hidden\" name=\"type\" value=\"$type\" />";
		print "<input type=\"hidden\" name=\"ged\" value=\"$ged\" />";
		print "<input type=\"submit\" name=\"action\" value=\"".$gm_lang["cleanup"]."\" />";
		print "</td>";
	}
	else print "<tr><td class=\"shade2\" colspan=\"3\">&nbsp;</td>";
	if ($cat == "F") print "<td class=\"shade2\" colspan=\"2\">";
	else print "<td class=\"shade2\">";
	print "<input type=\"button\" value=\"".$gm_lang["export_log"]."\" onclick=\"document.location='viewlog.php?type=$type&amp;cat=$cat&amp;max=$max&amp;ged=$ged&amp;action=download'; \" /></td></tr>";
	
	// -- Print the bottom bar
	if ($cat == "F") print "<tr><td colspan=\"6\" class=\"topbottombar\">&nbsp;</td></tr>";
	else print "<tr><td colspan=\"5\" class=\"topbottombar\">&nbsp;</td></tr>";
	
	print "</table>";
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
