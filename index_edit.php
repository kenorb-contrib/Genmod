<?php
/**
 * mygenmod page allows a logged in user the abilty
 * to keep bookmarks, see a list of upcoming events, etc.
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
 * This Page Is Valid XHTML 1.0 Transitional! > 13 August 2005
 *
 * @package Genmod
 * @subpackage Display
 * @version $Id: index_edit.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

global $GM_IMAGES, $TEXT_DIRECTION;
global $GEDCOM_TITLE;


//-- make sure that they have user status before they can use this page
//-- otherwise have them login again
if (empty($gm_user->username) || empty($name)) {
	PrintSimpleHeader("");
	print GM_LANG_access_denied;
	print "<div class=\"CloseWindow\"><a href=\"javascript:// ".GM_LANG_close_window."\" onclick=\"self.close();\">".GM_LANG_close_window."</a></div>\n";
	PrintSimpleFooter();
	exit;
}
if (!$gm_user->userIsAdmin()) $setdefault=false;

if (!isset($action)) $action="";
if (!isset($command)) $command="user";
if (!isset($main)) $main=array();
if (!isset($right)) $right=array();
if (!isset($setdefault)) $setdefault=false;
if (!isset($side)) $side="main";
if (!isset($index)) $index=1;

// Define all the icons we're going to use
$IconHelp = GM_LANG_qm;
if (GM_USE_HELPIMG) {
	$IconHelp = "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["help"]["small"]."\" class=\"Icon\" width=\"15\" height=\"15\" alt=\"\" />";
}
$IconUarrow = "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["uarrow"]["other"]."\" width=\"20\" height=\"20\" alt=\"\" />";
$IconDarrow = "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["darrow"]["other"]."\" width=\"20\" height=\"20\" alt=\"\" />";
$IconRarrow = "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["rarrow"]["other"]."\" width=\"20\" height=\"20\" alt=\"\" />";
$IconLarrow = "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["larrow"]["other"]."\" width=\"20\" height=\"20\" alt=\"\" />";
$IconRDarrow = "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["rdarrow"]["other"]."\" width=\"20\" height=\"20\" alt=\"\" />";
$IconLDarrow = "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["ldarrow"]["other"]."\" width=\"20\" height=\"20\" alt=\"\" />";

/**
 * Block definition array
 *
 * The following block definition array defines the
 * blocks that can be used to customize the portals
 * their names and the function to call them
 * "name" is the name of the block in the lists
 * "descr" is the name of a gm_lang var to describe this block.  Eg: if the block is
 * described by GM_LANG_my_block_text"], put "my_block_text" here.
 * "type" the options are "user" or "gedcom" or undefined
 * - The type determines which lists the block is available in.
 * - Leaving the type undefined allows it to be on both the user and gedcom portal
 *
 * @global array $GM_BLOCKS
 */
$GM_BLOCKS = array();

//-- See if https is used for login
$httpslogin = false;
if ((LOGIN_URL == "" && substr(SERVER_URL,0,5) == "https") || substr(LOGIN_URL,0,5) == "https") $httpslogin = true;
//-- load all of the blocks

$d = dir("blocks");
while (false !== ($entry = $d->read())) {
	if (strstr($entry, ".")==".php") {
		if (!($entry == "login_block.php" && $httpslogin)) include_once("blocks/".$entry);
	}
}
$d->close();

//	Build sorted table of block names, BUT:
//		include in this table ONLY if the block is appropriate for this page
//		If $BLOCK["type"] is	"both", include in both page types
//					"user", include in Portal page only
//					"gedcom", include in Index page only
$SortedBlocks = array();
foreach($GM_BLOCKS as $key => $BLOCK) {
	if (!isset($BLOCK["type"])) $BLOCK["type"] = "both";
	if (($BLOCK["type"]=="both") or ($BLOCK["type"]==$command)) {
		$SortedBlocks[$key] = $BLOCK["name"];
	}
}
asort($SortedBlocks);
reset($SortedBlocks);

// Build sorted table of block summary descriptions

$block_summary_table = "";
$SortedBlocks = array_flip($SortedBlocks);
foreach($SortedBlocks as $key => $b) {
	$temp = $GM_BLOCKS[$b]["descr"];
	$block_summary_table .= "<tr>";
	$block_summary_table .= "<td class=\"NavBlockField\">".$GM_BLOCKS[$b]["name"]."</td>";
	$block_summary_table .= "<td class=\"NavBlockField\">#gm_lang[$temp]#</td>";
	$block_summary_table .= "</tr>";
}
define('GM_LANG_block_summary_table', $block_summary_table);
$SortedBlocks = array_flip($SortedBlocks);

