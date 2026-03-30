import { api } from './client';
import type { Brief, AgentResult } from '../types';

export interface RunResponse extends AgentResult {
  run_id: string;
}

export interface StatusResponse {
  log: string[];
  finished: boolean;
  success?: boolean;
  pages?: AgentResult['pages'];
  rounds?: number;
}

export const runAgent = (brief: Brief): Promise<RunResponse> =>
  api.post<RunResponse>('wp-ai-agent/v1/run', brief);

export const getAgentStatus = (runId: string): Promise<StatusResponse> =>
  api.get<StatusResponse>(`wp-ai-agent/v1/status/${runId}`);

export const getHistory = (): Promise<AgentResult[]> =>
  api.get<AgentResult[]>('wp-ai-agent/v1/history');
