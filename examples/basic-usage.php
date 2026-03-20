<?php

declare(strict_types=1);

/**
 * Example: Working with the ps_viewedproduct PrestaShop module.
 *
 * ps_viewedproduct displays a "Recently Viewed Products" block on the storefront.
 * It tracks which product detail pages the visitor has viewed in the current
 * session and renders them in a sidebar or footer widget.
 *
 * This file documents common usage patterns.
 */

// --- Widget invocation in Smarty/Twig template ---
// {widget name="ps_viewedproduct" hook="displayLeftColumn"}

// --- How tracking works ---
// The module uses PHP session storage to track viewed product IDs per visitor.
// When a customer visits a product page, the ID is prepended to the session list.
// Session key: 'viewed_product_ids' (array of int)

// --- Reading viewed products from session ---
// $viewedIds = Context::getContext()->cookie->viewed ?? '';
// $idList    = array_filter(explode(',', $viewedIds));
//
// if (!empty($idList)) {
//     $products = Product::getProductsProperties(
//         id_lang: (int) Context::getContext()->language->id,
//         id_list: $idList,
//     );
//
//     foreach ($products as $product) {
//         echo $product['name'] . "\n";
//     }
// }

// --- Back Office configuration ---
// Modules > Viewed Products:
//   - Number of products to display (default: 8)
//   - Hook placement (left column, right column, footer)

// --- Template override ---
// themes/{theme}/modules/ps_viewedproduct/views/templates/hook/ps_viewedproduct.tpl
