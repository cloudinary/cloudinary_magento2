/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'underscore',
    'Magento_Ui/js/lib/validation/utils'
], function($, _, utils) {
    'use strict';

    /**
     * Validate that string is url
     * @param {String} href
     * @return {Boolean}
     */
    function validateIsUrl(href) {
        return (/^(http|https|ftp):\/\/(([A-Z0-9]([A-Z0-9_-]*[A-Z0-9]|))(\.[A-Z0-9]([A-Z0-9_-]*[A-Z0-9]|))*)(:(\d+))?(\/[A-Z0-9~](([A-Z0-9_~-]|\.)*[A-Z0-9~]|))*\/?(.*)?$/i).test(href); //eslint-disable-line max-len
    }

    return function(validator) {
        validator.addRule(
            'validate-video-url',
            function(href) {
                if (utils.isEmptyNoTrim(href)) {
                    return true;
                }

                href = (href || '').replace(/^\s+/, '').replace(/\s+$/, '');

                return validateIsUrl(href) && (href.match(/youtube\.com|youtu\.be/) || href.match(/vimeo\.com/) || href.match(/cloudinary\.com/));
            },
            $.mage.__('Please enter a valid video URL.')
        );
        return validator;
    };
});