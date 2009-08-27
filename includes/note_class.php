<?php
/**
 * Class file for a Note (NOTE) object
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
 * @subpackage DataModel
 * @version $Id$
 */
require_once 'includes/gedcomrecord.php';
class Note extends GedcomRecord {

	var $indilist = null;
	var $famlist = null;
	var $medialist = null;
	var $sourcelist = null;
	var $repolist = null;
	
	var $text = null;
	var $newtext = null;
	var $title = null;
	var $newtitle = null;
	var $notefacts = null;
	
	var $showchanges = false;
	var $canedit = null;
	var $disp = null;
	var $new = false;
	var $deleted = false;
	var $changed = false;
	var $textchanged = false;
	var $newgedrec = null;
	var $gedcomid = null;
	
	/**
	 * Constructor for note object
	 * @param string $gedrec	the raw note gedcom record
	 */
	function Note($gedrec, $new=false) {
		global $gm_username, $GEDCOM, $Users;

		parent::GedcomRecord($gedrec);
		$this->changed = false;
		$this->disp = displayDetailsByID($this->xref, "NOTE", 1, true);
		if ($this->disp) {
			if ((!isset($show_changes) || $show_changes != "no") && $Users->UserCanEdit($gm_username)) $this->showchanges = true;
			if ($this->showchanges) $rec = $this->getchangedGedcomRecord();
			if (empty($rec)) $this->deleted = true;
			else if (($rec != $this->gedrec) && !$new) {
				$this->changed = true;
				$this->newgedrec = $rec;
			}
			else if (!empty($rec) && $new) {
				$this->new = true;
				$this->newgedrec = $rec;
			}
			if ($Users->UserCanEdit($gm_username)) $this->canedit = true;
			else $this->canedit = false;

			// If the record is changed, check WHAT is changed.
			if ($this->changed && ($this->GetNoteText() != $this->GetNoteText(true))) $this->textchanged = true;
			
		}
	}
	
	/**
	 * Check if privacy options allow this record to be displayed
	 * @return boolean
	 */
	function canDisplayDetails() {
		return $this->disp;
	}
	
	/**
	 * get the title of this note record
	 * @return string
	 */
	function getTitle($l=40, $changed=false) {
		global $gm_lang;

		if ($changed && !is_null($this->newgedrec)) {
			if (is_null($this->newtitle)) {
				$this->newtitle = $this->GetNoteText($changed); // Get the text
				$this->newtitle = trim($this->newtitle); // we must show some starting characters
				$this->newtitle = preg_replace("~[\r|\n]+~", " ", $this->newtitle);
				$this->newtitle = preg_replace("~<br />~", " ", $this->newtitle);
				if (empty($this->newtitle)) $this->newtitle = $gm_lang["unknown"];
			}
			if ($l >= strlen($this->newtitle)) return $this->newtitle;
			else return substr($this->newtitle, 0, $l).".....";
		}
		
		if (is_null($this->title)) {
			$this->title = $this->GetNoteText($changed); // Get the text
			$this->title = trim($this->title); // we must show some starting characters
			$this->title = preg_replace("~[\r|\n]+~", " ", $this->title);
			$this->title = preg_replace("~<br />~", " ", $this->title);
			if (empty($this->title)) $this->title = $gm_lang["unknown"];
		}
		if ($l >= strlen($this->title)) return $this->title;
		else return substr($this->title, 0, $l).".....";
	}
	
	/**
	 * get note facts array
	 * @return array
	 */
	function getNoteFacts() {
		$this->parseFacts();
		return $this->notefacts;
	}
	
	/**
	 * Set the gedcom id in which the note exists
	 */
	function SetGedcomId($id) {
		$this->gedcomid = $id;
	}
	
	/**
	 * Parse the facts from the individual record
	 */
	function parseFacts() {
		if (!is_null($this->notefacts)) return;
		$this->notefacts = array();
		$this->allnotesubs = GetAllSubrecords($this->gedrec, "CONC, CONT", true, false, false);
		foreach ($this->allnotesubs as $key => $subrecord) {
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
			if (!empty($fact) ) {
				$this->notefacts[] = array($fact, $subrecord, $count[$fact]);
			}
		}
		$newrecs = RetrieveNewFacts($this->xref);
		foreach($newrecs as $key=> $newrec) {
			$ft = preg_match("/1\s(\w+)(.*)/", $newrec, $match);
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
			if (!empty($fact) && !in_array($fact, array("CONC", "CONT"))) {
				$this->notefacts[] = array($fact, $newrec, $count[$fact], "new");
			}
		}
	}

