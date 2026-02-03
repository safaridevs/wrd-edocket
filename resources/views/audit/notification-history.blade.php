<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Email Notification History') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Filters -->
            <div class="bg-white shadow rounded-lg p-6">
                <form method="GET" class="flex space-x-4">
                    <input type="hidden" name="group_by" value="{{ $groupBy }}">
                    <input type="text" name="case_id" placeholder="Case ID" value="{{ request('case_id') }}" 
                           class="border-gray-300 rounded-md text-sm flex-1">
                    <input type="email" name="email" placeholder="Email Address" value="{{ request('email') }}" 
                           class="border-gray-300 rounded-md text-sm flex-1">
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-600">
                        Filter
                    </button>
                    <a href="{{ route('audit.notifications') }}?group_by={{ $groupBy }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm hover:bg-gray-400">
                        Clear
                    </a>
                </form>
            </div>

            <!-- Tabs -->
            <div class="bg-white shadow rounded-lg">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px">
                        <a href="?group_by=case{{ request('case_id') ? '&case_id='.request('case_id') : '' }}{{ request('email') ? '&email='.request('email') : '' }}" 
                           class="px-6 py-3 text-sm font-medium {{ $groupBy === 'case' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            By Case
                        </a>
                        <a href="?group_by=type{{ request('case_id') ? '&case_id='.request('case_id') : '' }}{{ request('email') ? '&email='.request('email') : '' }}" 
                           class="px-6 py-3 text-sm font-medium {{ $groupBy === 'type' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            By Type
                        </a>
                        <a href="?group_by=date{{ request('case_id') ? '&case_id='.request('case_id') : '' }}{{ request('email') ? '&email='.request('email') : '' }}" 
                           class="px-6 py-3 text-sm font-medium {{ $groupBy === 'date' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            By Date
                        </a>
                        <a href="?group_by=recipient{{ request('case_id') ? '&case_id='.request('case_id') : '' }}{{ request('email') ? '&email='.request('email') : '' }}" 
                           class="px-6 py-3 text-sm font-medium {{ $groupBy === 'recipient' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            By Recipient
                        </a>
                    </nav>
                </div>

                <!-- Grouped Content -->
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Notifications ({{ $notifications->count() }} total)</h3>
                    
                    @forelse($grouped as $groupKey => $groupNotifications)
                        <div class="mb-6 border rounded-lg">
                            <!-- Group Header -->
                            <div class="bg-gray-50 px-4 py-3 border-b">
                                <div class="flex justify-between items-center">
                                    <h4 class="font-medium text-gray-900">
                                        @if($groupBy === 'case')
                                            @php
                                                $case = $groupNotifications->first()->case;
                                            @endphp
                                            {{ $case ? $case->case_no : 'No Case' }}
                                        @elseif($groupBy === 'type')
                                            {{ ucfirst(str_replace('_', ' ', $groupKey)) }}
                                        @elseif($groupBy === 'date')
                                            {{ \Carbon\Carbon::parse($groupKey)->format('l, F j, Y') }}
                                        @else
                                            {{ $groupKey }}
                                        @endif
                                    </h4>
                                    <span class="text-sm text-gray-600">{{ $groupNotifications->count() }} notifications</span>
                                </div>
                            </div>
                            
                            <!-- Group Items -->
                            <div class="divide-y">
                                @foreach($groupNotifications as $notification)
                                <div class="px-4 py-3 hover:bg-gray-50">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-3">
                                                <span class="text-sm font-medium text-gray-900">
                                                    {{ $notification->sent_at->format('M d, Y H:i') }}
                                                </span>
                                                @if($groupBy !== 'case' && $notification->case)
                                                    <a href="{{ route('cases.show', $notification->case) }}" class="text-sm text-blue-600 hover:text-blue-800">
                                                        {{ $notification->case->case_no }}
                                                    </a>
                                                @endif
                                                @if($groupBy !== 'type')
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                        {{ ucfirst(str_replace('_', ' ', $notification->notification_type)) }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="mt-1 text-sm text-gray-600">
                                                <strong>To:</strong> {{ $notification->payload_json['email'] ?? '-' }}
                                            </div>
                                            <div class="mt-1 text-sm text-gray-700">
                                                {{ $notification->payload_json['title'] ?? '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500">
                            No notifications found
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>