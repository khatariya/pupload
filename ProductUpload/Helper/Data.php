<?php
namespace Brainvire\ProductUpload\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_CLOUDFLARE_ENABLED = 'cloudflare/general/enabled';
    const XML_PATH_PRODUCT_EDIT_ENABLED = 'cloudflare/general/enable_product_edit';

    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CLOUDFLARE_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function isProductEditCookieEnabled()
    {
        return $this->isEnabled() && $this->scopeConfig->isSetFlag(
            self::XML_PATH_PRODUCT_EDIT_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }
} 