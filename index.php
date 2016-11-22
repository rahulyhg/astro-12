<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=ISO-8859-1" />
  <title>Astrological Natal Reports by Allen Edwall</title>
  <meta name="description" content="Free natal horoscope report/interpretation">
  <meta name="keywords" content="astrology, natal horoscopes, natal chart interpretation, transits, right now, planet positions, daily transits">
  <!--
  <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
  -->
  <script type="text/javascript" src="assets/js/jquery.min.js"></script>
  <script type="text/javascript" src="assets/js/index.js"></script>
  <link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet">
  <link rel="stylesheet" href="styles/styles.css">  
</head>

<body>
<?php
header('Content-Type: text/html; charset=UTF-8');

function get_sign_name($sign_pos) {
  $sign_name[1] = "Aries";
  $sign_name[2] = "Tauro";
  $sign_name[3] = "Géminis";
  $sign_name[4] = "Cáncer";
  $sign_name[5] = "Leo";
  $sign_name[6] = "Virgo";
  $sign_name[7] = "Libra";
  $sign_name[8] = "Escorpio";
  $sign_name[9] = "Sagitario";
  $sign_name[10] = "Capricornio";
  $sign_name[11] = "Acuario";
  $sign_name[12] = "Piscis";
  
  return $sign_name[$sign_pos];
}

function safeEscapeString($string) {
// replace HTML tags '<>' with '[]'
  $temp1 = str_replace("<", "[", $string);
  $temp2 = str_replace(">", "]", $temp1);

// but keep <br> or <br />
// turn <br> into <br /> so later it will be turned into ""
// using just <br> will add extra blank lines
  $temp1 = str_replace("[br]", "<br />", $temp2);
  $temp2 = str_replace("[br /]", "<br />", $temp1);

  if (get_magic_quotes_gpc()) {
    return $temp2;
  } else {
    return mysql_escape_string($temp2);
  }
}

function left($leftstring, $leftlength) {
  return(substr($leftstring, 0, $leftlength));
}

function Reduce_below_30($longitude) {
  $lng = $longitude;

  while ($lng >= 30)
  {
    $lng = $lng - 30;
  }

  return $lng;
}

/*
function Convert_Longitude($longitude) {
  $signs = array (0 => 'Ari', 'Tau', 'Gem', 'Can', 'Leo', 'Vir', 'Lib', 'Sco', 'Sag', 'Cap', 'Aqu', 'Pis');

  $sign_num = floor($longitude / 30);
  $pos_in_sign = $longitude - ($sign_num * 30);
  $deg = floor($pos_in_sign);
  $full_min = ($pos_in_sign - $deg) * 60;
  $min = floor($full_min);
  $full_sec = round(($full_min - $min) * 60);

  if ($deg < 10)
  {
    $deg = "0" . $deg;
  }

  if ($min < 10)
  {
    $min = "0" . $min;
  }

  if ($full_sec < 10)
  {
    $full_sec = "0" . $full_sec;
  }

  return $deg . " " . $signs[$sign_num] . " " . $min . "' " . $full_sec . chr(34);
}
*/

function longitude_to_sign($longitude) {
  return floor($longitude / 30) + 1;
}

function Find_Specific_Report_Paragraph($phrase_to_look_for, $file) {
  $string = "";
  $len = strlen($phrase_to_look_for);
  //put entire file contents into an array, line by line
  $file_array = file($file);

  // look through each line searching for $phrase_to_look_for
  for($i = 0; $i < count($file_array); $i++)
  {
    if (left(trim($file_array[$i]), $len) == $phrase_to_look_for)
    {
      $flag = 0;
      // don't include the phrase to find in the returned string
      $i++; 
      while (trim($file_array[$i]) != "*")
      {
        if ($flag == 0)
        {
          $string .= "<b>" . $file_array[$i] . "</b>";
        }
        else
        {
          $string .= $file_array[$i];
        }
        $flag = 1;
        $i++;
      }
      break;
    }
  }

  return $string;
}

function previous_sign($sign) {
  return ($sign == 1 ? 12 : $sign-1);
}

