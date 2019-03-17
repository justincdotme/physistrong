import Vue from 'vue';
import Vuex from 'vuex';
import VueCookies from 'vue-cookies';

import {actions} from './actions';
import {getters} from './getters';
import {mutations} from './mutations';

import workouts from './modules/workouts'
import workoutPreview from './modules/workoutPreview';
import user from './modules/user';


Vue.use(Vuex);
Vue.use(VueCookies);

export const store = new Vuex.Store({
    state: {
        error: {}
    },
    getters,
    mutations,
    actions,
    modules: {
        workouts,
        workoutPreview,
        user
    }
});