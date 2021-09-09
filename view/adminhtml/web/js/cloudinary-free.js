define(
    [
        'jquery'
    ],
    function($) {
        'use strict';

        $.widget(
            'cloudinary.cloudinaryFreeTransform', {

                currentTransform: '',
                currentTransformProducts: '',

                getTransformText: function() {
                    return $(this.options.transformInputFieldId).val() || '';
                },

                getTransformProductsText: function() {
                    return $(this.options.transformInputProductsFieldId).val() || '';
                },

                getTransformBehavior: function() {
                    return $(this.options.transformInputProductsBehaviorFieldId).val();
                },

                getImageHtml: function(src, header) {
                    if (!src) {
                        return '';
                    }
                    var cls = 'cloudinary_custom_transform_preview_image',
                        style = 'width: auto; height: auto; max-width: 350px; max-height: 350px; min-height: 50px;',
                        header = header || '',
                        footer = '<p>Image size restricted for viewing purposes</p>';
                    return header + '<img class="' + cls + '" src="' + src + '" style="' + style + '" />' + footer;
                },

                getErrorHtml: function(message) {
                    return '<ul><li class="admin__field-error">' + message + '</li></ul>';
                },

                updatePreviewImage: function(url, url2) {
                    $('#cloudinary_custom_transform_preview').html(
                        this.getImageHtml(url, '<hr><p><b>Global Custom Transformation Preview</b></p>') +
                        this.getImageHtml(url2, '<hr><p><b>Products Custom Transformation Preview</b></p>')
                    );
                },

                updatePreview: function() {
                    var self = this,
                        transformations_string = "";

                    if (!self.isPreviewActive()) {
                        return;
                    }

                    self.currentTransform = self.getTransformText();
                    self.currentTransformProducts = self.getTransformProductsText();
                    self.setPreviewActiveState(false);

                    $.ajax({
                        url: this.options.ajaxUrl,
                        data: {
                            free: self.currentTransform,
                            form_key: self.options.ajaxKey
                        },
                        type: 'post',
                        dataType: 'json',
                        showLoader: true
                    }).done(
                        function(response) {
                            if ((transformations_string = self.currentTransformProducts)) {
                                if (self.getTransformBehavior() === 'add') {
                                    transformations_string = self.currentTransform + ',' + transformations_string;
                                }
                                var globalResURL = response.url;
                                $.ajax({
                                    url: self.options.ajaxUrl,
                                    data: {
                                        free: transformations_string,
                                        form_key: self.options.ajaxKey
                                    },
                                    type: 'post',
                                    dataType: 'json',
                                    showLoader: true
                                }).done(
                                    function(response) {
                                        self.updatePreviewImage(globalResURL, response.url);
                                    }
                                ).fail(
                                    function(result) {
                                        $('#cloudinary_custom_transform_preview').html(self.getErrorHtml(result.responseJSON.error));
                                    }
                                );
                            } else {
                                return self.updatePreviewImage(response.url);
                                transformations_string = self.getTransformText();
                            }
                        }
                    ).fail(
                        function(result) {
                            $('#cloudinary_custom_transform_preview').html(self.getErrorHtml(result.responseJSON.error));
                        }
                    );
                },

                setPreviewActiveState: function(state) {
                    if (
                        state &&
                        (this.currentTransform !== this.getTransformText() || self.currentTransformProducts !== this.getTransformProductsText())
                    ) {
                        $(this.options.previewButtonId).removeClass('disabled');
                    } else {
                        $(this.options.previewButtonId).addClass('disabled');
                    }
                },

                isPreviewActive: function() {
                    return !$(this.options.previewButtonId).hasClass('disabled');
                },

                _create: function() {
                    var self = this;

                    $(this.options.previewButtonId).on(
                        'click',
                        function() {
                            self.updatePreview();
                        }
                    );
                    $(this.options.transformInputFieldId).on(
                        'change keydown paste input',
                        function() {
                            self.setPreviewActiveState(true);
                        }
                    );
                    $(this.options.transformInputProductsFieldId).on(
                        'change keydown paste input',
                        function() {
                            self.setPreviewActiveState(true);
                        }
                    );
                }

            }
        );

        return $.cloudinary.cloudinaryFreeTransform;
    }
);