<?php
/**
 * Displays the details about a repository record.
 * Also shows how many sources reference this repository.
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
 * @package Genmod
 * @subpackage Display
 * @version $Id: repo.php,v 1.6 2006/01/09 14:19:30 sjouke Exp $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

/**
 * Inclusion of the language files
*/
require($GM_BASE_DIRECTORY.$factsfile["english"]);
if (file_exists($GM_BASE_DIRECTORY . $factsfile[$LANGUAGE])) require $GM_BASE_DIRECTORY . $factsfile[$LANGUAGE];

if ($SHOW_SOURCES<getUserAccessLevel($gm_username)) {
	header("Location: index.php");
	exit;
}

if (empty($action)) $action="";
if (empty($show_changes)) $show_changes = "yes";
if (empty($rid)) $rid = " ";
$rid = clean_input($rid);

global $GM_IMAGES;

$accept_success=false;
if (userCanAccept($gm_username)) {
	if ($action=="accept") {
		if (accept_changes($rid."_".$GEDCOM)) {
			$show_changes="no";
			$accept_success=true;
		}
	}
}

$nonfacts = array();

$repo = find_repo_record($rid);
//-- make sure we have the true id from the record
$ct = preg_match("/0 @(.*)@/", $repo, $match);
if ($ct>0) $rid = trim($match[1]);

$name = get_repo_descriptor($rid);
$add_descriptor = get_add_repo_descriptor($rid);
if ($add_descriptor) $name .= " - ".$add_descriptor;


print_header("$name - $rid - ".$gm_lang["repo_info"]);

?>
<script language="JavaScript" type="text/javascript">
<!--
	function show_gedcom_record() {
		var recwin = window.open("gedrecord.php?pid=<?php print $rid ?>", "", "top=0,left=0,width=300,height=400,scrollbars=1,scrollable=1,resizable=1");
	}
	function showchanges() {
		window.location = '<?php print $SCRIPT_NAME."?".$QUERY_STRING."&show_changes=yes"; ?>';
	}
//-->
</script>
<table width="100%"><tr><td>
<?php
if ($accept_success) print "<b>".$gm_lang["accept_successful"]."</b><br />";
print "\n\t<span class=\"name_head\">".PrintReady($name);

if ($SHOW_ID_NUMBERS) print " &lrm;($rid)&lrm;";
print "</span><br />";
if (userCanEdit($gm_username)) {
	if ($view!="preview") {
		if (isset($gm_changes[$rid."_".$GEDCOM])) {
			if (!isset($show_changes)) {
				print "<a href=\"repo.php?rid=$rid&amp;show_changes=yes\">".$gm_lang["show_changes"]."</a>"."  ";
			}
			else {
				if (userCanAccept($gm_username)) print "<a href=\"repo.php?rid=$rid&amp;action=accept\">".$gm_lang["accept_all"]."</a> | ";
				print "<a href=\"repo.php?rid=$rid\">".$gm_lang["hide_changes"]."</a>"."  ";
			}
			print_help_link("show_changes_help", "qm");
			print "<br />";
		}
		print_help_link("edit_raw_gedcom_help", "qm", "edit_raw");
		print "<a href=\"#\" onclick=\"return edit_raw('$rid', 'edit_raw');\">".$gm_lang["edit_raw"]."</a>";
		print " | ";
		print_help_link("delete_repo_help", "qm", "delete_repo");
		print "<a href=\"#\" onclick = \"if (confirm('".$gm_lang["confirm_delete_repo"]."')) return deleterepository('$rid', 'delete_repo');\">".$gm_lang["delete_repo"]."</a>";
		if ($SHOW_GEDCOM_RECORD) {
			print " | ";
			print_help_link("show_repo_gedcom_help", "qm", "view_gedcom");
			print "\n\t\t<span class=\"link\"><a href=\"javascript:show_gedcom_record();\"><img class=\"icon\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["gedcom"]["small"]."\" border=\"0\" alt=\"\" />".$gm_lang["view_gedcom"]."</a>";
			print "</span>";
		}
		if ($ENABLE_CLIPPINGS_CART>=getUserAccessLevel()) {
			print " | ";
			print_help_link("add_repository_clip_help", "qm");
			print "<span class=\"link\"><a href=\"clippings.php?action=add&amp;id=$rid&amp;type=repository\"><img class=\"icon\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["clippings"]["small"]."\" border=\"0\" alt=\"\" />".$gm_lang["add_to_cart"]."</a>";
			print "</span>";
		}
	}
	if (isset($show_changes)) {
		$newrepo = trim(find_gedcom_record($rid));
	}
}
print "<br />";

