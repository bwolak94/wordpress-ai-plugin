import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect } from 'vitest';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { AdminLayout } from '../layout/AdminLayout';

function Wrapper({ children }: { children: React.ReactNode }) {
  return (
    <QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}>
      {children}
    </QueryClientProvider>
  );
}

describe('AdminLayout', () => {
  it('renders the topbar with logo and status pill', () => {
    render(<AdminLayout />, { wrapper: Wrapper });

    expect(screen.getByText('WP AI Agent')).toBeInTheDocument();
    expect(screen.getByText('idle')).toBeInTheDocument();
  });

  it('renders sidebar navigation sections', () => {
    render(<AdminLayout />, { wrapper: Wrapper });

    expect(screen.getByText('Agent')).toBeInTheDocument();
    expect(screen.getByText('Pages')).toBeInTheDocument();
    expect(screen.getByText('Settings')).toBeInTheDocument();
  });

  it('renders sidebar navigation items', () => {
    render(<AdminLayout />, { wrapper: Wrapper });

    expect(screen.getByRole('button', { name: /new brief/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /history/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /created pages/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /acf schemas/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /api config/i })).toBeInTheDocument();
  });

  it('shows New brief page by default', () => {
    render(<AdminLayout />, { wrapper: Wrapper });

    expect(screen.getByRole('heading', { name: /new brief/i })).toBeInTheDocument();
    expect(screen.getByText(/describe your documentation/i)).toBeInTheDocument();
  });

  it('switches to History page when History sidebar item is clicked', () => {
    render(<AdminLayout />, { wrapper: Wrapper });

    fireEvent.click(screen.getByRole('button', { name: /history/i }));

    // History page renders either heading or loading state
    expect(screen.getByRole('button', { name: /history/i })).toHaveAttribute('aria-current', 'page');
  });

  it('switches to API config page when API config sidebar item is clicked', () => {
    render(<AdminLayout />, { wrapper: Wrapper });

    fireEvent.click(screen.getByRole('button', { name: /api config/i }));

    expect(screen.getByRole('heading', { name: /api config/i })).toBeInTheDocument();
  });

  it('marks the active sidebar item with aria-current', () => {
    render(<AdminLayout />, { wrapper: Wrapper });

    const briefButton = screen.getByRole('button', { name: /new brief/i });
    expect(briefButton).toHaveAttribute('aria-current', 'page');

    fireEvent.click(screen.getByRole('button', { name: /history/i }));

    expect(briefButton).not.toHaveAttribute('aria-current');
    expect(screen.getByRole('button', { name: /history/i })).toHaveAttribute('aria-current', 'page');
  });
});
