<?php
/**
 * Customizable FAQ page
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
 * This Page Is Valid XHTML 1.0 Transitional! > 01 September 2005
 *
 * @package Genmod
 * @subpackage Charts
 * @version $Id: faq.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
 
/**
 * Inclusion of the configuration file
*/
require("config.php");

$faq_controller = new FAQController();

// -- print html header information
PrintHeader(GM_LANG_faq_page);

if ($faq_controller->canconfig && $faq_controller->adminedit) {?>
	<!-- Setup the left box -->
	<div id="AdminColumnLeft">
		<?php AdminFunctions::AdminLink("admin.php", GM_LANG_admin); ?>
	</div>
<?php } ?>
<div id="AdminColumnMiddle">
<?php
	if ($faq_controller->action=="add") {
		$i=1;
		?>
		<form name="addfaq" method="post" action="faq.php">
			<input type="hidden" name="action" value="commit" />
			<input type="hidden" name="type" value="add" />
			<table class="NavBlockTable FAQEditTable">
			<tr>
				<td class="NavBlockHeader" colspan="2">
					<?php PrintHelpLink("add_faq_item_help","qm","add_faq_item"); ?>
					<?php print GM_LANG_add_faq_item;?>
				</td>
			</tr>
			<tr>
				<td class="NavBlockLabel">
					<div class="HelpIconContainer"><?php PrintHelpLink("add_faq_header_help","qm","add_faq_header"); ?></div>
					<?php print GM_LANG_add_faq_header;?>
				</td>
				<td class="NavBlockField">
					<input type="text" name="header" size="90" tabindex="<?php print $i++; ?>" />
				</td>
			</tr>
			<tr>
				<td class="NavBlockLabel">
					<div class="HelpIconContainer"><?php PrintHelpLink("add_faq_body_help","qm","add_faq_body"); ?></div>
					<?php print GM_LANG_add_faq_body;?>
				</td>
				<td class="NavBlockField">
					<textarea name="body" rows="10" cols="70" tabindex="<?php print $i++; ?>"></textarea>
				</td>
			</tr>
			<tr>
				<td class="NavBlockFooter" colspan="2">
					<input type="submit" name="submit" value="<?php print GM_LANG_save; ?>" tabindex="<?php print $i++;?>" />
					<input type="button" name="faqhome" value="<?php print GM_LANG_faq_back; ?>" tabindex="<?php print $i++;?>" onclick="document.location='faq.php'" />
				</td>
			</tr>
			</table>
		</form>
		<?php
	}
	
	if ($faq_controller->action == "edit") {
		$i=1;
		?>
		<form name="editfaq" method="post" action="faq.php">
			<input type="hidden" name="action" value="commit" />
			<input type="hidden" name="type" value="update" />
			<input type="hidden" name="id" value="<?php print $faq_controller->id;?>" />
			<table class="NavBlockTable FAQEditTable">
			<tr>
				<td class="NavBlockHeader" colspan="2">
					<?php PrintHelpLink("edit_faq_item_help","qm","edit_faq_item"); ?>
					<?php print GM_LANG_edit_faq_item;?>
				</td>
			</tr>
			<tr>
				<td class="NavBlockLabel">
					<div class="HelpIconContainer"><?php PrintHelpLink("add_faq_header_help","qm","add_faq_header"); ?></div>
					<?php print GM_LANG_add_faq_header;?>
				</td>
				<td class="NavBlockField">
					<input type="text" name="header" size="90" value="<?php print $faq_controller->faq->header;?>" tabindex="<?php print $i++; ?>" />
				</td>
			</tr>
			<tr>
				<td class="NavBlockLabel">
					<div class="HelpIconContainer"><?php PrintHelpLink("add_faq_body_help","qm","add_faq_body"); ?></div>
					<?php print GM_LANG_add_faq_body;?>
				</td>
				<td class="NavBlockField">
					<textarea name="body" rows="10" cols="90" tabindex="<?php print $i++; ?>"><?php print html_entity_decode(stripslashes($faq_controller->faq->body));?></textarea>
				</td>
			</tr>
			<tr>
				<td class="NavBlockFooter" colspan="2">
					<input type="submit" name="submit" value="<?php print GM_LANG_save; ?>" tabindex="<?php print $i++;?>" />
					<input type="button" name="faqhome" value="<?php print GM_LANG_faq_back; ?>" tabindex="<?php print $i++;?>" onclick="document.location='faq.php'" />
				</td>
			</tr>
			</table>
		</form>
	<?php
	}
	
	if ($faq_controller->action == "show") {
		$faqs = $faq_controller->faqs;
		?>
		<div class="NavBlockHeader FAQPageHeader">
			<?php print GM_LANG_faq_page; ?>
		</div>
			<?php
			if (count($faqs) == 0 && $faq_controller->canconfig) { ?>
				<div class="FAQItem">
					<?php PrintHelpLink("add_faq_item_help","qm","add_faq_item"); ?>
					<a href="faq.php?action=add"><?php print GM_LANG_add_faq_item;?></a>
				</div>
				<?php
			}
			else if (count($faqs) == 0 && !$faq_controller->canconfig) { ?>
				<div class="FAQItem">
					<div class="Error"><?php print GM_LANG_no_faq_items; ?></div>
				</div>
				<?php
			}
			else { ?>
				<?php
				$highid = array_pop(array_keys($faqs));
				foreach($faqs as $id => $faq) {
					if (!isset($lowid)) $lowid = $id;
					if (!is_null($faq->header) && !is_null($faq->body)) { ?>
						<div class="FAQItem">
						<?php
						// NOTE: Print the position of the current item
						if ($faq_controller->canconfig && $faq_controller->adminedit) { ?>
							<div class="NavBlockLabel FAQItemOptions">
								<?php print GM_LANG_position_item;?>: <?php print $faq->order;?>
							</div>
							<?php
						}
						// NOTE: Print the header of the current item
						?>
							
						<?php if ($faq_controller->canconfig && $faq_controller->adminedit) {?> 
							<div class="NavBlockColumnHeader FAQEditHeaderText">
						<?php }
						else { ?>
							<div class="NavBlockColumnHeader FAQHeaderText">
						<?php } ?>
						<?php print html_entity_decode($faq->header);?>
							</div>
								<?php
								// NOTE: Print the edit options op the current item
								if ($faq_controller->canconfig && $faq_controller->adminedit) { ?>
									<div class="NavBlockLabel FAQItemOptions">
									<div class="FAQItemOption">
										<?php if ($id != $lowid) {
											PrintHelpLink("moveup_faq_item_help","qm","moveup_faq_item");?>
											<a href="faq.php?action=commit&amp;type=moveup&amp;id=<?php print $faq->id; ?>"><img src="<?php print GM_IMAGE_DIR."/".$GM_IMAGES["uarrow"]["other"];?>" alt="" /></a>
										<?php } 
										else print "&nbsp;";?>
									</div>
									<div class="FAQItemOption">
										<?php if ($id != $highid) {
											PrintHelpLink("movedown_faq_item_help","qm","movedown_faq_item"); ?>
											<a href="faq.php?action=commit&amp;type=movedown&amp;id=<?php print $faq->id; ?>"><img src="<?php print GM_IMAGE_DIR."/".$GM_IMAGES["darrow"]["other"];?>" alt="" /></a>
										<?php } 
										else print "&nbsp;";?>
									</div>
									<div class="FAQItemOption">
										<?php PrintHelpLink("edit_faq_item_help","qm","edit_faq_item"); ?>
										<a href="faq.php?action=edit&amp;id=<?php print $faq->id;?>"><?php print GM_LANG_edit;?></a>
									</div>
									<div class="FAQItemOption">
										<?php PrintHelpLink("delete_faq_item_help","qm","delete_faq_item"); ?>
										<a href="faq.php?action=commit&amp;type=delete&amp;id=<?php print $faq->id;?>" onclick="return confirm('<?php print GM_LANG_confirm_faq_delete;?>');"><?php print GM_LANG_delete;?></a>
									</div>
									</div>
									<?php
								}
								// NOTE: Print the body text op the current item
								?>
								<?php if ($faq_controller->canconfig && $faq_controller->adminedit) {?> 
									<div class="NavBlockField FAQEditBodyText">
								<?php }
								else { ?>
									<div class="NavBlockField FAQBodyText">
								<?php } ?>
							<?php print nl2br(html_entity_decode(stripslashes($faq->body)));?></div>
						</div>
						<?php
					}
				}?>
				<div class="FAQItem">
				<?php
				// NOTE: Add and preview link
				if ($faq_controller->canconfig && $faq_controller->adminedit) { ?>
					<?php PrintHelpLink("add_faq_item_help","qm","add_faq_item");?>
					<a href="faq.php?action=add"><?php print GM_LANG_add;?></a>
					<?php
				}
				
				if ($faq_controller->canconfig && $faq_controller->adminedit) { ?>
					<?php PrintHelpLink("preview_faq_item_help","qm","preview_faq_item");?>
					<a href="faq.php?adminedit=0"><?php print GM_LANG_preview;?></a>
					<?php
				}
				else if ($faq_controller->canconfig && !$faq_controller->adminedit) {
					PrintHelpLink("restore_faq_edits_help","qm","restore_faq_edits");
					print "<a href=\"faq.php?adminedit=1\">".GM_LANG_edit."</a>";
				}
				
				if ($faq_controller->canconfig && $faq_controller->adminedit) {
					if (!is_null($faq_controller->error_message)) print "<div class=\"Error\">".$faq_controller->error_message."</div>";
				}
				?>
				</div> <?php
			}
	}
	if ($faq_controller->action != "show") {
		?>
		<script language="JavaScript" type="text/javascript">
		<!--
			document.<?php print $faq_controller->action;?>faq.header.focus();
		//-->
		</script>
		<?php
	}
	if ($faq_controller->canconfig && $faq_controller->adminedit) {
		if ($faq_controller->message != "") print "<div class=\"Error\">".$faq_controller->message."</div>"; ?>
	<?php } ?>
</div>

<?php
PrintFooter();
?>
