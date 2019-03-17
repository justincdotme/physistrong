import {mutations, actions, getters, getDefaultState} from '../../../resources/js/store/modules/workoutPreview';
import expect from 'expect';

describe ('testing workouts module', () => {
    describe('state', () => {
        it('resets default state', () => {
            let state = getDefaultState();

            expect(state).toEqual({
                data: {}
            });
        });
    });

    describe('actions', () => {

    });

    describe('mutations', () => {

    });

    describe('getters', () => {

    });
});
