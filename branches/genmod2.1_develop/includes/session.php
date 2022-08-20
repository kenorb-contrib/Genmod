<?php
/**
 * Startup and session logic
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
 * @version $Id: session.php 43 2018-08-15 15:38:10Z Boudewijn $
 */

// NOTE: Start the debug collector
$DEBUG = false;
if ($DEBUG && !stristr($_SERVER["SCRIPT_NAME"],"gmrpc")) {
	define('DEBUG', true);
	DebugCollector::$show = true;
	define('pipo','');
}
else define('DEBUG', false);

if (stristr($_SERVER["SCRIPT_NAME"],"session")) {
//	print "Now, why would you want to do that.  You're not hacking are you?";
//	exit;
$INTRUSION_DETECTED = true;
}

$ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "";

// check for worms and bad bots
$worms = array (
	'Super_Ale',
	'Wget',
	'DataCha',
	'libwww-perl',
	'LWP::Simple',
	'lwp-trivial',
	'MJ bot',
	'DotBot',
	'HTTrack'
);

foreach( $worms as $worm ) {
	if (preg_match("/".$worm."/", $ua)) {
		print "Worms are not allowed here!";
		exit;
	}
}

// Check for allowed bots
$bots = array(
	'Teoma',
	'alexa',
	'froogle',
	'inktomi',
	'looksmart',
	'URL_Spider_SQL',
	'Firefly',
	'Gigabot',
	'NationalDirectory',
	'Ask Jeeves',
	'TECNOSEEK',
	'InfoSeek',
	'WebFindBot',
	'girafbot',
	'crawler',
	'www.galaxy.com',
	'Googlebot',
	'MSNbot',
	'Scooter',
	'Slurp',
	'appie',
	'FAST',
	'WebBug',
	'Spade',
	'ZyBorg',
	'rabaz'
);
$spider = false;
foreach($bots as $bot) {
	if (preg_match("/".$bot."/", $ua)) {
		$spider = $ua;
		if ($bot == 'Googlebot') {
			$host = $_SERVER['REMOTE_ADDR'];
			if (substr( $host, 0, 11) == '216.239.46.') $spider='Googlebot Deep Crawl';
			else if (substr( $host, 0, 7) == '64.68.8.') $spider='Google Freshbot';
		}
		session_id(0);
		break;
	}
}
if (!$spider) $bot = "";

// stop spiders from accessing certain parts of the site
$bots_not_allowed = array(
	'/reports/',
	'/includes/',
	'config',
	'clippings',
);

if ($spider) {
	foreach ($bots_not_allowed as $place) {
		if (preg_match("$".$place."$", $_SERVER['SCRIPT_NAME'])) {
			header("HTTP/1.0 403 Forbidden");
			print "Sorry, this page is not available for bots";
			exit;
		}
	}
}

date_default_timezone_set(date_default_timezone_get());
@ini_set('arg_separator.output', '&amp;');
@ini_set('error_reporting', 0);
@ini_set('display_errors', '1');
@error_reporting(0);

// NOTE: Version of Genmod
define("GM_VERSION", "2.1");
define("GM_VERSION_RELEASE", "Beta 2");
define("GM_REQUIRED_PRIVACY_VERSION", "3.3");
define("GM_REQUIRED_CONFIG_VERSION", "3.1");

// NOTE: Check for multibyte functions
if (defined("MB_CASE_TITLE")) {
	define("MB_FUNCTIONS", true);
	mb_internal_encoding("UTF-8");
}
else define("MB_FUNCTIONS", false);

if (version_compare(phpversion(), "5.3") < 0) set_magic_quotes_runtime(0);


if (!empty($_SERVER["SCRIPT_NAME"])) define("SCRIPT_NAME", basename($_SERVER["SCRIPT_NAME"]));
else if (!empty($_SERVER["PHP_SELF"])) define("SCRIPT_NAME", basename($_SERVER["PHP_SELF"]));
if (!empty($_SERVER["QUERY_STRING"])) $QUERY_STRING = $_SERVER["QUERY_STRING"];
else $QUERY_STRING="";
$QUERY_STRING = preg_replace(array("/&/","/</"), array("&amp;","&lt;"), $QUERY_STRING);
// Remove the contextual help param from the query string. It may stick.
$QUERY_STRING = preg_replace("/(&amp;)?show_context_help=(no|yes)/", "", $QUERY_STRING);
$QUERY_STRING = preg_replace("/^&amp;/", "", $QUERY_STRING);
// Do some cleanup to prevent double pages. show_changes is not allowed for bots.
if ($bot && preg_match("/(&amp;)?(show_changes=|show_full=)/", $QUERY_STRING)) {
	$QUERY_STRING = preg_replace("/(&amp;)?show_changes=(no|yes)/", "", $QUERY_STRING);
	$QUERY_STRING = preg_replace("/(&amp;)?show_full=(0|1)/", "", $QUERY_STRING);
	$QUERY_STRING = preg_replace("/^&amp;/", "", $QUERY_STRING);
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: ".basename(SCRIPT_NAME)."?".$QUERY_STRING);
	exit;
}
	
