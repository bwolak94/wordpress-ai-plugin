import { useQuery } from '@tanstack/react-query';
import { getHistory } from '../api/agent';

export function useAgentHistory() {
  return useQuery({
    queryKey: ['agent-history'],
    queryFn: getHistory,
    staleTime: 30_000,
  });
}
