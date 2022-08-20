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
 * @version $Id: note_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
 
class Note extends GedcomRecord {

	// General class information
	public $classname = "Note";				// Name of this class
	public $datatype = "NOTE";				// Type od data
	private static $cache = array();		// Holder of the instances for this class
	
	// Data
	private $text = null;					// Text of the note
	private $oldtext = null;				// Text of the note, forced from old record
	private $newtext = null;				// Text of the note including pending changes
	private $title = null;					// Title is shortened text
	private $name = null;					// Title is shortened text (same as title)
	private $newtitle = null;				// Title is shortened text after applying pending changes
	
	private $textchanged = null;			// If any pending changes exist for this note
	
	public static function GetInstance($xref, $gedrec="", $gedcomid="") {
		
		if (empty($gedcomid)) $gedcomid = GedcomConfig::$GEDCOMID;
		if (!isset(self::$cache[$gedcomid][$xref])) {
			self::$cache[$gedcomid][$xref] = new Note($xref, $gedrec, $gedcomid);
		}
		return self::$cache[$gedcomid][$xref];
	}
	
	public static function NewInstance($xref, $gedrec="", $gedcomid="") {
		
		if (empty($gedcomid)) $gedcomid = GedcomConfig::$GEDCOMID;
		self::$cache[$gedcomid][$xref] = new Note($xref, $gedrec, $gedcomid);
		return self::$cache[$gedcomid][$xref];
	}
	
	public static function IsInstance($xref, $gedcomid="") {
		
		if (empty($gedcomid)) $gedcomid = GedcomConfig::$GEDCOMID;
		if (!isset(self::$cache[$gedcomid][$xref])) return false;
		else return true;
	}
	
	/**
	 * Constructor for note object
	 * @param string $gedrec	the raw note gedcom record
	 */
	public function __construct($id, $gedrec="", $gedcomid="", $new=false) {

		if (is_array($gedrec)) {
			$id = $gedrec["o_id"];
			$gedcomid = $gedrec["o_file"];
			$gedrec = $gedrec["o_gedrec"];
		}
		
		parent::__construct($id, $gedrec, $gedcomid);
		
		$this->exclude_facts = "CONC,CONT";
		
	}
	
	public function __get($property) {
		
		switch ($property) {
			case "text":
				return $this->GetNoteText();
				break;
			case "oldtext":
				return $this->GetNoteText("old");
				break;
			case "textchanged":
				return $this->textchanged;
				break;
			case "title":
				return $this->GetTitle();
				break;
			case "name":
				return $this->GetTitle();
				break;
			case "textchanged":
				return $this->IsTextChanged();
				break;
			default:
				return parent::__get($property);
				break;
		}
	}
	
	public function ObjCount() {
		$count = 0;
		foreach(self::$cache as $ged => $note) {
			$count += count($note);
		}
		return $count;
	}	
	
	/**
	 * get the title of this note record
	 * @return string
	 */
	public function getTitle($l=40, $changed=false) {

		if (!$this->DisplayDetails()) {
			$this->title = GM_LANG_private;
			return $this->title;
		}
		
		if ($this->ThisChanged() && !is_null($this->GetChangedGedrec())) {
			if (is_null($this->newtitle)) {
				$this->newtitle = $this->GetNoteText($changed); // Get the text
				$this->newtitle = trim($this->newtitle); // we must show some starting characters
				$this->newtitle = preg_replace("~[\r|\n]+~", " ", $this->newtitle);
				$this->newtitle = preg_replace("~<br />~", " ", $this->newtitle);
				if (empty($this->newtitle)) $this->newtitle = GM_LANG_unknown;
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
				$this->title = GM_LANG_unknown;
				return $this->title;
			}
		}
		if ($l >= strlen($this->title) || $l == 0) return $this->title;
		else return substr($this->title, 0, $l).".....";
	}
	
	/**
	 * get the text of the note
	 */
	public function getNoteText($type="") {
		
		if (!$this->DisplayDetails()) {
			$this->newtext = GM_LANG_private;
			return $this->text;
		}
		else if ($type != "old" && $this->ThisChanged() && !is_null($this->GetChangedGedrec())) {
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
		$key = "";
		
		$sql = "SELECT DISTINCT n_id, i_key, i_gedrec, i_isdead, i_id, i_file, n_name, n_surname, n_nick, n_letter, n_fletter, n_type  FROM ".TBLPREFIX."other_mapping, ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE om_oid='".$this->xref."' AND om_file='".$this->gedcomid."' AND om_type='INDI' AND om_gid=i_id AND om_file=i_file AND i_key=n_key ORDER BY i_key, n_id";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()){
			if ($key != $row["i_key"]) {
				if ($key != "") $person->names_read = true;
				$key = $row["i_key"];
				$person = null;
				$person =& Person::GetInstance($row["i_id"], $row, $row["i_file"]);
				if ($person->DispName()) {
					$this->indilist[$row["i_key"]] = $person;
				}
				else $this->indi_hide++;
			}
			if ($person->DispName()) $person->addname = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_nick"], $row["n_type"], $row["n_fletter"]);
		}
		if ($key != "") $person->names_read = true;
		
		uasort($this->indilist, "ItemObjSort");
		$this->indi_count=count($this->indilist);
		return $this->indilist;
	}
	
