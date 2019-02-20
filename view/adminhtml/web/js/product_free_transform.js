define(
    [
        'underscore',
        'uiElement',
        'uiCollection',
        'uiRegistry',
        'jquery'
    ],
    function(_, Element, Collection, registry, $) {
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

            initObservable: function() {
                var self = this;

                this._super();

                this.observe('src freeTransformation hasError error hasChanges hasChangesToSave');

                this.on(
                    'freeTransformation',
                    function() {
                        self.hasChanges(true);
                        self.hasChangesToSave(true);
                    }
                );

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
                }).done(
                    function(response) {
                        self.src(response.url);
                        self.hasError(false);
                    }
                ).fail(
                    function(result) {
                        self.hasError(true);
                        self.error(result.responseJSON.error);
                    }
                );
            }
        });

        return Collection.extend({
            defaults: {
                ajaxUrl: "",
                template: 'Cloudinary_Cloudinary/product/free_transform',
                tableRows: {}
            },

            getTransforms: function() {
                return registry.get('product_form.product_form_data_source').data.product.cloudinary_transforms;
            },

            createRow: function(params) {
                return FreeTransformRow().configure(params);
            },

            insertChildRow: function(params) {
                if (!this.tableRows()[params.file]) {
                    params.ajaxUrl = this.ajaxUrl;
                    var elm = this.createRow(params);
                    this.tableRows()[params.file] = elm;
                    this.insertChild(elm);
                    return elm;
                } else {
                    return this.tableRows()[params.file];
                }
            },

            initObservable: function() {
                var self = this;

                self._super()
                    .observe([
                        'tableRows'
                    ]);

                if (this.getTransforms()) {
                    $.each(this.getTransforms(), function(i, transform) {
                        self.insertChildRow(transform);
                    });
                }

                $(document).on('addItem', '#media_gallery_content', function(event, file) {
                    if (file && file.media_type === 'image' && file.file && file.image_url) {
                        file.id = file.id || file.fileId;
                        file.image_url = file.asset_derived_image_url || file.image_url;
                        self.insertChildRow(file).trigger("freeTransformation");
                    }
                });

                return this;
            },

            afterRender: function() {
                var self = this;

                this.elems.each(
                    function(elem) {
                        elem.ajaxUrl = self.ajaxUrl;
                    }
                );
            }
        });
    }
);