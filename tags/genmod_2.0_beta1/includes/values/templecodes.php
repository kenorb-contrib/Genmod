<?php
/**
 * Displays the details about a repository record.
 * Also shows how many sources reference this repository.
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
 * @subpackage Display
 * @version $Id$
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

$TEMPLE_CODES = array();
$TEMPLE_CODES["ABA"]=	"Aba, Nigeria";
$TEMPLE_CODES["ACCRA"]=	"Accra, Ghana";
$TEMPLE_CODES["ADELA"]=	"Adelaide, Australia";
$TEMPLE_CODES["ALBUQ"]=	"Albuquerque, New Mexico";
$TEMPLE_CODES["ANCHO"]=	"Anchorage, Alaska";
$TEMPLE_CODES["SAMOA"]=	"Apia, Samoa";
$TEMPLE_CODES["ASUNC"]=	"Asuncion, Paraguay";
$TEMPLE_CODES["ATLAN"]=	"Atlanta, Georgia";
$TEMPLE_CODES["SWISS"]=	"Bern, Switzerland";
$TEMPLE_CODES["BOGOT"]=	"Bogota, Columbia";
$TEMPLE_CODES["BILLI"]=	"Billings, Montana";
$TEMPLE_CODES["BIRMI"]=	"Birmingham, Alabama";
$TEMPLE_CODES["BISMA"]=	"Bismarck, North Dakota";
$TEMPLE_CODES["BOGOT"]=	"Bogota, Colombia";
$TEMPLE_CODES["BOISE"]=	"Boise, Idaho";
$TEMPLE_CODES["BOSTO"]=	"Boston, Massachusetts";
$TEMPLE_CODES["BOUNT"]=	"Bountiful, Utah";
$TEMPLE_CODES["BRISB"]=	"Brisbane, Australia";
$TEMPLE_CODES["BROUG"]=	"Baton Rouge, Louisiana";
$TEMPLE_CODES["BAIRE"]=	"Buenos Aires, Argentina";
$TEMPLE_CODES["CAMPI"]=	"Campinas, Brazil";
$TEMPLE_CODES["CARAC"]=	"Caracas, Venezuela";
$TEMPLE_CODES["ALBER"]=	"Cardston, Alberta, Canada";
$TEMPLE_CODES["CHICA"]=	"Chicago, Illinois";
$TEMPLE_CODES["CIUJU"]=	"Ciudad Juarez, Mexico";
$TEMPLE_CODES["COCHA"]=	"Cochabamba, Bolivia";
$TEMPLE_CODES["COLJU"]=	"Colonia Juarez, Mexico";
$TEMPLE_CODES["COLSC"]=	"Columbia, South Carolina";
$TEMPLE_CODES["COLUM"]=	"Columbus, Ohio";
$TEMPLE_CODES["COPEN"]=	"Copenhagen, Denmark";
$TEMPLE_CODES["CRIVE"]=	"Columbia River, Washington";
$TEMPLE_CODES["DALLA"]=	"Dallas, Texas";
$TEMPLE_CODES["DENVE"]=	"Denver, Colorado";
$TEMPLE_CODES["DETRO"]=	"Detroit, Michigan";
$TEMPLE_CODES["EDMON"]=	"Edmonton, Alberta, Canada";
$TEMPLE_CODES["EHOUS"]=	"ENDOWMENT HOUSE";
$TEMPLE_CODES["FRANK"]=	"Frankfurt am Main, Germany";  // There's also a Frankfurt an der Oder in Germany
$TEMPLE_CODES["FREIB"]=	"Freiburg, Germany";
$TEMPLE_CODES["FRESN"]=	"Fresno, California";
$TEMPLE_CODES["FUKUO"]=	"Fukuoka, Japan";
$TEMPLE_CODES["GUADA"]=	"Guadalajara, Mexico";
$TEMPLE_CODES["GUATE"]=	"Guatemala City, Guatemala";
$TEMPLE_CODES["GUAYA"]=	"Guayaquil, Ecuador";
$TEMPLE_CODES["HAGUE"]=	"The Hague, Netherlands";
$TEMPLE_CODES["HALIF"]=	"Halifax, Nova Scotia, Canada";
$TEMPLE_CODES["NZEAL"]=	"Hamilton, New Zealand";
$TEMPLE_CODES["HARTF"]=	"Hartford, Connecticut";
$TEMPLE_CODES["HELSI"]=	"Helsinki, Finland";
$TEMPLE_CODES["HERMO"]=	"Hermosillo, Mexico";
$TEMPLE_CODES["HKONG"]=	"Hong Kong";
$TEMPLE_CODES["HOUST"]=	"Houston, Texas";
$TEMPLE_CODES["IFALL"]=	"Idaho Falls, Idaho";
$TEMPLE_CODES["JOHAN"]=	"Johannesburg, South Africa";
$TEMPLE_CODES["JRIVE"]=	"Jordan River, Utah";
$TEMPLE_CODES["KIEV"]=	"Kiev, Ukraine";
$TEMPLE_CODES["KONA"]=	"Kona, Hawaii";
$TEMPLE_CODES["HAWAI"]=	"Laie, Hawaii";
$TEMPLE_CODES["LVEGA"]=	"Las Vegas, Nevada";
$TEMPLE_CODES["LIMA"]=	"Lima, Peru";
$TEMPLE_CODES["LOGAN"]=	"Logan, Utah";
$TEMPLE_CODES["LONDO"]=	"London, England";
$TEMPLE_CODES["LANGE"]=	"Los Angeles, California";
$TEMPLE_CODES["LOUIS"]=	"Louisville, Kentucky";
$TEMPLE_CODES["LUBBO"]=	"Lubbock, Texas";
$TEMPLE_CODES["MADRI"]=	"Madrid, Spain";
$TEMPLE_CODES["MANIL"]=	"Manila, Philippines";
$TEMPLE_CODES["MANTI"]=	"Manti, Utah";
$TEMPLE_CODES["MEDFO"]=	"Medford, Oregon";
$TEMPLE_CODES["MELBO"]=	"Melbourne, Australia";
$TEMPLE_CODES["MEMPH"]=	"Memphis, Tennessee";
$TEMPLE_CODES["MERID"]=	"Merida, Mexico";
$TEMPLE_CODES["ARIZO"]=	"Mesa, Arizona";
$TEMPLE_CODES["MEXIC"]=	"Mexico City, Mexico";
$TEMPLE_CODES["MONTE"]=	"Monterrey, Mexico";
$TEMPLE_CODES["MNTVD"]=	"Montevideo, Uruguay";
$TEMPLE_CODES["MONTI"]=	"Monticello, Utah";
$TEMPLE_CODES["MONTR"]=	"Montreal, Quebec, Canada";
$TEMPLE_CODES["MTIMP"]=	"Mt. Timpanogos, Utah";
$TEMPLE_CODES["NASHV"]=	"Nashville, Tennessee";
$TEMPLE_CODES["NAUV2"]=	"Nauvoo, Illinois (new)";
$TEMPLE_CODES["NAUVO"]=	"Nauvoo, Illinois (original)";
$TEMPLE_CODES["NBEAC"]=	"Newport Beach, California";
$TEMPLE_CODES["NYORK"]=	"New York, New York";
$TEMPLE_CODES["NUKUA"]=	"Nuku'Alofa, Tonga";
$TEMPLE_CODES["OAKLA"]=	"Oakland, California";
$TEMPLE_CODES["OAXAC"]=	"Oaxaca, Mexico";
$TEMPLE_CODES["OGDEN"]=	"Ogden, Utah";
$TEMPLE_CODES["OKLAH"]=	"Oklahoma City, Oklahoma";
$TEMPLE_CODES["ORLAN"]=	"Orlando, Florida";
$TEMPLE_CODES["PALEG"]=	"Porto Alegre, Mexico";
$TEMPLE_CODES["PALMY"]=	"Palmyra, New York";
$TEMPLE_CODES["PAPEE"]=	"Papeete, Tahiti";
$TEMPLE_CODES["PERTH"]=	"Perth, Australia";
$TEMPLE_CODES["PORTL"]=	"Portland, Oregon";
$TEMPLE_CODES["POFFI"]=	"PRESIDENT'S OFFICE";
$TEMPLE_CODES["PREST"]=	"Preston, England";
$TEMPLE_CODES["PROVO"]=	"Provo, Utah";
$TEMPLE_CODES["RALEI"]=	"Raleigh, North Carolina";
$TEMPLE_CODES["RECIF"]=	"Recife, Brazil";
$TEMPLE_CODES["REDLA"]=	"Redlands, California";
$TEMPLE_CODES["REGIN"]=	"Regina, Saskatchewan, Canada";
$TEMPLE_CODES["RENO"]=	"Reno, Nevada";
$TEMPLE_CODES["SACRA"]=	"Sacramento, California";
$TEMPLE_CODES["SLAKE"]=	"Salt Lake City, Utah";
$TEMPLE_CODES["SANTO"]=	"San Antonio, Texas";
$TEMPLE_CODES["SDIEG"]=	"San Diego, California";
$TEMPLE_CODES["SJOSE"]= "San Jose, Costa Rica";
$TEMPLE_CODES["SANTI"]=	"Santiago, Chile";
$TEMPLE_CODES["SDOMI"]=	"Santo Domingo, Dom. Rep.";
$TEMPLE_CODES["SPAUL"]=	"Sao Paulo, Brazil";
$TEMPLE_CODES["SEATT"]=	"Seattle, Washington";
$TEMPLE_CODES["SEOUL"]=	"Seoul, Korea";
$TEMPLE_CODES["SNOWF"]= "Snowflake, Arizona";
$TEMPLE_CODES["SPOKA"]= "Spokane, Washington";
$TEMPLE_CODES["SGEOR"]=	"St. George, Utah";
$TEMPLE_CODES["SLOUI"]=	"St. Louis, Missouri";
$TEMPLE_CODES["SPMIN"]= "St. Paul, Minnesota";
$TEMPLE_CODES["STOCK"]=	"Stockholm, Sweden";
$TEMPLE_CODES["SUVA"]=	"Suva, Fiji";
$TEMPLE_CODES["SYDNE"]=	"Sydney, Australia";
$TEMPLE_CODES["TAIPE"]=	"Taipei, Taiwan";
$TEMPLE_CODES["TAMPI"]=	"Tampico, Mexico";
$TEMPLE_CODES["TOKYO"]=	"Tokyo, Japan";
$TEMPLE_CODES["TORNO"]=	"Toronto, Ontario, Canada";
$TEMPLE_CODES["TGUTI"]=	"Tuxtla Gutierrez, Mexico";
$TEMPLE_CODES["VERAC"]=	"Veracruz, Mexico";
$TEMPLE_CODES["VERNA"]=	"Vernal, Utah";
$TEMPLE_CODES["VILLA"]=	"Villa Hermosa, Mexico";
$TEMPLE_CODES["WASHI"]=	"Washington, DC";
$TEMPLE_CODES["WINTE"]=	"Winter Quarters, Nebraska";

$STATUS_CODES = array();
$STATUS_CODES["CHILD"]= "Died as a child: exempt";
$STATUS_CODES["INFANT"] = "Died as an infant: exempt";
$STATUS_CODES["STILLBORN"] = "Stillborn: exempt";
$STATUS_CODES["BIC"] = "Born in the covenant";
$STATUS_CODES["SUBMITTED"] = "Submitted but not yet cleared";
$STATUS_CODES["UNCLEARED"] = "Uncleared: insufficient data";
$STATUS_CODES["CLEARED"] = "Cleared but not yet completed";
$STATUS_CODES["COMPLETED"] = "Completed; date unknown";
$STATUS_CODES["PRE-1970"] = "Completed before 1970; date not available";
$STATUS_CODES["CANCELLED"] = "Sealing cancelled (divorce)";
$STATUS_CODES["DNS"] = "Do Not Seal: unauthorized";
$STATUS_CODES["DNS/CAN"] = "Do Not Seal, previous sealing cancelled";
?>
