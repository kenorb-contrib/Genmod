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
 * $Id: menu.php,v 1.73 2009/03/29 13:20:11 sjouke Exp $
 * @package Genmod
 */

class Menu {
	var $seperator = false;
	var $label = ' ';
	var $labelpos = 'right';
	var $link = '#';
	var $onclick = null;
	var $icon = null;
	var $hovericon = null;
	var $flyout = 'down';
	var $class = '';
	var $hoverclass = '';
	var $submenuclass = '';
	var $accesskey = null;
	var $parentmenu = null;
	var $submenus;

	/**
	 * Constructor for the menu class
	 * @param string $label		the label for the menu item (usually a gm_lang variable)
	 */
	function Menu($label=' ', $strip=true) {
		$this->submenus = array();
		if ($strip) $this->addLabel(htmlspecialchars($label));
		else $this->addLabel($label);
	}

	function isSeperator() {
		$this->seperator = true;
	}

	function addLabel($label=' ', $pos='right') {
		if ($label) $this->label = $label;
		$this->labelpos = $pos;
	}

	function addLink($link='#') {
		$this->link = $link;
	}
	
	function addOnclick($onclick) {
		$this->onclick = $onclick;
	}
	function addFlyout($flyout='down') {
		$this->flyout = $flyout;
	}

	function addClass($class, $hoverclass='', $submenuclass='') {
		$this->class = $class;
		$this->hoverclass = $hoverclass;
		$this->submenuclass = $submenuclass;
	}
	
	function addAccesskey($accesskey) {
		$this->accesskey = $accesskey;
	}

	function addSubMenu($obj) {
		$this->submenus[] = $obj;
	}

	function addSeperator() {
		$submenu = new Menu();
		$submenu->isSeperator();
		$this->submenus[] = $submenu;
	}
	
	function getMenu() {
		global
			$menucount,
			$TEXT_DIRECTION,
			$GM_IMAGE_DIR,
			$GM_IMAGES
		;
		if (!isset($menucount)) $menucount = 0;
		else $menucount++;
		if ($this->seperator) {
			$output = "<div id=\"menu{$menucount}\" class=\"seperator\";\">"
				."<img src=\"{$GM_IMAGE_DIR}/{$GM_IMAGES['hline']['other']}\" height=\"3\" alt=\"\" />"
				."</div>\n";
			return $output;
		}
		$c = count($this->submenus);
		$output = "<div id=\"menu{$menucount}\" style=\"clear: both;\"";
		if (!empty($this->class)) $output .= " class=\"{$this->class}\"";
		$output .= ">\n";
		if ($this->link=="#") $this->link = "javascript:;";
		$link = "<a href=\"{$this->link}\" onmouseover=\""
		;
		if ($c >= 0) $link .= "show_submenu('menu{$menucount}_subs', 'menu{$menucount}', '{$this->flyout}'); ";
		if ($this->hoverclass !== null) $link .= "change_class('menu{$menucount}', '{$this->hoverclass}'); ";
		// if ($this->hovericon !== null) $link .= "change_icon('menu{$menucount}_icon', '{$this->hovericon}'); ";
		$link .= '" onmouseout="';
		if ($c >= 0) $link .= "timeout_submenu('menu{$menucount}_subs'); ";
		if ($this->hoverclass !== null) $link .= "change_class('menu{$menucount}', '{$this->class}'); ";
		// if ($this->hovericon !== null) $link .= "change_icon('menu{$menucount}_icon', '{$this->icon}'); ";
		if ($this->onclick !== null) $link .= "\" onclick=\"{$this->onclick}";
		if ($this->accesskey !== null) $link .= '" accesskey="'.$this->accesskey;
		$link .= "\">";
		if ($this->icon !== null) {
			$MenuIcon = "<img id=\"menu{$menucount}_icon\" src=\"{$this->icon}\" class=\"icon\" alt=\"".preg_replace("/\"/", '', $this->label).'" title="'.preg_replace("/\"/", '', $this->label).'" '." />";
			switch ($this->labelpos) {
			case "right":
				$output .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
				$output .= "<tr>";
				$output .= "<td valign=\"middle\">";
				$output .= $link;
				$output .= $MenuIcon;
				$output .= "</a>";
				$output .= "</td>";
				$output .= "<td align=\"";
				if ($TEXT_DIRECTION=="rtl") $output .= "right";
				else $output .= "left";
				$output .= "\" valign=\"middle\" style=\"white-space: nowrap;\">";
				$output .= $link;
				$output .= $this->label;
				$output .= "</a></td>";
				$output .= "</tr></table>";
				break;
			case "left":
				$output .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
				$output .= "<tr>";
				$output .= "<td align=\"";
				if ($TEXT_DIRECTION=="rtl") $output .= "left";
				else $output .= "right";
				$output .= "\" valign=\"middle\" style=\"white-space: nowrap;\">";
				$output .= $link;
				$output .= $this->label;
				$output .= "</a></td>";
				$output .= "<td valign=\"middle\">";
				$output .= $link;
				$output .= $MenuIcon;
				$output .= "</a>";
				$output .= "</td>";
				$output .= "</tr></table>";
				break;
			case "down":
				$output .= $link;
				$output .= $MenuIcon;
				$output .= "<br />";
				$output .= $this->label;
				$output .= "</a>";
				break;
			case "up":
				$output .= $link;
				$output .= $this->label;
				$output .= "<br />";
				$output .= $MenuIcon;
				$output .= "</a>";
				break;
			default:
				$output .= $link;
				$output .= $MenuIcon;
				$output .= "</a>";
			}
		}
		else {
			$output .= $link;
			$output .= $this->label;
			$output .= "</a>";
		}
		
		if ($c > 0) {
			$submenuid = "menu{$menucount}_subs";
			if ($TEXT_DIRECTION == 'ltr') $output .= '<div style="text-align: left;">';
			else	$output .= '<div style="text-align: right;">';
			$output .= "<div id=\"menu{$menucount}_subs\" class=\"{$this->submenuclass}\" style=\"position: absolute; visibility: hidden; z-index: 100;";
			// if ($this->flyout == 'right') {
				if ($TEXT_DIRECTION == 'ltr') $output .= ' left: 80px;';
				else $output .= ' right: 50px;';
			// }
			$output .= "\" onmouseover=\"show_submenu('{$this->parentmenu}'); show_submenu('{$submenuid}');\" onmouseout=\"timeout_submenu('menu{$menucount}_subs');\">\n";
			foreach($this->submenus as $submenu) {
				$submenu->parentmenu = $submenuid;
				$output .= $submenu->getMenu();
			}
			$output .= "</div></div>\n";
		}
		$output .= "</div>\n";
		return $output;
	}
	
