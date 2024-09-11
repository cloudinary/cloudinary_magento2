define([
    'jquery',
    'cloudinaryProductGalleryAll'
], function ($) {
    'use strict';

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
            mergeMediaAssets: function(existingAssets, newAssets) {
                const existingPublicIds = existingAssets.map(asset => asset.publicId);
                const uniqueNewAssets = newAssets.filter(newAsset => !existingPublicIds.includes(newAsset.publicId));
                return [...existingAssets, ...uniqueNewAssets];
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
                    const imgsToUpdate = images.map(image => this.extractImageName(image.img));

                    if (cldGalleryWidget) {
                        const loadedAssets = cldGalleryWidget.options.mediaAssets;
                        const mediaAssets = this.mergeMediaAssets(loadedAssets, imgsToUpdate);

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