<?php
/**
 * Review Changes Block
 *
 * This block prints the changes that still need to be reviewed and accepted by an administrator
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
 * @subpackage Blocks
 * @version $Id: review_changes.php 29 2022-07-17 13:18:20Z Boudewijn $
 * @todo add a time configuration option
 */

$GM_BLOCKS["review_changes_block"]["name"]      = GM_LANG_review_changes_block;
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
	global $command, $QUERY_STRING, $GM_IMAGES;
	global $gm_changes, $TEXT_DIRECTION, $TIME_FORMAT, $GM_BLOCKS, $gm_user;

	print "<!-- Start Review Changes Block //-->";
	if (!GedcomConfig::$ALLOW_EDIT_GEDCOM) return;

	if (empty($config)) $config = $GM_BLOCKS["review_changes_block"]["config"];

	$lastmail = GedcomConfig::GetLastNotifMail();
	$display_block = false;
	$geds = ChangeFunctions::GetChangeData(false, "", false, "gedcoms");
	$sent = array();
	$users = array();
	foreach ($geds as $gedkey=>$gedvalue) {
		if ($gedvalue == GedcomConfig::$GEDCOMID) $display_block = true;
		if (isset($lastmail[$gedvalue])) {
			//-- if the time difference from the last email is greater than 24 hours then send out another email
			if (time()-$lastmail[$gedvalue] > (60*60*24*$config["days"])) {
				GedcomConfig::SetLastNotifMail($gedvalue);
				if ($config["sendmail"]=="yes") {
					if (count($users) == 0) $users = UserController::GetUsers();
					foreach($users as $username=>$user) {
						if ($user->userCanAccept()) {
							if (!in_array($username, $sent)) {
								$sent[] = $username;
								//-- send message
								$message = new Message();
								$message->to = $username;
								$host = preg_replace("/^www\./i", "", $_SERVER["SERVER_NAME"]);
								$message->from = "Genmod-noreply@".$host;
								$message->subject = GM_LANG_review_changes_subject;
								$message->body = GM_LANG_review_changes_body;
								$message->method = $user->contactmethod;
								$message->url = basename(SCRIPT_NAME)."?".$QUERY_STRING;
								$message->no_from = true;
								$message->AddMessage();
							}
						}
					}
				}
			}
		}
	}
	if ($display_block && $gm_user->userCanEdit()) {
		print "<div id=\"review_changes_block\" class=\"BlockContainer\">\n";
			print "<div class=\"BlockHeader\">";
				PrintHelpLink("review_changes_help", "qm", "review_changes");
				if ($GM_BLOCKS["review_changes_block"]["canconfig"]) {
					if ((($command=="gedcom")&&($gm_user->userGedcomAdmin())) || (($command=="user")&&($gm_user->username != ""))) {
						if ($command=="gedcom") $name = preg_replace("/'/", "\'", get_gedcom_from_id(GedcomConfig::$GEDCOMID));
						else $name = $gm_user->username;
						print "<a href=\"javascript: ".GM_LANG_config_block."\" onclick=\"window.open('index_edit.php?name=$name&amp;command=$command&amp;action=configure&amp;side=$side&amp;index=$index', '', 'top=50,left=50,width=500,height=250,scrollbars=1,resizable=1'); return false;\">";
						BlockFunctions::PrintAdminIcon();
						print "</a>";
					}
				}
				print "<div class=\"BlockHeaderText\">".GM_LANG_review_changes."</div>";
			print "</div>";
			print "<div class=\"BlockContent\">";
				if ($gm_user->userCanAccept()) print "<div class=\"ReviewChangesLink\"><a href=\"#\" onclick=\"window.open('edit_changes.php','','width=600,height=600,resizable=1,scrollbars=1'); return false;\">".GM_LANG_accept_changes."</a></div>\n";
				if ($block) print "<div class=\"RestrictedBlockHeightRight $TEXT_DIRECTION\">\n";
				else print "<div class=\"RestrictedBlockHeightMain\">\n";
					if ($config["sendmail"]=="yes" && GedcomConfig::$LAST_CHANGE_EMAIL != 0) {
						print "<div class=\"ReviewChangesMessage\">";
							$day = date("j", GedcomConfig::$LAST_CHANGE_EMAIL);
							$mon = date("M", GedcomConfig::$LAST_CHANGE_EMAIL);
							$year = date("Y", GedcomConfig::$LAST_CHANGE_EMAIL);
							print GM_LANG_last_email_sent.GetChangedDate("$day $mon $year")." - ".date($TIME_FORMAT, GedcomConfig::$LAST_CHANGE_EMAIL)."<br />\n";
							$day = date("j", GedcomConfig::$LAST_CHANGE_EMAIL+(60*60*24*$config["days"]));
							$mon = date("M", GedcomConfig::$LAST_CHANGE_EMAIL+(60*60*24*$config["days"]));
							$year = date("Y", GedcomConfig::$LAST_CHANGE_EMAIL+(60*60*24*$config["days"]));
							print GM_LANG_next_email_sent.GetChangedDate("$day $mon $year")." - ".date($TIME_FORMAT, GedcomConfig::$LAST_CHANGE_EMAIL+(60*60*24*$config["days"]))."\n";
						print "</div>";
					}
					$gm_changes = ChangeFunctions::GetChangeData(false, "", true, "gedlines");
					foreach($gm_changes as $gedcomid=>$changes) {
						if ($gedcomid == GedcomConfig::$GEDCOMID) {
							foreach($changes as $gid=>$change) {
								$object = ConstructObject($gid, "", $gedcomid);
								if (is_object($object)) {
									$type = $object->type;
									print "<div class=\"ReviewChangesShowLink\">";
									if ($type=="INDI") print " <a href=\"individual.php?pid=".$object->xref."&amp;gedid=".$object->gedcomid."\">".GM_LANG_view_change_diff."</a>\n<br />";
									if ($type=="FAM") print " <a href=\"family.php?famid=".$object->xref."&amp;gedid=".$object->gedcomid."\">".GM_LANG_view_change_diff."</a>\n<br />";
									if ($type=="OBJE") print " <a href=\"mediadetail.php?mid=".$object->xref."&amp;gedid=".$object->gedcomid."\">".GM_LANG_view_change_diff."</a>\n<br />";
									if ($type=="SOUR") print " <a href=\"source.php?sid=".$object->xref."&amp;gedid=".$object->gedcomid."\">".GM_LANG_view_change_diff."</a>\n<br />";
									if ($type=="REPO") print " <a href=\"repo.php?rid=".$object->xref."&amp;gedid=".$object->gedcomid."\">".GM_LANG_view_change_diff."</a>\n<br />";
									if ($type=="NOTE") print " <a href=\"note.php?oid=".$object->xref."&amp;gedid=".$object->gedcomid."\">".GM_LANG_view_change_diff."</a>\n<br />";
									if ($block) print "<br />";
									print "</div><div class=\"ReviewChangesObject\">";
									print "<span class=\"ReviewChangesObjectName\">".$object->name."</span>".$object->addxref;
									print "</div>";
								}
							}
						}
					}
				print "</div>\n";
			print "</div>";
		print "</div>";
	}
	print "<!-- End Review Changes Block //-->";
}

function review_changes_block_config($config) {
	global $GM_BLOCKS, $TEXT_DIRECTION;
	if (empty($config)) $config = $GM_BLOCKS["review_changes_block"]["config"];
	print "<tr><td class=\"NavBlockLabel\">".GM_LANG_review_changes_email."</td><td class=\"NavBlockField\">";
	print "&nbsp;<select name='sendmail'>";
		print "<option value='yes'";
		if ($config["sendmail"]=="yes") print " selected='selected'";
		print ">".GM_LANG_yes."</option>";
		print "<option value='no'";
		if ($config["sendmail"]=="no") print " selected='selected'";
		print ">".GM_LANG_no."</option>";
	print "</select></td></tr>";
	print "<tr><td class=\"NavBlockLabel\">".GM_LANG_review_changes_email_freq."</td><td class=\"NavBlockField\"><input type='text' name='days' value='".$config["days"]."' size='2' /></td></tr>";
}

?>
