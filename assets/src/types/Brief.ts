export interface Brief {
  documentation: string;
  goals: string;
  target_url?: string;
  parent_id?: number;
  acf_group_key?: string;
  status: 'draft' | 'publish';
  model: 'claude-opus-4-5' | 'claude-sonnet-4-6';
}
