<?php
/****************************************************************************
Copyright 2013 WoodWing Software BV

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

require_once dirname(__FILE__) . '/WordPressXmlRpcClient.class.php';

class WordPress_Utils
{
	const WORDPRESS_PLUGIN_NAME = 'WordPress';
	const CONFIG_ENT_SUGGESTION_ENTITY = 'wordpress_suggestion_entity';

	/**
	 * Import WordPress Tags.
	 *
	 * This function imports the WordPress tags and clears the old tags and then creates a new termEntity.
	 */
	function importTags()
	{
		require_once BASEDIR.'/server/interfaces/services/pub/DataClasses.php';
		require_once BASEDIR.'/server/services/adm/AdmCreateAutocompleteTermEntitiesService.class.php';
		require_once BASEDIR.'/server/services/adm/AdmCreateAutocompleteTermsService.class.php';

		$clientWordPress = new WordPressXmlRpcClient();
		$connectionInfo = $this->getConnectionInfo();
		$sites = $connectionInfo['sites'];
		$this->clearTermEntitiesAndTerms();

		if( $sites ) foreach( $sites as $siteName => $site ) {
			$clientWordPress->setConnectionPassword( $site['password'] );
			$clientWordPress->setConnectionUserName( $site['username'] );
			$clientWordPress->setConnectionUrl( $site['url'] . '/xmlrpc.php' );

			$tags = $clientWordPress->getTags();
			$preparedWordPressTags = array();
			foreach( $tags as $wordpressTag ){
				$preparedWordPressTags[] = $wordpressTag['name'];
			}

			$termEntity = new AdmTermEntity();
			$termEntity->Name = 'wordpress_tags_' . $siteName;
			$termEntity->AutocompleteProvider = self::WORDPRESS_PLUGIN_NAME;

			$service = new AdmCreateAutocompleteTermEntitiesService();
			$request = new AdmCreateAutocompleteTermEntitiesRequest();
			$request->Ticket = BizSession::getTicket();
			$request->TermEntities = array( $termEntity );
			$response = $service->execute( $request );

			if( $tags ) {
				$service = new AdmCreateAutocompleteTermsService();
				$request = new AdmCreateAutocompleteTermsRequest();
				$request->Ticket = BizSession::getTicket();
				$request->TermEntity = $response->TermEntities[0];
				$request->Terms = $preparedWordPressTags;
				$service->execute( $request );
			}
		}
	}

	/**
	 * Get the Connection info for WordPress.
	 *
	 * This function return the info that is needed for the connection to WordPress.
	 *
	 * @throws BizException
	 * @param object $publishTarget currently used for supporting multiple sites.
	 * @return array $info contains the info needed to connect to the WordPress site
	 */
	public function getConnectionInfo( $publishTarget = null )
	{
		$sites = unserialize( WORDPRESS_SITES );
		if( !$sites ) {
			$detail = BizResources::localize( 'WORDPRESS_ERROR_NO_SITES_TIP' );
			throw new BizException( 'WORDPRESS_ERROR_NO_SITES', 'Server', $detail );
		}

		if( !$publishTarget ) {
			return array( 'sites' => $sites ); // when we have nothing to filter just return all the sites. Used for HealthCheck and for importing customObjectMetadata
		}

		require_once BASEDIR . '/server/bizclasses/BizAdmProperty.class.php';
		require_once BASEDIR . '/server/utils/PublishingUtils.class.php';
		$channelObj = WW_Utils_PublishingUtils::getAdmChannelById( $publishTarget->PubChannelID );
		$siteKey = BizAdmProperty::getCustomPropVal( $channelObj->ExtraMetaData, 'C_WP_CHANNEL_SITE' );

		if( $siteKey && isset( $sites[$siteKey] ) ) {
			$site = $sites[$siteKey];
		} else {
			LogHandler::Log( 'SERVER', 'ERROR', 'Channel "' . $channelObj->Name . '" does not have a site configured or the site does not exist' );
		}

		if( !isset($site['url']) || !isset($site['username']) || !isset($site['password']) ) { // check if none of these credentials is empty
			throw new BizException( 'WORDPRESS_ERROR_SITE_CREDENTIALS', 'Server', 'Incorrect credentials', null, array( $siteKey ) );
		}
		$url = $site['url'];

		// More connection info can be added if needed
		$info = array();
		$info['siteName'] = $siteKey;
		$info['username'] = $site['username'];
		$info['password'] = $site['password'];
		$info['connectionUrl'] = $url . '/xmlrpc.php';
		$info['standardUrl'] = $url;

		return $info;
	}

	/**
	 * Check the url syntax
	 *
	 * This function checks the filled in WordPress URL if it has the right syntax and if it is responsive.
	 * If the url syntax is incorrect or the url is not responsive this will give an error.
	 * Can also be used for the health check then the second param should be true
	 *
	 * @throws BizException
	 * @param $url string
	 */
	public function checkUrlSyntax( $url )
	{
		try {
			require_once 'Zend/Uri.php';
			require_once BASEDIR.'/server/utils/UrlUtils.php';
			$check = Zend_Uri::check( $url );
			$responsive = WW_Utils_UrlUtils::isResponsiveUrl( $url );
		} catch( Exception $e ) {
			$e = $e;
			throw new BizException( 'WORDPRESS_ERROR_INVALID_URL', 'Server', 'WordPress Url error' , null, array( $url ));
		}

		if( !$check ) {
			throw new BizException( 'WORDPRESS_ERROR_INVALID_URL', 'Server', 'WordPress Url error' , null, array( $url ) );
		} else if( !$responsive ) {
			throw new BizException( 'WORDPRESS_ERROR_NOT_RESPONDING', 'Server', 'WordPress Url error', null, array( $url ));
		}
	}

	/**
	 * Get Categories and Formats
	 *
	 * Get all the categories and all the formats from all sites WordPress.
	 * These categories and Formats can be default and custom.
	 * These are used for the import of the categories widget and the formats widget.
	 *
	 * @throws BizException
	 * @return array
	 */
	public function getAllCategoriesAndFormats()
	{
		$wordpressUtils = new WordPress_Utils();
		$clientWordPress = new WordPressXmlRpcClient();
		$connectionInfo = $wordpressUtils->getConnectionInfo();
		$sites = $connectionInfo['sites'];
		$retVal = array();

		if( $sites ) foreach( $sites as $siteKey => $site ){
			$normalizedSiteKey = $this->normalizeSiteName( $siteKey );
			$clientWordPress->setConnectionUserName( $site['username'] );
			$clientWordPress->setConnectionPassword( $site['password'] );
			$clientWordPress->setConnectionUrl( $site['url'] . '/xmlrpc.php' );

			try {
				$retVal[$normalizedSiteKey]['categories'] = $this->prepareCategories( $clientWordPress->getCategories() );
			} catch ( BizException $e ){
				throw new BizException( 'WORDPRESS_ERROR_IMPORT_CATEGORIES', 'SERVER', 'Import Failed - Get categories' );
			}

			try {
				$formats = $clientWordPress->getFormats();
				$retVal[$normalizedSiteKey]['formats'] = $formats;
				$flippedFormats = array_flip( $formats );
				$this->saveFormats( $flippedFormats, $siteKey );
			} catch ( BizException $e ){
				$e = $e;
				throw new BizException( 'WORDPRESS_ERROR_IMPORT_FORMATS', 'SERVER', 'Import Failed - Get Formats' );
			}
		}

		return $retVal;
	}

    /**
     * This function calls the recursive function which creates the tree of categories.
     * All the child categories will be prepared with dashes.
     *
     * @param array $categories all the categories for a specific site.
     * @return array $preparedCategories returns the tree of found categories, prepared with dashes.
     */
    public function prepareCategories( $categories )
    {
        $preparedCategories = array();

        foreach( $categories as $category ) {
            if( !$category['parentId'] ){
                $this->findChildCategories( $category, $categories, 0, $preparedCategories );
            }
        }

        return $preparedCategories;
    }

    /**
     * This function searches children for the parent category the $search param.
     * Also the category name will be changed according to the level it is in.
     *
     * @param array $search this is a category that has no parent, it possibly has children.
     * @param array $allCategories all the categories for a specific site.
     * @param int $level the level that we're searching in.
     * @param array $foundTree the categories that we have found containing parents with children behind them.
     */
    public function findChildCategories( $search, $allCategories, $level, &$foundTree )
    {
        $categoryName = null;
        if( $level > 0 ) {
            for( $i = 0; $i < $level; $i++ ) {
                $categoryName .= '-';
            }
            $categoryName .= ' ';
        }
        $categoryName .= $search['categoryName'];
        $foundTree[] = $categoryName;

        foreach( $allCategories as $category ) {
            if( $search['categoryId'] == $category['parentId'] ) {
                $this->findChildCategories( $category, $allCategories, $level + 1, $foundTree );
            }
        }
    }

	/**
	 * Get WordPress site name
	 *
	 * Get the normalized site name by resolving the publishTarget.
	 * This function returns the siteName that can be used for multiple actions.
	 * For example it is used for getting and saving users per site.
	 *
	 * @param $publishTarget
	 * @return string
	 */
	public function getSiteName( $publishTarget )
	{
		$wordpressUtils = new WordPress_Utils();
		$connectionInfo = $wordpressUtils->getConnectionInfo( $publishTarget );
		$siteName = $connectionInfo['siteName'];

		return $wordpressUtils->normalizeSiteName( $siteName );
	}

	/**
	 * Update WordPress Tags widget.
	 *
	 * After importing new tags with the only tags option this is needed to update the tags widget.
	 */
	public function updateTagsWidget()
	{
		require_once BASEDIR.'/server/dbclasses/DBProperty.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmAutocompleteTermEntity.class.php';

		$termEntities = DBAdmAutocompleteTermEntity::getTermEntityByProvider( self::WORDPRESS_PLUGIN_NAME );
		if( $termEntities ) foreach( $termEntities as $termEntity ) {
			$tagsWidgetName = 'C_' . strtoupper($termEntity->Name);
			$tagsPropertyInfos = DBProperty::getPropertyInfos( self::WORDPRESS_PLUGIN_NAME, $tagsWidgetName );

			if( $tagsPropertyInfos ) {
				$tagsPropertyInfo = reset( $tagsPropertyInfos );
				$tagsPropertyInfo->TermEntity = $termEntity->Name;
				$tagsPropertyInfo->AutocompleteProvider = $termEntity->AutocompleteProvider;
				$tagsPropertyInfo->PublishSystemId = 0;

				DBProperty::updatePropertyInfo( $tagsWidgetName, $tagsPropertyInfo, array( 'serverplugin' => self::WORDPRESS_PLUGIN_NAME ) );
			}
		}
	}

	/**
	 * Delete WordPress Term Entities.
	 *
	 * To delete a list of Term Entities and Terms belong to WordPress provider.
	 */
	private function clearTermEntitiesAndTerms()
	{
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmAutocompleteTermEntity.class.php';
		require_once BASEDIR.'/server/services/adm/AdmDeleteAutocompleteTermEntitiesService.class.php';

		// Delete the Term Entities and all belonging Terms.
		$termEntities = DBAdmAutocompleteTermEntity::getTermEntityByProvider( self::WORDPRESS_PLUGIN_NAME );
		if( $termEntities ) {
			$service = new AdmDeleteAutocompleteTermEntitiesService();
			$request = new AdmDeleteAutocompleteTermEntitiesRequest();
			$request->Ticket = BizSession::getTicket();
			$request->TermEntities = $termEntities;
			$service->execute( $request );
		}
	}

	/**
	 * Stores the Enterprise Suggestion Entity for tags in the database.
	 *
	 * @param string $suggestionEntity The entity to be stored / updated.
	 * @return bool Whether or not the entity was successfully stored.
	 */
	public static function storeEnterpriseSuggestionEntity( $suggestionEntity )
	{
		require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
		return DBConfig::storeValue( self::CONFIG_ENT_SUGGESTION_ENTITY , $suggestionEntity );
	}

	/**
	 * Retrieves the Enterprise Suggestion Entity for tags from the database
	 *
	 * @return null|string The retrieved entity, or null if not set.
	 */
	public static function getEnterpriseSuggestionEntity()
	{
		require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
		return DBConfig::getValue( self::CONFIG_ENT_SUGGESTION_ENTITY );
	}

	/**
	 * Import users from WordPress.
	 *
	 * This function should be called when importing users, this function will get the users and save them.
	 * The username will be converted with strtolower().
	 *
	 */
	public function importAllUsers()
	{
		require_once 'Zend/Json.php';
		$connectionInfo = $this->getConnectionInfo();
		$sites = $connectionInfo['sites'];

		if( $sites ) foreach( $sites as $siteName => $site ) {
			$preparedUsers = array();
			$users = $this->getAllUsersFromWordpress( 250, $site ,'import' );  // the first param defines how much users you want to get every request. the second param which fields you want to get
			foreach( $users as $user ) {
				$preparedUsers[strtolower($user['username'])] = $user['user_id'];
			}
			$this->releaseSavedUsers( $siteName );

			file_put_contents( $this->getSavedUsersFilePath( $siteName ), Zend_Json::encode( $preparedUsers ));
		}
	}

	/**
	 * Get all the WordPress users
	 *
	 * This function gets all the author users from WordPress,
	 * this means that the subscriber and contributor users will not be returned.
	 *
	 * @param int $numberOfUsers this specifies the number of users to get every request
	 * @param array $siteConnectionInfo
	 * @param string $fields
	 * @return array $allWordPressUsers
	 */
	public function getAllUsersFromWordpress( $numberOfUsers, $siteConnectionInfo, $fields = null )
	{
		$offSet = 0;
		$allWordPressUsers = array();
		$clientWordPress = new WordPressXmlRpcClient();
		$clientWordPress->setConnectionUserName( $siteConnectionInfo['username'] );
		$clientWordPress->setConnectionPassword( $siteConnectionInfo['password'] );
		$clientWordPress->setConnectionUrl( $siteConnectionInfo['url'] . '/xmlrpc.php' );

		do {
			$users = $clientWordPress->getUsers( $numberOfUsers, $offSet, $fields );
			$offSet += $numberOfUsers;
			$userCount = count( $users );
			$allWordPressUsers = array_merge( $allWordPressUsers, $users );
		} while( $userCount == $numberOfUsers ); // When returned users is the same as $numberOfUsers there may still be other users, so we'll try to get them.

		return $allWordPressUsers;
	}

	/**
	 * Delete saved users
	 *
	 * Here the .jason file from a saved access-token gets deleted
	 *
	 * @param string $siteName
	 */
	public function releaseSavedUsers( $siteName )
	{
		$tokenFile = $this->getSavedUsersFilePath( $siteName );
		if ( file_exists( $tokenFile ) ) {
			unlink($tokenFile);
		}
	}

	/**
	 * Get the saved users for wordpress (.json file)
	 *
	 * @param string $siteName
	 *
	 * @return array
	 */
	public function getSavedUsers( $siteName )
	{
		$savedUsers = file_get_contents( $this->getSavedUsersFilePath( $siteName ));

		require_once 'Zend/Json.php';
		return Zend_Json::decode( $savedUsers );
	}

	/**
	 * The directory for saving and loading users
	 *
	 * @param string $siteName
	 * @return string
	 */
	private function getSavedUsersFilePath( $siteName )
	{
		return $this->getSavingFilePath() . '/users_' . $this->normalizeSiteName($siteName) . '.json';
	}

    /**
     * Save the formats for wordpress (.json file)
     *
     * @param array $formats
     * @param string $siteName
     *
     * @return array
     */
    public function saveFormats( $formats, $siteName )
    {
        $this->releaseSavedFormats( $siteName );

        require_once 'Zend/Json.php';
        file_put_contents( $this->getSavedFormatsFilePath( $siteName ), Zend_Json::encode( $formats ));
    }

    /**
     * Get the saved formats for WordPress (.json file)
     *
     * @param string $siteName
     *
     * @return array
     */
    public function getSavedFormats( $siteName )
    {
        $savedFormats = file_get_contents( $this->getSavedFormatsFilePath( $siteName ));

        require_once 'Zend/Json.php';
        return Zend_Json::decode( $savedFormats );
    }

    /**
     * Delete saved users
     *
     * Here the .jason file from a saved access-token gets deleted
     *
     * @param string $siteName
     */
    public function releaseSavedFormats( $siteName )
    {
        $tokenFile = $this->getSavedFormatsFilePath( $siteName );
        if ( file_exists( $tokenFile ) ) {
            unlink($tokenFile);
        }
    }

    /**
     * The directory for saving and loading formats
     *
     * @param string $siteName
     * @return string
     */
    private function getSavedFormatsFilePath( $siteName )
    {
        return $this->getSavingFilePath() . '/formats_' . $this->normalizeSiteName($siteName) . '.json';
    }

    /**
     * The directory for saving and loading.
     *
     * @return string
     */
    private function getSavingFilePath()
    {
        require_once BASEDIR . '/server/utils/FolderUtils.class.php';
        FolderUtils::mkFullDir( PERSISTENTDIRECTORY . '/WordPress/' );

        return PERSISTENTDIRECTORY . '/WordPress';
    }

	/**
	 * This function is used to clear strings from special characters and replace spaces by _
	 *
	 * @param $string
	 * @return mixed
	 */
	public function normalizeSiteName($string)
	{
		$string = str_replace(' ', '_', $string); // Replaces all spaces with underscores. (Custom properties don't support hyphens)
		return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
	}

	/**
	 * Goes through the sitekeys of the websites and sees if any of the normalized strings exceed the maximum length.
	 *
	 * @throws BizException when a sitekey exceeds 10 characters.
	 */
	public function checkAllSiteKeyLengths()
	{
		$connectionInfo = $this->getConnectionInfo();
		if ( $connectionInfo ) foreach( array_keys( $connectionInfo['sites'] ) as $siteKey ) {
			$cleanSiteKey = $this->normalizeSiteName( $siteKey );
			if( strlen( $cleanSiteKey ) - substr_count( $cleanSiteKey, '-' ) > 10 ) {
				$detail = BizResources::localize( 'WORDPRESS_ERROR_SITE_NAME_LENGTH_TIP' );
				throw new BizException( 'WORDPRESS_ERROR_SITE_NAME_LENGTH_MESSAGE', 'ERROR', $detail, null, array( $siteKey, $cleanSiteKey ) );
			}
		}
	}

	/**
	 * Obfuscates the passwords in the logging for all WordPress requests by replacing the password with '***'.
	 *
	 * This function and its parameters are based on LogHandler::replacePasswordWithAsterisk.
	 *
	 * @param string $methodName Request method name.
	 * @param string $transData Raw request data in XML format.
	 * @return string The complete request with replaced password.
	 */
	public static function obfuscatePasswordInRequest( $methodName, $transData )
	{
		$doc = new DOMDocument();
		$doc->loadXML( $transData );
		$xpath = new DOMXPath($doc);
		$params = $xpath->query('//methodCall/params/param/value/*');
		// WordPress uses different APIs, each of which has a different order of communicating properties.
		if( substr( $methodName, 0, 9 ) == 'woodwing.' && $methodName !== 'woodwing.GetPreviewUrl' ) {
			$item = $params->item(1);
		} else {
			$item = $params->item(2);
		}
		$item->nodeValue = '***';
		return $doc->saveXML();
	}
}