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
 * @version $Id$
 */
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
/**
 * print a person in a list
 *
 * This function will print a
 * clickable link to the individual.php
 * page with the person's name
 * lastname, firstname and their
 * birthplace and date
 * @param string $key the GEDCOM xref id of the person to print
 * @param array $value is an array of the form array($name, $GEDCOM)
 */
function print_list_person($key, $value, $findid=false, $asso="", $useli=true, $fact="") {
	global $pass, $indi_private, $indi_hide, $indi_total, $NAME_REVERSE;
	global $GEDCOMID, $TEXT_DIRECTION, $GM_IMAGES, $SHOW_DEATH_LISTS;
	
	$key = splitkey($key, "id");
	SwitchGedcom($value[1]);
	
	if (!isset($indi_private)) $indi_private=array();
	if (!isset($indi_hide)) $indi_hide=array();
	if (!isset($indi_total)) $indi_total=array();
	$indi_total[$key."[".$GEDCOMID."]"] = 1;

	$disp = PrivacyFunctions::displayDetailsByID($key);
	if ($disp) $disp2 = true;
	else $disp2 = PrivacyFunctions::showLivingNameByID($key);
	if ($disp2 || $disp) {
		if ($useli) {
			if (begRTLText($value[0]))                            //-- For future use
				 print "<li class=\"rtl\" dir=\"rtl\">";
			else print "<li class=\"ltr\" dir=\"ltr\">";
		}
//		if ($NAME_REVERSE || HasChinese($value[0])) $value[0] = str_replace(", ", "", $value[0]);
		if ($findid == true) {
			print "<a href=\"#\" onclick=\"pasteid('".$key."', '".urlencode(preg_replace("/'/", "\\'", PrintReady($value[0])));
			if ($disp) print "<br />".urlencode(print_first_major_fact($key, "", false));
			print "'); return false;\" class=\"list_item\"><b>";
			if (HasChinese($value[0])) print PrintReady($value[0]." (".GetPinYin($value[0]).")");
			else print PrintReady($value[0]);
			print "</b>";
		}
		else {
			print "<a href=\"individual.php?pid=$key&amp;gedid=$value[1]\" class=\"list_item\"";
			if (!empty($fact)) print " target=\"blank\" ";
			print "><b>";
//			if (HasChinese($value[0])) print PrintReady($value[0]." (".GetPinYin($value[0]).")");
//			else print PrintReady($value[0]);
			print PrintReady($value[0]);
			print "</b>";
		}
		if (GedcomConfig::$SHOW_ID_NUMBERS){
		   if ($TEXT_DIRECTION=="ltr") print " <span dir=\"ltr\">($key)</span>";
  		   else print " <span dir=\"rtl\">($key)</span>";
		}

		if (!$disp) {
			print " -- <i>".GM_LANG_private."</i>";
			$indi_private[$key."[".$GEDCOMID."]"] = 1;
		}
		else {
			$pfact = print_first_major_fact($key);
			if (isset($SHOW_DEATH_LISTS) && $SHOW_DEATH_LISTS==true) {
				if ($pfact!="DEAT") {
					$indirec = FindPersonRecord($key);
					$factrec = GetSubRecord(1, "1 DEAT", $indirec);
					if (strlen($factrec)>7 && PrivacyFunctions::showFact("DEAT", $key, "INDI") && !PrivacyFunctions::FactViewRestricted($key, $factrec)) {
						print " -- <i>";
						print GM_FACT_DEAT;
						print " ";
						print_fact_date($factrec);
						print_fact_place($factrec);
						print "</i>";
					}
				}
			}
		}
		if (!empty($fact)) {
			print " <i>(";
			if (defined("GM_FACT_".$fact)) print constant("GM_FACT_".$fact);
			else print $fact;
			print ")</i>";
		}
		print "</a>";
		if (is_array($asso) && ($disp)) {
			foreach ($asso as $akey => $avalue) {
				$newged = splitkey($avalue[0], "gedid");
				SwitchGedcom($newged);
				$key = splitkey($avalue[0], "id");
				if ($avalue[1] == "indi") {
					$name = GetPersonName($key);
					print "<br /><a href=\"individual.php?pid=$key&amp;gedid=$GEDCOMID\" title=\"$name\" class=\"list_item\">";
  				}
  				else {
					$name = GetFamilyDescriptor($key);
					print "<br /><a href=\"family.php?famid=$key&amp;gedid=$GEDCOMID\" title=\"$name\" class=\"list_item\">";
				}
				if ($TEXT_DIRECTION=="ltr") print " <span dir=\"ltr\">";
				else print " <span dir=\"rtl\">";
				print "(".GM_LANG_associate_with." ";
				if (GedcomConfig::$SHOW_ID_NUMBERS) print $key;
				print ": ".$name;
				if (!empty($avalue[2]) || !empty($avalue[3])) {
					print " - ";
					if (!empty($avalue[2])) print constant("GM_FACT_".$avalue[2]);
					if(!empty($avalue[2]) && !empty($avalue[3])) print " : ";
					if (defined("GM_LANG_".$avalue[3])) print constant("GM_LANG_".$avalue[3]);
					else print $avalue[3];
				}
				print ")</span></a>";
	  			SwitchGedcom();
			}
		}

		if ($useli) print "</li>";
	}
	else {
		$pass = TRUE;
		$indi_hide[$key."[".$GEDCOMID."]"] = 1;
	}
	SwitchGedcom();
}

