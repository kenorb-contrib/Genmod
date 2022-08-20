<?php
/**
 * Upgrades the database to a new structure
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
 * @subpackage admin
 * @version $Id: upgrade15-16.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
 
/**
 * Inclusion of the configuration file
*/
require("config.php");

if (!function_exists("userIsAdmin")) {
	function userIsAdmin($username) {
		return UserController::userIsAdmin($username);
	}
}

if (!userIsAdmin($gm_username)) {
	if (LOGIN_URL == "") header("Location: login.php?url=upgrade15-16.php?".GetQueryString(true));
	else header("Location: ".LOGIN_URL."?url=upgrade15-16.php?".GetQueryString(true));
	exit;
}

PrintHeader("Upgrade script to DB after 1.5 Final");

if (file_exists("includes/values/db_layout.php") || file_exists("values/db_layout.php")) {
	print "Remove the file db_layout.php from your Genmod root directory<br />and from the includes/values directory first.<br />Afterwards, restart this script.<br /><br />";
	print "This will prevent automatic rollback to the old situation if<br />anyone logs in after the upgrade and before installing the new version of the software.<br />";
	PrintFooter();
	exit;
}

// This modification will not be done by CheckDB, so we must do it manually
print "<br /><br /><b>Upgrade Configuration fields</b><br /><br />";
$sql = "ALTER TABLE ".TBLPREFIX."gedconf MODIFY gc_meta_keywords VARCHAR(128), MODIFY gc_alpha_index_lists INT(11)";
$res = NewQuery($sql);
if ($res) {
	$sql = "UPDATE ".TBLPREFIX."gedconf set gc_alpha_index_lists='500' WHERE gc_alpha_index_lists='1'";
	$res2 = NewQuery($sql);
	if ($res2) print "Configuration fields succesfully changed";
}
if (!$res || !$res2) print "There was a problem executing the query: <br />".$DBCONN->sqlerrordesc."<br />".$DBCONN->sqlquery."<br />";

// This part of the script copies the user rights from the users table to the newly created users_gedcoms table. 
// The next login will remove the old columns from the users table.
print "<br /><br /><b>Upgrade User Data</b>";
$sql = "SHOW tables like '".TBLPREFIX."users_gedcoms'";
$res = NewQuery($sql);
if ($res->NumRows() != 0) {
	print "<br /><br />Upgrade not needed, the new database table already exists, and is filled with values at an earlier stage.";
}
else {
	$res->FreeResult();
	print "<b>Upgrade user data</b><br />";
	print "Step 1: Creating new database table for user rights<br />";
	$sql = "CREATE TABLE ".TBLPREFIX."users_gedcoms (ug_ID int(11) NOT NULL auto_increment, ug_username varbinary(30) NOT NULL default '', ug_file int(11) NOT NULL default '0', ug_gedcomid varchar(255) default NULL, ug_rootid varchar(255) default NULL, ug_canedit varchar(7) default NULL, PRIMARY KEY  (ug_ID), KEY ug_user (ug_username), KEY ug_ged (ug_gedcomid,ug_gedfile)) ENGINE=MyISAM";
	$res = NewQuery($sql);
	if (!$res) {
		print "There was a problem creating the table: <br />".$DBCONN->sqlerrordesc."<br />".$DBCONN->sqlquery."<br />";
	}
	else {
		print "Table is sucessfully created<br /><br />Step 2: Starting user conversion<br />";
		$users = GetUsers();
		foreach ($users as $username => $user) {
			if (is_object($user)) $user = get_object_vars($user);
			$success = true;
			foreach ($GEDCOMS as $id=>$value) {
				$sql = "INSERT INTO ".TBLPREFIX."users_gedcoms VALUES('0','";
				$sql .= $user["username"]."','".$value["id"]."','";
				if (isset($user["gedcomid"][get_gedcom_from_id($id)])) $sql .= $user["gedcomid"][get_gedcom_from_id($id)];
				$sql .= "','";
				if (isset($user["rootid"][get_gedcom_from_id($id)])) $sql .= $user["rootid"][get_gedcom_from_id($id)];
				$sql .= "','";
				if (isset($newuser["canedit"][get_gedcom_from_id($id)])) $sql .= $user["canedit"][get_gedcom_from_id($id)]."')";
				else $sql .= "access')";
				$res = NewQuery($sql);
				$success = $success && $res;
			}
			if ($success) print "User ".$username." successfully converted<br />";
			else print "Problem writing the userinformation of ".$username." to the table<br />";
		}
	}
	print "<br />Step 3: Upgrade ended. If no errors appeared above, the conversion of user data is succesfully performed.";
}

