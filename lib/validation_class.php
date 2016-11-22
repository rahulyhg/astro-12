<?php
class Validate_fields {
	var $fields = array();
	var $messages = array();
	var $check_4html = false;
	var $language;
	var $time_stamp;
	var $month;
	var $day;
	var $year;

	function Validate_fields() {
		$this->language = "us";
		$this->create_msg();
	}

	function validation() {
		$status = 0;
		foreach ($this->fields as $key => $val) {
			$name = $val['name'];
			$length = $val['length'];
			$required = $val['required'];
			$num_decimals = $val['decimals'];
			$ver = $val['version'];
			switch ($val['type']) {
				case "email":
				if (!$this->check_email($name, $key, $required)) {
					$status++;
				}
				break;
				case "number":
				if (!$this->check_num_val($name, $key, $length, $required)) {
					$status++;
				}
				break;
				case "decimal":
				if (!$this->check_decimal($name, $key, $num_decimals, $required)) {
					$status++;
				}
				break;
				case "date":
				if (!$this->check_date($name, $key, $ver, $required)) {
					$status++;
				}
				break;
				case "url":
				if (!$this->check_url($name, $key, $required)) {
					$status++;
				}
				break;
				case "text":
				if (!$this->check_text($name, $key, $length, $required)) {
					$status++;
				}
				break;
			}
			if ($this->check_4html) {
				if (!$this->check_html_tags($name, $key)) {
					$status++;
				}
			}
		}
		if ($status == 0) {
			return true;
		} else {
			$this->messages[] = $this->error_text(0);
			return false;
		}
	}

	function add_text_field($name, $val, $type = "text", $required = true, $length = 0) {
		$this->fields[$name]['name'] = $val;
		$this->fields[$name]['type'] = $type;
		$this->fields[$name]['required'] = $required;
		$this->fields[$name]['length'] = $length;
	}

	function add_num_field($name, $val, $type = "number", $required = true, $decimals = 0, $length = 0) {
		$this->fields[$name]['name'] = $val;
		$this->fields[$name]['type'] = $type;
		$this->fields[$name]['required'] = $required;
		$this->fields[$name]['decimals'] = $decimals;
		$this->fields[$name]['length'] = $length;
	}

	function add_link_field($name, $val, $type = "email", $required = true) {
		$this->fields[$name]['name'] = $val;
		$this->fields[$name]['type'] = $type;
		$this->fields[$name]['required'] = $required;
	}

	function add_date_field($name, $val, $type = "date", $version = "us", $required = true) {
		$this->fields[$name]['name'] = $val;
		$this->fields[$name]['type'] = $type;
		$this->fields[$name]['version'] = $version;
		$this->fields[$name]['required'] = $required;
	}

	function check_url($url_val, $field, $req = true) {
		if ($url_val == "") {
			if ($req) {
				$this->messages[] = $this->error_text(1, $field);
				return false;
			} else {
				return true;
			}
		} else {
			if ($req)
			{
			  $url_pattern = "http\:\/\/[[:alnum:]\-\.]+(\.[[:alpha:]]{2,4})+";
			  $url_pattern .= "(\/[\w\-]+)*"; // folders like /val_1/45/
			  $url_pattern .= "((\/[\w\-\.]+\.[[:alnum:]]{2,4})?"; // filename like index.html
			  $url_pattern .= "|"; // end with filename or ?
			  $url_pattern .= "\/?)"; // trailing slash or not
			  $error_count = 0;
			  if (strpos($url_val, "?")) {
			  	$url_parts = explode("?", $url_val);
			  	if (!preg_match("/^".$url_pattern."$/", $url_parts[0])) {
			  		$error_count++;
			  	}
			  	if (!preg_match("/^(&?[\w\-]+=\w*)+$/", $url_parts[1])) {
			  		$error_count++;
			  	}
			  } else {
			  	if (!preg_match("/^".$url_pattern."$/", $url_val)) {
			  		$error_count++;
			  	}
			  }
			  if ($error_count > 0) {
			  	$this->messages[] = $this->error_text(14, $field);
			  	return false;
			  } else {
			  	return true;
			  }
			}
			else
			{
			  return true;
			}
		}
	}

	function check_num_val($num_val, $field, $num_len = 0, $req = false) {
		if ($num_val == "") {
			if ($req) {
				$this->messages[] = $this->error_text(1, $field);
				return false;
			} else {
				return true;
			}
		} else {
			$pattern = ($num_len == 0) ? "/^\-?[0-9]*$/" : "/^\-?[0-9]{0,".$num_len."}$/";
			if (preg_match($pattern, $num_val)) {
				return true;
			} else {
				$this->messages[] = $this->error_text(12, $field);
				return false;
			}
		}
	}

	function check_text($text_val, $field, $text_len = 0, $req = true) {
		if ($text_val == "") {
			if ($req) {
				$this->messages[] = $this->error_text(1, $field);
				return false;
			} else {
				return true;
			}
		} else {
			if ($text_len > 0) {
				if (strlen($text_val) > $text_len) {
					$this->messages[] = $this->error_text(13, $field);
					return false;
				} else {
					return true;
				}
			} else {
				return true;
			}
		}
	}

