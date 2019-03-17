import LoginForm from '../../../resources/js/components/LoginForm';
import { mount, createLocalVue } from '@vue/test-utils'
import * as types from '../../../resources/js/store/types';
import expect from 'expect';
import sinon from 'sinon'
import Vuex from 'vuex'

const localVue = createLocalVue().use(Vuex);

describe('LoginForm.vue', () => {

    let store, actions;

    beforeEach(() => {
        actions = {
            [types.A_USER_LOGIN]: sinon.stub(),
        };
        store = new Vuex.Store({
            actions
        })
    });

    it('calls user login action when submit is clicked', () => {
        let wrapper = mount(LoginForm, {store, localVue});
        wrapper.find('#email').setValue('test@user.com');
        wrapper.find('#password').setValue('staging');

        wrapper.find('button').trigger('click');

        expect(actions[types.A_USER_LOGIN].calledOnce).toEqual(true);
        expect(actions[types.A_USER_LOGIN].args[0][1].email).toEqual('test@user.com');
        expect(actions[types.A_USER_LOGIN].args[0][1].password).toEqual('staging');

    });

    it('displays error message if login fails', () => {
        let wrapper = mount(LoginForm, {store, localVue});

        wrapper.setData({
            error: {
                hasError: true
            }
        });

        expect(wrapper.find('#login-error').isVisible()).toBeTruthy();
    });

    it('error message is hidden on initial render', () => {
        let wrapper = mount(LoginForm, {store, localVue});

        expect(wrapper.find('#login-error').isVisible()).toBeFalsy();
    });
});