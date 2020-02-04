<?php

namespace Tests\Functional\Cart;

use FunctionalTester;
use Faker\Factory;
use Webkul\Tax\Models\TaxMap;
use Webkul\Tax\Models\TaxRate;
use Webkul\Tax\Models\TaxCategory;
use Webkul\Checkout\Models\Cart as CartModel;
use Cart;

class CartCest
{
    private $country;
    private $faker;
    private $product1, $product2;
    private $tax1, $tax2;

    function _before(FunctionalTester $I)
    {
        $this->faker = Factory::create();

        $this->country = 'DE'; //$this->faker->countryCode;

        $this->tax1 = $I->have(TaxRate::class, ['tax_rate' => 7.00, 'country' => $this->country]);
        $taxCategorie1 = $I->have(TaxCategory::class, []);
        $I->have(TaxMap::class, ['tax_rate_id' => $this->tax1->id, 'tax_category_id' => $taxCategorie1->id]);

        $this->tax2 = $I->have(TaxRate::class, ['tax_rate' => 19.00, 'country' => $this->country]);
        $taxCategorie2 = $I->have(TaxCategory::class, []);
        $I->have(TaxMap::class, ['tax_rate_id' => $this->tax2->id, 'tax_category_id' => $taxCategorie2->id]);

        $config1 = [
            'productInventory' => ['qty' => 100],
            'attributeValues'  => [
                'status'          => true,
                'new'             => 1,
                'tax_category_id' => $taxCategorie1->id,
            ],
        ];
        $this->product1 = $I->haveProduct($config1, ['simple']);

        $config2 = [
            'productInventory' => ['qty' => 100],
            'attributeValues'  => [
                'status'          => true,
                'new'             => 1,
                'tax_category_id' => $taxCategorie2->id,
            ],
        ];
        $this->product2 = $I->haveProduct($config2, ['simple']);
    }

    public function checkCartWithMultipleTaxRates(FunctionalTester $I)
    {
        $I->setConfigData(['default_country' => $this->country]);

        $prod1Quantity = $this->faker->numberBetween(9, 30);
        if ($prod1Quantity % 2 !== 0) {
            $prod1Quantity -= 1;
        }

        $prod2Quantity = $this->faker->numberBetween(9, 30);
        if ($prod2Quantity % 2 == 0) {
            $prod2Quantity -= 1;
        }

        Cart::addProduct($this->product1->id, [
            '_token'     => session('_token'),
            'product_id' => $this->product1->id,
            'quantity'   => 1,
        ]);

        $I->amOnPage('/checkout/cart');
        $I->see('Tax ' . $this->tax1->tax_rate . ' %', '#taxrate-' . $this->tax1->tax_rate);
        $I->see(round($this->product1->price * $this->tax1->tax_rate / 100, 2),
            '#basetaxamount-' . $this->tax1->tax_rate);

        Cart::addProduct($this->product1->id, [
            '_token'     => session('_token'),
            'product_id' => $this->product1->id,
            'quantity'   => $prod1Quantity,
        ]);

        $I->amOnPage('/checkout/cart');
        $I->see('Tax ' . $this->tax1->tax_rate . ' %', '#taxrate-' . $this->tax1->tax_rate);
        $I->see(round(($prod1Quantity + 1) * $this->product1->price * $this->tax1->tax_rate / 100, 2),
            '#basetaxamount-' . $this->tax1->tax_rate);

        Cart::addProduct($this->product2->id, [
            '_token'     => session('_token'),
            'product_id' => $this->product2->id,
            'quantity'   => $prod2Quantity,
        ]);

        $I->amOnPage('/checkout/cart');
        $I->see('Tax ' . $this->tax1->tax_rate . ' %', '#taxrate-' . $this->tax1->tax_rate);
        $taxAmount1 = round(($prod1Quantity + 1) * $this->product1->price * $this->tax1->tax_rate / 100, 2);
        $I->see(core()->currency($taxAmount1),'#basetaxamount-' . $this->tax1->tax_rate);

        $I->see('Tax ' . $this->tax2->tax_rate . ' %', '#taxrate-' . $this->tax2->tax_rate);
        $taxAmount2 = round($prod2Quantity * $this->product2->price * $this->tax2->tax_rate / 100, 2);
        $I->see(core()->currency($taxAmount2),'#basetaxamount-' . $this->tax2->tax_rate);

        $cart = Cart::getCart();

        $I->assertEquals(2, $cart->items_count);
        $I->assertEquals((float)($prod1Quantity + 1 + $prod2Quantity), $cart->items_qty);
        $I->assertEquals($taxAmount1 + $taxAmount2, $cart->tax_total);


    }
}