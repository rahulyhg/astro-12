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
header('Content-Type: text/html; charset=ISO-8859-1');

function safe_escape_string($string) {
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

// Gets the GeoPosition of the city usign Google's Geocode API
function get_geo_position($city, $state, $country) {
  //return array("lat" => -34, "lng" => -58);
  $city = trim(mb_convert_encoding(split(':', $city)[1], 'utf-8'));
  $state = trim(mb_convert_encoding(split(':', $state)[1], 'utf-8'));
  $country = trim(mb_convert_encoding(split(':', $country)[1], 'utf-8'));

  $address = $city . ',' . $state . ',' . $country;
  $url = "http://maps.google.com/maps/api/geocode/json?&address=".urlencode($address);

  $json = file_get_contents($url);

  $data = json_decode($json, TRUE);

  if($data['status']=="OK") {
    return rec_find($data['results'], "location");
  }
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
function rec_find($array, $key_element) {
  foreach ($array as $key => $value) {
    if (strcmp($key_element, $key) == 0) {
      return $value;
    } elseif (is_array($value)) {
      $result = rec_find($value, $key_element);
      if ($result !== null) {
          return $result;
      }
    }
  }
}

function persist_user($user) {

}

$months = array(0 => 'Seleccione el mes', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
$timeWindows = array(1 => array(name => 'Madrugada', start => '00:00', end => '06:00'),
    2 => array(name => 'Mañana', start => '06:00', end => '12:00'),
    3 => array(name => 'Tarde', start => '12:00', end => '18:00'),
    4 => array(name => 'Noche', start => '18:00', end => '24:00'));

  // check if the form has been submitted
  if (isset($_POST['submitted'])) {
    // get all variables from form
    $name = safe_escape_string($_POST["name"]);
    $email = safe_escape_string($_POST["email"]);
    $gender = safe_escape_string($_POST["gender"]);

    $month = safe_escape_string($_POST["month"]);
    $day = safe_escape_string($_POST["day"]);
    $year = safe_escape_string($_POST["year"]);

    $unknown_time = isset($_POST["unknown-time"]) ? 1 : 0;
    $time_range = safe_escape_string($_POST["time-window"]);

    $hour = safe_escape_string($_POST["hour"]);
    $minute = safe_escape_string($_POST["minute"]);

    $country = safe_escape_string($_POST["selected-country"]);
    $state = safe_escape_string($_POST["selected-state"]);
    $city = safe_escape_string($_POST["selected-city"]);
    
    $position = get_geo_position($city, $state, $country);

    $lat = $position["lat"];
    $lng = $position["lng"];

    $timezone = get_time_zone($lat, $lng);

    include("lib/validation_class.php");

    //error check
    $my_form = new Validate_fields;

    $my_form->check_4html = true;

    $my_form->add_text_field("Name", $name, true, 40);
    $my_form->add_link_field("Email", $email);
    $my_form->add_text_field("Gender", $gender, true);

    $my_form->add_num_field("Month", $month, true, 1, 12);
    $my_form->add_num_field("Day", $day, true, 1, 31);
    $my_form->add_num_field("Year", $year, true, 1900, 2100);

    $my_form->add_num_field("Hour", $hour, !$unknown_time, 0, 23);
    $my_form->add_num_field("Minute", $minute, !$unknown_time, 0, 59);
    $my_form->add_text_field("Time range", $time_range, $unknown_time);

    $my_form->add_text_field("Time zone", $timezone, true, 4);

    $my_form->add_text_field("Country", $country, true);
    $my_form->add_text_field("State", $state, !empty($country));
    $my_form->add_text_field("City", $city, !empty($state));

      if (is_null($position) && !empty($country) && !empty($state) && !empty($city))
    {
      $my_error = "Hubo un error al procesar su ubicación.<br>";
    }

    $validation_error = $my_form->validation();

    if ((!$validation_error) || ($my_error != ""))
    {
      echo "<h3>Hay errores en su formulario:</h3>";

      if ($my_error) {
        echo "$my_error <br>";
      }
    }
    else
    {
      $user = [];
      $user["name"] = $name;
      $user["email"] = $email;
      $user["gender"] = $gender;
      $user["day"] = $day;
      $user["month"] = $month;
      $user["year"] = $year;
      $user["unknown_time"] = $unknown_time;
      $user["time_range"] = $time_range;
      $user["hour"] = $hour;
      $user["minute"] = $minute;
      $user["country"] = $country;
      $user["state"] = $state;
      $user["city"] = $city;
      $user["lat"] = $lat;
      $user["lng"] = $lng;
      $user["timezone"] = $timezone;

      persist_user($user);
      echo "<p>Hemos recibido tu solicitud! Procesaremos tus datos y te enviaremos en las próximas horas un reporte astrológico a tu correo.</p><br>";
    }
  }
?>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" style="margin: 0px 20px;">
  <fieldset><legend>Calcular carta natal</legend>

    <table style="font-size:12px;">
      <TR>
        <td>
          <P align="right">Nombre:</P>
        </td>

        <td>
          <input type="text" size="40" name="name" 
            value="<?php echo $_POST['name']; ?>" 
            class="<?php echo $my_form->errors['Name'] ? 'invalid' : ''; ?>" />
        </td>
      </TR>

      <TR>
        <td>
          <P align="right">Email:</P>
        </td>

        <td>
          <input type="email" name="email" 
            value="<?php echo $_POST['email']; ?>" 
            class="<?php echo $my_form->errors['Email'] ? 'invalid' : ''; ?>" />
        </td>
      </TR>

      <TR>
        <td>
          <P align="right">Sexo:</P>
        </td>

        <td>
          <div>
            <input value="female" name="gender" type="radio"
              class="<?php echo $my_form->errors['Gender'] ? 'invalid' : ''; ?>"
              <?php echo ($_POST['gender']=='female') ? 'checked' : ''; ?> />
            <label for="female-gender">Femenino</label>
            <input value="male" name="gender" type="radio" 
              class="<?php echo $my_form->errors['Gender'] ? 'invalid' : ''; ?>"
              <?php echo ($_POST['gender']=='male') ? 'checked' : ''; ?> />
            <label for="male-gender">Masculino</label>
          </div>
        </td>
      </TR>

      <TR>
        <TD>
          <P align="right">Fecha de Nacimiento:</P>
        </TD>

        <TD>
          <input type="number" name="day" min="1" max="31" 
            class="<?php echo $my_form->errors['Day'] ? 'invalid' : ''; ?>"
            value="<?php echo $_POST['day']; ?>"/>

          <select name="month" class="<?php echo $my_form->errors['Month'] ? 'invalid' : ''; ?>">
          <?php
          foreach ($months as $key => $value)
          {
            echo "<option value=\"$key\"";
            if ($key == $month)
            {
              echo ' selected="selected"';
            }
            echo ">$value</option>\n";
          }
          ?>
          </select>

          <input type="number" name="year" min="1900" max="2099"
            class="<?php echo $my_form->errors['Year'] ? 'invalid' : ''; ?>"
            value="<?php echo $_POST['year']; ?>"/>
          (Solo los años de 1900 hasta 2099 son válidos)
       </TD>
      </TR>

      <TR>
        <td valign="top"><P align="right">Horario de Nacimiento:</P></td>
        <td>
          <div id="known-time-wrapper">
            <input type="number" min="0" max="23" name="hour"
              class="<?php echo $my_form->errors['Hour'] ? 'invalid' : ''; ?>"
              value="<?php echo $_POST['hour']; ?>"/>
            <input type="number" min="0" max="59" name="minute" 
              class="<?php echo $my_form->errors['Minute'] ? 'invalid' : ''; ?>"
              value="<?php echo $_POST['minute']; ?>"/>
          </div>
          <div id="unknown-time-wrapper">
            <select id="time-window" name="time-window" class="<?php echo $my_form->errors['Time range'] ? 'invalid' : ''; ?>">
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
          <select id="country" name="country" class="<?php echo $my_form->errors['Country'] ? 'invalid' : ''; ?>">
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
          <input type="hidden" id="selected-country" name="selected-country" value="<?php echo $country; ?>" />
        </td>
      </TR>

      <TR id="state-input">
        <td valign="top"><P align="right">Estado/Provincia:</P></td>
        <td>
          <select id="state" name="state" class="<?php echo $my_form->errors['State'] ? 'invalid' : ''; ?>">
            <option>Seleccione el estado/provincia</option>
          </select>
          <input type="hidden" id="selected-state" name="selected-state" value="<?php echo $state; ?>" />
        </td>
      </TR>

      <TR id="city-input">
        <td valign="top"><P align="right">Ciudad:</P></td>
        <td>
          <select id="city" name="city" class="<?php echo $my_form->errors['City'] ? 'invalid' : ''; ?>">
            <option>Seleccione la ciudad</option>
          </select>
          <input type="hidden" id="selected-city" name="selected-city" value="<?php echo $city; ?>" />
        </td>
      </TR>
    </table>

    <input type="hidden" name="submitted" value="true"/>
    <input type="submit" name="submit" value="Enviar"/>
  </fieldset>
</form>
</body>
</html>
