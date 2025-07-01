<?php
    declare(strict_types=1);
    namespace Cloudinary\Cloudinary\Model\GraphQLResolver;

    use Cloudinary\Cloudinary\Model\Configuration;
    use Magento\Framework\Exception\LocalizedException;
    use Magento\Framework\GraphQl\Config\Element\Field;
    use Magento\Framework\GraphQl\Query\ResolverInterface;
    use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
    use Cloudinary\Cloudinary\Model\Api\ProductGalleryManagement;
    use Magento\Framework\GraphQl\Type\Definition\ObjectType;
    use Magento\Catalog\Api\ProductRepositoryInterface;
    use Magento\Store\Model\StoreManagerInterface;
    use Magento\Framework\UrlInterface;
    /**
     * Class ProductAttributeCldResolver
     **/
    class ProductAttributeCldResolver implements ResolverInterface
    {
        /**
         * @var ProductGalleryManagement
         */
        private $productGalleryManagement;

        protected $productRepository;

        private $urlBuilder;
        private $storeManager;

        protected $_sku;

        protected $_configuration;

        /**
         * ProductAttributeCldResolver constructor.
         * @param ProductGalleryManagement $productGalleryManagement
         */
        public function __construct(
            ProductGalleryManagement $productGalleryManagement,
            ProductRepositoryInterface $productRepository,
            Configuration $configuration,
            UrlInterface $urlBuilder,
            StoreManagerInterface $storeManager,
        ) {
            $this->productGalleryManagement = $productGalleryManagement;
            $this->productRepository = $productRepository;
            $this->urlBuilder = $urlBuilder;
            $this->storeManager = $storeManager;
            $this->_configuration = $configuration;
        }

        /**
         * @inheritdoc
         */
        public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
        {
            $sku = $value['sku'] ?? null;
            if ($sku) {
                $this->_sku = $sku;
            }

            $attributeCodes = $args['attribute_codes'] ?? null;
            if ($attributeCodes && is_array($attributeCodes)) {
                $this->checkEnabled();
                $product = $this->productRepository->get($this->_sku);
                $mediaAttributes = [];
                  foreach ($attributeCodes as $attributeCode) {
                      $attrValue = $product->getData($attributeCode);
                      if ($attrValue) {
                          foreach ($product->getMediaGalleryImages() as $gallItem) {
                              if ($attrValue == $gallItem->getFile()) {
                                  $mediaAttributes[] = [
                                      'attribute_code' => $attributeCode,
                                      'url' => $gallItem->getUrl()
                                  ];
                              }
                          }

                      }
                  }
                  return $mediaAttributes;
            }
            $productMediaStr = $this->productGalleryManagement->getProductMedia($this->_sku);
            $jsonDecoder = new \Magento\Framework\Serialize\Serializer\Json();
            $productMedia = $jsonDecoder->unserialize($productMediaStr);

            return $productMedia['data'];
        }


        private function checkEnabled() {
            if (!$this->_configuration->isEnabled()) {
                throw new LocalizedException(
                    __("Cloudinary module is disabled. Please enable it first in order to use this API.")
                );
            }
            return $this;
        }
    }