	function printMenu() {
		print $this->getMenu();
	}
}

/**
 * Create text for sub-menu where one will be checked and others not
 * @return Text         the sub-menu text
 */
function GetSubmenuText($textIn, $selected=false) {
	global $GM_IMAGE_DIR;
	$checkImage = $GM_IMAGE_DIR."/checked.gif";
	$noImage = $GM_IMAGE_DIR."/pix1.gif";
	$displayImage = (file_exists($checkImage) && file_exists($noImage));
	//$displayImage=false;
	if ($selected==true) {
		if ($displayImage==true)
			return "<img src='{$checkImage}' height='12' witdh='12' alt='' />".$textIn;
		else
			return "<i>".$textIn."</i>";
	} else {
		if ($displayImage==true)
			return "<img src='{$noImage}' height='12' width='12' alt='' />".$textIn;
		else
			return $textIn;
	}
}


class MenuBar {
	/**
	 * Get the links to the custom pages created by the user
	 * @return Menu		the menu item
	 */
	function GetCustomMenu() {
		global $TBLPREFIX, $gm_lang, $TEXT_DIRECTION, $CONFIGURED, $DBLAYER, $Users;
		
		if (!$DBLAYER->connected) return false;
		
		// NOTE: Check if table exists, if not, do print the menu
		$sql = "SHOW TABLES LIKE '".$TBLPREFIX."pages'";
		$res = NewQuery($sql);
		if ($res) {
			if ($TEXT_DIRECTION=="rtl") $ff="_rtl";
			else $ff="";
			
			// Retrieve the current pages stored in the DB
			$sql = "SELECT * FROM ".$TBLPREFIX."pages";
			$result = NewQuery($sql);
			if (!$result) {
				$message  = 'Invalid query: ' . mysql_error() . "\n";
				$message .= 'Whole query: ' . $sql;
				die($message);
			}
			else {
				$pages = array();
				while ($row = $result->FetchAssoc()) {
					$page = array();
					$page["id"] = $row["pag_id"];
					$page["html"] = $row["pag_content"];
					$page["title"] = $row["pag_title"];
					$pages[$row["pag_id"]] = $page;
				}
			}
				
			//-- My Pages
			if (count($pages) > 0) {
				$menu = new Menu($gm_lang["my_pages"]);
				foreach ( $pages as $key => $page) {
					$submenu = new Menu($page["title"], "");
					$submenu->addLink("custompage.php?action=show&id=".$page["id"]);
					$menu->addSubmenu($submenu);
				}
			}
			else $menu = "";
			if ($Users->userIsAdmin($Users->GetUserName())) {
				if (!is_object($menu)) $menu = new Menu($gm_lang["my_pages"]);
				$submenu = new Menu($gm_lang["edit_pages"], "");
				$submenu->addLink("custompage.php?action=edit");
				$menu->addSubmenu($submenu);
			}
			return $menu;
		}
		return null;
	}

