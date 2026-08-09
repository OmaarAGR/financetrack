import { initFlowbite } from 'flowbite';
import './charts';

// Flowbite's dropdowns/modals/tooltips are wired via data-attributes and only
// auto-init on DOMContentLoaded. Livewire swaps the DOM on every navigation
// and on most component updates, so we re-run the initializer after both.
document.addEventListener('DOMContentLoaded', initFlowbite);
document.addEventListener('livewire:navigated', initFlowbite);
document.addEventListener('livewire:load', initFlowbite);
