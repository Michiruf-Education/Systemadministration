<?php
    class Template
    {
    	const LP   = '{{';
    	const RP   = '}}';
    	private $content = '';
    	
    	function __construct($file) {
			if (!file_exists($file)) {
				die("File " . $file . " does not exist!");
			}
			$this->content = file_get_contents($file);
    	}

    	function get() {
    		return $this->content;
    	}

		function assign($var, $val) {
			$this->content = str_replace(self::LP.$var.self::RP, $val, $this->content);
		}
		
		// Add at the end
		function add($var, $val){
			$this->content = str_replace(self::LP.$var.self::RP, $val.self::LP.$var.self::RP, $this->content);
		}
		
		// Add at the begin
		function addOnTop($var, $val){
			$this->content = str_replace(self::LP.$var.self::RP, self::LP.$var.self::RP.$val, $this->content);
		}

		function delete($var) {
			$this->assign($var, '');
		}

		function addTpl($var, $tpl) {
			$tpl = new Template($tpl);
			self::assign($var, $tpl->get());
		}
		
		function assignTpl($var, $tpl, $values) {
			$content = '';
			if (!empty($values) && is_array($values)) {
				foreach ($values as $entry) {
					$template = new Template($tpl);
					foreach ($entry as $tplVar => $tplVal) {
						$template->assign($tplVar, $tplVal);
					}
					$content .= $template->get();
				}
			}
			self::assign($var, $content);
		}
		
		function cleanup() {
			if (strpos($this->content, '{{')) {
				$this->content = preg_replace('/{{\w+}}/i', '', $this->content); 			}
		}
    }
?>