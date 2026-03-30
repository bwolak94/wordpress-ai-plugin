export interface AgentResult {
  success: boolean;
  run_id: string;
  rounds: number;
  log: string[];
  pages: CreatedPage[];
  error?: string;
}

export interface CreatedPage {
  post_id: number;
  title: string;
  slug: string;
  edit_url: string;
  acf_count: number;
}
