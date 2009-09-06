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

	// General class information
	public $classname = "Note";
	public $datatype = "NOTE";
	private static $notecache = array();	// Holder of the instances for this class
	
	// Data
	private $text = null;
	private $newtext = null;
	private $title = null;
	private $newtitle = null;
	private $textchanged = null;
	
	public static function GetInstance($xref, $gedrec="", $gedcomid="") {
		global $GEDCOMID;
		
		if (empty($gedcomid)) $gedcomid = $GEDCOMID;
		if (!isset(self::$notecache[$gedcomid][$xref])) {
			self::$notecache[$gedcomid][$xref] = new Note($xref, $gedrec, $gedcomid);
		}
		return self::$notecache[$gedcomid][$xref];
	}
	
	/**
	 * Constructor for note object
	 * @param string $gedrec	the raw note gedcom record
	 */
	public function __construct($id, $gedrec="", $gedcomid="", $new=false) {
		global $GEDCOMID;

		parent::__construct($id, $gedrec, $gedcomid);
		
		$this->exclude_facts = "CONC,CONT";
		
		if ($this->disp) {
			// If the record is changed, check WHAT is changed.
			if ($this->ThisChanged() && ($this->GetNoteText() != $this->GetNoteText(true))) $this->textchanged = true;
			
		}
	}
	
	public function __get($property) {
		
		switch ($property) {
			case "text":
				return $this->GetNoteText();
				break;
			case "textchanged":
				return $this->textchanged;
				break;
			case "title":
				return $this->GetTitle();
				break;
			default:
				return parent::__get($property);
				break;
		}
	}
	
	public function ObjCount() {
		$count = 0;
		foreach(self::$notecache as $ged => $note) {
			$count += count($note);
		}
		return $count;
	}	
	
	/**
	 * get the title of this note record
	 * @return string
	 */
	public function getTitle($l=40, $changed=false) {
		global $gm_lang;

		if (!$this->disp) {
			$this->title = $gm_lang["private"];
			return $this->title;
		}
		
		if ($this->ThisChanged() && !is_null($this->GetChangedGedrec())) {
			if (is_null($this->newtitle)) {
				$this->newtitle = $this->GetNoteText($changed); // Get the text
				$this->newtitle = trim($this->newtitle); // we must show some starting characters
				$this->newtitle = preg_replace("~[\r|\n]+~", " ", $this->newtitle);
				$this->newtitle = preg_replace("~<br />~", " ", $this->newtitle);
				if (empty($this->newtitle)) $this->newtitle = $gm_lang["unknown"];
			}
			if ($l >= strlen($this->newtitle) || $l == 0) return $this->newtitle;
			else return substr($this->newtitle, 0, $l).".....";
		}
		
		if (is_null($this->title)) {
			$this->title = $this->GetNoteText($changed); // Get the text
			$this->title = trim($this->title); // we must show some starting characters
			$this->title = preg_replace("~[\r|\n]+~", " ", $this->title);
			$this->title = preg_replace("~<br />~", " ", $this->title);
			if (empty($this->title)) {
				$this->title = $gm_lang["unknown"];
				return $this->title;
			}
		}
		if ($l >= strlen($this->title) || $l == 0) return $this->title;
		else return substr($this->title, 0, $l).".....";
	}
	
	/**
	 * get the text of the note
	 */
	public function getNoteText($changed=false) {
		
		if (!$this->disp) {
			$this->newtext = $gm_lang["private"];
			return $this->text;
		}
		else if ($this->ThisChanged() && !is_null($this->GetChangedGedrec())) {
			if (!is_null($this->newtext)) return $this->newtext;
			$this->newtext = "";
			$nt = preg_match("/0 @.+@ NOTE (.*)(\r\n|\n|\r)*/", $this->GetChangedGedrec(), $n1match);
			if ($nt>0) $this->newtext = preg_replace("/~~/", "<br />", $n1match[1]);
			$this->newtext .= GetCont(1, $this->GetChangedGedrec());
			return $this->newtext;
		}
		else {
			if (!is_null($this->text)) return $this->text;
			$this->text = "";
			$nt = preg_match("/0 @.+@ NOTE (.*)(\r\n|\n|\r)*/", $this->gedrec, $n1match);
			if ($nt>0) $this->text = preg_replace("/~~/", "<br />", $n1match[1]);
			$this->text .= GetCont(1, $this->gedrec);
			return $this->text;
		}
	}		
		
	protected function GetLinksFromIndis() {

		if (!is_null($this->indilist)) return $this->indilist;
		$this->indilist = array();
		$this->indi_hide = 0;
		
		$sql = "SELECT DISTINCT i_key, i_gedcom, i_isdead, i_id, i_file  FROM ".TBLPREFIX."other_mapping, ".TBLPREFIX."individuals WHERE om_oid='".$this->xref."' AND om_gedfile='".$this->gedcomid."' AND om_type='INDI' AND om_gid=i_id AND om_gedfile=i_file";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()){
			$person = null;
			$person =& Person::GetInstance($row["i_id"], $row["i_gedcom"]);
			if ($person->disp_name) {
				$this->indilist[$row["i_key"]] = $person;
			}
			else $this->indi_hide++;
		}
		uasort($this->indilist, "ItemObjSort");
		$this->indi_count=count($this->indilist);
		return $this->indilist;
	}
	
	protected function GetLinksFromFams() {

		if (!is_null($this->famlist)) return $this->famlist;
		$this->famlist = array();
		$this->fam_hide = 0;
		
		$sql = "SELECT DISTINCT f_key, f_gedcom, f_id, f_file, f_husb, f_wife  FROM ".TBLPREFIX."other_mapping, ".TBLPREFIX."families WHERE om_oid='".$this->xref."' AND om_gedfile='".$this->gedcomid."' AND om_type='FAM' AND om_gid=f_id AND om_gedfile=f_file";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()){
			$family = null;
			$family =& Family::GetInstance($row["f_id"], $row["f_gedcom"]);
			if ($family->disp) {
				$this->famlist[$row["f_key"]] = $family;
			}
			else $this->fam_hide++;
		}
		uasort($this->famlist, "ItemObjSort");
		$this->fam_count = count($this->famlist);
		return $this->famlist;
	}
	
	protected function GetLinksFromMedia() {
		
		if (!is_null($this->medialist)) return $this->medialist;
		$this->medialist = array();
		$this->media_hide = 0;
		
		$sql = "SELECT DISTINCT m_media, m_gedrec, m_gedfile FROM ".TBLPREFIX."other_mapping, ".TBLPREFIX."media WHERE om_oid='".$this->xref."' AND om_gedfile='".$this->gedcomid."' AND om_type='OBJE' AND om_gid=m_media AND m_gedfile=om_gedfile";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()) {
			$mediaitem = null;
			$mediaitem =& MediaItem::GetInstance($row["m_media"], $row["m_gedrec"], $row["m_gedfile"]);
			if ($mediaitem->disp) $this->medialist[JoinKey($row["m_media"], $row["m_gedfile"])] = $mediaitem;
			else $this->media_hide++;
		}
		uasort($this->medialist, "TitleObjSort");
		$this->media_count=count($this->medialist);
		return $this->medialist;
	}
	
	/**
	 * get the list of sources connected to a record
	 * @return array
	 */
	protected function GetLinksFromSources() {

		if(!is_null($this->sourcelist)) return $this->sourcelist;
		$this->sourcelist = array();
		$this->sour_hide = 0;
		
		$sql = 	"SELECT DISTINCT s_key, s_id, s_gedcom, s_file FROM ".TBLPREFIX."other_mapping, ".TBLPREFIX."sources WHERE om_oid='".$this->xref."' AND om_gedfile='".$this->gedcomid."' AND om_type='SOUR' AND s_file=om_gedfile AND s_id=om_gid";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()){
			$source = null;
			$source =& Source::GetInstance($row["s_id"], $row["s_gedcom"]);
			if ($source->disp) $this->sourcelist[$row["s_key"]] = $source;
			else $this->sour_hide++;
		}
		uasort($this->sourcelist, "GedcomObjSort");
		$this->sour_count = count($this->sourcelist);
		return $this->sourcelist;
	}
	
	/**
	 * get the list of repositories connected to this record
	 * @return array
	 */
	protected function GetLinksFromRepos() {
		
		if (!is_null($this->repolist)) return $this->repolist;
		
		$this->repolist = array();
		$this->repo_hide = 0;
		
		// repositories can be linked from 
		$sql = 	"SELECT o_key, o_id, o_gedcom FROM ".TBLPREFIX."other_mapping, ".TBLPREFIX."other WHERE om_oid='".$this->xref."' AND om_gedfile='".$this->gedcomid."' AND o_type='REPO' AND o_file=om_gedfile AND o_id=om_gid";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()){
			$repo = null;
			$repo =& Repository::GetInstance($row["o_id"], $row["o_gedcom"], $this->gedcomid);
			if ($repo->disp) $this->repolist[$row["o_key"]] = $repo;
			else $this->repo_hide++;
		}
		uasort($this->repolist, "ItemSort");
		$this->repo_count=count($this->repolist);
		return $this->repolist;
	}
		
	public function PrintListNote($len=60, $useli=true) {
		
		if (!$this->disp) return false;
		
		if ($useli) {
			if (begRTLText($this->GetTitle())) print "\n\t\t\t<li class=\"rtl\" dir=\"rtl\">";
			else print "\n\t\t\t<li class=\"ltr\" dir=\"ltr\">";
		}
		print "\n\t\t\t<a href=\"note.php?oid=".$this->xref."&amp;gedid=".$this->gedcomid."\" class=\"list_item\">".PrintReady($this->GetTitle($len));
		print $this->addxref;
		print "</a>\n";
		if ($useli) print "</li>\n";
	}	
}
?>