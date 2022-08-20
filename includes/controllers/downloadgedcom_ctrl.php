<?php
/**
 * Allow an admin user to download the entire gedcom	file.
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
 * @subpackage Admin
 * @version $Id: downloadgedcom_ctrl.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

class DownloadGedcomController extends BaseController {
	
	public $classname = "DownloadGedcomController";	// Name of this class
	private $remove = "no";							// Remove custom tags from the downloaded gedcom (yes/<not set>)
	private $convert = "";							// Convert the download to ANSI (yes/<not set>)
	private $zip = "no";							// Download in zipped format (yes/<not set>)
	private $privatize_export = "";					// Apply a privacy filter to the exported gedcom (yes/<not set>)
	private $privatize_export_level = "";			// Level of privacy to apply (visitor, user, gedadmin, siteadmin)
	private $embedmm = "";							// Replace MM links with embedded data
	private $embednote = "";						// Replace note links with embedded data
	private $addaction = "";						// Add actions to the gedcom
	
	public function __construct() {
		
		parent::__construct();
		
		if (isset($_REQUEST['convert'])) $this->convert = $_REQUEST['convert'];
		if (isset($_REQUEST['remove'])) $this->remove = $_REQUEST['remove'];
		if (isset($_REQUEST['embedmm'])) $this->embedmm = $_REQUEST['embedmm'];
		if (isset($_REQUEST['embednote'])) $this->embednote = $_REQUEST['embednote'];
		if (isset($_REQUEST['addaction'])) $this->addaction = $_REQUEST['addaction'];
		if (isset($_REQUEST['zip'])) $this->zip = $_REQUEST['zip'];
		if (isset($_REQUEST['privatize_export'])) $this->privatize_export = $_REQUEST['privatize_export'];
		if (isset($_REQUEST['privatize_export_level'])) $this->privatize_export_level = $_REQUEST['privatize_export_level'];
		if (isset($_REQUEST['gedid'])) $this->gedcomid = $_REQUEST['gedid'];
	}
	
	public function __get($property) {
		switch ($property) {
			case "zip":
				return $this->zip;
				break;
			case "convert":
				return $this->convert;
				break;
			case "remove":
				return $this->remove;
				break;
			case "privatize_export":
				return $this->privatize_export;
				break;
			case "privatize_export_level":
				return $this->privatize_export_level;
				break;
			case "embedmm":
				return $this->embedmm;
				break;
			case "embednote":
				return $this->embednote;
				break;
			case "addaction":
				return $this->addaction;
				break;
			default:
				return parent::__get($property);
				break;
		}
	}
	
	protected function GetPageTitle() {
		
		return GM_LANG_download_gedcom;
	}	
		
	public function DownloadZip() {
		global $gm_user;
		
		require('includes/pclzip.lib.php');
		$zipfile = INDEX_DIRECTORY."dl".adodb_date("YmdHis").".zip";
		$gedname = INDEX_DIRECTORY."DL_".get_gedcom_from_id($this->gedcomid);
		if (file_exists($gedname)) unlink($gedname);
		AdminFunctions::PrintGedcom($this->gedcomid, $this->convert, $this->remove, $this->zip, $this->privatize_export, $this->privatize_export_level, $gedname, $this->embedmm, $this->embednote, $this->addaction);
		$archive = new PclZip($zipfile);
		$v_list = $archive->create($gedname, PCLZIP_OPT_COMMENT, "Created by Genmod ".GM_VERSION." ".GM_VERSION_RELEASE." on ".adodb_date("r").".");
		if ($v_list == 0) print "Error : ".$archive->errorInfo(true);
		else {
			unlink($gedname);
			$fname = $zipfile;
			include('downloadbackup.php');
		}
	}
}
?>