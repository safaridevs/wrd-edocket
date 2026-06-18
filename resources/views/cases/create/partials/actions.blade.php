<input type="hidden" name="action" id="caseActionInput" value="submit">

<div class="mb-6">
    <label class="flex items-center">
        <input type="checkbox" name="affirmation" {{ old('affirmation') ? 'checked' : '' }} required class="mr-2">
        <span class="text-sm">Information provided is complete and correct *</span>
    </label>
    @error('affirmation')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div class="flex justify-center gap-4">
    <button type="button" data-submit-action="draft" data-loading-text="Saving draft..." class="inline-flex items-center justify-center gap-2 bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md transition-colors">
        <span data-loading-spinner class="hidden h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
        <span data-button-label>Save Draft</span>
    </button>
    <button type="button" data-submit-action="submit" data-loading-text="Submitting to HU..." class="inline-flex items-center justify-center gap-2 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md transition-colors">
        <span data-loading-spinner class="hidden h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
        <span data-button-label>Submit to HU</span>
    </button>
    <a href="{{ route('cases.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md transition-colors">
        Cancel
    </a>
</div>