$repo = array();
if (isset($repo_id_list[$rid])) $repo = $repo_id_list[$rid];
else {
	print "&nbsp;&nbsp;&nbsp;<span class=\"warning\"><i>".$gm_lang["no_results"]."</i></span>";
	print "<br /><br /><br /><br /><br /><br />\n";
	print_footer();
	exit;
}
$repofacts = array();
$allreposubs = get_all_subrecords($repo["gedcom"], "", true, false);
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
// if (!empty($newrepo)) {
	// $newrepofacts = array();
	// $gedlines = preg_split("/\n/", $newrepo);
	// $lct = count($gedlines);
	// $factrec = "";	// -- complete fact record
	// $line = "";	// -- temporary line buffer
	// $linenum = 0;
	// for($i=1; $i<=$lct; $i++) {
		// if ($i<$lct) $line = $gedlines[$i];
		// else $line=" ";
		// if (empty($line)) $line=" ";
		// if (($i==$lct)||($line{0}==1)) {
			// $newrepofacts[] = array($factrec, $linenum);
			// $factrec = $line;
			// $linenum = $i;
		// }
		// else $factrec .= "\n".$line;
	// }
// 
	// if (!empty($show_changes)) {
		// //-- update old facts
		// foreach($repofacts as $key=>$fact) {
			// $found = false;
			// foreach($newrepofacts as $indexval => $newfact) {
				// if (trim($newfact[0])==trim($fact[0])) {
					// $found = true;
					// break;
				// }
			// }
			// if (!$found) {
				// $repofacts[$key][0].="\nGM_OLD\n";
			// }
		// }
		// //-- look for new facts
		// foreach($newrepofacts as $key=>$newfact) {
			// $found = false;
			// foreach($repofacts as $indexval => $fact) {
				// if (trim($newfact[0])==trim($fact[0])) {
					// $found = true;
					// break;
				// }
			// }
			// if (!$found) {
				// $newfact[0].="\nGM_NEW\n";
				// $repofacts[]=$newfact;
			// }
		// }
	// }
// }
print "\n<table class=\"facts_table\">";
foreach($repofacts as $key => $value) {
	$fact = trim($value[0]);
	if (!empty($fact)) {
		if (showFact($fact, $rid)) {
			if ($fact=="OBJE") {
				print_main_media($factrec, 1, $rid);
			}
			else if ($fact=="NOTE") {
				print_main_notes($value[1], 1, $rid, $value[2]);
			}
			else {
				print_fact($value[1], $rid, $fact, $value[2]);
			}
		}
	}
}
//-- new fact link
if (($view!="preview") &&(userCanEdit($gm_username))) {
	print_add_new_fact($rid, $repofacts, "REPO");
}
print "</table>\n\n<br /><br />";
print_help_link("repos_listbox_help", "qm", "other_repo_records");
print "\n\t\t<span class=\"label\">".$gm_lang["other_repo_records"]."</span>";
flush();

$query = "REPO @$rid@";
// -- array of sources
$mysourcelist = array();

$mysourcelist = search_sources($query);
uasort($mysourcelist, "itemsort");
$cs=count($mysourcelist);

if ($cs>0) {
	print "\n\t<table class=\"list_table $TEXT_DIRECTION\">\n\t\t<tr><td class=\"list_label\"";
	if($cs>12)	print " colspan=\"2\"";
	print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["source"]["small"]."\" border=\"0\" title=\"".$gm_lang["titles_found"]."\" alt=\"".$gm_lang["titles_found"]."\" />&nbsp;&nbsp;";
	print $gm_lang["titles_found"];
	print "</td></tr><tr><td class=\"$TEXT_DIRECTION list_value_wrap\"><ul>";
	if (count($mysourcelist)>0) {
		$i=1;
		// -- print the array
		foreach ($mysourcelist as $key => $value) {
			print_list_source($key, $value);
			if ($i==ceil($cs/2) && $cs>12) print "</ul></td><td class=\"list_value_wrap\"><ul>\n";
			$i++;
		}
	}

	print "\n\t\t</ul></td>\n\t\t";

	print "</tr><tr>";
	print "</tr>\n\t</table>";
}
else print "&nbsp;&nbsp;&nbsp;<span class=\"warning\"><i>".$gm_lang["no_results"]."</span>";

print "<br /><br /></td><td valign=\"top\">";
print "&nbsp;</td></tr></table>\n";
print_footer();
?>