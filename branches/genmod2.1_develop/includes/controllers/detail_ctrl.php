<?php
/**
 * Base controller for all detail pages (individual, source, note, repository, media)
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
 * @version $Id: detail_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
 
abstract class DetailController extends BaseController{
	
	public $classname = "DetailController";	// Name of this class
	
	protected $action = null;				// Action parameter in the query string
	protected $tabs = null;					// Determine which tabs to print
	private $tabtype = null;				// Type of tab
	private $object_name = null;			// Object name which is printed
	private $fact_filter = null; 			// These facts will not be printed in the fact tab (but later in the separate tabs)
	protected $default_tab = 1;				// Default tab for the tabs. Set in the child controller.
	
	public function __construct() {
		
		parent::__construct();
		
		// First determine what controller is calling this function
		// Depending on this, we define which tabs must be shown.
		// The array always starts with '0', which indicates the "all" option.
		switch (get_class($this)) {
			case "IndividualController":
				if ($this->IsPrintPreview()) {
					$this->tabs = array('0', 'relatives', 'facts', 'sources', 'media', 'notes', 'relations', 'actions_person');
				}
				else $this->tabs = array('0', 'relatives', 'facts', 'sources', 'media', 'notes', 'relations', 'actions_person', 'external_search');
				$this->tabtype = "indi";
				$this->object_name = "indi";
				$this->fact_filter = array("OBJE", "SOUR", "NOTE", "SEX", "NAME");
				break;
			case "FamilyController":
				$this->tabs = array('0', 'facts', 'sources', 'media', 'notes', 'relations', 'actions_person');
				$this->tabtype = "fam";
				$this->object_name = "family";
				$this->fact_filter = array("OBJE", "SOUR", "NOTE");
				break;
			case "SourceController":
				$this->tabs = array('0','facts','individuals_links','families_links','notes_links','media_links');
				$this->tabtype = "sour";
				$this->object_name = "source";
				$this->fact_filter = array();
				break;
			case "MediaController":
				$this->tabs = array('0','facts','individuals_links','families_links','sources_links','repositories_links');
				$this->tabtype = "obje";
				$this->object_name = "media";
				$this->fact_filter = array();
				break;
			case "NoteController":
				$this->tabs = array('0','facts','individuals_links','families_links','sources_links','media_links','repositories_links');
				$this->tabtype = "note";
				$this->object_name = "note";
				$this->fact_filter = array();
				break;
			case "RepositoryController":
				$this->tabs = array('0','facts','sources_links','actions_links');
				$this->tabtype = "repo";
				$this->object_name = "repo";
				$this->fact_filter = array();
				break;
			default:
				return false;
		}
		
	}
	
	public function __get($property) {
		switch($property) {
			case "display_other_menu":
				return $this->CanDisplayOtherMenu();
				break;
			case "action":
				return $this->action;
				break;
			default:
				return parent::__get($property);
				break;
		}
	}
	
	public function CanDisplayOtherMenu() {
		global $gm_user, $ENABLE_CLIPPINGS_CART;
		
		$object_name = $this->object_name;
		if ($gm_user->userCanViewGedlines() || ($ENABLE_CLIPPINGS_CART >= $gm_user->getUserAccessLevel()) || ($this->$object_name->disp && $this->uname != "")) return true;
		else return false;
		
	}
	
	public function PrintTabs() {
		global $gm_user;
		global $GM_IMAGES, $TEXT_DIRECTION;
		
		$object_name = $this->object_name;
		?>
		<script type="text/javascript">
		<!--
		function tabswitch(n) {
		if ('<?php echo $this->view; ?>' != 'preview') sndReq('dummy', 'remembertab', true, 'xref', '<?php print JoinKey($this->xref, GedcomConfig::$GEDCOMID); ?>' , 'tab_tab', n, 'type', '<?php print $this->tabtype; ?>');
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
					elt.className = '';
					if (document.getElementById('no_tab'+i)) { // empty ? then show it only to users who have edit rights
						if (<?php if ($gm_user->userCanEdit()) echo 'true'; else echo 'false';?>) {
							elt.style.display='block';
							elt.className='TabDoorEmpty';
						}
						else elt.style.display='none'; // empty and not editable ==> hide
					}
					else elt.style.display='block';
				}
				// current door
				for (i=0; i<tabid.length; i++) {
					if (i != n) document.getElementById('door'+i).className+=' TabDoorUnselected';
					else document.getElementById('door'+i).className+=' TabDoorSelected';
				}
				return false;
			}
		}
		//-->
		</script>
		<?php
		if (!$this->IsPrintPreview()) {
			// Print message is any changes to links are present
			if ($this->show_changes && $this->HasUnapprovedLinks()) print "<br />".GM_LANG_unapproved_link."<br />";
			print "<div id=\"TabDoor\">";
			print "<dl>";
			foreach ($this->tabs as $index => $tab) {
				if ($index != 0) {
					print "<dd id=\"door".$index."\"><a href=\"javascript:;\" onclick=\"tabswitch(".$index.")\" ";
					if ($tab == "facts") print ($this->tabtype == "indi" ? "accesskey=\"".GM_LANG_accesskey_individual_details."\"" : "").">".GM_LANG_facts."</a></dd>\n";
					if ($tab == "individuals_links") print ">".GM_LANG_indi_linking." (".$this->$object_name->indi_count.")</a></dd>\n";
					if ($tab == "families_links") print ">".GM_LANG_fam_linking." (".$this->$object_name->fam_count.")</a></dd>\n";
					if ($tab == "notes_links") print ">".GM_LANG_note_linking." (".$this->$object_name->note_count.")</a></dd>\n";
					if ($tab == "media_links") print ">".GM_LANG_mm_linking." (".$this->$object_name->media_count.")</a></dd>\n";
					if ($tab == "sources_links") print ">".GM_LANG_sour_linking." (".$this->$object_name->sour_count.")</a></dd>\n";
					if ($tab == "repositories_links") print ">".GM_LANG_repo_linking." (".$this->$object_name->repo_count.")</a></dd>\n";
					if ($tab == "actions_links") print ">".GM_LANG_action_linking." (".$this->$object_name->action_count.")</a></dd>\n";
					if ($tab == "relatives") print ($this->tabtype == "indi" || $this->tabtype == "fam" ? "accesskey=\"".GM_LANG_accesskey_individual_relatives."\"" : "").">".GM_LANG_relatives."</a></dd>\n";
					if ($tab == "sources") print ($this->tabtype == "indi" || $this->tabtype == "fam" ? "accesskey=\"".GM_LANG_accesskey_individual_sources."\"" : "").">".GM_LANG_ssourcess."</a></dd>\n";
					if ($tab == "media") print ($this->tabtype == "indi" || $this->tabtype == "fam" ? "accesskey=\"".GM_LANG_accesskey_individual_media."\"" : "").">".GM_LANG_media."</a></dd>\n";
					if ($tab == "notes") print ($this->tabtype == "indi" || $this->tabtype == "fam" ? "accesskey=\"".GM_LANG_accesskey_individual_notes."\"" : "").">".GM_LANG_notes."</a></dd>\n";
					if ($tab == "relations") print ($this->tabtype == "indi" || $this->tabtype == "fam" ? "accesskey=\"".GM_LANG_accesskey_individual_relations."\"" : "").">".GM_LANG_relations."</a></dd>\n";
					if ($tab == "actions_person") print ($this->tabtype == "indi" || $this->tabtype == "fam" ? "accesskey=\"".GM_LANG_accesskey_individual_research_log."\"" : "").">".GM_LANG_research_log."</a></dd>\n";
					if ($tab == "external_search") print ">".GM_LANG_external_search."</a></dd>\n";
				}
			}
			print "<dd id=\"door0\"><a href=\"javascript:;\" onclick=\"tabswitch(0)\" >".GM_LANG_all."</a></dd>\n";
			print "</dl>\n";
			print "</div><div id=\"dummy\" class=\"ClearBoth\"></div>\n";
		}
		foreach ($this->tabs as $index => $tab) {
			if ($tab == "facts") {
				
				if ($this->tabtype == "indi" && count($this->$object_name->facts) > 0) {
					$this->PrintToggleJS1();
					global $n_chil, $n_gchi;
					$n_chil = 1; // counter for children on facts page
					$n_gchi = 1;
				}
				
				// Facts
				print "<!-- Facts tab //-->";
				print "<div id=\"facts\" class=\"TabPage\" style=\"display:none;\" >";
					
				print "\n<table class=\"FactsTable\">";
				
				if ($this->tabtype == "indi" && count($this->$object_name->facts) > 0) {
					print '<tr id="row_top"><td></td><td class="FactRelaSwitch FactRela">';
					print '<a href="#" onclick="togglerow(\'row_rela\'); return false;">';
					print '<img style="display:none;" id="rela_plus" src="'.GM_IMAGE_DIR.'/'.$GM_IMAGES["plus"]["other"].'" border="0" width="11" height="11" alt="'.GM_LANG_show_details.'" title="'.GM_LANG_show_details.'" />';
					print '<img id="rela_minus" src="'.GM_IMAGE_DIR.'/'.$GM_IMAGES["minus"]["other"].'" border="0" width="11" height="11" alt="'.GM_LANG_hide_details.'" title="'.GM_LANG_hide_details.'" />';
					print ' '.GM_LANG_relatives_events;
					print '</a></td></tr>';
				}
				
				if ($this->tabtype == "note") $this->PrintGeneralNote();
				foreach($this->$object_name->facts as $key => $factobj) {
					if ($factobj->fact != "" && !in_array($factobj->fact, $this->fact_filter)) {
						if ($factobj->fact == "OBJE") {
							FactFunctions::PrintMainmedia($factobj, $this->$object_name->xref, $this->$object_name->canedit);
						}
						else if ($factobj->fact == "SOUR") {
							FactFunctions::PrintMainSources($factobj, $this->$object_name->xref, $this->$object_name->canedit);
						}
						else if ($factobj->fact == "NOTE") {
							FactFunctions::PrintMainNotes($factobj, 1, $this->$object_name->xref, $this->$object_name->canedit);
						}
						else {
							FactFunctions::PrintFact($factobj, $this->$object_name->xref);
						}
					}
				}
				
				//-- new fact link
				if ($this->view != "preview" && $this->$object_name->canedit && !$this->$object_name->isdeleted) {
					FactFunctions::PrintAddNewFact($this->$object_name->xref, $this->$object_name->facts, strtoupper($this->tabtype));
					if ($this->object_name == "media") {
						print "<tr>";
						print "<td class=\"FactLabelCell\"><span class=\"FactLabelCellText\">";
						PrintHelpLink("add_media_link_help", "qm");
						print GM_LANG_add_media_link_lbl."</span></td>";
						print "<td class=\"FactDetailCell\">";
						print "<a href=\"javascript: ".GM_LANG_add_media_lbl."\" onclick=\"add_new_record('". $this->$object_name->xref."','".$this->$object_name->datatype."', 'add_media_link', '".$this->$object_name->datatype."'); return false;\">".GM_LANG_add_media_link."</a>";
						print "</td></tr>";
					}
				}
				print "</table>\n\n";
				print "</div>";
				if ($this->IsPrintPreview()) { 
					print "<br /><span class=\"FactDetailLabel\">";
					if ($this->tabtype == "sour") print GM_LANG_other_records;
					else if ($this->tabtype == "obje") print GM_LANG_other_mmrecords;
					elseif ($this->tabtype != "indi" && $this->tabtype != "fam") print constant("GM_LANG_other_".$this->tabtype."_records");
					print "</span>";
				}
				if ($this->tabtype == "indi" && count($this->$object_name->facts) > 0) $this->PrintToggleJS2();
			}
			if ($tab == "individuals_links") {
				// -- array of individuals
				print "<!-- Indilinks tab //-->";
				print "<div id=\"individuals_links\" class=\"TabPage\" style=\"display:none;\" >";
				
				if ($this->$object_name->indi_count>0) {
					print "\n\t<table class=\"DetailListTable $TEXT_DIRECTION\">\n\t\t<tr><td class=\"DetailListHeader\"";
					if($this->$object_name->indi_count>12)	print " colspan=\"2\"";
					print "><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]."\" border=\"0\" title=\"".GM_LANG_individuals."\" alt=\"".GM_LANG_individuals."\" />&nbsp;&nbsp;";
					print GM_LANG_individuals;
					print "</td></tr><tr><td class=\"DetaillistContent\"><ul>";
					$i=1;
					// -- print the array
					foreach ($this->$object_name->indilist as $key => $indi) {
						$addname = "";
						if (NameFunctions::HasChinese($indi->name_array[0][0]) || NameFunctions::HasCyrillic($indi->name_array[0][0])) $addname = " (".$indi->sortable_addname.")";
						$indi->PrintListPerson();
						if ($i==ceil($this->$object_name->indi_count/2) && $this->$object_name->indi_count>12) print "</ul></td><td class=\"DetaillistContent\"><ul>\n";
						$i++;
					}
				
					print "\n\t\t</ul></td>\n\t\t";
				
					print "</tr>";
						if ($this->$object_name->indi_count>0) { 
							print "<tr><td>";
							print GM_LANG_total_indis." ".$this->$object_name->indi_count;
							if ($this->$object_name->indi_hide>0) print "&nbsp;--&nbsp;".GM_LANG_hidden." ".$this->$object_name->indi_hide;
							print "</td></tr>";
						}
					print "</table><br />";
				}
				else print "<div id=\"no_tab".$index."\"></div>";
				print "</div>";
			}
			if ($tab == "families_links") {
				// -- array of families
				print "<!-- Famlinks tab //-->";
				print "<div id=\"families_links\" class=\"TabPage\" style=\"display:none;\" >";
				
				if ($this->$object_name->fam_count>0) {
					print "\n\t<table class=\"DetailListTable  $TEXT_DIRECTION\">\n\t\t<tr><td class=\"DetailListHeader\"";
					if($this->$object_name->fam_count>12)	print " colspan=\"2\"";
					print "><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["sfamily"]["small"]."\" border=\"0\" title=\"".GM_LANG_families."\" alt=\"".GM_LANG_families."\" />&nbsp;&nbsp;";
					print GM_LANG_families;
					print "</td></tr><tr><td class=\"DetaillistContent\"><ul>";
					$i=1;
					foreach ($this->$object_name->famlist as $key => $family) {
						$family->PrintListFamily();
						if ($i==ceil($this->$object_name->fam_count/2) && $this->$object_name->fam_count>12) print "</ul></td><td class=\"DetaillistContent\"><ul>\n";
						$i++;
					}
					print "\n\t\t</ul></td>\n\t\t";
				
					print "</tr>";
					if ($this->$object_name->fam_count>0) { 
						print "<tr><td>";
						print GM_LANG_total_fams." ".$this->$object_name->fam_count;
						if ($this->$object_name->fam_hide>0) print "&nbsp;--&nbsp;".GM_LANG_hidden." ".$this->$object_name->fam_hide;
						print "</td></tr>";
					}
					print "</table><br />";
				}
				else print "<div id=\"no_tab".$index."\"></div>";
				print "</div>";
			}
			if ($tab == "notes_links") {
				// array of notes
				print "<!-- Notelinks tab //-->";
				print "<div id=\"notes_links\" class=\"TabPage\" style=\"display:none;\" >";
				
				if ($this->$object_name->note_count>0) {
					print "\n\t<table class=\"DetailListTable $TEXT_DIRECTION\">\n\t\t<tr><td class=\"DetailListHeader\"";
					if($this->$object_name->note_count > 12) print " colspan=\"2\"";
					print "><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["note"]["other"]."\" border=\"0\" title=\"".GM_LANG_notes."\" alt=\"".GM_LANG_notes."\" />&nbsp;&nbsp;";
					print GM_LANG_titles_found;
					print "</td></tr><tr><td class=\"DetaillistContent\"><ul>";
					$i=1;
					foreach ($this->$object_name->notelist as $key => $note) {
						$note->PrintListNote();
						if ($i==ceil($this->$object_name->note_count/2) && $this->$object_name->note_count>12) print "</ul></td><td class=\"DetaillistContent\"><ul>\n";
						$i++;
					}
					print "\n\t\t</ul></td>\n\t\t";
				 
					print "</tr>";
					if ($this->$object_name->note_count>0) { 
						print "<tr><td>";
						print GM_LANG_total_notes." ".$this->$object_name->note_count;
						if ($this->$object_name->note_hide>0) print "&nbsp;--&nbsp;".GM_LANG_hidden." ".$this->$object_name->note_hide;
						print "</td></tr>";
					}
					print "</table><br />";
				}
				else print "<div id=\"no_tab".$index."\"></div>";
				print "</div>";

			}
			if ($tab == "sources_links") {
				// -- array of sources
				print "<!-- Sourcelinks tab //-->";
				print "<div id=\"sources_links\" class=\"TabPage\" style=\"display:none;\" >";
				
				if ($this->$object_name->sour_count>0 || $this->$object_name->sour_hide>0) {
					print "\n\t<table class=\"DetailListTable  $TEXT_DIRECTION\">\n\t\t<tr><td class=\"DetailListHeader\"";
					if($this->$object_name->sour_count>12)	print " colspan=\"2\"";
					print "><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["source"]["small"]."\" border=\"0\" title=\"".GM_LANG_sources."\" alt=\"".GM_LANG_sources."\" />&nbsp;&nbsp;";
					print GM_LANG_sources;
					print "</td></tr><tr><td class=\"DetaillistContent\"><ul>";
					$i=1;
					// -- print the array
					foreach ($this->$object_name->sourcelist as $key => $object) {
						$object->PrintListSource();
						if ($i==ceil($this->$object_name->sour_count/2) && $this->$object_name->sour_count>12) print "</ul></td><td class=\"DetaillistContent\"><ul>\n";
						$i++;
					}
				
					print "\n\t\t</ul></td>\n\t\t";
				
					print "</tr>";
						if ($this->$object_name->sour_count>0) { 
							print "<tr><td>";
							print GM_LANG_total_sources." ".$this->$object_name->sour_count;
							if ($this->$object_name->sour_hide>0) print "&nbsp;--&nbsp;".GM_LANG_hidden." ".$this->$object_name->sour_hide;
							print "</td></tr>";
						}
					print "</table><br />";
				}
				else print "<div id=\"no_tab".$index."\"></div>";
				print "</div>";
			}
			if ($tab == "media_links") {
				// -- array of media
				print "<!-- Medialinks tab //-->";
				print "<div id=\"media_links\" class=\"TabPage\" style=\"display:none;\" >";
				
				if ($this->$object_name->media_count > 0 || $this->$object_name->media_hide > 0) {
					print "\n\t<table class=\"DetailListTable  $TEXT_DIRECTION\">\n\t\t<tr><td class=\"DetailListHeader\"";
					if($this->$object_name->media_count>12)	print " colspan=\"2\"";
					print "><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["media"]["small"]."\" border=\"0\" title=\"".GM_LANG_media."\" alt=\"".GM_LANG_media."\" />&nbsp;&nbsp;";
					print GM_LANG_media;
					print "</td></tr><tr><td class=\"DetaillistContent\"><ul>";
					$i=1;
					foreach ($this->$object_name->medialist as $key => $object) {
						$object->PrintListMedia();
						if ($i==ceil($this->$object_name->media_count/2) && $this->$object_name->media_count>12) print "</ul></td><td class=\"DetaillistContent\"><ul>\n";
						$i++;
					}
					print "\n\t\t</ul></td>\n\t\t";
				
					print "</tr>";
					if ($this->$object_name->media_count>0) { 
						print "<tr><td>";
						print GM_LANG_total_media." ".$this->$object_name->media_count;
						if ($this->$object_name->media_hide > 0) print "&nbsp;--&nbsp;".GM_LANG_hidden." ".$this->$object_name->media_hide;
						print "</td></tr>";
					}
					print "</table><br />";
				}
				else print "<div id=\"no_tab".$index."\"></div>";
				print "</div>";
			}
			if ($tab == "repositories_links") {
				// -- array of repositories
				print "<!-- Repolinks tab //-->";
				print "<div id=\"repositories_links\" class=\"TabPage\" style=\"display:none;\" >";
				
				if ($this->$object_name->repo_count > 0 || $this->$object_name->repo_hide > 0) {
					print "\n\t<table class=\"DetailListTable  $TEXT_DIRECTION\">\n\t\t<tr><td class=\"DetailListHeader\"";
					if($this->$object_name->repo_count>12)	print " colspan=\"2\"";
					print "><img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["repository"]["small"]."\" border=\"0\" title=\"".GM_LANG_repos."\" alt=\"".GM_LANG_repos."\" />&nbsp;&nbsp;";
					print GM_LANG_repos;
					print "</td></tr><tr><td class=\"DetaillistContent\"><ul>";
					$i=1;
					if ($this->$object_name->repo_count>0){
						$i=1;
						// -- print the array
						foreach ($this->$object_name->repolist as $key => $object) {
							$object->PrintListRepository();
							if ($i==ceil($this->$object_name->repo_count/2) && $this->$object_name->repo_count>12) print "</ul></td><td class=\"DetaillistContent\"><ul>\n";
							$i++;
						}
						print "\n\t\t</ul></td>\n\t\t";
				 	
						print "</tr><tr><td>".GM_LANG_total_repositories." ".$this->$object_name->repo_count;
						if ($this->$object_name->repo_hide > 0) print "  --  ".GM_LANG_hidden." ".$this->$object_name->repo_hide;
					}
					print "</table><br />";
				}
				else print "<div id=\"no_tab".$index."\"></div>";
				print "</div>";
			}
			if ($tab == "actions_links") {
				print "<!-- Actionlinks tab //-->";
				print "<div id=\"actions_links\" class=\"TabPage\" style=\"display:none;\" >";
				if ($this->$object_name->action_count>0 || $this->$object_name->action_hide>0) {
					
					// Start of todo list
					print "\n\t<table class=\"DetailListTable $TEXT_DIRECTION\">";
					print "<tr><td colspan=\"3\" class=\"DetailListHeader\">".GM_LANG_actionlist."</td></tr>";
					print "<tr><td class=\"DetailListColumnHeader\">".GM_LANG_todo."</td><td class=\"DetailListColumnHeader\">".GM_LANG_for."</td><td class=\"DetailListColumnHeader\">".GM_LANG_status."</td></tr>";
					foreach ($this->$object_name->actionlist as $key => $item) {
						print "<tr>";
						print "<td class=\"DetaillistContent\">".nl2br(stripslashes($item->text))."</td>";
						print "<td class=\"DetaillistContent\">";
						print "<a href=\"individual.php?pid=".$item->pid."&amp;gedid=".$item->gedcomid."\">".$item->piddesc."</a>";
						print "</td>";
						print "<td class=\"DetaillistContent\">".constant("GM_LANG_action".$item->status)."</td>";
						print "</tr>";
					}
					print "<tr><td>".GM_LANG_total_actions." ".$this->$object_name->action_count;
					if ($this->$object_name->action_hide>0) print "  --  ".GM_LANG_hidden." ".$this->$object_name->action_hide;
					print "</table>";
				}
				else print "<div id=\"no_tab".$index."\"></div>";
				print "</div>";
			}
			if ($tab == "relatives") {
				print "<!-- Relalinks tab //-->";
				print "<div id=\"relatives\" class=\"TabPage IndiRelaTabPage\" style=\"display:none;\" >";
				if ($this->$object_name->close_relatives) {
					// Print the access key
//					print "<a href=\"javascript:".GM_LANG_relatives."\" onclick=\"tabswitch(".array_search("relatives", $this->tabs)."); return false;\" title=\"".GM_LANG_relatives."\" accesskey=\"".GM_LANG_accesskey_individual_relatives."\"></a>";
//					print "<a href=\"javascript:tabswitch(".array_search("relatives", $this->tabs)."); return false;\" accesskey=\"".GM_LANG_accesskey_individual_relatives."\"></a>";
					
					$show_full = true;
					$prtcount = 0;
					// NOTE: parent families
					if (is_array($this->$object_name->childfamilies)) {
						foreach ($this->$object_name->childfamilies as $famid => $family) {
							// Family header
							print "<div class=\"IndiSubHeader\">";
							print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["cfamily"]["small"]."\" border=\"0\" class=\"Icon\" alt=\"\" />";
							print "<span class=\"IndiSubHeaderLabel\">".$family->label."</span>";
							if (!$this->view) {
						 		print "<span class=\"IndiSubHeaderAssoLink\"> - <a href=\"family.php?famid=".$family->xref."&amp;gedid=".$family->gedcomid."\">[".GM_LANG_view_family.$family->addxref."]</a>";
					 			if ($family->husb_id == "" && $this->$object_name->canedit) {
					 				print " - ";
						 			PrintHelpLink("edit_add_parent_help", "qm");
									print "<a href=\"javascript ".GM_LANG_add_father."\" onclick=\"return addnewparentfamily('', 'HUSB', '".$family->xref."', 'add_father');\">".GM_LANG_add_father."</a>";
								}
					 			if ($family->wife_id == "" && $this->$object_name->canedit) { 
					 				print " - ";
					 				PrintHelpLink("edit_add_parent_help", "qm");
									print "<a href=\"javascript ".GM_LANG_add_mother."\" onclick=\"return addnewparentfamily('', 'WIFE', '".$family->xref."', 'add_mother');\">".GM_LANG_add_mother."</a>";
								}
								print "</span>";
							}
							print "</div>";
							
							// Husband and wife
							print "<table class=\"FactsTable\">";
							$prtcount = $this->PrintIndiParents($family, $prtcount);
							
							// Children
							$prtcount = $this->PrintIndiChildren($family, $prtcount);
							print "</table>";
						}
						// NOTE: Half-siblings father
						foreach ($this->$object_name->childfamilies as $id => $fam) {
							if ($fam->husb_id != "") {
								foreach ($fam->husb->spousefamilies as $famid => $family) {
									if ($fam->xref != $family->xref && $family->label != "") {
										print "<div class=\"IndiSubHeader\">";
										print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["cfamily"]["small"]."\" border=\"0\" class=\"Icon\" alt=\"\" />";
										print "<span class=\"IndiSubHeaderLabel\">".$family->label."</span>";
										if (!$this->view) print "<span class=\"IndiSubHeaderAssoLink\"> - <a href=\"family.php?famid=".$family->xref."&amp;gedid=".$family->gedcomid."\">[".GM_LANG_view_family.$family->addxref."]</a></span>";
										print "</div>";
										
										print "<table class=\"FactsTable\">";
										$prtcount = $this->PrintIndiParents($family, $prtcount, "husb");
										$prtcount = $this->PrintIndiChildren($family, $prtcount);
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
										print "<div class=\"IndiSubHeader\">";
										print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["cfamily"]["small"]."\" border=\"0\" class=\"Icon\" alt=\"\" />";
										print "<span class=\"IndiSubHeaderLabel\">".$family->label."</span>";
										if (!$this->view) print "<span class=\"IndiSubHeaderAssoLink\"> - <a href=\"family.php?famid=".$family->xref."&amp;gedid=".$family->gedcomid."\">[".GM_LANG_view_family.$family->addxref."]</a></span>";
										print "</div>";
										
										print "<table class=\"FactsTable\">";
										$prtcount = $this->PrintIndiParents($family, $prtcount, "wife");
										$prtcount = $this->PrintIndiChildren($family, $prtcount);
										print "</table>";
									}
								}
							}
						}
					}
					// NOTE: spouses and children
					if (is_array($this->$object_name->spousefamilies)) {
						foreach ($this->$object_name->spousefamilies as $famid => $family) {
							print "<div class=\"IndiSubHeader\">";
							print "<img src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["cfamily"]["small"]."\" border=\"0\" class=\"Icon\" alt=\"\" />";
							print "<span class=\"IndiSubHeaderLabel\">".$family->label."</span>";
							if (!$this->view) print "<span class=\"IndiSubHeaderAssoLink\"> - <a href=\"family.php?famid=".$family->xref."&amp;gedid=".$family->gedcomid."\">[".GM_LANG_view_family.$family->addxref."]</a></span>";
							print "</div>";
							
							print "<table class=\"FactsTable\">";
							$prtcount = $this->PrintIndiParents($family, $prtcount);
							$prtcount = $this->PrintIndiChildren($family, $prtcount);
							print "</table>";
						}
					}
				}
				else print "<div id=\"no_tab".$index."\">".GM_LANG_no_tab5."</div>\n";
				print "</div>";
			}
			if ($tab == "sources") {
				print "<!-- Sourcelinks tab //-->";
				print "<div id=\"sources\" class=\"TabPage\" style=\"display:none;\" >";
				
				if ($this->$object_name->sourfacts_count <= 0) {
					print "<div id=\"no_tab".$index."\"></div>\n";
					$table = false;
				}
				else {
					print "\n<table class=\"FactsTable\">";
					$table = true;
					foreach($this->$object_name->facts as $key => $factobj) {
						if ($factobj->fact != "" && $factobj->fact == "SOUR") {
							FactFunctions::PrintMainSources($factobj, $this->xref, $this->$object_name->canedit);
						}
					}
				}
					//-- new fact link
				if ($this->view != "preview" && $this->$object_name->canedit && !$this->$object_name->isdeleted) {
					if (!$table) {
						print "\n<table class=\"FactsTable\">"; 
						$table = true;
					}
					print "<tr><td class=\"FactLabelCell\"><span class=\"FactLabelCellText\">";
					PrintHelpLink("add_source_help", "qm");
					print GM_LANG_add_source_lbl."</span></td><td class=\"FactDetailCell\">";
					print "<a href=\"javascript: ".GM_LANG_add_source."\" onclick=\"add_new_record('".$this->$object_name->xref."','SOUR', 'add_source', '".$this->$object_name->datatype."'); return false;\">".GM_LANG_add_source."</a>";
					print "<br /></td></tr>";
				}
				if ($table) print "</table>";
				print "</div>";
			}

			if ($tab == "media") {
				print "<!-- Medialinks tab //-->";
				print "<div id=\"media\" class=\"TabPage\" style=\"display:none;\" >";
				
				if ($this->$object_name->mediafacts_count <= 0) {
					print "<div id=\"no_tab".$index."\"></div>\n";
					$table = false;
				}
				else {
					print "\n<table class=\"FactsTable\">";
					$table = true;
					foreach($this->$object_name->facts as $key => $factobj) {
						if ($factobj->fact != "" && $factobj->fact == "OBJE") {
//							$styleadd = $value[3];
							FactFunctions::PrintMainMedia($factobj, $this->xref, $this->$object_name->canedit);
						}
					}
//					print "</table>\n\n<br />";
				}
				if (!$this->isPrintPreview() && $this->$object_name->canedit && !$this->$object_name->isdeleted) {
					if (!$table) {
						print "\n<table class=\"FactsTable\">"; 
						$table = true;
					}
					print "<tr><td class=\"FactLabelCell\"><span class=\"FactLabelCellText\">";
					PrintHelpLink("add_media_help", "qm");
					print GM_LANG_add_media_lbl."</span></td><td class=\"FactDetailCell\">";
					print "<a href=\"javascript: ".GM_LANG_add_media_lbl."\" onclick=\"add_new_record('".$this->$object_name->xref."','OBJE', 'add_media', '".$this->$object_name->datatype."'); return false;\">".GM_LANG_add_media."</a>";
					print "</td></tr>";
				}
				if ($table) print "</table>";
				print "</div>";
			}
			if ($tab == "notes") {
				print "<!-- Notelinks tab //-->";
				print "<div id=\"notes\" class=\"TabPage\" style=\"display:none;\" >";
				
				if ($this->$object_name->notefacts_count <= 0) {
					print "<div id=\"no_tab".$index."\"></div>\n";
					$table = false;
				}
				else {
					print "\n<table class=\"FactsTable\">";
					$table = true;
					foreach($this->$object_name->facts as $key => $factobj) {
//						$fact = trim($value[0]);
						if ($factobj->fact != "" && $factobj->fact == "NOTE") {
//							$styleadd = $value[3];
							FactFunctions::PrintMainNotes($factobj, 1, $this->xref, $this->$object_name->canedit);
						}
					}
				}
				if (!$this->isPrintPreview() && $this->$object_name->canedit && !$this->$object_name->isdeleted) { 
					if (!$table) {
						print "\n<table class=\"FactsTable\">"; 
						$table = true;
					}
					print "<tr><td class=\"FactLabelCell\"><span class=\"FactLabelCellText\">";
					PrintHelpLink("add_note_help", "qm");
					print GM_LANG_add_note_lbl."</span></td><td class=\"FactDetailCell\">";
					print "<a href=\"javascript: ".GM_LANG_add_note."\" onclick=\"add_new_record('".$this->$object_name->xref."','NOTE', 'add_note', '".$this->$object_name->datatype."'); return false;\">".GM_LANG_add_note."</a>";
					print "</td></tr>";
					print "<tr><td class=\"FactLabelCell\"><span class=\"FactLabelCellText\">";
					PrintHelpLink("add_general_note_help", "qm");
					print GM_LANG_add_gnote_lbl."</span></td><td class=\"FactDetailCell\"><a href=\"javascript: ".GM_LANG_add_gnote."\" onclick=\"add_new_record('".$this->$object_name->xref."','GNOTE', 'add_gnote', '".$this->$object_name->datatype."'); return false;\">".GM_LANG_add_gnote."</a>";
					print "</td></tr>";
				}
				if ($table) print "</table>";
				print "</div>";
			}
			if ($tab == "actions_person") {
				print "<!-- Indiactionslinks tab //-->";
				print "<div id=\"actions_person\" class=\"TabPage\" style=\"display:none;\" >";
				if ($gm_user->ShowActionLog()) {
					print "<form name=\"actionform\" method=\"post\" action=\"individual.php\">";
					print "<input name=\"pid\" type=\"hidden\" value=\"".$this->$object_name->xref."\" />";
					print "<table class=\"FactsTable\">";
					if (count($this->$object_name->actionlist) > 0) {
						foreach ($this->$object_name->actionlist as $key => $action) {
							$action->PrintThis();
						}
					}
					else print "<tr><td id=\"no_tab".$index."\"></td></tr>\n";
					//-- New action Link
					if (!$this->isPrintPreview() && $this->$object_name->canedit && !$this->$object_name->isdeleted) { 
						ActionController::PrintAddLink($this->tabtype);
					}
					print "</table></form>";
				}
				else print "<div id=\"no_tab".$index."\"></div>\n";
				print "</div>";
			}
			if ($tab == "relations") {
				print "<!-- Relalinks tab //-->";
				print "<div id=\"relations\" class=\"TabPage\" style=\"display:none;\" >";
				$me = (count($this->$object_name->relationstome) > 0);
				$other = ($this->$object_name->datatype == "INDI" && count($this->$object_name->relationstoothers) > 0);
				if ($me || $other) {
					if ($me) {
						print "<div class=\"".($this->$object_name->datatype == "INDI" ? "RelaTabContainerIndi" : "RelaTabContainerFam")."\">";
						print "\n\t<table class=\"ListTable $TEXT_DIRECTION\">\n\t\t<tr><td class=\"RelaTabTableHeader\" colspan=\"3\">";
						print ($this->$object_name->datatype == "INDI" ? GM_LANG_relationstomeperson : GM_LANG_relationstomefamily);
						print "</td></tr>";
						print "<tr><td class=\"ListTableColumnHeader $TEXT_DIRECTION\">".GM_LANG_name."</td>";
						print "<td class=\"ListTableColumnHeader $TEXT_DIRECTION\">".GM_LANG_related_event."</td>";
						print "<td class=\"ListTableColumnHeader $TEXT_DIRECTION\">".GM_LANG_role."</td></tr>";
						foreach($this->$object_name->relationstome as $key => $assos) {
							usort($assos,"assosort");
							foreach($assos as $key2 => $asso) {
								if ($asso->disp) {
									print "<tr><td class=\"ListTableContent $TEXT_DIRECTION\">";
									$asso->assoperson->PrintListPerson(false);
									print "</td><td class=\"ListTableContent $TEXT_DIRECTION\">";
									if (defined("GM_FACT_".$asso->fact)) print constant("GM_FACT_".$asso->fact);
									else print $asso->fact;
									print "</td><td class=\"ListTableContent $TEXT_DIRECTION\">";
									if (defined("GM_LANG_".$asso->role)) print constant("GM_LANG_".$asso->role);
									else print $asso->role;
									print "</td></tr>";
								}
							}
						}
						print "</table></div>";
					}
					if ($other) {
						print "<div class=\"RelaTabContainerIndi\">";
						print "\n\t<table class=\"ListTable $TEXT_DIRECTION\">\n\t\t<tr><td colspan=\"3\" class=\"RelaTabTableHeader\">";
						print GM_LANG_relationstoothers;
						print "</td></tr>";
						print "<tr><td class=\"ListTableColumnHeader $TEXT_DIRECTION\">".GM_LANG_name."</td>";
						print "<td class=\"ListTableColumnHeader $TEXT_DIRECTION\">".GM_LANG_related_event."</td>";
						print "<td class=\"ListTableColumnHeader $TEXT_DIRECTION\">".GM_LANG_role."</td></tr>";
						foreach($this->$object_name->relationstoothers as $key => $asso) {
							if ($asso->disp) {
								print "<tr><td class=\"ListTableContent $TEXT_DIRECTION\">";
								if ($asso->associated->datatype == "INDI") $asso->associated->PrintListPerson(false);
								else $asso->associated->PrintListFamily(false);
								print "</td><td class=\"ListTableContent $TEXT_DIRECTION\">";
								if ($asso->associated->disp) {
									if (defined("GM_FACT_".$asso->fact)) print constant("GM_FACT_".$asso->fact);
									else print $asso->fact;
								}
								print "</td><td class=\"ListTableContent $TEXT_DIRECTION\">";
								if ($asso->associated->disp) {
									if (defined("GM_LANG_".$asso->role)) print constant("GM_LANG_".$asso->role);
									else print $asso->role;
								}
								print "</td></tr>";
							}
						}
						print "</table></div>";
					}
				}
				else if ($this->view != "preview") print "<div id=\"no_tab".$index."\" class=\"RelaTabTable\">".($gm_user->userCanEdit() ? GM_LANG_no_tab7 : "")."</div>\n";
				print "<br style=\" clear: both;\" /></div>";
			}
			if ($tab == "external_search") {
				print "<!-- External search tab //-->";
				print "<div id=\"external_search\" class=\"TabPage\" style=\"display:none;\" >\n";
				if ($gm_user->userCanEdit()) {
					$es_controller = new ExternalSearchController($this->$object_name);
					if ($es_controller->optioncount > 0) {
						print "<br />";
						print "<div id=\"esearchform\">\n";
						$es_controller->PrintSearchForm();
						print "</div>\n";
						print "<div id=\"esearchresults\">";
						print "</div>";
					}
					else print "<div id=\"no_tab".$index."\"></div>\n";
				}
				else print "<div id=\"no_tab".$index."\"></div>\n";
				print "</div>";
			}
		}	
		print "<script type=\"text/javascript\">\n<!--\n";
		if ($this->isPrintPreview()) print "tabswitch(".count($this->tabs).")";
		else if (isset($_SESSION["last_tab"][$this->tabtype][JoinKey($this->$object_name->xref, GedcomConfig::$GEDCOMID)])) print "tabswitch(".$_SESSION["last_tab"][$this->tabtype][JoinKey($this->$object_name->xref, GedcomConfig::$GEDCOMID)].")";
		else if ($object_name == "indi") print "tabswitch(".$this->default_tab.")";
		else print "tabswitch(1)";
		// Set the width of the facts table equal to the tabs
		print "\n//-->\n</script>\n";
		
	}
	
	public function PrintDetailJS() {
		
		$object_name = $this->object_name;
		
		?>
		<script language="JavaScript" type="text/javascript">
		<!--
			function show_gedcom_record() {
				var recwin = window.open("gedrecord.php?pid=<?php print $this->$object_name->xref; ?>&type=<?php print $this->$object_name->type;?>", "", "top=0,left=0,width=600,height=400,scrollbars=1,scrollable=1,resizable=1");
			}
			function showchanges() {
				sndReq('show_changes', 'set_show_changes', true, 'set_show_changes', <?php if ($this->show_changes) print "0"; else print "1"; ?>);
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
			print "&nbsp;&nbsp;&nbsp;<span Error=\"error\"><i>".$message."</i></span>";
			print "<br /><br /><br /><br /><br /><br />\n";
			PrintFooter();
			exit;
		}
	}
	
	public function CheckPrivate() {
		
		$object_name = $this->object_name;
		if ($this->$object_name->disp) return;
		else if ($this->$object_name->datatype == "INDI" && $this->$object_name->disp_name) return;
		else if ($this->$object_name->datatype == "FAM" && $this->$object_name->disp_name) return;
		$this->PrintDetailJS();
		PrintPrivacyError(GedcomConfig::$CONTACT_EMAIL);
		PrintFooter();
		exit;
	}
	
	public function CheckRawEdited() {
		
		$object_name = $this->object_name;
		if ($this->$object_name->israwedited) print GM_LANG_is_rawedited;
	}
	
	private function HasUnapprovedLinks() {
		
		if ($this->show_changes) {
			$sql = "SELECT count(ch_id) FROM ".TBLPREFIX."changes WHERE ch_file='".$this->gedcomid."' AND ch_fact NOT IN ('HUSB', 'WIFE', 'CHIL', 'FAMC', 'FAMS', 'INDI') AND ((ch_new LIKE '%@".$this->xref."@%' AND ch_new NOT LIKE '%0 @".$this->xref."@%') OR (ch_old LIKE '%@".$this->xref."@%' AND ch_old NOT LIKE '0 @".$this->xref."@%'))";
			$res = NewQuery($sql);
			$row = $res->FetchRow();
			return $row[0];
		}
		else return false;
	}
	
	private function PrintIndiParents($family, $prtcount, $suppress = "") {
		
		if ($suppress != "husb") {
			if ($family->show_changes && $family->husbold_id != "") {
				$style = " ChangeOld";
				print "<tr><td class=\"FactLabelCell".$style."\">";
				print "&nbsp;</td>"; // No relation for former wives
				print "<td class=\"".$this->getPersonStyle($family->husbold).$style."\">";
				PersonFunctions::PrintPedigreePerson($family->husbold, 2, true, $prtcount, 1, $this->view);
				$prtcount++;
				print "</td></tr>";
			}
			if ($family->husb_id != "") {
				$style = "";
				if ($this->show_changes && $family->husb_status != "") $style = " ChangeNew";
				print "<tr><td class=\"FactLabelCell".$style."\">";
				print "<span class=\"FactLabelCellText\">".$family->husb->label[$family->xref]."</span></td>";
				print "<td class=\"".$this->getPersonStyle($family->husb).$style."\">";
				PersonFunctions::PrintPedigreePerson($family->husb, 2, true, $prtcount, 2, $this->view);
				$prtcount++;
				print "</td></tr>";
			}
		}
		if ($suppress != "wife") {
			if ($family->show_changes && $family->wifeold_id != "") {
				$style = " ChangeOld";
				print "<tr><td class=\"FactLabelCell".$style."\">";
				print "&nbsp;</td>"; // No relation for former husbands
				print "<td class=\"".$this->getPersonStyle($family->wifeold).$style."\">";
				PersonFunctions::PrintPedigreePerson($family->wifeold, 2, true, $prtcount, 1, $this->view);
				$prtcount++;
				print "</td></tr>";
			}
			if ($family->wife_id != "") {
				$style = "";
				if ($this->show_changes && $family->wife_status != "") $style = " ChangeNew";
				print "<tr><td class=\"FactLabelCell".$style."\">";
				print "<span class=\"FactLabelCellText\">".$family->wife->label[$family->xref]."</span></td>";
				print "<td class=\"".$this->getPersonStyle($family->wife).$style."\">";
				PersonFunctions::PrintPedigreePerson($family->wife, 2, true, $prtcount, 2, $this->view);
				$prtcount++;
				print "</td></tr>";
			}
		}
		return $prtcount;
	}

	private function PrintIndiChildren($family, $prtcount) {
		
		foreach ($family->children as $childid => $child) {

			if (isset($child->label[$family->xref])) {
				$style = "";
				if ($child->show_changes) {
					if ($family->child_status[$childid] == "new") $style = " ChangeNew";
					elseif ($family->child_status[$childid] == "deleted") $style = " ChangeOld";
				}
				print "<tr><td class=\"FactLabelCell".$style."\">";
				print "<span class=\"FactLabelCellText\">".$child->label[$family->xref]."</span></td>";
				print "<td class=\"".$this->getPersonStyle($child).$style."\">";
				PersonFunctions::PrintPedigreePerson($child, 2 , true, $prtcount, 1, $this->view);
				$prtcount++;
				print "</td></tr>";
			}
		}
		return $prtcount;
	}
	
	protected function addFavorite() {
		
		if (empty($this->uname)) return;
		
		$object_name = $this->object_name;
		if (!$this->$object_name->isempty && !$this->$object_name->isdeleted) {	
			$favorite = new Favorite();
			$favorite->username = $this->uname;
			$favorite->gid = $this->$object_name->xref;
			$favorite->type = $this->$object_name->datatype;
			// Don't set the type (only for URL's)
			$favorite->file = GedcomConfig::$GEDCOMID;
			$favorite->SetFavorite();
		}
	}

}
?>