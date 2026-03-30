import { useQuery } from '@tanstack/react-query';
import { getAcfGroups } from '../api/acf';

export interface AcfOption {
  value: string;
  label: string;
}

export function useAcfSchema() {
  return useQuery({
    queryKey: ['acf-groups'],
    queryFn: getAcfGroups,
    staleTime: 5 * 60 * 1000,
    select: (groups) =>
      groups.map((g) => ({
        value: g.key,
        label: `${g.title} (${g.fields.length} fields)`,
      })) satisfies AcfOption[],
  });
}
