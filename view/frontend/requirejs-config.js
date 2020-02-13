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
        cloudinaryProductGalleryAll: "//product-gallery.cloudinary.com/all"
    },
    shim: {
        'jquery.lazyload': {
            deps: ['jquery']
        },
    }
};