<?php

declare(strict_types=1);

namespace OH\Labels\Block\Product\Label;

use Magento\Framework\View\Element\Template;
use OH\Labels\Block\Product\LabelFactory;
use OH\Labels\Model\Label;

class Wrapper extends Template
{
    protected $_template = 'label/wrapper.phtml';

    public function __construct(
        private Label $label,
        private LabelFactory $labelBlockFactory,
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getLabelsHtml()
    {
        if ($labels = $this->getData('labels_html')) {
            return $labels;
        }

        $productId = $this->getProductId();

        if (!$productId) {
            return '';
        }

        $labelsHtml = '';

        foreach ($this->label->getLabels($productId) as $label) {
            $labelBlock = $this->labelBlockFactory
                ->create()
                ->setData($label->getData());

            $labelsHtml .= $labelBlock->toHtml();
        }

        return $labelsHtml ? $this->setData('labels_html', $labelsHtml)->getData('labels_html') : '';
    }

    private function getProductId()
    {
        return $this->_request->getParam('id');
    }
}