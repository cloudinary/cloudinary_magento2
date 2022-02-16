<?php

namespace Cloudinary\Cloudinary\Api;

interface ProductGalleryManagementInterface
{

    /**
     * [!] DEPRECATED, please use addProductMedia() instead.
     * Add product gallery item from Cloudinary URL.
     * @method addItem
     * @param  string        $url
     * @param  string        $sku
     * @param  string|null   $publicId
     * @param  string|null   $roles
     * @param  string|null   $label
     * @param  bool|int|null $disabled
     * @param  string        $cldspinset
     * @return string
     */
    public function addItem($url = null, $sku = null, $publicId = null, $roles = null, $label = null, $disabled = 0, $cldspinset = null);

    /**
     * Add multiple gallery items to one or more products from Cloudinary URLs.
     * @method addItems
     * @param  mixed  $items
     * @return string
     */
    public function addItems($items);

    /**
     * Add product gallery items from Cloudinary URLs.
     * @method addItem
     * @param  string  $sku
     * @param  mixed   $urls
     * @return string
     */
    public function addProductMedia($sku, $urls);

    /**
     * Get product gallery items as Cloudinary URLs.
     * @method getProductMedia
     * @param  string  $sku
     * @return string
     */
    public function getProductMedia($sku, $onlyUrls = true);

    /**
     * Get products gallery items as Cloudinary URLs.
     * @method getProductsMedia
     * @param  mixed  $skus
     * @return string
     */
    public function getProductsMedia($skus);
}
