import React from 'react';
import styles from './Badge.module.css';

type BadgeVariant = 'page' | 'draft' | 'publish' | 'info' | 'success' | 'error';

interface BadgeProps {
  variant?: BadgeVariant;
  children: React.ReactNode;
}

export function Badge({ variant = 'info', children }: BadgeProps) {
  return (
    <span className={[styles.badge, styles[variant]].join(' ')}>
      {children}
    </span>
  );
}
