<?php
/**
 * Database layer for Genmod
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
 * $Id: dblayer_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 * @package Genmod
 * @subpackage Database
 */
 
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
 
class DbLayer extends MysqlDb {
	
	public $classname = "DbLayer";	// Name of this class
	
	public function __construct() {
		
		parent::__construct();
		
		$this->connected = $this->MakeConnection();
	}
	
	/**
	 * Check the database for integrity of the tables and fields
	 *
	 * This function checks the database to see if all the tables are in place
	 * and each table contains the correct fields. If the database is damaged, the
	 * log table will be created if it is missing to be able to store the log
	 * messages.
	 *
	 * @author	Genmod Development Team
	 * @return 	bool 	return true if the database is correct otherwise returns false
	 */
	public function CheckDBLayout() {
		
		if (file_exists("includes/values/db_layout.php")) require("includes/values/db_layout.php");
		else if (file_exists("values/db_layout.php")) require("values/db_layout.php");
		else return false;
		
		ArrayCopy($db_original, $db_layout);
		
		// First check for the default character set and collation of the server
		$sql = "show variables like '%character_set_server%'";
		$res = NewQuery($sql);
		$row = $res->fetchrow();
		$server_charset = $row[1];
		$sql = "show variables like '%collation_server%'";
		$res = NewQuery($sql);
		$row = $res->fetchrow();
		$server_collation = $row[1];
		
		// Check the default character set for the DB
		$sql = "SELECT @@SQL_QUOTE_SHOW_CREATE";
		$res = NewQuery($sql);
		$row = $res->fetchrow();
		$sqsc = $row[0];
		if ($sqsc) {
			$sql = "SET SQL_QUOTE_SHOW_CREATE=0";
			$res = NewQuery($sql);
		}
		$sql = "SHOW CREATE DATABASE `".DBNAME."`";
		$res = NewQuery($sql);
		$string = $res->FetchRow();
		$ct = preg_match("/DEFAULT CHARACTER SET (.+)\*/", $string[1], $charset);
		if ($charset[1] != $server_charset) {
			$sql = "ALTER DATABASE `".DBNAME."` CHARACTER SET ".$server_charset;
			$res = NewQuery($sql);
		}
		
		// Now get the tables
		$sql = "SHOW TABLES FROM `".DBNAME."` LIKE '".preg_replace("/_/","\_", TBLPREFIX)."%'";
		$result = NewQuery($sql);
		$deleterows = array();
		$deletekeys = array();
		$deletetables = array();
		if (!$result) return false;
		while ($row = $result->FetchRow()) {
			if (!isset($db_original[$row[0]])) {
				$deletetables[] = $row[0];
			}
			else {
				$altchar = false;
				// NOTE: Retrieve a list of fields of the current table
				$sql = "SHOW FULL COLUMNS FROM ".$row[0];
				$res = NewQuery($sql);
				if ($res->NumRows() > 0) {
					while ($fieldrow = $res->FetchAssoc()) {
						// if any fields exist in db_layout for this table, check
						if (isset($db_layout[$row[0]]["row"])) {
							// NOTE: Check if the fieldname exists in the database layout
							if (array_key_exists($fieldrow["Field"], $db_layout[$row[0]]["row"])) {
								if ($fieldrow["Collation"] != $server_collation && (strstr($fieldrow["Type"],"char") != false || strstr($fieldrow["Type"],"text")) != false) {
									$altchar = true;
								}
								unset($db_layout[$row[0]]["row"][$fieldrow["Field"]]);
								if (count($db_layout[$row[0]]["row"]) == 0) unset($db_layout[$row[0]]["row"]);
							}
							else $deleterows[] = array($row[0], $fieldrow["Field"]);
						}
						// remaining fields to be deleted
						else $deleterows[] = array($row[0], $fieldrow["Field"]);
					}
				}
				if ($altchar) {
					$altchar = false;
					$sql = "ALTER TABLE ".$row[0]." CONVERT TO CHARACTER SET '".$server_charset."' collate '".$server_collation."'";
					$resa = NewQuery($sql);
				}
				// NOTE: Retrieve a list of keys of the current table
				$sql = "SHOW KEYS FROM ".$row[0];
				$res = NewQuery($sql);
				if ($res->NumRows() > 0) {
					while ($fieldrow = $res->FetchAssoc()) {
						if (isset($db_layout[$row[0]]["key"])) {
							// NOTE: Check if the fieldname exists in the database layout
							if (array_key_exists(strtolower($fieldrow["Key_name"]), $db_layout[$row[0]]["key"])) {
								unset($db_layout[$row[0]]["key"][strtolower($fieldrow["Key_name"])]);
								if (count($db_layout[$row[0]]["key"]) == 0) unset($db_layout[$row[0]]["key"]);
								// NOTE: Remove the array if it contains no entries
								if (count($db_layout[$row[0]]) == 0) unset($db_layout[$row[0]]);
							}
							else {
								// We must check again, because combined field keys are reported twice and thus already deleted from the db_layout table
								if (!array_key_exists(strtolower($fieldrow["Key_name"]), $db_original[$row[0]]["key"])) {
									$deletekeys[] = array($row[0], $fieldrow["Key_name"]);
								}
							}
						}
						// remaining keys to be deleted
						else {
							if (!isset($db_original[$row[0]]) || !array_key_exists(strtolower($fieldrow["Key_name"]), $db_original[$row[0]]["key"])) {
								$deletekeys[] = array($row[0], $fieldrow["Key_name"]);
							}
						}
					}
				}
			}
		}
		$result->FreeResult();
		if (count($db_layout) > 0 || count($deleterows) > 0 || count($deletekeys) > 0 || count($deletetables) > 0) {
			$tablename = TBLPREFIX."log";
			// NOTE: Check if the log table is affected. If so, restore it
			if (array_key_exists($tablename, $db_layout)) {
				$fields = $db_layout[$tablename];
				$res = NewQuery("SHOW TABLES LIKE '".$tablename."'");
				if ($res->NumRows() > 0) {
					// NOTE: Table exists, only add the missing entries
					$fmsg = "";
					$kmsg = "";
					$sql = "ALTER TABLE `".$tablename."`";
					if (isset($fields["row"])) {
						$fmsg = "DBLayer-&gt; Table ".$tablename." added field(s)";
						foreach ($fields["row"] as $column => $field) {
							$field["details"] = preg_replace(array("/#charset#/", "/#collate#/"), array($server_charset, $server_collation), $field["details"]);
							$prev = " FIRST";
							foreach ($db_original[$tablename]["row"] as $tcolumn => $tdetail) {
								if ($tcolumn == $column) {
									break;
								}
								else $prev = " AFTER ".$tcolumn;
							}
							$sql .= " ADD `".$column."` ".$field["details"].$prev.", ";
							$fmsg .= " ".$column;
						}
					}
					if (isset($fields["key"])) {
						$kmsg = "DBLayer-&gt; Table ".$tablename." added key(s)";
						foreach ($fields["key"] as $column => $field) {
							$sql .= " ADD ".$field.", ";
							$kmsg .= " ".$column;
						}
					}
					$sql = trim($sql);
					$sql = substr($sql, 0, strlen($sql)-1);
					$res = NewQuery($sql);
					if ($res) {
						$res->FreeResult();
						if (!empty($fmsg)) WriteToLog($fmsg, "W","S", "", false);
						if (!empty($kmsg)) WriteToLog($kmsg, "W","S", "", false);
					}
				}
				// NOTE: Log table does not exist, so add the complete table
				else {
					$sql = "CREATE TABLE `".$tablename."` (";
					// NOTE: Add the fields
					foreach ($fields["row"] as $type => $field) {
						$field["details"] = preg_replace(array("/#charset#/", "/#collate#/"), array($server_charset, $server_collation), $field["details"]);
						$sql .= "`".$type."` ".$field["details"].", ";
					}
					// NOTE: Add the keys
					foreach ($fields["key"] as $type => $field) {
						$sql .= $field.", ";
					}
					$sql = trim($sql);
					$sql = substr($sql, 0, strlen($sql)-1);
					$sql .= ") TYPE=MyISAM CHARACTER SET ".$server_charset." COLLATE ".$server_collation;
					$res = NewQuery($sql);
					if ($res) {
						WriteToLog("DBLayer-&gt; Log table was missing. New log table created.","W","S", "", false);
						$res->FreeResult();
					}
				}
			}
			// NOTE: Now fix the rest of the tables
			// First delete keys, then fields. Then add missing ones.
			// Delete obsolete tables
			foreach ($deletetables as $key => $table) {
				$sql = "DROP TABLE `".$table."`";
				$res = @NewQuery($sql);
				if ($res) {
					$res->FreeResult();
					WriteToLog("DBLayer-&gt; Table ".$table." is deleted.","W","S", "", false);
				}
			}
				
			// Delete obsolete indexes
			foreach ($deletekeys as $key => $row) {
				if ($row[1] != "PRIMARY") $sql = "ALTER TABLE `".$row[0]."` DROP INDEX ".$row[1];
//				else $sql = "ALTER TABLE `".$row[0]."` DROP PRIMARY KEY";
				$res = @NewQuery($sql);
				if ($res) {
					$res->FreeResult();
					WriteToLog("DBLayer-&gt; Table ".$row[0]." deleted index ".$row[1],"W","S", "", false);
				}
			}
			// and rows
			foreach ($deleterows as $key => $row) {
				$sql = "ALTER TABLE ".$row[0]." DROP COLUMN ".$row[1];
				$res = NewQuery($sql);
				if ($res) {
					$res->FreeResult();
					WriteToLog("DBLayer-&gt; Table ".$row[0]." deleted field ".$row[1],"W","S", "", false);
				}
			}
			foreach ($db_layout as $tablename => $fields) {
				$fmsg = "";
				$kmsg = "";
				if ($tablename != TBLPREFIX."log") {
					$res = NewQuery("SHOW TABLES LIKE '".$tablename."'");
					if ($res->NumRows() > 0) {
						// NOTE: Table exists, only add the missing entries
						$fmsg = "DBLayer-&gt; Table ".$tablename." added field(s)";
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
								$fmsg .= " ".$column;
							}
						}
						if (isset($fields["key"])) {
							$kmsg = "DBLayer-&gt; Table ".$tablename." added key(s)";
							foreach ($fields["key"] as $column => $field) {
								$sql .= " ADD ".$field.", ";
								$kmsg .= " ".$column;
							}
						}
						$sql = trim($sql);
						$sql = substr($sql, 0, strlen($sql)-1);
						$res = NewQuery($sql);
						if ($res) {
							$res->FreeResult();
							if (!empty($fmsg)) WriteToLog($fmsg, "W","S", "", false);
							if (!empty($kmsg)) WriteToLog($kmsg, "W","S", "", false);
						}
					}
					// NOTE: Table does not exist, so add the complete table
					else {
						$sql = "CREATE TABLE `".$tablename."` (";
						// NOTE: Add the fields
						foreach ($fields["row"] as $type => $field) {
							$field["details"] = preg_replace(array("/#charset#/", "/#collate#/"), array($server_charset, $server_collation), $field["details"]);
							$sql .= "`".$type."` ".$field["details"].", ";
						}
						// NOTE: Add the keys
						foreach ($fields["key"] as $type => $field) {
							$sql .= $field.", ";
						}
						$sql = trim($sql);
						$sql = substr($sql, 0, strlen($sql)-1);
						$sql .= ") TYPE=MyISAM CHARACTER SET ".$server_charset." COLLATE ".$server_collation;
						$res = NewQuery($sql);
						if ($res) {
							WriteToLog("DBLayer-&gt; ".$tablename." table was missing. New table created.","W","S", "", false);
							$res->FreeResult();
						}
					}
				}
			}
			if ($sqsc) {
				$sql = "SET SQL_QUOTE_SHOW_CREATE=1";
				$res = NewQuery($sql);
			}
			return false;
		}
		else {
			if ($sqsc) {
				$sql = "SET SQL_QUOTE_SHOW_CREATE=1";
				$res = NewQuery($sql);
			}	
			return true;
		}
	}
}
?>