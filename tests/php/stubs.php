<?php
/**
 * Minimal PrestaShop doubles for ps_viewedproduct unit tests (PHP 5.6 syntax).
 */

require dirname(__FILE__) . '/stubs-prestashop.php';

if (!defined('_PS_USE_SQL_SLAVE_')) {
    define('_PS_USE_SQL_SLAVE_', false);
}

class Stubs
{
    public static $config = array();
    public static $registerHookReturns = array();
    public static $parentInstallResult = true;
    public static $updateValueResult = true;
    public static $dbRows = array();
    public static $dbCalls = array();
    public static $shopContextList = array(1);
    public static $shopRestriction = ' AND p.id_shop IN (1) ';
    public static $post = array();
    public static $get = array();
    public static $cookieData = array();
    public static $translations = array();
    public static $translationLog = array();
    public static $registerHookLog = array();
    public static $updateValueLog = array();
    public static $clearCacheLog = array();
    public static $isCached = false;
    public static $fetchResult = 'FETCH_SENTINEL';
    public static $assignLog = array();
    public static $fetchLog = array();
    public static $assembleProductsResult = array();
    public static $assembleProductsLog = array();
    public static $assembleProductLog = array();
    public static $presentLog = array();
    public static $presentationSettings = null;
    public static $linkAdminLink = 'ADMIN_LINK';
    public static $linkAdminLinkCalls = array();

    public static function reset()
    {
        self::$config = array(
            'PRODUCTS_VIEWED_NBR' => '8',
            'PS_LANG_DEFAULT' => '1',
        );
        self::$registerHookReturns = array();
        self::$parentInstallResult = true;
        self::$updateValueResult = true;
        self::$dbRows = array();
        self::$dbCalls = array();
        self::$shopContextList = array(1);
        self::$shopRestriction = ' AND p.id_shop IN (1) ';
        self::$post = array();
        self::$get = array();
        self::$cookieData = array();
        self::$translations = array(
            'Viewed products block' => 'Viewed products block',
            'Display a kind of showcase on your product pages with recently viewed products.' => 'Display a kind of showcase on your product pages with recently viewed products.',
            'You must fill in the \'Products displayed\' field.' => 'You must fill in the \'Products displayed\' field.',
            'Invalid number.' => 'Invalid number.',
            'The settings have been updated.' => 'The settings have been updated.',
            'Settings' => 'Settings',
            'Products to display' => 'Products to display',
            'Define the number of products displayed in this block.' => 'Define the number of products displayed in this block.',
            'Save' => 'Save',
        );
        self::$translationLog = array();
        self::$registerHookLog = array();
        self::$updateValueLog = array();
        self::$clearCacheLog = array();
        self::$isCached = false;
        self::$fetchResult = 'FETCH_SENTINEL';
        self::$assignLog = array();
        self::$fetchLog = array();
        self::$assembleProductsResult = array();
        self::$assembleProductsLog = array();
        self::$assembleProductLog = array();
        self::$presentLog = array();
        self::$presentationSettings = new stdClass();
        self::$linkAdminLink = 'ADMIN_LINK';
        self::$linkAdminLinkCalls = array();
        HelperForm::$lastInstance = null;
        Shop::resetStub();
        Db::resetStub();
        Context::resetStub();
    }
}

class Cookie
{
    private $data = array();

    public function __construct()
    {
        $this->data = Stubs::$cookieData;
    }

    public function __get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : false;
    }

    public function __isset($key)
    {
        return isset($this->data[$key]);
    }

    public function __set($key, $value)
    {
        $this->data[$key] = $value;
        Stubs::$cookieData[$key] = $value;
    }
}

class Language
{
    public $id;

    public function __construct($id)
    {
        $this->id = (int) $id;
    }
}

class Link
{
    public function getAdminLink($controller, $withToken = true)
    {
        Stubs::$linkAdminLinkCalls[] = array($controller, $withToken);

        return Stubs::$linkAdminLink;
    }
}

