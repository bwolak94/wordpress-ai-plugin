import { render, screen } from '@testing-library/react';
import { describe, it, expect } from 'vitest';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import App from '../App';

function Wrapper({ children }: { children: React.ReactNode }) {
  return (
    <QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}>
      {children}
    </QueryClientProvider>
  );
}

describe('App', () => {
  it('renders the AdminLayout with topbar', () => {
    render(<App />, { wrapper: Wrapper });
    expect(screen.getByText('WP AI Agent')).toBeInTheDocument();
  });
});