	/**
	 * get the text of the note
	 */
	function getNoteText($changed=false) {
		global $WORD_WRAPPED_NOTES;

		if ($changed && !is_null($this->newgedrec)) {
			if (!is_null($this->newtext)) return $this->newtext;
			$this->newtext = "";
			$nt = preg_match("/0 @.+@ NOTE (.*)/", $this->newgedrec, $n1match);
			if ($nt>0) $this->newtext = preg_replace("/~~/", "<br />", $n1match[1]);
			$this->newtext .= GetCont(1, $this->newgedrec);
			return $this->newtext;
		}
		else {
			if (!is_null($this->text)) return $this->text;
			$this->text = "";
			$nt = preg_match("/0 @.+@ NOTE (.*)/", $this->gedrec, $n1match);
			if ($nt>0) $this->text = preg_replace("/~~/", "<br />", $n1match[1]);
			$this->text .= GetCont(1, $this->gedrec);
			return $this->text;
		}
	}		
		
	/**
	 * get the list of individuals connected to this note
	 * @return array
	 */
	function getNoteIndis() {
		global $REGEXP_DB, $GEDCOMID;
		if (!is_null($this->indilist)) return $this->indilist;
		$this->indilist = array();
		$links = GetNoteLinks($this->xref, "INDI", true);
		if (count($links) > 0) {
			$linkslist = implode("[".$GEDCOMID."]','", $links);
			$linkslist .= "[".$GEDCOMID."]'";
			$linkslist = "'".$linkslist;
			$this->indilist = GetIndiList("", $linkslist, true);
			uasort($this->indilist, "ItemSort");
		}
		return $this->indilist;
	}
	
	/**
	 * get the list of families connected to this note
	 * @return array
	 */
	function getNoteFams() {
		global $REGEXP_DB, $GEDCOMID;
		if (!is_null($this->famlist)) return $this->famlist;
		$this->famlist = array();
		$links = GetNoteLinks($this->xref, "FAM", true);
		if (count($links) > 0) {
			$linkslist = implode("[".$GEDCOMID."]','", $links);
			$linkslist .= "[".$GEDCOMID."]'";
			$linkslist = "'".$linkslist;
			$this->famlist = GetFamList("", $linkslist, true);
			uasort($this->famlist, "ItemSort");
		}
		return $this->famlist;
	}
	
	/**
	 * get the list of media connected to this note
	 * @return array
	 */
	function getNoteMedia() {
		global $TBLPREFIX, $GEDCOMID;
		
		if (!is_null($this->medialist)) return $this->medialist;
		$this->medialist = array();
		$links = GetNoteLinks($this->xref, "OBJE", true, false);
		foreach ($links as $key => $link) {
			$media = array();
			$media["gedfile"] = $GEDCOMID;
			$media["name"] = GetMediaDescriptor($link);
			$this->medialist[$link] = $media;
		}
		uasort($this->medialist, "ItemSort");
		return $this->medialist;
	}
	
	/**
	 * get the list of repositories connected to this note
	 * @return array
	 */
	function getNoteRepos() {
		global $TBLPREFIX, $GEDCOMID;
		
		if (!is_null($this->repolist)) return $this->repolist;
		$this->repolist = array();
		$links = GetNoteLinks($this->xref, "REPO", true, false);
		if (count($links) > 0) {
			$linkslist = implode("','", $links);
			$linkslist .= "'";
			$linkslist = "'".$linkslist;
			$this->repolist = GetRepoList("", $linkslist);
		}
		uasort($this->repolist, "ItemSort");
		return $this->repolist;
	}