function next_sign($sign) {
  return ($sign == 12 ? 1 : $sign+1);
}

function read_from_file($file, $phrase_to_look_for) {
  $fh = fopen($file, "r");
  if ($phrase_to_look_for) {
    $string = Find_Specific_Report_Paragraph($phrase_to_look_for, $file);
  } else {
    $string = fread($fh, filesize($file));
  }

  fclose($fh);
  return mb_convert_encoding(nl2br($string), 'ISO-8859-1');
}

// Returns an array with the signs the moon was on at the begining and at the end of a time range
function moon_signs_for_unknown_time($moon_start, $moon_end) {
  $sign_start = get_sign_name(longitude_to_sign(explode(',', $moon_start)[1]));
  $sign_end = get_sign_name(longitude_to_sign(explode(',', $moon_end)[1]));

  $moon_signs = [$sign_start];
  if(!in_array($sign_end, $moon_signs)) array_push($moon_signs, $sign_end);

  return $moon_signs;
}

// Gets the GeoPosition of the city usign Google's Geocode API
function Get_Geo_Position($city, $state, $country) {
  //return array("lat" => -34, "lng" => -58);
  $city = trim(mb_convert_encoding(split(':', $city)[1], 'ISO-8859-1'));
  $state = trim(mb_convert_encoding(split(':', $state)[1], 'ISO-8859-1'));
  $country = trim(mb_convert_encoding(split(':', $country)[1], 'ISO-8859-1'));

  $address = $city . ',' . $state . ',' . $country;
  $url = "http://maps.google.com/maps/api/geocode/json?&address=".urlencode($address);

  $json = file_get_contents($url);

  $data = json_decode($json, TRUE);

  if($data['status']=="OK") {
    return Rec_Find($data['results'], "location");
  }
  /*
  */
}

function get_time_zone($lat, $lng) {
  $now = time();
  $url = "https://maps.googleapis.com/maps/api/timezone/json?location=$lat,$lng&timestamp=$now";

  $json = file_get_contents($url);

  $data = json_decode($json, TRUE);

  if($data['status']=="OK") {
    $tz_str = $data['timeZoneId'];
    $time = new DateTime('now', new DateTimeZone($tz_str));
    $timezoneOffset = $time->format('P');
    return decimal_hours($timezoneOffset);
  }
}

function decimal_hours($time) {
    $hms = explode(":", $time);
    return ($hms[0] + ($hms[1]/60) + ($hms[2]/3600));
}

// Recursive function to find a value by a given key on a multidimensional array
Function Rec_Find($array, $key_element) {
  foreach ($array as $key => $value) {
    if (strcmp($key_element, $key) == 0) {
      return $value;
    } elseif (is_array($value)) {
      $result = Rec_Find($value, $key_element);
      if ($result !== null) {
          return $result;
      }
    }
  }
}

