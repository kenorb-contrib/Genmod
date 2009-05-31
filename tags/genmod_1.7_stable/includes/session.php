<?php
/**
 * Startup and session logic
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
 * @version $Id: session.php,v 1.37 2006/05/28 13:00:03 roland-d Exp $
 */
if (strstr($_SERVER["SCRIPT_NAME"],"session")) {
	print "Now, why would you want to do that.  You're not hacking are you?";
	exit;
}

$ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "";

// check for worms and bad bots
$worms = array (
	'LWP::Simple',
	'lwp-trivial',
	'HTTrack'
);

foreach( $worms as $worm ) {
	if (eregi($worm, $ua)) {
		print "Bad Worm! Crawl back into your hole.";
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
	if (eregi($bot, $ua)) {
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

// stop spiders from accessing certain parts of the site
$bots_not_allowed = array(
	'/reports/',
	'/includes/',
	'config',
	'clippings',
);
if ($spider) {
	foreach ($bots_not_allowed as $place) {
		if (eregi($place, $_SERVER['SCRIPT_NAME'])) {
			header("HTTP/1.0 403 Forbidden");
			print "Sorry, this page is not available for bots";
			exit;
		}
	}
}

@ini_set('arg_separator.output', '&amp;');
@ini_set('error_reporting', 0);
@ini_set('display_errors', '1');
@error_reporting(0);

// NOTE: Version of Genmod
$VERSION = "1.0";
$VERSION_RELEASE = "beta 3";
$REQUIRED_PRIVACY_VERSION = "3.1";
$REQUIRED_CONFIG_VERSION = "3.1";

set_magic_quotes_runtime(0);

if (!empty($_SERVER["SCRIPT_NAME"])) $SCRIPT_NAME=$_SERVER["SCRIPT_NAME"];
else if (!empty($_SERVER["PHP_SELF"])) $SCRIPT_NAME=$_SERVER["PHP_SELF"];
if (!empty($_SERVER["QUERY_STRING"])) $QUERY_STRING = $_SERVER["QUERY_STRING"];
else $QUERY_STRING="";
$QUERY_STRING = preg_replace(array("/&/","/</"), array("&amp;","&lt;"), $QUERY_STRING);
$QUERY_STRING = preg_replace("/show_context_help=(no|yes)/", "", $QUERY_STRING);

// First load the base configuration to cover missing vars
require("configbase.php");
if (isset($CONFIG_PARMS)) {
	if (isset($HTTP_HOST)) $url = $HTTP_HOST;
	else if (isset($_SERVER["HTTP_HOST"])) $url = $_SERVER["HTTP_HOST"];
	else if (isset($SERVER_NAME)) $url = $SERVER_NAME;
	else if (isset($_SERVER["SERVER_NAME"])) $url = $_SERVER["SERVER_NAME"];
	if (isset($PHP_SELF) && strlen(dirname($PHP_SELF)) > 1) $url .= dirname($PHP_SELF)."/";
	else if (strlen(dirname($_SERVER["PHP_SELF"])) > 1) $url .= dirname($_SERVER["PHP_SELF"])."/";
	else $url .= "/";
	$fromurl1 = "http://".$url;
	$fromurl2 = "https://".$url;
	foreach($CONFIG_PARMS as $key => $config) {
		// Check if the actual URL corresponds with either the server URL or the login URL set in the config.php.
		// Check for http and https.
		if (!empty($key) 
		&& ((stristr($fromurl1, $key)) 
		|| (stristr($fromurl2, $key)) 
		|| ((!empty($CONFIG_PARMS["LOGIN_URL"])) && (stristr($fromurl1, $CONFIG_PARMS[$key]["LOGIN_URL"]))) 
		|| ((!empty($CONFIG_PARMS["LOGIN_URL"])) && (stristr($fromurl2, $CONFIG_PARMS[$key]["LOGIN_URL"]))))) {
			foreach($config as $var => $value) {
				$$var = $value;
			}
			break;
		}
	}
}

//-- if not configured then redirect to the configuration script
if (!$CONFIGURED) {
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
$CONFIG_VARS[] = "GM_DATABASE";
$CONFIG_VARS[] = "DBTYPE";
$CONFIG_VARS[] = "DBHOST";
$CONFIG_VARS[] = "DBUSER";
$CONFIG_VARS[] = "DBPASS";
$CONFIG_VARS[] = "DBNAME";
$CONFIG_VARS[] = "TBLPREFIX";
$CONFIG_VARS[] = "INDEX_DIRECTORY";
$CONFIG_VARS[] = "AUTHENTICATION_MODULE";
$CONFIG_VARS[] = "USE_REGISTRATION_MODULE";
$CONFIG_VARS[] = "ALLOW_USER_THEMES";
$CONFIG_VARS[] = "ALLOW_REMEMBER_ME";
$CONFIG_VARS[] = "DEFAULT_GEDCOM";
$CONFIG_VARS[] = "ALLOW_CHANGE_GEDCOM";
$CONFIG_VARS[] = "GM_SESSION_SAVE_PATH";
$CONFIG_VARS[] = "GM_SESSION_TIME";
$CONFIG_VARS[] = "GEDCOMS";
$CONFIG_VARS[] = "SERVER_URL";
$CONFIG_VARS[] = "LOGIN_URL";
$CONFIG_VARS[] = "GM_MEMORY_LIMIT";
$CONFIG_VARS[] = "GM_STORE_MESSAGES";
$CONFIG_VARS[] = "GM_SIMPLE_MAIL";
$CONFIG_VARS[] = "CONFIG_VERSION";
$CONFIG_VARS[] = "CONFIGURED";
$CONFIG_VARS[] = "MANUAL_SESSION_START";

foreach($CONFIG_VARS as $indexval => $VAR) {
	$incoming = array_keys($_REQUEST);
	if (in_array($VAR, $incoming)) {
		print "Config variable override detected. Possible hacking attempt. Script terminated.\n";
		if ((!ini_get('register_globals'))||(ini_get('register_globals')=="Off")) {
			//--load common functions
			require_once("includes/functions.php");
			//-- load db specific functions
			require_once("includes/functions_db.php");
			require_once("includes/authentication.php");      // -- load the authentication system
			WriteToLog("Config variable override detected. Possible hacking attempt. Script terminated.", "E", "S");
		}
		exit;
	}
}

if (empty($CONFIG_VERSION)) $CONFIG_VERSION = "2.65";
if (empty($SERVER_URL)) $SERVER_URL = stripslashes("http://".$_SERVER["SERVER_NAME"].dirname($SCRIPT_NAME)."/");
if (!isset($ALLOW_REMEMBER_ME)) $ALLOW_REMEMBER_ME = true;
if (!isset($GM_SIMPLE_MAIL)) $GM_SIMPLE_MAIL = false;

if (empty($GM_MEMORY_LIMIT)) $GM_MEMORY_LIMIT = "32M";
@ini_set('memory_limit', $GM_MEMORY_LIMIT);

//--load common functions
require_once($GM_BASE_DIRECTORY."includes/functions.php");
require_once($GM_BASE_DIRECTORY."includes/menu.php");
//-- load db specific functions
require_once($GM_BASE_DIRECTORY."includes/functions_db.php");
// -- load print functions
require_once($GM_BASE_DIRECTORY."includes/functions_print.php");
//-- load RTL functions
require_once($GM_BASE_DIRECTORY."includes/functions_rtl.php");
//-- load date functions
require_once($GM_BASE_DIRECTORY."includes/functions_date.php");

//-- set the error handler
$OLD_HANDLER = set_error_handler("gm_error_handler");

//-- setup execution timer
$start_time = getmicrotime();

//-- Setup array of media types
$MEDIATYPE = array("a11","acb","adc","adf","afm","ai","aiff","aif","amg","anm","ans","apd","asf","au","avi","awm","bga","bmp","bob","bpt","bw","cal","cel","cdr","cgm","cmp","cmv","cmx","cpi","cur","cut","cvs","cwk","dcs","dib","dmf","dng","doc","dsm","dxf","dwg","emf","enc","eps","fac","fax","fit","fla","flc","fli","fpx","ftk","ged","gif","gmf","hdf","iax","ica","icb","ico","idw","iff","img","jbg","jbig","jfif","jpe","jpeg","jp2","jpg","jtf","jtp","lwf","mac","mid","midi","miff","mki","mmm",".mod","mov","mp2","mp3","mpg","mpt","msk","msp","mus","mvi","nap","ogg","pal","pbm","pcc","pcd","pcf","pct","pcx","pdd","pdf","pfr","pgm","pic","pict","pk","pm3","pm4","pm5","png","ppm","ppt","ps","psd","psp","pxr","qt","qxd","ras","rgb","rgba","rif","rip","rla","rle","rpf","rtf","scr","sdc","sdd","sdw","sgi","sid","sng","swf","tga","tiff","tif","txt","text","tub","ul","vda","vis","vob","vpg","vst","wav","wdb","win","wk1","wks","wmf","wmv","wpd","wxf","wp4","wp5","wp6","wpg","wpp","xbm","xls","xpm","xwd","yuv","zgm");

//-- start the php session
$time = time()+$GM_SESSION_TIME;
$date = date("D M j H:i:s T Y", $time);
session_set_cookie_params($date, "/");
if (($GM_SESSION_TIME>0)&&(function_exists('session_cache_expire'))) session_cache_expire($GM_SESSION_TIME/60);
if (!empty($GM_SESSION_SAVE_PATH)) session_save_path($GM_SESSION_SAVE_PATH);
if (isset($MANUAL_SESSION_START) && !empty($SID)) session_id($SID);
@session_start();
//-- import the post, get, and cookie variable into the scope on new versions of php
if (phpversion() >= '4.1') {
	@import_request_variables("cgp");
}
if (phpversion() > '4.2.2') {
	//-- prevent sql and code injection
	foreach($_REQUEST as $key=>$value) {
		if (!is_array($value)) {
			if (preg_match("/((DELETE)|(INSERT)|(UPDATE)|(ALTER)|(CREATE)|( TABLE)|(DROP))\s[A-Za-z0-9 ]{0,200}(\s(FROM)|(INTO)|(TABLE)\s)/i", $value, $imatch)>0) {
				print "Possible SQL injection detected: $key=>$value.  <b>$imatch[0]</b> Script terminated.";
				require_once("includes/authentication.php");      // -- load the authentication system
				WriteToLog("Possible SQL injection detected: $key=>$value. <b>$imatch[0]</b> Script terminated.", "E", "S");
				exit;
			}
			//-- don't let any html in
			if (!empty($value)) ${$key} = preg_replace(array("/</","/>/"), array("&lt;","&gt;"), $value);
		}
		else {
			foreach($value as $key1=>$val) {
				if (!is_array($val)) {
					if (preg_match("/((DELETE)|(INSERT)|(UPDATE)|(ALTER)|(CREATE)|( TABLE)|(DROP))\s[A-Za-z0-9 ]{0,200}(\s(FROM)|(INTO)|(TABLE)\s)/i", $val, $imatch)>0) {
						print "Possible SQL injection detected: $key=>$val <b>$imatch[0]</b>.  Script terminated.";
						require_once("includes/authentication.php");      // -- load the authentication system
						WriteToLog("Possible SQL injection detected: $key=>$val <b>$imatch[0]</b>.  Script terminated.", "E", "S");
						exit;
					}
					//-- don't let any html in
					if (!empty($val)) ${$key}[$key1] = preg_replace(array("/</","/>/"), array("&lt;","&gt;"), $val);
				}
			}
		}
	}
}

// -- Read the GEDCOMS array
if ($CONFIGURED) {
	if (check_db()) {
		ReadGedcoms();
	}
}
else $GEDCOMS = array();

if (isset($_REQUEST["GEDCOM"])){
   $_REQUEST["GEDCOM"] = trim($_REQUEST["GEDCOM"]);
}
if (!isset($DEFAULT_GEDCOM)) $DEFAULT_GEDCOM = "";
if (empty($_REQUEST["GEDCOM"])) {
   if (isset($_SESSION["GEDCOM"]) && !empty($_SESSION["GEDCOM"])) $GEDCOM = $_SESSION["GEDCOM"];
   else {
      if ((empty($GEDCOM))||(empty($GEDCOMS[$GEDCOM]))) $GEDCOM=$DEFAULT_GEDCOM;
      else if ((empty($GEDCOM))&&(count($GEDCOMS)>0)) {
	      check_db();
         foreach($GEDCOMS as $ged_file=>$ged_array) {
	         $GEDCOM = $ged_file;
	         if (check_for_import($ged_file)) break;
         }
      }
   }
}
else {
	$GEDCOM = $_REQUEST["GEDCOM"];
}
if (isset($_REQUEST["ged"]) && !empty($_REQUEST["ged"])) {
	$GEDCOM = trim($_REQUEST["ged"]);
}
if (is_int($GEDCOM)) $GEDCOM = get_gedcom_from_id($GEDCOM);
$_SESSION["GEDCOM"] = $GEDCOM;
$INDILIST_RETRIEVED = false;
$FAMLIST_RETRIEVED = false;

require_once($GM_BASE_DIRECTORY."config_gedcom.php");

if ($CONFIGURED) {
	if (check_db()) {
		ReadGedcomConfig($GEDCOM);
	}
}

require_once($GM_BASE_DIRECTORY."includes/functions_name.php");

require_once($GM_BASE_DIRECTORY."includes/authentication.php");      // -- load the authentication system

//-- load media specific functions
if ($MULTI_MEDIA) require_once($GM_BASE_DIRECTORY."includes/functions_mediadb.php");

if (empty($PEDIGREE_GENERATIONS)) $PEDIGREE_GENERATIONS = $DEFAULT_PEDIGREE_GENERATIONS;

//-- load file for language settings
require_once($GM_BASE_DIRECTORY . "includes/lang_settings_std.php");
$Languages_Default = true;
if (file_exists($INDEX_DIRECTORY . "lang_settings.php")) {
	$DefaultSettings = $language_settings;		// Save default settings, so we can merge properly
	require_once($INDEX_DIRECTORY . "lang_settings.php");
	$ConfiguredSettings = $language_settings;	// Save configured settings, same reason
	$language_settings = array_merge($DefaultSettings, $ConfiguredSettings);	// Copy new langs into config
	unset($DefaultSettings);
	unset($ConfiguredSettings);		// We don't need these any more
	$Languages_Default = false;
}
	
/* Re-build the various language-related arrays
 *		Note:
 *		This code existed in both lang_settings_std.php and in lang_settings.php.
 *		It has been removed from both files and inserted here, where it belongs.
 */
$languages 			= array();
$gm_lang_use 			= array();
$gm_lang 				= array();
$lang_short_cut 		= array();
$lang_langcode 		= array();
$gm_language 			= array();
$confighelpfile 		= array();
$helptextfile 			= array();
$flagsfile 			= array();
$factsfile 			= array();
$factsarray 			= array();
$gm_lang_name 			= array();
$langcode				= array();
$ALPHABET_upper		= array();
$ALPHABET_lower		= array();
$DATE_FORMAT_array		= array();
$TIME_FORMAT_array		= array();
$WEEK_START_array		= array();
$TEXT_DIRECTION_array	= array();
$NAME_REVERSE_array		= array();

foreach ($language_settings as $key => $value) {
	$languages[$key] 			= $value["gm_langname"];
	$gm_lang_use[$key]			= $value["gm_lang_use"];
	$gm_lang[$key]				= $value["gm_lang"];
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
	
	$gm_lang["lang_name_$key"]	= $value["gm_lang"];
	
	$dDummy = $value["langcode"];
	$ct = strpos($dDummy, ";");
	while ($ct > 1) {
		$shrtcut = substr($dDummy,0,$ct);
		$dDummy = substr($dDummy,$ct+1);
		$langcode[$shrtcut]		= $key;
		$ct = strpos($dDummy, ";");
	}
}
	

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
if ((!empty($logout))&&($logout==1)) unset($_SESSION["CLANGUAGE"]);		// user is about to log out
else if (($ENABLE_MULTI_LANGUAGE)&&(empty($_SESSION["CLANGUAGE"]))) {
   if (isset($HTTP_ACCEPT_LANGUAGE)) $accept_langs = $HTTP_ACCEPT_LANGUAGE;
   else if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) $accept_langs = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
   if (isset($accept_langs)) {
      if (strstr($accept_langs, ",")) {
         $langs_array = preg_split("/(,\s*)|(;\s*)/", $accept_langs);
        for ($i=0; $i<count($langs_array); $i++) {
            if (!empty($langcode[$langs_array[$i]])) {
               $LANGUAGE = $langcode[$langs_array[$i]];
               break;
            }
         }
      }
      else {
         if (!empty($langcode[$accept_langs])) $LANGUAGE = $langcode[$accept_langs];
      }
   }
}
$deflang = $LANGUAGE;

if (!empty($_SESSION['CLANGUAGE'])) $CLANGUAGE = $_SESSION['CLANGUAGE'];
else if (!empty($HTTP_SESSION_VARS['CLANGUAGE'])) $CLANGUAGE = $HTTP_SESSION_VARS['CLANGUAGE'];
if (!empty($CLANGUAGE)) {
   $LANGUAGE = $CLANGUAGE;
}

if ($ENABLE_MULTI_LANGUAGE) {
	if ((isset($changelanguage))&&($changelanguage=="yes")) {
		if (!empty($NEWLANGUAGE) && isset($gm_language[$NEWLANGUAGE])) {
			$LANGUAGE=$NEWLANGUAGE;
			unset($_SESSION["upcoming_events"]);
			unset($_SESSION["todays_events"]);
		}
	}
}

// Get the username
$gm_username = getUserName();

// NOTE: Load English as the default language
loadEnglish();

// NOTE: Load the user chosen language
loadLanguage($LANGUAGE);

// Check for page views exceeding the limit
CheckPageViews();

// Check for the IP address where the request comes from
if (isset($_SESSION['gm_user']) && !empty($_SESSION['gm_user'])) {
	if (isset($_SESSION['IP']) && !empty($_SESSION['IP'])) {
		if ($_SESSION['IP'] != $_SERVER['REMOTE_ADDR']) {
			$string = "Intrusion detected on session for IP ".$_SESSION['IP']." by ".$_SERVER['REMOTE_ADDR'];
			WriteToLog($string, "E", "S");
			print $string;
			exit;
		}
	}
}

require_once($GM_BASE_DIRECTORY . "includes/templecodes.php");		//-- load in the LDS temple code translations

require_once("privacy.php");
//-- load the privacy file
if ($CONFIGURED) if (check_db()) ReadPrivacy($GEDCOM);

//-- load the privacy functions
require_once($GM_BASE_DIRECTORY."includes/functions_privacy.php");

if (!isset($SCRIPT_NAME)) $SCRIPT_NAME=$_SERVER["SCRIPT_NAME"];

if (empty($TEXT_DIRECTION)) $TEXT_DIRECTION="ltr";
$TEXT_DIRECTION = $TEXT_DIRECTION_array[$LANGUAGE];
$DATE_FORMAT	= $DATE_FORMAT_array[$LANGUAGE];
$TIME_FORMAT	= $TIME_FORMAT_array[$LANGUAGE];
$WEEK_START	= $WEEK_START_array[$LANGUAGE];
$NAME_REVERSE	= $NAME_REVERSE_array[$LANGUAGE];

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
if (!isset($_SESSION["show_context_help"])) $_SESSION["show_context_help"] = $SHOW_CONTEXT_HELP;
if (!isset($_SESSION["gm_user"])) $_SESSION["gm_user"] = "";
if (!isset($_SESSION["cookie_login"])) $_SESSION["cookie_login"] = false;
if (isset($SHOW_CONTEXT_HELP) && $show_context_help==='yes') $_SESSION["show_context_help"] = true;
if (isset($SHOW_CONTEXT_HELP) && $show_context_help==='no') $_SESSION["show_context_help"] = false;
if (!isset($USE_THUMBS_MAIN)) $USE_THUMBS_MAIN = false;
if ((strstr($SCRIPT_NAME, "editconfig.php")===false) &&(strstr($SCRIPT_NAME, "editconfig_help.php")===false)) {
	if ((!check_db())||(!adminUserExists())) {
		header("Location: editconfig.php");
		exit;
	}
	if ((strstr($SCRIPT_NAME, "editconfig_gedcom.php")===false)
	&&(strstr($SCRIPT_NAME, "help_text.php")===false)
	&&(strstr($SCRIPT_NAME, "editconfig_help.php")===false)
	&&(strstr($SCRIPT_NAME, "editgedcoms.php")===false)
	&&(strstr($SCRIPT_NAME, "uploadgedcom.php")===false)
	&&(strstr($SCRIPT_NAME, "login.php")===false)
	&&(strstr($SCRIPT_NAME, "admin.php")===false)
	&&(strstr($SCRIPT_NAME, "config_download.php")===false)
	&&(strstr($SCRIPT_NAME, "addnewgedcom.php")===false)
	&&(strstr($SCRIPT_NAME, "validategedcom.php")===false)
	&&(strstr($SCRIPT_NAME, "addmedia.php")===false)
	&&(strstr($SCRIPT_NAME, "importgedcom.php")===false)
	&&(strstr($SCRIPT_NAME, "edit_privacy.php")===false)
	&&(strstr($SCRIPT_NAME, "backup.php")===false)
	&&(strstr($SCRIPT_NAME, "useradmin.php")===false)) {
		if (((count($GEDCOMS)==0)||(!check_for_import($GEDCOM)))&&empty($logout)) {
			header("Location: editgedcoms.php");
			exit;
		}
	}
	
	//-----------------------------------
	//-- if user wishes to logout this is where we will do it
	if ((!empty($logout))&&($logout==1)) {
		userLogout($gm_username);
		if ($REQUIRE_AUTHENTICATION) {
			header("Location: ".$HOME_SITE_URL);
			exit;
		}
		else {
			if (count($GEDCOMS)==0) {
				if (empty($LOGIN_URL)) header("Location: login.php");
				else header("Location: ".$LOGIN_URL);
				exit;
			}
		}
	}
	
	if ($REQUIRE_AUTHENTICATION) {
		if (empty($gm_username)) {
			if ((strstr($SCRIPT_NAME, "login.php")===false)
				&&(strstr($SCRIPT_NAME, "login_register.php")===false)
				&&(strstr($SCRIPT_NAME, "help_text.php")===false)
				&&(strstr($SCRIPT_NAME, "message.php")===false)) {
				$url = basename($_SERVER["SCRIPT_NAME"])."?".$QUERY_STRING;
				if (stristr($url, "index.php")!==false) {
					if (stristr($url, "command=")===false) {
						if ((!isset($_SERVER['HTTP_REFERER'])) || (stristr($_SERVER['HTTP_REFERER'],$SERVER_URL)===false)) $url .= "&command=gedcom";
					}
				}
				if (stristr($url, "ged=")===false)  {
					$url.="&ged=".$GEDCOM;
				}
				if (empty($LOGIN_URL)) header("Location: login.php?url=".urlencode($url));
				else header("Location: ".$LOGIN_URL."?url=".urlencode($url));
				exit;
			}
		}
	}
	
	// -- setup session information for tree clippings cart features
	if (!isset($_SESSION['cart'])) $_SESSION['cart'] = array();
	$cart = $_SESSION['cart'];
	
	$_SESSION['CLANGUAGE'] = $LANGUAGE;
	if (!isset($_SESSION["timediff"])) {
		$_SESSION["timediff"] = 0;
	}
   
	//-- load any editing changes
	if (userCanEdit($gm_username)) {
		if (!isset($_SESSION['changes'])) $_SESSION['changes'] = array();
		$changes = $_SESSION['changes'];
	}
	if (empty($LOGIN_URL)) $LOGIN_URL = "login.php";

} 
else check_db();

//-- load the user specific theme
if ((!empty($gm_username))&&(!isset($logout))) {
	$tempuser = GetUser($gm_username);
	$usertheme = $tempuser["theme"];
	if ((!empty($_POST["user_theme"]))&&(!empty($_POST["oldusername"]))&&($_POST["oldusername"]==$gm_username)) $usertheme = $_POST["user_theme"];
	if ((!empty($usertheme)) && (file_exists($usertheme."theme.php")))  {
		$THEME_DIR = $usertheme;
	}
}

if (isset($_SESSION["theme_dir"])) {
	$THEME_DIR = $_SESSION["theme_dir"];
	if (!empty($gm_username)) {
		$tempuser = GetUser($gm_username);
		if ($tempuser["editaccount"]) unset($_SESSION["theme_dir"]);
	}
}

if (empty($THEME_DIR)) $THEME_DIR="standard/";
if (file_exists($GM_BASE_DIRECTORY.$THEME_DIR."theme.php")) require_once($GM_BASE_DIRECTORY.$THEME_DIR."theme.php");
else {
	$THEME_DIR = $GM_BASE_DIRECTORY."themes/standard/";
	require_once($THEME_DIR."theme.php");
}

require_once($GM_BASE_DIRECTORY."hitcount.php"); //--load the hit counter

if ($Languages_Default) {					// If Languages not yet configured
	$gm_lang_use["english"] = false;		//   disable English
	$gm_lang_use["$LANGUAGE"] = true;		//     and enable according to Browser pref.
	$language_settings["english"]["gm_lang_use"] = false;
	$language_settings["$LANGUAGE"]["gm_lang_use"] = true;
}

// NOTE: Update the user sessiontime since the user is not sleeping
// NOTE: This will keep the user logged in while being busy
update_sessiontime($gm_username);

// NOTE: Check every 15 minutes if there are users who are idle
// NOTE: Any user passing the GM_SESSION_TIME will be logged out
if (!isset($_SESSION["check_login"])) $_SESSION["check_login"] = time();
if ((time() - $_SESSION["check_login"]) > 900) {
	$users = getUsers("username", "asc", "firstname", "u_loggedin='Y'");
	foreach($users as $indexval => $user) {
		if (time() - $user["sessiontime"] > $GM_SESSION_TIME) userLogout($user["username"], "Session expired");
	}
	$_SESSION["check_login"] = time();
}

// Load the functions to determine the browser the users is using
require_once("detectbrowser.php");
?>