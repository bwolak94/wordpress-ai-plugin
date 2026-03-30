import { describe, it, expect } from 'vitest';
import { agentReducer, initialState } from '../agentReducer';
import type { AgentState } from '../agentReducer';
import type { AgentResult } from '../../types';

describe('agentReducer', () => {
  it('returns initialState by default', () => {
    expect(initialState.status).toBe('idle');
    expect(initialState.log).toEqual([]);
    expect(initialState.result).toBeNull();
    expect(initialState.runId).toBeNull();
    expect(initialState.error).toBeNull();
  });

  describe('START', () => {
    it('resets state and sets status to running', () => {
      const dirty: AgentState = {
        status: 'error',
        log: ['old log'],
        result: null,
        runId: 'old-run',
        error: 'old error',
      };

      const next = agentReducer(dirty, { type: 'START' });

      expect(next.status).toBe('running');
      expect(next.log).toEqual([]);
      expect(next.result).toBeNull();
      expect(next.runId).toBeNull();
      expect(next.error).toBeNull();
    });

    it('returns a new object reference', () => {
      const next = agentReducer(initialState, { type: 'START' });
      expect(next).not.toBe(initialState);
    });
  });

  describe('SET_RUN_ID', () => {
    it('sets runId', () => {
      const next = agentReducer(initialState, { type: 'SET_RUN_ID', payload: 'run_123' });
      expect(next.runId).toBe('run_123');
    });

    it('preserves other state', () => {
      const running: AgentState = { ...initialState, status: 'running', log: ['line1'] };
      const next = agentReducer(running, { type: 'SET_RUN_ID', payload: 'run_456' });
      expect(next.status).toBe('running');
      expect(next.log).toEqual(['line1']);
    });
  });

  describe('LOG_LINE', () => {
    it('appends to log array', () => {
      const state: AgentState = { ...initialState, status: 'running', log: ['first'] };
      const next = agentReducer(state, { type: 'LOG_LINE', payload: 'second' });
      expect(next.log).toEqual(['first', 'second']);
    });

    it('creates a new array (immutable)', () => {
      const state: AgentState = { ...initialState, log: ['first'] };
      const next = agentReducer(state, { type: 'LOG_LINE', payload: 'second' });
      expect(next.log).not.toBe(state.log);
    });

    it('does not mutate original state', () => {
      const state: AgentState = { ...initialState, log: ['first'] };
      agentReducer(state, { type: 'LOG_LINE', payload: 'second' });
      expect(state.log).toEqual(['first']);
    });
  });

  describe('SUCCESS', () => {
    it('sets status to success and stores result', () => {
      const result: AgentResult = {
        success: true,
        run_id: 'run_1',
        rounds: 3,
        log: ['done'],
        pages: [],
      };

      const state: AgentState = { ...initialState, status: 'running' };
      const next = agentReducer(state, { type: 'SUCCESS', payload: result });

      expect(next.status).toBe('success');
      expect(next.result).toBe(result);
    });
  });

  describe('ERROR', () => {
    it('sets status to error and stores message', () => {
      const state: AgentState = { ...initialState, status: 'running' };
      const next = agentReducer(state, { type: 'ERROR', payload: 'API failed' });

      expect(next.status).toBe('error');
      expect(next.error).toBe('API failed');
    });
  });

  describe('RESET', () => {
    it('returns to initialState', () => {
      const dirty: AgentState = {
        status: 'success',
        log: ['line'],
        result: { success: true, run_id: 'x', rounds: 1, log: [], pages: [] },
        runId: 'run_x',
        error: null,
      };

      const next = agentReducer(dirty, { type: 'RESET' });
      expect(next).toEqual(initialState);
    });
  });

  it('returns current state for unknown action', () => {
    const state = { ...initialState, status: 'running' as const };
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const next = agentReducer(state, { type: 'UNKNOWN' } as any);
    expect(next).toBe(state);
  });
});
