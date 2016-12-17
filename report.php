<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=ISO-8859-1" />
  <title>Astrologia</title>
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
function get_user_info($id) {
  $user_info = [];
  $user_info["name"] = 'Pepe';
  $user_info["gender"] = 'female';
  $user_info["day"] = 21;
  $user_info["month"] = 3;
  $user_info["year"] = 1990;
  $user_info["unknown_time"] = 0;
  $user_info["time_range"] = null;
  $user_info["hour"] = 7;
  $user_info["minute"] = 0;
  $user_info["country"] = 'ARG:Argentina';
  $user_info["state"] = 'Distrito Federal:Distrito Federal';
  $user_info["city"] = '69:Buenos Aires';
  $user_info["lat"] = -34.6036844;
  $user_info["lng"] = -58.3815591;
  $user_info["timezone"] = -3;

  return $user_info;
}

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

function left($leftstring, $leftlength) {
  return(substr($leftstring, 0, $leftlength));
}

function reduce_below_30($longitude) {
  $lng = $longitude;

  while ($lng >= 30)
  {
    $lng = $lng - 30;
  }

  return $lng;
}

function longitude_to_sign($longitude) {
  return floor($longitude / 30) + 1;
}

function find_specific_report_paragraph($phrase_to_look_for, $file) {
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
    $string = find_specific_report_paragraph($phrase_to_look_for, $file);
  } else {
    $string = fread($fh, filesize($file));
  }

  fclose($fh);
  return mb_convert_encoding(nl2br($string), 'UTF-8');
}

// Returns an array with the signs the moon was on at the begining and at the end of a time range
function moon_signs_for_unknown_time($moon_start, $moon_end) {
  $sign_start = get_sign_name(longitude_to_sign(explode(',', $moon_start)[1]));
  $sign_end = get_sign_name(longitude_to_sign(explode(',', $moon_end)[1]));

  $moon_signs = [$sign_start];
  if(!in_array($sign_end, $moon_signs)) array_push($moon_signs, $sign_end);

  return $moon_signs;
}

if (isset($_GET['id'])) {

  $user_id = $_GET['id'];
  $user_info = get_user_info($user_id);

  if (is_null($user_info)) {
    echo "<p>Hubo un problema al ejecutar su solicitud. Por favor inténtelo más tarde o contáctese con un administrador.</p><br>";
  }
  else
  {
    // get all variables from form
    $name = $user_info["name"];
    $gender = $user_info["gender"];

    $month = $user_info["month"];
    $day = $user_info["day"];
    $year = $user_info["year"];

    $unknown_time = $user_info["unknown_time"];
    $time_range = $user_info["time_window"];

    $hour = $user_info["hour"];
    $minute = $user_info["minute"];

    $country = $user_info["country"];
    $state = $user_info["selected"];
    $city = $user_info["selected-city"];
    
    $lat = $user_info["lat"];
    $lng = $user_info["lng"];

    $timezone = $user_info["timezone"];

    // no errors in filling out form, so process form
    //get today's date
    $date_now = date("Y-m-d");

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

    echo "<h1>Reporte astrológico para: $name</h1>";

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

    echo mb_convert_encoding($birth_info_str, 'UTF-8');

    echo "</center>";

    $hr_ob = $hour;
    $min_ob = $minute;

    $base_dir = "resources/natal_files";

    echo '<center><table width="61.8%" cellpadding="0" cellspacing="0" border="0">';
    echo '<tr><td>';

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

      $deg = reduce_below_30($p_lng);
      if ($unknown_time and strcmp('Moon', $key) == 0 and count($moon_signs) > 1)
      {
        //if the Moon changed during the time range entered, don't include it in the report
        $sign_interp .= "<b>La Luna en tu carta natal</b><br><br>No podemos darte datos certeros sobre tu Luna, ya que en el rango horario que ingresaste, la Luna pasó del signo <b>$moon_signs[0]</b> al signo <b>$moon_signs[1]</b>. Te recomendamos que busques tu horario de nacimiento para que puedas saber en qué signo se encontraba la Luna en el momento en que naciste.<br>";
      } else  {
        $sign_interp .= read_from_file($file_name, $p_name."_".$s_pos."_".$gender);
      }
    }

    echo "<p>" . $sign_interp . "</p>";

    if (!$unknown_time) {
      //display rising sign interpretation
      //get header first
      echo "<h2>EL SIGNO ASCENDENTE</h2>";
      echo "<p>" . read_from_file("$base_dir/ascendant.txt", "ascendant_description") . "</p>";

      $s_pos = longitude_to_sign($planets['Ascendant']['longitude']);

      echo "<p>" . read_from_file("$base_dir/ascendant.txt", "ascendant_".$s_pos."_$gender") . "</p>";
    }

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
        $string = find_specific_report_paragraph($phrase_to_look_for, $file);
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
          continue;     // do not allow Moon aspects or Ascendant aspects if birth time is unknown
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
          $string = find_specific_report_paragraph($phrase_to_look_for, $file);
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
  }
}
else
{
  header('Location: index.php');
  exit();
}

?>
</body>
</html>
