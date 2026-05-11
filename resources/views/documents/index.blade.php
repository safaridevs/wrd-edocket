<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Documents</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Document</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Case</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uploaded</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($documents as $document)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $document->custom_title ?: $document->doc_type_label }}</div>
                                    <div class="text-sm text-gray-500">{{ $document->original_filename }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($document->case)
                                        <a href="{{ route('cases.show', $document->case) }}" class="text-blue-600 hover:text-blue-900">{{ $document->case->case_no }}</a>
                                    @else
                                        <span class="text-gray-500">No case</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $document->stamped ? 'bg-blue-100 text-blue-800' : ($document->approved ? 'bg-green-100 text-green-800' : ($document->rejected_reason ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')) }}">
                                        {{ $document->stamped ? 'E-Stamped' : ($document->approved ? 'Accepted' : ($document->rejected_reason ? 'Rejected' : 'Pending')) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $document->uploaded_at?->format('M j, Y g:i A') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-3">
                                    <a href="{{ route('documents.preview', $document) }}" class="text-blue-600 hover:text-blue-900" target="_blank">Preview</a>
                                    <a href="{{ route('documents.download', $document) }}" class="text-purple-600 hover:text-purple-900">Download</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">No documents found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if($documents->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $documents->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
