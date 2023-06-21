<?php
declare(strict_types=1);

namespace OH\Labels\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigProvider
{
    /**
     * @var string
     */
    const XML_CONFIG_PATH_LABEL = 'oh_labels/settings/%s/%s';

    /**
     * @var string
     */
    const XML_CONFIG_PATH_MAX_ALLOWED = 'oh_labels/settings/max_allowed';

    public function __construct(
        private ScopeConfigInterface $scopeInterface
    ) {
    }

    /**
     * Retrieve label text
     *
     * @param $type
     * @param $config
     * @param $scopeId
     * @return mixed|string
     */
    public function getValue($type, $config, $scopeId = null)
    {
        if ($type && $config) {
            $path = sprintf(self::XML_CONFIG_PATH_LABEL, $type, $config);

            if ($config == 'enabled') {
                return $this->scopeInterface->isSetFlag($path, ScopeInterface::SCOPE_STORES, $scopeId);
            }

            return $this->scopeInterface->getValue($path, ScopeInterface::SCOPE_STORES, $scopeId);
        }

        return '';
    }
}
