@extends('layouts.app')

@section('content')

<div class="container mx-auto py-12 px-6">

    <h1 class="text-4xl font-extrabold mb-6 text-center">Contact Us</h1>

    <p class="text-gray-600 text-center max-w-2xl mx-auto mb-8">
        Have questions, need support, or want to inquire about a product or repair service?
        We’re here to help you every step of the way.
    </p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">

        {{-- Contact Info --}}
        <div class="space-y-8">

            {{-- Contact Details --}}

            <div class="p-6 border rounded-xl shadow-sm bg-white">
                <h2 class="text-2xl font-bold mb-4">Get in Touch</h2>

                <div class="space-y-4">

                    <div class="flex items-start gap-3">
                        <div class="text-blue-600 text-2xl">📞</div>
                        <div>
                            <p class="font-medium">Phone</p>
                            <p class="text-gray-600">+965 1234 5678</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="text-blue-600 text-2xl">✉️</div>
                        <div>
                            <p class="font-medium">Email</p>
                            <p class="text-gray-600">support@a2z.com</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="text-blue-600 text-2xl">📍</div>
                        <div>
                            <p class="font-medium">Address</p>
                            <p class="text-gray-600">Kuwait City, Kuwait</p>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Business Hours --}}
            <div class="p-6 border rounded-xl shadow-sm bg-white">
                <h2 class="text-2xl font-bold mb-4">Business Hours</h2>

                <ul class="text-gray-700 space-y-2">
                    <li class="flex justify-between"><span>Monday – Friday</span> <span>9:00 AM – 9:00 PM</span></li>
                    <li class="flex justify-between"><span>Saturday</span> <span>10:00 AM – 8:00 PM</span></li>
                    <li class="flex justify-between"><span>Sunday</span> <span>Closed</span></li>
                </ul>
            </div>

            {{-- WhatsApp --}}
            <a href="https://wa.me/96512345678"
               target="_blank"
               class="block text-center bg-green-600 hover:bg-green-700 text-white py-3 rounded-xl text-lg font-semibold shadow">
                💬 Message Us on WhatsApp
            </a>
        </div>

        {{-- Contact Form --}}
        <div class="p-6 border rounded-xl shadow-sm bg-white">

            <h2 class="text-2xl font-bold mb-6">Send Us a Message</h2>

            <form method="POST" action="#">
                @csrf

                <div class="mb-4">
                    <label class="block font-medium mb-1">Your Name</label>
                    <input type="text" class="w-full border p-3 rounded-lg" required>
                </div>

                <div class="mb-4">
                    <label class="block font-medium mb-1">Email Address</label>
                    <input type="email" class="w-full border p-3 rounded-lg" required>
                </div>

                <div class="mb-4">
                    <label class="block font-medium mb-1">Phone Number</label>
                    <input type="text" class="w-full border p-3 rounded-lg">
                </div>

                <div class="mb-4">
                    <label class="block font-medium mb-1">Message</label>
                    <textarea class="w-full border p-3 rounded-lg h-32" required></textarea>
                </div>

                <button class="bg-blue-600 hover:bg-blue-700 text-white py-3 px-6 rounded-xl font-semibold w-full">
                    Send Message
                </button>

            </form>

        </div>

    </div>

    {{-- Map --}}
    <div class="mt-12">
        <iframe
            class="w-full h-72 rounded-xl shadow"
            src="https://www.google.com/maps/embed?pb=!1m18..."
            allowfullscreen=""
            loading="lazy">
        </iframe>
    </div>

</div>

@endsection