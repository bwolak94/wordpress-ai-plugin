import { api } from './client';
import type { AcfGroup } from '../types';

export const getAcfGroups = (): Promise<AcfGroup[]> =>
  api.get<AcfGroup[]>('wp-ai-agent/v1/acf-groups');
