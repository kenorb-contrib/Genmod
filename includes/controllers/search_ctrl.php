<?php
/**
 * Controller for the Search Page
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
 *
 * @package Genmod
 * @subpackage Search
 * @version $Id: search_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}
 
class SearchController extends BaseController {
		
	public $classname = "SearchController";	// Name of this class
	
	// Containers for the results and counters
	private $sindilist = array();			// Container for results for individuals
	private $printindiname = array();		// Container for the print results for individuals
	private $indi_printed = array();		// Container for printed ID's for individuals
	private $indi_hide = array();			// Container for ID's for hidden individuals

	private $sfamlist = array();			// Container for results for families with hits in the name
	private $sfamlist2 = array();			// Container for results for families with hits in the gedcom record
	private $printfamname = array();		// Container for the print results for families
	private $fam_printed = array();			// Container for printed ID's for families
	private $fam_hide = array();			// Container for ID's for hidden families

	private $ssourcelist = array();			// Container for results for sources
	private $printsource = array();			// Container for the print results for sources
	private $sour_printed = array();		// Container for printed ID's for sources
	private $sour_hide = array();			// Container for ID's for hidden sources

	private $srepolist = array();			// Container for results for repositories
	private $printrepo = array();			// Container for the print results for repositories
	private $repo_printed = array();		// Container for printed ID's for repositories
	private $repo_hide = array();			// Container for ID's for hidden repositories

	private $smedialist = array();			// Container for results for media items
	private $printmedia = array();			// Container for the print results for media items
	private $media_printed = array();		// Container for printed ID's for media items
	private $media_hide = array();			// Container for ID's for hidden media items

	private $snotelist = array();			// Container for results for general notes
	private $printnote = array();			// Container for the print results for general notes
	private $note_printed = array();		// Container for printed ID's for general notes
	private $note_hide = array();			// Container for ID's for hidden general notes

	private $cquery = null;					// Results from ParseQuery
	private $found = null;					// Found a valid result, can be added to print (check for private later)
	private $hit = null;					// Found a result, which may be in a hidden tag. If not $found, add up one to xxx_hide
		
	// General options and input
	private $showasso = null;				// Also show the persons/families that a found person has relations to (on or off)
	private $assolist = null;				// Holder for the assolist
	private $searchgeds = array();			// Array of gedcom numbers to perform the search in
	private $origin = null;					// Where the controller is called from
	
	// Options and input for Soundex search
	private $soundex = null;				// Type of Soundex search (Russell or DaitchM, default Russell)
	private $nameprt = null;				// Print all or just found names for a record (all or hit, default all)
	private $sorton = null;					// Sort results on first or lastname (last or first, default last)
	private $firstname = null;				// First name entered in the search box
	private $myfirstname = null;			// Saved input value for first name entered in the search box
	private $lastname = null;				// Last name entered in the search box
	private $mylastname = null;				// Saved input value for last name entered in the search box
	private $place = null;					// Place entered in the search box
	private $myplace = null;				// Saved input value for place entered in the search box
	private $year = null;					// Year entered in the search box
	private $myyear = null;					// Saved input value for year entered in the search box
	
	// Options and input for General search
	private $query = null;					// Query string for general search
	private $myquery = null;				// Saved version of the input for query
	private $tagfilter = null;				// Filter hits on tags or not (on or off, default on)
	private $srindi = null;					// Set to yes if we must search individuals
	private $srfams = null;					// Set to yes if we must search families
	private $srsour = null;					// Set to yes if we must search sources
	private $srmedia = null;				// Set to yes if we must search media items
	private $srnote = null;					// Set to yes if we must search general notes
	private $srrepo = null;					// Set to yes if we must search repositories

	// Options and input for quickstart
	private $crossged = null;				// Set to yes if the search is called from the quickstart block	and 
											// all gedcoms must be searched
											
	// Options and input for topsearch 
	private $topsearch = null;				// Set to yes if search is called from the top search option
	
	// Options and input for find
	private $type = null;					// Type of find to perform
	private $srfile = null;					// Set to yes if we must search file objects
	private $srplac = null;					// Set to yes if we must search places
	private $external_links = null;			// If we are displaying external links for media files
	private $directory = null;				// Directory we are displaying for media files
	private $thumbdir = null;				// Thumbdir of the directory we are displaying for media files
	private $level = null;					// Level we are on in the media directory tree
	private $showthumb = null;				// If we display the thumb with the link or just the link
	private $magnify = null;				// If we display the characters large or not
	private $language_filter = null;		// Character set to display for specialchars

	
	public function __construct($org="search") {
		
		parent::__construct();
		
		$this->origin = $org;
		if ($this->origin == "search") {
			if (!isset($_REQUEST["showasso"])) $this->showasso = "off";
			else $this->showasso = $_REQUEST["showasso"];
			
			if (isset($_REQUEST["query"])) {
				$this->query = $_REQUEST["query"];
				$this->query = stripslashes($this->query);
				$this->myquery = $this->query;
			}
					
			if (!isset($_REQUEST["soundex"])) $this->soundex = "Russell";
			else $this->soundex = $_REQUEST["soundex"];
			
			if (isset($_REQUEST["topsearch"])) $this->topsearch = $_REQUEST["topsearch"];
			
			// Set the gedcoms to search in
			$this->GetSearchGeds();
	
			// If topsearch, see if we must jump to a record
			if ($this->topsearch == "yes") $this->CheckTopSearch();
			
			// Reset some settings if the quicksearch is selected
			if (!$this->CheckQuickStart()) {
				if ($this->action == "general") $this->GetGeneralParms();
				else $this->GetSoundexParms();
			}
				
			if ($this->action == "general") {
				if(!is_null($this->query) && trim($this->query) != "") $this->GetGeneralResults();
			}
			else if ($this->action == "soundex") {
				if ((!is_null($this->lastname) || !is_null($this->firstname)) && count($this->searchgeds)>0) $this->GetSoundexResults();
			}
		}
		else if ($this->origin == "find") {
			$this->GetFindParms();
			$this->searchgeds[] = GedcomConfig::$GEDCOMID;
			if ($this->action == "filter" && !in_array($this->type, array("specialchar", "file", "place"))) $this->GetGeneralResults();
		}
	}

	public function __get($property) {
		switch($property) {
			case "query":
				return $this->query; //ok
				break;
			case "myquery":
				return $this->myquery; //ok
				break;
			case "srindi":
				return $this->srindi; //ok
				break;
			case "srfams":
				return $this->srfams; //ok
				break;
			case "srsour":
				return $this->srsour; //ok
				break;
			case "srmedia":
				return $this->srmedia; //ok
				break;
			case "srrepo":
				return $this->srrepo; //ok
				break;
			case "srnote":
				return $this->srnote; //ok
				break;
			case "srfile":
				return $this->srfile; //ok
				break;
			case "firstname":
				return $this->firstname; //ok
				break;
			case "myfirstname":
				return $this->myfirstname; //ok
				break;
			case "lastname":
				return $this->lastname; //ok
				break;
			case "mylastname":
				return $this->mylastname; //ok
				break;
			case "place":
				return $this->place; //ok
				break;
			case "myplace":
				return $this->myplace; //ok
				break;
			case "myyear":
				return $this->myyear; //ok
				break;
			case "soundex":
				return $this->soundex; //ok
				break;
			case "nameprt":
				return $this->nameprt; //ok
				break;
			case "showasso":
				return $this->showasso; //ok
				break;
			case "sorton":
				return $this->sorton; //ok
				break;
			case "tagfilter":
				return $this->tagfilter; //ok
				break;
			case "searchgeds":
				return $this->searchgeds; //ok
				break;
			case "topsearch":
				return $this->topsearch; //ok
				break;
			case "sindilist":
				return $this->sindilist; //ok
				break;
			case "printindiname":
				return $this->printindiname; //ok
				break;
			case "indi_hide":
				return $this->indi_hide; //ok
				break;
			case "indi_total":
				return ($this->indi_printed + $this->indi_hide); //ok
				break;
			case "sfamlist":
				return $this->sfamlist; //ok
				break;
			case "printfamname":
				return $this->printfamname; //ok
				break;
			case "fam_hide":
				return $this->fam_hide; //ok
				break;
			case "fam_total":
				return ($this->fam_printed + $this->fam_hide); //ok
				break;
			case "ssourcelist":
				return $this->ssourcelist; //ok
				break;
			case "printsource":
				return $this->printsource; //ok
				break;
			case "sour_hide":
				return $this->sour_hide; //ok
				break;
			case "sour_total":
				return ($this->sour_printed + $this->sour_hide); //ok
				break;
			case "smedialist":
				return $this->smedialist; //ok
				break;
			case "printmedia":
				return $this->printmedia; //ok
				break;
			case "media_hide":
				return $this->media_hide; //ok
				break;
			case "media_total":
				return ($this->media_printed + $this->media_hide); //ok
				break;
			case "srepolist":
				return $this->srepolist; //ok
				break;
			case "printrepo":
				return $this->printrepo; //ok
				break;
			case "repo_hide":
				return $this->repo_hide; //ok
				break;
			case "repo_total":
				return ($this->repo_printed + $this->repo_hide); //ok
				break;
			case "snotelist":
				return $this->snotelist; //ok
				break;
			case "printnote":
				return $this->printnote; //ok
				break;
			case "note_hide":
				return $this->note_hide; //ok
				break;
			case "note_total":
				return ($this->note_printed + $this->note_hide); //ok
				break;
			case "type":
				return $this->type;
				break;
			case "external_links":
				return $this->external_links;
				break;
			case "directory":
				return $this->directory;
				break;
			case "thumbdir":
				return $this->thumbdir;
				break;
			case "level":
				return $this->level;
				break;
			case "showthumb":
				return $this->showthumb;
				break;
			case "magnify":
				return $this->magnify;
				break;
			case "language_filter":
				return $this->language_filter;
				break;
			case "origin":
				return $this->origin;
				break;
			default:
				return parent::__get($property);
				break;
		}
	}

	public function __set($property, $value) {
		switch($property) {
			default:
				return parent::__set($property, $value);
				break;
		}
	}
	
	protected function GetPageTitle() {
		
		if (is_null($this->pagetitle)) {
			if ($this->origin == "search") {
				$this->pagetitle = GM_LANG_search;
				if (!is_null($this->action) && !empty($this->action) && ($this->action == "general" || $this->action == "soundex")) $this->pagetitle .= " - ".constant("GM_LANG_search_".$this->action);
			}
			else if ($this->origin == "find") {
				switch ($this->type) {
					case "indi" :
						$this->pagetitle = GM_LANG_find_individual;
						break;
					case "fam" :
						$this->pagetitle = GM_LANG_find_fam_list;
						break;
					case "file" :
						$this->pagetitle = GM_LANG_find_mfile;
						break;
					case "media" :
						$this->pagetitle = GM_LANG_find_media;
						break;
					case "place" :
						$this->pagetitle = GM_LANG_find_place;
						break;
					case "repo" :
						$this->pagetitle = GM_LANG_repo_list;
						break;
					case "source" :
						$this->pagetitle = GM_LANG_find_source;
						break;
					case "note" :
						$this->pagetitle = GM_LANG_find_note;
						break;
					case "specialchar" :
						$this->pagetitle = GM_LANG_find_specialchar;
						break;
				}
			}
		}
		return $this->pagetitle;
	}

	// Set the gedcoms to search in, either from the seletion boxes, or from the crossgeds option in quickstart
	// If the search is performed from the topsearch box on top of the page, search only in the current gedcom.
	private function GetSearchGeds() {
		global $GEDCOMS;
		
		if (!isset($_REQUEST["crossged"])) $this->crossged = "";
		else $this->crossged = $_REQUEST["crossged"];
		
		if ($this->topsearch == "yes") {
			$this->searchgeds[] = GedcomConfig::$GEDCOMID;
		}
		if ($this->action == "quickstart" && SystemConfig::$ALLOW_CHANGE_GEDCOM && $this->crossged == "yes") {
			foreach($GEDCOMS as $ged => $gedvalues) {
				$this->searchgeds[] = $ged;
			}
		}
		else {	
			foreach($GEDCOMS as $ged => $gedvalues) {
				$var = "sg".$ged;
				if (isset($_REQUEST[$var])) $this->searchgeds[] = $ged;
			}
		}
		
		if (count($this->searchgeds) == 0) $this->searchgeds[] = GedcomConfig::$GEDCOMID;
		
		// Check if we may search in the selected geds
		foreach($this->searchgeds as $key => $gedid) {
			SwitchGedcom($gedid);
			if (GedcomConfig::$MUST_AUTHENTICATE && $this->uname == "") unset($this->searchgeds[$key]);
		}
		SwitchGedcom();
	}	
	
	private function CheckQuickStart() {
		
		if ($this->action == "quickstart") {
			if (!is_null($this->query) && $this->query != "") {
				$this->action = "general";
				$this->srindi = "yes";
			}
			else {
				$this->action = "soundex";
				$this->GetSoundexParms();
				$this->soundex = "Russell";
				if (!is_null($this->firstname) && (NameFunctions::HasChinese($this->firstname) || NameFunctions::HasCyrillic($this->firstname))) $this->soundex = "DaitchM"; 
				if (!is_null($this->lastname) && (NameFunctions::HasChinese($this->lastname) || NameFunctions::HasCyrillic($this->lastname))) $this->soundex = "DaitchM"; 
				if (!is_null($this->place) && (NameFunctions::HasChinese($this->place) || NameFunctions::HasCyrillic($this->place))) $this->soundex = "DaitchM";
			}
			return true;
		}
		else return false;
	}
	
	// This section is to handle searches entered in the top search box in the themes
	private function CheckTopSearch() {
		
		// first set some required variables. Search only in current gedcom, only in indi's.
		$this->srindi = "yes";
		
		// Then see if an ID is typed in. If so, we might want to jump there.
		if (!is_null($this->query) && $this->query != "") {
	
			$object = ConstructObject(str2upper($this->query));
			if (is_object($object)) {
				switch($object->type) {
					case "INDI":
						if ($object->disp_name) {
							header("Location: individual.php?pid=".$this->query."&gedid=".GedcomConfig::$GEDCOMID);
							exit;
						}
						break;
					case "FAM":
						if (($object->husb_id != "" && $object->husb->disp_name) && ($object->wife_id != "" && $object->wife->disp_name)) {
							header("Location: family.php?famid=".$this->query."&gedid=".GedcomConfig::$GEDCOMID);
							exit;
						}
						break;
					case "SOUR":
						if ($object->disp) {
							header("Location: source.php?sid=".$this->query."&gedid=".GedcomConfig::$GEDCOMID);
							exit;
						}
						break;
					case "REPO":
						if ($object->disp) {
							header("Location: repo.php?rid=".$this->query."&gedid=".GedcomConfig::$GEDCOMID);
							exit;
						}
						break;
					case "OBJE":
						if ($object->disp) {
							header("Location: mediadetail.php?mid=".$this->query."&gedid=".GedcomConfig::$GEDCOMID);
							exit;
						}
						break;
					case "NOTE":
						if ($object->disp) {
							header("Location: note.php?oid=".$this->query."&gedid=".GedcomConfig::$GEDCOMID);
							exit;
						}
						break;
				}
			}
		}
	}
	
	private function GetGeneralParms() {

		if (isset($_REQUEST["srindi"])) $this->srindi = $_REQUEST["srindi"];
		if (isset($_REQUEST["srfams"])) $this->srfams = $_REQUEST["srfams"];
		if (isset($_REQUEST["srsour"])) $this->srsour = $_REQUEST["srsour"];
		if (isset($_REQUEST["srrepo"])) $this->srrepo = $_REQUEST["srrepo"];
		if (isset($_REQUEST["srnote"])) $this->srnote = $_REQUEST["srnote"];
		if (isset($_REQUEST["srmedia"])) $this->srmedia = $_REQUEST["srmedia"];
		
		// if no general search specified, search in indi's
		if (is_null($this->srindi) && is_null($this->srfams) && is_null($this->srsour)&& is_null($this->srmedia)&& is_null($this->srnote)&& is_null($this->srrepo)) $this->srindi = "yes";
		
		if (!isset($_REQUEST["tagfilter"])) $this->tagfilter = "on";
		else $this->tagfilter = $_REQUEST["tagfilter"];
	}
	
	private function GetSoundexParms() {
		
		if (isset($_REQUEST["firstname"]) && !empty($_REQUEST["firstname"])) $this->firstname = $_REQUEST["firstname"];
		if (isset($_REQUEST["lastname"]) && !empty($_REQUEST["lastname"])) $this->lastname = $_REQUEST["lastname"];
		if (isset($_REQUEST["place"]) && !empty($_REQUEST["place"])) $this->place = $_REQUEST["place"];
		if (isset($_REQUEST["year"]) && !empty($_REQUEST["year"])) $this->year = $_REQUEST["year"];
		
		if (!isset($_REQUEST["nameprt"])) $this->nameprt = "";
		else $this->nameprt = $_REQUEST["nameprt"];
		
		if (!isset($_REQUEST["sorton"])) $this->sorton = "";
		else $this->sorton = $_REQUEST["sorton"];
		
		// If we only search on a place, no hits are likely to occur in person names. So, set nameprt to all.
		if (is_null($this->firstname) && is_null($this->lastname) && !is_null($this->place)) $this->nameprt = "all";
		
		if (!is_null($this->firstname)) $this->myfirstname = stripslashes($this->firstname);
		else $this->myfirstname = "";

		if (!is_null($this->lastname)) $this->mylastname = stripslashes($this->lastname);
		else $this->mylastname = "";

		if (!is_null($this->place)) $this->myplace = stripslashes($this->place);
		else $this->myplace = "";

		if (!is_null($this->year)) $this->myyear = $this->year;
		else $this->myyear = "";
	}
	
	private function GetFindParms() {
		global $lang_short_cut, $LANGUAGE;

		if (isset($_REQUEST["type"]) && !empty($_REQUEST["type"])) $this->type = $_REQUEST["type"];
		else $this->type = "indi";
		
		if ($this->type == "file") {
			
			if (isset($_REQUEST["external_links"]) && !empty($_REQUEST["external_links"])) $this->external_links = $_REQUEST["external_links"];
			else $this->external_links = "";
		
			if (isset($_REQUEST["directory"]) && !empty($_REQUEST["directory"])) $this->directory = $_REQUEST["directory"];
			else $this->directory = GedcomConfig::$MEDIA_DIRECTORY;
			//-- force the thumbnail directory to have the same layout as the media directory
			//-- Dots and slashes should be escaped for the preg_replace
			$srch = "/".addcslashes(GedcomConfig::$MEDIA_DIRECTORY,'/.')."/";
			$repl = addcslashes(GedcomConfig::$MEDIA_DIRECTORY."thumbs/",'/.');
			$this->thumbdir = stripcslashes(preg_replace($srch, $repl, $this->directory));
			
			if (!isset($_REQUEST["level"])) $this->level=0;
			else $this->level = $_REQUEST["level"];
	
			//-- prevent script from accessing an area outside of the media directory
			//-- and keep level consistency
			if (($this->level < 0) || ($this->level > GedcomConfig::$MEDIA_DIRECTORY_LEVELS)){
				$this->directory = GedcomConfig::$MEDIA_DIRECTORY;
				$this->level = 0;
			} elseif (preg_match("'^".RelativePathFile(GedcomConfig::$MEDIA_DIRECTORY)."'", $this->directory) == 0){
				$this->directory = GedcomConfig::$MEDIA_DIRECTORY;
				$this->level = 0;
			}
	
			$this->showthumb = isset($_REQUEST["showthumb"]);
			$this->action = "filter"; // Proceed with initial page
		}

		if ($this->type == "specialchar") {
			
			if (!isset($_REQUEST["magnify"])) $this->magnify=false;
			else $this->magnify = $_REQUEST["magnify"];
			
			if (!isset($_REQUEST["language_filter"])) $this->language_filter = "";
			else $this->language_filter = $_REQUEST["language_filter"];
			if (empty($this->language_filter)) {
				if (!empty($_SESSION["language_filter"])) $this->language_filter = $_SESSION["language_filter"];
				else $this->language_filter = $lang_short_cut[$LANGUAGE];
			}
		}
		
		
		if (isset($_REQUEST["query"]) && !empty($_REQUEST["query"])) $this->query = preg_replace("/\\\/", "", $_REQUEST["query"]);
	
		if ($this->type == "indi") $this->srindi = "yes";
		else if ($this->type == "fam") $this->srfams = "yes";
		else if ($this->type == "media") $this->srmedia = "yes";
		else if ($this->type == "repo") $this->srrepo = "yes";
		else if ($this->type == "source") $this->srsour = "yes";
		else if ($this->type == "note") $this->srnote = "yes";
		
		else if ($this->type == "place") $this->srplac = "yes";
		else if ($this->type == "file") $this->srfile = "yes";
		else if ($this->type == "specialchar") $this->action = "filter";
		
		$this->showasso = "off";
		$this->tagfilter = "off";
	}		
	
	private function GetGeneralResults() {
		global $gm_user, $global_facts;
		
		if ($this->origin == "search") {
			// Write a log entry
			$logstring = "General, ".$this->query;
			WriteToLog($logstring, "I", "F", $this->searchgeds);
		}
	
		// Cleanup the querystring so it can be used in a database query
		// Note: when more than one word is entered, this will return results where one word
		// is in one subrecord, another in another subrecord. Theze results are filtered later.
		if (strlen($this->query) == 1) $this->query = preg_replace(array("/\?/", "/\|/", "/\*/"), array("\\\?","\\\|", "\\\\\*") , $this->query);
		$this->query = preg_replace(array("/\(/", "/\)/", "/\//", "/\]/", "/\[/", "/\s+/"), array('\(','\)','\/','\]','\[', ' '), $this->query);
		
		// Get the cleaned up query to use in result comparisons
		$this->cquery = SearchFunctions::ParseFTSearchQuery($this->query);
		
		// Search the indi's
		if ((!is_null($this->srindi)) && (count($this->searchgeds)>0)) {
			$this->sindilist = SearchFunctions::FTSearchIndis($this->query, $this->searchgeds);
		}

		// Search the fams
		if ((!is_null($this->srfams)) && (count($this->searchgeds)>0)) {
			// Get the indilist, to check name hits. Store the ID's/names found in
			// the search array, so the fam records can be retrieved.
			// This way we include hits on family names.
			// If indi's are not searched yet, we have to search them first
			if (is_null($this->srindi)) $this->sindilist = SearchFunctions::FTSearchIndis($this->query, $this->searchgeds);
			$famquery = array();
			foreach($this->sindilist as $key1 => $myindi) {
				$this->found = false;
				foreach($myindi->name_array as $key2 => $name) {
					foreach ($this->cquery["includes"] as $qindex => $squery) {
						if ((preg_match("/".$squery["term"]."/i", $name[0]) > 0)) {
							$this->found = true;
							$famquery[] = $myindi->key;
							break;
						}
					}
					if ($this->found) break;
				}
			}
			// Get the famrecs with hits on names from the family table
			if (!empty($famquery)) $this->sfamlist = SearchFunctions::SearchFamsNames($famquery, "OR", true);
			// Get the famrecs with hits in the gedcom record from the family table
			$this->sfamlist2 = SearchFunctions::FTSearchFams($this->query, $this->searchgeds, "OR", true);
			$this->sfamlist = GmArrayMerge($this->sfamlist, $this->sfamlist2);
			// clear the myindilist is no search was intended for indi's
			// if (!is_null($this->srindi)) $sindilist = array();
		}

		// Search the sources
		if ((!is_null($this->srsour)) && (count($this->searchgeds)>0)) {
			$this->ssourcelist = SearchFunctions::FTSearchSources($this->query, $this->searchgeds);
		}

		// Search the repositories
		if ((!is_null($this->srrepo)) && (count($this->searchgeds)>0)) {
			$this->srepolist = SearchFunctions::FTSearchRepos($this->query, $this->searchgeds);
		}
		
		// Search the notes
		if ((!is_null($this->srnote)) && (count($this->searchgeds)>0)) {
			$this->snotelist = SearchFunctions::FTSearchNotes($this->query, $this->searchgeds);
		}
		
		// Search the media
		if ((!is_null($this->srmedia)) && (count($this->searchgeds)>0)) {
			$this->smedialist = SearchFunctions::FTSearchMedia($this->query, $this->searchgeds);
		}
		
		if ($this->origin == "search") {
			//-- if only 1 item is returned, automatically forward to that item
			// Check for privacy first. If ID cannot be displayed, continue to the search page.
			if (count($this->sindilist) == 1 && count($this->sfamlist) == 0 && count($this->ssourcelist) == 0 && count($this->srepolist) == 0 && count($this->smedialist) == 0 && count($this->snotelist) == 0 && !is_null($this->srindi)) {
				$this->assolist = ListFunctions::GetAssoList();
				foreach($this->sindilist as $key=>$indi) {
					SwitchGedcom($indi->gedcomid);
					if (!isset($this->assolist[$indi->key])) {
						if ($indi->disp_name) {
							header("Location: individual.php?pid=".$indi->xref."&gedid=".$indi->gedcomid);
							exit;
						}
					}
				}
			}
			if ((count($this->sindilist) == 0 || is_null($this->srindi)) && count($this->sfamlist) == 1 && count($this->ssourcelist) == 0 && count($this->srepolist) == 0 && count($this->smedialist) == 0 && count($this->snotelist) == 0) {
				foreach($this->sfamlist as $famid=>$fam) {
					if (($fam->husb_id != "" && $fam->husb->disp_name) && ($fam->wife_id != "" && $fam->wife->disp_name)) {
						header("Location: family.php?famid=".$fam->xref."&gedid=".$fam->gedcomid);
						exit;
					}
				}
			}
			if ((count($this->sindilist) == 0 || is_null($this->srindi)) && count($this->sfamlist) == 0 && count($this->ssourcelist) == 1 && count($this->srepolist) == 0 && count($this->smedialist) == 0 && count($this->snotelist) == 0) {
				foreach($this->ssourcelist as $sid=>$source) {
					if ($source->disp) {
						header("Location: source.php?sid=".$source->xref."&gedid=".$source->gedcomid);
						exit;
					}
				}
			}
			if ((count($this->sindilist) == 0 || is_null($this->srindi)) && count($this->sfamlist) == 0 && count($this->ssourcelist) == 0 && count($this->srepolist) == 1 && count($this->smedialist) == 0 && count($this->snotelist) == 0) {
				foreach($this->srepolist as $rid=>$repo) {
					if ($repo->disp) {
						header("Location: repo.php?rid=".$repo->xref."&gedid=".$repo->gedcomid);
						exit;
					}
				}
			}
			if ((count($this->sindilist) == 0 || is_null($this->srindi)) && count($this->sfamlist) == 0 && count($this->ssourcelist) == 0 && count($this->srepolist) == 0 && count($this->smedialist) == 0 && count($this->snotelist) == 1) {
				foreach($this->snotelist as $oid=>$note) {
					header("Location: note.php?oid=".$note->xref."&gedid=".$note->gedcomid);
					exit;
				}
			}
			if ((count($this->sindilist)==0 || is_null($this->srindi)) && count($this->sfamlist) == 0 && count($this->ssourcelist) == 0 && count($this->srepolist) == 0 && count($this->smedialist) == 1 && count($this->snotelist) == 0) {
				foreach($this->smedialist as $mid => $media) {
					header("Location: mediadetail.php?mid=".$media->xref."&gedid=".$media->gedcomid);
					exit;
				}
			}
		}
		// We have more than 1 result, so calculate the records to be printed
		
		//--- Results in these tags will be ignored when the tagfilter is on
		// Never show results in _UID
		if ($gm_user->userIsAdmin()) $skiptags = "_UID";
		// If not admin, also hide searches in RESN tags
		else $skiptags = "RESN, _UID";
		
		// Add the optional tags
		$skiptags_option = ", _GMU, FORM, CHAN, SUBM, REFN";
    	if ($this->tagfilter == "on") $skiptags .= $skiptags_option;
   		$userlevel = $gm_user->GetUserAccessLevel();
		$oldged = GedcomConfig::$GEDCOMID;
		
		// Prepare the individuals
		$cti=count($this->sindilist);
		if (($cti>0) && (!is_null($this->srindi))) {
			$curged = GedcomConfig::$GEDCOMID;

			// Add the facts in $global_facts that should not show
			$skiptagsged = $skiptags;
    		foreach ($global_facts as $gfact => $gvalue) {
	    		if (isset($gvalue["show"])) {
		    		if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
	    		}
  		  	}
			foreach ($this->sindilist as $key => $myindi) {
				$newged = $myindi->gedcomid;
				if ($newged != $curged) {
					SwitchGedcom($newged);
					$curged = $newged;
					// Recalculate the tags to skip
					$skiptagsged = $skiptags;
					foreach ($global_facts as $gfact => $gvalue) {
			    		if (isset($gvalue["show"])) {
	    					if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
    					}
 					}
				}
				//-- make sure that the data that was searched on is not in a private part of the record
				$this->hit = false;
		    	$this->found = false;
		    	
		    	// First check if the hit is in the key.
				$this->CheckKeyHits($myindi->key);
				
	    		// Check all records of the individual
				if ($this->found == false) {
					$this->CheckTagHits($myindi, $skiptagsged);
				}
				if ($this->found == true) {
					// print all names from the indi found
			    	foreach($myindi->name_array as $indexval => $namearray) {
						$this->printindiname[] = array(NameFunctions::SortableNameFromName($namearray[0]), $myindi->xref, $myindi->gedcomid, "", $indexval);
					}
					$this->indi_printed[$myindi->key] = true;
				}
				// No valid result, but we had a hit from the query
				else if ($this->hit == true) $this->indi_hide[$myindi->key] = true;
			}
		}
