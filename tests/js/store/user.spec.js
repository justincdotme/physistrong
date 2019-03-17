import {store} from '../../../resources/js/store/store';
import {mutations, actions, getters, getDefaultState} from '../../../resources/js/store/modules/user';
import {route} from '../../../resources/js/src/vendor/tightenco/ziggy/Router';
import {userResponse} from '../../js/stubs/http/userResponse';
import * as types from '../../../resources/js/store/types';
import sinon from 'sinon'
import expect from 'expect';
import moxios from 'moxios'

describe ('testing users module', () => {
    describe('state', () => {
        it('resets default state', () => {
            let state = getDefaultState();

            expect(state).toEqual({
                data: {
                    id: '',
                    firstName: '',
                    lastName: '',
                },
                loggedIn: false
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

        it('user can login', () => {
            const commit = sinon.spy();

            moxios.stubRequest(route('user.login').url(), {
                status: 200,
                response: userResponse
            });

            actions[types.A_USER_LOGIN]({commit}, {email: 'foo@bar.com', password: 'abc123'})
                .then((response) => {
                    expect(commit.args[0][0]).toEqual(types.M_USER_LOGGED_IN);
                    expect(commit.args[0][1].data).toEqual(userResponse);
                });
        });

        it('handles error while logging in', () => {
            const commit = sinon.spy();

            moxios.stubRequest(route('user.login').url(), {
                status: 401
            });

            actions[types.A_USER_LOGIN]({commit}, {email: 'foo@bar.com', password: 'abc123'})
                .then((response) => {
                    done(new Error("Test should have rejected promise."));
                }).catch((error) => {
                    expect(commit.args[0][0]).toEqual(types.M_RESET_USER_STATE);
            });
        });

        it('user can log out', () => {
            const commit = sinon.spy();

            moxios.stubRequest(route('user.logout').url(), {
                status: 200,
                response: userResponse
            });

            actions[types.A_USER_LOGOUT]({commit}, {})
                .then((response) => {
                    expect(commit.args[0][0]).toEqual(types.M_USER_LOGGED_OUT);
                });
        });

        it('handles error while logging out', () => {
            const commit = sinon.spy();

            moxios.stubRequest(route('user.logout').url(), {
                status: 401
            });

            actions[types.A_USER_LOGOUT]({commit}, {})
                .then((response) => {
                    done(new Error("Test should have rejected promise."));
                }).catch((error) => {
                    expect(commit.args.length).toEqual(0);
            });
        });

        it('can authenticate user by token in cookie', () => {
            const commit = sinon.spy();

            moxios.stubRequest(route('user.from-token').url(), {
                status: 200,
                response: userResponse
            });

            actions[types.A_USER_LOGIN]({commit}, {})
                .then((response) => {
                    expect(commit.args[0][0]).toEqual(types.M_USER_LOGGED_IN);
                });
        });

        it('handles missing token cookie', () => {
            const commit = sinon.spy();

            moxios.stubRequest(route('user.from-token').url(), {
                status: 401
            });

            actions[types.A_USER_LOGOUT]({commit}, {})
                .then((response) => {
                    done(new Error("Test should have rejected promise."));
                }).catch((error) => {
                    expect(commit.args.length).toEqual(0);
            });
        });
    });

    describe('mutations', () => {
        it('sets user state', () => {
            const state = {data: []};

            mutations[types.M_RESET_USER_STATE] (state, {data: userResponse});

            expect(Object.keys(state.data).length).toEqual(3);
            expect(state.data.id).toEqual('');
            expect(state.data.firstName).toEqual('');
            expect(state.data.lastName).toEqual('');
        });

        it('can reset state when user logs out', () => {
            const state = {data: userResponse.data};

            mutations[types.M_USER_LOGGED_OUT] (state, {});

            expect(Object.keys(state.data).length).toEqual(3);
            expect(state.data.id).toEqual('');
            expect(state.data.firstName).toEqual('');
            expect(state.data.lastName).toEqual('');
        });

        it('can reset state when login fails', () => {
            const state = {data: userResponse.data};

            mutations[types.M_RESET_USER_STATE] (state, {});

            expect(Object.keys(state.data).length).toEqual(3);
            expect(state.data.id).toEqual('');
            expect(state.data.firstName).toEqual('');
            expect(state.data.lastName).toEqual('');
        });
    });

    describe('getters', () => {
        it('gets user logged in state', () => {
            const state = getDefaultState();
            state.loggedIn = true;

            const output = getters[types.GET_USER_LOGGED_IN] (state);

            expect(output).toBeTruthy();
        });

        it('gets user logged out state', () => {
            const state = getDefaultState();

            const output = getters[types.GET_USER_LOGGED_IN] (state);

            expect(output).toBeFalsy();
        });
    });
});
