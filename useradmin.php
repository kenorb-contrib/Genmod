<?php
/**
 * Administrative User Interface.
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
 * This Page Is Valid XHTML 1.0 Transitional! > 30 August 2005
 *
 * @package Genmod
 * @subpackage Admin
 * @version $Id: useradmin.php,v 1.8 2006/04/05 18:00:41 sjouke Exp $
 */

/**
 * load configuration and context
 */
require "config.php";

global $TEXT_DIRECTION;
include "includes/functions_edit.php";

// Remove slashes
if (isset($ufirstname)) $ufirstname = stripslashes($ufirstname);
if (isset($ulastname)) $ulastname = stripslashes($ulastname);

if (!isset($action)) $action="";
if (!isset($filter)) $filter="";
if (!isset($namefilter)) $namefilter="";
if (!isset($sort)) $sort="";
if (!isset($ged)) $ged="";
if (!isset($usrlang)) $usrlang="";
if (isset($refreshlist)) $action="listusers";

//-- make sure that they have admin status before they can use this page
//-- otherwise have them login again
if (!userIsAdmin($gm_username)) {
	header("Location: login.php?url=useradmin.php");
	exit;
}
print_header("Genmod ".$gm_lang["user_admin"]);

// Javascript for edit form
?>
<script language="JavaScript" type="text/javascript">
<!--
	function checkform(frm) {
		if (frm.uusername.value=="") {
			alert("<?php print $gm_lang["enter_username"]; ?>");
			frm.uusername.focus();
			return false;
		}
		if (frm.ufirstname.value=="") {
			alert("<?php print $gm_lang["enter_fullname"]; ?>");
			frm.ufirstname.focus();
			return false;
		}
		if (frm.ulastname.value=="") {
			alert("<?php print $gm_lang["enter_fullname"]; ?>");
			frm.ulastname.focus();
			return false;
		}
	    if ((frm.pass1.value!="")&&(frm.pass1.value.length < 6)) {
	      alert("<?php print $gm_lang["passwordlength"]; ?>");
	      frm.pass1.value = "";
	      frm.pass2.value = "";
	      frm.pass1.focus();
	      return false;
	    }
		if ((frm.emailadress.value!="")&&(frm.emailadress.value.indexOf("@")==-1)) {
			alert("<?php print $gm_lang["enter_email"]; ?>");
			frm.emailadress.focus();
			return false;
		} 
		return true;
	}
	var pastefield;
	function paste_id(value) {
		pastefield.value=value;
	}
