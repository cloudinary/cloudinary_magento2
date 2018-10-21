<?php

namespace Cloudinary\Cloudinary\Model;

use Cloudinary\Cloudinary\Core\SynchroniseAssetsRepositoryInterface;

use Cloudinary\Cloudinary\Api\SynchronisationRepositoryInterface;
use Cloudinary\Cloudinary\Model\SynchronisationFactory;
use Cloudinary\Cloudinary\Model\ResourceModel\Synchronisation\CollectionFactory;
use Cloudinary\Cloudinary\Model\ResourceModel\Synchronisation\Collection as SynchronisationCollection;

use Magento\Framework\Api\AbstractSimpleObject;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;

class SynchronisationRepository
    implements SynchronisationRepositoryInterface, SynchroniseAssetsRepositoryInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var SearchResultsInterface
     */
    private $searchResult;

    /**
     * @var SearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SynchronisationFactory
     */
    private $synchronisationFactory;

    /**
     * @param FilterBuilder                 $filterBuilder
     * @param SearchCriteriaBuilder         $searchCriteriaBuilder
     * @param CollectionFactory             $collectionFactory
     * @param SearchResultsInterface        $searchResult
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param SynchronisationFactory        $synchronisationFactory
     */
    public function __construct(
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CollectionFactory $collectionFactory,
        SearchResultsInterface $searchResult,
        SearchResultsInterfaceFactory $searchResultsFactory,
        SynchronisationFactory $synchronisationFactory
    ) {
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->collectionFactory = $collectionFactory;
        $this->searchResult = $searchResult;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->synchronisationFactory = $synchronisationFactory;
    }

    /**
     * Retrieve data which match a specified criteria.
     *
     * @api
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->collectionFactory->create();

        $this->setFilters($searchCriteria, $collection);
        $this->setSortOrder($searchCriteria, $collection);
        $this->setPageSize($searchCriteria, $collection);
        $this->setCurrentPage($searchCriteria, $collection);

        $searchResult = $this->searchResultsFactory->create();
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());

        return $searchResult;
    }

    /**
     * @param  string $imagePath
     *
     * @return SearchResultsInterface
     */
    public function getListByImagePath($imagePath)
    {
        $this->searchCriteriaBuilder->addFilters([$this->createImagePathFilter($imagePath)]);

        return $this->getList($this->searchCriteriaBuilder->create());
    }

    /**
     * @param  string      $imagePath
     */
    public function saveAsSynchronized($imagePath)
    {
        $this->synchronisationFactory->create()
            ->setImagePath($imagePath)
            ->tagAsSynchronized();
    }

    /**
     * @param string $imagePath
     */
    public function removeSynchronised($imagePath)
    {
        $result = $this->getListByImagePath($imagePath);

        foreach ($result->getItems() as $item) {
            $item->delete();
        }
    }

    /**
     * Create image name filter
     *
     * @param string $imagePath
     * @return \Magento\Framework\Api\Filter
     */
    private function createImagePathFilter($imagePath)
    {
        $this->filterBuilder->setField('image_path');
        $this->filterBuilder->setConditionType('eq');
        $this->filterBuilder->setValue($imagePath);

        return $this->filterBuilder->create();
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @param SynchronisationCollection     $collection
     */
    private function setFilters(SearchCriteriaInterface $searchCriteria, $collection)
    {
        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $collection->addFieldToFilter(
                    $filter->getField(),
                    [$filter->getConditionType() => $filter->getValue()]
                );
            }
        }
    }

    /**
     * @param SearchCriteriaInterface   $searchCriteria
     * @param SynchronisationCollection $collection
     */
    private function setSortOrder(SearchCriteriaInterface $searchCriteria, $collection)
    {
        if ($searchCriteria->getSortOrders()) {
            foreach ($searchCriteria->getSortOrders() as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    $sortOrder->getDirection() === SearchCriteriaInterface::SORT_ASC ? 'ASC' : 'DESC'
                );
            }
        }
    }

    /**
     * @param SearchCriteriaInterface   $searchCriteria
     * @param SynchronisationCollection $collection
     */
    private function setPageSize(SearchCriteriaInterface $searchCriteria, $collection)
    {
        if ($searchCriteria->getPageSize()) {
            $collection->setPageSize($searchCriteria->getPageSize());
        }
    }

    /**
     * @param SearchCriteriaInterface   $searchCriteria
     * @param SynchronisationCollection $collection
     */
    private function setCurrentPage(SearchCriteriaInterface $searchCriteria, $collection)
    {
        if ($searchCriteria->getCurrentPage()) {
            $collection->setCurPage($searchCriteria->getCurrentPage());
        }
    }
}
