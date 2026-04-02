import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { CompilerPage } from '../pages/CompilerPage';
import { compileHtml, getAcfStatus, getCompilerHistory } from '../api/compiler';

vi.mock('../api/compiler', () => ({
  compileHtml: vi.fn(),
  getAcfStatus: vi.fn(),
  getCompilerHistory: vi.fn(),
}));

function createWrapper() {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: { retry: false },
      mutations: { retry: false },
    },
  });
  return function Wrapper({ children }: { children: React.ReactNode }) {
    return (
      <QueryClientProvider client={queryClient}>
        {children}
      </QueryClientProvider>
    );
  };
}

const ACF_PRO_STATUS = {
  acf_active: true,
  acf_pro: true,
  acf_version: '6.2.0',
  field_types: ['text', 'textarea', 'repeater'],
};

const COMPILE_RESULT = {
  success: true,
  sections: [{ name: 'hero', label: 'Hero', shared: false, fields: [{ type: 'text', key: 'field_hero_title' }] }],
  fields: [{ type: 'text', key: 'field_hero_title' }],
  section_count: 2,
  field_count: 8,
  shared_count: 1,
  is_acf_pro: true,
  acf_version: '6.2.0',
  downgraded_fields: [],
  upgrade_notice: null,
  error: null,
};