//-->
</script>
<?php
//-- section to create a new user
// NOTE: No table parts
if ($action=="createuser") {
	$alphabet = getAlphabet();
	$alphabet .= "_-. ";
	$i = 1;
	$pass = TRUE;
	while (strlen($uusername) > $i) {
		if (stristr($alphabet, $uusername{$i}) != TRUE){
			$pass = FALSE;
			break;
		}
		$i++;
	}
	if ($pass == TRUE){
		if (getUser($uusername)!==false) {
			print "<span class=\"error\">".$gm_lang["duplicate_username"]."</span><br />";
		}
		else if ($pass1==$pass2) {
			$user = array();
			$user["username"]=$uusername;
			$user["firstname"]=$ufirstname;
			$user["lastname"]=$ulastname;
			$user["email"]=$emailadress;
			if (!isset($verified)) $verified = "";
			$user["verified"] = $verified;
			if (!isset($verified_by_admin)) $verified_by_admin = "";
			$user["verified_by_admin"] = $verified_by_admin;
			if (!empty($user_language)) $user["language"] = $user_language;
			else $user["language"] = $LANGUAGE;
			$user["pwrequested"] = $pwrequested;
			$user["reg_timestamp"] = $reg_timestamp;
			$user["reg_hashcode"] = $reg_hashcode;
			$user["gedcomid"]=array();
			$user["rootid"]=array();
			$user["canedit"]=array();
			foreach($GEDCOMS as $ged=>$gedarray) {
				$file = $ged;
				$ged = preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $ged);
				$varname = "gedcomid_$ged";
				if (isset($$varname)) $user["gedcomid"][$file]=$$varname;
				$varname = "rootid_$ged";
				if (isset($$varname)) $user["rootid"][$file]=$$varname;
				$varname = "canedit_$ged";
				if (isset($$varname)) $user["canedit"][$file]=$$varname;
				else $user["canedit"][$file]="access";
			}
			$user["password"]=crypt($pass1);
			if ((isset($canadmin))&&($canadmin=="yes")) $user["canadmin"]=true;
			else $user["canadmin"]=false;
			if ((isset($visibleonline))&&($visibleonline=="yes")) $user["visibleonline"]=true;
			else $user["visibleonline"]=false;
			if ((isset($editaccount))&&($editaccount=="yes")) $user["editaccount"]=true;
			else $user["editaccount"]=false;
			if (!isset($new_user_theme)) $new_user_theme="";
			$user["theme"] = $new_user_theme;
			$user["loggedin"] = "N";
			$user["sessiontime"] = 0;
			if (!isset($new_contact_method)) $new_contact_method="messaging2";
			$user["contactmethod"] = $new_contact_method;
			if (isset($new_default_tab)) $user["default_tab"] = $new_default_tab;
			if (isset($new_comment)) $user["comment"] = $new_comment;
			if (isset($new_comment_exp)) $user["comment_exp"] = $new_comment_exp;
			if (isset($new_sync_gedcom)) $user["sync_gedcom"] = $new_sync_gedcom;
			else $user["sync_gedcom"] = "N";
			if (isset($new_relationship_privacy)) $user["relationship_privacy"] = $new_relationship_privacy;
			if (isset($new_max_relation_path)) $user["max_relation_path"] = $new_max_relation_path;
			$user["auto_accept"] = false;
			if (isset($new_auto_accept))  $user["auto_accept"] = true;
			
			$au = addUser($user, "added");
			
			if ($au) {
				print $gm_lang["user_created"]; print "<br />";
				//-- update Gedcom record with new email address
				if ($user["sync_gedcom"]=="Y" && !empty($user["email"])) {
					$oldged = $GEDCOM;
					foreach($user["gedcomid"] as $gedc=>$gedid) {
						if (!empty($gedid)) {
							$GEDCOM = $gedc;
							$indirec = find_person_record($gedid);
							if (!empty($indirec)) {
								if (preg_match("/\d _?EMAIL/", $indirec)>0) {
									$indirec = preg_replace("/(\d _?EMAIL)[^\r\n]*/", "$1 ".$user["email"], $indirec);
									replace_gedrec($gedid, $indirec);
								}
								else {
									$indirec .= "\r\n1 EMAIL ".$user["email"];
									replace_gedrec($gedid, $indirec);
								}
							}
						}
					}
				}
			}
			else {
				print "<span class=\"error\">".$gm_lang["user_create_error"]."<br /></span>";
			}
		}
		else {
			print "<span class=\"error\">".$gm_lang["password_mismatch"]."</span><br />";
		}
	}
	else {
		print "<span class=\"error\">".$gm_lang["invalid_username"]."</span><br />";
	}
}
//-- section to delete a user
// NOTE: No table parts
if ($action=="deleteuser") {
	deleteUser($username, "deleted");
}
//-- section to update a user by first deleting them
//-- and then adding them again
// NOTE: No table parts
if ($action=="edituser2") {
	$alphabet = getAlphabet();
	$alphabet .= "_-. ";
	$i = 1;
	$pass = TRUE;
	while (strlen($uusername) > $i) {
		if (stristr($alphabet, $uusername{$i}) != TRUE){
			$pass = FALSE;
			break;
		}
		$i++;
	}
	if ($pass == TRUE){
		if (($uusername!=$oldusername)&&(getUser($uusername)!==false)) {
			print "<span class=\"error\">".$gm_lang["duplicate_username"]."</span><br />";
			$action="edituser";
		}
		else if ($pass1==$pass2) {
			$sync_data_changed = false;
			$newuser = array();
			$olduser = getUser($oldusername);
			$newuser = $olduser;

			if (empty($pass1)) $newuser["password"]=$olduser["password"];
			else $newuser["password"]=crypt($pass1);
			deleteUser($oldusername, "changed");
			$newuser["username"]=$uusername;
			$newuser["firstname"]=$ufirstname;
			$newuser["lastname"]=$ulastname;

			if (!empty($user_language)) $newuser["language"] = $user_language;

			if ($olduser["email"]!=$emailadress) $sync_data_changed = true;
			$newuser["email"]=$emailadress;
			if (!isset($verified)) $verified = "";
			$newuser["verified"] = $verified;
			if (!isset($verified_by_admin)) $verified_by_admin = "";
			$newuser["verified_by_admin"] = $verified_by_admin;

			if (!empty($new_contact_method)) $newuser["contactmethod"] = $new_contact_method;
			if (isset($new_default_tab)) $newuser["default_tab"] = $new_default_tab;
			if (isset($new_comment)) $newuser["comment"] = $new_comment;
			if (isset($new_comment_exp)) $newuser["comment_exp"] = $new_comment_exp;
			if (isset($new_sync_gedcom)) $newuser["sync_gedcom"] = $new_sync_gedcom;
			if (isset($new_relationship_privacy)) $newuser["relationship_privacy"] = $new_relationship_privacy;
			if (isset($new_max_relation_path)) $newuser["max_relation_path"] = $new_max_relation_path;
			$newuser["auto_accept"] = false;
			if (isset($new_auto_accept)) $newuser["auto_accept"] = true;

			if (!isset($user_theme)) $user_theme="";
			$newuser["theme"] = $user_theme;
			$newuser["gedcomid"]=array();
			$newuser["rootid"]=array();
			$newuser["canedit"]=array();
			foreach($GEDCOMS as $ged=>$gedarray) {
				$file = $ged;
				$ged = preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $ged);
				$varname = "gedcomid_$ged";
				if (isset($$varname)) $newuser["gedcomid"][$file]=$$varname;
				$varname = "rootid_$ged";
				if (isset($$varname)) $newuser["rootid"][$file]=$$varname;
				$varname = "canedit_$ged";
				if (isset($$varname)) $newuser["canedit"][$file]=$$varname;
				else $user["canedit"][$file]="none";
			}
			if ($olduser["username"]!=$gm_username) {
				if ((isset($canadmin))&&($canadmin=="yes")) $newuser["canadmin"]=true;
				else $newuser["canadmin"]=false;
			}
			else $newuser["canadmin"]=$olduser["canadmin"];
			if ((isset($visibleonline))&&($visibleonline=="yes")) $newuser["visibleonline"]=true;
			else $newuser["visibleonline"]=false;
			if ((isset($editaccount))&&($editaccount=="yes")) $newuser["editaccount"]=true;
			else $newuser["editaccount"]=false;
			
			addUser($newuser, "changed");
			
			//-- update Gedcom record with new email address
			if ($newuser["sync_gedcom"]=="Y" && $sync_data_changed) {
				$oldged = $GEDCOM;
				foreach($newuser["gedcomid"] as $gedc=>$gedid) {
					if (!empty($gedid)) {
						$GEDCOM = $gedc;
						$indirec = find_person_record($gedid);
						if (!empty($indirec)) {
							if (preg_match("/\d _?EMAIL/", $indirec)>0) {
								$indirec = preg_replace("/(\d _?EMAIL)[^\r\n]*/", "$1 ".$newuser["email"], $indirec);
								replace_gedrec($gedid, $indirec);
							}
							else {
								$indirec .= "\r\n1 EMAIL ".$newuser["email"];
								replace_gedrec($gedid, $indirec);
							}
						}
					}
				}
			}

			//-- if the user was just verified by the admin, then send the user a message
			if (($olduser["verified_by_admin"]!=$newuser["verified_by_admin"])&&(!empty($newuser["verified_by_admin"]))) {

				// Switch to the users language
				$oldlanguage = $LANGUAGE;
				$LANGUAGE = $newuser["language"];
				if (isset($gm_language[$LANGUAGE]) && (file_exists($GM_BASE_DIRECTORY . $gm_language[$LANGUAGE]))) require($GM_BASE_DIRECTORY . $gm_language[$LANGUAGE]);	//-- load language file
				$TEXT_DIRECTION = $TEXT_DIRECTION_array[$LANGUAGE];
				$DATE_FORMAT	= $DATE_FORMAT_array[$LANGUAGE];
				$TIME_FORMAT	= $TIME_FORMAT_array[$LANGUAGE];
				$WEEK_START	= $WEEK_START_array[$LANGUAGE];
				$NAME_REVERSE	= $NAME_REVERSE_array[$LANGUAGE];

				$message = array();
				$message["to"] = $newuser["username"];
				$host = preg_replace("/^www\./i", "", $_SERVER["SERVER_NAME"]);
				$headers = "From: Genmod-noreply@".$host;
				$message["from"] = $gm_username;
				if (substr($SERVER_URL, -1) == "/"){
					$message["subject"] = str_replace("#SERVER_NAME#", substr($SERVER_URL,0, (strlen($SERVER_URL)-1)), $gm_lang["admin_approved"]);
					$message["body"] = str_replace("#SERVER_NAME#", $SERVER_URL, $gm_lang["admin_approved"]).$gm_lang["you_may_login"]."\r\n\r\n".substr($SERVER_URL,0, (strlen($SERVER_URL)-1))."/index.php?command=user\r\n";
				}
				else {
					$message["subject"] = str_replace("#SERVER_NAME#", $SERVER_URL, $gm_lang["admin_approved"]);
					$message["body"] = str_replace("#SERVER_NAME#", $SERVER_URL, $gm_lang["admin_approved"]).$gm_lang["you_may_login"]."\r\n\r\n".$SERVER_URL."/index.php?command=user\r\n";
				}
				$message["created"] = "";
				$message["method"] = "messaging2";
				addMessage($message);

				// Switch back to the page language
				$LANGUAGE = $oldlanguage;
				if (isset($gm_language[$LANGUAGE]) && (file_exists($GM_BASE_DIRECTORY . $gm_language[$LANGUAGE]))) require($GM_BASE_DIRECTORY . $gm_language[$LANGUAGE]);	//-- load language file
				$TEXT_DIRECTION = $TEXT_DIRECTION_array[$LANGUAGE];
				$DATE_FORMAT	= $DATE_FORMAT_array[$LANGUAGE];
				$TIME_FORMAT	= $TIME_FORMAT_array[$LANGUAGE];
				$WEEK_START	= $WEEK_START_array[$LANGUAGE];
				$NAME_REVERSE	= $NAME_REVERSE_array[$LANGUAGE];
			}
		}
		else {
			print "<span class=\"error\">".$gm_lang["password_mismatch"]."</span><br />";
			$action="edituser";
		}
	}
	else {
		print "<span class=\"error\">".$gm_lang["invalid_username"]."</span><br />";
	}
}
//-- print the form to edit a user
// NOTE: WORKING
require_once("./includes/functions_edit.php");
init_calendar_popup();
if ($action=="edituser") {
	$user = getUser($username);
	if (!isset($user['contactmethod'])) $user['contactmethod'] = "none";
	?>
	<form name="editform" method="post" action="useradmin.php" onsubmit="return checkform(this);">
	<input type="hidden" name="action" value="edituser2" />
	<input type="hidden" name="filter" value="<?php print $filter; ?>" />
	<input type="hidden" name="sort" value="<?php print $sort; ?>" />
	<input type="hidden" name="ged" value="<?php print $ged; ?>" />
	<input type="hidden" name="usrlang" value="<?php print $usrlang; ?>" />
	<input type="hidden" name="oldusername" value="<?php print $username; ?>" />
	<?php $tab=0; ?>
	<table class="center list_table width80 <?php print $TEXT_DIRECTION; ?>">
	<tr><td colspan="2" class="facts_label"><?php
	print "<h2>".$gm_lang["update_user"]."</h2>";
	?>
  </td>
  </tr>
    <tr>
      <td class="shade2 width20 wrap"><?php print_help_link("useradmin_username_help", "qm","username"); print $gm_lang["username"];?></td>
      <td class="shade1 wrap"><input type="text" name="uusername" tabindex="<?php $tab++; print $tab; ?>" value="<?php print $user['username']?>" /></td>
    </tr>
    <tr>
      <td class="shade2 wrap"><?php print_help_link("useradmin_firstname_help", "qm", "firstname"); print $gm_lang["firstname"];?></td>
      <td class="shade1 wrap"><input type="text" name="ufirstname" tabindex="<?php $tab++; print $tab; ?>" value="<?php print PrintReady($user['firstname'])?>" size="50" /></td>
    </tr>
    <tr>
      <td class="shade2 wrap"><?php print_help_link("useradmin_lastname_help", "qm","lastname");print $gm_lang["lastname"];?></td>
      <td class="shade1 wrap"><input type="text" name="ulastname" tabindex="<?php $tab++; print $tab; ?>" value="<?php print PrintReady($user['lastname'])?>" size="50" /></td>
    </tr>
    <tr>
      <td class="shade2 wrap"><?php print_help_link("useradmin_password_help", "qm","password"); print $gm_lang["password"];?></td>
      <td class="shade1 wrap"><input type="password" name="pass1" tabindex="<?php $tab++; print $tab; ?>" /><br /><?php print $gm_lang["leave_blank"];?></td>
    </tr>
    <tr>
      <td class="shade2 wrap"><?php print_help_link("useradmin_conf_password_help", "qm","confirm"); print $gm_lang["confirm"];?></td>
      <td class="shade1 wrap"><input type="password" name="pass2" tabindex="<?php $tab++; print $tab; ?>" /></td>
    </tr>
    <tr>
      <td class="shade2 wrap"><?php print_help_link("useradmin_gedcomid_help", "qm","gedcomid"); print $gm_lang["gedcomid"];?></td>
      <td class="shade1 wrap">
	<table class="<?php print $TEXT_DIRECTION; ?>">
         	<?php
		foreach($GEDCOMS as $ged=>$gedarray) {
			$file = $ged;
			$ged = preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $ged);			?>
			<tr>
			<td><?php print $file;?>:&nbsp;&nbsp;</td>
			<td> <input type="text" name="<?php print "gedcomid_$ged"; ?>" id="<?php print "gedcomid_$ged"; ?>" tabindex="<?php $tab++; print $tab; ?>" value="<?php
			if (isset($user['gedcomid'][$file])) print $user['gedcomid'][$file];
			print "\" />";
			print_findindi_link("gedcomid_$ged","");
			if (isset($user['gedcomid'][$file])) {
				$sged = $GEDCOM;
				$GEDCOM = $file;
				print "\n<span class=\"list_item\"> ".get_person_name($user['gedcomid'][$file]);
				print_first_major_fact($user['gedcomid'][$file]);
				$GEDCOM = $sged;
				print "</span>\n";
			}
			print "</td></tr>";
		} 
		?>
	</table>
      </td>
    </tr>
    <tr>
      <td class="shade2 wrap"><?php print_help_link("useradmin_rootid_help", "qm", "rootid"); print $gm_lang["rootid"];?></td>
      <td class="shade1 wrap">
	<table class="<?php print $TEXT_DIRECTION;?>">
	  <?php
	  foreach($GEDCOMS as $ged=>$gedarray) {
	    $file = $ged;
	    $ged = preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $ged);
	  ?><tr>
	    <td><?php print $file;?>:&nbsp;&nbsp;</td>
	    <td> <input type="text" name="<?php print "rootid_$ged"; ?>" id="<?php print "rootid_$ged"; ?>" tabindex="<?php $tab++; print $tab; ?>" value="<?php
	    if (isset($user['rootid'][$file])) print $user['rootid'][$file];
	    print "\" />";
	    print_findindi_link("rootid_$ged","");
		if (isset($user['rootid'][$file])) {
			$sged = $GEDCOM;
			$GEDCOM = $file;
			print "\n<span class=\"list_item\">".get_person_name($user['rootid'][$file]);
			print_first_major_fact($user['rootid'][$file]);
			$GEDCOM = $sged;
			print "</span>\n";
		}
	    ?></td>
	  </tr>
	<?php } ?></table>
      </td>
    </tr>
    <tr>
      <td class="shade2 wrap"><?php print_help_link("useradmin_sync_gedcom_help", "qm", "sync_gedcom"); print $gm_lang["sync_gedcom"];?></td>
      <td class="shade1 wrap"><input type="checkbox" name="new_sync_gedcom" tabindex="<?php $tab++; print $tab; ?>" value="Y" <?php if ($user['sync_gedcom']=="Y") print "checked=\"checked\""; ?> /></td>
    </tr>
    <tr>
      <td class="shade2 wrap"><?php print_help_link("useradmin_can_admin_help", "qm", "can_admin"); print $gm_lang["can_admin"];?></td>
      <td class="shade1 wrap"><input type="checkbox" name="canadmin" tabindex="<?php $tab++; print $tab; ?>" value="yes" <?php if ($user['canadmin']) print "checked=\"checked\""; if ($user["username"]==$gm_username) print " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr>
      <td class="shade2 wrap"><?php print_help_link("useradmin_can_edit_help", "qm","can_edit"); print $gm_lang["can_edit"];?></td>
      <td class="shade1 wrap">
	 <table class="<?php print $TEXT_DIRECTION; ?>">
      <?php
	foreach($GEDCOMS as $ged=>$gedarray) {
		$file = $ged;
		$ged = preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $ged);
		print "<tr><td>$file:&nbsp;&nbsp;</td><td>";
		if (isset($user['canedit'][$file])) {
			if ($user['canedit'][$file]===true) $user['canedit'][$file]="yes";
		}
		else $user['canedit'][$file]="no";
		$tab++;
		print "<select name=\"canedit_$ged\" tabindex=\"".$tab."\">\n";
		print "<option value=\"none\"";
		if ($user['canedit'][$file]=="none") print " selected=\"selected\"";
		print ">".$gm_lang["none"]."</option>\n";
		print "<option value=\"access\"";
		if ($user['canedit'][$file]=="access") print " selected=\"selected\"";
		print ">".$gm_lang["access"]."</option>\n";
		print "<option value=\"edit\"";
		if ($user['canedit'][$file]=="edit") print " selected=\"selected\"";
		print ">".$gm_lang["edit"]."</option>\n";
		print "<option value=\"accept\"";
		if ($user['canedit'][$file]=="accept") print " selected=\"selected\"";
		print ">".$gm_lang["accept"]."</option>\n";
		print "<option value=\"admin\"";
		if ($user['canedit'][$file]=="admin") print " selected=\"selected\"";
		print ">".$gm_lang["admin_gedcom"]."</option>\n";
		print "</select>\n";
		print "</td></tr>";
	}
	?>
	</table>
      </td>
    </tr>
    <tr><td class="shade2 wrap"><?php print_help_link("useradmin_auto_accept_help", "qm", "user_auto_accept"); print $gm_lang["user_auto_accept"];?></td>
    <td class="shade1 wrap"><input type="checkbox" name="new_auto_accept" tabindex="<?php $tab++; print $tab; ?>" value="Y" <?php if ($user["auto_accept"]) print "checked=\"checked\"";?> /></td></tr>
    <tr><td class="shade2 wrap"><?php print_help_link("useradmin_relation_priv_help", "qm", "user_relationship_priv"); print $gm_lang["user_relationship_priv"];?></td>
    <td class="shade1 wrap"><input type="checkbox" name="new_relationship_privacy" tabindex="<?php $tab++; print $tab; ?>" value="Y" <?php if ($user["relationship_privacy"]=="Y") print "checked=\"checked\"";?> /></td></tr>
    <tr><td class="shade2 wrap"><?php print_help_link("useradmin_path_length_help", "qm", "user_path_length"); print $gm_lang["user_path_length"];?></td>
    <td class="shade1 wrap"><input type="text" name="new_max_relation_path" tabindex="<?php $tab++; print $tab; ?>" value="<?php print $user["max_relation_path"]; ?>" size="5" /></td></tr>
    <tr><td class="shade2 wrap"><?php print_help_link("useradmin_email_help", "qm", "emailadress"); print $gm_lang["emailadress"];?></td><td class="shade1 wrap"><input type="text" name="emailadress" tabindex="<?php $tab++; print $tab; ?>" dir="ltr" value="<?php print $user['email']?>" size="50" /></td></tr>
    <tr><td class="shade2 wrap"><?php print_help_link("useradmin_verified_help", "qm", "verified"); print $gm_lang["verified"];?></td><td class="shade1 wrap"><input type="checkbox" name="verified" tabindex="<?php $tab++; print $tab; ?>" value="yes" <?php if ($user['verified']) print "checked=\"checked\"";?> /></td></tr>
    <tr><td class="shade2 wrap"><?php print_help_link("useradmin_verbyadmin_help", "qm", "verified_by_admin"); print $gm_lang["verified_by_admin"];?></td><td class="shade1 wrap"><input type="checkbox" name="verified_by_admin" tabindex="<?php $tab++; print $tab; ?>" value="yes" <?php if ($user['verified_by_admin']) print "checked=\"checked\""; ?> /></td></tr>
    <tr><td class="shade2 wrap"><?php print_help_link("edituser_change_lang_help", "qm", "change_lang");print $gm_lang["change_lang"];?></td><td class="shade1 wrap" valign="top"><?php
	if ($ENABLE_MULTI_LANGUAGE) {
		$tab++;
		print "<select name=\"user_language\" tabindex=\"".$tab."\" dir=\"ltr\" style=\"{ font-size: 9pt; }\">";
		foreach ($gm_language as $key => $value) {
			if ($language_settings[$key]["gm_lang_use"]) {
				print "\n\t\t\t<option value=\"$key\"";
				if ($key == $user["language"]) print " selected=\"selected\"";
				print ">" . $gm_lang[$key] . "</option>";
			}
		}
		print "</select>\n\t\t";
	}
	else print "&nbsp;";
    ?></td></tr>
    <?php if ($ALLOW_USER_THEMES) { ?>
    <tr><td class="shade2 wrap" valign="top" align="left"><?php print_help_link("useradmin_user_theme_help", "qm", "user_theme"); print $gm_lang["user_theme"];?></td><td class="shade1 wrap" valign="top">
    	<select name="user_theme" tabindex="<?php $tab++; print $tab; ?>" dir="ltr">
    	  <option value=""><?php print $gm_lang["site_default"]; ?></option>
    	  <?php
    	    $themes = get_theme_names();
    	    foreach($themes as $indexval => $themedir)
    	    {
    	      print "<option value=\"".$themedir["dir"]."\"";
    	      if ($themedir["dir"] == $user["theme"]) print " selected=\"selected\"";
    	      print ">".$themedir["name"]."</option>\n";
    	    }
	?></select>
      </td>
    </tr>
    <?php } ?>
    <tr>
		<td class="shade2 wrap"><?php print_help_link("useradmin_user_contact_help", "qm", "user_contact_method"); print $gm_lang["user_contact_method"];?></td>
		<td class="shade1 wrap"><select name="new_contact_method" tabindex="<?php $tab++; print $tab; ?>">
		<?php if ($GM_STORE_MESSAGES) { ?>
				<option value="messaging" <?php if ($user['contactmethod']=='messaging') print "selected=\"selected\""; ?>><?php print $gm_lang["messaging"];?></option>
				<option value="messaging2" <?php if ($user['contactmethod']=='messaging2') print "selected=\"selected\""; ?>><?php print $gm_lang["messaging2"];?></option>
		<?php } else { ?>
				<option value="messaging3" <?php if ($user['contactmethod']=='messaging3') print "selected=\"selected\""; ?>><?php print $gm_lang["messaging3"];?></option>
		<?php } ?>
				<option value="mailto" <?php if ($user['contactmethod']=='mailto') print "selected=\"selected\""; ?>><?php print $gm_lang["mailto"];?></option>
				<option value="none" <?php if ($user['contactmethod']=='none') print "selected=\"selected\""; ?>><?php print $gm_lang["no_messaging"];?></option>
			</select>
		</td>
	</tr>
	<tr>
      <td class="shade2 wrap"><?php print_help_link("useradmin_visibleonline_help", "qm", "visibleonline"); print $gm_lang["visibleonline"];?></td>
      <td class="shade1 wrap"><input type="checkbox" name="visibleonline" tabindex="<?php $tab++; print $tab; ?>" value="yes" <?php if ($user['visibleonline']) print "checked=\"checked\""; ?> /></td>
    </tr>
    <tr>
      <td class="shade2 wrap"><?php print_help_link("useradmin_editaccount_help", "qm", "editaccount"); print $gm_lang["editaccount"];?></td>
      <td class="shade1 wrap"><input type="checkbox" name="editaccount" tabindex="<?php $tab++; print $tab; ?>" value="yes" <?php if ($user['editaccount']) print "checked=\"checked\""; ?> /></td>
    </tr>
    <tr>
		<td class="shade2 wrap"><?php print_help_link("useradmin_user_default_tab_help", "qm", "user_default_tab"); print $gm_lang["user_default_tab"];?></td>
		<td class="shade1 wrap"><select name="new_default_tab" tabindex="<?php $tab++; print $tab; ?>">
				<option value="0" <?php if (@$user['default_tab']==0) print "selected=\"selected\""; ?>><?php print $gm_lang["personal_facts"];?></option>
				<option value="1" <?php if (@$user['default_tab']==1) print "selected=\"selected\""; ?>><?php print $gm_lang["notes"];?></option>
				<option value="2" <?php if (@$user['default_tab']==2) print "selected=\"selected\""; ?>><?php print $gm_lang["ssourcess"];?></option>
				<option value="3" <?php if (@$user['default_tab']==3) print "selected=\"selected\""; ?>><?php print $gm_lang["media"];?></option>
				<option value="4" <?php if (@$user['default_tab']==4) print "selected=\"selected\""; ?>><?php print $gm_lang["relatives"];?></option>
				<option value="5" <?php if (@$user['default_tab']==5) print "selected=\"selected\""; ?>><?php print $gm_lang["all"];?></option>
			</select>
		</td>
	</tr>
	<tr>
	  <td class="shade2 wrap"><?php print_help_link("useradmin_comment_help", "qm", "comment"); print $gm_lang["comment"];?></td>
      <td class="shade1 wrap"><textarea cols="50" rows="5" name="new_comment" tabindex="<?php $tab++; print $tab; ?>" ><?php $tmp = stripslashes(PrintReady($user['comment'])); print $tmp; ?></textarea></td>
    </tr>
	<tr>
	  <td class="shade2 wrap"><?php print_help_link("useradmin_comment_exp_help", "qm", "comment_exp"); print $gm_lang["comment_exp"];?></td>
      <td class="shade1 wrap"><input type="text" name="new_comment_exp" id="new_comment_exp" tabindex="<?php $tab++; print $tab; ?>" value="<?php print $user["comment_exp"]; ?>" />&nbsp;&nbsp;<?php print_calendar_popup("new_comment_exp"); ?></td>
    </tr>
    <tr><td class="admin_topbottombar" colspan="2">
  	<input type="submit" tabindex="<?php $tab++; print $tab; ?>" value="<?php print $gm_lang["update_user"]; ?>" />
	<input type="button" tabindex="<?php $tab++; print $tab; ?>" value="<?php print $gm_lang["back"];?>" onclick="window.location='useradmin.php?action=listusers&amp;sort=<?php print $sort;?>&amp;filter=<?php print $filter;?>&amp;usrlang=<?php print $usrlang;?>&amp;ged=<?php print $ged;?>&amp;namefilter=<?php print $namefilter;?>';"/>
	</td></tr>
	</table>
	</form>
	<?php
  print_footer();
  exit;
}
//-- end of $action=='edituser'