//-- print information about a family for a list view
// param fact is for sanitycheck to print the fact and open a new page in a new window.
function print_list_family($key, $value, $findid=false, $asso="", $useli=true, $fact="") {
	global $pass, $fam_private, $fam_hide, $fam_total;
	global $GEDCOMID, $HIDE_LIVE_PEOPLE;
	global $TEXT_DIRECTION, $COMBIKEY;

	SwitchGedcom($value[1]);
	
	if (!isset($fam_private)) $fam_private=array();
	if (!isset($fam_hide)) $fam_hide=array();
	if (!isset($fam_total)) $fam_total=array();
	$fam_total[$key."[".$GEDCOMID."]"] = 1;
	$famrec=FindFamilyRecord($key);
	$display = PrivacyFunctions::displayDetailsByID($key, "FAM");
	//print "display: ".$display." key: ".$key." famrec: ".$famrec;
	$showLivingHusb=true;
	$showLivingWife=true;
	$parents = FindParents($key);
	//-- check if we can display both parents
	if (!$display) {
		if (!PrivacyFunctions::FactViewRestricted($key, $famrec, 1)) {
			$showLivingHusb=PrivacyFunctions::showLivingNameByID($parents["HUSB"]);
			$showLivingWife=PrivacyFunctions::showLivingNameByID($parents["WIFE"]);
		}
	}
	if ($showLivingWife && $showLivingHusb) {
		$kid = SplitKey($key, "id");
		if ($useli) {
			if (begRTLText($value[0]))                            //-- For future use
				 print "<li class=\"rtl\" dir=\"rtl\">";
			else print "<li class=\"ltr\" dir=\"ltr\">";
		}
		if ($findid == true) {
			print "<a href=\"#\" onclick=\"pasteid('".$kid."'); return false;\" class=\"list_item\"><b>";
//			if (HasChinese($value[0])) print PrintReady($value[0]." (".GetPinYin($value[0]).")");
//			else print PrintReady($value[0]);
			print PrintReady($value[0]);
			print "</b>";
		}
		else {
			print "<a href=\"family.php?famid=$kid&amp;gedid=$value[1]\" class=\"list_item\"";
			if (!empty($fact)) print " target=\"blank\" ";
			print "><b>";
//			if (HasChinese($value[0])) print PrintReady($value[0]." (".GetPinYin($value[0]).")");
//			else print PrintReady($value[0]);
			print PrintReady($value[0]);
			print "</b>";
		}
		if (GedcomConfig::$SHOW_FAM_ID_NUMBERS) {
			if ($TEXT_DIRECTION=="ltr")	print " <span dir=\"ltr\">($kid)</span>";
  			else print " <span dir=\"rtl\">($kid)</span>";
			}
		if (!$display) {
			print " -- <i>".GM_LANG_private."</i>";
			$fam_private[$key."[".$GEDCOMID."]"] = 1;
		}
		else {
			$bpos1 = strpos($famrec, "1 MARR");
			if ($bpos1) {
				$birthrec = GetSubRecord(1, "1 MARR", $famrec);
				if (!PrivacyFunctions::FactViewRestricted($key, $birthrec) && PrivacyFunctions::showFact("MARR", $kid)) {
					print " -- <i>".GM_LANG_marriage." ";
					$bt = preg_match("/1 \w+/", $birthrec, $match);
					if ($bt>0) {
						 $bpos2 = strpos($birthrec, $match[0]);
						 if ($bpos2) $birthrec = substr($birthrec, 0, $bpos2);
					}
					print_fact_date($birthrec);
					print_fact_place($birthrec);
				}
				print "</i>";
			}
		}
		if (!empty($fact)) {
			print " <i>(";
			if (defined("GM_FACT_".$fact)) print constant("GM_FACT_".$fact);
			else print $fact;
			print ")</i>";
		}
		print "</a>";
		if (is_array($asso) && ($display)) {
			foreach ($asso as $akey => $avalue) {
				$newged = splitkey($avalue[0], "ged");
				SwitchGedcom($newged);
				$key = splitkey($avalue[0], "id");
				if ($avalue[1] == "indi") {
					$name = GetPersonName($key);
					print "<br /><a href=\"individual.php?pid=$key&amp;gedid=$GEDCOMID\" title=\"$name\" class=\"list_item\">";
  				}
  				else {
					$name = GetFamilyDescriptor($key);
					print "<br /><a href=\"family.php?famid=$key&amp;gedid=$GEDCOMID\" title=\"$name\" class=\"list_item\">";
				}
				if ($TEXT_DIRECTION=="ltr") print " <span dir=\"ltr\">";
				else print " <span dir=\"rtl\">";
				print "(".GM_LANG_associate_with." ";
				if (GedcomConfig::$SHOW_ID_NUMBERS) print $key;
				print ": ".$name;
				if(!empty($avalue[2]) || !empty($avalue[3])) {
					print " - ";
					if (!empty($avalue[2])) print constant("GM_FACT_".$avalue[2]);
					if(!empty($avalue[2]) && !empty($avalue[3])) print " : ";
					if (defined("GM_LANG_".$avalue[3])) print constant("GM_LANG_".$avalue[3]);
					else print $avalue[3];
				}
				print ")</span></a>";
	  			SwitchGedcom();
			}
		}
		if ($useli) print "</li>\n";
	}															//begin re-added by pluntke
	else {				   	//fixed THIS line (changed && to ||)
		$pass = true;
		$fam_hide[$key."[".$GEDCOMID."]"] = 1;
	}															//end re-added by pluntke
	SwitchGedcom();
}
// Initializes counters for lists
function InitListCounters($action = "reset") {
	global $indi_total, $indi_hide, $indi_private;
	global $fam_total, $fam_hide, $fam_private;
	global $repo_total, $repo_hide;
	global $source_total, $source_hide;
	global $note_total, $note_hide;
	global $media_total, $media_hide;

	if ($action != "reset") {
		if (!isset($indi_total)) $indi_total = array();
		if (!isset($indi_private)) $indi_private = array();
		if (!isset($indi_hide)) $indi_hide = array();
		if (!isset($fam_total)) $fam_total = array();
		if (!isset($fam_private)) $fam_private = array();
		if (!isset($fam_hide)) $fam_hide = array();
		if (!isset($source_total)) $source_total = array();
		if (!isset($source_hide)) $source_hide = array();
		if (!isset($repo_total)) $repo_total = array();
		if (!isset($repo_hide)) $repo_hide = array();
		if (!isset($note_total)) $note_total = array();
		if (!isset($note_hide)) $note_hide = array();
		if (!isset($media_total)) $media_total = array();
		if (!isset($media_hide)) $media_hide = array();
	}
	else {
		$indi_total = array();
		$indi_private = array();
		$indi_hide = array();
		$fam_total = array();
		$fam_private = array();
		$fam_hide = array();
		$source_total = array();
		$source_hide = array();
		$repo_total = array();
		$repo_hide = array();
		$note_total = array();
		$note_hide = array();
		$media_total = array();
		$media_hide = array();
	}
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
	global $BROWSERTYPE, $indilist, $INDILIST_RETRIEVED;
	global $view;
	global $GEDCOMS;
	global $QUERY_STRING, $action, $query, $changelanguage,$theme_name;
	global $GM_IMAGES, $TEXT_DIRECTION, $ONLOADFUNCTION, $SHOW_SOURCES;
	// globals for the bot 304 mechanism
	global $bot, $_SERVER, $GEDCOMID, $pid, $famid, $rid, $sid;

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
			$lastchange = GetLastChangeDate("INDI", $pid, $GEDCOMID, true);
			break;
		case "family.php":
			$lastchange = GetLastChangeDate("FAM", $famid, $GEDCOMID, true);
			break;
		case "source.php":
			$lastchange = GetLastChangeDate("FAM", $sid, $GEDCOMID, true);
			break;
		case "repo.php":
			$lastchange = GetLastChangeDate("REPO", $rid, $GEDCOMID, true);
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
	   print "<link rel=\"shortcut icon\" href=\"".GedcomConfig::$FAVICON."\" type=\"image/x-icon\"></link>\n\t\t";
	}
//	if (!isset(GedcomConfig::$META_TITLE)) GedcomConfig::$META_TITLE = "";
	if (isset($GEDCOMS[$GEDCOMID]["title"])) $title = $GEDCOMS[$GEDCOMID]["title"]." :: ".$title;
	print "<title>".PrintReady(strip_tags($title)." - ".GedcomConfig::$META_TITLE." - Genmod", TRUE)."</title>\n\t";
	 if (!GedcomConfig::$REQUIRE_AUTHENTICATION){
		print "<link href=\"" . SERVER_URL .  "rss.php\" rel=\"alternate\" type=\"application/rss+xml\" title=\"RSS\"></link>\n\t";
	 }
	 print "<link rel=\"stylesheet\" href=\"".GM_STYLESHEET."\" type=\"text/css\" media=\"all\"></link>\n\t";
	 if ((!empty($rtl_stylesheet))&&($TEXT_DIRECTION=="rtl")) print "<link rel=\"stylesheet\" href=\"".GM_RTL_STYLESHEET."\" type=\"text/css\" media=\"all\"></link>\n\t";
	 if ($use_alternate_styles) {
		if ($BROWSERTYPE != "other") {
			print "<link rel=\"stylesheet\" href=\"".GedcomConfig::$THEME_DIR.$BROWSERTYPE.".css\" type=\"text/css\" media=\"all\"></link>\n\t";
		}
	 }
	 print "<link rel=\"stylesheet\" href=\"".GM_PRINT_STYLESHEET."\" type=\"text/css\" media=\"print\"></link>\n\t";
	 if ($BROWSERTYPE == "msie") print "<style type=\"text/css\">\nFORM { margin-top: 0px; margin-bottom: 0px; }\n</style>\n";
	 print "<!-- Genmod v".GM_VERSION." -->\n";
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
		  $surnames = GetCommonSurnamesIndex($GEDCOMID);
		  foreach($surnames as $surname=>$count) if (!empty($surname)) print ", $surname";
		  print "\" />\n";
		  if ((empty(GedcomConfig::$META_PAGE_TOPIC))&&(!empty($GEDCOMS[$GEDCOMID]["title"]))) GedcomConfig::$META_PAGE_TOPIC = $GEDCOMS[$GEDCOMID]["title"];
		//LERMAN - make meta description unique, like the title
		  if (empty(GedcomConfig::$META_DESCRIPTION)) GedcomConfig::$META_DESCRIPTION = PrintReady(strip_tags($title)." - ".GedcomConfig::$META_TITLE." - Genmod", TRUE);
		  //if ((empty($META_DESCRIPTION))&&(!empty($GEDCOMS[$GEDCOMID]["title"]))) $META_DESCRIPTION = $GEDCOMS[$GEDCOMID]["title"];
		  if (!empty(GedcomConfig::$META_DESCRIPTION)) print "<meta name=\"description\" content=\"".preg_replace("/\"/", "", GedcomConfig::$META_DESCRIPTION)."\" />\n";
		  if (!empty(GedcomConfig::$META_PAGE_TOPIC)) print "<meta name=\"page-topic\" content=\"".preg_replace("/\"/", "", GedcomConfig::$META_PAGE_TOPIC)."\" />\n";
	 	  if (!empty(GedcomConfig::$META_AUDIENCE)) print "<meta name=\"audience\" content=\"".GedcomConfig::$META_AUDIENCE."\" />\n";
	 	  if (!empty(GedcomConfig::$META_PAGE_TYPE)) print "<meta name=\"page-type\" content=\"".GedcomConfig::$META_PAGE_TYPE."\" />\n";
	 	  if (!empty(GedcomConfig::$META_ROBOTS)) print "<meta name=\"robots\" content=\"".GedcomConfig::$META_ROBOTS."\" />\n";
	 	  if (!empty(GedcomConfig::$META_REVISIT)) print "<meta name=\"revisit-after\" content=\"".GedcomConfig::$META_REVISIT."\" />\n";
		  print "<meta name=\"generator\" content=\"Genmod v".GM_VERSION." - http://www.genmod.net\" />\n";
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
	 <?php print "query = \"$query_string\";\n"; ?>
	 <?php print "textDirection = \"$TEXT_DIRECTION\";\n"; ?>
	 <?php print "browserType = \"$BROWSERTYPE\";\n"; ?>
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
<script src="genmod.js" language="JavaScript" type="text/javascript"></script>
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
	 
	 if ($view!="preview") include(GM_MENUBAR);
	 else include(GM_PRINT_HEADERFILE);
	 print "<!-- end header section -->\n";
	 print "<!-- begin content section -->\n";
	
	 if (USE_GREYBOX) { ?>
		<script type="text/javascript" src="modules/greybox/AJS.js"></script>
		<script type="text/javascript" src="modules/greybox/AJS_fx.js"></script>
		<script type="text/javascript" src="modules/greybox/gb_scripts.js"></script>
	 <?php
 	}
	 // Unset the indilist as it is contaminated with ID's from other gedcoms
	 $INDILIST_RETRIEVED = false;
	 $indilist = array();
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
	 global $view;
	 global $QUERY_STRING, $action, $query, $changelanguage;
	 global $TEXT_DIRECTION, $GEDCOMS, $GEDCOMID,$GM_IMAGES;
	 
	 header("Content-Type: text/html; charset=".GedcomConfig::$CHARACTER_SET);
	 print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
	 print "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n\t<head>\n\t\t";
	 print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".GedcomConfig::$CHARACTER_SET."\" />\n\t\t";
	if( GedcomConfig::$FAVICON ) {
	   print "<link rel=\"shortcut icon\" href=\"".GedcomConfig::$FAVICON."\" type=\"image/x-icon\"></link>\n\t\t";
	}
