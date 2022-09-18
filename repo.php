<?php
/**
 * Displays the details about a repository record.
 * Also shows how many sources reference this repository.
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
 * @version $Id: repo.php,v 1.39 2009/03/18 19:43:48 sjouke Exp $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

if (empty($action)) $action="";
if (empty($show_changes)) $show_changes = "yes";
if (empty($rid)) $rid = " ";
$rid = CleanInput($rid);

if (!DisplayDetailsByID($rid, "REPO")) {
	print_header($gm_lang["repo_info"]);
	print_privacy_error($CONTACT_EMAIL);
	print_footer();
	exit;
}

global $GM_IMAGES;

$nonfacts = array();

$repo = FindRepoRecord($rid);

if ((!isset($show_changes) || $show_changes != "no") && $Users->UserCanEdit($gm_username)) $canshow = true;
else $canshow = false;

$name = "";
$reponew = false;
$repodeleted = false;
$repochanged = false;
$haschanges = GetChangeData(true, $rid, true);

if ($canshow && $haschanges) {
	$repochanged = true;
	$rec = GetChangeData(false, $rid, true, "gedlines");
	$newreporec = $rec[$GEDCOM][$rid];
	if (empty($newreporec) && !empty($repo)) $repodeleted = true;
	if (empty($repo) && !empty($newreporec)) $reponew = true;
}


//-- make sure we have the true id from the record
$ct = preg_match("/0 @(.*)@/", $repo, $match);
if ($ct>0) $rid = trim($match[1]);

if (!$repodeleted && $repochanged) {
	if (ShowFact("NAME", $rid)) {
		$ct = preg_match("/1 NAME (.*)/", $newreporec, $match);
 		if ($ct>0) $name = $match[1];
	}
	if (ShowFact("ROMN", $rid)) {
		$ct = preg_match("/\d ROMN (.*)/", $newreporec, $match);
 		if ($ct>0) $add_descriptor = $match[1];
	}
	if (ShowFact("_HEB", $rid)) {
		$ct = preg_match("/\d _HEB (.*)/", $newreporec, $match);
 		if ($ct>0) $add_descriptor = $match[1];
	}
	if (isset($add_descriptor)) $name .= " - ".$add_descriptor;
}
else {
	$name = GetRepoDescriptor($rid);
	$add_descriptor = GetAddRepoDescriptor($rid);
	if ($add_descriptor) $name .= " - ".$add_descriptor;
}

print_header("$name - $rid - ".$gm_lang["repo_info"]);

?>
<div id="show_changes"></div>

<script type="text/javascript">
<!--
	function show_gedcom_record() {
		var recwin = window.open("gedrecord.php?pid=<?php print $rid ?>", "", "top=0,left=0,width=300,height=400,scrollbars=1,scrollable=1,resizable=1");
	}
	function showchanges() {
		sndReq('show_changes', 'set_show_changes', 'set_show_changes', '<?php if ($show_changes == "yes") print "no"; else print "yes"; ?>');
		window.location.reload();
	}
	
	function reload() {
		window.location.reload();
	}

//-->
</script>
<table width="100%"><tr><td>
<?php
print "\n\t<span class=\"name_head\">".PrintReady($name);

if ($SHOW_ID_NUMBERS) print " &lrm;($rid)&lrm;";
print "</span><br />";
if($SHOW_COUNTER) {
	print "\n<br /><br /><span style=\"margin-left: 3px;\">".$gm_lang["hit_count"]."&nbsp;".$hits."</span>\n";
}
print "<br />";

if (empty($repo) && !$repochanged) {
	print "&nbsp;&nbsp;&nbsp;<span class=\"warning\"><i>".$gm_lang["no_results"]."</i></span>";
	print "</td></tr></table><br /><br /><br /><br /><br /><br />\n";
	print_footer();
	exit;
}
print "</td></tr></table>\n";

?>
<script type="text/javascript">
<!--
function tabswitch(n) {
	sndReq('dummy', 'remembertab', 'xref', '<?php print JoinKey($rid, $GEDCOMID); ?>' , 'tab_tab', n, 'type', 'repo');
	if (n==4) n = 0;
	var tabid = new Array('0','facts','sources','actions');
	// show all tabs ?
	var disp='none';
	if (n==0) disp='block';
	// reset all tabs areas
	for (i=1; i<tabid.length; i++) document.getElementById(tabid[i]).style.display=disp;
	if ('<?php echo $view; ?>' != 'preview') {
		// current tab area
		if (n>0) document.getElementById(tabid[n]).style.display='block';
		// empty tabs
		for (i=0; i<tabid.length; i++) {
			var elt = document.getElementById('door'+i);
			if (document.getElementById('no_tab'+i)) { // empty ?
				if (<?php if ($Users->userCanEdit($gm_username)) echo 'true'; else echo 'false';?>) {
					elt.style.display='block';
					elt.style.opacity='0.4';
					elt.style.filter='alpha(opacity=40)';
				}
				else elt.style.display='none'; // empty and not editable ==> hide
			}
			else elt.style.display='block';
		}
		// current door
		for (i=0; i<tabid.length; i++) {
			document.getElementById('door'+i).className='shade1 rela';
		}
		document.getElementById('door'+n).className='shade1';
		return false;
	}
}
//-->
</script>
<?php
// -- array of sources
$mysourcelist = array();
$links = GetRepoLinks($rid, "SOUR");
if (is_array($links) && count($links) > 0) {
	$linklist = "'".implode("','", $links)."'";
	$mysourcelist = GetSourceList($linklist);
	uasort($mysourcelist, "SourceSort");
}
$sour_count = count($mysourcelist);

if (is_object($Actions)) {	
	// Start of todo list
	$actionlist = $Actions->GetActionListByRepo($rid);
	$action_count = count($actionlist);
}
else $action_count = 0;

if ($view != "preview") {
	?>
	<div class="door center">
	<dl>
	<dd id="door1"><a href="javascript:;" onclick="tabswitch(1)" ><?php print $gm_lang["facts"]?></a></dd>
	<dd id="door2"><a href="javascript:;" onclick="tabswitch(2)" ><?php print $gm_lang["sour_linking"]." (".$sour_count.")";?></a></dd>
	<dd id="door3"><a href="javascript:;" onclick="tabswitch(3)" ><?php print $gm_lang["action_linking"]." (".$action_count.")";?></a></dd>
	<dd id="door0"><a href="javascript:;" onclick="tabswitch(0)" ><?php print $gm_lang["all"]?></a></dd>
	</dl>
	</div>
	<?php
}
print "<div id=\"dummy\"></div><br /><br />";
// Facts
print "<div id=\"facts\" class=\"tab_page\" style=\"display:none;\" >";

$repofacts = array();
$allreposubs = GetAllSubrecords($repo, "", true, false, false);
foreach ($allreposubs as $key => $subrecord) {
	$ft = preg_match("/1\s(\w+)(.*)/", $subrecord, $match);
	if ($ft>0) {
		$fact = $match[1];
		$gid = trim(str_replace("@", "", $match[2]));
	}
	else {
		$fact = "";
		$gid = "";
	}
	$fact = trim($fact);
	if (!isset($count[$fact])) $count[$fact] = 1;
	else $count[$fact]++;
	if (!empty($fact)) {
		$repofacts[] = array($fact, $subrecord, $count[$fact]);
	}
}


//-- get new repo records
$newfacts = RetrieveNewFacts($rid);
foreach ($newfacts as $key => $newfact) {
	$ft = preg_match("/1\s(\w+)(.*)/", $newfact, $match);
	if ($ft>0) {
		$fact = $match[1];
		$gid = trim(str_replace("@", "", $match[2]));
	}
	else {
		$fact = "";
		$gid = "";
	}
	$fact = trim($fact);
	if (!isset($count[$fact])) $count[$fact] = 1;
	else $count[$fact]++;
	if (!empty($fact)) {
		$repofacts[] = array($fact, $newfact, $count[$fact], "new");
	}
}
SortFacts($repofacts, "REPO");
print "\n<table class=\"facts_table\">";
foreach($repofacts as $key => $value) {
	$fact = trim($value[0]);
	if (!empty($fact)) {
		if (!$canshow) $styleadd = "";
		else if ($repodeleted) $styleadd = "change_old";
		else if (IsChangedFact($rid, $value[1])) $styleadd = "change_old";
		else if (isset($value[3]) && $value[3] == "new") $styleadd = "change_new";
		else $styleadd = "";
		if ($fact=="OBJE") {
			print_main_media($value[1], $rid, 0, $value[2], false, $styleadd);
		}
		else if ($fact=="NOTE") {
			print_main_notes($value[1], 1, $rid, $value[2], $styleadd);
			if ($styleadd == "change_old" && !$repodeleted) print_main_notes(RetrieveChangedFact($rid, $value[0], $value[1]), 1, $rid, $value[2], "change_new");
		}
		else {
			print_fact($value[1], $rid, $value[0], $value[2], false, $styleadd);
			if ($styleadd == "change_old" && !$repodeleted) {
				$tfact = RetrieveChangedFact($rid, $value[0], $value[1]);
				if (!empty($tfact)) print_fact($tfact, $rid, $value[0], $value[2], $repo, "change_new");
			}
		}
	}
}
//-- new fact link
if (($view!="preview") &&($Users->userCanEdit($gm_username))) {
	PrintAddNewFact($rid, $repofacts, "REPO");
}
print "</table>\n\n<br /></div>";
// End of repo facts

if ($view == "preview") print "<br /><span class=\"label\">".$gm_lang["other_repo_records"]."</span>";


// Start of link list

print "<div id=\"sources\" class=\"tab_page\" style=\"display:none;\" >";

if ($sour_count>0) {
	print "\n\t<table class=\"list_table $TEXT_DIRECTION\">\n\t\t<tr><td class=\"shade2 center\"";
	if($sour_count>12)	print " colspan=\"2\"";
	print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["source"]["small"]."\" border=\"0\" title=\"".$gm_lang["titles_found"]."\" alt=\"".$gm_lang["titles_found"]."\" />&nbsp;&nbsp;";
	print $gm_lang["titles_found"];
	print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
	$i=1;
	// -- print the array
	foreach ($mysourcelist as $key => $value) {
		print_list_source($key, $value);
		if ($i==ceil($sour_count/2) && $sour_count>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
		$i++;
	}
	print "\n\t\t</ul></td>\n\t\t";
	print "</tr>";
	if ($sour_count>0) { 
		print "<tr><td>";
		print $gm_lang["total_sources"]." ".$sour_count;
		if (count($source_hide)>0) print "&nbsp;--&nbsp;".$gm_lang["hidden"]." ".count($source_hide);
		print "</td></tr>";
	}
	print "</table>";
}
else print "<div id=\"no_tab2\"></div>";
print "</div>";

// End of link list
print "<div id=\"actions\" class=\"tab_page\" style=\"display:none;\" >";
if (is_object($Actions)) {
	
	// Start of todo list
	print "\n\t<table class=\"list_table $TEXT_DIRECTION\">";
	print "<tr><td colspan=\"3\" class=\"shade2 center\">".$gm_lang["actionlist"]."</td></tr>";
	print "<tr><td class=\"shade2 center\">".$gm_lang["todo"]."</td><td class=\"shade2 center\">".$gm_lang["for"]."</td><td class=\"shade2 center\">".$gm_lang["status"]."</td></tr>";
	foreach ($actionlist as $key => $item) {
		print "<tr>";
		print "<td class=\"shade1 wrap\">".nl2br(stripslashes($item->text))."</td>";
		print "<td class=\"shade1\">";
		$gedrec = FindGedcomRecord($item->pid);
		if (empty($gedrec)) print $item->pid;
		else {
			$type = GetRecType($gedrec);
			switch($type) {
				case "INDI":
					print "<a href=\"individual.php?pid=".$item->pid."\">".GetPersonName($item->pid, $gedrec)."</a>";
				break;
				case "FAM":
					print "<a href=\"family.php?famid=".$item->pid."\">".GetFamilyDescriptor($item->pid, "", $gedrec)."</a>";
				break;
				case "SOUR":
					print "<a href=\"source.php?sid=".$item->pid."\">".GetSourceDescriptor($item->pid, $gedrec)."</a>";
				break;
			}
		}
		print "</td>";
		print "<td class=\"shade1\">".$gm_lang["action_".$item->status]."</td>";
		print "</tr>";
	}
	print "</table>";
}
else print "<div id=\"no_tab3\"></div>";
print "</div>";
// End of action list

print "<script type=\"text/javascript\">\n<!--\n";
if ($view == "preview") print "tabswitch(0)";
else if (isset($_SESSION["repo"][JoinKey($rid, $GEDCOMID)])) print "tabswitch(".$_SESSION["repo"][JoinKey($rid, $GEDCOMID)].")";
else print "tabswitch(1)";
print "\n//-->\n</script>\n";

print_footer();
?>