if ($action == "massupdate") {
	
	// -- Count the number of users to be updated
	$userlist = GetUsers();
	foreach ($userlist as $key => $user) {
		$str = "select".$user["username"];
		if (!isset($$str)) unset($userlist[$key]);
	}
	
	// -- Determine the width of the table
	$colmax = count($userlist);
	// To do: return to userlist if 0
	if ($colmax > 5) $colmax = 5;
	$colcount = $colmax;
	
	// -- Start print the form/table
	print "<form name=\"massupdate\" method=\"post\" action=\"useradmin.php\">";
	print "<input type=\"hidden\" name=\"action\" value=\"massupdate2\" />";
	print "<input type=\"hidden\" name=\"sort\" value=\"".$sort."\" />";
	print "<input type=\"hidden\" name=\"filter\" value=\"".$filter."\" />";
	print "<input type=\"hidden\" name=\"usrlang\" value=\"".$usrlang."\" />";
	print "<input type=\"hidden\" name=\"ged\" value=\"".$ged."\" />";
	print "<input type=\"hidden\" name=\"namefilter\" value=\"".$namefilter."\" />";
	print "<table class=\"center list_table width80 ".$TEXT_DIRECTION."\">";

	// -- Print the title lines
	print "<tr><td colspan=\"".$colmax."\" class=\"facts_label\"><h2>".$gm_lang["mass_update"]."</h2></td></tr>";
    print "<tr><td colspan=\"".$colmax."\" class=\"admin_topbottombar\">".$gm_lang["mu_users"]."</td>";

    // -- Print the users
    foreach ($userlist as $key => $user) {
    	print "<input type=\"hidden\" name=\"select".$user["username"]."\" value=\"yes\" />";
		if ($colcount == $colmax) {
			$colcount = 0;
			print "</tr><tr>";
		}
		if ($TEXT_DIRECTION=="ltr") print "<td class=\"shade1\">".$user["username"]." - ".$user["firstname"]." ".$user["lastname"]."&lrm;</td>";
		else                        print "<td class=\"shade1\">".$user["username"].$user["firstname"]." ".$user["lastname"]."&rlm;</td>";
		$colcount++;
	}

	// -- Fill the remaining row cells
	for ($i=$colcount; $i<$colmax; $i++) print "<td class=\"shade1\">&nbsp;</td>";
	print "</tr>";
    print "<tr><td colspan=\"".$colmax."\" class=\"admin_topbottombar\">&nbsp</td></tr>";
	print "</table><br />";

	// -- print the form 
	print "<table class=\"center list_table width80 ".$TEXT_DIRECTION."\">";
	
	// -- print the top bar
	print "<tr><td class=\"admin_topbottombar\" colspan=\"3\">".$gm_lang["mu_options"]."</td></tr>";
	print "<tr><td class=\"admin_topbottombar width20\">".$gm_lang["mu_descr"]."</td><td class=\"admin_topbottombar\">".$gm_lang["select"]."</td><td class=\"admin_topbottombar\">".$gm_lang["mu_new_value"]."</td></tr>";

	// -- Print the options
	// -- Rootid
	$tab = 0;
	print "<tr>";
	print "<td class=\"shade2 wrap width20\" rowspan=\"".count($GEDCOMS)."\">".print_help_link("useradmin_rootid_help", "qm","rootid", "", true).$gm_lang["rootid"]."</td>";
	foreach($GEDCOMS as $ged=>$gedarray) {
		print "<td class=\"shade1 wrap center\">";
		$file = $ged;
		$ged = preg_replace(array("/\./","/-/"), array("_","_"), $ged);
		$tab++;
		print "<input type=\"checkbox\" name=\"change_rootid_$ged\" tabindex=\"".$tab."\" value=\"Y\" /></td>";
		print "<td class=\"shade1 wrap\">";
		$tab++;
		print "<input type=\"text\" name=\"new_rootid_$ged\" id=\"new_rootid_$ged\" tabindex=\"".$tab."\" value=\"\" />";
		print_findindi_link("new_rootid_$ged","");
		print "&nbsp;".$file."</td></tr><tr>";
	}

	// -- Sync with gedcom
	$tab++;
	print "<td class=\"shade2 wrap width20\">".print_help_link("useradmin_sync_gedcom_help", "qm","sync_gedcom", "", true).$gm_lang["sync_gedcom"]."</td>";
	$tab++;
	print "<td class=\"shade1 wrap center\"><input type=\"checkbox\" name=\"change_sync_gedcom\" tabindex=\"".$tab."\" value=\"Y\" /></td>";
	$tab++;
	print "<td class=\"shade1 wrap\"><input type=\"checkbox\" name=\"new_sync_gedcom\" tabindex=\"".$tab."\" value=\"Y\" /></td></tr>";
		
	// -- Access level
	print "<tr><td class=\"shade2 wrap width20\" rowspan=\"".count($GEDCOMS)."\">".print_help_link("useradmin_can_edit_help", "qm","can_edit", "", true).$gm_lang["can_edit"]."</td>";
	foreach($GEDCOMS as $ged=>$gedarray) {
		print "<td class=\"shade1 wrap center\">";
		$file = $ged;
		$ged = preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $ged);
		$tab++;
		print "<input type=\"checkbox\" name=\"change_canedit_$ged\" tabindex=\"".$tab."\" value=\"Y\" /></td>";
		print "<td class=\"shade1 wrap\">";
		$tab++;
		print "<select name=\"new_canedit_$ged\" tabindex=\"".$tab."\">\n";
		print "<option value=\"none\" selected=\"selected\"";
		print ">".$gm_lang["none"]."</option>\n";
		print "<option value=\"access\"";
		print ">".$gm_lang["access"]."</option>\n";
		print "<option value=\"edit\"";
		print ">".$gm_lang["edit"]."</option>\n";
		print "<option value=\"accept\"";
		print ">".$gm_lang["accept"]."</option>\n";
		print "<option value=\"admin\"";
		print ">".$gm_lang["admin_gedcom"]."</option>\n";
		print "</select>&nbsp;".$file."</td></tr><tr>";
	}

	// -- Auto accept
	print "<td class=\"shade2 wrap width20\">".print_help_link("useradmin_auto_accept_help", "qm", "user_auto_accept", "", true).$gm_lang["user_auto_accept"]."</td>";
	$tab++;
	print "<td class=\"shade1 wrap center\"><input type=\"checkbox\" name=\"change_auto_accept\" tabindex=\"".$tab."\" value=\"Y\" /></td>";
	$tab++;
	print "<td class=\"shade1 wrap\"><input type=\"checkbox\" name=\"new_auto_accept\" tabindex=\"".$tab."\" value=\"Y\" /></td></tr>";
	
	// -- Relationship privacy
	print "<tr><td class=\"shade2 wrap width20\">".print_help_link("useradmin_relation_priv_help", "qm", "user_relationship_priv", "", true).$gm_lang["user_relationship_priv"]."</td>";
	$tab++;
	print "<td class=\"shade1 wrap center\"><input type=\"checkbox\" name=\"change_relationship_privacy\" tabindex=\"".$tab."\" value=\"Y\" /></td>";
	$tab++;
	print "<td class=\"shade1 wrap\"><input type=\"checkbox\" name=\"new_relationship_privacy\" tabindex=\"".$tab."\" value=\"Y\" /></td></tr>";
	
	// -- Relation path length
	print "<tr><td class=\"shade2 wrap width20\">".print_help_link("useradmin_path_length_help", "qm", "user_path_length", "", true).$gm_lang["user_path_length"]."</td>";
	$tab++;
	print "<td class=\"shade1 wrap center\"><input type=\"checkbox\" name=\"change_max_relation_path\" tabindex=\"".$tab."\" value=\"Y\" /></td>";
	$tab++;
	print "<td class=\"shade1 wrap\"><input type=\"text\" name=\"new_max_relation_path\" tabindex=\"".$tab."\" value=\"0\" size=\"5\" /></td></tr>";
	
	// -- User theme
	if ($ALLOW_USER_THEMES) {
		print "<tr><td class=\"shade2 wrap width20\" valign=\"top\" align=\"left\">".print_help_link("useradmin_user_theme_help", "qm", "user_theme", "", true).$gm_lang["user_theme"]."</td>"; 
		$tab++;
		print "<td class=\"shade1 wrap center\"><input type=\"checkbox\" name=\"change_user_theme\" tabindex=\"".$tab."\" value=\"Y\" /></td>";
		$tab++;
		print "<td class=\"shade1 wrap\" valign=\"top\">";
		print "<select name=\"new_user_theme\" tabindex=\"".$tab."\">";
		print "<option value=\"\" selected=\"selected\">".$gm_lang["site_default"]."</option>";
		$themes = get_theme_names();
		foreach($themes as $indexval => $themedir) {
			print "<option value=\"".$themedir["dir"]."\"";
			print ">".$themedir["name"]."</option>\n";
		}
		print "</select>";
		print "</td></tr>";
	}
	// -- Contact method
	print "<tr><td class=\"shade2 wrap width20\">".print_help_link("useradmin_user_contact_help", "qm", "user_contact_method", "", true).$gm_lang["user_contact_method"]."</td>";
	$tab++;
	print "<td class=\"shade1 wrap center\"><input type=\"checkbox\" name=\"change_contact_method\" tabindex=\"".$tab."\" value=\"Y\" /></td>";
	$tab++;
	print "<td class=\"shade1 wrap\"><select name=\"new_contact_method\" tabindex=\"".$tab."\">";
	if ($GM_STORE_MESSAGES) {
		print "<option value=\"messaging\">".$gm_lang["messaging"]."</option>";
		print "<option value=\"messaging2\" selected=\"selected\">".$gm_lang["messaging2"]."</option>";
	} 
	else {
		print "<option value=\"messaging3\" selected=\"selected\">".$gm_lang["messaging3"]."</option>";
	}
	print "<option value=\"mailto\">".$gm_lang["mailto"]."</option>";
	print "<option value=\"none\">".$gm_lang["no_messaging"]."</option>";
	print "</select>";
	print "</td></tr>";
		
	// -- Visible online	
	print "<tr><td class=\"shade2 wrap width20\">".print_help_link("useradmin_visibleonline_help", "qm", "visibleonline", "", true).$gm_lang["visibleonline"]."</td>";
	$tab++;
	print "<td class=\"shade1 wrap center\"><input type=\"checkbox\" name=\"change_visibleonline\" tabindex=\"".$tab."\" value=\"Y\" /></td>";
	$tab++;
	print "<td class=\"shade1 wrap\"><input type=\"checkbox\" name=\"new_visibleonline\" tabindex=\"".$tab."\" value=\"Y\" checked=\"checked\" /></td></tr>";

	// -- Edit account		
	print "<tr><td class=\"shade2 wrap width20\">".print_help_link("useradmin_editaccount_help", "qm", "editaccount", "", true).$gm_lang["editaccount"]."</td>";
	$tab++;
	print "<td class=\"shade1 wrap center\"><input type=\"checkbox\" name=\"change_editaccount\" tabindex=\"".$tab."\" value=\"Y\" /></td>";
	$tab++;
	print "<td class=\"shade1 wrap\"><input type=\"checkbox\" name=\"new_editaccount\" tabindex=\"".$tab." value=\"Y\" checked=\"checked\" /></td></tr>";
	
	// -- Default tab
	print "<tr><td class=\"shade2 wrap width20\">".print_help_link("useradmin_user_default_tab_help", "qm", "user_default_tab", "", true).$gm_lang["user_default_tab"]."</td>";
	$tab++;
	print "<td class=\"shade1 wrap center\"><input type=\"checkbox\" name=\"change_default_tab\" tabindex=\"".$tab."\" value=\"Y\" /></td>";
	$tab++;
	print "<td class=\"shade1 wrap\"><select name=\"new_default_tab\" tabindex=\"".$tab."\">";
	print "<option value=\"0\">".$gm_lang["personal_facts"]."</option>";
	print "<option value=\"1\">".$gm_lang["notes"]."</option>";
	print "<option value=\"2\">".$gm_lang["ssourcess"]."</option>";
	print "<option value=\"3\">".$gm_lang["media"]."</option>";
	print "<option value=\"4\">".$gm_lang["relatives"]."</option>";
	print "<option value=\"5\">".$gm_lang["all"]."</option>";
	print "</select>";
	print "</td></tr>";
	
	// -- bottom bar
	print "<tr><td class=\"admin_topbottombar\" colspan=\"3\">";
	print "<input type=\"submit\" tabindex=\"".$tab."\" value=\"".$gm_lang["mass_update"]."\" />&nbsp;";
	print "<input type=\"button\" tabindex=\"".$tab."\" value=\"".$gm_lang["back"]."\" onclick=\"window.location='useradmin.php?action=listusers&amp;sort=".$sort."&amp;filter=".$filter."&amp;usrlang=".$usrlang."&amp;ged=".$ged."&amp;namefilter=".$namefilter."';\" />";
	print "</td></tr>";
	
	print "</table>";
	print "</form><br />";
	print_footer();
	exit;
}

