import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import { initSelects } from './select';
import { portfolioBuilder } from './portfolio-builder';
import { initAnalytics } from './analytics';

Alpine.plugin(focus);
window.Alpine = Alpine;
Alpine.data('portfolioBuilder', portfolioBuilder);
Alpine.start();
initSelects();
initAnalytics();
