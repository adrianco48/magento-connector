<?php

class LizardsAndPumpkins_MagentoConnector_Model_Export_SpecificCategoryCollectorTest
    extends \PHPUnit\Framework\TestCase
{
    public function testCategoryUrlKeySuffixIsAppendedSeparatedByADot()
    {
        $config = new LizardsAndPumpkins_MagentoConnector_Model_Export_MagentoConfig();
        $categoryUrlPathSuffix = Mage::getStoreConfig(Mage_Catalog_Helper_Category::XML_PATH_CATEGORY_URL_SUFFIX);
        
        $expectedSuffix = '.' === $categoryUrlPathSuffix[0] ? $categoryUrlPathSuffix : '.' . $categoryUrlPathSuffix;
        
        $categoryCollector = new LizardsAndPumpkins_MagentoConnector_Model_Export_SpecificCategoryCollector($config);
        
        $categoryCollector->setCategoryIdsToExport([4]);
        
        $category = $categoryCollector->getCategory();
        $this->assertStringEndsWith($expectedSuffix, $category->getData('url_path'));
    }
}
