import { useState } from 'react';
import { BriefPage } from '../pages/BriefPage';
import { HistoryPage } from '../pages/HistoryPage';
import { SettingsPage } from '../pages/SettingsPage';
import { CreatedPagesPage } from '../pages/CreatedPagesPage';
import { AcfSchemasPage } from '../pages/AcfSchemasPage';
import { CompilerPage } from '../pages/CompilerPage';
import { AgentProvider, useAgent } from '../store/AgentContext';
import styles from './AdminLayout.module.css';

type Tab = 'brief' | 'compiler' | 'history' | 'pages' | 'acf' | 'settings';

interface SidebarSection {
  label: string;
  items: SidebarItem[];
}

interface SidebarItem {
  id: Tab;
  label: string;
  icon: React.ReactNode;
  badge?: number;
}

const BriefIcon = () => (
  <svg className={styles.sidebarIcon} viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.5">
    <rect x="2" y="2" width="12" height="12" rx="2" />
    <path d="M5 8h6M5 5h3M5 11h4" />
  </svg>
);

const HistoryIcon = () => (
  <svg className={styles.sidebarIcon} viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.5">
    <circle cx="8" cy="8" r="6" />
    <path d="M8 5v3l2 2" />
  </svg>
);

const PagesIcon = () => (
  <svg className={styles.sidebarIcon} viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.5">
    <path d="M3 3h10v10H3zM3 7h10" />
  </svg>
);

const AcfIcon = () => (
  <svg className={styles.sidebarIcon} viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.5">
    <circle cx="8" cy="8" r="5" />
    <path d="M8 6v2l1.5 1.5" />
  </svg>
);

const SettingsIcon = () => (
  <svg className={styles.sidebarIcon} viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.5">
    <circle cx="8" cy="8" r="3" />
    <path d="M8 1v2M8 13v2M1 8h2M13 8h2" />
  </svg>
);

const CompilerIcon = () => (
  <svg className={styles.sidebarIcon} viewBox="0 0 16 16" fill="currentColor">
    <path d="M8 1l7 4v6l-7 4-7-4V5l7-4z" />
  </svg>
);

const SIDEBAR_SECTIONS: SidebarSection[] = [
  {
    label: 'Agent',
    items: [
      { id: 'brief', label: 'New brief', icon: <BriefIcon /> },
      { id: 'compiler', label: 'HTML Compiler', icon: <CompilerIcon /> },
      { id: 'history', label: 'History', icon: <HistoryIcon /> },
    ],
  },
  {
    label: 'Pages',
    items: [
      { id: 'pages', label: 'Created pages', icon: <PagesIcon /> },
      { id: 'acf', label: 'ACF schemas', icon: <AcfIcon /> },
    ],
  },
  {
    label: 'Settings',
    items: [
      { id: 'settings', label: 'API config', icon: <SettingsIcon /> },
    ],
  },
];

function StatusPill() {
  const { state } = useAgent();
  const isRunning = state.status === 'running';

  return (
    <div className={`${styles.statusPill} ${isRunning ? styles.statusRunning : styles.statusIdle}`}>
      <div className={`${styles.statusDot} ${isRunning ? styles.statusDotRunning : styles.statusDotIdle}`} />
      <span>{isRunning ? 'running' : 'idle'}</span>
    </div>
  );
}

function LayoutInner() {
  const [activeTab, setActiveTab] = useState<Tab>('brief');

  return (
    <div className={styles.app}>
      <div className={styles.topbar}>
        <div className={styles.logo}>
          <div className={styles.logoDot} />
          WP AI Agent
        </div>
        <div className={styles.topbarSpacer} />
        <StatusPill />
      </div>

      <nav className={styles.sidebar} role="navigation" aria-label="Main navigation">
        {SIDEBAR_SECTIONS.map((section) => (
          <div key={section.label}>
            <div className={styles.sidebarLabel}>{section.label}</div>
            {section.items.map((item) => (
              <button
                key={item.id}
                className={`${styles.sidebarItem} ${activeTab === item.id ? styles.sidebarItemActive : ''}`}
                onClick={() => setActiveTab(item.id)}
                aria-current={activeTab === item.id ? 'page' : undefined}
              >
                {item.icon}
                {item.label}
                {item.badge !== undefined && (
                  <span className={styles.badgeCount}>{item.badge}</span>
                )}
              </button>
            ))}
          </div>
        ))}
      </nav>

      <main className={styles.main}>
        {activeTab === 'brief' && <BriefPage />}
        {activeTab === 'compiler' && <CompilerPage />}
        {activeTab === 'history' && <HistoryPage />}
        {activeTab === 'pages' && <CreatedPagesPage />}
        {activeTab === 'acf' && <AcfSchemasPage />}
        {activeTab === 'settings' && <SettingsPage />}
      </main>
    </div>
  );
}

export function AdminLayout() {
  return (
    <AgentProvider>
      <LayoutInner />
    </AgentProvider>
  );
}
