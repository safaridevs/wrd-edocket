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
            
            @php
                $counselParties = $case->parties()->where('role', 'counsel')->with(['person', 'clientParty.person'])->get();
            @endphp
            @if($counselParties->count() > 0)
                <div class="space-y-4">
                    @foreach($counselParties as $relationship)
                    <div class="border rounded-lg p-4 bg-gray-50">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-medium text-gray-900">{{ $relationship->person->full_name }}</h3>
                                <p class="text-sm text-gray-500 mt-2">
                                    Representing: <span class="font-medium">{{ $relationship->clientParty?->person?->full_name ?? 'Unknown party' }}</span>
                                </p>
                            </div>
                            
                            @if(auth()->user()->canWriteCase())
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
                        
                    </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 italic">No active attorney representations for this case.</p>
            @endif
        </div>

        <!-- Add New Representation -->
        @if(auth()->user()->isAttorney() || auth()->user()->canWriteCase())
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
                            @if(!in_array($party->role, ['counsel', 'paralegal', 'agent']))
                            <option value="{{ $party->person->id }}">
                                {{ $party->person->full_name }} ({{ ucfirst($party->role) }})
                            </option>
                            @endif
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
