<?php

namespace Cloudinary\Cloudinary\Plugin\Theme\Block\Html\Header;

use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Cloudinary\Core\CloudinaryImageManager;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Core\ConfigurationBuilder;
use Cloudinary\Cloudinary\Core\Image;
use Cloudinary\Cloudinary\Core\UrlGenerator;
use Cloudinary\Configuration\Configuration;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;

class Logo
{
    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var CloudinaryImageManager
     */
    private $cloudinaryImageManager;

    /**
     * @var ConfigurationBuilder
     */
    private $configurationBuilder;

    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ConfigurationInterface $configuration
     * @param CloudinaryImageManager $cloudinaryImageManager
     * @param ConfigurationBuilder $configurationBuilder
     * @param UrlGenerator $urlGenerator
     * @param Filesystem $filesystem
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $urlBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        ConfigurationInterface $configuration,
        CloudinaryImageManager $cloudinaryImageManager,
        ConfigurationBuilder $configurationBuilder,
        UrlGenerator $urlGenerator,
        Filesystem $filesystem,
        ScopeConfigInterface $scopeConfig,
        UrlInterface $urlBuilder,
        LoggerInterface $logger
    ) {
        $this->configuration = $configuration;
        $this->cloudinaryImageManager = $cloudinaryImageManager;
        $this->configurationBuilder = $configurationBuilder;
        $this->urlGenerator = $urlGenerator;
        $this->filesystem = $filesystem;
        $this->scopeConfig = $scopeConfig;
        $this->urlBuilder = $urlBuilder;
        $this->logger = $logger;
    }

    /**
     * Plugin to intercept logo loading and serve from Cloudinary
     *
     * @param \Magento\Theme\Block\Html\Header\Logo $subject
     * @param callable $proceed
     * @return string
     */
    public function aroundGetLogoSrc(
        \Magento\Theme\Block\Html\Header\Logo $subject,
        callable $proceed
    ) {
        // Check if Cloudinary is enabled
        if (!$this->configuration->isEnabled()) {
            return $proceed();
        }

        try {
            // Get the original logo URL first
            $originalLogoUrl = $proceed();

            // Get logo path from configuration
            $logoPath = $this->scopeConfig->getValue(
                'design/header/logo_src',
                ScopeInterface::SCOPE_STORE
            );

            if (!$logoPath) {
                // If no custom logo is set, use the original URL
                return $this->processLogoFromUrl($originalLogoUrl);
            }

            // The logo path configuration doesn't include the 'logo/' prefix, so we need to add it
            $fullLogoPath = 'logo/' . $logoPath;

            // Create a unique public ID for the logo (without logo/ prefix since we add it later)
            $publicId = pathinfo($logoPath, PATHINFO_FILENAME);

            // Check if logo exists on Cloudinary
            $cloudinaryUrl = $this->checkAndUploadToCloudinary($fullLogoPath, $publicId, $originalLogoUrl);

            if ($cloudinaryUrl) {
                return $cloudinaryUrl;
            }
        } catch (\Exception $e) {
            $this->logger->error('Cloudinary Logo Plugin Error: ' . $e->getMessage());
        }

        // Fallback to original logo URL if anything fails
        return $proceed();
    }

    /**
     * Check if image exists on Cloudinary and upload if necessary
     *
     * @param string $logoPath
     * @param string $publicId
     * @param string $originalUrl
     * @return string|null
     */
    private function checkAndUploadToCloudinary($logoPath, $publicId, $originalUrl)
    {
        try {
            // Initialize Cloudinary configuration
            Configuration::instance($this->configurationBuilder->build());

            // Check if the image exists on Cloudinary
            $existingUrl = $this->checkCloudinaryImageExists($publicId);

            if (!$existingUrl) {
                // Image doesn't exist on Cloudinary, upload it
                $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
                $logoFullPath = null;

                if ($logoPath && $mediaDirectory->isFile($logoPath)) {
                    $logoFullPath = $mediaDirectory->getAbsolutePath($logoPath);
                } else {
                    // If the file doesn't exist in media, try to download from original URL
                    $logoFullPath = $this->downloadLogoFromUrl($originalUrl);
                    if (!$logoFullPath) {
                        return null;
                    }
                }

                // Upload to Cloudinary with specific public_id
                $uploader = new UploadApi($this->configuration->getCredentials());
                $uploadOptions = array_merge(
                    $this->configuration->getUploadConfig()->toArray(),
                    [
                        'public_id' => 'logo/' . $publicId,
                        'overwrite' => true,
                        'resource_type' => 'image'
                    ]
                );

                $uploadResult = $uploader->upload($logoFullPath, $uploadOptions);

                if (isset($uploadResult['secure_url'])) {
                    // Add Magento plugin metadata
                    if (isset($uploadResult['public_id'])) {
                        $metadata = "cld_mag_plugin=1";
                        $uploader->addContext($metadata, [$uploadResult['public_id']]);
                    }

                    $this->logger->info('Logo uploaded to Cloudinary with public_id: ' . $publicId);

                    // Clean up temp file if it was downloaded
                    if (strpos($logoFullPath, '/tmp/') !== false) {
                        try {
                            unlink($logoFullPath);
                        } catch (\Exception $unlinkException) {
                            $this->logger->debug('Could not delete temporary logo file: ' . $unlinkException->getMessage());
                        }
                    }

                    return $uploadResult['secure_url'];
                }
            } else {
                // Image exists on Cloudinary, return its URL
                $this->logger->info('Logo already exists on Cloudinary with public_id: ' . $publicId);
                return $existingUrl;
            }
        } catch (\Exception $e) {
            $this->logger->error('Error uploading logo to Cloudinary: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Check if image exists on Cloudinary
     *
     * @param string $publicId
     * @return string|false
     */
    private function checkCloudinaryImageExists($publicId)
    {
        try {
            // Initialize AdminApi to check resource existence
            $adminApi = new AdminApi($this->configuration->getCredentials());

            // Build the full public_id with folder
            $fullPublicId = 'logo/' . $publicId;

            try {
                // Try to get the resource details
                $result = $adminApi->asset($fullPublicId, ['resource_type' => 'image']);

                if (isset($result['secure_url'])) {
                    $imageUrl = $result['secure_url'];

                    // Verify the image is actually accessible by making a HEAD request
                    if ($this->verifyImageAccessibility($imageUrl)) {
                        return $imageUrl;
                    } else {
                        $this->logger->info('Cloudinary image URL not accessible, will re-upload: ' . $imageUrl);
                        return false;
                    }
                }
            } catch (\Exception $e) {
                // Resource doesn't exist, which is expected if not uploaded yet
                $this->logger->debug('Image not found on Cloudinary: ' . $fullPublicId);
            }
        } catch (\Exception $e) {
            $this->logger->debug('Error checking Cloudinary image existence: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Verify if an image URL is accessible
     *
     * @param string $url
     * @return bool
     */
    private function verifyImageAccessibility($url)
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'HEAD',
                    'timeout' => 5,
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ]);

            $headers = false;
            try {
                $headers = get_headers($url, 1, $context);
            } catch (\Exception $headerException) {
                $this->logger->debug('Could not retrieve headers for URL: ' . $headerException->getMessage());
            }

            if ($headers && isset($headers[0])) {
                // Check for successful HTTP status codes (200, 201, etc.)
                return strpos($headers[0], '200') !== false || strpos($headers[0], '201') !== false;
            }
        } catch (\Exception $e) {
            $this->logger->debug('Error verifying image accessibility: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Process logo from URL when no custom logo is set
     *
     * @param string $originalUrl
     * @return string
     */
    private function processLogoFromUrl($originalUrl)
    {
        try {
            // Extract filename from URL
            $urlParts = parse_url($originalUrl);
            $path = $urlParts['path'] ?? '';
            $filename = pathinfo($path, PATHINFO_FILENAME);

            if ($filename) {
                $publicId = 'logo/' . $filename;

                // Check and upload to Cloudinary
                $cloudinaryUrl = $this->checkAndUploadToCloudinary('', $publicId, $originalUrl);

                if ($cloudinaryUrl) {
                    return $cloudinaryUrl;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error processing logo from URL: ' . $e->getMessage());
        }

        return $originalUrl;
    }

    /**
     * Download logo from URL to temporary location
     *
     * @param string $url
     * @return string|null
     */
    private function downloadLogoFromUrl($url)
    {
        try {
            $tempDir = $this->filesystem->getDirectoryWrite(DirectoryList::TMP);
            $extension = pathinfo($url, PATHINFO_EXTENSION) ?: 'svg';
            $filename = 'logo_' . uniqid() . '.' . $extension;
            $tempPath = $tempDir->getAbsolutePath($filename);

            // Create context for HTTPS requests to disable SSL verification for local development
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'method' => 'GET',
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ]);

            $logoContent = file_get_contents($url, false, $context);
            if ($logoContent) {
                file_put_contents($tempPath, $logoContent);
                return $tempPath;
            }
        } catch (\Exception $e) {
            $this->logger->error('Error downloading logo from URL: ' . $e->getMessage());
        }

        return null;
    }
}