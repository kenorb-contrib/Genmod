<?php
/**
 * User Messages Block
 *
 * This block will print a users messages
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
 * @version $Id: user_messages.php 29 2022-07-17 13:18:20Z Boudewijn $
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

	print "<!-- Start User Messages Block //-->\n";
		print "<div id=\"user_messages\" class=\"BlockContainer\">\n";
			print "<div class=\"BlockHeader\">";
				$usermessagescount = MessageController::getUserMessagesCount($gm_user->username);
				PrintHelpLink("mygenmod_message_help", "qm", "my_messages");
				print "<div class=\"BlockHeaderText\">".GM_LANG_my_messages." &lrm;(".$usermessagescount.")&lrm;</div>";
			print "</div>";
			print "<div class=\"BlockContent\">";
				if ($block) print "<div class=\"RestrictedBlockHeightRight\" id=\"UserMessageBlock\">\n";
				else print "<div class=\"RestrictedBlockHeightMain\" id=\"UserMessageBlock\">\n";
				?>
				<script language="javascript" type="text/javascript">
				<!--
				function load_user_message_block() {
					sndReq("UserMessageBlock", "loadblockusermessage", true);
				}
				addLoadEvent(load_user_message_block);
				//-->
				</script>
				<?php
				print "</div>\n";
			print "</div>"; // blockcontent
		print "</div>"; // block
	print "\n<!-- End User Messages Block //-->\n";
}
?>
