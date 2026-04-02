import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect, vi } from 'vitest';
import { SettingsPage } from '../pages/SettingsPage';

const mockPost = vi.fn();

vi.mock('../api', () => ({
  api: {
    post: (...args: unknown[]) => mockPost(...args),
  },
}));

describe('SettingsPage', () => {
  beforeEach(() => {
    mockPost.mockReset();
  });

  it('renders heading "API config"', () => {
    render(<SettingsPage />);

    expect(screen.getByText('API config')).toBeInTheDocument();
  });

  it('renders model select with default value', () => {
    render(<SettingsPage />);

    const select = screen.getByLabelText(/default model/i) as HTMLSelectElement;
    expect(select).toBeInTheDocument();
    expect(select.value).toBe('claude-opus-4-5');
  });

  it('shows "Save settings" button', () => {
    render(<SettingsPage />);

    expect(screen.getByRole('button', { name: /save settings/i })).toBeInTheDocument();
  });

  it('calls api.post and shows "Saved" text after click', async () => {
    mockPost.mockResolvedValue({});
    const user = userEvent.setup();

    render(<SettingsPage />);

    await user.click(screen.getByRole('button', { name: /save settings/i }));

    await waitFor(() => {
      expect(mockPost).toHaveBeenCalledWith('wp-ai-agent/v1/settings', { model: 'claude-opus-4-5' });
    });

    await waitFor(() => {
      expect(screen.getByRole('button', { name: /saved/i })).toBeInTheDocument();
    });
  });

  it('handles save error gracefully', async () => {
    mockPost.mockRejectedValue(new Error('Network error'));
    const user = userEvent.setup();

    render(<SettingsPage />);

    await user.click(screen.getByRole('button', { name: /save settings/i }));

    await waitFor(() => {
      expect(mockPost).toHaveBeenCalled();
    });

    // Button should return to normal state (not stuck in saving)
    await waitFor(() => {
      expect(screen.getByRole('button', { name: /save settings/i })).toBeInTheDocument();
    });
  });
});
