import { render, screen } from '@testing-library/react';
import { describe, it, expect } from 'vitest';
import { ResultsGrid } from '../features/results/ResultsGrid';
import { AgentContext } from '../store/AgentContext';
import type { AgentState, AgentAction } from '../store/agentReducer';

function renderWithState(state: AgentState) {
  const dispatch = (() => {}) as React.Dispatch<AgentAction>;
  return render(
    <AgentContext.Provider value={{ state, dispatch }}>
      <ResultsGrid />
    </AgentContext.Provider>
  );
}

describe('ResultsGrid', () => {
  it('renders nothing when status is idle', () => {
    const { container } = renderWithState({
      status: 'idle',
      log: [],
      result: null,
      runId: null,
      error: null,
    });
    expect(container.innerHTML).toBe('');
  });

  it('renders nothing when status is running', () => {
    const { container } = renderWithState({
      status: 'running',
      log: ['line 1'],
      result: null,
      runId: 'run_1',
      error: null,
    });
    expect(container.innerHTML).toBe('');
  });

  it('renders page cards and agent summary on success', () => {
    renderWithState({
      status: 'success',
      log: ['line 1', 'line 2'],
      result: {
        success: true,
        run_id: 'run_1',
        rounds: 2,
        log: ['line 1', 'line 2'],
        pages: [
          { post_id: 42, title: 'Test Page', slug: 'test-page', edit_url: '/wp-admin/post.php?post=42', acf_count: 3 },
        ],
      },
      runId: 'run_1',
      error: null,
    });

    expect(screen.getByText('Results')).toBeInTheDocument();
    expect(screen.getByText('Test Page')).toBeInTheDocument();
    expect(screen.getByText(/test-page/)).toBeInTheDocument();
    expect(screen.getByText(/post_id: 42/)).toBeInTheDocument();
    expect(screen.getByText('3 fields set')).toBeInTheDocument();
    expect(screen.getByText('2 rounds')).toBeInTheDocument();
    expect(screen.getByText('2 tool calls')).toBeInTheDocument();
  });

  it('does not render ACF card when no fields were set', () => {
    renderWithState({
      status: 'success',
      log: [],
      result: {
        success: true,
        run_id: 'run_2',
        rounds: 1,
        log: [],
        pages: [
          { post_id: 10, title: 'No ACF', slug: 'no-acf', edit_url: '/wp-admin/post.php?post=10', acf_count: 0 },
        ],
      },
      runId: 'run_2',
      error: null,
    });

    expect(screen.queryByText(/fields set/)).not.toBeInTheDocument();
  });
});
