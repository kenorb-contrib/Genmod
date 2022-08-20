<?php
/**
 * Genmod Installation file
 *
 * This file implements the datastore functions necessary for Genmod to use an SQL database as its
 * datastore. This file also implements array caches for the database tables.  Whenever data is
 * retrieved from the database it is stored in a cache.  When a database access is requested the
 * cache arrays are checked first before querying the database.
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
 * @version $Id: install.php 29 2022-07-17 13:18:20Z Boudewijn $
 * @package Genmod
 * @subpackage Installation
 */

// NOTE: Checklist
// 1. PHP version
// 2. MySQL version
// 3. DB connectie details
// 4. DB structure
// 5. Admin user

define('GM_VERSION', "2.1");
define('GM_VERSION_RELEASE', "Beta 2");
define('DEBUG', true);
$min_php_version = "5.2";
$min_mysql_version = "5.1";
$stylesheet = "install_style.css";
$TEXT_DIRECTION = "ltr";
$MEDIA_DIRECTORY = "../media/";
$GM_BASE_DIRECTORY = "";
$action = "";

// NOTE: Construct the URL
$LOCATION =  "http://".basename($_SERVER["SERVER_NAME"]);
if ($_SERVER["SERVER_PORT"] != 80) $LOCATION = $LOCATION.":".$_SERVER["SERVER_PORT"];
$LOCATION .= "/";
$dirname = dirname($_SERVER["SCRIPT_NAME"]);
$split = preg_split("/\//",$dirname);
$newpath = "";
for ($check=1;$check < count($split)-1;$check++) {
	$newpath .= $split[$check]."/";
}
$LOCATION .= $newpath;

if (isset($_POST["step"])) $step = $_POST["step"];
else $step = 1;

// NOTE: Start the session
@session_start();

// NOTE: Turn all POST variables into session variables
foreach ($_POST as $name => $value) {
	$_SESSION[$name] = $value;
}

// NOTE: Make all session variables global variables
foreach ($_SESSION as $name => $value) {
	$$name = $value;
}

// NOTE: Turn post variables into global variables
extract($_REQUEST);

// NOTE: Load the functions
require("install_functions.php");
require("../includes/functions/functions.php");
// NOTE: Load the language
InstallLoadLanguage();

$setup_php = false;
$setup_mysql = false;
$setup_db = false;
$setup_config = false;
$media = false;
$thumbs = false;
$error = "";
$upgrade = false;
$newconfigparms = array();
$index_inuse = array();

// NOTE: Load the existing configuration
$oldconfig = file("../config.php");
foreach ($oldconfig as $key => $value) {
	// NOTE: Store all config values in a new array
	if (substr($value, 0, 1) == "\$" && stristr($value, "[")) {
		$hitsvar = preg_match("/\[\"(.*)\"]/",$value,$matchvar);
		$hitsvalue = preg_match("/\s'(.*)'/",$value,$matchvalue);
		if ($hitsvalue == 0) $hitsvalue = preg_match("/=\s(.*);/",$value,$matchvalue);
		if ($hitsvar == 1 && !stristr($matchvar[1], "http")) {
			$newconfig[$matchvar[1]] = trim(stripslashes($matchvalue[1]));
			if ($matchvar[1] == "INDEX_DIRECTORY") $index_inuse[] = $newconfig[$matchvar[1]];
		}
		else if (stristr($matchvar[1], "http")) {
			$newconfigparms[$matchvar[1]] = $newconfig;
		}
	}
}

// NOTE: Check if this is an upgrade or new installation
if (array_key_exists($LOCATION, $newconfigparms)) $upgrade = true;
if ($step > 2) {
	if ($link = @($GLOBALS["___mysqli_ston"] = mysqli_connect($DBHOST,  $DBUSER,  $DBPASS))) {
		((bool)mysqli_query($GLOBALS["___mysqli_ston"], "USE " . $DBNAME));
	}
	else {
		$step = 2;
		$error = GM_LANG_error.": ". ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false));
		if (strtolower($DBHOST) == "localhost") {
			$error .= "<br />".GM_LANG_DB_host_error;
		}
	}
	if (!$upgrade && in_array($INDEX_DIRECTORY, $index_inuse)) {
		$step = 2;
		$error .= "<br />".GM_LANG_error.": Index directory already in use by another site.";
	}
}

header("Content-Type: text/html; charset=UTF-8");
print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
print "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n\t<head>\n\t\t";
print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF8\" />\n\t\t";
print "<link rel=\"stylesheet\" href=\"".$stylesheet."\" type=\"text/css\" media=\"all\" />\n\t";
print "<title>Genmod ".GM_VERSION." ".GM_VERSION_RELEASE." ".GM_LANG_install."</title>";
print "</head>\n\t<body>";
print "<div id=\"header\" class=\"".$TEXT_DIRECTION."\">";
print "<img src=\"images/header.gif\" alt=\"\"/>";
print "<span style=\"font-size: 26px; color: #DE0029; padding-left: 2em;\">".GM_LANG_setup_genmod."</span>";
print "</div>";
print "<div id=\"body\" class=\"".$TEXT_DIRECTION."\" style=\"padding: 5em;\">";
InstallShowProgress();
print "<br />";

// NOTE: Check if we can write the session information
//	Note that you shouldn't use session_save_path() directly for performing file operations.
//	It returns the configuration option, not the directory.

