<?php
/**
 * Searches based on user query.
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
$search_controller = new SearchController("search");

PrintHeader($search_controller->pagetitle);
?>
<script language="JavaScript" type="text/javascript">
<!--
	function checknames(frm) {
		if (frm.action[1].checked) {
			if (frm.year.value!="") {
				message=true;
				if (frm.firstname.value!="") message=false;
				if (frm.lastname.value!="") message=false;
				if (frm.place.value!="") message=false;
				if (message) {
					alert("<?php print GM_LANG_invalid_search_input?>");
					frm.firstname.focus();
					return false;
				}
			}
		}
		if (frm.action[0].checked) {
			if (frm.query.value.length<1) {
					alert("<?php print GM_LANG_search_more_chars?>");
					frm.query.focus();
					return false;
			}
		}
		return true;
	}
//-->
</script>
<?php
	// print "<div id=\"search_content\">";
		print "<div id=\"search_header\">";
		print GM_LANG_search_gedcom;
		print "</div>";

if ($search_controller->view == "preview") {
	// to be done
	//	print "</td><tr><td align=\"center\">".$logstring."</td></tr></table>";
}
else {
	print "<div id=\"search_options\">";
		// start of new searchform
		print "<div class=\"topbottombar\">";
		PrintHelpLink("search_options_help", "qm","search_options");
		print GM_LANG_search_options;
		print "</div>";
		print "<form method=\"post\" onsubmit=\""?>return checknames(this);<?php print " \" action=\"".SCRIPT_NAME."\">";
		print "<table class=\"width100 center $TEXT_DIRECTION\">";
		
		// If more than one GEDCOM, switching is allowed AND DB mode is set, let the user select
		if ((count($GEDCOMS) > 1) && ($ALLOW_CHANGE_GEDCOM)) {
			print "<tr><td class=\"shade2\" style=\"padding: 5px;\">";
			print GM_LANG_search_geds;
			print "</td><td class=\"shade1\" style=\"padding: 5px;\">";
			echo '<input type="checkbox" onclick="CheckAllGed(this)" />'.GM_LANG_select_deselect_all.'<br>';
			foreach ($GEDCOMS as $key=>$ged) {
				SwitchGedcom($key);
				if ($gm_user->username != "" || !GedcomConfig::$REQUIRE_AUTHENTICATION) {
					$str = "sg".$key;
					print "<input type=\"checkbox\" ";
					if (($key == $GEDCOMID) && ($search_controller->action == "")) print "checked=\"checked\" ";
					else {
						if (in_array($key, $search_controller->searchgeds)) print "checked=\"checked\" ";
					}
					print "value=\"yes\" class=\"checkged\" name=\"".$str."\""." />".$GEDCOMS[$key]["title"]."<br />";
				}
			}
			SwitchGedcom();
			print "</td></tr>";
		}
		
		// Show associated persons/fams?
		print "<tr><td class=\"shade2\" style=\"padding: 5px;\">";
		print GM_LANG_search_asso_label;
		print "</td><td class=\"shade1\" style=\"padding: 5px;\">";
		print "<input type=\"checkbox\" name=\"showasso\" value=\"on\" ";
		if ($search_controller->showasso == "on") print "checked=\"checked\" ";
		print "/>".GM_LANG_search_asso_text;
		print "</td></tr>";
		
		// switch between general and soundex
		print "<tr><td class=\"shade2\" style=\"padding: 5px;\">".GM_LANG_search_type;
		print "</td><td class=\"shade1\" style=\"padding: 5px;\">";
		print "<input type=\"radio\" name=\"action\" value=\"general\" ";
		if ($search_controller->action == "general") print "checked=\"checked\" ";
		print "onclick=\"expand_layer('gsearch'); expand_layer('ssearch');\" />".GM_LANG_search_general;
		print "<br /><input type=\"radio\" name=\"action\" value=\"soundex\" ";
		if (($search_controller->action == "soundex") || ($search_controller->action == "")) print "checked=\"checked\" ";
		print "onclick=\"expand_layer('gsearch'); expand_layer('ssearch');\" />".GM_LANG_search_soundex;
		print "</td></tr>";
		print "</table>";
	print "</div>";
	
		// The first searchform
	print "<div id=\"gsearch\" style=\"display: ";
		if ($search_controller->action == "soundex" || $search_controller->action == "") print "none\">";
		else print "block\">";
		print "<div class=\"topbottombar\">";
		PrintHelpLink("search_enter_terms_help", "qm", "search_general");
		print GM_LANG_search_general;
		print "</div>";
		
		// search terms
		print "<table class=\"width100 center $TEXT_DIRECTION\">";
		print "<tr><td class=\"shade2\" style=\"padding: 5px;\">";
		print GM_LANG_enter_terms;
		print "</td><td class=\"shade1\" style=\"padding: 5px;\"><input tabindex=\"1\" type=\"text\" name=\"query\" value=\"";
		if ($search_controller->action == "general" && !is_null($search_controller->myquery)) print htmlspecialchars($search_controller->myquery);
		else print "";
		print "\" />";
		print "</td><td class=\"shade3\" style=\"vertical-align: middle; padding: 5px;\" rowspan=\"3\">";
		print "<input tabindex=\"2\" type=\"submit\" value=\"".GM_LANG_search."\" /></tr>";
		// Choice where to search
		print "<tr><td class=\"shade2\" style=\"padding: 5px;\">".GM_LANG_search_inrecs;
		print "</td><td class=\"shade1\" style=\"padding: 5px;\">";
		print "<input type=\"checkbox\"";
		if ((!is_null($search_controller->srindi)) || ($search_controller->action == "")) print " checked=\"checked\"";
		print " value=\"yes\" name=\"srindi\" />".GM_LANG_search_indis."<br />";
		print "<input type=\"checkbox\"";
		if (!is_null($search_controller->srfams)) print " checked=\"checked\"";
		print " value=\"yes\" name=\"srfams\" />".GM_LANG_search_fams."<br />";
		if (PrivacyFunctions::ShowSourceFromAnyGed()) {
			print "<input type=\"checkbox\"";
			if (!is_null($search_controller->srsour)) print " checked=\"checked\"";
			print " value=\"yes\" name=\"srsour\" />".GM_LANG_search_sources."<br />";
			print "<input type=\"checkbox\"";
			if (!is_null($search_controller->srrepo)) print " checked=\"checked\"";
			print " value=\"yes\" name=\"srrepo\" />".GM_LANG_search_repos."<br />";
		}
		print "<input type=\"checkbox\"";
		if (!is_null($search_controller->srmedia)) print " checked=\"checked\"";
		print " value=\"yes\" name=\"srmedia\" />".GM_LANG_search_media."<br />";
		print "<input type=\"checkbox\"";
		if (!is_null($search_controller->srnote)) print " checked=\"checked\"";
		print " value=\"yes\" name=\"srnote\" />".GM_LANG_search_notes."<br />";
		print "</td>";
		print "</tr>";
		print "<tr><td class=\"shade2\" style=\"padding: 5px;\">".GM_LANG_search_tagfilter."</td>";
		print "<td class=\"shade1\" style=\"padding: 5px;\"><input type=\"radio\" name=\"tagfilter\" value=\"on\" ";
		if (($search_controller->tagfilter == "on") || ($search_controller->tagfilter == "")) print "checked=\"checked\" ";
		print ">".GM_LANG_search_tagfon."<br /><input type=\"radio\" name=\"tagfilter\" value=\"off\" ";
		if ($search_controller->tagfilter == "off") print "checked=\"checked\"";
		print " />".GM_LANG_search_tagfoff;
		print "</td></tr>";

		print "</table>";
	print "</div>";
	
		// The second searchform
	print "<div id=\"ssearch\" style=\"display: ";
		if ($search_controller->action == "soundex" || $search_controller->action == "") print "block\">";
		else print "none\">";
		print "<div class=\"topbottombar\">";
		PrintHelpLink("soundex_search_help", "qm");
		print GM_LANG_soundex_search;
		print "</div>";
		
		print "<table class=\"width100 center $TEXT_DIRECTION\">";
		
		print "<tr><td class=\"shade2\">";
		print GM_LANG_lastname_search;
		print "</td><td class=\"shade1\"><input tabindex=\"3\" type=\"text\" name=\"lastname\" value=\"";
		if ($search_controller->action == "soundex") print $search_controller->mylastname;
		print "\" /></td>";

		print "<td class=\"shade3\" style=\"vertical-align: middle; text-align: center; padding: 5px;\"  rowspan=\"4\">";
		print "<input tabindex=\"7\" type=\"submit\" value=\"";
		print GM_LANG_search;
		print "\" /></td></tr>";

		print "<tr><td class=\"shade2\">";
		print GM_LANG_firstname_search;
		print "</td><td class=\"shade1\">";
		print "<input tabindex=\"4\" type=\"text\" name=\"firstname\" value=\"";
		if ($search_controller->action == "soundex") print $search_controller->myfirstname;
		print "\" /></td></tr>";
		print "<tr><td class=\"shade2\">";
		print GM_LANG_search_place;
		print "</td><td class=\"shade1\"><input tabindex=\"5\" type=\"text\" name=\"place\" value=\"";
		if ($search_controller->action == "soundex") print $search_controller->myplace;
		print "\" /></td></tr>";
		print "<tr><td class=\"shade2\">";
		print GM_LANG_search_year;
		print "</td><td class=\"shade1\"><input tabindex=\"6\" type=\"text\" name=\"year\" value=\"";
		if ($search_controller->action == "soundex") print $search_controller->myyear;
		print "\" /></td>";
		print "</tr>";
		print "<tr><td class=\"shade2\" >";
		print GM_LANG_search_soundextype;
		print "<td class=\"shade1\" colspan=\"2\" ><input type=\"radio\" name=\"soundex\" value=\"Russell\" ";
		if (($search_controller->soundex == "Russell") || ($search_controller->soundex == "")) print "checked=\"checked\" ";
		print " />".GM_LANG_search_russell."<br /><input type=\"radio\" name=\"soundex\" value=\"DaitchM\" ";
		if ($search_controller->soundex == "DaitchM") print "checked=\"checked\" ";
		print " />".GM_LANG_search_DM;
		print "</td>";
		print "</td></tr>";
		print "<tr><td class=\"shade2\">";
		print GM_LANG_search_prtnames;
		print "</td><td class=\"shade1\" colspan=\"2\" ><input type=\"radio\" name=\"nameprt\" value=\"hit\" ";
		if (($search_controller->nameprt == "hit")) print "checked=\"checked\" ";
		print ">".GM_LANG_search_prthit."<br /><input type=\"radio\" name=\"nameprt\" value=\"all\" ";
		if ($search_controller->nameprt == "all" || ($search_controller->nameprt == "")) print "checked=\"checked\" ";;
		print " />".GM_LANG_search_prtall;
		print "</td>";
		print "</td></tr>";
		print "<tr><td class=\"shade2\">";
		print GM_LANG_search_sorton;
		print "</td><td class=\"shade1\" colspan=\"2\" ><input type=\"radio\" name=\"sorton\" value=\"last\" ";
		if (($search_controller->sorton == "last") || ($search_controller->sorton == "")) print "checked=\"checked\" ";
		print ">".GM_LANG_lastname_search."<br /><input type=\"radio\" name=\"sorton\" value=\"first\" ";
		if ($search_controller->sorton == "first") print "checked=\"checked\" ";;
		print " />".GM_LANG_firstname_search;
		print "</td>";
		print "</td></tr>";
		print "</table>";
	print "</div>";
	print "</form>";
}			

// ---- section to search and display results on a general keyword search
if ($search_controller->action == "general") {
	if (!is_null($search_controller->query) && $search_controller->query != "") {
		
		?>
		<script type="text/javascript">
		<!--
		function tabswitch(n) {
			if (n==7) n = 0;
			var tabid = new Array('0','indis','fams','sources','repos','media','notes');
			// show all tabs ?
			var disp='none';
			if (n==0) disp='block';
			// reset all tabs areas
			for (i=1; i<tabid.length; i++) document.getElementById(tabid[i]).style.display=disp;
			if ('<?php echo $search_controller->view; ?>' != 'preview') {
				// current tab area
				if (n>0) document.getElementById(tabid[n]).style.display='block';
				// empty tabs
				for (i=0; i<tabid.length; i++) {
					var elt = document.getElementById('door'+i);
					if (document.getElementById('no_tab'+i)) { // empty ?
						if (<?php if ($gm_user->username != "") echo 'true'; else echo 'false';?>) {
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
		<div id="result" class="width100" style="display: inline-block;"><br /><br />
		<div class="door">
		<dl>
		<dd id="door1"><a href="javascript:;" onclick="tabswitch(1)" ><?php print GM_LANG_search_indis." (".count($search_controller->indi_total).")";?></a></dd>
		<dd id="door2"><a href="javascript:;" onclick="tabswitch(2)" ><?php print GM_LANG_search_fams." (".count($search_controller->fam_total).")";?></a></dd>
		<dd id="door3"><a href="javascript:;" onclick="tabswitch(3)" ><?php print GM_LANG_search_sources." (".count($search_controller->sour_total).")";?></a></dd>
		<dd id="door4"><a href="javascript:;" onclick="tabswitch(4)" ><?php print GM_LANG_search_repos." (".count($search_controller->repo_total).")";?></a></dd>
		<dd id="door5"><a href="javascript:;" onclick="tabswitch(5)" ><?php print GM_LANG_search_media." (".count($search_controller->media_total).")";?></a></dd>
		<dd id="door6"><a href="javascript:;" onclick="tabswitch(6)" ><?php print GM_LANG_search_notes." (".count($search_controller->note_total).")";?></a></dd>
		<dd id="door0"><a href="javascript:;" onclick="tabswitch(0)" ><?php print GM_LANG_all?></a></dd>
		</dl>
		</div><br /><br />
	
		<?php

		// Print the indis	
		print "<div id=\"indis\" class=\"tab_page\" style=\"display:none;\" >";
		
		if (!SearchFunctions::PrintIndiSearchResults($search_controller)) print "<div id=\"no_tab1\"></div>";
		print "<br /></div>";
		
		// print the fams
		print "<div id=\"fams\" class=\"tab_page\" style=\"display:none;\" >";
		
		if (!SearchFunctions::PrintFamSearchResults($search_controller)) print "<div id=\"no_tab2\"></div>";
		print "<br /></div>";
		
		// Print the sources
		print "<div id=\"sources\" class=\"tab_page\" style=\"display:none;\" >";
		
		if (!SearchFunctions::PrintSourceSearchResults($search_controller)) print "<div id=\"no_tab3\"></div>";
		print "<br /></div>";
		
		// Print the repositories
		print "<div id=\"repos\" class=\"tab_page\" style=\"display:none;\" >";
		
		if (!SearchFunctions::PrintRepoSearchResults($search_controller)) print "<div id=\"no_tab4\"></div>";
		print "<br /></div>";
		
		// Print the media
		print "<div id=\"media\" class=\"tab_page\" style=\"display:none;\" >";
		if (count($search_controller->media_total) > 0) {
			
			$ctm = count($search_controller->printmedia);
			print "\n\t<table class=\"list_table  $TEXT_DIRECTION\">\n\t\t<tr><td class=\"shade2 center\"";
			if($ctm > 12) print " colspan=\"2\"";
			print "><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["media"]["small"]."\" border=\"0\" title=\"".GM_LANG_media."\" alt=\"".GM_LANG_media."\" />&nbsp;&nbsp;";
			print GM_LANG_media;
			print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
			$media_private = array();
			$i=1;
			foreach ($search_controller->printmedia as $key => $mediakey) {
				$media = $search_controller->smedialist[$mediakey];
				if (!$media->PrintListMedia()) $media_private[$media->key] = true;
				if ($i==ceil($ctm/2) && $ctm>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
				$i++;
			}
			print "\n\t\t</ul></td>\n\t\t</tr>";
			
			print "<tr><td>".GM_LANG_total_media." ".count($search_controller->media_total);
			if (count($search_controller->media_hide) > 0) print "  --  ".GM_LANG_hidden." ".count($search_controller->media_hide);
			if (count($media_private)>0) print "&nbsp;--&nbsp;".GM_LANG_private." ".count($media_private);
			if (count($media_private) > 0 || count($search_controller->media_hide) > 0) PrintHelpLink("privacy_error_help", "qm");
			print "</td></tr>";
			print "</table><br />";
		}
		else print "<div id=\"no_tab5\"></div>";
		print "<br /></div>";
		
		// Print the notes
		print "<div id=\"notes\" class=\"tab_page\" style=\"display:none;\" >";
		if (!SearchFunctions::PrintNoteSearchResults($search_controller)) print "<div id=\"no_tab6\"></div>";
		print "<br /></div>";
		
		print "</div>"; // End result div
	}
}

// ----- section to search and display results for a Soundex last name search
if ($search_controller->action == "soundex") {
	if ($search_controller->soundex == "DaitchM") DMsoundex("", "closecache");
// 	$query = "";	// Stop function PrintReady from doing strange things to accented names
	if (!is_null($search_controller->lastname) || !is_null($search_controller->firstname) || !is_null($search_controller->place)) {
		$ct = count($search_controller->printindiname);
		if ($ct > 0) {
			print "<div class=\"search_results\"><br />";
			print "\n\t<table class=\"list_table $TEXT_DIRECTION\">\n\t\t<tr>\n\t\t";
			$extrafams = false;
			if (count($search_controller->printfamname) > 0) $extrafams = true;
			if ($extrafams) {
				print "<td class=\"topbottombar\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]."\" border=\"0\" alt=\"\" /> ".GM_LANG_people."</td>";
				print "<td class=\"topbottombar\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sfamily"]["small"]."\" border=\"0\" alt=\"\" /> ".GM_LANG_families."</td>";
			}
			else print "<td colspan=\"2\" class=\"topbottombar\"><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]."\" border=\"0\" alt=\"\" /> ".GM_LANG_people."</td>";
			print "</tr><tr>\n\t\t<td class=\"shade1 wrap\"><ul>";

			$i=1;
			$indi_private = array();
			foreach($search_controller->printindiname as $pkey => $pvalue) {
				$person = $search_controller->sindilist[JoinKey($pvalue[1], $pvalue[2])];
				if (!$person->PrintListPerson(true, false, "", $pvalue[4], "", $pvalue[3])) $indi_private[$person->key] = true;;
				print "\n";
				if (!$extrafams && $i == ceil($ct/2) && $ct>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
				$i++;
			}
			print "\n\t\t</ul></td>";

			// Start printing the associated fams
			if ($extrafams) {
				print "\n\t\t<td class=\"shade1 wrap\"><ul>";
				$fam_private = array();
				foreach($search_controller->printfamname as $pkey => $pvalue) {
					$fam = Family::GetInstance($pvalue[1], "", $pvalue[2]);
					if (!$fam->PrintListFamily(true, "", $pvalue[0], $pvalue[3])) $fam_private[$fam->key] = true;
					print "\n";
				}
				print "\n\t\t</ul>&nbsp;</td>";
			}

			// start printing the table footer
			print "\n\t\t</tr>\n\t";
			print "<tr><td ";
			if ((!$extrafams) && ($ct > 9)) print "colspan=\"2\">";
			else print ">";
			print GM_LANG_total_indis." ".count($search_controller->indi_total);
			if (count($indi_private)>0) print "  (".GM_LANG_private." ".count($indi_private).")";
			if (count($search_controller->indi_hide)>0) print "  --  ".GM_LANG_hidden." ".count($search_controller->indi_hide);
			if (count($indi_private)>0 || count($search_controller->indi_hide)>0) PrintHelpLink("privacy_error_help", "qm");
			print "</td>";
			if ($extrafams) {
				print "<td>".GM_LANG_total_fams." ".count($search_controller->fam_total);
				if (count($fam_private)>0) print "  (".GM_LANG_private." ".count($fam_private).")";
				if (count($search_controller->fam_hide)>0) print "  --  ".GM_LANG_hidden." ".count($search_controller->fam_hide);
				if (count($fam_private)>0 || count($search_controller->fam_hide)>0) PrintHelpLink("privacy_error_help", "qm");
				print "</td>";
			}
			print "</tr>";
			print "</table></div>";
		}
		else if (is_null($search_controller->topsearch)) print "<br style=\"clear: both;\" /><br /><div class=\"warning width100\" style=\" text-align: center;\"><i>".GM_LANG_no_results."</i></div>\n\t\t";
	}
	else if (is_null($search_controller->topsearch)) print "<br style=\"clear: both;\" /><br /><div class=\"warning width100\" style=\" text-align: center;\"><i>".GM_LANG_no_results."</i><br /></div>\n\t\t";
}
print "<br />";
if ($search_controller->action == "general") {
	if(!is_null($search_controller->srindi) && count($search_controller->indi_total) > 0) $tab = 1;
	else if(count($search_controller->fam_total) > 0) $tab = 2;
	else if(count($search_controller->sour_total) > 0) $tab = 3;
	else if(count($search_controller->repo_total) > 0) $tab = 4;
	else if(count($search_controller->media_total) > 0) $tab = 5;
	else if(count($search_controller->note_total) > 0) $tab = 6;
	else if ($search_controller->query != "") {
		print "<br style=\"clear: both;\" /><div class=\"warning\" style=\" text-align: center;\"><i>".GM_LANG_no_results."</i><br /></div>";
		print "<div id=\"no_tab0\"></div>";
		$tab = "0";
	}
	else $tab = 0;
	if ($tab != "0") {
		print "<script type=\"text/javascript\">\n<!--\n";
		print "tabswitch($tab)";
		print "\n//-->\n</script>\n";
	}
}

PrintFooter();
?>
