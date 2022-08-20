<?php
/**
 * MyGenMod page allows a logged in user the abilty
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
 * @package Genmod
 * @subpackage Display
 * @version $Id: index.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

 
/**
 * Inclusion of the module extension
*/
if (isset ($_REQUEST['mod'])) {
	require_once 'module.php';
	exit;
}

/**
 * Inclusion of the configuration file
*/
require("config.php");

if (!defined('CONFIGURED')) {
	print "Unable to include the config.php file.  Make sure that . is in your PHP include path in the php.ini file.";
	exit;
}
/**
 * Block definition array
 *
 * The following block definition array defines the
 * blocks that can be used to customize the portals
 * their names and the function to call them
 * "name" is the name of the block in the lists
 * "descr" is the name of a $gm_lang variable to describe this block
 * - eg: "whatever" here means that GM_LANG_whatever describes this block
 * "type" the options are "user" or "gedcom" or undefined
 * - The type determines which lists the block is available in.
 * - Leaving the type undefined allows it to be on both the user and gedcom portal
 * @global array $GM_BLOCKS
 */
$GM_BLOCKS = array();

//-- load all of the blocks
$d = dir("blocks");
while (false !== ($entry = $d->read())) {
	if (strstr($entry, ".")==".php") {
		include_once("blocks/".$entry);
	}
}
$d->close();

if (GedcomConfig::$USE_RTL_FUNCTIONS) {
	//-------> Today's Hebrew Day with Gedcom Month
	$datearray = array();
 	$datearray[0]["day"]   = GetCurrentDay();
 	$datearray[0]["mon"]   = $monthtonum[Str2Lower(trim(GetCurrentMonth()))];
 	$datearray[0]["year"]  = GetCurrentYear();
 	$datearray[0]["month"] = GetCurrentMonth();

    $date   = GregorianToJewishGedcomDate($datearray);
    $hDay   = $date[0]["day"];
    $hMonth = $date[0]["month"];
    $hYear	= $date[0]["year"];

//    $currhDay   = $hDay;
//    $currhMon   = trim($date[0]["month"]);
//    $currhMonth = $monthtonum[Str2Lower($currhMon)];
    $currhYear 	= $hYear;
}

if (!isset($action)) $action="";

//-- make sure that they have user status before they can use this page
//-- otherwise have them login again
if ($gm_user->username == "") {
	if (!empty($command)) {
		if ($command=="user") {
			if (LOGIN_URL == "") header("Location: login.php?help_message=mygenmod_login_help&url=".urlencode("index.php?command=user"));
			else header("Location: ".LOGIN_URL."?help_message=mygenmod_login_help&url=".urlencode("index.php?command=user"));
			exit;
		}
	}
	$command="gedcom";
}

if (empty($command)) $command="user";

if ($gm_user->username != "") {
	//-- add favorites action
	if (($action=="addfav")&&(!empty($gid))) {
		$gid = strtoupper($gid);
		if (!isset($favnote)) $favnote = "";
		$favobject = ConstructObject($gid, "", GedcomConfig::$GEDCOMID);
		if (is_object($favobject)) {
			$favorite = array();
			if (!isset($favtype)) {
				if ($command=="user") $favtype = "user";
				else $favtype = "gedcom";
			}
			$favorite = new Favorite();
			if ($favtype == "gedcom") $favorite->username = "";
			else $favorite->username = $gm_user->username;
			$favorite->gid = $gid;
			$favorite->type = $favobject->type;
			$favorite->file = GedcomConfig::$GEDCOMID;
			$favorite->url = "";
			$favorite->note = $favnote;
			$favorite->title = "";
			$favorite->SetFavorite();
		}
	}
	if (($action=="addfav")&&(!empty($url))) {
		if (!isset($favnote)) $favnote = "";
		if (empty($favtitle)) $favtitle = $url;
		$favorite = array();
		if (!isset($favtype)) {
			if ($command=="user") $favtype = "user";
			else $favtype = "gedcom";
		}
		$favorite = new Favorite();
		if ($favtype == "gedcom") $favorite->username = "";
		else $favorite->username = $gm_user->username;
		$favorite->gid = "";
		$favorite->type = "URL";
		$favorite->file = GedcomConfig::$GEDCOMID;
		$favorite->url = $url;
		$favorite->note = $favnote;
		$favorite->title = $favtitle;
		$favorite->SetFavorite();
	}
	if (($action=="deletefav")&&(isset($fv_id))) {
		FavoritesController::deleteFavorite($fv_id);
	}
	else if ($action=="deletemessage") {
		if (isset($message_id)) {
			if (!is_array($message_id)) MessageController::deleteMessage($message_id);
			else {
				foreach($message_id as $indexval => $mid) {
					if (isset($mid)) MessageController::deleteMessage($mid);
				}
			}
		}
	}
	else if (($action=="deletenews")&&(isset($news_id))) {
		NewsController::DeleteNews($news_id);
	}
}

