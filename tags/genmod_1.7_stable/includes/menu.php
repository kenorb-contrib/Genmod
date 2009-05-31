<?php
/**
 * System for generating menus.
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 Genmod Development Team
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
 * $Id: menu.php,v 1.13 2006/05/07 11:35:56 roland-d Exp $
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
	 * @param string $link		The link that the user should be taken to when clicking on the menuitem
	 * @param string $pos		The position of the label relative to the icon (right, left, top, bottom)
	 * @param string $flyout	The direction where any submenus should appear relative to the menu item (right, down) 
	 */
	function Menu($label=' ', $link='#', $pos='right', $flyout='down') {
		$this->submenus = array();
		$this->addLink($link);
		$this->addLabel($label, $pos);
		$this->addFlyout($flyout);
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

	function addIcon($icon, $hovericon=null) {
		if (file_exists($icon)) $this->icon = $icon;
		else $this->icon = null;
		if (file_exists($hovericon)) $this->hovericon = $hovericon;
		else $this->hovericon = null;
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
			$output = "<div id=\"menu{$menucount}\" class=\"seperator\">"
				."<img src=\"{$GM_IMAGE_DIR}/{$GM_IMAGES['hline']['other']}\" width=\"90%\" height=\"3\" alt=\"\" />"
				."</div>\n";
			return $output;
		}
		$c = count($this->submenus);
		$output = "<div id=\"menu{$menucount}\" style=\"clear: both;\" class=\"{$this->class}\">\n";
		if ($this->link=="#") $this->link = "javascript:;";
		$link = "<a href=\"{$this->link}\" onmouseover=\""
		;
		if ($c >= 0) $link .= "show_submenu('menu{$menucount}_subs', 'menu{$menucount}', '{$this->flyout}'); ";
		if ($this->hoverclass !== null) $link .= "change_class('menu{$menucount}', '{$this->hoverclass}'); ";
		if ($this->hovericon !== null) $link .= "change_icon('menu{$menucount}_icon', '{$this->hovericon}'); ";
		$link .= '" onmouseout="';
		if ($c >= 0) $link .= "timeout_submenu('menu{$menucount}_subs'); ";
		if ($this->hoverclass !== null) $link .= "change_class('menu{$menucount}', '{$this->class}'); ";
		if ($this->hovericon !== null) $link .= "change_icon('menu{$menucount}_icon', '{$this->icon}'); ";
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

class MenuBar {
	/**
	 * get the home menu
	 * @return Menu		the menu item
	 */
	function &getHomeMenu() {
		global $TEXT_DIRECTION, $GM_IMAGE_DIR, $GM_IMAGES, $HOME_SITE_URL, $HOME_SITE_TEXT, $gm_lang;
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl"; else $ff="";
		//-- main home menu item
		$menu = new Menu($HOME_SITE_TEXT, $HOME_SITE_URL, "down");
		if (!empty($GM_IMAGES["home"]["large"]))
			$menu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["home"]["large"]);
		$menu->addClass("menuitem$ff", "menuitem_hover$ff", "submenu$ff");
		return $menu;
	}

	/**
	 * get the menu with links to the gedcom portals
	 * @return Menu		the menu item
	 */
	function &getGedcomMenu() {
		global $GEDCOMS, $ALLOW_CHANGE_GEDCOM;
		global $TEXT_DIRECTION, $GM_IMAGE_DIR, $GM_IMAGES, $GEDCOM, $gm_lang;
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl"; else $ff="";
		//-- main menu
		$menu = new Menu($gm_lang["welcome_page"], "index.php?command=gedcom", "down");
		if (!empty($GM_IMAGES["gedcom"]["large"]))
			$menu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["gedcom"]["large"]);
		$menu->addClass("menuitem$ff", "menuitem_hover$ff", "submenu$ff");
		$menu->addAccesskey($gm_lang["accesskey_home_page"]);
		//-- gedcom list
		if ($ALLOW_CHANGE_GEDCOM && count($GEDCOMS)>1) {
			foreach($GEDCOMS as $ged=>$gedarray) {
				$submenu = new Menu(PrintReady($gedarray["title"]), "index.php?command=gedcom&amp;ged=$ged");
				if (!empty($GM_IMAGES["gedcom"]["small"]))
					$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["gedcom"]["small"]);
				$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
				$menu->addSubmenu($submenu);
			}
		}
		return $menu;
	}

	/**
	 * get the mygedview menu
	 * @return Menu		the menu item
	 */
	function &getMygedviewMenu() {
		global $GEDCOMS, $MEDIA_DIRECTORY, $MULTI_MEDIA;
		global $TEXT_DIRECTION, $GM_IMAGE_DIR, $GM_IMAGES, $GEDCOM, $gm_lang, $gm_username;
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl"; else $ff="";
		//-- main menu
		$menu = new Menu($gm_lang["mygedview"], "index.php?command=user", "down");
		if (!empty($GM_IMAGES["mygedview"]["large"]))
			$menu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["mygedview"]["large"]);
		else if (!empty($GM_IMAGES["gedcom"]["large"]))
			$menu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["gedcom"]["large"]);
		$menu->addClass("menuitem$ff", "menuitem_hover$ff", "submenu$ff");
		$menu->addAccesskey($gm_lang["accesskey_home_page"]);

		$username = $gm_username;
		if (!empty($username)) {
			$user = GetUser($username);
			//-- mygedview submenu
			$submenu = new Menu($gm_lang["mgv"], "index.php?command=user");
			if (!empty($GM_IMAGES["mygedview"]["small"]))
				$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["mygedview"]["small"]);
			$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
			$menu->addSubmenu($submenu);
			//-- editaccount submenu
			if ($user["editaccount"]) {
				$submenu = new Menu($gm_lang["editowndata"], "edituser.php");
				if (!empty($GM_IMAGES["mygedview"]["small"]))
					$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["mygedview"]["small"]);
				$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
				$menu->addSubmenu($submenu);
			}
			if (!empty($user["gedcomid"][$GEDCOM])) {
				//-- quick_update submenu
				$submenu = new Menu($gm_lang["quick_update_title"], "#");
				$submenu->addOnclick("return quickEdit('".$user["gedcomid"][$GEDCOM]."');");
				if (!empty($GM_IMAGES["indis"]["small"]))
					$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]);
				$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
				$menu->addSubmenu($submenu);
				//-- my_pedigree submenu
				$submenu = new Menu($gm_lang["my_pedigree"], "pedigree.php?rootid=".$user["gedcomid"][$GEDCOM]);
				if (!empty($GM_IMAGES["pedigree"]["small"]))
					$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["pedigree"]["small"]);
				//$submenu->addIcon($GM_IMAGE_DIR."/small/pedigree.gif");
				$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
				$menu->addSubmenu($submenu);
				//-- my_indi submenu
				$submenu = new Menu($gm_lang["my_indi"], "individual.php?pid=".$user["gedcomid"][$GEDCOM]);
				if (!empty($GM_IMAGES["indis"]["small"]))
					$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]);
				$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
				$menu->addSubmenu($submenu);
			}
			if ((userIsAdmin($username)) || (userGedcomAdmin($username, $GEDCOM))){
				$menu->addSeperator();
				//-- admin submenu
				$submenu = new Menu($gm_lang["admin"], "admin.php");
				if (!empty($GM_IMAGES["admin"]["small"]))
					$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["admin"]["small"]);
				$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
				$menu->addSubmenu($submenu);
				//-- manage_gedcoms submenu
				$submenu = new Menu($gm_lang["manage_gedcoms"], "editgedcoms.php");
				if (!empty($GM_IMAGES["admin"]["small"]))
					$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["admin"]["small"]);
				$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
				$menu->addSubmenu($submenu);
				//-- user_admin submenu
				$submenu = new Menu($gm_lang["user_admin"], "useradmin.php");
				if (!empty($GM_IMAGES["admin"]["small"]))
					$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["admin"]["small"]);
				$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
				$menu->addSubmenu($submenu);
				//-- upload_media submenu
			   if (is_writable($MEDIA_DIRECTORY) && $MULTI_MEDIA) {
					$submenu = new Menu($gm_lang["upload_media"], "uploadmedia.php");
					if (!empty($GM_IMAGES["media"]["small"]))
						$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["media"]["small"]);
					$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
					$menu->addSubmenu($submenu);
				}
			}
			else if (userCanEdit($username)) {
				//-- upload_media submenu
			   if (is_writable($MEDIA_DIRECTORY) && $MULTI_MEDIA) {
					$menu->addSeperator();
					$submenu = new Menu($gm_lang["upload_media"], "uploadmedia.php");
					if (!empty($GM_IMAGES["menu_media"]["small"]))
						$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["menu_media"]["small"]);
					$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
					$menu->addSubmenu($submenu);
				}
			}
		}
		return $menu;
	}
	/**
	 * Get the links to the custom pages created by the user
	 * @return Menu		the menu item
	 */
	function getCustomMenu() {
		global $TOTAL_QUERIES, $TBLPREFIX, $gm_lang, $GM_IMAGE_DIR, $GM_IMAGES, $TEXT_DIRECTION, $CONFIGURED;
		
		// NOTE: Check if table exists, if not, do print the menu
		$sql = "SHOW TABLES LIKE '".$TBLPREFIX."pages'";
		$res = mysql_query($sql);
		$TOTAL_QUERIES++;
		if ($res) {
			if ($TEXT_DIRECTION=="rtl") $ff="_rtl";
			else $ff="";
			
			// Retrieve the current pages stored in the DB
			$sql = "SELECT * FROM ".$TBLPREFIX."pages";
			$result = mysql_query($sql);
			if (!$result) {
				$message  = 'Invalid query: ' . mysql_error() . "\n";
				$message .= 'Whole query: ' . $sql;
				die($message);
			}
			else {
				$pages = array();
				while ($row = mysql_fetch_assoc($result)) {
					$page = array();
					$page["id"] = $row["pag_id"];
					$page["html"] = $row["pag_content"];
					$page["title"] = $row["pag_title"];
					$pages[$row["pag_id"]] = $page;
				}
			}
				
			//-- My Pages
			$menu = new Menu($gm_lang["my_pages"], "", "down");
			$menu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["gedcom"]["large"]);
			$menu->addClass("menuitem","menuitem_hover","submenu");
			if (count($pages) > 0) {	 
				foreach ( $pages as $key => $page) {
					$submenu = new Menu($page["title"], "");
					$submenu->addFlyout("right");
					$submenu->addLink("custompage.php?id=".$page["id"]);
					$submenu->addClass("submenuitem$ff","submenuitem_hover$ff");
					$menu->addSubmenu($submenu);
				}
			}
			if (userIsAdmin(getUserName())) {
				$submenu = new Menu($gm_lang["edit_pages"], "");
				$submenu->addFlyout("right");
				$submenu->addLink("custompage.php");
				$submenu->addClass("submenuitem$ff","submenuitem_hover$ff");
				$menu->addSubmenu($submenu);
			}
			return $menu;
		}
		return null;
	}
	
	/**
	 * get the menu for the charts
	 * @return Menu		the menu item
	 */
	function &getChartsMenu($rootid='',$myid='') {
		global $TEXT_DIRECTION, $GM_IMAGE_DIR, $GM_IMAGES, $GEDCOM, $gm_lang, $gm_username;
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl"; else $ff="";
		//-- main charts menu item
		$link = "pedigree.php";
		if ($rootid) {
			$link .= "?rootid=".$rootid;
			$menu = new Menu($gm_lang["charts"], $link);
			if (!empty($GM_IMAGES["pedigree"]["small"]))
				$menu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["pedigree"]["small"]);
			$menu->addClass("submenuitem$ff", "submenuitem_hover$ff", "submenu$ff");
		}
		else {
			// top menubar
			$menu = new Menu($gm_lang["charts"], $link, "down");
			if (!empty($GM_IMAGES["pedigree"]["large"]))
				$menu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["pedigree"]["large"]);
			$menu->addClass("menuitem$ff", "menuitem_hover$ff", "submenu$ff");
		}
		//-- pedigree sub menu
		$submenu = new Menu($gm_lang["pedigree_chart"], $link);
		if (!empty($GM_IMAGES["pedigree"]["small"]))
			$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["pedigree"]["small"]);
		$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
		$menu->addSubmenu($submenu);
		//-- descendancy sub menu
		if (file_exists("descendancy.php")) {
			$link = "descendancy.php";
			if ($rootid) $link .= "?pid=".$rootid;
			$submenu = new Menu($gm_lang["descend_chart"], $link);
			if (!empty($GM_IMAGES["descendant"]["small"]))
				$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["descendant"]["small"]);
			$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
			$menu->addSubmenu($submenu);
		}
		//-- ancestry submenu
		if (file_exists("ancestry.php")) {
			$link = "ancestry.php";
			if ($rootid) $link .= "?rootid=".$rootid;
			$submenu = new Menu($gm_lang["ancestry_chart"], $link);
			if (!empty($GM_IMAGES["ancestry"]["small"]))
				$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["ancestry"]["small"]);
			$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
			$menu->addSubmenu($submenu);
		}
		//-- fan chart submenu
		if (file_exists("fanchart.php") and function_exists("imagettftext")) {
			$link = "fanchart.php";
			if ($rootid) $link .= "?rootid=".$rootid;
			$submenu = new Menu($gm_lang["fan_chart"], $link);
			if (!empty($GM_IMAGES["fanchart"]["small"]))
				$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["fanchart"]["small"]);
			$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
			$menu->addSubmenu($submenu);
		}
		//-- hourglass submenu
		if (file_exists("hourglass.php")) {
			$link = "hourglass.php";
			if ($rootid) $link .= "?pid=".$rootid;
			$submenu = new Menu($gm_lang["hourglass_chart"], $link);
			if (!empty($GM_IMAGES["hourglass"]["small"]))
				$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["hourglass"]["small"]);
			$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
			$menu->addSubmenu($submenu);
		}
		//-- familybook submenu
		if (file_exists("familybook.php")) {
			$link = "familybook.php";
			if ($rootid) $link .= "?pid=".$rootid;
			$submenu = new Menu($gm_lang["familybook_chart"], $link);
			if (!empty($GM_IMAGES["fambook"]["small"]))
				$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["fambook"]["small"]);
			$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
			$menu->addSubmenu($submenu);
		}
		//-- timeline chart submenu
		if (file_exists("timeline.php")) {
			$link = "timeline.php";
			if ($rootid) $link .= "?pids[]=".$rootid;
			$submenu = new Menu($gm_lang["timeline_chart"], $link);
			if (!empty($GM_IMAGES["timeline"]["small"]))
				$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["timeline"]["small"]);
			$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
			$menu->addSubmenu($submenu);
		}
		//-- relationship submenu
		if (file_exists("relationship.php")) {
			if ($rootid and empty($myid)) {
				$username = $gm_username;
				if (!empty($username)) {
					$user = GetUser($username);
					$myid = @$user["gedcomid"][$GEDCOM];
				}
			}
			if (($myid and $myid!=$rootid) or empty($rootid)) {
				$link = "relationship.php";
				if ($rootid) {
					$link .= "?pid1=".$myid."&amp;pid2=".$rootid;
					$submenu = new Menu($gm_lang["relationship_to_me"], $link);
				} else {
					$submenu = new Menu($gm_lang["relationship_chart"], $link);
				}
				if (!empty($GM_IMAGES["relationship"]["small"]))
					$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["relationship"]["small"]);
				$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
				$menu->addSubmenu($submenu);
			}
		}
		return $menu;
	}

	/**
	 * get the menu for the lists
	 * @return Menu		the menu item
	 */
	function &getListsMenu() {
		global $TEXT_DIRECTION, $GM_IMAGE_DIR, $GM_IMAGES, $GEDCOM, $gm_lang;
		global $SHOW_SOURCES, $MULTI_MEDIA, $gm_username;
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl"; else $ff="";
		//-- main lists menu item
		$menu = new Menu($gm_lang["lists"], "indilist.php", "down");
		if (!empty($GM_IMAGES["indis"]["large"]))
			$menu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["large"]);
		$menu->addClass("menuitem$ff", "menuitem_hover$ff", "submenu$ff");
		//-- indi list sub menu
		$submenu = new Menu($gm_lang["individual_list"], "indilist.php");
		if (!empty($GM_IMAGES["indis"]["small"]))
			$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]);
		$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
		$menu->addSubmenu($submenu);
		//-- famlist sub menu
		if (file_exists("famlist.php")) {
			$submenu = new Menu($gm_lang["family_list"], "famlist.php");
			if (!empty($GM_IMAGES["cfamily"]["small"]))
				$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["cfamily"]["small"]);
			$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
			$menu->addSubmenu($submenu);
		}
		//-- source
		if (file_exists("sourcelist.php") && $SHOW_SOURCES>=getUserAccessLevel($gm_username)) {
			$submenu = new Menu($gm_lang["source_list"], "sourcelist.php");
			if (!empty($GM_IMAGES["source"]["small"]))
				$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["source"]["small"]);
			$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
			$menu->addSubmenu($submenu);
		}
		//-- repository
		if (file_exists("repolist.php")) {
			$submenu = new Menu($gm_lang["repo_list"], "repolist.php");
			if (!empty($GM_IMAGES["repository"]["small"]))
				$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["repository"]["small"]);
			$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
			$menu->addSubmenu($submenu);
		}
		//-- places
		if (file_exists("placelist.php")) {
			$submenu = new Menu($gm_lang["place_list"], "placelist.php");
			if (!empty($GM_IMAGES["place"]["small"]))
				$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["place"]["small"]);
			$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
			$menu->addSubmenu($submenu);
		}
		//-- medialist
		if (file_exists("medialist.php") and $MULTI_MEDIA) {
			$submenu = new Menu($gm_lang["media_list"], "medialist.php");
			if (!empty($GM_IMAGES["media"]["small"]))
				$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["media"]["small"]);
			$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
			$menu->addSubmenu($submenu);
		}
		//-- list most ancient parent of a family
		if (file_exists("patriarchlist.php")) {
			$submenu = new Menu($gm_lang["patriarch_list"], "patriarchlist.php");
			if (!empty($GM_IMAGES["patriarch"]["small"]))
				$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["patriarch"]["small"]);
			$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
			$menu->addSubmenu($submenu);
		}
		//-- aliveinyear
		if (file_exists("aliveinyear.php")) {
			$submenu = new Menu($gm_lang["alive_in_year"], "aliveinyear.php");
			if (!empty($GM_IMAGES["indis"]["small"]))
				$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]);
			$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
			$menu->addSubmenu($submenu);
		}
		// NOTE: Unlinked individuals and families
		if (file_exists("unlinked.php")) {
			$submenu = new Menu($gm_lang["unlink_list"], "unlinked.php");
			if (!empty($GM_IMAGES["indis"]["small"]))
				$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["indis"]["small"]);
			$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
			$menu->addSubmenu($submenu);
		}
		//-- produce a plot of statistics
		if (file_exists("statistics.php") && file_exists("jpgraph")) {
			$submenu = new Menu($gm_lang["statistics"], "statistics.php");
			if (!empty($GM_IMAGES["statistic"]["small"]))
				$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["statistic"]["small"]);
			$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
			$menu->addSubmenu($submenu);
		}
		return $menu;
	}

	/**
	 * get the menu for the calendar
	 * @return Menu		the menu item
	 */
	function &getCalendarMenu() {
		global $TEXT_DIRECTION, $GM_IMAGE_DIR, $GM_IMAGES, $GEDCOM, $gm_lang;
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl"; else $ff="";
		if (!file_exists("calendar.php")) return null;
		//-- main calendar menu item
		$menu = new Menu($gm_lang["anniversary_calendar"], "calendar.php", "down");
		if (!empty($GM_IMAGES["calendar"]["large"]))
			$menu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["calendar"]["large"]);
		$menu->addClass("menuitem$ff", "menuitem_hover$ff", "submenu$ff");
		//-- viewday sub menu
		$submenu = new Menu($gm_lang["viewday"], "calendar.php");
		if (!empty($GM_IMAGES["calendar"]["small"]))
			$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["calendar"]["small"]);
		$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
		$menu->addSubmenu($submenu);
		//-- viewmonth sub menu
		$submenu = new Menu($gm_lang["viewmonth"], "calendar.php?action=calendar");
		if (!empty($GM_IMAGES["calendar"]["small"]))
			$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["calendar"]["small"]);
		$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
		$menu->addSubmenu($submenu);
		//-- viewyear sub menu
		$submenu = new Menu($gm_lang["viewyear"], "calendar.php?action=year");
		if (!empty($GM_IMAGES["calendar"]["small"]))
			$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["calendar"]["small"]);
		$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
		$menu->addSubmenu($submenu);
		return $menu;
	}

	/**
	 * get the reports menu
	 * @return Menu		the menu item
	 */
	function &getReportsMenu($pid="", $famid="") {
		global $TEXT_DIRECTION, $GM_IMAGE_DIR, $GM_IMAGES, $GEDCOMS, $GEDCOM, $gm_lang;
		global $LANGUAGE, $PRIV_PUBLIC, $PRIV_USER, $PRIV_NONE, $PRIV_HIDE, $gm_username;
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl"; else $ff="";
		if (!file_exists("reportengine.php")) return null;
		//-- main reports menu item
		if ($pid or $famid) {
			$menu = new Menu($gm_lang["reports"], "#");
			if (!empty($GM_IMAGES["reports"]["small"]))
				$menu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["reports"]["small"]);
			$menu->addClass("submenuitem$ff", "submenuitem_hover$ff", "submenu$ff");
		}
		else {
			// top menubar
			$menu = new Menu($gm_lang["reports"], "reportengine.php", "down");
			if (!empty($GM_IMAGES["reports"]["large"]))
				$menu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["reports"]["large"]);
			$menu->addClass("menuitem$ff", "menuitem_hover$ff", "submenu$ff");
		}
		//-- reports submenus
		$reports = get_report_list();
		$username = $gm_username;
		foreach($reports as $file=>$report) {
			if (!isset($report["access"])) $report["access"] = $PRIV_PUBLIC;
			if ($report["access"]>=getUserAccessLevel($username)) {
				if (!empty($report["title"][$LANGUAGE])) $label = $report["title"][$LANGUAGE];
				else $label = implode("", $report["title"]);
				// indi report
				if ($pid) $submenu = new Menu($label, "reportengine.php?action=setup&amp;report=".$report["file"]."&amp;pid=".$pid);
				// family report
				else if ($famid) $submenu = new Menu($label, "reportengine.php?action=setup&amp;report=".$report["file"]."&amp;famid=".$famid);
				// default
				else $submenu = new Menu($label, "reportengine.php?action=setup&amp;report=".$report["file"]);
				if (isset($GM_IMAGES["reports"]["small"]) and isset($GM_IMAGES[$report["icon"]]["small"])) $iconfile=$GM_IMAGE_DIR."/".$GM_IMAGES[$report["icon"]]["small"];
				if (isset($iconfile) && file_exists($iconfile)) $submenu->addIcon($iconfile);
				$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
				// indi report
				if ($pid and $report["icon"]!="sfamily" and $report["icon"]!="place") $menu->addSubmenu($submenu);
				// family report
				else if ($famid and $report["icon"]=="sfamily") $menu->addSubmenu($submenu);
				// default
				else if (empty($pid) and empty($famid)) $menu->addSubmenu($submenu);
			}
		}
		return $menu;
	}

	/**
	 * @todo put back the ampersand. It causes an error at the moment.
	 * get the clipping menu
	 * @return Menu		the menu item
	 */
	function getClippingsMenu() {
		global $ENABLE_CLIPPINGS_CART;
		global $TEXT_DIRECTION, $GM_IMAGE_DIR, $GM_IMAGES, $GEDCOM, $gm_lang;
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl"; else $ff="";
		if (!file_exists("clippings.php")) return null;
		if ($ENABLE_CLIPPINGS_CART < getUserAccessLevel()) return null;
		//-- main clippings menu item
		$menu = new Menu($gm_lang["clippings_cart"], "clippings.php", "down");
		if (!empty($GM_IMAGES["clippings"]["large"]))
			$menu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["clippings"]["large"]);
		$menu->addClass("menuitem$ff", "menuitem_hover$ff", "submenu$ff");
		return $menu;
	}

	/**
	 * get the print_preview menu
	 * @return Menu		the menu item
	 */
	function &getPreviewMenu() {
		global $TEXT_DIRECTION, $GM_IMAGE_DIR, $GM_IMAGES, $SCRIPT_NAME, $gm_lang;
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl"; else $ff="";
		//-- main print_preview menu item
		$menu = new Menu($gm_lang["print_preview"], $SCRIPT_NAME."?view=preview", "down");
		if (!empty($GM_IMAGES["printer"]["large"]))
			$menu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["printer"]["large"]);
		$menu->addClass("menuitem$ff", "menuitem_hover$ff", "submenu$ff");
		return $menu;
	}

	/**
	 * get the search menu
	 * @return Menu		the menu item
	 */
	function &getSearchMenu() {
		global $TEXT_DIRECTION, $GM_IMAGE_DIR, $GM_IMAGES, $GEDCOM, $gm_lang;
		global $SHOW_MULTISITE_SEARCH;
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl"; else $ff="";
		if (!file_exists("search.php")) return null;
		//-- main search menu item
		$menu = new Menu($gm_lang["search"], "search.php", "down");
		if (!empty($GM_IMAGES["search"]["large"]))
			$menu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["search"]["large"]);
		$menu->addClass("menuitem$ff", "menuitem_hover$ff", "submenu$ff");
		//-- search_general sub menu
		$submenu = new Menu($gm_lang["search_general"], "search.php?action=general");
		if (!empty($GM_IMAGES["search"]["small"]))
			$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["search"]["small"]);
		$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
		$menu->addSubmenu($submenu);
		//-- search_soundex sub menu
		$submenu = new Menu($gm_lang["search_soundex"], "search.php?action=soundex");
		if (!empty($GM_IMAGES["search"]["small"]))
			$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["search"]["small"]);
		$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
		$menu->addSubmenu($submenu);
		return $menu;
	}

	/**
	 * get the help menu
	 * @return Menu		the menu item
	 */
	function &getHelpMenu() {
		global $TEXT_DIRECTION, $GM_IMAGE_DIR, $GM_IMAGES, $GEDCOM, $gm_lang;
		global $SHOW_CONTEXT_HELP, $SCRIPT_NAME, $QUERY_STRING, $helpindex, $action;
		if ($TEXT_DIRECTION=="rtl") $ff="_rtl"; else $ff="";
		//-- main help menu item
		$menu = new Menu($gm_lang["help_page"], "#", "down");
		if (!empty($GM_IMAGES["help"]["large"]))
			$menu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["help"]["large"]);
		if (empty($helpindex))
			$menu->addOnclick("return helpPopup('help_".basename($SCRIPT_NAME)."&amp;action=".$action."');");
		else
			$menu->addOnclick("return helpPopup('".$helpindex."');");
		$menu->addClass("menuitem$ff", "menuitem_hover$ff", "submenu$ff");

		//-- help_for_this_page sub menu
		$submenu = new Menu($gm_lang["help_for_this_page"], "#");
		if (!empty($GM_IMAGES["help"]["small"]))
			$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["help"]["small"]);
		$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
		if (empty($helpindex))
			$submenu->addOnclick("return helpPopup('help_".basename($SCRIPT_NAME)."&amp;action=".$action."');");
		else
			$submenu->addOnclick("return helpPopup('".$helpindex."');");
		$menu->addSubmenu($submenu);
		//-- help_contents sub menu
		$submenu = new Menu($gm_lang["help_contents"], "#");
		if (!empty($GM_IMAGES["help"]["small"]))
			$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["help"]["small"]);
		$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
		$submenu->addOnclick("return helpPopup('help_contents_help');");
		$menu->addSubmenu($submenu);
		//-- faq sub menu
		if (file_exists("faq.php")) {
			$submenu = new Menu($gm_lang["faq_list"], "faq.php");
			if (!empty($GM_IMAGES["help"]["small"]))
				$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["help"]["small"]);
			$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
			$menu->addSubmenu($submenu);
		}
		//-- searchhelp sub menu
		if (file_exists("searchhelp.php")) {
			$submenu = new Menu($gm_lang["hs_title"], "#");
			if (!empty($GM_IMAGES["search"]["small"]))
				$submenu->addIcon($GM_IMAGE_DIR."/".$GM_IMAGES["search"]["small"]);
			$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
			$submenu->addOnclick("window.open('searchhelp.php', '', 'top=50,left=10,width=700,height=600,scrollbars=1,resizable=1');");
			$menu->addSubmenu($submenu);
		}
		
		//-- add contact links to help menu
		$menu->addSeperator();
		$menuitems = print_contact_links(1);
		foreach($menuitems as $menuitem) {
			$submenu = new Menu($menuitem["label"], $menuitem["link"]);
			$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
			if (!empty($menuitem["onclick"])) $submenu->addOnclick($menuitem["onclick"]);
			$menu->addSubmenu($submenu);
		}
		//-- add show/hide context_help
		$menu->addSeperator();
		if ($_SESSION["show_context_help"])
			$submenu = new Menu($gm_lang["hide_contexthelp"], "$SCRIPT_NAME?$QUERY_STRING&amp;show_context_help=no");
		else
			$submenu = new Menu($gm_lang["show_contexthelp"], "$SCRIPT_NAME?$QUERY_STRING&amp;show_context_help=yes");
		$submenu->addClass("submenuitem$ff", "submenuitem_hover$ff");
		$menu->addSubmenu($submenu);
		return $menu;
	}
}
?>
