/* global Base64 */
define([
    'jquery',
    'Magento_Ui/js/form/element/image-uploader'
], function($, Element) {
    'use strict';

    return Element.extend({
        /**
         * {@inheritDoc}
         */
        initialize: function() {
            this._super();
            this.cloudinaryMLoptions.imageParamName = this.paramName || this.inputName;
            this.cloudinaryMLoptions.cldMLid = this.cloudinaryMLoptions.imageParamName + '_' + this.uid;
            this.cloudinaryMLoptions.callbackHandler = this;
            this.cloudinaryMLoptions.callbackHandlerMethod = 'addFile';
        },
        /**
         *  hides the 'Upload from Gallery' button at category page.
         * */
        showGalleryUploader: function() {
            return (this.cloudinaryMLoptions.isGallerySupported)
        }
    });
});
