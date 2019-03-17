import {userResponse} from '../stubs/http/userResponse';
import StringUtil from '../../../resources/js/src/StringUtil';
import expect from 'expect';

describe('StringUtil', () => {
    it(' maps object keys to camel case', () => {
        const stringFormatter = new StringUtil();

        const output = stringFormatter.mapToCamel(userResponse.data.attributes);

        expect(Object.keys(output).length).toEqual(2);
        expect(output).toHaveProperty('firstName');
        expect(output).toHaveProperty('lastName');
    });
});