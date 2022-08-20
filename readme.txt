=======================================================
    Genmod

    Version 1.0
    Copyright 2005 - 2007 Genmod Development team

    # $Id: readme.txt 29 2022-07-17 13:18:20Z Boudewijn $
=======================================================

CONTENTS
     1.  LICENSE
     2.  INTRODUCTION
     3.  SYSTEM REQUIREMENTS
     4.  PHP SETTINGS
     5.  QUICK INSTALL
     6.  INSTALLATION
     7.  UPDATING GEDCOMS
     8.  THEMES
     9.  MULTIMEDIA OBJECTS
    10.  RSS FEED
    11.  SECURITY
    12.  LANGUAGES
    13.  NON-STANDARD GEDCOM CODES
    14.  LANGUAGE EXTENSION FILES
    15.  BACKUP
    16.  RESTORE
    17.  ENTERPRISE VERSION

-------------------------------------------------------
LICENSE

Genmod: Genealogy Editor and Viewer
Copyright (C) 2005 - 2007  Genmod Development team

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

See the file GPL.txt included with this software for more
detailed licensing information.

-------------------------------------------------------
INTRODUCTION

Genmod is a genealogy program which allows you to view 
and edit your genealogy on your website.  Genmod has full editing 
capabilities, full privacy functions, and supports multimedia like photos 
and document images.  Genmod also simplifies the process of 
collaborating with others working on your family lines.  Your latest 
information is always on your web site and available for others to see.  
For more information visit http://genmod.sourceforge.net/.

Genmod is taking after the works by John Finaly on the PhpGedView project.

Genmod is Open Source software that has been produced by people from 
many countries freely donating their time and talents to the project.  All 
service, support, and future development is dependent on the time 
developers are willing to donate to the project, often at the expense of 
work, recreation, and family.  Beyond the few donations received from 
users, developers receive no compensation for the time they spend working 
on the project.  There is also no outside source of revenue to support the 
project.

-------------------------------------------------------
SYSTEM REQUIREMENTS

Genmod requires the following:
* A web server (Apache, IIS)
* PHP 4.3.0 or higher
* MySQL 4.1 or higher
* Enough diskspace (around 20MB)

The default installations of PHP on most servers should provide
you with all of the PHP functionality you should need.

To use the reporting engine, PHP needs to be compiled with XML support.  
This is compiled into PHP by default unless it is specifically disabled.  
See http://us3.php.net/manual/en/ref.xml.php

Some features of Genmod require the GD library and that PHP be compiled
with GD support.  Most precompiled versions of PHP include GD support.  If 
you are compiling PHP yourself you will need to configure it with the 
    --with-gd 
option.  See http://us3.php.net/manual/en/ref.image.php

The advanced calendar features of Genmod for converting Gregorian dates 
to the Hebrew or Jewish calendars require that PHP be compiled with the
    --enable-calendar 
configuration option.  See http://us2.php.net/manual/en/ref.calendar.php 
for more information.

For GEDCOMs larger than 2MB, you will likely need to research different
hosting options and find one that will accept a request to modify the
default memory and time limits built into PHP.  See the FAQ at
http://www.sourceforge.net/projects/genmod for more information about large GEDCOMs.

-------------------------------------------------------
PHP SETTINGS

When you experience trouble with logging into the program or nothing is being
displayed take a look at your PHP settings. Here are some important settings
with their recommendend values.

* output_buffering = 4096
* allow_call_time_pass_reference = Off
* register_globals = Off
* register_argc_argv = Off
* magic_quotes_gpc = Off
* session.auto_start = 0
-------------------------------------------------------
QUICK INSTALLATION

