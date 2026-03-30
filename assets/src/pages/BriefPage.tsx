import { BriefForm } from '../features/brief/BriefForm';
import { AgentLog } from '../features/agent/AgentLog';
import { ResultsGrid } from '../features/results/ResultsGrid';
import styles from './BriefPage.module.css';

export function BriefPage() {
  return (
    <div className={styles.page}>
      <header className={styles.header}>
        <h1 className={styles.title}>AI Page Builder</h1>
        <p className={styles.subtitle}>
          Give the agent your documentation and goals — it will create WordPress pages automatically.
        </p>
      </header>

      <main className={styles.main}>
        <BriefForm />
        <AgentLog />
        <ResultsGrid />
      </main>
    </div>
  );
}
