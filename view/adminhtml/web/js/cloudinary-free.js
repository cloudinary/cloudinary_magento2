define(['jquery'], function ($) {
    'use strict';

    $.widget('cloudinary.cloudinaryFreeTransform', {

        currentTransform: '',

        getTransformText: function() {
            return $(this.options.transformInputFieldId).val();
        },

        getImageHtml: function(src) {
            var id = 'cloudinary_custom_transform_preview_image',
                style = 'width: auto; height: auto; max-width: 500px; max-height: 500px; min-height: 50px;',
                footer = '<p>Image size restricted for viewing purposes</p>';
            return '<img id="' + id + '" src="' + src + '" style="' + style + '" />' + footer;
        },

        getErrorHtml: function(message) {
            return '<ul><li class="admin__field-error">' + message + '</li></ul>';
        },

        updatePreviewImage: function(url) {
            var $image = $('#cloudinary_custom_transform_preview_image');

            if (!$image.length) {
                $('#cloudinary_custom_transform_preview').html(this.getImageHtml(url));
            } else {
                $image.attr('src', url);
            }
        },

        updatePreview: function() {
            var self = this;

            if (!self.isPreviewActive()) {
                return;
            }

            self.currentTransform = self.getTransformText();
            self.setPreviewActiveState(false);

            $.ajax({
                url: this.options.ajaxUrl,
                data: {free: self.getTransformText(), form_key: self.options.ajaxKey},
                type: 'post',
                dataType: 'json',
                showLoader: true
            }).done(function(response) {
                self.updatePreviewImage(response.url);
            }).fail(function(result) {
                $('#cloudinary_custom_transform_preview').html(self.getErrorHtml(result.responseJSON.error));
            });
        },

        setPreviewActiveState: function(state) {
            if (state && (this.currentTransform !== this.getTransformText())) {
                $(this.options.previewButtonId).removeClass('disabled');
            } else {
                $(this.options.previewButtonId).addClass('disabled');
            }
        },

        isPreviewActive: function() {
            return !$(this.options.previewButtonId).hasClass('disabled');
        },

        _create: function () {
            var self = this;

            $(this.options.previewButtonId).on('click', function() { self.updatePreview(); });
            $(this.options.transformInputFieldId).on('change keydown paste input', function() {
                self.setPreviewActiveState(true);
            });
        }

    });

    return $.cloudinary.cloudinaryFreeTransform;
});
