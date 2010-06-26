<?php
/**
 * Functions for installing Genmod
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
 * $Id$
 * @package Genmod
 * @subpackage Installation
 */

function InstallCheckDBLayout() {
	global $DBHOST, $DBUSER, $DBPASS, $DBNAME, $TBLPREFIX, $setup_db, $link, $db_original, $deleterows, $db_layout, $server_charset, $server_collation;
	
	// NOTE: Get the database layout
	require("../includes/values/db_layout.php");
	ArrayCopy($db_original, $db_layout);
	
	// First check for the default character set and collation of the server
	$sql = "show variables like '%character_set_server%'";
	$res = mysql_query($sql);
	$row = mysql_fetch_row($res);
	$server_charset = $row[1];
	$sql = "show variables like '%collation_server%'";
	$res = mysql_query($sql);
	$row = mysql_fetch_row($res);
	$server_collation = $row[1];
	$sql = "SELECT @@SQL_QUOTE_SHOW_CREATE";
	$res = mysql_query($sql);
	$row = mysql_fetch_row($res);
	$sqsc = $row[0];
	if ($sqsc) {
		$sql = "SET SQL_QUOTE_SHOW_CREATE=0";
		$res = mysql_query($sql);
	}

	if (!mysql_query("SHOW CREATE DATABASE `".$DBNAME."`")) {
		// NOTE: Database does not exist. Try to create it.
		print "<img src=\"images/nok.png\" alt=\"Database does not exist\"/> ";
		print "Database ".$DBNAME." does not exist.";
		print "<br />";
		$sqlcreate = "CREATE DATABASE `".$DBNAME."`"." CHARACTER SET ".$server_charset." COLLATE ".$server_collation;
		$rescreate = mysql_query($sqlcreate);
		if (!$rescreate) {
			print "<img src=\"images/nok.png\" alt=\"Database cannot be created\"/> ";
			print "Cannot create database: " . mysql_error().".";
			print "<br />";
			return false;
		}
		else {
			mysql_select_db($DBNAME);
			print "<img src=\"images/ok.png\" alt=\"Database created\"/> ";
			print "Database ".$DBNAME." has been created.";
			print "<br />";
		}
	}
	else {
		// Check the default character set for the DB
		$sql = "SHOW CREATE DATABASE `".$DBNAME."`";
		$res = mysql_query($sql);
		$string = mysql_fetch_row($res);
		$ct = preg_match("/DEFAULT CHARACTER SET (.+)\*/", $string[1], $charset);
		if ($charset[1] != $server_charset) {
			$sql = "ALTER DATABASE ".$DBNAME." CHARACTER SET ".$server_charset." COLLATE ".$server_collation;
			$res = mysql_query($sql);
		}
	}

	// NOTE: Only take those tables who match the given table prefix
	$sqltables = "SHOW TABLES FROM `".$DBNAME."` LIKE '".preg_replace("/_/","\_", $TBLPREFIX)."%'";
	$result = mysql_query($sqltables);
	$deleterows = array();
	while ($row = mysql_fetch_row($result)) {
		// NOTE: Retrieve a list of fields of the current table
		$sql = "SHOW COLUMNS FROM ".$row[0];
		$res = mysql_query($sql);
		if (mysql_num_rows($res) > 0) {
			while ($fieldrow = mysql_fetch_assoc($res)) {
				// if any fields exist in db_layout for this table, check
				if (isset($db_layout[$row[0]]["row"])) {
					// NOTE: Check if the fieldname exists in the database layout
					if (array_key_exists($fieldrow["Field"], $db_layout[$row[0]]["row"])) {
						unset($db_layout[$row[0]]["row"][$fieldrow["Field"]]);
						if (count($db_layout[$row[0]]["row"]) == 0) unset($db_layout[$row[0]]["row"]);
					}
					else $deleterows[] = array($row[0], $fieldrow["Field"]);
				}
				// remaining fields to be deleted
				else $deleterows[] = array($row[0], $fieldrow["Field"]);
			}
		}
		// NOTE: Retrieve a list of keys of the current table
		$sql = "SHOW KEYS FROM ".$row[0];
		$res = mysql_query($sql);
		if (mysql_num_rows($res) > 0) {
			while ($fieldrow = mysql_fetch_assoc($res)) {
				if (isset($db_layout[$row[0]]["key"])) {
					// NOTE: Check if the fieldname exists in the database layout
					if (array_key_exists(strtolower($fieldrow["Key_name"]), $db_layout[$row[0]]["key"])) {
						unset($db_layout[$row[0]]["key"][strtolower($fieldrow["Key_name"])]);
						if (count($db_layout[$row[0]]["key"]) == 0) unset($db_layout[$row[0]]["key"]);
						// NOTE: Remove the array if it contains no entries
						if (count($db_layout[$row[0]]) == 0) unset($db_layout[$row[0]]);
					}
				}
			}
		}
	}
	mysql_free_result($result);
	if (count($db_layout) == 0 && count($deleterows) == 0) $setup_db = true;
	else $setup_db = false;
	if ($sqsc) {
		$sql = "SET SQL_QUOTE_SHOW_CREATE=1";
		$res = mysql_query($sql);
	}
	return $setup_db;
}

