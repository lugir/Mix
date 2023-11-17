(function ($, Drupal) {

  $(document).ready(function(){
    // @see https://stackoverflow.com/a/34899446/21149479
    // Store form state at page load
    let initial_form_state = $('form').serialize();

    // Update form state after form submit
    $('form').submit(function(){
      initial_form_state = $('form').serialize();
    });

    // Check form changes before leaving the page and warn user if needed
    $(window).bind('beforeunload', function(e) {
      let form_state = $('form').serialize();
      if(initial_form_state != form_state){
        // Some browsers are not allow to change the text.
        // Cross-browser compatibility (src: MDN)
        let message = Drupal.t("Changes you made may not be saved.");
        e.returnValue = message;
        return message;
      }
    });
  });

}) (jQuery, Drupal);