// -- Perform the mass update
if ($action == "massupdate2") {

	// -- Get the users to be updated
	$userlist = GetUsers();
	foreach ($userlist as $key => $user) {
		$str = "select".$user["username"];
		if (!isset($$str)) unset($userlist[$key]);
	}
	// -- Do the update
	foreach ($userlist as $key => $user) {
		$newuser = array();
		$newuser = $user;
		
		foreach($GEDCOMS as $ged=>$gedarray) {
			$file = $ged;
			$ged = preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $ged);
			$varname = "new_rootid_$ged";
			$chname = "change_rootid_$ged";
			if (isset($$chname)) {
				if (isset($$varname)) $newuser["rootid"][$file]=$$varname;
			}
			$chname = "change_canedit_$ged";
			$varname = "new_canedit_$ged";
			if (isset($$chname)) $newuser["canedit"][$file]=$$varname;
		}
		if (isset($change_auto_accept)) {
			if (isset($new_auto_accept)) $newuser["auto_accept"] = true;
			else $newuser["auto_accept"] = false;
		}
		if (isset($change_relationship_privacy)) {
			if ((isset($new_relationship_privacy)) && ($new_relationship_privacy=="Y")) $newuser["relationship_privacy"] = "Y";
			else $newuser["relationship_privacy"] = "N";
		}
		if (isset($change_max_relation_path)) {
			if (isset($new_max_relation_path)) $newuser["max_relation_path"] = $new_max_relation_path;
		}
		if (isset($change_user_theme)) {
			if (!isset($new_user_theme)) $new_user_theme="";
			$newuser["theme"] = $new_user_theme;
		}
		if (isset($change_contact_method)) {
			if (!empty($new_contact_method)) $newuser["contactmethod"] = $new_contact_method;
		}
		if (isset($change_visibleonline)) {
			if (isset($new_visibleonline)) $newuser["visibleonline"]=true;
			else $newuser["visibleonline"]=false;
		}
		if (isset($change_editaccount)) {
			if (isset($new_editaccount)) $newuser["editaccount"]=true;
			else $newuser["editaccount"]=false;
		}
		if (isset($change_default_tab)) {
			if (isset($new_default_tab)) $newuser["default_tab"] = $new_default_tab;
		}
		if (isset($change_sync_gedcom)) {
			if (isset($new_sync_gedcom)) $newuser["sync_gedcom"] = "Y";
			else $newuser["sync_gedcom"] = "N";
		}
		deleteUser($user["username"], "changed");
		addUser($newuser, "changed");
	}
}

