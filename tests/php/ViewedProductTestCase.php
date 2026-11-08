<?php

require_once dirname(__FILE__) . '/stubs.php';

abstract class ViewedProductTestCase extends PHPUnit_Framework_TestCase
{
    /** @var Ps_Viewedproduct */
    protected $module;

    protected function setUp()
    {
        Stubs::reset();
        $this->module = new Ps_Viewedproduct();
    }

    protected function invokeProtected($object, $method, array $args = array())
    {
        $reflection = new ReflectionClass($object);
        $callable = $reflection->getMethod($method);
        $callable->setAccessible(true);

        return $callable->invokeArgs($object, $args);
    }

    protected function setViewedCookie($value)
    {
        Stubs::$cookieData['viewed'] = $value;
        $this->module->context->cookie->viewed = $value;
    }

    protected function clearViewedCookie()
    {
        unset(Stubs::$cookieData['viewed']);
        $this->module->context->cookie = new Cookie();
    }

    protected function assignedProducts()
    {
        foreach (Stubs::$assignLog as $entry) {
            if ($entry[0] === 'products') {
                return $entry[1];
            }
        }

        return null;
    }

    protected function assertLoggedTranslationDomain($messageId, $domain)
    {
        foreach (Stubs::$translationLog as $entry) {
            if ($entry['id'] === $messageId && $entry['domain'] === $domain) {
                return;
            }
        }

        $this->fail(
            'Expected translation ' . $messageId . ' in domain ' . $domain
        );
    }
}
