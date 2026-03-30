import { useState } from 'react';
import { Select, Button } from '../components/ui';
import { api } from '../api';
import styles from './SettingsPage.module.css';

export function SettingsPage() {
  const [model, setModel]     = useState('claude-opus-4-5');
  const [saving, setSaving]   = useState(false);
  const [saved, setSaved]     = useState(false);

  const handleSave = async () => {
    setSaving(true);
    try {
      await api.post('wp-ai-agent/v1/settings', { model });
      setSaved(true);
      setTimeout(() => setSaved(false), 3000);
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className={styles.page}>
      <h1 className={styles.title}>Settings</h1>

      <div className={styles.section}>
        <h2 className={styles.sectionTitle}>AI Model</h2>
        <Select
          label="Default model"
          value={model}
          onChange={(e) => setModel(e.target.value)}
          options={[
            { value: 'claude-opus-4-5',   label: 'Claude Opus 4.5 — best quality' },
            { value: 'claude-sonnet-4-6', label: 'Claude Sonnet 4.6 — faster, cheaper' },
          ]}
        />
        <p className={styles.hint}>
          The model can also be overridden per-run in the Run Agent form.
        </p>
      </div>

      <Button onClick={handleSave} loading={saving}>
        {saved ? 'Saved' : 'Save Settings'}
      </Button>
    </div>
  );
}
