require(
    [
        'jquery',
        'mage/translate',
        "mage/calendar"
    ],
    function ($) {

        jQuery(".cocote-section").parent().prev().addClass("cocote-tab");
        cocoteTimeoutConfig = setInterval(function(){if(jQuery(".cocote-section").length) {jQuery(".cocote-section").parent().prev().addClass("cocote-tab");clearInterval(cocoteTimeoutConfig);}}, 1000);

        jQuery("#cocote_generate_path").change(function() {
            jQuery("#cocote_generate_generate").hide();
            jQuery("#cocote_generate_generate_message").show();
        });

        if(jQuery("#cocote_general_store").length==1) {
            jQuery("#row_cocote_general_store").hide();
        }
    }
);

