$(document).ready(function(){ 
  var isLive = $("input[name='paynow_mode']:checked").val();

  var serviceKey = $("input[name='paynow_service_key']").val();

  var errorFields = [];

  $("input[name='paynow_mode']").change(function(){
    isLive = $("input[name='paynow_mode']:checked").val();
    if($('#paynowDetailsError').html().length > 0){
      validatePNForm();
    }
  });

  $("input[name='paynow_service_key']").change(function(){
    serviceKey = $("input[name='paynow_service_key']").val();
  });


  $('#paynow__button').on("click" , function(event){
    if(!validatePNForm(true)){
      event.preventDefault();
    }
  });

  function validatePNForm(saveChanges = false){

    if( $('#paynowDetailsError').html().includes("Required: Pay Now Service Key") || saveChanges)
    {

      if(!serviceKey){
        errorFields.push("Service Key ID");
      }

      $("input[name='paynow_service_key']").css('border-color', !serviceKey ? 'rgb(255, 0, 0)' : 'rgb(204, 204, 204)');
    }

    var total = errorFields.length;
    var message = "";
    $.each( errorFields, function ( index, value) {
      if( index == total - 1){
        message += value;
      } else{
        message += value + ", ";
      }
    });
    if( errorFields.length !== 0 ){
      $('#paynowDetailsError').html("Required: " + message);
      $('#paynowDetailsError').css('display', 'block');
      errorFields = [];
      return false;     
    }
    else
    {
      $('#paynowDetailsError').html("");
      return true;
    }
  }
});