$ssp = session_save_path();
if (0) {
	if (!is_writable($ssp)) {
		print "<span class=\"Error\">".GM_LANG_session_cannot_start."</span>";
		print "<br />";
		print GM_LANG_session_path.$ssp;
		// NOTE: Delete session information since it cannot be written
		@session_destroy();
		print "<br />";
		print "<br />";
		print "<form method=\"post\" name=\"next\" action=\"".$_SERVER["SCRIPT_NAME"]."\">\n";
		print "<input type=\"submit\"  value=\"Restart\">\n";
	}
	else {
	}
}
if ($step == 1) {
	print GM_LANG_step1;
	print "<br /><br />";
	
	if (phpversion() < $min_php_version) {
		print "<img src=\"images/nok.png\" alt=\"PHP version too low\" title=\"PHP version too low\" /> ";
		print "<span class=\"Error\">Genmod requires PHP version 4.3.0 or later.</b><br />\nYour server is running PHP version ".phpversion().". Please ask your server's Administrator to upgrade the PHP installation.</span>";
		print "<br /><br />";
		$setup_php = false;
	}
	else {
		print "<img src=\"images/ok.png\" alt=\"PHP version OK\" title=\"PHP version OK\" /> ";
		print "Your PHP version meets the requirement for Genmod.<br />";
		$setup_php = true;
	}
	
	// Check if the media directory is not a .
	// If so, do not try to create it since it does exist
	// Check first if the $MEDIA_DIRECTORY exists
	if (!is_dir("../".GedcomConfig::$MEDIA_DIRECTORY)) {
		if (mkdir("../".GedcomConfig::$MEDIA_DIRECTORY)) {
			if (!file_exists("../".$MEDIA_DIRECTORY."index.php")) {
				$inddata = html_entity_decode("<?php\nheader(\"Location: ../medialist.php\");\nexit;\n?>");
				$fp = @fopen("../".GedcomConfig::$MEDIA_DIRECTORY."index.php","w+");
				if (!$fp) print "<span class=\"Error\">".GM_LANG_security_no_create.GedcomConfig::$MEDIA_DIRECTORY."</span>";
				else {
					// Write the index.php for the media folder
					fputs($fp,$inddata);
					fclose($fp);
					$media = true;
				}
			}
			else $media = true;
		}
		else  $media = false;
	}
	else $media = true;
	
	if ($media) {
		print "<img src=\"images/ok.png\" alt=\"Media structure OK\" title=\"Media structure OK\" /> ";
		print "The media folder structure has been checked and found OK.<br />";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Media structure NOK\" title=\"Media structure NOK\" /> ";
		print "<span class=\"Error\">The media folder structure has been checked and not found OK. The media folder could not be created.</span><br />";
	}
	// Check if the thumbs folder exists
	if (!is_dir("../".GedcomConfig::$MEDIA_DIRECTORY."thumbs")) {
		if (mkdir("../".GedcomConfig::$MEDIA_DIRECTORY."thumbs")) {
			if (!file_exists("../".GedcomConfig::$MEDIA_DIRECTORY."thumbs/index.php")) {
				$inddata = file_get_contents("../".GedcomConfig::$MEDIA_DIRECTORY."index.php");
				$inddatathumb = str_replace(": ../",": ../../",$inddata);
				$fpthumb = @fopen("../".GedcomConfig::$MEDIA_DIRECTORY."thumbs/index.php","w+");
				if (!$fpthumb) print "<div class=\"error\">".GM_LANG_security_no_create.GedcomConfig::$MEDIA_DIRECTORY."thumbs</div>";
				else {
					// Write the index.php for the thumbs media folder
					fputs($fpthumb,$inddatathumb);
					fclose($fpthumb);
					$thumbs = true;
				}
			}
			else $thumbs = true;
		}
		else $thumbs = false;
	}
	else $thumbs = true;
	
	if ($thumbs) {
		print "<img src=\"images/ok.png\" alt=\"Media structure OK\" title=\"Media structure OK\" /> ";
		print "The thumbnail media folder structure has been checked and found OK.<br />";
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Media structure NOK\" title=\"Media structure NOK\" /> ";
		print "<span class=\"Error\">The thumbnail media folder structure has been checked and not found OK. The media folder could not be created.</span>";
	}
	
	if ($setup_php && $media && $thumbs) {
		print "<form method=\"post\" name=\"next\" action=\"".$_SERVER["SCRIPT_NAME"]."\">";
		print "<input type=\"hidden\" name=\"step\" value=\"2\">";
		print "<br />";
		print "<input type=\"submit\"  value=\"".GM_LANG_next."\">";
		print "</form>";
		$setup_php = true;
		$media = true;
		$thumbs = true;
	}
	else {
		print "<br /><br />";
		print "<img src=\"images/nok.png\" alt=\"Requirements not OK\" title=\"Requirements not OK\" /> ";
		print "Unable to continue. Please update your system so that you meet all requirements.";
		print "<br /><br />";
		print "<span class=\"Error\">The installation has been terminated.</span>";
	}
}

