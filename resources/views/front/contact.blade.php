@extends('layouts.app')

@section('title', 'Contact A2Z Kuwait | Phones, Accessories & Repairs')
@section('meta_description', 'Contact A2Z Kuwait for product inquiries, repair bookings, WhatsApp support, and store location in Sharq, Kuwait.')

@section('content')

<div class="container mx-auto py-12 px-6">

    <h1 class="text-4xl font-extrabold mb-6 text-center">Contact Us</h1>

    <p class="text-gray-600 text-center max-w-2xl mx-auto mb-8">
        Have questions, need support, or want to inquire about a product or repair service?
        We are here to help you every step of the way.
    </p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">

        {{-- Contact Info --}}
        <div class="space-y-8">

            {{-- Contact Details --}}

            <div class="p-6 border rounded-xl shadow-sm bg-white">
                <h2 class="text-2xl font-bold mb-4">Get in Touch</h2>

                <div class="space-y-4">

                    <div class="flex items-start gap-3">
                        <i data-lucide="phone" class="w-6 h-6 text-blue-600 mt-0.5"></i>
                        <div>
                            <p class="font-medium">Phone</p>
                            <a href="tel:+96551523533" class="text-gray-600 block">+965 515 23533</a>
                            <a href="tel:+96597764165" class="text-gray-600 block">+965 977 64165</a>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <i data-lucide="mail" class="w-6 h-6 text-blue-600 mt-0.5"></i>
                        <div>
                            <p class="font-medium">Email</p>
                            <p class="text-gray-600">support@a2z.com</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <i data-lucide="map-pin" class="w-6 h-6 text-blue-600 mt-0.5"></i>
                        <div>
                            <p class="font-medium">Address</p>
                            <p class="text-gray-600">Khalid Bin Waleed Street, Block 6, Kazmi 10 Building, Shop 2, Sharq, Kuwait</p>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Business Hours --}}
            <div class="p-6 border rounded-xl shadow-sm bg-white">
                <h2 class="text-2xl font-bold mb-4">Business Hours</h2>

                <ul class="text-gray-700 space-y-2">
                    <li class="flex justify-between"><span>Monday - Friday</span> <span>9:00 AM - 9:00 PM</span></li>
                    <li class="flex justify-between"><span>Saturday</span> <span>10:00 AM - 8:00 PM</span></li>
                    <li class="flex justify-between"><span>Sunday</span> <span>Closed</span></li>
                </ul>
            </div>

            {{-- WhatsApp --}}
            <a href="https://wa.me/96597764165"
               target="_blank"
               class="flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white py-3 rounded-xl text-lg font-semibold shadow">
                <i data-lucide="message-circle" class="w-5 h-5"></i>
                <span>Message Us on WhatsApp</span>
            </a>
        </div>

        {{-- Contact Form --}}
        <div class="p-6 border rounded-xl shadow-sm bg-white">

            <h2 class="text-2xl font-bold mb-6">Send Us a Message</h2>

            @if (session('success'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('contact.send') }}">
                @csrf

                <div class="mb-4">
                    <label class="block font-medium mb-1" for="name">Your Name</label>
                    <input id="name" name="name" type="text" class="w-full border p-3 rounded-lg" value="{{ old('name') }}" required>
                    @error('name')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block font-medium mb-1" for="email">Email Address</label>
                    <input id="email" name="email" type="email" class="w-full border p-3 rounded-lg" value="{{ old('email') }}" required>
                    @error('email')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block font-medium mb-1" for="phone">Phone Number</label>
                    <input id="phone" name="phone" type="text" class="w-full border p-3 rounded-lg" value="{{ old('phone') }}">
                    @error('phone')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block font-medium mb-1" for="message">Message</label>
                    <textarea id="message" name="message" class="w-full border p-3 rounded-lg h-32" required>{{ old('message') }}</textarea>
                    @error('message')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button class="bg-blue-600 hover:bg-blue-700 text-white py-3 px-6 rounded-xl font-semibold w-full" type="submit">
                    Send Message
                </button>

            </form>

        </div>

    </div>

    {{-- Map --}}
    <div class="mt-12">
        <iframe
            class="w-full h-72 rounded-xl shadow"
            src="https://maps.google.com/maps?q=Khalid%20Bin%20Waleed%20Street%2C%20Block%206%2C%20Kazmi%2010%20Building%2C%20Shop%202%2C%20Sharq%2C%20Kuwait&z=18&output=embed"
            allowfullscreen=""
            loading="lazy">
        </iframe>
    </div>

</div>

@endsection
