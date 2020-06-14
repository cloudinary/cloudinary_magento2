/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'jquery',
        'underscore',
        'jquery/ui',
        'Magento_Ui/js/modal/modal',
        'mage/translate',
        'mage/backend/tree-suggest',
        'mage/backend/validation'
    ],
    function($, _) {
        'use strict';

        $.widget('mage.newCldSpinsetDialog', {
            /**
             * Build widget
             *
             * @private
             */
            _create: function() {
                var widget = this;

                this.element.modal({
                    type: 'slide',
                    //appendTo: this._gallery,
                    modalClass: 'cldspinset-dialog form-inline',
                    title: $.mage.__('Add Spinset from Cloudinary'),
                    buttons: [{
                            text: $.mage.__('Save'),
                            class: 'action-primary video-create-button',
                            click: $.proxy(widget._onCreate, widget)
                        },
                        {
                            text: $.mage.__('Cancel'),
                            class: 'video-cancel-button',
                            click: $.proxy(widget._onCancel, widget)
                        }
                    ],

                    /**
                     * @returns {null}
                     */
                    opened: function() {
                        console.log('cldspinset opened');
                    },

                    /**
                     * Closed
                     */
                    closed: function() {
                        console.log('cldspinset closed');
                    }
                });
            },

            /**
             * Fired when click on create video
             *
             * @private
             */
            _onCreate: function() {
                console.log('_onCreate');
            },

            /**
             * Fired when click on create video
             *
             * @private
             */
            _onCancel: function() {
                console.log('_onCancel');
            }
        });

        return $.mage.newCldSpinsetDialog;
    }
);