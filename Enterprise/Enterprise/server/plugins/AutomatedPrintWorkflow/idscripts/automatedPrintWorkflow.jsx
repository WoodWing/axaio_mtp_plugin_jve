/*
	- SCRIPT_ARG_KEYS -

	A list of script argument keys reflecting one-on-one the keys which can be passed by Smart Connection.
	This list will be used to construct a usable javascript object from it.
	When changes are made to the to pass arguments, this list should also be changed.

	Please be aware that most of the script argument keys are sent by the Smart Connection plugin in Pascal case.
	The reason for this is that the parameters from the Enterprise server are passed to the script without change.
	Initially, this means that these keys have to be defined with the same uppercase first letter in the
	SCRIPT_ARG_KEYS array beneath.

	The argument keys have to be given a lower case first character in order to work in the javascript
	Object model. This will be done at the moment of the operationObj creation.
*/
var SCRIPT_ARG_KEYS = [ "documentId",	// The object id of the layout to perform the operation on.
						"id",			// The GUID of the sent operation.
						"name",			// The name of the operation (PlaceArticleElement, PlaceImage or ClearFrameContent).
						"EditionId",	// The Enterprise database ID of the Edition for which the operation is created.
						"ArticleId",	// The Enterprise database ID of the parent Article.
						"ElementId",	// The GUID of the article component to place. When placing an image the elementID will be empty.
						"ImageId",		// The Enterprise database ID of the image object.
						"SplineId",		// The UID of the frame on the layout to place into or to clear.
						"ContentDx",	// The horizontal offset of the graphic.
						"ContentDy",	// The vertical offset of the graphic.
						"ScaleX",		// The horizontal scaling factor of the graphic.
						"ScaleY" ];		// The vertical scaling factor of the graphic.

/**
 *	This functions creates the operationObj where it lives locally
 *	and will dispatch the operations from.
 */
(function handleObjectOperations() {

	app.wwlog( "script", parseInt(LogLevelOptions.INFO), "<AutomatedPrintWorkflow::HandleObjectOperations>" );

	do {
		// Create the operation object from the given script paramers.
		// Once the operationObject is created it can be used to process the operations.
		//
		// The sent script arguments will be matched against the defined SCRIPT_ARG_KEYS
		// array and, if present, made into an object property for easy reference and set
		// with the untouched accompanying value.
		var operationObj = (function(){
			var opsInstructions = {};
			var scriptArgObj = app.scriptArgs;
			function firstLetterToLower( sKey ) {
			    return (sKey[0].toLowerCase() + sKey.slice(1));
			}
			for each( var argKey in SCRIPT_ARG_KEYS ) {
				if( scriptArgObj.isDefined(argKey) ) {
					opsInstructions[firstLetterToLower(argKey)] = scriptArgObj.get(argKey);
				}
			}
			return opsInstructions;
		})();

		// Log the operation object.
		logObjectOperations( operationObj );

		// Resolve the document to perform the operation on.
		var theDoc = getDocument( operationObj.documentId );
		if( theDoc === undefined ) {
			app.wwlog( "script", parseInt(LogLevelOptions.INFO), "  <HandleObjectOperations error=[No valid document found]/>" );
			break;
		}

		app.wwlog( "script", parseInt(LogLevelOptions.INFO), "  <HandleObjectOperations operationName=["+operationObj.name+"]/>" );

		// Perform the object operation considering the operation name.
		// New operations can be added and handled in this switch.
		switch( operationObj.name ) {
			case "ClearFrameContent":
				clearFrameContent( theDoc, operationObj );
				break;
			case "PlaceImage":
				placeImage( theDoc, operationObj );
				break;
			case "PlaceArticleElement":
				placeArticleElement( theDoc, operationObj );
				break;
			default:
				app.wwlog( "script", parseInt(LogLevelOptions.INFO), "  <HandleObjectOperations warning=[Operation not found]/>" );
				break;
		}
	}
	while( false );

	app.wwlog( "script", parseInt(LogLevelOptions.INFO), "</AutomatedPrintWorkflow::HandleObjectOperations>" );
})();