	/**
	 * Create the File menu
	 * @return Menu		the menu item
	 */
	function GetFileMenu() {
		global $TEXT_DIRECTION, $GEDCOM, $gm_lang, $debugcollector;
		global $SCRIPT_NAME, $LOGIN_URL, $SERVER_URL, $QUERY_STRING, $gm_username;
		global $ALLOW_CHANGE_GEDCOM, $GEDCOMS, $GEDCOM, $Users;
		
		 $username = $gm_username;
		 $user = $Users->GetUser($username);

		if ($TEXT_DIRECTION=="rtl") $ff="_rtl"; else $ff="";
		//-- main file menu item
		$menu = new Menu($gm_lang["menu_file"]);
		
		// NOTE: Login link
		if (empty($user->username)) {
			$submenu = new Menu($gm_lang["login"]);
			if (!empty($LOGIN_URL)) $submenu->addLink($LOGIN_URL."?url=".urlencode(basename($SCRIPT_NAME)."?".$QUERY_STRING."&ged=$GEDCOM"));
			else $submenu->addLink($SERVER_URL."login.php?url=".urlencode(basename($SCRIPT_NAME)."?".$QUERY_STRING."&ged=$GEDCOM"));
			$menu->addSubmenu($submenu);
		}
		
		// NOTE: Open GEDCOM
		if ($ALLOW_CHANGE_GEDCOM && count($GEDCOMS)>1) {
			$submenu = new Menu($gm_lang["menu_open"]);
			$menu->addSubmenu($submenu);
		
		// NOTE: Add GEDCOMS to open
			foreach($GEDCOMS as $ged=>$gedarray) {
				$submenu = new Menu(GetSubmenuText(PrintReady($gedarray["title"]), ($ged == $GEDCOM)), false);
				$submenu->addLink("index.php?command=gedcom&ged=".$ged."&gedcomid=".$gedarray["id"]);
				$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
			}
		}
		
		// NOTE: Admin link
		if ($user->canadmin || ($Users->userGedcomAdmin($username, $GEDCOM))) {
			$submenu = new Menu($gm_lang["admin"]);
			$submenu->addLink("admin.php");
			$menu->addSubmenu($submenu);
		}
		
		// NOTE: User page
		$submenu = new Menu($gm_lang["welcome_page"]);
		$submenu->addLink("index.php?command=gedcom");
		$menu->addSubmenu($submenu);
		
		// NOTE: User page
		$submenu = new Menu($gm_lang["mgv"]);
		$submenu->addLink("index.php?command=user");
		$menu->addSubmenu($submenu);
		
		// NOTE: Print preview
		$submenu = new Menu($gm_lang["print_preview"]);
		// TODO: Querystring contains htmlcode, kills the JS
		$submenu->addLink($SCRIPT_NAME."?view=preview&".GetQueryString());
		$menu->addSubmenu($submenu);
		
		// NOTE: Logout link
		if ($user && !empty($username)) {
			$submenu = new Menu($gm_lang["logout"]);
			$submenu->addLink("index.php?logout=1");
			$menu->addSubmenu($submenu);
		}
		
		return $menu;
	}

	function GetEditMenu() {
		global $TEXT_DIRECTION, $GEDCOM, $gm_lang, $debugcollector;
		global $SCRIPT_NAME, $LOGIN_URL, $QUERY_STRING, $GEDCOM, $gm_username;
		global $ALLOW_CHANGE_GEDCOM, $GEDCOMS;
		
		global $ENABLE_CLIPPINGS_CART, $Users;
		global $TEXT_DIRECTION, $GEDCOM, $gm_lang;
		
		$username = $gm_username;
		$user = $Users->GetUser($username);
		
		//-- main edit menu item
		$menu = new Menu($gm_lang["edit"], "#");
		
		// Clippings menu
		if (file_exists("clippings.php") &&($ENABLE_CLIPPINGS_CART > $Users->getUserAccessLevel())) {
			//-- main clippings menu item
			$submenu = new Menu($gm_lang["clippings_cart"]);
			$submenu->addLink("clippings.php");
			$menu->addSubmenu($submenu);
		}
				
		//-- search_general sub menu
		$submenu = new Menu($gm_lang["search"]);
		$submenu->addLink("search.php");
		$menu->addSubmenu($submenu);
		
		if ($user->editaccount) {
			$submenu = new Menu($gm_lang["editowndata"]);
			$submenu->addLink("edituser.php");
			$menu->addSubmenu($submenu);
		}
		
		return $menu;
	}
	
