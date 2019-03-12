import { mount } from '@vue/test-utils'
import expect from 'expect';
import Workouts from '../../resources/js/components/Workouts';

describe('Workouts', () => {
    it('contains test', () => {
        let wrapper = mount(Workouts);

        console.log(wrapper.vm.foo);
        wrapper.setData({
            foo: 'baz'
        });
        console.log(wrapper.vm.foo);

        expect(
            wrapper.html()
        ).toContain('Workouts');

        expect(
            wrapper.html()
        ).not.toContain('Workoutssssss');

        console.log(
            Object.getOwnPropertyNames(wrapper).filter(function (p) {
                return typeof wrapper[p] === 'function';
            })
        );

    });

});