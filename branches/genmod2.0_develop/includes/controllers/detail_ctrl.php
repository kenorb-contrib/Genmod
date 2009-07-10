<?php
/**
 * Base controller for most detail pages (individual, source, note, repository, media)
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
	
	public $classname = "DetailController";
	
	private $tabs = null;
	private $tabtype = null;
	private $object_name = null;
	
	// Holders and counters for linked objects
	
	public function __construct() {
		
		parent::__construct();
		
		// First determine what controller is calling this function
		// Depending on this, we define which tabs must be shown.
		// The array always starts with '0', which indicates the "all" option.
		switch (get_class($this)) {
			case "IndividualController":
				$this->tabs = array('0', 'relatives', 'facts', 'sources', 'media', 'notes', 'actions_person');
				$this->tabtype = "indi";
				$this->object_name = "indi";
				break;
			case "FamilyController":
				$this->tabs = array();
				$this->tabtype = "fam";
				$this->object_name = "family";
				break;
			case "SourceController":
				$this->tabs = array('0','facts','individuals_links','families_links','notes_links','media_links');
				$this->tabtype = "sour";
				$this->object_name = "source";
				break;
			case "MediaController":
				$this->tabs = array('0','facts','individuals_links','families_links','sources_links','repositories_links');
				$this->tabtype = "obje";
				$this->object_name = "media";
				break;
			case "NoteController":
				$this->tabs = array('0','facts','individuals_links','families_links','sources_links','media_links','repositories_links');
				$this->tabtype = "note";
				$this->object_name = "note";
				break;
			case "RepositoryController":
				$this->tabs = array('0','facts','sources_links','actions_links');
				$this->tabtype = "repo";
				$this->object_name = "repo";
				break;
			default:
				return false;
		}
		
	}
	
	public function __get($property) {
		switch($property) {
		}
	}
	
	public function PrintTabs() {
		global $GEDCOMID, $Users, $gm_username, $gm_lang, $Actions;
		global $GM_IMAGE_DIR, $GM_IMAGES, $TEXT_DIRECTION;
		
		$object_name = $this->object_name;
		?>
		<script type="text/javascript">
		<!--
		function tabswitch(n) {
			sndReq('dummy', 'remembertab', 'xref', '<?php print JoinKey($this->xref, $GEDCOMID); ?>' , 'tab_tab', n, 'type', '<?php print $this->tabtype; ?>');
			if (n==<?php print count($this->tabs); ?>) n = 0;
			var tabid = new Array(<?php print "'".implode("','", $this->tabs)."'"; ?>);
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
			if (HasUnapprovedLinks($this->xref) && $this->show_changes) print $gm_lang["unapproved_link"];
			print "<div class=\"door center\">";
			print "<dl>";
			foreach ($this->tabs as $index => $tab) {
				if ($index != 0) {
					print "<dd id=\"door".$index."\"><a href=\"javascript:;\" onclick=\"tabswitch(".$index.")\" >";
					if ($tab == "facts") print $gm_lang["facts"]."</a></dd>\n";
					if ($tab == "individuals_links") print $gm_lang["indi_linking"]." (".$this->$object_name->indi_count.")</a></dd>\n";
					if ($tab == "families_links") print $gm_lang["fam_linking"]." (".$this->$object_name->fam_count.")</a></dd>\n";
					if ($tab == "notes_links") print $gm_lang["note_linking"]." (".$this->$object_name->note_count.")</a></dd>\n";
					if ($tab == "media_links") print $gm_lang["mm_linking"]." (".$this->$object_name->media_count.")</a></dd>\n";
					if ($tab == "sources_links") print $gm_lang["sour_linking"]." (".$this->$object_name->sour_count.")</a></dd>\n";
					if ($tab == "repositories_links") print $gm_lang["repo_linking"]." (".$this->$object_name->repo_count.")</a></dd>\n";
					if ($tab == "actions_links") print $gm_lang["action_linking"]." (".$this->$object_name->action_count.")</a></dd>\n";
					if ($tab == "relatives") print $gm_lang["relatives"]."</a></dd>\n";
					if ($tab == "sources") print $gm_lang["ssourcess"]."</a></dd>\n";
					if ($tab == "media") print $gm_lang["media"]."</a></dd>\n";
					if ($tab == "notes") print $gm_lang["notes"]."</a></dd>\n";
					if ($tab == "actions_person") print $gm_lang["research_log"]."</a></dd>\n";
				}
			}
			print "<dd id=\"door0\"><a href=\"javascript:;\" onclick=\"tabswitch(0)\" >".$gm_lang["all"]."</a></dd>\n";
			print "</dl>\n";
			print "</div><div id=\"dummy\"></div><br /><br />\n";
		}
		foreach ($this->tabs as $index => $tab) {
			if ($tab == "facts") {
				
				if ($this->tabtype == "indi") {
					$this->PrintToggleJS1();
					global $n_chil, $n_gchi;
					$n_chil = 1; // counter for children on facts page
					$n_gchi = 1;
				}
				
				// Facts
				print "<div id=\"facts\" class=\"tab_page\" style=\"display:none;\" >";
					
				print "\n<table class=\"facts_table\">";
				
				if ($this->tabtype == "indi") {
					echo '<tr id="row_top"><td></td><td class="shade2 rela">';
					echo '<a href="#" onclick="togglerow(\'row_rela\'); return false;">';
					echo '<img style="display:none;" id="rela_plus" src="'.$GM_IMAGE_DIR.'/'.$GM_IMAGES["plus"]["other"].'" border="0" width="11" height="11" alt="'.$gm_lang["show_details"].'" title="'.$gm_lang["show_details"].'" />';
					echo '<img id="rela_minus" src="'.$GM_IMAGE_DIR.'/'.$GM_IMAGES["minus"]["other"].'" border="0" width="11" height="11" alt="'.$gm_lang["hide_details"].'" title="'.$gm_lang["hide_details"].'" />';
					echo ' '.$gm_lang["relatives_events"];
					echo '</a></td></tr>';
				}
				
				if ($this->tabtype == "note") $this->PrintGeneralNote();
				foreach($this->$object_name->facts as $key => $value) {
					$fact = trim($value[0]);
					if (!empty($fact)) {
						$styleadd = $value[3];
						if ($fact=="OBJE") {
							if ($object_name != "indi") print_main_media($value[1], $this->$object_name->xref, 0, $value[2], ($this->$object_name->show_changes), $value[3]);
						}
						else if ($fact=="SOUR") {
							if ($object_name != "indi") print_main_sources($value[1], 1, $this->$object_name->xref, $value[2], $value[3], $this->$object_name->canedit);
						}
						else if ($fact=="NOTE") {
							if ($object_name != "indi") print_main_notes($value[1], 1, $this->$object_name->xref, $value[2], $value[3]);
						}
						else {
							if ($object_name != "indi" || ($object_name == "indi" && $fact != "SEX" && $fact != "NAME")) print_fact($value[1], $this->$object_name->xref, $value[0], $value[2], false, $value[3]);
						}
					}
				}
				
				//-- new fact link
				if ($this->view != "preview" && $this->$object_name->canedit && !$this->$object_name->isdeleted) {
					PrintAddNewFact($this->$object_name->xref, $this->$object_name->facts, strtoupper($this->tabtype));
				}
				print "</table>\n\n<br />";
				print "</div>";
				if ($this->IsPrintPreview()) { 
					print "<br /><span class=\"label\">";
					if ($this->tabtype == "sour") print $gm_lang["other_records"];
					else if ($this->tabtype == "media") print $gm_lang["other_mmrecords"];
					else print $gm_lang["other_".$this->tabtype."_records"];
					print "</span>";
				}
				if ($this->tabtype == "indi") $this->PrintToggleJS2();
			}
			if ($tab == "individuals_links") {
				// -- array of individuals
				print "<div id=\"individuals_links\" class=\"tab_page\" style=\"display:none;\" >";
				
				if ($this->$object_name->indi_count>0) {
					print "\n\t<table class=\"list_table $TEXT_DIRECTION\">\n\t\t<tr><td class=\"shade2 center\"";
					if($this->$object_name->indi_count>12)	print " colspan=\"2\"";
					print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]."\" border=\"0\" title=\"".$gm_lang["individuals"]."\" alt=\"".$gm_lang["individuals"]."\" />&nbsp;&nbsp;";
					print $gm_lang["individuals"];
					print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
					$i=1;
					// -- print the array
					foreach ($this->$object_name->indilist as $key => $indi) {
						$addname = "";
						if (HasChinese($indi->name_array[0][0])) $addname = " (".$indi->sortable_addname.")";
						$indi->PrintListPerson();
						if ($i==ceil($this->$object_name->indi_count/2) && $this->$object_name->indi_count>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
						$i++;
					}
				
					print "\n\t\t</ul></td>\n\t\t";
				
					print "</tr>";
						if ($this->$object_name->indi_count>0) { 
							print "<tr><td>";
							print $gm_lang["total_indis"]." ".$this->$object_name->indi_count;
							if ($this->$object_name->indi_hide>0) print "&nbsp;--&nbsp;".$gm_lang["hidden"]." ".$this->$object_name->indi_hide;
							print "</td></tr>";
						}
					print "</table><br />";
				}
				else print "<div id=\"no_tab".$index."\"></div>";
				print "</div>";
			}
			if ($tab == "families_links") {
				// -- array of families
				print "<div id=\"families_links\" class=\"tab_page\" style=\"display:none;\" >";
				
				if ($this->$object_name->fam_count>0) {
					print "\n\t<table class=\"list_table  $TEXT_DIRECTION\">\n\t\t<tr><td class=\"shade2 center\"";
					if($this->$object_name->fam_count>12)	print " colspan=\"2\"";
					print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["sfamily"]["small"]."\" border=\"0\" title=\"".$gm_lang["families"]."\" alt=\"".$gm_lang["families"]."\" />&nbsp;&nbsp;";
					print $gm_lang["families"];
					print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
					$i=1;
					foreach ($this->$object_name->famlist as $key => $family) {
						$family->PrintListFamily();
						if ($i==ceil($this->$object_name->fam_count/2) && $this->$object_name->fam_count>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
						$i++;
					}
					print "\n\t\t</ul></td>\n\t\t";
				
					print "</tr>";
					if ($this->$object_name->fam_count>0) { 
						print "<tr><td>";
						print $gm_lang["total_fams"]." ".$this->$object_name->fam_count;
						if ($this->$object_name->fam_hide>0) print "&nbsp;--&nbsp;".$gm_lang["hidden"]." ".$this->$object_name->fam_hide;
						print "</td></tr>";
					}
					print "</table><br />";
				}
				else print "<div id=\"no_tab".$index."\"></div>";
				print "</div>";
			}
			if ($tab == "notes_links") {
				// array of notes
				print "<div id=\"notes_links\" class=\"tab_page\" style=\"display:none;\" >";
				
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
						if ($this->$object_name->note_hide>0) print "&nbsp;--&nbsp;".$gm_lang["hidden"]." ".$this->$object_name->note_hide;
						print "</td></tr>";
					}
					print "</table><br />";
				}
				else print "<div id=\"no_tab".$index."\"></div>";
				print "</div>";

			}
			if ($tab == "sources_links") {
				// -- array of sources
				print "<div id=\"sources_links\" class=\"tab_page\" style=\"display:none;\" >";
				
				if ($this->$object_name->sour_count>0 || $this->$object_name->sour_hide>0) {
					print "\n\t<table class=\"list_table  $TEXT_DIRECTION\">\n\t\t<tr><td class=\"shade2 center\"";
					if($this->$object_name->sour_count>12)	print " colspan=\"2\"";
					print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["source"]["small"]."\" border=\"0\" title=\"".$gm_lang["sources"]."\" alt=\"".$gm_lang["sources"]."\" />&nbsp;&nbsp;";
					print $gm_lang["sources"];
					print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
					$i=1;
					// -- print the array
					foreach ($this->$object_name->sourcelist as $key => $object) {
						$object->PrintListSource();
						if ($i==ceil($this->$object_name->sour_count/2) && $this->$object_name->sour_count>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
						$i++;
					}
				
					print "\n\t\t</ul></td>\n\t\t";
				
					print "</tr>";
						if ($this->$object_name->sour_count>0) { 
							print "<tr><td>";
							print $gm_lang["total_sources"]." ".$this->$object_name->sour_count;
							if ($this->$object_name->sour_hide>0) print "&nbsp;--&nbsp;".$gm_lang["hidden"]." ".$this->$object_name->sour_hide;
							print "</td></tr>";
						}
					print "</table><br />";
				}
				else print "<div id=\"no_tab".$index."\"></div>";
				print "</div>";
			}
			if ($tab == "media_links") {
				// -- array of media
				print "<div id=\"media_links\" class=\"tab_page\" style=\"display:none;\" >";
				
				if ($this->$object_name->media_count > 0 || $this->$object_name->media_hide > 0) {
					print "\n\t<table class=\"list_table  $TEXT_DIRECTION\">\n\t\t<tr><td class=\"shade2 center\"";
					if($this->$object_name->media_count>12)	print " colspan=\"2\"";
					print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["media"]["small"]."\" border=\"0\" title=\"".$gm_lang["media"]."\" alt=\"".$gm_lang["media"]."\" />&nbsp;&nbsp;";
					print $gm_lang["media"];
					print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
					$i=1;
					foreach ($this->$object_name->medialist as $key => $object) {
						$object->PrintListMedia();
						if ($i==ceil($this->$object_name->media_count/2) && $this->$object_name->media_count>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
						$i++;
					}
					print "\n\t\t</ul></td>\n\t\t";
				
					print "</tr>";
					if ($this->$object_name->media_count>0) { 
						print "<tr><td>";
						print $gm_lang["total_media"]." ".$this->$object_name->media_count;
						if ($this->$object_name->media_hide > 0) print "&nbsp;--&nbsp;".$gm_lang["hidden"]." ".$this->$object_name->media_hide;
						print "</td></tr>";
					}
					print "</table><br />";
				}
				else print "<div id=\"no_tab".$index."\"></div>";
				print "</div>";
			}
			if ($tab == "repositories_links") {
				// -- array of repositories
				print "<div id=\"repositories_links\" class=\"tab_page\" style=\"display:none;\" >";
				
				if ($this->$object_name->repo_count > 0 || $this->$object_name->repo_hide > 0) {
					print "\n\t<table class=\"list_table  $TEXT_DIRECTION\">\n\t\t<tr><td class=\"shade2 center\"";
					if($this->$object_name->repo_count>12)	print " colspan=\"2\"";
					print "><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["repository"]["small"]."\" border=\"0\" title=\"".$gm_lang["repos"]."\" alt=\"".$gm_lang["repos"]."\" />&nbsp;&nbsp;";
					print $gm_lang["repos"];
					print "</td></tr><tr><td class=\"$TEXT_DIRECTION shade1 wrap\"><ul>";
					$i=1;
					if ($this->$object_name->repo_count>0){
						$i=1;
						// -- print the array
						foreach ($this->$object_name->repolist as $key => $object) {
							$object->PrintListRepository();
							if ($i==ceil($this->$object_name->repo_count/2) && $this->$object_name->repo_count>12) print "</ul></td><td class=\"shade1 wrap\"><ul>\n";
							$i++;
						}
						print "\n\t\t</ul></td>\n\t\t";
				 	
						print "</tr><tr><td>".$gm_lang["total_repositories"]." ".$this->$object_name->repo_count;
						if ($this->$object_name->repo_hide > 0) print "  --  ".$gm_lang["hidden"]." ".$this->$object_name->repo_hide;
					}
					print "</table><br />";
				}
				else print "<div id=\"no_tab".$index."\"></div>";
				print "</div>";
			}
			if ($tab == "actions_links") {
				print "<div id=\"actions_links\" class=\"tab_page\" style=\"display:none;\" >";
				if ($this->$object_name->action_count>0 || $this->$object_name->action_hide>0) {
					
					// Start of todo list
					print "\n\t<table class=\"list_table $TEXT_DIRECTION\">";
					print "<tr><td colspan=\"3\" class=\"shade2 center\">".$gm_lang["actionlist"]."</td></tr>";
					print "<tr><td class=\"shade2 center\">".$gm_lang["todo"]."</td><td class=\"shade2 center\">".$gm_lang["for"]."</td><td class=\"shade2 center\">".$gm_lang["status"]."</td></tr>";
					foreach ($this->$object_name->actionlist as $key => $item) {
						print "<tr>";
						print "<td class=\"shade1 wrap\">".nl2br(stripslashes($item->text))."</td>";
						print "<td class=\"shade1\">";
						print "<a href=\"individual.php?pid=".$item->pid."\">".$item->indidesc."</a>";
						print "</td>";
						print "<td class=\"shade1\">".$gm_lang["action".$item->status]."</td>";
						print "</tr>";
					}
					print "<tr><td>".$gm_lang["total_actions"]." ".$this->$object_name->action_count;
					if ($this->$object_name->action_hide>0) print "  --  ".$gm_lang["hidden"]." ".$this->$object_name->action_hide;
					print "</table>";
				}
				else print "<div id=\"no_tab".$index."\"></div>";
				print "</div>";
			}
			if ($tab == "relatives") {
				print "<div id=\"relatives\" class=\"tab_page\" style=\"display:none;\" >";
				if ($this->$object_name->close_relatives) {
					$show_full = true;
					$prtcount = 0;
					// NOTE: parent families
					if (is_array($this->$object_name->childfamilies)) {
						foreach ($this->$object_name->childfamilies as $famid => $family) {
							// Family header
							print "<table>";
							print "<tr>";
							print "<td><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["cfamily"]["small"]."\" border=\"0\" class=\"icon\" alt=\"\" /></td>";
							print "<td><span class=\"subheaders\">".$family->label."</span>";
							if (!$this->view) {
						 		print " - <a href=\"family.php?famid=".$family->xref."\">[".$gm_lang["view_family"].$family->addxref."]</a>&nbsp;&nbsp";
					 			if ($family->husb_id == "" && $this->$object_name->canedit) { 
					 				print_help_link("edit_add_parent_help", "qm");
									print "<a href=\"javascript ".$gm_lang["add_father"]."\" onclick=\"return addnewparentfamily('', 'HUSB', '".$family->xref."', 'add_father');\">".$gm_lang["add_father"]."</a>";
								}
					 			if ($family->wife_id == "" && $this->$object_name->canedit) { 
					 				print_help_link("edit_add_parent_help", "qm");
									print "<a href=\"javascript ".$gm_lang["add_mother"]."\" onclick=\"return addnewparentfamily('', 'WIFE', '".$family->xref."', 'add_mother');\">".$gm_lang["add_mother"]."</a>";
								}
							}
							print "</td></tr>";
							print "</table>";
							// Husband and wife
							print "<table class=\"facts_table\">";
							if ($family->husb_id != "") {
								print "<tr><td class=\"width20 shade2 center\"";
								if ($this->show_changes && $family->husb->isnew) print " style=\"border: solid #0000FF 2px; vertical-align: middle;\"";
								else print " style=\"vertical-align: middle;\"";
								print ">";	
								print $family->husb->label."</td>";
								print "<td class=\"".$this->getPersonStyle($family->husb)."\">";
								PrintPedigreePerson($family->husb, 2, true, $prtcount, 1, $this->view);
								$prtcount++;
								print "</td></tr>";
							}
							if ($family->wife_id != "") {
								print "<tr><td class=\"width20 shade2 center\"";
								if ($this->show_changes && $family->wife->isnew) print " style=\"border: solid #0000FF 2px; vertical-align: middle;\"";
								else print " style=\"vertical-align: middle;\"";
								print ">";	
								print $family->wife->label."</td>";
								print "<td class=\"".$this->getPersonStyle($family->wife)."\">";
								PrintPedigreePerson($family->wife, 2, true, $prtcount, 1, $this->view);
								$prtcount++;
								print "</td></tr>";
							}
							// Children
							foreach ($family->children as $childid => $child) {
								if (isset($child->label[$famid])) {
									print "<tr><td class=\"width20 shade2 center";
									if ($this->show_changes) {
										if ($child->isnew) print " change_new";
										elseif ($child->isdeleted) print " change_old";
									}
									print "\" style=\"vertical-align: middle;\">";
									print $child->label[$famid]."</td>";
									print "<td class=\"".$this->getPersonStyle($child)."\">";
									PrintPedigreePerson($child, 2 , true, $prtcount, 1, $this->view);
									$prtcount++;
									print "</td></tr>";
								}
							}
							print "</table>";
						}
						// NOTE: Half-siblings father
						foreach ($this->$object_name->childfamilies as $id => $fam) {
							if ($fam->husb_id != "") {
								foreach ($fam->husb->spousefamilies as $famid => $family) {
									if ($fam->xref != $family->xref && $family->label != "") {
										print "<table><tr>";
										print "<td><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["cfamily"]["small"]."\" border=\"0\" class=\"icon\" alt=\"\" /></td>";
										print "<td><span class=\"subheaders\">".$family->label."</span>";
										if (!$this->view) print " - <a href=\"family.php?famid=".$family->xref."\">[".$gm_lang["view_family"].$family->addxref."]</a>";
										print "</td></tr></table>";
										
										print "<table class=\"facts_table\">";
										if ($family->wife_id != "") {
											print "<tr><td class=\"width20 shade2 center\" style=\"vertical-align: middle;\">".$family->wife->label."</td>";
											print "<td class=\"".$this->getPersonStyle($family->wife)."\">";
											PrintPedigreePerson($family->wife,2, true, $prtcount, 1, $this->view);
											$prtcount++;
											print "</td></tr>";
										}
										foreach ($family->children as $sibid => $sibling) {
											print "<tr><td class=\"width20 shade2 center\" style=\"vertical-align: middle;\">".$sibling->label."</td>";
											print "<td class=\"".$this->getPersonStyle($sibling)."\">";
											PrintPedigreePerson($sibling,2,true, $prtcount, 1, $this->view);
											$prtcount++;
											print "</td></tr>";
										}
										print "</table>";
									}
								}
							}
						}
	
						// NOTE: Half-siblings mother
						foreach ($this->$object_name->childfamilies as $id => $fam) {
							if ($fam->wife_id != "") {
								foreach ($fam->wife->spousefamilies as $famid => $family) {
									if ($fam->xref != $family->xref && $family->label != "") {
										print "<table><tr>";
										print "<td><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["cfamily"]["small"]."\" border=\"0\" class=\"icon\" alt=\"\" /></td>";
										print "<td><span class=\"subheaders\">".$family->label."</span>";
										if (!$this->view) print " - <a href=\"family.php?famid=".$family->xref."\">[".$gm_lang["view_family"].$family->addxref."]</a>";
										print "</td></tr></table>";
										
										print "<table class=\"facts_table\">";
										if ($family->husb_id != "") { 
											print "<tr><td class=\"width20 shade2 center\" style=\"vertical-align: middle;\">".$family->wife->label."</td>";
											print "<td class=\"".$this->getPersonStyle($family->husb)."\">";
											PrintPedigreePerson($family->husb,2,true, $prtcount, 1, $this->view);
											$prtcount++;
											print "</td></tr>";
										}
										foreach ($family->children as $sibid => $sibling) {
											print "<tr><td class=\"width20 shade2 center\" style=\"vertical-align: middle;\">".$sibling->label."</td>";
											print "<td class=\"".$this->getPersonStyle($sibling)."\">";
											PrintPedigreePerson($sibling,2,true, $prtcount, 1, $this->view);
											$prtcount++;
											print "</td></tr>";
										}
										print "</table>";
									}
								}
							}
						}
					}
					// NOTE: spouses and children
					if (is_array($this->$object_name->spousefamilies)) {
						foreach ($this->$object_name->spousefamilies as $famid => $family) {
							print "<table>";
							print "<tr>";
							print "<td><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["cfamily"]["small"]."\" border=\"0\" class=\"icon\" alt=\"\" /></td>";
							print "<td><span class=\"subheaders\">".$family->label."</span>";
							if (!$this->view) print " - <a href=\"family.php?famid=".$family->xref."\">[".$gm_lang["view_family"].$family->addxref."]</a>";
							print "</td></tr>";
							print "</table>";
							
							print "<table class=\"facts_table\">";
							if ($family->husb_id != "") {
								print "<tr><td class=\"width20 shade2 center";
								if ($this->show_changes) {
									if ($family->husb->isnew) print " change_new";
									elseif ($family->husb->isdeleted) print " change_old";
								}
								print "\" style=\"vertical-align: middle;\">";
								print $family->husb->label."</td>";
								print "<td class=\"".$this->getPersonStyle($family->husb)."\">";
								PrintPedigreePerson($family->husb, 2, true, $prtcount, 1, $this->view);
								$prtcount++;
								print "</td></tr>";
							}
							if ($family->wife_id != "") {
								print "<tr><td class=\"width20 shade2 center";
								if ($this->show_changes) {
									if ($family->wife->isnew) print " change_new";
									elseif ($family->wife->isdeleted) print " change_old";
								}
								print "\" style=\"vertical-align: middle;\">";
								print $family->wife->label."</td>";
								print "<td class=\"".$this->getPersonStyle($family->wife)."\">";
								PrintPedigreePerson($family->wife, 2, true, $prtcount, 1, $this->view);
								$prtcount++;
								print "</td></tr>";
							}
							foreach ($family->children as $kidid => $kid) {
								print "<tr><td class=\"width20 shade2 center"; 
								if ($this->show_changes) {
									if ($kid->isnew) print " change_new";
									else if ($kid->isdeleted) print " change_old";
								}
								print "\" style=\"vertical-align: middle;\">";
								print $kid->label."</td>";
								print "<td class=\"".$this->getPersonStyle($kid)."\">";
								PrintPedigreePerson($kid, 2, true, $prtcount, 1, $this->view);
								$prtcount++;
								print "</td></tr>";
							}
							print "</table>";
						}
					}
				}
				else print "<div id=\"no_tab".$index."\" colspan=\"2\" class=\"shade1\">".$gm_lang["no_tab5"]."</div>\n";
				print "</div>";
			}
			if ($tab == "sources") {
				print "<div id=\"sources\" class=\"tab_page\" style=\"display:none;\" >";
				
				print "\n<table class=\"facts_table\">";
				if ($this->$object_name->sourfacts_count > 0) {
					foreach($this->$object_name->facts as $key => $value) {
						$fact = trim($value[0]);
						if (!empty($fact) && $fact == "SOUR") {
							$styleadd = $value[3];
							print_main_sources($value[1], 1, $this->xref, $value[2], $value[3], $this->$object_name->canedit);
						}
					}
				}
				else print "<div id=\"no_tab".$index."\" colspan=\"2\" class=\"shade1\"></div>\n";
					//-- new fact link
				if ($this->view != "preview" && $this->$object_name->canedit && !$this->$object_name->isdeleted) {
					print "<tr><td class=\"width20 shade2\">";
					print_help_link("add_source_help", "qm");
					print $gm_lang["add_source_lbl"]."</td><td class=\"shade1\">";
					print "<a href=\"javascript: ".$gm_lang["add_source"]."\" onclick=\"add_new_record('".$this->$object_name->xref."','SOUR', 'add_source'); return false;\">".$gm_lang["add_source"]."</a>";
					print "<br /></td></tr>";
				}
				print "</table>\n\n<br />";
				print "</div>";
			}

			if ($tab == "media") {
				print "<div id=\"media\" class=\"tab_page\" style=\"display:none;\" >";
				
				print "\n<table class=\"facts_table\">";
				if ($this->$object_name->mediafacts_count > 0) {
					foreach($this->$object_name->facts as $key => $value) {
						$fact = trim($value[0]);
						if (!empty($fact) && $fact == "OBJE") {
							$styleadd = $value[3];
							print_main_media($value[1], $this->xref, 0, $value[2], ($this->$object_name->show_changes), $value[3]);
						}
					}
				}
				else print "<div id=\"no_tab".$index."\" colspan=\"2\" class=\"shade1\"></div>\n";
				if (!$this->isPrintPreview() && $this->$object_name->canedit && !$this->$object_name->isdeleted) {
					print "<tr><td class=\"shade2 width20\">";
					print_help_link("add_media_help", "qm");
					print $gm_lang["add_media_lbl"]."</td><td class=\"shade1\">";
					print "<a href=\"javascript: ".$gm_lang["add_media_lbl"]."\" onclick=\"add_new_record('".$this->$object_name->xref."','OBJE', 'add_media'); return false;\">".$gm_lang["add_media"]."</a>";
					print "</td></tr>";
				}
				print "</table>\n\n<br />";
				print "</div>";
			}
			if ($tab == "notes") {
				print "<div id=\"notes\" class=\"tab_page\" style=\"display:none;\" >";
				
				print "\n<table class=\"facts_table\">";
				if ($this->$object_name->notefacts_count > 0) {
					foreach($this->$object_name->facts as $key => $value) {
						$fact = trim($value[0]);
						if (!empty($fact) && $fact == "NOTE") {
							$styleadd = $value[3];
							print_main_notes($value[1], 1, $this->xref, $value[2], $value[3]);
						}
					}
				}
				else print "<div id=\"no_tab".$index."\" colspan=\"2\" class=\"shade1\"></div>\n";
				if (!$this->isPrintPreview() && $this->$object_name->canedit && !$this->$object_name->isdeleted) { 
					print "<tr><td class=\"shade2 width20\">";
					print_help_link("add_note_help", "qm");
					print $gm_lang["add_note_lbl"]."</td><td class=\"shade1\">";
					print "<a href=\"javascript: ".$gm_lang["add_note"]."\" onclick=\"add_new_record('".$this->$object_name->xref."','NOTE', 'add_note'); return false;\">".$gm_lang["add_note"]."</a>";
					print "</td></tr>";
					print "<tr><td class=\"shade2 width20\">";
					print_help_link("add_general_note_help", "qm");
					print $gm_lang["add_gnote_lbl"]."</td><td class=\"shade1\"><a href=\"javascript: ".$gm_lang["add_gnote"]."\" onclick=\"add_new_record('".$this->$object_name->xref."','GNOTE', 'add_gnote'); return false;\">".$gm_lang["add_gnote"]."</a>";
					print "</td></tr>";
				}
				print "</table>\n\n<br />";
				print "</div>";
			}
			if ($tab == "actions_person") {
				print "<div id=\"actions_person\" class=\"tab_page\" style=\"display:none;\" >";
				if ($Users->ShowActionLog()) {
					print "<br />";
					print "<form name=\"actionform\" method=\"post\" action=\"individual.php\">";
					print "<input name=\"pid\" type=\"hidden\" value=\"".$this->$object_name->xref."\" />";
					print "<table class=\"facts_table\">";
					if (count($this->$object_name->actionlist) > 0) {
						foreach ($this->$object_name->actionlist as $key => $action) {
							$action->PrintThis();
						}
					}
					else print "<tr><td id=\"no_tab".$index."\" colspan=\"2\" class=\"shade1\"></td></tr>\n";
					//-- New action Link
					if (!$this->isPrintPreview() && $this->$object_name->canedit && !$this->$object_name->isdeleted) { 
						$Actions->PrintAddLink();
					}
				}
				print "</table></form>";
				print "</div>";
			}
		}	
		print "<script type=\"text/javascript\">\n<!--\n";
		if ($this->isPrintPreview()) print "tabswitch(0)";
		else if (isset($_SESSION[$this->tabtype][JoinKey($this->$object_name->xref, $GEDCOMID)])) print "tabswitch(".$_SESSION[$this->tabtype][JoinKey($this->$object_name->xref, $GEDCOMID)].")";
		else print "tabswitch(1)";
		print "\n//-->\n</script>\n";
	}
	
	public function PrintDetailJS() {
		
		$object_name = $this->object_name;
		
		?>
		<script language="JavaScript" type="text/javascript">
		<!--
			function show_gedcom_record() {
				var recwin = window.open("gedrecord.php?pid=<?php print $this->$object_name->xref; ?>", "", "top=0,left=0,width=300,height=400,scrollbars=1,scrollable=1,resizable=1");
			}
			function showchanges() {
				sndReq('show_changes', 'set_show_changes', 'set_show_changes', '<?php if ($this->show_changes) print false; else print true; ?>');
				window.location.reload();
			}
			
			function reload() {
				window.location.reload();
			}
		
		//-->
		</script>
		<?php
	}
	
	public function CheckNoResult($message) {
		
		$object_name = $this->object_name;
		if ($this->$object_name->isempty && !$this->$object_name->ischanged) {
			$this->PrintDetailJS();
			print "&nbsp;&nbsp;&nbsp;<span class=\"error\"><i>".$message."</i></span>";
			print "<br /><br /><br /><br /><br /><br />\n";
			print_footer();
			exit;
		}
	}
	
	public function CheckPrivate() {
		global $CONTACT_EMAIL;
		
		$object_name = $this->object_name;
		if (!$this->$object_name->disp && !($this->$object_name->datatype == "INDI" && $this->$object_name->disp_name)) {
			$this->PrintDetailJS();
			print_privacy_error($CONTACT_EMAIL);
			print_footer();
			exit;
		}
	}
	
	public function CheckRawEdited() {
		global $gm_lang;
		
		$object_name = $this->object_name;
		if ($this->$object_name->israwedited) print $gm_lang["is_rawedited"];
	}
}
?>