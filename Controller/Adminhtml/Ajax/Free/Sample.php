<?php

namespace Cloudinary\Cloudinary\Controller\Adminhtml\Ajax\Free;

use Cloudinary\Cloudinary\Core\Image\Transformation\Freeform;
use Cloudinary\Cloudinary\Core\Image\Transformation;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Cloudinary\Cloudinary\Model\Config\Backend\Free as FreeBackendModel;
use Cloudinary\Cloudinary\Core\ConfigurationInterface;

class Sample extends Action
{
    const MISSING_ACCOUNT_DETAILS = 'Your Cloudinary account details must be configured';
    const NON_AJAX_REQUEST = 'Non-ajax call received.';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var FreeBackendModel
     */
    private $model;

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param FreeBackendModel $model
     * @param ConfigurationInterface $configuration
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        FreeBackendModel $model,
        ConfigurationInterface $configuration
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->model = $model;
        $this->configuration = $configuration;

        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $this->validateAjaxRequest();
            $this->validateAccountConfigured();

            $url = $this->model->sampleImageUrl(
                $this->defaultTransformWithFreeTransform($this->getRequest()->getParam('free'))
            );

            $this->model->validate($url);

            return $result->setData(['url' => $url]);
        } catch (\Exception $e) {
            return $result->setHttpResponseCode(400)->setData(['error' => $e->getMessage()]);
        }
    }

    /**
     * @param string $freeTransform
     * @return Transformation
     */
    private function defaultTransformWithFreeTransform($freeTransform)
    {
        return $this->configuration->getDefaultTransformation()
            ->withFreeform(Freeform::fromString($freeTransform));
    }

    /**
     * @throws \Exception
     */
    private function validateAjaxRequest()
    {
        if (!$this->getRequest()->isAjax()) {
            throw new \Exception(self::NON_AJAX_REQUEST);
        }
    }

    /**
     * @throws \Exception
     */
    private function validateAccountConfigured()
    {
        if (!$this->model->hasAccountConfigured()) {
            throw new \Exception(self::MISSING_ACCOUNT_DETAILS);
        }
    }
}
