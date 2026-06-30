@php
    $captionText = trim((string) ($caption ?? ''));
    $captionWords = preg_split('/\s+/', $captionText, -1, PREG_SPLIT_NO_EMPTY);
    $captionLimit = 55;
    $captionIsLong = count($captionWords) > $captionLimit;
    $captionPreview = $captionIsLong
        ? implode(' ', array_slice($captionWords, 0, $captionLimit))
        : $captionText;
    $captionRemainder = $captionIsLong
        ? implode(' ', array_slice($captionWords, $captionLimit))
        : '';
@endphp

<p class="text-gray-900 mb-3">
    {{ $captionPreview }}@if($captionIsLong)<span data-caption-ellipsis>...</span><span class="hidden" data-caption-more> {{ $captionRemainder }}</span>
        <button type="button"
                class="ml-1 font-medium text-blue-600 hover:text-blue-800 focus:outline-none focus:underline"
                onclick="const parent = this.parentElement; parent.querySelector('[data-caption-more]').classList.remove('hidden'); parent.querySelector('[data-caption-ellipsis]').classList.add('hidden'); this.classList.add('hidden');">
            More
        </button>
    @endif
</p>
