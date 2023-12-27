<?php
    declare(strict_types=1);
    namespace Cloudinary\Cloudinary\Model\GraphQLResolver;

    use Magento\Framework\GraphQl\Config\Element\Field;
    use Magento\Framework\GraphQl\Query\ResolverInterface;
    use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
    use Cloudinary\Cloudinary\Model\Api\ProductGalleryManagement;
    use Magento\Framework\GraphQl\Type\Definition\ObjectType;

    /**
     * Class ProductAttributeCldResolver
     **/
    class ProductAttributeCldDataResolver implements ResolverInterface
    {
        /**
         * @var ProductGalleryManagement
         */
        private $productGalleryManagement;

        /**
         * ProductAttributeCldResolver constructor.
         * @param ProductGalleryManagement $productGalleryManagement
         */
        public function __construct(
            ProductGalleryManagement $productGalleryManagement
        ) {
            $this->productGalleryManagement = $productGalleryManagement;
        }

        /**
         * @inheritdoc
         */
        public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
        {
            $productId = $value['sku'];
            $productMediaStr = $this->productGalleryManagement->getProductMediaData($productId);
            $jsonDecoder = new \Magento\Framework\Serialize\Serializer\Json();
            $productMedia = $jsonDecoder->unserialize($productMediaStr);
            return $productMedia['data'];
        }
    }