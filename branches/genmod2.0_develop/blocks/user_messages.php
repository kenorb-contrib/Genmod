<?php
/**
 * User Messages Block
 *
 * This block will print a users messages
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
 * @version $Id$
 * @package Genmod
 * @subpackage Blocks
 */

$GM_BLOCKS["print_user_messages"]["name"]       = GM_LANG_user_messages_block;
$GM_BLOCKS["print_user_messages"]["descr"]      = "user_messages_descr";
$GM_BLOCKS["print_user_messages"]["type"]       = "user";
$GM_BLOCKS["print_user_messages"]["canconfig"]	= false;
$GM_BLOCKS["print_user_messages"]["rss"]		= false;

//-- print user messages
function print_user_messages($block=true, $config="", $side, $index) {
		global $TEXT_DIRECTION, $TIME_FORMAT, $GM_IMAGES, $gm_user;

	print "<!-- Start User Messages Block //-->";
		$usermessages = MessageController::getUserMessages($gm_user->username);

		print "<div id=\"user_messages\" class=\"BlockContainer\">\n";
			print "<div class=\"BlockHeader\">";
				PrintHelpLink("mygedview_message_help", "qm", "my_messages");
				print GM_LANG_my_messages." &lrm;(".count($usermessages).")&lrm;";
			print "</div>";
			print "<div class=\"BlockContent\">";
				if ($block) print "<div class=\"RestrictedBlockHeightRight\">\n";
				else print "<div class=\"RestrictedBlockHeightMain\">\n";
					print "<form name=\"messageform\" action=\"\" onsubmit=\"return confirm('".GM_LANG_confirm_message_delete."');\">\n";
					if (count($usermessages)==0) {
						print "<div class=\"UserMessagesMessage\">".GM_LANG_no_messages."</div>";
					}
					else {
						print "<input type=\"hidden\" name=\"action\" value=\"deletemessage\" />\n";
						print "<table class=\"ListTable\"><tr>\n";
						print "<td class=\"ListTableColumnHeader\">".GM_LANG_delete."</td>\n";
						print "<td class=\"ListTableColumnHeader\">".GM_LANG_message_subject."</td>\n";
						print "<td class=\"ListTableColumnHeader\">".GM_LANG_date_created."</td>\n";
						print "<td class=\"ListTableColumnHeader\">".GM_LANG_message_from."</td>\n";
						print "</tr>\n";
						foreach($usermessages as $key=>$message) {
							if (!is_null($message->id)) $key = $message->id;
							print "<tr>";
							print "<td class=\"ListTableContent\">";
								print "<input type=\"checkbox\" name=\"message_id[]\" value=\"$key\" />";
							print "</td>\n";
							$showmsg=preg_replace("/(\w)\/(\w)/","\$1/<span style=\"font-size:1px;\"> </span>\$2",PrintReady($message->subject));
							$showmsg=preg_replace("/@/","@<span style=\"font-size:1px;\"> </span>",$showmsg);
							print "<td class=\"ListTableContent\">";
								print "<a href=\"#\" onclick=\"expand_layer('message$key'); return false;\"><b>".$showmsg."</b> <img id=\"message${key}_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" alt=\"\" title=\"\" /></a>";
							print "</td>\n";
							if (!is_null($message->created) && $message->created != "") $time = strtotime($message->created);
							else $time = time();
							$day = date("j", $time);
							$mon = date("M", $time);
							$year = date("Y", $time);
							// if incoming, print the from address.
							// if outgoing, print the to address.
							if ($message->from == $gm_user->username) $mdir = "to";
							else $mdir = "from";
							$tempuser =& User::GetInstance($message->$mdir);
							print "<td class=\"ListTableContent\">";
								print GetChangedDate("$day $mon $year")." - ".date($TIME_FORMAT, $time);
							print "</td>\n";
							print "<td class=\"ListTableContent\">";
								// If it's an existing user, print the details. Also do this if it doesn't appear to be a valid e-mail address
								if (!empty($tempuser->username) || stristr($message->$mdir, "Genmod-noreply") || !CheckEmailAddress($message->$mdir)) {
									print PrintReady($tempuser->firstname." ".$tempuser->lastname);
									if (!empty($tempuser->username)) $prt = " - ";
									else $prt = "";
									if ($TEXT_DIRECTION=="ltr") print " &lrm;".$prt.htmlspecialchars($message->$mdir)."&lrm;";
									else print " &rlm;".$prt.htmlspecialchars($message->$mdir)."&rlm;";
								}
								else print "<a href=\"mailto:".$message->$mdir."?SUBJECT=".$message->subject."\">".preg_replace("/@/","@<span style=\"font-size:1px;\"> </span>",$message->$mdir)."</a>";
							print "</td>\n";
							print "</tr>\n";
							print "<tr><td class=\"UserMessageMessageContainer\" colspan=\"4\">";
								print "<div id=\"message$key\" class=\"UserMessageMessageContent\" style=\"display: none;\">\n";
									$message->body = nl2br(preg_replace('#\( (http://\S+) \)#', "<a href=\"$1\" dir =\"ltr\">$1</a>", $message->body));
				
									print PrintReady($message->body)."\n";
									if (preg_match("/RE:/", $message->subject)==0) $message->subject = "RE:".$message->subject;
									// Only print the reply link if it's an incoming message.
									// Also, we don't use Genmod to send mail to non-users.
									// If the originator is not a user, let the Genmod user send a mail from his own mail system
									if ($mdir == "from") {
										if (!empty($tempuser->username)) print "<a href=\"#\" onclick=\"reply('".$message->$mdir."', '".addslashes($message->subject)."'); return false;\">".GM_LANG_reply."</a> | ";
										else if (!stristr($message->$mdir, "Genmod-noreply")) print "<a href=\"mailto:".$message->from."?SUBJECT=".$message->subject."\">".GM_LANG_reply."</a> | ";
									}
									print "<a href=\"index.php?action=deletemessage&amp;message_id=$key\" onclick=\"return confirm('".GM_LANG_confirm_message_delete."');\">".GM_LANG_delete."</a>";
								print "</div></td>";
							print "</tr>\n";
						}
						print "</table>\n";
						print "<input type=\"submit\"  value=\"".GM_LANG_delete."\" /><br /><br />\n";
					}
					$users = UserController::GetUsers("lastname", "asc", "firstname");
					if (count($users)>1) {
						print GM_LANG_message." <select name=\"touser\">\n";
						$username = $gm_user->username;
						if ($gm_user->userIsAdmin()) {
							print "<option value=\"all\">".GM_LANG_broadcast_all."</option>\n";
							print "<option value=\"never_logged\">".GM_LANG_broadcast_never_logged_in."</option>\n";
							print "<option value=\"last_6mo\">".GM_LANG_broadcast_not_logged_6mo."</option>\n";
						}
						foreach($users as $indexval => $user) {
							if ($username != $user->username && $user->verified_by_admin)  {
								print "<option value=\"".$user->username."\"";
								print ">".PrintReady($user->lastname.", ".$user->firstname);
								if ($TEXT_DIRECTION=="ltr") print " &lrm; - ".$user->username."&lrm;</option>\n";
								else print " &rlm; - ".$user->username."&rlm;</option>\n";
							}
						}
						print "</select><input type=\"button\" value=\"".GM_LANG_send."\" onclick=\"message(document.messageform.touser.options[document.messageform.touser.selectedIndex].value, 'messaging2', ''); return false;\" />\n";
					}
					print "</form>\n";
				print "</div>\n";
			print "</div>"; // blockcontent
		print "</div>"; // block
	print "<!-- End User Messages Block //-->";
}
?>
