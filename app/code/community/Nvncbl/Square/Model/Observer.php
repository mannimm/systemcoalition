<?php

class Nvncbl_Square_Model_Observer
{
	public function sales_order_payment_place_end($observer)
	{
		$customer = $observer->getPayment()->getOrder()->getCustomer();
		$customerId = $customer->getId();
		$customerEmail = $customer->getEmail();

		if (!empty($customerId) && !empty($customerEmail))
		{
			try
			{
				$resource = Mage::getSingleton('core/resource');
				$connection = $resource->getConnection('core_write');
				$fields = array();
				$fields['customer_id'] = $customerId;
				$condition = array($connection->quoteInto('customer_email=?', $customerEmail));
				$result = $connection->update('nvncbl_squaresubscriptions_customers', $fields, $condition);
			}
			catch (Exception $e) {}
		}
	}
}