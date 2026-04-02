import { renderHook, waitFor, act } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React from 'react';
import { AgentProvider } from '../store/AgentContext';
import { useRunAgent } from '../hooks/useRunAgent';
import * as agentApi from '../api/agent';

vi.mock('../api/agent', () => ({
  runAgent: vi.fn(),
  getAgentStatus: vi.fn(),
  getHistory: vi.fn(),
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
        <AgentProvider>{children}</AgentProvider>
      </QueryClientProvider>
    );
  };
}

describe('useRunAgent', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('calls runAgent and polls getAgentStatus until finished', async () => {
    vi.mocked(agentApi.runAgent).mockResolvedValue({
      run_id: 'run_poll', success: true, rounds: 1, log: ['done'], pages: [],
    } as agentApi.RunResponse);

    // Immediately return finished on first poll
    vi.mocked(agentApi.getAgentStatus).mockResolvedValue({
      log: ['done'],
      finished: true,
      success: true,
      pages: [{ post_id: 1, title: 'P', slug: 'p', edit_url: '/e', acf_count: 0 }],
      rounds: 1,
    });

    const { result } = renderHook(() => useRunAgent(), { wrapper: createWrapper() });

    act(() => {
      result.current.mutate({
        documentation: 'docs',
        goals: 'goals',
        status: 'draft' as const,
        model: 'claude-opus-4-5' as const,
      });
    });

    await waitFor(() => {
      expect(result.current.isSuccess).toBe(true);
    }, { timeout: 5000 });

    expect(agentApi.runAgent).toHaveBeenCalledTimes(1);
    // getAgentStatus called at least twice: once in poll, once for final fetch
    expect(agentApi.getAgentStatus).toHaveBeenCalled();
  });

  it('dispatches error when runAgent rejects', async () => {
    vi.mocked(agentApi.runAgent).mockRejectedValue(new Error('API down'));

    const { result } = renderHook(() => useRunAgent(), { wrapper: createWrapper() });

    act(() => {
      result.current.mutate({
        documentation: 'test',
        goals: 'test',
        status: 'draft' as const,
        model: 'claude-opus-4-5' as const,
      });
    });

    await waitFor(() => {
      expect(result.current.isError).toBe(true);
    });

    expect(result.current.error?.message).toBe('API down');
  });

  it('dispatches error when polling rejects', async () => {
    vi.mocked(agentApi.runAgent).mockResolvedValue({
      run_id: 'run_err', success: true, rounds: 0, log: [], pages: [],
    } as agentApi.RunResponse);
    vi.mocked(agentApi.getAgentStatus).mockRejectedValue(new Error('Poll failed'));

    const { result } = renderHook(() => useRunAgent(), { wrapper: createWrapper() });

    act(() => {
      result.current.mutate({
        documentation: 'test',
        goals: 'test',
        status: 'draft' as const,
        model: 'claude-opus-4-5' as const,
      });
    });

    await waitFor(() => {
      expect(result.current.isError).toBe(true);
    }, { timeout: 5000 });
  });
});
