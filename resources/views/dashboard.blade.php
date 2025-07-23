<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">{{ __('Dashboard') }}</h2>
    </x-slot>

    <x-auth-session-status :status="session('status')" class="mb-4" />
    @if (session('error'))
        <div class="bg-red-100 border-red-400 text-red-700 px-4 py-3 mb-4">{{ session('error') }}</div>
    @endif

    <div class="py-12">
        <div class="max-w-7xl mx-auto p-6 bg-white shadow rounded">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th>Name</th>
                        <th>Program</th>
                        <th>Date Awarded</th>
                        <th>Issue</th>
                        <th>On‑Chain</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($certificates as $cert)
                        <tr>
                            <td>{{ $cert->full_name }}</td>
                            <td>{{ $cert->program }}</td>
                            <td>{{ $cert->date_awarded }}</td>
                            <td>
                                @if ($cert->status !== 'issued')
                                    <form method="POST" action="{{ route('certificates.issue', $cert->id) }}">
                                        @csrf
                                        <button class="bg-green-600 text-white px-3 py-1 rounded">Issue</button>
                                    </form>
                                @else
                                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded">Issued</span>
                                @endif
                            </td>
                            <td class="space-x-2">
                                @if ($cert->status === 'issued')
                                    <a href="{{ config('services.polygonscan.base_url') }}/address/{{ config('services.polygonscan.contract') }}"
                                        class="bg-blue-600 text-white px-2 py-1 rounded">🔗 Verify on Chain</a>
                                    @if ($cert->tx_hash)
                                        <a href="{{ config('services.polygonscan.base_url') }}/tx/{{ $cert->tx_hash }}"
                                            class="bg-indigo-600 text-white px-2 py-1 rounded">🔍 View Tx</a>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-4">{{ $certificates->links() }}</div>
        </div>
    </div>
</x-app-layout>
