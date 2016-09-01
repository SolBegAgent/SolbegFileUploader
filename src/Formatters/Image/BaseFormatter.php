<?php

namespace Bicycle\FilesManager\Formatters\Image;

/**
 * Image\BaseFormatter is base formatter for all image formatters.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
abstract class BaseFormatter extends \Bicycle\FilesManager\Formatters\AbstractFormatter
{
    const DRIVER_GD2 = 'gd2';
    const DRIVER_IMAGICK = 'imagick';
    const DRIVER_GMAGICK = 'gmagick';

    /**
     * @var array priority of imagine drivers.
     * If `$driver` priority is null then drivers in this priority will be choose to use.
     */
    public static $driversPriority = [
        self::DRIVER_GMAGICK,
        self::DRIVER_IMAGICK,
        self::DRIVER_GD2,
    ];

    /**
     * @var string|null imagine driver that should be used.
     * Null is meaning the driver will be detected autovatically.
     */
    protected $driver;
}
