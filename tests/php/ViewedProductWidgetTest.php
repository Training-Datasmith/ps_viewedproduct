<?php

require_once dirname(__FILE__) . '/ViewedProductTestCase.php';

class ViewedProductWidgetTest extends ViewedProductTestCase
{
    public function testAdditionalInfoRecordsIdAndDoesNotRender()
    {
        $result = $this->module->renderWidget(
            'displayProductAdditionalInfo',
            array('product' => array('id_product' => 15))
        );
        $this->assertNull($result);
        $this->assertSame('15', Stubs::$cookieData['viewed']);
        $this->assertCount(0, Stubs::$fetchLog);
        $this->assertCount(0, Stubs::$assignLog);
        $this->assertCount(0, Stubs::$assembleProductsLog);
    }

    public function testCookieKeepsNewestLimitPlusOne()
    {
        Stubs::$config['PRODUCTS_VIEWED_NBR'] = '2';
        foreach (array(10, 20, 30, 40) as $id) {
            $this->module->renderWidget(
                'displayProductAdditionalInfo',
                array('product' => array('id_product' => $id))
            );
        }
        $this->assertSame('20,30,40', Stubs::$cookieData['viewed']);
    }

    public function testRecordingTheSameIdDoesNotDuplicateIt()
    {
        $this->module->renderWidget(
            'displayProductAdditionalInfo',
            array('product' => array('id_product' => 10))
        );
        $this->module->renderWidget(
            'displayProductAdditionalInfo',
            array('product' => array('id_product' => 10))
        );
        $this->assertSame('10', Stubs::$cookieData['viewed']);
    }

    public function testFooterRendersPresenterOutputNewestFirstExcludingCurrent()
    {
        $this->setViewedCookie('20,30,40');
        Stubs::$dbRows = array(
            array('id_product' => '20'),
            array('id_product' => '40'),
            array('id_product' => '30'),
        );
        $result = $this->module->renderWidget(
            'displayFooterProduct',
            array('product' => array('id_product' => 40))
        );
        $presented = array();
        foreach (Stubs::$presentLog as $entry) {
            $presented[] = (int) $entry['product']['id_product'];
        }
        $this->assertSame(array(30, 20), $presented);
        $this->assertSame(Stubs::$presentReturns, $this->assignedProducts());
        $this->assertCount(1, Stubs::$assignLog);
        $this->assertCount(1, Stubs::$fetchLog);
        $this->assertSame(Stubs::$fetchResult, $result);
    }

    public function testDisplayLimitAppliesAfterCurrentProductIsRemoved()
    {
        Stubs::$config['PRODUCTS_VIEWED_NBR'] = '2';
        $this->setViewedCookie('10,20,30,40');
        Stubs::$dbRows = array(
            array('id_product' => '10'),
            array('id_product' => '20'),
            array('id_product' => '30'),
            array('id_product' => '40'),
        );
        $this->module->renderWidget(
            'displayFooterProduct',
            array('product' => array('id_product' => 40))
        );
        $presented = array();
        foreach (Stubs::$presentLog as $entry) {
            $presented[] = (int) $entry['product']['id_product'];
        }
        $this->assertSame(array(30, 20), $presented);
    }

    public function testInactiveIdsAreRemovedBeforeTheLimit()
    {
        Stubs::$config['PRODUCTS_VIEWED_NBR'] = '2';
        $this->setViewedCookie('10,20,30');
        Stubs::$dbRows = array(
            array('id_product' => '30'),
            array('id_product' => '10'),
        );
        $this->module->getWidgetVariables('displayFooterProduct', array());
        $presented = array();
        foreach (Stubs::$presentLog as $entry) {
            $presented[] = (int) $entry['product']['id_product'];
        }
        $this->assertSame(array(30, 10), $presented);
    }

    public function testExistenceQueryUsesIntvalAndShopRestriction()
    {
        Stubs::$shopContextList = array(2, 5);
        $this->setViewedCookie('9,2);DELETE');
        $this->module->getWidgetVariables('displayFooterProduct', array());
        $this->assertSame(array(array(false, 'p')), Stubs::$addSqlRestrictionLog);
        $this->assertGreaterThan(0, count(Stubs::$dbCalls));
        $sql = Stubs::$dbCalls[count(Stubs::$dbCalls) - 1][1];
        $this->assertContains('ps_product_shop', $sql);
        $this->assertContains('IN (2,9)', $sql);
        $this->assertContains(' AND p.id_shop IN (2, 5) ', $sql);
        $this->assertNotContains('DELETE', $sql);
        $getInstanceCalls = array();
        foreach (Stubs::$dbCalls as $call) {
            if ($call[0] === 'getInstance') {
                $getInstanceCalls[] = $call[1];
            }
        }
        $this->assertContains(false, $getInstanceCalls);
    }

