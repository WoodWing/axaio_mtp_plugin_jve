var ww_cartoon = {};
var current = 'latest';

if(Drupal.jsEnabled) {
  $(document).ready(
    function() {
      $("a#navleft").click(ww_cartoon.next);
      $("a#navright").click(ww_cartoon.previous);
    }
  );

  ww_cartoon.next = function() { 
    $.get(Drupal.settings.ww_cartoon.json_url + '?type=next&date=' + current, function(data) {
      response = Drupal.parseJson(data);
      
      if(!response.status || response.status == 0) {
        if(response.cartoon) {
          current = response.cartoon.date;
      
          $("img#cartoonplaceholder").attr("src", response.cartoon.image);
        }
      }
    });
  }
  
  ww_cartoon.previous = function() { 
    $.get(Drupal.settings.ww_cartoon.json_url + '?type=previous&date=' + current, function(data) {
      response = Drupal.parseJson(data);
      
      if(!response.status || response.status == 0) {
      	if(response.cartoon) {
          current = response.cartoon.date;
      
          $("img#cartoonplaceholder").attr("src", response.cartoon.image);
        }
      }
    });
  }
}