// First load the base configuration to cover missing vars
require("configbase.php");

if (isset($CONFIG_PARMS)) {
	if (isset($HTTP_HOST)) $url = $HTTP_HOST;
	else if (isset($_SERVER["HTTP_HOST"])) $url = $_SERVER["HTTP_HOST"];
	else if (isset($SERVER_NAME)) $url = $SERVER_NAME;
	else if (isset($_SERVER["SERVER_NAME"])) {
		$url = $_SERVER["SERVER_NAME"];
		if ($_SERVER["SERVER_PORT"] != 80) $url .= ":".$_SERVER["SERVER_PORT"];
	}
	if (isset($PHP_SELF) && strlen(dirname($PHP_SELF)) > 1) $url .= dirname($PHP_SELF)."/";
	else if (strlen(dirname($_SERVER["PHP_SELF"])) > 1) $url .= dirname($_SERVER["PHP_SELF"])."/";
	else $url .= "/";
	$fromurl1 = "http://".$url;
	$fromurl2 = "https://".$url;
	$found = false;
	// First check the main URL's
	foreach($CONFIG_PARMS as $key => $config) {
		// Check if the actual URL corresponds with either the server URL or the login URL set in the config.php.
		// Check for http and https.
		if (!empty($key) 
		&& ((stristr($fromurl1, $key)) 
		|| (stristr($fromurl2, $key)) 
		|| ((!empty($CONFIG_PARMS[$key]["LOGIN_URL"])) && (stristr($fromurl1, $CONFIG_PARMS[$key]["LOGIN_URL"]))) 
		|| ((!empty($CONFIG_PARMS[$key]["LOGIN_URL"])) && (stristr($fromurl2, $CONFIG_PARMS[$key]["LOGIN_URL"]))))) {
			$found = true;
			$CONFIG_SITE = $key;
			foreach($config as $var => $value) {
				define($var, $value);
			}
		break;
		}
	}
	// If not found, check the aliases
	if (!$found) {
		foreach($CONFIG_PARMS as $key => $config) {
			if (!empty($key)) {
				$aliases = explode(",", $config["SITE_ALIAS"]);
				foreach ($aliases as $akey => $alias) {
					if (!empty($alias) && (stristr($fromurl1, $alias) || stristr($fromurl2, $alias))) {
						$found = true;
						$CONFIG_SITE = $key;
						foreach($config as $var => $value) {
							if ($var != "SERVER_URL") define($var, $value);
						}
						define('SERVER_URL', $alias);
						break;
					}
				}
			}
			if ($found) break;
		}
	}
}

//-- if not configured then redirect to the configuration script
if (!defined("CONFIGURED") || CONFIGURED == false) {
	if (file_exists("install/install.php")) {
	header("Location: install/install.php");
	exit;
   }
}
//-- allow user to cancel
ignore_user_abort(false);

