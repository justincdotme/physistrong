import {getters} from '../../../resources/js/store/modules/user';
import LoginLink from '../../../resources/js/components/LoginLink';
import * as types from "../../../resources/js/store/types";
import router from "../../../resources/js/http/routes";
import sinon from "sinon";
import Vuex from "vuex";
import {createLocalVue, shallowMount, mount} from "@vue/test-utils";
import expect from 'expect';
import VueRouter from 'vue-router'
const localVue = createLocalVue().use({
    Vuex,
    VueRouter
});

describe('LoginLink.vue', () => {

    let store, state, actions;

    beforeEach(() => {
        state = {
            loggedIn: true
        };

        store = new Vuex.Store({
            getters,
            state
        });
    });

    it('it shows logout link when logged in', () => {
        state.loggedIn = true;


        let wrapper = shallowMount(LoginLink, {
            store,
            localVue
        });

        expect(wrapper.find('#logout').exists()).toBeTruthy();
        expect(wrapper.find('#login').exists()).toBeFalsy();
    });

    it('it shows login link when logged out', () => {

        state.loggedIn = false;

        let wrapper = shallowMount(LoginLink, {
            store,
            localVue
        });

        expect(wrapper.find('#login').exists()).toBeTruthy();
        expect(wrapper.find('#logout').exists()).toBeFalsy();
    });

    it('clicking logout link should log user out', () => {
        actions = {
            [types.A_USER_LOGOUT]: sinon.stub(),
        };

        store = new Vuex.Store({
            getters,
            state,
            actions
        });
        let wrapper = mount(LoginLink, {
            store,
            localVue,
            router,
        });

        wrapper.find('#logout').trigger('click');

        expect(actions[types.A_USER_LOGOUT].calledOnce).toEqual(true);
    });
});