	function GetViewMenu() {
		global $gm_lang, $SCRIPT_NAME, $gm_language, $language_settings;
		global $LANGUAGE, $ENABLE_MULTI_LANGUAGE;
		global $ALLOW_THEME_DROPDOWN, $ALLOW_USER_THEMES, $Users, $THEME_DIR;
		
		//-- main edit menu item
		$menu = new Menu($gm_lang["menu_view"]);
		
		// Language selector
		if ($ENABLE_MULTI_LANGUAGE) {
			// Change language
			$submenu = new Menu($gm_lang["inc_languages"]);
			$menu->addSubmenu($submenu);
			
			// NOTE: Add languages available
			foreach ($gm_language as $key=>$value) {
				if ($language_settings[$key]["gm_lang_use"]) {
					$submenu = new Menu(GetSubmenuText($gm_lang[$key], ($LANGUAGE == $key)), false);
					$submenu->addLink($SCRIPT_NAME."?changelanguage=yes&NEWLANGUAGE=".$key."&".GetQueryString());
					$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
				}
			}
		}

		// Theme selector
		if ($ALLOW_THEME_DROPDOWN && $ALLOW_USER_THEMES) {
			// Change theme
			$submenu = new Menu($gm_lang["inc_themes"]);
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

			$uname = $Users->GetUserName();
			$user = $Users->getUser($uname);

			// NOTE: add themes
			$themes = GetThemeNames();
			foreach ($themes as $indexval => $themedir) {
				$submenu = new Menu(GetSubmenuText($themedir["name"], (($themedir["dir"] == $user->theme)||(empty($user->theme)&&($themedir["dir"]==$THEME_DIR)))), false);
//LERMAN - for some reason "...&amp;mytheme=..." does not work for Firefox 1.5.0.4 on Linux, but does work on IE. Changing it to "...&mytheme=..." works in both places
				$submenu->addLink("themechange.php?frompage=".urlencode($frompage)."&mytheme=".$themedir["dir"]);
				$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
			}
		}

		// Calendar menu
		$submenu = new Menu($gm_lang["menu_calendar"]);
		$menu->addSubmenu($submenu);
		
		// Day Calendar
		$submenu = new Menu($gm_lang["menu_calendar_day"]);
		$submenu->addLink("calendar.php");
		$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
		
		// Month Calendar
		$submenu = new Menu($gm_lang["menu_calendar_month"]);
		$submenu->addLink("calendar.php?action=calendar");
		$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
		
		// Year Calendar
		$submenu = new Menu($gm_lang["menu_calendar_year"]);
		$submenu->addLink("calendar.php?action=year");
		$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
		
		return $menu;
	}
	
