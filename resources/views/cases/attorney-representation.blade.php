@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Attorney Representation - {{ $case->case_no }}</h1>
            <a href="{{ route('cases.show', $case) }}" class="text-blue-600 hover:text-blue-800">← Back to Case</a>
        </div>

        <!-- Current Representations -->
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Current Attorney Representations</h2>
            
            @if($case->attorneyClientRelationships()->active()->count() > 0)
                <div class="space-y-4">
                    @foreach($case->attorneyClientRelationships()->active()->with(['attorney', 'client'])->get() as $relationship)
                    <div class="border rounded-lg p-4 bg-gray-50">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-medium text-gray-900">{{ $relationship->attorney->name }}</h3>
                                <p class="text-sm text-gray-600">{{ $relationship->attorney->law_firm ?? 'Independent Attorney' }}</p>
                                <p class="text-sm text-gray-600">Bar #: {{ $relationship->attorney->bar_number }}</p>
                                <p class="text-sm text-gray-500 mt-2">
                                    Representing: <span class="font-medium">{{ $relationship->client->name }}</span>
                                </p>
                                <p class="text-xs text-gray-500">Effective: {{ $relationship->effective_date->format('M j, Y') }}</p>
                            </div>
                            
                            @if(auth()->user()->canManageCase($case) || auth()->id() === $relationship->attorney_user_id)
                            <form method="POST" action="{{ route('attorney.terminate-representation', $relationship) }}" 
                                  onsubmit="return confirm('Are you sure you want to terminate this representation?')">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm">
                                    Terminate
                                </button>
                            </form>
                            @endif
                        </div>
                        
                        @if($relationship->notes)
                        <div class="mt-3 p-3 bg-blue-50 rounded">
                            <p class="text-sm text-blue-800">{{ $relationship->notes }}</p>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 italic">No active attorney representations for this case.</p>
            @endif
        </div>

        <!-- Add New Representation -->
        @if(auth()->user()->is_attorney || auth()->user()->canManageCase($case))
        <div class="border-t pt-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Add Attorney Representation</h2>
            
            <form method="POST" action="{{ route('attorney.add-client', $case) }}" class="space-y-4">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="client_person_id" class="block text-sm font-medium text-gray-700 mb-2">Client</label>
                        <select name="client_person_id" required class="w-full border-gray-300 rounded-md">
                            <option value="">Select client...</option>
                            @foreach($case->parties as $party)
                            <option value="{{ $party->person->id }}">
                                {{ $party->person->name }} ({{ ucfirst($party->role) }})
                            </option>
                            @endforeach
                        </select>
                        @error('client_person_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="effective_date" class="block text-sm font-medium text-gray-700 mb-2">Effective Date</label>
                        <input type="date" name="effective_date" value="{{ date('Y-m-d') }}" required 
                               class="w-full border-gray-300 rounded-md">
                        @error('effective_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                    <textarea name="notes" rows="3" class="w-full border-gray-300 rounded-md" 
                              placeholder="Any additional notes about this representation..."></textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Add Representation
                </button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection