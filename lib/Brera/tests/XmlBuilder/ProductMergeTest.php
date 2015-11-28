<?php

namespace Brera\MagentoConnector\XmlBuilder;

/**
 * @covers \Brera\MagentoConnector\XmlBuilder\ProductMerge
 */
class ProductMergeTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var ProductMerge
     */
    private $merge;

    public function testEmptyXml()
    {
        $xml = $this->merge->finish();

        $namespaces = [
            'xmlns="http://lizardsandpumpkins\.com"',
            'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"',
            'xsi:schemaLocation="http://lizardsandpumpkins\.com \.\./\.\./schema/catalog\.xsd"',
        ];
        foreach ($namespaces as $ns) {
            $this->assertRegExp("#<catalog .*$ns.*>#Us", $xml);
        }
    }

    public function testProductIsAdded()
    {
        $expectedXml = '<product>my product</product>';
        $this->merge->addProduct(new ProductContainer('<?xml version="1.0"?>' . $expectedXml));
        $xml = $this->merge->finish();
        $this->assertContains($expectedXml, $xml);
        $this->assertRegExp('#<products>#', $xml);
    }

    public function testPartialString()
    {
        $expectedXml = '<product>my product</product>';
        $this->merge->addProduct(new ProductContainer('<?xml version="1.0"?>' . $expectedXml));
        $xml = $this->merge->getPartialXmlString();
        $this->assertContains($expectedXml, $xml);
        $this->assertRegExp('#<products>#', $xml);
        $this->assertNotContains('</products>', $xml);

        $xml = $this->merge->finish();
        $this->assertContains('</products>', $xml);
        $this->assertContains('</catalog>', $xml);
    }

    protected function setUp()
    {
        $this->merge = new ProductMerge();
    }
}
