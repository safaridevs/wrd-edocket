<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Case Index List</h2>
                <p class="mt-1 text-sm text-gray-600">Hearing Number {{ $case->case_no }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('reports.index') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">Back to Reports</a>
                <a href="{{ route('reports.case-documents.print', ['case_id' => $case->id]) }}" target="_blank" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">Printable HTML</a>
                <a href="{{ route('reports.case-documents.pdf', ['case_id' => $case->id]) }}" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Download PDF</a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <section class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="border-b border-gray-200 px-6 py-5">
                    <h3 class="text-lg font-semibold text-gray-900">Hearing Number {{ $case->case_no }}</h3>
                </div>
                <div class="overflow-x-auto">
                    @include('reports.partials.case-document-list-table')
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
