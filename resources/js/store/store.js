import Vue from 'vue';
import Vuex from 'vuex';

import workout from './modules/workout'
import workoutPreview from './modules/workoutPreview'

Vue.use(Vuex);

export const store = new Vuex.Store({
    state: {
        value: 0
    },
    getters: {},
    mutations: {},
    actions: {},
    modules: {
        workout,
        workoutPreview
    }
});