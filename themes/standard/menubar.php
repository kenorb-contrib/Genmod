<?php
/**
 * The menubar that appears at the top of all screens
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 - 2012 Genmod Development Team
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
 * @version $Id: menubar.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * security check to prevent hackers from directly accessing this file
 */
if (strstr($_SERVER["PHP_SELF"],"menubar.php")) {
	print "Why do you want to do that?";
	exit;
}
global $controller, $note_controller, $source_controller, $repository_controller, $media_controller;
$filemenu = MenuBar::GetFileMenu();
$editmenu = MenuBar::GetEditMenu();
$viewmenu = MenuBar::GetViewMenu();
$chartmenu = MenuBar::GetChartsMenu();
if (isset($note_controller) && $note_controller->classname == "NoteController" && is_object($note_controller->note)) $notemenu = MenuBar::GetThisNoteMenu($note_controller);
else $notemenu = "";
if (isset($controller) && $controller->classname == "IndividualController") $personmenu = MenuBar::GetThisPersonMenu($controller);
else $personmenu = "";
if (isset($controller) && $controller->classname == "FamilyController") $familymenu = MenuBar::GetThisFamilyMenu($controller);
else $familymenu = "";
if (isset($source_controller) && $source_controller->classname == "SourceController") $sourcemenu = MenuBar::GetThisSourceMenu($source_controller);
else $sourcemenu = "";
if (isset($repository_controller) && $repository_controller->classname == "RepositoryController") $repomenu = MenuBar::GetThisRepoMenu($repository_controller);
else $repomenu = "";
if (isset($media_controller) && $media_controller->classname == "MediaController") $mediamenu = MenuBar::GetThisMediaMenu($media_controller);
else $mediamenu = "";
$reportmenu = MenuBar::GetReportMenu();
$listmenu = MenuBar::GetListMenu();
$helpmenu = MenuBar::GetHelperMenu();
$favoritesmenu = MenuBar::GetFavoritesMenu();
$custommenu = MenuBar::GetCustomMenu();

function CreateMenu($menuobject, $level=0, $sub=false) {
	global $outputmenu, $GM_IMAGES;
	
	if (!$sub) $outputmenu = array();
	foreach ($menuobject->submenus as $sublevel => $submenu) {
		if (count($submenu->submenus) > 0) {
			foreach ($submenu->submenus as $key => $lowsub) {
				$tempmenu = CreateMenu($lowsub, $level++, true);
				if ($lowsub->seperator) $tempmenu->label = "<div class='seperator'>"."</div>";
				$outputmenu[$submenu->label][$key]["label"] = $tempmenu->label;
				$outputmenu[$submenu->label][$key]["link"] = $tempmenu->link;
			}
		}
		if (!isset($outputmenu[$submenu->label])) {
			$outputmenu[$submenu->label] = $submenu->link;
		}
	}
	if ($sub) return $menuobject;
	return $outputmenu;
}

