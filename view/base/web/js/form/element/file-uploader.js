/**
 * @api
 */
/* global Base64 */
define([
    'jquery',
    'Magento_Ui/js/form/element/file-uploader'
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

    });
});