<?php
/**
 * Customizable FAQ page
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
 * This Page Is Valid XHTML 1.0 Transitional! > 01 September 2005
 *
 * @package Genmod
 * @subpackage Charts
 * @version $Id$
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
	<div id="admin_genmod_left">
		<div class="admin_link"><a href="admin.php"><?php print GM_LANG_admin;?></a></div>
	</div>
<?php } ?>
<div id="content">
<?php
	if ($faq_controller->action=="add") {
		$i=1;
		?>
		<form name="addfaq" method="post" action="faq.php">
			<input type="hidden" name="action" value="commit" />
			<input type="hidden" name="type" value="add" />
			<div class="admin_topbottombar">
				<?php print_help_link("add_faq_item_help","qm","add_faq_item"); ?>
				<?php print GM_LANG_add_faq_item;?>
			</div>
			<div class="item_box shade2">
				<div class="width25 choice_left">
					<div class="helpicon"><?php print_help_link("add_faq_header_help","qm","add_faq_header"); ?></div>
					<div class="description"><?php print GM_LANG_add_faq_header;?></div>
				</div>
			</div>
			<div class="item_box">
				<div class="width25 choice_left">
					<input type="text" name="header" size="90" tabindex="<?php print $i++; ?>" />
				</div>
			</div>
			<div class="item_box shade2">
				<div class="width25 choice_left">
					<div class="helpicon"><?php print_help_link("add_faq_body_help","qm","add_faq_body"); ?></div>
					<div class="description"><?php print GM_LANG_add_faq_body;?></div>
				</div>
			</div>
			<div class="item_box">
				<div class="width25 choice_left">
					<textarea name="body" rows="10" cols="90" tabindex="<?php print $i++; ?>"></textarea>
				</div>
			</div>
			<div class="item_box center">
				<input type="submit" name="submit" value="<?php print GM_LANG_save; ?>" tabindex="<?php print $i++;?>" />
			</div>
			<div class="admin_return"><a href="faq.php"><?php print GM_LANG_faq_back;?></a></div>
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
			<div class="admin_topbottombar">
				<?php print_help_link("edit_faq_item_help","qm","edit_faq_item"); ?>
				<?php print GM_LANG_edit_faq_item;?>
			</div>
				<div class="item_box shade2">
					<div class="width25 choice_left">
						<div class="helpicon"><?php print_help_link("add_faq_header_help","qm","add_faq_header"); ?></div>
						<div class="description"><?php print GM_LANG_add_faq_header;?></div>
					</div>
				</div>
				<div class="item_box">
					<div class="width25 choice_left">
						<input type="text" name="header" size="90" value="<?php print $faq_controller->faq->header;?>" tabindex="<?php print $i++; ?>" />
					</div>
				</div>
				<div class="item_box shade2">
					<div class="width25 choice_left">
						<div class="helpicon"><?php print_help_link("add_faq_body_help","qm","add_faq_body"); ?></div>
						<div class="description"><?php print GM_LANG_add_faq_body;?></div>
					</div>
				</div>
				<div class="item_box">
					<div class="width25 choice_left">
						<textarea name="body" rows="10" cols="90" tabindex="<?php print $i++; ?>"><?php print html_entity_decode(stripslashes($faq_controller->faq->body));?></textarea>
					</div>
				</div>
			<div class="item_box center">
				<input type="submit" name="submit" value="<?php print GM_LANG_save; ?>" tabindex="<?php print $i++;?>" />
			</div>
			<div class="admin_return"><a href="faq.php"><?php print GM_LANG_faq_back;?></a></div>
		</form>
	<?php
	}
	
	if ($faq_controller->action == "show") {
		$faqs = $faq_controller->faqs;
		?>
		<div class="admin_topbottombar">
			<?php print "<h3>".GM_LANG_faq_page."</h3>"; ?>
		</div>
			<?php
			if (count($faqs) == 0 && $faq_controller->canconfig) { ?>
				<div class="shade2 item_box">
					<?php print_help_link("add_faq_item_help","qm","add_faq_item"); ?>
					<a href="faq.php?action=add"><?php print GM_LANG_add_faq_item;?></a>
				</div>
				<?php
			}
			else if (count($faqs) == 0 && !$faq_controller->canconfig) { ?>
				<div class="shade2 item_box">
					<div class="error"><?php print GM_LANG_no_faq_items; ?></div>
				</div>
				<?php
			}
			else { ?>
				<div class="shade2 item_box">
				<?php
				// NOTE: Add and preview link
				if ($faq_controller->canconfig && $faq_controller->adminedit) { ?>
					<div class="choice_left">
						<?php print_help_link("add_faq_item_help","qm","add_faq_item");?>
						<a href="faq.php?action=add"><?php print GM_LANG_add;?></a>
					</div>
					<?php
				}
				
				if ($faq_controller->canconfig && $faq_controller->adminedit) { ?>
					<div class="choice_middle">
						<?php print_help_link("preview_faq_item_help","qm","preview_faq_item");?>
						<a href="faq.php?adminedit=0"><?php print GM_LANG_preview;?></a>
					</div>
					<?php
				}
				else if ($faq_controller->canconfig && !$faq_controller->adminedit) {
					print_help_link("restore_faq_edits_help","qm","restore_faq_edits");
					print "<a href=\"faq.php?adminedit=1\">".GM_LANG_edit."</a>";
				}
				
				if ($faq_controller->canconfig && $faq_controller->adminedit) {
					if (!is_null($faq_controller->error_message)) print "<div class=\"topbottombar red\">".$faq_controller->error_message."</div>";
				}
				?>
				</div><hr />
				<?php
				foreach($faqs as $id => $faq) {
					if (!is_null($faq->header) && !is_null($faq->body)) { ?>
						<div class="item_box">
							<?php
							// NOTE: Print the position of the current item
							if ($faq_controller->canconfig && $faq_controller->adminedit) { ?>
								<div class="shade2 width30 faq_item_left">
									<div class="choice_left">
										<?php print GM_LANG_position_item;?>: <?php print $faq->order;?>
									</div>
								</div>
								<?php
							}
							// NOTE: Print the header of the current item
							?>
							
								<?php if ($faq_controller->canconfig && $faq_controller->adminedit) {?> 
									<div class="width70 faq_item_right">
										<div class="choice_right">
								<?php }
								else { ?>
									<div class="admin_topbottombar">
								<?php } ?>
									<?php print html_entity_decode($faq->header);?>
								<?php if ($faq_controller->canconfig && $faq_controller->adminedit) { ?> 
										</div>
								<?php } ?>
									</div>
						</div>
						<div class="item_box">
							<div class="shade2 width30 faq_item_left">
								<?php
								// NOTE: Print the edit options op the current item
								if ($faq_controller->canconfig && $faq_controller->adminedit) { ?>
									<div class="choice_left">
										<?php print_help_link("moveup_faq_item_help","qm","moveup_faq_item");?>
										<a href="faq.php?action=commit&amp;type=moveup&amp;id=<?php print $faq->id; ?>"><img src="<?php print GM_IMAGE_DIR."/".$GM_IMAGES["uarrow"]["other"];?>" alt="" /></a>
									</div>
									<div class="choice_middle">
										<?php print_help_link("movedown_faq_item_help","qm","movedown_faq_item"); ?>
										<a href="faq.php?action=commit&amp;type=movedown&amp;id=<?php print $faq->id; ?>"><img src="<?php print GM_IMAGE_DIR."/".$GM_IMAGES["darrow"]["other"];?>" alt="" /></a>
									</div>
									<div class="choice_middle">					
										<?php print_help_link("edit_faq_item_help","qm","edit_faq_item"); ?>
										<a href="faq.php?action=edit&amp;id=<?php print $faq->id;?>"><?php print GM_LANG_edit;?></a>
									</div>
									<div class="choice_middle">
										<?php print_help_link("delete_faq_item_help","qm","delete_faq_item"); ?>
										<a href="faq.php?action=commit&amp;type=delete&amp;id=<?php print $faq->id;?>" onclick="return confirm('<?php print GM_LANG_confirm_faq_delete;?>');"><?php print GM_LANG_delete;?></a>
									</div>
									<?php
								}
								// NOTE: Print the body text op the current item
								?>
							</div>
							<?php
							if ($faq_controller->canconfig && $faq_controller->adminedit) {?> <div class="width70 faq_item_right"> <?php } ?>
								<div class="choice_right"><?php print nl2br(html_entity_decode(stripslashes($faq->body)));?></div>
							<?php if ($faq_controller->canconfig && $faq_controller->adminedit) { ?> </div> <?php } ?>
						</div><hr />
						<?php
					}
				}
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
		if ($faq_controller->message != "") print "<div class=\"shade1\">".$faq_controller->message."</div>"; ?>
	<?php } ?>
</div>

<?php
PrintFooter();
?>
