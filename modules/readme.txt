=======================================================
    Genmod

    Version 3.4
    Copyright 2005 John Finlay and others

    This and other information can be found online at
    http://www.Genmod.net

    # $Id: readme.txt 29 2022-07-17 13:18:20Z Boudewijn $
=======================================================

CONTENTS
     1.  LICENSE
     2.  INTRODUCTION
     3.  LIST OF MODULES

-------------------------------------------------------
LICENSE

Genmod: Genealogy Viewer
Copyright (C) 2002 to 2005  John Finlay and Others

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

Modules are additional software that can be added to the core Genmod.
They provide additional functionality not part of the core Genmod 
distribution. Some modules integrate very tightly with the Genmod
software while others are separate software that loosely links to Genmod,
such as the PunBB bulletin board system

Like Genmod, modules available for download are Open Source software that
has been produced by people from many countries freely donating their time
and talents to the project, though there is nothing to prevent someone from
developing a source module that is not open source. 


-------------------------------------------------------
LIST OF MODULES

The following modules are currently available.
FCKeditor, gallery2,punbb.

FCKeditor
---------

This module allows online editing of news items and contents of the HTML block
without any knowledge of HTML.  
  
To install this module, download FCKeditor from the official
website (see below) and upload it to the modules/FCKeditor
directory. make sure that the directory structure is:
Genmod directory\
----modules\
--------FCKeditor\
------------editor\....
------------fckeditor.js
------------......

As long as the FCKeditor is present in the above location, GM will use this
as the default editor as opposed to a plain textarea.

This module has been tested with the FCKeditor 2.0 on August 7th, 2005.
The code in GM required to use this is only in GM 3.4 and greater.

The FCKeditor homepage is at:
http://www.fckeditor.net/default.html
Sourceforge Project page is at:
http://sourceforge.net/projects/fckeditor/

-------------------------------------------------------

gallery2
--------




-------------------------------------------------------

punbb
-----




-------------------------------------------------------
