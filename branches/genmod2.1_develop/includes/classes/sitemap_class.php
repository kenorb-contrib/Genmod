<?php
/**
 * Class file for sitemaps
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
 * @version $Id: sitemap_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class SiteMap {
	
	// General class information
	public $classname = "SiteMap";	// The name of this class
	
	// Data
	private $sitemapfilename = "sitemap.xml";	// Filename the sitemap should be stored with (gedcom ID will append)
	private $xmlheader = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
											// Header lines for each site map
	private $xmlfooter = "</urlset>\n";		// Footer lines for each site map
	private $urlheader = "<url>\n<loc>\n";	// Tags before each URL
	private $urlfooter = "</loc>\n</url>\n";// Tags after each URL
	private $location = "";					// The URL of each page
	private $gedcomid = "";					// Gedcom ID the current sitemap refers to
	private $sitemap = array();				// array to store the items for the sitemap
	private $types = array();				// Types of records to generate a sitemap for
	private $list = array();				// Used to hold the list with objects
	private $added = false;					// keep track whether anything is added

	public function __construct($types=array("INDI")) {
		global $GEDCOMS;
		
		if (is_array($types)) $this->types = $types;
		$this->sitemapfilename = INDEX_DIRECTORY . $this->sitemapfilename;
		$this->sitemap[] = $this->xmlheader;
		foreach ($GEDCOMS as $key => $ged) {
			$this->gedcomid = $ged["id"];
			SwitchGedcom($this->gedcomid);
			if (GedcomConfig::$INCLUDE_IN_SITEMAP) {
				foreach($this->types as $key => $type) {
					switch ($type) {
						case "INDI" :
							$this->list = ListFunctions::GetIndiList(array($this->gedcomid),"", true);
							$this->AddIDs($type);
							break;
						case "FAM" :
							$this->list = ListFunctions::GetFamList(array($this->gedcomid),"", true);
							$this->AddIDs($type);
							break;
						case "REPO" :
							break;
						case "SOUR" :
							break;
					}
				}
			}
		}
		$this->sitemap[] = $this->xmlfooter;
		WriteToLog("Sitemap-class-&gt; ".GM_LANG_sitemap_export_success, "I", "S");
	}
	
	public function __get($property) {
		switch ($property) {
			case "sitemap":
				return implode("", $this->sitemap);
				break;
			case "hascontent":
				return $this->added;
				break;
			default:
				PrintGetSetError($property, get_class($this), "get");
				break;
		}
	}
	
	private function AddIDs($type) {

		if ($type == "INDI") {
			$page = "individual";
			$id = "pid";
		}
		if ($type == "FAM") {
			$page = "family";
			$id = "famid";
		}
		
		foreach($this->list as $key2 => $object) {
			if ($object->disp) {
				$this->added = true;
				$this->sitemap[] = $this->urlheader;
				$this->sitemap[] = htmlspecialchars(SERVER_URL . $page . ".php?" . $id . "=" . $object->xref . "&gedid=" . $object->gedcomid) . "\n";
				$this->sitemap[] = $this->urlfooter;
			}
		}
	}
}
?>