    public function testUnsetCookieDoesNotQueryProducts()
    {
        $this->clearViewedCookie();
        $vars = $this->module->getWidgetVariables('displayFooterProduct', array());
        $this->assertFalse($vars);
        $cacheId = $this->module->getCacheId();
        $this->assertSame('cid:ps_viewedproduct|', $cacheId);
        $executed = false;
        foreach (Stubs::$dbCalls as $call) {
            if ($call[0] === 'executeS') {
                $executed = true;
            }
        }
        $this->assertFalse($executed);
    }

    public function testGetCacheIdNamesFilteredIdsNewestFirst()
    {
        $this->setViewedCookie('20,30,40');
        Stubs::$dbRows = array(
            array('id_product' => '20'),
            array('id_product' => '40'),
            array('id_product' => '30'),
        );
        $this->module->renderWidget(
            'displayFooterProduct',
            array('product' => array('id_product' => 40))
        );
        $this->assertSame('cid:ps_viewedproduct|30|20', $this->module->getCacheId());
    }

    public function testCacheHitFetchesWithoutReassembling()
    {
        Stubs::$isCached = true;
        $this->setViewedCookie('10,20');
        Stubs::$dbRows = array(
            array('id_product' => '10'),
            array('id_product' => '20'),
        );
        $result = $this->module->renderWidget('displayFooterProduct', array());
        $this->assertCount(0, Stubs::$assembleProductsLog);
        $this->assertCount(0, Stubs::$assignLog);
        $this->assertCount(1, Stubs::$fetchLog);
        $this->assertContains('FETCH:', $result);
    }

    public function testNoActiveProductsReturnsFalseWithoutFetch()
    {
        $this->setViewedCookie('99');
        Stubs::$dbRows = array();
        $this->assertFalse($this->module->renderWidget('displayFooterProduct', array()));
        $this->assertCount(0, Stubs::$fetchLog);
        $this->assertCount(0, Stubs::$assignLog);
    }

    public function testPresenterReceivesBulkAssembledRowsAndFactorySettings()
    {
        $this->setViewedCookie('11,22');
        Stubs::$dbRows = array(
            array('id_product' => '11'),
            array('id_product' => '22'),
        );
        Stubs::$assembleProductsResult = array(
            array('id_product' => 11, 'name' => 'bulk-11'),
            array('id_product' => 22, 'name' => 'bulk-22'),
        );
        $vars = $this->module->getWidgetVariables('displayFooterProduct', array());
        $this->assertCount(1, Stubs::$assembleProductsLog);
        $this->assertCount(0, Stubs::$assembleProductLog);
        foreach (Stubs::$presentLog as $entry) {
            $this->assertSame(Stubs::$presentationSettings, $entry['settings']);
            $this->assertArrayHasKey('name', $entry['product']);
        }
        $this->assertSame('bulk-11', Stubs::$presentLog[0]['product']['name']);
        $this->assertCount(2, $vars['products']);
        $this->assertSame(Stubs::$presentReturns, $vars['products']);
    }

    public function testProductLifecycleHooksClearTheTemplateCache()
    {
        $this->module->hookActionObjectProductDeleteAfter(array('object' => new stdClass()));
        $this->module->hookActionObjectProductUpdateAfter(array('object' => new stdClass()));
        $this->assertCount(2, Stubs::$clearCacheLog);
        foreach (Stubs::$clearCacheLog as $entry) {
            $this->assertSame(
                'module:ps_viewedproduct/views/templates/hook/ps_viewedproduct.tpl',
                $entry['template']
            );
        }
    }

    public function testFooterWithEmptyCookieDoesNotFetch()
    {
        $this->clearViewedCookie();
        $this->assertNull($this->module->renderWidget('displayFooterProduct', array()));
        $this->assertCount(0, Stubs::$fetchLog);
    }
}
