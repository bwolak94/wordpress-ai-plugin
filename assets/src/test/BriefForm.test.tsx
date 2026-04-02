import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect } from 'vitest';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { AgentProvider } from '../store/AgentContext';
import { BriefForm } from '../features/brief/BriefForm';

function Wrapper({ children }: { children: React.ReactNode }) {
  return (
    <QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}>
      <AgentProvider>{children}</AgentProvider>
    </QueryClientProvider>
  );
}

describe('BriefForm', () => {
  it('shows validation error when documentation is empty', async () => {
    render(<BriefForm />, { wrapper: Wrapper });

    fireEvent.click(screen.getByRole('button', { name: /run agent/i }));

    await waitFor(() => {
      expect(screen.getByText(/documentation must be at least/i)).toBeInTheDocument();
    });
  });

  it('shows validation error when goals is empty', async () => {
    render(<BriefForm />, { wrapper: Wrapper });

    await userEvent.type(screen.getByLabelText(/documentation/i), 'Some long documentation text here');

    fireEvent.click(screen.getByRole('button', { name: /run agent/i }));

    await waitFor(() => {
      expect(screen.getByText(/goals must be at least/i)).toBeInTheDocument();
    });
  });

  it('disables submit button while running', async () => {
    render(<BriefForm />, { wrapper: Wrapper });

    await userEvent.type(screen.getByLabelText(/documentation/i), 'Documentation with enough content here');
    await userEvent.type(screen.getByLabelText(/goals/i), 'Create a landing page');

    fireEvent.click(screen.getByRole('button', { name: /run agent/i }));

    await waitFor(() => {
      const button = screen.getByRole('button', { name: /running/i });
      expect(button).toBeDisabled();
    });
  });

  it('renders ACF field group select', async () => {
    render(<BriefForm />, { wrapper: Wrapper });

    await waitFor(() => {
      expect(screen.getByLabelText(/acf group/i)).toBeInTheDocument();
    });
  });

  it('renders model and status selects', () => {
    render(<BriefForm />, { wrapper: Wrapper });

    expect(screen.getByLabelText(/model/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/status/i)).toBeInTheDocument();
  });
});
