import { AgentProvider } from './store/AgentContext';
import { BriefPage } from './pages/BriefPage';

export default function App() {
  return (
    <AgentProvider>
      <BriefPage />
    </AgentProvider>
  );
}
