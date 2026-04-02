import { useAgent } from '../../store/AgentContext';
import { Terminal } from '../../components/ui';

export function AgentLog() {
  const { state } = useAgent();

  if (state.status === 'idle') return null;

  return (
    <Terminal
      lines={state.log}
      isRunning={state.status === 'running'}
      title="agent log"
    />
  );
}
