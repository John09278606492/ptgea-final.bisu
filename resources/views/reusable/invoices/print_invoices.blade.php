<div style="width: 700px; margin: auto; font-family: 'Arial', sans-serif; font-size: 14px; color: #000;">
    <div style="padding: 20px; background-color: #fff; border: 1px solid #ddd; border-radius: 8px;">
        <h1 style="margin-bottom: 20px; font-size: 24px; font-weight: bold; text-align: center;">Payment History</h1>

        <table style="width: 100%; margin-bottom: 20px; border-collapse: collapse; font-size: 14px;">
            <thead>
                <tr style="background-color: #f5f5f5; text-align: left;">
                    <th style="padding: 10px; border: 1px solid #ddd;">Invoice #</th>
                    <th style="padding: 10px; border: 1px solid #ddd;">Date</th>
                    <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">Amount</th>
                    <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments->pays as $invoice)
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;">{{ $invoice->id }}</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">{{ $invoice->created_at->format('M d, Y h:i a') }}</td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: right;">{{ number_format($invoice->amount, 2) }}</td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: right;">
                            <span style="padding: 5px 10px; border-radius: 5px; color: {{ $invoice->status === 'paid' ? '#155724' : '#721c24' }}; background-color: {{ $invoice->status === 'paid' ? '#d4edda' : '#f8d7da' }};">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="padding: 10px; text-align: center; border: 1px solid #ddd; color: #999;">
                            No billing history available.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div style="text-align: right; margin-top: 20px; font-size: 18px; font-weight: bold;">
            Total in PHP: {{ number_format($payments->pays->sum('amount'), 2) }}
        </div>
    </div>
</div>

<style>
    /* For PDF rendering: Ensure uniform styles and prevent scaling issues */
    body {
        margin: 0;
        padding: 0;
        font-family: 'Arial', sans-serif;
    }

    /* Remove gaps between table cells */
    table {
        border-spacing: 0;
    }

    /* Ensure text aligns properly and grid lines are clear */
    th, td {
        border: 1px solid #ddd;
        padding: 10px;
    }
</style>
