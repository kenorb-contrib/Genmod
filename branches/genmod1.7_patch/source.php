<?php
/**
 * Displays the details about a source record. Also shows how many people and families
 * reference this source.
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
 * Inclusion of the source controller
*/
require_once("includes/controllers/source_ctrl.php");

if ($source_controller->isempty) {
	print_header($gm_lang["source_not_found"]);
	print "<span class=\"error\">".$gm_lang["source_not_found"]."</span>";
	print_footer();
	exit;
}

print_header($source_controller->getPageTitle());
	
?>
<div id="show_changes"></div>

<script language="JavaScript" type="text/javascript">
<!--
	function show_gedcom_record() {
		var recwin = window.open("gedrecord.php?pid=<?php print $source_controller->sid ?>", "", "top=0,left=0,width=300,height=400,scrollbars=1,scrollable=1,resizable=1");
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
<table class="list_table">
	<tr>
		<td>
		<span class="name_head"><?php print PrintReady($source_controller->source->getTitle()); if ($SHOW_ID_NUMBERS) print " &lrm;(".$source_controller->sid.")&lrm;"; ?></span><br />
		<?php if($SHOW_COUNTER) {
			print "\n<br /><br /><span style=\"margin-left: 3px;\">".$gm_lang["hit_count"]."&nbsp;".$hits."</span>\n";
		}?>
		</td>
	</tr>
	<tr>
</table>
<script type="text/javascript">
<!--
function tabswitch(n) {
	sndReq('dummy', 'remembertab', 'xref', '<?php print JoinKey($source_controller->source->xref, $GEDCOMID); ?>' , 'tab_tab', n, 'type', 'sour');
	if (n==6) n = 0;
	var tabid = new Array('0', 'facts', 'individuals','families','notes','media');
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
// -- array of names
$source_controller->source->getSourceIndis();
$indi_count = count($source_controller->source->indilist);
$source_controller->source->getSourceFams();
$fam_count = count($source_controller->source->famlist);
$source_controller->source->getSourceMedia();
$media_count = count($source_controller->source->medialist);
$source_controller->source->getSourceNotes();
$note_count = count($source_controller->source->notelist);

if (!$source_controller->IsPrintPreview()) {
	// Print message is any changes to links are present
	if (HasUnapprovedLinks($sid)) print $gm_lang["unapproved_link"];
	?>
	<div class="door center">
	<dl>
	<dd id="door1"><a href="javascript:;" onclick="tabswitch(1)" ><?php print $gm_lang["facts"]?></a></dd>
	<dd id="door2"><a href="javascript:;" onclick="tabswitch(2)" ><?php print $gm_lang["indi_linking"]." (".$indi_count.")";?></a></dd>
	<dd id="door3"><a href="javascript:;" onclick="tabswitch(3)" ><?php print $gm_lang["fam_linking"]." (".$fam_count.")";?></a></dd>
	<dd id="door4"><a href="javascript:;" onclick="tabswitch(4)" ><?php print $gm_lang["note_linking"]." (".$note_count.")";?></a></dd>
	<dd id="door5"><a href="javascript:;" onclick="tabswitch(5)" ><?php print $gm_lang["mm_linking"]." (".$media_count.")";?></a></dd>
	<dd id="door0"><a href="javascript:;" onclick="tabswitch(0)" ><?php print $gm_lang["all"]?></a></dd>
	</dl>
	</div><div id="dummy"></div><br /><br />
	<?php
}

// Facts
print "<div id=\"facts\" class=\"tab_page\" style=\"display:none;\" >";
$sourcefacts = $source_controller->source->getSourceFacts();
print "<table class=\"facts_table\">";

foreach($sourcefacts as $key => $value) {
	$fact = trim($value[0]);
	if (IsChangedFact($sid, $value[1])) $styleadd = "change_old";
	else if (isset($value[3]) && $value[3] == "new") $styleadd = "change_new";
	else $styleadd = "";
	if ($source_controller->source->sourdeleted) $styleadd = "change_old";
	if (!empty($fact)) {
		$cfact = RetrieveChangedFact($sid, $value[0], $value[1]);
		if ($fact=="OBJE") {
			print_main_media($value[1], $sid, 0, $value[2], ($source_controller->show_changes == "yes"), $styleadd);
			if ($styleadd == "change_old" && !$source_controller->source->sourdeleted) print_main_media($cfact, $sid, 0, $value[2], ($source_controller->show_changes == "yes"), "change_new");
		}
		else if ($fact=="NOTE") {
			print_main_notes($value[1], 1, $sid, $value[2], $styleadd);
			if ($styleadd == "change_old" && !$source_controller->source->sourdeleted) print_main_notes($cfact, 1, $sid, $value[2], "change_new");
		}
		else {
			print_fact($value[1], $sid, $value[0], $value[2], false, $styleadd);
			if ($styleadd == "change_old" && !empty($cfact) && !$source_controller->source->sourdeleted) print_fact($cfact, $sid, $value[0], $value[2], $source_controller->source->gedrec, "change_new");
		}
	}
}
//-- new fact link
if (!$source_controller->isPrintPreview() && $source_controller->userCanEdit() && !$source_controller->source->sourdeleted && !$source_controller->isempty) {
	PrintAddNewFact($sid, $sourcefacts, "SOUR");
}
print "</table></div>";

if ($source_controller->IsPrintPreview()) print "<br /><span class=\"label\">".$gm_lang["other_records"]."</span>";


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
	foreach ($source_controller->source->indilist as $key => $value) {
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
	foreach ($source_controller->source->famlist as $key => $value) {
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

// -- array of media
print "<div id=\"media\" class=\"tab_page\" style=\"display:none;\" >";

if ($media_count>0) {
	print "\n\t<table class=\"list_table  $TEXT_DIRECTION\">\n\t\t<tr><td class=\"shade2 center\"";
	if($media_count>12)	print " colspan=\"2\"";
	print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["media"]["small"]."\" border=\"0\" title=\"".$gm_lang["media"]."\" alt=\"".$gm_lang["media"]."\" />&nbsp;&nbsp;";
	print $gm_lang["media"];
	print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
	$i=1;
	foreach ($source_controller->source->medialist as $key => $value) {
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

// array of notes
print "<div id=\"notes\" class=\"tab_page\" style=\"display:none;\" >";

if ($note_count>0) {
	print "\n\t<table class=\"list_table $TEXT_DIRECTION\">\n\t\t<tr><td class=\"shade2 center\"";
	if($note_count > 12) print " colspan=\"2\"";
	print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["note"]["other"]."\" border=\"0\" title=\"".$gm_lang["notes"]."\" alt=\"".$gm_lang["notes"]."\" />&nbsp;&nbsp;";
	print $gm_lang["titles_found"];
	print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
	$i=1;
	foreach ($source_controller->source->notelist as $key => $note) {
		$note->PrintListNote();
		if ($i==ceil($note_count/2) && $note_count>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
		$i++;
	}
	print "\n\t\t</ul></td>\n\t\t";
 
	print "</tr>";
	if ($note_count>0) { 
		print "<tr><td>";
		print $gm_lang["total_notes"]." ".$note_count;
		if (count($note_hide)>0) print "&nbsp;--&nbsp;".$gm_lang["hidden"]." ".count($note_hide);
		print "</td></tr>";
	}
	print "</table><br />";
}
else print "<div id=\"no_tab4\"></div>";
print "</div>";


print "<script type=\"text/javascript\">\n<!--\n";
if ($source_controller->isPrintPreview()) print "tabswitch(0)";
else if (isset($_SESSION["sour"][JoinKey($source_controller->source->xref, $GEDCOMID)])) print "tabswitch(".$_SESSION["sour"][JoinKey($source_controller->source->xref, $GEDCOMID)].")";
//else print "tabswitch(". ($note_controller->default_tab + 1) .")";
else print "tabswitch(1)";
print "\n//-->\n</script>\n";

print_footer();
?>