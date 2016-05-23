COPY WITH CHILDREN

-what it does-

Once installed, Layouts will be copied recursively - which means the placed articles will be copied too and the relations are updated. 

Images will not be copied but they will get an additional placement.

-configuration-

The copy process can be controlled with a custom meta data field on the Layout. Configuration:

1. Create a custom meta data field:
Name	DEEPCOPY
Display Name	"Copy Articles"
Type	Boolean
Default Value	0


2. Add this custom meta data field to the CopyTo dialog *for Layout objects only*

Now a checkbox will appear in the Layout copy dialog. The checkbox indicates whether the articles for the source layout will be copied too.

If you do no define the custom meta data field DEEPCOPY, the plug-in will always copy the articles.

-notes-
If one of the articles which is copied already exists in the target issue/category, the copy process terminates with an error.


-disclaimer-
This plug-in is provided as a sample. 
