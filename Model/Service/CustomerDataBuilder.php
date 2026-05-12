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
     * @param array|null $resolvedRecipient
     * @return array
     */
    public function buildFromCustomer($customer, ?array $resolvedRecipient = null): array
    {
        try {
            $this->logger->info('CustomerDataBuilder::buildFromCustomer started.');

            // Senior Priority Logic: Billing Address -> Shipping Address -> EAV Attribute
            $telephone = '';
            $countryId = '';

            $billingAddress = $this->getDefaultBillingAddress($customer);
            if ($billingAddress) {
                $telephone = (string)$billingAddress->getTelephone();
                $countryId = (string)$billingAddress->getCountryId();
            }

            if (!$telephone) {
                $shippingAddress = $this->getDefaultShippingAddress($customer);
                if ($shippingAddress) {
                    $telephone = (string)$shippingAddress->getTelephone();
                    $countryId = (string)$shippingAddress->getCountryId();
                }
            }

            if (!$telephone) {
                $telephone = $this->getCustomAttributeValue($customer, 'whatsapp_phone_number');
                if (!$telephone) {
                    $telephone = $this->getCustomAttributeValue($customer, 'mobile_number');
                }
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

            $store = $this->storeManager->getStore();
            $result = [
                'firstName' => (string)$customer->getFirstname(),
                'lastName' => (string)$customer->getLastname(),
                'countryCode' => $countryCode,
                'mobileNumber' => $telephone,
                'contactId' => $this->getCustomAttributeValue($customer, 'whatsapp_contact_id'),
                'imageURL' => '',
                'email' => (string)$customer->getEmail(),
                'businessName' => $this->getCustomAttributeValue($customer, 'business_name'),
                'website' => $store->getBaseUrl(),
                'customerId' => $customer->getId(),

                // Add nested structures for senior variable resolution (e.g. {{var customer.firstname}})
                'customer' => [
                    'firstname' => (string)$customer->getFirstname(),
                    'lastname' => (string)$customer->getLastname(),
                    'email' => (string)$customer->getEmail(),
                    'created_in' => $customer instanceof CustomerInterface ?
                                    (string)$customer->getCreatedIn() :
                                    (string)$customer->getData('created_in'),
                ],
                'store' => [
                    'name' => $store->getName(),
                    'base_url' => $store->getBaseUrl(),
                ]
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
     * @param array|null $resolvedRecipient
     * @return array
     */
    public function buildFromOrder(OrderInterface $order, ?array $resolvedRecipient = null): array
    {
        $customerId = (int)$order->getCustomerId();
        $userDetail = [];

        if ($customerId > 0) {
            try {
                $customer = $this->customerRepository->getById($customerId);
                $userDetail = $this->buildFromCustomer($customer, $resolvedRecipient);

                if ($resolvedRecipient && !empty($resolvedRecipient['mobileNumber'])) {
                    $userDetail['mobileNumber'] = preg_replace('/\D/', '', $resolvedRecipient['mobileNumber']);
                    $userDetail['countryCode'] = preg_replace('/\D/', '', $resolvedRecipient['countryCode']);
                }
            } catch (\Throwable $e) {
                $this->logger->warning(
                    'CustomerDataBuilder::buildFromOrder - failed to load customer: ' . $e->getMessage()
                );
            }
        }

        if (empty($userDetail)) {
            $userDetail = $this->apiHelper->getUserDetailData($order);
            if ($resolvedRecipient && !empty($resolvedRecipient['mobileNumber'])) {
                $userDetail['mobileNumber'] = preg_replace('/\D/', '', $resolvedRecipient['mobileNumber']);
                $userDetail['countryCode'] = preg_replace('/\D/', '', $resolvedRecipient['countryCode']);
            }
        }

        $userDetail['order'] = [
            'increment_id' => (string)$order->getIncrementId(),
            'status' => (string)$order->getStatus(),
            'grand_total' => (string)$order->getGrandTotal(),
        ];

        return $userDetail;
    }

    /**
     * Build recipient details from a quote.
     *
     * @param CartInterface $quote
     * @param array|null $resolvedRecipient
     * @return array
     *
     * Abandoned cart messages should never send to fabricated placeholder numbers; require a real phone.
     */
    public function buildFromQuote(CartInterface $quote, ?array $resolvedRecipient = null): array
    {
        $customerId = (int)$quote->getCustomerId();
        $userDetail = [];

        if ($customerId > 0) {
            try {
                $customer = $this->customerRepository->getById($customerId);
                $userDetail = $this->buildFromCustomer($customer, $resolvedRecipient);

                if ($resolvedRecipient && !empty($resolvedRecipient['mobileNumber'])) {
                    $userDetail['mobileNumber'] = preg_replace('/\D/', '', $resolvedRecipient['mobileNumber']);
                    $userDetail['countryCode'] = preg_replace('/\D/', '', $resolvedRecipient['countryCode']);
                }

                // For abandoned cart messages we require an actual phone number.
                if (!empty($userDetail['mobileNumber'])
                    && str_starts_with((string)$userDetail['mobileNumber'], '999')
                ) {
                    $userDetail['mobileNumber'] = '';
                }
            } catch (\Throwable $e) {
                $this->logger->warning(
                    'CustomerDataBuilder::buildFromQuote - failed to load customer: ' . $e->getMessage()
                );
            }
        }

        if (empty($userDetail)) {
            $billing = $quote->getBillingAddress();
            $email = (string)$quote->getCustomerEmail();
            $firstName = $billing ? (string)$billing->getFirstname() : (string)$quote->getCustomerFirstname();
            $lastName = $billing ? (string)$billing->getLastname() : (string)$quote->getCustomerLastname();

            if ($resolvedRecipient && !empty($resolvedRecipient['mobileNumber'])) {
                $telephone = preg_replace('/\D/', '', $resolvedRecipient['mobileNumber']);
                $countryCode = preg_replace('/\D/', '', $resolvedRecipient['countryCode']);
            } else {
                $countryId = $billing ? (string)$billing->getCountryId() : '';
                $telephone = $billing ? (string)$billing->getTelephone() : '';
                $countryCode = $this->apiHelper->getCountryCallingCodes($countryId ?: 'IN');
                $countryCode = preg_replace('/\D/', '', (string)$countryCode);
                $telephone = preg_replace('/\D/', '', (string)$telephone);
            }

            $store = $this->storeManager->getStore((int)$quote->getStoreId());
            $userDetail = [
                'firstName' => $firstName,
                'lastName' => $lastName,
                'countryCode' => $countryCode,
                'mobileNumber' => $telephone,
                'contactId' => '',
                'imageURL' => '',
                'email' => $email,
                'businessName' => '',
                'website' => $store->getBaseUrl(),
                'store' => [
                    'name' => $store->getName(),
                    'base_url' => $store->getBaseUrl(),
                ]
            ];
        }

        $userDetail['quote'] = [
            'grand_total' => (string)$quote->getGrandTotal(),
        ];

        return $userDetail;
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
     * Load the customer's default shipping address if available.
     *
     * @param CustomerInterface $customer
     * @return \Magento\Customer\Api\Data\AddressInterface|null
     */
    private function getDefaultShippingAddress($customer)
    {
        $shippingId = $customer->getDefaultShipping();
        if (!$shippingId) {
            return null;
        }

        try {
            return $this->addressRepository->getById((int)$shippingId);
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
