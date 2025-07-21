<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>
    <x-auth-session-status class="mb-4" :status="session('status')" />
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @foreach ($certificates as $cert)
                        <tr>
                            <td>{{ $cert->full_name }}</td>
                            <td>{{ $cert->program }}</td>
                            <td>{{ $cert->date_awarded }}</td>
                            <td>
                                @if ($cert->status !== 'issued')
                                    <form method="POST" action="{{ route('certificates.issue', $cert->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-success">Issue</button>
                                    </form>
                                @else
                                    ✅ Issued
                                @endif
                            </td>
                            <td>
                                @if ($cert->status === 'issued')
                                    <a href="{{ config('services.polygonscan.base_url') }}/address/{{ config('services.polygonscan.contract') }}"
                                        target="_blank"
                                        class="inline-block px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
                                        🔗 Verify on Chain
                                    </a>
                                @endif

                            </td>
                        </tr>
                    @endforeach

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
