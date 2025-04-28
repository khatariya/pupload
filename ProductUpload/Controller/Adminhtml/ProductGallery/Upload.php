<?php
namespace Brainvire\ProductUpload\Controller\Adminhtml\ProductGallery;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Catalog\Model\Product\Gallery\Processor;
use Magento\MediaStorage\Model\File\Uploader;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Framework\Data\Form\FormKey\Validator;
use Brainvire\ProductUpload\Service\CloudflareService;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Session\SessionManagerInterface;

class Upload extends Action
{
    protected $processor;
    protected $filesystem;
    protected $mediaConfig;
    protected $formKeyValidator;
    protected $cloudflareService;
    protected $cookieManager;
    protected $cookieMetadataFactory;
    protected $sessionManager;

    public function __construct(
        Context $context,
        Processor $processor,
        Filesystem $filesystem,
        Config $mediaConfig,
        Validator $formKeyValidator,
        CloudflareService $cloudflareService,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManager
    ) {
        parent::__construct($context);
        $this->processor = $processor;
        $this->filesystem = $filesystem;
        $this->mediaConfig = $mediaConfig;
        $this->formKeyValidator = $formKeyValidator;
        $this->cloudflareService = $cloudflareService;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionManager = $sessionManager;
    }

    public function execute()
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $responseData = [];
        
        try {
            // Add security headers
            $this->getResponse()->setHeader('Access-Control-Allow-Origin', '*');
            $this->getResponse()->setHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS');
            $this->getResponse()->setHeader('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, X-CSRF-Token');
            $this->getResponse()->setHeader('Access-Control-Allow-Credentials', 'true');
            
            // Handle preflight requests
            if ($this->getRequest()->getMethod() === 'OPTIONS') {
                return $resultJson;
            }

            // Check Cloudflare security level
            $securityLevel = $this->cloudflareService->getSecurityLevel();
            if ($securityLevel === 'high') {
                // If security level is high, you might want to adjust it
                $this->cloudflareService->updateSecurityLevel('medium');
            }

            // Check if cf_clearance cookie exists
            $cfClearance = $this->cookieManager->getCookie('cf_clearance');
            
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
            }

            // Verify form key
            if (!$this->formKeyValidator->validate($this->getRequest())) {
                $responseData = [
                    'error' => 'Invalid form key. Please refresh the page and try again.',
                    'ajaxExpired' => 1,
                    'ajaxRedirect' => $this->_url->getUrl('admin')
                ];
                return $resultJson->setData($responseData);
            }

            $uploader = $this->_objectManager->create(
                \Magento\MediaStorage\Model\File\Uploader::class,
                ['fileId' => 'image']
            );
            
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(true);
            
            $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
            $uploadResult = $uploader->save(
                $mediaDirectory->getAbsolutePath($this->mediaConfig->getBaseTmpMediaPath())
            );

            if (is_array($uploadResult)) {
                unset($uploadResult['tmp_name']);
                unset($uploadResult['path']);
                $uploadResult['url'] = $this->mediaConfig->getTmpMediaUrl($uploadResult['file']);
                $uploadResult['file'] = $uploadResult['file'] . '.tmp';
                $responseData = $uploadResult;
            } else {
                $responseData = ['error' => 'Something went wrong while saving the file(s).'];
            }
        } catch (\Exception $e) {
            $responseData = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }

        return $resultJson->setData($responseData);
    }
}