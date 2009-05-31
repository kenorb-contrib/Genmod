<?php
/*=================================================
   charset=utf-8
   Projekt: Genmod
   Datei: facts.de.php
   Autor: John Finlay
   Übersetzung:        Bach Jürgen, Norgaz Kurt, Pluntke Peter
   Bemerkungen:        Definiert ein Array von GEDCOM codes und die Deutsche Bezeichnung dessen, was diese enthalten.
   Änderungen:        05.08.2002 - Datei erstellt
                   11.02.2003 - Renamed from facts.ge.php to facts.de.php
===================================================*/
# $Id: facts.de.php,v 1.1 2005/10/23 21:54:42 roland-d Exp $
if (preg_match("/facts\...\.php$/", $_SERVER["SCRIPT_NAME"])>0) {
        print "You cannot access a language file directly.";
        exit;
}
// -- Define a fact array to map GEDCOM tags with their German values
$factarray["ABBR"]	= "Abkürzung";
$factarray["ADDR"]	= "Adresse";
$factarray["ADR1"]	= "Adresse 1";
$factarray["ADR2"]	= "Adresse 2";
$factarray["ADOP"]	= "Adoption";
$factarray["AFN"]	= "Vorfahren Nummer (AFN)";
$factarray["AGE"]	= "Alter";
$factarray["AGNC"]	= "Büro";
$factarray["ALIA"]	= "Alias";
$factarray["ANCE"]	= "Vorfahren";
$factarray["ANCI"]	= "Vorfahren Interesse";
$factarray["ANUL"]	= "Annullierung";
$factarray["ASSO"]	= "Vereinigung";
$factarray["AUTH"]	= "Verfasser";
$factarray["BAPL"]	= "HLT Taufe";
$factarray["BAPM"]	= "Taufe";
$factarray["BARM"]	= "Bar Mitzvah";
$factarray["BASM"]	= "Bas Mitzvah";
$factarray["BIRT"]	= "Geburt";
$factarray["BLES"]	= "Segen";
$factarray["BLOB"]	= "Binäres Daten-Objekt";
$factarray["BURI"]	= "Beerdigung";
$factarray["CALN"]	= "Rufnummer";
$factarray["CAST"]	= "Kaste / Soziale Stellung / Status";
$factarray["CAUS"]	= "Todesursache";
$factarray["CEME"]  = "Friedhof";
$factarray["CENS"]	= "Volkszählung";
$factarray["CHAN"]	= "Letzte Änderung";
$factarray["CHAR"]	= "Zeichensatz";
$factarray["CHIL"]	= "Kind";
$factarray["CHR"]	= "Kleinkind Taufe";
$factarray["CHRA"]	= "Erwachsenen Taufe";
$factarray["CITY"]	= "Stadt";
$factarray["CONF"]	= "Konfirmation";
$factarray["CONL"]	= "HLT Konfirmation";
$factarray["COPR"]	= "Copyright";
$factarray["CORP"]	= "Firma";
$factarray["CREM"]	= "Einäscherung";
$factarray["CTRY"]	= "Land";
$factarray["DATA"]	= "Daten";
$factarray["DATE"]	= "Datum";
$factarray["DEAT"]	= "Tod";
$factarray["DESC"]	= "Nachfahren";
$factarray["DESI"]	= "Nachfahren Interesse";
$factarray["DEST"]	= "Bestimmung";
$factarray["DIV"]	= "Scheidung";
$factarray["DIVF"]	= "Scheidungsakte";
$factarray["DSCR"]	= "Beschreibung";
$factarray["EDUC"]	= "Ausbildung";
$factarray["EMIG"]	= "Auswanderung";
$factarray["ENDL"]	= "HLT Begabung";
$factarray["ENGA"]	= "Verlobung";
$factarray["EVEN"]	= "Ereignis";
$factarray["FAM"]	= "Familie";
$factarray["FAMC"]	= "Familie als Kind";
$factarray["FAMF"]	= "Familien-Akte";
$factarray["FAMS"]	= "Familie als Ehepartner";
$factarray["FCOM"]	= "Erstkommunion";
$factarray["FILE"]	= "Externe Datei";
$factarray["FORM"]	= "Format";
$factarray["GIVN"]	= "Vornamen";
$factarray["GRAD"]	= "Schulabschluß";
$factarray["IDNO"]	= "Identifikationsnummer";
$factarray["IMMI"]	= "Einwanderung";
$factarray["LEGA"]	= "Erbe";
$factarray["MARB"]	= "Eheaufgebot";
$factarray["MARC"]	= "Ehevertrag";
$factarray["MARL"]	= "Ehegenehmigung";
$factarray["MARR"]	= "Heirat";
$factarray["MARS"]	= "Ehevergleich";
$factarray["MEDI"]	= "Multimedia Typ";
$factarray["NAME"]	= "Name";
$factarray["NATI"]	= "Nationalität";
$factarray["NATU"]	= "Einbürgerung";
$factarray["NCHI"]	= "Anzahl der Kinder";
$factarray["NICK"]	= "Spitzname";
$factarray["NMR"]	= "Anzahl der Ehen";
$factarray["NOTE"]	= "Notiz";
$factarray["NPFX"]	= "Titel / Präfix";
$factarray["NSFX"]	= "Namenszusatz";
$factarray["OBJE"]	= "Multimedia Objekt";
$factarray["OCCU"]	= "Beruf";
$factarray["ORDI"]	= "Anordnung";
$factarray["ORDN"]	= "Ordination";
$factarray["PAGE"]	= "Auszeichnungs Details";
$factarray["PEDI"]	= "Stammbaum";
$factarray["PLAC"]	= "Ort";
$factarray["PHON"]	= "Telefon";
$factarray["POST"]	= "Postleitzahl";
$factarray["PROB"]	= "Testamentsbestätigung";
$factarray["PROP"]	= "Besitz";
$factarray["PUBL"]	= "Veröffentlichung";
$factarray["QUAY"]	= "Qualität der Daten";
$factarray["REPO"]	= "Lagerort";
$factarray["REFN"]	= "Referenz Nummer";
$factarray["RELA"]	= "Verwandtschaft ";
$factarray["RELI"]	= "Religion";
$factarray["RESI"]	= "Wohnort";
$factarray["RESN"]	= "Einschränkung";
$factarray["RETI"]	= "Ausscheiden";
$factarray["RFN"]	= "Datensatznummer";
$factarray["RIN"]	= "Daten ID-Nummer";
$factarray["ROLE"]	= "Rolle";
$factarray["SEX"]	= "Geschlecht";
$factarray["SLGC"]	= "HLT Kindes-Siegelung";
$factarray["SLGS"]	= "HLT Ehepartner-Siegelung";
$factarray["SOUR"]	= "Quelle";
$factarray["SPFX"]	= "Nachnamenszusatz";
$factarray["SSN"]	= "Sozialversicherungs-Nummer";
$factarray["STAE"]	= "Staat";
$factarray["STAT"]	= "Status";
$factarray["SUBM"]	= "Übermittler";
$factarray["SUBN"]	= "Übermittlung";
$factarray["SURN"]	= "Nachname";
$factarray["TEMP"]	= "Tempel";
$factarray["TEXT"]	= "Text";
$factarray["TIME"]	= "Zeit";
$factarray["TITL"]	= "Titel";
$factarray["TYPE"]	= "Typ";
$factarray["WILL"]	= "Testament";
$factarray["_EMAIL"]	= "Email-Adresse";
$factarray["EMAIL"]	= "Email-Addresse";
$factarray["_TODO"]	= "ToDo";
$factarray["_UID"]	= "Universelle<br />Identifikationsnummer<br />(UID)";
$factarray["_GMU"]	= "Zuletzt geändert durch";
$factarray["_PRIM"]	= "Bevorzugtes Bild";
$factarray["_THUM"]	= "Dieses Bild als Thumbnail verwenden ?";