//	if (!isset(GedcomConfig::$META_TITLE)) GedcomConfig::$META_TITLE = "";
	print "<title>".PrintReady(strip_tags($title))." - ".GedcomConfig::$META_TITLE." - Genmod</title>\n\t<link rel=\"stylesheet\" href=\"".GM_STYLESHEET."\" type=\"text/css\"></link>\n\t";
	if ((!empty($rtl_stylesheet))&&($TEXT_DIRECTION=="rtl")) print "<link rel=\"stylesheet\" href=\"".GM_RTL_STYLESHEET."\" type=\"text/css\" media=\"all\"></link>\n\t";
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
	$surnames = GetCommonSurnamesIndex($GEDCOMID);
	foreach($surnames as $surname=>$count) print ", $surname";
	print "\" />\n";
	if ((empty(GedcomConfig::$META_PAGE_TOPIC))&&(!empty($GEDCOMS[$GEDCOMID]["title"]))) GedcomConfig::$META_PAGE_TOPIC = $GEDCOMS[$GEDCOMID]["title"];
	//LERMAN - make meta description unique, like the title
	if (empty(GedcomConfig::$META_DESCRIPTION)) GedcomConfig::$META_DESCRIPTION = PrintReady(strip_tags($title)." - ".GedcomConfig::$META_TITLE." - Genmod", TRUE);
	if (!empty(GedcomConfig::$META_DESCRIPTION)) print "<meta name=\"description\" content=\"".preg_replace("/\"/", "", GedcomConfig::$META_DESCRIPTION)."\" />\n";
	if (!empty(GedcomConfig::$META_PAGE_TOPIC)) print "<meta name=\"page-topic\" content=\"".preg_replace("/\"/", "", GedcomConfig::$META_PAGE_TOPIC)."\" />\n";
	if (!empty(GedcomConfig::$META_AUDIENCE)) print "<meta name=\"audience\" content=\"".GedcomConfig::$META_AUDIENCE."\" />\n";
	if (!empty(GedcomConfig::$META_PAGE_TYPE)) print "<meta name=\"page-type\" content=\"".GedcomConfig::$META_PAGE_TYPE."\" />\n";
	if (!empty(GedcomConfig::$META_ROBOTS)) print "<meta name=\"robots\" content=\"".GedcomConfig::$META_ROBOTS."\" />\n";
	if (!empty(GedcomConfig::$META_REVISIT)) print "<meta name=\"revisit-after\" content=\"".GedcomConfig::$META_REVISIT."\" />\n";
	print "<meta name=\"generator\" content=\"Genmod v".GM_VERSION." - http://www.Genmod.net\" />\n";
	GedcomConfig::$META_AUTHOR = $old_META_AUTHOR;
	GedcomConfig::$META_PUBLISHER = $old_META_PUBLISHER;
	GedcomConfig::$META_COPYRIGHT = $old_META_COPYRIGHT;
	GedcomConfig::$META_DESCRIPTION = $old_META_DESCRIPTION;
	GedcomConfig::$META_PAGE_TOPIC = $old_META_PAGE_TOPIC;
	?>
	<style type="text/css">
	<!--
	.largechars {
		font-size: 18px;
	}
	-->
	</style>
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
	<script src="genmod.js" language="JavaScript" type="text/javascript"></script>
	<script src="gmrpc.js" language="JavaScript" type="text/javascript"></script>
	<?php if (USE_GREYBOX) { ?>
		<script type="text/javascript">
		<!--
	    	var GB_ROOT_DIR = "<?php print SERVER_URL."modules/greybox/";?>";
	    //-->
		</script>
		<link href="modules/greybox/gb_styles.css" rel="stylesheet" type="text/css" />
	<?php }
	print "</head>\n\t<body style=\"margin: 5px;\"";
	print " onload=\"loadHandler();\">\n\t";
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
	global $QUERY_STRING, $ALLOW_CHANGE_GEDCOM, $printlink;
	global $theme_name, $GM_IMAGES, $TEXT_DIRECTION, $footer_count;
	
	if (!isset($footer_count)) $footer_count = 1;
	else $footer_count++;
	
	print "<!-- begin footer -->\n";
	$QUERY_STRING = preg_replace("/&/", "&", $QUERY_STRING);
	if ($view != "preview") include(GM_FOOTERFILE);
	else {
		include(GM_PRINT_FOOTERFILE);
		print "\n\t<div class=\"center width95\"><br />";
		$backlink = SCRIPT_NAME."?".GetQueryString();
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
	print "\n\t</body>\n</html>";
	//-- We write the session data and close it. Fix for intermittend logoff.
	session_write_close();

}
// -- print the html to close the page
function PrintSimpleFooter() {
	global $start_time, $buildindex;
	global $CONFIG_PARMS;
	global $QUERY_STRING;
	
//	if (empty(SCRIPT_NAME)) {
//		$SCRIPT_NAME = $_SERVER["SCRIPT_NAME"];
//		$QUERY_STRING = $_SERVER["QUERY_STRING"];
//	}
	print "\n\t<br /><br /><div class=\"center\" style=\"width: 99%;\">";
	PrintContactLinks();
	print "<br />Running <a href=\"http://www.genmod.net/\" target=\"_blank\">Genmod";
	if (count($CONFIG_PARMS) >1) print " Enterprise";
	print MediaFS::GetStorageType();
	print "</a> Version ".GM_VERSION." ".GM_VERSION_RELEASE;
	if (GedcomConfig::$SHOW_STATS) PrintExecutionStats();
	print "</div>";
	if (DebugCollector::$show) DebugCollector::PrintDebug();
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
	print "<br /><br />".GM_LANG_exec_time;
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
	print "<br />";
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
			print "<div class=\"contact_links\">\n";
			//--only display one message if the contact users are the same
			if (GedcomConfig::$CONTACT_EMAIL == GedcomConfig::$WEBMASTER_EMAIL) {
				$user =& User::GetInstance(GedcomConfig::$WEBMASTER_EMAIL);
				if (!$user->is_empty && GedcomConfig::$SUPPORT_METHOD != "mailto") {
					print GM_LANG_for_all_contact." <a href=\"#\" accesskey=\"". GM_LANG_accesskey_contact ."\" onclick=\"message('".GedcomConfig::$WEBMASTER_EMAIL."', '".GedcomConfig::$SUPPORT_METHOD."'); return false;\">".$user->firstname." ".$user->lastname."</a><br />\n";
				}
				else {
					print GM_LANG_for_support." <a href=\"mailto:";
					if (!empty($gm_user->username)) print $user->email."\" accesskey=\"". GM_LANG_accesskey_contact ."\">".$gm_user->firstname." ".$gm_user->lastname."</a><br />\n";
					else print GedcomConfig::$WEBMASTER_EMAIL."\">".GedcomConfig::$WEBMASTER_EMAIL."</a><br />\n";
				}
			}
			//-- display two messages if the contact users are different
			else {
				  $user =& User::GetInstance(GedcomConfig::$CONTACT_EMAIL);
				  if (!$user->is_empty && GedcomConfig::$CONTACT_METHOD!="mailto") {
					  print GM_LANG_for_contact." <a href=\"#\" accesskey=\"". GM_LANG_accesskey_contact ."\" onclick=\"message('".GedcomConfig::$CONTACT_EMAIL."', '".GedcomConfig::$CONTACT_METHOD."'); return false;\">".$gm_user->firstname." ".$gm_user->lastname."</a><br /><br />\n";
				  }
				  else {
					   print GM_LANG_for_contact." <a href=\"mailto:";
					   if (!empty($gm_user->username)) print $user->email."\" accesskey=\"". GM_LANG_accesskey_contact ."\">".$gm_user->firstname." ".$gm_user->lastname."</a><br />\n";
					   else print GedcomConfig::$CONTACT_EMAIL."\">".GedcomConfig::$CONTACT_EMAIL."</a><br />\n";
				  }
				  $user =& User::GetInstance(GedcomConfig::$WEBMASTER_EMAIL);
				  if ($user && GedcomConfig::$SUPPORT_METHOD != "mailto") {
					  print GM_LANG_for_support." <a href=\"#\" onclick=\"message('".GedcomConfig::$WEBMASTER_EMAIL."', '".GedcomConfig::$SUPPORT_METHOD."'); return false;\">".$gm_user->firstname." ".$gm_user->lastname."</a><br />\n";
				  }
				  else {
					   print GM_LANG_for_support." <a href=\"mailto:";
					   if (!empty($gm_user->username)) print $gm_user->email."\">".$gm_user->firstname." ".$gm_user->lastname."</a><br />\n";
					   else print GedcomConfig::$WEBMASTER_EMAIL."\">".GedcomConfig::$WEBMASTER_EMAIL."</a><br />\n";
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

/**
 * print a simple form of the fact
 *
 * function to print the details of a fact in a simple format
 * @param string $indirec the gedcom record to get the fact from
 * @param string $fact the fact to print
 * @param string $pid the id of the individual to print, required to check privacy
 */
function print_simple_fact($indirec, $fact, $pid) {
	
	$emptyfacts = array("BIRT","CHR","DEAT","BURI","CREM","ADOP","BAPM","BARM","BASM","BLES","CHRA","CONF","FCOM","ORDN","NATU","EMIG","IMMI","CENS","PROB","WILL","GRAD","RETI","BAPL","CONL","ENDL","SLGC","EVEN","MARR","SLGS","MARL","ANUL","CENS","DIV","DIVF","ENGA","MARB","MARC","MARS","OBJE","CHAN","_SEPR","RESI", "DATA", "MAP");
	$factrec = GetSubRecord(1, "1 $fact", $indirec);
	if ((empty($factrec))||(PrivacyFunctions::FactViewRestricted($pid, $factrec))) return;
	$label = "";
	if (defined("GM_LANG_".$fact)) $label = constant("GM_LANG_".$fact);
	else if (defined("GM_FACT_".$fact)) $label = constant("GM_FACT_".$fact);
	if (GedcomConfig::$ABBREVIATE_CHART_LABELS) $label = GetFirstLetter($label);
	// RFE [ 1229233 ] "DEAT" vs "DEAT Y"
	// The check $factrec != "1 DEAT" will not show any records that only have 1 DEAT in them
	if (trim($factrec) != "1 DEAT"){
	   print "<span class=\"details_label\">".$label."</span> ";
	}
	if (PrivacyFunctions::showFactDetails($fact, $pid)) {
		if (!in_array($fact, $emptyfacts)) {
			$ct = preg_match("/1 $fact(.*)/", $factrec, $match);
			if ($ct>0) print PrintReady(trim($match[1]));
		}
		// 1 DEAT Y with no DATE => print YES
		// 1 DEAT N is not allowed
		// It is not proper GEDCOM form to use a N(o) value with an event tag to infer that it did not happen.
		/*-- handled by print_fact_date()
		 * if (GetSubRecord(2, "2 DATE", $factrec)=="") {
			if (strtoupper(trim(substr($factrec,6,2)))=="Y") print GM_LANG_yes;
		}*/
		print_fact_date($factrec, false, false, $fact, $pid, $indirec);
		print_fact_place($factrec);
	}
	else print GM_LANG_private;
	print "<br />\n";
}


/* Function to print popup help boxes
 * @param string $help		The variable that needs to be processed.
 * @param int $helpText		The text to be printed if the theme does not use images for help links
 * @param int $show_desc		The text to be shown as JavaScript description
 * @param boolean $use_print_text	If the text needs to be printed with the print_text() function
 * @param boolean $output	return the text instead of printing it
 */
function print_help_link($help, $helpText, $show_desc="", $use_print_text=false, $return=false) {
	global $view, $GM_IMAGES, $gm_user;
	
	if (GM_USE_HELPIMG) $sentense = "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["help"]["small"]."\" class=\"icon\" width=\"15\" height=\"15\" alt=\"\" />";
	else $sentense = constant("GM_LANG_".$helpText);
	$output = "";
	if (($view!="preview")&&($_SESSION["show_context_help"])){
		if ($helpText=="qm_ah"){
			if ($gm_user->userIsAdmin()){
				 $output .= " <a class=\"error help\" tabindex=\"0\" href=\"javascript:";
				 if ($show_desc == "") $output .= $help;
				 else if ($use_print_text) $output .= print_text($show_desc, 0, 1);
				 else if (stristr(constant("GM_LANG_".$show_desc), "\"")) $output .= preg_replace('/\"/','\'', constant("GM_LANG_".$show_desc));
				 else  $output .= strip_tags(constant("GM_LANG_".$show_desc));
				 $output .= "\" onclick=\"helpPopup('$help'); return false;\">".$sentense."</a> \n";
			}
		}
		else {
			$output .= " <a class=\"help\" tabindex=\"0\" href=\"javascript: ";
			if ($show_desc == "") $output .= $help;
			else if ($use_print_text) $output .= print_text($show_desc, 0, 1);
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
 * print_text($mytext, 0, 1);
 * @param string $help		The variable that needs to be processed.
 * @param int $level		The position of the embedded variable
 * @param int $noprint		The switch if the text needs to be printed or returned
 */
function print_text($help, $level=0, $noprint=0){
	 global $GEDCOM_TITLE, $LANGUAGE;
	 global $GUESS_URL, $UpArrow;
	 global $repeat, $thumbnail, $xref, $pid, $LANGUAGE;
	
	 if (!isset($_SESSION["DEBUG_LANG"])) $DEBUG_LANG = "no";
	 else $DEBUG_LANG = $_SESSION["DEBUG_LANG"];
	 if ($DEBUG_LANG == "yes") print "[LANG_DEBUG] Variable called: ".$help." at level: ".$level."<br /><br />";
	
	 $sentence = "";
	 if ($level>0) {
		 // check nested var
		 if (isset($$help)) $sentence = $$help;
		 // check constant
		 else if (defined($help)) $sentence = constant($help);
		 // check constant with prefix
		 else if (defined("GM_".$help)) $sentence = constant("GM_".$help);
		 // check fact constant
		 else if (defined("GM_FACT_".$help)) $sentence = constant("GM_FACT_".$help);
		 // check langvar
		 else if (defined("GM_LANG_".$help)) $sentence = constant("GM_LANG_".$help);
		 else (!defined("GM_LANG_".$help) ? $sentence = GetString($help, $LANGUAGE) : $sentence = constant("GM_LANG_".$help));
	 }
	 if (empty($sentence)) {
		  if ($noprint == 2) {
			  $sentence = $help;
	  	  }
	  	  else {
			  if (!defined("GM_LANG_".$help)) $sentence = GetString($help, $LANGUAGE);
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
		  $value = print_text($newreplace, $level+1);
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
			  $replace_text = "<span class=\"helpstart\">".Str2Upper($replace)."</span>";
			  $sentence = str_replace("~".$replace."~", $replace_text, $sentence);
		  } else break;
	 }
	 if ($noprint>0) return $sentence;
	 if ($level>0) return $sentence;
	 print $sentence;
}
function print_help_index($help){

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
			$var = print_text($items[1],0,1);
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
	 if ($ch>0) print "<table width=\"100%\"><tr><td style=\"vertical-align: top;\"><ul>";
	 $i=0;
	 foreach ($help_sorted as $k => $help_item){
		print "<li>".$k."</li>";
		$i++;
		if ($i==ceil($ch/2)) print "</ul></td><td style=\"vertical-align: top;\"><ul>";
	 }
	 if ($ch>0) print "</ul></td></tr></table>";
}
/**
 * prints a JavaScript popup menu
 *
 * This function will print the DHTML required
 * to create a JavaScript Popup menu.  The $menu
 * parameter is an array that looks like this
 * $menu["label"] = "Charts";
 * $menu["labelpos"] = "down"; // tells where the text should be positioned relative to the picture options are up down left right
 * $menu["icon"] = "images/pedigree.gif";
 * $menu["hovericon"] = "images/pedigree2.gif";
 * $menu["link"] = "pedigree.php";
 * $menu["accesskey"] = "Z"; // optional accesskey
 * $menu["class"] = "menuitem";
 * $menu["hoverclass"] = "menuitem_hover";
 * $menu["flyout"] = "down"; // options are up down left right
 * $menu["items"] = array(); // an array of like menu items
 * $menu["onclick"] = "return javascript";  // java script to run on click
 * @author Genmod Development Team
 * @param array $menu the menuitems array to print
 */
function PrintFactMenu($menu, $parentmenu="") {
	$conv = array(
		'label'=>'label',
		'labelpos'=>'labelpos',
		'icon'=>'icon',
		'hovericon'=>'hovericon',
		'link'=>'link',
		'accesskey'=>'accesskey',
		'class'=>'class',
		'hoverclass'=>'hoverclass',
		'flyout'=>'flyout',
		'submenuclass'=>'submenuclass',
		'onclick'=>'onclick'
	);
	$obj = new Menu();
	if ($menu == 'separator') {
		$obj->isSeperator();
		$obj->printMenu();
		return;
	}
	$items = false;
	foreach ($menu as $k=>$v) {
		if ($k == 'items' && is_array($v) && count($v) > 0) $items = $v;
		else {
			if (isset($conv[$k])){
				if ($v != '') {
					$obj->$conv[$k] = $v;
				}
			}
		}
	}
	if ($items !== false) {
		foreach ($items as $sub) {
			$sobj = new Menu();
			if ($sub == 'separator') {
				$sobj->isSeperator();
				$obj->addSubmenu($sobj);
				continue;
			}
			foreach ($sub as $k2=>$v2) {
				if (isset($conv[$k2])) {
					if ($v2 != '') {
						$sobj->$conv[$k2] = $v2;
					}
				}
			}
			$obj->addSubmenu($sobj);
		}
	}
	$obj->printMenu();
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
					$newtext = preg_replace("/(".$query1.")/i", "<span class=\"search_hit\">$1</span>", $newtext);
				}
				else if (preg_match("/(".Str2Upper($query1).")/", Str2Upper($text))) {
					$nlen = strlen($query1);
					$npos = strpos(Str2Upper($text), Str2Upper($query1));
					$newtext = substr_replace($newtext, "</span>", $npos+$nlen, 0);
					$newtext = substr_replace($newtext, "<span class=\"search_hit\">", $npos, 0);
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
			$newtext = preg_replace("/(".$query1.")/i", "<span class=\"search_hit\">$1</span>", $newtext);
			}
			else if (preg_match("/(".Str2Upper($query1).")/", Str2Upper($text))) {
			$nlen = strlen($query1);
			$npos = strpos(Str2Upper($text), Str2Upper($query1));
			$newtext = substr_replace($newtext, "</span>", $npos+$nlen, 0);
			$newtext = substr_replace($newtext, "<span class=\"search_hit\">", $npos, 0);
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
						$newtext = preg_replace("/(".$query1.")/i", "<span class=\"search_hit\">$1</span>", $newtext);
					}
					else if (preg_match("/(".Str2Upper($query1).")/", Str2Upper($text))) {
						$nlen = strlen($query1);
						$npos = strpos(Str2Upper($text), Str2Upper($query1));
						$newtext = substr_replace($newtext, "</span>", $npos+$nlen, 0);
						$newtext = substr_replace($newtext, "<span class=\"search_hit\">", $npos, 0);
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
						$newtext = preg_replace("/(".$query1.")/i", "<span class=\"search_hit\">$1</span>", $newtext);
					}
					else if (preg_match("/(".Str2Upper($query1).")/", Str2Upper($text))) {
						$nlen = strlen($query1);
						$npos = strpos(Str2Upper($text), Str2Upper($query1));
						$newtext = substr_replace($newtext, "</span>", $npos+$nlen, 0);
						$newtext = substr_replace($newtext, "<span class=\"search_hit\">", $npos, 0);
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
						$newtext = preg_replace("/(".$query1.")/i", "<span class=\"search_hit\">$1</span>", $newtext);
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
 * Print age of parents
 *
 * @param string $pid	child ID
 * @param string $bdate	child birthdate
 */
function print_parents_age($pid, $bdate) {
	global $GM_IMAGES;
	
	if (GedcomConfig::$SHOW_PARENTS_AGE) {
		$famids = FindFamilyIds($pid);
		// dont show age of parents if more than one family (ADOPtion)
		if (count($famids)==1) {
			$father_text = "";
			$mother_text = "";
			$parents = FindParents($famids[0]["famid"]);
			// father
			$spouse = $parents["HUSB"];
			if ($spouse && PrivacyFunctions::showFact("BIRT", $spouse)) {
				$age = ConvertNumber(GetAge(FindPersonRecord($spouse), $bdate, false));
				if (10<$age && $age<80) $father_text = "<img src=\"".GM_IMAGE_DIR."/" . $GM_IMAGES["sex"]["small"] . "\" title=\"" . GM_LANG_father . "\" alt=\"" . GM_LANG_father . "\" class=\"sex_image\" />$age";
			}
			// mother
			$spouse = $parents["WIFE"];
			if ($spouse && PrivacyFunctions::showFact("BIRT", $spouse)) {
				$age = ConvertNumber(GetAge(FindPersonRecord($spouse), $bdate, false));
				if (10<$age && $age<80) $mother_text = "<img src=\"".GM_IMAGE_DIR."/" . $GM_IMAGES["sexf"]["small"] . "\" title=\"" . GM_LANG_mother . "\" alt=\"" . GM_LANG_mother . "\" class=\"sex_image\" />$age";
			}
			if ((!empty($father_text)) || (!empty($mother_text))) print "<span class=\"age\">".$father_text.$mother_text."</span>";
		}
	}
}
/**
 * print fact DATE TIME
 *
 * @param string $factrec	gedcom fact record
 * @param boolean $anchor	option to print a link to calendar
 * @param boolean $time		option to print TIME value
 * @param string $fact		optional fact name (to print age)
 * @param string $pid		optional person ID (to print age)
 * @param string $indirec	optional individual record (to print age)
 */
function print_fact_date($factrec, $anchor=false, $time=false, $fact=false, $pid=false, $indirec=false, $prt=true) {

	$prtstr = "";
	$ct = preg_match("/2 DATE (.+)/", $factrec, $match);
	if ($ct>0) {
		$prtstr .= " ";
		// link to calendar ==> $anchor is never set to true
		if ($anchor) $prtstr .= GetDateUrl($match[1]);
		// simple date
		else $prtstr .= GetChangedDate(trim($match[1]));
		// time
		if ($time) {
			$timerec = GetSubRecord(2, "2 TIME", $factrec);
			if (empty($timerec)) $timerec = GetSubRecord(2, "2 DATE", $factrec);
			$tt = preg_match("/[2-3] TIME (.*)/", $timerec, $tmatch);
			if ($tt>0) $prtstr .= " - <span class=\"date\">".$tmatch[1]."</span>";
		}
		if ($fact and $pid) {
			// age of parents at child birth
			if ($fact=="BIRT") print_parents_age($pid, $match[1]);
			// age at event
			else if ($fact!="CHAN") {
				if (!$indirec) $indirec=FindPersonRecord($pid);
				// do not print age after death
				$deatrec=GetSubRecord(1, "1 DEAT", $indirec);
				if ((CompareFacts($factrec, $deatrec)!=1)||(strstr($factrec, "1 DEAT"))) $prtstr .= GetAge($indirec,$match[1]);
			}
		}
		$prtstr .= " ";
	}
	else {
		// 1 DEAT Y with no DATE => print YES
		// 1 DEAT N is not allowed
		// It is not proper GEDCOM form to use a N(o) value with an event tag to infer that it did not happen.
		if (preg_match("/^1\s(BIRT|DEAT|MARR|DIV|CHR|CREM|BURI)\sY/", $factrec) && !preg_match("/\n2\s(DATE|PLAC)/", $factrec)) $prtstr .= GM_LANG_yes."&nbsp;";
	}
	// gedcom indi age
	$ages=array();
	$agerec = GetSubRecord(2, "2 AGE", $factrec);
	$daterec = GetSubRecord(2, "2 DATE", $factrec);
	if (empty($agerec)) $agerec = GetSubRecord(3, "3 AGE", $daterec);
	$ages[0] = $agerec;
	// gedcom husband age
	$husbrec = GetSubRecord(2, "2 HUSB", $factrec);
	if (!empty($husbrec)) $agerec = GetSubRecord(3, "3 AGE", $husbrec);
	else $agerec = "";
	$ages[1] = $agerec;
	// gedcom wife age
	$wiferec = GetSubRecord(2, "2 WIFE", $factrec);
	if (!empty($wiferec)) $agerec = GetSubRecord(3, "3 AGE", $wiferec);
	else $agerec = "";
	$ages[2] = $agerec;
	// print gedcom ages
	foreach ($ages as $indexval=>$agerec) {
		if (!empty($agerec)) {
			$prtstr .= "<span class=\"label\">";
			if ($indexval==1) $prtstr .= GM_LANG_husband;
			else if ($indexval==2) $prtstr .= GM_LANG_wife;
			else $prtstr .= GM_FACT_AGE;
			$prtstr .= "</span>: ";
			$age = GetAgeAtEvent(substr($agerec,5));
			$prtstr .= PrintReady($age);
			$prtstr .= " ";
		}
	}
	if ($prt) {
		print $prtstr;
		if (!empty($prtstr)) return true;
		else return false;
	}
	else return $prtstr;
}
/**
 * print fact PLACe TEMPle STATus
 *
 * @param string $factrec	gedcom fact record
 * @param boolean $anchor	option to print a link to placelist
 * @param boolean $sub		option to print place subrecords
 * @param boolean $lds		option to print LDS TEMPle and STATus
 */
function print_fact_place($factrec, $anchor=false, $sub=false, $lds=false, $prt=true) {
	global $TEMPLE_CODES;

	$printed = false;
	$out = false;
	$prtstr = "";
	$ct = preg_match("/2 PLAC (.*)/", $factrec, $match);
	if ($ct>0) {
		$printed = true;
		$prtstr .= "&nbsp;";
		// Split on chinese comma 239 188 140
		$match[1] = preg_replace("/".chr(239).chr(188).chr(140)."/", ",", $match[1]);
		$levels = preg_split("/,/", $match[1]);
		if ($anchor) {
			$place = trim($match[1]);
			$place = preg_replace("/\,(\w+)/",", $1", $place);
			// reverse the array so that we get the top level first
			$levels = array_reverse($levels);
			$prtstr .= "<a href=\"placelist.php?action=show&amp;";
			foreach($levels as $pindex=>$ppart) {
				 // routine for replacing ampersands
				 $ppart = preg_replace("/amp\%3B/", "", trim($ppart));
//				 print "parent[$pindex]=".htmlentities($ppart)."&amp;";
				 $prtstr .= "parent[$pindex]=".urlencode($ppart)."&amp;";			}
			$prtstr .= "level=".count($levels);
			$prtstr .= "\"> ";
			if (HasChinese($place)) $prtstr .= PrintReady($place."&nbsp;(".GetPinYin($place).")");
			else $prtstr .= PrintReady($place);
			$prtstr .= "</a>";
		}
		else {
			$prtstr .= " -- ";
			for ($level=0; $level < GedcomConfig::$SHOW_PEDIGREE_PLACES; $level++) {
				if (!empty($levels[$level])) {
					if ($level>0) $prtstr .= ", ";
					$prtstr .= PrintReady($levels[$level]);
				}
			}
			if (HasChinese($match[1])) {
				$ptext = "(";
				for ($level=0; $level < GedcomConfig::$SHOW_PEDIGREE_PLACES; $level++) {
					if (!empty($levels[$level])) {
						if ($level>0) $ptext .= ", ";
						$ptext .= GetPinYin($levels[$level]);
					}
				}
				$ptext .= ")";
				$prtstr .= " ".PrintReady($ptext);
			}
		}
	}
	$ctn=0;
	if ($sub) {
		$placerec = GetSubRecord(2, "2 PLAC", $factrec);
		if (!empty($placerec)) {
			$rorec = GetSubRecord(3, "3 ROMN", $placerec);
			if (!empty($rorec)) {
				$roplac = GetGedcomValue("ROMN", 3, $rorec);
				if (!empty($roplac)) {
					if ($ct>0) $prtstr .= " - ";
					$prtstr .= " ".PrintReady($roplac);
					$rotype = GetGedcomValue("TYPE", 4, $rorec);
					if (!empty($rotype)) {
						$prtstr .= " ".PrintReady("(".$rotype.")");
					}
				}
			}
			$cts = preg_match("/\d _HEB (.*)/", $placerec, $match);
			if ($cts>0) {
				if ($ct>0) $prtstr .= " - ";
				$prtstr .= " ".PrintReady($match[1]);
			}
			$map_lati="";
			$cts = preg_match("/\d LATI (.*)/", $placerec, $match);
			if ($cts>0) {
				$map_lati = trim($match[1]);
				$prtstr .= "<br />".GM_FACT_LATI.": ".$match[1];
			}
			$map_long="";
			$cts = preg_match("/\d LONG (.*)/", $placerec, $match);
			if ($cts>0) {
				$map_long = trim($match[1]);
				$prtstr .= " ".GM_FACT_LONG.": ".$match[1];
			}
			if (!empty($map_lati) and !empty($map_long)) {
				$prtstr .= " <a target=\"_BLANK\" href=\"http://www.mapquest.com/maps/map.adp?searchtype=address&formtype=latlong&latlongtype=decimal&latitude=".$map_lati."&longitude=".$map_long."\"><img src=\"images/mapq.gif\" border=\"0\" alt=\"Mapquest &copy;\" title=\"Mapquest &copy;\" /></a>";
				if (is_numeric($map_lati) && is_numeric($map_long)) {
					$prtstr .= " <a target=\"_BLANK\" href=\"http://maps.google.com/maps?spn=.2,.2&ll=".$map_lati.",".$map_long."\"><img src=\"images/bubble.gif\" border=\"0\" alt=\"Google Maps &copy;\" title=\"Google Maps &copy;\" /></a>";
				}
				else $prtstr .= " <a target=\"_BLANK\" href=\"http://maps.google.com/maps?q=".$map_lati.",".$map_long."\"><img src=\"images/bubble.gif\" border=\"0\" alt=\"Google Maps &copy;\" title=\"Google Maps &copy;\" /></a>";
				$prtstr .= " <a target=\"_BLANK\" href=\"http://www.multimap.com/map/browse.cgi?lat=".$map_lati."&lon=".$map_long."&scale=icon=x\"><img src=\"images/multim.gif\" border=\"0\" alt=\"Multimap &copy;\" title=\"Multimap &copy;\" /></a>";
				$prtstr .= " <a target=\"_BLANK\" href=\"http://www.terraserver.com/imagery/image_gx.asp?cpx=".$map_long."&cpy=".$map_lati."&res=30&provider_id=340\"><img src=\"images/terrasrv.gif\" border=\"0\" alt=\"TerraServer &copy;\" title=\"TerraServer &copy;\" /></a>";
			}
			$ctn = preg_match("/\d NOTE (.*)/", $placerec, $match);
			if ($ctn>0) {
				// To be done: part of returnstring of this function
				print_fact_notes($placerec, 3);
				$out = true;
			}
		}
	}
	if ($lds) {
		$ct = preg_match("/2 TEMP (.*)/", $factrec, $match);
		if ($ct>0) {
			$tcode = trim($match[1]);
			if (array_key_exists($tcode, $TEMPLE_CODES)) {
				$prtstr .= "<br />".GM_LANG_temple.": ".$TEMPLE_CODES[$tcode];
			}
			else {
				$prtstr .= "<br />".GM_LANG_temple_code.$tcode;
			}
		}
		$ct = preg_match("/2 STAT (.*)/", $factrec, $match);
		if ($ct>0) {
			$prtstr .= "<br />".GM_LANG_status.": ";
			$prtstr .= trim($match[1]);
		}
	}
	if ($prt) {
		print $prtstr;
		return $printed;
	}
	else return $prtstr;
}
/**
 * print first major fact for an Individual
 *
 * @param string $key	indi pid
 */
function print_first_major_fact($key, $indirec="", $prt=true, $break=false) {
	global $GM_BASE_DIRECTORY, $factsfile, $LANGUAGE;
	
	$majorfacts = array("BIRT", "CHR", "BAPM", "DEAT", "BURI", "BAPL", "ADOP");
	if (empty($indirec)) $indirec = FindPersonRecord($key);
	$retstr = "";
	foreach ($majorfacts as $indexval => $fact) {
		$factrec = GetSubRecord(1, "1 $fact", $indirec);
		if (strlen($factrec)>7 and PrivacyFunctions::showFact("$fact", $key) and !PrivacyFunctions::FactViewRestricted($key, $factrec)) {
			if ($break) $retstr .= "<br />";
			else $retstr .= " -- ";
			$retstr .= "<i>";
			if (defined("GM_LANG_".$fact)) $retstr .= constant("GM_LANG_".$fact);
			else if (defined("GM_FACT_".$fact)) $retstr .= constant("GM_FACT_".$fact);
			else $retstr .= $fact;
			$retstr .= " ";
			$retstr .= print_fact_date($factrec, false, false, false, false, false, false);
			$retstr .= print_fact_place($factrec, false, false, false, false);
			$retstr .= "</i>";
			break;
		}
	}
	if ($prt) {
		print $retstr;
		return $fact;
	}
	else return addslashes($retstr);
}


/**
 * javascript declaration for calendar popup
 *
 * @param none
 */
function init_calendar_popup() {
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


/**
 * Print a list of surnames
 *
 * A table with columns is printed from an array of surnames. This can be individuals
 * or families.
 *
 * @todo		Add statistics for private and hidden links
 * @author	Genmod Development Team
 * @param		array		$personlist	The array with names to be printed
 * @param		string		$page		The page the links should point to
 */
function PrintSurnameList($surnames, $page, $allgeds="no", $resturl="") {
	global $TEXT_DIRECTION;
	global $surname_sublist, $indilist, $indi_hide, $indi_total;
	
	if (stristr($page, "aliveinyear")) {
		$aiy = true;
		global $indi_dead, $indi_alive, $indi_unborn;
	}
	else $aiy = false;
	
	$i = 0;
	$count_indi = 0;
	$col = 1;
	$count = count($surnames);
	if ($count == 0) return;
	else if ($count>36) $col=4;
	else if ($count>18) $col=3;
	else if ($count>6) $col=2;
	$newcol=ceil($count/$col);
	print "<table class=\"center $TEXT_DIRECTION\"><tr>";
	print "<td class=\"shade1 list_value wrap\">\n";
	
	// Surnames with starting and ending letters in 2 text orientations is shown in
	// a wrong way on the page with different orientation from the orientation of the first name letter
	foreach($surnames as $surname=>$namecount) {
		if (begRTLText($namecount["name"])) {
 			print "<div class =\"rtl\" dir=\"rtl\">&nbsp;<a href=\"".$page."?alpha=".urlencode($namecount["alpha"])."&amp;surname_sublist=".$surname_sublist."&amp;surname=".urlencode($namecount["name"]).$resturl;
 			if ($allgeds == "yes") print "&amp;allgeds=yes";
 			print "\">&nbsp;";
 			if (HasChinese($namecount["name"])) print PrintReady($namecount["name"]." (".GetPinYin($namecount["name"]).")");
 			else print PrintReady($namecount["name"]);
 			print "&rlm; - [".($namecount["match"])."]&rlm;";
		}
		else if (substr($namecount["name"], 0, 4) == "@N.N") {
			print "<div class =\"ltr\" dir=\"ltr\">&nbsp;<a href=\"".$page."?alpha=".$namecount["alpha"]."&amp;surname_sublist=$surname_sublist&amp;surname=@N.N.".$resturl;
 			if ($allgeds == "yes") print "&amp;allgeds=yes";
			print "\">&nbsp;".GM_LANG_NN . "&lrm; - [".($namecount["match"])."]&lrm;&nbsp;";
		}
		else {
			print "<div class =\"ltr\" dir=\"ltr\">&nbsp;<a href=\"".$page."?alpha=".urlencode($namecount["alpha"])."&amp;surname_sublist=$surname_sublist&amp;surname=".urlencode($namecount["name"]).$resturl;
 			if ($allgeds == "yes") print "&amp;allgeds=yes";
			print "\">";
 			if (HasChinese($namecount["name"])) print PrintReady($namecount["name"]." (".GetPinYin($namecount["name"]).")");
			else print PrintReady($namecount["name"]);
			print "&lrm; - [".($namecount["match"])."]&lrm;";
		}

 		print "</a></div>\n";
		$count_indi += $namecount["match"];
		$i++;
		if ($i==$newcol && $i<$count) {
			print "</td><td class=\"shade1 list_value wrap\">\n";
			$newcol=$i+ceil($count/$col);
		}
	}
	if ($aiy) $indi_total = $indi_alive + $indi_dead + $indi_unborn + count($indi_hide);
	else if (is_array($indi_total)) $indi_total = count($indi_total);
	print "</td>\n";
	if ($count>1 || count($indi_hide)>0) {
		print "</tr><tr><td colspan=\"$col\" class=\"center\">&nbsp;";
		if (GedcomConfig::$SHOW_MARRIED_NAMES && $count>1) print GM_LANG_total_names." ".$count_indi."<br />";
		if (isset($indi_total) && $count>1) print GM_LANG_total_indis." ".$indi_total."&nbsp;";
		if ($count>1 && count($indi_hide)>0) print "--&nbsp;";
		if (count($indi_hide)>0) print GM_LANG_hidden." ".count($indi_hide);
		if ($count>1 && $aiy) {
			print "<br />".GM_LANG_unborn."&nbsp;".$indi_unborn;
			print "&nbsp;--&nbsp;".GM_LANG_alive."&nbsp;".$indi_alive;
			print "&nbsp;--&nbsp;".GM_LANG_dead."&nbsp;".$indi_dead;
		}
		if ($count>1) print "<br />".GM_LANG_surnames." ".$count;
		print "</td>\n";
	}
	print "</tr></table>";
}
/**
 * Print a list of family names
 *
 * A table with columns is printed from an array of names.
 * A distinction is made between a list for the find page or a
 * page listing.
 *
 * @todo		Add statistics for private and hidden links
 * @author	Genmod Development Team
 * @param		array	$personlist	The array with names to be printed
 * @param		boolean	$print_all	Set to yes to print all individuals
 * @param		boolean	$find		Set to yes to print links for the find pages
 */
function PrintFamilyList($familylist, $print_all=true, $find=false, $allgeds="no") {
	global $TEXT_DIRECTION, $COMBIKEY;
	global $surname_sublist, $show_all, $famlist, $fam_hide, $alpha, $falpha;
	global $firstname_alpha, $fam_private, $show_all_firstnames, $surname;
	
	$count = count($familylist);
	
	print "<table class=\"center ".$TEXT_DIRECTION."\"><tr>";
	// NOTE: The list is really long so divide it up again by the first letter of the first name
	if (GedcomConfig::$ALPHA_INDEX_LISTS && $count > GedcomConfig::$ALPHA_INDEX_LISTS && $print_all == true) {
		$firstalpha = array();
		foreach($familylist as $gid=>$fam) {
			$fam = $famlist[$gid];
			$names = preg_split("/[,+] ?/", $fam["name"]);
			$letter = Str2Upper(GetFirstLetter(trim($names[1])));
			if (!isset($firstalpha[$letter])) {
				$firstalpha[$letter] = array("letter"=>$letter, "ids"=>$gid);
			}
			else $firstalpha[$letter]["ids"] .= ",".$gid;
			if (isset($names[2])&&isset($names[3])) {
				$letter = Str2Upper(GetFirstLetter(trim($names[2])));
				if ($letter==$alpha) {
					$letter = Str2Upper(GetFirstLetter(trim($names[3])));
					if (!isset($firstalpha[$letter])) {
						$firstalpha[$letter] = array("letter"=>$letter, "ids"=>$gid);
					}
					else $firstalpha[$letter]["ids"] .= ",".$gid;
				}
			}
		}
		// NOTE: Sort the family array
		uasort($firstalpha, "LetterSort");
		print "<td class=\"shade1 list_value wrap center\" colspan=\"2\">\n";
		print GM_LANG_first_letter_fname."<br />\n";
		foreach($firstalpha as $letter=>$list) {
			$pass = false;
			if ($letter != "@") {
				if (!isset($fstartalpha) && !isset($falpha)) {
					$fstartalpha = $letter;
					$falpha = $letter;
				}
				// NOTE: Print the link letter
				print "<a href=\"famlist.php?";
				// NOTE: only include the alpha letter when not showing the ALL list
				if ($show_all == "no") print "alpha=".urlencode($alpha)."&amp;";
				if ($surname_sublist == "yes" && isset($surname)) print "surname=".$surname."&amp;";
				print "falpha=".urlencode($letter)."&amp;show_all=$show_all&amp;surname_sublist=".$surname_sublist;
				if ($allgeds == "yes") print "&amp;allgeds=yes";
				print "\">";
				// NOTE: Red color for the chosen letter otherwise simply print the letter
				if (($falpha==$letter)&&($show_all_firstnames=="no")) print "<span class=\"warning\">".$letter."</span>";
				else print $letter;
				print "</a> | \n";
			}
			if ($letter === "@") {
				$pass = TRUE;
			}
		}
		// NOTE: Print the Unknown text on the letter bar
		if ($pass == TRUE) {
			if (isset($falpha) && $falpha == "@") {
				print "<a href=\"famlist.php?alpha=".urlencode($alpha)."&amp;surname=".urlencode($surname)."&amp;falpha=@&amp;surname_sublist=yes";
				if ($allgeds == "yes") print "&amp;allgeds=yes";
				print "\"><span class=\"warning\">".PrintReady(GM_LANG_NN)."</span></a>";
			}
			else {
				print "<a href=\"famlist.php?alpha=".urlencode($alpha)."&amp;surname=".urlencode($surname)."&amp;falpha=@&amp;surname_sublist=yes";
				if ($allgeds == "yes") print "&amp;allgeds=yes";
				print "\">".PrintReady(GM_LANG_NN)."</a>";
			}
			if (GedcomConfig::$LISTS_ALL) print " | \n";
			$pass = FALSE;
		}
		if (GedcomConfig::$LISTS_ALL) {
			print "<a href=\"famlist.php?";
			// NOTE: only include the alpha letter when not showing the ALL list
			if ($show_all == "no") print "alpha=".urlencode($alpha)."&amp;";
			// NOTE: Include the surname if surnames are to be listed
			if ($allgeds == "yes") print "allgeds=yes&amp;";
			if ($surname_sublist == "yes" && isset($surname)) print "surname=".urlencode($surname)."&amp;";
			if ($show_all_firstnames=="yes") print "show_all_firstnames=no&amp;show_all=$show_all&amp;surname_sublist=$surname_sublist\"><span class=\"warning\">".GM_LANG_all."</span>\n";
			else print "show_all_firstnames=yes&amp;show_all=$show_all&amp;surname_sublist=$surname_sublist\">".GM_LANG_all."</a>\n";
		}
		print "</td></tr><tr>\n";
		if (isset($fstartalpha)) $falpha = $fstartalpha;
		// NOTE: Get only the names who start with the matching first letter
		if ($show_all_firstnames=="no") {
			$ffamlist = array();
			if (isset($firstalpha[$falpha])) {
				$ids = preg_split("/,/", $firstalpha[$falpha]["ids"]);
				foreach($ids as $indexval => $id) {
					$ffamlist[$id] = $famlist[$id];
				}
			}
			PrintFamilyList($ffamlist, false, false, $allgeds);
		}
		else PrintFamilyList($familylist, false, false, $allgeds);
	}
	else {
		uasort($familylist, "ItemSort");
		$i=0;
		print "<td class=\"shade1 list_value indilist\">\n";
		foreach($familylist as $gid => $fam) {
			$fam = $famlist[$gid];
			$fam["name"] = CheckNN($fam["name"]);
			$pass = false;
			if ($COMBIKEY) $gid = SplitKey($gid, "id");
			if (HasChinese($fam["name"])) $fam["name"] .= " (".GetFamilyAddDescriptor($gid, false, $fam["gedcom"], false).")";
			print_list_family($gid, array($fam["name"], $fam["gedfile"]));
			$i++;
			if ($i==ceil($count/2) && $count>8) print "</td><td class=\"shade1 list_value indilist\">\n";
		}
		print "</td>\n";
		$i = 0;
		$count = count($familylist);
		$col = 1;
		if ($count>36) $col=4;
		else if ($count>18) $col=3;
		else if ($count>6) $col=2;
		$newcol=ceil($count/$col);
		if ($count>1 || count($fam_hide)>0) {
			print "</tr><tr><td colspan=\"$col\" align=\"center\">&nbsp;";
			if ($count>1) print GM_LANG_total_fams." ".count($famlist)."&nbsp;";
			if ($count>1 && count($fam_hide)>0) print "--&nbsp;";
			if (count($fam_hide)>0) print GM_LANG_hidden." ".count($fam_hide);
//			if ($count>1) print "<br />".GM_LANG_surnames." ".$count;
			print "</td>\n";
		}
	}
	print "</tr></table>";
}


function ExpandUrl($text) {
  // Some versions of RFC3987 have an appendix B which gives the following regex
  // (([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?
  // This matches far too much while a "precise" regex is several pages long.
  // This is a compromise.
  $URL_REGEX='((https?|ftp]):)(//([^\s/?#<\)\,]*))?([^\s?#<\)\,]*)(\?([^\s#<\)\,]*))?(#(\S*))?';

  return preg_replace_callback(
    '/'.addcslashes("(?!>)$URL_REGEX(?!</a>)", '/').'/i',
    create_function( // Insert <wbr/> codes into the replaced string
      '$m',
      'if (strlen($m[0])>30) $url = substr($m[0],0,30).".....";
      else $url = $m[0];
      return "<a href=\"".$m[0]."\" target=\"blank\">".preg_replace("/\b/", "<wbr/>", $url)."</a>";'
    ),
    $text
  );
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
		if ($filterev == "EVEN") print ">".GM_LANG_custom_event."</option>\n";
		else print ">".constant("GM_FACT_".$event)."</option>\n";
	}
}
?>
