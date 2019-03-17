export default class JsonApi {

    constructor (data) {
        this.response = data;
    }

    /**
     * Get the resource attributes.
     * Used for resource collections.
     *
     * @returns {Array}
     */
    getMappedAttributes (strategy) {
        return  this.response.data.map(strategy)
    }

    /**
     * Get pagination data.
     *
     * @returns {{meta: *, links: *}}
     */
    getPaginationData () {
        return {
            links: this.response.links,
            meta: this.getMeta()
        };
    }

    /**
     * Get resource metadata.
     *
     * @returns {{}}
     */
    getMeta () {
        return this.response.meta;
    }

    /**
     * Get resource attributes.
     *
     * @returns {{}}
     */
    getAttributes () {
        return this.response.data.attributes;
    }

    /**
     * Get the ID of a resource.
     *
     * @returns {*}
     */
    getId () {
        return this.response.data.id;
    }
}