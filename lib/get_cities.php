<?php
header('Content-Type: text/html; charset=ISO-8859-1');
include(dirname(__FILE__)."/location.php");

if($_POST['countryCode'] && $_POST['state'])
{
  $state_id = trim(split(':', $_POST['state'])[0]);
  $state_name = trim(split(':', $_POST['state'])[1]);
  $cities = $location->get_cities($state_id, $state_name);

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
