<?php

namespace Cloudinary\Cloudinary\Ui\Component\Control;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Cloudinary\Cloudinary\Block\Adminhtml\Cms\Wysiwyg\Images\Content;
use Magento\Framework\AuthorizationInterface;


class AddFromCloudinary implements ButtonProviderInterface
{
    private const ACL_UPLOAD_ASSETS= 'Magento_MediaGalleryUiApi::upload_assets';

    /**
     * @var Content
     */
    protected $images;

    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @var \Magento\Cms\Helper\Wysiwyg\Images
     */
    protected $cmsWysiwygImages;

    /**
     * @param Content $images
     * @param AuthorizationInterface $authorization
     */
    public function __construct(
        Content $images,
        AuthorizationInterface $authorization,
        \Magento\Cms\Helper\Wysiwyg\Images $cmsWysiwygImages
    ){
        $this->images = $images;
        $this->authorization =  $authorization;
        $this->cmsWysiwygImages = $cmsWysiwygImages;
    }
    /**
     * @inheritdoc
     */
    public function getButtonData(): array
    {
        $cloudinaryMLwidgetOprions = ($this->images->getCloudinaryMediaLibraryWidgetOptions())
            ? json_decode($this->images->getCloudinaryMediaLibraryWidgetOptions(),true)
            : null;

        $buttonData = [
            'label' => __('Add From Cloudinary'),
            'class' => 'action-secondary add-from-cloudinary-button cloudinary-button-with-logo lg-margin-bottom',
            'on_click' => 'return false;',
            'data_attribute' => [
                'mage-init' => ['cloudinaryMediaLibraryModal' => $cloudinaryMLwidgetOprions],
                'role' => 'add-from-cloudinary-button',

            ],
            'sort_order' => 200,
        ];

        if (!$this->authorization->isAllowed(self::ACL_UPLOAD_ASSETS)) {
            $buttonData['disabled'] = 'disabled';
        }

        return $buttonData;
    }
}
