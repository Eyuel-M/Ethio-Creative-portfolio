import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import { initSelects } from './select';

Alpine.plugin(focus);
window.Alpine = Alpine;
Alpine.start();
initSelects();
