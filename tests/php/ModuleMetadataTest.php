<?php

require_once dirname(__FILE__) . '/ViewedProductTestCase.php';

class ModuleMetadataTest extends ViewedProductTestCase
{
    public function testClassImplementsWidgetInterface()
    {
        $this->assertTrue(is_subclass_of('Ps_Viewedproduct', 'Module'));
        $this->assertTrue(in_array('PrestaShop\\PrestaShop\\Core\\Module\\WidgetInterface', class_implements('Ps_Viewedproduct')));
        $this->assertTrue(method_exists($this->module, 'renderWidget'));
        $this->assertTrue(method_exists($this->module, 'getWidgetVariables'));
    }

    public function testConstructorPublishesModuleMetadata()
    {
        $this->assertSame('ps_viewedproduct', $this->module->name);
        $this->assertSame('PrestaShop', $this->module->author);
        $this->assertSame('1.2.5', $this->module->version);
        $this->assertSame('front_office_features', $this->module->tab);
        $this->assertSame(0, $this->module->need_instance);
        $this->assertTrue($this->module->bootstrap);
        $this->assertSame('8.1.0', $this->module->ps_versions_compliancy['min']);
        $this->assertSame('8.1.7.0', $this->module->ps_versions_compliancy['max']);
        $property = new ReflectionProperty('Ps_Viewedproduct', 'templateFile');
        $property->setAccessible(true);
        $this->assertSame(
            'module:ps_viewedproduct/views/templates/hook/ps_viewedproduct.tpl',
            $property->getValue($this->module)
        );

        $ids = array();
        foreach (Stubs::$translationLog as $entry) {
            $ids[] = $entry['id'];
        }
        $this->assertContains('Viewed products block', $ids);
        $this->assertContains(
            'Display a kind of showcase on your product pages with recently viewed products.',
            $ids
        );
        $this->assertLoggedTranslationDomain('Viewed products block', 'Modules.Viewedproduct.Admin');
        $this->assertLoggedTranslationDomain(
            'Display a kind of showcase on your product pages with recently viewed products.',
            'Modules.Viewedproduct.Admin'
        );
    }

    public function testConfigXmlMatchesClassVersion()
    {
        $xmlPath = dirname(dirname(__DIR__)) . '/config.xml';
        $this->assertTrue(is_file($xmlPath));
        $xml = simplexml_load_file($xmlPath);
        $this->assertSame('ps_viewedproduct', (string) $xml->name);
        $this->assertSame('1.2.5', (string) $xml->version);
        $this->assertSame('front_office_features', (string) $xml->tab);
        $this->assertSame('0', (string) $xml->need_instance);
        $this->assertSame('1', (string) $xml->is_configurable);
        $this->assertSame((string) $xml->version, $this->module->version);
    }

    public function testTemplateIteratesAssignedProducts()
    {
        $tpl = dirname(dirname(__DIR__)) . '/views/templates/hook/ps_viewedproduct.tpl';
        $this->assertTrue(is_file($tpl));
        $contents = file_get_contents($tpl);
        $this->assertTrue(strlen($contents) > 0);
        $this->assertContains('{foreach from=$products', $contents);
        $this->assertContains('catalog/_partials/miniatures/product.tpl', $contents);
    }
}
