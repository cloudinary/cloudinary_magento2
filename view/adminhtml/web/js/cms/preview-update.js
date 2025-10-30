define(
    ['jquery','Magento_PageBuilder/js/events'],
    function ($,_PBEvents) {
        'use strict';
        return function (config, element) {
            let updateHandler = {
                images: {},
                init: function () {
                    let self = this;

                    _PBEvents.on('image:renderAfter', function (event) {
                        let elem = event.element, key = event.id;
                        let image = $(elem).find('img');

                        self.images[key] = {
                            key: key,
                            remote_image: image.attr('src')
                        };

                        self.update(key);
                    });
                    $('#save-button').on('click', function () {
                        self.images.each(function (elem) {
                            if (elem.cld_image) {
                                let cld_src = elem.cld_image;
                                let img = $('img[src="' +  cld_src +'"]');

                                if (img.length) {
                                    img.attr('src', elem.remote_image);
                                }
                            }
                        });
                    });
                },
                update: function (key) {
                    let self = this;
                    let imageData = self.images[key];

                    if (!imageData) {
                        return;
                    }

                    $.ajax({
                        url: config.ajaxUrl,
                        type: 'POST',
                        dataType: 'json',
                        data: { remote_image: imageData.remote_image },
                        success: function (image) {
                            self.images[key].cld_image = image;
                            let img = $('img[src="' +  imageData.remote_image +'"]');

                            if (img.length) {
                                img.attr('src', image);
                            }
                        },
                        error: function (xhr, textStatus, errorThrown) {
                            console.log('Error:', textStatus, errorThrown);
                        }
                    });
                }
            };

            return updateHandler.init();
        };
    });
