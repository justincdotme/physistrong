import JsonApi from "../../src/JsonApi";
import * as types from '../types';
import axios from 'axios';
import {route} from '../../src/vendor/tightenco/ziggy/Router';

export const getDefaultState = () => {
    return {
        data: [],
        pagination: {}
    }
};

export const state = getDefaultState();

export const actions = {
    [types.A_POPULATE_WORKOUTS]: ({commit}) => {
        return new Promise((resolve, reject) => {
            axios.get(route('workouts.index').url()
            ).then((response) => {
                commit(types.M_WORKOUTS_LOADED, response, {root: true});
                resolve(response);
            }).catch((error) => {
                commit(types.M_RESET_WORKOUTS_STATE, error, {root: true});
                reject(error);
            });
        });
    },
    [types.A_RESET_WORKOUT_STATE]({commit}) {
        commit(types.M_RESET_WORKOUTS_STATE);
    }
};

export const mutations = {
    [types.M_WORKOUTS_LOADED] (state, {data}) {
        let jpi = new JsonApi(data);

        state.data = jpi.getMappedAttributes(({attributes, id}) => (
            {
                id: id,
                name: attributes.name,
                dateScheduled: attributes.date_scheduled
            }
        ));
        state.pagination = jpi.getPaginationData();
    },
    [types.M_RESET_WORKOUTS_STATE] (state, payload) {
        Object.assign(state, getDefaultState())
    }
};

export const getters = {
    [types.GET_WORKOUTS] (state) {
        return state.data;
    },
    [types.GET_WORKOUT_PAGINATION] (state) {
        return state.pagination;
    }
};

export default {
    state,
    actions,
    mutations,
    getters,
}