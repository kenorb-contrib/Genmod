<?php
/**
 * Build your own webpages
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
 * @version $Id: custompage.php,v 1.13 2008/11/23 08:59:37 sjouke Exp $
 */
class CustomPage {
	
	var $name = 'CustomPage';
	var $output = '';
	var $message = '';
	var $id = 0;
	var $page_id = 0;
	var $pages = array();
	var $page = array();
	var $html = '';
	var $title = '';
	var $storenew = '';
	
	function CustomPage(&$genmod) {
		$this->CheckUrl();
		$this->GetPageValues($genmod);
		switch ($genmod['action']) {
			case 'show':
				if ($this->id > 0) {
					// Retrieve the page to be shown
					$sql = "SELECT * FROM ".$genmod['tblprefix']."pages WHERE `pag_id` = '".$this->id."'";
					$result = NewQuery($sql);
					if (!$result) {
						$message  = 'Invalid query: ' . mysql_error() . "\n";
						$message .= 'Whole query: ' . $sql;
						die($message);
					}
					else {
						while ($row = $result->FetchAssoc()) {
							$this->page = array();
							$this->page["id"] = $row["pag_id"];
							$this->page["html"] = stripslashes($row["pag_content"]);
							$this->page["title"] = stripslashes($row["pag_title"]);
						}
					}
					$this->AddHeader($this->page["title"]);
					echo html_entity_decode($this->page["html"]);
				}
				break;
			case 'edit':
				if ($this->CheckAccess()) {
					$this->AddHeader($genmod['gm_lang']['my_pages']);
					switch ($genmod['task']) {
						case $genmod['gm_lang']['save']:
							if ($this->storenew == "newpage") $sql = "INSERT INTO ".$genmod['tblprefix']."pages (`pag_content`, `pag_title`) VALUES ('".mysql_real_escape_string($this->html)."', '".mysql_real_escape_string($this->title)."')";
							else $sql = "UPDATE ".$genmod['tblprefix']."pages SET `pag_content` = '".mysql_real_escape_string($this->html)."', `pag_title` = '".mysql_real_escape_string($this->title)."' WHERE `pag_id` = '".$this->page_id."'";
							$result = NewQuery($sql);
							if (!$result) {
							   $message  = 'Invalid query: ' . mysql_error() . "\n";
							   $message .= 'Whole query: ' . $query;
							   die($message);
							}
							break;
						case $genmod['gm_lang']['delete']:
							$sql = "DELETE FROM ".$genmod['tblprefix']."pages WHERE `pag_id` = '".$this->page_id."'";
							$result = NewQuery($sql);
							if (!$result) {
							   $message  = 'Invalid query: ' . mysql_error() . "\n";
							   $message .= 'Whole query: ' . $query;
							   die($message);
							}
							// TODO: Add appropiate text
							else $this->message .= $genmod['gm_lang']['page_deleted'];
							break;
					}
					// Retrieve the current pages stored in the DB
					$sql = "SELECT * FROM ".$genmod['tblprefix']."pages";
					$result = NewQuery($sql);
					if (!$result) {
						$message  = 'Invalid query: ' . mysql_error() . "\n";
						$message .= 'Whole query: ' . $query;
						die($message);
					}
					else {
						while ($row = $result->FetchAssoc()) {
							$this->page["id"] = $row["pag_id"];
							$this->page["html"] = stripslashes($row["pag_content"]);
							$this->page["title"] = stripslashes($row["pag_title"]);
							$this->pages[$row["pag_id"]] = $this->page;
						}
					}
					$this->ShowForm($genmod);
				}
				else return false;
				break;
			default:
				$this->AddHeader($genmod['gm_lang']['edit']);
				echo $genmod['gm_lang']['access_denied'];
				break;
		}
		$this->AddFooter();
	}
	
	function CheckUrl() {
		// Do not allow direct access to the script
//		if ($_SERVER['SCRIPT_NAME'] == '/custompage.php') {
//			require_once("includes/functions.php");
//			header('Location: index2.php?page=custompage&'.GetQueryString());
//		}
	}
	
	function CheckAccess() {
		global $Users;
		// If no admin, always search in user help
		if (!$Users->userIsAdmin($Users->GetUserName())) return false;
		else return true;
	}
	
	function GetPageValues(&$genmod) {
		if (isset($_REQUEST['id'])) $this->id = $_REQUEST['id'];
		if (isset($_REQUEST['page_id'])) $this->page_id = $_REQUEST['page_id'];
		if (isset($_REQUEST['html'])) $this->html = $_REQUEST['html'];
		if (isset($_REQUEST['title'])) $this->title = $_REQUEST['title'];
		if (isset($_REQUEST['storenew'])) $this->storenew = $_REQUEST['storenew'];
	}
	
