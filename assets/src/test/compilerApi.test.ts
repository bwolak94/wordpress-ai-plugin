import { describe, it, expect, beforeEach } from 'vitest';
import { http, HttpResponse } from 'msw';
import { server } from './mswServer';
import { compileHtml, getAcfStatus, getCompilerHistory } from '../api/compiler';

describe('Compiler API', () => {
  beforeEach(() => {
    server.use(
      http.post('http://localhost/wp-json/wp-ai-agent/v1/compile', () =>
        HttpResponse.json({
          success: true,
          sections: [{ name: 'hero' }],
          fields: [{ type: 'text' }],
          section_count: 1,
          field_count: 1,
          shared_count: 0,
          is_acf_pro: true,
          acf_version: '6.2.0',
          downgraded_fields: [],
          upgrade_notice: null,
          error: null,
        }),
      ),
      http.get('http://localhost/wp-json/wp-ai-agent/v1/acf-status', () =>
        HttpResponse.json({
          acf_active: true,
          acf_pro: true,
          acf_version: '6.2.0',
          field_types: ['text', 'repeater'],
        }),
      ),
      http.get('http://localhost/wp-json/wp-ai-agent/v1/compiler-history', () =>
        HttpResponse.json([
          { success: true, section_count: 2, field_count: 5, created_at: 1700000000 },
        ]),
      ),
    );
  });

  it('compileHtml sends POST and returns result', async () => {
    const result = await compileHtml({ html: '<div>test</div>', prefix: 'page_' });
    expect(result.success).toBe(true);
    expect(result.section_count).toBe(1);
  });

  it('getAcfStatus fetches ACF status', async () => {
    const status = await getAcfStatus();
    expect(status.acf_active).toBe(true);
    expect(status.acf_pro).toBe(true);
    expect(status.field_types).toContain('text');
  });

  it('getCompilerHistory fetches history', async () => {
    const history = await getCompilerHistory();
    expect(history).toHaveLength(1);
    expect(history[0].section_count).toBe(2);
  });
});
