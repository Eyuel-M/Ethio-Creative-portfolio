import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import { initSelects } from './select';
import { portfolioBuilder } from './portfolio-builder';

Alpine.plugin(focus);
window.Alpine = Alpine;
Alpine.data('portfolioBuilder', portfolioBuilder);
Alpine.start();
initSelects();
