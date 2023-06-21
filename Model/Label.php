<?php

declare(strict_types=1);

namespace OH\Labels\Model;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\DataObject;

class Label
{
    public function __construct(
        private ConfigProvider $configProvider,
        private CollectionFactory $productCollFact,
    ) {
    }

    /**
     * Retrieve labels by product id
     *
     * @param $productId
     * @return array|array[]
     */
    public function getLabels($productId)
    {
        if (!$this->configProvider->getValue('global', 'enabled')) {
            return [];
        }

        $product = $this->getProduct($productId);

        if (!$product->getEntityId()) {
            return [];
        }

        $labels = [];
        if ($discountLabel = $this->getDiscountLabel($product)) {
            $labels[] = $discountLabel;
        }

        if ($newLabel = $this->getNewLabel($product)) {
            $labels[] = $newLabel;
        }

        if ($prodLabel = $this->getProdLabel($product)) {
            $labels[] = $prodLabel;
        }

        return $labels ? $this->sortAndFilter($labels) : [];
    }

    private function sortAndFilter($labels)
    {
        $maxAllowed = (int)$this->configProvider->getValue('global', 'max_allowed');

        usort($labels, function ($item1, $item2) {
            return $item1['sort_order'] <=> $item2['sort_order'];
        });

        return array_slice($labels, 0, $maxAllowed ?: 1);
    }

    private function getProduct($productId)
    {
        if ($productId) {
            return $this->productCollFact
                ->create()
                ->addAttributeToSelect('name')
                ->addAttributeToSelect('special_price')
                ->addAttributeToSelect('special_from_date')
                ->addAttributeToSelect('special_to_date')
                ->addAttributeToSelect('news_from_date')
                ->addAttributeToSelect('news_to_date')
                ->addAttributeToSelect('oh_label')
                ->addIdFilter($productId)
                ->addPriceData()
                ->getFirstItem();
        }

        return null;
    }

    private function getProdLabel($product)
    {
        if (!$product->getOhLabel() ||
            !$this->configProvider->getValue('product', 'enabled')) {
            return [];
        }

        $data = [
            'type' => 'product',
            'text' => $product->getOhLabel(),
            'sort_order' => $this->configProvider->getValue('product', 'sort_order'),
            'styles' => [
                'color' => $this->configProvider->getValue('product', 'text_color'),
                'background-color' => $this->configProvider->getValue('product', 'background_color'),
            ]
        ];

        $label = new DataObject();
        return $label->setData($data);
    }

    private function getNewLabel($product)
    {
        if (!$product->getNewsFromDate() ||
            !$this->isDateValid($product->getNewsToDate()) ||
            !$this->configProvider->getValue('news', 'enabled')) {
            return [];
        }

        $text = $this->configProvider->getValue('news', 'text') ?: __('New')->getText();

        $data = [
            'type' => 'new',
            'text' => $text,
            'sort_order' => $this->configProvider->getValue('news', 'sort_order'),
            'styles' => [
                'color' => $this->configProvider->getValue('news', 'text_color'),
                'background-color' => $this->configProvider->getValue('news', 'background_color'),
            ]
        ];

        $label = new DataObject();
        return $label->setData($data);
    }

    private function getDiscountLabel($product)
    {
        if (!$product->getSpecialPrice() ||
            !$this->isDateValid($product->getSpecialToDate()) ||
            !$this->configProvider->getValue('sale', 'enabled')) {
            return [];
        }

        $discountPercentage = $this->getDiscountPercentage(
            $product->getData('price'),
            $product->getData('special_price')
        );

        //Same price and special price no apply
        if ($discountPercentage <= 0) {
            return [];
        }

        $text = $this->configProvider->getValue('sale', 'text') ?
            sprintf($this->configProvider->getValue('sale', 'text'), $discountPercentage) :
            __('Sale')->getText();

        $data = [
            'type' => 'sale',
            'text' => $text,
            'sort_order' => $this->configProvider->getValue('sale', 'sort_order'),
            'styles' => [
                'color' => $this->configProvider->getValue('sale', 'text_color'),
                'background-color' => $this->configProvider->getValue('sale', 'background_color'),
            ]
        ];

        $label = new DataObject();
        return $label->setData($data);
    }

    private function getDiscountPercentage($origPrice, $newPrice)
    {
        $discount = $origPrice - $newPrice;
        $percentage = ($discount / $origPrice) * 100;
        return number_format($percentage, 0, null, '');
    }

    private function isDateValid($toDate)
    {
        //null to date never expire
        if (!$toDate) {
            return true;
        }

        $now = new \DateTime();
        //hours and mins not apply
        $now->settime(0, 0);

        $toDate = new \DateTime($toDate);
        //hours and mins not apply
        $now->settime(0, 0);
        return $now <= $toDate;
    }
}
