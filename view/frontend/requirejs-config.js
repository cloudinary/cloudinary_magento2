var config = {
    map: {
        '*': {
            loadPlayer: 'Cloudinary_Cloudinary/js/load-player',
            'Magento_ProductVideo/js/fotorama-add-video-events': 'Cloudinary_Cloudinary/js/fotorama-add-video-events',
            cloudinaryProductGallery: 'Cloudinary_Cloudinary/js/cloudinary-product-gallery',
            cloudinaryLazyload: 'Cloudinary_Cloudinary/js/cloudinary-lazyload'
        }
    },
    paths: {
        'jquery.lazyload': "Cloudinary_Cloudinary/js/jquery.lazyload.min",
        cloudinaryProductGalleryAll: "//product-gallery.cloudinary.com/latest/all"
    },
    shim: {
        'jquery.lazyload': {
            deps: ['jquery']
        },
    },
    config: {
        mixins: {
            'Magento_Swatches/js/swatch-renderer': {
                'Cloudinary_Cloudinary/js/swatch-renderer-mixin': true
            }
        }
    }
};
