import { useState } from 'react';
import { useMutation, useQuery } from '@tanstack/react-query';
import { compileHtml, getAcfStatus, getCompilerHistory } from '../api/compiler';
import type { CompileResult, CompilerHistoryItem } from '../api/compiler';
import { Button } from '../components/ui';
import styles from './CompilerPage.module.css';

type ResultTab = 'sections' | 'acf' | 'fields';

const COMPILE_STEPS = [
  'Sanitizing & chunking HTML',
  'Analyzing structure via Claude',
  'Detecting reusable sections',
  'Building ACF groups',
  'Processing field types',
  'Finalizing output',
];

export function CompilerPage() {
  const [html, setHtml]           = useState('');
  const [template, setTemplate]   = useState('');
  const [prefix, setPrefix]       = useState('page_');
  const [activeStep, setActiveStep] = useState(-1);
  const [resultTab, setResultTab] = useState<ResultTab>('sections');

  const acfStatus = useQuery({ queryKey: ['acf-status'], queryFn: getAcfStatus });
  const history   = useQuery({ queryKey: ['compiler-history'], queryFn: getCompilerHistory });

  const compile = useMutation({
    mutationFn: async () => {
      setActiveStep(0);
      const stepInterval = setInterval(() => {
        setActiveStep((prev) => {
          if (prev >= COMPILE_STEPS.length - 1) {
            clearInterval(stepInterval);
            return prev;
          }
          return prev + 1;
        });
      }, 1200);

      try {
        const result = await compileHtml({ html, template, prefix });
        clearInterval(stepInterval);
        setActiveStep(COMPILE_STEPS.length);
        return result;
      } catch (err) {
        clearInterval(stepInterval);
        throw err;
      }
    },
    onSuccess: () => {
      history.refetch();
    },
  });

  const result = compile.data as CompileResult | undefined;
  const isCompiling = compile.isPending;
  const progressPct = activeStep < 0 ? 0 : Math.min(100, ((activeStep + 1) / COMPILE_STEPS.length) * 100);

  return (
    <div className={styles.layout}>
      <div className={styles.mainPanel}>
        {/* ACF Status Banner */}
        {acfStatus.data && !acfStatus.data.acf_active && (
          <div className={styles.noticeDanger}>
            ACF plugin is not active. The HTML compiler requires ACF to function.
          </div>
        )}
        {acfStatus.data && acfStatus.data.acf_active && !acfStatus.data.acf_pro && (
          <div className={styles.noticeWarning}>
            ACF Free detected (v{acfStatus.data.acf_version}). PRO field types (repeater, flexible_content, gallery) will be
            downgraded to Free equivalents.
          </div>
        )}

        {/* Input card */}
        <div className={styles.card}>
          <div className={styles.cardTitle}>HTML input</div>
          <textarea
            className={styles.textarea}
            value={html}
            onChange={(e) => setHtml(e.target.value)}
            placeholder={'Paste your static HTML here...\n<section class="hero">\n  <h1>Page headline</h1>\n  <p>Lead text</p>\n  <a href="#" class="btn">CTA</a>\n</section>'}
            disabled={isCompiling}
          />

          <div className={styles.formRow}>
            <div>
              <div className={styles.formLabel}>PHP template reference (optional)</div>
              <textarea
                className={styles.textarea}
                style={{ minHeight: 60 }}
                value={template}
                onChange={(e) => setTemplate(e.target.value)}
                placeholder="Paste a reference PHP template showing how get_field() is used..."
                disabled={isCompiling}
              />
            </div>
            <div>
              <div className={styles.formLabel}>ACF key prefix</div>
              <input
                type="text"
                className={styles.input}
                value={prefix}
                onChange={(e) => setPrefix(e.target.value)}
                placeholder="page_"
                disabled={isCompiling}
              />
            </div>
          </div>
        </div>

        {/* Compile button */}
        <Button
          onClick={() => compile.mutate()}
          loading={isCompiling}
          disabled={isCompiling || !html.trim()}
        >
          {isCompiling ? 'Compiling...' : 'Compile HTML \u2192 ACF + PHP'}
        </Button>

        {/* Progress */}
        {activeStep >= 0 && (
          <div className={styles.card}>
            <div className={styles.cardTitle}>Compilation</div>
            <div className={styles.progressBarWrap}>
              <div className={styles.progressBar} style={{ width: `${progressPct}%` }} />
            </div>
            <div className={styles.steps}>
              {COMPILE_STEPS.map((step, i) => {
                const state = i < activeStep ? 'done' : i === activeStep && isCompiling ? 'active' : i <= activeStep ? 'done' : 'wait';
                return (
                  <div key={i} className={`${styles.step} ${styles[state]}`}>
                    <div className={styles.stepDot} />
                    {step}
                  </div>
                );
              })}
            </div>
          </div>
        )}

        {/* Results */}
        {result?.success && (
          <div className={styles.card}>
            <div className={styles.cardTitle}>Compilation result</div>

            <div className={styles.statRow}>
              <div className={styles.stat}>
                <div className={styles.statVal}>{result.section_count}</div>
                <div className={styles.statLbl}>Sections</div>
              </div>
              <div className={styles.stat}>
                <div className={styles.statVal}>{result.field_count}</div>
                <div className={styles.statLbl}>ACF fields</div>
              </div>
              <div className={styles.stat}>
                <div className={styles.statVal}>{result.shared_count}</div>
                <div className={styles.statLbl}>Shared</div>
              </div>
            </div>

            {/* Downgrade warning */}
            {result.downgraded_fields.length > 0 && (
              <div className={styles.noticeWarning} style={{ marginTop: 12 }}>
                <strong>{result.downgraded_fields.length} field(s) downgraded</strong> from PRO to Free:
                <ul className={styles.downgradeList}>
                  {result.downgraded_fields.map((d, i) => (
                    <li key={i}>{d.field_key}: {d.original_type} &rarr; {d.fallback_type}</li>
                  ))}
                </ul>
              </div>
            )}

            {result.is_acf_pro && (
              <div className={styles.noticeSuccess} style={{ marginTop: 12 }}>
                ACF PRO &mdash; all field types available
              </div>
            )}

            {/* Result tabs */}
            <div className={styles.resultTabs}>
              <button
                className={`${styles.rtab} ${resultTab === 'sections' ? styles.rtabActive : ''}`}
                onClick={() => setResultTab('sections')}
              >
                Sections
              </button>
              <button
                className={`${styles.rtab} ${resultTab === 'acf' ? styles.rtabActive : ''}`}
                onClick={() => setResultTab('acf')}
              >
                ACF JSON
              </button>
              <button
                className={`${styles.rtab} ${resultTab === 'fields' ? styles.rtabActive : ''}`}
                onClick={() => setResultTab('fields')}
              >
                All fields
              </button>
            </div>

            <pre className={styles.previewFrame}>
              {resultTab === 'sections' && JSON.stringify(result.sections, null, 2)}
              {resultTab === 'acf' && JSON.stringify(result.fields, null, 2)}
              {resultTab === 'fields' && JSON.stringify(result.fields, null, 2)}
            </pre>
          </div>
        )}

        {/* Error */}
        {result && !result.success && result.error && (
          <div className={styles.noticeDanger}>{result.error}</div>
        )}
        {compile.isError && (
          <div className={styles.noticeDanger}>
            {compile.error instanceof Error ? compile.error.message : 'Compilation failed'}
          </div>
        )}
      </div>

      {/* History panel */}
      <div className={styles.historyPanel}>
        <div className={styles.histTitle}>Compilation history</div>
        {history.isLoading && <p className={styles.histMeta}>Loading...</p>}
        {history.data?.map((item: CompilerHistoryItem, i: number) => (
          <div key={i} className={styles.histItem}>
            <div className={styles.histName}>
              {item.section_count} sections, {item.field_count} fields
            </div>
            <div className={styles.histMeta}>
              {new Date(item.created_at * 1000).toLocaleString()}
            </div>
            <div className={styles.histBadges}>
              <span className={`${styles.hbadge} ${item.success ? styles.hbGreen : styles.hbAmber}`}>
                {item.success ? 'success' : 'error'}
              </span>
              <span className={`${styles.hbadge} ${styles.hbBlue}`}>
                {item.field_count} ACF fields
              </span>
              {item.downgraded_fields.length > 0 && (
                <span className={`${styles.hbadge} ${styles.hbAmber}`}>
                  {item.downgraded_fields.length} downgraded
                </span>
              )}
            </div>
          </div>
        ))}
        {history.data?.length === 0 && (
          <p className={styles.histMeta}>No compilations yet.</p>
        )}
      </div>
    </div>
  );
}
