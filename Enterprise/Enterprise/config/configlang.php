<?php

function getObjectTypeMap()
{	
	static $map;
	if (!isset($map)) { // not cached yet
		$map = array();
		$map['Article']        		= BizResources::localize('OBJ_ARTICLE');
		$map['ArticleTemplate']		= BizResources::localize('OBJ_ARTICLE_TEMPLATE');
		$map['Layout']         		= BizResources::localize('OBJ_LAYOUT');
		$map['LayoutTemplate'] 		= BizResources::localize('OBJ_LAYOUT_TEMPLATE');
		$map['Image']          		= BizResources::localize('OBJ_IMAGE');
		$map['Advert']         		= BizResources::localize('OBJ_ADVERT');
		$map['AdvertTemplate'] 		= BizResources::localize('OBJ_ADVERT_TEMPLATE');
		$map['Plan']           		= BizResources::localize('OBJ_PLAN');
		$map['Audio']          		= BizResources::localize('OBJ_AUDIO');
		$map['Video']          		= BizResources::localize('OBJ_VIDEO');
		$map['Library']        		= BizResources::localize('OBJ_LIBRARY');
		$map['Dossier']        		= BizResources::localize('DOSSIER');
		$map['DossierTemplate'] 	= BizResources::localize('DOSSIER_TEMPLATE');
		$map['LayoutModule']   		= BizResources::localize('OBJ_LAYOUT_MODULE');
		$map['LayoutModuleTemplate']= BizResources::localize('OBJ_LAYOUT_MODULE_TEMPLATE');
		$map['Task']        		= BizResources::localize('OBJ_TASK');
		$map['Hyperlink']        	= BizResources::localize('OBJ_HYPERLINK');
		$map['Presentation']		= BizResources::localize('OBJ_PRESENTATION');
		$map['Archive']				= BizResources::localize('OBJ_ARCHIVE');
		$map['Spreadsheet']			= BizResources::localize('OBJ_SPREADSHEET');
		$map['Other']      			= BizResources::localize('OBJ_OTHER'); // BZ#10482
		$map['PublishForm']			= BizResources::localize('PUBLISH_FORM');
		$map['PublishFormTemplate']	= BizResources::localize('PUBLISH_FORM_TEMPLATE');
	}
	return $map;
}

function getPropertyTypeMap()
{	
	static $map;
	if (!isset($map)) { // not cached yet
		$map = array();
		$map['string']                      = BizResources::localize('PRO_STRING');
		$map['multistring']                 = BizResources::localize('PRO_MULTISTRING');
		$map['multiline']                   = BizResources::localize('PRO_MULTILINE');
		$map['bool']                        = BizResources::localize('PRO_BOOL');
		$map['int']                         = BizResources::localize('PRO_INT');
		$map['double']                      = BizResources::localize('PRO_DOUBLE');
		$map['date']                        = BizResources::localize('PRO_DATE');
		$map['datetime']                    = BizResources::localize('PRO_DATETIME');
		$map['list']                        = BizResources::localize('PRO_LIST');
		$map['multilist']                   = BizResources::localize('PRO_MULTILIST');
		$map['fileselector']                = BizResources::localize('PRO_FILESELECTOR');
		$map['file']                        = BizResources::localize('PRO_FILE');
		$map['articlecomponentselector']    = BizResources::localize('PRO_ARTICLECOMPONENTSELECTOR');
		$map['articlecomponent']            = BizResources::localize('PRO_ARTICLECOMPONENT');
	}
	return $map;
}