	function GetFavoritesMenu() {
		global $gm_lang, $gm_username, $GEDCOM, $REQUIRE_AUTHENTICATION, $GEDCOMID, $GEDCOMS;
		global $SHOW_ID_NUMBERS, $SHOW_FAM_ID_NUMBERS, $TEXT_DIRECTION;
		global $pid, $famid, $sid, $Privacy;
		
		// NOTE: Favorites
		$menu = new Menu($gm_lang["menu_favorites"]);
		
		// NOTE: User Favorites
		$username = $gm_username;
		if (empty($pid)&&(!empty($famid))) $pid = $famid;
		if (empty($pid)&&(!empty($sid))) $pid = $sid;
		
		
		if (!empty($username)) {
			$submenu = new Menu($gm_lang["my_favorites"]);
			$menu->addSubmenu($submenu);
			$userfavs = getUserFavorites($username);
		}
		else {
			if ($REQUIRE_AUTHENTICATION) return false;
			$userfavs = array();
		}
		
		$mygedcom = $GEDCOM;
		$mygedcomid = $GEDCOMID;
		$mypid = $pid;
		$current_gedcom = $GEDCOM;
		$current_gedcomid = $GEDCOMID;
		foreach($userfavs as $key => $favorite) {
			$GEDCOM = $favorite["file"];
			$GEDCOMID = $GEDCOMS[$GEDCOM]["id"];
			if ($current_gedcom != $GEDCOM) {
				$Privacy->ReadPrivacy($GEDCOMID);
				$current_gedcom = $GEDCOM;
			}
			if (displayDetailsById($favorite["gid"], $favorite["type"], 1, true)) {
				$indirec = FindGedcomRecord($favorite["gid"]);
				if ($favorite["type"]=="INDI") {
					if ($SHOW_ID_NUMBERS) $addid = " (".$favorite["gid"].")";
					else $addid = "";
					$indiname = PrintReady(GetPersonName($favorite["gid"], $indirec, false).$addid);
					$submenu = new Menu($indiname);
					$submenu->addLink("individual.php?pid=".$favorite["gid"]."&ged=$GEDCOM");
					$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
				}
				if ($favorite["type"]=="FAM") {
					if ($SHOW_FAM_ID_NUMBERS) $addid = " (".$favorite["gid"].")";
					else $addid = "";
					$famname = PrintReady(GetFamilyDescriptor($favorite["gid"], false, $indirec, false, false).$addid);
					$submenu = new Menu($famname);
					$submenu->addLink("family.php?famid=".$favorite["gid"]."&ged=$GEDCOM");
					$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
				}
				if ($favorite["type"]=="SOUR") {
					if ($SHOW_ID_NUMBERS) $addid = " (".$favorite["gid"].")";
					else $addid = "";
					$sourname = PrintReady(trim(GetSourceDescriptor($favorite["gid"])).$addid);
					$submenu = new Menu($sourname);
					$submenu->addLink("source.php?sid=".$favorite["gid"]."&ged=$GEDCOM");
					$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
				}
				if ($favorite["type"]=="OBJE") {
					if ($SHOW_ID_NUMBERS) $addid = " (".$favorite["gid"].")";
					else $addid = "";
					$medianame = PrintReady(GetMediaDescriptor($favorite["gid"]).$addid);
					$submenu = new Menu($medianame);
					$submenu->addLink("mediadetail.php?mid=".$favorite["gid"]."&ged=$GEDCOM");
					$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
				}
			}
		}
		$GEDCOM = $mygedcom;
		$GEDCOMID = $mygedcomid;
		if ($current_gedcom != $GEDCOM) $Privacy->ReadPrivacy($GEDCOMID);
		
		// NOTE: Link to add new favorites
		$pid = $mypid;
		$GEDCOM = $mygedcom;
		$GEDCOMID = $mygedcomid;
		if ((!empty($username))&&(strpos($_SERVER["SCRIPT_NAME"], "individual.php")!==false)) {
			$submenu = new Menu($gm_lang["add_to_my_favorites"]);
			$submenu->addLink("individual.php?action=addfav&gid=$pid&pid=$pid");
			$menu->addSubmenu($submenu);
		}
		
		// NOTE: Gedcom Favorites
		$gedcomfavs = getUserFavorites($GEDCOM);
		if (count($gedcomfavs)>0) {
			$submenu = new Menu($gm_lang["gedcom_favorites"]);
			$menu->addSubmenu($submenu);
			
			foreach($gedcomfavs as $key => $favorite) {
				if ($favorite["type"]=="URL" && !empty($favorite["url"])) {
					if (!empty($favorite["title"])) $urlname = PrintReady($favorite["title"]);
					else $urlname = $favorite["url"];
					$submenu = new Menu($urlname);
					$submenu->addLink($favorite["url"]);
					$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
				}
				else if (displayDetailsById($favorite["gid"], $favorite["type"], 1, true)) {
					$indirec = FindGedcomRecord($favorite["gid"]);
					if ($favorite["type"]=="INDI") {
						if ($SHOW_ID_NUMBERS) $addid = " (".$favorite["gid"].")";
						else $addid = "";
						$indiname = PrintReady(GetPersonName($favorite["gid"], $indirec, false).$addid);
						$submenu = new Menu($indiname);
						$submenu->addLink("individual.php?pid=".$favorite["gid"]."&ged=$GEDCOM");
						$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
					}
					if ($favorite["type"]=="FAM") {
						if ($SHOW_FAM_ID_NUMBERS) $addid = " (".$favorite["gid"].")";
						else $addid = "";
						$famname = PrintReady(GetFamilyDescriptor($favorite["gid"], false, $indirec, false, false).$addid);
						$submenu = new Menu($famname);
						$submenu->addLink("family.php?famid=".$favorite["gid"]."&ged=$GEDCOM");
						$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
					}
					if ($favorite["type"]=="SOUR") {
						if ($SHOW_ID_NUMBERS) $addid = " (".$favorite["gid"].")";
						else $addid = "";
						$sourname = PrintReady(trim(GetSourceDescriptor($favorite["gid"])).$addid);
						$submenu = new Menu($sourname);
						$submenu->addLink("source.php?sid=".$favorite["gid"]."&ged=$GEDCOM");
						$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
					}
					if ($favorite["type"]=="OBJE") {
						if ($SHOW_ID_NUMBERS) $addid = " (".$favorite["gid"].")";
						else $addid = "";
						$medianame = PrintReady(GetMediaDescriptor($favorite["gid"]).$addid);
						$submenu = new Menu($medianame);
						$submenu->addLink("mediadetail.php?mid=".$favorite["gid"]."&ged=$GEDCOM");
						$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
					}
				}
			}
		}
		
		return $menu;
	}
	
