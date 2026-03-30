import { useAgent } from '../../store/AgentContext';
import { Terminal } from '../../components/ui';
import styles from './AgentLog.module.css';

export function AgentLog() {
  const { state } = useAgent();

  if (state.status === 'idle') return null;

  return (
    <div className={styles.wrapper}>
      <Terminal
        lines={state.log}
        isRunning={state.status === 'running'}
        title="agent log"
      />
    </div>
  );
}
