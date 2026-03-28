<form method="GET"
      action="{{ url()->current() }}"
      class="bg-white shadow rounded p-4 mt-6 mb-6">

    <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
        {{ $slot }}
    </div>

    <div class="flex justify-end mt-4 gap-2">
        <a href="{{ url()->current() }}"
           class="px-4 py-2 border rounded text-sm text-gray-700">
            Reset
        </a>

        <button type="submit"
                class="px-4 py-2 bg-blue-600 text-white rounded text-sm">
            Apply Filters
        </button>
    </div>
</form>