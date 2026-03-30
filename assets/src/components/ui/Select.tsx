import React from 'react';
import styles from './Select.module.css';

interface SelectOption {
  value: string;
  label: string;
}

interface SelectProps extends React.SelectHTMLAttributes<HTMLSelectElement> {
  label: string;
  options: SelectOption[];
  error?: string;
  loading?: boolean;
}

export const Select = React.forwardRef<HTMLSelectElement, SelectProps>(
  ({ label, options, error, loading = false, id, ...rest }, ref) => {
    const fieldId = id ?? label.toLowerCase().replace(/\s+/g, '-');

    return (
      <div className={styles.field}>
        <label htmlFor={fieldId} className={styles.label}>{label}</label>
        <select
          ref={ref}
          id={fieldId}
          disabled={loading || rest.disabled}
          className={[styles.select, error ? styles.hasError : ''].join(' ')}
          {...rest}
        >
          {loading ? (
            <option value="">Loading…</option>
          ) : (
            <>
              <option value="">— Select —</option>
              {options.map((opt) => (
                <option key={opt.value} value={opt.value}>
                  {opt.label}
                </option>
              ))}
            </>
          )}
        </select>
        {error && (
          <span className={styles.error} role="alert">{error}</span>
        )}
      </div>
    );
  }
);

Select.displayName = 'Select';
