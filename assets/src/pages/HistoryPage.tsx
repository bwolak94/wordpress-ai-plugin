import { useAgentHistory } from '../hooks/useAgentHistory';
import { Badge } from '../components/ui';
import styles from './HistoryPage.module.css';

export function HistoryPage() {
  const { data: history, isLoading, error } = useAgentHistory();

  if (isLoading) return <p className={styles.state}>Loading history…</p>;
  if (error)     return <p className={styles.state}>Failed to load history.</p>;
  if (!history?.length) return <p className={styles.state}>No runs yet. Go to Run Agent to start.</p>;

  return (
    <div className={styles.page}>
      <h1 className={styles.title}>Run History</h1>
      <table className={styles.table}>
        <thead>
          <tr>
            <th>Run ID</th>
            <th>Status</th>
            <th>Rounds</th>
            <th>Pages</th>
            <th>Log lines</th>
          </tr>
        </thead>
        <tbody>
          {history.map((run) => (
            <tr key={run.run_id}>
              <td><code>{run.run_id}</code></td>
              <td>
                <Badge variant={run.success ? 'success' : 'error'}>
                  {run.success ? 'success' : 'error'}
                </Badge>
              </td>
              <td>{run.rounds}</td>
              <td>{run.pages.length}</td>
              <td>{run.log.length}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