//-- print out a list of the current users
// NOTE: WORKING
if (($action == "listusers") || ($action == "edituser2") || ($action == "deleteuser") || ($action == "massupdate2")) {
	if ($view != "preview") $showprivs = false;
	else $showprivs = true;

	switch ($sort){
		case "sortfname":
			$users = getUsers("firstname","asc", "lastname");
			break;
		case "sortlname":
			$users = getUsers("lastname","asc", "firstname");
			break;
		case "sortllgn":
			$users = getUsers("sessiontime","desc");
			break;
		case "sortuname":
			$users = getUsers("username","asc");
			break;
		case "sortreg":
			$users = getUsers("reg_timestamp","desc");
			break;
		case "sortver":
			$users = getUsers("verified","asc");
			break;
		case "sortveradm":
			$users = getUsers("verified_by_admin","asc");
			break;
		default: $users = getUsers("username","asc");
	}
	
	// First filter the users, otherwise the javascript to unfold priviledges gets disturbed
	foreach($users as $username=>$user) {
		if ($filter == "warnings") {
			if (!empty($user["comment_exp"])) {
				if ((strtotime($user["comment_exp"]) == "-1") || (strtotime($user["comment_exp"]) >= time("U"))) unset($users[$username]);
			}
			else if (((date("U") - $user["reg_timestamp"]) <= 604800) || ($user["verified"]=="yes")) unset($users[$username]);
		}
		else if ($filter == "adminusers") {
			if (!($user["canadmin"])) unset($users[$username]);
		}
		else if ($filter == "usunver") {
			if ($user["verified"] == "yes") unset($users[$username]);
		}
		else if ($filter == "admunver") {
			if (($user["verified_by_admin"] == "yes") || ($user["verified"] != "yes")) unset($users[$username]);
		}
		else if ($filter == "language") {
			if ($user["language"] != $usrlang) unset($users[$username]);
		}
		else if ($filter == "gedadmin") {
			if (isset($user["canedit"][$ged])) {
				if ($user["canedit"][$ged] != "admin") unset($users[$username]);
			}
		}
	}
	// If a name filter is entered, check for existence of the string in the user fullname
	if (!empty($namefilter)) {
		foreach($users as $username=>$user) {
			if (!stristr($user["firstname"], $namefilter) && !stristr($user["lastname"], $namefilter)&& !stristr($user["username"], $namefilter)) unset($users[$username]);
		}
	}
		
	
	// Then show the users
	?>
	<form name="userlist" method="post" action="useradmin.php">
	<input type="hidden" name="action" value="massupdate" />
	<input type="hidden" name="sort" value="<?php print $sort; ?>" />
	<input type="hidden" name="filter" value="<?php print $filter; ?>" />
	<input type="hidden" name="usrlang" value="<?php print $usrlang; ?>" />
	<input type="hidden" name="ged" value="<?php print $ged; ?>" />
	<table class="center list_table width80 <?php print $TEXT_DIRECTION; ?>">
	<tr><td colspan="<?php if ($view == "preview") print "9"; else print "10"; ?>" class="admin_topbottombar"><?php
		print "<h2>".$gm_lang["current_users"]."</h2>";
	?>
	</td></tr>
    <tr>
	  <td colspan="<?php if ($view == "preview") print "5"; else print "6"; ?>" class="admin_topbottombar ltr"><?php print $gm_lang["usernamefilter"];?>&nbsp;<input type="text" name="namefilter" value="<?php print $namefilter;?>" />  	<input type="submit" name="refreshlist" value="<?php print $gm_lang["refresh"]; ?>" /></td>
	  <td colspan="4" class="admin_topbottombar rtl"><a href="useradmin.php"><?php if ($view != "preview") print $gm_lang["back_useradmin"]; else print "&nbsp;";?></a></td>
    </tr>
	<tr>
		<?php if ($view != "preview") {
			print "<td class=\"shade2 wrap\">".$gm_lang["select"]."</td>";
			print "<td class=\"shade2 wrap\">".$gm_lang["delete"];
			print "<br />".$gm_lang["edit"]."</td>";
		} ?>
		<td class="shade2 wrap"><?php print "<a href=\"useradmin.php?action=listusers&amp;sort=sortuname&amp;filter=".$filter."&amp;usrlang=".$usrlang."&amp;ged=".$ged."\">"; ?><?php print $gm_lang["username"]; ?></a></td>
		<td class="shade2 wrap"><?php print "<a href=\"useradmin.php?action=listusers&amp;sort=sortlname&amp;filter=".$filter."&amp;usrlang=".$usrlang."&amp;ged=".$ged."\">"; ?><?php print $gm_lang["full_name"]; ?></a></td>
		<td class="shade2 wrap"><?php print $gm_lang["inc_languages"]; ?></td>
		<td class="shade2" style="padding-left:2px"><a href="javascript: <?php print $gm_lang["privileges"];?>" onclick="<?php
		$k = 1;
		for ($i=1, $max=count($users)+1; $i<=$max; $i++) print "expand_layer('user-geds".$i."'); ";
		print " return false;\"><img id=\"user-geds".$k."_img\" src=\"".$GM_IMAGE_DIR."/";
		if ($showprivs == false) print $GM_IMAGES["plus"]["other"];
		else print $GM_IMAGES["minus"]["other"];
		print "\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a>";
		print "<div id=\"user-geds".$k."\" style=\"display: ";
		if ($showprivs == false) print "none\">";
		else print "block\">";
		print "</div>&nbsp;";
		print $gm_lang["privileges"];?>
		</td>
		<td class="shade2 wrap"><?php print "<a href=\"useradmin.php?action=listusers&amp;sort=sortreg&amp;filter=".$filter."&amp;usrlang=".$usrlang."&amp;ged=".$ged."\">"; ?><?php print $gm_lang["date_registered"]; ?></a></td>
		<td class="shade2 wrap"><?php print "<a href=\"useradmin.php?action=listusers&amp;sort=sortllgn&amp;filter=".$filter."&amp;usrlang=".$usrlang."&amp;ged=".$ged."\">"; ?><?php print $gm_lang["last_login"]; ?></a></td>
		<td class="shade2 wrap"><?php print "<a href=\"useradmin.php?action=listusers&amp;sort=sortver&amp;filter=".$filter."&amp;usrlang=".$usrlang."&amp;ged=".$ged."\">"; ?><?php print $gm_lang["verified"]; ?></a></td>
		<td class="shade2 wrap"><?php print "<a href=\"useradmin.php?action=listusers&amp;sort=sortveradm&amp;filter=".$filter."&amp;usrlang=".$usrlang."&amp;ged=".$ged."\">"; ?><?php print $gm_lang["verified_by_admin"]; ?></a></td>
	</tr>
	<?php
	$k++;
	foreach($users as $username=>$user) {
		if (empty($user["language"])) $user["language"]=$LANGUAGE;
		print "<tr>\n";
		if ($view != "preview") {
			print "<td class=\"shade1 wrap\"><input type=\"checkbox\" name=\"select".$username."\" value=\"yes\" /></td>";
			print "\t<td class=\"shade1 wrap\">";
			if ($user["username"] != $gm_username) {
				if ($TEXT_DIRECTION=="ltr") print "<a href=\"useradmin.php?action=deleteuser&amp;username=".urlencode($username)."&amp;sort=".$sort."&amp;filter=".$filter."&amp;usrlang=".$usrlang."&amp;ged=".$ged."\" onclick=\"return confirm('".$gm_lang["confirm_user_delete"]." $username?');\">".$gm_lang["delete"]."</a><br />\n";
				else if (begRTLText($username)) print "<a href=\"useradmin.php?action=deleteuser&amp;username=".urlencode($username)."&amp;sort=".$sort."&amp;filter=".$filter."&amp;usrlang=".$usrlang."&amp;ged=".$ged."\" onclick=\"return confirm('?".$gm_lang["confirm_user_delete"]." $username');\">".$gm_lang["delete"]."</a><br />\n";
				else print "<a href=\"useradmin.php?action=deleteuser&amp;username=".urlencode($username)."&amp;sort=".$sort."&amp;filter=".$filter."&amp;usrlang=".$usrlang."&amp;ged=".$ged."\" onclick=\"return confirm('?$username ".$gm_lang["confirm_user_delete"]." ');\">".$gm_lang["delete"]."</a><br />\n";
			}
			print "<a href=\"useradmin.php?action=edituser&amp;username=".urlencode($username)."&amp;sort=".$sort."&amp;filter=".$filter."&amp;usrlang=".$usrlang."&amp;ged=".$ged."\">".$gm_lang["edit"]."</a></td>\n";
		}
		if (!empty($user["comment_exp"])) {
			if ((strtotime($user["comment_exp"]) != "-1") && (strtotime($user["comment_exp"]) < time("U"))) print "\t<td class=\"shade1 red\">".$username;
			else print "\t<td class=\"shade1 wrap\">".$username;
		}
		else print "\t<td class=\"shade1 wrap\">".$username;
		if (!empty($user["comment"])) print "<br /><img class=\"adminicon\" title=\"".PrintReady(stripslashes($user["comment"]))."\" width=\"20\" height=\"20\" align=\"top\" alt=\"".PrintReady(stripslashes($user["comment"]))."\"  src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["note"]["other"]."\">";
		print "</td>\n";
		if ($TEXT_DIRECTION=="ltr") print "\t<td class=\"shade1 wrap\">".$user["firstname"]." ".$user["lastname"]."&lrm;</td>\n";
		else                        print "\t<td class=\"shade1 wrap\">".$user["firstname"]." ".$user["lastname"]."&rlm;</td>\n";
		print "\t<td class=\"shade1 wrap\">".$gm_lang["lang_name_".$user["language"]]."<br /><img src=\"".$language_settings[$user["language"]]["flagsfile"]."\" class=\"brightflag\" alt=\"".$gm_lang["lang_name_".$user["language"]]."\" title=\"".$gm_lang["lang_name_".$user["language"]]."\" /></td>\n";
		print "\t<td class=\"shade1\">";
		print "<a href=\"javascript: ".$gm_lang["privileges"]."\" onclick=\"expand_layer('user-geds".$k."'); return false;\"><img id=\"user-geds".$k."_img\" src=\"".$GM_IMAGE_DIR."/";
		if ($showprivs == false) print $GM_IMAGES["plus"]["other"];
		else print $GM_IMAGES["minus"]["other"];
		print "\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" />";
		print "</a>";
		print "<div id=\"user-geds".$k."\" style=\"display: ";
		if ($showprivs == false) print "none\">";
		else print "block\">";
		print "<ul>";
		if ($user["canadmin"]) print "<li class=\"warning\">".$gm_lang["can_admin"]."</li>\n";
		uksort($GEDCOMS, "strnatcasecmp");
		reset($GEDCOMS);
		foreach($GEDCOMS as $gedid=>$gedcom) {
			if (isset($user["canedit"][$gedid])) $vval = $user["canedit"][$gedid];
			else $vval = "none";
			if ($vval == "") $vval = "none";
			if (isset($user["gedcomid"][$gedid])) $uged = $user["gedcomid"][$gedid];
			else $uged = "";
			if ($vval=="accept") print "<li class=\"warning\">"; 
			else print "<li>";
			print $gm_lang[$vval]." ";
			if ($uged != "") print "<a href=\"individual.php?pid=".$uged."&amp;ged=".$gedid."\">".$gedid."</a></li>\n";
			else print $gedid."</li>\n";
		}
		print "</ul>";
		print "</div>";
		$k++;
		print "</td>\n";
		if (((date("U") - $user["reg_timestamp"]) > 604800) && ($user["verified"]!="yes")) print "\t<td class=\"shade1 red\">";
		else print "\t<td class=\"shade1 wrap\">";
		print get_changed_date(date("d", $user["reg_timestamp"])." ".date("M", $user["reg_timestamp"])." ".date("Y", $user["reg_timestamp"]))." - ".date($TIME_FORMAT, $user["reg_timestamp"]);
		print "</td>\n";
		print "\t<td class=\"shade1 wrap\">";
		if ($user["reg_timestamp"] > $user["sessiontime"]) {
			print $gm_lang["never"];
		}
		else {
			print get_changed_date(date("d", $user["sessiontime"])." ".date("M", $user["sessiontime"])." ".date("Y", $user["sessiontime"]))." - ".date($TIME_FORMAT, $user["sessiontime"]);
		}
		print "</td>\n";
		print "\t<td class=\"shade1 wrap\">";
		if ($user["verified"]=="yes") print $gm_lang["yes"];
		else print $gm_lang["no"];
		print "</td>\n";
		print "\t<td class=\"shade1 wrap\">";
		if ($user["verified_by_admin"]=="yes") print $gm_lang["yes"];
		else print $gm_lang["no"];
		print "</td>\n";
		print "</tr>\n";
	}
	?>
	<tr><td colspan="<?php if ($view == "preview") print "5"; else print "6"; ?>" class="admin_topbottombar ltr">
	<a href="javascript: <?php print $gm_lang["do_massupdate"]; ?>" onclick="document.userlist.submit();return false;">
	<?php  if ($view != "preview") print $gm_lang["do_massupdate"]; else print "&nbsp;"; ?>
	</a>
	</td><td colspan="4" class="admin_topbottombar rtl"><a href="useradmin.php"><?php  if ($view != "preview") print $gm_lang["back_useradmin"]; else print "&nbsp;"; ?></a></td></tr><?php
	print "</table></form>";
	print_footer();
	exit;
}

