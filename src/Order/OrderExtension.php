<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Order;

use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DB;
use SwipeStripe\Coupons\CouponInterface;
use SwipeStripe\Coupons\Order\OrderItem\OrderItemCoupon;
use SwipeStripe\Coupons\Order\OrderItem\OrderItemCouponAddOn;
use SwipeStripe\Order\Order;
use SwipeStripe\Order\OrderLockedException;

/**
 * Class OrderExtension
 * @package SwipeStripe\Coupons\Order
 * @property Order|OrderExtension $owner
 */
class OrderExtension extends DataExtension
{
    /**
     * @return bool
     */
    public function hasCoupons(): bool
    {
        return $this->owner->OrderCouponAddOns()->exists() || $this->owner->OrderItemCouponAddOns()->exists();
    }

    /**
     * @return DataList|OrderCouponAddOn[]
     */
    public function OrderCouponAddOns(): DataList
    {
        return OrderCouponAddOn::get()->filter('OrderID', $this->owner->ID);
    }

    /**
     * @return DataList|OrderItemCouponAddOn[]
     */
    public function OrderItemCouponAddOns(): DataList
    {
        return OrderItemCouponAddOn::get()->filter('OrderItem.OrderID', $this->owner->ID);
    }

    /**
     * @return DataList|OrderItemCoupon[]
     */
    public function OrderItemCoupons(): DataList
    {
        return OrderItemCoupon::get()->filter('OrderItemCouponAddOns.OrderItem.OrderID', $this->owner->ID);
    }

    /**
     * @param OrderCoupon $coupon
     */
    public function applyCoupon(OrderCoupon $coupon): void
    {
        if ($this->owner->OrderCouponAddOns()->find('CouponID', $coupon->ID) === null) {
            $addOn = OrderCouponAddOn::create();
            $addOn->OrderID = $this->owner->ID;
            $addOn->setCoupon($coupon);
            $addOn->write();
        }
    }

    /**
     * @param CouponInterface $coupon
     * @return int Number of coupons cleared.
     */
    public function clearNonStackableCoupons(CouponInterface $coupon): int
    {
        if (!$this->owner->IsMutable()) {
            throw new OrderLockedException($this->owner);
        }

        $count = 0;

        foreach ($this->OrderCouponAddOns() as $addOn) {
            if (!$addOn->Coupon()->stacksWith($coupon)) {
                $addOn->delete();
                $count++;
            }
        }

        foreach ($this->OrderItemCouponAddOns() as $addOn) {
            if (!$addOn->Coupon()->stacksWith($coupon)) {
                $addOn->delete();
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return int
     */
    public function clearAppliedOrderCoupons(): int
    {
        if (!$this->owner->IsMutable()) {
            throw new OrderLockedException($this->owner);
        }

        $count = 0;

        foreach ($this->owner->OrderCouponAddOns() as $addOn) {
            $addOn->delete();
            $count++;
        }

        return $count;
    }

    /**
     * @return int
     */
    public function clearAppliedOrderItemCoupons(): int
    {
        if (!$this->owner->IsMutable()) {
            throw new OrderLockedException($this->owner);
        }

        $count = 0;

        foreach ($this->owner->OrderItemCouponAddOns() as $addOn) {
            $addOn->delete();
            $count++;
        }

        return $count;
    }

    /**
     * @throws \Exception
     */
    public function paymentCaptured(): void
    {
        $this->owner->recordOrderCouponUses();
        $this->owner->recordOrderItemCouponUses();
    }

    /**
     * @throws \Exception
     */
    public function recordOrderCouponUses(): void
    {
        DB::get_conn()->withTransaction(function () {
            foreach ($this->owner->OrderCouponAddOns() as $couponAddOn) {
                if (!$couponAddOn->UseRecorded && $couponAddOn->isActive()) {
                    $couponAddOn->UseRecorded = true;
                    $couponAddOn->write();

                    // Get by ID to prevent setting versioned parameters - we want the latest version
                    $coupon = OrderCoupon::get_by_id($couponAddOn->CouponID);
                    if ($coupon->LimitUses) {
                        $coupon->RemainingUses = max(0, $coupon->RemainingUses - 1);
                        $coupon->write();
                    }
                }
            }
        });
    }

    /**
     * @throws \Exception
     */
    public function recordOrderItemCouponUses()
    {
        DB::get_conn()->withTransaction(function () {
            $couponIdsToRecordUse = [];

            foreach ($this->owner->OrderItemCouponAddOns() as $couponAddOn) {
                if (!$couponAddOn->UseRecorded && $couponAddOn->isActive()) {
                    $couponId = $couponAddOn->CouponID;
                    $couponIdsToRecordUse[$couponId] = $couponId;

                    $couponAddOn->UseRecorded = true;
                    $couponAddOn->write();
                }
            }

            foreach ($couponIdsToRecordUse as $id) {
                $coupon = OrderItemCoupon::get_by_id($id);
                if ($coupon->LimitUses) {
                    $coupon->RemainingUses = max(0, $coupon->RemainingUses - 1);
                    $coupon->write();
                }
            }
        });
    }
}
