<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OrderDeviceDetector\Model;

/**
 * Class DeviceDataParser
 *
 * Use Matomo/DeviceDetector as a device data parser
 */
class DeviceDataParser implements \MageWorx\OrdersBase\Api\DeviceDataParserInterface
{
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
}
