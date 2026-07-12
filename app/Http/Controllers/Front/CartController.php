<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Http\Helpers\PhoneNumber;
use App\Services\OrderReceiptService;
use App\Services\MetaCloudWhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    private const DELIVERY_CHARGE = 1.000;

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
            return redirect()->route('shop.index')->with('error', 'Your cart is empty.');
        }

        return view('front.cart.checkout', compact('cart'));
    }

    // Place order
    public function placeOrder(
        Request $request,
        OrderReceiptService $orderReceiptService,
        MetaCloudWhatsAppService $metaCloudWhatsAppService
    )
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('shop.index');
        }

        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'required|string|max:40',
            'customer_address' => 'nullable|string|max:255',
        ]);

        $normalizedPhone = PhoneNumber::normalizeKuwait($data['customer_phone'] ?? null);

        $data['customer_phone'] = isset($data['customer_phone']) ? trim($data['customer_phone']) : null;
        $data['customer_phone_e164'] = $normalizedPhone;

        $subtotal = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);
        $total = $subtotal + self::DELIVERY_CHARGE;

        $order = Order::create(array_merge($data, [
            'total' => $total,
            'payment_method' => 'cash',
            'status' => 'pending',
            'receipt_language' => 'en',
            'user_id' => $this->checkoutUserId(),
            'store_id' => auth()->user()?->store_id ?? 1,
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

        $pdfPath = $orderReceiptService->generate($order);
        if ($pdfPath) {
            $metaCloudWhatsAppService->sendReceipt($order, $pdfPath);
        }

        session()->forget('cart');

        return redirect()->route('cart.success', $order->id);
    }

    private function checkoutUserId(): int
    {
        if (auth()->id()) {
            return auth()->id();
        }

        $userId = User::query()
            ->where(function ($query) {
                $query->whereNull('is_active')->orWhere('is_active', true);
            })
            ->whereIn('role', ['admin', 'salesman'])
            ->orderBy('id')
            ->value('id') ?? User::query()->orderBy('id')->value('id');

        if (! $userId) {
            throw ValidationException::withMessages([
                'customer_name' => 'No staff user exists to assign this order. Please create an admin or salesman user first.',
            ]);
        }

        return (int) $userId;
    }

    // Order success page
    public function success($id)
    {
        $order = Order::with('items.product')->findOrFail($id);
        return view('front.cart.success', compact('order'));
    }
}
