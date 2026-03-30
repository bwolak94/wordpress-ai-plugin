import React, { createContext, useContext, useReducer } from 'react';
import { agentReducer, initialState } from './agentReducer';
import type { AgentState, AgentAction } from './agentReducer';

interface AgentContextValue {
  state: AgentState;
  dispatch: React.Dispatch<AgentAction>;
}

const AgentContext = createContext<AgentContextValue | null>(null);

export function AgentProvider({ children }: { children: React.ReactNode }) {
  const [state, dispatch] = useReducer(agentReducer, initialState);

  return (
    <AgentContext.Provider value={{ state, dispatch }}>
      {children}
    </AgentContext.Provider>
  );
}

export function useAgent(): AgentContextValue {
  const ctx = useContext(AgentContext);
  if (!ctx) throw new Error('useAgent must be used inside AgentProvider');
  return ctx;
}
