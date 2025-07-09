<?php

namespace Cloudinary\Cloudinary\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class ProductGalleryCustomFreeParams extends \Magento\Framework\App\Config\Value
{
    const BAD_JSON_ERROR_MESSAGE = "Json error on 'Custom free parameters' please correct.";

    const JSON_ERRORS = [
        JSON_ERROR_NONE => 'No error',
        JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
        JSON_ERROR_STATE_MISMATCH => 'State mismatch (invalid or malformed JSON)',
        JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
        JSON_ERROR_SYNTAX => 'Syntax error',
        JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
    ];

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * Application config
     *
     * @var ScopeConfigInterface
     */
    protected $appConfig;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        ManagerInterface $messageManager,
        ReinitableConfigInterface $appConfig,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->messageManager = $messageManager;
        $this->appConfig = $appConfig;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function beforeSave()
    {
        $rawValue = $this->getValue();

        parent::beforeSave();

        $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
        $this->appConfig->reinit();

        if ($rawValue) {
            $data = json_decode($rawValue);
            if ($data === null || $data === false) {
                $this->setValue('{}');
                try {
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $this->messageManager->addError(self::BAD_JSON_ERROR_MESSAGE . ' (' . self::JSON_ERRORS[json_last_error()] . ')');
                    }
                } catch (\Exception $e) {
                    $this->messageManager->addError(self::BAD_JSON_ERROR_MESSAGE);
                }
            } else {
                $this->setValue(json_encode((array)$data));
            }
        } else {
            $this->setValue('{}');
        }
    }
}
