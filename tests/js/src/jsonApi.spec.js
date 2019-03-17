import JsonApi from '../../../resources/js/src/JsonApi';
import {workoutsResponse} from "../stubs/http/workoutCollection";
import {userResponse} from '../stubs/http/userResponse';
import expect from 'expect';

describe('JsonApi', () => {
    it('if formats collection attributes', () => {
        const jsonApiInstance = new JsonApi(workoutsResponse);
        const mapStrategy = ({attributes, id}) => (
            {
                id: id,
                name: attributes.name,
                date_scheduled: attributes.date_scheduled
            }
        );

        const output = jsonApiInstance.getMappedAttributes(mapStrategy);

        expect(output.length).toEqual(3);
        expect(output[0]).toHaveProperty('name');
        expect(output[0]).toHaveProperty('date_scheduled');
    });

    it(' returns pagination links', () => {
        const jsonApiInstance = new JsonApi(workoutsResponse);

        const output = jsonApiInstance.getPaginationData();

        expect(output.meta.total).toEqual(3);
        expect(output.links.first).toEqual('http://api.physistrong.com/v1/workouts?page=1');
        expect(output.links.last).toEqual('http://api.physistrong.com/v1/workouts?page=1');
    });

    it(' returns metadata', () => {
        const jsonApiInstance = new JsonApi(workoutsResponse);

        const output = jsonApiInstance.getMeta();

        expect(output).toHaveProperty('current_page');
        expect(output).toHaveProperty('from');
        expect(output).toHaveProperty('last_page');
        expect(output).toHaveProperty('path');
        expect(output).toHaveProperty('per_page');
        expect(output).toHaveProperty('to');
        expect(output).toHaveProperty('total');
    });

    it(' returns attributes', () => {
        const jsonApiInstance = new JsonApi(userResponse);

        const output = jsonApiInstance.getAttributes();

        expect(output).toHaveProperty('first_name');
        expect(output).toHaveProperty('last_name');
    });

    it(' returns id', () => {
        const jsonApiInstance = new JsonApi(userResponse);

        const output = jsonApiInstance.getId();

        expect(output).toEqual("1");
    });
});