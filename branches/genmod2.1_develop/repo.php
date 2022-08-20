<?php
/**
 * Displays the details about a repository record.
 * Also shows how many sources reference this repository.
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
 * @subpackage Display
 * @version $Id: repo.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");
/**
 * Inclusion of the repository controller
*/
$repository_controller = new RepositoryController();

PrintHeader($repository_controller->pagetitle);

$repository_controller->CheckNoResult(GM_LANG_repo_not_found);

$repository_controller->CheckPrivate();

$repository_controller->CheckRawEdited();

?>
<?php $repository_controller->PrintDetailJS(); ?>

<div class="DetailHeaderSection">
	<div class="PageTitleName"><?php print PrintReady($repository_controller->repo->title.$repository_controller->repo->addxref);?></div>
	<?php if(GedcomConfig::$SHOW_COUNTER && !$repository_controller->IsPrintPreview()) {
		print "\n<div class=\"PageCounter\">".GM_LANG_hit_count."&nbsp;".$hits."</div>\n";
	}?>
</div>
<?php

// Print the tab doors
$repository_controller->PrintTabs();

PrintFooter();
?>