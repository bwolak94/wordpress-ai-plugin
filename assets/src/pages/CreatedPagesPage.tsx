import { useAgentHistory } from '../hooks/useAgentHistory';
import { Badge } from '../components/ui';
import styles from './CreatedPagesPage.module.css';

export function CreatedPagesPage() {
  const { data: history, isLoading, error } = useAgentHistory();

  if (isLoading) return <p className={styles.state}>Loading pages...</p>;
  if (error)     return <p className={styles.state}>Failed to load pages.</p>;

  const allPages = (history ?? []).flatMap((run) =>
    run.pages.map((page) => ({ ...page, runId: run.run_id }))
  );

  if (!allPages.length) return <p className={styles.state}>No pages created yet.</p>;

  return (
    <>
      <h1 className={styles.title}>Created pages</h1>
      <div className={styles.grid}>
        {allPages.map((page) => (
          <div key={`${page.runId}-${page.post_id}`} className={styles.card}>
            <div className={styles.cardTop}>
              <span className={styles.resultType}>page</span>
              <Badge variant="page">PAGE</Badge>
            </div>
            <h3 className={styles.cardTitle}>{page.title}</h3>
            <p className={styles.cardMeta}>/{page.slug} &middot; post_id: {page.post_id}</p>
            {page.acf_count > 0 && (
              <p className={styles.cardMeta}>{page.acf_count} ACF fields</p>
            )}
            <a
              href={page.edit_url}
              target="_blank"
              rel="noreferrer"
              className={styles.editLink}
            >
              Edit in WP Admin
            </a>
          </div>
        ))}
      </div>
    </>
  );
}
