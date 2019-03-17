require('./bootstrap');
window.Vue = require('vue');
import router from './http/routes';
import {store} from './store/store';
import LoginLink from "./components/LoginLink";
import middleware from './http/middleware';


const app = new Vue({
    el: '#app',
    router,
    store,
    components: {
        LoginLink
    },
});