//-- check if they are trying to hack
$CONFIG_VARS = array();
$CONFIG_VARS[] = "GM_BASE_DIRECTORY";
$CONFIG_VARS[] = "DBHOST";
$CONFIG_VARS[] = "DBUSER";
$CONFIG_VARS[] = "DBPASS";
$CONFIG_VARS[] = "DBNAME";
$CONFIG_VARS[] = "INDEX_DIRECTORY";
$CONFIG_VARS[] = "DBPERSIST";
$CONFIG_VARS[] = "TBLPREFIX";
$CONFIG_VARS[] = "AUTHENTICATION_MODULE";
$CONFIG_VARS[] = "GM_STORE_MESSAGES";
$CONFIG_VARS[] = "GM_SIMPLE_MAIL";
$CONFIG_VARS[] = "USE_REGISTRATION_MODULE";
$CONFIG_VARS[] = "REQUIRE_ADMIN_AUTH_REGISTRATION";
$CONFIG_VARS[] = "ALLOW_USER_THEMES";
$CONFIG_VARS[] = "ALLOW_CHANGE_GEDCOM";
$CONFIG_VARS[] = "GM_SESSION_SAVE_PATH";
$CONFIG_VARS[] = "GM_SESSION_TIME";
$CONFIG_VARS[] = "SERVER_URL";
$CONFIG_VARS[] = "LOGIN_URL";
$CONFIG_VARS[] = "SITE_ALIAS";
$CONFIG_VARS[] = "MAX_VIEWS";
$CONFIG_VARS[] = "MAX_VIEW_TIME";
$CONFIG_VARS[] = "MAX_VIEW_LOGLEVEL";
$CONFIG_VARS[] = "EXCLUDE_HOSTS";
$CONFIG_VARS[] = "GM_MEMORY_LIMIT";
$CONFIG_VARS[] = "ALLOW_REMEMBER_ME";
$CONFIG_VARS[] = "CONFIG_VERSION";
$CONFIG_VARS[] = "NEWS_TYPE";
$CONFIG_VARS[] = "GM_NEWS_SERVER";
$CONFIG_VARS[] = "PROXY_ADDRESS";
$CONFIG_VARS[] = "PROXY_PORT";
$CONFIG_VARS[] = "PROXY_USER";
$CONFIG_VARS[] = "PROXY_PASSWORD";
$CONFIG_VARS[] = "DEFAULT_GEDCOMID";
$CONFIG_VARS[] = "GEDCOMS";
$CONFIG_VARS[] = "CONFIGURED";
$CONFIG_VARS[] = "LOCKOUT_TIME";
$CONFIG_VARS[] = "MANUAL_SESSION_START";
$CONFIG_VARS[] = "INTRUSION_DETECTED";
$CONFIG_VARS[] = "CONFIG_SITE";
$CONFIG_VARS[] = "MEDIA_IN_DB";

foreach($CONFIG_VARS as $indexval => $VAR) {
	$incoming = array_keys($_REQUEST);
	if (in_array($VAR, $incoming)) {
		if ((!ini_get('register_globals'))||(ini_get('register_globals')=="Off")) {
			//--load common functions
			require_once("includes/functions/functions.php");
			//-- load db specific functions
			require_once("includes/functions/functions_db.php");
			// NOTE: Setup the database connection, needed for logging
			$DBCONN = New DbLayer();
			WriteToLog("Session-&gt; Config variable override detected. Possible hacking attempt. Script terminated.", "W", "S");
			SystemFunctions::HandleIntrusion("Session-> Config variable override detected. Possible hacking attempt. Script terminated.\n");
		}
		exit;
	}
}

if (!defined('SERVER_URL')) {
	$SERVER_URL = "http://".$_SERVER["SERVER_NAME"];
	if ($_SERVER["SERVER_PORT"] != 80) $SERVER_URL .= ":".$SERVER["SERVER_PORT"];
	$SERVER_URL .= dirname(SCRIPT_NAME)."/";
	$SERVER_URL = stripslashes($SERVER_URL);
	define('SERVER_URL', $SERVER_URL);
}

//--load common functions
require_once($GM_BASE_DIRECTORY."includes/functions/functions.php");
//-- load db specific functions
require_once($GM_BASE_DIRECTORY."includes/functions/functions_db.php");
// -- load print functions
require_once($GM_BASE_DIRECTORY."includes/functions/functions_print.php");
//-- load RTL functions
require_once($GM_BASE_DIRECTORY."includes/functions/functions_rtl.php");
//-- load date functions
require_once($GM_BASE_DIRECTORY."includes/functions/functions_date.php");
//-- load sort functions
require_once($GM_BASE_DIRECTORY."includes/functions/functions_sort.php");
//-- load the extra time functions
require_once("includes/adodb-time.inc.php");

//-- set the error handler
$OLD_HANDLER = set_error_handler("GmErrorHandler");

//-- setup execution timer
$start_time = GetMicrotime();

//-- start the php session
$time = time() + GM_SESSION_TIME;
$date = date("D M j H:i:s T Y", $time);
session_set_cookie_params($date, "/");
if (GM_SESSION_TIME > 0 && function_exists('session_cache_expire')) session_cache_expire(GM_SESSION_TIME/60);
if (GM_SESSION_SAVE_PATH != "") session_save_path(GM_SESSION_SAVE_PATH);
if (isset($MANUAL_SESSION_START) && !empty($SID)) session_id($SID);
@session_start();

//-- import the post, get, and cookie variable into the scope on new versions of php
//@import_request_variables("cgp");

