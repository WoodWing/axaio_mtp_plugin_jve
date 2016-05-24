/****************************************************************************
   Copyright 2009 WoodWing Software BV

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
****************************************************************************/
#include "claro.inc.jsx"

var doc = app.activeDocument;

// prepare crop/rotation parameters from images to server (in case we need it later)
var toServerTxt = '';
var id = doc.entMetaData.get("Core_ID");

var factorX = 0;
var factorY = 0;

for (var img = 0; img < doc.managedImages.length; img++) {
	var imgData = doc.managedImages[img];
	
	toServerTxt += imgData.entMetaData.get("Core_ID");

	var geoFrame = imgData.pageItem.geometricBounds;
	var geoImage = imgData.pageItem.graphics[0].geometricBounds;

	// determine scale factors (only first time)
	if (!factorX) {
		var rememberX = geoFrame[1];
		var rememberY = geoFrame[0];
		geoFrame[0] = "p1";
		geoFrame[1] = "p1";
		
		imgData.pageItem.geometricBounds = geoFrame;
		
		geoFrame = imgData.pageItem.geometricBounds;
		
		factorX = 1.0/geoFrame[1];
		factorY = 1.0/geoFrame[0];
		geoFrame[1] = rememberX;
		geoFrame[0] = rememberY;
		imgData.pageItem.geometricBounds = geoFrame;
		
		if (debug) alert("Conversion X: "+factorX+", Y:"+factorY);
	}

	toServerTxt += ","+imgData.pageItem.images[0].rotationAngle;
	
	// width and height
	var w = ((geoFrame[3] - geoFrame[1])/(geoImage[3] - geoImage[1])) * 100;
	var h = ((geoFrame[2] - geoFrame[0])/(geoImage[2] - geoImage[0])) * 100;

	// crop and end
	var cx = ((geoFrame[1] - geoImage[1])/(geoImage[3] - geoImage[1])) * 100;
	var cy = ((geoFrame[0] - geoImage[0])/(geoImage[2] - geoImage[0])) * 100;

	toServerTxt += ","+w;	// width
	toServerTxt += ","+h;	// height
	
	toServerTxt += ","+cx;	// cropx
	toServerTxt += ","+cy;	// cropy

	toServerTxt += "/";
}

if (debug) alert("Imageinfo: "+toServerTxt);

if (toServerTxt) {
	var myurl = SERVER+ '/config/plugins/claro/savestatus.php?id='+id+"&images="+toServerTxt;
	var myresult=app.performSimpleRequest(myurl);

	if (debug) alert(myresult);
}
