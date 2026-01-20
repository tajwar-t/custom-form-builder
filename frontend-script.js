jQuery(document).ready(function ($) {
  $(".cfb-form").on("submit", function (e) {
    e.preventDefault();

    const $form = $(this);
    const formId = $form.data("form-id");
    const $submitBtn = $form.find(".cfb-submit");
    const $message = $form.find(".cfb-message");

    // Gather form data
    const formData = {};
    $form.find("input, textarea, select").each(function () {
      const $field = $(this);
      const name = $field.attr("name");
      if (name) {
        formData[name] = $field.val();
      }
    });

    // Disable submit button
    $submitBtn.prop("disabled", true).text("Submitting...");
    $message.removeClass("cfb-error cfb-success").empty();

    $.ajax({
      url: cfbAjax.ajaxurl,
      method: "POST",
      data: {
        action: "cfb_submit_form",
        nonce: cfbAjax.nonce,
        form_id: formId,
        form_data: formData,
      },
      success: function (response) {
        if (response.success) {
          $message.addClass("cfb-success").text(response.data.message);
          $form[0].reset();
        } else {
          $message
            .addClass("cfb-error")
            .text("Something went wrong. Please try again.");
        }
      },
      error: function () {
        $message
          .addClass("cfb-error")
          .text("Connection error. Please try again.");
      },
      complete: function () {
        $submitBtn.prop("disabled", false).text("Submit");
      },
    });
  });
});
