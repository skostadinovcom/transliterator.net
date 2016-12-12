<?php
class template {
	private $root = "";
	private $files = array();
	private $uncompiled_code = array();
	private $_tpldata = array();

	function __construct($root = ".") {
		$this->root = $root;
	}

	function tpl_file($filename_array) {
		if(!is_array($filename_array)) {
			return false;
		}

		while(list($handle, $filename) = each($filename_array)) {
			$this->files[$handle] = $this->mk_name($filename);
		}

		return true;
	}

	function mk_name($filename) {
		if(preg_match('/^[a-z_][^:]/i', $filename) ) {
			$filename = $this->root.'/'.$filename;
		}

		if(!file_exists($filename)) {
			die("<meta charset='utf-8'>Файлът <b>$filename</b> не съществува!");
		}

		return $filename;
	}

	function load($handle) {
		if(!isset($this->files[$handle])) {
			die("<meta charset='utf-8'>Не сте посочили файл за „<b>$handle</b>“, от който да се извлече съдържанието на страницата.");
		}

		$filename = $this->files[$handle];
		$str = implode("", @file($filename));

		if(empty($str)) {
			die("<meta charset='utf-8'>Файлът <b>$filename</b> е празен и няма какво да се покаже в страницата с „<b>$handle</b>“.");
		}

		$this->uncompiled_code[$handle] = $str;

		return true;
	}

	function a_var($vararray) {
		reset($vararray);

		while(list($key, $val) = each($vararray)) {
			$this->_tpldata['.'][0][$key] = $val;
		}

		return true;
	}

	function a_block($blockname, $vararray) {
		if(strstr($blockname, '.')) {
			$blocks = explode('.', $blockname);
			$blockcount = sizeof($blocks) - 1;
			$str = '$this->_tpldata';

			for($i = 0; $i < $blockcount; $i++) {
				$str .= '[\'' . $blocks[$i] . '.\']';

				eval('$lastiteration = isset('.$str.') ? sizeof('.$str.')-1:0;');

				$str .= '[' . $lastiteration . ']';
			}

			$str .= '[\'' . $blocks[$blockcount] . '.\'][] = $vararray;';

			eval($str);
		} else {
			$this->_tpldata[$blockname . '.'][] = $vararray;
		}

		return true;
	}

	function generate_block_varref($namespace, $varname) {
		$namespace = substr($namespace, 0, strlen($namespace) - 1);
		$varref = $this->generate_block_data_ref($namespace, true);
		$varref .= '[\'' . $varname . '\']';
		$varref = '\' . ( ( isset(' . $varref . ') ) ? ' . $varref . ' : \'\' ) . \'';

		return $varref;
	}

	function generate_block_data_ref($blockname, $include_last_iterator) {
		$blocks = explode(".", $blockname);
		$blockcount = sizeof($blocks) - 1;
		$varref = '$this->_tpldata';

		for($i = 0; $i < $blockcount; $i++) {
			$varref .= '[\'' . $blocks[$i] . '.\'][$_' . $blocks[$i] . '_i]';
		}

		$varref .= '[\'' . $blocks[$blockcount] . '.\']';

		if($include_last_iterator) {
			$varref .= '[$_' . $blocks[$blockcount] . '_i]';
		}

		return $varref;
	}