//-- prevent sql and code injection
foreach($_REQUEST as $key=>$value) {
	if (!is_array($value)) {
		if (preg_match("/((DELETE)|(INSERT)|(UPDATE)|(ALTER)|(CREATE)|( TABLE)|(DROP))\s[A-Za-z0-9 ]{0,200}(\s(FROM)|(INTO)|(TABLE)\s)/i", $value, $imatch) > 0 && SCRIPT_NAME != "/editlang_edit.php") {
			// NOTE: Setup the database connection, needed for logging
			$DBCONN = New DbLayer();
			WriteToLog("Session-&gt; Possible SQL injection detected: $key=>$value. <b>$imatch[0]</b> Script terminated.", "W", "S");
			SystemFunctions::HandleIntrusion("Session-> Possible SQL injection detected: $key=>$value.  <b>$imatch[0]</b> Script terminated.");
			exit;
		}
		//-- don't let any html in
		if (isset($value)) ${$key} = preg_replace(array("/</","/>/"), array("&lt;","&gt;"), $value);
	}
	else {
		foreach($value as $key1=>$val) {
			if (!is_array($val)) {
				if (preg_match("/((DELETE)|(INSERT)|(UPDATE)|(ALTER)|(CREATE)|( TABLE)|(DROP))\s[A-Za-z0-9 ]{0,200}(\s(FROM)|(INTO)|(TABLE)\s)/i", $val, $imatch)>0) {
					// NOTE: Setup the database connection
					$DBCONN = New DbLayer();
					WriteToLog("Session-&gt; Possible SQL injection detected: $key=>$val <b>$imatch[0]</b>.  Script terminated.", "W", "S");
					SystemFunctions::HandleIntrusion("Session-> Possible SQL injection detected: $key=>$val <b>$imatch[0]</b>.  Script terminated.");
					exit;
				}
				//-- don't let any html in
				if (isset($val)) ${$key}[$key1] = preg_replace(array("/</","/>/"), array("&lt;","&gt;"), $val);
			}
		}
	}
}

// NOTE: Setup the database connection
//include("db_layer.php");
$DBCONN = New DbLayer();

SystemConfig::Initialise();

@ini_set('memory_limit', SystemConfig::$GM_MEMORY_LIMIT);

// If this is an intrusion attempt, first handle it. This can update the lockout time. THEN check if locked. 
if (isset($INTRUSION_DETECTED) && $INTRUSION_DETECTED) SystemFunctions::HandleIntrusion();
SystemFunctions::CheckLockout();

// -- Read the GEDCOMS array
$GEDCOMS = array();
$DEFAULT_GEDCOMID = "";
if (!isset($GEDCOMID)) $GEDCOMID = "";
if (CONFIGURED) if ($DBCONN->connected) ReadGedcoms();
else $GEDCOMS = array();

if (empty($_REQUEST["gedid"])) {
	// Try to get the gedcom id from the session
	if (isset($_SESSION["GEDCOMID"]) && !empty($_SESSION["GEDCOMID"])) $GEDCOMID = $_SESSION["GEDCOMID"];
}
else {
	// NOTE: There is a value in the URL for GEDCOMID. Make sure it is a number
	settype($_REQUEST["gedid"], "integer");
	if (isset($GEDCOMS[$gedid])) $GEDCOMID = $_REQUEST["gedid"];
}
// We have an unknown or invalid gedcomid. Now get it otherwise
if (empty($GEDCOMID)) {
	// NOTE: There is no session Gedcom ID yet, and no ID was specified so get the default gedcom
	if (empty($GEDCOMID)) $GEDCOMID=$DEFAULT_GEDCOMID;
	// If still empty, get the first imported gedcom's id
	else if ((empty($GEDCOMID))&&(count($GEDCOMS)>0)) {
		foreach($GEDCOMS as $ged_file=>$ged_array) {
			$GEDCOMID = $ged_file;
			if (CheckForImport($ged_file)) break;
		}
	}
}

$_SESSION["GEDCOMID"] = $GEDCOMID;

// NOTE: This is to suppress the closing div tag in the footer
$without_close = true;

if (CONFIGURED) if ($DBCONN->connected) GedcomConfig::ReadGedcomConfig($GEDCOMID);

//-- load file for language settings
require_once(SystemConfig::$GM_BASE_DIRECTORY . "includes/values/lang_settings_std.php");
$Languages_Default = true;
$ConfiguredSettings = LanguageFunctions::LoadLangVars();
if (count($ConfiguredSettings) > 0) {
	$DefaultSettings = $language_settings;		// Save default settings, so we can merge properly
	$language_settings = array_merge($DefaultSettings, $ConfiguredSettings);	// Copy new langs into config
	unset($DefaultSettings);
	unset($ConfiguredSettings);		// We don't need these any more
	$Languages_Default = false;
}
	
