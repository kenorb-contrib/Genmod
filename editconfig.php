<?php
/**
 * Online UI for editing config.php site configuration variables
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
 * This Page Is Valid XHTML 1.0 Transitional! > 17 September 2005
 *
 * @package Genmod
 * @subpackage Admin
 * @see config.php
 * @version $Id$
 */
 
/**
 * Inclusion of the configuration file
*/
require "config.php";

$error_indexdir = false;
$error_db = false;
$error_db2 = false;
$error_db3 = false;
$error_url = false;
$error_cnf = false;
$error_ali = false;
$error_ali_login = false;

if (!isset($action)) $action="";
if (!isset($LOGIN_URL)) $LOGIN_URL = "";
if (!isset($NEW_LANGS)) $NEW_LANGS = array();
$message = "";
$i = 1;

PrintHeader(GM_LANG_configure_head);
?>
<!-- Setup the left box -->
<div id="admin_genmod_left">
	<div class="admin_link"><a href="admin.php"><?php print GM_LANG_admin;?></a></div>
</div>

<div id="content">
	<?php
	if ($action == "update") {
		if (!isset($_POST)) $_POST = $HTTP_POST_VARS;
		$boolarray = array();
		$boolarray["yes"]="1";
		$boolarray["no"]="0";
		$boolarray[false]="0";
		$boolarray[true]="1";
		$config_file_update = false;
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
		$CONFIG["MEDIA_IN_DB"] = $boolarray[$_POST["NEW_MEDIA_IN_DB"]];
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
		$CONFIG["SITE_ALIAS"] = $_POST["NEW_SITE_ALIAS"];
		$CONFIG["MAX_VIEWS"] = $_POST["NEW_MAX_VIEWS"];
		$CONFIG["MAX_VIEW_TIME"] = $_POST["NEW_MAX_VIEW_TIME"];
		$CONFIG["MAX_VIEW_LOGLEVEL"] = $_POST["NEW_MAX_VIEW_LOGLEVEL"];
		$CONFIG["EXCLUDE_HOSTS"] = $_POST["NEW_EXCLUDE_HOSTS"];
		$CONFIG["GM_MEMORY_LIMIT"] = $_POST["NEW_GM_MEMORY_LIMIT"];
		$CONFIG["ALLOW_REMEMBER_ME"] = $boolarray[$_POST["NEW_ALLOW_REMEMBER_ME"]];
		$CONFIG["CONFIG_VERSION"] = "1.0";
		$CONFIG["NEWS_TYPE"] = $_POST["NEW_NEWS_TYPE"];
		$CONFIG["PROXY_ADDRESS"] = $_POST["NEW_PROXY_ADDRESS"];
		$CONFIG["PROXY_PORT"] = $_POST["NEW_PROXY_PORT"];
		$CONFIG["LOCKOUT_TIME"] = $_POST["NEW_LOCKOUT_TIME"];
		$CONFIG["VISITOR_LANG"] = $_POST["NEW_VISITOR_LANG"];
		$CONFIG["DEFAULT_PAGE_SIZE"] = $_POST["NEW_DEFAULT_PAGE_SIZE"];
		$CONFIG["CONFIGURED"] = false;
		
		// Before resetting the DB parms, we first test them.
		if (!empty($_POST["NEW_DBPASS"])) $PASS = $_POST["NEW_DBPASS"];
		else $PASS = $DBPASS;
		// Try to make a new connection
		$conn = @mysql_connect($_POST["NEW_DBHOST"],$_POST["NEW_DBUSER"],$PASS, true);
		if ($conn === false) $error_db2 = true;
		// If a db name was entered, try to select it
		if (!$error_db2) {
			if (!empty($_POST["NEW_DBNAME"])) {
				$conn2 = mysql_select_db($_POST["NEW_DBNAME"], $conn);
				if ($conn2 === false) $error_db3 = true;
			}
			else $error_db3 = true;
			mysql_close($conn);
			$DBCONN = New DbLayer();
		}
		if (!$error_db2 && !$error_db3) {
			$DBHOST = $_POST["NEW_DBHOST"];
			$DBUSER = $_POST["NEW_DBUSER"];
			$DBNAME = $_POST["NEW_DBNAME"];
			if (!empty($_POST["NEW_DBPASS"])) $DBPASS = $_POST["NEW_DBPASS"];
		}
		
		$CONFIGURED = true;
		$CONFIG["CONFIGURED"] = true;

		//-- Perform validation checks, if not the first site defined
		if (isset($CONFIG_PARMS)) {
			foreach($CONFIG_PARMS as $site=>$parms) {
				if (($parms["INDEX_DIRECTORY"] == $CONFIG["INDEX_DIRECTORY"]) && $site != $CONFIG["SERVER_URL"]) $error_indexdir = true;
				if (($parms["DBNAME"] == $CONFIG["DBNAME"] && $parms["TBLPREFIX"] == $CONFIG["TBLPREFIX"]) && $site != $CONFIG["SERVER_URL"]) $error_db = true;
			}
		}
		
		if (!empty($CONFIG["LOGIN_URL"]) && !empty($CONFIG["SITE_ALIAS"])) $error_ali_login = true;
		
		$aliases = explode(",", $CONFIG["SITE_ALIAS"]);
		foreach ($aliases as $key => $alias) {
			if (!empty($alias) && preg_match("/^(http|https):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)(:(\d+))?\//i", $alias) == 0 && preg_match("/^(http|https):\/\/localhost(:(\d+))?\//i", $alias) == 0) {
				$error_ali = true;
			}
		}
						
		if (!$error_db && !$error_db2 && !$error_db3 && !$error_indexdir && !$error_url && !$error_ali && !$error_ali_login) {
			
			
			if (!$SystemConfig->StoreConfig($CONFIG)) {
				$message .= "<span class=\"error\">".GM_LANG_gm_config_write_error."</span>";
				$error_cnf = true;
			}
			
			$CONFIG_PARMS[$CONFIG["SERVER_URL"]] = $CONFIG;
			
			// Set English to active as it always needs to be active
			$NEW_LANGS["english"] = "english";

			// Add the active languages to the array as they MUST be present in the system.
			foreach($language_settings as $lname => $nothing) {
				if (LanguageInUse($lname) && !in_array($lname, $NEW_LANGS)) $NEW_LANGS[$lname] = $lname;
			}
		
			// Set the chosen languages to active
			foreach ($NEW_LANGS as $key => $name) {
				$gm_lang_use[$name] = true;
				if ($name["gm_lang_use"] && $name != "english" && !$language_settings[$name]["gm_lang_use"]) {
					StoreLanguage($name);
					ActivateLanguage($name);
					$language_settings[$name]["gm_lang_use"] = true;
				}
			}
			
			// Set the other languages to non-active
			foreach ($gm_lang_use as $name => $value) {
				if (!isset($NEW_LANGS[$name]) && $language_settings[$name]["gm_lang_use"]) {
					$gm_lang_use[$name] = false;
					RemoveLanguage($name);
					DeactivateLanguage($name);
					$language_settings[$name]["gm_lang_use"] = false;
				}
			}
			if (!$error_cnf) {
				$message .= GM_LANG_system_configuration_updated;
			}
		}
		foreach($_POST as $key=>$value) {
			$key=preg_replace("/NEW_/", "", $key);
			if ($value=='yes') $$key=true;
			else if ($value=='no') $$key=false;
			else $$key=$value;
		}
		if (!$error_db && !$error_db2 && !$error_db3 && !$error_indexdir && !$error_url && !$error_cnf && !$error_ali && !$error_ali_login) WriteToLog("EditConfig-> System configuration updated successfully.","I","S");
		else WriteToLog("EditConfig-> System configuration update failed.","E","S");
	}
	if (defined(SERVER_URL) && isset($CONFIG_SITE) && SERVER_URL != $CONFIG_SITE) $SERVER_URL = $CONFIG_SITE;
	else $SERVER_URL = SERVER_URL;
	?>
	<form method="post" name="configform" action="editconfig.php">
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="NEW_SERVER_URL" value="<?php print $SERVER_URL;?>" />
		<div class="admin_topbottombar">
			<h3>
				<?php print_help_link("configure_genmod_help", "qm", "configure");?>
				<?php print GM_LANG_configure; ?>
			</h3>
			<?php print GM_LANG_site_config.": ".$CONFIG_SITE; ?>
		</div>
		<?php
		if ($message != "") {
			print "<div class=\"shade2 center\">".$message."</div>";
		}
		?>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("DBHOST_help", "qm", "DBHOST");?>
				</div>
				<div class="description">
					<?php print GM_LANG_DBHOST; ?>
				</div>
			</div>
			<div class="choice_right">
				<input type="text" dir="ltr" name="NEW_DBHOST" value="<?php if ($gm_user->UserIsAdmin()) print $DBHOST?>" size="40" tabindex="<?php $i++; print $i?>" />
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("DBUSER_help", "qm", "DBUSER");?>
				</div>
				<div class="description">
					<?php  print GM_LANG_DBUSER; ?>
				</div>
			</div>
			<div class="choice_right">
				<input type="text" dir="ltr" name="NEW_DBUSER" value="<?php if ($gm_user->UserIsAdmin()) print $DBUSER?>" size="40" tabindex="<?php $i++; print $i?>" />
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("DBPASS_help", "qm", "DBPASS");?>
				</div>
				<div class="description">
					<?php print GM_LANG_DBPASS; ?>
				</div>
			</div>
			<div class="choice_right">
				<input type="text" dir="ltr" name="NEW_DBPASS" value="" size="40" tabindex="<?php $i++; print $i?>" />
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("DBNAME_help", "qm", "DBNAME");?>
				</div>
				<div class="description">
					<?php print GM_LANG_DBNAME; ?>
				</div>
			</div>
			<div class="choice_right">
				<input type="text" dir="ltr" name="NEW_DBNAME" value="<?php if ($gm_user->UserIsAdmin()) print $DBNAME?>" size="40" tabindex="<?php $i++; print $i?>" />
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("DBPERSIST_help", "qm", "DBPERSIST");?>
				</div>
				<div class="description">
					<?php print GM_LANG_DBPERSIST; ?>
				</div>
			</div>
			<div class="choice_right">
				<select name="NEW_DBPERSIST" tabindex="<?php $i++; print $i?>">
					<option value="yes" <?php if (DBPERSIST) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
					<option value="no" <?php if (!DBPERSIST) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
				</select>
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("TBLPREFIX_help", "qm", "TBLPREFIX");?>
				</div>
				<div class="description">
					<?php print GM_LANG_TBLPREFIX; ?>
				</div>
			</div>
			<div class="choice_right">
				<input type="text" name="NEW_TBLPREFIX" value="<?php print TBLPREFIX?>" size="40" tabindex="<?php $i++; print $i?>" />
				<?php if ($error_db) print "<div class=\"error\">".GM_LANG_duplicatedb."</div>"; ?>
				<?php if ($error_db2) print "<div class=\"error\">".GM_LANG_bad_host_user_pass."</div>"; ?>
				<?php if ($error_db3) print "<div class=\"error\">".GM_LANG_bad_database_name."</div>"; ?>
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("ALLOW_CHANGE_GEDCOM_help", "qm", "ALLOW_CHANGE_GEDCOM");?>
				</div>
				<div class="description">
					<?php print GM_LANG_ALLOW_CHANGE_GEDCOM; ?>
				</div>
			</div>
			<div class="choice_right">
				<select name="NEW_ALLOW_CHANGE_GEDCOM" tabindex="<?php $i++; print $i?>">
					<option value="yes" <?php if ($ALLOW_CHANGE_GEDCOM) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
					<option value="no" <?php if (!$ALLOW_CHANGE_GEDCOM) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
				</select>
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("INDEX_DIRECTORY_help", "qm", "INDEX_DIRECTORY");?>
				</div>
				<div class="description">
					<?php print GM_LANG_INDEX_DIRECTORY; ?>
				</div>
			</div>
			<div class="choice_right">
				<input type="text" size="40" name="NEW_INDEX_DIRECTORY" value="<?php print INDEX_DIRECTORY?>" dir="ltr" tabindex="<?php $i++; print $i?>" />
				<?php if ($error_indexdir) print "<div class=\"error\">".GM_LANG_duplicateindexdir."</div>"; ?>
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("MEDIA_IN_DB_help", "qm", "MEDIA_IN_DB");?>
				</div>
				<div class="description">
					<?php print GM_LANG_MEDIA_IN_DB; ?>
				</div>
			</div>
			<div class="choice_right">
				<select name="NEW_MEDIA_IN_DB" tabindex="<?php $i++; print $i?>">
					<option value="yes" <?php if ($MEDIA_IN_DB) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
					<option value="no" <?php if (!$MEDIA_IN_DB) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
				</select>
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("GM_STORE_MESSAGES_help", "qm", "GM_STORE_MESSAGES");?>
				</div>
				<div class="description">
					<?php print GM_LANG_GM_STORE_MESSAGES; ?>
				</div>
			</div>
			<div class="choice_right">
				<select name="NEW_GM_STORE_MESSAGES" tabindex="<?php $i++; print $i?>">
					<option value="yes" <?php if ($GM_STORE_MESSAGES) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
					<option value="no" <?php if (!$GM_STORE_MESSAGES) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
				</select>
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("USE_REGISTRATION_MODULE_help", "qm", "USE_REGISTRATION_MODULE");?>
				</div>
				<div class="description">
					<?php print GM_LANG_USE_REGISTRATION_MODULE; ?>
				</div>
			</div>
			<div class="choice_right">
				<select name="NEW_USE_REGISTRATION_MODULE" tabindex="<?php $i++; print $i?>">
					<option value="yes" <?php if ($USE_REGISTRATION_MODULE) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
					<option value="no" <?php if (!$USE_REGISTRATION_MODULE) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
				</select>
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("REQUIRE_ADMIN_AUTH_REGISTRATION_help", "qm", "REQUIRE_ADMIN_AUTH_REGISTRATION");?>
				</div>
				<div class="description">
					<?php print GM_LANG_REQUIRE_ADMIN_AUTH_REGISTRATION; ?>
				</div>
			</div>
			<div class="choice_right">
				<select name="NEW_REQUIRE_ADMIN_AUTH_REGISTRATION" tabindex="<?php $i++; print $i?>">
					<option value="yes" <?php if ($REQUIRE_ADMIN_AUTH_REGISTRATION) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
					<option value="no" <?php if (!$REQUIRE_ADMIN_AUTH_REGISTRATION) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
				</select>
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("GM_SIMPLE_MAIL_help", "qm", "GM_SIMPLE_MAIL");?>
				</div>
				<div class="description">
					<?php print GM_LANG_GM_SIMPLE_MAIL; ?>
				</div>
			</div>
			<div class="choice_right">
				<select name="NEW_GM_SIMPLE_MAIL" tabindex="<?php $i++; print $i?>">
					<option value="yes" <?php if ($GM_SIMPLE_MAIL) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
					<option value="no" <?php if (!$GM_SIMPLE_MAIL) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
				</select>
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("ALLOW_USER_THEMES_help", "qm", "ALLOW_USER_THEMES");?>
				</div>
				<div class="description">
					<?php print GM_LANG_ALLOW_USER_THEMES; ?>
				</div>
			</div>
			<div class="choice_right">
				<select name="NEW_ALLOW_USER_THEMES" tabindex="<?php $i++; print $i?>">
					<option value="yes" <?php if ($ALLOW_USER_THEMES) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
					<option value="no" <?php if (!$ALLOW_USER_THEMES) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
				</select>
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("NEWS_TYPE_help", "qm", "NEWS_TYPE");?>
				</div>
				<div class="description">
					<?php print GM_LANG_NEWS_TYPE; ?>
				</div>
			</div>
			<div class="choice_right">
				<select name="NEW_NEWS_TYPE" tabindex="<?php $i++; print $i?>">
					<option value="Normal" <?php if ($NEWS_TYPE == "Normal") print "selected=\"selected\""; ?>><?php print GM_LANG_normal;?></option>
					<option value="Urgent" <?php if ($NEWS_TYPE == "Urgent") print "selected=\"selected\""; ?>><?php print GM_LANG_urgent;?></option>
				</select>
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("ALLOW_REMEMBER_ME_help", "qm", "ALLOW_REMEMBER_ME");?>
				</div>
				<div class="description">
					<?php print GM_LANG_ALLOW_REMEMBER_ME; ?>
				</div>
			</div>
			<div class="choice_right">
				<select name="NEW_ALLOW_REMEMBER_ME" tabindex="<?php $i++; print $i?>">
					<option value="yes" <?php if ($ALLOW_REMEMBER_ME) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
					<option value="no" <?php if (!$ALLOW_REMEMBER_ME) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
				</select>
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("LANG_SELECTION_help", "qm", "LANG_SELECTION");?>
				</div>
				<div class="description">
					<?php print GM_LANG_LANG_SELECTION; ?>
				</div>
			</div>
			<div class="choice_right width65">
				<?php
				// NOTE: Build a sorted list of language names in the currently active language
				foreach ($language_settings as $key => $value){
					$d_LangName = "lang_name_".$key;
					$SortedLangs[$key] = constant("GM_LANG_".$d_LangName);
					if ($value["gm_lang_use"]) $ActiveLangs[$key] = constant("GM_LANG_".$d_LangName);
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
					print "<div class=\"admin_item_box\">";
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
								print "<div class=\"choice_middle width30\">";
								print "<input type=\"checkbox\" name=\"NEW_LANGS[".$LangName."]\" value=\"".$LangName."\" ";
								if (array_key_exists($LangName, $ActiveLangs) || LanguageInUse($LangName)) print "checked=\"checked\" ";
								if (LanguageInUse($LangName)) print "disabled=\"disabled\" ";
								print "/>";
								print "".$LocalName."\n";
								print "</div>";
							}
							else {
								print "<div class=\"choice_middle width30\">";
								print "&nbsp;";
								print "&nbsp;\n";
								print "</div>";
							}
							$curcol++;
						}
						
					
					$curline++;
					// Finish the table row
					print "</div>";
				}
				
				?>
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("VISITOR_LANG_help", "qm", "VISITOR_LANG");?>
				</div>
				<div class="description">
					<?php print GM_LANG_VISITOR_LANG; ?>
				</div>
			</div>
			<div class="choice_right">
				<select name="NEW_VISITOR_LANG" tabindex="<?php $i++; print $i?>">
					<option value="Genmod" <?php if ($VISITOR_LANG == "Genmod") print "selected=\"selected\""; ?>><?php print GM_LANG_genmod_lang;?></option>
					<?php
					foreach ($gm_language as $key=>$value) {
						if ($language_settings[$key]["gm_lang_use"]) {
							print "<option value=\"".$key."\"";
							if ($VISITOR_LANG == $key) print " selected=\"selected\"";
							print ">".constant("GM_LANG_lang_name_".$key)."</option>";
						}
					} ?>
				</select>
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("SERVER_URL_help", "qm", "SERVER_URL");?>
				</div>
				<div class="description">
					<?php print GM_LANG_SERVER_URL; ?>
				</div>
			</div>
			<div class="choice_right width65">
				<input type="text" name="NEW_SERVER_URL" value="<?php print $SERVER_URL?>" dir="ltr" tabindex="<?php $i++; print $i?>" size="40" 
				<?php if (isset($CONFIG["SERVER_URL"]) && isset($CONFIG_PARMS[$CONFIG["SERVER_URL"]])) print "disabled=\"disabled\""; ?> />
				<?php
				if ($error_url) print "<div class=\"error\">".GM_LANG_emptyserverurl."</div>";
				$GUESS_URL = ("http://".$_SERVER["SERVER_NAME"]);
				if ($_SERVER["SERVER_PORT"] != 80) $GUESS_URL .= ":".$_SERVER["SERVER_PORT"];
				$GUESS_URL .= dirname(SCRIPT_NAME)."/";
				$GUESS_URL = stripslashes($GUESS_URL);
				print "<div>".print_text("server_url_note",0,1)."</div>"; ?>
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("LOGIN_URL_help", "qm", "LOGIN_URL");?>
				</div>
				<div class="description">
					<?php print GM_LANG_LOGIN_URL; ?>
				</div>
			</div>
			<div class="choice_right">
				<input type="text" name="NEW_LOGIN_URL" value="<?php print LOGIN_URL?>" dir="ltr" tabindex="<?php $i++; print $i?>" size="40" />
				<?php if ($error_ali_login) print "<div class=\"error\">".GM_LANG_aliaslogin."</div>"; ?>
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("SITE_ALIAS_help", "qm", "LOGIN_URL");?>
				</div>
				<div class="description">
					<?php print GM_LANG_SITE_ALIAS; ?>
				</div>
			</div>
			<div class="choice_right">
				<input type="text" name="NEW_SITE_ALIAS" value="<?php print $SITE_ALIAS?>" dir="ltr" tabindex="<?php $i++; print $i?>" size="40" />
				<?php if ($error_ali) print "<div class=\"error\">".GM_LANG_invalidalias."</div>"; ?>
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("PROXY_ADDRESS_help", "qm", "PROXY_ADDRESS");?>
				</div>
				<div class="description">
					<?php print GM_LANG_PROXY_ADDRESS; ?>
				</div>
			</div>
			<div class="choice_right">
				<input type="text" name="NEW_PROXY_ADDRESS" value="<?php print $PROXY_ADDRESS;?>" dir="ltr" tabindex="<?php $i++; print $i?>" size="40" />
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("PROXY_PORT_help", "qm", "PROXY_PORT");?>
				</div>
				<div class="description">
					<?php print GM_LANG_PROXY_PORT; ?>
				</div>
			</div>
			<div class="choice_right">
				<input type="text" name="NEW_PROXY_PORT" value="<?php print $PROXY_PORT;?>" dir="ltr" tabindex="<?php $i++; print $i?>" size="5" />
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("GM_SESSION_SAVE_PATH_help", "qm", "GM_SESSION_SAVE_PATH");?>
				</div>
				<div class="description">
					<?php print GM_LANG_GM_SESSION_SAVE_PATH; ?>
				</div>
			</div>
			<div class="choice_right">
				<input type="text" dir="ltr" name="NEW_GM_SESSION_SAVE_PATH" value="<?php print GM_SESSION_SAVE_PATH;?>" tabindex="<?php $i++; print $i?>" size="40" />
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("GM_SESSION_TIME_help", "qm", "GM_SESSION_TIME");?>
				</div>
				<div class="description">
					<?php print GM_LANG_GM_SESSION_TIME; ?>
				</div>
			</div>
			<div class="choice_right">
				<input type="text" name="NEW_GM_SESSION_TIME" value="<?php print $GM_SESSION_TIME;?>" tabindex="<?php $i++; print $i?>" size="5" />
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("MAX_VIEW_RATE_help", "qm", "MAX_VIEW_RATE");?>
				</div>
				<div class="description">
					<?php print GM_LANG_MAX_VIEW_RATE; ?>
				</div>
			</div>
			<div class="choice_right">
				<input type="text" name="NEW_MAX_VIEWS" value="<?php print $MAX_VIEWS;?>" tabindex="<?php $i++; print $i?>" size="5" />
				<?php
					if ($TEXT_DIRECTION == "ltr") print GM_LANG_page_views;
					else print GM_LANG_seconds;
				?>
				<input type="text" name="NEW_MAX_VIEW_TIME" value="<?php print $MAX_VIEW_TIME?>" tabindex="<?php $i++; print $i?>" size="5" />
				<?php 
					if ($TEXT_DIRECTION == "ltr") print GM_LANG_seconds;
					else print GM_LANG_page_views;
				?>
				<br />
				<select name="NEW_MAX_VIEW_LOGLEVEL" tabindex="<?php $i++; print $i?>">
					<option value="0" <?php if ($MAX_VIEW_LOGLEVEL == "0") print "selected=\"selected\""; ?>><?php print GM_LANG_loglevel_0;?></option>
					<option value="1" <?php if ($MAX_VIEW_LOGLEVEL == "1") print "selected=\"selected\""; ?>><?php print GM_LANG_loglevel_1;?></option>
					<option value="2" <?php if ($MAX_VIEW_LOGLEVEL == "2") print "selected=\"selected\""; ?>><?php print GM_LANG_loglevel_2;?></option>
				</select>
				
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("EXCLUDE_HOSTS_help", "qm", "LOCKOUT_TIME");?>
				</div>
				<div class="description">
					<?php print GM_LANG_EXCLUDE_HOSTS; ?>
				</div>
			</div>
			<div class="choice_right">
				<input type="text" name="NEW_EXCLUDE_HOSTS" value="<?php print $EXCLUDE_HOSTS;?>" tabindex="<?php $i++; print $i?>" size="60" />
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("LOCKOUT_TIME_help", "qm", "LOCKOUT_TIME");?>
				</div>
				<div class="description">
					<?php print GM_LANG_LOCKOUT_TIME; ?>
				</div>
			</div>
			<div class="choice_right">
				<input type="text" name="NEW_LOCKOUT_TIME" value="<?php print $LOCKOUT_TIME;?>" tabindex="<?php $i++; print $i?>" size="5" />
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("GM_MEMORY_LIMIT_help", "qm", "GM_MEMORY_LIMIT");?>
				</div>
				<div class="description">
					<?php print GM_LANG_GM_MEMORY_LIMIT; ?>
				</div>
			</div>
			<div class="choice_right">
				<input type="text" name="NEW_GM_MEMORY_LIMIT" value="<?php print $GM_MEMORY_LIMIT;?>" tabindex="<?php $i++; print $i?>" size="5" />
			</div>
		</div>
		<div class="admin_item_box">
			<div class="width30 choice_left">
				<div class="helpicon">
					<?php print_help_link("DEFAULT_PAGE_SIZE_help", "qm", "DEFAULT_PAGE_SIZE");?>
				</div>
				<div class="description">
					<?php print GM_LANG_DEFAULT_PAGE_SIZE; ?>
				</div>
			</div>
			<div class="choice_right">
				<?php
				require_once("includes/reportheader.php");
				$sizes = explode(",", AVAIL_PAGE_SIZES);
				?>
				<select name="NEW_DEFAULT_PAGE_SIZE" tabindex="<?php $i++; print $i?>">
				<?php foreach ($sizes as $key => $size) {
						print "<option value=\"".$size."\" ";
						if ($DEFAULT_PAGE_SIZE == $size) print "selected=\"selected\"";
						print ">".constant("GM_LANG_p_".$size);
						print "</option>";
					} ?>
				</select>
			</div>
		</div>
		<div class="admin_item_box center">
			<input type="submit" value="<?php print GM_LANG_save;?>" />
		</div>
	</form>
	<?php if (!CONFIGURED) { ?>
		<script language="JavaScript" type="text/javascript">
		<!--
			helpPopup('welcome_new_help');
		//-->
		</script>
	<?php } ?>
	<script language="JavaScript" type="text/javascript">
	<!--
		document.configform.NEW_DBHOST.focus();
	//-->
	</script>
</div>
<?php
PrintFooter();
?>