// These facts are specific to GEDCOM exports from Family Tree Maker
$factarray["_MDCL"]	= "Medizinische Information";
$factarray["_DEG"]	= "Akademischer Grad";
$factarray["_MILT"]	= "Militärdienst";
$factarray["_SEPR"]	= "Getrennt";
$factarray["_DETS"]	= "Tot eines Ehepartners";
$factarray["CITN"]	= "Staatsangehörigkeit";
$factarray["_FA1"]	= "Ereignis 1";
$factarray["_FA2"]	= "Ereignis 2";
$factarray["_FA3"]	= "Ereignis 3";
$factarray["_FA4"]	= "Ereignis 4";
$factarray["_FA5"]	= "Ereignis 5";
$factarray["_FA6"]	= "Ereignis 6";
$factarray["_FA7"]	= "Ereignis 7";
$factarray["_FA8"]	= "Ereignis 8";
$factarray["_FA9"]	= "Ereignis 9";
$factarray["_FA10"]	= "Ereignis 10";
$factarray["_FA11"]	= "Ereignis 11";
$factarray["_FA12"]	= "Ereignis 12";
$factarray["_FA13"]	= "Ereignis 13";
$factarray["_MREL"]	= "Verwandtschaft zur Mutter";
$factarray["_FREL"]	= "Verwandtschaft zum Vater";
$factarray["_MSTAT"]	= "Beginn des Ehe-Status";
$factarray["_MEND"]	= "Ende des Ehe-Status";