print "<br /><br /><b>Upgrade User fields</b><br /><br />";
$sql = "ALTER TABLE ".TBLPREFIX."users MODIFY u_username VARBINARY(30)";
$res = NewQuery($sql);
if ($res) print "User fields succesfully changed";
else print "There was a problem executing the query: <br />".$DBCONN->sqlerrordesc."<br />".$DBCONN->sqlquery."<br />";

// This part adds columns in the privacy table to store the gedcom id. and some other new values. It also fills the column with the appropriate value.
print "<br /><br /><b>Upgrade Privacy Data</b>";
print "<br /><br />Attempting to add new fields to the privacy table and fill initial values";

$update1 = false;
$sql = "SHOW COLUMNS FROM ".TBLPREFIX."privacy LIKE 'p_gedcomid'";
$res = NewQuery($sql);
if ($res && ($res->NumRows() == 0)) {
	$sql = "ALTER TABLE ".TBLPREFIX."privacy ADD p_gedcomid INT(11) AFTER p_gedcom,  ADD KEY p_gedcomid (p_gedcomid)";
	$res1 = NewQuery($sql);
	if($res1) $update1 = true;
}
if (!$res) print "<br /><br />There was a problem altering the Database.<br />Error: ".$DBCONN->sqlerrordesc;

$update2 = false;
$sql = "SHOW COLUMNS FROM ".TBLPREFIX."privacy LIKE 'p_link_privacy'";
$res = NewQuery($sql);
if ($res && ($res->NumRows() == 0)) {
	$sql = "ALTER TABLE ".TBLPREFIX."privacy ADD p_link_privacy TINYINT(1) AFTER p_enable_clippings_cart";
	$res2 = NewQuery($sql);
	if($res2) $update2 = true;
}
if (!$res) print "<br /><br />There was a problem altering the Database.<br />Error: ".$DBCONN->sqlerrordesc;

$update5 = false;
$sql = "SHOW COLUMNS FROM ".TBLPREFIX."privacy LIKE 'p_hide_live_people'";
$res = NewQuery($sql);
if ($res && ($res->NumRows() == 0)) {
	$sql = "ALTER TABLE ".TBLPREFIX."privacy ADD p_hide_live_people VARCHAR(12) AFTER p_priv_none";
	$res5 = NewQuery($sql);
	if($res5) $update5 = true;
}
if (!$res) print "<br /><br />There was a problem altering the Database.<br />Error: ".$DBCONN->sqlerrordesc;

$update3 = false;
$sql = "SHOW COLUMNS FROM ".TBLPREFIX."privacy LIKE 'p_show_dead_people'";
$res = NewQuery($sql);
if ($res && ($res->NumRows() == 0)) {
	$sql = "ALTER TABLE ".TBLPREFIX."privacy ADD p_show_dead_people VARCHAR(12) AFTER p_hide_live_people";
	$res3 = NewQuery($sql);
	if($res3) $update3 = true;
}
if (!$res) print "<br /><br />There was a problem altering the Database.<br />Error: ".$DBCONN->sqlerrordesc;

$update4 = false;
$sql = "SHOW COLUMNS FROM ".TBLPREFIX."privacy LIKE 'p_check_child_dates'";
$res = NewQuery($sql);
if ($res && ($res->NumRows() == 0)) {
	$sql = "ALTER TABLE ".TBLPREFIX."privacy ADD p_check_child_dates TINYINT(1) AFTER p_check_marriage_relations";
	$res4 = NewQuery($sql);
	if($res4) $update4 = true;
}
if (!$res) print "<br /><br />There was a problem altering the Database.<br />Error: ".$DBCONN->sqlerrordesc;

if ($update1 || $update2 || $update3 || $update4 || $update5) {
	foreach ($GEDCOMS as $gedc => $values) {
		$sql = "UPDATE ".TBLPREFIX."privacy set ";
		if ($update1) $sql .= "p_gedcomid='".$values["id"]."', ";
		if ($update2) $sql .= "p_link_privacy='1', ";
		if ($update3) $sql .= "p_show_dead_people='PRIV_PUBLIC', ";
		if ($update4) {
			$sql2 = "select gc_check_child_dates FROM ".TBLPREFIX."gedconf WHERE gc_gedcom='".$gedc."'";
			$r = NewQuery($sql2);
			$val = $res->FetchRow();
			$sql .= "p_check_child_dates='".$val[0]."', ";
		}
		if ($update5) $sql .= "p_hide_live_people='PRIV_USER', ";
		$sql .= "p_privacy_version='3.3' ";
		$sql .= "WHERE p_gedcom='".$gedc."'";
		$res = NewQuery($sql);
	}
	print "<br /><br />Update of field(s) and adding values is ready.";
}
else print "<br />Database was already upgraded. No action was taken.";
print "<br /><br />End of upgrade script.";
PrintFooter();
?>