function getActionTypeMap()
{	
	static $map;
	if (!isset($map)) { // not cached yet
		$map = array();
		$map['Create']        = BizResources::localize('ACT_CREATE');
		$map['CheckIn']       = BizResources::localize('ACT_CHECKIN');
		$map['SendTo']        = BizResources::localize('ACT_SENDTO');
		$map['CopyTo']        = BizResources::localize('ACT_COPYTO');
		$map['SetProperties'] = BizResources::localize('ACT_SETPROPERTIES');
		$map['Preview'] 	  = BizResources::localize('ACT_PREVIEW');
		
		$map['Query']               	= BizResources::localize('ACT_QUERY');
		$map['QueryOut']            	= BizResources::localize('ACT_QUERYOUT');
		$map['QueryOutContentStation']	= BizResources::localize('ACT_QUERYOUTCONTENTSTATION');
		$map['QueryOutInCopy']      	= BizResources::localize('ACT_QUERYOUTINCOPY');
		$map['QueryOutInDesign']    	= BizResources::localize('ACT_QUERYOUTINDESIGN');
		$map['QueryOutPlanning']       	= BizResources::localize('ACT_QUERYOUTPLANNING');
	}
	return $map;
}

function getObjectActionTypeMap( $objType )
{
	static $map;
	if (!isset($map)) { // not cached yet
		if( $objType ) { // typically not set for Query action
			$objTypes = getObjectTypeMap();
			$objTxt = $objTypes[$objType];
		} else {
			$objTxt = '';
		}
		$map['Create']        = BizResources::localize('ACT_CREATE_OBJECT', true, array( $objTxt ) );
		$map['CheckIn']       = BizResources::localize('ACT_CHECKIN_OBJECT', true, array( $objTxt ) );
		$map['SendTo']        = BizResources::localize('ACT_SENDTO_OBJECT', true, array( $objTxt ) );
		$map['CopyTo']        = BizResources::localize('ACT_COPYTO_OBJECT', true, array( $objTxt ) );
		$map['SetProperties'] = BizResources::localize('ACT_PROPERTIES_DIALOG_TITLE', true, array( $objTxt ) );
		$map['Preview'] 	  = BizResources::localize('ACT_PREVIEW_OBJECT', true, array( $objTxt ) );
		$map['Query'] 		  = BizResources::localize('OBJ_QUERY_DIALOG' );
	}
	return $map;
}

function getPropertyViewMap()
{	
	static $map;
	if (!isset($map)) { // not cached yet
		$map = array();
		$map['All']      = BizResources::localize('PVW_ALL');
		$map['Static']   = BizResources::localize('PVW_STATIC');
		$map['Dynamic']  = BizResources::localize('PVW_DYNAMIC');
		$map['XMP']      = BizResources::localize('PVW_XMP');
		$map['Custom']   = BizResources::localize('PVW_CUSTOM');
		$map['Category'] = BizResources::localize('PVW_CATEGORY');
	}
	return $map;
}

function getPhaseTypeMap()
{
	static $map;
	if (!isset($map)) { // not cached yet
		$map = array();
		$map['Selection']    = BizResources::localize('WORKFLOW_PHASE_SELECTION');
		$map['Production']   = BizResources::localize('WORKFLOW_PHASE_PRODUCTION');
		$map['Completed']    = BizResources::localize('WORKFLOW_PHASE_COMPLETED');
		$map['Archived']     = BizResources::localize('WORKFLOW_PHASE_ARCHIVED');
	}
	return $map;
}

