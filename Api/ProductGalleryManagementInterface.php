<?php

namespace Cloudinary\Cloudinary\Api;

interface ProductGalleryManagementInterface
{

    /**
     * Add product gallery item from Cloudinary URL.
     * @method addItem
     * @param  string       $url
     * @param  string       $sku
     * @param  string|null  $publicId
     * @param  string|null  $roles
     * @return string
     */
    public function addItem($url, $sku, $publicId = null, $roles = null);

    /**
     * Add multiple product gallery items from Cloudinary URLs.
     * @method addItems
     * @param  mixed  $items
     * @return string
     */
    public function addItems($items);
}
