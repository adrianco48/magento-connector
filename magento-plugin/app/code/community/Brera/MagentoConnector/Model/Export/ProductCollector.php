<?php

class Brera_MagentoConnector_Model_Export_ProductCollector
{
    /**
     * @param Mage_Core_Model_Store $store
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function getAllQueuedProductUpdates($store)
    {
        $productUpdateAction = Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_STOCK_UPDATE;
        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->setStore($store);
        $collection->joinTable(
            'brera_magentoconnector/product_queue',
            'entity_id=product_id',
            '',
            'action=' . $productUpdateAction
        )
            ->addAttributeToSelect('*');

        return $collection;
    }

    public function getAllProducts($store)
    {
        /** @var $collection Mage_Catalog_Model_Resource_Product_Collection */
        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->setStore($store);
        $collection->addAttributeToSelect('*');

        return $collection;
    }

    public function getAllProductStockUpdates($store)
    {
        $stockUpdateAction = Brera_MagentoConnector_Model_Product_Queue_Item::ACTION_STOCK_UPDATE;

        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->setStore($store);
        $collection->joinTable(
            'brera_magentoconnector/product_queue',
            'entity_id=product_id',
            '',
            'action=' . $stockUpdateAction
        )
            ->addAttributeToSelect('*');

        return $collection;
    }


    /**
     * add media gallery images to collection
     *
     * @param Mage_Catalog_Model_Resource_Product_Collection $productCollection
     * @param Mage_Core_Model_Store $store
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     * @see  http://www.magentocommerce.com/boards/viewthread/17414/#t141830
     */
    private function addMediaGalleryAttributeToCollection(
        Mage_Catalog_Model_Resource_Product_Collection $productCollection,
        Mage_Core_Model_Store $store
    ) {
        $storeId = $store->getId();
        $mediaGalleryAttributeId = Mage::getSingleton('eav/config')->getAttribute('catalog_product', 'media_gallery')
            ->getAttributeId();
        $readConnection = Mage::getSingleton('core/resource')->getConnection('catalog_read');

        $mediaGalleryData = $readConnection->fetchAll(
            '
        SELECT
            main.entity_id, `main`.`value_id`, `main`.`value` AS `file`,
            `value`.`label`, `value`.`position`, `value`.`disabled`, `default_value`.`label` AS `label_default`,
            `default_value`.`position` AS `position_default`,
            `default_value`.`disabled` AS `disabled_default`
        FROM `catalog_product_entity_media_gallery` AS `main`
            LEFT JOIN `catalog_product_entity_media_gallery_value` AS `value`
                ON main.value_id=value.value_id AND value.store_id=' . $storeId . '
            LEFT JOIN `catalog_product_entity_media_gallery_value` AS `default_value`
                ON main.value_id=default_value.value_id AND default_value.store_id=0
        WHERE (
            main.attribute_id = ' . $readConnection->quote($mediaGalleryAttributeId) . ')
            AND (main.entity_id IN (' . $readConnection->quote($productCollection->getAllIds()) . '))
        ORDER BY IF(value.position IS NULL, default_value.position, value.position) ASC
    '
        );

        $mediaGalleryByProductId = array();
        foreach ($mediaGalleryData as $galleryImage) {
            $k = $galleryImage['entity_id'];
            unset($galleryImage['entity_id']);
            if (!isset($mediaGalleryByProductId[$k])) {
                $mediaGalleryByProductId[$k] = array();
            }
            $mediaGalleryByProductId[$k][] = $galleryImage;
        }
        unset($mediaGalleryData);

        foreach ($productCollection as $product) {
            $productId = $product->getData('entity_id');
            if (isset($mediaGalleryByProductId[$productId])) {
                $product->setData('media_gallery', array('images' => $mediaGalleryByProductId[$productId]));
            }
        }
        unset($mediaGalleryByProductId);

        return $productCollection;
    }

    /**
     * @param Mage_Core_Model_Store $store
     * @param Mage_Catalog_Model_Resource_Product_Collection $collection
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    public function addStockItemsAndMediaGallery(
        Mage_Core_Model_Store $store,
        Mage_Catalog_Model_Resource_Product_Collection $collection
    ) {
        Mage::getSingleton('cataloginventory/stock')
            ->addItemsToProducts($collection);

        return $this->addMediaGalleryAttributeToCollection($collection, $store);
    }
}
