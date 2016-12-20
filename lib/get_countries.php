<?php
header('Content-Type: text/html; charset=ISO-8859-1');
include(dirname(__FILE__)."/location.php");

$countries = $location->get_countries();
?>
<option selected>Selecciona un pais:</option>
<?php
foreach ($countries as $country) {
?>
  <option value="<?php echo $country['id'].':'.$country['name']; ?>"><?php echo $country['name']; ?></option>
<?php
}

?>
