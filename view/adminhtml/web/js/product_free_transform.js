define([
    'underscore',
    'uiElement',
    'uiCollection',
    'uiRegistry',
    'jquery'
], function (_, Element, Collection, registry, $) {
    'use strict';

    var FreeTransformRow = Element.extend({
        defaults: {
            id: 0,
            src: "",
            label: "",
            file: "",
            freeTransformation: "",
            hasChanges: false,
            hasChangesToSave: false,
            error: "",
            hasError: false,
            ajaxUrl: "",
            template: 'Cloudinary_Cloudinary/product/free_transform_row'
        },

        initObservable: function () {
            var self = this;

            this._super();

            this.observe('src freeTransformation hasError error hasChanges hasChangesToSave');

            this.on('freeTransformation', function() {
                self.hasChanges(true);
                self.hasChangesToSave(true);
            });

            return this;
        },

        configure: function(params) {
            this.id = params.id || 0;
            this.label = params.label || "";
            this.file = params.file || "";
            this.ajaxUrl = params.ajaxUrl || "";
            this.src(params.image_url || "");
            this.freeTransformation(params.free_transformation || "");
            this.hasChanges(false);

            return this;
        },

        inputName: function() {
            return 'product[cloudinary_free_transform][' + this.id + ']';
        },

        changesName: function() {
            return 'product[cloudinary_free_transform_changes][' + this.id + ']';
        },

        imageSrcForTransform: function(transform) {
            return 'http://res.cloudinary.com/m2501/image/upload/' + transform + '/sample.jpg';
        },

        refreshImage: function() {
            var self = this;

            self.hasChanges(false);

            $.ajax({
                url: self.ajaxUrl,
                data: {
                    free: self.freeTransformation(),
                    form_key: window.FORM_KEY,
                    image: self.file
                },
                type: 'post',
                dataType: 'json',
                showLoader: true
            }).done(function(response) {
                self.src(response.url);
                self.hasError(false);
            }).fail(function(result) {
                self.hasError(true);
                self.error(result.responseJSON.error);
            });
        }
    });

    return Collection.extend({
        defaults: {
            ajaxUrl: "",
            template: 'Cloudinary_Cloudinary/product/free_transform'
        },

        getTransforms: function() {
            return registry.get('product_form.product_form_data_source').data.product.cloudinary_transforms;
        },

        createRow: function(params) {
            return FreeTransformRow().configure(params);
        },

        initObservable: function () {
            var self = this;

            this._super();

            this.getTransforms().each(function(transform) {
                self.insertChild(self.createRow(transform));
            });

            return this;
        },

        afterRender: function() {
            var self = this;

            this.elems.each(function (elem) {
                elem.ajaxUrl = self.ajaxUrl;
            });
        }
    });
});
