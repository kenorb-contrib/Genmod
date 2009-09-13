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

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class SystemConfig {

	// Classname of this class
	private $classname = "SystemConfig";
	
	// Default value for this setting, that stores the maximum tried execution time
	public $max_execution_time = 0;
	
	// To store the names and values of the variables read from the database. Used for determining the changes made.
	private $configdb_list = array(); 	
	
	// Initialize the exclude parameter array. These variables are excluded from the database and will be stored in config.php
	// INDEX_DIRECTORY must NOT be stored in the DB, as changes to this parameter must be checked against the values for all sites.
	private $exclude_parms = array("DBHOST", "DBUSER", "DBPASS", "DBNAME", "DBPERSIST", "TBLPREFIX", "SERVER_URL", "LOGIN_URL", "SITE_ALIAS", "CONFIGURED", "INDEX_DIRECTORY", "GM_SESSION_SAVE_PATH", "GM_SESSION_TIME");
	
	// Array of boolean type variables
	private $boolean  = array("DBPERSIST", "GM_STORE_MESSAGES", "GM_SIMPLE_MAIL", "USE_REGISTRATION_MODULE", "REQUIRE_ADMIN_AUTH_REGISTRATION", "ALLOW_USER_THEMES", "ALLOW_CHANGE_GEDCOM", "ALLOW_REMEMBER_ME", "CONFIGURED", "MEDIA_IN_DB");
	
	// On instantiation, only the vars from the DB are read. The vars stored in the config.php are already read.
	public function __construct() {
		
		// Read the current config parms
		$sql = "SELECT * FROM ".TBLPREFIX."sysconf WHERE 1";
		$res = NewQuery($sql);
		if ($res) {
			while($row = $res->FetchAssoc()) {
				$var = substr($row["s_name"], 2);
				// Catch variables that don't belong in the DB
				if (!in_array($var, $this->exclude_parms)) {
					if ($row["s_value"] == "false") $this->$var = false;
					else if ($row["s_value"] == "true") $this->$var = true;
					else $this->$var = $row["s_value"];
					// temporary also make it global variables
					global $$var;
					$$var = $this->$var;
					$this->configdb_list[$var] = $row["s_value"];
				}
				// this variable should not be in the DB, so delete it.
				else $this->DeleteConfigDBValue($var);
			}
		}
	}
	
	public function SetConfigDBValue($name, $value) {
		
		if (in_array($name, $this->exclude_parms)) return false;
		$sql = "INSERT INTO ".TBLPREFIX."sysconf (s_name, s_value) VALUES ('s_".$name."', '".$value."') ON DUPLICATE KEY UPDATE s_value='".$value."'";
		$res = NewQuery($sql);
		if ($value == "false") $this->$name = false;
		else if ($value == "true") $this->$name = true;
		else $this->$name = $value;
		
		// temporary also make it global variables
		global $$name;
		$$name = $this->$name;
		
		// And add it to the list
		$this->configdb_list[$name] = $value;
		
		return true;
	}
	
	// this function will store the <var>$CONFIG_PARMS</var> array in the config.php file.
	public function StoreConfig($newconfig) {
		global $CONFIG_PARMS, $gm_lang;

		if (!is_array($newconfig)) return false;
		
		// First see what values are changed: only for values in the config.sys file we must rewrite that file
		// Then store the DB config parms in the database, if changed
		$config_file_update = false;
		foreach ($newconfig as $key => $param) {
			// normalise the param.
			if (in_array($key, $this->boolean)) {
				if ($param) $param = "true";
				else $param = "false";
			}
			// Check if it's a config.php value
			if (in_array($key, $this->exclude_parms)) {
				// if yes, check if it has changed or doesn't exist yet
				if ($config_file_update == false && (!isset($CONFIG_PARMS[$newconfig["SERVER_URL"]][$key]) || $newconfig[$key] != $CONFIG_PARMS[$newconfig["SERVER_URL"]][$key])) {
					$config_file_update = true;
				}
			}
			// Otherwise, it's a DB value and we must check if it has changed or is even new
			else {
				if (!isset($this->configdb_list[$key]) || $this->configdb_list[$key] != $param) {
					WriteToLog("SystemConfig-> Admin has set ".$key." to ".$param, "I", "S");
					$this->SetConfigDBValue($key, $param);
				}
			}
		}
		
		// Construct config.php only if changes must be made to that file
		if ($config_file_update) {
			//-- First lines
			$configtext = $this->AddConfigHeader();
		
			//-- Scroll through the site configs
			foreach($CONFIG_PARMS as $installurl => $CONFIG) {
				if ($installurl == $newconfig["SERVER_URL"]) $configtext .= $this->AddConfig($newconfig);
				else $configtext .= $this->AddConfig($CONFIG);
			}
			//-- Add last lines
			$configtext .= $this->AddConfigFooter();
			// Write the config file
			if ($this->WriteConfig($configtext)) {
				WriteToLog("SystemConfig-> Admin has updated config for ".$newconfig["SERVER_URL"], "I", "S");
				return true;
			}
			else return false;
		}
		else return true;
	}
	
	public function DeleteConfig($config_name) {
		global $CONFIG_PARMS;
		
		if (!isset($CONFIG_PARMS[$config_name])) return false;
		
		$configtext = $this->AddConfigHeader();
		
		// Only add configs that are not to be deleted
		foreach($CONFIG_PARMS as $site => $config) {
			if ($site != $config_name) $configtext .= $this->AddConfig($config);
		}
		//-- Add last line
		$configtext .= $this->AddConfigFooter();
		// Write the config file
		if ($this->WriteConfig($configtext)) {
			WriteToLog("SystemConfig-> Admin has deleted config for ".$config_name, "I", "S");
			return true;
		}
		else return false;

	}
			
		
	private function DeleteConfigDBValue($name) {
		
		$sql = "DELETE FROM ".TBLPREFIX."sysconf WHERE s_name='s_".$name."'";
		$res = NewQuery($sql);
		unset($this->configdb_list[$name]);
	}
	
	private function AddConfigHeader() {
		
		// First part of config.php
		$string = "<"."?php\n";
		$string .= "if (preg_match(\"/\Wconfig.php/\", \$_SERVER[\"SCRIPT_NAME\"])>0) {\n";
		$string .= "\$INTRUSION_DETECTED = true;\n";
		$string .= "}\n";
		$string .= "//--START SITE CONFIGURATIONS\n";
		$string .= "\$CONFIG_PARMS = array();\n";
		return $string;
	}

	// This function returns the part of config.php for the config array in the parameter	
	private function AddConfig($config) {
		global $CONFIG_PARMS;
		
		$text = "";
		$text .= "\$CONFIG = array();\n";

		//-- Scroll through the site parms
		foreach($config as $key=>$conf) {
			if (in_array($key, $this->exclude_parms)) {
				//-- If boolean, add true or false
				if (in_array($key, $this->boolean)) {
					if ($conf == true || $conf == "true") $text .= "\$CONFIG[\"".$key."\"] = true;\n";
					else $text .= "\$CONFIG[\"".$key."\"] = false;\n";
				}
				//-- If not boolean, add the value in quotes
				else $text .= "\$CONFIG[\"".$key."\"] = '".addslashes($conf)."';\n";
				if (!isset($CONFIG_PARMS[$config["SERVER_URL"]][$key]) || $CONFIG_PARMS[$config["SERVER_URL"]][$key] != $config[$key]) {
					WriteToLog("SystemConfig-> Admin has set ".$key." to ".$conf, "I", "S");
					global $key;
					$key = $conf;
				}
			}
		}
		//-- add last line per config
		$text .= "\$CONFIG_PARMS[\"".$config["SERVER_URL"]."\"] = \$CONFIG;\n";
		return $text;
	}

	private function AddConfigFooter() {
	
		$string = "require_once(\"includes/session.php\");\n"."?".">";
		return $string;
	}
			
	private function WriteConfig($string) {
		
		//-- Store the config file
		if (file_exists("config.php")) {
			if (file_exists("config.old") && FileIsWriteable("config.old")) unlink("config.old");
			if (FileIsWriteable("config.old")) copy("config.php", "config.old");
		}
		$fp = @fopen("config.php", "wb");
		if (!$fp) {
			return false;
			}
		else {
			fwrite($fp, $string);
			fclose($fp);
			return true;
		}
	}
}
?>