function InstallFixDBLayout() {
	global $db_layout, $deleterows, $db_original, $server_charset, $server_collation;
	
	foreach ($db_layout as $tablename => $fields) {
		// NOTE: Check if the table exists
		if (mysql_num_rows(mysql_query("SHOW TABLES LIKE '".$tablename."'")) == 1) {
			// NOTE: Table exists, only add the missing entries
			$sql = "ALTER TABLE `".$tablename."`";
						if (isset($fields["row"])) {
							foreach ($fields["row"] as $column => $field) {
								$field["details"] = preg_replace(array("/#charset#/", "/#collate#/"), array($server_charset, $server_collation), $field["details"]);
								$prev = " FIRST";
								foreach ($db_original[$tablename]["row"] as $tfield => $tdetail) {
									if ($tfield == $column) {
										break;
									}
									else $prev = " AFTER ".$tfield;
								}
								$sql .= " ADD `".$column."` ".$field["details"].$prev.", ";
							}
						}
			if (isset($fields["key"])) {
				foreach ($fields["key"] as $column => $field) {
					$sql .= " ADD ".$field.", ";
				}
			}
			$sql = trim($sql);
			$sql = substr($sql, 0, strlen($sql)-1);
//			$sql .= ")";
			if (!$res = mysql_query($sql)) {
				print "Query error:<br />".$sql; 
				print "<br />";
				print mysql_error()."<br />";
			}
			else print GM_LANG_missing_fields_keys_restored."<br />";
			
			// Delete obsolete rows
			foreach ($deleterows as $key => $row) {
				$sql = "ALTER TABLE `".$row[0]."` DROP `".$row[1]."`";
				$res = mysql_query($sql);
				if (!$res) {
					print "Query error:<br />".$sql; 
					print "<br />";
					print mysql_error()."<br />";
				}
				else print GM_LANG_obsolete_row_deleted." ".$row[1]."<br />";
			}
		}
		else {
			// NOTE: Table does not exist, so add the complete table
			InstallAddMissingTable(substr($tablename, strlen(trim($_POST["TBLPREFIX"]))));
		}
	}
}

function InstallAddMissingTable($tablename) {
	global $TBLPREFIX, $db_original, $server_charset, $server_collation;

	// NOTE: Set the response
	$ok = constant("GM_LANG_created_".$tablename);
	$nok = constant("GM_LANG_created_".$tablename."_fail");

	$sql = "CREATE TABLE `".$TBLPREFIX.$tablename."` (";
	// NOTE: Add the fields
	foreach ($db_original[$TBLPREFIX.$tablename]["row"] as $type => $field) {
		$field["details"] = preg_replace(array("/#charset#/", "/#collate#/"), array($server_charset, $server_collation), $field["details"]);
		$sql .= "`".$type."` ".$field["details"].", ";
	}
	
	// NOTE: Add the keys
	foreach ($db_original[$TBLPREFIX.$tablename]["key"] as $type => $field) {
		$sql .= $field.", ";
	}
	$sql = trim($sql);
	$sql = substr($sql, 0, strlen($sql)-1);
	$sql .= ") TYPE=MyISAM CHARACTER SET ".$server_charset." COLLATE ".$server_collation;

	// NOTE: Execute the query
	$res = mysql_query($sql);
	if($res) {
		print "<img src=\"images/ok.png\" alt=\"Table created\"/> ";
		print $ok."<br />\n";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Table not created\"/> ";
		print $nok."<br />".mysql_error()."\n";
	}
}

