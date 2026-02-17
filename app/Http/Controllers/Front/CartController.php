<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CartController extends Controller
{
    // Show cart page
    public function index()
    {
        $cart = session()->get('cart', []);
        return view('front.cart.index', compact('cart'));
    }

    // Add product to cart
    public function add(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $cart = session()->get('cart', []);

        if (isset($cart[$id])) {
            $cart[$id]['quantity']++;
        } else {
            $cart[$id] = [
                'name' => $product->name,
                'price' => $product->price,
                'image' => $product->image,
                'quantity' => 1,
            ];
        }

        session()->put('cart', $cart);

        // Keep users in the same area after add-to-cart (especially on mobile).
        $returnUrl = $request->query('return');
        $anchor = ltrim((string) $request->query('anchor', ''), '#');

        if ($returnUrl && (str_starts_with($returnUrl, url('/')) || str_starts_with($returnUrl, '/'))) {
            $target = $returnUrl;
            if ($anchor !== '') {
                $target .= '#' . $anchor;
            }

            return redirect()->to($target)->with('success', "{$product->name} added to cart!");
        }

        return redirect()->back()->with('success', "{$product->name} added to cart!");
    }

    // Remove item
    public function remove($id)
    {
        $cart = session()->get('cart', []);
        if (isset($cart[$id])) {
            unset($cart[$id]);
            session()->put('cart', $cart);
        }
        return redirect()->back()->with('success', 'Item removed from cart.');
    }

    // Checkout page
    public function checkout()
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('products.index')->with('error', 'Your cart is empty.');
        }

        return view('front.cart.checkout', compact('cart'));
    }

    // Place order
    public function placeOrder(Request $request)
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('products.index');
        }

        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string|max:20',
            'customer_address' => 'nullable|string|max:255',
        ]);

        $total = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);

        $order = Order::create(array_merge($data, [
            'total' => $total,
            'status' => 'pending',
            'user_id' => auth()->id(), // Optional: logged-in users
            'store_id' => 1, // Optional: default store
        ]));

        foreach ($cart as $id => $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $id,
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'subtotal' => $item['price'] * $item['quantity'],
            ]);
        }

        session()->forget('cart');

        return redirect()->route('cart.success', $order->id);
    }

    // Order success page
    public function success($id)
    {
        $order = Order::with('items.product')->findOrFail($id);
        return view('front.cart.success', compact('order'));
    }
}
