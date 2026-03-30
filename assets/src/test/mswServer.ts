import { setupServer } from 'msw/node';
import { http, HttpResponse } from 'msw';

export const handlers = [
  http.post('http://localhost/wp-json/wp-ai-agent/v1/run', () =>
    HttpResponse.json({
      success: true,
      run_id:  'run_test123',
      rounds:  2,
      log:     ['[create_page] Page created: Test Page'],
      pages:   [{ post_id: 42, title: 'Test Page', slug: 'test-page', edit_url: 'http://localhost/wp-admin/post.php?post=42', acf_count: 0 }],
    })
  ),

  http.get('http://localhost/wp-json/wp-ai-agent/v1/status/:runId', () =>
    HttpResponse.json({
      log:      ['[create_page] Page created: Test Page'],
      finished: true,
      success:  true,
      pages:    [{ post_id: 42, title: 'Test Page', slug: 'test-page', edit_url: 'http://localhost/wp-admin/post.php?post=42', acf_count: 0 }],
      rounds:   2,
    })
  ),

  http.get('http://localhost/wp-json/wp-ai-agent/v1/acf-groups', () =>
    HttpResponse.json([
      { key: 'group_abc', title: 'Page Fields', fields: [{ key: 'field_1', name: 'hero_title', type: 'text', label: 'Hero Title' }] },
    ])
  ),

  http.get('http://localhost/wp-json/wp-ai-agent/v1/history', () =>
    HttpResponse.json([])
  ),
];

export const server = setupServer(...handlers);
