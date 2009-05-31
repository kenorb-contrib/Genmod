<?php
/*=================================================
   charset=utf-8
   Project:	Genmod
   File:	facts.hu.php
   Author:	John Finlay
   Translation:	István Pető
   Comments:	Defines an array of GEDCOM codes and the hungarian name facts that they represent.
   Change Log:	03/25/03 - File Created
   		04/29/08 - Upgraded to v3.1
		04/26/10 - Upgraded to v3.2
		04/26/12 - Upgraded to v3.2.1
   2005.02.19 "Genmod" and "GEDCOM" made consistent across all language files  G.Kroll (canajun2eh)
===================================================*/
# $Id: facts.hu.php,v 1.1 2005/10/23 21:54:42 roland-d Exp $
if (preg_match("/facts\...\.php$/", $_SERVER["SCRIPT_NAME"])>0) {
	print "You cannot access a language file directly.";
	exit;
}
// -- Define a fact array to map GEDCOM tags with their Hungarian values
$factarray["ABBR"] = "Rövidítés";
$factarray["ADDR"] = "Lakcím:";
$factarray["ADR1"] = "Lakcím 1";
$factarray["ADR2"] = "Lakcím 2";
$factarray["ADOP"] = "Örökbefogadás";
$factarray["AFN"]  = "Ancestral File Number (AFN)";
$factarray["AGE"]  = "Életkor";
$factarray["AGNC"] = "Képviselet";
$factarray["ALIA"] = "Úgyis mint";
$factarray["ANCE"] = "Felmenők";
$factarray["ANCI"] = "Ancestors Interest";
$factarray["ANUL"] = "Házasság felbontása";
$factarray["ASSO"] = "Kapcsolódó személyek";
$factarray["AUTH"] = "Szerző";
$factarray["BAPL"] = "UNSZ-keresztség";
$factarray["BAPM"] = "Keresztelés";
$factarray["BARM"] = "Bar Mitzvah";
$factarray["BASM"] = "Bat Mitzvah";
$factarray["BIRT"] = "Született:";
$factarray["BLES"] = "Megáldás";
$factarray["BLOB"] = "Bináris adatok";
$factarray["BURI"] = "Temetés";
$factarray["CALN"] = "Gyűjtemény azonosító";
$factarray["CAST"] = "Szociális/társadalmi státusz";
$factarray["CAUS"] = "A halál oka";
$factarray["CENS"] = "Összeírás";
$factarray["CHAN"] = "Módosítás:";
$factarray["CHAR"] = "Kódkészlet";
$factarray["CHIL"] = "Gyermek";
$factarray["CHR"]  = "Keresztelés:";
$factarray["CHRA"] = "Felnőttkori keresztség";
$factarray["CITY"] = "Település";
$factarray["CONF"] = "Konfirmáció";
$factarray["CONL"] = "UNSZ-konfirmáció";
$factarray["COPR"] = "Copyright";
$factarray["CORP"] = "Vállalat/Intézmény";
$factarray["CREM"] = "Hamvasztás";
$factarray["CTRY"] = "Ország";
$factarray["DATE"] = "Dátum";
$factarray["DATA"] = "Adat";
$factarray["DEAT"] = "Elhunyt:";
$factarray["DESC"] = "Leszármazottak";
$factarray["DESI"] = "Descendents Interest";
$factarray["DEST"] = "Cél-program";
$factarray["DIV"]  = "Válás";
$factarray["DIVF"] = "Válási akta";
$factarray["DSCR"] = "Külső ismertetőjegyek";
$factarray["EDUC"] = "Végzettség";
$factarray["EMIG"] = "Kivándorlás";
$factarray["ENDL"] = "UNSZ-szertartás (Endowment)";
$factarray["ENGA"] = "Eljegyzés";
$factarray["EVEN"] = "Esemény";
$factarray["FAM"]  = "Család";
$factarray["FAMC"] = "Családtagok (gyermekként)";
$factarray["FAMF"] = "UNSZ családi akta";
$factarray["FAMS"] = "Családtagok (házastársként)";
$factarray["FCOM"] = "Elsőáldozás";
$factarray["FILE"] = "Külső adatállomány";
$factarray["FORM"] = "Formátum";
$factarray["GIVN"] = "Keresztnév";
$factarray["GRAD"] = "Felsőfokú végzettség";
$factarray["IDNO"] = "Azonosítószám";
$factarray["IMMI"] = "Bevándorlás";
$factarray["LEGA"] = "Végrendeleti örökös";
$factarray["MARB"] = "Eljegyzés kihirdetése";
$factarray["MARC"] = "Házassági szerződés";
$factarray["MARL"] = "Házassági engedély";
$factarray["MARR"] = "Házasság";
$factarray["MARS"] = "Házasság előtti szerzõdés";
$factarray["MEDI"] = "Médiatípus";
$factarray["NAME"] = "Név";
$factarray["NATI"] = "Nemzetiség";
$factarray["NATU"] = "Honosítás";
$factarray["NCHI"] = "Gyermekek száma";
$factarray["NICK"] = "Ragadványnév";
$factarray["NMR"]  = "Házasságkötések száma";
$factarray["NOTE"] = "Kiegészítő információk";
$factarray["NPFX"] = "Előtag";
$factarray["NSFX"] = "Utótag";
$factarray["OBJE"] = "Multimédia-elem";
$factarray["OCCU"] = "Foglalkozás";
$factarray["ORDI"] = "UNSZ-szertartás";
$factarray["ORDN"] = "Pappá szentelés";
$factarray["PAGE"] = "Hivatkozás";
$factarray["PEDI"] = "Felmenő rokonság";
$factarray["PLAC"] = "Helyszín";
$factarray["PHON"] = "Telefon:";
$factarray["POST"] = "Irányítószám";
$factarray["PROB"] = "Végrendelet hitelesítése";
$factarray["PROP"] = "Tulajdon";
$factarray["PUBL"] = "Publikáció";
$factarray["QUAY"] = "Adat-megbízhatóság";
$factarray["REPO"] = "Fellelhető";
$factarray["REFN"] = "Hivatkozási szám";
$factarray["RELA"] = "Rokonság";
$factarray["RELI"] = "Vallás:";
$factarray["RESI"] = "Lakhely";
$factarray["RESN"] = "Korlátozás";
$factarray["RETI"] = "Nyugdíjazás";
$factarray["RFN"]  = "Rekord állomány-azonosító";
$factarray["RIN"]  = "Rekord azonosítója";
$factarray["ROLE"] = "Szerep";
$factarray["SEX"]  = "Nem";
$factarray["SLGC"] = "LDS Child Sealing";
$factarray["SLGS"] = "LDS Spouse Sealing";
$factarray["SOUR"] = "Forrás";
$factarray["SPFX"] = "Vezetéknév előtagja";
$factarray["SSN"]  = "Társadalombiztosítási azonosító";
$factarray["STAE"] = "Állam";
$factarray["STAT"] = "Státusz";
$factarray["SUBM"] = "Adatszolgáltató";
$factarray["SUBN"] = "Beadvány";
$factarray["SURN"] = "Vezetéknév";
$factarray["TEMP"] = "Templom";
$factarray["TEXT"] = "Szöveg";
$factarray["TIME"] = "Idő";
$factarray["TITL"] = "Cím";
$factarray["TYPE"] = "Típus";
$factarray["WILL"] = "Végrendelet";
$factarray["_EMAIL"] = "Email-cím";
$factarray["EMAIL"] = "Email cím:";
$factarray["_TODO"] = "Tennivalók";
$factarray["_UID"]  = "Általános azonosító:";
$factarray["_GMU"] = "Utoljára módosította";
$factarray["_PRIM"] = "kijelölt kép";
$factarray["_THUM"] = "Használjuk ezt a képet bélyegképként?";
	 