/**
 *	Logs the source of the passed object.
 *
 *	@param {Object}	operationObj	The object operation insructions in object form.
 */
function logObjectOperations( operationObj ) {
	
	app.wwlog( "script", parseInt(LogLevelOptions.INFO), "<HandleCandidateRelations::LogObjectOperations>" );

	app.wwlog( "script", parseInt(LogLevelOptions.INFO), "  " + operationObj.toSource() );

	app.wwlog( "script", parseInt(LogLevelOptions.INFO), "</HandleCandidateRelations::LogObjectOperations>" );	
}


/**
 *	Places an Enterprise database article in a page item, either solely by its
 *	database object ID or per article element by its GUID.
 *
 *	@param {Object} docObject		The document object, or parent, of the pageItemObject.
 *	@param {Object}	operationObj	The object operation instructions in object form.
 */
function placeArticleElement( docObject, operationObj ) {

	app.wwlog( "script", parseInt(LogLevelOptions.INFO), "<AutomatedPrintWorkflow::PlaceArticleElement frameUID=["+operationObj.splineId+"] elementId=["+operationObj.elementId+"]>" );

	do {
		// Get the frame object from the UID.
		var curFrame = docObject.pageItems.itemByID( parseInt(operationObj.splineId) );
		
		if( !curFrame.isValid ) {
			app.wwlog( "script", parseInt(LogLevelOptions.ERROR), "  <PlaceArticleElement error=[No valid frame]/>" );
			break;
		}
		
		// Check if the article is already placed.
		if( eval(curFrame.properties.frameData)[0].ElementID === operationObj.elementId ) {
			app.wwlog( "script", parseInt(LogLevelOptions.INFO), "  <PlaceArticleElement info=[Article component is laready placed]/>" );
			break;
		}

		// Before placing we will make sure the frame is empty.
		clearFrameContent( docObject, operationObj );
		app.wwlog( "script", parseInt(LogLevelOptions.INFO), "  <PlaceArticleElement info=[Frame is cleared]/>" );

		// Replace the candidate frame with the actual Enterprise object (component).
		curFrame.placeObject( operationObj.articleId, operationObj.elementId );

		app.wwlog( "script", parseInt(LogLevelOptions.INFO), "  <PlaceArticleElement info=[Object is placed]/>" );
	}
	while( false );

	app.wwlog( "script", parseInt(LogLevelOptions.INFO), "</AutomatedPrintWorkflow::PlaceArticleElement>" );
}


/**
 *	Places an Enterprise database image in a page item by its database object ID.
 *
 *	@param {Object} docObject		The document object, or parent, of the pageItemObject.
 *	@param {Object}	operationObj	The object operation instructions in object form.
 */
