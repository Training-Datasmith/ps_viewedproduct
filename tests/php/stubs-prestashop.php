<?php

namespace PrestaShop\PrestaShop\Core\Module;

interface WidgetInterface
{
    public function renderWidget($hookName, array $configuration);

    public function getWidgetVariables($hookName, array $configuration);
}

namespace PrestaShop\PrestaShop\Adapter\Image;

class ImageRetriever
{
    public $link;

    public function __construct($link)
    {
        $this->link = $link;
    }
}

namespace PrestaShop\PrestaShop\Adapter\Product;

class PriceFormatter
{
}

class ProductColorsRetriever
{
}

namespace PrestaShop\PrestaShop\Adapter\Presenter\Product;

class ProductListingPresenter
{
    public function present($settings, array $product, $language)
    {
        \Stubs::$presentLog[] = array(
            'settings' => $settings,
            'product' => $product,
            'language' => $language,
        );

        $presented = array(
            'id_product' => $product['id_product'],
            'presented_id' => (int) $product['id_product'],
            'name' => isset($product['name']) ? $product['name'] : 'product-' . $product['id_product'],
        );
        \Stubs::$presentReturns[] = $presented;

        return $presented;
    }
}