class ControllerStub
{
    public function getLanguages()
    {
        return array('en' => array('id_lang' => 7));
    }
}

class SmartyStub
{
    public $assigned = array();

    public function assign($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->assigned[$k] = $v;
                Stubs::$assignLog[] = array($k, $v);
            }

            return;
        }
        $this->assigned[$key] = $value;
        Stubs::$assignLog[] = array($key, $value);
    }

    public function createData($parent = null)
    {
        return new SmartyStub();
    }

    public function createTemplate($template, $cacheId = null, $compileId = null, $parent = null)
    {
        return new SmartyTemplateStub();
    }
}

class SmartyTemplateStub
{
    public function fetch()
    {
        return Stubs::$fetchResult;
    }
}

class Context
{
    public static $instance;

    public $cookie;
    public $link;
    public $language;
    public $controller;
    public $smarty;
    public $shop;

    public function __construct()
    {
        $this->cookie = new Cookie();
        $this->link = new Link();
        $this->language = new Language(7);
        $this->controller = new ControllerStub();
        $this->smarty = new SmartyStub();
        $this->shop = new stdClass();
        $this->shop->id = 1;
    }

    public static function getContext()
    {
        if (!self::$instance) {
            self::$instance = new Context();
        }

        return self::$instance;
    }

    public static function resetStub()
    {
        self::$instance = new Context();
    }

    public function getTranslator()
    {
        return new TranslatorStub();
    }
}

class TranslatorStub
{
    public function trans($id, array $parameters = array(), $domain = null, $locale = null)
    {
        Stubs::$translationLog[] = array(
            'id' => $id,
            'domain' => $domain,
            'parameters' => $parameters,
        );

        if (isset(Stubs::$translations[$id])) {
            return Stubs::$translations[$id];
        }

        return $id;
    }
}

class Tools
{
    public static function getValue($key, $defaultValue = false)
    {
        if (isset(Stubs::$post[$key])) {
            return Stubs::$post[$key];
        }
        if (isset(Stubs::$get[$key])) {
            return Stubs::$get[$key];
        }

        return $defaultValue;
    }

    public static function isSubmit($submit)
    {
        return isset(Stubs::$post[$submit])
            || isset(Stubs::$post[$submit . '_x'])
            || isset(Stubs::$post[$submit . '_y'])
            || isset(Stubs::$get[$submit])
            || isset(Stubs::$get[$submit . '_x'])
            || isset(Stubs::$get[$submit . '_y']);
    }

    public static function getAdminTokenLite($tab, $context = null)
    {
        return 'TOKEN_LITE';
    }
}

class Configuration
{
    public static function get($key, $idLang = null, $idShopGroup = null, $idShop = null, $default = false)
    {
        if (array_key_exists($key, Stubs::$config)) {
            return Stubs::$config[$key];
        }

        return $default;
    }

    public static function updateValue($key, $values, $html = false, $idShopGroup = null, $idShop = null)
    {
        Stubs::$updateValueLog[] = array('key' => $key, 'value' => $values);

        if (!Stubs::$updateValueResult) {
            return false;
        }

        if (!is_array($values)) {
            Stubs::$config[$key] = $values;
        }

        return true;
    }
}

class Db
{
    public static $instance;

    public static function getInstance($master = true)
    {
        Stubs::$dbCalls[] = array('getInstance', $master);
        if (!self::$instance) {
            self::$instance = new Db();
        }

        return self::$instance;
    }

    public static function resetStub()
    {
        self::$instance = new Db();
    }

    public function executeS($sql, $array = true, $useCache = true)
    {
        Stubs::$dbCalls[] = array('executeS', $sql);

        return Stubs::$dbRows;
    }
}

class Shop
{
    public static $restriction;

    public static function addSqlRestriction($share = false, $alias = null)
    {
        return Stubs::$shopRestriction;
    }

    public static function getContextListShopID($share = false)
    {
        return Stubs::$shopContextList;
    }

