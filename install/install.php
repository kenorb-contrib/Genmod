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
 * Copyright (C) 2002 to 2005  GM Development Team
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
 * @version $Id: install.php,v 1.1 2006/05/28 13:00:03 roland-d Exp $
 * @package Genmod
 * @subpackage Admin
 */

// NOTE: Checklist
// 1. PHP version
// 2. MySQL version
// 3. DB connectie details
// 4. DB structure
// 5. Admin user

$VERSION = "1.0";
$VERSION_RELEASE = "beta 3";
$min_php_version = "4.3";
$min_mysql_version = "4.0";
$stylesheet = "themes/standard/style.css";
$TEXT_DIRECTION = "rtl";
$action = "";

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
@import_request_variables("cgp");

// NOTE: Load the functions
require("install_functions.php");

// NOTE: Load the language
loadLanguage();

$setup_php = false;
$setup_mysql = false;
$setup_db = false;
$setup_config = false;
$error = "";

if ($step > 2) {
	if ($link = mysql_connect($DBHOST, $DBUSER, $DBPASS)) mysql_select_db($DBNAME);
	else {
		$step = 2;
		$error = $gm_lang["error"].": ". mysql_error();
	}
}

header("Content-Type: text/html; charset=UTF-8");
print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
print "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n\t<head>\n\t\t";
print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF8\" />\n\t\t";
print "<link rel=\"stylesheet\" href=\"install_style.css\" type=\"text/css\" media=\"all\"></link>\n\t";
print "<title>Genmod ".$VERSION." ".$VERSION_RELEASE." ".$gm_lang["install"]."</title>";
print "</head>\n\t<body>";
print "<div id=\"header\" class=\"".$TEXT_DIRECTION."\">";
print "<img src=\"images/header.gif\" alt=\"\"/>";
print "<span style=\"font-size: 26px; color: #DE0029; padding-left: 2em;\">".$gm_lang["setup_genmod"]."</span>";
print "</div>";
print "<div id=\"body\" class=\"".$TEXT_DIRECTION."\" style=\"padding: 5em;\">";
ShowProgress();
print "<br />";
if ($step == 1) {
	print $gm_lang["step1"];
	print "<br /><br />";
	
	if (phpversion() < $min_php_version) {
		print "<img src=\"images/nok.png\" alt=\"PHP version too low\"/> ";
		print "<b style=\"color: red;\">Genmod requires PHP version 4.3.0 or later.</b><br />\nYour server is running PHP version ".phpversion().". Please ask your server's Administrator to upgrade the PHP installation.";
		print "<br /><br />";
		$setup_php = false;
	}
	else {
		print "<img src=\"images/ok.png\" alt=\"PHP version OK\"/> ";
		print "Your PHP version meets the requirement for Genmod.<br />";
		$setup_php = true;
	}
	if (substr(mysql_get_server_info(), 0, strlen($min_mysql_version)-1) < $min_mysql_version) {
		print "<img src=\"images/nok.png\" alt=\"MySQL version too low\"/> ";
		print "<b style=\"color: red;\">Genmod requires MySQL version ".$min_mysql_version." or later.</b><br />\nYour server is running PHP version ".mysql_get_server_info().". Please ask your server's Administrator to upgrade the MySQL installation.";
		print "<br /><br />";
		$setup_mysql = false;
	}
	else {
		print "<img src=\"images/ok.png\" alt=\"MySQL version OK\"/> ";
		print "Your MySQL version meets the requirement for Genmod.<br />";
		$setup_mysql = true;
	}
	
	if ($setup_php && $setup_mysql) {
		print "<form method=\"post\" name=\"next\" action=\"".$_SERVER["SCRIPT_NAME"]."\">";
		print "<input type=\"hidden\" name=\"step\" value=\"2\">";
		print "<br />";
		print "<input type=\"submit\" value=\"".$gm_lang["next"]."\">";
		print "</form>";
		$setup_php = true;
	}
	else {
		print "<br /><br />";
		print "<img src=\"images/nok.png\" alt=\"Requirements not OK\"/> ";
		print "Unable to continue. Please update your system so that you meet all requirements.";
		print "<br /><br />";
		print "<span class=\"error\">The installation has been terminated.</span>";
	}
}

// NOTE: PHP Check

