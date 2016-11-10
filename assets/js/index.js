$(function() {

  $('.country').change(function() {
    var countryCode = $(this).val();
    $(".state").find('option').remove();
    $(".city").find('option').remove();
    $.ajax({
      type: "POST",
      url: document.location.href + "lib/get_states.php",
      data: { countryCode: countryCode },
      cache: false,
      success: function(html) { 
        $(".state").html(html); 
      }
    });
  });

  $('.state').change(function() {
    var countryCode = $(".country").val();
    var state = $(this).val();
    $(".city").find('option').remove();
    $.ajax({
      type: "POST",
      url: document.location.href + "lib/get_cities.php",
      data: { countryCode: countryCode, state: state },
      cache: false,
      success: function(html) {
        $(".city").html(html); 
      }
    });
  });
});