// -- print out the form to add a new user
// NOTE: WORKING
if ($action == "createform") {
	?>
	<script language="JavaScript" type="text/javascript">
	<!--
		function checkform(frm) {
			if (frm.uusername.value=="") {
				alert("<?php print $gm_lang["enter_username"]; ?>");
				frm.uusername.focus();
				return false;
			}
			if (frm.ufirstname.value=="") {
				alert("<?php print $gm_lang["enter_fullname"]; ?>");
				frm.ufirstname.focus();
				return false;
			}
			if (frm.ulastname.value=="") {
				alert("<?php print $gm_lang["enter_fullname"]; ?>");
				frm.ulastname.focus();
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
		    if (frm.pass1.value.length < 6) {
		      alert("<?php print $gm_lang["passwordlength"]; ?>");
			  frm.pass1.value = "";
		      frm.pass2.value = "";
		      frm.pass1.focus();
			  return false;
		    }
			if ((frm.emailadress.value!="")&&(frm.emailadress.value.indexOf("@")==-1)) {
				alert("<?php print $gm_lang["enter_email"]; ?>");
				frm.emailadress.focus();
				return false;
			}
			return true;
		}
	//-->
	</script>
	
	<form name="newform" method="post" action="<?php print $SCRIPT_NAME;?>" onsubmit="return checkform(this);">
	<input type="hidden" name="action" value="createuser" />
	<!--table-->
	<?php $tab = 0; ?>
	<table class="center list_table width80 <?php print $TEXT_DIRECTION; ?>">
	<tr>
		<td class="admin_topbottombar" colspan="2">
		<h2><?php print $gm_lang["add_user"];?></h2>
		</td>
	</tr>
		<tr><td class="shade2 wrap width20"><?php print_help_link("useradmin_username_help", "qm", "username"); print $gm_lang["username"];?></td><td class="shade1 wrap"><input type="text" name="uusername" tabindex="<?php $tab++; print $tab; ?>" /></td></tr>
		<tr><td class="shade2 wrap"><?php print_help_link("useradmin_firstname_help", "qm","firstname"); print $gm_lang["firstname"];?></td><td class="shade1 wrap"><input type="text" name="ufirstname" tabindex="<?php $tab++; print $tab; ?>" size="50" /></td></tr>
		<tr><td class="shade2 wrap"><?php print_help_link("useradmin_lastname_help", "qm", "lastname"); print $gm_lang["lastname"];?></td><td class="shade1 wrap"><input type="text" name="ulastname" tabindex="<?php $tab++; print $tab; ?>" size="50" /></td></tr>
		<tr><td class="shade2 wrap"><?php print_help_link("useradmin_password_help", "qm", "password"); print $gm_lang["password"];?></td><td class="shade1 wrap"><input type="password" name="pass1" tabindex="<?php $tab++; print $tab; ?>" /></td></tr>
		<tr><td class="shade2 wrap"><?php print_help_link("useradmin_conf_password_help", "qm", "confirm"); print $gm_lang["confirm"];?></td><td class="shade1 wrap"><input type="password" name="pass2" tabindex="<?php $tab++; print $tab; ?>" /></td></tr>
		<tr><td class="shade2 wrap"><?php print_help_link("useradmin_gedcomid_help", "qm","gedcomid"); print $gm_lang["gedcomid"];?></td><td class="shade1 wrap">

		<table class="<?php print $TEXT_DIRECTION; ?>">
		<?php
		foreach($GEDCOMS as $ged=>$gedarray) {
			$file = $ged;
			$ged = preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $ged);
			$tab++;
			print "<tr><td>$file:&nbsp;&nbsp;</td><td><input type=\"text\" name=\"gedcomid_$ged\" id=\"gedcomid_$ged\" tabindex=\"".$tab."\" value=\"";
			print "\" />\n";
			print_findindi_link("gedcomid_$ged","");
			print "</td></tr>\n";
		}
		?>
		</table>
		</td></tr>
		<tr><td class="shade2 wrap"><?php print_help_link("useradmin_rootid_help", "qm","rootid"); print $gm_lang["rootid"];?></td><td class="shade1 wrap">
		<table class="<?php print $TEXT_DIRECTION; ?>">
		<?php
		foreach($GEDCOMS as $ged=>$gedarray) {
			$file = $ged;
			$ged = preg_replace(array("/\./","/-/"), array("_","_"), $ged);
			$tab++;
			print "<tr><td>$file:&nbsp;&nbsp;</td><td><input type=\"text\" name=\"rootid_$ged\" id=\"rootid_$ged\" tabindex=\"".$tab."\" value=\"";
			print "\" />\n";
			print_findindi_link("rootid_$ged","");
			print "</td></tr>\n";
		}
		print "</table>";
		?>
		</td></tr>
		<tr><td class="shade2 wrap"><?php print_help_link("useradmin_sync_gedcom_help", "qm","sync_gedcom"); print $gm_lang["sync_gedcom"];?></td>
      		<td class="shade1 wrap"><input type="checkbox" name="new_sync_gedcom" tabindex="<?php $tab++; print $tab; ?>" value="Y" /></td></tr>
		<tr><td class="shade2 wrap"><?php print_help_link("useradmin_can_admin_help", "qm","can_admin"); print $gm_lang["can_admin"];?></td><td class="shade1 wrap"><input type="checkbox" name="canadmin" tabindex="<?php $tab++; print $tab; ?>" value="yes" /></td></tr>
		<tr><td class="shade2 wrap"><?php print_help_link("useradmin_can_edit_help", "qm","can_edit");print $gm_lang["can_edit"];?></td><td class="shade1 wrap">
		<?php
		foreach($GEDCOMS as $ged=>$gedarray) {
			$file = $ged;
			$ged = preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $ged);
			$tab++;
			print "<select name=\"canedit_$ged\" tabindex=\"".$tab."\">\n";
			print "<option value=\"none\" selected=\"selected\"";
			print ">".$gm_lang["none"]."</option>\n";
			print "<option value=\"access\"";
			print ">".$gm_lang["access"]."</option>\n";
			print "<option value=\"edit\"";
			print ">".$gm_lang["edit"]."</option>\n";
			print "<option value=\"accept\"";
			print ">".$gm_lang["accept"]."</option>\n";
			print "<option value=\"admin\"";
			print ">".$gm_lang["admin_gedcom"]."</option>\n";
			print "</select> $file<br />\n";
		}
		?>
		</td></tr>
		<tr><td class="shade2 wrap"><?php print_help_link("useradmin_auto_accept_help", "qm", "user_auto_accept");print $gm_lang["user_auto_accept"];?></td>
			<td class="shade1 wrap"><input type="checkbox" name="new_auto_accept" tabindex="<?php $tab++; print $tab; ?>" value="Y" /></td></tr>
		<tr><td class="shade2 wrap"><?php print_help_link("useradmin_relation_priv_help", "qm", "user_relationship_priv");print $gm_lang["user_relationship_priv"];?></td>
			<td class="shade1 wrap"><input type="checkbox" name="new_relationship_privacy" tabindex="<?php $tab++; print $tab; ?>" value="Y" /></td></tr>
		<tr><td class="shade2 wrap"><?php print_help_link("useradmin_path_length_help", "qm", "user_path_length"); print $gm_lang["user_path_length"];?></td>
			<td class="shade1 wrap"><input type="text" name="new_max_relation_path" tabindex="<?php $tab++; print $tab; ?>" value="0" size="5" /></td></tr>
		<tr><td class="shade2 wrap"><?php print_help_link("useradmin_email_help", "qm", "emailadress"); print $gm_lang["emailadress"];?></td><td class="shade1 wrap"><input type="text" name="emailadress" tabindex="<?php $tab++; print $tab; ?>" value="" size="50" /></td></tr>
		<tr><td class="shade2 wrap"><?php print_help_link("useradmin_verified_help", "qm", "verified"); print $gm_lang["verified"];?></td><td class="shade1 wrap"><input type="checkbox" name="verified" tabindex="<?php $tab++; print $tab; ?>" value="yes" checked="checked" /></td></tr>
		<tr><td class="shade2 wrap"><?php print_help_link("useradmin_verbyadmin_help", "qm", "verified_by_admin"); print $gm_lang["verified_by_admin"];?></td><td class="shade1 wrap"><input type="checkbox" name="verified_by_admin" tabindex="<?php $tab++; print $tab; ?>" value="yes" checked="checked" /></td></tr>
		<tr><td class="shade2 wrap"><?php print_help_link("useradmin_change_lang_help", "qm", "change_lang");print $gm_lang["change_lang"];?></td><td class="shade1 wrap" valign="top"><?php
		
		$user = GetUser($gm_username);
		if ($ENABLE_MULTI_LANGUAGE) {
			$tab++;
	      	print "<select name=\"user_language\" tabindex=\"".$tab."\" style=\"{ font-size: 9pt; }\">";
		  	foreach ($gm_language as $key => $value) {
			  	if ($language_settings[$key]["gm_lang_use"]) {
		      		print "\n\t\t\t<option value=\"$key\"";
	      			if ($key == $user["language"]) {
			      	    print " selected=\"selected\"";
	      			}
			 		print ">" . $gm_lang[$key] . "</option>";
		 		}
      		}
      		print "</select>\n\t\t";
		}
		else print "&nbsp;";
		?></td></tr>
		<?php if ($ALLOW_USER_THEMES) { ?>
			<tr><td class="shade2 wrap" valign="top" align="left"><?php print_help_link("useradmin_user_theme_help", "qm", "user_theme"); print $gm_lang["user_theme"];?></td><td class="shade1 wrap" valign="top">
	    	<select name="new_user_theme" tabindex="<?php $tab++; print $tab; ?>">
			<option value="" selected="selected"><?php print $gm_lang["site_default"]; ?></option>
			<?php
			$themes = get_theme_names();
			foreach($themes as $indexval => $themedir) {
				print "<option value=\"".$themedir["dir"]."\"";
				print ">".$themedir["name"]."</option>\n";
			}
			?>
			</select>
			</td></tr>
		<?php } ?>
		<tr>
			<td class="shade2 wrap"><?php print_help_link("useradmin_user_contact_help", "qm", "user_contact_method"); print $gm_lang["user_contact_method"];?></td>
			<td class="shade1 wrap"><select name="new_contact_method" tabindex="<?php $tab++; print $tab; ?>">
			<?php if ($GM_STORE_MESSAGES) { ?>
				<option value="messaging"><?php print $gm_lang["messaging"];?></option>
				<option value="messaging2" selected="selected"><?php print $gm_lang["messaging2"];?></option>
			<?php } else { ?>
				<option value="messaging3" selected="selected"><?php print $gm_lang["messaging3"];?></option>
			<?php } ?>
				<option value="mailto"><?php print $gm_lang["mailto"];?></option>
				<option value="none"><?php print $gm_lang["no_messaging"];?></option>
			</select>
			</td>
		</tr>
		<tr>
			<td class="shade2 wrap"><?php print_help_link("useradmin_visibleonline_help", "qm", "visibleonline"); print $gm_lang["visibleonline"];?></td>
			<td class="shade1 wrap"><input type="checkbox" name="visibleonline" tabindex="<?php $tab++; print $tab; ?>" value="yes" <?php print "checked=\"checked\""; ?> /></td>
		</tr>
		<tr>
			<td class="shade2 wrap"><?php print_help_link("useradmin_editaccount_help", "qm", "editaccount"); print $gm_lang["editaccount"];?></td>
			<td class="shade1 wrap"><input type="checkbox" name="editaccount" tabindex="<?php $tab++; print $tab; ?>" value="yes" <?php print "checked=\"checked\""; ?> /></td>
		</tr>
		<tr>
			<td class="shade2 wrap"><?php print_help_link("useradmin_user_default_tab_help", "qm", "user_default_tab"); print $gm_lang["user_default_tab"];?></td>
			<td class="shade1 wrap"><select name="new_default_tab" tabindex="<?php $tab++; print $tab; ?>">
				<option value="0"><?php print $gm_lang["personal_facts"];?></option>
				<option value="1"><?php print $gm_lang["notes"];?></option>
				<option value="2"><?php print $gm_lang["ssourcess"];?></option>
				<option value="3"><?php print $gm_lang["media"];?></option>
				<option value="4"><?php print $gm_lang["relatives"];?></option>
				</select>
			</td>
		</tr>
		<?php if (userIsAdmin($gm_username)) { ?>
		<tr>
			<td class="shade2 wrap"><?php print_help_link("useradmin_comment_help", "qm", "comment"); print $gm_lang["comment"];?></td>
			<td class="shade1 wrap"><textarea cols="50" rows="5" name="new_comment" tabindex="<?php $tab++; print $tab; ?>" ></textarea></td>
		</tr>
		<tr>
			<td class="shade2 wrap"><?php print_help_link("useradmin_comment_exp_help", "qm", "comment_exp"); print $gm_lang["comment_exp"];?></td>
			<td class="shade1 wrap"><input type="text" name="new_comment_exp" tabindex="<?php $tab++; print $tab; ?>" id="new_comment_exp" />&nbsp;&nbsp;<?php print_calendar_popup("new_comment_exp"); ?></td>
		</tr>
		<?php } ?>
	<tr><td class="admin_topbottombar" colspan="2">
	<input type="hidden" name="pwrequested" value="" />
	<input type="hidden" name="reg_timestamp" value="<?php print date("U");?>" />
	<input type="hidden" name="reg_hashcode" value="" />
	<input type="submit" tabindex="<?php $tab++; print $tab; ?>" value="<?php print $gm_lang["create_user"]; ?>" />
	<input type="button" tabindex="<?php $tab++; print $tab; ?>" value="<?php print $gm_lang["back"];?>" onclick="window.location='useradmin.php';"/>
	</td></tr></table>
	</form>
	<?php
	print_footer();
	exit;
}

