<?php
/**
 * Displays the details about a source record. Also shows how many people, families and other records
 * reference this source.
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
 * @version $Id: source.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

/**
 * Inclusion of the source controller
*/
$source_controller = new SourceController();

PrintHeader($source_controller->pagetitle);

$source_controller->CheckNoResult(GM_LANG_source_not_found);

$source_controller->CheckPrivate();

$source_controller->CheckRawEdited();

?>
<?php $source_controller->PrintDetailJS(); ?>

<div class="DetailHeaderSection">
	<div class="PageTitleName"><?php print PrintReady($source_controller->source->title.$source_controller->source->addxref);?></div><br />
	<?php if(GedcomConfig::$SHOW_COUNTER && !$source_controller->IsPrintPreview()) print "\n<div class=\"PageCounter\">".GM_LANG_hit_count."&nbsp;".$hits."</div>\n"; ?>
</div>
<?php

// Print the tab doors
$source_controller->PrintTabs();

PrintFooter();
?>