// These facts are specific to GEDCOM exports from Family Tree Maker
$factarray["_MDCL"] = "Orvosi adatok";
$factarray["_DEG"]  = "Fokozat";
$factarray["_MILT"] = "Katonai szolgálat";
$factarray["_SEPR"] = "Különélés";
$factarray["_DETS"] = "Egyik házastárs halála";
$factarray["CITN"]  = "Állampolgárság";
$factarray["_FA1"]	= "1. esemény";
$factarray["_FA2"]	= "2. esemény";
$factarray["_FA3"]	= "3. esemény";
$factarray["_FA4"]	= "4. esemény";
$factarray["_FA5"]	= "5. esemény";
$factarray["_FA6"]	= "6. esemény";
$factarray["_FA7"]	= "7. esemény";
$factarray["_FA8"]	= "8. esemény";
$factarray["_FA9"]	= "9. esemény";
$factarray["_FA10"]	= "10. esemény";
$factarray["_FA11"]	= "11. esemény";
$factarray["_FA12"]	= "12. esemény";
$factarray["_FA13"]	= "13. esemény";
$factarray["_MREL"]	= "Relationship to Mother";
$factarray["_FREL"]	= "Relationship to Father";
$factarray["_MSTAT"]	= "Marriage Beginning Status";
$factarray["_MEND"]	= "Marriage Ending Status";

