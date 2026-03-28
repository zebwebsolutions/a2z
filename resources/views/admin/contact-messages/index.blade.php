@extends('admin.layouts.app')

@section('title', 'Contact Messages')

@section('content')
<div class="bg-white p-6 rounded-lg shadow">
    <h1 class="text-2xl font-bold mb-6">Contact Messages</h1>

    @if($messages->count())
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-100 border-b">
                        <th class="p-3 text-left">#</th>
                        <th class="p-3 text-left">Name</th>
                        <th class="p-3 text-left">Email</th>
                        <th class="p-3 text-left">Phone</th>
                        <th class="p-3 text-left">Message</th>
                        <th class="p-3 text-left">Date</th>
                        <th class="p-3 text-left">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($messages as $message)
                        <tr class="border-b align-top hover:bg-gray-50">
                            <td class="p-3">{{ $message->id }}</td>
                            <td class="p-3 font-medium">{{ $message->name }}</td>
                            <td class="p-3">{{ $message->email }}</td>
                            <td class="p-3">{{ $message->phone ?: '-' }}</td>
                            <td class="p-3 w-[28rem]">
                                <div class="group relative">
                                    <p class="break-words cursor-help text-gray-800" title="Hover to preview full message">
                                        {{ \Illuminate\Support\Str::limit($message->message, 110) }}
                                    </p>

                                    <div class="pointer-events-none invisible absolute left-0 top-full z-30 mt-2 w-[32rem] max-w-[70vw] rounded-md border border-gray-200 bg-gray-900 px-3 py-2 text-xs text-white opacity-0 shadow-lg transition group-hover:visible group-hover:opacity-100">
                                        <pre class="whitespace-pre-wrap break-words font-mono text-[11px] leading-5 overflow-x-auto">{{ $message->message }}</pre>
                                    </div>
                                </div>

                                <details class="mt-2 text-xs">
                                    <summary class="cursor-pointer text-blue-600 hover:text-blue-700">View full message</summary>
                                    <pre class="mt-2 whitespace-pre-wrap break-words rounded border border-gray-200 bg-gray-50 p-2 text-gray-700 text-xs leading-5 overflow-x-auto">{{ $message->message }}</pre>
                                </details>
                            </td>
                            <td class="p-3">{{ $message->created_at->format('d M, Y h:i A') }}</td>
                            <td class="p-3">
                                <form method="POST" action="{{ route('admin.contact-messages.destroy', $message) }}" onsubmit="return confirm('Delete this message?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $messages->links() }}
        </div>
    @else
        <p class="text-gray-600">No contact messages yet.</p>
    @endif
</div>
@endsection
