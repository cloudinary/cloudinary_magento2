define([
    'jquery',
    'cloudinaryProductGalleryAll'
], function($) {
    'use strict';

    $.widget('mage.cloudinaryProductGallery', {

        options: {
            cloudinaryPGoptions: {}, // Options for Cloudinary-PG galleryWidget()
            cldPGid: 0,
        },

        /**
         * @private
         */
        _create: function() {
            this._super();

            var widget = this;
            window.cloudinary_pg = window.cloudinary_pg || [];
            this.options.cldPGid = this.options.cldPGid || 0;
            if (typeof window.cloudinary_pg[this.options.cldPGid] === "undefined") {
                this.cloudinary_pg = window.cloudinary_pg[this.options.cldPGid] = cloudinary.galleryWidget(this.options.cloudinaryPGoptions);
                this.cloudinary_pg.render();
            } else {
                this.cloudinary_pg = window.cloudinary_pg[this.options.cldPGid];
            }
        },

    });

    return $.mage.cloudinaryProductGallery;
});