function InstallRestartButton() {
	global $DBHOST, $DBUSER, $DBPASS, $DBNAME, $TBLPREFIX;
	
	print "<form method=\"post\" name=\"next\" action=\"".$_SERVER["SCRIPT_NAME"]."\">\n";
	print "<input type=\"hidden\" name=\"step\" value=\"2\">\n";
	print "<input type=\"hidden\" name=\"DBHOST\" value=\"".$DBHOST."\">\n";
	print "<input type=\"hidden\" name=\"DBUSER\" value=\"".$DBUSER."\">\n";
	print "<input type=\"hidden\" name=\"DBPASS\" value=\"".$DBPASS."\">\n";
	print "<input type=\"hidden\" name=\"DBNAME\" value=\"".$DBNAME."\">\n";
	print "<input type=\"hidden\" name=\"TBLPREFIX\" value=\"".$TBLPREFIX."\">\n";
	print "<br />\n";
	print "<input type=\"submit\" value=\"Restart\">\n";
	print "</form>\n";
}

function InstallAddAdminUser($newuser, $msg = "added") {
	global $TBLPREFIX;

	$newuser["firstname"] = preg_replace("/\//", "", $newuser["firstname"]);
	$newuser["lastname"] = preg_replace("/\//", "", $newuser["lastname"]);
	$sql = "INSERT INTO ".$TBLPREFIX."users VALUES('".$newuser["username"]."','".$newuser["password"]."','".mysql_real_escape_string($newuser["firstname"])."','".mysql_real_escape_string($newuser["lastname"])."'";
	if ($newuser["canadmin"]) $sql .= ",'Y'";
	else $sql .= ",'N'";
	$sql .= ",'".mysql_real_escape_string($newuser["email"])."'";
	$sql .= ",'".mysql_real_escape_string($newuser["verified"])."'";
	$sql .= ",'".mysql_real_escape_string($newuser["verified_by_admin"])."'";
	$sql .= ",'".mysql_real_escape_string($newuser["language"])."'";
	$sql .= ",'".mysql_real_escape_string($newuser["reg_timestamp"])."'";
	$sql .= ",'".mysql_real_escape_string($newuser["reg_hashcode"])."'";
	$sql .= ",'".mysql_real_escape_string($newuser["theme"])."'";
	$sql .= ",'".mysql_real_escape_string($newuser["loggedin"])."'";
	$sql .= ",'".mysql_real_escape_string($newuser["sessiontime"])."'";
	$sql .= ",'".mysql_real_escape_string($newuser["contactmethod"])."'";
	if ($newuser["visibleonline"]) $sql .= ",'Y'";
	else $sql .= ",'N'";
	if ($newuser["editaccount"]) $sql .= ",'Y'";
	else $sql .= ",'N'";
	$sql .= ",'".mysql_real_escape_string($newuser["default_tab"])."'";
	$sql .= ",'".mysql_real_escape_string($newuser["comment"])."'";
	$sql .= ",'".mysql_real_escape_string($newuser["comment_exp"])."'";
	if (isset($newuser["sync_gedcom"])) $sql .= ",'".mysql_real_escape_string($newuser["sync_gedcom"])."'";
	else $sql .= ",'N'";
	if (isset($newuser["auto_accept"]) && $newuser["auto_accept"]==true) $sql .= ",'Y'";
	else $sql .= ",'N'";
	$sql .= ")";
	$res = mysql_query($sql);
	if ($res) return true;
	else print mysql_error()."<br />";
}

function InstallShowProgress() {
	global $step;
	print "<div style=\"border: 1px solid #FF0000; width: 700px;\">";
	print "<img src=\"images/progressbar.png\" width=\"".$step."00px\" height=\"10px\" alt=\"Progress\"/> ";
	print "</div>";
}

function InstallLoadLanguage() {
	
	// Load the language
	if (file_exists("install_lang.txt")) {
		$lines = file("install_lang.txt");
		foreach ($lines as $key => $line) {
			$data = preg_split("/\";\"/", $line, 2);
			$data[0] = substr(trim($data[0]), 1);
			$data[1] = substr(trim($data[1]), 0, -1);
			define("GM_LANG_".$data[0], $data[1]);
		}
	}
}

