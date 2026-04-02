import { render, screen } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import { HistoryPage } from '../pages/HistoryPage';

const mockUseAgentHistory = vi.fn();

vi.mock('../hooks/useAgentHistory', () => ({
  useAgentHistory: () => mockUseAgentHistory(),
}));

describe('HistoryPage', () => {
  it('shows loading text initially', () => {
    mockUseAgentHistory.mockReturnValue({ data: undefined, isLoading: true, error: null });

    render(<HistoryPage />);

    expect(screen.getByText('Loading history...')).toBeInTheDocument();
  });

  it('shows error message on error', () => {
    mockUseAgentHistory.mockReturnValue({ data: undefined, isLoading: false, error: new Error('fail') });

    render(<HistoryPage />);

    expect(screen.getByText('Failed to load history.')).toBeInTheDocument();
  });

  it('shows empty state when no history', () => {
    mockUseAgentHistory.mockReturnValue({ data: [], isLoading: false, error: null });

    render(<HistoryPage />);

    expect(screen.getByText('No runs yet. Go to New brief to start.')).toBeInTheDocument();
  });

  it('renders table with history data when available', () => {
    mockUseAgentHistory.mockReturnValue({
      data: [
        {
          run_id: 'run_abc123',
          success: true,
          rounds: 3,
          pages: [{ post_id: 1, title: 'Page 1', slug: 'page-1', edit_url: '#', acf_count: 0 }],
          log: ['line1', 'line2'],
        },
        {
          run_id: 'run_def456',
          success: false,
          rounds: 1,
          pages: [],
          log: ['error line'],
        },
      ],
      isLoading: false,
      error: null,
    });

    render(<HistoryPage />);

    expect(screen.getByText('History')).toBeInTheDocument();
    expect(screen.getByText('run_abc123')).toBeInTheDocument();
    expect(screen.getByText('run_def456')).toBeInTheDocument();
    expect(screen.getByText('success')).toBeInTheDocument();
    expect(screen.getByText('error')).toBeInTheDocument();

    // Check table rows exist with expected content (avoid ambiguous single-digit matches)
    const rows = screen.getAllByRole('row');
    // header + 2 data rows
    expect(rows).toHaveLength(3);
  });
});
