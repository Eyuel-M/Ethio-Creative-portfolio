import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import { initSelects } from './select';
import { portfolioBuilder } from './portfolio-builder';
import { postBuilder } from './post-builder';
import { initAnalytics } from './analytics';
import { shareCard } from './share-card';
import { initViewportFit } from './viewport-fit';

Alpine.plugin(focus);
window.Alpine = Alpine;
Alpine.data('portfolioBuilder', portfolioBuilder);
Alpine.data('postBuilder', postBuilder);
Alpine.data('shareCard', shareCard);
Alpine.start();
initSelects();
initAnalytics();
initViewportFit();
