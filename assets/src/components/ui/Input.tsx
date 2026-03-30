import React from 'react';
import styles from './Input.module.css';

interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label: string;
  error?: string;
}

export const Input = React.forwardRef<HTMLInputElement, InputProps>(
  ({ label, error, id, ...rest }, ref) => {
    const fieldId = id ?? label.toLowerCase().replace(/\s+/g, '-');

    return (
      <div className={styles.field}>
        <label htmlFor={fieldId} className={styles.label}>{label}</label>
        <input
          ref={ref}
          id={fieldId}
          className={[styles.input, error ? styles.hasError : ''].join(' ')}
          aria-invalid={!!error}
          aria-describedby={error ? `${fieldId}-error` : undefined}
          {...rest}
        />
        {error && (
          <span id={`${fieldId}-error`} className={styles.error} role="alert">{error}</span>
        )}
      </div>
    );
  }
);

Input.displayName = 'Input';
