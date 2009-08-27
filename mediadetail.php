<?php
/**
 * Displays the details about a media record. Also shows how many people and families
 * reference this media.
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
 * Inclusion of the media controller
*/
require_once("includes/controllers/media_ctrl.php");

if ($controller->isempty) {
	print_header($gm_lang["media_not_found"]);
	print "<span class=\"error\">".$gm_lang["media_not_found"]."</span>";
	print_footer();
	exit;
}
if (!$controller->media->disp) {
	print_header($gm_lang["private"]." ".$gm_lang["media_info"]);
	print_privacy_error($CONTACT_EMAIL);
	print_footer();
	exit;
}

print_header($controller->getPageTitle());
?>
<div id="show_changes"></div>

<script language="JavaScript" type="text/javascript">
<!--
	function show_gedcom_record() {
		var recwin = window.open("gedrecord.php?pid=<?php print $controller->mid ?>", "", "top=0,left=0,width=300,height=400,scrollbars=1,scrollable=1,resizable=1");
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
		<td colspan="2">
		<?php
		// Print the picture!
		$filename = $controller->media->m_fileobj->f_main_file;
		$thumbnail = $controller->media->m_fileobj->f_thumb_file;
		// NOTE: Determine the size of the mediafile
		$imgwidth = 300;
		$imgheight = 300;
		if (preg_match("'://'", $filename)) {
			if ($MediaFS->IsValidMedia($filename)) {
				$imgwidth = 400;
				$imgheight = 500;
			}
			else {
				$imgwidth = 800;
				$imgheight = 400;
			}
		}
		else if ((preg_match("'://'", $MEDIA_DIRECTORY)>0)||($controller->media->m_fileobj->f_file_exists)) {
			if ($controller->media->m_fileobj->f_width > 0 && $controller->media->m_fileobj->f_height > 0) {
				$imgwidth = $controller->media->m_fileobj->f_width+50;
				$imgheight = $controller->media->m_fileobj->f_height + 50;
			}
		}
		// Print the title
		print "<span class=\"name_head\">".PrintReady($controller->media->getTitle());
		if ($SHOW_ID_NUMBERS) print " &lrm;(".$controller->mid.")&lrm;";
		print "</span><br />";
		// Print the picture
		if (preg_match("'://'", $thumbnail)||(preg_match("'://'", $MEDIA_DIRECTORY)>0)||($controller->media->m_fileobj->f_file_exists)) {
			if ($USE_GREYBOX && $controller->media->m_fileobj->f_is_image) print "<a href=\"".FilenameEncode($filename)."\" title=\"".PrintReady($controller->media->getTitle())."\" rel=\"gb_imageset[]\">";
			else {
				print "<a href=\"#\" onclick=\"return openImage('".$filename."',".$imgwidth.", ".$imgheight.", ".$controller->media->m_fileobj->f_is_image.");\">";
			}
			print "<img src=\"".$thumbnail."\" border=\"0\" align=\"" . ($TEXT_DIRECTION== "rtl"?"right": "left") . "\" class=\"thumbnail\" alt=\"\" /></a>";
		}
		?>
		</td>
	</tr>
</table>	

<script type="text/javascript">
<!--
function tabswitch(n) {
	sndReq('dummy', 'remembertab', 'xref', '<?php print JoinKey($controller->mid, $GEDCOMID); ?>' , 'tab_tab', n, 'type', 'media');
	if (n==6) n = 0;
	var tabid = new Array('0', 'facts', 'individuals','families','sources','repositories');
	// show all tabs ?
	var disp='none';
	if (n==0) disp='block';
	// reset all tabs areas
	for (i=1; i<tabid.length; i++) document.getElementById(tabid[i]).style.display=disp;
	if ('<?php echo $controller->view; ?>' != 'preview') {
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
// -- Get the facts
$mediafacts = $controller->media->getmediaFacts();
// -- array of names
InitListCounters();
$myindilist = $controller->media->getMediaIndis();
$myfamlist = $controller->media->getMediaFams();
$mysourcelist = $controller->media->getMediaSources();
$myrepolist = $controller->media->getMediaRepos();
$indi_count = count($myindilist);
$fam_count = count($myfamlist);
$sour_count = count($mysourcelist);
$repo_count = count($myrepolist);

if (!$controller->IsPrintPreview()) {
	// Print message is any changes to links are present
	if (HasUnapprovedLinks($mid)) print $gm_lang["unapproved_link"];
	?>
	<div class="door center">
	<dl>
	<dd id="door1"><a href="javascript:;" onclick="tabswitch(1)" ><?php print $gm_lang["facts"]?></a></dd>
	<dd id="door2"><a href="javascript:;" onclick="tabswitch(2)" ><?php print $gm_lang["indi_linking"]." (".$indi_count.")";?></a></dd>
	<dd id="door3"><a href="javascript:;" onclick="tabswitch(3)" ><?php print $gm_lang["fam_linking"]." (".$fam_count.")";?></a></dd>
	<dd id="door4"><a href="javascript:;" onclick="tabswitch(4)" ><?php print $gm_lang["sour_linking"]." (".$sour_count.")";?></a></dd>
	<dd id="door5"><a href="javascript:;" onclick="tabswitch(5)" ><?php print $gm_lang["repo_linking"]." (".$repo_count.")";?></a></dd>
	<dd id="door0"><a href="javascript:;" onclick="tabswitch(0)" ><?php print $gm_lang["all"]?></a></dd>
	</dl>
	</div>
	<?php
}
print "<div id=\"dummy\"></div><br /><br />";
// Facts
print "<div id=\"facts\" class=\"tab_page\" style=\"display:none;\" >";
print "\n<table class=\"facts_table\">";

foreach($mediafacts as $key => $value) {
	$fact = trim($value[0]);
	if (IsChangedFact($mid, $value[1])) $styleadd = "change_old";
	else if (isset($value[3]) && $value[3] == "new") $styleadd = "change_new";
	else $styleadd = "";
	$cfact = RetrieveChangedFact($mid, $value[0], $value[1]);
	if ($controller->media->mediadeleted) {
		$styleadd = "change_old";
	}
	if ($fact=="NOTE") {
		print_main_notes($value[1], 1, $mid, $value[2], $styleadd);
		if ($styleadd == "change_old" && !$controller->media->mediadeleted) print_main_notes($cfact, 1, $mid, $value[2], "change_new");
	}
	else if ($fact == "SOUR") {
		print_main_sources($value[1], substr($value[1],0,1), $mid, $value[2], $styleadd);
		if ($styleadd == "change_old" && !$controller->media->mediadeleted) {
			print_main_sources($cfact, substr($cfact,0,1), $mid, $value[2], "change_new");
		}
	}
	else {
		print_fact($value[1], $mid, $value[0], $value[2], false, $styleadd);
		if ($styleadd == "change_old" && !empty($cfact) && !$controller->media->mediadeleted) print_fact($cfact, $mid, $value[0], $value[2], $controller->media->m_gedrec, "change_new");
	}
}
//-- new fact link
if (!$controller->isPrintPreview() && $controller->userCanEdit() && !$controller->media->mediadeleted && !$controller->isempty) {
	PrintAddNewFact($mid, $mediafacts, "OBJE");
	print "<tr>";
	print "<td class=\"shade2 width20\">";
	print_help_link("add_media_link_help", "qm");
	print $gm_lang["add_media_link_lbl"]."</td>";
	print "<td class=\"shade1\">";
	print "<a href=\"javascript: ".$gm_lang["add_media_lbl"]."\" onclick=\"add_new_record('". $controller->mid."','OBJE', 'add_media_link'); return false;\">".$gm_lang["add_media_link"]."</a>";
	print "</td></tr>";
}
?>
</table>
</div>
<?php 
if ($controller->IsPrintPreview()) print "<br /><span class=\"label\">".$gm_lang["other_mmrecords"]."</span>";

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
	foreach ($myindilist as $key => $value) {
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
	foreach ($myfamlist as $key => $value) {
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
else print "<div id=\"no_tab4\"></div>";
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
		foreach ($myrepolist as $key => $value) {
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
else print "<div id=\"no_tab5\"></div>";
print "</div>";

print "<script type=\"text/javascript\">\n<!--\n";
if ($controller->isPrintPreview()) print "tabswitch(0)";
else if (isset($_SESSION["media"][JoinKey($controller->mid, $GEDCOMID)])) print "tabswitch(".$_SESSION["media"][JoinKey($controller->mid, $GEDCOMID)].")";
else print "tabswitch(1)";
print "\n//-->\n</script>\n";


print_footer(); ?>