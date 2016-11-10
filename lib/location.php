<?php
include(dirname(__FILE__)."/../config/dbconfig.php");

class Location
{
  const GET_COUNTRIES_QUERY = "SELECT code, name FROM country ORDER BY name";
  const GET_STATES_QUERY = "SELECT DISTINCT district FROM city WHERE CountryCode = :countryCode ORDER BY district";
  const GET_CITIES_QUERY = "SELECT id, name FROM city WHERE CountryCode = :countryCode AND district = :district ORDER BY name";

  function get_countries() {
    global $DB_con;

    $stmt = $DB_con->prepare(self::GET_COUNTRIES_QUERY);
    $stmt->execute();

    $countries = array();

    while($row=$stmt->fetch(PDO::FETCH_ASSOC)) {
      array_push($countries, array("code" => $row['code'], "name" => $row['name']));
    }

    return $countries;
  }

  function get_states($country_code) {
    global $DB_con;

    $stmt = $DB_con->prepare(self::GET_STATES_QUERY);
    $stmt->bindParam(':countryCode', $country_code, PDO::PARAM_STR, 4);
    $stmt->execute();

    $states = array();

    while($row=$stmt->fetch(PDO::FETCH_ASSOC)) {
      array_push($states, array("id" => $row['district'], "name" => $row['district']));
    }

    return $states;
  }

  function get_cities($country_code, $state_id) {
    global $DB_con;

    $stmt = $DB_con->prepare(self::GET_CITIES_QUERY);
    $stmt->bindParam(':countryCode', $country_code, PDO::PARAM_STR, 4);
    $stmt->bindParam(':district', $state_id, PDO::PARAM_STR, 4);
    $stmt->execute();

    $cities = array();

    while($row=$stmt->fetch(PDO::FETCH_ASSOC)) {
      array_push($cities, array("id" => $row['id'], "name" => $row['name']));
    }

    return $cities;
  }
}

$location = new Location();
?>
