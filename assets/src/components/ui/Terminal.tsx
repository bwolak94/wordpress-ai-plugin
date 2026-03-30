import { useEffect, useRef } from 'react';
import styles from './Terminal.module.css';

interface TerminalProps {
  lines: string[];
  isRunning?: boolean;
  title?: string;
  classifyLine?: (line: string) => string;
}

function defaultClassify(line: string): string {
  if (line.includes('ERROR') || line.includes('error') || line.includes('failed')) return styles.lineError;
  if (line.includes('OK') || line.includes('success') || line.includes('created')) return styles.lineOk;
  if (line.includes('Round') || line.includes('sending') || line.includes('running')) return styles.lineWait;
  if (line.includes('tool_use') || line.includes('[')) return styles.lineTool;
  return styles.lineInfo;
}

export function Terminal({ lines, isRunning = false, title = 'agent log', classifyLine }: TerminalProps) {
  const bottomRef = useRef<HTMLDivElement>(null);
  const classify = classifyLine ?? defaultClassify;

  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [lines]);

  return (
    <div className={styles.terminal}>
      <div className={styles.termBar}>
        <span className={styles.dot} style={{ background: '#ef4444' }} />
        <span className={styles.dot} style={{ background: '#f59e0b' }} />
        <span className={styles.dot} style={{ background: '#22c55e' }} />
        <span className={styles.label}>{title}</span>
      </div>
      <div className={styles.body}>
        {lines.map((line, i) => (
          <div key={i} className={`${styles.line} ${classify(line)}`}>
            <span className={styles.lineNum}>{String(i + 1).padStart(2, '0')}</span>
            <span>{line}</span>
          </div>
        ))}
        {isRunning && (
          <div className={styles.line}>
            <span className={styles.lineNum}>&nbsp;&nbsp;</span>
            <span className={styles.cursor} aria-hidden />
          </div>
        )}
        <div ref={bottomRef} />
      </div>
    </div>
  );
}