function placeImage( docObject, operationObj ) {
	
	app.wwlog( "script", parseInt(LogLevelOptions.INFO), "<AutomatedPrintWorkflow::PlaceImage imageId=["+operationObj.imageId+"]>" );

	do
	{
		// Get the frame object from the UID.
		var curFrame = docObject.pageItems.itemByID( parseInt(operationObj.splineId) );

		// Get the frame object from the UID.
		if( curFrame === undefined || !curFrame.isValid ) {
			app.wwlog( "script", parseInt(LogLevelOptions.ERROR), "  <PlaceImage error=[No valid frame]/>" );
			break;
		}
		
		// We will not place on an image frame which has an object lock.
		if( curFrame.locked === true ) {
			app.wwlog( "script", parseInt(LogLevelOptions.INFO), "  <PlaceImage info=[Cannot place on image frame with an Object lock]/>" );
			break;
		}

		// Check if the pageItem is of the managedImage type.
		var bImageAlreadyPlaced = false;
		if( curFrame.properties.hasOwnProperty("managedImage") && curFrame.properties.managedImage !== null ) {
			bImageAlreadyPlaced = (curFrame.managedImage.entMetaData.get("Core_ID") === operationObj.imageId);
		}

		// If the image is not yet placed, place it.
		if( !bImageAlreadyPlaced ) {
			// Replace the candidate frame with the actual Enterprise object (component).
			curFrame.placeObject( operationObj.imageId );
			app.wwlog( "script", parseInt(LogLevelOptions.INFO), "  <PlaceImage info=[Image is placed]>" );
		}
		else {
			curFrame.updateCaptionAndCredit();
			app.wwlog( "script", parseInt(LogLevelOptions.INFO), "  <PlaceImage info=[Image is already placed]>" );
		}
		
		// Store the horizontal and vertical measurements unit setting.
		var storedHorMes = docObject.viewPreferences.horizontalMeasurementUnits;
		var storedVertMes = docObject.viewPreferences.verticalMeasurementUnits;

		// Set the document's horizontal and vertical measurements unit to 'Points'.
		docObject.viewPreferences.horizontalMeasurementUnits = MeasurementUnits.POINTS;
		docObject.viewPreferences.verticalMeasurementUnits = MeasurementUnits.POINTS;

		// Determine rotation of the image. Later on this will be used to adapt scaling and translation.
        	var image = curFrame.images.firstItem();
        	var imageRotationAngle = image.rotationAngle;
        	var rotM = (imageRotationAngle / 90 + 8) % 4;
        	var rotCW = ( rotM === 1);
        	var rotCCW = (rotM === 3);
        	var rot180 = (rotM === 2);
       
		// ********************
		// Scaling.
		// ********************

		// swap scaleX/scaleY in case of 90 or 270 rotation (EN-88412)
		// Reason: with 90/270 rotation, the Y-axis and X-axis of the graphic are swapped by InDesign
        	if ( rotCW || rotCCW )
        	{
            		var tempScaleX = operationObj.scaleX;
            		operationObj.scaleX = operationObj.scaleY;
            		operationObj.scaleY = tempScaleX;
        	}

		// When scaling and translation are set to '0', we will fit the content
		// to the frame if the dimensions did no change.
		if( operationObj.scaleX === "0" && operationObj.scaleY === "0" &&
			operationObj.contentDx === "0" && operationObj.contentDy === "0" ) {
			curFrame.fit( FitOptions.PROPORTIONALLY );
		}
		else {
			// Only check and translate when the values are defined.
			if( operationObj.scaleX !== undefined && operationObj.scaleY !== undefined ) {
		
				// Obtain the current horizontal and vertical scaling factors.
				var curScaleX = parseFloat(curFrame.allGraphics[0].horizontalScale / 100);
				var curScaleY = parseFloat(curFrame.allGraphics[0].verticalScale / 100);
			
				if( !bImageAlreadyPlaced && (operationObj.scaleX === "1") && (operationObj.scaleY === "1") ) {
					app.wwlog( "script", parseInt(LogLevelOptions.INFO), "  <PlaceImage info=[No scaling needed]>" );
				}
				else {
					// Check if the horizontal and vertical scaling are already set as wanted.
					// If not, we will set them correctly.
					if( (curScaleX !== parseFloat(operationObj.scaleX)) || (curScaleY !== parseFloat(operationObj.scaleY)) ) {
						app.wwlog( "script", parseInt(LogLevelOptions.INFO), "  <PlaceImage info=[Scaling needed]>" );

						// Scale the image.
						curFrame.allGraphics[0].horizontalScale = (operationObj.scaleX * 100);
						curFrame.allGraphics[0].verticalScale = (operationObj.scaleY * 100);
					}
				}
			}
			else {
				app.wwlog( "script", parseInt(LogLevelOptions.INFO), "<PlaceImage info=[Scaling is not defined]/>" );
			}

			// ********************
			// Translation.
			// ********************

			// Only check and translate when the values are defined.
			if( operationObj.contentDx !== undefined && operationObj.contentDy !== undefined ) {
                    		// Check if the translation has already been done.
                    		var frameBounds = curFrame.geometricBounds;
                    		var graphicBounds = curFrame.allGraphics[0].geometricBounds;
                    		/*
					app.wwlog( "script", parseInt(LogLevelOptions.INFO), "  <PlaceImage graphicBounds[0]=["+graphicBounds[0]+"]>" );
                            		app.wwlog( "script", parseInt(LogLevelOptions.INFO), "  <PlaceImage graphicBounds[1]=["+graphicBounds[1]+"]>" );
                            		app.wwlog( "script", parseInt(LogLevelOptions.INFO), "  <PlaceImage frameBounds[0]=["+frameBounds[0]+"]>" );
                            		app.wwlog( "script", parseInt(LogLevelOptions.INFO), "  <PlaceImage frameBounds[1]=["+frameBounds[1]+"]>" );
                            	*/
                 
                    		// Check if there is a difference in offset.
                    		var curOffsetY = (graphicBounds[0] - frameBounds[0]);
                    		var curOffsetX = (graphicBounds[1] - frameBounds[1]);
				
                    		// Check if the horizontal and vertical offset are already set as wanted.
                    		// If not, we will set them correctly.
                    		if( (parseFloat(curOffsetX) !== parseFloat(operationObj.contentDx)) || (parseFloat(curOffsetY) !== parseFloat(operationObj.contentDy)) ) {
              
                        		app.wwlog( "script", parseInt(LogLevelOptions.INFO), "  <PlaceImage: going to move>" );

                        		// Align the image after placing top-left of the parent page item.
                        		// In case of rotated image we have to take care (EN-88412):
                        		//  - when rotated 90 degrees Clock Wise, the reference point is the bottom-left.
                        		//  - when rotated 90 degrees Counter Clock Wise, the reference point is the top-right
                        		// - when rotated 180 degrees, the reference point is the bottom-right
                        		// We move the reference point of the image such that after moving, the top left corner of the image aligns with the top left corner of the frame.
                        
                        		var graphicWidth = graphicBounds[3] - graphicBounds[1];
                        		var graphicHeight = graphicBounds[2] - graphicBounds[0];
                        		var xNew = 0;
                        		var yNew = 0;
                        
                        		if ( rotCW ) {
                            			app.wwlog( "script", parseInt(LogLevelOptions.INFO), "  <PlaceImage: move: 90 CW rotation>" );
                            			yNew = frameBounds[0] + graphicHeight;
                            			xNew = frameBounds[1];
                        		}
                        		else if ( rotCCW ) {
                            				app.wwlog( "script", parseInt(LogLevelOptions.INFO), "  <PlaceImage: move: 90 CCW rotation>" );
                            				yNew = frameBounds[0] ;
                            				xNew = frameBounds[1] + graphicWidth;
                        		}
                        		else if ( rot180 ) {
                            				app.wwlog( "script", parseInt(LogLevelOptions.INFO), "  <PlaceImage: move: 180 rotation>" );
                            				yNew = frameBounds[0] + graphicHeight;
                            				xNew = frameBounds[1] + graphicWidth;
                        		}
                        		else {
                            				yNew = frameBounds[0] ;
                            				xNew = frameBounds[1];
                       			}
                       			curFrame.allGraphics[0].move([xNew, yNew ]);

					// Create a transformation matrix for the image.
					var translationMatrix = app.transformationMatrices.add({horizontalTranslation:parseFloat(operationObj.contentDx), verticalTranslation:parseFloat(operationObj.contentDy)});
					curFrame.allGraphics[0].transform(CoordinateSpaces.PARENT_COORDINATES, AnchorPoint.TOP_LEFT_ANCHOR, translationMatrix);
				}
			}
			else {
				app.wwlog( "script", parseInt(LogLevelOptions.INFO), "<PlaceImage info=[Translation is not defined]/>" );
			}
		}

		// ********************
		// Restore the horizontal and vertical measurements unit setting.
		docObject.viewPreferences.horizontalMeasurementUnits = storedHorMes;
		docObject.viewPreferences.verticalMeasurementUnits = storedVertMes;
	}
	while( false );
	app.wwlog( "script", parseInt(LogLevelOptions.INFO), "</AutomatedPrintWorkflow::PlaceImage>" );
}


