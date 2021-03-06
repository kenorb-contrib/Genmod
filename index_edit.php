<?php
/**
 * MyGedView page allows a logged in user the abilty
 * to keep bookmarks, see a list of upcoming events, etc.
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
 * This Page Is Valid XHTML 1.0 Transitional! > 13 August 2005
 *
 * @package Genmod
 * @subpackage Display
 * @version $Id: index_edit.php,v 1.14 2006/05/13 07:53:45 roland-d Exp $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

global $gm_lang, $GM_USE_HELPIMG, $GM_IMAGES, $GM_IMAGE_DIR, $TEXT_DIRECTION;
global $GEDCOM_TITLE;


//-- make sure that they have user status before they can use this page
//-- otherwise have them login again
$uname = $gm_username;
if (empty($uname) || empty($name)) {
	print_simple_header("");
	print $gm_lang["access_denied"];
	print "<div class=\"center\"><a href=\"javascript:// ".$gm_lang["close_window"]."\" onclick=\"self.close();\">".$gm_lang["close_window"]."</a></div>\n";
	print_simple_footer();
	exit;
}
if (!userIsAdmin($uname)) $setdefault=false;

if (!isset($action)) $action="";
if (!isset($command)) $command="user";
if (!isset($main)) $main=array();
if (!isset($right)) $right=array();
if (!isset($setdefault)) $setdefault=false;
if (!isset($side)) $side="main";
if (!isset($index)) $index=1;

// Define all the icons we're going to use
$IconHelp = $gm_lang["qm"];
if ($GM_USE_HELPIMG) {
	$IconHelp = "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["help"]["small"]."\" class=\"icon\" width=\"15\" height=\"15\" alt=\"\" />";
}
$IconUarrow = "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["uarrow"]["other"]."\" width=\"20\" height=\"20\" alt=\"\" />";
$IconDarrow = "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["darrow"]["other"]."\" width=\"20\" height=\"20\" alt=\"\" />";
$IconRarrow = "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["rarrow"]["other"]."\" width=\"20\" height=\"20\" alt=\"\" />";
$IconLarrow = "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["larrow"]["other"]."\" width=\"20\" height=\"20\" alt=\"\" />";
$IconRDarrow = "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["rdarrow"]["other"]."\" width=\"20\" height=\"20\" alt=\"\" />";
$IconLDarrow = "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["ldarrow"]["other"]."\" width=\"20\" height=\"20\" alt=\"\" />";

/**
 * Block definition array
 *
 * The following block definition array defines the
 * blocks that can be used to customize the portals
 * their names and the function to call them
 * "name" is the name of the block in the lists
 * "descr" is the name of a gm_lang var to describe this block.  Eg: if the block is
 * described by $gm_lang["my_block_text"], put "my_block_text" here.
 * "type" the options are "user" or "gedcom" or undefined
 * - The type determines which lists the block is available in.
 * - Leaving the type undefined allows it to be on both the user and gedcom portal
 *
 * @global array $GM_BLOCKS
 */
$GM_BLOCKS = array();