	/**
	 * get the list of sources connected to this note
	 * @return array
	 */
	function getNoteSources() {
		global $TBLPREFIX, $GEDCOMID;
		
		if (!is_null($this->sourcelist)) return $this->sourcelist;
		$this->sourcelist = array();
		$links = GetNoteLinks($this->xref, "SOUR", true, false);
		if (count($links) > 0) {
			$linkslist = implode("','", $links);
			$linkslist .= "'";
			$linkslist = "'".$linkslist;
			$this->sourcelist = GetSourceList($linkslist);
		}
		uasort($this->sourcelist, "Sourcesort");
		return $this->sourcelist;
	}
	/**
	 * print main note row
	 *
	 * this function will print a table row for a fact table for a level 1 note in the main record
	 * @param string $factrec	the raw gedcom sub record for this note
	 * @param int $level		The start level for this note, usually 1
	 * @param string $pid		The gedcom XREF id for the level 0 record that this note is a part of
	 */
	function PrintGeneralNote($styleadd="", $mayedit=true) {
		global $gm_lang, $gm_username, $Users;
		global $factarray, $view, $show_changes;
		global $WORD_WRAPPED_NOTES, $GM_IMAGE_DIR;
		global $GM_IMAGES, $GEDCOM;

		if (!$this->disp) return false;
		
		if (($this->textchanged || $this->deleted) && $this->showchanges && !($view == "preview")) {
			$styleadd = "change_old";
			print "\n\t\t<tr><td class=\"shade2 $styleadd center\" style=\"vertical-align: middle;\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["note"]["other"]."\" width=\"50\" height=\"50\" alt=\"\" /><br />".$gm_lang["note"].":";
			print " </td>\n<td class=\"shade1 $styleadd wrap\">";
			if (showFactDetails("NOTE", $this->xref)) {
				print PrintReady($this->GetNoteText())."<br />\n";
				// See if RESN tag prevents display or edit/delete
			 	$resn_tag = preg_match("/2 RESN (.*)/", $this->gedrec, $match);
	 			if ($resn_tag > 0) $resn_value = strtolower(trim($match[1]));
				// -- Find RESN tag
				if (isset($resn_value)) {
					print_help_link("RESN_help", "qm");
					print $gm_lang[$resn_value]."\n";
				}
				print "<br />\n";
			}
			print "</td></tr>";
		}
		if (($this->textchanged || $this->new) && !$this->deleted && $this->showchanges && !($view == "preview")) $styleadd = "change_new";
		if (!$this->deleted || !$this->showchanges) {
			print "\n\t\t<tr><td class=\"shade2 $styleadd center\" style=\"vertical-align: middle;\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["note"]["other"]."\" width=\"50\" height=\"50\" alt=\"\" /><br />".$gm_lang["note"].":";
			if ($this->canedit && !FactEditRestricted($this->xref, $this->gedrec, 0) && ($styleadd!="change_old")&&($view!="preview")&& $mayedit) {
				$menu = array();
				$menu["label"] = $gm_lang["edit"];
				$menu["labelpos"] = "right";
				$menu["icon"] = "";
				$menu["link"] = "#";
				$menu["onclick"] = "return edit_record('$this->xref', 'NOTE', '1', 'edit_general_note');";
				$menu["class"] = "";
				$menu["hoverclass"] = "";
				$menu["flyout"] = "down";
				$menu["submenuclass"] = "submenu";
				$menu["items"] = array();
				$submenu = array();
				$submenu["label"] = $gm_lang["edit"];
				$submenu["labelpos"] = "right";
				$submenu["icon"] = "";
				$submenu["onclick"] = "return edit_record('$this->xref', 'NOTE', '1', 'edit_general_note');";
				$submenu["link"] = "#";
				$submenu["class"] = "submenuitem";
				$submenu["hoverclass"] = "submenuitem_hover";
				$menu["items"][] = $submenu;
				$submenu = array();
				$submenu["label"] = $gm_lang["copy"];
				$submenu["labelpos"] = "right";
				$submenu["icon"] = "";
				$submenu["onclick"] = "return copy_record('$this->xref', 'NOTE', '1', 'copy_general_note');";
				$submenu["link"] = "#";
				$submenu["class"] = "submenuitem";
				$submenu["hoverclass"] = "submenuitem_hover";
				$menu["items"][] = $submenu;
				// No delete option. A note cannot be without text!
				print " <div style=\"width:25px;\" class=\"center\">";
				print_menu($menu);
				print "</div>";
			}
			print " </td>\n<td class=\"shade1 $styleadd wrap\">";
			if (showFactDetails("NOTE", $this->xref)) {
				if ($styleadd == "change_new") print PrintReady($this->GetNoteText(true))."<br />\n";
				else print PrintReady($this->GetNoteText())."<br />\n";
				// See if RESN tag prevents display or edit/delete
			 	$resn_tag = preg_match("/2 RESN (.*)/", $this->gedrec, $match);
		 		if ($resn_tag > 0) $resn_value = strtolower(trim($match[1]));
				// -- Find RESN tag
				if (isset($resn_value)) {
					print_help_link("RESN_help", "qm");
					print $gm_lang[$resn_value]."\n";
				}
				print "<br />\n";
			}
			print "</td></tr>";
		}
	}
	
	function PrintListNote() {
		global $GEDCOM, $SHOW_ID_NUMBERS, $GEDCOM, $TEXT_DIRECTION;
		
		if (begRTLText($this->GetTitle())) print "\n\t\t\t<li class=\"rtl\" dir=\"rtl\">";
		else print "\n\t\t\t<li class=\"ltr\" dir=\"ltr\">";
		print "\n\t\t\t<a href=\"note.php?oid=".$this->xref."&amp;ged=".$GEDCOM."\" class=\"list_item\">".PrintReady($this->GetTitle(60));
		if ($SHOW_ID_NUMBERS) {
			if ($TEXT_DIRECTION=="ltr") print " &lrm;(".$this->GetXref().")&lrm;";
			else print " &rlm;(".$this->GetXref().")&rlm;";
		}
		print "</a>\n";
		print "</li>\n";
	}	
}
?>