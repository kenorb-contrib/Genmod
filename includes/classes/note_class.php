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

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
 
class Note extends GedcomRecord {

	public $classname = "Note";
	
	private $indilist = null;
	private $indi_count = null;
	private $famlist = null;
	private $fam_count = null;
	private $medialist = null;
	private $media_count = null;
	private $sourcelist = null;
	private $sour_count = null;
	private $repolist = null;
	private $repo_count = null;
	
	private $text = null;
	private $newtext = null;
	private $title = null;
	private $newtitle = null;
	private $notefacts = null;
	
	private $canedit = null;
	private $textchanged = false;
	private $newgedrec = null;
	
	/**
	 * Constructor for note object
	 * @param string $gedrec	the raw note gedcom record
	 */
	public function __construct($id, $gedrec="", $new=false) {
		global $otherlist, $GEDCOMID;

		if (empty($gedrec)) {
			if (isset($sourcelist[$id])) $gedrec = $sourcelist[$id]["gedcom"];
			else $gedrec = FindOtherRecord($id);
		}
		if (empty($gedrec)) $gedrec = "0 @".$id."@ NOTE\r\n";
		else $otherlist[$id]["gedcom"] = $gedrec;
		
		parent::__construct($id, $gedrec);
		
		$this->gedcomid = $GEDCOMID;
		
		if ($this->disp) {
			// If the record is changed, check WHAT is changed.
			if ($this->changed && ($this->GetNoteText() != $this->GetNoteText(true))) $this->textchanged = true;
			
		}
	}
	
	public function __get($property) {
		$result = NULL;
		switch ($property) {
			case "text":
				return $this->GetNoteText();
				break;
			case "indilist":
				return $this->GetNoteIndis();
				break;
			case "indi_count":
				if (is_null($this->indi_count)) $this->GetNoteIndis();
				return $this->indi_count;
				break;
			case "famlist":
				return $this->GetNoteFams();
				break;
			case "fam_count":
				if (is_null($this->fam_count)) $this->GetNoteFams();
				return $this->fam_count;
				break;
			case "medialist":
				return $this->GetNoteMedia();
				break;
			case "media_count":
				if (is_null($this->media_count)) $this->GetNoteMedia();
				return $this->media_count;
				break;
			case "sourcelist":
				return $this->GetNoteSources();
				break;
			case "sour_count":
				if (is_null($this->sour_count)) $this->GetNoteSources();
				return $this->sour_count;
			case "repolist":
				return $this->GetNoteRepos();
				break;
			case "repo_count":
				if (is_null($this->repo_count)) $this->GetNoteRepos();
				return $this->repo_count;
			default:
				return parent::__get($property);
				break;
		}
	}
	
	/**
	 * get the title of this note record
	 * @return string
	 */
	public function getTitle($l=40, $changed=false) {
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
	 * get the text of the note
	 */
	public function getNoteText($changed=false) {
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
	private function getNoteIndis() {
		global $GEDCOMID;
		
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
		$this->indi_count=count($this->indilist);
		return $this->indilist;
	}
	
	/**
	 * get the list of families connected to this note
	 * @return array
	 */
	private function getNoteFams() {
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
		$this->fam_count=count($this->famlist);
		return $this->famlist;
	}
	
	/**
	 * get the list of media connected to this note
	 * @return array
	 */
	private function getNoteMedia() {
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
		$this->media_count=count($this->medialist);
		return $this->medialist;
	}
	
	/**
	 * get the list of repositories connected to this note
	 * @return array
	 */
	private function getNoteRepos() {
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
		$this->repo_count=count($this->repolist);
		return $this->repolist;
	}

	/**
	 * get the list of sources connected to this note
	 * @return array
	 */
	private function getNoteSources() {
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
		$this->sour_count=count($this->sourcelist);
		return $this->sourcelist;
	}
	
	public function PrintListNote($len=60) {
		global $GEDCOM, $SHOW_ID_NUMBERS, $GEDCOM, $TEXT_DIRECTION;
		
		if (!$this->disp) return false;

		if (begRTLText($this->GetTitle())) print "\n\t\t\t<li class=\"rtl\" dir=\"rtl\">";
		else print "\n\t\t\t<li class=\"ltr\" dir=\"ltr\">";
		print "\n\t\t\t<a href=\"note.php?oid=".$this->xref."&amp;ged=".$GEDCOM."\" class=\"list_item\">".PrintReady($this->GetTitle($len));
		if ($SHOW_ID_NUMBERS) {
			if ($TEXT_DIRECTION=="ltr") print " &lrm;(".$this->xref.")&lrm;";
			else print " &rlm;(".$this->xref.")&rlm;";
		}
		print "</a>\n";
		print "</li>\n";
	}	
}
?>