//-- get the blocks list
if ($command=="user") $ublocks = new Blocks("user", $gm_user->username, $action);
else $ublocks = new Blocks("gedcom", "", $action);

if ($command=="user") PrintSimpleHeader(GM_LANG_mygenmod);
else PrintSimpleHeader($GEDCOMS[GedcomConfig::$GEDCOMID]["title"]);

$GEDCOM_TITLE = PrintReady($GEDCOMS[GedcomConfig::$GEDCOMID]["title"]);  // needed in GM_LANG_rss_descr

if ($action=="updateconfig") {
	$block = $ublocks->$side;
	$block = $block[$index];

	if (isset($GM_BLOCKS[$block[0]]["canconfig"]) && $GM_BLOCKS[$block[0]]["canconfig"] && isset($GM_BLOCKS[$block[0]]["config"]) && is_array($GM_BLOCKS[$block[0]]["config"])) {
		$config = $block[1];
		foreach($GM_BLOCKS[$block[0]]["config"] as $config_name=>$config_value) {
			if (isset($_POST[$config_name])) {
				$config[$config_name] = stripslashes($_POST[$config_name]);
			}
			else {
				$config[$config_name] = "";
			}
		}
		// Cleanup the config for parameters that no longer exist
		foreach ($config as $key => $value) {
			if (!array_key_exists($key, $GM_BLOCKS[$block[0]]["config"])) unset($config[$key]);
		}
		if ($side == "main") $ublocks->main[$index][1] = $config;
		else $ublocks->right[$index][1] = $config;
		$ublocks->SetValues($setdefault);
		if ($block[0] == "print_upcoming_events" || $block[0] == "print_todays_events") GedcomConfig::ResetCaches();
		if ($block[0] == "print_block_name_top10") unset($_SESSION["top10_surnames"]);
	}
	print GM_LANG_config_update_ok."<br />\n";?>
	<script language="JavaScript" type="text/javascript">
	<!--
	opener.location.reload();
	window.close();
	//-->
	</script>
	<?php
}

if ($action=="update") {
	$newublocks = new Blocks($command, $name, "init");
	if (is_array($main)) {
		foreach($main as $indexval => $b) {
			$config = "";
			$index = "";
			reset($ublocks->main);
			foreach($ublocks->main as $index=>$block) {
				if ($block[0]==$b) {
					$config = $block[1];
					break;
				}
			}
			if ($index!="") unset($ublocks->main[$index]);
			$newublocks->main[] = array($b, $config);
		}
	}

	if (is_array($right)) {
		foreach($right as $indexval => $b) {
			$config = "";
			$index = "";
			reset($ublocks->right);
			foreach($ublocks->right as $index=>$block) {
				if ($block[0]==$b) {
					$config = $block[1];
					break;
				}
			}
			if ($index!="") unset($ublocks->right[$index]);
			$newublocks->right[] = array($b, $config);
		}
	}
	$newublocks->SetValues($setdefault);?>
	<script language="JavaScript" type="text/javascript">
	<!--
	opener.location.reload();
	window.close();
	//-->
	</script>
	<?php
}

// NOTE: Store the changed userfavorite
if ($action == "storefav") {
	$favorite = new favorite();
	$favorite->username = $username;
	$favorite->id = $id;
	$favorite->gid = $gid;
	$favorite->type = $type;
	$favorite->file = $file;
	if (isset($favurl)) $favorite->url = $favurl;
	if (isset($favnote)) $favorite->note = $favnote;
	if (isset($favtitle)) $favorite->title = $favtitle;
	if ($favorite->SetFavorite()) { ?>
		<script language="JavaScript" type="text/javascript">
		<!--
		opener.location.reload();
		window.close();
		//-->
		</script>
		<?php
	}
	else {
		print "<span class=\"Error\">".GM_LANG_favorite_not_stored."</span>";
		print "<div class=\"CloseWindow\"><a href=\"javascript:// ".GM_LANG_close_window."\" onclick=\"self.close();\">".GM_LANG_close_window."</a></div>\n";
		PrintFooter();
		exit;
	}
}