//-- get the blocks list
if ($command=="user") $ublocks = new Blocks("user", $gm_user->username, $action);
else $ublocks = new Blocks("gedcom", "", $action);

if ($command=="user") {
	$helpindex = "index_myged_help";
	PrintHeader(GM_LANG_mygenmod);
}
else {
	PrintHeader("");
}
?>
<script language="JavaScript" type="text/javascript">
<!--
	function refreshpage() {
		window.location = 'index.php?command=<?php print $command; ?>';
	}
	function addnews(uname) {
		window.open('editnews.php?uname='+uname, '', 'top=50,left=50,width=800,height=500,resizable=1,scrollbars=1');
	}
	function editnews(news_id) {
		window.open('editnews.php?news_id='+news_id, '', 'top=50,left=50,width=800,height=500,resizable=1,scrollbars=1');
	}
	var pastefield;
	function paste_id(value) {
		pastefield.value=value;
	}
//-->
</script>
<?php
//-- start of main content section
if ($command=="user") {
	print "<div id=\"IndexMyGenmodBlock\">";
	print "<div class=\"MyGenmodHeader\">".GM_LANG_mygenmod."</div>";
	print GM_LANG_mygenmod_desc;
	print "</div>\n";
}
$disp_stats = false;
if (count($ublocks->main) != 0) {
	if (count($ublocks->right) != 0) print "\t<div id=\"IndexMainBlocksContainer\">\n";
	else print "\t<div id=\"IndexFullBlocksContainer\">\n";

	foreach($ublocks->main as $bindex=>$block) {
		if ($disp_stats) {
			$time1 = getmicrotime();
			$queries1 = $TOTAL_QUERIES;
		}
		// N.B. Main blocks are not restricted in height!
		if (function_exists($block[0])) eval($block[0]."(false, \$block[1], \"main\", $bindex);");
		if ($disp_stats) {
			$time2 = getmicrotime();
			$time = $time2 - $time1;
			print "Execution time: ";
			printf(" %.3f ", $time);
			print "<br />";
			print "Queries: ".($TOTAL_QUERIES - $queries1)." ";
		}
	}
	print "</div>\n";
}
//-- end of main content section

//-- start of blocks section
if (count($ublocks->right) != 0) {
	if (count($ublocks->main) != 0) print "\t<div id=\"IndexSmallBlocksContainer\">\n";
	else print "\t<div id=\"IndexFullBlocksContainer\">\n";
	foreach($ublocks->right as $bindex => $block) {
		// NOTE: print_random_media(true, $block[1], right, $bindex
		if ($disp_stats) {
			$time1 = getmicrotime();
			$queries1 = $TOTAL_QUERIES;
		}
		if (function_exists($block[0])) eval($block[0]."(true, \$block[1], \"right\", $bindex);");
		if ($disp_stats) {
			$time2 = getmicrotime();
			$time = $time2 - $time1;
			print "Execution time: ";
			printf(" %.3f ", $time);
			print "<br />";
			print "Queries: ".($TOTAL_QUERIES - $queries1)." ";
		}
	}
	print "\t</div>\n";
}
//-- end of blocks section

if (($command=="user") and (!$ublocks->welcome_block_present)) {
	print "<div>";
	PrintHelpLink("mygenmod_customize_help", "qm");
	print "<a href=\"#\" onclick=\"window.open('index_edit.php?name=".$gm_user->username."&amp;command=user', '', 'top=50,left=10,width=1000,height=400,scrollbars=1,resizable=1');\">".GM_LANG_customize_page."</a>\n";
	print "</div>";
}
if (($command=="gedcom") and (!$ublocks->gedcom_block_present)) {
	if ($gm_user->userIsAdmin()) {
		print "<div>";
		print "<a href=\"#\" onclick=\"window.open('index_edit.php?name=GedcomConfig::$GEDCOMID&amp;command=gedcom', '', 'top=50,left=10,width=1000,height=400,scrollbars=1,resizable=1');\">".GM_LANG_customize_gedcom_page."</a>\n";
		print "</div>";
	}
}

PrintFooter();
?>
