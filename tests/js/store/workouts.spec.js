import {mutations, actions, getters, getDefaultState} from '../../../resources/js/store/modules/workouts';
import {route} from '../../../resources/js/src/vendor/tightenco/ziggy/Router';
import {workoutsResponse} from '../../js/stubs/http/workoutCollection';
import * as types from '../../../resources/js/store/types';
import sinon from 'sinon'
import expect from 'expect';
import moxios from 'moxios'

describe ('testing workouts module', () => {
    describe('state', () => {
        it('resets default state', () => {
            let state = getDefaultState();

            expect(state).toEqual({
                data: [],
                pagination: {}
            });
        });
    });

    describe('actions', () => {
        beforeEach(function () {
            moxios.install();
        });

        afterEach(function () {
            moxios.uninstall()
        });

        it('populates workouts', () => {
            const commit = sinon.spy();

            moxios.stubRequest(route('workouts.index').url(), {
                status: 200,
                response: workoutsResponse
            });

            actions[types.A_POPULATE_WORKOUTS]({commit})
                .then((response) => {
                    expect(commit.args[0][0]).toEqual(types.M_WORKOUTS_LOADED);
                    expect(commit.args[0][1].data).toEqual(workoutsResponse);
            });
        });

        it('handles error while populating workouts', () => {
            const commit = sinon.spy();

            moxios.stubRequest(route('workouts.index').url(), {
                status: 500
            });

            actions[types.A_POPULATE_WORKOUTS]({commit})
                .then((response) => {
                    done(new Error("Test should have rejected promise."));
                }).catch((error) => {
                    expect(commit.args[0][0]).toEqual(types.M_RESET_WORKOUTS_STATE);
                });
        });

        it('resests workout state', () => {
            const commit = sinon.spy();

            actions[types.A_RESET_WORKOUT_STATE]({commit});

            expect(commit.args).toEqual([[types.M_RESET_WORKOUTS_STATE]]);
        });
    });

    describe('mutations', () => {
        it('adds workout to state', () => {
            const state = {data: []};

            mutations[types.M_WORKOUTS_LOADED] (state, {data: workoutsResponse});

            expect(state.data.length).toEqual(3);
        });

        it('can reset state when workouts fail to load', () => {
            const state = {data: workoutsResponse.data};

            mutations[types.M_RESET_WORKOUTS_STATE] (state, {});

            expect(state.data.length).toEqual(0);
            expect(Object.keys(state.pagination).length).toEqual(0);
        });
    });

    describe('getters', () => {
        it('gets workouts', () => {
            const state = {data: workoutsResponse.data};

            const output = getters[types.GET_WORKOUTS] (state);

            expect(output.length).toEqual(3);
        });

        it('gets pagination data', () => {
            const state = {pagination: workoutsResponse.links};

            const output = getters[types.GET_WORKOUT_PAGINATION] (state);

            expect(Object.keys(output).length).toEqual(4);
        });
    });
});
