<?php

namespace Cloudinary\Cloudinary\Controller\Adminhtml\Ajax\System\Config;

use Cloudinary\Cloudinary\Core\AutoUploadMapping\RequestProcessor;
use Cloudinary\Cloudinary\Model\Configuration;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AutoUploadMapping extends Action
{
    const AUTO_UPLOAD_SETUP_FAIL_MESSAGE = 'Error. Unable to setup auto upload mapping.';
    const NON_AJAX_REQUEST = 'Rejected: Non-ajax request';

    /**
     * @var JsonFactory
     */
    private $jsonResultFactory;

    /**
     * @var RequestProcessor
     */
    protected $requestProcessor;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * Application config
     *
     * @var ScopeConfigInterface
     */
    protected $appConfig;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @method __construct
     * @param  Context                   $context
     * @param  JsonFactory               $jsonResultFactory
     * @param  RequestProcessor          $requestProcessor
     * @param  ManagerInterface          $messageManager
     * @param  Configuration             $configuration
     * @param  TypeListInterface         $cacheTypeList
     * @param  ReinitableConfigInterface $config
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        RequestProcessor $requestProcessor,
        ManagerInterface $messageManager,
        Configuration $configuration,
        TypeListInterface $cacheTypeList,
        ReinitableConfigInterface $config
    ) {
        parent::__construct($context);
        $this->jsonResultFactory = $jsonResultFactory;
        $this->requestProcessor = $requestProcessor;
        $this->messageManager = $messageManager;
        $this->configuration = $configuration;
        $this->cacheTypeList = $cacheTypeList;
        $this->appConfig = $config;
    }

    /**
     * @return ResultInterface
     */
    public function execute()
    {
        try {
            $this->validateAjaxRequest();
            $this->cleanConfigCache();

            if ($this->configuration->isEnabled()) {
                if (!$this->requestProcessor->handle(DirectoryList::MEDIA, $this->configuration->getMediaBaseUrl(), true)) {
                    throw new \Exception(self::AUTO_UPLOAD_SETUP_FAIL_MESSAGE);
                }
            }
        } catch (\Exception $e) {
            return $this->jsonResultFactory->create()
                ->setHttpResponseCode(500)
                ->setData(['error' => 1, 'message' => "ERROR during the mapping process: " . $e->getMessage(), 'errorcode' => $e->getCode()]);
        }

        return $this->jsonResultFactory->create()
            ->setHttpResponseCode(\Magento\Framework\Webapi\Response::HTTP_OK)
            ->setData(['error' => 0, 'message' => 'Successfully mapped media directory!']);
    }

    protected function cleanConfigCache()
    {
        $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
        $this->appConfig->reinit();
        return $this;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Cloudinary_Cloudinary::config_cloudinary');
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
}