	/**
	 * get the menu for the charts
	 * @return Menu		the menu item
	 */
	function GetChartMenu($rootid='',$myid='') {
		global $TEXT_DIRECTION, $GEDCOM, $gm_lang, $gm_username, $Users;
		
		//-- main charts menu item
		$link = "pedigree.php";
		if ($rootid) {
			$link .= "?rootid=".$rootid;
			$menu = new Menu($gm_lang["charts"]);
			$menu->addLink($link);
		}
		else {
			// top menubar
			$menu = new Menu($gm_lang["charts"]);
			$menu->addLink($link);
		}
		//-- pedigree sub menu
		$submenu = new Menu($gm_lang["pedigree_chart"]);
		$submenu->addLink($link);
		$menu->addSubmenu($submenu);
		
		//-- descendancy sub menu
		if (file_exists("descendancy.php")) {
			$link = "descendancy.php";
			if ($rootid) $link .= "?pid=".$rootid;
			$submenu = new Menu($gm_lang["descend_chart"]);
			$submenu->addLink($link);
			$menu->addSubmenu($submenu);
		}
		//-- ancestry submenu
		if (file_exists("ancestry.php")) {
			$link = "ancestry.php";
			if ($rootid) $link .= "?rootid=".$rootid;
			$submenu = new Menu($gm_lang["ancestry_chart"]);
			$submenu->addLink($link);
			$menu->addSubmenu($submenu);
		}
		//-- fan chart submenu
		if (file_exists("fanchart.php") and function_exists("imagettftext")) {
			$link = "fanchart.php";
			if ($rootid) $link .= "?rootid=".$rootid;
			$submenu = new Menu($gm_lang["fan_chart"]);
			$submenu->addLink($link);
			$menu->addSubmenu($submenu);
		}
		//-- hourglass submenu
		if (file_exists("hourglass.php")) {
			$link = "hourglass.php";
			if ($rootid) $link .= "?pid=".$rootid;
			$submenu = new Menu($gm_lang["hourglass_chart"]);
			$submenu->addLink($link);
			$menu->addSubmenu($submenu);
		}
		//-- familybook submenu
		if (file_exists("familybook.php")) {
			$link = "familybook.php";
			if ($rootid) $link .= "?pid=".$rootid;
			$submenu = new Menu($gm_lang["familybook_chart"]);
			$submenu->addLink($link);
			$menu->addSubmenu($submenu);
		}
		//-- timeline chart submenu
		if (file_exists("timeline.php")) {
			$link = "timeline.php";
			if ($rootid) $link .= "?pids[]=".$rootid;
			$submenu = new Menu($gm_lang["timeline_chart"]);
			$submenu->addLink($link);
			$menu->addSubmenu($submenu);
		}
		//-- relationship submenu
		if (file_exists("relationship.php")) {
			if ($rootid and empty($myid)) {
				$username = $gm_username;
				if (!empty($username)) {
					$user = $Users->GetUser($username);
					$myid = @$user->gedcomid[$GEDCOM];
				}
			}
			if (($myid and $myid!=$rootid) or empty($rootid)) {
				$link = "relationship.php";
				if ($rootid) {
					$link .= "?pid1=".$myid."&pid2=".$rootid;
					$submenu = new Menu($gm_lang["relationship_to_me"]);
					$submenu->addLink($link);
				} else {
					$submenu = new Menu($gm_lang["relationship_chart"]);
					$submenu->addLink($link);
				}
				$menu->addSubmenu($submenu);
			}
		}
		//-- produce a plot of statistics
		if (!$rootid && file_exists("statistics.php") && file_exists("modules/jpgraph")) {
			$submenu = new Menu($gm_lang["statistics"]);
			$submenu->addLink("statistics.php");
			$menu->addSubmenu($submenu);
		}
		return $menu;
	}
	
	function GetReportMenu($pid="", $famid="") {
		global $TEXT_DIRECTION, $GEDCOMS, $GEDCOM, $gm_lang, $Users;
		global $LANGUAGE, $PRIV_PUBLIC, $PRIV_USER, $PRIV_NONE, $PRIV_HIDE, $gm_username;
		
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl"; else $ff="";
		if (!file_exists("reportengine.php")) return null;
		$menu = new Menu($gm_lang["reports"]);
		
		//-- reports submenus
		$reports = GetReportList();
		$username = $gm_username;
		$sortreports = array ();
		foreach($reports as $file=>$report) {
			if (!isset($report["access"])) $report["access"] = $PRIV_PUBLIC;
			if ($report["access"] >= $Users->getUserAccessLevel($username)) {
				if (!empty($report["title"][$LANGUAGE])) $label = $report["title"][$LANGUAGE];
				else $label = implode("", $report["title"]);
				$sortreports[$report["file"]]=$label;
			}
		}
		asort($sortreports);
		foreach($sortreports as $file=>$label) {
			// indi report
			if ($pid) {
				$submenu = new Menu($label);
				$submenu->addLink("reportengine.php?action=setup&report=".$file."&pid=".$pid);
			}
			// family report
			else if ($famid) {
				$submenu = new Menu($label);
				$submenu->addLink("reportengine.php?action=setup&report=".$file."&famid=".$famid);
			}
			// default
			else {
				$submenu = new Menu($label);
				$submenu->addLink("reportengine.php?action=setup&report=".$file);
			}
			$menu->addSubmenu($submenu);
		}
		return $menu;
	}
	
