<div class="mb-6">
    <label class="block text-sm font-medium text-gray-700 mb-2">OSE File Numbers</label>
    <div id="ose-numbers" class="space-y-2">
        <div class="flex gap-2 items-center flex-wrap">
            <div class="flex items-center gap-1">
                <select name="ose_numbers[0][basin_code_from]" class="border-gray-300 rounded-md text-sm">
                    <option value="">Select Basin</option>
                    @foreach($basinCodes as $code)
                        <option value="{{ $code->initial }}" {{ old('ose_numbers.0.basin_code_from') == $code->initial ? 'selected' : '' }}>{{ $code->initial }} - {{ $code->description }}</option>
                    @endforeach
                </select>
                <span class="text-sm">-</span>
                <input type="text" name="ose_numbers[0][file_no_from]" placeholder="12345" value="{{ old('ose_numbers.0.file_no_from') }}" class="border-gray-300 rounded-md w-20 text-sm">
            </div>
            <div id="to-section-0" class="flex items-center gap-1 {{ old('ose_numbers.0.file_no_to') ? '' : 'hidden' }}">
                <span class="text-sm text-gray-600">into</span>
                <select name="ose_numbers[0][basin_code_to]" class="border-gray-300 rounded-md text-sm">
                    <option value="">Select Basin</option>
                    @foreach($basinCodes as $code)
                        <option value="{{ $code->initial }}" {{ old('ose_numbers.0.basin_code_to') == $code->initial ? 'selected' : '' }}>{{ $code->initial }} - {{ $code->description }}</option>
                    @endforeach
                </select>
                <span class="text-sm">-</span>
                <input type="text" name="ose_numbers[0][file_no_to]" placeholder="12350" value="{{ old('ose_numbers.0.file_no_to') }}" class="border-gray-300 rounded-md w-20 text-sm">
                <button type="button" onclick="hideToSection(0)" class="text-red-600 text-xs ml-1">âœ•</button>
            </div>
            <button id="add-to-0" type="button" onclick="showToSection(0)" class="text-blue-600 text-xs {{ old('ose_numbers.0.file_no_to') ? 'hidden' : '' }}">+ Add Range</button>
        </div>
    </div>
    <button type="button" onclick="addOseNumber()" class="mt-2 text-blue-600 text-sm">+ Add Another</button>
</div>
