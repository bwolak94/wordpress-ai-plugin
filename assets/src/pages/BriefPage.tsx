import { BriefForm } from '../features/brief/BriefForm';
import { AgentLog } from '../features/agent/AgentLog';
import { ResultsGrid } from '../features/results/ResultsGrid';
import styles from './BriefPage.module.css';

export function BriefPage() {
  return (
    <>
      <div className={styles.header}>
        <h1 className={styles.title}>New brief</h1>
        <p className={styles.subtitle}>
          Describe your documentation and goals — the agent will build the page.
        </p>
      </div>

      <BriefForm />
      <AgentLog />
      <ResultsGrid />
    </>
  );
}
