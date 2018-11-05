<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Order\OrderItem;

use SwipeStripe\Order\OrderItem\OrderItemAddOn;
use SwipeStripe\Price\DBPrice;

/**
 * Class OrderItemCouponAddOn
 * @package SwipeStripe\Coupons\Order\OrderItem
 * @property int $CouponID
 * @method OrderItemCoupon Coupon()
 */
class OrderItemCouponAddOn extends OrderItemAddOn
{
    /**
     * @var string
     */
    private static $table_name = 'SwipeStripe_Coupons_OrderItemAddOn';

    /**
     * @var array
     */
    private static $has_one = [
        'Coupon' => OrderItemCoupon::class,
    ];

    /**
     * @param OrderItemCoupon $coupon
     * @return $this
     */
    public function setCoupon(OrderItemCoupon $coupon): self
    {
        $this->CouponID = $coupon->ID;
        $this->Title = _t(self::class . '.ADDON_TITLE', 'Coupon - {coupon_title}', [
            'coupon_title' => $coupon->Title,
        ]);

        return $this;
    }

    /**
     * @return DBPrice
     */
    public function getAmount(): DBPrice
    {
        return $this->Coupon()->AmountFor($this->OrderItem());
    }
}