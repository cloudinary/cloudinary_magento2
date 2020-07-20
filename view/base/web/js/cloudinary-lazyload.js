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
            options = this.mergeOptions(this.options, options);
            this.cldLazyloadInit(options);
            setInterval(function() {
                widget.cldLazyloadInit(options);
            }, 4000);
        },

        cldLazyloadInit: function(options) {
            if ($(".cloudinary-lazyload").length) {
                var widget = this;
                try {
                    $(".cloudinary-lazyload").lazyload(options || widget.options);
                    $(".cloudinary-lazyload").addClass("cloudinary-lazyload-processed").removeClass("cloudinary-lazyload");
                } catch (err) {
                    console.warn("Notice: An error occured while initializing Lazyload (" + err + "). Trying to fix automatically...");
                    $(".cloudinary-lazyload").each(function() {
                        if ($(this).is("img") || $(this).is("iframe")) {
                            $(this).attr("src", $(this).attr("data-original"));
                        } else {
                            $(this).css("background-image", "url('" + $(this).attr("data-original") + "')");
                        }
                        $(this).addClass("cloudinary-lazyload-processed").removeClass("cloudinary-lazyload");
                    });
                }
            }
        },

        /**
         * @param obj1
         * @param obj2
         * @returns object
         */
        mergeOptions: function(o1, o2) {
            var o1 = o1 || {};
            var o2 = o2 || {};
            var o3 = {};
            for (var attr in o1) {
                o3[attr] = o1[attr];
            }
            for (var attr in o2) {
                o3[attr] = o2[attr];
            }
            return o3;
        }

    });

    return $.mage.cloudinaryLazyload;
});