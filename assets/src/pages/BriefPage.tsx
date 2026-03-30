import { BriefForm } from '../features/brief/BriefForm';
import { AgentLog } from '../features/agent/AgentLog';
import { ResultsGrid } from '../features/results/ResultsGrid';

export function BriefPage() {
  return (
    <div className="wrap">
      <h1>AI Page Builder</h1>
      <BriefForm />
      <AgentLog />
      <ResultsGrid />
    </div>
  );
}
