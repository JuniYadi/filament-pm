import './bootstrap';
import Sort from '@alpinejs/sort';

document.addEventListener('alpine:init', () => {
    Alpine.plugin(Sort);
});
