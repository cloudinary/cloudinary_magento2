define([
    'jquery',
    'productGallery',
    'Magento_Ui/js/modal/alert',
    'mage/backend/notification',
    'mage/translate',
    'jquery/ui',
    'Magento_Ui/js/modal/modal',
    'mage/backend/tree-suggest',
    'mage/backend/validation',
    'es6Promise'
], function($, productGallery, uiAlert, notification, $t) {
    'use strict';

    $.widget('mage.cloudinarySpinsetModal', {

        loadedResource: null,

        options: {
            buttonSelector: '',
            modalSelector: '#cldspinset-modal',
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
                $(this.options.buttonSelector).on('click', this.openSpinsetModal.bind(this));
            } else {
                this.element.on('click', this.openSpinsetModal.bind(this));
            }
        },

        /**
         * @param {Array} messages
         */
        notifyError: function(messages) {
            var data = {
                content: messages.join('')
            };
            if (messages.length > 1) {
                data.modalClass = '_image-box';
            }
            uiAlert(data);
            return this;
        },

        /**
         */
        noPreviewErrorMessage: function(err) {
            if (err) {
                console.error(err);
            }
            this.loadedResource = null;
            $('.new-cldspinset-save-button').prop('disabled', true);
            if ($('#cldspinset-preview-img').length) {
                $('#cldspinset-preview-img').remove();
            }
            var aggregatedErrorMessages = [];
            notification().add({
                error: true,
                message: $t("No spin set exists for the given tag. Ensure you have uploaded it to Cloudinary correctly, or try again with a different tag name."),
                insertMethod: function(constructedMessage) {
                    aggregatedErrorMessages.push(constructedMessage);
                }
            });
            if (aggregatedErrorMessages.length) {
                this.notifyError(aggregatedErrorMessages);
            }
        },



        /**
         * @private
         */
        _create: function() {
            this._super();
            this._bind();

            var widget = this;

            widget.cldspinsetDialog = $(widget.options.modalSelector);
            //this.cldspinsetDialog.mage('newCldSpinsetDialog', this.cldspinsetDialog.data('modalInfo'));

            $(document).on('change', '#cldspinset-modal [name="new_cldspinset"]', function() {
                var spintetTag = $('#cldspinset-modal [name="new_cldspinset"]').val();
                if (!spintetTag) {
                    $('.new-cldspinset-save-button').prop('disabled', true);
                    if ($('#cldspinset-preview-img').length) {
                        $('#cldspinset-preview-img').remove();
                    }
                    return;
                } else {
                    try {
                        $.ajax({
                            method: "GET",
                            url: '/rest/V1/cloudinary/resources/tag',
                            dataType: 'json',
                            data: {
                                id: spintetTag,
                                max_results: 1
                            },
                            showLoader: true,
                            timeout: 15000,
                            success: function(res) {
                                res = JSON.parse(res);
                                if (!res || res.error || !res.data || !res.data[0] || res.data[0].resource_type !== "image") {
                                    widget.noPreviewErrorMessage(res);
                                } else {
                                    res.data[0].cldspinset = spintetTag;
                                    widget.loadedResource = res.data[0];
                                    $('.new-cldspinset-save-button').prop('disabled', false);
                                    $('<img id="cldspinset-preview-img" src="" alt="Cloudinary spinset preview image"/>').attr('src', res.data[0].secure_url).prependTo('#cldspinset-preview');
                                }
                            },

                            /**
                             * @private
                             */
                            error: function(err) {
                                widget.noPreviewErrorMessage(err);
                            }
                        });
                    } catch (err) {
                        widget.noPreviewErrorMessage(err);
                    }
                }
            });
        },

        /**
         * Fired when click on save
         *
         * @private
         */
        _onModalSave: function() {
            if (this.loadedResource) {
                $('.new-cldspinset-save-button').prop('disabled', true);
                $("body").trigger('processStart');
                this.cldspinsetInsertHandler(this.loadedResource);
            }
        },

        /**
         * Fired when click on create video
         *
         * @private
         */
        _onModalCancel: function() {
            this.cldspinsetDialog.modal('closeModal');
        },

        /**
         * Fired on trigger "openSpinsetModal"
         */
        openSpinsetModal: function() {
            var widget = this;
            this.cldspinsetDialog.modal({
                type: 'slide',
                //appendTo: this._gallery,
                modalClass: 'cldspinset-dialog form-inline',
                title: $.mage.__('Add Spinset from Cloudinary'),
                buttons: [{
                        text: $.mage.__('Save'),
                        class: 'action-primary new-cldspinset-save-button',
                        click: $.proxy(widget._onModalSave, widget)
                    },
                    {
                        text: $.mage.__('Cancel'),
                        class: 'new-cldspinset-cancel-button',
                        click: $.proxy(widget._onModalCancel, widget)
                    }
                ],

                /**
                 * @returns {null}
                 */
                opened: function() {
                    this.loadedResource = null;
                    $('.new-cldspinset-save-button').prop('disabled', true);
                    $('#cldspinset-modal [name="new_cldspinset"]').val('');
                    if ($('#cldspinset-preview-img').length) {
                        $('#cldspinset-preview-img').remove();
                    }
                },

                /**
                 * Closed
                 */
                closed: function() {
                    this.loadedResource = null;
                    $('.new-cldspinset-save-button').prop('disabled', true);
                    $('#cldspinset-modal [name="new_cldspinset"]').val('');
                    if ($('#cldspinset-preview-img').length) {
                        $('#cldspinset-preview-img').remove();
                    }
                }
            });
            this.cldspinsetDialog.modal('openModal');
        },

        cldspinsetInsertHandler: function(asset) {
            try {
                var widget = this;
                var aggregatedErrorMessages = [];

                if (widget.options.imageUploaderUrl) {
                    asset.asset_url = asset.asset_image_url = asset.secure_url;
                    asset.free_transformation = "";
                    if (asset.derived && asset.derived[0] && asset.derived[0].secure_url) {
                        asset.asset_derived_url = asset.asset_derived_image_url = asset.derived[0].secure_url;
                        asset.free_transformation = (asset.derived[0].hasOwnProperty('raw_transformation')) ?
                            asset.derived[0].raw_transformation :
                            asset.asset_derived_image_url
                            .replace(new RegExp('^.*cloudinary.com/(' + this.options.cloudinaryMLoptions.cloud_name + '/)?' + asset.resource_type + '/' + asset.type + '/'), '')
                            .replace(/\.[^/.]+$/, '')
                            .replace(new RegExp('\/' + asset.public_id + '$'), '')
                            .replace(new RegExp('\/v[0-9]{1,10}$'), '')
                            .replace(new RegExp('\/'), ',');
                        if (widget.options.useDerived) {
                            asset.asset_url = asset.asset_image_url = asset.derived[0].secure_url;
                        }
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
                                file.media_type = "image";
                                file.label = asset.label = context.alt || context.caption || asset.public_id || "";
                                if (widget.options.addTmpExtension && !/\.tmp$/.test(file.file)) {
                                    file.file = file.file + '.tmp';
                                }
                                file.cldspinset = asset.cldspinset;
                                file.free_transformation = asset.free_transformation;
                                file.asset_derived_image_url = asset.asset_derived_image_url;
                                file.image_url = asset.asset_image_url;
                                file.cloudinary_asset = asset;

                                if (widget.options.triggerSelector && widget.options.triggerEvent) {
                                    $(widget.options.triggerSelector).last().trigger(widget.options.triggerEvent, file);
                                }
                                if (widget.options.callbackHandler && widget.options.callbackHandlerMethod && typeof widget.options.callbackHandler[widget.options.callbackHandlerMethod] === 'function') {
                                    widget.options.callbackHandler[widget.options.callbackHandlerMethod](file);
                                }
                                $("body").trigger('processStop');
                                widget.cldspinsetDialog.modal('closeModal');
                            } else {
                                $("body").trigger('processStop');
                                $('.new-cldspinset-save-button').prop('disabled', false);
                                console.error(file);
                                notification().add({
                                    error: true,
                                    message: $t('An error occured during ' + asset.resource_type + ' insert (' + asset.public_id + ')!') + '%s%sError: ' + file.error.replace(/File:.*$/, ''),
                                    insertMethod: function(constructedMessage) {
                                        aggregatedErrorMessages.push(constructedMessage.replace('%s%s', '<br>'));
                                    }
                                });
                            }
                            if (aggregatedErrorMessages.length) {
                                widget.notifyError(aggregatedErrorMessages);
                            }
                        }
                    ).fail(
                        function(response) {
                            $("body").trigger('processStop');
                            $('.new-cldspinset-save-button').prop('disabled', false);
                            console.error(response);
                            notification().add({
                                error: true,
                                message: $t('An error occured during ' + asset.resource_type + ' insert (' + asset.public_id + ')!')
                            });
                            if (aggregatedErrorMessages.length) {
                                widget.notifyError(aggregatedErrorMessages);
                            }
                        }
                    );
                }
            } catch (e) {
                $("body").trigger('processStop');
                $('.new-cldspinset-save-button').prop('disabled', false);
                console.error(e);
                notification().add({
                    error: true,
                    message: $t('An error occured during ' + asset.resource_type + ' insert! ' + e)
                });
                if (aggregatedErrorMessages.length) {
                    widget.notifyError(aggregatedErrorMessages);
                }
            }

        }
    });

    return $.mage.cloudinarySpinsetModal;
});