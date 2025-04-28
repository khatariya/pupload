<?php
namespace Brainvire\ProductUpload\Service;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class CloudflareService
{
    protected $scopeConfig;
    protected $apiKey;
    protected $email;
    protected $zoneId;
    protected $logger;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->apiKey = $this->scopeConfig->getValue('cloudflare/general/api_key');
        $this->email = $this->scopeConfig->getValue('cloudflare/general/email');
        $this->zoneId = $this->scopeConfig->getValue('cloudflare/general/zone_id');
    }

    public function getSecurityLevel()
    {
        try {
            if (!$this->isConfigured()) {
                throw new LocalizedException(__('Cloudflare is not properly configured.'));
            }

            $client = new \Cloudflare\API\Adapter\Guzzle(new \Cloudflare\API\Auth\APIKey($this->email, $this->apiKey));
            $zones = new \Cloudflare\API\Endpoints\Zones($client);
            
            return $zones->getSecurityLevel($this->zoneId);
        } catch (\Exception $e) {
            $this->logger->error('Cloudflare API Error: ' . $e->getMessage());
            return false;
        }
    }

    public function updateSecurityLevel($level)
    {
        try {
            if (!$this->isConfigured()) {
                throw new LocalizedException(__('Cloudflare is not properly configured.'));
            }

            $client = new \Cloudflare\API\Adapter\Guzzle(new \Cloudflare\API\Auth\APIKey($this->email, $this->apiKey));
            $zones = new \Cloudflare\API\Endpoints\Zones($client);
            
            return $zones->updateSecurityLevel($this->zoneId, $level);
        } catch (\Exception $e) {
            $this->logger->error('Cloudflare API Error: ' . $e->getMessage());
            return false;
        }
    }

    public function getFirewallRules()
    {
        try {
            if (!$this->isConfigured()) {
                throw new LocalizedException(__('Cloudflare is not properly configured.'));
            }

            $client = new \Cloudflare\API\Adapter\Guzzle(new \Cloudflare\API\Auth\APIKey($this->email, $this->apiKey));
            $zones = new \Cloudflare\API\Endpoints\Zones($client);
            
            return $zones->listFirewallRules($this->zoneId);
        } catch (\Exception $e) {
            $this->logger->error('Cloudflare API Error: ' . $e->getMessage());
            return false;
        }
    }

    protected function isConfigured()
    {
        return !empty($this->apiKey) && !empty($this->email) && !empty($this->zoneId);
    }
} 