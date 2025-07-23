<x-guest-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">{{ __('Verify Certificate') }}</h2>
    </x-slot>

    <div class="max-w-2xl mx-auto mt-8 p-6 bg-white shadow rounded">
        <h2 class="text-xl font-bold mb-4">Certificate Verification</h2>

        @if ($status === 'valid')
            <p class="text-green-600">✅ Valid & untampered.</p>
        @elseif($status === 'revoked')
            <p class="text-yellow-600">⚠️ Revoked.</p>
        @elseif($status === 'tampered')
            <p class="text-red-600">❌ Tampered / hash mismatch.</p>
        @else
            <p class="text-gray-600">Error: {{ $error }}</p>
        @endif

        <div class="mt-4 space-y-2">
            <p><strong>Name:</strong> {{ $cert->full_name }}</p>
            <p><strong>Program:</strong> {{ $cert->program }}</p>
            <p><strong>Date Awarded:</strong> {{ $cert->date_awarded }}</p>
            <p>
                <strong>IPFS Metadata:</strong>
                <a href="{{ str_replace('ipfs://', 'https://ipfs.io/ipfs/', $cert->metadata_uri) }}"
                    class="text-blue-600 hover:underline" target="_blank">View JSON</a>
            </p>
        </div>
    </div>
</x-guest-layout>
