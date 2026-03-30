import type { AgentResult } from '../types';

export type AgentStatus = 'idle' | 'running' | 'success' | 'error';

export interface AgentState {
  status: AgentStatus;
  log: string[];
  result: AgentResult | null;
  runId: string | null;
  error: string | null;
}

export type AgentAction =
  | { type: 'START' }
  | { type: 'SET_RUN_ID'; payload: string }
  | { type: 'LOG_LINE'; payload: string }
  | { type: 'SUCCESS'; payload: AgentResult }
  | { type: 'ERROR'; payload: string }
  | { type: 'RESET' };

export const initialState: AgentState = {
  status: 'idle',
  log: [],
  result: null,
  runId: null,
  error: null,
};

export function agentReducer(state: AgentState, action: AgentAction): AgentState {
  switch (action.type) {
    case 'START':
      return { ...initialState, status: 'running' };

    case 'SET_RUN_ID':
      return { ...state, runId: action.payload };

    case 'LOG_LINE':
      return { ...state, log: [...state.log, action.payload] };

    case 'SUCCESS':
      return { ...state, status: 'success', result: action.payload };

    case 'ERROR':
      return { ...state, status: 'error', error: action.payload };

    case 'RESET':
      return initialState;

    default:
      return state;
  }
}
