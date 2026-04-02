import { api } from './client';

export interface CompileRequest {
  html: string;
  template?: string;
  prefix?: string;
  page_id?: number;
}

export interface DowngradedField {
  original_type: string;
  fallback_type: string;
  field_key: string;
}

export interface CompileResult {
  success: boolean;
  sections: unknown[];
  fields: unknown[];
  section_count: number;
  field_count: number;
  shared_count: number;
  is_acf_pro: boolean;
  acf_version: string;
  downgraded_fields: DowngradedField[];
  upgrade_notice: string | null;
  error: string | null;
}

export interface AcfStatus {
  acf_active: boolean;
  acf_pro: boolean;
  acf_version: string;
  field_types: string[];
}

export interface CompilerHistoryItem extends CompileResult {
  prefix: string;
  created_at: number;
}

export async function compileHtml(data: CompileRequest): Promise<CompileResult> {
  return api.post<CompileResult>('wp-ai-agent/v1/compile', data);
}

export async function getAcfStatus(): Promise<AcfStatus> {
  return api.get<AcfStatus>('wp-ai-agent/v1/acf-status');
}

export async function getCompilerHistory(): Promise<CompilerHistoryItem[]> {
  return api.get<CompilerHistoryItem[]>('wp-ai-agent/v1/compiler-history');
}