/** Re-build the various language-related arrays
 *		Note:
 *		This code existed in both lang_settings_std.php and in lang_settings.php.
 *		It has been removed from both files and inserted here, where it belongs.
 */
$languages 				= array();
$gm_lang_use 			= array();
$lang_short_cut 		= array();
$lang_langcode 			= array();
$gm_language 			= array();
$confighelpfile 		= array();
$helptextfile 			= array();
$flagsfile 				= array();
$factsfile 				= array();
$factsarray 			= array();
$gm_lang_name 			= array();
$langcode				= array();
$ALPHABET_upper			= array();
$ALPHABET_lower			= array();
$DATE_FORMAT_array		= array();
$TIME_FORMAT_array		= array();
$WEEK_START_array		= array();
$TEXT_DIRECTION_array	= array();
$NAME_REVERSE_array		= array();
$MON_SHORT_array		= array();

foreach ($language_settings as $key => $value) {
	$languages[$key] 			= $value["gm_langname"];
	$gm_lang_use[$key]			= $value["gm_lang_use"];
	define("GM_LANG_".$key, $value["gm_lang"]);
	$lang_short_cut[$key]		= $value["lang_short_cut"];
	$lang_langcode[$key]		= $value["langcode"];
	$gm_language[$key]			= $value["gm_language"];
	$confighelpfile[$key]		= $value["confighelpfile"];
	$helptextfile[$key]			= $value["helptextfile"];
	$flagsfile[$key]			= $value["flagsfile"];
	$factsfile[$key]			= $value["factsfile"];
	$ALPHABET_upper[$key]		= $value["ALPHABET_upper"];
	$ALPHABET_lower[$key]		= $value["ALPHABET_lower"];
	$DATE_FORMAT_array[$key]	= $value["DATE_FORMAT"];
	$TIME_FORMAT_array[$key]	= $value["TIME_FORMAT"];;
	$WEEK_START_array[$key]		= $value["WEEK_START"];
	$TEXT_DIRECTION_array[$key]	= $value["TEXT_DIRECTION"];
	$NAME_REVERSE_array[$key]	= $value["NAME_REVERSE"];
	$MON_SHORT_array[$key]		= $value["MON_SHORT"];
	
//	define("GM_LANG_lang_name_".$key, $value["gm_lang"]);
	
	$dDummy = $value["langcode"];
	$ct = strpos($dDummy, ";");
	while ($ct > 1) {
		$shrtcut = substr($dDummy,0,$ct);
		$dDummy = substr($dDummy,$ct+1);
		$langcode[strtolower($shrtcut)]		= $key;
		$ct = strpos($dDummy, ";");
	}
}
asort($gm_language);	

/**
 * The following business rules are used to choose currently active language
 * 1. If the user has chosen a language from the list or the flags, use their choice.
 * 2. When the user logs in, switch to the language in their user profile
 * 3. Use the language in visitor's browser settings if it is supported in the GM site.  
 *    If it is not supported, use the gedcom configuration setting.
 * 4. When a user logs out their current language choice is ignored and the site will
 *    revert back to the language they first saw when arriving at the site according to
 *    rule 3. 
 */
if ($spider) {
	GedcomConfig::$ENABLE_MULTI_LANGUAGE = false;
}
if (!empty($logout) && $logout == 1) unset($_SESSION["CLANGUAGE"]);		// user is about to log out
else {
	if ((GedcomConfig::$ENABLE_MULTI_LANGUAGE)&&(empty($_SESSION["CLANGUAGE"]))) {
		// If visitor language is forced, set it
		if (SystemConfig::$VISITOR_LANG != "Genmod" && isset($gm_lang_use[SystemConfig::$VISITOR_LANG])) {
			$LANGUAGE = SystemConfig::$VISITOR_LANG;
		}
		else {
			// get the language from the browser
			if (isset($HTTP_ACCEPT_LANGUAGE)) $accept_langs = $HTTP_ACCEPT_LANGUAGE;
			else if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) $accept_langs = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
   			if (isset($accept_langs)) {
	   			$accept_langs = strtolower($accept_langs);
      			if (strstr($accept_langs, ",")) {
         			$langs_array = preg_split("/(,\s*)|(;\s*)/", $accept_langs);
        			for ($i=0; $i<count($langs_array); $i++) {
	        			// First see if the full code is found (like nl-nl)
            			if (!empty($langcode[$langs_array[$i]])) {
               				$LANGUAGE = $langcode[$langs_array[$i]];
               				break;
	            		}
	            		// Then try the partial code (like nl)
		      			if (strstr($langs_array[$i], "-")) {
		            		$parts = preg_split("/-/", $langs_array[$i]);
		            		if (isset ($parts[0]) && !empty($langcode[$parts[0]])) {
        	       				$LANGUAGE = $langcode[$parts[0]];
            	   				break;
        	   				}
	            		}
    	     		}
      			}
      			else {
        			// First see if the full code is found (like nl-nl)
         			if (!empty($langcode[$accept_langs])) $LANGUAGE = $langcode[$accept_langs];
         			else {
		      			if (strstr($accept_langs, "-")) {
		            		$parts = preg_split("/-/", $accept_langs);
		        			for ($i=0; $i<count($parts); $i++) {
			            		if (isset ($langcode[$parts[$i]]) && !empty($langcode[$parts[0]])) {
    		           				$LANGUAGE = $langcode[$parts[$i]];
        		       				break;
    	       					}
	       					}
	            		}
         			}
      			}
  			}
   		}
	}
}

