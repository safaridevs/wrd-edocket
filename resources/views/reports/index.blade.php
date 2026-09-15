<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Reports</h2>
                <p class="mt-1 text-sm text-gray-600">Monthly active case list and future reporting views.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="space-y-6">
                <section class="bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 px-6 py-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Monthly Active Case List</h3>
                                <p class="mt-1 text-sm text-gray-600">
                                    Cases active at any point from {{ $monthStart->format('F j, Y') }} through {{ $monthEnd->format('F j, Y') }}.
                                </p>
                            </div>

                            <div class="flex flex-col gap-3 lg:items-end">
                            <form method="GET" action="{{ route('reports.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                                <div>
                                    <label for="reportMonth" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Report Month</label>
                                    <input
                                        id="reportMonth"
                                        type="month"
                                        name="month"
                                        value="{{ $selectedMonth->format('Y-m') }}"
                                        class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                </div>
                                <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                    Run Report
                                </button>
                            </form>
                            <div class="flex gap-2">
                                <a
                                    href="{{ route('reports.active-cases.print', ['month' => $selectedMonth->format('Y-m')]) }}"
                                    target="_blank"
                                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                                >
                                    Printable HTML
                                </a>
                                <a
                                    href="{{ route('reports.active-cases.pdf', ['month' => $selectedMonth->format('Y-m')]) }}"
                                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                                >
                                    Download PDF
                                </a>
                            </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 border-b border-gray-200 bg-gray-50 px-6 py-4 sm:grid-cols-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total Cases</p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $summary['total'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Currently Active</p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $summary['active'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Hearing Unit Display Status</p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $summary['with_hu_display_status'] }}</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        @include('reports.partials.active-case-list-table')
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
