<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OrderDeviceDetector\Model;

use Magento\Framework\Exception\LocalizedException;
use MageWorx\OrdersBase\Api\Data;

/**
 * Class DeviceDataParser
 *
 * Use Matomo/DeviceDetector as a device data parser
 */
class DeviceDataParser implements \MageWorx\OrdersBase\Api\DeviceTypeParserInterface
{
    /**
     * @var \DeviceDetector\DeviceDetectorFactory|null
     */
    protected $deviceDetectorFactory;

    /**
     * @var \Magento\Framework\HTTP\Header
     */
    protected $httpHeader;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * OrderPlaced constructor.
     *
     * @param \Magento\Framework\HTTP\Header $httpHeader
     * @param \MageWorx\OrdersBase\Api\DeviceDataRepositoryInterface $deviceDataRepository
     * @param \Psr\Log\LoggerInterface $logger
     * @param \MageWorx\OrderDeviceDetector\Model\DeviceDetectorFactory $deviceDetectorFactory
     * @param \MageWorx\OrdersBase\Api\DataParserInterface[] $parsers
     */
    public function __construct(
        \Magento\Framework\HTTP\Header $httpHeader,
        \Psr\Log\LoggerInterface $logger,
        \MageWorx\OrderDeviceDetector\Model\DeviceDetectorFactory $deviceDetectorFactory
    ) {
        $this->httpHeader            = $httpHeader;
        $this->logger                = $logger;
        $this->deviceDetectorFactory = $deviceDetectorFactory;
    }

    /**
     * @inheritDoc
     */
    public function getDeviceName(string $code = null): string
    {
        if ($code === null) {
            return 'API';
        }

        if (\class_exists('\DeviceDetector\Parser\Device\DeviceParserAbstract')) {
            $name = \DeviceDetector\Parser\Device\DeviceParserAbstract::getDeviceName($code);
        } elseif (\class_exists('\DeviceDetector\Parser\Device\AbstractDeviceParser')) {
            $name = \DeviceDetector\Parser\Device\AbstractDeviceParser::getDeviceName($code);
        } else {
            $name = 'unknown';
        }

        return ucwords($name);
    }

    /**
     * @inheritDoc
     */
    public function parseData(\Magento\Sales\Api\Data\OrderInterface $order, Data\DeviceDataInterface $deviceData): void
    {
        $code = $this->getDeviceCode();
        $deviceName = $this->getDeviceName($code);
        $deviceData->setDeviceName($deviceName);
    }

    /**
     * @inheritDoc
     */
    public function getDeviceCode(): ?string
    {
        $deviceCode = null;
        if ($this->deviceDetectorFactory === null) {
            return $deviceCode;
        }

        $userAgent = $this->httpHeader->getHttpUserAgent();
        if (!is_string($userAgent)) {
            if (is_array($userAgent)) {
                $userAgent = implode(' ', $userAgent);
            } elseif (is_object($userAgent)) {
                /** @var object $userAgent */
                $userAgent = $userAgent->__toString();
            } else {
                throw new LocalizedException(__('Unable to detect user agent.'));
            }
        }
        try {
            /** @var \DeviceDetector\DeviceDetector $deviceDetector */
            $deviceDetector = $this->deviceDetectorFactory->create(['userAgent' => $userAgent]);
            $deviceDetector->discardBotInformation();
            $deviceDetector->parse();

            if (!$deviceDetector->isBot()) {
                $deviceCode = $deviceDetector->getDevice();
            }
        } catch (\ReflectionException $runtimeException) {
            // Case when DeviceDetector instance could not be initialized by ObjectManager
            // Log error and proceed checkout without saving device data (it is not critical)
            $this->logger->warning($runtimeException->getMessage());
        }

        return $deviceCode;
    }
}
