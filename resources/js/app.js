import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const fileDropzoneInitialized = 'fileDropzoneInitialized';

function formatFileSize(bytes) {
    if (!Number.isFinite(bytes)) {
        return '';
    }

    if (bytes < 1024 * 1024) {
        return `${Math.max(1, Math.round(bytes / 1024))} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function fileMatchesAccept(file, accept) {
    if (!accept) {
        return true;
    }

    const rules = accept.split(',').map((rule) => rule.trim().toLowerCase()).filter(Boolean);
    if (!rules.length) {
        return true;
    }

    const fileName = file.name.toLowerCase();
    const fileType = file.type.toLowerCase();

    return rules.some((rule) => {
        if (rule.startsWith('.')) {
            return fileName.endsWith(rule);
        }

        if (rule.endsWith('/*')) {
            return fileType.startsWith(rule.slice(0, -1));
        }

        return fileType === rule;
    });
}

function setInputFiles(input, files) {
    const dataTransfer = new DataTransfer();

    files.forEach((file) => dataTransfer.items.add(file));
    input.files = dataTransfer.files;
    input.dispatchEvent(new Event('change', { bubbles: true }));
}

function enhanceFileInput(input) {
    if (!(input instanceof HTMLInputElement) || input.type !== 'file') {
        return;
    }

    if (input.dataset[fileDropzoneInitialized] === 'true' || input.classList.contains('hidden') || input.dataset.noDropzone === 'true') {
        return;
    }

    input.dataset[fileDropzoneInitialized] = 'true';

    const acceptsMultiple = input.multiple;
    const acceptedText = input.accept
        ? input.accept.split(',').map((item) => item.trim().toUpperCase().replace('.', '')).join(', ')
        : 'Any supported file';

    const wrapper = document.createElement('div');
    wrapper.className = 'space-y-2';

    const dropzone = document.createElement('div');
    dropzone.className = 'file-dropzone rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 px-4 py-5 text-center transition hover:border-blue-400 hover:bg-blue-50';
    dropzone.setAttribute('role', 'button');
    dropzone.setAttribute('tabindex', '0');

    const actionText = acceptsMultiple ? 'Choose files' : 'Choose file';
    dropzone.innerHTML = `
        <div class="flex flex-col items-center gap-2">
            <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 16V4m0 0 4 4m-4-4-4 4M4 16.5V18a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-1.5"></path>
            </svg>
            <div>
                <p class="text-sm font-medium text-gray-800"><span class="text-blue-700">${actionText}</span> or drag and drop here</p>
                <p class="mt-1 text-xs text-gray-500">${acceptedText}${acceptsMultiple ? ' files' : ' file'}</p>
            </div>
        </div>
    `;

    const message = document.createElement('p');
    message.className = 'hidden text-xs text-red-600';

    const list = document.createElement('div');
    list.className = 'hidden rounded-md border border-gray-200 bg-white text-sm text-gray-700';

    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);
    wrapper.appendChild(dropzone);
    wrapper.appendChild(message);
    wrapper.appendChild(list);

    input.classList.add('sr-only');

    const renderFiles = () => {
        const files = Array.from(input.files || []);
        list.innerHTML = '';
        list.classList.toggle('hidden', files.length === 0);

        if (!files.length) {
            return;
        }

        files.forEach((file, index) => {
            const row = document.createElement('div');
            row.className = 'flex items-center justify-between gap-3 border-b border-gray-100 px-3 py-2 last:border-b-0';

            const info = document.createElement('div');
            info.className = 'min-w-0';
            info.innerHTML = `
                <p class="truncate font-medium text-gray-800"></p>
                <p class="text-xs text-gray-500">${formatFileSize(file.size)}</p>
            `;
            info.querySelector('p').textContent = file.name;

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'shrink-0 rounded-md px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50';
            removeButton.textContent = 'Remove';
            removeButton.addEventListener('click', () => {
                const nextFiles = Array.from(input.files || []).filter((_, fileIndex) => fileIndex !== index);
                setInputFiles(input, nextFiles);
            });

            row.appendChild(info);
            row.appendChild(removeButton);
            list.appendChild(row);
        });
    };

    const showMessage = (text) => {
        message.textContent = text;
        message.classList.toggle('hidden', !text);
    };

    const addFiles = (incomingFiles) => {
        showMessage('');

        let files = Array.from(incomingFiles || []);
        const rejected = files.filter((file) => !fileMatchesAccept(file, input.accept));
        files = files.filter((file) => fileMatchesAccept(file, input.accept));

        if (rejected.length) {
            showMessage(`${rejected.length} file(s) did not match the allowed type.`);
        }

        if (!files.length) {
            return;
        }

        const currentFiles = acceptsMultiple ? Array.from(input.files || []) : [];
        const nextFiles = acceptsMultiple ? currentFiles.concat(files) : [files[0]];
        setInputFiles(input, nextFiles);
    };

    input.addEventListener('change', renderFiles);

    dropzone.addEventListener('click', () => input.click());
    dropzone.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            input.click();
        }
    });

    ['dragenter', 'dragover'].forEach((eventName) => {
        dropzone.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropzone.classList.add('border-blue-500', 'bg-blue-50');
        });
    });

    ['dragleave', 'drop'].forEach((eventName) => {
        dropzone.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropzone.classList.remove('border-blue-500', 'bg-blue-50');
        });
    });

    dropzone.addEventListener('drop', (event) => {
        addFiles(event.dataTransfer?.files);
    });

    renderFiles();
}

function enhanceFileInputs(root = document) {
    root.querySelectorAll?.('input[type="file"]').forEach(enhanceFileInput);
}

document.addEventListener('DOMContentLoaded', () => {
    enhanceFileInputs();

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node instanceof HTMLInputElement) {
                    enhanceFileInput(node);
                    return;
                }

                if (node instanceof Element) {
                    enhanceFileInputs(node);
                }
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
});