	function AddHeader($title) {
		print_header($title);
	}
	
	function AddFooter() {
		if (!empty($this->message)) echo $this->message;
		print_footer();
	}
	
	function ShowForm(&$genmod) {
		global $useFCK, $language_settings, $LANGUAGE;
		
		echo '<div id="content">';
		echo '<div id="mainpage">';
		echo '<div class="topbottombar">'.$genmod['gm_lang']['my_pages'].'</div>';
		switch ($genmod['task']) {
			case $genmod['gm_lang']["edit"]:
/**
 * Inclusion of the FCK Editor
*/
$useFCK = file_exists("./modules/FCKeditor/fckeditor.php");
if($useFCK){
	include("./modules/FCKeditor/fckeditor.php");
}
				
				?>
				<form name="htmlpage" method="post" action="custompage.php">
				<?php
				echo '<input type="hidden" name="action" value="'.$genmod['action'].'">';
				echo '<input type="hidden" name="page" value="'.$genmod['page'].'">';
				if ($this->page_id == "newpage") echo '<input type="hidden" name="storenew" value="newpage">';
				else echo '<input type="hidden" name="page_id" value="'.$this->page_id.'">';
				echo $genmod['gm_lang']['title'].':<br /><input type="text" name="title" value="';
				if ($this->page_id != "newpage") echo $this->pages[$this->page_id]["title"]; 
				echo '" /><br />';
				echo $genmod['gm_lang']["content"].':<br />';
				if ($useFCK) { // use FCKeditor module
					if ($this->page_id != "newpage") $text = $this->pages[$this->page_id]["html"];
					else $text = "";
					
					$oFCKeditor = new FCKeditor('html') ;
					$oFCKeditor->BasePath =  './modules/FCKeditor/';
					$oFCKeditor->Value = $text;
					$oFCKeditor->Width = 700;
					$oFCKeditor->Height = 450;
					$oFCKeditor->Config['EnterMode'] = 'br';
					$oFCKeditor->Config['AutoDetectLanguage'] = false ;
					$oFCKeditor->Config['DefaultLanguage'] = $language_settings[$LANGUAGE]["lang_short_cut"];
					$oFCKeditor->Create() ;
				} else { //use standard textarea
					echo '<textarea name="html" rows="15" cols="80">';
					if ($this->page_id != "newpage") echo $this->pages[$this->page_id]["html"];
					?></textarea>
				<?php } ?>
				<br />
				<input type="submit" name="task" value="<?php print $genmod['gm_lang']['save']; ?>">
				<input type="submit" name="task" value="<?php print $genmod['gm_lang']["cancel"]; ?>">
				</form>
				<?php
				break;
			default:
				// Form with pages to edit
				echo '<table class="width100">';
				echo '<tr class="shade3"><td class="width10">'.$genmod['gm_lang']['options'].'</td><td>'.$genmod['gm_lang']['title'].'</td></tr>';
				echo '<tr><td class="shade2"><a style="text-decoration: none;" href="custompage.php?action='.$genmod['action'].'&amp;task='.$genmod['gm_lang']['edit'].'&amp;page_id=newpage"><img class="noborder" src="'.$genmod['gm_image_dir'].'/'.$genmod['gm_images']["edit"]["button"].'" alt="'.$genmod['gm_lang']['edit'].'"/></a>';
				echo '&nbsp;</td>';
				echo '<td class="shade1">'.$genmod['gm_lang']['new'].'</td></tr>';
				foreach ($this->pages as $ct => $page) {
					echo '<tr><td class="shade2"><a style="text-decoration: none;" href="custompage.php?action='.$genmod['action'].'&amp;task='.$genmod['gm_lang']['edit'].'&amp;page_id='.$page["id"].'"><img class="noborder" src="'.$genmod['gm_image_dir'].'/'.$genmod['gm_images']["edit"]["button"].'" alt="'.$genmod['gm_lang']['edit'].'"/></a>&nbsp;';
					echo '<a href="custompage.php?action='.$genmod['action'].'&amp;task='.$genmod['gm_lang']['delete'].'&amp;page_id='.$page["id"].'" onclick="return confirm(\''.$genmod['gm_lang']['confirm_page_delete'].'\');"><img class="noborder" src="'.$genmod['gm_image_dir'].'/'.$genmod['gm_images']["delete"]["button"].'" alt="'.$genmod['gm_lang']['delete'].'"/></a></td>';
					echo '<td class="shade1">'.$page["title"].'</td></tr>';
				}
				echo '</table>';
				break;
		}
		echo '</div>';
	}
}
require "config.php";
require "includes/setgenmod.php";
$custompage = new CustomPage($genmod);
?>