function InstallStoreLanguage($storelang) {
	global $TBLPREFIX, $language_settings;
	$output = array();
	$output["lang"] = true;
	$output["help"] = true;
	$output["facts"] = true;
	
	// NOTE: Store the chosen languages
	if (file_exists("../languages/lang.".$language_settings[$storelang]["lang_short_cut"].".txt")) {
		$lines = file("../languages/lang.".$language_settings[$storelang]["lang_short_cut"].".txt");
		foreach ($lines as $key => $line) {
			$data = preg_split("/\";\"/", $line, 2);
			if (!isset($data[1])) print "Problem with language string ".$line."<br />";
			else {
				$data[0] = substr(trim($data[0]), 1);
				$data[1] = substr(trim($data[1]), 0, -1);
				$sql = "UPDATE ".$TBLPREFIX."language SET `lg_".$storelang."` = '".mysql_real_escape_string($data[1])."', lg_last_update_date='".time()."', lg_last_update_by='install' WHERE lg_string = '".$data[0]."' LIMIT 1";
				if (!$result = mysql_query($sql)) {
					print mysql_error();
					print "<br />";
					$output["lang"] = false;
					print "Install => Could not add language string ".$line." for language ".$storelang." to table";
				}
				else set_time_limit(10);
			 }
	    }
	}
	if (file_exists("../languages/help_text.".$language_settings[$storelang]["lang_short_cut"].".txt")) {
		$lines = file("../languages/help_text.".$language_settings[$storelang]["lang_short_cut"].".txt");
		foreach ($lines as $key => $line) {
			$data = preg_split("/\";\"/", $line, 2);
			if (!isset($data[1])) print "Problem with help language string ".$line."<br />";
			else {
				$data[0] = substr(trim($data[0]), 1);
				$data[1] = substr(trim($data[1]), 0, -1);
				$sql = "UPDATE ".$TBLPREFIX."language_help SET `lg_".$storelang."` = '".mysql_real_escape_string($data[1])."', lg_last_update_date='".time()."', lg_last_update_by='install' WHERE lg_string = '".$data[0]."' LIMIT 1";
				if (!$result = mysql_query($sql)) {
					print mysql_error();
					print "<br />";
					$output["help"] = false;
					print "Install => Could not add language help string ".$line." for language ".$storelang." to table ";
				}
				else set_time_limit(10);
			}
		}
	}
	if (file_exists("../languages/facts.".$language_settings[$storelang]["lang_short_cut"].".txt")) {
		$lines = file("../languages/facts.".$language_settings[$storelang]["lang_short_cut"].".txt");
			foreach ($lines as $key => $line) {
			$data = preg_split("/\";\"/", $line, 2);
			if (!isset($data[1])) print "Problem with facts language string ".$line."<br />";
			else {
				$data[0] = substr(trim($data[0]), 1);
				$data[1] = substr(trim($data[1]), 0, -1);
				$sql = "UPDATE ".$TBLPREFIX."facts SET `lg_".$storelang."` = '".mysql_real_escape_string($data[1])."', lg_last_update_date='".time()."', lg_last_update_by='install' WHERE lg_string = '".$data[0]."' LIMIT 1";
				if (!$result = mysql_query($sql)) {
					print mysql_error();
					print "<br />";
					$output["facts"] = false;
					print "Install => Could not add facts string ".$line." for language ".$storelang." to table ";
				}
				else set_time_limit(10);
			}
		}
	}
	$sql = "INSERT INTO ".$TBLPREFIX."lang_settings (ls_gm_langname, ls_translated, ls_md5_lang, ls_md5_help, ls_md5_facts) VALUES ('".$storelang."', '0','".md5_file("../languages/lang.".$language_settings[$storelang]["lang_short_cut"].".txt")."', '".md5_file("../languages/help_text.".$language_settings[$storelang]["lang_short_cut"].".txt")."', '".md5_file("../languages/facts.".$language_settings[$storelang]["lang_short_cut"].".txt")."') ON DUPLICATE KEY UPDATE ls_translated='0', ls_md5_lang='".md5_file("../languages/lang.".$language_settings[$storelang]["lang_short_cut"].".txt")."', ls_md5_help='".md5_file("../languages/help_text.".$language_settings[$storelang]["lang_short_cut"].".txt")."', ls_md5_facts='".md5_file("../languages/facts.".$language_settings[$storelang]["lang_short_cut"].".txt")."'";
	$res = mysql_query($sql);

	return $output;
}

