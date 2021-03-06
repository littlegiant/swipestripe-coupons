<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Checkout;

use SilverStripe\Core\Extension;
use SwipeStripe\Coupons\Order\OrderExtension;
use SwipeStripe\Order\Checkout\CheckoutFormInterface;
use SwipeStripe\Order\Checkout\CheckoutFormValidator;
use SwipeStripe\Order\Order;

/**
 * Class CheckoutFormValidatorExtension
 * @package SwipeStripe\Coupons\Checkout
 * @property CheckoutFormValidator|CheckoutFormValidatorExtension $owner
 */
class CheckoutFormValidatorExtension extends Extension
{
    /**
     * @param CheckoutFormInterface $form
     * @param array $data
     */
    public function validate(CheckoutFormInterface $form, array $data): void
    {
        /** @var Order|OrderExtension $cart */
        $cart = $form->getCart();
        foreach ($cart->OrderCouponAddOns() as $addOn) {
            $this->owner->getResult()->combineAnd($addOn->Coupon()->isValidFor($cart));
        }

        foreach ($cart->OrderItemCoupons() as $coupon) {
            $this->owner->getResult()->combineAnd($coupon->isValidFor($cart));
        }
    }
}
