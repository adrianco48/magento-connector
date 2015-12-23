<?php

use LizardsAndPumpkins\MagentoConnector\Api\Api;

class LizardsAndPumpkins_MagentoConnector_Model_Export_Content
{

    const XML_SPECIAL_BLOCKS = 'lizardsAndPumpkins/magentoconnector/cms_special_blocks';

    /**
     * @var Api
     */
    private $api;

    public function export()
    {
        $cmsBlocks = $this->getAllCmsBlocksWithStoreId();
        $this->api = $this->getApi();
        $this->exportCmsBlocks($cmsBlocks);
        $this->exportNonCmsBlocks();
    }

    /**
     * @return Mage_Core_Model_Resource_Db_Collection_Abstract
     */
    private function getAllCmsBlocksWithStoreId()
    {
        $cmsBlocks = Mage::getResourceModel('cms/block_collection')
            ->join(['block_store' => 'cms/block_store'], 'main_table.block_id=block_store.block_id', 'store_id')
            ->addExpressionFieldToSelect(
                'block_id',
                "CONCAT({{block_id}},'_', {{store_id}})",
                [
                    'block_id' => 'main_table.block_id',
                    'store_id' => 'store_id',
                ]
            );
        return $cmsBlocks;
    }

    /**
     * @return Api
     */
    private function getApi()
    {
        $apiUrl = Mage::getStoreConfig('lizardsAndPumpkins/magentoconnector/api_url');
        return new Api($apiUrl);
    }

    /**
     * @param $cmsBlocks
     */
    private function exportCmsBlocks($cmsBlocks)
    {
        foreach ($cmsBlocks as $block) {
            /* @var Mage_Cms_Model_Block $block */
            $context = [
                'locale' => Mage::getStoreConfig('general/locale/code', $block->getStoreId()),
            ];
            $this->api->triggerCmsBlockUpdate($block->getIdentifier(), $block->getContent(), $context);
        }
    }

    private function exportNonCmsBlocks()
    {
        /** @var $appEmulation Mage_Core_Model_App_Emulation */
        $specialBlocks = Mage::getStoreConfig(self::XML_SPECIAL_BLOCKS);
        if (!is_array($specialBlocks)) {
            return;
        }

        $specialBlocks = array_keys($specialBlocks);

        foreach (Mage::app()->getStores(true) as $store) {
            $layout = $this->emulateStore($store);

            foreach ($specialBlocks as $blockName) {
                $block = $layout->getBlock($blockName);
                if (!$block) {
                    continue;
                }

                $content = $block->toHtml();
                $context = [
                    'locale' => Mage::getStoreConfig('general/locale/code', $store->getId()),
                ];
                $this->api->triggerCmsBlockUpdate($block->getNameInLayout(), $content, $context);
            }
        }
    }

    /**
     * @param $store
     * @return Mage_Core_Model_Layout
     */
    private function emulateStore($store)
    {
        $storeId = $store->getId();
        Mage::app()->getLocale()->emulate($storeId);
        Mage::app()->setCurrentStore(Mage::app()->getStore($storeId));

        Mage::getDesign()->setArea('frontend')
            ->setStore($storeId);

        $designChange = Mage::getSingleton('core/design')
            ->loadChange($storeId);

        if ($designChange->getData()) {
            Mage::getDesign()->setPackageName($designChange->getPackage())
                ->setTheme($designChange->getTheme());
        }

        $layout = Mage::getModel('core/layout');
        $layout->setArea(Mage_Core_Model_App_Area::AREA_FRONTEND);
        $layout->getUpdate()->load('default');
        $layout->generateXml();
        $layout->generateBlocks();
        return $layout;
    }
}