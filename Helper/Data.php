<?php
/**
 * Copyright © 2017 Studio Raz. All rights reserved.
 * For more information contact us at dev@studioraz.co.il
 * See COPYING_STUIDRAZ.txt for license details.
 */
namespace SR\PreviousNextNavigation\Helper;

/**
 * Class Data
 *
 * @package SR\PreviousNextNavigation\Helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const NEXT = true;
    const PREV = false;

    /**
     * Registry model
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * Product repository model
     *
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Url
     */
    protected $_catalogUrl;

    /**
     * Class constructor.
     *
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\ResourceModel\Url $catalogUrl,
        \Magento\Framework\App\ResourceConnection $resource
    )
    {
        $this->_coreRegistry = $coreRegistry;
        $this->_productRepository = $productRepository;
        $this->_resource = $resource;
        $this->_catalogUrl = $catalogUrl;

        parent::__construct($context);
    }

    /**
     * Return previous model in category.
     *
     * @return bool|\Magento\Catalog\Api\Data\ProductInterface
     */
    public function getNextProduct()
    {
        return $this->getSiblingProduct(self::NEXT);
    }

    /**
     * Return next model in category.
     *
     * @return bool|\Magento\Catalog\Api\Data\ProductInterface
     */
    public function getPreviousProduct()
    {
        return $this->getSiblingProduct(self::PREV);
    }

    /**
     * Return next or previous product model in category.
     *
     * @param bool $isNext
     *
     * @return bool|\Magento\Catalog\Api\Data\ProductInterface
     */
    protected function getSiblingProduct($isNext)
    {
        $prodId = $this->_coreRegistry->registry('current_product')->getId();

        $category =  $this->_coreRegistry->registry('current_category');

        if($category){
            $catArray = $this->getProductsPosition($category);
            //$catArray = $category->getProductsPosition();

            $keys = array_flip(array_keys($catArray));
            $values = array_keys($catArray);

            if ($isNext) {
                $siblingId = $keys[$prodId] + 1;
            } else {
                $siblingId = $keys[$prodId] - 1;
            }

            if (!isset($values[$siblingId])) {
                return false;
            }
            $productId = $values[$siblingId];

            $product = $this->_productRepository->getById($productId);

            $product->setCategoryId($category->getId());
            $urlData = $this->_catalogUrl->getRewriteByProductStore([$product->getId() => $category->getStoreId()]);
            if (!isset($urlData[$product->getId()])) {
                $product->setUrlDataObject(new \Magento\Framework\DataObject($urlData[$product->getId()]));
            }

            if($product->getId()) {
                return $product;
            }
            return false;
        }

        return false;
    }


    public function getProductsPosition($category)
    {
        $connection = $this->_resource->getConnection();
        $tableName = $this->_resource->getTableName('catalog_category_product_index');

        $select = $connection->select()->from(
            $tableName,
            ['product_id', 'position']
        )->where(
            'category_id = :category_id'
        )->where(
            'store_id = :store_id'
        )->order('position', 'ASC');

        $bind = [
            'category_id' => (int)$category->getId(),
            'store_id' => $category->getStoreId(),
        ];

        return $connection->fetchPairs($select, $bind);

    }
}