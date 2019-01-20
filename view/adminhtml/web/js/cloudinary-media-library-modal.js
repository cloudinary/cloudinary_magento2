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

        options: {
            buttonSelector: null,
            triggerSelector: null, // #media_gallery_content .image.image-placeholder > .uploader' / '.media-gallery-modal'
            triggerEvent: null // 'addItem' / 'fileuploaddone'
        },

        /**
         * Bind events
         * @private
         */
        _bind: function() {
            if ($(this.options.buttonSelector).length) {
                $(this.options.buttonSelector).on('click', this.openMediaLibrary.bind(this));
            } else {
                this.element.on('click', this.openMediaLibrary.bind(this));
            }
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
            var widget = this;

            data.assets.forEach(asset => {
                if (asset.resource_type === 'image' && widget.options.imageUploaderUrl) {
                    $.ajax({
                        url: widget.options.imageUploaderUrl,
                        data: {
                            asset: asset,
                            form_key: window.FORM_KEY
                        },
                        method: 'POST',
                        dataType: 'json',
                        async: false,
                        showLoader: true
                    }).done(
                        function(file) {
                            file.fileId = Math.random().toString(36).substr(2, 9);
                            if (widget.options.triggerSelector && widget.options.triggerEvent) {
                                $(widget.options.triggerSelector).last().trigger(widget.options.triggerEvent, file);
                            }
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