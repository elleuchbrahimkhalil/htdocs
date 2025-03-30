<?php
class JsonToTemplateConverter
{
    const JCISPACER = '<br>';
    const INDENT_STEP = '  ';
    const STUB_OBJECT_NAME = '';

    const HTMl_TABLE_START = '<table border="1">';
    const HTMl_TABLE_END = '</table>';
    const HTMl_TABLE_LINE_START = '<tr valign="top">';
    const HTMl_TABLE_CELL_START = '<td>';
    const HTMl_TABLE_LINE_END = '</tr>';
    const HTMl_TABLE_CELL_END = '</td>';

    private $basenode;
    private $jsonArr;
    private $isJson = FALSE;
	private $basenodeSelectedJSON = NULL;

    private $html_table_start = "";
    private $html_table_end = "";
    private $html_table_line_start = "";
    private $html_table_cell_start = ""; 
    private $html_table_line_end = ""; 
    private $html_table_cell_end = ""; 


    public function __construct($jsonArr = '', $basenode = '') {
		$this->basenode = $basenode;
		$this->jsonArr = $jsonArr;
		if ($basenode!='') {
			# reduce JSON: start from basenode
			$this->reduceJsonByBasenode($this->jsonArr, $this->basenode);
			$this->jsonArr = $this->getBasenodeSelectedJSON();
		}
		if (!is_null($this->jsonArr)) {
			$this->isJson = TRUE;
		}
		
		if (1==2) {  # in developement
			$this->html_table_start = self::HTMl_TABLE_START;
			$this->html_table_end = self::HTMl_TABLE_END;
			$this->html_table_line_start = self::HTMl_TABLE_LINE_START;
			$this->html_table_cell_start = self::HTMl_TABLE_CELL_START;
			$this->html_table_line_end = self::HTMl_TABLE_LINE_END;
			$this->html_table_cell_end = self::HTMl_TABLE_CELL_END;
		}
    }

    public function getTemplate() {
		if (!$this->isJson) {
			return "Invalid-JSON";
		}
		#return "Basenode:".$this->basenode."\nJSON: ".json_encode($this->jsonArr);
		$lines = $this->getLines();
		return implode(PHP_EOL, $lines);
    }

    /**
     * Return array of lines
     *
     * @param $jsonString string Expects raw json string
     * @param int $level
     * @param null $key
     *
     * @return array|bool
     */
    public function getLines($level = 0, $key = null) {
        $jsonStructure = $this->jsonArr;
        if (is_array($jsonStructure)){
            return $this->getLinesForArray($jsonStructure);
        } elseif (is_object($jsonStructure)){
            return $this->getLinesForObject($jsonStructure);
        }
        return false;
    }

	public function reduceJsonByBasenode($json, $seljsonnode) {
		$seljsonnodeArr = explode(".", $seljsonnode);
		$jsonStr = json_encode($json);
		$jsonArr1 = json_decode($jsonStr, TRUE);
		$curnode = array_shift($seljsonnodeArr);
		$restnode = join(".", $seljsonnodeArr);
		if (empty($restnode)) {
			$this->basenodeSelectedJSON[] = $jsonArr1[$curnode] ?? null;
			#$this->basenodeSelectedJSON[$curnode] = $jsonArr1[$curnode];
		} else {
			$this->reduceJsonByBasenode($jsonArr1[$curnode], $restnode);	
		}		
	}
	
	public function getBasenodeSelectedJSON() {
		$jsonStr = json_encode($this->basenodeSelectedJSON);
		$jsonArr1 = json_decode($jsonStr);
		return $jsonArr1;
	}

	private function getJSONvalue($key) {
		$arrayIn = json_decode(json_encode($this->jsonArr), TRUE);
		$array = array();
		if ($this->isSequentiallyIndexedArray($arrayIn)) {
			$array = $arrayIn[0] ?? null;
		} else {
			$array = $arrayIn;
		}
		$keys = explode('.', $key);
		foreach ($keys as $k) {
			if (!isset($array[$k])) {
				return null;
			}
			$array = $array[$k];
		}
		return $array;
	}	
	
	private function isSequentiallyIndexedArray($array) {
		return array_keys($array) === range(0, count($array) - 1);
	}
	
	private function is_html($string) {
		if($string != strip_tags($string)){
			return TRUE; 
		}
		return FALSE;
		#return preg_match("/<[^<]+>/", $string, $m) != 0;
	}

    private function checkFromattingJSONvalue($value) {
		if (empty($value)) {
			return "";
		}
		$chk_strtotime = strtotime($value) ?? "";
		if (!empty($chk_strtotime)) {
			$ht =":datetime,d.m.Y, H:i:s,0";
			return $ht;
		}
		if ($this->is_html($value)) {
			$ht =":html";
			return $ht;
		}
		#return "--".htmlentities(substr($value, 0 ,10));
		return "";
	}
	
