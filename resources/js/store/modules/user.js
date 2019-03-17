import {route} from '../../src/vendor/tightenco/ziggy/Router';
import JsonApi from "../../src/JsonApi";
import router from '../../http/routes';
import * as types from '../types';
import axios from 'axios';

export const getDefaultState = () => {
    return {
        data: {
            id: '',
            firstName: '',
            lastName: '',
        },
        loggedIn: false
    }
};

export const state = getDefaultState();

export const actions = {
    [types.A_USER_LOGIN] ({commit}, {email, password}) {
        return new Promise((resolve, reject) => {
            axios.post(route('user.login').url(), {
                    email,
                    password
                }
            ).then((response) => {
                commit(types.M_USER_LOGGED_IN, response, {root: true});
                router.push('/workouts');
                resolve({
                    id: response.data.id
                });
            }).catch((error) => {
                commit(types.M_RESET_USER_STATE);
                reject(error);
            });
        })
    },
    [types.A_USER_LOGOUT] ({commit}, payload) {
        return new Promise((resolve, reject) => {
            axios.post(route('user.logout').url(), {}
            ).then((response) => {
                commit(types.M_USER_LOGGED_OUT, {}, {root: true});
                router.push('/login');
                resolve();
            }).catch((error) => {
                reject(error);
            });
        })
    },
    [types.A_AUTHENTICATE_USER] ({commit}, data) {
        return new Promise((resolve, reject) => {
            axios.get(route('user.from-token').url()).then((response) => {
                commit(types.M_USER_LOGGED_IN, response, {root: true});
                resolve({
                    id: response.data.id
                });
            }).catch((error) => {
                reject(error);
            });
        })
    },
};

export const mutations = {
    [types.M_USER_LOGGED_IN] (state, {data}) {
        let jpi = new JsonApi(data);
        let names = jpi.getAttributes();

        state.data.id = data.data.id;
        Object.assign(state.data, names);

        state.loggedIn = true;
    },
    [types.M_USER_LOGGED_OUT] (state, {data}) {
        Object.assign(state, getDefaultState())
    },
    [types.M_RESET_USER_STATE] (state) {
        Object.assign(state, getDefaultState())
    }
};

export const getters = {
    [types.GET_USER_LOGGED_IN] (state) {
        return state.loggedIn;
    }
};

export default {
    state,
    actions,
    mutations,
    getters,
}