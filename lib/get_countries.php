<?php
include(dirname(__FILE__)."/location.php");

$countries = $location->get_countries();
//echo '<br>'.$selected_country.'<br>';
/*
foreach ($countries as $country) {
?>
  <option value="<?php echo $country['code'].':'.$country['name']; ?>" "<?php echo ($country == $selected_country) ? 'selected' : '' ?>"><?php echo $country['name']; ?></option>
<?php
}
*/
?>
