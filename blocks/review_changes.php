<?php
/**
 * Review Changes Block
 *
 * This block prints the changes that still need to be reviewed and accepted by an administrator
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
 * @subpackage Blocks
 * @version $Id: review_changes.php,v 1.24 2009/03/16 19:51:12 sjouke Exp $
 * @todo add a time configuration option
 */

$GM_BLOCKS["review_changes_block"]["name"]      = $gm_lang["review_changes_block"];
$GM_BLOCKS["review_changes_block"]["descr"]     = "review_changes_descr";
$GM_BLOCKS["review_changes_block"]["canconfig"]	= true;
$GM_BLOCKS["review_changes_block"]["config"] 	= array("days"=>1, "sendmail"=>"yes");
$GM_BLOCKS["review_changes_block"]["rss"]       = false;
/**
 * Print Review Changes Block
 *
 * Prints a block allowing the user review all changes pending approval
 */
function review_changes_block($block = true, $config="", $side, $index) {
	global $gm_lang, $GEDCOM, $GEDCOMS, $command, $SCRIPT_NAME, $QUERY_STRING, $factarray, $GM_IMAGE_DIR, $GM_IMAGES;
	global $gm_changes, $LAST_CHANGE_EMAIL, $ALLOW_EDIT_GEDCOM, $SERVER_URL, $TEXT_DIRECTION, $SHOW_SOURCES, $TIME_FORMAT, $GM_BLOCKS, $TBLPREFIX, $gm_username, $Users;

	if (!$ALLOW_EDIT_GEDCOM) return;

	if (empty($config)) $config = $GM_BLOCKS["review_changes_block"]["config"];

	$lastmail = array();
	$sql = "SELECT gc_gedcom, gc_last_change_email from ".$TBLPREFIX."gedconf";
	$res = NewQuery($sql);
	if ($res) {
		while ($row = $res->FetchAssoc()) {
			$lastmail[$row["gc_gedcom"]] = $row["gc_last_change_email"];
		}
	}
	$display_block = false;
	$geds = GetChangeData(false, "", false, "gedcoms");
	$sent = array();
	foreach ($geds as $gedkey=>$gedvalue) {
		if ($gedvalue == $GEDCOM) $display_block = true;
		if (isset($lastmail[$gedvalue])) {
			//-- if the time difference from the last email is greater than 24 hours then send out another email
			if (time()-$lastmail[$gedvalue] > (60*60*24*$config["days"])) {
				$sql = "UPDATE ".$TBLPREFIX."gedconf SET gc_last_change_email='".time()."' WHERE gc_gedcom='".$gedvalue."'";
				$res = NewQuery($sql);
				if ($config["sendmail"]=="yes") {
					$users = $Users->GetUsers();
					foreach($users as $username=>$user) {
						if ($Users->userCanAccept($username)) {
							if (!in_array($username, $sent)) {
								$sent[] = $username;
								//-- send message
								$message = array();
								$message["to"]=$username;
								$host = preg_replace("/^www\./i", "", $_SERVER["SERVER_NAME"]);
								$message["from"] = "Genmod-noreply@".$host;
								$message["subject"] = $gm_lang["review_changes_subject"];
								$message["body"] = $gm_lang["review_changes_body"];
								$message["method"] = $user->contactmethod;
								$message["url"] = basename($SCRIPT_NAME)."?".$QUERY_STRING;
								$message["no_from"] = true;
								AddMessage($message);
							}
						}
					}
				}
			}
		}
	}
	if ($display_block && $Users->userCanEdit($gm_username)) {
		print "<div id=\"review_changes_block\" class=\"block\">\n";
		print "<div class=\"blockhc\">";
		print_help_link("review_changes_help", "qm", "review_changes");
		if ($GM_BLOCKS["review_changes_block"]["canconfig"]) {
			$username = $gm_username;
			if ((($command=="gedcom")&&($Users->userGedcomAdmin($username))) || (($command=="user")&&(!empty($username)))) {
				if ($command=="gedcom") $name = preg_replace("/'/", "\'", $GEDCOM);
				else $name = $username;
				print "<a href=\"javascript: ".$gm_lang["config_block"]."\" onclick=\"window.open('index_edit.php?name=$name&amp;command=$command&amp;action=configure&amp;side=$side&amp;index=$index', '', 'top=50,left=50,width=500,height=250,scrollbars=1,resizable=1'); return false;\">";
				print "<img class=\"adminicon\" src=\"$GM_IMAGE_DIR/".$GM_IMAGES["admin"]["small"]."\" width=\"15\" height=\"15\" border=\"0\" alt=\"".$gm_lang["config_block"]."\" /></a>\n";
			}
		}
		print $gm_lang["review_changes"];
		print "</div>";
		print "<div class=\"blockcontent\">";
		if ($Users->userCanAccept($gm_username)) print "<a href=\"#\" onclick=\"window.open('edit_changes.php','','width=600,height=600,resizable=1,scrollbars=1'); return false;\">".$gm_lang["accept_changes"]."</a><br />\n";
		if ($block) print "<div class=\"small_inner_block, $TEXT_DIRECTION\">\n";
		if ($config["sendmail"]=="yes" && $LAST_CHANGE_EMAIL != 0) {
			$day = date("j", $LAST_CHANGE_EMAIL);
			$mon = date("M", $LAST_CHANGE_EMAIL);
			$year = date("Y", $LAST_CHANGE_EMAIL);
			print $gm_lang["last_email_sent"].GetChangedDate("$day $mon $year")." - ".date($TIME_FORMAT, $LAST_CHANGE_EMAIL)."<br />\n";
			$day = date("j", $LAST_CHANGE_EMAIL+(60*60*24*$config["days"]));
			$mon = date("M", $LAST_CHANGE_EMAIL+(60*60*24*$config["days"]));
			$year = date("Y", $LAST_CHANGE_EMAIL+(60*60*24*$config["days"]));
			print $gm_lang["next_email_sent"].GetChangedDate("$day $mon $year")." - ".date($TIME_FORMAT, $LAST_CHANGE_EMAIL+(60*60*24*$config["days"]))."<br /><br />\n";
		}
		$gm_changes = GetChangeData(false, "", true, "gedlines");
		foreach($gm_changes as $gedcom=>$changes) {
			if ($gedcom == $GEDCOM) {
				foreach($changes as $gid=>$change) {
					// if an INDI or FAM is deleted, get the data from the old record
					if (empty($change)) $change = FindGedcomRecord($gid);
					$ct = preg_match("/0 @(.*)@\s(\w+)/", $change, $match);
					// If ct=0, the ID is added then deleted, both unapproved. We cannot display anythong old/new.
					if ($ct>0) {
						$type = trim($match[2]);
						if ($type=="INDI") {
							$name = GetPersonName($gid);
							if (empty($name)) {
								$name = GetPersonName($gid, $change);
							}
							print "<b>".PrintReady($name)."</b> &lrm;(".$gid.")&lrm;\n";
						}
						else if ($type=="FAM") print "<b>".PrintReady(GetFamilyDescriptor($gid))."</b> &lrm;(".$gid.")&lrm;\n";
						else if ($type=="SOUR") {
							if ($SHOW_SOURCES>=$Users->getUserAccessLevel($gm_username)) print "<b>".PrintReady(GetSourceDescriptor($gid))."</b> &lrm;(".$gid.")&lrm;\n";
						}
						else if ($type=="REPO") {
							if ($SHOW_SOURCES>=$Users->getUserAccessLevel($gm_username)) print "<b>".PrintReady(GetRepoDescriptor($gid))."</b> &lrm;(".$gid.")&lrm;\n";
						}
						else if ($type == "OBJE") {
							print "<b>".PrintReady(GetMediaDescriptor($gid))."</b> &lrm;(".$gid.")&lrm;\n";
						}
						else print "<b>".$factarray[$type]."</b> &lrm;(".$gid.")&lrm;\n";
						if ($block) print "<br />";
						if ($type=="INDI") print " <a href=\"individual.php?pid=".$gid."&amp;ged=".$gedcom."\">".$gm_lang["view_change_diff"]."</a>\n<br />";
						if ($type=="FAM") print " <a href=\"family.php?famid=".$gid."&amp;ged=".$gedcom."\">".$gm_lang["view_change_diff"]."</a>\n<br />";
						if ($type=="OBJE") print " <a href=\"mediadetail.php?mid=".$gid."&amp;ged=".$gedcom."\">".$gm_lang["view_change_diff"]."</a>\n<br />";
						if (($type=="SOUR") && ($SHOW_SOURCES>=$Users->getUserAccessLevel($gm_username))) print " <a href=\"source.php?sid=".$gid."&amp;ged=".$gedcom."\">".$gm_lang["view_change_diff"]."</a>\n<br />";
						if (($type=="REPO") && ($SHOW_SOURCES>=$Users->getUserAccessLevel($gm_username))) print " <a href=\"repo.php?rid=".$gid."&amp;ged=".$gedcom."\">".$gm_lang["view_change_diff"]."</a>\n<br />";
						if ($type=="NOTE") print " <a href=\"note.php?oid=".$gid."&amp;ged=".$gedcom."\">".$gm_lang["view_change_diff"]."</a>\n<br />";
					}

				}
			}
		}
		if ($block) print "</div>\n";
		print "</div>";
		print "</div>";
	}
}

function review_changes_block_config($config) {
	global $gm_lang, $GM_BLOCKS, $TEXT_DIRECTION;
	if (empty($config)) $config = $GM_BLOCKS["review_changes_block"]["config"];
	print "<table class=\"facts_table ".$TEXT_DIRECTION."\">";
	print "<tr><td class=\"shade2\">".$gm_lang["review_changes_email"]."</td><td class=\"shade1\">";
	print "&nbsp;<select name='sendmail'>";
		print "<option value='yes'";
		if ($config["sendmail"]=="yes") print " selected='selected'";
		print ">".$gm_lang["yes"]."</option>";
		print "<option value='no'";
		if ($config["sendmail"]=="no") print " selected='selected'";
		print ">".$gm_lang["no"]."</option>";
	print "</select></td></tr>";
	print "<tr><td class=\"shade2\">".$gm_lang["review_changes_email_freq"]."</td><td class=\"shade1\"><input type='text' name='days' value='".$config["days"]."' size='2' /></td></tr>";
	print "</table>";
}

?>
