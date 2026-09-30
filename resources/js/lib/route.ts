type Method = 'get' | 'head' | 'post' | 'put' | 'patch' | 'delete';
type Query = Record<string, string | number | boolean | null | undefined>;
type Options = { query?: Query; mergeQuery?: Query };

// Explicit auth/settings URLs, maintained with the Laravel routes. No PHP generator is required.
export function route<M extends Method>(path: string, method: M) {
    const url = (options?: Options) => {
        const query = new URLSearchParams();
        Object.entries(options?.query ?? options?.mergeQuery ?? {}).forEach(
            ([key, value]) => {
                if (value != null) query.set(key, String(value));
            },
        );
        return path + (query.size ? `?${query.toString()}` : '');
    };
    return Object.assign(
        (options?: Options) => ({ url: url(options), method }),
        {
            url,
            form: (options?: Options) => ({ action: url(options), method }),
        },
    );
}
