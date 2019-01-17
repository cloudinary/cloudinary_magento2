define([
    'jquery',
    'productGallery',
    'jquery/ui',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'mage/backend/tree-suggest',
    'mage/backend/validation',
    'cloudinaryMediaLibraryAll',
    'es6Promise'
], function($, productGallery) {
    'use strict';

    $.widget('mage.cloudinaryMediaLibraryModal', {

        /**
         * Bind events
         * @private
         */
        _bind: function() {
            $('.add-from-cloudinary-button[data-role="add-from-cloudinary-button"]').on('click', this.openMediaLibrary.bind(this));
        },

        /**
         * @private
         */
        _create: function() {
            this._super();
            this._bind();

            var widget = this;

            if (typeof window.cloudinary_ml === "undefined") {
                this.cloudinary_ml = window.cloudinary_ml = cloudinary.createMediaLibrary(
                    this.options.cloudinaryMLoptions, {
                        insertHandler: function(data) {
                            return widget.cloudinaryInsertHandler(data);
                        }
                    }
                );
            } else {
                this.cloudinary_ml = window.cloudinary_ml;
            }

        },

        /**
         * Fired on trigger "openMediaLibrary"
         */
        openMediaLibrary: function() {
            this.cloudinary_ml.show();
        },

        /**
         * Fired on trigger "cloudinaryInsertHandler"
         */
        cloudinaryInsertHandler: function(data) {
            //console.log("Inserted assets:", JSON.stringify(data.assets, null, 2));
            data.assets.forEach(asset => {
                if (asset.resource_type === 'image') {
                    $.ajax({
                        url: this.options.uploaderUrl,
                        data: {
                            asset: asset,
                            form_key: window.FORM_KEY
                        },
                        method: 'POST',
                        dataType: 'json',
                        async: false,
                        showLoader: true
                    }).done(
                        function(response) {
                            //console.log(response);
                            $("#media_gallery_content .image.image-placeholder > .uploader").last().trigger('addItem', response);
                        }
                    ).fail(
                        function(response) {
                            alert($.mage.__('An error occured during image insert!'));
                            //console.log(response);
                        }
                    );
                }
            });
        }
    });

    return $.mage.cloudinaryMediaLibraryModal;
});