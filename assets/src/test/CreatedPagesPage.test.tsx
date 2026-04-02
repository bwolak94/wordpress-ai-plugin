import { render, screen } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import { CreatedPagesPage } from '../pages/CreatedPagesPage';

const mockUseAgentHistory = vi.fn();

vi.mock('../hooks/useAgentHistory', () => ({
  useAgentHistory: () => mockUseAgentHistory(),
}));

describe('CreatedPagesPage', () => {
  it('shows loading state', () => {
    mockUseAgentHistory.mockReturnValue({ data: undefined, isLoading: true, error: null });

    render(<CreatedPagesPage />);

    expect(screen.getByText('Loading pages...')).toBeInTheDocument();
  });

  it('shows empty state when no pages created', () => {
    mockUseAgentHistory.mockReturnValue({ data: [], isLoading: false, error: null });

    render(<CreatedPagesPage />);

    expect(screen.getByText('No pages created yet.')).toBeInTheDocument();
  });

  it('shows error state', () => {
    mockUseAgentHistory.mockReturnValue({ data: undefined, isLoading: false, error: new Error('fail') });

    render(<CreatedPagesPage />);

    expect(screen.getByText('Failed to load pages.')).toBeInTheDocument();
  });

  it('shows page cards with title, slug, post_id, and edit link', () => {
    mockUseAgentHistory.mockReturnValue({
      data: [
        {
          run_id: 'run_1',
          success: true,
          rounds: 2,
          pages: [
            {
              post_id: 42,
              title: 'Landing Page',
              slug: 'landing-page',
              edit_url: 'http://localhost/wp-admin/post.php?post=42',
              acf_count: 3,
            },
            {
              post_id: 99,
              title: 'About Us',
              slug: 'about-us',
              edit_url: 'http://localhost/wp-admin/post.php?post=99',
              acf_count: 0,
            },
          ],
          log: [],
        },
      ],
      isLoading: false,
      error: null,
    });

    render(<CreatedPagesPage />);

    expect(screen.getByText('Created pages')).toBeInTheDocument();
    expect(screen.getByText('Landing Page')).toBeInTheDocument();
    expect(screen.getByText('About Us')).toBeInTheDocument();
    expect(screen.getByText(/\/landing-page/)).toBeInTheDocument();
    expect(screen.getByText(/post_id: 42/)).toBeInTheDocument();
    expect(screen.getByText(/post_id: 99/)).toBeInTheDocument();
    expect(screen.getByText('3 ACF fields')).toBeInTheDocument();

    const editLinks = screen.getAllByText('Edit in WP Admin');
    expect(editLinks).toHaveLength(2);
    expect(editLinks[0].closest('a')).toHaveAttribute('href', 'http://localhost/wp-admin/post.php?post=42');
    expect(editLinks[1].closest('a')).toHaveAttribute('href', 'http://localhost/wp-admin/post.php?post=99');
  });
});
