<?php

namespace Cloudinary\Cloudinary\Controller\Adminhtml\PageBuilder\MediaGallery;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Upload extends Action implements HttpPostActionInterface
{

    private const HTTP_OK = 200;
    private const HTTP_INTERNAL_ERROR = 500;
    private const HTTP_BAD_REQUEST = 400;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Action\Context $context
     * @param LoggerInterface $logger
     */
    public function __construct(
        Action\Context $context,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->logger = $logger;
    }

    public function execute()
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try{
            $params = $this->getRequest()->getParams();

        } catch (NoSuchEntityException $e) {
            $responseCode = self::HTTP_OK;
            $responseContent = [];
        } catch (\Exception $e) {
            $responseCode = self::HTTP_INTERNAL_ERROR;
            $this->logger->critical($e);
            $responseContent = [
                'success' => false,
                'message' => __('An error occurred on attempt to retrieve asset information.'),
            ];
        }

        $resultJson->setHttpResponseCode($responseCode);
        $resultJson->setData($responseContent);

        return $resultJson;
    }
}
