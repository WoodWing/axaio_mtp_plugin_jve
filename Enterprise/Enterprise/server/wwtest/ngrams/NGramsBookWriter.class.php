<?php
require_once dirname(__FILE__).'/NGramsBookData.class.php';

class NGramsBookWriter extends NGramsBookData
{
	private $type;
	
	public function __construct( $type, $nGrams )
	{
		$this->type = $type;
		if( $this->type == 'word' ) { // word based
			parent::__construct( $nGrams, array(), ' ' ); // space
		} else { // char based
			parent::__construct( $nGrams, array(), '' ); // empty
		}		
	}

	public function writeBook( $langCode, $bookTitle, $rawText, &$message )
	{
		/*$rawText = str_replace( "\t", ' ', $rawText );
		$rawText = str_replace( "\n", ' ', $rawText );
		$rawText = str_replace( "\r", ' ', $rawText );
		$rawText = str_replace( '  ', ' ', $rawText );*/
		$rawText = preg_replace( '/\s+/', ' ', $rawText );
		
		if( empty($this->separator) ) { // char based
			$this->learn( $this->mbStringToArray($rawText) );
		} else { // word based
			$this->learn( explode($this->separator, $rawText) );
		}
		$bookContents = '<?php'."\n".'require_once BASEDIR.\'/server/wwtest/ngrams/NGramsBookData.class.php\';'."\n\n";
		$bookContents .= 'class NGramsBook extends NGramsBookData'."\n{\n";
		$bookContents .= "\t".'public function __construct()'."\n\t{\n";
		$bookContents .= "\t\t".'$table = '."\n".var_export( $this->table, true ).';'."\n";
		$bookContents .= "\t\t".'parent::__construct( '.$this->n.', $table, \''.$this->separator.'\' );'."\n\t}\n}";
		
		$bookFilePath = BASEDIR.'/server/wwtest/ngrams/books/'."$langCode-$bookTitle-$this->type-$this->n".'gram.php';
		if( file_put_contents( $bookFilePath, $bookContents ) === false ) {
			$message = 'Could not write book to '.$bookFilePath;
			return false;
		} else {
			$message = 'Book successfully written to '.$bookFilePath;
			return true;
		}
	}
	
	private function mbStringToArray( $string )
	{
		$strlen = mb_strlen($string);
		while ($strlen) {
			$array[] = mb_substr($string,0,1,"UTF-8");
			$string = mb_substr($string,1,$strlen,"UTF-8");
			$strlen = mb_strlen($string);
		}
		return $array;
	}

	private function learn( $tokens )
	{
		$length = count($tokens) - $this->n;
		for($i = 0; $i <= $length; $i++) {
			/*if(!($i % 500)) {
				echo "$i/$length";
				flush();
			}*/
			$ngram = array(); 
			for($j = 0; $j < $this->n; $j++) {
				$ngram[] = $tokens[$i+$j];
			} 
			$this->addNGram($ngram);
			if($i > 50000) break; 
		}
	}
	
	private function addNGram( $ngram )
	{
		$arr = &$this->table; 
		$lastToken = array_pop($ngram); 
		foreach($ngram as $token) { 
			$arr = &$arr[$token]; 
		} 
		if(!isset($arr[$lastToken])) { 
			$arr[$lastToken] = 0; 
		}
		$arr[$lastToken]++; 
	}
}