function InstallRemoveLanguage($removelang) {
	global $TBLPREFIX;
	
	if ($removelang != "english") {
		// Drop the column
		$sql = "ALTER TABLE ".$TBLPREFIX."language DROP lg_".$removelang;
		$result = mysql_query($sql);
		
		// Add the column
		$sql = "ALTER TABLE ".$TBLPREFIX."language ADD lg_".$removelang." TEXT NOT NULL";
		$result = mysql_query($sql);
		
		// NOTE: Drop the column help text
		$sql = "ALTER TABLE ".$TBLPREFIX."language_help DROP lg_".$removelang;
		$result = mysql_query($sql);
		
		// Add the column
		$sql = "ALTER TABLE ".$TBLPREFIX."language_help ADD lg_".$removelang." TEXT NOT NULL";
		$result = mysql_query($sql);
		
		// NOTE: Drop the column help text
		$sql = "ALTER TABLE ".$TBLPREFIX."facts DROP lg_".$removelang;
		$result = mysql_query($sql);
		
		// Add the column
		$sql = "ALTER TABLE ".$TBLPREFIX."facts ADD lg_".$removelang." TEXT NOT NULL";
		$result = mysql_query($sql);
	}
	return $result;
}

/**
 * Store CONFIG array
 *
 * this function will store the <var>$CONFIG_PARMS</var> array in the config.php
 * file.  The config.php file is parsed in session.php to create the system variables
 * with every page request.
 * @see session.php
 */
function InstallStoreConfig() {
	global $newconfigparms;

	//-- Determine which values must be written as false/true
	$boolean = array("DBPERSIST", "CONFIGURED");
	
	//-- First lines
	$configtext = "<"."?php\n";
	$configtext .= "if (preg_match(\"/\Wconfig.php/\", \$_SERVER[\"SCRIPT_NAME\"])>0) {\n";
//	$configtext .= "print \"Got your hand caught in the cookie jar.\";\n";
//	$configtext .= "exit;\n";
	$configtext .= "\$INTRUSION_DETECTED = true;\n";
	$configtext .= "}\n";
	$configtext .= "//--START SITE CONFIGURATIONS\n";
	$configtext .= "\$CONFIG_PARMS = array();\n";
	
	//-- Scroll through the site configs
	foreach($newconfigparms as $indexval => $config) {
		$configtext .= "\$CONFIG = array();\n";
		//-- Scroll through the site parms
		foreach($config as $key=>$conf) {
			//-- If boolean, add true or false
			if (in_array($key, $boolean)) {
				$configtext .= "\$CONFIG[\"".$key."\"] = ";
				if ($conf == "true") $configtext .= "true;\n";
				else $configtext .= "false;\n";
			}
			//-- If not boolean, add the value in quotes
			else $configtext .= "\$CONFIG[\"".$key."\"] = '".addslashes($conf)."';\n";
		}
		//-- add last line per config
		$configtext .= "\$CONFIG_PARMS[\"".$indexval."\"] = \$CONFIG;\n";
	}
	//-- Add last lines
	$configtext .= "require_once(\"includes/session.php\")\n"."?".">";
	
	//-- Store the config file
	if (file_exists("../config.php")) {
		if (file_exists("../config.old") && AdminFunctions::FileIsWriteable("../config.old")) unlink("../config.old");
		if (AdminFunctions::FileIsWriteable("../config.old")) copy("../config.php", "../config.old");
	}
	$fp = @fopen("../config.php", "wb");
	if (!$fp) {
		return false;
		}
	else {
		fwrite($fp, $configtext);
		fclose($fp);
		return true;
	}
}

function GuessIndexDirectory($inuse) {
	
	if (!in_array("./index/", $inuse)) return "./index/";
	$i = 1;
	while (1) {
		$idir = "./index".$i."/";
		if (!in_array($idir, $inuse)) return $idir;
		$i++;
	}
}	
?>
