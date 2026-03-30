import { useMutation } from '@tanstack/react-query';
import { runAgent, getAgentStatus } from '../api/agent';
import { useAgent } from '../store/AgentContext';
import type { Brief } from '../types';

export function useRunAgent() {
  const { dispatch } = useAgent();

  return useMutation({
    mutationFn: async (brief: Brief) => {
      dispatch({ type: 'START' });

      const { run_id } = await runAgent(brief);
      dispatch({ type: 'SET_RUN_ID', payload: run_id });

      // Poll for live log updates
      await pollUntilDone(run_id, (line) => {
        dispatch({ type: 'LOG_LINE', payload: line });
      });

      // Fetch final result
      const final = await getAgentStatus(run_id);

      dispatch({
        type: 'SUCCESS',
        payload: {
          success: final.success ?? true,
          run_id,
          rounds: final.rounds ?? 0,
          log: final.log ?? [],
          pages: final.pages ?? [],
        },
      });

      return final;
    },
    onError: (err: Error) => {
      dispatch({ type: 'ERROR', payload: err.message });
    },
  });
}

async function pollUntilDone(
  runId: string,
  onLine: (line: string) => void,
  intervalMs = 1000,
): Promise<void> {
  let seen = 0;

  return new Promise((resolve, reject) => {
    const timer = setInterval(async () => {
      try {
        const { log, finished } = await getAgentStatus(runId);

        log.slice(seen).forEach(onLine);
        seen = log.length;

        if (finished) {
          clearInterval(timer);
          resolve();
        }
      } catch (err) {
        clearInterval(timer);
        reject(err);
      }
    }, intervalMs);
  });
}
