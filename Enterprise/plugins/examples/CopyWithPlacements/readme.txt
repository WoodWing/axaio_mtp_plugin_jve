- CopyWithPlacements v9.0 -

(Formerly known as CopyWithChildren)


Once installed, this plug-in will copy a Layout including all placed
Articles. The articles will end up in the same target as the layout.
Optionally the recursive copy can be controlled with a custom checkbox
in the Layout Copy dialog.

Placed images and layout modules will not be copied - they just get an
additional placement on the new layout. The plug-in does no dossier
management.

This functionality can be used to use complete layout documents
including (dummy) articles as a template for a complete layout.


Installation and configuration

1. Install and activate the plug-in. A custom meta data field (name: CWP_DEEPCOPY)
is automatically created by the plug-in. 

2. Look up the custom meta data field: CWP_DEEPCOPY and change the default value (0|1).

3. Add this CWP_DEEPCOPY custom meta data field to the CopyTo dialog *for Layout
objects only*

Now a checkbox will appear in the Layout copy dialog. The checkbox
indicates whether the articles which are placed on the source layout
will be copied too.

If you do not define the custom meta data field CWP_DEEPCOPY, the plug-in
will not make a new copy of the articles.


Known problems

- If one of the articles which is copied already exists in the target
issue/category, the copy process terminates with an error.
- If the layout is copied into a dossier, the dossier is not applied to the copied articles.

For questions and support: support@woodwing.com





