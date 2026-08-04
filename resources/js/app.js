import './bootstrap';
import './analytics';
import './scoped-search-field';
import './global-search-modal';
import './todo';
import './favourites';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

Alpine.plugin(collapse);

window.Alpine = Alpine;

Alpine.start();
