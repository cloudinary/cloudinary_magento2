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
                origFreeTransformation: "",
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
                this.origFreeTransformation = params.free_transformation || "";
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

                if (/\.tmp$/.test(self.file)) {
                    self.src(self.src().replace(new RegExp('\/image\/upload(\/v[0-9]{1,10})?(\/' + this.origFreeTransformation + ')?(\/v[0-9]{1,10})?\/'), '/image/upload/' + self.freeTransformation() + '/'));
                    this.origFreeTransformation = self.freeTransformation();
                    self.hasError(false);
                    return true;
                }

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
                if (!this.tableRows()[params.id]) {
                    params.ajaxUrl = this.ajaxUrl;
                    var elm = this.createRow(params);
                    this.tableRows()[params.id] = elm;
                    this.insertChild(elm);
                    return elm;
                } else {
                    return this.tableRows()[params.id];
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
                    if (file && (file.media_type === 'image') && file.file && (file.image_url || file.url)) {
                        file.image_url = file.image_url || file.url;
                        file.id = file.id || file.file_id || file.value_id || file.fileId;
                        if (!file.id) {
                            file.id = $('input[name^="product[media_gallery][images]"][name$="[file]"][value="' + file.file + '"]:last');
                            if (file.id.length) {
                                file.id = file.file_id = file.id.attr('name')
                                    .replace(/^product\[media_gallery\]\[images\]\[/, '')
                                    .replace(/\]\[file\]$/, '');
                            }
                        }
                        if (file.id) {
                            file.image_url = file.asset_derived_image_url || file.image_url;
                            self.insertChildRow(file).trigger("freeTransformation");
                        }
                    }
                });

                $(document).on('removeItem', '#media_gallery_content', function(event, file) {
                    if (file && (file.id || file.file_id || file.value_id || file.fileId)) {
                        file.id = file.id || file.file_id || file.value_id || file.fileId;
                        self.elems.each(function(elem) {
                            if (elem.id == file.id) {
                                self.removeChild(elem);
                            }
                        });
                    }
                });

                return this;
            },

            afterRender: function() {
                var self = this;

                this.elems.each(function(elem) {
                    elem.ajaxUrl = self.ajaxUrl;
                });
            }
        });
    }
);