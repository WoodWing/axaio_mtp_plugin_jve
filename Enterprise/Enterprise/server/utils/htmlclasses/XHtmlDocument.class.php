<?php

/**
 * @package 	Enterprise
 * @subpackage 	Utils
 * @since 		v6.5
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 * 
 * XHTML classes used to manage flexible (X)HTML pages.
 * It allow you to add input elements to forms dynamically.
 * As a whole, typicaly an useful utility to build admin screen.
 * Each class wraps and manages XML DOM objects to preserve well-formedness.
 * 
	Example usage:
		$doc = new Utils_XHtmlDocument();
		$form = $doc->addForm( 'myform', 'myform' );
		$field1 = $form->addField( 'string', 'field1', 'field1' );
		$field1->setValues( array('hello') );
		$field2 = $form->addField( 'multiline', 'field2', 'field2' );
		$field2->setValues( array('world') );
		$field2->setWidth( 200 );
		$field3 = $form->addField( 'bool', 'field3', 'field3' );
		$field3->setValues( array(true) );
		$field4 = $form->addField( 'list', 'field4', 'field4' );
		$field4->setOptions( array( '1' => 'rood', '2' => 'oranje', '3' => 'groen' ) );
		$field4->setValues( array('2') );
		//echo "<pre>".htmlentities( $field4->toString() )."</pre>";
		echo $form->toString();
*/

/**
 * XHTML document. Manages XHTML forms.
 */
class Utils_XHtmlDocument
{
	protected $DOM; // DOMDocument
	
	/**
	 * Creates an XHTML document with body element.
	 */
	public function __construct()
	{
		$this->DOM = new DOMDocument();
		$body = $this->DOM->createElement( 'body' );
		$this->DOM->appendChild( $body );
	}

	/**
	 * Adds form element to the HTML document.
	 *
	 * @param string $id The id attribute of the form
	 * @param string $name The name attribute of the form
	 * @return Utils_XHtmlForm
	 */
	public function addForm( $id, $name )
	{
		$form = new Utils_XHtmlForm( $this, $id, $name );
		$this->DOM->documentElement->appendChild( $form->getDOM() );
		return $form;
	}
	
	public function getDOM() { return $this->DOM; }
}

/**
 * Basic behavior implementation for all kind of XHTML elements.
 */
class Utils_XHtmlElement
{
	protected $Doc; // Utils_XHtmlDocument (parent)
	protected $DOM; // DOMElement (this)
	protected $Styles = array();
	
	/**
	 * Creates new XHTML element.
	 *
	 * @param Utils_XHtmlDocument $doc
	 * @param string $nodeName XHTML element name, such as 'input', 'textarea', etc
	 */
	public function __construct( Utils_XHtmlDocument $doc, $nodeName )
	{
		$this->Doc = $doc;
		$this->DOM = $doc->getDOM()->createElement( $nodeName );
	}
	
	/**
	 * Streames the XHTML element into a string.
	 *
	 * @return string
	 */
	public function toString()
	{
		return $this->Doc->getDOM()->saveXML( $this->DOM );
	}

	/**
	 * Set the width of the XHTML element.
	 *
	 * @param integer $width Number of pixels.
	 */
	public function setWidth( $width )
	{
		$this->Styles['width'] = 'width:'.$width.'px';
		$this->DOM->setAttribute( 'style', implode( ';', $this->Styles ).';' );
	}
	
	/**
	 * Makes XHTML read-only (or back to editable again). Default is editable.
	 *
	 * @param bool $readOnly Whether or not to make it read-only.
	 */
	public function setReadOnly( $readOnly )
	{
		if( $readOnly ) {
			$this->DOM->setAttribute( 'readonly', 'readonly' );
		} else {
			$this->DOM->removeAttribute( 'readonly' );
		}
	}

	/**
	 * Sets the onchange event. Typically used to run a javascript.
	 *
	 * @param string $command
	 */
	public function setOnChange( $command )
	{
		$this->DOM->setAttribute( 'onchange', $command );
	}
	
	public function getDOM() { return $this->DOM; }
}

/**
 * XHTML form element. Container to add input fields.
 */
