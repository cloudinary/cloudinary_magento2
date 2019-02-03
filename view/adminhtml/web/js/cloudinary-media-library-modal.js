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
            triggerEvent: null, // 'addItem' / 'fileuploaddone'
            callbackHandler: null,
            callbackHandlerMethod: null,
            imageParamName: 'image'
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

            if (typeof this.cloudinary_ml === "undefined") {
                this.cloudinary_ml = window.cloudinary_ml = cloudinary.createMediaLibrary(
                    this.options.cloudinaryMLoptions, {
                        insertHandler: function(data) {
                            return widget.cloudinaryInsertHandler(data);
                        }
                    }
                );
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
                switch (asset.resource_type) {
                    case 'image':
                        if (widget.options.imageUploaderUrl) {
                            $.ajax({
                                url: widget.options.imageUploaderUrl,
                                data: {
                                    asset: asset,
                                    remote_image: asset.secure_url,
                                    param_name: widget.options.imageParamName,
                                    form_key: window.FORM_KEY
                                },
                                method: 'POST',
                                dataType: 'json',
                                async: false,
                                showLoader: true
                            }).done(
                                function(file) {
                                    var context = (asset.context && asset.context.custom) ? asset.context.custom : {};
                                    file.fileId = Math.random().toString(36).substr(2, 9);
                                    file.label = context.alt || context.caption || "";
                                    if (widget.options.triggerSelector && widget.options.triggerEvent) {
                                        $(widget.options.triggerSelector).last().trigger(widget.options.triggerEvent, file);
                                    }
                                    if (widget.options.callbackHandler && widget.options.callbackHandlerMethod && typeof widget.options.callbackHandler[widget.options.callbackHandlerMethod] === 'function') {
                                        widget.options.callbackHandler[widget.options.callbackHandlerMethod](file);
                                    }
                                }
                            ).fail(
                                function(response) {
                                    alert($.mage.__('An error occured during image insert!'));
                                    //console.log(response);
                                }
                            );
                        }

                        break;

                    case 'video':
                        if (widget.options.videoUploaderUrl) {
                            asset.video_url = (location.protocol === 'https:') ? asset.secure_url : asset.url;
                            asset.thumbnail = asset.video_url.replace(/\.[^/.]+$/, "").replace(/\/([^\/]+)$/, '/so_auto/$1.jpg');
                            $.ajax({
                                url: widget.options.videoUploaderUrl,
                                data: {
                                    asset: asset,
                                    remote_image: asset.thumbnail,
                                    form_key: window.FORM_KEY
                                },
                                method: 'POST',
                                dataType: 'json',
                                async: false,
                                showLoader: true
                            }).done(
                                function(file) {
                                    var context = (asset.context && asset.context.custom) ? asset.context.custom : {};
                                    file.fileId = Math.random().toString(36).substr(2, 9);
                                    file.video_provider = 'cloudinary';
                                    file.media_type = "external-video";
                                    file.video_url = asset.video_url;
                                    file.video_title = context.caption || context.alt || "";
                                    file.video_description = (context.description || context.alt || context.caption || "").replace(/(&nbsp;|<([^>]+)>)/ig, '');

                                    if (widget.options.triggerSelector && widget.options.triggerEvent) {
                                        $(widget.options.triggerSelector).last().trigger(widget.options.triggerEvent, file);
                                        $(widget.options.triggerSelector).last().find('img[src="' + file.url + '"]').addClass('video-item');
                                    }
                                    if (widget.options.callbackHandler && widget.options.callbackHandlerMethod && typeof widget.options.callbackHandler[widget.options.callbackHandlerMethod] === 'function') {
                                        widget.options.callbackHandler[widget.options.callbackHandlerMethod](file);
                                    }
                                }
                            ).fail(
                                function(response) {
                                    alert($.mage.__('An error occured during video insert!'));
                                    //console.log(response);
                                }
                            );
                        }

                        break;
                }

            });
        }
    });

    return $.mage.cloudinaryMediaLibraryModal;
});