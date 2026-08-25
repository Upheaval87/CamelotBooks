import './bootstrap';
import './analytics';
import './global-search-modal';
import './todo';
import './todo-modal';
import './forgot-password';
import './verify-code';
import './new-password';
import './feedback';
import './favourites';
import './report-center';
import './permissions-console';
import './font-scale';
import './tx-export';
import './pos-mobile';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

Alpine.plugin(collapse);

window.Alpine = Alpine;

Alpine.start();