if (is_object($filemenu)) $showmenu[GM_LANG_menu_file] = CreateMenu($filemenu);
if (is_object($editmenu)) $showmenu[GM_LANG_menu_edit] = CreateMenu($editmenu);
if (is_object($viewmenu)) $showmenu[GM_LANG_menu_view] = CreateMenu($viewmenu);
if (is_object($chartmenu)) $showmenu[GM_LANG_menu_charts] = CreateMenu($chartmenu);
if (is_object($listmenu)) $showmenu[GM_LANG_menu_lists] = CreateMenu($listmenu);
if (is_object($reportmenu)) $showmenu[GM_LANG_menu_reports] = CreateMenu($reportmenu);
if (is_object($favoritesmenu)) $showmenu[GM_LANG_menu_favorites] = CreateMenu($favoritesmenu);
if (is_object($personmenu)) $showmenu[GM_LANG_this_individual] = CreateMenu($personmenu);
if (is_object($familymenu)) $showmenu[GM_LANG_this_family] = CreateMenu($familymenu);
if (is_object($sourcemenu)) $showmenu[GM_LANG_this_source] = CreateMenu($sourcemenu);
if (is_object($mediamenu)) $showmenu[GM_LANG_this_media] = CreateMenu($mediamenu);
if (is_object($notemenu)) $showmenu[GM_LANG_this_note] = CreateMenu($notemenu);
if (is_object($repomenu)) $showmenu[GM_LANG_this_repository] = CreateMenu($repomenu);
if (is_object($helpmenu)) $showmenu[GM_LANG_helpmenu] = CreateMenu($helpmenu);
if (is_object($custommenu)) $showmenu[GM_LANG_my_pages] = CreateMenu($custommenu);
?>
<div id="HeaderSection" class="<?php echo $TEXT_DIRECTION; ?>">
	<script type="text/javascript" src="transmenu.js"></script>
	<script type="text/javascript">
	<!--
		function init() {
			//==========================================================================================
			// if supported, initialize TransMenus
			//==========================================================================================
			// Check isSupported() so that menus aren't accidentally sent to non-supporting browsers.
			// This is better than server-side checking because it will also catch browsers which would
			// normally support the menus but have javascript disabled.
			//
			// If supported, call initialize() and then hook whatever image rollover code you need to do
			// to the .onactivate and .ondeactivate events for each menu.
			//==========================================================================================
			if (TransMenu.isSupported()) {
				TransMenu.initialize();
	
				// hook all the highlight swapping of the main toolbar to menu activation/deactivation
				// instead of simple rollover to get the effect where the button stays hightlit until
				// the menu is closed.
				<?php foreach (array_keys($showmenu) as $number => $name) { 
					$num = $number + 1;?>
					menu<?php echo $number+1 ?>.onactivate = function() { document.getElementById("<?php echo "menutitle".$num; ?>").className = "hover"; };
					menu<?php echo $number+1 ?>.ondeactivate = function() { document.getElementById("<?php echo "menutitle".$num; ?>").className = ""; };
				<?php } ?>
			}
		}
	//-->
	</script>
	<?php
	global $GEDCOMS, $GM_IMAGES, $gm_user;
	if ($TEXT_DIRECTION == "ltr") {
		$rdir = "right";
		$ldir = "left";
	}
	else {
		$rdir = "left";
		$ldir = "right";
	}
	?>
	<div id="HeaderMenuContainer" class="<?php echo $TEXT_DIRECTION; ?>">
		<?php if (isset($GEDCOMS[GedcomConfig::$GEDCOMID])) { ?>
			<div id="HeaderLogoLink">
			<a href="index.php?command=gedcom">
			<img src="<?php print GM_IMAGE_DIR."/".$GM_IMAGES['gedcom']['small']; ?>" alt="<?php print $GEDCOMS[GedcomConfig::$GEDCOMID]['title']; ?>" title="<?php print $GEDCOMS[GedcomConfig::$GEDCOMID]['title']; ?>" />
			</a>
			</div>
		<?php } 
		else { ?>
			<div id="HeaderLogo"></div>
		<?php } ?>
		<div id="HeaderMenuBar" style="float: <?php print $ldir; ?>;">
			<?php foreach (array_keys($showmenu) as $number => $name) { 
				$num = $number + 1;?>
			<a id="<?php echo "menutitle".$num; ?>" href="#"><?php echo $name; ?></a> 
				&nbsp;&nbsp;
			<?php } ?>
		</div>
		<div id="HeaderLinkBar">
			<?php 
			if (isset($gm_user->gedcomid[GedcomConfig::$GEDCOMID]) && !empty($gm_user->gedcomid[GedcomConfig::$GEDCOMID])) {
				$person =& Person::GetInstance($gm_user->gedcomid[GedcomConfig::$GEDCOMID], "", GedcomConfig::$GEDCOMID);
				if ($person->disp_name) print "<a href=\"individual.php?pid=".$gm_user->gedcomid[GedcomConfig::$GEDCOMID]."&amp;gedid=".GedcomConfig::$GEDCOMID."\">".$gm_user->firstname.' '.$gm_user->lastname."</a><br />";
			}
			else echo $gm_user->firstname.' '.$gm_user->lastname.'<br />';
			echo "<a href=\"".GedcomConfig::$HOME_SITE_URL."\">".GedcomConfig::$HOME_SITE_TEXT."</a>";
			?>
		</div>
	</div>
	<br class="ClearBoth" />
	<div id="HeaderQuickSearch">
		<form action="search.php" method="get" name="searchformtop">
			<input type="hidden" name="action" value="general" />
			<input type="hidden" name="topsearch" value="yes" />
			<input class="search" type="text" name="query" accesskey="<?php print GM_LANG_accesskey_search?>" size="25" onfocus="document.searchformtop.query.size=55;" onblur="document.searchformtop.query.size=25;" />
			<input type="submit" value="<?php print GM_LANG_search;?>" />
		</form>
	</div>
	<br class="ClearBoth" />
	
	<script type="text/javascript">
	<!--
	// set up drop downs anywhere in the body of the page. I think the bottom of the page is better.. 
	// but you can experiment with effect on loadtime.
	if (TransMenu.isSupported()) {

		//==================================================================================================
		// create a set of dropdowns
		//==================================================================================================
		// the first param should always be down, as it is here
		//
		// The second and third param are the top and left offset positions of the menus from their actuators
		// respectively. To make a menu appear a little to the left and bottom of an actuator, you could use
		// something like -5, 5
		//
		// The last parameter can be .topLeft, .bottomLeft, .topRight, or .bottomRight to inidicate the corner
		// of the actuator from which to measure the offset positions above. Here we are saying we want the 
		// menu to appear directly below the bottom left corner of the actuator
		//==================================================================================================
		var ms = new TransMenuSet(TransMenu.direction.down, -10, 3, TransMenu.reference.bottomLeft);

		//==================================================================================================
		// create a dropdown menu
		//==================================================================================================
		// the first parameter should be the HTML element which will act actuator for the menu
		//==================================================================================================
		<?php
		
		$menubar = "";
		$menuitems = "";
		$menusubitems = "";
		$maintitlecount = 1;
		$submenucount = 0;
		$link = "";
		$pass = false;
		foreach ($showmenu as $maintitle => $submenu) {
			$menubar .= 'var menu'.$maintitlecount.' = ms.addMenu(document.getElementById("menutitle'.$maintitlecount.'"));'.chr(10);
			$itemcount = 0;
			foreach ($submenu as $subtitle => $subitems) {
				if (isset($subitems) && !is_array($subitems)) $link = $subitems;
				else $link = "";
				$menuitems .= 'menu'.$maintitlecount.'.addItem("'.$subtitle.'", "'.$link.'");'.chr(10);
				if (is_array($subitems)) {
					foreach ($subitems as $key => $label) {
						if (!$pass) {
							$menusubitems .= 'var submenu'.$submenucount.' = menu'.$maintitlecount.'.addMenu(menu'.$maintitlecount.'.items['.$itemcount.']);';
							$pass = true;
						}
						$menusubitems .= 'submenu'.$submenucount.'.addItem("'.$label["label"].'", "'.$label["link"].'");'.chr(10);
					}
					$pass = false;
					$menusubitems .= chr(13);
					$submenucount++;
				}
				$itemcount++;
			}
			$maintitlecount++;
		}
		echo $menubar;
		echo $menuitems;
		echo $menusubitems;
		?>
		/**
		var submenu00 = submenu0.addMenu(submenu0.items[0]);
	    submenu00.addItem("foo");
	    submenu00.addItem("bar");
	    **/
		//==================================================================================================

		//==================================================================================================
		// write drop downs into page
		//==================================================================================================
		// this method writes all the HTML for the menus into the page with document.write(). It must be
		// called within the body of the HTML page.
		//==================================================================================================
		TransMenu.renderAll();
	}
	//-->
	</script>
</div>
