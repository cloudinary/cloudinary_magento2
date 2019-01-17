<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Cloudinary\Cloudinary\Controller\Adminhtml\Product\Gallery;

use Cloudinary\Cloudinary\Core\ConfigurationInterface;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\HTTP\Client\Curl;

class Upload extends \Magento\Catalog\Controller\Adminhtml\Product\Gallery\Upload implements HttpPostActionInterface
{
    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var ConfigurationInterface
     */
    protected $configuration;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param Curl $curl
     * @param ConfigurationInterface $configuration
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        Curl $curl,
        ConfigurationInterface $configuration
    ) {
        parent::__construct($context, $resultRawFactory);
        $this->curl = $curl;
        $this->configuration = $configuration;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        try {
            if ($this->configuration->isEnabled()) {
                $asset = $this->getRequest()->getPostValue("asset");
                $this->curl->get($asset['url']);
                $asset['image'] = $this->curl->getBody();
                $this->getRequest()->setPostValue("asset", $asset);

                $tmpfile = tmpfile();
                fwrite($tmpfile, $asset['image']);

                $_FILES['image'] = [
                    "name" => basename($asset['url']),
                    "type" => "{$asset['resource_type']}/{$asset['format']}",
                    "tmp_name" => stream_get_meta_data($tmpfile)['uri'],
                    "error" => 0,
                    "size" => $asset['bytes'],
                ];
            }
        } catch (\Exception $e) {
            $response = $this->resultRawFactory->create();
            $response->setHeader('Content-type', 'text/plain');
            $response->setContents(json_encode(['error' => $e->getMessage(), 'errorcode' => $e->getCode()]));
            return $response;
        }

        return parent::execute();
    }
}
