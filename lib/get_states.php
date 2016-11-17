<?php
header('Content-Type: text/html; charset=ISO-8859-1');
include(dirname(__FILE__)."/location.php");

if($_POST['countryCode'])
{
  $country_code = split(':', $_POST['countryCode'])[0];
  $states = $location->get_states($country_code);

  ?>
  <option selected>Selecciona un estado:</option>
  <?php
  foreach ($states as $state) {
  ?>
    <option value="<?php echo $state['id'].':'.$state['name']; ?>"><?php echo $state['name']; ?></option>
  <?php
  }
}
?>
