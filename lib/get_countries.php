<?php
include(dirname(__FILE__)."/location.php");

$countries = $location->get_countries();

foreach ($countries as $country) {
?>
  <option value="<?php echo $country['code'].':'.$country['name']; ?>"><?php echo $country['name']; ?></option>
<?php
}

?>
