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
            imageParamName: 'image',
            cloudinaryMLoptions: {}, // Options for Cloudinary-ML createMediaLibrary()
            cloudinaryMLshowOptions: {}, // Options for Cloudinary-ML show()
            cldMLid: 0,
            useDerived: true,
            addTmpExtension: false,
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
            window.cloudinary_ml = window.cloudinary_ml || [];
            this.options.cldMLid = this.options.cldMLid || 0;
            if (typeof window.cloudinary_ml[this.options.cldMLid] === "undefined") {
                this.cloudinary_ml = window.cloudinary_ml[this.options.cldMLid] = cloudinary.createMediaLibrary(
                    this.options.cloudinaryMLoptions, {
                        insertHandler: function(data) {
                            $('body').first().css('overflow', 'initial');
                            return widget.cloudinaryInsertHandler(data);
                        }
                    }
                );
            } else {
                this.cloudinary_ml = window.cloudinary_ml[this.options.cldMLid];
            }

        },

        /**
         * Fired on trigger "openMediaLibrary"
         */
        openMediaLibrary: function() {
            this.cloudinary_ml.show(this.options.cloudinaryMLshowOptions);
        },

        /**
         * Fired on trigger "cloudinaryInsertHandler"
         */
        cloudinaryInsertHandler: function(data) {
            var widget = this;

            data.assets.forEach(asset => {
                //console.log(asset);
                if (widget.options.imageUploaderUrl) {
                    asset.asset_url = asset.asset_image_url = asset.secure_url;
                    if (asset.derived && asset.derived[0] && asset.derived[0].secure_url) {
                        asset.asset_derived_url = asset.asset_derived_image_url = asset.derived[0].secure_url;
                        asset.free_transformation = asset.asset_derived_image_url
                            .replace(new RegExp('^.*cloudinary.com/(' + this.options.cloudinaryMLoptions.cloud_name + '/)?' + asset.resource_type + '/' + asset.type + '/'), '')
                            .replace(/\.[^/.]+$/, '')
                            .replace(new RegExp('\/' + asset.public_id + '$'), '')
                            .replace(new RegExp('\/v[0-9]{1,10}$'), '')
                            .replace(new RegExp('\/'), ',');
                        if (widget.options.useDerived) {
                            asset.asset_url = asset.asset_image_url = asset.derived[0].secure_url;
                        }
                    }
                    if (asset.resource_type === "video") {
                        asset.asset_image_url = asset.asset_url.replace(/\.[^/.]+$/, "").replace(new RegExp('\/v[0-9]{1,10}\/'), '/').replace(new RegExp('\/(' + asset.public_id + ')$'), '/so_auto/$1.jpg');
                    }
                    $.ajax({
                        url: widget.options.imageUploaderUrl,
                        data: {
                            asset: asset,
                            remote_image: asset.asset_image_url,
                            param_name: widget.options.imageParamName,
                            form_key: window.FORM_KEY
                        },
                        method: 'POST',
                        dataType: 'json',
                        async: false,
                        showLoader: true
                    }).done(
                        function(file) {
                            if (file.file && !file.error) {
                                var context = (asset.context && asset.context.custom) ? asset.context.custom : {};
                                if (asset.resource_type === "video") {
                                    file.video_provider = 'cloudinary';
                                    file.media_type = "external-video";
                                    file.video_url = asset.asset_url;
                                    file.video_title = context.caption || context.alt || asset.public_id || "";
                                    file.video_description = (context.description || context.alt || context.caption || "").replace(/(&nbsp;|<([^>]+)>)/ig, '');
                                } else {
                                    file.media_type = "image";
                                    file.label = asset.label = context.alt || context.caption || asset.public_id || "";
                                    if (widget.options.addTmpExtension && !/\.tmp$/.test(file.file)) {
                                        file.file = file.file + '.tmp';
                                    }
                                }
                                file.free_transformation = asset.free_transformation;
                                file.asset_derived_image_url = asset.asset_derived_image_url;
                                file.image_url = asset.asset_image_url;
                                file.cloudinary_asset = asset;

                                if (widget.options.triggerSelector && widget.options.triggerEvent) {
                                    $(widget.options.triggerSelector).last().trigger(widget.options.triggerEvent, file);
                                    if (asset.resource_type === "video") {
                                        $(widget.options.triggerSelector).last().find('img[src="' + file.url + '"]').addClass('video-item');
                                    }
                                }
                                if (widget.options.callbackHandler && widget.options.callbackHandlerMethod && typeof widget.options.callbackHandler[widget.options.callbackHandlerMethod] === 'function') {
                                    widget.options.callbackHandler[widget.options.callbackHandlerMethod](file);
                                }
                            } else {
                                alert($.mage.__('An error occured during ' + asset.resource_type + ' insert!'));
                                console.error(file);
                            }

                        }
                    ).fail(
                        function(response) {
                            alert($.mage.__('An error occured during ' + asset.resource_type + ' insert!'));
                            console.error(response);
                        }
                    );
                }
            });
        }
    });

    return $.mage.cloudinaryMediaLibraryModal;
});