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

// check if we need to process this document
var doc = app.activeDocument;

var factorX = 0;
var factorY = 0;
// reset crop/rotation parameters in images
for (var img = 0; img < doc.managedImages.length; img++) {
	var imgData = doc.managedImages[img];
	for(var sameImage = 0; sameImage < imgData.pageItem.length; sameImage++) { // Same image might be place multiple times, so loop through every instance
		var geoFrame = imgData.pageItem[0].geometricBounds;
	
		if (imgData.entMetaData.get("Core_Basket") == POSTPROCESSSTATUS) {
	
			if (debug) alert("Postprocessing image: "+imgData.entMetaData.get("Core_Name"));
		
			// rotate
			if (doRotate) {
				imgData.pageItem[sameImage].graphics[0].rotationAngle = 0;
			}
	
			// crop
			if (doCrop) {
				imgData.pageItem[sameImage].graphics[0].horizontalScale = 100;
				imgData.pageItem[sameImage].graphics[0].verticalScale = 100;
		
				geoImage = imgData.pageItem[sameImage].graphics[0].geometricBounds;
				geoImage[0] = geoFrame[0];
				geoImage[1] = geoFrame[1];
		
				imgData.pageItem[sameImage].graphics[0].geometricBounds = geoFrame;
			}
		}
	}
}