// Now the default language is set, or not.
// Get the language that is stored in the session
if (!empty($_SESSION['CLANGUAGE'])) $CLANGUAGE = $_SESSION['CLANGUAGE'];
else if (!empty($HTTP_SESSION_VARS['CLANGUAGE'])) $CLANGUAGE = $HTTP_SESSION_VARS['CLANGUAGE'];
if (!empty($CLANGUAGE) && !$spider) {
   $LANGUAGE = $CLANGUAGE;
}

// If we still don't know the language, set it to the gedcom language. If no gedcom is known, it will default to default gedcom settings.
if (!isset($LANGUAGE)) {
	$LANGUAGE = GedcomConfig::$GEDCOMLANG;
}

if (GedcomConfig::$ENABLE_MULTI_LANGUAGE) {
	if ((isset($changelanguage))&&($changelanguage=="yes")) {
		if (!empty($NEWLANGUAGE) && isset($gm_language[$NEWLANGUAGE])) {
			$LANGUAGE=$NEWLANGUAGE;
			unset($_SESSION["upcoming_events"]);
			unset($_SESSION["todays_events"]);
		}
	}
}

// if ($spider) WriteToLog("Spider ".$spider." set language to ".$LANGUAGE, "I", "S");

// Get the username
if (!isset($_SESSION["cookie_login"])) $_SESSION["cookie_login"] = false;
$gm_username = UserController::GetUserName();

$gm_user =& User::GetInstance($gm_username);
// Only show changes for authenticated users with edit rights
if ($gm_user->userCanEdit()) {
	// setting in the query string may overrule the session setting
	if (!isset($show_changes)) {
		if (isset($_SESSION["show_changes"])) {
			$show_changes = $_SESSION["show_changes"];
		}
		else {
			$_SESSION["show_changes"] = true;
			$show_changes = true;
		}
		// Force show_changes off if no changes are present. This will save some queries
		if (!ChangeFunctions::GetChangeData(true, "", true)) $show_changes = false;
	}
	else $_SESSION["show_changes"] = $show_changes;
}
else $show_changes = false;

// NOTE: Load English as the default language
LanguageFunctions::LoadEnglish(false, false, true);

//NOTE: Load factsfile
LanguageFunctions::LoadEnglishFacts(false, true);

// Check for page views exceeding the limit
SystemFunctions::CheckPageViews();

// Check for the IP address where the request comes from
SystemFunctions::CheckSessionIP();

require_once(SystemConfig::$GM_BASE_DIRECTORY . "includes/values/templecodes.php");		//-- load in the LDS temple code translations

//-- load the privacy settings
PrivacyController::ReadPrivacy($GEDCOMID);

if (!defined("SCRIPT_NAME")) define("SCRIPT_NAME", $_SERVER["SCRIPT_NAME"]);

if (empty($TEXT_DIRECTION)) $TEXT_DIRECTION="ltr";
$TEXT_DIRECTION = $TEXT_DIRECTION_array[$LANGUAGE];
$DATE_FORMAT	= $DATE_FORMAT_array[$LANGUAGE];
$TIME_FORMAT	= $TIME_FORMAT_array[$LANGUAGE];
$WEEK_START		= $WEEK_START_array[$LANGUAGE];
$NAME_REVERSE	= $NAME_REVERSE_array[$LANGUAGE];
$MON_SHORT		= $MON_SHORT_array[$LANGUAGE];

