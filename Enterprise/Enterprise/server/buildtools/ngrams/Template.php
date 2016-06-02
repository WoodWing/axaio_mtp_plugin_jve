<?php
	/**
	 * @author Jan "johno" Suchal
	 */
	 
	class Template {
		private $file;
		private $vars;
		
		public function __construct($file) {
			$this->file = $file;
			$this->vars = array();
		}
		
		public function assign($key, $val) {
			$this->vars[$key] = $val;
		}
		
		public function render() {
			extract($this->vars);
			ob_start();
			include($this->file);
			$contents = ob_get_contents();
			ob_end_clean();
			return $contents;
		}
	}
?>