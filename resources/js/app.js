import Alpine from 'alpinejs';

// Register the primary font files with Vite so Blade can <link rel="preload"> their built URLs.
import.meta.glob([
    '../../node_modules/@fontsource-variable/inter/files/inter-latin-wght-normal.woff2',
    '../../node_modules/@fontsource-variable/inter-tight/files/inter-tight-latin-wght-normal.woff2',
]);

window.Alpine = Alpine;
Alpine.start();