if ($step == 2) {
	print $gm_lang["step2"];
	print "<br /><br />";
	
	if (!empty($error)) print "<span class=\"error\">".$error."</span><br /><br />";
	print "<form method=\"post\" action=\"".$_SERVER["SCRIPT_NAME"]."\" name=\"loginform\">\n";
	print "<input type=\"hidden\" name=\"step\" value=\"3\"/>";
	print "<label class=\"label_form\">Database details</label><br style=\"clear: left;\"/>";
	print "<label class=\"label_form\" for=\"DBHOST\">".$gm_lang["DBHOST"]."</label>";
	print "<input class=\"input_form\" type=\"text\" id=\"DBHOST\" name=\"DBHOST\" value=\"localhost\" /><br style=\"clear: left;\"/>";
	print "<label class=\"label_form\" for=\"DBUSER\">".$gm_lang["DBUSER"]."</label>";
	print "<input class=\"input_form\" type=\"text\" id=\"DBUSER\" name=\"DBUSER\" value=\"".$DBUSER."\"/><br style=\"clear: left;\"/>";
	print "<label class=\"label_form\" for=\"DBPASS\">".$gm_lang["DBPASS"]."</label>";
	print "<input class=\"input_form\" type=\"password\" id=\"DBPASS\" name=\"DBPASS\" value=\"".$DBPASS."\" /><br style=\"clear: left;\"/>";
	print "<label class=\"label_form\" for=\"DBNAME\">".$gm_lang["DBNAME"]."</label>";
	print "<input class=\"input_form\" type=\"text\" id=\"DBNAME\" name=\"DBNAME\"";
	if (isset($DBNAME)) print "value=\"".$DBNAME."\"";
	else print "value=\"genmod\"";
	print " /><br style=\"clear: left;\"/>";
	print "<label class=\"label_form\" for=\"TBLPREFIX\">".$gm_lang["TBLPREFIX"]."</label>";
	print "<input class=\"input_form\" type=\"text\" id=\"TBLPREFIX\" name=\"TBLPREFIX\"";
	if (isset($TBLPREFIX)) print "value=\"".$TBLPREFIX."\"";
	else print "value=\"gm_\"";
	print " /><br style=\"clear: left;\"/>";
	print "<label class=\"label_form\" for=\"submit\">".$gm_lang["submit"]."</label>";
	print "<input class=\"input_form\" type=\"submit\" id=\"submit\" name=\"submit\" value=\"".$gm_lang["next"]."\"/><br style=\"clear: left;\"/>";
	print "</form>";

}
// NOTE: Verify database structure
if ($step == 3) {
	print $gm_lang["step3"];
	print "<br /><br />";
	
	$db_layout = CheckDBLayout();
	if (!$db_layout && !is_array($db_layout)) {
		print $gm_lang["create_database_first"];
		RestartButton();
	}
	else {
		if (count($db_layout) > 0) {
			print "<img src=\"images/nok.png\" alt=\"Database layout not OK\"/> ";
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
					FixDBLayout($db_layout);
					$db_layout = CheckDBLayout();
					if (count($db_layout) == 0) $setup_db = true;
					$loop++;
				}
			} while (count($db_layout) > 0);
			print "</div>";
		}
		if (empty($db_layout) && $setup_db) {
			print "<img src=\"images/ok.png\" alt=\"Database layout OK\" /> ";
			print "Your database layout has been checked and found correct.";
			print "<br />";
			$setup_db = true;
			print "<form method=\"post\" name=\"next\" action=\"".$_SERVER["SCRIPT_NAME"]."\">";
			print "<input type=\"hidden\" name=\"step\" value=\"4\">";
			print "<br />";
			print "<input type=\"submit\" value=\"".$gm_lang["next"]."\">";
			print "</form>";
		}
		else if (!$setup_db) {
			print "<img src=\"images/nok.png\" alt=\"Database layout not OK\" /> ";
			print "Unable to setup the database correctly. Please check if you have the correct priviliges.";
			print "<br /><br />";
			print "Run installation again after all priviliges has been set. ";
			RestartButton();
		}
	}
}
if ($step == 4) {
	print $gm_lang["step4"];
	print "<br /><br />";
	
	// NOTE: Request user details
	$sql = "SELECT COUNT(u_username) as admins FROM ".$TBLPREFIX."users WHERE u_canadmin = 'Y'";
	$res = mysql_query($sql);
	$row = mysql_fetch_row($res);
	
	if ($action == "createadminuser" && $row[0] == 0) {
		$user = array();
		$user["username"]=$username;
		$user["firstname"]=$firstname;
		$user["lastname"]=$lastname;
		$user["password"]=crypt($pass1);
		$user["canedit"] = array();
		$user["rootid"] = array();
		$user["gedcomid"] = array();
		$user["canadmin"]=true;
		$user["email"]=$emailadress;
		$user["verified"] = "yes";
		$user["verified_by_admin"] = "yes";
		$user["pwrequested"] = "";
		$user["theme"] = "";
		$user["theme"] = "Y";
		$user["language"] = $LANGUAGE;
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
		$au = addAdminUser($user);
		unset($_SESSION["action"]);
		if ($au) {
			print "<img src=\"images/ok.png\" alt=\"Create administrator account OK\" /> ";
			print $gm_lang["user_created"];
			print "<br />";
			$_SESSION["gm_user"]=$username;
		}
		else {
			print "<img src=\"images/nok.png\" alt=\"Create administrator account NOK\" /> ";
			print $gm_lang["user_create_error"];
			print "<br />";
		}
	}
	// NOTE: Request user details
	$sql = "SELECT COUNT(u_username) as admins FROM ".$TBLPREFIX."users WHERE u_canadmin = 'Y'";
	$res = mysql_query($sql);
	$row = mysql_fetch_row($res);
	
	if ($row[0] > 0) {
		print "<img src=\"images/ok.png\" alt=\"Administrator account OK\" /> ";
		print "An administrator account exists.";
		print "<form method=\"post\" name=\"next\" action=\"".$_SERVER["SCRIPT_NAME"]."\">";
		print "<input type=\"hidden\" name=\"step\" value=\"5\">";
		print "<br />";
		print "<input type=\"submit\" value=\"".$gm_lang["next"]."\">";
		print "</form>";
		// session_destroy();
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Administrator account NOK\" /> ";
		print "An administrator account does not yet exist.";
		print "<br />";
		?>
		<script language="JavaScript" type="text/javascript">
			function checkform(frm) {
				if (frm.username.value=="") {
					alert("<?php print $gm_lang["enter_username"]; ?>");
					frm.username.focus();
					return false;
				}
				if (frm.firstname.value=="") {
					alert("<?php print $gm_lang["enter_fullname"]; ?>");
					frm.firstname.focus();
					return false;
				}
				if (frm.lastname.value=="") {
					alert("<?php print $gm_lang["enter_fullname"]; ?>");
					frm.lastname.focus();
					return false;
				}
				if (frm.pass1.value=="") {
					alert("<?php print $gm_lang["enter_password"]; ?>");
					frm.pass1.focus();
					return false;
				}
				if (frm.pass2.value=="") {
					alert("<?php print $gm_lang["confirm_password"]; ?>");
					frm.pass2.focus();
					return false;
				}
				if (frm.pass1.value != frm.pass2.value) {
					alert("<?php print $gm_lang["password_mismatch"]; ?>");
					frm.pass1.focus();
					return false;
				}
				return true;
			}
		</script>
		<br />
		<form method="post" action="<?php print $_SERVER["SCRIPT_NAME"];?>" onsubmit="return checkform(this);">
			<input type="hidden" name="action" value="createadminuser" />
			<input type="hidden" name="step" value="4" />
			<label class="label_form"><?php print $gm_lang["username"];?></label>
			<input class="input_form" type="text" name="username" /><br style="clear: left;" />
			<label class="label_form"><?php print $gm_lang["firstname"];?></label>
			<input class="input_form" type="text" name="firstname" /><br style="clear: left;" />
			<label class="label_form"><?php print $gm_lang["lastname"];?></label>
			<input class="input_form" type="text" name="lastname" /><br style="clear: left;" />
			<label class="label_form"><?php print $gm_lang["password"];?></label>
			<input class="input_form" type="password" name="pass1" /><br style="clear: left;" />
			<label class="label_form"><?php print $gm_lang["confirm"];?></label>
			<input class="input_form" type="password" name="pass2" /><br style="clear: left;" />
			<label class="label_form"><?php print $gm_lang["emailadress"];?></label>
			<input class="input_form" type="text" name="emailadress" size="45" /><br style="clear: left;" />
			<input class="input_form" type="submit" value="<?php print $gm_lang["create_user"]; ?>" />
		</form>
		<?php
		
	}
}

