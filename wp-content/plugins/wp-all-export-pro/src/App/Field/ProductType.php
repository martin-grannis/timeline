<?php

namespace Wpae\App\Field;


class ProductType extends Field
{
    const SECTION = 'productCategories';

    public function getValue($snippetData)
    {
        $productCategoriesData = $this->feed->getSectionFeedData(self::SECTION);

        if($productCategoriesData['productType'] == 'useWooCommerceProductCategories') {

            $productId = false;

            if($this->entry->post_type == 'product') {
                $productId = $this->entry->ID;
            } else if($this->entry->post_type == 'product_variation') {
                $productId = $this->entry->post_parent;
            }
            else {
                return '';
            }

            $categories = wp_get_post_terms($productId, 'product_cat');
            if(is_array($categories)){
                if(isset($categories[0])) {
                    $category = $categories[0];
                    return $category->name;
                } else {
                    return '';
                }

            } else {
                return '';
            }
        } else if($productCategoriesData['productType'] == self::CUSTOM_VALUE_TEXT) {
            return $this->replaceSnippetsInValue($productCategoriesData['productTypeCV'], $snippetData);
        } else {
            throw new \Exception('Unknown product type value '.$productCategoriesData['productType']);
        }

    }

    public function getFieldName()
    {
        return 'product_type';
    }


}