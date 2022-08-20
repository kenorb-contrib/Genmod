<?php
/**
 * Class file for sending out mail
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
 * @subpackage tools
 * @version $Id: sendmail.class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class SendMail {
	
	public $classname = 'SendMail';	// Name of this class
	
	private $header = '';			// used to construct the mail header
	private $message = '';			// Used to construct the mail body
	private $uid = '';				// Help field
	
	// TODO: test the \r\n in the attachment functions
	
	public function __construct($filenames, $path, $mailto, $from_mail, $from_name, $replyto, $subject, $message, $html=true, $admincopy=false) {
		
		$this->uid = md5(uniqid(time()));
		// NOTE: Add the general mail header
		$this->MailHeader($from_name, $from_mail, $replyto, $admincopy);
		
		// NOTE: If there are files to attach, do it here.
		if (!empty($filenames)) {
			$this->MailHeaderAttachments($from_name, $from_mail, $replyto);
			$this->AddAttachments($filenames, $path, $message);
		}
		else {
			if ($html) $this->MailHeaderHtml();
			else $this->MailHeaderPlain();
			$this->AddMessage($message);
		}
		
		// NOTE: All done. Send out the mail
		if ($this->OutputMail($mailto, $subject)) return true;
		else return false;
	}
	// NOTE: Basic mailheader
	private function MailHeader($from_name, $from_mail, $replyto, $admincopy) {
		
		$this->header .= "MIME-Version: 1.0\n";
		$this->header .= "X-Mailer: Genmod\n";
		// Add extra headers and separate them by \r\n
		$this->header = "From: ".$from_name." <".$from_mail.">\r\n";
		$this->header .= "Reply-To: ".$replyto."\r\n";
		if ($admincopy && GedcomConfig::$BCC_WEBMASTER) {
			$adm =& User::GetInstance(GedcomConfig::$WEBMASTER_EMAIL);
			if (!empty($adm->email)) $this->header .= "Bcc: ".$adm->email."\r\n";
		}
	}
	
	// NOTE: Attachments for mail
	private function MailHeaderAttachments() {
		$this->header .= "Content-Type: multipart/mixed; boundary=\"".$this->uid."\"\r\n\r\n";
		$this->header .= "This is a multi-part message in MIME format.\r\n";
	}
	
	// NOTE: HTML mail
	private function MailHeaderHtml() {
		$this->header .= "Content-Type: text/html; charset=utf-8\n\n";
	}
	
	// NOTE Plain text mail
	private function MailHeaderPlain() {
		$this->header .= "Content-Type: text/plain; charset=utf-8\n\n";
	}
	
	// NOTE: Add the attachemnts
	private function AddAttachments($filenames, $path, $message) {
		$this->header .= "--".$this->uid."\r\n";
		$this->header .= "Content-type:text/plain; charset=iso-8859-1\r\n";
		$this->header .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
		$this->header .= $message."\r\n\r\n";
		if (is_array($filenames)) {
			foreach ($filenames as $id => $filename) {
				$this->AddFile($filename, $path);
			}
		}
		else {
			$this->AddFile($filenames, $path);
		}
		$this->header .= "--".$this->uid."--";
	}
	
	// NOTE: Add a file to the message
	private function AddFile($filename, $path) {
		$file = $path.$filename;
		$file_size = filesize($file);
		$handle = fopen($file, "r");
		$content = fread($handle, $file_size);
		fclose($handle);
		$content = chunk_split(base64_encode($content));
		
		$name = basename($file);
		
		$this->header .= "--".$this->uid."\r\n";
		$this->header .= "Content-Type: application/octet-stream; name=\"".$filename."\"\r\n"; // use diff. types here
		$this->header .= "Content-Transfer-Encoding: base64\r\n";
		$this->header .= "Content-Disposition: attachment; filename=\"".$filename."\"\r\n\r\n";
		$this->header .= $content."\r\n\r\n";
	}
	
	// NOTE: Add the message to the mail
	private function AddMessage(&$message) {
		$this->message .= $message;
	}
	
	// NOTE: Send out the mail
	private function OutputMail($mailto, $subject) {
		$sent = mail($mailto, $subject, $this->message, $this->header);
		if ($sent) return true;
		else {
			WriteToLog("SendMail-&gt;Outputmail-&gt; There was an error sending mail<br />mailto: ".$mailto."<br />subject: ".$subject."<br />header: ".$this->header."<br />message: ".$this->message, "E", "S");
			return false;
		}
	}
}
/**
// how to use
// $my_file = "20070315_075100_Rapportage KPN Inbound IT.xml";
// $my_file = array('20070706_090202_Rapportage KPN Inbound IT Het Net Capelle.xml','20070706_090202_Rapportage KPN Inbound VT Capelle.xml');
// $my_file = '20070706_090202_Rapportage KPN Inbound IT Het Net Capelle.xml';
// $my_path = dirname(__FILE__).'/output/';
$my_file = '';
$my_path = '';
$my_name = "E.Novation Contact Centers";
$my_mail = "rolandd@rolandd.com";
$my_replyto = "rolandd@rolandd.com";
$my_subject = "This is a mail with attachment.";
$my_message = "Testing.....";
$my_mail_to = "rolandd@rolandd.com";
// $extra_headers = "Bcc: Roland Dalmulder <roland.dalmulder@enovation.nl>, roland.dalmulder@hotmail.com";
$sendmail = new SendMail($my_file, $my_path, $my_mail_to, $my_mail, $my_name, $my_replyto, $my_subject, $my_message);
*/
?>