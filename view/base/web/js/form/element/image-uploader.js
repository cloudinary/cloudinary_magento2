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
            this.unsupportedFromVersion = '2.4.0';
        },
        /*
        *  hide the 'Upload from Gallery' button for Magento version above 2.4.0
        * */
        showGalleryUploader: function() {
            return (!this.isNewVersion())
        },

        isNewVersion: function () {
            let currentVersion = this.cloudinaryMLoptions.magentoVersion.split('.');
            let unsupported = this.unsupportedFromVersion.split('.');
            return  ( currentVersion[1] >= unsupported[1] );
        }
    });
});
