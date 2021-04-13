<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OrderDeviceDetector\Plugin\Block\Sales\Adminhtml\Order\View;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderExtension;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Block\Adminhtml\Order\View\Info as InfoBlock;
use Magento\Sales\Model\Order;
use MageWorx\OrdersBase\Api\Data\DeviceDataInterface;

/**
 * Class Info
 *
 * Plugin which adds the device data to the order view page
 */
class Info
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * Info constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param InfoBlock $subject
     * @param array $result
     * @return array
     */
    public function afterGetCustomerAccountData(
        InfoBlock $subject,
        array $result
    ): array {
        try {
            $order = $subject->getOrder();
        } catch (LocalizedException $exception) {
            return $result;
        }

        $deviceDataObject = $this->getDeviceDataObject($order);
        if ($deviceDataObject) {
            $result[] = [
                'label' => __('Device'),
                'value' => $deviceDataObject->getDeviceName()
            ];
            $result[] = [
                'label' => __('Area'),
                'value' => $deviceDataObject->getAreaName()
            ];
        }

        return $result;
    }

    /**
     * @param Order $order
     * @return DeviceDataInterface|null
     */
    protected function getDeviceDataObject(Order $order): ?DeviceDataInterface
    {
        /** @var OrderExtension $extensionAttributes */
        $extensionAttributes = $order->getExtensionAttributes();
        if (!$extensionAttributes || !$extensionAttributes instanceof OrderExtension) {
            $order = $this->orderRepository->get($order->getId());
            /** @var OrderExtension $extensionAttributes */
            $extensionAttributes = $order->getExtensionAttributes();
        }

        if (!$extensionAttributes) {
            return null;
        }

        return $extensionAttributes->getDeviceData();
    }
}
