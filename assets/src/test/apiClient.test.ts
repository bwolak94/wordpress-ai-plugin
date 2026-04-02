import { describe, it, expect } from 'vitest';
import { http, HttpResponse } from 'msw';
import { server } from './mswServer';
import { api, ApiError } from '../api/client';

describe('ApiClient', () => {
  describe('get', () => {
    it('fetches JSON with nonce header', async () => {
      server.use(
        http.get('http://localhost/wp-json/test/get', ({ request }) => {
          expect(request.headers.get('X-WP-Nonce')).toBe('test-nonce');
          return HttpResponse.json({ ok: true });
        }),
      );

      const data = await api.get<{ ok: boolean }>('test/get');
      expect(data.ok).toBe(true);
    });

    it('throws ApiError on non-ok response', async () => {
      server.use(
        http.get('http://localhost/wp-json/test/fail', () =>
          HttpResponse.json({ error: 'Not found' }, { status: 404 }),
        ),
      );

      await expect(api.get('test/fail')).rejects.toThrow(ApiError);
      try {
        await api.get('test/fail');
      } catch (e) {
        expect(e).toBeInstanceOf(ApiError);
        expect((e as ApiError).status).toBe(404);
      }
    });

    it('handles non-JSON error response gracefully', async () => {
      server.use(
        http.get('http://localhost/wp-json/test/text-error', () =>
          new HttpResponse('Internal Server Error', { status: 500 }),
        ),
      );

      await expect(api.get('test/text-error')).rejects.toThrow(ApiError);
    });
  });

  describe('post', () => {
    it('posts JSON with correct headers', async () => {
      server.use(
        http.post('http://localhost/wp-json/test/post', async ({ request }) => {
          expect(request.headers.get('Content-Type')).toBe('application/json');
          expect(request.headers.get('X-WP-Nonce')).toBe('test-nonce');
          const body = await request.json();
          return HttpResponse.json({ received: body });
        }),
      );

      const data = await api.post<{ received: unknown }>('test/post', { foo: 'bar' });
      expect(data.received).toEqual({ foo: 'bar' });
    });

    it('throws ApiError on post failure', async () => {
      server.use(
        http.post('http://localhost/wp-json/test/post-fail', () =>
          HttpResponse.json({ message: 'Bad request' }, { status: 400 }),
        ),
      );

      await expect(api.post('test/post-fail', {})).rejects.toThrow(ApiError);
    });

    it('handles non-JSON error in post', async () => {
      server.use(
        http.post('http://localhost/wp-json/test/post-crash', () =>
          new HttpResponse('crash', { status: 500 }),
        ),
      );

      await expect(api.post('test/post-crash', {})).rejects.toThrow(ApiError);
    });
  });
});
