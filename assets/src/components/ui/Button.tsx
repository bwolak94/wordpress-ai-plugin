import React from 'react';
import styles from './Button.module.css';

interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  loading?: boolean;
  icon?: 'play' | 'reset';
  variant?: 'primary' | 'secondary' | 'ghost';
}

export function Button({
  children,
  loading = false,
  icon,
  variant = 'primary',
  disabled,
  className,
  ...rest
}: ButtonProps) {
  return (
    <button
      {...rest}
      disabled={disabled || loading}
      className={[styles.btn, styles[variant], loading ? styles.loading : '', className ?? ''].join(' ')}
    >
      {loading && <span className={styles.spinner} aria-hidden />}
      {!loading && icon === 'play' && <span className={styles.iconPlay} aria-hidden />}
      {!loading && icon === 'reset' && <span className={styles.iconReset} aria-hidden />}
      {children}
    </button>
  );
}