//-- See if https is used for login
$httpslogin = false;
if ((empty($LOGIN_URL) && substr($SERVER_URL,0,5) == "https") || substr($LOGIN_URL,0,5) == "https") $httpslogin = true;
//-- load all of the blocks
$d = dir("blocks");
while (false !== ($entry = $d->read())) {
	if (($entry!=".") && ($entry!="..") && ($entry!="CVS") && (strstr($entry, ".")==".php")) {
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
global $gm_lang;
$gm_lang["block_summary_table"] = "";
$SortedBlocks = array_flip($SortedBlocks);
foreach($SortedBlocks as $key => $b) {
	$temp = $GM_BLOCKS[$b]["descr"];
	$gm_lang["block_summary_table"] .= "<tr valign='top'>";
	$gm_lang["block_summary_table"] .= "<td>".$GM_BLOCKS[$b]["name"]."</td>";
	$gm_lang["block_summary_table"] .= "<td>#gm_lang[$temp]#</td>";
	$gm_lang["block_summary_table"] .= "</tr>";
}
$SortedBlocks = array_flip($SortedBlocks);

//-- get the blocks list
if ($command=="user") {
	$ublocks = getBlocks($uname);
	if (($action=="reset") || ((count($ublocks["main"])==0) && (count($ublocks["right"])==0))) {
		$ublocks["main"] = array();
		$ublocks["main"][] = array("print_todays_events", "");
		$ublocks["main"][] = array("print_user_messages", "");
		$ublocks["main"][] = array("print_user_favorites", "");

		$ublocks["right"] = array();
		$ublocks["right"][] = array("print_welcome_block", "");
		$ublocks["right"][] = array("print_random_media", "");
		$ublocks["right"][] = array("print_upcoming_events", "");
		$ublocks["right"][] = array("print_logged_in_users", "");
	}
}
else {
	$ublocks = getBlocks($GEDCOM);
	if (($action=="reset") or ((count($ublocks["main"])==0) and (count($ublocks["right"])==0))) {
		$ublocks["main"] = array();
		$ublocks["main"][] = array("print_gedcom_stats", "");
		$ublocks["main"][] = array("print_gedcom_news", "");
		$ublocks["main"][] = array("print_gedcom_favorites", "");
		$ublocks["main"][] = array("review_changes_block", "");

		$ublocks["right"] = array();
		$ublocks["right"][] = array("print_gedcom_block", "");
		$ublocks["right"][] = array("print_random_media", "");
		$ublocks["right"][] = array("print_todays_events", "");
		$ublocks["right"][] = array("print_logged_in_users", "");
	}
}

if ($command=="user") print_simple_header($gm_lang["mygedview"]);
else print_simple_header($GEDCOMS[$GEDCOM]["title"]);

$GEDCOM_TITLE = PrintReady($GEDCOMS[$GEDCOM]["title"]);  // needed in $gm_lang["rss_descr"]

if ($action=="updateconfig") {
	$block = $ublocks[$side][$index];
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
		$ublocks[$side][$index][1] = $config;
		setBlocks($name, $ublocks, $setdefault);
	}
	print $gm_lang["config_update_ok"]."<br />\n";?>
	<script language="JavaScript" type="text/javascript">
	opener.location.reload();
	window.close();
	</script>
	<?php
}

if ($action=="update") {
	$newublocks["main"] = array();
	if (is_array($main)) {
		foreach($main as $indexval => $b) {
			$config = "";
			$index = "";
			reset($ublocks["main"]);
			foreach($ublocks["main"] as $index=>$block) {
				if ($block[0]==$b) {
					$config = $block[1];
					break;
				}
			}
			if ($index!="") unset($ublocks["main"][$index]);
			$newublocks["main"][] = array($b, $config);
		}
	}

	$newublocks["right"] = array();
	if (is_array($right)) {
		foreach($right as $indexval => $b) {
			$config = "";
			$index = "";
			reset($ublocks["right"]);
			foreach($ublocks["right"] as $index=>$block) {
				if ($block[0]==$b) {
					$config = $block[1];
					break;
				}
			}
			if ($index!="") unset($ublocks["right"][$index]);
			$newublocks["right"][] = array($b, $config);
		}
	}
	$ublocks = $newublocks;
	setBlocks($name, $ublocks, $setdefault); ?>
	<script language="JavaScript" type="text/javascript">
	opener.location.reload();
	window.close();
	</script>
	<?php
}

if ($action=="configure" && isset($ublocks[$side][$index])) {
	$block = $ublocks[$side][$index];
	print "<table class=\"facts_table ".$TEXT_DIRECTION."\">";
	print "<tr><td class=\"facts_label\">";
	print "<h2>".$gm_lang["config_block"]."</h2>";
	print "</td></tr>";
	print "<tr><td class=\"topbottombar\">";
	print "<b>".$GM_BLOCKS[$block[0]]["name"]."</b>";
	print "</td></tr>";
	print "</table>";
	
	/**
	print "<label class=\"label_form\" for=\"username\">".$gm_lang["username"]."</label>";
	print "<input class=\"input_form\" type=\"text\" id=\"username\" name=\"username\" /><br style=\"clear: left;\"/>";
	*/
	print "\n<form name=\"block\" method=\"post\" action=\"index_edit.php\">\n";
	print "<input type=\"hidden\" name=\"command\" value=\"$command\" />\n";
	print "<input type=\"hidden\" name=\"action\" value=\"updateconfig\" />\n";
	print "<input type=\"hidden\" name=\"name\" value=\"$name\" />\n";
	print "<input type=\"hidden\" name=\"side\" value=\"$side\" />\n";
	print "<input type=\"hidden\" name=\"index\" value=\"$index\" />\n";
	print "<table border=\"0\" class=\"facts_table ".$TEXT_DIRECTION."\">";
	if ($GM_BLOCKS[$block[0]]["canconfig"]) {
		eval($block[0]."_config(\$block[1]);");
		print "<tr><td colspan=\"2\" class=\"center\">";
		print_help_link("click_here_help", "qm", "click_here");
		print "<input type=\"button\" value=\"".$gm_lang["click_here"]."\" onclick=\"document.block.submit();\" />";
		print "&nbsp&nbsp;<input type =\"button\" value=\"".$gm_lang["cancel"]."\" onclick=\"window.close();\" />";
		print "</td></tr>";
	}
	else {
		print "<tr><td colspan=\"2\" class=\"shade1\">";
		print $gm_lang["block_not_configure"];
		print "</td></tr>";
		print "<tr><td colspan=\"2\" class=\"center\">";
		print_help_link("click_here_help", "qm");
		print "<input type=\"button\" value=\"".$gm_lang["click_here"]."\" onclick=\"parentrefresh();\" />";
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
		instruct = document.getElementById('index_edit_advice');
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
		print "block_descr['$b'] = '".preg_replace("/'/", "\\'", print_text($block["descr"],0,1))."';\n";
	}
	print "block_descr['advice1'] = '".preg_replace("/'/", "\\'", print_text('index_edit_advice',0,1))."';\n";
	?>


	/**
	 * Show Block Description JavaScript function
	 *
	 * This function shows a description for the selected option
	 * @param String list_name the name of the select to get the option from
	 */
	function show_description(list_name) {
		list_select = document.getElementById(list_name);
		instruct = document.getElementById('index_edit_advice');
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
	<div id="configure" class="tab_page center" style="position: absolute; display: block; top: auto; left: auto; z-index: 1; ">
		<br />
		<form name="config_setup" method="post" action="index_edit.php">
		<input type="hidden" name="command" value="<?php print $command;?>" />
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="name" value="<?php print $name;?>" />
		<?php
		// NOTE: Print the header
		print "<div class=\"topbottombar\">";
			print_help_link("portal_config_intructions", "qm");
			if ($command=="user") print "<b>".str2upper($gm_lang["customize_page"])."</b>";
			else print "<b>".str2upper($gm_lang["customize_gedcom_page"])."</b>";
		print "</div>";
		
		// NOTE: Print the container
		print "<div id=\"index_edit_container\">";
			// NOTE: Print the arrows for moving the left block items up and down
			print "<div id=\"index_edit_left_arrow\">";
				print "<a tabindex=\"-1\" onclick=\"move_up_block('main_select');\" title=\"".$gm_lang["move_up"]."\">".$IconUarrow."</a>";
				print "<br />";
				print "<a tabindex=\"-1\" onclick=\"move_down_block('main_select');\" title=\"".$gm_lang["move_down"]."\">".$IconDarrow."</a>";
				print "<br /><br />";
				print_help_link("block_move_up_help", "qm");
			print "</div>";
			
			// NOTE: Print the blocks currently in the left frame
			print "<div id=\"index_edit_left\">";
				print "<b>".$gm_lang["main_section"]."</b>";
				print "<select multiple=\"multiple\" id=\"main_select\" name=\"main[]\" size=\"10\" onchange=\"show_description('main_select');\">\n";
				foreach($ublocks["main"] as $indexval => $block) {
					if (function_exists($block[0])) {
						print "<option value=\"$block[0]\">".$GM_BLOCKS[$block[0]]["name"]."</option>\n";
					}
				}
				print "</select>\n";
			print "</div>";
			
			// NOTE: Print the arrows for moving items left and right
			print "<div id=\"index_edit_left_right_arrow\">";
				print "<a tabindex=\"-1\" onclick=\"move_left_right_block('main_select', 'right_select');\" title=\"".$gm_lang["move_right"]."\">".$IconRDarrow."</a>";
				print "<br />";
				print "<a tabindex=\"-1\" onclick=\"move_left_right_block('main_select', 'available_select');\" title=\"".$gm_lang["remove"]."\">".$IconRarrow."</a>";
				print "<br />";
				print "<a tabindex=\"-1\" onclick=\"move_left_right_block('available_select', 'main_select');\" title=\"".$gm_lang["add"]."\">".$IconLarrow."</a>";
				print "<br /><br />";
				print_help_link("block_move_right_help", "qm");
			print "</div>";
			
			// NOTE: Print the arrows for moving the right block items up and down
			print "<div id=\"index_edit_right_arrow\">";
				print "<a tabindex=\"-1\" onclick=\"move_up_block('right_select');\" title=\"".$gm_lang["move_up"]."\">".$IconUarrow."</a>";
				print "<br />";
				print "<a tabindex=\"-1\" onclick=\"move_down_block('right_select');\" title=\"".$gm_lang["move_down"]."\">".$IconDarrow."</a>";
				print "<br /><br />";
				print_help_link("block_move_up_help", "qm");
			print "</div>";
			
			// NOTE: Print the blocks currently in the right frame
			print "<div id=\"index_edit_right\">";
				print "<b>".$gm_lang["right_section"]."</b>";
				print "<select multiple=\"multiple\" id=\"right_select\" name=\"right[]\" size=\"10\" onchange=\"show_description('right_select');\">\n";
				foreach($ublocks["right"] as $indexval => $block) {
					if (function_exists($block[0])) {
						print "<option value=\"$block[0]\">".$GM_BLOCKS[$block[0]]["name"]."</option>\n";
					}
				}
				print "</select>\n";
			print "</div>";
			
			// NOTE: Print the arrows for moving items left and right
			print "<div id=\"index_edit_right_left_arrow\">";
				print "<a tabindex=\"-1\" onclick=\"move_left_right_block('right_select', 'main_select');\" title=\"".$gm_lang["move_left"]."\">".$IconLDarrow."</a>";
				print "<br />";
				print "<a tabindex=\"-1\" onclick=\"move_left_right_block('right_select', 'available_select');\" title=\"".$gm_lang["remove"]."\">".$IconLarrow."</a>";
				print "<br />";
				print "<a tabindex=\"-1\" onclick=\"move_left_right_block('available_select', 'right_select');\" title=\"".$gm_lang["add"]."\">".$IconRarrow."</a>";
				print "<br /><br />";
				print_help_link("block_move_right_help", "qm");
			print "</div>";
			
			// NOTE: Print the blocks currently available
			print "<div id=\"index_edit_content\">";
				print " <a href=\"#\" class=\"help\" tabindex=\"0\" onclick=\"expand_layer('help',true); expand_layer('configure', false);\">".$IconHelp."</a> \n";
				print "<b>".$gm_lang["available_blocks"]."</b><br />";
				print "<select id=\"available_select\" name=\"available[]\" size=\"10\" onchange=\"show_description('available_select');\">\n";
				foreach($SortedBlocks as $key => $value) {
					if (!isset($GM_BLOCKS[$key]["type"])) $GM_BLOCKS[$key]["type"]=$command;
					print "<option value=\"$key\">".$SortedBlocks[$key]."</option>\n";
				}
				print "</select>\n";
			print "</div>";
						
			// NOTE: Print the box for showing the advice
			print "<div id=\"index_edit_advice\" class=\"wrap $TEXT_DIRECTION\">";
				print $gm_lang["index_edit_advice"];
			print "</div>";
			
			// NOTE: Print the submit buttons
			print "<div>";
				if ((userIsAdmin($uname))&&($command=='user')) {
					print $gm_lang["use_blocks_for_default"]."<input type=\"checkbox\" name=\"setdefault\" value=\"1\" /><br />\n";
				}
				
				if ($command=='user') {
					print_help_link("block_default_portal", "qm");
				} 
				else {
					print_help_link("block_default_index", "qm");
				}
				print "<input type=\"button\" value=\"".$gm_lang["reset_default_blocks"]."\" onclick=\"window.location='index_edit.php?command=$command&amp;action=reset&amp;name=".preg_replace("/'/", "\'", $name)."';\" />\n";
				print "&nbsp;&nbsp;";
				print_help_link("click_here_help", "qm");
				print "<input type=\"button\" value=\"".$gm_lang["click_here"]."\" onclick=\"select_options(); document.config_setup.submit();\" />\n";
				print "&nbsp;&nbsp;";
				print "<input type =\"button\" value=\"".$gm_lang["cancel"]."\" onclick=\"window.close();\" />";
			print "</div>";
		print "</div>";
		print "</form>\n";
	print "</div>\n";

	// NOTE: Hidden help text for column items
	print "\n\t<div id=\"help\" class=\"tab_page\" style=\"position: absolute; display: none; top: auto; left: auto; z-index: 2; \">\n\t";

	print "<br /><center><input type=\"button\" value=\"".$gm_lang["click_here"]."\" onclick=\"expand_layer('configure', true); expand_layer('help', false);\" /></center><br /><br />\n";
	print_text("block_summaries");

	// end of 2nd tab
	print "</div>\n";
}

print "</body></html>";
?>
