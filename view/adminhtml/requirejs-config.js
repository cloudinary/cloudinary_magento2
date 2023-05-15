var config = {
    map: {
        '*': {
            cloudinaryFreeTransform: 'Cloudinary_Cloudinary/js/cloudinary-free',
            newVideoDialog: 'Cloudinary_Cloudinary/js/new-video-dialog',
            'Magento_ProductVideo/js/get-video-information': 'Cloudinary_Cloudinary/js/get-video-information',
            cloudinaryMediaLibraryModal: 'Cloudinary_Cloudinary/js/cloudinary-media-library-modal',
            cloudinarySpinsetModal: 'Cloudinary_Cloudinary/js/cloudinary-spinset-modal',
            cldspinsetDialog: 'Cloudinary_Cloudinary/js/cloudinary-spinset-dialog',
            productGallery: 'Cloudinary_Cloudinary/js/product-gallery',
            cloudinaryLazyload: 'Cloudinary_Cloudinary/js/cloudinary-lazyload'
        }
    },
    paths: {
        'jquery.lazyload': "Cloudinary_Cloudinary/js/jquery.lazyload.min",
        cloudinaryMediaLibraryAll: "//media-library.cloudinary.com/global/all",
        es6Promise: "//cdnjs.cloudflare.com/ajax/libs/es6-promise/4.1.1/es6-promise.auto.min",
        'uiComponent': 'Magento_Ui/js/core/app',
    },
    shim: {
        'jquery.lazyload': {
            deps: ['jquery']
        },
        'uiComponent': {
            deps: ['jquery']
        },
    },
    config: {
        mixins: {
            'Magento_Ui/js/lib/validation/validator': {
                'Cloudinary_Cloudinary/js/form/element/validator-rules-mixin': true
            },
        }
    }
};