function getUiTerms()
{
	static $terms;
	if (!isset($terms)) { // not cached yet
		$terms = array();
		$terms[] = new Term( 'Publication',     BizResources::localize('PUBLICATION',false) );
		$terms[] = new Term( 'Publications',    BizResources::localize('PUBLICATIONS',false) );
		$terms[] = new Term( 'Issue',           BizResources::localize('ISSUE',false) );
		$terms[] = new Term( 'Issues',          BizResources::localize('ISSUES',false) );
		$terms[] = new Term( 'Section',         BizResources::localize('CATEGORY',false) ); // redirection to Category
		$terms[] = new Term( 'Sections',        BizResources::localize('CATEGORIES',false) ); // redirection to Categories
		$terms[] = new Term( 'Category',        BizResources::localize('CATEGORY',false) );
		$terms[] = new Term( 'Categories',      BizResources::localize('CATEGORIES',false) );
		$terms[] = new Term( 'Edition',         BizResources::localize('EDITION',false) );
		$terms[] = new Term( 'Editions',        BizResources::localize('EDITIONS',false) );
		$terms[] = new Term( 'State',           BizResources::localize('STATE',false) );
		$terms[] = new Term( 'States',          BizResources::localize('STATES',false) );
		$terms[] = new Term( 'Phase',           BizResources::localize('WORKFLOW_PHASE',false) );
		
		// >>> BZ#5636
		$terms[] = new Term( 'Current',         BizResources::localize('CURRENT',false) );
		$terms[] = new Term( 'Next',            BizResources::localize('NEXT',false) );
		$terms[] = new Term( 'Previous',        BizResources::localize('PREVIOUS',false) );
		$terms[] = new Term( 'CurrentIssue',    BizResources::localize('CURRENT_SCE_ISSUE',false) );
		$terms[] = new Term( 'NextIssue',       BizResources::localize('NEXT_SCE_ISSUE',false) );
		$terms[] = new Term( 'PreviousIssue',   BizResources::localize('PREVIOUS_SCE_ISSUE',false) );
		// <<<
		
		$terms[] = new Term( 'PublicationDate', BizResources::localize('PUBLICATION_DATE',false) );
		$terms[] = new Term( 'IssueSubject',    BizResources::localize('ISSUE_SUBJECT',false) );
		$terms[] = new Term( 'IssueDescription',BizResources::localize('ISSUE_DESCRIPTION',false) );
		$terms[] = new Term( 'UserName',        BizResources::localize('USR_USER_NAME',false) );

		// BZ#18554
		$terms[] = new Term( 'Inbox',          BizResources::localize('OBJ_INBOX',false) );
	}
	return $terms;
}

/**
 * Returns an array with language codes as keys and English names as values.
 * The keys are language codes in "llCC" format (l = language code, C = county code).
 * (The language code follows ISO-639, and the country code follows ISO-3166.)
 * The values are full English names in "Language[: Country]" format as used in Adobe InCopy documents.
 *
 * Values for which there is no valid key (conform the two ISO standards) are NOT supported by
 * Enterprise, such as 'English: USA Medical'. Since documents are created within Enterprise, 
 * only 'English: USA' will be used instead. At the back-end, admins can install the Medical
 * dictionary, which gets implicitly taken into account by the use of 'English: USA'.
 *
 * The intention of this function is to simplify configuration for 99% of the customers.
 * Values for which there -is- a valid key, but are missing in the list below, are bugs.
 * Same for values that do not match with the actual value at the InCopy document.
 */
