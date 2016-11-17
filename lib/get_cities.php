<?php
include(dirname(__FILE__)."/location.php");
header('Content-Type: text/html; charset=ISO-8859-1');

if($_POST['countryCode'] && $_POST['state'])
{
  $country_code = split(':', $_POST['countryCode'])[0];
  $state = split(':', $_POST['state'])[0];
  $cities = $location->get_cities($country_code, $state);

  ?>
  <option selected>Selecciona una ciudad:</option>
  <?php
  foreach ($cities as $city) {
  ?>
    <option value="<?php echo $city['id'].':'.$city['name']; ?>"><?php echo $city['name']; ?></option>
  <?php
  }
}
?>
