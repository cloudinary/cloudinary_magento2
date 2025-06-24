<?php

namespace Cloudinary\Cloudinary\Plugin\Catalog\Block\Category;

use Magento\Catalog\ViewModel\Category\Image as CategoryImageViewModel;
use Cloudinary\Asset\Media;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Cloudinary\Cloudinary\Core\ConfigurationBuilder;
use Cloudinary\Cloudinary\Core\Image\Transformation;
use Cloudinary\Configuration\Configuration;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Catalog\Model\Category;

class Image
{
    protected $configuration;
    protected $configurationBuilder;
    protected $transformation;

    private $authorised = false;

    public function __construct(
        ConfigurationInterface $configuration,
        ConfigurationBuilder $configurationBuilder,
        Transformation $transformation
    ) {
        $this->configuration = $configuration;
        $this->configurationBuilder = $configurationBuilder;
        $this->transformation = $transformation;
    }

    protected function authorise()
    {
        if (!$this->authorised && $this->configuration->isEnabled()) {
            Configuration::instance($this->configurationBuilder->build());
            $this->authorised = true;
        }
    }

    /**
     * Plugin after getUrl to return Cloudinary version
     */
    public function afterGetUrl(
        \Magento\Catalog\ViewModel\Category\Image $subject,
        string $result,
        Category $category,
        string $attributeCode = 'image'
    ): string {
        $this->authorise();

        $imagePath = $category->getData($attributeCode);
        if (!$this->configuration->isEnabled() || !$imagePath) {
            return $result;
        }

        try {

            $filename = pathinfo($imagePath, PATHINFO_FILENAME);
            $publicId = preg_replace('/^cld_[a-f0-9]+_/', '', $filename);

            return Media::fromParams($publicId, [
                'transformation' => $this->transformation->build(),
                'secure' => true,
                'sign_url' => $this->configuration->getUseSignedUrls(),
                'version' => 1
            ]);
        } catch (\Exception $e) {
            return $result; // fallback to original if Cloudinary fails
        }
    }
}
