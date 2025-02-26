<?php

declare(strict_types=1);

namespace Brille24\SyliusCustomerOptionsPlugin\Services;

use Brille24\SyliusCustomerOptionsPlugin\Entity\CustomerOptions\CustomerOptionValueInterface;
use Brille24\SyliusCustomerOptionsPlugin\Entity\OrderItemInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Order\Model\OrderInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Webmozart\Assert\Assert;

final class CustomerOptionValueRefresher implements OrderProcessorInterface
{
    /**
     * {@inheritdoc}
     *
     * For more info have a look at this graphic `docs/images/OrderProcessor_Usage.png`
     */
    public function process(OrderInterface $order): void
    {
        foreach ($order->getItems() as $orderItem) {
            if (!$orderItem instanceof OrderItemInterface) {
                continue;
            }

            /** @var \Sylius\Component\Core\Model\OrderInterface $order */
            $this->copyOverValuesForOrderItem($orderItem, $order->getChannel());
        }
    }

    /** {@inheritdoc} */
    public function copyOverValuesForOrderItem(OrderItemInterface $orderItem, ChannelInterface $channel): void
    {
        $orderItemOptions = $orderItem->getCustomerOptionConfiguration();
        $product          = $orderItem->getProduct();
        foreach ($orderItemOptions as $orderItemOption) {
            /** @var CustomerOptionValueInterface $customerOptionValue */
            // Gets the object reference to the customer option value
            $customerOptionValue = $orderItemOption->getCustomerOptionValue();
            if ($customerOptionValue === null) {
                continue;
            }

            // This part of the process is needed to not only store the object reference but also update the copied
            // values on the entity so that if the reference changes the values stay the same
            $orderItemOption->setCustomerOptionValue($customerOptionValue);

            $price = $customerOptionValue->getPriceForChannel($channel, $product);
            Assert::notNull($price, 'The customer option value "'.$customerOptionValue->getCode().'" has no price');

            // Same here: Copy the price onto the customer option to be independent of the customer option value object.
            $orderItemOption->setPrice($price);
        }
    }
}
