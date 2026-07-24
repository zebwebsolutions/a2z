<?php

namespace App\Http\Controllers\Front;

use App\Mail\OrderConfirmationMail;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Http\Helpers\PhoneNumber;
use App\Services\OrderReceiptService;
use App\Services\MetaCloudWhatsAppService;
use App\Services\Products\ProductInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Throwable;

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

        if ($product->stock <= 0) {
            return redirect()->back()->with('error', "{$product->name} is currently out of stock.");
        }

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
        MetaCloudWhatsAppService $metaCloudWhatsAppService,
        ProductInventoryService $productInventoryService
    )
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('shop.index');
        }

        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:150',
            'customer_phone' => 'required|string|max:40',
            'customer_address' => 'nullable|string|max:255',
        ]);

        $normalizedPhone = PhoneNumber::normalizeKuwait($data['customer_phone'] ?? null);

        $data['customer_phone'] = isset($data['customer_phone']) ? trim($data['customer_phone']) : null;
        $data['customer_phone_e164'] = $normalizedPhone;

        $checkoutUserId = $this->checkoutUserId();
        $storeId = auth()->user()?->store_id
            ?? User::whereKey($checkoutUserId)->value('store_id')
            ?? 1;

        $order = DB::transaction(function () use (
            $cart,
            $data,
            $normalizedPhone,
            $checkoutUserId,
            $storeId,
            $productInventoryService
        ) {
            $resolvedItems = [];
            $subtotal = 0.0;

            foreach ($cart as $id => $cartItem) {
                $quantity = (int) ($cartItem['quantity'] ?? 0);
                if ($quantity < 1) {
                    throw ValidationException::withMessages([
                        'cart' => 'A cart item has an invalid quantity.',
                    ]);
                }

                $product = Product::whereKey($id)->lockForUpdate()->first();
                if (!$product) {
                    throw ValidationException::withMessages([
                        'cart' => 'A product in your cart is no longer available.',
                    ]);
                }

                if ($product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'cart' => "Only {$product->stock} unit(s) of {$product->name} are available.",
                    ]);
                }

                $price = (float) $product->price;
                $subtotal += $price * $quantity;
                $resolvedItems[] = compact('product', 'quantity', 'price');
            }

            $order = Order::create(array_merge($data, [
                'customer_phone_e164' => $normalizedPhone,
                'total' => $subtotal + self::DELIVERY_CHARGE,
                'payment_method' => 'cash',
                'order_source' => 'online',
                'status' => 'pending',
                'receipt_language' => 'en',
                'user_id' => $checkoutUserId,
                'store_id' => $storeId,
            ]));

            foreach ($resolvedItems as $item) {
                /** @var Product $product */
                $product = $item['product'];
                $quantity = $item['quantity'];

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $item['price'],
                ]);

                if ($product->tracks_inventory_by_unit) {
                    $units = $productInventoryService->reserveUnits($product, $quantity);
                    $orderItem->productUnits()->sync($units->pluck('id'));
                } else {
                    $product->decrement('stock', $quantity);
                }
            }

            return $order;
        });

        $order->load('items.product');

        try {
            Mail::to($order->customer_email)->send(new OrderConfirmationMail($order));
        } catch (Throwable $exception) {
            report($exception);
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
