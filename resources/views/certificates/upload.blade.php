<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>
    <div class="max-w-2xl mx-auto p-6 bg-white shadow rounded">
        <h2 class="text-xl font-bold mb-4">Upload Certificates CSV</h2>

        <form method="POST" action="{{ route('certificates.upload.process') }}" enctype="multipart/form-data">
            @csrf
            <input type="file" name="csv_file" accept=".csv" class="mb-4" required>
            <button class="bg-blue-600 text-white px-4 py-2 rounded" type="submit">Upload</button>
        </form>
    </div>
</x-app-layout>
