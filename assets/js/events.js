jQuery(function($){
  $(document).on('submit', '.westin-test-rsvp-form', function(event){
    event.preventDefault();

    const $form = $(this);
    const $response = $form.find('.westin-test-rsvp-response');

    $response.text('');

    $.post(westinTestEvents.ajaxUrl, $form.serialize())
      .done(function(resp){
        $response.text(resp && resp.data && resp.data.message ? resp.data.message : westinTestEvents.i18n.success);
        $form[0].reset();
      })
      .fail(function(resp){
        const message =
          resp &&
          resp.responseJSON &&
          resp.responseJSON.data &&
          resp.responseJSON.data.message
            ? resp.responseJSON.data.message
            : westinTestEvents.i18n.error;

        $response.text(message);
      });
  });
});
