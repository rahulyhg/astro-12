$(function() {
  toggleUnknownTimeWrapper();
  if (!!$(".country").val()) {
    getStates();
  } else {
    $("#state-input").hide();
  }

  if (!!$(".country").val() && !!$(".state").val()) {
    getCities();
  } else {
    $("#city-input").hide();
  }

  function getCurrentPath() {
    var loc = document.location.href;
    return loc.substring(0, loc.lastIndexOf('/'));
  }

  function toggleUnknownTimeWrapper() {
    var unknownTime = $("#unknown-time").is(":checked");
    if (unknownTime) {
      $("#unknown-time-wrapper").show();
      $("#known-time-wrapper").hide();
    } else {
      $("#known-time-wrapper").show();
      $("#unknown-time-wrapper").hide();
    }
  }

  function getStates() {
    var countryCode = $(".country").val();
    $(".state").find('option').remove();
    $(".city").find('option').remove();
    $("#state-input").show();
    $("#city-input").hide();
    $.ajax({
      type: "POST",
      url: getCurrentPath() + "/lib/get_states.php",
      data: { countryCode: countryCode },
      cache: false,
      success: function(html) {
        $(".state").html(html);
      }
    });
  }

  function getCities() {
    var countryCode = $(".country").val();
    var state = $(".state").val();
    $(".city").find('option').remove();
    $.ajax({
      type: "POST",
      url: getCurrentPath() + "/lib/get_cities.php",
      data: { countryCode: countryCode, state: state },
      cache: false,
      success: function(html) {
        $("#city-input").show();
        $(".city").html(html); 
      }
    });
  }

  $("#unknown-time").change(toggleUnknownTimeWrapper);
  $('.country').change(getStates);
  $('.state').change(getCities);
});