// NOTE: Have the user enter database details
if ($step == 2) {
	print GM_LANG_step2;
	print "<br /><br />";
	
	if (!empty($error)) print "<span class=\"Error\">".$error."</span><br /><br />";
	print "<form method=\"post\" action=\"".$_SERVER["SCRIPT_NAME"]."\" name=\"loginform\">\n";
	print "<input type=\"hidden\" name=\"step\" value=\"3\"/>";
	print "<label class=\"InstallLabelForm\">Database details</label><br style=\"clear: left;\"/>";
	print "<label class=\"InstallLabelForm\" for=\"DBHOST\">".GM_LANG_DBHOST."</label>";
	print "<input class=\"InstallInputForm\" type=\"text\" id=\"DBHOST\" name=\"DBHOST\" value=\"";
	if ($upgrade) print $newconfigparms[$LOCATION]["DBHOST"];
	else print "localhost";
	if (!isset($DBUSER)) $DBUSER = "";
	if (!isset($DBPASS)) $DBPASS = "";
	print "\" /><br style=\"clear: left;\"/>";
	print "<label class=\"InstallLabelForm\" for=\"DBUSER\">".GM_LANG_DBUSER."</label>";
	print "<input class=\"InstallInputForm\" type=\"text\" id=\"DBUSER\" name=\"DBUSER\" value=\"".$DBUSER."\"/><br style=\"clear: left;\"/>";
	print "<label class=\"InstallLabelForm\" for=\"DBPASS\">".GM_LANG_DBPASS."</label>";
	print "<input class=\"InstallInputForm\" type=\"password\" id=\"DBPASS\" name=\"DBPASS\" value=\"".$DBPASS."\" /><br style=\"clear: left;\"/>";
	print "<label class=\"InstallLabelForm\" for=\"DBNAME\">".GM_LANG_DBNAME."</label>";
	print "<input class=\"InstallInputForm\" type=\"text\" id=\"DBNAME\" name=\"DBNAME\" value=\"";
	if (isset($DBNAME)) print $DBNAME;
	else if ($upgrade) print $newconfigparms[$LOCATION]["DBNAME"];
	else print "genmod";
	print "\" /><br style=\"clear: left;\"/>";
	print "<label class=\"InstallLabelForm\" for=\"TBLPREFIX\">".GM_LANG_TBLPREFIX."</label>";
	print "<input class=\"InstallInputForm\" type=\"text\" id=\"TBLPREFIX\" name=\"TBLPREFIX\" value=\"";
	if (isset($TBLPREFIX)) print $TBLPREFIX;
	else if ($upgrade) print $newconfigparms[$LOCATION]["TBLPREFIX"];
	else print "gm_";
	print "\" /><br style=\"clear: left;\"/>";
	print "<label class=\"InstallLabelForm\" for=\"INDEX_DIRECTORY\">".GM_LANG_INDEX_DIRECTORY."</label>";
	print "<input class=\"InstallInputForm\" type=\"text\" id=\"INDEX_DIRECTORY\" name=\"INDEX_DIRECTORY\" value=\"";
	if (isset($INDEX_DIRECTORY)) print $INDEX_DIRECTORY;
	else if ($upgrade) print $newconfigparms[$LOCATION]["INDEX_DIRECTORY"];
	else print GuessIndexDirectory($index_inuse);
	print "\" /><br style=\"clear: left;\"/>";
	print "<label class=\"InstallLabelForm\" for=\"submit\" />&nbsp;</label>";
	print "<input class=\"InstallInputForm\" type=\"submit\"  id=\"submit\" name=\"submit\" value=\"".GM_LANG_next."\"/><br style=\"clear: left;\"/>";
	print "</form>";

}
// NOTE: Verify database structure
if ($step == 3) {
	print GM_LANG_step3;
	print "<br /><br />";
	if (substr(trim(((is_null($___mysqli_res = mysqli_get_server_info($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res)), 0, strlen($min_mysql_version)) < $min_mysql_version) {
		print "<img src=\"images/nok.png\" alt=\"MySQL version too low\" title=\"MySQL version too low\" /> ";
		print "<b style=\"color: red;\">Genmod requires MySQL version ".$min_mysql_version." or later.</b><br />\nYour server is running PHP version ".((is_null($___mysqli_res = mysqli_get_server_info($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res).". Please ask your server's Administrator to upgrade the MySQL installation.";
		print "<br /><br />";
		$setup_mysql = false;
	}
	else {
		$res = mysqli_query($GLOBALS["___mysqli_ston"], "SHOW VARIABLES LIKE 'sql_mode'");
		if ($res) {
			$mode = mysqli_fetch_assoc($res);
			$strict = stristr($mode["Value"], "STRICT_TRANS_TABLES");
			if ($strict) {
				print "<img src=\"images/nok.png\" alt=\"MySQL mode incorrect\" title=\"MySQL mode incorrect\" /> ";
				print "<b style=\"color: red;\">Your MySQL installation is in Strict Mode.</b><br />\nPlease ask your server's Administrator to modify the MySQL installation.";
				$setup_mysql = false;
			}
			else {
				print "<img src=\"images/ok.png\" alt=\"MySQL version OK\" title=\"MySQL version OK\" /> ";
				print "Your MySQL version meets the requirement for Genmod.<br />";
				$setup_mysql = true;
			}
		}
	}
	
	if ($setup_mysql) {
		$db_ok = InstallCheckDBLayout();
		if (!$db_ok && !is_array($deleterows)) {
			print GM_LANG_create_database_first;
			InstallRestartButton();
		}
		else {
			if (!$db_ok) {
				print "<img src=\"images/nok.png\" alt=\"Database layout not OK\" title=\"Database layout not OK\" /> ";
				print "There are missing entries found in your database. Your database will now be upgraded.";
				print "<br />";
				print "<div style=\"overflow-y: auto; border: 1px solid #DE0039; height: 5em; width: 30em; margin: 1em; padding: 1em;\">";
				// Maximum loop is 3, in case there is a system error
				$loop = 0;
				do {
					if ($loop > 3) {
						$setup_db = false;
						break;
					}
					else {
						$db_ok = InstallCheckDBLayout();
						if (!$db_ok) InstallFixDBLayout();
						else $setup_db = true;
						$loop++;
					}
				} while (!$db_ok);
				print "</div>";
			}
			if ($db_ok) {
				print "<img src=\"images/ok.png\" alt=\"Database layout OK\" title=\"Database layout OK\" /> ";
				print "Your database layout has been checked and found correct.";
				print "<br />";
				$setup_db = true;
				print "<form method=\"post\" name=\"next\" action=\"".$_SERVER["SCRIPT_NAME"]."\">";
				print "<input type=\"hidden\" name=\"step\" value=\"4\">";
				print "<br />";
				print "<input type=\"submit\"  value=\"".GM_LANG_next."\">";
				print "</form>";
			}
			else if (!$db_ok) {
				print "<img src=\"images/nok.png\" alt=\"Database layout not OK\" title=\"Database layout not OK\" /> ";
				print "Unable to setup the database correctly. Please check if you have the correct priviliges.";
				print "<br /><br />";
				print "Run installation again after all priviliges has been set. ";
				InstallRestartButton();
			}
		}
	}
	else {
		print "<br /><br />";
		print "<img src=\"images/nok.png\" alt=\"Requirements not OK\" title=\"Requirements not OK\" /> ";
		print "Unable to continue. Please update your system so that you meet all requirements.";
		print "<br /><br />";
		print "<span class=\"Error\">The installation has been terminated.</span>";
	}
}
if ($step == 4) {
	print GM_LANG_step4;
	print "<br /><br />";
	
	// NOTE: Request user details
	$sql = "SELECT COUNT(u_username) as admins FROM ".$TBLPREFIX."users WHERE u_canadmin = 'Y'";
	$res = mysqli_query($GLOBALS["___mysqli_ston"], $sql);
	$row = mysqli_fetch_row($res);
	
	if ($action == "createadminuser" && $row[0] == 0) {
		$user = array();
		$user["username"]=$username;
		$user["firstname"]=$firstname;
		$user["lastname"]=$lastname;
		$user["password"]=password_hash($pass1, PASSWORD_DEFAULT);
		$user["canedit"] = array();
		$user["rootid"] = array();
		$user["gedcomid"] = array();
		$user["canadmin"]=true;
		$user["email"]=$emailadress;
		$user["verified"] = "Y";
		$user["verified_by_admin"] = "Y";
		$user["theme"] = "";
		$user["theme"] = "Y";
		$user["language"] = "english";
		$user["reg_timestamp"] = date("U");
		$user["reg_hashcode"] = "";
		$user["loggedin"] = "Y";
		$user["sessiontime"] = 0;
		$user["contactmethod"] = "messaging2";
		$user["visibleonline"] = true;
		$user["editaccount"] = true;
		$user["default_tab"] = 0;
		$user["comment"] = "";
		$user["comment_exp"] = "";
		$user["sync_gedcom"] = "N";
		$au = InstallAddAdminUser($user);
		unset($_SESSION["action"]);
		if ($au) {
			print "<img src=\"images/ok.png\" alt=\"Create administrator account OK\" title=\"Create administrator account OK\" /> ";
			print GM_LANG_user_created;
			print "<br />";
			$_SESSION["gm_user"]=$username;
		}
		else {
			print "<img src=\"images/nok.png\" alt=\"Create administrator account NOK\" title=\"Create administrator account NOK\" /> ";
			print GM_LANG_user_create_error;
			print "<br />";
		}
	}
	// NOTE: Request user details
	$sql = "SELECT COUNT(u_username) as admins FROM ".$TBLPREFIX."users WHERE u_canadmin = 'Y'";
	$res = mysqli_query($GLOBALS["___mysqli_ston"], $sql);
	$row = mysqli_fetch_row($res);
	
	if ($row[0] > 0) {
		print "<img src=\"images/ok.png\" alt=\"Administrator account OK\" title=\"Administrator account OK\" /> ";
		print "An administrator account exists.";
		print "<form method=\"post\" name=\"next\" action=\"".$_SERVER["SCRIPT_NAME"]."\">";
		print "<input type=\"hidden\" name=\"step\" value=\"5\">";
		print "<br />";
		print "<input type=\"submit\"  value=\"".GM_LANG_next."\">";
		print "</form>";
		// session_destroy();
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Administrator account NOK\" title=\"Administrator account NOK\" /> ";
		print "An administrator account does not yet exist.";
		print "<br />";
		?>
		<script language="JavaScript" type="text/javascript">
		<!--
			function checkform(frm) {
				if (frm.username.value=="") {
					alert("<?php print GM_LANG_enter_username; ?>");
					frm.username.focus();
					return false;
				}
				if (frm.firstname.value=="") {
					alert("<?php print GM_LANG_enter_fullname; ?>");
					frm.firstname.focus();
					return false;
				}
				if (frm.lastname.value=="") {
					alert("<?php print GM_LANG_enter_fullname; ?>");
					frm.lastname.focus();
					return false;
				}
				if (frm.pass1.value=="") {
					alert("<?php print GM_LANG_enter_password; ?>");
					frm.pass1.focus();
					return false;
				}
				if (frm.pass2.value=="") {
					alert("<?php print GM_LANG_confirm_password; ?>");
					frm.pass2.focus();
					return false;
				}
				if (frm.pass1.value != frm.pass2.value) {
					alert("<?php print GM_LANG_password_mismatch; ?>");
					frm.pass1.focus();
					return false;
				}
				return true;
			}
		//-->
		</script>
		<br />
		<form method="post" action="<?php print $_SERVER["SCRIPT_NAME"];?>" onsubmit="return checkform(this);">
			<input type="hidden" name="action" value="createadminuser" />
			<input type="hidden" name="step" value="4" />
			<label class="InstallLabelForm"><?php print GM_LANG_username;?></label>
			<input class="InstallInputForm" type="text" name="username" /><br style="clear: left;" />
			<label class="InstallLabelForm"><?php print GM_LANG_firstname;?></label>
			<input class="InstallInputForm" type="text" name="firstname" /><br style="clear: left;" />
			<label class="InstallLabelForm"><?php print GM_LANG_lastname;?></label>
			<input class="InstallInputForm" type="text" name="lastname" /><br style="clear: left;" />
			<label class="InstallLabelForm"><?php print GM_LANG_password;?></label>
			<input class="InstallInputForm" type="password" name="pass1" /><br style="clear: left;" />
			<label class="InstallLabelForm"><?php print GM_LANG_confirm;?></label>
			<input class="InstallInputForm" type="password" name="pass2" /><br style="clear: left;" />
			<label class="InstallLabelForm"><?php print GM_LANG_emailadress;?></label>
			<input class="InstallInputForm" type="text" name="emailadress" size="45" /><br style="clear: left;" />
			<input class="InstallLabelForm" type="submit" value="<?php print GM_LANG_create_user; ?>" />
		</form>
		<?php
		
	}
}

if ($step == 5) {
	print GM_LANG_step5;
	print "<br /><br />";
	
	// NOTE: Load the language settings file
	include("install_lang_settings.php");

	// Read, if possible, the previous active/inactive settings from the DB
	$sql = "SELECT ls_gm_langname, ls_gm_lang_use FROM ".$TBLPREFIX."lang_settings";
	$res = mysqli_query($GLOBALS["___mysqli_ston"], $sql);
	$currentlangs = array();
	while ($row = mysqli_fetch_row($res)) {
		$currentlangs[$row[0]] = $row[1];
	}
		
	print "<form method=\"post\" name=\"next\" action=\"".$_SERVER["SCRIPT_NAME"]."\">"; 
	print "<table class=\"facts_table\">";
	// NOTE: Build a sorted list of language names in the currently active language
	// NOTE: Also build a list of active languages so we can compare which have been turned off
	foreach ($language_settings as $key => $value){
		$d_LangName = "lang_name_".$key;
		$SortedLangs[$key] = constant("GM_LANG_".$d_LangName);
		// If the language is in the DB settings, take that value for on/off
		if (isset($currentlangs[$key])) {
			if ($currentlangs[$key] == 1) $_SESSION["ActiveLangs"][] = $key;
		}
		else {
			// If not, take it from the install file
			if ($value["gm_lang_use"]) $_SESSION["ActiveLangs"][] = $key;
		}
	}
	asort($SortedLangs);
	
	// NOTE: Build sorted list of languages, using numeric index
	// NOTE: If necessary, insert one blank filler at the end of the 2nd column
	// NOTE: Always insert a blank filler at the end of the 3rd column
	$lines = ceil(count($SortedLangs) / 3);
	$i = 1;
	$LangsList = array();
	foreach ($SortedLangs as $key => $value) {
		$LangsList[$i] = $SortedLangs[$key];
		$i++;
	}
	
	// Print the languages in three columns
	$curline = 1;
	$SortedLangs = array_flip($SortedLangs);
	while ($curline <= $lines) {
		// NOTE: Start each table row
		print "<tr>";
		$curcol = 0;
		$showkey = 0;
		// NOTE: Print each column
		while ($curcol < 3) {
			// NOTE: Determine the key to get from the language array
			if ($curcol > 0) $showkey = $curline+(8*$curcol);
			else $showkey = $curline;
			if (array_key_exists($showkey, $LangsList)) {
				$LocalName = $LangsList[$showkey];
				$LangName = $SortedLangs[$LocalName];
				print "<td class=\"shade1\"><input type=\"checkbox\" name=\"NEW_LANGS[]\" value=\"".$LangName."\" ";
				if (in_array($LangName, $_SESSION["ActiveLangs"]) || $LangName == "english") print "checked=\"checked\"";
				if ($LangName == "english") print "disabled=\"disabled\" ";
				print "/></td>";
				print "<td class=\"shade2 width30\">".$LocalName."</td>\n";
			}
			else {
				print "<td class=\"shade1\">&nbsp;</td>";
				print "<td class=\"shade2 width30\">&nbsp;</td>\n";
			}
			$curcol++;
		}
		// Finish the table row
		print "</tr>";
		$curline++;
	}
	print "</table>";
	print "<input type=\"hidden\" name=\"step\" value=\"6\">";
	print "<input type=\"hidden\" name=\"language\" value=\"chosen\">";
	print "<br />";
	print "<input type=\"submit\"  value=\"".GM_LANG_next."\">";
	print "</form>";
}

if ($step ==  6) {
	print GM_LANG_step6;
	print "<br /><br />";
	include("install_lang_settings.php");
	
	$output = array();
	$output["lang"] = true;
	$output["help"] = true;
	$output["facts"] = true;

	// NOTE: Make a copy of the languages in the session so we can later store the on/off values
	if (!isset($_SESSION["GM_LANGS"])) {
		$_SESSION["GM_LANGS"] = $NEW_LANGS;
		$_SESSION["GM_LANGS"][] = "english";
	}
	
	if (!isset($_POST["english_done"])) $english_done = false;
	if (!isset($_POST["NEW_LANGS"])) $NEW_LANGS = array();
	else $NEW_LANGS = $_POST["NEW_LANGS"];

	if (!$english_done) {
		// NOTE: Empty the language table
		$sql = "TRUNCATE TABLE ".$TBLPREFIX."language";
		$res = @mysqli_query($GLOBALS["___mysqli_ston"], $sql);
		
		// NOTE: Empty the language help table
		$sql = "TRUNCATE TABLE ".$TBLPREFIX."language_help";
		$res = @mysqli_query($GLOBALS["___mysqli_ston"], $sql);

		// NOTE: Empty the language facts table
		$sql = "TRUNCATE TABLE ".$TBLPREFIX."facts";
		$res = @mysqli_query($GLOBALS["___mysqli_ston"], $sql);
	}
	if ($english_done == false) {
		$output["lang"] = true;
		$output["help"] = true;
		$output["facts"] = true;
		// NOTE: Store English it is the basis language
		if (file_exists("../languages/lang.en.txt")) {
			// NOTE: Import the English language into the database
			$lines = file("../languages/lang.en.txt");
			foreach ($lines as $key => $line) {
				$data = preg_split("/\";\"/", $line, 2);
				if (!isset($data[1])) WriteToLog($line, "E");
				else {
					$data[0] = substr(trim($data[0]), 1);
					$data[1] = substr(trim($data[1]), 0, -1);
					$sql = "INSERT INTO ".$TBLPREFIX."language VALUES ('".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $data[0]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', '".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $data[1]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '".time()."', 'install')";
					if (!$result = mysqli_query($GLOBALS["___mysqli_ston"], $sql)) {
						$output["lang"] = false;
						print "Could not add language string ".$line." for language English to table<br />";
						print "Error: ".((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false));
					}
				 }
			}
		}
		
		if (file_exists("../languages/help_text.en.txt")) {
			// NOTE: Import the English language help into the database
			$lines = file("../languages/help_text.en.txt");
			foreach ($lines as $key => $line) {
				$data = preg_split("/\";\"/", $line, 2);
				if (!isset($data[1])) WriteToLog($line, "E");
				else {
					$data[0] = substr(trim($data[0]), 1);
					$data[1] = substr(trim($data[1]), 0, -1);
					$sql = "INSERT INTO ".$TBLPREFIX."language_help VALUES ('".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $data[0]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', '".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $data[1]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '".time()."', 'install')";
					if (!$result = mysqli_query($GLOBALS["___mysqli_ston"], $sql)) {
						$output["help"] = false;
						print "Could not add language help string ".$line." for language English to table<br />";
						print "Error: ".((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false));
					}
				}
			}
		}
		if (file_exists("../languages/facts.en.txt")) {
			// NOTE: Import the English language help into the database
			$lines = file("../languages/facts.en.txt");
			foreach ($lines as $key => $line) {
				$data = preg_split("/\";\"/", $line, 2);
				if (!isset($data[1])) WriteToLog($line, "E");
				else {
					$data[0] = substr(trim($data[0]), 1);
					$data[1] = substr(trim($data[1]), 0, -1);
					$sql = "INSERT INTO ".$TBLPREFIX."facts VALUES ('".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $data[0]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', '".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $data[1]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '".time()."', 'install')";
					if (!$result = mysqli_query($GLOBALS["___mysqli_ston"], $sql)) {
						$output["facts"] = false;
						print "Could not add facts string ".$line." for language English to table<br />";
						print "Error: ".((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false));
					}
				}
			}
		}
		// NOTE: Output the result of the English language import
		$all_ok = true;
		foreach ($output as $type => $result) {
			if ($result) {
				print "<img src=\"images/ok.png\" alt=\"Language import OK\" title=\"Language import OK\" /> ";
				if ($type == "lang") print "Language ";
				else if ($type == "help") print "Help ";
				else if ($type == "facts") print "Facts ";
				print GM_LANG_lang_name_english." imported succesfully.";
				print "<br />";
			}
			else {
				$all_ok = false;
				print "<img src=\"images/nok.png\" alt=\"Language import NOK\" title=\"Language import NOK\" /> ";
				if ($type == "lang") print "Language ";
				else if ($type == "help") print "Help ";
				else if ($type == "facts") print "Facts ";
				print GM_LANG_lang_name_english." imported failed.";
				print "<br />";
			}
		}
		$english_done = true;
		if ($all_ok) {
			$sql = "INSERT INTO ".$TBLPREFIX."lang_settings (ls_gm_langname, ls_translated, ls_md5_lang, ls_md5_help, ls_md5_facts) VALUES ('english', '0','".md5_file("../languages/lang.".$language_settings["english"]["lang_short_cut"].".txt")."', '".md5_file("../languages/help_text.".$language_settings["english"]["lang_short_cut"].".txt")."', '".md5_file("../languages/facts.".$language_settings["english"]["lang_short_cut"].".txt")."') ON DUPLICATE KEY UPDATE ls_translated='0', ls_md5_lang='".md5_file("../languages/lang.".$language_settings["english"]["lang_short_cut"].".txt")."', ls_md5_help='".md5_file("../languages/help_text.".$language_settings["english"]["lang_short_cut"].".txt")."', ls_md5_facts='".md5_file("../languages/facts.".$language_settings["english"]["lang_short_cut"].".txt")."'";
			$res = mysqli_query($GLOBALS["___mysqli_ston"], $sql);
			
			if (!$res) print ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false));
		}

	}
	else {
		$output = InstallStoreLanguage($NEW_LANGS[0]);
		foreach ($output as $type => $result) {
			if ($result) {
				print "<img src=\"images/ok.png\" alt=\"Language import OK\" title=\"Language import OK\" /> ";
				if ($type == "lang") print "Language ";
				else if ($type == "help") print "Help ";
				else if ($type == "facts") print "Facts ";
				print constant("GM_LANG_lang_name_".$NEW_LANGS[0])." imported succesfully.";
				print "<br />";
			}
			else {
				print "<img src=\"images/nok.png\" alt=\"Language import NOK\" title=\"Language import NOK\" /> ";
				if ($type == "lang") print "Language ";
				else if ($type == "help") print "Help ";
				else if ($type == "facts") print "Facts ";
				print constant("GM_LANG_lang_name_".$NEW_LANGS[0])." imported failed.";
				print "<br />";
			}
		}
		// NOTE: Remove the language so it is not processed again
		unset($NEW_LANGS[0]);
	}
	print "<form method=\"post\" name=\"next\" action=\"".$_SERVER["SCRIPT_NAME"]."\">";
	foreach ($NEW_LANGS as $key => $lang) {
		print "<input type=\"hidden\" name=\"NEW_LANGS[]\" value=\"".$lang."\" >";
	}
	print "<input type=\"hidden\" name=\"step\" value=\"";
	if (count($NEW_LANGS) == 0) print "7";
	else print "6";
	print "\">";
	print "<input type=\"hidden\" name=\"english_done\" value=\"".$english_done."\">";
	print "<br />";
	print "<input type=\"submit\"  value=\"".GM_LANG_next."\">";
	print "</form>";
}

if ($step == 7) {
	print GM_LANG_step7;
	print "<br /><br />";
	
	// NOTE: Write the configuration file
	// Include the basic values
	require_once ("../configbase.php");

	// overwrite with actual values
	$CONFIG["DBHOST"] = $_SESSION["DBHOST"];
	$CONFIG["DBUSER"] = $_SESSION["DBUSER"];
	$CONFIG["DBPASS"] = $_SESSION["DBPASS"];
	$CONFIG["DBNAME"] = $_SESSION["DBNAME"];
	$CONFIG["INDEX_DIRECTORY"] = $_SESSION["INDEX_DIRECTORY"];
	if ($upgrade && isset($newconfigparms[$LOCATION]["DBPERSIST"])) $CONFIG["DBPERSIST"] = $newconfigparms[$LOCATION]["DBPERSIST"];
	else $CONFIG["DBPERSIST"] = $DBPERSIST;
	$CONFIG["TBLPREFIX"] = $_SESSION["TBLPREFIX"];
	$CONFIG["SERVER_URL"] = $LOCATION;
	if ($upgrade && isset($newconfigparms[$LOCATION]["LOGIN_URL"])) $CONFIG["LOGIN_URL"] = $newconfigparms[$LOCATION]["LOGIN_URL"];
	else $CONFIG["LOGIN_URL"] = $LOGIN_URL;
	if ($upgrade && isset($newconfigparms[$LOCATION]["SITE_ALIAS"])) $CONFIG["SITE_ALIAS"] = $newconfigparms[$LOCATION]["SITE_ALIAS"];
	else if (preg_match("/:\/\/www\./", $LOCATION)) {
		$CONFIG["SITE_ALIAS"] = preg_replace("/:\/\/www\./", "://", $LOCATION);
	}
	else $CONFIG["SITE_ALIAS"] = "";
	if ($upgrade && isset($newconfigparms[$LOCATION]["GM_SESSION_SAVE_PATH"])) $CONFIG["GM_SESSION_SAVE_PATH"] = $newconfigparms[$LOCATION]["GM_SESSION_SAVE_PATH"];
	else $CONFIG["GM_SESSION_SAVE_PATH"] = "";
	if ($upgrade && isset($newconfigparms[$LOCATION]["GM_SESSION_TIME"])) $CONFIG["GM_SESSION_TIME"] = $newconfigparms[$LOCATION]["GM_SESSION_TIME"];
	else $CONFIG["GM_SESSION_TIME"] = "7200";
	$CONFIG["CONFIGURED"] = true;
	$CONFIG_PARMS[$LOCATION] = $CONFIG;
	$newconfigparms[$LOCATION] = $CONFIG_PARMS[$LOCATION];
	if (InstallStoreConfig()) {
		print "<img src=\"images/ok.png\" alt=\"Configuration save OK\" title=\"Configuration save OK\" /> Configuration file saved.<br />";
		$setup_config = true;
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Configuration save NOK\" title=\"Configuration save NOK\" /> Configuration file could not be saved.<br />";
		print "<span class=\"Error\">Most likely the file is not writeable.</span><br />";
	}
	
	// Set the chosen languages to active
	foreach ($_SESSION["GM_LANGS"] as $key => $name) {
		$gm_lang_use[$name] = true;
	}
	
	require("install_lang_settings.php");
	$setup_langconfig = true;
	foreach($language_settings as $key => $value) {
		$result = true;
		$sql = "SELECT ls_gm_lang FROM ".$TBLPREFIX."lang_settings WHERE ls_gm_langname='".$key."'";
		$res = mysqli_query($GLOBALS["___mysqli_ston"], $sql);
		if (mysqli_num_rows($res) == 0) {
			// The language does not exist
			$sql = "INSERT INTO ".$TBLPREFIX."lang_settings (ls_gm_langname, ls_gm_lang_use, ls_gm_lang, ls_lang_short_cut, ls_langcode, ls_gm_language, ls_confighelpfile, ls_helptextfile, ls_flagsfile, ls_factsfile, ls_DATE_FORMAT, ls_TIME_FORMAT, ls_WEEK_START, ls_TEXT_DIRECTION, ls_NAME_REVERSE, ls_ALPHABET_upper, ls_ALPHABET_lower, ls_MON_SHORT) VALUES ('".$key."', '";
			if (isset($gm_lang_use[$key])) $sql .= "1', ";
			else $sql .= "0', ";
			$sql .= "'".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $value["gm_lang"]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', ";
			$sql .= "'".$value["lang_short_cut"]."', ";
			$sql .= "'".$value["langcode"]."', ";
			$sql .= "'".$value["gm_language"]."', ";
			$sql .= "'".$value["confighelpfile"]."', ";
			$sql .= "'".$value["helptextfile"]."', ";
			$sql .= "'".$value["flagsfile"]."', ";
			$sql .= "'".$value["factsfile"]."', ";
			$sql .= "'".$value["DATE_FORMAT"]."', ";
			$sql .= "'".$value["TIME_FORMAT"]."', ";
			$sql .= "'".$value["WEEK_START"]."', ";
			$sql .= "'".$value["TEXT_DIRECTION"]."', ";
			if ($value["NAME_REVERSE"] == true) $sql .= "ls_NAME_REVERSE='1', ";
			else $sql .= "ls_NAME_REVERSE='0', ";
			$sql .= "'".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $value["ALPHABET_upper"]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', ";
			$sql .= "'".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $value["ALPHABET_lower"]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', ";
			$sql .= "'".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $value["MON_SHORT"]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."')";
			$res = mysqli_query($GLOBALS["___mysqli_ston"], $sql);
			if (!$res) {
				print "Error: ".((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false));
				$result = false;
			}
		}
		else {
			$row = mysqli_fetch_row($res);
			// language settings already present, only update on/off
			if ($row[0] != "") {
				$sql = "UPDATE ".$TBLPREFIX."lang_settings SET ls_gm_lang_use='";
				if (isset($gm_lang_use[$key])) $sql .="1'";
				else $sql .= "0'";
				$sql .= " WHERE ls_gm_langname='".$key."'";
				$res = mysqli_query($GLOBALS["___mysqli_ston"], $sql);
				if (!$res) {
					print "Error: ".((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false));
					$result = false;
				}
			}
			else {
				// Only MD settings are there, update the rest
				$sql = "UPDATE ".$TBLPREFIX."lang_settings SET ls_gm_lang_use='";
				if (isset($gm_lang_use[$key])) $sql .="1', ";
				else $sql .= "0', ";
				$sql .= "ls_gm_lang='".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $value["gm_lang"]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', ";
				$sql .= "ls_lang_short_cut='".$value["lang_short_cut"]."', ";
				$sql .= "ls_langcode='".$value["langcode"]."', ";
				$sql .= "ls_gm_language='".$value["gm_language"]."', ";
				$sql .= "ls_confighelpfile='".$value["confighelpfile"]."', ";
				$sql .= "ls_helptextfile='".$value["helptextfile"]."', ";
				$sql .= "ls_flagsfile='".$value["flagsfile"]."', ";
				$sql .= "ls_factsfile='".$value["factsfile"]."', ";
				$sql .= "ls_DATE_FORMAT='".$value["DATE_FORMAT"]."', ";
				$sql .= "ls_TIME_FORMAT='".$value["TIME_FORMAT"]."', ";
				$sql .= "ls_WEEK_START='".$value["WEEK_START"]."', ";
				$sql .= "ls_TEXT_DIRECTION='".$value["TEXT_DIRECTION"]."', ";
				if ($value["NAME_REVERSE"] == true) $sql .= "ls_NAME_REVERSE='1', ";
				else $sql .= "ls_NAME_REVERSE='0', ";
				$sql .= "ls_ALPHABET_upper='".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $value["ALPHABET_upper"]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', ";
				$sql .= "ls_ALPHABET_lower='".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $value["ALPHABET_lower"]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."', ";
				$sql .= "ls_MON_SHORT='".((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $value["MON_SHORT"]) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""))."' WHERE ls_gm_langname='".$key."'";
				$res = @mysqli_query($GLOBALS["___mysqli_ston"], "SET NAMES 'latin1'");
				$res = mysqli_query($GLOBALS["___mysqli_ston"], $sql);
				if (!$res) {
					print "Error: ".((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false));
					$result = false;
				}
			}
		}
		if (!$result) $setup_langconfig = false;
	}
	if ($setup_langconfig) {
		print "<img src=\"images/ok.png\" alt=\"Language config save OK\" title=\"Language config save OK\" /> Language configuration settings saved.<br />";
		}
	else {
		print "<img src=\"images/nok.png\" alt=\"Language config save NOK\" title=\"Language config save NOK\" /> Language configuration settings could not be saved.<br />";
	}
	if (!$setup_config || !$setup_langconfig) {
		print "<form method=\"post\" name=\"next\" action=\"".$_SERVER["SCRIPT_NAME"]."\">";
		print "<input type=\"hidden\" name=\"step\" value=\"7\">";
		print "<br />";
		print "<input type=\"submit\"  value=\"Restart\">";
		print "</form>";
	}
	else {
		print "<br />";
		print "Your system has been setup. Please click on the link below.<br /><br />";
		print "<a href=\"".$LOCATION."index.php\">Start Genmod</a>";
		session_destroy();
	}
}
if (isset($link) && $link) ((is_null($___mysqli_res = mysqli_close($link))) ? false : $___mysqli_res);
print "\n\t</div></body>\n</html>";
@session_write_close();
// Define the function to autoload the classes
function __autoload($classname) {
	global $GM_BASE_DIRECTORY;
	
	if (stristr($classname, "controller")) require_once($GM_BASE_DIRECTORY.strtolower("../includes/controllers/".str_ireplace("controller", "", $classname)."_ctrl.php"));
	else if (stristr($classname, "functions")) require_once($GM_BASE_DIRECTORY.strtolower("../includes/functions/functions_".str_ireplace("functions", "", $classname)."_class.php"));
	else require_once($GM_BASE_DIRECTORY.strtolower("../includes/classes/".$classname."_class.php"));
}

?>