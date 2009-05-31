<?php
/**
 * Online UI for editing config.php site configuration variables
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
 * This Page Is Valid XHTML 1.0 Transitional! > 17 September 2005
 *
 * @package Genmod
 * @subpackage Admin
 * @see config.php
 * @version $Id: editconfig.php,v 1.26 2006/05/28 13:00:03 roland-d Exp $
 */
 
/**
 * Inclusion of the configuration file
*/
require "config.php";

/**
 * Inclusion of the DB functions
*/
if (!defined("DB_ERROR")) require_once('includes/DB.php');

$error_indexdir = false;
$error_db = false;
$error_url = false;

if (empty($action)) $action="";
if (!isset($LOGIN_URL)) $LOGIN_URL = "";

print_header($gm_lang["configure_head"]);
if ($action=="update") {
	if (!isset($_POST)) $_POST = $HTTP_POST_VARS;
	$boolarray = array();
	$boolarray["yes"]="1";
	$boolarray["no"]="0";
	$boolarray[false]="0";
	$boolarray[true]="1";
	print $gm_lang["performing_update"];
	print "<br />";
	print $gm_lang["config_file_read"]."<br />";
	if (empty($_POST["NEW_SERVER_URL"])) $error_url = true;
	else {
		if (preg_match("'://'", $_POST["NEW_SERVER_URL"])==0) $_POST["NEW_SERVER_URL"] = "http://".$_POST["NEW_SERVER_URL"];
		if (substr($_POST["NEW_SERVER_URL"], -1) != "/") $_POST["NEW_SERVER_URL"] .= "/";
		if (strlen($_POST["NEW_SERVER_URL"]) > 1 && substr($_POST["NEW_SERVER_URL"],-2) == "//") $_POST["NEW_SERVER_URL"] = substr($_POST["NEW_SERVER_URL"], 0, -1);
	}
	$CONFIG["GM_BASE_DIRECTORY"] = "";
	$CONFIG["DBHOST"] = $_POST["NEW_DBHOST"];
	$CONFIG["DBUSER"] = $_POST["NEW_DBUSER"];
	if (!empty($_POST["NEW_DBPASS"])) $CONFIG["DBPASS"] = $_POST["NEW_DBPASS"];
	$CONFIG["DBNAME"] = $_POST["NEW_DBNAME"];
	$CONFIG["DBPERSIST"] = $boolarray[$_POST["NEW_DBPERSIST"]];
	$CONFIG["TBLPREFIX"] = $_POST["NEW_TBLPREFIX"];
	$_POST["NEW_INDEX_DIRECTORY"] = preg_replace('/\\\/','/',$_POST["NEW_INDEX_DIRECTORY"]);
	$CONFIG["INDEX_DIRECTORY"] = $_POST["NEW_INDEX_DIRECTORY"];
	$CONFIG["AUTHENTICATION_MODULE"] = "authentication.php";
	$CONFIG["GM_STORE_MESSAGES"] = $boolarray[$_POST["NEW_GM_STORE_MESSAGES"]];
	$CONFIG["GM_SIMPLE_MAIL"] = $boolarray[$_POST["NEW_GM_SIMPLE_MAIL"]];
	$CONFIG["USE_REGISTRATION_MODULE"] = $boolarray[$_POST["NEW_USE_REGISTRATION_MODULE"]];
	$CONFIG["REQUIRE_ADMIN_AUTH_REGISTRATION"] = $boolarray[$_POST["NEW_REQUIRE_ADMIN_AUTH_REGISTRATION"]];
	$CONFIG["ALLOW_USER_THEMES"] = $boolarray[$_POST["NEW_ALLOW_USER_THEMES"]];
	$CONFIG["ALLOW_CHANGE_GEDCOM"] = $boolarray[$_POST["NEW_ALLOW_CHANGE_GEDCOM"]];
	$CONFIG["GM_SESSION_SAVE_PATH"] = $_POST["NEW_GM_SESSION_SAVE_PATH"];
	$CONFIG["GM_SESSION_TIME"] = $_POST["NEW_GM_SESSION_TIME"];
	$CONFIG["SERVER_URL"] = $_POST["NEW_SERVER_URL"];
	$CONFIG["LOGIN_URL"] = $_POST["NEW_LOGIN_URL"];
	$CONFIG["MAX_VIEWS"] = $_POST["NEW_MAX_VIEWS"];
	$CONFIG["MAX_VIEW_TIME"] = $_POST["NEW_MAX_VIEW_TIME"];
	$CONFIG["GM_MEMORY_LIMIT"] = $_POST["NEW_GM_MEMORY_LIMIT"];
	$CONFIG["ALLOW_REMEMBER_ME"] = $boolarray[$_POST["NEW_ALLOW_REMEMBER_ME"]];
	$CONFIG["CONFIG_VERSION"] = "1.0";
	$CONFIG["NEWS_TYPE"] = $_POST["NEW_NEWS_TYPE"];
	$CONFIG["PROXY_ADDRESS"] = $_POST["NEW_PROXY_ADDRESS"];
	$CONFIG["PROXY_PORT"] = $_POST["NEW_PROXY_PORT"];
	$CONFIG["CONFIGURED"] = false;
	
	$DBHOST = $_POST["NEW_DBHOST"];
	$DBUSER = $_POST["NEW_DBUSER"];
	$DBNAME = $_POST["NEW_DBNAME"];
	if (!empty($_POST["NEW_DBPASS"])) $DBPASS = $_POST["NEW_DBPASS"];
	
	$CONFIGURED = true;
	$CONFIG["CONFIGURED"] = true;
	
	//-- Perform validation checks, if not the first site defined
	if (isset($CONFIG_PARMS)) {
		foreach($CONFIG_PARMS as $site=>$parms) {
			if (($parms["INDEX_DIRECTORY"] == $CONFIG["INDEX_DIRECTORY"]) && $site != $CONFIG["SERVER_URL"]) $error_indexdir = true;
			if (($parms["DBNAME"] == $CONFIG["DBNAME"] && $parms["TBLPREFIX"] == $CONFIG["TBLPREFIX"]) && $site != $CONFIG["SERVER_URL"]) $error_db = true;
		}
	}
		
	if (!$error_db && !$error_indexdir && !$error_url) {
		$CONFIG_PARMS[$CONFIG["SERVER_URL"]] = $CONFIG;
		
		if (!isset($download)) {
			if (!store_config()) {
					print "<span class=\"error\">";
					print $gm_lang["gm_config_write_error"];
					print "<br /></span>\n";
			}
			else {
				if ($CONFIGURED) print "<script language=\"JavaScript\" type=\"text/javascript\">\nwindow.location = 'editconfig.php';\n</script>\n";
			}
		}
		else {
			$_SESSION["config.php"]=$configtext;
			print "<br /><br /><a href=\"config_download.php?file=config.php\">";
			print $gm_lang["download_here"];
			print "</a><br /><br />\n";
		}
		
		// Save the languages the user has chosen to have active on the website
		$Filename = $INDEX_DIRECTORY . "lang_settings.php";
		if (!file_exists($Filename)) copy("includes/lang_settings_std.php", $Filename);
		
		// Set the chosen languages to active
		foreach ($NEW_LANGS as $key => $name) {
			$gm_lang_use[$name] = true;
			if ($name["gm_lang_use"] && $name != "english") {
				storeLanguage($name);
			}
		}
		
		// Set the other languages to non-active
		foreach ($gm_lang_use as $name => $value) {
			if (!isset($NEW_LANGS[$name])) {
				$gm_lang_use[$name] = false;
				removeLanguage($name);
			}
		}
		$error = "";
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
					fwrite($fp, "//-- settings for " . $languages[$key] . "\r\n");
					fwrite($fp, "\$lang = array();\r\n");
					fwrite($fp, "\$lang[\"gm_langname\"]    = \"" . $languages[$key] . "\";\r\n");
					fwrite($fp, "\$lang[\"gm_lang_use\"]    = ");
					if ($gm_lang_use[$key]) fwrite($fp, "true"); else fwrite($fp, "false");
					fwrite($fp, ";\r\n");
					fwrite($fp, "\$lang[\"gm_lang\"]    = \"" . $gm_lang[$key] . "\";\r\n");
					fwrite($fp, "\$lang[\"lang_short_cut\"]    = \"" . $lang_short_cut[$key] . "\";\r\n");
					fwrite($fp, "\$lang[\"langcode\"]    = \"" . $lang_langcode[$key] . "\";\r\n");
					fwrite($fp, "\$lang[\"gm_language\"]    = \"" . $gm_language[$key] . "\";\r\n");
					fwrite($fp, "\$lang[\"confighelpfile\"]    = \"" . $confighelpfile[$key] . "\";\r\n");
					fwrite($fp, "\$lang[\"helptextfile\"]    = \"" . $helptextfile[$key] . "\";\r\n");
					fwrite($fp, "\$lang[\"flagsfile\"]    = \"" . $flagsfile[$key] . "\";\r\n");
					fwrite($fp, "\$lang[\"factsfile\"]    = \"" . $factsfile[$key] . "\";\r\n");
					fwrite($fp, "\$lang[\"DATE_FORMAT\"]    = \"" . $DATE_FORMAT_array[$key] . "\";\r\n");
					fwrite($fp, "\$lang[\"TIME_FORMAT\"]    = \"" . $TIME_FORMAT_array[$key] . "\";\r\n");
					fwrite($fp, "\$lang[\"WEEK_START\"]    = \"" . $WEEK_START_array[$key] . "\";\r\n");
					fwrite($fp, "\$lang[\"TEXT_DIRECTION\"]    = \"" . $TEXT_DIRECTION_array[$key] . "\";\r\n");
					fwrite($fp, "\$lang[\"NAME_REVERSE\"]    = ");
					if ($NAME_REVERSE_array[$key]) fwrite($fp, "true"); else fwrite($fp, "false");
					fwrite($fp, ";\r\n");
					fwrite($fp, "\$lang[\"ALPHABET_upper\"]    = \"" . $ALPHABET_upper[$key] . "\";\r\n");
					fwrite($fp, "\$lang[\"ALPHABET_lower\"]    = \"" . $ALPHABET_lower[$key] . "\";\r\n");
					fwrite($fp, "\$language_settings[\"" . $languages[$key] . "\"]  = \$lang;\r\n");
				}
				$end_found = false;
				for ($x = 0; $x < count($file_array); $x++) {
					$dDummy00 = trim($file_array[$x]);
					if ($dDummy00 == "//-- NEVER manually delete or edit this entry and every line above this entry! --END--//"){fwrite($fp, "\r\n"); $end_found = true;}
					if ($end_found) fwrite($fp, $file_array[$x]);
				}
				fclose($fp);
			}
			else $error = "lang_config_write_error";
		}
		else $error = "lang_set_file_read_error";

		if ($error != "") {
		    print "<span class=\"error\">" . $gm_lang[$error] . "</span><br /><br />";
		}
	}
	foreach($_POST as $key=>$value) {
		$key=preg_replace("/NEW_/", "", $key);
		if ($value=='yes') $$key=true;
		else if ($value=='no') $$key=false;
		else $$key=$value;
	}
}

