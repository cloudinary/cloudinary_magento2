<?php

namespace Cloudinary\Cloudinary\Api;

use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface SynchronisationRepositoryInterface
{
    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param  string $imagePath
     *
     * @return SearchResultsInterface
     */
    public function getListByImagePath($imagePath);
}
