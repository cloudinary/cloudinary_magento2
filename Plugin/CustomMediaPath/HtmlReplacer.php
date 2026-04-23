<?php

namespace Cloudinary\Cloudinary\Plugin\CustomMediaPath;

use Cloudinary\Cloudinary\Core\AutoUploadMapping\AutoUploadConfigurationInterface;
use Cloudinary\Cloudinary\Core\Image;
use Cloudinary\Cloudinary\Core\UrlGenerator;
use Cloudinary\Cloudinary\Model\Configuration;
use Cloudinary\Cloudinary\Model\Logger as CloudinaryLogger;
use Cloudinary\Cloudinary\Model\SynchronisationRepository;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\HTTP\PhpEnvironment\Response as HttpResponse;
use Magento\Framework\View\Result\Layout;

class HtmlReplacer
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * @var SynchronisationRepository
     */
    private $synchronisationRepository;

    /**
     * @var AutoUploadConfigurationInterface
     */
    private $autoUploadConfiguration;

    /**
     * @var CloudinaryLogger
     */
    private $logger;

    /**
     * @param Configuration $configuration
     * @param UrlGenerator $urlGenerator
     * @param SynchronisationRepository $synchronisationRepository
     * @param AutoUploadConfigurationInterface $autoUploadConfiguration
     * @param CloudinaryLogger $logger
     */
    public function __construct(
        Configuration $configuration,
        UrlGenerator $urlGenerator,
        SynchronisationRepository $synchronisationRepository,
        AutoUploadConfigurationInterface $autoUploadConfiguration,
        CloudinaryLogger $logger
    ) {
        $this->configuration = $configuration;
        $this->urlGenerator = $urlGenerator;
        $this->synchronisationRepository = $synchronisationRepository;
        $this->autoUploadConfiguration = $autoUploadConfiguration;
        $this->logger = $logger;
    }

    /**
     * @param Layout $subject
     * @param callable $proceed
     * @param ResponseInterface $httpResponse
     * @return mixed
     */
    public function aroundRenderResult(Layout $subject, callable $proceed, ResponseInterface $httpResponse)
    {
        $result = $proceed($httpResponse);

        if (!($httpResponse instanceof HttpResponse) || !$this->shouldProcess()) {
            return $result;
        }

        $html = $httpResponse->getBody();
        if (!$html) {
            return $result;
        }

        $mediaBaseUrl = $this->configuration->getMediaBaseUrl();
        $customPaths = $this->configuration->getCustomMediaPaths();

        // Quick check: does the HTML contain any of the custom paths?
        $matchingPaths = [];
        foreach ($customPaths as $path) {
            if (stripos($html, $path . '/') !== false) {
                $matchingPaths[] = $path;
            }
        }

        if (empty($matchingPaths)) {
            return $result;
        }

        $replacedHtml = $html;

        // Step 1: Normalize PageBuilder rendition URLs (both local and Cloudinary forms)
        // so Cloudinary fetches the original image instead of the rendition derivative.
        $replacedHtml = $this->normalizeRenditionUrls($replacedHtml, $matchingPaths);

        // Step 2: Revert unsynced Cloudinary URLs back to local URLs
        $replacedHtml = $this->revertUnsyncedCloudinaryUrls($replacedHtml, $mediaBaseUrl, $matchingPaths);

        // Step 3: Replace local URLs with Cloudinary URLs for synced images
        $replacedHtml = $this->replaceLocalUrls($replacedHtml, $mediaBaseUrl, $matchingPaths);

        if ($replacedHtml !== $html) {
            $httpResponse->setBody($replacedHtml);
        }

        return $result;
    }

    /**
     * @return bool
     */
    private function shouldProcess(): bool
    {
        return $this->configuration->isEnabled()
            && $this->configuration->isEnabledCustomMediaPath()
            && !empty($this->configuration->getCustomMediaPaths());
    }

    /**
     * Strip the `.renditions/` segment from URLs pointing to PageBuilder-generated
     * rendition derivatives under a configured custom media path.
     *
     * PageBuilder writes a resized preview to pub/media/.renditions/<original-path>/
     * and outputs URLs pointing at it. Because that file lives under a dot-prefixed
     * directory (commonly blocked at the web-server level) and the auto-upload mapping
     * is configured for the original path, those URLs 404. Cloudinary handles resizing
     * itself, so we rewrite to the original path in both local and Cloudinary form.
     *
     * @param string $html
     * @param array $paths
     * @return string
     */
    private function normalizeRenditionUrls(string $html, array $paths): string
    {
        $pathAlternation = implode('|', array_map(function ($p) {
            return preg_quote($p, '~');
        }, $paths));

        // `/media/.renditions/<custom-path>/…` → `/media/<custom-path>/…`
        // Covers both local media URLs and already-rewritten Cloudinary URLs.
        $pattern = '~(/media/)\.renditions/((?:' . $pathAlternation . ')/)~i';

        return preg_replace($pattern, '$1$2', $html);
    }

    /**
     * Revert Cloudinary URLs back to local URLs for images that aren't actually synced.
     * This handles cases where other plugins (e.g., Widget Template Filter) converted
     * {{media url=...}} directives to Cloudinary URLs based on auto-upload mapping being active,
     * even though the image hasn't been uploaded to Cloudinary yet.
     *
     * @param string $html
     * @param string $mediaBaseUrl
     * @param array $paths
     * @return string
     */
    private function revertUnsyncedCloudinaryUrls(string $html, string $mediaBaseUrl, array $paths): string
    {
        // When auto-upload is active, Cloudinary URLs are valid even without sync — no reverting needed.
        if ($this->autoUploadConfiguration->isActive()) {
            return $html;
        }

        $pathAlternation = implode('|', array_map(function ($p) {
            return preg_quote($p, '~');
        }, $paths));

        // Match Cloudinary URLs containing custom media paths
        // Pattern: https://res.cloudinary.com/{cloud}/.../media/{custom_path}/...{file}.{ext}?_i=AB
        $pattern = '~https?://[^\s\'"]+/(?:media/(?:' . $pathAlternation . ')/[^\s\'"\\\\)?#]+\.(?:jpe?g|png|gif|webp|svg|avif))(?:\?[^\s\'"]*)?~i';

        return preg_replace_callback($pattern, function ($matches) use ($mediaBaseUrl, $pathAlternation) {
            $fullUrl = $matches[0];

            // Extract the media-relative path from the Cloudinary URL
            if (!preg_match('~(media/(?:' . $pathAlternation . ')/[^\s\'"\\\\)?#]+\.(?:jpe?g|png|gif|webp|svg|avif))~i', $fullUrl, $pathMatch)) {
                return $fullUrl;
            }

            $migratedPath = $pathMatch[1];

            $isSynced = $this->synchronisationRepository->isSynchronizedImagePath($migratedPath);
            $this->logger->info('[CustomMediaPath] Revert check: ' . $migratedPath . ' synced=' . ($isSynced ? 'YES' : 'NO'));

            // If the image IS synced, keep the Cloudinary URL
            if ($isSynced) {
                return $fullUrl;
            }

            // Not synced — revert to local URL
            $relativePath = preg_replace('#^media/#', '', $migratedPath);
            $this->logger->info('[CustomMediaPath] Reverting to local: ' . $mediaBaseUrl . $relativePath);
            return $mediaBaseUrl . $relativePath;
        }, $html);
    }

    /**
     * Replace local media URLs with Cloudinary URLs for synced images.
     *
     * @param string $html
     * @param string $mediaBaseUrl
     * @param array $paths
     * @return string
     */
    private function replaceLocalUrls(string $html, string $mediaBaseUrl, array $paths): string
    {
        $mediaBaseUrlEscaped = preg_quote($mediaBaseUrl, '~');
        $pathAlternation = implode('|', array_map(function ($p) {
            return preg_quote($p, '~');
        }, $paths));

        $pattern = '~(' . $mediaBaseUrlEscaped . '(?:' . $pathAlternation . ')/[^\s\'"\\\\)?#]+\.(?:jpe?g|png|gif|webp|svg|avif))~i';

        $autoUploadActive = $this->autoUploadConfiguration->isActive();

        return preg_replace_callback($pattern, function ($matches) use ($mediaBaseUrl, $autoUploadActive) {
            $fullUrl = $matches[1];

            $relativePath = str_replace($mediaBaseUrl, '', $fullUrl);
            $migratedPath = $this->configuration->getMigratedPath($relativePath);

            // When auto-upload is active, Cloudinary fetches the image on first request — no sync needed.
            // Otherwise, only replace if the image is already synced to Cloudinary.
            if (!$autoUploadActive && !$this->synchronisationRepository->isSynchronizedImagePath($migratedPath)) {
                $this->logger->info('[CustomMediaPath] Skipping (not synced, auto-upload inactive): ' . $migratedPath);
                return $fullUrl;
            }

            $image = Image::fromPath($relativePath, $migratedPath);
            $cloudinaryUrl = $this->urlGenerator->generateFor($image, $this->configuration->getDefaultTransformation());

            $this->logger->info('[CustomMediaPath] Replaced: ' . $fullUrl . ' -> ' . ($cloudinaryUrl ?: 'FAILED'));
            return $cloudinaryUrl ?: $fullUrl;
        }, $html);
    }
}
