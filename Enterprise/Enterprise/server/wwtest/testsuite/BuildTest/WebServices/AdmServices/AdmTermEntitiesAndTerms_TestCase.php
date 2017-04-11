<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v8.0.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_AdmServices_AdmTermEntitiesAndTerms_TestCase extends TestCase
{
	public function getDisplayName() { return 'TermEntities and Terms'; }
	public function getTestGoals()   { return 'Checks if all admin TermEntity and Term services run well. '; }
	public function getTestMethods() { return 'Call Adm-create/modify/get/delete TermEntities/Terms service calls to see if the data is round-tripped.'; }
    public function getPrio()        { return 140; }

	private $ticket = null; // string, session ticket for admin user
	private $utils = null; // WW_Utils_TestSuite

	private $termEntities = null;
	private $terms = null;
	private $searchCities = null;

	const AUTOCOMPLETE_PROVIDER = 'MultiChannelPublishingSample';

	final public function runTest()
	{
		// Init utils.
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		// Retrieve the Ticket that has been determined by AdmInitData TestCase.
   		$vars = $this->getSessionVariables();
   		$this->ticket = @$vars['BuildTest_WebServices_AdmServices']['ticket'];
		if( !$this->ticket ) {
			$this->setResult( 'ERROR',  'Could not find ticket to test with.', 'Please enable the "Setup test data" test.' );
			return;
		}

		// Perform the test with MultiChannelPublishingSample plug-in activated.
		$didActivate = $this->utils->activatePluginByName( $this, 'MultiChannelPublishingSample' );
		if( is_null($didActivate) ) { // Error during activate?
			$this->setResult( 'ERROR', 'MultiChannelPublishingSample plugin cannot be activated.', 'Please check if the plugin is '.
				'installed and activate it in the ServerPlugin page.' );
			return;
		}

		// Run tests.
		do {
			$this->clearTermEntitiesAndTerms();
			if( !$this->testCreateTermEntities() ) { break; }
			if( !$this->testModifyTermEntities() ) { break; }
			if( !$this->testGetTermEntities() )    { break; }
			if( !$this->testCreateTerms() )        { break; }
			if( !$this->testModifyTerms() )        { break; }
			if( !$this->testGetTerms() )           { break; }
			if( !$this->testDeleteTerms() )        { break; }
			if( !$this->testDeleteTermEntities() ) { break; }

		} while( false );

		// In case the above tests failed before testDeleteTerms() or testDeleteTermEntities(),
		// the Terms or TermEntities might not be deleted yet, so here, delete them before ending
		// the test.
		if( !is_null( $this->terms ) ) {
			$this->testDeleteTerms();
		}
		if( !is_null( $this->termEntities ) ) {
			$this->testDeleteTermEntities();
		}

		// Restore plugin activation.
		if( $didActivate ) { // If we did activate the server plugin, deactivate it now ..
			$this->utils->deactivatePluginByName( $this, 'MultiChannelPublishingSample' );
		}
	}

	/****************************************** TermEntities ********************************************************/

	/**
	 * To run several AdmCreateAutocompleteTermEntities service calls and validate its responses.
	 *
	 * @return bool
	 */
	private function testCreateTermEntities()
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateAutocompleteTermEntitiesService.class.php';
		$request = new AdmCreateAutocompleteTermEntitiesRequest();
		$request->Ticket = $this->ticket;

		do {
			// Invalid request: Extra Id provided (which shouldn't be set in the Create call).
			$termEntity = new AdmTermEntity();
			$termEntity->Id = 999; // Set a random Id, which is invalid for Create operation.
			$termEntity->Name = 'Citi';
			$termEntity->AutocompleteProvider = self::AUTOCOMPLETE_PROVIDER;

			$request->TermEntities = array( $termEntity );
			$stepInfo = 'Testing on AdmCreateAutocompleteTermEntities service.(Invalid Id)';
			if( $this->utils->callService( $this,  $request, $stepInfo, '(S1000)' ) ) {
				$this->setResult( 'ERROR', 'TermEntity->Id is provided in the AdmCreateAutocompleteTermEntities request, '.
					'expected an error but no error in the service response.', 'Please check in the ' .
					'AdmCreateAutocompleteTermEntities service call.' );
				$result = false;
				break;
			}

			// Invalid request: Missing AutocompleteProvider.
			$termEntity = new AdmTermEntity();
			$termEntity->Name = 'Citi';

			$request->TermEntities = array( $termEntity );
			$stepInfo = 'Testing on AdmCreateAutocompleteTermEntities service.(Missing AutocompleteProvider)';
			if( $this->utils->callService( $this,  $request, $stepInfo, '(S1000)' ) ) {
				$this->setResult( 'ERROR', 'TermEntity->AutocompleteProvider is not provided in the ' .
					'AdmCreateAutocompleteTermEntities request, expected an error but no error in the service response.',
					'Please check in the AdmCreateAutocompleteTermEntities service call.' );
				$result = false;
				break;
			}

			// Invalid request: Inconsistent AutocompleteProvider.
			$termEntities = array();
			$termEntity = new AdmTermEntity();
			$termEntity->Name = 'Citi';
			$termEntity->AutocompleteProvider = self::AUTOCOMPLETE_PROVIDER;
			$termEntities[] = $termEntity;

			$termEntity = new AdmTermEntity();
			$termEntity->Name = 'Countries';
			$termEntity->AutocompleteProvider = self::AUTOCOMPLETE_PROVIDER . '2';
			$termEntities[] = $termEntity;

			$request->TermEntities = $termEntities;
			$stepInfo = 'Testing on AdmCreateAutocompleteTermEntities service.(Inconsistent AutocompleteProvider)';
			if( $this->utils->callService( $this,  $request, $stepInfo, '(S1000)' ) ) {
				$this->setResult( 'ERROR', 'TermEntity->AutocompleteProvider is not consistent throughout the TermEntities ' .
					'provided in the AdmCreateAutocompleteTermEntities request, expected an error but no error in the service response.',
					'Please check in the AdmCreateAutocompleteTermEntities service call.' );
				$result = false;
				break;
			}

			// Valid CreateAutocompleteTermEntities request.
			$this->termEntities = array();
			$termEntity = new AdmTermEntity();
			$termEntity->Name = 'Citi';
			$termEntity->AutocompleteProvider = self::AUTOCOMPLETE_PROVIDER;
			$this->termEntities[] = $termEntity;

			$termEntity = new AdmTermEntity();
			$termEntity->Name = 'Countries';
			$termEntity->AutocompleteProvider = self::AUTOCOMPLETE_PROVIDER;
			$this->termEntities[] = $termEntity;

			$request->TermEntities = $this->termEntities;
			$stepInfo = 'Testing on AdmCreateAutocompleteTermEntities service.';
			$response = $this->utils->callService( $this,  $request, $stepInfo );
			$result = $this->verifyCreateTermEntitiesResp( $response );
			if( $result ) {
				$this->termEntities = $response->TermEntities;
			}
		} while( false );

		return $result;
	}

	/**
	 * To validate AdmCreateAutocompleteTermEntities response.
	 *
	 * @param AdmCreateAutocompleteTermEntitiesResponse $response
	 * @return bool
	 */
	private function verifyCreateTermEntitiesResp( $response )
	{
		$result = true;
		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		do{
			if( $response->TermEntities ) foreach( $response->TermEntities as $termEntity ) {
				if( !$termEntity->Id ) {
					$this->setResult( 'ERROR', 'The returned TermEntity CreateAutocompleteTermEntities has no TermEntity ' .
						'Id which is invalid. Please check in the CreateAutocompleteTermEntities service call.');
					$result = false;
					break 2;
				}
			}
			$phpCompare = new WW_Utils_PhpCompare();
			$phpCompare->initCompare( array(
				'[0]->Id' => true,  // For creation, there's no Id yet, so this property can be ignored.
				'[1]->Id' => true, ) );
			if( !$phpCompare->compareTwoArrays( $this->termEntities, $response->TermEntities )) {
				$this->setResult( 'ERROR', 'The returned TermEntities in the CreateAutocompleteTermEntities is invalid. ' .
					print_r( $phpCompare->getErrors(),1) );
				$result = false;
				break;
			}

		} while( false );
		return $result;
	}

	/**
	 * To run several AdmModifyAutocompleteTermEntities service calls and validate its responses.
	 *
	 * @return bool
	 */
	private function testModifyTermEntities()
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyAutocompleteTermEntitiesService.class.php';
		$request = new AdmModifyAutocompleteTermEntitiesRequest();
		$request->Ticket = $this->ticket;

		// Modify(Update) the TermEntity name.
		if( $this->termEntities ) foreach( $this->termEntities as $termEntity ) {
			if( $termEntity->Name == 'Citi' ) {
				$termEntity->Name = 'City';
			}
		}

		do {
			// Invalid request: Id is not provided.
			$termEntities = unserialize( serialize( $this->termEntities ));
			if( $termEntities ) foreach( $termEntities as $termEntity ) {
				$termEntity->Id = null;
			}
			$request->TermEntities = $termEntities;
			$stepInfo = 'Testing on AdmModifyAutocompleteTermEntities service.(Missing Id)';
			if( $this->utils->callService( $this,  $request, $stepInfo, '(S1000)' ) ) {
				$this->setResult( 'ERROR', 'TermEntity->Id is not provided in the ' .
					'AdmModifyAutocompleteTermEntities request, expected an error but no error in the service response.',
					'Please check in the AdmModifyAutocompleteTermEntities service call.' );
				$result = false;
				break;
			}

			// Invalid request: Inconsistent AutocompleteProvider.
			$termEntities = unserialize( serialize( $this->termEntities ));
			if( $termEntities ) foreach( $termEntities as $termEntity ) {
				if( $termEntity->Name == 'City' ) {
					$termEntity->AutocompleteProvider = 'abc'; // Make AutocompleteProvider inconsistent with other TermEntity.
				}
			}
			$request->TermEntities = $termEntities;
			$stepInfo = 'Testing on AdmModifyAutocompleteTermEntities service.(Inconsistent AutocompleteProvider)';
			if( $this->utils->callService( $this,  $request, $stepInfo, '(S1000)' ) ) {
				$this->setResult( 'ERROR', 'TermEntity->AutocompleteProvider is not consistent throughout the TermEntities ' .
					'provided in the AdmModifyAutocompleteTermEntities request, expected an error but no error in the service response.',
					'Please check in the AdmModifyAutocompleteTermEntities service call.' );
				$result = false;
				break;
			}

			// Valid request.
			$termEntities = unserialize( serialize( $this->termEntities ));

			// In the request, set the AutocompleteProvider to be null.
			if( $termEntities ) foreach( $termEntities as $termEntity ) {
				// When AutocompleteProvider is set to null, server should resolve it.
				// In the modifyAutocompleteTermEntities response, we expect the AutocompleteProvider is resolved.
				$termEntity->AutocompleteProvider = null;
			}
			$request->TermEntities = $termEntities;
			$stepInfo = 'Testing on AdmModifyAutocompleteTermEntities service.';
			$response = $this->utils->callService( $this,  $request, $stepInfo );
			$result = $this->verifyModifyTermEntitiesResp( $response );

		} while( false) ;

		return $result;
	}

	/**
	 * To validate AdmModifyAutocompleteTermEntities response.
	 *
	 * @param AdmModifyAutocompleteTermEntitiesResponse $response
	 * @return bool
	 */
	private function verifyModifyTermEntitiesResp( $response )
	{
		$result = true;
		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		do{
			$phpCompare = new WW_Utils_PhpCompare();
			$phpCompare->initCompare( array() );
			if( !$phpCompare->compareTwoArrays( $this->termEntities, $response->TermEntities )) {
				$this->setResult( 'ERROR', 'The returned TermEntities in the ModifyAutocompleteTermEntities is invalid. ' .
					print_r( $phpCompare->getErrors(),1) );
				$result = false;
				break;
			}

		} while( false );
		return $result;
	}

	/**
	 * Call the AdmGetAutocompleteTermEntities service and returns its response.
	 *
	 * @return AdmGetAutocompleteTermEntitiesResponse
	 */
	private function getTermEntities()
	{
		require_once BASEDIR.'/server/services/adm/AdmGetAutocompleteTermEntitiesService.class.php';
		$request = new AdmGetAutocompleteTermEntitiesRequest();
		$request->Ticket = $this->ticket;
		$request->AutocompleteProvider = self::AUTOCOMPLETE_PROVIDER;
		$stepInfo = 'Testing on AdmGetAutocompleteTermEntities service.';
		$response = $this->utils->callService( $this,  $request, $stepInfo );
		return $response;
	}

	/**
	 * To run the AdmGetAutocompleteTermEntities service call and validate its response.
	 *
	 * @return bool
	 */
	private function testGetTermEntities()
	{
		$response = $this->getTermEntities();
		$result = $this->verifyGetTermEntitiesResp( $response );

		return $result;
	}

	/**
	 * To validate AdmGetAutocompleteTermEntities response.
	 *
	 * @param AdmGetAutocompleteTermEntitiesResponse $response
	 * @return bool
	 */
	private function verifyGetTermEntitiesResp( $response )
	{
		$result = true;
		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		do{
			$phpCompare = new WW_Utils_PhpCompare();
			$phpCompare->initCompare( array() );
			if( !$phpCompare->compareTwoArrays( $this->termEntities, $response->TermEntities )) {
				$this->setResult( 'ERROR', 'The returned TermEntities in the GetAutocompleteTermEntities is invalid. ' .
					print_r( $phpCompare->getErrors(),1) );
				$result = false;
				break;
			}

		} while( false );
		return $result;
	}

	/**
	 * To run the AdmDeleteAutocompleteTermEntities service call and validate its response.
	 *
	 * @return bool
	 */
	private function testDeleteTermEntities()
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteAutocompleteTermEntitiesService.class.php';
		$request = new AdmDeleteAutocompleteTermEntitiesRequest();
		$request->Ticket = $this->ticket;

		do {
			// Invalid request: Id is not provided.
			$termEntities = unserialize( serialize( $this->termEntities ));
			if( $termEntities ) foreach( $termEntities as $termEntity ) {
				$termEntity->Id = null;
			}
			$request->TermEntities = $termEntities;
			$stepInfo = 'Testing on AdmDeleteAutocompleteTermEntities service.(Missing Id)';
			if( $this->utils->callService( $this,  $request, $stepInfo, '(S1000)' ) ) {
				$this->setResult( 'ERROR', 'TermEntity->Id is not provided in the ' .
					'AdmDeleteAutocompleteTermEntities request, expected an error but no error in the service response.',
					'Please check in the AdmDeleteAutocompleteTermEntities service call.' );
				$result = false;
				break;
			}

			// Invalid request: Inconsistent AutocompleteProvider.
			$termEntities = unserialize( serialize( $this->termEntities ));
			if( $termEntities ) foreach( $termEntities as $termEntity ) {
				if( $termEntity->Name == 'City' ) {
					$termEntity->AutocompleteProvider = 'abc'; // Make AutocompleteProvider inconsistent with other TermEntity.
				}
			}
			$request->TermEntities = $termEntities;
			$stepInfo = 'Testing on AdmDeleteAutocompleteTermEntities service.(Inconsistent AutocompleteProvider)';
			if( $this->utils->callService( $this,  $request, $stepInfo, '(S1000)' ) ) {
				$this->setResult( 'ERROR', 'TermEntity->AutocompleteProvider is not consistent throughout the TermEntities ' .
					'provided in the AdmDeleteAutocompleteTermEntities request, expected an error but no error in the service response.',
					'Please check in the AdmDeleteAutocompleteTermEntities service call.' );
				$result = false;
				break;
			}

			// Valid request.
			$request->TermEntities = $this->termEntities;
			$stepInfo = 'Testing on AdmDeleteAutocompleteTermEntities service.';
			$response = $this->utils->callService( $this,  $request, $stepInfo );
			$result = $this->verifyDeleteTermEntitiesResp( $response );
			$this->termEntities = null; // Clear.

		} while ( false );

		return $result;
	}

	/**
	 * To validate AdmDeleteAutocompleteTermEntities service call.
	 *
	 * Function calls the AdmGetAutocompleteTermEntities service to ensure that the
	 * TermEntities are no longer exists.
	 *
	 * @param AdmDeleteAutocompleteTermEntities $response
	 * @return bool
	 */
	private function verifyDeleteTermEntitiesResp( $response )
	{
		// Since there's nothing returned in the response, we query for the TermEntities
		// to verify if the TermEntities have really been deleted.
		$result = true;
		$termEntitiesInDB = $this->getTermEntities();
		if( count( $termEntitiesInDB->TermEntities ) > 0 ) {
			$termEntities = array();
			foreach( $this->termEntities as $termEntity ) {
				if( !isset( $termEntities[$termEntity->Id] )) {
					$termEntities[$termEntity->Id] = true;
				}
			}

			foreach( $termEntitiesInDB as $termEntityInDB ) {
				$id = $termEntityInDB->Id;
				if( isset( $termEntities[$id]) ) {
					$this->setResult( 'ERROR', 'The TermEntity(id='.$id.') is not deleted during the DeleteAutocompleteTermEntities ' .
						'service call, which is incorrect, please check in the DeleteAutocompleteTermEntities service.');
					$result = false;
				}
			}
		}

		return $result;
	}

	/****************************************** Terms *************************************************************/

	/**
	 * To run the AdmCreateAutocompleteTerms service call and validate its response.
	 *
	 * @return bool
	 */
	private function testCreateTerms()
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateAutocompleteTermsService.class.php';
		$request = new AdmCreateAutocompleteTermsRequest();
		$request->Ticket = $this->ticket;

		do {
			// Invalid request: Id is not provided.
			$request->TermEntity = new AdmTermEntity();
			$request->Terms =  array( 'Amsterdam', 'The Hague' );
			$stepInfo = 'Testing on AdmCreateAutocompleteTerms service.(Missing Id)';
			if( $this->utils->callService( $this,  $request, $stepInfo, '(S1000)' ) ) {
				$this->setResult( 'ERROR', 'TermEntity->Id is not provided in the ' .
					'AdmCreateAutocompleteTerms request, expected an error but no error in the service response.',
					'Please check in the AdmCreateAutocompleteTerms service call.' );
				$result = false;
				break;
			}

			// Valid request
			$city1 = 'Stra'. chr( 0xC3) . chr( 0x9F ) . 'bur'; // Normalized name = Stra's'bur
			$city2 = 'Holb'. chr( 0xC3) . chr(0xA6) . 'k'; // Normalized name = Holb'ae'k
			$this->searchCities = array( $city2, 'Holroyd', 'Holstebro' ); // For response validation later.
			$this->terms = array( $city1, 'Amsterdam', 'Holstebro', 'Holroyd', $city2 );
			$request->TermEntity = $this->getCityTermEntity();
			$request->Terms =  $this->terms;
			$stepInfo = 'Testing on AdmCreateAutocompleteTerms service.';
			$response = $this->utils->callService( $this,  $request, $stepInfo );
			$result = $this->verifyTermsResp( $response, 'create' );

		} while ( false );

		return $result;
	}

	/**
	 * To run the AdmModifyAutocompleteTerms service and validate its response.
	 *
	 * @return bool
	 */
	private function testModifyTerms()
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyAutocompleteTermsService.class.php';
		$request = new AdmModifyAutocompleteTermsRequest();
		$request->Ticket = $this->ticket;

		do {
			// Invalid request: Id is not provided.
			$request->TermEntity = new AdmTermEntity();
			// Not the test subject, so just put same values for both old and new.
			$request->OldTerms =  array( 'Amsterdam', 'The Hague' );
			$request->NewTerms =  array( 'Amsterdam', 'The Hague' );
			$stepInfo = 'Testing on AdmModifyAutocompleteTerms service.(Missing Id)';
			if( $this->utils->callService( $this,  $request, $stepInfo, '(S1000)' ) ) {
				$this->setResult( 'ERROR', 'TermEntity->Id is not provided in the ' .
					'AdmModifyAutocompleteTerms request, expected an error but no error in the service response.',
					'Please check in the AdmModifyAutocompleteTerms service call.' );
				$result = false;
				break;
			}

			// Valid request.
			$newTerms = unserialize( serialize( $this->terms )); // Deep clone.
			$city = 'Stra'. chr( 0xC3) . chr( 0x9F ) . 'bur'; // without 'g' in the end.
			if( $newTerms ) foreach( $newTerms as &$term ) {
				if( $term == $city ) {
					$term = 'Stra'. chr( 0xC3) . chr( 0x9F ) . 'burg'; // with 'g' in the end.
				}
			}
			$request->TermEntity = $this->getCityTermEntity();
			$request->OldTerms = $this->terms;
			$request->NewTerms = $newTerms;
			$stepInfo = 'Testing on AdmModifyAutocompleteTerms service.';
			$response = $this->utils->callService( $this,  $request, $stepInfo );
			$this->terms = $newTerms; // Update in the memory, will be used in the validation function later.
			$result = $this->verifyTermsResp( $response, 'modify' );

		} while ( false );

		return $result;
	}

	/**
	 * To validate AdmCreateAutocompleteTerms or AdmModifyAutocompleteTerms response.
	 *
	 * @param AdmCreateAutocompleteTermsResponse|AdmModifyAutocompleteTermsResponse $response
	 * @param string $action 'create', 'modify'
	 * @return bool
	 */
	private function verifyTermsResp( $response, $action )
	{
		$result = true;
		require_once BASEDIR .'/server/dbclasses/DBAdmAutocompleteTerm.class.php';
		foreach( $this->terms as $term ) {
			$admTerm = new AdmTerm();
			$admTerm->EntityId = $this->termEntities[0]->Id;
			$admTerm->DisplayName = $term;
			$admTerm->NormalizedName = null;
			$termInDB = DBAdmAutocompleteTerm::getTerm( $admTerm );
			if( is_null( $termInDB )) { // Not found in the database, error here.
				$message = '';
				$serviceName = '';
				if( $action == 'create' ) {
					$message = 'created';
					$serviceName = 'AdmCreateAutocompleteTerms';
				} else if( $action == 'modify' ) {
					$message = 'modified';
					$serviceName = 'AdmModifyAutocompleteTerms';
				}
				$this->setResult( 'ERROR', 'Term "'.$term.'" was not '.$message.' in '.$serviceName.' service, which is wrong.',
					'Please check in the '.$serviceName.' service.' );
				$result = false;
				break;
			}
		}

		return $result;
	}

	/**
	 * To run the AdmGetAutocompleteTerms service and validate its response.
	 *
	 * @return bool
	 */
	private function testGetTerms()
	{
		require_once BASEDIR.'/server/services/adm/AdmGetAutocompleteTermsService.class.php';
		$request = new AdmGetAutocompleteTermsRequest();
		$request->Ticket     = $this->ticket;

		do {
			// Invalid request: Id is not provided.
			$request->TermEntity = new AdmTermEntity();
			// Not the test subject, so just put any values.
			$request->TypedValue = 'Hol';
			$request->FirstEntry = 5;
			$request->MaxEntries = 6;
			$stepInfo = 'Testing on AdmGetAutocompleteTerms service.(Missing Id)';
			if( $this->utils->callService( $this,  $request, $stepInfo, '(S1000)' ) ) {
				$this->setResult( 'ERROR', 'TermEntity->Id is not provided in the ' .
					'AdmGetAutocompleteTerms request, expected an error but no error in the service response.',
					'Please check in the AdmGetAutocompleteTerms service call.' );
				$result = false;
				break;
			}

			// Valid request.
			$request->TermEntity = $this->getCityTermEntity();
			$request->TypedValue = 'Hol';
			$request->FirstEntry = 5;  // TODO: add the first entry when this parameter is supported.
			$request->MaxEntries = 6; // TODO: add the max entry when this parameter is supported.
			$stepInfo = 'Testing on AdmGetAutocompleteTerms service.';
			$response = $this->utils->callService( $this,  $request, $stepInfo );
			$result = $this->verifyGetTermsResp( $response );

		} while( false );

		return $result;
	}

	/**
	 * To validate AdmGetAutocompleteTerms response.
	 *
	 * @param AdmGetAutocompleteTermsResponse $response
	 * @return bool
	 */
	private function verifyGetTermsResp( $response )
	{
		$result = true;
		do {
			if( !$response->Terms ) {
				$this->setResult( 'ERROR', 'No terms returned in the GetAutocompleteTerms response, which is incorrect.' .
					'Expected terms "'.implode( ',', $this->searchCities).'". ',
					'Please check in the GetAutocompleteTerms service call.' );
					$result = false;
				break;
			}
			require_once BASEDIR.'/server/utils/PhpCompare.class.php';
			$phpCompare = new WW_Utils_PhpCompare();
			$phpCompare->initCompare( array());
			if( !$phpCompare->compareTwoArrays( $this->searchCities, $response->Terms )) {
				$this->setResult( 'ERROR', 'The terms returned by the GetAutocompleteTerms service is incorrect:' .
					print_r( $phpCompare->getErrors(),1), 'Please check in the GetAutocompleteTerms service call.' );
				$result = false;
				break;
			}
		} while( false );


		return $result;
	}

	/**
	 * To run the AdmDeleteAutocompleteTerms service and validate its response.
	 *
	 * @return bool
	 */
	private function testDeleteTerms()
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteAutocompleteTermsService.class.php';
		$request = new AdmDeleteAutocompleteTermsRequest();
		$request->Ticket     = $this->ticket;

		do {
			// Invalid request: Id is not provided.
			$request->TermEntity = new AdmTermEntity();
			// Not the test subject, so just put any value.
			$request->Terms = array( 'Hol' );
			$stepInfo = 'Testing on AdmDeleteAutocompleteTerms service.(Missing Id)';
			if( $this->utils->callService( $this,  $request, $stepInfo, '(S1000)' ) ) {
				$this->setResult( 'ERROR', 'TermEntity->Id is not provided in the ' .
					'AdmDeleteAutocompleteTerms request, expected an error but no error in the service response.',
					'Please check in the AdmDeleteAutocompleteTerms service call.' );
				$result = false;
				break;
			}

			// Valid request.
			$request->TermEntity = $this->getCityTermEntity();
			$request->Terms = $this->terms;
			$stepInfo = 'Testing on AdmDeleteAutocompleteTerms service.';
			$response = $this->utils->callService( $this,  $request, $stepInfo );
			$result = $this->verifyDeleteTermsResp( $response );
			$this->terms = null; // Clear.

		} while( false );
		return $result;
	}

	/**
	 * To validate AdmDeleteAutocompleteTerms response.
	 *
	 * @param AdmDeleteAutocompleteTermsResponse $response
	 * @return bool
	 */
	private function verifyDeleteTermsResp( $response )
	{
		// Since there's nothing returned in the response, we query for the Terms
		// to verify if the Terms have really been deleted.
		$result = true;
		require_once BASEDIR .'/server/dbclasses/DBAdmAutocompleteTerm.class.php';
		foreach( $this->terms as $term ) {
			$admTerm = new AdmTerm();
			$admTerm->EntityId = $this->termEntities[0]->Id;
			$admTerm->DisplayName = $term;
			$admTerm->NormalizedName = null;
			$termInDB = DBAdmAutocompleteTerm::getTerm( $admTerm );
			if( !is_null( $termInDB )) { // Found in the database (which shouldn't), so error here.
				$this->setResult( 'ERROR', 'Term "'.$term.'" was not deleted in AdmDeleteAutocompleteTerms service, which is wrong.',
					'Please check in the AdmDeleteAutocompleteTerms service.' );
				$result = false;
				break;
			}
		}
		return $result;
	}

	/**
	 * Retrieve the City TermEntity from a list of TermEntities($this->termEntities)
	 * @return AdmTermEntity|null Car TermEntity or null when not found
	 */
	private function getCityTermEntity()
	{
		$cityTermEntity = null;
		$termEntities = unserialize( serialize( $this->termEntities ));
		if( $termEntities ) foreach( $termEntities as $termEntity ) {
			if( $termEntity->Name == 'City' ) {
				$termEntity->AutocompleteProvider = null; // Server will resolve this.
				$termEntity->Name = null; // Server will resolve this.
				$cityTermEntity = $termEntity;
				break; // Found
			}
		}
		return $cityTermEntity;
	}

	/****************************************** Helper functions ******************************************************/

	/**
	 * To delete a list of Term Entities and Terms belong to MultiChannelPublishingSample provider.
	 */
	private function clearTermEntitiesAndTerms()
	{
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmAutocompleteTermEntity.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmAutocompleteTerm.class.php';
		require_once BASEDIR.'/server/services/adm/AdmDeleteAutocompleteTermEntitiesService.class.php';
		require_once BASEDIR.'/server/services/adm/AdmDeleteAutocompleteTermsService.class.php';

		// Delete the Terms.
		$service = new AdmDeleteAutocompleteTermsService();
		$request = new AdmDeleteAutocompleteTermsRequest();
		$request->Ticket = $this->ticket;

		$termEntities = DBAdmAutocompleteTermEntity::getTermEntityByProvider( self::AUTOCOMPLETE_PROVIDER );
		if( $termEntities ) foreach( $termEntities as $termEntity ) {
			$terms = array();
			$admTerms = DBAdmAutocompleteTerm::getTermsByTermEntityId( $termEntity->Id );
			if( $admTerms ) foreach( $admTerms as $admTerm ) {
				$terms[] = $admTerm->DisplayName;
			}
			$request->TermEntity = $termEntity;
			$request->Terms = $terms;
			$service->execute( $request );
		}

		// Delete the Term Entities.
		if( $termEntities ) {
			$service = new AdmDeleteAutocompleteTermEntitiesService();
			$request = new AdmDeleteAutocompleteTermEntitiesRequest();
			$request->Ticket = $this->ticket;
			$request->TermEntities = $termEntities;
			$service->execute( $request );
		}
	}
}
