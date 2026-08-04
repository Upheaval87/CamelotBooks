export function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

/**
 * fetch() wrapper that always sends the CSRF token and parses JSON.
 * Resolves with the parsed body (or null on 204); rejects on non-2xx.
 */
export async function fetchJson(url, options) {
    options = options || {};
    options.headers = Object.assign({
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'Content-Type': 'application/json',
    }, options.headers || {});

    const res = await fetch(url, options);
    const text = await res.text();
    const body = text ? JSON.parse(text) : null;

    if (!res.ok) {
        const err = new Error((body && body.message) || ('Request failed: ' + res.status));
        err.status = res.status;
        err.body = body;
        throw err;
    }
    return body;
}