//		print "printed: ";
//		print_r($this->indi_printed);
//		print "<br />hide: ";
//		print_r($this->indi_hide);
		SwitchGedcom();
		
		// Get the fams to be printed
		$ctf = count($this->sfamlist);
		// What is the count for?
		if ($ctf>0 || count($this->printfamname) > 0) {
			$curged = GedcomConfig::$GEDCOMID;
			// Add the facts in $global_facts that should not show
			$skiptagsged = $skiptags;
    		foreach ($global_facts as $gfact => $gvalue) {
	    		if (isset($gvalue["show"])) {
		    		if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
	    		}
  		  	}
			foreach ($this->sfamlist as $key => $fam) {
				$newged = $fam->gedcomid;
				if ($newged != $curged) {
					SwitchGedcom($newged);
					$curged = $newged;
					// Recalculate the tags to skip
					$skiptagsged = $skiptags;
					foreach ($global_facts as $gfact => $gvalue) {
			    		if (isset($gvalue["show"])) {
	   						if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
    					}
					}
			  	}

				// lets see where the hit is
			    $this->found = false;
				$this->hit = false;
				
		    	// First check if the hit is in the key.
				$this->CheckKeyHits($fam->key);
				
				// If a name is hit, no need to check for tags
				if ($this->found == false) {
					foreach($fam->allnames as $nkey => $famname) {
						foreach ($this->cquery["includes"] as $qindex => $squery) {
							if ((preg_match("/".$squery["term"]."/i", $famname)) > 0) {
								$this->found = true;
								break;
							}
						}
						if ($this->found) break;
					}
				}
				
	    		// Check all records of the family
				if ($this->found == false) {
					$this->CheckTagHits($fam, $skiptagsged);
				}
				
				if ($this->found == true) {
					$printed = false;	
					foreach ($fam->allnames as $namekey => $famname) {
						$famsplit = preg_split("/(\s\+\s)/", trim($famname));
						// Both names have to have the same direction and combination of chinese/not chinese
						$foundf = false;
						$founds = false;
						$founde = false;
						if (hasRTLText($famsplit[0]) == hasRTLText($famsplit[1]) && NameFunctions::HasChinese($famsplit[0], true) == NameFunctions::HasChinese($famsplit[1], true)) {
							// do not print if the hit only in the second name. We want it first.
							foreach ($this->cquery["includes"] as $qindex => $squery) {
//								print $squery."<br />".$famsplit[0]."<br />".$famsplit[1]."<br />";
								if (preg_match("/".$squery["term"]."/i", $famsplit[0]) > 0) $foundf = true;
								if (preg_match("/".$squery["term"]."/i", $famsplit[1]) > 0) $founds = true;
							}
//							if ($foundf) print "ff is waar";
//							if ($founds) print "fs is waar";
//							print "<br /><br />";
							// now we must also check that excluded names are not printed anyway, regardless of in name 1 or 2
							if (isset($this->cquery["excludes"])) {
								foreach ($this->cquery["excludes"] as $qindex => $squery) {
	//								print $squery."<br />".$famsplit[0]."<br />".$famsplit[1]."<br />";
									if (preg_match("/".$squery["term"]."/i", $famsplit[0]) > 0) $founde = true;
									if (preg_match("/".$squery["term"]."/i", $famsplit[1]) > 0) $founde = true;
								}
							}
							 
							if ($foundf && !$founde) {
								$this->printfamname[] = array($famname, $fam->xref, $fam->gedcomid,"");
								$this->fam_printed[$fam->key] = "1";
								$printed = true;
							}
						}
					}
					if (!$printed && !$founde) {
						$this->printfamname[] = array($fam->sortable_name, $fam->xref, $fam->gedcomid,"");
						$this->fam_printed[$fam->key] = "1";
					}						
		    	}
				else if ($this->hit == true) $this->fam_hide[$fam->key] = 1;
			}
			SwitchGedcom();
		}
	
		// Add the assos to the indi and famlist
	  	if ($this->showasso == "on") {
		  	$this->SearchAddAssos();
		}
		if (count($this->printindiname) > 0) uasort($this->printindiname, "ItemSort");
		if (count($this->printfamname) > 0) uasort($this->printfamname, "ItemSort");

		SwitchGedcom();
		
		// Get the sources to be printed
		$cts=count($this->ssourcelist);
		if ($cts>0) {
			uasort($this->ssourcelist, "SourceDescrSort"); 
			$oldged = GedcomConfig::$GEDCOMID;
			$curged = GedcomConfig::$GEDCOMID;

			// Add the facts in $global_facts that should not show
			$skiptagsged = $skiptags;
    		foreach ($global_facts as $gfact => $gvalue) {
	    		if (isset($gvalue["show"])) {
		    		if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
	    		}
  		  	}
			foreach ($this->ssourcelist as $key => $source) {
				$newged = $source->gedcomid;
				if ($curged != $newged) {
					SwitchGedcom(GedcomConfig::$GEDCOMID);
					$curged = $newged;
					// Recalculate the tags to skip
					$skiptagsged = $skiptags;
					foreach ($global_facts as $gfact => $gvalue) {
			    		if (isset($gvalue["show"])) {
	    					if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
    					}
				  	}
				}
				
		    	$this->found = false;
		    	$this->hit = false;
		    	
		    	// First check if the hit is in the key.
				$this->CheckKeyHits($source->key);
				
	    		// Check all records of the source
				if ($this->found == false) {
					$this->CheckTagHits($source, $skiptagsged);
				}
				
				if ($this->found == true) {
					$this->printsource[] = $source->key;
					$this->sour_printed[$source->key] = "1";
				}
				else if ($this->hit == true) $this->sour_hide[$source->key] = 1;
			}
			SwitchGedcom();
		}
		
		// Get the repos to be printed
		$ctr = count($this->srepolist);
		if ($ctr > 0) {
			uasort($this->srepolist, "SourceDescrSort"); 
			$oldged = GedcomConfig::$GEDCOMID;
			$curged = GedcomConfig::$GEDCOMID;

			// Add the facts in $global_facts that should not show
			$skiptagsged = $skiptags;
    		foreach ($global_facts as $gfact => $gvalue) {
	    		if (isset($gvalue["show"])) {
		    		if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
	    		}
  		  	}
			foreach ($this->srepolist as $key => $repo) {
				$newged = $repo->gedcomid;
				$key = SplitKey($key, "id");
				if ($curged != $newged) {
					SwitchGedcom($newged);
					$curged = $newged;
					// Recalculate the tags to skip
					$skiptagsged = $skiptags;
					foreach ($global_facts as $gfact => $gvalue) {
			    		if (isset($gvalue["show"])) {
	    					if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
    					}
				  	}
				}
		    	$this->found = false;
		    	$this->hit = false;
		    	
		    	// First check if the hit is in the key.
				$this->CheckKeyHits($repo->key);
				
	    		// Check all records of the source
				if ($this->found == false) {
					$this->CheckTagHits($repo, $skiptagsged);
				}
				
				if ($this->found == true) {
					$this->printrepo[] = $repo->key;
					$this->repo_printed[$repo->key] = "1";
				}
				else if ($this->hit == true) $this->repo_hide[$repo->key] = 1;
			}
		}
		
		// Get the media items to be printed
		$ctr = count($this->smedialist);
		if ($ctr > 0) {
			uasort($this->smedialist, "TitleObjSort"); 
			$oldged = GedcomConfig::$GEDCOMID;
			$curged = GedcomConfig::$GEDCOMID;

			// Add the facts in $global_facts that should not show
			$skiptagsged = $skiptags;
    		foreach ($global_facts as $gfact => $gvalue) {
	    		if (isset($gvalue["show"])) {
		    		if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
	    		}
  		  	}
			foreach ($this->smedialist as $key => $media) {
				$newged = $media->gedcomid;
				$key = SplitKey($key, "id");
				if ($curged != $newged) {
					SwitchGedcom($newged);
					$curged = $newged;
					// Recalculate the tags to skip
					$skiptagsged = $skiptags;
					foreach ($global_facts as $gfact => $gvalue) {
			    		if (isset($gvalue["show"])) {
	    					if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
    					}
				  	}
				}
		    	$this->found = false;
		    	$this->hit = false;
		    	
		    	// First check if the hit is in the key.
				$this->CheckKeyHits($media->key);
				
	    		// Check all records of the media
				if ($this->found == false) {
					$this->CheckTagHits($media, $skiptagsged);
				}
				if ($this->found == true) {
					$this->printmedia[] = $media->key;
					$this->media_printed[$media->key] = "1";
				}
				else if ($this->hit == true) $this->media_hide[$media->key] = 1;
			}
		}
		
		// Print the notes
		$ctn = count($this->snotelist);
		$oldged = GedcomConfig::$GEDCOMID;
		$curged = GedcomConfig::$GEDCOMID;
		if ($ctn>0) {
			// Add the facts in $global_facts that should not show
			$skiptagsged = $skiptags;
    		foreach ($global_facts as $gfact => $gvalue) {
	    		if (isset($gvalue["show"])) {
		    		if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
	    		}
  		  	}
			foreach ($this->snotelist as $key => $note) {
				if ($note->gedcomid != GedcomConfig::$GEDCOMID) {
					SwitchGedcom($note->gedcomid);
					$skiptagsged = $skiptags;
					foreach ($global_facts as $gfact => $gvalue) {
			    		if (isset($gvalue["show"])) {
	    					if (($gvalue["show"] < $userlevel)) $skiptagsged .= ", ".$gfact;
    					}
				  	}
				}
		    	$this->found = false;
		    	$this->hit = false;
		    	// First check if the hit is in the key!
		    	if ($this->tagfilter == "off") {
					foreach ($this->cquery["includes"] as $qindex => $squery) {
			    		if (strpos(Str2Upper($note->xref), Str2Upper($squery["term"])) !== false) {
				    		$this->found = true;
				    		$this->hit = true;
				    		break;
		    			}
		    		}
	    		}
	    		// We must check the text that is in the note here, because it's at level 0 and not in the subrecs
	    		$text = Str2Upper($note->text);
				foreach ($this->cquery["includes"] as $qindex => $squery) {
			    	if (strpos($text, Str2Upper($squery["term"])) !== false || Str2Upper($squery["term"]) == "NOTE") {
				   		$this->found = true;
				   		$this->hit = true;
				   		break;
		    		}
		    	}
				if ($this->found == false) {
					$recs = GetAllSubrecords($note->gedrec, $skiptagsged, false, false, false);
					// Also levels>1 must be checked for tags. This is done below.
					foreach ($recs as $keysr => $subrec) {
						// We must remember the level 1 tag to check later. This was we can eliminate 
						// hits in 2 DATE which are valid, while we actually excluded hits in 1 CHAN.
						$ct = preg_match("/\d\s(\S*)\s*.*/i", $subrec, $level1tag);
						$level1tag = $level1tag[1];
						$recs2 = preg_split("/\r?\n/", $subrec);
						foreach ($recs2 as $keysr2 => $subrec2) {
							// There must be a hit in a subrec. If found, check in which tag
							foreach ($this->cquery["includes"] as $qindex => $squery) {
								if (preg_match("/".$squery["term"]."/i",$subrec2, $result)>0) {
									$ct = preg_match("/\d.(\S*).*/i",$subrec2, $result2);
									if (($ct > 0) && (!empty($result2[1]))) {
										$this->hit = true;
										// if the tag can be displayed, do so
										if (strpos($skiptagsged, $result2[1]) === false && strpos($skiptagsged, $level1tag) === false) $this->found = true;
									}
								}
								if ($this->found == true) break;
							}
							if ($this->found == true) break;
						}
						if ($this->found == true) break;
					}
				}
				if ($this->found == true) {
					$this->printnote[] = $note->key;
					$this->note_printed[$note->key] = "1";
				}
				else if ($this->hit == true) $this->note_hide[$note->key] = 1;
			}
			SwitchGedcom();
		}
	}
	
	public function SearchAddAssos() {
		
		if (is_null($this->assolist)) $this->assolist = ListFunctions::GetAssoList();
		
		// Step 1: Pull the relations to the printed results from the assolist
		$toadd = array();
		foreach ($this->assolist as $p1key => $assos) {
			// Get the person who might be a relation
			// Check if we can show him/her
			SwitchGedcom(SplitKey($p1key, "gedid"));
			foreach ($assos as $key => $asso) {
				$p2key = $asso->key2;
				// Design choice: we add to->from and from->to
				// Check if he/she  and the persons/fams he/she is related to, actually exist
				// Also respect name privacy for all related individuals. If one is hidden, it prevents the relation to be displayed
				// p1key can be either indi or fam, p2key can only be indi.
				if (array_key_exists($p2key, $this->indi_printed) || array_key_exists($p1key, $this->indi_printed) || array_key_exists($p1key, $this->fam_printed)) {
					// p2key is always an indi, so check this first
					// save his relation to existing search results
					if ($asso->disp) {
//						$toadd[$p1key][] = array($p2key, "indi", $asso->fact, $asso->role);
//						$toadd[$p2key][] = array($p1key, $asso->type, $asso->fact, $asso->role);
						$toadd[$p1key][] = $asso;
						$toadd[$p2key][] = $asso;
					}
				}
			}
		}
		// Step 2: Add the relations who are not printed themselves
		foreach ($toadd as $add => $assos) {
			foreach ($assos as $key => $asso) {
				if ($asso->type == "INDI") {
					if (!array_key_exists($asso->key1, $this->indi_printed)) {
						$this->indi_printed[$asso->key1] = "1";
						$this->sindilist[$asso->associated->key] = $asso->associated;
				    	foreach($asso->associated->name_array as $indexval => $namearray) {
							$this->printindiname[] = array(NameFunctions::SortableNameFromName($namearray[0]), $asso->associated->xref, $asso->associated->gedcomid, "", $indexval);
						}
					}
				}
				else {
					if (!array_key_exists($asso->key1, $this->fam_printed)) {
						$this->fam_printed[$asso->key1] = "1";
						$this->sfamlist[$asso->associated->key] = $asso->associated;
						foreach ($asso->associated->allnames as $namekey => $famname) {
							$famsplit = preg_split("/(\s\+\s)/", trim($famname));
							// Both names have to have the same direction and combination of chinese/not chinese
							if (hasRTLText($famsplit[0]) == hasRTLText($famsplit[1]) && NameFunctions::HasChinese($famsplit[0], true) == NameFunctions::HasChinese($famsplit[1], true)) {
								$this->printfamname[]=array(NameFunctions::CheckNN($famname), $asso->associated->xref, $asso->associated->gedcomid, "", $namekey);
							}
						}
					}
				}
				if (!array_key_exists($asso->key2, $this->indi_printed)) {
					$this->indi_printed[$asso->key2] = "1";
					$this->sindilist[$asso->assoperson->key] = $asso->assoperson;
				    foreach($asso->assoperson->name_array as $indexval => $namearray) {
						$this->printindiname[] = array(NameFunctions::SortableNameFromName($namearray[0]), $asso->assoperson->xref, $asso->assoperson->gedcomid, "", $indexval);
					}
				}
			}
		}
		// Step 3: now cycle through the indi search results to add a relation link
		foreach ($this->printindiname as $pkey => $printindi) {
			$pikey = JoinKey($printindi[1], get_id_from_gedcom($printindi[2]));
			if (isset($toadd[$pikey])) {
				foreach ($toadd[$pikey] as $rkey => $asso) {
					SwitchGedcom($printindi[2]);
					$this->printindiname[$pkey][3][] = $asso;
				}
			}
		}
		// Step 4: now cycle through the fam search results to add a relation link
		foreach ($this->printfamname as $pkey => $printfam) {
			$pikey = JoinKey($printfam[1], get_id_from_gedcom($printfam[2]));
			if (isset($toadd[$pikey])) {
				foreach ($toadd[$pikey] as $rkey => $asso) {
					SwitchGedcom($printfam[2]);
					$this->printfamname[$pkey][3][] = $asso;
				}
			}
		}
	
		SwitchGedcom();
	}

	private function CheckKeyHits($key) {
		
		foreach($this->cquery["includes"] as $qindex => $squery) {
			if (strpos(Str2Upper($key), Str2Upper($squery["term"])) !== false) {
				// We have a hit in the key
				$this->hit = true; 
				// If it is not filtered, we have a result.
				if ($this->tagfilter == "off") $this->found = true; 
				break;
			}
		}
	}
	
	private function CheckTagHits(&$object, $skiptagsged) {
		
		$recs = GetAllSubrecords($object->gedrec, "", false, false, false);
		// Also levels>1 must be checked for tags. This is done below.
		foreach ($recs as $keysr => $subrec) {
			// We must remember the level 1 tag to check later. This was we can eliminate 
			// hits in 2 DATE which are valid, while we actually excluded hits in 1 CHAN.
			$ct = preg_match("/\d\s(\S*)\s*.*/i", $subrec, $level1tag);
			$level1tag = $level1tag[1];
			$recs2 = preg_split("/\r?\n/", $subrec);
			foreach ($recs2 as $keysr2 => $subrec2) {
				// There must be a hit in a subrec. If found, check in which tag
				foreach ($this->cquery["includes"] as $qindex => $squery) {
					if (preg_match("/\b".$squery["term"]."/i", $subrec2, $result) > 0) {
						// There is a hit in a subrec
						// Now get the tag
						$ct = preg_match("/\d\s(\S*)\s*.*/i", $subrec2, $result2);
						if (($ct > 0) && (!empty($result2[1]))) {
							// we found the tag of the subrecord where the hit was
//							print "Hit in".$subrec2."<br />leveltag: ".$level1tag."<br />tag:".$result2[1]."<br />";
							$this->hit = true;
							// if the tag can be displayed, do so
							// but first check if the hit is in a link to a hidden record
							if (strpos($skiptagsged, $result2[1]) === false && strpos($skiptagsged, $level1tag) === false) {
								// Step 1: passed for checking both tags agains skiptags
								$ct9 = preg_match("/\d\s\w+\s@(.+)@/", $subrec2, $match9);
								// Step 2: Check if the record is a link to another record
								if ($ct9) {
									$rtype = "";
									if (in_array($result2[1], array("CHIL", "HUSB", "WIFE", "ASSO"))) $rtype = "INDI";
									else if (in_array($result2[1], array("FAMC", "FAMS"))) $rtype = "FAM";
									else if ($result2[1] == "SOUR") $rtype = "SOUR";
									else if ($result2[1] == "REPO") $rtype = "REPO";
									else if ($result2[1] == "NOTE") $rtype = "NOTE";
									else $rtype = "OBJE";
//									print "Check ".$match9[1]." leveltag ".$result2[1]." type ".$rtype." subrec ".$subrec2." for display<br />";
									$obj = ConstructObject($match9[1], $rtype, $object->gedcomid);
									// If we can display the name of the record, we have a valid hit.
									// Also check if an object is returned, we might have found two @ signs with some garbage in between.
									// If not an object, it was just a string, and we did find a hit but not a link.
									if (is_object($obj)) {
										if ($obj->disp_name) {
											$this->found = true;
											break;
										}
									}
									else $this->found = true;
								}
								// Hit found in subrec, no link
								else $this->found = true;
							}
						}
					}
					if ($this->found == true) break;
				}
				if ($this->found == true) break;
			}
			if ($this->found == true) break;
		}
	}
	
	private function GetSoundexResults() {
		global $GEDCOMS;
		
		// Write the search action to the logfile
		$logstring = "Soundex, ";
		if (!is_null($this->lastname)) $logstring .= "Last name: ".$this->lastname."<br />";
		if (!is_null($this->firstname)) $logstring .= "First name: ".$this->firstname."<br />";
		if (!is_null($this->place)) $logstring .= "Place: ".$this->place."<br />";
		if (!is_null($this->year)) $logstring .= "Year: ".$this->year."<br />";
		WriteToLog($logstring, "I", "F", $this->searchgeds);
		
    	if (!is_null($this->lastname)) {
	    	// see if there are brackets around letter(groups)
	    	$bcount = substr_count($this->lastname, "[");
	    	// Open and close must be equal and > 0
	    	if (($bcount == substr_count($this->lastname, "]")) && $bcount > 0) {
		    	$barr = array();
			    $ln = $this->lastname;
			    $pos = 0;
			    $npos = 0;
		    	for ($i=0; $i<$bcount; $i++) {
					$pos1 = strpos($ln, "[")+1;
					$pos2 = strpos($ln, "]");
					$barr[$i] = array(substr($ln, $pos1, $pos2-$pos1), $pos1+$npos-1, $pos2-$pos1);
					$npos = $npos + $pos2 - 1;
					$ln = substr($ln, $pos2+1);
				}
				// Then strip the brackets so we can search
				$this->lastname = preg_replace(array("/\[/", "/\]/"), array("",""), $this->lastname);
			}
		}
		
		if (!is_null($this->year)) {
			// We must be able to search regexp in the DB for years
	    	if (strlen($this->year) == 1) $this->year = preg_replace(array("/\?/", "/\|/", "/\*/"), array("\\\?","\\\|", "\\\\\*") , $this->year);
			$this->year = preg_replace(array("/\s+/", "/\(/", "/\)/"), array(".*",'\(','\)'), $this->year);
		}
			
		if ($this->soundex == "DaitchM") NameFunctions::DMsoundex("", "opencache");

		// Do some preliminary stuff: determine the soundex codes for the search criteria
		if (!is_null($this->lastname)) {
			$orglastname = $this->lastname;
			if (NameFunctions::HasChinese($this->lastname, true)) $this->lastname = NameFunctions::GetPinYin($this->lastname, true);
			else if (NameFunctions::HasCyrillic($this->lastname, true)) $this->lastname = NameFunctions::GetTransliterate($this->lastname, true);
			$lastnames = preg_split("/\s/", trim($this->lastname));
			$larr = array();
			for($j=0; $j<count($lastnames); $j++) {
				if ($this->soundex == "Russell") $larr[$j] = soundex($lastnames[$j]);
				if ($this->soundex == "DaitchM") $larr[$j] = NameFunctions::DMsoundex($lastnames[$j]);
			}
		}
		if (!is_null($this->firstname)) {
			$orgfirstname = $this->firstname;
			if (NameFunctions::HasChinese($this->firstname, true)) $this->firstname = NameFunctions::GetPinYin($this->firstname, true);
			else if (NameFunctions::HasCyrillic($this->firstname, true)) $this->firstname = NameFunctions::GetTransliterate($this->firstname, true);
			$firstnames = preg_split("/\s/", trim($this->firstname));
			$farr = array();
			for($j=0; $j<count($firstnames); $j++) {
				if ($this->soundex == "Russell") $farr[$j] = soundex($firstnames[$j]);
				if ($this->soundex == "DaitchM") $farr[$j] = NameFunctions::DMsoundex($firstnames[$j]);
			}
		}
		$parr = array();
		if (!is_null($this->place)) {
			if (NameFunctions::HasChinese($this->place, true)) $this->place = NameFunctions::GetPinYin($this->place, true);
			else if (NameFunctions::HasCyrillic($this->place, true)) $this->place = NameFunctions::GetTransliterate($this->place, true);
			if ($this->place != "" && $this->soundex == "DaitchM") $parr = NameFunctions::DMsoundex($this->place);
			if ($this->place != "" && $this->soundex == "Russell") $parr = soundex(trim($this->place));
		}
		// Start the search
		$oldged = GedcomConfig::$GEDCOMID;

		// Build the query
		$sql = "SELECT DISTINCT n_id, i_key, i_id, i_gedrec, i_file, i_isdead, n_name, n_surname, n_nick, n_type, n_letter, n_fletter FROM ";

		if (isset($farr)) $sql .= TBLPREFIX."soundex as s1, ";
		if (isset($larr)) $sql .= TBLPREFIX."soundex as s2, ";
		$sql .= TBLPREFIX."individuals, ".TBLPREFIX."names WHERE i_key=n_key AND ";
		
		// Process the firstname parts
		if (isset($farr)) {
			if (count($this->searchgeds) != count($GEDCOMS)) {
				$sql .= " (";
				$i = 0;
				foreach ($this->searchgeds as $key => $gedcomid) {
					if ($i != 0) $sql .= " OR ";
					$i++;
					$sql .= "s1.s_file='".$gedcomid."'";
				}
				$sql .= ") AND";
			}
		
			$sql .= " i_key=s1.s_gid AND ";
			if ($this->soundex == "Russell") $sql .= "s1.s_type='R'";
			else $sql .= "s1.s_type='D'";
			$sql .= " AND s1.s_nametype='F' AND (";
			$i = 0;
			foreach ($farr as $key => $code) {
				if ($this->soundex == "Russell") {
					if ($i > 0) $sql .= " OR ";
					$i++;
					$sql .= "s1.s_code LIKE '".$code."'";
				}
				else {
					foreach ($code as $key2 => $value) {
						if ($i > 0) $sql .= " OR ";
						$i++;
						$sql .= "s1.s_code LIKE '".$value."'";
					}
				}
			}
			$sql .= ")";
		}
		
		// Process the last name parts
		if (isset($larr)) {
			if (isset($farr)) $sql .= " AND i_key=s2.s_gid AND s1.s_type=s2.s_type AND s2.s_nametype='L' AND (";
			else {
				if (count($this->searchgeds) != count($GEDCOMS)) {
					$sql .= " (";
					$i = 0;
					foreach ($this->searchgeds as $key => $gedcomid) {
						if ($i != 0) $sql .= " OR ";
						$i++;
						$sql .= "s2.s_file='".$gedcomid."'";
					}
					$sql .= ") AND";
				}
				$sql .= " i_key=s2.s_gid AND s2.s_nametype='L' AND (";
			}
			$i = 0;
			foreach ($larr as $key => $code) {
				if ($this->soundex == "Russell") {
					if ($i > 0) $sql .= " OR ";
					$i++;
					if (isset($farr)) $sql .= "s2.s_code LIKE '".$code."'";
					else $sql .= "s2.s_code LIKE '".$code."'";
				}
				else {
					foreach ($code as $key2 => $value) {
						if ($i > 0) $sql .= " OR ";
						$i++;
						if (isset($farr)) $sql .= "s2.s_code LIKE '".$value."'";
						else $sql .= "s2.s_code LIKE '".$value."'";
					}
				}
			}
		$sql .= ")"; 
		}
		$sql .= " ORDER BY i_key, n_id";
	
		$res = NewQuery($sql);		
		if ($res) {
			$key = "";
			while($row = $res->FetchAssoc()){
				if ($key != $row["i_key"]) {
					if ($key != "") $person->names_read = true;
					$person = null;
					$key = $row["i_key"];
					$person =& Person::GetInstance($row["i_id"], $row, $row["i_file"]);
					$this->sindilist[$row["i_key"]] = $person;
				}
				$this->sindilist[$row["i_key"]]->addname = array($row["n_name"], $row["n_letter"], $row["n_surname"], $row["n_nick"], $row["n_type"], $row["n_fletter"]);
			}
			if ($key != "") $person->names_read = true;
			$res->FreeResult();
		}
		
		// -- Check the result on places
		foreach ($this->sindilist as $key => $indi) {
			$save = true;
			if (!empty($this->place) || !empty($this->year)) {
				$indirec = $indi->gedrec;
				if (!empty($this->place)) {
					$savep = false;
					$pt = preg_match_all("/\d PLAC (.*)/i", $indirec, $match, PREG_PATTERN_ORDER );
					if ($pt>0) {
						$places = array();
						for ($pp=0; $pp<count($match[1]); $pp++){
							// Split on chinese comma 239 188 140
							$match[1][$pp] = preg_replace("/".chr(239).chr(188).chr(140)."/", ",", $match[1][$pp]);
							$places[$pp] =preg_split("/,\s/", trim($match[1][$pp]));
						}
						$cp = count($places);
						$p = 0;
						while($p < $cp && $savep == false) {
							$pp = 0;
							while($pp < count($places[$p]) && $savep == false) {
								if (NameFunctions::HasChinese($places[$p][$pp])) $pl = NameFunctions::GetPinYin(trim($places[$p][$pp]));
								else if (NameFunctions::HasCyrillic($places[$p][$pp])) $pl = NameFunctions::GetTransliterate(trim($places[$p][$pp]));
								else $pl = trim($places[$p][$pp]);
								if ($this->soundex == "Russell") {
									if (soundex($pl) == $parr) $savep = true;
								}
								if ($this->soundex == "DaitchM") {
									$arr1 = NameFunctions::DMsoundex($pl);
									$a = array_intersect($arr1, $parr);
									if (!empty($a)) $savep = true;
								}
								$pp++;
							}
							$p++;
						}
					}
					if ($savep == false) $save = false;
				}
				// Check the result on years
				if (!empty($this->year) && $save == true) {
					$yt = preg_match("/\d DATE (.*$this->year.*)/i", $indirec, $match);
					if ($yt == 0) $save = false;
				}
			}
			// Add either all names or names with a hit
			if ($save === true) {
				if ($this->nameprt == "all") {
					foreach($indi->name_array as $indexval => $namearray) {
						$this->printindiname[] = array(NameFunctions::SortableNameFromName($namearray[0]), $indi->xref, $indi->gedcomid, "", $indexval);
					}
					$this->indi_printed[$indi->key] = true;
				}
				else {
					foreach($indi->name_array as $indexval => $namearray) {
						$printl = false;
						$name = explode("/", $namearray[0]);
						if ($this->lastname != "" && (NameFunctions::HasChinese($orglastname, true) == NameFunctions::HasChinese($name[1], true) || NameFunctions::HasCyrillic($orglastname, true) == NameFunctions::HasCyrillic($name[1], true))) {
							if (NameFunctions::HasChinese($name[1], true)) $name[1] = NameFunctions::GetPinYin($name[1], true);
							else if (NameFunctions::HasCyrillic($name[1], true)) $name[1] = NameFunctions::GetTransliterate($name[1], true);
							$lnames = preg_split("/\s/", trim($name[1]));
							foreach ($lnames as $namekey => $namepart) {
								if ($this->soundex == "DaitchM") {
									$lndm = NameFunctions::DMSoundex($namepart);
									foreach($larr as $arrkey => $arrvalue) {
										$a = array_intersect($lndm, $arrvalue);
										if (!empty($a)) {
											$printl = true;
											break;
										}
									}
								}
								else {
									if (in_array(soundex($namepart), $larr)) $printl = true;
								}
								if ($printl) break;
							}
						}
						// If not searching on lastname or comparing different ords, this step is passed
						else $printl = true;
						
						$printf = false;
						if ($this->firstname != "" && $printl == true  && (NameFunctions::HasChinese($orgfirstname, true) == NameFunctions::HasChinese($name[0], true) || NameFunctions::HasCyrillic($orgfirstname, true) == NameFunctions::HasCyrillic($name[0], true))) {
							if (NameFunctions::HasChinese($name[0], true)) $name[0] = NameFunctions::GetPinYin($name[0], true);
							else if (NameFunctions::HasCyrillic($name[0], true)) $name[0] = NameFunctions::GetTransliterate($name[0], true);
							$fnames = preg_split("/\s/", trim($name[0]));
							foreach ($fnames as $namekey => $name) {
								if ($this->soundex == "DaitchM") {
									$fndm = NameFunctions::DMSoundex($name);
									foreach($farr as $arrkey => $arrvalue) {
										$a = array_intersect($fndm, $arrvalue);
										if (!empty($a)) {
											$printf = true;
											break;
										}
									}
								}
								else {
									if (in_array(soundex($name), $farr)) $printf = true;
								}
								if ($printf) break;
							}
						}
						// If not searching on firstname or comparing different ords, this step is passed
						else $printf = true;
						
						if ($printl && $printf) {
							$this->printindiname[] = array(NameFunctions::SortableNameFromName($namearray[0]), $indi->xref, $indi->gedcomid, "", $indexval);
							$this->indi_printed[$indi->key] = true;
						}
					}
				}
			}
		}
		NameFunctions::DMSoundex("", "closecache");
		SwitchGedcom();
		// check the result on required characters
		if (isset($barr)) {
			foreach ($this->printindiname as $pkey=>$pname) {
				$print = true;
				foreach ($barr as $key=>$checkchar) {
					if (Str2Upper(substr($pname[0], $checkchar[1], $checkchar[2])) != Str2Upper($checkchar[0])) {
						$print = false;
						break;
					}
				}
				if ($print == false) {
					unset($this->indi_printed[JoinKey($this->printindiname[$pkey][1], $this->printindiname[$pkey][2])]);
					unset($this->printindiname[$pkey]);
				}
			}
		}
		// Now we have the final list of indi's to be printed.
		// We may add the assos at this point.
		if ($this->showasso == "on") $this->SearchAddAssos();
		//-- if only 1 item is returned, automatically forward to that item
		if (count($this->printindiname) == 1) {
			SwitchGedcom($this->printindiname[0][2]);
			$person = Person::GetInstance($this->printindiname[0][1], "", $this->printindiname[0][2]);
			if ($person->disp_name) {
				header("Location: individual.php?pid=".$person->xref."&gedid=".$person->gedcomid);
				exit;
			}
			SwitchGedcom();
		}
		if ($this->sorton == "last" || $this->sorton == "") uasort($this->printindiname, "ItemSort");
		else uasort($this->printindiname, "FirstnameSort");
		if (count($this->printfamname) > 0)	uasort($this->printfamname, "ItemSort");
	}
}
?>