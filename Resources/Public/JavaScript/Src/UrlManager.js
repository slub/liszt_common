class UrlManager {

    #mappings = [];
    #body = {};
    #url = new URL(location);

    get body() {
        this.updateMappings();
        return this.#body;
    }

    updateMappings() {
        for (let key in this.#mappings) {
            const value = this.#url.searchParams.get(key);
            const path = this.#mappings[key].split('.');
            if (value) {
                let cursor = this.#body;
                for (let pathSegment of path.slice(0, -1)) {
                    cursor[pathSegment] ??= {};
                    cursor = cursor[pathSegment];
                }
                cursor[path.slice(-1)[0]] = value;
            } else {
                this.#body = {};
            }
        }
    }

    registerMapping(mapping) {
        for (let key in mapping) {
            this.#mappings[key] = mapping[key];
        }
    }

    setParam(key, value) {
        if (value) {
            this.#url.searchParams.set(key, value);
        } else {
            this.#url.searchParams.delete(key);
        }
        window.history.pushState({}, '', this.#url.href);
    }
}
