import { useAcfSchema } from '../hooks/useAcfSchema';
import { Badge } from '../components/ui';
import styles from './AcfSchemasPage.module.css';

export function AcfSchemasPage() {
  const { data: groups, isLoading, error } = useAcfSchema();

  if (isLoading) return <p className={styles.state}>Loading ACF schemas...</p>;
  if (error)     return <p className={styles.state}>Failed to load ACF schemas.</p>;
  if (!groups?.length) return <p className={styles.state}>No ACF field groups found.</p>;

  return (
    <>
      <h1 className={styles.title}>ACF schemas</h1>
      <div className={styles.grid}>
        {groups.map((group) => (
          <div key={group.value} className={styles.card}>
            <div className={styles.cardTop}>
              <span className={styles.resultType}>group</span>
              <Badge variant="success">ACF</Badge>
            </div>
            <h3 className={styles.cardTitle}>{group.label}</h3>
            <p className={styles.cardMeta}>{group.value}</p>
          </div>
        ))}
      </div>
    </>
  );
}
