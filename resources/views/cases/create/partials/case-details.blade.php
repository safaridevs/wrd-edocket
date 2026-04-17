<div class="mb-6">
    <label class="block text-sm font-medium text-gray-700 mb-2">Case Type *</label>
    <div class="space-y-2">
        <label class="flex items-center">
            <input type="radio" name="case_type" value="aggrieved" {{ old('case_type') == 'aggrieved' ? 'checked' : '' }} required class="mr-2">
            Aggrieved
        </label>
        <label class="flex items-center">
            <input type="radio" name="case_type" value="protested" {{ old('case_type') == 'protested' ? 'checked' : '' }} required class="mr-2">
            Protested
        </label>
        <label class="flex items-center">
            <input type="radio" name="case_type" value="compliance" {{ old('case_type') == 'compliance' ? 'checked' : '' }} required class="mr-2">
            Compliance Action
        </label>
    </div>
    @error('case_type')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div class="mb-6">
    <label for="caption" class="block text-sm font-medium text-gray-700">Caption *</label>
    <textarea name="caption" id="caption" rows="3" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm {{ $errors->has('caption') ? 'border-red-500' : '' }}">{{ old('caption') }}</textarea>
    @error('caption')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div class="mb-6">
    <label class="block text-sm font-medium text-gray-700 mb-3">Administrative Litigation Unit *</label>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <label class="flex items-start p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 {{ old('wrd_office', 'santa_fe') == 'albuquerque' ? 'border-blue-500 bg-blue-50' : 'border-gray-300' }}">
            <input type="radio" name="wrd_office" value="albuquerque" {{ old('wrd_office', 'santa_fe') == 'albuquerque' ? 'checked' : '' }} required class="mt-1 mr-3">
            <div class="flex-1">
                <div class="font-semibold text-gray-900">Albuquerque Office</div>
                <div class="text-sm text-gray-600 mt-1">
                    <div>5550 San Antonio Dr NE</div>
                    <div>Albuquerque, NM 87109</div>
                    <div class="mt-1">Phone: (505) 469-9662</div>
                </div>
            </div>
        </label>
        <label class="flex items-start p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 {{ old('wrd_office', 'santa_fe') == 'santa_fe' ? 'border-blue-500 bg-blue-50' : 'border-gray-300' }}">
            <input type="radio" name="wrd_office" value="santa_fe" {{ old('wrd_office', 'santa_fe') == 'santa_fe' ? 'checked' : '' }} required class="mt-1 mr-3">
            <div class="flex-1">
                <div class="font-semibold text-gray-900">Santa Fe Office</div>
                <div class="text-sm text-gray-600 mt-1">
                    <div>407 Galisteo St STE 102</div>
                    <div>Santa Fe, NM 87501</div>
                    <div class="mt-1">Phone: (505) 827-6120</div>
                </div>
            </div>
        </label>
    </div>
    @error('wrd_office')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