function getLanguageCodesTable()
{
	return array( 
		//'' => 'Neutral',
		
		//'' => 'Arabic',
		'arSA' => 'Arabic: Saudi Arabia',
		'arIQ' => 'Arabic: Iraq',
		'arEG' => 'Arabic: Egypt',
		'arLY' => 'Arabic: Libya',
		'arDZ' => 'Arabic: Algeria',
		'arMA' => 'Arabic: Morocco',
		'arTN' => 'Arabic: Tunisia',
		'arOM' => 'Arabic: Oman',
		'arYE' => 'Arabic: Yemen',
		'arSY' => 'Arabic: Syria',
		'arJO' => 'Arabic: Jordan',
		'arLB' => 'Arabic: Lebanon',
		'arKW' => 'Arabic: Kuwait',
		'arAE' => 'Arabic: United Arab Emirates',
		'arBH' => 'Arabic: Bahrain',
		'arQA' => 'Arabic: Qatar',
			
		'afZA' => 'Afrikaans',
		'sqAL' => 'Albanian',
		'euES' => 'Basque',
		'bgBG' => 'Bulgarian',
		'beBY' => 'Byelorussian',
		'caES' => 'Catalan',
			
		//'' => 'Chinese',
		'zhTW' => 'Chinese: Taiwan',
		'zhCN' => 'Chinese: PR China',
		'zhHK' => 'Chinese: Hong Kong',
		'zhSG' => 'Chinese: Singapore',
		
		'hrHR' => 'Croatian',
		'csCZ' => 'Czech',
		'daDK' => 'Danish',
			
		'nlNL' => 'Dutch',
		'nlBE' => 'Dutch: Belgian',
			
		//'' => 'English',
		'enUS' => 'English: USA',
		//'' => 'English: USA Legal',
		//'' => 'English: USA Medical',
		'enGB' => 'English: UK',
		'enAU' => 'English: Australian',
		'enCA' => 'English: Canadian',
		'enNZ' => 'English: New Zealand',
		'enIE' => 'English: Irish',
		'enZA' => 'English: South Africa',
		'enJM' => 'English: Jamaica',
		'enCB' => 'English: Carribean',
			
		//'' => 'US English',
		//'' => 'UK English',
			
		'etEE' => 'Estonian',
		'foFO' => 'Faeroese',
		'faIR' => 'Farsi',
		'fiFI' => 'Finnish',
			
		//'' => 'French',
		'frBE' => 'French: Belgian',
		'frCA' => 'French: Canadian',
		'frCH' => 'French: Swiss',
		'frLU' => 'French: Luxembourg',
		'frFR' => 'France French',
			
		//'' => 'German',
		//'' => 'German: Traditional',
		//'' => 'German: Reformed',
		'deCH' => 'German: Swiss',
		'deAT' => 'German: Austrian',
		'deLU' => 'German: Luxembourg',
		'deLI' => 'German: Liechtenstein',
		'deDE' => 'Germany German',
			
		'elGR' => 'Greek',
		'heIL' => 'Hebrew',
		'huHU' => 'Hungarian',
		'isIS' => 'Icelandic',
		'idID' => 'Indonesian',
			
		'itIT' => 'Italian',
		'itCH' => 'Italian: Swiss',
		
		'jaJP' => 'Japanese',
		//'' => 'Japanese(Not Spell Checked)',
			
		'koKR' => 'Korean',
		//'' => 'Korean: Johab',
			
		'lvLV' => 'Latvian',
		'ltLT' => 'Lithuanian',
			
		//'' => 'Norwegian',
		'nbNO' => 'Norwegian: Bokmal',
		'nnNO' => 'Norwegian: Nynorsk',
			
		'plPL' => 'Polish',
			
		'ptPT' => 'Portuguese',
		'ptBR' => 'Portuguese: Brazilian',
			
		'roRO' => 'Romanian',
		'ruRU' => 'Russian',
		'skSK' => 'Slovak',
		'slSI' => 'Slovenian',
		'srRS' => 'Sorbian',
			
		//'' => 'Spanish',
		'esES' => 'Spanish: Castilian',
		'esMX' => 'Spanish: Mexican',
		//'' => 'Spanish: Modern',
		'esGT' => 'Spanish: Guatemala',
		'esCR' => 'Spanish: Costa Rica',
		'esPA' => 'Spanish: Panama',
		'esDO' => 'Spanish: Dominican Republic',
		'esVE' => 'Spanish: Venezuela',
		'esCO' => 'Spanish: Colombia',
		'esPE' => 'Spanish: Peru',
		'esAR' => 'Spanish: Argentina',
		'esEC' => 'Spanish: Ecuador',
		'esCL' => 'Spanish: Chile',
		'esUY' => 'Spanish: Uruguay',
		'esPY' => 'Spanish: Paraguay',
		'esBO' => 'Spanish: Bolivia',
			
		'svSE' => 'Swedish',
		'thTH' => 'Thai',
		'trTR' => 'Turkish',
		'ukUA' => 'Ukrainian'
	);
}