	/**
	 * get the menu for the lists
	 * @return Menu		the menu item
	 */
	function GetListMenu() {
		global $TEXT_DIRECTION, $GEDCOM, $gm_lang, $Users;
		global $SHOW_SOURCES, $MULTI_MEDIA, $gm_username;
		
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl"; else $ff="";
		//-- main lists menu item
		$menu = new Menu($gm_lang["lists"]);
		
		//-- indi list sub menu
		$submenu = new Menu($gm_lang["individual_list"]);
		$submenu->addLink("indilist.php");
		$menu->addSubmenu($submenu);
		//-- famlist sub menu
		if (file_exists("famlist.php")) {
			$submenu = new Menu($gm_lang["family_list"]);
			$submenu->addLink("famlist.php");
			$menu->addSubmenu($submenu);
		}
		//-- source
		if (file_exists("sourcelist.php") && $SHOW_SOURCES >= $Users->getUserAccessLevel($gm_username)) {
			$submenu = new Menu($gm_lang["source_list"]);
			$submenu->addLink("sourcelist.php");
			$menu->addSubmenu($submenu);
		}
		//-- repository
		if (file_exists("repolist.php")&& $SHOW_SOURCES >= $Users->getUserAccessLevel($gm_username)) {
			$submenu = new Menu($gm_lang["repo_list"]);
			$submenu->addLink("repolist.php");
			$menu->addSubmenu($submenu);
		}
		//-- general notes
		if (file_exists("notelist.php")) {
			$submenu = new Menu($gm_lang["note_list"]);
			$submenu->addLink("notelist.php");
			$menu->addSubmenu($submenu);
		}
		//-- places
		if (file_exists("placelist.php")) {
			$submenu = new Menu($gm_lang["place_list"]);
			$submenu->addLink("placelist.php");
			$menu->addSubmenu($submenu);
		}
		//-- medialist
		if (file_exists("medialist.php") and $MULTI_MEDIA) {
			$submenu = new Menu($gm_lang["media_list"]);
			$submenu->addLink("medialist.php");
			$menu->addSubmenu($submenu);
		}
		//-- list most ancient parent of a family
//		if (file_exists("patriarchlist.php")) {
//			$submenu = new Menu($gm_lang["patriarch_list"]);
//			$submenu->addLink("patriarchlist.php");
//			$menu->addSubmenu($submenu);
//		}
		//-- aliveinyear
		if (file_exists("aliveinyear.php")) {
			$submenu = new Menu($gm_lang["alive_in_year"]);
			$submenu->addLink("aliveinyear.php");
			$menu->addSubmenu($submenu);
		}
		// NOTE: Unlinked individuals and families
		if (file_exists("unlinked.php")) {
			$submenu = new Menu($gm_lang["unlink_list"]);
			$submenu->addLink("unlinked.php");
			$menu->addSubmenu($submenu);
		}
		//-- Actionlist (admins only!)
		if (file_exists("actionlist.php") && $Users->userGedcomAdmin($Users->GetUserName(), $GEDCOM)) {
			$submenu = new Menu($gm_lang["actionlist"]);
			$submenu->addLink("actionlist.php");
			$menu->addSubmenu($submenu);
		}
		return $menu;
	}
	
	/**
	 * get the help menu
	 * @return Menu		the menu item
	 */
	function GetHelperMenu() {
		global $TEXT_DIRECTION, $GEDCOM, $gm_lang, $spider;
		global $SHOW_CONTEXT_HELP, $SCRIPT_NAME, $QUERY_STRING, $helpindex, $action;
		
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl"; else $ff="";
		//-- main help menu item
		$menu = new Menu($gm_lang["help_page"]);

		//-- help_for_this_page sub menu
		$submenu = new Menu($gm_lang["help_for_this_page"]);
		$submenu->addLink("window.open.help_help_".basename($SCRIPT_NAME)."&action=".$action);
		$menu->addSubmenu($submenu);
		
		//-- help_contents sub menu
		$submenu = new Menu($gm_lang["help_contents"]);
		$submenu->addLink("window.open.help_help_contents_help");
		$menu->addSubmenu($submenu);
		
		//-- faq sub menu
		if (file_exists("faq.php")) {
			$submenu = new Menu($gm_lang["faq_list"]);
			$submenu->addLink("faq.php");
			$menu->addSubmenu($submenu);
		}
		//-- searchhelp sub menu
		if (file_exists("searchhelp.php")) {
			$submenu = new Menu($gm_lang["hs_title"]);
			$submenu->addLink("window.open_index2.php?page=searchhelp");
			$menu->addSubmenu($submenu);
		}
		
		//-- add contact links to help menu
		$menu->addSeperator();
		$menuitems = print_contact_links(1);
		foreach($menuitems as $menuitem) {
			$submenu = new Menu($menuitem["label"]);
			$submenu->addLink($menuitem["link"]);
			$menu->addSubmenu($submenu);
		}
		
		//-- add show/hide context_help
		if (!$spider) {
			$menu->addSeperator();
			if ($_SESSION["show_context_help"]) {
				$submenu = new Menu($gm_lang["hide_contexthelp"]);
				$submenu->addLink($SCRIPT_NAME."?".$QUERY_STRING."&show_context_help=no");
			}
			else {
				$submenu = new Menu($gm_lang["show_contexthelp"]);
				$submenu->addLink($SCRIPT_NAME."?".$QUERY_STRING."&show_context_help=yes");
			}
			$menu->addSubmenu($submenu);
		}
		return $menu;
	}
	
	function GetThisPersonMenu(&$controller) {
		global $gm_lang, $ALLOW_EDIT_GEDCOM;
		
		//-- main edit menu item
		$menu = new Menu($gm_lang["this_individual"]);
		
		// Charts menu
		$submenu = $this->GetChartMenu($controller->pid);
		$menu->addSubmenu($submenu);
		
		// Reports menu
		if (file_exists("reports/individual.xml")) {
			$submenu = $this->GetReportMenu($controller->pid);
			$menu->addSubmenu($submenu);
		}
		
		// Edit menu
		if ($controller->show_menu_edit && $ALLOW_EDIT_GEDCOM) {
			$submenu = $controller->getEditMenu();
			$menu->addSubmenu($submenu);
		}
		
		// Other menu
		if ($controller->show_menu_other) {
			$submenu = $controller->getOtherMenu();
			$menu->addSubmenu($submenu);
		}
		
		return $menu;
	}
	
