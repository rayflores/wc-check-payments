jQuery(function ($) {
  wcCP.currentAction = null;
  wcCP.transactionSucceeded = false;

  var currentPanelcp = $("#wc_check_payment [data-cp-current-panel]").data(
    "cp-panel"
  );
  var previousPanelcp = "cp-main";

  wcCP.$main = $("#wc_check_payment #wc-cp-main");
  wcCP.$checkDate = $("#wc-cp-main #check_date");
  wcCP.$checkNumber = $("#wc-cp-main #check_number");
  wcCP.order_id = $("#wc_check_payment #order_id").val();

  wcCP.$checkAmount = $("#wc_check_payment #check_amount");

  var $checkBtn = $("#wc_check_payment #cp-check-btn");

  function init() {
    $("#post, #order").on(
      "change",
      ":input:not(#wc_check_payment *)",
      warningOrderUnsavedChanges
    );

    $("#wc_check_payment [data-cpopen-panel]").on("click", cpopenPanel);
    $("#wc_check_payment [data-cpclose-panel]").on("click", cpclosePanel);

    // $(document).on("click", '[data-toggle="collapse"]', toggleCollapse);

    wcCP.$checkAmount.on("input", updateProcessButton);
    updateProcessButton();

    $("#check").on("keypress", checkPressEnter);
    $checkBtn.on("click", submitCheck);
  }

  function updateProcessButton() {
    var cpAmount = wcCP.$checkAmount.val();

    $checkBtn.text(
      "Process " + wcCP.formatCPMoney(cpAmount > 0 ? cpAmount : 0)
    );
  }

  wcCP.formatCPMoney = function (cpAmount, params) {
    return accounting.formatMoney(
      cpAmount,
      $.extend(
        {
          symbol: wcCP.currencySymbol,
          decimal: woocommerce_admin_meta_boxes.currency_format_decimal_sep,
          thousand: woocommerce_admin_meta_boxes.currency_format_thousand_sep,
          precision: 2,
          format: woocommerce_admin_meta_boxes.currency_format,
        },
        params
      )
    );
  };

  function warningOrderUnsavedChanges() {
    globalNoticeCP(
      "warning",
      "It looks like you've edited the order. " +
        "If you process a transaction before saving the order, your changes will be lost.",
      null,
      true
    );
  }

  function cpopenPanel() {
    previousPanelcp = currentPanelcp;
    currentPanelcp = this.dataset.cpopenPanel;

    $('[data-cp-panel="' + currentPanelcp + '"]').slideDown();
    $('[data-cp-panel="' + previousPanelcp + '"]').slideUp();
  }

  function cpclosePanel() {
    oldPreviousPanelcp = previousPanelcp;
    previousPanelcp = currentPanelcp;
    currentPanelcp = oldPreviousPanelcp;

    $('[data-cp-panel="' + previousPanelcp + '"]').slideUp();
    $('[data-cp-panel="' + currentPanelcp + '"]').slideDown();
  }

  function globalNoticeCP(type, message, details, isDismissible, raw) {
    notice(
      $("#wc_check_payment .cp-global-notice"),
      type,
      message,
      details,
      isDismissible,
      raw
    );
  }
  function checkPressEnter(event) {
    if (event.keyCode == 13) {
      event.preventDefault();

      submitCheck();
    }
  }
  function submitCheck() {
    if (wcCP.currentAction) {
      console.log("wcCP.currentAction", wcCP.currentAction);
      return;
    }

    wcCP.currentAction = "process_check_payment";
    wcCP.transactionSucceeded = false;

    // if (!valid()) return;

    wcCP.blockUIOverlay();

    wcCP.beginProcessingCheck();
  }

  wcCP.blockUIOverlay = function () {
    $("#wc_check_payment").block({
      message: null,
      overlayCSS: {
        background: "#fff",
        opacity: 0.6,
      },
    });
  };

  wcCP.beginProcessingCheck = function () {
    var data = {
      action: "process_check_payment",
      order_id: wcCP.order_id,
      nonce: wcCP.cpnonce,
      check_date: wcCP.$checkDate.val(),
      check_number: wcCP.$checkNumber.val(),
      check_amount: wcCP.$checkAmount.val(),
    };
    console.log("data", data);
    $.ajax({
      type: "POST",
      url: wcCP.ajax_url,
      data: data,
      success: function (response) {
        wcCP.endProcessingCheck(response);
      },
      error: function (jqXHR, textStatus, errorThrown) {
        wcCP.endProcessingCheck({
          success: false,
          message: errorThrown,
        });
      },
    });
  };

  wcCP.endProcessingCheck = function (response) {
    wcCP.transactionSucceeded = response.success;

    if (response.success) {
      wcCP.$checkDate.val("");
      wcCP.$checkNumber.val("");
      wcCP.$checkAmount.val("");

      //   globalNoticeCP("success", response.message, null, true);
      console.log("response:success", response);
    } else {
      //   globalNoticeCP("error", response.message, null, true);
      console.log("response:error", response);
    }

    wcCP.currentAction = null;
    wcCP.$checkAmount.focus();

    $("#wc_check_payment").unblock();
  };

  init();
});

