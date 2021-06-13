import api from './api';


/**
 * Simple RESTful resource class
 */
class Resource {
    constructor(uri) {
        this.uri = uri;
    }

    list(query) {
        return api
        ({
            url: '/' + this.uri,
            method: 'get',
            params: query,
        });
    }

    get(id) {
        return api
        ({
            url: '/' + this.uri + '/' + id,
            method: 'get',
        });
    }

    store(resource) {
        return api
        ({
            url: '/' + this.uri,
            method: 'post',
            data: resource,
        });
    }

    update(id, resource) {
        return api
        ({
            url: '/' + this.uri + '/' + id,
            method: 'put',
            data: resource,
        });
    }

    destroy(id, forceDelete = false) {
        let url = '/' + this.uri + '/' + id;
        if (forceDelete) {
            url = '/' + this.uri + '/force-delete/' + id;
        }
        return api
        ({
            url: url,
            method: 'delete',
        });
    }

    //soft delete routes
    recycleBin() {
        return api
        ({
            url: '/' + this.uri + '/recycle-bin',
            method: 'get',
        });
    }

    restore(id) {
        return api
        ({
            url: '/' + this.uri + '/restore/' + id,
            method: 'get',
        });
    }

}

export {Resource as default};
