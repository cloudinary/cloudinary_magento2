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

                return validateIsUrl(href) && (
                    href.match(/youtube\.com|youtu\.be/) ||
                    href.match(/vimeo\.com/) ||
                    href.match(/cloudinary\.com/) ||
                    href.match(/\.(mp4|ogv|webm)(?!\w)/)
                );
            },
            $.mage.__('Please enter a valid video URL. Valid URLs have a video file extension (.mp4, .webm, .ogv) or links to videos on YouTube, Vimeo or Cloudinary.')//eslint-disable-line max-len
        );

        validator.addRule(
            'validate-video-source',
            function (href) {
                if (utils.isEmptyNoTrim(href)) {
                    return true;
                }

                href = (href || '').replace(/^\s+/, '').replace(/\s+$/, '');

                return validateIsUrl(href) && (
                    href.match(/youtube\.com|youtu\.be/) ||
                    href.match(/vimeo\.com/) ||
                    href.match(/cloudinary\.com/) ||
                    href.match(/\.(mp4|ogv|webm)(?!\w)/)
                );
            },
            $.mage.__('Please enter a valid video URL. Valid URLs have a video file extension (.mp4, .webm, .ogv) or links to videos on YouTube, Vimeo or Cloudinary.')//eslint-disable-line max-len
        );

        return validator;
    };
});
