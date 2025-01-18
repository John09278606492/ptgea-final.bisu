<x-filament-panels::page>
    @if (!$studentInfo)
        <!-- Display message if no student found -->
        <p class="text-lg italic text-center text-gray-500">Student not found.</p>
    @else
        <!-- If studentInfo exists, display the UI -->
        <x-filament::tabs label="Content tabs">
            <x-filament::tabs.item
                :active="$activeTab === 'student info'"
                wire:click="$set('activeTab', 'student info')"
            >
                Student Info
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$activeTab === 'siblings'"
                wire:click="$set('activeTab', 'siblings')"
            >
                Siblings
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$activeTab === 'invoice'"
                wire:click="$set('activeTab', 'invoice')"
            >
                Invoice
            </x-filament::tabs.item>
        </x-filament::tabs>

        {{-- Conditionally Render Sections --}}
        @if ($activeTab === 'student info')
            <x-filament::section>
                <x-filament::fieldset>
                    <x-slot name="label">
                        Student IDN
                    </x-slot>
                    <span class="font-bold">{{ $studentInfo->studentidn }}</span>
                </x-filament::fieldset>
                <x-filament::fieldset>
                    <x-slot name="label">
                        First Name
                    </x-slot>
                    <span class="font-bold">{{ $studentInfo->firstname }}</span>
                </x-filament::fieldset>
                <x-filament::fieldset>
                    <x-slot name="label">
                        Middle Name
                    </x-slot>
                    <span class="font-bold">{{ $studentInfo->middlename }}</span>
                </x-filament::fieldset>
                <x-filament::fieldset>
                    <x-slot name="label">
                        Last Name
                    </x-slot>
                    <span class="font-bold">{{ $studentInfo->lastname }}</span>
                </x-filament::fieldset>
            </x-filament::section>
        @endif

        @if ($activeTab === 'siblings')
            <x-filament::section>
                @if ($studentInfo->siblings->isEmpty())
                    <p class="text-lg italic text-center text-gray-500">No siblings found.</p>
                @else
                    <table class="w-full mb-6 border border-collapse border-gray-300 dark:border-gray-600">
                        <thead>
                            <tr class="bg-gray-100 dark:bg-gray-700 dark:text-gray-300">
                                <th class="p-2 text-left border border-gray-300 dark:border-gray-600">First Name</th>
                                <th class="p-2 text-left border border-gray-300 dark:border-gray-600">Middle Name</th>
                                <th class="p-2 text-left border border-gray-300 dark:border-gray-600">Last Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($studentInfo->siblings as $siblingsInfo)
                                <tr class="dark:hover:bg-gray-700">
                                    <td class="p-2 border border-gray-300 dark:border-gray-600">{{ $siblingsInfo->stud->firstname }}</td>
                                    <td class="p-2 border border-gray-300 dark:border-gray-600">{{ $siblingsInfo->stud->middlename }}</td>
                                    <td class="p-2 border border-gray-300 dark:border-gray-600">{{ $siblingsInfo->stud->lastname }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </x-filament::section>
        @endif

        @if ($activeTab === 'invoice')
            @if (!$payments)
                <p class="text-lg italic text-center text-gray-500">No records found for this student.</p>
            @else
                @php
                    $totalCollections = $payments->collections->sum('amount');
                    $totalYearLevelPayments = $payments->yearlevelpayments->sum('amount');
                    $totalPays = $payments->pays->sum('amount');

                    $remainingBalance = ($totalCollections + $totalYearLevelPayments) - $totalPays;
                @endphp
                <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800 dark:text-gray-200">
                    <h1 class="mb-6 text-2xl font-bold text-right dark:text-white">INVOICE</h1>
                    <p class="mb-6 text-right text-.5xl dark:text-white">PTGEA</p>
                    <p class="mb-6 text-right text-.5xl dark:text-white">San Isidro, Calape Bohol</p>
                    <p class="mb-6 text-right text-.5xl dark:text-white">6328</p>
                    <p class="mb-6 text-right text-.5xl dark:text-white">0927 860 6492</p>

                    <div class="p-4 mb-6">
                        <h2 class="mb-4 text-xl font-semibold dark:text-white">Student Information</h2>
                        <div class="flex mb-2">
                            <span class="w-32">Name:</span>
                            <span><strong>{{ $payments->stud->firstname }} {{ $payments->stud->middlename }} {{ $payments->stud->lastname }}</strong></span>
                        </div>
                        <div class="flex mb-2">
                            <span class="w-32">Student ID:</span>
                            <span><strong>{{ $payments->stud->studentidn }}</strong></span>
                        </div>
                        <div class="flex mb-2">
                            <span class="w-32">College:</span>
                            <span><strong>{{ $payments->college->college }}</strong></span>
                        </div>
                        <div class="flex mb-2">
                            <span class="w-32">Program:</span>
                            <span><strong>{{ $payments->program->program }}</strong></span>
                        </div>
                        <div class="flex mb-2">
                            <span class="w-32">Year Level:</span>
                            <span><strong>{{ $payments->yearlevel->yearlevel }}</strong></span>
                        </div>
                        <div class="flex mb-2">
                            <span class="w-32">School Year:</span>
                            <span><strong>{{ $payments->schoolyear->schoolyear }}</strong></span>
                        </div>
                    </div>

                    <div class="p-4 mb-6">
                        <h2 class="mb-4 text-xl font-semibold text-left dark:text-white">Siblings Information</h2>
                        @if ($finalSib->isEmpty())
                            <p class="text-lg italic text-center text-gray-500">No siblings found enrolled in the same school year.</p>
                        @else
                            <table class="w-full mb-6 border border-collapse border-gray-300 dark:border-gray-600">
                                <thead>
                                    <tr class="bg-gray-100 dark:bg-gray-700 dark:text-gray-300">
                                        <th class="p-2 text-left border border-gray-300 dark:border-gray-600">Complete Name</th>
                                        <th class="p-2 text-left border border-gray-300 dark:border-gray-600">College</th>
                                        <th class="p-2 text-left border border-gray-300 dark:border-gray-600">Program</th>
                                        <th class="p-2 text-left border border-gray-300 dark:border-gray-600">Year Level</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($finalSib as $enrollment)
                                        <tr class="dark:hover:bg-gray-700">
                                            <td class="p-2 border border-gray-300 dark:border-gray-600">{{ $enrollment->stud->firstname }} {{ $enrollment->stud->middlename }} {{ $enrollment->stud->lastname }}</td>
                                            <td class="p-2 border border-gray-300 dark:border-gray-600">{{ $enrollment->stud->enrollments->college->college }}</td>
                                            <td class="p-2 border border-gray-300 dark:border-gray-600">{{ $enrollment->stud->enrollments->program->program }}</td>
                                            <td class="p-2 border border-gray-300 dark:border-gray-600">{{ $enrollment->stud->enrollments->yearlevel->yearlevel }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>

                    <div class="p-4 mb-6">
                        <h2 class="mb-4 text-xl font-semibold text-left dark:text-white">Year Level Fee Type</h2>
                        <table class="w-full mb-6 border border-collapse border-gray-300 dark:border-gray-600">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-700 dark:text-gray-300">
                                    <th class="p-2 text-left border border-gray-300 dark:border-gray-600">Description</th>
                                    <th class="p-2 text-right border border-gray-300 dark:border-gray-600">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($payments->yearlevelpayments as $fee)
                                    <tr class="dark:hover:bg-gray-700">
                                        <td class="p-2 border border-gray-300 dark:border-gray-600">{{ $fee->description }}</td>
                                        <td class="p-2 text-right border border-gray-300 dark:border-gray-600">₱{{ number_format($fee->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="p-2 text-center border border-gray-300 dark:border-gray-600 dark:text-gray-400">
                                            No year level fee type/s available.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="flex justify-end">
                            <div class="w-full p-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                <h2 class="text-xl font-bold text-right dark:text-gray-100">Total: ₱{{ number_format($payments->yearlevelpayments->sum('amount'), 2) }}</h2>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 mb-6">
                        <h2 class="mb-4 text-xl font-semibold text-left dark:text-white">School Year Fee Type</h2>
                        <table class="w-full mb-6 border border-collapse border-gray-300 dark:border-gray-600">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-700 dark:text-gray-300">
                                    <th class="p-2 text-left border border-gray-300 dark:border-gray-600">Description</th>
                                    <th class="p-2 text-right border border-gray-300 dark:border-gray-600">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($payments->collections as $fee)
                                    <tr class="dark:hover:bg-gray-700">
                                        <td class="p-2 border border-gray-300 dark:border-gray-600">{{ $fee->description }} - Semester {{ $fee->semester->semester }}</td>
                                        <td class="p-2 text-right border border-gray-300 dark:border-gray-600">₱{{ number_format($fee->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="p-2 text-center border border-gray-300 dark:border-gray-600 dark:text-gray-400">
                                            No school year fee type/s available.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="flex justify-end">
                            <div class="w-full p-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                <h2 class="text-xl font-bold text-right dark:text-gray-100">Total: ₱{{ number_format($payments->collections->sum('amount'), 2) }}</h2>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 mb-6">
                        <h2 class="mb-4 text-xl font-semibold text-left dark:text-white">Payment History</h2>
                        <table class="w-full mb-6 border border-collapse border-gray-300 dark:border-gray-600">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-700 dark:text-gray-300">
                                    <th class="p-2 border border-gray-300 dark:border-gray-600">Date</th>
                                    <th class="p-2 text-right border border-gray-300 dark:border-gray-600">Status</th>
                                    <th class="p-2 text-right border border-gray-300 dark:border-gray-600">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($payments->pays as $invoice)
                                    <tr class="dark:hover:bg-gray-700">
                                        <td class="p-2 border border-gray-300 dark:border-gray-600">{{ $invoice->created_at->format('M d, Y h:i a') }}</td>
                                        <td class="p-2 text-right border border-gray-300 dark:border-gray-600">
                                            <span class="px-2 py-1 rounded {{ $invoice->status === 'paid' ? 'bg-green-200 text-green-800 dark:bg-green-700 dark:text-green-100' : 'bg-red-200 text-red-800 dark:bg-red-700 dark:text-red-100' }}">
                                                {{ ucfirst($invoice->status) }}
                                            </span>
                                        </td>
                                        <td class="p-2 text-right border border-gray-300 dark:border-gray-600">₱{{ number_format($invoice->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="p-2 text-center border border-gray-300 dark:border-gray-600 dark:text-gray-400">
                                            No payment history available.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="flex justify-end">
                            <div class="w-full p-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                <h2 class="text-xl font-bold text-right dark:text-gray-100">Total: ₱{{ number_format($payments->pays->sum('amount'), 2) }}</h2>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 mb-6">
                        <div class="flex justify-end">
                            <div class="w-full p-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                <h2 class="text-2xl font-bold text-right dark:text-gray-100">Remaining Balance: ₱{{ number_format($remainingBalance, 2) }}</h2>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    @endif
</x-filament-panels::page>