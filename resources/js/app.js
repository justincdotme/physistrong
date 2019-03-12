require('./bootstrap');
window.Vue = require('vue');

import VueRouter from 'vue-router';
import routes from './routes';
import {store} from './store/store';

Vue.use(VueRouter);

const app = new Vue({
    el: '#app',
    router: new VueRouter(routes),
    store
});
