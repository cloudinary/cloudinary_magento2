define([
    'jquery',
    'jquery.lazyload'
], function($) {
    'use strict';

    $.widget('mage.cloudinaryLazyload', {

        options: {
            threshold: 500,
            failure_limit: 0,
            event: "scroll",
            effect: "fadeIn",
            data_attribute: "original",
            skip_invisible: true,
            appear: null,
            load: null,
            placeholder: "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsQAAA7EAZUrDhsAAAANSURBVBhXYzh8+PB/AAffA0nNPuCLAAAAAElFTkSuQmCC"
        },

        /**
         * @private
         */
        _create: function() {
            this._super();
            this.initialize();
        },

        initialize: function(options) {
            var widget = this;
            options = $.extend({}, widget.options, options || {});
            if ($(".cloudinary-lazyload").length) {
                try {
                    $(".cloudinary-lazyload").lazyload(widget.options);
                } catch (err) {
                    console.warn("Notice: An error occured while initializing Lazyload (" + err + "). Trying to fix automatically...");
                    $(".cloudinary-lazyload").each(function() {
                        if ($(this).is("img") || $(this).is("iframe")) {
                            $(this).attr("src", $(this).attr("data-original"));
                        } else {
                            $(this).css("background-image", "url('" + $(this).attr("data-original") + "')");
                        }
                        $(this).removeClass("cloudinary-lazyload");
                    });
                }
            }

        }

    });

    return $.mage.cloudinaryLazyload;
});
