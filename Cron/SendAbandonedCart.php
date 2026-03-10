<?php
namespace Azguards\WhatsAppConnect\Cron;

use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Azguards\WhatsAppConnect\Logger\Logger;

class SendAbandonedCart
{
    public const COLLECTION_LIMIT = 100;
    public const XML_PATH_SEARCHABLE_DROPDOWN_ABANDON_CART =
    "whatsApp_conector/abandon_cart/searchable_dropdown_abandon_cart";
    public const XML_PATH_ABANDON_CART_VERIABLE = "whatsApp_conector/abandon_cart/abandoned_cart_variable";

    /**
     * @var ApiHelper
     */
    protected $apiHelper;
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var QuoteCollectionFactory
     */
    protected $quoteCollectionFactory;

    /**
     * SendAbandonedCart construct
     *
     * @param ApiHelper $apiHelper
     * @param QuoteCollectionFactory $quoteCollectionFactory
     * @param Logger $logger
     */
    public function __construct(
        ApiHelper $apiHelper,
        QuoteCollectionFactory $quoteCollectionFactory,
        Logger $logger
    ) {
        $this->apiHelper = $apiHelper;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * Execute
     *
     * @return void
     */
    public function execute()
    {
        try {
            $abandonCartTempaletId = $this->apiHelper->getConfigValue(self::XML_PATH_SEARCHABLE_DROPDOWN_ABANDON_CART);
            $abandonCartTempaletVerible = $this->apiHelper->getConfigValue(self::XML_PATH_ABANDON_CART_VERIABLE);
            $quotes = $this->getAbandonedCarts();
            $quotesData = $this->getQuoteCollections();
            $customerData = $this->getCustomerQuotesDetails($quotes, $orderCreateTempaletId);
            // $response = $this->apiHelper
            // ->sendMessage($abandonCartTempaletId, $abandonCartTempaletVerible, 'SendAbandonedCart');
        } catch (\Exception $e) {
            $this->logger->info("WhatsApp API Error: " . $e->getMessage());
        }
    }

    /**
     * Get Abandoned Carts
     *
     * @return void
     */
    protected function getAbandonedCarts()
    {
        $minutes = 1; // Consider carts abandoned after 2 hours

        $collection = $this->quoteCollectionFactory->create()
        ->addFieldToFilter('is_active', 1)
        ->addFieldToFilter('items_count', ['gt' => 0])
        ->addFieldToFilter('updated_at', ['lt' => date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"))]);
        return $collection;
    }

    /**
     * Get Customer Quotes Details
     *
     * @param [type] $quotes
     * @param [type] $abandonCartTemplateId
     * @return void
     */
    public function getCustomerQuotesDetails($quotes, $abandonCartTemplateId)
    {
        foreach ($quotes as $quote) {
            $firstName = $quote->getCustomerFirstname();
            $lastName = $quote->getCustomerLastname();
            $email = $quote->getCustomerEmail();
            $mobileNumber = $quote->getBillingAddress()->getTelephone() ?? '';

            $countryId = $quote->getBillingAddress()->getCountryId();
            $countryCode = $this->apiHelper->getCountryCallingCodes($countryId);

            // Skip if required details are missing
            if (!$email) {
                $email = "johnsmith@yopmail.com";
            }
            if (!$mobileNumber) {
                $mobileNumber = "9898989898";
            }
            return [
                "templateId" => $abandonCartTemplateId,
                "firstName" => $firstName,
                "lastName" => $lastName,
                "countryCode" => $countryCode,
                "mobileNumber" => $mobileNumber,
                "imageURL" => "",
                "email" => $email,
                "businessName" => "Your Store Name",
                "website" => "https://yourstore.com"
            ];
        }
    }

    /**
     * Get Quote Collections
     *
     * @return void
     */
    public function getQuoteCollections()
    {
        try {
            $quote = $this->quoteCollectionFactory->create()
                ->getCollection()
                ->addFieldToFilter('is_active', ['eq' => '1'])
                ->addFieldToFilter('customer_id', ['neq' => null])
                ->setOrder(
                    'created_at',
                    'desc'
                );
            $quote->getSelect()->limit(self::COLLECTION_LIMIT);
            return $quote;
        } catch (Exception $e) {
            $this->logger->addErrorLog(__('Failed to fetch active quotes: %1', $e->getMessage()));
            return null;
        }
    }
}
