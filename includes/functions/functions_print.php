<?php
/**
 * Function for printing
 *
 * Various printing functions used by all scripts and included by the functions.php file.
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
 * @subpackage Display
 * @version $Id: functions_print.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

/**
 * print out standard HTML header
 *
 * This function will print out the HTML, HEAD, and BODY tags and will load in the CSS javascript and
 * other auxiliary files needed to run GM.  It will also include the theme specific header file.
 * This function should be called by every page, except popups, before anything is output.
 *
 * Popup pages, because of their different format, should invoke function print_simple_header() instead.
 *
 * @param string $title	the title to put in the <TITLE></TITLE> header tags
 * @param string $head
 * @param boolean $use_alternate_styles
 */
function PrintHeader($title, $head="",$use_alternate_styles=true) {
	global $bwidth;
	global $BROWSERTYPE;
	global $view, $gm_user;
	global $GEDCOMS;
	global $QUERY_STRING, $action, $changelanguage,$theme_name;
	global $GM_IMAGES, $TEXT_DIRECTION, $ONLOADFUNCTION;
	// globals for the bot 304 mechanism
	global $bot, $_SERVER, $pid, $famid, $rid, $sid;

	// Determine browser type
	if (!isset($_SERVER["HTTP_USER_AGENT"])) $BROWSERTYPE = "other";
	else if (stristr($_SERVER["HTTP_USER_AGENT"], "Opera"))
		$BROWSERTYPE = "opera";
	else if (stristr($_SERVER["HTTP_USER_AGENT"], "Netscape"))
		$BROWSERTYPE = "netscape";
	else if (stristr($_SERVER["HTTP_USER_AGENT"], "Gecko"))
		$BROWSERTYPE = "mozilla";
	else if (stristr($_SERVER["HTTP_USER_AGENT"], "MSIE"))
		$BROWSERTYPE = "msie";
	else
		$BROWSERTYPE = "other";
		
	// This sends back a 304 if the CHAN record contains a date/time before or on the date sent by the bot.
	// NOTE: Pending changes in Genmod are NOT considered.

	$debug = false;
	if ($debug && !empty($bot)) WriteToLog ("Bot: ".$bot."<br />Using URL: ".$_SERVER["SCRIPT_NAME"]."?".GetQueryString()."<br />Visited your site.", "I", "S");
	
	if (!isset($ifModifiedSinceDate) &&
		isset($_SERVER["HTTP_IF_MODIFIED_SINCE"]) &&
		!empty($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {
		$ifModifiedSinceDate = $_SERVER["HTTP_IF_MODIFIED_SINCE"];
	}
	if (isset($ifModifiedSinceDate)) {
		// Tells the requestor that you.ve recognized the conditional GET
		header("X-Requested-If-Modified-Since: ".$ifModifiedSinceDate, TRUE);
		
		switch (basename(SCRIPT_NAME)) {
		case "individual.php":
			$lastchange = GetLastChangeDate("INDI", $pid, GedcomConfig::$GEDCOMID, true);
			break;
		case "family.php":
			$lastchange = GetLastChangeDate("FAM", $famid, GedcomConfig::$GEDCOMID, true);
			break;
		case "source.php":
			$lastchange = GetLastChangeDate("FAM", $sid, GedcomConfig::$GEDCOMID, true);
			break;
		case "repo.php":
			$lastchange = GetLastChangeDate("REPO", $rid, GedcomConfig::$GEDCOMID, true);
			break;
		}
		// If the last change date cannot be retrieved, just continue processing
		if (isset($lastchange) && $lastchange) {
			$gmt_mtime = gmdate("D, d M Y H:i:s", $lastchange) . " GMT";
			$lastModifiedHeader = "Last-Modified: " . $gmt_mtime;
			if ($lastchange <= strtotime($ifModifiedSinceDate)) {
				// send 304
				header("HTTP/1.0 304 Not Modified");
				//header($lastModifiedHeader, TRUE, 304);
				// Log for debugging
				if ($debug) WriteToLog ("Bot: ".$bot."<br />Using URL: ".$_SERVER["SCRIPT_NAME"]."?".GetQueryString()."<br />Cached by bot on: ".$_SERVER['HTTP_IF_MODIFIED_SINCE']."<br />Modified on: ".gmdate("D, d M Y H:i:s", $lastchange)." GMT<br />Sent 304!", "I", "S");
				exit;
			}
			header($lastModifiedHeader, TRUE);
			// Log for debugging
			if ($debug) WriteToLog ("Bot: ".$bot."<br />Using URL: ".$_SERVER["SCRIPT_NAME"]."?".GetQueryString()."<br />Cached by bot on: ".$_SERVER['HTTP_IF_MODIFIED_SINCE']."<br />Modified on: ".gmdate("D, d M Y H:i:s", $lastchange)." GMT<br />Continued processing!", "I", "S");
		}
		else if ($debug) WriteToLog ("Bot: ".$bot."<br />Using URL: ".$_SERVER["SCRIPT_NAME"]."?".GetQueryString()."<br />Cached by bot on: ".$_SERVER['HTTP_IF_MODIFIED_SINCE']."<br />No last change date found.<br />Continued processing!", "I", "S");

	}
	
	// Continue normal processing	
	header("Content-Type: text/html; charset=".GedcomConfig::$CHARACTER_SET);
	header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");

		
	print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
	print "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n\t<head>\n\t\t";
	print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".GedcomConfig::$CHARACTER_SET."\" />\n\t\t";
	if( GedcomConfig::$FAVICON ) {
	   print "<link rel=\"shortcut icon\" href=\"".GedcomConfig::$FAVICON."\" type=\"image/x-icon\" />\n\t\t";
	}
//	if (!isset(GedcomConfig::$META_TITLE)) GedcomConfig::$META_TITLE = "";
	if (isset($GEDCOMS[GedcomConfig::$GEDCOMID]["title"])) $title = $GEDCOMS[GedcomConfig::$GEDCOMID]["title"]." :: ".$title;
	print "<title>".PrintReady(strip_tags($title)." - ".GedcomConfig::$META_TITLE." - Genmod", TRUE)."</title>\n\t";
	 if (!GedcomConfig::$MUST_AUTHENTICATE){
		print "<link href=\"" . SERVER_URL .  "rss.php\" rel=\"alternate\" type=\"application/rss+xml\" title=\"RSS\" />\n\t";
	 }
	 print "<link rel=\"stylesheet\" href=\"".GM_STYLESHEET."\" type=\"text/css\" media=\"all\" />\n\t";
	 if ((!empty($rtl_stylesheet))&&($TEXT_DIRECTION=="rtl")) print "<link rel=\"stylesheet\" href=\"".GM_RTL_STYLESHEET."\" type=\"text/css\" media=\"all\" />\n\t";
	 if ($use_alternate_styles) {
		if ($BROWSERTYPE != "other") {
			print "<link rel=\"stylesheet\" href=\"".GedcomConfig::$THEME_DIR.$BROWSERTYPE.".css\" type=\"text/css\" media=\"all\" />\n\t";
		}
	 }
	 if ($view == "preview") print "<link rel=\"stylesheet\" href=\"".GM_PRINT_STYLESHEET."\" type=\"text/css\" media=\"print\" />\n\t";
	 if ($BROWSERTYPE == "msie") print "<style type=\"text/css\">\nFORM { margin-top: 0px; margin-bottom: 0px; }\n</style>\n";
	 print "<!--  Genmod v".GM_VERSION." -->\n";
	 if (isset($changelanguage)) {
		  $terms = preg_split("/[&?]/", $QUERY_STRING);
		  $vars = "";
		  for ($i=0; $i<count($terms); $i++) {
			   if ((!empty($terms[$i]))&&(strstr($terms[$i], "changelanguage")===false)&&(strpos($terms[$i], "NEWLANGUAGE")===false)) {
					$vars .= $terms[$i]."&";
			   }
		  }
		  $query_string = $vars;
	 }
	 else $query_string = $QUERY_STRING;
	// Viewport should be included also in preview mode
	// print "<meta name=\"viewport\" content=\"width=1020, initial-scale=1.01\" />\n";
	 print "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.1\" />\n";
	 if ($view!="preview") {
		 $old_META_AUTHOR = GedcomConfig::$META_AUTHOR;
		 $old_META_PUBLISHER = GedcomConfig::$META_PUBLISHER;
		 $old_META_COPYRIGHT = GedcomConfig::$META_COPYRIGHT;
		 $old_META_DESCRIPTION = GedcomConfig::$META_DESCRIPTION;
		 $old_META_PAGE_TOPIC = GedcomConfig::$META_PAGE_TOPIC;
		  $cuser =& User::GetInstance(GedcomConfig::$CONTACT_EMAIL);
		  if (!empty($cuser->username)) {
			  if (empty(GedcomConfig::$META_AUTHOR)) GedcomConfig::$META_AUTHOR = $cuser->firstname." ".$cuser->lastname;
			  if (empty(GedcomConfig::$META_PUBLISHER)) GedcomConfig::$META_PUBLISHER = $cuser->firstname." ".$cuser->lastname;
			  if (empty(GedcomConfig::$META_COPYRIGHT)) GedcomConfig::$META_COPYRIGHT = $cuser->firstname." ".$cuser->lastname;
		  }
		  if (!empty(GedcomConfig::$META_AUTHOR)) print "<meta name=\"author\" content=\"".GedcomConfig::$META_AUTHOR."\" />\n";
		  if (!empty(GedcomConfig::$META_PUBLISHER)) print "<meta name=\"publisher\" content=\"".GedcomConfig::$META_PUBLISHER."\" />\n";
		  if (!empty(GedcomConfig::$META_COPYRIGHT)) print "<meta name=\"copyright\" content=\"".GedcomConfig::$META_COPYRIGHT."\" />\n";
		  print "<meta name=\"keywords\" content=\"".GedcomConfig::$META_KEYWORDS;
		  $surnames = NameFunctions::GetCommonSurnamesIndex(GedcomConfig::$GEDCOMID);
		  foreach($surnames as $surname=>$count) if (!empty($surname)) print ", $surname";
		  print "\" />\n";
		  if ((empty(GedcomConfig::$META_PAGE_TOPIC))&&(!empty($GEDCOMS[GedcomConfig::$GEDCOMID]["title"]))) GedcomConfig::$META_PAGE_TOPIC = $GEDCOMS[GedcomConfig::$GEDCOMID]["title"];
		//LERMAN - make meta description unique, like the title
		  if (empty(GedcomConfig::$META_DESCRIPTION)) GedcomConfig::$META_DESCRIPTION = PrintReady(strip_tags($title)." - ".GedcomConfig::$META_TITLE." - Genmod", TRUE);
		  //if ((empty($META_DESCRIPTION))&&(!empty($GEDCOMS[GedcomConfig::$GEDCOMID]["title"]))) $META_DESCRIPTION = $GEDCOMS[GedcomConfig::$GEDCOMID]["title"];
		  if (!empty(GedcomConfig::$META_DESCRIPTION)) print "<meta name=\"description\" content=\"".preg_replace("/\"/", "", GedcomConfig::$META_DESCRIPTION)."\" />\n";
		  if (!empty(GedcomConfig::$META_PAGE_TOPIC)) print "<meta name=\"page-topic\" content=\"".preg_replace("/\"/", "", GedcomConfig::$META_PAGE_TOPIC)."\" />\n";
	 	  if (!empty(GedcomConfig::$META_AUDIENCE)) print "<meta name=\"audience\" content=\"".GedcomConfig::$META_AUDIENCE."\" />\n";
	 	  if (!empty(GedcomConfig::$META_PAGE_TYPE)) print "<meta name=\"page-type\" content=\"".GedcomConfig::$META_PAGE_TYPE."\" />\n";
	 	  print ParseRobotsTXT();
	 	  if (!empty(GedcomConfig::$META_REVISIT)) print "<meta name=\"revisit-after\" content=\"".GedcomConfig::$META_REVISIT."\" />\n";
		  print "<meta name=\"generator\" content=\"Genmod v".GM_VERSION." - https://www.sourceforge.net/projects/genmod\" />\n";
		 GedcomConfig::$META_AUTHOR = $old_META_AUTHOR;
		 GedcomConfig::$META_PUBLISHER = $old_META_PUBLISHER;
		 GedcomConfig::$META_COPYRIGHT = $old_META_COPYRIGHT;
		 GedcomConfig::$META_DESCRIPTION = $old_META_DESCRIPTION;
		 GedcomConfig::$META_PAGE_TOPIC = $old_META_PAGE_TOPIC;
	}
	else {
?>
<script language="JavaScript" type="text/javascript">
<!--
function hidePrint() {
	 var printlink = document.getElementById('printlink');
	 var printlinktwo = document.getElementById('printlinktwo');
	 if (printlink) {
		  printlink.style.display='none';
		  printlinktwo.style.display='none';
	 }
}
function showBack() {
	 var backlink = document.getElementById('backlink');
	 if (backlink) {
		  backlink.style.display='block';
	 }
}
//-->
</script>
<?php
}
?>
<script language="JavaScript" type="text/javascript">
<!--
	 <?php print "query = \"".$query_string."\";\n"; ?>
	 <?php print "textDirection = \"".$TEXT_DIRECTION."\";\n"; ?>
	 <?php print "browserType = \"".$BROWSERTYPE."\";\n"; ?>
	 <?php print "themeName = \"".strtolower($theme_name)."\";\n"; ?>
	 <?php print "SCRIPT_NAME = \"".SCRIPT_NAME."\";\n"; ?>
	 /* keep the session id when opening new windows */
	 <?php print "sessionid = \"".session_id()."\";\n"; ?>
	 <?php print "sessionname = \"".session_name()."\";\n"; ?>
	 plusminus = new Array();
	 plusminus[0] = new Image();
	 plusminus[0].src = "<?php print GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]; ?>";
	 plusminus[1] = new Image();
	 plusminus[1].src = "<?php print GM_IMAGE_DIR."/".$GM_IMAGES["minus"]["other"]; ?>";
	 zoominout = new Array();
	 zoominout[0] = new Image();
	 zoominout[0].src = "<?php print GM_IMAGE_DIR."/".$GM_IMAGES["zoomin"]["other"]; ?>";
	 zoominout[1] = new Image();
	 zoominout[1].src = "<?php print GM_IMAGE_DIR."/".$GM_IMAGES["zoomout"]["other"]; ?>";
	 arrows = new Array();
	 arrows[0] = new Image();
	 arrows[0].src = "<?php print GM_IMAGE_DIR."/".$GM_IMAGES["larrow2"]["other"]; ?>";
	 arrows[1] = new Image();
	 arrows[1].src = "<?php print GM_IMAGE_DIR."/".$GM_IMAGES["rarrow2"]["other"]; ?>";
	 arrows[2] = new Image();
	 arrows[2].src = "<?php print GM_IMAGE_DIR."/".$GM_IMAGES["uarrow2"]["other"]; ?>";
	 arrows[3] = new Image();
	 arrows[3].src = "<?php print GM_IMAGE_DIR."/".$GM_IMAGES["darrow2"]["other"]; ?>";

function message(username, method, url, subject) {
	 if ((!url)||(url=="")) url='<?php print urlencode(basename(SCRIPT_NAME)."?".$QUERY_STRING); ?>';
	 if ((!subject)||(subject=="")) subject= '';
	 window.open('message.php?to='+username+'&method='+method+'&url='+url+'&subject='+subject+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=600,height=500,resizable=1,scrollbars=1');
	 return false;
}
var whichhelp = 'help_<?php print basename(SCRIPT_NAME)."&amp;action=".$action; ?>';
//-->
</script>
<script src="gm.js" language="JavaScript" type="text/javascript"></script>
<?php if ($gm_user->canedit) { ?><script src="genmod_edit.js" language="JavaScript" type="text/javascript"></script><?php } ?>
<script src="gmrpc.js" language="JavaScript" type="text/javascript"></script>
<?php if (USE_GREYBOX) { ?>
<script type="text/javascript">
<!--
    var GB_ROOT_DIR = "<?php print SERVER_URL."modules/greybox/";?>";
//-->
</script>
<link href="modules/greybox/gb_styles.css" rel="stylesheet" type="text/css" />
<?php }
	print $head;
	print "</head>\n\t<body";
	if ($view=="preview") print " onbeforeprint=\"hidePrint();\" onafterprint=\"showBack();\"";
	if ($TEXT_DIRECTION=="rtl" || !empty($ONLOADFUNCTION)) {
		print " onload=\"$ONLOADFUNCTION";
	 	if ($TEXT_DIRECTION=="rtl") print " maxscroll = document.documentElement.scrollLeft;";
	 	print " loadHandler();";
	 	print "\"";
 	}
 	else {
		echo ' onload="loadHandler();';
		if ($view !== "preview") echo 'init();"';
		else echo '"';
	}
	print ">\n\t";
	// Start the container
	// print "<div id=\"container\">";
	print "<!-- begin header section -->\n";
	include("includes/values/include_top.php");
	// Genmod container
	print "<div id=\"GenmodContainer\">";
	print "<div id=\"dummy\" style=\"display: none;\"></div>";
	 
	if ($view!="preview") include(GM_MENUBAR);
	else include(GM_PRINT_HEADERFILE);
	PrintAccessKeyHeaders();
	print "<!-- end header section -->\n";
	print "<!-- begin content section -->\n";
	
	if (USE_GREYBOX) { ?>
		<script type="text/javascript" src="modules/greybox/AJS.js"></script>
		<script type="text/javascript" src="modules/greybox/AJS_fx.js"></script>
		<script type="text/javascript" src="modules/greybox/gb_scripts.js"></script>
	<?php
 	}
}

/**
 * print simple HTML header
 *
 * This function will print out the HTML, HEAD, and BODY tags and will load in the CSS javascript and
 * other auxiliary files needed to run GM.  It does not include any theme specific header files.
 * This function should be called by every page before anything is output on popup pages.
 *
 * @param string $title	the title to put in the <TITLE></TITLE> header tags
 * @param string $head
 * @param boolean $use_alternate_styles
 */
function PrintSimpleHeader($title) {
	 global $view, $gm_user;
	 global $QUERY_STRING, $action, $query, $changelanguage;
	 global $TEXT_DIRECTION, $GEDCOMS, $GM_IMAGES;
	 
	 header("Content-Type: text/html; charset=".GedcomConfig::$CHARACTER_SET);
	 print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
	 print "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n\t<head>\n\t\t";
	 print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".GedcomConfig::$CHARACTER_SET."\" />\n\t\t";
	if( GedcomConfig::$FAVICON ) {
	   print "<link rel=\"shortcut icon\" href=\"".GedcomConfig::$FAVICON."\" type=\"image/x-icon\" />\n\t\t";
	}
//	if (!isset(GedcomConfig::$META_TITLE)) GedcomConfig::$META_TITLE = "";
	print "<title>".PrintReady(strip_tags($title))." - ".GedcomConfig::$META_TITLE." - Genmod</title>\n\t<link rel=\"stylesheet\" href=\"".GM_STYLESHEET."\" type=\"text/css\" />\n\t";
	if ((!empty($rtl_stylesheet))&&($TEXT_DIRECTION=="rtl")) print "<link rel=\"stylesheet\" href=\"".GM_RTL_STYLESHEET."\" type=\"text/css\" media=\"all\" />\n\t";
	$old_META_AUTHOR = GedcomConfig::$META_AUTHOR;
	$old_META_PUBLISHER = GedcomConfig::$META_PUBLISHER;
	$old_META_COPYRIGHT = GedcomConfig::$META_COPYRIGHT;
	$old_META_DESCRIPTION = GedcomConfig::$META_DESCRIPTION;
	$old_META_PAGE_TOPIC = GedcomConfig::$META_PAGE_TOPIC;
	$cuser =& User::GetInstance(GedcomConfig::$CONTACT_EMAIL);
	if (!empty($cuser->username)) {
		if (empty(GedcomConfig::$META_AUTHOR)) GedcomConfig::$META_AUTHOR = $cuser->firstname." ".$cuser->lastname;
		if (empty(GedcomConfig::$META_PUBLISHER)) GedcomConfig::$META_PUBLISHER = $cuser->firstname." ".$cuser->lastname;
		if (empty(GedcomConfig::$META_COPYRIGHT)) GedcomConfig::$META_COPYRIGHT = $cuser->firstname." ".$cuser->lastname;
	}
	if (!empty(GedcomConfig::$META_AUTHOR)) print "<meta name=\"author\" content=\"".GedcomConfig::$META_AUTHOR."\" />\n";
	if (!empty(GedcomConfig::$META_PUBLISHER)) print "<meta name=\"publisher\" content=\"".GedcomConfig::$META_PUBLISHER."\" />\n";
	if (!empty(GedcomConfig::$META_COPYRIGHT)) print "<meta name=\"copyright\" content=\"".GedcomConfig::$META_COPYRIGHT."\" />\n";
	print "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.1\" />\n";
	print "<meta name=\"keywords\" content=\"".GedcomConfig::$META_KEYWORDS;
	$surnames = NameFunctions::GetCommonSurnamesIndex(GedcomConfig::$GEDCOMID);
	foreach($surnames as $surname=>$count) print ", $surname";
	print "\" />\n";
	if ((empty(GedcomConfig::$META_PAGE_TOPIC))&&(!empty($GEDCOMS[GedcomConfig::$GEDCOMID]["title"]))) GedcomConfig::$META_PAGE_TOPIC = $GEDCOMS[GedcomConfig::$GEDCOMID]["title"];
	//LERMAN - make meta description unique, like the title
	if (empty(GedcomConfig::$META_DESCRIPTION)) GedcomConfig::$META_DESCRIPTION = PrintReady(strip_tags($title)." - ".GedcomConfig::$META_TITLE." - Genmod", TRUE);
	if (!empty(GedcomConfig::$META_DESCRIPTION)) print "<meta name=\"description\" content=\"".preg_replace("/\"/", "", GedcomConfig::$META_DESCRIPTION)."\" />\n";
	if (!empty(GedcomConfig::$META_PAGE_TOPIC)) print "<meta name=\"page-topic\" content=\"".preg_replace("/\"/", "", GedcomConfig::$META_PAGE_TOPIC)."\" />\n";
	if (!empty(GedcomConfig::$META_AUDIENCE)) print "<meta name=\"audience\" content=\"".GedcomConfig::$META_AUDIENCE."\" />\n";
	if (!empty(GedcomConfig::$META_PAGE_TYPE)) print "<meta name=\"page-type\" content=\"".GedcomConfig::$META_PAGE_TYPE."\" />\n";
	print ParseRobotsTXT();
	if (!empty(GedcomConfig::$META_REVISIT)) print "<meta name=\"revisit-after\" content=\"".GedcomConfig::$META_REVISIT."\" />\n";
	print "<meta name=\"generator\" content=\"Genmod v".GM_VERSION." - https://www.sourceforge.net/projects/genmod\" />\n";
	GedcomConfig::$META_AUTHOR = $old_META_AUTHOR;
	GedcomConfig::$META_PUBLISHER = $old_META_PUBLISHER;
	GedcomConfig::$META_COPYRIGHT = $old_META_COPYRIGHT;
	GedcomConfig::$META_DESCRIPTION = $old_META_DESCRIPTION;
	GedcomConfig::$META_PAGE_TOPIC = $old_META_PAGE_TOPIC;
	?>
	 <script language="JavaScript" type="text/javascript">
	 <!--
	 /* set these vars so that the session can be passed to new windows */
	 <?php print "sessionid = \"".session_id()."\";\n"; ?>
	 <?php print "sessionname = \"".session_name()."\";\n"; ?>
	 plusminus = new Array();
	 plusminus[0] = new Image();
	 plusminus[0].src = "<?php print GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]; ?>";
	 plusminus[1] = new Image();
	 plusminus[1].src = "<?php print GM_IMAGE_DIR."/".$GM_IMAGES["minus"]["other"]; ?>";
	 zoominout = new Array();
	 zoominout[0] = new Image();
	 zoominout[0].src = "<?php print GM_IMAGE_DIR."/".$GM_IMAGES["zoomin"]["other"]; ?>";
	 zoominout[1] = new Image();
	 zoominout[1].src = "<?php print GM_IMAGE_DIR."/".$GM_IMAGES["zoomout"]["other"]; ?>";

	var helpWin;
	function helpPopup(which) {
		if ((!helpWin)||(helpWin.closed)) helpWin = window.open('help_text.php?help='+which,'','left=50,top=50,width=500,height=320,resizable=1,scrollbars=1');
		else helpWin.location = 'help_text.php?help='+which;
		return false;
	}
	function message(username, method, url, subject) {
		if ((!url)||(url=="")) url='<?php print urlencode(basename(SCRIPT_NAME)."?".$QUERY_STRING); ?>';
		if ((!subject)||(subject=="")) subject= '';
		window.open('message.php?to='+username+'&method='+method+'&url='+url+'&subject='+subject+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=600,height=500,resizable=1,scrollbars=1');
		return false;
	}
	//-->
	</script>
	<script src="gm.js" language="JavaScript" type="text/javascript"></script>
	<?php if ($gm_user->canedit) { ?><script src="genmod_edit.js" language="JavaScript" type="text/javascript"></script><?php } ?>
	<script src="gmrpc.js" language="JavaScript" type="text/javascript"></script>
	<?php if (USE_GREYBOX) { ?>
		<script type="text/javascript">
		<!--
	    	var GB_ROOT_DIR = "<?php print SERVER_URL."modules/greybox/";?>";
	    //-->
		</script>
		<link href="modules/greybox/gb_styles.css" rel="stylesheet" type="text/css" />
	<?php }
	print "</head>\n\t<body";
	print " onload=\"loadHandler();\">\n\t";
		// Genmod container
	print "<div id=\"GenmodContainer\">";
	print "<div id=\"dummy\" style=\"display: none;\"></div>";
	if (USE_GREYBOX) { ?>
		<script type="text/javascript" src="modules/greybox/AJS.js"></script>
		<script type="text/javascript" src="modules/greybox/AJS_fx.js"></script>
		<script type="text/javascript" src="modules/greybox/gb_scripts.js"></script>
	 <?php
 	}
}

// -- print the html to close the page
function PrintFooter() {
	global $without_close, $view, $buildindex;
	global $QUERY_STRING, $printlink;
	global $theme_name, $GM_IMAGES, $TEXT_DIRECTION, $footer_count;
	
	if (!isset($footer_count)) $footer_count = 1;
	else $footer_count++;
	print "<!-- begin footer -->\n";
	print "<div id=\"FooterSectionDummy\"></div>";
	print "<div id=\"FooterSection\" class=\"$TEXT_DIRECTION\">";
	$QUERY_STRING = preg_replace("/&/", "&", $QUERY_STRING);
	if ($view != "preview") include(GM_FOOTERFILE);
	else {
		include(GM_PRINT_FOOTERFILE);
		print "\n\t<div class=\"FooterPreviewLinkContainer\"><br />";
		$backlink = basename(SCRIPT_NAME)."?".GetQueryString();
		if (!$printlink) {
			print "\n\t<br /><a id=\"printlink\" href=\"#\" onclick=\"print(); return false;\">".GM_LANG_print."</a><br />";
			print "\n\t <a id=\"printlinktwo\"	  href=\"#\" onclick=\"window.location='".$backlink."'; return false;\">".GM_LANG_cancel_preview."</a><br />";
		}
		$printlink = true;
		print "\n\t<a id=\"backlink\" style=\"display: none;\" href=\"#\" onclick=\"window.location='".$backlink."'; return false;\">".GM_LANG_cancel_preview."</a><br />";
		print "</div>";
	}
	// print "<!-- close container -->\n";
	// print "</div>";
	if (DebugCollector::$show) DebugCollector::PrintDebug();
	include("includes/values/include_bottom.php");	
	print "</div> <!-- close div id=\"FooterSection\" -->\n";
	print "</div> <!-- close div id=\"GenmodContainer\" -->\n";
	?>
	<script type="text/javascript">
	<!--
	document.getElementById('FooterSectionDummy').style.paddingBottom = (document.getElementById('FooterSection').offsetHeight);
	//-->
	</script>
	<?php
	print "\n\t</body>\n</html>";
	//-- We write the session data and close it. Fix for intermittend logoff.
	session_write_close();

}

// -- print the html to close the page
function PrintSimpleFooter() {
	global $start_time, $buildindex;
	global $CONFIG_PARMS;
	global $QUERY_STRING;
	
	print "<!-- begin footer -->\n";
	print "<div id=\"FooterSectionDummy\"></div>";
	print "\n\t<div id=\"FooterSection\">";
		PrintContactLinks();
		print "<div class=\"FooterExecutionStats\">";
			if (GedcomConfig::$SHOW_STATS) PrintExecutionStats();
			print "<br />Running <a href=\"https://www.sourceforge.net/projects/genmod/\" target=\"_blank\">Genmod";
			if (count($CONFIG_PARMS) >1) print " Enterprise";
			print MediaFS::GetStorageType();
			print "</a> - Version ".GM_VERSION." ".GM_VERSION_RELEASE;
			if (DebugCollector::$show) DebugCollector::PrintDebug();
		print "</div>"; // close FooterExecutionStats
	print "</div>"; // Close FooterContainer
	print "</div>"; // Close Genmod container
	?>
	<script type="text/javascript">
	<!--
	document.getElementById('FooterSectionDummy').style.paddingBottom = (document.getElementById('FooterSection').clientHeight);
	//-->
	</script>
	<?php
	print "\n\t</body>\n</html>";
	//-- We write the session data and close it. Fix for intermittend logoff.
	session_write_close();

}

if( !function_exists('memory_get_usage') ) {
	function memory_get_usage() {
		//If its Windows
       	//Tested on Win XP Pro SP2. Should work on Win 2003 Server too
       	//Doesn't work for 2000
       	//If you need it to work for 2000 look at http://us2.php.net/manual/en/function.memory-get-usage.php#54642
       	if ( substr(PHP_OS,0,3) == 'WIN') {
           	$output = array();
            $ret = @exec( 'tasklist /FI "PID eq ' . getmypid() . '" /FO LIST', $output );
            if(!$ret) return 0;
			if (isset($output[5])) return preg_replace( '/[\D]/', '', $output[5] ) * 1024;
			else return 0;
		}
		else {
			//We now assume the OS is UNIX
			//Tested on Mac OS X 10.4.6, Linux Red Hat Enterprise 4 and Solaris 10
			//This should work on most UNIX systems
			$pid = getmypid();
			if (PHP_OS == 'SunOS') {
				exec("ps -eopmem,rss,pid | grep $pid | awk -F' ' '{print $2;}'", $output);
			}
			else {
				exec("ps -eo%mem,rss,pid | grep $pid | awk -F' ' '{print $2;}'", $output);
			}
			//rss is given in 1024 byte units
			return $output[0] * 1024;
		}
   	}
}

/**
 * Prints exection statistics
 *
 * Prints out the execution time and the databse queries
 *
 * @author	Genmod Development Team
 */
function PrintExecutionStats() {
	global $start_time, $TOTAL_QUERIES, $PRIVACY_CHECKS, $QUERY_EXECTIME;
	$end_time = GetMicrotime();
	$exectime = $end_time - $start_time;
	print GM_LANG_exec_time;
	printf(" %.3f ".GM_LANG_sec, $exectime);
	print "  ".GM_LANG_total_queries." $TOTAL_QUERIES.";
	print " ".GM_LANG_query_exec_time;
	printf(" %.3f ".GM_LANG_sec, $QUERY_EXECTIME);
	if (!$PRIVACY_CHECKS) $PRIVACY_CHECKS=0;
	print " ".GM_LANG_total_privacy_checks." $PRIVACY_CHECKS.";
	if (function_exists("memory_get_usage")) {
		$mu = memory_get_usage(true);
		if ($mu) {
			print " ".GM_LANG_total_memory_usage." ";
			print GetFileSize($mu);
			print ".";
		}
	}
}

/**
 * print links for genealogy and technical contacts
 *
 * this function will print appropriate links based on the preferred contact methods for the genealogy
 * contact user and the technical support contact user
 */
function PrintContactLinks($style=0) {
	global $gm_user;
	
	if (GedcomConfig::$SUPPORT_METHOD=="none" && GedcomConfig::$CONTACT_METHOD=="none") return array();
	if (GedcomConfig::$SUPPORT_METHOD=="none") GedcomConfig::$WEBMASTER_EMAIL = GedcomConfig::$CONTACT_EMAIL;
	if (GedcomConfig::$CONTACT_METHOD=="none") GedcomConfig::$CONTACT_EMAIL = GedcomConfig::$WEBMASTER_EMAIL;
	switch($style) {
		case 0:
			print "<div class=\"FooterContactLinks\">\n";
			//--only display one message if the contact users are the same
			if (GedcomConfig::$CONTACT_EMAIL == GedcomConfig::$WEBMASTER_EMAIL) {
				$user =& User::GetInstance(GedcomConfig::$WEBMASTER_EMAIL);
				if (!$user->is_empty && GedcomConfig::$SUPPORT_METHOD != "mailto") {
					print GM_LANG_for_all_contact." <a href=\"#\" accesskey=\"". GM_LANG_accesskey_contact ."\" onclick=\"message('".GedcomConfig::$WEBMASTER_EMAIL."', '".GedcomConfig::$SUPPORT_METHOD."'); return false;\">".$user->firstname." ".$user->lastname."</a>\n";
				}
				else {
					print GM_LANG_for_support." <a href=\"mailto:";
					if (!empty($gm_user->username)) print $user->email."\" accesskey=\"". GM_LANG_accesskey_contact ."\">".$gm_user->firstname." ".$gm_user->lastname."</a>\n";
					else print GedcomConfig::$WEBMASTER_EMAIL."\">".GedcomConfig::$WEBMASTER_EMAIL."</a>\n";
				}
			}
			//-- display two messages if the contact users are different
			else {
				  $user =& User::GetInstance(GedcomConfig::$CONTACT_EMAIL);
				  if (!$user->is_empty && GedcomConfig::$CONTACT_METHOD!="mailto") {
					  print GM_LANG_for_contact." <a href=\"#\" accesskey=\"". GM_LANG_accesskey_contact ."\" onclick=\"message('".GedcomConfig::$CONTACT_EMAIL."', '".GedcomConfig::$CONTACT_METHOD."'); return false;\">".$gm_user->firstname." ".$gm_user->lastname."</a><br />\n";
				  }
				  else {
					   print GM_LANG_for_contact." <a href=\"mailto:";
					   if (!empty($gm_user->username)) print $user->email."\" accesskey=\"". GM_LANG_accesskey_contact ."\">".$gm_user->firstname." ".$gm_user->lastname."</a><br />\n";
					   else print GedcomConfig::$CONTACT_EMAIL."\">".GedcomConfig::$CONTACT_EMAIL."</a><br />\n";
				  }
				  $user =& User::GetInstance(GedcomConfig::$WEBMASTER_EMAIL);
				  if ($user && GedcomConfig::$SUPPORT_METHOD != "mailto") {
					  print GM_LANG_for_support." <a href=\"#\" onclick=\"message('".GedcomConfig::$WEBMASTER_EMAIL."', '".GedcomConfig::$SUPPORT_METHOD."'); return false;\">".$gm_user->firstname." ".$gm_user->lastname."</a>\n";
				  }
				  else {
					   print GM_LANG_for_support." <a href=\"mailto:";
					   if (!empty($gm_user->username)) print $gm_user->email."\">".$gm_user->firstname." ".$gm_user->lastname."</a>\n";
					   else print GedcomConfig::$WEBMASTER_EMAIL."\">".GedcomConfig::$WEBMASTER_EMAIL."</a>\n";
				  }
			}
			print "</div>\n";
			break;
		case 1:
			$menuitems = array();
			if (GedcomConfig::$CONTACT_EMAIL == GedcomConfig::$WEBMASTER_EMAIL) {
				$user =& User::GetInstance(GedcomConfig::$WEBMASTER_EMAIL);
				$submenu = array();
				if (!$user->is_empty && GedcomConfig::$SUPPORT_METHOD != "mailto") {
					$submenu["label"] = GM_LANG_support_contact." ".$gm_user->firstname." ".$gm_user->lastname;
					$submenu["link"] = "message('".GedcomConfig::$WEBMASTER_EMAIL."', '".GedcomConfig::$SUPPORT_METHOD."');";
				}
				else {
					$submenu["label"] = GM_LANG_support_contact." ";
					$submenu["link"] = "mailto:";
					if (!empty($gm_user->username)) {
						$submenu["link"] .= $gm_user->email;
						$submenu["label"] .= $gm_user->firstname." ".$gm_user->lastname;
					}
					else {
						$submenu["link"] .= GedcomConfig::$WEBMASTER_EMAIL;
						$submenu["label"] .= GedcomConfig::$WEBMASTER_EMAIL;
					}
				}
	            $submenu["label"] = GM_LANG_support_contact;
	            $submenu["labelpos"] = "right";
	            $submenu["class"] = "submenuitem";
	            $submenu["hoverclass"] = "submenuitem_hover";
	            $menuitems[] = $submenu;
			}
			else {
				$user =& User::GetInstance(GedcomConfig::$CONTACT_EMAIL);
				$submenu = array();
				if (!$user->is_empty && GedcomConfig::$CONTACT_METHOD!="mailto") {
					$submenu["label"] = GM_LANG_genealogy_contact." ".$gm_user->firstname." ".$gm_user->lastname;
					$submenu["link"] = "message('".GedcomConfig::$CONTACT_EMAIL."', '".GedcomConfig::$CONTACT_METHOD."');";
				}
				else {
					$submenu["label"] = GM_LANG_genealogy_contact." ";
					$submenu["link"] = "mailto:";
					if (!empty($gm_user->username)) {
						$submenu["link"] .= $gm_user->email;
						$submenu["label"] .= $gm_user->firstname." ".$gm_user->lastname;
					}
					else {
						$submenu["link"] .= GedcomConfig::$CONTACT_EMAIL;
						$submenu["label"] .= GedcomConfig::$CONTACT_EMAIL;
					}
				}
	            $submenu["labelpos"] = "right";
	            $submenu["class"] = "submenuitem";
	            $submenu["hoverclass"] = "submenuitem_hover";
	            $menuitems[] = $submenu;
	            $submenu = array();
				if ($user && GedcomConfig::$SUPPORT_METHOD != "mailto") {
					$submenu["label"] = GM_LANG_support_contact." ".$gm_user->firstname." ".$gm_user->lastname;
					$submenu["link"] = "message('".GedcomConfig::$WEBMASTER_EMAIL."', '".GedcomConfig::$SUPPORT_METHOD."');";
				}
				else {
					$submenu["label"] = GM_LANG_support_contact." ";
					$submenu["link"] = "mailto:";
					if (!empty($gm_user->username)) {
						$submenu["link"] .= $gm_user->email;
						$submenu["label"] .= $gm_user->firstname." ".$gm_user->lastname;
					}
					else {
						$submenu["link"] .= GedcomConfig::$WEBMASTER_EMAIL;
						$submenu["label"] .= GedcomConfig::$WEBMASTER_EMAIL;
					}
				}
	            $submenu["labelpos"] = "right";
	            $submenu["class"] = "submenuitem";
	            $submenu["hoverclass"] = "submenuitem_hover";
	            $menuitems[] = $submenu;
	        }
            return $menuitems;
			break;
	}
}

/* Function to print popup help boxes
 * @param string $help		The variable that needs to be processed.
 * @param int $helpText		The text to be printed if the theme does not use images for help links
 * @param int $show_desc		The text to be shown as JavaScript description
 * @param boolean $use_print_text	If the text needs to be printed with the PrintText() function
 * @param boolean $output	return the text instead of printing it
 */
function PrintHelpLink($help, $helpText, $show_desc="", $use_print_text=false, $return=false) {
	global $view, $GM_IMAGES, $gm_user;
	
	if (GM_USE_HELPIMG) $sentense = "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["help"]["small"]."\" class=\"HelpImage\" width=\"15\" height=\"15\" alt=\"\" />";
	else $sentense = constant("GM_LANG_".$helpText);
	$output = "";
	if (($view!="preview")&&($_SESSION["show_context_help"])){
		if ($helpText=="qm_ah"){
			if ($gm_user->userIsAdmin()){
				 $output .= " <a class=\"Error HelpImage\" tabindex=\"0\" href=\"javascript:";
				 if ($show_desc == "") $output .= $help;
				 else if ($use_print_text) $output .= PrintText($show_desc, 0, 1);
				 else if (stristr(constant("GM_LANG_".$show_desc), "\"")) $output .= preg_replace('/\"/','\'', constant("GM_LANG_".$show_desc));
				 else  $output .= strip_tags(constant("GM_LANG_".$show_desc));
				 $output .= "\" onclick=\"helpPopup('$help'); return false;\">".$sentense."</a> \n";
			}
		}
		else {
			$output .= " <a class=\"HelpImage\" tabindex=\"0\" href=\"javascript: ";
			if ($show_desc == "") $output .= $help;
			else if ($use_print_text) $output .= PrintText($show_desc, 0, 1);
			else if (stristr(constant("GM_LANG_".$show_desc), "\"")) $output .= preg_replace('/\"/','\'',constant("GM_LANG_".$show_desc));
			else  $output .= strip_tags(constant("GM_LANG_".$show_desc));
			$output .= "\" onclick=\"helpPopup('$help'); return false;\">".$sentense."</a> \n";
		}
	}
	if (!$return) print $output;
	return $output;
}

/**
 * print a language variable
 *
 * It accepts any kind of language variable. This can be a single variable but also
 * a variable with included variables that needs to be converted.
 * print_text, which used to be called print_help_text, now takes 3 parameters
 *		of which only the 1st is mandatory
 * The first parameter is the variable that needs to be processed.  At nesting level zero,
 *		this is the name of a $gm_lang array entry.  "whatever" refers to
 *		GM_LANG_whatever.  At nesting levels greater than zero, this is the name of
 *		any global variable, but *without* the $ in front.  For example, VERSION or
 *		gm_lang["whatever or factarray["rowname"].
 * The second parameter is $level for the nested vars in a sentence.  This indicates
 *		that the function has been called recursively.
 * The third parameter $noprint is for returning the text instead of printing it
 *		This parameter, when set to 2 means, in addition to NOT printing the result,
 *		the input string $help is text that needs to be interpreted instead of being
 *		the name of a $gm_lang array entry.  This lets you use this function to work
 *		on something other than $gm_lang array entries, but coded according to the
 *		same rules.
 * When we want it to return text we need to code:
 * PrintText($mytext, 0, 1);
 * @param string $help		The variable that needs to be processed.
 * @param int $level		The position of the embedded variable
 * @param int $noprint		The switch if the text needs to be printed or returned
 */
function PrintText($help, $level=0, $noprint=0){
	 global $GEDCOM_TITLE, $LANGUAGE;
	 global $GUESS_URL, $UpArrow;
	 global $repeat, $thumbnail, $xref, $pid, $LANGUAGE;
	
	 if (!isset($_SESSION["DEBUG_LANG"])) $DEBUG_LANG = "no";
	 else $DEBUG_LANG = $_SESSION["DEBUG_LANG"];
	 if ($DEBUG_LANG == "yes") print "[LANG_DEBUG] Variable called: ".$help." at level: ".$level."<br /><br />";
	
	 $sentence = "";
	 if ($level>0) {
		 // check langvar
		 if (defined("GM_LANG_".$help)) $sentence = constant("GM_LANG_".$help);
		 // check fact constant
		 else if (defined("GM_FACT_".$help)) $sentence = constant("GM_FACT_".$help);
		 // check constant
		 else if (defined($help)) $sentence = constant($help);
		 // check nested var
		 else if (isset($$help)) $sentence = $$help;
		 // check constant with prefix
		 else if (defined("GM_".$help)) $sentence = constant("GM_".$help);
		 else (!defined("GM_LANG_".$help) ? $sentence = LanguageFunctions::GetString($help, $LANGUAGE) : $sentence = constant("GM_LANG_".$help));
	 }
	 if (empty($sentence)) {
		  if ($noprint == 2) {
			  $sentence = $help;
	  	  }
	  	  else {
			  if (!defined("GM_LANG_".$help)) $sentence = LanguageFunctions::GetString($help, $LANGUAGE);
			  else $sentence = constant("GM_LANG_".$help);
		  }
		
		  if (empty($sentence)) {
			  if ($DEBUG_LANG == "yes") print "[LANG_DEBUG] Variable not present: ".$help."<br /><br />";
			  $sentence = GM_LANG_help_not_exist;
		  }
	 }
	 $mod_sentence = "";
	 $replace = "";
	 $replace_text = "";
	 $sub = "";
	 $pos1 = 0;
	 $pos2 = 0;
	 $ct = preg_match_all("/#([a-zA-Z0-9_.\-\[\]]+)#/", $sentence, $match, PREG_SET_ORDER);
	 for($i=0; $i<$ct; $i++) {
		  $value = "";
		  $newreplace = preg_replace(array("/factarray/","/gm_lang/","/\[/","/\]/"), array("","","",""), $match[$i][1]);
		  if ($DEBUG_LANG == "yes") print "[LANG_DEBUG] Embedded variable: ".$match[$i][1]."<br /><br />";
		  $value = PrintText($newreplace, $level+1);
		  if (!empty($value)) $sentence = str_replace($match[$i][0], $value, $sentence);
		  else if ($noprint==0) $sentence = str_replace($match[$i][0], $match[$i][1].": ".GM_LANG_var_not_exist, $sentence);
	 }
	 // ------ Replace paired ~  by tag_start and tag_end (those vars contain CSS classes)
	 while (stristr($sentence, "~") == TRUE){
		  $pos1 = strpos($sentence, "~");
		  $mod_sentence = substr_replace($sentence, " ", $pos1, 1);
		  if (stristr($mod_sentence, "~")){		// If there's a second one:
			  $pos2 = strpos($mod_sentence, "~");
			  $replace = substr($sentence, ($pos1+1), ($pos2-$pos1-1));
			  $replace_text = "<span class=\"HelpContentHeader\">".Str2Upper($replace)."</span>";
			  $sentence = str_replace("~".$replace."~", $replace_text, $sentence);
		  } else break;
	 }
	 if ($noprint>0) return $sentence;
	 if ($level>0) return $sentence;
	 print $sentence;
}

function PrintHelpIndex($help){

	 $sentence = constant("GM_LANG_".$help);
	 $mod_sentence = "";
	 $replace = "";
	 $replace_text = "";
	 $sub = "";
	 $pos1 = 0;
	 $pos2 = 0;
	 $admcol=false;
	 $ch=0;
	 $help_sorted = array();
	 $var="";
	 while (stristr($sentence, "#") == TRUE){
		$pos1 = strpos($sentence, "#");
		$mod_sentence = substr_replace($sentence, " ", $pos1, 1);
		$pos2 = strpos($mod_sentence, "#");
		$replace = substr($sentence, ($pos1+1), ($pos2-$pos1-1));
		$sub = preg_replace(array("/gm_lang\\[/","/\]/"), array("",""), $replace);
		if (defined("GM_LANG_".$sub)) {
			$items = preg_split("/,/", constant("GM_LANG_".$sub));
			$var = PrintText($items[1],0,1);
		}
		$sub = preg_replace(array("/factarray\\[/","/\]/"), array("",""), $replace);
//		print "sub: ".$sub."<br />";
		if (defined("GM_FACT_".$sub)) {
			$items = preg_split("/,/", constant("GM_FACT_".$sub));
			$var = constant("GM_FACT_".$items[1]);
		}
		if (substr($var,0,1)=="_") {
			$admcol=true;
			$ch++;
		}
		$replace_text = "<a href=\"help_text.php?help=".$items[0]."\">".$var."</a><br />";
		$help_sorted[$replace_text] = $var;
		$sentence = str_replace("#".$replace."#", $replace_text, $sentence);
	 }
	 uasort($help_sorted, "StringSort");
	 if ($ch==0) $ch=count($help_sorted);
	 else $ch +=$ch;
	 if ($ch>0) print "<table class=\"ListTable\"><tr><td class=\"ListTableContent HelpContentList\"><ul>";
	 $i=0;
	 foreach ($help_sorted as $k => $help_item){
		print "<li>".$k."</li>";
		$i++;
		if ($i==ceil($ch/2)) print "</ul></td><td  class=\"ListTableContent HelpContentList\"><ul>";
	 }
	 if ($ch>0) print "</ul></td></tr></table>";
}

/**
 * Prepare text with parenthesis for printing
 * Convert & to &amp; for xhtml compliance
 *
 * @author	Genmod Development Team
 * @param		string	$text		The text that should be preperated
 * @param		boolean	$InHeaders	Is the text from the header, if so do not highlight it
 * @return 	string 	text to be printed
 */
function PrintReady($text, $InHeaders=false) {
	global $TEXT_DIRECTION, $SpecialChar, $SpecialPar, $query, $action, $firstname, $lastname, $place, $year;
	
	// Check whether Search page highlighting should be done or not
	$HighlightOK = false;
	if (strstr($_SERVER["SCRIPT_NAME"], "search.php")) {	// If we're on the Search page
		if (!$InHeaders) {								//   and also in page body
			//	if ((isset($query) and ($query != "")) || (isset($action) && ($action === "soundex"))) {		//   and the query isn't blank
			if ((isset($query) and ($query != "")) ) {		//   and the query isn't blank				
				//$HighlightOK = true;					// It's OK to mark search result
			}
		}
	}
	$SpecialOpen = '(';
	$SpecialClose = array('(');
	//-- convert all & to &amp;
	$text = preg_replace("/&/", "&amp;", $text);
	//-- make sure we didn't double convert &amp; to &amp;amp;
	$text = preg_replace("/&amp;(\w+);/", "&$1;", $text);
	
	// NOTE: Remove any lrm and rlm to prevent doubling up
	$text = preg_replace(array('/&rlm;/','/&lrm;/'), '', $text);
	
	$text=trim($text);
	//-- if we are on the search page body, then highlight any search hits
	if ($HighlightOK) {
		if (isset($query)) {
			$queries = preg_split("/\.\*/", $query);
			$newtext = $text;
			$hasallhits = true;
			foreach($queries as $index=>$query1) {
				if (preg_match("/(".$query1.")/i", $text)) {
					$newtext = preg_replace("/(".$query1.")/i", "<span class=\"SearchHit\">$1</span>", $newtext);
				}
				else if (preg_match("/(".Str2Upper($query1).")/", Str2Upper($text))) {
					$nlen = strlen($query1);
					$npos = strpos(Str2Upper($text), Str2Upper($query1));
					$newtext = substr_replace($newtext, "</span>", $npos+$nlen, 0);
					$newtext = substr_replace($newtext, "<span class=\"SearchHit\">", $npos, 0);
				}
				else $hasallhits = false;
			}
			if ($hasallhits) $text = $newtext;
		}
		if (isset($action) && ($action === "soundex")) {
			if (isset($firstname)) {
			$queries = preg_split("/\.\*/", $firstname);
			$newtext = $text;
			$hasallhits = true;
			foreach($queries as $index=>$query1) {
			if (preg_match("/(".$query1.")/i", $text)) {
			$newtext = preg_replace("/(".$query1.")/i", "<span class=\"SearchHit\">$1</span>", $newtext);
			}
			else if (preg_match("/(".Str2Upper($query1).")/", Str2Upper($text))) {
			$nlen = strlen($query1);
			$npos = strpos(Str2Upper($text), Str2Upper($query1));
			$newtext = substr_replace($newtext, "</span>", $npos+$nlen, 0);
			$newtext = substr_replace($newtext, "<span class=\"SearchHit\">", $npos, 0);
			}
			else $hasallhits = false;
			}
			if ($hasallhits) $text = $newtext;
			}
			if (isset($lastname)) {
				$queries = preg_split("/\.\*/", $lastname);
				$newtext = $text;
				$hasallhits = true;
				foreach($queries as $index=>$query1) {
					if (preg_match("/(".$query1.")/i", $text)) {
						$newtext = preg_replace("/(".$query1.")/i", "<span class=\"SearchHit\">$1</span>", $newtext);
					}
					else if (preg_match("/(".Str2Upper($query1).")/", Str2Upper($text))) {
						$nlen = strlen($query1);
						$npos = strpos(Str2Upper($text), Str2Upper($query1));
						$newtext = substr_replace($newtext, "</span>", $npos+$nlen, 0);
						$newtext = substr_replace($newtext, "<span class=\"SearchHit\">", $npos, 0);
					}
					else $hasallhits = false;
				}
				if ($hasallhits) $text = $newtext;
			}
			if (isset($place)) {
				$queries = preg_split("/\.\*/", $place);
				$newtext = $text;
				$hasallhits = true;
				foreach($queries as $index=>$query1) {
					if (preg_match("/(".$query1.")/i", $text)) {
						$newtext = preg_replace("/(".$query1.")/i", "<span class=\"SearchHit\">$1</span>", $newtext);
					}
					else if (preg_match("/(".Str2Upper($query1).")/", Str2Upper($text))) {
						$nlen = strlen($query1);
						$npos = strpos(Str2Upper($text), Str2Upper($query1));
						$newtext = substr_replace($newtext, "</span>", $npos+$nlen, 0);
						$newtext = substr_replace($newtext, "<span class=\"SearchHit\">", $npos, 0);
					}
					else $hasallhits = false;
				}
				if ($hasallhits) $text = $newtext;
			}
			if (isset($year)) {
				$queries = preg_split("/\.\*/", $year);
				$newtext = $text;
				$hasallhits = true;
				foreach($queries as $index=>$query1) {
					if (preg_match("/(".$query1.")/i", $text)) {
						$newtext = preg_replace("/(".$query1.")/i", "<span class=\"SearchHit\">$1</span>", $newtext);
					}
					else $hasallhits = false;
				}
				if ($hasallhits) $text = $newtext;
			}
		}
	}
	if (hasRTLText($text)) {
		if (hasLTRText($text)) {
			// Text contains both RtL and LtR characters
			// return the parenthesis with surrounding &rlm; and the rest as is
			$printvalue = "";
			$first = 1;
			$linestart = 0;
			for ($i=0; $i<strlen($text); $i++) {
				$byte = substr($text,$i,1);
				if (substr($text,$i,6) == "<br />") $linestart = $i+6;
				if (in_array($byte,$SpecialPar) || (($i==strlen($text)-1 || substr($text,$i+1,6)=="<br />") && in_array($byte,$SpecialChar))) {
					if ($first==1) {
						if ($byte==")" && !in_array(substr($text,$i+1),$SpecialClose)) {
							$printvalue .= "&lrm;".$byte."&lrm;";
							$linestart = $i+1;
						}
						else	if (in_array($byte,$SpecialChar)) {                          //-- all special chars
							if (hasRTLText(substr($text,$linestart,4))) $printvalue .= "&rlm;".$byte."&rlm;";
							else $printvalue .= "&lrm;".$byte."&lrm;";
						}
						else {
							$first = 0;
							if (hasRTLText(substr($text,$i+1,4))) {
								$printvalue .= "&rlm;";
								$ltrflag = 0;
							}
							else {
								$printvalue .= "&lrm;";
								$ltrflag = 1;
							}
							$printvalue .= substr($text,$i,1);
						}
					}
					else {
						$first = 1;
						$printvalue .= substr($text,$i,1);
						if ($ltrflag) $printvalue .= "&lrm;";
						else $printvalue .= "&rlm;";
					}
				}
				else if (oneRTLText(substr($text,$i,2))) {
					$printvalue .= substr($text,$i,2);
					$i++;
				}
				else $printvalue .= substr($text,$i,1);
			}
			if (!$first) if ($ltrflag) $printvalue .= "&lrm;";
			else $printvalue .= "&rlm;";
			return $printvalue;
		}
		else return "&rlm;".$text."&rlm;";
	}
	else if ($TEXT_DIRECTION=="rtl" && hasLTRText($text)) {
		$printvalue = "";
		$linestart = 0;
		$first = 1;
		for ($i=0; $i<strlen($text); $i++) {
			$byte = substr($text,$i,1);
			if (substr($text,$i,6) == "<br />") $linestart = $i+6;
			if (in_array($byte,$SpecialPar)	|| (($i==strlen($text)-1 || substr($text,$i+1,6)=="<br />") && in_array($byte,$SpecialChar))) {
				if ($first==1) {
					if ($byte==")" && !in_array(substr($text,$i+1),$SpecialClose)) {
						$printvalue .= "&rlm;".$byte."&rlm;";
						$linestart = $i+1;
					}
					else if (in_array($byte,$SpecialChar) && ($i==strlen($text)-1 || substr($text,$i+1,6)=="<br />")) {
						if (hasRTLText(substr($text,$linestart,4))) $printvalue .= "&rlm;".$byte."&rlm;";
						else $printvalue .= "&lrm;".$byte."&lrm;";
					}
					else {
						$first = 0;
						if (hasRTLText(substr($text,$i+1,4))) {
							$printvalue .= "&rlm;";
							$ltrflag = 0;
						}
						else {
							$printvalue .= "&lrm;";
							$ltrflag = 1;
						}
						$printvalue .= substr($text,$i,1);
					}
				}
				else {
					$first = 1;
					$printvalue .= substr($text,$i,1);
					if ($ltrflag) $printvalue .= "&lrm;";
					else $printvalue .= "&rlm;";
				}
			}
			else {
				if (oneRTLText(substr($text,$i,2))) {
					$printvalue .= substr($text,$i,2);
					$i++;
				}
				else $printvalue .= substr($text,$i,1);
			}
		}
		if (!$first) if ($ltrflag) $printvalue .= "&lrm;";
		else $printvalue .= "&rlm;";
		return $printvalue;
	}
	else {
		return $text;
	}
}

/**
 * javascript declaration for calendar popup
 *
 * @param none
 */
function InitCalendarPopUp() {
	global $monthtonum, $WEEK_START;

	print "<script language=\"JavaScript\" type='text/javascript'>\n<!--\n";
	// month names
	print "cal_setMonthNames(";
	foreach($monthtonum as $mon=>$num) {
		if (defined("GM_LANG_".$mon)) {
			if ($num>1) print ",";
			print "\"".constant("GM_LANG_".$mon)."\"";
		}
	}
	print ");\n";
	// day headers
	print "cal_setDayHeaders(";
	foreach(array('sunday_1st','monday_1st','tuesday_1st','wednesday_1st','thursday_1st','friday_1st','saturday_1st') as $indexval => $day) {
		if (defined("GM_LANG_".$day)) {
			if ($day!=="sunday_1st") print ",";
			print "\"".constant("GM_LANG_".$day)."\"";
		}
	}
	print ");\n";
	// week start day
	print "cal_setWeekStart(".$WEEK_START.");\n";
	print "//-->\n</script>\n";
}


function PrintFilterEvent($filterev) {
	
	print "<option value=\"all\"";
	if ($filterev == "all") print " selected=\"selected\"";
	print ">".GM_LANG_all."</option>\n";		
	
	// If this array is changed, also change the selection for the search facts in calendar.php!
	$events = array("BIRT", "CHR", "CHRA", "BAPM", "_COML", "MARR", "DIV", "DEAT", "BURI", "IMMI", "EMIG", "EVEN");
	
	foreach($events as $nothing => $event) {
		print "<option value=\"".$event."\"";
		if ($filterev == $event) print " selected=\"selected\"";
		if ($event == "EVEN") print ">".GM_LANG_custom_event."</option>\n";
		else print ">".constant("GM_FACT_".$event)."</option>\n";
	}
}

function PrintCachedObjectCount() {
	if (DEBUG) {
		print "Objects:";
		print " P: ".(class_exists("Person", false) ? Person::objcount() : 0);
		print " F: ".(class_exists("Family", false) ? Family::objcount() : 0);
		print " M: ".(class_exists("MediaItem", false) ? MediaItem::objcount() : 0);
		print " S: ".(class_exists("Source", false) ? Source::objcount() : 0);
		print " R: ".(class_exists("Repository", false) ? Repository::objcount() : 0);
		print " N: ".(class_exists("Note", false) ? Note::Objcount() : 0);
		print " U: ".(class_exists("User", false) ? User::objcount() : 0);
		print "<br />";
	}
}	

//-- function to print a privacy error with contact method
function PrintPrivacyError($username) {
	
	 $method = GedcomConfig::$CONTACT_METHOD;
	
	 if ($username == GedcomConfig::$WEBMASTER_EMAIL) $method = GedcomConfig::$SUPPORT_METHOD;
	 $user =& User::GetInstance($username);
	 if (empty($user->username)) $method = "mailto";
	 print "<br /><span class=\"Error\">".GM_LANG_privacy_error;
	 if ($method=="none") {
		  print "</span><br />\n";
		  return;
	 }
	 print GM_LANG_more_information;
	 if ($method=="mailto") {
		  if (!$user) {
			   $email = $username;
			   $fullname = $username;
		  }
		  else {
			   $email = $user->email;
			   $fullname = $user->firstname." ".$user->lastname;
		  }
		  print " <a href=\"mailto:$email\">".$fullname."</a></span><br />";
	 }
	 else {
		  print " <a href=\"#\" onclick=\"message('$username','$method'); return false;\">".$user->firstname." ".$user->lastname."</a></span><br />";
	 }
}

function PrintAccessKeyHeaders() {
	global $action;
	
	?>
	<div class="HeaderAccessKeys">
	<a class="HeaderAccessKeys" href="#content" title="<?php print GM_LANG_accesskey_skip_to_content_desc; ?>" tabindex="0" accesskey="<?php print GM_LANG_accesskey_skip_to_content; ?>"><?php print GM_LANG_accesskey_skip_to_content_desc; ?></a>
	<a class="HeaderAccessKeys" href="javascript://accesskey_viewing_advice_help" onfocus="this.onclick" onclick="return helpPopup('accesskey_viewing_advice_help');" title="<?php print GM_LANG_accesskey_viewing_advice_desc; ?>" accesskey="<?php print GM_LANG_accesskey_viewing_advice; ?>"><?php print GM_LANG_accesskey_viewing_advice_desc; ?></a>
	<a href="javascript://help_<?php print basename(SCRIPT_NAME); ?>" onclick="return helpPopup('help_<?php print basename(SCRIPT_NAME); ?>&amp;action=<?php print $action;?>');" accesskey="<?php print GM_LANG_accesskey_help_current_page; ?>"> </a>
	<a href="javascript://help_contents_help" onclick="return helpPopup('help_contents_help');" accesskey="<?php print GM_LANG_accesskey_help_content; ?>"> </a>
	</div>
	<?php
}

function PrintGetSetError($property, $class, $type) {
	
	print "<span class=\"Error\">Invalid property ".$property." for __".$type." in ".$class." class</span><br />";
}
?>
