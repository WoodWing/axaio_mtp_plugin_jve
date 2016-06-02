<?php
require_once dirname(__FILE__).'/NGramsBookData.class.php';

class NGramsBookReader extends NGramsBookData
{
	private $suppressQuotes;
	private $suppressMarks;
	private $suppressDigits;
	private $ucaseFirst;
	private $quotesReplacement;
	private $markReplacement;
	private $digitReplacement;
	
	public function __construct( $book, $suppressQuotes, $suppressMarks, $suppressDigits, $ucaseFirst )
	{
		parent::__construct( $book->getNGrams(), $book->getTable(), $book->getSeparator() );
		
		$this->suppressQuotes = $suppressQuotes;
		$this->suppressMarks = $suppressMarks;
		$this->suppressDigits = $suppressDigits;
		$this->ucaseFirst = $ucaseFirst;
		
		$this->quotesReplacement = array(
			'"' => '',
			'‚Äù' => '',
			'\'' => '',
			'‚Äú' => '',
			'‚Äô' => '',
		);
	
		$this->markReplacement = array(
			'(' => '',
			')' => '',
			'[' => '',
			']' => '',
			'{' => '',
			'}' => '',
			'<' => '',
			'>' => '',
			'!' => '',
			'?' => '',
			'@' => '',
			'#' => '',
			'$' => '',
			'%' => '',
			'^' => '',
			'&' => '',
			'*' => '',
			'_' => '',
			'-' => '',
			'+' => '',
			'=' => '',
			'\\' => '',
			'/' => '',
			',' => '',
			'.' => '',
			':' => '',
			';' => '',
			'±' => '',
			'§' => '',
			'`' => '',
			'~' => '',
		);
		$this->digitReplacement = array(
			'0' => '',
			'1' => '',
			'2' => '',
			'3' => '',
			'4' => '',
			'5' => '',
			'6' => '',
			'7' => '',
			'8' => '',
			'9' => '',
		);

	}
	
	public function readBook( $length )
	{
		$timeForUpper = true;
		$out = array();
		$ngram = array();
		$arr = $this->table;
		for($i = 0; $i < $this->n-1; $i++) {
			$target = array_rand($arr);
			$ngram[] = $target;
			$arr = &$arr[$target];
		}
		for($i = 0; $i < $length; $i++) {
			$arr = &$this->table;
			for($j = 0; $j < $this->n - 1; $j++) {
				$token = $ngram[$j];
				$arr = &$arr[$token];
			}
			$sum = array_sum($arr);
			$random = rand(0, $sum);
			$counter = 0;
			foreach($arr as $token => $count) {
				$counter += $count;
				if($counter >= $random) {
					$target = $token;
					break;
				}
			}
			$term = array_shift($ngram);
			array_push($ngram, $target);

			// Uppercase first chars (if requested)
			if( $this->ucaseFirst && ($timeForUpper || $this->separator == ' ') ) {
				$term = ucfirst( $term );
			}
			$timeForUpper = ctype_space( $term );

			// Replace special chars (if requested)
			$orgTerm = $term;
			if( $this->suppressQuotes ) {
				$term = strtr( $term, $this->quotesReplacement );
			}
			if( $this->suppressMarks ) {
				$term = strtr( $term, $this->markReplacement );
			}
			if( $this->suppressDigits ) {
				$term = strtr( $term, $this->digitReplacement );
			}
			if( $orgTerm == $term ) {
				$out[] = $term;
			} else { // damaged by replacements, try again
				$length++;
			}

		}
		$ret = implode($this->separator, $out);
		if( $this->separator == '' && strlen($ret) > 2) {
			if( $ret[1] == ' ' ) {
				$ret[0] = ' ';
			}
			if( $ret[strlen($ret)-2] == ' ' ) {
				$ret[strlen($ret)-1] = ' ';
			}
		}
		$ret = trim($ret);
		$ret = ucfirst( $ret );
		return $ret;
	}
}
