<?php
/**
 * Family Tree Clippings Cart
 *
 * Uses the $_SESSION["cart"] to store the ids of clippings to download
 * @TODO print a message if people are not included due to privacy
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
 * @subpackage Charts
 * @version $Id$
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

if (!isset($ENABLE_CLIPPINGS_CART)) $ENABLE_CLIPPINGS_CART = $PRIV_HIDE;
if ($ENABLE_CLIPPINGS_CART===true) $ENABLE_CLIPPING_CART=$PRIV_PUBLIC;
if ($ENABLE_CLIPPINGS_CART < $Users->getUserAccessLevel())
{
  header("Location: index.php");
  exit;
}
if (!isset($action)) $action="";
if (!isset($id)) $id = "";
if (!isset($remove)) $remove = "no";
if (!isset($convert)) $convert = "no";
if (!isset($type)) $type = "";

$id = CleanInput($id);

// -- print html header information
print_header($gm_lang["clip_cart"]);
print "\r\n\t<h3>".$gm_lang["clippings_cart"]."</h3>";


function same_group($a, $b) {
	static $carray;
	
	// order: indi, fam, obje, note, sour, repo
	if (!isset($carray)) $carray = array_flip(array("indi", "fam", "obje", "note", "sour", "repo"));
	if ($a['type']==$b['type']) return strnatcasecmp($a['id'], $b['id']);
	else return ($carray[$a['type']] > $carray[$b['type']]);
}

function id_in_cart($id) {
	global $cart, $GEDCOMID;
	
	if (!isset($cart[$GEDCOMID])) return false;
	$ct = count($cart[$GEDCOMID]);
	for($i=0; $i<$ct; $i++) {
		$temp = $cart[$GEDCOMID][$i];
		if ($temp['id']==$id) {
			return true;
		}
	}
	return false;
}

function add_clipping($clipping) {
	global $cart, $gm_lang, $SHOW_SOURCES, $Users, $GEDCOMID;
	
	if (($clipping['id']==false)||($clipping['id']=="")) return false;

	if (!id_in_cart($clipping['id'])) {
		if ($clipping['type']=="indi") {
			if (showLivingNameById($clipping['id'])) {
				$cart[$GEDCOMID][]=$clipping;
				$gedrec = FindGedcomRecord($clipping['id']);
				$st = preg_match_all("/\d (\w+) @(.*)@/", $gedrec, $match, PREG_SET_ORDER);
				for($i=0; $i<$st; $i++) {
					$clipping = array();
					$clipping['type'] = strtolower($match[$i][1]);
					if (in_array($clipping['type'], array("note", "sour", "repo", "obje"))) {
						$clipping['id'] = $match[$i][2];
						if (displayDetailsById($clipping['id'], $clipping["type"], 1, true)) {
							add_clipping($clipping);
						}
					}
				}
			}
			else return false;
		}
		else if ($clipping['type']=="fam") {
			$parents = FindParents($clipping['id']);
			if (showLivingNameById($parents['HUSB']) && showLivingNameById($parents['WIFE'])) {
				$cart[$GEDCOMID][]=$clipping;
				$gedrec = FindGedcomRecord($clipping['id']);
				$st = preg_match_all("/\d (\w+) @(.*)@/", $gedrec, $match, PREG_SET_ORDER);
				for($i=0; $i<$st; $i++) {
					$clipping = array();
					$clipping['type'] = strtolower($match[$i][1]);
					if (in_array($clipping['type'], array("note", "sour", "repo", "obje"))) {
						$clipping['id'] = $match[$i][2];
						if (displayDetailsById($clipping['id'], $clipping["type"], 1, true)) {
							add_clipping($clipping);
						}
					}
				}
			}
			else return false;
		}
		else {
			if (displayDetailsById($clipping['id'], $clipping['type'], 1, true)) {
				$cart[$GEDCOMID][]=$clipping;
				$gedrec = FindGedcomRecord($clipping['id']);
				$nt = preg_match_all("/\d (\w+) @(.*)@/", $gedrec, $match, PREG_SET_ORDER);
				for($i=0; $i<$nt; $i++) {
					$clipping = array();
					$clipping['type'] = strtolower($match[$i][1]);
					if (in_array($clipping['type'], array("note", "sour", "repo", "obje"))) {
						$clipping['id'] = $match[$i][2];
						add_clipping($clipping);
					}
				}
			}
		}
	}
	return true;
}

// --------------------------------- Recursive function to traverse the tree
function add_family_descendancy($famid) {
	global $cart;

	if (!$famid) return;
	//print "add_family_descendancy(" . $famid . ")<br />";					# --------------
	$famrec = FindFamilyRecord($famid);
	if ($famrec) {
		$parents = FindParentsInRecord($famrec);
		if (!empty($parents["HUSB"])) {
			$clipping = array();
			$clipping['type']="indi";
			$clipping['id']=$parents["HUSB"];
			add_clipping($clipping);
		}
		if (!empty($parents["WIFE"])) {
			$clipping = array();
			$clipping['type']="indi";
			$clipping['id']=$parents["WIFE"];
			add_clipping($clipping);
		}
		$num = preg_match_all("/1\s*CHIL\s*@(.*)@/", $famrec, $smatch,PREG_SET_ORDER);
		for($i=0; $i<$num; $i++) {
			$cfamids = FindSfamilyIds($smatch[$i][1]);
			if (count($cfamids)>0) {
				foreach($cfamids as $indexval => $fcfamid) {
					$cfamid = $fcfamid["famid"];
					if (!id_in_cart($cfamid)) {
						$clipping = array();
						$clipping['type']="fam";
						$clipping['id']=$cfamid;
						$ret = add_clipping($clipping);		// add the childs family
						add_family_descendancy($cfamid);	// recurse on the childs family
					}
				}
			}
			else {
				$clipping = array();
				$clipping['type']="indi";
				$clipping['id']=$smatch[$i][1];
				add_clipping($clipping);
			}
		}
	}
}

function add_family_members($famid) {
	global $cart;
	$parents = FindParents($famid);
	if (!empty($parents["HUSB"])) {
		$clipping = array();
		$clipping['type']="indi";
		$clipping['id']=$parents["HUSB"];
		add_clipping($clipping);
	}
	if (!empty($parents["WIFE"])) {
		$clipping = array();
		$clipping['type']="indi";
		$clipping['id']=$parents["WIFE"];
		add_clipping($clipping);
	}
	$famrec = FindFamilyRecord($famid);
	if ($famrec) {
		$num = preg_match_all("/1\s*CHIL\s*@(.*)@/", $famrec, $smatch,PREG_SET_ORDER);
		for($i=0; $i<$num; $i++) {
			$clipping = array();
			$clipping['type']="indi";
			$clipping['id']=$smatch[$i][1];
			add_clipping($clipping);
		}
	}
}

//-- recursively adds direct-line ancestors to cart
function add_ancestors_to_cart($pid) {
	global $cart;
	$famids = FindFamilyIds($pid);
	if (count($famids)>0) {
		foreach($famids as $indexval => $ffamid) {
			$famid = $ffamid["famid"];
			$clipping = array();
			$clipping['type']="fam";
			$clipping['id']=$famid;
			$ret = add_clipping($clipping);
			if ($ret) {
				$parents = FindParents($famid);
				if (!empty($parents["HUSB"])) {
					$clipping = array();
					$clipping['type']="indi";
					$clipping['id']=$parents["HUSB"];
					add_clipping($clipping);
					add_ancestors_to_cart($parents["HUSB"]);
				}
				if (!empty($parents["WIFE"])) {
					$clipping = array();
					$clipping['type']="indi";
					$clipping['id']=$parents["WIFE"];
					add_clipping($clipping);
					add_ancestors_to_cart($parents["WIFE"]);
				}
			}
		}
	}
}

//-- recursively adds direct-line ancestors and their families to the cart
function add_ancestors_to_cart_families($pid) {
	global $cart;
	$famids = FindFamilyIds($pid);
	if (count($famids)>0) {
		foreach($famids as $indexval => $ffamid) {
			$famid = $ffamid["famid"];
			$clipping = array();
			$clipping['type']="fam";
			$clipping['id']=$famid;
			$ret = add_clipping($clipping);
			if ($ret) {
				$parents = FindParents($famid);
				if (!empty($parents["HUSB"])) {
					$clipping = array();
					$clipping['type']="indi";
					$clipping['id']=$parents["HUSB"];
					$ret = add_clipping($clipping);
					add_ancestors_to_cart_families($parents["HUSB"]);
				}
				if (!empty($parents["WIFE"])) {
					$clipping = array();
					$clipping['type']="indi";
					$clipping['id']=$parents["WIFE"];
					$ret = add_clipping($clipping);
					add_ancestors_to_cart_families($parents["WIFE"]);
				}
				$famrec = FindFamilyRecord($famid);
				if ($famrec) {
					$num = preg_match_all("/1\s*CHIL\s*@(.*)@/", $famrec, $smatch,PREG_SET_ORDER);
					for($i=0; $i<$num; $i++) {
						$clipping = array();
						$clipping['type']="indi";
						$clipping['id']=$smatch[$i][1];
						add_clipping($clipping);
					}
				}
			}
		}
	}
}

//---------------------------- End function definition
if ($action=='add') {
	if ($type=='fam') {
		print "\r\n<form action=\"clippings.php\" method=\"get\">\r\n".$gm_lang["which_links"]."<br />";
		print "\r\n\t<input type=\"hidden\" name=\"id\" value=\"$id\" />";
		print "\r\n\t<input type=\"hidden\" name=\"type\" value=\"$type\" />";
		print "\r\n\t<input type=\"hidden\" name=\"action\" value=\"add1\" />";
		print "\r\n\t<input type=\"radio\" name=\"others\" value=\"none\" />".$gm_lang["just_family"]."<br />";
		print "\r\n\t<input type=\"radio\" name=\"others\" value=\"parents\" />".$gm_lang["parents_and_family"]."<br />";
		print "\r\n\t<input type=\"radio\" name=\"others\" selected value=\"members\" />".$gm_lang["parents_and_child"]."<br />";
		print "\r\n\t<input type=\"radio\" name=\"others\" value=\"descendants\" />".$gm_lang["parents_desc"]."<br />";
		print "\r\n\t<input type=\"submit\"  value=\"".$gm_lang["continue"]."\" /><br />\r\n\t</form>";
	}
	else if ($type=='indi') {
		print "\r\n<form action=\"clippings.php\" method=\"get\">\r\n".$gm_lang["which_p_links"]."<br />";
		print "\r\n\t<input type=\"hidden\" name=\"id\" value=\"$id\" />";
		print "\r\n\t<input type=\"hidden\" name=\"type\" value=\"$type\" />";
		print "\r\n\t<input type=\"hidden\" name=\"action\" value=\"add1\" />";
		print "\r\n\t<input type=\"radio\" name=\"others\" value=\"none\" />".$gm_lang["just_person"]."<br />";
		print "\r\n\t<input type=\"radio\" name=\"others\" value=\"parents\" />".$gm_lang["person_parents_sibs"]."<br />";
		print "\r\n\t<input type=\"radio\" name=\"others\" value=\"ancestors\" />".$gm_lang["person_ancestors"]."<br />";
		print "\r\n\t<input type=\"radio\" name=\"others\" value=\"ancestorsfamilies\" />".$gm_lang["person_ancestor_fams"]."<br />";
		print "\r\n\t<input type=\"radio\" name=\"others\" selected value=\"members\" />".$gm_lang["person_spouse"]."<br />";
		print "\r\n\t<input type=\"radio\" name=\"others\" value=\"descendants\" />".$gm_lang["person_desc"]."<br />";
		print "\r\n\t<input type=\"submit\"  value=\"".$gm_lang["continue"]."\" /><br />\r\n\t</form>";
	}
	else {
		$action='add1';
	}
}

if ($action=='add1') {
	$clipping = array();
	$clipping['type']=$type;
	$clipping['id']=$id;
	$ret = add_clipping($clipping);
	if ($ret) {
		if ($type=='fam') {
			if ($others=='parents') {
				$parents = FindParents($id);
				if (!empty($parents["HUSB"])) {
					$clipping = array();
					$clipping['type']="indi";
					$clipping['id']=$parents["HUSB"];
					$ret = add_clipping($clipping);
				}
				if (!empty($parents["WIFE"])) {
					$clipping = array();
					$clipping['type']="indi";
					$clipping['id']=$parents["WIFE"];
					$ret = add_clipping($clipping);
				}
			}
			else if ($others=="members") {
				add_family_members($id);
			}
			else if ($others=="descendants") {
				add_family_descendancy($id);
			}
		}
		else if ($type=='indi') {
			if ($others=='parents') {
				$famids = FindFamilyIds($id);
				foreach($famids as $indexval => $ffamid) {
					$famid = $ffamid["famid"];
					$clipping = array();
					$clipping['type']="fam";
					$clipping['id']=$famid;
					$ret = add_clipping($clipping);
					if ($ret) add_family_members($famid);
				}
			}
			else if ($others=='ancestors') {
				add_ancestors_to_cart($id);
			}
			else if ($others=='ancestorsfamilies') {
				add_ancestors_to_cart_families($id);
			}
			else if ($others=='members') {
				$famids = FindSfamilyIds($id);
				foreach($famids as $indexval => $ffamid) {
					$famid = $ffamid["famid"];
					$clipping = array();
					$clipping['type']="fam";
					$clipping['id']=$famid;
					$ret = add_clipping($clipping);
					if ($ret) add_family_members($famid);
				}
			}
			else if ($others=='descendants') {
				$famids = FindSfamilyIds($id);
				foreach($famids as $indexval => $ffamid) {
					$famid = $ffamid["famid"];
					$clipping = array();
					$clipping['type']="fam";
					$clipping['id']=$famid;
					$ret = add_clipping($clipping);
					if ($ret) add_family_descendancy($famid);
				}
			}
		}
	}
}
else if($action=='remove') {
	$ct = count($cart[$GEDCOMID]);
	for($i=$item+1; $i<$ct; $i++) {
		$cart[$GEDCOMID][$i-1] = $cart[$GEDCOMID][$i];
	}
	unset($cart[$ct-1]);
}
else if($action=='empty') {
	$cart[$GEDCOMID] = array();
	$_SESSION["clippings"] = "";
}
else if($action=='download') {
	$path = substr($SCRIPT_NAME, 0, strrpos($SCRIPT_NAME, "/"));
	if (empty($path)) $path="/";
	if ($path[strlen($path)-1]!="/") $path .= "/";
	if ($SERVER_URL[strlen($SERVER_URL)-1] == "/")
	{
	  $dSERVER_URL = substr($SERVER_URL, 0, strlen($SERVER_URL) - 1);
	}
	else $dSERVER_URL = $SERVER_URL;
	usort($cart[$GEDCOMID], "same_group");
	$media = array();
	$mediacount=0;
	$ct = count($cart[$GEDCOMID]);
	$filetext = "0 HEAD\r\n1 SOUR Genmod\r\n2 NAME Genmod Online Genealogy\r\n2 VERS $VERSION $VERSION_RELEASE\r\n1 DEST DISKETTE\r\n1 DATE ".date("j M Y")."\r\n2 TIME ".date("h:i:s")."\r\n";
	$filetext .= "1 GEDC\r\n2 VERS 5.5\r\n2 FORM LINEAGE-LINKED\r\n1 CHAR $CHARACTER_SET\r\n";
	$head = FindGedcomRecord("HEAD");
	$placeform = trim(GetSubRecord(1, "1 PLAC", $head));
	if (!empty($placeform)) $filetext .= $placeform."\r\n";
//	else $filetext .= "1 PLAC\r\n2 FORM ".$gm_lang["default_form"]."\r\n";
	else $filetext .= "1 PLAC\r\n2 FORM "."City, County, State/Province, Country"."\r\n";
	if ($convert=="yes") {
		$filetext = preg_replace("/UTF-8/", "ANSI", $filetext);
		$filetext = utf8_decode($filetext);
	}
	for($i=0; $i<$ct; $i++)
	{
		$clipping = $cart[$GEDCOMID][$i];
		$record = FindGedcomRecord($clipping['id']);
		$record = privatize_gedcom($record);
		$record = RemoveCustomTags($record, $remove);
		if ($convert=="yes") $record = utf8_decode($record);
		if ($clipping['type']=='indi') {
			$ft = preg_match_all("/1 FAMC @(.*)@/", $record, $match, PREG_SET_ORDER);
			for ($k=0; $k<$ft; $k++) {
				if (!id_in_cart($match[$k][1])) {
					$record = preg_replace("/1 FAMC @".$match[$k][1]."@.*/", "", $record);
				}
			}
			$ft = preg_match_all("/1 FAMS @(.*)@/", $record, $match, PREG_SET_ORDER);
			for ($k=0; $k<$ft; $k++) {
				if (!id_in_cart($match[$k][1])) {
					$record = preg_replace("/1 FAMS @".$match[$k][1]."@.*/", "", $record);
				}
			}
			$ft = preg_match_all("/\d FILE (.*)/", $record, $match, PREG_SET_ORDER);
			for ($k=0; $k<$ft; $k++) {
				$filename = $MediaFS->CheckMediaDepth(trim($match[$k][1]));
				$media[$mediacount]=$filename;
				$filename = substr($match[$k][1], strrpos($match[$k][1], "\\"));
				$mediacount++;
				$record = preg_replace("|(\d FILE )".addslashes($match[$k][1])."|", "$1".$filename, $record);
			}
			$filetext .= trim($record)."\r\n";
			$filetext .= "1 SOUR @SGM1@\r\n";
			$filetext .= "2 PAGE ".$dSERVER_URL."/individual.php?pid=".$clipping['id']."\r\n";
			$filetext .= "2 DATA\r\n";
			$filetext .= "3 TEXT ".$gm_lang["indi_downloaded_from"]."\r\n";
			$filetext .= "4 CONT ".$dSERVER_URL."/individual.php?pid=".$clipping['id']."\r\n";
		}
		else if ($clipping['type']=='fam') {
			$ft = preg_match_all("/1 CHIL @(.*)@/", $record, $match, PREG_SET_ORDER);
			for ($k=0; $k<$ft; $k++) {
				 if (!id_in_cart($match[$k][1])) {
				   /* if the child is not in the list delete the record of it */
				   $record = preg_replace("/1 CHIL @".$match[$k][1]."@.*/", "", $record);
				 }
			}

			$ft = preg_match_all("/1 HUSB @(.*)@/", $record, $match, PREG_SET_ORDER);
			for ($k=0; $k<$ft; $k++)
			{
				 if (!id_in_cart($match[$k][1]))
				 {
				   /* if the husband is not in the list delete the record of him */
				   $record = preg_replace("/1 HUSB @".$match[$k][1]."@.*/", "", $record);
				 }
			}

			$ft = preg_match_all("/1 WIFE @(.*)@/", $record, $match, PREG_SET_ORDER);
			for ($k=0; $k<$ft; $k++)
			{
				 if (!id_in_cart($match[$k][1]))
				 {
				   /* if the wife is not in the list delete the record of her */
				   $record = preg_replace("/1 WIFE @".$match[$k][1]."@.*/", "", $record);
				 }
			}

			$ft = preg_match_all("/\d FILE (.*)/", $record, $match, PREG_SET_ORDER);
			for ($k=0; $k<$ft; $k++) {
				$filename = $MediaFS->CheckMediaDepth($match[$k][1]);
				 	$media[$mediacount]=$filename;
				 		$mediacount++;
			   	 	$record = preg_replace("@(\d FILE )".addslashes($match[$k][1])."@", "$1".$filename, $record);
			}

			$filetext .= trim($record)."\r\n";
			$filetext .= "1 SOUR @SGM1@\r\n";
			$filetext .= "2 PAGE ".$dSERVER_URL.$path."family.php?famid=".$clipping['id']."\r\n";
			$filetext .= "2 DATA\r\n";
			$filetext .= "3 TEXT ".$gm_lang["family_downloaded_from"]."\r\n";
			$filetext .= "4 CONT ".$dSERVER_URL."/family.php?famid=".$clipping['id']."\r\n";
		}
		else if($clipping['type']=="sour") {
			$filetext .= trim($record)."\r\n";
			$filetext .= "1 NOTE ".$gm_lang["source_downloaded_from"]."\r\n";
			$filetext .= "2 CONT ".$dSERVER_URL."/source.php?sid=".$clipping['id']."\r\n";
		}
		else if($clipping['type']=="repo") {
			$filetext .= trim($record)."\r\n";
			$filetext .= "1 NOTE ".$gm_lang["repo_downloaded_from"]."\r\n";
			$filetext .= "2 CONT ".$dSERVER_URL."/repo.php?rid=".$clipping['id']."\r\n";
		}
		else if($clipping['type']=="note") {
			$filetext .= trim($record)."\r\n";
			$filetext .= "1 CONT ".$gm_lang["note_downloaded_from"]."\r\n";
			$filetext .= "1 CONT ".$dSERVER_URL."/note.php?oid=".$clipping['id']."\r\n";
		}
		else $filetext .= trim($record)."\r\n";
	}
	$filetext .= "0 @SGM1@ SOUR\r\n";
	$tuser = $Users->getUser($CONTACT_EMAIL);
	if ($tuser) {
		$filetext .= "1 AUTH ".$tuser->firstname." ".$tuser->lastname."\r\n";
	}
	$filetext .= "1 TITL ".$HOME_SITE_TEXT."\r\n";
	$filetext .= "1 ABBR ".$HOME_SITE_TEXT."\r\n";
	$filetext .= "1 PUBL ".$HOME_SITE_URL."\r\n";
	$filetext .= "0 TRLR\r\n";
	//-- make sure the gedcom doesn't have any empty lines
	$filetext = preg_replace("/(\r?\n)+/", "\r\n", $filetext);
	//-- make sure DOS line endings are used
	$filetext = preg_replace("/\r?\n/", "\r\n", $filetext);

	$_SESSION["clippings"] = $filetext;
	print "\r\n\t<br /><br />".$gm_lang["download"]."<br /><br /><ul><li>".$gm_lang["gedcom_file"]."</li><ul><li><a href=\"clippings_download.php\">clipping.ged</a></li></ul><br />";
	if ($mediacount>0) {
		// -- create zipped media file====> is a todo
		print "<li>".$gm_lang["media_files"]."</li><ul>";
		for($m=0; $m<$mediacount; $m++) {
			print "<li><a href=\"".$MEDIA_DIRECTORY."$media[$m]\">".substr($media[$m], strrpos($media[$m], "/"))."</a></li>";
		}
		print "</ul>";
	}
	print "</ul><br /><br />";
}
if (!isset($cart[$GEDCOMID]) || count($cart[$GEDCOMID]) == 0) {

	// NOTE: display helptext when cart is empty
	if ($action!='add') print_text("help_clippings.php");
	
	// -- end new lines
	print "\r\n\t\t<br /><br />".$gm_lang["cart_is_empty"]."<br /><br />";
}
else {
	$ct = count($cart[$GEDCOMID]);
	print "\r\n\t<table class=\"list_table\">\r\n\t\t<tr>\r\n\t\t\t<td class=\"list_label\">".$gm_lang["type"]."</td><td class=\"list_label\">".$gm_lang["id"]."</td><td class=\"list_label\">".$gm_lang["name_description"]."</th><td class=\"list_label\">".$gm_lang["remove"]."</td>\r\n\t\t</tr>";
	for($i=0; $i<$ct; $i++) {
		print "\r\n\t\t<tr>\r\n\t\t<td class=\"list_value\">";
		$clipping = $cart[$GEDCOMID][$i];
		if($clipping['type']=='indi') print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]."\" border=\"0\" alt=\"".$gm_lang["individual"]."\" />";
		else if($clipping['type']=='fam') print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["sfamily"]["small"]."\" border=\"0\" alt=\"".$gm_lang["family"]."\" />";
		else if($clipping['type']=='sour') print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["source"]["small"]."\" border=\"0\" alt=\"".$gm_lang["source"]."\" />";
		else if($clipping['type']=='repo') print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["repository"]["small"]."\" border=\"0\" alt=\"".$gm_lang["repo"]."\" />";
		else if($clipping['type']=='obje') print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["media"]["small"]."\" border=\"0\" alt=\"".$gm_lang["media"]."\" />";
		else if($clipping['type']=='note') print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["note"]["other"]."\" border=\"0\" alt=\"".$gm_lang["note"]."\" />";
		print "</td><td class=\"list_value\">".$clipping['id']."</td><td class=\"list_value\">";

		$id_ok = true;
		if($clipping['type']=='indi') {
			if (showLivingNameByID($clipping['id'])) $id_ok = true;
			else $id_ok = false;
			if ($id_ok) $dName = GetSortableName($clipping['id']); else $dName = $gm_lang["person_private"];
		  	$names = preg_split("/,/", $dName);
			$dName = CheckNN($names);
		  	print "<a href=\"individual.php?pid=".$clipping['id']."\">";
			if (HasChinese($dName)) print PrintReady($dName." (".GetPinYin($dName).")");
			else print PrintReady($dName);
		  	print "</a>";
		}
		else {
			if($clipping['type']=='fam') {
			    $famrec = FindFamilyRecord($clipping['id']);

			    $husb_ok = true;
			    $ct01 = preg_match("/1 HUSB @(.*)@/", $famrec, $match);
		    	if ($ct01 > 0) {
					if (showLivingNameByID($match[1])) $husb_ok = true;
					else $husb_ok = false;
		    	}

		    	$wife_ok = true;
		    	$ct02 = preg_match("/1 WIFE @(.*)@/", $famrec, $match);
		    	if ($ct02 > 0) {
		      		if (showLivingNameByID($match[1])) $wife_ok = true;
		      		else $wife_ok = false;
		    	}
		    	if (($husb_ok) && ($wife_ok)) $dName = GetFamilyDescriptor($clipping['id']); else $dName = $gm_lang["family_private"];
		    	print "<a href=\"family.php?famid=".$clipping['id']."\">";
				if (HasChinese($dName) && $husb_ok && $wife_ok) print PrintReady($dName." (".GetFamilyAddDescriptor($clipping['id']).")");
				else print PrintReady($dName);
		    	print "</a>";
		  	} 
		  	else {
			    if($clipping['type']=='sour') {
		      		print "<a href=\"source.php?sid=".$clipping['id']."\">".PrintReady(GetSourceDescriptor($clipping['id']))."</a>";
		    	}
		    	else {
				    if($clipping['type']=='note') {
						require_once("includes/controllers/note_ctrl.php");
						$note_controller->init($clipping['id']);
						print "<a href=\"note.php?oid=".$clipping['id']."\">";
						if ($note_controller->note->canDisplayDetails()) print $note_controller->note->GetTitle(40)." (".$clipping['id'].")";
						else print $gm_lang["private"];
			      		print "</a>";
			    	}
				  	else {
					    if($clipping['type']=='repo') {
				      		print "<a href=\"repo.php?rid=".$clipping['id']."\">".PrintReady(GetRepoDescriptor($clipping['id']))."</a>";
		    			}
					  	else {
						    if($clipping['type']=='obje') {
					      		print "<a href=\"mediadetail.php?mid=".$clipping['id']."\">".PrintReady(GetMediaDescriptor($clipping['id']))."</a>";
		    				}
	    				}
	    			}
		  		}
			}
		}
		print "</td><td class=\"list_value\"><a href=\"clippings.php?action=remove&item=$i\">".$gm_lang["remove"]."</a>\r\n\t\t</tr>";
	}
	print "\r\n\t</table>";
	if ($action != 'download') {
		print "<form method=\"post\" action=\"clippings.php\">\n<input type=\"hidden\" name=\"action\" value=\"download\" />\n";
		?>
		<table>
		<tr><td><input type="checkbox" name="convert" value="yes" /></td><td><?php print $gm_lang["utf8_to_ansi"]; print_help_link("utf8_ansi_help", "qm"); ?></td></tr>
		<tr><td><input type="checkbox" name="remove" value="yes" checked="checked" /></td><td><?php print $gm_lang["remove_custom_tags"]; print_help_link("remove_tags_help", "qm"); ?></td></tr>
		</table>
		<input type="submit"  value="<?php print $gm_lang["download_now"]; ?>" />
		<?php
		print_help_link("clip_download_help", "qm");
		print "<br />";
	}
	print "\r\n\t<br /><a href=\"clippings.php?action=empty\">".$gm_lang["empty_cart"]."  "."</a>";
	print_help_link("empty_cart_help", "qm");
}
if (isset($_SESSION["cart"])) $_SESSION["cart"]=$cart;
print_footer();
?>