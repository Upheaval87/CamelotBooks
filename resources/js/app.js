import './bootstrap';
import './analytics';
import './global-search-modal';
import './todo';
import './forgot-password';
import './verify-code';
import './new-password';
import './feedback';
import './favourites';
import './report-center';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

Alpine.plugin(collapse);

window.Alpine = Alpine;

Alpine.start();
