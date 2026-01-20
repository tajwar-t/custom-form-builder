jQuery(document).ready(function ($) {
  $(".cfb-form").on("submit", function (e) {
    e.preventDefault();

    const $form = $(this);
    const formId = $form.data("form-id");
    const $submitBtn = $form.find(".cfb-submit");
    const $message = $form.find(".cfb-message");

    // Create FormData object to handle files
    const formDataObj = new FormData();
    formDataObj.append("action", "cfb_submit_form");
    formDataObj.append("nonce", cfbAjax.nonce);
    formDataObj.append("form_id", formId);

    // Gather form data
    const formData = {};
    $form.find('input:not([type="file"]), textarea, select').each(function () {
      const $field = $(this);
      const name = $field.attr("name");
      if (name) {
        if ($field.attr("type") === "checkbox") {
          if ($field.is(":checked")) {
            if (!formData[name]) formData[name] = [];
            formData[name].push($field.val());
          }
        } else {
          formData[name] = $field.val();
        }
      }
    });

    // Add regular form data
    formDataObj.append("form_data", JSON.stringify(formData));

    // Add file uploads
    $form.find('input[type="file"]').each(function () {
      const $fileInput = $(this);
      const name = $fileInput.attr("name");
      if (this.files && this.files[0]) {
        formDataObj.append(name, this.files[0]);
      }
    });

    // Disable submit button
    $submitBtn.prop("disabled", true).text("Submitting...");
    $message.removeClass("cfb-error cfb-success").empty();

    $.ajax({
      url: cfbAjax.ajaxurl,
      method: "POST",
      data: formDataObj,
      processData: false,
      contentType: false,
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
