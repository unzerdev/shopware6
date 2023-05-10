<?php

declare(strict_types=1);

namespace Shopware\Core\Framework\Routing\Annotation;

// TODO: Remove me if compatibility is at least 6.5.0.0
if (!class_exists('\Shopware\Core\Framework\Routing\Annotation\RouteScope')) {
    /**
     * @Annotation
     * @Attributes({
     *     @Attribute("scopes",  type="array"),
     * })
     */
    class RouteScope
    {
        /** @var array */
        public $scopes = [];
    }
}