class Utils_XHtmlForm extends Utils_XHtmlElement
{
	/**
	 * Creates new XHTML form element and adds it to the document.
	 *
	 * @param Utils_XHtmlDocument $doc
	 * @param string $id The id attribute of the form
	 * @param string $name The name attribute of the form
	 */
	public function __construct( Utils_XHtmlDocument $doc, $id, $name )
	{
		parent::__construct( $doc, 'form' );
		$this->DOM->setAttribute( 'id', $id );
		$this->DOM->setAttribute( 'name', $name );
	}
	
	/**
	 * Adds an field element to the form.
	 *
	 * @param string $propType Kind of input element, such as 'input', 'textarea', etc
	 * @param string $id The id attribute of the field
	 * @param string $name The name attribute of the field
	 * @return Utils_XHtmlField of any kind.
	 */
	public function addField( $propType, $id, $name )
	{
		$field = Utils_XHtmlFieldFactory::create( $this->Doc, $propType, $id, $name );
		if( $field && $field->DOM ) {
			$this->DOM->appendChild( $field->DOM );
		}
		return $field;
	}
}

/**
 * User input form field.
 */
abstract class Utils_XHtmlField extends Utils_XHtmlElement
{
	public function __construct( Utils_XHtmlDocument $doc, $nodeName, $id, $name )
	{
		parent::__construct( $doc, $nodeName );
		$this->DOM->setAttribute( 'id', $id );
		$this->DOM->setAttribute( 'name', $name );
	}
	
	abstract function setValues( array $values );
	public function setMaxLength( $max ) { $max=$max; } // typically for strings
}

/**
 * Dummy placeholder to group fields. Caller should draw a field separator (e.g. hor line).
 * This class just tracks the display name of the separator (if any provided).
 */
class Utils_XHtmlSeparatorField extends Utils_XHtmlField
{
	public function __construct( Utils_XHtmlDocument $doc )
	{
		parent::__construct( $doc, 'div', '', '' );
	}
	
	function setValues( array $values )
	{
		$values = $values; // keep code analyzer happy
	}
}

/**
 * User input form field to edit string value.
 */
class Utils_XHtmlStringField extends Utils_XHtmlField
{
	public function __construct( Utils_XHtmlDocument $doc, $id, $name )
	{
		parent::__construct( $doc, 'input', $id, $name );
		$this->DOM->setAttribute( 'type', 'text' );
	}
	
	public function setValues( array $values )
	{
		if( is_array( $values ) ) { // support for multistring
			$value = implode( ', ', $values ); // comma separation
		} else { // normal string
			$value = $values[0];
		}
		$this->DOM->setAttribute( 'value', $value );
	}
	
	public function setMaxLength( $max )
	{
		if( is_numeric($max) && $max > 0 ) {
			$this->DOM->setAttribute( 'maxlength', intval($max) );
		}
	}
}

/**
 * User input form field to edit password string value.
 */
class Utils_XHtmlPasswordField extends Utils_XHtmlStringField
{
	public function __construct( Utils_XHtmlDocument $doc, $id, $name )
	{
		Utils_XHtmlField::__construct( $doc, 'input', $id, $name );
		$this->DOM->setAttribute( 'type', 'password' );
		$this->DOM->setAttribute( 'autocomplete', 'off' );
	}
}

/**
 * User input form field to edit text (multiple lines).
 */
class Utils_XHtmlTextField extends Utils_XHtmlField
{
	public function __construct( Utils_XHtmlDocument $doc, $id, $name )
	{
		parent::__construct( $doc, 'textarea', $id, $name );
		// An empty <textarea/> causes troubles at web browsers, so we add an
		// empty text child to force open+close tags <textarea>...</textarea> 
		$this->setValues( array('') );
		
		// Firefix does not show vertical scrollbar for small textarea widgets.
		// So here we set a minimum height that seems to trigger showing the bar.
		$this->Styles['height'] = 'height:65px';
		$this->DOM->setAttribute( 'style', implode( ';', $this->Styles ).';' );
	}