	function GetThisFamilyMenu(&$controller) {
		global $gm_lang, $Users, $ALLOW_EDIT_GEDCOM;
		
		if ($controller->exists) {
			//-- main edit menu item
			$menu = new Menu($gm_lang["this_family"]);
			
			// Charts menu
			$submenu = $controller->getChartsMenu();
			$menu->addSubmenu($submenu);
			
			// Reports menu
			$submenu = $controller->getReportsMenu();
			$menu->addSubmenu($submenu);
			
			// Edit menu
			if ($Users->userCanEdit($controller->uname) && $ALLOW_EDIT_GEDCOM) {
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
	
	function GetThisSourceMenu(&$source_controller) {
		global $gm_lang, $Users, $ALLOW_EDIT_GEDCOM;
		
		if (!$source_controller->isempty && !$source_controller->source->sourdeleted) {
			//-- main edit menu item
			$menu = new Menu($gm_lang["this_source"]);
			
			// Edit menu
			if ($Users->userCanEdit($source_controller->uname) && $ALLOW_EDIT_GEDCOM) {
				$submenu = $source_controller->getEditMenu();
				$menu->addSubmenu($submenu);
			}
			
			// Other menu
			if ($source_controller->display_other_menu) {
				$submenu = $source_controller->getOtherMenu();
				$menu->addSubmenu($submenu);
			}
			
			return $menu;
		}
		else return false;
	}
	
	function GetThisRepoMenu() {
		global $gm_lang, $rid, $ENABLE_CLIPPINGS_CART, $controller, $gm_username, $Users, $ALLOW_EDIT_GEDCOM, $show_changes;
		
		//-- main edit menu item
		$menu = new Menu($gm_lang["this_repository"]);
		
		// Edit menu
		if ($Users->userCanEdit($gm_username) && $ALLOW_EDIT_GEDCOM) {
			$submenu = new Menu($gm_lang["edit"]);
			$menu->addSubmenu($submenu);
		
			// Edit menu items
			// Edit raw record
			if ($Users->userCanEditGedlines()) {
				$submenu = new Menu($gm_lang["edit_raw"]);
				$submenu->addLink("edit_raw('$rid', 'edit_raw');");
				$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
			}
			
			// Delete repository
			$submenu = new Menu($gm_lang["delete_repo"]);
			$submenu->addLink("if (confirm('".$gm_lang["confirm_delete_repo"]."')) deleterepository('$rid', 'delete_repo');");
			$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
			
			if (GetChangeData(true, $rid, true)) {
//				// edit_sour / seperator
//				$submenu = new Menu();
//				$submenu->isSeperator();
//				$menu->addSubmenu($submenu);

				// edit_sour / show/hide changes
				if ($show_changes == "no") $submenu = new Menu($gm_lang['show_changes']);
				else $submenu = new Menu($gm_lang['hide_changes']);
				$submenu->addLink('showchanges();');
				$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
			}
		}
		
		if ($Users->userCanViewGedlines() || $ENABLE_CLIPPINGS_CART >= $Users->getUserAccessLevel()) {
			// Other menu
			$submenu = new Menu($gm_lang["other"]);
			$menu->addSubmenu($submenu);
			
				// Other menu items
				// Show Gedcom record
				if ($Users->userCanViewGedlines()) {
					$submenu = new Menu($gm_lang["view_gedcom"]);
					$submenu->addLink("show_gedcom_record();");
					$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
				}
				
				// Add to clippings cart
				if ($ENABLE_CLIPPINGS_CART >= $Users->getUserAccessLevel()) {
					$submenu = new Menu($gm_lang["add_to_cart"]);
					$submenu->addLink("clippings.php?action=add&id=$rid&type=repository");
					$menu->submenus[count($menu->submenus)-1]->submenus[]=$submenu;
				}
		}
		
		return $menu;
	}
	function GetThisMediaMenu(&$controller) {
		global $gm_lang, $Users, $ALLOW_EDIT_GEDCOM;
		
		if (!$controller->isempty && !$controller->media->mediadeleted) {
			//-- main edit menu item
			$menu = new Menu($gm_lang["this_media"]);
			
			// Edit menu
			if ($Users->userCanEdit($controller->uname) && $ALLOW_EDIT_GEDCOM) {
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
	function GetThisNoteMenu(&$controller) {
		global $gm_lang, $Users, $ALLOW_EDIT_GEDCOM;
		
		if (!$controller->isempty && !$controller->note->deleted) {
			//-- main edit menu item
			$menu = new Menu($gm_lang["this_note"]);
			
			// Edit menu
			if ($Users->userCanEdit($controller->uname) && $ALLOW_EDIT_GEDCOM) {
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