// GEDCOM 5.5.1 related facts
$factarray["FAX"] 	= "Fax";
$factarray["FACT"] 	= "Esemény";
$factarray["WWW"] 	= "Honlap";
$factarray["MAP"] 	= "Térkép";
$factarray["LATI"] 	= "Szélességi fok";
$factarray["LONG"] 	= "Hosszúsági fok";
$factarray["FONE"] 	= "Fonetikus";
$factarray["ROMN"] 	= "Romanized";
$factarray["_HEB"] 	= "Héber";

// Rootsmagic
$factarray["_SUBQ"]	= "Rövid változat";
$factarray["_BIBL"] 	= "Irodalomjegyzék";

// PAF related facts
$factarray["_NAME"] 	= "Levelezési név";
$factarray["URL"] 	= "Webcím";

// Other common customized facts
$factarray["_ADPF"] 	= "Az apa örökbefogadta";
$factarray["_ADPM"] 	= "Az anya örökbefogadta";
$factarray["_AKA"] 	= "Úgyis mint";
$factarray["_AKAN"]	= "Úgyis mint";
$factarray["_BRTM"]	= "Körülmetélés";
$factarray["_COML"]	= "Polgári házasság";
$factarray["_EYEC"] 	= "Szemszín";
$factarray["_FNRL"]	= "Temetés";
$factarray["_HAIR"]	= "Hajszín";
$factarray["_HEIG"] 	= "Magasság";
$factarray["_INTE"]	= "Interred";
$factarray["_MARI"]	= "Házassági szándék";
$factarray["_MBON"]	= "Marriage bond";
$factarray["_MEDC"] 	= "Egészségi állapot";
$factarray["_MILI"] 	= "Katonai szolgálat";
$factarray["_NMR "]	= "Nem házasodott meg";
$factarray["_NLIV"]	= "Nincs életben";
$factarray["_NMAR"] 	= "Nem házasodott meg";
$factarray["_PRMN"]	= "Permanent Number";
$factarray["_WEIG"] 	= "Testsúly";
$factarray["_YART"]	= "Jarzeit";
$factarray["_MARNM"]	= "Házasult név";
$factarray["_STAT"]	= "Marriage status";
$factarray["COMM"]	= "Megjegyzés";

if (file_exists($GM_BASE_DIRECTORY . "languages/facts.hu.extra.php")) require $GM_BASE_DIRECTORY . "languages/facts.hu.extra.php";
?>