// Cleanup users and user rights
//NOTE: WORKING
if ($action == "cleanup") {
	?>
	<form name="cleanupform" method="post" action="">
	<input type="hidden" name="action" value="cleanup2" />
	<table class="center list_table width80 <?php print $TEXT_DIRECTION; ?>">
	<tr>
		<td class="facts_label" colspan="2">
		<h2><?php print $gm_lang["cleanup_users"];?></h2>
		</td>
	</tr>
	<?php
	// Check for idle users
	if (!isset($month)) $month = 1;
	print "<tr><td class=\"shade2\">".$gm_lang["usr_idle"]."</td>";
	print "<td class=\"shade1\"><select onchange=\"document.location=options[selectedIndex].value;\">";
	for($i=1; $i<=12; $i++) { 
		print "<option value=\"useradmin.php?action=cleanup&amp;month=$i\"";
		if ($i == $month) print " selected=\"selected\"";
		print " >".$i."</option>";
	}
	print "</select></td></tr>";
	?>
	<tr><td class="admin_topbottombar" colspan="2"><?php print $gm_lang["options"]; ?></td></tr>
	<?php
	// Check users not logged in too long
	$users = GetUsers();
	$ucnt = 0;
	foreach($users as $key=>$user) {
		if ($user["sessiontime"] == "0") $datelogin = $user["reg_timestamp"];
		else $datelogin = $user["sessiontime"];
		if ((mktime(0, 0, 0, date("m")-$month, date("d"), date("Y")) > $datelogin) && ($user["verified"] == "yes") && ($user["verified_by_admin"] == "yes")) {
			?><tr><td class="shade2"><?php print $user["username"]." - ".$user["firstname"]." ".$user["lastname"].":&nbsp;&nbsp;".$gm_lang["usr_idle_toolong"];
			print get_changed_date(date("d", $datelogin)." ".date("M", $datelogin)." ".date("Y", $datelogin));
			$ucnt++;
			?></td><td class="shade1"><input type="checkbox" name="<?php print "del_".preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $user["username"]); ?>" value="yes" /></td></tr><?php
		}
	}
		
	// Check unverified users
	foreach($users as $key=>$user) {
		if (((date("U") - $user["reg_timestamp"]) > 604800) && ($user["verified"]!="yes")) {
			?><tr><td class="shade2"><?php print $user["username"]." - ".$user["firstname"]." ".$user["lastname"].":&nbsp;&nbsp;".$gm_lang["del_unveru"]; 
			$ucnt++;
			?></td><td class="shade1"><input type="checkbox" checked="checked" name="<?php print "del_".preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $user["username"]); ?>" value="yes" /></td></tr><?php
		}
	}

	// Check users not verified by admin
	foreach($users as $key=>$user) {
		if (($user["verified_by_admin"]!="yes") && ($user["verified"] == "yes")) {
			?><tr><td  class="shade2"><?php print $user["username"]." - ".$user["firstname"]." ".$user["lastname"].":&nbsp;&nbsp;".$gm_lang["del_unvera"]; 
			?></td><td class="shade1"><input type="checkbox" name="<?php print "del_".preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $user["username"]); ?>" value="yes" /></td></tr><?php
			$ucnt++;
		}
	}
	
	// Then check obsolete gedcom rights
	$gedrights = array();
	foreach($users as $key=>$user) {
		foreach($user["canedit"] as $gedid=>$data) {
			if ((!isset($GEDCOMS[$gedid])) && (!in_array($gedid, $gedrights))) $gedrights[] = $gedid;
		}
		foreach($user["gedcomid"] as $gedid=>$data) {
			if ((!isset($GEDCOMS[$gedid])) && (!in_array($gedid, $gedrights))) $gedrights[] = $gedid;
		}
		foreach($user["rootid"] as $gedid=>$data) {
			if ((!isset($GEDCOMS[$gedid])) && (!in_array($gedid, $gedrights))) $gedrights[] = $gedid;
		}
	}
	ksort($gedrights);
	foreach($gedrights as $key=>$ged) {
		?><tr><td class="shade2"><?php print $ged.":&nbsp;&nbsp;".$gm_lang["del_gedrights"]; 
		?></td><td class="shade1"><input type="checkbox" checked="checked" name="<?php print "delg_".preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $ged); ?>" value="yes" /></td></tr><?php
		$ucnt++;
	}
	if ($ucnt == 0) {
		print "<tr><td class=\"warning\">";
		print $gm_lang["usr_no_cleanup"]."</td></tr>";
	}?>
	<tr><td class="admin_topbottombar" colspan="2">
	<?php
	if ($ucnt >0) {
		?><input type="submit" value="<?php print $gm_lang["del_proceed"]; ?>" />&nbsp;<?php
	}?>
	<input type="button" value="<?php print $gm_lang["back"];?>" onclick="window.location='useradmin.php';"/>
	</td></tr></table>
	</form><?php
	print_footer();
	exit;
}
// NOTE: No table parts
if ($action == "cleanup2") {
	$users = getUsers();
	foreach($users as $key=>$user) {
		$var = "del_".preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $user["username"]);
		if (isset($$var)) {
			deleteUser($key);
			print $gm_lang["usr_deleted"]; print $user["username"]."<br />";
		}
		else {
			foreach($user["canedit"] as $gedid=>$data) {
				$var = "delg_".preg_replace(array("/\./","/-/","/ /"), array("_","_","_"), $gedid);
				if (isset($$var)) {
					unset($user["canedit"][$gedid]);
					print $gedid.":&nbsp;&nbsp;".$gm_lang["usr_unset_rights"].$user["username"]."<br />";
					if (isset($user["rootid"][$gedid])) {
						unset($user["rootid"][$gedid]);
						print $gedid.":&nbsp;&nbsp;".$gm_lang["usr_unset_rootid"].$user["username"]."<br />";
					}
					if (isset($user["gedcomid"][$gedid])) {
						unset($user["gedcomid"][$gedid]);
						print $gedid.":&nbsp;&nbsp;".$gm_lang["usr_unset_gedcomid"].$user["username"]."<br />";
					}
					deleteUser($key, "changed");
					Adduser($user, "changed");
				}
			}
		}
	}
	print "<br />";
}

