<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Quote\Api;

use Magento\Quote\Api\Data\AddressInterface;
use Magento\TestFramework\TestCase\WebapiAbstract;
use Magento\Webapi\Model\Rest\Config as RestConfig;

class ShippingAddressManagementTest extends WebapiAbstract
{
    const SERVICE_VERSION = 'V1';
    const SERVICE_NAME = 'quoteShippingAddressManagementV1';
    const RESOURCE_PATH = '/V1/carts/';

    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    protected $objectManager;

    protected function setUp()
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
    }

    /**
     * @magentoApiDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     */
    public function testGetAddress()
    {
        $quote = $this->objectManager->create('Magento\Quote\Model\Quote');
        $quote->load('test_order_1', 'reserved_order_id');

        /** @var \Magento\Quote\Model\Quote\Address  $address */
        $address = $quote->getShippingAddress();

        $data = [
            AddressInterface::KEY_COUNTRY_ID => $address->getCountryId(),
            AddressInterface::KEY_ID => (int)$address->getId(),
            AddressInterface::KEY_CUSTOMER_ID => $address->getCustomerId(),
            AddressInterface::REGION => $address->getRegion(),
            AddressInterface::REGION_ID => $address->getRegionId(),
            AddressInterface::REGION_CODE => $address->getRegionCode(),
            AddressInterface::KEY_STREET => $address->getStreet(),
            AddressInterface::KEY_COMPANY => $address->getCompany(),
            AddressInterface::KEY_TELEPHONE => $address->getTelephone(),
            AddressInterface::KEY_POSTCODE => $address->getPostcode(),
            AddressInterface::KEY_CITY => $address->getCity(),
            AddressInterface::KEY_FIRSTNAME => $address->getFirstname(),
            AddressInterface::KEY_LASTNAME => $address->getLastname(),
            AddressInterface::KEY_EMAIL => $address->getEmail()
        ];

        $cartId = $quote->getId();

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . $quote->getId() . '/shipping-address',
                'httpMethod' => RestConfig::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'Get',
            ],
        ];

        $requestData = ["cartId" => $cartId];
        $this->assertEquals($data, $this->_webApiCall($serviceInfo, $requestData));
    }

    /**
     * @magentoApiDataFixture Magento/Checkout/_files/quote_with_virtual_product_and_address.php
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Cart contains virtual product(s) only. Shipping address is not applicable
     */
    public function testGetAddressOfQuoteWithVirtualProduct()
    {
        $quote = $this->objectManager->create('Magento\Quote\Model\Quote');
        $cartId = $quote->load('test_order_with_virtual_product', 'reserved_order_id')->getId();

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . $quote->getId() . '/shipping-address',
                'httpMethod' => RestConfig::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'Get',
            ],
        ];

        $this->_webApiCall($serviceInfo, ["cartId" => $cartId]);
    }

    /**
     * @magentoApiDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     */
    public function testSetAddress()
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->objectManager->create('Magento\Quote\Model\Quote');
        $quote->load('test_order_1', 'reserved_order_id');

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . $quote->getId() . '/shipping-address',
                'httpMethod' => RestConfig::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'Assign',
            ],
        ];

        $addressData = [
            'firstname' => 'John',
            'lastname' => 'Smith',
            'email' => 'cat@dog.com',
            'company' => 'eBay Inc',
            'street' => ['Typical Street', 'Tiny House 18'],
            'city' => 'Big City',
            'region_id' => 12,
            'region' => 'California',
            'region_code' => 'CA',
            'postcode' => '0985432',
            'country_id' => 'US',
            'telephone' => '88776655',
            'fax' => '44332255',
        ];
        $requestData = [
            "cartId" => $quote->getId(),
            'address' => $addressData,
        ];

        $addressId = $this->_webApiCall($serviceInfo, $requestData);

        //reset $quote to reload data
        $quote = $this->objectManager->create('Magento\Quote\Model\Quote');
        $quote->load('test_order_1', 'reserved_order_id');
        $address = $quote->getShippingAddress();
        $address->getRegionCode();
        $savedData  = $address->getData();
        $this->assertEquals($addressId, $savedData['address_id'], 'Invalid address ID');
        $this->assertEquals(0, $savedData['same_as_billing']);
        //custom checks for street, region and address_type
        $this->assertEquals($addressData['street'], $quote->getShippingAddress()->getStreet());
        unset($addressData['street']);

        $this->assertEquals('shipping', $savedData['address_type']);
        //check the rest of fields
        foreach ($addressData as $key => $value) {
            $this->assertEquals($value, $savedData[$key], 'Invalid value for ' . $key);
        }
    }

    /**
     * Set address to quote with virtual products only
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Cart contains virtual product(s) only. Shipping address is not applicable
     *
     * @magentoApiDataFixture Magento/Checkout/_files/quote_with_virtual_product_and_address.php
     */
    public function testSetAddressForVirtualQuote()
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->objectManager->create('Magento\Quote\Model\Quote');
        $quote->load('test_order_with_virtual_product', 'reserved_order_id');

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . $quote->getId() . '/shipping-address',
                'httpMethod' => RestConfig::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'Assign',
            ],
        ];

        $addressData = [
            'firstname' => 'John',
            'lastname' => 'Smith',
            'email' => 'cat@dog.com',
            'company' => 'eBay Inc',
            'street' => ['Typical Street', 'Tiny House 18'],
            'city' => 'Big City',
            'region_id' => 12,
            'region' => 'California',
            'region_code' => 'CA',
            'postcode' => '0985432',
            'country_id' => 'US',
            'telephone' => '88776655',
            'fax' => '44332255',
        ];
        $requestData = [
            "cartId" => $quote->getId(),
            'address' => $addressData,
        ];

        $this->_webApiCall($serviceInfo, $requestData);
    }
}
