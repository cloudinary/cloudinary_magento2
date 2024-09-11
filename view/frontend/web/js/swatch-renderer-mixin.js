define([
    'jquery',
    'cloudinaryProductGalleryAll'
], function ($) {
    'use strict';

    console.log('Mixin loaded'); // For debugging

    return function (SwatchRenderer) {
        $.widget('mage.SwatchRenderer', $['mage']['SwatchRenderer'], {

            extractImageName: function (url){
                const fileNameWithExtension = url.substring(url.lastIndexOf('/') + 1);
                const firstChar = fileNameWithExtension.charAt(0);
                const secondChar = fileNameWithExtension.charAt(1);
                var publicId = `media/catalog/product/${firstChar}/${secondChar}/${fileNameWithExtension}`;
                publicId = publicId.replace('?_i=AB', '');
                return {'publicId': publicId, 'mediaType': 'image'};
            },
            _OnClick: function ($this, $widget) {

                const loadedGallery = $('.cloudinary-product-gallery');
                const cldPGid = loadedGallery.attr('id')
                const cldGalleryWidget = window.cloudinary_pg[cldPGid] || null;


                if (typeof this._super === 'function') {
                    this._super($this, $widget);
                }

                const images = $widget.options.jsonConfig.images[$widget.getProduct()];
                if (images && images.length > 0) {

                    const mainImageUrl = images[0].url;
                     const imgsToUpdate = images.map(image => this.extractImageName(image.img));
                    //const imgsToUpdate = images.map(image => image.img);

                    if (cldGalleryWidget) {
                        var loadedAssets = cldGalleryWidget.options.mediaAssets;
                        var mediaAssets = [...loadedAssets, ...imgsToUpdate];
                        console.log(mediaAssets);
                        var selectedIndex = loadedAssets.length + 1;

                        cldGalleryWidget.update({
                            mediaAssets: mediaAssets,
                        });
                    }
                }
            }
        });

        return $['mage']['SwatchRenderer'];
    };
});