    private function getLineForObjectProp($propKey, $level = 0, $key = null)  {
		$keytmp = substr($key, 0, -1);
		if (($propKey!="") 
			&& (strval($keytmp) === strval(intval($keytmp) ))) {
			$propKeyValueLiteral = $propKey;
		} else {
			$propKeyValueLiteral = "{$key}$propKey";
		}
        $propKeyEsc = addcslashes($propKey, "{}");
		$jv = $this->getJSONvalue($propKeyValueLiteral);
		$formattingextra = $this->checkFromattingJSONvalue($jv);

        return str_repeat(self::INDENT_STEP, $level) . "$propKeyEsc = {".$propKeyValueLiteral."$formattingextra}". self::JCISPACER;
    }
	
    private function getLinesForObject(\StdClass $object, $level = 0, $key = null) {
		#echo "level: ".$level." / key: ".$key." \n";
		$keytmp = substr($key, 0, -1);
		if (is_numeric($keytmp)) {
			$key = "";
		}
        $lines = [];

        if ($level === 0 && !$key){
            $key = '';
        } elseif (!$key) {
            $key = self::STUB_OBJECT_NAME;
        }
		
        foreach ($object as $propKey => $propValue){
            #$lines[] = str_repeat(self::INDENT_STEP, $level);
			if (is_array($propValue)){
                $lines[] = str_repeat(self::INDENT_STEP, $level + 1) . "$propKey:".self::JCISPACER;
				$addkey = $key;
                $lines = array_merge($lines, $this->getLinesForArray($propValue, $level + 2, $addkey.$propKey));
            } elseif (is_object($propValue)){
                $lines[] = str_repeat(self::INDENT_STEP, $level + 1) . "{subloop:{$key}$propKey:-1}";
				$addkey = $key;
                $lines = array_merge($lines, $this->getLinesForObject($propValue, $level + 2, $addkey.$propKey.".", substr($propKey, 0, -1)));
			#	$lines[] = str_repeat(self::INDENT_STEP, $level) ."BJS".print_r($propValue,true);
				$lines[] = str_repeat(self::INDENT_STEP, $level) . "{/subloop:{$key}$propKey}";
            } else {
				$addkey = $key;
                $lines[] = $this->getLineForObjectProp($propKey, $level + 2, $addkey);
            }
        }
        return $lines;
    }

    private function getLinesForArray(array $array, $level = 0, $key = null, $itemKey = null) {
        $lines = [];
		$isalreadyarr = FALSE;
        if (!$key && $level === 0){
            $key = '';
            $itemKey = '';
			$isalreadyarr = TRUE;
        }

        if (!$key){
			if ($isalreadyarr) {
				$key = '';
			} else {
				$key = '' . $level . '';
			}
        }

        if (!$itemKey){
			$istlistwithval = FALSE;
			$noarrl = "-1";
			foreach ($array as $keya => $valuea) {
				if (!is_array($valuea) && !is_object($valuea)){
					$istlistwithval = TRUE;
				}
			}

			if ($istlistwithval) {
				$noarrl = count($array);
				$delim = '} {';
				for ($i = 0; $i < $noarrl; $i++) {
					if ($key!="") {
						if (strval($key) === strval(intval($key))) {
							$itemKey .= $i.$delim;
						} else {
							$itemKey .= "{$key}".'.'.$i.$delim;
						}
					}
				}				
				$itemKey = substr($itemKey, 0, (-1*(strlen($delim))));
			} else {
				if ($key==="") {
					$itemKey = '';
				} else {
					$itemKey = "{$key}".'.';
				}
			}
        }

        // @TODO now we're assuming that array's values are homogeneous. Probably consider opposite.
		if (!$isalreadyarr) {
			$lines[] = str_repeat(self::INDENT_STEP, $level) . "{subloop-array:$key:".$noarrl."}".self::JCISPACER;
		}
		if (isset($array[0]) && is_object($array[0])){
			$accumObj = $this->getAccumObjectOfArray($array);
			$lines = array_merge($lines, $this->getLinesForObject($accumObj, $level + 1, $itemKey));
		} elseif (isset($array[0]) && is_array($array[0])){
			$lines = array_merge($lines, $this->getLinesForArray($array[0], $level + 1, $itemKey));
		} else {
			$lines[] = str_repeat(self::INDENT_STEP, $level + 1) . $this->html_table_line_start. "{".$itemKey."}".$this->html_table_line_end;#.self::JCISPACER;
		}
		if (!$isalreadyarr) {
			$lines[] = str_repeat(self::INDENT_STEP, $level) . "{/subloop-array:$key}" . self::JCISPACER;
		}

        return $lines;

    }

    private function getAccumObjectOfArray($array)  {
        $accumObj = new \StdClass();
        foreach ($array as $obj) {
            $accumObj = (object)array_merge((array)$accumObj, (array)$obj);
        }

        return $accumObj;
    }
}
