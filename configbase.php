<?php
/**
 * Main configuration file required by all other files in GM
 *
 * The variables in this file are the main configuration variable for the site
 * Gedcom specific configuration variables are stored in the config_gedcom.php file.
 * Site administrators may edit these settings online through the editconfig.php file.
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
 * @see editconfig.php
 * @version $Id: configbase.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (preg_match("/\Wconfigbase.php/", $_SERVER["SCRIPT_NAME"])>0) {
	require "intrusion.php";
}

/**
 * Absolut Path to Genmod installation
 *
 */
$GM_BASE_DIRECTORY = "";						//-- path to Genmod (Only needed when running as Genmod from another php program such as postNuke, otherwise leave it blank)
$DBHOST = "localhost";							//-- Host where MySQL database is kept
$DBUSER = "";									//-- MySQL database User Name
$DBPASS = "";									//-- MySQL database User Password
$DBNAME = "genmod";								//-- The MySQL database name where you want Genmod to build its tables
$DBPERSIST = true;
$TBLPREFIX = "gm_";								//-- prefix to include on table names
$INDEX_DIRECTORY = "./index/";					//-- Readable and Writeable Directory to store index files (include the trailing "/")
$SERVER_URL = "";								//-- the URL used to access this server
$LOGIN_URL = "";								//-- the URL to use to go to the login page, use this value if you want to redirect to a different site when users login, useful for switching from http to https
$SITE_ALIAS = "";								//-- Other names under which this site can be known
$GM_SESSION_TIME = "7200";						//-- number of seconds to wait before an inactive session times out
$GM_SESSION_SAVE_PATH = "";						//-- Path to save PHP session Files -- DO NOT MODIFY unless you know what you are doing
												//-- leaving it blank will use the default path for your php configuration as found in php.ini
$MEDIA_IN_DB = false;							//-- Where to store the MM files: DB or not DB (file system)
$CONFIGURED = false;

//$GM_STORE_MESSAGES = true;						//-- allow messages sent to users to be stored in the GM system
//$GM_SIMPLE_MAIL = true;							//-- allow admins to set this so that they can override the name <emailaddress> combination in the emails
//$USE_REGISTRATION_MODULE = true;				//-- turn on the user self registration module
//$REQUIRE_ADMIN_AUTH_REGISTRATION = true;		//-- require an admin user to authorize a new registration before a user can login
//$ALLOW_USER_THEMES = true;						//-- Allow user to set their own theme
//$ALLOW_CHANGE_GEDCOM = true;					//-- A true value will provide a link in the footer to allow users to change the gedcom they are viewing
//$MAX_VIEWS = "100";								//-- the maximum number of page views per xx seconds per session
//$MAX_VIEW_TIME = "0";							//-- the number of seconds in which the maximum number of views must not be reached
//$MAX_VIEW_LOGLEVEL = "0";						//-- 0 no logging, 1 exceeding treshold, 2 # views when MAX_VIEW_TIME has passed and reset
//$EXCLUDE_HOSTS = "";								//-- List of hosts and IP's to exclude from session-IP check
//$GM_MEMORY_LIMIT = "32M";						//-- the maximum amount of memory that GM should be allowed to consume
//$ALLOW_REMEMBER_ME = true;						//-- whether the users have the option of being remembered on the current computer
//$CONFIG_VERSION = "1.0";						//-- the version this config file goes to
//$NEWS_TYPE = "Normal";							//-- Type of news to be retrieved from the Genmod website
//$PROXY_ADDRESS = "";							//-- Allows obtaining GM-News and GEDCOM checking when the server is behind a proxy. Type either IP address or name (e.g. mywwwproxy.net)
//$PROXY_PORT = "";								//-- Proxy port to be used
//$LOCKOUT_TIME = "-1";							//-- Lockout time after intrusion attempt. -1 = no lockout, 0 = forever, any other = # minutes
//$VISITOR_LANG = "Genmod";						//-- Let Genmod determine the site language, or a specific language
//$DEFAULT_PAGE_SIZE = "A4";						//-- Sets the default page size for reports

if (!stristr($_SERVER["SCRIPT_NAME"], "install.php")) require_once($GM_BASE_DIRECTORY."includes/session.php");
?>