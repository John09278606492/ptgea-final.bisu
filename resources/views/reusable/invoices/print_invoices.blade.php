<style>
    @page {
        size: 350px auto; /* Set the width to match your container width */
        margin: 0; /* Remove default margins for a snug fit */
    }
    body {
        margin: 0;
        padding: 0;
    }
</style>
@php
    $totalCollections = $payments->collections->sum('amount');
    $totalYearLevelPayments = $payments->yearlevelpayments->sum('amount');
    $totalPays = $payments->pays->sum('amount');
    $remainingBalance = ($totalCollections + $totalYearLevelPayments) - $totalPays;
@endphp
<div style="width: 350px; margin: 0 auto; padding: 0.5rem; background-color: #fff; border-radius: 0.5rem; color: #333; font-family: DejaVu Sans; font-size: 11px;">
    <h1 style="margin-bottom: 0.5rem; font-size: 16px; font-weight: bold; text-align: center; color: #000; line-height: 1.2;">INVOICE</h1>
    <p style="margin-bottom: 0.25rem; text-align: center; font-size: 10px; line-height: 0.5;">PTGEA</p>
    <p style="margin-bottom: 0.25rem; text-align: center; font-size: 10px; line-height: 0.5;">San Isidro, Calape Bohol</p>
    <p style="margin-bottom: 0.25rem; text-align: center; font-size: 10px; line-height: 0.5;">6328</p>
    <p style="margin-bottom: 0.25rem; text-align: center; font-size: 10px; line-height: 0.5;">0927 860 6492</p>

    <div style="padding: 0.10rem 0.75rem; line-height: .80;">
        <h2 style="margin-bottom: 0.5rem; font-size: 15px; font-weight: bold;">Student Information</h2>
        <div style="margin-bottom: 0.25rem; display: flex;">
            <span style="width: 110px; font-weight: bold;">Name:</span>
            <span>{{ $payments->stud->firstname }} {{ $payments->stud->middlename }} {{ $payments->stud->lastname }}</span>
        </div>
        <div style="margin-bottom: 0.25rem; display: flex;">
            <span style="width: 110px; font-weight: bold;">Student ID:</span>
            <span>{{ $payments->stud->studentidn }}</span>
        </div>
        <div style="margin-bottom: 0.25rem; display: flex;">
            <span style="width: 110px; font-weight: bold;">College:</span>
            <span>{{ $payments->college->college }}</span>
        </div>
        <div style="margin-bottom: 0.25rem; display: flex;">
            <span style="width: 110px; font-weight: bold;">Program:</span>
            <span>{{ $payments->program->program }}</span>
        </div>
        <div style="margin-bottom: 0.25rem; display: flex;">
            <span style="width: 110px; font-weight: bold;">Year Level:</span>
            <span>{{ $payments->yearlevel->yearlevel }}</span>
        </div>
        <div style="margin-bottom: 0rem; display: flex;">
            <span style="width: 110px; font-weight: bold;">School Year:</span>
            <span>{{ $payments->schoolyear->schoolyear }}</span>
        </div>
    </div>

    <div style="padding: 0.10rem 0.75rem; margin-bottom: 0rem; line-height: .80;">
        <h2 style="margin-bottom: 0.5rem; font-size: 15px; font-weight: bold;">Siblings Information</h2>
        <table style="width: 100%; margin-bottom: 0rem; border-collapse: collapse; border: 1px solid #ccc; font-size: 11px;">
            <thead>
                <tr style="background-color: #f9f9f9;">
                    <th style="padding: 0.25rem; text-align: left; border: 1px solid #ccc;">Complete Name</th>
                    <th style="padding: 0.25rem; text-align: left; border: 1px solid #ccc;">College</th>
                    <th style="padding: 0.25rem; text-align: left; border: 1px solid #ccc;">Program</th>
                    <th style="padding: 0.25rem; text-align: left; border: 1px solid #ccc;">Year Level</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($siblingsInformation->siblings as $siblingsInfo)
                    <tr>
                        <td style="padding: 0.25rem; border: 1px solid #ccc;">{{ $siblingsInfo->stud->firstname }} {{ $siblingsInfo->stud->middlename }} {{ $siblingsInfo->stud->lastname }}</td>
                        <td style="padding: 0.25rem; border: 1px solid #ccc;">{{ $siblingsInfo->stud->enrollments->college->college }}</td>
                        <td style="padding: 0.25rem; border: 1px solid #ccc;">{{ $siblingsInfo->stud->enrollments->program->program }}</td>
                        <td style="padding: 0.25rem; border: 1px solid #ccc;">{{ $siblingsInfo->stud->enrollments->yearlevel->yearlevel }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="padding: 0rem; text-align: center; border: 1px solid #ccc;">No sibling/s found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="padding: 0.10rem 0.75rem; line-height: .80;">
        <h2 style="margin-bottom: 0.5rem; font-size: 15px; font-weight: bold;">Year Level Fee Type</h2>
        <table style="width: 100%; border-collapse: collapse; border: 1px solid #ccc; font-size: 11px;">
            <thead>
                <tr style="background-color: #f9f9f9;">
                    <th style="padding: 0.25rem; text-align: left; border: 1px solid #ccc;">Description</th>
                    <th style="padding: 0.25rem; text-align: right; border: 1px solid #ccc;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments->yearlevelpayments as $fee)
                    <tr>
                        <td style="padding: 0.25rem; border: 1px solid #ccc;">{{ $fee->description }}</td>
                        <td style="padding: 0.25rem; text-align: right; border: 1px solid #ccc;">₱{{ number_format($fee->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" style="padding: 0.25rem; text-align: center; border: 1px solid #ccc;">No year level fee type/s available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div style="text-align: right; margin-top: 0rem; font-size: 12px; font-weight: bold;">Total: ₱{{ number_format($payments->yearlevelpayments->sum('amount'), 2) }}</div>
    </div>

    <div style="padding: 0.10rem 0.75rem; line-height: .80;">
        <h2 style="margin-bottom: 0.5rem; font-size: 15px; font-weight: bold;">School Year Fee Type</h2>
        <table style="width: 100%; border-collapse: collapse; border: 1px solid #ccc; font-size: 11px;">
            <thead>
                <tr style="background-color: #f9f9f9;">
                    <th style="padding: 0.25rem; text-align: left; border: 1px solid #ccc;">Description</th>
                    <th style="padding: 0.25rem; text-align: right; border: 1px solid #ccc;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments->collections as $fee)
                    <tr>
                        <td style="padding: 0.25rem; border: 1px solid #ccc;">{{ $fee->description }} - Semester {{ $fee->semester->semester }}</td>
                        <td style="padding: 0.25rem; text-align: right; border: 1px solid #ccc;">₱{{ number_format($fee->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" style="padding: 0.25rem; text-align: center; border: 1px solid #ccc;">No school year fee type/s available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div style="text-align: right; margin-top: 0rem; font-size: 12px; font-weight: bold;">Total: ₱{{ number_format($payments->collections->sum('amount'), 2) }}</div>
    </div>

    <div style="padding: 0.10rem 0.75rem; line-height: .80;">
        <h2 style="margin-bottom: 0.5rem; font-size: 15px; font-weight: bold;">Payment History</h2>
        <table style="width: 100%; margin-bottom: 0.75rem; border-collapse: collapse; border: 1px solid #ccc; font-size: 11px;">
            <thead>
                <tr style="background-color: #f9f9f9;">
                    <th style="padding: 0.25rem; text-align: left; border: 1px solid #ccc;">Date</th>
                    <th style="padding: 0.25rem; text-align: right; border: 1px solid #ccc;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments->pays as $pay)
                    <tr>
                        <td style="padding: 0.25rem; border: 1px solid #ccc;">{{ $pay->created_at->format('Y-m-d') }}</td>
                        <td style="padding: 0.25rem; text-align: right; border: 1px solid #ccc;">₱{{ number_format($pay->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" style="padding: 0.25rem; text-align: center; border: 1px solid #ccc;">No payment history found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div style="text-align: right; margin-top: 0rem; font-size: 12px; font-weight: bold;">Total: ₱{{ number_format($payments->pays->sum('amount'), 2) }}</div>
    </div>

    <div style="padding: 0.5rem 0.75rem;">
        <div style="text-align: right; font-size: 14px; font-weight: bold;">Remaining Balance: ₱{{ number_format($remainingBalance, 2) }}</div>
    </div>
</div>
