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

class VideoSettingsFreeParams extends \Magento\Framework\App\Config\Value
{
    const BAD_JSON_ERROR_MESSAGE = "Json error on 'Video free parameters' please correct.";

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
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        ManagerInterface $messageManager,
        ReinitableConfigInterface $appConfig,
        array $data = []
    ) {
        $this->messageManager = $messageManager;
        $this->appConfig = $appConfig;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }


    protected function jsonDecode($jsonString)
    {
        $json = preg_replace('/\r|\n/','',trim($jsonString));
        $json = str_replace(
                array('"',  "'"),
                array('\"', '"'),
            $json
            );

        $start = strpos($json, 'logoImageUrl');
        // Find the end of the URL (first comma after the start of logoImageUrl)
        $end = strpos($json, ',', $start);
        $logoUrl = null;
        if ($start) {

            $logoUrl = substr($json, $start, $end - $start);
            $json = substr($json, 0, $start) . substr($json, $end + 1);
        }
        $linkStart = strpos($json, 'logoOnclickUrl');
        $linkEnd = strpos($json, ',',$linkStart);
        $linkUrl = null;
        if ($linkStart) {
            $linkUrl = substr($json, $linkStart, $linkEnd - $linkStart);
            $json = substr($json, 0, $linkStart) . substr($json, $linkEnd +1);
        }

        // Extract the logoImageUrl part

        $json = preg_replace('/(\w+):/', '"$1":', $json);
        $arr = json_decode($json, true);

        if (is_array($arr)) {
            if ($logoUrl) {
                $url = str_replace("logoImageUrl:", "", $logoUrl);
                $url = str_replace('"', "", $url);
                $arr['player']['logoImageUrl'] = $url;
            }
            if ($linkUrl) {
                $url = str_replace("logoOnclickUrl:", "", $linkUrl);
                $url = str_replace('"', "", $url);
                $arr['player']['logoOnclickUrl'] = $url;
            }
            $json = json_encode($arr);
        }
        // $json = str_replace("'", '"', $json);
        return (json_decode($json)) ? $json : $jsonString;
    }

    function isJson($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public function beforeSave()
    {
        $rawValue = $this->getValue();

        parent::beforeSave();

        $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
        $this->appConfig->reinit();

        if ($rawValue) {

            $data = $this->jsonDecode($rawValue);
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
                if ($this->isJson($data)) {

                    return $this->setValue($data);
                }
                $this->setValue('{}');
            }
        } else {
            $this->setValue('{}');
        }
    }
}