// Print main menu
// NOTE: WORKING
?>
<table class="center list_table width40 <?php print $TEXT_DIRECTION; ?>">
	<tr>
		<td class="admin_topbottombar" colspan="3">
		<h2><?php print $gm_lang["user_admin"];?></h2>
		</td>
	</tr>
	<tr>
		<td colspan="3" class="admin_topbottombar"><?php print $gm_lang["select_an_option"]; ?></td>
	</tr>
	<tr>
		<td class="shade1"><a href="useradmin.php?action=listusers"><?php print $gm_lang["current_users"];?></a></td>
		<td class="shade1"><a href="useradmin.php?action=createform"><?php print $gm_lang["add_user"];?></a></td>
	</tr>
	<tr>
		<td class="shade1"><a href="useradmin.php?action=cleanup"><?php print $gm_lang["cleanup_users"];?></a></td>
		<td class="shade1">
			<a href="javascript: <?php print $gm_lang["message_to_all"]; ?>" onclick="message('all', 'messaging2', '', ''); return false;"><?php print $gm_lang["message_to_all"]; ?></a><br />
			<a href="javascript: <?php print $gm_lang["broadcast_never_logged_in"]; ?>" onclick="message('never_logged', 'messaging2', '', ''); return false;"><?php print $gm_lang["broadcast_never_logged_in"]; ?></a><br />
			<a href="javascript: <?php print $gm_lang["broadcast_not_logged_6mo"]; ?>" onclick="message('last_6mo', 'messaging2', '', ''); return false;"><?php print $gm_lang["broadcast_not_logged_6mo"]; ?></a><br />
		</td>
	</tr>
	<tr>
		<td class="admin_topbottombar" colspan="2" align="center" ><a href="admin.php"><?php print $gm_lang["lang_back_admin"]; ?></a></td>
	</tr>
	<tr><td></td></tr>
	<tr>
		<td colspan="3" class="admin_topbottombar"><?php print $gm_lang["admin_info"]; ?></td>
	</tr>
	<tr>
      	<td class="shade1" colspan="3">
	<?php
	$users = getUsers();
	$totusers = 0;			// Total number of users
	$warnusers = 0;			// Users with warning
	$applusers = 0;			// Users who have not verified themselves
	$nverusers = 0;			// Users not verified by admin but verified themselves
	$adminusers = 0;		// Administrators
	$userlang = array();	// Array for user languages
	$gedadmin = array();	// Array for gedcom admins
	foreach($users as $username=>$user) {
		if (empty($user["language"])) $user["language"]=$LANGUAGE;
		$totusers = $totusers + 1;
		if (((date("U") - $user["reg_timestamp"]) > 604800) && ($user["verified"]!="yes")) $warnusers++;
		else {
			if (!empty($user["comment_exp"])) {
				if ((strtotime($user["comment_exp"]) != "-1") && (strtotime($user["comment_exp"]) < time("U"))) $warnusers++;
			}
		}
		if (($user["verified_by_admin"] != "yes") && ($user["verified"] == "yes")) $nverusers++;
		if ($user["verified"] != "yes") $applusers++;
		if ($user["canadmin"]) $adminusers++;
		foreach($user["canedit"] as $gedid=>$rights) {
			if ($rights == "admin") {
				if (isset($GEDCOMS[$gedid])) {
					if (isset($gedadmin[$GEDCOMS[$gedid]["title"]])) $gedadmin[$GEDCOMS[$gedid]["title"]]["number"]++;
					else {
						$gedadmin[$GEDCOMS[$gedid]["title"]]["name"] = $GEDCOMS[$gedid]["title"];
						$gedadmin[$GEDCOMS[$gedid]["title"]]["number"] = 1;
						$gedadmin[$GEDCOMS[$gedid]["title"]]["ged"] = $gedid;
					}
				}
			}
		}
		if (isset($userlang[$gm_lang["lang_name_".$user["language"]]])) $userlang[$gm_lang["lang_name_".$user["language"]]]["number"]++;
		else {
			$userlang[$gm_lang["lang_name_".$user["language"]]]["langname"] = $user["language"];
			$userlang[$gm_lang["lang_name_".$user["language"]]]["number"] = 1;
		}
	}
	print "<table class=\"width100 $TEXT_DIRECTION\">";
	print "<tr><td class=\"font11\">".$gm_lang["users_total"]."</td><td class=\"font11\">".$totusers."</td></tr>";

	print "<tr><td class=\"font11\">";
	if ($adminusers == 0) print $gm_lang["users_admin"];
	else print "<a href=\"useradmin.php?action=listusers&amp;filter=adminusers\">".$gm_lang["users_admin"]."</a></td>";
	print "<td class=\"font11\">".$adminusers."</td></tr>";

	print "<tr><td class=\"font11\">".$gm_lang["users_gedadmin"]."</td>";
	asort($gedadmin);
	$ind = 0;
	foreach ($gedadmin as $key=>$geds) {
		if ($ind !=0) print "<tr><td class=\"font11\"></td>";
		$ind = 1;
		print "<td class=\"font11\">";
		if ($geds["number"] == 0) print $geds["name"];
		else print "<a href=\"useradmin.php?action=listusers&amp;filter=gedadmin&amp;ged=".$geds["ged"]."\">".$geds["name"]."</a>";
		print "</td><td class=\"font11\">".$geds["number"]."</td></tr>";
	}
	print "<tr><td class=\"font11\"></td></tr><tr><td class=\"font11\">";
	if ($warnusers == 0) print $gm_lang["warn_users"];
	else print "<a href=\"useradmin.php?action=listusers&amp;filter=warnings\">".$gm_lang["warn_users"]."</a>";
	print "</td><td class=\"font11\">".$warnusers."</td></tr>";

	print "<tr><td class=\"font11\">";
	if ($applusers == 0) print $gm_lang["users_unver"];
	else print "<a href=\"useradmin.php?action=listusers&amp;filter=usunver\">".$gm_lang["users_unver"]."</a>";
	print "</td><td class=\"font11\">".$applusers."</td></tr>";
	
	print "<tr><td class=\"font11\">";
	if ($nverusers == 0) print $gm_lang["users_unver_admin"];
	else print "<a href=\"useradmin.php?action=listusers&amp;filter=admunver\">".$gm_lang["users_unver_admin"]."</a>";
	print "</td><td class=\"font11\">".$nverusers."</td></tr>";

	asort($userlang);
	print "<tr valign=\"middle\"><td class=\"font11\">".$gm_lang["users_langs"]."</td>";
	foreach ($userlang as $key=>$ulang) {
		print "\t<td class=\"font11\"><img src=\"".$language_settings[$ulang["langname"]]["flagsfile"]."\" class=\"brightflag\" alt=\"".$key."\" title=\"".$key."\" /></td><td>&nbsp;<a href=\"useradmin.php?action=listusers&amp;filter=language&amp;usrlang=".$ulang["langname"]."\">".$key."</a></td><td>".$ulang["number"]."</td></tr><tr class=\"vmiddle\"><td></td>\n";
	}
	print "</tr></table>";
	print "</td></tr></table>";
	 ?>
<?php
print_footer();
?>