// GEDCOM 5.5.1 related facts
$factarray["FAX"] = "FAX";
$factarray["FACT"] = "Ereignis";
$factarray["WWW"] = "Internetseite";
$factarray["MAP"] = "Karte";
$factarray["LATI"] = "Breitengrad";
$factarray["LONG"] = "Längengrad";
$factarray["FONE"] = "Phonetisch";
$factarray["ROMN"] = "Romanisiert";

// PAF related facts
$factarray["_NAME"] = "Name";
$factarray["URL"] = "Internet URL";
$factarray["_HEB"] = "Hebräisch";
$factarray["_SCBK"] = "Sammelalbum";
$factarray["_TYPE"] = "Multimedia-Typ";
$factarray["_SSHOW"] = "Diashow";

// Rootsmagic
$factarray["_SUBQ"]= "Kurzfassung";
$factarray["_BIBL"] = "Quellenverzeichnis";

// Other common customized facts
$factarray["_ADPF"]	= "Vom Vater adoptiert";
$factarray["_ADPM"]	= "Von der Mutter adoptiert";
$factarray["_AKAN"]	= "Auch bekannt als";
$factarray["_AKA"]	= "Auch bekannt als";
$factarray["_BRTM"]	= "Brit mila";
$factarray["_COML"]	= "eheähnliche Lebensgemeinschaft";
$factarray["_EYEC"]	= "Augenfarbe";
$factarray["_FNRL"]	= "Bestattung";
$factarray["_HAIR"]	= "Haarfarbe";
$factarray["_HEIG"]	= "Größe";
$factarray["_HOL"]  = "Holocaust";
$factarray["_INTE"]	= "Begraben";
$factarray["_MARI"]	= "Heiratsabsicht";
$factarray["_MBON"]	= "Verlobung";
$factarray["_MEDC"]	= "Gesundheitszustand";
$factarray["_MILI"]	= "Militär";
$factarray["_NMR"]	= "unverheiratet";
$factarray["_NLIV"]	= "nicht lebend";
$factarray["_NMAR"]	= "nie verheiratet";
$factarray["_PRMN"]	= "permanente Nummer";
$factarray["_WEIG"]	= "Gewicht";
$factarray["_YART"]	= "Yartzeit";
$factarray["_MARNM"]	= "Nachname in Ehe";
$factarray["_STAT"] = "Ehestatus";
$factarray["COMM"]	= "Kommentar";

// Aldfaer related facts
$factarray["MARR_CIVIL"] = "standesamtliche Hochzeit";
$factarray["MARR_RELIGIOUS"] = "kirchliche Hochzeit";
$factarray["MARR_PARTNERS"] = "eingetragene Lebensgemeinschaft";
$factarray["MARR_UNKNOWN"] = "Art der Hochzeit unbekannt";
$factarray["_HNM"] = "Hebräischer Name";

if (file_exists($GM_BASE_DIRECTORY . "languages/facts.de.extra.php")) require $GM_BASE_DIRECTORY . "languages/facts.de.extra.php";

?>