	public function setValues( array $values )
	{
		$text = $this->Doc->getDOM()->createTextNode( $values[0] );
		if( $this->DOM->hasChildNodes() ) {
			$this->DOM->replaceChild( $text, $this->DOM->firstChild );
		} else {
			$this->DOM->appendChild( $text );
		}
	}
}

/**
 * User input form field to tag an option (checkbox).
 */
class Utils_XHtmlCheckboxField extends Utils_XHtmlField
{
	public function __construct( Utils_XHtmlDocument $doc, $id, $name )
	{
		parent::__construct( $doc, 'input', $id, $name );
		$this->DOM->setAttribute( 'type', 'checkbox' );
	}
	
	public function setValues( array $values )
	{
		if( $values[0] ) {
			$this->DOM->setAttribute( 'checked', 'checked' );
		}
	}

	/**
	 * Makes XHTML checkbox readonly (or back to editable again). Default is editable.
	 *
	 * @param bool $readOnly Whether or not to make it read-only.
	 */
	public function setReadOnly( $readOnly )
	{
		if( $readOnly ) {
			$this->DOM->setAttribute( 'disabled', 'disabled' );
		} else {
			$this->DOM->removeAttribute( 'disabled' );
		}
	}
}

/**
 * User input form field to make a single selection (combobox).
 */
class Utils_XHtmlComboboxField extends Utils_XHtmlField
{
	protected $Options = array();
	
	public function __construct( Utils_XHtmlDocument $doc, $id, $name, $multiSelect )
	{
		parent::__construct( $doc, 'select', $id, $name );
		if( $multiSelect ) {
			$this->DOM->setAttribute( 'multiple', 'multiple' );
		}
	}

	/**
	 * Sets list of options to let user choose from.
	 * Keys are used as internal ids and Values are shown to user.
	 * The options array can contain only value text as 'value' => 'text to display'
	 * or can can be created as 'optgroup label' => array('value' => 'text to display') 
	 * to support optgroups
	 *
	 * @param array $options Key-Value pairs.
	 */
	public function setOptions( array $options )
	{
		$this->Options = $options;
	}
	
	/**
	 * Pre-select a value from list of options (see setOptions).
	 *
	 * @param array $values First entry it used to pre-select. 
	 */
	public function setValues( array $values )
	{
		$domDoc = $this->Doc->getDOM();
        foreach( $this->Options as $optionId => $optionVal ) {
        	// If the value is an array, then create a optgroup with the id as label
        	if(is_array($optionVal)) {
        		$optgroup = $domDoc->createElement( 'optgroup' );
				$this->DOM->appendChild( $optgroup );
				$optgroup->setAttribute( 'label', $optionId );
        		foreach ( $optionVal as $optionIdInGroup => $optionValInGroup ) {
        			$option = $this->createOption( $domDoc, $optionIdInGroup, $optionValInGroup, $values );
        			$optgroup->appendChild( $option );	
        		}
        		$this->DOM->appendChild( $optgroup );
        	} else {
				$option = $this->createOption( $domDoc, $optionId, $optionVal, $values );
				$this->DOM->appendChild( $option );
        	}
        }
	}
	
	/**
	 * Creates and returns a select option and set the values. 
	 *
	 * @param DOMElement $domDoc The parent element to create a element
	 * @param mixed $optionId The value for the option
	 * @param mixed $optionVal The text to display
	 * @param array $values Entries to set a option selected
	 * @return DOMElement option
	 */
	private function createOption( $domDoc, $optionId, $optionVal, $values )
	{
		$option = $domDoc->createElement( 'option' );
		$option->setAttribute( 'id', $optionId );
		$option->setAttribute( 'value', $optionId );
        if( in_array( $optionId, $values ) ) {
			$option->setAttribute( 'selected', 'selected' );
        }
		$optionTxt = $domDoc->createTextNode( $optionVal );
		$option->appendChild( $optionTxt );
		
		return $option;
	}

	/**
	 * Makes XHTML combobox readonly (or back to editable again). Default is editable.
	 *
	 * @param bool $readOnly Whether or not to make it read-only.
	 */
	public function setReadOnly( $readOnly )
	{
		if( $readOnly ) {
			$this->DOM->setAttribute( 'disabled', 'disabled' );
		} else {
			$this->DOM->removeAttribute( 'disabled' );
		}
	}
}

