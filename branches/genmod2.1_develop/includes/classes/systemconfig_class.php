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
 * @version $Id: systemconfig_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

abstract class SystemConfig {

	// Classname of this class
	public static $classname = "SystemConfig";
	
	// Default value for this setting, that stores the maximum tried execution time
	public static $max_execution_time = 0;
	
	// To store the names and values of the variables read from the database. Used for determining the changes made.
	private static $configdb_list = array(); 	
	
	// Initialize the exclude parameter array. These variables are excluded from the database and will be stored in config.php
	// They are available throughout the program as constants.
	// All other variables can be accessed with SystemConfig::$VARIABLE.
	// INDEX_DIRECTORY must NOT be stored in the DB, as changes to this parameter must be checked against the values for all sites.
	private static $exclude_parms = array("DBHOST", "DBUSER", "DBPASS", "DBNAME", "DBPERSIST", "TBLPREFIX", "SERVER_URL", "LOGIN_URL", "SITE_ALIAS", "CONFIGURED", "INDEX_DIRECTORY", "GM_SESSION_SAVE_PATH", "GM_SESSION_TIME");
	
	// Array of boolean type variables
	private static $boolean  = array("DBPERSIST", "GM_STORE_MESSAGES", "GM_SIMPLE_MAIL", "USE_REGISTRATION_MODULE", "REQUIRE_ADMIN_AUTH_REGISTRATION", "ALLOW_USER_THEMES", "ALLOW_CHANGE_GEDCOM", "ALLOW_REMEMBER_ME", "CONFIGURED", "MEDIA_IN_DB");

	// Static variables
	public static $GM_BASE_DIRECTORY			 	= "";		//-- path to Genmod (Only needed when running as Genmod from another php program 
																//-- such as postNuke, otherwise leave it blank)
	public static $MEDIA_IN_DB 						= false;
	public static $GM_STORE_MESSAGES				= true;		//-- allow messages sent to users to be stored in the GM system
	public static $GM_SIMPLE_MAIL					= true;		//-- allow admins to set this so that they can override the name <emailaddress> 
																//-- combination in the emails
	public static $USE_REGISTRATION_MODULE			= true;		//-- turn on the user self registration module
	public static $REQUIRE_ADMIN_AUTH_REGISTRATION	= true;		//-- require an admin user to authorize a new registration before a user can login
	public static $ALLOW_USER_THEMES				= true;		//-- Allow user to set their own theme
	public static $ALLOW_CHANGE_GEDCOM				= true;		//-- A true value will provide a link in the footer to allow users to change the 
																//-- gedcom they are viewing
	public static $MAX_VIEWS						= 100;		//-- the maximum number of page views per xx seconds per session
	public static $MAX_VIEW_TIME					= 0;		//-- the number of seconds in which the maximum number of views must not be reached
	public static $MAX_VIEW_LOGLEVEL				= 0;		//-- 0 no logging, 1 exceeding treshold, 2 # views when MAX_VIEW_TIME has passed 
																//-- and reset
	public static $EXCLUDE_HOSTS					= "";		//-- List of hosts and IP's to exclude from session-IP check
	public static $GM_MEMORY_LIMIT					= "64M";	//-- the maximum amount of memory that GM should be allowed to consume
	public static $ALLOW_REMEMBER_ME				= true;		//-- whether the users have the option of being remembered on the current computer
	public static $CONFIG_VERSION					= "1.0";	//-- the version this config file goes to
	public static $NEWS_TYPE						= "Normal";	//-- Type of news to be retrieved from the Genmod website
	public static $GM_NEWS_SERVER					= "";		//-- Server URL to get Genmod news from
	public static $PROXY_ADDRESS					= "";		//-- Allows obtaining GM-News and GEDCOM checking when the server is behind a proxy.	
																//-- Type either IP address or name (e.g. mywwwproxy.net)
	public static $PROXY_PORT						= "";		//-- Proxy port to be used
	public static $PROXY_USER						= "";		//-- Username for proxy authentication
	public static $PROXY_PASSWORD					= "";		//-- Password for proxy authentication
	public static $LOCKOUT_TIME						= -1;		//-- Lockout time after intrusion attempt. -1 = no lockout, 0 = forever, 
																//-- any other = # minutes
	public static $VISITOR_LANG						= "Genmod";	//-- Let Genmod determine the site language, or a specific language
	public static $DEFAULT_PAGE_SIZE				= "A4";		//-- Sets the default page size for reports
	
	// On instantiation, only the vars from the DB are read. The vars stored in the config.php are already read.
	public static function Initialise() {
		
		// Read the current config parms
		$sql = "SELECT * FROM ".TBLPREFIX."sysconf WHERE 1";
		$res = NewQuery($sql);
		if ($res) {
			while($row = $res->FetchAssoc()) {
				$var = substr($row["s_name"], 2);
				// Catch variables that don't belong in the DB
				if (!in_array($var, self::$exclude_parms) && property_exists("SystemConfig", $var)) {
					if ($row["s_value"] == "false") self::$$var = false;
					else if ($row["s_value"] == "true") self::$$var = true;
					else self::$$var = $row["s_value"];
					
					self::$configdb_list[$var] = $row["s_value"];
				}
				// this variable should not be in the DB, so delete it.
				else self::DeleteConfigDBValue($var);
			}
		}
	}
	
