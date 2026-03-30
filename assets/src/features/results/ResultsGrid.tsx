import { useAgent } from '../../store/AgentContext';
import { Badge } from '../../components/ui';
import styles from './ResultsGrid.module.css';

export function ResultsGrid() {
  const { state } = useAgent();

  if (state.status !== 'success' || !state.result) return null;

  const { result } = state;

  return (
    <section className={styles.section}>
      <h2 className={styles.heading}>
        Agent finished in {result.rounds} round{result.rounds !== 1 ? 's' : ''}
      </h2>

      <div className={styles.grid}>
        {result.pages.map((page) => (
          <div key={page.post_id} className={styles.card}>
            <div className={styles.cardTop}>
              <Badge variant="page">PAGE</Badge>
              <Badge variant="draft">draft</Badge>
            </div>
            <h3 className={styles.cardTitle}>{page.title}</h3>
            <p className={styles.cardMeta}>/{page.slug} · post_id: {page.post_id}</p>
            {page.acf_count > 0 && (
              <p className={styles.cardMeta}>{page.acf_count} ACF fields set</p>
            )}
            <a
              href={page.edit_url}
              target="_blank"
              rel="noreferrer"
              className={styles.editLink}
            >
              Edit in WP Admin →
            </a>
          </div>
        ))}

        <div className={styles.card}>
          <div className={styles.cardTop}>
            <Badge variant="info">AGENT</Badge>
            <Badge variant="success">done</Badge>
          </div>
          <h3 className={styles.cardTitle}>{result.rounds} rounds</h3>
          <p className={styles.cardMeta}>{result.log.length} tool calls</p>
        </div>
      </div>
    </section>
  );
}
