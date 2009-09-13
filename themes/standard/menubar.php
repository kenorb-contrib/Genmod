<?php
/**
 * The menubar that appears at the top of all screens
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
 * @version $Id$
 */

/**
 * security check to prevent hackers from directly accessing this file
 */
if (strstr($_SERVER["PHP_SELF"],"menubar.php")) {
	print "Why do you want to do that?";
	exit;
}
global $gm_lang, $controller, $note_controller, $source_controller, $repository_controller, $media_controller, $rid;
$menubar = new MenuBar();
$filemenu = $menubar->GetFileMenu();
$editmenu = $menubar->GetEditMenu();
$viewmenu = $menubar->GetViewMenu();
$chartmenu = $menubar->GetChartsMenu();
if (isset($note_controller) && $note_controller->classname == "NoteController" && is_object($note_controller->note)) $notemenu = $menubar->GetThisNoteMenu($note_controller);
else $notemenu = "";
if (isset($controller) && $controller->classname == "IndividualController") $personmenu = $menubar->GetThisPersonMenu($controller);
else $personmenu = "";
if (isset($controller) && $controller->classname == "FamilyController") $familymenu = $menubar->GetThisFamilyMenu($controller);
else $familymenu = "";
if (isset($source_controller) && $source_controller->classname == "SourceController") $sourcemenu = $menubar->GetThisSourceMenu($source_controller);
else $sourcemenu = "";
if (isset($repository_controller) && $repository_controller->classname == "RepositoryController") $repomenu = $menubar->GetThisRepoMenu($repository_controller);
else $repomenu = "";
if (isset($media_controller) && $media_controller->classname == "MediaController") $mediamenu = $menubar->GetThisMediaMenu($media_controller);
else $mediamenu = "";
$reportmenu = $menubar->GetReportMenu();
$listmenu = $menubar->GetListMenu();
$helpmenu = $menubar->GetHelperMenu();
$favoritesmenu = $menubar->GetFavoritesMenu();
$custommenu = $menubar->GetCustomMenu();
function CreateMenu($menuobject, $level=0, $sub=false) {
	global $outputmenu;
	if (!$sub) $outputmenu = array();
	foreach ($menuobject->submenus as $sublevel => $submenu) {
		if (count($submenu->submenus) > 0) {
			foreach ($submenu->submenus as $key => $lowsub) {
				$tempmenu = CreateMenu($lowsub, $level++, true);
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

if (is_object($filemenu)) $showmenu[$gm_lang["menu_file"]] = CreateMenu($filemenu);
if (is_object($editmenu)) $showmenu[$gm_lang["menu_edit"]] = CreateMenu($editmenu);
if (is_object($viewmenu)) $showmenu[$gm_lang["menu_view"]] = CreateMenu($viewmenu);
if (is_object($chartmenu)) $showmenu[$gm_lang["menu_charts"]] = CreateMenu($chartmenu);
if (is_object($listmenu)) $showmenu[$gm_lang["menu_lists"]] = CreateMenu($listmenu);
if (is_object($reportmenu)) $showmenu[$gm_lang["menu_reports"]] = CreateMenu($reportmenu);
if (is_object($favoritesmenu)) $showmenu[$gm_lang["menu_favorites"]] = CreateMenu($favoritesmenu);
if (is_object($personmenu)) $showmenu[$gm_lang["this_individual"]] = CreateMenu($personmenu);
if (is_object($familymenu)) $showmenu[$gm_lang["this_family"]] = CreateMenu($familymenu);
if (is_object($sourcemenu)) $showmenu[$gm_lang["this_source"]] = CreateMenu($sourcemenu);
if (is_object($mediamenu)) $showmenu[$gm_lang["this_media"]] = CreateMenu($mediamenu);
if (is_object($notemenu)) $showmenu[$gm_lang["this_note"]] = CreateMenu($notemenu);
if (is_object($repomenu)) $showmenu[$gm_lang["this_repository"]] = CreateMenu($repomenu);
if (is_object($helpmenu)) $showmenu[$gm_lang["helpmenu"]] = CreateMenu($helpmenu);
if (is_object($custommenu)) $showmenu[$gm_lang["my_pages"]] = CreateMenu($custommenu);
/**
$outputmenu["Calendar"] = Array
        (
            0 => Array
                (
                    "label" => "Day",
                    "link" => "calendar.php"
                ),

            1 => Array
                (
                    "label" => "Month",
                    "link" => "calendar.php?action=calendar"
                ),

            2 => Array
                (
				 "Jaar" => Array
				 (
					"label" => "Year",
					"link" => "calendar.php?action=year"
				 )
                )

        );
	   **/
?>
<div id="header" class="<?php echo $TEXT_DIRECTION; ?>">
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
				<?php foreach (array_keys($showmenu) as $number => $name) { ?>
					menu<?php echo $number+1 ?>.onactivate = function() { document.getElementById("<?php echo $name; ?>").className = "hover"; };
					menu<?php echo $number+1 ?>.ondeactivate = function() { document.getElementById("<?php echo $name; ?>").className = ""; };
				<?php } ?>
			}
		}
	//-->
	</script>
	<?php
	global $gm_lang, $gm_username, $GEDCOMS, $HOME_SITE_URL, $HOME_SITE_TEXT,$GM_IMAGE_DIR, $GM_IMAGES, $gm_user;
	if ($TEXT_DIRECTION == "ltr") {
		$rdir = "right";
		$ldir = "left";
	}
	else {
		$rdir = "left";
		$ldir = "right";
	}
	?>
	<?php if (isset($GEDCOMS[$GEDCOMID])) { ?>
		<div style="width: 1em; height: 1em;">
		<a href="index.php?command=gedcom">
		<img src="<?php print $GM_IMAGE_DIR."/".$GM_IMAGES['gedcom']['small']; ?>" alt="<?php print $GEDCOMS[$GEDCOMID]['title']; ?>" />
		</a>
		</div>
	<?php } 
	else { ?>
		<div id="headerlogo" />
	<?php } ?>
	<div id="menu" class="shade1 <?php echo $TEXT_DIRECTION; ?>">
		<div class="shade1" style="float: <?php print $ldir; ?>;">
			<?php foreach (array_keys($showmenu) as $number => $name) { ?>
				<a id="<?php echo $name; ?>" href="#"><?php echo $name; ?></a>
				&nbsp;&nbsp;
			<?php } ?>
		</div>
		<div class="shade1" style="float: <?php print $rdir; ?>; text-align: <?php print $ldir; ?>; margin-<?php print $rdir; ?>: 0em; width: 20em;">
			<?php 
			if (isset($gm_user->gedcomid[$GEDCOMID]) && !empty($gm_user->gedcomid[$GEDCOMID]) && PrivacyFunctions::DisplayDetailsByID($gm_user->gedcomid[$GEDCOMID])) print "<a href=\"individual.php?pid=".$gm_user->gedcomid[$GEDCOMID]."\">".$gm_user->firstname.' '.$gm_user->lastname."</a><br />";
			else echo $gm_user->firstname.' '.$gm_user->lastname.'<br />';
			echo "<a href=\"".$HOME_SITE_URL."\">".$HOME_SITE_TEXT."</a>";
			?>
		</div>
	</div>
	<br clear="all" />
	<div style="float: <?php print $rdir; ?>; margin-<?php print $rdir; ?>: 0em; text-align: <?php print $rdir; ?>; height: 50px;">
		<form action="search.php" method="get" name="searchformtop">
			<input type="hidden" name="action" value="general" />
			<input type="hidden" name="topsearch" value="yes" />
			<input class="search" type="text" name="query" accesskey="<?php print $gm_lang["accesskey_search"]?>" size="25" onfocus="document.searchformtop.query.size=55;" onblur="document.searchformtop.query.size=25;" />
			<input type="submit" value="<?php print $gm_lang['search']?>" />
		</form>
	</div>
	<br clear="all" />
	
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
		var ms = new TransMenuSet(TransMenu.direction.down, 10, 15, TransMenu.reference.bottomLeft);

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
			$menubar .= 'var menu'.$maintitlecount.' = ms.addMenu(document.getElementById("'.$maintitle.'"));'.chr(10);
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
