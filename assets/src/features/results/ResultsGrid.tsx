import { useAgent } from '../../store/AgentContext';
import { Badge } from '../../components/ui';
import styles from './ResultsGrid.module.css';

export function ResultsGrid() {
  const { state } = useAgent();

  if (state.status !== 'success' || !state.result) return null;

  const { result } = state;

  const acfCount = result.pages.reduce((sum, p) => sum + p.acf_count, 0);

  return (
    <section className={styles.section}>
      <h2 className={styles.heading}>Results</h2>

      <div className={styles.grid}>
        {result.pages.map((page) => (
          <div key={page.post_id} className={styles.card}>
            <div className={styles.cardTop}>
              <span className={styles.resultType}>page</span>
              <Badge variant="page">PAGE</Badge>
            </div>
            <h3 className={styles.cardTitle}>{page.title}</h3>
            <p className={styles.cardMeta}>/{page.slug} &middot; post_id: {page.post_id}</p>
            <div className={styles.cardFooter}>
              <Badge variant="draft">DRAFT</Badge>
            </div>
          </div>
        ))}

        {acfCount > 0 && (
          <div className={styles.card}>
            <div className={styles.cardTop}>
              <span className={styles.resultType}>acf</span>
              <Badge variant="success">ACF</Badge>
            </div>
            <h3 className={styles.cardTitle}>{acfCount} fields set</h3>
            <p className={styles.cardMeta}>across {result.pages.length} page{result.pages.length !== 1 ? 's' : ''}</p>
            <div className={styles.cardFooter}>
              <Badge variant="success">OK</Badge>
            </div>
          </div>
        )}

        <div className={styles.card}>
          <div className={styles.cardTop}>
            <span className={styles.resultType}>agent</span>
            <Badge variant="page">AGENT</Badge>
          </div>
          <h3 className={styles.cardTitle}>{result.rounds} round{result.rounds !== 1 ? 's' : ''}</h3>
          <p className={styles.cardMeta}>{result.log.length} tool calls</p>
          <div className={styles.cardFooter}>
            <Badge variant="success">DONE</Badge>
          </div>
        </div>
      </div>
    </section>
  );
}
