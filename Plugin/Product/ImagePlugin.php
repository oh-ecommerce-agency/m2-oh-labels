<?php

declare(strict_types=1);

namespace OH\Labels\Plugin\Product;

use Magento\Catalog\Block\Product\Image;

class ImagePlugin
{
    public function __construct(
        private \OH\Labels\Block\Product\Label\WrapperFactory $labelWrapperFactory,
        private \OH\Labels\Block\Product\LabelFactory $labelBlockFactory,
        private \OH\Labels\Model\Label $label
    ) {
    }

    public function afterToHtml(Image $subject, $html)
    {
        $productId = $subject->getProductId();
        $labels = $this->label->getLabels($productId);

        if (!$labels) {
            return $html;
        }

        $labelsHtml = '';

        foreach ($labels as $label) {
            $labelBlock = $this->labelBlockFactory
                ->create()
                ->setData($label->getData());

            $labelsHtml .= $labelBlock->toHtml();
        }

        if ($labelsHtml) {
            $labelBlock = $this->labelWrapperFactory
                ->create()
                ->setLabelsHtml($labelsHtml);

            $html .= $labelBlock->toHtml();
        }

        return $html;
    }
}