/**
 *	Clears the page item content but keeps the frame UID intact.
 *
 *	@param {Object} docObject		The document object, or parent, of the pageItemObject.
 *	@param {Object}	operationObj	The object operation instructions in object form.
*/
function clearFrameContent( docObject, operationObj ) {

	app.wwlog( "script", parseInt(LogLevelOptions.INFO), "<AutomatedPrintWorkflow::ClearFrameContent frameUID=["+operationObj.splineId+"]>" );

	do {
		// Get the frame object from the UID.
		var curFrame = docObject.pageItems.itemByID( parseInt(operationObj.splineId) );
		
		// Get the frame object from the UID.
		if( !curFrame.isValid ) {
			app.wwlog( "script", parseInt(LogLevelOptions.ERROR), "  <ClearFrameContent error=[No valid frame]/>" );
			break;
		}

		// Check if the pageItem is of the managedArticle type.
		var bManagedArticle = false;
		if( eval(curFrame.properties.frameData)[0].ElementID !== "" ) {
			bManagedArticle = true;
		}
		
		if( bManagedArticle ) {
			var sMATempLog = "";

			// Check the amount of placed components of the managed article parent.
			// If there is more than 1 component placed of the article, then we
			// will only detach the current page item frame. Else, if there is only
			// one component placed of the article, we will detach the whole article.
			if( curFrame.managedArticle.components.length > 1 ) {
				sMATempLog += "ManagedArticle component frame";
				curFrame.managedArticle.detachFrame(curFrame);
			}
			else {
				sMATempLog += "ManagedArticle frame";
				curFrame.managedArticle.detach();
			}

			app.wwlog( "script", parseInt(LogLevelOptions.INFO), "  <ClearFrameContent info=["+sMATempLog+" is detached]/>" );
		}


		// Check if there are inline frames in the page item. If so, remove them.
		for( var nCurPI = (curFrame.pageItems.length - 1); nCurPI >= 0; nCurPI-- ) {
			curFrame.pageItems[nCurPI].remove();
		}

		app.wwlog( "script", parseInt(LogLevelOptions.INFO), "  <ClearFrameContent info=[PageItem does not contain inline frames]/>" );


		// Clear all text fram the page item.
		if( curFrame.hasOwnProperty("contents") ) {
			for( var nCur = 0; nCur < 100; nCur++ ) {
				if( curFrame.contents === "" ) {
					break;
				}
				curFrame.contents = "";
			}
			app.wwlog( "script", parseInt(LogLevelOptions.INFO), "  <ClearFrameContent info=[Text is removed from the pageItem]/>" );
		}

	}
	while( false );

	app.wwlog( "script", parseInt(LogLevelOptions.INFO), "</AutomatedPrintWorkflow::ClearFrameContent>" );
}


