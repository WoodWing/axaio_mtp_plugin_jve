<?php
class NGramsBookData
{
	protected $n;
	protected $table;
	protected $separator;
	
	public function __construct( $n, $table, $separator )
	{
		$this->n = $n;
		$this->table = &$table;
		$this->separator = $separator;
	}
	
	public function getNGrams() { return $this->n; }
	public function getTable() { return $this->table; }
	public function getSeparator() { return $this->separator; }
}
