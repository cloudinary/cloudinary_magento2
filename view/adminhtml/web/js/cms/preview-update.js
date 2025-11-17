/**
 * Cloudinary Preview Update Handler
 * Manages image preview updates with Cloudinary transformed images in Page Builder
 */
define(
    ['jquery', 'Magento_PageBuilder/js/events'],
    function ($, _PBEvents) {
        'use strict';

        /**
         * @param {Object} config - Configuration object
         * @param {string} config.ajaxUrl - URL for AJAX requests
         * @param {string} config.saveButtonSelector - Selector for save button (default: '#save-button')
         * @param {Element} element - DOM element
         * @returns {Object} updateHandler instance
         */
        return function (config, element) {
            let updateHandler = {
                images: {},
                pendingRequests: {},
                processedImages: new Set(),
                saveButtonSelector: config.saveButtonSelector || '#save-button',
                eventHandlers: [],

                /**
                 * Initialize the handler and set up event listeners
                 */
                init: function () {
                    let self = this;

                    // Store event handler references for cleanup
                    let renderAfterHandler = function (event) {
                        let elem = event.element,
                            key = event.id,
                            image = $(elem).find('img'),
                            imageSrc = image.attr('src');

                        // Skip if no image source or already processed
                        if (!imageSrc || self.processedImages.has(imageSrc)) {
                            return;
                        }

                        self.images[key] = {
                            key: key,
                            remote_image: imageSrc
                        };

                        self.update(key);
                    };

                    let saveButtonHandler = function () {
                        self.restoreOriginalImages();
                    };

                    // Attach event handlers
                    _PBEvents.on('image:renderAfter', renderAfterHandler);
                    $(self.saveButtonSelector).on('click', saveButtonHandler);

                    // Store handlers for cleanup
                    self.eventHandlers.push({
                        event: 'image:renderAfter',
                        handler: renderAfterHandler
                    });
                    self.eventHandlers.push({
                        selector: self.saveButtonSelector,
                        event: 'click',
                        handler: saveButtonHandler
                    });

                    return self;
                },

                /**
                 * Restore original images before save
                 */
                restoreOriginalImages: function () {
                    let self = this;

                    $.each(self.images, function (_, elem) {
                        if (elem.cld_image) {
                            // Use safer selector approach
                            $('img').filter(function () {
                                return $(this).attr('src') === elem.cld_image;
                            }).attr('src', elem.remote_image);
                        }
                    });
                },

                /**
                 * Validate image URL
                 * @param {string} url - URL to validate
                 * @returns {boolean} Whether URL is valid
                 */
                isValidImageUrl: function (url) {
                    if (!url || typeof url !== 'string') {
                        return false;
                    }

                    // Basic URL validation
                    try {
                        let urlObj = new URL(url, window.location.origin);
                        return /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(urlObj.pathname) ||
                               url.indexOf('media/') !== -1;
                    } catch (e) {
                        return false;
                    }
                },

                /**
                 * Update image with Cloudinary version
                 * @param {string} key - Image key
                 */
                update: function (key) {
                    let self = this,
                        imageData = self.images[key];

                    // Validation checks
                    if (!imageData || !imageData.remote_image) {
                        return;
                    }

                    if (!self.isValidImageUrl(imageData.remote_image)) {
                        console.warn('Invalid image URL:', imageData.remote_image);
                        return;
                    }

                    // Check if already processing this image URL
                    let imageUrl = imageData.remote_image;
                    if (self.processedImages.has(imageUrl)) {
                        return;
                    }

                    // Abort previous request for this key if still pending
                    if (self.pendingRequests[key]) {
                        self.pendingRequests[key].abort();
                    }

                    // Mark as being processed
                    self.processedImages.add(imageUrl);

                    // Make AJAX request and store reference
                    self.pendingRequests[key] = $.ajax({
                        url: config.ajaxUrl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            remote_image: imageData.remote_image,
                            form_key: window.FORM_KEY || ''
                        },
                        success: function (image) {
                            
                            delete self.pendingRequests[key];

                            // Validate response
                            if (!image || typeof image !== 'string') {
                                console.warn('Invalid response for image:', key);
                                return;
                            }

                            self.images[key].cld_image = image;

                            // Update image in DOM using safer selector
                            $('img').filter(function () {
                                return $(this).attr('src') === imageData.remote_image;
                            }).attr('src', image);
                        },
                        error: function (xhr, textStatus, errorThrown) {
                            // Clean up pending request reference
                            delete self.pendingRequests[key];

                            // Only log if not aborted
                            if (textStatus !== 'abort') {
                                console.error('Cloudinary image update failed:', {
                                    key: key,
                                    status: textStatus,
                                    error: errorThrown,
                                    response: xhr.responseText
                                });

                                // Remove from processed set to allow retry
                                self.processedImages.delete(imageUrl);
                            }
                        }
                    });
                },

                /**
                 * Cleanup event listeners and abort pending requests
                 */
                destroy: function () {
                    let self = this;

                    // Remove event listeners
                    self.eventHandlers.forEach(function (handler) {
                        if (handler.selector) {
                            $(handler.selector).off(handler.event, handler.handler);
                        } else if (handler.event) {
                            _PBEvents.off(handler.event, handler.handler);
                        }
                    });

                    // Abort all pending AJAX requests
                    $.each(self.pendingRequests, function (_, request) {
                        request.abort();
                    });

                    // Clear data
                    self.images = {};
                    self.pendingRequests = {};
                    self.processedImages.clear();
                    self.eventHandlers = [];
                }
            };

            return updateHandler.init();
        };
    });