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
     * @return string
     */
    public function addItem($url, $sku, $publicId = null, $roles = null, $label = null, $disabled = 0);

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
}
