document.addEventListener("alpine:init", () => {
    if (typeof initGallery === "function") {
        const originalInitGallery = initGallery;

        window.initGallery = function () {
            const gallery = originalInitGallery();

            gallery.ensureYouTubeIframeExists = function () {
                let youtubeContainer = document.querySelector("#youtube-player");

                if (!youtubeContainer || youtubeContainer.tagName !== "IFRAME") {
                    const iframe = document.createElement("iframe");
                    iframe.id = "youtube-player";
                    iframe.classList.add("w-full", "h-full");
                    iframe.width = "640";
                    iframe.height = "360";
                    iframe.allow = "autoplay; fullscreen";
                    iframe.frameBorder = "0";
                    iframe.src = "about:blank";
                    youtubeContainer?.replaceWith(iframe);
                }
            };

            document.addEventListener("DOMContentLoaded", () => {
                gallery.ensureYouTubeIframeExists();
            });

            const originalGetVideoData = gallery.getVideoData;
            gallery.getVideoData = function () {
                const videoData = originalGetVideoData.apply(this);
                if (videoData && videoData.id && videoData.type) {
                    return videoData;
                }

                const activeImage = this.images[this.active];
                const videoUrl = activeImage && activeImage.videoUrl;

                if (!videoUrl || !videoUrl.includes("cloudinary.com")) {
                    return false;
                }

                const transformedUrl = videoUrl.replace("/upload/", "/upload/f_auto,q_auto:good,vc_auto/");

                return {
                    id: transformedUrl,
                    type: "cloudinary",
                    url: transformedUrl,
                };
            };

            const originalActivateVideo = gallery.activateVideo;
            gallery.activateVideo = function () {
                const videoData = this.getVideoData();
                if (!videoData || !videoData.id || !videoData.type) {
                    return;
                }

                this.ensureYouTubeIframeExists();

                if (videoData.type === "cloudinary") {
                    this.activeVideoType = "youtube";
                    this.$nextTick(() => {
                        this.loadCloudinaryIntoYouTubeIframe(videoData);
                    });
                    return;
                }

                if (videoData.type === "youtube") {
                    this.$nextTick(() => {
                        this.resetYouTubeIframe(videoData);
                    });
                }

                originalActivateVideo.apply(this);
            };

            gallery.loadCloudinaryIntoYouTubeIframe = function (videoData) {
                this.$nextTick(() => {
                    const youtubeIframe = document.querySelector("#youtube-player");
                    if (!youtubeIframe || youtubeIframe.tagName !== "IFRAME") {
                        return;
                    }

                    youtubeIframe.src = "";
                    youtubeIframe.src = `${videoData.url}?autoplay=1&loop=0&controls=1`;
                });
            };

            gallery.resetYouTubeIframe = function (videoData) {
                this.$nextTick(() => {
                    const youtubeIframe = document.querySelector("#youtube-player");
                    if (!youtubeIframe || youtubeIframe.tagName !== "IFRAME") {
                        return;
                    }

                    youtubeIframe.src = `https://www.youtube.com/embed/${videoData.id}?autoplay=1`;
                });
            };

            return gallery;
        };
    }
});
