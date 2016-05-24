===================================================================================
= Woodwing MakeDays 2014: WormGraphviz, an object model viewer for ContentStation =
===================================================================================

Introduction:
=============

As part of the two day WoodWing MakeDays event, a team of developers came up with the
idea to create the WoodWing Object Relation Modeler (W.O.R.M.) for short. The idea was
to use model drawings in ContentStation to provide additional (and hopefully insightful)
views of the objects and their progress.

The WormGraphViz plugin gives ContentStation users a new view on their work. Users can
see the overall progress of a dossier by means of a wire model, and are given the option
to zoom in on certain areas of the workflow. Users can zoom in on Layouts and
PublishForms to see placed articles in debt, and in the case of Layouts to show page
previews, all by clicking through the model.


Installation:
=============

To properly use the WormGraphviz plugin the following requirements must be met.

1) Download and install Graphviz as described here: http://www.graphviz.org/Download..php

2) Place the WormGraphviz plugin in your /config/plugins folder.

3) Adjust config.php by providing the path to the dot executable.

4) Enable the plugin in the admin pages.

5) Run the health test page.


Clickable links in PDF reports:
===============================

If you want to make the hyperlinks in the Graphviz reports clickable, please do the following:

1) Download and install Ghostscript from: http://pages.uoregon.edu/koch/

2) Configure the installed ps2pdf executable in the GRAPHVIZ_PS2PDF_APPLICATION_PATH option (see config.php file).

3) Run the health test page.


Reports in context menu:
========================

To access the reports through the context menu in Content Station, these two links should be placed
in the WWSettings.xml file between the <ObjectContextMenuActions> and </ObjectContextMenuActions> markers:

	<ObjectContextMenuAction label="Progress" url="{SERVER_URL}/config/plugins/WormGraphviz/index.php?command=objectprogressreport&ticket={SESSION_ID}&id={OBJECT_IDS}&format=pdf" external="true" objtypes="Dossier"/>
	<ObjectContextMenuAction label="Placements" url="{SERVER_URL}/config/plugins/WormGraphviz/index.php?command=placementsreport&ticket={SESSION_ID}&id={OBJECT_IDS}&format=pdf" external="true" objtypes="PublishForm,Layout,LayoutModule"/>


Reports in menu of dossier tab:
===============================

To access the reports through the menu in the dossier tab, add the following fragment
between the <SCEnt:ContentStation> and </SCEnt:ContentStation> markers:

	<Reports>
		<Report name="Progress" value="true" extension="svg"/>
	</Reports>		

This feature requires Content Station 9.5.0 build 240 or later.
For the extension attribute, you can fill in "svg" or "pdf".


Usage:
======

There are two types of context menu's available in ContentStation

- When right-mouse-button-clicking on a Dossier the 'Progress' item is exposed: this will
  show the progress overview for the Dossier.

- When right-mouse-button-clicking a Layout or PublishForm the 'Placements' item is exposed.
  clicking this will show a placements model for the selected object.


Credits:
========

Content Station Development: Edwin van der Ven
Enterprise Server Development: Edwin van der Klaauw, MengLu Au Yong, Florian van der Velde