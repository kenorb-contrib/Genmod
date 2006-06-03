<?php
/**
 * User Messages Block
 *
 * This block will print a users messages
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
 * @version $Id: user_messages.php,v 1.6 2006/02/19 11:32:04 roland-d Exp $
 * @package Genmod
 * @subpackage Blocks
 */

$GM_BLOCKS["print_user_messages"]["name"]        = $gm_lang["user_messages_block"];
$GM_BLOCKS["print_user_messages"]["descr"]        = "user_messages_descr";
$GM_BLOCKS["print_user_messages"]["type"]        = "user";
$GM_BLOCKS["print_user_messages"]["canconfig"]        = false;

//-- print user messages
function print_user_messages($block=true, $config="", $side, $index) {
		global $gm_lang, $GM_IMAGE_DIR, $TEXT_DIRECTION, $TIME_FORMAT, $GM_STORE_MESSAGES, $GM_IMAGES, $usersortfields, $gm_username;

		$usermessages = getUserMessages($gm_username);

		print "<div id=\"user_messages\" class=\"block\">\n";
		print "<div class=\"blockhc\">";
		print_help_link("mygedview_message_help", "qm");
		print $gm_lang["my_messages"]." &lrm;(".count($usermessages).")&lrm;";
		print "</div>";
		print "<div class=\"blockcontent\">";
		if ($block) print "<div class=\"small_inner_block\">\n";
		print "<form name=\"messageform\" action=\"\" onsubmit=\"return confirm('".$gm_lang["confirm_message_delete"]."');\">\n";
		if (count($usermessages)==0) {
			print $gm_lang["no_messages"]."<br />";
		}
		else {
			print "<input type=\"hidden\" name=\"action\" value=\"deletemessage\" />\n";
			print "<table class=\"list_table\"><tr>\n";
			print "<td class=\"list_label shade3\">".$gm_lang["delete"]."</td>\n";
			print "<td class=\"list_label shade3\">".$gm_lang["message_subject"]."</td>\n";
			print "<td class=\"list_label shade3\">".$gm_lang["date_created"]."</td>\n";
			print "<td class=\"list_label shade3\">".$gm_lang["message_from"]."</td>\n";
			print "</tr>\n";
			foreach($usermessages as $key=>$message) {
				if (isset($message["id"])) $key = $message["id"];
				print "<tr>";
				print "<td class=\"list_value_wrap shade1\"><input type=\"checkbox\" name=\"message_id[]\" value=\"$key\" /></td>\n";
				$showmsg=preg_replace("/(\w)\/(\w)/","\$1/<span style=\"font-size:1px;\"> </span>\$2",PrintReady($message["subject"]));
				$showmsg=preg_replace("/@/","@<span style=\"font-size:1px;\"> </span>",$showmsg);
				print "<td class=\"list_value_wrap\"><a href=\"#\" onclick=\"expand_layer('message$key'); return false;\"><b>".$showmsg."</b> <img id=\"message${key}_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" alt=\"\" title=\"\" /></a></td>\n";
				if (!empty($message["created"])) $time = strtotime($message["created"]);
				else $time = time();
				$day = date("j", $time);
				$mon = date("M", $time);
				$year = date("Y", $time);
				$tempuser = getUser($message["from"]);
				print "<td class=\"list_value_wrap\">".get_changed_date("$day $mon $year")." - ".date($TIME_FORMAT, $time)."</td>\n";
				print "<td class=\"list_value_wrap\">";
				if ($tempuser) {
					print PrintReady($tempuser["firstname"]." ".$tempuser["lastname"]);
					if ($TEXT_DIRECTION=="ltr") print " &lrm; - ".htmlspecialchars($message["from"])."&lrm;";
					else print " &rlm; - ".htmlspecialchars($message["from"])."&rlm;";
				}
				else print "<a href=\"mailto:".$message["from"]."\">".preg_replace("/@/","@<span style=\"font-size:1px;\"> </span>",$message["from"])."</a>";
				print "</td>\n";
				print "</tr>\n";
				print "<tr><td class=\"list_value_wrap\" colspan=\"4\"><div id=\"message$key\" style=\"display: none;\" class=\"wrap\">\n";
				$message["body"] = preg_replace("/(http:[^<]+)/", "<a href=\"$1\" dir =\"ltr\">$1</a>", $message["body"]);

				print PrintReady($message["body"])."<br /><br />\n";
				if (preg_match("/RE:/", $message["subject"])==0) $message["subject"]="RE:".$message["subject"];
				if ($tempuser) print "<a href=\"#\" onclick=\"reply('".$message["from"]."', '".$message["subject"]."'); return false;\">".$gm_lang["reply"]."</a> | ";
				print "<a href=\"index.php?action=deletemessage&amp;message_id=$key\" onclick=\"return confirm('".$gm_lang["confirm_message_delete"]."');\">".$gm_lang["delete"]."</a></div></td></tr>\n";
			}
			print "</table>\n";
			print "<input type=\"submit\" value=\"".$gm_lang["delete_selected_messages"]."\" /><br /><br />\n";
		}
		$users = getUsers();
		if (count($users)>1) {
			print $gm_lang["message"]." <select name=\"touser\">\n";
			$username = $gm_username;
			$usersortfields = array("lastname","firstname");
			uasort($users, "usersort");
			if (userIsAdmin($username)) {
				print "<option value=\"all\">".$gm_lang["broadcast_all"]."</option>\n";
				print "<option value=\"never_logged\">".$gm_lang["broadcast_never_logged_in"]."</option>\n";
				print "<option value=\"last_6mo\">".$gm_lang["broadcast_not_logged_6mo"]."</option>\n";
			}
			foreach($users as $indexval => $user) {
				if ($username!=$user["username"] && $user["verified_by_admin"])  {
					print "<option value=\"".$user["username"]."\"";
					print ">".PrintReady($user["firstname"]." ".$user["lastname"]);
					if ($TEXT_DIRECTION=="ltr") print " &lrm; - ".$user["username"]."&lrm;</option>\n";
					else print " &rlm; - ".$user["username"]."&rlm;</option>\n";
				}
			}
			print "</select><input type=\"button\" value=\"".$gm_lang["send"]."\" onclick=\"message(document.messageform.touser.options[document.messageform.touser.selectedIndex].value, 'messaging2', ''); return false;\" />\n";
		}
		print "</form>\n";
		if ($block) print "</div>\n";
		print "</div>"; // blockcontent
		print "</div>"; // block
}
?>
