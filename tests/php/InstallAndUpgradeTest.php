<?php

require_once dirname(__FILE__) . '/ViewedProductTestCase.php';

class InstallAndUpgradeTest extends ViewedProductTestCase
{
    public function testInstallRegistersDefaultAndHooks()
    {
        $result = $this->module->install();
        $this->assertTrue($result);
        $this->assertCount(1, Stubs::$updateValueLog);
        $this->assertSame('PRODUCTS_VIEWED_NBR', Stubs::$updateValueLog[0]['key']);
        $this->assertSame(8, Stubs::$updateValueLog[0]['value']);
        $hooks = array();
        foreach (Stubs::$registerHookLog as $entry) {
            $hooks[] = $entry['hook'];
        }
        $this->assertSame(
            array(
                'displayFooterProduct',
                'displayProductAdditionalInfo',
                'actionObjectProductDeleteAfter',
                'actionObjectProductUpdateAfter',
            ),
            $hooks
        );
    }

    public function testInstallReturnsFalseWhenParentInstallFails()
    {
        Stubs::$parentInstallResult = false;
        $this->assertFalse($this->module->install());
        $this->assertCount(0, Stubs::$updateValueLog);
        $this->assertCount(0, Stubs::$registerHookLog);
    }

    public function testInstallReturnsFalseWhenConfigurationUpdateFails()
    {
        Stubs::$updateValueResult = false;
        $this->assertFalse($this->module->install());
        $this->assertCount(0, Stubs::$registerHookLog);
    }

    public function testInstallReturnsFalseWhenHookRegistrationFails()
    {
        Stubs::$registerHookReturns = array(true, true, false);
        $this->assertFalse($this->module->install());
        $this->assertCount(3, Stubs::$registerHookLog);
    }

    public function testUpgrade110ReturnsTrueWhenHookRegisters()
    {
        require_once dirname(dirname(__DIR__)) . '/upgrade/upgrade-1.1.0.php';
        $module = $this->getMockBuilder('Module')
            ->setMethods(array('registerHook'))
            ->getMock();
        $module->expects($this->once())
            ->method('registerHook')
            ->with('displayProductAdditionalInfo')
            ->will($this->returnValue(true));
        $this->assertTrue(upgrade_module_1_1_0($module));
    }

    public function testUpgrade110ReturnsFalseWhenHookRegistrationFails()
    {
        require_once dirname(dirname(__DIR__)) . '/upgrade/upgrade-1.1.0.php';
        $module = $this->getMockBuilder('Module')
            ->setMethods(array('registerHook'))
            ->getMock();
        $module->expects($this->once())
            ->method('registerHook')
            ->with('displayProductAdditionalInfo')
            ->will($this->returnValue(false));
        $this->assertFalse(upgrade_module_1_1_0($module));
    }

    public function testUpgrade122CallsUnregisterOnDisplayProductButtons()
    {
        require_once dirname(dirname(__DIR__)) . '/upgrade/upgrade-1.2.2.php';
        $module = $this->getMockBuilder('Module')
            ->setMethods(array('unregisterHook'))
            ->getMock();
        $module->expects($this->once())
            ->method('unregisterHook')
            ->with('displayProductButtons');
        upgrade_module_1_2_2($module);
    }
}
