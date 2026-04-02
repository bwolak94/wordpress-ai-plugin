import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useRunAgent } from '../../hooks/useRunAgent';
import { useAcfSchema } from '../../hooks/useAcfSchema';
import { useAgent } from '../../store/AgentContext';
import { Button, Textarea, Select } from '../../components/ui';
import styles from './BriefForm.module.css';

const briefSchema = z.object({
  documentation: z.string().min(10, 'Documentation must be at least 10 characters'),
  goals:         z.string().min(5, 'Goals must be at least 5 characters'),
  target_url:    z.string().url('Must be a valid URL').optional().or(z.literal('')),
  parent_id:     z.number().optional(),
  acf_group_key: z.string().optional(),
  status:        z.enum(['draft', 'publish']).default('draft'),
  model:         z.enum(['claude-opus-4-5', 'claude-sonnet-4-6']).default('claude-opus-4-5'),
});

type BriefFormValues = z.infer<typeof briefSchema>;

export function BriefForm() {
  const { state } = useAgent();
  const runAgent  = useRunAgent();
  const { data: acfOptions, isLoading: acfLoading } = useAcfSchema();

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<BriefFormValues>({
    resolver: zodResolver(briefSchema),
    defaultValues: { status: 'draft', model: 'claude-opus-4-5' },
  });

  const onSubmit = (data: BriefFormValues) => {
    runAgent.mutate(data);
  };

  const isRunning = state.status === 'running';

  return (
    <>
      <form onSubmit={handleSubmit(onSubmit)} noValidate>
        <div className={styles.card}>
          <div className={styles.cardTitle}>Brief</div>
          <div className={styles.formGrid}>
            <div className={styles.full}>
              <Textarea
                label="Documentation"
                {...register('documentation')}
                error={errors.documentation?.message}
                placeholder="Paste your product documentation, feature descriptions, copy..."
                rows={5}
                disabled={isRunning}
              />
            </div>
            <div className={styles.full}>
              <Textarea
                label="Goals"
                {...register('goals')}
                error={errors.goals?.message}
                placeholder="What should this page achieve?"
                rows={3}
                disabled={isRunning}
              />
            </div>
            <Select
              label="ACF group"
              {...register('acf_group_key')}
              options={acfOptions ?? []}
              loading={acfLoading}
              error={errors.acf_group_key?.message}
              disabled={isRunning}
            />
            <Select
              label="Status"
              {...register('status')}
              options={[
                { value: 'draft',   label: 'draft' },
                { value: 'publish', label: 'publish' },
              ]}
              disabled={isRunning}
            />
            <Select
              label="Model"
              {...register('model')}
              options={[
                { value: 'claude-opus-4-5',   label: 'claude-opus-4-5' },
                { value: 'claude-sonnet-4-6', label: 'claude-sonnet-4-6' },
              ]}
              disabled={isRunning}
            />
          </div>
        </div>

        <div className={styles.actions} style={{ marginTop: 20 }}>
          <Button
            type="submit"
            loading={isRunning}
            disabled={isRunning}
            icon="play"
          >
            {isRunning ? 'Running...' : 'Run agent'}
          </Button>

          {(state.status === 'success' || state.status === 'error') && (
            <Button
              type="button"
              variant="secondary"
              icon="reset"
              onClick={() => { reset(); runAgent.reset(); }}
            >
              New run
            </Button>
          )}
        </div>

        {state.status === 'error' && state.error && (
          <p className={styles.globalError} role="alert">
            {state.error}
          </p>
        )}
      </form>
    </>
  );
}