    public static function resetStub()
    {
        Stubs::$shopRestriction = ' AND p.id_shop IN (1) ';
    }
}

class HelperForm
{
    public static $lastInstance;

    public $show_toolbar;
    public $table;
    public $default_form_language;
    public $allow_employee_form_lang;
    public $identifier;
    public $submit_action;
    public $currentIndex;
    public $token;
    public $tpl_vars = array();
    public $generatedForms = array();

    public function __construct()
    {
        self::$lastInstance = $this;
    }

    public function generateForm($forms)
    {
        $this->generatedForms = $forms;

        return 'FORM_SENTINEL';
    }
}

class ProductAssembler
{
    public $context;

    public function __construct($context)
    {
        $this->context = $context;
    }

    public function assembleProducts(array $rawProducts)
    {
        Stubs::$assembleProductsLog[] = $rawProducts;

        if (count(Stubs::$assembleProductsResult) > 0) {
            return Stubs::$assembleProductsResult;
        }

        $out = array();
        foreach ($rawProducts as $row) {
            $out[] = array('id_product' => $row['id_product'], 'name' => 'assembled-' . $row['id_product']);
        }

        return $out;
    }

    public function assembleProduct(array $rawProduct)
    {
        Stubs::$assembleProductLog[] = $rawProduct;

        return array('id_product' => $rawProduct['id_product'], 'name' => 'assembled-' . $rawProduct['id_product']);
    }
}

class ProductPresenterFactory
{
    public $context;

    public function __construct($context)
    {
        $this->context = $context;
    }

    public function getPresentationSettings()
    {
        return Stubs::$presentationSettings;
    }
}

class Module
{
    public $name;
    public $author;
    public $version;
    public $tab;
    public $need_instance;
    public $bootstrap;
    public $displayName;
    public $description;
    public $ps_versions_compliancy = array();
    public $table = 'module';
    public $identifier = 'id_module';
    public $id = 42;
    public $context;
    public $smarty;

    public function __construct()
    {
        $this->context = Context::getContext();
        $this->smarty = $this->context->smarty->createData($this->context->smarty);
    }

    public function install()
    {
        return Stubs::$parentInstallResult;
    }

    public function registerHook($hookName, $shopList = null)
    {
        Stubs::$registerHookLog[] = array('hook' => $hookName, 'shop_list' => $shopList);
        if (count(Stubs::$registerHookReturns) > 0) {
            $next = array_shift(Stubs::$registerHookReturns);

            return $next;
        }

        return true;
    }

    public function unregisterHook($hookId, $shopList = null)
    {
        Stubs::$registerHookLog[] = array('unregister' => $hookId, 'shop_list' => $shopList);

        return true;
    }

    protected function getCacheId($name = null)
    {
        return 'cid:' . $name;
    }

    protected function trans($id, array $parameters = array(), $domain = null, $locale = null)
    {
        return htmlspecialchars($this->getTranslator()->trans($id, $parameters, $domain, $locale), ENT_NOQUOTES);
    }

    public function getTranslator()
    {
        return $this->context->getTranslator();
    }

    public function displayError($error)
    {
        return '<div class="module_error">' . $error . '</div>';
    }

    public function displayConfirmation($string)
    {
        return '<div class="module_confirmation">' . $string . '</div>';
    }

    protected function _clearCache($template, $cacheId = null, $compileId = null)
    {
        Stubs::$clearCacheLog[] = array(
            'template' => $template,
            'cache_id' => $cacheId,
        );

        return 1;
    }

    public function isCached($template, $cacheId = null, $compileId = null)
    {
        return Stubs::$isCached;
    }

    public function fetch($templatePath, $cacheId = null, $compileId = null)
    {
        Stubs::$fetchLog[] = array('template' => $templatePath, 'cache_id' => $cacheId);
        Stubs::$fetchResult = 'FETCH:' . $templatePath . '|' . $cacheId;

        return Stubs::$fetchResult;
    }
}
