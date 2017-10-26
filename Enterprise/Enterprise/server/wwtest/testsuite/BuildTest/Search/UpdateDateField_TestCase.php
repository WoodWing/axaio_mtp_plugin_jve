<?php

require_once BASEDIR . '/server/wwtest/testsuite/BuildTest/Search/Search_TestCaseBase.php';

class WW_TestSuite_BuildTest_Search_UpdateDateField_TestCase extends WW_TestSuite_BuildTest_Search_Base
{
	public function getDisplayName()
	{
		return 'Update "Modified" date field in Solr Search';
	}

	public function getTestGoals()
	{
		return 'Update Object properties and verify if the updated values are searchable by Solr Search. ';
	}

	public function getTestMethods()
	{
		return 'Updates the properties of the object using SetObjectProperties and searches on the updated values ' .
			   'using QueryObjects. "Modified" property is changed and is being searched on. Search on the "Name" '.
				'property should give the same result as before.';
	}

	public function getPrio()
	{
		return 5;
	}

	final public function runTest()
	{
		parent::runTest();
		if( $this->hasError() ) {
			return;
		}

		if( !$this->validateUpdateObject() ) {
			return;
		}

		// Update session vars for next test cases
		$this->setSessionVariables( $this->vars );
	}

	public function __construct()
	{
	}


	/**
	 * Updates an object with given metadata by calling the MultiSetObjectProperties service.
	 *
	 * @param Object[] $objects Objects properties to update. On success, they get updated with latest info from DB.
	 * @param string $stepInfo Extra logging info.
	 * @param string|null $expectedError S-code when error expected. NULL when no error expected.
	 * @param MetaDataValue[] $updateProps List of metadata properties to update.
	 * @param string[] $changedPropPaths List of changed metadata properties, expected to be different.
	 * @return bool success or failure depending on response
	 */
	private function multiSetObjectProperties(
		$objects, $stepInfo, array $expectedErrors,
		array $updateProps, array $changedPropPaths )
	{
		// Collect object ids.
		$objectIds = array();
		foreach( $objects as $object ) {
			$objectIds[] = $object->MetaData->BasicMetaData->ID;
		}

		// Suppress errors that are expected.
		$serverityMap = array();
		foreach( $objectIds as $objectId ) {
			$expectedError = $expectedErrors[$objectId];
			if( !is_null($expectedError) ) {
				$expectedError = trim( $expectedError,'()' ); // remove () brackets
				$serverityMap[$expectedError] = 'INFO';
			}
		}
		$severityMapHandle = new BizExceptionSeverityMap( $serverityMap );
		$severityMapHandle = $severityMapHandle; // keep code analyzer happy

		// Call the SetObjectProperties service.
		require_once BASEDIR . '/server/services/wfl/WflMultiSetObjectPropertiesService.class.php';
		$request = new WflMultiSetObjectPropertiesRequest();
		$request->Ticket	= $this->ticket;
		$request->IDs       = $objectIds;
		$request->MetaData  = $updateProps;
		$response = $this->utils->callService( $this, $request, $stepInfo, null );
		if( !$response ) {
			return null;
		}
		unset($severityMapHandle); // until here the errors are expected, so end it

		// Check if expected errors can be found in the returned error reports.
		$compareOk = true;
		foreach( $objectIds as $objectId ) {
			$expectedError = $expectedErrors[$objectId];
			if( !is_null($expectedError) ) {
				$foundExpected = false;
				foreach( $response->Reports as $report ) {
					$belongsTo = $report->BelongsTo;
					if( $belongsTo->Type == 'Object' && $belongsTo->ID == $objectId ) {
						foreach( $report->Entries as $entry ) {
							if( '('.$entry->ErrorCode.')' == $expectedError ) {
								$foundExpected = true;
								break 2; // quit both foreach loops at once
							}
						}
					}
				}
				if( !$foundExpected ) {
					$errorMsg = 'Expected to raise error "'.$expectedError.'" for '.
						'object id "'.$objectId.'" but it was not found in the error reports.';
					$errorContext = 'Problem detected in Reports of MultiSetObjectProperties.';
					$this->setResult( 'ERROR', $errorMsg, $errorContext );
					$compareOk = false;
				}
			}
		}

		// Don't get objects for which an error was expected.
		$getObjIds = array();
		foreach( $objectIds as $objectId ) {
			if( is_null( $expectedErrors[$objectId] ) ) {
				$getObjIds[] = $objectId;
			}
		}

		// Call GetObjects to retrieve all changed properties from database.
		require_once BASEDIR .'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = $getObjIds;
		$request->Lock = false;
		$request->Rendition = 'none';
		$request->RequestInfo = array( 'MetaData', 'Targets' );
		$response = $this->utils->callService( $this, $request, $stepInfo, null );
		if( !$response ) {
			return null;
		}

		foreach( $response->Objects as $respObject ) {

			// Lookup the original/cached object for the object returned through web service response.
			foreach( $objects as $orgObject ) {
				if( $orgObject->MetaData->BasicMetaData->ID == $respObject->MetaData->BasicMetaData->ID ) {
					break; // found
				}
			}

			// Simulate the property updates in memory on the orignal/cached object.
			require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
			$flatMD = new stdClass();
			$flatMD->MetaDataValue = $updateProps;
			BizProperty::updateMetaDataTreeWithFlat( $orgObject->MetaData, $flatMD );

			// Validate MetaData and Targets; Compare the original ones with the ones found in service response.
			require_once BASEDIR.'/server/utils/PhpCompare.class.php';
			$phpCompare = new WW_Utils_PhpCompare();

			$phpCompare->initCompare( $changedPropPaths, array() );

			foreach( $changedPropPaths as $changedPropPath => $expPropValue ) {
				$retPropValue = null;
				eval( '$retPropValue = $respObject->'.$changedPropPath.';' );
				if( $retPropValue != $expPropValue ) {
					$errorMsg = 'The returned '.$changedPropPath.' is set to "'.
						$retPropValue.'" but should be set "'.$expPropValue.'".';
					$errorContext = 'Problem detected in MetaData of GetObjects response after calling MultiSetObjectProperties.';
					$this->setResult( 'ERROR', $errorMsg, $errorContext );
					$compareOk = false;
				}
			}

			// Update the orignal/cached object with response data.
			$orgObject->MetaData = $respObject->MetaData;
		}
		return $compareOk;
	}

