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
    } catch {
      // Save failed — button returns to default state
    } finally {
      setSaving(false);
    }
  };

  return (
    <>
      <h1 className={styles.title}>API config</h1>

      <div className={styles.card}>
        <div className={styles.cardTitle}>AI Model</div>
        <Select
          label="Default model"
          value={model}
          onChange={(e) => setModel(e.target.value)}
          options={[
            { value: 'claude-opus-4-5',   label: 'claude-opus-4-5' },
            { value: 'claude-sonnet-4-6', label: 'claude-sonnet-4-6' },
          ]}
        />
        <p className={styles.hint}>
          The model can also be overridden per-run in the brief form.
        </p>
      </div>

      <Button onClick={handleSave} loading={saving}>
        {saved ? 'Saved' : 'Save settings'}
      </Button>
    </>
  );
}