describe('CompilerPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(getAcfStatus).mockResolvedValue(ACF_PRO_STATUS);
    vi.mocked(getCompilerHistory).mockResolvedValue([]);
  });

  it('renders HTML input textarea', () => {
    render(<CompilerPage />, { wrapper: createWrapper() });
    expect(screen.getByPlaceholderText(/paste your static html here/i)).toBeInTheDocument();
  });

  it('renders compile button', () => {
    render(<CompilerPage />, { wrapper: createWrapper() });
    expect(screen.getByRole('button', { name: /compile html/i })).toBeInTheDocument();
  });

  it('compile button is disabled when HTML is empty', () => {
    render(<CompilerPage />, { wrapper: createWrapper() });
    expect(screen.getByRole('button', { name: /compile html/i })).toBeDisabled();
  });

  it('shows ACF not active warning', async () => {
    vi.mocked(getAcfStatus).mockResolvedValue({
      acf_active: false,
      acf_pro: false,
      acf_version: '0.0.0',
      field_types: [],
    });

    render(<CompilerPage />, { wrapper: createWrapper() });

    await waitFor(() => {
      expect(screen.getByText(/acf plugin is not active/i)).toBeInTheDocument();
    });
  });

  it('shows ACF Free warning when acf_pro is false', async () => {
    vi.mocked(getAcfStatus).mockResolvedValue({
      acf_active: true,
      acf_pro: false,
      acf_version: '5.12.0',
      field_types: ['text', 'textarea'],
    });

    render(<CompilerPage />, { wrapper: createWrapper() });

    await waitFor(() => {
      expect(screen.getByText(/acf free detected/i)).toBeInTheDocument();
    });
  });

  it('shows history panel title', () => {
    render(<CompilerPage />, { wrapper: createWrapper() });
    expect(screen.getByText(/compilation history/i)).toBeInTheDocument();
  });

  it('shows "No compilations yet" when history is empty', async () => {
    render(<CompilerPage />, { wrapper: createWrapper() });

    await waitFor(() => {
      expect(screen.getByText(/no compilations yet/i)).toBeInTheDocument();
    });
  });

  it('renders template and prefix inputs', () => {
    render(<CompilerPage />, { wrapper: createWrapper() });

    expect(screen.getByPlaceholderText(/paste a reference php template/i)).toBeInTheDocument();
    expect(screen.getByDisplayValue('page_')).toBeInTheDocument();
  });

  it('enables compile button after typing HTML', async () => {
    const user = userEvent.setup();
    render(<CompilerPage />, { wrapper: createWrapper() });

    const textarea = screen.getByPlaceholderText(/paste your static html here/i);
    await user.type(textarea, '<div>hello</div>');

    expect(screen.getByRole('button', { name: /compile html/i })).not.toBeDisabled();
  });

  it('shows results after successful compile', async () => {
    vi.mocked(compileHtml).mockResolvedValue(COMPILE_RESULT);
    const user = userEvent.setup();

    render(<CompilerPage />, { wrapper: createWrapper() });

    const textarea = screen.getByPlaceholderText(/paste your static html here/i);
    await user.type(textarea, '<section class="hero"><h1>Title</h1></section>');

    fireEvent.click(screen.getByRole('button', { name: /compile html/i }));

    await waitFor(() => {
      expect(screen.getByText('2')).toBeInTheDocument(); // section_count
      expect(screen.getByText('8')).toBeInTheDocument(); // field_count
    });

    // Result tabs
    expect(screen.getByRole('button', { name: /sections/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /acf json/i })).toBeInTheDocument();
  });

  it('shows downgraded fields warning when present', async () => {
    vi.mocked(compileHtml).mockResolvedValue({
      ...COMPILE_RESULT,
      is_acf_pro: false,
      downgraded_fields: [
        { original_type: 'repeater', fallback_type: 'group', field_key: 'field_items' },
      ],
    });
    const user = userEvent.setup();

    render(<CompilerPage />, { wrapper: createWrapper() });

    await user.type(screen.getByPlaceholderText(/paste your static html here/i), '<div>test</div>');
    fireEvent.click(screen.getByRole('button', { name: /compile html/i }));

    await waitFor(() => {
      expect(screen.getByText(/1 field\(s\) downgraded/i)).toBeInTheDocument();
      expect(screen.getByText(/field_items/)).toBeInTheDocument();
    });
  });

  it('shows error when compile fails', async () => {
    vi.mocked(compileHtml).mockResolvedValue({
      ...COMPILE_RESULT,
      success: false,
      error: 'Failed to parse AI response',
    });
    const user = userEvent.setup();

    render(<CompilerPage />, { wrapper: createWrapper() });

    await user.type(screen.getByPlaceholderText(/paste your static html here/i), '<div>test</div>');
    fireEvent.click(screen.getByRole('button', { name: /compile html/i }));

    await waitFor(() => {
      expect(screen.getByText(/failed to parse ai response/i)).toBeInTheDocument();
    });
  });

  it('shows ACF PRO badge when is_acf_pro is true in results', async () => {
    vi.mocked(compileHtml).mockResolvedValue(COMPILE_RESULT);
    const user = userEvent.setup();

    render(<CompilerPage />, { wrapper: createWrapper() });

    await user.type(screen.getByPlaceholderText(/paste your static html here/i), '<div>test</div>');
    fireEvent.click(screen.getByRole('button', { name: /compile html/i }));

    await waitFor(() => {
      expect(screen.getByText(/acf pro/i)).toBeInTheDocument();
    });
  });

  it('renders history items when history is available', async () => {
    vi.mocked(getCompilerHistory).mockResolvedValue([
      {
        success: true,
        sections: [],
        fields: [],
        section_count: 3,
        field_count: 12,
        shared_count: 1,
        is_acf_pro: true,
        acf_version: '6.2.0',
        downgraded_fields: [],
        upgrade_notice: null,
        error: null,
        prefix: 'page_',
        created_at: Math.floor(Date.now() / 1000),
      },
    ]);

    render(<CompilerPage />, { wrapper: createWrapper() });

    await waitFor(() => {
      expect(screen.getByText(/3 sections, 12 fields/)).toBeInTheDocument();
    });
  });

  it('shows compile error from rejected promise', async () => {
    vi.mocked(compileHtml).mockRejectedValue(new Error('Network failure'));
    const user = userEvent.setup();

    render(<CompilerPage />, { wrapper: createWrapper() });

    await user.type(screen.getByPlaceholderText(/paste your static html here/i), '<div>test</div>');
    fireEvent.click(screen.getByRole('button', { name: /compile html/i }));

    await waitFor(() => {
      expect(screen.getByText(/network failure/i)).toBeInTheDocument();
    });
  });
});