	public function SetConfigDBValue($name, $value) {
		
		if (in_array($name, self::$exclude_parms)) return false;
		$sql = "INSERT INTO ".TBLPREFIX."sysconf (s_name, s_value) VALUES ('s_".$name."', '".$value."') ON DUPLICATE KEY UPDATE s_value='".$value."'";
		$res = NewQuery($sql);
		if ($value == "false") self::$$name = false;
		else if ($value == "true") self::$$name = true;
		else self::$$name = $value;
		
		// And add it to the list
		self::$configdb_list[$name] = $value;
		
		return true;
	}
	
	// this function will store the <var>$CONFIG_PARMS</var> array in the config.php file.
	public function StoreConfig($newconfig) {
		global $CONFIG_PARMS;

		if (!is_array($newconfig)) return false;
		
		// First see what values are changed: only for values in the config.sys file we must rewrite that file
		// Then store the DB config parms in the database, if changed
		$config_file_update = false;
		foreach ($newconfig as $key => $param) {
			// normalise the param.
			if (in_array($key, self::$boolean)) {
				if ($param) $param = "true";
				else $param = "false";
			}
			// Check if it's a config.php value
			if (in_array($key, self::$exclude_parms)) {
				// if yes, check if it has changed or doesn't exist yet
				if ($config_file_update == false && (!isset($CONFIG_PARMS[$newconfig["SERVER_URL"]][$key]) || $newconfig[$key] != $CONFIG_PARMS[$newconfig["SERVER_URL"]][$key])) {
					$config_file_update = true;
				}
			}
			// Otherwise, it's a DB value and we must check if it has changed or is even new
			else {
				if (!isset(self::$configdb_list[$key]) || self::$configdb_list[$key] != $param) {
					WriteToLog("SystemConfig-&gt;StoreConfig-&gt; Admin has set ".$key." to ".$param, "I", "S");
					self::SetConfigDBValue($key, $param);
				}
			}
		}
		
		// Construct config.php only if changes must be made to that file
		if ($config_file_update) {
			//-- First lines
			$configtext = self::AddConfigHeader();
		
			//-- Scroll through the site configs
			foreach($CONFIG_PARMS as $installurl => $CONFIG) {
				if ($installurl == $newconfig["SERVER_URL"]) $configtext .= self::AddConfig($newconfig);
				else $configtext .= self::AddConfig($CONFIG);
			}
			//-- Add last lines
			$configtext .= self::AddConfigFooter();
			// Write the config file
			if (self::WriteConfig($configtext)) {
				WriteToLog("SystemConfig-&gt;StoreConfig-&gt; Admin has updated config for ".$newconfig["SERVER_URL"], "I", "S");
				return true;
			}
			else return false;
		}
		else return true;
	}
	
	public function DeleteConfig($config_name) {
		global $CONFIG_PARMS;
		
		if (!isset($CONFIG_PARMS[$config_name])) return false;
		
		$configtext = self::AddConfigHeader();
		
		// Only add configs that are not to be deleted
		foreach($CONFIG_PARMS as $site => $config) {
			if ($site != $config_name) $configtext .= self::AddConfig($config);
		}
		//-- Add last line
		$configtext .= self::AddConfigFooter();
		// Write the config file
		if (self::WriteConfig($configtext)) {
			WriteToLog("SystemConfig-&gt;DeleteConfig-&gt; Admin has deleted config for ".$config_name, "I", "S");
			return true;
		}
		else return false;

	}
			
		
	private function DeleteConfigDBValue($name) {
		
		$sql = "DELETE FROM ".TBLPREFIX."sysconf WHERE s_name='s_".$name."'";
		$res = NewQuery($sql);
		unset(self::$configdb_list[$name]);
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
			if (in_array($key, self::$exclude_parms)) {
				//-- If boolean, add true or false
				if (in_array($key, self::$boolean)) {
					if ($conf == true || $conf == "true") $text .= "\$CONFIG[\"".$key."\"] = true;\n";
					else $text .= "\$CONFIG[\"".$key."\"] = false;\n";
				}
				//-- If not boolean, add the value in quotes
				else $text .= "\$CONFIG[\"".$key."\"] = '".addslashes($conf)."';\n";
				if (!isset($CONFIG_PARMS[$config["SERVER_URL"]][$key]) || $CONFIG_PARMS[$config["SERVER_URL"]][$key] != $config[$key]) {
					WriteToLog("SystemConfig-&gt;AddConfig-&gt; Admin has set ".$key." to ".$conf, "I", "S");
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
			if (file_exists("config.old") && AdminFunctions::FileIsWriteable("config.old")) unlink("config.old");
			if (AdminFunctions::FileIsWriteable("config.old")) copy("config.php", "config.old");
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