$monthtonum = array();
$monthtonum["jan"] = 1;
$monthtonum["feb"] = 2;
$monthtonum["mar"] = 3;
$monthtonum["apr"] = 4;
$monthtonum["may"] = 5;
$monthtonum["jun"] = 6;
$monthtonum["jul"] = 7;
$monthtonum["aug"] = 8;
$monthtonum["sep"] = 9;
$monthtonum["oct"] = 10;
$monthtonum["nov"] = 11;
$monthtonum["dec"] = 12;
$monthtonum["tsh"] = 1;
$monthtonum["csh"] = 2;
$monthtonum["ksl"] = 3;
$monthtonum["tvt"] = 4;
$monthtonum["shv"] = 5;
$monthtonum["adr"] = 6;
$monthtonum["ads"] = 7;
$monthtonum["nsn"] = 8;
$monthtonum["iyr"] = 9;
$monthtonum["svn"] = 10;
$monthtonum["tmz"] = 11;
$monthtonum["aav"] = 12;
$monthtonum["ell"] = 13;

if (!isset($show_context_help)) $show_context_help = "";
if (!isset($_SESSION["show_context_help"])) $_SESSION["show_context_help"] = GedcomConfig::$SHOW_CONTEXT_HELP;
if ($show_context_help === "yes") $_SESSION["show_context_help"] = true;
else if ($show_context_help === "no") $_SESSION["show_context_help"] = false;
if (!isset($_SESSION["gm_user"])) $_SESSION["gm_user"] = "";
if (!isset($_SESSION["cookie_login"])) $_SESSION["cookie_login"] = false;

if ((strstr(SCRIPT_NAME, "editconfig.php")===false) &&(strstr(SCRIPT_NAME, "editconfig_help.php")===false)) {
	if ((!$DBCONN->connected)||(!UserController::AdminUserExists())) {
		header("Location: editconfig.php");
		exit;
	}
	if ((strstr(SCRIPT_NAME, "editconfig_gedcom.php")===false)
	&&(strstr(SCRIPT_NAME, "addmedia.php")===false)
	&&(strstr(SCRIPT_NAME, "addnewgedcom.php")===false)
	&&(strstr(SCRIPT_NAME, "admin.php")===false)
	&&(strstr(SCRIPT_NAME, "admin_maint.php")===false)
	&&(strstr(SCRIPT_NAME, "backup.php")===false)
	&&(strstr(SCRIPT_NAME, "config_download.php")===false)
	&&(strstr(SCRIPT_NAME, "config_maint.php")===false)
	&&(strstr(SCRIPT_NAME, "edit_privacy.php")===false)
	&&(strstr(SCRIPT_NAME, "editconfig_help.php")===false)
	&&(strstr(SCRIPT_NAME, "editgedcoms.php")===false)
	&&(strstr(SCRIPT_NAME, "gmrpc.php")===false)
	&&(strstr(SCRIPT_NAME, "help_text.php")===false)
	&&(strstr(SCRIPT_NAME, "importgedcom.php")===false)
	&&(strstr(SCRIPT_NAME, "lockout_maint.php")===false)
	&&(strstr(SCRIPT_NAME, "login.php")===false)
	&&(strstr(SCRIPT_NAME, "sanity.php")===false)
	&&(strstr(SCRIPT_NAME, "uploadgedcom.php")===false)
	&&(strstr(SCRIPT_NAME, "useradmin.php")===false)
	&&(strstr(SCRIPT_NAME, "validategedcom.php")===false)
	&&(strstr(SCRIPT_NAME, "viewlog.php")===false)) {
		if ((count($GEDCOMS) ==0 || !CheckForImport($GEDCOMID)) && empty($logout)) {
			header("Location: editgedcoms.php");
			exit;
		}
	}
	
	//-----------------------------------
	//-- if user wishes to logout this is where we will do it
	if ((!empty($logout))&&($logout==1)) {
		UserController::UserLogout($gm_username);
		$Action = "";
		$gm_user =& User::GetInstance($gm_username);
		if (GedcomConfig::$MUST_AUTHENTICATE) {
			header("Location: ".GedcomConfig::$HOME_SITE_URL);
			exit;
		}
		else {
			if (count($GEDCOMS)==0) {
				if (LOGIN_URL == "") header("Location: login.php");
				else header("Location: ".LOGIN_URL);
				exit;
			}
		}
	}
	
	if (GedcomConfig::$MUST_AUTHENTICATE) {
		if (empty($gm_username)) {
			if ((strstr(SCRIPT_NAME, "login.php")===false)
				&&(strstr(SCRIPT_NAME, "login_register.php")===false)
				&&(strstr(SCRIPT_NAME, "help_text.php")===false)
				&&(strstr(SCRIPT_NAME, "message.php")===false)) {
				$url = basename($_SERVER["SCRIPT_NAME"])."?".$QUERY_STRING;
				if (stristr($url, "index.php")!==false) {
					if (stristr($url, "command=")===false) {
						if ((!isset($_SERVER['HTTP_REFERER'])) || (stristr($_SERVER['HTTP_REFERER'],SERVER_URL)===false)) $url .= "&command=gedcom";
					}
				}
				if (stristr($url, "gedid=")===false)  {
					$url.="&gedid=".GedcomConfig::$GEDCOMID;
				}
				if (LOGIN_URL == "") header("Location: login.php?url=".urlencode($url));
				else header("Location: ".LOGIN_URL."?url=".urlencode($url));
				exit;
			}
		}
	}

	$_SESSION['CLANGUAGE'] = $LANGUAGE;
	if (!isset($_SESSION["timediff"])) {
		$_SESSION["timediff"] = 0;
	}
   
}

