<?php
/**
 * Class file for system config
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

if (strstr($_SERVER["SCRIPT_NAME"],"system_config_class")) {
	require "../intrusion.php";
}

class SystemConfig {

	var $max_execution_time = 0;
	var $exclude_parms = array();
	
	function SystemConfig() {
		global $TBLPREFIX;
		
		// Initialize the exclude parameter array
		// INDEX_DIRECTORY must NOT be stored in the DB, as changes to this parameter must be checked against the values for all sites.
		$this->exclude_parms = array("DBHOST", "DBUSER", "DBPASS", "DBNAME", "DBPERSIST", "TBLPREFIX", "SERVER_URL", "LOGIN_URL", "SITE_ALIAS", "CONFIGURED", "INDEX_DIRECTORY", "GM_SESSION_SAVE_PATH", "GM_SESSION_TIME");
		
		// Read the current config parms
		$sql = "SELECT * FROM ".$TBLPREFIX."sysconf WHERE 1";
		$res = NewQuery($sql);
		if ($res) {
			while($row = $res->FetchAssoc()) {
				$var = substr($row["s_name"], 2);
				if (!in_array($var, $this->exclude_parms)) {
					if ($row["s_value"] == "false") $this->$var = false;
					else if ($row["s_value"] == "true") $this->$var = true;
					else $this->$var = $row["s_value"];
					// temporary also make it global variables
					global $$var;
					$$var = $this->$var;
				}
				else $this->DeleteConfigValue($var);
			}
		}
	}
	
	function StoreConfigValue($name, $value) {
		global $TBLPREFIX;
		
		if (in_array($name, $this->exclude_parms)) return false;
		$sql = "INSERT INTO ".$TBLPREFIX."sysconf (s_name, s_value) VALUES ('s_".$name."', '".$value."') ON DUPLICATE KEY UPDATE s_value='".$value."'";
		$res = NewQuery($sql);
		if ($value == "false") $this->$name = false;
		else if ($value == "true") $this->$name = true;
		else $this->$name = $value;
		
		// temporary also make it global variables
		global $$name;
		$$name = $this->$name;
		
		return true;
	}

	function DeleteConfigValue($name) {
		global $TBLPREFIX;
		
		$sql = "DELETE FROM ".$TBLPREFIX."sysconf WHERE s_name='s_".$name."'";
		$res = NewQuery($sql);
	}
			
	function GetConfigFileParmList() {
		return $this->exclude_parms;
	}
		
}