	/**
	 * Test changing properties of an object.
	 *
	 * Validate the object is searchable using the new updated values.
	 */
	public function validateUpdateObject()
	{
		$articleObject = $this->vars['BuildTest_Search']['Article'];
		$articleID = $articleObject->MetaData->BasicMetaData->ID;
		$articleName = $this->vars['BuildTest_Search']['ArticleName'];

		// Change article modified to a non null date value more than one year in the past
		$articleModified = '2012-01-30T00:00:00';
		$articleObject->MetaData->WorkflowMetaData->Modified = $articleModified;

		$expectedErrors = array();
		$expectedErrors[$articleID] = null; // no error

		$stepInfo = 'Change "Modified" date field by calling MultiSetObjectProperties service.';

		$updateProps = array();
		$mdValue = new MetaDataValue();
		$mdValue->Property = 'Modified';
		$propValue = new PropertyValue();
		$propValue->Value = $articleModified;
		$mdValue->PropertyValues = array( $propValue );
		$updateProps[] = $mdValue;

		$changedPropPaths = array(
			'MetaData->WorkflowMetaData->Modified' => $articleModified,
		);

		if( !$this->multiSetObjectProperties( array($articleObject), $stepInfo, $expectedErrors, $updateProps, $changedPropPaths ) ) {
			return false;
		}

		$queryParam = new QueryParam( 'Modified', '=', '>_This_Year' );
		if( !$this->testSearch( $articleID, $articleName, 'Searching for article on "Modified" value (older than a year)', true, array($queryParam) ) ) {
			return false;
		}

		// Clear modified
		$articleModified = '';

		$updateProps = array();
		$mdValue = new MetaDataValue();
		$mdValue->Property = 'Modified';
		$propValue = new PropertyValue();
		$propValue->Value = $articleModified;
		$mdValue->PropertyValues = array( $propValue );
		$updateProps[] = $mdValue;

		$changedPropPaths = array(
			'MetaData->WorkflowMetaData->Modified' => $articleModified,
		);

		if( !$this->multiSetObjectProperties( array($articleObject), $stepInfo, $expectedErrors, $updateProps, $changedPropPaths ) ) {
			return false;
		}

		// Test modified field is cleared in Solr...
		// Note: a null modified field does not show up in any of the date range facets
		$queryParam = new QueryParam( 'Modified', '=', '>_This_Year' );
		if( !$this->testSearch( $articleID, $articleName, 'Searching for article on old "Modified" date field value', false, array($queryParam) ) ) {
			return false;
		}

		// Object should still be findable on name
		if( !$this->testSearch( $articleID, $articleName, 'Searching for article on name', true ) ) {
			return false;
		}

		return true;
	}
}
