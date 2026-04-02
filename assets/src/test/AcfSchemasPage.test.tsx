import { render, screen } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import { AcfSchemasPage } from '../pages/AcfSchemasPage';

const mockUseAcfSchema = vi.fn();

vi.mock('../hooks/useAcfSchema', () => ({
  useAcfSchema: () => mockUseAcfSchema(),
}));

describe('AcfSchemasPage', () => {
  it('shows loading state', () => {
    mockUseAcfSchema.mockReturnValue({ data: undefined, isLoading: true, error: null });

    render(<AcfSchemasPage />);

    expect(screen.getByText('Loading ACF schemas...')).toBeInTheDocument();
  });

  it('shows error state', () => {
    mockUseAcfSchema.mockReturnValue({ data: undefined, isLoading: false, error: new Error('fail') });

    render(<AcfSchemasPage />);

    expect(screen.getByText('Failed to load ACF schemas.')).toBeInTheDocument();
  });

  it('shows empty state when no groups found', () => {
    mockUseAcfSchema.mockReturnValue({ data: [], isLoading: false, error: null });

    render(<AcfSchemasPage />);

    expect(screen.getByText('No ACF field groups found.')).toBeInTheDocument();
  });

  it('renders group cards with label and value', () => {
    mockUseAcfSchema.mockReturnValue({
      data: [
        { label: 'Page Fields', value: 'group_abc' },
        { label: 'Hero Section', value: 'group_xyz' },
      ],
      isLoading: false,
      error: null,
    });

    render(<AcfSchemasPage />);

    expect(screen.getByText('ACF schemas')).toBeInTheDocument();
    expect(screen.getByText('Page Fields')).toBeInTheDocument();
    expect(screen.getByText('group_abc')).toBeInTheDocument();
    expect(screen.getByText('Hero Section')).toBeInTheDocument();
    expect(screen.getByText('group_xyz')).toBeInTheDocument();

    const badges = screen.getAllByText('ACF');
    expect(badges).toHaveLength(2);
  });
});
