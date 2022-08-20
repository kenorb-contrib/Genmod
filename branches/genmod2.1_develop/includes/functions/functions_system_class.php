<?php
/**
 * General system related functions
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
 * @package Genmod
 * @subpackage Tools
 *
 * $Id: functions_system_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
 
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

abstract class SystemFunctions {

	public function CheckLockout() {
		global $_SERVER, $_REQUEST, $gm_username;
		
		// Get the username to add to the log
		if (!isset($gm_username) || empty($gm_username)) {
			$gm_username = "";
			if (isset($_SESSION)) {
				if (!empty($_SESSION['gm_user'])) $gm_username = $_SESSION['gm_user'];
			}
			if (isset($HTTP_SESSION_VARS)) {
				if (!empty($HTTP_SESSION_VARS['gm_user'])) $gm_username = $HTTP_SESSION_VARS['gm_user'];
			}
		}
		
		$ip = $_SERVER["REMOTE_ADDR"];
		$sql = "SELECT * FROM ".TBLPREFIX."lockout WHERE lo_ip='".$ip."'";
		if (!empty($gm_username)) $sql .= " OR lo_username='".$gm_username."'";
		$res = NewQuery($sql);
		$staylocked = false;
		if ($res) {
			if ($res->NumRows()>0) {
				while($row = $res->FetchRow()){
					$ntime = time();
					if ($row[2] <= $ntime && $row[2] != '0') {
						$sql = "DELETE FROM ".TBLPREFIX."lockout WHERE lo_ip='".$row[0]."' AND lo_username='".$row[3]."'";
						$res2 = NewQuery($sql);
					}
					else {
						$staylocked = true;
					}
				}
				if ($staylocked) {
					header("HTTP/1.1 403 Forbidden");
					exit;
				}
			}
		}
	}
	
	public function HandleIntrusion($text="") {
		global $_SERVER, $_REQUEST, $gm_username;
		
		// Get the username to add to the log
		if (!isset($gm_username) || empty($gm_username)) {
			$gm_username = "";
			if (isset($_SESSION)) {
				if (!empty($_SESSION['gm_user'])) $gm_username = $_SESSION['gm_user'];
			}
			if (isset($HTTP_SESSION_VARS)) {
				if (!empty($HTTP_SESSION_VARS['gm_user'])) $gm_username = $HTTP_SESSION_VARS['gm_user'];
			}
		}
		
		$ip = $_SERVER["REMOTE_ADDR"];
		
		// Make the logstring
		$str = "HandleIntrusion-&gt; Intrusion detected for ".$_SERVER["SCRIPT_NAME"]."<br />Query string:<br />";
		foreach ($_REQUEST as $key => $value) {
			$str.= $key."&nbsp;=&nbsp;".$value."<br />";
		}
		if (SystemConfig::$LOCKOUT_TIME == "-1") $str .= "IP not locked out.";
		else {
			if (SystemConfig::$LOCKOUT_TIME == "0") {
				$str .= "IP locked out forever.";
				$sql = "INSERT INTO ".TBLPREFIX."lockout VALUES ('".$ip."' , '".time()."', '0', '".$gm_username."') ON DUPLICATE KEY UPDATE lo_timestamp='".time()."', lo_release='0'";
			}
			else {
				$str .= "IP locked out for ".SystemConfig::$LOCKOUT_TIME." minutes.";
				$newtime = time() + 60*SystemConfig::$LOCKOUT_TIME;
				$sql = "INSERT INTO ".TBLPREFIX."lockout VALUES ('".$ip."', '".time()."', '".$newtime."', '".$gm_username."') ON DUPLICATE KEY UPDATE lo_timestamp='".time()."', lo_release='".$newtime."'";
			}
			$res = NewQuery($sql);
			@session_destroy();
		}
		WriteToLog($str, "W", "S");
		
		header("HTTP/1.1 403 Forbidden");
		if (!empty($text)) print $text;
		exit;
	}
	
	// This function checks if the IP from which the user authenticated, is still the IP where the request comes from.
	// It also checks if the current IP is in the exclude list
	public function CheckSessionIP() {
		
		if (isset($_SESSION['gm_user']) && !empty($_SESSION['gm_user'])) {
			if (isset($_SESSION['IP']) && !empty($_SESSION['IP'])) {
				if ($_SESSION['IP'] != $_SERVER['REMOTE_ADDR']) {
					$excluded = self::IPInRange($_SERVER["REMOTE_ADDR"], SystemConfig::$EXCLUDE_HOSTS);
					if (!$excluded) {
						$string = "CheckSessionIP-&gt; Intrusion detected on session for IP ".$_SESSION['IP']." by ".$_SERVER['REMOTE_ADDR'];
						WriteToLog($string, "W", "S");
						self::HandleIntrusion($string);
						exit;
					}
				}
			}
		}
	}

	public function IPInRange($ip, $range) {

		$excluded = false;
		$lines = preg_split("/[;,]/", $range);
		$hostname = gethostbyaddr($ip);
		foreach ($lines as $key => $line) {
			$line = trim($line);
//					print $line."<br />";
			if (!empty($line)) {
				// is it a hostname?
				if (preg_match("/[a-zA_Z]/", $line, $match)) {
					$host = strtolower(preg_replace("/\*/", ".*", $line));
					if (preg_match("/($host)/", strtolower($hostname), $match)) {
						$excluded = true;
						break;
					}
				}
				// Is it a single IP?
				if (preg_match("/^([0-9]{1,3}\.){3}[0-9]{1,3}$/", $line, $match)) {
					if ($line == $ip) {
						$excluded = true;
						break;
					}
				}
				// Is it an IP-range?
				if (preg_match("/^([0-9]{1,3}\.){3}([0-9]{1,3}\-)([0-9]{1,3}\.){3}[0-9]{1,3}$/", $line, $match)) {
					$ips = explode("-", $line);
					if (ip2long($ip) <= ip2long($ips[1]) && ip2long($ip) >= ip2long($ips[0])) {
						$excluded = true;
						break;
					}
				}
			}
		}
		return $excluded;
	}	
	/**
	 * check if the maximum number of page views per hour for a session has been exeeded.
	 */
	public function CheckPageViews() {
		
		if (SystemConfig::$MAX_VIEW_TIME == 0) return;

		if (in_array(basename($_SERVER["SCRIPT_NAME"]), array("useradmin.php", "showblob.php")) || substr(basename($_SERVER["SCRIPT_NAME"]), 0, 4) == "edit") return;
		
		if ((!isset($_SESSION["pageviews"])) || (time() - $_SESSION["pageviews"]["time"] > SystemConfig::$MAX_VIEW_TIME)) {
			if (isset($_SESSION["pageviews"]) && SystemConfig::$MAX_VIEW_LOGLEVEL == "2") {
				$str = "Max pageview counter reset: max reached was ".$_SESSION["pageviews"]["number"];
				WriteToLog("CheckPageViews-&gt; ".$str, "I", "S");
			}
			$_SESSION["pageviews"]["time"] = time();
			$_SESSION["pageviews"]["number"] = 0;
			$_SESSION["pageviews"]["hadmsg"] = false;
		}
		
		$_SESSION["pageviews"]["number"]++;
		
		if ($_SESSION["pageviews"]["number"] > SystemConfig::$MAX_VIEWS) {
			$time = time() - $_SESSION["pageviews"]["time"];
			print GM_LANG_maxviews_exceeded;
			if ((SystemConfig::$MAX_VIEW_LOGLEVEL == "2" || SystemConfig::$MAX_VIEW_LOGLEVEL == "1") && $_SESSION["pageviews"]["hadmsg"] == false) {
				$str = "CheckPageViews-&gt; Maximum number of pageviews exceeded after ".$time." seconds.";
				WriteToLog($str, "W", "S");
				$_SESSION["pageviews"]["hadmsg"] = true;
			}
			exit;
		}
		return;
	}	
	
			
}
?>