if ($step == 5) {
	print $gm_lang["step5"];
	print "<br /><br />";
	
	// NOTE: Load the language settings file
	$Filename = "../index/lang_settings.php";
	if (!file_exists($Filename)) include("install_lang_settings.php");
	else include($Filename);
	
	print "<form method=\"post\" name=\"next\" action=\"".$_SERVER["SCRIPT_NAME"]."\">"; 
	print "<table class=\"facts_table\">";
	// NOTE: Build a sorted list of language names in the currently active language
	// NOTE: Also build a list of active languages so we can compare which have been turned off
	foreach ($language_settings as $key => $value){
		$d_LangName = "lang_name_".$key;
		$SortedLangs[$key] = $gm_lang[$d_LangName];
		if ($value["gm_lang_use"]) $_SESSION["ActiveLangs"][] = $key;
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
				print "<td class=\"shade1\"><input type=\"checkbox\" name=\"NEW_LANGS[".$LangName."]\" value=\"".$LangName."\" ";
				if ($language_settings[$LangName]["gm_lang_use"] || $LangName == "english") print "checked=\"checked\"";
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
	print "<input type=\"submit\" value=\"".$gm_lang["next"]."\">";
	print "</form>";
}

if ($step ==  6) {
	print $gm_lang["step6"];
	print "<br /><br />";
	include("install_lang_settings.php");
	
	$output = array();
	$output["lang"] = true;
	$output["help"] = true;
	
	// NOTE: Retrieve the new language array from the session
	if (isset($_SESSION["NEW_LANGS"])) $NEW_LANGS = $_SESSION["NEW_LANGS"];
	
	// NOTE: Sort the new language array if it exists, otherwise create an empty array
	if(!isset($NEW_LANGS)) $NEW_LANGS = array();
	else sort($NEW_LANGS);
	
	// NOTE: Make a copy of the languages in the session so we can later store them
	$_SESSION["GM_LANGS"] = $NEW_LANGS;
	$_SESSION["GM_LANGS"][] = "english";
	
	// NOTE: Get the array of removed languages
	if (!isset($_SESSION["RemoveLangs"])) {
		$_SESSION["RemoveLangs"] = array_diff($_SESSION["ActiveLangs"], $NEW_LANGS);
	}
	// NOTE: Remove the languages no longer used
	foreach ($_SESSION["RemoveLangs"] as $key => $removelang) {
		if ($removelang != "english") {
			if (RemoveLanguage($removelang)) {
				print "<img src=\"images/ok.png\" alt=\"Language removal OK\" /> ";
				print "Language ".$gm_lang["lang_name_".$removelang]." has been removed.";
				print "<br />";
			}
			else {
				print "<img src=\"images/nok.png\" alt=\"Language removal NOK\" /> ";
				print "Language ".$gm_lang["lang_name_".$removelang]." could not be removed.";
				print "<br />";
			}
		}
	}
	
	if (count($NEW_LANGS) == 0) {
		print "<br />";
		print "No languages to import.";
		print "<br />";
	}
	else {
		if (!in_array($NEW_LANGS[0], $_SESSION["ActiveLangs"])) {
			if ($NEW_LANGS[0] == "english") {
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
							$sql = "INSERT INTO ".$TBLPREFIX."language (lg_string, lg_english, lg_last_update_date, lg_last_update_by) VALUES ('".mysql_real_escape_string($data[0])."', '".mysql_real_escape_string($data[1])."', '".time()."', 'install')";
							if (!$result = mysql_query($sql)) {
								$output["lang"] = false;
								WriteToLog("Could not add language string ".$line." for language English to table ", "W");
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
							$sql = "INSERT INTO ".$TBLPREFIX."language_help (lg_string, lg_english, lg_last_update_date, lg_last_update_by) VALUES ('".mysql_real_escape_string($data[0])."', '".mysql_real_escape_string($data[1])."', '".time()."', 'install')";
							if (!$result = mysql_query($sql)) {
								$output["help"] = false;
								WriteToLog("Could not add language help string ".$line." for language English to table ", "W");
							}
						}
					}
				}
			}
			else $output = StoreLanguage($NEW_LANGS[0]);
				
			foreach ($output as $type => $result) {
				if ($result) {
					print "<img src=\"images/ok.png\" alt=\"Language import OK\" /> ";
					if ($type == "lang") print "Language ";
					else if ($type == "help") print "Help ";
					print $gm_lang["lang_name_".$NEW_LANGS[0]]." imported succesfully.";
					print "<br />";
				}
				else {
					print "<img src=\"images/nok.png\" alt=\"Language import NOK\" /> ";
					if ($type == "lang") print "Language ";
					else if ($type == "help") print "Help ";
					print $gm_lang["lang_name_".$NEW_LANGS[0]]." imported failed.";
					print "<br />";
				}
			}
		}
		else {
			print "<img src=\"images/ok.png\" alt=\"Language already exists\" /> ";
			print "The language ".$gm_lang["lang_name_".$NEW_LANGS[0]]." already exists. No need to import.<br />";
		}
		
		// NOTE: Remove the language so it is not processed again
		unset($_SESSION["NEW_LANGS"][$NEW_LANGS[0]]);
	}
	print "<form method=\"post\" name=\"next\" action=\"".$_SERVER["SCRIPT_NAME"]."\">";
	print "<input type=\"hidden\" name=\"step\" value=\"";
	if (count($_SESSION["NEW_LANGS"]) == 0) print "7";
	else print "6";
	print "\">";
	print "<br />";
	print "<input type=\"submit\" value=\"".$gm_lang["next"]."\">";
	print "</form>";
}

