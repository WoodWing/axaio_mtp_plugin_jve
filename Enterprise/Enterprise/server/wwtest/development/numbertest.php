<?php 

require_once dirname(__FILE__).'/../../../config/config.php';
require_once BASEDIR.'/server/utils/NumberUtils.class.php';

echo '<table>';
echo '<tr><td><b>Decimal</b></td><td><b>Roman</b></td><td><b>Alpha</b></td><td><b>Compare</b></td>';
//echo '<td><b>Bin</b></td><td><b>Oct</b></td><td><b>Hex</b></td>';
echo '</tr>';
for( $a = 1; $a <= 5000; $a++ ) {
//for( $a = 17570; $a <= 17670; $a++ ) {
	$ara = NumberUtils::toRomanNumber($a);
	$alp = NumberUtils::toAlphaNumber($a);
	$msg = '';
	if( ( $rpos = strrpos($ara,$alp) ) !== false && $rpos == (strlen($ara)-strlen($alp)) ) {
		$msg = '<font color="red">DANGEROUS!</font>';
	}
	if( ( $rpos = strrpos($alp,$ara) ) !== false && $rpos == (strlen($alp)-strlen($ara)) ) {
		$msg = '<font color="red">DANGEROUS!</font>';
	}
	echo '<tr><td>'.$a.'</td><td>'.$ara.'</td><td>'.$alp.'</td><td>'.$msg.'</td>';
	//echo '<td>'.decbin($a).'</td><td>'.decoct($a).'</td><td>'.dechex($a).'</td>';
	echo '</tr>';
}
echo '</table>';

/*
The 'human readable page number' could have a prefix followed by the real page number.
We always know those two, for example "page: XII" and "12". 
We want to derive the 'page number system' from that, which is "alpha_lower",Ê"alpha_upper", "roman_lower", "roman_upper" or "arabic". See InDesign.

The test page above shows there is only one potential conversion problem; number 243.
This problem can be avoided by an conversion algorithm, which tries roman at first!
For example like this code fragment does:
*/
/*
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/utils/NumberUtils.class.php';

header('Content-type: text/plain');
print_r( parsePageNumber( "Page: XII", "12" ));
print_r( parsePageNumber( "Page: xiii", "13" ));
print_r( parsePageNumber( "Page: 5", "5" ));
print_r( parsePageNumber( "Page: AA", "27" ));
print_r( parsePageNumber( "Page: ab", "28" ));


class PageNumInfo
{
	public function __construct( $numSystem, $realNum, $displayNum, $pagePrefix )
	{
		$this->NumberingSystem = $numSystem;
		$this->RealPageNumber = $realNum;
		$this->DisplayPageNumber = $displayNum;
		$this->PagePrefix = $pagePrefix;
	}
}

function parsePageNumber( $displayNum, $realNum )
{
	if( ( $numPos = isAtFarEndOf( $displayNum, $realNum ) ) !== false ) {
		$pagePrefix = substr( $displayNum, 0, $numPos );
		return new PageNumInfo( 'arabic', $realNum, $displayNum, $pagePrefix );
	}
	$romanNum = NumberUtils::toRomanNumber( $realNum );	
	if( ( $numPos = isAtFarEndOf( $displayNum, $romanNum ) ) !== false ) {
		$pagePrefix = substr( $displayNum, 0, $numPos );
		return new PageNumInfo( 'roman_upper', $realNum, $displayNum, $pagePrefixÊ);
	}
	if( ( $numPos = isAtFarEndOf( $displayNum, strtolower($romanNum) ) ) !== false ) {
		$pagePrefix = substr( $displayNum, 0, $numPos );
		return new PageNumInfo( 'roman_lower', $realNum, $displayNum, $pagePrefixÊ);
	}
	$alphaNum = NumberUtils::toAlphaNumber( $realNum );	
	if( ( $numPos = isAtFarEndOf( $displayNum, $alphaNum ) ) !== false ) {
		$pagePrefix = substr( $displayNum, 0, $numPos );
		return new PageNumInfo( 'alpha_upper', $realNum, $displayNum, $pagePrefixÊ);
	}
	if( ( $numPos = isAtFarEndOf( $displayNum, strtolower($alphaNum) ) ) !== false ) {
		$pagePrefix = substr( $displayNum, 0, $numPos );
		return new PageNumInfo( 'alpha_lower', $realNum, $displayNum, $pagePrefixÊ);
	}
	return null;
}

function isAtFarEndOf( $haystack, $needle )
{
	if( empty($haystack) || empty($needle) ) {
		return false;
	}
	$right = substr( $haystack, -strlen($needle) );
	if( $right != $needle ) {
		return false;
	}
	return strlen( $haystack ) - strlen( $needle );
}
*/
?>