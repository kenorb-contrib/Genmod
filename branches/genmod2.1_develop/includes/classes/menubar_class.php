<?php
/**
 * System for generating menus.
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
 * $Id: menubar_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 * @package Genmod
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

abstract class MenuBar {
	
	// General class information
	public $classname = "MenuBar";	// Name of the class
	
	/**
	 * Create text for sub-menu where one will be checked and others not
	 * @return Text         the sub-menu text
	 */
	private function GetSubmenuText($textIn, $selected=false) {
		global $GM_IMAGES;
		
		$displayImage = (file_exists(GM_IMAGE_DIR."/".$GM_IMAGES["check"]["other"]) && file_exists(GM_IMAGE_DIR."/".$GM_IMAGES["nocheck"]["other"]));
		//$displayImage=false;
		if ($selected == true) {
			if ($displayImage == true) {
				return "<img src='".GM_IMAGE_DIR."/".$GM_IMAGES["check"]["other"]."' height='12' witdh='12' alt='' />".$textIn;
			}
			else {
				return "<i>".$textIn."</i>";
			}
		}
		else {
			if ($displayImage == true) {
				return "<img src='".GM_IMAGE_DIR."/".$GM_IMAGES["nocheck"]["other"]."' height='12' width='12' alt='' />".$textIn;
			}
			else {
				return $textIn;
			}
		}
	}
	/**
	 * Get the links to the custom pages created by the user
	 * @return Menu		the menu item
	 */
	public function GetCustomMenu() {
		global $gm_user, $DBCONN;
		
		// Retrieve the current pages stored in the DB
		$sql = "SELECT * FROM ".TBLPREFIX."pages";
		$result = NewQuery($sql);
		$pages = array();
		while ($row = $result->FetchAssoc()) {
			$pages[$row["pag_id"]] = new CustomPage($row);
		}
			
		//-- My Pages
		if (count($pages) > 0) {
			$menu = new Menu(GM_LANG_my_pages);
			foreach ( $pages as $key => $page) {
				$submenu = new Menu($page->title, "");
				$submenu->addLink("custompage.php?action=show&page_id=".$page->id);
				$menu->addSubmenu($submenu);
			}
		}
		else $menu = "";
		if ($gm_user->userIsAdmin()) {
			if (!is_object($menu)) $menu = new Menu(GM_LANG_my_pages);
			$submenu = new Menu(GM_LANG_edit_pages, "");
			$submenu->addLink("custompage.php?action=edit");
			$menu->addSubmenu($submenu);
		}
		return $menu;
	}

	/**
	 * Create the File menu
	 * @return Menu		the menu item
	 */
	public function GetFileMenu() {
		global $QUERY_STRING;
		global $GEDCOMS, $gm_user;
		

		//-- main file menu item
		$menu = new Menu(GM_LANG_menu_file);
		
		// NOTE: Login link
		if (empty($gm_user->username)) {
			$submenu = new Menu(GM_LANG_login);
			if (!LOGIN_URL == "") $submenu->addLink(LOGIN_URL."?url=".urlencode(basename(SCRIPT_NAME)."?".$QUERY_STRING."&gedid=".GedcomConfig::$GEDCOMID));
			else $submenu->addLink(SERVER_URL."login.php?url=".urlencode(basename(SCRIPT_NAME)."?".$QUERY_STRING."&gedid=".GedcomConfig::$GEDCOMID));
			$menu->addSubmenu($submenu);
		}
		
		// NOTE: Open GEDCOM
		if (SystemConfig::$ALLOW_CHANGE_GEDCOM && count($GEDCOMS)>1) {
			$submenu = new Menu(GM_LANG_menu_open);
			$menu->addSubmenu($submenu);
		
		// NOTE: Add GEDCOMS to open
			foreach($GEDCOMS as $gedid => $gedarray) {
				$submenu = new Menu(self::GetSubmenuText(PrintReady($gedarray["title"]), ($gedid == GedcomConfig::$GEDCOMID)), false);
				$submenu->addLink("index.php?command=gedcom&gedid=".$gedid);
				$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
			}
		}
		
		// NOTE: Admin link
		if ($gm_user->canadmin || ($gm_user->userGedcomAdmin(GedcomConfig::$GEDCOMID))) {
			$submenu = new Menu(GM_LANG_admin);
			$submenu->addLink("admin.php");
			$menu->addSubmenu($submenu);
		}
		
		// NOTE: User page
		$submenu = new Menu(GM_LANG_welcome_page);
		$submenu->addLink("index.php?command=gedcom");
		$menu->addSubmenu($submenu);
		
		// NOTE: User page
		$submenu = new Menu(GM_LANG_mgv);
		$submenu->addLink("index.php?command=user");
		$menu->addSubmenu($submenu);
		
		// NOTE: Print preview
		$submenu = new Menu(GM_LANG_print_preview);
		// TODO: Querystring contains htmlcode, kills the JS
		$submenu->addLink(basename(SCRIPT_NAME)."?view=preview&".htmlentities(GetQueryString()));
		$menu->addSubmenu($submenu);
		
		// NOTE: Logout link
		if ($gm_user->username != "") {
			$submenu = new Menu(GM_LANG_logout);
			$submenu->addLink("index.php?logout=1");
			$menu->addSubmenu($submenu);
		}
		
		return $menu;
	}

	public function GetEditMenu() {
		global $QUERY_STRING;
		global $ENABLE_CLIPPINGS_CART, $gm_user;
		
		//-- main edit menu item
		$menu = new Menu(GM_LANG_edit, "#");
		
		// Clippings menu
		if (file_exists("clippings.php") &&($ENABLE_CLIPPINGS_CART > $gm_user->getUserAccessLevel())) {
			//-- main clippings menu item
			$submenu = new Menu(GM_LANG_clippings_cart);
			$submenu->addLink("clippings.php");
			$menu->addSubmenu($submenu);
		}
				
		//-- search_general sub menu
		$submenu = new Menu(GM_LANG_search);
		$submenu->addLink("search.php");
		$menu->addSubmenu($submenu);
		
		if ($gm_user->editaccount) {
			$submenu = new Menu(GM_LANG_editowndata);
			$submenu->addLink("edituser.php");
			$menu->addSubmenu($submenu);
		}
		
		return $menu;
	}
	
	public function GetViewMenu() {
		global $gm_language, $language_settings;
		global $LANGUAGE;
		global $gm_user;
		
		//-- main edit menu item
		$menu = new Menu(GM_LANG_menu_view);
		
		// Language selector
		if (GedcomConfig::$ENABLE_MULTI_LANGUAGE) {
			// Change language
			$submenu = new Menu(GM_LANG_inc_languages);
			$menu->addSubmenu($submenu);
			
			// NOTE: Add languages available
			foreach ($gm_language as $key=>$value) {
				if ($language_settings[$key]["gm_lang_use"]) {
					$submenu = new Menu(self::GetSubmenuText(constant("GM_LANG_".$key), ($LANGUAGE == $key)), false);
					$submenu->addLink(SCRIPT_NAME."?changelanguage=yes&NEWLANGUAGE=".$key."&".htmlentities(GetQueryString()));
					$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
				}
			}
		}

		// Theme selector
		if (GedcomConfig::$ALLOW_THEME_DROPDOWN && SystemConfig::$ALLOW_USER_THEMES) {
			// Change theme
			$submenu = new Menu(GM_LANG_inc_themes);
			$menu->addSubmenu($submenu);

			isset($_SERVER["QUERY_STRING"]) == true?$tqstring = "?".$_SERVER["QUERY_STRING"]:$tqstring = "";
			$frompage = $_SERVER["SCRIPT_NAME"].$tqstring;
			if(isset($_REQUEST['mod'])){
				if(!strstr("?", $frompage))
				{
					if(!strstr("%3F", $frompage)) ;
					else $frompage.="?";
				}
				if(!strstr("&mod",$frompage))$frompage.="&mod=".$_REQUEST['mod'];
			}


			// NOTE: add themes
			$themes = GetThemeNames();
			foreach ($themes as $indexval => $themedir) {
				$submenu = new Menu(self::GetSubmenuText($themedir["name"], (($themedir["dir"] == $gm_user->theme)||(empty($gm_user->theme)&&($themedir["dir"] == GedcomConfig::$THEME_DIR)))), false);
//LERMAN - for some reason "...&amp;mytheme=..." does not work for Firefox 1.5.0.4 on Linux, but does work on IE. Changing it to "...&mytheme=..." works in both places
				$submenu->addLink("themechange.php?frompage=".urlencode($frompage)."&mytheme=".$themedir["dir"]);
				$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
			}
		}

		// Calendar menu
		$submenu = new Menu(GM_LANG_menu_calendar);
		$menu->addSubmenu($submenu);
		
		// Day Calendar
		$submenu = new Menu(GM_LANG_menu_calendar_day);
		$submenu->addLink("calendar.php");
		$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
		
		// Month Calendar
		$submenu = new Menu(GM_LANG_menu_calendar_month);
		$submenu->addLink("calendar.php?action=calendar");
		$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
		
		// Year Calendar
		$submenu = new Menu(GM_LANG_menu_calendar_year);
		$submenu->addLink("calendar.php?action=year");
		$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
		
		return $menu;
	}
	
	public function GetFavoritesMenu() {
		global $gm_user;
		
		// NOTE: User Favorites
		if ($gm_user->username != "") {
			$userfavs = FavoritesController::getUserFavorites($gm_user->username);
		}
		else {
			if (GedcomConfig::$MUST_AUTHENTICATE) return false;
			$userfavs = array();
		}
		
		if (count($userfavs) > 0) {
			$menu = new Menu(GM_LANG_menu_favorites);
			$submenu = new Menu(GM_LANG_my_favorites);
			$menu->addSubmenu($submenu);
			foreach($userfavs as $key => $favorite) {
				$submenu = new Menu($favorite->title, false);
				$submenu->addLink($favorite->link);
				$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
			}
		}
			
		// NOTE: Gedcom Favorites
		$gedcomfavs = FavoritesController::getGedcomFavorites(GedcomConfig::$GEDCOMID);
		if (count($gedcomfavs) > 0) {
			if (!isset($menu)) $menu = new Menu(GM_LANG_menu_favorites);
			$submenu = new Menu(GM_LANG_gedcom_favorites);
			$menu->addSubmenu($submenu);
			
			foreach($gedcomfavs as $key => $favorite) {
				$submenu = new Menu($favorite->title, false);
				$submenu->addLink($favorite->link);
				$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
			}
		}
		
		return (isset($menu) && is_object($menu) ? $menu : false);
	}
	
	/**
	 * get the menu for the charts
	 * @return Menu		the menu item
	 */
	public function GetChartsMenu($rootid='',$myid='') {
		global $gm_user;
		
		//-- main charts menu item
		$link = "pedigree.php";
		if ($rootid) {
			$link .= "?rootid=".$rootid;
			$menu = new Menu(GM_LANG_charts);
			$menu->addLink($link);
		}
		else {
			// top menubar
			$menu = new Menu(GM_LANG_charts);
			$menu->addLink($link);
		}
		//-- pedigree sub menu
		$submenu = new Menu(GM_LANG_pedigree_chart);
		$submenu->addLink($link);
		$menu->addSubmenu($submenu);
		
		//-- descendancy sub menu
		if (file_exists("descendancy.php")) {
			$link = "descendancy.php";
			if ($rootid) $link .= "?rootid=".$rootid;
			$submenu = new Menu(GM_LANG_descend_chart);
			$submenu->addLink($link);
			$menu->addSubmenu($submenu);
		}
		//-- ancestry submenu
		if (file_exists("ancestry.php")) {
			$link = "ancestry.php";
			if ($rootid) $link .= "?rootid=".$rootid;
			$submenu = new Menu(GM_LANG_ancestry_chart);
			$submenu->addLink($link);
			$menu->addSubmenu($submenu);
		}
		//-- fan chart submenu
		if (file_exists("fanchart.php") and function_exists("imagettftext")) {
			$link = "fanchart.php";
			if ($rootid) $link .= "?rootid=".$rootid;
			$submenu = new Menu(GM_LANG_fan_chart);
			$submenu->addLink($link);
			$menu->addSubmenu($submenu);
		}
		//-- hourglass submenu
		if (file_exists("hourglass.php")) {
			$link = "hourglass.php";
			if ($rootid) $link .= "?pid=".$rootid;
			$submenu = new Menu(GM_LANG_hourglass_chart);
			$submenu->addLink($link);
			$menu->addSubmenu($submenu);
		}
		//-- familybook submenu
		if (file_exists("familybook.php")) {
			$link = "familybook.php";
			if ($rootid) $link .= "?pid=".$rootid;
			$submenu = new Menu(GM_LANG_familybook_chart);
			$submenu->addLink($link);
			$menu->addSubmenu($submenu);
		}
		//-- timeline chart submenu
		if (file_exists("timeline.php")) {
			$link = "timeline.php";
			if ($rootid) $link .= "?pids0=".$rootid;
			$submenu = new Menu(GM_LANG_timeline_chart);
			$submenu->addLink($link);
			$menu->addSubmenu($submenu);
		}
		//-- relationship submenu
		if (file_exists("relationship.php")) {
			if ($rootid && empty($myid)) {
				if ($gm_user->username != "") {
					$myid = $gm_user->gedcomid[GedcomConfig::$GEDCOMID];
				}
			}
			if ((!empty($myid) && $myid != $rootid) || empty($rootid)) {
				$link = "relationship.php";
				if ($rootid) {
					$link .= "?pid1=".$myid."&pid2=".$rootid;
					$submenu = new Menu(GM_LANG_relationship_to_me);
					$submenu->addLink($link);
				} else {
					$submenu = new Menu(GM_LANG_relationship_chart);
					$submenu->addLink($link);
				}
				$menu->addSubmenu($submenu);
			}
		}
		//-- produce a plot of statistics
		if (!$rootid && file_exists("statistics.php") && file_exists("modules/jpgraph")) {
			$submenu = new Menu(GM_LANG_statistics);
			$submenu->addLink("statistics.php");
			$menu->addSubmenu($submenu);
		}
		//-- ancestry submenu
		if (file_exists("paternals.php")) {
			$link = "paternals.php";
			if ($rootid) $link .= "?rootid=".$rootid;
			$submenu = new Menu(GM_LANG_paternal_chart);
			$submenu->addLink($link);
			$menu->addSubmenu($submenu);
		}
		return $menu;
	}
	
	public function GetReportMenu($pid="", $type="") {
		global $gm_user;
		global $LANGUAGE, $PRIV_PUBLIC, $PRIV_USER, $PRIV_NONE, $PRIV_HIDE;

		if (!file_exists("reportengine.php")) return null;
		$menu = new Menu(GM_LANG_reports);
		
		//-- reports submenus
		$reports = GetReportList();
		$sortreports = array ();
//		print_r($reports);
		foreach($reports as $file=>$report) {
			if (empty($type) || $report["type"] == $type) {
				if (!isset($report["access"])) $report["access"] = $PRIV_PUBLIC;
				if ($report["access"] >= $gm_user->getUserAccessLevel()) {
					if (!empty($report["title"][$LANGUAGE])) $label = $report["title"][$LANGUAGE];
					else $label = implode("", $report["title"]);
					$sortreports[$report["file"]]=$label;
				}
			}
		}
		asort($sortreports);
		foreach($sortreports as $file=>$label) {
			// indi report
			if ($type == "indi") {
				$submenu = new Menu($label);
				$submenu->addLink("reportengine.php?action=setup&report=".$file."&pid=".$pid);
			}
			// family report
			else if ($type == "fam") {
				$submenu = new Menu($label);
				$submenu->addLink("reportengine.php?action=setup&report=".$file."&famid=".$pid);
			}
			// family report
			else if ($type == "repo") {
				$submenu = new Menu($label);
				$submenu->addLink("reportengine.php?action=setup&report=".$file."&repo=".$pid);
			}
			// family report
			else if ($type == "sour") {
				$submenu = new Menu($label);
				$submenu->addLink("reportengine.php?action=setup&report=".$file."&sid=".$pid);
			}
			// default
			else {
				$submenu = new Menu($label);
				$submenu->addLink("reportengine.php?action=setup&report=".$file);
			}
			$menu->addSubmenu($submenu);
		}
		if(isset($submenu)) return $menu;
		return false;
	}
	
	/**
	 * get the menu for the lists
	 * @return Menu		the menu item
	 */
	public function GetListMenu() {
		global $gm_user;
		global $SHOW_SOURCES;
		
		//-- main lists menu item
		$menu = new Menu(GM_LANG_lists);
		
		//-- indi list sub menu
		$submenu = new Menu(GM_LANG_individual_list);
		$submenu->addLink("indilist.php");
		$menu->addSubmenu($submenu);
		//-- famlist sub menu
		if (file_exists("famlist.php")) {
			$submenu = new Menu(GM_LANG_family_list);
			$submenu->addLink("famlist.php");
			$menu->addSubmenu($submenu);
		}
		//-- source
		if (file_exists("sourcelist.php") && $SHOW_SOURCES >= $gm_user->getUserAccessLevel()) {
			$submenu = new Menu(GM_LANG_source_list);
			$submenu->addLink("sourcelist.php");
			$menu->addSubmenu($submenu);
		}
		//-- repository
		if (file_exists("repolist.php")&& $SHOW_SOURCES >= $gm_user->getUserAccessLevel()) {
			$submenu = new Menu(GM_LANG_repo_list);
			$submenu->addLink("repolist.php");
			$menu->addSubmenu($submenu);
		}
		//-- general notes
		if (file_exists("notelist.php")) {
			$submenu = new Menu(GM_LANG_note_list);
			$submenu->addLink("notelist.php");
			$menu->addSubmenu($submenu);
		}
		//-- places
		if (file_exists("placelist.php")) {
			$submenu = new Menu(GM_LANG_place_list);
			$submenu->addLink("placelist.php");
			$menu->addSubmenu($submenu);
		}
		//-- medialist
		if (file_exists("medialist.php")) {
			$submenu = new Menu(GM_LANG_media_list);
			$submenu->addLink("medialist.php");
			$menu->addSubmenu($submenu);
		}
		//-- aliveinyear
		if (file_exists("aliveinyear.php")) {
			$submenu = new Menu(GM_LANG_alive_in_year);
			$submenu->addLink("aliveinyear.php");
			$menu->addSubmenu($submenu);
		}
		// NOTE: Unlinked individuals and families
		if (file_exists("unlinked.php")) {
			$submenu = new Menu(GM_LANG_unlink_list);
			$submenu->addLink("unlinked.php");
			$menu->addSubmenu($submenu);
		}
		//-- Actionlist (admins only!)
		if (file_exists("actionlist.php") && $gm_user->ShowActionLog()) {
			$submenu = new Menu(GM_LANG_actionlist);
			$submenu->addLink("actionlist.php");
			$menu->addSubmenu($submenu);
		}
		return $menu;
	}
	
	/**
	 * get the help menu
	 * @return Menu		the menu item
	 */
	public function GetHelperMenu() {
		global $spider;
		global $QUERY_STRING, $helpindex, $action;
		
		//-- main help menu item
		$menu = new Menu(GM_LANG_help_page);

		//-- help_for_this_page sub menu
		$submenu = new Menu(GM_LANG_help_for_this_page);
		$submenu->addLink("window.open.help_help_".basename(SCRIPT_NAME)."&action=".$action);
		$menu->addSubmenu($submenu);
		
		//-- help_contents sub menu
		$submenu = new Menu(GM_LANG_help_contents);
		$submenu->addLink("window.open.help_help_contents_help");
		$menu->addSubmenu($submenu);
		
		//-- faq sub menu
		if (file_exists("faq.php")) {
			$submenu = new Menu(GM_LANG_faq_list);
			$submenu->addLink("faq.php");
			$menu->addSubmenu($submenu);
		}
		//-- searchhelp sub menu
		if (file_exists("searchhelp.php")) {
			$submenu = new Menu(GM_LANG_hs_title);
			$submenu->addLink("window.open_searchhelp.php");
			$menu->addSubmenu($submenu);
		}
		
		//-- add contact links to help menu
		$menu->addSeperator();
		$menuitems = PrintContactLinks(1);
		foreach($menuitems as $menuitem) {
			$submenu = new Menu($menuitem["label"]);
			$submenu->addLink($menuitem["link"]);
			$menu->addSubmenu($submenu);
		}
		
		//-- add show/hide context_help
		if (!$spider) {
			$menu->addSeperator();
			if ($_SESSION["show_context_help"]) {
				$submenu = new Menu(GM_LANG_hide_contexthelp);
				$submenu->addLink(SCRIPT_NAME."?".$QUERY_STRING."&show_context_help=no");
			}
			else {
				$submenu = new Menu(GM_LANG_show_contexthelp);
				$submenu->addLink(SCRIPT_NAME."?".$QUERY_STRING."&show_context_help=yes");
			}
			$menu->addSubmenu($submenu);
		}
		return $menu;
	}
	
	public function GetThisPersonMenu(&$controller) {
		
		//-- main edit menu item
		$menu = new Menu(GM_LANG_this_individual);
		
		// Charts menu
		$submenu = self::GetChartsMenu($controller->xref);
		$menu->addSubmenu($submenu);
		
		// Reports menu
		$submenu = self::GetReportMenu($controller->xref, "indi");
		if ($submenu) $menu->addSubmenu($submenu);
		
		// Edit menu
		if ($controller->indi->canedit || $controller->caneditown) {
			$submenu = $controller->getEditMenu();
			if ($submenu->hasSubMenus()) $menu->addSubmenu($submenu);
		}
		
		// Other menu
		if ($controller->display_other_menu) {
			$submenu = $controller->getOtherMenu();
			$menu->addSubmenu($submenu);
		}
		
		return $menu;
	}
	
	public function GetThisFamilyMenu(&$controller) {
		
		if (!$controller->family->isempty) {
			//-- main edit menu item
			$menu = new Menu(GM_LANG_this_family);
			
			// Charts menu
			$submenu = $controller->getChartsMenu();
			$menu->addSubmenu($submenu);
			
			// Reports menu
			$submenu = self::GetReportMenu($controller->xref, "fam");
			if ($submenu) $menu->addSubmenu($submenu);
			
			// Edit menu
			if ($controller->family->canedit) {
				$submenu = $controller->getEditMenu();
				$menu->addSubmenu($submenu);
			}
			
			// Other menu
			if ($controller->display_other_menu) {
				$submenu = $controller->getOtherMenu();
				$menu->addSubmenu($submenu);
			}
			
			return $menu;
		}
	}
	
	public function GetThisSourceMenu(&$source_controller) {
		
		if ($source_controller->source->canedit || $source_controller->display_other_menu) {
			//-- main edit menu item
			$menu = new Menu(GM_LANG_this_source);

			// Reports menu
			$submenu = self::GetReportMenu($source_controller->xref, "sour");
			if ($submenu) $menu->addSubmenu($submenu);
		
			// Edit menu
			if ($source_controller->source->canedit) {
				$submenu = $source_controller->getEditMenu();
				$menu->addSubmenu($submenu);
			}
			
			// Other menu
			if ($source_controller->display_other_menu) {
				$submenu = $source_controller->getOtherMenu();
				$menu->addSubmenu($submenu);
			}
			if (isset($submenu)) return $menu;
		}
		return false;
	}
	
	public function GetThisRepoMenu(&$repository_controller) {
		
		if ($repository_controller->repo->canedit || $repository_controller->display_other_menu) {
			//-- main edit menu item
			$menu = new Menu(GM_LANG_this_repository);
		
			// Reports menu
			$submenu = self::GetReportMenu($repository_controller->xref, "repo");
			if ($submenu) $menu->addSubmenu($submenu);
			
			// Edit menu
			if ($repository_controller->repo->canedit) {
				$submenu = $repository_controller->getEditMenu();
				$menu->addSubmenu($submenu);
			}
		
			// Other menu
			if ($repository_controller->display_other_menu) {
				$submenu = $repository_controller->getOtherMenu();
				$menu->addSubmenu($submenu);
			}
		}
		if (isset($submenu)) return $menu;
	}
	
	public function GetThisMediaMenu(&$controller) {
		
		if ($controller->media->canedit || !$controller->display_other_menu) {
			//-- main edit menu item
			$menu = new Menu(GM_LANG_this_media);
			
			// Reports menu
			$submenu = self::GetReportMenu($controller->xref, "media");
			if ($submenu) $menu->addSubmenu($submenu);
			
			// Edit menu
			if ($controller->media->canedit) {
				$submenu = $controller->getEditMenu();
				$menu->addSubmenu($submenu);
			}
			
			// Other menu
			if ($controller->display_other_menu) {
				$submenu = $controller->getOtherMenu();
				$menu->addSubmenu($submenu);
			}
			
			return $menu;
		}
		else return false;
	}
	
	public function GetThisNoteMenu(&$controller) {
		
		if (!$controller->note->isempty && !$controller->note->isdeleted) {
			//-- main edit menu item
			$menu = new Menu(GM_LANG_this_note);
			
			// Reports menu
			$submenu = self::GetReportMenu($controller->xref, "note");
			if ($submenu) $menu->addSubmenu($submenu);
			
			// Edit menu
			if ($controller->note->canedit) {
				$submenu = $controller->getEditMenu();
				$menu->addSubmenu($submenu);
			}
			
			// Other menu
			if ($controller->display_other_menu) {
				$submenu = $controller->getOtherMenu();
				$menu->addSubmenu($submenu);
			}
			
			return $menu;
		}
		else return false;
	}
}
?>
