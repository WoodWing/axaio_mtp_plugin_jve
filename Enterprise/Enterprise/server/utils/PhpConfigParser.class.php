<?php
/**
 * Utility class to display defines made in PHP config files. <br>
 *
 * It parses PHP files and takes out all defines. <br>
 * The defines can be retrieved in arrays.  See {@link ParseDefines()}. <br>
 *
 * It also determines the actual values of the defines. <br> 
 * So make sure you have included the PHP files before using this parser. <br>
 * Values can be retrieved in array of arrays. See {@link ParseDefineValues()}. <br>
 * 
 * @package Enterprise
 * @subpackage Utils
 * @since v4.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

class PhpConfigParser
{
	/*
	 * Construct the PhpConfigServer
	 *
	 * @param string $filePath Full paths name of PHP file. <br> 
	 */
	public function __construct( $filePath )
	{
		$this->filePath = $filePath;
	}
		
	/*
	 * Returns array of defines made in given PHP file. <br>
	 *
	 * @return array of string Sorted array of unique defines. <br>
	 */ 
	public function ParseDefines()
	{
		$fileLines = file( $this->filePath );
		
		// Remove end of line comments
		foreach ($fileLines as $i => $line) {
			$fileLines[$i] = trim($fileLines[$i]);
			$fileLines[$i] = preg_replace('/\/\/.*$/m', '', $line);
		}
		$configFile = implode('', $fileLines); 
		
		// Remove block comments
		$configFile = preg_replace('/\/\*(.*?)\*\//s', '', $configFile);
			
		$defines = array();
		preg_match_all( '/define[ ]*\([ ]*\'([^\']*)/', $configFile, $defines );
	
		sort( $defines[1] );
		return array_unique( $defines[1] );
	}
	
	/*
	 * Parses PH config file and returns values of given definitions. <br>
	 * 
	 * @param string $defines Array of PHP defines as returned from {@link GetDefines()} <br>
	 * @return array of array of string. Each element (array) has three string values: define, value, comment <br>
	 */
	public function ParseDefineValues()
	{
		$defines = $this->ParseDefines();
		$rows = array();
		foreach( $defines as $define ) {
			$unser_define = @unserialize( constant( $define ) ) ;
			if( is_array( $unser_define ) ) {
				$empty = sizeof($unser_define) == 0 ? 'empty ' : '';
				array_push( $rows, array( $define, '', $empty.'array' ));
				while( ($def = each( $unser_define )) ) {
					if( is_object( $def[ 'value' ] ) ) {
						array_push( $rows, array( '', '', get_class( $def[ 'value' ] ) ));
						$obj = $def[ 'value' ];
						foreach ( $obj as $objProp => $objPropVal) {
							if( !is_null( $objPropVal)) {
								if ( is_array($objPropVal) ) {
									array_push( $rows, array( '', '- '.$objProp.' => '.print_r($objPropVal,true), '' ) );
								} else {
									array_push( $rows, array( '', '- '.$objProp.' => '.$objPropVal, '' ) );
								}
							}
						}
					} elseif( is_array( $def[ 'value' ] ) ) {
						array_push( $rows, array( '', $def['key'].' => '.print_r($def['value'],true) ));
						/*$arr = $def[ 'value' ];
						foreach ( $arr as $arrKey => $arrVal) {
							array_push( $rows, array( '', '- '.$arrKey.' => '.$arrVal, '' ) );
						}*/
					} elseif( is_numeric( $def[ 'key' ] ) ) { // value only -> send value as key
						array_push( $rows, array( '', $def['value'], '' ));
					} else { // key-value pair
						array_push( $rows, array( '', $def['key'].' => '.$def['value'], '' ));
					}
				}
			} else {
				array_push( $rows, array( $define, constant($define), '' ));
			}
		}
		return $rows;
	}
}
