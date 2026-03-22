/**
 * Helpers compartidos para tests de carga k6
 */

import http from 'k6/http';
import { BASE_URL, USERNAME, PASSWORD } from './k6-config.js';

/**
 * Autentica y devuelve el token Bearer de Sanctum.
 * Se llama una vez en setup() y se comparte entre VUs.
 */
export function authenticate() {
    const res = http.post(`${BASE_URL}/login`, JSON.stringify({
        email: USERNAME,
        password: PASSWORD,
    }), {
        headers: { 'Content-Type': 'application/json' },
    });

    if (res.status !== 200) {
        console.error(`Login failed: ${res.status} - ${res.body}`);
        return null;
    }

    const body = JSON.parse(res.body);
    // Intentar distintos formatos de respuesta
    const token = body.token || body.access_token || (body.data && body.data.token);

    if (!token) {
        console.error(`No token in response: ${res.body}`);
        return null;
    }

    return token;
}

/**
 * Genera headers con autenticación Bearer.
 */
export function authHeaders(token) {
    return {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`,
        },
    };
}

/**
 * GET autenticado.
 */
export function authGet(token, path, params) {
    const url = params ? `${BASE_URL}${path}?${params}` : `${BASE_URL}${path}`;
    return http.get(url, authHeaders(token));
}

/**
 * POST autenticado.
 */
export function authPost(token, path, body) {
    return http.post(
        `${BASE_URL}${path}`,
        JSON.stringify(body),
        authHeaders(token),
    );
}

/**
 * PUT autenticado.
 */
export function authPut(token, path, body) {
    return http.put(
        `${BASE_URL}${path}`,
        JSON.stringify(body),
        authHeaders(token),
    );
}
