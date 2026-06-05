@if(auth()->user()->canAssignAttorneys())
<div class="mb-6">
    <label class="block text-sm font-medium text-gray-700 mb-2">Assign ALU Attorneys / WRD Contract Attorneys</label>
    <div class="border rounded-lg p-4 bg-gray-50">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach(\App\Models\User::whereAnyCurrentRole(['alu_atty', 'external_attorney'])->orderBy('name')->get() as $attorney)
            <label class="flex items-center p-2 border rounded hover:bg-white cursor-pointer">
                <input type="checkbox" name="assigned_attorneys[]" value="{{ $attorney->id }}"
                       {{ in_array($attorney->id, old('assigned_attorneys', [])) ? 'checked' : '' }}
                       class="mr-3">
                <div>
                    <div class="font-medium">{{ $attorney->name }}</div>
                    <div class="text-sm text-gray-600">{{ $attorney->email }} @if($attorney->isExternalAttorney()) <span class="text-xs text-indigo-600">(Contract)</span> @endif</div>
                </div>
            </label>
            @endforeach
        </div>
        <p class="text-xs text-gray-500 mt-2">Select attorneys representing WRD on this case. Contract attorneys stay limited to assigned cases.</p>
    </div>
    @error('assigned_attorneys')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div class="mb-6">
    <label class="block text-sm font-medium text-gray-700 mb-2">Assign ALU Clerks / Paralegals</label>
    <div class="border rounded-lg p-4 bg-gray-50">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach(\App\Models\User::whereAnyCurrentRole(['alu_clerk', 'alu_paralegal'])->orderBy('name')->get() as $clerk)
                <label class="flex items-center p-2 border rounded hover:bg-white cursor-pointer">
                    <input type="checkbox" name="assigned_clerks[]" value="{{ $clerk->id }}"
                        {{ in_array($clerk->id, old('assigned_clerks', [])) ? 'checked' : '' }}
                        class="mr-3">
                    <div>
                        <div class="font-medium">{{ $clerk->name }}</div>
                        <div class="text-sm text-gray-600">{{ $clerk->email }} @if($clerk->isALUParalegal()) <span class="text-xs text-indigo-600">(Paralegal)</span> @endif</div>
                    </div>
                </label>
            @endforeach
        </div>
        <p class="text-xs text-gray-500 mt-2">Select one or more ALU clerks or paralegals to assign to this case</p>
    </div>
    @error('assigned_clerks')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
@endif
