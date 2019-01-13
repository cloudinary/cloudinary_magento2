<?php

namespace Cloudinary\Cloudinary\Model\Template;

use Magento\Widget\Model\Template\Filter as WidgetFilter;

class Filter extends WidgetFilter
{
    /**
     * Return associative array of parameters *exposing $this->getParameters().
     *
     * @param  string $value raw parameters
     * @return array
     */
    public function getParams($value)
    {
        return $this->getParameters($value);
    }
}
