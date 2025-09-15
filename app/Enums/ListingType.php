<?php

namespace App\Enums;

enum ListingType: string
{
    case PRODUCT_SELLER = 'product_seller';
    case PRODUCT_BUYER = 'product_buyer';
    case SERVICE_GIVER = 'service_giver';
    case SERVICE_TAKER = 'service_taker';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PRODUCT_SELLER => __('listing_types.product_seller'),
            self::PRODUCT_BUYER => __('listing_types.product_buyer'),
            self::SERVICE_GIVER => __('listing_types.service_giver'),
            self::SERVICE_TAKER => __('listing_types.service_taker'),
            self::OTHER => __('listing_types.other'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PRODUCT_SELLER => 'fa-store',
            self::PRODUCT_BUYER => 'fa-shopping-cart',
            self::SERVICE_GIVER => 'fa-tools',
            self::SERVICE_TAKER => 'fa-handshake',
            self::OTHER => 'fa-box',
        };
    }
}
