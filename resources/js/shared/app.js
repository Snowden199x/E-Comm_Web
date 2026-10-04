import Alpine from 'alpinejs';
import ajax from '@imacrayon/alpine-ajax';
import './form-drafts.js';

window.Alpine = Alpine;

Alpine.plugin(ajax);
Alpine.start();
