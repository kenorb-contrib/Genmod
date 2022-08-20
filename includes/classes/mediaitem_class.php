<?php
/**
 * Class file for media item objects
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
 * @version $Id: mediaitem_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
class MediaItem extends GedcomRecord {
	
	public $classname = "MediaItem";	// Name of this class
	public $datatype = "OBJE";			// Datatype
	private static $cache = array(); 	// Holder of the instances for this class
	
	private $extension = null;			// file extension
	private $title = null; 				// Title (if present) or filename of media file
	private $oldtitle = null; 			// As above, but from the unchanged data
	private $name = null; 				// Same as title
	private $filename = null;			// Calculated filename
	private $level = 0;					// Level of the OBJE record (should be 0)
	private $validmedia = null;			// If the extension exists in the $MEDIA_TYPE array
	private $fileobj = null;			// Objectholder of the physical file object
	private $isprimary = null;			// This record has a 1 _PRIM Y/N tag or not
	private $useasthumb = null;			// This record has a 1 _THUM Y/N tag or not
	
	public  $links = array();			// set in media class
	public  $linked = false; 			// set in media class

	public static function GetInstance($id, $gedrec="", $gedcomid="") {
		
		if (empty($gedcomid)) $gedcomid = GedcomConfig::$GEDCOMID;
		if (!isset(self::$cache[$gedcomid][$id])) {
			self::$cache[$gedcomid][$id] = new MediaItem($id, $gedrec, $gedcomid);
		}
		return self::$cache[$gedcomid][$id];
	}
	
	public static function NewInstance($id, $gedrec="", $gedcomid="") {
		
		if (empty($gedcomid)) $gedcomid = GedcomConfig::$GEDCOMID;
		self::$cache[$gedcomid][$id] = new MediaItem($id, $gedrec, $gedcomid);
		return self::$cache[$gedcomid][$id];
	}
	
	public static function IsInstance($xref, $gedcomid="") {
		
		if (empty($gedcomid)) $gedcomid = GedcomConfig::$GEDCOMID;
		if (!isset(self::$cache[$gedcomid][$xref])) return false;
		else return true;
	}
	
	public function __construct($id, $gedrec="", $gedcomid="") {
		
		if (is_array($gedrec)) {
			// Prefill some variables
			$this->extension = $gedrec["m_ext"];
			$file = $gedrec["m_mfile"];
			$id = $gedrec["m_media"];
			$gedcomid = $gedrec["m_file"];
			$gedrec = $gedrec["m_gedrec"];
		}
	
		parent::__construct($id, $gedrec, $gedcomid);
	
		$mimetypedetect = new MimeTypeDetect();
		
		if ($this->show_changes && $this->ThisChanged()) $this->filename = RelativePathFile(FilenameDecode(MediaFS::CheckMediaDepth(GetGedcomValue("FILE", 1, $this->GetChangedGedrec()))));
		else {
			if (isset($file)) $this->filename = RelativePathFile(FilenameDecode(MediaFS::CheckMediaDepth($file)));
			else $this->filename = RelativePathFile(FilenameDecode(MediaFS::CheckMediaDepth(GetGedcomValue("FILE", 1,$this->gedrec))));
		}

	}
	

	public function __get($property) {
		switch ($property) {
			case "title":
				return $this->getTitle();
				break;
			case "oldtitle":
				return $this->getTitle("old");
				break;
			case "name":
				return $this->getTitle();
				break;
			case "filename":
				return $this->filename;
				break;
			case "extension":
				return $this->getExtension();
				break;
			case "level":
				return $this->getLevel();
				break;
			case "validmedia":
				return $this->IsValidMedia();
				break;
			case "isprimary":
				return $this->IsPrimaryObject();
				break;
			case "useasthumb":
				return $this->UseAsThumb();
				break;
			case "fileobj":
				return $this->GetFileObject();
			default:
				return parent::__get($property);
				break;
		}
	}

	public function __set($property, $value) {
		switch ($property) {
			case "addlink":
				if (is_null($this->link_array)) $this->link_array = array();
				$this->link_array[] = $value;
				break;
			default:
				parent::__set($property, $value);
				break;
		}
	}

	private function GetFileObject() {
		
		if (is_null($this->fileobj)) {
			if (stristr($this->filename, "://")) $this->fileobj = new MFile($this->filename);
			else $this->fileobj = new MFile(GedcomConfig::$MEDIA_DIRECTORY.$this->filename);
		}
		return $this->fileobj;
	}
		
	public function ObjCount() {
		$count = 0;
		foreach(self::$cache as $ged => $media) {
			$count += count($media);
		}
		return $count;
	}	
	private function getTitle($type="") {
		
		$title = $type."title";
		if (is_null($this->$title)) {
			if ($this->DisplayDetails()) {
				if ($this->show_changes && $this->ThisChanged() && $type != "old") $gedrec = $this->GetChangedGedRec();
				else $gedrec = $this->gedrec;

				if (!PrivacyFunctions::ShowFactDetails("TITL", $this->xref, "OBJE") || !PrivacyFunctions::showFactDetails("FILE", $this->xref, "OBJE")) $this->$title = GM_LANG_private;
				else if (!PrivacyFunctions::showFact("TITL", $this->xref, "OBJE")) $this->$title = GM_LANG_unknown;
				else $this->$title = $this->GetMediaDescriptor($type);
			}
			else $this->$title = GM_LANG_private;
		}
		return $this->$title;
	}

	private function GetMediaDescriptor($type="") {
		
		if ($this->show_changes && $this->ThisChanged() && $type != "old") $gedrec = $this->GetChangedGedRec();
		else $gedrec = $this->gedrec;
		
		$title = GetGedcomValue("TITL", 1, $gedrec);
		if (empty($title)) $title = GetGedcomValue("FILE:TITL", 1, $gedrec);
		if (empty($title)) $title = GetGedcomValue("FILE", 1, $gedrec);
		else $title = stripslashes($title);
		return $title;
	}
		
	private function getExtension() {
		
		if (is_null($this->extension)) {
			$et = preg_match("/(\.\w+)$/", $this->filename, $ematch);
			if ($et>0) $this->extension =  substr(trim($ematch[1]),1);
			else $this->extension = "";
		}
		return $this->extension;
	}
	
	private function getLevel() {
		
		if (is_null($this->level)) {
			if ($this->show_changes && $this->ThisChanged()) $this->level = substr(trim($this->GetChangedGedrec()), 0, 1);
			else $this->level = substr(trim($this->gedrec), 0, 1);
		}
		return $this->level;
	}

	private function IsValidMedia() {
		
		if (is_null($this->validmedia)) {
			$this->validmedia = MediaFS::IsValidMedia($this->filename);
		}
		return $this->validmedia;
	}
				
	/**
	 * get the list of individuals connected to this MM object
	 * @return array
	 */
	protected function GetLinksFromIndis() {

		if (!is_null($this->indilist)) return $this->indilist;
		$this->indilist = array();
		$this->indi_hide = 0;
		$key = "";
		
		$sql = "SELECT DISTINCT n_id, i_key, i_gedrec, i_isdead, i_id, i_file, n_name, n_surname, n_nick, n_letter, n_fletter, n_type  FROM ".TBLPREFIX."media_mapping, ".TBLPREFIX."individuals, ".TBLPREFIX."names WHERE mm_media='".$this->xref."' AND mm_file='".$this->gedcomid."' AND mm_type='INDI' AND mm_gid=i_id AND mm_file=i_file AND i_key=n_key ORDER BY i_key, n_id";
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
	
	/**
	 * get the list of families connected to this MM object
	 * @return array
	 */
	protected function GetLinksFromFams() {

		if (!is_null($this->famlist)) return $this->famlist;
		$this->famlist = array();
		$this->fam_hide = 0;
		
		$sql = "SELECT DISTINCT f_key, f_gedrec, f_id, f_file, f_husb, f_wife  FROM ".TBLPREFIX."media_mapping, ".TBLPREFIX."families WHERE mm_media='".$this->xref."' AND mm_file='".$this->gedcomid."' AND mm_type='FAM' AND mm_gid=f_id AND mm_file=f_file";
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

	/**
	 * get the list of sources connected to a record
	 * @return array
	 */
	protected function GetLinksFromSources() {

		if(!is_null($this->sourcelist)) return $this->sourcelist;
		$this->sourcelist = array();
		$this->sour_hide = 0;
		
		$sql = 	"SELECT DISTINCT s_key, s_id, s_gedrec, s_file FROM ".TBLPREFIX."media_mapping, ".TBLPREFIX."sources WHERE mm_media='".$this->xref."' AND mm_file='".$this->gedcomid."' AND mm_type='SOUR' AND s_file=mm_file AND s_id=mm_gid";
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
		$sql = 	"SELECT o_id, o_gedrec FROM ".TBLPREFIX."media_mapping, ".TBLPREFIX."other WHERE mm_media='".$this->xref."' AND mm_file='".$this->gedcomid."' AND mm_type='REPO' AND o_type='REPO' AND o_file=mm_file AND o_id=mm_gid";
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

	protected function ReadMediaRecord() {
		
		$sql = "SELECT m_gedrec FROM ".TBLPREFIX."media WHERE m_media='".DbLayer::EscapeQuery($this->xref)."' AND m_file='".$this->gedcomid."'";
		$res = NewQuery($sql);
		if ($res) {
			if ($res->NumRows() != 0) {
				$row = $res->fetchAssoc();
				$this->gedrec = $row["m_gedrec"];
			}
		}
	}
		
	// Prints the information for media in a list view
	public function PrintListMedia($useli=true, $fact="", $paste=false) {
		
		if (!$this->DisplayDetails()) return false;
		if ($useli) {
			if (begRTLText($this->GetTitle())) print "\n\t\t\t<li class=\"rtl\" dir=\"rtl\">";
			else print "\n\t\t\t<li class=\"ltr\" dir=\"ltr\">";
		}
		if ($paste) {
			print "<a href=\"#\" onclick=\"sndReq(document.getElementById('dummy'), 'lastused', false, 'type', '".$this->datatype."', 'id', '".$this->key."'); pasteid('".$this->xref."', ''); return false;\" class=\"ListItem\">";
		}
		else print "\n\t\t\t<a href=\"mediadetail.php?mid=".$this->xref."&amp;gedid=".$this->gedcomid."\" class=\"ListItem\">";
		print PrintReady($this->GetTitle());
		print $this->addxref;
		if (!empty($fact)) {
			print " <i>(";
			if (defined("GM_FACT_".$fact)) print constant("GM_FACT_".$fact);
			else print $fact;
			print ")</i>";
		}
		print "</a>\n";
		if ($useli) print "</li>\n";
		return true;
	}
	
	private function IsPrimaryObject() {
		
		if (is_null($this->isprimary)) {
			$this->isprimary = GetGedcomValue("_PRIM", 1, ($this->ischanged ? $this->changedgedrec : $this->gedrec));
		}
		return $this->isprimary;
	}
	
	private function UseAsThumb() {
		
		if (is_null($this->useasthumb)) {
			$this->useasthumb = GetGedcomValue("_THUM", 1, ($this->ischanged ? $this->changedgedrec : $this->gedrec));
			if ($this->useasthumb == "") $this->isprimary = GetGedcomValue("_THUM", 2, ($this->ischanged ? $this->changedgedrec : $this->gedrec));
		}
		return $this->useasthumb;
	}
			
}
?>