	protected function GetLinksFromFams() {

		if (!is_null($this->famlist)) return $this->famlist;
		$this->famlist = array();
		$this->fam_hide = 0;
		
		$sql = "SELECT DISTINCT f_key, f_gedrec, f_id, f_file, f_husb, f_wife  FROM ".TBLPREFIX."other_mapping, ".TBLPREFIX."families WHERE om_oid='".$this->xref."' AND om_file='".$this->gedcomid."' AND om_type='FAM' AND om_gid=f_id AND om_file=f_file";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()){
			$family = null;
			$family =& Family::GetInstance($row["f_id"], $row, $row["f_file"]);
			if ($family->DisplayDetails()) {
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
		
		$sql = "SELECT DISTINCT m_media, m_gedrec, m_file, m_ext, m_mfile FROM ".TBLPREFIX."other_mapping, ".TBLPREFIX."media WHERE om_oid='".$this->xref."' AND om_file='".$this->gedcomid."' AND om_type='OBJE' AND om_gid=m_media AND m_file=om_file";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()) {
			$mediaitem = null;
			$mediaitem =& MediaItem::GetInstance($row["m_media"], $row, $row["m_file"]);
			if ($mediaitem->DisplayDetails()) $this->medialist[JoinKey($row["m_media"], $row["m_file"])] = $mediaitem;
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
		
		$sql = 	"SELECT DISTINCT s_key, s_id, s_gedrec, s_file FROM ".TBLPREFIX."other_mapping, ".TBLPREFIX."sources WHERE om_oid='".$this->xref."' AND om_file='".$this->gedcomid."' AND om_type='SOUR' AND s_file=om_file AND s_id=om_gid";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()){
			$source = null;
			$source =& Source::GetInstance($row["s_id"], $row, $row["s_file"]);
			if ($source->DisplayDetails()) $this->sourcelist[$row["s_key"]] = $source;
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
		$sql = 	"SELECT o_key, o_id, o_gedrec, o_file FROM ".TBLPREFIX."other_mapping, ".TBLPREFIX."other WHERE om_oid='".$this->xref."' AND om_file='".$this->gedcomid."' AND o_type='REPO' AND o_file=om_file AND o_id=om_gid";
		$res = NewQuery($sql);
		while($row = $res->FetchAssoc()){
			$repo = null;
			$repo =& Repository::GetInstance($row["o_id"], $row, $row["o_file"], $this->gedcomid);
			if ($repo->DisplayDetails()) $this->repolist[$row["o_key"]] = $repo;
			else $this->repo_hide++;
		}
		uasort($this->repolist, "ItemSort");
		$this->repo_count=count($this->repolist);
		return $this->repolist;
	}
	
	protected function ReadNoteRecord() {
		
		$sql = "SELECT o_gedrec FROM ".TBLPREFIX."other WHERE o_key='".DbLayer::EscapeQuery(JoinKey($this->xref, $this->gedcomid))."' AND o_type='NOTE'";
		$res = NewQuery($sql);
		if ($res) {
			if ($res->NumRows() != 0) {
				$row = $res->fetchAssoc();
				$this->gedrec = $row["o_gedrec"];
			}
		}
	}
		
	public function PrintListNote($len=60, $useli=true, $paste=false) {
		
		if (!$this->DisplayDetails()) return false;
		
		if ($useli) {
			if (begRTLText($this->GetTitle())) print "\n\t\t\t<li class=\"rtl\" dir=\"rtl\">";
			else print "\n\t\t\t<li class=\"ltr\" dir=\"ltr\">";
		}
		if ($paste) print "<a href=\"#\" onclick=\"sndReq(document.getElementById('dummy'), 'lastused', false, 'type', '".$this->datatype."', 'id', '".$this->key."'); pasteid('".$this->xref."', ''); return false;\" class=\"ListItem\">";
		else print "\n\t\t\t<a href=\"note.php?oid=".$this->xref."&amp;gedid=".$this->gedcomid."\" class=\"ListItem\">";
		print PrintReady($this->GetTitle($len));
		print $this->addxref;
		print "</a>\n";
		if ($useli) print "</li>\n";
		return true;
	}
	
	private function IsTextChanged() {
		
		if (is_null($this->textchanged)) {
			if ($this->DisplayDetails()) {
				// If the record is changed, check WHAT is changed.
				if ($this->ThisChanged() && ($this->GetNoteText() != $this->GetNoteText(true))) $this->textchanged = true;
				else $this->textchanged = false;
			}
		}
		return $this->textchanged;
	}	
}
?>