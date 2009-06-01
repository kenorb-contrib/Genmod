<?php
/**
 * Displays the details about a note record.
 * Also shows the links of other records to this note.
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

/**
 * Inclusion of the configuration file
*/
require("config.php");

/**
 * Inclusion of the note controller
*/
$note_controller = new NoteController();

if ($note_controller->isempty) {
	print_header($gm_lang["note_not_found"]);
	print "<span class=\"error\">".$gm_lang["note_not_found"]."</span>";
	print_footer();
	exit;
}

print_header($note_controller->getPageTitle());
	
?>
<div id="show_changes"></div>
<script type="text/javascript">
<!--
	function show_gedcom_record() {
		var recwin = window.open("gedrecord.php?pid=<?php print $oid ?>", "", "top=0,left=0,width=300,height=400,scrollbars=1,scrollable=1,resizable=1");
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
print "\n\t<span class=\"name_head\">";
print $note_controller->note->GetTitle(40, $note_controller->note->showchanges);
if ($SHOW_ID_NUMBERS) print " &lrm;($oid)&lrm;";
print "</span><br />";
if($SHOW_COUNTER) {
	print "\n<br /><br /><span style=\"margin-left: 3px;\">".$gm_lang["hit_count"]."&nbsp;".$hits."</span>\n";
}
print "<br />";

if ($note_controller->isempty && !$note_controller->note->changed) {
	print "&nbsp;&nbsp;&nbsp;<span class=\"warning\"><i>".$gm_lang["no_results"]."</i></span>";
	print "<br /><br /><br /><br /><br /><br />\n";
	print_footer();
	exit;
}

?>
<script type="text/javascript">
<!--
function tabswitch(n) {
	sndReq('dummy', 'remembertab', 'xref', '<?php print JoinKey($note_controller->note->xref, $GEDCOMID); ?>' , 'tab_tab', n, 'type', 'note');
	if (n==7) n = 0;
	var tabid = new Array('0','facts','individuals','families','sources','media','repositories');
	// show all tabs ?
	var disp='none';
	if (n==0) disp='block';
	// reset all tabs areas
	for (i=1; i<tabid.length; i++) document.getElementById(tabid[i]).style.display=disp;
	if ('<?php echo $note_controller->view; ?>' != 'preview') {
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

// Start of link list
//print "\n\t\t<br />";
//print_help_link("note_listbox_help", "qm", "other_note_records");
//print "<span class=\"facts_label\">".$gm_lang["other_note_records"]."</span>";
//flush();

// Get the link lists and see if there is any link
$note_controller->note->GetNoteIndis();
$indi_count = count($note_controller->note->indilist);
$note_controller->note->GetNoteFams();
$fam_count = count($note_controller->note->famlist);
$note_controller->note->GetNoteSources();
$sour_count = count($note_controller->note->sourcelist);
$note_controller->note->GetNoteMedia();
$media_count = count($note_controller->note->medialist);
$note_controller->note->GetNoteRepos();
$repo_count = count($note_controller->note->repolist);

if (!$note_controller->IsPrintPreview()) {
	// Print message is any changes to links are present
	if (HasUnapprovedLinks($oid)) print $gm_lang["unapproved_link"];
	?>
	<div class="door">
	<dl>
	<dd id="door1"><a href="javascript:;" onclick="tabswitch(1)" ><?php print $gm_lang["facts"]?></a></dd>
	<dd id="door2"><a href="javascript:;" onclick="tabswitch(2)" ><?php print $gm_lang["indi_linking"]." (".$indi_count.")";?></a></dd>
	<dd id="door3"><a href="javascript:;" onclick="tabswitch(3)" ><?php print $gm_lang["fam_linking"]." (".$fam_count.")";?></a></dd>
	<dd id="door4"><a href="javascript:;" onclick="tabswitch(4)" ><?php print $gm_lang["sour_linking"]." (".$sour_count.")";?></a></dd>
	<dd id="door5"><a href="javascript:;" onclick="tabswitch(5)" ><?php print $gm_lang["mm_linking"]." (".$media_count.")";?></a></dd>
	<dd id="door6"><a href="javascript:;" onclick="tabswitch(6)" ><?php print $gm_lang["repo_linking"]." (".$repo_count.")";?></a></dd>
	<dd id="door0"><a href="javascript:;" onclick="tabswitch(0)" ><?php print $gm_lang["all"]?></a></dd>
	</dl>
	</div>
	<?php
}
print "<div id=\"dummy\"></div><br /><br />";


// Facts
print "<div id=\"facts\" class=\"tab_page\" style=\"display:none;\" >";

$facts = $note_controller->note->GetNoteFacts();
print "\n<table class=\"facts_table\">";
$note_controller->note->PrintGeneralNote();

foreach($facts as $key => $value) {
	$fact = trim($value[0]);
	if (!empty($fact)) {
		if (!$note_controller->show_changes) $styleadd = "";
		else if ($note_controller->note->deleted) $styleadd = "change_old";
		else if (IsChangedFact($oid, $value[1])) $styleadd = "change_old";
		else if (isset($value[3]) && $value[3] == "new") $styleadd = "change_new";
		else $styleadd = "";
		$oid = $note_controller->note->xref;
		if ($fact == "SOUR") {
			if (!$note_controller->show_changes) {
				if ($note_controller->note->deleted) print_main_sources($value[1], 1, $oid, $value[2], "deleted");
				else print_main_sources($value[1], 1, $oid, $value[2], "", $note_controller->note->canedit);
			}
			else {
				if ($note_controller->note->deleted && $note_controller->note->showchanges) {
					print_main_sources($value[1], 1, $oid, $value[2], "change_old", $note_controller->note->canedit);
				}
				else {
					if ($note_controller->show_changes && !$note_controller->note->deleted && IsChangedFact($oid, $value[1])) {
						$adds = "";
						if (!isset($value[3]) || $value[3] != "new") {
							print_main_sources($value[1], 1, $oid, $value[2], "change_old", $note_controller->note->canedit);
						}
						$cts = preg_match("/1 _GMS @(.*)@/", $value[1], $matchs);
						if ($cts>0) $adds = $matchs[0]."\r\n";
						$newfact = RetrieveChangedFact($oid, $value[0], $value[1]);
						if (!empty($newfact)) print_main_sources($newfact, 1, $oid, $value[2], "change_new", $note_controller->note->canedit);
					}
					else if ($note_controller->show_changes && isset($value[3]) && $value[3] == "new" ) {
						print_main_sources($value[1], 1, $oid, $value[2], "change_new", $note_controller->note->canedit);
					}
					else print_main_sources($value[1], 1, $oid, $value[2], "", $note_controller->note->canedit);
				}
			}
		}
		else {
			if (!$note_controller->show_changes) {
				if ($note_controller->note->deleted) print_fact($value[1], $oid, $value[0], $value[2], $note_controller->note->gedrec, "deleted");
				else print_fact($value[1], $oid, $value[0], $value[2], $note_controller->note->gedrec, "", $note_controller->note->canedit);
			}
			else {
				if ($note_controller->note->deleted && $note_controller->show_changes) print_fact($value[1], $oid, $value[0], $value[2], $note_controller->note->gedrec, "change_old");
				else {
					if ($note_controller->show_changes && IsChangedFact($oid, $value[1])) {
						$adds = "";
						if (!isset($value[3]) || $value[3] != "new") {
							print_fact($value[1], $oid, $value[0], $value[2], $note_controller->note->gedrec, "change_old", $note_controller->note->canedit);
						}
						$cts = preg_match("/1 _GMS @(.*)@/", $value[1], $matchs);
						if ($cts>0) $adds = $matchs[0]."\r\n";
						$newfact = RetrieveChangedFact($oid, $value[0], $value[1]);
						if (!empty($newfact)) print_fact($newfact, $oid, $value[0], $value[2], $note_controller->note->gedrec, "change_new", $note_controller->note->canedit);
					}
					else if ($note_controller->show_changes && isset($value[3]) && $value[3] == "new" ) {
						print_fact($value[1], $oid, $value[0], $value[2], $note_controller->note->gedrec, "change_new", $note_controller->note->canedit);
					}
					else print_fact($value[1], $oid, $value[0], $value[2], $note_controller->note->gedrec, "", $note_controller->note->canedit);
				}
			}
		}
	}
}
//-- new fact link
if ($view != "preview" && $Users->userCanEdit($gm_username) && !$note_controller->note->deleted) {
	PrintAddNewFact($oid, $facts, "NOTE");
}
if (!$note_controller->view && $note_controller->canedit && $note_controller->note->canDisplayDetails() && !$note_controller->note->deleted) {
	print "<tr>";
	print "<td class=\"width20 shade2\">";
	print_help_link("add_source_help", "qm");
	print $gm_lang["add_source_lbl"]."</td>";
	print "<td class=\"shade1\"><a href=\"javascript: ".$gm_lang["add_source"]."\" onclick=\"add_new_record('".$note_controller->xref."','SOUR', 'add_source'); return false;\">".$gm_lang["add_source"]."</a><br /></td>";
	print "</tr>";
}

print "</table>\n\n<br />";
print "</div>";

// End of note info

if ($note_controller->IsPrintPreview()) print "<br /><span class=\"label\">".$gm_lang["other_note_records"]."</span>";


// -- array of individuals
print "<div id=\"individuals\" class=\"tab_page\" style=\"display:none;\" >";

if ($indi_count>0) {
	print "\n\t<table class=\"list_table $TEXT_DIRECTION\">\n\t\t<tr><td class=\"shade2 center\"";
	if($indi_count>12)	print " colspan=\"2\"";
	print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]."\" border=\"0\" title=\"".$gm_lang["individuals"]."\" alt=\"".$gm_lang["individuals"]."\" />&nbsp;&nbsp;";
	print $gm_lang["individuals"];
	print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
	$i=1;
	// -- print the array
	foreach ($note_controller->note->indilist as $key => $value) {
		// Check if we can display source references for this indi
		$addname = "";
		if (HasChinese($value["names"][0][0])) $addname = " (".GetSortableAddName($key, false).")";
		print_list_person($key, array(CheckNN(GetSortableName($key)).$addname, get_gedcom_from_id($value["gedfile"])));
		print "\n";
		if ($i==ceil($indi_count/2) && $indi_count>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
		$i++;
	}

	print "\n\t\t</ul></td>\n\t\t";

	print "</tr>";
		if ($indi_count>0) { 
			print "<tr><td>";
			print $gm_lang["total_indis"]." ".$indi_count;
			if (count($indi_hide)>0) print "&nbsp;--&nbsp;".$gm_lang["hidden"]." ".count($indi_hide);
			print "</td></tr>";
		}
	print "</table><br />";
}
else print "<div id=\"no_tab2\"></div>";
print "</div>";

// -- array of families
print "<div id=\"families\" class=\"tab_page\" style=\"display:none;\" >";

if ($fam_count>0) {
	print "\n\t<table class=\"list_table  $TEXT_DIRECTION\">\n\t\t<tr><td class=\"shade2 center\"";
	if($fam_count>12)	print " colspan=\"2\"";
	print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["sfamily"]["small"]."\" border=\"0\" title=\"".$gm_lang["families"]."\" alt=\"".$gm_lang["families"]."\" />&nbsp;&nbsp;";
	print $gm_lang["families"];
	print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
	$i=1;
	foreach ($note_controller->note->famlist as $key => $value) {
		$addname = "";
		if (HasChinese($value["name"])) $addname = " (".GetFamilyAddDescriptor($key, false, $value["gedcom"]).")";
		print_list_family($key, array(GetFamilyDescriptor($key).$addname, get_gedcom_from_id($value["gedfile"])));
		if ($i==ceil($fam_count/2) && $fam_count>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
		$i++;
	}
	print "\n\t\t</ul></td>\n\t\t";

	print "</tr>";
	if ($fam_count>0) { 
		print "<tr><td>";
		print $gm_lang["total_fams"]." ".$fam_count;
		if (count($fam_hide)>0) print "&nbsp;--&nbsp;".$gm_lang["hidden"]." ".count($fam_hide);
		print "</td></tr>";
	}
	print "</table><br />";
}
else print "<div id=\"no_tab3\"></div>";
print "</div>";

// -- array of sources
print "<div id=\"sources\" class=\"tab_page\" style=\"display:none;\" >";

if ($sour_count>0) {
	print "\n\t<table class=\"list_table  $TEXT_DIRECTION\">\n\t\t<tr><td class=\"shade2 center\"";
	if($sour_count>12)	print " colspan=\"2\"";
	print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["source"]["small"]."\" border=\"0\" title=\"".$gm_lang["sources"]."\" alt=\"".$gm_lang["sources"]."\" />&nbsp;&nbsp;";
	print $gm_lang["sources"];
	print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
	$i=1;
	// -- print the array
	foreach ($note_controller->note->sourcelist as $key => $value) {
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
	print "</table><br />";
}
else print "<div id=\"no_tab4\"></div>";
print "</div>";

// -- array of media
print "<div id=\"media\" class=\"tab_page\" style=\"display:none;\" >";

if ($media_count>0) {
	print "\n\t<table class=\"list_table  $TEXT_DIRECTION\">\n\t\t<tr><td class=\"shade2 center\"";
	if($media_count>12)	print " colspan=\"2\"";
	print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["media"]["small"]."\" border=\"0\" title=\"".$gm_lang["media"]."\" alt=\"".$gm_lang["media"]."\" />&nbsp;&nbsp;";
	print $gm_lang["media"];
	print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
	$i=1;
	foreach ($note_controller->note->medialist as $key => $value) {
		print_list_media($key, $value);
		if ($i==ceil($media_count/2) && $media_count>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
		$i++;
	}
	print "\n\t\t</ul></td>\n\t\t";

	print "</tr>";
	if ($media_count>0) { 
		print "<tr><td>";
		print $gm_lang["total_media"]." ".$media_count;
		if (count($media_hide)>0) print "&nbsp;--&nbsp;".$gm_lang["hidden"]." ".count($media_hide);
		print "</td></tr>";
	}
	print "</table><br />";
}
else print "<div id=\"no_tab5\"></div>";
print "</div>";

// -- array of repositories
print "<div id=\"repositories\" class=\"tab_page\" style=\"display:none;\" >";

if ($repo_count>0) {
	print "\n\t<table class=\"list_table  $TEXT_DIRECTION\">\n\t\t<tr><td class=\"shade2 center\"";
	if($repo_count>12)	print " colspan=\"2\"";
	print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["repository"]["small"]."\" border=\"0\" title=\"".$gm_lang["repos"]."\" alt=\"".$gm_lang["repos"]."\" />&nbsp;&nbsp;";
	print $gm_lang["repos"];
	print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
	$i=1;
	if ($repo_count>0){
		$i=1;
		// -- print the array
		foreach ($note_controller->note->repolist as $key => $value) {
			print_list_repository($key, $value);
			if ($i==ceil($repo_count/2) && $repo_count>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
			$i++;
		}
		print "\n\t\t</ul></td>\n\t\t";
 	
		print "</tr><tr><td>".$gm_lang["total_repositories"]." ".count($repo_count);
		if (count($repo_hide)>0) print "  --  ".$gm_lang["hidden"]." ".count($repo_hide);
	}
	print "</table><br />";
}
else print "<div id=\"no_tab6\"></div>";
print "</div>";
// End of link list

// active tab
print "<script type=\"text/javascript\">\n<!--\n";
if ($note_controller->view) print "tabswitch(0)";
else if (isset($_SESSION["note"][JoinKey($note_controller->note->xref, $GEDCOMID)])) print "tabswitch(".$_SESSION["note"][JoinKey($note_controller->note->xref, $GEDCOMID)].")";
else print "tabswitch(1)";
print "\n//-->\n</script>\n";

print_footer();
?>