/**
 *	Gets the requested document object from all open documents and returns it.
 *
 *	@param 		{Object} docObject		The document object, or parent, of the pageItemObject.
 *	@param 		{Object} operationObj	The object operation instructions in object form.
 *	@returns	{Object} doc 			The document to work with.
 */
function getDocument( docId ) {
	
	app.wwlog( "script", parseInt(LogLevelOptions.INFO), "<AutomatedPrintWorkflow::GetDocument docId=["+docId+"]>" );

	// Get the document to do the placement on.
	var doc = undefined;

	do {
		var docs = app.documents;
		// Loop through all open documents to find the one with
		// the requested Enterprise database ID.
		for( var nCurDoc = 0; nCurDoc < docs.length; nCurDoc++ ) {

			if( !!docs[nCurDoc].entMetaData.length && docs[nCurDoc].entMetaData.get("Core_ID") === docId ) {
				doc = docs[nCurDoc];
				break;
			}
		}

		// Check if we have a valid document.
		if( doc === undefined || doc.constructor.name !== "Document" ) {
			app.wwlog( "script", parseInt(LogLevelOptions.ERROR), "  <GetDocument error=[No matching document found]/>" );
			break;
		}
	}
	while( false )

	app.wwlog( "script", parseInt(LogLevelOptions.INFO), "</AutomatedPrintWorkflow::GetDocument>" );

	return doc;
}
