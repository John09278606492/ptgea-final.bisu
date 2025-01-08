<x-filament-panels::page>
    <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800 dark:text-gray-200">
        <h1 class="mb-6 text-2xl font-bold dark:text-white">Payment History</h1>

        <table class="w-full mb-6 border border-collapse border-gray-300 dark:border-gray-600">
            <thead>
                <tr class="bg-gray-100 dark:bg-gray-700 dark:text-gray-300">
                    <th class="p-2 border border-gray-300 dark:border-gray-600">Invoice #</th>
                    <th class="p-2 border border-gray-300 dark:border-gray-600">Date</th>
                    <th class="p-2 text-right border border-gray-300 dark:border-gray-600">Amount</th>
                    <th class="p-2 text-right border border-gray-300 dark:border-gray-600">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments->pays as $invoice)
                    <tr class="dark:hover:bg-gray-700">
                        <td class="p-2 border border-gray-300 dark:border-gray-600">{{ $invoice->id }}</td>
                        <td class="p-2 border border-gray-300 dark:border-gray-600">{{ $invoice->created_at->format('M d, Y h:i a') }}</td>
                        <td class="p-2 text-right border border-gray-300 dark:border-gray-600">₱{{ number_format($invoice->amount, 2) }}</td>
                        <td class="p-2 text-right border border-gray-300 dark:border-gray-600">
                            <span class="px-2 py-1 rounded {{ $invoice->status === 'paid' ? 'bg-green-200 text-green-800 dark:bg-green-700 dark:text-green-100' : 'bg-red-200 text-red-800 dark:bg-red-700 dark:text-red-100' }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-2 text-center border border-gray-300 dark:border-gray-600 dark:text-gray-400">
                            No billing history available.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="flex justify-end">
            <div class="w-1/2 p-4 border border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                <h2 class="text-xl font-bold text-right dark:text-gray-100">Total: ₱{{ number_format($payments->pays->sum('amount'), 2) }}</h2>
            </div>
        </div>
    </div>
</x-filament-panels::page>
