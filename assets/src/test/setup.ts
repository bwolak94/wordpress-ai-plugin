import '@testing-library/jest-dom';
import { server } from './mswServer';

// jsdom doesn't implement scrollIntoView
Element.prototype.scrollIntoView = () => {};

beforeAll(() => server.listen({ onUnhandledRequest: 'error' }));
afterEach(() => server.resetHandlers());
afterAll(() => server.close());

Object.defineProperty(window, 'wpAiAgent', {
  value: {
    nonce:    'test-nonce',
    root:     'http://localhost/wp-json/',
    adminUrl: 'http://localhost/wp-admin/',
    version:  '1.0.0',
    userCaps: { edit_pages: true },
  },
});
