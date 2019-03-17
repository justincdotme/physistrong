export default class StringUtil {

    /**
     * Map a snake_case string to camelCase.
     *
     * @param obj
     * @returns {{}}
     */
    mapToCamel (obj) {
        return window._.mapKeys(obj, function (value, key) {
            return window._.camelCase(key);
        });
    }
}