jQuery(document).ready(function ($) {
  let currentFields = [];
  let editingFormId = 0;

  // Create new form
  $("#cfb-create-btn").on("click", function () {
    const formName = $("#cfb-form-name").val().trim();
    if (!formName) {
      alert("Please enter a form name");
      return;
    }

    editingFormId = 0;
    currentFields = [];
    $("#cfb-editing-id").val("0");
    $("#cfb-editing-name").text(formName);
    $("#cfb-fields-list").empty();
    $(".cfb-forms-list").hide();
    $(".cfb-create-form").hide();
    $("#cfb-form-editor").show();
  });

  // Add field to form
  $("#cfb-add-field-btn").on("click", function () {
    const fieldType = $("#cfb-field-type").val();
    const fieldLabel = $("#cfb-field-label").val().trim();

    if (!fieldLabel) {
      alert("Please enter a field label");
      return;
    }

    const field = {
      type: fieldType,
      label: fieldLabel,
      options: [],
    };

    // For select, radio, checkbox - ask for options
    if (["select", "radio", "checkbox"].includes(fieldType)) {
      const optionsStr = prompt("Enter options separated by commas:");
      if (optionsStr) {
        field.options = optionsStr.split(",").map((o) => o.trim());
      }
    }

    currentFields.push(field);
    renderFieldsList();
    $("#cfb-field-label").val("");
  });

  // Render fields list
  function renderFieldsList() {
    const $list = $("#cfb-fields-list");
    $list.empty();

    if (currentFields.length === 0) {
      $list.html("<p>No fields added yet. Add your first field above!</p>");
      return;
    }

    currentFields.forEach((field, index) => {
      const $fieldItem = $('<div class="cfb-field-item"></div>');
      const options = field.options || [];
      $fieldItem.html(`
                <span class="cfb-field-info">
                    <strong>${field.label}</strong> (${field.type})
                    ${options.length > 0 ? " - Options: " + options.join(", ") : ""}
                </span>
                <button class="button cfb-remove-field" data-index="${index}">Remove</button>
            `);
      $list.append($fieldItem);
    });
  }

  // Remove field
  $(document).on("click", ".cfb-remove-field", function () {
    const index = $(this).data("index");
    currentFields.splice(index, 1);
    renderFieldsList();
  });

  // Save form
  $("#cfb-save-form-btn").on("click", function () {
    if (currentFields.length === 0) {
      alert("Please add at least one field");
      return;
    }

    const formName = $("#cfb-editing-name").text();
    const formId = $("#cfb-editing-id").val();

    $.ajax({
      url: cfbAjax.ajaxurl,
      method: "POST",
      data: {
        action: "cfb_save_form",
        nonce: cfbAjax.nonce,
        form_id: formId,
        form_name: formName,
        fields: currentFields,
      },
      success: function (response) {
        if (response.success) {
          alert("Form saved successfully!");
          location.reload();
        }
      },
    });
  });

  // Cancel editing
  $("#cfb-cancel-btn").on("click", function () {
    $("#cfb-form-editor").hide();
    $(".cfb-create-form").show();
    $(".cfb-forms-list").show();
    $("#cfb-form-name").val("");
  });

  // Edit form
  $(document).on("click", ".cfb-edit-form", function () {
    const formId = $(this).data("id");

    $.ajax({
      url: cfbAjax.ajaxurl,
      method: "POST",
      data: {
        action: "cfb_get_form",
        nonce: cfbAjax.nonce,
        form_id: formId,
      },
      success: function (response) {
        if (response.success) {
          const form = response.data;
          editingFormId = form.id;
          currentFields = JSON.parse(form.fields);
          $("#cfb-editing-id").val(form.id);
          $("#cfb-editing-name").text(form.name);
          renderFieldsList();
          $(".cfb-forms-list").hide();
          $(".cfb-create-form").hide();
          $("#cfb-form-editor").show();
        }
      },
    });
  });

  // Delete form
  $(document).on("click", ".cfb-delete-form", function () {
    if (!confirm("Are you sure you want to delete this form?")) return;

    const formId = $(this).data("id");

    $.ajax({
      url: cfbAjax.ajaxurl,
      method: "POST",
      data: {
        action: "cfb_delete_form",
        nonce: cfbAjax.nonce,
        form_id: formId,
      },
      success: function (response) {
        if (response.success) {
          alert("Form deleted successfully!");
          location.reload();
        }
      },
    });
  });
});
