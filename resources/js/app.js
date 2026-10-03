// Alpine.js is bundled with Livewire 4 — register shared components on init.
document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // Gallery lightbox: x-data="lightbox(images)" where images = [{src, caption}]
    Alpine.data('lightbox', (images = []) => ({
        images,
        index: null,
        get current() {
            return this.index === null ? null : this.images[this.index];
        },
        open(i) {
            this.index = i;
            document.body.classList.add('overflow-hidden');
        },
        close() {
            this.index = null;
            document.body.classList.remove('overflow-hidden');
        },
        next() {
            this.index = (this.index + 1) % this.images.length;
        },
        prev() {
            this.index = (this.index - 1 + this.images.length) % this.images.length;
        },
    }));

    // Free-text tag input that submits as name[]: x-data="tagInput(['Design'])"
    Alpine.data('tagInput', (initial = []) => ({
        tags: initial,
        draft: '',
        add() {
            const value = this.draft.trim().replace(/,$/, '');
            if (value && !this.tags.includes(value) && this.tags.length < 15) {
                this.tags.push(value);
            }
            this.draft = '';
        },
        remove(i) {
            this.tags.splice(i, 1);
        },
    }));
});

// Confirm dangerous actions: <form data-confirm="Are you sure?">
document.addEventListener('submit', (event) => {
    const message = event.target?.dataset?.confirm;
    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});
