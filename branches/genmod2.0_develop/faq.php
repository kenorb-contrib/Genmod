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

global $GM_IMAGES, $faqs;

if ($Users->userGedcomAdmin($gm_username)) $canconfig = true;
else $canconfig = false;
if (!isset($action)) $action = "show";
if (!isset($adminedit) && $canconfig) $adminedit = true;
else if (!isset($adminedit)) $adminedit = false;
$message = "";

// -- print html header information
print_header($gm_lang["faq_page"]);

if ($canconfig && $adminedit) {?>
	<!-- Setup the left box -->
	<div id="admin_genmod_left">
		<div class="admin_link"><a href="admin.php"><?php print $gm_lang["admin"];?></a></div>
	</div>
<?php } ?>
<div id="content">
<?php
	// NOTE: Commit the faq data to the DB
	if ($action=="commit") {
		if ($type == "update") {
			$faqs = GetFaqData();
			if (isset($faqs[$order])) {
				foreach ($faqs as $key => $item) {
					if ($key >= $order) {
						$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".($key+1)."' WHERE b_id='".$faqs[$key]["header"]["pid"]."' and b_location='header'";;
						$res = NewQuery($sql);
						$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".($key+1)."' WHERE b_id='".$faqs[$key]["body"]["pid"]."' and b_location='body'";
						$res = NewQuery($sql);
					}
				}
			}
			$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".$order."', b_config='".$DBCONN->EscapeQuery(serialize($header))."' WHERE b_id='".$pidh."' and b_username='".$GEDCOMID."' and b_location='header'";
			$res = NewQuery($sql);
			$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".$order."', b_config='".$DBCONN->EscapeQuery(serialize($body))."' WHERE b_id='".$pidb."'  and b_username='".$GEDCOMID."' and b_location='body'";
			$res = NewQuery($sql);
			WriteToLog("FAQ-> FAQ item has been edited.<br />Header ID: ".$pidh.".<br />Body ID: ".$pidb, "I", "G", $GEDCOM);
			$action = "show";
		}
		else if ($type == "delete") {
			$sql = "DELETE FROM ".$TBLPREFIX."blocks WHERE b_order='".$id."' AND b_name='faq' AND b_username='".$GEDCOMID."'";
			$res = NewQuery($sql);
			WriteToLog("FAQ-> FAQ item has been deleted.<br />Header ID: ".$pidh.".<br />Body ID: ".$pidb, "I", "G", $GEDCOM);
			$action = "show";
		}
		else if ($type == "add") {
			$faqs = GetFaqData();
			if (isset($faqs[$order])) {
				foreach ($faqs as $key => $item) {
					if ($key >= $order) {
						$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".($key+1)."' WHERE b_id='".$faqs[$key]["header"]["pid"]."' and b_location='header'";;
						$res = NewQuery($sql);
						$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".($key+1)."' WHERE b_id='".$faqs[$key]["body"]["pid"]."' and b_location='body'";
						$res = NewQuery($sql);
					}
				}
			}
			$newid = GetNextId("blocks", "b_id");
			$sql = "INSERT INTO ".$TBLPREFIX."blocks VALUES ($newid, '".$GEDCOMID."', 'header', '$order', 'faq', '".$DBCONN->EscapeQuery(serialize($header))."')";
			$res = NewQuery($sql);
			$sql = "INSERT INTO ".$TBLPREFIX."blocks VALUES (".($newid+1).", '".$GEDCOMID."', 'body', '".$order."', 'faq', '".$DBCONN->EscapeQuery(serialize($body))."')";
			$res = NewQuery($sql);
			WriteToLog("FAQ-> FAQ item has been added.<br />Header ID: ".$newid.".<br />Body ID: ".($newid+1), "I", "G", $GEDCOM);
			$action = "show";
		}
		else if ($type == "moveup") {
			$faqs = GetFaqData();
			if ($id-1 != 0) {
				if (isset($faqs[$id-1])) {
					$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".($id)."' WHERE b_id='".$faqs[$id-1]["header"]["pid"]."' and b_location='header'";;
					$res = NewQuery($sql);
					$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".($id)."' WHERE b_id='".$faqs[$id-1]["body"]["pid"]."' and b_location='body'";
					$res = NewQuery($sql);
				}
				$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".($id-1)."' WHERE b_id='".$pidh."' and b_location='header'";;
				$res = NewQuery($sql);
				$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".($id-1)."' WHERE b_id='".$pidb."' and b_location='body'";
				$res = NewQuery($sql);
				WriteToLog("FAQ-> FAQ item has been moved up.<br />Header ID: ".$pidh.".<br />Body ID: ".$pidb, "I", "G", $GEDCOM);
			}
			else $message = "Item cannot be moved lower.";
			$action = "show";
		}
		else if ($type == "movedown") {
			$faqs = GetFaqData();
			if (isset($faqs[$id+1])) {
				$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".($id)."' WHERE b_id='".$faqs[$id+1]["header"]["pid"]."' and b_location='header'";;
				$res = NewQuery($sql);
				$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".($id)."' WHERE b_id='".$faqs[$id+1]["body"]["pid"]."' and b_location='body'";
				$res = NewQuery($sql);
			}
			
			$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".($id+1)."' WHERE b_id='".$pidh."' and b_location='header'";;
			$res = NewQuery($sql);
			$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".($id+1)."' WHERE b_id='".$pidb."' and b_location='body'";
			$res = NewQuery($sql);
			WriteToLog("FAQ item has been moved down.<br />Header ID: ".$pidh.".<br />Body ID: ".$pidb, "I", "G", $GEDCOM);
			$action = "show";
		}	
		$action = "show";
	}
	
	if ($action=="add") {
		$i=1;
		?>
		<form name="addfaq" method="post" action="faq.php">
			<input type="hidden" name="action" value="commit" />
			<input type="hidden" name="type" value="add" />
			<div class="admin_topbottombar">
				<?php print_help_link("add_faq_item_help","qm","add_faq_item"); ?>
				<?php print $gm_lang["add_faq_item"];?>
			</div>
			<div class="item_box shade2">
				<div class="width25 choice_left">
					<div class="helpicon"><?php print_help_link("add_faq_header_help","qm","add_faq_header"); ?></div>
					<div class="description"><?php print $gm_lang["add_faq_header"];?></div>
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
					<div class="description"><?php print $gm_lang["add_faq_body"];?></div>
				</div>
			</div>
			<div class="item_box">
				<div class="width25 choice_left">
					<textarea name="body" rows="10" cols="90" tabindex="<?php print $i++; ?>"></textarea>
				</div>
			</div>
			<div class="item_box shade2">
				<div class="width25 choice_left">
					<div class="helpicon"><?php print_help_link("add_faq_order_help","qm","add_faq_order"); ?></div>
					<div class="description"><?php print $gm_lang["add_faq_order"];?></div>
				</div>
			</div>
			<div class="item_box">
				<div class="width25 choice_left">
					<input type="text" name="order" size="3" tabindex="<?php print $i++; ?>" />
				</div>
			</div>
			<div class="item_box center">
				<input type="submit" name="submit" value="<?php print $gm_lang["save"]; ?>" tabindex="<?php print $i++;?>" />
			</div>
			<div class="admin_return"><a href="faq.php"><?php print $gm_lang["faq_back"];?></a></div>
		</form>
		<?php
	}
	
	if ($action == "edit") {
		if (!isset($id)) {
			$error = true;
			$error_message =  $gm_lang["no_id"];
			$action = "show";
		}
		else {
			$faqs = GetFaqData($id);
			$i=1;
			?>
			<form name="editfaq" method="post" action="faq.php">
				<input type="hidden" name="action" value="commit" />
				<input type="hidden" name="type" value="update" />
				<input type="hidden" name="id" value="<?php print $id;?>" />
				<div class="admin_topbottombar">
					<?php print_help_link("edit_faq_item_help","qm","edit_faq_item"); ?>
					<?php print $gm_lang["edit_faq_item"];?>
				</div>
				<?php foreach ($faqs as $id => $data) { ?>
					<input type="hidden" name="pidh" value="<?php print $data["header"]["pid"];?>" />
					<input type="hidden" name="pidb" value="<?php print $data["body"]["pid"];?>" />
					<div class="item_box shade2">
						<div class="width25 choice_left">
							<div class="helpicon"><?php print_help_link("add_faq_header_help","qm","add_faq_header"); ?></div>
							<div class="description"><?php print $gm_lang["add_faq_header"];?></div>
						</div>
					</div>
					<div class="item_box">
						<div class="width25 choice_left">
							<input type="text" name="header" size="90" value="<?php print $data["header"]["text"];?>" tabindex="<?php print $i++; ?>" />
						</div>
					</div>
					<div class="item_box shade2">
						<div class="width25 choice_left">
							<div class="helpicon"><?php print_help_link("add_faq_body_help","qm","add_faq_body"); ?></div>
							<div class="description"><?php print $gm_lang["add_faq_body"];?></div>
						</div>
					</div>
					<div class="item_box">
						<div class="width25 choice_left">
							<textarea name="body" rows="10" cols="90" tabindex="<?php print $i++; ?>"><?php print html_entity_decode(stripslashes($data["body"]["text"]));?></textarea>
						</div>
					</div>
					<div class="item_box shade2">
						<div class="width25 choice_left">
							<div class="helpicon"><?php print_help_link("add_faq_order_help","qm","add_faq_order"); ?></div>
							<div class="description"><?php print $gm_lang["add_faq_order"];?></div>
						</div>
					</div>
					<div class="item_box">
						<div class="width25 choice_left">
							<input type="text" name="order" size="3" value="<?php print $id;?>" tabindex="<?php print $i++; ?>" />
						</div>
					</div>
				<?php } ?>
				<div class="item_box center">
					<input type="submit" name="submit" value="<?php print $gm_lang["save"]; ?>" tabindex="<?php print $i++;?>" />
				</div>
				<div class="admin_return"><a href="faq.php"><?php print $gm_lang["faq_back"];?></a></div>
			</form>
		<?php
		}
	}
	
	if ($action == "show") {
		$faqs = GetFaqData();
		?>
		<div class="admin_topbottombar">
			<?php print "<h3>".$gm_lang["faq_page"]."</h3>"; ?>
		</div>
			<?php
			if (count($faqs) == 0 && $canconfig) { ?>
				<div class="shade2 item_box">
					<?php print_help_link("add_faq_item_help","qm","add_faq_item"); ?>
					<a href="faq.php?action=add"><?php print $gm_lang["add_faq_item"];?></a>
				</div>
				<?php
			}
			else if (count($faqs) == 0 && !$canconfig) { ?>
				<div class="shade2 item_box">
					<div class="error"><?php print $gm_lang["no_faq_items"]; ?></div>
				</div>
				<?php
			}
			else { ?>
				<div class="shade2 item_box">
				<?php
				// NOTE: Add and preview link
				if ($canconfig && $adminedit) { ?>
					<div class="choice_left">
						<?php print_help_link("add_faq_item_help","qm","add_faq_item");?>
						<a href="faq.php?action=add"><?php print $gm_lang["add"];?></a>
					</div>
					<?php
				}
				
				if ($canconfig && $adminedit) { ?>
					<div class="choice_middle">
						<?php print_help_link("preview_faq_item_help","qm","preview_faq_item");?>
						<a href="faq.php?adminedit=0"><?php print $gm_lang["preview"];?></a>
					</div>
					<?php
				}
				else if ($canconfig && !$adminedit) {
					print_help_link("restore_faq_edits_help","qm","restore_faq_edits");
					print "<a href=\"faq.php?adminedit=1\">".$gm_lang["edit"]."</a>";
				}
				
				if ($canconfig && $adminedit) {
					if (isset($error)) print "<div class=\"topbottombar red\">".$error_message."</div>";
				}
				?>
				</div>
				<?php
				foreach($faqs as $id => $data) {
					if ($data["header"] && $data["body"]) { ?>
						<div class="item_box">
							<?php
							// NOTE: Print the position of the current item
							if ($canconfig && $adminedit) { ?>
								<div class="shade2 width30 faq_item_left">
									<div class="choice_left">
										<?php print $gm_lang["position_item"];?>: <?php print $id;?>
									</div>
								</div>
								<?php
							}
							// NOTE: Print the header of the current item
							?>
							
								<?php if ($canconfig && $adminedit) {?> 
									<div class="width70 faq_item_right">
										<div class="choice_right">
								<?php }
								else { ?>
									<div class="admin_topbottombar">
								<?php } ?>
									<?php print html_entity_decode($data["header"]["text"]);?>
								<?php if ($canconfig && $adminedit) { ?> 
										</div>
									</div>
								<?php }
								else { ?>
									</div>
								<?php } ?>
						</div>
						<div class="item_box">
							<div class="shade2 width30 faq_item_left">
								<?php
								// NOTE: Print the edit options op the current item
								if ($canconfig && $adminedit) { ?>
									<div class="choice_left">
										<?php print_help_link("moveup_faq_item_help","qm","moveup_faq_item");?>
										<a href="faq.php?action=commit&amp;type=moveup&amp;id=<?php print $id; ?>&amp;pidh=<?php print $data["header"]["pid"]; ?>&amp;pidb=<?php print $data["body"]["pid"];?>"><img src="<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["uarrow"]["other"];?>" alt="" /></a>
									</div>
									<div class="choice_middle">
										<?php print_help_link("movedown_faq_item_help","qm","movedown_faq_item"); ?>
										<a href="faq.php?action=commit&amp;type=movedown&amp;id=<?php print $id; ?>&amp;pidh=<?php print $data["header"]["pid"]; ?>&amp;pidb=<?php print $data["body"]["pid"];?>"><img src="<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["darrow"]["other"];?>" alt="" /></a>
									</div>
									<div class="choice_middle">					
										<?php print_help_link("edit_faq_item_help","qm","edit_faq_item"); ?>
										<a href="faq.php?action=edit&amp;id=<?php print $id;?>"><?php print $gm_lang["edit"];?></a>
									</div>
									<div class="choice_middle">
										<?php print_help_link("delete_faq_item_help","qm","delete_faq_item"); ?>
										<a href="faq.php?action=commit&amp;type=delete&amp;id=<?php print $id;?>&amp;pidh=<?php print $data["header"]["pid"]; ?>&amp;pidb=<?php print $data["body"]["pid"];?>" onclick="return confirm('<?php print $gm_lang["confirm_faq_delete"];?>');"><?php print $gm_lang["delete"];?></a>
									</div>
									<?php
								}
								// NOTE: Print the body text op the current item
								?>
							</div>
							<?php
							if ($canconfig && $adminedit) {?> <div class="width70 faq_item_right"> <?php } ?>
								<div class="choice_right"><?php print nl2br(html_entity_decode(stripslashes($data["body"]["text"])));?></div>
							<?php if ($canconfig && $adminedit) { ?> </div> <?php } ?>
						</div>
						<?php
					}
				}
			}
	}
	if ($action != "show") {
		?>
		<script language="JavaScript" type="text/javascript">
		<!--
			document.<?php print $action;?>faq.header.focus();
		//-->
		</script>
		<?php
	}
	if ($canconfig && $adminedit) {
		if ($message != "") print "<div class=\"shade1\">".$message."</div>"; ?>
	<?php } ?>
</div>

<?php
print_footer();
?>
