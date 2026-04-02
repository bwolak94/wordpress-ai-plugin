import { render, screen } from '@testing-library/react';
import { describe, it, expect } from 'vitest';
import { AgentLog } from '../features/agent/AgentLog';
import { AgentContext } from '../store/AgentContext';
import type { AgentState, AgentAction } from '../store/agentReducer';

function renderWithState(state: AgentState) {
  const dispatch = (() => {}) as React.Dispatch<AgentAction>;
  return render(
    <AgentContext.Provider value={{ state, dispatch }}>
      <AgentLog />
    </AgentContext.Provider>
  );
}

describe('AgentLog', () => {
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

  it('renders terminal with log lines when running', () => {
    renderWithState({
      status: 'running',
      log: ['Agent started', 'tool_use → create_page'],
      result: null,
      runId: 'run_1',
      error: null,
    });

    expect(screen.getByText('agent log')).toBeInTheDocument();
    expect(screen.getByText('Agent started')).toBeInTheDocument();
    expect(screen.getByText('tool_use → create_page')).toBeInTheDocument();
  });

  it('renders terminal when status is success', () => {
    renderWithState({
      status: 'success',
      log: ['Done'],
      result: {
        success: true,
        run_id: 'run_1',
        rounds: 1,
        log: ['Done'],
        pages: [],
      },
      runId: 'run_1',
      error: null,
    });

    expect(screen.getByText('agent log')).toBeInTheDocument();
    expect(screen.getByText('Done')).toBeInTheDocument();
  });

  it('renders terminal when status is error', () => {
    renderWithState({
      status: 'error',
      log: ['Something went wrong'],
      result: null,
      runId: 'run_1',
      error: 'API failure',
    });

    expect(screen.getByText('agent log')).toBeInTheDocument();
    expect(screen.getByText('Something went wrong')).toBeInTheDocument();
  });
});
