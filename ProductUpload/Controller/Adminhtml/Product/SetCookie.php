<?php
namespace Brainvire\ProductUpload\Controller\Adminhtml\Product;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Brainvire\ProductUpload\Service\CloudflareService;

class SetCookie extends Action
{
    protected $cookieManager;
    protected $cookieMetadataFactory;
    protected $sessionManager;
    protected $cloudflareService;

    public function __construct(
        Context $context,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManager,
        CloudflareService $cloudflareService
    ) {
        parent::__construct($context);
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionManager = $sessionManager;
        $this->cloudflareService = $cloudflareService;
    }

    public function execute()
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $responseData = ['success' => false];

        try {
            // Check if Cloudflare is enabled
            if (!$this->cloudflareService->isEnabled()) {
                throw new \Exception('Cloudflare integration is not enabled');
            }

            // Check Cloudflare security level
            $securityLevel = $this->cloudflareService->getSecurityLevel();
            $responseData['security_level'] = $securityLevel;
            
            if ($securityLevel === 'high') {
                $this->cloudflareService->updateSecurityLevel('medium');
                $responseData['security_level_updated'] = true;
            }

            // Check if cf_clearance cookie exists
            $cfClearance = $this->cookieManager->getCookie('cf_clearance');
            $responseData['existing_cookie'] = !empty($cfClearance);
            
            // If cookie doesn't exist, generate and set new value
            if (!$cfClearance) {
                // Generate a new Cloudflare clearance value
                $timestamp = time();
                $randomString = bin2hex(random_bytes(16));
                $hash = hash('sha256', $timestamp . $randomString);
                $newCfClearance = $timestamp . '.' . $randomString . '.' . $hash;
                
                $metadata = $this->cookieMetadataFactory
                    ->createPublicCookieMetadata()
                    ->setDuration(3600)
                    ->setPath('/')
                    ->setDomain($this->sessionManager->getCookieDomain())
                    ->setHttpOnly(true)
                    ->setSecure($this->sessionManager->getCookieSecure());
                
                $this->cookieManager->setPublicCookie('cf_clearance', $newCfClearance, $metadata);
                $responseData['cookie_set'] = true;
                $responseData['cookie_value'] = $newCfClearance;
                $responseData['success'] = true;
                $responseData['message'] = 'Cloudflare cookie set successfully';
            } else {
                $responseData['success'] = true;
                $responseData['message'] = 'Cloudflare cookie already exists';
            }
        } catch (\Exception $e) {
            $responseData['message'] = $e->getMessage();
            $responseData['error'] = $e->getTraceAsString();
        }

        return $resultJson->setData($responseData);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Catalog::products');
    }
} 