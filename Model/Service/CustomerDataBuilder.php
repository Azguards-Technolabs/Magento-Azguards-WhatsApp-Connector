<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Azguards\WhatsAppConnect\Logger\Logger;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;

class CustomerDataBuilder
{
    /**
     * @var ApiHelper
     */
    private ApiHelper $apiHelper;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var AddressRepositoryInterface
     */
    private AddressRepositoryInterface $addressRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @param ApiHelper $apiHelper
     * @param StoreManagerInterface $storeManager
     * @param AddressRepositoryInterface $addressRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param Logger $logger
     */
    public function __construct(
        ApiHelper $apiHelper,
        StoreManagerInterface $storeManager,
        AddressRepositoryInterface $addressRepository,
        CustomerRepositoryInterface $customerRepository,
        Logger $logger
    ) {
        $this->apiHelper = $apiHelper;
        $this->storeManager = $storeManager;
        $this->addressRepository = $addressRepository;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
    }

    /**
     * Build recipient data from a Magento customer.
     *
     * @param CustomerInterface $customer
     * @return array
     */
    public function buildFromCustomer($customer): array
    {
        try {
            $this->logger->info('CustomerDataBuilder::buildFromCustomer started.');
            $billingAddress = $this->getDefaultBillingAddress($customer);
            $this->logger->info('CustomerDataBuilder::buildFromCustomer - Billing address resolved.');

            $countryId = $billingAddress ? (string)$billingAddress->getCountryId() : '';
            $telephone = $billingAddress ? (string)$billingAddress->getTelephone() : '';

            $mobileNumber = $this->getCustomAttributeValue($customer, 'whatsapp_phone_number');
            if (!$mobileNumber) {
                $mobileNumber = $this->getCustomAttributeValue($customer, 'mobile_number');
            }

            if ($mobileNumber) {
                $telephone = $mobileNumber;
            }

            $whatsappCountryCode = $this->getCustomAttributeValue($customer, 'whatsapp_country_code');
            $countryCode = $whatsappCountryCode ?: $this->apiHelper->getCountryCallingCodes($countryId ?: 'IN');

            $countryCode = preg_replace('/\D/', '', (string)$countryCode);
            $telephone = preg_replace('/\D/', '', (string)$telephone);

            // Generate a stable placeholder when a customer has no phone number.
            if (!$telephone) {
                $countryCode = $countryCode ?: '91';
                $telephone = '999' . str_pad((string)$customer->getId(), 7, '0', STR_PAD_LEFT);
            }

            $result = [
                'firstName' => (string)$customer->getFirstname(),
                'lastName' => (string)$customer->getLastname(),
                'countryCode' => $countryCode,
                'mobileNumber' => $telephone,
                'contactId' => $this->getCustomAttributeValue($customer, 'whatsapp_contact_id'),
                'imageURL' => '',
                'email' => (string)$customer->getEmail(),
                'businessName' => $this->getCustomAttributeValue($customer, 'business_name'),
                'website' => $this->storeManager->getStore()->getBaseUrl(),
            ];
            $this->logger->info('CustomerDataBuilder::buildFromCustomer completed.');
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Error in CustomerDataBuilder::buildFromCustomer: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Build recipient data from an order.
     *
     * @param OrderInterface $order
     * @return array
     */
    public function buildFromOrder(OrderInterface $order): array
    {
        $customerId = (int)$order->getCustomerId();
        if ($customerId > 0) {
            try {
                $customer = $this->customerRepository->getById($customerId);
                return $this->buildFromCustomer($customer);
            } catch (\Throwable $e) {
                $this->logger->warning(
                    'CustomerDataBuilder::buildFromOrder - failed to load customer: ' . $e->getMessage()
                );
            }
        }

        return $this->apiHelper->getUserDetailData($order);
    }

    /**
     * Build recipient details from a quote.
     *
     * @param CartInterface $quote
     * @return array
     *
     * Abandoned cart messages should never send to fabricated placeholder numbers; require a real phone.
     */
    public function buildFromQuote(CartInterface $quote): array
    {
        $customerId = (int)$quote->getCustomerId();
        if ($customerId > 0) {
            try {
                $customer = $this->customerRepository->getById($customerId);
                $userDetail = $this->buildFromCustomer($customer);

                // For abandoned cart messages we require an actual phone number.
                if (!empty($userDetail['mobileNumber'])
                    && str_starts_with((string)$userDetail['mobileNumber'], '999')
                ) {
                    $userDetail['mobileNumber'] = '';
                }

                return $userDetail;
            } catch (\Throwable $e) {
                $this->logger->warning(
                    'CustomerDataBuilder::buildFromQuote - failed to load customer: ' . $e->getMessage()
                );
            }
        }

        $billing = $quote->getBillingAddress();
        $countryId = $billing ? (string)$billing->getCountryId() : '';
        $telephone = $billing ? (string)$billing->getTelephone() : '';
        $email = (string)$quote->getCustomerEmail();
        $firstName = $billing ? (string)$billing->getFirstname() : (string)$quote->getCustomerFirstname();
        $lastName = $billing ? (string)$billing->getLastname() : (string)$quote->getCustomerLastname();

        $countryCode = $this->apiHelper->getCountryCallingCodes($countryId ?: 'IN');
        $countryCode = preg_replace('/\D/', '', (string)$countryCode);
        $telephone = preg_replace('/\D/', '', (string)$telephone);

        return [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'countryCode' => $countryCode,
            'mobileNumber' => $telephone,
            'contactId' => '',
            'imageURL' => '',
            'email' => $email,
            'businessName' => '',
            'website' => $this->storeManager->getStore((int)$quote->getStoreId())->getBaseUrl(),
        ];
    }

    /**
     * Load the customer's default billing address if available.
     *
     * @param CustomerInterface $customer
     * @return \Magento\Customer\Api\Data\AddressInterface|null
     */
    private function getDefaultBillingAddress($customer)
    {
        $billingId = $customer->getDefaultBilling();
        if (!$billingId) {
            return null;
        }

        try {
            return $this->addressRepository->getById((int)$billingId);
        } catch (NoSuchEntityException $exception) {
            return null;
        }
    }

    /**
     * Read a custom attribute from a customer-like entity.
     *
     * @param CustomerInterface $customer
     * @param string $attributeCode
     * @return string
     */
    private function getCustomAttributeValue($customer, string $attributeCode): string
    {
        if (method_exists($customer, 'getCustomAttribute')) {
            $attribute = $customer->getCustomAttribute($attributeCode);
            if ($attribute) {
                return (string)$attribute->getValue();
            }
        }

        if (method_exists($customer, 'getData')) {
            return (string)$customer->getData($attributeCode);
        }

        return '';
    }
}