//-- load the user specific theme
if ((!empty($gm_username))&&(!isset($logout))) {
	$usertheme = $gm_user->theme;
	if ((!empty($_POST["user_theme"]))&&(!empty($_POST["oldusername"]))&&($_POST["oldusername"]==$gm_username)) $usertheme = $_POST["user_theme"];
	if ((!empty($usertheme)) && (file_exists($usertheme."theme.php")))  {
		GedcomConfig::$THEME_DIR = $usertheme;
	}
}

if (isset($_SESSION["theme_dir"])) {
	GedcomConfig::$THEME_DIR = $_SESSION["theme_dir"];
	if (!empty($gm_username)) {
		if ($gm_user->editaccount) unset($_SESSION["theme_dir"]);
	}
}

if (empty(GedcomConfig::$THEME_DIR)) GedcomConfig::$THEME_DIR="standard/";
if (file_exists(SystemConfig::$GM_BASE_DIRECTORY.GedcomConfig::$THEME_DIR."theme.php")) require_once(SystemConfig::$GM_BASE_DIRECTORY.GedcomConfig::$THEME_DIR."theme.php");
else {
	GedcomConfig::$THEME_DIR = SystemConfig::$GM_BASE_DIRECTORY."themes/standard/";
	require_once(GedcomConfig::$THEME_DIR."theme.php");
}

if (GedcomConfig::$SHOW_COUNTER) $hits = CounterFunctions::GetCounter(); //--load the hit counter

if ($Languages_Default) {					// If Languages not yet configured
	$gm_lang_use["english"] = false;		//   disable English
	$gm_lang_use["$LANGUAGE"] = true;		//     and enable according to Browser pref.
	$language_settings["english"]["gm_lang_use"] = false;
	$language_settings["$LANGUAGE"]["gm_lang_use"] = true;
}

// NOTE: Update the user sessiontime since the user is not sleeping
// NOTE: This will keep the user logged in while being busy
UserController::UpdateSessiontime($gm_username);

// NOTE: Check every 15 minutes if there are users who are idle
// NOTE: Any user passing the GM_SESSION_TIME will be logged out
if (!isset($_SESSION["check_login"])) $_SESSION["check_login"] = time();
if ((time() - $_SESSION["check_login"]) > 900) {
	$users = UserController::GetUsers("username", "asc", "firstname", "u_loggedin='Y'");
	foreach($users as $indexval => $user) {
		if (time() - $user->sessiontime > GM_SESSION_TIME) UserController::UserLogout($user->username, "Session expired");
	}
	$_SESSION["check_login"] = time();
}

// Check for the presence of Greybox
define('USE_GREYBOX', is_dir("modules/greybox/"));

// Define the function to autoload the classes
function __autoload($classname) {
	global $GM_BASE_DIRECTORY;
	
	if (stristr($classname, "controller")) {
		require_once($GM_BASE_DIRECTORY.strtolower("includes/controllers/".str_ireplace("controller", "", $classname)."_ctrl.php"));
		if (DEBUG) Debugcollector::OutputCollector("Loaded controller class: ".$classname, "autoload");
	}
	else if (stristr($classname, "functions")) {
		require_once($GM_BASE_DIRECTORY.strtolower("includes/functions/functions_".str_ireplace("functions", "", $classname)."_class.php"));
		if (DEBUG) Debugcollector::OutputCollector("Loaded function class: ".$classname, "autoload");
	}
	else {
		require_once($GM_BASE_DIRECTORY.strtolower("includes/classes/".$classname."_class.php"));
		if (DEBUG) Debugcollector::OutputCollector("Loaded data class: ".$classname, "autoload");
	}
}
?>