import './bootstrap';

// import hljs from 'highlight.js/lib/core';
// import sql from 'highlight.js/lib/languages/sql';

// // Register the SQL language
// hljs.registerLanguage('sql', sql);

// // Initialize Highlight.js for all code blocks
// document.addEventListener('DOMContentLoaded', () => {
//   document.querySelectorAll('pre code').forEach((block) => {
//       hljs.highlightElement(block);
//   });
// });

// Alpine.js is automatically handled by Livewire 3
// No need to import or initialize it manually

// Import the ecotox modal component
import './alpine-components/ecotox-modal.js';

// Register the component with Alpine (which is already available from Livewire)
document.addEventListener('DOMContentLoaded', () => {
    if (window.Alpine) {
        Alpine.data('ecotoxModal', window.ecotoxModal);
    }
});