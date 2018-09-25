<?php

namespace Webkul\Cart\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Webkul\Cart\Repositories\CartRepository;
use Webkul\Cart\Repositories\CartItemRepository;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Product\Product\ProductImage;
use Webkul\Product\Product\View as ProductView;
use Webkul\Attribute\Repositories\AttributeOptionRepository;

use Cart;

/**
 * Cart controller for the customer
 * and guest users for adding and
 * removing the products in the
 * cart.
 *
 * @author    Prashant Singh <prashant.singh852@webkul.com>
 * @copyright 2018 Webkul Software Pvt Ltd (http://www.webkul.com)
 */
class CartController extends Controller
{

    /**
     * Protected Variables that
     * holds instances of the
     * repository classes.
     *
     * @param Array $_config
     * @param $cart
     * @param $cartItem
     * @param $customer
     * @param $product
     * @param $productImage
     * @param $productView
     */
    protected $_config;

    protected $cart;

    protected $cartItem;

    protected $customer;

    protected $product;

    protected $productView;

    /**
     * Initializing various
     * required repositories
     * and classes.
     *
     * @param Mixed $cart
     * @param Mixed $cartItem
     * @param Mixed $customer
     * @param Mixed $product
     * @param Mixed $productImage
     * @param Mixed $productView
     */
    public function __construct(
        CartRepository $cart,
        CartItemRepository $cartItem,
        CustomerRepository $customer,
        ProductRepository $product,
        ProductImage $productImage,
        ProductView $productView)
        {

        $this->middleware('customer')->except(['add', 'remove', 'test']);

        $this->customer = $customer;

        $this->cart = $cart;

        $this->cartItem = $cartItem;

        $this->product = $product;

        $this->productImage = $productImage;

        $this->productView = $productView;

        $this->_config = request('_config');
    }

    /**
     * Function for guests
     * user to add the product
     * in the cart.
     *
     * @return Mixed
     */

    public function add($id) {

        session()->forget('cart');
        return redirect()->back();

        $data = request()->input();

        if(!isset($data['is_configurable']) || !isset($data['product']) ||!isset($data['quantity'])) {
            session()->flash('error', 'Cart System Integrity Violation');

            return redirect()->back();
        } else {
            //handle the accidental case
            //when some one deleted
            //form fields from the DOM
            if($data['is_configurable']) {
                if(!isset($data['super_attributes'])) {
                    session()->flash('error', 'Cart System Integrity Violation');

                    return redirect()->back();
                }
            }
        }

        if(isset($data['is_configurable']) && $data['is_configurable']) {
            $data['price'] = $this->product->findOneByField('id', $data['selected_configurable_option'])->price;

        } else {
            $data['price'] = $this->product->findOneByField('id', $data['product'])->price;
        }

        Cart::add($id, $data);

        return redirect()->back();
    }

    public function remove($id) {

        if(auth()->guard('customer')->check()) {
            Cart::remove($id);
        } else {
            Cart::guestUnitRemove($id);
        }

        return redirect()->back();
    }

    /**
     * Method to populate
     * the cart page which
     * will be populated
     * before the checkout
     * process.
     *
     * @return Mixed
     */
    public function beforeCheckout() {
        if(auth()->guard('customer')->check()) {
            $cart = $this->cart->findOneByField('customer_id', auth()->guard('customer')->user()->id);

            if(isset($cart)) {
                $cart = $this->cart->findOneByField('id', 144);

                $cartItems = $this->cart->items($cart['id']);

                $products = array();

                foreach($cartItems as $cartItem) {
                    $image = $this->productImage->getGalleryImages($cartItem->product);

                    if(isset($image[0]['small_image_url'])) {
                        $products[$cartItem->product->id] = [$cartItem->product->name, $cartItem->price, $image[0]['small_image_url'], $cartItem->quantity];
                    }
                    else {
                        $products[$cartItem->product->id] = [$cartItem->product->name, $cartItem->price, 'null', $cartItem->quantity];
                    }

                }
            }
        } else {
            if(session()->has('cart')) {
                $cart = session()->get('cart');

                if(isset($cart)) {
                    $cart = $this->cart->findOneByField('id', 144);

                    $cartItems = $this->cart->items($cart['id']);

                    $products = array();

                    foreach($cartItems as $cartItem) {
                        $image = $this->productImage->getGalleryImages($cartItem->product);

                        if(isset($image[0]['small_image_url'])) {
                            $products[$cartItem->product->id] = [$cartItem->product->name, $cartItem->price, $image[0]['small_image_url'], $cartItem->quantity];
                        }
                        else {
                            $products[$cartItem->product->id] = [$cartItem->product->name, $cartItem->price, 'null', $cartItem->quantity];
                        }

                    }
                }
            }
        }

        return view($this->_config['view'])->with('products', $products);
    }

    /**
     * This method will return
     * the quantities from
     * inventory sources whose
     * status are not false.
     *
     * @return Array
     */
    public function canAddOrUpdate() {
        $cart = $this->cart->findOneByField('id', 144);

        $items = $cart->items;

        $allProdQty = array();

        $allProdQty1 = array();

        $totalQty = 0;

        foreach($items as $item) {
            $inventories = $item->product->inventories;

            $inventory_sources = $item->product->inventory_sources;

            $totalQty = 0;
            foreach($inventory_sources as $inventory_source) {

                if($inventory_source->status!=0) {
                    foreach($inventories as $inventory) {
                        $totalQty = $totalQty + $inventory->qty;
                    }

                    array_push($allProdQty1, $totalQty);

                    $allProdQty[$item->product->id] = $totalQty;
                }

            }
        }

        dd($allProdQty);

        foreach ($items as $item) {
            $inventories = $item->product->inventory_sources->where('status', '=', '1');

            foreach($inventories as $inventory) {
                dump($inventory->status);
            }
        }
    }

    public function test() {
        $cart = $this->cart->findOneByField('id', 144);

        $cartItems = $this->cart->items($cart['id']);

        $products = array();

        foreach($cartItems as $cartItem) {
            $image = $this->productImage->getGalleryImages($cartItem->product);

            dump($cartItem->product);

            if(isset($image[0]['small_image_url'])) {
                $products[$cartItem->product->id] = [$cartItem->product->name, $cartItem->price, $image[0]['small_image_url'], $cartItem->quantity];
            }
            else {
                $products[$cartItem->product->id] = [$cartItem->product->name, $cartItem->price, 'null', $cartItem->quantity];
            }

        }

        dd($products);
    }
}