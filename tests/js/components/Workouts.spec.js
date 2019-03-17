import Workouts from '../../../resources/js/components/Workouts';
import { mount, createLocalVue } from '@vue/test-utils'
import * as types from '../../../resources/js/store/types';
import expect from 'expect';
import sinon from 'sinon'
import Vuex from 'vuex'

const localVue = createLocalVue().use(Vuex);

describe('Workouts.vue', () => {

    let actions, store;

    beforeEach(() => {
        actions = {
            [types.A_POPULATE_WORKOUTS]: sinon.stub(),
            [types.A_RESET_WORKOUT_STATE]: sinon.stub()
        };
        store = new Vuex.Store({
            actions
        })
    });

    it('calls populate workouts action when mounted', () => {
        mount(Workouts, {store, localVue});

        expect(actions[types.A_POPULATE_WORKOUTS].calledOnce).toEqual(true);
    });

    it('calls reset workouts state action when destroyed', () => {
        mount(Workouts, {store, localVue}).destroy();

        expect(actions[types.A_RESET_WORKOUT_STATE].calledOnce).toEqual(true);
    });
});