/**
 * User input form field to pick a date and time.
 * @todo The HtmlDateTimeField class is used as temporary hack. Please remove.
 */
class Utils_XHtmlDatetimeField extends Utils_XHtmlField
{
	protected $Field; // HtmlDateTimeField -> temp hack!
	
	public function __construct( Utils_XHtmlDocument $doc, $id, $name )
	{
		$id = $id; $doc = $doc; // keep analyzer happy
		require_once BASEDIR.'/server/utils/htmlclasses/HtmlDateTimeField.class.php';
		$this->Field = new HtmlDateTimeField( null, $name, false, true, false );
	}

	public function setValues( array $values )
	{
		$this->Field->setValue( $values[0] );
	}

	public function setReadOnly( $readOnly ) // overrule parent -> temp hack!
	{
		$this->Field->ReadOnly = $readOnly;
	}

	public function toString() // overrule parent -> temp hack!
	{
		return $this->Field->drawBody();
	}
	
	public function setOnChange( $command ) // overrule parent -> temp hack!
	{
		$this->Field->OnChange = $command;
	}

	public function setWidth( $width ) // overrule parent -> temp hack!
	{
		$width = $width; // ignore
	}
}

/**
 * User input form field to pick a date.
 * @todo The HtmlDateField class is used as temporary hack. Please remove.
 */
class Utils_XHtmlDateField extends Utils_XHtmlDatetimeField
{
	public function __construct( Utils_XHtmlDocument $doc, $id, $name )
	{
		$id = $id; $doc = $doc; // keep analyzer happy
		require_once BASEDIR.'/server/utils/htmlclasses/HtmlDateField.class.php';
		$this->Field = new HtmlDateField( null, $name, false, true, false );
	}
}

/**
 * Factory that creates all kind of Utils_XHtmlField classes.
 */
class Utils_XHtmlFieldFactory
{
	/**
	 * Creates Utils_XHtmlField classes depending on given property type.
	 *
	 * @param Utils_XHtmlDocument $doc
	 * @param string $propType Determines what class is created. See WSDL
	 * @param string $id The id attribute of the input field
	 * @param string $name The name attribute of the input field
	 * @return XHhtmlField flavors
	 */
	static public function create( Utils_XHtmlDocument $doc, $propType, $id, $name )
	{
		switch( $propType )
		{
			case 'int':
			case 'double':
			case 'multistring':
				// good enough to fall-back at string for now...
			case 'string':
				$field = new Utils_XHtmlStringField( $doc, $id, $name );
				break;
			case 'multiline':
				$field = new Utils_XHtmlTextField( $doc, $id, $name );
				break;
			case 'bool':
				$field = new Utils_XHtmlCheckboxField( $doc, $id, $name );
				break;
			case 'list':
				$field = new Utils_XHtmlComboboxField( $doc, $id, $name, false );
				break;
			case 'multilist':
				$field = new Utils_XHtmlComboboxField( $doc, $id, $name.'[]', true );
				break;
			case 'datetime':
				$field = new Utils_XHtmlDatetimeField( $doc, $id, $name );
				break;
			case 'date':
				$field = new Utils_XHtmlDateField( $doc, $id, $name );
				break;
			// TODO: Add new options below to the WSDL once $admObj->ExtraMetaData is made public!!!
			case 'password':
				$field = new Utils_XHtmlPasswordField( $doc, $id, $name );
				break;
			/* FUTURE: choose Color Picker widget!
			case 'color': // -> should be reflected at WSDL too
				break; */
			/* FUTURE: choose Language Combo widget!
			case 'language': // -> should be reflected at WSDL too
				break; */
			case 'separator':
				$field = new Utils_XHtmlSeparatorField( $doc, $id, $name );
				break;
			default:
				$field = null;
				LogHandler::Log( 'XHtmlFieldFactory', 'ERROR', 'Unknown field type request: '.$propType );
				break;
		}
		return $field;
	}
}