if ($step == 7) {
	print $gm_lang["step7"];
	print "<br /><br />";
	
	// NOTE: Write the configuration file
	$CONFIG["GM_BASE_DIRECTORY"] = "";
	$CONFIG["DBHOST"] = $_SESSION["DBHOST"];
	$CONFIG["DBUSER"] = $_SESSION["DBUSER"];
	$CONFIG["DBPASS"] = $_SESSION["DBPASS"];
	$CONFIG["DBNAME"] = $_SESSION["DBNAME"];
	$CONFIG["DBPERSIST"] = true;
	$CONFIG["TBLPREFIX"] = $_SESSION["TBLPREFIX"];
	$CONFIG["INDEX_DIRECTORY"] = "./index/";
	$CONFIG["AUTHENTICATION_MODULE"] = "authentication.php";
	$CONFIG["GM_STORE_MESSAGES"] = true;
	$CONFIG["GM_SIMPLE_MAIL"] = true;
	$CONFIG["USE_REGISTRATION_MODULE"] = true;
	$CONFIG["REQUIRE_ADMIN_AUTH_REGISTRATION"] = true;
	$CONFIG["ALLOW_USER_THEMES"] = true;
	$CONFIG["ALLOW_CHANGE_GEDCOM"] = true;
	$CONFIG["GM_SESSION_SAVE_PATH"] = "";
	$CONFIG["GM_SESSION_TIME"] = "7200";
	$CONFIG["SERVER_URL"] = "http://".$_SERVER["SERVER_NAME"].substr_replace("install", "", dirname($_SERVER["SCRIPT_NAME"]))."/";
	$CONFIG["LOGIN_URL"] = "";
	$CONFIG["MAX_VIEWS"] = "100";
	$CONFIG["MAX_VIEW_TIME"] = "0";
	$CONFIG["GM_MEMORY_LIMIT"] = "32M";
	$CONFIG["ALLOW_REMEMBER_ME"] = true;
	$CONFIG["CONFIG_VERSION"] = "1.0";
	$CONFIG["NEWS_TYPE"] = "Normal";
	$CONFIG["PROXY_ADDRESS"] = "";
	$CONFIG["PROXY_PORT"] = "";
	$CONFIG["CONFIGURED"] = true;
	$CONFIG_PARMS[$CONFIG["SERVER_URL"]] = $CONFIG;
	if (StoreConfig()) {
		print "<img src=\"images/ok.png\" alt=\"Configuration save OK\" /> Configuration file saved.<br />";
		$setup_config = true;
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Configuration save NOK\" /> Configuration file could not be saved.<br />";
		print "<span class=\"error\">Most likely the file is not writeable.</span>";
	}
	
	// NOTE: Save the languages the user has chosen to have active on the website
	$Filename = "../index/lang_settings.php";
	if (!file_exists($Filename)) copy("../includes/lang_settings_std.php", $Filename);
	
	// Set the chosen languages to active
	foreach ($_SESSION["GM_LANGS"] as $key => $name) {
		$gm_lang_use[$name] = true;
	}
	
	require("install_lang_settings.php");
	if ($file_array = file($Filename)) {
		@copy($Filename, $Filename . ".old");
		if ($fp = @fopen($Filename, "w")) {
			for ($x = 0; $x < count($file_array); $x++) {
				fwrite($fp, $file_array[$x]);
				$dDummy00 = trim($file_array[$x]);
				if ($dDummy00 == "//-- NEVER manually delete or edit this entry and every line below this entry! --START--//") break;
			}
			fwrite($fp, "\r\n");
			fwrite($fp, "// Array definition of language_settings\r\n");
			fwrite($fp, "\$language_settings = array();\r\n");
			foreach ($language_settings as $key => $value) {
				fwrite($fp, "\r\n");
				fwrite($fp, "//-- settings for " . $key . "\r\n");
				fwrite($fp, "\$lang = array();\r\n");
				fwrite($fp, "\$lang[\"gm_langname\"]    = \"" . $value["gm_langname"] . "\";\r\n");
				fwrite($fp, "\$lang[\"gm_lang_use\"]    = ");
				if ($gm_lang_use[$key]) fwrite($fp, "true"); else fwrite($fp, "false");
				fwrite($fp, ";\r\n");
				fwrite($fp, "\$lang[\"gm_lang\"]    = \"" . $value["gm_lang"] . "\";\r\n");
				fwrite($fp, "\$lang[\"lang_short_cut\"]    = \"" . $value["lang_short_cut"] . "\";\r\n");
				fwrite($fp, "\$lang[\"langcode\"]    = \"" . $value["langcode"] . "\";\r\n");
				fwrite($fp, "\$lang[\"gm_language\"]    = \"" . $value["gm_language"] . "\";\r\n");
				fwrite($fp, "\$lang[\"confighelpfile\"]    = \"" . $value["confighelpfile"] . "\";\r\n");
				fwrite($fp, "\$lang[\"helptextfile\"]    = \"" . $value["helptextfile"] . "\";\r\n");
				fwrite($fp, "\$lang[\"flagsfile\"]    = \"" . $value["flagsfile"] . "\";\r\n");
				fwrite($fp, "\$lang[\"factsfile\"]    = \"" . $value["factsfile"] . "\";\r\n");
				fwrite($fp, "\$lang[\"DATE_FORMAT\"]    = \"" . $value["DATE_FORMAT"] . "\";\r\n");
				fwrite($fp, "\$lang[\"TIME_FORMAT\"]    = \"" . $value["TIME_FORMAT_array"] . "\";\r\n");
				fwrite($fp, "\$lang[\"WEEK_START\"]    = \"" . $value["WEEK_START_array"] . "\";\r\n");
				fwrite($fp, "\$lang[\"TEXT_DIRECTION\"]    = \"" . $value["TEXT_DIRECTION"] . "\";\r\n");
				fwrite($fp, "\$lang[\"NAME_REVERSE\"]    = \"" . $value["NAME_REVERSE"] . "\";\r\n");
				fwrite($fp, "\$lang[\"ALPHABET_upper\"]    = \"" . $value["ALPHABET_upper"] . "\";\r\n");
				fwrite($fp, "\$lang[\"ALPHABET_lower\"]    = \"" . $value["ALPHABET_lower"] . "\";\r\n");
				fwrite($fp, "\$language_settings[\"" . $key . "\"]  = \$lang;\r\n");
			}
			$end_found = false;
			for ($x = 0; $x < count($file_array); $x++) {
				$dDummy00 = trim($file_array[$x]);
				if ($dDummy00 == "//-- NEVER manually delete or edit this entry and every line above this entry! --END--//"){fwrite($fp, "\r\n"); $end_found = true;}
				if ($end_found) fwrite($fp, $file_array[$x]);
			}
			if (fclose($fp)) {
				print "<img src=\"images/ok.png\" alt=\"Language config save OK\" /> Language configuration file saved.<br />";
				$setup_langconfig = true;
			}
		}
		else {
			print "<img src=\"images/nok.png\" alt=\"Language config save NOK\" /> Language configuration file could not be saved.<br />";
			$setup_langconfig = false;
		}
	}
	else {
		print "<img src=\"images/nok.png\" alt=\"Language config read NOK\" /> Language configuration file could not be read.<br />";
		$setup_langconfig = false;
	}
	if (!$setup_config || !$setup_langconfig) {
		print "<form method=\"post\" name=\"next\" action=\"".$_SERVER["SCRIPT_NAME"]."\">";
		print "<input type=\"hidden\" name=\"step\" value=\"7\">";
		print "<br />";
		print "<input type=\"submit\" value=\"Restart\">";
		print "</form>";
	}
	else {
		print "<br />";
		print "Your system has been setup. Please delete the installation folder and click on the link below.<br /><br />";
		print "<a href=\"http://".$_SERVER["SERVER_NAME"]."/index.php\">Start Genmod</a>";
		session_destroy();
	}
}

mysql_close($link);
print "\n\t</div></body>\n</html>";
session_write_close();
?>