$block = $ublocks->$side;
if ($action=="configure" && isset($block[$index])) {
	
	$block = $block[$index];
	
	print "\n<form name=\"block\" method=\"post\" action=\"index_edit.php\">\n";
	print "<input type=\"hidden\" name=\"command\" value=\"$command\" />\n";
	print "<input type=\"hidden\" name=\"action\" value=\"updateconfig\" />\n";
	print "<input type=\"hidden\" name=\"name\" value=\"$name\" />\n";
	print "<input type=\"hidden\" name=\"side\" value=\"$side\" />\n";
	print "<input type=\"hidden\" name=\"index\" value=\"$index\" />\n";
	print "<table class=\"NavBlockTable\">";
	print "<tr><td class=\"NavBlockHeader\" colspan=\"2\">".GM_LANG_config_block.": ".$GM_BLOCKS[$block[0]]["name"]."</td></tr>";
	
	/**
	print "<label class=\"InstallLabelForm\" for=\"username\">".GM_LANG_username"]."</label>";
	print "<input class=\"input_form\" type=\"text\" id=\"username\" name=\"username\" /><br style=\"clear: left;\"/>";
	*/
	
	if ($GM_BLOCKS[$block[0]]["canconfig"]) {
		eval($block[0]."_config(\$block[1]);");
		print "<tr><td colspan=\"2\" class=\"NavBlockFooter\">";
		PrintHelpLink("click_here_help", "qm", "click_here");
		print "<input type=\"button\" value=\"".GM_LANG_click_here."\" onclick=\"document.block.submit();\" />";
		print "&nbsp&nbsp;<input type =\"button\" value=\"".GM_LANG_cancel."\" onclick=\"window.close();\" />";
		print "</td></tr>";
	}
	else {
		print "<tr><td colspan=\"2\" class=\"NavBlockLabel\">";
		print GM_LANG_block_not_configure;
		print "</td></tr>";
		print "<tr><td colspan=\"2\" class=\"NavBlockFooter\">";
		PrintHelpLink("click_here_help", "qm");
		print "<input type=\"button\" value=\"".GM_LANG_click_here."\" onclick=\"parentrefresh();\" />";
		print "</td></tr>";
	}
	print "</table>";
	print "</form>";
}
else {
	?>
	<script language="JavaScript" type="text/javascript">
	<!--
	/**
	 * Move Up Block JavaScript function
	 *
	 * This function moves the selected option up in the given select list
	 * @param String section_name the name of the select to move the options
	 */
	function move_up_block(section_name) {
		section_select = document.getElementById(section_name);
		if (section_select) {
			if (section_select.selectedIndex <= 0) return false;
			index = section_select.selectedIndex;
			temp = new Option(section_select.options[index-1].text, section_select.options[index-1].value);
			section_select.options[index-1] = new Option(section_select.options[index].text, section_select.options[index].value);
			section_select.options[index] = temp;
			section_select.selectedIndex = index-1;
		}
	}

	/**
	 * Move Down Block JavaScript function
	 *
	 * This function moves the selected option down in the given select list
	 * @param String section_name the name of the select to move the options
	 */
	function move_down_block(section_name) {
		section_select = document.getElementById(section_name);
		if (section_select) {
			if (section_select.selectedIndex < 0) return false;
			if (section_select.selectedIndex >= section_select.length-1) return false;
			index = section_select.selectedIndex;
			temp = new Option(section_select.options[index+1].text, section_select.options[index+1].value);
			section_select.options[index+1] = new Option(section_select.options[index].text, section_select.options[index].value);
			section_select.options[index] = temp;
			section_select.selectedIndex = index+1;
		}
	}

	/**
	 * Move Block from one column to the other JavaScript function
	 *
	 * This function moves the selected option down in the given select list
	 * @author KosherJava
	 * @param String add_to_column the name of the select to move the option to
	 * @param String remove_from_column the name of the select to remove the option from
	 */
	function move_left_right_block(add_to_column, remove_from_column) {
		section_select = document.getElementById(remove_from_column);
		add_select = document.getElementById(add_to_column);
		instruct = document.getElementById('IndexEditAdvice');
		if ((section_select) && (add_select)) {
			add_option = add_select.options[add_select.selectedIndex];
			if (remove_from_column != 'available_select') {
				section_select.options[section_select.length] = new Option(add_option.text, add_option.value);
			}
			if (add_to_column != 'available_select') {
				add_select.options[add_select.selectedIndex] = null; //remove from list
			}
		}
	}
	/**
	 * Select Options JavaScript function
	 *
	 * This function selects all the options in the multiple select lists
	 */
	function select_options() {
		section_select = document.getElementById('main_select');
		if (section_select) {
			for(i=0; i<section_select.length; i++) {
				section_select.options[i].selected=true;
			}
		}
		section_select = document.getElementById('right_select');
		if (section_select) {
			for(i=0; i<section_select.length; i++) {
				section_select.options[i].selected=true;
			}
		}
		return true;
	}

	/**
	 * Load Block Description array for use by jscript
	 */
	<?php
	print "var block_descr = new Array();\n";
	foreach($GM_BLOCKS as $b=>$block) {
		print "block_descr['$b'] = '".preg_replace("/'/", "\\'", PrintText($block["descr"],0,1))."';\n";
	}
	print "block_descr['advice1'] = '".preg_replace("/'/", "\\'", PrintText('index_edit_advice',0,1))."';\n";
	?>


	/**
	 * Show Block Description JavaScript function
	 *
	 * This function shows a description for the selected option
	 * @param String list_name the name of the select to get the option from
	 */
	function show_description(list_name) {
		list_select = document.getElementById(list_name);
		instruct = document.getElementById('IndexEditAdvice');
		if (list_select && instruct) {
			instruct.innerHTML = block_descr[list_select.options[list_select.selectedIndex].value];
		}
		list1 = document.getElementById('main_select');
		list2 = document.getElementById('available_select');
		list3 = document.getElementById('right_select');
		if (list_name=='main_select') {
			list2.selectedIndex = -1;
			list3.selectedIndex = -1;
		}
		if (list_name=='available_select') {
			list1.selectedIndex = -1;
			list3.selectedIndex = -1;
		}
		if (list_name=='right_select') {
			list1.selectedIndex = -1;
			list2.selectedIndex = -1;
		}
	}
	//-->
	</script>
	<?php
	// NOTE: Page block settings
	?>
	<div id="IndexEditConfigure">
		<form name="config_setup" method="post" action="index_edit.php">
		<input type="hidden" name="command" value="<?php print $command;?>" />
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="name" value="<?php print $name;?>" />
		<?php

		// NOTE: Print the container
		// NOTE: Print the header
		print "<div id=\"IndexEditContainer\">";
			print "<div class=\"NavBlockHeader\">";
				PrintHelpLink("portal_config_intructions", "qm");
				if ($command=="user") print Str2Upper(GM_LANG_customize_page);
				else print Str2Upper(GM_LANG_customize_gedcom_page);
			print "</div>";
			print "<div class=\"IndexEditContent\">";
				// NOTE: Print the arrows for moving the left block items up and down
				print "<div id=\"IndexEditLeftArrow\">";
					print "<a tabindex=\"-1\" onclick=\"move_up_block('main_select');\" title=\"".GM_LANG_move_up."\">".$IconUarrow."</a>";
					print "<br />";
					print "<a tabindex=\"-1\" onclick=\"move_down_block('main_select');\" title=\"".GM_LANG_move_down."\">".$IconDarrow."</a>";
					print "<br /><br />";
					PrintHelpLink("block_move_up_help", "qm");
				print "</div>";
				
				// NOTE: Print the blocks currently in the left frame
				print "<div id=\"IndexEditLeft\">";
					print GM_LANG_main_section."<br />";
					print "<select multiple=\"multiple\" id=\"main_select\" name=\"main[]\" size=\"10\" onchange=\"show_description('main_select');\">\n";
					foreach($ublocks->main as $indexval => $block) {
						if (function_exists($block[0])) {
							print "<option value=\"$block[0]\">".$GM_BLOCKS[$block[0]]["name"]."</option>\n";
						}
					}
					print "</select>\n";
				print "</div>";
				
				// NOTE: Print the arrows for moving items left and right
				print "<div id=\"IndexEditLeftRightArrow\">";
					print "<a tabindex=\"-1\" onclick=\"move_left_right_block('main_select', 'right_select');\" title=\"".GM_LANG_move_right."\">".$IconRDarrow."</a>";
					print "<br />";
					print "<a tabindex=\"-1\" onclick=\"move_left_right_block('main_select', 'available_select');\" title=\"".GM_LANG_remove."\">".$IconRarrow."</a>";
					print "<br />";
					print "<a tabindex=\"-1\" onclick=\"move_left_right_block('available_select', 'main_select');\" title=\"".GM_LANG_add."\">".$IconLarrow."</a>";
					print "<br /><br />";
					PrintHelpLink("block_move_right_help", "qm");
				print "</div>";
				
				// NOTE: Print the arrows for moving the right block items up and down
				print "<div id=\"IndexEditRightArrow\">";
					print "<a tabindex=\"-1\" onclick=\"move_up_block('right_select');\" title=\"".GM_LANG_move_up."\">".$IconUarrow."</a>";
					print "<br />";
					print "<a tabindex=\"-1\" onclick=\"move_down_block('right_select');\" title=\"".GM_LANG_move_down."\">".$IconDarrow."</a>";
					print "<br /><br />";
					PrintHelpLink("block_move_up_help", "qm");
				print "</div>";
				
				// NOTE: Print the blocks currently in the right frame
				print "<div id=\"IndexEditRight\">";
					print GM_LANG_right_section."<br />";
					print "<select multiple=\"multiple\" id=\"right_select\" name=\"right[]\" size=\"10\" onchange=\"show_description('right_select');\">\n";
					foreach($ublocks->right as $indexval => $block) {
						if (function_exists($block[0])) {
							print "<option value=\"$block[0]\">".$GM_BLOCKS[$block[0]]["name"]."</option>\n";
						}
					}
					print "</select>\n";
				print "</div>";
				
				// NOTE: Print the arrows for moving items left and right
				print "<div id=\"IndexEditRightLeftArrow\">";
					print "<a tabindex=\"-1\" onclick=\"move_left_right_block('right_select', 'main_select');\" title=\"".GM_LANG_move_left."\">".$IconLDarrow."</a>";
					print "<br />";
					print "<a tabindex=\"-1\" onclick=\"move_left_right_block('right_select', 'available_select');\" title=\"".GM_LANG_remove."\">".$IconLarrow."</a>";
					print "<br />";
					print "<a tabindex=\"-1\" onclick=\"move_left_right_block('available_select', 'right_select');\" title=\"".GM_LANG_add."\">".$IconRarrow."</a>";
					print "<br /><br />";
					PrintHelpLink("block_move_right_help", "qm");
				print "</div>";
				
				// NOTE: Print the blocks currently available
				print "<div id=\"IndexEditContent\">";
					print " <a href=\"#\" class=\"help\" tabindex=\"0\" onclick=\"expand_layer('IndexEditConfigureHelp',true); expand_layer('IndexEditConfigure', false);\">".$IconHelp."</a> \n";
					print GM_LANG_available_blocks."<br />";
					print "<select id=\"available_select\" name=\"available[]\" size=\"10\" onchange=\"show_description('available_select');\">\n";
					foreach($SortedBlocks as $key => $value) {
						if (!isset($GM_BLOCKS[$key]["type"])) $GM_BLOCKS[$key]["type"]=$command;
						print "<option value=\"$key\">".$SortedBlocks[$key]."</option>\n";
					}
					print "</select>\n";
							
					// NOTE: Print the box for showing the advice
					print "<div id=\"IndexEditAdvice\">";
						print GM_LANG_index_edit_advice;
					print "</div>";
					if (($gm_user->userIsAdmin())&&($command=='user')) {
						print "<div id=\"IndexEditSetDefault\">";
						print GM_LANG_use_blocks_for_default."&nbsp;&nbsp;<input type=\"checkbox\" name=\"setdefault\" value=\"1\" />\n";
						print "</div>";
					}
				print "</div>";
			print "</div>";
			// NOTE: Print the submit buttons
			print "<div class=\"NavBlockFooter\">";
				
				if ($command=='user') {
					PrintHelpLink("block_default_portal", "qm");
				} 
				else {
					PrintHelpLink("block_default_index", "qm");
				}
				print "<input type=\"button\" value=\"".GM_LANG_reset_default_blocks."\" onclick=\"window.location='index_edit.php?command=$command&amp;action=reset&amp;name=".preg_replace("/'/", "\'", $name)."';\" />\n";
				print "&nbsp;&nbsp;";
				PrintHelpLink("click_here_help", "qm");
				print "<input type=\"button\" value=\"".GM_LANG_click_here."\" onclick=\"select_options(); document.config_setup.submit();\" />\n";
				print "&nbsp;&nbsp;";
				print "<input type =\"button\" value=\"".GM_LANG_cancel."\" onclick=\"window.close();\" />";
			print "</div>";
		print "</div>"; // Close IndexEditContainer
		print "</form>\n";
	print "</div>\n"; // Close IndexEditConfigure

	// NOTE: Hidden help text for column items
	print "\n\t<div id=\"IndexEditConfigureHelp\" class=\"TabPage\">\n\t";

	print "<br /><center><input type=\"button\" value=\"".GM_LANG_click_here."\" onclick=\"expand_layer('IndexEditConfigure', true); expand_layer('IndexEditConfigureHelp', false);\" /></center><br /><br />\n";
	PrintText("block_summaries");

	// end of 2nd tab
	print "</div>\n";
}
PrintSimpleFooter();
?>
