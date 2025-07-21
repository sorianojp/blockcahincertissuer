<x-guest-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Verify Certificate') }}
        </h2>
    </x-slot>

    <div class="max-w-2xl mx-auto mt-8 p-6 bg-white shadow rounded">
        <h2 class="text-xl font-bold mb-4">Certificate Verification</h2>

        @if ($status === 'valid')
            <p class="text-green-600 font-semibold">✅ This certificate is valid and untampered.</p>
        @elseif ($status === 'revoked')
            <p class="text-yellow-600 font-semibold">⚠️ This certificate has been revoked.</p>
        @elseif ($status === 'tampered')
            <p class="text-red-600 font-semibold">❌ Certificate has been tampered with or hash mismatch.</p>
        @else
            <p class="text-gray-600">Unable to verify certificate. {{ $error ?? '' }}</p>
        @endif

        <div class="mt-4 space-y-2">
            <p><strong>Name:</strong> {{ $certificate->full_name }}</p>
            <p><strong>Program:</strong> {{ $certificate->program }}</p>
            <p><strong>Date Awarded:</strong> {{ $certificate->date_awarded }}</p>
            <p>
                <strong>IPFS Metadata:</strong>
                <a href="{{ str_replace('ipfs://', 'https://ipfs.io/ipfs/', $certificate->metadata_uri) }}"
                    target="_blank" class="text-blue-600 hover:underline">
                    View JSON
                </a>
                {{-- Instead of $cert, use $certificate --}}
                <a href="https://amoy.polygonscan.com/tx/{{ $certificate->tx_hash }}" target="_blank">
                    🔗 View on Polygonscan
                </a>

            </p>
        </div>
    </div>
</x-guest-layout>
