define(
    ['jquery','Magento_PageBuilder/js/events','mage/url'],
    function($,_PBEvents, urlBuilder){
        'use strict'
        return function (config, element) {
            var updateHandler = {
                images: [],
                init: function () {
                    let self = this;
                    _PBEvents.on('image:renderAfter', function (event){
                        let elem = event.element, key = event.id;
                        let image = $(elem).find('img');
                        let src = {'remote_image': image.attr('src')};
                        self.images.push(src);

                        self.update(key);
                    });
                    $('#save-button').on('click', function (e){
                        self.images.each(function(elem){
                            if (elem.cld_image) {
                                let cld_src = elem.cld_image;
                                let img = $('img[src="' +  cld_src +'"]');
                                return (img.length) ? img.attr('src', elem.remote_image) : '';
                            }
                        });
                    });
                },
                update: function(key) {
                    let self = this;
                    this.images.each(function(elem,ind){
                        $.ajax({
                            url: config.ajaxUrl,
                            type: 'POST',
                            dataType: 'json',
                            data: elem,
                            success: function(image) {
                                self.images[ind].cld_image = image;
                                let img = $('img[src="' +  self.images[ind].remote_image +'"]');
                                if (img.length) {
                                    $('img[src="' +  self.images[ind].remote_image +'"]').attr('src', self.images[ind].cld_image);
                                }
                            },
                            error: function(xhr, textStatus, errorThrown) {
                                console.log('Error:', textStatus, errorThrown);
                            }
                        });

                    })
                }
            };
            return updateHandler.init();
        }
    });
