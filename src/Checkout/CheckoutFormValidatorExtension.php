<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Checkout;

use SilverStripe\Core\Extension;
use SwipeStripe\Coupons\OrderCoupon;
use SwipeStripe\Order\Checkout\CheckoutForm;
use SwipeStripe\Order\Checkout\CheckoutFormValidator;

/**
 * Class CheckoutFormValidatorExtension
 * @package SwipeStripe\Coupons\Checkout
 * @property CheckoutFormValidator|CheckoutFormValidatorExtension $owner
 */
class CheckoutFormValidatorExtension extends Extension
{
    /**
     * @param CheckoutForm $form
     * @param array $data
     */
    public function validate(CheckoutForm $form, array $data): void
    {
        $code = $data[CheckoutFormExtension::COUPON_CODE_FIELD];
        if (empty($code)) {
            return;
        }

        $coupon = OrderCoupon::getByCode($code);
        if ($coupon === null) {
            $this->owner->validationError(CheckoutFormExtension::COUPON_CODE_FIELD,
                _t(self::class . '.COUPON_INVALID', 'Sorry, that coupon code is invalid.'));
        } elseif (!$coupon->isValidFor($form->getCart())) {
            $this->owner->validationError(CheckoutFormExtension::COUPON_CODE_FIELD,
                _t(self::class . '.COUPON_INACTIVE', 'Sorry, that coupon is not currently available.'));
        }
    }
}