?>
<script language="JavaScript" type="text/javascript">
<!--
	var helpWin;
	function helpPopup(which) {
		if ((!helpWin)||(helpWin.closed)) helpWin = window.open('editconfig_help.php?help='+which,'','left=50,top=50,width=500,height=320,resizable=1,scrollbars=1');
		else helpWin.location = 'editconfig_help.php?help='+which;
		return false;
	}
	function getHelp(which) {
		if ((helpWin)&&(!helpWin.closed)) helpWin.location='editconfig_help.php?help='+which;
	}
	function closeHelp() {
		if (helpWin) helpWin.close();
	}
	//-->
</script>
<form method="post" name="configform" action="editconfig.php">
<?php
	if (isset($security)) {
		if (isset($security_user)) $DBUSER = $security_user;
		if (isset($security_check)) $DBPASS = $security_check;
	}
	if (!check_db() && $CONFIGURED) {
		print "<span class=\"error\">";
		print $gm_lang["db_setup_bad"];
		print "</span><br />";
		print "<span class=\"error\">".$DBCONN->getMessage()." ".$DBCONN->getUserInfo()."</span><br />";
		if ($CONFIGURED==true) {
			//-- force the incoming user to enter the database password before they can configure the site for security.
			if (!isset($_POST["security_check"]) || !isset($_POST["security_user"]) || (($_POST["security_check"]!=$DBPASS)&&($_POST["security_user"]==$DBUSER))) {
				print "<br /><br />";
				print_text("enter_db_pass");
				print "<br />";
				print $gm_lang["DBUSER"];
				print " <input type=\"hidden\" name=\"qsecurity\" value=\"yes\" />";
				print " <input type=\"text\" name=\"security_user\" /><br />\n";
				print $gm_lang["DBPASS"];
				print " <input type=\"password\" name=\"security_check\" /><br />\n";
				print "<input type=\"submit\" value=\"";
				print $gm_lang["login"];
				print "\" />\n";
				print "</form>\n";
				print_footer();
				exit;
			}
		}
	}
	print "<input type=\"hidden\" name=\"action\" value=\"update\" />";
	print "<table class=\"facts_table\">";
	print "<tr><td class=\"topbottombar\" colspan=\"2\">";
	print "<span class=\"subheaders\">";
	print $gm_lang["configure"];
	print "</span><br /><br />";
	print "<div class=\"".$TEXT_DIRECTION." wrap\">".$gm_lang["welcome"];
	print "<br />";
	print $gm_lang["review_readme"];
	print_text("return_editconfig");
	if ($CONFIGURED) {
		print "<a href=\"editgedcoms.php\"><b>";
		print $gm_lang["admin_gedcoms"];
		print "</b></a><br /><br />\n";
	}
	$i = 0;
	print "</div></td></tr>";
	?>
	<table class="facts_table">
	<tr>
		<td class="shade2"><?php print_help_link("DBHOST_help", "qm", "DBHOST"); print $gm_lang["DBHOST"];?></td>
		<td class="shade1"><input type="text" dir="ltr" name="NEW_DBHOST" value="<?php print $DBHOST?>" size="40" tabindex="<?php $i++; print $i?>" />
		</td>
	</tr>
	<tr>
		<td class="shade2"><?php print_help_link("DBUSER_help", "qm", "DBUSER"); print $gm_lang["DBUSER"];?></td>
		<td class="shade1"><input type="text" name="NEW_DBUSER" value="<?php print $DBUSER?>" size="40" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2"><?php print_help_link("DBPASS_help", "qm", "DBPASS"); print $gm_lang["DBPASS"];?></td>
		<td class="shade1"><input type="password" name="NEW_DBPASS" value="" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2"><?php print_help_link("DBNAME_help", "qm", "DBNAME"); print $gm_lang["DBNAME"];?></td>
		<td class="shade1"><input type="text" name="NEW_DBNAME" value="<?php print $DBNAME?>" size="40" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 width20 wrap"><?php print_help_link("DBPERSIST_help", "qm", "DBPERSIST"); print $gm_lang["DBPERSIST"];?></td>
		<td class="shade1"><select name="NEW_DBPERSIST" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($DBPERSIST) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$DBPERSIST) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2"><?php print_help_link("TBLPREFIX_help", "qm", "TBLPREFIX"); print $gm_lang["TBLPREFIX"];?></td>
		<td class="shade1"><input type="text" name="NEW_TBLPREFIX" value="<?php print $TBLPREFIX?>" size="40" tabindex="<?php $i++; print $i?>" />
		<?php if ($error_db) print "<span class=\"error\">".$gm_lang["duplicatedb"]."</span>"; ?>
		</td>
	</tr>
	<tr>
		<td class="shade2 width20 wrap"><?php print_help_link("ALLOW_CHANGE_GEDCOM_help", "qm", "ALLOW_CHANGE_GEDCOM"); print $gm_lang["ALLOW_CHANGE_GEDCOM"];?></td>
		<td class="shade1"><select name="NEW_ALLOW_CHANGE_GEDCOM" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($ALLOW_CHANGE_GEDCOM) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$ALLOW_CHANGE_GEDCOM) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><?php print_help_link("INDEX_DIRECTORY_help", "qm", "INDEX_DIRECTORY"); print $gm_lang["INDEX_DIRECTORY"];?></td>
		<td class="shade1"><input type="text" size="50" name="NEW_INDEX_DIRECTORY" value="<?php print $INDEX_DIRECTORY?>" dir="ltr" tabindex="<?php $i++; print $i?>" />
		<?php if ($error_indexdir) print "<span class=\"error\">".$gm_lang["duplicateindexdir"]."</span>"; ?>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><?php print_help_link("GM_STORE_MESSAGES_help", "qm", "GM_STORE_MESSAGES"); print $gm_lang["GM_STORE_MESSAGES"];?></td>
		<td class="shade1"><select name="NEW_GM_STORE_MESSAGES" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($GM_STORE_MESSAGES) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$GM_STORE_MESSAGES) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>

	<tr>
		<td class="shade2 wrap"><?php print_help_link("USE_REGISTRATION_MODULE_help", "qm", "USE_REGISTRATION_MODULE"); print $gm_lang["USE_REGISTRATION_MODULE"];?></td>
		<td class="shade1"><select name="NEW_USE_REGISTRATION_MODULE" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($USE_REGISTRATION_MODULE) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$USE_REGISTRATION_MODULE) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>

 	<tr>
 		<td class="shade2 wrap"><?php print_help_link("REQUIRE_ADMIN_AUTH_REGISTRATION_help", "qm", "REQUIRE_ADMIN_AUTH_REGISTRATION"); print $gm_lang["REQUIRE_ADMIN_AUTH_REGISTRATION"];?></td>
 		<td class="shade1"><select name="NEW_REQUIRE_ADMIN_AUTH_REGISTRATION" tabindex="<?php $i++; print $i?>">
 				<option value="yes" <?php if ($REQUIRE_ADMIN_AUTH_REGISTRATION) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
 				<option value="no" <?php if (!$REQUIRE_ADMIN_AUTH_REGISTRATION) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
 		</td>
 	</tr>

	<tr>
		<td class="shade2 wrap"><?php print_help_link("GM_SIMPLE_MAIL_help", "qm", "GM_SIMPLE_MAIL"); print $gm_lang["GM_SIMPLE_MAIL"];?></td>
		<td class="shade1"><select name="NEW_GM_SIMPLE_MAIL" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($GM_SIMPLE_MAIL) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$GM_SIMPLE_MAIL) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>

	<tr>
		<td class="shade2 wrap"><?php print_help_link("ALLOW_USER_THEMES_help", "qm", "ALLOW_USER_THEMES"); print $gm_lang["ALLOW_USER_THEMES"];?></td>
		<td class="shade1"><select name="NEW_ALLOW_USER_THEMES" tabindex="<?php $i++; print $i?>">
				<option value="yes" <?php if ($ALLOW_USER_THEMES) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
				<option value="no" <?php if (!$ALLOW_USER_THEMES) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>

	<tr>
		<td class="shade2 wrap"><?php print_help_link("NEWS_TYPE_help", "qm", "NEWS_TYPE"); print $gm_lang["NEWS_TYPE"];?></td>
		<td class="shade1"><select name="NEW_NEWS_TYPE" tabindex="<?php $i++; print $i?>">
				<option value="Normal" <?php if ($NEWS_TYPE == "Normal") print "selected=\"selected\""; ?>><?php print $gm_lang["normal"];?></option>
				<option value="Urgent" <?php if ($NEWS_TYPE == "Urgent") print "selected=\"selected\""; ?>><?php print $gm_lang["urgent"];?></option>
			</select>
		</td>
	</tr>

	<tr>
		<td class="shade2 wrap"><?php print_help_link("ALLOW_REMEMBER_ME_help", "qm", "ALLOW_REMEMBER_ME"); print $gm_lang["ALLOW_REMEMBER_ME"];?></td>
		<td class="shade1"><select name="NEW_ALLOW_REMEMBER_ME" tabindex="<?php $i++; print $i?>">
 				<option value="yes" <?php if ($ALLOW_REMEMBER_ME) print "selected=\"selected\""; ?>><?php print $gm_lang["yes"];?></option>
 				<option value="no" <?php if (!$ALLOW_REMEMBER_ME) print "selected=\"selected\""; ?>><?php print $gm_lang["no"];?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><?php print_help_link("LANG_SELECTION_help", "qm", "LANG_SELECTION"); print $gm_lang["LANG_SELECTION"];?></td>
		<td class="shade1">
			<table class="facts_table">
			<?php
			// NOTE: Build a sorted list of language names in the currently active language
			foreach ($language_settings as $key => $value){
				$d_LangName = "lang_name_".$key;
				$SortedLangs[$key] = $gm_lang[$d_LangName];
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
						if ($LangName == "english") print "checked=\"checked\" disabled=\"disabled\" ";
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
			?>
			</table>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><?php print_help_link("SERVER_URL_help", "qm", "SERVER_URL"); print $gm_lang["SERVER_URL"];?></td>
		<td class="shade1 wrap"><input type="text" name="NEW_SERVER_URL" value="<?php print $SERVER_URL?>" dir="ltr" tabindex="<?php $i++; print $i?>" size="100" 
		<?php if (isset($CONFIG["SERVER_URL"]) && isset($CONFIG_PARMS[$CONFIG["SERVER_URL"]])) print "READONLY"; ?> />
		<br /><?php
			if ($error_url) print print "<span class=\"error\">".$gm_lang["emptyserverurl"]."</span>";
			$GUESS_URL = stripslashes("http://".$_SERVER["SERVER_NAME"].dirname($SCRIPT_NAME)."/");
			print_text("server_url_note");
			?>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><?php print_help_link("LOGIN_URL_help", "qm", "LOGIN_URL"); print $gm_lang["LOGIN_URL"];?></td>
		<td class="shade1"><input type="text" name="NEW_LOGIN_URL" value="<?php print $LOGIN_URL?>" dir="ltr" tabindex="<?php $i++; print $i?>" size="100" />
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><?php print_help_link("PROXY_ADDRESS_help", "qm", "PROXY_ADDRESS"); print $gm_lang["PROXY_ADDRESS"];?></td>
		<td class="shade1"><input type="text" name="NEW_PROXY_ADDRESS" value="<?php print $PROXY_ADDRESS?>" dir="ltr" tabindex="<?php $i++; print $i?>" size="100" />
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><?php print_help_link("PROXY_PORT_help", "qm", "PROXY_PORT"); print $gm_lang["PROXY_PORT"];?></td>
		<td class="shade1"><input type="text" name="NEW_PROXY_PORT" value="<?php print $PROXY_PORT?>" dir="ltr" tabindex="<?php $i++; print $i?>" />
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><?php print_help_link("GM_SESSION_SAVE_PATH_help", "qm", "GM_SESSION_SAVE_PATH"); print $gm_lang["GM_SESSION_SAVE_PATH"];?></td>
		<td class="shade1"><input type="text" dir="ltr" size="50" name="NEW_GM_SESSION_SAVE_PATH" value="<?php print $GM_SESSION_SAVE_PATH?>" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><?php print_help_link("GM_SESSION_TIME_help", "qm", "GM_SESSION_TIME"); print $gm_lang["GM_SESSION_TIME"];?></td>
		<td class="shade1"><input type="text" name="NEW_GM_SESSION_TIME" value="<?php print $GM_SESSION_TIME?>" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="shade2 wrap"><?php print_help_link("MAX_VIEW_RATE_help", "qm", "MAX_VIEW_RATE"); print $gm_lang["MAX_VIEW_RATE"];?></td>
		<td class="shade1">
			<input type="text" name="NEW_MAX_VIEWS" value="<?php print $MAX_VIEWS?>" tabindex="<?php $i++; print $i?>" />
			<?php
				if ($TEXT_DIRECTION == "ltr") print $gm_lang["page_views"];
				else print $gm_lang["seconds"];
			?>
			<input type="text" name="NEW_MAX_VIEW_TIME" value="<?php print $MAX_VIEW_TIME?>" tabindex="<?php $i++; print $i?>" />
			<?php 
				if ($TEXT_DIRECTION == "ltr") print $gm_lang["seconds"];
				else print $gm_lang["page_views"];
			?>
		</td>
	</tr>
	<tr>
		<td class="shade2 wrap"><?php print_help_link("GM_MEMORY_LIMIT_help", "qm", "GM_MEMORY_LIMIT"); print $gm_lang["GM_MEMORY_LIMIT"];?></td>
		<td class="shade1"><input type="text" name="NEW_GM_MEMORY_LIMIT" value="<?php print $GM_MEMORY_LIMIT?>" tabindex="<?php $i++; print $i?>" /></td>
	</tr>
	<tr>
		<td class="center" colspan="2"><input type="submit" tabindex="<?php $i++; print $i?>" value="<?php print $gm_lang["save_config"];?>" onclick="closeHelp();" />
		&nbsp;&nbsp;
		<input type="reset" tabindex="<?php $i++; print $i?>" value="<?php print $gm_lang["reset"];?>" />
		</td>
	</tr>
<?php
	if (!file_is_writeable("config.php")) {
			print "<tr><td class=\"shade2\" colspan=\"2\">";
			print_text("not_writable");
			print "</td></tr>";
			print "<tr><td class=\"center\" colspan=\"2\"><input type=\"submit\" value=\"";
			print $gm_lang["download_file"];
			print "\" name=\"download\" /></td></tr>\n";
	}
?>
</table>
</form>
<?php if (!$CONFIGURED) { ?>
<script language="JavaScript" type="text/javascript">
	helpPopup('welcome_new_help');
</script>
<?php
}
?>
<script language="JavaScript" type="text/javascript">
	document.configform.NEW_DBHOST.focus();
</script>
<?php
print_footer();

?>