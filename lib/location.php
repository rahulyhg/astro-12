<?php
header('Content-Type: text/html; charset=ISO-8859-1');
include(dirname(__FILE__)."/../config/dbconfig.php");

require_once 'Services/GeoNames.php';

class Location
{
  const GET_COUNTRIES_QUERY = "SELECT code, name FROM country ORDER BY name";
  const GET_STATES_QUERY = "SELECT DISTINCT district FROM city WHERE CountryCode = :countryCode ORDER BY district";
  const GET_CITIES_QUERY = "SELECT id, name FROM city WHERE CountryCode = :countryCode AND district = :district ORDER BY name";
  const GEONAMES_USER = "rominga";

  function locationCmp($loc_1, $loc_2) {
    return strcmp($loc_1['name'], $loc_2['name']);
  }

  function get_countries() {
    $geonames = new Services_GeoNames(self::GEONAMES_USER);

    $countries = array();
    $country_info = $geonames->countryInfo(array('lang' => 'es', 'charset' => 'ISO-8859-1'));

    foreach ($country_info as $country) {
      array_push($countries, array("id" => $country->geonameId, "name" => $country->countryName));
    }
    
    /*
    global $DB_con;

    $stmt = $DB_con->prepare(self::GET_COUNTRIES_QUERY);
    $stmt->execute();

    while($row=$stmt->fetch(PDO::FETCH_ASSOC)) {
      array_push($countries, array("code" => $row['code'], "name" => $row['name']));
    }
    */
    usort($countries, "self::locationCmp");
    return $countries;
  }

  function get_states($country_code, $country_name) {
    $states = array();

    try {
      $geonames = new Services_GeoNames(self::GEONAMES_USER);
      $children = $geonames->children(array('geonameId' => $country_code, 'lang' => 'es'));

      foreach ($children as $child) {
        array_push($states, array("id" => $child->geonameId, "name" => mb_convert_encoding($child->name, "ISO-8859-1")));
      }
    } catch(Exception $e) {
      array_push($states, array("id" => $country_code, "name" => mb_convert_encoding($country_name, "ISO-8859-1")));
    }

    /*
    global $DB_con;
    
    $stmt = $DB_con->prepare(self::GET_STATES_QUERY);
    $stmt->bindParam(':countryCode', $country_code, PDO::PARAM_STR, 4);
    $stmt->execute();

    while($row=$stmt->fetch(PDO::FETCH_ASSOC)) {
      array_push($states, array("id" => $row['district'], "name" => $row['district']));
    }
    */

    usort($states, "self::locationCmp");
    return $states;
  }

  function get_cities($state_id, $state_name) {
    /*
    global $DB_con;

    $stmt = $DB_con->prepare(self::GET_CITIES_QUERY);
    $stmt->bindParam(':countryCode', $country_code, PDO::PARAM_STR, 4);
    $stmt->bindParam(':district', $state_id, PDO::PARAM_STR, 4);
    $stmt->execute();

    while($row=$stmt->fetch(PDO::FETCH_ASSOC)) {
      array_push($cities, array("id" => $row['id'], "name" => $row['name']));
    }
    */

    $cities = array();

    try {
      $geonames = new Services_GeoNames(self::GEONAMES_USER);
      $children = $geonames->children(array('geonameId' => $state_id, 'lang' => 'es'));

      foreach ($children as $child) {
        array_push($cities, array("id" => $child->geonameId, "name" => mb_convert_encoding($child->name, "ISO-8859-1")));
      }
    } catch(Exception $e) {
      array_push($cities, array("id" => $state_id, "name" => mb_convert_encoding($state_name, "ISO-8859-1")));
    }

    usort($cities, "self::locationCmp");
    return $cities;
  }
}

$location = new Location();
?>
