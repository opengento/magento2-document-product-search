<?php
/**
 * Copyright © OpenGento, All rights reserved.
 * See LICENSE bundled with this library for license details.
 */
declare(strict_types=1);

namespace Opengento\DocumentProductSearch\Model\Collection;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Data\CollectionModifierInterface;
use Magento\Framework\DB\Helper;
use Opengento\DocumentSearch\Model\QueryData;

final class ProductModifier implements CollectionModifierInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var QueryData
     */
    private $queryData;

    /**
     * @var Helper
     */
    private $dbHelper;

    public function __construct(
        CollectionFactory $collectionFactory,
        QueryData $queryData,
        Helper $dbHelper
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->queryData = $queryData;
        $this->dbHelper = $dbHelper;
    }

    public function apply(AbstractDb $documentCollection): void
    {
        $term = $this->dbHelper->escapeLikeValue($this->queryData->getTerm(), ['position' => 'any']);

        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addStoreFilter();
        $collection->addAttributeToFilter(
            [
                ['attribute' => ProductInterface::NAME, ['like' => $term]],
                ['attribute' => ProductInterface::SKU, ['like' => $term]],
            ]
        );
        $productIds = $collection->getAllIds();
        if ($productIds) {
            $select = $documentCollection->getSelect();
            $select->joinLeft(
                ['odpl' => 'opengento_document_product_link'],
                'main_table.entity_id=odpl.document_id',
                ''
            );
            $select->orWhere('odpl.product_id IN (?)', $productIds);
        }
    }
}
