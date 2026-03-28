@extends('layouts.app')

@section('title', 'Privacy Policy | A to Z Electronics & Repairing')
@section('meta_description', 'Read the Privacy Policy of A to Z Electronics & Repairing (A2Z Kuwait) to understand how we collect, use, and protect customer data.')
@section('meta_robots', 'index,follow')

@section('content')
<div class="bg-gray-100 py-10">
    <div class="container mx-auto px-3 md:px-4 lg:px-6 mb-6">
        <nav class="text-sm text-gray-600 flex items-center gap-2">
            <a href="{{ url('/') }}" class="hover:text-blue-600">Home</a>
            <span>/</span>
            <span class="text-gray-800 font-semibold">Privacy Policy</span>
        </nav>
    </div>

    <div class="container mx-auto px-3 md:px-4 lg:px-6">
        <div class="bg-white p-8 rounded-xl shadow-sm mb-8 border">
            <h1 class="text-4xl font-extrabold tracking-tight mb-4">Privacy Policy</h1>
            <p class="text-gray-600 text-lg leading-relaxed">
                This Privacy Policy explains how <strong>A to Z Electronics &amp; Repairing</strong> (also known as <strong>A2Z Kuwait</strong>)
                collects, uses, shares, and protects personal information when you use our website, contact us, place orders,
                or request repair services.
            </p>
            <p class="text-gray-500 text-sm mt-3">Effective date: February 20, 2026</p>
        </div>

        <div class="bg-white p-8 rounded-xl shadow-sm border">
            <article class="prose max-w-none prose-headings:font-extrabold prose-headings:text-gray-900 prose-p:text-gray-700 prose-li:text-gray-700">
                <h2>1. Business Identity</h2>
                <p>
                    Legal business name: <strong>A to Z Electronics &amp; Repairing</strong>.
                    We operate stores including Life Style, Nada Phone, International Link, and A2Z under the same ownership in Kuwait.
                </p>

                <h2>2. Information We Collect</h2>
                <ul>
                    <li>Contact details such as name, phone number, and email address.</li>
                    <li>Order and repair details such as device model, issue description, and service history.</li>
                    <li>Technical information such as browser type, device type, pages visited, and approximate location.</li>
                    <li>Messages submitted through contact forms, WhatsApp, or customer support channels.</li>
                </ul>

                <h2>3. How We Use Information</h2>
                <ul>
                    <li>To process orders, service requests, and customer support inquiries.</li>
                    <li>To communicate updates, confirmations, and follow-up service details.</li>
                    <li>To improve website performance, product selection, and customer experience.</li>
                    <li>To prevent fraud, abuse, unauthorized access, and other harmful activity.</li>
                    <li>To comply with legal and regulatory obligations.</li>
                </ul>

                <h2>4. Marketing and Advertising</h2>
                <p>
                    We may use cookies, pixels, and analytics tools (including Meta and Google technologies) to measure traffic,
                    understand user behavior, and improve ads. These tools may collect browser/device identifiers and event data.
                </p>
                <p>
                    We do not sell personal information. We only use collected data for business operations, service improvement,
                    and lawful advertising performance measurement.
                </p>

                <h2>5. Sharing of Information</h2>
                <p>We may share limited data with trusted service providers such as:</p>
                <ul>
                    <li>Payment and order-processing providers.</li>
                    <li>Hosting, analytics, and website performance providers.</li>
                    <li>Customer communication tools (email, SMS, or WhatsApp support).</li>
                </ul>
                <p>
                    We may also disclose information if required by law, court order, or lawful government request.
                </p>

                <h2>6. Data Retention</h2>
                <p>
                    We keep information only as long as needed for order/repair processing, support, record keeping,
                    and legal compliance. When no longer needed, data is deleted or anonymized where reasonably possible.
                </p>

                <h2>7. Your Rights</h2>
                <p>Subject to applicable law, you may request to:</p>
                <ul>
                    <li>Access the personal data we hold about you.</li>
                    <li>Correct inaccurate or incomplete information.</li>
                    <li>Request deletion of your personal data.</li>
                    <li>Withdraw consent for marketing communications.</li>
                </ul>

                <h2>8. Data Security</h2>
                <p>
                    We use reasonable administrative and technical safeguards to protect personal information.
                    However, no online service can guarantee absolute security.
                </p>

                <h2>9. Contact Information</h2>
                <p>
                    If you have privacy questions, data requests, or complaints, contact us:
                </p>
                <ul>
                    <li>Business: A to Z Electronics &amp; Repairing</li>
                    <li>Address: Khalid Bin Waleed Street, Kazmi 10 Building, Shop 2, Sharq, Kuwait</li>
                    <li>Phone: +965 977 64165</li>
                    <li>Email: support@a2z.com</li>
                </ul>

                <h2>10. Policy Updates</h2>
                <p>
                    We may update this policy from time to time. Updates will be posted on this page with a revised effective date.
                </p>
            </article>
        </div>
    </div>
</div>
@endsection
