/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 @version  0.0.2
 @requires jQuery & jQuery UI
 */

define(
    [
        'jquery',
        'jquery-ui-modules/widget',
        'https://unpkg.com/cloudinary-video-player/dist/cld-video-player.min.js'
    ],
    function ($) {
        'use strict';

        var videoRegister = {
            _register: {},

            /**
             * Checks, if api is already registered
             *
             * @param   {String} api
             * @returns {bool}
             */
            isRegistered: function (api) {
                return this._register[api] !== undefined;
            },

            /**
             * Checks, if api is loaded
             *
             * @param   {String} api
             * @returns {bool}
             */
            isLoaded: function (api) {
                return this._register[api] !== undefined && this._register[api] === true;
            },

            /**
             * Register new video api
             *
             * @param {String} api
             * @param {bool} loaded
             */
            register: function (api, loaded) {
                loaded = loaded || false;
                this._register[api] = loaded;
            }
        };

        $.widget(
            'mage.productVideoLoader', {

                /**
                 * @private
                 */
                _create: function () {
                    switch (this.element.data('type')) {
                        case 'youtube':
                            this.element.videoYoutube();
                            this._player = this.element.data('mageVideoYoutube');
                            break;
                        case 'vimeo':
                            this.element.videoVimeo();
                            this._player = this.element.data('mageVideoVimeo');
                            break;
                        case 'cloudinary':
                            this.element.videoCloudinary();
                            this._player = this.element.data('mageVideoCloudinary');
                            break;
                        default:
                            throw {
                                name: 'Video Error',
                                message: 'Unknown video type',

                                /**
                                 * join name with message
                                 */
                                toString: function () {
                                    return this.name + ': ' + this.message;
                                }
                            };
                    }
                },

                /**
                 * Initializes variables
                 *
                 * @private
                 */
                _initialize: function () {
                    this._params = this.element.data('params') || {};
                    this._code = this.element.data('code');
                    this._width = this.element.data('width');
                    this._height = this.element.data('height');
                    this._autoplay = !!this.element.data('autoplay');
                    this._videoUrl = this.element.data('video-url');
                    this._playing = this._autoplay || false;
                    this._loop = this.element.data('loop');
                    this._rel = this.element.data('related');
                    this.useYoutubeNocookie = this.element.data('youtubenocookie') || false;

                    this._responsive = this.element.data('responsive') !== false;

                    if (this._responsive === true) {
                        this.element.addClass('responsive');
                    }

                    this._calculateRatio();
                },

                /**
                 * Abstract play command
                 */
                play: function () {
                    this._player.play();
                },

                /**
                 * Abstract pause command
                 */
                pause: function () {
                    this._player.pause();
                },

                /**
                 * Abstract stop command
                 */
                stop: function () {
                    this._player.stop();
                },

                /**
                 * Abstract playing command
                 */
                playing: function () {
                    return this._player.playing();
                },

                /**
                 * Destroyer
                 */
                destroy: function () {
                    console.log(this._player);
                },

                /**
                 * Calculates ratio for responsive videos
                 *
                 * @private
                 */
                _calculateRatio: function () {
                    if (!this._responsive) {
                        return;
                    }
                    this.element.css('paddingBottom', this._height / this._width * 100 + '%');
                }
            }
        );

        $.widget(
            'mage.videoYoutube', $.mage.productVideoLoader, {

                /**
                 * Initialization of the Youtube widget
                 *
                 * @private
                 */
                _create: function () {
                    var self = this;

                    this._initialize();

                    this.element.append('<div></div>');

                    this._on(
                        window, {

                            /**
                             * Handle event
                             */
                            'youtubeapiready': function () {
                                var host = 'https://www.youtube.com';

                                if (self.useYoutubeNocookie) {
                                    host = 'https://www.youtube-nocookie.com';
                                }

                                if (self._player !== undefined) {
                                    return;
                                }
                                self._autoplay = true;

                                if (self._autoplay) {
                                    self._params.autoplay = 1;
                                }

                                if (!self._rel) {
                                    self._params.rel = 0;
                                }

                                self._player = new window.YT.Player(
                                    self.element.children(':first')[0], {
                                        height: self._height,
                                        width: self._width,
                                        videoId: self._code,
                                        playerVars: self._params,
                                        host: host,
                                        events: {

                                            /**
                                             * Get duration
                                             */
                                            'onReady': function onPlayerReady()
                                            {
                                                self._player.getDuration();
                                                self.element.closest('.fotorama__stage__frame')
                                                    .addClass('fotorama__product-video--loaded');
                                            },

                                            /**
                                             * Event observer
                                             */
                                            onStateChange: function (data) {
                                                switch (window.parseInt(data.data, 10)) {
                                                    case 1:
                                                        self._playing = true;
                                                        break;
                                                    default:
                                                        self._playing = false;
                                                        break;
                                                }

                                                self._trigger('statechange', {}, data);

                                                if (data.data === window.YT.PlayerState.ENDED && self._loop) {
                                                    self._player.playVideo();
                                                }
                                            }
                                        }

                                    }
                                );
                            }
                        }
                    );

                    this._loadApi();
                },

                /**
                 * Loads Youtube API and triggers event, when loaded
                 *
                 * @private
                 */
                _loadApi: function () {
                    var element,
                        scriptTag;

                    if (videoRegister.isRegistered('youtube')) {
                        if (videoRegister.isLoaded('youtube')) {
                            $(window).trigger('youtubeapiready');
                        }

                        return;
                    }
                    videoRegister.register('youtube');

                    element = document.createElement('script');
                    scriptTag = document.getElementsByTagName('script')[0];

                    element.async = true;
                    element.src = 'https://www.youtube.com/iframe_api';
                    scriptTag.parentNode.insertBefore(element, scriptTag);

                    /**
                     * Event observe and handle
                     */
                    window.onYouTubeIframeAPIReady = function () {
                        $(window).trigger('youtubeapiready');
                        videoRegister.register('youtube', true);
                    };
                },

                /**
                 * Play command for Youtube
                 */
                play: function () {
                    this._player.playVideo();
                    this._playing = true;
                },

                /**
                 * Pause command for Youtube
                 */
                pause: function () {
                    this._player.pauseVideo();
                    this._playing = false;
                },

                /**
                 * Stop command for Youtube
                 */
                stop: function () {
                    this._player.stopVideo();
                    this._playing = false;
                },

                /**
                 * Playing command for Youtube
                 */
                playing: function () {
                    return this._playing;
                },

                /**
                 * stops and unloads player
                 *
                 * @private
                 */
                destroy: function () {
                    this.stop();
                    this._player.destroy();
                }
            }
        );

        $.widget(
            'mage.videoVimeo', $.mage.productVideoLoader, {

                /**
                 * Initialize the Vimeo widget
                 *
                 * @private
                 */
                _create: function () {
                    var timestamp,
                        additionalParams = '',
                        src, id;

                    this._initialize();
                    timestamp = new Date().getTime();
                    this._autoplay = true;

                    if (this._autoplay) {
                        additionalParams += '&autoplay=1';
                    }

                    if (this._loop) {
                        additionalParams += '&loop=1';
                    }
                    src = 'https://player.vimeo.com/video/' +
                        this._code + '?api=1&player_id=vimeo' +
                        this._code +
                        timestamp +
                        additionalParams;
                    id = 'vimeo' + this._code + timestamp;
                    this.element.append(
                        $('<iframe></iframe>')
                            .attr('frameborder', 0)
                            .attr('id', id)
                            .attr('width', this._width)
                            .attr('height', this._height)
                            .attr('src', src)
                            .attr('webkitallowfullscreen', '')
                            .attr('mozallowfullscreen', '')
                            .attr('allowfullscreen', '')
                            .attr('referrerPolicy', 'origin')
                    );

                    this._player = new Vimeo.Player(this.element.children(':first')[0]);
                    // Froogaloop throws error without a registered ready event
                    this._player.ready().then(function () {
                        $('#' + id).closest('.fotorama__stage__frame').addClass('fotorama__product-video--loaded');
                    });
                },

                /**
                 * Play command for Vimeo
                 */
                play: function () {
                    this._player.api('play');
                    this._playing = true;
                },

                /**
                 * Pause command for Vimeo
                 */
                pause: function () {
                    this._player.api('pause');
                    this._playing = false;
                },

                /**
                 * Stop command for Vimeo
                 */
                stop: function () {
                    this._player.api('unload');
                    this._playing = false;
                },

                /**
                 * Playing command for Vimeo
                 */
                playing: function () {
                    return this._playing;
                }
            }
        );

        $.widget(
            'mage.videoCloudinary', $.mage.productVideoLoader, {

                /**
                 * Initialize the Vimeo widget
                 *
                 * @private
                 */
                _create: function () {
                    this._initialize();
                    var elem = this.element;
                    var cldVideoSettings = JSON.parse(document.getElementById('cld_video_settings').textContent);
                    console.log(cldVideoSettings);

                    if (cldVideoSettings.player_type != 'cloudinary') {
                        elem.append(
                            $('<iframe></iframe>')
                                .attr('frameborder', 0)
                                .attr('id', 'cloudinary' + this._code + (new Date().getTime()))
                                .attr('class', 'cld-video-player')
                                .attr('width', this._width)
                                .attr('height', this._height)
                                .attr('src', this._videoUrl.replace(/(^\w+:|^)/, ''))
                                .attr('webkitallowfullscreen', '')
                                .attr('mozallowfullscreen', '')
                                .attr('allowfullscreen', '')
                                .attr('referrerPolicy', 'origin')
                                .on(
                                    "load",
                                    function () {
                                        elem.parent('.fotorama__stage__frame').addClass('fotorama__product-video--loaded');
                                    }
                                )
                        );
                    } else {
                        let id = 'cld_video_player';
                        this._player = $('<video></video>');
                        console.log(elem);
                        elem.append(
                            this._player
                                .attr('id', id)
                                .attr('controls', '')
                                .attr('autoplay', '')
                                .attr('preload', "none")
                                .attr('data-cld-public-id', this._code)
                                .attr('class', 'cld-video-player cld-fluid')
                        );
                        var url = this._videoUrl;
                        let settings = {...cldVideoSettings.settings};
                        let cldPlayer = cloudinary.videoPlayer(id ,settings);
                        let additionalParams = cldVideoSettings.source ? cldVideoSettings.source : {};
                        var url = this._videoUrl;
                        if (cldVideoSettings.transformation !== 'undefined') {
                            url = this._videoUrl.replace('/upload/','/upload/' + cldVideoSettings.transformation + '/');
                            cldPlayer.source(url, additionalParams);
                        } else {
                            cldPlayer.source(this._code, additionalParams);
                        }

                        $('#' + id).parent('.product-video').addClass('cld-product-video');
                        $('#' + id).closest('.fotorama__stage__frame').addClass('fotorama__product-video--loaded');
                        this._player.parent().css({
                            "position": "relative",
                            "z-index": "100"
                        });

                        if (settings.autoplay && settings.muted) {
                            cldPlayer.play();
                        }
                    }
                },
            }
        );
    }
);