	function check_decimal($dec_val, $field, $decimals = 2, $req = false) {
		if ($dec_val == "") {
			if ($req) {
				$this->messages[] = $this->error_text(1, $field);
				return false;
			} else {
				return true;
			}
		} else {
			$pattern = "/^[-]*[0-9][0-9]*\.[0-9]{".$decimals."}$/";
			if (preg_match($pattern, $dec_val)) {
				return true;
			} else {
				$this->messages[] = $this->error_text(12, $field);
				return false;
			}
		}
	}

	function check_date($date, $field, $version = "us", $req = false) {
		if ($date == "") {
			if ($req) {
				$this->messages[] = $this->error_text(1, $field);
				return false;
			} else {
				return true;
			}
		} else {
			if ($version == "us") {
			  // european = $pattern = "/^(0[1-9]|[1-2][0-9]|3[0-1])[-](0[1-9]|1[0-2])[-](19|20)[0-9]{2}$/";
			  //format = mm-dd-yyyy
			  $pattern = "/^(0[1-9]|1[0-2])[-](0[1-9]|[1-2][0-9]|3[0-1])[-](19|20)[0-9]{2}$/";
			} else {
			  //format = dd-mm-yyyy
			  $pattern = "/^(19|20)[0-9]{2}[-](0[1-9]|1[0-2])[-](0[1-9]|[1-2][0-9]|3[0-1])$/";
			}
			if (preg_match($pattern, $date)) {
			  return true;
			} else {
			  if ($version == "us") {
			    // european = $pattern = "/^(0[1-9]|[1-2][0-9]|3[0-1])[-](0[1-9]|1[0-2])[-](19|20)[0-9]{2}$/";
			    //format = mm/dd/yyyy
			    $pattern = "/^(0[1-9]|1[0-2])[\/](0[1-9]|[1-2][0-9]|3[0-1])[\/](19|20)[0-9]{2}$/";
			  } else {
			    //format = dd/mm/yyyy
			    $pattern = "/^(19|20)[0-9]{2}[\/](0[1-9]|1[0-2])[\/](0[1-9]|[1-2][0-9]|3[0-1])$/";
			  }
			  if (preg_match($pattern, $date)) {
				return true;
			  } else {
				//added by Allen on 18 Jan 2006
				//format = yyyy-mm-dd
				$time_stamp = strtotime($date);       //convert user-entered date into a UNIX timestamp
				$month = date('m', $time_stamp);      //get month, day, and year of this entered date
				$day = date('d', $time_stamp);
				$year = date('Y', $time_stamp);

				//debug only
				//echo $date . " is timestamp " . $time_stamp . " and that equals " . $month . "/" . $day . "/" . $year . "<br><br>";

				//is entered date a valid date?
				if (($time_stamp < 0) or (!checkdate($month,$day,$year)) or ($this->mid($date, 5, 1) != "-") or ($this->mid($date, 8, 1) != "-"))
				{
				  $this->messages[] = $this->error_text(10, $field);
				  return false;
				}
                else
                {
                  //debug
                  //echo $month . "    " . $day;
                  if (($month > 12) OR ($month < 1) OR ($day > 31) OR ($day < 1) OR $month != $this->mid($date, 6, 2) OR $day != $this->mid($date, 9, 2))
                  {
				    $this->messages[] = $this->error_text(10, $field);
				    return false;
                  }
                  else
                  {
                    return true;
                  }
                }
              }
			}
		}
	}

	function check_email($mail_address, $field, $req = true) {
		if ($mail_address == "") {
			if ($req) {
				$this->messages[] = $this->error_text(1, $field);
				return false;
			} else {
				return true;
			}
		} else {
			if (preg_match("/^[0-9a-z]+(([\.\-_])[0-9a-z]+)*@[0-9a-z]+(([\.\-])[0-9a-z-]+)*\.[a-z]{2,4}$/i", strtolower($mail_address))) {
				return true;
			} else {
				$this->messages[] = $this->error_text(11, $field);
				return false;
			}
		}
	}

	function check_html_tags($value, $field) {
		if (preg_match("/[<](\w+)((\s+)(\w+)[=]((\w+)|(\"\.\")|('\.')))*[>]/", $value)) {
			$this->messages[] = $this->error_text(15, $field);
			return false;
		} else {
			return true;
		}
	}

	function create_msg() {
		$the_msg = "";
		asort($this->messages);
		reset($this->messages);
		foreach ($this->messages as $value) {
			$the_msg .= $value."<br>\n";
		}
		return $the_msg;
	}

	function mid($midstring, $midstart, $midlength) {
        return(substr($midstring, $midstart-1, $midlength));
	}

	function error_text($num, $fieldname = "") {
		$fieldname = str_replace("_", " ", $fieldname);
		switch ($this->language) {
			case "dk":
			break;
			default:
			$msg[0] = "Por favor corrija los siguientes errores:";
			$msg[1] = "El campo " . $fieldname . " está vacío.";
			$msg[10] = "La fecha en el campo " . $fieldname . " no es válida.";
			$msg[11] = "El valor de " . $fieldname . " no es válido.";
			$msg[12] = "El valor del campo " . $fieldname . " no es válido.";
			$msg[13] = "El valor del campo " . $fieldname . " es demasiado largo.";
			$msg[14] = "La URL en el campo " . $fieldname . " no es válida.";
			$msg[15] = "Hay código HTML en el campo " . $fieldname . " - esto no está permitido.";
		}
		return mb_convert_encoding(nl2br($msg[$num]), 'ISO-8859-1');
	}
}
?>
