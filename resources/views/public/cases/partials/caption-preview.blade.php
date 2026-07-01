@php
    $captionText = trim((string) ($caption ?? ''));
    $captionLimit = 500;
    $captionLength = function_exists('mb_strlen') ? mb_strlen($captionText) : strlen($captionText);
    $captionIsLong = $captionLength > $captionLimit;
    $captionPreview = $captionIsLong
        ? (function_exists('mb_substr') ? mb_substr($captionText, 0, $captionLimit) : substr($captionText, 0, $captionLimit))
        : $captionText;
    $captionRemainder = $captionIsLong
        ? (function_exists('mb_substr') ? mb_substr($captionText, $captionLimit) : substr($captionText, $captionLimit))
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
