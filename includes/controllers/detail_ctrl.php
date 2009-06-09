<?php
/**
 * Base controller for most detail pages (source, note, repository, media)
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
 * Page does not validate see line number 1109 -> 15 August 2005
 *
 * @package Genmod
 * @subpackage Controllers
 * @version $Id$
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
 
/**
 * The base controller for all classes
 *
 * The base controller for all classes. Also check if it is a print preview.
 *
 * @author	Genmod Development Team
 * @param		string	$view		Show the data
 * @return 	string	Return the value of $view
 * @todo Update this description
 */
class DetailController extends BaseController{
	
	public function PrintTabs() {
		global $GEDCOMID, $Users, $gm_username, $gm_lang;
		global $GM_IMAGE_DIR, $GM_IMAGES, $TEXT_DIRECTION;
		global $indi_hide, $fam_hide, $source_hide, $media_hide, $repo_hide, $note_hide;
		
		// First determine what controller is calling this function
		// Depending on this, we define which tabs must be shown.
		// The array always starts with '0', which indicates that the "all" option.
		switch (get_class($this)) {
			case "SourceController":
				$tabs = array('0','facts','individuals','families','notes','media');
				$type = "sour";
				$object_name = "source";
				break;
			case "NoteController":
				$tabs = array('0','facts','individuals','families','sources','media','repositories');
				$type = "note";
				$object_name = "note";
				break;
		}
		?>
		<script type="text/javascript">
		<!--
		function tabswitch(n) {
			sndReq('dummy', 'remembertab', 'xref', '<?php print JoinKey($this->xref, $GEDCOMID); ?>' , 'tab_tab', n, 'type', '<?php print $type; ?>');
			if (n==<?php print count($tabs); ?>) n = 0;
			var tabid = new Array(<?php print "'".implode("','", $tabs)."'"; ?>);
			// show all tabs ?
			var disp='none';
			if (n==0) disp='block';
			// reset all tabs areas
			for (i=1; i<tabid.length; i++) document.getElementById(tabid[i]).style.display=disp;
			if ('<?php echo $this->view; ?>' != 'preview') {
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
		if (!$this->IsPrintPreview()) {
			// Print message is any changes to links are present
			if (HasUnapprovedLinks($this->xref)) print $gm_lang["unapproved_link"];
			print "<div class=\"door center\">";
			print "<dl>";
			foreach ($tabs as $index => $tab) {
				if ($index != 0) {
					print "<dd id=\"door".$index."\"><a href=\"javascript:;\" onclick=\"tabswitch(".$index.")\" >";
					if ($tab == "facts") print $gm_lang["facts"]."</a></dd>\n";
					if ($tab == "individuals") print $gm_lang["indi_linking"]." (".$this->$object_name->indi_count.")</a></dd>\n";
					if ($tab == "families") print $gm_lang["fam_linking"]." (".$this->$object_name->fam_count.")</a></dd>\n";
					if ($tab == "notes") print $gm_lang["note_linking"]." (".$this->$object_name->note_count.")</a></dd>\n";
					if ($tab == "media") print $gm_lang["mm_linking"]." (".$this->$object_name->media_count.")</a></dd>\n";
					if ($tab == "sources") print $gm_lang["sour_linking"]." (".$this->$object_name->sour_count.")</a></dd>\n";
					if ($tab == "repositories") print $gm_lang["repo_linking"]." (".$this->$object_name->repo_count.")</a></dd>\n";
					if ($tab == "actions") print $gm_lang["action_linking"]." (".$this->$object_name->action_count.")</a></dd>\n";
				}
			}
			print "<dd id=\"door0\"><a href=\"javascript:;\" onclick=\"tabswitch(0)\" >".$gm_lang["all"]."</a></dd>\n";
			print "</dl>\n";
			print "</div><div id=\"dummy\"></div><br /><br />\n";
		}
		foreach ($tabs as $index => $tabtype) {
			if ($tabtype == "facts") {
				// Facts
				print "<div id=\"facts\" class=\"tab_page\" style=\"display:none;\" >";
				
				$facts = $this->$object_name->facts;
				print "\n<table class=\"facts_table\">";
				if ($type == "note") $this->PrintGeneralNote();
				foreach($this->$object_name->facts as $key => $value) {
					$fact = trim($value[0]);
					if (!empty($fact)) {
						$styleadd = $value[3];
						if ($fact=="OBJE") {
							print_main_media($value[1], $this->xref, 0, $value[2], ($this->$object_name->show_changes), $value[3]);
						}
						else if ($fact=="SOUR") {
							print_main_sources($value[1], 1, $this->xref, $value[2], "", $this->$object_name->canedit);
						}
						else if ($fact=="NOTE") {
							print_main_notes($value[1], 1, $this->xref, $value[2], $value[3]);
						}
						else {
							print_fact($value[1], $this->xref, $value[0], $value[2], false, $value[3]);
						}
					}
				}
				
				//-- new fact link
				if ($this->view != "preview" && $Users->userCanEdit($gm_username) && !$this->$object_name->deleted) {
					PrintAddNewFact($this->xref, $this->$object_name->facts, strtoupper($object_name));
				}
				print "</table>\n\n<br />";
				print "</div>";
				if ($this->IsPrintPreview()) { 
					print "<br /><span class=\"label\">";
					if ($type == "sour") print $gm_lang["other_records"];
					else if ($type == "media") print $gm_lang["other_mmrecords"];
					else print $gm_lang["other_".$type."_records"];
					print "</span>";
				}
			}
			if ($tabtype == "individuals") {
				// -- array of individuals
				print "<div id=\"individuals\" class=\"tab_page\" style=\"display:none;\" >";
				
				if ($this->$object_name->indi_count>0) {
					print "\n\t<table class=\"list_table $TEXT_DIRECTION\">\n\t\t<tr><td class=\"shade2 center\"";
					if($this->$object_name->indi_count>12)	print " colspan=\"2\"";
					print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]."\" border=\"0\" title=\"".$gm_lang["individuals"]."\" alt=\"".$gm_lang["individuals"]."\" />&nbsp;&nbsp;";
					print $gm_lang["individuals"];
					print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
					$i=1;
					// -- print the array
					foreach ($this->$object_name->indilist as $key => $value) {
						// Check if we can display source references for this indi
						$addname = "";
						if (HasChinese($value["names"][0][0])) $addname = " (".GetSortableAddName($key, false).")";
						print_list_person($key, array(CheckNN(GetSortableName($key)).$addname, get_gedcom_from_id($value["gedfile"])));
						print "\n";
						if ($i==ceil($this->$object_name->indi_count/2) && $this->$object_name->indi_count>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
						$i++;
					}
				
					print "\n\t\t</ul></td>\n\t\t";
				
					print "</tr>";
						if ($this->$object_name->indi_count>0) { 
							print "<tr><td>";
							print $gm_lang["total_indis"]." ".$this->$object_name->indi_count;
							if (count($indi_hide)>0) print "&nbsp;--&nbsp;".$gm_lang["hidden"]." ".count($indi_hide);
							print "</td></tr>";
						}
					print "</table><br />";
				}
				else print "<div id=\"no_tab".$index."\"></div>";
				print "</div>";
			}
			if ($tabtype == "families") {
				// -- array of families
				print "<div id=\"families\" class=\"tab_page\" style=\"display:none;\" >";
				
				if ($this->$object_name->fam_count>0) {
					print "\n\t<table class=\"list_table  $TEXT_DIRECTION\">\n\t\t<tr><td class=\"shade2 center\"";
					if($this->$object_name->fam_count>12)	print " colspan=\"2\"";
					print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["sfamily"]["small"]."\" border=\"0\" title=\"".$gm_lang["families"]."\" alt=\"".$gm_lang["families"]."\" />&nbsp;&nbsp;";
					print $gm_lang["families"];
					print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
					$i=1;
					foreach ($this->$object_name->famlist as $key => $value) {
						$addname = "";
						if (HasChinese($value["name"])) $addname = " (".GetFamilyAddDescriptor($key, false, $value["gedcom"]).")";
						print_list_family($key, array(GetFamilyDescriptor($key).$addname, get_gedcom_from_id($value["gedfile"])));
						if ($i==ceil($this->$object_name->fam_count/2) && $this->$object_name->fam_count>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
						$i++;
					}
					print "\n\t\t</ul></td>\n\t\t";
				
					print "</tr>";
					if ($this->$object_name->fam_count>0) { 
						print "<tr><td>";
						print $gm_lang["total_fams"]." ".$this->$object_name->fam_count;
						if (count($fam_hide)>0) print "&nbsp;--&nbsp;".$gm_lang["hidden"]." ".count($fam_hide);
						print "</td></tr>";
					}
					print "</table><br />";
				}
				else print "<div id=\"no_tab".$index."\"></div>";
				print "</div>";
			}
			if ($tabtype == "notes") {
				// array of notes
				print "<div id=\"notes\" class=\"tab_page\" style=\"display:none;\" >";
				
				if ($this->$object_name->note_count>0) {
					print "\n\t<table class=\"list_table $TEXT_DIRECTION\">\n\t\t<tr><td class=\"shade2 center\"";
					if($this->$object_name->note_count > 12) print " colspan=\"2\"";
					print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["note"]["other"]."\" border=\"0\" title=\"".$gm_lang["notes"]."\" alt=\"".$gm_lang["notes"]."\" />&nbsp;&nbsp;";
					print $gm_lang["titles_found"];
					print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
					$i=1;
					foreach ($this->$object_name->notelist as $key => $note) {
						$note->PrintListNote();
						if ($i==ceil($this->$object_name->note_count/2) && $this->$object_name->note_count>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
						$i++;
					}
					print "\n\t\t</ul></td>\n\t\t";
				 
					print "</tr>";
					if ($this->$object_name->note_count>0) { 
						print "<tr><td>";
						print $gm_lang["total_notes"]." ".$this->$object_name->note_count;
						if (count($note_hide)>0) print "&nbsp;--&nbsp;".$gm_lang["hidden"]." ".count($note_hide);
						print "</td></tr>";
					}
					print "</table><br />";
				}
				else print "<div id=\"no_tab".$index."\"></div>";
				print "</div>";

			}
			if ($tabtype == "sources") {
				// -- array of sources
				print "<div id=\"sources\" class=\"tab_page\" style=\"display:none;\" >";
				
				if ($this->$object_name->sour_count>0) {
					print "\n\t<table class=\"list_table  $TEXT_DIRECTION\">\n\t\t<tr><td class=\"shade2 center\"";
					if($this->$object_name->sour_count>12)	print " colspan=\"2\"";
					print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["source"]["small"]."\" border=\"0\" title=\"".$gm_lang["sources"]."\" alt=\"".$gm_lang["sources"]."\" />&nbsp;&nbsp;";
					print $gm_lang["sources"];
					print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
					$i=1;
					// -- print the array
					foreach ($this->$object_name->sourcelist as $key => $value) {
						print_list_source($key, $value);
						if ($i==ceil($this->$object_name->sour_count/2) && $this->$object_name->sour_count>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
						$i++;
					}
				
					print "\n\t\t</ul></td>\n\t\t";
				
					print "</tr>";
						if ($this->$object_name->sour_count>0) { 
							print "<tr><td>";
							print $gm_lang["total_sources"]." ".$this->$object_name->sour_count;
							if (count($source_hide)>0) print "&nbsp;--&nbsp;".$gm_lang["hidden"]." ".count($source_hide);
							print "</td></tr>";
						}
					print "</table><br />";
				}
				else print "<div id=\"no_tab".$index."\"></div>";
				print "</div>";
			}
			if ($tabtype == "media") {
				// -- array of media
				print "<div id=\"media\" class=\"tab_page\" style=\"display:none;\" >";
				
				if ($this->$object_name->media_count>0) {
					print "\n\t<table class=\"list_table  $TEXT_DIRECTION\">\n\t\t<tr><td class=\"shade2 center\"";
					if($this->$object_name->media_count>12)	print " colspan=\"2\"";
					print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["media"]["small"]."\" border=\"0\" title=\"".$gm_lang["media"]."\" alt=\"".$gm_lang["media"]."\" />&nbsp;&nbsp;";
					print $gm_lang["media"];
					print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
					$i=1;
					foreach ($this->$object_name->medialist as $key => $value) {
						print_list_media($key, $value);
						if ($i==ceil($this->$object_name->media_count/2) && $this->$object_name->media_count>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
						$i++;
					}
					print "\n\t\t</ul></td>\n\t\t";
				
					print "</tr>";
					if ($this->$object_name->media_count>0) { 
						print "<tr><td>";
						print $gm_lang["total_media"]." ".$this->$object_name->media_count;
						if (count($media_hide)>0) print "&nbsp;--&nbsp;".$gm_lang["hidden"]." ".count($media_hide);
						print "</td></tr>";
					}
					print "</table><br />";
				}
				else print "<div id=\"no_tab".$index."\"></div>";
				print "</div>";
			}
			if ($tabtype == "repositories") {
				// -- array of repositories
				print "<div id=\"repositories\" class=\"tab_page\" style=\"display:none;\" >";
				
				if ($this->$object_name->repo_count>0) {
					print "\n\t<table class=\"list_table  $TEXT_DIRECTION\">\n\t\t<tr><td class=\"shade2 center\"";
					if($this->$object_name->repo_count>12)	print " colspan=\"2\"";
					print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["repository"]["small"]."\" border=\"0\" title=\"".$gm_lang["repos"]."\" alt=\"".$gm_lang["repos"]."\" />&nbsp;&nbsp;";
					print $gm_lang["repos"];
					print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
					$i=1;
					if ($this->$object_name->repo_count>0){
						$i=1;
						// -- print the array
						foreach ($this->$object_name->repolist as $key => $value) {
							print_list_repository($key, $value);
							if ($i==ceil($this->$object_name->repo_count/2) && $this->$object_name->repo_count>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
							$i++;
						}
						print "\n\t\t</ul></td>\n\t\t";
				 	
						print "</tr><tr><td>".$gm_lang["total_repositories"]." ".$this->$object_name->repo_count;
						if (count($repo_hide)>0) print "  --  ".$gm_lang["hidden"]." ".count($repo_hide);
					}
					print "</table><br />";
				}
				else print "<div id=\"no_tab".$index."\"></div>";
				print "</div>";
			}
		}
		print "<script type=\"text/javascript\">\n<!--\n";
		if ($this->isPrintPreview()) print "tabswitch(0)";
		else if (isset($_SESSION[$type][JoinKey($this->$object_name->xref, $GEDCOMID)])) print "tabswitch(".$_SESSION[$type][JoinKey($this->$object_name->xref, $GEDCOMID)].")";
		else print "tabswitch(1)";
		print "\n//-->\n</script>\n";
	}
}
?>