	function compiler($code) {
		$code = str_replace('\\', '\\\\', $code);
		$code = str_replace('\'', '\\\'', $code);

		if (preg_match_all ( '/{include\s+file="([\{\}a-zA-Z0-9_\.\-\/]+)"\s*\}/i', $code, $matches )) {
			for($i = 0; $i < count ( $matches [0] ); $i ++) {
				$file_path = $matches [1] [$i];

				if(preg_match('/\{([a-z0-9]{1,})\}/i',$matches [1] [$i], $m)) {
					if($this->_tpldata['.'][0][$m[1]]) {
						$file_path = $this->_tpldata['.'][0][$m[1]];
					}
				}

				$content = '';

				if(file_exists($this->root .'/'. $file_path)) {
					$content = ((function_exists ('file_get_contents'))) ? file_get_contents ($this->root .'/'. $file_path) : implode ("\n", file($this->root .'/'. $file_path));
				}

				$code = str_replace($matches [0] [$i], $content, $code);
			}
		}

		preg_match_all('#\{(([a-z0-9\-_]+?\.)+?)([a-z0-9\-_]+?)\}#ismU', $code, $varrefs);

		$varcount = sizeof($varrefs[1]);

		for ($i = 0; $i < $varcount; $i++){
			$namespace = $varrefs[1][$i];
			$varname = $varrefs[3][$i];
			$new = $this->generate_block_varref($namespace, $varname);
			$code = str_replace($varrefs[0][$i], $new, $code);
		}

		$code = preg_replace('#\{([a-z0-9\-_]*?)\}#is', '\' . ( ( isset($this->_tpldata[\'.\'][0][\'\1\']) ) ? $this->_tpldata[\'.\'][0][\'\1\'] : \'\' ) . \'', $code);
		$code_lines = explode("\n", $code);
		$block_nesting_level = 0;
		$block_names = array();
		$block_names[0] = ".";
		$line_count = sizeof($code_lines);

		for ($i = 0; $i < $line_count; $i++) {
			$code_lines[$i] = chop($code_lines[$i]);

			if (preg_match('#<!-- BEGIN (.*?) -->#ismU', $code_lines[$i], $m)) {
				$n[0] = $m[0];
				$n[1] = $m[1];

				if ( preg_match('#<!-- END (.*?) -->#ismU', $code_lines[$i], $n) ){
					$block_nesting_level++;
					$block_names[$block_nesting_level] = $m[1];

					if ($block_nesting_level < 2){
						$code_lines[$i] = '$_' . $n[1] . '_count = ( isset($this->_tpldata[\'' . $n[1] . '.\']) ) ?  sizeof($this->_tpldata[\'' . $n[1] . '.\']) : 0;';
						$code_lines[$i] .= "\n" . 'for ($_' . $n[1] . '_i = 0; $_' . $n[1] . '_i < $_' . $n[1] . '_count; $_' . $n[1] . '_i++)';
						$code_lines[$i] .= "\n" . '{';
					} else {
						$namespace = implode('.', $block_names);
						$namespace = substr($namespace, 2);
						$varref = $this->generate_block_data_ref($namespace, false);
						$code_lines[$i] = '$_' . $n[1] . '_count = ( isset(' . $varref . ') ) ? sizeof(' . $varref . ') : 0;';
						$code_lines[$i] .= "\n" . 'for ($_' . $n[1] . '_i = 0; $_' . $n[1] . '_i < $_' . $n[1] . '_count; $_' . $n[1] . '_i++)';
						$code_lines[$i] .= "\n" . '{';
					}

					unset($block_names[$block_nesting_level]);

					$block_nesting_level--;
					$code_lines[$i] .= '} // END ' . $n[1];
					$m[0] = $n[0];
					$m[1] = $n[1];
				} else {
					$block_nesting_level++;
					$block_names[$block_nesting_level] = $m[1];

					if ($block_nesting_level < 2) {
						$code_lines[$i] = '$_' . $m[1] . '_count = ( isset($this->_tpldata[\'' . $m[1] . '.\']) ) ? sizeof($this->_tpldata[\'' . $m[1] . '.\']) : 0;';
						$code_lines[$i] .= "\n" . 'for ($_' . $m[1] . '_i = 0; $_' . $m[1] . '_i < $_' . $m[1] . '_count; $_' . $m[1] . '_i++)';
						$code_lines[$i] .= "\n" . '{';
					} else {
						$namespace = implode('.', $block_names);
						$namespace = substr($namespace, 2);
						$varref = $this->generate_block_data_ref($namespace, false);
						$code_lines[$i] = '$_' . $m[1] . '_count = ( isset(' . $varref . ') ) ? sizeof(' . $varref . ') : 0;';
						$code_lines[$i] .= "\n" . 'for ($_' . $m[1] . '_i = 0; $_' . $m[1] . '_i < $_' . $m[1] . '_count; $_' . $m[1] . '_i++)';
						$code_lines[$i] .= "\n" . '{';
					}
				}
			} else if (preg_match('#<!-- END (.*?) -->#ismU', $code_lines[$i], $m)) {
				unset($block_names[$block_nesting_level]);

				$block_nesting_level--;
				$code_lines[$i] = '} // END ' . $m[1];
			} else {
				$code_lines[$i] = 'echo \'' . $code_lines[$i] . '\' . "\\n";';
			}
		}

		return $code_lines;
	}

	function parse($handle) {
		$this->load($handle);
		$code_lines = $this->compiler($this->uncompiled_code[$handle]);

		for($i=0; $i<count($code_lines); $i++) {
			$code_lines[$i] = $code_lines[$i];
		}

		$code = implode("\n", $code_lines);

		eval($code);
	}
}
?>