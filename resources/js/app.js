import './bootstrap';
import "../../node_modules/bulma/css/bulma.css";
import Alpine from 'alpinejs'
import flatpickr from "flatpickr";
import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';

window.flatpickr = flatpickr;
Alpine.directive('tooltip', (el, { expression }) => {
    tippy(el, { content: expression });
});
window.Alpine = Alpine


Alpine.start()
