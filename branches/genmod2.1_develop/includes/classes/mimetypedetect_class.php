<?php
/**
 * Class file for detection of mimetypes
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
 * @subpackage DataModel
 * @version $Id: mimetypedetect_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

class MimeTypeDetect {
	
	public $classname = "MimeTypeDetect";	// Name of the class
	private $mime_types = array();			// Array of mime types
	private $found_mime_type = array();		// Holder for the results to return from this class
	
	public function __construct() {
		$this->LoadMimeTypes();
	}
	
	private function LoadMimeTypes() {
		// Signatures
		$this->mime_types[0]['signature'] = '474946383761';
		$this->mime_types[1]['signature'] = '424D';
		$this->mime_types[2]['signature'] = '4D5A';
		$this->mime_types[3]['signature'] = '504B0304';
		$this->mime_types[4]['signature'] = 'D0CF11E0A1B11AE1';
		$this->mime_types[5]['signature'] = '0100000058000000';
		$this->mime_types[6]['signature'] = '03000000C466C456';
		$this->mime_types[7]['signature'] = '3F5F0300';
		$this->mime_types[8]['signature'] = '1F8B08';
		$this->mime_types[9]['signature'] = '28546869732066696C65';
		$this->mime_types[10]['signature'] = '0000010000';
		$this->mime_types[11]['signature'] = '4C000000011402';
		$this->mime_types[12]['signature'] = '25504446';
		$this->mime_types[13]['signature'] = '5245474544495434';
		$this->mime_types[14]['signature'] = '7B5C727466';
		$this->mime_types[15]['signature'] = 'lh';
		$this->mime_types[16]['signature'] = 'MThd';
		$this->mime_types[17]['signature'] = '0A050108';
		$this->mime_types[18]['signature'] = '25215053';
		$this->mime_types[19]['signature'] = '2112';
		$this->mime_types[20]['signature'] = '1A02';
		$this->mime_types[21]['signature'] = '1A03';
		$this->mime_types[22]['signature'] = '1A04';
		$this->mime_types[23]['signature'] = '1A08';
		$this->mime_types[24]['signature'] = '1A09';
		$this->mime_types[25]['signature'] = '60EA';
		$this->mime_types[26]['signature'] = '41564920';
		$this->mime_types[27]['signature'] = '425A68';
		$this->mime_types[28]['signature'] = '49536328';
		$this->mime_types[29]['signature'] = '4C01';
		$this->mime_types[30]['signature'] = '303730373037';
		$this->mime_types[31]['signature'] = '4352555348';
		$this->mime_types[32]['signature'] = '3ADE68B1';
		$this->mime_types[33]['signature'] = '1F8B';
		$this->mime_types[34]['signature'] = '91334846';
		$this->mime_types[35]['signature'] = '3C68746D6C3E';
		$this->mime_types[36]['signature'] = '3C48544D4C3E';
		$this->mime_types[37]['signature'] = '3C21444F4354';
		$this->mime_types[38]['signature'] = '100';
		$this->mime_types[39]['signature'] = '5F27A889';
		$this->mime_types[40]['signature'] = '2D6C68352D';
		$this->mime_types[41]['signature'] = '20006040600';
		$this->mime_types[42]['signature'] = '00001A0007800100';
		$this->mime_types[43]['signature'] = '00001A0000100400';
		$this->mime_types[44]['signature'] = '20006800200';
		$this->mime_types[45]['signature'] = '00001A0002100400';
		$this->mime_types[46]['signature'] = '5B7665725D';
		$this->mime_types[47]['signature'] = '300000041505052';
		$this->mime_types[48]['signature'] = '1A0000030000';
		$this->mime_types[49]['signature'] = '4D47582069747064';
		$this->mime_types[50]['signature'] = '4D534346';
		$this->mime_types[51]['signature'] = '4D546864';
		$this->mime_types[52]['signature'] = '000001B3';
		$this->mime_types[53]['signature'] = '0902060000001000B9045C00';
		$this->mime_types[54]['signature'] = '0904060000001000F6055C00';
		$this->mime_types[55]['signature'] = '7FFE340A';
		$this->mime_types[56]['signature'] = '1234567890FF';
		$this->mime_types[57]['signature'] = '31BE000000AB0000';
		$this->mime_types[58]['signature'] = '1A00000300001100';
		$this->mime_types[59]['signature'] = '7E424B00';
		$this->mime_types[60]['signature'] = '504B0304';
		$this->mime_types[61]['signature'] = '89504E470D0A';
		$this->mime_types[62]['signature'] = '6D646174';
		$this->mime_types[63]['signature'] = '6D646174';
		$this->mime_types[64]['signature'] = '52617221';
		$this->mime_types[65]['signature'] = '2E7261FD';
		$this->mime_types[66]['signature'] = 'EDABEEDB';
		$this->mime_types[67]['signature'] = '2E736E64';
		$this->mime_types[68]['signature'] = '53495421';
		$this->mime_types[69]['signature'] = '53747566664974';
		$this->mime_types[70]['signature'] = '1F9D';
		$this->mime_types[71]['signature'] = '49492A';
		$this->mime_types[72]['signature'] = '4D4D2A';
		$this->mime_types[73]['signature'] = '554641';
		$this->mime_types[74]['signature'] = '57415645666D74';
		$this->mime_types[75]['signature'] = 'D7CDC69A';
		$this->mime_types[76]['signature'] = '4C000000';
		$this->mime_types[77]['signature'] = '504B3030504B0304';
		$this->mime_types[78]['signature'] = 'FF575047';
		$this->mime_types[79]['signature'] = 'FF575043';
		$this->mime_types[80]['signature'] = '3C3F786D6C';
		$this->mime_types[81]['signature'] = 'FFFE3C0052004F004F0054005300540055004200';
		$this->mime_types[82]['signature'] = '3C21454E54495459';
		$this->mime_types[83]['signature'] = '5A4F4F20';
		$this->mime_types[84]['signature'] = 'FFD8FFFE00';
		$this->mime_types[85]['signature'] = 'FFD8FFE000';
		$this->mime_types[86]['signature'] = '474946383961';
		
		// Extensions
		$this->mime_types[0]['extension'] = '.gif';
		$this->mime_types[1]['extension'] = '.bmp';
		$this->mime_types[2]['extension'] = '.exe;.com;.386;.ax;.acm;.sys;.dll;.drv;.flt;.fon;.ocx;.scr;.lrc;.vxd;.cpl;.x32';
		$this->mime_types[3]['extension'] = '.zip';
		$this->mime_types[4]['extension'] = '.doc;.xls;.xlt;.ppt;.apr';
		$this->mime_types[5]['extension'] = '.emf';
		$this->mime_types[6]['extension'] = '.evt';
		$this->mime_types[7]['extension'] = '.gid;.hlp;.lhp';
		$this->mime_types[8]['extension'] = '.gz';
		$this->mime_types[9]['extension'] = '.hqx';
		$this->mime_types[10]['extension'] = '.ico';
		$this->mime_types[11]['extension'] = '.lnk';
		$this->mime_types[12]['extension'] = '.pdf';
		$this->mime_types[13]['extension'] = '.reg';
		$this->mime_types[14]['extension'] = '.rtf';
		$this->mime_types[15]['extension'] = '.lzh';
		$this->mime_types[16]['extension'] = '.mid';
		$this->mime_types[17]['extension'] = '.pcx';
		$this->mime_types[18]['extension'] = '.eps';
		$this->mime_types[19]['extension'] = '.ain';
		$this->mime_types[20]['extension'] = '.arc';
		$this->mime_types[21]['extension'] = '.arc';
		$this->mime_types[22]['extension'] = '.arc';
		$this->mime_types[23]['extension'] = '.arc';
		$this->mime_types[24]['extension'] = '.arc';
		$this->mime_types[25]['extension'] = '.arj';
		$this->mime_types[26]['extension'] = '.avi';
		$this->mime_types[27]['extension'] = '.bz;.bz2';
		$this->mime_types[28]['extension'] = '.cab';
		$this->mime_types[29]['extension'] = '.obj';
		$this->mime_types[30]['extension'] = '.tar;.cpio';
		$this->mime_types[31]['extension'] = '.cru;.crush';
		$this->mime_types[32]['extension'] = '.dcx';
		$this->mime_types[33]['extension'] = '.gz;.tar;.tgz';
		$this->mime_types[34]['extension'] = '.hap';
		$this->mime_types[35]['extension'] = '.htm;.html';
		$this->mime_types[36]['extension'] = '.htm;.html';
		$this->mime_types[37]['extension'] = '.htm;.html';
		$this->mime_types[38]['extension'] = '.ico';
		$this->mime_types[39]['extension'] = '.jar';
		$this->mime_types[40]['extension'] = '.lha';
		$this->mime_types[41]['extension'] = '.wk1;.wks';
		$this->mime_types[42]['extension'] = '.fm3';
		$this->mime_types[43]['extension'] = '.wk3';
		$this->mime_types[44]['extension'] = '.fmt';
		$this->mime_types[45]['extension'] = '.wk4';
		$this->mime_types[46]['extension'] = '.ami';
		$this->mime_types[47]['extension'] = '.adx';
		$this->mime_types[48]['extension'] = '.nsf;.ntf';
		$this->mime_types[49]['extension'] = '.ds4';
		$this->mime_types[50]['extension'] = '.cab';
		$this->mime_types[51]['extension'] = '.mid';
		$this->mime_types[52]['extension'] = '.mpg;.mpeg';
		$this->mime_types[53]['extension'] = '.xls';
		$this->mime_types[54]['extension'] = '.xls';
		$this->mime_types[55]['extension'] = '.doc';
		$this->mime_types[56]['extension'] = '.doc';
		$this->mime_types[57]['extension'] = '.doc';
		$this->mime_types[58]['extension'] = '.nsf';
		$this->mime_types[59]['extension'] = '.psp';
		$this->mime_types[60]['extension'] = '.zip';
		$this->mime_types[61]['extension'] = '.png';
		$this->mime_types[62]['extension'] = '.mov';
		$this->mime_types[63]['extension'] = '.qt';
		$this->mime_types[64]['extension'] = '.rar';
		$this->mime_types[65]['extension'] = '.ra;.ram';
		$this->mime_types[66]['extension'] = '.rpm';
		$this->mime_types[67]['extension'] = '.au';
		$this->mime_types[68]['extension'] = '.sit';
		$this->mime_types[69]['extension'] = '.sit';
		$this->mime_types[70]['extension'] = '.z';
		$this->mime_types[71]['extension'] = '.tif;.tiff';
		$this->mime_types[72]['extension'] = '.tif;.tiff';
		$this->mime_types[73]['extension'] = '.ufa';
		$this->mime_types[74]['extension'] = '.wav';
		$this->mime_types[75]['extension'] = '.wmf';
		$this->mime_types[76]['extension'] = '.lnk';
		$this->mime_types[77]['extension'] = '.zip';
		$this->mime_types[78]['extension'] = '.wpg';
		$this->mime_types[79]['extension'] = '.wp';
		$this->mime_types[80]['extension'] = '.xml';
		$this->mime_types[81]['extension'] = '.xml';
		$this->mime_types[82]['extension'] = '.dtd';
		$this->mime_types[83]['extension'] = '.zoo';
		$this->mime_types[84]['extension'] = '.jpeg;.jpe;.jpg';
		$this->mime_types[85]['extension'] = '.jpeg;.jpe;.jpg';
		$this->mime_types[86]['extension'] = '.gif';
		
		// Descriptions
		$this->mime_types[0]['description'] = 'GIF 87A';
		$this->mime_types[1]['description'] = 'Windows Bitmap';
		$this->mime_types[2]['description'] = 'Executable File ';
		$this->mime_types[3]['description'] = 'Zip Compressed';
		$this->mime_types[4]['description'] = 'MS Compound Document v1 or Lotus Approach APR file';
		$this->mime_types[5]['description'] = 'Extended (Enhanced) Windows Metafile Format';
		$this->mime_types[6]['description'] = 'Windows NT/2000 Event Viewer Log File';
		$this->mime_types[7]['description'] = 'Windows Help File';
		$this->mime_types[8]['description'] = 'GZ Compressed File';
		$this->mime_types[9]['description'] = 'Macintosh BinHex 4 Compressed Archive';
		$this->mime_types[10]['description'] = 'Icon File';
		$this->mime_types[11]['description'] = 'Windows Link File';
		$this->mime_types[12]['description'] = 'Adobe PDF File';
		$this->mime_types[13]['description'] = 'egistry Data File';
		$this->mime_types[14]['description'] = 'Rich Text Format File';
		$this->mime_types[15]['description'] = 'Lz compression file';
		$this->mime_types[16]['description'] = 'usical Instrument Digital Interface MIDI-sequention Sound';
		$this->mime_types[17]['description'] = 'C Paintbrush Bitmap Graphic';
		$this->mime_types[18]['description'] = 'Adobe EPS File';
		$this->mime_types[19]['description'] = 'AIN Archive File';
		$this->mime_types[20]['description'] = 'ARC/PKPAK Compressed 1';
		$this->mime_types[21]['description'] = 'ARC/PKPAK Compressed 2';
		$this->mime_types[22]['description'] = 'ARC/PKPAK Compressed 3';
		$this->mime_types[23]['description'] = 'ARC/PKPAK Compressed 4';
		$this->mime_types[24]['description'] = 'ARC/PKPAK Compressed 5';
		$this->mime_types[25]['description'] = 'ARJ Compressed';
		$this->mime_types[26]['description'] = 'Audio Video Interleave (AVI)';
		$this->mime_types[27]['description'] = 'Bzip Archive';
		$this->mime_types[28]['description'] = 'Cabinet File';
		$this->mime_types[29]['description'] = 'Compiled Object Module';
		$this->mime_types[30]['description'] = 'CPIO Archive File';
		$this->mime_types[31]['description'] = 'CRUSH Archive File';
		$this->mime_types[32]['description'] = 'DCX Graphic File';
		$this->mime_types[33]['description'] = 'Gzip Archive File';
		$this->mime_types[34]['description'] = 'HAP Archive File';
		$this->mime_types[35]['description'] = 'HyperText Markup Language 1';
		$this->mime_types[36]['description'] = 'HyperText Markup Language 2';
		$this->mime_types[37]['description'] = 'HyperText Markup Language 3';
		$this->mime_types[38]['description'] = 'ICON File';
		$this->mime_types[39]['description'] = 'JAR Archive File';
		$this->mime_types[40]['description'] = 'LHA Compressed';
		$this->mime_types[41]['description'] = 'Lotus 123 v1 Worksheet';
		$this->mime_types[42]['description'] = 'Lotus 123 v3 FMT file';
		$this->mime_types[43]['description'] = 'Lotus 123 v3 Worksheet';
		$this->mime_types[44]['description'] = 'Lotus 123 v4 FMT file';
		$this->mime_types[45]['description'] = 'Lotus 123 v5';
		$this->mime_types[46]['description'] = 'Lotus Ami Pro';
		$this->mime_types[47]['description'] = 'Lotus Approach ADX file';
		$this->mime_types[48]['description'] = 'Lotus Notes Database/Template';
		$this->mime_types[49]['description'] = 'Micrografix Designer 4';
		$this->mime_types[50]['description'] = 'Microsoft CAB File Format';
		$this->mime_types[51]['description'] = 'Midi Audio File';
		$this->mime_types[52]['description'] = 'MPEG Movie';
		$this->mime_types[53]['description'] = 'MS Excel v2';
		$this->mime_types[54]['description'] = 'MS Excel v4';
		$this->mime_types[55]['description'] = 'MS Word';
		$this->mime_types[56]['description'] = 'MS Word 6.0';
		$this->mime_types[57]['description'] = 'MS Word for DOS 6.0';
		$this->mime_types[58]['description'] = 'Notes Database';
		$this->mime_types[59]['description'] = 'PaintShop Pro Image File';
		$this->mime_types[60]['description'] = 'PKZIP Compressed';
		$this->mime_types[61]['description'] = 'PNG Image File';
		$this->mime_types[62]['description'] = 'QuickTime Movie';
		$this->mime_types[63]['description'] = 'Quicktime Movie File';
		$this->mime_types[64]['description'] = 'RAR Archive File';
		$this->mime_types[65]['description'] = 'Real Audio File';
		$this->mime_types[66]['description'] = 'RPM Archive File';
		$this->mime_types[67]['description'] = 'SoundMachine Audio File';
		$this->mime_types[68]['description'] = 'Stuffit v1 Archive File';
		$this->mime_types[69]['description'] = 'Stuffit v5 Archive File';
		$this->mime_types[70]['description'] = 'TAR Compressed Archive File';
		$this->mime_types[71]['description'] = 'TIFF (Intel)';
		$this->mime_types[72]['description'] = 'TIFF (Motorola)';
		$this->mime_types[73]['description'] = 'UFA Archive File';
		$this->mime_types[74]['description'] = 'Wave Files';
		$this->mime_types[75]['description'] = 'Windows Meta File';
		$this->mime_types[76]['description'] = 'Windows Shortcut (Link File)';
		$this->mime_types[77]['description'] = 'WINZIP Compressed';
		$this->mime_types[78]['description'] = 'WordPerfect Graphics';
		$this->mime_types[79]['description'] = 'WordPerfect v5 or v6';
		$this->mime_types[80]['description'] = 'XML Document';
		$this->mime_types[81]['description'] = 'XML Document (ROOTSTUB)';
		$this->mime_types[82]['description'] = 'XML DTD';
		$this->mime_types[83]['description'] = 'ZOO Archive File';
		$this->mime_types[84]['description'] = 'JPG Graphic File';
		$this->mime_types[85]['description'] = 'JPG Graphic File';
		$this->mime_types[86]['description'] = 'GIF 89A';
		
		// Mime descriptions
		$this->mime_types[0]['mime_type'] = 'image/gif';
		$this->mime_types[1]['mime_type'] = 'image/bmp';
		$this->mime_types[2]['mime_type'] = '';
		$this->mime_types[3]['mime_type'] = 'application/zip';
		$this->mime_types[4]['mime_type'] = 'application/msword';
		$this->mime_types[5]['mime_type'] = '';
		$this->mime_types[6]['mime_type'] = '';
		$this->mime_types[7]['mime_type'] = '';
		$this->mime_types[8]['mime_type'] = '';
		$this->mime_types[9]['mime_type'] = '';
		$this->mime_types[10]['mime_type'] = '';
		$this->mime_types[11]['mime_type'] = '';
		$this->mime_types[12]['mime_type'] = 'application/pdf';
		$this->mime_types[13]['mime_type'] = '';
		$this->mime_types[14]['mime_type'] = 'application/rtf';
		$this->mime_types[15]['mime_type'] = '';
		$this->mime_types[16]['mime_type'] = 'audio/x-midi';
		$this->mime_types[17]['mime_type'] = '';
		$this->mime_types[18]['mime_type'] = '';
		$this->mime_types[19]['mime_type'] = '';
		$this->mime_types[20]['mime_type'] = '';
		$this->mime_types[21]['mime_type'] = '';
		$this->mime_types[22]['mime_type'] = '';
		$this->mime_types[23]['mime_type'] = '';
		$this->mime_types[24]['mime_type'] = '';
		$this->mime_types[25]['mime_type'] = '';
		$this->mime_types[26]['mime_type'] = 'video/msvideo';
		$this->mime_types[27]['mime_type'] = '';
		$this->mime_types[28]['mime_type'] = '';
		$this->mime_types[29]['mime_type'] = '';
		$this->mime_types[30]['mime_type'] = '';
		$this->mime_types[31]['mime_type'] = '';
		$this->mime_types[32]['mime_type'] = '';
		$this->mime_types[33]['mime_type'] = '';
		$this->mime_types[34]['mime_type'] = '';
		$this->mime_types[35]['mime_type'] = '';
		$this->mime_types[36]['mime_type'] = '';
		$this->mime_types[37]['mime_type'] = '';
		$this->mime_types[38]['mime_type'] = '';
		$this->mime_types[39]['mime_type'] = '';
		$this->mime_types[40]['mime_type'] = '';
		$this->mime_types[41]['mime_type'] = '';
		$this->mime_types[42]['mime_type'] = '';
		$this->mime_types[43]['mime_type'] = '';
		$this->mime_types[44]['mime_type'] = '';
		$this->mime_types[45]['mime_type'] = '';
		$this->mime_types[46]['mime_type'] = '';
		$this->mime_types[47]['mime_type'] = '';
		$this->mime_types[48]['mime_type'] = '';
		$this->mime_types[49]['mime_type'] = '';
		$this->mime_types[50]['mime_type'] = '';
		$this->mime_types[51]['mime_type'] = 'audio/x-midi';
		$this->mime_types[52]['mime_type'] = 'video/mpeg';
		$this->mime_types[53]['mime_type'] = '';
		$this->mime_types[54]['mime_type'] = '';
		$this->mime_types[55]['mime_type'] = '';
		$this->mime_types[56]['mime_type'] = '';
		$this->mime_types[57]['mime_type'] = '';
		$this->mime_types[58]['mime_type'] = '';
		$this->mime_types[59]['mime_type'] = '';
		$this->mime_types[60]['mime_type'] = '';
		$this->mime_types[61]['mime_type'] = 'image/png';
		$this->mime_types[62]['mime_type'] = 'video/quicktime';
		$this->mime_types[63]['mime_type'] = 'video/quicktime';
		$this->mime_types[64]['mime_type'] = '';
		$this->mime_types[65]['mime_type'] = '';
		$this->mime_types[66]['mime_type'] = '';
		$this->mime_types[67]['mime_type'] = '';
		$this->mime_types[68]['mime_type'] = '';
		$this->mime_types[69]['mime_type'] = '';
		$this->mime_types[70]['mime_type'] = '';
		$this->mime_types[71]['mime_type'] = 'image/tiff';
		$this->mime_types[72]['mime_type'] = '';
		$this->mime_types[73]['mime_type'] = '';
		$this->mime_types[74]['mime_type'] = 'audio/x-wav';
		$this->mime_types[75]['mime_type'] = '';
		$this->mime_types[76]['mime_type'] = '';
		$this->mime_types[77]['mime_type'] = 'application/zip';
		$this->mime_types[78]['mime_type'] = '';
		$this->mime_types[79]['mime_type'] = '';
		$this->mime_types[80]['mime_type'] = '';
		$this->mime_types[81]['mime_type'] = '';
		$this->mime_types[82]['mime_type'] = '';
		$this->mime_types[83]['mime_type'] = '';
		$this->mime_types[84]['mime_type'] = 'image/jpeg';
		$this->mime_types[85]['mime_type'] = 'image/jpeg';
		$this->mime_types[86]['mime_type'] = 'image/gif';
	}
	
	public function FindMimeType($filename) {
		if (file_exists($filename) && !is_dir($filename)) {
			$handle = fopen($filename, "r");
			$string = fread($handle, 20);
			$max_length_found = 0;
			foreach ($this->mime_types as $key => $type) {
				if (strpos(strtolower(bin2hex($string)), strtolower($type['signature']), 0) !== false) {
					if (strlen($type['signature']) > $max_length_found) {
						$max_length_found =  strlen($type['signature']);
						$this->found_mime_type['mime_type'] = $type['mime_type'];
						$this->found_mime_type['description'] = $type['description'];
						$this->found_mime_type['signature'] = $type['signature'];
						$this->found_mime_type['extension'] = $type['extension'];
					}
				}
			}
			fclose($handle);
			if (isset($this->found_mime_type['mime_type'])) return $this->found_mime_type;
			else return false;
		}
		else return false;
	}
}
?>