$months = array(0 => 'Seleccione el mes', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
$timeWindows = array(1 => array(name => 'Madrugada', start => '00:00', end => '06:00'),
  2 => array(name => 'Mañana', start => '06:00', end => '12:00'),
  3 => array(name => 'Tarde', start => '12:00', end => '18:00'),
  4 => array(name => 'Noche', start => '18:00', end => '24:00'));
$my_error = "";

  // check if the form has been submitted
  if (isset($_POST['submitted'])) {
    header('Content-Type: text/html; charset=ISO-8859-1');

    // get all variables from form
    $name = safeEscapeString($_POST["name"]);
    $gender = safeEscapeString($_POST["gender"]);

    $month = safeEscapeString($_POST["month"]);
    $day = safeEscapeString($_POST["day"]);
    $year = safeEscapeString($_POST["year"]);

    $unknown_time = isset($_POST["unknown-time"]);
    $time_range = safeEscapeString($_POST["time-window"]);

    $hour = safeEscapeString($_POST["hour"]);
    $minute = safeEscapeString($_POST["minute"]);

    $country = safeEscapeString($_POST["country"]);
    $state = safeEscapeString($_POST["state"]);
    $city = safeEscapeString($_POST["city"]);
    
    $position = Get_Geo_Position($city, $state, $country);

    $lat = $position["lat"];
    $lng = $position["lng"];

    $timezone = get_time_zone($lat, $lng);

    include("lib/validation_class.php");

    //error check
    $my_form = new Validate_fields;

    $my_form->check_4html = true;

    $my_form->add_text_field("Name", $name, "text", true, 40);
    $my_form->add_text_field("Gender", $gender, "text", true);

    $my_form->add_text_field("Month", $month, "text", true, 2);
    $my_form->add_text_field("Day", $day, "text", true, 2);
    $my_form->add_text_field("Year", $year, "text", true, 4);

    $my_form->add_text_field("Hour", $hour, "text", !$unknown_time, 2);
    $my_form->add_text_field("Minute", $minute, "text", !$unknown_time, 2);
    $my_form->add_text_field("Time range", $time_range, "text", $unknown_time);

    $my_form->add_text_field("Time zone", $timezone, "text", true, 4);

    $my_form->add_text_field("Country", $country, "text", true);
    $my_form->add_text_field("State", $state, "text", true);
    $my_form->add_text_field("City", $city, "text", true);

    // additional error checks on user-entered data
    if ($gender != "male" and $gender != "female") {
      $my_error .= "Por favor seleccione su género.<br>";
    }

    if ($month != "" And $day != "" And $year != "")
    {
      if (!$date = checkdate(settype ($month, "integer"), settype ($day, "integer"), settype ($year, "integer")))
      {
        $my_error .= "La fecha de nacimiento ingresada no es válida.<br>";
      }
    }

    if (($year < 1900) Or ($year >= 2100))
    {
      $my_error .= "Por favor ingrese un año entre 1900 y 2099.<br>";
    }

    if (($hour < 0) Or ($hour > 23))
    {
      $my_error .= "La hora de nacimiento debe ser entre 0 y 23.<br>";
    }

    if (($minute < 0) Or ($minute > 59))
    {
      $my_error .= "Los minutos de nacimiento deben ser entre 0 y 59.<br>";
    }

    if (is_null($position))
    {
      $my_error .= "Hubo un error al procesar su ubicación.<br>";
    }

    $validation_error = $my_form->validation();

    if ((!$validation_error) || ($my_error != ""))
    {
      $error = $my_form->create_msg();
      echo "<h3>Hay errores en su formulario:</h3>";

      if ($error)
      {
        echo $error . $my_error;
      }
      else
      {
        echo $error . "<br>" . $my_error . "<br>";
      }
    }
    else
    {
      // no errors in filling out form, so process form
      //get today's date
      $date_now = date ("Y-m-d");

      // calculate astronomic data
      $swephsrc = './resources/ephemeris/';
      $sweph = './resources/ephemeris/';

      // Unset any variables not initialized elsewhere in the program
      unset($PATH,$out,$pl_names,$longitude,$house_pos);

      //assign data from database to local variables
      $inmonth = $month;
      $inday = $day;
      $inyear = $year;

      $inhours = $hour;
      $inmins = $minute;
      $insecs = "0";

      $intz = $timezone;

      $my_longitude = $lng;
      $my_latitude = $lat;

      if ($intz >= 0)
      {
        $whole = floor($intz);
        $fraction = $intz - floor($intz);
      }
      else
      {
        $whole = ceil($intz);
        $fraction = $intz - ceil($intz);
      }

      $inhours = $inhours - $whole;
      $inmins = $inmins - ($fraction * 60);

      // adjust date and time for minus hour due to time zone taking the hour negative
      if ($inyear >= 2000)
      {
        $utdatenow = strftime("%d.%m.20%y", mktime($inhours, $inmins, $insecs, $inmonth, $inday, $inyear));
      }
      else
      {
        $utdatenow = strftime("%d.%m.19%y", mktime($inhours, $inmins, $insecs, $inmonth, $inday, $inyear));
      }

      $utnow = strftime("%H:%M:%S", mktime($inhours, $inmins, $insecs, $inmonth, $inday, $inyear));

      putenv("PATH=$PATH:$swephsrc");

      // get 10 planets and all house cusps
      /*
      original call to swetest before script modifications
      exec("swetest -edir$sweph -b$utdatenow -ut$utnow -p0123456789 -eswe -house$my_longitude,$my_latitude, -fPlj -g, -head", $out);
      */
      if ($unknown_time) {
        exec("swetest -edir$sweph -b$utdatenow -ut$utnow -p01 -eswe -fPlj -g, -head", $out);

        $start_time = $timeWindows[$time_range]["start"];
        $end_time = $timeWindows[$time_range]["end"];

        exec("swetest -edir$sweph -b$utdatenow -ut$start_time -p1 -eswe -fPlj -g, -head", $moon_start);
        exec("swetest -edir$sweph -b$utdatenow -ut$end_time -p1 -eswe -fPlj -g, -head", $moon_end);

        $moon_signs = moon_signs_for_unknown_time($moon_start[0], $moon_end[0]);
      } else {
        exec("swetest -edir$sweph -b$utdatenow -ut$utnow -p01 -eswe -house$my_longitude,$my_latitude, -fPlj -g, -head", $out);
      }

      // Each line of output data from swetest is exploded into array $row, giving these elements:
      // 0 = planet name
      // 1 = longitude
      // 2 = house position
      // planets are index 0 - index 9, house cusps are index 10 - 21
      $pl_names = ['Sun', 'Moon', 'Ascendant'];

      /*
      $pl_names[0] = "Sun";
      $pl_names[1] = "Moon";
      $pl_names[2] = "Mercury";
      $pl_names[3] = "Venus";
      $pl_names[4] = "Mars";
      $pl_names[5] = "Jupiter";
      $pl_names[6] = "Saturn";
      $pl_names[7] = "Uranus";
      $pl_names[8] = "Neptune";
      $pl_names[9] = "Pluto";
      $pl_names[10] = "Ascendant";
      $pl_names[11] = "House 2";
      $pl_names[12] = "House 3";
      $pl_names[13] = "House 4";
      $pl_names[14] = "House 5";
      $pl_names[15] = "House 6";
      $pl_names[16] = "House 7";
      $pl_names[17] = "House 8";
      $pl_names[18] = "House 9";
      $pl_names[19] = "MC (Midheaven)";
      $pl_names[20] = "House 11";
      $pl_names[21] = "House 12";
      */

      $planets = [];
      foreach ($out as $key => $line)
      {
        $row = explode(',', $line);
        if (in_array(trim($row[0]), $pl_names)) {
          $planet_name = trim($row[0]);
          $planets[$planet_name] = array("longitude" => $row[1]);
        }
      };

      //get house positions of planets here
      for ($x = 1; $x <= 12; $x++)
      {
        for ($y = 0; $y <= 9; $y++)
        {
          $pl = $longitude[$y] + (1 / 36000);
          if ($x < 12 And $longitude[$x + 9] > $longitude[$x + 10])
          {
            If (($pl >= $longitude[$x + 9] And $pl < 360) Or ($pl < $longitude[$x + 10] And $pl >= 0))
            {
              $house_pos[$y] = $x;
              continue;
            }
          }

          if ($x == 12 And ($longitude[$x + 9] > $longitude[10]))
          {
            if (($pl >= $longitude[$x + 9] And $pl < 360) Or ($pl < $longitude[10] And $pl >= 0))
            {
              $house_pos[$y] = $x;
            }
            continue;
          }

          if (($pl >= $longitude[$x + 9]) And ($pl < $longitude[$x + 10]) And ($x < 12))
          {
            $house_pos[$y] = $x;
            continue;
          }

          if (($pl >= $longitude[$x + 9]) And ($pl < $longitude[10]) And ($x == 12))
          {
            $house_pos[$y] = $x;
          }
        }
      }

      //generate natal report here (and display natal data)
      echo "<center>";

      $existing_name = $name;

      echo "<h1>Reporte astrológico para: $existing_name</h1>";

      $secs = "0";
      if ($timezone < 0)
      {
        $tz = $timezone;
      } else {
        $tz = "+" . $timezone;
      }

      setlocale(LC_ALL, 'es_AR.UTF-8');
      if ($unknown_time) {
        $birth_info_str = strftime("%A, %d de %B de %Y<br/>", mktime(0, 0, 0, $month, $day, $year));
      } else {
        $birth_info_str = strftime("%A, %d de %B de %Y a las %H:%M (GMT $tz)<br/>", mktime($hour, $minute, $secs, $month, $day, $year));
      }

      echo mb_convert_encoding($birth_info_str, 'ISO-8859-1');

      echo "</center>";

      $hr_ob = $hour;
      $min_ob = $minute;

      $base_dir = "resources/natal_files";

      echo '<center><table width="61.8%" cellpadding="0" cellspacing="0" border="0">';
      echo '<tr><td>';

      if (!$unknown_time) {
        //display rising sign interpretation
        //get header first
        echo "<h2>EL SIGNO ASCENDENTE</h2>";
        echo "<p>" . read_from_file("$base_dir/ascendant.txt", "ascendant_description") . "</p>";

        $s_pos = longitude_to_sign($planets['Ascendant']['longitude']);

        echo "<p>" . read_from_file("$base_dir/ascendant.txt", "ascendant_".$s_pos."_$gender") . "</p>";
      }

      //display planet in sign interpretation
      //get header first
      echo "<h2>EL SOL Y LA LUNA</h2>";

      // loop through each planet (only sun and moon)
      $keys = ['Sun', 'Moon'];
      foreach ($keys as $key)
      {
        $p_name = strtolower(trim($key));
        $p_lng = $planets[$key]['longitude'];
        $s_pos = longitude_to_sign($p_lng);
        $file_name = "$base_dir/" . $p_name . ".txt";

        $sign_interp .= read_from_file($file_name, $p_name."_description");

        $deg = Reduce_below_30($p_lng);
        if ($unknown_time and strcmp('Moon', $key) == 0 and count($moon_signs) > 1)
        {
          //if the Moon changed during the time range entered, don't include it in the report
          $sign_interp .= "<b>La luna en tu carta natal</b><br><br>No podemos darte datos certeros sobre tu luna, ya que en el rango horario que ingresaste, la luna pasó del signo <b>$moon_signs[0]</b> al signo <b>$moon_signs[1]</b>. Te recomendamos que busques tu horario de nacimiento para que puedas saber en qué signo se encontraba la luna en el momento en que naciste.<br>";
        } else  {
          $sign_interp .= read_from_file($file_name, $p_name."_".$s_pos."_".$gender);
        }
      }

      echo "<p>" . $sign_interp . "</p>";

      /*
      if (!$unknown_time)
      {
        //display planet in house interpretation
        //get header first
        echo "<center><font size='+1' color='#0000ff'><b>HOUSE POSITIONS OF PLANETS</b></font></center>";

        $file = "$base_dir/house.txt";
        $fh = fopen($file, "r");
        $string = fread($fh, filesize($file));
        fclose($fh);

        $string = nl2br($string);
        $house_interp = $string;

        // loop through each planet
        for ($i = 0; $i <= 9; $i++)
        {
          $h_pos = $house_pos[$i];
          $phrase_to_look_for = $pl_names[$i] . " in";
          $file = "$base_dir/house_" . trim($h_pos) . ".txt";
          $string = Find_Specific_Report_Paragraph($phrase_to_look_for, $file);
          $string = nl2br($string);
          $house_interp .= $string;
        }

        echo "<font size=2>" . $house_interp . "</font>";
      }
      */

      /*
      //display planetary aspect interpretations
      //get header first
      echo "<center><font size='+1' color='#0000ff'><b>PLANETARY ASPECTS</b></font></center>";

      $file = "$base_dir/aspect.txt";
      $fh = fopen($file, "r");
      $string = fread($fh, filesize($file));
      fclose($fh);

      $string = nl2br($string);
      $p_aspect_interp = $string;

      echo "<font size=2>" . $p_aspect_interp . "</font>";

      // loop through each planet
      for ($i = 0; $i <= 9; $i++)
      {
        for ($j = $i + 1; $j <= 10; $j++)
        {
          if (($i == 1 Or $j == 1 Or $j == 10) And $unknown_time == 1)
          {
            continue;			// do not allow Moon aspects or Ascendant aspects if birth time is unknown
          }

          $da = Abs($longitude[$i] - $longitude[$j]);
          if ($da > 180)
          {
            $da = 360 - $da;
          }

          // set orb - 8 if Sun or Moon, 6 if not Sun or Moon
          if ($i == 0 Or $i == 1 Or $j == 0 Or $j == 1)
          {
            $orb = 8;
          }
          else
          {
            $orb = 6;
          }

          // are planets within orb?
          $q = 1;
          if ($da <= $orb)
          {
            $q = 2;
          }
          elseif (($da <= 60 + $orb) And ($da >= 60 - $orb))
          {
            $q = 3;
          }
          elseif (($da <= 90 + $orb) And ($da >= 90 - $orb))
          {
            $q = 4;
          }
          elseif (($da <= 120 + $orb) And ($da >= 120 - $orb))
          {
            $q = 5;
          }
          elseif ($da >= 180 - $orb)
          {
            $q = 6;
          }

          if ($q > 1)
          {
            if ($q == 2)
            {
              $aspect = " blending with ";
            }
            elseif ($q == 3 Or $q == 5)
            {
              $aspect = " harmonizing with ";
            }
            elseif ($q == 4 Or $q == 6)
            {
              $aspect = " discordant to ";
            }

            $phrase_to_look_for = $pl_names[$i] . $aspect . $pl_names[$j];
            $file = "$base_dir/" . strtolower($pl_names[$i]) . ".txt";
            $string = Find_Specific_Report_Paragraph($phrase_to_look_for, $file);
            $string = nl2br($string);
            echo "<font size=2>" . $string . "</font>";
          }
        }
      }
      */

      /*
      //display closing
      echo "<br><center><font size='+1' color='#0000ff'><b>Comentarios finales</b></font></center>";

      if ($unknown_time)
      {
        $file = "$base_dir/closing_unk.txt";
      }
      else
      {
        $file = "$base_dir/closing.txt";
      }
      $fh = fopen($file, "r");
      $string = fread($fh, filesize($file));
      fclose($fh);

      $closing = nl2br($string);
      echo "<font size=2>" . $closing . "</font>";
      */

      echo '</td></tr>';
      echo '</table></center>';

      /*
      $retrograde = "          ";

      //display natal data
      echo '<center><table width="50%" cellpadding="0" cellspacing="0" border="0">',"\n";

      echo '<tr>';
      echo "<td><font color='#0000ff'><b> Name </b></font></td>";
      echo "<td><font color='#0000ff'><b> Longitude </b></font></td>";
      if ($unknown_time)
      {
        echo "<td>&nbsp;</td>";
      }
      else
      {
        echo "<td><font color='#0000ff'><b> House<br>position </b></font></td>";
      }
      echo '</tr>';

      for ($i = 0; $i <= 9; $i++)
      {
        echo '<tr>';
        echo "<td>" . $pl_names[$i] . "</td>";
        echo "<td><font face='Courier New'>" . Convert_Longitude($longitude[$i]) . "</font></td>";
        if ($unknown_time == 1)
        {
          echo "<td>&nbsp;</td>";
        }
        else
        {
          $hse = floor($house_pos[$i]);
          if ($hse < 10)
          {
            echo "<td>&nbsp; " . $hse . "</td>";
          }
          else
          {
            echo "<td>" . $hse . "</td>";
          }
        }
        echo '</tr>';
      }

      echo '<tr>';
      echo "<td> &nbsp </td>";
      echo "<td> &nbsp </td>";
      echo "<td> &nbsp </td>";
      echo "<td> &nbsp </td>";
      echo '</tr>';

      if ($unknown_time)
      {
        echo '<tr>';
        echo "<td><font color='#0000ff'><b> Name </b></font></td>";
        echo "<td><font color='#0000ff'><b> Longitude </b></font></td>";
        echo "<td> &nbsp </td>";
        echo '</tr>';

        for ($i = 10; $i <= 21; $i++)
        {
          echo '<tr>';
          if ($i == 10)
          {
            echo "<td>Ascendant </td>";
          }
          elseif ($i == 19)
          {
            echo "<td>MC (Midheaven) </td>";
          }
          else
          {
            echo "<td>House " . ($i - 9) . "</td>";
          }
          echo "<td><font face='Courier New'>" . Convert_Longitude($longitude[$i]) . "</font></td>";
          echo "<td> &nbsp </td>";
          echo '</tr>';
        }
      }

      echo '</table></center>',"\n";
      */
      exit();
    }
  }

?>
<?php
  header('Content-Type: text/html; charset=ISO-8859-1');
?>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" style="margin: 0px 20px;">
  <fieldset><legend>Calcular carta natal</legend>

  <table style="font-size:12px;">
    <TR>
      <td>
        <P align="right">Nombre:</P>
      </td>

      <td>
        <input size="40" name="name" value="<?php echo $_POST['name']; ?>"/>
      </td>
    </TR>

    <TR>
      <td>
        <P align="right">Sexo:</P>
      </td>

      <td>
        <div>
          <input value="female" name="gender" type="radio" <?php echo ($_POST['gender']=='female') ? 'checked' : ''; ?> />
          <label for="female-gender">Femenino</label>
          <input value="male" name="gender" type="radio" <?php echo ($_POST['gender']=='male') ? 'checked' : ''; ?> />
          <label for="male-gender">Masculino</label>
        </div>
      </td>
    </TR>

    <TR>
      <TD>
        <P align="right">Fecha de Nacimiento:</P>
      </TD>

      <TD>
        <input size="2" maxlength="2" name="day" value="<?php echo $_POST['day']; ?>"/>

        <?php
        echo '<select name="month">';
        foreach ($months as $key => $value)
        {
          echo "<option value=\"$key\"";
          if ($key == $month)
          {
            echo ' selected="selected"';
          }
          echo ">$value</option>\n";
        }
        echo '</select>';
        ?>

        <input size="4" maxlength="4" name="year" value="<?php echo $_POST['year']; ?>"/>
        (Solo los años de 1900 hasta 2099 son válidos)
     </TD>
    </TR>

    <TR>
      <td valign="top"><P align="right">Horario de Nacimiento:</P></td>
      <td>
        <div id="known-time-wrapper">
          <input maxlength="2" size="2" name="hour" value="<?php echo $_POST['hour']; ?>"/>
          <input maxlength="2" size="2" name="minute" value="<?php echo $_POST['minute']; ?>"/>
        </div>
        <div id="unknown-time-wrapper">
          <select class="time-window" name="time-window">
            <option value="" selected>Elegí un rango horario aproximado</option>
            <?php
            foreach ($timeWindows as $key => $value) {
              echo "<option value=\"$key\"";
              if ($key == $time_range)
              {
                echo ' selected="selected"';
              }
              echo ">$value[name] ($value[start]-$value[end])</option>\n";
            }
            ?>
          </select>
        </div>
        <input id="unknown-time" name="unknown-time" type="checkbox" <?php echo (isset($_POST['unknown-time']) ? 'checked' : ''); ?> >
        <label for="unknown-time">No conozco mi horario de nacimiento.</label>
      </td>
    </TR>

    <TR>
      <td valign="top"><P align="right">Pais:</P></td>
      <td>
        <select class="country" name="country">
          <option>Seleccione el pais</option>
          <?php
            require('lib/get_countries.php');

            foreach ($countries as $key => $value) {
              echo "<option value=\"$value[code]:$value[name]\"";
              if ($value['code'].':'.$value['name'] == $country)
              {
                echo ' selected="selected"';
              }
              echo ">$value[name]</option>\n";
            }
          ?>
        </select>
      </td>
    </TR>

    <TR id="state-input">
      <td valign="top"><P align="right">Estado/Provincia:</P></td>
      <td>
        <select class="state" name="state">
          <option value="<?php echo $_POST['state']; ?>" selected>Seleccione el estado/provincia</option>
        </select>
      </td>
    </TR>

    <TR id="city-input">
      <td valign="top"><P align="right">Ciudad:</P></td>
      <td>
        <select class="city" name="city">
          <option value="<?php echo $_POST['city']; ?>" selected>Seleccione la ciudad</option>
        </select>
      </td>
    </TR>

    <!--
    <TR>
      <td valign="top"><P align="right">Zona horaria:</P></td>

      <td>
        <select name="timezone" size="1">
          <option value="" selected>Seleccioná la zona horaria</option>
          <option value="-12" >GMT -12:00 hrs - IDLW</option>
          <option value="-11" >GMT -11:00 hrs - BET or NT</option>
          <option value="-10.5" >GMT -10:30 hrs - HST</option>
          <option value="-10" >GMT -10:00 hrs - AHST</option>
          <option value="-9.5" >GMT -09:30 hrs - HDT or HWT</option>
          <option value="-9" >GMT -09:00 hrs - YST or AHDT or AHWT</option>
          <option value="-8" >GMT -08:00 hrs - PST or YDT or YWT</option>
          <option value="-7" >GMT -07:00 hrs - MST or PDT or PWT</option>
          <option value="-6" >GMT -06:00 hrs - CST or MDT or MWT</option>
          <option value="-5" >GMT -05:00 hrs - EST or CDT or CWT</option>
          <option value="-4" >GMT -04:00 hrs - AST or EDT or EWT</option>
          <option value="-3.5" >GMT -03:30 hrs - NST</option>
          <option value="-3" >GMT -03:00 hrs - BZT2 or AWT</option>
          <option value="-2" >GMT -02:00 hrs - AT</option>
          <option value="-1" >GMT -01:00 hrs - WAT</option>
          <option value="0" >Greenwich Mean Time - GMT or UT</option>
          <option value="1" >GMT +01:00 hrs - CET or MET or BST</option>
          <option value="2" >GMT +02:00 hrs - EET or CED or MED or BDST or BWT</option>
          <option value="3" >GMT +03:00 hrs - BAT or EED</option>
          <option value="3.5" >GMT +03:30 hrs - IT</option>
          <option value="4" >GMT +04:00 hrs - USZ3</option>
          <option value="5" >GMT +05:00 hrs - USZ4</option>
          <option value="5.5" >GMT +05:30 hrs - IST</option>
          <option value="6" >GMT +06:00 hrs - USZ5</option>
          <option value="6.5" >GMT +06:30 hrs - NST</option>
          <option value="7" >GMT +07:00 hrs - SST or USZ6</option>
          <option value="7.5" >GMT +07:30 hrs - JT</option>
          <option value="8" >GMT +08:00 hrs - AWST or CCT</option>
          <option value="8.5" >GMT +08:30 hrs - MT</option>
          <option value="9" >GMT +09:00 hrs - JST or AWDT</option>
          <option value="9.5" >GMT +09:30 hrs - ACST or SAT or SAST</option>
          <option value="10" >GMT +10:00 hrs - AEST or GST</option>
          <option value="10.5" >GMT +10:30 hrs - ACDT or SDT or SAD</option>
          <option value="11" >GMT +11:00 hrs - UZ10 or AEDT</option>
          <option value="11.5" >GMT +11:30 hrs - NZ</option>
          <option value="12" >GMT +12:00 hrs - NZT or IDLE</option>
          <option value="12.5" >GMT +12:30 hrs - NZS</option>
          <option value="13" >GMT +13:00 hrs - NZST</option>
        </select>
      </td>
    </TR>
    -->
  </table>

  <input type="hidden" name="submitted" value="true"/>
  <input type="submit" name="submit" value="Enviar" />
  </fieldset>
</form>
</body>
</html>