Follow the instructions in this section to install Genmod if you are
already familiar with the program or are familiar with installing other PHP 
web applications.

 1.  Upload the files to your web server.
 2.  Set Write permissions on config.php and the "index" directory.  For 
     optimal security, you should move the "index" directory to a location 
     where it is not accessible from the Internet.
 3.  Point your browser to the directory where you uploaded your Genmod 
     files (for example, http://www.yourserver.com/Genmod/).
 4.  Enter your configuration settings.  If you moved the index directory, 
     be sure to specify the correct location to it on this page.  Save the 
     configuration parameters.
 5.  You are taken through the installation process.
 6.  Login as this user and upload your GEDCOM file.
 7.  Save the GEDCOM configuration settings.
 8.  Import the GEDCOM.

Optional Steps
 9.  If you want to use the language editing features you will need to set 
     Write permissions for all of the files in the ./languages folder.
10.  If you want to upload media files using the Upload Media section of 
     the Admin menu then you need to set Write permissions for your ./media 
     and ./media/thumbs directories.
11.  If you want to edit your GEDCOM file online, the GEDCOM file must have
     Write permissions set for the PHP user.
12.  If you want to use the Backup feature of the Upgrade utility in 
     Genmod you will need to either set Write permission on the 
     Genmod folder itself or create a folder named "backup" with Write 
     permissions.  Write permissions for the Genmod folder can be 
     removed as soon as the backup folder is in place and has the 
     appropriate permissions.
13.  For security you should set the permissions back to Read-only when you 
     are done editing or uploading files.

-------------------------------------------------------
INSTALLATION

Follow these instructions if you are not familiar with Genmod or 
installing PHP applications.

*A.  Upload Program Files:
To install Genmod, unzip the compressed package and upload the files to 
a directory on your web server.  If you have limited space on your server, 
you can save space in the following ways:
1.  Delete the themes from the themes folder that you do not plan to use.
2.  Delete some or all of the language files that you do not want. English 
    files are named configure_help.en.txt, countries.en.txt, facts.en.txt, 
    help_text.en.txt and lang.en.txt.  French files, for example, are named 
    with ".fr." in place of ".en.".  Hebrew files use ".he." in place of 
    ".en.", and so on. All active languages are loaded into the database,
    therefore they can be removed. If you need to restore any of the languages
    make sure the files are back in the languages folder.
    The English language files should not be deleted.  They are always loaded 
    before the files for the selected language are loaded.  This ensures 
    that all language variables are defined, and that the English version 
    will be used when a given variable is missing in the new language.
3.  Do not upload the "places" folder.  This folder contains maps for some
    countries.  It also contains text files containing state, county, and 
    place names.  Its purpose is to allow you to enter place names by 
    picking them from lists.    

For optimal security, you may want to move the "index" directory to a 
different location outside of your Internet accessible space.  You will 
specify the location of this directory during the online configuration.  
See the SECURITY section for more information.

*B.  Required File Permissions:
Genmod requires that Read permissions be set for all files in the
Genmod directory tree.  Some hosts also require Execute permissions
(chmod 755).  Genmod requires full Write permissions on the index 
directory (chmod 777 under most hosting configurations).  Genmod also 
requires that Write permissions (chmod 777) be set temporarily for the 
config.php file.

To help with the setting of permissions a file called setpermissions.php 
has been included with the project.  This file will attempt to set 777 
permissions to the config.php, ./index, and all of the files inside the 
./index directory.  Because host settings vary on the ability of PHP 
programs to set file permissions, you have to run this file manually.

If at any time you have trouble during configuration, check your 
permissions again.

There are some advanced features that require more Write permissions to be 
set.  If you want to use the language editing features you will need to set 
Write permissions for all of the files in the ./languages folder (chmod 
777).  If you want to upload media files using the Upload Media section of 
the Admin menu then you need to set Write permissions (chmod 777) for your 
./media and ./media/thumbs directories.  If you want to edit your GEDCOM 
file online, the GEDCOM file must have Write permissions set to the PHP 
user (chmod 777).

*C.  Configuration:
Next point your web browser to the Genmod folder
(for example, http://www.yourserver.com/Genmod/) to automatically 
begin the online configuration process.  Information about each of the 
configuration options can be found online by clicking on the question mark 
(?) near each label.

You may reconfigure Genmod at any time by taken the following steps:
1. Log in as an administrator
2. Go to the admin page
3. Click on "maintenance"
4. Click on "Restart setup"

*D.  Add GEDCOM file
To add GEDCOM files to the system, you can upload your GEDCOM file using 
the "Upload GEDCOM" option from the Admin menu.  All files uploaded using 
the "Upload GEDCOM" page are saved in your index directory.  You can also 
upload your GEDCOM manually using FTP or any other file upload method.  
Most hosts limit the size of files that can be uploaded from a web form for 
security reasons, so you may be forced to use a manual method.  You may 
also upload your GEDCOM in ZIP format, either manually or using the 
"Upload GEDCOM" option.  Make sure to enter the filename of the ZIP file.  
Genmod will automatically unpack the ZIP file and use the GEDCOM file 
inside it.  Be sure to create the ZIP file to contain only one GEDCOM file.

*E.  Set GEDCOM Configuration Settings
After uploading your GEDCOM, you will be asked to set the configuration
parameters for it.  There are too many parameters to list all of their 
options in this document.  Please use the online Help documentation to 
guide you through the configuration process.

*F.  Validate GEDCOM
After you save the GEDCOM configuration Genmod will validate your 
GEDCOM and automatically fix any errors that it can.  If any errors found 
in the GEDCOM require user input, you will be prompted to choose how to 
proceed.  Again use the online Help ? for more information.

*G.  Import GEDCOM
You are almost done.  This is the final step before you can begin viewing 
your data.  After validating the GEDCOM and fixing any errors, you will 
need to import the GEDCOM into the data store.  During the Import you will 
see a lot of processing statistics printed on the screen.  If the Import 
completed successfully you will see a blue "Import Complete" message.  
Everything is now set up and you can begin using Genmod with your 
GEDCOM.

*H.  Deleting GEDCOMs
You may delete GEDCOMs from your site from the "Manage GEDCOMs" area.
Deleting a GEDCOM from the site will remove it from the database but will 
not delete the original GEDCOM file that you imported.  It will also not
delete any of the cache or privacy files related to this GEDCOM.  These 
retained files, which are no longer required by Genmod, are all located 
in the "index" directory.

*I.  Reset config.php Permissions
For security you should set the permissions of config.php back to Read-only
(chmod 755) when you have finished configuring for the first time.  Write
permissions will only need to be set for config.php when you use the
Admin->Configuration link.  Everything else will be stored in the index
directory.

*J.  Custom Themes
You can customize the look and feel of Genmod by modifying one of the
provided themes.  See the THEMES section of this readme file for more
information.

*K.  HTTP Compression
Pages generated by Genmod can be large and use up a lot of bandwidth.
Compression of the data between the server and browser using GZip 
compression can compress the bandwidth by up to 90% (usually 80% - 90% for 
Genmod that were tested) If your web server is Apache, configuring this 
is very easy.  Add the following 2 lines to your php.ini file:
    php_flag zlib.output_compression On
    php_value zlib.output_compression_level 5

If you have no access to the php.ini file, create a blank file named 
.htaccess (including the dot) and add the lines to that file (or add them 
to an existing .htaccess file and upload the file to your Genmod 
directory.

Note: If your host is using mod_gzip or an other compression method, using 
this technique can cause problems.  Compression will have no effect on 
browsers that do not support it.  You can test the compression at
http://leknor.com/code/gziped.php

If you need help or support visit  http://www.genmod.net/

-------------------------------------------------------
UPDATING GEDCOMS

When you change your genealogy data outside of Genmod, it is not 
necessary to delete your GEDCOMs from Genmod and start over.  Follow 
these steps to update a GEDCOM that has already been imported:

1.  The first step is to replace your old GEDCOM on the site with your new
    GEDCOM.  You can do this using FTP, or by going to the "Upload GEDCOM" 
    page and uploading a new GEDCOM with the same filename as the old one.  
    Please remember that file names are case sensitive.
2.  Re-import the GEDCOM file by going to 
    Admin->Manage GEDCOMs->Import GEDCOM.  The GEDCOM will be validated 
    again before importing.
3.  The Import script will detect that the GEDCOM has already been imported 
    and will ask if you want to replace the old data.  Click the "Yes" 
    button.
4.  You will again see the Import statistics and the Import Complete 
    message at the bottom of the page when the Import is complete.

If you use a ZIP file to upload the GEDCOM, the only way to do it is by 
either using the "Upload GEDCOM" option, or the "Add GEDCOM" option.  The 
GEDCOM file in the zipped file has to have exactly the same name as the 
already existing GEDCOM.  This way existing GEDCOM settings will be 
preserved.

-------------------------------------------------------
THEMES

Genmod uses a theme based architecture allowing you to have greater
flexibility over the appearance of the site.  The "themes" directory 
contains the standard themes that come packaged with Genmod.  You may 
customize any of these themes to your liking or create your own theme by 
copying any of the standard themes to a new folder and modifying it.  When 
you configure Genmod, you should tell it to look in your new theme 
directory.

A theme directory must contain at least the following 6 files:
  footer.html        # PHP/HTML for the bottom of every page
  header.html        # PHP/HTML for the top of every page
  print_footer.html  # PHP/HTML for the bottom of every print preview page
  print_header.html  # PHP/HTML for the top of every print preview page
  style.css          # A CSS stylesheet containing all styles
  sublinks.html      # PHP/HTML to print the links to other places
  theme.php          # The PHP design variables that you may customize
  toplinks.html      # PHP/HTML that appears just below the header.html

-------------------------------------------------------
MULTIMEDIA OBJECTS

The GEDCOM 5.5 standard supports multimedia files of all types.  Currently
Genmod supports multimedia objects only as external files.  Multimedia
embedded in the GEDCOM file itself will be ignored.  To use the multimedia
support in Genmod you must copy the multimedia files external to your
GEDCOM to the "media" directory in the folder where you installed 
Genmod.

In choosing which picture to show on charts, Genmod will choose the 
first one with the _PRIM Y marker.  If there are no _PRIM tags in your 
media object records then the first object found will be used.  You can 
disable all photos on charts for a particular person by setting _PRIM N on 
all media objects.  Most genealogy programs will do this for you 
automatically.

You can find all of the images referenced in your file by opening your 
GEDCOM in a text editor and looking for the OBJE or FILE tags.

Genmod includes a "media/thumbs" directory where you can place 
thumbnails of your media files for display in lists and on other pages.  
Genmod allows you to create your own thumbnails so that you can 
maintain artistic control over your media and to avoid the installation of 
other server side software.  Make a copy your images and reduce them to an 
appropriate thumbnail size somewhere around 100px width and upload them to 
the "media/thumbs" directory.  Keep the filename the same as the original.  
Thumbnails can be created for non-image media files as well.  To do this 
create a thumbnail image in either gif, jpeg, png or bmp formats and name 
them the same name as the media file including the file extension (even if 
the media is a non image such as a PDF or an AVI file, name the thumbnail 
IMAGE with the PDF or AVI file extension).

There is an Image module that fully integrates with GM and that will
automatically create thumbnails for you if you use it to upload your files.  
It is not included with the main Genmod files because it requires 
external libraries that not all hosts will have installed.  You should be 
able to get it to work by following the instructions included with it.  
You can download the ImageModule from:
http://sourceforge.net/project/showfiles.php?group_id=55456&package_id=88140

You can configure Genmod to recognize subdirectories in your media 
folder.  The subdirectories must be the same names as the subdirectories in 
your media file paths pointed to in your GEDCOM file.  For example, if you 
have the following media references in your GEDCOM file:
    C:\Pictures\Genealogy\photo.jpg
    C:\Pictures\Scans\scan1.jpg
    scan2.jpg

With the media depth set to 1 you need to set up your directory structure 
like this:
    media/Genealogy/photo.jpg
    media/Scans/scan1.jpg
    media/scan2.jpg
    media/Genealogy/thumbs/photo.jpg
    media/Scans/thumbs/scan1.jpg
    media/thumbs/scan2.jpg

With the media depth set to 2 you need to set up your directory structure 
like this:
    media/Pictures/Genealogy/photo.jpg
    media/Pictures/Scans/scan1.jpg
    media/scan2.jpg
    media/Pictures/Genealogy/thumbs/photo.jpg
    media/Pictures/Scans/thumbs/scan1.jpg
    media/thumbs/scan2.jpg

-------------------------------------------------------
RSS FEED

GM now includes an RSS feed.  RSS is an XML format that allows other sites
to get news and other data from your site.  The language used is the 
default language of the site.  The language of the feed can be set to any 
language supported by GM by changing the URL that your RSS aggregator uses 
from the default /Genmod/rss.php to /Genmod/rss.php?lang=english 
(or any language supported by GM such as rss.php?lang=french).  

Currently only the default site GEDCOM is supported for the feed info.  
Other options available in the RSS feed are the ability to specify the feed 
type via the rssStyle parameter.  The GM default is "RSS1.0".  Passing any 
supported type including "PIE0.1", "mbox","RSS0.91", "RSS1.0", "RSS2.0", 
"OPML", "ATOM0.3", "HTML", "JS" will change the feed type.  

For example, calling  /Genmod/rss.php?rssStyle=HTML will create HTML 
output suitable for inclusion in an other page via an iFrame.  The JS 
option will output JavaScript that can be included in an other page without 
using an iFrame.  

You can also specify a module that you want to output (only 1) so that only 
that module will be output.  This is done via the module parameter.  For 
example, /Genmod/rss.php?module=GEDCOMStats will only output the GEDCOM 
Stats block.  

These parameters can be chained so that
/Genmod/rss.php?lang=hebrew&module=GEDCOMStats&rssStyle=HTML 
will output the GEDCOM Stats module in Hebrew in HTML.

-------------------------------------------------------
SECURITY

Even though Genmod gives you the ability to hide the details of living
individuals, whenever you post the personal details of living individuals 
on the Internet, you should first obtain the permission of EACH living 
person you plan to include.  There are many people who would not even want 
their name linked with their family history made public on the Internet and 
their wishes should be respected and honored.  Most family history programs 
allow you to choose the people who are exported when you create your GEDCOM 
file.  The most secure option is to deselect all living people in your 
genealogy program when you export your genealogical data to a GEDCOM file.

If you wish to protect your GEDCOM file itself from being downloaded over 
the Internet you should place it outside the root directory of your web 
server or virtual host and set the value of the $GEDCOM variable to point 
to that location.  For example, if your home directory is something like
"/home/username" and if the root directory for your web site is
"/home/username/public_html" and you have installed Genmod in the
"public_html/Genmod" directory then you would place your GEDCOM file in
your home directory at the same level as your "public_html" directory.  You
would then set the file path to "/home/username/GEDCOM.ged" by editing the
GEDCOM configuration.

You can also manually set the location by changing the "path" line in
index/GEDCOMs.php:
    $gedarray["path"] = "../../GEDCOM.ged";
or
    $gedarray["path"] = "/home/username/GEDCOM.ged";

Since your GEDCOM file resides in a directory outside of your web server's 
root directory, your web server will not be able to fullfill requests to 
download it.  However, Genmod will still be able to read and display 
its contents.

In the end it is YOUR responsibility to guarantee that there has been no
violation of an individual's privacy and YOU could be held liable should
private information be made public on the web sites that you administer.

N.B. Another option is to remove the imported GEDCOM file from the directory
where you placed it. Genmod doesn't need the GEDCOM file for normal operation.


-------------------------------------------------------
LANGUAGES

Genmod has built-in support for multiple languages.  PHP does not 
support unicode (UTF-16).  It does support UTF-8 and that is the 
recommended character encoding for GEDCOMs with Genmod.  If you have 
characters in your GEDCOM outside the standard ASCII alphabet, you should 
probably use the UTF-8 encoding.  There are many differences between UTF-8 
and UTF-16, but anything that you can encode in UTF-16 you should be able 
to encode in UTF-8.  It is also quite easy to convert from Unicode to 
UTF-8.  Simply open your Unicode GEDCOM file in Windows Notepad and select 
"File->Save As.." from the menu and choose UTF-8 as the encoding option.  
You shouldn't lose any of the characters in the translation.

You should check the Patches section of
http://sourceforge.net/projects/genmod to get the latest language 
files.

Discussion and questions about the multiple language features of Genmod
including translations, should be posted in the Translations forum 
available from the Genmod project page here:
http://www.sourceforge.net/projects/genmod

To translate Genmod into another language that is not currently 
supported you must first login to Genmod as an administrator and go to 
the Language Edit Utility by clicking on "Admin -> Translator Tools". Click on
"Add files and settings for a new language".  Choose your language from the 
dropdown list and click on the "Add new Language" button. A popup window will 
appear that allows you to edit the default settings for your language.  Each 
of the settings has online help available by clicking on the "?".  You might 
want to look at the settings for some of the other languages on the edit 
language page to see how they are set up.  When you have finished editing the 
settings, click the Save button.  The new language will be stored in the
database. Use the Lanugauge File Export Utility to save your language in a file.
You will notice that your language now appears in the supported languages list.

You can translate these files using the online Language File Edit utility.
Just select your language from the drop-down list and then select the file 
you want to edit and click the Edit button.  Your file will be compared to 
the English language file so that you can easily translate the files 
online.

You can also translate these files manually by opening them in any text 
editor.  If you manually edit the files, you must be sure to save them in 
the UTF-8 character set.  Some text editors like Windows Notepad add a 
3-byte Byte-Order-Mark (BOM) to files they save in UTF-8.  PHP does not 
like the BOM and it should be removed before testing the files in 
Genmod.  Genmod's Translator Tools section has a utility program
for removing these BOMs.

You should obtain a flag file from http://w3f.com/gifs/index.html and size 
it to match the other flags in the images/flags directory.

To help maintain languages, a language change log is provided in the 
languages directory.  This change log is named LANG_CHANGELOG.txt.  All 
changes to the English language files are recorded here.

If you make a new translation of Genmod or update another translation, 
and would like to contribute it to the community please post your language 
files and your index/lang_settings.php file to the Patches section of the 
SourceForge project site at http://www.sourceforge.net/projects/Genmod

-------------------------------------------------------
NON-STANDARD GEDCOM CODES

The GEDCOM 5.5 standard has a defined set of codes.  You can read the
specification online at http://www.sourceforge.net/projects/genmod  Part of the
standard allows for genealogy software to define their own codes, and 
requests that they begin with an "_" underscore.  

When Genmod comes across a tag that is not defined it will display an 
error message.  You can disable these error messages by setting 
$HIDE_GEDCOM_ERRORS=true; in the config.php file.  Genmod can also be 
customized to work with these codes by adding them to the facts array in a 
new language file named facts.en.extra.php.  If you add it to the English 
facts file you should also add it to the other facts language files you are 
using on your site if you want other languages to translate the tag 
correctly.

The format of the facts file is a PHP associative array.  Each tag requires 
one line in the array.  The following line defines the label "Abbreviation" 
for the ABBR GEDCOM tag.
    $factarray["ABBR"] = "Abbreviation";

As an example, if you use a genealogy program that generates the tag 
"_ZZZZ" you can customize Genmod to accept this code by adding the 
following lines to the facts.en.extra.php file:
    <?php
    $factarray["_ZZZZ"] = "Tag Label goes here";
    ?>

-------------------------------------------------------
LANGUAGE EXTENSION FILES

Language extension files are custom PHP files that you can use to make your 
own language specific extensions to Genmod.  To add a language file 
extension, create a new PHP file called lang.xx.extra.php replacing the 
"xx" with the code for the language you want to extend.  These files are 
not automatically included with the package so that when you upgrade, your 
extensions are not overwritten.

If this file exists for the language that is chosen, it is the very last 
thing that is loaded before the display starts.  These files were designed 
to be language file extensions, but you could easily use them to make 
settings changes based on the chosen language.

What sort of things can you do with language extensions?
 - Customize any of the text that appears on the site,
 - Change configuration options based on language,
 - Change to a different GEDCOM when someone views your site in a different
   language.

The only settings that you should not override in this file are the Privacy
settings.

If, for example, you wanted to change the GEDCOM title when you changed the
language, you could change the title for each language by adding the 
following line to your lang.xx.extra.php:
    $GEDCOMS["surname.ged"]["title"] = "Title in Chinese";

In this file you could also change the text on the buttons:
    $gm_lang["view"]="Show";

With this file you could also change the GEDCOM that is displayed when the 
language is selected.  Suppose you had a GEDCOM that was in German and one 
that was in English.  In the lang.de.extra.php file you could add the 
following lines:
    if ($GEDCOM=="english.ged") {
      header("Location: $SCRIPT_NAME?$QUERY_STRING&ged=german.ged");
      exit;
    }

These lines say that if we are using the German language files, but are 
using the English GEDCOM, then we need to reload the page with the German 
GEDCOM.  You need to reload the page so that the configuration settings for 
the GEDCOM get loaded.  This assumes that you have both "english.ged" and 
"german.ged" imported into the database and that the english.ged and the 
german.ged have the same people in them, just in a different language.  
Thus I0001 in english.ged should refer to the same I0001 in german.ged.

===========================================================
BACKUP

With the Backup function in the administration menu, you can make a simple 
backup of all important Genmod data.  With this backup, it's possible 
to rebuild your Genmod site to the situation at backup time.

The backup can contain the following files, as selected on the Backup page:
  - config.php with all configuration settings of your installation
  - include_top.php and include_bottom.php with admin customised code
  - language settings and language extension files
  - all genealogical data, exported to a GEDCOM file
  - all GEDCOM options and privacy settings for the above files
  - counters, Genmod- and search-log data
  - user definitions and options (block definitions, favorites, messages 
    and news)

The files will be gathered into a ZIP file, which can be downloaded by 
clicking the link on the page.

Note: The database itself will not be included in the backup, since it can 
be rebuilt using the files in the backup.

Note: Multimedia files can be included in the backup, but preferably they should be
copied to a local computer manually, on a regular basis.

Note: The SQL queries that are created by the Backup cannot be used to
restore the database with tools like PhpMyAdmin. The data may contain 
linebreaks, causing the restore to fail. The restore function of Genmod takes
care of these linebreaks properly and should be used to perform the restore.

Note: The Backup script may run a long time on very large GEDCOM files. The script 
automatically sets the maximum execution time to the maximum value, found in any
of the GEDCOM settings. If the script execution time nevertheless exceeds the maximum,
try running it again after raising the value in the settings of one of the GEDCOM
files. This also applies to the Restore.

The Backup function uses the PclZip library, which is written by Vincent 
Blavet and can be found at http://www.phpconcept.net/pclzip.

============================================================
RESTORE

The files, created by the Genmod Backup, are sufficient to rebuild you site
from scratch and restore all data, except Multimedia files.

There are a few rules though that apply:
- The backup files cannot be used across versions. Possible difference in 
database table layout prevent this.
- Site URL, database name, database user and password and table prefix should
be the same. If you forgot them, look in the config.php file. If they differ from
the origination installation, don't restore the config.php file.
- Table prefixes MUST be the same, they are included in the restore queries.
- If you are running Enterprise Version of Genmod, you need to perform a restore
on every site. However, the steps 1. and 2. should only be done once.
- It is possible to do a partial restore of the data. However, keep in mind that
all data is related and therefore a partial restore can cause loss of database
integrity. As a compromise all data that MUST be kept together, is put together in
one file.

To rebuild your site(s) from scratch:
 1. Install Genmod on your server, following the instructions in chapter 
"6. Installation", parts A and B. Make sure all the necessary Index folders
are created.
 2. From the backup ZIP file, restore the config.php file. Also restore the files 
include_top.php and include_bottom.php to the includes folder. If present in the ZIP
file, also restore the lang_settings.php file to the Index directory, and the 
language extension files (xx.extra.php) to the Languages directory. 
 3. Also upload the other files (.sql and .ged) to the apporpriate index directory.
 4. Adjust the filenames of the Gedccom files by removing the "export_" prefix.
 5. Startup Genmod, this should bring you to the page for creating an admin user.
 6. Create an admin user. This user will be temporary as it will be overwritten 
by the restore later.
 7. Go to the admin menu and choose Restore
 8. You will be presented with a list of possible data to restore. Be sure all is 
checked and start the restore. When finished, Genmod will tell you the restore is
performed succesfully.
 9. Leaving the restore, you will be asked to login again. At this point the old data
is available again, you can login with your original admin user.
10. Go to the admin menu, Manage GEDCOMs and edit Privacy. Import all Gedcom's
mentioned there.
11. Ready!

======================================================================
ENTERPRISE VERSION

The Enterprise Version of Genmod enables you to run multiple completely separated
Genmod Websites with just one installation of the software and just one MySQL database.
Actually there is no distinct Enterprise Version, the software is suitable to run
a single website as well as multiple websites.

There are a few pre-conditions to run multiple websites:
- Each website URL (and, if applicable, the login URL) must be unique, as Genmod 
recognizes from the URL which website must be run. Different URL's are
http://www.mygenmod.net and http://www.famtree.org, but also 
http://www.mydomain.org/genmod1 and http://www.mydomain.org/genmod2.
- Each website must have it's own Index directory. It can have any name, but must 
correspond with the name entered in the configuration.
- Database name OR table prefix must be unique, to ensure all site data is stored
separately from data of other sites. Database user and password don't have to be
unique, if the same user is used, be sure the user has been given rights on any
applicable database.

Installation of any next website is pretty much the same as installation of the first
one. Just follow the installation instructions as mentioned in chapter "6. Installation",
starting from step B. Be sure to make the index directory in advance.
Once you pointed the webserver, with a new unique URL, to the Genmod directory, Genmod
will perform the normal steps to configure the website. Existing websites will be preserved.

As all websites have access to the Multimedia files, they CAN be shared between websites.
However, you CAN choose to have a different media directory for each website.

Basically there is no way that one website influences another, with one exception:
All configuration data for the websites is stored in one file: config.php. If this file
is restored from a backup, the configuration data for all websites will be restored, and new 
websites configured since then will not be included. So make sure to restore the config.php 
file from the latest backup only!