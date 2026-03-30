import { useState } from 'react';
import { BriefPage } from '../pages/BriefPage';
import { HistoryPage } from '../pages/HistoryPage';
import { SettingsPage } from '../pages/SettingsPage';
import { AgentProvider } from '../store/AgentContext';
import styles from './AdminLayout.module.css';

type Tab = 'run' | 'history' | 'settings';

const TABS: { id: Tab; label: string }[] = [
  { id: 'run',      label: 'Run Agent' },
  { id: 'history',  label: 'History' },
  { id: 'settings', label: 'Settings' },
];

export function AdminLayout() {
  const [activeTab, setActiveTab] = useState<Tab>('run');

  return (
    <div className={styles.layout}>
      <nav className={styles.tabs} role="tablist">
        {TABS.map((tab) => (
          <button
            key={tab.id}
            role="tab"
            aria-selected={activeTab === tab.id}
            className={[styles.tab, activeTab === tab.id ? styles.active : ''].join(' ')}
            onClick={() => setActiveTab(tab.id)}
          >
            {tab.label}
          </button>
        ))}
      </nav>

      <div className={styles.content} role="tabpanel">
        <AgentProvider>
          {activeTab === 'run'      && <BriefPage />}
          {activeTab === 'history'  && <HistoryPage />}
          {activeTab === 'settings' && <SettingsPage />}
        </AgentProvider>
      </div>
    </div>
  );
}
